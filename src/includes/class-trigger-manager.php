<?php
/**
 * Trigger Manager - Business Logic Layer
 *
 * Sits between REST API and DAL. Handles business logic, validation,
 * sanitization, and permissions for triggers/actions system.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Trigger_Manager
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Trigger_Manager' ) ) :

	/**
	 * SUPER_Trigger_Manager Class
	 */
	class SUPER_Trigger_Manager {

		/**
		 * Create trigger with actions (atomic operation)
		 *
		 * @param array $trigger_data  Trigger data
		 * @param array $actions_data  Array of action configurations
		 * @return array|WP_Error Trigger data with actions, or error
		 * @since 6.5.0
		 */
		public static function create_trigger_with_actions( $trigger_data, $actions_data = array() ) {
			// Validate permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to create triggers', 'super-forms' )
				);
			}

			// Validate trigger data
			$validation = self::validate_trigger_data( $trigger_data );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// Sanitize trigger data
			$trigger_data = self::sanitize_trigger_data( $trigger_data );

			// Create trigger
			$trigger_id = SUPER_Trigger_DAL::create_trigger( $trigger_data );
			if ( is_wp_error( $trigger_id ) ) {
				return $trigger_id;
			}

			// Create actions
			$created_actions = array();
			if ( ! empty( $actions_data ) && is_array( $actions_data ) ) {
				foreach ( $actions_data as $action_data ) {
					// Validate action
					$validation = self::validate_action_data( $action_data );
					if ( is_wp_error( $validation ) ) {
						// Rollback trigger creation
						SUPER_Trigger_DAL::delete_trigger( $trigger_id );
						return $validation;
					}

					// Sanitize action
					$action_data = self::sanitize_action_data( $action_data );

					// Create action
					$action_id = SUPER_Trigger_DAL::create_action( $trigger_id, $action_data );
					if ( is_wp_error( $action_id ) ) {
						// Rollback
						SUPER_Trigger_DAL::delete_trigger( $trigger_id );
						return $action_id;
					}

					$created_actions[] = array(
						'id'              => $action_id,
						'action_type'     => $action_data['action_type'],
						'action_config'   => $action_data['action_config'],
						'execution_order' => $action_data['execution_order'] ?? 10,
						'enabled'         => $action_data['enabled'] ?? 1,
					);
				}
			}

			// Return complete trigger data
			return array(
				'id'              => $trigger_id,
				'trigger_name'    => $trigger_data['trigger_name'],
				'scope'           => $trigger_data['scope'],
				'scope_id'        => $trigger_data['scope_id'] ?? null,
				'event_id'        => $trigger_data['event_id'],
				'conditions'      => $trigger_data['conditions'],
				'enabled'         => $trigger_data['enabled'] ?? 1,
				'execution_order' => $trigger_data['execution_order'] ?? 10,
				'actions'         => $created_actions,
			);
		}

		/**
		 * Validate trigger data
		 *
		 * @param array $data Trigger data
		 * @return bool|WP_Error True if valid, WP_Error if invalid
		 * @since 6.5.0
		 */
		public static function validate_trigger_data( $data ) {
			// Check required fields
			if ( empty( $data['trigger_name'] ) ) {
				return new WP_Error(
					'missing_trigger_name',
					__( 'Trigger name is required', 'super-forms' )
				);
			}

			if ( empty( $data['event_id'] ) ) {
				return new WP_Error(
					'missing_event_id',
					__( 'Event ID is required', 'super-forms' )
				);
			}

			// Validate scope
			$valid_scopes = array( 'form', 'global', 'user', 'role', 'site', 'network' );
			$scope        = $data['scope'] ?? 'form';

			if ( ! in_array( $scope, $valid_scopes, true ) ) {
				return new WP_Error(
					'invalid_scope',
					sprintf(
						/* translators: %1$s: provided scope, %2$s: valid scopes list */
						__( 'Invalid scope "%1$s". Must be one of: %2$s', 'super-forms' ),
						$scope,
						implode( ', ', $valid_scopes )
					)
				);
			}

			// Validate scope_id requirements
			$requires_scope_id = array( 'form', 'user', 'site' );
			if ( in_array( $scope, $requires_scope_id, true ) && empty( $data['scope_id'] ) ) {
				return new WP_Error(
					'missing_scope_id',
					sprintf(
						/* translators: %s: scope type */
						__( 'Scope "%s" requires a scope_id', 'super-forms' ),
						$scope
					)
				);
			}

			// Validate event exists in registry
			$registry = SUPER_Trigger_Registry::get_instance();
			$event    = $registry->get_event( $data['event_id'] );
			if ( null === $event ) {
				return new WP_Error(
					'invalid_event_id',
					sprintf(
						/* translators: %s: event ID */
						__( 'Event "%s" is not registered', 'super-forms' ),
						$data['event_id']
					)
				);
			}

			// Validate conditions structure
			if ( ! empty( $data['conditions'] ) ) {
				$validation = SUPER_Trigger_Conditions::validate( $data['conditions'] );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}
			}

			return true;
		}

		/**
		 * Validate action data
		 *
		 * @param array $data Action data
		 * @return bool|WP_Error True if valid, WP_Error if invalid
		 * @since 6.5.0
		 */
		public static function validate_action_data( $data ) {
			// Check required fields
			if ( empty( $data['action_type'] ) ) {
				return new WP_Error(
					'missing_action_type',
					__( 'Action type is required', 'super-forms' )
				);
			}

			// Validate action exists in registry
			$registry = SUPER_Trigger_Registry::get_instance();
			$instance = $registry->get_action_instance( $data['action_type'] );

			if ( null === $instance ) {
				return new WP_Error(
					'invalid_action_type',
					sprintf(
						/* translators: %s: action type */
						__( 'Action "%s" is not registered', 'super-forms' ),
						$data['action_type']
					)
				);
			}

			// Validate action config using action's validation
			if ( ! empty( $data['action_config'] ) ) {
				$validation = $instance->validate_config( $data['action_config'] );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}
			}

			return true;
		}

		/**
		 * Sanitize trigger data
		 *
		 * @param array $data Trigger data
		 * @return array Sanitized data
		 * @since 6.5.0
		 */
		public static function sanitize_trigger_data( $data ) {
			return array(
				'trigger_name'    => sanitize_text_field( $data['trigger_name'] ),
				'event_id'        => sanitize_text_field( $data['event_id'] ),
				'scope'           => sanitize_text_field( $data['scope'] ?? 'form' ),
				'scope_id'        => ! empty( $data['scope_id'] ) ? absint( $data['scope_id'] ) : null,
				'conditions'      => ! empty( $data['conditions'] ) ? $data['conditions'] : '',
				'enabled'         => absint( $data['enabled'] ?? 1 ),
				'execution_order' => absint( $data['execution_order'] ?? 10 ),
			);
		}

		/**
		 * Sanitize action data
		 *
		 * @param array $data Action data
		 * @return array Sanitized data
		 * @since 6.5.0
		 */
		public static function sanitize_action_data( $data ) {
			// Get action instance for sanitization
			$registry = SUPER_Trigger_Registry::get_instance();
			$instance = $registry->get_action_instance( $data['action_type'] );

			$sanitized_config = array();
			if ( $instance && ! empty( $data['action_config'] ) ) {
				$sanitized_config = $instance->sanitize_config( $data['action_config'] );
			}

			return array(
				'action_type'     => sanitize_text_field( $data['action_type'] ),
				'action_config'   => $sanitized_config,
				'execution_order' => absint( $data['execution_order'] ?? 10 ),
				'enabled'         => absint( $data['enabled'] ?? 1 ),
			);
		}

		/**
		 * Check if user can manage trigger
		 *
		 * @param int $trigger_id Trigger ID
		 * @param int $user_id    User ID
		 * @return bool True if allowed, false otherwise
		 * @since 6.5.0
		 */
		public static function can_user_manage_trigger( $trigger_id, $user_id = null ) {
			if ( null === $user_id ) {
				$user_id = get_current_user_id();
			}

			// Get trigger
			$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
			if ( is_wp_error( $trigger ) ) {
				return false;
			}

			// For Phase 1: Only manage_options can manage triggers
			// Phase 1.5: Add granular permissions based on scope
			if ( ! user_can( $user_id, 'manage_options' ) ) {
				return false;
			}

			// Future: Check scope-based permissions
			// - form scope: Form editors can manage
			// - user scope: User can manage their own triggers
			// - global scope: Only manage_options
			// - role scope: Only manage_options

			return true;
		}

		/**
		 * Resolve triggers for event and context
		 *
		 * Determines which triggers should execute for a given event,
		 * considering scope, conditions, and enabled status.
		 *
		 * @param string $event_id Event identifier
		 * @param array  $context  Event context data
		 * @return array Array of applicable triggers
		 * @since 6.5.0
		 */
		public static function resolve_triggers_for_event( $event_id, $context ) {
			// Get all potentially applicable triggers from DAL
			$all_triggers = SUPER_Trigger_DAL::get_active_triggers_for_context( $event_id, $context );

			if ( empty( $all_triggers ) ) {
				return array();
			}

			// Filter triggers by conditions
			$applicable_triggers = array();

			foreach ( $all_triggers as $trigger ) {
				// Evaluate conditions
				$conditions_pass = true;

				if ( ! empty( $trigger['conditions'] ) ) {
					$conditions_pass = SUPER_Trigger_Conditions::evaluate(
						$trigger['conditions'],
						$context
					);
				}

				if ( $conditions_pass ) {
					$applicable_triggers[] = $trigger;
				}
			}

			/**
			 * Filter triggers before execution
			 *
			 * @param array  $applicable_triggers Triggers that passed conditions
			 * @param string $event_id            Event identifier
			 * @param array  $context             Event context
			 * @since 6.5.0
			 */
			return apply_filters(
				'super_resolved_triggers_for_event',
				$applicable_triggers,
				$event_id,
				$context
			);
		}

		/**
		 * Get trigger with full actions
		 *
		 * @param int $trigger_id Trigger ID
		 * @return array|WP_Error Trigger data with actions, or error
		 * @since 6.5.0
		 */
		public static function get_trigger_with_actions( $trigger_id ) {
			// Get trigger
			$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
			if ( is_wp_error( $trigger ) ) {
				return $trigger;
			}

			// Get actions
			$actions             = SUPER_Trigger_DAL::get_actions( $trigger_id, false );
			$trigger['actions'] = $actions;

			return $trigger;
		}

		/**
		 * Update trigger with actions
		 *
		 * @param int   $trigger_id    Trigger ID
		 * @param array $trigger_data  Updated trigger data
		 * @param array $actions_data  Updated actions (optional)
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function update_trigger_with_actions( $trigger_id, $trigger_data, $actions_data = null ) {
			// Validate permissions
			if ( ! self::can_user_manage_trigger( $trigger_id ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to update this trigger', 'super-forms' )
				);
			}

			// Update trigger if data provided
			if ( ! empty( $trigger_data ) ) {
				// Validate
				$validation = self::validate_trigger_data( $trigger_data );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}

				// Sanitize
				$trigger_data = self::sanitize_trigger_data( $trigger_data );

				// Update
				$result = SUPER_Trigger_DAL::update_trigger( $trigger_id, $trigger_data );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}

			// Update actions if provided
			if ( null !== $actions_data && is_array( $actions_data ) ) {
				// Get current actions
				$current_actions    = SUPER_Trigger_DAL::get_actions( $trigger_id, false );
				$current_action_ids = wp_list_pluck( $current_actions, 'id' );

				// Track which actions to keep
				$updated_action_ids = array();

				// Process updated/new actions
				foreach ( $actions_data as $action_data ) {
					if ( ! empty( $action_data['id'] ) ) {
						// Update existing action
						$action_id = absint( $action_data['id'] );

						// Validate
						$validation = self::validate_action_data( $action_data );
						if ( is_wp_error( $validation ) ) {
							return $validation;
						}

						// Sanitize
						$action_data = self::sanitize_action_data( $action_data );

						// Update
						$result = SUPER_Trigger_DAL::update_action( $action_id, $action_data );
						if ( is_wp_error( $result ) ) {
							return $result;
						}

						$updated_action_ids[] = $action_id;
					} else {
						// Create new action
						$validation = self::validate_action_data( $action_data );
						if ( is_wp_error( $validation ) ) {
							return $validation;
						}

						$action_data = self::sanitize_action_data( $action_data );

						$action_id = SUPER_Trigger_DAL::create_action( $trigger_id, $action_data );
						if ( is_wp_error( $action_id ) ) {
							return $action_id;
						}

						$updated_action_ids[] = $action_id;
					}
				}

				// Delete actions that were removed
				$actions_to_delete = array_diff( $current_action_ids, $updated_action_ids );
				foreach ( $actions_to_delete as $action_id ) {
					SUPER_Trigger_DAL::delete_action( $action_id );
				}
			}

			return true;
		}

		/**
		 * Delete trigger (permission check included)
		 *
		 * @param int $trigger_id Trigger ID
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function delete_trigger( $trigger_id ) {
			// Validate permissions
			if ( ! self::can_user_manage_trigger( $trigger_id ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to delete this trigger', 'super-forms' )
				);
			}

			// Delete trigger (actions cascade via DAL)
			return SUPER_Trigger_DAL::delete_trigger( $trigger_id );
		}

		/**
		 * Duplicate trigger
		 *
		 * @param int   $trigger_id Trigger ID to duplicate
		 * @param array $overrides  Optional data to override
		 * @return array|WP_Error New trigger data, or error
		 * @since 6.5.0
		 */
		public static function duplicate_trigger( $trigger_id, $overrides = array() ) {
			// Get original trigger with actions
			$original = self::get_trigger_with_actions( $trigger_id );
			if ( is_wp_error( $original ) ) {
				return $original;
			}

			// Prepare new trigger data
			$new_trigger_data = array(
				'trigger_name'    => $original['trigger_name'] . ' (Copy)',
				'event_id'        => $original['event_id'],
				'scope'           => $original['scope'],
				'scope_id'        => $original['scope_id'],
				'conditions'      => $original['conditions'],
				'enabled'         => $original['enabled'],
				'execution_order' => $original['execution_order'],
			);

			// Apply overrides
			$new_trigger_data = array_merge( $new_trigger_data, $overrides );

			// Duplicate actions
			$new_actions_data = array();
			foreach ( $original['actions'] as $action ) {
				$new_actions_data[] = array(
					'action_type'     => $action['action_type'],
					'action_config'   => $action['action_config'],
					'execution_order' => $action['execution_order'],
					'enabled'         => $action['enabled'],
				);
			}

			// Create new trigger with actions
			return self::create_trigger_with_actions( $new_trigger_data, $new_actions_data );
		}
	}

endif;
