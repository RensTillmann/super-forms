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

// @Important - Do not delete the below definition
// This is required to fix a bug with NextGen Gallery plugin which causes the request to not return anything, resulting in a JS error
define( 'NGG_DISABLE_RESOURCE_MANAGER', true );

error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');

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
$max_file_size = filter_input(INPUT_POST, 'max_file_size', FILTER_VALIDATE_INT);
$image_library = filter_input(INPUT_POST, 'image_library', FILTER_VALIDATE_INT);
$upload_handler = new UploadHandler(array(
    'accept_file_types' => '/\.(' . $accept_file_types . ')$/i',
    'max_file_size' => $max_file_size,
    'image_library' => $image_library
));
