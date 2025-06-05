<?php

namespace True;

/**
 * Folder Management Object
 *
 * Provides static utility methods for common directory management operations,
 * including creation, deletion, copying, moving, and advanced listing (with wildcard support).
 *
 * @package True
 * @author Daniel Baldwin
 * @version 1.1.0
 *
 * ## Usage Examples
 *
 * // Remove a directory (recursively deletes all contents)
 * \True\Folder::remove('/path/to/directory');
 *
 * // Create a directory (including parent directories if needed)
 * \True\Folder::create('/path/to/new/directory');
 *
 * // Copy a directory and all of its contents to a new location
 * \True\Folder::copy('/source/directory', '/destination/directory');
 *
 * // List all files and directories
 * $all = \True\Folder::listContents('/path/to/dir');
 *
 * // List only .json files
 * $json = \True\Folder::listContents('/path/to/dir/*.json');
 *
 * // List only .json and .txt files
 * $jsonAndTxt = \True\Folder::listContents('/path/to/dir/*.{json,txt}');
 *
 * // List all subdirectories
 * $dirs = \True\Folder::listContents('/path/to/dir/*');
 *
 * // Check if a directory is empty
 * if (\True\Folder::isEmpty('/path/to/directory')) {
 *     echo "The directory is empty.";
 * } else {
 *     echo "The directory contains files or subdirectories.";
 * }
 *
 * // Create a unique subdirectory with a prefix
 * $uniqueDir = \True\Folder::makeUnique('/base/path', 'prefix_');
 * echo $uniqueDir; // Outputs full path of the new unique directory
 *
 * // Move (rename) a directory to a new location
 * \True\Folder::move('/source/directory', '/destination/directory');
 *
 * @method static void    remove(string $dir)
 * @method static bool    create(string $path, int $mode = 0755, bool $recursive = true)
 * @method static void    copy(string $source, string $destination)
 * @method static array   listContents(string $path, string $pattern = null)
 * @method static bool    isEmpty(string $path)
 * @method static string  makeUnique(string $path, string $prefix = '')
 * @method static void    move(string $source, string $destination)
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
	 * Lists files and directories within the given path, supporting wildcards.
	 *
	 * @param string $pattern The directory path, or path with glob pattern (e.g., '/path/*.json' or '/path/*.{json,txt}').
	 * @return array An array of file/directory names matching the pattern.
	 * @throws \Exception If the base path is not a directory.
	 *
	 * Examples:
	 *   \True\Folder::listContents('/path/to/dir');           // List all
	 *   \True\Folder::listContents('/path/to/dir/*.json');    // Only .json
	 *   \True\Folder::listContents('/path/to/dir/*.{json,txt}'); // Multiple types
	 */
	public static function listContents($pattern)
	{
		// If just a directory, list all
		if (is_dir($pattern)) {
			$pattern = rtrim($pattern, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*';
		}

		// Auto-apply GLOB_BRACE if pattern uses curly brace extension sets
		$flags = 0;
		if (strpos($pattern, '{') !== false && strpos($pattern, '}') !== false) {
			$flags |= defined('GLOB_BRACE') ? GLOB_BRACE : 0;
		}

		$matches = glob($pattern, $flags);
		if ($matches === false) {
			throw new \Exception("Unable to read directory contents with pattern '$pattern'.");
		}

		return array_map('basename', $matches);
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