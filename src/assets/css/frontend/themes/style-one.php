<?php
$s = '.super-form-'.$form_id.' ';
$s_large = '.super-form-'.$form_id.'.super-field-size-large ';
$s_huge = '.super-form-'.$form_id.'.super-field-size-huge ';
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
".$s.".super-field .super-field-wrapper .super-shortcode-field,
".$s.".super-field .super-field-wrapper .super-autosuggest-tags,
".$s.".super-field .super-field-wrapper .super-shortcode-field,
".$s.".super-stripe_ideal .StripeElement {
	border:none;
	background:none;
	-webkit-box-shadow: none;  
	-moz-box-shadow: none;  
	box-shadow: none;
    padding-left:0px;
}
".$s.".super-field .super-field-wrapper .super-autosuggest-tags.super-shortcode-field input {
    margin-left:0px;
}
".$s.".super-field .super-field-wrapper .super-dropdown-ui {
	border:none;
}
".$s.".super-field div .super-fileupload-button {
	border:none!important;
	background:none!important;
}
".$s.".super-fileupload-button i {
    left: 5px;
}
".$s.".super-fileupload-button {
	padding-left:25px;
}
".$s.".super-field.super-text:after,
".$s.".super-field.super-textarea:after,
".$s.".super-field.super-currency:after,
".$s.".super-field.super-file:after,
".$s.".super-field.super-date:after,
".$s.".super-field.super-time:after,
".$s.".super-field.super-dropdown:after {
	content: '';
	position: absolute;
	left: 0;
	width: 100%;
	bottom: -8px;
	border-bottom: 2px solid ".$v['theme_field_colors_border'].";
}
".$s.".super-field.super-text:before,
".$s.".super-field.super-textarea:before,
".$s.".super-field.super-currency:before,
".$s.".super-field.super-file:before,
".$s.".super-field.super-date:before,
".$s.".super-field.super-time:before,
".$s.".super-field.super-dropdown:before {
    content: '';
    position: absolute;
    left: 0;
    width: 0%;
    bottom: 1px;
    margin-top: 2px;
    bottom: -8px;
    border-bottom: 4px solid ".$v['theme_field_colors_border_focus'].";
    z-index: 2;
    -webkit-transition: width .4s ease-out;
    -moz-transition: width .4s ease-out;
    -o-transition: width .4s ease-out;
    transition: width .4s ease-out;
}
".$s.".super-field.super-text.super-focus:before,
".$s.".super-field.super-textarea.super-focus:before,
".$s.".super-field.super-currency.super-focus:before,
".$s.".super-field.super-file.super-focus:before,
".$s.".super-field.super-date.super-focus:before,
".$s.".super-field.super-time.super-focus:before,
".$s.".super-field.super-focus-dropdown:before {
    width: 100%;
}
".$s.".super-shortcode.super-field.super-focus-dropdown .super-field-wrapper {
    height: 33px;
    min-height: 33px;
}
".$s.".super-field .super-field-wrapper .super-dropdown-ui li.super-placeholder {
	height:33px;
}
".$s.".super-field.super-focus-dropdown .super-placeholder {
	margin-bottom: 4px;
}

".$s_large.".super-shortcode.super-field.super-focus-dropdown .super-field-wrapper {
    height: 43px;
    min-height: 43px;
}
".$s_large.".super-field .super-field-wrapper .super-dropdown-ui li.super-placeholder {
	height:43px;
}
".$s_large.".super-field.super-focus-dropdown .super-placeholder {
	margin-bottom: 4px;
}

".$s_huge.".super-shortcode.super-field.super-focus-dropdown .super-field-wrapper {
    height: 53px;
    min-height: 53px;
}
".$s_huge.".super-field .super-field-wrapper .super-dropdown-ui li.super-placeholder {
	height:53px;
}
".$s_huge.".super-field.super-focus-dropdown .super-placeholder {
	margin-bottom: 4px;
}


".$s.".super-quantity .super-minus-button:after {
	content:'\\f056';
}
".$s.".super-quantity .super-plus-button:after {
	content:'\\f055';
}
".$s.".super-quantity .super-minus-button,
".$s.".super-quantity .super-plus-button {
	background:none!important;
}
";