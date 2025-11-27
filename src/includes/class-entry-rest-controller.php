<?php
/**
 * Entry REST API Controller
 *
 * Provides REST API v1 endpoints for contact entries (Phase 17).
 * Supports both custom table and post type storage seamlessly via Entry DAL.
 *
 * Endpoints:
 * - GET    /entries          - List entries with pagination/filtering
 * - POST   /entries          - Create new entry
 * - GET    /entries/{id}     - Get single entry
 * - PUT    /entries/{id}     - Update entry
 * - DELETE /entries/{id}     - Delete entry (soft or permanent)
 * - GET    /entries/{id}/data - Get entry field data (EAV)
 * - POST   /entries/{id}/restore - Restore trashed entry
 *
 * @author      WebRehab
 * @category    API
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Entry_REST_Controller
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Entry_REST_Controller' ) ) :

	/**
	 * SUPER_Entry_REST_Controller Class
	 */
	class SUPER_Entry_REST_Controller extends WP_REST_Controller {

		/**
		 * Namespace
		 *
		 * @var string
		 */
		protected $namespace = 'super-forms/v1';

		/**
		 * Resource name
		 *
		 * @var string
		 */
		protected $rest_base = 'entries';

		/**
		 * Register routes
		 *
		 * @since 6.5.0
		 */
		public function register_routes() {
			// Collection routes
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_item' ),
						'permission_callback' => array( $this, 'create_item_permissions_check' ),
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);

			// Single item routes
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
						'args'                => array(
							'id'      => array(
								'description' => __( 'Unique identifier for the entry.', 'super-forms' ),
								'type'        => 'integer',
								'required'    => true,
							),
							'context' => $this->get_context_param( array( 'default' => 'view' ) ),
						),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_item' ),
						'permission_callback' => array( $this, 'update_item_permissions_check' ),
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_item' ),
						'permission_callback' => array( $this, 'delete_item_permissions_check' ),
						'args'                => array(
							'id'    => array(
								'description' => __( 'Unique identifier for the entry.', 'super-forms' ),
								'type'        => 'integer',
								'required'    => true,
							),
							'force' => array(
								'description' => __( 'Whether to bypass Trash and force deletion.', 'super-forms' ),
								'type'        => 'boolean',
								'default'     => false,
							),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);

			// Entry data (field values)
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)/data',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_entry_data' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Unique identifier for the entry.', 'super-forms' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				)
			);

			// Restore trashed entry
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)/restore',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restore_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Unique identifier for the entry.', 'super-forms' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				)
			);

			// Batch operations
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/batch',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => array(
						'delete'  => array(
							'description' => __( 'Entry IDs to delete.', 'super-forms' ),
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
						),
						'trash'   => array(
							'description' => __( 'Entry IDs to move to trash.', 'super-forms' ),
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
						),
						'restore' => array(
							'description' => __( 'Entry IDs to restore from trash.', 'super-forms' ),
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
						),
					),
				)
			);
		}

		/**
		 * Check if a given request has access to get items.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return bool|WP_Error
		 */
		public function get_items_permissions_check( $request ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to view entries.', 'super-forms' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
			return true;
		}

		/**
		 * Check if a given request has access to get a specific item.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return bool|WP_Error
		 */
		public function get_item_permissions_check( $request ) {
			return $this->get_items_permissions_check( $request );
		}

		/**
		 * Check if a given request has access to create items.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return bool|WP_Error
		 */
		public function create_item_permissions_check( $request ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to create entries.', 'super-forms' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
			return true;
		}

		/**
		 * Check if a given request has access to update a specific item.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return bool|WP_Error
		 */
		public function update_item_permissions_check( $request ) {
			return $this->create_item_permissions_check( $request );
		}

		/**
		 * Check if a given request has access to delete a specific item.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return bool|WP_Error
		 */
		public function delete_item_permissions_check( $request ) {
			return $this->create_item_permissions_check( $request );
		}

		/**
		 * Check if a given request has access to batch operations.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return bool|WP_Error
		 */
		public function batch_items_permissions_check( $request ) {
			return $this->create_item_permissions_check( $request );
		}

		/**
		 * Get a collection of entries.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_items( $request ) {
			$args = array(
				'limit'  => $request->get_param( 'per_page' ) ?: 10,
				'offset' => ( ( $request->get_param( 'page' ) ?: 1 ) - 1 ) * ( $request->get_param( 'per_page' ) ?: 10 ),
			);

			// Filters
			if ( $request->get_param( 'form_id' ) ) {
				$args['form_id'] = absint( $request->get_param( 'form_id' ) );
			}
			if ( $request->get_param( 'status' ) ) {
				$args['wp_status'] = sanitize_text_field( $request->get_param( 'status' ) );
			}
			if ( $request->get_param( 'entry_status' ) ) {
				$args['entry_status'] = sanitize_text_field( $request->get_param( 'entry_status' ) );
			}
			if ( $request->get_param( 'search' ) ) {
				$args['search'] = sanitize_text_field( $request->get_param( 'search' ) );
			}
			if ( $request->get_param( 'user_id' ) ) {
				$args['user_id'] = absint( $request->get_param( 'user_id' ) );
			}

			// Date filters
			if ( $request->get_param( 'after' ) ) {
				$args['date_after'] = sanitize_text_field( $request->get_param( 'after' ) );
			}
			if ( $request->get_param( 'before' ) ) {
				$args['date_before'] = sanitize_text_field( $request->get_param( 'before' ) );
			}

			// Sorting
			$orderby = $request->get_param( 'orderby' ) ?: 'created_at';
			$order = $request->get_param( 'order' ) ?: 'DESC';

			$allowed_orderby = array( 'id', 'title', 'form_id', 'created_at', 'updated_at', 'wp_status' );
			if ( in_array( $orderby, $allowed_orderby, true ) ) {
				$args['orderby'] = $orderby;
				$args['order'] = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
			}

			$entries = SUPER_Entry_DAL::query( $args );
			$total = SUPER_Entry_DAL::count( array_diff_key( $args, array( 'limit' => '', 'offset' => '', 'orderby' => '', 'order' => '' ) ) );

			$data = array();
			foreach ( $entries as $entry ) {
				$data[] = $this->prepare_item_for_response( $entry, $request );
			}

			$response = rest_ensure_response( $data );

			// Add pagination headers
			$max_pages = ceil( $total / ( $request->get_param( 'per_page' ) ?: 10 ) );
			$response->header( 'X-WP-Total', $total );
			$response->header( 'X-WP-TotalPages', $max_pages );

			return $response;
		}

		/**
		 * Get a single entry.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_item( $request ) {
			$entry_id = absint( $request->get_param( 'id' ) );
			$entry = SUPER_Entry_DAL::get( $entry_id );

			if ( ! $entry ) {
				return new WP_Error(
					'rest_entry_not_found',
					__( 'Entry not found.', 'super-forms' ),
					array( 'status' => 404 )
				);
			}

			return rest_ensure_response( $this->prepare_item_for_response( $entry, $request ) );
		}

		/**
		 * Create an entry.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_item( $request ) {
			$data = array(
				'form_id'      => absint( $request->get_param( 'form_id' ) ),
				'title'        => sanitize_text_field( $request->get_param( 'title' ) ?: '' ),
				'entry_status' => sanitize_text_field( $request->get_param( 'entry_status' ) ?: '' ),
				'ip_address'   => sanitize_text_field( $request->get_param( 'ip_address' ) ?: '' ),
				'user_agent'   => sanitize_text_field( $request->get_param( 'user_agent' ) ?: '' ),
			);

			// Validate form_id
			if ( ! $data['form_id'] || get_post_type( $data['form_id'] ) !== 'super_form' ) {
				return new WP_Error(
					'rest_invalid_form',
					__( 'Invalid form ID.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			$entry_id = SUPER_Entry_DAL::create( $data );

			if ( is_wp_error( $entry_id ) ) {
				return $entry_id;
			}

			// Save entry data if provided
			$entry_data = $request->get_param( 'data' );
			if ( is_array( $entry_data ) ) {
				SUPER_Data_Access::save_entry_data( $entry_id, $entry_data );
			}

			// Save meta if provided
			$meta = $request->get_param( 'meta' );
			if ( is_array( $meta ) ) {
				foreach ( $meta as $key => $value ) {
					SUPER_Entry_DAL::update_meta( $entry_id, sanitize_key( $key ), $value );
				}
			}

			$entry = SUPER_Entry_DAL::get( $entry_id );

			$response = rest_ensure_response( $this->prepare_item_for_response( $entry, $request ) );
			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $entry_id ) ) );

			return $response;
		}

		/**
		 * Update an entry.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_item( $request ) {
			$entry_id = absint( $request->get_param( 'id' ) );
			$entry = SUPER_Entry_DAL::get( $entry_id );

			if ( ! $entry ) {
				return new WP_Error(
					'rest_entry_not_found',
					__( 'Entry not found.', 'super-forms' ),
					array( 'status' => 404 )
				);
			}

			$data = array();

			// Only update provided fields
			if ( $request->has_param( 'title' ) ) {
				$data['title'] = sanitize_text_field( $request->get_param( 'title' ) );
			}
			if ( $request->has_param( 'status' ) ) {
				$data['wp_status'] = sanitize_text_field( $request->get_param( 'status' ) );
			}
			if ( $request->has_param( 'entry_status' ) ) {
				$data['entry_status'] = sanitize_text_field( $request->get_param( 'entry_status' ) );
			}

			if ( ! empty( $data ) ) {
				$result = SUPER_Entry_DAL::update( $entry_id, $data );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}

			// Update entry data if provided
			$entry_data = $request->get_param( 'data' );
			if ( is_array( $entry_data ) ) {
				SUPER_Data_Access::save_entry_data( $entry_id, $entry_data );
			}

			// Update meta if provided
			$meta = $request->get_param( 'meta' );
			if ( is_array( $meta ) ) {
				foreach ( $meta as $key => $value ) {
					SUPER_Entry_DAL::update_meta( $entry_id, sanitize_key( $key ), $value );
				}
			}

			$entry = SUPER_Entry_DAL::get( $entry_id );
			return rest_ensure_response( $this->prepare_item_for_response( $entry, $request ) );
		}

		/**
		 * Delete an entry.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_item( $request ) {
			$entry_id = absint( $request->get_param( 'id' ) );
			$force = (bool) $request->get_param( 'force' );

			$entry = SUPER_Entry_DAL::get( $entry_id );
			if ( ! $entry ) {
				return new WP_Error(
					'rest_entry_not_found',
					__( 'Entry not found.', 'super-forms' ),
					array( 'status' => 404 )
				);
			}

			$previous = $this->prepare_item_for_response( $entry, $request );

			$result = SUPER_Entry_DAL::delete( $entry_id, $force );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$response = new WP_REST_Response();
			$response->set_data( array(
				'deleted'  => true,
				'previous' => $previous,
			) );

			return $response;
		}

		/**
		 * Restore a trashed entry.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function restore_item( $request ) {
			$entry_id = absint( $request->get_param( 'id' ) );

			$entry = SUPER_Entry_DAL::get( $entry_id );
			if ( ! $entry ) {
				return new WP_Error(
					'rest_entry_not_found',
					__( 'Entry not found.', 'super-forms' ),
					array( 'status' => 404 )
				);
			}

			$result = SUPER_Entry_DAL::restore( $entry_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$entry = SUPER_Entry_DAL::get( $entry_id );
			return rest_ensure_response( $this->prepare_item_for_response( $entry, $request ) );
		}

		/**
		 * Get entry field data.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_entry_data( $request ) {
			$entry_id = absint( $request->get_param( 'id' ) );

			$entry = SUPER_Entry_DAL::get( $entry_id );
			if ( ! $entry ) {
				return new WP_Error(
					'rest_entry_not_found',
					__( 'Entry not found.', 'super-forms' ),
					array( 'status' => 404 )
				);
			}

			$data = SUPER_Data_Access::get_entry_data( $entry_id );

			if ( is_wp_error( $data ) ) {
				return $data;
			}

			return rest_ensure_response( array(
				'entry_id' => $entry_id,
				'data'     => $data,
			) );
		}

		/**
		 * Batch operations on entries.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_REST_Response
		 */
		public function batch_items( $request ) {
			$results = array(
				'deleted'  => array(),
				'trashed'  => array(),
				'restored' => array(),
				'errors'   => array(),
			);

			// Handle permanent deletions
			$delete_ids = $request->get_param( 'delete' );
			if ( is_array( $delete_ids ) ) {
				foreach ( $delete_ids as $id ) {
					$id = absint( $id );
					$result = SUPER_Entry_DAL::delete( $id, true );
					if ( is_wp_error( $result ) ) {
						$results['errors'][] = array( 'id' => $id, 'error' => $result->get_error_message() );
					} else {
						$results['deleted'][] = $id;
					}
				}
			}

			// Handle trash
			$trash_ids = $request->get_param( 'trash' );
			if ( is_array( $trash_ids ) ) {
				foreach ( $trash_ids as $id ) {
					$id = absint( $id );
					$result = SUPER_Entry_DAL::update( $id, array( 'wp_status' => 'trash' ) );
					if ( is_wp_error( $result ) ) {
						$results['errors'][] = array( 'id' => $id, 'error' => $result->get_error_message() );
					} else {
						$results['trashed'][] = $id;
					}
				}
			}

			// Handle restore
			$restore_ids = $request->get_param( 'restore' );
			if ( is_array( $restore_ids ) ) {
				foreach ( $restore_ids as $id ) {
					$id = absint( $id );
					$result = SUPER_Entry_DAL::restore( $id );
					if ( is_wp_error( $result ) ) {
						$results['errors'][] = array( 'id' => $id, 'error' => $result->get_error_message() );
					} else {
						$results['restored'][] = $id;
					}
				}
			}

			return rest_ensure_response( $results );
		}

		/**
		 * Prepare a single entry for response.
		 *
		 * @param array           $entry   Entry data.
		 * @param WP_REST_Request $request Request object.
		 * @return array
		 */
		public function prepare_item_for_response( $entry, $request ) {
			$context = $request->get_param( 'context' ) ?: 'view';

			$data = array(
				'id'           => (int) $entry['id'],
				'form_id'      => (int) $entry['form_id'],
				'title'        => $entry['title'],
				'status'       => $entry['wp_status'],
				'entry_status' => $entry['entry_status'],
				'created_at'   => $entry['created_at'],
				'updated_at'   => $entry['updated_at'],
				'link'         => admin_url( 'admin.php?page=super_contact_entry&id=' . $entry['id'] ),
			);

			// Include additional data for edit context
			if ( $context === 'edit' ) {
				$data['ip_address'] = $entry['ip_address'];
				$data['user_agent'] = $entry['user_agent'];
				$data['user_id'] = (int) $entry['user_id'];
				$data['session_id'] = $entry['session_id'] ? (int) $entry['session_id'] : null;

				// Get meta
				$data['meta'] = SUPER_Entry_DAL::get_all_meta( $entry['id'] );
			}

			// Add form info
			$form_title = get_the_title( $entry['form_id'] );
			$data['form_title'] = $form_title ?: sprintf( '#%d', $entry['form_id'] );

			return $data;
		}

		/**
		 * Get the query params for collections.
		 *
		 * @return array
		 */
		public function get_collection_params() {
			return array(
				'page'         => array(
					'description' => __( 'Current page of the collection.', 'super-forms' ),
					'type'        => 'integer',
					'default'     => 1,
					'minimum'     => 1,
				),
				'per_page'     => array(
					'description' => __( 'Maximum number of items to be returned in result set.', 'super-forms' ),
					'type'        => 'integer',
					'default'     => 10,
					'minimum'     => 1,
					'maximum'     => 100,
				),
				'search'       => array(
					'description' => __( 'Limit results to those matching a string.', 'super-forms' ),
					'type'        => 'string',
				),
				'form_id'      => array(
					'description' => __( 'Limit results to entries from a specific form.', 'super-forms' ),
					'type'        => 'integer',
				),
				'status'       => array(
					'description' => __( 'Limit results to entries with a specific status.', 'super-forms' ),
					'type'        => 'string',
					'enum'        => array( 'publish', 'super_read', 'trash' ),
				),
				'entry_status' => array(
					'description' => __( 'Limit results to entries with a specific custom status.', 'super-forms' ),
					'type'        => 'string',
				),
				'user_id'      => array(
					'description' => __( 'Limit results to entries submitted by a specific user.', 'super-forms' ),
					'type'        => 'integer',
				),
				'after'        => array(
					'description' => __( 'Limit response to entries created after a given ISO8601 date.', 'super-forms' ),
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'before'       => array(
					'description' => __( 'Limit response to entries created before a given ISO8601 date.', 'super-forms' ),
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'orderby'      => array(
					'description' => __( 'Sort collection by entry attribute.', 'super-forms' ),
					'type'        => 'string',
					'enum'        => array( 'id', 'title', 'form_id', 'created_at', 'updated_at' ),
					'default'     => 'created_at',
				),
				'order'        => array(
					'description' => __( 'Order sort attribute ascending or descending.', 'super-forms' ),
					'type'        => 'string',
					'enum'        => array( 'asc', 'desc' ),
					'default'     => 'desc',
				),
				'context'      => $this->get_context_param( array( 'default' => 'view' ) ),
			);
		}

		/**
		 * Get the Entry's schema for display.
		 *
		 * @return array
		 */
		public function get_item_schema() {
			return array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'entry',
				'type'       => 'object',
				'properties' => array(
					'id'           => array(
						'description' => __( 'Unique identifier for the entry.', 'super-forms' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
						'readonly'    => true,
					),
					'form_id'      => array(
						'description' => __( 'The ID of the form this entry belongs to.', 'super-forms' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
						'required'    => true,
					),
					'title'        => array(
						'description' => __( 'The title for the entry.', 'super-forms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit', 'embed' ),
					),
					'status'       => array(
						'description' => __( 'A WordPress status for the entry.', 'super-forms' ),
						'type'        => 'string',
						'enum'        => array( 'publish', 'super_read', 'trash' ),
						'context'     => array( 'view', 'edit' ),
					),
					'entry_status' => array(
						'description' => __( 'A custom status for the entry.', 'super-forms' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'ip_address'   => array(
						'description' => __( 'The IP address of the submitter.', 'super-forms' ),
						'type'        => 'string',
						'context'     => array( 'edit' ),
					),
					'user_agent'   => array(
						'description' => __( 'The user agent of the submitter.', 'super-forms' ),
						'type'        => 'string',
						'context'     => array( 'edit' ),
					),
					'created_at'   => array(
						'description' => __( 'The date the entry was created.', 'super-forms' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view', 'edit', 'embed' ),
						'readonly'    => true,
					),
					'updated_at'   => array(
						'description' => __( 'The date the entry was last modified.', 'super-forms' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'data'         => array(
						'description' => __( 'Entry form field data.', 'super-forms' ),
						'type'        => 'object',
						'context'     => array( 'edit' ),
					),
					'meta'         => array(
						'description' => __( 'Entry metadata.', 'super-forms' ),
						'type'        => 'object',
						'context'     => array( 'edit' ),
					),
				),
			);
		}
	}

	// Register routes on rest_api_init
	add_action( 'rest_api_init', function () {
		$controller = new SUPER_Entry_REST_Controller();
		$controller->register_routes();
	} );

endif;
