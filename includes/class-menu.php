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
            esc_html__( 'Your Forms', 'super-forms' ), 
            esc_html__( 'Your Forms', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_form'
        );    
        add_submenu_page(
            'super_forms', 
            esc_html__( 'Create Form', 'super-forms' ), 
            esc_html__( 'Create Form', 'super-forms' ), 
            'manage_options', 
            'super_create_form',
            'SUPER_Pages::create_form'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'Settings', 'super-forms' ), 
            esc_html__( 'Settings', 'super-forms' ), 
            'manage_options', 
            'super_settings',
            'SUPER_Pages::settings'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'Contact Entries', 'super-forms' ), 
            esc_html__( 'Contact Entries', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_contact_entry'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'Documentation', 'super-forms' ), 
            esc_html__( 'Documentation', 'super-forms' ), 
            'manage_options', 
            'super_documentation',
            'SUPER_Pages::documentation'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'Licenses', 'super-forms' ), 
            esc_html__( 'Licenses', 'super-forms' ), 
            'manage_options', 
            'super_addons',
            'SUPER_Pages::addons'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'What\'s New?', 'super-forms' ), 
            esc_html__( 'What\'s New?', 'super-forms' ), 
            'manage_options', 
            '#'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'Demos', 'super-forms' ), 
            esc_html__( 'Demos', 'super-forms' ), 
            'manage_options',
            'super_demos' ,
            'SUPER_Pages::demos'
        );
        add_submenu_page( 
            null, 
            esc_html__( 'View contact entry', 'super-forms' ), 
            esc_html__( 'View contact entry', 'super-forms' ), 
            'manage_options', 
            'super_contact_entry',
            'SUPER_Pages::contact_entry'
        );
        add_submenu_page( 
            null, 
            esc_html__( 'Contact entries', 'super-forms' ), 
            esc_html__( 'Contact entries', 'super-forms' ), 
            'manage_options', 
            'super_contact_entries',
            'SUPER_Pages::contact_entries'
        );
        unset($submenu['super_forms'][0]);
        if( isset($submenu['super_forms']) ) {
            $submenu['super_forms'][5][2] = 'https://webrehab.zendesk.com/hc';
            $submenu['super_forms'][7][2] = 'https://renstillmann.github.io/super-forms/#/changelog';
        }
    }
}
endif;