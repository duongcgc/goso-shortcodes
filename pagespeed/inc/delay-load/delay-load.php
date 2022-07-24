<?php

namespace Authow\PageSpeed;

use Authow\PageSpeed\OptimizeCss\Stylesheet;

/**
 * Delay load assets singleton.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class DelayLoad {
	public $enabled = true;
	public $use_js = false;
	public $js_type = 'inline';

	public $preload = [];

	/**
	 * Note: Should be used a singleton.
	 */
	public function __construct() {
		// nothing here
	}

	/**
	 * Enable injection of required scripts and preloads, as needed.
	 *
	 * @param null|true $use_js Whether to inject delay/defer JS. null keeps the current.
	 *
	 * @return void
	 */
	public function enable( $use_js = null ) {
		$this->enabled = true;

		if ( $use_js === true ) {
			$this->use_js = $use_js;
		}
	}

	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Enqueue a preload.
	 *
	 * @param Stylesheet $asset
	 *
	 * @return void
	 */
	public function add_preload( Stylesheet $asset ) {
		$this->preload[] = $asset;
	}

	/**
	 * Add extras to the provided HTML buffer.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function render( $html ) {
		if ( ! $this->enabled ) {
			return $html;
		}

		$js_head   = $this->render_js( 'header' );
		$js_footer = $this->render_js( 'footer' );

		$preloads = $this->render_preloads();

		$append   = $preloads;
		$footerjs = $js_head . $js_footer;

		// Add to header or at the start head.
		// Note: Has to be at the end to ensure all <script> tags with defer has been added.
		if ( strpos( $html, '</head>' ) !== false ) {
			$html = str_replace( '</head>', $append . "\n</head>", $html );
		} else {
			$html .= $append;
		}

		// Add to body or at the end.
		// Note: Has to be at the end to ensure all <script> tags with defer has been added.
		if ( strpos( $html, '</body>' ) !== false ) {
			$html = str_replace( '</body>', $footerjs . "\n</body>", $html );
		} else {
			$html .= $footerjs;
		}


		return $html;
	}

	/**
	 * Get JS enqueues and inline scripts as needed.
	 *
	 * @return string
	 */
	protected function render_js( $data = 'header' ) {

		$js         = '';
		$js_content = '';
		$file       = [];

		if ( 'header' == $data && ! get_theme_mod( 'goso_speed_disable_first_screen', false ) ) {
			$file[] = get_template_directory() . '/js/goso-lazy.js';
		}

		if ( 'footer' == $data ) {

			if ( $this->use_js && get_theme_mod( 'goso_speed_delay_js' ) ) {
				$file[] = Plugin::get_instance()->dir_path . 'inc/delay-load/js/first-touch.js';
				$file[] = Plugin::get_instance()->dir_path . 'inc/delay-load/js/lazy-load.min.js';
			}

			if ( $this->should_add_delay_css() ) {
				$file[] = Plugin::get_instance()->dir_path . 'inc/delay-load/js/lazy-load-css.min.js';
			}

		}

		// get is_content

		if ( ! empty( $file ) ) {
			foreach ( $file as $f ) {
				$js_content .= Plugin::file_system()->get_contents( $f );
			}
		}

		// Delay load JS comes after, just in case, to not mess up readyState.
		if ( $this->js_type === 'inline' && $js_content ) {
			$js .= sprintf( '<script type=\'text/javascript\' id="authow-pagespeed-' . $data . '" data-cfasync="false">%s</script>', $js_content );

		}

		if ( ! $js ) {
			return '';
		}

		return $js;
	}

	public function should_add_delay_css() {
		$render = false;
		if ( get_theme_mod( 'goso_speed_optimize_css' ) || get_theme_mod( 'goso_speed_remove_css' ) ) {
			$render = true;
		}

		return $render;
	}

	protected function render_preloads() {
		$preloads = [];
		foreach ( $this->preload as $preload ) {
			$media = 'all';
			$type  = ( $preload instanceof Stylesheet ) ? 'style' : 'script';

			if ( $type === 'style' ) {
				$media = $preload->media ?: $media;
			}

			$preloads[] = sprintf( '<link rel="prefetch" href="%1$s" as="%2$s" media="%3$s" />', esc_url( $preload->get_content_url() ), $type, esc_attr( $media ) );
		}

		return implode( '', $preloads );
	}

	/**
	 * Check for conditionals for delay load.
	 *
	 * @return boolean
	 */
	public function should_delay_css() {

		$valid = Plugin::process()->check_enabled( [ 'all' ] );

		return apply_filters( 'authow_pagespeed/should_delay_css', $valid );
	}
}
