<?php

namespace Authow\PageSpeed\OptimizeCss;

use Authow\PageSpeed\Base\Asset;

/**
 * Value object for stylehseets.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class Stylesheet extends Asset {
	public $has_cache = false;

	/**
	 * @var string
	 */
	public $file;

	/**
	 * CSS content.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * @var array
	 */
	public $parsed_data;

	/**
	 * @var string
	 */
	public $render_type = '';
	public $delay_type = 'onload'; //interact

	/**
	 * Whether delay load exists. Some sheets may have a different render_type but
	 * may still need to be delayed in addition.
	 *
	 * @var boolean
	 */
	public $has_delay = false;

	/**
	 * Media type for this stylesheet.
	 *
	 * @var string
	 */
	public $media;

	/**
	 * Pre-rendered content
	 *
	 * @var string
	 */
	public $render_string;

	/**
	 * Whether the stylesheet is a google fonts link.
	 *
	 * @var boolean
	 */
	protected $is_google_fonts = false;

	public function __construct( string $id, string $url ) {
		$this->id       = $id;
		$this->url      = $url;
		$this->orig_url = $url;

		if ( ! $this->id ) {
			$this->id = md5( $this->url );
		}

		$this->is_google_fonts = stripos( $this->url, 'fonts.googleapis.com/css' ) !== false;
	}

	public function render() {
		if ( $this->render_string ) {
			return $this->render_string;
		}

		$attrs = [
			'rel' => 'stylesheet',
			'id'  => $this->id,
		];

		if ( $this->media ) {
			$attrs['media'] = $this->media;
		}

		/**
		 * 1. Render type 'delay' at onload or delay via JS for later.
		 */
		if ( $this->render_type === 'delay' ) {
			if ( $this->media === 'print' ) {
				return '';
			}

			$attrs += [
				'data-authow_pagespeed-delay' => true,
				'data-href'                    => $this->get_content_url()
			];

			return sprintf(
				'<link %1$s/>',
				implode( ' ', $this->render_attrs( $attrs, [ 'onload' ] ) )
			);
		}

		/**
		 * 2. Render type 'inline' when the CSS has to be inlined.
		 */
		if ( $this->render_type === 'inline' ) {
			if ( ! $this->content ) {
				return '';
			}

			return sprintf(
				'<style %1$s>%2$s</style>',
				implode( ' ', $this->render_attrs( $attrs ) ),
				$this->content
			);
		}

		/**
		 * 3. Normal render. Usually for stylesheets that just have to be minified.
		 */
		if ( $this->render_type === 'normal' ) {
			$attrs += [
				'href' => $this->get_content_url()
			];

			return sprintf(
				'<link %1$s/>',
				implode( ' ', $this->render_attrs( $attrs ) )
			);
		}

		// Default to nothing.
		return '';
	}

	public function set_render( $type, $render_string = '' ) {
		$this->render_type = $type;

		if ( $render_string ) {
			$this->render_string = $render_string;
		}
	}

	/**
	 * Whether or not stylesheet is delayed or has a delayed factor to it (such as
	 * remove_css + delay combo).
	 *
	 * @return boolean
	 */
	public function has_delay() {
		return $this->has_delay || $this->render_type === 'delay';
	}

	public function convert_urls() {
		// Original base URL.
		$base_url = preg_replace( '#[^/]+\?.*$#', '', $this->url );

		// Borrowed and modified from MatthiasMullie\Minify\CSS.
		$regex = '/
			# open url()
			url\(
				\s*

				# open path enclosure
				(?P<quotes>["\'])?

					# fetch path
					(?P<path>.+?)

				# close path enclosure, conditional
				(?(quotes)(?P=quotes))

				\s*
			# close url()
			\)
		/ix';

		preg_match_all( $regex, $this->content, $matches, PREG_SET_ORDER );
		if ( ! $matches ) {
			return;
		}

		foreach ( $matches as $match ) {
			$url = trim( $match['path'] );

			if ( substr( $url, 0, 5 ) === 'data:' ) {
				continue;
			}

			$parsed_url = parse_url( $url );

			// Skip known host and protocol-relative paths.
			if ( ! empty( $parsed_url['host'] ) || empty( $parsed_url['path'] ) || $parsed_url['path'][0] === '/' ) {
				continue;
			}

			$new_url = $base_url . $url;

			// URLs with quotes, #, brackets or characters above 0x7e should be quoted.
			// Restore original quotes.
			// Ref: https://developer.mozilla.org/en-US/docs/Web/CSS/url()#syntax
			if ( preg_match( '/[\s\)\'"#\x{7f}-\x{9f}]/u', $new_url ) ) {
				$new_url = $match['quotes'] . $new_url . $match['quotes'];
			}

			$new_url       = 'url(' . $new_url . ')';
			$this->content = str_replace( $match[0], $new_url, $this->content );
		}
	}

	/**
	 * Whether the style is a google fonts URL.
	 *
	 * @return boolean
	 */
	public function is_google_fonts() {
		return $this->is_google_fonts;
	}
}
