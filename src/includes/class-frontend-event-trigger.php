<?php
/**
 * Frontend Event Trigger System
 *
 * Unified system for triggering automation events from the frontend.
 * All event types (button clicks, field interactions, step navigation, etc.)
 * go through a single AJAX endpoint with type-specific validation and
 * context building.
 *
 * @package Super_Forms
 * @since 6.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SUPER_Frontend_Event_Trigger' ) ) :

	/**
	 * SUPER_Frontend_Event_Trigger Class
	 *
	 * Handles frontend-initiated automation events through a unified entry point.
	 */
	class SUPER_Frontend_Event_Trigger {

		/**
		 * Registered frontend event types
		 *
		 * @var array
		 */
		private static $event_types = array();

		/**
		 * Whether the class has been initialized
		 *
		 * @var bool
		 */
		private static $initialized = false;

		/**
		 * Initialize - register AJAX handler and built-in event types
		 *
		 * @return void
		 */
		public static function init() {
			if ( self::$initialized ) {
				return;
			}

			// Register AJAX handlers (with and without priv for logged-in/guests)
			add_action( 'wp_ajax_super_trigger_frontend_event', array( __CLASS__, 'handle_request' ) );
			add_action( 'wp_ajax_nopriv_super_trigger_frontend_event', array( __CLASS__, 'handle_request' ) );

			// Register built-in event types
			self::register_builtin_event_types();

			/**
			 * Fires after frontend event trigger system initializes.
			 * Use this hook to register custom event types.
			 *
			 * @since 6.7.0
			 */
			do_action( 'super_frontend_event_trigger_init' );

			self::$initialized = true;
		}

		/**
		 * Register a frontend event type
		 *
		 * @param string $type_id Unique event type identifier (e.g., 'button_click').
		 * @param array  $config  Event type configuration.
		 * @return void
		 */
		public static function register_frontend_event_type( $type_id, $config ) {
			$defaults = array(
				'event_pattern'       => '',
				'required_params'     => array(),
				'optional_params'     => array(),
				'validation_callback' => null,
				'context_builder'     => null,
				'supports_sync'       => true,
				'rate_limit'          => null,
			);

			self::$event_types[ $type_id ] = wp_parse_args( $config, $defaults );
		}

		/**
		 * Get a registered event type config
		 *
		 * @param string $type_id Event type identifier.
		 * @return array|null Event type config or null if not found.
		 */
		public static function get_event_type( $type_id ) {
			return isset( self::$event_types[ $type_id ] ) ? self::$event_types[ $type_id ] : null;
		}

		/**
		 * Get all registered event types
		 *
		 * @return array All registered event types.
		 */
		public static function get_all_event_types() {
			return self::$event_types;
		}

		/**
		 * Main request handler
		 *
		 * @return void
		 */
		public static function handle_request() {
			// 1. Verify nonce
			if ( ! check_ajax_referer( 'super_frontend_event', 'nonce', false ) ) {
				wp_send_json_error(
					array(
						'code'    => 'invalid_nonce',
						'message' => __( 'Security check failed. Please refresh and try again.', 'super-forms' ),
					),
					403
				);
			}

			// 2. Get and validate event type
			$event_type = isset( $_POST['event_type'] ) ? sanitize_key( $_POST['event_type'] ) : '';
			if ( ! isset( self::$event_types[ $event_type ] ) ) {
				wp_send_json_error(
					array(
						'code'    => 'invalid_event_type',
						'message' => __( 'Unknown event type.', 'super-forms' ),
					),
					400
				);
			}

			$config = self::$event_types[ $event_type ];

			// 3. Extract and validate required params
			$params = self::extract_params( $config );
			if ( is_wp_error( $params ) ) {
				wp_send_json_error(
					array(
						'code'    => $params->get_error_code(),
						'message' => $params->get_error_message(),
					),
					400
				);
			}

			// 4. Run type-specific validation
			if ( $config['validation_callback'] && is_callable( $config['validation_callback'] ) ) {
				$validation = call_user_func( $config['validation_callback'], $params );
				if ( is_wp_error( $validation ) ) {
					wp_send_json_error(
						array(
							'code'    => $validation->get_error_code(),
							'message' => $validation->get_error_message(),
						),
						400
					);
				}
			}

			// 5. Check rate limit
			if ( $config['rate_limit'] ) {
				$rate_check = self::check_rate_limit( $event_type, $params, $config['rate_limit'] );
				if ( is_wp_error( $rate_check ) ) {
					wp_send_json_error(
						array(
							'code'    => 'rate_limited',
							'message' => $rate_check->get_error_message(),
						),
						429
					);
				}
			}

			// 6. Build event ID from pattern
			$event_id = self::build_event_id( $config['event_pattern'], $params );

			// 7. Build context
			$context = array(
				'form_id'    => isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0,
				'user_id'    => get_current_user_id(),
				'user_ip'    => SUPER_Common::real_ip(),
				'timestamp'  => current_time( 'c' ),
				'source_url' => wp_get_referer() ? wp_get_referer() : '',
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
			);

			// Add type-specific context
			if ( $config['context_builder'] && is_callable( $config['context_builder'] ) ) {
				$type_context = call_user_func( $config['context_builder'], $params );
				if ( is_array( $type_context ) ) {
					$context = array_merge( $context, $type_context );
				}
			}

			/**
			 * Filter the context before firing the event.
			 *
			 * @since 6.7.0
			 * @param array  $context    Event context.
			 * @param string $event_id   Full event identifier.
			 * @param string $event_type Event type (e.g., 'button_click').
			 * @param array  $params     Extracted parameters.
			 */
			$context = apply_filters( 'super_frontend_event_context', $context, $event_id, $event_type, $params );

			// 8. Fire automation event
			$start_time = microtime( true );
			$result     = null;

			if ( class_exists( 'SUPER_Automation_Executor' ) ) {
				$result = SUPER_Automation_Executor::fire_event( $event_id, $context );
			}

			$execution_time = ( microtime( true ) - $start_time ) * 1000;

			/**
			 * Fires after a frontend event has been processed.
			 *
			 * @since 6.7.0
			 * @param string $event_id       Full event identifier.
			 * @param array  $context        Event context.
			 * @param mixed  $result         Result from automation executor.
			 * @param float  $execution_time Execution time in milliseconds.
			 */
			do_action( 'super_frontend_event_fired', $event_id, $context, $result, $execution_time );

			// 9. Format and return response
			$response = self::format_response( $result, $execution_time );

			if ( $response['success'] ) {
				wp_send_json_success( $response['data'] );
			} else {
				wp_send_json_error( $response['error'], 500 );
			}
		}

		/**
		 * Extract params from POST based on config
		 *
		 * @param array $config Event type configuration.
		 * @return array|WP_Error Extracted params or error.
		 */
		private static function extract_params( $config ) {
			$params = array();

			// Required params
			foreach ( $config['required_params'] as $param ) {
				if ( ! isset( $_POST[ $param ] ) || '' === $_POST[ $param ] ) {
					return new WP_Error(
						'missing_param',
						/* translators: %s: parameter name */
						sprintf( __( 'Missing required parameter: %s', 'super-forms' ), $param )
					);
				}
				$params[ $param ] = self::sanitize_param( $param, $_POST[ $param ] );
			}

			// Optional params
			foreach ( $config['optional_params'] as $param ) {
				if ( isset( $_POST[ $param ] ) ) {
					$params[ $param ] = self::sanitize_param( $param, $_POST[ $param ] );
				}
			}

			return $params;
		}

		/**
		 * Sanitize parameter based on name/type
		 *
		 * @param string $name  Parameter name.
		 * @param mixed  $value Parameter value.
		 * @return mixed Sanitized value.
		 */
		private static function sanitize_param( $name, $value ) {
			// JSON params
			if ( in_array( $name, array( 'form_data', 'context', 'metadata' ), true ) ) {
				$decoded = json_decode( stripslashes( $value ), true );
				return is_array( $decoded ) ? $decoded : array();
			}

			// Integer params
			if ( in_array( $name, array( 'form_id', 'entry_id', 'step_index' ), true ) ) {
				return absint( $value );
			}

			// Key params (alphanumeric + underscore)
			if ( in_array( $name, array( 'event_id', 'event_type', 'interaction_type' ), true ) ) {
				return sanitize_key( $value );
			}

			// Default: text field
			return sanitize_text_field( $value );
		}

		/**
		 * Build event ID from pattern by replacing placeholders
		 *
		 * @param string $pattern Event pattern with {placeholders}.
		 * @param array  $params  Parameters to substitute.
		 * @return string Final event ID.
		 */
		private static function build_event_id( $pattern, $params ) {
			$event_id = $pattern;
			foreach ( $params as $key => $value ) {
				if ( is_string( $value ) || is_numeric( $value ) ) {
					$event_id = str_replace( '{' . $key . '}', $value, $event_id );
				}
			}
			return $event_id;
		}

		/**
		 * Format response for frontend
		 *
		 * @param mixed $result          Result from automation executor.
		 * @param float $execution_time_ms Execution time in milliseconds.
		 * @return array Formatted response.
		 */
		private static function format_response( $result, $execution_time_ms ) {
			// If fire_event returns structured response
			if ( is_array( $result ) && isset( $result['success'] ) ) {
				$data = isset( $result['data'] ) ? $result['data'] : array();
				$data['execution_time_ms'] = round( $execution_time_ms, 2 );

				return array(
					'success' => $result['success'],
					'data'    => $data,
					'error'   => isset( $result['error'] ) ? $result['error'] : null,
				);
			}

			// If fire_event returns WP_Error
			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					'data'    => null,
					'error'   => array(
						'code'    => $result->get_error_code(),
						'message' => $result->get_error_message(),
					),
				);
			}

			// Default success (fire_event completed without error)
			return array(
				'success' => true,
				'data'    => array(
					'message'           => __( 'Action completed successfully', 'super-forms' ),
					'execution_time_ms' => round( $execution_time_ms, 2 ),
				),
				'error'   => null,
			);
		}

		/**
		 * Check rate limit for event type
		 *
		 * @param string $event_type Event type identifier.
		 * @param array  $params     Request parameters.
		 * @param array  $config     Rate limit configuration.
		 * @return true|WP_Error True if allowed, WP_Error if rate limited.
		 */
		private static function check_rate_limit( $event_type, $params, $config ) {
			// Build rate limit key based on scope
			$key_parts = array( 'super_fe_rate', $event_type );

			$scope = isset( $config['scope'] ) ? $config['scope'] : 'user_form';

			switch ( $scope ) {
				case 'user':
					$user_id     = get_current_user_id();
					$key_parts[] = $user_id ? $user_id : SUPER_Common::real_ip();
					break;
				case 'form':
					$key_parts[] = isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0;
					break;
				case 'user_form':
					$user_id     = get_current_user_id();
					$user_key    = $user_id ? $user_id : SUPER_Common::real_ip();
					$form_id     = isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0;
					$key_parts[] = $user_key . '_' . $form_id;
					break;
				case 'ip':
					$key_parts[] = SUPER_Common::real_ip();
					break;
			}

			$key          = implode( '_', $key_parts );
			$count        = get_transient( $key );
			$count        = false === $count ? 0 : absint( $count );
			$max_requests = isset( $config['max_requests'] ) ? absint( $config['max_requests'] ) : 10;
			$window       = isset( $config['window_seconds'] ) ? absint( $config['window_seconds'] ) : 60;

			if ( $count >= $max_requests ) {
				return new WP_Error(
					'rate_limited',
					__( 'Too many requests. Please wait before trying again.', 'super-forms' )
				);
			}

			set_transient( $key, $count + 1, $window );
			return true;
		}

		/**
		 * Register built-in event types
		 *
		 * @return void
		 */
		private static function register_builtin_event_types() {
			// Button click events
			self::register_frontend_event_type(
				'button_click',
				array(
					'event_pattern'       => 'button.{event_id}.clicked',
					'required_params'     => array( 'form_id', 'button_id', 'event_id' ),
					'optional_params'     => array( 'form_data', 'entry_id', 'session_key', 'button_name' ),
					'validation_callback' => array( __CLASS__, 'validate_button_click' ),
					'context_builder'     => array( __CLASS__, 'build_button_context' ),
					'supports_sync'       => true,
					'rate_limit'          => array(
						'max_requests'   => 10,
						'window_seconds' => 60,
						'scope'          => 'user_form',
					),
				)
			);

			/**
			 * Fires after built-in event types are registered.
			 * Use this hook to modify built-in event types.
			 *
			 * @since 6.7.0
			 */
			do_action( 'super_frontend_event_types_registered' );
		}

		/**
		 * Validate button click event
		 *
		 * @param array $params Request parameters.
		 * @return true|WP_Error True if valid, WP_Error if invalid.
		 */
		public static function validate_button_click( $params ) {
			// Verify form exists
			$form = get_post( $params['form_id'] );
			if ( ! $form || 'super_form' !== $form->post_type ) {
				return new WP_Error( 'invalid_form', __( 'Form not found.', 'super-forms' ) );
			}

			// Verify event_id is not empty and is valid format
			if ( empty( $params['event_id'] ) ) {
				return new WP_Error( 'invalid_event_id', __( 'Event ID is required.', 'super-forms' ) );
			}

			/**
			 * Filter button click validation.
			 * Return WP_Error to reject the request.
			 *
			 * @since 6.7.0
			 * @param true|WP_Error $valid  Validation result.
			 * @param array         $params Request parameters.
			 */
			return apply_filters( 'super_validate_button_click', true, $params );
		}

		/**
		 * Build context for button click events
		 *
		 * @param array $params Request parameters.
		 * @return array Button-specific context.
		 */
		public static function build_button_context( $params ) {
			$context = array(
				'button_id'   => isset( $params['button_id'] ) ? $params['button_id'] : '',
				'button_name' => isset( $params['button_name'] ) ? $params['button_name'] : '',
				'event_name'  => isset( $params['event_id'] ) ? $params['event_id'] : '',
				'form_data'   => isset( $params['form_data'] ) ? $params['form_data'] : array(),
				'entry_id'    => isset( $params['entry_id'] ) ? absint( $params['entry_id'] ) : null,
				'session_key' => isset( $params['session_key'] ) ? $params['session_key'] : '',
			);

			// Add user email if logged in
			$user = wp_get_current_user();
			if ( $user->ID ) {
				$context['user_email'] = $user->user_email;
			}

			/**
			 * Filter button click context.
			 *
			 * @since 6.7.0
			 * @param array $context Button context.
			 * @param array $params  Request parameters.
			 */
			return apply_filters( 'super_button_click_context', $context, $params );
		}

		/**
		 * Get nonce for frontend scripts
		 *
		 * @return string Nonce value.
		 */
		public static function get_nonce() {
			return wp_create_nonce( 'super_frontend_event' );
		}
	}

endif;
