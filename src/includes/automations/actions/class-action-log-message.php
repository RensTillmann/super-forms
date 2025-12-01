<?php
/**
 * Log Message Action
 *
 * Logs a message to WordPress debug.log and database for debugging
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Log_Message extends SUPER_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'log_message';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __('Log Message', 'super-forms');
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Log a message to WordPress debug.log and database for debugging and monitoring', 'super-forms');
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'utility';
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name' => 'message',
				'label' => __('Message', 'super-forms'),
				'type' => 'textarea',
				'required' => true,
				'default' => 'Trigger fired: {event_id} for form #{form_id}',
				'description' => __('Message to log. Supports {tag} replacement from event context.', 'super-forms')
			],
			[
				'name' => 'log_level',
				'label' => __('Log Level', 'super-forms'),
				'type' => 'select',
				'required' => false,
				'default' => 'info',
				'options' => [
					'debug' => 'Debug',
					'info' => 'Info',
					'warning' => 'Warning',
					'error' => 'Error'
				],
				'description' => __('Severity level for this log message', 'super-forms')
			],
			[
				'name' => 'include_context',
				'label' => __('Include Context Data', 'super-forms'),
				'type' => 'checkbox',
				'required' => false,
				'default' => false,
				'description' => __('Include full event context data in log (useful for debugging)', 'super-forms')
			]
		];
	}

	/**
	 * Execute the action
	 *
	 * @param array $context Event context data
	 * @param array $config Action configuration
	 * @return array Result data
	 */
	public function execute($context, $config) {
		// Get message from config
		$message = $config['message'] ?? 'Trigger action executed';

		// Replace tags with context values
		$message = $this->replace_tags($message, $context);

		// Get log level
		$log_level = $config['log_level'] ?? 'info';

		// Build log entry
		$log_entry = sprintf(
			'[Super Forms Trigger] [%s] %s',
			strtoupper($log_level),
			$message
		);

		// Add context if requested
		if (!empty($config['include_context'])) {
			// Sanitize context for logging (remove sensitive data)
			$safe_context = $this->sanitize_context_for_logging($context);
			$log_entry .= ' | Context: ' . wp_json_encode($safe_context);
		}

		// Log to WordPress debug.log
		$logged_to_file = false;
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log($log_entry);
			$logged_to_file = true;
		}

		// Return success with details
		return [
			'success' => true,
			'message' => $message,
			'log_level' => $log_level,
			'logged_to_file' => $logged_to_file,
			'logged_to_database' => true, // Executor logs to DB automatically
			'logged_at' => current_time('mysql')
		];
	}

	/**
	 * Sanitize context data for logging
	 *
	 * Removes sensitive information before logging
	 *
	 * @param array $context
	 * @return array
	 */
	private function sanitize_context_for_logging($context) {
		$safe_context = $context;

		// Remove potentially sensitive keys
		$sensitive_keys = [
			'password',
			'pass',
			'pwd',
			'secret',
			'token',
			'api_key',
			'credit_card',
			'ssn',
			'social_security'
		];

		foreach ($sensitive_keys as $key) {
			if (isset($safe_context[$key])) {
				$safe_context[$key] = '[REDACTED]';
			}

			// Also check in form_data
			if (isset($safe_context['form_data'][$key])) {
				$safe_context['form_data'][$key] = '[REDACTED]';
			}
		}

		return $safe_context;
	}

	/**
	 * Check if action can run
	 *
	 * Log Message can always run
	 *
	 * @param array $context
	 * @return bool
	 */
	public function can_run($context) {
		return true; // Always allow logging
	}

	/**
	 * Get required capabilities
	 *
	 * @return array
	 */
	public function get_required_capabilities() {
		// Any user with form access can create triggers with log actions
		return ['edit_posts'];
	}
}
