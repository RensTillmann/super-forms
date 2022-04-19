<?php
/**
 * Super Forms - E-mail Reminders
 *
 * @package   Super Forms - E-mail Reminders
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - E-mail Reminders
 * Description: Send email appointment reminders at specific times based on form submission date or user selected date with an optional offset
 * Version:     1.2.1
 * Plugin URI:  http://f4d.nl/super-forms
 * Author URI:  http://f4d.nl/super-forms
 * Author:      feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 4.9
 * Requires PHP:      5.4
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


if( !class_exists('SUPER_Email_Reminders') ) :


    /**
     * Main SUPER_Email_Reminders Class
     *
     * @class SUPER_Email_Reminders
     * @version 1.0.0
     */
    final class SUPER_Email_Reminders {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '1.2.1';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'email-reminders';
        public $add_on_name = 'E-mail Reminders';

        
        /**
         * @var SUPER_Email_Reminders The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Email_Reminders Instance
         *
         * Ensures only one instance of SUPER_Email_Reminders is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Email_Reminders()
         * @return SUPER_Email_Reminders - Main instance
         *
         *  @since      1.0.0
        */
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        
        /**
         * SUPER_Email_Reminders Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_email_reminders_loaded');
        }

        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *  @since      1.0.0
        */
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }

        
        /**
         * What type of request is this?
         *
         * string $type ajax, frontend or admin
         * @return bool
         *
         *  @since      1.0.0
        */
        private function is_request($type){
            switch ($type){
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        
        /**
         * Hook into actions and filters
         *
         *  @since      1.0.0
        */
        private function init_hooks() {
            
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );

            // Setup reminders cron job
            if ( ! wp_next_scheduled( 'super_cron_reminders' ) ) {
                wp_schedule_event( time(), 'every_minute', 'super_cron_reminders' );
            }

            // Send reminders (triggered by cron job)
            add_action( 'super_cron_reminders', array( $this, 'send_reminders' ) );

            // Upon activating & deactivating the plugin
            register_deactivation_hook(__FILE__, array( $this, 'plugin_deactivation' ) );

            // Upon activating & deactivating the bundled plugin
            add_action( 'after_super_forms_deactivated', array( $this, 'plugin_deactivation' ) );

            if ( $this->is_request( 'admin' ) ) {
                // Filters since 1.0.0
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_email_success_msg_action', array( $this, 'set_reminder' ) );
            }

        }


        /**
         * Load Localisation files.
         * Note: the first-loaded translation file overrides any following ones if the same translation is present.
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'plugin_locale', get_locale(), 'super-forms' );
            load_textdomain( 'super-forms', WP_LANG_DIR . '/super-forms-' . $this->add_on_slug . '/super-forms-' . $this->add_on_slug . '-' . $locale . '.mo' );
            load_plugin_textdomain( 'super-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
        }


        /**
         * Upon plugin deactivation
         *
         *  @since      1.0.0
        */
        public static function plugin_deactivation( $schedules ) {
            wp_clear_scheduled_hook('super_cron_reminders');
        }

        /**
         * Send reminders
         *
         *  @since      1.0.0
        */
        public static function send_reminders(){
            include_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
            // Retrieve reminders from database based on post_meta named `_super_reminder_timestamp` based on the timestamp we can determine if we need to send the reminder yet
            global $wpdb;
            $reminders = $wpdb->get_results("
            SELECT post_id, meta_value AS timestamp, 
            (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_super_reminder_settings' AND r.post_id = post_id) AS reminder_settings,
            (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_super_reminder_data' AND r.post_id = post_id) AS reminder_data
            FROM $wpdb->postmeta AS r
            INNER JOIN $wpdb->posts ON ID = post_id
            WHERE meta_key = '_super_reminder_timestamp'");
            $current_timestamp = strtotime(current_time('Y-m-d H:i'));
            foreach($reminders as $k => $v){
                // If timestamp is smaller (in the past) or equal to current timestamp the we may proceed
                if($v->timestamp <= $current_timestamp){
                    // Grab post ID
                    $post_id = $v->post_id;
                    // Grab submission data
                    $data = maybe_unserialize($v->reminder_data);
                    // Grab form settings, and merge with reminder settings
                    $settings = SUPER_Common::get_form_settings($post_id);
                    $v->reminder_settings = maybe_unserialize($v->reminder_settings);
                    $settings = array_merge($settings,  $v->reminder_settings);
                    unset($settings['theme_custom_js']);
                    unset($settings['theme_custom_css']);
                    unset($settings['form_custom_css']);
                    if(!isset($settings['reminder_exclude_empty'])) $settings['reminder_exclude_empty'] = '';

                    $reminder_loop = '';
                    $attachments = array();
                    $string_attachments = array();
                    if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
                        foreach( $data as $k => $v ) {
                            $row = $settings['reminder_email_loop'];
                            if( !isset( $v['exclude'] ) ) {
                                $v['exclude'] = 0;
                            }
                            if( $v['exclude']==2 ) {
                                continue;
                            }
                            /** 
                             *  Filter to control the email loop when something special needs to happen
                             *  e.g. Signature element needs to display image instead of the base64 code that the value contains
                             *
                             *  @param  string  $row
                             *  @param  array   $data
                             *
                            */
                            $result = apply_filters( 'super_before_email_loop_data_filter', $row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                            if( isset( $result['status'] ) ) {
                                if( $result['status']=='continue' ) {
                                    if( isset( $result['string_attachments'] ) ) {
                                        $string_attachments = $result['string_attachments'];
                                    }
                                    $reminder_loop .= $result['row'];
                                    continue;
                                }
                            }
                            if( $v['type']=='files' ) {
                                $files_value = '';
                                if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
                                    $v['value'] = '';
                                    if( !empty( $v['label'] ) ) {
                                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                    }else{
                                        $row = str_replace( '{loop_label}', '', $row );
                                    }
                                    $files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
                                }else{
                                    $v['value'] = '-';
                                    foreach( $v['files'] as $key => $value ) {
                                        if( $key==0 ) {
                                            if( !empty( $v['label'] ) ) {
                                                $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                            }else{
                                                $row = str_replace( '{loop_label}', '', $row );
                                            }
                                        }
                                        $files_value .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html($value['value']) . '</a><br /><br />';
                                        if( $v['exclude']!=2 ) {
                                            if( $v['exclude']==1 ) {
                                                $attachments[$value['value']] = $value['url'];
                                            }else{
                                                $attachments[$value['value']] = $value['url'];
                                            }
                                        }
                                    }
                                }
                                $row = str_replace( '{loop_value}', $files_value, $row );
                            }else{
                                if( ($v['type']=='form_id') || ($v['type']=='entry_id') ) {
                                    $row = '';
                                }else{

                                    if( !empty( $v['label'] ) ) {
                                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                    }else{
                                        $row = str_replace( '{loop_label}', '', $row );
                                    }
                                    if( isset( $v['admin_value'] ) ) {
                                        if( !empty($v['replace_commas']) ) $v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
                                        $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $row );
                                    }
                                    if( isset( $v['value'] ) ) {
                                        if( !empty($v['replace_commas']) ) $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                                        $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $row );
                                    }

                                }
                            }
                            if( $settings['reminder_exclude_empty']=='true' && empty($v['value']) ) {
                            }else{
                                $reminder_loop .= $row;
                            }
                        }
                    }
                    
                    if( !isset($settings['reminder_header_additional']) ) $settings['reminder_header_additional'] = '';
                    $settings['header_additional'] = $settings['reminder_header_additional'];
                    
                    if(!empty($settings['reminder_body_open'])) $settings['reminder_body_open'] = $settings['reminder_body_open'] . '<br /><br />';
                    if(!empty($settings['reminder_body'])) $settings['reminder_body'] = $settings['reminder_body'] . '<br /><br />';
                    $email_body = $settings['reminder_body_open'] . $settings['reminder_body'] . $settings['reminder_body_close'];
                    $email_body = str_replace( '{loop_fields}', $reminder_loop, $email_body );
                    $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );

                    if(!isset($settings['reminder_body_nl2br'])) $settings['reminder_body_nl2br'] = 'true';
                    if($settings['reminder_body_nl2br']=='true') $email_body = nl2br( $email_body );
                    
                    $email_body = do_shortcode($email_body);
                    $email_body = apply_filters( 'super_before_sending_reminder_body_filter', $email_body, array( 'settings'=>$settings, 'reminder_loop'=>$reminder_loop, 'data'=>$data ) );
                    if( !isset( $settings['reminder_from_type'] ) ) $settings['reminder_from_type'] = 'default';
                    if( $settings['reminder_from_type']=='default' ) {
                        $settings['reminder_from_name'] = get_option( 'blogname' );
                        $settings['reminder_from'] = get_option( 'admin_email' );
                    }
                    if( !isset( $settings['reminder_from_name'] ) ) $settings['reminder_from_name'] = get_option( 'blogname' );
                    if( !isset( $settings['reminder_from'] ) ) $settings['reminder_from'] = get_option( 'admin_email' );
                    $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['reminder_to'], $data, $settings ) );
                    $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['reminder_from'], $data, $settings ) );
                    $from_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['reminder_from_name'], $data, $settings ) );          
                    $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['reminder_subject'], $data, $settings ) );

                    $cc = '';
                    if( !empty($settings['reminder_header_cc']) ) {
                        $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['reminder_header_cc'], $data, $settings ) );
                    }
                    $bcc = '';
                    if( !empty($settings['reminder_header_bcc']) ) {
                        $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['reminder_header_bcc'], $data, $settings ) );
                    }

                    if( !isset($settings['reminder_header_reply_enabled']) ) $settings['reminder_header_reply_enabled'] = false;
                    $reply = '';
                    $reply_name = '';
                    if( $settings['reminder_header_reply_enabled']==false ) {
                        $custom_reply = false;
                    }else{
                        $custom_reply = true;
                        if( !isset($settings['reminder_header_reply']) ) $settings['reminder_header_reply'] = '';
                        if( !isset($settings['reminder_header_reply_name']) ) $settings['reminder_header_reply_name'] = '';
                        $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['reminder_header_reply'], $data, $settings ) );
                        $reply_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['reminder_header_reply_name'], $data, $settings ) );
                    }

                    // Default email attachments
                    if( !empty($settings['reminder_attachments']) ) {
                        $email_attachments = explode( ',', $settings['reminder_attachments'] );
                        foreach($email_attachments as $k => $v){
                            $file = get_attached_file($v);
                            if( $file ) {
                                $url = wp_get_attachment_url($v);
                                $filename = basename ( $file );
                                $attachments[$filename] = $url;
                            }
                        }
                    }
                    $attachments = apply_filters( 'super_before_sending_email_reminder_attachments_filter', $attachments, array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body )  );

                    // Send the email
                    $mail = SUPER_Common::email( $to, $from, $from_name, $custom_reply, $reply, $reply_name, $cc, $bcc, $subject, $email_body, $settings, $attachments, $string_attachments );

                    // Delete reminder
                    wp_delete_post($post_id, true);

                }
            }
        }


        /**
         * Hook into settings and add E-mail Reminders settings
         *
         *  @since      1.0.0
        */
        public static function set_reminder( $atts ) {
            $settings = $atts['settings'];
            $data = $atts['data'];
            // Loop until we can't find reminder
            if(empty($settings['email_reminder_amount'])) $settings['email_reminder_amount'] = 3;
            $limit = absint($settings['email_reminder_amount']);
            if($limit==0) $limit = 3;
            $x = 1;
            while( $x <= $limit ) {
                if( (!empty($settings['email_reminder_' . $x])) && ($settings['email_reminder_' . $x]=='true') ) {
                    self::insert_reminder($x, $settings, $data);
                }
                $x++;
            }
        }
        public static function insert_reminder($suffix, $settings, $data){
            if(empty($settings['email_reminder_'.$suffix.'_date_offset'])) $settings['email_reminder_'.$suffix.'_date_offset'] = 0;
            if(empty($settings['email_reminder_'.$suffix.'_time_offset'])) $settings['email_reminder_'.$suffix.'_time_offset'] = 0;
            if(empty($settings['email_reminder_'.$suffix.'_base_date'])) $settings['email_reminder_'.$suffix.'_base_date'] = date('Y-m-d');
            // 86400 = 1 day / 24 hours
            $offset = $settings['email_reminder_'.$suffix.'_date_offset'];
            $offset = 86400 * $offset;
            $base_date = $settings['email_reminder_'.$suffix.'_base_date'];
            if(strpos($base_date, ';timestamp') !== false){
                $base_date = SUPER_Common::email_tags( $base_date, $data, $settings );
                $base_date = $base_date/1000;
                $reminder_date = date('Y-m-d', $base_date + $offset);
            }else{
                $base_date = SUPER_Common::email_tags( $base_date, $data, $settings );
                $reminder_date = date('Y-m-d', strtotime($base_date) + $offset);
            }

            // Send at a fixed time
            if($settings['email_reminder_'.$suffix.'_time_method']==='fixed'){
                $reminder_time = SUPER_Common::email_tags( $settings['email_reminder_'.$suffix.'_time_fixed'], $data, $settings );
                // Test if time was set to 24 hour format
                if(!preg_match("#([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1}):[0-5]{1}[0-9]{1}#", $reminder_time)){
                    SUPER_Common::output_message(
                        $error = true, 
                        $msg = $reminder_time . esc_html__( 'is not a valid 24-hour clock format, please correct and make sure to use a 24-hour format e.g: 21:45', 'super-forms' ) 
                    );
                }
            }else{
                // Send based of form submission + an offset
                $base_time = current_time('H:i');
                // 3600 = 1 hour / 60 minutes
                $offset = 3600 * $settings['email_reminder_'.$suffix.'_time_offset'];
                $reminder_time = date('H:i', strtotime($base_time) + $offset);
            }
            $reminder_real_date = date('Y-m-d H:i', strtotime($reminder_date.' '.$reminder_time));

            $reminder_date = strtotime($reminder_real_date);
            if($reminder_date < strtotime(current_time('Y-m-d H:i'))){
                SUPER_Common::output_message(
                    $error = true, 
                    $msg = '<strong>' . $reminder_real_date . '</strong> ' . esc_html__( 'can not be used as a reminder date because it is in the past, please check your settings under "Form Settings > E-mail Reminders".', 'super-forms' ) 
                );
            }

            // Insert reminder into database
            $post = array(
                'post_type' => 'super_email_reminder',
                'post_status' => 'queued', // `queued` = scheduled to be send, `send` = has been send
                'post_parent' => $data['hidden_form_id']['value'] // Keep reference to the form
            );
            $reminder_id = wp_insert_post($post); 
            
            // Save the timestamp for this reminder, we will use this to check when to send the reminder
            add_post_meta( $reminder_id, '_super_reminder_timestamp', $reminder_date );
            
            // Save all settings/data as post meta for this reminder
            $reminder_settings = array();
            foreach($settings as $k => $v){
                if(strpos($k, 'email_reminder_'.$suffix.'_') === false) continue;
                $k = str_replace('email_reminder_'.$suffix.'_', 'reminder_', $k);
                $reminder_settings[$k] = $v;
            }
            add_post_meta( $reminder_id, '_super_reminder_settings', $reminder_settings );

            // Save all submission data post meta for this reminder
            add_post_meta( $reminder_id, '_super_reminder_data', $data );

            // Store the created reminders post ID into a session
            $email_reminders = SUPER_Common::getClientData( 'super_forms_email_reminders' );
            if(!is_array($email_reminders)) $email_reminders = array();
            $email_reminders[] = $reminder_id;
            SUPER_Common::setClientData( array( 'name'=> 'super_forms_email_reminders', 'value'=>$email_reminders  ) );
        }


        /**
         * Hook into settings and add E-mail Reminders settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $x ) {
            $settings = $x['settings'];
            // First reminder settings
            $array['email_reminders'] = array(        
                'name' => esc_html__( 'E-mail Reminders', 'super-forms' ),
                'label' => esc_html__( 'E-mail Reminders', 'super-forms' ),
                'html' => array( '<style>.super-settings .email-reminders-html-notice {display:none;}</style>', '<p class="email-reminders-html-notice">' . sprintf( esc_html__( 'Need to send more E-mail reminders? You can increase the amount here:%s%s%sSuper Forms > Settings > E-mail Reminders%s%s', 'super-forms' ), '<br />', '<a target="_blank" href="' . esc_url(admin_url()) . 'admin.php?page=super_settings#email-reminders">', '<strong>', '</strong>', '</a>' ) . '</p>' ),
                'fields' => array(
                    'email_reminder_amount' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Select how many individual E-mail reminders you require', 'super-forms' ),
                        'desc' => esc_html__( 'If you need to send 10 reminders enter: 10', 'super-forms' ),
                        'default' => '3'
                    )
                )
            );
             
            if(empty($settings['email_reminder_amount'])) $settings['email_reminder_amount'] = 3;
            $limit = absint($settings['email_reminder_amount']);
            if($limit==0) $limit = 3;

            $x = 1;
            while($x <= $limit) {
                // Second reminder settings
                $reminder_settings = array(
                    'email_reminder_'.$x => array(
                        'hidden_setting' => true,
                        'desc' => sprintf( esc_html__( 'Enable email reminder #%s', 'super-forms' ), $x ), 
                        'default' => '',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => sprintf( esc_html__( 'Enable email reminder #%s', 'super-forms' ), $x ),
                        ),
                        'filter' => true
                    ),
                    'email_reminder_'.$x.'_base_date' => array(
                        'hidden_setting' => true,
                        'name'=> esc_html__( 'Send reminder based on the following date:', 'super-forms' ),
                        'label'=> esc_html__( 'Must be English formatted date e.g: "25-03-2020". When using a datepicker that doesn\'t use the correct format, you can use the tag {date;timestamp} to retrieve the timestamp which will work correctly with any date format (leave blank to use the form submission date)', 'super-forms' ),
                        'default'=> '',
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x,
                        'filter_value'=>'true'
                    ),
                    'email_reminder_'.$x.'_date_offset' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Define how many days after or before the reminder should be send based of the base date', 'super-forms' ),
                        'label'=> esc_html__( '0 = The same day, 1 = Next day, 5 = Five days after, -1 = One day before, -3 = Three days before', 'super-forms' ),
                        'default'=> '0',
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x,
                        'filter_value'=>'true'
                    ),
                    'email_reminder_'.$x.'_time_method' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Send reminder at a fixed time, or by offset', 'super-forms' ),
                        'default'=> 'fixed',
                        'type' => 'select', 
                        'values' => array(
                            'fixed' => esc_html__( 'Fixed (e.g: always at 09:00)', 'super-forms' ), 
                            'offset' => esc_html__( 'Offset (e.g: 2 hours after date)', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x,
                        'filter_value'=>'true'
                    ),
                    'email_reminder_'.$x.'_time_fixed' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Define at what time the reminder should be send', 'super-forms' ),
                        'label'=> esc_html__( 'Use 24h format e.g: 13:00, 09:30 etc.', 'super-forms' ),
                        'default'=> '09:00',
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x.'_time_method',
                        'filter_value'=>'fixed'
                    ),
                    'email_reminder_'.$x.'_time_offset' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Define at what offset the reminder should be send based of the base time', 'super-forms' ),
                        'label'=> esc_html__( 'Example: 2 = Two hours after, -5 = Five hours before<br />(the base time will be the time of the form submission)', 'super-forms' ),
                        'default'=> '0',
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x.'_time_method',
                        'filter_value'=>'offset'
                    )
                );
                $array['email_reminders']['fields'] = array_merge($array['email_reminders']['fields'], $reminder_settings);


                $fields = $array['confirmation_email_settings']['fields'];
                $new_fields = array();
                foreach($fields as $k => $v){
                    if($k=='confirm'){
                        unset($fields[$k]);
                        continue;
                    }
                    if( !empty($v['parent']) ) {
                        if($v['parent']=='confirm'){
                            $v['parent'] = 'email_reminder_'.$x;
                            $v['filter_value'] = 'true';
                        }else{
                            $v['parent'] = str_replace('confirm_', 'email_reminder_'.$x.'_', $v['parent']);
                        }
                    }
                    unset($fields[$k]);
                    $k = str_replace('confirm_', 'email_reminder_'.$x.'_', $k);
                    $v['hidden_setting'] = true;
                    $new_fields[$k] = $v;
                }
                $new_fields['email_reminder_'.$x.'_attachments'] = array(
                    'hidden_setting' => true,
                    'name' => sprintf( esc_html__( 'Attachments for reminder email #%s', 'super-forms' ), $x ),
                    'desc' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
                    'default'=> '',
                    'type' => 'file',
                    'multiple' => 'true',
                    'filter'=>true,
                    'parent'=>'email_reminder_'.$x,
                    'filter_value'=>'true'
                );
                $array['email_reminders']['fields'] = array_merge($array['email_reminders']['fields'], $new_fields);
                $x++;
            }

            return $array;
        }

    }
        
endif;


/**
 * Returns the main instance of SUPER_Email_Reminders to prevent the need to use globals.
 *
 * @return SUPER_Email_Reminders
 */
if( !function_exists('SUPER_Email_Reminders') ){
    function SUPER_Email_Reminders() {
        return SUPER_Email_Reminders::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Email_Reminders'] = SUPER_Email_Reminders();
}
