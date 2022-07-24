<?php

namespace Authow\PageSpeed\Integrations;
use Authow\PageSpeed\Plugin;

/**
 * Rules specific to WPBakery Page Builder plugin.
 */
class Wpbakery 
{
	public $allow_selectors;
	
	public function __construct()
	{
		$this->register_hooks();
	}

	public function register_hooks()
	{
		add_action('wp', [$this, 'setup']);
	}

	public function setup()
	{
		$this->setup_remove_css();
	}

	/**
	 * Special rules related to remove css when WPBakery is active.
	 *
	 * @return void
	 */
	public function setup_remove_css()
	{
		// Add all WPBakery frontend files for CSS processing.
		add_filter('authow_pagespeed/remove_css_includes', function($include) {

			// Only need to remove unused on this. All other CSS files are modular and included only if needed.
			$include[] = 'id:js_composer_front';

			// FontAwesome can also go through this.
			$include[] = 'js_composer/*font-awesome';

			return $include;
		});

		add_filter('authow_pagespeed/remove_css_excludes', function($exclude, \Authow\PageSpeed\RemoveCss $remove_css) {
			// // Don't bother with animations CSS file as it won't remove much.
			// $exclude[] = 'id:vc_animate-css';

			// // Pageable owl carousel.
			// $exclude[] = 'id:vc_pageable_owl-carousel';

			// // prettyPhoto would need all the CSS.
			// $exclude[] = 'js_composer/*prettyphoto';

			// // owlcarousel is added only if needed.
			// $exclude[] = 'js_composer/*owl';

			return $exclude;
		}, 10, 2);

		/**
		 * WPbakery selectors extras.
		 */
		$this->allow_selectors = [
			[
				'type'   => 'any',
				'search' => [
					'.vc_mobile',
					'.vc_desktop',
				]
			],
			[
				'type'  => 'class',
				'class' => 'vc_parallax',
				'sheet' => 'id:js_composer_front',
				'search' => [
					'*.vc_parallax*',
					'.vc_hidden'
				]
			],
			[
				'type'  => 'class',
				'class' => 'vc_pie_chart',
				'sheet' => 'id:js_composer_front',
				'search' => [
					'.vc_ready'
				]
			],

			[
				'type'  => 'class',
				'class' => 'wpb_gmaps_widget',
				'sheet' => 'id:js_composer_front',
				'search' => [
					'.map_ready',
				]
			],

			[
				'type'  => 'class',
				'class' => 'wpb_animate_when_almost_visible',
				'sheet' => 'id:js_composer_front',
				'search' => [
					'.wpb_start_animation',
					'.animated'
				]
			],

			[
				'type'  => 'class',
				'class' => 'vc_toggle',
				'sheet' => 'id:js_composer_front',
				'search' => [
					'.vc_toggle_active',
				]
			],

			[
				'type'  => 'class',
				'class' => 'vc_grid',
				'sheet' => 'id:js_composer_front',
				'search' => [
					'.vc_grid-loading',
					'.vc_visible-item',
					'.vc_is-hover',

					// -complete and -failed etc. too
					'*.vc-spinner*',
					'*.vc-grid*.vc_active*',

					// Other dependencies maybe: prettyPhoto, owlcarousel for pagination of specific type.
				]
			],
		];

		add_filter('authow_pagespeed/allow_css_selectors', function($allow, \Authow\PageSpeed\RemoveCss $remove_css) {

			// $html = $remove_css->html;
			// if (strpos($html, 'background_slideshow_gallery') !== false) {
			// 	array_push($this->allow_selectors, ...[
			// 		[
			// 			'type'   => 'any',
			// 			'sheet'  => 'id:elementor-',
			// 			'search' => [
			// 				'*.swiper-*',
			// 				'*.elementor-background-slideshow*',
			// 				'.elementor-ken-burns*',
			// 			]
			// 		],
			// 	]);
			// }

			return array_merge($allow, $this->allow_selectors);
		}, 10, 2);
	}
}
