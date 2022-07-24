<?php

namespace Authow\PageSpeed;

use Authow\PageSpeed\OptimizeCss\Stylesheet;
use Authow\PageSpeed\RemoveCss\Sanitizer;

/**
 * Process stylesheets to authow_pagespeed and optimize CSS.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class RemoveCss {
	/**
	 * @var \DOMDocument
	 */
	public $dom;
	public $html;

	/**
	 * @var array
	 */
	public $used_markup = [];

	/**
	 * @var array
	 */
	protected $stylesheets;

	protected $include_sheets = [];
	protected $exclude_sheets = [];

	/**
	 * Undocumented function
	 *
	 * @param Stylesheet[] $stylesheets
	 * @param \DOMDocument $dom
	 * @param string $raw_html
	 */
	public function __construct( $stylesheets, \DOMDocument $dom, string $raw_html ) {
		$this->stylesheets = $stylesheets;
		$this->dom         = $dom;
		$this->html        = $raw_html;
	}

	public function process() {
		// Collect all the classes, ids, tags used in DOM.
		$this->find_used_selectors();

		// Figure out valid sheets.
		$this->setup_valid_sheets();

		/**
		 * Process and replace stylesheets with CSS cleaned.
		 */
		do_action( 'authow_pagespeed/remove_css_begin', $this );
		$allow_selectors = $this->get_allowed_selectors();

		foreach ( $this->stylesheets as $sheet ) {
			if ( ! $this->should_process_stylesheet( $sheet ) ) {
				// Util\debug_log('Skipping: ' . print_r($sheet, true));
				continue;
			}

			$org_url = $sheet->url;
			if ( substr( $org_url, 0, 12 ) === "/wp-content/" ) {
				$sheet->url = home_url( $org_url );
			}

			if ( substr( $sheet->url, 0, 2 ) === "//" ) {
				$sheet->url = 'https:' . $sheet->url;
			}

			// Check URL content.
			if ( $this->is_external_url( $sheet->url ) ) {
				$file_data = $this->fetch_remote_content( $sheet->url );
			} else {
				$file_data = $this->process_file_by_url( $sheet->url );
			}

			if ( ! $file_data ) {
				continue;
			}

			$sheet->content = $file_data['content'];
			$sheet->file    = $file_data['file'];

			// Parsed sheet will be cached instead of being parsed by Sabberworm again.
			$this->setup_sheet_cache( $sheet );

			/**
			 * Fire up sanitizer to processa and remove unused CSS.
			 */

			$cache_name = 'authow_pagespeed_sheet_cache_goso_crcss_' . $sheet->id;

			if ( is_multisite() ) {
				$cache_name = 'authow_pagespeed_sheet_cache_site_' . get_current_blog_id() . '_goso_crcss ' . $sheet->id;
			}

			if ( is_home() ) {
				$cache_name = $cache_name . '_home';
			}

			if ( is_front_page() ) {
				$cache_name = $cache_name . '_front';
			}

			if ( is_page() ) {
				$page_specify = [
					'page-fullwidth.php',
					'elementor_header_footer',
					'page-vc.php',
					'page-vc-sidebar.php',
					'template-custom-all-blog-posts.php',
				];
				$page_id      = get_the_ID();
				$custom_cr    = get_post_meta( $page_id, 'goso_post_critical_css', true );
				$custom_temp  = get_post_meta( $page_id, '_wp_page_template', true );
				$cache_name   = $custom_cr || in_array( $custom_temp, $page_specify ) ? $cache_name . '_page_' . $page_id : $cache_name . '_page_' . get_post_type();
			}

			if ( is_singular() ) {
				$singular_id = get_the_ID();
				$custom_cr   = get_post_meta( $singular_id, 'goso_post_critical_css', true );
				$cache_name  = $custom_cr ? $cache_name . '_post_' . get_post_type() . '_' . $singular_id : $cache_name . '_post_' . get_post_type();
			}

			if ( is_category() ) {
				$cat_data        = get_queried_object();
				$cat_id          = $cat_data->term_id;
				$cat_custom_data = get_option( "category_$cat_id" );
				$cache_name      = isset( $cat_custom_data['goso_critical_css'] ) && $cat_custom_data['goso_critical_css'] ? $cache_name . '_cat_' . $cat_data->taxonomy . '_' . $cat_id : $cache_name . '_cat_' . $cat_data->taxonomy;
			}

			if ( is_tag() ) {
				$tag_data   = get_queried_object();
				$cache_name = $cache_name . '_tag_' . $tag_data->taxonomy;
			}

			if ( is_tax() ) {
				$tax_data   = get_queried_object();
				$cache_name = $cache_name . '_tax_' . $tax_data->taxonomy;
			}

			if ( is_search() || is_author() || is_date() || is_day() || is_month() || is_year() ) {
				$cache_name = $cache_name . '_archive';
			}

			$sanitized_css = Plugin::file_cache()->get( $cache_name );

			if ( $sanitized_css ) {
				$use_cache     = true;
				$sanitized_css = Plugin::file_system()->get_contents( $sanitized_css );
			} else {
				$use_cache     = false;
				$sanitizer     = new Sanitizer( $sheet, $this->used_markup, $allow_selectors );
				$sanitized_css = $sanitizer->sanitize();
				Plugin::file_cache()->set( $cache_name, $sanitized_css );
			}

			if ( get_theme_mod( 'goso_speed_optimize_gfonts_delay' ) ) {
				$sanitized_css = preg_replace( '/@font-face[^{]*{([^{}]|{[^{}]*})*}/', '', $sanitized_css );
			}

			// Store sizes for debug info.
			$sheet->original_size = strlen( $sheet->content );
			$sheet->new_size      = $sanitized_css ? strlen( $sanitized_css ) : $sheet->original_size;

			if ( $sanitized_css ) {

				$extra_class = $use_cache ? 'cached' : 'no-cached';

				// 'id' is pre-sanitized as it was extracted from HTML.
				$replacement = "<style data-goso-cache='{$extra_class}' id='authow_pagespeed-{$sheet->id}'>" . $sanitized_css . '</style>';

				// Add tags for delayed CSS files.
				if ( Plugin::delay_load()->should_delay_css() ) {

					$sheet->delay_type = get_theme_mod( 'goso_speed_delay_css_type' );
					$sheet->set_render( 'delay' );
					$sheet->has_delay = true;

					// Add the delay load CSS tag in addition to inlined sanitized CSS above.
					$replacement .= $sheet->render();
				}

				$sheet->set_render( 'remove_css', $replacement );
				$this->save_sheet_cache( $sheet );
			}

			$sheet->content     = '';
			$sheet->parsed_data = '';
		}

		// $this->stylesheets = array_map(function($sheet) {
		// 	if (isset($sheet->original_size)) {
		// 		$sheet->saved = $sheet->original_size - $sheet->new_size;
		// 	}
		// 	return $sheet;
		// }, $this->stylesheets);

		return $this->html;
	}

	/**
	 * Find all the classes, ids, and tags used in the document.
	 *
	 * @return void
	 */
	protected function find_used_selectors() {
		$this->used_markup = [
			'tags'    => [],
			'classes' => [],
			'ids'     => [],
		];

		/**
		 * @var DOMElement $node
		 */
		$classes = [];
		foreach ( $this->dom->getElementsByTagName( '*' ) as $node ) {
			$this->used_markup['tags'][ $node->tagName ] = 1;

			// Collect tag classes.
			if ( $node->hasAttribute( 'class' ) ) {
				$class       = $node->getAttribute( 'class' );
				$ele_classes = preg_split( '/\s+/', $class );
				array_push( $classes, ...$ele_classes );
			}

			if ( $node->hasAttribute( 'id' ) ) {
				$this->used_markup['ids'][ $node->getAttribute( 'id' ) ] = 1;
			}
		}

		// Add the classes.
		$classes = array_filter( array_unique( $classes ) );
		if ( $classes ) {
			$this->used_markup['classes'] = array_fill_keys( $classes, 1 );
		}
	}

	/**
	 * Setup includes and excludes based on options.
	 *
	 * @return void
	 */
	public function setup_valid_sheets() {
		$default_excludes = [
			'wp-includes/css/dashicons.css',
			'admin-bar.css',
			'wp-mediaelement',
			'id:goso-font-awesome',
			'id:goso-font-awesomeold',
			'id:goso-font-iweather',
			'id:goso_icon',
		];

		$excludes = array_merge( $default_excludes, Util\option_to_array( get_theme_mod( 'goso_speed_remove_css_excludes' ) ) );

		$this->exclude_sheets = apply_filters( 'authow_pagespeed/remove_css_excludes', $excludes, $this );

		if ( get_theme_mod( 'goso_speed_remove_css' ) ) {
			return;
		}

		$this->include_sheets[] = content_url( 'themes' ) . '/*';
		$this->include_sheets[] = content_url( 'plugins' ) . '/*';

		$this->include_sheets = apply_filters( 'authow_pagespeed/remove_css_includes', $this->include_sheets, $this );
	}

	/**
	 * Get all the allowed selectors in correct data format to be used by sanitizer.
	 *
	 * @return array
	 */
	public function get_allowed_selectors() {
		// Allowed selectors of type 'any': simple match in selector string.
		$allowed_any = array_map( function ( $value ) {
			if ( ! $value ) {
				return '';
			}

			return [
				'type'   => 'any',
				'search' => [ $value ]
			];
		}, Util\option_to_array( (string) get_theme_mod( 'goso_speed_allow_css_selectors' ) ) );

		return apply_filters( 'authow_pagespeed/allow_css_selectors', array_filter( $allowed_any ), $this );
	}

	/**
	 * Determine if stylesheet should be processed, based on exclusion and inclusion
	 * rules and settings.
	 *
	 * @param Stylesheet $sheet
	 *
	 * @return boolean
	 */
	public function should_process_stylesheet( Stylesheet $sheet ) {
		// Handle manual excludes first.
		if ( $this->exclude_sheets ) {
			foreach ( $this->exclude_sheets as $exclude ) {
				if ( Util\asset_match( $exclude, $sheet ) ) {
					return false;
				}
			}
		}

		foreach ( $this->include_sheets as $include ) {
			if ( Util\asset_match( $include, $sheet ) ) {
				return true;
			}
		}

		// All stylesheets are valid.
		if ( get_theme_mod( 'goso_speed_remove_css' ) ) {
			return true;
		}

		return false;
	}

	public function is_external_url( $url ): bool {
		$external = false;

		$assets_domain = $this->get_url_domain( $url );
		$site_domain   = $this->get_url_domain( home_url() );

		if ( $assets_domain !== $site_domain ) {
			$external = true;
		}

		return $external;
	}

	public function get_url_domain( $url ) {
		$pieces = parse_url( $url );
		$domain = isset( $pieces['host'] ) && $pieces['host'] ? $pieces['host'] : $pieces['path'];
		if ( preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs ) ) {
			return $regs['domain'];
		}

		return false;
	}

	/**
	 * Get remote asset content and add to asset content.
	 *
	 * @return array
	 */
	public function fetch_remote_content( $url ) {

		$file = Plugin::file_cache()->get( $url );

		if ( ! $file ) {
			$request = wp_remote_get( $url, [
				'timeout'    => 10,
				// For google fonts mainly.
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
			] );

			if ( is_wp_error( $request ) || empty( $request['body'] ) ) {
				return;
			}

			$content = $request['body'];
		} else {
			$content = Plugin::file_system()->get_contents( $file );
		}

		return [
			'content' => $content,
			'file'    => $url
		];
	}

	/**
	 * Process a stylesheet via URL
	 *
	 * @return boolean|array With 'content' and 'file'.
	 * @uses Plugin::file_system()->url_to_local()
	 * @uses Plugin::file_system()
	 */
	public function process_file_by_url( $url ) {
		// Try to get local path for this stylesheet
		$file = Plugin::file_system()->url_to_local( $url );
		if ( ! $file ) {
			return false;
		}

		// We can only support .css files yet
		if ( substr( $file, - 4 ) !== '.css' ) {
			return false;
		}

		$content = Plugin::file_system()->get_contents( $file );
		if ( ! $content ) {
			return false;
		}

		return [
			'content' => $content,
			'file'    => $file
		];
	}

	/**
	 * Add parsed data cache to stylesheet object. Will be used by save_sheet_cache later.
	 *
	 * @param Stylesheet $sheet
	 *
	 * @return void
	 */
	public function setup_sheet_cache( Stylesheet $sheet ) {
		if ( ! isset( $sheet->file ) ) {
			return;
		}

		$cache = get_transient( $this->get_transient_id( $sheet ) );
		if ( $cache && $cache['mtime'] < Plugin::file_system()->mtime( $sheet->file ) ) {
			return;
		}

		if ( $cache && ! empty( $cache['data'] ) ) {
			$sheet->parsed_data = $cache['data'];
			$sheet->has_cache   = true;

			return;
		}
	}

	protected function get_transient_id( $sheet ) {
		return substr( 'authow_pagespeed_sheet_cache_' . $sheet->id, 0, 190 );
	}

	/**
	 * Cache the parsed data.
	 *
	 * Note: This doesn't cache whole CSS as that would vary based on found selectors.
	 *
	 * @param Stylesheet $sheet
	 *
	 * @return void
	 */
	public function save_sheet_cache( Stylesheet $sheet ) {
		if ( $sheet->has_cache ) {
			return;
		}

		$cache_data = [
			'data'  => $sheet->parsed_data,
			'mtime' => Plugin::file_system()->mtime( $sheet->file )
		];

		// With expiry; won't be auto-loaded.
		set_transient( $this->get_transient_id( $sheet ), $cache_data, MONTH_IN_SECONDS );
	}
}
