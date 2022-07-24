<?php

namespace Authow\PageSpeed;

/**
 * Autoloader for loading classes off defined namespaces or a class map.
 * 
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class Autoloader
{
	public $class_map;
	public $namespaces = [];

	public function __construct($namespaces = null, $prepend = false)
	{
		if (is_array($namespaces)) {
			$this->namespaces = $namespaces;
		}

		spl_autoload_register(array($this, 'load'), true, $prepend);
	}

	/**
	 * Autoloader the class either using a class map or via conversion of 
	 * class name to file.
	 * 
	 * @param string $class
	 */
	public function load($class)
	{
		if (isset($this->class_map[$class])) {
			$file = $this->class_map[$class];
		}
		else {
			foreach ($this->namespaces as $namespace => $dir) {
				if (strpos($class, $namespace) !== false) {
					$file = $this->get_file_path($class, $namespace, $dir);
					break;	
				}
			}
		}

		if (!empty($file)) {
			require_once $file;
		}
	}

	/**
	 * Get file path to include.
	 * 
	 * Examples:
	 * 
	 *  Bunyad_Theme_Foo_Bar to inc/foo/bar/bar.php (fallback to inc/foo/bar.php)
	 *  Bunyad\Blocks\FooBar to blocks/foo-bar/foo-bar.php (fallback to inc/foo-bar.php)
	 * 
	 * @return string  Relative path to the file from the theme dir
	 */
	public function get_file_path($class, $prefix = '', $path = '') 
	{
		// Remove namespace and convert underscore as a namespace delim.
		$class = str_replace($prefix, '', $class);
		$class = str_replace('_', '\\', $class);
		
		// Split to convert CamelCase.
		$parts = explode('\\', $class);
		foreach ($parts as $key => $part) {
			
			$test = substr($part, 1); 
					
			// Convert CamelCase to Camel-Case
			if (strtolower($test) !== $test) {
				$part = preg_replace('/(.)(?=[A-Z])/u', '$1-', $part);
			}

			$parts[$key] = $part;
		}

		$name = strtolower(array_pop($parts));
		$path = $path . '/' . strtolower(
			implode('/', $parts)
		);
		$path = trailingslashit($path);

		// Preferred and fallback file path.
		$pref_file = $path . "{$name}/{$name}.php";
		$alt_file  = $path . "{$name}.php";
		
		// Try with directory path pattern first.
		if (file_exists($pref_file)) {
			return $pref_file;
		}
		else if (file_exists($alt_file)) {
			return $alt_file;
		}
	}
}
