<?php
if( !defined('ABSPATH') ) {
    exit;
}
// View entry
if( isset($_POST['action']) && isset($_POST['entry_id']) && isset($_POST['form_id']) && isset($_POST['list_id']) ) {
    $entry_id = absint($_POST['entry_id']);
    $form_id = SUPER_Listings::parse_form_id(sanitize_text_field(wp_unslash($_POST['form_id'])));
    $list_id =  absint($_POST['list_id']);
    if($form_id===false || get_post_type($form_id)!=='super_form') {
        wp_die('-1', '', array('response'=>403));
    }
    $settings = SUPER_Common::get_form_settings($form_id);
    $lists = is_array($settings) && isset($settings['_listings']['lists']) && is_array($settings['_listings']['lists'])
        ? $settings['_listings']['lists']
        : array();
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if(!wp_verify_nonce($nonce, 'super_listings_entry_'.$form_id.'_'.$list_id)) {
        wp_die('-1', '', array('response'=>403));
    }
    if(!isset($lists[$list_id])){
        $html = '<div class="super-msg super-error">';
            $html .= esc_html__( 'Incorrect list ID, or list no longer exists:', 'super-forms' );
        $html .= '</div>';
        echo wp_kses_post($html);
    }else{
        // Check if invalid Entry ID
        if( ($entry_id==0) || (get_post_type($entry_id)!='super_contact_entry') ) {
            $html = '<div class="super-msg super-error">';
                $html .= esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id;
            $html .= '</div>';
            echo wp_kses_post($html);
        }else{
            $list = SUPER_Listings::get_default_listings_settings($lists[$list_id]);
            $entry = get_post($entry_id);
            $allow = SUPER_Listings::get_action_permissions(array('list'=>$list, 'entry'=>$entry));

            // If we are editing an entry
            if($_POST['action']==='super_listings_edit_entry'){
                $allowEditAny = $allow['allowEditAny'];
                $allowEditOwn = $allow['allowEditOwn'];
                if($allowEditAny || $allowEditOwn){
                    // Enforce the list's exact authoritative entry-form scope.
                    if(!SUPER_Listings::entry_is_in_retrieval_scope($list, $entry->post_parent, $form_id)){
                        $html = '<div class="super-msg super-error">';
                            $html .= esc_html__( 'You do not have permissions to edit this entry.', 'super-forms' ) . ' ' . $entry_id;
                        $html .= '</div>';
                        echo wp_kses_post($html);
                    }else{
                        $target_form_id = absint($entry->post_parent);
                        $target_status = get_post_status($target_form_id);
                        if( $target_form_id===0
                            || get_post_type($target_form_id)!=='super_form'
                            || ($target_status!=='publish'
                                && (get_current_user_id()===0 || !current_user_can('edit_post', $target_form_id))) ) {
                            $html = '<div class="super-msg super-error">';
                                $html .= esc_html__( 'You do not have permissions to edit this entry.', 'super-forms' ) . ' ' . $entry_id;
                            $html .= '</div>';
                            echo wp_kses_post($html);
                        }else{
                            // Check if this entry belongs to a WooCommerce Order
                            // If so display a message to the user that the entry can't be edited
                            $wc_order_id = get_post_meta( $entry_id, '_super_contact_entry_wc_order_id', true );
                            if(!empty($wc_order_id)){
                                $html = '<div class="super-msg super-error">';
                                    $html .= esc_html__( 'You are not allowed to edit this entry because it is connected to Order: ', 'super-forms' ) . ' <a href="' . esc_url(get_admin_url() . 'post.php?post=' . $wc_order_id . '&action=edit') . '">#' . esc_html($wc_order_id) . '</a>';
                                $html .= '</div>';
                                echo wp_kses_post($html);
                            }else{
                                $entry_access_issued = SUPER_Common::issue_entry_access_credential($entry);
                                if( !$entry_access_issued && !current_user_can('manage_options') ) {
                                    $html = '<div class="super-msg super-error">';
                                        $html .= esc_html__( 'Unable to authorize this entry for editing.', 'super-forms' );
                                    $html .= '</div>';
                                    echo wp_kses_post($html);
                                }else{
                                    $_GET['contact_entry_id'] = $entry_id;
                                    // All checks passed, show the form
                                    $form_html = SUPER_Shortcodes::super_form_func( array( 'id'=>$target_form_id, 'listing_form_id'=>$form_id, 'list_id'=>$list_id, 'entry_id'=>$entry_id ) );
                                    if( is_string($form_html) && $form_html!=='' ) {
                                        $update_grant = SUPER_Common::current_entry_update_grant_value();
                                        if( $update_grant!==false ) {
                                            SUPER_Common::setClientData( array(
                                                'name' => 'update_contact_entry_' . $target_form_id . '_' . absint($form_id) . '_' . absint($list_id) . '_' . $entry_id,
                                                'value' => $update_grant,
                                                'force' => true
                                            ) );
                                        }
                                    }
                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- complete rendered form document returned by SUPER_Shortcodes::super_form_func() (includes/class-shortcodes.php:5778) for a listing edit modal: the request is nonce-verified at includes/extensions/listings/form-blank-page-template.php:17-18 and the entry is authorized via SUPER_Common::issue_entry_access_credential() (line 70); wp_kses_post() would drop form/input/select/script/style and esc_html() would render the markup as text
                                    echo $form_html;
                                }
                            }
                        }
                    }
                }
            }

            // If we are viewing an entry
            if($_POST['action']==='super_listings_view_entry'
                && SUPER_Listings::entry_is_in_retrieval_scope($list, $entry->post_parent, $form_id)){
                $allowViewAny = $allow['allowViewAny'];
                $allowViewOwn = $allow['allowViewOwn'];
                if($allowViewAny || $allowViewOwn){
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
                $data = SUPER_Data_Access::get_entry_data( $entry_id );
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
}