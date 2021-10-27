<?php
$s = '.super-form-'.$form_id.' ';
$s1 = '.super-form-'.$form_id.'.super-style-one ';
$st = '.tooltip-super-form-'.$form_id.' ';
$v = $settings;

// @since 4.9.3 - Adaptive Placeholders */
$bottom = $v['adaptive_placeholder_bg_bottom_focus'];
$top = $v['adaptive_placeholder_bg_top_focus'];
$placeholder_bg_focus = "
".$s.".super-focus .super-adaptive-placeholder span,
".$s.".super-adaptive-positioning span {
    background: ".$bottom."; /* Old browsers */
    background: -moz-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* FF3.6-15 */
    background: -webkit-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* Chrome10-25,Safari5.1-6 */
    background: linear-gradient(to bottom, ".$top." 50%, ".$bottom." 50%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='".$top."', endColorstr='".$bottom."', GradientType=0 ); /* IE6-9 */
}";
$bottom = $v['adaptive_placeholder_bg_bottom_filled'];
$top = $v['adaptive_placeholder_bg_top_filled'];
$placeholder_bg_filled = "
".$s.".super-filled .super-adaptive-placeholder span {
    background: ".$bottom."; /* Old browsers */
    background: -moz-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* FF3.6-15 */
    background: -webkit-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* Chrome10-25,Safari5.1-6 */
    background: linear-gradient(to bottom, ".$top." 50%, ".$bottom." 50%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='".$top."', endColorstr='".$bottom."', GradientType=0 ); /* IE6-9 */
}";

return "
".$s.".super-label,
".$s.".super-group-title,
".$s.".super-toggle-prefix-label,
".$s.".super-toggle-suffix-label,
".$s.".super-html-title {
    color: ".$v['theme_field_label'].";
}
".$s.".super-description,
".$s.".super-html-subtitle,
".$s.".super-html-content {
  color: ".$v['theme_field_description'].";
}
".$s.".super-multipart-step.super-error:before {
  color: ".$v['theme_error_font'].";
}
".$s.".super-dropdown-list .super-item {
  background-color: ".$v['theme_ui_dropdown_item_bg'].";
}
".$s.".super-dropdown-list .super-item:not(.super-placeholder):hover,
".$s.".super-dropdown-list .super-item:not(.super-placeholder).super-active,
".$s.".super-dropdown-list .super-item:not(.super-placeholder).super-focus {
  background-color: ".$v['theme_ui_dropdown_item_bg_focus'].";
  color: ".$v['theme_ui_dropdown_item_font_focus'].";
}
".$s.".super-int-phone_country {
  background-color: ".$v['theme_ui_dropdown_item_bg'].";
}
".$s.".super-int-phone_country.super-int-phone_highlight,
".$s.".super-int-phone_country.super-int-phone_active {
  opacity: 0.7;
  background-color: ".$v['theme_ui_dropdown_item_bg_focus'].";
  color: ".$v['theme_ui_dropdown_item_font_focus'].";
}
".$s.".super-int-phone_country.super-int-phone_highlight .super-int-phone_dial-code,
".$s.".super-int-phone_country.super-int-phone_active .super-int-phone_dial-code {
  color: ".$v['theme_ui_dropdown_item_font_focus'].";
}
".$s.".super-int-phone_country.super-int-phone_active {
  opacity: 1;
}
".$s.".super-dropdown-arrow {
  color: ".$v['theme_ui_dropdown_arrow'].";
}
".$s.".super-accordion-header {
  background-color: ".$v['theme_accordion_header'].";
}
".$s.".super-accordion-title {
  color: ".$v['theme_accordion_title'].";
}
".$s.".super-accordion-desc {
  color: ".$v['theme_accordion_desc'].";
}
".$s.".super-accordion-header:before,
".$s.".super-accordion-header:after {
  background-color: ".$v['theme_accordion_icon'].";
}
".$s.".super-accordion-content {
  background-color: ".$v['theme_accordion_content'].";
}
".$s.".super-accordion-item:hover .super-accordion-header {
  background-color: ".$v['theme_accordion_header_hover'].";
}
".$s.".super-accordion-item:hover .super-accordion-title {
  color: ".$v['theme_accordion_title_hover'].";
}
".$s.".super-accordion-item:hover .super-accordion-desc {
  color: ".$v['theme_accordion_desc_hover'].";
}
".$s.".super-accordion-item:hover .super-accordion-header:before,
".$s.".super-accordion-item:hover .super-accordion-header:after {
  background-color: ".$v['theme_accordion_icon_hover'].";
}
".$s.".super-accordion-item.super-active .super-accordion-header {
  background-color: ".$v['theme_accordion_header_active'].";
}
".$s.".super-accordion-item.super-active .super-accordion-title {
  color: ".$v['theme_accordion_title_active'].";
}
".$s.".super-accordion-item.super-active .super-accordion-desc {
  color: ".$v['theme_accordion_desc_active'].";
}
".$s.".super-accordion-item.super-active .super-accordion-header:before,
".$s.".super-accordion-item.super-active .super-accordion-header:after {
  background-color: ".$v['theme_accordion_icon_active'].";
}
".$s.".super-keyword-tag,
".$s.".super-wp-tag {
  background-color: ".$v['theme_ui_keywords_bg'].";
  color: ".$v['theme_ui_keywords_font'].";
}
".$s.".super-keyword-tag:after,
".$s.".super-wp-tag:after {
  color: ".$v['theme_ui_keywords_icon'].";
}
".$s.".super-keyword-tag:hover:after,
".$s.".super-wp-tag:hover:after {
  color: ".$v['theme_ui_keywords_icon_hover'].";
}
".$s.".super-autosuggest-tags-list .super-item:hover.super-active {
  background-color: ".$v['theme_ui_tags_list_bg_hover'].";
}

".$s.".super-load-icon {
  color: ".$v['theme_ui_loading_icon_font'].";
}
".$s.".super-slider .slider .dragger {
  background-color: ".$v['theme_ui_slider_dragger'].";
}
".$s.".super-slider .slider .track {
  background-color: ".$v['theme_ui_slider_track'].";
}
".$s.".super-slider.super-focus .dragger {
  -webkit-box-shadow: 0 0 5px 2px ".$v['theme_ui_slider_dragger'].";
  -moz-box-shadow: 0 0 5px 2px ".$v['theme_ui_slider_dragger'].";
  box-shadow: 0 0 5px 2px ".$v['theme_ui_slider_dragger'].";
}
".$s.".super-error-msg,
".$s.".super-empty-error-msg {
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
}
".$s.".super-msg.super-success a,
".$s.".super-msg.super-success .super-close {
    color: ".$v['theme_success_msg_font_color'].";
}
".$s.".super-msg.super-success:after {
    color: ".$v['theme_success_msg_icon_color'].";
}
".$s.".super-shortcode-field,
".$s.".super-keyword-filter,
".$s.".super-fileupload-button,
".$s.".super-dropdown-list,
".$s.".super-autosuggest-tags > div,
".$s.".sp-replacer {
  color: ".$v['theme_field_colors_font'].";
  border: 1px solid ".$v['theme_field_colors_border'].";
  background-color: ".$v['theme_field_colors_top'].";
  background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top']."), color-stop(1, ".$v['theme_field_colors_bottom']."));
  background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
  background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
  background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
  background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
  background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);
}
".$s.".super-rating-star {
  border: 1px solid ".$v['theme_rating_border'].";
  color: ".$v['theme_rating_color'].";
  background-color: ".$v['theme_rating_bg'].";
}
".$s.".super-rating-star.super-hover {
  color: ".$v['theme_rating_color_hover'].";
  background-color: ".$v['theme_rating_bg_hover'].";
}
".$s.".super-rating-star.super-active {
  color: ".$v['theme_rating_color_active'].";
  background-color: ".$v['theme_rating_bg_active'].";
}
".$s.".super-toggle-switch {
  border: 1px solid ".$v['theme_field_colors_border'].";
}
".$s.".super-toggle-on {
  color: ".$v['theme_ui_toggle_font'].";
  background-color: ".$v['theme_ui_toggle_bg'].";
}
".$s.".super-toggle-off {
  color: ".$v['theme_ui_toggle_disabled_font'].";
  background-color: ".$v['theme_ui_toggle_disabled_bg'].";
}
".$s.".super-quantity .super-minus-button,
".$s.".super-quantity .super-plus-button {
  color: ".$v['theme_ui_quantity_font'].";
  background-color: ".$v['theme_ui_quantity_bg'].";
}
".$s.".super-quantity .super-minus-button:hover,
".$s.".super-quantity .super-plus-button:hover {
  color: ".$v['theme_ui_quantity_font_hover'].";
  background-color: ".$v['theme_ui_quantity_bg_hover'].";
}
".$s.".super-calculator-label {
    color: ".$v['theme_calc_amount_label_color'].";
}
".$s.".super-calculator-currency {
    color: ".$v['theme_calc_amount_currency_color'].";
}
".$s.".super-calculator-amount {
    color: ".$v['theme_calc_amount_color'].";
}
".$s.".super-calculator-format {
    color: ".$v['theme_calc_amount_format_color'].";
}
".$st.".tooltipster-box {
  border: 2px solid ".$v['theme_tooltip_border'].";
}
".$st.".tooltipster-box .tooltipster-content {
  color: ".$v['theme_tooltip_font'].";
  background: ".$v['theme_tooltip_bg'].";
}
".$st.".tooltipster-arrow .tooltipster-arrow-uncropped .tooltipster-arrow-border {
  border-top-color: ".$v['theme_tooltip_arrow_border'].";
}
".$st.".tooltipster-arrow .tooltipster-arrow-uncropped .tooltipster-arrow-background {
  border-top-color: ".$v['theme_tooltip_arrow_bg'].";
}
".$s1.".super-quantity .super-minus-button,
".$s1.".super-quantity .super-plus-button {
  color: ".$v['theme_ui_quantity_bg'].";
  background: none;
}
".$s1.".super-quantity .super-minus-button:hover,
".$s1.".super-quantity .super-plus-button:hover {
  color: ".$v['theme_ui_quantity_bg_hover'].";
  background: none;
}
".$s1.".super-field-wrapper:after {
    border-bottom: 2px solid ".$v['theme_field_colors_border'].";
}
".$s1.".super-field-wrapper:before {
    border-bottom: 4px solid ".$v['theme_field_colors_border_focus'].";
}
".$s.".super-radio .super-before,
".$s.".super-checkbox .super-before {
  border: 2px solid ".$v['theme_ui_checkbox_border'].";
}
".$s.".super-focus .super-item.super-focus .super-before {
  -webkit-box-shadow: 0px 0px 5px 2px ".$v['theme_ui_checkbox_border'].";
  -moz-box-shadow: 0px 0px 5px 2px ".$v['theme_ui_checkbox_border'].";
  box-shadow: 0px 0px 5px 2px ".$v['theme_ui_checkbox_border'].";
}
".$s.".super-radio .super-before .super-after,
".$s.".super-checkbox .super-before .super-after {
  background-color: ".$v['theme_ui_checkbox_inner'].";
}
".$s.".super-radio .super-item,
".$s.".super-checkbox .super-item {
  color: ".$v['theme_ui_checkbox_label'].";
}
".$s.".super-icon {
  color: ".$v['theme_icon_color'].";
  background-color: ".$v['theme_icon_bg'].";
  border: 1px solid ".$v['theme_icon_border'].";
}
".$s.".super-focus:not(.super-rating) .super-shortcode-field,
".$s.".super-focus:not(.super-rating) .super-keyword-filter,
".$s.".super-focus:not(.super-rating) .super-fileupload-button,
".$s.".super-focus:not(.super-rating) .super-dropdown-list,
".$s.".super-focus:not(.super-rating) .sp-replacer,
".$s.".super-focus:not(.super-rating) .super-autosuggest-tags > div {
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
".$s.".super-focus:not(.super-rating) .super-icon {
  color: ".$v['theme_icon_color_focus'].";
  background-color: ".$v['theme_icon_bg_focus'].";
  border: 1px solid ".$v['theme_icon_border_focus'].";
}
".$s.".super-multipart-progress {
  border: 1px solid ".$v['theme_progress_bar_border_color']."; 
}
".$s.".super-multipart-progress .super-multipart-progress-inner {
  background-color: ".$v['theme_progress_bar_secondary_color'].";
}
".$s.".super-multipart-progress .super-multipart-progress-inner .super-multipart-progress-bar {
  background-color: ".$v['theme_progress_bar_primary_color'].";
}
".$s.".super-multipart-steps .super-multipart-step {
  border: 1px solid ".$v['theme_progress_step_border_color'].";
  background-color: ".$v['theme_progress_step_secondary_color'].";
}
".$s.".super-multipart-steps .super-multipart-step.super-active {
  color: ".$v['theme_progress_step_font_color_active'].";
}
".$s.".super-multipart-steps .super-multipart-step.super-active .super-multipart-step-wrapper {
  background-color: ".$v['theme_progress_step_primary_color_active'].";
}
".$s.".super-multipart-steps .super-multipart-step.super-error:before {
  color: ".$v['theme_error_font'].";
}
".$s.".super-multipart-steps .super-multipart-step:after {
  border-top: 1px dashed #000000;
  opacity: 0.2;
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper {
  background-color: ".$v['theme_progress_step_primary_color'].";
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper .super-multipart-step-count {
  color: ".$v['theme_progress_step_font_color'].";
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper .super-multipart-step-name {
  color: ".$v['theme_progress_step_font_color'].";
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper .super-multipart-step-description {
  color: ".$v['theme_progress_step_font_color'].";
}
".$s."::-webkit-input-placeholder {
  opacity: 1;
  color: ".$v['theme_field_colors_placeholder'].";
}
".$s.":-moz-placeholder {
  opacity: 1;
  color: ".$v['theme_field_colors_placeholder'].";
}
".$s."::-moz-placeholder {
  opacity: 1;
  color: ".$v['theme_field_colors_placeholder'].";
}
".$s.":-ms-input-placeholder {
  opacity: 1;
  color: ".$v['theme_field_colors_placeholder'].";
}
".$s.".super-dropdown:not(.super-filled) .super-item.super-placeholder {
  opacity: 1;
  color: ".$v['theme_field_colors_placeholder'].";
}
".$s.".super-focus ::-webkit-input-placeholder {
  opacity: 1;
  color: ".$v['adaptive_placeholder_focus'].";
}
".$s.".super-focus :-moz-placeholder {
  opacity: 1;
  color: ".$v['adaptive_placeholder_focus'].";
}
".$s.".super-focus ::-moz-placeholder {
  opacity: 1;
  color: ".$v['adaptive_placeholder_focus'].";
}
".$s.".super-focus :-ms-input-placeholder {
  opacity: 1;
  color: ".$v['adaptive_placeholder_focus'].";
}
".$s.".super-focus:not(.super-filled) .super-item.super-placeholder {
  opacity: 1;
  color: ".$v['adaptive_placeholder_focus'].";
}
/* @since 4.9.3 - Adaptive Placeholders */
/* Initial Color */
".$s.".super-adaptive-placeholder span {
  color:".$v['theme_field_colors_placeholder'].";
}
/* Focused Colors */
".$s.".super-focus .super-adaptive-placeholder span,
".$s.".super-adaptive-positioning span {
  color:".$v['adaptive_placeholder_focus'].";
  ".(!empty($v['adaptive_placeholder_border_focus']) ? 'border:1px solid '.$v['adaptive_placeholder_border_focus'] : 'border: 0' ).";
}
/* Filled Colors */
".$s.".super-filled .super-adaptive-placeholder span {
  color:".$v['adaptive_placeholder_filled'].";
  ".(!empty($v['adaptive_placeholder_border_filled']) ? 'border:1px solid '.$v['adaptive_placeholder_border_filled'] : 'border: 0' ).";
}
/* Filled + Focus Colors */
".$s.".super-focus.super-filled .super-adaptive-placeholder span {
    color:".$v['adaptive_placeholder_focus'].";
    ".(!empty($v['adaptive_placeholder_border_focus']) ? 'border:1px solid '.$v['adaptive_placeholder_border_focus'] : 'border: 0' ).";
}
/* Background colors */
".$placeholder_bg_focus."
".$placeholder_bg_filled; // return CSS styles
