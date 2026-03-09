<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Rewav_Docs_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/rewav-docs-admin.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/rewav-docs-admin.js', [ 'jquery' ], $this->version, false );

		$options = get_option( 'rewav_docs_options', [] );
		$mermaid_enabled = isset( $options['enable_mermaid'] ) ? (bool) $options['enable_mermaid'] : false;

		if ( $mermaid_enabled && isset( $_GET['page'] ) && 'rewav-docs' === $_GET['page'] && isset( $_GET['doc'] ) ) {
			wp_enqueue_script( 'mermaid', 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js', [], '11.0.0', true );
			wp_add_inline_script( 'mermaid', 'mermaid.initialize({ startOnLoad: true });' );
		}
	}

	/**
	 * Add the plugin menu to the admin panel.
	 */
	public function add_plugin_admin_menu() {
		$view_capability = apply_filters( 'rewav_docs_view_capability', 'rewav_docs_view' );
		$manage_capability = apply_filters( 'rewav_docs_manage_capability', 'rewav_docs_manage' );

		add_menu_page(
			__( 'Documentation', 'rewav-docs' ),
			__( 'Documentation', 'rewav-docs' ),
			$view_capability,
			$this->plugin_name,
			[ $this, 'display_plugin_admin_page' ],
			'dashicons-media-document',
			apply_filters( 'rewav_docs_menu_position', 3 )
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'All Documents', 'rewav-docs' ),
			__( 'All Documents', 'rewav-docs' ),
			$view_capability,
			$this->plugin_name,
			[ $this, 'display_plugin_admin_page' ]
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'Settings', 'rewav-docs' ),
			__( 'Settings', 'rewav-docs' ),
			$manage_capability,
			$this->plugin_name . '-settings',
			[ $this, 'display_plugin_settings_page' ]
		);
	}

	/**
	 * Handle admin notices.
	 */
	public function display_admin_notices() {
		if ( isset( $_GET['refreshed'] ) && '1' === $_GET['refreshed'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Documentation index refreshed successfully.', 'rewav-docs' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Refresh the file scanner index.
	 */
	public function refresh_index() {
		check_admin_referer( 'rewav_docs_refresh_index' );

		if ( ! current_user_can( apply_filters( 'rewav_docs_manage_capability', 'rewav_docs_manage' ) ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'rewav-docs' ) );
		}

		$scanner = new Rewav_Docs_File_Scanner();
		$scanner->scan( true );

		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-settings&refreshed=1' ) );
		exit;
	}

	/**
	 * Render the admin page for this plugin.
	 */
	public function display_plugin_admin_page() {
		$doc_slug = isset( $_GET['doc'] ) ? sanitize_text_field( wp_unslash( $_GET['doc'] ) ) : '';

		if ( ! empty( $doc_slug ) ) {
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/rewav-docs-document-view.php';
		} else {
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/rewav-docs-admin-display.php';
		}
	}

	/**
	 * Render the settings page for this plugin.
	 */
	public function display_plugin_settings_page() {
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/rewav-docs-settings-page.php';
	}

}
