# File Class Documentation

## Overview
The **File** class provides functionality to handle file uploads and perform basic image manipulations, such as moving uploaded files to a specific directory and resizing or cropping images.

This class is primarily intended for handling file uploads from HTML forms in a structured and secure manner.

---

## Usage Examples

### Example 1: Handling Single File Uploads
```php
if ($request->files->fieldName->uploaded) {
    $request->files->fieldName->move(BP.'/assets/', $request->files->fieldName->name);
    echo $request->files->fieldName->ext; // Outputs: jpg
    echo $request->files->fieldName->mime; // Outputs: image/jpeg
}
```

### Example 2: Handling Multiple File Uploads
```php
echo $request->files->fieldName[0]->name;
```

### Example 3: Resizing an Uploaded Image
```php
$request->files->file->imageWidth = 800;
$request->files->file->imageHeight = 800;
$request->files->file->imageQuality = 80;

try {
    $request->files->file->resize();
    $request->files->file->move($path, $fileName);
    $App->response('{"result":"success", "filename":"'.$fileName.'"}', 'json');
} catch (Exception $e) {
    $App->response('{"result":"error", "error":"'.$e->getMessage().'"}', 'json', 422);
}
```

---

## Public Methods

### `__construct()`
Initializes the File object with the uploaded file data.

#### Signature
```php
public function __construct(array $file, string $name)
```

#### Parameters
- **`$file`** (array): The uploaded file data from the `$_FILES` superglobal.
- **`$name`** (string): The name of the uploaded file.

### `move()`
Moves the uploaded file to a specified directory.

#### Signature
```php
public function move(string $path, string $filename): void
```

#### Parameters
- **`$path`** (string): The directory where the file should be moved.
- **`$filename`** (string): The name to give the file in the new location.

#### Throws
- **Exception**: If the file cannot be moved.

### `resize()`
Resizes the uploaded image to the specified dimensions while maintaining the aspect ratio.

#### Signature
```php
public function resize(): void
```

#### Usage
Set the `imageWidth`, `imageHeight`, and `imageQuality` properties before calling this method.

### `crop()`
Crops the image based on the provided coordinates.

#### Signature
```php
public function crop(array $coordinates): void
```

#### Parameters
- **`$coordinates`** (array): An array specifying the crop dimensions as `[top, right, bottom, left]`.

### `cropSquare()`
Automatically crops the image to make it a square by removing portions from both sides of the longest dimension.

#### Signature
```php
public function cropSquare(): void
```

### `cropBottomSquare()`
Crops the image from the bottom to make it a square.

#### Signature
```php
public function cropBottomSquare(): void
```

### `cropTopSquare()`
Crops the image from the top to make it a square.

#### Signature
```php
public function cropTopSquare(): void
```

---

## Properties

### `imageHeight`
The height of the image to resize to.

- **Type**: int|null

### `imageWidth`
The width of the image to resize to.

- **Type**: int|null

### `imageQuality`
The quality of the resized image (only applicable for JPEG images).

- **Type**: int
- **Default**: 90

---

## Summary
The **File** class is a versatile tool for handling file uploads and basic image processing in PHP applications. It provides methods for moving files, resizing images, and cropping images to desired dimensions. By using this class, developers can streamline file handling processes and ensure that uploaded files are managed securely and efficiently.

