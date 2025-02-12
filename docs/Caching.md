# TrueFW Cache Class Documentation

## Overview
The `Cache` class provides a simple caching mechanism within TrueFW using a SQLite database backend. It allows you to store, retrieve, delete, and clear cached data efficiently. The database file is located in /vendor/truecastdesign/true/data/caching.sqlite. Move it to your /app/data folder before using.

## Usage Examples

### Basic Cache Implementation
```php
$DBTrueCache = new Truecast\Hopper(['driver' => 'sqlite', 'database' => BP.'/app/data/caching.sqlite']);

$App->cache = new True\Cache($DBTrueCache);

// Clean URL
$url = strtok($_SERVER["REQUEST_URI"], '?');

$cacheObj = $App->cache->get($url);

if ($cacheObj === false) {
    ob_start();
    
    // Run dynamic content generation
    
    $buffer = ob_get_contents();
    ob_end_flush();
    
    $App->cache->set($url, $buffer);
} else {
    echo $cacheObj;
}
```

### Caching Complex Data
```php
$DBTrueCache = new Truecast\Hopper(['driver' => 'sqlite', 'database' => BP.'/app/data/caching.sqlite']);

$App->cache = new True\Cache($DBTrueCache);

// Clean URL
$url = strtok($_SERVER["REQUEST_URI"], '?');

$cacheObj = $App->cache->get($url);

if ($cacheObj === false) {
    ob_start();
    
    // Run dynamic content generation
    
    $buffer = ob_get_contents();
    ob_end_flush();
    
    $saveCacheObj = (object)[];
    $saveCacheObj->buffer = $buffer;
    $saveCacheObj->docTitle = $docTitle;
    $saveCacheObj->description = $description;
    
    $App->cache->set($url, $saveCacheObj);
} else {
    $buffer = $cacheObj->buffer;
    $docTitle = $cacheObj->docTitle;
    $description = $cacheObj->description;
}
```

## Methods

### `set($key, $content)`
Stores an item in the cache.

```php
public function set($key, $content): bool
```

#### Parameters
- `$key` *(string)* – The unique identifier for the cache item.
- `$content` *(bool|integer|string|object|array)* – The data to cache.

#### Returns
- `bool` – `true` if caching was successful, `false` otherwise.

#### Example
```php
$cache->set('homepage', ['title' => 'Welcome', 'body' => 'Hello World']);
```

---

### `get($key)`
Retrieves an item from the cache.

```php
public function get($key)
```

#### Parameters
- `$key` *(string)* – The unique identifier for the cache item.

#### Returns
- `mixed` – The stored content if found; `false` if not found.

#### Example
```php
$data = $cache->get('homepage');
if (is_array($data)) {
    echo $data['title'];
}
```

---

### `delete($key)`
Deletes a single or multiple cache items.

```php
public function delete($key): bool
```

#### Parameters
- `$key` *(string|array)* – The cache key or an array of keys to delete.

#### Returns
- `bool` – `true` if deletion was successful, `false` otherwise.

#### Example
```php
$cache->delete('homepage');
$cache->delete(['page1', 'page2']);
```

---

### `flush()`
Clears all cached data.

```php
public function flush(): bool
```

#### Returns
- `bool` – `true` if cache was successfully cleared.

#### Example
```php
$cache->flush();
```

---

### `getStats()`
Retrieves cache statistics.

```php
public function getStats()
```

#### Returns
- `object` – Contains cache size in bytes and total records.

#### Example
```php
$stats = $cache->getStats();
echo "Cache size: " . $stats->bytes . " bytes";
echo "Total cached records: " . $stats->records;
```

## Notes
- Data is stored in a database table named `data`.
- The `content` column stores JSON-encoded data for objects and arrays.
- The cache supports different data types, including strings, integers, booleans, arrays, and objects.

## Best Practices
- Use meaningful keys to avoid collisions.
- Regularly flush outdated or unnecessary cached data.
- Ensure database connectivity before using the cache class.

## Changelog
### Version 1.1.1
- Added type validation for stored content.
- Improved error handling in `delete()`.
- Enhanced `getStats()` to include record count.

---

**Author:** Daniel Baldwin  
**Version:** 1.1.1

