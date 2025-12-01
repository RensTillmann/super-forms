<?php
/**
 * Clear Cache Action
 *
 * Clears various WordPress caches
 * Useful after data updates to ensure fresh content
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class SUPER_Action_Clear_Cache extends SUPER_Action_Base {

	public function get_id() {
		return 'clear_cache';
	}

	public function get_label() {
		return __('Clear Cache', 'super-forms');
	}

	public function get_description() {
		return __('Clear WordPress caches. Useful after updating content to ensure fresh data.', 'super-forms');
	}

	public function get_category() {
		return 'performance';
	}

	public function get_settings_schema() {
		return [
			[
				'name' => 'cache_types',
				'label' => __('Cache Types to Clear', 'super-forms'),
				'type' => 'multiselect',
				'required' => true,
				'options' => [
					'object' => 'Object Cache',
					'transients' => 'Transients',
					'rewrite' => 'Rewrite Rules',
					'super_forms' => 'Super Forms Cache',
					'wp_rocket' => 'WP Rocket (if installed)',
					'w3tc' => 'W3 Total Cache (if installed)',
					'wp_super_cache' => 'WP Super Cache (if installed)'
				]
			]
		];
	}

	public function execute($context, $config) {
		$cache_types = $config['cache_types'] ?? ['object'];
		$cleared = [];

		foreach ($cache_types as $cache_type) {
			switch ($cache_type) {
				case 'object':
					wp_cache_flush();
					$cleared[] = 'Object Cache';
					break;

				case 'transients':
					global $wpdb;
					$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%'");
					$cleared[] = 'Transients';
					break;

				case 'rewrite':
					flush_rewrite_rules();
					$cleared[] = 'Rewrite Rules';
					break;

				case 'super_forms':
					delete_option('super_forms_cache');
					$cleared[] = 'Super Forms Cache';
					break;

				case 'wp_rocket':
					if (function_exists('rocket_clean_domain')) {
						rocket_clean_domain();
						$cleared[] = 'WP Rocket';
					}
					break;

				case 'w3tc':
					if (function_exists('w3tc_flush_all')) {
						w3tc_flush_all();
						$cleared[] = 'W3 Total Cache';
					}
					break;

				case 'wp_super_cache':
					if (function_exists('wp_cache_clear_cache')) {
						wp_cache_clear_cache();
						$cleared[] = 'WP Super Cache';
					}
					break;
			}
		}

		return [
			'success' => true,
			'cache_types_cleared' => $cleared,
			'cleared_count' => count($cleared),
			'cleared_at' => current_time('mysql')
		];
	}

	public function can_run($context) {
		return true;
	}

	public function get_required_capabilities() {
		return ['manage_options'];
	}
}
