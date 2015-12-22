<?php
$s = '.super-form-'.$id.' ';
$v = $settings;

if( !isset( $v['theme_field_colors_placeholder'] ) ) {
    $v['theme_field_colors_placeholder'] = '';
}

return "
".$s."::-webkit-input-placeholder { /* WebKit browsers */
    color:".$v['theme_field_colors_placeholder'].";
}
".$s.":-moz-placeholder { /* Mozilla Firefox 4 to 18 */
   color:".$v['theme_field_colors_placeholder'].";
   opacity:1;
}
".$s."::-moz-placeholder { /* Mozilla Firefox 19+ */
   color:".$v['theme_field_colors_placeholder'].";
   opacity:1;
}
".$s.":-ms-input-placeholder { /* Internet Explorer 10+ */
   color:".$v['theme_field_colors_placeholder'].";
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

".$s.".super-field .super-label {
	color: ".$v['theme_field_label'].";
}
".$s.".super-field .super-description {
	color: ".$v['theme_field_description'].";
}
".$s."input,
".$s.".super-dropdown-ui,
".$s."textarea,
".$s.".super-field div .super-fileupload-button {
    color: ".$v['theme_field_colors_font'].";
    background-color: ".$v['theme_field_colors_top'].";
    border: solid 1px ".$v['theme_field_colors_border'].";
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top']."), color-stop(1, ".$v['theme_field_colors_bottom']."))!important;';
    background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%)!important;';
    background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%)!important;';
    background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%)!important;';
    background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%)!important;';
    background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%)!important;';
}
".$s."input:focus,
".$s.".super-focus .super-dropdown-ui,
".$s."textarea:focus {
    color: ".$v['theme_field_colors_font_focus'].";
    background-color: ".$v['theme_field_colors_top_focus'].";
    border: solid 1px ".$v['theme_field_colors_border_focus'].";
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top_focus']."), color-stop(1, ".$v['theme_field_colors_bottom_focus']."))!important;';
    background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%)!important;';
    background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%)!important;';
    background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%)!important;';
    background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%)!important;';
    background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%)!important;';
}
".$s.".super-field-wrapper .super-icon {
	color: ".$v['theme_icon_color'].";
    background-color: ".$v['theme_icon_bg'].";
    border: 1px solid ".$v['theme_icon_border'].";
}
".$s.".super-focus .super-field-wrapper .super-icon {
	color: ".$v['theme_icon_color_focus'].";
    background-color: ".$v['theme_icon_bg_focus'].";
    border: 1px solid ".$v['theme_icon_border_focus'].";
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
".$s.".super-rating .super-rating-star.selected {
	color: ".$v['theme_rating_color_active'].";
    background-color: ".$v['theme_rating_bg_active'].";
}
".$s.".super-product .super-currency,
".$s.".super-total .super-currency,
".$s.".super-discount .super-currency {
	color: ".$v['theme_currency_color'].";
}
".$s.".super-product .super-price,
".$s.".super-total .super-amount,
".$s.".super-discount .super-amount {
	color: ".$v['theme_amount_color'].";
}
".$s.".super-product .super-quantity {
	color: ".$v['theme_quantity_color'].";
}
".$s.".super-discount .super-percentage {
	color: ".$v['theme_percentage_color'].";
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
".$s.".super-form.super-style-one .super-multipart-steps .super-multipart-step.super-error:before {
    color: ".$v['theme_error_font'].";
}
".$s.".super-button .super-button-name {
    color: ".$v['theme_button_font'].";
}
".$s.".super-field > p {
    color: ".$v['theme_error_font'].";
}
".$s.".super-msg.error {
    border: 1px solid".$v['theme_error_msg_border_color'].";
    background-color: ".$v['theme_error_msg_bg_color'].";
    color: ".$v['theme_error_msg_font_color'].";
}
".$s.".super-msg.error a {
    color: ".$v['theme_error_msg_font_color'].";
}
".$s.".super-msg.error:after {
    color: ".$v['theme_error_msg_icon_color'].";
}
".$s.".super-msg.success {
    border: 1px solid".$v['theme_success_msg_border_color'].";
    background-color: ".$v['theme_success_msg_bg_color'].";
    color: ".$v['theme_success_msg_font_color'].";
}
".$s.".super-msg.success a {
    color: ".$v['theme_success_msg_font_color'].";
}
".$s.".super-msg.success:after {
    color: ".$v['theme_success_msg_icon_color'].";
}
";