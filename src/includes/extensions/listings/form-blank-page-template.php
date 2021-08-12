<?php
// View entry
if( isset($_POST['action']) && isset($_POST['entry_id']) && isset($_POST['form_id']) && isset($_POST['list_id']) ) {
    $entry_id = absint($_POST['entry_id']);
    $form_id =  absint($_POST['form_id']);
    $list_id =  absint($_POST['list_id']);
    $settings = SUPER_Common::get_form_settings($form_id);
    $lists = $settings['_listings']['lists'];
    if(!isset($lists[$list_id])){
        $html = '<div class="super-msg super-error">';
            $html .= esc_html__( 'Incorrect list ID, or list no longer exists:', 'super-forms' );
        $html .= '</div>';
        echo $html;
    }else{
        // Check if invalid Entry ID
        if( ($entry_id==0) || (get_post_type($entry_id)!='super_contact_entry') ) {
            $html = '<div class="super-msg super-error">';
                $html .= esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id;
            $html .= '</div>';
            echo $html;
        }else{
            $list = SUPER_Listings::get_default_listings_settings($lists[$list_id]);
            $entry = get_post($entry_id);
            $allow = SUPER_Listings::get_action_permissions(array('list'=>$list, 'entry'=>$entry));

            // If we are editing an entry
            if($_POST['action']==='super_listings_edit_entry'){
                $allowEditAny = $allow['allowEditAny'];
                $allowEditOwn = $allow['allowEditOwn'];
                if($allowEditAny || $allowEditOwn){
                    // Must be set to populate the form with the entry data
                    $_GET['contact_entry_id'] = $entry_id; 
                    // Check if the form ID equals the post_parent, if not something isn't right and we will not allow the user to edit this entry for savety reasons
                    if($entry->post_parent !== $form_id){
                        $html = '<div class="super-msg super-error">';
                            $html .= esc_html__( 'You do not have permissions to edit this entry.', 'super-forms' ) . ' ' . $entry_id;
                        $html .= '</div>';
                        echo $html;
                    }else{
                        // Check if this entry belongs to a WooCommerce Order
                        // If so display a message to the user that the entry can't be edited
                        $wc_order_id = get_post_meta( $entry_id, '_super_contact_entry_wc_order_id', true );
                        if(!empty($order_id)){
                            $html = '<div class="super-msg super-error">';
                                $html .= esc_html__( 'You are not allowed to edit this entry because it is connected to Order: ', 'super-forms' ) . ' <a href="' . esc_url(get_admin_url() . 'post.php?post=' . $order_id . '&action=edit') . '">#' . $order_id . '</a>';
                            $html .= '</div>';
                            echo $html;
                        }else{
                            // All checks passsed, show the form
                            echo SUPER_Shortcodes::super_form_func( array( 'id'=>$form_id, 'list_id'=>$list_id, 'entry_id'=>$entry_id ) );
                        }
                    }
                }
            }

            // If we are viewing an entry
            if($_POST['action']==='super_listings_view_entry'){
                $allowViewAny = $allow['allowViewAny'];
                $allowViewOwn = $allow['allowViewOwn'];
                // VIEW OWN html can be different from VIEW ANY html
                // this allows to have different templates between what a owner can see and what admins can see
                if($allowViewOwn) {
                    $html_template = $list['view_own']['html_template'];
                    $listing_loop = $list['view_own']['loop_html'];
                }
                // If user has permission to VIEW ANY, then use that html instead
                // this allows admins to have more information/details for a contact entry than the owner himself
                if($allowViewAny) {
                    $html_template = $list['view_any']['html_template'];
                    $listing_loop = $list['view_any']['loop_html'];
                } 

                $entry_title = get_the_title($entry_id);
                $entry_date = get_the_time('Y-m-d @ H:i:s', $entry_id);
                $list = SUPER_Listings::get_default_listings_settings($lists[$list_id]);
                $data = get_post_meta( $entry_id, '_super_contact_entry_data', true );
                $loops = SUPER_Common::retrieve_email_loop_html(
                    array(
                        'listing_loop' => $listing_loop,
                        'data' => $data,
                        'settings' => $settings,
                        'exclude' => array()
                    )
                );
                $listing_loop = $loops['listing_loop'];
                $html = str_replace( '{loop_fields}', $listing_loop, $html_template);
                $html = str_replace( '{listing_entry_id}', $entry_id, $html);
                $html = str_replace( '{listing_form_id}', $form_id, $html);
                $html = str_replace( '{listing_list_id}', $list_id, $html);
                $html = str_replace( '{listing_entry_title}', $entry_title, $html);
                $html = str_replace( '{listing_entry_date}', $entry_date, $html);
                echo do_shortcode($html);
            }
        }
    }
}