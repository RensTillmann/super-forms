<?php
/**
 * Super Forms Update Checker
 *
 * Lightweight custom update system for Super Forms.
 * Hooks into WordPress core update system to check f4d.nl server for new versions.
 *
 * WordPress handles all UI, notifications, downloads, and installation automatically.
 * This class simply provides version information from our update server.
 *
 * @since 6.4.127
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class SUPER_Update_Checker {

	/**
	 * Plugin slug (directory name)
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin basename (super-forms/super-forms.php)
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Update server URL
	 * @var string
	 */
	private $update_url;

	/**
	 * Current installed version
	 * @var string
	 */
	private $version;

	/**
	 * Initialize update checker
	 *
	 * @param string $plugin_file Full path to main plugin file (__FILE__)
	 * @param string $update_url  URL to check for updates (f4d.nl endpoint)
	 * @param string $version     Current plugin version
	 */
	public function __construct( $plugin_file, $update_url, $version ) {
		$this->plugin_file = plugin_basename( $plugin_file );
		$this->plugin_slug = dirname( $this->plugin_file );
		$this->update_url  = $update_url;
		$this->version     = $version;

		// Hook into WordPress core update system
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );

		// Clear cache when user manually checks for updates
		add_action( 'load-update-core.php', array( $this, 'clear_cache' ) );
	}

	/**
	 * Check for updates
	 *
	 * Hooked into WordPress plugin update checker.
	 * Runs every 12 hours when WordPress automatically checks for plugin updates.
	 * Also runs when user clicks "Check Again" on Dashboard → Updates.
	 *
	 * @param object $transient WordPress plugin update transient
	 * @return object Modified transient with Super Forms update info (if available)
	 */
	public function check_update( $transient ) {
		// WordPress hasn't checked yet
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get update info from our server
		$remote = $this->request();

		// No response or error from server
		if ( ! $remote || ! isset( $remote->version ) ) {
			return $transient;
		}

		// Compare versions: Is server version newer than installed version?
		if ( version_compare( $this->version, $remote->version, '<' ) ) {
			// Build update object in WordPress format
			$res = new stdClass();
			$res->slug        = $this->plugin_slug;
			$res->plugin      = $this->plugin_file;
			$res->new_version = $remote->version;
			$res->tested      = isset( $remote->tested ) ? $remote->tested : '';
			$res->package     = $remote->download_url;

			// Inject into WordPress update queue
			// This triggers "Update available" notification in admin
			$transient->response[ $this->plugin_file ] = $res;
		}

		return $transient;
	}

	/**
	 * Plugin information for "View details" modal
	 *
	 * Shown when user clicks "View version X.Y.Z details" link in plugin row.
	 * Provides changelog, description, and installation instructions.
	 *
	 * @param false|object|array $res    The result object or array
	 * @param string             $action The type of information being requested (plugin_information)
	 * @param object             $args   Plugin API arguments
	 * @return false|object Modified result with Super Forms details
	 */
	public function plugin_info( $res, $action, $args ) {
		// Not a plugin information request
		if ( $action !== 'plugin_information' ) {
			return $res;
		}

		// Not requesting info about Super Forms
		if ( $args->slug !== $this->plugin_slug ) {
			return $res;
		}

		// Get update info from our server
		$remote = $this->request();

		if ( ! $remote ) {
			return $res;
		}

		// Build plugin info object in WordPress format
		$res = new stdClass();
		$res->name          = isset( $remote->name ) ? $remote->name : 'Super Forms';
		$res->slug          = $this->plugin_slug;
		$res->version       = isset( $remote->version ) ? $remote->version : $this->version;
		$res->tested        = isset( $remote->tested ) ? $remote->tested : '';
		$res->requires      = isset( $remote->requires ) ? $remote->requires : '5.8';
		$res->download_link = isset( $remote->download_url ) ? $remote->download_url : '';

		// Sections shown in modal (description, installation, changelog)
		$res->sections = array(
			'description'  => isset( $remote->sections->description ) ? $remote->sections->description : '',
			'installation' => isset( $remote->sections->installation ) ? $remote->sections->installation : '',
			'changelog'    => isset( $remote->sections->changelog ) ? $remote->sections->changelog : '',
		);

		return $res;
	}

	/**
	 * Request update information from f4d.nl server
	 *
	 * Cached for 12 hours to prevent excessive server requests.
	 * Cache automatically cleared when user manually checks for updates.
	 *
	 * @return object|false Update info object or false on error
	 */
	private function request() {
		// Check cache first (12 hour transient)
		$cache_key = 'superforms_update_check';
		$remote = get_transient( $cache_key );

		if ( $remote !== false ) {
			return $remote;
		}

		// Request update info from f4d.nl server
		$remote = wp_remote_get(
			$this->update_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Request failed (network error, timeout, etc.)
		if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) ) {
			// Cache failure for 1 hour to avoid hammering server
			set_transient( $cache_key, false, HOUR_IN_SECONDS );
			return false;
		}

		// Parse JSON response from server
		$remote = json_decode( wp_remote_retrieve_body( $remote ) );

		if ( ! is_object( $remote ) ) {
			// Invalid JSON response
			set_transient( $cache_key, false, HOUR_IN_SECONDS );
			return false;
		}

		// Cache valid response for 12 hours
		set_transient( $cache_key, $remote, 12 * HOUR_IN_SECONDS );

		return $remote;
	}

	/**
	 * Clear update cache
	 *
	 * Forces fresh check when user visits Dashboard → Updates page.
	 * Ensures "Check Again" button gets latest info from server.
	 *
	 * @since 6.4.127
	 */
	public function clear_cache() {
		delete_transient( 'superforms_update_check' );
	}
}
