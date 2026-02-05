<?php
namespace True;

/**
 * Uploaded File Object
 *
 * @package Truecast
 * @author Daniel Baldwin
 * @version 1.3.0
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
	var $format = '';
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

		$this->file['uploaded'] = isset($file['error']) && $file['error'] === UPLOAD_ERR_OK &&!empty($file['tmp_name']) && is_readable($file['tmp_name']) && filesize($file['tmp_name']) > 0;

		if ($this->file['uploaded']) {
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
		
		try {
			if (!copy($this->file['tmp_name'], $path . $filename))
				throw new \Exception("The file could not be moved to the right folder!");
		} catch (\Throwable $e) {
			throw new \Exception("File move failed: " . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * Resizes and optionally converts or crops the uploaded image.
	 *
	 * This method performs the following operations:
	 * - Retrieves the source image dimensions using `getimagesize()`.
	 * - Calculates the target dimensions based on provided width and height.
	 *   If neither is provided, the original dimensions are used.
	 *   If only one is provided, the other is calculated to preserve aspect ratio.
	 * - Loads the image using the appropriate GD function (JPEG, PNG, GIF, WebP, AVIF).
	 * - Applies cropping offsets if any of the crop properties (`cropTop`, `cropRight`, `cropBottom`, `cropLeft`) are set.
	 * - Creates a new true-color image canvas, preserving transparency for PNG/GIF sources.
	 * - Resamples the source image into the new dimensions.
	 * - Optionally converts the image to a new format if `$outputFormat` is provided (`'jpg'`, `'png'`, `'webp'`).
	 * - Saves the resulting image back to the temporary file location.
	 * - Resets the crop and dimension properties after processing.
	 *
	 * Example:
	 * ```php
	 * $request->files->photo->imageWidth = 800;
	 * $request->files->photo->resize('jpg'); // resize and convert to JPEG
	 * ```
	 *
	 * @param string|null $outputFormat Optional. Target format to convert to ('jpg', 'png', 'webp'). If omitted, the original format is preserved.
	 * @return void
	 *
	 * @throws \Exception If the image type is unsupported or cannot be processed.
	 */
	public function resize()
	{
		list($srcW, $srcH) = getimagesize($this->file['tmp_name']);
		$image = $this->file['tmp_name'];

		switch ($this->file['mime']) {
			case 'image/jpeg': $src = imagecreatefromjpeg($image); break;
			case 'image/gif': $src = imagecreatefromgif($image); break;
			case 'image/webp': $src = imagecreatefromwebp($image); break;
			case 'image/png': $src = imagecreatefrompng($image); break;
			case 'image/avif': $src = imagecreatefromavif($image); break;
			default: throw new \Exception('Unsupported source mime: '.$this->file['mime']);
		}

		// --- crop box (effective source area)
		$cropX = max(0, (int)$this->cropLeft);
		$cropY = max(0, (int)$this->cropTop);
		$effW = max(1, (int)($srcW - $this->cropLeft - $this->cropRight));
		$effH = max(1, (int)($srcH - $this->cropTop  - $this->cropBottom));

		// --- target size
		if ($this->imageWidth === null && $this->imageHeight === null) { $dstW=$effW; $dstH=$effH; }
		elseif ($this->imageWidth !== null && $this->imageHeight === null) { $dstW=(int)round($this->imageWidth); $dstH=(int)round($dstW*($effH/$effW)); }
		elseif ($this->imageHeight !== null && $this->imageWidth === null) { $dstH=(int)round($this->imageHeight); $dstW=(int)round($dstH*($effW/$effH)); }
		else { $dstW=(int)round($this->imageWidth); $dstH=(int)round($this->imageHeight); }

		$dstW = max(1,$dstW); $dstH = max(1,$dstH);

		$dst = imagecreatetruecolor($dstW, $dstH);
		if (in_array($this->file['mime'], ['image/gif','image/png'])) {
			imagecolortransparent($dst, imagecolorallocatealpha($dst, 0,0,0,127));
			imagealphablending($dst, false);
			imagesavealpha($dst, true);
		}

		imagecopyresampled($dst, $src, 0,0, $cropX,$cropY, $dstW,$dstH, $effW,$effH);

		// --- choose encoder based on $this->format (fallback: keep source mime)
		$fmt = strtolower(trim($this->format));

		if ($fmt !== '') {
			switch ($fmt) {
				case 'jpg':
				case 'jpeg':
					$flat = imagecreatetruecolor($dstW,$dstH);
					$white = imagecolorallocate($flat,255,255,255);
					imagefill($flat,0,0,$white);
					imagecopy($flat,$dst,0,0,0,0,$dstW,$dstH);
					if (!imagejpeg($flat,$image,$this->imageQuality)) throw new \Exception('JPEG save failed');
					imagedestroy($flat);
					$this->file['ext']='jpg'; $this->file['mime']='image/jpeg';
					break;
				case 'png':
					imagesavealpha($dst,true);
					$c = 9 - (int)round($this->imageQuality/10); if ($c<0) $c=0; if ($c>9) $c=9;
					if (!imagepng($dst,$image,$c)) throw new \Exception('PNG save failed');
					$this->file['ext']='png'; $this->file['mime']='image/png';
					break;
				case 'webp':
					imagesavealpha($dst,true);
					if (!imagewebp($dst,$image,$this->imageQuality)) throw new \Exception('WebP save failed');
					$this->file['ext']='webp'; $this->file['mime']='image/webp';
					break;
				case 'avif':
					if (!function_exists('imageavif')) throw new \Exception('AVIF not supported by GD');
					imagesavealpha($dst,true);
					if (!imageavif($dst,$image,$this->imageQuality)) throw new \Exception('AVIF save failed');
					$this->file['ext']='avif'; $this->file['mime']='image/avif';
					break;
				case 'gif':
					if (!imagegif($dst,$image)) throw new \Exception('GIF save failed');
					$this->file['ext']='gif'; $this->file['mime']='image/gif';
					break;
				default: throw new \Exception('Unsupported output format: '.$fmt);
			}
		} else {
			switch ($this->file['mime']) {
				case 'image/jpeg': if (!imagejpeg($dst,$image,$this->imageQuality)) throw new \Exception('JPEG save failed'); break;
				case 'image/gif':  if (!imagegif($dst,$image)) throw new \Exception('GIF save failed'); break;
				case 'image/webp': if (!imagewebp($dst,$image,$this->imageQuality)) throw new \Exception('WebP save failed'); break;
				case 'image/png':
					imagesavealpha($dst,true);
					$c = 9 - (int)round($this->imageQuality/10); if ($c<0) $c=0; if ($c>9) $c=9;
					if (!imagepng($dst,$image,$c)) throw new \Exception('PNG save failed');
					break;
				case 'image/avif':
					if (!function_exists('imageavif')) throw new \Exception('AVIF not supported by GD');
					imagesavealpha($dst,true);
					if (!imageavif($dst,$image,$this->imageQuality)) throw new \Exception('AVIF save failed');
					break;
				default: throw new \Exception('Saving in original mime failed/unsupported');
			}
		}

		imagedestroy($src);
		imagedestroy($dst);

		$this->cropTop = $this->cropRight = $this->cropBottom = $this->cropLeft = 0;
		$this->imageWidth = $this->imageHeight = null;
	}

	/**
	 * Converts the uploaded image to a specified format without resizing.
	 *
	 * This method re-encodes the temporary image file into a new format.
	 * If the image is already in the requested format, it will simply re-save it.
	 * Transparency in PNG or GIF images is flattened to white when converting to JPEG.
	 *
	 * Supported formats: `'jpg'`, `'jpeg'`, `'png'`, `'webp'`.
	 *
	 * Example:
	 * ```php
	 * $request->files->photo->convert('jpg');  // convert PNG to JPG
	 * $request->files->photo->convert('webp'); // re-encode image to WebP
	 * ```
	 *
	 * @param string $format Target format ('jpg', 'jpeg', 'png', or 'webp').
	 * @param resource|null $sourceImage Optional. Existing GD image resource to convert from.
	 *                                   If omitted, the method loads the current file automatically.
	 * @return void
	 *
	 * @throws \Exception If the format is unsupported or conversion fails.
	 */
	public function convert(string $format, $gdImage): void
	{
		$format = strtolower(trim($format)) ?: 'jpg';
		$path   = $this->file['tmp_name'];

		$w = imagesx($gdImage);
		$h = imagesy($gdImage);

		switch ($format) {
			case 'jpg':
			case 'jpeg':
				// Flatten transparency onto white for JPEG
				$flat = imagecreatetruecolor($w, $h);
				$white = imagecolorallocate($flat, 255, 255, 255);
				imagefilledrectangle($flat, 0, 0, $w, $h, $white);
				imagecopy($flat, $gdImage, 0, 0, 0, 0, $w, $h);

				if (!imagejpeg($flat, $path, $this->imageQuality)) throw new \Exception('Failed to write JPEG');
				imagedestroy($flat);

				$this->file['ext']  = 'jpg';
				$this->file['mime'] = 'image/jpeg';
				break;

			case 'png':
				// Preserve alpha for PNG
				imagealphablending($gdImage, false);
				imagesavealpha($gdImage, true);
				// Convert quality (0–100) to PNG compression (0–9); higher compression is slower.
				$compression = max(0, min(9, 9 - (int)round($this->imageQuality / 10)));
				if (!imagepng($gdImage, $path, $compression)) throw new \Exception('Failed to write PNG');

				$this->file['ext']  = 'png';
				$this->file['mime'] = 'image/png';
				break;

			case 'webp':
				if (!function_exists('imagewebp')) throw new \Exception('WEBP not supported by GD');
				if (!imagewebp($gdImage, $path, $this->imageQuality)) throw new \Exception('Failed to write WEBP');

				$this->file['ext']  = 'webp';
				$this->file['mime'] = 'image/webp';
				break;

			case 'avif':
				if (!function_exists('imageavif')) throw new \Exception('AVIF not supported by GD');
				// PHP/GD AVIF quality is typically 0–100 as well
				if (!imageavif($gdImage, $path, $this->imageQuality)) throw new \Exception('Failed to write AVIF');

				$this->file['ext']  = 'avif';
				$this->file['mime'] = 'image/avif';
				break;

			default:
				throw new \Exception('Unsupported output format: ' . $format);
		}
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