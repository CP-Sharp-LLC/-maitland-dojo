<?php
/**
 * Represents the view for the administration dashboard.
 *
 * Import sample content
 *
 * @package   monstroid_wizard
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

global $monstroid_wizard;

// validate auth data (theme name, key and installation type) before show anything to user
$cherry_auth_data = $monstroid_wizard->check_auth_data();
if ( !$cherry_auth_data ) {
	return;
}

if ( $monstroid_wizard->has_importer() && function_exists( 'cherry_dm_get_admin_template' ) ) {
	cherry_dm_get_admin_template( 'cherry-content-import.php' );
}