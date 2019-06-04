<?php
$s = '.super-form-'.$form_id.' ';
$s_large = '.super-form-'.$form_id.'.super-field-size-large ';
$s_huge = '.super-form-'.$form_id.'.super-field-size-huge ';
$v = $settings;

return "
".$s.".super-shortcode-field,
".$s.".super-field .super-field-wrapper .super-shortcode-field,
".$s.".super-field .super-field-wrapper .super-autosuggest-tags,
".$s.".super-field-wrapper.super-icon-outside .super-icon,
".$s.".super-field-wrapper.super-icon-inside .super-icon,
".$s.".super-fileupload-button,
".$s.".super-dropdown-ui,
".$s.".super-dropdown-ui li.super-placeholder,
".$s.".super-field.super-quantity .super-minus-button,
".$s.".super-field.super-quantity .super-plus-button,
".$s.".super-rating .super-rating i:nth-child(1),
".$s.".super-rating .super-rating i:nth-child(5),
".$s.".super-toggle-switch,
".$s.".sp-replacer.super-forms,
".$s.".sp-replacer.super-forms .sp-preview-inner,
".$s.".sp-replacer.super-forms .sp-preview,
".$s." > p,
".$s.".super-field .super-field-wrapper .super-autosuggest-tags > div > span, 
".$s.".super-field.super-text.super-keyword-tags.super-string-found .super-field-wrapper .super-dropdown-ui li,
".$s.".super-field.super-text.super-keyword-tags.super-string-found .super-field-wrapper .super-dropdown-ui li span.super-wp-tag,
".$s.".super-stripe_ideal .StripeElement {
	-webkit-border-radius:18px;
	-moz-border-radius:18px;
	border-radius:18px;
}
".$s_large.".super-shortcode-field,
".$s_large.".super-autosuggest-tags,
".$s_large.".super-field .super-field-wrapper .super-shortcode-field,
".$s_large.".super-field-wrapper.super-icon-outside .super-icon,
".$s_large.".super-field-wrapper.super-icon-inside .super-icon,
".$s_large.".super-fileupload-button,
".$s_large.".super-dropdown-ui,
".$s_large.".super-dropdown-ui li.super-placeholder,
".$s.".super-field.super-quantity .super-minus-button,
".$s.".super-field.super-quantity .super-plus-button,
".$s_large.".super-rating .super-rating i:nth-child(1),
".$s_large.".super-rating .super-rating i:nth-child(5),
".$s_large.".super-toggle-switch,
".$s_large." > p,
".$s.".super-stripe_ideal .StripeElement {
	-webkit-border-radius:21px;
	-moz-border-radius:21px;
	border-radius:21px;
}
".$s_huge.".super-shortcode-field,
".$s_huge.".super-autosuggest-tags,
".$s_huge.".super-field .super-field-wrapper .super-shortcode-field,
".$s_huge.".super-field-wrapper.super-icon-outside .super-icon,
".$s_huge.".super-field-wrapper.super-icon-inside .super-icon,
".$s_huge.".super-fileupload-button,
".$s_huge.".super-dropdown-ui,
".$s_huge.".super-dropdown-ui li.super-placeholder,
".$s.".super-field.super-quantity .super-minus-button,
".$s.".super-field.super-quantity .super-plus-button,
".$s_huge.".super-rating .super-rating i:nth-child(1),
".$s_huge.".super-rating .super-rating i:nth-child(5),
".$s_huge.".super-toggle-switch,
".$s_huge." > p,
".$s.".super-stripe_ideal .StripeElement {
	-webkit-border-radius:26px;
	-moz-border-radius:26px;
	border-radius:26px;
}

".$s_large.".super-checkbox .super-field-wrapper label:before,
".$s_huge.".super-checkbox .super-field-wrapper label:before {
	-webkit-border-radius:4px;
	-moz-border-radius:4px;
	border-radius:4px;
}
".$s_large.".super-checkbox .super-field-wrapper label:after,
".$s_huge.".super-checkbox .super-field-wrapper label:after {
	-webkit-border-radius: 2px;
    -moz-border-radius: 2px;
    border-radius: 2px;
}

".$s.".super-field.super-quantity .super-field-wrapper .super-shortcode-field {
	-webkit-border-radius:0px;
	-moz-border-radius:0px;
	border-radius:0px;	
}
".$s.".super-field.super-quantity .super-minus-button,
".$s.".super-rating .super-rating i:nth-child(1),
".$s_large.".super-field.super-quantity .super-minus-button,
".$s_large.".super-rating .super-rating i:nth-child(1),
".$s_huge.".super-field.super-quantity .super-minus-button,
".$s_huge.".super-rating .super-rating i:nth-child(1) {
	-webkit-border-top-right-radius: 0px;
	-webkit-border-bottom-right-radius: 0px;
	-moz-border-radius-topright: 0px;
	-moz-border-radius-bottomright: 0px;
	border-top-right-radius: 0px;
	border-bottom-right-radius: 0px;
	width: 38px;
    padding-left: 5px;
}
".$s.".super-field.super-quantity .super-plus-button,
".$s.".super-rating .super-rating i:nth-child(5),
".$s_large.".super-field.super-quantity .super-plus-button,
".$s_large.".super-rating .super-rating i:nth-child(5),
".$s_huge.".super-field.super-quantity .super-plus-button,
".$s_huge.".super-rating .super-rating i:nth-child(5) {
	-webkit-border-top-left-radius: 0px;
	-webkit-border-bottom-left-radius: 0px;
	-moz-border-radius-topleft: 0px;
	-moz-border-radius-bottomleft: 0px;
	border-top-left-radius: 0px;
	border-bottom-left-radius: 0px;
	width: 38px;
    padding-right: 5px;	
}
".$s_large.".super-field.super-quantity .super-minus-button,
".$s_large.".super-rating .super-rating i:nth-child(1) {
	width: 48px;
    padding-left: 5px;
}
".$s_large.".super-field.super-quantity .super-plus-button,
".$s_large.".super-rating .super-rating i:nth-child(5) {
	width: 48px;
    padding-right: 5px;
}
".$s_huge.".super-field.super-quantity .super-minus-button,
".$s_huge.".super-rating .super-rating i:nth-child(1) {
	width: 58px;
    padding-left: 5px;
}
".$s_huge.".super-field.super-quantity .super-plus-button,
".$s_huge.".super-rating .super-rating i:nth-child(5) {
	width: 58px;
    padding-right: 5px;
}
".$s.".super-field:not(.super-slider):not(.super-radio):not(.super-checkbox):not(.super-rating):not(.super-toggle) .super-field-wrapper.super-icon-inside .super-icon {
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
".$s.".super-focus .super-field-wrapper.super-icon-inside .super-dropdown-ui {
    width: -moz-calc(100% - 0px)!important;
    width: calc(100% - 0px)!important;
}
".$s.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-icon,
".$s.".super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-icon,
".$s_large.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-icon,
".$s_large.".super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-icon {
	-webkit-border-top-right-radius: 0px;
	-webkit-border-bottom-right-radius: 0px;
	-moz-border-radius-topright: 0px;
	-moz-border-radius-bottomright: 0px;
	border-top-right-radius: 0px;
	border-bottom-right-radius: 0px;
}
".$s.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(1),
".$s.".super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-toggle-switch,
".$s_large.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(1),
".$s_large.".super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-toggle-switch {
	-webkit-border-top-left-radius: 0px;
	-webkit-border-bottom-left-radius: 0px;
	-moz-border-radius-topleft: 0px;
	-moz-border-radius-bottomleft: 0px;
	border-top-left-radius: 0px;
	border-bottom-left-radius: 0px;
}
".$s.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(1) {
	width: 33px;
	padding-left:0px;
}
".$s_large.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(1) {
	width: 43px;
	padding-left:0px;
}
".$s_huge.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(1) {
	width: 53px;
	padding-left:0px;
}
";
?>