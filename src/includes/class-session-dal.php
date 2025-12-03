<?php
/**
 * Session Data Access Layer
 *
 * Handles all database operations for progressive form sessions.
 * Sessions track form fills and enable auto-save recovery.
 *
 * Session Status Values:
 * - draft      : Session created, form being filled
 * - submitting : Form submission in progress
 * - completed  : Form successfully submitted
 * - aborted    : Submission blocked (spam/duplicate)
 * - abandoned  : No activity for 30+ minutes
 * - expired    : Session expired (24 hours)
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SUPER_Session_DAL' ) ) :

	/**
	 * SUPER_Session_DAL Class
	 */
	class SUPER_Session_DAL {

		/**
		 * Valid session statuses
		 *
		 * @var array
		 */
		const VALID_STATUSES = array(
			'draft',
			'submitting',
			'completed',
			'aborted',
			'abandoned',
			'expired',
		);

		/**
		 * Get table name
		 *
		 * @return string Table name with prefix
		 */
		private static function get_table() {
			global $wpdb;
			return $wpdb->prefix . 'superforms_sessions';
		}

		/**
		 * Create a new session
		 *
		 * @param array $data Session data.
		 * @return int|WP_Error Session ID or error.
		 */
		public static function create( $data ) {
			global $wpdb;
			$table = self::get_table();

			// Validate required fields
			if ( empty( $data['form_id'] ) ) {
				return new WP_Error( 'missing_form_id', __( 'Form ID is required', 'super-forms' ) );
			}

			// Generate unique session key if not provided
			$session_key = isset( $data['session_key'] )
				? sanitize_text_field( $data['session_key'] )
				: wp_generate_password( 32, false );

			// Validate status if provided
			$status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'draft';
			if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
				$status = 'draft';
			}

			$insert_data = array(
				'session_key'   => $session_key,
				'form_id'       => absint( $data['form_id'] ),
				'user_id'       => isset( $data['user_id'] ) && $data['user_id'] ? absint( $data['user_id'] ) : null,
				'client_token'  => isset( $data['client_token'] ) ? sanitize_text_field( $data['client_token'] ) : null,
				'user_ip'       => isset( $data['user_ip'] ) ? sanitize_text_field( $data['user_ip'] ) : null,
				'status'        => $status,
				'form_data'     => isset( $data['form_data'] ) ? wp_json_encode( $data['form_data'] ) : '{}',
				'metadata'      => isset( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : '{}',
				'started_at'    => current_time( 'mysql' ),
				'last_saved_at' => current_time( 'mysql' ),
				'expires_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '+24 hours' ) ),
			);

			// Handle nullable fields for wpdb
			$format = array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
			if ( $insert_data['user_id'] === null ) {
				$insert_data['user_id'] = null;
				$format[2] = null; // wpdb will handle NULL correctly when format is null
			}

			// Use wpdb::insert with explicit NULL handling
			$columns = array_keys( $insert_data );
			$values = array();
			$placeholders = array();

			foreach ( $insert_data as $key => $value ) {
				if ( $value === null ) {
					$placeholders[] = 'NULL';
				} else {
					$placeholders[] = is_int( $value ) ? '%d' : '%s';
					$values[] = $value;
				}
			}

			$sql = sprintf(
				'INSERT INTO %s (%s) VALUES (%s)',
				$table,
				implode( ', ', $columns ),
				implode( ', ', $placeholders )
			);

			if ( ! empty( $values ) ) {
				$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$result = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( $result === false ) {
				return new WP_Error( 'db_insert_failed', $wpdb->last_error );
			}

			return $wpdb->insert_id;
		}

		/**
		 * Get session by ID
		 *
		 * @param int $id Session ID.
		 * @return array|null Session data or null.
		 */
		public static function get( $id ) {
			global $wpdb;
			$table = self::get_table();

			$session = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);

			return self::decode_session( $session );
		}

		/**
		 * Get session by session key
		 *
		 * @param string $session_key Unique session key.
		 * @return array|null Session data or null.
		 */
		public static function get_by_key( $session_key ) {
			global $wpdb;
			$table = self::get_table();

			$session = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE session_key = %s", $session_key ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);

			return self::decode_session( $session );
		}

		/**
		 * Update session by ID
		 *
		 * @param int   $id   Session ID.
		 * @param array $data Data to update.
		 * @return bool|WP_Error True on success or error.
		 */
		public static function update( $id, $data ) {
			global $wpdb;
			$table = self::get_table();

			$update_data = self::prepare_update_data( $data );

			if ( empty( $update_data ) ) {
				return new WP_Error( 'no_update_data', __( 'No valid data to update', 'super-forms' ) );
			}

			$result = $wpdb->update( $table, $update_data, array( 'id' => absint( $id ) ) );

			if ( $result === false ) {
				return new WP_Error( 'db_update_failed', $wpdb->last_error );
			}

			return true;
		}

		/**
		 * Update session by session key
		 *
		 * @param string $session_key Session key.
		 * @param array  $data        Data to update.
		 * @return bool|WP_Error True on success or error.
		 */
		public static function update_by_key( $session_key, $data ) {
			global $wpdb;
			$table = self::get_table();

			$update_data = self::prepare_update_data( $data );

			if ( empty( $update_data ) ) {
				return new WP_Error( 'no_update_data', __( 'No valid data to update', 'super-forms' ) );
			}

			$result = $wpdb->update( $table, $update_data, array( 'session_key' => sanitize_text_field( $session_key ) ) );

			if ( $result === false ) {
				return new WP_Error( 'db_update_failed', $wpdb->last_error );
			}

			return true;
		}

		/**
		 * Delete session by ID
		 *
		 * @param int $id Session ID.
		 * @return bool True on success.
		 */
		public static function delete( $id ) {
			global $wpdb;
			$table = self::get_table();

			return $wpdb->delete( $table, array( 'id' => absint( $id ) ) ) !== false;
		}

		/**
		 * Delete session by session key
		 *
		 * @param string $session_key Session key.
		 * @return bool True on success.
		 */
		public static function delete_by_key( $session_key ) {
			global $wpdb;
			$table = self::get_table();

			return $wpdb->delete( $table, array( 'session_key' => sanitize_text_field( $session_key ) ) ) !== false;
		}

		/**
		 * Find existing recoverable session for user/form
		 *
		 * Searches for the most recent draft or abandoned session that hasn't expired.
		 * For logged-in users, matches by user_id. For guests, matches by client_token.
		 *
		 * @param int         $form_id      Form ID.
		 * @param int|null    $user_id      User ID (null for guests).
		 * @param string|null $client_token Client token from localStorage (for guest sessions).
		 * @return array|null Most recent recoverable session or null.
		 */
		public static function find_recoverable( $form_id, $user_id = null, $client_token = null ) {
			global $wpdb;
			$table = self::get_table();

			// Build query based on user/guest
			if ( $user_id ) {
				$session = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$table}
						WHERE form_id = %d
						AND user_id = %d
						AND status IN ('draft', 'abandoned')
						AND expires_at > NOW()
						ORDER BY last_saved_at DESC
						LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$form_id,
						$user_id
					),
					ARRAY_A
				);
			} elseif ( $client_token ) {
				// For anonymous users, match by client_token (localStorage-based, reliable)
				$session = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$table}
						WHERE form_id = %d
						AND client_token = %s
						AND user_id IS NULL
						AND status IN ('draft', 'abandoned')
						AND expires_at > NOW()
						ORDER BY last_saved_at DESC
						LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$form_id,
						$client_token
					),
					ARRAY_A
				);
			} else {
				// No user_id and no client_token - cannot reliably identify session
				return null;
			}

			return self::decode_session( $session );
		}

		/**
		 * Find session by client token and form
		 *
		 * Used for session recovery for anonymous users.
		 * Client token is a UUID stored in localStorage - unique per browser profile.
		 *
		 * @param string $client_token Client token from localStorage.
		 * @param int    $form_id      Form ID.
		 * @return array|null Session data or null.
		 */
		public static function find_by_client_token( $client_token, $form_id ) {
			global $wpdb;
			$table = self::get_table();

			$session = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table}
					WHERE client_token = %s
					AND form_id = %d
					AND status IN ('draft', 'abandoned')
					AND expires_at > NOW()
					ORDER BY last_saved_at DESC
					LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$client_token,
					$form_id
				),
				ARRAY_A
			);

			return self::decode_session( $session );
		}

		/**
		 * Mark session as completed
		 *
		 * @param string   $session_key Session key.
		 * @param int|null $entry_id    Created entry ID (optional).
		 * @return bool True on success.
		 */
		public static function mark_completed( $session_key, $entry_id = null ) {
			global $wpdb;
			$table = self::get_table();

			$update_data = array(
				'status'        => 'completed',
				'completed_at'  => current_time( 'mysql' ),
				'last_saved_at' => current_time( 'mysql' ),
			);

			// Store entry_id in metadata if provided
			if ( $entry_id ) {
				$session = self::get_by_key( $session_key );
				if ( $session ) {
					$metadata = is_array( $session['metadata'] ) ? $session['metadata'] : array();
					$metadata['entry_id'] = absint( $entry_id );
					$update_data['metadata'] = wp_json_encode( $metadata );
				}
			}

			return $wpdb->update( $table, $update_data, array( 'session_key' => $session_key ) ) !== false;
		}

		/**
		 * Mark session as aborted
		 *
		 * @param string $session_key Session key.
		 * @param string $reason      Abort reason (spam_detected, duplicate_detected, etc.).
		 * @return bool True on success.
		 */
		public static function mark_aborted( $session_key, $reason = '' ) {
			global $wpdb;
			$table = self::get_table();

			$session = self::get_by_key( $session_key );
			$metadata = $session && is_array( $session['metadata'] ) ? $session['metadata'] : array();
			$metadata['abort_reason'] = sanitize_text_field( $reason );
			$metadata['aborted_at'] = current_time( 'mysql' );

			return $wpdb->update(
				$table,
				array(
					'status'        => 'aborted',
					'metadata'      => wp_json_encode( $metadata ),
					'last_saved_at' => current_time( 'mysql' ),
				),
				array( 'session_key' => $session_key )
			) !== false;
		}

		/**
		 * Add a message to a session's metadata
		 *
		 * @param string $session_key Session key.
		 * @param string $message     Message to store.
		 * @return bool True on success.
		 */
		public static function add_message( $session_key, $message ) {
			$session = self::get_by_key( $session_key );
			if ( ! $session ) {
				return false;
			}

			$metadata = $session['metadata'] ?: array();
			$metadata['msg'] = $message;

			return self::update_by_key( $session_key, array( 'metadata' => $metadata ) );
		}


		/**
		 * Mark sessions as abandoned (no activity for 30+ minutes)
		 *
		 * @return int Number of sessions marked abandoned.
		 */
		public static function mark_abandoned() {
			global $wpdb;
			$table = self::get_table();

			$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-30 minutes' ) );

			return $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table}
					SET status = 'abandoned'
					WHERE status = 'draft'
					AND last_saved_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$threshold
				)
			);
		}

		/**
		 * Cleanup expired sessions
		 *
		 * @param int $limit Max sessions to cleanup per run.
		 * @return int Number of sessions deleted.
		 */
		public static function cleanup_expired( $limit = 100 ) {
			global $wpdb;
			$table = self::get_table();

			// Get expired session IDs
			$expired_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE expires_at < NOW() LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$limit
				)
			);

			if ( empty( $expired_ids ) ) {
				return 0;
			}

			// Delete expired sessions
			$placeholders = implode( ',', array_fill( 0, count( $expired_ids ), '%d' ) );
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					...$expired_ids
				)
			);

			return count( $expired_ids );
		}

		/**
		 * Cleanup completed sessions older than specified days
		 *
		 * @param int $days Days to retain completed sessions.
		 * @param int $limit Max sessions to cleanup per run.
		 * @return int Number of sessions deleted.
		 */
		public static function cleanup_completed( $days = 7, $limit = 100 ) {
			global $wpdb;
			$table = self::get_table();

			$threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

			// Get old completed session IDs
			$old_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$table}
					WHERE status = 'completed'
					AND completed_at < %s
					LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$threshold,
					$limit
				)
			);

			if ( empty( $old_ids ) ) {
				return 0;
			}

			// Delete old sessions
			$placeholders = implode( ',', array_fill( 0, count( $old_ids ), '%d' ) );
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					...$old_ids
				)
			);

			return count( $old_ids );
		}

		/**
		 * Get session statistics for a form
		 *
		 * @param int $form_id Form ID.
		 * @return array Statistics array with counts by status.
		 */
		public static function get_form_stats( $form_id ) {
			global $wpdb;
			$table = self::get_table();

			$stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) as total,
						SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
						SUM(CASE WHEN status = 'submitting' THEN 1 ELSE 0 END) as submitting,
						SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
						SUM(CASE WHEN status = 'aborted' THEN 1 ELSE 0 END) as aborted,
						SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned,
						SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
					FROM {$table}
					WHERE form_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$form_id
				),
				ARRAY_A
			);

			// Ensure numeric values
			if ( $stats ) {
				foreach ( $stats as $key => $value ) {
					$stats[ $key ] = (int) $value;
				}
			}

			return $stats ?: array(
				'total'      => 0,
				'draft'      => 0,
				'submitting' => 0,
				'completed'  => 0,
				'aborted'    => 0,
				'abandoned'  => 0,
				'expired'    => 0,
			);
		}

		/**
		 * Get global session statistics
		 *
		 * @return array Statistics array with counts by status.
		 */
		public static function get_global_stats() {
			global $wpdb;
			$table = self::get_table();

			$stats = $wpdb->get_row(
				"SELECT
					COUNT(*) as total,
					SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
					SUM(CASE WHEN status = 'submitting' THEN 1 ELSE 0 END) as submitting,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
					SUM(CASE WHEN status = 'aborted' THEN 1 ELSE 0 END) as aborted,
					SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned,
					SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
				FROM {$table}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);

			// Ensure numeric values
			if ( $stats ) {
				foreach ( $stats as $key => $value ) {
					$stats[ $key ] = (int) $value;
				}
			}

			return $stats ?: array(
				'total'      => 0,
				'draft'      => 0,
				'submitting' => 0,
				'completed'  => 0,
				'aborted'    => 0,
				'abandoned'  => 0,
				'expired'    => 0,
			);
		}

		/**
		 * Get active sessions count (draft or submitting)
		 *
		 * @param int|null $form_id Optional form ID to filter.
		 * @return int Number of active sessions.
		 */
		public static function get_active_count( $form_id = null ) {
			global $wpdb;
			$table = self::get_table();

			if ( $form_id ) {
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table}
						WHERE form_id = %d
						AND status IN ('draft', 'submitting')
						AND expires_at > NOW()", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$form_id
					)
				);
			}

			return (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$table}
				WHERE status IN ('draft', 'submitting')
				AND expires_at > NOW()" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}

		/**
		 * Decode session JSON fields
		 *
		 * @param array|null $session Raw session row.
		 * @return array|null Session with decoded JSON fields.
		 */
		private static function decode_session( $session ) {
			if ( ! $session ) {
				return null;
			}

			$session['form_data'] = json_decode( $session['form_data'], true );
			$session['metadata'] = json_decode( $session['metadata'], true );

			// Ensure arrays for empty JSON
			if ( ! is_array( $session['form_data'] ) ) {
				$session['form_data'] = array();
			}
			if ( ! is_array( $session['metadata'] ) ) {
				$session['metadata'] = array();
			}

			return $session;
		}

		/**
		 * Prepare update data array
		 *
		 * @param array $data Raw update data.
		 * @return array Prepared update data.
		 */
		private static function prepare_update_data( $data ) {
			$update_data = array();

			if ( isset( $data['status'] ) ) {
				$status = sanitize_key( $data['status'] );
				if ( in_array( $status, self::VALID_STATUSES, true ) ) {
					$update_data['status'] = $status;
				}
			}

			if ( isset( $data['form_data'] ) ) {
				$update_data['form_data'] = wp_json_encode( $data['form_data'] );
			}

			if ( isset( $data['metadata'] ) ) {
				$update_data['metadata'] = wp_json_encode( $data['metadata'] );
			}

			if ( isset( $data['completed_at'] ) ) {
				$update_data['completed_at'] = $data['completed_at'];
			}

			// Always update last_saved_at and reset expiry on any update
			$update_data['last_saved_at'] = current_time( 'mysql' );
			$update_data['expires_at'] = gmdate( 'Y-m-d H:i:s', strtotime( '+24 hours' ) );

			return $update_data;
		}
		public static function cleanup() {
			self::mark_abandoned();
			self::cleanup_expired();
			self::cleanup_completed();
		}

	}

endif;
