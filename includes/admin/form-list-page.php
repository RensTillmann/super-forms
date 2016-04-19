<?php
function super_remove_row_actions($actions){
    if(get_post_type() === 'super_form'){
        if(isset($actions['trash'])){
            $trash = $actions['trash'];
            unset($actions['trash']);
        }
        unset($actions['inline hide-if-no-js']);
        unset($actions['view']);
        unset($actions['edit']);
        $actions['shortcode'] = '<input type="text" readonly="readonly" class="super-get-form-shortcodes" value=\'[super_form id="'.get_the_ID().'"]\' />';
		$actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'edit.php?post_type=super_form&action=duplicate_super_form&amp;post=' . get_the_ID() ), 'super-duplicate-form_' . get_the_ID() ) . '" title="' . __( 'Make a duplicate from this form', 'super-forms' ) . '" rel="permalink">' .  __( 'Duplicate', 'super-forms' ) . '</a>';
        $actions['view'] = '<a href="admin.php?page=super_create_form&id='.get_the_ID().'">'.__('Edit','wp').'</a>';
        if(isset($trash)) $actions['trash'] = $trash;
    }
    return $actions;
}
add_filter('post_row_actions','super_remove_row_actions', 10, 1);

function super_edit_post_link($link, $post_id, $context) {
    if( get_post_type()==='super_form' ) {
        return 'admin.php?page=super_create_form&id=' . get_the_ID();
    }else{
        return $link;
    }
}
add_filter('get_edit_post_link', 'super_edit_post_link', 99, 3);

function super_add_post_link() {
    global $post_new_file,$post_type_object;
    if(!isset($post_type_object) || 'super_form' != $post_type_object->name){
        return false;
    }
    $post_new_file = 'admin.php?page=super_create_form';
}
add_action('admin_head','super_add_post_link');