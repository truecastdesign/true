<?php
namespace True;

/**
 * Uploaded File Object
 *
 * @package Truecast
 * @author Daniel Baldwin
 * @version 1.1.0
 * 
 * Use:
 * 
 * if ($request->files->fieldName->uploaded) {
 * 	$request->files->fieldName->move(BP.'/assets/', $request->files->fieldName->name);
 * 	echo $request->files->fieldName->ext; // jpg
 * 	echo $request->files->fieldName->mime; // image/jpeg
 * }
 * 
 * $request->files->file->imageXSize = 800;
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
	var $imageXSize = null;
	var $imageYSize = null;
	var $imageQuality = 90;

	public function __construct($file)
	{
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

	public function move($path, $filename)
	{
		if (!@copy($this->file['tmp_name'], $path.$filename))
			throw new \Exception("The file could not be moved to the right folder!");
	}

	public function resize()
	{
		list($sourceXSize, $sourceYSize) = getimagesize($this->file['tmp_name']);

		$sourceRatio = $sourceXSize / $sourceYSize;

		if (is_null($this->imageXSize) and is_null($this->imageYSize))
			throw new \Exception("The image width and height are missing!");

		if (is_int($this->imageXSize) and is_null($this->imageYSize))
			$this->imageYSize = $this->imageXSize / $sourceRatio;

		elseif (is_int($this->imageYSize) and is_null($this->imageXSize))
			$this->imageXSize = $this->imageYSize / $sourceRatio;

		$image = $this->file['tmp_name'];

		switch ($this->file['mime']) {
			case 'image/jpeg': $sourceImage = imagecreatefromjpeg($image); break;
			case 'image/gif': $sourceImage = imagecreatefromgif($image); break;
			case 'image/webp': $sourceImage = imagecreatefromwebp($image); break;
			case 'image/png': $sourceImage = imagecreatefrompng($image); break;
		}

		$targetImage = imagecreatetruecolor($this->imageXSize, $this->imageYSize);

		if ($this->file['mime'] == "image/gif" or $this->file['mime'] == "image/png") {
			imagecolortransparent($targetImage, imagecolorallocatealpha($targetImage, 0, 0, 0, 127));
			imagealphablending($targetImage, false);
			imagesavealpha($targetImage, true);
		}

		imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $this->imageXSize, $this->imageYSize, $sourceXSize, $sourceYSize);
	
		switch ($this->file['mime']) {
			case 'image/jpeg': imagejpeg($targetImage, $image); break;
			case 'image/gif': imagegif($targetImage, $image); break;
			case 'image/webp': imagewebp($targetImage, $image); break;
			case 'image/png': imagepng($targetImage, $image); break;
		}
	  
	}
}