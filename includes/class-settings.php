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
            'name' => __( 'Email Headers', 'super' ),
            'label' => __( 'Email Headers', 'super' ),
            'fields' => array(
                'header_to' => array(
                    'name' => __( 'Send mail to', 'super' ),
                    'desc' => __( 'Recipient(s) seperated with commas (email address)', 'super' ),
                    'placeholder' => __( 'your@email.com, your@email.com', 'super'),
                    'default' => self::get_value( $default, 'header_to', $settings, get_option( 'admin_email' ) ),
                ),
                'header_from_type' => array(
                    'name'=>'From',
                    'default' => self::get_value( $default, 'header_from_type', $settings, 'default' ),
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'default' => __(  'Default blog name', 'super' ),
                        'custom' => __(  'Custom from', 'super' ),
                    )
                ),
                'header_from' => array(
                    'name' => '',
                    'default' => self::get_value( $default, 'header_from', $settings, 'Your Name or Company <info@company.com>' ),
                    'placeholder' => __( 'Your Name or Company', 'super'),
                    'filter'=>true,
                    'parent'=>'header_from_type',
                    'filter_value'=>'custom',  
                ),
                'header_subject' => array(
                    'name' => __( 'Email subject', 'super' ),
                    'desc' => __( 'The subject for this email', 'super' ),
                    'default' => self::get_value( $default, 'header_subject', $settings, 'This mail was send from yourdomain.com' ),
                    'placeholder' => __( 'This mail was send from yourdomain.com', 'super'),
                ),
                'header_content_type' => array(
                    'name' => __( 'Email content type', 'super' ),
                    'desc' => __( 'The content type to use for this email', 'super' ),
                    'default' => self::get_value( $default, 'header_content_type', $settings, 'html' ),
                    'type'=>'select',
                    'values'=>array(
                        'html'=>'HTML',
                        'plain'=>'Plain text',
                    )
                ),
                'header_cc' => array(
                    'name' => __( 'CC email to', 'super' ),
                    'desc' => __( 'Send copy to following address(es)', 'super' ),
                    'default' => self::get_value( $default, 'header_cc', $settings, '' ),
                    'placeholder' => __( 'someones@email.com, someones@emal.com', 'super'),
                ),
                'header_bcc' => array(
                    'name' => __( 'BCC email to', 'super' ),
                    'desc' => __( 'Send copy to following address(es), without able to see the address', 'super' ),
                    'default' => self::get_value( $default, 'header_bcc', $settings, '' ),
                    'placeholder' => __( 'someones@email.com, someones@emal.com', 'super'),
                ),
                'header_additional' => array(
                    'name' => __('Additional Headers', 'super' ),
                    'desc' => __('Add any extra email headers here (put each header on a new line)', 'super' ),
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
            'name' => __( 'Email Settings', 'super' ),
            'label' => __( 'Email Settings', 'super' ),
            'fields' => array(        
                'send' => array(
                    'name' => __( 'Send Admin Email', 'super' ),
                    'desc' => __( 'Send or do not send the admin emails', 'super' ),
                    'default' => self::get_value( $default, 'send', $settings, 'yes' ),
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'yes' => __( 'Send an admin email', 'super' ),
                        'no' => __( 'Do not send an admin email', 'super' ),
                    )
                ),
                'email_body_open' => array(
                    'name' => __( 'Email Body Open', 'super' ),
                    'desc' => __( 'This content will be placed before the body content of the email.', 'super' ),
                    'default' => self::get_value( $default, 'email_body_open', $settings, __( 'The following information has been send by the submitter:', 'super' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'email_body' => array(
                    'name' => __( 'Email Body', 'super' ),
                    'desc' => __( 'Use a custom email body. Use {loop_fields} to retrieve the loop.', 'super' ),
                    'default' => self::get_value( $default, 'email_body', $settings, __( '<table cellpadding="5">{loop_fields}</table>', 'super' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'email_body_close' => array(
                    'name' => __( 'Email Body Close', 'super' ),
                    'desc' => __( 'This content will be placed after the body content of the email.', 'super' ),
                    'default' => self::get_value( $default, 'email_body_close', $settings, __( "Best regards, {option_blogname}", "super" ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'email_loop' => array(
                    'name' => __( 'Email Loop', 'super' ),
                    'desc' => __( 'Use a custom loop. Use {loop_label and {loop_value} to retrieve values.', 'super' ),
                    'default' => self::get_value( $default, 'email_loop', $settings, __( '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>', 'super' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',   
                ),
                'confirm' => array(
                    'name' => __( 'Confirmation Email', 'super' ),
                    'desc' => __( 'Send or do not send confirmation emails', 'super' ),
                    'default' => self::get_value( $default, 'confirm', $settings, 'yes' ),
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'yes' => __( 'Send a confirmation email', 'super' ),
                        'no' => __( 'Do not send a confirmation email', 'super' ),
                    )
                ),
                'confirm_to' => array(
                    'name' => __( 'Confirmation To', 'super' ),
                    'desc' => __( 'Recipient(s) seperated by commas', 'super' ),
                    'default' => self::get_value( $default, 'confirm_to', $settings, '{field_name} <{field_email}>' ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',   
                ),
                'confirm_from' => array(
                    'name' => __( 'Confirmation From', 'super' ),
                    'desc' => __( 'Sender (email address)', 'super' ),
                    'default' => self::get_value( $default, 'confirm_from', $settings, '{option_blogname} <{option_admin_email}>' ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                ),
                'confirm_subject' => array(
                    'name' => __( 'Confirmation Subject', 'super' ),
                    'desc' => __( 'The confirmation subject for this email', 'super' ),
                    'default' => self::get_value( $default, 'confirm_subject', $settings, __( 'Thank you!', 'super' ) ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                ),
                'confirm_body_open' => array(
                    'name' => __( 'Confirm Body Open', 'super' ),
                    'desc' => __( 'This content will be placed before the confirmation email body.', 'super' ),
                    'default' => self::get_value( $default, 'confirm_body_open', $settings, __( "Dear user,\n\nThank you for contacting us!", "super" ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                ),
                'confirm_body' => array(
                    'name' => __( 'Confirm Body', 'super' ),
                    'desc' => __( 'Use a custom email body. Use {loop_fields} to retrieve the loop.', 'super' ),
                    'default' => self::get_value( $default, 'confirm_body', $settings, __( '<table cellpadding="5">{loop_fields}</table>', 'super' ) ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',  
                ),
                'confirm_body_close' => array(
                    'name' => __( 'Confirm Body Close', 'super' ),
                    'desc' => __( 'This content will be placed after the confirmation email body.', 'super' ),
                    'default' => self::get_value( $default, 'confirm_body_close', $settings, __( "We will reply within 48 hours.\n\nBest Regards, {option_blogname}", "super" ) ),
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
            'name' => __( 'Email Template', 'super' ),
            'label' => __( 'Email Template', 'super' ),
            'fields' => array(        
                'email_template' => array(
                    'name' => __( 'Select email template', 'super' ),
                    'desc' => __( 'Choose which email template you would like to use', 'super' ),
                    'info'=>'<a target="_blank" href="http://codecanyon.net/user/feeling4design/portfolio">'.__('Click here to check out all available email templates!', 'super' ).'</a>',
                    'type'=>'select',
                    'default' => self::get_value( $default, 'email_template', $settings, 'default_email_template' ),
                    'filter'=>true,
                    'values'=>array(
                        'default_email_template' => __('Default email template', 'super' ),
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
            'name' => __( 'Form Settings', 'super' ),
            'label' => __( 'Form Settings', 'super' ),
            'fields' => array(        
                'save_contact_entry' => array(
                    'name' => __( 'Save data', 'super' ),
                    'desc' => __( 'Choose if you want to save the user data as a Contact Entry', 'super' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'save_contact_entry', $settings, 'yes' ),
                    'filter'=>true,
                    'values'=>array(
                        'yes' => __('Save as Contact Entry', 'super' ),
                        'no' => __('Do not save data', 'super' ),
                    )
                ),
                'form_actions' => array(
                    'name' => __( 'Form action', 'super' ),
                    'desc' => __( 'What should happen after a user submitted the form?', 'super' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'form_actions', $settings, 0 ),
                    'filter'=>true,
                    'values'=>array(
                        0 => __('Send an email', 'super' ),
                    ),
                ),
                'form_duration' => array(
                    'name' => __( 'Error FadeIn Duration', 'super' ),
                    'desc' => __( 'The duration for error messages to popup in milliseconds.', 'super' ),
                    'default' => self::get_value( $default, 'form_duration', $settings, 500 ),
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>1000,
                    'steps'=>100,
                ),
                'form_thanks_title' => array(
                    'name' => __( 'Thanks Title', 'super' ),
                    'desc' => __( 'A custom thank you title shown after a user completed the form.', 'super' ),
                    'default' => self::get_value( $default, 'form_thanks_title', $settings, __( 'Thank you!', 'super' ) ),
                ),
                'form_thanks_description' => array(
                    'name' => __( 'Thanks Description', 'super' ),
                    'desc' => __( 'A custom thank you description shown after a user completed the form.', 'super' ),
                    'default' => self::get_value( $default, 'form_thanks_description', $settings, __( 'We will reply within 24 hours.', 'super' ) ),
                    'type'=>'textarea',
                ),
                'form_preload' => array(
                    'name' => __( 'Preloader', 'super' ),
                    'desc' => __( 'Custom use of preloader for the form.', 'super' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'form_preload', $settings, '1' ),
                    'values'=>array(
                        '1' => __( 'Enabled', 'super' ),
                        '0' => __( 'Disabled', 'super' ),
                    ),
                ),
                'form_recaptcha' => array(
                    'name' => '<a href="https://www.google.com/recaptcha" target="_blank">'.__( 'reCAPTCHA key', 'super' ).'</a>',
                    'default' => self::get_value( $default, 'form_recaptcha', $settings, '' ),
                ),
                'form_recaptcha_secret' => array(
                    'name' => '<a href="https://www.google.com/recaptcha" target="_blank">'.__( 'reCAPTCHA secret', 'super' ).'</a>',
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
                    'name' => __('Enter a custom URL to redirect to', 'super' ),
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
            'name' => __( 'Theme & Colors', 'super' ),
            'label' => __( 'Theme & Colors', 'super' ),
            'fields' => array(        
                'theme_style' => array(
                    'name' => __( 'Theme style', 'super' ),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'theme_style', $settings, '' ),
                    'values'=>array(
                        '' => __( 'Default Squared', 'super' ),
                        'super-default-rounded' => __( 'Default Rounded', 'super' ),
                        'super-full-rounded' => __( 'Full Rounded', 'super' ),
                        'super-style-one' => __( 'Minimal', 'super' ),
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
                'theme_label_colors' => array(
                    'name' => __('Label & Description colors', 'super' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_label'=>array(
                            'label'=>'Field label',
                            'default' => self::get_value( $default, 'theme_field_label', $settings, '#444444' ),
                        ),
                        'theme_field_description'=>array(
                            'label'=>'Field description',
                            'default' => self::get_value( $default, 'theme_field_description', $settings, '#8e8e8e' ),
                        ),
                    ),
                ),
                'theme_field_colors' => array(
                    'name' => __('Field Colors', 'super' ),
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
                    'name' => __('Field Colors Focus', 'super' ),
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
                'theme_icon_colors' => array(
                    'name' => __('Icon Colors', 'super' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_icon_color'=>array(
                            'label'=>'Icon color',
                            'default' => self::get_value( $default, 'theme_icon_color', $settings, '#cdcdcd' ),
                        ),
                        'theme_icon_color_focus'=>array(
                            'label'=>'Icon color focus',
                            'default' => self::get_value( $default, 'theme_icon_color_focus', $settings, '#444444' ),
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
                ),
                'theme_rating_colors' => array(
                    'name' => __('Rating Colors', 'super' ),
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
                    'name' => __('Rating Colors Hover', 'super' ),
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
                    'name' => __('Rating Colors Active', 'super' ),
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
                    'name' => __('Product, Discount & Total Colors', 'super' ),
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
                    'name' => __('Progress Bar Colors', 'super' ),
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
                    'name' => __('Progress Step Colors', 'super' ),
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
                    'name' => __('Progress Step Colors Active', 'super' ),
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
                    'name' => __('Error Colors', 'super' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_error_font'=>array(
                            'label'=>'Font Color',
                            'default' => self::get_value( $default, 'theme_error_font', $settings, '#f2322b' ),
                        ),                     
                    ),
                ),
            )
        );
        $array = apply_filters( 'super_settings_after_theme_colors_filter', $array, array( 'settings'=>$settings ) );

        
        /** 
         *	Submit Button Settings
         *
         *	@since		1.0.0
        */
        $array['submit_button'] = array(        
            'name' => __( 'Submit Button', 'super' ),
            'label' => __( 'Submit Button', 'super' ),
            'fields' => array(        
                'form_button' => array(
                    'name' => __('Button name', 'super' ),
                    'default' => self::get_value( $default, 'form_button', $settings, __( 'Submit', 'super' ) ),
                ),
                'theme_button_colors' => array(
                    'name' => __('Button Colors', 'super' ),
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
                    'name'=> __('Button radius', 'super' ),
                    'default' => self::get_value( $default, 'form_button_radius', $settings, 'square' ),
                    'type'=>'select',
                    'values'=>array(
                        'rounded'=>'Rounded',
                        'square'=>'Square',
                        'full-rounded'=>'Full Rounded',
                    )
                ),
                'form_button_type' => array(
                    'name'=> __('Button type', 'super' ),
                    'default' => self::get_value( $default, 'form_button_type', $settings, '2d' ),
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
                    'name'=> __('Button size', 'super' ),
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
                    'name'=> __('Button position', 'super' ),
                    'default' => self::get_value( $default, 'form_button_align', $settings, 'left' ),
                    'type'=>'select', 
                    'values'=>array(
                        'left' => 'Align Left', 
                        'center' => 'Align Center', 
                        'right' => 'Align Right', 
                    ),
                ), 
                'form_button_width' => array(
                    'name'=> __('Button width', 'super' ),
                    'default' => self::get_value( $default, 'form_button_width', $settings, 'auto' ),
                    'type'=>'select', 
                    'values'=>array(
                        'auto' => 'Auto', 
                        'fullwidth' => 'Fullwidth', 
                    ),
                ),         
                'form_button_icon_option' => array(
                    'name'=> __('Button icon position', 'super' ),
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
                    'name'=> __('Button icon visibility', 'super' ),
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
                    'name'=> __('Button icon animation', 'super' ),
                    'default' => self::get_value( $default, 'form_button_icon_animation', $settings, 'horizontal' ),
                    'filter'=>true,
                    'parent'=>'form_button_icon_option',
                    'filter_value'=>'left,right',
                    'type'=>'select', 
                    'values'=>array(
                        'horizontal' => 'Horizontal animation', 
                        'vertical' => 'Vertical animation', 
                    ),
                ),                                
                'form_button_icon' => array(
                    'name'=> __('Button icon', 'super' ),
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
            'name' => __( 'Backend Settings', 'super' ),
            'label' => __('Here you can change serveral settings that apply to your backend','super'),
            'fields' => array(
                'backend_contact_entry_list_fields' => array(
                    'name' => __('Columns for contact entries','super'),
                    'desc' => __('Put each on a new line.<br />Example:<br />fieldname|Field label<br />email|Email<br />phonenumber|Phonenumber','super'),
                    'default' => self::get_value( $default, 'backend_contact_entry_list_fields', $settings, "email|Email\nphonenumber|Phonenumber\nmessage|Message" ),
                    'type' => 'textarea', 
                ),
                'backend_debug_mode' => array(
                    'name' => __('Debug mode','super'),
                    'desc' => __('If enabled, you will be able to view/edit/copy the raw shortcode','super'),
                    'type'=>'select',
                    'default' => self::get_value( $default, 'backend_debug_mode', $settings, 'disabled' ),
                    'values'=>array(
                        'disabled' =>  __('Disabled','super'),
                        'enabled' =>  __('Enabled','super'),
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
            'name' => __( 'Custom CSS', 'super' ),
            'label' => __('Below you can override the default CSS styles','super'),
            'fields' => array(
                'theme_custom_css' => array(
                    'name' => __('Custom CSS','super'),
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
            'name' => __( 'SMTP Server', 'super' ),
            'label' => __( 'SMTP Configuration, leave your host empty to disable the use of SMTP', 'super' ),
            'fields' => array(        
                'smtp_server' => array(
                    'name' => __('Server','super'),
                    'default' => self::get_value( $default, 'smtp_server', $settings, '' ),
                ),
                'smtp_port' => array(
                    'name' => __('Port','super'),
                    'default' => self::get_value( $default, 'smtp_port', $settings, 25 ),
                    'width' => 100, 
                ),
                'smtp_timeout' => array(
                    'name' => __('Timeout','super'),
                    'default' => self::get_value( $default, 'smtp_timeout', $settings, 30 ),
                    'width' => 100, 
                ),
                'smtp_username' => array(
                    'name' => __('Username','super'),
                    'default' => self::get_value( $default, 'smtp_username', $settings, '' ),
                ),
                'smtp_password' => array(
                    'name' => __('Password','super'),
                    'default' => self::get_value( $default, 'smtp_password', $settings, '' ),
                    'password' => true,
                ),
                'smtp_host' => array(
                    'name' => __('Host','super'),
                    'default' => self::get_value( $default, 'smtp_host', $settings, '' ),
                ),
                'smtp_ssl' => array(
                    'name' => __('Enable SSL','super'),
                    'type'=>'select', 
                    'default' => self::get_value( $default, 'smtp_ssl', $settings, 0 ),
                    'values'=>array(
                        0 =>  __('Disabled','super'), 
                        1 =>  __('Enabled','super'), 
                    )
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
            'name' => __( 'Usefull Tags', 'super' ),
            'label' => __( 'Usefull Tags', 'super' ),
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
                '<li><strong>4. The formatting of</strong> <i>Send mail to/from</i> <strong>and</strong> <i>Confirmation to/from</i> <strong>must comply with <a href="http://www.faqs.org/rfcs/rfc2822.html" target="_blank">RFC 2822</a>. Examples are:</strong><br />',
                '<small style="color:red;">user@email.com -----OR----- <strong style="color:black;">{field_email}</strong><br />',
                'user@email.com, anotheruser@email.com -----OR----- <strong style="color:black;">{field_email}</strong>, <strong style="color:black;">{field_anotheremail}</strong><br />',
                'User1 &lt;user1@email.com&gt; -----OR----- <strong style="color:black;">{field_firstname}</strong> &lt;<strong style="color:black;">{field_email}</strong>&gt;<br />',
                'User1 &lt;user1@email.com&gt;, User2 &lt;user2@email.com&gt; -----OR----- <strong style="color:black;">{field_name1}</strong> &lt;<strong style="color:black;">{field_email1}</strong>&gt;, <strong style="color:black;">{field_name2}</strong> &lt;<strong style="color:black;">{field_email2}</strong>&gt;</small><br /><br />',
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
            'name' => __( 'Restore Default Settings', 'super' ),
            'label' => __( 'Restore Default Settings', 'super' ),
            'html' => array(
                '<p><input type="submit" class="restore-default button button-primary button-large" value="Restore Default Settings"></p>',
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
            'name' => __( 'System Status', 'super' ),
            'label' => __( 'System Status', 'super' ),
            'html' => array(
                '<p><b>PHP ' . __('version','super') . ':</b> ' . phpversion() . '</p>',
                '<p><b>MySQL ' . __('version','super') . ':</b> ' . $mysql_version . '</p>',                
                '<p><b>WordPress ' . __(' version','super') . ':</b> ' . get_bloginfo( 'version' ) . '</p>',
                '<p><b>Super Forms ' . __('version','super') . ':</b> ' . SUPER_VERSION . '</p>',
            ),
        );
        $array = apply_filters( 'super_settings_after_system_status_filter', $array, array( 'settings'=>$settings ) );
        
        
        /** 
         *	Support
         *
         *	@since		1.0.0
        */
        $array['support'] = array(        
            'hidden' => true,
            'name' => __( 'Support', 'super' ),
            'label' => __( 'Support', 'super' ),
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