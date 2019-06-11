<?php

/**
 * Utility class for sesion utilities
 *
 * THIS CLASS SHOULD NEVER BE INSTANTIATED
 */
class SUPER_WP_Session_Utils {
	/**
	 * Count the total sessions in the database.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int
	 */
	public static function count_sessions() {
		global $wpdb;

		$query = "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_super_session_expires_%'";

		/**
		 * Filter the query in case tables are non-standard.
		 *
		 * @param string $query Database count query
		 */
		$query = apply_filters( 'super_session_count_query', $query );

		$sessions = $wpdb->get_var( $query );

		return absint( $sessions );
	}

	/**
	 * Create a new, random session in the database.
	 *
	 * @param null|string $date
	 */
	public static function create_dummy_session( $date = null ) {
		// Generate our date
		if ( null !== $date ) {
			$time = strtotime( $date );

			if ( false === $time ) {
				$date = null;
			} else {
				$expires = date( 'U', strtotime( $date ) );
			}
		}

		// If null was passed, or if the string parsing failed, fall back on a default
		if ( null === $date ) {
			/**
			 * Filter the expiration of the session in the database
			 *
			 * @param int
			 */
			$expires = time() + (int) apply_filters( 'super_session_expiration', 30 * 60 );
		}

		$session_id = self::generate_id();

		// Store the session
		add_option( "_super_session_{$session_id}", array(), '', 'no' );
		add_option( "_super_session_expires_{$session_id}", $expires, '', 'no' );
	}

	/**
	 * Delete old sessions from the database.
	 *
	 * @param int $limit Maximum number of sessions to delete.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int Sessions deleted.
	 */
	public static function delete_old_sessions( $limit = 1000 ) {
		global $wpdb;

		$limit = absint( $limit );
		$keys = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_super_session_expires_%' ORDER BY option_value ASC LIMIT 0, {$limit}" );

		$now = time();
		$expired = array();
		$count = 0;

		foreach( $keys as $expiration ) {
			$key = $expiration->option_name;
			$expires = $expiration->option_value;

			if ( $now > $expires ) {
				$session_id = preg_replace("/[^A-Za-z0-9_]/", '', substr( $key, 23 ) );

				$expired[] = $key;
				$expired[] = "_super_session_{$session_id}";

				$count += 1;
			}
		}

		// Delete expired sessions
		if ( ! empty( $expired ) ) {
		    $placeholders = array_fill( 0, count( $expired ), '%s' );
		    $format = implode( ', ', $placeholders );
		    $query = "DELETE FROM $wpdb->options WHERE option_name IN ($format)";
			$wpdb->query( $wpdb->prepare( $query, $expired ) );
		}

        // @since 3.2.4 - Delete sessions that no longer have an expired sessions in option table
        $keys = $wpdb->get_results( "
        SELECT option_name, option_value
        FROM $wpdb->options
        WHERE option_name LIKE '_super_session_%' AND option_name NOT LIKE '_super_session_expires_%'
        ORDER BY option_value ASC
        LIMIT 0, {$limit}" );
        foreach( $keys as $v ) {
            if (strpos($v->option_name, '_super_session_expires_') === false) {
                $name = str_replace('_super_session_', '_super_session_expires_', $v->option_name);
                if( get_option($name, false) === false) {
                    delete_option($v->option_name);
                }
            }
        }
                
		return $count;
	}

	/**
	 * Remove all sessions from the database, regardless of expiration.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int Sessions deleted
	 */
	public static function delete_all_sessions() {
		global $wpdb;

		$count = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_super_session_%'" );

		return (int) ( $count / 2 );
	}

	/**
	 * Generate a new, random session ID.
	 *
	 * @return string
	 */
	public static function generate_id() {
		require_once( ABSPATH . 'wp-includes/class-phpass.php' );
		$hash = new PasswordHash( 8, false );

		return md5( $hash->get_random_bytes( 32 ) );
	}
} 