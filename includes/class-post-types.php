<?php
/**
 * Register Post Types
 *
 * @author      feeling4design
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Post_Types
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Post_Types' ) ) :

/**
 * SUPER_Post_Types Class
 */
class SUPER_Post_Types {
    
    /**
     * Register Post TYpes when WordPress Initialises.
     *
     *	@since		1.0.0
    */    
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
	}
    
    /** 
	 *	Register core post types
	 *
	 *	@since		1.0.0
	*/
	public static function register_post_types() {
		
        if( !post_type_exists( 'super_form' ) ) {
            register_post_type(
                'super_form', 
                apply_filters( 
                    'super_register_post_type_form', 
                    array(
                        'label' => 'Super Forms',
                        'description' => '',
                        'public' => true,
                        'show_ui' => true,
                        'show_in_menu' => false,
                        'capability_type' => 'post',
                        'map_meta_cap' => true,
                        'hierarchical' => false,
                        'rewrite' => array('slug' => 'super_form', 'with_front' => true),
                        'query_var' => true,
                        'supports' => array(),
                        'labels' => array (
                            'name' => 'Forms',
                            'singular_name' => 'Form',
                            'menu_name' => 'Super Forms',
                            'add_new' => 'Add Form',
                            'add_new_item' => 'Add New Form',
                            'edit' => 'Edit',
                            'edit_item' => 'Edit Form',
                            'new_item' => 'New Form',
                            'view' => 'View Form',
                            'view_item' => 'View Form',
                            'search_items' => 'Search Forms',
                            'not_found' => 'No Forms Found',
                            'not_found_in_trash' => 'No Forms Found in Trash',
                            'parent' => 'Parent Form',
                        )
                    )
                )
            );
        }
        
        if( !post_type_exists( 'super_contact_entry' ) ) {
            register_post_type(
                'super_contact_entry', 
                apply_filters( 
                    'super_register_post_type_contact_entry', 
                    array(
                        'rewrite' => array('slug' => 'super_contact_entry', 'with_front' => true),
                        'capability_type' => 'post',
                        'capabilities' => array(
                            'create_posts' => false, // Removes support for the "Add New" function
                        ),
                        'label' => 'Super Forms',
                        'description' => '',
                        'public' => true,
                        'show_ui' => true,
                        'show_in_menu' => false,
                        'map_meta_cap' => true,
                        'hierarchical' => false,
                        'query_var' => true,
                        'supports' => array(),
                        'labels' => array (
                            'name' => 'Contact Entries',
                            'singular_name' => 'Contact Entry',
                            'menu_name' => 'Contact Entries',
                            'add_new' => 'Add Contact Entry',
                            'add_new_item' => 'Add New Contact Entry',
                            'edit' => 'Edit',
                            'edit_item' => 'Edit Entry',
                            'new_item' => 'New Entry',
                            'view' => 'View Entry',
                            'view_item' => 'View Entry',
                            'search_items' => 'Search Entries',
                            'not_found' => 'No Entries Found',
                            'not_found_in_trash' => 'No Entries Found in Trash',
                            'parent' => 'Parent Entry',
                        )
                    )
                )
            );
        }
        
    }
}
endif;

SUPER_Post_Types::init();