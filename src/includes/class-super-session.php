<?php
/**
 * Super Forms Session Class.
 *
 * @author      feeling4design
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Session
 * @version     1.0.0
 * @since       3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Session' ) ) :

/**
 * SUPER_Session Class
 */
class SUPER_Session {

	/**
	 * Holds our session data
	 *
	 * @var array
	 * @access private
	 */
	private $session;


	/**
	 * Session index prefix
	 *
	 * @var string
	 * @access private
	 */
	private $prefix = '';


	/**
	 * Get things started
	 *
	 * Defines our SUPER_WP_Session constants, includes the necessary libraries and
	 * retrieves the WP Session instance
	 *
	 */
	public function __construct() {
		if( !$this->should_start_session() ) {
			return;
		}
		
		// let users change the session cookie name
		if( ! defined( 'SUPER_SESSION_COOKIE' ) ) define( 'SUPER_SESSION_COOKIE', 'super_session' );
		if ( ! class_exists( 'Recursive_ArrayAccess' ) ) include 'sessions/class-recursive-arrayaccess.php';
		
		// Include utilities class
		if ( ! class_exists( 'SUPER_WP_Session_Utils' ) ) include 'sessions/class-wp-session-utils.php';
		
		// Include WP_CLI routines early
		if ( defined( 'WP_CLI' ) && WP_CLI ) include 'sessions/wp-cli.php';
		
		// Only include the functionality if it's not pre-defined.
		if ( ! class_exists( 'SUPER_WP_Session' ) ) {
			include 'sessions/class-wp-session.php';
			include 'sessions/wp-session.php';
		}

		add_filter( 'super_session_expiration_variant', array( $this, 'set_expiration_variant_time' ), 99999 );
		add_filter( 'super_session_expiration', array( $this, 'set_expiration_time' ), 99999 );
		
		if ( empty($this->session) ) {
			add_action( 'plugins_loaded', array( $this, 'init' ), -1 );
		} else {
			add_action( 'init', array( $this, 'init' ), -1 );
		}
	}


	/**
	 * Setup the SUPER_WP_Session instance
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		$this->session = SUPER_WP_Session::get_instance();
		return $this->session;
	}


	/**
	 * Retrieve session ID
	 *
	 * @access public
	 * @return string Session ID
	 */
	public function get_id() {
		return $this->session->session_id;
	}


	/**
	 * Retrieve a session variable
	 *
	 * @access public
	 * @param string $key Session key
	 * @return mixed Session variable
	 */
	public function get( $key ) {
		$key    = sanitize_key( $key );
		$return = false;
		if ( isset( $this->session[ $key ] ) && ! empty( $this->session[ $key ] ) ) {
			preg_match( '/[oO]\s*:\s*\d+\s*:\s*"\s*(?!(?i)(stdClass))/', $this->session[ $key ], $matches );
			if ( ! empty( $matches ) ) {
				$this->set( $key, null );
				return false;
			}
			if ( is_numeric( $this->session[ $key ] ) ) {
				$return = $this->session[ $key ];
			} else {
				$maybe_json = json_decode( $this->session[ $key ] );
				// Since json_last_error is PHP 5.3+, we have to rely on a `null` value for failing to parse JSON.
				if ( is_null( $maybe_json ) ) {
					$is_serialized = is_serialized( $this->session[ $key ] );
					if ( $is_serialized ) {
						$value = unserialize( $this->session[ $key ] );
						$this->set( $key, (array) $value );
						$return = $value;
					} else {
						$return = $this->session[ $key ];
					}
				} else {
					$return = json_decode( $this->session[ $key ], true );
				}
			}
		}
		return $return;
	}


	/**
	 * Set a session variable
	 *
	 * @param string $key Session key
	 * @param int|string|array $value Session variable
	 * @return mixed Session variable
	 */
	public function set( $key, $value ) {
		$key = sanitize_key( $key );
		if ( is_array( $value ) ) {
			$this->session[ $key ] = wp_json_encode( $value );
		} else {
			$this->session[ $key ] = esc_attr( $value );
		}
		return $this->session[ $key ];
	}


	/**
	 * Force the cookie expiration variant time to 23 hours
	 *
	 * @access public
	 * @param int $exp Default expiration (1 hour)
	 * @return int
	 */
	public function set_expiration_variant_time( $exp ) {
		// Example to return 23 hour expiration time: 30 * 60 * 23
		return ( 24 * 60 ); // 30 min.
	}


	/**
	 * Force the cookie expiration time to 24 hours
	 *
	 * @access public
	 * @param int $exp Default expiration (1 hour)
	 * @return int Cookie expiration time
	 */
	public function set_expiration_time( $exp ) {
		// Example to return 24 hour expiration time: 30 * 60 * 24
		return ( 30 * 60 ); // 30 min.
	}


	/**
	 * Determines if we should start sessions
	 *
	 * @return bool
	 */
	public function should_start_session() {
		$start_session = true;
		if( ! empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
			$blacklist = $this->get_blacklist();
			$uri = ltrim( $_SERVER[ 'REQUEST_URI' ], '/' );
			$uri = untrailingslashit( $uri );
			if( in_array( $uri, $blacklist ) ) {
				$start_session = false;
			}
			if( false !== strpos( $uri, 'feed=' ) ) {
				$start_session = false;
			}
		}
		return apply_filters( 'super_start_session', $start_session );
	}


	/**
	 * Retrieve the URI blacklist
	 * These are the URIs where we never start sessions
	 *
	 * @return array
	 */
	public function get_blacklist() {
		$blacklist = apply_filters( 'super_session_start_uri_blacklist', array(
			'feed',
			'feed/rss',
			'feed/rss2',
			'feed/rdf',
			'feed/atom',
			'comments/feed'
		) );
		// Look to see if WordPress is in a sub folder or this is a network site that uses sub folders
		$folder = str_replace( network_home_url(), '', get_site_url() );
		if( ! empty( $folder ) ) {
			foreach( $blacklist as $path ) {
				$blacklist[] = $folder . '/' . $path;
			}
		}
		return $blacklist;
	}

}
endif;