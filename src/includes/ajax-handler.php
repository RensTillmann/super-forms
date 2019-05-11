<?php
header('Content-Type: text/html');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
$request_body = json_decode(file_get_contents('php://input'), true);
if( (!empty($request_body['super_ajax'])) && ($request_body['super_ajax']==='true') ) {
	define( 'DOING_AJAX', true );
	define( 'SHORTINIT', true );

	require_once('../../../../wp-load.php');
	// Include scripts to call `current_user_can()` function
	require_once( ABSPATH . WPINC . '/capabilities.php' );
	require_once( ABSPATH . WPINC . '/class-wp-roles.php' );
	require_once( ABSPATH . WPINC . '/class-wp-role.php' );
	require_once( ABSPATH . WPINC . '/class-wp-session-tokens.php' );
	require_once( ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php' );
	require_once( ABSPATH . WPINC . '/rest-api.php' );
	require_once( ABSPATH . WPINC . '/class-wp-user.php' );
	require_once( ABSPATH . WPINC . '/user.php' );
	require_once( ABSPATH . WPINC . '/kses.php' );
	// The below global needs to be set to prevent the following PHP warnings:
	// arsort() expects parameter 1 to be array, null given in \wp-includes\plugin.php on line 672
	// Invalid argument supplied for foreach() in \wp-includes\plugin.php on line 673
	$GLOBALS['wp_plugin_paths'] = array();
	// Define constants that rely on the API to obtain the default value.
	// Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
	wp_plugin_directory_constants();
	if ( is_multisite() ) {
		ms_cookie_constants();
	}			
	// Define constants after multisite is loaded.
	wp_cookie_constants();
	require_once( ABSPATH . WPINC . '/pluggable.php' );
	// Check if user has permissions to execute this request
	if(current_user_can('administrator')){
		if( !empty($request_body['action']) ) {
			// Define constants which affect functionality if not already defined.
			wp_functionality_constants();
			// Add magic quotes and set up $_REQUEST ( $_GET + $_POST )
			wp_magic_quotes();
			require_once( ABSPATH . WPINC . '/l10n.php' );
			require_once( ABSPATH . WPINC . '/taxonomy.php' );
			require_once( ABSPATH . WPINC . '/class-wp-taxonomy.php' );
			require_once( ABSPATH . WPINC . '/class-wp-query.php' );
			require_once( ABSPATH . WPINC . '/general-template.php' );
			require_once( ABSPATH . WPINC . '/revision.php' );
			require_once( ABSPATH . WPINC . '/theme.php' );
			require_once( ABSPATH . WPINC . '/rewrite.php' );
			require_once( ABSPATH . WPINC . '/class-wp-rewrite.php' );
			$GLOBALS['wp_rewrite'] = new WP_Rewrite();
			// Upon saving a form
			if( $request_body['action']==='save_form' ) {
				require_once( ABSPATH . WPINC . '/link-template.php' );
				require_once( ABSPATH . WPINC . '/post.php' );
				require_once( ABSPATH . WPINC . '/class-wp-post.php' );
				require_once( ABSPATH . WPINC . '/cron.php' );
				require_once( ABSPATH . WPINC . '/comment.php' );
				require_once( ABSPATH . WPINC . '/nav-menu.php' );
				require_once( ABSPATH . WPINC . '/post-template.php' );
				require_once( ABSPATH . WPINC . '/author-template.php' );
				require_once( '../super-forms.php' );
				$_POST['shortcode'] = $request_body['shortcode'];
				$_POST['i18n_switch'] = $request_body['i18n_switch'];
				$_POST['i18n'] = $request_body['i18n'];
				SUPER_Ajax::save_form( $request_body['form_id'], array(), $request_body['translations'], $request_body['settings'], $request_body['title'] );
			}

			// Load element settings (when editing an element)
			if( $request_body['action']==='load_element_settings' ) {
				require_once( ABSPATH . WPINC . '/link-template.php' );
				require_once( ABSPATH . WPINC . '/post.php' );
				require_once( ABSPATH . WPINC . '/class-wp-post.php' );
				require_once( ABSPATH . WPINC . '/shortcodes.php' );
				require_once( ABSPATH . WPINC . '/media.php' );
				require_once( '../super-forms.php' );
				require_once( '../includes/class-field-types.php');
				// Make sure WC is loaded
				if(SUPER_WC_ACTIVE) require_once( '../../woocommerce/includes/wc-order-functions.php' );
				$_POST['id'] = $request_body['id'];
				$_POST['translating'] = $request_body['translating'];
				if(isset($request_body['i18n']))
					$_POST['i18n'] = $request_body['i18n'];
				SUPER_Ajax::load_element_settings( $request_body['tag'], $request_body['group'], $request_body['data'] );
			}

			// Load form preview
			if( $request_body['action']==='load_preview' ) {
				require_once( ABSPATH . WPINC . '/class-wp-locale.php' );
				require_once( ABSPATH . WPINC . '/link-template.php' );
				require_once( ABSPATH . WPINC . '/post.php' );
				require_once( ABSPATH . WPINC . '/class-wp-post-type.php' );
				require_once( ABSPATH . WPINC . '/class-wp-post.php' );
				require_once( ABSPATH . WPINC . '/cron.php' );
				require_once( ABSPATH . WPINC . '/script-loader.php' );
				require_once( ABSPATH . WPINC . '/shortcodes.php' );
				require_once( ABSPATH . WPINC . '/embed.php' );
				require_once( ABSPATH . WPINC . '/class-wp-embed.php' );
				require_once( ABSPATH . WPINC . '/media.php' );
				require_once( ABSPATH . WPINC . '/http.php' );
				require_once( ABSPATH . WPINC . '/class-http.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-response.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-proxy.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-requests-hooks.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-requests-response.php' );
				require_once( ABSPATH . WPINC . '/widgets.php' );
				require_once( ABSPATH . WPINC . '/class-wp-widget.php' );
				require_once( ABSPATH . WPINC . '/class-wp-widget-factory.php' );

				// Only required for TyniMCE editor
				require_once( ABSPATH . WPINC . '/blocks.php' );
				require_once( ABSPATH . WPINC . '/vars.php' );

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

				require_once( '../super-forms.php' );
				// Make sure WC is loaded
				if(SUPER_WC_ACTIVE) require_once( '../../woocommerce/includes/wc-order-functions.php' );
				// Load pluggable functions.
				require_once( ABSPATH . WPINC . '/pluggable.php' );
				do_action( 'plugins_loaded' );
				$GLOBALS['wp'] = new WP();
				$GLOBALS['wp_widget_factory'] = new WP_Widget_Factory();
				$GLOBALS['wp_locale'] = new WP_Locale();
				do_action( 'init' );

				echo SUPER_Shortcodes::super_form_func( array( 'id'=>$request_body['id'] ) );
        		die();
			}			

			// After adding new element load in the html for this element
			if( $request_body['action']==='get_element_builder_html' ) {
				require_once( ABSPATH . WPINC . '/class-wp-locale.php' );
				require_once( ABSPATH . WPINC . '/link-template.php' );
				require_once( ABSPATH . WPINC . '/post.php' );
				require_once( ABSPATH . WPINC . '/class-wp-post-type.php' );
				require_once( ABSPATH . WPINC . '/class-wp-post.php' );
				require_once( ABSPATH . WPINC . '/cron.php' );
				require_once( ABSPATH . WPINC . '/script-loader.php' );
				require_once( ABSPATH . WPINC . '/shortcodes.php' );
				require_once( ABSPATH . WPINC . '/embed.php' );
				require_once( ABSPATH . WPINC . '/class-wp-embed.php' );
				require_once( ABSPATH . WPINC . '/media.php' );
				require_once( ABSPATH . WPINC . '/http.php' );
				require_once( ABSPATH . WPINC . '/class-http.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-response.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-proxy.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-requests-hooks.php' );
				require_once( ABSPATH . WPINC . '/class-wp-http-requests-response.php' );
				require_once( ABSPATH . WPINC . '/widgets.php' );
				require_once( ABSPATH . WPINC . '/class-wp-widget.php' );
				require_once( ABSPATH . WPINC . '/class-wp-widget-factory.php' );

				// Only required for TyniMCE editor
				if($request_body['tag']==='textarea'){
					require_once( ABSPATH . WPINC . '/blocks.php' );
					require_once( ABSPATH . WPINC . '/vars.php' );
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

				require_once( '../super-forms.php' );
				// Make sure WC is loaded
				if(SUPER_WC_ACTIVE) require_once( '../../woocommerce/includes/wc-order-functions.php' );
				// Load pluggable functions.
				require_once( ABSPATH . WPINC . '/pluggable.php' );
				do_action( 'plugins_loaded' );
				$GLOBALS['wp'] = new WP();
				$GLOBALS['wp_widget_factory'] = new WP_Widget_Factory();
				$GLOBALS['wp_locale'] = new WP_Locale();
				do_action( 'init' );

				$_POST['form_id'] = $request_body['form_id'];
				if(!empty($request_body['predefined'])) {
					$_POST['predefined'] = $request_body['predefined'];
				}else{
					if(isset($request_body['builder'])) 
						$_POST['builder'] = $request_body['builder'];
					if(isset($request_body['translating'])) 
						$_POST['translating'] = $request_body['translating'];
					if(isset($request_body['i18n'])) 
						$_POST['i18n'] = $request_body['i18n'];
				}
				SUPER_Ajax::get_element_builder_html($request_body['tag'], $request_body['group'], null, $request_body['data'], 1);
			}
		}else{
			header("HTTP/1.0 404 Not Found");
			die();
		}
	}else{
		header("HTTP/1.0 404 Not Found");
		die();
	}
}else{
	header("HTTP/1.0 404 Not Found");
	die();
}
exit;