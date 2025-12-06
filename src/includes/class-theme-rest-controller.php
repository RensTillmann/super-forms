<?php
/**
 * Theme REST API Controller
 *
 * Provides REST API v1 endpoints for the theming system.
 *
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Theme_REST_Controller
 * @version     1.0.0
 * @since       6.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Theme_REST_Controller' ) ) :

	/**
	 * SUPER_Theme_REST_Controller Class
	 */
	class SUPER_Theme_REST_Controller extends WP_REST_Controller {

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
			// Collection: GET (list) + POST (create)
			register_rest_route(
				$this->namespace,
				'/themes',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_themes' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_theme' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_create_theme_args(),
					),
				)
			);

			// Single item: GET, PUT, DELETE
			register_rest_route(
				$this->namespace,
				'/themes/(?P<id>[\d]+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_theme' ),
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
						'callback'            => array( $this, 'update_theme' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => $this->get_update_theme_args(),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_theme' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);

			// Get by slug
			register_rest_route(
				$this->namespace,
				'/themes/slug/(?P<slug>[a-z0-9-]+)',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_theme_by_slug' ),
					'permission_callback' => array( $this, 'check_permission' ),
				)
			);

			// Apply theme to form
			register_rest_route(
				$this->namespace,
				'/themes/(?P<id>[\d]+)/apply',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'apply_theme' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'form_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'description'       => 'Form ID to apply theme to',
						),
					),
				)
			);

			// Duplicate theme
			register_rest_route(
				$this->namespace,
				'/themes/(?P<id>[\d]+)/duplicate',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'duplicate_theme' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'name' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => 'Name for the duplicated theme (optional)',
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
		 * @since 6.6.0
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
					__( 'Authentication required.', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			// Check user capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'You do not have permission to manage themes.', 'super-forms' ),
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
		 * @since 6.6.0
		 */
		private function authenticate_api_key( $api_key, $request ) {
			if ( ! class_exists( 'SUPER_Automation_API_Keys' ) ) {
				return new WP_Error(
					'api_keys_unavailable',
					__( 'API key authentication is not available.', 'super-forms' ),
					array( 'status' => 500 )
				);
			}

			$key_data = SUPER_Automation_API_Keys::instance()->validate_key( $api_key );

			if ( ! $key_data ) {
				return new WP_Error(
					'invalid_api_key',
					__( 'Invalid or expired API key.', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			// Set user context from API key
			wp_set_current_user( $key_data['user_id'] );

			// For themes, just check if key is valid - no granular permissions yet
			return true;
		}

		/**
		 * Get collection parameters
		 *
		 * @return array Collection parameters
		 * @since 6.6.0
		 */
		public function get_collection_params() {
			return array(
				'category'      => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'Filter by category (light, dark, minimal, etc.)',
				),
				'include_stubs' => array(
					'type'              => 'boolean',
					'default'           => true,
					'description'       => 'Include stub (coming soon) themes',
				),
				'user_only'     => array(
					'type'              => 'boolean',
					'default'           => false,
					'description'       => 'Only return user\'s custom themes',
				),
			);
		}

		/**
		 * Get create theme arguments
		 *
		 * @return array Create theme arguments
		 * @since 6.6.0
		 */
		private function get_create_theme_args() {
			return array(
				'name'           => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'Theme name',
				),
				'description'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'description'       => 'Theme description',
				),
				'category'       => array(
					'type'              => 'string',
					'default'           => 'light',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'Theme category',
				),
				'styles'         => array(
					'required'    => true,
					'type'        => 'object',
					'description' => 'Style definitions for each node type',
				),
				'preview_colors' => array(
					'type'        => 'array',
					'description' => 'Array of 4 hex colors for preview',
					'items'       => array(
						'type' => 'string',
					),
				),
			);
		}

		/**
		 * Get update theme arguments
		 *
		 * @return array Update theme arguments
		 * @since 6.6.0
		 */
		private function get_update_theme_args() {
			return array(
				'name'           => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'Theme name',
				),
				'description'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'description'       => 'Theme description',
				),
				'category'       => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'Theme category',
				),
				'styles'         => array(
					'type'        => 'object',
					'description' => 'Style definitions for each node type',
				),
				'preview_colors' => array(
					'type'        => 'array',
					'description' => 'Array of 4 hex colors for preview',
					'items'       => array(
						'type' => 'string',
					),
				),
			);
		}

		/**
		 * Get themes (list)
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function get_themes( $request ) {
			$params = $request->get_params();

			$filters = array(
				'category'      => $params['category'] ?? null,
				'include_stubs' => $params['include_stubs'] ?? true,
				'user_id'       => ( $params['user_only'] ?? false ) ? get_current_user_id() : null,
			);

			// DAL already decodes JSON fields
			$themes = SUPER_Theme_DAL::get_all_themes( $filters );

			return rest_ensure_response( $themes );
		}

		/**
		 * Get single theme by ID
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function get_theme( $request ) {
			$theme_id = absint( $request['id'] );

			// DAL already decodes JSON fields
			$theme = SUPER_Theme_DAL::get_theme( $theme_id );

			if ( is_wp_error( $theme ) ) {
				return $theme;
			}

			return rest_ensure_response( $theme );
		}

		/**
		 * Get theme by slug
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function get_theme_by_slug( $request ) {
			$slug = sanitize_title( $request['slug'] );

			// DAL already decodes JSON fields
			$theme = SUPER_Theme_DAL::get_theme_by_slug( $slug );

			if ( is_wp_error( $theme ) ) {
				return $theme;
			}

			return rest_ensure_response( $theme );
		}

		/**
		 * Create theme
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function create_theme( $request ) {
			$params = $request->get_json_params();

			$theme_data = array(
				'name'           => $params['name'] ?? '',
				'description'    => $params['description'] ?? '',
				'category'       => $params['category'] ?? 'light',
				'styles'         => $params['styles'] ?? array(),
				'preview_colors' => $params['preview_colors'] ?? array(),
				'user_id'        => get_current_user_id(),
			);

			$theme_id = SUPER_Theme_DAL::create_theme( $theme_data );

			if ( is_wp_error( $theme_id ) ) {
				return $theme_id;
			}

			// DAL already decodes JSON fields
			$theme = SUPER_Theme_DAL::get_theme( $theme_id );

			return rest_ensure_response( $theme );
		}

		/**
		 * Update theme
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function update_theme( $request ) {
			$theme_id = absint( $request['id'] );
			$params   = $request->get_json_params();

			// Verify theme exists
			$theme = SUPER_Theme_DAL::get_theme( $theme_id );
			if ( is_wp_error( $theme ) ) {
				return $theme;
			}

			// System themes cannot be modified
			if ( $theme['is_system'] ) {
				return new WP_Error(
					'cannot_modify_system_theme',
					__( 'System themes cannot be modified.', 'super-forms' ),
					array( 'status' => 403 )
				);
			}

			// Check ownership for custom themes (unless admin)
			if ( $theme['user_id'] && $theme['user_id'] != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'You can only modify your own themes.', 'super-forms' ),
					array( 'status' => 403 )
				);
			}

			$update_data = array();

			if ( isset( $params['name'] ) ) {
				$update_data['name'] = $params['name'];
			}
			if ( isset( $params['description'] ) ) {
				$update_data['description'] = $params['description'];
			}
			if ( isset( $params['category'] ) ) {
				$update_data['category'] = $params['category'];
			}
			if ( isset( $params['styles'] ) ) {
				$update_data['styles'] = $params['styles'];
			}
			if ( isset( $params['preview_colors'] ) ) {
				$update_data['preview_colors'] = $params['preview_colors'];
			}

			$result = SUPER_Theme_DAL::update_theme( $theme_id, $update_data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// DAL already decodes JSON fields
			$theme = SUPER_Theme_DAL::get_theme( $theme_id );

			return rest_ensure_response( $theme );
		}

		/**
		 * Delete theme
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function delete_theme( $request ) {
			$theme_id = absint( $request['id'] );

			$theme = SUPER_Theme_DAL::get_theme( $theme_id );
			if ( is_wp_error( $theme ) ) {
				return $theme;
			}

			// System themes cannot be deleted
			if ( $theme['is_system'] ) {
				return new WP_Error(
					'cannot_delete_system_theme',
					__( 'System themes cannot be deleted.', 'super-forms' ),
					array( 'status' => 403 )
				);
			}

			// Check ownership for custom themes (unless admin)
			if ( $theme['user_id'] && $theme['user_id'] != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'You can only delete your own themes.', 'super-forms' ),
					array( 'status' => 403 )
				);
			}

			$result = SUPER_Theme_DAL::delete_theme( $theme_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'deleted' => $theme_id,
				)
			);
		}

		/**
		 * Apply theme to form
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function apply_theme( $request ) {
			$theme_id = absint( $request['id'] );
			$form_id  = absint( $request['form_id'] );

			// Verify theme exists and is not a stub
			$theme = SUPER_Theme_DAL::get_theme( $theme_id );
			if ( is_wp_error( $theme ) ) {
				return $theme;
			}

			if ( $theme['is_stub'] ) {
				return new WP_Error(
					'theme_is_stub',
					__( 'Cannot apply a stub theme. This theme is coming soon.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			// Get form
			if ( ! class_exists( 'SUPER_Form_DAL' ) ) {
				return new WP_Error(
					'form_dal_unavailable',
					__( 'Form data access is not available.', 'super-forms' ),
					array( 'status' => 500 )
				);
			}

			$form = SUPER_Form_DAL::get_form( $form_id );
			if ( is_wp_error( $form ) ) {
				return $form;
			}

			// Update form settings with theme
			$settings                   = is_string( $form['settings'] ) ? json_decode( $form['settings'], true ) : $form['settings'];
			$settings                   = $settings ?? array();
			$settings['currentThemeId'] = $theme_id;
			$settings['globalStyles']   = is_string( $theme['styles'] ) ? json_decode( $theme['styles'], true ) : $theme['styles'];

			$result = SUPER_Form_DAL::update_form(
				$form_id,
				array(
					'settings' => $settings,
				)
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'success'  => true,
					'theme_id' => $theme_id,
					'form_id'  => $form_id,
					'message'  => sprintf(
						/* translators: %s: Theme name */
						__( 'Theme "%s" applied to form.', 'super-forms' ),
						$theme['name']
					),
				)
			);
		}

		/**
		 * Duplicate theme
		 *
		 * @param WP_REST_Request $request The REST request
		 * @return WP_REST_Response|WP_Error
		 * @since 6.6.0
		 */
		public function duplicate_theme( $request ) {
			$theme_id = absint( $request['id'] );
			$new_name = $request->get_param( 'name' );

			// Get source theme
			$source_theme = SUPER_Theme_DAL::get_theme( $theme_id );
			if ( is_wp_error( $source_theme ) ) {
				return $source_theme;
			}

			// Cannot duplicate stub themes
			if ( $source_theme['is_stub'] ) {
				return new WP_Error(
					'cannot_duplicate_stub',
					__( 'Cannot duplicate a stub theme.', 'super-forms' ),
					array( 'status' => 400 )
				);
			}

			// Generate name if not provided
			if ( empty( $new_name ) ) {
				$new_name = sprintf(
					/* translators: %s: Original theme name */
					__( '%s (Copy)', 'super-forms' ),
					$source_theme['name']
				);
			}

			// DAL already decodes, so styles/preview_colors are arrays
			$theme_data = array(
				'name'           => $new_name,
				'description'    => $source_theme['description'],
				'category'       => $source_theme['category'],
				'styles'         => $source_theme['styles'],
				'preview_colors' => $source_theme['preview_colors'],
				'user_id'        => get_current_user_id(),
			);

			$new_theme_id = SUPER_Theme_DAL::create_theme( $theme_data );

			if ( is_wp_error( $new_theme_id ) ) {
				return $new_theme_id;
			}

			// DAL already decodes JSON fields
			$new_theme = SUPER_Theme_DAL::get_theme( $new_theme_id );

			return rest_ensure_response( $new_theme );
		}
	}

endif;
