<?php
/**
 * Provide a admin area view for the plugin index page.
 */

if ( ! current_user_can( apply_filters( 'rewav_docs_view_capability', 'rewav_docs_view' ) ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rewav-docs' ) );
}

?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<hr class="wp-header-end">

	<?php
	$scanner = new Rewav_Docs_File_Scanner();
	$files = $scanner->scan();

	if ( empty( $files ) ) :
	?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'No documentation files found. Please check your settings and documentation path.', 'rewav-docs' ); ?></p>
		</div>
		<div class="card">
			<h2><?php esc_html_e( 'Getting Started', 'rewav-docs' ); ?></h2>
			<p>
				<?php
				$docs_path = $scanner->get_docs_path();
				printf(
					/* translators: %s: documentation path */
					wp_kses_post( __( 'Create a folder at %s and add some <code>.md</code> files to get started.', 'rewav-docs' ) ),
					'<code>' . esc_html( $docs_path ) . '</code>'
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rewav-docs-settings' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Configure Settings', 'rewav-docs' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Title', 'rewav-docs' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Section', 'rewav-docs' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Last Modified', 'rewav-docs' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'rewav-docs' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $files as $file ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=rewav-docs&doc=' . $file['slug'] ) ); ?>">
									<?php echo esc_html( $file['title'] ); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html( $file['section'] ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $file['modified_time'] ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=rewav-docs&doc=' . $file['slug'] ) ); ?>" class="button button-small">
								<?php esc_html_e( 'View', 'rewav-docs' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
