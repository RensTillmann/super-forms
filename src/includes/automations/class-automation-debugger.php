<?php
/**
 * Automation Debugger - Development Debugging Tools
 *
 * Provides debugging utilities for automation development:
 * - Static debug data collection during request
 * - Visual debug panel in admin footer
 * - Backtrace and timing information
 * - Real-time execution flow visualization
 *
 * Enable debug mode by adding to wp-config.php:
 * define('SUPER_AUTOMATIONS_DEBUG', true);
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Automation_Debugger
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Debugger' ) ) :

	/**
	 * SUPER_Automation_Debugger Class
	 */
	class SUPER_Automation_Debugger {

		/**
		 * Debug data collected during request
		 *
		 * @var array
		 */
		private static $debug_data = array();

		/**
		 * Request start time for timing calculations
		 *
		 * @var float
		 */
		private static $request_start_time = null;

		/**
		 * Panel minimized state cookie name
		 */
		const PANEL_STATE_COOKIE = 'super_debug_panel_minimized';

		/**
		 * Initialize debugger
		 *
		 * @since 6.5.0
		 */
		public static function init() {
			if ( ! self::is_debug_mode() ) {
				return;
			}

			self::$request_start_time = microtime( true );

			// Hook into admin footer for debug panel
			if ( is_admin() && current_user_can( 'manage_options' ) ) {
				add_action( 'admin_footer', array( __CLASS__, 'output_debug_panel' ), 1000 );
			}

			// Also hook into wp_footer for frontend debugging (when logged in as admin)
			if ( ! is_admin() && current_user_can( 'manage_options' ) ) {
				add_action( 'wp_footer', array( __CLASS__, 'output_debug_panel' ), 1000 );
			}
		}

		/**
		 * Check if debug mode is enabled
		 *
		 * @return bool
		 * @since 6.5.0
		 */
		public static function is_debug_mode() {
			return defined( 'SUPER_AUTOMATIONS_DEBUG' ) && SUPER_AUTOMATIONS_DEBUG;
		}

		/**
		 * Log debug message with context
		 *
		 * @param string $message Debug message
		 * @param mixed  $data    Optional data to include
		 * @param string $level   Log level (info, warning, error, success)
		 * @return void
		 * @since 6.5.0
		 */
		public static function debug( $message, $data = null, $level = 'info' ) {
			if ( ! self::is_debug_mode() ) {
				return;
			}

			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
			$caller = isset( $backtrace[1] ) ? $backtrace[1] : array();
			$caller_of_caller = isset( $backtrace[2] ) ? $backtrace[2] : array();

			$entry = array(
				'time'         => microtime( true ),
				'elapsed'      => self::$request_start_time ? microtime( true ) - self::$request_start_time : 0,
				'level'        => $level,
				'message'      => $message,
				'data'         => $data,
				'file'         => isset( $caller['file'] ) ? $caller['file'] : 'unknown',
				'line'         => isset( $caller['line'] ) ? $caller['line'] : 0,
				'function'     => isset( $caller['function'] ) ? $caller['function'] : 'unknown',
				'class'        => isset( $caller['class'] ) ? $caller['class'] : '',
				'called_from'  => isset( $caller_of_caller['function'] ) ? $caller_of_caller['function'] : '',
				'memory'       => memory_get_usage(),
				'peak_memory'  => memory_get_peak_usage(),
			);

			self::$debug_data['logs'][] = $entry;

			// Also write to error_log for persistent debugging
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf(
				'[SUPER_AUTOMATIONS_DEBUG] [%s] %s (in %s:%d)',
				strtoupper( $level ),
				$message,
				basename( $entry['file'] ),
				$entry['line']
			) );
		}

		/**
		 * Log automation event firing
		 *
		 * @param string $event_id Event identifier
		 * @param array  $context  Event context
		 * @since 6.5.0
		 */
		public static function log_event_fired( $event_id, $context = array() ) {
			self::$debug_data['events'][] = array(
				'event_id'  => $event_id,
				'form_id'   => isset( $context['form_id'] ) ? $context['form_id'] : null,
				'entry_id'  => isset( $context['entry_id'] ) ? $context['entry_id'] : null,
				'context'   => $context,
				'timestamp' => microtime( true ),
			);

			self::debug(
				sprintf( 'Event fired: %s', $event_id ),
				array(
					'event_id'   => $event_id,
					'form_id'    => isset( $context['form_id'] ) ? $context['form_id'] : null,
					'entry_id'   => isset( $context['entry_id'] ) ? $context['entry_id'] : null,
					'has_data'   => ! empty( $context['data'] ),
				),
				'info'
			);
		}

		/**
		 * Log automation evaluation
		 *
		 * @param int    $automation_id Automation ID
		 * @param string $name          Automation name
		 * @param bool   $matched       Whether conditions matched
		 * @param array  $conditions    Optional conditions that were evaluated
		 * @since 6.5.0
		 */
		public static function log_automation_evaluated( $automation_id, $name, $matched, $conditions = array() ) {
			self::$debug_data['automations'][] = array(
				'automation_id' => $automation_id,
				'name'          => $name,
				'matched'       => $matched,
				'conditions'    => $conditions,
				'timestamp'     => microtime( true ),
			);

			self::debug(
				sprintf( 'Automation evaluated: %s (#%d) - %s', $name, $automation_id, $matched ? 'MATCHED' : 'no match' ),
				array(
					'automation_id' => $automation_id,
					'name'          => $name,
					'matched'       => $matched,
				),
				$matched ? 'success' : 'info'
			);
		}

		/**
		 * Log action execution
		 *
		 * @param int    $action_id     Action ID
		 * @param string $action_type   Action type
		 * @param bool   $success       Whether action succeeded
		 * @param string $error_message Optional error message
		 * @since 6.5.0
		 */
		public static function log_action_executed( $action_id, $action_type, $success, $error_message = null ) {
			self::$debug_data['actions'][] = array(
				'action_id'     => $action_id,
				'action_type'   => $action_type,
				'success'       => $success,
				'error_message' => $error_message,
				'timestamp'     => microtime( true ),
			);

			self::debug(
				sprintf(
					'Action executed: %s (#%d) - %s%s',
					$action_type,
					$action_id,
					$success ? 'SUCCESS' : 'FAILED',
					$error_message ? ': ' . $error_message : ''
				),
				array(
					'action_id'   => $action_id,
					'action_type' => $action_type,
					'success'     => $success,
					'error'       => $error_message,
				),
				$success ? 'success' : 'error'
			);
		}

		/**
		 * Log individual condition evaluation
		 *
		 * @param string $condition_type Condition type
		 * @param string $field_name     Field being evaluated
		 * @param string $operator       Comparison operator
		 * @param mixed  $expected       Expected value
		 * @param mixed  $actual         Actual value
		 * @param bool   $passed         Whether condition passed
		 * @since 6.5.0
		 */
		public static function log_condition_evaluated( $condition_type, $field_name, $operator, $expected, $actual, $passed ) {
			self::$debug_data['conditions'][] = array(
				'type'      => $condition_type,
				'field'     => $field_name,
				'operator'  => $operator,
				'expected'  => $expected,
				'actual'    => $actual,
				'passed'    => $passed,
				'timestamp' => microtime( true ),
			);

			self::debug(
				sprintf(
					'Condition: %s.%s %s "%s" (actual: "%s") - %s',
					$condition_type,
					$field_name,
					$operator,
					$expected,
					$actual,
					$passed ? 'PASSED' : 'FAILED'
				),
				array(
					'type'     => $condition_type,
					'field'    => $field_name,
					'operator' => $operator,
					'expected' => $expected,
					'actual'   => $actual,
					'passed'   => $passed,
				),
				$passed ? 'success' : 'warning'
			);
		}

		/**
		 * Log conditions evaluation (batch)
		 *
		 * @param array $conditions Conditions evaluated
		 * @param bool  $result     Evaluation result
		 * @since 6.5.0
		 */
		public static function log_conditions_evaluated( $conditions, $result ) {
			$condition_count = is_array( $conditions ) ? count( $conditions ) : 0;

			self::debug(
				sprintf( 'Conditions evaluated: %d condition(s) - %s', $condition_count, $result ? 'PASSED' : 'FAILED' ),
				array(
					'condition_count' => $condition_count,
					'result'          => $result,
				),
				$result ? 'success' : 'warning'
			);
		}

		/**
		 * Get all debug data collected during request
		 *
		 * @return array
		 * @since 6.5.0
		 */
		public static function get_debug_data() {
			return self::$debug_data;
		}

		/**
		 * Clear debug data
		 *
		 * @since 6.5.0
		 */
		public static function clear() {
			self::$debug_data = array();
		}

		/**
		 * Reset debug data (alias for clear)
		 *
		 * @since 6.5.0
		 */
		public static function reset() {
			self::$debug_data = array(
				'events'      => array(),
				'automations' => array(),
				'actions'     => array(),
				'conditions'  => array(),
				'logs'        => array(),
			);
		}

		/**
		 * Output debug panel in admin/frontend footer
		 *
		 * @since 6.5.0
		 */
		public static function output_debug_panel() {
			if ( ! self::is_debug_mode() ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$data = self::$debug_data;
			$total_time = self::$request_start_time ? microtime( true ) - self::$request_start_time : 0;
			$memory_peak = memory_get_peak_usage( true );
			$entry_count = count( $data );

			// Count by level
			$counts = array(
				'error'   => 0,
				'warning' => 0,
				'success' => 0,
				'info'    => 0,
			);
			foreach ( $data as $entry ) {
				if ( isset( $counts[ $entry['level'] ] ) ) {
					$counts[ $entry['level'] ]++;
				}
			}

			?>
			<div id="super-automations-debug-panel" class="super-debug-panel">
				<div class="super-debug-header">
					<span class="super-debug-title">
						<strong>Super Forms Automations Debug</strong>
						<span class="super-debug-badge"><?php echo esc_html( $entry_count ); ?></span>
					</span>
					<span class="super-debug-stats">
						<?php if ( $counts['error'] > 0 ) : ?>
							<span class="stat-error"><?php echo esc_html( $counts['error'] ); ?> errors</span>
						<?php endif; ?>
						<?php if ( $counts['warning'] > 0 ) : ?>
							<span class="stat-warning"><?php echo esc_html( $counts['warning'] ); ?> warnings</span>
						<?php endif; ?>
						<span class="stat-time"><?php echo esc_html( number_format( $total_time * 1000, 2 ) ); ?>ms</span>
						<span class="stat-memory"><?php echo esc_html( size_format( $memory_peak ) ); ?></span>
					</span>
					<button type="button" class="super-debug-toggle" aria-label="Toggle debug panel">
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</button>
				</div>
				<div class="super-debug-content">
					<?php if ( empty( $data ) ) : ?>
						<div class="super-debug-empty">
							<p>No automation activity recorded during this request.</p>
							<p><small>Automation events will appear here when forms are submitted or automations are executed.</small></p>
						</div>
					<?php else : ?>
						<div class="super-debug-entries">
							<?php foreach ( $data as $index => $entry ) : ?>
								<div class="super-debug-entry debug-level-<?php echo esc_attr( $entry['level'] ); ?>">
									<div class="entry-header">
										<span class="entry-time">+<?php echo esc_html( number_format( $entry['elapsed'] * 1000, 1 ) ); ?>ms</span>
										<span class="entry-level entry-level-<?php echo esc_attr( $entry['level'] ); ?>"><?php echo esc_html( strtoupper( $entry['level'] ) ); ?></span>
										<span class="entry-message"><?php echo esc_html( $entry['message'] ); ?></span>
									</div>
									<?php if ( ! empty( $entry['data'] ) ) : ?>
										<div class="entry-data">
											<pre><?php echo esc_html( print_r( $entry['data'], true ) ); ?></pre>
										</div>
									<?php endif; ?>
									<div class="entry-location">
										<small>
											<?php
											echo esc_html( sprintf(
												'%s%s%s() in %s:%d',
												$entry['class'] ? $entry['class'] . '::' : '',
												$entry['function'],
												$entry['called_from'] ? ' called from ' . $entry['called_from'] : '',
												basename( $entry['file'] ),
												$entry['line']
											) );
											?>
										</small>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<style>
				#super-automations-debug-panel {
					position: fixed;
					bottom: 0;
					left: 0;
					right: 0;
					background: #1d2327;
					color: #f0f0f1;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					font-size: 12px;
					z-index: 999999;
					box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
					transition: transform 0.3s ease;
				}
				#super-automations-debug-panel.minimized {
					transform: translateY(calc(100% - 36px));
				}
				#super-automations-debug-panel.minimized .super-debug-toggle .dashicons {
					transform: rotate(180deg);
				}
				.super-debug-header {
					display: flex;
					align-items: center;
					justify-content: space-between;
					padding: 8px 15px;
					background: #2c3338;
					border-bottom: 1px solid #3c434a;
					cursor: pointer;
				}
				.super-debug-title {
					display: flex;
					align-items: center;
					gap: 8px;
				}
				.super-debug-badge {
					background: #2271b1;
					color: #fff;
					padding: 2px 8px;
					border-radius: 10px;
					font-size: 11px;
				}
				.super-debug-stats {
					display: flex;
					gap: 15px;
					font-size: 11px;
					color: #a7aaad;
				}
				.super-debug-stats .stat-error { color: #d63638; }
				.super-debug-stats .stat-warning { color: #dba617; }
				.super-debug-toggle {
					background: none;
					border: none;
					color: #f0f0f1;
					cursor: pointer;
					padding: 5px;
				}
				.super-debug-toggle .dashicons {
					transition: transform 0.3s ease;
				}
				.super-debug-content {
					max-height: 300px;
					overflow-y: auto;
					padding: 10px 15px;
				}
				.super-debug-empty {
					text-align: center;
					padding: 20px;
					color: #a7aaad;
				}
				.super-debug-entry {
					margin-bottom: 8px;
					padding: 8px 10px;
					background: #2c3338;
					border-radius: 4px;
					border-left: 3px solid #50575e;
				}
				.super-debug-entry.debug-level-error { border-left-color: #d63638; background: #3c2325; }
				.super-debug-entry.debug-level-warning { border-left-color: #dba617; background: #3c3525; }
				.super-debug-entry.debug-level-success { border-left-color: #00a32a; background: #253c2c; }
				.super-debug-entry.debug-level-info { border-left-color: #72aee6; }
				.entry-header {
					display: flex;
					gap: 10px;
					align-items: center;
				}
				.entry-time {
					color: #a7aaad;
					font-family: monospace;
					min-width: 70px;
				}
				.entry-level {
					font-size: 10px;
					font-weight: 600;
					padding: 2px 6px;
					border-radius: 3px;
					text-transform: uppercase;
				}
				.entry-level-error { background: #d63638; color: #fff; }
				.entry-level-warning { background: #dba617; color: #1d2327; }
				.entry-level-success { background: #00a32a; color: #fff; }
				.entry-level-info { background: #72aee6; color: #1d2327; }
				.entry-message {
					flex: 1;
				}
				.entry-data {
					margin-top: 6px;
					padding: 6px;
					background: #1d2327;
					border-radius: 3px;
					overflow-x: auto;
				}
				.entry-data pre {
					margin: 0;
					font-size: 11px;
					color: #a7aaad;
					white-space: pre-wrap;
					word-wrap: break-word;
				}
				.entry-location {
					margin-top: 4px;
					color: #72777c;
				}
				/* Admin bar offset */
				.admin-bar #super-automations-debug-panel {
					/* No offset needed since it's fixed to bottom */
				}
			</style>
			<script>
				(function() {
					var panel = document.getElementById('super-automations-debug-panel');
					var header = panel.querySelector('.super-debug-header');

					// Check cookie for initial state
					if (document.cookie.indexOf('super_debug_panel_minimized=1') !== -1) {
						panel.classList.add('minimized');
					}

					header.addEventListener('click', function() {
						panel.classList.toggle('minimized');
						// Save state in cookie
						document.cookie = 'super_debug_panel_minimized=' + (panel.classList.contains('minimized') ? '1' : '0') + '; path=/';
					});
				})();
			</script>
			<?php
		}

		/**
		 * Get summary of debug session
		 *
		 * @return array Summary data
		 * @since 6.5.0
		 */
		public static function get_summary() {
			$data = self::$debug_data;

			$summary = array(
				'total_entries'       => count( $data ),
				'errors'              => 0,
				'warnings'            => 0,
				'total_time'          => self::$request_start_time ? microtime( true ) - self::$request_start_time : 0,
				'peak_memory'         => memory_get_peak_usage( true ),
				'events_fired'        => 0,
				'automations_matched' => 0,
				'actions_executed'    => 0,
			);

			foreach ( $data as $entry ) {
				if ( $entry['level'] === 'error' ) {
					$summary['errors']++;
				} elseif ( $entry['level'] === 'warning' ) {
					$summary['warnings']++;
				}

				// Count specific events
				if ( strpos( $entry['message'], 'Event fired:' ) === 0 ) {
					$summary['events_fired']++;
				} elseif ( strpos( $entry['message'], 'Automation evaluated:' ) === 0 && strpos( $entry['message'], 'MATCHED' ) !== false ) {
					$summary['automations_matched']++;
				} elseif ( strpos( $entry['message'], 'Action executed:' ) === 0 ) {
					$summary['actions_executed']++;
				}
			}

			return $summary;
		}
	}

	// Initialize debugger
	add_action( 'init', array( 'SUPER_Automation_Debugger', 'init' ), 1 );

endif;
