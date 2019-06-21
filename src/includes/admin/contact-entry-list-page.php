<?php
function super_contact_entry_columns( $columns ) {
    foreach( $columns as $k => $v ) {
        if( ( $k != 'title' ) && ( $k != 'cb' ) ) {
            unset( $columns[$k] );
        }
    }
    $global_settings = SUPER_Common::get_global_settings();
    $GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses($global_settings);

    $fields = explode( "\n", $global_settings['backend_contact_entry_list_fields'] );

    // @since 3.4.0 - add the contact entry status to the column list for entries
    if( !isset($global_settings['backend_contact_entry_list_status']) ) $global_settings['backend_contact_entry_list_status'] = 'true';
    if( $global_settings['backend_contact_entry_list_status']=='true' ) {
        $columns = array_merge( $columns, array( 'entry_status' => esc_html__( 'Status', 'super-forms' ) ) );
    }

    // @since 1.2.9
    if( !isset($global_settings['backend_contact_entry_list_form']) ) $global_settings['backend_contact_entry_list_form'] = 'true';
    if( $global_settings['backend_contact_entry_list_form']=='true' ) {
        $columns = array_merge( $columns, array( 'hidden_form_id' => esc_html__( 'Based on Form', 'super-forms' ) ) );
    }

    // @since 3.1.0
    if( (isset($global_settings['backend_contact_entry_list_ip'])) && ($global_settings['backend_contact_entry_list_ip']=='true') ) {
        $columns = array_merge( $columns, array( 'contact_entry_ip' => esc_html__( 'IP-address', 'super-forms' ) ) );
    }

    foreach( $fields as $k ) {
        $field = explode( "|", $k );
        if( $field[0]=='hidden_form_id' ) {
            $columns['hidden_form_id'] = $field[1];
        }elseif( $field[0]=='entry_status' ){
            $columns['entry_status'] = $field[1];
        }else{
            $columns = array_merge( $columns, array( $field[0] => $field[1] ) );
        }
    }

    $columns = array_merge( $columns, array( 'date' => esc_html__( 'Date', 'super-forms' ) ) );
    return $columns;
}
add_filter( 'manage_super_contact_entry_posts_columns' , 'super_contact_entry_columns', 999999 );


function super_form_columns( $columns ) {
    foreach( $columns as $k => $v ) {
        if( ( $k != 'title' ) && ( $k != 'cb' ) && ( $k != 'date' ) ) {
            unset( $columns[$k] );
        }
    }
    return $columns;
}
add_filter( 'manage_super_form_posts_columns' , 'super_form_columns', 999999 );


function super_custom_columns( $column, $post_id ) {
    $contact_entry_data = get_post_meta( $post_id, '_super_contact_entry_data' );
    if( $column=='hidden_form_id' ) {
        if( isset( $contact_entry_data[0][$column] ) ) {
            $form_id = $contact_entry_data[0][$column]['value'];
            $form_id = absint($form_id);
            if($form_id==0){
                echo esc_html__( 'Unknown', 'super-forms' );
            }else{
                $form = get_post($form_id);
                if( isset( $form->post_title ) ) {
                    echo '<a href="admin.php?page=super_create_form&id=' . $form->ID . '">' . $form->post_title . '</a>';
                }else{
                    echo esc_html__( 'Unknown', 'super-forms' );
                }
            }
        }
    }elseif( $column=='entry_status' ) {
        $entry_status = get_post_meta($post_id, '_super_contact_entry_status', true);
        $statuses = $GLOBALS['backend_contact_entry_status'];
        if( (isset($statuses[$entry_status])) && ($entry_status!='') ) {
            echo '<span class="super-entry-status super-entry-status-' . $entry_status . '" style="color:' . $statuses[$entry_status]['color'] . ';background-color:' . $statuses[$entry_status]['bg_color'] . '">' . $statuses[$entry_status]['name'] . '</span>';
        }else{
            $post_status = get_post_status($post_id);
            if($post_status=='super_read'){
                echo '<span class="super-entry-status super-entry-status-' . $post_status . '" style="background-color:#d6d6d6;">' . esc_html__( 'Read', 'super-forms' ) . '</span>';
            }else{
                echo '<span class="super-entry-status super-entry-status-' . $post_status . '">' . esc_html__( 'Unread', 'super-forms' ) . '</span>';
            }
        }
    }elseif( $column=='contact_entry_ip' ) {
        $entry_ip = get_post_meta($post_id, '_super_contact_entry_ip', true);
        echo $entry_ip . ' [<a href="http://whois.domaintools.com/' . $entry_ip . '" target="_blank">Whois</a>]';
    }else{
        if( isset( $contact_entry_data[0][$column] ) ) {
            echo $contact_entry_data[0][$column]['value'];
        }
    }
}
add_action( 'manage_super_contact_entry_posts_custom_column' , 'super_custom_columns', 10, 2 );

function super_remove_row_actions( $actions ) {
    if( get_post_type()==='super_contact_entry' ) {
        if( isset( $actions['trash'] ) ) {
            $trash = $actions['trash'];
            unset( $actions['trash'] );
        }
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['view'] );
        unset( $actions['edit'] );
        $actions['view'] = '<a href="admin.php?page=super_contact_entry&id=' . get_the_ID() . '">View</a>';

        

        $actions['mark'] = '<a class="super-mark-read" data-contact-entry="' . get_the_ID() . '" title="' . esc_attr( __( 'Mark this entry as read', 'super-forms' ) ) . '" href="#">' . esc_html__( 'Mark read', 'super-forms' ) . '</a><a class="super-mark-unread" data-contact-entry="' . get_the_ID() . '" title="' . esc_attr( __( 'Mark this entry as unread', 'super-forms' ) ) . '" href="#">' . esc_html__( 'Mark unread', 'super-forms' ) . '</a>';
        $actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'edit.php?post_type=super_contact_entry&action=duplicate_super_contact_entry&amp;post=' . get_the_ID() ), 'super-duplicate-contact-entry_' . get_the_ID() ) . '" title="' . esc_attr( __( 'Make a duplicate of this entry', 'super-forms' ) ) . '" rel="permalink">' .  esc_html__( 'Duplicate', 'super-forms' ) . '</a>';
        if( isset( $trash ) ) {
            $actions['trash'] = $trash;
        }
    }
    return $actions;
}
add_filter( 'post_row_actions', 'super_remove_row_actions', 10, 1 );


function super_edit_post_link( $link, $post_id, $context ) {
    if( get_post_type() === 'super_contact_entry' ) {
        return 'admin.php?page=super_contact_entry&id=' . get_the_ID();
    }else{
        return $link;
    }
}
add_filter( 'get_edit_post_link', 'super_edit_post_link', 99, 3 );


// @since 3.4.0 - add bulk edit option to change entry status
function display_custom_quickedit_super_contact_entry( $column_name, $post_type ) {
    if( ($post_type=='super_contact_entry') && ($column_name=='entry_status') ) {
        static $printNonce = TRUE;
        if ( $printNonce ) {
            $printNonce = FALSE;
            wp_nonce_field( plugin_basename( __FILE__ ), 'book_edit_nonce' );
        }
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <div class="inline-edit-group wp-clearfix">
                    <label class="inline-edit-status alignleft">
                        <span class="title">Entry status</span>
                        <select name="entry_status">
                            <option value="-1">— No changes —</option>
                            <?php
                            $statuses = $GLOBALS['backend_contact_entry_status'];
                            foreach($statuses as $k => $v){
                                echo '<option value="' . $k . '">' . $v['name'] . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </div>
            </div>
        </fieldset>
        <?php
    }
}
add_action( 'bulk_edit_custom_box', 'display_custom_quickedit_super_contact_entry', 10, 2 );
