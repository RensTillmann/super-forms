<?php
$s = '.super-form-'.$form_id.' ';
$srtl = '.super-form-'.$form_id.'.super-rtl ';
$rs1 = '.super-form-'.$form_id.'.super-window-first-responsiveness ';
$rs2 = '.super-form-'.$form_id.'.super-window-second-responsiveness ';
$rs3 = '.super-form-'.$form_id.'.super-window-third-responsiveness ';
$v = $settings;
if( !isset( $v['theme_success_msg_margin'] ) ) {
    $v['theme_success_msg_margin'] = '0px 0px 30px 0px';
}
// @since 3.3.0 - show/hide multi-part progress bar
$extra_styles = '';
if( (isset($v['theme_multipart_progress_bar'])) && ($v['theme_multipart_progress_bar']!='true') ) {
    $extra_styles .= $s.".super-multipart-progress {
    display:none;
}\n";
}
if( (isset($v['theme_multipart_steps'])) && ($v['theme_multipart_steps']!='true') ) {
    $extra_styles .= $s.".super-multipart-steps,
    ".$srtl.".super-multipart-steps {
    display:none;
}\n";
}else{
    if( (isset($v['theme_multipart_steps_hide_mobile'])) && ($v['theme_multipart_steps_hide_mobile']=='true') ) {
        $extra_styles .= $rs1.".super-multipart-steps,
".$rs2.".super-multipart-steps,
".$rs3.".super-multipart-steps {
    display:none;
}\n";
    }
}

return "
".$s.".super-msg.super-error,
".$s.".super-msg.super-success {
    margin: ".$v['theme_success_msg_margin'].";
}
".$extra_styles;
