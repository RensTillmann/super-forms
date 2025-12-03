<?php
/**
 * Register Post Types
 *
 * @author      WebRehab
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Post_Types
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Post_Types' ) ) :

	/**
	 * SUPER_Post_Types Class
	 */
	class SUPER_Post_Types {

		/**
		 * Register Post TYpes when WordPress Initialises.
		 *
		 *  @since      1.0.0
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		}

		/**
		 *  Register core post types
		 *
		 *  @since      1.0.0
		 */
		public static function register_post_types() {

			// REMOVED: Legacy super_form post type registration
			// Forms now stored in custom table wp_superforms_forms via SUPER_Form_DAL
			// See class-form-dal.php for new data access layer

			if ( ! post_type_exists( 'super_contact_entry' ) ) {
				register_post_type(
					'super_contact_entry',
					apply_filters(
						'super_register_post_type_contact_entry',
						array(
							'label'               => 'Super Forms',
							'description'         => '',
							'capability_type'     => 'post',
							'exclude_from_search' => true, // @since 2.6.0 - make sure to exclude from default search
							'public'              => false,
							'query_var'           => false,
							'has_archive'         => false,
							'publicaly_queryable' => false,
							'show_ui'             => true,
							'show_in_menu'        => false,
							'map_meta_cap'        => true,
							'hierarchical'        => false,
							'supports'            => array(),
							'capabilities'        => array(
								'create_posts' => false, // Removes support for the "Add New" function
							),
							'rewrite'             => array(
								'slug'       => 'super_contact_entry',
								'with_front' => true,
							),
							'labels'              => array(
								'name'               => 'Contact Entries',
								'singular_name'      => 'Contact Entry',
								'menu_name'          => 'Contact Entries',
								'add_new'            => 'Add Contact Entry',
								'add_new_item'       => 'Add New Contact Entry',
								'edit'               => 'Edit',
								'edit_item'          => 'Edit Entry',
								'new_item'           => 'New Entry',
								'view'               => 'View Entry',
								'view_item'          => 'View Entry',
								'search_items'       => 'Search Entries',
								'not_found'          => 'No Entries Found',
								'not_found_in_trash' => 'No Entries Found in Trash',
								'parent'             => 'Parent Entry',
							),
						)
					)
				);
			}
		}
	}
endif;

SUPER_Post_Types::init();
