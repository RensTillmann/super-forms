<div class="super-settings">
    <div class="super-header"></div>
    <div class="super-wrapper">
        <ul class="super-tabs super-noselect">
            <?php
            $counter = 0;
            foreach( $fields as $k => $v ) {
                if(!isset($v['name'])) continue;
                if( (isset($v['hidden'])) && ($v['hidden']==='settings') ) {
                    continue;
                }
                $data_key = str_replace('_', '-', $k);
                if( $counter==0 ) {
                    echo '<li class="super-active" data-key="' . $data_key . '">' . $v['name'] . '</li>';
                }else{
                    echo '<li data-key="' . $data_key . '">' . $v['name'] . '</li>';
                }
                $counter++;
            }
            echo '<li class="save">';
            echo '<span class="button super-button save-settings">' . esc_html__( 'Save Settings', 'super-forms' ) . '</span>';
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
                echo '<div class="super-fields super-active super-field-type-">';
            }else{
                echo '<div class="super-fields">';
            }

            if( !empty($v['label'])) {
                echo '<h2>' . $v['label'] . '</h2>';
            }
            if( isset( $v['html'] ) ) {
                foreach( $v['html'] as $html ) {
                    echo $html;
                }
            } 
            //Load fields
            if( isset( $v['fields'] ) ) {
                foreach( $v['fields'] as $fk => $fv ) {
                    if(isset($fv['type']) && $fv['type']==='multicolor'){
                        foreach($fv['colors'] as $ck => $cv){
                            if(isset($g[$ck])) $fv['colors'][$ck]['v'] = $g[$ck];
                        }
                    }
                    echo call_user_func( array( 'SUPER_Field_Types', 'loop_over_fields' ), $fk, $fv );
                }
            }
            echo '</div>';
            $counter++;
        }
        $tags = SUPER_Common::email_tags(null, null, null, null, false);
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