<?php

namespace Authow\PageSpeed;

use Authow\PageSpeed\OptimizeJs\Script;

/**
 * JS Optimizations such as defer and delay.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class OptimizeJs {
	protected $html;

	/**
	 * @var array
	 */
	protected $scripts;

	/**
	 * Include and exclude scripts for "delay".
	 *
	 * @var array
	 */
	protected $include_scripts = [];
	protected $exclude_scripts = [];


	/**
	 * Scripts already delayed or deffered.
	 */
	protected $done_delay = [];
	protected $done_defer = [];

	/**
	 * Scripts registered data from core.
	 */
	protected $enqueues = [];

	public function __construct( string $html ) {
		$this->html = $html;
	}

	public function process() {
		$this->find_scripts();

		// Figure out valid scripts.
		$this->setup_valid_scripts();

		do_action( 'authow_pagespeed/optimize_js_begin', $this );

		/**
		 * Setup scripts defer and delays and render the replacements.
		 */
		$has_delayed = false;
		$script_array = $this->scripts;
		if ( is_array( $script_array ) || is_object( $script_array ) ){
			foreach ( $script_array as $script ) {

				// Has to be done for all scripts.
				if ( get_theme_mod( 'goso_speed_minify_js' ) ) {
					$this->minify_js( $script );
					$script->render = true;
				}

				// Should this script be delayed.
				if ( $this->should_delay_script( $script ) ) {
					$script->delay  = true;
					$script->render = true;

					$has_delayed = true;
				} else {
					Util\debug_log( 'Skipping: ' . print_r( $script, true ) );
				}

				if ( $script->render ) {
					$this->html = str_replace( $script->orig_html, $script->render(), $this->html );
				}
			}
		}

		if ( $has_delayed ) {
			Plugin::delay_load()->enable( true );
		}

		return $this->html;
	}

	public function find_scripts() {
		/**
		 * Collect all valid enqueues.
		 */
		$all_scripts = [];
		foreach ( wp_scripts()->registered as $handle => $script ) {

			// Not an enqueued script, ignore.
			if ( ! in_array( $handle, wp_scripts()->done ) ) {
				continue;
			}

			// Don't mutate original.
			$script = clone $script;

			$script->deps = array_map( function ( $id ) {
				// jQuery JS should be mapped to core. Add -js suffix for others.
				return $id === 'jquery' ? 'jquery-core-js' : $id . '-js';
			}, $script->deps );

			// Add -js prefix to match the IDs that will retrieved from attrs.
			$handle                 .= '-js';
			$all_scripts[ $handle ] = $script;

			// Pseudo entry for extras, adding a dependency.
			if ( isset( $script->extra['after'] ) ) {
				$all_scripts[ $handle . '-after' ] = (object) [
					'deps' => [ $handle ]
				];
			}
		}

		$this->enqueues = $all_scripts;

		/**
		 * Find all scripts.
		 */
		if ( ! preg_match_all( '#<script.*?</script>#si', $this->html, $matches ) ) {
			return;
		}

		foreach ( $matches[0] as $script ) {
			$script = Script::from_tag( $script );
			if ( $script ) {
				$script->deps                 = $this->enqueues[ $script->id ]->deps ?? [];
				$this->scripts[ $script->id ] = $script;

				// Inline script has jQuery content? Ensure dependency.
				if (
					! $script->url &&
					in_array( 'jquery-core-js', $script->deps ) &&
					strpos( $script->content, 'jQuery' ) !== false
				) {
					$script->deps[] = 'jquery-core-js';
				}
			}
		}
	}

	/**
	 * Setup includes and excludes based on options.
	 *
	 * @return void
	 */
	public function setup_valid_scripts() {
		// Used by both delay and defer.
		$shared_excludes = [];

		/**
		 * Delayed load scripts.
		 */
		$excludes   = Util\option_to_array( get_theme_mod( 'goso_speed_delay_js_excludes' ) );
		$default_ex = [
			'analytics.js',
			'google-analytics',
			'googletagmanager',
			'analytics',
			'gtag',
			'jetpack',
			// Jetpack stats.
			'url://stats.wp.com',
			'_stq.push',

			// WPML browser redirect.
			'browser-redirect/app.js',

			// WordPress jQuery
			//'jquery-core',
			//'jquery-migrate',

			// Skip -js-extra as it's  global variables and localization that shouldn't be delayed.
			'id:-js-extra',

			//Extra
			//'/wp-includes/js/wp-embed.min.js',
			'et_core_page_resource_fallback',
			'window.\$us === undefined',
			'js-extra',
			'fusionNavIsCollapsed',
			'eio_lazy_vars',
			'et_animation_data',
			'wpforms_settings',
			'var nfForms',
			'//stats.wp.com', // Jetpack Stats.
			'_stq.push', // Jetpack Stats.
			'fluent_form_ff_form_instance_', // Fluent Forms.
			'cpLoadCSS', // Convert Pro.
			'ninja_column_', // Ninja Tables.
			'var rbs_gallery_', // Robo Gallery.
			'var lepopup_', // Green Popup.
			'var billing_additional_field', // Woo Autocomplete Nish.
			'var gtm4wp',
			'var dataLayer_content',
			'/ewww-image-optimizer/includes/load[_-]webp(\.min)?.js', // EWWW WebP rewrite external script.
			'/ewww-image-optimizer/includes/check-webp(\.min)?.js', // EWWW WebP check external script.
			'ewww_webp_supported', // EWWW WebP inline scripts.
			'/dist/js/browser-redirect/app.js', // WPML browser redirect script.
			'/perfmatters/js/lazyload.min.js',
			'scripts.mediavine.com/tags/', // allows mediavine-video schema to be accessible by search engines.
			'initCubePortfolio', // Cube Portfolio show images.
			'jetpack-lazy-images-js-enabled',  // Jetpack Boost plugin lazyload.
			'jetpack-boost-critical-css', // Jetpack Boost plugin critical CSS.
		];

		$default = [
			'document\.body\.classList\.remove\("no-js"\)',
			'document\.documentElement\.className\.replace\( \'no-js\', \'js\' \)',
		];

		if ( get_theme_mod( 'goso_speed_delay_js_excludes' ) == '' || ! get_theme_mod( 'goso_speed_delay_js_excludes' ) ) {
			$default = array_merge( $default_ex, $default );
		}

		$excludes = array_merge( $excludes, $shared_excludes, $default );

		$this->exclude_scripts = apply_filters( 'authow_pagespeed/delay_js_excludes', $excludes );

		// Enable delay adsense.
		if ( get_theme_mod( 'goso_speed_delay_js_adsense' ) ) {
			$this->include_scripts[] = 'adsbygoogle.js';
		}

		$this->include_scripts = apply_filters( 'authow_pagespeed/delay_js_includes', $this->include_scripts );
	}

	/**
	 * Minify the JS file, if possible.
	 *
	 * @param Script $script
	 *
	 * @return void
	 */
	public function minify_js( Script $script ) {
		$minifier = new Minifier( $script );
		$minifier->process();
	}

	/**
	 * Should the script be delayed using one of the JS delay methods.
	 *
	 * @param Script $script
	 *
	 * @return boolean
	 */
	public function should_delay_script( Script $script ) {
		if ( ! get_theme_mod( 'goso_speed_delay_js' ) ) {
			return false;
		}

		// Excludes should be handled before includes and parents check.
		foreach ( $this->exclude_scripts as $exclude ) {
			if ( Util\asset_match( $exclude, $script, 'orig_html' ) ) {
				return false;
			}
		}

		// Delay all.
		if ( get_theme_mod( 'goso_speed_delay_js' ) ) {
			return true;
		}

		if ( $this->check_dependency( $script, $this->done_delay ) ) {
			$this->done_delay[ $script->id ] = true;

			return true;
		}

		foreach ( $this->include_scripts as $include ) {
			if ( Util\asset_match( $include, $script, 'orig_html' ) ) {
				$this->done_delay[ $script->id ] = true;

				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the script has a parent dependency that is delayed/deferred.
	 *
	 * @param Script $script
	 * @param array $valid
	 *
	 * @return boolean
	 */
	public function check_dependency( Script $script, array $valid ) {
		// Check if one of parent dependencies is delayed/deferred.
		foreach ( (array) $script->deps as $dep ) {
			if ( isset( $valid[ $dep ] ) ) {
				return true;
			}
		}

		// For translations, if wp-i18n-js is valid, so should be translations.
		if ( preg_match( '/-js-translations/', $script->id, $matches ) ) {
			if ( isset( $valid['wp-i18n-js'] ) ) {
				return true;
			}
		}

		// Special case: Inline script with a parent dep. If parent is true, so is child.
		// Note: 'before' and 'extra' isn't accounted for and is not usually needed. Since 'extra' is
		// usually localization and 'before' has to happen before anyways.
		if ( preg_match( '/(.+?-js)-(before|after)/', $script->id, $matches ) ) {
			$handle = $matches[1];

			// Parent was valid, so is the current child.
			if ( isset( $valid[ $handle ] ) ) {
				return true;
			}
		}

		return false;
	}
}
