<?php
/**
 * Pretty fatal-error + exception handler.
 *
 * Covers:
 *   - Uncaught \Throwable (via set_exception_handler)
 *   - Parse errors, E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR,
 *     E_RECOVERABLE_ERROR (via register_shutdown_function + error_get_last)
 *
 * Require this file as early as possible — ideally from public_html/index.php
 * *before* init.php — so parse errors in init.php itself can be rendered.
 *
 * \True\App's constructor calls set_exception_handler and replaces ours.
 * Call true_register_fatal_handlers() again after `new \True\App` to restore
 * pretty rendering for uncaught exceptions.
 *
 * Uses __DIR__ for self-location so it does not depend on BP being defined.
 */

if (!function_exists('true_register_fatal_handlers')) {

	function true_render_fatal_page(string $message, string $file, int $line, ?string $trace = null): void
	{
		$templatePath = __DIR__ . '/../html/fatal-errors.html';

		while (ob_get_level() > 0) {
			@ob_end_clean();
		}

		if (!headers_sent()) {
			http_response_code(500);
			header('Content-Type: text/html; charset=utf-8');
		}

		$template = @file_get_contents($templatePath);
		if ($template === false) {
			echo "<pre>Fatal: " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
				. "\nFile: " . htmlspecialchars($file, ENT_QUOTES, 'UTF-8')
				. "\nLine: $line</pre>";
			return;
		}

		$snippet = 'Code snippet not available.';
		if ($file !== '' && is_file($file)) {
			$padding = 3;
			$lines = @file($file);
			if (is_array($lines)) {
				$start = max(0, $line - $padding - 1);
				$end = min(count($lines), $line + $padding);
				$snippet = '';
				for ($i = $start; $i < $end; $i++) {
					$lineNum = $i + 1;
					$highlight = $lineNum === $line
						? 'style="background:#ff5555;color:#fff;padding:2px 4px;"'
						: '';
					$snippet .= "<span $highlight>{$lineNum}: "
						. htmlspecialchars($lines[$i], ENT_QUOTES, 'UTF-8')
						. "</span><br>";
				}
			}
		}

		echo strtr($template, [
			'{message}'     => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
			'{file}'        => htmlspecialchars($file, ENT_QUOTES, 'UTF-8'),
			'{linenumber}'  => (string) $line,
			'{stacktrace}'  => $trace === null
				? ''
				: nl2br(htmlspecialchars($trace, ENT_QUOTES, 'UTF-8')),
			'{code}'        => $snippet,
		]);
	}

	/**
	 * Register the pretty exception handler. Call this again after anything
	 * that replaces set_exception_handler (e.g. \True\App's constructor).
	 * The shutdown-time fatal handler only needs to be registered once.
	 */
	function true_register_fatal_handlers(): void
	{
		set_exception_handler(function (\Throwable $e) {
			true_render_fatal_page(
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				$e->getTraceAsString()
			);
		});

		static $shutdownRegistered = false;
		if ($shutdownRegistered) return;
		$shutdownRegistered = true;

		register_shutdown_function(function () {
			$err = error_get_last();
			if (!$err) return;

			$fatalTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR
				| E_USER_ERROR | E_RECOVERABLE_ERROR;
			if (!($err['type'] & $fatalTypes)) return;

			true_render_fatal_page(
				$err['message'],
				$err['file'] ?? '',
				(int) ($err['line'] ?? 0)
			);
		});
	}

	true_register_fatal_handlers();
}
