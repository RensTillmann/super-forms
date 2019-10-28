<?php
header('Content-Type: text/html');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
if( (!empty($_POST['super_ajax'])) && ($_POST['super_ajax']==='true') ) {
	if( !empty($_POST['action']) ) {
		define( 'DOING_AJAX', true );
		if( $_POST['action']==='load_preview' ) {
			define( 'SHORTINIT', false );
			require_once($_POST['wp_root'] . 'wp-load.php');
		}else{
			define( 'SHORTINIT', true );
			require_once($_POST['wp_root'] . 'wp-load.php');
			require_once( ABSPATH . WPINC . '/l10n.php' );
			require_once( ABSPATH . WPINC . '/class-wp-locale.php' );
			require_once( ABSPATH . WPINC . '/class-wp-locale-switcher.php' );
			require_once( ABSPATH . WPINC . '/formatting.php' );
			require_once( ABSPATH . WPINC . '/capabilities.php' );
			require_once( ABSPATH . WPINC . '/class-wp-roles.php' );
			require_once( ABSPATH . WPINC . '/class-wp-role.php' );
			require_once( ABSPATH . WPINC . '/class-wp-user.php' );
			require_once( ABSPATH . WPINC . '/class-wp-query.php' );
			require_once( ABSPATH . WPINC . '/theme.php' );
			require_once( ABSPATH . WPINC . '/class-wp-theme.php' );
			require_once( ABSPATH . WPINC . '/user.php' );
			require_once( ABSPATH . WPINC . '/class-wp-user-query.php' );
			require_once( ABSPATH . WPINC . '/class-wp-session-tokens.php' );
			require_once( ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php' );
			require_once( ABSPATH . WPINC . '/meta.php' );
			require_once( ABSPATH . WPINC . '/class-wp-meta-query.php' );
			require_once( ABSPATH . WPINC . '/class-wp-metadata-lazyloader.php' );
			require_once( ABSPATH . WPINC . '/general-template.php' );
			require_once( ABSPATH . WPINC . '/link-template.php' );
			if( $_POST['action']==='save_form' ) {
				require_once( ABSPATH . WPINC . '/author-template.php' );
			}
			require_once( ABSPATH . WPINC . '/post.php' );
			require_once( ABSPATH . WPINC . '/class-wp-post-type.php' );
			require_once( ABSPATH . WPINC . '/class-wp-post.php' );
			if( $_POST['action']==='save_form' ) {
				require_once( ABSPATH . WPINC . '/post-template.php' );
			}
			require_once( ABSPATH . WPINC . '/revision.php' );
			require_once( ABSPATH . WPINC . '/post-formats.php' );
			require_once( ABSPATH . WPINC . '/post-thumbnail-template.php' );
			require_once( ABSPATH . WPINC . '/category.php' );
			require_once( ABSPATH . WPINC . '/category-template.php' );
			if( $_POST['action']==='save_form' ) {
				require_once( ABSPATH . WPINC . '/comment.php' );
			}
			require_once( ABSPATH . WPINC . '/rewrite.php' );
			require_once( ABSPATH . WPINC . '/class-wp-rewrite.php' );
			require_once( ABSPATH . WPINC . '/kses.php' );
			require_once( ABSPATH . WPINC . '/cron.php' );
			require_once( ABSPATH . WPINC . '/script-loader.php' );
			require_once( ABSPATH . WPINC . '/taxonomy.php' );
			require_once( ABSPATH . WPINC . '/class-wp-taxonomy.php' );
			require_once( ABSPATH . WPINC . '/class-wp-term.php' );
			require_once( ABSPATH . WPINC . '/class-wp-term-query.php' );
			require_once( ABSPATH . WPINC . '/class-wp-tax-query.php' );
			require_once( ABSPATH . WPINC . '/shortcodes.php' );
			require_once( ABSPATH . WPINC . '/embed.php' );
			require_once( ABSPATH . WPINC . '/class-wp-embed.php' );
			require_once( ABSPATH . WPINC . '/media.php' );
			require_once( ABSPATH . WPINC . '/http.php' );
			require_once( ABSPATH . WPINC . '/class-http.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-streams.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-curl.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-proxy.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-cookie.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-encoding.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-response.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-requests-response.php' );
			require_once( ABSPATH . WPINC . '/class-wp-http-requests-hooks.php' );
			require_once( ABSPATH . WPINC . '/widgets.php' );
			require_once( ABSPATH . WPINC . '/class-wp-widget.php' );
			require_once( ABSPATH . WPINC . '/class-wp-widget-factory.php' );
			if( $_POST['action']==='save_form' ) {
				require_once( ABSPATH . WPINC . '/nav-menu.php' );
			}
			require_once( ABSPATH . WPINC . '/rest-api.php' );
			// Only required for TyniMCE editor
			if( $_POST['action']==='get_element_builder_html' ) {
                if( file_exists( ABSPATH . WPINC . '/class-wp-block-type.php' ) ) require_once( ABSPATH . WPINC . '/class-wp-block-type.php' );
				if( file_exists( ABSPATH . WPINC . '/class-wp-block-type-registry.php' ) ) require_once( ABSPATH . WPINC . '/class-wp-block-type-registry.php' );
                if( file_exists( ABSPATH . WPINC . '/class-wp-block-parser.php' ) ) require_once( ABSPATH . WPINC . '/class-wp-block-parser.php' );
				if( file_exists( ABSPATH . WPINC . '/blocks.php' ) ) require_once( ABSPATH . WPINC . '/blocks.php' );
				if( file_exists( ABSPATH . WPINC . '/vars.php' ) ) require_once( ABSPATH . WPINC . '/vars.php' );
			}
			$GLOBALS['wp_embed'] = new WP_Embed();
			// Load multisite-specific files.
			if ( is_multisite() ) {
				require_once( ABSPATH . WPINC . '/ms-functions.php' );
				require_once( ABSPATH . WPINC . '/ms-default-filters.php' );
				require_once( ABSPATH . WPINC . '/ms-deprecated.php' );
			}
			// Define constants that rely on the API to obtain the default value.
			// Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
			wp_plugin_directory_constants();
			$GLOBALS['wp_plugin_paths'] = array();

			// Load network activated plugins.
			if ( is_multisite() ) {
				foreach ( wp_get_active_network_plugins() as $network_plugin ) {
					$basename = basename($network_plugin);
					if( (strpos($basename, "super-forms")!==false) || ($basename == "woocommerce.php") ) {
						wp_register_plugin_realpath( $network_plugin );
						include_once( $network_plugin );
						do_action( 'network_plugin_loaded', $network_plugin );
					}
				}
				unset( $network_plugin );
			}
			do_action( 'muplugins_loaded' );
			if ( is_multisite() ) {
				ms_cookie_constants();
			}
			// Define constants after multisite is loaded.
			wp_cookie_constants();
			foreach ( wp_get_active_and_valid_plugins() as $plugin ) {
				$basename = basename($plugin);
				if( (strpos($basename, "super-forms")!==false) || ($basename == "woocommerce.php") ) {
					wp_register_plugin_realpath( $plugin );
					include_once( $plugin );
					do_action( 'plugin_loaded', $plugin );
				}
			}
			unset( $plugin );
			if( $_POST['action']==='load_element_settings' ) {
				require_once( '../includes/class-field-types.php');
			}
			// Load pluggable functions.
			require_once( ABSPATH . WPINC . '/pluggable.php' );
			do_action( 'plugins_loaded' );
			// Define constants which affect functionality if not already defined.
			wp_functionality_constants();
			$GLOBALS['wp_rewrite'] = new WP_Rewrite();
			$GLOBALS['wp'] = new WP();
			$GLOBALS['wp_widget_factory'] = new WP_Widget_Factory();
			load_default_textdomain();
			$locale      = get_locale();
			$locale_file = WP_LANG_DIR . "/$locale.php";
			if ( ( 0 === validate_file( $locale ) ) && is_readable( $locale_file ) ) {
				require_once( $locale_file );
			}
			unset( $locale_file );
			$GLOBALS['wp_locale'] = new WP_Locale();
			$GLOBALS['wp_locale_switcher'] = new WP_Locale_Switcher();
			$GLOBALS['wp_locale_switcher']->init();

            // @IMPORTANT Required for WooCommerce
            // Otherwise it will return a Fatal error: wc_get_stock_html()
            do_action( 'after_setup_theme' );

			do_action( 'init' );
			do_action( 'wp_loaded' );
		}
		
		// Check if user has permission to execute this request
		if(current_user_can('administrator')){
			// After adding new element load in the html for this element
			if( $_POST['action']==='get_element_builder_html' ) {
				$_POST['form_id'] = absint($_POST['form_id']);
				if( empty($_POST['data']) ) $_POST['data'] = null;
				if( empty($_POST['inner']) ) $_POST['inner'] = null;
				SUPER_Ajax::get_element_builder_html($_POST['tag'], $_POST['group'], $_POST['inner'], $_POST['data'], 1);
			}
			// Upon saving a form
			if( $_POST['action']==='save_form' ) {
				if(!isset($_POST['translations'])) $_POST['translations'] = array();
				SUPER_Ajax::save_form( absint($_POST['form_id']), array(), $_POST['translations'], $_POST['settings'], $_POST['title'] );
			}
			// Load element settings (when editing an element)
			if( $_POST['action']==='load_element_settings' ) {
				require_once( '../includes/class-field-types.php');
				SUPER_Ajax::load_element_settings( $_POST['tag'], $_POST['group'], $_POST['data'] );
			}
			// Load form preview
			if( $_POST['action']==='load_preview' ) {
				echo SUPER_Shortcodes::super_form_func( array( 'id'=>$_POST['form_id'] ) );
			}
		}else{
			header("HTTP/1.0 204 No Content");
			die();
		}		
	}else{
		header("HTTP/1.0 204 No Content");
		die();
	}
}else{
	header("HTTP/1.0 204 No Content");
	die();
}
die();
