<?php

/**
 * Handles the file scanning for Markdown documentation.
 */
class Rewav_Docs_File_Scanner {

	/**
	 * The name of the transient used for caching the file index.
	 */
	const TRANSIENT_KEY = 'rewav_docs_file_index';

	/**
	 * Get the documentation root path.
	 *
	 * @return string The absolute path to the docs folder.
	 */
	public function get_docs_path() {
		$default_path = WP_CONTENT_DIR . '/uploads/rewav-docs';
		$options = get_option( 'rewav_docs_options', [] );
		$path = isset( $options['path'] ) ? $options['path'] : $default_path;

		if ( defined( 'REWAV_DOCS_PATH' ) ) {
			$path = REWAV_DOCS_PATH;
		}

		return apply_filters( 'rewav_docs_folder_path', $path );
	}

	/**
	 * Get the maximum scan depth.
	 *
	 * @return int The max depth.
	 */
	public function get_scan_depth() {
		$options = get_option( 'rewav_docs_options', [] );
		$depth = isset( $options['scan_depth'] ) ? (int) $options['scan_depth'] : 3;

		if ( defined( 'REWAV_DOCS_SCAN_DEPTH' ) ) {
			$depth = (int) REWAV_DOCS_SCAN_DEPTH;
		}

		return apply_filters( 'rewav_docs_scan_depth', $depth );
	}

	/**
	 * Scan the directory for Markdown files.
	 *
	 * @param bool $force_refresh Whether to bypass the cache.
	 * @return array List of discovered files.
	 */
	public function scan( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached_files = get_transient( self::TRANSIENT_KEY );
			if ( false !== $cached_files ) {
				return $cached_files;
			}
		}

		$docs_path = $this->get_docs_path();
		$files = [];

		if ( ! is_dir( $docs_path ) || ! is_readable( $docs_path ) ) {
			return $files;
		}

		$max_depth = $this->get_scan_depth();
		$max_files = apply_filters( 'rewav_docs_max_files', 200 );
		$allowed_extensions = apply_filters( 'rewav_docs_allowed_extensions', [ 'md', 'markdown' ] );

		try {
			$directory = new RecursiveDirectoryIterator( $docs_path, RecursiveDirectoryIterator::SKIP_DOTS );
			$iterator = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::SELF_FIRST );
			$iterator->setMaxDepth( $max_depth - 1 );

			foreach ( $iterator as $file ) {
				if ( count( $files ) >= $max_files ) {
					break;
				}

				if ( $file->isDir() ) {
					continue;
				}

				$extension = pathinfo( $file->getFilename(), PATHINFO_EXTENSION );
				if ( ! in_array( strtolower( $extension ), $allowed_extensions, true ) ) {
					continue;
				}

				// Ignore hidden files and folders starting with _ or .
				$relative_path = substr( $file->getPathname(), strlen( $docs_path ) + 1 );
				$path_parts = explode( DIRECTORY_SEPARATOR, $relative_path );
				$ignore = false;
				foreach ( $path_parts as $part ) {
					if ( strpos( $part, '_' ) === 0 || strpos( $part, '.' ) === 0 ) {
						$ignore = true;
						break;
					}
				}
				if ( $ignore ) {
					continue;
				}

				$files[] = $this->create_file_object( $file, $docs_path );
			}
		} catch ( Exception $e ) {
			if ( defined( 'REWAV_DOCS_DEBUG' ) && REWAV_DOCS_DEBUG ) {
				error_log( 'Rewav Docs Scan Error: ' . $e->getMessage() );
			}
		}

		$files = apply_filters( 'rewav_docs_files', $files );
		set_transient( self::TRANSIENT_KEY, $files, apply_filters( 'rewav_docs_cache_ttl', 300 ) );

		return $files;
	}

	/**
	 * Create a file object from a SplFileInfo instance.
	 *
	 * @param SplFileInfo $file      The file info.
	 * @param string      $docs_path The root docs path.
	 * @return array The file data.
	 */
	private function create_file_object( $file, $docs_path ) {
		$absolute_path = $file->getPathname();
		$relative_path = substr( $absolute_path, strlen( $docs_path ) + 1 );
		$slug = str_replace( DIRECTORY_SEPARATOR, '--', $relative_path );

		// Derive title from H1 or filename
		$title = $this->get_title_from_file( $absolute_path );
		if ( empty( $title ) ) {
			$title = str_replace( [ '_', '-' ], ' ', pathinfo( $file->getFilename(), PATHINFO_FILENAME ) );
			$title = ucwords( $title );
		}

		return [
			'absolute_path' => $absolute_path,
			'relative_path' => $relative_path,
			'slug'          => $slug,
			'title'         => $title,
			'modified_time' => $file->getMTime(),
			'section'       => $this->get_section( $relative_path ),
		];
	}

	/**
	 * Extract the first H1 from a file.
	 *
	 * @param string $path Absolute path.
	 * @return string The title or empty string.
	 */
	private function get_title_from_file( $path ) {
		// We use file_get_contents here for a small chunk of the file.
		// In a full implementation, we'd use WP_Filesystem if available.
		$content = @file_get_contents( $path, false, null, 0, 1024 );
		if ( $content && preg_match( '/^#\s+(.+)$/m', $content, $matches ) ) {
			return trim( $matches[1] );
		}
		return '';
	}

	/**
	 * Get the section name from a relative path.
	 *
	 * @param string $relative_path Relative path.
	 * @return string Section name.
	 */
	private function get_section( $relative_path ) {
		$parts = explode( DIRECTORY_SEPARATOR, $relative_path );
		if ( count( $parts ) > 1 ) {
			return ucwords( str_replace( [ '_', '-' ], ' ', $parts[0] ) );
		}
		return __( 'General', 'rewav-docs' );
	}

	/**
	 * Find a file by its slug.
	 *
	 * @param string $slug The file slug.
	 * @return array|false The file data or false if not found.
	 */
	public function get_file_by_slug( $slug ) {
		$files = $this->scan();
		foreach ( $files as $file ) {
			if ( $file['slug'] === $slug ) {
				return $file;
			}
		}
		return false;
	}
}
