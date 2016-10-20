<?php
$s = '.super-form-'.$id.' ';
$v = $settings;

return " 
".$s.".super-multipart-progress-inner {
	background-color: ".$v['theme_progress_bar_secondary_color'].";
	border: 1px solid ".$v['theme_progress_bar_border_color'].";
}
".$s.".super-multipart-progress-bar {
	background-color: ".$v['theme_progress_bar_primary_color'].";
}
".$s.".super-multipart-steps .super-multipart-step {
	background-color: ".$v['theme_progress_step_secondary_color'].";
	border: 1px solid ".$v['theme_progress_step_border_color'].";
}
".$s.".super-multipart-steps .super-multipart-step:after {
	border-top: 1px dashed ".$v['theme_progress_step_border_color'].";
}
".$s.".super-multipart-steps .super-multipart-step.active {
	color: ".$v['theme_progress_step_font_color_active'].";
	background-color: ".$v['theme_progress_step_secondary_color_active'].";
	border: 1px solid ".$v['theme_progress_step_border_color_active'].";
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper {
	background-color: ".$v['theme_progress_step_primary_color'].";
}
".$s.".super-multipart-steps .super-multipart-step.active .super-multipart-step-wrapper {
	background-color: ".$v['theme_progress_step_primary_color_active'].";
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-count {
	color: ".$v['theme_progress_step_font_color'].";
}
".$s.".super-multipart-steps .super-multipart-step.active .super-multipart-step-count {
	color: ".$v['theme_progress_step_font_color_active'].";
}
".$s.".super-multipart-progress {
    display:none;
}
".$s.".super-multipart-steps {
	text-align:center;
}
".$s.".super-multipart-steps .super-multipart-step {
	-webkit-border-radius: 0%;      
	-moz-border-radius: 0%;      
	border-radius: 0%;
	padding:0px;
	padding:0px;
	width:40px;
	height:2px;
	margin:0px 5px 0px 5px;
    background-color: ".$v['theme_progress_step_primary_color'].";
}
".$s.".super-multipart-steps .super-multipart-step:after {
	display:none;
}
".$s.".super-multipart-steps .super-multipart-step * {
	display:none;
}
".$s.".super-multipart-steps .super-multipart-step.active {
	border-color: ".$v['theme_progress_step_primary_color_active'].";
    background-color: ".$v['theme_progress_step_primary_color_active'].";
}
".$s.".super-multipart-steps .super-multipart-step.super-error {
    background-color: ".$v['theme_error_font'].";
	border-color: ".$v['theme_error_font'].";
}
".$s.".super-multipart-steps .super-multipart-step.super-error:before {
    content:'\\f071';
    font-family: Fontawesome;
    position: absolute;
    width: 100%;
    text-align: center;
    left: 0px;
    bottom: 8px;
    height: 20px;
    line-height: 20px;
    font-size: 14px;
    color: ".$v['theme_error_font'].";
}
".$s.".active {
	background:none;
}
".$s.".super-field .super-field-wrapper {
	padding: 0px 0px 10px 0px;
}
".$s.".super-field.super-checkbox.display-vertical .super-field-wrapper,
".$s.".super-field.super-radio.display-vertical .super-field-wrapper {
	padding:0px 0px 10px 0px;
}
".$s.".super-field .super-field-wrapper.super-icon-outside.super-icon-left {
	padding: 0px 0px 10px 40px;
}
".$s.".super-field.super-checkbox.display-horizontal .super-field-wrapper,
".$s.".super-field.super-radio.display-horizontal .super-field-wrapper {
	padding: 0px 30px 10px 50px;
}
".$s.".super-field .super-field-wrapper.super-icon-outside.super-icon-right {
	padding:0px 40px 10px 0px;
}
".$s.".super-field .super-field-wrapper.super-icon-inside.super-icon-left {
	padding: 0px 0px 10px 40px;
}
".$s.".super-field .super-field-wrapper.super-icon-inside.super-icon-right {
	padding: 0px 40px 10px 0px;
}
".$s.".super-field .super-field-wrapper .super-shortcode-field {
	border:0px!important;
	background:none!important;
	-webkit-box-shadow: none;  
	-moz-box-shadow: none;  
	box-shadow: none;
    padding-left:0px;
}
".$s.".super-field.super-time .super-field-wrapper .super-shortcode-field {
	width:100%;
}
".$s.".super-field .super-field-wrapper.super-icon-inside .super-shortcode-field {
	padding-left:15px;
}
".$s.".super-field .super-field-wrapper.super-icon-inside.super-icon-right .super-shortcode-field {
	padding:0px 0px 0px 15px;
}
".$s.".super-field .super-field-wrapper:after {
	content: '';
	position: absolute;
	left: 0;
	width: 100%;
	bottom: 1px;
	border-bottom: 2px solid ".$v['theme_field_colors_border'].";
}
".$s.".super-field .super-field-wrapper:before {
    content: '';
    position: absolute;
    left: 0;
    width: 0%;
    bottom: 1px;
    margin-top: 2px;
    bottom: 0px;
    border-bottom: 4px solid ".$v['theme_field_colors_border_focus'].";
    z-index: 2;
    -webkit-transition: width .4s ease-out;
    -moz-transition: width .4s ease-out;
    -o-transition: width .4s ease-out;
    transition: width .4s ease-out;
}
".$s.".super-field.super-focus .super-field-wrapper:before,
".$s.".super-field.super-focus-dropdown .super-field-wrapper:before {
    width: 100%;
}
".$s.".super-field.super-skype .super-field-wrapper:before,
".$s.".super-field.super-skype .super-field-wrapper:after {
	display:none;
}
".$s.".super-field.super-slider .super-field-wrapper:after,
".$s.".super-field.super-slider .super-field-wrapper:before {
	display:none;
}
".$s.".super-field.super-skype .super-field-wrapper #SkypeButton_Call_ {
	padding:20px 0px 0px 5px;
}
".$s.".super-field.super-skype .super-field-wrapper #SkypeButton_Call_ #SkypeButton_Call__paraElement {
	margin:0px;
}
".$s.".super-field.super-skype .super-field-wrapper #SkypeButton_Call_ #SkypeButton_Call__paraElement a {
	outline: 0;
}
".$s.".super-field.super-skype .super-field-wrapper #SkypeButton_Call_ #SkypeButton_Call__paraElement a img {
	margin:0px!important;
}
".$s.".super-datepicker-dialog,
".$s.".ui-timepicker-wrapper {
	margin-top:15px;
}
".$s.".super-message {
	padding:10px 20px;
	margin:0px 0px 50px 0px;
	background-color: rgb(74, 74, 74);
	color:white;
	font-size:14px;
	border: 1px solid rgb(157, 157, 157);
}
".$s.".super-field > p::before {
  left: -26px;
  top: -10px;
}

/*------------------------------------------------------------*/
/*	SUPER Dropdown UI
/*------------------------------------------------------------*/
".$s.".super-field .super-field-wrapper .super-dropdown-ui {
	list-style:none;
	height: auto;
	line-height:16px;
	margin: 0px 0px 0px 0px;
	padding:0;
	cursor: pointer;
	position: relative;
	border: 0px;
	z-index: 9999;
	text-align: left;
	background: none!important;
}
".$s.".super-field .super-field-wrapper .super-dropdown-ui li.super-placeholder {
	display:block;
	height: 33px;
	line-height: 32px;
	padding-left: 0px;
	background: none!important;
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper .super-dropdown-ui li.super-placeholder {
	padding-bottom: 43px;
    border:0px;
}
".$s.".super-field .super-field-wrapper .super-dropdown-ui li {
	margin:0;
	padding: 0px 0px 0px 15px;
	height: 20px;
	line-height: 29px;
	background-color: ".$v['theme_field_colors_top'].";;
	display: none;
	line-height: 20px;
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper .super-dropdown-ui {    
	max-height: 200px;
	z-index: 9999999;
	overflow: hidden;
	position: absolute;
	overflow-y: auto;
	top: 0px;
	padding-bottom: 10px;
	background: none!important;
	border: none!important;
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper.super-icon-left .super-dropdown-ui {
	border:1px solid red;
	width: 90%;
	left: initial;
	right: 0px;
	width: 100%; /* all browsers */    
	width: -moz-calc(100% - 40px); /* Firefox 4+ */    
	width: calc(100% - 40px); /* IE9+ and future browsers */;
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper .super-dropdown-ui li {
	display:block;
	background-color: ".$v['theme_field_colors_top'].";
    border-left: 1px solid ".$v['theme_field_colors_border_focus'].";
    border-right: 1px solid ".$v['theme_field_colors_border_focus'].";    
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper .super-dropdown-ui li:last-child {
    border-bottom: 1px solid ".$v['theme_field_colors_border_focus'].";
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper .super-dropdown-ui li.selected {
	font-weight:bold;
	padding-left: 15px;
}
".$s.".super-field.super-focus-dropdown .super-field-wrapper .super-dropdown-ui li.selected:before {
	content:'\f00c';
    font-size: 12px;
    font-weight:100;
    padding:0px 5px 0px 0px;
    font-family: FontAwesome;
}
".$s.".super-fileupload-button {
	border:none!important;
}
".$s.".super-field.super-quantity .super-field-wrapper:before,
".$s.".super-field.super-quantity .super-field-wrapper:after {
	display:none;
}
".$s.".super-quantity .super-minus-button,
".$s.".super-quantity .super-plus-button {
	background:none!important;;
}
".$s.".super-quantity .super-minus-button:after {
	content:'\\f056';
}
".$s.".super-quantity .super-plus-button:after {
	content:'\\f055';
}
";