<?php
//var_dump($_POST['accept_file_types']);
//var_dump($_POST['max_file_size']);
//var_dump($_POST['image_library']);
//var_dump($_POST['files']);
//var_dump($_FILES);
//exit;
header('Content-Type: text/plain; charset=utf-8');
try {
    // If this request falls under any of them, treat it invalid.
    var_dump($_FILES['files']);
    var_dump($_FILES['files']['error']);
    if (!isset($_FILES['files']['error']) || is_array($_FILES['files']['error'])) {
        throw new RuntimeException('Invalid parameters.');
    }
    // Check $_FILES['files']['error'] value.
    switch ($_FILES['files']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }
    // You should also check filesize here.
    if ($_FILES['files']['size'] > 1000000) {
        throw new RuntimeException('Exceeded filesize limit.');
    }
    // DO NOT TRUST $_FILES['files']['mime'] VALUE !!
    // Check MIME Type by yourself.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (false === $ext = array_search(
        $finfo->file($_FILES['files']['tmp_name']),
        array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ),
        true
    )) {
        throw new RuntimeException('Invalid file format.');
    }
    // You should name it uniquely.
    // DO NOT USE $_FILES['files']['name'] WITHOUT ANY VALIDATION !!
    // On this example, obtain safe unique name from its binary data.
    if (!move_uploaded_file( $_FILES['files']['tmp_name'], sprintf('./u/f/%s.%s', sha1_file($_FILES['files']['tmp_name']), $ext))) {
        throw new RuntimeException('Failed to move uploaded file.');
    }
    echo 'File is uploaded successfully.';
} catch (RuntimeException $e) {
    echo $e->getMessage();
}

///*
// * jQuery File Upload Plugin PHP Example 5.14
// * https://github.com/blueimp/jQuery-File-Upload
// *
// * Copyright 2010, Sebastian Tschan
// * https://blueimp.net
// *
// * Licensed under the MIT license:
// * http://www.opensource.org/licenses/MIT
// */
//
//if( (!isset($_REQUEST['max_file_size'])) || (!isset($_REQUEST['accept_file_types'])) ) {
//	exit;
//}
//
//// @Important - Do not delete the below definition
//// This is required to fix a bug with NextGen Gallery plugin which causes the request to not return anything, resulting in a JS error
//define( 'NGG_DISABLE_RESOURCE_MANAGER', true );
//
//error_reporting(E_ALL | E_STRICT);
//require('UploadHandler.php');
//$accept_file_types = filter_input(INPUT_POST, 'accept_file_types', FILTER_SANITIZE_STRING);
//$accept_file_types = explode('|', $accept_file_types);
//$strip = array('php', 'phtml', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'php9', 'php10', 'phps', 'shtml', 'asa', 'cer');
//foreach($accept_file_types as $k => $v){
//	if((in_array(strtolower($v), $strip)) || (!ctype_alnum($v))) {
//	    unset($accept_file_types[$k]);
//    }
//}
//$accept_file_types = implode('|', $accept_file_types);
//$max_file_size = filter_input(INPUT_POST, 'max_file_size', FILTER_VALIDATE_INT);
//$image_library = filter_input(INPUT_POST, 'image_library', FILTER_VALIDATE_INT);
//$upload_handler = new UploadHandler(array(
//    'accept_file_types' => '/\.(' . $accept_file_types . ')$/i',
//    'max_file_size' => $max_file_size,
//    'image_library' => $image_library
//));
