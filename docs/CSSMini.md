# CSSMini Class Documentation

## Overview
The **CSSMini** class is a utility for minifying CSS code by removing unnecessary spaces and comments. It is designed to optimize CSS files for better performance and faster loading times.

You can use this class statically by calling:
```php
$str = True\CSSMini::process($str);
```

---

## Public Methods

### `process()`
This is the main method of the CSSMini class. It takes a CSS string as input and returns a minified version of that CSS.

#### Signature
```php
public static function process(string $css): string
```

#### Parameters
- **`$css`** (string): The raw CSS string to be minified.

#### Returns
- (string): The minified CSS string.

#### Example Usage
```php
$rawCSS = "body {\n    background-color: #fff;\n    margin: 0;\n}\n/* This is a comment */";
$minifiedCSS = True\CSSMini::process($rawCSS);

echo $minifiedCSS; // Outputs: "body {background-color:#fff;margin:0;}"
```

---

## Full Example
Here's a complete example showing how to use the **CSSMini** class to minify a CSS string:

#### Example Code
```php
use True\CSSMini;

$rawCSS = "body {\n    background-color: #fff;\n    margin: 0;\n}\n/* This is a comment */";
$minifiedCSS = CSSMini::process($rawCSS);

echo $minifiedCSS;
```

#### Output
```text
body {background-color:#fff;margin:0;}
```

---

## Summary
The **CSSMini** class is a lightweight, efficient tool for minifying CSS files. By using the `process()` method, you can quickly reduce the size of your CSS files by removing unnecessary spaces and comments, which helps improve website performance.

