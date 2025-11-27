<?php
/**
 * Entry Data Access Layer
 *
 * Single source of truth for all entry operations.
 * Replaces direct access to super_contact_entry post type.
 *
 * @package     SUPER_Forms/Classes
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SUPER_Entry_DAL' ) ) :

	/**
	 * SUPER_Entry_DAL Class
	 *
	 * Provides CRUD operations and meta management for entries
	 * stored in wp_superforms_entries and wp_superforms_entry_meta tables.
	 *
	 * @since 6.5.0
	 */
	class SUPER_Entry_DAL {

		/**
		 * Migration state cache
		 *
		 * @var array|null
		 */
		private static $migration_state = null;

		/**
		 * Table names cache
		 *
		 * @var array
		 */
		private static $tables = array();

		/**
		 * Get table names
		 *
		 * @return array Table names with wpdb prefix
		 */
		private static function get_tables() {
			if ( empty( self::$tables ) ) {
				global $wpdb;
				self::$tables = array(
					'entries'    => $wpdb->prefix . 'superforms_entries',
					'entry_meta' => $wpdb->prefix . 'superforms_entry_meta',
					'entry_data' => $wpdb->prefix . 'superforms_entry_data',
				);
			}
			return self::$tables;
		}

		// =============================================
		// CORE CRUD OPERATIONS
		// =============================================

		/**
		 * Get entry by ID
		 *
		 * @param int $entry_id Entry ID.
		 * @return object|WP_Error Entry object or error.
		 */
		public static function get( $entry_id ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );
			if ( $entry_id < 1 ) {
				return new WP_Error( 'invalid_entry_id', __( 'Invalid entry ID.', 'super-forms' ) );
			}

			// Check migration state - during migration, check custom table first
			$storage_mode = self::get_storage_mode();

			if ( 'post_type' === $storage_mode ) {
				// Not migrated yet - use post type
				return self::get_from_post_type( $entry_id );
			}

			// Try custom table first
			$entry = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$tables['entries']} WHERE id = %d",
				$entry_id
			) );

			if ( $entry ) {
				return $entry;
			}

			// If dual-read mode and not found in custom table, check post type
			if ( 'both' === $storage_mode ) {
				return self::get_from_post_type( $entry_id );
			}

			return new WP_Error( 'entry_not_found', __( 'Entry not found.', 'super-forms' ) );
		}

		/**
		 * Get entry from post type (backwards compatibility)
		 *
		 * @param int $entry_id Entry ID.
		 * @return object|WP_Error Entry object or error.
		 */
		private static function get_from_post_type( $entry_id ) {
			$post = get_post( $entry_id );

			if ( ! $post || 'super_contact_entry' !== $post->post_type ) {
				return new WP_Error( 'entry_not_found', __( 'Entry not found.', 'super-forms' ) );
			}

			// Convert to entry object format
			return self::post_to_entry( $post );
		}

		/**
		 * Convert WP_Post to entry object
		 *
		 * @param WP_Post $post WordPress post object.
		 * @return object Entry object.
		 */
		private static function post_to_entry( $post ) {
			$entry = new stdClass();
			$entry->id              = $post->ID;
			$entry->form_id         = $post->post_parent;
			$entry->user_id         = $post->post_author;
			$entry->title           = $post->post_title;
			$entry->wp_status       = $post->post_status;
			$entry->entry_status    = get_post_meta( $post->ID, '_super_contact_entry_status', true );
			$entry->created_at      = $post->post_date;
			$entry->created_at_gmt  = $post->post_date_gmt;
			$entry->updated_at      = $post->post_modified;
			$entry->updated_at_gmt  = $post->post_modified_gmt;
			$entry->ip_address      = get_post_meta( $post->ID, '_super_contact_entry_ip', true );
			$entry->user_agent      = get_post_meta( $post->ID, '_super_contact_entry_user_agent', true );
			$entry->session_id      = null;
			$entry->_from_post_type = true; // Flag for debugging

			return $entry;
		}

		/**
		 * Create new entry
		 *
		 * NEW entries are ALWAYS created in the custom table, regardless of migration state.
		 * This simplifies the architecture - no dual-write mode needed.
		 * Existing entries in wp_posts are read via backwards compatibility layer.
		 *
		 * @since 6.5.0 - Always write to custom table (no dual-write mode)
		 * @param array $data Entry data.
		 * @return int|WP_Error Entry ID or error.
		 */
		public static function create( $data ) {
			global $wpdb;
			$tables = self::get_tables();

			// Validate required fields
			if ( empty( $data['form_id'] ) ) {
				return new WP_Error( 'missing_form_id', __( 'Form ID is required.', 'super-forms' ) );
			}

			// Set defaults
			$now     = current_time( 'mysql' );
			$now_gmt = current_time( 'mysql', 1 );

			$insert_data = array(
				'form_id'        => absint( $data['form_id'] ),
				'user_id'        => isset( $data['user_id'] ) ? absint( $data['user_id'] ) : get_current_user_id(),
				'title'          => isset( $data['title'] ) ? mb_substr( sanitize_text_field( $data['title'] ), 0, 255 ) : '',
				'wp_status'      => isset( $data['wp_status'] ) ? sanitize_key( $data['wp_status'] ) : 'publish',
				'entry_status'   => isset( $data['entry_status'] ) ? sanitize_text_field( $data['entry_status'] ) : null,
				'created_at'     => isset( $data['created_at'] ) ? $data['created_at'] : $now,
				'created_at_gmt' => isset( $data['created_at_gmt'] ) ? $data['created_at_gmt'] : $now_gmt,
				'updated_at'     => isset( $data['updated_at'] ) ? $data['updated_at'] : $now,
				'updated_at_gmt' => isset( $data['updated_at_gmt'] ) ? $data['updated_at_gmt'] : $now_gmt,
				'ip_address'     => isset( $data['ip_address'] ) ? sanitize_text_field( $data['ip_address'] ) : null,
				'user_agent'     => isset( $data['user_agent'] ) ? sanitize_text_field( substr( $data['user_agent'], 0, 500 ) ) : null,
				'session_id'     => isset( $data['session_id'] ) ? absint( $data['session_id'] ) : null,
			);

			// Always insert into custom table - no dual-write mode
			// Existing entries in wp_posts are handled by backwards compatibility layer
			$result = $wpdb->insert( $tables['entries'], $insert_data );

			if ( false === $result ) {
				return new WP_Error( 'db_insert_error', $wpdb->last_error );
			}

			$entry_id = $wpdb->insert_id;

			/**
			 * Fires after an entry is created
			 *
			 * @param int   $entry_id    Entry ID.
			 * @param array $insert_data Entry data.
			 */
			do_action( 'super_entry_created', $entry_id, $insert_data );

			return $entry_id;
		}

		/**
		 * Update entry
		 *
		 * @param int   $entry_id Entry ID.
		 * @param array $data     Fields to update.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		public static function update( $entry_id, $data ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );
			if ( $entry_id < 1 ) {
				return new WP_Error( 'invalid_entry_id', __( 'Invalid entry ID.', 'super-forms' ) );
			}

			// Check if entry exists
			$entry = self::get( $entry_id );
			if ( is_wp_error( $entry ) ) {
				return $entry;
			}

			// Set updated timestamp
			$now     = current_time( 'mysql' );
			$now_gmt = current_time( 'mysql', 1 );
			$data['updated_at']     = $now;
			$data['updated_at_gmt'] = $now_gmt;

			// Sanitize data
			$update_data = array();
			$allowed_fields = array(
				'form_id', 'user_id', 'title', 'wp_status', 'entry_status',
				'updated_at', 'updated_at_gmt', 'ip_address', 'user_agent', 'session_id',
			);

			foreach ( $allowed_fields as $field ) {
				if ( array_key_exists( $field, $data ) ) {
					$update_data[ $field ] = $data[ $field ];
				}
			}

			if ( empty( $update_data ) ) {
				return new WP_Error( 'no_data', __( 'No data to update.', 'super-forms' ) );
			}

			// Check if entry is in post type (legacy)
			if ( isset( $entry->_from_post_type ) && $entry->_from_post_type ) {
				return self::update_via_post_type( $entry_id, $update_data );
			}

			// Update in custom table
			$result = $wpdb->update(
				$tables['entries'],
				$update_data,
				array( 'id' => $entry_id )
			);

			if ( false === $result ) {
				return new WP_Error( 'db_update_error', $wpdb->last_error );
			}

			/**
			 * Fires after an entry is updated
			 *
			 * @param int   $entry_id    Entry ID.
			 * @param array $update_data Updated data.
			 */
			do_action( 'super_entry_updated', $entry_id, $update_data );

			return true;
		}

		/**
		 * Update entry via post type (backwards compatibility)
		 *
		 * @param int   $entry_id Entry ID.
		 * @param array $data     Fields to update.
		 * @return bool|WP_Error
		 */
		private static function update_via_post_type( $entry_id, $data ) {
			$post_data = array( 'ID' => $entry_id );

			// Map entry fields to post fields
			$field_map = array(
				'title'     => 'post_title',
				'wp_status' => 'post_status',
				'form_id'   => 'post_parent',
				'user_id'   => 'post_author',
			);

			foreach ( $field_map as $entry_field => $post_field ) {
				if ( isset( $data[ $entry_field ] ) ) {
					$post_data[ $post_field ] = $data[ $entry_field ];
				}
			}

			if ( count( $post_data ) > 1 ) {
				$result = wp_update_post( $post_data, true );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}

			// Update meta fields
			if ( isset( $data['ip_address'] ) ) {
				update_post_meta( $entry_id, '_super_contact_entry_ip', $data['ip_address'] );
			}
			if ( isset( $data['entry_status'] ) ) {
				update_post_meta( $entry_id, '_super_contact_entry_status', $data['entry_status'] );
			}
			if ( isset( $data['user_agent'] ) ) {
				update_post_meta( $entry_id, '_super_contact_entry_user_agent', $data['user_agent'] );
			}

			return true;
		}

		/**
		 * Delete entry
		 *
		 * @param int  $entry_id     Entry ID.
		 * @param bool $force_delete Skip trash (permanent delete).
		 * @return bool|WP_Error
		 */
		public static function delete( $entry_id, $force_delete = false ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );
			if ( $entry_id < 1 ) {
				return new WP_Error( 'invalid_entry_id', __( 'Invalid entry ID.', 'super-forms' ) );
			}

			// Check if entry exists
			$entry = self::get( $entry_id );
			if ( is_wp_error( $entry ) ) {
				return $entry;
			}

			// Check if entry is in post type (legacy)
			if ( isset( $entry->_from_post_type ) && $entry->_from_post_type ) {
				if ( $force_delete ) {
					wp_delete_post( $entry_id, true );
				} else {
					wp_trash_post( $entry_id );
				}
				return true;
			}

			if ( $force_delete ) {
				// Delete entry meta first
				self::delete_all_meta( $entry_id );

				// Delete entry data (form field values)
				$wpdb->delete( $tables['entry_data'], array( 'entry_id' => $entry_id ) );

				// Delete entry
				$result = $wpdb->delete( $tables['entries'], array( 'id' => $entry_id ) );

				if ( false === $result ) {
					return new WP_Error( 'db_delete_error', $wpdb->last_error );
				}

				/**
				 * Fires after an entry is permanently deleted
				 *
				 * @param int $entry_id Entry ID.
				 */
				do_action( 'super_entry_deleted', $entry_id );
			} else {
				// Move to trash
				$result = self::update( $entry_id, array( 'wp_status' => 'trash' ) );
				if ( is_wp_error( $result ) ) {
					return $result;
				}

				/**
				 * Fires after an entry is trashed
				 *
				 * @param int $entry_id Entry ID.
				 */
				do_action( 'super_entry_trashed', $entry_id );
			}

			return true;
		}

		/**
		 * Restore entry from trash
		 *
		 * @param int $entry_id Entry ID.
		 * @return bool|WP_Error
		 */
		public static function restore( $entry_id ) {
			$entry = self::get( $entry_id );
			if ( is_wp_error( $entry ) ) {
				return $entry;
			}

			// Check if entry is in post type (legacy)
			if ( isset( $entry->_from_post_type ) && $entry->_from_post_type ) {
				wp_untrash_post( $entry_id );
				// wp_untrash_post restores to draft, we need publish
				wp_update_post( array(
					'ID'          => $entry_id,
					'post_status' => 'publish',
				) );
				return true;
			}

			$result = self::update( $entry_id, array( 'wp_status' => 'publish' ) );

			if ( ! is_wp_error( $result ) ) {
				/**
				 * Fires after an entry is restored from trash
				 *
				 * @param int $entry_id Entry ID.
				 */
				do_action( 'super_entry_restored', $entry_id );
			}

			return $result;
		}

		// =============================================
		// QUERY METHODS
		// =============================================

		/**
		 * Query entries with flexible parameters
		 *
		 * @param array $args Query arguments.
		 * @return array Array of entry objects.
		 */
		public static function query( $args = array() ) {
			global $wpdb;
			$tables = self::get_tables();

			$defaults = array(
				'form_id'      => null,
				'user_id'      => null,
				'wp_status'    => null,
				'entry_status' => null,
				'search'       => null,
				'orderby'      => 'created_at',
				'order'        => 'DESC',
				'per_page'     => 20,
				'limit'        => null, // Alias for per_page
				'page'         => 1,
				'offset'       => null,
			);

			$args = wp_parse_args( $args, $defaults );

			// Handle 'limit' as alias for 'per_page'
			if ( null !== $args['limit'] ) {
				$args['per_page'] = $args['limit'];
			}

			// Check storage mode
			$storage_mode = self::get_storage_mode();

			if ( 'post_type' === $storage_mode ) {
				return self::query_via_post_type( $args );
			}

			// Build query
			$where   = array( '1=1' );
			$values  = array();

			if ( null !== $args['form_id'] ) {
				$where[]  = 'form_id = %d';
				$values[] = absint( $args['form_id'] );
			}

			if ( null !== $args['user_id'] ) {
				$where[]  = 'user_id = %d';
				$values[] = absint( $args['user_id'] );
			}

			if ( null !== $args['wp_status'] ) {
				if ( is_array( $args['wp_status'] ) ) {
					$placeholders = implode( ', ', array_fill( 0, count( $args['wp_status'] ), '%s' ) );
					$where[]      = "wp_status IN ($placeholders)";
					$values       = array_merge( $values, array_map( 'sanitize_key', $args['wp_status'] ) );
				} else {
					$where[]  = 'wp_status = %s';
					$values[] = sanitize_key( $args['wp_status'] );
				}
			}

			if ( null !== $args['entry_status'] ) {
				$where[]  = 'entry_status = %s';
				$values[] = sanitize_text_field( $args['entry_status'] );
			}

			if ( ! empty( $args['search'] ) ) {
				$where[]  = 'title LIKE %s';
				$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			}

			// Validate orderby
			$allowed_orderby = array( 'id', 'form_id', 'user_id', 'title', 'wp_status', 'entry_status', 'created_at', 'updated_at' );
			$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
			$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			// Build LIMIT clause
			$per_page = absint( $args['per_page'] );
			$page     = max( 1, absint( $args['page'] ) );
			$offset   = null !== $args['offset'] ? absint( $args['offset'] ) : ( $page - 1 ) * $per_page;

			$where_clause = implode( ' AND ', $where );

			$sql = "SELECT * FROM {$tables['entries']} WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
			$values[] = $per_page;
			$values[] = $offset;

			if ( count( $values ) > 2 ) {
				$entries = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
			} else {
				// No WHERE conditions other than 1=1
				$entries = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM {$tables['entries']} ORDER BY $orderby $order LIMIT %d OFFSET %d",
					$per_page,
					$offset
				) );
			}

			return $entries ? $entries : array();
		}

		/**
		 * Query entries via post type (backwards compatibility)
		 *
		 * @param array $args Query arguments.
		 * @return array Array of entry objects.
		 */
		private static function query_via_post_type( $args ) {
			$query_args = array(
				'post_type'      => 'super_contact_entry',
				'posts_per_page' => $args['per_page'],
				'orderby'        => 'date',
				'order'          => $args['order'],
			);

			// Handle offset - if provided directly, use it; otherwise calculate from page
			if ( null !== $args['offset'] ) {
				$query_args['offset'] = $args['offset'];
			} else {
				$query_args['paged'] = $args['page'];
			}

			if ( null !== $args['form_id'] ) {
				$query_args['post_parent'] = $args['form_id'];
			}

			if ( null !== $args['user_id'] ) {
				$query_args['author'] = $args['user_id'];
			}

			if ( null !== $args['wp_status'] ) {
				$query_args['post_status'] = $args['wp_status'];
			} else {
				$query_args['post_status'] = 'any';
			}

			if ( ! empty( $args['search'] ) ) {
				$query_args['s'] = $args['search'];
			}

			$query   = new WP_Query( $query_args );
			$entries = array();

			foreach ( $query->posts as $post ) {
				$entries[] = self::post_to_entry( $post );
			}

			return $entries;
		}

		/**
		 * Count entries matching criteria
		 *
		 * @param array $args Query arguments (same as query()).
		 * @return int
		 */
		public static function count( $args = array() ) {
			global $wpdb;
			$tables = self::get_tables();

			$storage_mode = self::get_storage_mode();

			if ( 'post_type' === $storage_mode ) {
				return self::count_via_post_type( $args );
			}

			$where  = array( '1=1' );
			$values = array();

			if ( ! empty( $args['form_id'] ) ) {
				$where[]  = 'form_id = %d';
				$values[] = absint( $args['form_id'] );
			}

			if ( ! empty( $args['user_id'] ) ) {
				$where[]  = 'user_id = %d';
				$values[] = absint( $args['user_id'] );
			}

			if ( ! empty( $args['wp_status'] ) ) {
				if ( is_array( $args['wp_status'] ) ) {
					$placeholders = implode( ', ', array_fill( 0, count( $args['wp_status'] ), '%s' ) );
					$where[]      = "wp_status IN ($placeholders)";
					$values       = array_merge( $values, array_map( 'sanitize_key', $args['wp_status'] ) );
				} else {
					$where[]  = 'wp_status = %s';
					$values[] = sanitize_key( $args['wp_status'] );
				}
			}

			// @since 6.5.0 - Support title filtering for duplicate detection
			if ( ! empty( $args['title'] ) ) {
				$where[]  = 'title = %s';
				$values[] = sanitize_text_field( $args['title'] );
			}

			// @since 6.5.0 - Support form_ids array for duplicate detection across multiple forms
			if ( ! empty( $args['form_ids'] ) && is_array( $args['form_ids'] ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $args['form_ids'] ), '%d' ) );
				$where[]      = "form_id IN ($placeholders)";
				$values       = array_merge( $values, array_map( 'absint', $args['form_ids'] ) );
			}

			// @since 6.5.0 - Support excluding trashed entries
			if ( ! empty( $args['exclude_trash'] ) ) {
				$where[] = "wp_status != 'trash'";
			}

			$where_clause = implode( ' AND ', $where );

			if ( ! empty( $values ) ) {
				$count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$tables['entries']} WHERE $where_clause",
					$values
				) );
			} else {
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['entries']}" );
			}

			return absint( $count );
		}

		/**
		 * Count entries via post type (backwards compatibility)
		 *
		 * @param array $args Query arguments.
		 * @return int
		 */
		private static function count_via_post_type( $args ) {
			$query_args = array(
				'post_type'      => 'super_contact_entry',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			if ( ! empty( $args['form_id'] ) ) {
				$query_args['post_parent'] = $args['form_id'];
			}

			// @since 6.5.0 - Support form_ids array
			if ( ! empty( $args['form_ids'] ) && is_array( $args['form_ids'] ) ) {
				$query_args['post_parent__in'] = array_map( 'absint', $args['form_ids'] );
			}

			if ( ! empty( $args['user_id'] ) ) {
				$query_args['author'] = $args['user_id'];
			}

			if ( ! empty( $args['wp_status'] ) ) {
				$query_args['post_status'] = $args['wp_status'];
			} elseif ( ! empty( $args['exclude_trash'] ) ) {
				// @since 6.5.0 - Exclude trash if requested
				$query_args['post_status'] = array( 'publish', 'super_unread', 'super_read', 'draft', 'pending' );
			} else {
				$query_args['post_status'] = 'any';
			}

			// @since 6.5.0 - Support title filtering for duplicate detection
			if ( ! empty( $args['title'] ) ) {
				$query_args['title'] = sanitize_text_field( $args['title'] );
			}

			$query = new WP_Query( $query_args );
			return $query->found_posts;
		}

		/**
		 * Get entries by form ID
		 *
		 * @param int   $form_id Form ID.
		 * @param array $args    Additional query args.
		 * @return array
		 */
		public static function get_by_form( $form_id, $args = array() ) {
			$args['form_id'] = $form_id;
			return self::query( $args );
		}

		/**
		 * Get entries by user ID
		 *
		 * @param int   $user_id User ID.
		 * @param array $args    Additional query args.
		 * @return array
		 */
		public static function get_by_user( $user_id, $args = array() ) {
			$args['user_id'] = $user_id;
			return self::query( $args );
		}

		/**
		 * Update entry status
		 *
		 * @param int    $entry_id    Entry ID.
		 * @param string $status      New status.
		 * @param string $status_type 'wp_status' or 'entry_status'.
		 * @return bool|WP_Error
		 */
		public static function update_status( $entry_id, $status, $status_type = 'wp_status' ) {
			$valid_types = array( 'wp_status', 'entry_status' );
			if ( ! in_array( $status_type, $valid_types, true ) ) {
				return new WP_Error( 'invalid_status_type', __( 'Invalid status type.', 'super-forms' ) );
			}

			return self::update( $entry_id, array( $status_type => $status ) );
		}

		/**
		 * Get entry with all field data
		 *
		 * @param int $entry_id Entry ID.
		 * @return array|WP_Error Complete entry with fields.
		 */
		public static function get_complete( $entry_id ) {
			$entry = self::get( $entry_id );
			if ( is_wp_error( $entry ) ) {
				return $entry;
			}

			// Get field data via SUPER_Data_Access
			$entry_data = array();
			if ( class_exists( 'SUPER_Data_Access' ) ) {
				$entry_data = SUPER_Data_Access::get_entry_data( $entry_id );
				if ( is_wp_error( $entry_data ) ) {
					$entry_data = array();
				}
			}

			// Get all meta
			$meta = self::get_all_meta( $entry_id );

			return array(
				'entry'  => $entry,
				'fields' => $entry_data,
				'meta'   => $meta,
			);
		}

		/**
		 * Bulk update entries
		 *
		 * @param array $entry_ids Array of entry IDs.
		 * @param array $data      Fields to update.
		 * @return int Number of entries updated.
		 */
		public static function bulk_update( $entry_ids, $data ) {
			$updated = 0;
			foreach ( $entry_ids as $entry_id ) {
				$result = self::update( $entry_id, $data );
				if ( ! is_wp_error( $result ) ) {
					++$updated;
				}
			}
			return $updated;
		}

		/**
		 * Bulk delete entries
		 *
		 * @param array $entry_ids    Array of entry IDs.
		 * @param bool  $force_delete Skip trash.
		 * @return int Number of entries deleted.
		 */
		public static function bulk_delete( $entry_ids, $force_delete = false ) {
			$deleted = 0;
			foreach ( $entry_ids as $entry_id ) {
				$result = self::delete( $entry_id, $force_delete );
				if ( ! is_wp_error( $result ) ) {
					++$deleted;
				}
			}
			return $deleted;
		}

		// =============================================
		// ENTRY META METHODS
		// =============================================

		/**
		 * Get entry meta value
		 *
		 * @param int    $entry_id Entry ID.
		 * @param string $meta_key Meta key.
		 * @param bool   $single   Return single value or array.
		 * @return mixed Meta value(s) or empty string/array.
		 */
		public static function get_meta( $entry_id, $meta_key, $single = true ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );
			$meta_key = sanitize_key( $meta_key );

			// Check storage mode - if post_type, use postmeta
			$storage_mode = self::get_storage_mode();
			if ( 'post_type' === $storage_mode ) {
				$old_key = self::get_old_meta_key( $meta_key );
				return get_post_meta( $entry_id, $old_key, $single );
			}

			if ( $single ) {
				$value = $wpdb->get_var( $wpdb->prepare(
					"SELECT meta_value FROM {$tables['entry_meta']} WHERE entry_id = %d AND meta_key = %s LIMIT 1",
					$entry_id,
					$meta_key
				) );
				return null !== $value ? maybe_unserialize( $value ) : '';
			} else {
				$results = $wpdb->get_col( $wpdb->prepare(
					"SELECT meta_value FROM {$tables['entry_meta']} WHERE entry_id = %d AND meta_key = %s",
					$entry_id,
					$meta_key
				) );
				return array_map( 'maybe_unserialize', $results );
			}
		}

		/**
		 * Update entry meta (add or update)
		 *
		 * @param int    $entry_id   Entry ID.
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 * @return int|bool Meta ID on success, false on failure.
		 */
		public static function update_meta( $entry_id, $meta_key, $meta_value ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );
			$meta_key = sanitize_key( $meta_key );

			// Check storage mode
			$storage_mode = self::get_storage_mode();
			if ( 'post_type' === $storage_mode ) {
				$old_key = self::get_old_meta_key( $meta_key );
				// WordPress handles serialization internally
				return update_post_meta( $entry_id, $old_key, $meta_value );
			}

			// Serialize for custom table storage
			$meta_value = maybe_serialize( $meta_value );

			// Check if meta exists
			$existing_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$tables['entry_meta']} WHERE entry_id = %d AND meta_key = %s LIMIT 1",
				$entry_id,
				$meta_key
			) );

			if ( $existing_id ) {
				// Update existing
				$result = $wpdb->update(
					$tables['entry_meta'],
					array( 'meta_value' => $meta_value ),
					array( 'id' => $existing_id )
				);
				return false !== $result ? (int) $existing_id : false;
			} else {
				// Insert new
				return self::add_meta( $entry_id, $meta_key, $meta_value );
			}
		}

		/**
		 * Add entry meta (allows duplicate keys)
		 *
		 * @param int    $entry_id   Entry ID.
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 * @return int|bool Meta ID on success, false on failure.
		 */
		public static function add_meta( $entry_id, $meta_key, $meta_value ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );
			$meta_key = sanitize_key( $meta_key );

			// Check storage mode
			$storage_mode = self::get_storage_mode();
			if ( 'post_type' === $storage_mode ) {
				$old_key = self::get_old_meta_key( $meta_key );
				// WordPress handles serialization internally
				return add_post_meta( $entry_id, $old_key, $meta_value );
			}

			// Serialize for custom table storage
			$meta_value = maybe_serialize( $meta_value );

			$result = $wpdb->insert(
				$tables['entry_meta'],
				array(
					'entry_id'   => $entry_id,
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value,
				)
			);

			return false !== $result ? $wpdb->insert_id : false;
		}

		/**
		 * Delete entry meta
		 *
		 * @param int    $entry_id   Entry ID.
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Optional - delete only if value matches.
		 * @return bool
		 */
		public static function delete_meta( $entry_id, $meta_key, $meta_value = '' ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );
			$meta_key = sanitize_key( $meta_key );

			// Check storage mode
			$storage_mode = self::get_storage_mode();
			if ( 'post_type' === $storage_mode ) {
				$old_key = self::get_old_meta_key( $meta_key );
				// WordPress handles serialization internally
				return delete_post_meta( $entry_id, $old_key, $meta_value );
			}

			$where = array(
				'entry_id' => $entry_id,
				'meta_key' => $meta_key,
			);

			if ( '' !== $meta_value ) {
				$where['meta_value'] = maybe_serialize( $meta_value );
			}

			$result = $wpdb->delete( $tables['entry_meta'], $where );
			return false !== $result;
		}

		/**
		 * Get all meta for an entry
		 *
		 * @param int $entry_id Entry ID.
		 * @return array Associative array of meta_key => meta_value.
		 */
		public static function get_all_meta( $entry_id ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );

			// Check storage mode
			$storage_mode = self::get_storage_mode();
			if ( 'post_type' === $storage_mode ) {
				$all_meta = get_post_meta( $entry_id );
				$result   = array();
				foreach ( $all_meta as $key => $values ) {
					// Only include Super Forms entry meta
					if ( strpos( $key, '_super_contact_entry_' ) === 0 ) {
						$new_key            = self::get_new_meta_key( $key );
						$result[ $new_key ] = count( $values ) === 1 ? maybe_unserialize( $values[0] ) : array_map( 'maybe_unserialize', $values );
					}
				}
				return $result;
			}

			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$tables['entry_meta']} WHERE entry_id = %d",
				$entry_id
			) );

			$meta = array();
			foreach ( $results as $row ) {
				$meta[ $row->meta_key ] = maybe_unserialize( $row->meta_value );
			}

			return $meta;
		}

		/**
		 * Delete all meta for an entry
		 *
		 * @param int $entry_id Entry ID.
		 * @return int Number of meta rows deleted.
		 */
		public static function delete_all_meta( $entry_id ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );

			// Check storage mode
			$storage_mode = self::get_storage_mode();
			if ( 'post_type' === $storage_mode ) {
				// Delete via postmeta
				$all_meta = get_post_meta( $entry_id );
				foreach ( array_keys( $all_meta ) as $key ) {
					if ( strpos( $key, '_super_contact_entry_' ) === 0 || strpos( $key, '_super_test_' ) === 0 ) {
						delete_post_meta( $entry_id, $key );
					}
				}
				// Return true on success (delete operation completed)
				return true;
			}

			$result = $wpdb->delete( $tables['entry_meta'], array( 'entry_id' => $entry_id ) );
			return false !== $result;
		}

		// =============================================
		// META KEY MAPPING (OLD <-> NEW)
		// =============================================

		/**
		 * Map new meta key to old postmeta key
		 *
		 * @param string $new_key New meta key.
		 * @return string Old postmeta key.
		 */
		public static function get_old_meta_key( $new_key ) {
			$map = array(
				'_wc_order_id'        => '_super_contact_entry_wc_order_id',
				'_paypal_order_id'    => '_super_contact_entry_paypal_order_id',
				'_stripe_session_id'  => '_super_contact_entry_stripe_session_id',
				'_test_entry'         => '_super_test_entry',
			);

			return isset( $map[ $new_key ] ) ? $map[ $new_key ] : '_super_contact_entry_' . $new_key;
		}

		/**
		 * Map old postmeta key to new meta key
		 *
		 * @param string $old_key Old postmeta key.
		 * @return string New meta key.
		 */
		public static function get_new_meta_key( $old_key ) {
			$map = array(
				'_super_contact_entry_wc_order_id'        => '_wc_order_id',
				'_super_contact_entry_paypal_order_id'    => '_paypal_order_id',
				'_super_contact_entry_stripe_session_id'  => '_stripe_session_id',
				'_super_test_entry'                       => '_test_entry',
			);

			if ( isset( $map[ $old_key ] ) ) {
				return $map[ $old_key ];
			}

			// Generic conversion: remove _super_contact_entry_ prefix
			if ( strpos( $old_key, '_super_contact_entry_' ) === 0 ) {
				$new_key = substr( $old_key, strlen( '_super_contact_entry_' ) );
				// If result doesn't start with underscore, add one
				if ( strpos( $new_key, '_' ) !== 0 ) {
					$new_key = '_' . $new_key;
				}
				return $new_key;
			}

			return $old_key;
		}

		// =============================================
		// STORAGE MODE / MIGRATION HELPERS
		// =============================================

		/**
		 * Check if entry exists
		 *
		 * @param int $entry_id Entry ID.
		 * @return bool
		 */
		public static function exists( $entry_id ) {
			$entry = self::get( $entry_id );
			return ! is_wp_error( $entry );
		}

		/**
		 * Get storage mode
		 *
		 * @return string 'post_type' | 'custom_table' | 'both'
		 */
		public static function get_storage_mode() {
			if ( null === self::$migration_state ) {
				self::$migration_state = get_option( 'superforms_entries_migration', array() );
			}

			$state = isset( self::$migration_state['state'] ) ? self::$migration_state['state'] : 'not_started';

			switch ( $state ) {
				case 'not_started':
					return 'post_type';
				case 'in_progress':
					return 'both';
				case 'completed':
				case 'cleaned':
					return 'custom_table';
				default:
					return 'post_type';
			}
		}

		/**
		 * Clear migration state cache
		 */
		public static function clear_cache() {
			self::$migration_state = null;
		}

		/**
		 * Migrate single entry from post type to custom table
		 *
		 * @param int $entry_id Entry ID.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		public static function migrate_entry( $entry_id ) {
			global $wpdb;
			$tables = self::get_tables();

			$entry_id = absint( $entry_id );

			// Get entry from post type
			$post = get_post( $entry_id );
			if ( ! $post || 'super_contact_entry' !== $post->post_type ) {
				return new WP_Error( 'entry_not_found', __( 'Entry not found in post type.', 'super-forms' ) );
			}

			// Check if already migrated
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$tables['entries']} WHERE id = %d",
				$entry_id
			) );

			if ( $exists ) {
				return true; // Already migrated
			}

			// Get meta values
			$ip_address    = get_post_meta( $entry_id, '_super_contact_entry_ip', true );
			$entry_status  = get_post_meta( $entry_id, '_super_contact_entry_status', true );
			$user_agent    = get_post_meta( $entry_id, '_super_contact_entry_user_agent', true );

			// Insert into custom table with SAME ID
			$result = $wpdb->insert(
				$tables['entries'],
				array(
					'id'             => $entry_id, // Preserve original ID
					'form_id'        => $post->post_parent,
					'user_id'        => $post->post_author,
					'title'          => $post->post_title,
					'wp_status'      => $post->post_status,
					'entry_status'   => $entry_status ? $entry_status : null,
					'created_at'     => $post->post_date,
					'created_at_gmt' => $post->post_date_gmt,
					'updated_at'     => $post->post_modified,
					'updated_at_gmt' => $post->post_modified_gmt,
					'ip_address'     => $ip_address ? $ip_address : null,
					'user_agent'     => $user_agent ? $user_agent : null,
					'session_id'     => null,
				)
			);

			if ( false === $result ) {
				return new WP_Error( 'db_insert_error', $wpdb->last_error );
			}

			// Migrate postmeta to entry_meta
			$meta_keys_to_migrate = array(
				'_super_contact_entry_wc_order_id',
				'_super_contact_entry_paypal_order_id',
				'_super_contact_entry_stripe_session_id',
				'_super_test_entry',
			);

			foreach ( $meta_keys_to_migrate as $old_key ) {
				$value = get_post_meta( $entry_id, $old_key, true );
				if ( $value ) {
					$new_key = self::get_new_meta_key( $old_key );
					$wpdb->insert(
						$tables['entry_meta'],
						array(
							'entry_id'   => $entry_id,
							'meta_key'   => $new_key,
							'meta_value' => maybe_serialize( $value ),
						)
					);
				}
			}

			// Log successful migration
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( "[Super Forms] Migrated entry {$entry_id} to custom table" );
			}

			return true;
		}

		// =============================================
		// QUERY BUILDER HELPERS
		// =============================================

		/**
		 * Get SQL query components for complex queries
		 *
		 * Returns table names, column mappings, and JOIN syntax based on storage mode.
		 * Used by listings.php and other code that needs to build complex SQL queries.
		 *
		 * @since 6.5.0
		 * @return array Query components.
		 */
		public static function get_query_components() {
			global $wpdb;
			$tables = self::get_tables();
			$storage_mode = self::get_storage_mode();

			// When using post_type mode, return wp_posts based components
			if ( 'post_type' === $storage_mode ) {
				return array(
					'storage_mode'   => 'post_type',
					'entries_table'  => $wpdb->posts,
					'entry_meta_table' => $wpdb->postmeta,
					'id_column'      => 'ID',
					'form_id_column' => 'post_parent',
					'user_id_column' => 'post_author',
					'title_column'   => 'post_title',
					'status_column'  => 'post_status',
					'date_column'    => 'post_date',
					'modified_column' => 'post_modified',
					// For status meta join
					'status_meta_join' => "LEFT JOIN {$wpdb->postmeta} AS entry_status ON entry_status.post_id = {entry_alias}.ID AND entry_status.meta_key = '_super_contact_entry_status'",
					'status_meta_column' => 'entry_status.meta_value',
					// For IP meta join
					'ip_meta_join' => "LEFT JOIN {$wpdb->postmeta} AS entry_ip ON entry_ip.post_id = {entry_alias}.ID AND entry_ip.meta_key = '_super_contact_entry_ip'",
					'ip_meta_column' => 'entry_ip.meta_value',
					// For WC order meta join
					'wc_order_meta_join' => "LEFT JOIN {$wpdb->postmeta} AS wc_order_connection ON wc_order_connection.post_id = {entry_alias}.ID AND wc_order_connection.meta_key = '_super_contact_entry_wc_order_id'",
					'wc_order_meta_column' => 'wc_order_connection.meta_value',
					// For PayPal order meta join
					'paypal_order_meta_join' => "LEFT JOIN {$wpdb->postmeta} AS paypal_order_connection ON paypal_order_connection.post_id = {entry_alias}.ID AND paypal_order_connection.meta_key = '_super_contact_entry_paypal_order_id'",
					'paypal_order_meta_column' => 'paypal_order_connection.meta_value',
					// WHERE clause for entry post type
					'where_entry_type' => "post_type = 'super_contact_entry'",
					'where_not_trash'  => "post_status != 'trash'",
				);
			}

			// For custom_table or both modes, use custom table
			return array(
				'storage_mode'   => $storage_mode,
				'entries_table'  => $tables['entries'],
				'entry_meta_table' => $tables['entry_meta'],
				'id_column'      => 'id',
				'form_id_column' => 'form_id',
				'user_id_column' => 'user_id',
				'title_column'   => 'title',
				'status_column'  => 'wp_status',
				'date_column'    => 'created_at',
				'modified_column' => 'updated_at',
				// Status is a column, not meta
				'status_meta_join' => '', // Not needed - it's a column
				'status_meta_column' => 'entry_status', // Direct column
				// IP is a column, not meta
				'ip_meta_join' => '', // Not needed - it's a column
				'ip_meta_column' => 'ip_address', // Direct column
				// For WC order meta join
				'wc_order_meta_join' => "LEFT JOIN {$tables['entry_meta']} AS wc_order_connection ON wc_order_connection.entry_id = {entry_alias}.id AND wc_order_connection.meta_key = '_wc_order_id'",
				'wc_order_meta_column' => 'wc_order_connection.meta_value',
				// For PayPal order meta join
				'paypal_order_meta_join' => "LEFT JOIN {$tables['entry_meta']} AS paypal_order_connection ON paypal_order_connection.entry_id = {entry_alias}.id AND paypal_order_connection.meta_key = '_paypal_order_id'",
				'paypal_order_meta_column' => 'paypal_order_connection.meta_value',
				// WHERE clause - no post_type needed
				'where_entry_type' => '1=1', // Always true
				'where_not_trash'  => "wp_status != 'trash'",
			);
		}

		/**
		 * Build entry query SQL from components
		 *
		 * Helper method that generates a standard entry query SQL based on storage mode.
		 * Handles both post_type and custom_table modes transparently.
		 *
		 * @since 6.5.0
		 * @param array $args Query arguments.
		 * @return array Array with 'sql' and 'values' keys.
		 */
		public static function build_listing_query( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'form_id'       => null,
				'form_ids'      => null,
				'user_id'       => null,
				'wp_status'     => null,
				'entry_status'  => null,
				'search'        => null,
				'orderby'       => 'created_at',
				'order'         => 'DESC',
				'limit'         => 20,
				'offset'        => 0,
				'alias'         => 'entry',
				'select'        => '*',
				'include_meta'  => false, // Include entry_meta columns
				'include_wc'    => false, // Include WooCommerce order JOIN
				'include_paypal' => false, // Include PayPal order JOIN
			);

			$args = wp_parse_args( $args, $defaults );
			$components = self::get_query_components();
			$alias = esc_sql( $args['alias'] );

			// Replace {entry_alias} placeholder in JOIN strings
			$wc_join = str_replace( '{entry_alias}', $alias, $components['wc_order_meta_join'] );
			$paypal_join = str_replace( '{entry_alias}', $alias, $components['paypal_order_meta_join'] );
			$status_join = str_replace( '{entry_alias}', $alias, $components['status_meta_join'] );
			$ip_join = str_replace( '{entry_alias}', $alias, $components['ip_meta_join'] );

			// Build SELECT
			$select = $args['select'];

			// Build FROM
			$from = "{$components['entries_table']} AS {$alias}";

			// Build JOINs
			$joins = array();
			if ( ! empty( $status_join ) ) {
				$joins[] = $status_join;
			}
			if ( ! empty( $ip_join ) ) {
				$joins[] = $ip_join;
			}
			if ( $args['include_wc'] && ! empty( $wc_join ) ) {
				$joins[] = $wc_join;
				$joins[] = "LEFT JOIN {$wpdb->posts} AS wc_order ON wc_order.ID = wc_order_connection.meta_value";
			}
			if ( $args['include_paypal'] && ! empty( $paypal_join ) ) {
				$joins[] = $paypal_join;
				$joins[] = "LEFT JOIN {$wpdb->posts} AS paypal_order ON paypal_order.ID = paypal_order_connection.meta_value";
			}

			// Build WHERE
			$where = array( $components['where_entry_type'], $components['where_not_trash'] );
			$values = array();

			if ( null !== $args['form_id'] ) {
				$where[] = "{$alias}.{$components['form_id_column']} = %d";
				$values[] = absint( $args['form_id'] );
			}

			if ( ! empty( $args['form_ids'] ) && is_array( $args['form_ids'] ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $args['form_ids'] ), '%d' ) );
				$where[] = "{$alias}.{$components['form_id_column']} IN ($placeholders)";
				$values = array_merge( $values, array_map( 'absint', $args['form_ids'] ) );
			}

			if ( null !== $args['user_id'] ) {
				$where[] = "{$alias}.{$components['user_id_column']} = %d";
				$values[] = absint( $args['user_id'] );
			}

			if ( null !== $args['wp_status'] ) {
				$where[] = "{$alias}.{$components['status_column']} = %s";
				$values[] = sanitize_key( $args['wp_status'] );
			}

			if ( null !== $args['entry_status'] ) {
				$where[] = "{$components['status_meta_column']} = %s";
				$values[] = sanitize_text_field( $args['entry_status'] );
			}

			if ( ! empty( $args['search'] ) ) {
				$where[] = "{$alias}.{$components['title_column']} LIKE %s";
				$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			}

			// Build ORDER BY
			$orderby_map = array(
				'id'         => $components['id_column'],
				'form_id'    => $components['form_id_column'],
				'user_id'    => $components['user_id_column'],
				'title'      => $components['title_column'],
				'created_at' => $components['date_column'],
				'updated_at' => $components['modified_column'],
			);
			$orderby_column = isset( $orderby_map[ $args['orderby'] ] ) ? $orderby_map[ $args['orderby'] ] : $components['date_column'];
			$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			// Build SQL
			$join_sql = ! empty( $joins ) ? implode( "\n", $joins ) : '';
			$where_sql = implode( ' AND ', $where );

			$sql = "SELECT {$select}
				FROM {$from}
				{$join_sql}
				WHERE {$where_sql}
				ORDER BY {$alias}.{$orderby_column} {$order}
				LIMIT %d OFFSET %d";

			$values[] = absint( $args['limit'] );
			$values[] = absint( $args['offset'] );

			return array(
				'sql'        => $sql,
				'values'     => $values,
				'components' => $components,
			);
		}
	}

endif;
