<?php
/**
 * Settings related functions and actions.
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Settings
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Settings' ) ) :

/**
 * SUPER_Settings Class
 */
class SUPER_Settings {
    
    /** 
	 *	All the fields
	 *
	 *	Create an array with all the fields
	 *
	 *	@since		1.0.0
	 */
	public static function fields( $settings=null, $default=0 ) {
		
        global $wpdb;

        $mysql_version = $wpdb->get_var("SELECT VERSION() AS version");
        
        if( $settings==null) {
            $settings = get_option( 'super_settings', true );
        }
        $settings = stripslashes_deep( $settings );
        
        $array = array();
        
        $array = apply_filters( 'super_settings_start_filter', $array, array( 'settings'=>$settings ) );
        
        /** 
         *	Email Headers
         *
         *	@since		1.0.0
        */
        $array['email_headers'] = array(
            'name' => __( 'Email Headers', 'super-forms' ),
            'label' => __( 'Email Headers', 'super-forms' ),
            'fields' => array(
                'header_to' => array(
                    'name' => __( 'Send mail to', 'super-forms' ),
                    'desc' => __( 'Recipient(s) email address seperated with commas', 'super-forms' ),
                    'placeholder' => __( 'your@email.com, your@email.com', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_to', $settings, '{option_admin_email}' ),
                ),
                'header_from_type' => array(
                    'name'=> __( 'From', 'super-forms' ),
                    'desc' => __( 'Enter a custom email address or use the blog settings', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_from_type', $settings, 'default' ),
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'default' => __(  'Default blog email and name', 'super-forms' ),
                        'custom' => __(  'Custom from', 'super-forms' ),
                    )
                ),
                'header_from' => array(
                    'name' => __( 'From email address', 'super-forms' ),
                    'desc' => __( 'Example: info@companyname.com', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_from', $settings, '{option_admin_email}' ),
                    'placeholder' => __( 'Company Email Address', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'header_from_type',
                    'filter_value'=>'custom',
                ),
                'header_from_name' => array(
                    'name' => __( 'From name', 'super-forms' ),
                    'desc' => __( 'Example: Company Name', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_from_name', $settings, '{option_blogname}' ),
                    'placeholder' => __( 'Your Company Name', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'header_from_type',
                    'filter_value'=>'custom',
                ),
                'header_subject' => array(
                    'name' => __( 'Email subject', 'super-forms' ),
                    'desc' => __( 'The subject for this email', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_subject', $settings, 'New question' ),
                    'placeholder' => __( 'New question', 'super-forms' ),
                ),
                'header_content_type' => array(
                    'name' => __( 'Email content type', 'super-forms' ),
                    'desc' => __( 'The content type to use for this email', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_content_type', $settings, 'html' ),
                    'type'=>'select',
                    'values'=>array(
                        'html'=>'HTML',
                        'plain'=>'Plain text',
                    )
                ),
                'header_charset' => array(
                    'name' => __( 'Email Charset', 'super-forms' ),
                    'desc' => __( 'The content type to use for this email.<br />Example: UTF-8 or ISO-8859-1', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_charset', $settings, 'UTF-8' ),
                ),
                'header_cc' => array(
                    'name' => __( 'CC email to', 'super-forms' ),
                    'desc' => __( 'Send copy to following address(es)', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_cc', $settings, '' ),
                    'placeholder' => __( 'someones@email.com, someones@emal.com', 'super-forms' ),
                ),
                'header_bcc' => array(
                    'name' => __( 'BCC email to', 'super-forms' ),
                    'desc' => __( 'Send copy to following address(es), without able to see the address', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_bcc', $settings, '' ),
                    'placeholder' => __( 'someones@email.com, someones@emal.com', 'super-forms' ),
                ),
                'header_additional' => array(
                    'name' => __('Additional Headers', 'super-forms' ),
                    'desc' => __('Add any extra email headers here (put each header on a new line)', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_additional', $settings, '' ),
                    'type' =>'textarea'
                )
            )
        );
        $array = apply_filters( 'super_settings_after_email_headers_filter', $array, array( 'settings'=>$settings ) );
        
             
        /** 
         *	Email Settings
         *
         *	@since		1.0.0
        */
        $array['email_settings'] = array(        
            'name' => __( 'Email Settings', 'super-forms' ),
            'label' => __( 'Email Settings', 'super-forms' ),
            'fields' => array(        
                'send' => array(
                    'name' => __( 'Send Admin Email', 'super-forms' ),
                    'desc' => __( 'Send or do not send the admin emails', 'super-forms' ),
                    'default' => self::get_value( $default, 'send', $settings, 'yes' ),
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'yes' => __( 'Send an admin email', 'super-forms' ),
                        'no' => __( 'Do not send an admin email', 'super-forms' ),
                    )
                ),
                'email_body_open' => array(
                    'name' => __( 'Email Body Open', 'super-forms' ),
                    'desc' => __( 'This content will be placed before the body content of the email.', 'super-forms' ),
                    'default' => self::get_value( $default, 'email_body_open', $settings, __( 'The following information has been send by the submitter:', 'super-forms' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'email_body' => array(
                    'name' => __( 'Email Body', 'super-forms' ),
                    'desc' => __( 'Use a custom email body. Use {loop_fields} to retrieve the loop.', 'super-forms' ),
                    'default' => self::get_value( $default, 'email_body', $settings, __( '<table cellpadding="5">{loop_fields}</table>', 'super-forms' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'email_body_close' => array(
                    'name' => __( 'Email Body Close', 'super-forms' ),
                    'desc' => __( 'This content will be placed after the body content of the email.', 'super-forms' ),
                    'default' => self::get_value( $default, 'email_body_close', $settings, __( "Best regards, {option_blogname}", "super-forms" ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'email_loop' => array(
                    'name' => __( 'Email Loop', 'super-forms' ),
                    'desc' => __( 'Use a custom loop. Use {loop_label and {loop_value} to retrieve values.', 'super-forms' ),
                    'default' => self::get_value( $default, 'email_loop', $settings, __( '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>', 'super-forms' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',   
                ),
                'confirm' => array(
                    'name' => __( 'Confirmation Email', 'super-forms' ),
                    'desc' => __( 'Send or do not send confirmation emails', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm', $settings, 'yes' ),
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'yes' => __( 'Send a confirmation email', 'super-forms' ),
                        'no' => __( 'Do not send a confirmation email', 'super-forms' ),
                    )
                ),
                'confirm_to' => array(
                    'name' => __( 'Confirmation To', 'super-forms' ),
                    'desc' => __( 'Recipient(s) email address seperated by commas', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_to', $settings, '{field_email}' ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',   
                ),
                'confirm_from_type' => array(
                    'name'=> __( 'Confirmation from', 'super-forms' ),
                    'desc' => __( 'Enter a custom email address or use the blog settings', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_from_type', $settings, 'default' ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',   
                    'type'=>'select',
                    'values'=>array(
                        'default' => __(  'Default blog email and name', 'super-forms' ),
                        'custom' => __(  'Custom from', 'super-forms' ),
                    )
                ),
                'confirm_from' => array(
                    'name' => __( 'Confirmation from email address', 'super-forms' ),
                    'desc' => __( 'Example: info@companyname.com', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_from', $settings, '{option_admin_email}' ),
                    'placeholder' => __( 'Company Email Address', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm_from_type',
                    'filter_value'=>'custom',
                ),
                'confirm_from_name' => array(
                    'name' => __( 'Confirmation from name', 'super-forms' ),
                    'desc' => __( 'Example: Company Name', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_from_name', $settings, '{option_blogname}' ),
                    'placeholder' => __( 'Your Company Name', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm_from_type',
                    'filter_value'=>'custom',
                ),
                'confirm_subject' => array(
                    'name' => __( 'Confirmation Subject', 'super-forms' ),
                    'desc' => __( 'The confirmation subject for this email', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_subject', $settings, __( 'Thank you!', 'super-forms' ) ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                ),
                'confirm_body_open' => array(
                    'name' => __( 'Confirm Body Open', 'super-forms' ),
                    'desc' => __( 'This content will be placed before the confirmation email body.', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_body_open', $settings, __( "Dear user,\n\nThank you for contacting us!", "super-forms" ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                ),
                'confirm_body' => array(
                    'name' => __( 'Confirm Body', 'super-forms' ),
                    'desc' => __( 'Use a custom email body. Use {loop_fields} to retrieve the loop.', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_body', $settings, __( '<table cellpadding="5">{loop_fields}</table>', 'super-forms' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                ),
                'confirm_body_close' => array(
                    'name' => __( 'Confirm Body Close', 'super-forms' ),
                    'desc' => __( 'This content will be placed after the confirmation email body.', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_body_close', $settings, __( "We will reply within 48 hours.\n\nBest Regards, {option_blogname}", "super-forms" ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                )
            )
        );
        $array = apply_filters( 'super_settings_after_email_settings_filter', $array, array( 'settings'=>$settings ) );

             
        /** 
         *	Email Template
         *
         *	@since		1.0.0
        */
        $array['email_template'] = array(        
            'name' => __( 'Email Template', 'super-forms' ),
            'label' => __( 'Email Template', 'super-forms' ),
            'fields' => array(        
                'email_template' => array(
                    'name' => __( 'Select email template', 'super-forms' ),
                    'desc' => __( 'Choose which email template you would like to use', 'super-forms' ),
                    'info'=>'<a target="_blank" href="http://codecanyon.net/user/feeling4design/portfolio">'.__('Click here to check out all available email templates!', 'super-forms' ).'</a>',
                    'type'=>'select',
                    'default' => self::get_value( $default, 'email_template', $settings, 'default_email_template' ),
                    'filter'=>true,
                    'values'=>array(
                        'default_email_template' => __('Default email template', 'super-forms' ),
                    )
                )
            )
        );
        $array = apply_filters( 'super_settings_after_email_template_filter', $array, array( 'settings'=>$settings ) );

                
        /** 
         *	Form Settings
         *
         *	@since		1.0.0
        */
        $array['form_settings'] = array(        
            'name' => __( 'Form Settings', 'super-forms' ),
            'label' => __( 'Form Settings', 'super-forms' ),
            'fields' => array(        
                'save_contact_entry' => array(
                    'name' => __( 'Save data', 'super-forms' ),
                    'desc' => __( 'Choose if you want to save the user data as a Contact Entry', 'super-forms' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'save_contact_entry', $settings, 'yes' ),
                    'filter'=>true,
                    'values'=>array(
                        'yes' => __('Save as Contact Entry', 'super-forms' ),
                        'no' => __('Do not save data', 'super-forms' ),
                    )
                ),

                // @since 1.2.6  - custom contact entry titles
                'enable_custom_entry_title' => array(
                    'hidden_setting' => true,
                    'default' => self::get_value( $default, 'enable_custom_entry_title', $settings, '' ),
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => __( 'Enable custom entry titles', 'super-forms' ),
                    ),
                    'parent' => 'save_contact_entry',
                    'filter_value' => 'yes',
                ),
                'contact_entry_title' => array(
                    'name' => __('Enter a custom entry title', 'super-forms' ),
                    'desc' => __( 'You can use field tags {field_name} if you want', 'super-forms' ),
                    'default' => self::get_value( $default, 'contact_entry_title', $settings, __( 'Contact entry', 'super-forms' ) ),
                    'filter'=>true,
                    'parent'=>'enable_custom_entry_title',
                    'filter_value'=>'true',   
                ),
                'contact_entry_add_id' => array(
                    'default' => self::get_value( $default, 'contact_entry_add_id', $settings, '' ),
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => __( 'Append entry ID after the custom title', 'super-forms' ),
                    ),
                    'parent' => 'enable_custom_entry_title',
                    'filter_value' => 'true',
                ),

                // @since 2.2.0 - update contact entry data if a contact entry was found based on search field or when POST or GET contained the entry id: ['contact_entry_id']
                'update_contact_entry' => array(
                    'name' => __( 'Enable contact entry updating', 'super-forms' ),
                    'label' => __( 'This only works if your form contains a search field that searches contact entries based on their title or when $_GET or $_POST contains a key [contact_entry_id] with the entry ID', 'super-forms' ),
                    'hidden_setting' => true,
                    'default' => self::get_value( $default, 'update_contact_entry', $settings, '' ),
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => __( 'Update contact entry data (if contact entry was found)', 'super-forms' ),
                    ),
                ),


                /** 
                 *  Form action
                 *
                 *  @deprecated since 1.0.6
                */
                // 'form_actions' => array()
               
                'form_duration' => array(
                    'name' => __( 'Error FadeIn Duration', 'super-forms' ),
                    'desc' => __( 'The duration for error messages to popup in milliseconds.', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_duration', $settings, 500 ),
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>1000,
                    'steps'=>100,
                ),

                'form_show_thanks_msg' => array(
                    'hidden_setting' => true,
                    'default' => self::get_value( $default, 'form_show_thanks_msg', $settings, 'true' ),
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => __( 'Show thank you message', 'super-forms' ),
                    ),
                ),
                'form_thanks_title' => array(
                    'name' => __( 'Thanks Title', 'super-forms' ),
                    'desc' => __( 'A custom thank you title shown after a user completed the form.', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_thanks_title', $settings, __( 'Thank you!', 'super-forms' ) ),
                    'filter'=>true,
                    'parent' => 'form_show_thanks_msg',
                    'filter_value' => 'true',
                ),
                'form_thanks_description' => array(
                    'name' => __( 'Thanks Description', 'super-forms' ),
                    'desc' => __( 'A custom thank you description shown after a user completed the form.', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_thanks_description', $settings, __( 'We will reply within 24 hours.', 'super-forms' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent' => 'form_show_thanks_msg',
                    'filter_value' => 'true',
                ),
                'form_preload' => array(
                    'name' => __( 'Preloader', 'super-forms' ),
                    'desc' => __( 'Custom use of preloader for the form.', 'super-forms' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'form_preload', $settings, '1' ),
                    'values'=>array(
                        '1' => __( 'Enabled', 'super-forms' ),
                        '0' => __( 'Disabled', 'super-forms' ),
                    ),
                ),
                'enable_ajax' => array(
                    'hidden' => true,
                    'name' => __( 'Enable Ajax', 'super-forms' ),
                    'desc' => __( 'If your site uses Ajax to request post content activate this option. This makes sure styles/scripts are loaded before the Ajax request.', 'super-forms' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'enable_ajax', $settings, '0' ),
                    'values'=>array(
                        '0' => __( 'Disabled', 'super-forms' ),
                        '1' => __( 'Enabled', 'super-forms' ),
                    ),
                ),
                'form_recaptcha' => array(
                    'hidden' => true,
                    'name' => '<a href="https://www.google.com/recaptcha" target="_blank">'.__( 'reCAPTCHA key', 'super-forms' ).'</a>',
                    'default' => self::get_value( $default, 'form_recaptcha', $settings, '' ),
                ),
                'form_recaptcha_secret' => array(
                    'hidden' => true,
                    'name' => '<a href="https://www.google.com/recaptcha" target="_blank">'.__( 'reCAPTCHA secret', 'super-forms' ).'</a>',
                    'default' => self::get_value( $default, 'form_recaptcha_secret', $settings, '' ),
                ),

                // @since 2.2.0 - Custom form post method
                'form_post_option' => array(
                    'hidden_setting' => true,
                    'default' => self::get_value( $default, 'form_post_option', $settings, '' ),
                    'type' => 'checkbox',
                    'filter' => true,
                    'values' => array(
                        'true' => __( 'Enable form POST method', 'super-forms' ),
                    ),
                ),
                'form_post_url' => array(
                    'name' => __( 'Enter a custom form post URL', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_post_url', $settings, '' ),
                    'filter' => true,
                    'parent' => 'form_post_option',
                    'filter_value' => 'true',   
                ),

                'form_redirect_option' => array(
                    'name' => __( 'Form redirect option', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_redirect_option', $settings, '' ),
                    'filter' => true,
                    'type' => 'select',
                    'values' => array(
                        '' => __( 'No Redirect', 'super-forms' ),
                        'custom' => __( 'Custom URL', 'super-forms' ),
                        'page' => __( 'Existing Page', 'super-forms' ),
                    )
                ),
                'form_redirect' => array(
                    'name' => __('Enter a custom URL to redirect to', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_redirect', $settings, '' ),
                    'filter' => true,
                    'parent' => 'form_redirect_option',
                    'filter_value' => 'custom',   
                ),
                'form_redirect_page' => array(
                    'name' => 'Select a page to link to',
                    'default' => self::get_value( $default, 'form_redirect_page', $settings, '' ),
                    'type' =>'select',
                    'values' => SUPER_Common::list_posts_by_type_array('page'),
                    'filter' => true,
                    'parent' => 'form_redirect_option',
                    'filter_value' => 'page',    
                ),

                // @since 2.0.0  - do not hide form after submitting
                'form_hide_after_submitting' => array(
                    'hidden_setting' => true,
                    'default' => self::get_value( $default, 'form_hide_after_submitting', $settings, 'true' ),
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => __( 'Hide form after submitting', 'super-forms' ),
                    ),
                ),
                // @since 2.0.0  - reset / clear the form after submitting
                'form_clear_after_submitting' => array(
                    'hidden_setting' => true,
                    'default' => self::get_value( $default, 'form_clear_after_submitting', $settings, '' ),
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => __( 'Clear / reset the form after submitting', 'super-forms' ),
                    ),
                ),


            )
        );
        $array = apply_filters( 'super_settings_after_form_settings_filter', $array, array( 'settings'=>$settings ) );


        /** 
         *	Theme & Colors
         *
         *	@since		1.0.0
        */
        $array['theme_colors'] = array(        
            'name' => __( 'Theme & Colors', 'super-forms' ),
            'label' => __( 'Theme & Colors', 'super-forms' ),
            'fields' => array(        
                'theme_style' => array(
                    'name' => __( 'Theme style', 'super-forms' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'theme_style', $settings, '' ),
                    'values'=>array(
                        '' => __( 'Default Squared', 'super-forms' ),
                        'super-default-rounded' => __( 'Default Rounded', 'super-forms' ),
                        'super-full-rounded' => __( 'Full Rounded', 'super-forms' ),
                        'super-style-one' => __( 'Minimal', 'super-forms' ),
                    ),
                ),
                'theme_hide_icons' => array(
                    'name' => __( 'Hide field icons', 'super-forms' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'theme_hide_icons', $settings, 'yes' ),
                    'values'=>array(
                        'yes' => __( 'Yes (hide)', 'super-forms' ),
                        'no' => __( 'No (show)', 'super-forms' ),
                    ),
                    'filter'=>true
                ),

                // @since 1.2.8  - RTL support
                'theme_rtl' => array(
                    'hidden_setting' => true,
                    'default' => self::get_value( $default, 'theme_rtl', $settings, '' ),
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => __( 'Enable RTL (Right To Left layout)', 'super-forms' ),
                    ),
                ),

                'theme_icon_colors' => array(
                    'name' => __('Icon Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_icon_color'=>array(
                            'label'=>'Icon color',
                            'default' => self::get_value( $default, 'theme_icon_color', $settings, '#B3DBDD' ),
                        ),
                        'theme_icon_color_focus'=>array(
                            'label'=>'Icon color focus',
                            'default' => self::get_value( $default, 'theme_icon_color_focus', $settings, '#4EB1B6' ),
                        ),
                        'theme_icon_bg'=>array(
                            'label'=>'Icon background',
                            'default' => self::get_value( $default, 'theme_icon_bg', $settings, '#ffffff' ),
                        ),
                        'theme_icon_bg_focus'=>array(
                            'label'=>'Icon background focus',
                            'default' => self::get_value( $default, 'theme_icon_bg_focus', $settings, '#ffffff' ),
                        ),
                        'theme_icon_border'=>array(
                            'label'=>'Icon border color',
                            'default' => self::get_value( $default, 'theme_icon_border', $settings, '#cdcdcd' ),
                        ),
                        'theme_icon_border_focus'=>array(
                            'label'=>'Icon border color focus',
                            'default' => self::get_value( $default, 'theme_icon_border_focus', $settings, '#cdcdcd' ),
                        ),                            
                    ),
                    'filter'=>true,
                    'parent'=>'theme_hide_icons',
                    'filter_value'=>'no',
                ),
                'theme_label_colors' => array(
                    'name' => __( 'Label & Description colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_label'=>array(
                            'label'=>__( 'Field label', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_field_label', $settings, '#444444' ),
                        ),
                        'theme_field_description'=>array(
                            'label'=>__( 'Field description', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_field_description', $settings, '#8e8e8e' ),
                        ),
                    ),
                ),
                'theme_ui_checkbox_colors' => array(
                    'name' => __( 'Checkbox & Radio colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_checkbox_border'=>array(
                            'label'=>__( 'Check/Radio border', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_checkbox_border', $settings, '#4EB1B6' ),
                        ),
                        'theme_ui_checkbox_inner'=>array(
                            'label'=>__( 'Check/Radio inner', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_checkbox_inner', $settings, '#4EB1B6' ),
                        ),
                    ),
                ),
                'theme_ui_quantity_colors' => array(
                    'name' => __( 'Quantity button colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_quantity_bg'=>array(
                            'label'=>__( 'Button background', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_quantity_bg', $settings, '#4EB1B6' ),
                        ),
                        'theme_ui_quantity_font'=>array(
                            'label'=>__( 'Button font', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_quantity_font', $settings, '#ffffff' ),
                        ),
                        'theme_ui_quantity_bg_hover'=>array(
                            'label'=>__( 'Button background hover', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_quantity_bg_hover', $settings, '#7ed0d4' ),
                        ),
                        'theme_ui_quantity_font_hover'=>array(
                            'label'=>__( 'Button font hover', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_quantity_font_hover', $settings, '#ffffff' ),
                        ),
                    ),
                ),
                'theme_ui_slider_colors' => array(
                    'name' => __( 'Slider colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_slider_dragger'=>array(
                            'label'=>__( 'Dragger color', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_slider_dragger', $settings, '#4EB1B6' ),
                        ),
                        'theme_ui_slider_track'=>array(
                            'label'=>__( 'Track color', 'super-forms' ),
                            'default' => self::get_value( $default, 'theme_ui_slider_track', $settings, '#CDCDCD' ),
                        ),
                    ),
                ),
                'theme_field_colors' => array(
                    'name' => __('Field Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_colors_top'=>array(
                            'label'=>'Gradient Top',
                            'default' => self::get_value( $default, 'theme_field_colors_top', $settings, '#ffffff' ),
                        ),
                        'theme_field_colors_bottom'=>array(
                            'label'=>'Gradient Bottom',
                            'default' => self::get_value( $default, 'theme_field_colors_bottom', $settings, '#ffffff' ),
                        ),
                        'theme_field_colors_border'=>array(
                            'label'=>'Border Color',
                            'default' => self::get_value( $default, 'theme_field_colors_border', $settings, '#cdcdcd' ),
                        ),
                        'theme_field_colors_font'=>array(
                            'label'=>'Font Color',
                            'default' => self::get_value( $default, 'theme_field_colors_font', $settings, '#444444' ),
                        ),
                        'theme_field_colors_placeholder'=>array(
                            'label'=>'Placeholder Color',
                            'default' => self::get_value( $default, 'theme_field_colors_placeholder', $settings, '#444444' ),
                        ),                        
                    ),
                ),
                'theme_field_colors_focus' => array(
                    'name' => __('Field Colors Focus', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_colors_top_focus'=>array(
                            'label'=>'Gradient Top Focus',
                            'default' => self::get_value( $default, 'theme_field_colors_top_focus', $settings, '#ffffff' ),
                        ),
                        'theme_field_colors_bottom_focus'=>array(
                            'label'=>'Gradient Bottom Focus',
                            'default' => self::get_value( $default, 'theme_field_colors_bottom_focus', $settings, '#ffffff' ),
                        ),
                        'theme_field_colors_border_focus'=>array(
                            'label'=>'Border Color Focus',
                            'default' => self::get_value( $default, 'theme_field_colors_border_focus', $settings, '#cdcdcd' ),
                        ),
                        'theme_field_colors_font_focus'=>array(
                            'label'=>'Font Color Focus',
                            'default' => self::get_value( $default, 'theme_field_colors_font_focus', $settings, '#444444' ),
                        ),
                        'theme_field_colors_placeholder_focus'=>array(
                            'label'=>'Placeholder Color',
                            'default' => self::get_value( $default, 'theme_field_colors_placeholder_focus', $settings, '#444444' ),
                        ),                                                
                    ),
                ),
                'theme_field_transparent' => array(
                    'desc' => __( 'Allows you to set the field background to transparent', 'super-forms' ), 
                    'default' => self::get_value( $default, 'theme_field_transparent', $settings, '' ),
                    'type' => 'checkbox', 
                    'filter'=>true,
                    'values' => array(
                        'true' => __( 'Enable transparent backgrounds', 'super-forms' ),
                    )
                ),
                'theme_rating_colors' => array(
                    'name' => __('Rating Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_rating_color'=>array(
                            'label'=>'Rating color',
                            'default' => self::get_value( $default, 'theme_rating_color', $settings, '#cdcdcd' ),
                        ),
                        'theme_rating_bg'=>array(
                            'label'=>'Rating background',
                            'default' => self::get_value( $default, 'theme_rating_bg', $settings, '#ffffff' ),
                        ),
                        'theme_rating_border'=>array(
                            'label'=>'Rating border color',
                            'default' => self::get_value( $default, 'theme_rating_border', $settings, '#cdcdcd' ),
                        ),
                    ),
                ),                    
                'theme_rating_colors_hover' => array(
                    'name' => __('Rating Colors Hover', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_rating_color_hover'=>array(
                            'label'=>'Rating color',
                            'default' => self::get_value( $default, 'theme_rating_color_hover', $settings, '#f7f188' ),
                        ),
                        'theme_rating_bg_hover'=>array(
                            'label'=>'Rating background',
                            'default' => self::get_value( $default, 'theme_rating_bg_hover', $settings, '#ffffff' ),
                        ),
                    ),
                ),
                'theme_rating_colors_active' => array(
                    'name' => __('Rating Colors Active', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_rating_color_active'=>array(
                            'label'=>'Rating color',
                            'default' => self::get_value( $default, 'theme_rating_color_active', $settings, '#f7ea00' ),
                        ),
                        'theme_rating_bg_active'=>array(
                            'label'=>'Rating background',
                            'default' => self::get_value( $default, 'theme_rating_bg_active', $settings, '#ffffff' ),
                        ),
                    ),
                ),
                'theme_product_colors' => array(
                    'name' => __('Product, Discount & Total Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_currency_color'=>array(
                            'label'=>'Currency color',
                            'default' => self::get_value( $default, 'theme_currency_color', $settings, '#139307' ),
                        ),
                        'theme_amount_color'=>array(
                            'label'=>'Amount color',
                            'default' => self::get_value( $default, 'theme_amount_color', $settings, '#139307' ),
                        ),
                        'theme_quantity_color'=>array(
                            'label'=>'Quantity color',
                            'default' => self::get_value( $default, 'theme_quantity_color', $settings, '#ff0000' ),
                        ),
                        'theme_percentage_color'=>array(
                            'label'=>'Percentage color',
                            'default' => self::get_value( $default, 'theme_percentage_color', $settings, '#139307' ),
                        ),                            
                    ),
                ),
                'theme_progress_bar_colors' => array(
                    'name' => __('Progress Bar Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_progress_bar_primary_color'=>array(
                            'label'=>'Primary color',
                            'default' => self::get_value( $default, 'theme_progress_bar_primary_color', $settings, '#87CC83' ),
                        ),
                        'theme_progress_bar_secondary_color'=>array(
                            'label'=>'Secondary color',
                            'default' => self::get_value( $default, 'theme_progress_bar_secondary_color', $settings, '#E2E2E2' ),
                        ),
                        'theme_progress_bar_border_color'=>array(
                            'label'=>'Border color',
                            'default' => self::get_value( $default, 'theme_progress_bar_border_color', $settings, '#CECECE' ),
                        ),
                    ),
                ),
                'theme_progress_step_colors' => array(
                    'name' => __('Progress Step Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_progress_step_primary_color'=>array(
                            'label'=>'Primary color',
                            'default' => self::get_value( $default, 'theme_progress_step_primary_color', $settings, '#CECECE' ),
                        ),
                        'theme_progress_step_secondary_color'=>array(
                            'label'=>'Secondary color',
                            'default' => self::get_value( $default, 'theme_progress_step_secondary_color', $settings, '#E2E2E2' ),
                        ),
                        'theme_progress_step_border_color'=>array(
                            'label'=>'Border color',
                            'default' => self::get_value( $default, 'theme_progress_step_border_color', $settings, '#CECECE' ),
                        ),
                        'theme_progress_step_font_color'=>array(
                            'label'=>'Font color',
                            'default' => self::get_value( $default, 'theme_progress_step_font_color', $settings, '#FFFFFF' ),
                        ),                                
                    ),
                ),
                'theme_progress_step_colors_active' => array(
                    'name' => __('Progress Step Colors Active', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_progress_step_primary_color_active'=>array(
                            'label'=>'Primary color',
                            'default' => self::get_value( $default, 'theme_progress_step_primary_color_active', $settings, '#87CC83' ),
                        ),
                        'theme_progress_step_secondary_color_active'=>array(
                            'label'=>'Secondary color',
                            'default' => self::get_value( $default, 'theme_progress_step_secondary_color_active', $settings, '#E2E2E2' ),
                        ),
                        'theme_progress_step_border_color_active'=>array(
                            'label'=>'Border color',
                            'default' => self::get_value( $default, 'theme_progress_step_border_color_active', $settings, '#CECECE' ),
                        ),
                        'theme_progress_step_font_color_active'=>array(
                            'label'=>'Font color',
                            'default' => self::get_value( $default, 'theme_progress_step_font_color_active', $settings, '#FFFFFF' ),
                        ),                                
                    ),
                ),
                'theme_error' => array(
                    'name' => __('Error Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_error_font'=>array(
                            'label'=>'Font Color',
                            'default' => self::get_value( $default, 'theme_error_font', $settings, '#f2322b' ),
                        ),                     
                    ),
                ),              


                /** 
                 *  Error & Success message colors
                 *
                 *  @since      1.0.6
                */
                'theme_error_msg' => array(
                    'name' => __('Error Message Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_error_msg_font_color'=>array(
                            'label'=>'Font color',
                            'default' => self::get_value( $default, 'theme_error_msg_font_color', $settings, '#D08080' ),
                        ),
                        'theme_error_msg_border_color'=>array(
                            'label'=>'Border color',
                            'default' => self::get_value( $default, 'theme_error_msg_border_color', $settings, '#FFCBCB' ),
                        ),
                        'theme_error_msg_bg_color'=>array(
                            'label'=>'Background color',
                            'default' => self::get_value( $default, 'theme_error_msg_bg_color', $settings, '#FFEBEB' ),
                        ),
                        'theme_error_msg_icon_color'=>array(
                            'label'=>'Icon color',
                            'default' => self::get_value( $default, 'theme_error_msg_icon_color', $settings, '#FF9A9A' ),
                        ),
                    ),
                ),
                'theme_success_msg' => array(
                    'name' => __('Success Message Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_success_msg_font_color'=>array(
                            'label'=>'Font color',
                            'default' => self::get_value( $default, 'theme_success_msg_font_color', $settings, '#5E7F62' ),
                        ),
                        'theme_success_msg_border_color'=>array(
                            'label'=>'Border color',
                            'default' => self::get_value( $default, 'theme_success_msg_border_color', $settings, '#90C397' ),
                        ),
                        'theme_success_msg_bg_color'=>array(
                            'label'=>'Background color',
                            'default' => self::get_value( $default, 'theme_success_msg_bg_color', $settings, '#C5FFCD' ),
                        ),
                        'theme_success_msg_icon_color'=>array(
                            'label'=>'Icon color',
                            'default' => self::get_value( $default, 'theme_success_msg_icon_color', $settings, '#90C397' ),
                        ),
                    ),
                ),

                // @since 2.0.0
                'theme_success_msg_margin' => array(
                    'name' => __( 'Thanks margins in px (top right bottom left)', 'super-forms' ),
                    'desc' => __( 'A custom thank you description shown after a user completed the form.', 'super-forms' ),
                    'default' => self::get_value( $default, 'theme_success_msg_margin', $settings, '0px 0px 30px 0px'),
                ),
                
                'theme_max_width' => array(
                    'name' => __( 'Form Maximum Width', 'super-forms' ),
                    'label' => __( '(0 = disabled)', 'super-forms' ),
                    'default' => self::get_value( $default, 'theme_max_width', $settings, 0 ),
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>1000,
                    'steps'=>10,
                ),

                // @since 1.3
                'theme_form_margin' => array(
                    'name' => __( 'Form Margins example: 0px 0px 0px 0px', 'super-forms' ),
                    'label' => __( '(top right bottom left)', 'super-forms' ),
                    'default' => self::get_value( $default, 'theme_form_margin', $settings, '0px 0px 0px 0px' ),
                    'type'=>'text',
                ),

            )
        );
        $array = apply_filters( 'super_settings_after_theme_colors_filter', $array, array( 'settings'=>$settings ) );

        
        /** 
         *  Custom CSS
         *
         *  @since      1.2.8
        */
        $array['form_custom_css'] = array(        
            'hidden' => 'settings',
            'name' => __( 'Custom CSS', 'super-forms' ),
            'label' => __( 'Custom CSS', 'super-forms' ),
            'fields' => array(        
                'form_custom_css' => array(
                    'name' => __( 'Custom CSS', 'super-forms' ),
                    'type'=>'textarea',
                    'default' => self::get_value( $default, 'form_custom_css', $settings, '' ),
                ),

            )
        );
        $array = apply_filters( 'super_settings_after_form_custom_css_filter', $array, array( 'settings'=>$settings ) );

        
        /** 
         *	Submit Button Settings
         *
         *	@since		1.0.0
        */
        $array['submit_button'] = array(        
            'name' => __( 'Submit Button', 'super-forms' ),
            'label' => __( 'Submit Button', 'super-forms' ),
            'fields' => array(        
                'form_button' => array(
                    'name' => __('Button name', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button', $settings, __( 'Submit', 'super-forms' ) ),
                ),
                // @since 2.0.0
                'form_button_loading' => array(
                    'name' => __('Button loading name', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_loading', $settings, __( 'Loading...', 'super-forms' ) ),
                ),

                'theme_button_colors' => array(
                    'name' => __('Button Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_button_color'=>array(
                            'label'=>'Button background color',
                            'default' => self::get_value( $default, 'theme_button_color', $settings, '#f26c68' ),
                        ),
                        'theme_button_color_hover'=>array(
                            'label'=>'Button background color hover',
                            'default' => self::get_value( $default, 'theme_button_color_hover', $settings, '#444444' ),
                        ),
                        'theme_button_font'=>array(
                            'label'=>'Button font color',
                            'default' => self::get_value( $default, 'theme_button_font', $settings, '#ffffff' ),
                        ),
                        'theme_button_font_hover'=>array(
                            'label'=>'Button font color hover',
                            'default' => self::get_value( $default, 'theme_button_font_hover', $settings, '#ffffff' ),
                        ),                            
                    ),
                ),
                'form_button_radius' => array(
                    'name'=> __('Button radius', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_radius', $settings, 'square' ),
                    'type'=>'select',
                    'values'=>array(
                        'rounded'=>'Rounded',
                        'square'=>'Square',
                        'full-rounded'=>'Full Rounded',
                    )
                ),
                'form_button_type' => array(
                    'name'=> __('Button type', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_type', $settings, 'flat' ),
                    'type'=>'select',
                    'values'=>array(
                        '3d'=>'3D Button',
                        '2d'=>'2D Button',
                        'flat'=>'Flat Button',
                        'outline'=>'Outline Button',
                        'diagonal'=>'Diagonal Button',
                    )
                ),
                'form_button_size' => array(
                    'name'=> __('Button size', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_size', $settings, 'medium' ),
                    'type'=>'select', 
                    'values'=>array(
                        'mini' => 'Mini', 
                        'tiny' => 'Tiny', 
                        'small' => 'Small', 
                        'medium' => 'Medium', 
                        'large' => 'Large', 
                        'big' => 'Big', 
                        'huge' => 'Huge', 
                        'massive' => 'Massive', 
                    ),
                ),
                'form_button_align' => array(
                    'name'=> __('Button position', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_align', $settings, 'left' ),
                    'type'=>'select', 
                    'values'=>array(
                        'left' => 'Align Left', 
                        'center' => 'Align Center', 
                        'right' => 'Align Right', 
                    ),
                ), 
                'form_button_width' => array(
                    'name'=> __('Button width', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_width', $settings, 'auto' ),
                    'type'=>'select', 
                    'values'=>array(
                        'auto' => 'Auto', 
                        'fullwidth' => 'Fullwidth', 
                    ),
                ),         
                'form_button_icon_option' => array(
                    'name'=> __('Button icon position', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_icon_option', $settings, 'none' ),
                    'filter'=>true,
                    'type'=>'select', 
                    'values'=>array(
                        'none' => 'No icon', 
                        'left' => 'Left icon', 
                        'right' => 'Right icon', 
                    ),
                ),
                'form_button_icon_visibility' => array(
                    'name'=> __('Button icon visibility', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_icon_visibility', $settings, 'visible' ),
                    'filter'=>true,
                    'parent'=>'form_button_icon_option',
                    'filter_value'=>'left,right',
                    'type'=>'select', 
                    'values'=>array(
                        'visible' => 'Always Visible', 
                        'hidden' => 'Visible on hover (mouseover)', 
                    ),
                ),
                'form_button_icon_animation' => array(
                    'name'=> __('Button icon animation', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_icon_animation', $settings, 'horizontal' ),
                    'filter'=>true,
                    'parent'=>'form_button_icon_visibility',
                    'filter_value'=>'hidden',
                    'type'=>'select', 
                    'values'=>array(
                        'horizontal' => 'Horizontal animation', 
                        'vertical' => 'Vertical animation', 
                    ),
                ),                                
                'form_button_icon' => array(
                    'name'=> __('Button icon', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_button_icon', $settings, '' ),
                    'type'=>'icon',
                    'filter'=>true,
                    'parent'=>'form_button_icon_option',
                    'filter_value'=>'left,right',
                ),
            )
        );
        
        
        /** 
         *	Backend Settings
         *
         *	@since		1.0.0
        */
        $array['backend_settings'] = array(        
            'hidden' => true,
            'name' => __( 'Backend Settings', 'super-forms' ),
            'label' => __('Here you can change serveral settings that apply to your backend', 'super-forms' ),
            'fields' => array(
                'backend_contact_entry_list_fields' => array(
                    'name' => __('Columns for contact entries', 'super-forms' ),
                    'desc' => __('Put each on a new line.<br />Example:<br />fieldname|Field label<br />email|Email<br />phonenumber|Phonenumber', 'super-forms' ),
                    'default' => self::get_value( $default, 'backend_contact_entry_list_fields', $settings, "email|Email\nphonenumber|Phonenumber\nmessage|Message" ),
                    'type' => 'textarea', 
                ),
                
                // @since 1.2.9
                'backend_contact_entry_list_form' => array(
                    'name' => '&nbsp;',
                    'default' => self::get_value( $default, 'backend_contact_entry_list_form', $settings, 'true' ),
                    'values' => array(
                        'true' => __('Add the form name to the contact entry list', 'super-forms' ),
                    ),
                    'type' => 'checkbox'
                )
            ),
        );
        $array = apply_filters( 'super_settings_after_backend_settings_filter', $array, array( 'settings'=>$settings ) );
        

        /** 
         *	Custom CSS
         *
         *	@since		1.0.0
        */
        $array['custom_css'] = array(        
            'hidden' => true,
            'name' => __( 'Custom CSS', 'super-forms' ),
            'label' => __('Below you can override the default CSS styles', 'super-forms' ),
            'fields' => array(
                'theme_custom_css' => array(
                    'name' => __('Custom CSS', 'super-forms' ),
                    'default' => self::get_value( $default, 'theme_custom_css', $settings, '' ),
                    'type' => 'textarea', 
                ),
            ),
        );
        $array = apply_filters( 'super_settings_after_custom_css_filter', $array, array( 'settings'=>$settings ) );

        
        /** 
         *	SMTP Server
         *
         *	@since		1.0.0
        */
        $array['smtp_server'] = array(        
            'hidden' => true,
            'name' => __( 'SMTP Server', 'super-forms' ),
            'label' => __( 'SMTP Configuration', 'super-forms' ),
            'fields' => array(        
                'smtp_enabled' => array(
                    'name' => __( 'Set mailer to use SMTP', 'super-forms' ),
                    'desc' => __( 'Use the default wp_mail() or use SMTP to send emails', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_enabled', $settings, 'disabled' ),
                    'filter' => true,
                    'type' => 'select',
                    'values' => array(
                        'disabled' => __( 'Disabled', 'super-forms' ),
                        'enabled' => __( 'Enabled', 'super-forms' )
                    )
                ),
                'smtp_host' => array(
                    'name' => __( 'Specify main and backup SMTP servers', 'super-forms' ),
                    'desc' => __( 'Example: smtp1.example.com;smtp2.example.com', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_host', $settings, 'smtp1.example.com;smtp2.example.com' ),
                    'placeholder' => __( 'Your SMTP server', 'super-forms' ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',  
                ),
                'smtp_auth' => array(
                    'name' => __( 'Enable SMTP authentication', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_auth', $settings, 'disabled' ),
                    'type' => 'select',
                    'values' => array(
                        'disabled' => __( 'Disabled', 'super-forms' ),
                        'enabled' => __( 'Enabled', 'super-forms' )
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_username' => array(
                    'name' => __( 'SMTP username', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_username', $settings, '' ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_password' => array(
                    'name' => __( 'SMTP password', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_password', $settings, '' ),
                    'type' => 'password',
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),                                
                'smtp_secure' => array(
                    'name' => __( 'Enable TLS or SSL encryption', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_secure', $settings, '' ),
                    'type' => 'select',
                    'values' => array(
                        '' => __( 'Disabled', 'super-forms' ),
                        'ssl' => __( 'SSL', 'super-forms' ),
                        'tls' => __( 'TLS', 'super-forms' )
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_port' => array(
                    'name' => __( 'TCP port to connect to', 'super-forms' ),
                    'desc' => __( 'SMTP – port 25 or 2525 or 587<br />Secure SMTP (SSL / TLS) – port 465 or 25 or 587, 2526', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_port', $settings, '465' ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                    'width' => 100, 
                ),
                'smtp_timeout' => array(
                    'name' => __( 'Timeout (seconds)', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_timeout', $settings, 30 ),
                    'width' => 100, 
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_keep_alive' => array(
                    'name' => __( 'Keep connection open after each message', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_keep_alive', $settings, 'disabled' ),
                    'type' => 'select',
                    'values' => array(
                        'disabled' => __( 'Disabled', 'super-forms' ),
                        'enabled' => __( 'Enabled', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_debug' => array(
                    'name' => __( 'SMTP debug output mode', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_debug', $settings, 0 ),
                    'type' => 'select',
                    'values' => array(
                        0 => __( 'No output', 'super-forms' ),
                        1 => __( 'Commands', 'super-forms' ),
                        2 => __( 'Data and commands', 'super-forms' ),
                        3 => __( 'As 2 plus connection status', 'super-forms' ),
                        4 => __( 'Low-level data output', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_debug_output_mode' => array(
                    'name' => __( 'How to handle debug output', 'super-forms' ),
                    'default' => self::get_value( $default, 'smtp_debug_output_mode', $settings, 'echo' ),
                    'type' => 'select',
                    'values' => array(
                        'echo' => __( 'ECHO - Output plain-text as-is, appropriate for CLI', 'super-forms' ),
                        'html' => __( 'HTML - Output escaped, line breaks converted to `<br>`, appropriate for browser output', 'super-forms' ),
                        'error_log' => __( 'ERROR_LOG - Output to error log as configured in php.ini', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'smtp_debug',
                    'filter_value' => '1,2,3,4',
                ),

            )
        );
        $array = apply_filters( 'super_settings_after_smtp_server_filter', $array, array( 'settings'=>$settings ) );
                
        
        /** 
         *	Usefull Tags
         *
         *	@since		1.0.0
        */
        $array['usefull_tags'] = array(        
            'hidden' => true,
            'name' => __( 'Usefull Tags', 'super-forms' ),
            'label' => __( 'Usefull Tags', 'super-forms' ),
            'html' => array(
                '<ul>',
                '<li>',
                '<strong>1. You have the ability to retrieve your field values by applying the following tag:</strong><br />',
                '<small style="color:red;"><strong style="color:black;">{field_*****}</strong> (where ***** is your field name):<br />',
                'When you have set a field "First Name" named "firstname" use: <strong style="color:black;">{field_firstname}</strong></small><br /><br />',
                '</li>',
                '<li><strong>2. You have the ability to retrieve important options that WordPress uses by default by applying one of the following tags:</strong><br />',
                '<small style="color:red;"><strong style="color:black;">{option_admin_email}</strong> - E-mail address of blog administrator.<br />',
                '<strong style="color:black;">{option_blogname}</strong> - Weblog title; set in General Options..<br />',
                '<strong style="color:black;">{option_blogdescription}</strong> - Tagline for your blog; set in General Options.<br />',
                '<strong style="color:black;">{option_default_category}</strong> - Default post category; set in Writing Options.<br />',
                '<strong style="color:black;">{option_home}</strong> - The blog\'s home web address; set in General Options.<br><strong style="color:black;">{option_siteurl}</strong> - WordPress web address; set in General Options.<br><strong style="color:black;">{option_template}</strong> - The current theme\'s name; set in Presentation.<br />',
                '<strong style="color:black;">{option_upload_path}</strong> - Default upload location; set in Miscellaneous Options.<br />',
                '<strong style="color:black;">{real_ip}</strong> - Retrieves the submitter\'s IP address.</small><br /><br />',
                '</li>',
                '<li>',
                '<strong>3. You have the ability to change the way your field data is being wrapped inside your email see "Loop Fields" option:</strong><br />',
                '<small style="color:red;">Use <strong style="color:black;">{loop_label}</strong> to retrieve the field label.<br />',
                'Use <strong style="color:black;">{loop_value}</strong> to retrieve the field value.<br />',
                'Use <strong style="color:black;">{loop_fields}</strong> to retrieve the loop anywhere in your email.</small><br /><br />',
                '</li>',
                '</ul>',
            ),
        );
        $array = apply_filters( 'super_settings_after_usefull_tags_filter', $array, array( 'settings'=>$settings ) );
        
        
        /** 
         *	Restore Default Settings
         *
         *	@since		1.0.0
        */
        $array['restore_default'] = array(        
            'hidden' => true,
            'name' => __( 'Restore Default Settings', 'super-forms' ),
            'label' => __( 'Restore Default Settings', 'super-forms' ),
            'html' => array(
                '<span class="super-button restore-default delete">' . __( 'Restore Default Settings', 'super-forms' ) . '</span>',
            ),
        );
        $array = apply_filters( 'super_settings_after_restore_default_filter', $array, array( 'settings'=>$settings ) );
        
        
        /** 
         *	System Status
         *
         *	@since		1.0.0
        */
        $array['system_status'] = array(        
            'hidden' => true,
            'name' => __( 'System Status', 'super-forms' ),
            'label' => __( 'System Status', 'super-forms' ),
            'html' => array(
                '<p><b>PHP ' . __('version', 'super-forms' ) . ':</b> ' . phpversion() . '</p>',
                '<p><b>MySQL ' . __('version', 'super-forms' ) . ':</b> ' . $mysql_version . '</p>',                
                '<p><b>WordPress ' . __(' version', 'super-forms' ) . ':</b> ' . get_bloginfo( 'version' ) . '</p>',
                '<p><b>Super Forms ' . __('version', 'super-forms' ) . ':</b> ' . SUPER_VERSION . '</p>',
            ),
        );
        $array = apply_filters( 'super_settings_after_system_status_filter', $array, array( 'settings'=>$settings ) );
        
         
        /** 
         *  Export & Import
         *
         *  @since      1.0.6
        */
        $array['export_import'] = array(        
            'name' => __( 'Export & Import', 'super-forms' ),
            'label' => __( 'Export & Import', 'super-forms' ),
            'html' => array(
                '<div class="super-export-import">',
                '<strong>' . __( 'Export Settings', 'super-forms' ) . ':</strong>',
                '<textarea name="export-json">' . json_encode( $settings ) . '</textarea>',
                '<hr />',
                '<strong>' . __( 'Import Settings', 'super-forms' ) . ':</strong>',
                '<textarea name="import-json"></textarea>',
                '<span class="super-button import-settings delete">' . __( 'Import Settings', 'super-forms' ) . '</span>',
                '<span class="super-button load-default-settings clear">' . __( 'Load default Settings', 'super-forms' ) . '</span>',
                '</div>',

                // @since 1.9 - export forms
                '<div class="super-export-import-forms">',
                    '<strong>' . __( 'Export Forms', 'super-forms' ) . ':</strong>',
                    '<span class="super-button export-forms delete" data-type="csv">' . __( 'Export Forms', 'super-forms' ) . '</span>',
                '</div>',

                // @since 1.9 - import forms
                '<div class="super-export-import-entries">',
                    '<strong>' . __( 'Import Forms', 'super-forms' ) . ':</strong>',
                    '<div class="browse-forms-import-file">',
                        '<span class="button super-button super-import-forms"><i class="fa fa-download"></i> Select Import file (.txt)</span>',
                    '</div>',
                '</div>',

                '<div class="super-export-import-entries">',
                    '<strong>' . __( 'Export Contact Entries', 'super-forms' ) . ':</strong>',
                    '<p>' . __( 'Below you can enter a date range (or leave empty to export all contact entries)', 'super-forms' ) . '</p>',
                    '<span>' . __( 'From', 'super-forms' ) . ':</span> <input type="text" value="" name="from" />',
                    '<span>' . __( 'Till', 'super-forms' ) . ':</span> <input type="text" value="" name="till" />',
                    '<p>' . __( 'Below you can change the default delimiter and enclosure characters', 'super-forms' ) . ':</p>',
                    '<span>' . __( 'Delimiter', 'super-forms' ) . ':</span> <input type="text" value="," name="delimiter" />',
                    '<span>' . __( 'Enclosure', 'super-forms' ) . ':</span> <input type="text" value="' . htmlentities('"') . '" name="enclosure" />',
                    '<span class="super-button export-entries delete" data-type="csv">' . __( 'Export Contact Entries to CSV', 'super-forms' ) . '</span>',
                '</div>',

                '<div class="super-export-import-entries">',
                    '<strong>' . __( 'Import Contact Entries', 'super-forms' ) . ':</strong>',
                    '<div class="browse-csv-import-file">',
                        '<span class="button super-button super-insert-files"><i class="fa fa-download"></i> Select CSV file</span>',
                        '<div class="file-preview"></div>',
                        '<input type="hidden" name="csv_import_file" value="">',
                    '</div>',
                '</div>'


            ),
        );
        $array = apply_filters( 'super_settings_after_export_import_filter', $array, array( 'settings'=>$settings ) );


        /** 
         *  Activation
         *
         *  @since      1.0.9
        */
        $sac = get_option( 'image_default_positioning', 0 );
        $sact = '';
        $dact = '';
        if($sac==1){
            $sact = '<strong style="color:green;">Plugin is activated!</strong>';
            $dact = '<br /><br />---';
            $dact .= '<br /><br /><strong style="color:green;">If you want to transfer this plugin to another domain,<br />';
            $dact .= 'you can deactivate it on this domain by clicking the following button:</strong>';
            $dact .= '<br /><br /><span class="button super-button deactivate">Deactivate on current domain</span>';
        }else{
            $sact = '<strong style="color:red;">Plugin is not yet activated!</strong>';
            $sact .= '<br /><br />---';
            $sact .= '<br /><br /><span class="button super-button save-settings">Activate</span>';
            $sact .= '';
        }
        $array['activation'] = array(
            'hidden' => true,
            'name' => __( 'Activation', 'super-forms' ),
            'label' => __( 'Product Activation', 'super-forms' ),
            'html' => array(
                '<p>',
                'Before you can start using the plugin, you need to enter your Item Purchase Code below.<br />',
                'You can find your Purchase code in your Envato account under your <a target="_blank" href="http://themeforest.net/downloads">Downloads</a> section.',
                '</p>',
                '<div class="super-field">',
                '<div class="super-field-info"></div>',
                '<div class="input"><strong>Super Forms - Drag & Drop Form Builder</strong><br /><input type="text" id="field-license" name="license" class="element-field" value="' . self::get_value( $default, 'license', $settings, '' ) . '" /></div>',
                '<div class="input activation-msg">' . $sact . $dact . '</div>',
                '</div>'
            ),
        );
        $array = apply_filters( 'super_settings_after_activation_filter', $array, array( 'settings'=>$settings ) );


        /** 
         *	Support
         *
         *	@since		1.0.0
        */
        $array['support'] = array(
            'hidden' => true,
            'name' => __( 'Support', 'super-forms' ),
            'label' => __( 'Support', 'super-forms' ),
            'html' => array(
                '<p>For support please contact us through Envato: <a href="http://codecanyon.net/user/feeling4design">feeling4design</a></p>',
            ),
        );
        $array = apply_filters( 'super_settings_after_support_filter', $array, array( 'settings'=>$settings ) );

        
        $array = apply_filters( 'super_settings_end_filter', $array, array( 'settings'=>$settings ) );
        
        return $array;
        
    }

    /**
     * Retrieve the default value of the field
     * @param  string $name
     * @param  array $settings
     * @param  string $default
     *
     *	@since		1.0.0
    */
    public static function get_value( $strict_default, $name, $settings, $default ) {
        if( $strict_default==1 ) {
            return $default;
        }else{
            return ( !isset( $settings[$name] ) ? $default : $settings[$name] );
        }
    }
    
    /**
     * Reformat the settings
     * @param  array $settings
     *
     *	@since		1.0.0
    */
    public static function format_settings( $settings=null ) {
        if($settings!=false){
            foreach($settings as $k=>$v) {
                $settings[$k] = stripslashes($v);
            }
        }
        return $settings;
    }
    
}
endif;