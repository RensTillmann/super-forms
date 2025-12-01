<?php
/**
 * Automation Manager - Business Logic Layer
 *
 * Sits between REST API and DAL. Handles business logic, validation,
 * sanitization, and permissions for triggers/actions system.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Automation_Manager
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Manager' ) ) :

	/**
	 * SUPER_Automation_Manager Class
	 */
	class SUPER_Automation_Manager {

		/**
		 * Create trigger with actions (atomic operation)
		 *
		 * @param array $automation_data  Trigger data
		 * @param array $actions_data  Array of action configurations
		 * @return array|WP_Error Trigger data with actions, or error
		 * @since 6.5.0
		 */
		public static function create_automation_with_actions( $automation_data, $actions_data = array() ) {
			// Validate permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to create triggers', 'super-forms' )
				);
			}

			// Validate trigger data
			$validation = self::validate_automation_data( $automation_data );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// Sanitize trigger data
			$automation_data = self::sanitize_automation_data( $automation_data );

			// Create trigger
			$automation_id = SUPER_Automation_DAL::create_automation( $automation_data );
			if ( is_wp_error( $automation_id ) ) {
				return $automation_id;
			}

			// Create actions
			$created_actions = array();
			if ( ! empty( $actions_data ) && is_array( $actions_data ) ) {
				foreach ( $actions_data as $action_data ) {
					// Validate action
					$validation = self::validate_action_data( $action_data );
					if ( is_wp_error( $validation ) ) {
						// Rollback trigger creation
						SUPER_Automation_DAL::delete_automation( $automation_id );
						return $validation;
					}

					// Sanitize action
					$action_data = self::sanitize_action_data( $action_data );

					// Create action
					$action_id = SUPER_Automation_DAL::create_action( $automation_id, $action_data );
					if ( is_wp_error( $action_id ) ) {
						// Rollback
						SUPER_Automation_DAL::delete_automation( $automation_id );
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
				'id'              => $automation_id,
				'name'    => $automation_data['name'],
				'scope'           => $automation_data['scope'],
				'scope_id'        => $automation_data['scope_id'] ?? null,
				'event_id'        => $automation_data['event_id'],
				'conditions'      => $automation_data['conditions'],
				'enabled'         => $automation_data['enabled'] ?? 1,
				'execution_order' => $automation_data['execution_order'] ?? 10,
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
		public static function validate_automation_data( $data ) {
			// Check required fields
			if ( empty( $data['name'] ) ) {
				return new WP_Error(
					'missing_automation_name',
					__( 'Automation name is required', 'super-forms' )
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
			$registry = SUPER_Automation_Registry::get_instance();
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
				$validation = SUPER_Automation_Conditions::validate( $data['conditions'] );
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
			$registry = SUPER_Automation_Registry::get_instance();
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
		public static function sanitize_automation_data( $data ) {
			return array(
				'name'    => sanitize_text_field( $data['name'] ),
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
			$registry = SUPER_Automation_Registry::get_instance();
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
		 * @param int $automation_id Trigger ID
		 * @param int $user_id    User ID
		 * @return bool True if allowed, false otherwise
		 * @since 6.5.0
		 */
		public static function can_user_manage_automation( $automation_id, $user_id = null ) {
			if ( null === $user_id ) {
				$user_id = get_current_user_id();
			}

			// Get trigger
			$automation = SUPER_Automation_DAL::get_automation( $automation_id );
			if ( is_wp_error( $automation ) ) {
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
		public static function resolve_automations_for_event( $event_id, $context ) {
			// Load ALL enabled triggers (node-level scope architecture)
			$all_automations = SUPER_Automation_DAL::get_all_automations( true );

			if ( empty( $all_automations ) ) {
				return array();
			}

			$applicable_automations = array();

			// Filter automations using node-level scope checking
			foreach ( $all_automations as $automation ) {
				// Check if automation applies to this event based on workflow type
				$applies = false;

				if ( 'visual' === $automation['workflow_type'] ) {
					$applies = self::check_visual_workflow_applies( $automation, $event_id, $context );
				} elseif ( 'code' === $automation['workflow_type'] ) {
					$applies = self::check_code_workflow_applies( $automation, $event_id, $context );
				}

				if ( $applies ) {
					$applicable_automations[] = $automation;
				}
			}

			/**
			 * Filter automations before execution
			 *
			 * @param array  $applicable_automations Automations that passed node-level scope filtering
			 * @param string $event_id            Event identifier
			 * @param array  $context             Event context
			 * @since 6.5.0
			 */
			return apply_filters(
				'super_resolved_automations_for_event',
				$applicable_automations,
				$event_id,
				$context
			);
		}

		/**
		 * Check if visual workflow applies to event
		 *
		 * @param array  $automation  Trigger with workflow_graph
		 * @param string $event_id Event identifier
		 * @param array  $context  Event context
		 * @return bool True if workflow should execute
		 */
		private static function check_visual_workflow_applies( $automation, $event_id, $context ) {
			if ( empty( $automation['workflow_graph'] ) || empty( $automation['workflow_graph']['nodes'] ) ) {
				return false;
			}

			$nodes = $automation['workflow_graph']['nodes'];

			// Find event nodes that match this event type
			foreach ( $nodes as $node ) {
				// Check if node is an event node matching the event_id
				if ( $node['type'] !== $event_id ) {
					continue;
				}

				// Node matches event type, now check scope
				$node_config = isset( $node['config'] ) ? $node['config'] : array();

				// Check scope configuration
				if ( self::check_node_scope_match( $node_config, $context ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if code workflow applies to event
		 *
		 * @param array  $automation  Trigger with workflow_graph (actions array)
		 * @param string $event_id Event identifier
		 * @param array  $context  Event context
		 * @return bool True if workflow should execute
		 */
		private static function check_code_workflow_applies( $automation, $event_id, $context ) {
			if ( empty( $automation['workflow_graph'] ) || empty( $automation['workflow_graph']['actions'] ) ) {
				return false;
			}

			$actions = $automation['workflow_graph']['actions'];

			// Find event actions that match this event type
			foreach ( $actions as $action ) {
				// Check if action is an event action matching the event_id
				if ( $action['type'] !== $event_id ) {
					continue;
				}

				// Action matches event type, now check scope
				$action_config = isset( $action['config'] ) ? $action['config'] : array();

				// Check scope configuration
				if ( self::check_node_scope_match( $action_config, $context ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if node scope configuration matches event context
		 *
		 * Scope types:
		 * - 'all': Trigger for all forms (no formId required)
		 * - 'current': Trigger for specific form (formId must match context form_id)
		 * - 'specific': Trigger for specific form (formId must match context form_id)
		 * - No scope set: Default to 'all'
		 *
		 * @param array $node_config Node configuration
		 * @param array $context     Event context
		 * @return bool True if scope matches
		 */
		private static function check_node_scope_match( $node_config, $context ) {
			// Default scope is 'all'
			$scope = isset( $node_config['scope'] ) ? $node_config['scope'] : 'all';

			switch ( $scope ) {
				case 'all':
					// Execute for all forms
					return true;

				case 'current':
				case 'specific':
					// Execute only if formId matches
					if ( empty( $node_config['formId'] ) ) {
						// No formId specified, default to 'all'
						return true;
					}

					if ( empty( $context['form_id'] ) ) {
						// No form_id in context, cannot match
						return false;
					}

					// Check if formId matches context form_id
					return absint( $node_config['formId'] ) === absint( $context['form_id'] );

				default:
					// Unknown scope, default to 'all'
					return true;
			}
		}

		/**
		 * Get trigger with full actions
		 *
		 * @param int $automation_id Trigger ID
		 * @return array|WP_Error Trigger data with actions, or error
		 * @since 6.5.0
		 */
		public static function get_automation_with_actions( $automation_id ) {
			// Get trigger
			$automation = SUPER_Automation_DAL::get_automation( $automation_id );
			if ( is_wp_error( $automation ) ) {
				return $automation;
			}

			// Get actions
			$actions             = SUPER_Automation_DAL::get_actions( $automation_id, false );
			$automation['actions'] = $actions;

			return $automation;
		}

		/**
		 * Update trigger with actions
		 *
		 * @param int   $automation_id    Trigger ID
		 * @param array $automation_data  Updated trigger data
		 * @param array $actions_data  Updated actions (optional)
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function update_automation_with_actions( $automation_id, $automation_data, $actions_data = null ) {
			// Validate permissions
			if ( ! self::can_user_manage_automation( $automation_id ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to update this trigger', 'super-forms' )
				);
			}

			// Update trigger if data provided
			if ( ! empty( $automation_data ) ) {
				// Validate
				$validation = self::validate_automation_data( $automation_data );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}

				// Sanitize
				$automation_data = self::sanitize_automation_data( $automation_data );

				// Update
				$result = SUPER_Automation_DAL::update_automation( $automation_id, $automation_data );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}

			// Update actions if provided
			if ( null !== $actions_data && is_array( $actions_data ) ) {
				// Get current actions
				$current_actions    = SUPER_Automation_DAL::get_actions( $automation_id, false );
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
						$result = SUPER_Automation_DAL::update_action( $action_id, $action_data );
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

						$action_id = SUPER_Automation_DAL::create_action( $automation_id, $action_data );
						if ( is_wp_error( $action_id ) ) {
							return $action_id;
						}

						$updated_action_ids[] = $action_id;
					}
				}

				// Delete actions that were removed
				$actions_to_delete = array_diff( $current_action_ids, $updated_action_ids );
				foreach ( $actions_to_delete as $action_id ) {
					SUPER_Automation_DAL::delete_action( $action_id );
				}
			}

			return true;
		}

		/**
		 * Delete trigger (permission check included)
		 *
		 * @param int $automation_id Trigger ID
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function delete_automation( $automation_id ) {
			// Validate permissions
			if ( ! self::can_user_manage_automation( $automation_id ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to delete this trigger', 'super-forms' )
				);
			}

			// Delete trigger (actions cascade via DAL)
			return SUPER_Automation_DAL::delete_automation( $automation_id );
		}

		/**
		 * Duplicate trigger
		 *
		 * @param int   $automation_id Trigger ID to duplicate
		 * @param array $overrides  Optional data to override
		 * @return array|WP_Error New trigger data, or error
		 * @since 6.5.0
		 */
		public static function duplicate_automation( $automation_id, $overrides = array() ) {
			// Get original automation with actions
			$original = self::get_automation_with_actions( $automation_id );
			if ( is_wp_error( $original ) ) {
				return $original;
			}

			// Prepare new automation data
			$new_automation_data = array(
				'name'    => $original['name'] . ' (Copy)',
				'event_id'        => $original['event_id'],
				'scope'           => $original['scope'],
				'scope_id'        => $original['scope_id'],
				'conditions'      => $original['conditions'],
				'enabled'         => $original['enabled'],
				'execution_order' => $original['execution_order'],
			);

			// Apply overrides
			$new_automation_data = array_merge( $new_automation_data, $overrides );

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

			// Create new automation with actions
			return self::create_automation_with_actions( $new_automation_data, $new_actions_data );
		}
	}

endif;
