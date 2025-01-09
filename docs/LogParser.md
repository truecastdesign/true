# LogParser Class Documentation

## Overview
The **LogParser** class allows you to parse and iterate through Apache log files, including `.gz` compressed log files. It provides functionality to extract details such as IP addresses, access times, requested pages, HTTP status codes, referers, and client browsers.

This class implements the **Iterator** interface, making it possible to loop through log entries using a `foreach` loop.

---

## Public Methods

### `__construct($logFile)`
Initializes the `LogParser` instance and automatically parses the specified log file.

#### Parameters
- **`$logFile`** (string): The path to the log file. Supports both plain text and `.gz` compressed log files.

#### Example Usage
```php
use True\LogParser;

$logParser = new LogParser('/path/to/access.log');
foreach ($logParser as $logEntry) {
    echo 'IP: ' . $logEntry->ip . PHP_EOL;
    echo 'Datetime: ' . $logEntry->datetime . PHP_EOL;
    echo 'Page: ' . $logEntry->page . PHP_EOL;
    echo 'HTTP Code: ' . $logEntry->code . PHP_EOL;
}
```

---

### `parse($logFile)`
Parses the specified log file and stores the parsed log entries in the `list` property.

#### Parameters
- **`$logFile`** (string): The path to the log file. Supports both plain text and `.gz` compressed log files.

#### Details
The method reads the log file, processes each log entry, and extracts the following fields:
- **IP Address** (`ip`)
- **Datetime** (`datetime`)
- **Page** (`page`)
- **Request Type** (`type`)
- **HTTP Status Code** (`code`)
- **Response Size in Bytes** (`size`)
- **Referer** (`referer`)
- **Client Browser** (`client`)

---

### Iterator Methods
The class implements the following **Iterator** interface methods:

#### `rewind()`
Resets the internal pointer to the beginning of the log list.

#### `current()`
Returns the current log entry as an object.

#### `next()`
Advances the internal pointer to the next log entry.

#### `valid()`
Checks if the current pointer position is valid.

#### `key()`
Returns the current key (index) in the log list.

---

## Example Usage
```php
use True\LogParser;

$logParser = new LogParser('/var/log/apache2/access.log.gz');

foreach ($logParser as $entry) {
    echo "IP Address: {$entry->ip}\n";
    echo "Date/Time: {$entry->datetime}\n";
    echo "Page: {$entry->page}\n";
    echo "HTTP Status Code: {$entry->code}\n";
    echo "Referer: {$entry->referer}\n";
    echo "Client: {$entry->client}\n";
    echo "Response Size: {$entry->size} bytes\n\n";
}
```

---

## Parsed Log Entry Structure
Each log entry is returned as an object with the following properties:

| Property   | Description                |
|------------|----------------------------|
| `ip`       | The IP address of the client. |
| `datetime` | The access date and time in `Y-m-d H:i:s` format. |
| `page`     | The requested page or resource. |
| `type`     | The request type (e.g., `GET`, `POST`). |
| `code`     | The HTTP status code (e.g., `200`, `404`). |
| `size`     | The size of the response in bytes. |
| `referer`  | The referer URL. |
| `client`   | The client browser user agent. |

---

## Summary
The **LogParser** class is a powerful tool for processing Apache log files. It supports both plain text and compressed `.gz` files, making it flexible for various server setups. By implementing the **Iterator** interface, it allows for straightforward iteration over parsed log entries.

