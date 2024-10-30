# Using the `True\DataCleaner` Class

The `DataCleaner` class provides a collection of static methods for cleaning and formatting various types of data that a website might encounter. It helps ensure that input data is sanitized, safe, and in the correct format.

## Basic Usage

Since all methods in the `DataCleaner` class are static, you can call them directly without creating an instance of the class.

```php
use True\DataCleaner;

// Sanitize a string to contain only letters and numbers
$cleanString = DataCleaner::alphaInt($inputString);
```

## Methods

### `streetAddress($str)`

Cleans a street address by removing any characters that are not letters, numbers, or common address symbols.

```php
$address = DataCleaner::streetAddress($inputAddress);
```

---

### `int($str)`

Removes all characters except digits.

```php
$number = DataCleaner::int($inputString);
```

---

### `alpha($str)`

Removes all characters except letters (a-z and A-Z).

```php
$lettersOnly = DataCleaner::alpha($inputString);
```

---

### `alphaInt($str)`

Removes all characters except letters and digits.

```php
$alphaNumeric = DataCleaner::alphaInt($inputString);
```

---

### `name($str)`

Cleans a name string by allowing letters, numbers, spaces, and common name punctuation.

```php
$cleanName = DataCleaner::name($inputName);
```

---

### `decimal($str)`

Removes all characters except digits, dots, and minus signs.

```php
$decimalNumber = DataCleaner::decimal($inputNumber);
```

---

### `filePath($str)`

Removes all characters except letters, numbers, and hyphens.

```php
$cleanFilePath = DataCleaner::filePath($inputPath);
```

---

### `dbField($str)`

Removes all characters except letters, numbers, hyphens, underscores, and spaces.

```php
$dbFieldName = DataCleaner::dbField($inputField);
```

---

### `creditCard($str)`

Removes all characters except digits.

```php
$ccNumber = DataCleaner::creditCard($inputCC);
```

---

### `postalCode($str)`

Cleans a postal code by removing all characters except letters, numbers, hyphens, and spaces.

```php
$postalCode = DataCleaner::postalCode($inputPostalCode);
```

---

### `addDashes($CC_Num, $CC_Type)`

Formats a credit card number by adding dashes based on the card type.

```php
$formattedCC = DataCleaner::addDashes($ccNumber, 'Visa');
```

---

### `email($str)`

Sanitizes an email address.

```php
$email = DataCleaner::email($inputEmail);
```

---

### `url($str)`

Sanitizes a URL.

```php
$url = DataCleaner::url($inputUrl);
```

---

### `filterOutURLs($str)`

Removes URLs from a string.

```php
$textWithoutUrls = DataCleaner::filterOutURLs($inputText);
```

---

### `ip($str)`

Validates and returns an IP address.

```php
$ipAddress = DataCleaner::ip($inputIP);
```

---

### `float($str)`

Sanitizes a floating-point number, allowing fractions.

```php
$floatNumber = DataCleaner::float($inputFloat);
```

---

### `phoneFormat($ph, $type = 1, $noCountryCode = false)`

Formats a phone number in various styles.

- **Type 1**: `555-555-5555` or `1-555-555-5555`
- **Type 2**: `(555) 555-5555` or `1 (555) 555-5555`
- **Type 3**: E.164 format `+15555555555`

```php
$formattedPhone = DataCleaner::phoneFormat($inputPhone, 2);
```

---

### `currency($str)`

Formats a number as currency.

```php
$currency = DataCleaner::currency($amount);
```

---

### `titleCase($string)`

Converts a string to title case, handling exceptions for certain words.

```php
$titleCasedString = DataCleaner::titleCase($inputString);
```

---

### `postalCodeFormat($string, $country = 'US')`

Formats a postal code based on the country.

- **US**: `12345` or `12345-6789`
- **CA**: `A1A 1A1`
- **AU**: `1234`

```php
$formattedPostalCode = DataCleaner::postalCodeFormat($inputPostalCode, 'CA');
```

---

### `splitName($name)`

Splits a full name into first and last name.

```php
list($firstName, $lastName) = DataCleaner::splitName($fullName);
```

---

### `convertNum($num)`

Converts a number to its English word representation.

```php
$numberInWords = DataCleaner::convertNum(123);
```

---

### `charset_decode_utf_8($string)`

Decodes a UTF-8 encoded string to its original character set.

```php
$decodedString = DataCleaner::charset_decode_utf_8($inputString);
```

---

### `forMetaTags($str)`

Prepares a string for use in HTML meta tags by removing or encoding special characters.

```php
$metaTagContent = DataCleaner::forMetaTags($inputString);
```

---

### `encodeQuotes($str)`

Encodes single and double quotes in a string to HTML entities.

```php
$encodedString = DataCleaner::encodeQuotes($inputString);
```

---

### `forHtmlEditors($str)`

Prepares a string for use in HTML editors by encoding ampersands.

```php
$editorContent = DataCleaner::forHtmlEditors($inputString);
```

---

### `htmlOutput($str)`

Prepares a string for HTML output by encoding special characters.

```php
$htmlOutput = DataCleaner::htmlOutput($inputString);
```

---

### `sanitize($str, $santype = 1)`

Sanitizes a string based on the specified type.

- **Type 1**: Strips HTML tags.
- **Type 2**: Strips HTML tags and converts characters to HTML entities.
- **Type 3**: Same as Type 2.

```php
$sanitizedString = DataCleaner::sanitize($inputString, 2);
```

---

### `escape($str)`

Encodes special HTML characters.

```php
$escapedString = DataCleaner::escape($inputString);
```

---

## Example Usage

```php
use True\DataCleaner;

$inputEmail = 'user@example.com';
$cleanEmail = DataCleaner::email($inputEmail);

$inputPhone = '(555) 123-4567';
$formattedPhone = DataCleaner::phoneFormat($inputPhone, 2);

$inputAddress = '123 Main St., Apt #4B';
$cleanAddress = DataCleaner::streetAddress($inputAddress);

echo "Email: $cleanEmail\n";
echo "Phone: $formattedPhone\n";
echo "Address: $cleanAddress\n";
```

---

## Notes

- **Deprecated Methods**: Methods like `intOnly` and `alphaOnly` are deprecated. Use `int` and `alpha` instead.
- **Error Handling**: The methods return an empty string if the input is `null`.
- **Input Validation**: It's assumed that the input is a string or `null`. Passing other types may lead to unexpected results.

