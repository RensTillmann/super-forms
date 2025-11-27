<?php
/**
 * Trigger REST API Controller
 *
 * Provides REST API v1 endpoints for triggers/actions system.
 * UI (Phase 1.5) will consume these endpoints.
 *
 * @author      WebRehab
 * @category    API
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Trigger_REST_Controller
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Trigger_REST_Controller' ) ) :

	/**
	 * SUPER_Trigger_REST_Controller Class
	 */
	class SUPER_Trigger_REST_Controller extends WP_REST_Controller {

		/**
		 * Namespace
		 *
		 * @var string
		 */
		protected $namespace = 'super-forms/v1';

		/**
		 * Register routes
		 *
		 * @since 6.5.0
		 */
		public function register_routes() {
			// Payment Webhooks (public - no auth required, verified by signature)
			register_rest_route(
				$this->namespace,
				'/webhooks/stripe',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_stripe_webhook' ),
					'permission_callback' => '__return_true', // Verified by signature
				)
			);

			register_rest_route(
				$this->namespace,
				'/webhooks/paypal',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_paypal_webhook' ),
					'permission_callback' => '__return_true', // Verified by signature
				)
			);

			// Triggers CRUD
			register_rest_route(
				$this->namespace,
				'/triggers',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_triggers' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_trigger' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_create_trigger_args(),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/triggers/(?P<id>[\d]+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_trigger' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'id' => array(
								'required'          => true,
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_trigger' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_update_trigger_args(),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_trigger' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);

			// Trigger test endpoint
			register_rest_route(
				$this->namespace,
				'/triggers/(?P<id>[\d]+)/test',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'test_trigger' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'mock_context' => array(
							'type'        => 'object',
							'description' => 'Mock context data for testing',
						),
					),
				)
			);

			// Actions CRUD (nested under triggers)
			register_rest_route(
				$this->namespace,
				'/triggers/(?P<trigger_id>[\d]+)/actions',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_actions' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_action' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_action_args(),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/triggers/(?P<trigger_id>[\d]+)/actions/(?P<id>[\d]+)',
				array(
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_action' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_action_args(),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_action' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);

			// Registry introspection
			register_rest_route(
				$this->namespace,
				'/events',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_events' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/action-types',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_action_types' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);

			// Execution logs
			register_rest_route(
				$this->namespace,
				'/trigger-logs',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_logs' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'trigger_id' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'form_id'    => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'entry_id'   => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'status'     => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'limit'      => array(
							'type'              => 'integer',
							'default'           => 100,
							'sanitize_callback' => 'absint',
						),
						'offset'     => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
					),
				)
			);
		}

		/**
		 * Check permission
		 *
		 * Supports multiple authentication methods:
		 * - WordPress cookie authentication (logged-in users)
		 * - API key authentication (X-API-Key header)
		 *
		 * @param WP_REST_Request $request The REST request object
		 * @return bool|WP_Error True if authorized, WP_Error otherwise
		 * @since 6.5.0
		 */
		public function check_permission( $request = null ) {
			// Check for API key authentication first
			if ( $request ) {
				$api_key = $request->get_header( 'X-API-Key' );
				if ( $api_key ) {
					return $this->authenticate_api_key( $api_key, $request );
				}
			}

			// WordPress authentication fallback
			if ( ! is_user_logged_in() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Authentication required. Provide X-API-Key header or use WordPress authentication.', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			// Check permissions using the permissions class if available
			if ( class_exists( 'SUPER_Trigger_Permissions' ) ) {
				$action = $this->get_action_from_request( $request );
				return SUPER_Trigger_Permissions::check_rest_permission( $request, $action );
			}

			// Fallback to manage_options
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'You do not have permission to access this resource.', 'super-forms' ),
					array( 'status' => 403 )
				);
			}

			return true;
		}

		/**
		 * Authenticate using API key
		 *
		 * @param string          $api_key The API key from header
		 * @param WP_REST_Request $request The REST request
		 * @return bool|WP_Error
		 * @since 6.5.0
		 */
		private function authenticate_api_key( $api_key, $request ) {
			if ( ! class_exists( 'SUPER_Trigger_API_Keys' ) ) {
				return new WP_Error(
					'api_keys_unavailable',
					__( 'API key authentication is not available.', 'super-forms' ),
					array( 'status' => 500 )
				);
			}

			$key_data = SUPER_Trigger_API_Keys::instance()->validate_key( $api_key );

			if ( ! $key_data ) {
				return new WP_Error(
					'invalid_api_key',
					__( 'Invalid or expired API key.', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			// Set user context from API key
			wp_set_current_user( $key_data['user_id'] );

			// Check key permissions for this endpoint
			$action = $this->get_action_from_request( $request );
			$required_permission = $this->get_required_permission( $action );

			if ( ! SUPER_Trigger_API_Keys::instance()->has_permission( $key_data, $required_permission ) ) {
				return new WP_Error(
					'insufficient_permissions',
					sprintf(
						/* translators: %s: required permission */
						__( 'API key does not have the required "%s" permission.', 'super-forms' ),
						$required_permission
					),
					array( 'status' => 403 )
				);
			}

			return true;
		}

		/**
		 * Determine action type from request
		 *
		 * @param WP_REST_Request|null $request The REST request
		 * @return string Action type
		 * @since 6.5.0
		 */
		private function get_action_from_request( $request ) {
			if ( ! $request ) {
				return 'read';
			}

			$method = $request->get_method();

			switch ( $method ) {
				case 'GET':
					return 'read';
				case 'POST':
					$route = $request->get_route();
					if ( strpos( $route, '/test' ) !== false ) {
						return 'execute';
					}
					return 'create';
				case 'PUT':
				case 'PATCH':
					return 'update';
				case 'DELETE':
					return 'delete';
				default:
					return 'read';
			}
		}

		/**
		 * Get required permission for action
		 *
		 * @param string $action The action type
		 * @return string Permission name
		 * @since 6.5.0
		 */
		private function get_required_permission( $action ) {
			switch ( $action ) {
				case 'read':
					return 'triggers';
				case 'create':
				case 'update':
				case 'delete':
					return 'triggers';
				case 'execute':
					return 'execute';
				default:
					return 'triggers';
			}
		}

		/**
		 * Get triggers (list)
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function get_triggers( $request ) {
			$params = $request->get_params();

			// Build filters
			$filters = array();

			if ( ! empty( $params['scope'] ) ) {
				$filters['scope'] = $params['scope'];
			}

			if ( ! empty( $params['scope_id'] ) ) {
				$filters['scope_id'] = absint( $params['scope_id'] );
			}

			if ( ! empty( $params['event_id'] ) ) {
				$filters['event_id'] = sanitize_text_field( $params['event_id'] );
			}

			// For now, get all triggers (pagination in Phase 1.5)
			// Using DAL methods based on filters
			if ( ! empty( $filters['scope'] ) ) {
				$triggers = SUPER_Trigger_DAL::get_triggers_by_scope(
					$filters['scope'],
					$filters['scope_id'] ?? null,
					false
				);
			} elseif ( ! empty( $filters['event_id'] ) ) {
				$triggers = SUPER_Trigger_DAL::get_triggers_by_event(
					$filters['event_id'],
					null,
					null,
					false
				);
			} else {
				// Get all triggers - for Phase 1 simplicity
				// Phase 1.5 will add search_triggers() with pagination
				global $wpdb;
				$triggers = $wpdb->get_results(
					"SELECT * FROM {$wpdb->prefix}superforms_triggers ORDER BY id DESC",
					ARRAY_A
				);

				// Decode conditions
				foreach ( $triggers as &$trigger ) {
					if ( ! empty( $trigger['conditions'] ) ) {
						$trigger['conditions'] = json_decode( $trigger['conditions'], true );
					}
				}
			}

			return rest_ensure_response( $triggers );
		}

		/**
		 * Get single trigger
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function get_trigger( $request ) {
			$trigger_id = absint( $request['id'] );

			$trigger = SUPER_Trigger_Manager::get_trigger_with_actions( $trigger_id );

			if ( is_wp_error( $trigger ) ) {
				return $trigger;
			}

			return rest_ensure_response( $trigger );
		}

		/**
		 * Create trigger
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function create_trigger( $request ) {
			$params = $request->get_json_params();

			$trigger_data = array(
				'trigger_name'    => $params['trigger_name'] ?? '',
				'event_id'        => $params['event_id'] ?? '',
				'scope'           => $params['scope'] ?? 'form',
				'scope_id'        => $params['scope_id'] ?? null,
				'conditions'      => $params['conditions'] ?? '',
				'enabled'         => $params['enabled'] ?? 1,
				'execution_order' => $params['execution_order'] ?? 10,
			);

			$actions_data = $params['actions'] ?? array();

			$result = SUPER_Trigger_Manager::create_trigger_with_actions( $trigger_data, $actions_data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( $result );
		}

		/**
		 * Update trigger
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function update_trigger( $request ) {
			$trigger_id = absint( $request['id'] );
			$params     = $request->get_json_params();

			$trigger_data = array();

			// Only update fields that are provided
			if ( isset( $params['trigger_name'] ) ) {
				$trigger_data['trigger_name'] = $params['trigger_name'];
			}
			if ( isset( $params['event_id'] ) ) {
				$trigger_data['event_id'] = $params['event_id'];
			}
			if ( isset( $params['scope'] ) ) {
				$trigger_data['scope'] = $params['scope'];
			}
			if ( isset( $params['scope_id'] ) ) {
				$trigger_data['scope_id'] = $params['scope_id'];
			}
			if ( isset( $params['conditions'] ) ) {
				$trigger_data['conditions'] = $params['conditions'];
			}
			if ( isset( $params['enabled'] ) ) {
				$trigger_data['enabled'] = $params['enabled'];
			}
			if ( isset( $params['execution_order'] ) ) {
				$trigger_data['execution_order'] = $params['execution_order'];
			}

			$actions_data = isset( $params['actions'] ) ? $params['actions'] : null;

			$result = SUPER_Trigger_Manager::update_trigger_with_actions(
				$trigger_id,
				$trigger_data,
				$actions_data
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Return updated trigger
			$trigger = SUPER_Trigger_Manager::get_trigger_with_actions( $trigger_id );

			return rest_ensure_response( $trigger );
		}

		/**
		 * Delete trigger
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function delete_trigger( $request ) {
			$trigger_id = absint( $request['id'] );

			$result = SUPER_Trigger_Manager::delete_trigger( $trigger_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( array( 'deleted' => true ) );
		}

		/**
		 * Test trigger
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function test_trigger( $request ) {
			$trigger_id   = absint( $request['id'] );
			$mock_context = $request->get_param( 'mock_context' ) ?? array();

			$result = SUPER_Trigger_Executor::test_trigger( $trigger_id, $mock_context );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( $result );
		}

		/**
		 * Get actions for trigger
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function get_actions( $request ) {
			$trigger_id = absint( $request['trigger_id'] );

			$actions = SUPER_Trigger_DAL::get_actions( $trigger_id, false );

			return rest_ensure_response( $actions );
		}

		/**
		 * Create action
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function create_action( $request ) {
			$trigger_id = absint( $request['trigger_id'] );
			$params     = $request->get_json_params();

			$action_data = array(
				'action_type'     => $params['action_type'] ?? '',
				'action_config'   => $params['action_config'] ?? '',
				'execution_order' => $params['execution_order'] ?? 10,
				'enabled'         => $params['enabled'] ?? 1,
			);

			// Validate
			$validation = SUPER_Trigger_Manager::validate_action_data( $action_data );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// Sanitize
			$action_data = SUPER_Trigger_Manager::sanitize_action_data( $action_data );

			// Create
			$action_id = SUPER_Trigger_DAL::create_action( $trigger_id, $action_data );

			if ( is_wp_error( $action_id ) ) {
				return $action_id;
			}

			// Return created action
			$action = array_merge(
				array( 'id' => $action_id ),
				$action_data
			);

			return rest_ensure_response( $action );
		}

		/**
		 * Update action
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function update_action( $request ) {
			$action_id = absint( $request['id'] );
			$params    = $request->get_json_params();

			$action_data = array();

			if ( isset( $params['action_type'] ) ) {
				$action_data['action_type'] = $params['action_type'];
			}
			if ( isset( $params['action_config'] ) ) {
				$action_data['action_config'] = $params['action_config'];
			}
			if ( isset( $params['execution_order'] ) ) {
				$action_data['execution_order'] = $params['execution_order'];
			}
			if ( isset( $params['enabled'] ) ) {
				$action_data['enabled'] = $params['enabled'];
			}

			// Validate
			$validation = SUPER_Trigger_Manager::validate_action_data( $action_data );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// Sanitize
			$action_data = SUPER_Trigger_Manager::sanitize_action_data( $action_data );

			// Update
			$result = SUPER_Trigger_DAL::update_action( $action_id, $action_data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( array( 'updated' => true ) );
		}

		/**
		 * Delete action
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response or error
		 * @since 6.5.0
		 */
		public function delete_action( $request ) {
			$action_id = absint( $request['id'] );

			$result = SUPER_Trigger_DAL::delete_action( $action_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( array( 'deleted' => true ) );
		}

		/**
		 * Get registered events
		 *
		 * @return WP_REST_Response Response
		 * @since 6.5.0
		 */
		public function get_events() {
			$registry = SUPER_Trigger_Registry::get_instance();
			$events   = $registry->get_events();

			return rest_ensure_response( $events );
		}

		/**
		 * Get registered action types
		 *
		 * @return WP_REST_Response Response
		 * @since 6.5.0
		 */
		public function get_action_types() {
			$registry     = SUPER_Trigger_Registry::get_instance();
			$action_types = $registry->get_actions();

			// Get instances with metadata
			$actions = array();
			foreach ( $action_types as $action_id => $class_name ) {
				$instance = $registry->get_action_instance( $action_id );
				if ( $instance ) {
					$actions[ $action_id ] = array(
						'id'          => $instance->get_id(),
						'label'       => $instance->get_label(),
						'category'    => $instance->get_category(),
						'description' => $instance->get_description(),
						'schema'      => $instance->get_settings_schema(),
					);
				}
			}

			return rest_ensure_response( $actions );
		}

		/**
		 * Get execution logs
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response Response
		 * @since 6.5.0
		 */
		public function get_logs( $request ) {
			$params = $request->get_params();

			$filters = array();

			if ( ! empty( $params['trigger_id'] ) ) {
				$filters['trigger_id'] = absint( $params['trigger_id'] );
			}
			if ( ! empty( $params['form_id'] ) ) {
				$filters['form_id'] = absint( $params['form_id'] );
			}
			if ( ! empty( $params['entry_id'] ) ) {
				$filters['entry_id'] = absint( $params['entry_id'] );
			}
			if ( ! empty( $params['status'] ) ) {
				$filters['status'] = sanitize_text_field( $params['status'] );
			}

			$limit  = absint( $params['limit'] ?? 100 );
			$offset = absint( $params['offset'] ?? 0 );

			$logs = SUPER_Trigger_DAL::get_execution_logs( $filters, $limit, $offset );

			return rest_ensure_response( $logs );
		}

		/**
		 * Get collection params
		 *
		 * @return array Collection parameters
		 * @since 6.5.0
		 */
		public function get_collection_params() {
			return array(
				'scope'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'scope_id' => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'event_id' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			);
		}

		/**
		 * Get create trigger args
		 *
		 * @return array Arguments schema
		 * @since 6.5.0
		 */
		private function get_create_trigger_args() {
			return array(
				'trigger_name'    => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'event_id'        => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'scope'           => array(
					'type'              => 'string',
					'default'           => 'form',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'scope_id'        => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'conditions'      => array(
					'type' => 'object',
				),
				'enabled'         => array(
					'type'    => 'integer',
					'default' => 1,
				),
				'execution_order' => array(
					'type'    => 'integer',
					'default' => 10,
				),
				'actions'         => array(
					'type' => 'array',
				),
			);
		}

		/**
		 * Get update trigger args
		 *
		 * @return array Arguments schema
		 * @since 6.5.0
		 */
		private function get_update_trigger_args() {
			$args = $this->get_create_trigger_args();

			// Make all fields optional for update
			foreach ( $args as &$arg ) {
				unset( $arg['required'] );
			}

			return $args;
		}

		/**
		 * Get action args
		 *
		 * @return array Arguments schema
		 * @since 6.5.0
		 */
		private function get_action_args() {
			return array(
				'action_type'     => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'action_config'   => array(
					'type' => 'object',
				),
				'execution_order' => array(
					'type'    => 'integer',
					'default' => 10,
				),
				'enabled'         => array(
					'type'    => 'integer',
					'default' => 1,
				),
			);
		}

		// =========================================================================
		// Payment Webhook Handlers
		// =========================================================================

		/**
		 * Handle Stripe webhook
		 *
		 * Verifies signature and fires appropriate payment events
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response
		 * @since 6.5.0
		 */
		public function handle_stripe_webhook( $request ) {
			$payload   = $request->get_body();
			$signature = $request->get_header( 'Stripe-Signature' );

			if ( empty( $signature ) ) {
				return new WP_Error(
					'missing_signature',
					__( 'Missing Stripe signature header.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			// Get webhook secret from credentials
			$webhook_secret = $this->get_stripe_webhook_secret();

			if ( empty( $webhook_secret ) ) {
				// Log warning but process anyway in development
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::warning( 'Stripe webhook secret not configured - signature verification skipped' );
				}
			} else {
				// Verify signature
				$verified = $this->verify_stripe_signature( $payload, $signature, $webhook_secret );
				if ( is_wp_error( $verified ) ) {
					return $verified;
				}
			}

			// Parse event
			$event = json_decode( $payload, true );

			if ( json_last_error() !== JSON_ERROR_NONE || empty( $event['type'] ) ) {
				return new WP_Error(
					'invalid_payload',
					__( 'Invalid webhook payload.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			// Map Stripe event to Super Forms event
			$event_mapping = $this->get_stripe_event_mapping();
			$stripe_type   = $event['type'];

			if ( ! isset( $event_mapping[ $stripe_type ] ) ) {
				// Unhandled event type - return success to acknowledge receipt
				return rest_ensure_response( array(
					'received' => true,
					'handled'  => false,
					'message'  => sprintf( 'Event type %s not mapped to trigger event', $stripe_type ),
				) );
			}

			$super_event_id = $event_mapping[ $stripe_type ];
			$context        = $this->build_stripe_context( $event );

			// Fire the trigger event
			$result = SUPER_Trigger_Executor::fire_event( $super_event_id, $context );

			// Log the webhook
			if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
				SUPER_Trigger_Logger::info(
					sprintf( 'Stripe webhook processed: %s -> %s', $stripe_type, $super_event_id ),
					array(
						'stripe_event_id' => $event['id'] ?? '',
						'super_event'     => $super_event_id,
						'triggers_fired'  => is_array( $result ) ? count( $result ) : 0,
					)
				);
			}

			return rest_ensure_response( array(
				'received'       => true,
				'handled'        => true,
				'event'          => $super_event_id,
				'triggers_fired' => is_array( $result ) ? count( $result ) : 0,
			) );
		}

		/**
		 * Handle PayPal webhook
		 *
		 * Verifies signature and fires appropriate payment events
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error Response
		 * @since 6.5.0
		 */
		public function handle_paypal_webhook( $request ) {
			$payload = $request->get_body();

			// PayPal sends multiple headers for verification
			$headers = array(
				'PAYPAL-AUTH-ALGO'         => $request->get_header( 'PAYPAL-AUTH-ALGO' ),
				'PAYPAL-CERT-URL'          => $request->get_header( 'PAYPAL-CERT-URL' ),
				'PAYPAL-TRANSMISSION-ID'   => $request->get_header( 'PAYPAL-TRANSMISSION-ID' ),
				'PAYPAL-TRANSMISSION-SIG'  => $request->get_header( 'PAYPAL-TRANSMISSION-SIG' ),
				'PAYPAL-TRANSMISSION-TIME' => $request->get_header( 'PAYPAL-TRANSMISSION-TIME' ),
			);

			// Check required headers
			if ( empty( $headers['PAYPAL-TRANSMISSION-ID'] ) ) {
				return new WP_Error(
					'missing_headers',
					__( 'Missing PayPal verification headers.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			// Get webhook ID from credentials
			$webhook_id = $this->get_paypal_webhook_id();

			if ( empty( $webhook_id ) ) {
				// Log warning but process anyway in development
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::warning( 'PayPal webhook ID not configured - signature verification skipped' );
				}
			} else {
				// Verify with PayPal API
				$verified = $this->verify_paypal_webhook( $payload, $headers, $webhook_id );
				if ( is_wp_error( $verified ) ) {
					return $verified;
				}
			}

			// Parse event
			$event = json_decode( $payload, true );

			if ( json_last_error() !== JSON_ERROR_NONE || empty( $event['event_type'] ) ) {
				return new WP_Error(
					'invalid_payload',
					__( 'Invalid webhook payload.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			// Map PayPal event to Super Forms event
			$event_mapping = $this->get_paypal_event_mapping();
			$paypal_type   = $event['event_type'];

			if ( ! isset( $event_mapping[ $paypal_type ] ) ) {
				// Unhandled event type - return success to acknowledge receipt
				return rest_ensure_response( array(
					'received' => true,
					'handled'  => false,
					'message'  => sprintf( 'Event type %s not mapped to trigger event', $paypal_type ),
				) );
			}

			$super_event_id = $event_mapping[ $paypal_type ];
			$context        = $this->build_paypal_context( $event );

			// Fire the trigger event
			$result = SUPER_Trigger_Executor::fire_event( $super_event_id, $context );

			// Log the webhook
			if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
				SUPER_Trigger_Logger::info(
					sprintf( 'PayPal webhook processed: %s -> %s', $paypal_type, $super_event_id ),
					array(
						'paypal_event_id' => $event['id'] ?? '',
						'super_event'     => $super_event_id,
						'triggers_fired'  => is_array( $result ) ? count( $result ) : 0,
					)
				);
			}

			return rest_ensure_response( array(
				'received'       => true,
				'handled'        => true,
				'event'          => $super_event_id,
				'triggers_fired' => is_array( $result ) ? count( $result ) : 0,
			) );
		}

		/**
		 * Get Stripe webhook secret from credentials
		 *
		 * @return string|null Webhook secret
		 * @since 6.5.0
		 */
		private function get_stripe_webhook_secret() {
			if ( ! class_exists( 'SUPER_Payment_OAuth' ) ) {
				return null;
			}

			$oauth       = SUPER_Payment_OAuth::instance();
			$credentials = $oauth->get_api_credentials( 'stripe', 'live' );

			// Try live mode first, then test mode
			if ( empty( $credentials['webhook_secret'] ) ) {
				$credentials = $oauth->get_api_credentials( 'stripe', 'test' );
			}

			return $credentials['webhook_secret'] ?? null;
		}

		/**
		 * Get PayPal webhook ID from credentials
		 *
		 * @return string|null Webhook ID
		 * @since 6.5.0
		 */
		private function get_paypal_webhook_id() {
			if ( ! class_exists( 'SUPER_Payment_OAuth' ) ) {
				return null;
			}

			$oauth       = SUPER_Payment_OAuth::instance();
			$credentials = $oauth->get_api_credentials( 'paypal', 'live' );

			// Try live mode first, then sandbox mode
			if ( empty( $credentials['webhook_id'] ) ) {
				$credentials = $oauth->get_api_credentials( 'paypal', 'sandbox' );
			}

			return $credentials['webhook_id'] ?? null;
		}

		/**
		 * Verify Stripe webhook signature
		 *
		 * @param string $payload        Raw request body
		 * @param string $signature      Stripe-Signature header
		 * @param string $webhook_secret Webhook signing secret
		 * @return true|WP_Error True if valid, error otherwise
		 * @since 6.5.0
		 */
		private function verify_stripe_signature( $payload, $signature, $webhook_secret ) {
			// Parse signature header
			$elements = explode( ',', $signature );
			$sig_data = array();

			foreach ( $elements as $element ) {
				$parts = explode( '=', $element, 2 );
				if ( count( $parts ) === 2 ) {
					$sig_data[ $parts[0] ] = $parts[1];
				}
			}

			if ( empty( $sig_data['t'] ) || empty( $sig_data['v1'] ) ) {
				return new WP_Error(
					'invalid_signature_format',
					__( 'Invalid signature format.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			$timestamp = $sig_data['t'];
			$expected  = $sig_data['v1'];

			// Check timestamp tolerance (5 minutes)
			if ( abs( time() - (int) $timestamp ) > 300 ) {
				return new WP_Error(
					'signature_expired',
					__( 'Webhook timestamp expired.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			// Compute expected signature
			$signed_payload = $timestamp . '.' . $payload;
			$computed       = hash_hmac( 'sha256', $signed_payload, $webhook_secret );

			if ( ! hash_equals( $expected, $computed ) ) {
				return new WP_Error(
					'invalid_signature',
					__( 'Invalid webhook signature.', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			return true;
		}

		/**
		 * Verify PayPal webhook with API
		 *
		 * @param string $payload    Raw request body
		 * @param array  $headers    PayPal verification headers
		 * @param string $webhook_id Configured webhook ID
		 * @return true|WP_Error True if valid, error otherwise
		 * @since 6.5.0
		 */
		private function verify_paypal_webhook( $payload, $headers, $webhook_id ) {
			if ( ! class_exists( 'SUPER_Payment_OAuth' ) ) {
				return new WP_Error(
					'oauth_unavailable',
					__( 'Payment OAuth not available.', 'super-forms' ),
					array( 'status' => 500 )
				);
			}

			$oauth       = SUPER_Payment_OAuth::instance();
			$credentials = $oauth->get_api_credentials( 'paypal', 'live' );

			// Determine API base
			$api_base = 'https://api-m.paypal.com';
			if ( empty( $credentials['client_id'] ) ) {
				$credentials = $oauth->get_api_credentials( 'paypal', 'sandbox' );
				$api_base    = 'https://api-m.sandbox.paypal.com';
			}

			if ( empty( $credentials['client_id'] ) || empty( $credentials['client_secret'] ) ) {
				// Can't verify without credentials - log and allow in dev
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::warning( 'PayPal credentials not configured - skipping verification' );
				}
				return true;
			}

			// Get access token
			$token_response = wp_remote_post(
				$api_base . '/v1/oauth2/token',
				array(
					'headers' => array(
						'Accept'        => 'application/json',
						'Authorization' => 'Basic ' . base64_encode( $credentials['client_id'] . ':' . $credentials['client_secret'] ),
						'Content-Type'  => 'application/x-www-form-urlencoded',
					),
					'body'    => 'grant_type=client_credentials',
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $token_response ) ) {
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::error( 'PayPal token request failed', array( 'error' => $token_response->get_error_message() ) );
				}
				// Allow webhook in case of API issues
				return true;
			}

			$token_data = json_decode( wp_remote_retrieve_body( $token_response ), true );

			if ( empty( $token_data['access_token'] ) ) {
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::error( 'PayPal token not received', array( 'response' => $token_data ) );
				}
				return true;
			}

			// Verify webhook signature
			$verify_response = wp_remote_post(
				$api_base . '/v1/notifications/verify-webhook-signature',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $token_data['access_token'],
					),
					'body'    => wp_json_encode( array(
						'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'],
						'cert_url'          => $headers['PAYPAL-CERT-URL'],
						'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'],
						'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'],
						'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'],
						'webhook_id'        => $webhook_id,
						'webhook_event'     => json_decode( $payload, true ),
					) ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $verify_response ) ) {
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::error( 'PayPal verification request failed', array( 'error' => $verify_response->get_error_message() ) );
				}
				// Allow in case of API issues
				return true;
			}

			$verify_data = json_decode( wp_remote_retrieve_body( $verify_response ), true );

			if ( isset( $verify_data['verification_status'] ) && $verify_data['verification_status'] !== 'SUCCESS' ) {
				return new WP_Error(
					'invalid_signature',
					__( 'PayPal webhook verification failed.', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			return true;
		}

		/**
		 * Get Stripe event type mapping
		 *
		 * @return array Stripe event -> Super Forms event mapping
		 * @since 6.5.0
		 */
		private function get_stripe_event_mapping() {
			return array(
				// Checkout/Payment
				'checkout.session.completed'         => 'payment.stripe.checkout_completed',
				'payment_intent.succeeded'           => 'payment.stripe.payment_succeeded',
				'payment_intent.payment_failed'      => 'payment.stripe.payment_failed',

				// Subscriptions
				'customer.subscription.created'      => 'subscription.stripe.created',
				'customer.subscription.updated'      => 'subscription.stripe.updated',
				'customer.subscription.deleted'      => 'subscription.stripe.cancelled',
				'invoice.paid'                       => 'subscription.stripe.invoice_paid',
				'invoice.payment_failed'             => 'subscription.stripe.invoice_failed',
			);
		}

		/**
		 * Get PayPal event type mapping
		 *
		 * @return array PayPal event -> Super Forms event mapping
		 * @since 6.5.0
		 */
		private function get_paypal_event_mapping() {
			return array(
				// Payments
				'PAYMENT.CAPTURE.COMPLETED'          => 'payment.paypal.capture_completed',
				'PAYMENT.CAPTURE.DENIED'             => 'payment.paypal.capture_denied',
				'PAYMENT.CAPTURE.REFUNDED'           => 'payment.paypal.capture_refunded',

				// Subscriptions
				'BILLING.SUBSCRIPTION.CREATED'       => 'subscription.paypal.created',
				'BILLING.SUBSCRIPTION.ACTIVATED'     => 'subscription.paypal.activated',
				'BILLING.SUBSCRIPTION.CANCELLED'     => 'subscription.paypal.cancelled',
				'BILLING.SUBSCRIPTION.SUSPENDED'     => 'subscription.paypal.suspended',
				'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => 'subscription.paypal.payment_failed',
			);
		}

		/**
		 * Build context from Stripe event
		 *
		 * @param array $event Stripe event data
		 * @return array Context for trigger execution
		 * @since 6.5.0
		 */
		private function build_stripe_context( $event ) {
			$data    = $event['data']['object'] ?? array();
			$context = array(
				'gateway'         => 'stripe',
				'event_id'        => $event['id'] ?? '',
				'event_type'      => $event['type'] ?? '',
				'livemode'        => $event['livemode'] ?? false,
				'created'         => $event['created'] ?? time(),
			);

			// Extract common fields based on event type
			$type = $event['type'] ?? '';

			if ( strpos( $type, 'checkout.session' ) === 0 ) {
				// Checkout session
				$context['session_id']       = $data['id'] ?? '';
				$context['payment_intent']   = $data['payment_intent'] ?? '';
				$context['customer']         = $data['customer'] ?? '';
				$context['customer_email']   = $data['customer_email'] ?? $data['customer_details']['email'] ?? '';
				$context['amount_total']     = $data['amount_total'] ?? 0;
				$context['currency']         = $data['currency'] ?? '';
				$context['payment_status']   = $data['payment_status'] ?? '';
				$context['subscription']     = $data['subscription'] ?? '';

				// Extract form_id and entry_id from metadata if present
				$metadata                = $data['metadata'] ?? array();
				$context['form_id']      = $metadata['form_id'] ?? 0;
				$context['entry_id']     = $metadata['entry_id'] ?? 0;
				$context['metadata']     = $metadata;

			} elseif ( strpos( $type, 'payment_intent' ) === 0 ) {
				// Payment intent
				$context['payment_intent_id'] = $data['id'] ?? '';
				$context['amount']            = $data['amount'] ?? 0;
				$context['currency']          = $data['currency'] ?? '';
				$context['status']            = $data['status'] ?? '';
				$context['customer']          = $data['customer'] ?? '';

				// Error info for failures
				if ( ! empty( $data['last_payment_error'] ) ) {
					$context['error_code']    = $data['last_payment_error']['code'] ?? '';
					$context['error_message'] = $data['last_payment_error']['message'] ?? '';
				}

				$metadata            = $data['metadata'] ?? array();
				$context['form_id']  = $metadata['form_id'] ?? 0;
				$context['entry_id'] = $metadata['entry_id'] ?? 0;
				$context['metadata'] = $metadata;

			} elseif ( strpos( $type, 'customer.subscription' ) === 0 ) {
				// Subscription
				$context['subscription_id']  = $data['id'] ?? '';
				$context['customer']         = $data['customer'] ?? '';
				$context['status']           = $data['status'] ?? '';
				$context['current_period_start'] = $data['current_period_start'] ?? 0;
				$context['current_period_end']   = $data['current_period_end'] ?? 0;
				$context['cancel_at_period_end'] = $data['cancel_at_period_end'] ?? false;

				// Plan info
				$items = $data['items']['data'] ?? array();
				if ( ! empty( $items[0] ) ) {
					$context['plan_id']      = $items[0]['price']['id'] ?? '';
					$context['plan_amount']  = $items[0]['price']['unit_amount'] ?? 0;
					$context['plan_interval'] = $items[0]['price']['recurring']['interval'] ?? '';
				}

				$metadata            = $data['metadata'] ?? array();
				$context['form_id']  = $metadata['form_id'] ?? 0;
				$context['entry_id'] = $metadata['entry_id'] ?? 0;
				$context['metadata'] = $metadata;

			} elseif ( strpos( $type, 'invoice' ) === 0 ) {
				// Invoice
				$context['invoice_id']      = $data['id'] ?? '';
				$context['subscription_id'] = $data['subscription'] ?? '';
				$context['customer']        = $data['customer'] ?? '';
				$context['customer_email']  = $data['customer_email'] ?? '';
				$context['amount_due']      = $data['amount_due'] ?? 0;
				$context['amount_paid']     = $data['amount_paid'] ?? 0;
				$context['currency']        = $data['currency'] ?? '';
				$context['status']          = $data['status'] ?? '';

				$metadata            = $data['metadata'] ?? array();
				$context['form_id']  = $metadata['form_id'] ?? 0;
				$context['entry_id'] = $metadata['entry_id'] ?? 0;
				$context['metadata'] = $metadata;
			}

			// Convert amounts from cents to dollars for display
			foreach ( array( 'amount', 'amount_total', 'amount_due', 'amount_paid', 'plan_amount' ) as $field ) {
				if ( isset( $context[ $field ] ) && is_numeric( $context[ $field ] ) ) {
					$context[ $field . '_display' ] = number_format( $context[ $field ] / 100, 2 );
				}
			}

			return $context;
		}

		/**
		 * Build context from PayPal event
		 *
		 * @param array $event PayPal event data
		 * @return array Context for trigger execution
		 * @since 6.5.0
		 */
		private function build_paypal_context( $event ) {
			$resource = $event['resource'] ?? array();
			$context  = array(
				'gateway'         => 'paypal',
				'event_id'        => $event['id'] ?? '',
				'event_type'      => $event['event_type'] ?? '',
				'create_time'     => $event['create_time'] ?? '',
				'resource_type'   => $event['resource_type'] ?? '',
			);

			$type = $event['event_type'] ?? '';

			if ( strpos( $type, 'PAYMENT.CAPTURE' ) === 0 ) {
				// Payment capture
				$context['capture_id']    = $resource['id'] ?? '';
				$context['status']        = $resource['status'] ?? '';
				$context['amount']        = $resource['amount']['value'] ?? 0;
				$context['currency']      = $resource['amount']['currency_code'] ?? '';
				$context['invoice_id']    = $resource['invoice_id'] ?? '';

				// Custom data from invoice_id or custom_id
				$context['custom_id'] = $resource['custom_id'] ?? '';

				// Try to parse form_id/entry_id from custom_id
				$custom = json_decode( $resource['custom_id'] ?? '', true );
				if ( is_array( $custom ) ) {
					$context['form_id']  = $custom['form_id'] ?? 0;
					$context['entry_id'] = $custom['entry_id'] ?? 0;
				}

			} elseif ( strpos( $type, 'BILLING.SUBSCRIPTION' ) === 0 ) {
				// Subscription
				$context['subscription_id']  = $resource['id'] ?? '';
				$context['status']           = $resource['status'] ?? '';
				$context['plan_id']          = $resource['plan_id'] ?? '';
				$context['start_time']       = $resource['start_time'] ?? '';
				$context['subscriber_email'] = $resource['subscriber']['email_address'] ?? '';
				$context['subscriber_name']  = $resource['subscriber']['name']['given_name'] ?? '';

				// Billing info
				$billing_info = $resource['billing_info'] ?? array();
				if ( ! empty( $billing_info['last_payment'] ) ) {
					$context['last_payment_amount'] = $billing_info['last_payment']['amount']['value'] ?? 0;
					$context['last_payment_time']   = $billing_info['last_payment']['time'] ?? '';
				}
				$context['next_billing_time'] = $billing_info['next_billing_time'] ?? '';

				// Custom data
				$context['custom_id'] = $resource['custom_id'] ?? '';
				$custom = json_decode( $resource['custom_id'] ?? '', true );
				if ( is_array( $custom ) ) {
					$context['form_id']  = $custom['form_id'] ?? 0;
					$context['entry_id'] = $custom['entry_id'] ?? 0;
				}
			}

			// Add display amount
			if ( isset( $context['amount'] ) && is_numeric( $context['amount'] ) ) {
				$context['amount_display'] = number_format( (float) $context['amount'], 2 );
			}

			return $context;
		}
	}

endif;
