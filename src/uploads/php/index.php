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
require_once('../../../../../wp-load.php');
error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');
$max_file_size = $_REQUEST['max_file_size'];  
$accept_file_types = $_REQUEST['accept_file_types'];
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