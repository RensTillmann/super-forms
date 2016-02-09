<div class="super-create-form">
    <div class="super-wrapper">
        <div class="super-header">
            <div class="super-switch-forms" >
                <i class="fa fa-chevron-down popup" title="<?php echo __('Switch form','super'); ?>" data-placement="bottom"></i>
                <ul>
                    <?php
                    if(count($forms)==0){
                        echo '<li><a href="admin.php?page=super_create_form">'.__('No forms found, create one!','super').'</a></li>';
                    }else{
                        foreach($forms as $value){
                            if($post_ID!=$value->ID){
                                echo '<li value="'.$value->ID.'"><a href="admin.php?page=super_create_form&id='.$value->ID.'">'.$value->post_title.'</a></li>';
                            }
                        }
                    }
                    ?>
                </ul>
            </div>
            <input type="text" name="title" class="form-name popup" title="<?php echo __('Enter a name for your form','super'); ?>" data-placement="bottom" value="<?php echo $title; ?>" />
            <?php
            if(isset($_GET['id'])){
                echo '<input type="text" readonly="readonly" class="super-get-form-shortcodes popup" title="'.__('Paste shortcode on any page','super').'" data-placement="bottom" value=\'[super_form id="'.$post_ID.'"]\' />';
                echo '<input type="hidden" name="form_id" value="'.$post_ID.'" />';
            }else{
                echo '<input type="hidden" name="form_id" value="" />';
                echo '<input type="text" readonly="readonly" class="super-get-form-shortcodes popup" title="'.__('Please save your form first!','super').'" data-placement="bottom" value="[form-not-saved-yet]" />';
            }
            echo '<p>'.__('Take the shortcode and place it anywere!','super').'</p>';
            echo '<div class="super-actions">';
                echo '<span class="save popup" title="'.__('Save your form','super').'" data-placement="bottom"><i class="fa fa-save"></i>'.__('Save','super').'</span>';
                echo '<span class="clear popup" title="'.__('Start all over','super').'" data-placement="bottom"><i class="fa fa-eraser"></i>'.__('Clear','super').'</span>';
                echo '<span class="delete popup" title="'.__('Delete complete form','super').'" data-placement="bottom"><i class="fa fa-trash-o"></i>'.__('Delete','super').'</span>';
                echo '<span class="preview desktop popup active" title="'.__('Desktop preview','super').'" data-placement="bottom"><i class="fa fa-desktop"></i></span>';
                echo '<span class="preview tablet popup" title="'.__('Tablet preview','super').'" data-placement="bottom"><i class="fa fa-tablet"></i></span>';
                echo '<span class="preview mobile popup" title="'.__('Mobile preview','super').'" data-placement="bottom"><i class="fa fa-mobile"></i></span>';
                echo '<span class="preview switch popup" title="'.__('Live preview','super').'" data-placement="bottom">'.__('Preview','super').'</span>';
            echo '</div>';
            ?>
        </div>
        <div class="super-builder">

            <?php
            // Try to load the selected theme style
            $id = $post_ID;
            $theme_style = 'super-style-default ';
            $style_content  = '';
            if( ( isset( $settings['theme_style'] ) ) && ( $settings['theme_style']!='' ) ) {
                $theme_style = $theme_style . $settings['theme_style'];
                $style_content .= require_once( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/' . str_replace( 'super-', '', $settings['theme_style'] ) . '.php' );
            }
            // Always load the default styles (these can be overwritten by the above loaded style file
            $style_content .= require_once( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
            ?>

            <div class="super-preview"> 
                <div class="super-preview-elements super-dropable super-form-<?php echo $id; ?> <?php echo $theme_style; ?>"><?php SUPER_Common::generate_backend_elements($post_ID, $shortcodes); ?></div>
                <div class="super-live-preview"></div>
                <div class="super-debug" <?php if( ( isset( $settings['backend_debug_mode'] ) ) && ( $settings['backend_debug_mode']==0 ) ) { echo 'hidden'; } ?>>
                    <textarea name="_super_elements"><?php echo get_post_meta($post_ID, '_super_elements', true); ?></textarea>
                </div>
            </div>
            <style type="text/css"><?php echo apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$id, 'settings'=>$settings ) ) . $settings['theme_custom_css']; ?></style>
            <div class="super-elements">
                <?php
                echo '<div class="super-element super-element-settings">';
                    echo '<h3><i class="fa fa-th-large"></i>' . __( 'Element Settings & Options', 'super' ) . '</h3>';
                    echo '<div class="super-elements-container"><p>' . sprintf( __( 'You are currently not editing an element.%sEdit any alement by clicking the %s icon.', 'super' ), '<br />', '<i class="fa fa-pencil"></i>' ) . '</p></div>';
                echo '</div>';
                foreach($shortcodes as $k => $v){
                    echo '<div class="super-element '.$v['class'].'">';
                        echo '<h3><i class="fa fa-th-large"></i>'.$v['title'].'</h3>';
                        echo '<div class="super-elements-container">';
                            if( isset( $v['info'] ) ) {
                                echo '<p>'.$v['info'].'</p>';
                            }
                            foreach($v['shortcodes'] as $key => $value){ 
                                if( ( !isset( $value['hidden'] ) ) || ( $value['hidden']==false ) )  {
                                    echo SUPER_Shortcodes::output_element( $key, $k, $value );
                                }
                            }
                        echo '</div>';
                    echo '</div>';
                }
                ?>
                <div class="super-element super-form-settings">
                    <h3><i class="fa fa-th-large"></i><?php echo __( 'Form Settings', 'super' ); ?></h3>
                    <div class="super-elements-container">
                        <?php
                        echo '<div class="super-form-settings-tabs">';
                            echo '<select>';
                            $i = 0;
                            foreach($form_settings as $key => $value){ 
                                if( ( !isset( $value['hidden'] ) ) || ( $value['hidden']==false ) )  {
                                    echo '<option value="'.$i.'" '.($i==0 ? 'selected="selected"' : '').'>'.$value['name'].'</option>';
                                    $i++;
                                }
                            }
                            echo '</select>';
                        echo '</div>';

                        $counter = 0;
                        foreach($form_settings as $key => $value){ 
                            if( ( !isset( $value['hidden'] ) ) || ( $value['hidden']==false ) )  {
                                echo '<div class="tab-content '.($counter==0 ? 'active' : '').'">';
                                if( isset( $value['html'] ) ) {
                                    foreach( $value['html'] as $v ) {
                                        echo $v;
                                    }
                                }
                                if( isset( $value['fields'] ) ) {
                                    foreach($value['fields'] as $k => $v){
                                        $filter = '';
                                        $parent = '';
                                        $filtervalue = '';
                                        if((isset($v['filter'])) && ($v['filter']==true)){
                                            $filter = ' filter';
                                            if(isset($v['parent'])) $parent = ' data-parent="'.$v['parent'].'"';
                                            if(isset($v['filter_value'])) $filtervalue = ' data-filtervalue="'.$v['filter_value'].'"';
                                        }
                                        echo '<div class="field'.$filter.'"'.$parent.''.$filtervalue.'>';
                                            if(isset($v['name'])) echo '<div class="field-name">'.$v['name'].'</div>';
                                            if(isset($v['desc'])) echo '<i class="info popup" title="" data-placement="bottom" data-original-title="'.$v['desc'].'"></i>';
                                            if(isset($v['label'])) echo '<div class="field-label">'.$v['label'].'</div>';
                                            echo '<div class="field-input">';
                                                if(!isset($v['type'])) $v['type'] = 'text';
                                                echo call_user_func(array('SUPER_Field_Types', $v['type']), $k, $v);
                                            echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                echo '</div>';
                            }
                            $counter++;
                        }
                        ?>
                    </div>
                </div>
                <div class="super-element super-load-form">
                    <h3><i class="fa fa-th-large"></i><?php echo __('Examples','super'); ?></h3>
                    <div class="super-elements-container">
                        <div class="load-form-container">
                            <select name="super-forms">
                                <?php
                                echo '<option value="">'.__('- select a form -','super').'</option>';
                                echo '<option value="0">'.__('Default Form','super').'</option>';
                                do_action( 'super_before_load_form_dropdown_hook' );
                                foreach($forms as $value){
                                    echo '<option value="'.$value->ID.'">'.$value->post_title.'</option>';
                                }
                                ?>
                            </select>
                            <?php do_action( 'super_after_load_form_dropdown_hook' ); ?>
                            <span class="super-button load-form">Insert</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>