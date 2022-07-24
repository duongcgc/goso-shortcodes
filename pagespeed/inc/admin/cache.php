<?php

namespace Authow\PageSpeed\Admin;

use Authow\PageSpeed\Plugin;

/**
 * Cache clear, stats and similar for admin area.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class Cache {
	protected $deleting = false;

	public function init() {
		$this->register_clear_hooks();
	}

	/**
	 * Register hooks to clear cache.
	 *
	 * @return void
	 */
	public function register_clear_hooks() {
		/**
		 * Use this hook to clear caches externally.
		 */
		add_action( 'authow_pagespeed/empty_caches', [ $this, 'empty' ] );

		/**
		 * Other plugin hooks to empty cache on.
		 */
		$hooks = [
			// WP Rocket.
			'after_rocket_clean_domain',

			// W3 Total Cache.
			'w3tc_flush_all',

			// SGF plugin font cache delete.
			'sgf/after_delete_cache',
		];

		foreach ( $hooks as $hook ) {
			add_action( $hook, [ $this, 'empty' ] );
		}
	}

	/**
	 * Delete all types of caches.
	 *
	 * @return void
	 */
	public function empty_single_site() {
		// Already clearing the caches. Needed as we hook into plugins clear methods,
		// but also call them ourselves on cache clear.
		if ( $this->deleting ) {
			return;
		}

		// One of the hooks set via register_clear_hooks() may fire.
		$this->deleting = true;

		$this->delete_single_transients();

		// Delete files cache.
		Plugin::file_cache()->delete_cache( 'css' );

		// Clear cache plugins.
		$this->empty_cache_plugins();

		$this->deleting = false;
	}

	/**
	 * Delete all the authow_pagespeed cache transients.
	 *
	 * @return void
	 */
	protected function delete_single_transients() {
		foreach ( $this->get_single_transients() as $transient ) {
			$transient = str_replace( '_transient_', '', $transient['option_name'] );
			\delete_transient( $transient );
		}
	}

	/**
	 * Get all the authow_pagespeed cache transients.
	 *
	 * @return array
	 */
	public function get_single_transients() {
		global $wpdb;

		$blogid = get_current_blog_id();

		return (array) $wpdb->get_results( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_authow_pagespeed_sheet_cache_site_{$blogid}_%'", ARRAY_A );
	}

	/**
	 * Empty external caches from plugins and hosts.
	 *
	 * @return void
	 */
	protected function empty_cache_plugins() {
		// WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache( is_multisite() ? get_current_blog_id() : 0 );
		}

		// W3 Total Cache.
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		}

		// WP Rocket.
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// WP Fastest Cache.
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache();
		}

		// Swift Performance plugin.
		if ( class_exists( '\Swift_Performance_Cache' ) && is_callable( [
				'\Swift_Performance_Cache',
				'clear_all_cache'
			] ) ) {
			\Swift_Performance_Cache::clear_all_cache();
		}

		// LiteSpeed Cache.
		if ( class_exists( '\LiteSpeed_Cache_API' ) && is_callable( [ '\LiteSpeed_Cache_API', 'purge_all' ] ) ) {
			\LiteSpeed_Cache_API::purge_all();
		}

		// Cache Enabler.
		if ( class_exists( '\Cache_Enabler' ) && is_callable( [ '\Cache_Enabler', 'clear_total_cache' ] ) ) {
			\Cache_Enabler::clear_total_cache();
		}

		// Comet cache.
		if ( class_exists( '\comet_cache' ) && is_callable( [ '\comet_cache', 'clear' ] ) ) {
			\comet_cache::clear();
		}

		// RT Nginx Helper plugin.
		if ( defined( 'NGINX_HELPER_BASENAME' ) ) {
			do_action( 'rt_nginx_helper_purge_all' );
		}

		// Hummingbird
		if ( class_exists( '\Hummingbird\WP_Hummingbird' ) && is_callable( [
				'\Hummingbird\WP_Hummingbird',
				'flush_cache'
			] ) ) {
			\Hummingbird\WP_Hummingbird::flush_cache();
		}

		// Pagely.
		if ( class_exists( '\PagelyCachePurge' ) && is_callable( [ '\PagelyCachePurge', 'purgeAll' ] ) ) {
			\PagelyCachePurge::purgeAll();
		}

		// WPEngine
		if ( class_exists( '\WpeCommon' ) ) {
			is_callable( [ '\WpeCommon', 'purge_memcached' ] ) && \WpeCommon::purge_memcached();
			is_callable( [ '\WpeCommon', 'purge_varnish_cache' ] ) && \WpeCommon::purge_varnish_cache();
		}

		// SiteGround.
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
	}

	/**
	 * Delete all types of caches.
	 *
	 * @return void
	 */
	public function empty() {
		// Already clearing the caches. Needed as we hook into plugins clear methods,
		// but also call them ourselves on cache clear.
		if ( $this->deleting ) {
			return;
		}

		// One of the hooks set via register_clear_hooks() may fire.
		$this->deleting = true;

		$this->delete_transients();

		// Delete files cache.
		Plugin::file_cache()->delete_all_cache( 'css' );

		// Clear cache plugins.
		$this->empty_cache_plugins();

		$this->deleting = false;
	}

	/**
	 * Delete all the authow_pagespeed cache transients.
	 *
	 * @return void
	 */
	protected function delete_transients() {
		foreach ( $this->get_transients() as $transient ) {
			$transient = str_replace( '_transient_', '', $transient['option_name'] );
			\delete_transient( $transient );
		}
	}

	/**
	 * Get all the authow_pagespeed cache transients.
	 *
	 * @return array
	 */
	public function get_transients() {
		global $wpdb;

		return (array) $wpdb->get_results( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_authow_pagespeed_sheet_cache_%'", ARRAY_A );
	}
}
