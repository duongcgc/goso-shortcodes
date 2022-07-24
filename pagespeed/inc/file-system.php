<?php

namespace Authow\PageSpeed;

/**
 * Filesystem that mainly wraps the WP_File_System
 * 
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 * 
 * @mixin \WP_Filesystem_Base
 */
class FileSystem
{
	/**
	 * Pattern to remove protocol and www
	 */
	const PROTO_REMOVE_PATTERN = '#^(https?)?:?//(|www\.)#i';

	/**
	 * @var WP_Filesystem_Base
	 */
	public $filesystem;

	protected $valid_hosts;
	protected $paths_urls;

	/**
	 * Setup file system
	 */
	public function __construct()
	{
		global $wp_filesystem;

		if (empty($wp_filesystem)) {

			require_once wp_normalize_path(ABSPATH . '/wp-admin/includes/file.php');
			
			// At shutdown is usually a ob_start callback which doesn't permit calling ob_*
			if (did_action('shutdown') && ob_get_level() > 0) {
				$creds = request_filesystem_credentials('');
			}
			else {
				ob_start();
				$creds = request_filesystem_credentials('');
				ob_end_clean();
			}

			if (!$creds) {
				$creds = array();
			}

			$filesystem = WP_Filesystem($creds);
			
			if (!$filesystem) {
				
				// Fallback to lax permissions
				$upload = wp_upload_dir();
				WP_Filesystem(false, $upload['basedir'], true);
			}
		}

		$this->filesystem = $wp_filesystem;
	}

	/**
	 * Recursively make parent directories for a path.
	 * 
	 * Note: Adapted a bit from wp_mkdir_p().
	 *
	 * @param string $path
	 * @return boolean  true on success, false or failure.
	 */
	public function mkdir_p($path)
	{
		if ($this->is_dir($path)) {
			return;
		}

		$path = str_replace('//', '/', $path);

		// Safe mode safety.
		$path = rtrim($path, '/');
		$path = $path ?: '/';

		$created = $this->mkdir($path, FS_CHMOD_DIR);
		if ($created) {
			return true;
		}

		// If it failed above, try again by creating the parent first, recursively.
		$parent = dirname($path);
		if ($parent !== '/' && $this->mkdir_p($parent)) {
			return $this->mkdir($path, FS_CHMOD_DIR);
		}

		return true;
	}

	/**
	 * Get a local file by the provided URL, if possible.
	 * 
	 * @see wp_normalize_path()
	 * 
	 * @return bool|string  Either the path or false on failure.
	 */
	public function url_to_local($url)
	{
		$url = trim($url);
		
		// Not a URL, just return the path.
		if (substr($url, 0, 4) !== 'http' && substr($url, 0, 2) !== '//') {
			return $url;
		}

		$url = explode('?', trim($url));
		$url = trim($url[0]);

		// We're not working with encoded URLs.
		if (strpos($url, '%') !== false) {
            $url = urldecode($url);
		}

		// Add https:// for parse_url() or it fails.
		$url_no_proto = preg_replace(self::PROTO_REMOVE_PATTERN, '', $url);
		$url_host     = parse_url('https://' . $url_no_proto, PHP_URL_HOST);

		// Not a known host / URL.
		if (!in_array($url_host, $this->get_valid_hosts())) {
			return false;
		}

		/**
		 * Go through each known path url map and stop at first matched.
		 */
		$valid_urls   = $this->get_paths_urls();
		$url_dirname  = dirname($url_no_proto);
		$matched      = [];

		foreach ($valid_urls as $path_url) {

			if (strpos($url_dirname, untrailingslashit($path_url['url'])) !== false) {
				$matched = $path_url;
				break;
			}
		}

		// We have a matched path.
		if (!empty($matched['path'])) {
			$path = wp_normalize_path(
				$matched['path'] . str_replace($matched['url'], '', $url_dirname)
			);

			$file = trailingslashit($path) . wp_basename($url_no_proto);

			if (file_exists($file) && is_file($file) && is_readable($file)) {
				return $file;
			}
		}

		return false;
	}

	/**
	 * Get recognized hostnames for stylesheet URLs.
	 * 
	 * @return array
	 */
	public function get_valid_hosts()
	{
		if (!$this->valid_hosts) {
			$this->valid_hosts = wp_list_pluck(
				$this->get_paths_urls(),
				'host'
			);
		}

		return apply_filters('authow_pagespeed/file_system/valid_hosts', $this->valid_hosts);
	}

	/**
	 * Get a map of known path URLs, associated local path, and host.
	 * 
	 * @return array
	 */
	public function get_paths_urls()
	{
		if (!$this->paths_urls) {

			// We add https:// back for parse_url() to prevent it from failing
			$site_url  = preg_replace(self::PROTO_REMOVE_PATTERN, '', site_url());
			$site_host = parse_url('https://' . $site_url, PHP_URL_HOST);

			$content_url  = preg_replace(self::PROTO_REMOVE_PATTERN, '', content_url());
			$content_host = parse_url('https://' . $content_url, PHP_URL_HOST);

			/**
			 * This array will be processed in order it's defined to find the matching host and URL.
			 */
			$hosts = [

				// First priority to use content_host and content_url()
				'content' => [
					'url'  => $content_url, 
					'path' => WP_CONTENT_DIR,
					'host' => $content_host
				],
				
				// Fallback to using site URL with ABSPATH
				'site' => [
					'url'  => $site_url, 
					'path' => ABSPATH,
					'host' => $site_host
				],
			];

			$this->paths_urls  = apply_filters('authow_pagespeed/file_system/paths_urls', $hosts);
		}

		return $this->paths_urls;
	}

	/**
	 * Proxies to WP_Filesystem_Base
	 */
	public function __call($name, $arguments)
	{
		return call_user_func_array([$this->filesystem, $name], $arguments);
	}

}
