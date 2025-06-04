<?php
/**
 * Super Forms UI Class.
 *
 * @author      WebRehab
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_UI
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_UI' ) ) :

/**
 * SUPER_UI
 */
class SUPER_UI {

    public static function get_value($array, $keys, $field) {
        $keys = explode('.', $keys);
        foreach($keys as $key){
            if(is_array($array) && isset($array[$key])){
                $array = $array[$key];
            }else{
                if(isset($field['default'])){
                    return $field['default'];
                }
                return null;
            }
        }
        return $array;
    }

    public static function loop_over_tab_setting_nodes($s, $nodes, $prefix){
        foreach($nodes as $k => $v){
            if(isset($v['type']) && $v['type']==='repeater'){
                echo '<div class="5 sfui-setting'.(isset($v['toggle']) ? ' sfui-toggle' : '').(isset($v['vertical']) ? ' sfui-vertical' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'>';
                    echo '<label'.(isset($v['toggle']) ? ' class="sfui-toggle-label"' : '').' onclick="SUPER.ui.toggle(event, this)">';
                        if(isset($v['title'])) echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
                        if(isset($v['label'])) echo '<span class="sfui-label">' . $v['label'] . '</span>';
                    echo '</label>';
                    $prefix[] = $v['name'];
                    echo '<div class="sfui-repeater" data-r="'.$v['name'].'">';
                        $name = $v['name'];
                        if(count($prefix)>0) {
                            $name = implode('.',$prefix); //.'.'.$v['name'];
                        }
                        $items = self::get_value($s, $name, null);
                        if(!empty($items) && is_array($items)){
                            $i = 0;
                            foreach($items as $ik => $iv){
                                $prefix[] = $i;
                                echo '<div class="sfui-repeater-item'.((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').(isset($v['inline']) ? ' sfui-inline' : '').'">';
                                    // Might have children
                                    if(isset($v['nodes']) && is_array($v['nodes'])) {
                                        self::loop_over_tab_setting_nodes($s, $v['nodes'], $prefix);
                                    }
                                    echo '<div>';
                                        echo '<div style="margin-left:10px;" class="add-repeater-item-btn sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Add item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                                        echo '<div style="margin-left:0px;" class="delete-repeater-item-btn sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                                    echo '</div>';
                                echo '</div>';
                                array_pop($prefix);  
                                $i++;
                            }
                        }else{
                            echo '<div class="sfui-repeater-item'.(isset($v['inline']) ? ' sfui-inline' : '').'">';
                                // Might have children
                                if(isset($v['nodes']) && is_array($v['nodes'])) {
                                    self::loop_over_tab_setting_nodes($s, $v['nodes'], $prefix);
                                }
                                echo '<div>';
                                    echo '<div style="margin-left:10px;" class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Add item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                                    echo '<div style="margin-left:0px;" class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                                echo '</div>';
                            echo '</div>';
                        }

                        //echo SUPER_Common::safe_json_encode($iv);
                        // tmp if(isset($iv[$v['name']])){
                        // tmp     $items = $iv[$v['name']];
                        // tmp }else{
                        // tmp     $name = $v['name'];
                        // tmp     if(count($prefix)>0) $name = implode('.',$prefix).'.'.$v['name'];
                        // tmp     if($iv!==null && isset($iv[$name])){
                        // tmp         $iv = $iv[$name];
                        // tmp     }else{
                        // tmp         $items = self::get_value($s, $name, null);
                        // tmp     }
                        // tmp }
                    echo '</div>';
                echo '</div>';
                array_pop($prefix);  
                continue;
            }
            if(isset($v['toggle']) && $v['toggle']===true){
                // just a wrapper with inline or filters
                echo '<div class="6 sfui-setting'.(isset($v['toggle']) ? ' sfui-toggle' : '').((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').(isset($v['vertical']) ? ' sfui-vertical' : '').(isset($v['inline']) ? ' sfui-inline' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'>';
                    echo '<label'.(isset($v['toggle']) ? ' class="sfui-toggle-label"' : '').' onclick="SUPER.ui.toggle(event, this)">';
                        if(isset($v['title'])) echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
                        if(isset($v['label'])) echo '<span class="sfui-label">' . $v['label'] . '</span>';
                    echo '</label>';
                    if(isset($v['notice'])){
                        echo '<div class="sfui-notice'.($v['notice']==='info' ? ' sfui-yellow' : '').($v['notice']==='hint' ? ' sfui-desc' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'><p>'.$v['content'].'</p></div>';
                    }
                    if(isset($v['nodes']) && is_array($v['nodes'])) self::loop_over_tab_setting_nodes($s, $v['nodes'], $prefix);
                echo '</div>';
                continue;
            }
            if(isset($v['name'])){
                // Is field
                if(isset($v['wrap']) && $v['wrap']===false){
                    // don't wrap
                }else{
                    echo '<div class="4 sfui-setting'.(isset($v['i18n']) ? ' sfui-i18n' : '').(isset($v['tinymce']) ? ' sfui-tinymce' : '').(isset($v['width_full']) ? ' sfui-width-full' : '').(isset($v['width_auto']) ? ' sfui-width-auto' : '').(isset($v['type']) ? ' sfui-type-'.$v['type'] : '').(isset($v['inline']) ? ' sfui-inline' : '').(isset($v['vertical']) ? ' sfui-vertical' : '').((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'>';
                }
                self::print_field($s, $v, $prefix);
                // Reset to default setting buttons
                if(!empty($v['reset']) && empty($v['tinymce'])){
                    $name = $v['name'];
                    if(count($prefix)>0) $name = implode('.',$prefix).'.'.$v['name'];
                    $v['v'] = self::get_value($s, $name, null);
                    echo SUPER_Common::reset_setting_icons($v, false);
                }
                // Might have children
                if(isset($v['nodes']) && is_array($v['nodes'])) {
                    self::loop_over_tab_setting_nodes($s, $v['nodes'], $prefix);
                }
            }else{
                // Not a field, either sub, group or just a wrapper with inline or filters
                if(isset($v['sub']) && $v['sub']===true){
                    // sub
                    echo '<div class="sfui-sub-settings'.((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').(isset($v['inline']) ? ' sfui-inline' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'>';
                        if(isset($v['nodes']) && is_array($v['nodes'])) self::loop_over_tab_setting_nodes($s, $v['nodes'], $prefix);
                    echo '</div>';
                }
                if(isset($v['group']) && $v['group']===true){
                    // group
                    echo '<div class="3 sfui-setting-group'.((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').(isset($v['width_full']) ? ' sfui-width-full' : '').(isset($v['width_auto']) ? ' sfui-width-auto' : '').(isset($v['vertical']) ? ' sfui-vertical' : '').(isset($v['inline']) ? ' sfui-inline' : '').'"'.(!empty($v['group_name']) ? ' data-g="'.$v['group_name'].'"' : '').(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'>';
                        if(isset($v['wrap']) && $v['wrap']===false){
                            // don't wrap
                        }else{
                            echo '<div class="2 sfui-setting">';
                        }
                        if(!empty($v['group_name'])){
                            //'group_name' => 'instant_conditionally',
                            $prefix[] = $v['group_name'];
                            //error_log('after opening group: ' . SUPER_Common::safe_json_encode($prefix));
                        }
                        if(isset($v['nodes']) && is_array($v['nodes'])) self::loop_over_tab_setting_nodes($s, $v['nodes'], $prefix);
                        if(isset($v['wrap']) && $v['wrap']===false){
                            // don't wrap
                        }else{
                            echo '</div>';
                        }
                    echo '</div>';
                }
                if(!isset($v['sub']) && !isset($v['group']) && !isset($v['notice'])){
                    // just a wrapper with inline or filters
                    echo '<div class="1 sfui-setting'.((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').(isset($v['vertical']) ? ' sfui-vertical' : '').(isset($v['inline']) ? ' sfui-inline' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'>';
                        if(isset($v['nodes']) && is_array($v['nodes'])) self::loop_over_tab_setting_nodes($s, $v['nodes'], $prefix);
                    echo '</div>';
                }
            }
            if(isset($v['name'])){
                //array_pop($prefix);  
                if(isset($v['wrap']) && $v['wrap']===false){
                    // don't wrap
                }else{
                    echo '</div>';
                }
            }
            if(!empty($v['group_name'])){
                array_pop($prefix);  
                // tmp error_log('after closing group: ' . SUPER_Common::safe_json_encode($prefix));
            }
            if(isset($v['notice'])){
                echo '<div class="sfui-notice'.($v['notice']==='info' ? ' sfui-yellow' : '').($v['notice']==='hint' ? ' sfui-desc' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'><p>'.$v['content'].'</p></div>';
                continue;
            }

        }
    }
    public static function subline($v){
        if(isset($v['subline']) || !empty($v['accepted_values'])){
            if(!isset($v['subline'])) $v['subline'] = '';
            echo '<span class="sfui-subline"><i>' . $v['subline'];
            if(!empty($v['accepted_values'])){
                echo ' '.esc_html( 'Accepted values', 'super-forms' ).': ';
                $x = 0;
                foreach($v['accepted_values'] as $iv){
                    if($iv['v']==='signup_payment_processing') continue; // this is a system login status, don't allow users to use it
                    if($x>0) echo ', ';
                    if(isset($iv['v'])){
                        echo '<code>'.$iv['v'].'</code>'.(!empty($iv['i']) ? ' '.$iv['i'] : '');
                    }
                    $x++;
                }
            }
            echo '</i></span>';
        }
    }
    public static function print_field($s, $v, $prefix){
        $name = $v['name'];
        if(count($prefix)>0){
            $name = implode('.',$prefix).'.'.$v['name'];
        }
        // Is multicolor
        if($v['type']==='multicolor'){
            if(isset($v['title'])) echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
            if(isset($v['label'])) echo '<span class="sfui-label">' . $v['label'] . '</span>';
            if(isset($v['subline'])) echo '<span class="sfui-subline"><i>' . $v['subline'] . '</i></span>';
            foreach($v['colors'] as $name => $color){
                echo '<div class="sfui-colorpicker-wrap'.(isset($color['inline']) ? ' sfui-inline' : '').'">';
                    //echo '<div class="6 sfui-setting'.(isset($v['toggle']) ? ' sfui-toggle' : '').((isset($v['padding']) && $v['padding']===false) ? ' sfui-no-padding' : '').(isset($v['vertical']) ? ' sfui-vertical' : '').(isset($v['inline']) ? ' sfui-inline' : '').'"'.(isset($v['filter']) ? ' data-f="'.$v['filter'].'"' : '').'>';
                    if(isset($color['title'])) echo '<span class="sfui-title'.((isset($color['label'])) ? ' sfui-no-padding' : '').'">' . $color['title'] . '</span>';
                    if(isset($color['label'])) echo '<span class="sfui-label">' . $color['label'] . '</span>';
                    $value = self::get_value($s, $name, $color);
                    echo '<label class="sfui-colorpicker">';
                        echo '<input type="text" name="'.esc_attr($name).'" value="' . esc_attr($value). '" />';
                    echo '</label>';
                    echo SUPER_Common::reset_setting_icons($color, false);
                    if(isset($color['subline'])) echo '<span class="sfui-subline"><i>' . $color['subline'] . '</i></span>';
                echo '</div>';
            }
            return;
        }
        // Is field
        if($v['type']==='checkbox'){
            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                echo '<input type="checkbox" name="'.$v['name'].'" value="true"' . (self::get_value($s, $name, null)==='true' ? ' checked="checked"' : '') . ' />';
                if(isset($v['title'])){
                    echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
                }
                if(isset($v['label'])){
                    echo '<span class="sfui-label">' . $v['label'] . '</span>';
                }
                if(isset($v['subline'])){
                    echo '<span class="sfui-subline"><i>' . $v['subline'] . '</i></span>';
                }
            echo '</label>';
            return;
        }
        if($v['type']==='files'){
            echo '<label>';
            if(isset($v['title'])){
                echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
            }
            if(isset($v['label'])){
                echo '<span class="sfui-label">' . $v['label'] . '</span>';
            }
            echo '<div class="image-field browse-files" data-file-type="" data-multiple="true">';
                echo '<span class="button super-insert-files"><i class="fas fa-plus"></i> Browse files</span>';
                echo '<ul class="file-preview">';
                $value = self::get_value($s, $name, null);
                $files = array();
                if(!empty($value)) $files = explode(',', $value);
                foreach($files as $fv){
                    $file = get_attached_file($fv);
                    if($file) {
                        $url = wp_get_attachment_url($fv);
                        $filename = basename ( $file );
                        $base = includes_url() . "/images/media/";
                        $type = get_post_mime_type($fv);
                        switch ($type) {
                            case 'image/jpeg':
                            case 'image/png':
                            case 'image/gif':
                            $icon = $url; break;
                            case 'video/mpeg':
                            case 'video/mp4': 
                            case 'video/quicktime':
                            $icon = $base . "video.png"; break;
                            case 'text/csv':
                            case 'text/plain': 
                            case 'text/xml':
                            $icon = $base . "text.png"; break;
                            default:
                            $icon = $base . "document.png";
                        }
                        echo '<li data-file="'.$fv.'">';
                        echo '<div class="super-image"><img src="' . esc_url($icon) . '"></div>';
                        echo '<a href="' . esc_url($url) . '">' . $filename . '</a>';
                        echo '<a href="#" class="super-delete">'.esc_html__('Delete', 'super-forms').'</a>';
                        echo '</li>';
                    }
                }
                echo '</ul>';
                echo '<input type="hidden" name="' . $v['name'] . '" value="' . esc_attr($value) . '" onchange="SUPER.ui.updateSettings(event, this)"/>';
            echo '</div>';
            self::subline($v);
            echo '</label>';
            return;
        }
        if($v['type']==='textarea'){
            echo '<label>';
                if(isset($v['title'])){
                    echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
                }
                if(isset($v['label'])){
                    echo '<span class="sfui-label">' . $v['label'] . '</span>';
                }
                $value = self::get_value($s, $name, $v);
                if(is_array($value)){
                    $value = SUPER_Common::safe_json_encode($value, JSON_PRETTY_PRINT);
                }
                $value = wp_unslash($value);
                $value = esc_textarea($value);
                if($v['name']==='i18n' && $value==='[]') $value = '';
                if(!empty($v['tinymce'])){
                    error_log('#############################');
                    error_log(json_encode($s));
                    error_log(json_encode($v));
                    $name = $v['name'];
                    if(count($prefix)>0) $name = implode('.',$prefix).'.'.$v['name'];
                    $v['v'] = self::get_value($s, $name, null);
                    echo SUPER_Common::reset_setting_icons($v, false);
                }
                echo '<textarea'.(isset($v['tinymce']) ? ' class="sfui-textarea-tinymce"' : '').' name="'.$v['name'].'"'.(isset($v['placeholder']) ? ' placeholder="'.$v['placeholder'].'"' : '').' onchange="SUPER.ui.updateSettings(event, this)">' . $value . '</textarea>';
                //echo '<textarea'.(isset($v['tinymce']) ? ' class="sfui-textarea-tinymce"' : '').' name="'.$v['name'].'"'.(isset($v['placeholder']) ? ' placeholder="'.$v['placeholder'].'"' : '').'>' . esc_textarea(wp_unslash(self::get_value($s, $name, $v))) . '</textarea>';
                self::subline($v);
            echo '</label>';
            return;
        }
        if($v['type']==='text' || $v['type']==='hidden' || $v['type']==='number' || $v['type']==='date'){
            echo '<label>';
                if(isset($v['title'])){
                    echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
                }
                if(isset($v['label'])){
                    echo '<span class="sfui-label">' . $v['label'] . '</span>';
                }
                $value = self::get_value($s, $name, $v);
                if(isset($v['func'])){
                    if($v['func']==='listing_id'){
                        if(empty($value)){
                            $explodedName = explode('.', $name);
                            $index = intval($explodedName[count($explodedName)-2]);
                            $value = $index+1;
                        }
                    }
                    if($v['func']==='listing_shortcode'){
                        $explodedName = explode('.', $name);
                        $index = intval($explodedName[count($explodedName)-2]);
                        if(empty($s['lists'][$index]['id'])){
                            $id = $index+1;
                        }else{
                            $id = $s['lists'][$index]['id'];
                        }
                        $form_id = (isset($_GET['id']) ? absint($_GET['id']) : 0);
                        $shortcode = '['.esc_html__( 'form-not-saved-yet', 'super-forms' ).']';
                        if($form_id!=0) $shortcode = '[super_listings list=&quot;'.$id.'&quot; id=&quot;'.$form_id.'&quot;]';
                        $value = $shortcode;
                    }
                }
                echo '<input type="'.esc_attr($v['type']).'" name="'.esc_attr($v['name']).'"'.(isset($v['readonly']) ? ' readonly="readonly"' : '').(isset($v['min']) ? ' min="'.esc_attr($v['min']).'"' : '').(isset($v['max']) ? ' max="'.esc_attr($v['max']).'"' : '').(isset($v['step']) ? ' step="'.esc_attr($v['step']).'"' : '').(isset($v['placeholder']) ? ' placeholder="'.esc_attr($v['placeholder']).'"' : '').' value="' . esc_attr($value). '" onChange="SUPER.ui.updateSettings(event, this)" />';
                self::subline($v);
            echo '</label>';
            return;
        }
        if($v['type']==='radio' || $v['type']==='select'){
            echo '<label>';
                if(isset($v['title'])){
                    echo '<span class="sfui-title'.((isset($v['label'])) ? ' sfui-no-padding' : '').'">' . $v['title'] . '</span>';
                }
                if(isset($v['label'])){
                    echo '<span class="sfui-label">' . $v['label'] . '</span>';
                }
                if($v['type']==='radio'){
                    echo '<form class="sfui-setting">';
                    foreach($v['options'] as $ok => $ov){
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)"><input type="radio" name="'.$v['name'].'" value="'.esc_attr($ok).'"'.(self::get_value($s, $name, null)===$ok ? ' checked="checked"' : '').'><span class="sfui-title">'.($ov).'</span></label>';
                    }
                    echo '</form>';
                }
                if($v['type']==='select'){
                    echo '<select name="'.$v['name'].'" onChange="SUPER.ui.updateSettings(event, this)">';
                        $hadLabel = false;
                        foreach($v['options'] as $ok => $ov){
                            if(!isset($ov['items'])){
                                echo '<option'.(self::get_value($s, $name, null)===$ok ? ' selected="selected"' : '').' value="'.esc_attr($ok).'">'.$ov.'</option>';
                                continue;
                            }
                            if(isset($ov['label'])){
                                $hadLabel = true;
                                echo '<optgroup label="'.$ov['label'].'">';
                            }
                            $count = 0;
                            foreach($ov['items'] as $ook => $oov){
                                echo '<option'.(self::get_value($s, $name, null)===$ook ? ' selected="selected"' : '').' value="'.esc_attr($ook).'">'.$oov.'</option>';
                                $count++;
                                if(count($ov['items'])===$count){
                                    echo '</optgroup>';
                                }
                            }
                        }
                    echo '</select>';
                }
                self::subline($v);
            echo '</label>';
            return;
        }
    }
}
endif;
