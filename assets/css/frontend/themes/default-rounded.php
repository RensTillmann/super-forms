<?php
$s = '.super-form-'.$id.' ';
return "
".$s.".super-shortcode-field,
".$s.".super-field .super-field-wrapper .super-shortcode-field,
".$s.".super-field-wrapper.super-icon-outside .super-icon,
".$s.".super-field-wrapper.super-icon-inside .super-icon,
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
".$s.".super-field:not(.super-slider):not(.super-radio):not(.super-checkbox) .super-field-wrapper.super-icon-inside .super-icon {
	margin-top:1px;
	margin-left:2px;
	border:0;
	background:none;
}
".$s.".super-focus .super-field-wrapper.super-icon-inside .super-icon {
	border:0;
}
".$s.".super-field-wrapper.super-icon-inside .super-dropdown-ui {
	margin-left:0px;
	padding-left:20px;
}
".$s.".super-slider .super-field-wrapper.super-icon-inside {
	padding-left:40px;
}
".$s.".super-field-wrapper.super-icon-inside .super-dropdown-ui {
    margin-left: 0px;
    padding-left: 0px;
}
".$s.".super-field-wrapper.super-icon-inside .super-dropdown-ui > li {
    margin-left: 0px;
    padding-left: 35px;
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper .super-dropdown-ui li.selected {
	padding-left: 35px;
}
".$s.".super-focus .super-field-wrapper.super-icon-inside .super-dropdown-ui {
    width: -moz-calc(100% - 0px)!important;
    width: calc(100% - 0px)!important;
}
";
?>



