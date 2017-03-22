<?php
class SUPER_WP_AutoUpdate {

	/**
	 * The plugin current version
	 * @var string
	 */
	private $current_version;

	/**
	 * The plugin remote update path
	 * @var string
	 */
	private $update_path;

	/**
	 * Plugin Slug (plugin_directory/plugin_file.php)
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin name (plugin_file)
	 * @var string
	 */
	private $slug;

	/**
	 * Plugin
	 * @var string
	 */
	private $plugin;

	/**
	 * License User
	 * @var string
	 */
	private $license_user;

	/**
	 * License Key 
	 * @var string
	 */
	private $license_key;

	/**
	 * Initialize a new instance of the WordPress Auto-Update class
	 * @param string $current_version
	 * @param string $update_path
	 * @param string $plugin_slug
	 */
	public function __construct( $current_version, $update_path, $plugin_slug, $license_user='', $license_key='', $plugin='super_forms' ) {
		
		$this->plugin = $plugin;

		// Set the class public variables
		$this->current_version = $current_version;
		$this->update_path = $update_path;

		// Set the License
		$this->license_user = $license_user;
		$this->license_key = $license_key;

		// Set the Plugin Slug	
		$this->plugin_slug = $plugin_slug;
		list ($t1, $t2) = explode( '/', $plugin_slug );
		$this->slug = str_replace( '.php', '', $t2 );		
		

		add_action( 'admin_init', array( &$this, 'init' ), 100 );

		// define the alternative API for updating checking
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ), 20 );

		// Define the alternative response for information checking
		add_filter( 'plugins_api', array( &$this, 'check_info' ), 10, 3 );
	}

	public function init() {
		if( !is_admin() ) return;
		if( !current_user_can( 'update_plugins' ) ) return;
		add_filter( 'admin_notices', array( &$this, 'admin_notices' ), 10 );
	}
	
	public function admin_notices() {
		if(!isset($_SESSION['super_forms_update_loop'])) $_SESSION['super_forms_update_loop'] = array();
		$update_plugins = get_site_transient('update_plugins');
		if( isset( $update_plugins->response ) ) {
			foreach( $update_plugins->response as $slug => $data ) {
				if(!in_array($data->slug, $_SESSION['super_forms_update_loop'])){
					$_SESSION['super_forms_update_loop'][$data->slug] = $data->slug;
					$notices = array();
					if( (isset($data->admin_notices)) && (!empty($data->admin_notices)) ) {
						foreach( $data->admin_notices as $version => $notice ) {
							if( version_compare( $version, $data->version, '<=' ) ) continue;
							$notices[] = stripslashes( $notice );
						}
					}else if( !empty( $data->admin_notice ) && version_compare( $data->version, $data->new_version, '<=' ) ) {
						$notices[] = stripslashes( $data->admin_notice );
					}
					if( empty( $notices ) ) continue;
					$nonce = wp_create_nonce( 'upgrade-plugin_' . $data->plugin );
					foreach( $notices as $notice ) {
						echo '<div class="update-nag">';
						echo str_replace(array('%%updateurl%%'), array(admin_url('update.php?action=upgrade-plugin&plugin='.urlencode( $data->plugin ).'&_wpnonce='.$nonce)), $notice.'</div>');
					}
					break;
				}
			}
		}
	}


	/**
	 * Add our self-hosted autoupdate plugin to the filter transient
	 *
	 * @param $transient
	 * @return object $ transient
	 */
	public function check_update( $transient ) {
		if( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get the remote version
		$remote_version = $this->getRemote('version');

		// If a newer version is available, add the update
		if( version_compare( $this->current_version, $remote_version->new_version, '<' ) ) {
			$obj = new stdClass();
			$obj->slug = $this->slug;
			$obj->plugin = $this->plugin_slug;
			$obj->version = $this->current_version;
			$obj->package = $remote_version->package;
			$obj->new_version = $remote_version->new_version;
			$obj->requires = $remote_version->requires;
			$obj->tested = $remote_version->tested;
			$obj->upgrade_notice = $remote_version->upgrade_notice;
			$obj->admin_notice = $remote_version->admin_notice;
			$obj->admin_notices = $remote_version->admin_notices;
			$obj->url = $remote_version->url;
			$transient->response[$this->plugin_slug] = $obj;
		}
		return $transient;
	}

	/**
	 * Add our self-hosted description to the filter
	 *
	 * @param boolean $false
	 * @param array $api
	 * @param object $arg
	 * @return bool|object
	 */
	public function check_info( $false, $api, $arg ) {
		if( isset( $arg->slug ) && $arg->slug === $this->slug ) {
			return $this->getRemote('info');
		}
		
		return false;
	}

	/**
	 * Return the remote version
	 * 
	 * @return string $remote_version
	 */
	public function getRemote( $api='' ) {
		$params = array(
			'body' => array(
				'api' => $api,
				'plugin' => $this->plugin,
				'license_user' => $this->license_user,
				'license_key' => $this->license_key,
			),
		);
		
		// Make the POST request
		$request = wp_remote_post( $this->update_path, $params );
		
		// Check if response is valid
		if( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			return @unserialize( $request['body'] );
		}
		
		return false;
	}

}