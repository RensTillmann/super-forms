<?php
/**
 * Installation related functions and actions.
 *
 * @author      WebRehab
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Install
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Install' ) ) :

/**
 * SUPER_Install Class
 */
class SUPER_Install {
    
    /** 
	 *	Activation
	 *
	 *	Upon plugin activation save the default settings
	 *
	 *	@since		1.0.0
	 */
	public static function install(){
        //error_log('SUPER_Install::install()');
        flush_rewrite_rules(true); // required for add_rewrite_rule() to be affected after updating the plugin or installing it for the first time
        global $wpdb;
        if(!defined('SUPER_INSTALLING')){
            define('SUPER_INSTALLING', true);
        }
        // Only save settings on first time
        // In case Super Forms is updated or replaced by a newer version
        // do not override to the default settings
        // The following checks if super_settings doesn't exist
        // If it doesn't we can save the default settings (for the first time)
        if(!get_option('super_settings')){
            $default_settings = SUPER_Settings::get_defaults();
            // Now save the settings to the database
            update_option('super_settings', $default_settings);
        }
    }


    /**  
     *  Deactivate
     *
     *  Upon plugin deactivation delete activation
     *
     *  @since      1.9
     */
    public static function deactivate(){       
        //error_log('SUPER_Install::deactivate()');
        flush_rewrite_rules(true); // required for add_rewrite_rule() to be affected after updating the plugin or installing it for the first time
        delete_option('_sf_permalinks_flushed');
        wp_clear_scheduled_hook('super_client_data_garbage_collection');
        wp_clear_scheduled_hook('super_cron_reminders');
        wp_clear_scheduled_hook('super_scheduled_trigger_actions');
        do_action('after_super_forms_deactivated');
    }

}
endif;