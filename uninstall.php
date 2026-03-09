<?php

/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-rewav-docs-capabilities.php';

$capabilities = new Rewav_Docs_Capabilities();
$capabilities->remove_capabilities();

delete_option( 'rewav_docs_options' );
delete_transient( 'rewav_docs_file_index' );
