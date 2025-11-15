<?php
/**
 * Developer Tools Helper Class
 *
 * @author      WebRehab
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Developer_Tools
 * @version     6.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('SUPER_Developer_Tools')) :

	/**
	 * SUPER_Developer_Tools Class
	 */
	class SUPER_Developer_Tools {

		/**
		 * Generate test contact entries
		 *
		 * @param array $args Configuration for entry generation
		 * @return array Results with generated/failed counts
		 */
		public static function generate_test_entries($args) {
			$defaults = array(
				'count' => 10,
				'form_id' => 0,
				'date_mode' => 'today',
				'complexity' => array('basic_text'),
				'batch_offset' => 0
			);
			$args = wp_parse_args($args, $defaults);

			$generated = 0;
			$failed = 0;
			$errors = array();

			for ($i = 0; $i < $args['count']; $i++) {
				try {
					// 1. Generate entry data based on complexity options
					$entry_data = self::generate_entry_data($args['complexity'], $args['batch_offset'] + $i);

					// 2. Generate post date based on date mode
					$post_date = self::generate_post_date($args['date_mode']);

					// 3. Create WordPress post
					$entry_id = wp_insert_post(array(
						'post_type' => 'super_contact_entry',
						'post_status' => 'super_unread',
						'post_parent' => $args['form_id'],
						'post_title' => 'Test Entry ' . ($args['batch_offset'] + $i + 1),
						'post_date' => $post_date,
						'post_date_gmt' => get_gmt_from_date($post_date)
					));

					if (is_wp_error($entry_id)) {
						throw new Exception($entry_id->get_error_message());
					}

					// 4. Save data via Data Access Layer (force serialized format for testing migration)
					$result = SUPER_Data_Access::save_entry_data($entry_id, $entry_data, 'serialized');

					if (is_wp_error($result)) {
						throw new Exception($result->get_error_message());
					}

					// 5. Tag as test entry (CRITICAL for safe deletion)
					add_post_meta($entry_id, '_super_test_entry', true);

					$generated++;

				} catch (Exception $e) {
					$failed++;
					$errors[] = $e->getMessage();
				}
			}

			return array(
				'generated' => $generated,
				'failed' => $failed,
				'errors' => $errors,
				'total_offset' => $args['batch_offset'] + $generated
			);
		}

		/**
		 * Generate entry data based on complexity patterns
		 *
		 * @param array $complexity Array of complexity options
		 * @param int $index Entry index for unique values
		 * @return array Entry data in Super Forms format
		 */
		private static function generate_entry_data($complexity, $index) {
			$data = array();

			// 1. Basic text fields
			if (in_array('basic_text', $complexity)) {
				$data['first_name'] = array(
					'name' => 'first_name',
					'value' => 'John' . $index,
					'type' => 'text',
					'label' => 'First Name'
				);
				$data['last_name'] = array(
					'name' => 'last_name',
					'value' => 'Doe' . $index,
					'type' => 'text',
					'label' => 'Last Name'
				);
				$data['email'] = array(
					'name' => 'email',
					'value' => 'test' . $index . '@test.com',
					'type' => 'field',
					'label' => 'Email'
				);
				$data['phone'] = array(
					'name' => 'phone',
					'value' => '+1-555-' . str_pad($index % 10000, 4, '0', STR_PAD_LEFT),
					'type' => 'text',
					'label' => 'Phone'
				);
			}

			// 2. Special characters (UTF-8)
			if (in_array('special_chars', $complexity)) {
				$data['name_utf8'] = array(
					'name' => 'name_utf8',
					'value' => 'José Müller 中文名字 مرحبا 🚀 ®™ ' . $index,
					'type' => 'text',
					'label' => 'International Name'
				);
			}

			// 3. Long text (>10KB)
			if (in_array('long_text', $complexity)) {
				$data['description'] = array(
					'name' => 'description',
					'value' => str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 200),
					'type' => 'text',
					'label' => 'Description'
				);
			}

			// 4. Numeric values (stored as strings)
			if (in_array('numeric', $complexity)) {
				$data['age'] = array(
					'name' => 'age',
					'value' => strval(20 + ($index % 50)),
					'type' => 'field',
					'label' => 'Age'
				);
				$data['price'] = array(
					'name' => 'price',
					'value' => number_format(99.99 + ($index % 100), 2, '.', ''),
					'type' => 'field',
					'label' => 'Price'
				);
				$data['temperature'] = array(
					'name' => 'temperature',
					'value' => strval(-50 + ($index % 100)),
					'type' => 'field',
					'label' => 'Temperature'
				);
			}

			// 5. Empty/null values
			if (in_array('empty', $complexity)) {
				$data['optional_field'] = array(
					'name' => 'optional_field',
					'value' => '',
					'type' => 'text',
					'label' => 'Optional Field'
				);
				$data['zero_value'] = array(
					'name' => 'zero_value',
					'value' => '0',
					'type' => 'field',
					'label' => 'Zero Value'
				);
			}

			// 6. Checkbox arrays (multi-select - JSON in EAV)
			if (in_array('arrays', $complexity)) {
				$interests = array('sports', 'music', 'reading', 'travel');
				$selected = array();
				for ($i = 0; $i < ($index % 4) + 1; $i++) {
					$selected[] = $interests[$i];
				}
				$data['interests'] = array(
					'name' => 'interests',
					'value' => implode(', ', $selected),
					'type' => 'field',
					'label' => 'Interests'
				);
			}

			// 7. File upload URLs
			if (in_array('files', $complexity)) {
				$data['resume'] = array(
					'name' => 'resume',
					'files' => array(array(
						'value' => 'resume' . $index . '.pdf',
						'url' => 'https://example.com/uploads/resume' . $index . '.pdf',
						'attachment' => 100 + $index
					)),
					'type' => 'files',
					'label' => 'Resume'
				);
			}

			return $data;
		}

		/**
		 * Generate post date based on mode
		 *
		 * @param string $mode Date mode (today, random_30_days, random_year)
		 * @return string MySQL datetime format
		 */
		private static function generate_post_date($mode) {
			switch ($mode) {
				case 'today':
					return current_time('mysql');

				case 'random_30_days':
					$days = rand(0, 30);
					$hours = rand(0, 23);
					$minutes = rand(0, 59);
					return date('Y-m-d H:i:s', strtotime("-{$days} days -{$hours} hours -{$minutes} minutes"));

				case 'random_year':
					$days = rand(0, 365);
					$hours = rand(0, 23);
					$minutes = rand(0, 59);
					return date('Y-m-d H:i:s', strtotime("-{$days} days -{$hours} hours -{$minutes} minutes"));

				default:
					return current_time('mysql');
			}
		}

		/**
		 * Delete test entries (safe - only removes tagged entries)
		 *
		 * @return array Results with deleted count
		 */
		public static function delete_test_entries() {
			global $wpdb;

			// Find ONLY test entries
			$test_ids = $wpdb->get_col("
				SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_super_test_entry'
				AND meta_value = '1'
			");

			if (empty($test_ids)) {
				return array('deleted' => 0, 'message' => 'No test entries found');
			}

			$deleted = 0;
			foreach ($test_ids as $entry_id) {
				// Delete from EAV
				$wpdb->delete(
					$wpdb->prefix . 'superforms_entry_data',
					array('entry_id' => $entry_id),
					array('%d')
				);

				// Delete from serialized
				delete_post_meta($entry_id, '_super_contact_entry_data');
				delete_post_meta($entry_id, '_super_test_entry');

				// Delete WordPress post
				wp_delete_post($entry_id, true);

				$deleted++;
			}

		// Log the action
		error_log(sprintf(
			'[Super Forms Developer Tools] delete_test_entries: Deleted %d test entries by user %s',
			$deleted,
			wp_get_current_user()->user_login
		));

			return array('deleted' => $deleted, 'message' => sprintf('Deleted %d test entries', $deleted));
		}

	/**
	 * Delete ALL contact entries (test and real)
	 * WARNING: This is a destructive operation!
	 *
	 * @return array Result with deleted count and message
	 */
	public static function delete_all_entries() {
		global $wpdb;

		// Find ALL contact entries
		$all_ids = $wpdb->get_col("
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'super_contact_entry'
		");

		if (empty($all_ids)) {
			return array('deleted' => 0, 'message' => 'No contact entries found');
		}

		$deleted = 0;
		foreach ($all_ids as $entry_id) {
			// Delete from EAV
			$wpdb->delete(
				$wpdb->prefix . 'superforms_entry_data',
				array('entry_id' => $entry_id),
				array('%d')
			);

			// Delete all post meta (includes serialized data and test flag)
			$wpdb->delete(
				$wpdb->postmeta,
				array('post_id' => $entry_id),
				array('%d')
			);

			// Delete WordPress post
			wp_delete_post($entry_id, true);

			$deleted++;
		}

		// Log the action
		error_log(sprintf(
			'[Super Forms Developer Tools] delete_all_entries: Deleted %d contact entries by user %s',
			$deleted,
			wp_get_current_user()->user_login
		));

		return array('deleted' => $deleted, 'message' => sprintf('Deleted ALL %d contact entries', $deleted));
	}

		/**
		 * Get count of test entries
		 *
		 * @return int Count of test entries
		 */
		public static function get_test_entry_count() {
			global $wpdb;

			return (int) $wpdb->get_var("
				SELECT COUNT(*)
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_super_test_entry'
				AND meta_value = '1'
			");
		}

	// ======================================
	// Verification Test Methods (Phase 4)
	// ======================================

	/**
	 * Test 1: Data Integrity (EAV ↔ Serialized)
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_data_integrity($entry_ids = null) {
		global $wpdb;

		// Get all entry IDs if not provided
		if (empty($entry_ids)) {
			$entry_ids = $wpdb->get_col("
				SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'super_contact_entry'
				AND post_status IN ('publish', 'super_read', 'super_unread')
				LIMIT 1000
			");
		}

		$start_time = microtime(true);
		$result = SUPER_Data_Access::bulk_validate_integrity($entry_ids);
		$end_time = microtime(true);

		return array(
			'test' => 'data_integrity',
			'passed' => ($result['invalid_count'] === 0),
			'total_checked' => $result['total_checked'],
			'valid' => $result['valid_count'],
			'invalid' => $result['invalid_count'],
			'errors' => $result['errors'],
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => $result['invalid_count'] === 0
				? 'All entries match between EAV and serialized'
				: $result['invalid_count'] . ' entries have mismatches'
		);
	}

	/**
	 * Test 2: Field Count Match
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_field_count_match($entry_ids = null) {
		global $wpdb;

		if (empty($entry_ids)) {
			$entry_ids = $wpdb->get_col("
				SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'super_contact_entry'
				AND post_status IN ('publish', 'super_read', 'super_unread')
				LIMIT 1000
			");
		}

		$start_time = microtime(true);
		$mismatches = array();

		foreach ($entry_ids as $entry_id) {
			// Get from serialized
			$serialized_data = get_post_meta($entry_id, '_super_contact_entry_data', true);
			$serialized_data = maybe_unserialize($serialized_data);
			$serialized_count = is_array($serialized_data) ? count($serialized_data) : 0;

			// Get from EAV
			$eav_data = SUPER_Data_Access::get_entry_data($entry_id);
			$eav_count = is_array($eav_data) && !is_wp_error($eav_data) ? count($eav_data) : 0;

			if ($serialized_count !== $eav_count) {
				$mismatches[$entry_id] = array(
					'serialized_count' => $serialized_count,
					'eav_count' => $eav_count
				);
			}
		}

		$end_time = microtime(true);

		return array(
			'test' => 'field_count_match',
			'passed' => empty($mismatches),
			'total_checked' => count($entry_ids),
			'mismatches' => count($mismatches),
			'errors' => $mismatches,
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => empty($mismatches)
				? 'All entries have matching field counts'
				: count($mismatches) . ' entries have field count mismatches'
		);
	}

	/**
	 * Test 3: Field Values Match
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_field_values_match($entry_ids = null) {
		global $wpdb;

		if (empty($entry_ids)) {
			$entry_ids = $wpdb->get_col("
				SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'super_contact_entry'
				AND post_status IN ('publish', 'super_read', 'super_unread')
				LIMIT 1000
			");
		}

		$start_time = microtime(true);
		$mismatches = array();

		foreach ($entry_ids as $entry_id) {
			// Get from both sources
			$serialized_data = get_post_meta($entry_id, '_super_contact_entry_data', true);
			$serialized_data = maybe_unserialize($serialized_data);
			$eav_data = SUPER_Data_Access::get_entry_data($entry_id);

			if (!is_array($serialized_data) || !is_array($eav_data) || is_wp_error($eav_data)) {
				continue;
			}

			// Compare each field
			foreach ($serialized_data as $field_name => $field_data) {
				if (!isset($eav_data[$field_name])) {
					$mismatches[$entry_id][$field_name] = 'Missing in EAV';
					continue;
				}

				$serial_value = isset($field_data['value']) ? $field_data['value'] : '';
				$eav_value = isset($eav_data[$field_name]['value']) ? $eav_data[$field_name]['value'] : '';

				// Normalize arrays for comparison
				if (is_array($serial_value)) {
					$serial_value = json_encode($serial_value);
				}
				if (is_array($eav_value)) {
					$eav_value = json_encode($eav_value);
				}

				if ($serial_value != $eav_value) {
					$mismatches[$entry_id][$field_name] = array(
						'serialized' => $serial_value,
						'eav' => $eav_value
					);
				}
			}
		}

		$end_time = microtime(true);

		return array(
			'test' => 'field_values_match',
			'passed' => empty($mismatches),
			'total_checked' => count($entry_ids),
			'mismatches' => count($mismatches),
			'errors' => $mismatches,
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => empty($mismatches)
				? 'All field values match'
				: count($mismatches) . ' entries have field value mismatches'
		);
	}

	/**
	 * Test 4: CSV Export Byte-Comparison
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_csv_export_comparison($entry_ids = null) {
		global $wpdb;

		if (empty($entry_ids)) {
			$entry_ids = $wpdb->get_col("
				SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'super_contact_entry'
				AND post_status IN ('publish', 'super_read', 'super_unread')
				LIMIT 100
			");
		}

		$start_time = microtime(true);

		// Get current migration status
		$migration = get_option('superforms_eav_migration', array());
		$original_storage = isset($migration['using_storage']) ? $migration['using_storage'] : 'serialized';

		// Export with serialized storage
		$migration['using_storage'] = 'serialized';
		update_option('superforms_eav_migration', $migration);
		$csv_serialized = self::generate_csv_export($entry_ids);

		// Export with EAV storage
		$migration['using_storage'] = 'eav';
		update_option('superforms_eav_migration', $migration);
		$csv_eav = self::generate_csv_export($entry_ids);

		// Restore original storage
		$migration['using_storage'] = $original_storage;
		update_option('superforms_eav_migration', $migration);

		$end_time = microtime(true);

		// Compare MD5 hashes
		$hash_serialized = md5($csv_serialized);
		$hash_eav = md5($csv_eav);
		$match = ($hash_serialized === $hash_eav);

		return array(
			'test' => 'csv_export_comparison',
			'passed' => $match,
			'total_checked' => count($entry_ids),
			'hash_serialized' => $hash_serialized,
			'hash_eav' => $hash_eav,
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => $match
				? 'CSV exports are identical'
				: 'CSV exports differ (hashes do not match)'
		);
	}

	/**
	 * Generate CSV export for given entry IDs
	 *
	 * @param array $entry_ids Entry IDs to export
	 * @return string CSV data
	 */
	private static function generate_csv_export($entry_ids) {
		$csv_data = array();

		// Get all field names
		$field_names = array();
		foreach ($entry_ids as $entry_id) {
			$data = SUPER_Data_Access::get_entry_data($entry_id);
			if (is_array($data)) {
				$field_names = array_merge($field_names, array_keys($data));
			}
		}
		$field_names = array_unique($field_names);
		sort($field_names);

		// Header row
		$csv_data[] = implode(',', $field_names);

		// Data rows
		foreach ($entry_ids as $entry_id) {
			$data = SUPER_Data_Access::get_entry_data($entry_id);
			$row = array();
			foreach ($field_names as $field) {
				$value = isset($data[$field]['value']) ? $data[$field]['value'] : '';
				if (is_array($value)) {
					$value = json_encode($value);
				}
				$row[] = '"' . str_replace('"', '""', $value) . '"';
			}
			$csv_data[] = implode(',', $row);
		}

		return implode("\n", $csv_data);
	}

	/**
	 * Test 5: CSV Import Roundtrip
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_csv_import_roundtrip($entry_ids = null) {
		// Note: This test is a placeholder for now
		// Full implementation would require CSV import functionality
		return array(
			'test' => 'csv_import_roundtrip',
			'passed' => true,
			'total_checked' => 0,
			'time_ms' => 0,
			'message' => 'Test not yet implemented (requires CSV import functionality)'
		);
	}

	/**
	 * Test 6: Listings Query Accuracy
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_listings_query_accuracy($entry_ids = null) {
		global $wpdb;

		$start_time = microtime(true);

		// Test query: Find entries where email contains "@test.com"
		$field_name = 'email';
		$field_value = '@test.com';

		// Get current migration status
		$migration = get_option('superforms_eav_migration', array());
		$original_storage = isset($migration['using_storage']) ? $migration['using_storage'] : 'serialized';

		// Serialized query
		$migration['using_storage'] = 'serialized';
		update_option('superforms_eav_migration', $migration);
		$serialized_ids = $wpdb->get_col("
			SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
			WHERE p.post_type = 'super_contact_entry'
			AND m.meta_key = '_super_contact_entry_data'
			AND m.meta_value LIKE '%{$field_value}%'
		");

		// EAV query
		$migration['using_storage'] = 'eav';
		update_option('superforms_eav_migration', $migration);
		$table = $wpdb->prefix . 'superforms_entry_data';
		$eav_ids = $wpdb->get_col($wpdb->prepare("
			SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$table} eav ON eav.entry_id = p.ID
			WHERE p.post_type = 'super_contact_entry'
			AND eav.field_name = %s
			AND eav.field_value LIKE %s
		", $field_name, '%' . $wpdb->esc_like($field_value) . '%'));

		// Restore original storage
		$migration['using_storage'] = $original_storage;
		update_option('superforms_eav_migration', $migration);

		$end_time = microtime(true);

		// Compare results
		sort($serialized_ids);
		sort($eav_ids);
		$match = ($serialized_ids === $eav_ids);

		$only_in_serialized = array_diff($serialized_ids, $eav_ids);
		$only_in_eav = array_diff($eav_ids, $serialized_ids);

		return array(
			'test' => 'listings_query_accuracy',
			'passed' => $match,
			'serialized_count' => count($serialized_ids),
			'eav_count' => count($eav_ids),
			'only_in_serialized' => array_values($only_in_serialized),
			'only_in_eav' => array_values($only_in_eav),
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => $match
				? 'Query results are identical'
				: 'Query results differ: ' . count($only_in_serialized) . ' only in serialized, ' . count($only_in_eav) . ' only in EAV'
		);
	}

	/**
	 * Test 7: Search Query Accuracy
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_search_query_accuracy($entry_ids = null) {
		global $wpdb;

		$start_time = microtime(true);
		$keyword = 'test';

		// Get current migration status
		$migration = get_option('superforms_eav_migration', array());
		$original_storage = isset($migration['using_storage']) ? $migration['using_storage'] : 'serialized';

		// Serialized search
		$migration['using_storage'] = 'serialized';
		update_option('superforms_eav_migration', $migration);
		$serialized_ids = $wpdb->get_col($wpdb->prepare("
			SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
			WHERE p.post_type = 'super_contact_entry'
			AND m.meta_key = '_super_contact_entry_data'
			AND m.meta_value LIKE %s
		", '%' . $wpdb->esc_like($keyword) . '%'));

		// EAV search
		$migration['using_storage'] = 'eav';
		update_option('superforms_eav_migration', $migration);
		$table = $wpdb->prefix . 'superforms_entry_data';
		$eav_ids = $wpdb->get_col($wpdb->prepare("
			SELECT DISTINCT entry_id
			FROM {$table}
			WHERE field_value LIKE %s
		", '%' . $wpdb->esc_like($keyword) . '%'));

		// Restore original storage
		$migration['using_storage'] = $original_storage;
		update_option('superforms_eav_migration', $migration);

		$end_time = microtime(true);

		// Compare results
		sort($serialized_ids);
		sort($eav_ids);
		$match = ($serialized_ids === $eav_ids);

		return array(
			'test' => 'search_query_accuracy',
			'passed' => $match,
			'keyword' => $keyword,
			'serialized_count' => count($serialized_ids),
			'eav_count' => count($eav_ids),
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => $match
				? 'Search results are identical'
				: 'Search results differ: ' . abs(count($serialized_ids) - count($eav_ids)) . ' entries difference'
		);
	}

	/**
	 * Test 8: Bulk Fetch Consistency
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_bulk_fetch_consistency($entry_ids = null) {
		global $wpdb;

		if (empty($entry_ids)) {
			$entry_ids = $wpdb->get_col("
				SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'super_contact_entry'
				AND post_status IN ('publish', 'super_read', 'super_unread')
				LIMIT 100
			");
		}

		$start_time = microtime(true);

		// Bulk fetch
		$bulk_data = SUPER_Data_Access::get_bulk_entry_data($entry_ids);

		// Individual fetches
		$individual_data = array();
		foreach ($entry_ids as $entry_id) {
			$individual_data[$entry_id] = SUPER_Data_Access::get_entry_data($entry_id);
		}

		$end_time = microtime(true);

		// Compare
		$mismatches = array();
		foreach ($entry_ids as $entry_id) {
			if (serialize($bulk_data[$entry_id]) != serialize($individual_data[$entry_id])) {
				$mismatches[] = $entry_id;
			}
		}

		return array(
			'test' => 'bulk_fetch_consistency',
			'passed' => empty($mismatches),
			'total_checked' => count($entry_ids),
			'mismatches' => count($mismatches),
			'errors' => $mismatches,
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => empty($mismatches)
				? 'Bulk fetch matches individual fetches'
				: count($mismatches) . ' entries differ between bulk and individual fetch'
		);
	}

	/**
	 * Test 9: Empty Entry Handling
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_empty_entry_handling($entry_ids = null) {
		global $wpdb;

		$start_time = microtime(true);

		// Find entries with no data or only empty fields
		$empty_ids = $wpdb->get_col("
			SELECT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_super_contact_entry_data'
			WHERE p.post_type = 'super_contact_entry'
			AND (m.meta_value IS NULL OR m.meta_value = '' OR m.meta_value = 'a:0:{}')
			LIMIT 100
		");

		if (empty($empty_ids)) {
			return array(
				'test' => 'empty_entry_handling',
				'passed' => true,
				'total_checked' => 0,
				'time_ms' => round((microtime(true) - $start_time) * 1000, 2),
				'message' => 'No empty entries found to test'
			);
		}

		$mismatches = array();
		foreach ($empty_ids as $entry_id) {
			$serialized = get_post_meta($entry_id, '_super_contact_entry_data', true);
			$eav = SUPER_Data_Access::get_entry_data($entry_id);

			$serialized_empty = empty($serialized) || $serialized === 'a:0:{}';
			$eav_empty = empty($eav) || is_wp_error($eav);

			if ($serialized_empty !== $eav_empty) {
				$mismatches[] = $entry_id;
			}
		}

		$end_time = microtime(true);

		return array(
			'test' => 'empty_entry_handling',
			'passed' => empty($mismatches),
			'total_checked' => count($empty_ids),
			'mismatches' => count($mismatches),
			'errors' => $mismatches,
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => empty($mismatches)
				? 'Empty entries handled correctly'
				: count($mismatches) . ' empty entries differ between storage methods'
		);
	}

	/**
	 * Test 10: Special Characters Preservation
	 *
	 * @param array $entry_ids Optional array of entry IDs to test
	 * @return array Test results
	 */
	public static function test_special_characters_preservation($entry_ids = null) {
		global $wpdb;

		$start_time = microtime(true);

		// Test strings with special characters
		$test_patterns = array(
			'José',           // Accents
			'中文',           // Chinese
			'مرحبا',          // Arabic
			'🚀',            // Emoji
			'®™©',           // Symbols
		);

		$found_entries = array();
		$mismatches = array();

		// Search for entries with these special characters
		foreach ($test_patterns as $pattern) {
			$ids = $wpdb->get_col($wpdb->prepare("
				SELECT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
				WHERE p.post_type = 'super_contact_entry'
				AND m.meta_key = '_super_contact_entry_data'
				AND m.meta_value LIKE %s
				LIMIT 10
			", '%' . $wpdb->esc_like($pattern) . '%'));

			$found_entries = array_merge($found_entries, $ids);
		}

		$found_entries = array_unique($found_entries);

		if (empty($found_entries)) {
			return array(
				'test' => 'special_characters_preservation',
				'passed' => true,
				'total_checked' => 0,
				'time_ms' => round((microtime(true) - $start_time) * 1000, 2),
				'message' => 'No entries with special characters found to test'
			);
		}

		// Verify character preservation
		foreach ($found_entries as $entry_id) {
			$serialized_data = get_post_meta($entry_id, '_super_contact_entry_data', true);
			$serialized_data = maybe_unserialize($serialized_data);
			$eav_data = SUPER_Data_Access::get_entry_data($entry_id);

			if (!is_array($serialized_data) || !is_array($eav_data)) {
				continue;
			}

			foreach ($serialized_data as $field_name => $field_data) {
				$serial_value = isset($field_data['value']) ? $field_data['value'] : '';
				$eav_value = isset($eav_data[$field_name]['value']) ? $eav_data[$field_name]['value'] : '';

				// Check for special characters and verify exact match
				foreach ($test_patterns as $pattern) {
					if (strpos($serial_value, $pattern) !== false) {
						if ($serial_value !== $eav_value) {
							$mismatches[$entry_id][$field_name] = $pattern;
						}
					}
				}
			}
		}

		$end_time = microtime(true);

		return array(
			'test' => 'special_characters_preservation',
			'passed' => empty($mismatches),
			'total_checked' => count($found_entries),
			'mismatches' => count($mismatches),
			'errors' => $mismatches,
			'time_ms' => round(($end_time - $start_time) * 1000, 2),
			'message' => empty($mismatches)
				? 'Special characters preserved correctly'
				: count($mismatches) . ' entries have special character mismatches'
		);
	}
	/**
	 * ========================================
	 * PERFORMANCE BENCHMARKS
	 * ========================================
	 */

	/**
	 * Benchmark Operation Timing Wrapper
	 *
	 * @param callable $callback Function to benchmark
	 * @param int $iterations Number of iterations (default 1)
	 * @return float Time in milliseconds
	 */
	private static function benchmark_operation($callback, $iterations = 1) {
		// Warm up (ignore first run to avoid cache effects)
		if (is_callable($callback)) {
			$callback();
		}

		// Actual timing
		$start = microtime(true);
		for ($i = 0; $i < $iterations; $i++) {
			$callback();
		}
		$end = microtime(true);

		return round(($end - $start) * 1000, 2); // Return milliseconds
	}

	/**
	 * Benchmark 1: CSV Export Performance
	 *
	 * @param int $entry_count Number of entries to export
	 * @return array|WP_Error Benchmark results
	 */
	public static function benchmark_csv_export($entry_count = 100) {
		global $wpdb;

		// Get sample entry IDs
		$entry_ids = $wpdb->get_col($wpdb->prepare("
			SELECT ID FROM {$wpdb->posts}
			WHERE post_type = 'super_contact_entry'
			AND post_status IN ('publish', 'super_read', 'super_unread')
			ORDER BY ID ASC
			LIMIT %d
		", $entry_count));

		if (count($entry_ids) < $entry_count) {
			return new WP_Error('insufficient_entries',
				sprintf('Only %d entries available, need %d', count($entry_ids), $entry_count));
		}

		// Benchmark serialized method (N+1 queries)
		add_filter('superforms_force_serialized_storage', '__return_true');
		$time_serialized = self::benchmark_operation(function() use ($entry_ids) {
			$data = array();
			foreach ($entry_ids as $entry_id) {
				// Simulates old export method
				$entry_data = get_post_meta($entry_id, '_super_contact_entry_data', true);
				$entry_data = maybe_unserialize($entry_data);
				$data[$entry_id] = $entry_data;
			}
			return $data;
		});
		remove_filter('superforms_force_serialized_storage', '__return_true');

		// Benchmark EAV method (single bulk query)
		add_filter('superforms_force_eav_storage', '__return_true');
		$time_eav = self::benchmark_operation(function() use ($entry_ids) {
			return SUPER_Data_Access::get_bulk_entry_data($entry_ids);
		});
		remove_filter('superforms_force_eav_storage', '__return_true');

		// Calculate improvement
		$improvement = $time_serialized > 0 ? round($time_serialized / $time_eav, 1) : 0;
		$faster_text = $improvement >= 1
			? $improvement . 'x faster'
			: round(1 / $improvement, 1) . 'x slower';

		return array(
			'benchmark' => 'csv_export',
			'entry_count' => $entry_count,
			'time_serialized' => $time_serialized,
			'time_eav' => $time_eav,
			'improvement' => $improvement,
			'faster' => $faster_text,
			'message' => sprintf(
				'CSV export: %dms (serialized) vs %dms (EAV) - %s',
				$time_serialized,
				$time_eav,
				$faster_text
			)
		);
	}

	/**
	 * Benchmark 2: Listings Filter Performance
	 *
	 * @param int $entry_count Number of entries to query
	 * @return array|WP_Error Benchmark results
	 */
	public static function benchmark_listings_filter($entry_count = 100) {
		global $wpdb;

		$field_name = 'email';
		$field_value = '@test.com';

		// Ensure we have entries with test email
		$available = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(DISTINCT entry_id)
			FROM {$wpdb->prefix}superforms_entry_data
			WHERE field_name = %s
			AND field_value LIKE %s
			LIMIT %d
		", $field_name, '%' . $wpdb->esc_like($field_value) . '%', $entry_count));

		if ($available < $entry_count) {
			return new WP_Error('insufficient_test_data',
				sprintf('Only %d entries match criteria, need %d', $available, $entry_count));
		}

		// Benchmark serialized method (SUBSTRING_INDEX scan)
		$time_serialized = self::benchmark_operation(function() use ($wpdb, $field_name, $field_value, $entry_count) {
			$field_length = strlen($field_name);
			$results = $wpdb->get_col($wpdb->prepare("
				SELECT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
				WHERE p.post_type = 'super_contact_entry'
				AND m.meta_key = '_super_contact_entry_data'
				AND SUBSTRING_INDEX(
					SUBSTRING_INDEX(
						SUBSTRING_INDEX(m.meta_value, 's:4:\"name\";s:%d:\"%s\";s:5:\"value\";', -1),
						'\";s:', 1
					),
					':\"', -1
				) LIKE %s
				LIMIT %d
			", $field_length, $field_name, '%' . $wpdb->esc_like($field_value) . '%', $entry_count));
			return $results;
		});

		// Benchmark EAV method (indexed JOIN)
		$time_eav = self::benchmark_operation(function() use ($wpdb, $field_name, $field_value, $entry_count) {
			$results = $wpdb->get_col($wpdb->prepare("
				SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}superforms_entry_data eav ON eav.entry_id = p.ID
				WHERE p.post_type = 'super_contact_entry'
				AND eav.field_name = %s
				AND eav.field_value LIKE %s
				LIMIT %d
			", $field_name, '%' . $wpdb->esc_like($field_value) . '%', $entry_count));
			return $results;
		});

		// Calculate improvement
		$improvement = $time_serialized > 0 ? round($time_serialized / $time_eav, 1) : 0;
		$faster_text = $improvement >= 1
			? $improvement . 'x faster'
			: round(1 / $improvement, 1) . 'x slower';

		return array(
			'benchmark' => 'listings_filter',
			'entry_count' => $entry_count,
			'time_serialized' => $time_serialized,
			'time_eav' => $time_eav,
			'improvement' => $improvement,
			'faster' => $faster_text,
			'message' => sprintf(
				'Listings filter: %dms (serialized) vs %dms (EAV) - %s',
				$time_serialized,
				$time_eav,
				$faster_text
			)
		);
	}

	/**
	 * Benchmark 3: Admin Search Performance
	 *
	 * @param int $entry_count Number of entries to search
	 * @param string $keyword Keyword to search for
	 * @return array Benchmark results
	 */
	public static function benchmark_admin_search($entry_count = 100, $keyword = 'test') {
		global $wpdb;

		// Benchmark serialized method (LIKE on serialized meta_value)
		$time_serialized = self::benchmark_operation(function() use ($wpdb, $keyword, $entry_count) {
			$results = $wpdb->get_col($wpdb->prepare("
				SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
				WHERE p.post_type = 'super_contact_entry'
				AND m.meta_key = '_super_contact_entry_data'
				AND m.meta_value LIKE %s
				LIMIT %d
			", '%' . $wpdb->esc_like($keyword) . '%', $entry_count));
			return $results;
		});

		// Benchmark EAV method (indexed field_value LIKE with prefix index)
		$time_eav = self::benchmark_operation(function() use ($wpdb, $keyword, $entry_count) {
			$results = $wpdb->get_col($wpdb->prepare("
				SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}superforms_entry_data eav ON eav.entry_id = p.ID
				WHERE p.post_type = 'super_contact_entry'
				AND eav.field_value LIKE %s
				LIMIT %d
			", '%' . $wpdb->esc_like($keyword) . '%', $entry_count));
			return $results;
		});

		// Calculate improvement
		$improvement = $time_serialized > 0 ? round($time_serialized / $time_eav, 1) : 0;
		$faster_text = $improvement >= 1
			? $improvement . 'x faster'
			: round(1 / $improvement, 1) . 'x slower';

		return array(
			'benchmark' => 'admin_search',
			'entry_count' => $entry_count,
			'keyword' => $keyword,
			'time_serialized' => $time_serialized,
			'time_eav' => $time_eav,
			'improvement' => $improvement,
			'faster' => $faster_text,
			'message' => sprintf(
				'Admin search: %dms (serialized) vs %dms (EAV) - %s',
				$time_serialized,
				$time_eav,
				$faster_text
			)
		);
	}


	/**
	 * ========================================
	 * DATABASE UTILITIES
	 * ========================================
	 */

	/**
	 * Get Serialized Storage Count
	 *
	 * @return int Number of serialized entries
	 */
	public static function get_serialized_count() {
		global $wpdb;
		return $wpdb->get_var("
			SELECT COUNT(*)
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_super_contact_entry_data'
		");
	}

	/**
	 * Get EAV Table Statistics
	 *
	 * @return array EAV statistics
	 */
	public static function get_eav_stats() {
		global $wpdb;

		$stats = array(
			'total_rows' => $wpdb->get_var("
				SELECT COUNT(*)
				FROM {$wpdb->prefix}superforms_entry_data
			"),
			'unique_entries' => $wpdb->get_var("
				SELECT COUNT(DISTINCT entry_id)
				FROM {$wpdb->prefix}superforms_entry_data
			"),
			'unique_fields' => $wpdb->get_var("
				SELECT COUNT(DISTINCT field_name)
				FROM {$wpdb->prefix}superforms_entry_data
			"),
			'avg_fields_per_entry' => $wpdb->get_var("
				SELECT AVG(field_count)
				FROM (
					SELECT COUNT(*) as field_count
					FROM {$wpdb->prefix}superforms_entry_data
					GROUP BY entry_id
				) as subquery
			"),
			'table_size_mb' => $wpdb->get_var($wpdb->prepare("
				SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
				FROM information_schema.TABLES
				WHERE table_schema = DATABASE()
				AND table_name = %s
			", $wpdb->prefix . 'superforms_entry_data'))
		);

		return $stats;
	}

	/**
	 * Get Index Status
	 *
	 * @return array Index information
	 */
	public static function get_index_status() {
		global $wpdb;

		$indexes = $wpdb->get_results("
			SHOW INDEX FROM {$wpdb->prefix}superforms_entry_data
		");

		$index_info = array();
		foreach ($indexes as $index) {
			$key = $index->Key_name;
			if (!isset($index_info[$key])) {
				$index_info[$key] = array(
					'name' => $key,
					'unique' => ($index->Non_unique == 0),
					'columns' => array()
				);
			}
			$index_info[$key]['columns'][] = $index->Column_name;
		}

		return $index_info;
	}

	/**
	 * Get Sample Entry Data
	 *
	 * @param int $entry_id Entry ID to sample
	 * @return array Entry field data
	 */
	public static function get_sample_entry_data($entry_id) {
		global $wpdb;

		$data = $wpdb->get_results($wpdb->prepare("
			SELECT field_name, LEFT(field_value, 100) as value_preview, field_type
			FROM {$wpdb->prefix}superforms_entry_data
			WHERE entry_id = %d
			ORDER BY field_name ASC
			LIMIT 50
		", $entry_id));

		return $data;
	}


	/**
	 * Delete All EAV Data (Keep Serialized)
	 *
	 * @return array Result with count and message
	 */
	public static function delete_all_eav_data() {
		global $wpdb;

		$deleted = $wpdb->query("
			DELETE FROM {$wpdb->prefix}superforms_entry_data
		");

		error_log('[Super Forms Developer Tools] Deleted all EAV data (' . $deleted . ' rows)');

		return array('deleted' => $deleted, 'message' => sprintf('Deleted %d EAV rows', $deleted));
	}

	/**
	 * Delete All Serialized Data (Keep EAV)
	 *
	 * @return array Result with count and message
	 */
	public static function delete_all_serialized_data() {
		global $wpdb;

		$deleted = $wpdb->query("
			DELETE FROM {$wpdb->postmeta}
			WHERE meta_key = '_super_contact_entry_data'
		");

		error_log('[Super Forms Developer Tools] Deleted all serialized data (' . $deleted . ' rows)');

		return array('deleted' => $deleted, 'message' => sprintf('Deleted %d serialized entries', $deleted));
	}

	/**
	 * Optimize Tables
	 *
	 * @return array Result message
	 */
	public static function optimize_tables() {
		global $wpdb;

		$results = array();

		// Optimize EAV table
		$wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}superforms_entry_data");
		$results[] = 'Optimized superforms_entry_data';

		// Optimize postmeta table
		$wpdb->query("OPTIMIZE TABLE {$wpdb->postmeta}");
		$results[] = 'Optimized postmeta';

		error_log('[Super Forms Developer Tools] Optimized tables');

		return array('message' => implode(', ', $results));
	}

	/**
	 * Rebuild Indexes
	 *
	 * @return array Result message
	 */
	public static function rebuild_indexes() {
		global $wpdb;

		// Drop existing indexes (except PRIMARY)
		$wpdb->query("
			ALTER TABLE {$wpdb->prefix}superforms_entry_data
			DROP INDEX idx_entry_id,
			DROP INDEX idx_field_name,
			DROP INDEX idx_entry_field,
			DROP INDEX idx_field_value
		");

		// Recreate indexes
		$wpdb->query("
			ALTER TABLE {$wpdb->prefix}superforms_entry_data
			ADD INDEX idx_entry_id (entry_id),
			ADD INDEX idx_field_name (field_name),
			ADD INDEX idx_entry_field (entry_id, field_name),
			ADD INDEX idx_field_value (field_value(191))
		");

		error_log('[Super Forms Developer Tools] Rebuilt EAV indexes');

		return array('message' => 'Indexes rebuilt successfully');
	}

	/**
	 * Vacuum Orphaned Data
	 *
	 * @return array Result with count and message
	 */
	public static function vacuum_orphaned_data() {
		global $wpdb;

		// Delete EAV data for entries that no longer exist
		$deleted = $wpdb->query("
			DELETE eav FROM {$wpdb->prefix}superforms_entry_data eav
			LEFT JOIN {$wpdb->posts} p ON p.ID = eav.entry_id
			WHERE p.ID IS NULL
		");

		error_log('[Super Forms Developer Tools] Vacuumed ' . $deleted . ' orphaned rows');

		return array('deleted' => $deleted, 'message' => sprintf('Deleted %d orphaned EAV rows', $deleted));
	}

	/**
	 * Allowed SQL Queries (Whitelist)
	 */
	private static $allowed_queries = array(
		'count_eav_total' => "SELECT COUNT(*) as count FROM {wpdb_prefix}superforms_entry_data",
		'count_eav_entries' => "SELECT COUNT(DISTINCT entry_id) as count FROM {wpdb_prefix}superforms_entry_data",
		'count_serialized' => "SELECT COUNT(*) as count FROM {wpdb_prefix}postmeta WHERE meta_key = '_super_contact_entry_data'",
		'show_indexes' => "SHOW INDEX FROM {wpdb_prefix}superforms_entry_data",
		'table_stats' => "SELECT COUNT(*) as rows, COUNT(DISTINCT entry_id) as entries, COUNT(DISTINCT field_name) as fields FROM {wpdb_prefix}superforms_entry_data",
		'recent_entries' => "SELECT ID, post_title, post_date FROM {wpdb_prefix}posts WHERE post_type = 'super_contact_entry' ORDER BY post_date DESC LIMIT 10",
		'field_names' => "SELECT DISTINCT field_name, COUNT(*) as count FROM {wpdb_prefix}superforms_entry_data GROUP BY field_name ORDER BY count DESC",
		'entry_count_by_form' => "SELECT post_parent as form_id, COUNT(*) as entries FROM {wpdb_prefix}posts WHERE post_type = 'super_contact_entry' GROUP BY post_parent",
		'test_entry_count' => "SELECT COUNT(*) as count FROM {wpdb_prefix}postmeta WHERE meta_key = '_super_test_entry' AND meta_value = '1'",
	);

	/**
	 * Execute Whitelisted SQL Query
	 *
	 * @param string $query_key Query key from whitelist
	 * @return array|WP_Error Query results or error
	 */
	public static function execute_whitelisted_sql($query_key) {
		global $wpdb;

		if (!isset(self::$allowed_queries[$query_key])) {
			return new WP_Error('query_not_allowed', 'Query not in whitelist');
		}

		$query = self::$allowed_queries[$query_key];

		// Replace placeholders
		$query = str_replace('{wpdb_prefix}', $wpdb->prefix, $query);

		// Execute query
		$results = $wpdb->get_results($query);

		if ($wpdb->last_error) {
			return new WP_Error('query_error', $wpdb->last_error);
		}

		return $results;
	}

	/**
	 * Cleanup skipped entries (orphaned entries with no data)
	 *
	 * Deletes entries marked with _skipped field in EAV table.
	 * These are entries that had no form data during migration.
	 *
	 * @return array Results with deleted count
	 * @since 6.4.121
	 */
	public static function cleanup_skipped_entries() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'superforms_entry_data';

		// Find all entry_ids with _cleanup_empty marker (formerly _skipped)
		$empty_entry_ids = $wpdb->get_col(
			"SELECT DISTINCT entry_id
			FROM {$table_name}
			WHERE field_name = '_cleanup_empty'"
		);

		if (empty($empty_entry_ids)) {
			return array(
				'deleted' => 0,
				'message' => 'No empty entries found'
			);
		}

		$deleted_count = 0;

		// Delete each entry (post and all metadata)
		foreach ($empty_entry_ids as $entry_id) {
			// Force delete (bypass trash)
			$result = wp_delete_post($entry_id, true);

			if ($result !== false) {
				// Also delete from EAV table
				$wpdb->delete(
					$table_name,
					array('entry_id' => $entry_id),
					array('%d')
				);

				$deleted_count++;
			}
		}

		// Update migration state to reflect cleanup
		$migration = get_option('superforms_eav_migration', array());
		if (isset($migration['cleanup_queue']['empty_posts'])) {
			$migration['cleanup_queue']['empty_posts'] = max(0, $migration['cleanup_queue']['empty_posts'] - $deleted_count);
			update_option('superforms_eav_migration', $migration);
		}

		error_log('[Super Forms Developer Tools] Cleaned up ' . $deleted_count . ' empty entries');

		return array(
			'deleted' => $deleted_count,
			'message' => sprintf('Deleted %d empty entries', $deleted_count)
		);
	}

	/**
	 * Cleanup orphaned metadata
	 *
	 * Finds and deletes metadata entries (_super_contact_entry_data) that don't have
	 * corresponding posts. This can happen when posts are deleted but metadata remains.
	 *
	 * @since 6.4.122
	 * @return array Result with count and message
	 */
	public static function cleanup_orphaned_metadata() {
		global $wpdb;

		// Find metadata entries without corresponding posts
		$orphaned_post_ids = $wpdb->get_col(
			"SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_super_contact_entry_data'
			AND p.ID IS NULL"
		);

		if (empty($orphaned_post_ids)) {
			return array(
				'deleted' => 0,
				'message' => 'No orphaned metadata found'
			);
		}

		$deleted_count = 0;

		// Delete orphaned metadata for each post_id
		foreach ($orphaned_post_ids as $post_id) {
			// Delete all metadata for this orphaned post
			$result = $wpdb->delete(
				$wpdb->postmeta,
				array('post_id' => $post_id),
				array('%d')
			);

			if ($result !== false) {
				$deleted_count++;
			}
		}

		// Update migration state to reflect cleanup
		$migration = get_option('superforms_eav_migration', array());
		if (isset($migration['cleanup_queue']['orphaned_meta'])) {
			$migration['cleanup_queue']['orphaned_meta'] = max(0, $migration['cleanup_queue']['orphaned_meta'] - $deleted_count);
			update_option('superforms_eav_migration', $migration);
		}

		error_log('[Super Forms Developer Tools] Cleaned up ' . $deleted_count . ' orphaned metadata entries');

		return array(
			'deleted' => $deleted_count,
			'message' => sprintf('Deleted metadata for %d orphaned posts', $deleted_count)
		);
	}
	}

endif;
