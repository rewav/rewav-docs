<?php
/**
 * Provide a admin area view for the settings page.
 */

if ( ! current_user_can( apply_filters( 'rewav_docs_manage_capability', 'rewav_docs_manage' ) ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rewav-docs' ) );
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form action="options.php" method="post">
		<?php
		settings_fields( 'rewav_docs_options_group' );
		do_settings_sections( 'rewav_docs_settings' );
		submit_button();
		?>
	</form>

	<hr>

	<div class="card">
		<h2><?php esc_html_e( 'Actions', 'rewav-docs' ); ?></h2>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'rewav_docs_refresh_index' ); ?>
			<input type="hidden" name="action" value="rewav_docs_refresh_index">
			<p>
				<?php esc_html_e( 'If you have added or removed documentation files, you may need to refresh the index to see the changes.', 'rewav-docs' ); ?>
			</p>
			<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Refresh File Index', 'rewav-docs' ); ?>">
		</form>
	</div>
</div>
