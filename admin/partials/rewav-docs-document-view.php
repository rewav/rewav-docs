<?php
/**
 * Provide a admin area view for single document display.
 */

if ( ! current_user_can( apply_filters( 'rewav_docs_view_capability', 'rewav_docs_view' ) ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rewav-docs' ) );
}

$doc_slug = isset( $_GET['doc'] ) ? sanitize_text_field( wp_unslash( $_GET['doc'] ) ) : '';
$scanner = new Rewav_Docs_File_Scanner();
$file = $scanner->get_file_by_slug( $doc_slug );

if ( ! $file ) {
	wp_die( esc_html__( 'The requested document does not exist.', 'rewav-docs' ), 404 );
}

// Security: Path traversal check
$real_docs_root = realpath( $scanner->get_docs_path() );
$real_file_path = realpath( $file['absolute_path'] );

if ( false === $real_file_path || 0 !== strpos( $real_file_path, $real_docs_root ) ) {
	wp_die( esc_html__( 'Invalid document path.', 'rewav-docs' ), 403 );
}

// Read file content
global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
}

$markdown = $wp_filesystem->get_contents( $file['absolute_path'] );

if ( false === $markdown ) {
	wp_die( esc_html__( 'Unable to read the document.', 'rewav-docs' ) );
}

$renderer = new Rewav_Docs_Markdown_Renderer();
$rendered_html = $renderer->render( $markdown, $file['absolute_path'] );
?>

<div class="wrap rewav-docs-wrap">
	<nav aria-label="<?php esc_attr_e( 'Documentation breadcrumbs', 'rewav-docs' ); ?>">
		<p class="breadcrumbs">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rewav-docs' ) ); ?>"><?php esc_html_e( 'Documentation', 'rewav-docs' ); ?></a>
			&raquo;
			<?php echo esc_html( $file['section'] ); ?>
			&raquo;
			<?php echo esc_html( $file['title'] ); ?>
		</p>
	</nav>

	<div class="rewav-docs-header">
		<h1 class="wp-heading-inline"><?php echo esc_html( $file['title'] ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rewav-docs' ) ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Back to All Documents', 'rewav-docs' ); ?>
		</a>
		<hr class="wp-header-end">
	</div>

	<div class="rewav-docs-main-layout">
		<aside class="rewav-docs-sidebar">
			<nav aria-label="<?php esc_attr_e( 'Documentation navigation', 'rewav-docs' ); ?>">
				<ul class="rewav-docs-nav-list">
					<?php
					$all_files = $scanner->scan();
					$sections = [];
					foreach ( $all_files as $f ) {
						$sections[ $f['section'] ][] = $f;
					}
					ksort( $sections );

					foreach ( $sections as $section_name => $section_files ) :
					?>
						<li class="rewav-docs-nav-section">
							<span class="rewav-docs-nav-section-title"><?php echo esc_html( $section_name ); ?></span>
							<ul class="rewav-docs-nav-sublist">
								<?php foreach ( $section_files as $section_file ) : ?>
									<li class="rewav-docs-nav-item <?php echo $section_file['slug'] === $doc_slug ? 'is-active' : ''; ?>">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=rewav-docs&doc=' . $section_file['slug'] ) ); ?>">
											<?php echo esc_html( $section_file['title'] ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</aside>

		<main class="rewav-docs-content-area card">
			<article class="rewav-docs-content" role="main">
				<?php echo $rendered_html; // Sanitized in renderer. ?>
			</article>
			<footer class="rewav-docs-footer">
				<p class="description">
					<?php
					printf(
						/* translators: %s: date */
						esc_html__( 'Last modified: %s', 'rewav-docs' ),
						esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $file['modified_time'] ) )
					);
					?>
				</p>
			</footer>
		</main>
	</div>
</div>
