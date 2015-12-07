<?php
$s = '.super-form-'.$id.' ';
$v = $settings;

return "
".$s.".super-shortcode-field,
".$s.".super-field-wrapper.super-icon-outside .super-icon,
".$s.".super-fileupload-button,
".$s.".super-dropdown-ui,
".$s.".super-dropdown-ui li.super-placeholder,
".$s." > p {
    -webkit-border-radius: 17px;
    -moz-border-radius: 17px;
    border-radius: 17px;
}
";
/*
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper {
	-webkit-border-radius: 100%;      
	-moz-border-radius: 100%;      
	border-radius: 100%;
}
".$s.".super-multipart-steps .super-multipart-step {
	-webkit-border-radius: 100%;      
	-moz-border-radius: 100%;      
	border-radius: 100%;
}
".$s.".super-multipart-progress-inner {
	-webkit-border-radius: 15px;  
	-moz-border-radius: 15px;  
	border-radius: 15px;
}
".$s.".super-multipart-progress-bar {
	-webkit-border-radius: 100%;
	-moz-border-radius: 100%;
	border-radius: 100%;
}
*/
?>