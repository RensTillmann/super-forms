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
     *  Create array with default values of all settings
     *
     *  @since 4.6.0
     */
    public static function get_defaults($settings=null){
        // First retrieve all the fields and their default value
        $fields = self::fields( $settings );
        // Loop through all the settings and create a nice array so we can save it to our database
        $array = array();
        foreach( $fields as $k => $v ) {
            if( !isset( $v['fields'] ) ) continue;
            foreach( $v['fields'] as $fk => $fv ) {
                if( ( isset( $fv['type'] ) ) && ( $fv['type']=='multicolor' ) ) {
                    foreach( $fv['colors'] as $ck => $cv ) {
                        if( !isset( $cv['default'] ) ) $cv['default'] = '';
                        $array[$ck] = $cv['default'];
                    }
                }else{
                    if( !isset( $fv['default'] ) ) $fv['default'] = '';
                    $array[$fk] = $fv['default'];
                }
            }
        }
        return $array;
    }


    /**
     *  Retrieve statuses for contact entries
     *
     *  @since 3.4.0
     */
    public static function get_entry_statuses( $g=null, $return_default=false ) {
        $default = "pending|Pending|#808080|#FFFFFF\nprocessing|Processing|#808080|#FFFFFF\non_hold|On hold|#FF7700|#FFFFFF\naccepted|Accepted|#2BC300|#FFFFFF\ncompleted|Completed|#2BC300|#FFFFFF\ncancelled|Cancelled|#E40000|#FFFFFF\ndeclined|Declined|#E40000|#FFFFFF\nrefunded|Refunded|#000000|#FFFFFF";
        if( $return_default==true ) {
            return $default;
        }
        if( $g==null ) {
            $g = SUPER_Common::get_global_settings();
        }
        if(!isset($g['backend_contact_entry_status'])){
            $raw_statuses = $default;
        }else{
            $raw_statuses = $g['backend_contact_entry_status'];
        }
        $raw_statuses = explode( "\n", $raw_statuses );
        $statuses = array();
        $statuses[''] = array(
            'name' => esc_html__( 'None (default)', 'super-forms' )
        );
        foreach( $raw_statuses as $value ) {
            $status = explode( "|", $value );
            if( (isset($status[0])) && (isset($status[1])) ) {
                if(!isset($status[2])) $status[2] = '#808080';
                if(!isset($status[3])) $status[3] = '#FFFFFF';
                $statuses[$status[0]] = array(
                    'name' => $status[1], 
                    'bg_color' => $status[2], 
                    'color' => $status[3]
                );
            }
        }
        return $statuses;
    }
    

    /** 
	 *	All the fields
	 *
	 *	Create an array with all the fields
	 *
	 *	@since		1.0.0
	 */
	public static function fields( $s=null, $default=0 ) {
        global $wpdb;
        $mysql_version = $wpdb->get_var("SELECT VERSION() AS version");
        $g = SUPER_Common::get_global_settings();
        if( $s==null) {
            $s = $g;
            $statuses = self::get_entry_statuses($s);
        }else{
            $statuses = self::get_entry_statuses();
            if( (isset($s['id'])) && ($s['id']!=0) ) {
                $s = SUPER_Common::get_form_settings($s['id']);
            }
        }
        $new_statuses = array();
        foreach($statuses as $k => $v){
            $new_statuses[$k] = $v['name'];
        }
        $statuses = $new_statuses;
        unset($new_statuses);
        
        $submission_count = 0;
        if( ((isset($s['id'])) && ($s['id']!=0)) || (isset($_GET['id']) && !empty(absint($_GET['id']))) ) {
            if(empty($s['id'])) $s['id'] = 0;
            if(empty(absint($s['id']))) $s['id'] = absint($_GET['id']);
            $submission_count = get_post_meta( absint($s['id']), '_super_submission_count', true );
            if( !$submission_count ) {
                $submission_count = 0;
            }
        }

        $array = array();
        
        $array = apply_filters( 'super_settings_start_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        $backend_contact_entry_status = self::get_entry_statuses( null, true );


        /** 
         *  Admin email settings
         *
         *  @since      2.8.0
        */
        $array['admin_email_settings'] = array(        
            'name' => esc_html__( 'Admin E-mail', 'super-forms' ),
            'label' => esc_html__( 'Admin E-mail', 'super-forms' ),
            'fields' => array(
                'send' => array(
                    'name' => esc_html__( 'Send admin email', 'super-forms' ),
                    'label' => esc_html__( 'Send or do not send the admin emails', 'super-forms' ),
                    'default' => 'yes',
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'yes' => esc_html__( 'Send an admin email', 'super-forms' ),
                        'no' => esc_html__( 'Do not send an admin email', 'super-forms' ),
                    )
                ),
                'header_to' => array(
                    'name' => esc_html__( 'Send email to:', 'super-forms' ),
                    'label' => esc_html__( 'Recipient(s) email address seperated with commas', 'super-forms' ),
                    'placeholder' => esc_html__( 'your@email.com, your@email.com', 'super-forms' ),
                    'default' => '{option_admin_email}',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                    
                ),
                'header_from_type' => array(
                    'name'=> esc_html__( 'Send email from:', 'super-forms' ),
                    'label' => esc_html__( 'Enter a custom email address or use the blog settings', 'super-forms' ),
                    'default' => 'default',
                    'type'=>'select',
                    'values'=>array(
                        'default' => esc_html__(  'Default blog email and name', 'super-forms' ),
                        'custom' => esc_html__(  'Custom from', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'header_from' => array(
                    'name' => esc_html__( 'From email:', 'super-forms' ),
                    'label' => esc_html__( 'Example: info@companyname.com', 'super-forms' ),
                    'default' =>  '{option_admin_email}',
                    'placeholder' => esc_html__( 'Company Email Address', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'header_from_type',
                    'filter_value'=>'custom',
                    
                ),
                'header_from_name' => array(
                    'name' => esc_html__( 'From name:', 'super-forms' ),
                    'label' => esc_html__( 'Example: Company Name', 'super-forms' ),
                    'default' =>  '{option_blogname}',
                    'placeholder' => esc_html__( 'Your Company Name', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'header_from_type',
                    'filter_value'=>'custom',
                ),

                // @since 2.8.0 - custom reply to headers
                'header_reply_enabled' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( '(optional) Set a custom reply to header', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'header_reply' => array(
                    'name' => esc_html__( 'Reply to email:', 'super-forms' ),
                    'label' => esc_html__( 'Example: no-reply@companyname.com', 'super-forms' ),
                    'default' =>  '{option_admin_email}',
                    'placeholder' => esc_html__( 'Company Email Address', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'header_reply_enabled',
                    'filter_value'=>'true',
                ),
                'header_reply_name' => array(
                    'name' => esc_html__( 'Reply to name:', 'super-forms' ),
                    'label' => esc_html__( 'Example: Company Name', 'super-forms' ),
                    'default' =>  '{option_blogname}',
                    'placeholder' => esc_html__( 'Your Company Name', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'header_reply_enabled',
                    'filter_value'=>'true',
                ),
                'header_subject' => array(
                    'name' => esc_html__( 'Subject:', 'super-forms' ),
                    'label' => esc_html__( 'The subject for this email', 'super-forms' ),
                    'default' =>  'New question',
                    'placeholder' => esc_html__( 'New question', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                'email_body_open' => array(
                    'name' => esc_html__( 'Body header:', 'super-forms' ),
                    'label' => esc_html__( 'This content will be placed before the body content of the email.', 'super-forms' ),
                    'default' =>  esc_html__( 'The following information has been send by the submitter:', 'super-forms'  ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                'email_body' => array(
                    'name' => esc_html__( 'Body content:', 'super-forms' ),
                    'label' => esc_html__( 'Use a custom email body. Use {loop_fields} to retrieve the loop.', 'super-forms' ),
                    'default' =>  '<table cellpadding="5">{loop_fields}</table>',
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                'email_loop' => array(
                    'name' => esc_html__( 'Field Loop:', 'super-forms' ),
                    'label' => esc_html__( '{loop_fields} inside the email body will be replaced with this content', 'super-forms' ) . '<br />' . esc_html__( 'Use a custom loop. Use {loop_label} and {loop_value} to retrieve values.', 'super-forms' ),
                    'default' =>  '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>',
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'email_body_close' => array(
                    'name' => esc_html__( 'Body footer:', 'super-forms' ),
                    'label' => esc_html__( 'This content will be placed after the body content of the email.', 'super-forms' ),
                    'default' =>  esc_html__( "Best regards, {option_blogname}", "super-forms"  ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                // @since 4.5.0 - exclude empty values from email loop
                'email_exclude_empty' => array(
                    'name' => esc_html__( 'Exclude empty values from email loop', 'super-forms' ),
                    'label' => esc_html__( 'This will strip out any fields that where not filled out by the user', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable (exclude empty values)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                // @since 3.1.0 - auto line breaks
                'email_body_nl2br' => array(
                    'name' => esc_html__( 'Enable line breaks', 'super-forms' ),
                    'label' => esc_html__( 'This will convert line breaks to [br /] tags in HTML emails', 'super-forms' ),
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Automatically add line breaks (enabled by default)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                // @since 4.9.5 - RTL E-mails
                'email_rtl' => array(
                    'name' => esc_html__( 'Enable RTL E-mails', 'super-forms' ),
                    'label' => esc_html__( 'This will apply a right to left layout for your emails', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable RTL E-mails', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes'
                ),

                'header_cc' => array(
                    'name' => esc_html__( 'CC:', 'super-forms' ),
                    'label' => esc_html__( 'Send copy to following address(es)', 'super-forms' ),
                    'default' =>  '',
                    'placeholder' => esc_html__( 'someones@email.com, someones@emal.com', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'header_bcc' => array(
                    'name' => esc_html__( 'BCC:', 'super-forms' ),
                    'label' => esc_html__( 'Send copy to following address(es), without being able to see the address', 'super-forms' ),
                    'default' =>  '',
                    'placeholder' => esc_html__( 'someones@email.com, someones@emal.com', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                ),
                'header_additional' => array(
                    'name' => esc_html__('Additional Headers:', 'super-forms' ),
                    'label' => esc_html__('Add any extra email headers here (put each header on a new line)', 'super-forms' ),
                    'default' =>  '',
                    'type' =>'textarea',
                    'filter'=>true,
                    'parent'=>'send',
                    'filter_value'=>'yes',
                )
            ),
        );
        $array = apply_filters( 'super_settings_after_admin_email_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *  Confirmation email settings
         *
         *  @since      2.8.0
        */
        $array['confirmation_email_settings'] = array(        
            'name' => esc_html__( 'Confirmation E-mail', 'super-forms' ),
            'label' => esc_html__( 'Confirmation E-mail', 'super-forms' ),
            'fields' => array(
                'confirm' => array(
                    'name' => esc_html__( 'Send confirmation email', 'super-forms' ),
                    'label' => esc_html__( 'Send or do not send confirmation emails', 'super-forms' ),
                    'default' =>  'yes',
                    'filter'=>true,
                    'type'=>'select',
                    'values'=>array(
                        'yes' => esc_html__( 'Send a confirmation email', 'super-forms' ),
                        'no' => esc_html__( 'Do not send a confirmation email', 'super-forms' ),
                    )
                ),
                'confirm_to' => array(
                    'name' => esc_html__( 'Send email to:', 'super-forms' ),
                    'label' => esc_html__( 'Recipient(s) email address seperated by commas', 'super-forms' ),
                    'default' =>  '{email}',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),
                'confirm_from_type' => array(
                    'name'=> esc_html__( 'Send email from:', 'super-forms' ),
                    'label' => esc_html__( 'Enter a custom email address or use the blog settings', 'super-forms' ),
                    'default' =>  'default',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',   
                    'type'=>'select',
                    'values'=>array(
                        'default' => esc_html__(  'Default blog email and name', 'super-forms' ),
                        'custom' => esc_html__(  'Custom from', 'super-forms' ),
                    )
                ),
                'confirm_from' => array(
                    'name' => esc_html__( 'From email:', 'super-forms' ),
                    'label' => esc_html__( 'Example: info@companyname.com', 'super-forms' ),
                    'default' =>  '{option_admin_email}',
                    'placeholder' => esc_html__( 'Company Email Address', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm_from_type',
                    'filter_value'=>'custom'
                ),
                'confirm_from_name' => array(
                    'name' => esc_html__( 'From name:', 'super-forms' ),
                    'label' => esc_html__( 'Example: Company Name', 'super-forms' ),
                    'default' =>  '{option_blogname}',
                    'placeholder' => esc_html__( 'Your Company Name', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm_from_type',
                    'filter_value'=>'custom'
                ),

                // @since 2.8.0 - custom reply to headers
                'confirm_header_reply_enabled' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( '(optional) Set a custom reply to header', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),
                'confirm_header_reply' => array(
                    'name' => esc_html__( 'Reply to email:', 'super-forms' ),
                    'label' => esc_html__( 'Example: no-reply@companyname.com', 'super-forms' ),
                    'default' =>  '{option_admin_email}',
                    'placeholder' => esc_html__( 'Company Email Address', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm_header_reply_enabled',
                    'filter_value'=>'true',
                ),
                'confirm_header_reply_name' => array(
                    'name' => esc_html__( 'Reply to name:', 'super-forms' ),
                    'label' => esc_html__( 'Example: Company Name', 'super-forms' ),
                    'default' =>  '{option_blogname}',
                    'placeholder' => esc_html__( 'Your Company Name', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm_header_reply_enabled',
                    'filter_value'=>'true',
                ),
                'confirm_subject' => array(
                    'name' => esc_html__( 'Subject:', 'super-forms' ),
                    'label' => esc_html__( 'The confirmation subject for this email', 'super-forms' ),
                    'default' =>  esc_html__( 'Thank you!', 'super-forms'  ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                'confirm_body_open' => array(
                    'name' => esc_html__( 'Body header:', 'super-forms' ),
                    'label' => esc_html__( 'This content will be placed before the confirmation email body.', 'super-forms' ),
                    'default' =>  esc_html__( "Dear user,\n\nThank you for contacting us!", "super-forms"  ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                'confirm_body' => array(
                    'name' => esc_html__( 'Body content:', 'super-forms' ),
                    'label' => esc_html__( 'Use a custom email body. Use {loop_fields} to retrieve the loop.', 'super-forms' ),
                    'default' =>  '<table cellpadding="5">{loop_fields}</table>',
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                'confirm_email_loop' => array(
                    'name' => esc_html__( 'Field Loop:', 'super-forms' ),
                    'label' => esc_html__( '{loop_fields} inside the email body will be replaced with this content', 'super-forms' ) . '<br />' . esc_html__( 'Use a custom loop. Use {loop_label} and {loop_value} to retrieve values.', 'super-forms' ),
                    'default' =>  '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>',
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),
                'confirm_body_close' => array(
                    'name' => esc_html__( 'Body footer:', 'super-forms' ),
                    'label' => esc_html__( 'This content will be placed after the confirmation email body.', 'super-forms' ),
                    'default' =>  esc_html__( "We will reply within 48 hours.\n\nBest Regards, {option_blogname}", "super-forms"  ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes',
                    'i18n'=>true
                ),
                // @since 4.5.0 - exclude empty values from email loop
                'confirm_exclude_empty' => array(
                    'name' => esc_html__( 'Exclude empty values from email loop', 'super-forms' ),
                    'label' => esc_html__( 'This will strip out any fields that where not filled out by the user', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable (exclude empty values)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),
                // @since 3.1.0 - auto line breaks
                'confirm_body_nl2br' => array(
                    'name' => esc_html__( 'Enable line breaks', 'super-forms' ),
                    'label' => esc_html__( 'This will convert line breaks to [br /] tag in HTML emails', 'super-forms' ),
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Automatically add line breaks (enabled by default)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),
                // @since 4.9.5 - RTL E-mails
                'confirm_rtl' => array(
                    'name' => esc_html__( 'Enable RTL E-mails', 'super-forms' ),
                    'label' => esc_html__( 'This will apply a right to left layout for your emails', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable RTL E-mails', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),

                'confirm_header_cc' => array(
                    'name' => esc_html__( 'CC:', 'super-forms' ),
                    'label' => esc_html__( 'Send copy to following address(es)', 'super-forms' ),
                    'default' =>  '',
                    'placeholder' => esc_html__( 'someones@email.com, someones@emal.com', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),
                'confirm_header_bcc' => array(
                    'name' => esc_html__( 'BCC:', 'super-forms' ),
                    'label' => esc_html__( 'Send copy to following address(es), without being able to see the address', 'super-forms' ),
                    'default' =>  '',
                    'placeholder' => esc_html__( 'someones@email.com, someones@emal.com', 'super-forms' ),
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                ),
                'confirm_header_additional' => array(
                    'name' => esc_html__('Additional Headers:', 'super-forms' ),
                    'label' => esc_html__('Add any extra email headers here (put each header on a new line)', 'super-forms' ),
                    'default' =>  '',
                    'type' =>'textarea',
                    'filter'=>true,
                    'parent'=>'confirm',
                    'filter_value'=>'yes'
                )
            ),
        );
        $array = apply_filters( 'super_settings_after_confirmation_email_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        /** 
         *	Global Overriding
         *
         *	@since		4.9.5
        */
        $array['global_overriding'] = array(        
            'hidden' => true,
            'name' => esc_html__( 'Global Overriding', 'super-forms' ),
            'label' => esc_html__( 'Global Overriding', 'super-forms' ),
            'html' => array(
                '<p class="super-global-email-config-notice" style="background-color: #ff3535;color: #ffffff;font-size: 14px;line-height: 16px;margin-bottom: 30px;border-radius: 5px;padding: 15px 25px 15px 25px;">',
                '<strong style="font-size:16px;">' . esc_html__( 'Important notice:', 'super-forms' ) . '</strong> ',
                esc_html__( 'Here you can override specific settings for all your forms (including previously created forms). Only use it if you have a setting that needs to be used on all forms. To actually apply the setting to all forms you must enable the checkbox \'Force: ...\' that corresponds with the setting. If you later decide to uncheck the checkbox, the old form values will be used again. Meaning if you use the below settings it will not actually override any of your form settings, but simply ignore them.', 'super-forms' ),
                '</p>',
            ),
            'fields' => array(

                // @since 4.9.5 - configure global email settings (can be used to override all form settings)
                // Set global 'To' header, can override 'header_to' and 'confirm_to' settings
                'global_email_to_admin' => array(
                    'name' => esc_html__( 'Send to (Admin E-mails)', 'super-forms' ),
                    'desc' => esc_html__( 'The email address where emails are sent to', 'super-forms' ),
                    'default' =>  '{option_admin_email}',
                    'placeholder' => esc_html__( 'Enter an email address', 'super-forms' ),
                    'children' => array(
                        'global_email_to_admin_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Admin E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        )
                    )
                ),
                'global_email_to_confirm' => array(
                    'name' => esc_html__( 'Send to (Confirmation E-mails)', 'super-forms' ),
                    'desc' => esc_html__( 'The email address where emails are sent to', 'super-forms' ),
                    'default' =>  '{email}',
                    'placeholder' => esc_html__( '{email}', 'super-forms' ),
                    'children' => array(
                        'global_email_to_confirm_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Confirmation E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            ),
                        ),
                    )
                ),
                
                // Set global 'From' header, can override 'header_from' and 'confirm_from' settings
                'global_email_from' => array(
                    'name' => esc_html__( 'From email', 'super-forms' ),
                    'desc' => sprintf( esc_html__( 'The email address which emails are sent from.%s(if you encounter issues with receiving emails, try to use info@%s).%sIf you are using an email provider (Gmail, Yahoo, Outlook.com, etc) it should be the email address of that account.', 'super-forms' ), '<br />', '<strong style="color:red;">' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>', '<br />' ),
                    'default' =>  '{option_admin_email}',
                    'placeholder' => esc_html__( 'Enter an email address', 'super-forms' ),
                    'children' => array(
                        'global_email_from_admin_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Admin E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            ),
                        ),
                        'global_email_from_confirm_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Confirmation E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        ),
                    )
                ),

                // Set global 'From name' header, can override 'header_from_name' and 'confirm_from_name' settings
                'global_email_from_name' => array(
                    'name' => esc_html__( 'From name', 'super-forms' ),
                    'desc' => esc_html__( 'The name which emails are sent from.', 'super-forms' ),
                    'default' =>  '{option_blogname}',
                    'placeholder' => esc_html__( 'Enter a name', 'super-forms' ),
                    'children' => array(
                        'global_email_from_name_admin_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Admin E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        ),
                        'global_email_from_name_confirm_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Confirmation E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        )
                    )
                ),

                // Set global 'Reply to' header, can override 'header_reply' and 'confirm_reply' settings
                'global_email_reply' => array(
                    'name' => esc_html__( '(optional) Reply to email', 'super-forms' ),
                    'desc' => esc_html__( 'The email address where user will reply to (leave blank to use \'From email\' setting).', 'super-forms' ),
                    'default' =>  '',
                    'placeholder' => esc_html__( 'Enter an email address', 'super-forms' ),
                    'children' => array(
                        'global_email_reply_admin_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Admin E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        ),
                        'global_email_reply_confirm_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Confirmation E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        )
                    )
                ),

                // Set global 'Reply name' header, can override 'header_reply_name' and 'confirm_reply_name' settings
                'global_email_reply_name' => array(
                    'name' => esc_html__( '(optional) Reply to name', 'super-forms' ),
                    'desc' => esc_html__( 'The name where user will reply to (leave blank to use \'From name\' setting).', 'super-forms' ),
                    'default' =>  '',
                    'placeholder' => esc_html__( 'Enter an email address', 'super-forms' ),
                    'children' => array(
                        'global_email_reply_name_admin_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Admin E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        ),
                        'global_email_reply_name_confirm_force' => array(
                            'default' =>  '',
                            'type' => 'checkbox',
                            'values' => array(
                                'true' => esc_html__( 'Force: use the above setting for all Confirmation E-mails (the setting defined on individual forms will be ignored)', 'super-forms' ),
                            )
                        )
                    )
                )
            )
        );

        /** 
         *  File Upload Settings
         *
         *  @since      1.0.0
        */

        // Get available roles
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters( 'editable_roles', $all_roles );
        $roles = array();
        foreach( $editable_roles as $k => $v ) {
            $roles[$k] = $k;
        }
        $array['file_upload_settings'] = array(        
            'hidden' => true,
            'name' => esc_html__( 'File Upload Settings', 'super-forms' ),
            'label' => esc_html__('Here you can change the way files are being processed and uploaded', 'super-forms' ),
            'fields' => array(
                'file_upload_hide_from_media_library' => array(
                    'name' => esc_html__('Hide files from Media Library that were uploaded via forms', 'super-forms' ),
                    'desc' => esc_html__('Please note that when you are storing your files in a secure/private directory outside the root the files will automatically not be added to the Media Library.', 'super-forms' ),
                    // allow empty / allow_empty
                    'default' =>  'true',
                    'values' => array(
                        'true' => esc_html__('Do not show file uploads in the Media Library', 'super-forms' )
                    ),
                    'type' => 'checkbox'
                ),
                'file_upload_remove_hyperlink_in_emails' => array(
                    'name' => esc_html__('Remove hyperlink in emails', 'super-forms' ),
                    'desc' => esc_html__('When enabled users will only be able to download the file as an attachment and there will be no reference to the file location. Please note that some email clients have a limit in attachment size that you can send. If you send large attachments it might be a good idea to leave this option unchecked.', 'super-forms' ),
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Remove URL (hyperlink) from files in emails', 'super-forms' )
                    ),
                    'type' => 'checkbox'
                ),
                'file_upload_remove_from_email_loop' => array(
                    'name' => esc_html__('Remove files from {loop_fields} in emails', 'super-forms' ),
                    'desc' => esc_html__('When enabled the files will no longer be listed inside the email when using the {loop_fields} tag. The files can only be downloaded as an attachment. Just keep in mind that when you are working with large files the attachment might be missing due to the email client limitations.', 'super-forms' ),
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Remove files from {loop_fields} tag', 'super-forms' )
                    ),
                    'type' => 'checkbox'
                ),
                'file_upload_submission_delete' => array(
                    'name' => esc_html__('Delete files from server after form submissions', 'super-forms' ),
                    'desc' => esc_html__('When enabled files are automatically deleted after form submissions.', 'super-forms' ),
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Delete files from server after the form was submitted', 'super-forms' )
                    ),
                    'type' => 'checkbox'
                ),
                'file_upload_entry_delete' => array(
                    'name' => esc_html__('After deleting a Contact Entry delete all it\'s associated files', 'super-forms' ),
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Delete associated files after deleting a Contact Entry', 'super-forms' )
                    ),
                    'type' => 'checkbox'
                ),
                'file_upload_use_year_month_folders' => array(
                    'name' => esc_html__('Organize uploads into a month/year based folders e.g:', 'super-forms' ) . ' ' . date('Y') . '/' . date('m'),
                    'default' =>  'true',
                    'values' => array(
                        'true' => esc_html__('Yes, store files in a year/month folder structure', 'super-forms' )
                    ),
                    'type' => 'checkbox'
                ),
                'file_upload_auth' => array(
                    'name' => esc_html__('Only allow logged in users to download secure/private files:', 'super-forms' ),
                    'desc' => esc_html__('This setting only works when files are stored in a secure directory outside the wp-content directory. When enabled, this will prevent any none authorized download of files. Users must be logged in before they can download the files.', 'super-forms' ),
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Only allow logged in users to download secure/private files', 'super-forms' )
                    ),
                    'type' => 'checkbox',
                    'filter' => true,
                ),                
                'file_upload_auth_roles' => array(
                    'name' => esc_html__('Only allow the following user roles to download secure/private files:', 'super-forms' ),
                    'label' => esc_html__('This setting only works when files are stored in a secure directory outside the wp-content directory. Leave blank to allow any logged in user to download the files.', 'super-forms' ),
                    'default' =>  '',
                    'info' => sprintf( 
                        esc_html__( 
                            '%2$sSeperate each role by comma, available roles:%3$s%1$s%4$s', 
                            'super-forms' 
                        ), '<br />', '<strong>', '</strong>', implode(', ', $roles)
                    ),
                    'filter' => true,
                    'parent' => 'file_upload_auth',
                    'filter_value' => 'true',
                ),
                'file_upload_dir' => array(
                    'name' => esc_html__('Select where files should be uploaded to', 'super-forms' ),
                    'label' => esc_html__('Please note that changing this directory will not affect any previously uploaded files.', 'super-forms' ),
                    'info' => sprintf( 
                        esc_html__( 
                            '%6$sPlease define a directory relative to your%7$s %4$sroot%5$s %6$spath.%7$s%1$s%1$s
                            Your site root:%1$s
                            %2$s' . ABSPATH . '%3$s%1$s
                            Your wp-content directory relative to the root:%1$s
                            %2$s' . str_replace(ABSPATH, "", WP_CONTENT_DIR) . '%3$s%1$s
                            %4$sThe%5$s %6$sdefault upload directory%7$s %4$srelative to the root:%5$s%1$s
                            %2$s' . SUPER_FORMS_UPLOAD_DIR . '%3$s%1$s
                            %4$sExample for custom%5$s %6$spublic directory:%7$s%1$s
                            Site visitors will be able to access/download files directly via URL\'s%1$s
                            %2$smy-custom-public-folder%3$s%1$s
                            %4$sExample for custom%5$s %6$sprivate directory:%7$s%1$s
                            Files will be stored securely outside of the site root directory.%1$s
                            Site visitors won\'t be able to access/download files via URL\'s%1$s
                            Only use this option if you have sensitive file uploads. If you do not, then it might be best to just use the "Hide files from Media Library" setting.%1$s
                            Storing files at a secure location can brake some functionality and features related to files/media.%1$s
                            On some servers it isn\'t possible for Super Forms to create the private directory due to permissions, in that case contact your provider.%1$s
                            %2$s../my-custom-private-folder%3$s%1$s
                            %6$sNote to WordPress installations in a subdirectory:%7$s to use a secure directory you will have to go up another directory in your root tree like so:%1$s
                            %2$s../../my-custom-private-folder%3$s',
                            'super-forms' 
                        ), '<br />', '<code>', '</code>', '<strong>', '</strong>', '<strong style="color:red;">', '</strong>'
                    ),
                    'default' =>  SUPER_FORMS_UPLOAD_DIR ,
                    'filter' => true
                ),
                // For future improvements:
                // - upload to Google Drive
                // - upload to AWS
                // - upload to Dropbox
                // - upload to FTP?
                // - upload somewhere else? (look at WP Migrate plugin for other good options)
            ),
        );
        $array = apply_filters( 'super_settings_after_file_upload_settings_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *	SMTP Server Configuration
         *
         *	@since		1.0.0
        */
        $array['smtp_server'] = array(        
            'hidden' => true,
            'name' => esc_html__( 'SMTP Server', 'super-forms' ),
            'label' => esc_html__( 'SMTP Server', 'super-forms' ),
            'fields' => array(
                // SMTP Settings
                'smtp_enabled' => array(
                    'name' => esc_html__( 'Set mailer to use SMTP', 'super-forms' ),
                    'label' => esc_html__( 'Use the default wp_mail() or use SMTP to send emails', 'super-forms' ),
                    'default' =>  'disabled',
                    'filter' => true,
                    'type' => 'select',
                    'values' => array(
                        'disabled' => esc_html__( 'Disabled', 'super-forms' ),
                        'enabled' => esc_html__( 'Enabled', 'super-forms' )
                    )
                ),
                'smtp_host' => array(
                    'name' => esc_html__( 'Specify main and backup SMTP servers', 'super-forms' ),
                    'label' => esc_html__( 'Example: smtp1.example.com;smtp2.example.com', 'super-forms' ),
                    'default' =>  'smtp1.example.com;smtp2.example.com',
                    'placeholder' => esc_html__( 'Your SMTP server', 'super-forms' ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_auth' => array(
                    'name' => esc_html__( 'Enable SMTP authentication', 'super-forms' ),
                    'default' =>  'disabled',
                    'type' => 'select',
                    'values' => array(
                        'disabled' => esc_html__( 'Disabled', 'super-forms' ),
                        'enabled' => esc_html__( 'Enabled', 'super-forms' )
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_username' => array(
                    'name' => esc_html__( 'SMTP username', 'super-forms' ),
                    'default' =>  '',
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_password' => array(
                    'name' => esc_html__( 'SMTP password', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'password',
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),                                
                'smtp_secure' => array(
                    'name' => esc_html__( 'Enable TLS or SSL encryption', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'select',
                    'values' => array(
                        '' => esc_html__( 'Disabled', 'super-forms' ),
                        'ssl' => esc_html__( 'SSL', 'super-forms' ),
                        'tls' => esc_html__( 'TLS', 'super-forms' )
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_port' => array(
                    'name' => esc_html__( 'TCP port to connect to', 'super-forms' ),
                    'label' => sprintf( esc_html__( 'SMTP – port 25 or 2525 or 587%sSecure SMTP (SSL / TLS) – port 465 or 25 or 587, 2526', 'super-forms' ), '<br />' ),
                    'default' =>  '465',
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                    'width' => 100, 
                ),
                'smtp_timeout' => array(
                    'name' => esc_html__( 'Timeout (seconds)', 'super-forms' ),
                    'default' =>  30 ,
                    'width' => 100, 
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_keep_alive' => array(
                    'name' => esc_html__( 'Keep connection open after each message', 'super-forms' ),
                    'default' =>  'disabled',
                    'type' => 'select',
                    'values' => array(
                        'disabled' => esc_html__( 'Disabled', 'super-forms' ),
                        'enabled' => esc_html__( 'Enabled', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_debug' => array(
                    'name' => esc_html__( 'SMTP debug output mode', 'super-forms' ),
                    'default' =>  0 ,
                    'type' => 'select',
                    'values' => array(
                        0 => esc_html__( '0 - No output', 'super-forms' ),
                        1 => esc_html__( '1 - Commands', 'super-forms' ),
                        2 => esc_html__( '2 - Data and commands', 'super-forms' ),
                        3 => esc_html__( '3 - As 2 plus connection status', 'super-forms' ),
                        4 => esc_html__( '4 - Low-level data output', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'smtp_enabled',
                    'filter_value' => 'enabled',
                ),
                'smtp_debug_output_mode' => array(
                    'name' => esc_html__( 'How to handle debug output', 'super-forms' ),
                    'default' =>  'echo',
                    'type' => 'select',
                    'values' => array(
                        'echo' => esc_html__( 'ECHO - Output plain-text as-is, appropriate for CLI', 'super-forms' ),
                        'html' => esc_html__( 'HTML - Output escaped, line breaks converted to `<br>`, appropriate for browser output', 'super-forms' ),
                        'error_log' => esc_html__( 'ERROR_LOG - Output to error log as configured in php.ini', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'smtp_debug',
                    'filter_value' => '1,2,3,4',
                )
            )
        );
        $array = apply_filters( 'super_settings_after_global_overriding_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *	Email Headers
         *
         *	@since		1.0.0
        */
        $array['email_headers'] = array(
            'name' => esc_html__( 'Email headers', 'super-forms' ),
            'label' => esc_html__( 'Email headers', 'super-forms' ),
            'fields' => array(
                'header_content_type' => array(
                    'name' => esc_html__( 'Content type:', 'super-forms' ),
                    'label' => esc_html__( 'The content type to use for this email', 'super-forms' ),
                    'default' =>  'html',
                    'type'=>'select',
                    'values'=>array(
                        'html'=>'HTML',
                        'plain'=>'Plain text',
                    )
                ),
                'header_charset' => array(
                    'name' => esc_html__( 'Charset:', 'super-forms' ),
                    'label' => sprintf( esc_html__( 'The content type to use for this email.%sExample: UTF-8 or ISO-8859-1', 'super-forms' ), '<br />' ),
                    'default' =>  'UTF-8',
                    'i18n'=>true
                ),
            )
        );
        $array = apply_filters( 'super_settings_after_email_headers_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        /** 
         *  Email Attachments
         *
         *  @since      3.3.2
        */
        $array['email_attachments'] = array(
            'name' => esc_html__( 'Email attachments', 'super-forms' ),
            'label' => esc_html__( 'Email attachments', 'super-forms' ),
            'fields' => array(
                'admin_attachments' => array(
                    'name' => esc_html__( 'Attachments for admin emails:', 'super-forms' ),
                    'label' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'file',
                    'multiple' => 'true',
                    'i18n'=>true
                ),
                'confirm_attachments' => array(
                    'name' => esc_html__( 'Attachments for confirmation emails:', 'super-forms' ),
                    'label' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'file',
                    'multiple' => 'true',
                    'i18n'=>true
                ),
            )
        );
        $array = apply_filters( 'super_settings_after_email_attachments_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *	Email Template
         *
         *	@since		1.0.0
        */
        $array['email_template'] = array(        
            'name' => esc_html__( 'Email template', 'super-forms' ),
            'label' => esc_html__( 'Email template', 'super-forms' ),
            'fields' => array(        
                'email_template' => array(
                    'name' => esc_html__( 'Select email template', 'super-forms' ),
                    'label' => esc_html__( 'Choose which email template you would like to use', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  'default_email_template',
                    'filter'=>true,
                    'values'=>array(
                        'default_email_template' => esc_html__('Default email template', 'super-forms' ),
                    )
                )
            )
        );
        $array = apply_filters( 'super_settings_after_email_template_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *  Form Settings
         *
         *  @since      1.0.0
        */
        $array['form_settings'] = array(        
            'name' => esc_html__( 'Form settings', 'super-forms' ),
            'label' => esc_html__( 'Form settings', 'super-forms' ),
            'fields' => array(        
                'save_contact_entry' => array(
                    'name' => esc_html__( 'Save data', 'super-forms' ),
                    'label' => esc_html__( 'Choose if you want to save the user data as a Contact Entry', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  'yes',
                    'filter'=>true,
                    'values'=>array(
                        'yes' => esc_html__('Save as Contact Entry', 'super-forms' ),
                        'no' => esc_html__('Do not save data', 'super-forms' ),
                    )
                ), 
                // @since 4.5.0 - do not save empty values for contact entries
                'contact_entry_exclude_empty' => array(
                    'name' => esc_html__( 'Do not save empty values', 'super-forms' ),
                    'label' => esc_html__( 'This will prevent empty values from being saved for the Contact Entry', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable (do not save empty values)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'save_contact_entry',
                    'filter_value'=>'yes'
                ),
                // @since 4.0.0  - conditionally save contact entry based on user input
                'conditionally_save_entry' => array(
                    'hidden_setting' => true,
                    'default' =>  '',
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Conditionally save Contact Entry based on user data', 'super-forms' ),
                    ),
                    'parent' => 'save_contact_entry',
                    'filter_value' => 'yes'
                ),
                'conditionally_save_entry_check' => array(
                    'hidden_setting' => true,
                    'type' => 'conditional_check',
                    'name' => esc_html__( 'Only save entry when following condition is met', 'super-forms' ),
                    'label' => esc_html__( 'Your are allowed to enter field {tags} to do the check', 'super-forms' ),
                    'default' =>  '',
                    'placeholder' => "{fieldname},value",
                    'filter'=>true,
                    'parent' => 'conditionally_save_entry',
                    'filter_value' => 'true'
                    
                ),


                // @since 3.4.0  - custom contact entry status
                'contact_entry_custom_status' => array(
                    'name' => esc_html__( 'Contact entry status', 'super-forms' ),
                    'label' => sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>'),
                    'type'=>'select',
                    'default' =>  '',
                    'values' => $statuses,
                    'filter'=>true,
                    'parent' => 'save_contact_entry',
                    'filter_value' => 'yes'
                ),

                // @since 1.2.6  - custom contact entry titles
                'enable_custom_entry_title' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Enable custom entry titles', 'super-forms' ),
                    ),
                    'parent' => 'save_contact_entry',
                    'filter_value' => 'yes'
                ),
                'contact_entry_title' => array(
                    'name' => esc_html__('Enter a custom entry title', 'super-forms' ),
                    'label' => esc_html__( 'You can use field tags {field_name} if you want', 'super-forms' ),
                    'default' =>  esc_html__( 'Contact entry', 'super-forms'  ),
                    'filter'=>true,
                    'parent'=>'enable_custom_entry_title',
                    'filter_value'=>'true'
                ),
                'contact_entry_add_id' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Append entry ID after the custom title', 'super-forms' ),
                    ),
                    'parent' => 'enable_custom_entry_title',
                    'filter_value' => 'true'
                ),
                // @since 4.9.600 - prevent submitting form when entry title already exists
                'contact_entry_unique_title' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Prevent submitting form when entry title already exists', 'super-forms' ),
                    ),
                    'parent' => 'enable_custom_entry_title',
                    'filter_value' => 'true'
                ),
                'contact_entry_unique_title_compare' => array(
                    'name' => esc_html__( 'Compare duplicate contact entry title against:', 'super-forms' ),
                    'default' =>  'form',
                    'type'=>'select',
                    'values'=>array(
                        'form' => esc_html__( 'Compare against current form entries only (default)', 'super-forms' ),
                        'global' => esc_html__( 'Compare against all entries (all forms)', 'super-forms' ),
                        'ids' => esc_html__( 'Compare against entries from the following form ID\'s (specific forms only)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'contact_entry_unique_title',
                    'filter_value' => 'true'
                ),
                'contact_entry_unique_title_trashed' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Also compare against trashed contact entries', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'contact_entry_unique_title',
                    'filter_value' => 'true'
                ),
                'contact_entry_unique_title_form_ids' => array(
                    'name' => esc_html__('Enter form ID\'s to compare duplicate entry title against', 'super-forms' ),
                    'label' => esc_html__( 'Seperate each form ID by a comma.', 'super-forms' ) . ' ' . esc_html__( 'You can use field tags {field_name} if you want', 'super-forms' ),
                    'default' =>  '',
                    'filter'=>true,
                    'parent'=>'contact_entry_unique_title_compare',
                    'filter_value'=>'ids'
                ),
                'contact_entry_unique_title_msg' => array(
                    'name' => esc_html__('Duplicate entry title error message', 'super-forms' ),
                    'label' => esc_html__('This message will be displayed to the user if an entry with the same title already exists based on above configuration', 'super-forms' ),
                    'type'=>'textarea',
                    'default' =>  esc_html__( 'Could not submit the form because a contact entry with the exact same title already exists!', 'super-forms'  ),
                    'filter'=>true,
                    'parent' => 'contact_entry_unique_title',
                    'filter_value' => 'true'
                ),

                // @since 3.2.0 - Save form progression so that when a user returns the data isn't lost
                'save_form_progress' => array(
                    'name' => esc_html__( 'Save form progression (when a user returns, the data isn\'t lost)', 'super-forms' ),
                    'label' => esc_html__( 'When enabled it will save the form data entered by the user and populates the form with this data when the user returns or refreshes the page', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Yes, save form progression', 'super-forms' ),
                    )
                ),

                // @since 2.9.0 - allow to autopopulate form with last entry data based on logged in user
                'retrieve_last_entry_data' => array(
                    'name' => esc_html__( 'Retrieve form data from users last submission', 'super-forms' ),
                    'label' => esc_html__( 'This only works for logged in users or when $_GET or $_POST contains a key [contact_entry_id] with the entry ID (in that case the "form ID" setting is obsolete)', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Autopopulate form with last contact entry data', 'super-forms' ),
                    ),
                    'filter'=>true
                ),
                'retrieve_last_entry_form' => array(
                    'name' => esc_html__( 'Set a form ID to retrieve data from (seperated by comma)', 'super-forms' ),
                    'label' => esc_html__( 'You are allowed to use multiple ID\'s. Please note that always the last entry will be used.', 'super-forms' ) . ' ' . esc_html__( 'This allows you to retrieve entry data from a different form and autopopulate it inside this form.', 'super-forms' ),
                    'default' =>  '',
                    'filter'=>true,
                    'parent' => 'retrieve_last_entry_data',
                    'filter_value' => 'true'
                ),
                // @since 2.2.0 - update contact entry data if a contact entry was found based on search field or when POST or GET contained the entry id: ['contact_entry_id']
                'update_contact_entry' => array(
                    'name' => esc_html__( 'Enable contact entry updating', 'super-forms' ),
                    'label' => sprintf( esc_html__( 'This only works if either one of the following is used/enabled:%1$s- You enabled "Retrieve form data from users last submission"%1$s- Your form contains a search field that searches contact entries based on their title;%1$s- When $_GET or $_POST contains a key [contact_entry_id] with the entry ID;%1$s- When you have a Hidden field named "hidden_contact_entry_id" with the tag {user_last_entry_id} set as it\'s Default value;', 'super-forms' ), '<br />' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Update contact entry data (if contact entry was found)', 'super-forms' ),
                    ),
                    'filter'=>true
                ),
                // @since 4.7.7 - prevent creating a new contact entry if we successfully found an existing entry and updated it
                'contact_entry_prevent_creation' => array(
                    'name' => esc_html__( 'Do not create a new Contact Entry when an existing one was updated', 'super-forms' ),
                    'label' => esc_html__( 'Enable this if you do not wish to create a brand new Contact Entry upon updating an existing Contact Entry', 'super-forms' ),
                    'default' =>  '',
                    'type'=>'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Do not create a new Contact Entry', 'super-forms' )
                    ),
                    'filter'=>true,
                    'parent' => 'update_contact_entry',
                    'filter_value' => 'true'
                ),
                // @since 3.4.0  - allow to update the contact entry status after updating the entry
                'contact_entry_custom_status_update' => array(
                    'name' => esc_html__( 'Contact entry status after updating', 'super-forms' ),
                    'label' => sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ),
                    'type'=>'select',
                    'default' =>  '',
                    'values' => $statuses,
                    'filter'=>true,
                    'parent' => 'update_contact_entry',
                    'filter_value' => 'true'
                ),
                'form_processing_overlay' => array(
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Display form processing overlay (popup)', 'super-forms' ),
                    ),
                ),
                'form_show_thanks_msg' => array(
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Show thank you message', 'super-forms' ),
                    ),
                ),
                'form_thanks_title' => array(
                    'name' => esc_html__( 'Thanks Title', 'super-forms' ),
                    'label' => esc_html__( 'A custom thank you title shown after a user completed the form.', 'super-forms' ),
                    'default' =>  esc_html__( 'Thank you!', 'super-forms'  ),
                    'filter'=>true,
                    'parent' => 'form_show_thanks_msg',
                    'filter_value' => 'true',
                    'i18n'=>true
                ),
                'form_thanks_description' => array(
                    'name' => esc_html__( 'Thanks Description', 'super-forms' ),
                    'label' => esc_html__( 'A custom thank you description shown after a user completed the form.', 'super-forms' ),
                    'default' =>  esc_html__( 'We will reply within 24 hours.', 'super-forms'  ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent' => 'form_show_thanks_msg',
                    'filter_value' => 'true',
                    'i18n'=>true
                ),
                'form_preload' => array(
                    'name' => esc_html__( 'Preloader (form loading icon)', 'super-forms' ),
                    'label' => esc_html__( 'Custom use of preloader for the form.', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  '1',
                    'values'=>array(
                        '1' => esc_html__( 'Enabled', 'super-forms' ),
                        '0' => esc_html__( 'Disabled', 'super-forms' ),
                    ),
                ),
                'form_duration' => array(
                    'name' => esc_html__( 'Error FadeIn Duration', 'super-forms' ),
                    'desc' => esc_html__( 'The duration for error messages to popup in milliseconds.', 'super-forms' ),
                    'default' =>  500 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>1000,
                    'steps'=>100,
                ),                
                'enable_ajax' => array(
                    'hidden' => true,
                    'name' => esc_html__( 'Enable Ajax', 'super-forms' ),
                    'label' => esc_html__( 'If your site uses Ajax to request post content activate this option. This makes sure styles/scripts are loaded before the Ajax request.', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  '0',
                    'values'=>array(
                        '0' => esc_html__( 'Disabled', 'super-forms' ),
                        '1' => esc_html__( 'Enabled', 'super-forms' ),
                    ),
                ),
                'csrf_check' => array(
                    'hidden' => true,
                    'name' => esc_html__( 'Cross-Site Request Forgery (CSRF) check', 'super-forms' ),
                    'label' => esc_html__( 'If you are loading forms through iframes that have a different origin you will require to disable the CSRF check in order to be able to submit forms. This is not recommended. Only use this if you have no other solution.', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  'true',
                    'values'=>array(
                        'true' => esc_html__( 'Enabled (recommended)', 'super-forms' ),
                        'false' => esc_html__( 'Disabled (not recommended)', 'super-forms' )
                    ),
                ),
                'allow_storing_cookies' => array(
                    'hidden' => true,
                    'name' => esc_html__( 'Allow storing cookies', 'super-forms' ),
                    'label' => esc_html__( 'If your site runs a caching system that doesn\'t allow for cookies to be used e.g Varnish Cache or NGINX caching engines you can enable this option to disable the cookie from being stored. Note that this will break some functionalities within the plugin that require cookies. For instance the functionality to "Save form progression" will not work when this option is enabled.', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  '1',
                    'values'=>array(
                        '1' => esc_html__( 'Enabled (recommended)', 'super-forms' ),
                        '0' => esc_html__( 'Disabled (not recommended)', 'super-forms' )
                    ),
                ),
                // reCAPTCHA v2
                'form_recaptcha' => array(
                    'hidden' => true,
                    'name' => '<a href="https://www.google.com/recaptcha/admin" target="_blank">reCAPTCHA v2 key</a>',
                    'default' =>  '',
                ),
                'form_recaptcha_secret' => array(
                    'hidden' => true,
                    'name' => '<a href="https://www.google.com/recaptcha/admin" target="_blank">reCAPTCHA v2 secret</a>',
                    'default' =>  '',
                ),

                // reCAPTCHA v3
                'form_recaptcha_v3' => array(
                    'hidden' => true,
                    'name' => '<a href="https://www.google.com/recaptcha/admin" target="_blank">reCAPTCHA v3 key</a>',
                    'default' =>  '',
                ),
                'form_recaptcha_v3_secret' => array(
                    'hidden' => true,
                    'name' => '<a href="https://www.google.com/recaptcha/admin" target="_blank">reCAPTCHA v3 secret</a>',
                    'default' =>  '',
                ),

                // Google Maps API
                'form_google_places_api' => array(
                    'hidden' => true,
                    'name' => '<a href="https://console.developers.google.com/" target="_blank">'.esc_html__( 'Google Maps API - Key', 'super-forms' ).'</a>',
                    'label' => esc_html__( 'The API key will be used for the Google Map element, Address Autocomplete and other features related to the Google Maps API', 'super-forms' ).'</a>',
                    'default' =>  '',
                ),
                'google_maps_api_language' => array(
                    'hidden' => true,
                    'name' => esc_html__( 'Google Maps API - Language', 'super-forms' ),
                    'label' => sprintf( esc_html__( 'The language to use. This affects the names of controls, copyright notices, driving directions, and control labels, as well as the responses to service requests. List of supported language codes: %sSupported Languages%s', 'super-forms' ), '<a href="https://developers.google.com/maps/faq?hl=nl#languagesupport">', '</a>'),
                    'default'=> ( !isset( $attributes['google_maps_api_language'] ) ? 'en' : $attributes['google_maps_api_language'] ),
                ),
                'google_maps_api_region' => array(
                    'hidden' => true,
                    'name' => esc_html__( 'Google Maps API - Region', 'super-forms' ),
                    'label' => esc_html__( 'The region code to use. This alters the map\'s behavior based on a given country or territory. The region parameter accepts Unicode region subtag identifiers which (generally) have a one-to-one mapping to country code Top-Level Domains (ccTLDs). Most Unicode region identifiers are identical to ISO 3166-1 codes, with some notable exceptions. For example, Great Britain\'s ccTLD is "uk" (corresponding to the domain .co.uk) while its region identifier is "GB".', 'super-forms' ),
                    'default'=> ( !isset( $attributes['google_maps_api_region'] ) ? '' : $attributes['google_maps_api_region'] ),
                ),
                
                // @since 2.2.0 - Custom form post method
                'form_post_option' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'filter' => true,
                    'values' => array(
                        'true' => esc_html__( 'Enable form POST method', 'super-forms' ),
                    )
                ),
                'form_post_url' => array(
                    'name' => esc_html__( 'Enter a custom form post URL', 'super-forms' ),
                    'default' =>  '',
                    'filter' => true,
                    'parent' => 'form_post_option',
                    'filter_value' => 'true'
                ),

                // @since 3.6.0 - Custom parameter string for POST method
                'form_post_custom' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable custom parameter string for POST method', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'form_post_option',
                    'filter_value' => 'true'
                ),
                'form_post_parameters' => array(
                    'name' => esc_html__( 'Enter custom parameter string', 'super-forms' ),
                    'label' => '<strong style="color:red;">' . esc_html__( 'Leave blank to send all form data', 'super-forms' ) . '</strong> ' . sprintf( esc_html__( 'You are allowed to use {tags}.%sPut each on a new line seperate parameter and value by pipes e.g:%sfirst_name|{first_name}', 'super-forms' ), '<br />', '<br />' ) . '<br />' . esc_html__( 'Instead of super forms sending all data vailable you can send a custom POST with custom parameters required', 'super-forms' ),
                    'placeholder' => "first_name|{first_name}\nlast_name|{last_name}",
                    'default' =>  '',
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent' => 'form_post_custom',
                    'filter_value' => 'true'
                ),
                'form_post_incl_dynamic_data' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Include dynamic data (enable this when using dynamic columns)', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'form_post_custom',
                    'filter_value' => 'true'  
                ),
                'form_post_json' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Send data as JSON string', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'form_post_custom',
                    'filter_value' => 'true'   
                ),
                'form_post_timeout' => array(
                    'name' => esc_html__( 'Post timeout in seconds', 'super-forms' ),
                    'label' => esc_html__( 'The default for this value is 5 seconds', 'super-forms' ) . '<br />' . esc_html__( 'The time in seconds, before the connection is dropped and an error is returned.', 'super-forms' ),
                    'default' =>  '5',
                    'filter'=>true,
                    'parent' => 'form_post_custom',
                    'filter_value' => 'true'
                ),
                'form_post_http_version' => array(
                    'name' => esc_html__( 'HTTP version', 'super-forms' ),
                    'label' => esc_html__( 'Depending on the service you are interacting with you may need to set this to 1.1', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  '1.0',
                    'values'=>array(
                        '1.0' => esc_html__( 'HTTP v1.0 (default)', 'super-forms' ),
                        '1.1' => esc_html__( 'HTTP v1.1', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'form_post_custom',
                    'filter_value' => 'true'
                ),
                'form_post_debug' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable debug mode (will output POST response for developers)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'form_post_custom',
                    'filter_value' => 'true'
                ),

                // @since 3.3.0 - Prevent submitting form on pressing "Enter" button
                'form_disable_enter' => array(
                    'desc' => esc_html__( 'Disable \'Enter\' keyboard button (preventing to submit form on pressing Enter)', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'filter' => true,
                    'values' => array(
                        'true' => esc_html__( 'Prevent submitting form on pressing "Enter" keyboard button', 'super-forms' ),
                    )
                ),
                'form_redirect_option' => array(
                    'name' => esc_html__( 'Form redirect option', 'super-forms' ),
                    'default' =>  '',
                    'filter' => true,
                    'type' => 'select',
                    'values' => array(
                        '' => esc_html__( 'No Redirect', 'super-forms' ),
                        'custom' => esc_html__( 'Custom URL', 'super-forms' ),
                        'page' => esc_html__( 'Existing Page', 'super-forms' ),
                    )
                ),
                'form_redirect' => array(
                    'name' => esc_html__('Enter a custom URL to redirect to', 'super-forms' ),
                    'default' =>  '',
                    'filter' => true,
                    'parent' => 'form_redirect_option',
                    'filter_value' => 'custom',
                    'i18n'=>true
                ),
                'form_redirect_page' => array(
                    'name' => 'Select a page to link to',
                    'default' =>  '',
                    'type' =>'select',
                    'values' => SUPER_Common::list_posts_by_type_array('page'),
                    'filter' => true,
                    'parent' => 'form_redirect_option',
                    'filter_value' => 'page'
                ),

                // @since 3.6.0 - google tracking
                'form_enable_ga_tracking' => array(
                    'name' => esc_html__( 'Track form submissions with Google Analytics', 'super-forms' ).'</a>',
                    'hidden' => true,
                    'default' =>  '',
                    'type' => 'checkbox',
                    'filter' => true,
                    'values' => array(
                        'true' => esc_html__( 'Enable Google Analytics Tracking', 'super-forms' ),
                    )
                ),
                'form_ga_code' => array(
                    'hidden' => true,
                    'name' => '<a href="https://developers.google.com/analytics/devguides/collection/analyticsjs/" target="_blank">'.esc_html__( 'Analytics.js tracking snippet', 'super-forms' ).'</a>',
                    'label' => esc_html__( 'Put the tracking code here and replace \'UA-XXXXX-Y\' with the property ID (also called the "tracking ID") of the Google Analytics property you wish to track.<br />(only add if you are sure this code hasn\'t been placed elsewhere yet, otherwise leave empty)', 'super-forms' ),
                    'default' =>  '',
                    'type'=>'textarea',
                    'filter' => true,
                    'parent' => 'form_enable_ga_tracking',
                    'filter_value' => 'true',
                    'placeholder' => "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\nm=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');\n\nga('create', 'UA-XXXXX-Y', 'auto');"
                ),
                'form_ga_tracking' => array(
                    'hidden' => true,
                    'name' => '<a href="https://developers.google.com/analytics/devguides/collection/analyticsjs/events" target="_blank">' . esc_html__( 'Tracking Events', 'super-forms' ) . '</a>',
                    'label' => sprintf( esc_html__( 'Put each tracking event on a new line, seperate parameters with pipes. You can also append a form ID to only trigger the event when that specific form was submitted. Examples:%1$s%1$s%2$sTo trigger for specific form only:%3$s%4$s2316:send|event|Signup Form|submit%5$s%2$sTo trigger for all forms:%3$s%4$ssend|event|Contact Form|submit%5$s%2$sExample with event Label and Value:%3$s%4$ssend|event|Campaign Form|submit|Fall Campaign|43%5$s', 'super-forms' ), '<br />', '<strong>', '</strong>', '<pre>', '</pre>' ),
                    'default' =>  '',
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent' => 'form_enable_ga_tracking',
                    'filter_value' => 'true',
                    'placeholder' => "6213:send|event|Signup Form|submit\n5349:send|event|Contact Form|submit"
                ),

                // @since 6.3.0 - Option to show/hide the progress bar for mult-parts
                'multipart_url_params' => array(
                    'desc' => esc_html__( 'Disable Multi-part current step parameter in the URL', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox', 
                    'values' => array(
                        'false' => esc_html__( 'Disable Multi-part current step parameter in the URL', 'super-forms' ),
                    ),
                ),

                // @since 2.0.0  - do not hide form after submitting
                'form_hide_after_submitting' => array(
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Hide form after submitting', 'super-forms' ),
                    )
                ),
                // @since 2.0.0  - reset / clear the form after submitting
                'form_clear_after_submitting' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Clear / reset the form after submitting', 'super-forms' ),
                    )
                ),

            )
        );
        $array = apply_filters( 'super_settings_after_form_settings_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *  Form Locker - Lock form after specific amount of submissions (based on total contact entries created)
         *
         *  @since      3.4.0
        */
        $array['form_locker'] = array(        
            'name' => esc_html__( 'Global Form locker / submission limit', 'super-forms' ),
            'label' => esc_html__( 'Global Form locker / submission limit', 'super-forms' ),
            'fields' => array(    
                'form_locker' => array(
                    'name' => esc_html__( 'Lock form after specific amount of submissions', 'super-forms' ),
                    'label' => esc_html__( 'Note: this will only work if contact entries are being saved', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable', 'super-forms' ),
                    ),
                    'filter'=>true
                ),
                'form_locker_limit' => array(
                    'name' => esc_html__( 'Set the limitation thresshold', 'super-forms' ),
                    'label' => esc_html__( 'Example: if you want to limit the form to 50 submissions in total, set this option to "50"', 'super-forms' ),
                    'default' =>  10 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>100,
                    'steps'=>1,
                    'filter'=>true,
                    'parent' => 'form_locker',
                    'filter_value' => 'true'
                ),
                'form_locker_msg' => array(
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Display an error message when form is locked', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'form_locker',
                    'filter_value' => 'true'
                ),
                'form_locker_msg_title' => array(
                    'name' => esc_html__( 'Lock message title', 'super-forms' ),
                    'default' =>  esc_html__( 'Please note:', 'super-forms'  ),
                    'filter'=>true,
                    'parent' => 'form_locker_msg',
                    'filter_value' => 'true',
                    'i18n'=>true
                ),
                'form_locker_msg_desc' => array(
                    'name' => esc_html__( 'Lock message description', 'super-forms' ),
                    'default' =>  esc_html__( 'This form is no longer available', 'super-forms'  ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent' => 'form_locker_msg',
                    'filter_value' => 'true',
                    'i18n'=>true
                ),
                'form_locker_hide' => array(
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Hide form when locked', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'form_locker',
                    'filter_value' => 'true'
                ),
                'form_locker_reset' => array(
                    'name' => esc_html__( 'Select when to reset the form lock', 'super-forms' ),
                    'label' => esc_html__( 'Select None to never reset the lock', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  '',
                    'values'=>array(
                        '' => esc_html__( 'Never (do not reset)', 'super-forms' ),
                        'daily' => esc_html__( 'Daily (every day)', 'super-forms' ),
                        'weekly' => esc_html__( 'Weekly (every week)', 'super-forms' ),
                        'monthly' => esc_html__( 'Monthly (every month)', 'super-forms' ),
                        'yearly' => esc_html__( 'Yearly (every year)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'form_locker',
                    'filter_value' => 'true'
                ),
                'form_locker_submission_reset' => array(
                    'name' => esc_html__( 'Reset locker submission counter to:', 'super-forms' ),
                    'default' => $submission_count,
                    'type'=>'reset_submission_count',
                    'filter'=>true,
                    'parent' => 'form_locker',
                    'filter_value' => 'true'
                ),
            )
        );
        $array = apply_filters( 'super_settings_after_form_locker_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *  User Form Locker - Lock form after specific amount of submissions (based on total contact entries created)
         *
         *  @since      3.8.0
        */
        $array['user_form_locker'] = array(        
            'name' => esc_html__( 'User Form locker / submission limit', 'super-forms' ),
            'label' => esc_html__( 'User Form locker / submission limit', 'super-forms' ),
            'fields' => array(    
                'user_form_locker' => array(
                    'name' => esc_html__( 'Lock form after specific amount of submissions by user', 'super-forms' ),
                    'label' => esc_html__( 'Note: this will only work for logged in users', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable user form lock / submission limit', 'super-forms' ),
                    ),
                    'filter'=>true
                ),
                'user_form_locker_limit' => array(
                    'name' => esc_html__( 'Set the limitation thresshold per user', 'super-forms' ),
                    'label' => esc_html__( 'Example: if you want to limit 2 submissions per user set this to "2"', 'super-forms' ),
                    'default' =>  10 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>100,
                    'steps'=>1,
                    'filter'=>true,
                    'parent' => 'user_form_locker',
                    'filter_value' => 'true'
                ), 
                'user_form_locker_msg' => array(
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Display an error message when form is locked', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'user_form_locker',
                    'filter_value' => 'true'
                ),
                'user_form_locker_msg_title' => array(
                    'name' => esc_html__( 'Lock message title', 'super-forms' ),
                    'default' =>  esc_html__( 'Please note:', 'super-forms'  ),
                    'filter'=>true,
                    'parent' => 'user_form_locker_msg',
                    'filter_value' => 'true',
                    'i18n'=>true                    
                ),
                'user_form_locker_msg_desc' => array(
                    'name' => esc_html__( 'Lock message description', 'super-forms' ),
                    'default' =>  esc_html__( 'This form is no longer available', 'super-forms'  ),
                    'type'=>'textarea',
                    'filter'=>true,
                    'parent' => 'user_form_locker_msg',
                    'filter_value' => 'true',
                    'i18n'=>true                    
                ),
                'user_form_locker_hide' => array(
                    'default' =>  'true',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Hide form when locked', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'user_form_locker',
                    'filter_value' => 'true'
                ),
                'user_form_locker_reset' => array(
                    'name' => esc_html__( 'Select when to reset the form lock', 'super-forms' ),
                    'label' => esc_html__( 'Select None to never reset the lock', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  '',
                    'values'=>array(
                        '' => esc_html__( 'Never (do not reset)', 'super-forms' ),
                        'daily' => esc_html__( 'Daily (every day)', 'super-forms' ),
                        'weekly' => esc_html__( 'Weekly (every week)', 'super-forms' ),
                        'monthly' => esc_html__( 'Monthly (every month)', 'super-forms' ),
                        'yearly' => esc_html__( 'Yearly (every year)', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent' => 'user_form_locker',
                    'filter_value' => 'true'
                ),
                'user_form_locker_submission_reset' => array(
                    'name' => esc_html__( 'Reset locker submission counter for all users:', 'super-forms' ),
                    'default' => $submission_count,
                    'type'=>'reset_user_submission_count',
                    'filter'=>true,
                    'parent' => 'user_form_locker',
                    'filter_value' => 'true'
                ),
            )
        );
        $array = apply_filters( 'super_settings_after_form_locker_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *	Theme & Colors
         *
         *	@since		1.0.0
        */
        $array['theme_colors'] = array(        
            'name' => esc_html__( 'Theme & colors', 'super-forms' ),
            'label' => esc_html__( 'Theme & colors', 'super-forms' ),
            'fields' => array(        
                'theme_style' => array(
                    'name' => esc_html__( 'Theme style', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  '',
                    'values'=>array(
                        '' => esc_html__( 'Default Squared', 'super-forms' ),
                        'super-default-rounded' => esc_html__( 'Default Rounded', 'super-forms' ),
                        'super-full-rounded' => esc_html__( 'Full Rounded', 'super-forms' ),
                        'super-style-one' => esc_html__( 'Minimal', 'super-forms' ),
                    ),
                    
                ),
                
                // @since 2.9.0 - field size in height
                'theme_field_size' => array(
                    'name' => esc_html__( 'Field size in height', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  'medium',
                    'values'=>array(
                        'medium' => esc_html__( 'Medium (default)', 'super-forms' ),
                        'large' => esc_html__( 'Large', 'super-forms' ),
                        'huge' => esc_html__( 'Huge', 'super-forms' ),
                    ),
                ),

                'theme_hide_icons' => array(
                    'name' => esc_html__( 'Hide field icons', 'super-forms' ),
                    'type'=>'select',
                    'default' =>  'yes',
                    'values'=>array(
                        'yes' => esc_html__( 'Yes (hide)', 'super-forms' ),
                        'no' => esc_html__( 'No (show)', 'super-forms' ),
                    ),
                    'filter'=>true
                ),

                // @since 1.2.8  - RTL support
                'theme_rtl' => array(
                    'default' =>  '',
                    'type' => 'checkbox',
                    'values' => array(
                        'true' => esc_html__( 'Enable RTL (Right To Left layout)', 'super-forms' ),
                    )
                ),

                'theme_placeholder_colors' => array(
                    'name' => esc_html__('Placeholder Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_colors_placeholder'=>array(
                            'label'=>'Placeholder color',
                            'default' =>  '#9a9a9a',
                        ),
                        'adaptive_placeholder_focus'=>array(
                            'label'=> esc_html__( 'Focussed font color', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                    ),
                ),

                // @since 4.9.3 - Adaptive Placeholders
                'enable_adaptive_placeholders' => array(
                    // allow empty / allow_empty
                    'default' =>  'true',
                    'type' => 'checkbox', 
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Enable Adaptive Placeholders', 'super-forms' ),
                    ),
                ),
                'placeholder_adaptive_positioning' => array(
                    'desc' => esc_html__( 'When enabled the placeholder will always be at it\'s adaptive position, even when the field is not focussed', 'super-forms' ),
                    // allow empty / allow_empty
                    'default' =>  '',
                    'type' => 'checkbox', 
                    'values' => array(
                        'true' => esc_html__( 'Use the adaptive positioning by default', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'enable_adaptive_placeholders',
                    'filter_value'=>'true',
                ),

                'theme_adaptive_placeholder_colors' => array(
                    'name' => esc_html__('Adaptive Placeholder Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'filter'=>true,
                    'parent'=>'enable_adaptive_placeholders',
                    'filter_value'=>'true',
                    'colors'=>array(
                        // Font color
                        'adaptive_placeholder_filled'=>array(
                            'label'=> esc_html__( 'Filled font color', 'super-forms' ),
                            'default' =>  '#9a9a9a',
                        ),
                        // Focussed background color
                        'adaptive_placeholder_bg_top_focus'=>array(
                            'label'=> esc_html__( 'Focussed top background color', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'adaptive_placeholder_bg_bottom_focus'=>array(
                            'label'=> esc_html__( 'Focussed bottom background color', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        // Filled background color
                        'adaptive_placeholder_bg_top_filled'=>array(
                            'label'=> esc_html__( 'Filled top background color', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'adaptive_placeholder_bg_bottom_filled'=>array(
                            'label'=> esc_html__( 'Filled bottom background color', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        // Border color
                        'adaptive_placeholder_border_focus'=>array(
                            'label'=> esc_html__( 'Focussed border color (leave blank for no border)', 'super-forms' ),
                            'default' =>  '',
                        ),
                        'adaptive_placeholder_border_filled'=>array(
                            'label'=> esc_html__( 'Filled border color (leave blank for no border)', 'super-forms' ),
                            'default' =>  '',
                        )
                    ),
                ),

                // @since 3.6.0 - option to center the form
                'theme_center_form' => array(
                    // allow empty / allow_empty
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Center the form', 'super-forms' ),
                    ),
                    'type' => 'checkbox'
                ),
                'theme_max_width' => array(
                    'name' => esc_html__( 'Form Maximum Width', 'super-forms' ),
                    'label' => esc_html__( '(0 = disabled)', 'super-forms' ),
                    'default' =>  0 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>1000,
                    'steps'=>10,
                ),
                // @since 1.3
                'theme_form_margin' => array(
                    'name' => esc_html__( 'Form Margins example: 0px 0px 0px 0px', 'super-forms' ),
                    'label' => esc_html__( '(top right bottom left)', 'super-forms' ),
                    'default' =>  '0px 0px 0px 0px',
                    'type'=>'text',
                ),




                'theme_icon_colors' => array(
                    'name' => esc_html__('Icon Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_icon_color'=>array(
                            'label'=>'Icon color',
                            'default' =>  '#B3DBDD',
                        ),
                        'theme_icon_color_focus'=>array(
                            'label'=>'Icon color focus',
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_icon_bg'=>array(
                            'label'=>'Icon background',
                            'default' =>  '#ffffff',
                        ),
                        'theme_icon_bg_focus'=>array(
                            'label'=>'Icon background focus',
                            'default' =>  '#ffffff',
                        ),
                        'theme_icon_border'=>array(
                            'label'=>'Icon border color',
                            'default' =>  '#cdcdcd',
                        ),
                        'theme_icon_border_focus'=>array(
                            'label'=>'Icon border color focus',
                            'default' =>  '#4EB1B6',
                        ),                            
                    ),
                    'filter'=>true,
                    'parent'=>'theme_hide_icons',
                    'filter_value'=>'no',
                ),
                'theme_ui_loading_icon' => array(
                    'name' => esc_html__( 'Form loading icon (preloader)', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_loading_icon_font'=>array(
                            'label'=>esc_html__( 'Spinning icon color', 'super-forms' ),
                            'default' =>  '#c5c5c5',
                        ),
                    ),
                ),
                'theme_label_colors' => array(
                    'name' => esc_html__( 'Label & Description colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_label'=>array(
                            'label'=>esc_html__( 'Field label', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_field_description'=>array(
                            'label'=>esc_html__( 'Field description', 'super-forms' ),
                            'default' =>  '#8e8e8e',
                        ),
                    ),
                ),
                'theme_ui_checkbox_colors' => array(
                    'name' => esc_html__( 'Checkbox & Radio colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_checkbox_border'=>array(
                            'label'=>esc_html__( 'Check/Radio border', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_ui_checkbox_inner'=>array(
                            'label'=>esc_html__( 'Check/Radio inner', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_ui_checkbox_label'=>array(
                            'label'=>esc_html__( 'Check/Radio Labels', 'super-forms' ),
                            'default' =>  '#444444',
                        ),

                    ),
                ),
                'theme_ui_dropdown_item_bg_colors' => array(
                    'name' => esc_html__( 'Dropdown colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_dropdown_item_bg'=>array(
                            'label'=>esc_html__( 'Dropdown item background', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_ui_dropdown_item_font_focus'=>array(
                            'label'=>esc_html__( 'Dropdown item font focus', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_ui_dropdown_item_bg_focus'=>array(
                            'label'=>esc_html__( 'Dropdown item background focus', 'super-forms' ),
                            'default' =>  '#1E90FF',
                        ),
                        'theme_ui_dropdown_arrow'=>array(
                            'label'=>esc_html__( 'Dropdown arrow', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                    ),
                ),
                'theme_ui_quantity_colors' => array(
                    'name' => esc_html__( 'Quantity button colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_quantity_bg'=>array(
                            'label'=>esc_html__( 'Button background', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_ui_quantity_font'=>array(
                            'label'=>esc_html__( 'Button font', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_ui_quantity_bg_hover'=>array(
                            'label'=>esc_html__( 'Button background hover', 'super-forms' ),
                            'default' =>  '#7ed0d4',
                        ),
                        'theme_ui_quantity_font_hover'=>array(
                            'label'=>esc_html__( 'Button font hover', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                    ),
                ),

                // @since 2.9.0 - toggle button
                'theme_ui_toggle_colors' => array(
                    'name' => esc_html__( 'Toggle button colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_toggle_bg'=>array(
                            'label'=>esc_html__( 'Toggle button background (on)', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_ui_toggle_font'=>array(
                            'label'=>esc_html__( 'Toggle button font (on)', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_ui_toggle_disabled_bg'=>array(
                            'label'=>esc_html__( 'Toggle button background (off)', 'super-forms' ),
                            'default' =>  '#e4e4e4',
                        ),
                        'theme_ui_toggle_disabled_font'=>array(
                            'label'=>esc_html__( 'Toggle button font (off)', 'super-forms' ),
                            'default' =>  '#9c9c9c',
                        ),
                    ),
                ),

                // @since 2.9.0 - keywords field
                'theme_ui_keywords_colors' => array(
                    'name' => esc_html__( 'Keywords/Tags colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_keywords_bg'=>array(
                            'label'=>esc_html__( 'Keyword/Tag background', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_ui_keywords_font'=>array(
                            'label'=>esc_html__( 'Keyword/Tag font', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_ui_keywords_icon'=>array(
                            'label'=>esc_html__( 'Keyword/Tag delete icon', 'super-forms' ),
                            'default' =>  '#2e8a90',
                        ),
                        'theme_ui_keywords_icon_hover'=>array(
                            'label'=>esc_html__( 'Keyword/Tag delete icon hover', 'super-forms' ),
                            'default' =>  '#246569',
                        ),
                        'theme_ui_tags_list_bg_hover'=>array(
                            'label'=>esc_html__( 'Keyword/Tag list background hover', 'super-forms' ),
                            'default' =>  '#fdecde',
                        ),
                    ),
                ),

                'theme_ui_slider_colors' => array(
                    'name' => esc_html__( 'Slider colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_ui_slider_dragger'=>array(
                            'label'=>esc_html__( 'Dragger color', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_ui_slider_track'=>array(
                            'label'=>esc_html__( 'Track color', 'super-forms' ),
                            'default' =>  '#CDCDCD',
                        ),
                    ),
                ),
                'theme_field_colors' => array(
                    'name' => esc_html__('Field Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_colors_top'=>array(
                            'label'=>esc_html__( 'Gradient Top', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_field_colors_bottom'=>array(
                            'label'=>esc_html__( 'Gradient Bottom', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_field_colors_border'=>array(
                            'label'=>esc_html__( 'Border Color', 'super-forms' ),
                            'default' =>  '#cdcdcd',
                        ),
                        'theme_field_colors_font'=>array(
                            'label'=>esc_html__( 'Font Color', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                    ),
                ),

                'theme_field_colors_focus' => array(
                    'name' => esc_html__('Field Colors Focus', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_field_colors_top_focus'=>array(
                            'label'=>esc_html__( 'Gradient Top Focus', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_field_colors_bottom_focus'=>array(
                            'label'=>esc_html__( 'Gradient Bottom Focus', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_field_colors_border_focus'=>array(
                            'label'=>esc_html__( 'Border Color Focus', 'super-forms' ),
                            'default' =>  '#4EB1B6',
                        ),
                        'theme_field_colors_font_focus'=>array(
                            'label'=>esc_html__( 'Font Color Focus', 'super-forms' ),
                            'default' =>  '#444444',
                        ),                                               
                    ),
                ),
                'theme_field_transparent' => array(
                    'desc' => esc_html__( 'Allows you to set the field background to transparent', 'super-forms' ), 
                    // allow empty / allow_empty
                    'default' =>  '',
                    'type' => 'checkbox', 
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Enable transparent backgrounds', 'super-forms' ),
                    ),
                ),
                'theme_rating_colors' => array(
                    'name' => esc_html__('Rating Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_rating_color'=>array(
                            'label'=>esc_html__('Rating color', 'super-forms' ),
                            'default' =>  '#cdcdcd',
                        ),
                        'theme_rating_bg'=>array(
                            'label'=>esc_html__('Rating background', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_rating_border'=>array(
                            'label'=>esc_html__('Rating border color', 'super-forms' ),
                            'default' =>  '#cdcdcd',
                        ),
                    ),
                ),                    
                'theme_rating_colors_hover' => array(
                    'name' => esc_html__('Rating Colors Hover', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_rating_color_hover'=>array(
                            'label'=>esc_html__( 'Rating color', 'super-forms' ),
                            'default' =>  '#ffc800',
                        ),
                        'theme_rating_bg_hover'=>array(
                            'label'=>esc_html__( 'Rating background', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                    ),
                ),
                'theme_rating_colors_active' => array(
                    'name' => esc_html__('Rating Colors Active', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_rating_color_active'=>array(
                            'label'=>esc_html__( 'Rating color', 'super-forms' ),
                            'default' =>  '#f7ea00',
                        ),
                        'theme_rating_bg_active'=>array(
                            'label'=>esc_html__( 'Rating background', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                    ),
                ),
                // Accordion colors
                'theme_accordion_colors' => array(
                    'name' => esc_html__('Accordion Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_accordion_header'=>array(
                            'label'=>esc_html__( 'Accordion Header', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_accordion_title'=>array(
                            'label'=>esc_html__( 'Accordion Title', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_accordion_desc'=>array(
                            'label'=>esc_html__( 'Accordion Desc', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_accordion_icon'=>array(
                            'label'=>esc_html__( 'Accordion Icon', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_accordion_content'=>array(
                            'label'=>esc_html__( 'Accordion Content', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),

                        'theme_accordion_header_hover'=>array(
                            'label'=>esc_html__( 'Accordion Header (hover)', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_accordion_title_hover'=>array(
                            'label'=>esc_html__( 'Accordion Title (hover)', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_accordion_desc_hover'=>array(
                            'label'=>esc_html__( 'Accordion Desc (hover)', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_accordion_icon_hover'=>array(
                            'label'=>esc_html__( 'Accordion Icon (hover)', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        
                        'theme_accordion_header_active'=>array(
                            'label'=>esc_html__( 'Accordion Header (active)', 'super-forms' ),
                            'default' =>  '#ffffff',
                        ),
                        'theme_accordion_title_active'=>array(
                            'label'=>esc_html__( 'Accordion Title (active)', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_accordion_desc_active'=>array(
                            'label'=>esc_html__( 'Accordion Desc (active)', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_accordion_icon_active'=>array(
                            'label'=>esc_html__( 'Accordion Icon (active)', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                    ),
                ),                    

                // Calculator colors
                'theme_calc_colors' => array(
                    'name' => esc_html__('Calculator Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_calc_amount_label_color'=>array(
                            'label'=>esc_html__( 'Calculator Amount Label Color', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_calc_amount_currency_color'=>array(
                            'label'=>esc_html__( 'Calculator Amount Currency Color', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_calc_amount_color'=>array(
                            'label'=>esc_html__( 'Calculator Amount Color', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_calc_amount_format_color'=>array(
                            'label'=>esc_html__( 'Calculator Amount Format Color', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                    ),
                ),                    
                
                // Tooltip colors
                'theme_tooltip_colors' => array(
                    'name' => esc_html__('Tooltip Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_tooltip_border'=>array(
                            'label'=>esc_html__( 'Tooltip Border', 'super-forms' ),
                            'default' =>  '#000000',
                        ),
                        'theme_tooltip_font'=>array(
                            'label'=>esc_html__( 'Tooltip Font', 'super-forms' ),
                            'default' =>  '#FFFFFF',
                        ),
                        'theme_tooltip_bg'=>array(
                            'label'=>esc_html__( 'Tooltip Background', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                        'theme_tooltip_arrow_border'=>array(
                            'label'=>esc_html__( 'Tooltip Arrow Border', 'super-forms' ),
                            'default' =>  '#000000',
                        ),
                        'theme_tooltip_arrow_bg'=>array(
                            'label'=>esc_html__( 'Tooltip Arrow Background', 'super-forms' ),
                            'default' =>  '#444444',
                        ),
                    ),
                ),                    

                // @since 3.3.0 - Option to show/hide the progress bar for mult-parts
                'theme_multipart_progress_bar' => array(
                    'desc' => esc_html__( 'Enable this if you want to show the progress bar for Multi-part', 'super-forms' ), 
                    // allow empty / allow_empty
                    'default' =>  'true',
                    'type' => 'checkbox', 
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Show progress bar for Multi-part', 'super-forms' ),
                    ),
                ),

                'theme_progress_bar_colors' => array(
                    'name' => esc_html__('Progress Bar Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_progress_bar_primary_color'=>array(
                            'label'=>'Primary color',
                            'default' =>  '#87CC83',
                        ),
                        'theme_progress_bar_secondary_color'=>array(
                            'label'=>'Secondary color',
                            'default' =>  '#E2E2E2',
                        ),
                        'theme_progress_bar_border_color'=>array(
                            'label'=>'Border color',
                            'default' =>  '#CECECE',
                        ),
                    ),
                    'filter'=>true,
                    'parent'=>'theme_multipart_progress_bar',
                    'filter_value'=>'true',
                ),

                // @since 3.3.0 - Option to show/hide the progress bar for mult-parts
                'theme_multipart_steps' => array(
                    'desc' => esc_html__( 'Enable this if you want to show the steps for Multi-part', 'super-forms' ), 
                    // allow empty / allow_empty
                    'default' =>  'true',
                    'type' => 'checkbox', 
                    'filter'=>true,
                    'values' => array(
                        'true' => esc_html__( 'Show steps for Multi-part', 'super-forms' ),
                    ),
                ),
                // @since 4.6.0 - option to hide steps on mobile devices
                'theme_multipart_steps_hide_mobile' => array(
                    'desc' => esc_html__( 'Enable this if you want to hide the steps on mobile devices', 'super-forms' ), 
                    // allow empty / allow_empty
                    'default' =>  'true',
                    'type' => 'checkbox', 
                    'values' => array(
                        'true' => esc_html__( 'Hide steps on mobile devices', 'super-forms' ),
                    ),
                    'filter'=>true,
                    'parent'=>'theme_multipart_steps',
                    'filter_value'=>'true',
                ),

                'theme_progress_step_colors' => array(
                    'name' => esc_html__('Progress Step Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_progress_step_primary_color'=>array(
                            'label'=>'Primary color',
                            'default' =>  '#CECECE',
                        ),
                        'theme_progress_step_secondary_color'=>array(
                            'label'=>'Secondary color',
                            'default' =>  '#E2E2E2',
                        ),
                        'theme_progress_step_border_color'=>array(
                            'label'=>'Border color',
                            'default' =>  '#CECECE',
                        ),
                        'theme_progress_step_font_color'=>array(
                            'label'=>'Font color',
                            'default' =>  '#FFFFFF',
                        ),                                
                    ),
                    'filter'=>true,
                    'parent'=>'theme_multipart_steps',
                    'filter_value'=>'true',                    
                ),
                'theme_progress_step_colors_active' => array(
                    'name' => esc_html__('Progress Step Colors Active', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_progress_step_primary_color_active'=>array(
                            'label'=>'Primary color',
                            'default' =>  '#87CC83',
                        ),
                        'theme_progress_step_secondary_color_active'=>array(
                            'label'=>'Secondary color',
                            'default' =>  '#E2E2E2',
                        ),
                        'theme_progress_step_border_color_active'=>array(
                            'label'=>'Border color',
                            'default' =>  '#CECECE',
                        ),
                        'theme_progress_step_font_color_active'=>array(
                            'label'=>'Font color',
                            'default' =>  '#FFFFFF',
                        ),                                
                    ),
                    'filter'=>true,
                    'parent'=>'theme_multipart_steps',
                    'filter_value'=>'true',
                ),
                'theme_error' => array(
                    'name' => esc_html__('Error Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_error_font'=>array(
                            'label'=>'Font Color',
                            'default' =>  '#f2322b',
                        ),                     
                    ),
                ),              


                /** 
                 *  Error & Success message colors
                 *
                 *  @since      1.0.6
                */
                'theme_error_msg' => array(
                    'name' => esc_html__('Error Message Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_error_msg_font_color'=>array(
                            'label'=>'Font color',
                            'default' =>  '#D08080',
                        ),
                        'theme_error_msg_border_color'=>array(
                            'label'=>'Border color',
                            'default' =>  '#FFCBCB',
                        ),
                        'theme_error_msg_bg_color'=>array(
                            'label'=>'Background color',
                            'default' =>  '#FFEBEB',
                        ),
                        'theme_error_msg_icon_color'=>array(
                            'label'=>'Icon color',
                            'default' =>  '#FF9A9A',
                        ),
                    ),
                ),
                'theme_success_msg' => array(
                    'name' => esc_html__('Success Message Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_success_msg_font_color'=>array(
                            'label'=>'Font color',
                            'default' =>  '#5E7F62',
                        ),
                        'theme_success_msg_border_color'=>array(
                            'label'=>'Border color',
                            'default' =>  '#90C397',
                        ),
                        'theme_success_msg_bg_color'=>array(
                            'label'=>'Background color',
                            'default' =>  '#C5FFCD',
                        ),
                        'theme_success_msg_icon_color'=>array(
                            'label'=>'Icon color',
                            'default' =>  '#90C397',
                        ),
                    ),
                ),

                // @since 2.0.0
                'theme_success_msg_margin' => array(
                    'name' => esc_html__( 'Thanks margins in px (top right bottom left)', 'super-forms' ),
                    'label' => esc_html__( 'A custom thank you description shown after a user completed the form.', 'super-forms' ),
                    'default' =>  '0px 0px 30px 0px',
                )
            )
        );
        $array = apply_filters( 'super_settings_after_theme_colors_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        /** 
         *  Font families
         *
         *  @since      2.9.0
        */
        $array['font_family'] = array(
            'name' => esc_html__( 'Font family', 'super-forms' ),
            'label' => esc_html__( 'Font family', 'super-forms' ),
            'fields' => array(
                'font_google_fonts' => array(
                    'name' => esc_html__( 'Import fonts via URL (put each on a new line)', 'super-forms' ),
                    'label' => sprintf( esc_html__( 'Click %shere%s to search for google fonts%sCopy past the URL e.g:%shttps://fonts.googleapis.com/css?family=Raleway', 'super-forms' ), '<a target="_blank" href="https://fonts.google.com/">', '</a>', '<br />', '<br />' ),
                    'default' =>  '',
                    'type' => 'textarea',
                ),
                'font_global_family' => array(
                    'name' => esc_html__( 'Global font family', 'super-forms' ),
                    'label' => esc_html__( 'To use for example Raleway google font you can enter: \'Raleway\', sans-serif', 'super-forms' ),
                    'default' =>  '"Helvetica", "Arial", sans-serif',
                ),
            )
        );

        /** 
         *  Font styles
         *
         *  @since      2.9.0
        */
        $array['font_styles'] = array(
            'name' => esc_html__( 'Font styles', 'super-forms' ),
            'label' => esc_html__( 'Font styles', 'super-forms' ),
            'fields' => array(
                // Globals
                'font_global_size' => array(
                    'name' => esc_html__( 'Global font size', 'super-forms' ),
                    'label' => esc_html__( '(12 = default)', 'super-forms' ),
                    'default' =>  12 ,
                    'type'=>'slider',
                    'min'=>8,
                    'max'=>50,
                    'steps'=>1,
                ),
                'font_global_lineheight' => array(
                    'name' => esc_html__( 'Global line height', 'super-forms' ),
                    'label' => esc_html__( '(default line height is 1.2)', 'super-forms' ),
                    'default' =>  1.2 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>5,
                    'steps'=>0.01,
                ),
                'font_global_weight' => array(
                    'name' => esc_html__( 'Global font weight', 'super-forms' ),
                    'label' => esc_html__( '(set to 0 to use normal font weight)', 'super-forms' ),
                    'default' =>  0 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>900,
                    'steps'=>100,
                ),
                // Labels
                'font_label_size' => array(
                    'name' => esc_html__( 'Field label font size', 'super-forms' ),
                    'label' => esc_html__( '(16 = default)', 'super-forms' ),
                    'default' =>  16 ,
                    'type'=>'slider',
                    'min'=>8,
                    'max'=>50,
                    'steps'=>1,
                ),
                'font_label_lineheight' => array(
                    'name' => esc_html__( 'Field label line height', 'super-forms' ),
                    'label' => esc_html__( '(default line height is 1.2)', 'super-forms' ),
                    'default' =>  1.2 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>5,
                    'steps'=>0.01,
                ),
                'font_label_weight' => array(
                    'name' => esc_html__( 'Field label font weight', 'super-forms' ),
                    'label' => esc_html__( '(set to 0 to use normal font weight)', 'super-forms' ),
                    'default' =>  0 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>900,
                    'steps'=>100,
                ),
                // Descriptions
                'font_description_size' => array(
                    'name' => esc_html__( 'Field description font size', 'super-forms' ),
                    'label' => esc_html__( '(14 = default)', 'super-forms' ),
                    'default' =>  14 ,
                    'type'=>'slider',
                    'min'=>8,
                    'max'=>50,
                    'steps'=>1,
                ),
                'font_description_lineheight' => array(
                    'name' => esc_html__( 'Field description line height', 'super-forms' ),
                    'label' => esc_html__( '(default line height is 1.2)', 'super-forms' ),
                    'default' =>  1.2 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>5,
                    'steps'=>0.01,
                ),
                'font_description_weight' => array(
                    'name' => esc_html__( 'Field description font weight', 'super-forms' ),
                    'label' => esc_html__( '(set to 0 to use normal font weight)', 'super-forms' ),
                    'default' =>  0 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>900,
                    'steps'=>100,
                ),
            )
        );
        /** 
         *  Font styles mobile/responsiveness
         *
         *  @since      4.9.52
        */
        $array['font_styles_mobile'] = array(
            'name' => esc_html__( 'Font styles (mobile/responsive)', 'super-forms' ),
            'label' => esc_html__( 'Font styles (mobile/responsive)', 'super-forms' ),
            'fields' => array(
                // Globals
                'font_global_size_mobile' => array(
                    'name' => esc_html__( 'Global font size', 'super-forms' ),
                    'label' => esc_html__( '(16 = default)', 'super-forms' ),
                    'default' =>  16 ,
                    'type'=>'slider',
                    'min'=>8,
                    'max'=>50,
                    'steps'=>1,
                ),
                'font_global_lineheight_mobile' => array(
                    'name' => esc_html__( 'Global line height', 'super-forms' ),
                    'label' => esc_html__( '(default line height is 1.2)', 'super-forms' ),
                    'default' =>  1.2 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>5,
                    'steps'=>0.01,
                ),
                'font_global_weight_mobile' => array(
                    'name' => esc_html__( 'Global font weight', 'super-forms' ),
                    'label' => esc_html__( '(set to 0 to use normal font weight)', 'super-forms' ),
                    'default' =>  0 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>900,
                    'steps'=>100,
                ),
                // Labels
                'font_label_size_mobile' => array(
                    'name' => esc_html__( 'Field label font size', 'super-forms' ),
                    'label' => esc_html__( '(20 = default)', 'super-forms' ),
                    'default' =>  20 ,
                    'type'=>'slider',
                    'min'=>8,
                    'max'=>50,
                    'steps'=>1,
                ),
                'font_label_lineheight_mobile' => array(
                    'name' => esc_html__( 'Field label line height', 'super-forms' ),
                    'label' => esc_html__( '(default line height is 1.2)', 'super-forms' ),
                    'default' =>  1.2 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>5,
                    'steps'=>0.01,
                ),
                'font_label_weight_mobile' => array(
                    'name' => esc_html__( 'Field label font weight', 'super-forms' ),
                    'label' => esc_html__( '(set to 0 to use normal font weight)', 'super-forms' ),
                    'default' =>  0 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>900,
                    'steps'=>100,
                ),
                // Descriptions
                'font_description_size_mobile' => array(
                    'name' => esc_html__( 'Field description font size', 'super-forms' ),
                    'label' => esc_html__( '(16 = default)', 'super-forms' ),
                    'default' =>  16 ,
                    'type'=>'slider',
                    'min'=>8,
                    'max'=>50,
                    'steps'=>1,
                ),
                'font_description_lineheight_mobile' => array(
                    'name' => esc_html__( 'Field description line height', 'super-forms' ),
                    'label' => esc_html__( '(default line height is 1.2)', 'super-forms' ),
                    'default' =>  1.2 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>5,
                    'steps'=>0.01,
                ),
                'font_description_weight_mobile' => array(
                    'name' => esc_html__( 'Field description font weight', 'super-forms' ),
                    'label' => esc_html__( '(set to 0 to use normal font weight)', 'super-forms' ),
                    'default' =>  0 ,
                    'type'=>'slider',
                    'min'=>0,
                    'max'=>900,
                    'steps'=>100,
                ),
            )
        );
        $array = apply_filters( 'super_settings_after_font_styles_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *  Custom CSS
         *
         *  @since      1.2.8
        */
        $array['form_custom_css'] = array(        
            'hidden' => 'settings',
            'name' => esc_html__( 'Custom CSS', 'super-forms' ),
            'label' => esc_html__( 'Custom CSS', 'super-forms' ),
            'fields' => array(        
                'form_custom_css' => array(
                    'name' => esc_html__( 'Custom CSS', 'super-forms' ),
                    'type'=>'textarea',
                    'default' =>  '',
                ),
            )
        );
        $array = apply_filters( 'super_settings_after_form_custom_css_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        
        /** 
         *	Submit Button Settings
         *
         *	@since		1.0.0
        */
        $array['submit_button'] = array(        
            'name' => esc_html__( 'Submit button', 'super-forms' ),
            'label' => esc_html__( 'Submit button', 'super-forms' ),
            'fields' => array(        
                'form_button' => array(
                    'name' => esc_html__('Button name', 'super-forms' ),
                    'default' =>  esc_html__( 'Submit', 'super-forms'  ),
                    'i18n'=>true
                ),
                // @since 2.0.0
                'form_button_loading' => array(
                    'name' => esc_html__('Button loading name', 'super-forms' ),
                    'default' =>  esc_html__( 'Loading...', 'super-forms'  ),
                    'i18n'=>true
                ),

                'theme_button_colors' => array(
                    'name' => esc_html__('Button Colors', 'super-forms' ),
                    'type'=>'multicolor', 
                    'colors'=>array(
                        'theme_button_color'=>array(
                            'label'=>'Button background color',
                            'default' =>  '#f26c68',
                        ),
                        'theme_button_color_hover'=>array(
                            'label'=>'Button background color hover',
                            'default' =>  '#444444',
                        ),
                        'theme_button_font'=>array(
                            'label'=>'Button font color',
                            'default' =>  '#ffffff',
                        ),
                        'theme_button_font_hover'=>array(
                            'label'=>'Button font color hover',
                            'default' =>  '#ffffff',
                        ),                            
                    ),
                ),
                'form_button_radius' => array(
                    'name'=> esc_html__('Button radius', 'super-forms' ),
                    'default' =>  'square',
                    'type'=>'select',
                    'values'=>array(
                        'rounded'=>'Rounded',
                        'square'=>'Square',
                        'full-rounded'=>'Full Rounded',
                    )
                ),
                'form_button_type' => array(
                    'name'=> esc_html__('Button type', 'super-forms' ),
                    'default' =>  'flat',
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
                    'name'=> esc_html__('Button size', 'super-forms' ),
                    'default' =>  'medium',
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
                    )
                ),
                'form_button_align' => array(
                    'name'=> esc_html__('Button position', 'super-forms' ),
                    'default' =>  'left',
                    'type'=>'select', 
                    'values'=>array(
                        'left' => 'Align Left', 
                        'center' => 'Align Center', 
                        'right' => 'Align Right', 
                    )
                ), 
                'form_button_width' => array(
                    'name'=> esc_html__('Button width', 'super-forms' ),
                    'default' =>  'auto',
                    'type'=>'select', 
                    'values'=>array(
                        'auto' => 'Auto', 
                        'fullwidth' => 'Fullwidth', 
                    )
                ),         
                'form_button_icon_option' => array(
                    'name'=> esc_html__('Button icon position', 'super-forms' ),
                    'default' =>  'none',
                    'filter'=>true,
                    'type'=>'select', 
                    'values'=>array(
                        'none' => 'No icon', 
                        'left' => 'Left icon', 
                        'right' => 'Right icon', 
                    )
                ),
                'form_button_icon_visibility' => array(
                    'name'=> esc_html__('Button icon visibility', 'super-forms' ),
                    'default' =>  'visible',
                    'filter'=>true,
                    'parent'=>'form_button_icon_option',
                    'filter_value'=>'left,right',
                    'type'=>'select', 
                    'values'=>array(
                        'visible' => 'Always Visible', 
                        'hidden' => 'Visible on hover (mouseover)', 
                    )
                ),
                'form_button_icon_animation' => array(
                    'name'=> esc_html__('Button icon animation', 'super-forms' ),
                    'default' =>  'horizontal',
                    'filter'=>true,
                    'parent'=>'form_button_icon_visibility',
                    'filter_value'=>'hidden',
                    'type'=>'select', 
                    'values'=>array(
                        'horizontal' => 'Horizontal animation', 
                        'vertical' => 'Vertical animation', 
                    )
                ),                                
                'form_button_icon' => array(
                    'name'=> esc_html__('Button icon', 'super-forms' ),
                    'default' =>  '',
                    'type'=>'icon',
                    'filter'=>true,
                    'parent'=>'form_button_icon_option',
                    'filter_value'=>'left,right'
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
            'name' => esc_html__( 'Backend Settings', 'super-forms' ),
            'label' => esc_html__('Here you can change serveral settings that apply to your backend', 'super-forms' ),
            'fields' => array(
                'backend_contact_entry_list_fields' => array(
                    'name' => esc_html__('Columns for contact entries', 'super-forms' ),
                    'label' => sprintf( esc_html__('Put each on a new line.%1$sExample:%1$sfieldname|Field label%1$semail|Email%1$sphonenumber|Phonenumber', 'super-forms' ), '<br />' ),
                    'default' =>  "email|Email\nphonenumber|Phonenumber\nmessage|Message" ,
                    'type' => 'textarea', 
                ),

                // @since 3.4.0 - contact entry status
                'backend_contact_entry_status' => array(
                    'name' => esc_html__('Contact entry statuses', 'super-forms' ),
                    'label' => sprintf( esc_html__('Put each on a new line.%1$s%1$sFormat:%1$sname|label|bg_color|font_color%1$s%1$sExample:%1$spending|Pending|#808080|#FFFFFF%1$sprocessing|Processing|#808080|#FFFFFF%1$son_hold|On hold|#FF7700|#FFFFFF%1$saccepted|Accepted|#2BC300|#FFFFFF%1$scompleted|Completed|#2BC300|#FFFFFF%1$scancelled|Cancelled|#E40000|#FFFFFF%1$sdeclined|Declined|#E40000|#FFFFFF%1$srefunded|Refunded|#000000|#FFFFFF', 'super-forms' ), '<br />' ),
                    'default' =>  $backend_contact_entry_status ,
                    'type' => 'textarea', 
                ),

                // @since 1.2.9
                'backend_contact_entry_list_form' => array(
                    'name' => '&nbsp;',
                    'default' =>  'true',
                    'values' => array(
                        'true' => esc_html__('Add the form name to the contact entry list', 'super-forms' ),
                    ),
                    'type' => 'checkbox',
                ),
                // @since 3.1.0 - allow to display IP address to the contact entry column
                'backend_contact_entry_list_ip' => array(
                    'name' => '&nbsp;',
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Add the IP address to the contact entry list', 'super-forms' ),
                    ),
                    'type' => 'checkbox'
                ),
                'backend_disable_whats_new_notice' => array(
                    'name' => '&nbsp;',
                    'default' =>  '',
                    'values' => array(
                        'true' => esc_html__('Do not display an admin notice after updating the plugin', 'super-forms' ),
                    ),
                    'type' => 'checkbox'
                )
            ),
        );
        $array = apply_filters( 'super_settings_after_backend_settings_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );
        
        /** 
         *	WooCommerce /my-account menu items
         *
         *	@since		5.0.0
        */
        $array['wc_my_account_menu'] = array(
            'hidden' => true,
            'name' => esc_html__( 'WooCommerce My Account Menu Items', 'super-forms' ),
            'label' => esc_html__('Define custom WooCommerce "My Account" menu items', 'super-forms' ),
            'html' => array(
                '<p style="background-color: #ff3535;color: #ffffff;font-size: 14px;line-height: 16px;margin-bottom: 30px;border-radius: 5px;padding: 15px 25px 15px 25px;">',
                '<strong style="font-size:16px;">' . esc_html__( 'Please note:', 'super-forms' ) . '</strong> ',
                sprintf( esc_html__( 'In order to reflect any changes made to the below settings you must refresh your permalinks under %1$sSettings > Permalinks%2$s by clicking "Save Changes".', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'options-permalink.php') . '">', '</a>'),
                '</p>',
            ),
            'fields' => array(
                'wc_my_account_menu_items' => array(
                    'name' => esc_html__('Define menu items', 'super-forms' ),
                    'label' => sprintf( esc_html__('Put each on a new line formatted like this:%1$s%4$smenu-slug|Menu Title|Put your HTML or shortcode here...|Integer for menu item position (optional)|URL to custom page (optional)%5$s%1$s%2$sExample without custom page URL:%3$s%1$s%4$sform-submissions|Form Submissions|[super_listings list="1" id="54751"]|3%5$s%1$s%2$sExample with custom page URL:%3$s%1$s%4$sform-submissions|Form Submissions|[super_listings list="1" id="54751"]|3|https://domain.com/my-custom-page%5$s', 'super-forms' ), '<br />', '<strong>', '</strong>', '<div class="super-settings-code-snippet">', '</div>' ),
                    'default' =>  "" ,
                    'type' => 'textarea', 
                ),
            ),
        );
        $array = apply_filters( 'super_settings_after_wc_my_account_menu_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        /** 
         *  Custom CSS
         *
         *  @since      1.0.0
        */
        $array['custom_css'] = array(        
            'hidden' => true,
            'name' => esc_html__( 'Custom CSS', 'super-forms' ),
            'label' => esc_html__('Override the default CSS styles', 'super-forms' ),
            'fields' => array(
                'theme_custom_css' => array(
                    'name' => esc_html__('Custom CSS', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'textarea', 
                ),
            ),
        );
        $array = apply_filters( 'super_settings_after_custom_css_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );


        /** 
         *  Custom JS
         *
         *  @since      4.2.0
        */
        $array['custom_js'] = array(        
            'hidden' => true,
            'name' => esc_html__( 'Custom JS', 'super-forms' ),
            'label' => esc_html__('Add custom JavaScript code', 'super-forms' ),
            'fields' => array(
                'theme_custom_js' => array(
                    'name' => esc_html__('Custom JS', 'super-forms' ),
                    'label' => esc_html__('Add your code without the <script></script> tags', 'super-forms' ),
                    'default' =>  '',
                    'type' => 'textarea', 
                ),
            ),
        );
        $array = apply_filters( 'super_settings_after_custom_js_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );
        // Old deprecated hook (keep for possible backward compatibility)
        $array = apply_filters( 'super_settings_after_smtp_server_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        
        /** 
         *	Restore Default Settings
         *
         *	@since		1.0.0
        */
        $array['restore_default'] = array(        
            'hidden' => true,
            'name' => esc_html__( 'Restore Default Settings', 'super-forms' ),
            'label' => esc_html__( 'Restore Default Settings', 'super-forms' ),
            'html' => array(
                '<span class="super-button super-restore-default super-delete">' . esc_html__( 'Restore Default Settings', 'super-forms' ) . '</span>',
            ),
        );
        $array = apply_filters( 'super_settings_after_restore_default_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );
        
        
        /** 
         *	System Status
         *
         *	@since		1.0.0
        */
        $array['system_status'] = array(        
            'hidden' => true,
            'name' => esc_html__( 'System Status', 'super-forms' ),
            'label' => esc_html__( 'System Status', 'super-forms' ),
            'html' => array(
                '<p><b>PHP ' . esc_html__('version', 'super-forms' ) . ':</b> ' . phpversion() . '</p>',
                '<p><b>MySQL ' . esc_html__('version', 'super-forms' ) . ':</b> ' . $mysql_version . '</p>',                
                '<p><b>WordPress ' . esc_html__(' version', 'super-forms' ) . ':</b> ' . get_bloginfo( 'version' ) . '</p>',
                '<p><b>Super Forms ' . esc_html__('version', 'super-forms' ) . ':</b> ' . SUPER_VERSION . '</p>',
            ),
        );
        $array = apply_filters( 'super_settings_after_system_status_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );
        
         
        /** 
         *  Export & Import
         *
         *  @since      1.0.6
        */
        $array['export_import'] = array(      
            'name' => esc_html__( 'Export & Import', 'super-forms' ),
            'label' => esc_html__( 'Export & Import', 'super-forms' ),
            'html' => array(

                // @since 4.0.0 - Export & Import Single Forms
                '<div class="super-export-import-single-form">',

                    '<div class="super-field">
                        <div class="super-field-name">' . esc_html__( 'Export form', 'super-forms' ) . ':</div>
                        <label class="super-retain-underlying-global-values"><input checked="checked" type="checkbox" name="retain_underlying_global_values" /><span>' . esc_html__( 'Retain underlying global value (recommended when exporting to other sites)', 'super-forms' ) . '</span></label>
                        <span class="super-button super-export super-clear">' . esc_html__( 'Export', 'super-forms' ) . '</span>
                    </div>',

                    '<div class="super-field">
                        <div class="super-field-name">' . esc_html__( 'Import form', 'super-forms' ) . ':
                            <div class="super-field-label">' . esc_html__( 'Browse import file and choose what you want to import', 'super-forms' ) . '</div>
                        </div>
                        <div class="super-field-input">
                        <div class="image-field browse-files" data-file-type="text/plain" data-multiple="false">
                            <span class="button super-insert-files"><i class="fas fa-plus"></i> Browse files</span>
                            <ul class="file-preview"></ul>
                            <input type="hidden" name="import-file" class="super-element-field">
                            </div>
                        </div>
                        <div class="super-field-input">
                            <div class="super-checkbox">
                                <label>
                                    <input type="checkbox" name="import-settings">' . esc_html__( 'Import settings', 'super-forms' ) . '
                                </label>
                                <label>
                                    <input type="checkbox" name="import-elements">' . esc_html__( 'Import elements', 'super-forms' ) . '
                                </label>
                                <label>
                                    <input type="checkbox" name="import-translations">' . esc_html__( 'Import translation settings', 'super-forms' ) . '
                                </label>
                                <label>
                                    <input type="checkbox" name="import-secrets">' . esc_html__( 'Import secrets', 'super-forms' ) . '
                                </label>
                            </div>
                        </div>
                        <span class="super-button super-import super-delete">' . esc_html__( 'Start Import', 'super-forms' ) . '</span>
                    </div>',

                    '<div class="super-field">
                        <span class="super-button super-reset-global-settings super-clear">' . esc_html__( 'Reset to global settings', 'super-forms' ) . '</span>
                    </div>',

                '</div>',

                // @since 1.9 - export settings
                '<div class="super-export-import">',
                    '<strong>' . esc_html__( 'Export Settings', 'super-forms' ) . ':</strong>',
                    '<textarea name="export-json">' . json_encode( $s ) . '</textarea>',
                    '<hr />',
                    '<strong>' . esc_html__( 'Import Settings', 'super-forms' ) . ':</strong>',
                    '<textarea name="import-json"></textarea>',
                    '<span class="super-button super-import-settings super-delete">' . esc_html__( 'Import Settings', 'super-forms' ) . '</span>',
                    '<span class="super-button super-load-default-settings super-clear">' . esc_html__( 'Load default Settings', 'super-forms' ) . '</span>',
                '</div>',

                // @since 1.9 - export forms
                '<div class="super-export-import-forms">',
                    '<strong>' . esc_html__( 'Export Forms', 'super-forms' ) . ':</strong>',
                    '<span class="super-button super-export-forms super-delete" data-type="csv">' . esc_html__( 'Export Forms', 'super-forms' ) . '</span>',
                '</div>',

                // @since 1.9 - import forms
                '<div class="super-export-import-entries">',
                    '<strong>' . esc_html__( 'Import Forms', 'super-forms' ) . ':</strong>',
                    '<div class="browse-forms-import-file">',
                        '<span class="button super-button super-import-forms"><i class="fas fa-download"></i> Select Import file</span>',
                    '</div>',
                '</div>',

                '<div class="super-export-import-entries">',
                    '<strong>' . esc_html__( 'Export Contact Entries', 'super-forms' ) . ':</strong>',
                    '<p>' . esc_html__( 'Below you can enter a date range (or leave empty to export all contact entries)', 'super-forms' ) . '</p>',
                    '<span>' . esc_html__( 'From', 'super-forms' ) . ':</span> <input type="text" value="" name="from" />',
                    '<span>' . esc_html__( 'Till', 'super-forms' ) . ':</span> <input type="text" value="" name="till" />',
                    '<p>' . esc_html__( 'Filter by forms (or leave empty to export entries from all forms)', 'super-forms' ) . '</p>',
                    '<span>' . esc_html__( 'Form ID\'s seperated by commas', 'super-forms' ) . ':</span> <input type="text" value="" name="form_ids" />',
                    '<p>' . esc_html__( 'Display oldest or latest entries at the top', 'super-forms' ) . '</p>',
                    '<span>' . esc_html__( 'Sort', 'super-forms' ) . ':</span> <select name="order_by"><option value="ASC">ASC - Oldest first (default)</option><option value="DESC">DESC - Newest first</option></select>',
                    '<p>' . esc_html__( 'Below you can change the default delimiter and enclosure characters', 'super-forms' ) . ':</p>',
                    '<span>' . esc_html__( 'Delimiter', 'super-forms' ) . ':</span> <input type="text" value="," name="delimiter" />',
                    '<span>' . esc_html__( 'Enclosure', 'super-forms' ) . ':</span> <input type="text" value="' . htmlentities('"') . '" name="enclosure" />',
                    '<span class="super-button super-export-entries super-delete" data-type="csv">' . esc_html__( 'Export Contact Entries to CSV', 'super-forms' ) . '</span>',
                '</div>',

                '<div class="super-export-import-entries">',
                    '<strong>' . esc_html__( 'Import Contact Entries', 'super-forms' ) . ':</strong>',
                    '<div class="browse-csv-import-file">',
                        '<span class="button super-button super-insert-files"><i class="fas fa-download"></i> Select CSV file</span>',
                        '<div class="file-preview"></div>',
                        '<input type="hidden" name="csv_import_file" value="">',
                    '</div>',
                '</div>'
            ),
        );
        $array = apply_filters( 'super_settings_after_export_import_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );
        $array = apply_filters( 'super_settings_after_support_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );

        // @since 6.3.0
        // Before returning the settings, set global and current values
        // In some cases a value might be locked to it's global value, this way a value that isn't allowed to be empty can still be empty and honor the global setting when global setting is being changed
        // This wasn't possible before
        foreach($array as $k => $v){
            if(!isset($array[$k]['fields'])) continue;
            foreach($array[$k]['fields'] as $kk => $vv){
                if(isset($vv['type']) && $vv['type']==='multicolor'){
                    foreach($vv['colors'] as $ck => $cv){
                        $array[$k]['fields'][$kk]['colors'][$ck]['g'] = (isset($g[$ck]) ? $g[$ck] : '');
                        $array[$k]['fields'][$kk]['colors'][$ck]['v'] = (isset($g[$ck]) ? $g[$ck] : '');
                    }
                    continue;
                }
                $array[$k]['fields'][$kk]['g'] = (isset($g[$kk]) ? $g[$kk] : ''); // global
                $array[$k]['fields'][$kk]['v'] = (isset($s[$kk]) ? $s[$kk] : '');
            }
        }

        $array = apply_filters( 'super_settings_end_filter', $array, array( 'settings'=>$s, 'default'=>$default ) );
        return $array;
    }

    /**
     * Retrieve the default value of the field
     * @param  int $strict_default
     * @param  string $name
     * @param  array $s
     * @param  string $default
     * @param  boolean $allow_empty
     *
     *	@since		1.0.0
    */
    public static function get_value( $strict_default, $name, $s, $default, $allowEmpty=true ) {
        if( $strict_default==1 ) {
            return $default;
        } 
        // Check if this setting is allowd to be left empty
        if( $allowEmpty && (empty($s[$name])) ){
            return '';
        }
        if( !isset( $s[$name] ) ) return $default;
        return $s[$name];
    }
    
    /**
     * Reformat the settings
     * @param  array $s
     *
     *	@since		1.0.0
    */
    public static function format_settings( $s=null ) {
        if($s!=false){
            foreach($s as $k=>$v) {
                $s[$k] = stripslashes($v);
            }
        }
        return $s;
    }
    
}
endif;
