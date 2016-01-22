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
        $form_builder = add_menu_page(
            'Super Forms',
            'Super Forms',
            'manage_options',
            'super_forms',
            'super_forms_callback',
            SUPER_PLUGIN_FILE.'assets/images/logo-small.png',
            '25.1011121314145699215'
        );
        $your_forms = add_submenu_page(
            'super_forms', 
            __('Your Forms','super'), 
            __('Your Forms','super'), 
            'manage_options', 
            'edit.php?post_type=super_form'
        );    
        $create_form = add_submenu_page(
            'super_forms', 
            __('Create Form','super'), 
            __('Create Form','super'), 
            'manage_options', 
            'super_create_form',
            'SUPER_Pages::create_form'
        );
        $settings = add_submenu_page( 
            'super_forms', 
            __('Settings','super'), 
            __('Settings','super'), 
            'manage_options', 
            'super_settings',
            'SUPER_Pages::settings'
        );
        $entries = add_submenu_page( 
            'super_forms', 
            __('Contact Entries','super'), 
            __('Contact Entries','super'), 
            'manage_options', 
            'edit.php?post_type=super_contact_entry'
        );
        add_submenu_page( 
            'super_forms', 
            __('Import/Export','super'), 
            __('Import/Export','super'), 
            'manage_options', 
            'edit.php?post_type=super_export'
        );
        add_submenu_page( 
            'super_forms', 
            __('Support','super'), 
            __('Support','super'), 
            'manage_options', 
            'edit.php?post_type=super_suport'
        );        
        $view_contact_entries = add_submenu_page( 
            null, 
            __('View contact entry','super'), 
            __('View contact entry','super'), 
            'manage_options', 
            'super_contact_entry',
            'SUPER_Pages::contact_entry'
        );
        unset($submenu['super_forms'][0]);
        if(isset($submenu['super_forms'])){
            $submenu['super_forms'][5][2] = get_admin_url().'export.php';
            $submenu['super_forms'][6][2] = 'http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866/support';
        }
    }
}
endif;