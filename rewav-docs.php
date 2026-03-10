<?php
/**
 * Plugin Name:       Rewav Docs
 * Plugin URI:        https://rewav.co
 * Description:       Display Markdown documentation files from your filesystem directly inside the WordPress Dashboard.
 * Version:           1.0.2
 * Requires at least: 6.3
 * Requires PHP:      8.1
 * Author:            Manuel Padilla
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rewav-docs
 * Domain Path:       /languages
 */

/**
 * Copyright (c) 2026. All rights reserved.
 * This software is licensed under the GPL-2.0-or-later license.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently used version of the plugin.
 */
define( 'REWAV_DOCS_VERSION', '1.0.2' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rewav-docs.php';

/**
 * The code that runs during plugin activation.
 */
function activate_rewav_docs() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rewav-docs-capabilities.php';
	$capabilities = new Rewav_Docs_Capabilities();
	$capabilities->add_capabilities();
}

register_activation_hook( __FILE__, 'activate_rewav_docs' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function run_rewav_docs() {
	$plugin = new Rewav_Docs();
	$plugin->run();
}
run_rewav_docs();
