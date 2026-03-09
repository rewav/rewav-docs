<?php

/**
 * Handles the role/capability management.
 */
class Rewav_Docs_Capabilities {

	/**
	 * Define the default capabilities for each role.
	 *
	 * @return array Default capabilities.
	 */
	public function get_default_capabilities() {
		return apply_filters( 'rewav_docs_default_capabilities', [
			'administrator' => [ 'rewav_docs_view', 'rewav_docs_manage' ],
			'editor'        => [ 'rewav_docs_view' ],
		] );
	}

	/**
	 * Add custom capabilities to roles.
	 */
	public function add_capabilities() {
		$default_caps = $this->get_default_capabilities();

		foreach ( $default_caps as $role_name => $caps ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Remove custom capabilities from roles.
	 */
	public function remove_capabilities() {
		$default_caps = $this->get_default_capabilities();

		foreach ( $default_caps as $role_name => $caps ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}
}
