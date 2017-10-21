<?php
function super_contact_entry_columns( $columns ) {
    foreach( $columns as $k => $v ) {
        if( ( $k != 'title' ) && ( $k != 'cb' ) ) {
            unset( $columns[$k] );
        }
    }
    $settings = get_option( 'super_settings' );
    if(!isset($settings['backend_contact_entry_status'])) $settings['backend_contact_entry_status'] = SUPER_Common::get_default_setting_value( 'backend_settings', 'backend_contact_entry_status' );
    $backend_contact_entry_status = explode( "\n", $settings['backend_contact_entry_status'] );
    $statuses = array();
    $statuses[''] = 'None (default)';
    foreach( $backend_contact_entry_status as $value ) {
        $status = explode( "|", $value );
        if( (isset($status[0])) && (isset($status[1])) ) {
            if(!isset($status[2])) $status[2] = '#808080';
            if(!isset($status[3])) $status[3] = '#FFFFFF';
            $statuses[$status[0]] = array('name'=>$status[1], 'bg_color'=>$status[2], 'color'=>$status[3]);
        }
    }
    $GLOBALS['backend_contact_entry_status'] = $statuses;

    $fields = explode( "\n", $settings['backend_contact_entry_list_fields'] );

    // @since 3.4.0 - add the contact entry status to the column list for entries
    if( !isset($settings['backend_contact_entry_list_status']) ) $settings['backend_contact_entry_list_status'] = 'true';
    if( $settings['backend_contact_entry_list_status']=='true' ) {
        $columns = array_merge( $columns, array( 'entry_status' => __( 'Status', 'super-forms' ) ) );
    }

    // @since 1.2.9
    if( !isset($settings['backend_contact_entry_list_form']) ) $settings['backend_contact_entry_list_form'] = 'true';
    if( $settings['backend_contact_entry_list_form']=='true' ) {
        $columns = array_merge( $columns, array( 'hidden_form_id' => __( 'Based on Form', 'super-forms' ) ) );
    }

    // @since 3.1.0
    if( (isset($settings['backend_contact_entry_list_ip'])) && ($settings['backend_contact_entry_list_ip']=='true') ) {
        $columns = array_merge( $columns, array( 'contact_entry_ip' => __( 'IP-address', 'super-forms' ) ) );
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

    $columns = array_merge( $columns, array( 'date' => __( 'Date', 'super-forms' ) ) );
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
                echo __( 'Unknown', 'super-forms' );
            }else{
                $form = get_post($form_id);
                if( isset( $form->post_title ) ) {
                    echo '<a href="admin.php?page=super_create_form&id=' . $form->ID . '">' . $form->post_title . '</a>';
                }else{
                    echo __( 'Unknown', 'super-forms' );
                }
            }
        }
    }elseif( $column=='entry_status' ) {
        $entry_status = get_post_meta($post_id, '_super_contact_entry_status', true);
        $statuses = $GLOBALS['backend_contact_entry_status'];
        if( (isset($statuses[$entry_status])) && ($entry_status!='') ) {
            echo '<span class="super-entry-status super-entry-status-' . $entry_status . '" style="color:' . $statuses[$entry_status]['color'] . ';background-color:' . $statuses[$entry_status]['bg_color'] . '">' . $statuses[$entry_status]['name'] . '</span>';
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
add_action( 'manage_posts_custom_column' , 'super_custom_columns', 10, 2 );


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
        $actions['mark'] = '<a class="super-mark-read" data-contact-entry="' . get_the_ID() . '" title="' . __( 'Mark this entry as read', 'super-forms' ) . '" href="#">' . __( 'Mark read', 'super-forms' ) . '</a><a class="super-mark-unread" data-contact-entry="' . get_the_ID() . '" title="' . __( 'Mark this entry as unread', 'super-forms' ) . '" href="#">' . __( 'Mark unread', 'super-forms' ) . '</a>';
        $actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'edit.php?post_type=super_contact_entry&action=duplicate_super_contact_entry&amp;post=' . get_the_ID() ), 'super-duplicate-contact-entry_' . get_the_ID() ) . '" title="' . __( 'Make a duplicate of this entry', 'super-forms' ) . '" rel="permalink">' .  __( 'Duplicate', 'super-forms' ) . '</a>';
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