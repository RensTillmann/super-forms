<?php
/**
 * Installation related functions and actions.
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Menu
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Menu' ) ) :

/**
 * SUPER_Menu Class
 */
class SUPER_Menu {
    
    /** 
	 *	Add menu items
	 *
	 *	@since		1.0.0
	*/
    public static function register_menu(){
        global $menu, $submenu;
        add_menu_page(
            'Super Forms',
            'Super Forms',
            'manage_options',
            'super_forms',
            'super_forms_callback',
            SUPER_PLUGIN_FILE.'assets/images/logo-small.png',
            '25.1011121314145699215'
        );
        add_submenu_page(
            'super_forms', 
            __( 'Your Forms', 'super-forms' ), 
            __( 'Your Forms', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_form'
        );    
        add_submenu_page(
            'super_forms', 
            __( 'Create Form', 'super-forms' ), 
            __( 'Create Form', 'super-forms' ), 
            'manage_options', 
            'super_create_form',
            'SUPER_Pages::create_form'
        );
        add_submenu_page( 
            'super_forms', 
            __( 'Settings', 'super-forms' ), 
            __( 'Settings', 'super-forms' ), 
            'manage_options', 
            'super_settings',
            'SUPER_Pages::settings'
        );
        add_submenu_page( 
            'super_forms', 
            __( 'Contact Entries', 'super-forms' ), 
            __( 'Contact Entries', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_contact_entry'
        );
        add_submenu_page( 
            'super_forms', 
            __( 'Support', 'super-forms' ), 
            __( 'Support', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_suport'
        );
        add_submenu_page( 
            'super_forms', 
            __( 'Add-ons', 'super-forms' ), 
            __( 'Add-ons', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_suport'
        );
        add_submenu_page( 
            'super_forms', 
            __( 'Marketplace', 'super-forms' ), 
            __( 'Marketplace', 'super-forms' ) . ' (NEW)', 
            'manage_options',
            'super_marketplace' ,
            'SUPER_Pages::marketplace'
        );
        add_submenu_page( 
            null, 
            __( 'View contact entry', 'super-forms' ), 
            __( 'View contact entry', 'super-forms' ), 
            'manage_options', 
            'super_contact_entry',
            'SUPER_Pages::contact_entry'
        );
        add_submenu_page( 
            null, 
            __( 'Contact entries', 'super-forms' ), 
            __( 'Contact entries', 'super-forms' ), 
            'manage_options', 
            'super_contact_entries',
            'SUPER_Pages::contact_entries'
        );
        unset($submenu['super_forms'][0]);
        if(isset($submenu['super_forms'])){
            $submenu['super_forms'][5][2] = 'http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866/support';
            $submenu['super_forms'][6][2] = 'http://f4d.nl/super-forms/add-ons/';
        }
    }
}
endif;