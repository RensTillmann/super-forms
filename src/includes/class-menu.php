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
            esc_html__( 'Support', 'super-forms' ), 
            esc_html__( 'Support', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_suport'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'Add-ons', 'super-forms' ), 
            esc_html__( 'Add-ons', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_addons'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'FAQ', 'super-forms' ), 
            esc_html__( 'FAQ', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_faq'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'What\'s New?', 'super-forms' ), 
            esc_html__( 'What\'s New?', 'super-forms' ), 
            'manage_options', 
            'edit.php?post_type=super_suport&super_whats_new=true'
        );
        add_submenu_page( 
            'super_forms', 
            esc_html__( 'Marketplace', 'super-forms' ), 
            esc_html__( 'Marketplace', 'super-forms' ), 
            'manage_options',
            'super_marketplace' ,
            'SUPER_Pages::marketplace'
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
            $submenu['super_forms'][5][2] = 'https://renstillmann.github.io/super-forms/#/';
            $submenu['super_forms'][6][2] = 'http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866/support';
            $submenu['super_forms'][7][2] = 'https://renstillmann.github.io/super-forms/#/add-ons';
            $submenu['super_forms'][8][2] = 'https://renstillmann.github.io/super-forms/#/faq';
        }
    }
}
endif;