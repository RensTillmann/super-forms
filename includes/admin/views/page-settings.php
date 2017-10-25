<div class="super-settings">
    <div class="super-donation">
        <h1>Support this plugin!</h1>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="WP68J5ZK3VFNJ">
            <input type="image" src="https://www.paypalobjects.com/en_US/NL/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>
    </div>
    <div class="super-header"></div>
    <div class="super-wrapper">
        <div class="super-msg">
            <?php echo __('<strong>Please note:</strong> These are your default settings. Previous created forms will not be affected when editing some of the below settings. You can change form settings under the "Form Settings" tab when editing a form.', 'super-forms' ); ?>
        </div>
        <ul class="super-tabs noselect">
            <?php
            $counter = 0;
            foreach( $fields as $k => $v ) {
                if( (isset($v['hidden'])) && ($v['hidden']==='settings') ) {
                    continue;
                }
                if( $counter==0 ) {
                    echo '<li class="active">' . $v['name'] . '</li>';
                }else{
                    echo '<li>' . $v['name'] . '</li>';
                }
                $counter++;
            }
            echo '<li class="save">';
            echo '<span class="button super-button save-settings">' . __( 'Save Settings', 'super-forms' ) . '</span>';
            echo '<div class="message"></div>';
            echo '</li>';
            ?>
        </ul>
        <?php
        $counter = 0;
        foreach( $fields as $k => $v ) {
            if( (isset($v['hidden'])) && ($v['hidden']==='settings') ) {
                continue;
            }
            if( $counter==0 ) {
                echo '<div class="super-fields active">';
            }else{
                echo '<div class="super-fields">';
            }

            echo '<h2>' . $v['label'] . '</h2>';
            if( isset( $v['html'] ) ) {
                foreach( $v['html'] as $html ) {
                    echo $html;
                }
            } 
            //Load fields
            if( isset( $v['fields'] ) ) {
                foreach( $v['fields'] as $fk => $fv ) {
                    $style = '';
                    $filter = '';
                    if( isset( $fv['filter'] ) ) $filter = ' filter';
                    $parent = '';
                    $hidden = '';
                    if( isset( $fv['parent'] ) ) {
                        $parent = 'data-parent="' . $fv['parent'] . '"';
                        $hidden = ' hidden';
                    }
                    if( isset( $fv['hidden_setting'] ) ) {
                        $style = ' style="display:none;"';
                    }
                    $filter_value = '';
                    if( isset( $fv['filter_value'] ) ) $filter_value = 'data-filtervalue="' . $fv['filter_value'] . '"';
                    echo '<div class="super-field' . $filter . $hidden . '" ' . $parent . ' ' . $filter_value . $style.'>';
                        echo '<div class="super-field-info">';
                            if( isset( $fv['name'] ) ) {
                                echo '<h2>' . $fv['name'] . '</h2>';
                            }
                            if( isset( $fv['desc'] ) ) {
                                echo '<div class="field-description">' . $fv['desc'] . '</div>';
                            }
                        echo '</div>';
                        if( !isset( $fv['type'] ) ) $fv['type'] = 'text';
                        echo call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv );
                    echo '</div>';
                }
            }
            echo '</div>';
            $counter++;
        }
        $tags = SUPER_Common::email_tags();
        $tags_html  = '';
        $tags_html .= '<div class="super-tags">';
        $tags_html .= '<ul>';
        foreach( $tags as $k => $v ) {
            $tags_html .= '<li data-value="{' . $k . '}"><strong>{' . $k . '}</strong> - ' . $v[0] . '</li>';
        }
        $tags_html .= '</ul>';
        $tags_html .= '</div>';
        echo $tags_html;
        ?>
    </div>
</div>