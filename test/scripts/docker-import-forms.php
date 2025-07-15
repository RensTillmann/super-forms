#!/usr/bin/env php
<?php
/**
 * Import forms directly - simplified for Docker environment
 */

// Check if we have the WordPress environment
if (!function_exists('wp_insert_post')) {
    echo "Error: WordPress not loaded. Run this with WP-CLI.\n";
    exit(1);
}

echo "=== IMPORTING CRITICAL FORMS ===\n\n";

// Define forms to import
$forms = array(
    125 => array(
        'title' => 'Redirect to Page',
        'settings' => 'a:312:{s:9:"header_to";s:13:"{field_email}";s:16:"header_from_type";s:6:"custom";s:11:"header_from";s:24:"no-reply@super-forms.com";s:16:"header_from_name";s:17:"{option_blogname}";s:20:"header_reply_enabled";s:0:"";s:12:"header_reply";s:20:"{option_admin_email}";s:17:"header_reply_name";s:17:"{option_blogname}";s:14:"header_subject";s:59:"Question received from {field_first_name} {field_last_name}";s:9:"header_cc";s:0:"";s:10:"header_bcc";s:0:"";s:17:"header_additional";s:0:"";s:17:"confirm_from_type";s:6:"custom";s:12:"confirm_from";s:24:"no-reply@super-forms.com";s:17:"confirm_from_name";s:17:"{option_blogname}";s:28:"confirm_header_reply_enabled";s:0:"";s:20:"confirm_header_reply";s:20:"{option_admin_email}";s:25:"confirm_header_reply_name";s:17:"{option_blogname}";s:17:"confirm_body_open";s:77:"Dear user {field_first_name} {field_last_name},\n\nThank you for contacting us!";s:12:"confirm_body";s:0:"";s:18:"confirm_email_loop";s:78:"<tr><th valign=\"top\" align=\"right\">{loop_label}</th><td>{loop_value}</td></tr>";s:17:"confirm_header_cc";s:0:"";s:18:"confirm_header_bcc";s:0:"";s:25:"confirm_header_additional";s:0:"";s:17:"admin_attachments";s:0:"";s:19:"confirm_attachments";s:0:"";s:21:"email_template_1_logo";s:0:"";s:22:"email_template_1_title";s:10:"Your title";s:30:"email_template_1_confirm_title";s:10:"Your title";s:25:"email_template_1_subtitle";s:13:"Your subtitle";s:33:"email_template_1_confirm_subtitle";s:13:"Your subtitle";s:26:"email_template_1_copyright";s:30:"&copy; Someone, somewhere 2016";s:24:"email_template_1_socials";s:45:"http://twitter.com/company|url_to_social_icon";s:32:"email_template_1_header_bg_color";s:7:"#5ba1d3";s:35:"email_template_1_header_title_color";s:7:"#ffffff";s:30:"email_template_1_body_bg_color";s:7:"#ffffff";s:36:"email_template_1_body_subtitle_color";s:7:"#474747";s:32:"email_template_1_body_font_color";s:7:"#9e9e9e";s:32:"email_template_1_footer_bg_color";s:7:"#ee4c50";s:34:"email_template_1_footer_font_color";s:7:"#ffffff";s:24:"conditionally_save_entry";s:0:"";s:32:"conditionally_save_entry_check_1";s:0:"";s:32:"conditionally_save_entry_check_3";s:0:"";s:30:"conditionally_save_entry_check";s:0:"";s:27:"contact_entry_custom_status";s:0:"";s:25:"enable_custom_entry_title";s:0:"";s:19:"contact_entry_title";s:13:"Contact entry";s:20:"contact_entry_add_id";s:0:"";s:18:"save_form_progress";s:0:"";s:20:"update_contact_entry";s:0:"";s:34:"contact_entry_custom_status_update";s:0:"";s:24:"retrieve_last_entry_data";s:0:"";s:24:"retrieve_last_entry_form";s:0:"";s:20:"form_show_thanks_msg";s:4:"true";s:16:"form_post_option";s:0:"";s:13:"form_post_url";s:0:"";s:16:"form_post_custom";s:0:"";s:20:"form_post_parameters";s:0:"";s:17:"form_post_timeout";s:1:"5";s:22:"form_post_http_version";s:3:"1.0";s:15:"form_post_debug";s:0:"";s:18:"form_disable_enter";s:0:"";s:20:"form_redirect_option";s:6:"custom";s:13:"form_redirect";s:18:"http://google.com/";s:18:"form_redirect_page";s:0:"";s:27:"form_clear_after_submitting";s:0:"";s:11:"form_locker";s:0:"";s:17:"form_locker_limit";s:2:"10";s:15:"form_locker_msg";s:4:"true";s:16:"form_locker_hide";s:4:"true";s:17:"form_locker_reset";s:0:"";s:28:"form_locker_submission_reset";s:1:"0";s:16:"user_form_locker";s:0:"";s:22:"user_form_locker_limit";s:2:"10";s:20:"user_form_locker_msg";s:4:"true";s:21:"user_form_locker_hide";s:4:"true";s:22:"user_form_locker_reset";s:0:"";s:11:"theme_style";s:0:"";s:17:"theme_center_form";s:0:"";s:9:"theme_rtl";s:0:"";s:16:"theme_icon_color";s:7:"#cdcdcd";s:22:"theme_icon_color_focus";s:7:"#444444";s:13:"theme_icon_bg";s:7:"#ffffff";s:19:"theme_icon_bg_focus";s:7:"#ffffff";s:17:"theme_icon_border";s:7:"#cdcdcd";s:23:"theme_icon_border_focus";s:7:"#cdcdcd";s:23:"theme_field_transparent";s:0:"";s:26:"theme_error_msg_icon_color";s:7:"#FFCBCB";s:17:"font_google_fonts";s:0:"";s:15:"form_custom_css";s:0:"";s:16:"form_button_type";s:2:"2d";s:17:"form_button_align";s:5:"right";s:27:"form_button_icon_visibility";s:7:"visible";s:26:"form_button_icon_animation";s:10:"horizontal";s:16:"form_button_icon";s:0:"";s:21:"csv_attachment_enable";s:0:"";s:19:"csv_attachment_name";s:20:"super-csv-attachment";s:22:"csv_attachment_save_as";s:17:"admin_email_value";s:22:"csv_attachment_exclude";s:0:"";s:24:"csv_attachment_delimiter";s:1:",";s:24:"csv_attachment_enclosure";s:1:"\"";s:25:"frontend_posting_redirect";s:0:"";s:26:"frontend_posting_post_type";s:4:"page";s:23:"frontend_posting_status";s:7:"publish";s:28:"frontend_posting_post_parent";s:0:"";s:31:"frontend_posting_comment_status";s:0:"";s:28:"frontend_posting_ping_status";s:0:"";s:30:"frontend_posting_post_password";s:0:"";s:27:"frontend_posting_menu_order";s:0:"";s:21:"frontend_posting_meta";s:63:"field_name|meta_key\nfield_name2|meta_key2\nfield_name3|meta_key3";s:23:"frontend_posting_author";s:0:"";s:34:"frontend_posting_post_cat_taxonomy";s:0:"";s:26:"frontend_posting_tax_input";s:0:"";s:27:"frontend_posting_tags_input";s:0:"";s:34:"frontend_posting_post_tag_taxonomy";s:0:"";s:28:"frontend_posting_post_format";s:0:"";s:21:"frontend_posting_guid";s:0:"";s:29:"frontend_posting_product_type";s:0:"";s:33:"frontend_posting_product_featured";s:2:"no";s:37:"frontend_posting_product_stock_status";s:7:"instock";s:37:"frontend_posting_product_manage_stock";s:2:"no";s:30:"frontend_posting_product_stock";s:1:"0";s:35:"frontend_posting_product_backorders";s:2:"no";s:42:"frontend_posting_product_sold_individually";s:2:"no";s:37:"frontend_posting_product_downloadable";s:2:"no";s:32:"frontend_posting_product_virtual";s:2:"no";s:35:"frontend_posting_product_visibility";s:7:"visible";s:16:"password_protect";s:0:"";s:25:"password_protect_password";s:24:"mx5tuZM4u#Dw9Gziujq6z*WH";s:30:"password_protect_incorrect_msg";s:37:"Incorrect password, please try again!";s:22:"password_protect_roles";s:0:"";s:27:"password_protect_user_roles";s:0:"";s:21:"password_protect_hide";s:0:"";s:25:"password_protect_show_msg";s:4:"true";s:20:"password_protect_msg";s:47:"You do not have permission to submit this form!";s:22:"password_protect_login";s:0:"";s:27:"password_protect_login_hide";s:0:"";s:31:"password_protect_show_login_msg";s:4:"true";s:26:"password_protect_login_msg";s:89:"You are currently not logged in. In order to submit the form make sure you are logged in!";s:40:"password_protect_show_login_after_submit";s:4:"true";s:31:"password_protect_not_login_hide";s:0:"";s:15:"paypal_checkout";s:0:"";s:11:"paypal_mode";s:0:"";s:21:"paypal_merchant_email";s:0:"";s:20:"paypal_currency_code";s:3:"USD";s:18:"paypal_no_shipping";s:1:"0";s:19:"paypal_payment_type";s:7:"product";s:16:"paypal_item_name";s:14:"Flower (roses)";s:18:"paypal_item_amount";s:4:"5.00";s:20:"paypal_item_quantity";s:1:"1";s:18:"paypal_item_weight";s:0:"";s:18:"paypal_item_number";s:0:"";s:27:"paypal_item_discount_amount";s:0:"";s:25:"paypal_item_discount_rate";s:0:"";s:24:"paypal_item_discount_num";s:0:"";s:20:"paypal_item_shipping";s:0:"";s:21:"paypal_item_shipping2";s:0:"";s:27:"paypal_subscription_periods";s:0:"";s:17:"paypal_cart_items";s:0:"";s:15:"paypal_tax_cart";s:0:"";s:18:"paypal_weight_cart";s:0:"";s:27:"paypal_discount_amount_cart";s:0:"";s:25:"paypal_discount_rate_cart";s:0:"";s:20:"paypal_handling_cart";s:0:"";s:24:"paypal_custom_return_url";s:0:"";s:17:"paypal_return_url";s:48:"https://super-forms.com/my-custom-thank-you-page";s:17:"paypal_cancel_url";s:47:"https://super-forms.com/my-custom-canceled-page";s:24:"paypal_advanced_settings";s:0:"";s:9:"paypal_lc";s:2:"US";s:14:"paypal_charset";s:5:"UTF-8";s:15:"paypal_handling";s:0:"";s:25:"paypal_undefined_quantity";s:0:"";s:18:"paypal_weight_unit";s:3:"lbs";s:14:"paypal_invoice";s:0:"";s:20:"paypal_night_phone_a";s:0:"";s:13:"night_phone_b";s:0:"";s:13:"night_phone_c";s:0:"";s:21:"paypal_custom_address";s:0:"";s:23:"paypal_address_override";s:0:"";s:17:"paypal_first_name";s:0:"";s:16:"paypal_last_name";s:0:"";s:12:"paypal_email";s:0:"";s:15:"paypal_address1";s:0:"";s:15:"paypal_address2";s:0:"";s:11:"paypal_city";s:0:"";s:12:"paypal_state";s:0:"";s:10:"paypal_zip";s:0:"";s:14:"paypal_country";s:0:"";s:29:"paypal_completed_entry_status";s:9:"completed";s:17:"paypal_notify_url";s:0:"";s:28:"paypal_completed_post_status";s:7:"publish";s:30:"paypal_completed_signup_status";s:6:"active";s:37:"register_login_register_not_logged_in";s:0:"";s:29:"register_login_user_id_update";s:0:"";s:35:"register_login_action_skip_register";s:0:"";s:15:"login_user_role";a:1:{i:0;s:0:"";}s:18:"register_user_role";s:13:"administrator";s:27:"register_user_signup_status";s:6:"active";s:27:"register_send_approve_email";s:0:"";s:24:"register_approve_subject";s:25:"Account has been approved";s:22:"register_approve_email";s:261:"Dear {user_login},\n\nYour account has been approved and can now be used!\n\nUsername: <strong>{user_login}</strong>\nPassword: <strong>{user_pass}</strong>\n\nClick <a href=\"{register_login_url}\">here</a> to login into your account.\n\n\nBest regards,\n\n{option_blogname}";s:30:"register_approve_generate_pass";s:0:"";s:25:"register_login_activation";s:6:"verify";s:18:"register_login_url";s:30:"https://super-forms.com/login/";s:25:"register_welcome_back_msg";s:32:"Welcome back {field_user_login}!";s:27:"register_incorrect_code_msg";s:68:"The combination username, password and activation code is incorrect!";s:30:"register_account_activated_msg";s:58:"Hello {field_user_login}, your account has been activated!";s:24:"register_login_user_meta";s:81:"first_name|billing_first_name\nlast_name|billing_last_name\naddress|billing_address";s:32:"register_login_multisite_enabled";s:0:"";s:31:"register_login_multisite_domain";s:12:"{user_email}";s:29:"register_login_multisite_path";s:12:"{user_email}";s:30:"register_login_multisite_title";s:12:"{user_email}";s:27:"register_login_multisite_id";s:1:"1";s:30:"register_login_multisite_email";s:4:"true";s:35:"register_reset_password_success_msg";s:89:"Your password has been reset. We have just send you a new password to your email address.";s:38:"register_reset_password_not_exists_msg";s:53:"We couldn\'t find a user with the given email address!";s:31:"register_reset_password_subject";s:17:"Your new password";s:29:"register_reset_password_email";s:274:"Dear {user_login},\n\nYou just requested to reset your password.\nUsername: <strong>{user_login}</strong>\nPassword: <strong>{register_generated_password}</strong>\n\nClick <a href=\"{register_login_url}\">here</a> to login with your new password.\n\n\nBest regards,\n\n{option_blogname}";s:32:"register_login_not_logged_in_msg";s:98:"You must be logged in to submit this form. Click <a href=\"{register_login_url}\">here</a> to login!";s:31:"register_login_update_user_meta";s:669:"billing_first_name|billing_first_name\nbilling_last_name|billing_last_name\nbilling_company|billing_company\nbilling_address_1|billing_address_1\nbilling_address_2|billing_address_2\nbilling_city|billing_city\nbilling_postcode|billing_postcode\nbilling_country|billing_country\nbilling_state|billing_state\nbilling_phone|billing_phone\nbilling_email|billing_email\nshipping_first_name|shipping_first_name\nshipping_last_name|shipping_last_name\nshipping_company|shipping_company\nshipping_address_1|shipping_address_1\nshipping_address_2|shipping_address_2\nshipping_city|shipping_city\nshipping_postcode|shipping_postcode\nshipping_country|shipping_country\nshipping_state|shipping_state";s:20:"woocommerce_checkout";s:0:"";s:31:"woocommerce_checkout_empty_cart";s:0:"";s:35:"woocommerce_checkout_remove_coupons";s:0:"";s:32:"woocommerce_checkout_remove_fees";s:0:"";s:29:"woocommerce_checkout_products";s:28:"{id}|{quantity}|none|{price}";s:34:"woocommerce_checkout_products_meta";s:18:"{id}|Color|{color}";s:27:"woocommerce_checkout_coupon";s:0:"";s:25:"woocommerce_checkout_fees";s:41:"{fee_name}|{amount}|{taxable}|{tax_class}";s:27:"woocommerce_checkout_fields";s:0:"";s:38:"woocommerce_checkout_fields_skip_empty";s:0:"";s:20:"woocommerce_redirect";s:8:"checkout";s:27:"woocommerce_completed_email";s:0:"";s:24:"woocommerce_completed_to";s:0:"";s:31:"woocommerce_completed_from_type";s:7:"default";s:26:"woocommerce_completed_from";s:0:"";s:31:"woocommerce_completed_from_name";s:0:"";s:42:"woocommerce_completed_header_reply_enabled";s:0:"";s:34:"woocommerce_completed_header_reply";s:0:"";s:39:"woocommerce_completed_header_reply_name";s:0:"";s:29:"woocommerce_completed_subject";s:0:"";s:31:"woocommerce_completed_body_open";s:0:"";s:26:"woocommerce_completed_body";s:0:"";s:32:"woocommerce_completed_body_close";s:0:"";s:32:"woocommerce_completed_email_loop";s:0:"";s:32:"woocommerce_completed_body_nl2br";s:0:"";s:31:"woocommerce_completed_header_cc";s:0:"";s:32:"woocommerce_completed_header_bcc";s:0:"";s:39:"woocommerce_completed_header_additional";s:0:"";s:33:"woocommerce_completed_attachments";s:0:"";s:23:"woocommerce_post_status";s:7:"publish";s:25:"woocommerce_signup_status";s:6:"active";s:11:"import-file";s:0:"";s:13:"popup_enabled";s:0:"";s:15:"popup_logged_in";s:4:"true";s:19:"popup_not_logged_in";s:4:"true";s:15:"popup_page_load";s:4:"true";s:17:"popup_exit_intent";s:0:"";s:11:"popup_leave";s:0:"";s:15:"popup_leave_msg";s:62:"Wait stay with us! Please take the time to fill out our form!?";s:22:"popup_enable_scrolling";s:0:"";s:14:"popup_scrolled";s:1:"0";s:20:"popup_enable_seconds";s:0:"";s:13:"popup_seconds";s:1:"0";s:23:"popup_enable_inactivity";s:0:"";s:16:"popup_inactivity";s:1:"0";s:21:"popup_enable_schedule";s:0:"";s:10:"popup_from";s:10:"2018-09-06";s:10:"popup_till";s:10:"2018-09-06";s:21:"popup_disable_closing";s:0:"";s:15:"popup_close_btn";s:4:"true";s:26:"popup_close_btn_icon_color";s:4:"#fff";s:24:"popup_close_btn_bg_color";s:7:"#00bc65";s:21:"popup_close_btn_label";s:0:"";s:27:"popup_close_btn_label_color";s:7:"#00bc65";s:30:"popup_close_btn_label_bg_color";s:7:"#00bc65";s:29:"popup_close_btn_label_padding";s:0:"";s:25:"popup_close_btn_icon_size";s:2:"14";s:22:"popup_close_btn_border";s:1:"0";s:28:"popup_close_btn_border_color";s:0:"";s:19:"popup_close_btn_top";s:1:"0";s:21:"popup_close_btn_right";s:1:"0";s:23:"popup_close_btn_padding";s:0:"";s:22:"popup_close_btn_radius";s:1:"0";s:20:"popup_enable_padding";s:0:"";s:13:"popup_padding";s:0:"";s:20:"popup_expire_trigger";s:0:"";s:12:"popup_expire";s:1:"1";s:11:"popup_width";s:3:"700";s:22:"popup_background_color";s:7:"#ffffff";s:19:"popup_overlay_color";s:7:"#000000";s:21:"popup_overlay_opacity";s:3:"0.5";s:22:"popup_background_image";s:1:"0";s:29:"popup_background_image_repeat";s:9:"no-repeat";s:27:"popup_background_image_size";s:5:"cover";s:11:"popup_slide";s:4:"none";s:20:"popup_slide_duration";s:3:"300";s:19:"popup_fade_duration";s:3:"300";s:23:"popup_fade_out_duration";s:3:"300";s:12:"popup_sticky";s:7:"default";s:20:"popup_enable_borders";s:0:"";s:17:"popup_border_size";s:1:"0";s:18:"popup_border_color";s:7:"#00bc65";s:28:"popup_border_radius_top_left";s:2:"10";s:29:"popup_border_radius_top_right";s:2:"10";s:31:"popup_border_radius_bottom_left";s:2:"10";s:32:"popup_border_radius_bottom_right";s:2:"10";s:20:"popup_enable_shadows";s:0:"";s:30:"popup_shadow_horizontal_length";s:1:"5";s:28:"popup_shadow_vertical_length";s:1:"5";s:17:"popup_blur_radius";s:2:"15";s:19:"popup_spread_radius";s:1:"3";s:18:"popup_shadow_color";s:7:"#000000";s:20:"popup_shadow_opacity";s:3:"0.7";}',
        'elements' => 'a:3:{i:0;a:4:{s:3:"tag";s:6:"column";s:5:"group";s:15:"layout_elements";s:5:"inner";a:1:{i:0;a:4:{s:3:"tag";s:4:"text";s:5:"group";s:13:"form_elements";s:5:"inner";s:0:"";s:4:"data";a:20:{s:4:"name";s:10:"first_name";s:5:"email";s:11:"First name:";s:5:"label";s:0:"";s:11:"description";s:0:"";s:11:"placeholder";s:15:"Your First Name";s:7:"tooltip";s:0:"";s:10:"validation";s:5:"empty";s:5:"error";s:0:"";s:7:"grouped";s:1:"0";s:9:"maxlength";s:1:"0";s:9:"minlength";s:1:"0";s:5:"width";s:1:"0";s:7:"exclude";s:1:"0";s:14:"error_position";s:0:"";s:13:"icon_position";s:7:"outside";s:10:"icon_align";s:4:"left";s:4:"icon";s:4:"user";s:18:"conditional_action";s:8:"disabled";s:19:"conditional_trigger";s:3:"all";s:17:"conditional_items";a:1:{i:0;a:3:{s:5:"field";s:4:"name";s:5:"logic";s:8:"contains";s:5:"value";s:0:"";}}}}}s:4:"data";a:3:{s:4:"size";s:3:"1/2";s:6:"margin";s:0:"";s:18:"conditional_action";s:8:"disabled";}}i:1;a:4:{s:3:"tag";s:6:"column";s:5:"group";s:15:"layout_elements";s:5:"inner";a:1:{i:0;a:4:{s:3:"tag";s:4:"text";s:5:"group";s:13:"form_elements";s:5:"inner";s:0:"";s:4:"data";a:20:{s:4:"name";s:9:"last_name";s:5:"email";s:10:"Last name:";s:5:"label";s:0:"";s:11:"description";s:0:"";s:11:"placeholder";s:14:"Your Last Name";s:7:"tooltip";s:0:"";s:10:"validation";s:5:"empty";s:5:"error";s:0:"";s:7:"grouped";s:1:"0";s:9:"maxlength";s:1:"0";s:9:"minlength";s:1:"0";s:5:"width";s:1:"0";s:7:"exclude";s:1:"0";s:14:"error_position";s:0:"";s:13:"icon_position";s:7:"outside";s:10:"icon_align";s:4:"left";s:4:"icon";s:4:"user";s:18:"conditional_action";s:8:"disabled";s:19:"conditional_trigger";s:3:"all";s:17:"conditional_items";a:1:{i:0;a:3:{s:5:"field";s:4:"name";s:5:"logic";s:8:"contains";s:5:"value";s:0:"";}}}}}s:4:"data";a:3:{s:4:"size";s:3:"1/2";s:6:"margin";s:0:"";s:18:"conditional_action";s:8:"disabled";}}i:2;a:4:{s:3:"tag";s:6:"column";s:5:"group";s:15:"layout_elements";s:5:"inner";a:2:{i:0;a:4:{s:3:"tag";s:4:"text";s:5:"group";s:13:"form_elements";s:5:"inner";s:0:"";s:4:"data";a:20:{s:4:"name";s:5:"email";s:5:"email";s:14:"Email address:";s:5:"label";s:0:"";s:11:"description";s:0:"";s:11:"placeholder";s:18:"Your Email Address";s:7:"tooltip";s:0:"";s:10:"validation";s:5:"email";s:5:"error";s:0:"";s:7:"grouped";s:1:"0";s:9:"maxlength";s:1:"0";s:9:"minlength";s:1:"0";s:5:"width";s:1:"0";s:7:"exclude";s:1:"0";s:14:"error_position";s:0:"";s:13:"icon_position";s:7:"outside";s:10:"icon_align";s:4:"left";s:4:"icon";s:8:"envelope";s:18:"conditional_action";s:8:"disabled";s:19:"conditional_trigger";s:3:"all";s:17:"conditional_items";a:1:{i:0;a:3:{s:5:"field";s:10:"first_name";s:5:"logic";s:8:"contains";s:5:"value";s:0:"";}}}}i:1;a:4:{s:3:"tag";s:8:"textarea";s:5:"group";s:13:"form_elements";s:5:"inner";s:0:"";s:4:"data";a:9:{s:4:"name";s:8:"question";s:5:"email";s:8:"Question";s:11:"placeholder";s:23:"Ask us any questions...";s:10:"validation";s:4:"none";s:13:"icon_position";s:7:"outside";s:10:"icon_align";s:4:"left";s:4:"icon";s:8:"question";s:18:"conditional_action";s:8:"disabled";s:19:"conditional_trigger";s:3:"all";}}}s:4:"data";a:3:{s:4:"size";s:3:"1/1";s:6:"margin";s:0:"";s:18:"conditional_action";s:8:"disabled";}}}',
        'date' => '2015-12-09 18:32:27'
    )
);

// Create test form
foreach ($forms as $original_id => $form_data) {
    echo "Creating test form (original ID: $original_id)...\n";
    
    // Create post
    $post_data = array(
        'post_title' => $form_data['title'],
        'post_type' => 'super_form',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_date' => $form_data['date']
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        echo "✗ Failed to create form: " . $post_id->get_error_message() . "\n";
        continue;
    }
    
    // Unserialize and save settings
    $settings = unserialize($form_data['settings']);
    update_post_meta($post_id, '_super_form_settings', $settings);
    
    // Unserialize and save elements
    $elements = unserialize($form_data['elements']);
    update_post_meta($post_id, '_super_elements', $elements);
    
    // Set legacy version to trigger email migration
    update_post_meta($post_id, '_super_version', '6.3.999');
    
    echo "✓ Created form with ID: $post_id\n";
    echo "  Title: " . $form_data['title'] . "\n";
    echo "  Has redirect settings: " . ($settings['form_redirect_option'] === 'custom' ? 'Yes' : 'No') . "\n";
    echo "\n";
}

// Also create a simple test form with conditional logic
echo "Creating form with conditional logic for testing...\n";
$test_form = array(
    'post_title' => 'Test Form - Conditional Logic',
    'post_type' => 'super_form',
    'post_status' => 'publish',
    'post_author' => 1
);

$test_id = wp_insert_post($test_form);

if (!is_wp_error($test_id)) {
    // Simple elements with conditional logic
    $test_elements = array(
        array(
            'tag' => 'text',
            'group' => 'form_elements',
            'data' => array(
                'name' => 'trigger_field',
                'email' => 'Type "show" to reveal hidden field:',
                'placeholder' => 'Type "show" here'
            )
        ),
        array(
            'tag' => 'text',
            'group' => 'form_elements',
            'data' => array(
                'name' => 'hidden_field',
                'email' => 'Hidden field (visible when trigger = "show"):',
                'placeholder' => 'This was hidden!',
                'conditional_action' => 'show',
                'conditional_trigger' => 'all',
                'conditional_items' => array(
                    array(
                        'field' => 'trigger_field',
                        'logic' => 'equal',
                        'value' => 'show'
                    )
                )
            )
        )
    );
    
    update_post_meta($test_id, '_super_elements', $test_elements);
    update_post_meta($test_id, '_super_form_settings', array());
    
    echo "✓ Created test form with ID: $test_id\n";
    echo "  Has conditional logic: Yes\n";
}

echo "\n=== READY FOR TESTING ===\n";
echo "1. Access WordPress at: http://localhost:8080/wp-admin/\n";
echo "2. Login: admin / admin\n";
echo "3. Go to Super Forms > All Forms\n";
echo "4. Edit the forms and check for JavaScript errors\n";
echo "\nDone!\n";