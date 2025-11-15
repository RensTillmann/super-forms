<?php
/**
 * Installation related functions and actions.
 *
 * @author      WebRehab
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Install
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Install' ) ) :

	/**
	 * SUPER_Install Class
	 */
	class SUPER_Install {

	/**
	 * Plugin activation handler
	 *
	 * Installs Super Forms for the current site.
	 * On multisite networks, must be activated per-site.
	 *
	 * @since 1.0.0
	 */
	public static function install() {
		// error_log('SUPER_Install::install()');

		// Flush rewrite rules if custom hook not registered
		if ( ! has_action( 'super_forms_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
		}

		/**
		 * Flush rewrite rules after plugin activation
		 *
		 * @since 6.4.111
		 */
		do_action( 'super_forms_flush_rewrite_rules' );

		global $wpdb;
		if ( ! defined( 'SUPER_INSTALLING' ) ) {
			define( 'SUPER_INSTALLING', true );
		}
		// Only save settings on first time
		// In case Super Forms is updated or replaced by a newer version
		// do not override to the default settings
		// The following checks if super_settings doesn't exist
		// If it doesn't we can save the default settings (for the first time)
		if ( ! get_option( 'super_settings' ) ) {
			$default_settings = SUPER_Settings::get_defaults();
			// Now save the settings to the database
			update_option( 'super_settings', $default_settings );
		}

		// Create database tables
		self::create_tables();

		// Initialize migration state tracking
		self::init_migration_state();

		// Schedule background migration if needed (automatic detection)
		if ( class_exists( 'SUPER_Background_Migration' ) ) {
			SUPER_Background_Migration::schedule_if_needed( 'activation' );
		}

		// Update database version
		self::update_db_version();

		// Store plugin version for version-based migration detection
		update_option( 'super_plugin_version', defined( 'SUPER_VERSION' ) ? SUPER_VERSION : '0.0.0' );
	}

		/**
		 * Create database tables
		 *
		 * @since 6.0.0
		 */
		private static function create_tables() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();
			if ( empty( $charset_collate ) ) {
				$charset_collate = 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
			}

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			// EAV table for contact entry data
			$table_name = $wpdb->prefix . 'superforms_entry_data';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				entry_id BIGINT(20) UNSIGNED NOT NULL,
				form_id BIGINT(20) UNSIGNED NOT NULL,
				field_name VARCHAR(255) NOT NULL,
				field_value LONGTEXT,
				field_type VARCHAR(50),
				field_label VARCHAR(255),
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY entry_id (entry_id),
				KEY form_id (form_id),
				KEY field_name (field_name),
				KEY entry_field (entry_id, field_name),
				KEY field_value (field_value(191)),
				KEY form_field_filter (form_id, field_name, field_value(191)),
				KEY form_entry_field (form_id, entry_id, field_name)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// Run schema upgrades for existing installations
			self::upgrade_database_schema();
		}

		/**
		 * Upgrade database schema for existing installations
		 *
		 * Adds form_id column and composite indexes if they don't exist.
		 * Safe to run multiple times - checks before altering.
		 *
		 * @since 6.0.0
		 */
		private static function upgrade_database_schema() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'superforms_entry_data';

			// Check if table exists first
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			if ( $table_exists !== $table_name ) {
				return; // Table doesn't exist yet, create_tables() will handle it
			}

			// Check if form_id column exists
			$column_exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND COLUMN_NAME = 'form_id'",
				DB_NAME,
				$table_name
			) );

			if ( ! $column_exists ) {
				// Add form_id column
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN form_id BIGINT(20) UNSIGNED NOT NULL AFTER entry_id" );

				// Add form_id index
				$wpdb->query( "ALTER TABLE {$table_name} ADD KEY form_id (form_id)" );

				// Add composite indexes
				$wpdb->query( "ALTER TABLE {$table_name} ADD KEY form_field_filter (form_id, field_name, field_value(191))" );
				$wpdb->query( "ALTER TABLE {$table_name} ADD KEY form_entry_field (form_id, entry_id, field_name)" );

				// Populate form_id from existing entries
				self::populate_form_id_column();

				// Log the upgrade
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
					error_log( '[Super Forms] Database schema upgraded: Added form_id column and composite indexes' );
				}
			}
		}

		/**
		 * Populate form_id column for existing entry_data records
		 *
		 * @since 6.0.0
		 */
		private static function populate_form_id_column() {
			global $wpdb;

			// Populate form_id by joining with entries table
			$wpdb->query( "
				UPDATE {$wpdb->prefix}superforms_entry_data ed
				INNER JOIN {$wpdb->posts} p ON p.ID = ed.entry_id
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_super_form_id'
				SET ed.form_id = pm.meta_value
				WHERE ed.form_id = 0
			" );

			// Log completion
			$updated_count = $wpdb->rows_affected;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( "[Super Forms] Populated form_id for {$updated_count} entry_data records" );
			}
		}

		/**
		 * Initialize migration state tracking
		 *
		 * @since 6.0.0
		 */
		private static function init_migration_state() {
			// Only initialize if not already set
			$migration = get_option( 'superforms_eav_migration' );
			if ( false === $migration ) {
				$migration_state = array(
					'status'                     => 'not_started',
					'using_storage'              => 'serialized',
					// Note: migrated_entries calculated live in get_migration_status()
					'failed_entries'             => array(),
					'verification_failed'        => array(),  // NEW: Separate tracking for verification issues
					'cleanup_queue'              => array(
						'empty_posts'            => 0,  // Posts with no form data
						'orphaned_meta'          => 0,  // Metadata without corresponding posts
					),
					'started_at'                 => '',
					'completed_at'               => '',
					'last_processed_id'          => 0,
					'verification_passed'        => false,
					'rollback_available'         => false,
					// Background processing fields
					'background_enabled'         => false,
					'last_batch_processed_at'    => '',
					'last_schedule_attempt'      => '',
					'auto_triggered_by'          => '',
					'health_check_count'         => 0,
					'last_health_check'          => '',
					'batch_count'                => 0,  // NEW: Track batch iterations for optimized recalculation
				);
				update_option( 'superforms_eav_migration', $migration_state );
			}
		}

		/**
		 * Update database version
		 *
		 * @since 6.0.0
		 */
		private static function update_db_version() {
			update_option( 'superforms_db_version', '1.0.0' );
		}

	/**
	 * Ensure EAV database tables exist (auto-create if missing)
	 *
	 * Public helper for self-healing setup - can be called from version detection,
	 * FTP uploads, git pulls, or any scenario where tables might not exist yet.
	 *
	 * @return bool True if tables were created, false if already existed
	 * @since 6.0.0
	 */
	public static function ensure_tables_exist() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'superforms_entry_data';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		if ( $table_exists !== $table_name ) {
			// Table missing - create it
			self::create_tables();

			// Log for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( '[Super Forms Migration] EAV database tables created automatically' );
			}

			return true; // Tables were created
		}

		return false; // Tables already existed
	}

	/**
	 * Ensure migration state is initialized (auto-initialize if missing)
	 *
	 * Public helper for self-healing setup - can be called from version detection,
	 * FTP uploads, git pulls, or any scenario where state might not exist yet.
	 *
	 * @return bool True if state was initialized, false if already existed
	 * @since 6.0.0
	 */
	public static function ensure_migration_state_initialized() {
		$state = get_option( 'superforms_eav_migration' );

		if ( false === $state ) {
			// State missing - initialize it
			self::init_migration_state();

			// Log for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( '[Super Forms Migration] Migration state initialized automatically' );
			}

			return true; // State was initialized
		}

		return false; // State already existed
	}

		/**
		 *  Deactivate
		 *
		 *  Upon plugin deactivation delete activation
		 *
		 *  @since      1.9
		 */
		public static function deactivate() {
			// error_log('SUPER_Install::deactivate()');

			// Flush rewrite rules if custom hook not registered
			if ( ! has_action( 'super_forms_flush_rewrite_rules' ) ) {
				flush_rewrite_rules();
			}

			/**
			 * Flush rewrite rules after plugin deactivation
			 *
			 * @since 6.4.111
			 */
			do_action( 'super_forms_flush_rewrite_rules' );

			delete_option( '_sf_permalinks_flushed' );
			wp_clear_scheduled_hook( 'super_client_data_garbage_collection' );
			wp_clear_scheduled_hook( 'super_cron_reminders' );
			wp_clear_scheduled_hook( 'super_scheduled_trigger_actions' );
			do_action( 'after_super_forms_deactivated' );
		}
	}
endif;
