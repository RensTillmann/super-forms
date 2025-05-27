<?php
/**
 * Super Forms - CSV Attachment
 *
 * @package   Super Forms - CSV Attachment
 * @author    WebRehab
 * @link      http://super-forms.com
 * @copyright 2022 by WebRehab
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - CSV Attachment
 * Description: Sends a CSV file with the form data to the admin email as an attachment
 * Version:     1.4.1
 * Plugin URI:  http://super-forms.com
 * Author URI:  http://super-forms.com
 * Author:      WebRehab
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

if( !class_exists('SUPER_CSV_Attachment') ) :


    /**
     * Main SUPER_CSV_Attachment Class
     *
     * @class SUPER_CSV_Attachment
     * @version 1.0.0
     */
    final class SUPER_CSV_Attachment {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '1.4.1';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'csv-attachment';
        public $add_on_name = 'CSV Attachment';

        
        /**
         * @var SUPER_CSV_Attachment The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_CSV_Attachment Instance
         *
         * Ensures only one instance of SUPER_CSV_Attachment is loaded or can be loaded.
         *
         * @static
         * @see SUPER_CSV_Attachment()
         * @return SUPER_CSV_Attachment - Main instance
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
         * SUPER_CSV_Attachment Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_csv_attachment_loaded');
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
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            if ( $this->is_request( 'ajax' ) ) {
                add_filter( 'super_before_sending_email_attachments_filter', array( $this, 'add_csv_attachment' ), 10, 2 );
            }
        }


        /**
         * Hook into settings and add CSV Attachment settings
         *
         *  @since      1.0.0
        */
        public static function add_csv_attachment( $attachments, $atts ) {
            error_log('add_csv_attachment() called');
            //error_log('Raw $atts: ' . print_r($atts, true));
            $data = isset($atts['data']) ? $atts['data'] : array();
            $form_id = isset($atts['form_id']) ? $atts['form_id'] : null;
            // Use options from trigger if present
            $csv_settings = null;
            if (isset($atts['options']['csv_attachment']) && is_array($atts['options']['csv_attachment'])) {
                $csv_settings = $atts['options']['csv_attachment'];
                error_log('Using $atts[options][csv_attachment] for settings');
            } elseif (isset($atts['csv_attachment']) && is_array($atts['csv_attachment'])) {
                $csv_settings = $atts['csv_attachment'];
                error_log('Using $atts[csv_attachment] for settings');
            } else {
                // fallback to legacy
                $csv_settings = isset($atts['settings']) ? $atts['settings'] : $atts;
                error_log('Using legacy structure for CSV settings');
            }
            error_log('CSV settings: ' . print_r($csv_settings, true));
            // Check if enabled
            $enabled = isset($csv_settings['enabled']) ? $csv_settings['enabled'] : (isset($csv_settings['csv_attachment_enable']) ? $csv_settings['csv_attachment_enable'] : '');
            if ($enabled === 'true') {
                $csv_attachment_name = isset($csv_settings['name']) ? $csv_settings['name'] : (isset($csv_settings['csv_attachment_name']) ? $csv_settings['csv_attachment_name'] : 'super-csv-attachment');
                $csv_attachment_name = SUPER_Common::email_tags($csv_attachment_name, $data, $csv_settings);
                $save_as = isset($csv_settings['save_as']) ? $csv_settings['save_as'] : (isset($csv_settings['csv_attachment_save_as']) ? $csv_settings['csv_attachment_save_as'] : 'entry_value');
                $excluded_fields = array();
                if (isset($csv_settings['exclude_fields']) && is_array($csv_settings['exclude_fields'])) {
                    foreach ($csv_settings['exclude_fields'] as $field) {
                        if (isset($field['name']) && $field['name'] !== '') {
                            $excluded_fields[] = $field['name'];
                        }
                    }
                } elseif (isset($csv_settings['csv_attachment_exclude'])) {
                    $excluded_fields = explode("\n", $csv_settings['csv_attachment_exclude']);
                }
                $delimiter = isset($csv_settings['delimiter']) ? $csv_settings['delimiter'] : (isset($csv_settings['csv_attachment_delimiter']) ? $csv_settings['csv_attachment_delimiter'] : ',');
                $enclosure = isset($csv_settings['enclosure']) ? $csv_settings['enclosure'] : (isset($csv_settings['csv_attachment_enclosure']) ? $csv_settings['csv_attachment_enclosure'] : '"');
                $rows = array();
                foreach ($data as $k => $v) {
                    if (!isset($v['name'])) continue;
                    if (!in_array($v['name'], $excluded_fields)) {
                        $rows[0][] = $k;
                    }
                }
                foreach ($data as $k => $v) {
                    if (!isset($v['name'])) continue;
                    if (!in_array($v['name'], $excluded_fields)) {
                        if ((isset($v['type'])) && ($v['type'] == 'files')) {
                            $files = '';
                            if ((isset($v['files'])) && (count($v['files']) != 0)) {
                                foreach ($v['files'] as $fk => $fv) {
                                    if ($fk == 0) {
                                        $files .= $fv['url'];
                                    } else {
                                        $files .= PHP_EOL . $fv['url'];
                                    }
                                }
                            }
                            $rows[1][] = $files;
                        } else {
                            if (!isset($v['value'])) {
                                $rows[1][] = '';
                            } else {
                                if (($save_as == 'entry_value') && (isset($v['entry_value']))) {
                                    $v['value'] = $v['entry_value'];
                                } elseif (($save_as == 'confirm_email_value') && (isset($v['confirm_value']))) {
                                    $v['value'] = $v['confirm_value'];
                                }
                                $rows[1][] = stripslashes($v['value']);
                            }
                        }
                    }
                }
                try {
                    error_log('form_id: ' . $form_id);
                    $d = wp_upload_dir();
                    $basename = sanitize_title_with_dashes($csv_attachment_name) . '.csv';
                    $filename = trailingslashit($d['path']) . $basename;
                    $fp = fopen($filename, 'w');
                    $bom = apply_filters('super_csv_bom_header_filter', chr(0xEF) . chr(0xBB) . chr(0xBF));
                    if (fwrite($fp, $bom) === false) {
                        SUPER_Common::output_message(array(
                            'msg' => "Unable to write to file ($filename)",
                            'form_id' => absint($form_id)
                        ));
                    }
                    $delimiter = wp_unslash(sanitize_text_field($delimiter));
                    $enclosure = wp_unslash(sanitize_text_field($enclosure));
                    if (empty($delimiter)) $delimiter = ',';
                    if (empty($enclosure)) $enclosure = '"';
                    foreach ($rows as $fields) {
                        fputcsv($fp, $fields, $delimiter, $enclosure, PHP_EOL);
                    }
                    fclose($fp);
                    $attachment = array(
                        'post_mime_type' => 'text/csv',
                        'post_title'     => preg_replace('/\.[^.]+$/', '', $basename),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attachment_id = wp_insert_attachment($attachment, $filename, 0);
                    add_post_meta($attachment_id, 'super-forms-form-upload-file', true);
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
                    wp_update_attachment_metadata($attachment_id, $attach_data);
                    $attachments['csv-form-data.csv'] = wp_get_attachment_url($attachment_id);
                    error_log('CSV attachment created: ' . $filename . ' (ID: ' . $attachment_id . ')');
                } catch (Exception $e) {
                    error_log('form_id: ' . $form_id);
                    SUPER_Common::output_message(array(
                        'msg' => $e->getMessage(),
                        'form_id' => absint($form_id)
                    ));
                }
            }
            return $attachments;
        }


        /**
         * Formats a line (passed as a fields  array) as CSV and returns the CSV as a string.
         *
         *  @since      1.0.0
        */
        public static function array_to_csv( array &$fields, $delimiter = ';', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false ) {
            $delimiter_esc = preg_quote($delimiter, '/');
            $enclosure_esc = preg_quote($enclosure, '/');
            $output = array();
            foreach ( $fields as $field ) {
                if ($field === null && $nullToMysqlNull) {
                    $output[] = 'NULL';
                    continue;
                }
                if ( $encloseAll || preg_match( "/(?:{$delimiter_esc}|{$enclosure_esc}|\s)/", $field ) ) {
                    $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
                }
                else {
                    $output[] = $field;
                }
            }
            return implode( $delimiter, $output );
        }


        /**
         * Hook into settings and add CSV Attachment settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $x ) {
            $array['csv_attachment'] = array(
                'hidden' => 'settings',
                'name' => esc_html__( 'CSV Attachments', 'super-forms' ),
                'label' => esc_html__( 'CSV Attachments Settings', 'super-forms' ),
                //'docs' => array(
                //    array('title'=>'Sending to different departments conditionally', 'url'=>'/tutorials/sending-emails-to-different-department-based-on-selected-form-option')
                //),
                'html' => array(
                    sprintf( esc_html__( '%s%sNote: %sCSV attachments for your E-mails should now be configured via the [Triggers] TAB when sending an E-mail%s', 'super-forms' ), '<div class="sfui-notice sfui-desc">', '<strong>', '</strong>', '</div>' ),
                )
            );
            // tmp $array['csv_attachment'] = array(
            // tmp     'hidden' => 'settings',
            // tmp     'name' => esc_html__( 'CSV Attachment', 'super-forms' ),
            // tmp     'label' => esc_html__( 'CSV Attachment Settings', 'super-forms' ),
            // tmp     'fields' => array(
            // tmp         'csv_attachment_enable' => array(
            // tmp             'desc' => esc_html__( 'This will attach a CSV file to the admin email', 'super-forms' ), 
            // tmp             'default' => '',
            // tmp             'type' => 'checkbox',
            // tmp             'values' => array(
            // tmp                 'true' => esc_html__( 'Send CSV attachment with form data to the admin email', 'super-forms' ),
            // tmp             ),
            // tmp             'filter' => true
            // tmp         ),
            // tmp         'csv_attachment_name' => array(
            // tmp             'name'=> esc_html__( 'The filename of the attachment', 'super-forms' ),
            // tmp             'default'=> 'super-csv-attachment',
            // tmp             'filter'=>true,
            // tmp             'parent'=>'csv_attachment_enable',
            // tmp             'filter_value'=>'true'
            // tmp         ),
            // tmp         'csv_attachment_save_as' => array(
            // tmp             'name'=> esc_html__( 'Choose what value to save for checkboxes & radio buttons', 'super-forms' ),
            // tmp             'desc'=> esc_html__( 'When editing a field you can change these settings', 'super-forms' ),
            // tmp             'default'=> 'admin_email_value',
            // tmp             'type'=>'select', 
            // tmp             'values'=>array(
            // tmp                 'admin_email_value' => esc_html__( 'Save the admin email value (default)', 'super-forms' ),
            // tmp                 'confirm_email_value' => esc_html__( 'Save the confirmation email value', 'super-forms' ),
            // tmp                 'entry_value' => esc_html__( 'Save the entry value', 'super-forms' ),
            // tmp             ),
            // tmp             'filter'=>true,
            // tmp             'parent'=>'csv_attachment_enable',
            // tmp             'filter_value'=>'true'
            // tmp         ),
            // tmp         'csv_attachment_exclude' => array(
            // tmp             'name'=> esc_html__( 'Exclude fields from CSV file (put each field name on a new line)', 'super-forms' ),
            // tmp             'desc'=> esc_html__( 'When saving the CSV these fields will be excluded from the CSV file', 'super-forms' ),
            // tmp             'default'=> '',
            // tmp             'type'=>'textarea', 
            // tmp             'filter'=>true,
            // tmp             'parent'=>'csv_attachment_enable',
            // tmp             'filter_value'=>'true'
            // tmp         ),

            // tmp         // @since 1.1.1 - custom settings for delimiter and enclosure
            // tmp         'csv_attachment_delimiter' => array(
            // tmp             'name'=> esc_html__( 'Custom delimiter', 'super-forms' ),
            // tmp             'desc' => esc_html__( 'Set a custom delimiter to separate the values on each row', 'super-forms' ), 
            // tmp             'default'=> ',',
            // tmp             'filter'=>true,
            // tmp             'parent'=>'csv_attachment_enable',
            // tmp             'filter_value'=>'true'
            // tmp         ),
            // tmp         'csv_attachment_enclosure' => array(
            // tmp             'name'=> esc_html__( 'Custom enclosure', 'super-forms' ),
            // tmp             'desc' => esc_html__( 'Set a custom enclosure character for values', 'super-forms' ), 
            // tmp             'default'=> '"',
            // tmp             'filter'=>true,
            // tmp             'parent'=>'csv_attachment_enable',
            // tmp             'filter_value'=>'true'
            // tmp         ),
            // tmp     )
            // tmp );
            return $array;
        }



    }
        
endif;


/**
 * Returns the main instance of SUPER_CSV_Attachment to prevent the need to use globals.
 *
 * @return SUPER_CSV_Attachment
 */
if( !function_exists('SUPER_CSV_Attachment') ){
    function SUPER_CSV_Attachment() {
        return SUPER_CSV_Attachment::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_CSV_Attachment'] = SUPER_CSV_Attachment();
}
