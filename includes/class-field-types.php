<?php
/**
 * Class to handle field types like textarea dropdown etc.
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Field_Types
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Field_Types' ) ) :

/**
 * SUPER_Field_Types
 */
class SUPER_Field_Types {
        
    // Previously Created Fields
    public static function previously_created_fields($id, $field){
		$multiple = '';
		$filter = '';
        if(isset($field['multiple'])) $multiple = ' multiple';
        if(isset($field['filter'])) $filter = ' filter';
        $return  = '<div class="input">';
            $return .= '<select id="field-'.$id.'" name="'.$id.'" class="element-field previously-created-fields '.$multiple.'"'.$multiple.$filter.'>';
            foreach($field['values'] as $k => $v ) {
                $selected = '';
                if($field['default']==$k){
                    $selected = ' selected="selected"';
                }
                $return .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
            }
            $return .= '</select>';
		$return .= '</div>';
        return $return;
	}
    
    // Previously Created Product Fields
    public static function previously_created_product_fields($id, $field){
		$multiple = '';
		$filter = '';
        if(isset($field['multiple'])) $multiple = ' multiple';
        if(isset($field['filter'])) $filter = ' filter';
        $return  = '<div class="input">';
            $return .= '<select id="field-'.$id.'" name="'.$id.'" class="element-field previously-created-product-fields '.$multiple.'"'.$multiple.$filter.'>';
            foreach($field['values'] as $k => $v ) {
                $selected = '';
                if($field['default']==$k){
                    $selected = ' selected="selected"';
                }
                $return .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
            }
            $return .= '</select>';
		$return .= '</div>';
        return $return;
	}
   
    // Dropdown Items
    public static function dropdown_items( $id, $field, $data ) {
        $return = '<div class="field-info-message"></div>';
        if( isset( $data[$id] ) ) {
            $return = '';
            foreach( $data[$id] as $k => $v ) {
                $return .= '<div class="super-multi-items super-dropdown-item">';
                    if( !isset( $v['checked'] ) ) $v['checked'] = 'false';
                    $return .= '<input data-prev="'.$v['checked'].'" ' . ($id=='radio_items' || $id=='autosuggest_items' ? 'type="radio"' : 'type="checkbox"') . ( $v['checked']=='true' ? ' checked="checked"' : '' ) . '">';
                    $return .= '<div class="sorting">';
                        $return .= '<span class="up"><i class="fa fa-arrow-up"></i></span>';
                        $return .= '<span class="down"><i class="fa fa-arrow-down"></i></span>';
                    $return .= '</div>';
                    $return .= '<input type="text" placeholder="' . __( 'Label', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['label'] ) ) . '" name="label">';
                    $return .= '<input type="text" placeholder="' . __( 'Value', 'super-forms' ) . '" value="' . esc_attr( stripslashes( $v['value'] ) ) . '" name="value">';
                    $return .= '<i class="add super-add-item fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o"></i>';
                    
                    // @since v1.2.3
                    if( ($id=='checkbox_items') || ($id=='radio_items') ) {
                        if( !isset( $v['image'] ) ) $v['image'] = '';
                        $return .= '<div class="image-field browse-images">';
                        $return .= '<span class="button super-insert-image"><i class="fa fa-picture-o"></i></span>';
                        $return .= '<div class="image-preview">';
                        $image = wp_get_attachment_image_src( $v['image'], 'thumbnail' );
                        $image = !empty( $image[0] ) ? $image[0] : '';
                        if( !empty( $image ) ) {
                            $return .= '<div class="image"><img src="' . $image . '"></div>';
                            $return .= '<a href="#" class="delete">Delete</a>';
                        }
                        $return .= '</div>';
                        $return .= '<input type="hidden" name="image" value="' . $v['image'] . '" />';
                        $return .= '</div>';
                    }                

                $return .= '</div>';
            }
            $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . json_encode( $data[$id] ) . '</textarea>';
        }else{
            $return .= '<div class="super-multi-items super-dropdown-item">';
                $return .= '<div class="sorting">';
                    $return .= '<span class="up"><i class="fa fa-arrow-up"></i></span>';
                    $return .= '<span class="down"><i class="fa fa-arrow-down"></i></span>';
                $return .= '</div>';
                $return .= '<input ' . ($id=='radio_items' || $id=='autosuggest_items' ? 'type="radio"' : 'type="checkbox"') . '">';
                
                // @since v1.2.3
                if( ($id=='checkbox_items') || ($id=='radio_items') ) {
                    $return .= '<div class="image-field browse-images">';
                    $return .= '<span class="button super-insert-image"><i class="fa fa-picture-o"></i></span>';
                    $return .= '<div class="image-preview"></div>';
                    $return .= '<input type="hidden" name="image" value="" />';
                    $return .= '</div>';
                }
                
                $return .= '<input type="text" placeholder="' . __( 'Label', 'super-forms' ) . '" value="" name="label">';
                $return .= '<input type="text" placeholder="' . __( 'Value', 'super-forms' ) . '" value="" name="value">';
                $return .= '<i class="add super-add-item fa fa-plus"></i>';
                $return .= '<i class="delete fa fa-trash-o"></i>';
            $return .= '</div>';
            $return .= '<textarea name="' . $id . '" class="element-field multi-items-json"></textarea>';
        }
        return $return;
    }

    // Checkbox Items
    public static function checkbox_items( $id, $field, $data ) {
        return self::dropdown_items( $id, $field, $data );
    }

    // Radio Items
    public static function radio_items( $id, $field, $data ) {
        return self::dropdown_items( $id, $field, $data );
    }            
    
    // Image
    public static function image( $id, $field ) {
		$return  = '<div class="image-field browse-images">';
        $return .= '<span class="button super-insert-image"><i class="fa fa-plus"></i> ' . __( 'Browse images', 'super-forms' ) . '</span>';
        $return .= '<div class="image-preview">';
        $image = wp_get_attachment_image_src( $field['default'], 'thumbnail' );
        $image = !empty( $image[0] ) ? $image[0] : '';
        if( !empty( $image ) ) {
            $return .= '<div class="image"><img src="' . $image . '"></div>';
            $return .= '<a href="#" class="delete">Delete</a>';
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['default'] ) . '" id="field-' . $id . '" class="element-field" />';
        $return .= '</div>';
		return $return;
    }

    // File
    // @since   1.0.6
    public static function file( $id, $field ) {
        $return  = '<div class="image-field browse-files">';
        $return .= '<span class="button super-insert-files"><i class="fa fa-plus"></i> ' . __( 'Browse files', 'super-forms' ) . '</span>';
        $return .= '<div class="file-preview">';
        $file = get_attached_file($field['default']);
        if( $file ) {
            $url = wp_get_attachment_url($field['default']);
            $filename = basename ( $file );
            $base = includes_url() . "/images/media/";
            $type = get_post_mime_type($field['default']);
            switch ($type) {
                case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                  $icon = $base . "image.png"; break;
                case 'video/mpeg':
                case 'video/mp4': 
                case 'video/quicktime':
                  $icon = $base . "video.png"; break;
                case 'text/csv':
                case 'text/plain': 
                case 'text/xml':
                  $icon = $base . "text.png"; break;
                default:
                  $icon = $base . "file.png";
            }

            $return .= '<div class="image"><img src="' . $icon . '"></div>';
            $return .= '<a href="' . $url . '">' . $filename . '</a>';
            $return .= '<a href="#" class="delete">Delete</a>';
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['default'] ) . '" id="field-' . $id . '" class="element-field" />';
        $return .= '</div>';
        return $return;
    }

    //TinyMCE
    public static function tiny_mce($id, $field){
        $return = '';
        $return .= '<div class="super-element super-shortcode-tinymce super-shortcode-target-content">';
        $content = stripslashes($field['default']);
        ob_start();
        wp_editor( $content, $id , array('editor_class' => 'super-advanced-textarea super-tinymce', 'media_buttons' => true ) );
        $return .= ob_get_clean();
        $return .= '</div>';
        return $return;
    }
    
    //Number slider
    public static function slider($id, $field){
		$return  = '<div class="slider-field">';
        $return .= '<input type="text" name="'.$id.'" value="'.esc_attr($field['default']).'" id="field-'.$id.'" data-steps="'.$field['steps'].'" data-min="'.$field['min'].'" data-max="'.$field['max'].'" class="element-field" />';
        $return .= '</div>';
		return $return;
	}
    
    //Input field    
    public static function text( $id, $field ) {
        $return  = '<div class="input">';
            $return .= '<input type="text" id="field-' . $id . '"';
            if( isset( $field['placeholder'] ) ) {
                $return .= ( $field['placeholder']!='' ? 'placeholder="' . $field['placeholder'] . '"' : '' );
            }
            if( isset( $field['required'] ) ) {
                $return .= ( $field['required']==true ? 'required="true"' : '');
            }
            if( isset( $field['maxlength'] ) ) {
                $return .= ( $field['maxlength'] > 0 ? 'maxlength="' . $field['maxlength'] . '"' : '' );
            }
            $return .= 'name="' . $id . '" class="element-field" value="' . esc_attr( stripslashes( $field['default'] ) ) . '" />';
        $return .= '</div>';
        return $return;
    }

    //Checkbox field
    public static function checkbox( $id, $field ) {
        $return = '';
        $return .= '<div class="super-checkbox">';
        foreach( $field['values'] as $k => $v ) {
            $return .= '<label><input type="checkbox" value="' . $k . '" ' . ($field['default']==$k ? 'checked="checked"' : '') . '>' . $v . '</label>';
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="' . $id . '" value="' . esc_attr( $field['default'] ) . '" id="field-' . $id . '" class="element-field" />';
        return $return;
    }

    //Input field    
    public static function password($id, $field){
        $return  = '<div class="input">';
            $return .= '<input type="password" id="field-'.$id.'"';
            if(isset($field['placeholder'])){
                $return .= ($field['placeholder']!='' ? 'placeholder="'.$field['placeholder'].'"' : '');
            }
            if(isset($field['required'])){
                $return .= ($field['required']==true ? 'required="true"' : '');
            }
            if(isset($field['maxlength'])){
                $return .= ($field['maxlength'] > 0 ? 'maxlength="' . $field['maxlength'] . '"' : '');
            }
            $return .= 'name="'.$id.'" class="element-field" value="' . esc_attr( $field['default'] ) . '" />';
        $return .= '</div>';
        return $return;
    }

    //Textarea  
	public static function textarea( $id, $field ) {
		$field = wp_parse_args( $field, array(
			'rows'    => 3,
			'default' => ''
		) );
		$return = '<textarea name="' . $id . '" id="super-generator-attr-' . $id . '" ';
        if(isset($field['placeholder'])){
            $return .= ($field['placeholder']!='' ? 'placeholder="'.$field['placeholder'].'"' : '');
        }        
        if(isset($field['required'])){
            $return .= ($field['required']==true ? 'required="true" ' : '');
        }
        $return .= 'rows="' . $field['rows'] . '" class="element-field">' . esc_textarea(stripslashes($field['default'])) . '</textarea>';
        return $return;
	}
    
    //Conditions
    public static function conditions( $id, $field, $data ) {
        $options = array(
            'contains'=>'?? Contains',
            'equal'=>'== Equal',
            'not_equal'=>'!= Not equal',
            'greater_than'=>'> Greater than',
            'less_than'=>'<  Less than',
            'greater_than_or_equal'=>'>= Greater than or equal to',
            'less_than_or_equal'=>'<= Less than or equal',            
        );
        if( ( isset( $data[$id] ) ) && ( $data[$id]!='' ) ) {
            $return = '';
            foreach( $data[$id] as $k => $v ) {
                if( !isset( $v['and_method'] ) ) $v['and_method'] = '';
                if( !isset( $v['field_and'] ) ) $v['field_and'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( !isset( $v['value_and'] ) ) $v['value_and'] = '';
                $return .= '<div class="super-multi-items super-conditional-item">';
                    $return .= '<select name="conditional_field" data-value="' . $v['field'] . '"></select>';
                    $return .= '<select name="conditional_logic">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value'] . '" name="conditional_value">';
                    $return .= '<select name="conditional_and_method">';
                        $return .= '<option selected="selected" value="">- select -</option>';
                        $return .= '<option' . ('and'==$v['and_method'] ? ' selected="selected"' : '') . '  value="and">AND</option>';
                        $return .= '<option' . ('or'==$v['and_method'] ? ' selected="selected"' : '') . '  value="or">OR</option>';
                    $return .= '</select>';
                    $return .= '<select name="conditional_field_and" data-value="' . $v['field_and'] . '"></select>';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="add fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                    $return .= '<span class="line-break"></span>';
                $return .= '</div>';
            }
        }else{
            $return  = '<div class="super-multi-items super-conditional-item">';
                $return .= '<select name="conditional_field" data-value=""></select>';
                $return .= '<select name="conditional_logic">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value">';
                $return .= '<select name="conditional_and_method">';
                    $return .= '<option selected="selected" value="">- select -</option>';
                    $return .= '<option value="and">AND</option>';
                    $return .= '<option value="or">OR</option>';
                $return .= '</select>';
                $return .= '<select name="conditional_field_and" data-value=""></select>';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="add fa fa-plus"></i>';
                $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                $return .= '<span class="line-break"></span>';
            $return .= '</div>';
        }
        if( is_array( $field['default'] ) ) $field['default'] = json_encode( $field['default'] );
        $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . $field['default'] . '</textarea>';
        return $return;
    }

    // @since 1.2.7 Variable Conditions
    public static function variable_conditions( $id, $field, $data ) {
        $options = array(
            'contains'=>'?? Contains',
            'equal'=>'== Equal',
            'not_equal'=>'!= Not equal',
            'greater_than'=>'> Greater than',
            'less_than'=>'<  Less than',
            'greater_than_or_equal'=>'>= Greater than or equal to',
            'less_than_or_equal'=>'<= Less than or equal',            
        );
        if( ( isset( $data[$id] ) ) && ( $data[$id]!='' ) ) {
            $return = '';
            foreach( $data[$id] as $k => $v ) {
                if( !isset( $v['and_method'] ) ) $v['and_method'] = '';
                if( !isset( $v['field_and'] ) ) $v['field_and'] = '';
                if( !isset( $v['logic_and'] ) ) $v['logic_and'] = '';
                if( !isset( $v['value_and'] ) ) $v['value_and'] = '';
                $return .= '<div class="super-multi-items super-conditional-item">';
                    $return .= '<select name="conditional_field" data-value="' . $v['field'] . '"></select>';
                    $return .= '<select name="conditional_logic">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value'] . '" name="conditional_value">';
                    $return .= '<select name="conditional_and_method">';
                        $return .= '<option selected="selected" value="">- select -</option>';
                        $return .= '<option' . ('and'==$v['and_method'] ? ' selected="selected"' : '') . '  value="and">AND</option>';
                        $return .= '<option' . ('or'==$v['and_method'] ? ' selected="selected"' : '') . '  value="or">OR</option>';
                    $return .= '</select>';
                    $return .= '<select name="conditional_field_and" data-value="' . $v['field_and'] . '"></select>';
                    $return .= '<select name="conditional_logic_and">';
                        $return .= '<option selected="selected" value="">---</option>';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic_and'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value_and'] . '" name="conditional_value_and">';
                    $return .= '<i class="add fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                    $return .= '<span class="line-break"></span>';
                    $return .= '<p>' . __( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
                    $return .= '<input type="text" placeholder="New value" value="' . $v['new_value'] . '" name="conditional_new_value">';
                    $return .= '</div>';
            }
        }else{
            $return  = '<div class="super-multi-items super-conditional-item">';
                $return .= '<select name="conditional_field" data-value=""></select>';
                $return .= '<select name="conditional_logic">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value">';
                $return .= '<select name="conditional_and_method">';
                    $return .= '<option selected="selected" value="">- select -</option>';
                    $return .= '<option value="and">AND</option>';
                    $return .= '<option value="or">OR</option>';
                $return .= '</select>';
                $return .= '<select name="conditional_field_and" data-value=""></select>';
                $return .= '<select name="conditional_logic_and">';
                    $return .= '<option selected="selected" value="">---</option>';
                    foreach( $options as $ok => $ov ) {
                        $return .= '<option value="' . $ok . '">' . $ov . '</option>';
                    }
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value_and">';
                $return .= '<i class="add fa fa-plus"></i>';
                $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                $return .= '<span class="line-break"></span>';
                $return .= '<p>' . __( 'When above conditions are met set following value:', 'super-forms' ) . '</p>';
                $return .= '<input type="text" placeholder="New value" value="" name="conditional_new_value">';
                $return .= '</div>';
        }
        if( is_array( $field['default'] ) ) $field['default'] = json_encode( $field['default'] );
        $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . $field['default'] . '</textarea>';
        return $return;
    }
    

 
    //Time field    
    public static function time($id, $field){
        $return  = '<div class="input">';
            $return .= '<input type="text" id="field-'.$id.'"';
            if(isset($field['required'])){
                $return .= ($field['required']==true ? 'required="true"' : '');
            }
            $return .= 'name="'.$id.'" data-format="H:i" data-step="5" class="element-field super-timepicker" value="'.esc_attr($field['default']).'" />';
		$return .= '</div>';
        return $return;
	}
    
    //Dropdown - Select field
    public static function select($id, $field){
		$multiple = '';
		$filter = '';
        if( isset( $field['multiple'] ) ) $multiple = ' multiple';
        if( isset( $field['filter'] ) ) $filter = ' filter';
        $return  = '<div class="input">';
            $return .= '<select id="field-' . $id . '" name="' . $id . '" class="element-field ' . $multiple . '"' . $multiple . $filter . '>';
            foreach( $field['values'] as $k => $v ) {
                $selected = '';
                if( ( isset( $field['multiple'] ) ) && ( $field['default']!='' ) ) {
                    if( in_array( $k, $field['default'] ) ) {
                        $selected = ' selected="selected"';
                    }
                }else{
                    if( $field['default']==$k ) {
                        $selected = ' selected="selected"';
                    }
                }
                $return .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $return .= '</select>';
            if( isset( $field['info'] ) ) $return .= '<p>' . $field['info'] . '</p>';
		$return .= '</div>';
        return $return;
	}
    
    //Color picker
	public static function color($id, $field){
		$return  = '<div class="color-picker-container">';
            $return .= '<div class="color-picker">';
                $return .= '<input type="text" id="field-'.$id.'" name="'.$id.'" class="element-field" value="'.esc_attr($field['default']).'" />';
            $return .= '</div>';
        $return .= '</div>';
        return $return;
	}
    
    //Multi Color picker
	public static function multicolor($id, $field){
        $return = '<div class="input">';
        foreach($field['colors'] as $k => $v){
            $return .= '<div class="color-picker-container">';
                $return .= '<div class="color-picker-label">'.$v['label'].'</div>';
                $return .= '<div class="color-picker">';
                    $return .= '<input type="text" id="field-'.$k.'" name="'.$k.'" class="element-field" value="'.esc_attr($v['default']).'" />';
                $return .= '</div>';
            $return .= '</div>';
        }
        $return .= '</div>';
        return $return;
    }
    
    //Icon list
    public static function icon($id, $field){
		$return  = '<div class="super-icon-field">';
        $icons = self::icons();
        $return .= '<div class="super-icon-search"><input type="text" placeholder="Filter icons" /></div>';
        $return .= '<div class="super-icon-list">';
        foreach($icons as $k => $v){
            if($field['default']==$v){
                 $return .= '<i class="fa fa-'.$v.' active"></i>';
            }else{
                 $return .= '<i class="fa fa-'.$v.'"></i>';
            }
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="'.$id.'" value="'.esc_attr($field['default']).'" id="field-'.$id.'" class="element-field" />';
        $return .= '</div>';
		return $return;
	
    }
    
    // Available Icons
	public static function icons() {
		$icon_array = apply_filters( 
            'super_icons', 
            array(
                'adjust',
                'american-sign-language-interpreting',
                'anchor',
                'archive',
                'area-chart',
                'arrows',
                'arrows-h',
                'arrows-v',
                'asl-interpreting',
                'assistive-listening-systems',
                'asterisk',
                'at',
                'audio-description',
                'automobile',
                'balance-scale',
                'ban',
                'bank',
                'bar-chart',
                'bar-chart-o',
                'barcode',
                'bars',
                'battery-0',
                'battery-1',
                'battery-2',
                'battery-3',
                'battery-4',
                'battery-empty',
                'battery-full',
                'battery-half',
                'battery-quarter',
                'battery-three-quarters',
                'bed',
                'beer',
                'bell',
                'bell-o',
                'bell-slash',
                'bell-slash-o',
                'bicycle',
                'binoculars',
                'birthday-cake',
                'blind',
                'bluetooth',
                'bluetooth-b',
                'bolt',
                'bomb',
                'book',
                'bookmark',
                'bookmark-o',
                'braille',
                'briefcase',
                'bug',
                'building',
                'building-o',
                'bullhorn',
                'bullseye',
                'bus',
                'cab',
                'calculator',
                'calendar',
                'calendar-check-o',
                'calendar-minus-o',
                'calendar-o',
                'calendar-plus-o',
                'calendar-times-o',
                'camera',
                'camera-retro',
                'car',
                'caret-square-o-down',
                'caret-square-o-left',
                'caret-square-o-right',
                'caret-square-o-up',
                'cart-arrow-down',
                'cart-plus',
                'cc',
                'certificate',
                'check',
                'check-circle',
                'check-circle-o',
                'check-square',
                'check-square-o',
                'child',
                'circle',
                'circle-o',
                'circle-o-notch',
                'circle-thin',
                'clock-o',
                'clone',
                'close',
                'cloud',
                'cloud-download',
                'cloud-upload',
                'code',
                'code-fork',
                'coffee',
                'cog',
                'cogs',
                'comment',
                'comment-o',
                'commenting',
                'commenting-o',
                'comments',
                'comments-o',
                'compass',
                'copyright',
                'creative-commons',
                'credit-card',
                'credit-card-alt',
                'crop',
                'crosshairs',
                'cube',
                'cubes',
                'cutlery',
                'dashboard',
                'database',
                'deaf',
                'deafness',
                'desktop',
                'diamond',
                'dot-circle-o',
                'download',
                'edit',
                'ellipsis-h',
                'ellipsis-v',
                'envelope',
                'envelope-o',
                'envelope-square',
                'eraser',
                'exchange',
                'exclamation',
                'exclamation-circle',
                'exclamation-triangle',
                'external-link',
                'external-link-square',
                'eye',
                'eye-slash',
                'eyedropper',
                'fax',
                'feed',
                'female',
                'fighter-jet',
                'file-archive-o',
                'file-audio-o',
                'file-code-o',
                'file-excel-o',
                'file-image-o',
                'file-movie-o',
                'file-pdf-o',
                'file-photo-o',
                'file-picture-o',
                'file-powerpoint-o',
                'file-sound-o',
                'file-video-o',
                'file-word-o',
                'file-zip-o',
                'film',
                'filter',
                'fire',
                'fire-extinguisher',
                'flag',
                'flag-checkered',
                'flag-o',
                'flash',
                'flask',
                'folder',
                'folder-o',
                'folder-open',
                'folder-open-o',
                'frown-o',
                'futbol-o',
                'gamepad',
                'gavel',
                'gear',
                'gears',
                'gift',
                'glass',
                'globe',
                'graduation-cap',
                'group',
                'hand-grab-o',
                'hand-lizard-o',
                'hand-paper-o',
                'hand-peace-o',
                'hand-pointer-o',
                'hand-rock-o',
                'hand-scissors-o',
                'hand-spock-o',
                'hand-stop-o',
                'hard-of-hearing',
                'hashtag',
                'hdd-o',
                'headphones',
                'heart',
                'heart-o',
                'heartbeat',
                'history',
                'home',
                'hotel',
                'hourglass',
                'hourglass-1',
                'hourglass-2',
                'hourglass-3',
                'hourglass-end',
                'hourglass-half',
                'hourglass-o',
                'hourglass-start',
                'i-cursor',
                'image',
                'inbox',
                'industry',
                'info',
                'info-circle',
                'institution',
                'key',
                'keyboard-o',
                'language',
                'laptop',
                'leaf',
                'legal',
                'lemon-o',
                'level-down',
                'level-up',
                'life-bouy',
                'life-buoy',
                'life-ring',
                'life-saver',
                'lightbulb-o',
                'line-chart',
                'location-arrow',
                'lock',
                'low-vision',
                'magic',
                'magnet',
                'mail-forward',
                'mail-reply',
                'mail-reply-all',
                'male',
                'map',
                'map-marker',
                'map-o',
                'map-pin',
                'map-signs',
                'meh-o',
                'microphone',
                'microphone-slash',
                'minus',
                'minus-circle',
                'minus-square',
                'minus-square-o',
                'mobile',
                'mobile-phone',
                'money',
                'moon-o',
                'mortar-board',
                'motorcycle',
                'mouse-pointer',
                'music',
                'navicon',
                'newspaper-o',
                'object-group',
                'object-ungroup',
                'paint-brush',
                'paper-plane',
                'paper-plane-o',
                'paw',
                'pencil',
                'pencil-square',
                'pencil-square-o',
                'percent',
                'phone',
                'phone-square',
                'photo',
                'picture-o',
                'pie-chart',
                'plane',
                'plug',
                'plus',
                'plus-circle',
                'plus-square',
                'plus-square-o',
                'power-off',
                'print',
                'puzzle-piece',
                'qrcode',
                'question',
                'question-circle',
                'question-circle-o',
                'quote-left',
                'quote-right',
                'random',
                'recycle',
                'refresh',
                'registered',
                'remove',
                'reorder',
                'reply',
                'reply-all',
                'retweet',
                'road',
                'rocket',
                'rss',
                'rss-square',
                'search',
                'search-minus',
                'search-plus',
                'send',
                'send-o',
                'server',
                'share',
                'share-alt',
                'share-alt-square',
                'share-square',
                'share-square-o',
                'shield',
                'ship',
                'shopping-bag',
                'shopping-basket',
                'shopping-cart',
                'sign-in',
                'sign-language',
                'sign-out',
                'signal',
                'signing',
                'sitemap',
                'sliders',
                'smile-o',
                'soccer-ball-o',
                'sort',
                'sort-alpha-asc',
                'sort-alpha-desc',
                'sort-amount-asc',
                'sort-amount-desc',
                'sort-asc',
                'sort-desc',
                'sort-down',
                'sort-numeric-asc',
                'sort-numeric-desc',
                'sort-up',
                'space-shuttle',
                'spinner',
                'spoon',
                'square',
                'square-o',
                'star',
                'star-half',
                'star-half-empty',
                'star-half-full',
                'star-half-o',
                'star-o',
                'sticky-note',
                'sticky-note-o',
                'street-view',
                'suitcase',
                'sun-o',
                'support',
                'tablet',
                'tachometer',
                'tag',
                'tags',
                'tasks',
                'taxi',
                'television',
                'terminal',
                'thumb-tack',
                'thumbs-down',
                'thumbs-o-down',
                'thumbs-o-up',
                'thumbs-up',
                'ticket',
                'times',
                'times-circle',
                'times-circle-o',
                'tint',
                'toggle-down',
                'toggle-left',
                'toggle-off',
                'toggle-on',
                'toggle-right',
                'toggle-up',
                'trademark',
                'trash',
                'trash-o',
                'tree',
                'trophy',
                'truck',
                'tty',
                'tv',
                'umbrella',
                'universal-access',
                'university',
                'unlock',
                'unlock-alt',
                'unsorted',
                'upload',
                'user',
                'user-plus',
                'user-secret',
                'user-times',
                'users',
                'video-camera',
                'volume-control-phone',
                'volume-down',
                'volume-off',
                'volume-up',
                'warning',
                'wheelchair',
                'wheelchair-alt',
                'wifi',
                'wrench',
                'american-sign-language-interpreting',
                'asl-interpreting',
                'assistive-listening-systems',
                'audio-description',
                'blind',
                'braille',
                'cc',
                'deaf',
                'deafness',
                'hard-of-hearing',
                'low-vision',
                'question-circle-o',
                'sign-language',
                'signing',
                'tty',
                'universal-access',
                'volume-control-phone',
                'wheelchair',
                'wheelchair-alt',
                'hand-grab-o',
                'hand-lizard-o',
                'hand-o-down',
                'hand-o-left',
                'hand-o-right',
                'hand-o-up',
                'hand-paper-o',
                'hand-peace-o',
                'hand-pointer-o',
                'hand-rock-o',
                'hand-scissors-o',
                'hand-spock-o',
                'hand-stop-o',
                'thumbs-down',
                'thumbs-o-down',
                'thumbs-o-up',
                'thumbs-up',
                'ambulance',
                'automobile',
                'bicycle',
                'bus',
                'cab',
                'car',
                'fighter-jet',
                'motorcycle',
                'plane',
                'rocket',
                'ship',
                'space-shuttle',
                'subway',
                'taxi',
                'train',
                'truck',
                'wheelchair',
                'genderless',
                'intersex',
                'mars',
                'mars-double',
                'mars-stroke',
                'mars-stroke-h',
                'mars-stroke-v',
                'mercury',
                'neuter',
                'transgender',
                'transgender-alt',
                'venus',
                'venus-double',
                'venus-mars',
                'file',
                'file-archive-o',
                'file-audio-o',
                'file-code-o',
                'file-excel-o',
                'file-image-o',
                'file-movie-o',
                'file-o',
                'file-pdf-o',
                'file-photo-o',
                'file-picture-o',
                'file-powerpoint-o',
                'file-sound-o',
                'file-text',
                'file-text-o',
                'file-video-o',
                'file-word-o',
                'file-zip-o',
                'circle-o-notch',
                'cog',
                'gear',
                'refresh',
                'spinner',
                'check-square',
                'check-square-o',
                'circle',
                'circle-o',
                'dot-circle-o',
                'minus-square',
                'minus-square-o',
                'plus-square',
                'plus-square-o',
                'square',
                'square-o',
                'cc-amex',
                'cc-diners-club',
                'cc-discover',
                'cc-jcb',
                'cc-mastercard',
                'cc-paypal',
                'cc-stripe',
                'cc-visa',
                'credit-card',
                'credit-card-alt',
                'google-wallet',
                'paypal',
                'area-chart',
                'bar-chart',
                'bar-chart-o',
                'line-chart',
                'pie-chart',
                'bitcoin',
                'btc',
                'cny',
                'dollar',
                'eur',
                'euro',
                'gbp',
                'gg',
                'gg-circle',
                'ils',
                'inr',
                'jpy',
                'krw',
                'money',
                'rmb',
                'rouble',
                'rub',
                'ruble',
                'rupee',
                'shekel',
                'sheqel',
                'try',
                'turkish-lira',
                'usd',
                'won',
                'yen',
                'align-center',
                'align-justify',
                'align-left',
                'align-right',
                'bold',
                'chain',
                'chain-broken',
                'clipboard',
                'columns',
                'copy',
                'cut',
                'dedent',
                'eraser',
                'file',
                'file-o',
                'file-text',
                'file-text-o',
                'files-o',
                'floppy-o',
                'font',
                'header',
                'indent',
                'italic',
                'link',
                'list',
                'list-alt',
                'list-ol',
                'list-ul',
                'outdent',
                'paperclip',
                'paragraph',
                'paste',
                'repeat',
                'rotate-left',
                'rotate-right',
                'save',
                'scissors',
                'strikethrough',
                'subscript',
                'superscript',
                'table',
                'text-height',
                'text-width',
                'th',
                'th-large',
                'th-list',
                'underline',
                'undo',
                'unlink',
                'angle-double-down',
                'angle-double-left',
                'angle-double-right',
                'angle-double-up',
                'angle-down',
                'angle-left',
                'angle-right',
                'angle-up',
                'arrow-circle-down',
                'arrow-circle-left',
                'arrow-circle-o-down',
                'arrow-circle-o-left',
                'arrow-circle-o-right',
                'arrow-circle-o-up',
                'arrow-circle-right',
                'arrow-circle-up',
                'arrow-down',
                'arrow-left',
                'arrow-right',
                'arrow-up',
                'arrows',
                'arrows-alt',
                'arrows-h',
                'arrows-v',
                'caret-down',
                'caret-left',
                'caret-right',
                'caret-square-o-down',
                'caret-square-o-left',
                'caret-square-o-right',
                'caret-square-o-up',
                'caret-up',
                'chevron-circle-down',
                'chevron-circle-left',
                'chevron-circle-right',
                'chevron-circle-up',
                'chevron-down',
                'chevron-left',
                'chevron-right',
                'chevron-up',
                'exchange',
                'hand-o-down',
                'hand-o-left',
                'hand-o-right',
                'hand-o-up',
                'long-arrow-down',
                'long-arrow-left',
                'long-arrow-right',
                'long-arrow-up',
                'toggle-down',
                'toggle-left',
                'toggle-right',
                'toggle-up',
                'arrows-alt',
                'backward',
                'compress',
                'eject',
                'expand',
                'fast-backward',
                'fast-forward',
                'forward',
                'pause',
                'pause-circle',
                'pause-circle-o',
                'play',
                'play-circle',
                'play-circle-o',
                'random',
                'step-backward',
                'step-forward',
                'stop',
                'stop-circle',
                'stop-circle-o',
                'youtube-play',
                '500px',
                'adn',
                'amazon',
                'android',
                'angellist',
                'apple',
                'behance',
                'behance-square',
                'bitbucket',
                'bitbucket-square',
                'bitcoin',
                'black-tie',
                'bluetooth',
                'bluetooth-b',
                'btc',
                'buysellads',
                'cc-amex',
                'cc-diners-club',
                'cc-discover',
                'cc-jcb',
                'cc-mastercard',
                'cc-paypal',
                'cc-stripe',
                'cc-visa',
                'chrome',
                'codepen',
                'codiepie',
                'connectdevelop',
                'contao',
                'css3',
                'dashcube',
                'delicious',
                'deviantart',
                'digg',
                'dribbble',
                'dropbox',
                'drupal',
                'edge',
                'empire',
                'envira',
                'expeditedssl',
                'fa',
                'facebook',
                'facebook-f',
                'facebook-official',
                'facebook-square',
                'firefox',
                'first-order',
                'flickr',
                'font-awesome',
                'fonticons',
                'fort-awesome',
                'forumbee',
                'foursquare',
                'ge',
                'get-pocket',
                'gg',
                'gg-circle',
                'git',
                'git-square',
                'github',
                'github-alt',
                'github-square',
                'gitlab',
                'gittip',
                'glide',
                'glide-g',
                'google',
                'google-plus',
                'google-plus-circle',
                'google-plus-official',
                'google-plus-square',
                'google-wallet',
                'gratipay',
                'hacker-news',
                'houzz',
                'html5',
                'instagram',
                'internet-explorer',
                'ioxhost',
                'joomla',
                'jsfiddle',
                'lastfm',
                'lastfm-square',
                'leanpub',
                'linkedin',
                'linkedin-square',
                'linux',
                'maxcdn',
                'meanpath',
                'medium',
                'mixcloud',
                'modx',
                'odnoklassniki',
                'odnoklassniki-square',
                'opencart',
                'openid',
                'opera',
                'optin-monster',
                'pagelines',
                'paypal',
                'pied-piper',
                'pied-piper-alt',
                'pied-piper-pp',
                'pinterest',
                'pinterest-p',
                'pinterest-square',
                'product-hunt',
                'qq',
                'ra',
                'rebel',
                'reddit',
                'reddit-alien',
                'reddit-square',
                'renren',
                'resistance',
                'safari',
                'scribd',
                'sellsy',
                'share-alt',
                'share-alt-square',
                'shirtsinbulk',
                'simplybuilt',
                'skyatlas',
                'skype',
                'slack',
                'slideshare',
                'snapchat',
                'snapchat-ghost',
                'snapchat-square',
                'soundcloud',
                'spotify',
                'stack-exchange',
                'stack-overflow',
                'steam',
                'steam-square',
                'stumbleupon',
                'stumbleupon-circle',
                'tencent-weibo',
                'themeisle',
                'trello',
                'tripadvisor',
                'tumblr',
                'tumblr-square',
                'twitch',
                'twitter',
                'twitter-square',
                'usb',
                'viacoin',
                'viadeo',
                'viadeo-square',
                'vimeo',
                'vimeo-square',
                'vine',
                'vk',
                'wechat',
                'weibo',
                'weixin',
                'whatsapp',
                'wikipedia-w',
                'windows',
                'wordpress',
                'wpbeginner',
                'wpforms',
                'xing',
                'xing-square',
                'y-combinator',
                'y-combinator-square',
                'yahoo',
                'yc',
                'yc-square',
                'yelp',
                'yoast',
                'youtube',
                'youtube-play',
                'youtube-square',
                'ambulance',
                'h-square',
                'heart',
                'heart-o',
                'heartbeat',
                'hospital-o',
                'medkit',
                'plus-square',
                'stethoscope',
                'user-md',
                'wheelchair'

            )
        );
        return array_unique( $icon_array );

	}    
    
}
endif;