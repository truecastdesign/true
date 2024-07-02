<?php
namespace True;

/**
 * For templates that have {key} style placeholders for values.
 * 
 * Use:
 * 
 * $filledTemplate = True\Templates::fill(BP.'/app/email/template.html', []);
 */
class Templates
{
	/**
	 * Insert values into the template.
	 *
	 * @param string $template The template with placeholders.
	 * @param object|array $values Associative array of values to replace placeholders.
	 * @return string The template with inserted values.
	 */
	public static function fill(string $template, object|array $values) {
		if (file_exists($template))
			$templateStr = file_get_contents($template);
		else
			throw new \Exception("The template file $template does not exist!");
		
		if (is_object($values))
			$values = (array) $values;
		
		foreach ($values as $key => $value)
			$templateStr = str_replace('{' . $key . '}', $value, $templateStr);
		
		return $templateStr;
	}
}