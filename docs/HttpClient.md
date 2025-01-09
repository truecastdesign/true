# HttpClient Class Documentation

## Overview
The **HttpClient** class provides a way to send HTTP requests to external servers using various methods like `GET`, `POST`, and others. It supports customizable headers, query parameters, and request body types (JSON, XML, or form data). The response is processed and passed to a callback function for further handling.

---

## Public Methods

### `request($method, $endpoint, $callable, $body, $options)`
Sends an HTTP request to the specified endpoint and processes the server response.

#### Parameters
- **`$method`** (string): The HTTP method to use (e.g., `GET`, `POST`, `PUT`, `DELETE`).
- **`$endpoint`** (string): The URL of the server to which the request will be sent.
- **`$callable`** (callable): A callback function that processes the server response. The response is passed as an argument to this function.
- **`$body`** (mixed): The request body. It can be an array for form data or JSON, or a string for raw data.
- **`$options`** (array): An associative array of additional options for the request. Available keys:
  - `headers` (array|string): Custom headers to include in the request.
  - `timeout` (int): Connection timeout in seconds (default is 60).
  - `type` (string): The request body type (`json`, `xml`, or `form`).
  - `tlsv1.2` (bool): Enable TLSv1.2 for secure connections.
  - `proxy` (string): Proxy server address.
  - `query` (array): Query parameters to append to the URL.

#### Returns
- (void): The method does not return a value. The response is passed to the callback function.

---

## Example Usage

### Sending a GET Request
```php
use True\HttpClient;

$client = new HttpClient();
$client->request('GET', 'https://api.example.com/data', function($response) {
    echo "Response Status: {$response->status}\n";
    echo "Response Body: {$response->body}\n";
}, null, [
    'timeout' => 30
]);
```

### Sending a POST Request with JSON Body
```php
use True\HttpClient;

$client = new HttpClient();
$data = ['name' => 'John Doe', 'email' => 'john@example.com'];

$client->request('POST', 'https://api.example.com/users', function($response) {
    if ($response->status === '200') {
        echo "User created successfully!";
    } else {
        echo "Failed to create user.\n";
        print_r($response->headers);
    }
}, $data, [
    'type' => 'json',
    'headers' => ['Authorization: Bearer token123']
]);
```

---

## Options Details
| Option        | Description                                                |
|---------------|------------------------------------------------------------|
| `headers`     | Custom headers to send with the request. Can be an array or a string. |
| `timeout`     | Connection timeout in seconds. Default is 60 seconds.       |
| `type`        | The body type of the request. Values: `json`, `xml`, `form`.|
| `tlsv1.2`     | Enables TLSv1.2 for secure HTTPS connections.               |
| `proxy`       | Proxy server address to route the request through.          |
| `query`       | Query parameters to append to the endpoint URL.             |

---

## Response Handling
The response passed to the callback function is an object with the following properties:

| Property     | Description                   |
|--------------|--------------------------------|
| `version`    | The HTTP version (e.g., `HTTP/1.1`). |
| `status`     | The HTTP status code from the server response. |
| `headers`    | An associative array of response headers. |
| `body`       | The body content of the response. |

#### Example Response Object
```php
$response = (object) [
    'version' => 'HTTP/1.1',
    'status' => '200',
    'headers' => [
        'Content-Type' => 'application/json',
        'Set-Cookie' => ['sessionid=abc123', 'csrftoken=xyz456']
    ],
    'body' => '{"message":"Success"}'
];
```

---

## Summary
The **HttpClient** class simplifies making HTTP requests in PHP by providing a flexible and customizable interface. You can easily configure request headers, body types, and handle responses using callback functions. This makes it a valuable tool for interacting with APIs and external services in your PHP applications.

