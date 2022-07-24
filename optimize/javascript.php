<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access, please.
}

class Goso_Authow_Optimization_JS {

	/**
	 * __construct funtions
	 */
	function __construct() {

		if ( ! is_admin() ) {
			// Filter defer tag
			if ( get_theme_mod( 'goso_speed_add_defer' ) || get_theme_mod( 'goso_speed_add_more_defer' ) ) {
				/* Filter script_loader_tag with 2 variable $html & handle */
				add_filter( 'script_loader_tag', array( $this, 'filter_defer_script' ), 10, 2 );
			}
		}

	}

	/**
	 * Filter to add defer="defer" to the JS files
	 */
	public function filter_defer_script( $html, $handle ) {

		if ( ! is_admin() || ! is_user_logged_in()  ) {

			$array_js = array();

			if ( get_theme_mod( 'goso_speed_add_defer' ) ) {
				$array_js[] = 'goso-libs-js';
				$array_js[] = 'goso-facebook-js';
				$array_js[] = 'goso-smoothscroll';
				$array_js[] = 'main-scripts';
				$array_js[] = 'goso_ajax_like_post';
				$array_js[] = 'goso_rateyo';
				$array_js[] = 'jquery-recipe-rateyo';
				$array_js[] = 'goso-elementor';
				$array_js[] = 'goso_ajax_more_posts';
				$array_js[] = 'goso_ajax_more_scroll';
				$array_js[] = 'goso_ajax_archive_more_scroll';
				/* $array_js[] = 'comment-reply'; */ /* Consider to add */
			}
			if ( get_theme_mod( 'goso_speed_add_more_defer' ) ) {
				$input_array = get_theme_mod( 'goso_speed_add_more_defer' );
				$array_input = goso_speed_strto_array( $input_array );
				if ( ! empty( $array_input ) ) {
					foreach ( $array_input as $js_id ) {
						$array_js[] = $js_id;
					}
				}
			}

			if ( ! empty( $array_js ) && in_array( $handle, $array_js ) ) {
				return str_replace( " src", " defer='defer' src", $html );
			}
		}

		return $html;
	}

}

new Goso_Authow_Optimization_JS();

/**
 * Remove jQuery Migrate
 */
add_action( 'wp_default_scripts', 'goso_speed_do_remove_jquery_migrate' );
function goso_speed_do_remove_jquery_migrate() {
	if ( get_theme_mod( 'goso_speed_remove_jquery_migrate' ) && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) {
			// Check whether the script has any dependencies
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}
}

// Remove jQuery Migrate version 3+ added by the jQuery Updater plugin
add_action( 'wp_enqueue_scripts', 'goso_speed_do_remove_jquery_migrate_back', 99 );
function goso_speed_do_remove_jquery_migrate_back() {
	global $wp_scripts;
	if ( get_theme_mod( 'goso_speed_remove_jquery_migrate' ) && isset( $wp_scripts->registered['jquery-migrate'] ) ) {
		$script = $wp_scripts->registered['jquery-migrate'];
		if ( ! empty( $script ) && $script->ver >= 3 ) {
			$deps                                   = $wp_scripts->registered['jquery']->deps;
			$wp_scripts->registered['jquery']->deps = array_diff( $deps, array( 'jquery-migrate' ) );
		}
	}
}
