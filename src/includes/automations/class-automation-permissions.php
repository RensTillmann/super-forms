<?php
/**
 * Trigger Permissions Manager
 *
 * Handles capability-based permissions for the triggers system,
 * including custom capabilities and scope-based access control.
 *
 * @package    SUPER_Forms
 * @subpackage Triggers
 * @since      6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Permissions' ) ) :

	/**
	 * SUPER_Automation_Permissions Class
	 *
	 * Manages permissions for triggers including:
	 * - Custom WordPress capabilities
	 * - Trigger ownership checks
	 * - Scope-based access control
	 * - Form-specific permissions
	 *
	 * @since 6.5.0
	 */
	class SUPER_Automation_Permissions {

		/**
		 * Capability: Manage all triggers
		 */
		const CAP_MANAGE_TRIGGERS = 'super_manage_triggers';

		/**
		 * Capability: Execute triggers
		 */
		const CAP_EXECUTE_TRIGGERS = 'super_execute_triggers';

		/**
		 * Capability: View trigger logs
		 */
		const CAP_VIEW_LOGS = 'super_view_trigger_logs';

		/**
		 * Capability: Manage API credentials
		 */
		const CAP_MANAGE_CREDENTIALS = 'super_manage_api_credentials';

		/**
		 * Capability: Manage API keys
		 */
		const CAP_MANAGE_API_KEYS = 'super_manage_api_keys';

		/**
		 * Capability: Create global triggers
		 */
		const CAP_CREATE_GLOBAL_TRIGGERS = 'super_create_global_triggers';

		/**
		 * All custom capabilities
		 *
		 * @var array
		 */
		private static $capabilities = array(
			self::CAP_MANAGE_TRIGGERS,
			self::CAP_EXECUTE_TRIGGERS,
			self::CAP_VIEW_LOGS,
			self::CAP_MANAGE_CREDENTIALS,
			self::CAP_MANAGE_API_KEYS,
			self::CAP_CREATE_GLOBAL_TRIGGERS,
		);

		/**
		 * Initialize permissions system
		 */
		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'add_capabilities' ) );
			add_filter( 'user_has_cap', array( __CLASS__, 'filter_capabilities' ), 10, 4 );
		}

		/**
		 * Add capabilities to administrator role
		 */
		public static function add_capabilities() {
			$admin = get_role( 'administrator' );
			if ( ! $admin ) {
				return;
			}

			foreach ( self::$capabilities as $cap ) {
				if ( ! $admin->has_cap( $cap ) ) {
					$admin->add_cap( $cap );
				}
			}
		}

		/**
		 * Remove capabilities (for uninstall)
		 */
		public static function remove_capabilities() {
			$roles = array( 'administrator', 'editor', 'author' );

			foreach ( $roles as $role_name ) {
				$role = get_role( $role_name );
				if ( $role ) {
					foreach ( self::$capabilities as $cap ) {
						$role->remove_cap( $cap );
					}
				}
			}
		}

		/**
		 * Filter user capabilities dynamically
		 *
		 * @param array   $allcaps All capabilities of the user
		 * @param array   $caps    Required capabilities
		 * @param array   $args    Additional arguments
		 * @param WP_User $user    User object
		 * @return array Modified capabilities
		 */
		public static function filter_capabilities( $allcaps, $caps, $args, $user ) {
			// If user has manage_options, grant all trigger capabilities
			if ( isset( $allcaps['manage_options'] ) && $allcaps['manage_options'] ) {
				foreach ( self::$capabilities as $cap ) {
					$allcaps[ $cap ] = true;
				}
			}

			return $allcaps;
		}

		/**
		 * Check if user can manage a specific trigger
		 *
		 * @param int      $automation_id Trigger ID
		 * @param int|null $user_id    User ID (default: current user)
		 * @return bool
		 */
		public static function can_manage_trigger( $automation_id, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			if ( ! $user_id ) {
				return false;
			}

			// Admins can manage all triggers
			if ( user_can( $user_id, 'manage_options' ) ) {
				return true;
			}

			// Check specific capability
			if ( ! user_can( $user_id, self::CAP_MANAGE_TRIGGERS ) ) {
				return false;
			}

			// Get trigger to check ownership/scope
			$trigger = SUPER_Automation_DAL::get_trigger( $automation_id );
			if ( ! $trigger ) {
				return false;
			}

			// Check ownership
			if ( isset( $trigger['created_by'] ) && $trigger['created_by'] == $user_id ) {
				return true;
			}

			// Check scope-based permissions
			return self::can_access_scope( $trigger['scope'], $trigger['scope_id'], $user_id );
		}

		/**
		 * Check if user can create a trigger with given scope
		 *
		 * @param string   $scope    Trigger scope
		 * @param int|null $scope_id Scope ID
		 * @param int|null $user_id  User ID (default: current user)
		 * @return bool
		 */
		public static function can_create_trigger( $scope, $scope_id = null, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			if ( ! $user_id ) {
				return false;
			}

			// Admins can create any trigger
			if ( user_can( $user_id, 'manage_options' ) ) {
				return true;
			}

			// Check basic capability
			if ( ! user_can( $user_id, self::CAP_MANAGE_TRIGGERS ) ) {
				return false;
			}

			// Global triggers require special permission
			if ( 'global' === $scope ) {
				return user_can( $user_id, self::CAP_CREATE_GLOBAL_TRIGGERS );
			}

			// Check scope access
			return self::can_access_scope( $scope, $scope_id, $user_id );
		}

		/**
		 * Check if user can access a specific scope
		 *
		 * @param string   $scope    Scope type
		 * @param int|null $scope_id Scope ID
		 * @param int|null $user_id  User ID
		 * @return bool
		 */
		public static function can_access_scope( $scope, $scope_id, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			switch ( $scope ) {
				case 'form':
					// Check if user can edit the form
					return self::can_edit_form( $scope_id, $user_id );

				case 'user':
					// Users can only manage their own user-scoped triggers
					return $scope_id == $user_id;

				case 'role':
					// Role-scoped triggers require manage_options
					return user_can( $user_id, 'manage_options' );

				case 'global':
					// Global triggers require special permission
					return user_can( $user_id, self::CAP_CREATE_GLOBAL_TRIGGERS );

				default:
					return false;
			}
		}

		/**
		 * Check if user can edit a form
		 *
		 * @param int      $form_id Form ID
		 * @param int|null $user_id User ID
		 * @return bool
		 */
		public static function can_edit_form( $form_id, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			// Check if form exists
			$form = get_post( $form_id );
			if ( ! $form || 'super_form' !== $form->post_type ) {
				return false;
			}

			// Check standard edit capabilities
			return user_can( $user_id, 'edit_post', $form_id );
		}

		/**
		 * Check if user can view trigger logs
		 *
		 * @param int|null $user_id User ID
		 * @return bool
		 */
		public static function can_view_logs( $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			if ( user_can( $user_id, 'manage_options' ) ) {
				return true;
			}

			return user_can( $user_id, self::CAP_VIEW_LOGS );
		}

		/**
		 * Check if user can manage API credentials
		 *
		 * @param int|null $user_id User ID
		 * @return bool
		 */
		public static function can_manage_credentials( $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			if ( user_can( $user_id, 'manage_options' ) ) {
				return true;
			}

			return user_can( $user_id, self::CAP_MANAGE_CREDENTIALS );
		}

		/**
		 * Check if user can manage API keys
		 *
		 * @param int|null $user_id User ID
		 * @return bool
		 */
		public static function can_manage_api_keys( $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			if ( user_can( $user_id, 'manage_options' ) ) {
				return true;
			}

			return user_can( $user_id, self::CAP_MANAGE_API_KEYS );
		}

		/**
		 * Check if user can execute triggers
		 *
		 * @param int|null $user_id User ID
		 * @return bool
		 */
		public static function can_execute_triggers( $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			if ( user_can( $user_id, 'manage_options' ) ) {
				return true;
			}

			return user_can( $user_id, self::CAP_EXECUTE_TRIGGERS );
		}

		/**
		 * Get triggers that a user can access
		 *
		 * @param int|null $user_id User ID
		 * @return array Array of trigger IDs
		 */
		public static function get_accessible_triggers( $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			// Admins can access all
			if ( user_can( $user_id, 'manage_options' ) ) {
				return SUPER_Automation_DAL::get_all_automation_ids();
			}

			$accessible = array();

			// Get user's own triggers
			$user_triggers = SUPER_Automation_DAL::get_triggers_by_user( $user_id );
			foreach ( $user_triggers as $trigger ) {
				$accessible[] = $trigger['id'];
			}

			// Get triggers for forms user can edit
			$user_forms = self::get_user_editable_forms( $user_id );
			foreach ( $user_forms as $form_id ) {
				$form_triggers = SUPER_Automation_DAL::get_triggers( array(
					'scope'    => 'form',
					'scope_id' => $form_id,
				) );
				foreach ( $form_triggers as $trigger ) {
					$accessible[] = $trigger['id'];
				}
			}

			return array_unique( $accessible );
		}

		/**
		 * Get forms a user can edit
		 *
		 * @param int $user_id User ID
		 * @return array Array of form IDs
		 */
		private static function get_user_editable_forms( $user_id ) {
			$args = array(
				'post_type'      => 'super_form',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => array( 'publish', 'draft', 'private' ),
			);

			// If not admin, limit to user's own forms
			if ( ! user_can( $user_id, 'manage_options' ) ) {
				$args['author'] = $user_id;
			}

			return get_posts( $args );
		}

		/**
		 * Grant a capability to a role
		 *
		 * @param string $role_name Role name
		 * @param string $cap       Capability name
		 * @return bool
		 */
		public static function grant_capability( $role_name, $cap ) {
			if ( ! in_array( $cap, self::$capabilities, true ) ) {
				return false;
			}

			$role = get_role( $role_name );
			if ( ! $role ) {
				return false;
			}

			$role->add_cap( $cap );
			return true;
		}

		/**
		 * Revoke a capability from a role
		 *
		 * @param string $role_name Role name
		 * @param string $cap       Capability name
		 * @return bool
		 */
		public static function revoke_capability( $role_name, $cap ) {
			if ( ! in_array( $cap, self::$capabilities, true ) ) {
				return false;
			}

			$role = get_role( $role_name );
			if ( ! $role ) {
				return false;
			}

			$role->remove_cap( $cap );
			return true;
		}

		/**
		 * Get all trigger capabilities
		 *
		 * @return array
		 */
		public static function get_capabilities() {
			return self::$capabilities;
		}

		/**
		 * Check REST API permissions
		 *
		 * @param WP_REST_Request $request Request object
		 * @param string          $action  Action being performed
		 * @return bool|WP_Error
		 */
		public static function check_rest_permission( $request, $action = 'read' ) {
			// Check for API key authentication
			$api_key = $request->get_header( 'X-API-Key' );
			if ( $api_key && class_exists( 'SUPER_Automation_API_Keys' ) ) {
				$key_data = SUPER_Automation_API_Keys::instance()->validate_key( $api_key );
				if ( $key_data ) {
					// Set user context
					wp_set_current_user( $key_data['user_id'] );

					// Check key permissions
					$permissions = $key_data['permissions'];
					if ( in_array( 'triggers', $permissions, true ) || in_array( $action, $permissions, true ) ) {
						return true;
					}

					return new WP_Error(
						'rest_forbidden',
						__( 'API key does not have required permissions', 'super-forms' ),
						array( 'status' => 403 )
					);
				}

				return new WP_Error(
					'rest_forbidden',
					__( 'Invalid API key', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			// Check WordPress authentication
			if ( ! is_user_logged_in() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Authentication required', 'super-forms' ),
					array( 'status' => 401 )
				);
			}

			// Check capabilities based on action
			switch ( $action ) {
				case 'read':
				case 'list':
					return self::can_view_logs() || self::can_manage_trigger( 0 );

				case 'create':
				case 'update':
				case 'delete':
					return current_user_can( self::CAP_MANAGE_TRIGGERS );

				case 'execute':
					return self::can_execute_triggers();

				default:
					return current_user_can( 'manage_options' );
			}
		}
	}

endif;

// Initialize permissions
add_action( 'init', array( 'SUPER_Automation_Permissions', 'init' ) );
