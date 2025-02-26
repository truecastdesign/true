
# Folder Class Documentation

## Overview
The **Folder** class provides a set of utilities for managing directory operations, including creating, removing, copying, moving, and listing contents of directories. This class aims to simplify common file system tasks with methods.

---

## Usage Examples

### Example 1: Removing a Directory
```php
True\Folder::remove('/path/to/directory');
```

### Example 2: Creating a Directory
```php
True\Folder::create('/path/to/new/directory');
```

### Example 3: Copying a Directory
```php
True\Folder::copy('/source/directory', '/destination/directory');
```

### Example 4: Listing Directory Contents
```php
$contents = True\Folder::listContents('/path/to/directory');
foreach ($contents as $item) {
    echo $item . "\\n";
}
```

### Example 5: Checking if a Directory is Empty
```php
if (True\Folder::isEmpty('/path/to/directory')) {
    echo "The directory is empty.";
} else {
    echo "The directory contains files or subdirectories.";
}
```

---

## Public Methods

### `remove()`
Recursively removes a directory and its contents.

#### Signature
```php
public function remove(string $dir): void
```

#### Parameters
- `$dir` (string): The path of the directory to remove.

#### Throws
- **Exception**: If directory removal fails.

### `create()`
Creates a directory recursively if it does not exist.

#### Signature
```php
public function create(string $path, int $mode = 0755, bool $recursive = true): bool
```

#### Parameters
- `$path` (string): The path of the directory to create.
- `$mode` (int): The permission mode, defaults to 0755.
- `$recursive` (bool): Whether to create nested directories, defaults to true.

#### Returns
- **bool**: True if the directory was created, false otherwise.

### `copy()`
Copies a directory and its contents to another location.

#### Signature
```php
public function copy(string $source, string $destination): void
```

#### Parameters
- `$source` (string): The source directory path.
- `$destination` (string): The target directory path.

#### Throws
- **Exception**: If copying fails.

### `listContents()`
Lists all files and directories within the specified path.

#### Signature
```php
public function listContents(string $path): array
```

#### Parameters
- `$path` (string): The directory path to list contents from.

#### Returns
- **array**: An array of file and directory names in the path.

#### Throws
- **Exception**: If the path is not a directory.

### `isEmpty()`
Checks if the given path is an empty directory.

#### Signature
```php
public function isEmpty(string $path): bool
```

#### Parameters
- `$path` (string): The directory path to check.

#### Returns
- **bool**: True if the directory is empty, false otherwise.

### `makeUnique()`
Generates a unique directory name within the given path.

#### Signature
```php
public function makeUnique(string $path, string $prefix = ''): string
```

#### Parameters
- `$path` (string): The base path where to create the unique directory.
- `$prefix` (string): Optional prefix for the directory name.

#### Returns
- **string**: The full path of the new unique directory.

### `move()`
Moves a directory from one location to another.

#### Signature
```php
public function move(string $source, string $destination): void
```

#### Parameters
- `$source` (string): The source directory path.
- `$destination` (string): The destination directory path.

#### Throws
- **Exception**: If moving fails.

---

## Summary
The **Folder** class is designed to handle various directory operations in PHP, providing methods that cover common needs in file system management. By leveraging this class, developers can perform tasks like folder creation, deletion, copying, and content management with ease, mirroring functionalities available in more extensive frameworks.
