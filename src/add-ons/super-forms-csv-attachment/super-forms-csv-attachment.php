<?php
/**
 * Super Forms - CSV Attachment
 *
 * @package   Super Forms - CSV Attachment
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2016 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - CSV Attachment
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Sends a CSV file with the form data to the admin email as an attachment
 * Version:     1.1.2
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_CSV_Attachment')) :


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
        public $version = '1.1.2';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'csv_attachment';
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
            
            register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
            
            // Filters since 1.0.0
            add_filter( 'super_after_activation_message_filter', array( $this, 'activation_message' ), 10, 2 );

            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
                add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );

                // Actions since 1.0.0
                add_action( 'init', array( $this, 'update_plugin' ) );

            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Actions since 1.0.0
                add_action( 'super_before_sending_email_attachments_filter', array( $this, 'add_csv_attachment' ), 10, 2 );

            }
            
        }

        
        /**
         * Automatically update plugin from the repository
         *
         *  @since      1.0.0
        */
        function update_plugin() {
            if( defined('SUPER_PLUGIN_DIR') ) {
                require_once ( SUPER_PLUGIN_DIR . '/includes/admin/update-super-forms.php' );
                $plugin_remote_path = 'http://f4d.nl/super-forms/';
                $plugin_slug = plugin_basename( __FILE__ );
                new SUPER_WP_AutoUpdate( $this->version, $plugin_remote_path, $plugin_slug, '', '', $this->add_on_slug );
            }
        }


        /**
         * Add the activation under the "Activate" TAB
         * 
         * @since       1.0.0
        */
        public function activation($array, $data) {
            if (method_exists('SUPER_Forms','add_on_activation')) {
                return SUPER_Forms::add_on_activation($array, $this->add_on_slug, $this->add_on_name);
            }else{
                return $array;
            }
        }


        /**  
         *  Deactivate
         *
         *  Upon plugin deactivation delete activation
         *
         *  @since      1.0.0
         */
        public static function deactivate(){
            if (method_exists('SUPER_Forms','add_on_deactivate')) {
                SUPER_Forms::add_on_deactivate(SUPER_CSV_Attachment()->add_on_slug);
            }
        }


        /**
         * Check license and show activation message
         * 
         * @since       1.0.0
        */
        public function activation_message( $activation_msg, $data ) {
            if (method_exists('SUPER_Forms','add_on_activation_message')) {
                $settings = $data['settings'];
                if( (isset($settings['csv_attachment_enable'])) && ($settings['csv_attachment_enable']=='true') ) {
                    return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
                }
            }
            return $activation_msg;
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

                // @since 1.1.1 - custom settings for delimiter and enclosure
                if(!isset($data['settings']['csv_attachment_delimiter'])) $data['settings']['csv_attachment_delimiter'] = ',';
                if(!isset($data['settings']['csv_attachment_enclosure'])) $data['settings']['csv_attachment_enclosure'] = '"';
                $delimiter = $data['settings']['csv_attachment_enclosure'];
                $enclosure = $data['settings']['csv_attachment_enclosure'];

                $rows = array();
                foreach( $data['data'] as $k => $v ) {
                    if( !in_array( $v['name'], $excluded_fields ) ) {
                        $rows[0][] = $k;
                    }
                }
                foreach( $data['data'] as $k => $v ) {
                     if( !in_array( $v['name'], $excluded_fields ) ) {
                        if( (isset($v['type'])) && ($v['type'] == 'files') ) {
                            $files = '';
                            if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                                foreach( $v['files'] as $fk => $fv ) {
                                    if( $fk==0 ) {
                                        $files .= $fv['url'];
                                    }else{
                                        $files .= "\n" . $fv['url'];
                                    }
                                }
                            }
                            $rows[$k+1][] = $files;
                        }else{
                            if( !isset($v['value']) ) {
                                $rows[$k+1][] = '';
                            }else{
                                if( ($data['settings']['csv_attachment_save_as']=='entry_value') && (isset($v['entry_value'])) ) {
                                    $v['value'] = $v['entry_value'];
                                }elseif( ($data['settings']['csv_attachment_save_as']=='confirm_email_value') && (isset($v['confirm_value'])) ) {
                                    $v['value'] = $v['confirm_value'];
                                }
                                $rows[$k+1][] = stripslashes($v['value']);
                            }
                        }
                    }
                }
                $file_location = '/uploads/php/files/' . sanitize_title_with_dashes($csv_attachment_name) . '.csv';
                $source = urldecode( SUPER_PLUGIN_DIR . $file_location );
                if( file_exists( $source ) ) {
                    SUPER_Common::delete_file( $source );
                }
                $fp = fopen( $source, 'w' );
                foreach ( $rows as $fields ) {
                    fputcsv( $fp, $fields, $delimiter, $enclosure );
                }
                fclose( $fp );
                $attachments['csv-form-data.csv'] = SUPER_PLUGIN_FILE . $file_location;
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
        public static function add_settings( $array, $settings ) {

            $array['csv_attachment'] = array(        
                'hidden' => 'settings',
                'name' => __( 'CSV Attachment', 'super-forms' ),
                'label' => __( 'CSV Attachment Settings', 'super-forms' ),
                'fields' => array(
                    'csv_attachment_enable' => array(
                        'desc' => __( 'This will attach a CSV file to the admin email', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'csv_attachment_enable', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Send CSV attachment with form data to the admin email', 'super-forms' ),
                        ),
                        'filter' => true
                    ),
                    'csv_attachment_name' => array(
                        'name'=> __( 'The filename of the attachment', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'csv_attachment_name', $settings['settings'], 'super-csv-attachment' ),
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),
                    'csv_attachment_save_as' => array(
                        'name'=> __( 'Choose what value to save for checkboxes & radio buttons', 'super-forms' ),
                        'desc'=> __( 'When editing a field you can change these settings', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'csv_attachment_save_as', $settings['settings'], 'admin_email_value' ),
                        'type'=>'select', 
                        'values'=>array(
                            'admin_email_value' => __( 'Save the admin email value (default)', 'super-forms' ),
                            'confirm_email_value' => __( 'Save the confirmation email value', 'super-forms' ),
                            'entry_value' => __( 'Save the entry value', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),
                    'csv_attachment_exclude' => array(
                        'name'=> __( 'Exclude fields from CSV file (put each field name on a new line)', 'super-forms' ),
                        'desc'=> __( 'When saving the CSV these fields will be excluded from the CSV file', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'csv_attachment_exclude', $settings['settings'], '' ),
                        'type'=>'textarea', 
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),

                    // @since 1.1.1 - custom settings for delimiter and enclosure
                    'csv_attachment_delimiter' => array(
                        'name'=> __( 'Custom delimiter', 'super-forms' ),
                        'desc' => __( 'Set a custom delimiter to seperate the values on each row' ), 
                        'default'=> SUPER_Settings::get_value( 0, 'csv_attachment_delimiter', $settings['settings'], ',' ),
                        'filter'=>true,
                        'parent'=>'csv_attachment_enable',
                        'filter_value'=>'true'
                    ),
                    'csv_attachment_enclosure' => array(
                        'name'=> __( 'Custom enclosure', 'super-forms' ),
                        'desc' => __( 'Set a custom enclosure character for values' ), 
                        'default'=> SUPER_Settings::get_value( 0, 'csv_attachment_enclosure', $settings['settings'], '"' ),
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
function SUPER_CSV_Attachment() {
    return SUPER_CSV_Attachment::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_CSV_Attachment'] = SUPER_CSV_Attachment();