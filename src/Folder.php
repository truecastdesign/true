<?php

namespace True;

/**
 * Folder Management Object
 *
 * @package True
 * @author Daniel Baldwin
 * @version 1.0.0
 *
 * Use:
 *
 * To remove a directory:
 * \True\Folder::remove('/path/to/directory');
 *
 * To create a directory:
 * \True\Folder::create('/path/to/new/directory');
 *
 * To copy a directory:
 * \True\Folder::copy('/source/directory', '/destination/directory');
 *
 * To list contents of a directory:
 * $contents = \True\Folder::listContents('/path/to/directory');
 * foreach ($contents as $item) {
 *     echo $item . "\n";
 * }
 *
 * To check if a directory is empty:
 * if (\True\Folder::isEmpty('/path/to/directory')) {
 *     echo "The directory is empty.";
 * } else {
 *     echo "The directory contains files or subdirectories.";
 * }
 *
 * To create a unique directory:
 * $uniqueDir = \True\Folder::makeUnique('/base/path', 'prefix_');
 * echo $uniqueDir; // Outputs the full path of the new unique directory
 *
 * To move a directory:
 * \True\Folder::move('/source/directory', '/destination/directory');
 *
 */
class Folder
{
	/**
     * Recursively removes a directory and all of its contents.
     *
     * @param string $dir The directory path to remove. Path from root ending without a slash
     * @throws \Exception If directory removal fails.
     */
	public static function remove($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
						self::remove($dir . DIRECTORY_SEPARATOR . $object);
					} else {
							unlink($dir . DIRECTORY_SEPARATOR . $object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	/**
	 * Creates a directory recursively if it doesn't exist.
	 *
	 * @param string $path The path of the directory to create.
	 * @param int $mode The permission mode (default is 0755).
	 * @param bool $recursive Whether to create nested directories (default is true).
	 * @return bool True if the directory was created, false otherwise.
	 */
	public static function create($path, $mode = 0755, $recursive = true)
	{
		if (!is_dir($path) && !mkdir($path, $mode, $recursive) && !is_dir($path)) {
			return false;
		}
		return true;
	}

    /**
     * Copies a directory from one location to another, recursively.
     *
     * @param string $source The source directory path.
     * @param string $destination The destination directory path.
     * @throws \Exception If copying fails.
     */
	public static function copy($source, $destination)
	{
		if (!is_dir($destination)) {
			mkdir($destination, 0755, true);
		}
		
		$dir = opendir($source);
		while (($file = readdir($dir)) !== false) {
			if (($file != '.') && ($file != '..')) {
				$sourceFile = $source . DIRECTORY_SEPARATOR . $file;
				$destFile = $destination . DIRECTORY_SEPARATOR . $file;
				if (is_dir($sourceFile)) {
				self::copy($sourceFile, $destFile);
				} else {
					copy($sourceFile, $destFile);
				}
			}
		}
		closedir($dir);
	}

	/**
	 * Lists all files and directories within the given path.
	 *
	 * @param string $path The directory path to list contents from.
	 * @return array An array of file and directory names in the path.
	 * @throws \Exception If the path is not a directory.
	 */
	public static function listContents($path)
	{
		if (!is_dir($path)) {
			throw new \Exception("The path '$path' is not a directory.");
		}
		return array_diff(scandir($path), array('..', '.'));
	}

	/**
	 * Checks if the given path is empty (no files or subdirectories).
	 *
	 * @param string $path The directory path to check.
	 * @return bool True if the directory is empty, false otherwise.
	 */
	public static function isEmpty($path)
	{
		return (new \FilesystemIterator($path))->valid() === false;
	}

	/**
	 * Generates a unique directory name within the given path.
	 *
	 * @param string $path The base path where to create the unique directory.
	 * @param string $prefix Optional prefix for the directory name.
	 * @return string The full path of the new unique directory.
	 */
	public static function makeUnique($path, $prefix = '')
	{
		$uniquePath = $path . DIRECTORY_SEPARATOR . $prefix . uniqid();
		while (is_dir($uniquePath)) {
			$uniquePath = $path . DIRECTORY_SEPARATOR . $prefix . uniqid();
		}
		mkdir($uniquePath, 0755);
		return $uniquePath;
	}

	/**
	 * Moves a directory from one location to another.
	 *
	 * @param string $source The source directory path.
	 * @param string $destination The destination directory path.
	 * @throws \Exception If moving fails.
	 */
	public static function move($source, $destination)
	{
		if (!rename($source, $destination)) {
			throw new \Exception("Failed to move directory from '$source' to '$destination'.");
		}
	}
}