<?php

namespace Authow\PageSpeed\Integrations;

use Authow\PageSpeed\Plugin;

/**
 * Rules specific to Elementor plugin.
 */
class Elementor 
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
	 * Special rules related to remove css when Elementor is active.
	 *
	 * @return void
	 */
	public function setup_remove_css()
	{
		// Add all Elementor frontend files for CSS processing.
		add_filter('authow_pagespeed/remove_css_includes', function($include) {

			$include[] = 'id:elementor-frontend-css';
			$include[] = 'id:elementor-pro-css';
			// $include[] = 'elementor/*font-awesome';
			return $include;
		});

		add_filter('authow_pagespeed/remove_css_excludes', function($exclude, \Authow\PageSpeed\RemoveCss $remove_css) {

			// Don't bother with animations CSS file as it won't remove much.
			if (!empty($remove_css->used_markup['classes']['elementor-invisible'])) {
				$exclude[] = 'id:elementor-animations';
			}

			$exclude[] = 'id:elementor-icons';
			$exclude[] = 'id:elementor-icons-*';

			return $exclude;
		}, 10, 2);

		/**
		 * Elementor selectors extras.
		 */
		$this->allow_selectors = [
			[
				'type'   => 'any',
				'sheet'  => 'id:elementor-',
				'search' => [
					'*.e--ua-*',
					'.elementor-loading',
					'.elementor-invisible',
					//'.elementor-background-video-embed',
				]
			],
			[
				'type'  => 'class',
				'class' => 'elementor-invisible',
				'sheet' => 'id:elementor-',
				'search' => [
					'.animated'
				]
			],
			[
				'type'  => 'class',
				'class' => 'elementor-invisible',
				'sheet' => 'id:elementor-',
				'search' => [
					'.animated'
				]
			],
		];

		if (defined('ELEMENTOR_PRO_VERSION')) {
			$this->allow_selectors = array_merge($this->allow_selectors, [
				[
					'type'  => 'class',
					'class' => 'elementor-posts-container',
					// 'sheet' => 'id:elementor-',
					'search' => [
						'.elementor-posts-container',
						'.elementor-has-item-ratio'
					]
				],
			]);
		}

		add_filter('authow_pagespeed/allow_css_selectors', function($allow, \Authow\PageSpeed\RemoveCss $remove_css) {

			$html = $remove_css->html;
			if (strpos($html, 'background_slideshow_gallery') !== false) {
				array_push($this->allow_selectors, ...[
					[
						'type'   => 'any',
						'sheet'  => 'id:elementor-',
						'search' => [
							'*.swiper-*',
							'*.elementor-background-slideshow*',
							'.elementor-ken-burns*',
						]
					],
				]);
			}

			return array_merge($allow, $this->allow_selectors);
		}, 10, 2);
	}
}
