<?php
/**
 * Form Migration Validation Script
 *
 * This script validates the form migration from wp_posts to wp_superforms_forms table.
 * It checks migration completion status and data integrity.
 *
 * Usage: Run via WP-CLI or load in WordPress admin (requires manage_options capability)
 * WP-CLI: wp eval-file src/includes/admin/validate-form-migration.php
 *
 * @since 6.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Ensure user has permission
if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
	wp_die( 'Unauthorized access' );
}

class SUPER_Form_Migration_Validator {

	/**
	 * Run the full validation
	 *
	 * @return array Validation results
	 */
	public static function validate() {
		$results = array(
			'timestamp'       => current_time( 'mysql' ),
			'migration'       => self::check_migration_status(),
			'data_integrity'  => self::check_data_integrity(),
			'orphaned_data'   => self::check_orphaned_data(),
			'rest_api'        => self::check_rest_api(),
			'recommendations' => array(),
		);

		// Generate recommendations
		$results['recommendations'] = self::generate_recommendations( $results );

		return $results;
	}

	/**
	 * Check migration status
	 *
	 * @return array Migration status details
	 */
	private static function check_migration_status() {
		if ( ! class_exists( 'SUPER_Form_Background_Migration' ) ) {
			return array(
				'status'  => 'error',
				'message' => 'Migration class not found',
			);
		}

		$status = SUPER_Form_Background_Migration::get_migration_status();

		return array(
			'status'           => $status['is_complete'] ? 'complete' : 'incomplete',
			'total_forms'      => $status['total_forms'],
			'migrated'         => $status['migrated'],
			'remaining'        => $status['remaining'],
			'failed_count'     => $status['failed_count'],
			'failed_forms'     => $status['failed_forms'],
			'progress_percent' => $status['progress_percent'],
			'migration_status' => $status['status'],
		);
	}

	/**
	 * Check data integrity between old and new systems
	 *
	 * @return array Integrity check results
	 */
	private static function check_data_integrity() {
		global $wpdb;

		// Count forms in old system (wp_posts)
		$old_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'super_form'"
		);

		// Count forms in new system (custom table)
		$new_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}superforms_forms"
		);

		// Count migrated forms (forms with migration marker)
		$migrated_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT pm.post_id)
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE p.post_type = %s
				AND pm.meta_key = %s",
				'super_form',
				'_super_migrated_to_table'
			)
		);

		// Sample validation: Check if first 5 forms match
		$sample_forms = $wpdb->get_results(
			"SELECT ID, post_title FROM {$wpdb->posts}
			WHERE post_type = 'super_form'
			ORDER BY ID ASC
			LIMIT 5"
		);

		$samples_match = true;
		$sample_details = array();

		foreach ( $sample_forms as $post ) {
			$new_form = SUPER_Form_DAL::get( $post->ID );
			$match    = $new_form && $new_form->name === $post->post_title;

			$sample_details[] = array(
				'id'         => $post->ID,
				'old_title'  => $post->post_title,
				'new_title'  => $new_form ? $new_form->name : null,
				'match'      => $match,
			);

			if ( ! $match ) {
				$samples_match = false;
			}
		}

		return array(
			'old_system_count'  => $old_count,
			'new_system_count'  => $new_count,
			'migrated_count'    => $migrated_count,
			'counts_match'      => $old_count === $new_count,
			'migration_markers' => $migrated_count,
			'sample_validation' => array(
				'all_match' => $samples_match,
				'details'   => $sample_details,
			),
		);
	}

	/**
	 * Check for orphaned data
	 *
	 * @return array Orphaned data results
	 */
	private static function check_orphaned_data() {
		global $wpdb;

		// Find forms in old system not in new system
		$orphaned_old = $wpdb->get_results(
			"SELECT p.ID, p.post_title
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->prefix}superforms_forms f ON p.ID = f.id
			WHERE p.post_type = 'super_form'
			AND f.id IS NULL
			LIMIT 10"
		);

		// Find forms in new system not in old system
		$orphaned_new = $wpdb->get_results(
			"SELECT f.id, f.name
			FROM {$wpdb->prefix}superforms_forms f
			LEFT JOIN {$wpdb->posts} p ON f.id = p.ID
			WHERE p.ID IS NULL
			LIMIT 10"
		);

		return array(
			'orphaned_in_old_system' => array(
				'count' => count( $orphaned_old ),
				'forms' => $orphaned_old,
			),
			'orphaned_in_new_system' => array(
				'count' => count( $orphaned_new ),
				'forms' => $orphaned_new,
			),
		);
	}

	/**
	 * Check REST API availability
	 *
	 * @return array REST API check results
	 */
	private static function check_rest_api() {
		$routes = rest_get_server()->get_routes();

		$forms_routes = array();
		foreach ( $routes as $route => $handlers ) {
			if ( strpos( $route, '/super-forms/v1/forms' ) === 0 ) {
				$forms_routes[] = $route;
			}
		}

		$expected_routes = array(
			'/super-forms/v1/forms',
			'/super-forms/v1/forms/(?P<id>[\d]+)',
			'/super-forms/v1/forms/(?P<id>[\d]+)/operations',
			'/super-forms/v1/forms/(?P<id>[\d]+)/versions',
			'/super-forms/v1/forms/(?P<id>[\d]+)/revert/(?P<version_id>[\d]+)',
		);

		$missing_routes = array_diff( $expected_routes, $forms_routes );

		return array(
			'rest_enabled'     => rest_get_url_prefix() !== '',
			'routes_found'     => count( $forms_routes ),
			'expected_routes'  => count( $expected_routes ),
			'all_routes_exist' => empty( $missing_routes ),
			'missing_routes'   => array_values( $missing_routes ),
			'registered_routes' => $forms_routes,
		);
	}

	/**
	 * Generate recommendations based on validation results
	 *
	 * @param array $results Validation results
	 * @return array Recommendations
	 */
	private static function generate_recommendations( $results ) {
		$recommendations = array();

		// Migration status recommendations
		if ( $results['migration']['status'] !== 'complete' ) {
			$recommendations[] = array(
				'priority' => 'high',
				'category' => 'migration',
				'message'  => sprintf(
					'Migration is not complete. %d of %d forms migrated (%.2f%%).',
					$results['migration']['migrated'],
					$results['migration']['total_forms'],
					$results['migration']['progress_percent']
				),
				'action'   => 'Wait for migration to complete or manually trigger: SUPER_Form_Background_Migration::schedule_if_needed()',
			);
		}

		if ( $results['migration']['failed_count'] > 0 ) {
			$recommendations[] = array(
				'priority' => 'high',
				'category' => 'migration',
				'message'  => sprintf( '%d forms failed to migrate.', $results['migration']['failed_count'] ),
				'action'   => 'Review failed form IDs: ' . implode( ', ', $results['migration']['failed_forms'] ),
			);
		}

		// Data integrity recommendations
		if ( ! $results['data_integrity']['counts_match'] ) {
			$recommendations[] = array(
				'priority' => 'high',
				'category' => 'integrity',
				'message'  => sprintf(
					'Form counts do not match. Old system: %d, New system: %d',
					$results['data_integrity']['old_system_count'],
					$results['data_integrity']['new_system_count']
				),
				'action'   => 'Investigate discrepancy before proceeding with cleanup.',
			);
		}

		// Orphaned data recommendations
		if ( $results['orphaned_data']['orphaned_in_old_system']['count'] > 0 ) {
			$recommendations[] = array(
				'priority' => 'medium',
				'category' => 'orphaned',
				'message'  => sprintf(
					'%d forms exist in old system but not in new system.',
					$results['orphaned_data']['orphaned_in_old_system']['count']
				),
				'action'   => 'These forms need to be migrated or are test data that can be deleted.',
			);
		}

		// REST API recommendations
		if ( ! $results['rest_api']['all_routes_exist'] ) {
			$recommendations[] = array(
				'priority' => 'high',
				'category' => 'rest_api',
				'message'  => 'Some REST API routes are missing.',
				'action'   => 'Missing routes: ' . implode( ', ', $results['rest_api']['missing_routes'] ),
			);
		}

		// All clear recommendation
		if ( empty( $recommendations ) ) {
			$recommendations[] = array(
				'priority' => 'info',
				'category' => 'success',
				'message'  => 'All validation checks passed! System is ready for Phase 2.',
				'action'   => 'Proceed with completing missing DAL methods (duplicate, search, archive, restore).',
			);
		}

		return $recommendations;
	}

	/**
	 * Format results as text
	 *
	 * @param array $results Validation results
	 * @return string Formatted text output
	 */
	public static function format_results( $results ) {
		$output = "========================================\n";
		$output .= "SUPER FORMS MIGRATION VALIDATION REPORT\n";
		$output .= "========================================\n";
		$output .= "Timestamp: {$results['timestamp']}\n\n";

		// Migration Status
		$output .= "--- MIGRATION STATUS ---\n";
		$output .= "Status: {$results['migration']['migration_status']}\n";
		$output .= "Progress: {$results['migration']['progress_percent']}%\n";
		$output .= "Total Forms: {$results['migration']['total_forms']}\n";
		$output .= "Migrated: {$results['migration']['migrated']}\n";
		$output .= "Remaining: {$results['migration']['remaining']}\n";
		$output .= "Failed: {$results['migration']['failed_count']}\n";
		if ( ! empty( $results['migration']['failed_forms'] ) ) {
			$output .= "Failed Form IDs: " . implode( ', ', $results['migration']['failed_forms'] ) . "\n";
		}
		$output .= "\n";

		// Data Integrity
		$output .= "--- DATA INTEGRITY ---\n";
		$output .= "Old System Count: {$results['data_integrity']['old_system_count']}\n";
		$output .= "New System Count: {$results['data_integrity']['new_system_count']}\n";
		$output .= "Counts Match: " . ( $results['data_integrity']['counts_match'] ? 'YES' : 'NO' ) . "\n";
		$output .= "Migrated Count: {$results['data_integrity']['migrated_count']}\n";
		$output .= "Sample Validation: " . ( $results['data_integrity']['sample_validation']['all_match'] ? 'PASS' : 'FAIL' ) . "\n";
		$output .= "\n";

		// Orphaned Data
		$output .= "--- ORPHANED DATA ---\n";
		$output .= "Orphaned in Old System: {$results['orphaned_data']['orphaned_in_old_system']['count']}\n";
		$output .= "Orphaned in New System: {$results['orphaned_data']['orphaned_in_new_system']['count']}\n";
		$output .= "\n";

		// REST API
		$output .= "--- REST API ---\n";
		$output .= "REST Enabled: " . ( $results['rest_api']['rest_enabled'] ? 'YES' : 'NO' ) . "\n";
		$output .= "Routes Found: {$results['rest_api']['routes_found']}/{$results['rest_api']['expected_routes']}\n";
		$output .= "All Routes Exist: " . ( $results['rest_api']['all_routes_exist'] ? 'YES' : 'NO' ) . "\n";
		if ( ! empty( $results['rest_api']['missing_routes'] ) ) {
			$output .= "Missing Routes:\n";
			foreach ( $results['rest_api']['missing_routes'] as $route ) {
				$output .= "  - {$route}\n";
			}
		}
		$output .= "\n";

		// Recommendations
		$output .= "--- RECOMMENDATIONS ---\n";
		if ( empty( $results['recommendations'] ) ) {
			$output .= "No recommendations. All checks passed!\n";
		} else {
			foreach ( $results['recommendations'] as $i => $rec ) {
				$output .= sprintf(
					"%d. [%s] %s: %s\n   Action: %s\n",
					$i + 1,
					strtoupper( $rec['priority'] ),
					strtoupper( $rec['category'] ),
					$rec['message'],
					$rec['action']
				);
			}
		}
		$output .= "\n";

		$output .= "========================================\n";

		return $output;
	}
}

// If running via WP-CLI, execute and output results
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$results = SUPER_Form_Migration_Validator::validate();
	$output  = SUPER_Form_Migration_Validator::format_results( $results );
	WP_CLI::log( $output );

	// Also output as JSON for programmatic access
	if ( isset( $args ) && in_array( '--json', $args, true ) ) {
		WP_CLI::log( "\n--- JSON OUTPUT ---" );
		WP_CLI::log( wp_json_encode( $results, JSON_PRETTY_PRINT ) );
	}
}

// If running in WordPress admin, output HTML
if ( ! defined( 'WP_CLI' ) && is_admin() ) {
	$results = SUPER_Form_Migration_Validator::validate();
	$output  = SUPER_Form_Migration_Validator::format_results( $results );
	echo '<pre>' . esc_html( $output ) . '</pre>';
}
