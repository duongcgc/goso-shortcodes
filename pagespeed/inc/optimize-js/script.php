<?php

namespace Authow\PageSpeed\OptimizeJs;

use Authow\PageSpeed\Base\Asset;
use Authow\PageSpeed\Util;

/**
 * Class for handling scripts.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class Script extends Asset {
	public $defer = false;
	public $async = false;
	public $delay = false;

	/**
	 * Dependencies to mirror from core script.
	 *
	 * @var array
	 */
	public $deps = [];

	/**
	 * Inner content for inline scripts
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * @var array
	 */
	public $attrs = [];

	/**
	 * Original HTML tag.
	 */
	public $orig_html = '';

	/**
	 * Whether the script is dirty and needs rendering.
	 *
	 * @var boolean
	 */
	public $render = false;

	/**
	 * @var callable|null
	 */
	protected $minifier;

	public function __construct( string $id, string $url = '', string $orig_html = '' ) {
		$this->id        = $id;
		$this->url       = $url;
		$this->orig_url  = $url;
		$this->orig_html = $orig_html;

		if ( ! $this->id ) {
			$this->id = md5( $this->url ?: uniqid( 'authow_pagespeed_script', true ) );
		}

		// Has to be processed early as it might have to be searched for things like defer.
		$this->process_content();
	}

	/**
	 * Set the inner content, if any.
	 *
	 * @return void
	 */
	public function process_content() {
		if ( ! $this->url ) {
			$this->content = preg_replace( '#<script[^>]*>(.+?)</script>\s*$#is', '\\1', $this->orig_html );
		}
	}

	/**
	 * Factory method: Create an instance provided an HTML tag.
	 *
	 * @param string $tag
	 *
	 * @return boolean|self
	 */
	public static function from_tag( string $tag ) {
		$attrs = Util\parse_attrs( $tag );

		// Only process scripts of type javascript or missing type (assumed JS by browser).
		if ( isset( $attrs['type'] ) && strpos( $attrs['type'], 'javascript' ) === false ) {
			return false;
		}

		$script = new self( $attrs['id'] ?? '', $attrs['src'] ?? '', $tag );

		// Note: We keep 'id' just in case if there was an original id.
		$script->attrs = \array_diff_key( $attrs, array_flip( [ 'src', 'defer', 'async' ] ) );

		if ( isset( $attrs['defer'] ) ) {
			$script->defer = true;
		}

		if ( isset( $attrs['async'] ) ) {
			$script->async = true;
		}

		return $script;
	}

	/**
	 * Render the HTML.
	 *
	 * @return string
	 */
	public function render() {
		// Add src if available.
		if ( $this->url ) {
			$this->attrs += [
				'src' => $this->get_content_url(),
				'id'  => $this->id,
			];
		}

		$this->process_delay();

		// Setting local to avoid mutating.
		$content = $this->content;

		if ( ! $this->url && $content && is_callable( $this->minifier ) ) {
			$content = call_user_func( $this->minifier, $content );
		}

		// Add defer for external scripts. For inline scripts, use data uri in src.
		if ( $this->defer ) {
			$this->attrs['defer'] = true;

			if ( ! $this->url ) {
				$this->attrs['src'] = 'data:text/javascript;base64,' . base64_encode( $content );
				$content            = '';
			}
		}

		if ( $this->async ) {
			$this->attrs['async'] = true;
		}

		$render_atts = implode( ' ', $this->render_attrs( $this->attrs ) );
		$new_tag     = sprintf(
			'<script%1$s>%2$s</script>',
			$render_atts ? " $render_atts" : '',
			! $this->url ? $content : ''
		);

		return $new_tag;
	}

	/**
	 * Process and add delayed script attributes if needed.
	 *
	 * @return void
	 */
	public function process_delay() {
		if ( ! $this->delay ) {
			return;
		}

		// Defer / async shouldn't be enabled anymore. Delay load logic implies deferred anyways.
		$this->defer = false;
		$this->async = false;

		// Normal script with a URL.
		if ( ! empty( $this->attrs['type'] ) ) {
			$this->attrs['data-gosolazy-type'] = $this->attrs['type'];
		}

		$this->attrs['type'] = 'GosoLazyScript';
	}

	/**
	 * Set a callable minifier function.
	 *
	 * @param callable $minifier
	 *
	 * @return void
	 */
	public function set_minifier( $minifier ) {
		$this->minifier = $minifier;
	}

}
