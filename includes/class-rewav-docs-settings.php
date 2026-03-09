<?php

/**
 * Handles the plugin settings.
 */
class Rewav_Docs_Settings {

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
	 * Register the plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'rewav_docs_options_group',
			'rewav_docs_options',
			[ $this, 'sanitize_settings' ]
		);

		add_settings_section(
			'rewav_docs_general_section',
			__( 'General Settings', 'rewav-docs' ),
			[ $this, 'general_section_callback' ],
			'rewav_docs_settings'
		);

		add_settings_field(
			'path',
			__( 'Documentation Path', 'rewav-docs' ),
			[ $this, 'path_callback' ],
			'rewav_docs_settings',
			'rewav_docs_general_section'
		);

		add_settings_field(
			'scan_depth',
			__( 'Scan Depth', 'rewav-docs' ),
			[ $this, 'scan_depth_callback' ],
			'rewav_docs_settings',
			'rewav_docs_general_section'
		);
	}

	/**
	 * Sanitize the settings.
	 *
	 * @param array $input Input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = [];

		if ( isset( $input['path'] ) ) {
			$sanitized['path'] = sanitize_text_field( $input['path'] );
		}

		if ( isset( $input['scan_depth'] ) ) {
			$sanitized['scan_depth'] = absint( $input['scan_depth'] );
		}

		return $sanitized;
	}

	/**
	 * Callback for the general section.
	 */
	public function general_section_callback() {
		_e( 'Configure the core settings for Rewav Docs.', 'rewav-docs' );
	}

	/**
	 * Callback for the path field.
	 */
	public function path_callback() {
		$options = get_option( 'rewav_docs_options', [] );
		$path = isset( $options['path'] ) ? $options['path'] : '';
		$disabled = defined( 'REWAV_DOCS_PATH' ) ? 'disabled' : '';

		echo '<input type="text" name="rewav_docs_options[path]" value="' . esc_attr( $path ) . '" class="regular-text" ' . esc_attr( $disabled ) . ' />';
		if ( $disabled ) {
			echo '<p class="description">' . wp_kses_post( __( 'This setting is currently overridden by the <code>REWAV_DOCS_PATH</code> constant in wp-config.php.', 'rewav-docs' ) ) . '</p>';
		} else {
			echo '<p class="description">' . wp_kses_post( __( 'Absolute path to the documentation folder. Default: <code>wp-content/uploads/rewav-docs</code>.', 'rewav-docs' ) ) . '</p>';
		}
	}

	/**
	 * Callback for the scan depth field.
	 */
	public function scan_depth_callback() {
		$options = get_option( 'rewav_docs_options', [] );
		$depth = isset( $options['scan_depth'] ) ? (int) $options['scan_depth'] : 3;
		$disabled = defined( 'REWAV_DOCS_SCAN_DEPTH' ) ? 'disabled' : '';

		echo '<input type="number" name="rewav_docs_options[scan_depth]" value="' . esc_attr( $depth ) . '" min="1" max="10" ' . esc_attr( $disabled ) . ' />';
		if ( $disabled ) {
			echo '<p class="description">' . wp_kses_post( __( 'This setting is currently overridden by the <code>REWAV_DOCS_SCAN_DEPTH</code> constant in wp-config.php.', 'rewav-docs' ) ) . '</p>';
		}
	}
}
