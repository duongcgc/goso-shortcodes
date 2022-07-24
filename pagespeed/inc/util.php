<?php

namespace Authow\PageSpeed\Util;
use Authow\PageSpeed\Plugin;

/**
 * Convert an option value to array format, and clean it.
 *
 * @param string|array $value
 * @return array
 */
function option_to_array($value, $separator = "\n")
{
	$value = $value ?: [];

	if (is_string($value)) {
		$value = array_map('trim', explode($separator, $value));
	}

	if (!is_array($value)) {
		throw new \Exception('A string or array is expected.');
	}

	$value = array_filter($value);
	return $value;
}

/**
 * Get attributes given an HTML tag.
 *
 * @param string $tag
 * @return array
 */
function parse_attrs($tag)
{
	// Should be a valid html tag with attributes.	
	preg_match('#<\w+\s*([^>]*)/?>#', $tag, $match);
	if (!$match) {
		return [];
	}

	$attr_string = $match[1];

	$regex = '(?P<keys>[_a-zA-Z][-\w:.]*)'
	. '(?:'               // Attribute value.
	.     '\s*=\s*'       // All values begin with '='.
	.     '(?:'
	.         '"([^"]*)"'   // Double-quoted.
	.     '|'
	.         "'([^']*)'"   // Single-quoted.
	.     '|'
	.         '([^\s"\']+)' // Non-quoted.
	.         '(?:\s|$)'  // Must have a space.
	.     ')'
	. '|'
	.     '(?:\s|$)'      // If attribute has no value, space is required.
	. ')';

	preg_match_all('#' . $regex . '#i', $attr_string, $matches);

	$attrs = [];
	foreach ((array) $matches['keys'] as $i => $key) {

		// Any of the capturing groups for double-quoted, single-quoted or non-quoted.
		$value = $matches[2][$i] ?: $matches[3][$i] ?: $matches[4][$i];
		$attrs[$key] = $value;
	}

	return $attrs;
}

/**
 * Match an asset against a string based search.
 *
 * @param string $match        Search string.
 * @param object $asset        Asset object. 
 * @param string $search_prop  Property to search in.
 * @return boolean
 */
function asset_match($match, $asset, $search_prop = 'url')
{
	$search_prop = $search_prop ?: 'url';
	$search      = trim($match);

	// Starts with id: or url: - search in specific properties.
	if (preg_match('/^(id|url):\s*(.+?)$/', $search, $matches)) {
		$search_prop = $matches[1];
		$search      = $matches[2];
	}

	$search = preg_quote(trim($search));
	$search = str_replace('\*', '(.+?)', $search);

	return preg_match('#' . $search . '#i', $asset->{$search_prop});
}


/**
 * Check if it's a call from Elementor Page Builder preview.
 *
 * @return boolean
 */
function is_elementor($preview_check = true)
{
	// Elmentor may use AJAX to get widget configs etc.
	if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'elementor_ajax') {
		return true;
	}
	
	if (!class_exists('\Elementor\Plugin', false)) {
		return false;
	}

	if ($preview_check && isset($_REQUEST['elementor-preview'])) {
		return true;
	}

	return \Elementor\Plugin::$instance->editor->is_edit_mode();
}

function debug_log($message)
{
	if (Plugin::get_instance()->env === 'dev') {
		echo wp_kses_post($message); // phpcs:ignore WordPress.Security.EscapeOutput - Hardcoded debug output only enabled when environment is set to 'dev'.
	}
}
