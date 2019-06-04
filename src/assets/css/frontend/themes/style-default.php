<?php
$s = '.super-form-'.$form_id.' ';
$v = $settings;

if( !isset( $v['theme_field_colors_placeholder'] ) ) {
    $v['theme_field_colors_placeholder'] = '';
}

// @since 2.0.0
if( !isset( $v['theme_success_msg_margin'] ) ) {
    $v['theme_success_msg_margin'] = '0px 0px 30px 0px';
}

/* @since 2.9.0 - toggle buttons */
if( !isset( $v['theme_ui_toggle_bg'] ) ) $v['theme_ui_toggle_bg'] = '#4EB1B6';
if( !isset( $v['theme_ui_toggle_font'] ) ) $v['theme_ui_toggle_font'] = '#ffffff';
if( !isset( $v['theme_ui_toggle_disabled_bg'] ) ) $v['theme_ui_toggle_disabled_bg'] = '#e4e4e4';
if( !isset( $v['theme_ui_toggle_disabled_font'] ) ) $v['theme_ui_toggle_disabled_font'] = '#9c9c9c';

/* @since 2.9.0 - keywords */
if( !isset( $v['theme_ui_keywords_bg'] ) ) $v['theme_ui_keywords_bg'] = '#4EB1B6';
if( !isset( $v['theme_ui_keywords_font'] ) ) $v['theme_ui_keywords_font'] = '#ffffff';
if( !isset( $v['theme_ui_keywords_icon'] ) ) $v['theme_ui_keywords_icon'] = '#2e8a90';
if( !isset( $v['theme_ui_keywords_icon_hover'] ) ) $v['theme_ui_keywords_icon_hover'] = '#246569';

// @since 2.9.0
if( !isset( $v['font_global_size'] ) ) $v['font_global_size'] = 12;
if( !isset( $v['font_label_size'] ) ) $v['font_label_size'] = 16;
if( !isset( $v['font_description_size'] ) ) $v['font_description_size'] = 14;
if( !isset( $v['font_google_fonts'] ) ) $v['font_google_fonts'] = '';
if( !isset( $v['font_global_family'] ) ) $v['font_global_family'] = '"Open Sans",sans-serif';

$import_fonts = '';
if($v['font_google_fonts']!=''){
    $google_fonts = explode( "\n", $v['font_google_fonts'] );  
    foreach( $google_fonts as $font ) {
        $import_fonts .= "@import url('".$font."');\n";
    }
}

// @since 3.0.0 - loading icon font color
if( !isset( $v['theme_ui_loading_icon_font'] ) ) $v['theme_ui_loading_icon_font'] = '#c5c5c5';

// @since 3.1.0 - checkbox/radio label colors
if( !isset( $v['theme_ui_checkbox_label'] ) ) $v['theme_ui_checkbox_label'] = $v['theme_field_colors_font'];

// @since 3.3.0 - show/hide multi-part progress bar
$extra_styles = '';
if( (isset($v['theme_multipart_progress_bar'])) && ($v['theme_multipart_progress_bar']!='true') ) {
    $extra_styles .= $s.".super-multipart-progress {
    display:none;
}\n";  
}
if( (isset($v['theme_multipart_steps'])) && ($v['theme_multipart_steps']!='true') ) {
    $extra_styles .= $s.".super-multipart-steps {
    display:none;
}\n";
}else{
    if( (isset($v['theme_multipart_steps_hide_mobile'])) && ($v['theme_multipart_steps_hide_mobile']=='true') ) {
        $extra_styles .= ".super-form-".$form_id.".super-window-first-responsiveness .super-multipart-steps,
.super-form-".$form_id.".super-window-second-responsiveness .super-multipart-steps,
.super-form-".$form_id.".super-window-third-responsiveness .super-multipart-steps {
    display:none;
}\n";  
    }
}





// @since 3.3.0
if( !isset( $v['theme_ui_checkbox_border'] ) ) $v['theme_ui_checkbox_border'] = '#4EB1B6';
if( !isset( $v['theme_ui_checkbox_inner'] ) ) $v['theme_ui_checkbox_inner'] = '#4EB1B6';
if( !isset( $v['theme_ui_slider_dragger'] ) ) $v['theme_ui_slider_dragger'] = '#4EB1B6';
if( !isset( $v['theme_ui_slider_track'] ) ) $v['theme_ui_slider_track'] = '#CDCDCD';
if( !isset( $v['theme_ui_quantity_bg'] ) ) $v['theme_ui_quantity_bg'] = '#4EB1B6';
if( !isset( $v['theme_ui_quantity_font'] ) ) $v['theme_ui_quantity_font'] = '#ffffff';
if( !isset( $v['theme_ui_quantity_bg_hover'] ) ) $v['theme_ui_quantity_bg_hover'] = '#7ed0d4';
if( !isset( $v['theme_ui_quantity_font_hover'] ) ) $v['theme_ui_quantity_font_hover'] = '#ffffff';

/* @since 3.7.0 - tags autosuggest field */
if( !isset( $v['theme_ui_tags_bg'] ) ) $v['theme_ui_tags_bg'] = '#4EB1B6';
if( !isset( $v['theme_ui_tags_font'] ) ) $v['theme_ui_tags_font'] = '#ffffff';
if( !isset( $v['theme_ui_tags_remove'] ) ) $v['theme_ui_tags_remove'] = '#2e8a90';
if( !isset( $v['theme_ui_tags_remove_hover'] ) ) $v['theme_ui_tags_remove_hover'] = '#246569';
if( !isset( $v['theme_ui_tags_list_bg_hover'] ) ) $v['theme_ui_tags_list_bg_hover'] = '#fdecde';

if( !isset( $v['theme_icon_color'] ) ) $v['theme_icon_color'] = '#B3DBDD';
if( !isset( $v['theme_icon_bg'] ) ) $v['theme_icon_bg'] = '#ffffff';
if( !isset( $v['theme_icon_border'] ) ) $v['theme_icon_border'] = '#cdcdcd';
if( !isset( $v['theme_icon_color_focus'] ) ) $v['theme_icon_color_focus'] = '#4EB1B6';
if( !isset( $v['theme_icon_bg_focus'] ) ) $v['theme_icon_bg_focus'] = '#ffffff';
if( !isset( $v['theme_icon_border_focus'] ) ) $v['theme_icon_border_focus'] = '#cdcdcd';

return $import_fonts."
".$s."::-webkit-input-placeholder { /* WebKit browsers */
    color:".$v['theme_field_colors_placeholder'].";
    font-size: ".$v['font_global_size']."px;
    font-family: ".$v['font_global_family'].";
}
".$s.":-moz-placeholder { /* Mozilla Firefox 4 to 18 */
    color:".$v['theme_field_colors_placeholder'].";
    opacity:1;
    font-size: ".$v['font_global_size']."px;
    font-family: ".$v['font_global_family'].";
}
".$s."::-moz-placeholder { /* Mozilla Firefox 19+ */
    color:".$v['theme_field_colors_placeholder'].";
    opacity:1;
    font-size: ".$v['font_global_size']."px;
    font-family: ".$v['font_global_family'].";
}
".$s.":-ms-input-placeholder { /* Internet Explorer 10+ */
    color:".$v['theme_field_colors_placeholder'].";
    font-size: ".$v['font_global_size']."px;
    font-family: ".$v['font_global_family'].";
}
".$s.".super-focus ::-webkit-input-placeholder { /* WebKit browsers */
    color:".$v['theme_field_colors_placeholder_focus'].";
}
".$s.".super-focus :-moz-placeholder { /* Mozilla Firefox 4 to 18 */
   color:".$v['theme_field_colors_placeholder_focus'].";
   opacity:1;
}
".$s.".super-focus ::-moz-placeholder { /* Mozilla Firefox 19+ */
   color:".$v['theme_field_colors_placeholder_focus'].";
   opacity:1;
}
".$s.".super-focus :-ms-input-placeholder { /* Internet Explorer 10+ */
   color:".$v['theme_field_colors_placeholder_focus'].";
}




".$s.".super-field .super-label,
".$s.".super-field .super-toggle-prefix-label,
".$s.".super-field .super-toggle-suffix-label,
".$s.".super-html .super-html-title {
    color: ".$v['theme_field_label'].";
}
".$s.".super-field .super-description,
".$s.".super-html .super-html-subtitle,
".$s.".super-html .super-html-content {
    color: ".$v['theme_field_description'].";
}
".$s.".super-load-icon {
    color: ".$v['theme_ui_loading_icon_font'].";
}
".$s."input,
".$s.".super-dropdown-ui,
".$s."textarea,
".$s.".super-field div .super-fileupload-button,
".$s.".super-autosuggest-tags,
".$s.".super-stripe_ideal .StripeElement {
    color: ".$v['theme_field_colors_font'].";
    background-color: ".$v['theme_field_colors_top'].";
    border: solid 1px ".$v['theme_field_colors_border'].";
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top']."), color-stop(1, ".$v['theme_field_colors_bottom']."));
    background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
}
".$s.".super-checkbox .super-field-wrapper label,
".$s.".super-radio .super-field-wrapper label {
    color: ".$v['theme_ui_checkbox_label'].";
}
".$s."input:focus,
".$s.".super-focus .super-dropdown-ui,
".$s."textarea:focus {
    color: ".$v['theme_field_colors_font_focus'].";
    background-color: ".$v['theme_field_colors_top_focus'].";
    border: solid 1px ".$v['theme_field_colors_border_focus'].";
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top_focus']."), color-stop(1, ".$v['theme_field_colors_bottom_focus']."));
    background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);
    background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);
    background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);
    background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);
    background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);
}
".$s.".super-radio .super-field-wrapper label:before,
".$s.".super-checkbox .super-field-wrapper label:before {
    border-color: ".$v['theme_ui_checkbox_border'].";
}
".$s.".super-radio .super-field-wrapper label:after,
".$s.".super-checkbox .super-field-wrapper label:after {
    background-color: ".$v['theme_ui_checkbox_inner'].";
}
".$s.".super-slider .super-field-wrapper .slider .dragger,
".$s.".super-slider .super-field-wrapper .slider .dragger:hover {
    background:none;
    background-color: ".$v['theme_ui_slider_dragger'].";
}
".$s.".super-slider .super-field-wrapper .slider .track {
    background-color: ".$v['theme_ui_slider_track'].";
}
".$s.".super-quantity .super-minus-button,
".$s.".super-quantity .super-plus-button {
    background-color: ".$v['theme_ui_quantity_bg'].";
    color: ".$v['theme_ui_quantity_font'].";
}
".$s.".super-quantity .super-minus-button:hover ,
".$s.".super-quantity .super-plus-button:hover {
    background-color: ".$v['theme_ui_quantity_bg_hover'].";
    color: ".$v['theme_ui_quantity_font_hover'].";
}
".$s.".super-field-wrapper .super-icon {
    color: ".$v['theme_icon_color'].";
    " . ($v['theme_icon_bg']!='' ? "background-color: ".$v['theme_icon_bg'].";" : "") . "
    " . ($v['theme_icon_border']!='' ? "border: 1px solid ".$v['theme_icon_border'].";" : "padding-top:1px;padding-left:1px;") . "
}
".$s.".super-focus .super-field-wrapper .super-icon {
    color: ".$v['theme_icon_color_focus'].";
    " . ($v['theme_icon_bg_focus']!='' ? "background-color: ".$v['theme_icon_bg_focus'].";" : "") . "
    " . ($v['theme_icon_border_focus']!='' ? "border: 1px solid ".$v['theme_icon_border_focus'].";" : "padding-top:1px;padding-left:1px;") . "
}
".$s.".super-rating .super-rating-star {
    color: ".$v['theme_rating_color'].";
    background-color: ".$v['theme_rating_bg'].";
    border: 1px solid ".$v['theme_rating_border'].";
}
".$s.".super-rating .super-rating-star:hover {
    color: ".$v['theme_rating_color_hover'].";
    background-color: ".$v['theme_rating_bg_hover'].";
}
".$s.".super-rating .super-rating-star.super-active {
    color: ".$v['theme_rating_color_active'].";
    background-color: ".$v['theme_rating_bg_active'].";
}
".$s.".super-multipart-progress-inner {
    border: 1px solid ".$v['theme_progress_bar_border_color'].";
    background-color: ".$v['theme_progress_bar_secondary_color'].";
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
".$s.".super-form.super-style-one .super-multipart-steps .super-multipart-step {
    background-color: ".$v['theme_progress_step_primary_color'].";
}
".$s.".super-form.super-style-one .super-multipart-steps .super-multipart-step.active {
    border-color: ".$v['theme_progress_step_primary_color_active'].";
    background-color: ".$v['theme_progress_step_primary_color_active'].";
}
".$s.".super-form.super-style-one .super-multipart-steps .super-multipart-step.super-error {
    background-color: ".$v['theme_error_font'].";
    border-color: ".$v['theme_error_font'].";
}
".$s.".super-multipart-steps .super-multipart-step.super-error:before {
    content:'\\f071';
    position: absolute;
    width: 100%;
    text-align: center;
    left: 0px;
    top: -22px;
    bottom:inherit;
    height: 20px;
    line-height: 20px;
    font-size: 14px;
    color: ".$v['theme_error_font'].";
}
".$s.".super-form.super-style-one .super-multipart-steps .super-multipart-step.super-error:before {
    color: ".$v['theme_error_font'].";
}
".$s.".super-initialized .super-multipart.active {
    visibility: visible;
    height: auto;
}
".$s.".super-initialized .super-multipart.active > * {
    opacity:1;
}
".$s.".super-field > p {
    color: ".$v['theme_error_font'].";
}
".$s.".super-msg.super-error {
    border: 1px solid ".$v['theme_error_msg_border_color'].";
    background-color: ".$v['theme_error_msg_bg_color'].";
    color: ".$v['theme_error_msg_font_color'].";
}
".$s.".super-msg.super-error a {
    color: ".$v['theme_error_msg_font_color'].";
}
".$s.".super-msg.super-error:after {
    color: ".$v['theme_error_msg_icon_color'].";
}
".$s.".super-msg.super-success {
    border: 1px solid".$v['theme_success_msg_border_color'].";
    background-color: ".$v['theme_success_msg_bg_color'].";
    color: ".$v['theme_success_msg_font_color'].";
    margin: ".$v['theme_success_msg_margin'].";
}
".$s.".super-msg.super-success a,
".$s.".super-msg.super-success .close {
    color: ".$v['theme_success_msg_font_color'].";
}
".$s.".super-msg.super-success:after {
    color: ".$v['theme_success_msg_icon_color'].";
}
".$s.".super-dropdown-arrow {
    color: ".$v['theme_field_colors_font'].";
}

/* @since 2.9.0 - toggle buttons */
".$s.".super-toggle-switch {
    border-color: ".$v['theme_ui_toggle_bg'].";
}
".$s.".super-toggle-switch .super-toggle-group .super-toggle-on,
".$s.".super-toggle-switch .super-toggle-group .super-toggle-off {
    background-color: ".$v['theme_ui_toggle_bg'].";
    color: ".$v['theme_ui_toggle_font'].";
}
".$s.".super-toggle-switch:not(.super-active) {
    border-color: ".$v['theme_ui_toggle_disabled_bg'].";
}
".$s.".super-toggle-switch .super-toggle-group .super-toggle-off {
    background-color: ".$v['theme_ui_toggle_disabled_bg'].";
    color: ".$v['theme_ui_toggle_disabled_font'].";
}


/* @since 2.9.0 - keywords */
".$s.".super-entered-keywords > span {
    background-color: ".$v['theme_ui_keywords_bg'].";
    color: ".$v['theme_ui_keywords_font'].";
}
.super-entered-keywords > span:after {
    color: ".$v['theme_ui_keywords_icon'].";
}
.super-entered-keywords > span:hover:after {
    color: ".$v['theme_ui_keywords_icon_hover'].";
}


/* @since 2.9.0 - font sizes adjustments */
".$s.".super-html .super-html-subtitle,
".$s.".super-html .super-html-content,
".$s.".super-slider .super-field-wrapper span.amount,
".$s.".super-fileupload-button,
".$s.".super-msg,
".$s.".super-radio.display-horizontal .super-field-wrapper label,
".$s.".super-checkbox.display-horizontal .super-field-wrapper label,
".$s.".super-radio.display-vertical .super-field-wrapper label,
".$s.".super-checkbox.display-vertical .super-field-wrapper label,
".$s.".super-form .super-field > p,
".$s.".super-field .super-field-wrapper .super-shortcode-field,
".$s.".super-field .super-field-wrapper .super-autosuggest-tags,
".$s.".super-field .super-field-wrapper .super-dropdown-ui li,
".$s.".super-fileupload-files > div .super-fileupload-name,
".$s.".super-toggle-prefix-label,
".$s.".super-toggle-suffix-label,
".$s.".super-toggle-switch,
".$s.".super-field .super-field-wrapper .super-shortcode-field,
.ui-timepicker-wrapper.super-form-".$form_id.".super-timepicker-dialog {
    font-size: ".$v['font_global_size']."px;
    font-family: ".$v['font_global_family'].";
}
.super-datepicker-dialog {
    font-family: ".$v['font_global_family'].";
}
".$s.".super-button .super-button-name {
    color: ".$v['theme_button_font'].";
    font-family: ".$v['font_global_family'].";
}
".$s.".super-entered-keywords > span {
    font-family: ".$v['font_global_family'].";
}
".$s.".super-field .super-label {
    font-size: ".$v['font_label_size']."px;
    line-height: ".$v['font_label_size']."px;
}
".$s.".super-field .super-description {
    font-size: ".$v['font_description_size']."px;
    line-height: ".$v['font_description_size']."px;
}
.super-visible {
    visibility:visible;
}

/* @since 3.7.0 - tags autosuggest field */
".$s.".super-field .super-field-wrapper .super-autosuggest-tags > div > span,
".$s.".super-field.super-text.super-keyword-tags.super-string-found .super-field-wrapper .super-dropdown-ui li span.super-wp-tag {
    background-color: ".$v['theme_ui_tags_bg'].";
    color: ".$v['theme_ui_tags_font'].";
}
".$s.".super-field .super-field-wrapper .super-autosuggest-tags > div > span:after {
    color: ".$v['theme_ui_tags_remove'].";
}
".$s.".super-field .super-field-wrapper .super-autosuggest-tags > div > span:hover:after {
    color: ".$v['theme_ui_tags_remove_hover'].";
}
".$s.".super-field.super-text.super-keyword-tags.super-string-found .super-field-wrapper .super-dropdown-ui li:hover {
    background-color: ".$v['theme_ui_tags_list_bg_hover'].";
}
".$s.".super-field.super-text .super-field-wrapper.super-overlap .super-dropdown-ui {
    border: solid 1px ".$v['theme_field_colors_border'].";
    background-color: ".$v['theme_field_colors_top'].";
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top']."), color-stop(1, ".$v['theme_field_colors_bottom']."));
    background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
    background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
}
".$s.".super-field.super-text .super-field-wrapper.super-overlap .super-dropdown-ui > li.super-active {
    color: ".$v['theme_field_colors_font'].";
}
".$extra_styles;