<?php
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) 
	exit();

/**
 *  @since 				1.0.0
 *	@deprecated @since 	1.0.8.3
 *
 * We do no longer want to delete the Super Forms settings because
 * this will cause trouble when users forget to export their settings
 *
*/
//delete_option('super_settings');