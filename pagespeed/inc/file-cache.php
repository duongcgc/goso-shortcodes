<?php

namespace Authow\PageSpeed;

/**
 * Filesystem based cache for assets.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class FileCache {
	public $cache_path;
	public $cache_url;
	/**
	 * @var Filesystem
	 */
	protected $fs;

	public function __construct() {
		$this->fs         = Plugin::file_system();
		$this->cache_path = WP_CONTENT_DIR . '/cache/authow_pagespeed/';
		$this->cache_url  = WP_CONTENT_URL . '/cache/authow_pagespeed/';
		if ( is_multisite() ) {
			$this->cache_path = WP_CONTENT_DIR . '/cache/authow_pagespeed/site-' . get_current_blog_id() . '/';
			$this->cache_url  = WP_CONTENT_URL . '/cache/authow_pagespeed/site-' . get_current_blog_id() . '/';
		}
	}

	/**
	 * Get a cached file URL.
	 *
	 * @param string $id
	 *
	 * @return bool|string
	 */
	public function get_url( $id ) {
		if ( ! $this->get( $id ) ) {
			return false;
		}

		$file = $this->get_file_name( $id );

		if ( ! $file ) {
			return false;
		}

		return $this->cache_url . $this->get_cache_type( $file ) . '/' . $file;
	}

	/**
	 * Get a cached file path.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function get( $id ) {
		$file = $this->get_file_name( $id );
		if ( ! $file ) {
			return false;
		}

		$file = $this->get_cache_path( $id ) . $file;
		if ( $this->fs->is_file( $file ) ) {
			return $file;
		}

		return false;
	}

	/**
	 * Get file name for cache storage/retrieval provided a URL or path.
	 *
	 * @param string $id URL or path of a file.
	 *
	 * @return string
	 */
	protected function get_file_name( $id ) {
		// Get a local path if a valid URL was provided. Remote URLs also work.
		$file = $this->fs->url_to_local( $id );
		if ( ! $file ) {
			// Remote here.
		}

		// MD5 on the id as it's likely to be a URL that can has a changing query string.
		$hash = md5( $id );

		return $hash . '.' . $this->get_cache_type( $file ?: $id );
	}

	/**
	 * Get the type of cache group based on file extension.
	 *
	 * @param string $id File path or name. URLs fine too.
	 *
	 * @return string
	 */
	protected function get_cache_type( string $file ) {
		$extension = pathinfo( $file, PATHINFO_EXTENSION );
		$type      = $extension === 'js' ? 'js' : 'css';

		return $type;
	}

	/**
	 * Get cache path based on provided file path or id.
	 *
	 * @param string $asset A local path or a URL.
	 *
	 * @return string
	 */
	public function get_cache_path( $asset = '' ) {
		$path  = $this->cache_path;
		$asset = $asset ? $this->fs->url_to_local( $asset ) : '';

		// Path with asset type directory.
		return $path . $this->get_cache_type( $asset ) . '/';
	}

	/**
	 * Save a file in the cache.
	 *
	 * @param string $id Path, a local asset URL, or a unqiue name.
	 * @param string $content
	 *
	 * @return bool|string    File path on success or false on failure.
	 */
	public function set( $id, string $content = '' ) {
		if ( ! $content ) {
			return false;
		}

		// Ensure path exists.
		$path = $this->get_cache_path( $id );
		$this->fs->mkdir_p( $path );

		$file = $path . $this->get_file_name( $id );
		if ( $this->fs->put_contents( $file, $content ) ) {
			return $file;
		}

		return false;
	}

	/**
	 * Delete all the cached files for specified cache.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function delete_cache( $type = 'js' ) {
		$dir   = $this->cache_path . $type . '/';
		$files = (array) $this->fs->dirlist( $dir );
		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				if ( isset( $file['name'] ) ) {
					$this->fs->delete( $dir . $file['name'] );
				}
			}
		}
	}

	/**
	 * Delete all the cached files for specified cache.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function delete_all_cache( $type = 'js' ) {
		$dir   = WP_CONTENT_DIR . '/cache/authow_pagespeed/' . $type . '/';
		$files = (array) $this->fs->dirlist( $dir );
		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				if ( isset( $file['name'] ) ) {
					$this->fs->delete( $dir . $file['name'] );
				}
			}
		}
	}

	/**
	 * Get number of files in cache for a specific type.
	 *
	 * @param string $type
	 *
	 * @return integer
	 */
	public function get_stats( $type = 'js' ) {
		$dir   = $this->cache_path . $type;
		$files = $this->fs->dirlist( $dir );

		return $files ? count( $files ) : 0;
	}
}
