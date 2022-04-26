<?php
$s = '.super-form-'.$form_id.' ';
$rs1 = '.super-form-'.$form_id.'.super-window-first-responsiveness ';
$rs2 = '.super-form-'.$form_id.'.super-window-second-responsiveness ';
$rs3 = '.super-form-'.$form_id.'.super-window-third-responsiveness ';
$v = $settings;
$v = array_filter($settings);
$global_settings = SUPER_Common::get_global_settings();
$v = array_merge($global_settings, $v);

//// Google fonts
//if( !isset( $v['font_google_fonts'] ) ) $v['font_google_fonts'] = '';
//$import_fonts = ""; // example: "@import url('https://fonts.googleapis.com/css2?family=PT+Sans&family=Roboto&display=swap');\n";
//if($v['font_google_fonts']!=''){
//    $google_fonts = explode( "\n", $v['font_google_fonts'] );  
//    foreach( $google_fonts as $font ) {
//        //$import_fonts .= "@import url('".$font."');\n";
//    }
//}
//$import_fonts .= "@import url('https://fonts.googleapis.com/css2?family=Palette+Mosaic&display=swap');";
//return $import_fonts."
return "
".$s.".super-shortcode-field,
".$s.".super-keyword-filter,
".$s.".super-keyword-tag,
".$s.".super-no-results,
".$s.".super-item,
".$s.".super-toggle,
".$s.".super-toggle-off,
".$s.".super-toggle-on,
".$s.".super-color,
".$s.".super-fileupload-button,
".$s.".super-msg,
".$s.".super-error-msg,
".$s.".super-empty-error-msg,
".$s.".super-wp-tag-count,
".$s.".super-wp-tag-desc,
".$s.".super-fileupload-files,
".$s.".super-tabs,
".$s.".super-slider .amount,
".$s.".super-slider .amount,
".$s.".super-int-phone_country,
".$s.".super-int-phone_selected-dial-code,
".$s.".super-adaptive-placeholder,
.super-loading-overlay-".$form_id." {
    ".(!empty($v['font_global_family']) ? "font-family: ".$v['font_global_family'].";" : "")."
    ".(!empty($v['font_global_size']) ? "font-size: ".$v['font_global_size']."px;" : "")."
    ".(!empty($v['font_global_weight']) ? "font-weight: ".$v['font_global_weight'].";" : "")."
    line-height: normal;
    letter-spacing: 0;
}
".$s.".super-multipart-step-count,
".$s.".super-heading-title > h1,
".$s.".super-heading-title > h2,
".$s.".super-heading-title > h3,
".$s.".super-heading-title > h4,
".$s.".super-heading-title > h5,
".$s.".super-heading-title > h6,
".$s.".super-heading-title > h7,
".$s.".super-heading-description,
".$s.".super-label,
".$s.".super-description,
".$s.".super-html-title,
".$s.".super-html-subtitle,
".$s.".super-html-content,
".$s.".super-button-name,
".$s.".super-calculator-label,
".$s.".super-calculator-label,
".$s.".super-calculator-currency-wrapper,
.tooltip-super-form-".$form_id." {
    ".(!empty($v['font_global_family']) ? "font-family: ".$v['font_global_family'].";" : "")."
}
".$s.".super-focus .super-adaptive-placeholder,
".$s.".super-filled .super-adaptive-placeholder {
    font-size: 10px;
}
".$s.".super-icon {
    ".(!empty($v['font_global_size']) ? "font-size: ".$v['font_global_size']."px;" : "")."
    line-height: normal;
    letter-spacing: 0;
}
".$s.".super-multipart-step-count,
".$s.".super-multipart-step-icon {
    font-size: 20px;
    line-height: normal;
    letter-spacing: 0;
}
".$s.".super-label,
".$s.".super-group-title {
    ".(!empty($v['font_label_size']) ? "font-size: ".$v['font_label_size']."px;" : "")."
    ".(!empty($v['font_label_weight']) ? "font-weight: ".$v['font_label_weight'].";" : "")."
    line-height: normal;
    letter-spacing: 0;
}
".$s.".super-description {
    ".(!empty($v['font_description_size']) ? "font-size: ".$v['font_description_size']."px;" : "")."
    ".(!empty($v['font_description_weight']) ? "font-weight: ".$v['font_description_weight'].";" : "")."
    line-height: normal;
    letter-spacing: 0;
}

/* Mobile font styles */
".$rs1.".super-shortcode-field,
".$rs1.".super-keyword-filter,
".$rs1.".super-keyword-tag,
".$rs1.".super-no-results,
".$rs1.".super-item,
".$rs1.".super-toggle,
".$rs1.".super-toggle-off,
".$rs1.".super-toggle-on,
".$rs1.".super-color,
".$rs1.".super-fileupload-button,
".$rs1.".super-error-msg,
".$rs1.".super-empty-error-msg,
".$rs1.".super-wp-tag-count,
".$rs1.".super-wp-tag-desc,
".$rs1.".super-fileupload-files,
".$rs1.".super-tabs,
".$rs1.".super-int-phone_selected-dial-code,
".$rs1.".super-slider .amount {
    ".(!empty($v['font_global_size_mobile']) ? "font-size: ".$v['font_global_size_mobile']."px;" : "")."
    ".(!empty($v['font_global_weight_mobile']) ? "font-weight: ".$v['font_global_weight_mobile'].";" : "")."
    line-height: normal;
    letter-spacing: 0;
}
".$rs1.".super-label,
".$rs1.".super-group-title {
    ".(!empty($v['font_label_size_mobile']) ? "font-size: ".$v['font_label_size_mobile']."px;" : "")."
    ".(!empty($v['font_label_weight_mobile']) ? "font-weight: ".$v['font_label_weight_mobile'].";" : "")."
    line-height: normal; 
    letter-spacing: 0;
}
".$rs1.".super-description {
    ".(!empty($v['font_description_size_mobile']) ? "font-size: ".$v['font_description_size_mobile']."px;" : "")."
    ".(!empty($v['font_description_weight_mobile']) ? "font-weight: ".$v['font_description_weight_mobile'].";" : "")."
    line-height: normal; 
    letter-spacing: 0;
}
";
