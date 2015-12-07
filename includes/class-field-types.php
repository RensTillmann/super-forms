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

if(!class_exists('SUPER_Field_Types')) :

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
                    $return .= '<div class="sorting">';
                        $return .= '<span class="up"><i class="fa fa-arrow-up"></i></span>';
                        $return .= '<span class="down"><i class="fa fa-arrow-down"></i></span>';
                    $return .= '</div>';
                    if(!isset($v['checked'])) $v['checked'] = 'false';
                    $return .= '<input data-prev="'.$v['checked'].'" type="radio"' . ($v['checked']=='true' ? ' checked="checked"' : '') . '">';
                    $return .= '<input type="text" placeholder="' . __( 'Label', 'super' ) . '" value="' . $v['label'] . '" name="label">';
                    $return .= '<input type="text" placeholder="' . __( 'Value', 'super' ) . '" value="' . $v['value'] . '" name="value">';
                    $return .= '<i class="add super-add-item fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o"></i>';
                $return .= '</div>';
            }
            $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . json_encode( $data[$id] ) . '</textarea>';
        }else{
            $return .= '<div class="super-multi-items super-dropdown-item">';
                $return .= '<div class="sorting">';
                    $return .= '<span class="up"><i class="fa fa-arrow-up"></i></span>';
                    $return .= '<span class="down"><i class="fa fa-arrow-down"></i></span>';
                $return .= '</div>';
                $return .= '<input type="radio"">';
                $return .= '<input type="text" placeholder="' . __( 'Label', 'super' ) . '" value="" name="label">';
                $return .= '<input type="text" placeholder="' . __( 'Value', 'super' ) . '" value="" name="value">';
                $return .= '<i class="add super-add-item fa fa-plus"></i>';
                $return .= '<i class="delete fa fa-trash-o"></i>';
            $return .= '</div>';
            $return .= '<textarea name="' . $id . '" class="element-field multi-items-json">' . $field['default'] . '</textarea>';
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
    public static function image($id, $field){
		$return  = '<div class="image-field browse-images">';
        $return .= '<span class="button super-insert-image"><i class="fa fa-plus"></i> Add image</span>';
        $return .= '<div class="image-preview">';
        $image = wp_get_attachment_image_src($field['default'],'thumbnail');
        $image = !empty($image[0]) ? $image[0] : '';
        if(!empty($image)){
            $return .= '<img src="'.$image.'">';
            $return .= '<br>';
            $return .= '<a href="#">Delete</a>';
        }
        $return .= '</div>';
        $return .= '<input type="hidden" name="'.$id.'" value="'.esc_attr($field['default']).'" id="field-'.$id.'" class="element-field" />';
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
    public static function text($id, $field){
        $return  = '<div class="input">';
            $return .= '<input type="text" id="field-'.$id.'"';
            if(isset($field['placeholder'])){
                $return .= ($field['placeholder']!='' ? 'placeholder="'.$field['placeholder'].'"' : '');
            }
            if(isset($field['required'])){
                $return .= ($field['required']==true ? 'required="true"' : '');
            }
            if(isset($field['maxlength'])){
                $return .= ($field['maxlength'] > 0 ? 'maxlength="'.$field['maxlength'].'"' : '');
            }
            $return .= 'name="'.$id.'" class="element-field" value="'.esc_attr($field['default']).'" />';
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
                $return .= '<div class="super-multi-items super-conditional-item">';
                    $return .= '<select name="conditional_field" data-value="' . $v['field'] . '"></select>';
                    $return .= '<select name="conditional_logic">';
                        foreach( $options as $ok => $ov ) {
                            $return .= '<option' . ($ok==$v['logic'] ? ' selected="selected"' : '') . ' value="' . $ok . '">' . $ov . '</option>';
                        }
                    $return .= '</select>';
                    $return .= '<input type="text" placeholder="Value" value="' . $v['value'] . '" name="conditional_value">';
                    $return .= '<i class="add fa fa-plus"></i>';
                    $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
                $return .= '</div>';
            }
        }else{
            $return  = '<div class="super-multi-items super-conditional-item">';
                $return .= '<select name="conditional_field" data-value=""></select>';
                $return .= '<select name="conditional_logic">';
                    $return .= '<option value="contains">?? Contains</option>';
                    $return .= '<option value="equal">== Equal</option>';
                    $return .= '<option value="not_equal">!= Not equal</option>';
                    $return .= '<option value="greater_than">> Greater than</option>';
                    $return .= '<option value="less_than"><  Less than</option>';
                    $return .= '<option value="greater_than_or_equal">>= Greater than or equal to</option>';
                    $return .= '<option value="less_than_or_equal"><= Less than or equal</option>';
                $return .= '</select>';
                $return .= '<input type="text" placeholder="Value" value="" name="conditional_value">';
                $return .= '<i class="add fa fa-plus"></i>';
                $return .= '<i class="delete fa fa-trash-o" style="visibility: hidden;"></i>';
            $return .= '</div>';
        }
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
        if(isset($field['multiple'])) $multiple = ' multiple';
        if(isset($field['filter'])) $filter = ' filter';
        $return  = '<div class="input">';
            $return .= '<select id="field-'.$id.'" name="'.$id.'" class="element-field '.$multiple.'"'.$multiple.$filter.'>';
            foreach($field['values'] as $k => $v ) {
                $selected = '';
                if($field['default']==$k){
                    $selected = ' selected="selected"';
                }
                $return .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
            }
            $return .= '</select>';
            if(isset($field['info'])) $return .= '<p>'.$field['info'].'</p>';
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
		return apply_filters( 
            'super_icons', 
            array(
                'adjust',
                'anchor',
                'archive',
                'area-chart',
                'arrows',
                'arrows-h',
                'arrows-v',
                'asterisk',
                'at',
                'automobile',
                'ban',
                'bank',
                'bar-chart',
                'bar-chart-o',
                'barcode',
                'bars',
                'beer',
                'bell',
                'bell-o',
                'bell-slash',
                'bell-slash-o',
                'bicycle',
                'binoculars',
                'birthday-cake',
                'bolt',
                'bomb',
                'book',
                'bookmark',
                'bookmark-o',
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
                'calendar-o',
                'camera',
                'camera-retro',
                'car',
                'caret-square-o-down',
                'caret-square-o-left',
                'caret-square-o-right',
                'caret-square-o-up',
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
                'comments',
                'comments-o',
                'compass',
                'copyright',
                'credit-card',
                'crop',
                'crosshairs',
                'cube',
                'cubes',
                'cutlery',
                'dashboard',
                'database',
                'desktop',
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
                'hdd-o',
                'headphones',
                'heart',
                'heart-o',
                'history',
                'home',
                'image',
                'inbox',
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
                'magic',
                'magnet',
                'mail-forward',
                'mail-reply',
                'mail-reply-all',
                'male',
                'map-marker',
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
                'music',
                'navicon',
                'newspaper-o',
                'paint-brush',
                'paper-plane',
                'paper-plane-o',
                'paw',
                'pencil',
                'pencil-square',
                'pencil-square-o',
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
                'quote-left',
                'quote-right',
                'random',
                'recycle',
                'refresh',
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
                'share',
                'share-alt',
                'share-alt-square',
                'share-square',
                'share-square-o',
                'shield',
                'shopping-cart',
                'sign-in',
                'sign-out',
                'signal',
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
                'suitcase',
                'sun-o',
                'support',
                'tablet',
                'tachometer',
                'tag',
                'tags',
                'tasks',
                'taxi',
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
                'trash',
                'trash-o',
                'tree',
                'trophy',
                'truck',
                'tty',
                'umbrella',
                'university',
                'unlock',
                'unlock-alt',
                'unsorted',
                'upload',
                'user',
                'users',
                'video-camera',
                'volume-down',
                'volume-off',
                'volume-up',
                'warning',
                'wheelchair',
                'wifi',
                'wrench',

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
                'cc-discover',
                'cc-mastercard',
                'cc-paypal',
                'cc-stripe',
                'cc-visa',
                'credit-card',
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
                'play',
                'play-circle',
                'play-circle-o',
                'step-backward',
                'step-forward',
                'stop',
                'youtube-play',

                'adn',
                'android',
                'angellist',
                'apple',
                'behance',
                'behance-square',
                'bitbucket',
                'bitbucket-square',
                'bitcoin',
                'btc',
                'cc-amex',
                'cc-discover',
                'cc-mastercard',
                'cc-paypal',
                'cc-stripe',
                'cc-visa',
                'codepen',
                'css3',
                'delicious',
                'deviantart',
                'digg',
                'dribbble',
                'dropbox',
                'drupal',
                'empire',
                'facebook',
                'facebook-square',
                'flickr',
                'foursquare',
                'ge',
                'git',
                'git-square',
                'github',
                'github-alt',
                'github-square',
                'gittip',
                'google',
                'google-plus',
                'google-plus-square',
                'google-wallet',
                'hacker-news',
                'html5',
                'instagram',
                'ioxhost',
                'joomla',
                'jsfiddle',
                'lastfm',
                'lastfm-square',
                'linkedin',
                'linkedin-square',
                'linux',
                'maxcdn',
                'meanpath',
                'openid',
                'pagelines',
                'paypal',
                'pied-piper',
                'pied-piper-alt',
                'pinterest',
                'pinterest-square',
                'qq',
                'ra',
                'rebel',
                'reddit',
                'reddit-square',
                'renren',
                'share-alt',
                'share-alt-square',
                'skype',
                'slack',
                'slideshare',
                'soundcloud',
                'spotify',
                'stack-exchange',
                'stack-overflow',
                'steam',
                'steam-square',
                'stumbleupon',
                'stumbleupon-circle',
                'tencent-weibo',
                'trello',
                'tumblr',
                'tumblr-square',
                'twitch',
                'twitter',
                'twitter-square',
                'vimeo-square',
                'vine',
                'vk',
                'wechat',
                'weibo',
                'weixin',
                'windows',
                'wordpress',
                'xing',
                'xing-square',
                'yahoo',
                'yelp',
                'youtube',
                'youtube-play',
                'youtube-square',

                'ambulance',
                'h-square',
                'hospital-o',
                'medkit',
                'plus-square',
                'stethoscope',
                'user-md',
                'wheelchair'
            )
        );
	}    
    
}
endif;