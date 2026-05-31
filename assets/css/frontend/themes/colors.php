<?php
$s = '.super-form-'.$form_id.' ';
$s1 = '.super-form-'.$form_id.'.super-style-one ';
$st = '.tooltip-super-form-'.$form_id.' ';
$v = $settings;
$v = array_filter($settings);
$global_settings = SUPER_Common::get_global_settings();
$v = array_merge($global_settings, $v);

// @since 4.9.3 - Adaptive Placeholders */
$bottom = $v['adaptive_placeholder_bg_bottom_focus'];
$top = $v['adaptive_placeholder_bg_top_focus'];
$placeholder_bg_focus = "
".$s.".super-focus .super-adaptive-placeholder span,
".$s.".super-adaptive-positioning span {
    ".(!empty($bottom) ? "background: ".$bottom."; /* Old browsers */" : "")."
    ".(!empty($top) && !empty($bottom) ? "background: -moz-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* FF3.6-15 */" : "")."
    ".(!empty($top) && !empty($bottom) ? "background: -webkit-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* Chrome10-25,Safari5.1-6 */" : "")."
    ".(!empty($top) && !empty($bottom) ? "background: linear-gradient(to bottom, ".$top." 50%, ".$bottom." 50%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */" : "")."
    ".(!empty($top) && !empty($bottom) ? "filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='".$top."', endColorstr='".$bottom."', GradientType=0 ); /* IE6-9 */" : "")."
}";
$bottom = $v['adaptive_placeholder_bg_bottom_filled'];
$top = $v['adaptive_placeholder_bg_top_filled'];
$placeholder_bg_filled = "
".$s.".super-filled .super-adaptive-placeholder span {
    ".(!empty($bottom) ? "background: ".$bottom."; /* Old browsers */" : "")."
    ".(!empty($top) && !empty($bottom) ? "background: -moz-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* FF3.6-15 */" : "")."
    ".(!empty($top) && !empty($bottom) ? "background: -webkit-linear-gradient(top, ".$top." 50%, ".$bottom." 50%); /* Chrome10-25,Safari5.1-6 */" : "")."
    ".(!empty($top) && !empty($bottom) ? "background: linear-gradient(to bottom, ".$top." 50%, ".$bottom." 50%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */" : "")."
    ".(!empty($top) && !empty($bottom) ? "filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='".$top."', endColorstr='".$bottom."', GradientType=0 ); /* IE6-9 */" : "")."
}";

return "
".$s.".super-label,
".$s.".super-group-title,
".$s.".super-toggle-prefix-label,
".$s.".super-toggle-suffix-label,
".$s.".super-html-title {
   ".(!empty($v['theme_field_label']) ? "color: ".$v['theme_field_label'].";" : "")."
}
".$s.".super-description,
".$s.".super-html-subtitle,
".$s.".super-html-content {
  ".(!empty($v['theme_field_description']) ? "color: ".$v['theme_field_description'].";" : "")."
}
".$s.".super-multipart-step.super-error:before {
  ".(!empty($v['theme_error_font']) ? "color: ".$v['theme_error_font'].";" : "")."
}
".$s.".super-dropdown-list .super-item {
  ".(!empty($v['theme_ui_dropdown_item_bg']) ? "background-color: ".$v['theme_ui_dropdown_item_bg'].";" : "")."
}
".$s.".super-dropdown-list .super-item:not(.super-placeholder):hover,
".$s.".super-dropdown-list .super-item:not(.super-placeholder).super-active,
".$s.".super-dropdown-list .super-item:not(.super-placeholder).super-focus {
  ".(!empty($v['theme_ui_dropdown_item_bg_focus']) ? "background-color: ".$v['theme_ui_dropdown_item_bg_focus'].";" : "")."
  ".(!empty($v['theme_ui_dropdown_item_font_focus']) ? "color: ".$v['theme_ui_dropdown_item_font_focus'].";" : "")."
}
".$s.".super-int-phone_country {
  ".(!empty($v['theme_ui_dropdown_item_bg']) ? "background-color: ".$v['theme_ui_dropdown_item_bg'].";" : "")."
}
".$s.".super-int-phone_country.super-int-phone_highlight,
".$s.".super-int-phone_country.super-int-phone_active {
  opacity: 0.7;
  ".(!empty($v['theme_ui_dropdown_item_bg_focus']) ? "background-color: ".$v['theme_ui_dropdown_item_bg_focus'].";" : "")."
  ".(!empty($v['theme_ui_dropdown_item_font_focus']) ? "color: ".$v['theme_ui_dropdown_item_font_focus'].";" : "")."
}
".$s.".super-int-phone_country.super-int-phone_highlight .super-int-phone_dial-code,
".$s.".super-int-phone_country.super-int-phone_active .super-int-phone_dial-code {
  ".(!empty($v['theme_ui_dropdown_item_font_focus']) ? "color: ".$v['theme_ui_dropdown_item_font_focus'].";" : "")."
}
".$s.".super-int-phone_country.super-int-phone_active {
  opacity: 1;
}
".$s.".super-dropdown-arrow {
  ".(!empty($v['theme_ui_dropdown_arrow']) ? "color: ".$v['theme_ui_dropdown_arrow'].";" : "")."
}
".$s.".super-accordion-header {
  ".(!empty($v['theme_accordion_header']) ? "background-color: ".$v['theme_accordion_header'].";" : "")."
}
".$s.".super-accordion-title {
  ".(!empty($v['theme_accordion_title']) ? "color: ".$v['theme_accordion_title'].";" : "")."
}
".$s.".super-accordion-desc {
  ".(!empty($v['theme_accordion_desc']) ? "color: ".$v['theme_accordion_desc'].";" : "")."
}
".$s.".super-accordion-header:before,
".$s.".super-accordion-header:after {
  ".(!empty($v['theme_accordion_icon']) ? "background-color: ".$v['theme_accordion_icon'].";" : "")."
}
".$s.".super-accordion-content {
  ".(!empty($v['theme_accordion_content']) ? "background-color: ".$v['theme_accordion_content'].";" : "")."
}
".$s.".super-accordion-item:hover .super-accordion-header {
  ".(!empty($v['theme_accordion_header_hover']) ? "background-color: ".$v['theme_accordion_header_hover'].";" : "")."
}
".$s.".super-accordion-item:hover .super-accordion-title {
  ".(!empty($v['theme_accordion_title_hover']) ? "color: ".$v['theme_accordion_title_hover'].";" : "")."
}
".$s.".super-accordion-item:hover .super-accordion-desc {
  ".(!empty($v['theme_accordion_desc_hover']) ? "color: ".$v['theme_accordion_desc_hover'].";" : "")."
}
".$s.".super-accordion-item:hover .super-accordion-header:before,
".$s.".super-accordion-item:hover .super-accordion-header:after {
  ".(!empty($v['theme_accordion_icon_hover']) ? "background-color: ".$v['theme_accordion_icon_hover'].";" : "")."
}
".$s.".super-accordion-item.super-active .super-accordion-header {
  ".(!empty($v['theme_accordion_header_active']) ? "background-color: ".$v['theme_accordion_header_active'].";" : "")."
}
".$s.".super-accordion-item.super-active .super-accordion-title {
  ".(!empty($v['theme_accordion_title_active']) ? "color: ".$v['theme_accordion_title_active'].";" : "")."
}
".$s.".super-accordion-item.super-active .super-accordion-desc {
  ".(!empty($v['theme_accordion_desc_active']) ? "color: ".$v['theme_accordion_desc_active'].";" : "")."
}
".$s.".super-accordion-item.super-active .super-accordion-header:before,
".$s.".super-accordion-item.super-active .super-accordion-header:after {
  ".(!empty($v['theme_accordion_icon_active']) ? "background-color: ".$v['theme_accordion_icon_active'].";" : "")."
}
".$s.".super-keyword-tag,
".$s.".super-wp-tag {
  ".(!empty($v['theme_ui_keywords_bg']) ? "background-color: ".$v['theme_ui_keywords_bg'].";" : "")."
  ".(!empty($v['theme_ui_keywords_font']) ? "color: ".$v['theme_ui_keywords_font'].";" : "")."
}
".$s.".super-keyword-tag:after,
".$s.".super-wp-tag:after {
  ".(!empty($v['theme_ui_keywords_icon']) ? "color: ".$v['theme_ui_keywords_icon'].";" : "")."
}
".$s.".super-keyword-tag:hover:after,
".$s.".super-wp-tag:hover:after {
  ".(!empty($v['theme_ui_keywords_icon_hover']) ? "color: ".$v['theme_ui_keywords_icon_hover'].";" : "")."
}
".$s.".super-autosuggest-tags-list .super-item:hover.super-active {
  ".(!empty($v['theme_ui_tags_list_bg_hover']) ? "background-color: ".$v['theme_ui_tags_list_bg_hover'].";" : "")."
}

".$s.".super-load-icon {
  ".(!empty($v['theme_ui_loading_icon_font']) ? "color: ".$v['theme_ui_loading_icon_font'].";" : "")."
}
".$s.".super-slider .slider .dragger {
  ".(!empty($v['theme_ui_slider_dragger']) ? "background-color: ".$v['theme_ui_slider_dragger'].";" : "")."
}
".$s.".super-slider .slider .track {
  ".(!empty($v['theme_ui_slider_track']) ? "background-color: ".$v['theme_ui_slider_track'].";" : "")."
}
".$s.".super-slider.super-focus .dragger {
  ".(!empty($v['theme_ui_slider_dragger']) ? "-webkit-box-shadow: 0 0 5px 2px ".$v['theme_ui_slider_dragger'].";" : "")."
  ".(!empty($v['theme_ui_slider_dragger']) ? "-moz-box-shadow: 0 0 5px 2px ".$v['theme_ui_slider_dragger'].";" : "")."
  ".(!empty($v['theme_ui_slider_dragger']) ? "box-shadow: 0 0 5px 2px ".$v['theme_ui_slider_dragger'].";" : "")."
}
".$s.".super-error-msg,
".$s.".super-empty-error-msg {
  ".(!empty($v['theme_error_font']) ? "color: ".$v['theme_error_font'].";" : "")."
}
".$s.".super-msg.super-error {
    ".(!empty($v['theme_error_msg_border_color']) ? "border: 1px solid ".$v['theme_error_msg_border_color'].";" : "")."
    ".(!empty($v['theme_error_msg_bg_color']) ? "background-color: ".$v['theme_error_msg_bg_color'].";" : "")."
    ".(!empty($v['theme_error_msg_font_color']) ? "color: ".$v['theme_error_msg_font_color'].";" : "")."
}
".$s.".super-msg.super-error a {
    ".(!empty($v['theme_error_msg_font_color']) ? "color: ".$v['theme_error_msg_font_color'].";" : "")."
}
".$s.".super-msg.super-error:after {
    ".(!empty($v['theme_error_msg_icon_color']) ? "color: ".$v['theme_error_msg_icon_color'].";" : "")."
}
".$s.".super-msg.super-success {
    ".(!empty($v['theme_success_msg_border_color']) ? "border: 1px solid".$v['theme_success_msg_border_color'].";" : "")."
    ".(!empty($v['theme_success_msg_bg_color']) ? "background-color: ".$v['theme_success_msg_bg_color'].";" : "")."
    ".(!empty($v['theme_success_msg_font_color']) ? "color: ".$v['theme_success_msg_font_color'].";" : "")."
}
".$s.".super-msg.super-success a,
".$s.".super-msg.super-success .super-close {
    ".(!empty($v['theme_success_msg_font_color']) ? "color: ".$v['theme_success_msg_font_color'].";" : "")."
}
".$s.".super-msg.super-success:after {
    ".(!empty($v['theme_success_msg_icon_color']) ? "color: ".$v['theme_success_msg_icon_color'].";" : "")."
}

".$s.".super-shortcode-field,
".$s.".super-autosuggest-tags,
".$s.".super-keyword-filter,
".$s.".super-fileupload-button,
".$s.".super-dropdown-list,
".$s.".super-int-phone_country-list,
".$s.".super-autosuggest-tags > div,
".$s.".sp-replacer {
  ".(!empty($v['theme_field_colors_font']) ? "color: ".$v['theme_field_colors_font'].";" : "")."
  ".(!empty($v['theme_field_colors_border']) ? "border: 1px solid ".$v['theme_field_colors_border'].";" : "")."
  ".(!empty($v['theme_field_colors_top']) ? "background-color: ".$v['theme_field_colors_top'].";" : "")."
  ".(!empty($v['theme_field_colors_top']) && !empty($v['theme_field_colors_bottom']) ? "background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top']."), color-stop(1, ".$v['theme_field_colors_bottom']."));" : "")."
  ".(!empty($v['theme_field_colors_top']) && !empty($v['theme_field_colors_bottom']) ? "background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top']) && !empty($v['theme_field_colors_bottom']) ? "background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top']) && !empty($v['theme_field_colors_bottom']) ? "background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top']) && !empty($v['theme_field_colors_bottom']) ? "background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top']) && !empty($v['theme_field_colors_bottom']) ? "background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top']." 25%, ".$v['theme_field_colors_bottom']." 100%);" : "")."
}
".$s.".super-int-phone_selected-dial-code {
  ".(!empty($v['theme_field_colors_font']) ? "color: ".$v['theme_field_colors_font'].";" : "")."
}
".$s.".super-int-phone_arrow { 
  ".(!empty($v['theme_field_colors_font']) ? "border-top: 4px solid ".$v['theme_field_colors_font'].";" : "")."
}
".$s.".super-rating-star {
  ".(!empty($v['theme_rating_border']) ? "border: 1px solid ".$v['theme_rating_border'].";" : "")."
  ".(!empty($v['theme_rating_color']) ? "color: ".$v['theme_rating_color'].";" : "")."
  ".(!empty($v['theme_rating_bg']) ? "background-color: ".$v['theme_rating_bg'].";" : "")."
}
".$s.".super-rating-star.super-hover {
  ".(!empty($v['theme_rating_color_hover']) ? "color: ".$v['theme_rating_color_hover'].";" : "")."
  ".(!empty($v['theme_rating_bg_hover']) ? "background-color: ".$v['theme_rating_bg_hover'].";" : "")."
}
".$s.".super-rating-star.super-active {
  ".(!empty($v['theme_rating_color_active']) ? "color: ".$v['theme_rating_color_active'].";" : "")."
  ".(!empty($v['theme_rating_bg_active']) ? "background-color: ".$v['theme_rating_bg_active'].";" : "")."
}
".$s.".super-toggle-switch {
  ".(!empty($v['theme_field_colors_border']) ? "border: 1px solid ".$v['theme_field_colors_border'].";" : "")."
}
".$s.".super-toggle-on {
  ".(!empty($v['theme_ui_toggle_font']) ? "color: ".$v['theme_ui_toggle_font'].";" : "")."
  ".(!empty($v['theme_ui_toggle_bg']) ? "background-color: ".$v['theme_ui_toggle_bg'].";" : "")."
}
".$s.".super-toggle-off {
  ".(!empty($v['theme_ui_toggle_disabled_font']) ? "color: ".$v['theme_ui_toggle_disabled_font'].";" : "")."
  ".(!empty($v['theme_ui_toggle_disabled_bg']) ? "background-color: ".$v['theme_ui_toggle_disabled_bg'].";" : "")."
}
".$s.".super-quantity .super-minus-button,
".$s.".super-quantity .super-plus-button {
  ".(!empty($v['theme_ui_quantity_font']) ? "color: ".$v['theme_ui_quantity_font'].";" : "")."
  ".(!empty($v['theme_ui_quantity_bg']) ? "background-color: ".$v['theme_ui_quantity_bg'].";" : "")."
}
".$s.".super-quantity .super-minus-button:hover,
".$s.".super-quantity .super-plus-button:hover {
  ".(!empty($v['theme_ui_quantity_font_hover']) ? "color: ".$v['theme_ui_quantity_font_hover'].";" : "")."
  ".(!empty($v['theme_ui_quantity_bg_hover']) ? "background-color: ".$v['theme_ui_quantity_bg_hover'].";" : "")."
}
".$s.".super-calculator-label {
    ".(!empty($v['theme_calc_amount_label_color']) ? "color: ".$v['theme_calc_amount_label_color'].";" : "")."
}
".$s.".super-calculator-currency {
    ".(!empty($v['theme_calc_amount_currency_color']) ? "color: ".$v['theme_calc_amount_currency_color'].";" : "")."
}
".$s.".super-calculator-amount {
    ".(!empty($v['theme_calc_amount_color']) ? "color: ".$v['theme_calc_amount_color'].";" : "")."
}
".$s.".super-calculator-format {
    ".(!empty($v['theme_calc_amount_format_color']) ? "color: ".$v['theme_calc_amount_format_color'].";" : "")."
}
".$st.".tooltipster-box {
  ".(!empty($v['theme_tooltip_border']) ? "border: 2px solid ".$v['theme_tooltip_border'].";" : "")."
}
".$st.".tooltipster-box .tooltipster-content {
  ".(!empty($v['theme_tooltip_font']) ? "color: ".$v['theme_tooltip_font'].";" : "")."
  ".(!empty($v['theme_tooltip_bg']) ? "background: ".$v['theme_tooltip_bg'].";" : "")."
}
".$st.".tooltipster-arrow .tooltipster-arrow-uncropped .tooltipster-arrow-border {
  ".(!empty($v['theme_tooltip_arrow_border']) ? "border-top-color: ".$v['theme_tooltip_arrow_border'].";" : "")."
}
".$st.".tooltipster-arrow .tooltipster-arrow-uncropped .tooltipster-arrow-background {
  ".(!empty($v['theme_tooltip_arrow_bg']) ? "border-top-color: ".$v['theme_tooltip_arrow_bg'].";" : "")."
}
".$s1.".super-quantity .super-minus-button,
".$s1.".super-quantity .super-plus-button {
  ".(!empty($v['theme_ui_quantity_bg']) ? "color: ".$v['theme_ui_quantity_bg'].";" : "")."
  background: none;
}
".$s1.".super-quantity .super-minus-button:hover,
".$s1.".super-quantity .super-plus-button:hover {
  ".(!empty($v['theme_ui_quantity_bg_hover']) ? "color: ".$v['theme_ui_quantity_bg_hover'].";" : "")."
  background: none;
}
".$s1.".super-field-wrapper:after {
    ".(!empty($v['theme_field_colors_border']) ? "border-bottom: 2px solid ".$v['theme_field_colors_border'].";" : "")."
}
".$s1.".super-field-wrapper:before {
    ".(!empty($v['theme_field_colors_border_focus']) ? "border-bottom: 4px solid ".$v['theme_field_colors_border_focus'].";" : "")."
}
".$s.".super-radio .super-before,
".$s.".super-checkbox .super-before {
  ".(!empty($v['theme_ui_checkbox_border']) ? "border: 2px solid ".$v['theme_ui_checkbox_border'].";" : "")."
}
".$s.".super-focus .super-item.super-focus .super-before {
  ".(!empty($v['theme_ui_checkbox_border']) ? "-webkit-box-shadow: 0px 0px 5px 2px ".$v['theme_ui_checkbox_border'].";" : "")."
  ".(!empty($v['theme_ui_checkbox_border']) ? "-moz-box-shadow: 0px 0px 5px 2px ".$v['theme_ui_checkbox_border'].";" : "")."
  ".(!empty($v['theme_ui_checkbox_border']) ? "box-shadow: 0px 0px 5px 2px ".$v['theme_ui_checkbox_border'].";" : "")."
}
".$s.".super-radio .super-before .super-after,
".$s.".super-checkbox .super-before .super-after {
  ".(!empty($v['theme_ui_checkbox_inner']) ? "background-color: ".$v['theme_ui_checkbox_inner'].";" : "")."
}
".$s.".super-radio .super-item,
".$s.".super-checkbox .super-item {
  ".(!empty($v['theme_ui_checkbox_label']) ? "color: ".$v['theme_ui_checkbox_label'].";" : "")."
}
".$s.".super-icon {
  ".(!empty($v['theme_icon_color']) ? "color: ".$v['theme_icon_color'].";" : "")."
  ".(!empty($v['theme_icon_bg']) ? "background-color: ".$v['theme_icon_bg'].";" : "")."
  ".(!empty($v['theme_icon_border']) ? "border: 1px solid ".$v['theme_icon_border'].";" : "")."
}
".$s.".super-focus:not(.super-rating) .super-shortcode-field,
".$s.".super-focus:not(.super-rating) .super-autosuggest-tags,
".$s.".super-focus:not(.super-rating) .super-keyword-filter,
".$s.".super-focus:not(.super-rating) .super-fileupload-button,
".$s.".super-focus:not(.super-rating) .super-dropdown-list,
".$s.".super-focus:not(.super-rating) .super-int-phone_country-list,
".$s.".super-focus:not(.super-rating) .sp-replacer,
".$s.".super-focus:not(.super-rating) .super-autosuggest-tags > div {
  ".(!empty($v['theme_field_colors_font_focus']) ? "color: ".$v['theme_field_colors_font_focus'].";" : "")."
  ".(!empty($v['theme_field_colors_top_focus']) ? "background-color: ".$v['theme_field_colors_top_focus'].";" : "")."
  ".(!empty($v['theme_field_colors_border_focus']) ? "border: solid 1px ".$v['theme_field_colors_border_focus'].";" : "")."
  ".(!empty($v['theme_field_colors_top_focus']) && !empty($v['theme_field_colors_bottom_focus']) ? "background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, ".$v['theme_field_colors_top_focus']."), color-stop(1, ".$v['theme_field_colors_bottom_focus']."));" : "")."
  ".(!empty($v['theme_field_colors_top_focus']) && !empty($v['theme_field_colors_bottom_focus']) ? "background-image: -o-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top_focus']) && !empty($v['theme_field_colors_bottom_focus']) ? "background-image: -moz-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top_focus']) && !empty($v['theme_field_colors_bottom_focus']) ? "background-image: -webkit-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top_focus']) && !empty($v['theme_field_colors_bottom_focus']) ? "background-image: -ms-linear-gradient(bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);" : "")."
  ".(!empty($v['theme_field_colors_top_focus']) && !empty($v['theme_field_colors_bottom_focus']) ? "background-image: linear-gradient(to bottom, ".$v['theme_field_colors_top_focus']." 25%, ".$v['theme_field_colors_bottom_focus']." 100%);" : "")."
}
".$s.".super-focus:not(.super-rating) .super-icon {
  ".(!empty($v['theme_icon_color_focus']) ? "color: ".$v['theme_icon_color_focus'].";" : "")."
  ".(!empty($v['theme_icon_bg_focus']) ? "background-color: ".$v['theme_icon_bg_focus'].";" : "")."
  ".(!empty($v['theme_icon_border_focus']) ? "border: 1px solid ".$v['theme_icon_border_focus'].";" : "")."
}
".$s.".super-multipart-progress {
  ".(!empty($v['theme_progress_bar_border_color']) ? "border: 1px solid ".$v['theme_progress_bar_border_color']."; " : "")."
}
".$s.".super-multipart-progress .super-multipart-progress-inner {
  ".(!empty($v['theme_progress_bar_secondary_color']) ? "background-color: ".$v['theme_progress_bar_secondary_color'].";" : "")."
}
".$s.".super-multipart-progress .super-multipart-progress-inner .super-multipart-progress-bar {
  ".(!empty($v['theme_progress_bar_primary_color']) ? "background-color: ".$v['theme_progress_bar_primary_color'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step {
  ".(!empty($v['theme_progress_step_border_color']) ? "border: 1px solid ".$v['theme_progress_step_border_color'].";" : "")."
  ".(!empty($v['theme_progress_step_secondary_color']) ? "background-color: ".$v['theme_progress_step_secondary_color'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step.super-active {
  ".(!empty($v['theme_progress_step_font_color_active']) ? "color: ".$v['theme_progress_step_font_color_active'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step.super-active .super-multipart-step-wrapper {
  ".(!empty($v['theme_progress_step_primary_color_active']) ? "background-color: ".$v['theme_progress_step_primary_color_active'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step.super-error:before {
  ".(!empty($v['theme_error_font']) ? "color: ".$v['theme_error_font'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step:after {
  border-top: 1px dashed #000000;
  opacity: 0.2;
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper {
  ".(!empty($v['theme_progress_step_primary_color']) ? "background-color: ".$v['theme_progress_step_primary_color'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper .super-multipart-step-count {
  ".(!empty($v['theme_progress_step_font_color']) ? "color: ".$v['theme_progress_step_font_color'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper .super-multipart-step-name {
  ".(!empty($v['theme_progress_step_font_color']) ? "color: ".$v['theme_progress_step_font_color'].";" : "")."
}
".$s.".super-multipart-steps .super-multipart-step .super-multipart-step-wrapper .super-multipart-step-description {
  ".(!empty($v['theme_progress_step_font_color']) ? "color: ".$v['theme_progress_step_font_color'].";" : "")."
}
".$s."::-webkit-input-placeholder {
  opacity: 1;
  ".(!empty($v['theme_field_colors_placeholder']) ? "color: ".$v['theme_field_colors_placeholder'].";" : "")."
}
".$s.":-moz-placeholder {
  opacity: 1;
  ".(!empty($v['theme_field_colors_placeholder']) ? "color: ".$v['theme_field_colors_placeholder'].";" : "")."
}
".$s."::-moz-placeholder {
  opacity: 1;
  ".(!empty($v['theme_field_colors_placeholder']) ? "color: ".$v['theme_field_colors_placeholder'].";" : "")."
}
".$s.":-ms-input-placeholder {
  opacity: 1;
  ".(!empty($v['theme_field_colors_placeholder']) ? "color: ".$v['theme_field_colors_placeholder'].";" : "")."
}
".$s.".super-dropdown:not(.super-filled) .super-item.super-placeholder {
  opacity: 1;
  ".(!empty($v['theme_field_colors_placeholder']) ? "color: ".$v['theme_field_colors_placeholder'].";" : "")."
}
".$s.".super-focus ::-webkit-input-placeholder {
  opacity: 1;
  ".(!empty($v['adaptive_placeholder_focus']) ? "color: ".$v['adaptive_placeholder_focus'].";" : "")."
}
".$s.".super-focus :-moz-placeholder {
  opacity: 1;
  ".(!empty($v['adaptive_placeholder_focus']) ? "color: ".$v['adaptive_placeholder_focus'].";" : "")."
}
".$s.".super-focus ::-moz-placeholder {
  opacity: 1;
  ".(!empty($v['adaptive_placeholder_focus']) ? "color: ".$v['adaptive_placeholder_focus'].";" : "")."
}
".$s.".super-focus :-ms-input-placeholder {
  opacity: 1;
  ".(!empty($v['adaptive_placeholder_focus']) ? "color: ".$v['adaptive_placeholder_focus'].";" : "")."
}
".$s.".super-focus:not(.super-filled) .super-item.super-placeholder {
  opacity: 1;
  ".(!empty($v['adaptive_placeholder_focus']) ? "color: ".$v['adaptive_placeholder_focus'].";" : "")."
}
/* @since 4.9.3 - Adaptive Placeholders */
/* Initial Color */
".$s.".super-adaptive-placeholder span {
  ".(!empty($v['theme_field_colors_placeholder']) ? "color:".$v['theme_field_colors_placeholder'].";" : "")."
}
/* Focused Colors */
".$s.".super-focus .super-adaptive-placeholder span,
".$s.".super-adaptive-positioning span {
  ".(!empty($v['adaptive_placeholder_focus']) ? "color:".$v['adaptive_placeholder_focus'].";" : "")."
  ".(!empty($v['adaptive_placeholder_border_focus']) ? 'border:1px solid '.$v['adaptive_placeholder_border_focus'] : 'border: 0' ).";
}
/* Filled Colors */
".$s.".super-filled .super-adaptive-placeholder span {
  ".(!empty($v['adaptive_placeholder_filled']) ? "color:".$v['adaptive_placeholder_filled'].";" : "")."
  ".(!empty($v['adaptive_placeholder_border_filled']) ? 'border:1px solid '.$v['adaptive_placeholder_border_filled'] : 'border: 0' ).";
}
/* Filled + Focus Colors */
".$s.".super-focus.super-filled .super-adaptive-placeholder span {
    ".(!empty($v['adaptive_placeholder_focus']) ? "color:".$v['adaptive_placeholder_focus'].";" : "")."
    ".(!empty($v['adaptive_placeholder_border_focus']) ? 'border:1px solid '.$v['adaptive_placeholder_border_focus'] : 'border: 0' ).";
}
/* Background colors */
".$placeholder_bg_focus."
".$placeholder_bg_filled; // return CSS styles
