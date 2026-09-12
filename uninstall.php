<?php
/**
 * Clean up on uninstall.
 *
 * @package ShitateDemoScale
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sds_settings' );
delete_site_transient( 'sds_github_release' );
