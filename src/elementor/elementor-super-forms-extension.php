<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Elementor Test Extension Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 4.9.300
 */
final class Elementor_Super_Forms_Extension {

	/**
	 * Minimum Elementor Version
	 *
	 * @since 4.9.300
	 *
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 4.9.300
	 *
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '5.6';

	/**
	 * Instance
	 *
	 * @since 4.9.300
	 *
	 * @access private
	 * @static
	 *
	 * @var Elementor_Super_Forms_Extension The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 4.9.300
	 *
	 * @access public
	 * @static
	 *
	 * @return Elementor_Super_Forms_Extension An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * Constructor
	 *
	 * @since 4.9.300
	 *
	 * @access public
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'i18n' ] );
		add_action( 'plugins_loaded', [ $this, 'init' ] );

	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 *
	 * Fired by `init` action hook.
	 *
	 * @since 4.9.300
	 *
	 * @access public
	 */
	public function i18n() {

		load_plugin_textdomain( 'elementor-test-extension' );

	}

	/**
	 * Initialize the plugin
	 *
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed load the files required to run the plugin.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 4.9.300
	 *
	 * @access public
	 */
	public function init() {

		// Add Plugin actions
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
		// Only enqueue scripts in preview mode (which is also the editor mode)
		if(!empty($_GET['elementor-preview'])){
			add_action('elementor/frontend/after_enqueue_styles', [ $this, 'load_frontend_scripts_before_ajax' ] );
		}

        add_action( 'elementor/editor/footer', function() {
			?>
			<script>
				jQuery( function( $ ) {
					var intval = setInterval(function(){ 
						var widget = document.querySelector('.elementor-super-forms-icon');
						if(widget){
							widget = widget.closest('.elementor-element-wrapper');
							if(widget){
								var parent = widget.parentNode;
								if(parent){
									if(parent.firstChild!==widget){
										parent.insertBefore(widget, parent.firstChild);
									}
								}
							}
						}
					}, 100);
				});
			</script>
            <style>
            .elementor-super-forms-icon {
                width: 100%;
                display: block;
                height: 28px;
                background-image: url('<?php echo esc_url(SUPER_PLUGIN_FILE . '/assets/images/elementor.jpg'); ?>');
                background-repeat: no-repeat;
                background-position: center;
                background-size: contain;
            }
            </style>
            <?php
        });
	}
	public function load_frontend_scripts_before_ajax() {
		$global_settings = SUPER_Common::get_global_settings();
		require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
		$default_settings = SUPER_Settings::get_defaults();
		$global_settings = array_merge( $default_settings, $global_settings );
		SUPER_Forms::enqueue_element_styles();
		SUPER_Forms::enqueue_element_scripts( $global_settings, true );
	}

	/**
	 * Init Widgets
	 *
	 * Include widgets files and register them
	 *
	 * @since 4.9.300
	 *
	 * @access public
	 */
	public function init_widgets() {

		// Include Widget files
		require_once( __DIR__ . '/widgets/super-forms-widget.php' );

		// Register widget
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Elementor_Super_Forms_Widget() );

	}

}

Elementor_Super_Forms_Extension::instance();