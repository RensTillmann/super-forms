<?php
/*
 * jQuery File Upload Plugin PHP Example 5.14
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

if( (!isset($_REQUEST['max_file_size'])) || (!isset($_REQUEST['accept_file_types'])) ) {
	exit;
}

$script_filename = filter_input(INPUT_SERVER, 'SCRIPT_FILENAME', FILTER_SANITIZE_URL);
$root = dirname( dirname( dirname( dirname( dirname( dirname( $script_filename ) ) ) ) ) );
$root = ($root=='/' ? '' : $root);
$root = realpath( $root );
if( file_exists( $root . '/wp-load.php' ) ) {
    require_once( $root . '/wp-load.php' );
}else{
	print_r('Could not locate: ' . $root . '/wp-load.php');
	exit;
}

error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');

$max_file_size = filter_input(INPUT_POST, 'max_file_size', FILTER_VALIDATE_INT);
$accept_file_types = filter_input(INPUT_POST, 'accept_file_types', FILTER_SANITIZE_STRING);
$accept_file_types = explode('|', $accept_file_types);
$strip = array('.*', 'php', 'phtml', 'php3', 'php5', 'phps', 'shtml', 'asa', 'cer');
foreach($accept_file_types as $k => $v){
	$extension = strtolower($accept_file_types[$k]);
	if (in_array($extension, $strip)) {
	    unset($accept_file_types[$k]);
	}
}
$accept_file_types = implode('|', $accept_file_types);
$upload_handler = new UploadHandler(array(
    'accept_file_types' => '/\.(' . $accept_file_types . ')$/i',
    'max_file_size' => $max_file_size
));
