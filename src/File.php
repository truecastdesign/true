<?php
namespace True;

/**
 * Uploaded File Object
 *
 * @package Truecast
 * @author Daniel Baldwin
 * @version 1.2.4
 * 
 * Use:
 * 
 * if ($request->files->fieldName->uploaded) {
 * 	$request->files->fieldName->move(BP.'/assets/', $request->files->fieldName->name);
 * 	echo $request->files->fieldName->ext; // jpg
 * 	echo $request->files->fieldName->mime; // image/jpeg
 * }
 * 
 * Multiple files uploaded with one file field
 * echo $request->files->fieldName[0]->name;
 * 
 * $request->files->file->imageWidth = 800;
 * $request->files->file->imageHeight = 800;
 * $request->files->file->imageQuality = 80;
 *	try {
 *		$request->files->file->resize();
 *		$request->files->file->move($path, $fileName);
 *		$App->response('{"result":"success", "filename":"'.$fileName.'"}', 'json');
 *	} catch (Exception $e) {
 *		$App->response('{"result":"error", "error":"'.$e->getMessage().'"}', 'json', 422);
 *	}
 * 
 */

class File
{
	var $file = null;
	var $imageHeight = null;
	var $imageWidth = null;
	var $imageQuality = 90;
	var $cropTop = 0;
	var $cropRight = 0;
	var $cropBottom = 0;
	var $cropLeft = 0;

	/**
	 * Constructs a new instance for handling an uploaded file.
	 *
	 * This constructor initializes the file object with the provided file array (typically from the $_FILES superglobal)
	 * and a given name. It determines whether the file was successfully uploaded (i.e. no error occurred), and if so,
	 * it extracts and sets the file extension (in lowercase) and MIME type based on the temporary file.
	 *
	 * @param array  $file An associative array containing file upload data (e.g., ['name', 'tmp_name', 'error', etc.]).
	 * @param mixed  $name A name associated with the file (can be used for further processing or labeling).
	 *
	 * @return void
	 */
	public function __construct($file, $name)
	{
		# check for multiple files on one field
		$this->file = $file;
		$this->file['uploaded'] = ($file['error']==0? true:false);
		if ($this->file['uploaded'] and !empty($this->file['tmp_name'])) {
			$this->file['ext'] = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$this->file['mime'] = mime_content_type($file['tmp_name']);
		}
	}

	public function __get($value)
	{
		return $this->file[$value];
	}

	/**
	 * Moves the uploaded file to the specified directory.
	 *
	 * This method ensures that the destination path is normalized to end with a 
	 * slash and then attempts to copy the temporary file to the new location. 
	 * If the copy operation fails, an exception is thrown.
	 *
	 * @param string $path The directory path where the file should be moved to. 
	 *                     This can end with or without a trailing slash.
	 * @param string $filename The name to save the file as in the destination directory.
	 * @throws \Exception If the file cannot be moved to the specified location.
	 */
	public function move($path, $filename)
	{
		$path = rtrim($path, '/') . '/';
		
		if (!@copy($this->file['tmp_name'], $path.$filename))
			throw new \Exception("The file could not be moved to the right folder!");
	}

	/**
	 * Resizes and optionally crops the uploaded image.
	 *
	 * This method performs the following operations:
	 * - Retrieves the source image dimensions using `getimagesize()`.
	 * - Calculates the target dimensions based on provided width and height properties.
	 *   If neither is provided, the source dimensions are used. If only one is provided,
	 *   the other is computed to maintain the aspect ratio.
	 * - Loads the image from its temporary location using the appropriate GD function,
	 *   depending on the MIME type (supports JPEG, GIF, PNG, and WebP).
	 * - Applies cropping offsets if any of the crop properties (`cropTop`, `cropRight`, 
	 *   `cropBottom`, `cropLeft`) are set, adjusting both the source dimensions and target dimensions.
	 * - Creates a new true color image and, for PNG and GIF files, preserves transparency.
	 * - Resamples the source image into the target image using `imagecopyresampled()`.
	 * - Saves the resized image back to the original temporary file location.
	 * - Resets the crop and dimension properties to their default values after processing.
	 *
	 * @return void
	 *
	 * @throws \Exception If the image file type is unsupported or if the image cannot be processed.
	 */
	public function resize()
	{
		list($sourceXSize, $sourceYSize) = getimagesize($this->file['tmp_name']);

		$sourceRatio = $sourceXSize / $sourceYSize;

		if (is_null($this->imageWidth) and is_null($this->imageHeight)) {
			$this->imageHeight = $sourceYSize;
			$this->imageWidth = $sourceXSize;
		}

		if (is_int($this->imageWidth) and is_null($this->imageHeight))
			$this->imageHeight = $this->imageWidth / $sourceRatio;

		elseif (is_int($this->imageHeight) and is_null($this->imageWidth))
			$this->imageWidth = $this->imageHeight / $sourceRatio;

		$image = $this->file['tmp_name'];

		switch ($this->file['mime']) {
			case 'image/jpeg': $sourceImage = imagecreatefromjpeg($image); break;
			case 'image/gif': $sourceImage = imagecreatefromgif($image); break;
			case 'image/webp': $sourceImage = imagecreatefromwebp($image); break;
			case 'image/png': $sourceImage = imagecreatefrompng($image); break;
		}

		$cropX = 0;
		$cropY = 0;

		if ($this->cropTop > 0) {
			$cropY = $this->cropTop;
			$this->imageHeight -= $this->cropTop;
		}

		if ($this->cropRight > 0) {
			$sourceXSize -= $this->cropRight;
			$this->imageWidth -= $this->cropRight;
		}

		if ($this->cropBottom > 0) {
			$sourceYSize -= $this->cropBottom;
			$this->imageHeight -= $this->cropBottom;
		}
			
		if ($this->cropLeft > 0) {
			$cropX = $this->cropLeft;
			$this->imageWidth -= $this->cropLeft;
		}

		$targetImage = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

		if ($this->file['mime'] == "image/gif" or $this->file['mime'] == "image/png") {
			imagecolortransparent($targetImage, imagecolorallocatealpha($targetImage, 0, 0, 0, 127));
			imagealphablending($targetImage, false);
			imagesavealpha($targetImage, true);
		}		
		
		if ($this->cropTop > 0 or $this->cropRight > 0 or $this->cropBottom > 0 or $this->cropLeft > 0) {
			imagecopyresampled($targetImage, $sourceImage, 0,0,$cropX,$cropY, $this->imageWidth, $this->imageHeight, $sourceXSize - $cropX, $sourceYSize - $cropY);
		} else {
			imagecopyresampled($targetImage, $sourceImage, 0,0,0,0, $this->imageWidth, $this->imageHeight, $sourceXSize, $sourceYSize);
		}		
	
		switch ($this->file['mime']) {
			case 'image/jpeg': imagejpeg($targetImage, $image); break;
			case 'image/gif': imagegif($targetImage, $image); break;
			case 'image/webp': imagewebp($targetImage, $image); break;
			case 'image/png': imagepng($targetImage, $image); break;
		}

		$this->cropTop = 0;
		$this->cropRight = 0;
		$this->cropBottom = 0;
		$this->cropLeft = 0;
		$this->imageWidth = null;
		$this->imageHeight = null; 
	}

	/**
	 * crop image
	 *
	 * @param array $coordinates [top, right, bottom, left] example: [0,0,20,20]
	 */
	public function crop(array $coordinates)
	{
		$this->cropTop = $coordinates[0] ?? 0;
		$this->cropRight = $coordinates[1] ?? 0;
		$this->cropBottom = $coordinates[2] ?? 0;
		$this->cropLeft = $coordinates[3] ?? 0;
		$this->resize();		
	}

	/**
	 * Crop both sides of the longest dimension
	 */
	public function cropSquare()
	{
		list($sourceXSize, $sourceYSize) = getimagesize($this->file['tmp_name']);
		
		# Landscape
		if ($sourceXSize > $sourceYSize) { 
			$crop = ceil(($sourceXSize - $sourceYSize) / 2);
			$this->crop([0,$crop,0,$crop]);
		} 
		# Portrait
		else {
			$crop = ceil(($sourceYSize - $sourceXSize) / 2);
			$this->crop([$crop,0,$crop,0]);
		}
	}

	/**
	 * Crop the bottom to make the image square
	 */
	public function cropBottomSquare()
	{
		list($sourceXSize, $sourceYSize) = getimagesize($this->file['tmp_name']);
		
		# Landscape
		if ($sourceXSize > $sourceYSize) { 
			$crop = ceil(($sourceXSize - $sourceYSize) / 2);
			$this->crop([0,$crop,0,$crop]);
		} 
		# Portrait
		else {
			$crop = ($sourceYSize - $sourceXSize);
			$this->crop([0,0,$crop,0]);
		}
	}

	/**
	 * Crop the top to make the image square
	 */
	public function cropTopSquare()
	{
		list($sourceXSize, $sourceYSize) = getimagesize($this->file['tmp_name']);
		
		# Landscape
		if ($sourceXSize > $sourceYSize) { 
			$crop = ceil(($sourceXSize - $sourceYSize) / 2);
			$this->crop([0,$crop,0,$crop]);
		} 
		# Portrait
		else {
			$crop = ($sourceYSize - $sourceXSize);
			$this->crop([$crop,0,0,0]);
		}
	}
}