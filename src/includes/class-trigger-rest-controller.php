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
		 * Phase 1: All endpoints require manage_options
		 * Phase 1.5: Add granular scope-based permissions
		 *
		 * @return bool
		 * @since 6.5.0
		 */
		public function check_permission() {
			return current_user_can( 'manage_options' );
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
	}

endif;
