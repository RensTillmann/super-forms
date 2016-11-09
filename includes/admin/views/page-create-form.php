<div class="super-create-form">
    <div class="super-wrapper">
        <div class="super-header">
            <div class="super-switch-forms" >
                <i class="fa fa-chevron-down super-tooltip" title="<?php echo __('Switch form', 'super-forms' ); ?>"></i>
                <ul>
                    <?php
                    if(count($forms)==0){
                        echo '<li><a href="admin.php?page=super_create_form">'.__('No forms found, create one!', 'super-forms' ).'</a></li>';
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
            <input type="text" name="title" class="form-name super-tooltip" title="<?php echo __('Enter a name for your form', 'super-forms' ); ?>" value="<?php echo $title; ?>" />
            <?php
            if(isset($_GET['id'])){
                echo '<input type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="'.__('Paste shortcode on any page', 'super-forms' ).'" value=\'[super_form id="'.$post_ID.'"]\' />';
                echo '<input type="hidden" name="form_id" value="'.$post_ID.'" />';
            }else{
                echo '<input type="hidden" name="form_id" value="" />';
                echo '<input type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="'.__('Please save your form first!', 'super-forms' ).'" value="[form-not-saved-yet]" />';
            }
            echo '<p>'.__('Take the shortcode and place it anywere!', 'super-forms' ).'</p>';
            echo '<div class="super-actions">';
                echo '<span class="save super-tooltip" title="'.__('Save your form', 'super-forms' ).'" ><i class="fa fa-save"></i>'.__('Save', 'super-forms' ).'</span>';
                echo '<span class="clear super-tooltip" title="'.__('Start all over', 'super-forms' ).'" ><i class="fa fa-eraser"></i>'.__('Clear', 'super-forms' ).'</span>';
                echo '<span class="delete super-tooltip" title="'.__('Delete complete form', 'super-forms' ).'" ><i class="fa fa-trash-o"></i>'.__('Delete', 'super-forms' ).'</span>';
                echo '<span class="preview desktop super-tooltip active" title="'.__('Desktop preview', 'super-forms' ).'" ><i class="fa fa-desktop"></i></span>';
                echo '<span class="preview tablet super-tooltip" title="'.__('Tablet preview', 'super-forms' ).'" ><i class="fa fa-tablet"></i></span>';
                echo '<span class="preview mobile super-tooltip" title="'.__('Mobile preview', 'super-forms' ).'" ><i class="fa fa-mobile"></i></span>';
                echo '<span class="preview switch super-tooltip" title="'.__('Live preview', 'super-forms' ).'" >'.__('Preview', 'super-forms' ).'</span>';
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
                <?php
                if( $post_ID==0 ) {
                    $admin_url = get_admin_url() . 'admin.php?page=super_marketplace';
                    echo '<div class="super-marketplace-notice">';
                    echo '<h2>Creating a new form?</h2>';
                    echo '<p>Take the time to check out one of the many ready to use forms in the <a href="' . $admin_url . '">Marketplace</a>!</p>';
                    echo ' <a href="' . $admin_url . '" class="button button-primary button-large">Bring me to the Marketplace!</a>';
                    echo '</div>';
                }
                ?>
                <div class="super-preview-elements super-dropable super-form-<?php echo $id; ?> <?php echo $theme_style; ?>"><?php SUPER_Common::generate_backend_elements($post_ID, $shortcodes); ?></div>
                <style type="text/css"><?php echo apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$id, 'settings'=>$settings ) ) . $settings['theme_custom_css']; ?></style>
                <div class="super-live-preview"></div>
                <div class="super-debug">
                    <textarea name="_super_elements"><?php echo get_post_meta($post_ID, '_super_elements', true); ?></textarea>
                </div>
            </div>
            <div class="super-elements">
                <?php
                echo '<div class="super-element super-element-settings">';
                    echo '<h3><i class="fa fa-th-large"></i>' . __( 'Element Settings & Options', 'super-forms' ) . '</h3>';
                    echo '<div class="super-elements-container"><p>' . sprintf( __( 'You are currently not editing an element.%sEdit any alement by clicking the %s icon.', 'super-forms' ), '<br />', '<i class="fa fa-pencil"></i>' ) . '</p></div>';
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
                    <h3><i class="fa fa-th-large"></i><?php echo __( 'Form Settings', 'super-forms' ); ?></h3>
                    <div class="super-elements-container">
                        <?php
                        echo '<div class="super-form-settings-tabs">';
                            echo '<select>';
                            $i = 0;
                            foreach( $form_settings as $key => $value ) { 
                                if( ( !isset( $value['hidden'] ) ) || ( $value['hidden']==false ) || ( $value['hidden']==='settings' ) ) {
                                    echo '<option value="' . $i . '" ' . ( $i==0 ? 'selected="selected"' : '') . '>' . $value['name'] . '</option>';
                                    $i++;
                                }
                            }
                            echo '</select>';
                        echo '</div>';

                        $counter = 0;
                        foreach( $form_settings as $key => $value ) { 
                            if( ( !isset( $value['hidden'] ) ) || ( $value['hidden']==false ) || ( $value['hidden']==='settings' ) ) {
                                echo '<div class="tab-content '.($counter==0 ? 'active' : '').'">';
                                if( isset( $value['html'] ) ) {
                                    foreach( $value['html'] as $v ) {
                                        echo $v;
                                    }
                                }
                                if( isset( $value['fields'] ) ) {
                                    foreach( $value['fields'] as $k => $v ) {
                                        if( ( !isset( $v['hidden'] ) ) || ( $v['hidden']==false ) )  {
                                            $filter = '';
                                            $parent = '';
                                            $filtervalue = '';
                                            if( ( isset( $v['filter'] ) ) && ( $v['filter']==true ) ) {
                                                $filter = ' filter';
                                                if( isset( $v['parent'] ) ) $parent = ' data-parent="' . $v['parent'] . '"';
                                                if( isset( $v['filter_value'] ) ) $filtervalue = ' data-filtervalue="' . $v['filter_value'].'"';
                                            }
                                            echo '<div class="field' . $filter . '"' . $parent . '' . $filtervalue . '>';
                                                if( isset( $v['name'] ) ) echo '<div class="field-name">' . $v['name'] . '</div>';
                                                if( isset( $v['desc'] ) ) echo '<i class="info super-tooltip" title="' . $v['desc'] . '"></i>';
                                                if( isset( $v['label'] ) ) echo '<div class="field-label">' . $v['label'] . '</div>';
                                                echo '<div class="field-input">';
                                                    if( !isset( $v['type'] ) ) $v['type'] = 'text';
                                                    echo call_user_func( array( 'SUPER_Field_Types', $v['type'] ), $k, $v );
                                                echo '</div>';
                                            echo '</div>';
                                        }
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
                    <h3><i class="fa fa-th-large"></i><?php echo __('Examples', 'super-forms' ); ?></h3>
                    <div class="super-elements-container">
                        <div class="load-form-container">
                            <select name="super-forms">
                                <?php
                                echo '<option value="">' . __('- select a form -', 'super-forms' ) . '</option>';
                                echo '<option value="0">' . __('Default Form', 'super-forms' ) . '</option>';
                                do_action( 'super_before_load_form_dropdown_hook' );
                                foreach( $forms as $value ) {
                                    echo '<option value="' . $value->ID . '">' . ($value->post_title=='' ? '(no title)' : $value->post_title) . '</option>';
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