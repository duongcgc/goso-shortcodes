<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access, please.
}

class Goso_Authow_Optimization_General {

	/**
	 * __construct funtions
	 */
	function __construct(){
		
		if( ! is_admin() ){
			
			// Disable Emojis
			if( get_theme_mod( 'goso_speed_disable_emoji' ) ){
				remove_action( 'wp_print_styles',            'print_emoji_styles');
				remove_action( 'wp_head',                    'print_emoji_detection_script', 7);
				remove_filter( 'the_excerpt',                'convert_smilies' );
				remove_filter( 'the_post_thumbnail_caption', 'convert_smilies' );
				remove_filter( 'the_content',                'convert_smilies', 20 );
				remove_filter( 'comment_text',               'convert_smilies', 20 );
				remove_filter( 'widget_text_content',        'convert_smilies', 20 );
			}

			// Remove Query Strings
			if( get_theme_mod( 'goso_speed_remove_query_string' ) ){
				add_filter( 'style_loader_src',   array( $this, 'remove_query_vers' ), 15 );
				add_filter( 'script_loader_src',  array( $this, 'remove_query_vers' ), 15 );
			}

			// Add Preconnect & Dns prefetch by default
			add_action( 'wp_enqueue_scripts', array( $this, 'preconnect_domains' ), 7 );
			add_action( 'wp_enqueue_scripts', array( $this, 'dns_prefetch_domains' ), 7 );

			// Add preload
			//add_action( 'wp_enqueue_scripts', array( $this, 'add_preload' ), 8 );

			// Disable wlwmanifest Link
			if( get_theme_mod( 'goso_speed_remove_wlwmanifest' ) ){
				remove_action( 'wp_head', 'wlwmanifest_link' );
			}
			
			// Disable XML-RPC and RSD Link
			if( get_theme_mod( 'goso_speed_remove_xml_rsd' ) ){
				remove_action( 'wp_head', 'rsd_link' );
				add_filter( 'xmlrpc_enabled', '__return_false', 5 );
			}

			// Disable no needed filter - see more: https://developer.wordpress.org/reference/functions/capital_p_dangit/
			remove_filter( 'the_title',   'capital_P_dangit', 11 );
			remove_filter( 'the_content', 'capital_P_dangit', 11 );
			remove_filter( 'wp_title',    'capital_P_dangit', 11 );
			remove_filter( 'comment_text', 'capital_P_dangit', 31 );
		}

	}


	/**
	 * Remove Query Strings
	 */
	public function remove_query_vers( $src ){

		if( ! is_admin() && ! current_user_can( 'manage_options' ) ){
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}
	
	/**
	 * Add preconnect for the google fonts
	 */
	public function preconnect_domains() {

		$domains = apply_filters( 'goso_authow_optimize_preconnect_domains', array(
			"https://fonts.googleapis.com",
			"https://fonts.gstatic.com",
		));

		if( ! empty( $domains ) && is_array( $domains ) ){
			foreach ( $domains as $domain ) {
				echo "<link rel='preconnect' href='$domain' />\n";
			}
		}
	}
	
	/**
	 * Add dns-prefetch for the most used domains
	 */
	public function dns_prefetch_domains() {

		echo "<meta http-equiv='x-dns-prefetch-control' content='on'>\n";

		$domains = apply_filters( 'goso_authow_optimize_dns_domains', array(
			"//fonts.googleapis.com",
			"//fonts.gstatic.com",
			"//s.gravatar.com",
			"//www.google-analytics.com"
		));

		if( ! empty( $domains ) && is_array( $domains ) ){
			foreach ( $domains as $domain ) {
				if ( ! empty( $domain ) ){
					echo "<link rel='dns-prefetch' href='$domain' />\n";
				}
			}
		}
	}


	/**
	 * Add preload - see more: https://www.webnots.com/how-to-fix-preload-key-requests-with-fonts-in-wordpress/
	 */
	public function add_preload(){

		// Preload logo images
		$logo_data = $this->logo_data();
		if( ! empty( $logo_data ) && is_array( $logo_data ) ){
			foreach ( $logo_data as $logo_url ) {
				if( ! empty( $logo_url ) ){
					$file_type = wp_check_filetype( $logo_url );  /* Check file type: https://developer.wordpress.org/reference/functions/wp_check_filetype/ */
					$type = $file_type['ext'];
					$file_end = substr( $logo_url, -4 );
					if( '.svg' != $file_end  ){
						echo "<link rel='preload' as='image' href='$logo_url' type='image/$type'>\n";
					}
				}
			}
		}

		// Preload Fonts inside the theme
		$fonts_data = $this->fonts_data();
		$dir = get_template_directory_uri();

		if( ! empty( $fonts_data ) && is_array( $fonts_data ) ){
			foreach ( $fonts_data as $name => $type ) {
				echo "<link rel='preload' as='font' href='". $dir ."/$name' type='font/$type' crossorigin='anonymous' />\n";
			}
		}

	}
	
	/**
	 * Get logo data for preload
	 */
	public function logo_data(){
		$return = array();
		
		if( ! get_theme_mod( 'goso_vertical_nav_show' ) ){
			$logo_src = $mobile_logo = get_template_directory_uri() . '/images/logo.png';

			if ( get_theme_mod( 'goso_logo' ) ) {
				$logo_src = $mobile_logo = get_theme_mod( 'goso_logo' );
			}
			
			$logo_trans = '';
			
			if( function_exists( 'goso_is_header_transparent' ) ){
				$header_trans  = goso_is_header_transparent();
				if( $header_trans ) {
					$hlogo_trans_sticky = get_theme_mod( 'goso_upload_transparent_logo' );
					if ( $hlogo_trans_sticky ) {
						$logo_trans = $hlogo_trans_sticky;
					}
				}

				if ( is_page() ) {
					$pmeta_page_header = get_post_meta( get_the_ID(), 'goso_pmeta_page_header', true );
					if ( isset( $pmeta_page_header['custom_logo'] ) && $pmeta_page_header['custom_logo'] ) {
						$url_logo_src = wp_get_attachment_url( intval( $pmeta_page_header['custom_logo'] ) );
						if ( $url_logo_src ) {
							$logo_src = $url_logo_src;
						}
					}

					if( $header_trans ){
						if ( isset( $pmeta_page_header['hlogo_trans'] ) && $pmeta_page_header['hlogo_trans'] ) {
							$url_hlogo_trans = wp_get_attachment_url( intval( $pmeta_page_header['hlogo_trans'] ) );
							if ( $url_hlogo_trans ) {
								$logo_trans = $url_hlogo_trans;
							}
						}
					}
				}
			}
			
			$return['logo_image'] = $logo_src;
			if( $logo_trans ){
				if( $logo_trans != $logo_src ){
					$return['logo_image'] = $logo_trans;
				}
			}
			
		} else {
			$logo_src = $logo_vertical = get_template_directory_uri() . '/images/logo.png';
			if( get_theme_mod( 'goso_logo' ) ){
				$logo_src = $logo_vertical = get_theme_mod( 'goso_logo' );
			}
			if( get_theme_mod( 'goso_vertical_nav_remove_header' ) ){
				$logo_src = '';
			}
			if( get_theme_mod( 'goso_menu_hbg_logo' ) ){
				$logo_vertical = get_theme_mod( 'goso_menu_hbg_logo' );
			}
			
			$return['logo_vertical'] = $logo_vertical;
			if( $logo_src && $logo_src != $logo_vertical ){
				$return['logo_image'] = $logo_src;
			}
			
		}
		
		return $return;
		
	}
	
	/**
	 * Get fonts data for preload
	 */
	public function fonts_data(){
		
		$return = array(
			'fonts/fontawesome-webfont.woff2?v=4.7.0' => 'woff2'
		);

		if( get_theme_mod( 'goso_fontawesome_ver5' ) ){
			$return['webfonts/fa-brands-400.woff2'] = 'woff2';
			$return['webfonts/fa-regular-400.woff2'] = 'woff2';
			$return['webfonts/fa-solid-900.woff2'] = 'woff2';
		}
		
		return $return;
		
	}

}

new Goso_Authow_Optimization_General();
