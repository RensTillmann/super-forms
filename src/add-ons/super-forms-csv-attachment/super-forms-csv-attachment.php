<?php
/**
 * Super Forms - CSV Attachment
 *
 * @package   Super Forms - CSV Attachment
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - CSV Attachment
 * Description: Sends a CSV file with the form data to the admin email as an attachment
 * Version:     1.4.1
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
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_sending_email_attachments_filter', array( $this, 'add_csv_attachment' ), 10, 2 );
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
         * Hook into settings and add CSV Attachment settings
         *
         *  @since      1.0.0
        */
        public static function add_csv_attachment( $attachments, $data ) {
            if( (isset($data['settings']['csv_attachment_enable'])) && ($data['settings']['csv_attachment_enable']=='true') ) {
                if(!isset($data['settings']['csv_attachment_name'])) {
                    $csv_attachment_name = 'super-csv-attachment';
                }else{
                    // @since 1.1.2 - compatibility with {tags}
                    $csv_attachment_name = SUPER_Common::email_tags( $data['settings']['csv_attachment_name'], $data['data'], $data['settings'] );
                }
                if(!isset($data['settings']['csv_attachment_save_as'])) $data['settings']['csv_attachment_save_as'] = 'entry_value';
                if(!isset($data['settings']['csv_attachment_exclude'])) $data['settings']['csv_attachment_exclude'] = '';
                $excluded_fields = explode( "\n", $data['settings']['csv_attachment_exclude'] );

                $rows = array();
                foreach( $data['data'] as $k => $v ) {
                    if( !isset($v['name']) ) continue;
                    if( !in_array( $v['name'], $excluded_fields ) ) {
                        $rows[0][] = $k;
                    }
                }
                foreach( $data['data'] as $k => $v ) {
                     if( !isset($v['name']) ) continue;
                     if( !in_array( $v['name'], $excluded_fields ) ) {
                        if( (isset($v['type'])) && ($v['type'] == 'files') ) {
                            $files = '';
                            if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                                foreach( $v['files'] as $fk => $fv ) {
                                    if( $fk==0 ) {
                                        $files .= $fv['url'];
                                    }else{
                                        $files .= PHP_EOL . $fv['url'];
                                    }
                                }
                            }
                            $rows[1][] = $files;
                        }else{
                            if( !isset($v['value']) ) {
                                $rows[1][] = '';
                            }else{
                                if( ($data['settings']['csv_attachment_save_as']=='entry_value') && (isset($v['entry_value'])) ) {
                                    $v['value'] = $v['entry_value'];
                                }elseif( ($data['settings']['csv_attachment_save_as']=='confirm_email_value') && (isset($v['confirm_value'])) ) {
                                    $v['value'] = $v['confirm_value'];
                                }
                                $rows[1][] = stripslashes($v['value']);
                            }
                        }
                    }
                }
                try {
                    $d = wp_upload_dir();
                    $basename = sanitize_title_with_dashes($csv_attachment_name) . '.csv';
                    $filename = trailingslashit($d['path']) . $basename;
                    $fp = fopen( $filename, 'w' );
                    // @since 3.1.0 - write file header (byte order mark) for correct encoding to fix UTF-8 in Excel
                    $bom = apply_filters( 'super_csv_bom_header_filter', chr(0xEF).chr(0xBB).chr(0xBF) );
                    if(fwrite($fp, $bom)===false){
                        // Print error message
                        SUPER_Common::output_message(
                            $error = true,
                            "Unable to write to file ($filename)"
                        );
                    }
                    // @since 1.1.1 - custom settings for delimiter and enclosure
                    if(!isset($data['settings']['csv_attachment_delimiter'])) $data['settings']['csv_attachment_delimiter'] = ',';
                    if(!isset($data['settings']['csv_attachment_enclosure'])) $data['settings']['csv_attachment_enclosure'] = '"';
                    $delimiter = wp_unslash(sanitize_text_field($data['settings']['csv_attachment_delimiter']));
                    $enclosure = wp_unslash(sanitize_text_field($data['settings']['csv_attachment_enclosure']));
                    if(empty($delimiter)) $delimiter = ',';
                    if(empty($enclosure)) $enclosure = '"';
                    foreach ( $rows as $fields ) {
                        fputcsv( $fp, $fields, $delimiter, $enclosure, PHP_EOL);
                    }
                    fclose( $fp );
                    $attachment = array(
                        'post_mime_type' => 'text/csv',
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
                    add_post_meta($attachment_id, 'super-forms-form-upload-file', true);
                    $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
                    wp_update_attachment_metadata( $attachment_id,  $attach_data );
                    $attachments['csv-form-data.csv'] = wp_get_attachment_url( $attachment_id );
                } catch (Exception $e) {
                    // Print error message
                    SUPER_Common::output_message(
                        $error = true,
                        $e->getMessage()
                    );
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
                if ( $encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ) {
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
                'name' => esc_html__( 'CSV Attachment', 'super-forms' ),
                'label' => esc_html__( 'CSV Attachment Settings', 'super-forms' ),
                'fields' => array(
                    'csv_attachment_enable' => array(
                        'desc' => esc_html__( 'This will attach a CSV file to the admin email', 'super-forms' ), 
                        'default' => '',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Send CSV attachment with form data to the admin email', 'super-forms' ),
                        ),
                        'filter' => true
                    ),
                    'csv_attachment_name' => array(
                        'name'=> esc_html__( 'The filename of the attachment', 'super-forms' ),
                        'default'=> 'super-csv-attachment',
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),
                    'csv_attachment_save_as' => array(
                        'name'=> esc_html__( 'Choose what value to save for checkboxes & radio buttons', 'super-forms' ),
                        'desc'=> esc_html__( 'When editing a field you can change these settings', 'super-forms' ),
                        'default'=> 'admin_email_value',
                        'type'=>'select', 
                        'values'=>array(
                            'admin_email_value' => esc_html__( 'Save the admin email value (default)', 'super-forms' ),
                            'confirm_email_value' => esc_html__( 'Save the confirmation email value', 'super-forms' ),
                            'entry_value' => esc_html__( 'Save the entry value', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),
                    'csv_attachment_exclude' => array(
                        'name'=> esc_html__( 'Exclude fields from CSV file (put each field name on a new line)', 'super-forms' ),
                        'desc'=> esc_html__( 'When saving the CSV these fields will be excluded from the CSV file', 'super-forms' ),
                        'default'=> '',
                        'type'=>'textarea', 
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),

                    // @since 1.1.1 - custom settings for delimiter and enclosure
                    'csv_attachment_delimiter' => array(
                        'name'=> esc_html__( 'Custom delimiter', 'super-forms' ),
                        'desc' => esc_html__( 'Set a custom delimiter to seperate the values on each row', 'super-forms' ), 
                        'default'=> ',',
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),
                    'csv_attachment_enclosure' => array(
                        'name'=> esc_html__( 'Custom enclosure', 'super-forms' ),
                        'desc' => esc_html__( 'Set a custom enclosure character for values', 'super-forms' ), 
                        'default'=> '"',
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),
                )
            );
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
