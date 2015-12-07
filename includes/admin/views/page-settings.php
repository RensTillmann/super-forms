<div class="super-settings">
    <div class="super-header"></div>
    <div class="super-wrapper">
        <ul class="super-tabs noselect">
            <?php
            $counter = 0;
            foreach($fields as $k => $v){
                if($counter==0){
                    echo '<li class="active">'.$v['name'].'</li>';
                }else{
                    echo '<li>'.$v['name'].'</li>';
                }
                $counter++;
            }
            echo '<li class="save">';
            echo '<input type="submit" class="button button-primary button-large" value="'.__('Save Settings','super').'">';
            echo '<div class="message"></div>';
            echo '</li>';
            ?>
        </ul>
        <?php
        $counter = 0;
        foreach($fields as $k => $v){
            if($counter==0){
                echo '<div class="super-fields active">';
            }else{
                echo '<div class="super-fields">';
            }

            echo '<h2>'.$v['label'].'</h2>';
            if(isset($v['html'])){
                foreach($v['html'] as $html){
                    echo $html;
                }
            } 
            //Load fields
            if(isset($v['fields'])){
                foreach($v['fields'] as $fk => $fv){
                    $filter = '';
                    if(isset($fv['filter'])) $filter = ' filter';
                    $parent = '';
                    $hidden = '';
                    if(isset($fv['parent'])){
                        $parent = 'data-parent="'.$fv['parent'].'"';
                        $hidden = ' hidden';
                    }
                    $filter_value = '';
                    if(isset($fv['filter_value'])) $filter_value = 'data-filtervalue="'.$fv['filter_value'].'"';
                    echo '<div class="super-field'.$filter.$hidden.'" '.$parent.' '.$filter_value.'>';
                        echo '<div class="super-field-info">';
                            echo '<h2>'.$fv['name'].'</h2>';
                            if(isset($fv['desc'])){
                                echo '<div class="field-description">'.$fv['desc'].'</div>';
                            }
                        echo '</div>';
                        if(!isset($fv['type'])) $fv['type'] = 'text';
                        echo call_user_func(array('SUPER_Field_Types', $fv['type']), $fk, $fv);
                    echo '</div>';
                }
            }
            echo '</div>';
            $counter++;
        }
        $tags = array(
            'field_*****' => __( 'Any field value submitted by the user', 'super' ),
            'option_admin_email' => __( 'E-mail address of blog administrator', 'super' ),
            'option_blogname' => __( 'Weblog title; set in General Options', 'super' ),
            'option_blogdescription' => __( 'Tagline for your blog; set in General Options', 'super' ),
            'option_default_category' => __( 'Default post category; set in Writing Options', 'super' ),
            'option_home' => __( 'The blog\'s home web address; set in General Options', 'super' ),
            'option_siteurl' => __( 'WordPress web address; set in General Options', 'super' ),
            'option_template' => __( 'The current theme\'s name; set in Presentation', 'super' ),
            'option_upload_path' => __( 'Default upload location; set in Miscellaneous Options', 'super' ),
            'real_ip' => __( 'Retrieves the submitter\'s IP address', 'super' ),
            'loop_label' => __( 'Retrieves the field label for the field loop {loop_fields}', 'super' ),
            'loop_label' => __( 'Retrieves the field value for the field loop {loop_fields}', 'super' ),
            'loop_fields' => __( 'Retrieves the loop anywhere in your email', 'super' ),
        );
        $tags_html  = '';
        $tags_html .= '<div class="super-tags">';
        $tags_html .= '<ul>';
        foreach($tags as $k => $v){
            $tags_html .= '<li data-value="{'.$k.'}"><strong>{'.$k.'}</strong> - '.$v.'</li>';
        }
        $tags_html .= '</ul>';
        $tags_html .= '</div>';
        echo $tags_html;
        ?>
    </div>
</div>