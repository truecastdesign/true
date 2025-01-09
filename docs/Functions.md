# Functions Class Documentation

## Overview
The **Functions** class provides a wide range of utility methods for encryption, URL parsing, file size formatting, text processing, and more. This class is part of the True Framework.

---

## Public Methods

### `encrypt()`
Encrypts a plain text string using the AES-128-CBC encryption method.

#### Signature
```php
public static function encrypt(string $str, string $key): string
```

#### Parameters
- **`$str`** (string): The plain text string to encrypt.
- **`$key`** (string): The private key used for encryption.

#### Returns
- (string): The encrypted string, encoded in Base64 format.

#### Example Usage
```php
$encrypted = Functions::encrypt('Hello World!', 'mySecretKey');
echo $encrypted;
```

---

### `decrypt()`
Decrypts an encrypted string using the AES-128-CBC encryption method.

#### Signature
```php
public static function decrypt(string $str, string $key): ?string
```

#### Parameters
- **`$str`** (string): The encrypted string to decrypt.
- **`$key`** (string): The private key used for decryption.

#### Returns
- (string|null): The decrypted plain text string, or `null` if the decryption fails.

#### Example Usage
```php
$decrypted = Functions::decrypt($encrypted, 'mySecretKey');
echo $decrypted;
```

---

### `genToken()`
Generates a secure random token.

#### Signature
```php
public static function genToken(int $length = 64): string
```

#### Parameters
- **`$length`** (int): The length of the token. Default is 64.

#### Returns
- (string): A secure random token.

#### Example Usage
```php
$token = Functions::genToken();
echo $token;
```

---

### `host()`
Returns the full host name of the current request.

#### Signature
```php
public static function host(): string
```

#### Returns
- (string): The host name, including the protocol (e.g., `https://www.example.com`).

#### Example Usage
```php
echo Functions::host();
```

---

### `parseUrl()`
Parses a URL and returns its components.

#### Signature
```php
public static function parseUrl(string $url, bool $extra = false): object
```

#### Parameters
- **`$url`** (string): The URL to parse.
- **`$extra`** (bool): Whether to include additional parts such as port and user credentials. Default is `false`.

#### Returns
- (object): An object containing the parsed components of the URL.

#### Example Usage
```php
$urlInfo = Functions::parseUrl('https://user:pass@www.example.com:8080/path?query=123#fragment');
print_r($urlInfo);
```

---

### `getBrowser()`
Detects the browser name and version from the user agent string.

#### Signature
```php
public function getBrowser(): array
```

#### Returns
- (array): An associative array with `name` and `version` keys representing the browser's name and version.

#### Example Usage
```php
$browser = Functions::getBrowser();
print_r($browser);
```

---

### `supportsGrid()`
Checks if the detected browser supports CSS Grid.

#### Signature
```php
public function supportsGrid(array $params): bool
```

#### Parameters
- **`$params`** (array): An array with `name` and `version` keys.

#### Returns
- (bool): `true` if the browser supports CSS Grid, `false` otherwise.

#### Example Usage
```php
$browser = ['name' => 'Chrome', 'version' => '57'];
if (Functions::supportsGrid($browser)) {
    echo 'CSS Grid is supported!';
} else {
    echo 'CSS Grid is not supported.';
}
```

---

### `humanFileSize()`
Converts a file size in bytes to a human-readable format.

#### Signature
```php
public static function humanFileSize(int $size, string $unit = ''): string
```

#### Parameters
- **`$size`** (int): The file size in bytes.
- **`$unit`** (string): The unit to use (`GB`, `MB`, `KB`). Default is an empty string for automatic selection.

#### Returns
- (string): The formatted file size with the appropriate unit.

#### Example Usage
```php
echo Functions::humanFileSize(1048576); // Outputs: 1.00MB
```

---

### `contains()`
Checks if a string contains another string.

#### Signature
```php
public static function contains(string $content, string $str, bool $ignorecase = true): bool
```

#### Parameters
- **`$content`** (string): The string to search in.
- **`$str`** (string): The string to search for.
- **`$ignorecase`** (bool): Whether to ignore case. Default is `true`.

#### Returns
- (bool): `true` if the string is found, `false` otherwise.

#### Example Usage
```php
if (Functions::contains('Hello World', 'world')) {
    echo 'String found!';
} else {
    echo 'String not found.';
}
```

---

### `keys_exist()`
Checks if all specified keys exist in an array.

#### Signature
```php
public static function keys_exist(array $keys, array $array): bool
```

#### Parameters
- **`$keys`** (array): The keys to check for.
- **`$array`** (array): The array to check.

#### Returns
- (bool): `true` if all keys exist, `false` otherwise.

#### Example Usage
```php
$keys = ['name', 'email'];
$array = ['name' => 'John', 'email' => 'john@example.com'];

if (Functions::keys_exist($keys, $array)) {
    echo 'All keys exist.';
} else {
    echo 'Some keys are missing.';
}
```

---

### `dollars()`
Formats a number as a dollar amount.

#### Signature
```php
public static function dollars(float $amount): string
```

#### Parameters
- **`$amount`** (float): The amount to format.

#### Returns
- (string): The formatted dollar amount.

#### Example Usage
```php
echo Functions::dollars(1234.56); // Outputs: $1,234.56
```

---

### `getLastPartOfURL()`
Gets the last part of a URL or the current request URI.

#### Signature
```php
public static function getLastPartOfURL(string|bool $str): string
```

#### Parameters
- **`$str`** (string|bool): The URL string or `true` to use the current request URI.

#### Returns
- (string): The last part of the URL.

#### Example Usage
```php
echo Functions::getLastPartOfURL('https://www.example.com/path/to/page'); // Outputs: page
```

