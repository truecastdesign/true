# Response Class Documentation

## Overview
The **Response** class simplifies the process of sending HTTP responses with various content types (e.g., HTML, JSON, XML, plain text) and customizable headers. It can also be configured to automatically cache certain response types.

---

## Usage Examples

### Basic HTML Response
```php
$response = new \True\Response();
$response("<h1>Hello World</h1>");
```

### JSON Response
```php
$response = new \True\Response();
$response(["result" => "success"], 'json');
```

### Custom Headers and Status Code
```php
$response = new \True\Response();
$response('{"result":"success"}', 'json', 200, ["Cache-Control: no-cache"]);
```

### Enable Caching for JSON and HTML Responses
```php
$response = new \True\Response(['cacheJson', 'cacheHTML']);
$response("<h1>Cached HTML Response</h1>");
```

---

## Public Methods

### `__construct($prefs = [])`
Creates a new instance of the `Response` class with optional preferences.

#### Parameters
- **`$prefs`** (array): An array of preferences for the response.
  - **`cacheJson`**: Enables caching for JSON responses.
  - **`cacheHTML`**: Enables caching for HTML responses.
  - **`hsts`**: Enables HTTP Strict Transport Security (HSTS) headers.

---

### `__invoke($body, $type = 'html', $code = 200, $headers = [])`
Invokes the `Response` object to send an HTTP response.

#### Parameters
- **`$body`** (mixed): The content of the response. Can be a string, array, or object.
- **`$type`** (string): The content type of the response. Supported values:
  - `html` (default)
  - `json`
  - `xml`
  - `text`
- **`$code`** (int): The HTTP status code. Default is `200`.
- **`$headers`** (array): Additional headers to include in the response.

#### Behavior
- Automatically sets security-related headers like `X-Frame-Options`, `X-Content-Type-Options`, and `Referrer-Policy`.
- Automatically removes certain headers like `X-Powered-By` and `cache-control`.
- Supports automatic caching headers for `json` and `html` types if enabled in preferences.

---

## Content Types and Cache Control

| Type    | Content-Type Header         | Cache Control (if enabled)         |
|---------|-----------------------------|------------------------------------|
| `html`  | `text/html`                 | `max-age=604800, public` (7 days)  |
| `json`  | `application/json`          | `max-age=21600, public` (6 hours)  |
| `xml`   | `application/xml`           | No automatic caching               |
| `text`  | `text/plain`                | No automatic caching               |

---

## Security Headers
The **Response** class automatically includes the following security headers:
- `X-Frame-Options: SAMEORIGIN`
- `Strict-Transport-Security` (if `hsts` is enabled)
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: same-origin`

---

## Summary
The **Response** class provides an easy way to handle different types of HTTP responses, with built-in support for security headers and customizable caching options. It is flexible enough to handle various content formats and ensures that your responses are secure and efficient.

