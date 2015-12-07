<?php
function super_contact_entry_columns( $columns ) {
    foreach( $columns as $k => $v ) {
        if( ( $k != 'title' ) && ( $k != 'cb' ) ) {
            unset( $columns[$k] );
        }
    }
    $settings = get_option( 'super_settings' );
    $fields = explode( "\n", $settings['backend_contact_entry_list_fields'] );
    foreach( $fields as $k ) {
        $field = explode( "|", $k );
        $columns = array_merge( $columns, array( $field[0] => $field[1] ) );
    }
    $columns = array_merge( $columns, array( 'date' => __( 'Date', 'super' ) ) );
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
    if( isset( $contact_entry_data[0][$column] ) ) {
        if( $contact_entry_data[0][$column]['type']=='barcode' ) {   
            echo '<div class="super-barcode">';
                echo '<div class="super-barcode-target"></div>';
                echo '<input type="hidden" value="' . $contact_entry_data[0][$column]['value'] . '" data-barcodetype="' . $contact_entry_data[0][$column]['barcodetype'] . '" data-modulesize="' . $contact_entry_data[0][$column]['modulesize'] . '" data-quietzone="' . $contact_entry_data[0][$column]['quietzone'] . '" data-rectangular="' . $contact_entry_data[0][$column]['rectangular'] . '" data-barheight="30" data-barwidth="' . $contact_entry_data[0][$column]['barwidth'] . '" />';
            echo '</div>';
        }else{
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
        $actions['mark'] = '<a class="super-mark-read" data-contact-entry="' . get_the_ID() . '" title="' . __( 'Mark this entry as read', 'super' ) . '" href="#">' . __( 'Mark read', 'super' ) . '</a><a class="super-mark-unread" data-contact-entry="' . get_the_ID() . '" title="' . __( 'Mark this entry as unread', 'super' ) . '" href="#">' . __( 'Mark unread', 'super' ) . '</a>';
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