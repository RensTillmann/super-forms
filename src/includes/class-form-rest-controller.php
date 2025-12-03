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

			// Duplicate form
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)/duplicate',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'duplicate_form' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);

			// Search forms
			register_rest_route(
				$this->namespace,
				'/forms/search',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_forms' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'query' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => 'Search query string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				)
			);

			// Archive form
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)/archive',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'archive_form' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);

			// Restore form
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)/restore',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restore_form' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);

			// Export form
			register_rest_route(
				$this->namespace,
				'/forms/(?P<id>[\d]+)/export',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export_form' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);

			// Import form
			register_rest_route(
				$this->namespace,
				'/forms/import',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_form' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'form_data' => array(
							'required'    => true,
							'type'        => 'object',
							'description' => 'Form data to import (JSON format)',
						),
					),
				)
			);

			// Bulk operations - RESTful endpoints per operation
			register_rest_route(
				$this->namespace,
				'/forms/bulk/(?P<operation>delete|archive|restore|duplicate)',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'bulk_operations' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'operation' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => 'Bulk operation type from URL',
							'validate_callback' => function ( $param ) {
								return in_array( $param, array( 'delete', 'archive', 'restore', 'duplicate' ), true );
							},
						),
						'form_ids' => array(
							'required'    => true,
							'type'        => 'array',
							'description' => 'Array of form IDs to operate on',
						),
					),
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

		/**
		 * Duplicate a form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function duplicate_form( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$new_form_id = SUPER_Form_DAL::duplicate( $form_id );

			if ( is_wp_error( $new_form_id ) ) {
				return $new_form_id;
			}

			$new_form = SUPER_Form_DAL::get( $new_form_id );

			return rest_ensure_response(
				array(
					'success'  => true,
					'form_id'  => $new_form_id,
					'form'     => $new_form,
				)
			);
		}

		/**
		 * Search forms
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response
		 */
		public function search_forms( $request ) {
			$query = $request->get_param( 'query' );
			$args = array(
				'status'  => $request->get_param( 'status' ),
				'number'  => $request->get_param( 'number' ) ?? 20,
				'offset'  => $request->get_param( 'offset' ) ?? 0,
				'orderby' => $request->get_param( 'orderby' ) ?? 'id',
				'order'   => $request->get_param( 'order' ) ?? 'DESC',
			);

			$forms = SUPER_Form_DAL::search( $query, $args );

			return rest_ensure_response(
				array(
					'query'   => $query,
					'results' => $forms,
					'count'   => count( $forms ),
				)
			);
		}

		/**
		 * Archive a form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function archive_form( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$result = SUPER_Form_DAL::archive( $form_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'form_id' => $form_id,
					'message' => __( 'Form archived successfully.', 'super-forms' ),
				)
			);
		}

		/**
		 * Restore an archived form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function restore_form( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$result = SUPER_Form_DAL::restore( $form_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'form_id' => $form_id,
					'message' => __( 'Form restored successfully.', 'super-forms' ),
				)
			);
		}

		/**
		 * Export a form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function export_form( $request ) {
			$form_id = (int) $request->get_param( 'id' );
			$form = SUPER_Form_DAL::get( $form_id );

			if ( ! $form ) {
				return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ), array( 'status' => 404 ) );
			}

			$export_data = array(
				'version' => defined( 'SUPER_VERSION' ) ? SUPER_VERSION : '1.0.0',
				'exported_at' => current_time( 'mysql' ),
				'form' => array(
					'name' => $form->name,
					'elements' => $form->elements,
					'settings' => $form->settings,
					'translations' => $form->translations,
				),
			);

			return rest_ensure_response( $export_data );
		}

		/**
		 * Import a form
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function import_form( $request ) {
			$form_data = $request->get_param( 'form_data' );

			if ( ! isset( $form_data['form'] ) ) {
				return new WP_Error( 'invalid_import_data', __( 'Invalid import data format.', 'super-forms' ), array( 'status' => 400 ) );
			}

			$import = $form_data['form'];

			// Create the form
			$new_form_data = array(
				'name' => isset( $import['name'] ) ? $import['name'] . ' (Imported)' : __( 'Imported Form', 'super-forms' ),
				'status' => 'draft',
				'elements' => $import['elements'] ?? array(),
				'settings' => $import['settings'] ?? array(),
				'translations' => $import['translations'] ?? array(),
			);

			$form_id = SUPER_Form_DAL::create( $new_form_data );

			if ( is_wp_error( $form_id ) ) {
				return $form_id;
			}

			$form = SUPER_Form_DAL::get( $form_id );

			return rest_ensure_response(
				array(
					'success' => true,
					'form_id' => $form_id,
					'form'    => $form,
					'message' => __( 'Form imported successfully.', 'super-forms' ),
				)
			);
		}

		/**
		 * Bulk operations on forms
		 *
		 * @param WP_REST_Request $request Request object
		 * @return WP_REST_Response|WP_Error
		 */
		public function bulk_operations( $request ) {
			$operation = $request->get_param( 'operation' );
			$form_ids = $request->get_param( 'form_ids' );

			if ( empty( $form_ids ) || ! is_array( $form_ids ) ) {
				return new WP_Error( 'invalid_form_ids', __( 'Invalid or empty form IDs array.', 'super-forms' ), array( 'status' => 400 ) );
			}

			$results = array(
				'success' => array(),
				'failed'  => array(),
			);

			foreach ( $form_ids as $form_id ) {
				$form_id = (int) $form_id;
				$result = null;

				switch ( $operation ) {
					case 'delete':
						$result = SUPER_Form_DAL::delete( $form_id );
						break;

					case 'archive':
						$result = SUPER_Form_DAL::archive( $form_id );
						break;

					case 'restore':
						$result = SUPER_Form_DAL::restore( $form_id );
						break;

					case 'duplicate':
						$result = SUPER_Form_DAL::duplicate( $form_id );
						break;

					case 'change_status':
						$status = $request->get_param( 'status' );
						if ( empty( $status ) ) {
							$result = new WP_Error( 'missing_status', __( 'Status parameter required for change_status operation.', 'super-forms' ) );
						} else {
							$result = SUPER_Form_DAL::update( $form_id, array( 'status' => $status ) );
						}
						break;
				}

				if ( is_wp_error( $result ) ) {
					$results['failed'][] = array(
						'form_id' => $form_id,
						'error'   => $result->get_error_message(),
					);
				} else {
					$results['success'][] = $form_id;
				}
			}

			return rest_ensure_response(
				array(
					'operation'      => $operation,
					'total'          => count( $form_ids ),
					'success_count'  => count( $results['success'] ),
					'failed_count'   => count( $results['failed'] ),
					'results'        => $results,
				)
			);
		}
	}

endif;
