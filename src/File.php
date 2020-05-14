<?php
namespace True;

/**
 * Uploaded File Object
 *
 * @package Truecast
 * @author Daniel Baldwin
 * @version 1.0.0
 * 
 * Use:
 * 
 * if ($request->files->fieldName->uploaded) {
 * 	$request->files->fieldName->move(BP.'/assets/', $request->files->fieldName->name);
 * 	echo $request->files->fieldName->ext; // jpg
 * 	echo $request->files->fieldName->mime; // image/jpeg
 * }
 * 
 */

class File
{
	var $file = null;

	public function __construct($file)
	{
		$this->file = $file;
		$this->file['uploaded'] = ($file['error']==0? true:false);
		if ($this->file['uploaded'] and !empty($this->file['tmp_name'])) {
			$this->file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
			$this->file['mime'] = mime_content_type($file['tmp_name']);
		}		
	}

	public function __get($value)
	{
		return $this->file[$value];
	}

	public function move($path, $filename)
	{
		if (@copy($this->file['tmp_name'], $path.$filename)) {
			$success = true;
		} else {
			$success = false;
		}
	}
}