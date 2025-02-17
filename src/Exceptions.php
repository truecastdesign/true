<?php

set_exception_handler(function($exception) {
	$template = file_get_contents(BP.'/vendor/truecastdesign/true/html/fatal-errors.html'); // Load the HTML template

	$replacements = [
		'{message}' => htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'),
		'{file}' => htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8'),
		'{linenumber}' => $exception->getLine(),
		'{stacktrace}' => nl2br(htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8')),
		'{code}' => getErrorCodeSnippet($exception->getFile(), $exception->getLine())
	];

	$output = str_replace(array_keys($replacements), array_values($replacements), $template);

	echo $output; 
});

/**
 * Extracts a snippet of code around the error line for context.
 */
function getErrorCodeSnippet($file, $line, $padding = 3) {
	if (!file_exists($file)) return 'Code snippet not available.';

	$lines = file($file);
	$start = max(0, $line - $padding - 1);
	$end = min(count($lines), $line + $padding);
	$snippet = '';

	for ($i = $start; $i < $end; $i++) {
		$lineNum = $i + 1;
		$highlight = $lineNum === $line ? 'style="background:#ff5555; color:white; padding:2px 4px;"' : '';
		$snippet .= "<span $highlight>{$lineNum}: " . htmlspecialchars($lines[$i]) . "</span><br>";
	}

	return $snippet;
}