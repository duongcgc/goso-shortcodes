<?php
/**
 * Plugin bootstrap
 */

use Authow\PageSpeed\Plugin;

defined( 'WPINC' ) || exit;

if ( version_compare( phpversion(), '7.0', '<' ) ) {
	/**
	 * Display an admin error notice when PHP is older the version 7.0
	 * Hook it to the 'admin_notices' action.
	 */
	function authow_pagespeed_old_php_admin_error_notice() {

		$message = sprintf( esc_html__(
			'The %Authow Shortcode & Performance %3$s plugin requires %2$sPHP 7.0+%3$s to run properly. Please contact your web hosting company and ask them to update the PHP version of your site.%4$s Your current version of PHP has reached end-of-life is %2$shighly insecure: %1$s%3$s', 'authow_pagespeed' ),
			phpversion(),
			'<strong>',
			'</strong>',
			'<br>'
		);

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}

	add_action( 'admin_notices', 'authow_pagespeed_old_php_admin_error_notice' );

	// bail
	return;
}

/**
 * Launch the plugin
 */
require_once plugin_dir_path( __FILE__ ) . 'inc/plugin.php';

$plugin              = \Authow\PageSpeed\Plugin::get_instance();
$plugin->plugin_file = __FILE__;

// Init on plugins loaded
add_action( 'plugins_loaded', array( $plugin, 'init' ) );

function goso_speed_optimizer_get_stat() {
	global $wpdb;
	$stat = [];

	$files         = (array) $wpdb->get_results(
		"SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_authow_pagespeed_sheet_cache_%'",
		ARRAY_A
	);
	$stat['css']   = Plugin::file_cache()->get_stats( 'css' );
	$stat['files'] = $files;

	return $stat;
}

add_filter( 'theme_mod_goso_enable_spoptimizer', function ( $value ) {
	if ( get_theme_mod( 'goso_speed_remove_css' ) || get_theme_mod( 'goso_speed_optimize_css' ) ) {
		return false;
	} else {
		return $value;
	}
} );
