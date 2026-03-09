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
			// Parsedown handles basic XSS but we'll use wp_kses_post as well.
			$html = $parsedown->text( $markdown );
		} else {
			$html = '<pre>' . esc_html( $markdown ) . '</pre>';
		}

		$html = apply_filters( 'rewav_docs_rendered_html', $html, $file_path );

		// Sanitize the HTML while allowing standard post tags.
		return wp_kses_post( $html );
	}
}
