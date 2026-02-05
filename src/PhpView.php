<?php
namespace True;

/**
 * PHP template system
 *
 * @package True 6 framework
 * @author Daniel Baldwin
 * @version 5.10.0
 */

class PhpView
{
	# used keys: js, css, head, body, footer_controls, admin, cache
	private $vars = [];
	static $version = "5.9.10";

	private $metaData = ['_metaTitle'=>'', '_metaDescription'=>'', '_metaLinkText'=>'', '_js'=>'', '_css'=>''];

	public $definedMetaVars = ['base_path', 'assets_path', 'base_assets_path', 'layout', '404', '401', '403', 'error_page', 'cache', 'variables', 'styles', 'css', 'js', 'headHtml', 'headOutput', 'title', 'description', 'keywords', 'canonical', 'modified', 'timezone', 'footer_controls', 'head', 'body', 'admin', 'status'];

	# Head systems
	private $headMeta = [];		// deduped meta tags
	private $headLinks = [];	// deduped link tags
	private $headStyles = [];	// {style} blocks
	private $headScripts = [];	// {script} blocks
	private $headRaw = '';		// {head} blocks (raw injection)
	private $jsonld = [];		// {jsonld} blocks

	public function __construct($args = null)
	{
		global $App;

		if (isset($App->config->site->public_dir)) $publicDir = $App->config->site->public_dir;
		else $publicDir = 'public_html';

		# from root; end with /; ex: BP.'/app/views/'
		$this->vars['base_path'] = (isset($args['base_path'])? $args['base_path']:BP.'/app/views/');

		# from root; end with /; ex: '/assets/'
		$this->vars['assets_path'] = (isset($args['assets_path'])? $args['assets_path']:'/assets/');

		# from root; end with /; ex: BP.'/public_html/assets/'
		$this->vars['base_assets_path'] = (isset($args['assets_path'])? $args['assets_path']:BP.'/'.$publicDir.'/assets/');

		# from root; end with /; ex: BP.'/app/views/_layouts/base.phtml'
		$this->vars['layout'] = (isset($args['layout'])? $args['layout']:BP.'/app/views/_layouts/base.phtml');

		# put in base_path dir; ex: 404-error.phtml
		$this->vars['404'] = (isset($args['404'])? $args['404']:'404-error.phtml');

		# put in base_path dir; ex: 401-error.phtml
		$this->vars['401'] = (isset($args['401'])? $args['401']:'401-error.phtml');

		# put in base_path dir; ex: 403-error.phtml
		$this->vars['403'] = (isset($args['403'])? $args['403']:'403-error.phtml');

		# put in base_path dir; ex: error.phtml
		$this->vars['error_page'] = (isset($args['error_page'])? $args['error_page']:'error.phtml');

		# turn on or off page caching
		$this->vars['cache'] = (isset($args['cache'])? $args['cache']:true);

		# global variables for layout template
		$this->vars['variables'] = (isset($args['variables'])? $args['variables']:[]);

		# styles
		$this->vars['styles'] = '';

		# defaults
		$this->vars['headHtml'] = '';
		$this->vars['headOutput'] = '';
	}

	/**
	 * Used to add global css and js files
	 *
	 * @return void
	 **/
	public function __set($key, $value)
	{
		switch ($key) {
			case 'variables':
				$this->vars['variables'] = array_merge($this->vars['variables'], $value);
			break;
			case 'css':
			case 'js':
				if (!empty($this->vars[$key])) $this->vars[$key] .= ', '.$value;
				else $this->vars[$key] = $value;
			break;
			default:
				$this->vars[$key] = $value;
		}
	}

	/**
	 * force adding to var array
	 *
	 * @param string $key
	 * @param string|array $value
	 * @return void
	 */
	public function addVar($key, $value)
	{
		switch ($key) {
			case 'variables':
				$this->vars['variables'] = array_merge($this->vars['variables'], $value);
			break;
			case 'css':
			case 'js':
				if (!empty($this->vars[$key])) $this->vars[$key] .= ', '.$value;
				else $this->vars[$key] = $value;
			break;
			default:
				$this->vars[$key] = $value;
		}
	}

	/**
	 * Use to access values in the $vars array
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->vars)) return $this->vars[$key];
		return '';
	}

	public function __unset($key) { $this->vars[$key] = ''; }

	public function isset(string $key) { return isset($this->vars[$key]); }

	public function __isset($key) { return isset($this->vars[$key]); }

	public function __call(string $name, array $args)
	{
		switch ($name) {
			case 'created':
				if (!isset($this->vars['created'])) $this->vars['created'] = date("Y-m-d");
				$DateTime = new \DateTime($this->vars['created']);
				return $DateTime->format($args[0]);
			break;
			case 'modified':
				$DateTime = new \DateTime($this->vars['modifiedRaw']);
				return $DateTime->format($args[0]);
			break;
		}
	}

	/* =======================
		HEAD HELPERS
	======================= */

	private function resetHeadBuckets()
	{
		$this->headMeta = [];
		$this->headLinks = [];
		$this->headStyles = [];
		$this->headScripts = [];
		$this->headRaw = '';
		$this->jsonld = [];
	}

	private function e($s)
	{
		// decode first so INI values like &amp; don't become &amp;amp;
		return htmlspecialchars(html_entity_decode((string)$s, ENT_QUOTES, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	private function inferMetaAttr($key)
	{
		$k = strtolower($key);
		if (strpos($k, 'og:') === 0) return 'property';
		if (strpos($k, 'fb:') === 0) return 'property';
		if (strpos($k, 'article:') === 0) return 'property';
		return 'name'; // twitter:* and everything else
	}

	private function addHeadMeta($attr, $key, $content)
	{
		$attr = strtolower(trim($attr));
		$k = $attr.'|'.$key;
		$this->headMeta[$k] = ['attr'=>$attr, 'key'=>$key, 'content'=>$content];
	}

	private function addHeadMetaFromIniKey($iniKey, $value)
	{
		$iniKey = trim($iniKey);

		// Explicit prefix: property:, name:, http-equiv:, link:
		if (strpos($iniKey, ':') !== false) {
			list($prefix, $rest) = explode(':', $iniKey, 2);
			$prefix = strtolower($prefix);
			$rest = trim($rest);

			if ($prefix === 'property' || $prefix === 'name' || $prefix === 'http-equiv') {
				$this->addHeadMeta($prefix, $rest, $value);
				return;
			}

			if ($prefix === 'link') {
				$this->addHeadLink($rest, $value);
				return;
			}
		}

		// Fallback: normal <meta name="...">
		$this->addHeadMeta('name', $iniKey, $value);
	}

	private function addHeadLink($rel, $href)
	{
		$rel = strtolower(trim($rel));
		$href = trim($href);
		$k = $rel.'|'.$href;
		$this->headLinks[$k] = ['rel'=>$rel, 'href'=>$href];
	}

	private function addHeadStyle($css)
	{
		$css = trim((string)$css);
		if ($css) $this->headStyles[] = $css;
	}

	private function addHeadScript($js)
	{
		$js = trim((string)$js);
		if ($js) $this->headScripts[] = $js;
	}

	private function addHeadRaw($html)
	{
		$html = trim((string)$html);
		if (!$html) return;
		$this->headRaw .= ($this->headRaw ? "\n" : '').$html;
	}

	private function addJsonLdBlock($json)
	{
		$json = trim((string)$json);
		if ($json) $this->jsonld[] = $json;
	}

	public function hasHeadMeta($attr, $key) { return isset($this->headMeta[strtolower($attr).'|'.$key]); }

	public function getHeadMeta($attr, $key)
	{
		$k = strtolower($attr).'|'.$key;
		return isset($this->headMeta[$k]) ? $this->headMeta[$k]['content'] : null;
	}

	private function applyDefaults()
	{
		// og:type default
		if (!$this->hasHeadMeta('property', 'og:type'))
			$this->addHeadMeta('property', 'og:type', 'website');

		// og:description from description
		if ($this->isset('description') && !$this->hasHeadMeta('property', 'og:description'))
			$this->addHeadMeta('property', 'og:description', $this->vars['description']);

		// Resolve URL for og:url
		if (!$this->hasHeadMeta('property', 'og:url')) {

			if ($this->isset('canonical')) {
				$url = $this->vars['canonical'];
			} else {
				$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
				$scheme = $https ? 'https' : 'http';
				$host = $_SERVER['HTTP_HOST'] ?? '';
				$uri = $_SERVER['REQUEST_URI'] ?? '/';
				$url = $scheme.'://'.$host.$uri;
			}

			$this->addHeadMeta('property', 'og:url', $url);
		}
	}

	private function resolvePlaceholders($value)
	{
		global $App;

		if (!is_string($value) || strpos($value, '{') === false) return $value;

		$url = isset($App->request->url->full) ? $App->request->url->full : '';
		$canonical = $this->isset('canonical') ? $this->vars['canonical'] : $url;
		$image = $this->getHeadMeta('property', 'og:image');
		if (!$image && $this->isset('og:image')) $image = $this->vars['og:image'];

		$map = [
			'{title}' => $this->vars['title'] ?? '',
			'{description}' => $this->vars['description'] ?? '',
			'{url}' => $url,
			'{canonical}' => $canonical,
			'{image}' => $image ?? ''
		];

		return strtr($value, $map);
	}

	private function buildHeadOutput()
	{
		global $App;

		$lines = [];

		// title
		if ($this->isset('title')) {
			$title = $this->resolvePlaceholders($this->vars['title']);
			$lines[] = "<title>".$this->e($title)."</title>";
		}

		// description
		if ($this->isset('description')) {
			$desc = $this->resolvePlaceholders($this->vars['description']);
			$lines[] = "<meta name=\"description\" content=\"".$this->e($desc)."\">";
		}

		// keywords
		if ($this->isset('keywords')) {
			$kw = $this->resolvePlaceholders($this->vars['keywords']);
			$lines[] = "<meta name=\"keywords\" content=\"".$this->e($kw)."\">";
		}

		// canonical: explicit > accessed url
		if ($this->isset('canonical')) {
			$this->addHeadLink('canonical', $this->vars['canonical']);
		} elseif (isset($App->request->url->full)) {
			$this->addHeadLink('canonical', $App->request->url->full);
		}

		// links
		foreach ($this->headLinks as $l) {
			$href = $this->resolvePlaceholders($l['href']);
			$lines[] = "<link rel=\"".$this->e($l['rel'])."\" href=\"".$this->e($href)."\">";
		}

		// meta tags
		foreach ($this->headMeta as $m) {
			$content = $this->resolvePlaceholders($m['content']);
			$lines[] = "<meta ".$this->e($m['attr'])."=\"".$this->e($m['key'])."\" content=\"".$this->e($content)."\">";
		}

		// inline styles
		if (!empty($this->headStyles)) {
			$lines[] =
				"<style>\n\t".
				implode("\n\n\t", $this->headStyles).
				"\n\t</style>";
		}

		// JSON-LD
		foreach ($this->jsonld as $json) {
			$json = $this->resolvePlaceholders($json);
			$lines[] =
				"<script type=\"application/ld+json\">\n\t".
				$json.
				"\n\t</script>";
		}

		// inline scripts
		if (!empty($this->headScripts)) {
			$lines[] =
				"<script>\n\t".
				implode("\n\n\t", $this->headScripts).
				"\n\t</script>";
		}

		// raw head HTML
		if ($this->headRaw) {
			$lines[] = $this->resolvePlaceholders(rtrim($this->headRaw));
		}

		// legacy headHtml (kept last on purpose)
		if ($this->isset('headHtml')) {
			$hh = trim($this->vars['headHtml']);
			if ($hh) $lines[] = $this->resolvePlaceholders($hh);
		}

		// one tab indentation, no double-tab bugs
		return "\t".implode("\n\t", $lines)."\n";
	}

	/**
	 * Render views. Use .phtml file
	 *
	 * @param String $taView - path and filename.phtml to render
	 * @param Array $variables - variables to pass to view file
	 * @param Array $renderConfig - ['noPartials'=>true]
	 * @return void
	 **/
	public function render(string $taView, array $variables = [], $renderConfig = [])
	{
		$outputFiles = [];
		$searchFiles = [];
		$replaceTags = [];
		$searchTags = [];
		$httpCodesHeaders = ['301'=>'Moved Permanently', '302'=>'Found', '303'=>'See Other', '304'=>'Not Modified', '307'=>'Temporary Redirect', '308'=>'Permanent Redirect', '400'=>'Bad Request', '401'=>'Unauthorized', '403'=>'Forbidden', '404'=>'Not Found', '405'=>'Method Not Allowed'];

		$this->resetHeadBuckets();

		# check for error page
		if (is_int($taView)) {
			header("HTTP/2 ".$taView." ".$httpCodesHeaders[$taView]);
			$this->metaData['_metaTitle'] = $httpCodesHeaders[$taView];
			if (key_exists($taView, $this->vars)) $taView = $this->vars['base_path'].$this->vars[$taView];
			else {
				$variables['errorCode'] = $taView;
				$variables['errorText'] = $httpCodesHeaders[$taView];
				$taView = $this->vars['base_path'].$this->vars['error_page'];
			}
		}

		if (!is_array($variables))
			throw new \Exception("variables passed needs to be inside an array. ['varname'=>'value].");

		if (empty($taView) or $taView == '.phtml') $taView = 'index.phtml';

		$fullPath = ($taView[0] == '/')? true:false;

		header('X-Frame-Options: SAMEORIGIN');
		if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') header('Strict-Transport-Security: max-age=31536000');

		header('X-Content-Type-Options: nosniff');
		header('Referrer-Policy: same-origin');
		header('X-Frame-Options: sameorigin');
		header("Feature-Policy: vibrate 'self'; microphone 'self'; camera 'self'; notifications 'self'; gyroscope 'self'");
		header_remove("X-Powered-By");

		if (isset($this->vars['base_path']) and !$fullPath) $taView = $this->vars['base_path'].$taView;

		if (file_exists($taView) === false) {
			header("HTTP/2 404 Not Found");
			$this->metaData['_metaTitle'] = "File Not Found";
			$taView = $this->vars['base_path'].$this->vars['404'];
		}

		$this->vars['headHtml'] = '';

		ob_start();
			global $App;
			extract($this->vars['variables']);
			extract($variables);
			extract($this->metaData);
			include $taView;
		$fileContents = ob_get_clean();

		# find the break point for the meta data
		$fileParts = explode("{endmeta}", $fileContents, 2);

		# if no {endmeta}, just use the file contents
		if (count($fileParts) == 1) {
			$fileParts[1] = $fileContents;
			unset($fileParts[0]);
		}

		$metaDataArray = null;

		if (isset($fileParts[0]) && isset($fileParts[1])) {

			$header = $fileParts[0];

			// {head}...{/head} (multiple)
			if (preg_match_all('/\{head\}(.*?)\{\/head\}/s', $header, $mm)) {
				foreach ($mm[1] as $block) $this->addHeadRaw($block);
				$header = preg_replace('/\{head\}(.*?)\{\/head\}/s', '', $header);
			}

			// {jsonld}...{/jsonld} (multiple)
			if (preg_match_all('/\{jsonld\}(.*?)\{\/jsonld\}/s', $header, $mm)) {
				foreach ($mm[1] as $block) $this->addJsonLdBlock($block);
				$header = preg_replace('/\{jsonld\}(.*?)\{\/jsonld\}/s', '', $header);
			}

			// {style}...{/style} (multiple)
			if (preg_match_all('/\{style\}(.*?)\{\/style\}/s', $header, $mm)) {
				foreach ($mm[1] as $block) $this->addHeadStyle($block);
				$header = preg_replace('/\{style\}(.*?)\{\/style\}/s', '', $header);
			}

			// {script}...{/script} (multiple)
			if (preg_match_all('/\{script\}(.*?)\{\/script\}/s', $header, $mm)) {
				foreach ($mm[1] as $block) $this->addHeadScript($block);
				$header = preg_replace('/\{script\}(.*?)\{\/script\}/s', '', $header);
			}

			$metaDataArray = parse_ini_string($header);

			if (is_array($metaDataArray))
			foreach ($metaDataArray as $metaKey=>$metaValue) {

				if ($metaKey == 'cache') $metaValue = ($metaValue == 1) ? true:false;

				// Standard vars (stored as vars)
				if ($metaKey == 'title' || $metaKey == 'description' || $metaKey == 'keywords' || $metaKey == 'canonical' || $metaKey == 'modified' || $metaKey == 'timezone' || $metaKey == 'headHtml' || $metaKey == 'indexing' || $metaKey == 'cache' || $metaKey == 'css' || $metaKey == 'js' || $metaKey == 'status' || $metaKey == 'footer_controls') {
					$this->addVar($metaKey, $metaValue);
					continue;
				}

				// Everything else becomes a generated <meta ...> tag
				$this->addHeadMetaFromIniKey($metaKey, $metaValue);
			}
		}

		if (isset($metaDataArray) && is_array($metaDataArray) and array_key_exists('indexing', $metaDataArray) and $metaDataArray['indexing'] == '')
			$this->vars['headHtml'] .= "\n".'<meta name="robots" content="noindex">'."\n";

		// List of time zones: https://www.w3schools.com/PHP/php_ref_timezones.asp
		if (isset($this->vars['modified'])) {

			if (isset($this->vars['timezone'])) {
				$modifiedDate = new \DateTime($this->vars['modified'], new \DateTimeZone($this->vars['timezone']));
				$modifiedDate->setTimezone(new \DateTimeZone('Europe/London'));
			} else $modifiedDate = new \DateTime($this->vars['modified']);

			$this->vars['modifiedRaw'] = $this->vars['modified'];
			header("Last-Modified: " . $modifiedDate->format("D, d M Y H:i:s")." GMT");
		} else {
			if (isset($this->vars['timezone'])) {
				$modifiedDate = new \DateTime(date("Y-m-d H:i:s",filemtime($taView)), new \DateTimeZone($this->vars['timezone']));
				$modifiedDate->setTimezone(new \DateTimeZone('Europe/London'));
			} else $modifiedDate = new \DateTime(date("Y-m-d H:i:s",filemtime($taView)));

			$this->vars['modifiedRaw'] = date("Y-m-d H:i:s",filemtime($taView));
			$this->vars['modified'] = $modifiedDate->format("D, d M Y H:i:s")." GMT";

			header("Last-Modified: " . $modifiedDate->format("D, d M Y H:i:s")." GMT");
		}

		$this->processMetaData(); # just process global meta data

		# if status in view is set but not to published it will return 404 page.
		if (isset($this->vars['status']) and $this->vars['status'] != 'published') {
			$this->vars['status'] = null;
			$this->render($this->vars['base_path'].$this->vars['404'], $variables);
		}

		# insert template into page if needed
		if (is_array($renderConfig) and !isset($renderConfig['noPartials'])) {
			preg_match_all("/\{partial:(.*)}/", $fileContents, $outputArray);

			if (is_array($outputArray[1])) {
				foreach ($outputArray[1] as $partial)
				{
					ob_start();
						extract($this->vars['variables']);
						extract($variables);
						extract($this->metaData);
						if (isset($partial[0]) && $partial[0] === '/') {
							if (!include(BP.$partial))
								throw new \Exception("Included partial not found: ".BP.$partial);
						} else {
							if (!include(BP.'/app/views/_partials/'.$partial))
								throw new \Exception("Included partial not found: ".BP.'/app/views/_partials/'.$partial);
						}
					$replaceTags[] = ob_get_clean();
					$searchTags[] = "{partial:".$partial."}";
				}
			}

			if (is_array($outputArray[0])) {
				foreach ($outputArray[0] as $tag) {
					$replaceTags[] = '';
					$searchTags[] = $tag;
				}
			}
		}

		if (isset($outputArray) and is_array($outputArray[0])) {
			foreach ($outputArray[0] as $tag) {
				$replaceTags[] = '';
				$searchTags[] = $tag;
			}
		}

		# find and replace special tags
		if (isset($fileParts[1]))
			$fileParts[1] = str_replace($searchTags, $replaceTags, $fileParts[1]);

		if (!$this->vars['cache']) {
			header('Expires: '.gmdate("D, d M Y H:i:s", strtotime("-4 hours")).' GMT');
			header_remove("Pragma");
			header("Pragma: no-cache");
			header_remove("Cache-Control");
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		} else {
			header_remove("Pragma");
			header('Cache-Control: max-age=3600, public'); # 1 hour
			header('Expires: '.gmdate("D, d M Y H:i:s", strtotime("+1 hour")).' GMT');
		}

		if (isset($fileParts[1])) $this->metaData['_html'] = $fileParts[1];
		elseif (isset($fileParts[0])) $this->metaData['_html'] = $fileParts[0];
		else $this->metaData['_html'] = '';

		$this->vars['html'] = $this->metaData['_html'];

		$stylesHTML = $this->moveStyleTags($this->vars['html']);

		$this->vars['html'] = $stylesHTML->html;

		extract($this->metaData);
		extract($this->vars['variables']);
		extract($variables);
		global $App;

		if (!empty($stylesHTML->styles))
			$App->view->headHtml = $App->view->headHtml ."\n".$this->vars['styles']."\n<style>".$stylesHTML->styles."</style>\n\n";

		// generate an ETag
		$etag1 = md5_file($this->vars['layout']);
		$etag2 = $this->vars['html']? md5($this->vars['html']):'';
		$etag = $etag1.$etag2? md5($etag1.$etag2):'';

		if (!empty($etag))
			header("ETag: \"$etag\"");

		// Step 3: Check if the ETag matches the client's request
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === "\"$etag\"") {
			header("HTTP/1.1 304 Not Modified");
			exit;
		}

		// Apply automatic defaults (og:*, urls, etc.)
		$this->applyDefaults();

		// Build head output now that everything is known (meta, moved styles, etc.)
		$this->vars['headOutput'] = $this->buildHeadOutput();

		if (isset($this->vars['layout'])) {
			require_once $this->vars['layout'];
			die();
		}
		else {
			echo $this->metaData['_html'];
			die();
		}
	}

	public function error(int $type)
	{
		switch ($type) {
			case 404:
				header("HTTP/2 404 Not Found");
				$this->render($this->vars['404']);
			break;

			case 401:
				header("HTTP/2 401 Unauthorized");
				$this->render($this->vars['401']);
			break;

			case 403:
				header("HTTP/2 401 Forbidden");
				$this->render($this->vars['403']);
			break;
		}
	}

	private function processMetaData($metaData = null)
	{
		if ($metaData == null) $metaData = [];

		$css = [];
		$js = [];

		if (isset($this->vars['css'])) $css = explode(',',trim($this->vars['css']));
		if (isset($this->vars['js'])) $js = explode(',',trim($this->vars['js']));

		$https = array_key_exists('HTTPS', $_SERVER) ? ($_SERVER['HTTPS'] == 'on' ? true:false):false;
		$protocol = $_SERVER['REQUEST_SCHEME'] ?? $https ? 'https':'http';
		$urlStart = $protocol.'://'.$_SERVER['HTTP_HOST'];

		$this->vars['breadcrumbs'] = [];

		if (isset($metaData['breadcrumb'])) {
			if (is_array($metaData['breadcrumb'])) {
				foreach ($metaData['breadcrumb'] as $crumb) {
					$parts = explode('|', $crumb);
					$this->vars['breadcrumbs'][] = ['name'=>$parts[0], 'url'=>$urlStart.$parts[1]];
				}
			}
		}

		if (isset($metaData['css'])) $css = array_merge($css, explode(',',trim($metaData['css'])));
		if (isset($metaData['js'])) $js = array_merge($js, explode(',',trim($metaData['js'])));

		$css = $this->processAssetsPaths($css);
		$js = $this->processAssetsPaths($js);

		if (is_array($js)) {
			$this->metaData['_js'] = $this->buildJSFile($js);
			$this->vars['jsoutput'] = $this->metaData['_js'];
		}

		if (is_array($css)) {
			$this->metaData['_css'] = $this->buildCSSFile($css);
			$this->vars['cssoutput'] = $this->metaData['_css'];
		}
	}

	private function processAssetsPaths($list)
	{
		$assetList = [];

		foreach($list as $value)
		{
			if (isset($value)) $value = trim($value);

			if(strtok($value, '/') == 'vendor' OR strtok($value, '/') == 'app') $assetList[] = BP.rtrim($value, '/');
			elseif( strpos($value, '://') === false and !empty($value) and strpos($value, '*') === false) $assetList[] = $_SERVER['DOCUMENT_ROOT'].$value;
			elseif(!empty($value)) $assetList[] = $value;
		}

		return $assetList;
	}

	private function buildCSSFile(array $cssList)
	{
		$firstPartFilename = $this->generateFileHash($cssList);

		$cssCachePath = $this->vars['assets_path'].'css/cache/';
		$cssCacheRootPath = $this->vars['base_assets_path'].'css/cache/';

		if (!empty($firstPartFilename))
		{
			$cacheFilename = $firstPartFilename.'.css';

			if (file_exists($cssCachePath.$cacheFilename))
				return '<link rel="stylesheet" href="'.$cssCachePath.$cacheFilename.'">'."\n";

			else {
				if (in_array('.scss', $cssList) !== false) $TAscss = new \True\SCSS;

				$cachedStr = '';

				foreach ($cssList as $file)
				{
					if (substr($file, strrpos($file, '.') + 1) == 'css') {
						if (file_exists($file)) $cachedStr .= file_get_contents($file);
					}
					elseif (substr($file, strrpos($file, '.') + 1) == 'scss')
					{
						if (file_exists($file)) $cachedStr .= $TAscss->compile( file_get_contents($file) );
					}
				}

				$cachedStrMin = \True\CSSMini::process($cachedStr);

				file_put_contents($cssCacheRootPath.$cacheFilename, $cachedStrMin);

				return '<link rel="stylesheet" href="'.$cssCachePath.$cacheFilename.'">'."\n";
			}
		}
	}

	private function buildJSFile(array $jsFiles)
	{
		$cacheFilename = $this->generateFileHash($jsFiles);

		$jsCachePath = $this->vars['assets_path'].'js/cache/'.$cacheFilename.'.js';
		$jsCacheRootPath = $this->vars['base_assets_path'].'js/cache/'.$cacheFilename.'.js';

		$jsScripts = '';
		$cachedJSStr = '';

		if ($cacheFilename === false OR file_exists($jsCacheRootPath)) {
			if (is_array($jsFiles))
			foreach ($jsFiles as $file) {
				if (strpos($file, '://') !== false OR strpos($file, '*') !== false) {
					$file = str_replace('*', '', $file);
					$jsScripts .= '<script src="'.$file.'"></script>'."\n";
				}
			}

			if ($cacheFilename !== false) $jsScripts .= '<script src="'.$jsCachePath.'"></script>'."\n";

			return $jsScripts;
		}
		else {
			foreach ($jsFiles as $file) {

				if (substr($file, strrpos($file, '.') + 1) == 'js')
				{
					if (strpos($file, '://') !== false OR strpos($file, '*') !== false) $cdnFiles[] = $file;
					else if(file_exists($file)) $cachedJSStr .= file_get_contents($file)."\n";
				}
			}

			if (isset($cdnFiles))
			foreach($cdnFiles as $file) {
				$file = str_replace('*', '', $file);
				$jsScripts .= '<script src="'.$file.'"></script>'."\n";
			}

			if (!empty($cachedJSStr)) {
				$cachedJSStrMin = \True\JSMin::process($cachedJSStr);

				if(empty($cachedJSStrMin) and !empty($cachedJSStr)) $cachedJSStrMin = $cachedJSStr;

				file_put_contents($jsCacheRootPath, $cachedJSStrMin);

				$jsScripts .= '<script src="'.$jsCachePath.'"></script>'."\n";
			}
		}
		return $jsScripts;
	}

	/**
	 * get the contents of the files, combine them, and return the hash
	 *
	 * @param array $files - ['file/path/filename.ext', 'file/path/filename.ext']
	 * @return string - file contacts hash
	 * @author Daniel Baldwin
	 **/
	private function generateFileHash($files)
	{
		$content = '';

		foreach ($files as $file) {
			if (strpos($file, '://') === false)
				$content .= file_get_contents($file);
		}
		
		if (empty($content))
			return false;
		else
			return md5($content);
	}
	
	function moveStyleTags($html) 
	{
		if (empty($html)) {
			return ['styles' => '', 'html' => $html];
		}

		// Suppress DOMDocument warnings
		libxml_use_internal_errors(true);

		// Use placeholders for script tags to prevent DOMDocument from altering them
		$scriptPlaceholder = '###SCRIPT###';
		$scripts = [];
		$htmlWithoutScripts = preg_replace_callback('/<script\b[^>]*>([\s\S]*?)<\/script>/i', function ($matches) use (&$scripts, $scriptPlaceholder) {
			$scripts[] = $matches[0];
			return $scriptPlaceholder . count($scripts) - 1;
		}, $html);

		// Create a new DOMDocument instance
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
		$dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $htmlWithoutScripts . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		// Collect all style tags in an array
		$stylesArray = [];
		$styleTags = $dom->getElementsByTagName('style');
		while ($styleTags->length > 0) {
			$styleTag = $styleTags->item(0);
			$stylesArray[] = $styleTag->nodeValue;
			$styleTag->parentNode->removeChild($styleTag);
		}

		// Save the HTML content back to a variable without the style tags
		$body = $dom->getElementsByTagName('body')->item(0);
		$htmlContentWithoutStyles = $dom->saveHTML($body);

		// Remove the enclosing <body> tags
		$cleanedHTML = str_replace(['<body>', '</body>'], '', $htmlContentWithoutStyles);

		// Restore the script tags that were temporarily removed
		foreach ($scripts as $index => $scriptTag) {
			$cleanedHTML = str_replace($scriptPlaceholder . $index, $scriptTag, $cleanedHTML);
		}
		
		return (object)[
			'styles' => implode("\n\n", $stylesArray),
			'html' => $cleanedHTML
		];
	}
	
	/**
	 * create bread crumbs for site
	 * 
	 * By default it uses the filename to build the breadcrum link text. If you want custom link text for a page, use the meta data section of the page to set it and add "true" as the boolean second value passed to the function. See below for format.
	 * 
	 * linkText = "Page Link Text"
	 *
	 * @param string $sep the separator between pages
	 * @return string html
	 * @author Daniel Baldwin
	 */
	public static function getBreadcrumbs($sep='&#x279D;')
	{
		$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

		$str = '';
		$linkURL = '';

		$pagesList = explode('/',ltrim($path, "/"));

		$c = count($pagesList);
		for ($i=0; $i < $c; $i++) {
			unset($linkText);

			$page = array_shift($pagesList);
			
			$linkURL .= "/$page";
			
			$fullFilePath = BP."/app/views$linkURL.phtml";
			
			# find the break point for the meta data
			if (file_exists($fullFilePath)) {
				$fileParts = explode("{endmeta}", file_get_contents($fullFilePath), 2);
				
				if (isset($fileParts[0]))  # does the template have meta data
					$metaDataArray = parse_ini_string($fileParts[0]);
				
				if (isset($metaDataArray['linkText']))
					$linkText = $metaDataArray['linkText'];
				elseif (isset($metaDataArray['title']))
					$linkText = $metaDataArray['title'];
			}

			if (!isset($linkText))
				$linkText = self::ucwordss(str_replace('-',' ',$page), ["is", "to", "the", "for"]);				

			if ($i+1 == $c) # last one
				$str .= ' '.$sep.' '.$linkText;
			else
				$str .= ' '.$sep.' <a href="'.$linkURL.'">'.$linkText.'</a>';
		}

		$indexFileParts = explode("{endmeta}", file_get_contents(BP."/app/views/index.phtml"), 2);
				
		if (isset($indexFileParts[0]))  # does the template have meta data
			$metaDataArray = parse_ini_string($indexFileParts[0]);
		
		if (isset($metaDataArray['linkText']))
			$linkText = $metaDataArray['linkText'];
		else
			$linkText = 'Home';

		return '<a href="/">'.$linkText.'</a>'.$str;
	}
	
	/**
	 * Build Navigation
	 *
	 * @param json string $propsStr {"ul_class":"className", "ul_id":"ulId", "select_class":"sel", "wrapper":"none|ul"}
	 * @return string
	 * @author Daniel Baldwin
	 */
	public static function nav($propsStr = null)
	{
		if ($propsStr != null)
			$props = json_decode($propsStr, true);
		
		if (!isset($props['wrapper'])) 
			$props['wrapper'] = 'ul';
		
		$html = '';
		
		$viewsPath = rtrim($GLOBALS['controller'], 'controller.php').'views';
		
		# get list of files in view dir
		$list = array_diff(scandir($viewsPath), array('..', '.', '_parts'));

		# sort files if needed
		if(isset($props['sort']))
		{
			$listSorted = explode(',', $props['sort']);
			array_walk($listSorted, function(&$value) { $value .= '.html'; });
			$list = $listSorted;
		}
		
		# check if wrapper property is set
		if($props['wrapper'] == 'ul')
		{
			$html = '<ul';
			if(isset($props['ul_class'])) $html .= ' class="'.$props['ul_class'].'"';
			if(isset($props['ul_id'])) $html .= ' id="'.$props['ul_id'].'"';
			$html .= '>';
		}
				
		foreach($list as $file)
		{
			if(strpos($file, '.html'))
			{
				# remove .html from file name to get path
				$path = str_replace('.html', '', $file);
				
				# read the file into lines
				if(file_exists($viewsPath.'/'.$file))
					$lines = file($viewsPath.'/'.$file);
										
				$j = 0;
				# loop and get meta data needed
				if(isset($lines))
				foreach($lines as $line)
				{
					if(strpos($line,'#not_in_nav:') !== false)
					{
						$meta['not_in_nav'] = true;
					}
					
					if(strpos($line,'#link_text:') !== false)
					{
						$viewParts = explode(':',$line,2);
						$meta['link_text'] = trim($viewParts[1]);
					}
					
					if($j > 9) break;
					$j++;
				}
				
				# build link is not in nav is not set
				if(!isset($meta['not_in_nav'])) 
				{
					if($props['wrapper'] == 'ul')
						$html .= '<li>';
					
					$html .= '<a href="/'.$path.'"';
					
					if(isset($props['select_class']))
					{
						if($_SERVER["REQUEST_URI"] == '/'.$path)
							$html .= ' class="'.$props['select_class'].'"';
					}
					
					if(isset($meta['link_text']))
						$html .= '>'.$meta['link_text'].'</a>';	
					else
						$html .= '>'.ucwords($path).'</a>';
					
					if($props['wrapper'] == 'ul')
						$html .= '</li>';
				}	
			} 
			unset($meta, $lines);
		}

		if ($props['wrapper'] == 'ul')
			$html .= '</ul>';
		
		return $html;
	}
	
	# $additionalString should start with an '&' like &key=value
	/**
	 * A pagination method for displaying page numbers for a list of records.
	 *
	 * @param int $rowCount, total records available
	 * @param int $perPage, how many records per page
	 * @param int $pageRange, number of pages to display at a time
	 * @param int $rangeLimit, not used anymore
	 * @param int $page, the current page to be displayed
	 * @param bool $return, true|false if to return the html or echo it
	 * @param string $additionalString, additional text on end of query string of page links.
	 * @return string HTML with links to all the page numbers needed.
	 * @author Dan Baldwin
	 */
	public function pageNumbers($rowCount, $perPage, $pageRange, $rangeLimit, $page, $return=false, $additionalString='')
	{
		if($page == null OR $page == 0) $page = 1;
		if(!$perPage) $perPage = 100;
		if($page == 1) $pageRange = $pageRange + 2;
		$totalPages = ceil($rowCount/$perPage);
		$rangeLimit = ceil($pageRange/2);
		$output = '';

		$output .= '
		<div class="Pages">
			<div class="Paginator"> <b>Pages</b>
			';

		if($page > $rangeLimit)
			$output .= "<a href='$PHP_SELF?page=1".$additionalString."' class='Prev'>&lt; First</a>
			<span class='break'>...</span>
			"; 
			
			

		for($pageCounter=1; $pageCounter <= $totalPages; $pageCounter++)
		{
			if($pageCounter > $page - $rangeLimit AND $pageCounter < $page + $rangeLimit)
			{
				if($page == $pageCounter)
					$output .= "<span class='this-page'>$pageCounter</span>
						";
				else
				{
					if($totalPages != 1)
						$output .= "<a href='$PHP_SELF?page=$pageCounter".$additionalString."'>$pageCounter</a>
						";
				}
			}
		}

		if($totalPages > $page + $pageRange)
			$output .= "<span class='break'>...</span>
			"; 

		if($page < $totalPages - $rangeLimit AND $totalPages > $rangeLimit)
			$output .= "<a href='$PHP_SELF?page=$totalPages".$additionalString."' class='Next'>Last &gt;</a>
			"; 
		//status=$status&amp; <- removed 2007-03-26

		$output .= "
			</div>
		</div>
		";
		if($rowCount>1)
		{
			if($return) return $output;
			else echo $output;
		}
	}
	
	/**
	 * sideSelect - used by the sidebar.tpl in an <a> tag so the link will be highlighted when the user is on that page.
	 * Example: <a href=""<?=$TA->sideSelect('articles')?>>Link</a>
	 *
	 * @param string $item should match the string in the $subpg variable on the page.
	 * @return string class to apply selected style.
	 * @author Dan Baldwin
	 */
	static function sideSelect($item)
	{
		return ($GLOBALS['subpg']==$item? ' class="sideCurr"':'');
	}
	
	/**
	 * sidebarItem - 
	 *
	 * @param string $title Title of link
	 * @param string $url Link
	 * @param string $subPage Subpage tag
	 * @param string $img Image for link located in the local images folder for the module
	 * @param string $permission Permission tag if it needs to have restricted access.
	 * @return void Echos out the results.
	 * @author Dan Baldwin
	 */
	public static function sidebarItem($title, $url, $img, $subPage=null, $permission=null)
	{
		if(!strstr($img,'/')) $img = 'views/images/'.$img;
		if($permission)
		{
			
			echo '<a href="'.$url.'"><img src="'.$img.'" width="16" height="16"/> '.$title.'</a>';
			
		}
		else echo '<a href="'.$url.'"><img src="'.$img.'" width="16" height="16"/> '.$title.'</a>';
	}

	/**
	 * add section action to menu
	 *
	 * @param array $args ['title'=>'', 'icon'=>'just the file name', 'onclick'=>'']
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function addSectionAction($values)
	{
		$this->vars['section_action'][] = (object) $values;
	}

	public function getSectionActions()
	{
		global $TAConfig;

		$str = '';
		
		if(is_object($this->vars['section_action'][0]))
		foreach($this->vars['section_action'] as $action)
		{
			$str .= '<li'.(isset($action->onclick)? ' onclick="'.$action->onclick.'"':'').'><a href="javascript:;">'.(isset($action->icon)? '<img src="/modules/'.$TAConfig->controlling_module.'/assets/images/'.$action->icon.'">':'').$action->title.'</a></li>';
		}
		return $str;
	}

	public function hasActions()
	{
		#print_r($this->vars['section_action']);
		if(is_object($this->vars['section_action'][0]))
			return true;
		else
			return false;
	}
	
	/**
	 * version - Check to see if the version required is available. The var $str should contain needed version. Will return true or false if sent a version else it will return current version.
	 *
	 * @param string $str 
	 * @return string|bool version number of TrueAdmin if you don't send it a version or true|false if the version sent is >= to TrueAdmin version.
	 * @author Dan Baldwin
	 */
	public function version($str=null)
	{
		if ($str) {
			if($str >= self::$version) return true;
			else return false;
		}
		else return self::$version;
	}

	/**
	 * uppercase words with ignore list
	 *
	 * @param string - words you want changed
	 * @param array - list of words you want ignored
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	private static function ucwordss($str, $exceptions)
	{
		$out = "";
		foreach (explode(" ", $str) as $word) {
			$out .= (!in_array($word, $exceptions)) ? strtoupper($word[0]) . substr($word, 1) . " " : $word . " ";
		}
		return rtrim($out);
	}
}