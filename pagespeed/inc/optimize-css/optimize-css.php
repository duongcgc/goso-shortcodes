<?php

namespace Authow\PageSpeed;

use Authow\PageSpeed\OptimizeCss\Stylesheet;

/**
 * Process stylesheets to authow_pagespeed and optimize CSS.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class OptimizeCss {
	/**
	 * @var \DOMDocument
	 */
	protected $dom;
	protected $html;

	/**
	 * @var array
	 */
	protected $stylesheets;

	/**
	 * @var array
	 */
	protected $exclude_sheets;

	public function __construct( \DOMDocument $dom, string $raw_html ) {
		$this->dom  = $dom;
		$this->html = $raw_html;
	}

	public function process() {
		$this->find_stylesheets();

		// Setup optimization excludes.

		$default_excludes = [
			'id:woocommerce-smallscreen'
		];

		$excludes = array_merge( $default_excludes, Util\option_to_array( get_theme_mod( 'goso_speed_optimize_css_excludes' ) ) );


		$this->exclude_sheets = apply_filters( 'authow_pagespeed/optimize_css_excludes', $excludes );

		// Remove CSS first, if any.
		if ( $this->should_remove_css() ) {
			$remove_css = new RemoveCss( $this->stylesheets, $this->dom, $this->html );
			$this->html = $remove_css->process();
		}

		/**
		 * Process and replace stylesheets with CSS cleaned.
		 */
		$has_gfonts   = false;
		$replacements = [];

		/** @var Stylesheet $sheet */
		foreach ( $this->stylesheets as $sheet ) {
			$replacement = '';

			// Optimizations such as min and inline.
			$this->optimize( $sheet );

			if ( get_theme_mod( 'goso_speed_optimize_gfonts' ) && $sheet->is_google_fonts() ) {
				$replacement = Plugin::google_fonts()->do_render( $sheet );
				$has_gfonts  = true;
			}

			if ( ! $replacement ) {
				// Get the rendered stylesheet to replace original with.
				$replacement = $sheet->render();
			}

			if ( $replacement ) {
				// Util\debug_log('Replacing: ' . print_r($sheet, true));
				$replacements[ $sheet->orig_url ] = $replacement;

				if ( $sheet->has_delay() ) {

					// onload and preload type doesn't need prefetch; both use a non-JS method.
					if ( ! in_array( $sheet->delay_type, [ 'onload', 'preload' ] ) ) {
						Plugin::delay_load()->add_preload( $sheet );
					}
				}
			}

			// Found Google Fonts.
			if ( $sheet->is_google_fonts() ) {
				$has_gfonts = true;
			}

			// Free up memory.
			$sheet->content     = null;
			$sheet->parsed_data = null;
		}

		/**
		 * Make stylesheet replacements, if any. Slightly more efficient in one go.
		 */
		if ( $replacements ) {
			$urls = array_map( 'preg_quote', array_keys( $replacements ) );

			// Using callback to prevent issues with backreferences such as $1 or \0 in replacement string.
			$this->html = preg_replace_callback( '#<link[^>]*href=(?:"|\'|)(' . implode( '|', $urls ) . ')(?:"|\'|\s)[^>]*>#Usi', function ( $match ) use ( $replacements ) {
				if ( ! empty( $replacements[ $match[1] ] ) ) {
					return $replacements[ $match[1] ];
				}

				return $match[0];
			}, $this->html );
		}

		if ( $has_gfonts ) {
			Plugin::google_fonts()->enable();
			$this->html = Plugin::google_fonts()->render_in_dom( $this->html );
		}

		return $this->html;
	}

	/**
	 * Find all the stylesheet links.
	 *
	 * @return Stylesheet[]
	 */
	public function find_stylesheets() {
		$stylesheets = [];

		$nodes = $this->dom->xpath->query( "//link[@rel='stylesheet']" );
		foreach ( $nodes as $node ) {
			/** @var \DOMElement $node */
			$elements[] = $node;

			if ( ! $node->hasAttributes() ) {
				continue;
			}

			$url = $node->getAttribute( 'href' );
			if ( ! $url ) {
				continue;
			}

			$sheet        = new Stylesheet( $node->getAttribute( 'id' ), $url );
			$sheet->media = $node->getAttribute( 'media' );

			$stylesheets[] = $sheet;
		}

		$this->stylesheets = $stylesheets;

		return $this->stylesheets;
	}

	/**
	 * Should unused CSS be removed.
	 *
	 * @return boolean
	 */
	public function should_remove_css() {
		// check post type + page remove here
		$valid = get_theme_mod( 'goso_speed_remove_css' );

		return apply_filters( 'authow_pagespeed/should_remove_css', $valid );
	}

	/**
	 * Apply CSS optimizes such as minify and inline, if enabled.
	 *
	 * @param Stylesheet $sheet
	 *
	 * @return void
	 */
	public function optimize( Stylesheet $sheet ) {
		if ( ! $this->should_optimize( $sheet ) ) {
			return;
		}

		// We're going to use onload delay method (non-JS) fixing render blocking.
		$sheet->delay_type = 'delay';
		$sheet->set_render( 'delay' );

		// Should the sheet be minified.
		$minify       = get_theme_mod( 'goso_speed_optimize_css_minify' );
		$delay_gfonts = get_theme_mod( 'goso_speed_optimize_gfonts_delay' );

		// For inline CSS, minification is enforced.
		if ( get_theme_mod( 'goso_speed_optimize_css_to_inline' ) ) {
			$minify = true;
		}

		// Will optimize if it's a google font. Note: Has to be done before minify.
		Plugin::google_fonts()->optimize( $sheet );

		// Google Fonts inline also relies on minification.
		if ( $sheet->is_google_fonts() && get_theme_mod( 'goso_speed_optimize_gfonts_inline' ) && ! $delay_gfonts ) {
			$minify = true;
		}

		$disable_icon_delay = get_theme_mod( 'goso_speed_optimize_disable_icon_delay' );


		// Theme Icon Font
		if ( $this->delay_all_fonts( $sheet ) && ! $disable_icon_delay ) {
			$sheet->set_render( 'delay' );
			$minify = false;
		} elseif ( $this->delay_all_fonts( $sheet ) && $disable_icon_delay ) {
			$sheet->set_render( 'inline' );
			$minify = true;
		}

		if ( $minify ) {
			$minifier = new Minifier( $sheet );
			$minifier->process();
		}

		if ( get_theme_mod( 'goso_speed_optimize_css_to_inline' ) && ! $this->delay_all_fonts( $sheet ) && ! $delay_gfonts ) {
			if ( ! $sheet->content ) {
				$file = Plugin::file_system()->url_to_local( $sheet->get_content_url() );

				if ( $file ) {
					$sheet->content = Plugin::file_system()->get_contents( $file );
				}
			}

			// If we have content by now.
			if ( $sheet->content ) {
				$sheet->set_render( 'delay' );
			}
		}
	}

	/**
	 * Determine if stylesheet should be optimized, based on exclusion and inclusion
	 * rules and settings.
	 *
	 * @param Stylesheet $sheet
	 *
	 * @return boolean
	 */
	public function should_optimize( Stylesheet $sheet ) {
		// Only go ahead if optimizations are enabled and remove css hasn't happened.
		if ( ! get_theme_mod( 'goso_speed_optimize_css' ) || $sheet->render_type === 'remove_css' ) {
			return false;
		}

		// Debugging scripts. Can't be minified, so can't be inline either.
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return;
		}

		// Handle manual excludes first.
		if ( $this->exclude_sheets ) {
			foreach ( $this->exclude_sheets as $exclude ) {
				if ( Util\asset_match( $exclude, $sheet ) ) {
					return false;
				}
			}
		}

		return true;
	}

	public function delay_all_fonts( Stylesheet $sheet ) {
		// Only go ahead if optimizations are enabled and remove css hasn't happened.
		if ( ! get_theme_mod( 'goso_speed_optimize_css' ) || $sheet->render_type === 'remove_css' ) {
			return false;
		}

		$fonts = [
			'id:goso_icon',
			'id:goso-font-iweather',
			'id:goso-font-awesome',
			'id:goso-font-awesomeold',
			'id:elementor-icons-*',
		];

		foreach ( $fonts as $font ) {
			if ( Util\asset_match( $font, $sheet ) ) {
				return true;
			}
		}

		return false;
	}
}
