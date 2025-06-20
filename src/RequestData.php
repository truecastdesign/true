<?php
namespace True;

/**
 * @version 1.2
 */
#[\AllowDynamicProperties]
class RequestData {
	public function __get($name) {
		return property_exists($this, $name) ? $this->$name : null;
	}

	public function __set($name, $value) {
		$this->$name = $value;
	}

	public function int(string $key): ?int {
		return isset($this->$key) ? intval(preg_replace("/[^0-9]/", '', $this->$key)) : null;
	}

	public function alpha(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^a-zA-Z]/", '', $this->$key) : null;
	}

	public function alphaInt(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^a-zA-Z0-9]/", '', $this->$key) : null;
	}

	public function name(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^a-zA-Z0-9 \.-\&\/\(\)\,']", '', $this->$key) : null;
	}

	public function decimal(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^0-9\.\-]/", '', $this->$key) : null;
	}

	public function filePath(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^a-zA-Z0-9\-]/", '', $this->$key) : null;
	}

	public function dbField(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^a-zA-Z0-9\-_ ]/", '', $this->$key) : null;
	}

	public function creditCard(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^0-9]/", '', $this->$key) : null;
	}

	public function postalCode(string $key): ?string {
		return isset($this->$key) ? preg_replace("/[^a-zA-Z0-9\- ]/", '', $this->$key) : null;
	}

	public function email(string $key): ?string {
		return isset($this->$key) ? filter_var($this->$key, FILTER_SANITIZE_EMAIL) : null;
	}

	public function url(string $key): ?string {
		return isset($this->$key) ? filter_var($this->$key, FILTER_SANITIZE_URL) : null;
	}

	public function ip(string $key): ?string {
		return isset($this->$key) ? filter_var($this->$key, FILTER_VALIDATE_IP) : null;
	}

	public function float(string $key): ?float {
		return isset($this->$key) ? floatval(filter_var($this->$key, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)) : null;
	}

	public function dateTime(string $key): ?string {
		return isset($this->$key) ? preg_replace('/[^0-9\-\/\:\s]/', '', $this->$key) : null;
	}

	public function formatCurrency(string $key): string {
		return isset($this->$key) ? '$' . number_format($this->$key, 2, '.', ',') : '';
	}

	public function formatDateTime(string $key, $format = 'Y-m-d H:i:s'): ?string {
		if (empty($this->$key)) return null;
		$timestamp = strtotime($this->$key);
		return $timestamp === false ? null : date($format, $timestamp);
	}

	public function titleCase(string $key): ?string {
		if (!isset($this->$key)) return null;
		$string = strtolower($this->$key);
		$word_splitters = [' ', '-', "O'", "L'", "D'", 'St.', 'Mc', 'Mac'];
		$lowercase_exceptions = ['the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'"];
		$uppercase_exceptions = ['III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'P.O.'];

		foreach ($word_splitters as $delimiter) {
			$words = explode($delimiter, $string);
			$newwords = [];
			foreach ($words as $word) {
				if (in_array(strtoupper($word), $uppercase_exceptions)) $word = strtoupper($word);
				else if (!in_array($word, $lowercase_exceptions)) $word = ucfirst($word);
				$newwords[] = $word;
			}
			$string = join($delimiter, $newwords);
		}
		return $string;
	}

	public function forMetaTags(string $key): ?string {
		if (!isset($this->$key)) return null;
		$find = ['"', "'", " & "];
		$replace = ['', '', ' &amp; '];
		$str = trim(strip_tags(str_replace($find, $replace, $this->$key)));
		return preg_replace(['/\s{2,}/', '/[\t\n\r]/'], ' ', $str);
	}

	public function encodeQuotes(string $key): ?string {
		return isset($this->$key) ? str_replace(["'", '"'], ['&#x27;', '&#34;'], $this->$key) : null;
	}

	public function forHtmlEditors(string $key): ?string {
		return isset($this->$key) ? str_replace('&', '&amp;', $this->$key) : null;
	}

	public function htmlOutput(string $key): ?string {
		return isset($this->$key) ? str_replace(" & ", ' &amp; ', $this->$key) : null;
	}

	public function sanitize(string $key, int $type = 1): ?string {
		if (!isset($this->$key)) return null;
		if ($type == 1) return strip_tags($this->$key);
		if ($type == 2 || $type == 3) return htmlentities(strip_tags($this->$key), ENT_QUOTES, 'UTF-8');
		return $this->$key;
	}

	public function escape(string $key): ?string {
		return isset($this->$key) ? htmlspecialchars($this->$key) : null;
	}
}