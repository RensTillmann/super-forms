<?php
header('Content-Type: text/html');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
$request_body = json_decode(file_get_contents('php://input'), true);
if( (!empty($request_body['super_ajax'])) && ($request_body['super_ajax']==='true') ) {
	if( !empty($request_body['action']) ) {
		define( 'DOING_AJAX', true );
		if( $request_body['action']==='delete_entry' ) {
			define( 'SHORTINIT', false );
			var_dump($request_body['wp_root'] . 'wp-load.php');
			require_once($request_body['wp_root'] . 'wp-load.php');
			if( $request_body['action']=='delete_entry' ) {
				var_dump('delete entry');
				var_dump($request_body['entry_id']);
				// Move entry to trash
				wp_delete_post( absint($request_body['entry_id']) );
			}
			die();
		}
		// // Check if user has permission to execute this request
		// if(current_user_can('administrator')){
		// 	// After adding new element load in the html for this element
		// 	if( $request_body['action']==='get_element_builder_html' ) {
		// 		$_POST['form_id'] = absint($request_body['form_id']);
		// 		if(!empty($request_body['predefined'])) {
		// 			$_POST['predefined'] = $request_body['predefined'];
		// 		}else{
		// 			if(isset($request_body['builder'])) 
		// 				$_POST['builder'] = $request_body['builder'];
		// 			if(isset($request_body['translating'])) 
		// 				$_POST['translating'] = $request_body['translating'];
		// 			if(isset($request_body['i18n'])) 
		// 				$_POST['i18n'] = $request_body['i18n'];
		// 		}
		// 		if( empty($request_body['data']) ) $request_body['data'] = null;
		// 		if( empty($request_body['inner']) ) $request_body['inner'] = null;
		// 		SUPER_Ajax::get_element_builder_html($request_body['tag'], $request_body['group'], $request_body['inner'], $request_body['data'], 1);
		// 	}
		// 	// Upon saving a form
		// 	if( $request_body['action']==='save_form' ) {
		// 		$_POST['shortcode'] = $request_body['shortcode'];
		// 		$_POST['i18n_switch'] = $request_body['i18n_switch'];
		// 		$_POST['i18n'] = $request_body['i18n'];
		// 		SUPER_Ajax::save_form( absint($request_body['form_id']), array(), $request_body['translations'], $request_body['settings'], $request_body['title'] );
		// 	}
		// 	// Load element settings (when editing an element)
		// 	if( $request_body['action']==='load_element_settings' ) {
		// 		require_once( '../includes/class-field-types.php');
		// 		$_POST['id'] = $request_body['id'];
		// 		$_POST['translating'] = $request_body['translating'];
		// 		if(isset($request_body['i18n']))
		// 			$_POST['i18n'] = $request_body['i18n'];
		// 		SUPER_Ajax::load_element_settings( $request_body['tag'], $request_body['group'], $request_body['data'] );
		// 	}
		// 	// Load form preview
		// 	if( $request_body['action']==='load_preview' ) {
		// 		echo SUPER_Shortcodes::super_form_func( array( 'id'=>$request_body['id'] ) );
		// 	}
		// }else{
		// 	header("HTTP/1.0 404 Not Found");
		// 	die();
		// }		
	}else{
		header("HTTP/1.0 404 Not Found");
		die();
	}
}else{
	header("HTTP/1.0 404 Not Found");
	die();
}
die();