<?php
$s = '.super-form-'.$form_id.' ';
$rs1 = '.super-form-'.$form_id.'.super-window-first-responsiveness ';
$rs2 = '.super-form-'.$form_id.'.super-window-second-responsiveness ';
$rs3 = '.super-form-'.$form_id.'.super-window-third-responsiveness ';
$v = $settings;

// Google fonts
if( !isset( $v['font_google_fonts'] ) ) $v['font_google_fonts'] = '';
$import_fonts = "@import url('https://fonts.googleapis.com/css2?family=PT+Sans&family=Roboto&display=swap');\n";
if($v['font_google_fonts']!=''){
    $google_fonts = explode( "\n", $v['font_google_fonts'] );  
    foreach( $google_fonts as $font ) {
        $import_fonts .= "@import url('".$font."');\n";
    }
}
// Font family
if( empty( $v['font_global_family'] ) ) $v['font_global_family'] = '"Helvetica", "Arial", sans-serif';
// Globals
if( empty( $v['font_global_size'] ) ) $v['font_global_size'] = 12;
if( empty( $v['font_global_weight'] ) ) { $v['font_global_weight'] = 'normal'; }else{ $v['font_global_weight']; }
if( empty( $v['font_global_lineheight'] ) ) { $v['font_global_lineheight'] = '1.2'; }
if( floatval($v['font_global_lineheight'])>5 ) { $v['font_global_lineheight'] = '1.2'; }
// Labels
if( empty( $v['font_label_size'] ) ) $v['font_label_size'] = 16;
if( empty( $v['font_label_weight'] ) ) { $v['font_label_weight'] = 'normal'; }else{ $v['font_label_weight']; }
if( empty( $v['font_label_lineheight'] ) ) { $v['font_label_lineheight'] = '1.2'; }
if( floatval($v['font_label_lineheight'])>5 ) { $v['font_label_lineheight'] = '1.2'; }
// Descriptions
if( empty( $v['font_description_size'] ) ) $v['font_description_size'] = 14;
if( empty( $v['font_description_weight'] ) ) { $v['font_description_weight'] = 'normal'; }else{ $v['font_description_weight']; }
if( empty( $v['font_description_lineheight'] ) ) { $v['font_description_lineheight'] = '1.2'; }
if( floatval($v['font_description_lineheight'])>5 ) { $v['font_description_lineheight'] = '1.2'; }
// Globals (mobile)
if( empty( $v['font_global_size_mobile'] ) ) $v['font_global_size_mobile'] = 16;
if( empty( $v['font_global_weight_mobile'] ) ) { $v['font_global_weight_mobile'] = 'normal'; }else{ $v['font_global_weight_mobile']; }
if( empty( $v['font_global_lineheight_mobile'] ) ) { $v['font_global_lineheight_mobile'] = '1.2'; }
if( floatval($v['font_global_lineheight_mobile'])>5 ) { $v['font_global_lineheight_mobile'] = '1.2'; }
// Labels (mobile)
if( empty( $v['font_label_size_mobile'] ) ) $v['font_label_size_mobile'] = 20;
if( empty( $v['font_label_weight_mobile'] ) ) { $v['font_label_weight_mobile'] = 'normal'; }else{ $v['font_label_weight_mobile']; }
if( empty( $v['font_label_lineheight_mobile'] ) ) { $v['font_label_lineheight_mobile'] = '1.2'; }
if( floatval($v['font_label_lineheight_mobile'])>5 ) { $v['font_label_lineheight_mobile'] = '1.2'; }
// Descriptions (mobile)
if( empty( $v['font_description_size_mobile'] ) ) $v['font_description_size_mobile'] = 16;
if( empty( $v['font_description_weight_mobile'] ) ) { $v['font_description_weight_mobile'] = 'normal'; }else{ $v['font_description_weight_mobile']; }
if( empty( $v['font_description_lineheight_mobile'] ) ) { $v['font_description_lineheight_mobile'] = '1.2'; }
if( floatval($v['font_description_lineheight_mobile'])>5 ) { $v['font_description_lineheight_mobile'] = '1.2'; }

return $import_fonts."

".$s.".super-shortcode-field,
".$s.".super-keyword-filter,
".$s.".super-keyword-tag,
".$s.".super-no-results,
".$s.".super-item,
".$s.".super-toggle,
".$s.".super-toggle-off,
".$s.".super-toggle-on,
".$s.".super-fileupload-button,
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
".$s.".super-adaptive-placeholder {
    font-family: ".$v['font_global_family'].";
    font-size: ".$v['font_global_size']."px;
    font-weight: ".$v['font_global_weight'].";
    line-height: normal;
    letter-spacing: 0;
}
".$s.".super-focus .super-adaptive-placeholder,
".$s.".super-filled .super-adaptive-placeholder {
    font-size: 10px;
}
".$s.".super-icon {
    font-size: ".$v['font_global_size']."px;
    line-height: normal;
    letter-spacing: 0;
}
".$s.".super-multipart-step-count,
".$s.".super-multipart-step-icon {
    font-size: 20px;
    line-height: normal;
    letter-spacing: 0;
}
// Labels
".$s.".super-label,
".$s.".super-group-title {
    font-size: ".$v['font_label_size']."px;
    font-weight: ".$v['font_label_weight'].";
    line-height: normal;
    letter-spacing: 0;
}
// Descriptions
".$s.".super-description {
    font-size: ".$v['font_description_size']."px;
    font-weight: ".$v['font_description_weight'].";
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
".$rs1.".super-fileupload-button,
".$rs1.".super-error-msg,
".$rs1.".super-empty-error-msg,
".$rs1.".super-wp-tag-count,
".$rs1.".super-wp-tag-desc,
".$rs1.".super-fileupload-files,
".$rs1.".super-tabs,
".$rs1.".super-int-phone_selected-dial-code,
".$rs1.".super-slider .amount {
    font-size: ".$v['font_global_size_mobile']."px;
    font-weight: ".$v['font_global_weight_mobile'].";
    line-height: normal;
    letter-spacing: 0;
}
// Labels
".$rs1.".super-label,
".$rs1.".super-group-title {
    font-size: ".$v['font_label_size_mobile']."px;
    font-weight: ".$v['font_label_weight_mobile'].";
    line-height: normal; 
    letter-spacing: 0;
}
// Descriptions
".$rs1.".super-description {
    font-size: ".$v['font_description_size_mobile']."px;
    font-weight: ".$v['font_description_weight_mobile'].";
    line-height: normal; 
    letter-spacing: 0;
}
";
