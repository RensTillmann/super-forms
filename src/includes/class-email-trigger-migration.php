<?php
/**
 * Email Trigger Migration
 *
 * Migrates legacy email settings from _super_form_settings to the
 * triggers/actions system. Handles one-time background migration via
 * Action Scheduler.
 *
 * Legacy email settings migrated:
 * - Admin Email (send, header_to, header_subject, email_body, etc.)
 * - Confirmation Email (confirm, confirm_to, confirm_subject, confirm_body, etc.)
 * - Email Reminders 1-3 (email_reminder_1, email_reminder_2, email_reminder_3)
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SUPER_Email_Trigger_Migration' ) ) :

	/**
	 * SUPER_Email_Trigger_Migration Class
	 */
	class SUPER_Email_Trigger_Migration {

		/**
		 * Migration state option key
		 */
		const MIGRATION_OPTION = 'superforms_email_trigger_migration';

		/**
		 * Meta key for tracking email-to-trigger mapping
		 */
		const EMAIL_TRIGGER_MAP = '_super_email_triggers';

		/**
		 * Batch size for migration
		 */
		const BATCH_SIZE = 10;

		/**
		 * Initialize migration hooks
		 *
		 * @since 6.5.0
		 */
		public static function init() {
			// Check migration state on admin init
			add_action( 'admin_init', array( __CLASS__, 'maybe_schedule_migration' ) );

			// Background migration via Action Scheduler
			add_action( 'super_migrate_emails_batch', array( __CLASS__, 'process_batch' ), 10, 1 );
			add_action( 'super_email_migration_complete', array( __CLASS__, 'migration_complete' ) );
		}

		/**
		 * Get migration state
		 *
		 * @return array Migration state
		 * @since 6.5.0
		 */
		public static function get_state() {
			return get_option(
				self::MIGRATION_OPTION,
				array(
					'status'          => 'not_started',
					'started_at'      => null,
					'completed_at'    => null,
					'forms_migrated'  => 0,
					'forms_total'     => 0,
					'emails_migrated' => 0,
					'failed_forms'    => array(),
					'current_offset'  => 0,
				)
			);
		}

		/**
		 * Update migration state
		 *
		 * @param array $updates State updates
		 * @since 6.5.0
		 */
		public static function update_state( $updates ) {
			$state = self::get_state();
			$state = array_merge( $state, $updates );
			update_option( self::MIGRATION_OPTION, $state );
		}

		/**
		 * Check if migration is complete
		 *
		 * @return bool
		 * @since 6.5.0
		 */
		public static function is_complete() {
			$state = self::get_state();
			return $state['status'] === 'completed';
		}

		/**
		 * Check if migration is needed and schedule it
		 *
		 * @since 6.5.0
		 */
		public static function maybe_schedule_migration() {
			$state = self::get_state();

			// Already completed or in progress
			if ( in_array( $state['status'], array( 'completed', 'in_progress' ), true ) ) {
				return;
			}

			// Count forms that need migration
			$forms_count = self::count_forms_needing_migration();

			if ( $forms_count === 0 ) {
				// No forms need migration
				self::update_state( array(
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
				) );
				return;
			}

			// Start migration
			self::update_state( array(
				'status'         => 'in_progress',
				'started_at'     => current_time( 'mysql' ),
				'forms_total'    => $forms_count,
				'current_offset' => 0,
			) );

			// Schedule first batch
			self::schedule_next_batch( 0 );
		}

		/**
		 * Schedule next migration batch
		 *
		 * @param int $offset Current offset
		 * @since 6.5.0
		 */
		private static function schedule_next_batch( $offset ) {
			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action(
					'super_migrate_emails_batch',
					array( 'offset' => $offset ),
					'super-forms-email-migration'
				);
			} else {
				// Fallback: process synchronously
				self::process_batch( $offset );
			}
		}

		/**
		 * Count forms that need email migration
		 *
		 * @return int Form count
		 * @since 6.5.0
		 */
		public static function count_forms_needing_migration() {
			global $wpdb;

			// Count forms with legacy email settings that haven't been migrated
			// Check for actual email content fields (not just enabled flags) to catch disabled emails too
			$count = $wpdb->get_var(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '" . self::EMAIL_TRIGGER_MAP . "'
				WHERE p.post_type = 'super_form'
				AND p.post_status IN ('publish', 'draft')
				AND pm.meta_key = '_super_form_settings'
				AND (
					pm.meta_value LIKE '%\"header_to\";s:%'
					OR pm.meta_value LIKE '%\"header_subject\";s:%'
					OR pm.meta_value LIKE '%\"email_body\";s:%'
					OR pm.meta_value LIKE '%\"confirm_to\";s:%'
					OR pm.meta_value LIKE '%\"confirm_subject\";s:%'
					OR pm.meta_value LIKE '%\"confirm_body\";s:%'
					OR pm.meta_value LIKE '%\"email_reminder_1_to\";s:%'
					OR pm.meta_value LIKE '%\"email_reminder_2_to\";s:%'
					OR pm.meta_value LIKE '%\"email_reminder_3_to\";s:%'
				)
				AND pm2.meta_id IS NULL"
			);

			return absint( $count );
		}

		/**
		 * Get forms needing migration (batch)
		 *
		 * @param int $offset Offset for pagination
		 * @param int $limit  Limit for pagination
		 * @return array Form IDs
		 * @since 6.5.0
		 */
		public static function get_forms_needing_migration( $offset = 0, $limit = 50 ) {
			global $wpdb;

			// Check for actual email content fields (not just enabled flags) to catch disabled emails too
			$forms = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT p.ID
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
					WHERE p.post_type = 'super_form'
					AND p.post_status IN ('publish', 'draft')
					AND pm.meta_key = '_super_form_settings'
					AND (
						pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
						OR pm.meta_value LIKE %s
					)
					AND pm2.meta_id IS NULL
					ORDER BY p.ID ASC
					LIMIT %d OFFSET %d",
					self::EMAIL_TRIGGER_MAP,
					'%"header_to";s:%',
					'%"header_subject";s:%',
					'%"email_body";s:%',
					'%"confirm_to";s:%',
					'%"confirm_subject";s:%',
					'%"confirm_body";s:%',
					'%"email_reminder_1_to";s:%',
					'%"email_reminder_2_to";s:%',
					'%"email_reminder_3_to";s:%',
					$limit,
					$offset
				)
			);

			return array_map( 'absint', $forms );
		}

		/**
		 * Process a batch of forms
		 *
		 * @param int $offset Current offset
		 * @since 6.5.0
		 */
		public static function process_batch( $offset ) {
			$forms = self::get_forms_needing_migration( $offset, self::BATCH_SIZE );

			if ( empty( $forms ) ) {
				// Migration complete
				self::migration_complete();
				return;
			}

			$state = self::get_state();
			$emails_in_batch = 0;

			foreach ( $forms as $form_id ) {
				$result = self::migrate_form( $form_id );

				if ( ! is_wp_error( $result ) ) {
					$state['forms_migrated']++;
					$emails_in_batch += $result['emails_migrated'];
				} else {
					$state['failed_forms'][] = array(
						'form_id' => $form_id,
						'error'   => $result->get_error_message(),
					);
				}
			}

			$state['emails_migrated'] = ( $state['emails_migrated'] ?? 0 ) + $emails_in_batch;
			$state['current_offset']  = $offset + self::BATCH_SIZE;
			self::update_state( $state );

			// Schedule next batch if there are more forms
			if ( count( $forms ) === self::BATCH_SIZE ) {
				self::schedule_next_batch( $offset + self::BATCH_SIZE );
			} else {
				// This was the last batch
				self::migration_complete();
			}
		}

		/**
		 * Migrate a single form's email settings to triggers
		 *
		 * @param int $form_id Form ID
		 * @return array|WP_Error Migration result
		 * @since 6.5.0
		 */
		public static function migrate_form( $form_id ) {
			$form_id = absint( $form_id );

			// Get form settings
			$settings = get_post_meta( $form_id, '_super_form_settings', true );

			if ( empty( $settings ) || ! is_array( $settings ) ) {
				// Mark as migrated (no settings)
				update_post_meta( $form_id, self::EMAIL_TRIGGER_MAP, array() );
				return array( 'emails_migrated' => 0 );
			}

			$trigger_map     = array();
			$emails_migrated = 0;

			// Migrate Admin Email (always migrate if settings exist, track enabled state)
			if ( self::has_admin_email_settings( $settings ) ) {
				$enabled = self::is_email_enabled( $settings, 'send' );
				$result  = self::migrate_admin_email( $form_id, $settings, $enabled );
				if ( ! is_wp_error( $result ) ) {
					$trigger_map['admin'] = $result['trigger_id'];
					$emails_migrated++;
				}
			}

			// Migrate Confirmation Email (always migrate if settings exist)
			if ( self::has_confirmation_email_settings( $settings ) ) {
				$enabled = self::is_email_enabled( $settings, 'confirm' );
				$result  = self::migrate_confirmation_email( $form_id, $settings, $enabled );
				if ( ! is_wp_error( $result ) ) {
					$trigger_map['confirmation'] = $result['trigger_id'];
					$emails_migrated++;
				}
			}

			// Migrate Email Reminders (dynamic count based on email_reminder_amount setting)
			$reminder_limit = ! empty( $settings['email_reminder_amount'] ) ? absint( $settings['email_reminder_amount'] ) : 3;
			for ( $i = 1; $i <= $reminder_limit; $i++ ) {
				if ( self::has_reminder_email_settings( $settings, $i ) ) {
					$enabled = self::is_email_enabled( $settings, 'email_reminder_' . $i );
					$result  = self::migrate_reminder_email( $form_id, $settings, $i, $enabled );
					if ( ! is_wp_error( $result ) ) {
						$trigger_map[ 'reminder_' . $i ] = $result['trigger_id'];
						$emails_migrated++;
					}
				}
			}

			// Store mapping
			update_post_meta( $form_id, self::EMAIL_TRIGGER_MAP, $trigger_map );

			return array(
				'form_id'         => $form_id,
				'emails_migrated' => $emails_migrated,
				'trigger_map'     => $trigger_map,
			);
		}

		/**
		 * Check if an email type is enabled
		 *
		 * @param array  $settings Form settings
		 * @param string $key      Setting key
		 * @return bool
		 * @since 6.5.0
		 */
		private static function is_email_enabled( $settings, $key ) {
			if ( empty( $settings[ $key ] ) ) {
				return false;
			}
			return in_array( $settings[ $key ], array( 'yes', 'true', true ), true );
		}

		/**
		 * Check if admin email settings exist (regardless of enabled state)
		 *
		 * @param array $settings Form settings
		 * @return bool
		 * @since 6.5.0
		 */
		private static function has_admin_email_settings( $settings ) {
			return ! empty( $settings['header_to'] )
				|| ! empty( $settings['header_subject'] )
				|| ! empty( $settings['email_body'] );
		}

		/**
		 * Check if confirmation email settings exist (regardless of enabled state)
		 *
		 * @param array $settings Form settings
		 * @return bool
		 * @since 6.5.0
		 */
		private static function has_confirmation_email_settings( $settings ) {
			return ! empty( $settings['confirm_to'] )
				|| ! empty( $settings['confirm_subject'] )
				|| ! empty( $settings['confirm_body'] );
		}

		/**
		 * Check if reminder email settings exist (regardless of enabled state)
		 *
		 * @param array $settings Form settings
		 * @param int   $i        Reminder number (1-3)
		 * @return bool
		 * @since 6.5.0
		 */
		private static function has_reminder_email_settings( $settings, $i ) {
			$prefix = 'email_reminder_' . $i;
			return ! empty( $settings[ $prefix . '_to' ] )
				|| ! empty( $settings[ $prefix . '_subject' ] )
				|| ! empty( $settings[ $prefix . '_body' ] );
		}

		/**
		 * Migrate Admin Email to trigger
		 *
		 * @param int   $form_id  Form ID
		 * @param array $settings Form settings
		 * @param bool  $enabled  Whether the email was enabled in legacy settings
		 * @return array|WP_Error Result
		 * @since 6.5.0
		 */
		private static function migrate_admin_email( $form_id, $settings, $enabled = true ) {
			// Build action config from legacy settings
			$action_config = array(
				'to'          => $settings['header_to'] ?? '{option_admin_email}',
				'subject'     => $settings['header_subject'] ?? __( 'New submission', 'super-forms' ),
				'body'        => self::build_legacy_body( $settings, 'email' ),
				'body_type'   => 'legacy_html',
				'from'        => self::get_from_email( $settings, 'header' ),
				'from_name'   => self::get_from_name( $settings, 'header' ),
				'cc'          => $settings['header_cc'] ?? '',
				'bcc'         => $settings['header_bcc'] ?? '',
				'attachments' => $settings['admin_attachments'] ?? '',
			);

			// Reply-to
			if ( ! empty( $settings['header_reply_enabled'] ) && $settings['header_reply_enabled'] === 'true' ) {
				$action_config['reply_to']      = $settings['header_reply'] ?? '';
				$action_config['reply_to_name'] = $settings['header_reply_name'] ?? '';
			}

			// Loop settings for {loop_fields}
			$action_config['loop_open']      = '<table cellpadding="5">';
			$action_config['loop']           = $settings['email_loop'] ?? '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>';
			$action_config['loop_close']     = '</table>';
			$action_config['exclude_empty']  = ( ! empty( $settings['email_exclude_empty'] ) && $settings['email_exclude_empty'] === 'true' );
			$action_config['rtl']            = ( ! empty( $settings['email_rtl'] ) && $settings['email_rtl'] === 'true' );

			// Additional headers
			if ( ! empty( $settings['header_additional'] ) ) {
				$action_config['additional_headers'] = $settings['header_additional'];
			}

			return self::create_email_trigger( $form_id, 'Admin Email', 'form.submitted', $action_config, null, $enabled );
		}

		/**
		 * Migrate Confirmation Email to trigger
		 *
		 * @param int   $form_id  Form ID
		 * @param array $settings Form settings
		 * @param bool  $enabled  Whether the email was enabled in legacy settings
		 * @return array|WP_Error Result
		 * @since 6.5.0
		 */
		private static function migrate_confirmation_email( $form_id, $settings, $enabled = true ) {
			$action_config = array(
				'to'          => $settings['confirm_to'] ?? '{email}',
				'subject'     => $settings['confirm_subject'] ?? __( 'Thank you!', 'super-forms' ),
				'body'        => self::build_legacy_body( $settings, 'confirm' ),
				'body_type'   => 'legacy_html',
				'from'        => self::get_from_email( $settings, 'confirm' ),
				'from_name'   => self::get_from_name( $settings, 'confirm' ),
				'cc'          => $settings['confirm_header_cc'] ?? '',
				'bcc'         => $settings['confirm_header_bcc'] ?? '',
				'attachments' => $settings['confirm_attachments'] ?? '',
			);

			// Reply-to
			if ( ! empty( $settings['confirm_header_reply_enabled'] ) && $settings['confirm_header_reply_enabled'] === 'true' ) {
				$action_config['reply_to']      = $settings['confirm_header_reply'] ?? '';
				$action_config['reply_to_name'] = $settings['confirm_header_reply_name'] ?? '';
			}

			// Loop settings
			$action_config['loop_open']      = '<table cellpadding="5">';
			$action_config['loop']           = $settings['confirm_email_loop'] ?? '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>';
			$action_config['loop_close']     = '</table>';
			$action_config['exclude_empty']  = ( ! empty( $settings['confirm_exclude_empty'] ) && $settings['confirm_exclude_empty'] === 'true' );
			$action_config['rtl']            = ( ! empty( $settings['confirm_rtl'] ) && $settings['confirm_rtl'] === 'true' );

			// Additional headers
			if ( ! empty( $settings['confirm_header_additional'] ) ) {
				$action_config['additional_headers'] = $settings['confirm_header_additional'];
			}

			return self::create_email_trigger( $form_id, 'Confirmation Email', 'form.submitted', $action_config, null, $enabled );
		}

		/**
		 * Migrate Email Reminder to trigger
		 *
		 * @param int   $form_id  Form ID
		 * @param array $settings Form settings
		 * @param int   $index    Reminder index (1-3)
		 * @param bool  $enabled  Whether the email was enabled in legacy settings
		 * @return array|WP_Error Result
		 * @since 6.5.0
		 */
		private static function migrate_reminder_email( $form_id, $settings, $index, $enabled = true ) {
			$prefix = 'email_reminder_' . $index;

			$action_config = array(
				'to'         => $settings[ $prefix . '_to' ] ?? '{email}',
				'subject'    => $settings[ $prefix . '_subject' ] ?? '',
				'body'       => self::build_reminder_body( $settings, $prefix ),
				'body_type'  => 'legacy_html',
				'from'       => self::get_from_email( $settings, $prefix ),
				'from_name'  => self::get_from_name( $settings, $prefix ),
				'cc'         => $settings[ $prefix . '_header_cc' ] ?? '',
				'bcc'        => $settings[ $prefix . '_header_bcc' ] ?? '',
			);

			// Reply-to
			if ( ! empty( $settings[ $prefix . '_header_reply_enabled' ] ) && $settings[ $prefix . '_header_reply_enabled' ] === 'true' ) {
				$action_config['reply_to']      = $settings[ $prefix . '_header_reply' ] ?? '';
				$action_config['reply_to_name'] = $settings[ $prefix . '_header_reply_name' ] ?? '';
			}

			// Loop settings
			$action_config['loop_open']      = '<table cellpadding="5">';
			$action_config['loop']           = $settings[ $prefix . '_email_loop' ] ?? '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>';
			$action_config['loop_close']     = '</table>';
			$action_config['exclude_empty']  = ( ! empty( $settings[ $prefix . '_exclude_empty' ] ) && $settings[ $prefix . '_exclude_empty' ] === 'true' );
			$action_config['rtl']            = ( ! empty( $settings[ $prefix . '_rtl' ] ) && $settings[ $prefix . '_rtl' ] === 'true' );

			// Schedule configuration
			$schedule_config = array(
				'base_date'   => $settings[ $prefix . '_base_date' ] ?? '',
				'date_offset' => $settings[ $prefix . '_date_offset' ] ?? '0',
				'time_method' => $settings[ $prefix . '_time_method' ] ?? 'fixed',
				'time_fixed'  => $settings[ $prefix . '_time_fixed' ] ?? '09:00',
			);
			$action_config['schedule'] = $schedule_config;

			// Use entry.created event for reminders (they fire after entry is saved)
			return self::create_email_trigger(
				$form_id,
				sprintf( __( 'Email Reminder #%d', 'super-forms' ), $index ),
				'entry.created',
				$action_config,
				$schedule_config,
				$enabled
			);
		}

		/**
		 * Build legacy email body from open/body/close parts
		 *
		 * @param array  $settings Form settings
		 * @param string $type     'email' or 'confirm'
		 * @return string Combined body
		 * @since 6.5.0
		 */
		private static function build_legacy_body( $settings, $type ) {
			$body = '';

			$open_key  = $type . '_body_open';
			$body_key  = $type . '_body';
			$close_key = $type . '_body_close';

			if ( ! empty( $settings[ $open_key ] ) ) {
				$body .= $settings[ $open_key ] . "\n\n";
			}

			$body .= $settings[ $body_key ] ?? '';

			if ( ! empty( $settings[ $close_key ] ) ) {
				$body .= "\n\n" . $settings[ $close_key ];
			}

			return $body;
		}

		/**
		 * Build reminder email body
		 *
		 * @param array  $settings Form settings
		 * @param string $prefix   Setting prefix
		 * @return string Combined body
		 * @since 6.5.0
		 */
		private static function build_reminder_body( $settings, $prefix ) {
			$body = '';

			if ( ! empty( $settings[ $prefix . '_body_open' ] ) ) {
				$body .= $settings[ $prefix . '_body_open' ] . "\n\n";
			}

			$body .= $settings[ $prefix . '_body' ] ?? '';

			if ( ! empty( $settings[ $prefix . '_body_close' ] ) ) {
				$body .= "\n\n" . $settings[ $prefix . '_body_close' ];
			}

			return $body;
		}

		/**
		 * Get from email based on type setting
		 *
		 * @param array  $settings Form settings
		 * @param string $prefix   Setting prefix
		 * @return string From email
		 * @since 6.5.0
		 */
		private static function get_from_email( $settings, $prefix ) {
			$type_key = $prefix . '_from_type';
			$from_key = $prefix . '_from';

			// Handle different prefix patterns
			if ( $prefix === 'header' ) {
				$type_key = 'header_from_type';
				$from_key = 'header_from';
			} elseif ( $prefix === 'confirm' ) {
				$type_key = 'confirm_from_type';
				$from_key = 'confirm_from';
			}

			if ( ! empty( $settings[ $type_key ] ) && $settings[ $type_key ] === 'default' ) {
				return '{option_admin_email}';
			}

			return $settings[ $from_key ] ?? '{option_admin_email}';
		}

		/**
		 * Get from name based on type setting
		 *
		 * @param array  $settings Form settings
		 * @param string $prefix   Setting prefix
		 * @return string From name
		 * @since 6.5.0
		 */
		private static function get_from_name( $settings, $prefix ) {
			$type_key = $prefix . '_from_type';
			$name_key = $prefix . '_from_name';

			// Handle different prefix patterns
			if ( $prefix === 'header' ) {
				$type_key = 'header_from_type';
				$name_key = 'header_from_name';
			} elseif ( $prefix === 'confirm' ) {
				$type_key = 'confirm_from_type';
				$name_key = 'confirm_from_name';
			}

			if ( ! empty( $settings[ $type_key ] ) && $settings[ $type_key ] === 'default' ) {
				return '{option_blogname}';
			}

			return $settings[ $name_key ] ?? '{option_blogname}';
		}

		/**
		 * Create trigger and action for email
		 *
		 * @param int    $form_id       Form ID
		 * @param string $name          Trigger name
		 * @param string $event_id      Event ID
		 * @param array  $action_config Action configuration
		 * @param array  $schedule      Optional schedule config for delayed emails
		 * @return array|WP_Error Result
		 * @since 6.5.0
		 */
		private static function create_email_trigger( $form_id, $name, $event_id, $action_config, $schedule = null, $enabled = true ) {
			// Create trigger
			$trigger_id = SUPER_Trigger_DAL::create_trigger( array(
				'trigger_name'    => $name,
				'event_id'        => $event_id,
				'scope'           => 'form',
				'scope_id'        => $form_id,
				'conditions'      => null,
				'enabled'         => $enabled ? 1 : 0,
				'execution_order' => 10,
			) );

			if ( is_wp_error( $trigger_id ) ) {
				return $trigger_id;
			}

			// If scheduled, add delay action first
			if ( ! empty( $schedule ) && ! empty( $schedule['base_date'] ) ) {
				$delay_config = array(
					'delay_type'  => 'relative_to_field',
					'delay_field' => $schedule['base_date'],
					'delay_days'  => absint( $schedule['date_offset'] ?? 0 ),
					'time_method' => $schedule['time_method'] ?? 'fixed',
					'time_value'  => $schedule['time_fixed'] ?? '09:00',
				);

				SUPER_Trigger_DAL::create_action( $trigger_id, array(
					'action_type'     => 'delay_execution',
					'action_config'   => $delay_config,
					'execution_order' => 5,
					'enabled'         => 1,
				) );
			}

			// Create send_email action
			$action_id = SUPER_Trigger_DAL::create_action( $trigger_id, array(
				'action_type'     => 'send_email',
				'action_config'   => $action_config,
				'execution_order' => 10,
				'enabled'         => 1,
			) );

			if ( is_wp_error( $action_id ) ) {
				// Rollback trigger
				SUPER_Trigger_DAL::delete_trigger( $trigger_id );
				return $action_id;
			}

			return array(
				'trigger_id' => $trigger_id,
				'action_id'  => $action_id,
			);
		}

		/**
		 * Migration complete callback
		 *
		 * @since 6.5.0
		 */
		public static function migration_complete() {
			$state = self::get_state();

			self::update_state( array(
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql' ),
			) );

			// Log completion
			if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
				SUPER_Trigger_Logger::instance()->info(
					sprintf(
						'Email migration complete: %d forms, %d emails migrated',
						$state['forms_migrated'],
						$state['emails_migrated']
					)
				);
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'[Super Forms] Email migration complete: %d forms, %d emails migrated',
					$state['forms_migrated'],
					$state['emails_migrated']
				) );
			}
		}

		/**
		 * Get trigger ID for a specific email type
		 *
		 * @param int    $form_id    Form ID
		 * @param string $email_type Email type ('admin', 'confirmation', 'reminder_1', etc.)
		 * @return int|null Trigger ID or null
		 * @since 6.5.0
		 */
		public static function get_trigger_for_email( $form_id, $email_type ) {
			$trigger_map = get_post_meta( $form_id, self::EMAIL_TRIGGER_MAP, true );

			if ( is_array( $trigger_map ) && isset( $trigger_map[ $email_type ] ) ) {
				return absint( $trigger_map[ $email_type ] );
			}

			return null;
		}

		/**
		 * Check if a specific form has been migrated
		 *
		 * Used for per-form lazy migration on import/restore/edge cases.
		 * Background migration remains the primary path for existing forms.
		 *
		 * @param int $form_id Form ID
		 * @return bool True if form has been migrated
		 * @since 6.5.0
		 */
		public static function is_form_migrated( $form_id ) {
			// Check if this form has a trigger map (empty array counts as migrated - no emails to migrate)
			$trigger_map = get_post_meta( $form_id, self::EMAIL_TRIGGER_MAP, true );
			return $trigger_map !== '' && $trigger_map !== false;
		}

		/**
		 * Ensure a form is migrated (lazy migration for imports/restores/edge cases)
		 *
		 * This is a lightweight wrapper that only migrates if needed.
		 * Background migration via Action Scheduler remains the primary path.
		 *
		 * @param int $form_id Form ID
		 * @return bool True if migration ran, false if already migrated
		 * @since 6.5.0
		 */
		public static function ensure_form_migrated( $form_id ) {
			if ( self::is_form_migrated( $form_id ) ) {
				return false;
			}

			// Migrate this form now
			self::migrate_form( $form_id );
			return true;
		}

		// ─────────────────────────────────────────────────────────
		// BIDIRECTIONAL SYNC: Email v2 ↔ Triggers
		// ─────────────────────────────────────────────────────────

		/**
		 * Sync emails from Email v2 UI to trigger system
		 *
		 * Called by SUPER_Common::save_form_emails_settings() when Email v2 saves.
		 * Creates/updates triggers for each email in the _emails array.
		 *
		 * Mapping Strategy:
		 * - email.id → trigger mapping stored in EMAIL_TRIGGER_MAP postmeta
		 * - If email has existing trigger, update it
		 * - If email is new (no mapping), create trigger
		 * - If email was deleted, delete the trigger
		 *
		 * @param int   $form_id Form ID
		 * @param array $emails  Email v2 data array
		 * @return array Updated trigger mapping
		 * @since 6.5.0
		 */
		public static function sync_emails_to_triggers( $form_id, $emails ) {
			if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) {
				return array();
			}

			$form_id = absint( $form_id );
			if ( empty( $form_id ) ) {
				return array();
			}

			// Get existing trigger mapping
			$trigger_map = get_post_meta( $form_id, self::EMAIL_TRIGGER_MAP, true );
			if ( ! is_array( $trigger_map ) ) {
				$trigger_map = array();
			}

			// Normalize emails to array
			if ( ! is_array( $emails ) ) {
				$emails = array();
			}

			// Build a map of current email IDs for deletion detection
			$current_email_ids = array();
			foreach ( $emails as $email ) {
				if ( ! empty( $email['id'] ) ) {
					$current_email_ids[] = $email['id'];
				}
			}

			// Delete triggers for removed emails
			foreach ( $trigger_map as $email_key => $trigger_id ) {
				// Check if this email still exists
				$email_id = $email_key; // email.id or legacy key (admin, confirmation, etc.)

				// Legacy keys don't have corresponding Email v2 entries, skip them
				if ( in_array( $email_key, array( 'admin', 'confirmation', 'reminder_1', 'reminder_2', 'reminder_3' ), true ) ) {
					continue;
				}

				if ( ! in_array( $email_id, $current_email_ids, true ) ) {
					// Email was deleted, remove trigger
					SUPER_Trigger_DAL::delete_trigger( $trigger_id );
					unset( $trigger_map[ $email_key ] );
				}
			}

			// Create/update triggers for each email
			foreach ( $emails as $email ) {
				if ( empty( $email['id'] ) ) {
					continue;
				}

				$email_id = $email['id'];

				// Convert Email v2 format to trigger action config
				$action_config = self::email_to_action_config( $email );

				// Check if trigger exists for this email
				if ( isset( $trigger_map[ $email_id ] ) ) {
					// Update existing trigger
					$trigger_id = $trigger_map[ $email_id ];

					// Update trigger settings
					SUPER_Trigger_DAL::update_trigger( $trigger_id, array(
						'trigger_name' => ! empty( $email['description'] ) ? $email['description'] : 'Email',
						'enabled'      => ! empty( $email['enabled'] ) ? 1 : 0,
					) );

					// Update action config - get the first send_email action
					$actions = SUPER_Trigger_DAL::get_actions( $trigger_id, false );
					foreach ( $actions as $action ) {
						if ( $action['action_type'] === 'send_email' ) {
							SUPER_Trigger_DAL::update_action( $action['id'], array(
								'action_config' => $action_config,
							) );
							break;
						}
					}
				} else {
					// Create new trigger
					$result = self::create_email_trigger(
						$form_id,
						! empty( $email['description'] ) ? $email['description'] : 'Email',
						'form.submitted', // Default event for Email v2
						$action_config,
						null, // No schedule for now
						! empty( $email['enabled'] )
					);

					if ( ! is_wp_error( $result ) ) {
						$trigger_map[ $email_id ] = $result['trigger_id'];
					}
				}
			}

			// Save updated mapping
			update_post_meta( $form_id, self::EMAIL_TRIGGER_MAP, $trigger_map );

			return $trigger_map;
		}

		/**
		 * Convert Email v2 format to trigger action config
		 *
		 * @param array $email Email v2 data
		 * @return array Action config for send_email action
		 * @since 6.5.0
		 */
		private static function email_to_action_config( $email ) {
			$config = array(
				'to'          => $email['to'] ?? '{email}',
				'subject'     => $email['subject'] ?? '',
				'body'        => $email['body'] ?? '',
				'body_type'   => 'email_v2', // Mark as Email v2 format
				'from'        => $email['from_email'] ?? '{option_admin_email}',
				'from_name'   => $email['from_name'] ?? '{option_blogname}',
				'cc'          => $email['cc'] ?? '',
				'bcc'         => $email['bcc'] ?? '',
				'attachments' => $email['attachments'] ?? array(),
			);

			// Reply-to
			if ( ! empty( $email['reply_to']['enabled'] ) ) {
				$config['reply_to']      = $email['reply_to']['email'] ?? '';
				$config['reply_to_name'] = $email['reply_to']['name'] ?? '';
			}

			// Template
			if ( ! empty( $email['template'] ) ) {
				$config['template'] = $email['template'];
			}

			// Conditions (for per-email conditions in Email v2)
			if ( ! empty( $email['conditions']['enabled'] ) ) {
				$config['conditions'] = $email['conditions'];
			}

			// Schedule
			if ( ! empty( $email['schedule']['enabled'] ) ) {
				$config['schedule'] = $email['schedule'];
			}

			return $config;
		}

		/**
		 * Convert triggers back to Email v2 format for UI display
		 *
		 * Called when Email v2 UI loads to populate from migrated triggers.
		 * This enables the Email v2 UI to show emails that were migrated
		 * from legacy settings or created in the Triggers tab.
		 *
		 * @param int $form_id Form ID
		 * @return array Emails in Email v2 format
		 * @since 6.5.0
		 */
		public static function convert_triggers_to_emails_format( $form_id ) {
			if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) {
				return array();
			}

			$form_id = absint( $form_id );
			if ( empty( $form_id ) ) {
				return array();
			}

			// Get trigger mapping
			$trigger_map = get_post_meta( $form_id, self::EMAIL_TRIGGER_MAP, true );
			if ( ! is_array( $trigger_map ) || empty( $trigger_map ) ) {
				return array();
			}

			$emails = array();

			foreach ( $trigger_map as $email_key => $trigger_id ) {
				$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
				if ( is_wp_error( $trigger ) ) {
					continue;
				}

				// Get actions for this trigger
				$actions = SUPER_Trigger_DAL::get_actions( $trigger_id, false );

				// Find the send_email action
				$email_action = null;
				foreach ( $actions as $action ) {
					if ( $action['action_type'] === 'send_email' ) {
						$email_action = $action;
						break;
					}
				}

				if ( ! $email_action ) {
					continue;
				}

				$config = $email_action['action_config'];
				if ( ! is_array( $config ) ) {
					$config = array();
				}

				// Convert to Email v2 format
				// Set body_type to 'html' for migrated emails - this tells the UI
				// to show a raw HTML editor instead of the visual canvas builder
				$email = array(
					'id'          => $email_key,
					'enabled'     => ! empty( $trigger['enabled'] ),
					'description' => $trigger['trigger_name'] ?? 'Email',
					'to'          => $config['to'] ?? '{email}',
					'from_email'  => $config['from'] ?? '',
					'from_name'   => $config['from_name'] ?? '{option_blogname}',
					'subject'     => $config['subject'] ?? '',
					'body'        => $config['body'] ?? '',
					'body_type'   => 'html', // Migrated emails use raw HTML mode
					'attachments' => $config['attachments'] ?? array(),
					'reply_to'    => array(
						'enabled' => ! empty( $config['reply_to'] ),
						'email'   => $config['reply_to'] ?? '',
						'name'    => $config['reply_to_name'] ?? '',
					),
					'cc'          => $config['cc'] ?? '',
					'bcc'         => $config['bcc'] ?? '',
					'template'    => $config['template'] ?? array( 'slug' => 'none' ),
					'conditions'  => $config['conditions'] ?? array(
						'enabled' => false,
						'f1'      => '',
						'logic'   => '==',
						'f2'      => '',
					),
					'schedule'    => $config['schedule'] ?? array(
						'enabled'   => false,
						'schedules' => array(),
					),
				);

				$emails[] = $email;
			}

			return $emails;
		}

		/**
		 * Get or populate Email v2 data
		 *
		 * This is the main entry point for Email v2 UI to get email data.
		 * It checks _emails first, and if empty, populates from triggers.
		 *
		 * @param int $form_id Form ID
		 * @return array Emails in Email v2 format
		 * @since 6.5.0
		 */
		public static function get_emails_for_ui( $form_id ) {
			$form_id = absint( $form_id );
			if ( empty( $form_id ) ) {
				return array();
			}

			// First check if _emails has data
			$emails = get_post_meta( $form_id, '_emails', true );
			if ( is_array( $emails ) && ! empty( $emails ) ) {
				return $emails;
			}

			// If _emails is empty, try to populate from triggers
			$emails = self::convert_triggers_to_emails_format( $form_id );

			// If we got emails from triggers, save them to _emails for future loads
			if ( ! empty( $emails ) ) {
				update_post_meta( $form_id, '_emails', $emails );
			}

			return $emails;
		}

	}

endif;

// Initialize
SUPER_Email_Trigger_Migration::init();
