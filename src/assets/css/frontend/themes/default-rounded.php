<?php
$s = '.super-form-'.$form_id.' ';
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
".$s.".super-checkbox .super-field-wrapper label:before,
".$s.".super-field .super-field-wrapper .super-autosuggest-tags > div > span,
".$s.".super-field.super-text.super-keyword-tags.super-string-found .super-field-wrapper .super-dropdown-ui li,
".$s.".super-field.super-text.super-keyword-tags.super-string-found .super-field-wrapper .super-dropdown-ui li span.super-wp-tag,
".$s.".super-stripe_ideal .StripeElement {
	-webkit-border-radius:4px;
	-moz-border-radius:4px;
	border-radius:4px;
}
".$s.".super-checkbox .super-field-wrapper label:after {
	-webkit-border-radius: 2px;
    -moz-border-radius: 2px;
    border-radius: 2px;
}
".$s.".super-field.super-quantity .super-field-wrapper .super-shortcode-field {
	-webkit-border-radius:0px;
	-moz-border-radius:0px;
	border-radius:0px;	
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
.super-form-".$form_id.":not(.super-rtl) .super-field.super-quantity .super-minus-button,
.super-form-".$form_id.":not(.super-rtl) .super-field.super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-icon,
.super-form-".$form_id.":not(.super-rtl) .super-field.super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-icon {
	-webkit-border-top-right-radius: 0px;
	-webkit-border-bottom-right-radius: 0px;
	-moz-border-radius-topright: 0px;
	-moz-border-radius-bottomright: 0px;
	border-top-right-radius: 0px;
	border-bottom-right-radius: 0px;
}
.super-form-".$form_id.".super-rtl .super-field.super-quantity .super-minus-button,
.super-form-".$form_id.".super-rtl .super-field.super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-icon,
.super-form-".$form_id.".super-rtl .super-field.super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-icon {
	-webkit-border-top-left-radius: 0px;
	-webkit-border-bottom-left-radius: 0px;
	-moz-border-radius-topleft: 0px;
	-moz-border-radius-bottomleft: 0px;
	border-top-left-radius: 0px;
	border-bottom-left-radius: 0px;
}
".$s.".super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(1) {
	-webkit-border-radius:0px;
	-moz-border-radius:0px;
	border-radius:0px;
}
.super-form-".$form_id.":not(.super-rtl) .super-field.super-quantity .super-plus-button,
.super-form-".$form_id.":not(.super-rtl) .super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(5),
.super-form-".$form_id.":not(.super-rtl) .super-field.super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-toggle-switch {
	-webkit-border-top-left-radius: 0px;
	-webkit-border-bottom-left-radius: 0px;
	-moz-border-radius-topleft: 0px;
	-moz-border-radius-bottomleft: 0px;
	border-top-left-radius: 0px;
	border-bottom-left-radius: 0px;	
}
.super-form-".$form_id.".super-rtl .super-field.super-quantity .super-plus-button,
.super-form-".$form_id.".super-rtl .super-rating .super-field-wrapper.super-icon-inside.super-icon-left .super-rating i:nth-child(5),
.super-form-".$form_id.".super-rtl .super-field.super-toggle .super-field-wrapper.super-icon-inside.super-icon-left .super-toggle-switch {
	-webkit-border-top-right-radius: 0px;
	-webkit-border-bottom-right-radius: 0px;
	-moz-border-radius-topright: 0px;
	-moz-border-radius-bottomright: 0px;
	border-top-right-radius: 0px;
	border-bottom-right-radius: 0px;	
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

";
?>



