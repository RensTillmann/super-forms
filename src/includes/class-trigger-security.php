<?php
/**
 * Trigger Security Manager
 *
 * Provides security measures including rate limiting, suspicious pattern
 * detection, and security event logging for the triggers system.
 *
 * @package    SUPER_Forms
 * @subpackage Triggers
 * @since      6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Trigger_Security' ) ) :

	/**
	 * SUPER_Trigger_Security Class
	 *
	 * Handles security concerns including:
	 * - Rate limiting for API endpoints
	 * - Suspicious data pattern detection
	 * - Security event logging
	 * - Execution validation
	 *
	 * @since 6.5.0
	 */
	class SUPER_Trigger_Security {

		/**
		 * Singleton instance
		 *
		 * @var SUPER_Trigger_Security|null
		 */
		private static $instance = null;

		/**
		 * Default rate limit window in seconds
		 *
		 * @var int
		 */
		const RATE_LIMIT_WINDOW = 60;

		/**
		 * Default max requests per window
		 *
		 * @var int
		 */
		const RATE_LIMIT_MAX = 60;

		/**
		 * Rate limit transient prefix
		 *
		 * @var string
		 */
		const RATE_LIMIT_PREFIX = 'super_rate_';

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Trigger_Security
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			// Hook into REST API for rate limiting
			add_filter( 'rest_pre_dispatch', array( $this, 'check_rate_limit' ), 10, 3 );

			// Hook into trigger execution for validation
			add_action( 'super_trigger_before_execute', array( $this, 'validate_execution' ), 10, 2 );

			// Security event logging
			add_action( 'super_trigger_security_event', array( $this, 'log_security_event' ), 10, 2 );
		}

		/**
		 * Check rate limit for REST API requests
		 *
		 * @param mixed           $result  Response to replace the requested version
		 * @param WP_REST_Server  $server  Server instance
		 * @param WP_REST_Request $request Request used to generate the response
		 * @return mixed|WP_Error
		 */
		public function check_rate_limit( $result, $server, $request ) {
			// Only check for our API endpoints
			$route = $request->get_route();
			if ( strpos( $route, '/super-forms/v1/' ) !== 0 ) {
				return $result;
			}

			// Get rate limit settings
			$limit = $this->get_rate_limit( $request );
			$identifier = $this->get_rate_limit_identifier( $request );

			// Check current count
			$key = self::RATE_LIMIT_PREFIX . md5( $identifier . '_' . $route );
			$current = get_transient( $key );

			if ( false === $current ) {
				$current = array(
					'count'      => 0,
					'window_end' => time() + self::RATE_LIMIT_WINDOW,
				);
			}

			// Check if window expired
			if ( time() > $current['window_end'] ) {
				$current = array(
					'count'      => 0,
					'window_end' => time() + self::RATE_LIMIT_WINDOW,
				);
			}

			// Check limit
			if ( $current['count'] >= $limit ) {
				$this->log_security_event( 'rate_limit_exceeded', array(
					'identifier' => $identifier,
					'route'      => $route,
					'count'      => $current['count'],
					'limit'      => $limit,
				) );

				return new WP_Error(
					'rate_limit_exceeded',
					__( 'Rate limit exceeded. Please try again later.', 'super-forms' ),
					array(
						'status'      => 429,
						'retry_after' => $current['window_end'] - time(),
					)
				);
			}

			// Increment counter
			$current['count']++;
			set_transient( $key, $current, self::RATE_LIMIT_WINDOW );

			// Add rate limit headers to response
			add_filter( 'rest_post_dispatch', function( $response ) use ( $current, $limit ) {
				if ( $response instanceof WP_REST_Response ) {
					$response->header( 'X-RateLimit-Limit', $limit );
					$response->header( 'X-RateLimit-Remaining', max( 0, $limit - $current['count'] ) );
					$response->header( 'X-RateLimit-Reset', $current['window_end'] );
				}
				return $response;
			} );

			return $result;
		}

		/**
		 * Get rate limit for a request
		 *
		 * @param WP_REST_Request $request Request object
		 * @return int Rate limit
		 */
		private function get_rate_limit( $request ) {
			// Check API key rate limit
			$api_key = $request->get_header( 'X-API-Key' );
			if ( $api_key && class_exists( 'SUPER_Trigger_API_Keys' ) ) {
				$key_data = SUPER_Trigger_API_Keys::instance()->validate_key( $api_key );
				if ( $key_data && isset( $key_data['rate_limit'] ) ) {
					return (int) $key_data['rate_limit'];
				}
			}

			// Check user-based rate limit from settings
			if ( is_user_logged_in() ) {
				$user_limit = get_user_meta( get_current_user_id(), '_super_api_rate_limit', true );
				if ( $user_limit ) {
					return (int) $user_limit;
				}
			}

			// Default limit from settings or constant
			$settings = SUPER_Settings::get_settings();
			return (int) ( $settings['api_rate_limit'] ?? self::RATE_LIMIT_MAX );
		}

		/**
		 * Get identifier for rate limiting
		 *
		 * @param WP_REST_Request $request Request object
		 * @return string
		 */
		private function get_rate_limit_identifier( $request ) {
			// Use API key if present
			$api_key = $request->get_header( 'X-API-Key' );
			if ( $api_key ) {
				return 'api_' . substr( $api_key, 0, 12 );
			}

			// Use user ID if logged in
			if ( is_user_logged_in() ) {
				return 'user_' . get_current_user_id();
			}

			// Use IP address for anonymous requests
			return 'ip_' . $this->get_client_ip();
		}

		/**
		 * Validate trigger execution
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $data       Event data
		 * @throws Exception If validation fails in strict mode
		 */
		public function validate_execution( $trigger_id, $data ) {
			$suspicious = false;
			$reasons = array();

			// Check for rapid-fire executions
			$recent_key = 'super_recent_exec_' . $trigger_id;
			$recent = get_transient( $recent_key );

			if ( $recent && ( time() - $recent ) < 1 ) {
				$suspicious = true;
				$reasons[] = 'rapid_fire_execution';
			}

			set_transient( $recent_key, time(), 60 );

			// Check for suspicious data patterns
			$pattern_check = $this->check_suspicious_patterns( $data );
			if ( $pattern_check !== true ) {
				$suspicious = true;
				$reasons = array_merge( $reasons, (array) $pattern_check );
			}

			// Log and optionally block
			if ( $suspicious ) {
				$this->log_security_event( 'suspicious_execution', array(
					'trigger_id' => $trigger_id,
					'reasons'    => $reasons,
					'data_hash'  => md5( wp_json_encode( $data ) ),
				) );

				// Block in strict security mode
				if ( $this->is_strict_mode() ) {
					throw new Exception(
						__( 'Security check failed: ', 'super-forms' ) . implode( ', ', $reasons )
					);
				}
			}
		}

		/**
		 * Check for suspicious data patterns
		 *
		 * @param mixed $data Data to check
		 * @return true|array True if clean, array of reasons if suspicious
		 */
		public function check_suspicious_patterns( $data ) {
			if ( ! is_array( $data ) && ! is_string( $data ) ) {
				return true;
			}

			$json = is_string( $data ) ? $data : wp_json_encode( $data );
			$reasons = array();

			// Define suspicious patterns
			$patterns = array(
				'xss_script'       => '/<script\b[^>]*>/i',
				'xss_javascript'   => '/javascript\s*:/i',
				'xss_event'        => '/on\w+\s*=/i',
				'sql_injection'    => '/(\bunion\b.*\bselect\b|\bselect\b.*\bfrom\b.*\bwhere\b)/i',
				'path_traversal'   => '/\.\.\//',
				'null_byte'        => '/\x00|%00/',
				'php_tags'         => '/<\?php/i',
				'shell_command'    => '/(\||;|`|\$\(|\${)/i',
			);

			// Apply filters for custom patterns
			$patterns = apply_filters( 'super_trigger_security_patterns', $patterns );

			foreach ( $patterns as $name => $pattern ) {
				if ( preg_match( $pattern, $json ) ) {
					$reasons[] = $name;
				}
			}

			return empty( $reasons ) ? true : $reasons;
		}

		/**
		 * Sanitize data for safe execution
		 *
		 * @param mixed $data Data to sanitize
		 * @return mixed Sanitized data
		 */
		public function sanitize_data( $data ) {
			if ( is_array( $data ) ) {
				return array_map( array( $this, 'sanitize_data' ), $data );
			}

			if ( is_string( $data ) ) {
				// Remove potentially dangerous content
				$data = wp_kses( $data, array() ); // Strip all HTML
				$data = str_replace( array( '<?', '?>' ), '', $data ); // Remove PHP tags
			}

			return $data;
		}

		/**
		 * Log a security event
		 *
		 * @param string $event_type Event type
		 * @param array  $data       Event data
		 */
		public function log_security_event( $event_type, $data = array() ) {
			// Use compliance audit if available
			if ( class_exists( 'SUPER_Trigger_Compliance' ) ) {
				SUPER_Trigger_Compliance::instance()->log_compliance_action(
					'security_' . $event_type,
					array_merge( $data, array(
						'ip_address' => $this->get_client_ip(),
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ?
							sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
						'user_id'    => get_current_user_id(),
					) )
				);
			}

			// Also log to error log for critical events
			$critical_events = array(
				'unauthorized_access',
				'api_key_breach',
				'rate_limit_exceeded',
				'suspicious_execution',
			);

			if ( in_array( $event_type, $critical_events, true ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
					error_log( sprintf(
						'[Super Forms Security] %s: %s',
						$event_type,
						wp_json_encode( $data )
					) );
				}

				// Send admin alert for critical events
				$this->maybe_send_alert( $event_type, $data );
			}
		}

		/**
		 * Send security alert to admin
		 *
		 * @param string $event_type Event type
		 * @param array  $data       Event data
		 */
		private function maybe_send_alert( $event_type, $data ) {
			// Check if alerts are enabled
			$settings = SUPER_Settings::get_settings();
			if ( empty( $settings['security_alerts_enabled'] ) ) {
				return;
			}

			// Rate limit alerts (max 1 per hour per event type)
			$alert_key = 'super_security_alert_' . $event_type;
			if ( get_transient( $alert_key ) ) {
				return;
			}
			set_transient( $alert_key, true, HOUR_IN_SECONDS );

			$admin_email = get_option( 'admin_email' );
			$subject = sprintf(
				/* translators: %s: event type */
				__( '[Super Forms Security Alert] %s detected', 'super-forms' ),
				str_replace( '_', ' ', ucfirst( $event_type ) )
			);

			$message = sprintf(
				/* translators: 1: event type, 2: site URL, 3: timestamp, 4: IP address, 5: event data */
				__(
					"A security event has been detected on your site:\n\n" .
					"Event Type: %1\$s\n" .
					"Site: %2\$s\n" .
					"Time: %3\$s\n" .
					"IP Address: %4\$s\n\n" .
					"Event Data:\n%5\$s\n\n" .
					"This is an automated alert from Super Forms.",
					'super-forms'
				),
				$event_type,
				home_url(),
				current_time( 'mysql' ),
				$this->get_client_ip(),
				print_r( $data, true )
			);

			wp_mail( $admin_email, $subject, $message );
		}

		/**
		 * Check if strict security mode is enabled
		 *
		 * @return bool
		 */
		public function is_strict_mode() {
			$settings = SUPER_Settings::get_settings();
			return ! empty( $settings['triggers_strict_security'] );
		}

		/**
		 * Get client IP address
		 *
		 * @return string
		 */
		public function get_client_ip() {
			$headers = array(
				'HTTP_CF_CONNECTING_IP', // Cloudflare
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_REAL_IP',
				'REMOTE_ADDR',
			);

			foreach ( $headers as $header ) {
				if ( ! empty( $_SERVER[ $header ] ) ) {
					$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
					// Handle comma-separated IPs (X-Forwarded-For)
					if ( strpos( $ip, ',' ) !== false ) {
						$ips = explode( ',', $ip );
						$ip = trim( $ips[0] );
					}
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						return $ip;
					}
				}
			}

			return '0.0.0.0';
		}

		/**
		 * Verify webhook signature
		 *
		 * @param string $payload   Request body
		 * @param string $signature Signature from header
		 * @param string $secret    Webhook secret
		 * @param string $algorithm Hash algorithm (default: sha256)
		 * @return bool
		 */
		public function verify_webhook_signature( $payload, $signature, $secret, $algorithm = 'sha256' ) {
			$expected = hash_hmac( $algorithm, $payload, $secret );

			// Handle prefixed signatures (e.g., "sha256=...")
			if ( strpos( $signature, '=' ) !== false ) {
				list( , $signature ) = explode( '=', $signature, 2 );
			}

			return hash_equals( $expected, $signature );
		}

		/**
		 * Generate a secure random token
		 *
		 * @param int $length Token length (default: 32)
		 * @return string
		 */
		public function generate_token( $length = 32 ) {
			return bin2hex( random_bytes( $length / 2 ) );
		}

		/**
		 * Hash a value securely
		 *
		 * @param string $value Value to hash
		 * @return string
		 */
		public function hash( $value ) {
			return hash( 'sha256', $value );
		}

		/**
		 * Clear rate limit for an identifier
		 *
		 * @param string $identifier Rate limit identifier
		 * @param string $route      Optional specific route
		 * @return bool
		 */
		public function clear_rate_limit( $identifier, $route = '' ) {
			if ( $route ) {
				$key = self::RATE_LIMIT_PREFIX . md5( $identifier . '_' . $route );
				return delete_transient( $key );
			}

			// Clear all rate limits for this identifier (using pattern match is not possible with transients)
			// This would need to track all routes, for now just return false
			return false;
		}

		/**
		 * Get current rate limit status for an identifier
		 *
		 * @param string $identifier Rate limit identifier
		 * @param string $route      Route to check
		 * @return array
		 */
		public function get_rate_limit_status( $identifier, $route ) {
			$key = self::RATE_LIMIT_PREFIX . md5( $identifier . '_' . $route );
			$current = get_transient( $key );

			if ( false === $current ) {
				return array(
					'count'      => 0,
					'limit'      => self::RATE_LIMIT_MAX,
					'remaining'  => self::RATE_LIMIT_MAX,
					'window_end' => time() + self::RATE_LIMIT_WINDOW,
				);
			}

			$limit = self::RATE_LIMIT_MAX;
			return array(
				'count'      => $current['count'],
				'limit'      => $limit,
				'remaining'  => max( 0, $limit - $current['count'] ),
				'window_end' => $current['window_end'],
			);
		}
	}

endif;
