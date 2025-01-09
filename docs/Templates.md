# Templates Class Documentation

## Overview
The **Templates** class provides a utility for filling template files with dynamic values. It supports placeholder-based templating using `{key}` syntax.

---

## Public Methods

### `fill(string $template, object|array $values)`
Inserts values into a template file by replacing placeholders with corresponding values.

#### Parameters
- **`$template`** (string): The path to the template file containing placeholders.
- **`$values`** (object|array): An associative array or object containing key-value pairs to replace placeholders in the template.

#### Returns
- (string): The template with placeholders replaced by the provided values.

#### Example Usage
```php
use True\Templates;

$templatePath = BP.'/app/email/template.html';
$values = [
    'name' => 'John Doe',
    'date' => 'January 9, 2025'
];

try {
    $filledTemplate = Templates::fill($templatePath, $values);
    echo $filledTemplate;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

#### Explanation
Given a template file containing:
```html
<p>Hello, {name}!</p>
<p>Your appointment is scheduled for {date}.</p>
```
The output will be:
```html
<p>Hello, John Doe!</p>
<p>Your appointment is scheduled for January 9, 2025.</p>
```

---

## Error Handling
The `fill()` method throws an exception if the specified template file does not exist.

#### Example
```php
try {
    $filledTemplate = Templates::fill('/path/to/nonexistent/template.html', $values);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```
Output:
```
Error: The template file /path/to/nonexistent/template.html does not exist!
```

---

## Summary
The **Templates** class is a simple and effective solution for dynamic content rendering using placeholder-based templates. It allows you to quickly fill templates with provided values, making it ideal for email generation, content pages, and more.

