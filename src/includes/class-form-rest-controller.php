<?php
/**
 * Form REST API Controller
 *
 * Provides REST API v1 endpoints for forms with operations-based updates.
 * Enables AI/LLM integration, undo/redo, and version control.
 *
 * @author      WebRehab
 * @category    API
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Form_REST_Controller
 * @version     1.0.0
 * @since       6.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Form_REST_Controller' ) ) :

	/**
	 * SUPER_Form_REST_Controller Class
	 */
	class SUPER_Form_REST_Controller extends WP_REST_Controller {

		/**
		 * Namespace
		 *
		 * @var string
		 */
		protected $namespace = 'super-forms/v1';

		/**
		 * Register routes
		 *
		 * @since 6.6.0
		 */
		public function register_routes() {
			// Forms collection
			register_rest_route(
				$this->namespace,
				'/forms',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_forms' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_form' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);

			// Single form (GET, PUT, DELETE)
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_form' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_form' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_form' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);

			// Operations-based PATCH (Phase 27 - JSON Patch)
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)/operations',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'apply_operations' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id'         => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param );
							},
						),
						'operations' => array(
							'required'          => true,
							'type'              => 'array',
							'description'       => 'Array of JSON Patch operations',
							'validate_callback' => function ( $param ) {
								return is_array( $param );
							},
						),
					),
				)
			);

			// Version management
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)/versions',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_versions' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'limit' => array(
								'default'           => 20,
								'validate_callback' => function ( $param ) {
									return is_numeric( $param ) && $param > 0 && $param <= 100;
								},
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_version' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'message'    => array(
								'type'              => 'string',
								'description'       => 'Commit message',
								'validate_callback' => function ( $param ) {
									return is_string( $param ) && strlen( $param ) <= 500;
								},
							),
							'operations' => array(
								'type'        => 'array',
								'description' => 'Operations applied since last version',
							),
						),
					),
				)
			);

			// Revert to version
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)/revert/(?P<version_id>[\d]+)',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'revert_to_version' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);
		}

		/**
		 * Check permission for API access
		 *
		 * @param WP_REST_Request $request Request object
		 * @return bool
		 */
		public function check_permission( $request ) {
			return current_user_can( 'manage_options' );
		}

		/**
		 * Get collection parameters
		 *
		 * @return array
		 */
		public function get_collection_params() {
			return array(
				'status' => array(
					'default'           => 'publish',
					'validate_callback' => function ( $param ) {
						return in_array( $param, array( 'publish', 'draft', 'trash', '' ), true );
					},
				),
				'number' => array(
					'default'           => 20,
					'validate_callback' => function ( $param ) {
						return is_numeric( $param ) && $param > 0 && $param <= 100;
					},
				),
				'offset' => array(
					'default'           => 0,
					'validate_callback' => function ( $param ) {
						return is_numeric( $param ) && $param >= 0;
					},
				),
				'orderby' => array(
					'default'           => 'id',
					'validate_callback' => function ( $param ) {
						return in_array( $param, array( 'id', 'name', 'created_at', 'updated_at' ), true );
					},
				),
				'order' => array(
					'default'           => 'DESC',
					'validate_callback' => function ( $param ) {
						return in_array( strtoupper( $param ), array( 'ASC', 'DESC' ), true );
					},
				),
			);
		}

		/**
		 * Get forms collection
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response
		 */
		public function get_forms( $request ) {
			$args = array(
				'status'  => $request->get_param( 'status' ),
				'number'  => $request->get_param( 'number' ),
				'offset'  => $request->get_param( 'offset' ),
				'orderby' => $request->get_param( 'orderby' ),
				'order'   => $request->get_param( 'order' ),
			);

			$forms = SUPER_Form_DAL::query( $args );

			return rest_ensure_response( $forms );
		}

		/**
		 * Get single form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_form( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$form = SUPER_Form_DAL::get( $form_id );

			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			return rest_ensure_response( $form );
		}

		/**
		 * Create new form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_form( $request ) {
			$data = $request->get_json_params();

			$result = SUPER_Form_DAL::create( $data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$form = SUPER_Form_DAL::get( $result );

			return rest_ensure_response(
				array(
					'id'   => $result,
					'form' => $form,
				)
			);
		}

		/**
		 * Update form (full replacement)
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_form( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$data = $request->get_json_params();

			// Verify form exists
			$form = SUPER_Form_DAL::get( $form_id );
			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			$result = SUPER_Form_DAL::update( $form_id, $data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$updated_form = SUPER_Form_DAL::get( $form_id );

			return rest_ensure_response( $updated_form );
		}

		/**
		 * Delete form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_form( $request ) {
			$form_id = (int) $request->get_param( 'id' );

			// Verify form exists
			$form = SUPER_Form_DAL::get( $form_id );
			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			$result = SUPER_Form_DAL::delete( $form_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'deleted' => true,
					'id'      => $form_id,
				)
			);
		}

		/**
		 * Apply JSON Patch operations to form (Phase 27)
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function apply_operations( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$operations = $request->get_param( 'operations' );

			// Verify form exists
			$form = SUPER_Form_DAL::get( $form_id );
			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			// Apply operations via DAL
			$result = SUPER_Form_DAL::apply_operations( $form_id, $operations );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Return updated form
			$updated_form = SUPER_Form_DAL::get( $form_id );

			return rest_ensure_response(
				array(
					'success'      => true,
					'operations'   => count( $operations ),
					'updated_form' => $updated_form,
				)
			);
		}

		/**
		 * Get versions for a form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_versions( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$limit = (int) $request->get_param( 'limit' );

			// Verify form exists
			$form = SUPER_Form_DAL::get( $form_id );
			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			$versions = SUPER_Form_DAL::get_versions( $form_id, $limit );

			return rest_ensure_response( $versions );
		}

		/**
		 * Create a new version snapshot
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_version( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$message = $request->get_param( 'message' );
			$operations = $request->get_param( 'operations' );

			// Verify form exists
			$form = SUPER_Form_DAL::get( $form_id );
			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			// Create snapshot from current form state
			$snapshot = array(
				'elements'     => $form->elements,
				'settings'     => $form->settings,
				'translations' => $form->translations,
			);

			$version_id = SUPER_Form_DAL::create_version(
				$form_id,
				$snapshot,
				$operations ?? array(),
				$message ?? ''
			);

			if ( is_wp_error( $version_id ) ) {
				return $version_id;
			}

			$version = SUPER_Form_DAL::get_version( $version_id );

			return rest_ensure_response(
				array(
					'success'    => true,
					'version_id' => $version_id,
					'version'    => $version,
				)
			);
		}

		/**
		 * Revert form to a specific version
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function revert_to_version( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$version_id = (int) $request->get_param( 'version_id' );

			// Verify form exists
			$form = SUPER_Form_DAL::get( $form_id );
			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			$result = SUPER_Form_DAL::revert_to_version( $form_id, $version_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$updated_form = SUPER_Form_DAL::get( $form_id );

			return rest_ensure_response(
				array(
					'success'      => true,
					'reverted_to'  => $version_id,
					'updated_form' => $updated_form,
				)
			);
		}
	}

endif;
