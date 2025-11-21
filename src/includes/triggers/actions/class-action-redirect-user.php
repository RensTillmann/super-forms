<?php
/**
 * Redirect User Action
 *
 * Redirects the user to a specified URL after form submission
 * Can use tags for dynamic redirects based on form data
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Redirect_User extends SUPER_Trigger_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'redirect_user';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __('Redirect User', 'super-forms');
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Redirect the user to a different page or URL. Supports conditional redirects based on form data.', 'super-forms');
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'flow_control';
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name' => 'redirect_url',
				'label' => __('Redirect URL', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => 'https://example.com/thank-you',
				'description' => __('URL to redirect to. Supports {tags} for dynamic URLs.', 'super-forms')
			],
			[
				'name' => 'redirect_type',
				'label' => __('Redirect Type', 'super-forms'),
				'type' => 'select',
				'required' => false,
				'default' => 'client',
				'options' => [
					'client' => 'Client-side (JavaScript)',
					'server' => 'Server-side (HTTP 302)',
					'meta' => 'Meta Refresh'
				],
				'description' => __('How to perform the redirect', 'super-forms')
			],
			[
				'name' => 'delay_seconds',
				'label' => __('Delay (seconds)', 'super-forms'),
				'type' => 'number',
				'required' => false,
				'default' => 0,
				'min' => 0,
				'max' => 60,
				'description' => __('Delay before redirecting (0 = immediate)', 'super-forms')
			],
			[
				'name' => 'pass_entry_id',
				'label' => __('Pass Entry ID', 'super-forms'),
				'type' => 'checkbox',
				'required' => false,
				'default' => false,
				'description' => __('Add ?entry_id={id} to the redirect URL', 'super-forms')
			],
			[
				'name' => 'open_new_tab',
				'label' => __('Open in New Tab', 'super-forms'),
				'type' => 'checkbox',
				'required' => false,
				'default' => false,
				'description' => __('Open redirect URL in a new browser tab (client-side only)', 'super-forms')
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
		// Get redirect URL
		$redirect_url = $config['redirect_url'] ?? '';
		if (empty($redirect_url)) {
			return [
				'success' => false,
				'error' => 'Redirect URL is required'
			];
		}

		// Replace tags in URL
		$redirect_url = $this->replace_tags($redirect_url, $context);

		// Validate URL
		if (!filter_var($redirect_url, FILTER_VALIDATE_URL)) {
			return [
				'success' => false,
				'error' => 'Invalid redirect URL: ' . $redirect_url
			];
		}

		// Add entry ID if requested
		if (!empty($config['pass_entry_id']) && !empty($context['entry_id'])) {
			$redirect_url = add_query_arg('entry_id', $context['entry_id'], $redirect_url);
		}

		// Get redirect type
		$redirect_type = $config['redirect_type'] ?? 'client';
		$delay_seconds = absint($config['delay_seconds'] ?? 0);
		$open_new_tab = !empty($config['open_new_tab']);

		// Perform redirect based on type
		switch ($redirect_type) {
			case 'server':
				// Server-side redirect (only works if headers not sent)
				if (!headers_sent()) {
					wp_redirect($redirect_url, 302);
					exit;
				} else {
					// Fallback to client-side if headers already sent
					$this->client_side_redirect($redirect_url, $delay_seconds, $open_new_tab);
				}
				break;

			case 'meta':
				// Meta refresh redirect
				$this->meta_refresh_redirect($redirect_url, $delay_seconds);
				break;

			case 'client':
			default:
				// Client-side JavaScript redirect
				$this->client_side_redirect($redirect_url, $delay_seconds, $open_new_tab);
				break;
		}

		return [
			'success' => true,
			'redirect_url' => $redirect_url,
			'redirect_type' => $redirect_type,
			'delay_seconds' => $delay_seconds,
			'open_new_tab' => $open_new_tab,
			'redirected_at' => current_time('mysql')
		];
	}

	/**
	 * Perform client-side redirect
	 *
	 * @param string $url Redirect URL
	 * @param int $delay Delay in seconds
	 * @param bool $new_tab Open in new tab
	 */
	private function client_side_redirect($url, $delay = 0, $new_tab = false) {
		// Add redirect data to response
		// The frontend will handle the actual redirect via JavaScript
		add_filter('super_form_submit_response', function($response) use ($url, $delay, $new_tab) {
			$response['redirect'] = [
				'url' => $url,
				'delay' => $delay * 1000, // Convert to milliseconds
				'new_tab' => $new_tab
			];
			return $response;
		});
	}

	/**
	 * Perform meta refresh redirect
	 *
	 * @param string $url Redirect URL
	 * @param int $delay Delay in seconds
	 */
	private function meta_refresh_redirect($url, $delay = 0) {
		// Output meta refresh tag
		add_action('wp_head', function() use ($url, $delay) {
			echo '<meta http-equiv="refresh" content="' . esc_attr($delay) . ';url=' . esc_url($url) . '">';
		}, 1);
	}

	/**
	 * Check if action can run
	 *
	 * @param array $context
	 * @return bool
	 */
	public function can_run($context) {
		return true;
	}

	/**
	 * Get required capabilities
	 *
	 * @return array
	 */
	public function get_required_capabilities() {
		return ['edit_posts'];
	}
}
