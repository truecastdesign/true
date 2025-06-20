# Using the `True\Request` class

The `True\Request` class is designed to encapsulate information about an HTTP request, providing a convenient way to access various details about the request in a structured manner.

## Basic Usage

To instantiate a new `Request` object, simply do:

```php
$App->request = new \True\Request();
```

This will automatically gather information from the current HTTP request, including headers, URL details, HTTP method, and more.

## Accessing HTTP Method

You can access the HTTP method (e.g., GET, POST, PUT, DELETE) like this:

```php
echo $App->request->method; // Outputs: GET, POST, etc.
```

## Getting URL Information

The Request object provides several properties to access URL-related information:

url->path: The path part of the URL, excluding query strings.
url->host: The host part of the URL (e.g., www.example.com).
url->domain: The domain without subdomains.
url->protocol: Either http or https, depending on the request.
url->full: The full URL, including protocol and path.
url->protocolhost: The protocol and host combined.

```php
echo $App->request->url->path; // Outputs: /about
echo $App->request->url->full; // Outputs: https://www.example.com/about
```

## Accessing Client Information

You can obtain client-related information such as the IP address and user agent:

```php
echo $App->request->ip; // Outputs: 192.168.0.1
echo $App->request->userAgent; // Outputs: The browser's User Agent string
```

## Checking for HTTPS

To verify if the request was made over HTTPS:

```php
if ($App->request->https) {
    echo "Secure connection";
}
```

## Retrieving Headers

The Request class allows you to access headers like this:

```php
echo $App->request->headers->Authorization;
```

## Handling Request Parameters

The Request class provides convenient access to GET, POST, PUT, DELETE, and PATCH parameters:

```php
// Accessing GET parameters
echo $App->request->get->key;

// Accessing POST parameters
echo $App->request->post->key;
```

## Handling Files

If the request includes file uploads, you can access the uploaded files like this:

```php
if ($App->request->files->fileFieldName->uploaded) {
   $App->request->files->fileFieldName->move('/path/to/save', 'newname.jpg');
}
```

You can access file details like name, extension, and MIME type:

```php
echo $App->request->files->fileFieldName->name; // Outputs: image.jpg
echo $App->request->files->fileFieldName->mime; // Outputs: image/jpeg
```

## Checking the Current Path

To determine if the current path matches a specific pattern or this and sub paths using * as a wildcard. The '/about*' will return true for paths of /about and /about/staff

```html
<ul class="nav">
	<li><a href="/about" class="<?=$App->request->is('/about*')? 'active':''?>">About</a></li>
	<li><a href="/contact" class="<?=$App->request->is('/contact')? 'active':''?>">Contact</a></li>
</ul>
```

## Example for Using JSON Requests

If the request's content type is application/json, the JSON body will automatically be parsed and accessible via the corresponding HTTP method property:

```php
if ($App->request->method === 'POST' && $App->request->contentType === 'application/json') {
   $data = $App->request->post; // Access parsed JSON body as an object
   echo $data->name; // Assuming the JSON payload has a "name" field
}
```

## Merging All Request Data

To access all request data (GET, POST, etc.) as a single collection:

```php
echo $App->request->all->key; // Attempts to get a value from GET, POST, PUT, PATCH, DELETE collectively
```

## `is` Method

### Description
The `is` method determines if the current path matches a given pattern, using `*` as a wildcard. This is useful for checking whether the requested URL matches a specific path or set of paths.

---

### Parameters
- **`$pattern`** (string): The pattern to match against the current path. Examples of patterns include:
  - `'about'`: Matches exactly `/about`.
  - `'products*'`: Matches `/products`, `/products/item`, `/products/item/details`, etc.
  - `'/'`: Matches the root path `/`.

---

### Returns
- **(bool)**: Returns `true` if the current path matches the given pattern; otherwise, returns `false`.

---

### Usage
```php
// Example usage in a PHP file:
if ($App->request->is('/about')) {
    echo "Active path is /about";
}

// Using a wildcard to match sub-paths
if ($App->request->is('/products*')) {
    echo "This is a products-related page";
}

// Checking for the root path
if ($App->request->is('/')) {
    echo "This is the homepage";
}
```

---

## `has` Method

### Description
The `has` method checks if the request contains a specific key within a given HTTP method (GET, POST, PUT, etc.), and optionally checks for a specific value.

### Parameters
- **`$method`** (string): The HTTP method to check (e.g., `'POST'`, `'GET'`).
- **`$key`** (string): The key to look for in the request data.
- **`$value`** (mixed, optional): The expected value. If omitted, the method only checks for the existence of the key.

### Returns
- **(bool)**: Returns `true` if the request method matches and the key exists (and optionally, if the value matches); otherwise, returns `false`.

### Usage
```php
// Check if a POST request contains 'username'
if ($App->request->has('POST', 'username')) {
    echo "Username is provided!";
}

// Check if a GET request contains 'id' with a specific value
if ($App->request->has('GET', 'id', 123)) {
    echo "ID is 123!";
}
```

---

### Behavior and Examples

#### Exact Match:
- **Pattern**: `/about`
  - **Matches**: `/about`
  - **Does NOT Match**: `/about-us`, `/about/us`

---

#### Wildcard Match:
- **Pattern**: `/products*`
  - **Matches**: `/products`, `/products/item`, `/products/category/item`
  - **Does NOT Match**: `/product`, `/productsitem`

---

#### Root Path:
- **Pattern**: `/`
  - **Matches**: `/`
  - **Does NOT Match**: `/home`, `/about`

## Request Data Filters (Sanitization & Formatting)

In addition to basic access via:
```php
$value = $App->request->post->fieldName;
```
You can now sanitize or format incoming values directly with built-in filters:

### Example Usage

```php
// Returns a sanitized integer from POST
$businessId = $App->request->post->int('businessId');

// Returns only letters from a GET key
$code = $App->request->get->alpha('code');

// Removes unwanted characters from a name
$name = $App->request->post->name('fullName');

// Formats a monetary value
$amount = $App->request->post->formatCurrency('donation');

// Sanitizes a string for safe output
$bio = $App->request->post->escape('bio');
```

### Available Filter Methods

Each method accepts a field name (e.g., 'amount') and returns the cleaned value, or null if not present.



## Summary

The Request class is a powerful way to manage HTTP requests in your application, providing a clean and intuitive API for accessing headers, URL components, HTTP methods, and more. It also includes methods for handling file uploads and matching URL patterns using wildcards. This class can help you write clearer, more maintainable code when dealing with HTTP requests.