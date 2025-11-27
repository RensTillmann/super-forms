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

			// ─────────────────────────────────────────────────────────
			// Triggers/Actions System Tables
			// @since 6.5.0
			// ─────────────────────────────────────────────────────────

			// Main triggers table
			$table_name = $wpdb->prefix . 'superforms_triggers';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				trigger_name VARCHAR(255) NOT NULL,
				scope VARCHAR(50) NOT NULL DEFAULT 'form',
				scope_id BIGINT(20),
				event_id VARCHAR(100) NOT NULL,
				conditions TEXT,
				enabled TINYINT(1) DEFAULT 1,
				execution_order INT DEFAULT 10,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY scope_lookup (scope, scope_id, enabled),
				KEY event_lookup (event_id, enabled),
				KEY form_triggers (scope, scope_id) USING BTREE
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// Trigger actions table (normalized - 1:N relationship)
			$table_name = $wpdb->prefix . 'superforms_trigger_actions';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				trigger_id BIGINT(20) UNSIGNED NOT NULL,
				action_type VARCHAR(100) NOT NULL,
				action_config TEXT,
				execution_order INT DEFAULT 10,
				enabled TINYINT(1) DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY trigger_id (trigger_id),
				KEY action_type (action_type),
				KEY trigger_order (trigger_id, execution_order)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// Trigger execution logs table
			$table_name = $wpdb->prefix . 'superforms_trigger_logs';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				trigger_id BIGINT(20) UNSIGNED NOT NULL,
				action_id BIGINT(20) UNSIGNED,
				entry_id BIGINT(20) UNSIGNED,
				form_id BIGINT(20) UNSIGNED,
				event_id VARCHAR(100) NOT NULL,
				status VARCHAR(20) NOT NULL,
				error_message TEXT,
				execution_time_ms INT,
				context_data LONGTEXT,
				result_data LONGTEXT,
				user_id BIGINT(20) UNSIGNED,
				scheduled_action_id BIGINT(20) UNSIGNED,
				executed_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY trigger_id (trigger_id),
				KEY entry_id (entry_id),
				KEY form_id (form_id),
				KEY status (status),
				KEY executed_at (executed_at),
				KEY form_status (form_id, status)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// Compliance audit table for GDPR and security tracking
			// @since 6.5.0
			$table_name = $wpdb->prefix . 'superforms_compliance_audit';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				action_type VARCHAR(50) NOT NULL,
				user_id BIGINT(20) UNSIGNED,
				object_type VARCHAR(50),
				object_id BIGINT(20) UNSIGNED,
				details LONGTEXT,
				ip_address VARCHAR(45),
				user_agent TEXT,
				performed_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY idx_user_id (user_id),
				KEY idx_action_type (action_type),
				KEY idx_object (object_type, object_id),
				KEY idx_performed_at (performed_at)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// ─────────────────────────────────────────────────────────
			// API Security Tables (Phase 4)
			// @since 6.5.0
			// ─────────────────────────────────────────────────────────

			// API credentials table - encrypted storage for OAuth tokens, API keys, etc.
			$table_name = $wpdb->prefix . 'superforms_api_credentials';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				service VARCHAR(100) NOT NULL,
				credential_key VARCHAR(100) NOT NULL,
				credential_value LONGTEXT NOT NULL,
				user_id BIGINT(20) UNSIGNED,
				form_id BIGINT(20) UNSIGNED,
				expires_at DATETIME,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY service_key_user (service, credential_key, user_id),
				KEY service (service),
				KEY user_id (user_id),
				KEY form_id (form_id),
				KEY expires_at (expires_at)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// API keys table - for external API access to Super Forms
			$table_name = $wpdb->prefix . 'superforms_api_keys';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				key_name VARCHAR(255) NOT NULL,
				api_key_hash VARCHAR(64) NOT NULL,
				api_key_prefix VARCHAR(12) NOT NULL,
				permissions TEXT NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				last_used_at DATETIME,
				last_used_ip VARCHAR(45),
				usage_count BIGINT(20) UNSIGNED DEFAULT 0,
				rate_limit INT UNSIGNED DEFAULT 60,
				expires_at DATETIME,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY api_key_hash (api_key_hash),
				KEY user_id (user_id),
				KEY status (status),
				KEY api_key_prefix (api_key_prefix)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// ─────────────────────────────────────────────────────────
			// Progressive Sessions Table (Phase 1a)
			// @since 6.5.0
			// ─────────────────────────────────────────────────────────

			// Sessions table for form auto-save and pre-submission firewall
			$table_name = $wpdb->prefix . 'superforms_sessions';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				session_key VARCHAR(32) NOT NULL,
				form_id BIGINT(20) UNSIGNED NOT NULL,
				user_id BIGINT(20) UNSIGNED,
				client_token VARCHAR(36),
				user_ip VARCHAR(45),
				status VARCHAR(20) DEFAULT 'draft',
				form_data LONGTEXT,
				metadata LONGTEXT,
				started_at DATETIME NOT NULL,
				last_saved_at DATETIME,
				completed_at DATETIME,
				expires_at DATETIME,
				PRIMARY KEY (id),
				UNIQUE KEY session_key (session_key),
				KEY form_id_status (form_id, status),
				KEY expires_at (expires_at),
				KEY user_lookup (user_id, form_id, status),
				KEY client_token_lookup (client_token, form_id, status)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// ─────────────────────────────────────────────────────────
			// Contact Entries Tables (Phase 17)
			// Replaces super_contact_entry post type
			// @since 6.5.0
			// ─────────────────────────────────────────────────────────

			// Main entries table - core entry data
			$table_name = $wpdb->prefix . 'superforms_entries';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				form_id BIGINT(20) UNSIGNED NOT NULL,
				user_id BIGINT(20) UNSIGNED DEFAULT 0,
				title VARCHAR(255) NOT NULL DEFAULT '',
				wp_status VARCHAR(20) NOT NULL DEFAULT 'publish',
				entry_status VARCHAR(50) DEFAULT NULL,
				created_at DATETIME NOT NULL,
				created_at_gmt DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				updated_at_gmt DATETIME NOT NULL,
				ip_address VARCHAR(45) DEFAULT NULL,
				user_agent VARCHAR(500) DEFAULT NULL,
				session_id BIGINT(20) UNSIGNED DEFAULT NULL,
				PRIMARY KEY (id),
				KEY form_id (form_id),
				KEY user_id (user_id),
				KEY wp_status (wp_status),
				KEY entry_status (entry_status),
				KEY created_at (created_at),
				KEY form_status (form_id, wp_status),
				KEY form_date (form_id, created_at),
				KEY session_id (session_id)
			) ENGINE=InnoDB $charset_collate;";

			dbDelta( $sql );

			// Entry meta table - extensible metadata storage
			// Stores: payment IDs, integration links, custom flags
			$table_name = $wpdb->prefix . 'superforms_entry_meta';

			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				entry_id BIGINT(20) UNSIGNED NOT NULL,
				meta_key VARCHAR(255) NOT NULL,
				meta_value LONGTEXT,
				PRIMARY KEY (id),
				KEY entry_id (entry_id),
				KEY meta_key (meta_key(191)),
				KEY entry_meta (entry_id, meta_key(191))
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
					// Note: Counters calculated live in get_migration_status(), not stored
					'failed_entries'             => array(),
					'verification_failed'        => array(),
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
			update_option( 'superforms_db_version', '1.1.0' ); // Incremented for triggers/actions system tables
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
