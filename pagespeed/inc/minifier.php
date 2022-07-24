<?php

namespace Authow\PageSpeed;

use MatthiasMullie\Minify;
use Authow\PageSpeed\Base\Asset;
use Authow\PageSpeed\OptimizeCss\Stylesheet;

/**
 * Minifer for assets with cache integration.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class Minifier {
	protected $asset_type = 'js';

	/**
	 * @var Authow\PageSpeed\OptimizeCss\Stylesheet|Authow\PageSpeed\OptimizeJs\Script
	 */
	protected $asset;

	/**
	 * @param \Authow\PageSpeed\Base\Asset $asset
	 */
	public function __construct( Asset $asset ) {
		if ( $asset instanceof Stylesheet ) {
			$this->asset_type = 'css';
		}

		$this->asset = $asset;
	}

	/**
	 * Minify the asset, cache it, and replace its URL in the asset object.
	 *
	 * @return string URL of the minified file.
	 * @uses Plugin::file_system()
	 *
	 * @uses Plugin::file_cache()
	 */
	public function process() {
		// Not for inline assets.
		if ( ! $this->asset->url ) {
			return;
		}

		// Fix other plugin strip the domain name in the asset link.
		$org_url = $this->asset->url;
		if ( substr( $org_url, 0, 12 ) === "/wp-content/" ) {
			$this->asset->url = home_url( $org_url );
		}

		// Debugging scripts. Do not minify.
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return;
		}

		$file = Plugin::file_cache()->get_url( $this->asset->url );
		if ( ! $file ) {
			$minify = $this->minify();
			if ( ! $minify ) {
				return;
			}

			// For CSS assets, convert URLs to fully qualified.
			$this->maybe_convert_urls();

			if ( ! Plugin::file_cache()->set( $this->asset->url, $this->asset->content ) ) {
				return;
			}

			$file = Plugin::file_cache()->get_url( $this->asset->url );
		}

		$this->asset->minified_url = $file;

		return $file;
	}

	/**
	 * Minify the file using the URL in the asset object.
	 *
	 * @return string|boolean
	 */
	public function minify() {
		// We support google fonts remote fetch.
		if ( $this->is_external_url() ) {
			$this->fetch_remote_content();
		} else {
			// We minify and cache local source only for now.
			$source_file = Plugin::file_system()->url_to_local( $this->asset->url );
			if ( ! $source_file ) {
				return false;
			}

			$source               = Plugin::file_system()->get_contents( $source_file );
			$this->asset->content = $source;
		}

		/**
		 * We want a cached file with source data whether existing is minified or not - as
		 * post-processing is needed for URLs when inlining the CSS.
		 *
		 * For JS, caching the already min files also serves the purpose of not testing them
		 * again, unnecessarily.
		 */
		if ( $this->is_content_minified() ) {
			return $this->asset->content;
		}

		//Util\debug_log( 'Minifying: ' . $this->asset->url );

		// JS minifier.
		if ( $this->asset_type === 'js' ) {

			// Improper handling for older webpack: https://github.com/matthiasmullie/minify/issues/375
			if ( strpos( $source, 'var __webpack_require__ = function (moduleId)' ) !== false ) {
				return false;
			}

			$minifier             = new Minify\JS( $this->asset->content );
			$this->asset->content = $minifier->minify();

		} else {

			// CSS minifier. Set content and convert urls.
			$minifier             = new Minify\CSS( $this->asset->content );
			$this->asset->content = $minifier->minify();
		}

		return $this->asset->content;
	}

	public function is_external_url() {
		$external = false;

		$assets_domain = $this->get_url_domain( $this->asset->url );
		$site_domain   = $this->get_url_domain( home_url() );

		if ( get_theme_mod( 'goso_speed_optimize_gfonts_inline' ) &&
			$this->asset instanceof Stylesheet &&
			$this->asset->is_google_fonts() ) {
			$external = true;
		}

		if ( $assets_domain !== $site_domain && $this->asset instanceof Stylesheet ) {
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
	 * @return void
	 */
	public function fetch_remote_content() {
		$request = wp_remote_get( $this->asset->url, [
			'timeout'    => 10,
			// For google fonts mainly.
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
		] );

		if ( is_wp_error( $request ) || empty( $request['body'] ) ) {
			return;
		}

		$this->asset->content = $request['body'];
	}

	/**
	 * Check if provided content is already minified.
	 *
	 * @return boolean
	 */
	public function is_content_minified( $content = '' ) {
		$content = $content ?: $this->asset->content;

		// Already minified asset.
		if ( preg_match( '/[\-\.]min\.(js|css)/', $this->asset->url ) ) {
			return true;
		}

		$content = trim( $content );
		if ( ! $content ) {
			return true;
		}

		// Hacky, but will do.
		$new_lines = substr_count( $content, "\n", 0, min( strlen( $content ), 2000 ) );
		if ( $new_lines < 5 ) {
			return true;
		}
	}

	/**
	 * Convert URLs for CSS assets.
	 *
	 * @return void
	 */
	public function maybe_convert_urls() {
		if ( $this->asset_type !== 'css' ) {
			return;
		}

		// Check if any non-data URLs exist.
		if ( ! preg_match( '/url\((?![\'"\s]*data:)/', $this->asset->content ) ) {
			return;
		}

		$this->asset->convert_urls();
	}
}
