<?php
namespace True;
/**
 * Redirects stored in a JSON file as {"from-path": "to-path-or-url"}.
 *
 * The one redirect class for the framework and every module (TrueCMS pages,
 * TrueContent articles, ...). The site serves the list from app/routes.php
 * with Redirects::redirect(); modules add entries with add() / pageMoved().
 *
 * Paths are stored without leading or trailing slashes (older files with a
 * trailing slash still match). A trailing * on the From path matches
 * anything below it; a * on the To path receives what it matched:
 *
 *   old-services/*  ->  services/*     /old-services/orthopedics -> /services/orthopedics
 *   old-blog/*      ->  blog           /old-blog/anything        -> /blog
 *
 * A To value starting with http:// or https:// stays a full URL so a
 * redirect can point at another site. Matching uses only the request path;
 * the query string is passed along unless the target has its own.
 *
 * @package General
 * @author Daniel Baldwin
 * @version 3.0.0
 */
class Redirects
{
	public const DEFAULT_FILE = '/app/data/redirects.json';

	/** @var string */
	public $file;

	/**
	 * @param string|null $file JSON file; defaults to BP/app/data/redirects.json.
	 *                          Created (as {}) when missing.
	 */
	public function __construct($file = null)
	{
		$this->file = $file ?: BP . self::DEFAULT_FILE;
		if (!is_file($this->file)) {
			if (!is_dir(dirname($this->file))) @mkdir(dirname($this->file), 0775, true);
			if (@file_put_contents($this->file, '{}') === false)
				throw new \Exception("The redirects file {$this->file} does not exist and could not be created.");
		}
	}

	/**
	 * Redirect the current request if it matches an entry, then exit.
	 * Returns false when nothing matches (or the file is missing/invalid).
	 *
	 * @param array $params ['request'=>$_SERVER['REQUEST_URI'], 'lookup'=>BP.'/app/data/redirects.json', 'type'=>'301']
	 *                      'lookup' and 'type' are optional (defaults: the app/data file, 301).
	 * @return false
	 */
	public static function redirect($params)
	{
		$request = (string) ($params['request'] ?? ($_SERVER['REQUEST_URI'] ?? ''));
		$lookup = $params['lookup'] ?? (BP . self::DEFAULT_FILE);
		$type = (string) ($params['type'] ?? '301');
		if (!is_file($lookup)) return false;

		$to = (new self($lookup))->match($request);
		$path = '/' . trim((string) parse_url($request, PHP_URL_PATH), '/');
		if ($to === null || $to === $path) return false;

		$query = (string) parse_url($request, PHP_URL_QUERY);
		if ($query !== '' && !str_contains($to, '?')) $to .= '?' . $query;

		$codes = [
			'301' => '301 Moved Permanently',   // permanent, passes link equity
			'302' => '302 Found',
			'303' => '303 See Other',           // forces a GET to the new URL
			'307' => '307 Temporary Redirect',  // same method, temporary
			'308' => '308 Permanent Redirect',  // same method, permanent
		];
		$code = isset($codes[$type]) ? (int) $type : 301;
		header('Location: ' . $to, true, $code);
		exit;
	}

	/**
	 * Where a request path should go (a /path or a full URL), or null when
	 * nothing matches. Exact matches win over wildcards; among wildcards the
	 * longest prefix wins.
	 */
	public function match(string $path): ?string
	{
		$path = trim((string) parse_url($path, PHP_URL_PATH), '/');
		$all = [];
		foreach ($this->getRedirectsList() as $from => $to) $all[trim((string) $from, '/')] = $to;
		if ($path !== '' && isset($all[$path])) return $this->finish($all[$path], '');

		$best = null; $bestLen = -1;
		foreach ($all as $from => $to) {
			if (substr($from, -1) !== '*') continue;
			$prefix = rtrim(substr($from, 0, -1), '/');
			if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
				if (strlen($prefix) > $bestLen) { $best = [$to, ltrim(substr($path, strlen($prefix)), '/')]; $bestLen = strlen($prefix); }
			}
		}
		return $best ? $this->finish($best[0], $best[1]) : null;
	}

	/** True when a path redirects away (e.g. to keep it out of the sitemap). */
	public function isRedirected(string $path): bool
	{
		return $this->match($path) !== null;
	}

	/** Add or replace a redirect. */
	public function add($from, $to): void
	{
		$from = $this->cleanFrom((string) $from);
		$to = $this->cleanTo((string) $to);
		if ($from === '') throw new \Exception('From URL is empty.');
		if ($to === '') throw new \Exception('To URL is empty.');
		if ($from === ltrim($to, '/')) throw new \Exception('From and To are the same page, which would loop forever.');

		$all = $this->withoutKey($this->getRedirectsList(), $from);
		$all[$from] = $to;
		$this->save($all);
	}

	/** Remove a redirect (any stored slash variant of the path). */
	public function remove($from): void
	{
		$from = $this->cleanFrom((string) $from);
		if ($from === '') throw new \Exception('From URL is empty.');
		$this->save($this->withoutKey($this->getRedirectsList(), $from));
	}

	/**
	 * Make sure a page that exists at $path is reachable: remove any redirect
	 * whose From is exactly this path (a leftover from an earlier rename, for
	 * example). Call on every page save. Wildcards are left alone. Returns
	 * the removed targets, as ['/from' => '/to'].
	 */
	public function clearFrom(string $path): array
	{
		$from = $this->cleanFrom($path);
		if ($from === '') return [];
		$all = $this->getRedirectsList();
		$removed = [];
		foreach ($all as $k => $to) {
			if (trim((string) $k, '/') !== $from) continue;
			$removed['/' . $from] = preg_match('#^https?://#i', (string) $to) ? $to : '/' . trim((string) $to, '/');
			unset($all[$k]);
		}
		if ($removed) $this->save($all);
		return $removed;
	}

	/**
	 * Record that a page moved from $oldPath to $newPath (its slug changed).
	 * Keeps the list free of loops and chains:
	 *  - a redirect FROM the new address is removed, so renaming back to an
	 *    old slug does not send the page to itself;
	 *  - redirects that pointed at the old address now point at the new one;
	 *  - old -> new is added, plus old/* -> new/* when it has sub-pages.
	 *
	 * @return array the redirects added, as ['/from' => '/to']
	 */
	public function pageMoved(string $oldPath, string $newPath, bool $hasChildren = false): array
	{
		$old = $this->cleanFrom($oldPath);
		$new = $this->cleanFrom($newPath);
		if ($old === '' || $new === '' || $old === $new || $old === 'index') return [];

		$all = $this->withoutKey($this->withoutKey($this->getRedirectsList(), $new), $new . '/*');

		foreach ($all as $from => $to) {
			if (preg_match('#^https?://#i', (string) $to)) continue;
			$t = trim((string) $to, '/');
			if ($t === $old) $all[$from] = $new;
			elseif (str_starts_with($t, $old . '/')) $all[$from] = $new . substr($t, strlen($old));
		}

		$added = [];
		$all = $this->withoutKey($all, $old);
		$all[$old] = $new;
		$added['/' . $old] = '/' . $new;
		if ($hasChildren) {
			$all[$old . '/*'] = $new . '/*';
			$added['/' . $old . '/*'] = '/' . $new . '/*';
		}

		$this->save($all);
		return $added;
	}

	/**
	 * All redirects, sorted by From.
	 *
	 * @return array ['from-path' => 'to', ...]
	 */
	public function getRedirectsList(): array
	{
		$data = json_decode((string) @file_get_contents($this->file), true);
		if (!is_array($data)) return [];
		ksort($data, SORT_NATURAL | SORT_FLAG_CASE);
		return $data;
	}

	/** Path part of a URL without its leading slash (kept for older callers). */
	public function cleanUrl($url)
	{
		return ltrim((string) parse_url(trim((string) $url), PHP_URL_PATH), '/');
	}

	private function finish(string $to, string $rest): string
	{
		if (str_contains($to, '*')) $to = str_replace('*', $rest, $to);
		if (preg_match('#^https?://#i', $to)) return $to;
		return '/' . ltrim(preg_replace('#/+#', '/', $to), '/');
	}

	/** From is always a path on this site, with any host and query dropped. */
	private function cleanFrom(string $url): string
	{
		$path = (string) parse_url(trim($url), PHP_URL_PATH);
		return trim(preg_replace('#/+#', '/', $path), '/');
	}

	/** To keeps a full URL to another site; anything else becomes a path. */
	private function cleanTo(string $url): string
	{
		$url = trim($url);
		if (preg_match('#^https?://#i', $url)) {
			$host = strtolower((string) parse_url($url, PHP_URL_HOST));
			$self = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
			if ($host !== '' && $host !== $self) return $url;
		}
		$path = (string) parse_url($url, PHP_URL_PATH);
		$query = (string) parse_url($url, PHP_URL_QUERY);
		$frag = (string) parse_url($url, PHP_URL_FRAGMENT);
		$path = trim(preg_replace('#/+#', '/', $path), '/');
		return $path . ($query !== '' ? '?' . $query : '') . ($frag !== '' ? '#' . $frag : '');
	}

	/** Drop every stored key that is $from with or without slashes. */
	private function withoutKey(array $all, string $from): array
	{
		foreach (array_keys($all) as $k) if (trim((string) $k, '/') === $from) unset($all[$k]);
		return $all;
	}

	private function save(array $all): void
	{
		ksort($all, SORT_NATURAL | SORT_FLAG_CASE);
		if (file_put_contents($this->file, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false)
			throw new \Exception('Could not write ' . $this->file);
	}
}
