<?php
/**
 * Entry Backwards Compatibility Layer
 *
 * Intercepts WordPress functions (get_post, get_post_meta, WP_Query)
 * to route entry operations to the custom table when migration is complete.
 *
 * @package     SUPER_Forms/Classes
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SUPER_Entry_Backwards_Compat' ) ) :

	/**
	 * SUPER_Entry_Backwards_Compat Class
	 *
	 * Provides backwards compatibility for third-party code that uses
	 * WordPress functions to access super_contact_entry post type.
	 *
	 * @since 6.5.0
	 */
	class SUPER_Entry_Backwards_Compat {

		/**
		 * Instance of this class
		 *
		 * @var SUPER_Entry_Backwards_Compat
		 */
		private static $instance = null;

		/**
		 * Cache of converted entries
		 *
		 * @var array
		 */
		private static $entry_cache = array();

		/**
		 * Whether hooks are registered
		 *
		 * @var bool
		 */
		private static $hooks_registered = false;

		/**
		 * Get instance
		 *
		 * @return SUPER_Entry_Backwards_Compat
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			// Register hooks if migration is in progress or completed
			$this->maybe_register_hooks();
		}

		/**
		 * Maybe register hooks based on migration state
		 */
		private function maybe_register_hooks() {
			if ( self::$hooks_registered ) {
				return;
			}

			if ( ! $this->should_intercept() ) {
				return;
			}

			// Intercept get_post() for entry IDs
			add_filter( 'get_post', array( $this, 'intercept_get_post' ), 10, 2 );

			// Intercept get_post_meta() for entry meta
			add_filter( 'get_post_metadata', array( $this, 'intercept_get_post_meta' ), 10, 5 );

			// Intercept update_post_meta() for entry meta
			add_filter( 'update_post_metadata', array( $this, 'intercept_update_post_meta' ), 10, 5 );

			// Intercept add_post_meta() for entry meta
			add_filter( 'add_post_metadata', array( $this, 'intercept_add_post_meta' ), 10, 5 );

			// Intercept delete_post_meta() for entry meta
			add_filter( 'delete_post_metadata', array( $this, 'intercept_delete_post_meta' ), 10, 5 );

			// Intercept WP_Query for post_type = 'super_contact_entry'
			add_action( 'pre_get_posts', array( $this, 'intercept_pre_get_posts' ) );
			add_filter( 'posts_results', array( $this, 'intercept_posts_results' ), 10, 2 );

			// Intercept post existence check
			add_filter( 'post_exists_check', array( $this, 'intercept_post_exists' ), 10, 4 );

			self::$hooks_registered = true;
		}

		/**
		 * Check if we should intercept WordPress functions
		 *
		 * Only intercept when migration is in progress or completed
		 *
		 * @return bool
		 */
		private function should_intercept() {
			if ( ! class_exists( 'SUPER_Entry_DAL' ) ) {
				return false;
			}

			$storage_mode = SUPER_Entry_DAL::get_storage_mode();

			// Intercept when using custom table or during dual-read mode
			return in_array( $storage_mode, array( 'custom_table', 'both' ), true );
		}

		/**
		 * Check if a post ID is an entry
		 *
		 * @param int $post_id Post ID.
		 * @return bool
		 */
		private function is_entry_id( $post_id ) {
			global $wpdb;

			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return false;
			}

			// Check cache first
			if ( isset( self::$entry_cache[ $post_id ] ) ) {
				return true;
			}

			// Check if exists in custom entries table
			$tables = array(
				'entries' => $wpdb->prefix . 'superforms_entries',
			);

			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$tables['entries']} WHERE id = %d LIMIT 1",
				$post_id
			) );

			return (bool) $exists;
		}

		/**
		 * Intercept get_post() for entry IDs
		 *
		 * @param WP_Post|null $post   Post object.
		 * @param int          $post_id Post ID.
		 * @return WP_Post|null
		 */
		public function intercept_get_post( $post, $post_id ) {
			// If WordPress found the post, return it
			if ( $post instanceof WP_Post ) {
				return $post;
			}

			// Check if this is an entry ID
			if ( ! $this->is_entry_id( $post_id ) ) {
				return $post;
			}

			// Get entry from custom table
			$entry = SUPER_Entry_DAL::get( $post_id );
			if ( is_wp_error( $entry ) ) {
				return $post;
			}

			// Convert to WP_Post-like object
			return $this->entry_to_post( $entry );
		}

		/**
		 * Convert entry object to WP_Post-like object
		 *
		 * @param object $entry Entry object.
		 * @return WP_Post
		 */
		public function entry_to_post( $entry ) {
			// Check cache
			if ( isset( self::$entry_cache[ $entry->id ] ) ) {
				return self::$entry_cache[ $entry->id ];
			}

			$post                    = new WP_Post( new stdClass() );
			$post->ID                = (int) $entry->id;
			$post->post_author       = (int) $entry->user_id;
			$post->post_date         = $entry->created_at;
			$post->post_date_gmt     = $entry->created_at_gmt;
			$post->post_content      = '';
			$post->post_title        = $entry->title;
			$post->post_excerpt      = '';
			$post->post_status       = $entry->wp_status;
			$post->comment_status    = 'closed';
			$post->ping_status       = 'closed';
			$post->post_password     = '';
			$post->post_name         = sanitize_title( $entry->title );
			$post->to_ping           = '';
			$post->pinged            = '';
			$post->post_modified     = $entry->updated_at;
			$post->post_modified_gmt = $entry->updated_at_gmt;
			$post->post_content_filtered = '';
			$post->post_parent       = (int) $entry->form_id;
			$post->guid              = '';
			$post->menu_order        = 0;
			$post->post_type         = 'super_contact_entry';
			$post->post_mime_type    = '';
			$post->comment_count     = 0;
			$post->filter            = 'raw';

			// Store reference to actual entry
			$post->_super_entry = $entry;

			// Cache the converted post
			self::$entry_cache[ $entry->id ] = $post;

			return $post;
		}

		/**
		 * Intercept get_post_meta() for entry meta
		 *
		 * @param mixed  $value     Current value (null to use default).
		 * @param int    $object_id Post ID.
		 * @param string $meta_key  Meta key.
		 * @param bool   $single    Single value or array.
		 * @param string $meta_type Meta type.
		 * @return mixed
		 */
		public function intercept_get_post_meta( $value, $object_id, $meta_key, $single, $meta_type = 'post' ) {
			// Only intercept post meta
			if ( 'post' !== $meta_type ) {
				return $value;
			}

			// Check if this is an entry
			if ( ! $this->is_entry_id( $object_id ) ) {
				return $value;
			}

			// Handle known entry meta keys
			$entry_meta_keys = array(
				'_super_contact_entry_ip'              => 'ip_address',
				'_super_contact_entry_status'          => 'entry_status',
				'_super_contact_entry_user_agent'      => 'user_agent',
				'_super_contact_entry_wc_order_id'     => '_wc_order_id',
				'_super_contact_entry_paypal_order_id' => '_paypal_order_id',
				'_super_contact_entry_stripe_session_id' => '_stripe_session_id',
				'_super_test_entry'                    => '_test_entry',
			);

			if ( ! isset( $entry_meta_keys[ $meta_key ] ) ) {
				// Unknown key - check if it's a Super Forms meta key
				if ( strpos( $meta_key, '_super_contact_entry_' ) !== 0 && strpos( $meta_key, '_super_' ) !== 0 ) {
					return $value; // Not our meta key
				}
			}

			// Get entry
			$entry = SUPER_Entry_DAL::get( $object_id );
			if ( is_wp_error( $entry ) ) {
				return $value;
			}

			// Check if it's a column value
			$column_keys = array(
				'_super_contact_entry_ip'         => 'ip_address',
				'_super_contact_entry_status'     => 'entry_status',
				'_super_contact_entry_user_agent' => 'user_agent',
			);

			if ( isset( $column_keys[ $meta_key ] ) ) {
				$column = $column_keys[ $meta_key ];
				$result = isset( $entry->$column ) ? $entry->$column : '';
				return $single ? $result : array( $result );
			}

			// Get from entry_meta table
			$new_key = SUPER_Entry_DAL::get_new_meta_key( $meta_key );
			$result  = SUPER_Entry_DAL::get_meta( $object_id, $new_key, $single );

			// Return in expected format
			if ( $single ) {
				return $result;
			}
			return is_array( $result ) ? $result : array( $result );
		}

		/**
		 * Intercept update_post_meta() for entry meta
		 *
		 * @param null|bool $check      Whether to allow updating.
		 * @param int       $object_id  Post ID.
		 * @param string    $meta_key   Meta key.
		 * @param mixed     $meta_value Meta value.
		 * @param mixed     $prev_value Previous value.
		 * @return null|bool
		 */
		public function intercept_update_post_meta( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
			// Check if this is an entry
			if ( ! $this->is_entry_id( $object_id ) ) {
				return $check;
			}

			// Handle column values
			$column_keys = array(
				'_super_contact_entry_ip'         => 'ip_address',
				'_super_contact_entry_status'     => 'entry_status',
				'_super_contact_entry_user_agent' => 'user_agent',
			);

			if ( isset( $column_keys[ $meta_key ] ) ) {
				$column = $column_keys[ $meta_key ];
				$result = SUPER_Entry_DAL::update( $object_id, array( $column => $meta_value ) );
				return ! is_wp_error( $result );
			}

			// Handle entry_meta values
			if ( strpos( $meta_key, '_super_contact_entry_' ) === 0 || strpos( $meta_key, '_super_' ) === 0 ) {
				$new_key = SUPER_Entry_DAL::get_new_meta_key( $meta_key );
				$result  = SUPER_Entry_DAL::update_meta( $object_id, $new_key, $meta_value );
				return false !== $result;
			}

			return $check;
		}

		/**
		 * Intercept add_post_meta() for entry meta
		 *
		 * @param null|bool $check      Whether to allow adding.
		 * @param int       $object_id  Post ID.
		 * @param string    $meta_key   Meta key.
		 * @param mixed     $meta_value Meta value.
		 * @param bool      $unique     Whether to ensure uniqueness.
		 * @return null|bool
		 */
		public function intercept_add_post_meta( $check, $object_id, $meta_key, $meta_value, $unique ) {
			// Check if this is an entry
			if ( ! $this->is_entry_id( $object_id ) ) {
				return $check;
			}

			// Handle column values (use update instead)
			$column_keys = array(
				'_super_contact_entry_ip'         => 'ip_address',
				'_super_contact_entry_status'     => 'entry_status',
				'_super_contact_entry_user_agent' => 'user_agent',
			);

			if ( isset( $column_keys[ $meta_key ] ) ) {
				$column = $column_keys[ $meta_key ];
				$result = SUPER_Entry_DAL::update( $object_id, array( $column => $meta_value ) );
				return ! is_wp_error( $result );
			}

			// Handle entry_meta values
			if ( strpos( $meta_key, '_super_contact_entry_' ) === 0 || strpos( $meta_key, '_super_' ) === 0 ) {
				$new_key = SUPER_Entry_DAL::get_new_meta_key( $meta_key );

				if ( $unique ) {
					$result = SUPER_Entry_DAL::update_meta( $object_id, $new_key, $meta_value );
				} else {
					$result = SUPER_Entry_DAL::add_meta( $object_id, $new_key, $meta_value );
				}
				return false !== $result;
			}

			return $check;
		}

		/**
		 * Intercept delete_post_meta() for entry meta
		 *
		 * @param null|bool $check      Whether to allow deleting.
		 * @param int       $object_id  Post ID.
		 * @param string    $meta_key   Meta key.
		 * @param mixed     $meta_value Meta value.
		 * @param bool      $delete_all Whether to delete all.
		 * @return null|bool
		 */
		public function intercept_delete_post_meta( $check, $object_id, $meta_key, $meta_value, $delete_all ) {
			// Check if this is an entry
			if ( ! $this->is_entry_id( $object_id ) ) {
				return $check;
			}

			// Handle column values (set to null)
			$column_keys = array(
				'_super_contact_entry_ip'         => 'ip_address',
				'_super_contact_entry_status'     => 'entry_status',
				'_super_contact_entry_user_agent' => 'user_agent',
			);

			if ( isset( $column_keys[ $meta_key ] ) ) {
				$column = $column_keys[ $meta_key ];
				$result = SUPER_Entry_DAL::update( $object_id, array( $column => null ) );
				return ! is_wp_error( $result );
			}

			// Handle entry_meta values
			if ( strpos( $meta_key, '_super_contact_entry_' ) === 0 || strpos( $meta_key, '_super_' ) === 0 ) {
				$new_key = SUPER_Entry_DAL::get_new_meta_key( $meta_key );
				return SUPER_Entry_DAL::delete_meta( $object_id, $new_key, $meta_value );
			}

			return $check;
		}

		/**
		 * Intercept WP_Query pre_get_posts
		 *
		 * Marks queries for super_contact_entry so we can handle results
		 *
		 * @param WP_Query $query Query object.
		 */
		public function intercept_pre_get_posts( $query ) {
			$post_type = $query->get( 'post_type' );

			// Check if querying for entries
			if ( 'super_contact_entry' === $post_type || ( is_array( $post_type ) && in_array( 'super_contact_entry', $post_type, true ) ) ) {
				// Mark this query as an entry query
				$query->set( '_super_entry_query', true );
			}
		}

		/**
		 * Intercept WP_Query posts_results
		 *
		 * If this is an entry query and no results from wp_posts,
		 * query the custom entries table.
		 *
		 * @param array    $posts Array of posts.
		 * @param WP_Query $query Query object.
		 * @return array
		 */
		public function intercept_posts_results( $posts, $query ) {
			// Check if this is an entry query
			if ( ! $query->get( '_super_entry_query' ) ) {
				return $posts;
			}

			// If we already have posts, convert them
			if ( ! empty( $posts ) ) {
				return $posts;
			}

			// Query custom entries table
			$args = array(
				'per_page' => $query->get( 'posts_per_page' ) > 0 ? $query->get( 'posts_per_page' ) : 20,
				'page'     => max( 1, $query->get( 'paged' ) ),
				'order'    => $query->get( 'order' ) ?: 'DESC',
			);

			// Map WP_Query params to Entry DAL params
			if ( $query->get( 'post_parent' ) ) {
				$args['form_id'] = $query->get( 'post_parent' );
			}

			if ( $query->get( 'author' ) ) {
				$args['user_id'] = $query->get( 'author' );
			}

			$post_status = $query->get( 'post_status' );
			if ( $post_status && 'any' !== $post_status ) {
				$args['wp_status'] = $post_status;
			}

			if ( $query->get( 's' ) ) {
				$args['search'] = $query->get( 's' );
			}

			// Get entries from custom table
			$entries = SUPER_Entry_DAL::query( $args );

			// Convert to WP_Post objects
			$converted = array();
			foreach ( $entries as $entry ) {
				$converted[] = $this->entry_to_post( $entry );
			}

			// Update query found_posts
			$query->found_posts = SUPER_Entry_DAL::count( $args );
			$query->max_num_pages = ceil( $query->found_posts / $args['per_page'] );

			return $converted;
		}

		/**
		 * Intercept post existence check
		 *
		 * @param int|null $post_id   Post ID if found.
		 * @param string   $title     Post title.
		 * @param string   $content   Post content.
		 * @param string   $date      Post date.
		 * @return int|null
		 */
		public function intercept_post_exists( $post_id, $title, $content, $date ) {
			// Not applicable for entries
			return $post_id;
		}

		/**
		 * Clear entry cache
		 *
		 * @param int $entry_id Optional specific entry ID to clear.
		 */
		public static function clear_cache( $entry_id = null ) {
			if ( null === $entry_id ) {
				self::$entry_cache = array();
			} else {
				unset( self::$entry_cache[ $entry_id ] );
			}
		}
	}

endif;

// Initialize backwards compatibility on plugins_loaded
add_action( 'plugins_loaded', array( 'SUPER_Entry_Backwards_Compat', 'instance' ), 20 );
