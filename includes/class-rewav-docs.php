<?php

/**
 * The core plugin class.
 */
class Rewav_Docs {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var Rewav_Docs_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		if ( defined( 'REWAV_DOCS_VERSION' ) ) {
			$this->version = REWAV_DOCS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'rewav-docs';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( __FILE__ ) . 'class-rewav-docs-loader.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-rewav-docs-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-rewav-docs-file-scanner.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-rewav-docs-markdown-renderer.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-rewav-docs-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-rewav-docs-capabilities.php';
		
		// Load Parsedown
		if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/Parsedown.php' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/Parsedown.php';
		}

		$this->loader = new Rewav_Docs_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
	}
	
	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->plugin_name,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Rewav_Docs_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_settings = new Rewav_Docs_Settings( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_admin_notices' );

		$this->loader->add_action( 'admin_post_rewav_docs_refresh_index', $plugin_admin, 'refresh_index' );
		
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
