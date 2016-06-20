<?php
$s = '.super-form-'.$id.' ';
return "
".$s.".super-shortcode-field,
".$s.".super-field .super-field-wrapper .super-shortcode-field,
".$s.".super-field-wrapper.super-icon-outside .super-icon,
".$s.".super-fileupload-button,
".$s.".super-dropdown-ui,
".$s.".super-dropdown-ui li.super-placeholder,
".$s." > p {
	-webkit-border-radius:4px;
	-moz-border-radius:4px;
	border-radius:4px;
}
".$s.".super-field.super-quantity .super-field-wrapper .super-shortcode-field {
	-webkit-border-radius:0px;
	-moz-border-radius:0px;
	border-radius:0px;	
}
".$s.".super-field.super-quantity .super-minus-button {
	-webkit-border-top-left-radius: 4px;
	-webkit-border-bottom-left-radius: 4px;
	-moz-border-radius-topleft: 4px;
	-moz-border-radius-bottomleft: 4px;
	border-top-left-radius: 4px;
	border-bottom-left-radius: 4px;
}
".$s.".super-field.super-quantity .super-plus-button {
	-webkit-border-top-right-radius: 4px;
	-webkit-border-bottom-right-radius: 4px;
	-moz-border-radius-topright: 4px;
	-moz-border-radius-bottomright: 4px;
	border-top-right-radius: 4px;
	border-bottom-right-radius: 4px;
}
";
?>



