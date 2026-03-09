<?php

/**
 * Handles the Markdown to HTML rendering.
 */
class Rewav_Docs_Markdown_Renderer {

	/**
	 * Render Markdown content to HTML.
	 *
	 * @param string $markdown  The raw Markdown.
	 * @param string $file_path The absolute path to the file (for filters).
	 * @return string The rendered HTML.
	 */
	public function render( $markdown, $file_path = '' ) {
		if ( empty( $markdown ) ) {
			return '';
		}

		$markdown = apply_filters( 'rewav_docs_pre_render', $markdown, $file_path );

		if ( class_exists( 'Parsedown' ) ) {
			$parsedown = new Parsedown();
			$html = $parsedown->text( $markdown );
		} else {
			$html = '<pre>' . esc_html( $markdown ) . '</pre>';
		}

		// Resolve relative image paths
		if ( ! empty( $file_path ) ) {
			$html = $this->resolve_relative_images( $html, $file_path );
		}

		// Transform Mermaid blocks: <pre><code class="language-mermaid">...</code></pre> -> <div class="mermaid">...</div>
		$html = preg_replace_callback(
			'/<pre><code class="language-mermaid">([\s\S]*?)<\/code><\/pre>/',
			function( $matches ) {
				// Decode HTML entities because Parsedown might have encoded them
				$content = htmlspecialchars_decode( $matches[1] );
				return '<div class="mermaid">' . $content . '</div>';
			},
			$html
		);

		$html = apply_filters( 'rewav_docs_rendered_html', $html, $file_path );

		// Sanitize the HTML. We need to allow div with class "mermaid" for Mermaid.js
		$allowed_html = wp_kses_allowed_html( 'post' );
		$allowed_html['div'] = [
			'class' => true,
		];

		return wp_kses( $html, $allowed_html );
	}

	/**
	 * Resolve relative image paths to absolute URLs.
	 *
	 * @param string $html      The rendered HTML.
	 * @param string $file_path The absolute path to the Markdown file.
	 * @return string Updated HTML.
	 */
	private function resolve_relative_images( $html, $file_path ) {
		$scanner = new Rewav_Docs_File_Scanner();
		$docs_root = realpath( $scanner->get_docs_path() );
		$file_dir = dirname( realpath( $file_path ) );

		if ( false === $docs_root || false === $file_dir ) {
			return $html;
		}

		// Get Uploads URL and Path
		$uploads = wp_upload_dir();
		$upload_base_url = $uploads['baseurl'];
		$upload_base_path = $uploads['basedir'];

		return preg_replace_callback(
			'/<img\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i',
			function( $matches ) use ( $file_dir, $docs_root, $upload_base_url, $upload_base_path ) {
				$src = $matches[2];

				// Skip if it's already an absolute URL or data URI
				if ( preg_match( '/^(https?:\/\/|data:)/i', $src ) ) {
					return $matches[0];
				}

				// Construct the absolute filesystem path to the image
				$image_path = realpath( $file_dir . DIRECTORY_SEPARATOR . $src );

				// Ensure the image is within the documentation root (security)
				if ( $image_path && 0 === strpos( $image_path, $docs_root ) ) {
					// Map filesystem path to URL
					// We assume the docs are within the uploads directory or somewhere accessible via URL.
					// If they are in uploads, we can just replace the base path with the base URL.
					if ( 0 === strpos( $image_path, $upload_base_path ) ) {
						$image_url = str_replace( $upload_base_path, $upload_base_url, $image_path );
						return sprintf( '<img %ssrc="%s"%s>', $matches[1], esc_url( $image_url ), $matches[3] );
					}
				}

				return $matches[0];
			},
			$html
		);
	}
}
