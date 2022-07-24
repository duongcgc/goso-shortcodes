<?php

namespace Authow\PageSpeed\Base;

use Authow\PageSpeed\Plugin;

/**
 * Base class for scripts and stylesheets.
 * 
 * @uses Plugin::file_system()
 * 
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
abstract class Asset
{
	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $orig_url;
	public $url;
	public $minified_url;

	/**
	 * Whether the asset is local or at a remote URL.
	 * Note: Defaults to null for lazy init.
	 *
	 * @var boolean
	 */
	private $is_remote = null;

	/**
	 * Return URL to get content from. Minified URL if present.
	 *
	 * @return void
	 */
	public function get_content_url()
	{
		return $this->minified_url ?: $this->url;
	}

	/**
	 * Render attributes using key values.
	 *
	 * @param array $orig_attrs Key value pair of attributes.
	 * @param array $safe       List of attributes pre-escaped.
	 * @return array Array of attributes.
	 */
	public function render_attrs($orig_attrs, $safe = [])
	{
		$attrs = [];
		foreach ($orig_attrs as $key => $value) {

			// For true, no value is needed in HTML.
			if ($value === true) {
				$attrs[] = $key;
				continue;
			}

			// Adding previously escaped, but escape again just in case it was done using 
			// different quotes originally.
			$value = !in_array($key, $safe) ? esc_attr($value) : $value;
			$attrs[] = sprintf('%s="%s"', $key, $value);
		}

		return $attrs;
    }

	/**
	 * Whether the asset is on a remote location.
	 *
	 * @return boolean
	 */
	public function is_remote()
	{
		if ($this->is_remote || $this->minified_url) {
			return true;
		}

		if ($this->is_remote === null && !Plugin::file_system()->url_to_local($this->url)) {
			$this->is_remote = true;
		}

		return $this->is_remote;
	}
}
