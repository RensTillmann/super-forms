<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// if(!class_exists('SUPER_VCF_Card')) :


//     /**
//      * Main SUPER_VCF_Card Class
//      *
//      * @class SUPER_VCF_Card
//      * @version 1.0.0
//      */
//     final class SUPER_VCF_Card {
    
        
//         /**
//          * @var string
//          *
//          *  @since      1.0.0
//         */
//         public $version = '1.3.0';


//         /**
//          * @var string
//          *
//          *  @since      1.0.0
//         */
//         public $add_on_slug = 'vcf-card';
//         public $add_on_name = 'VCF Card';

        
//         /**
//          * @var SUPER_VCF_Card The single instance of the class
//          *
//          *  @since      1.0.0
//         */
//         protected static $_instance = null;

        
//         /**
//          * Main SUPER_VCF_Card Instance
//          *
//          * Ensures only one instance of SUPER_VCF_Card is loaded or can be loaded.
//          *
//          * @static
//          * @see SUPER_VCF_Card()
//          * @return SUPER_VCF_Card - Main instance
//          *
//          *  @since      1.0.0
//         */
//         public static function instance() {
//             if(is_null( self::$_instance)){
//                 self::$_instance = new self();
//             }
//             return self::$_instance;
//         }

        
//         /**
//          * SUPER_VCF_Card Constructor.
//          *
//          *  @since      1.0.0
//         */
//         public function __construct(){
//             $this->init_hooks();
//             do_action('SUPER_VCF_Card_loaded');
//         }

        
//         /**
//          * Define constant if not already set
//          *
//          * @param  string $name
//          * @param  string|bool $value
//          *
//          *  @since      1.0.0
//         */
//         private function define($name, $value){
//             if(!defined($name)){
//                 define($name, $value);
//             }
//         }

        
//         /**
//          * What type of request is this?
//          *
//          * string $type ajax, frontend or admin
//          * @return bool
//          *
//          *  @since      1.0.0
//         */
//         private function is_request($type){
//             switch ($type){
//                 case 'admin' :
//                     return is_admin();
//                 case 'ajax' :
//                     return defined( 'DOING_AJAX' );
//                 case 'cron' :
//                     return defined( 'DOING_CRON' );
//                 case 'frontend' :
//                     return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
//             }
//         }

        
//         /**
//          * Hook into actions and filters
//          *
//          *  @since      1.0.0
//         */
//         private function init_hooks() {
//             add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
//             if ( $this->is_request( 'admin' ) ) {
//                 add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
//             }
//             if ( $this->is_request( 'ajax' ) ) {
//                 add_action( 'super_before_sending_email_attachments_filter', array( $this, 'add_vcf_attachment' ), 10, 2 );
//             }
//         }


//         /**
//          * Load Localisation files.
//          * Note: the first-loaded translation file overrides any following ones if the same translation is present.
//          */
//         public function load_plugin_textdomain() {
//             $locale = apply_filters( 'plugin_locale', get_locale(), 'super-forms' );

//             load_textdomain( 'super-forms', WP_LANG_DIR . '/super-forms-' . $this->add_on_slug . '/super-forms-' . $this->add_on_slug . '-' . $locale . '.mo' );
//             load_plugin_textdomain( 'super-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
//         }

        
//         /**
//          * Hook into settings and add VCF Card settings
//          *
//          *  @since      1.0.0
//         */
//         public static function add_vcf_attachment( $attachments, $data ){
//             $formData = $data['data'];
//             $attachmentName = 'contact.vcf';
//             $cardData = null;
//             $cardData .= "BEGIN:VCARD\n";
//             $cardData .= "VERSION:2.1\n";
//             $cardData .= "FN:" . $formData['first_name']['value'] . "\n";
//             $cardData .= "EMAIL:" . $formData['email']['value'] . "\n";
//             $cardData .= "END:VCARD";
//             $filePath = '/.SUPER_FORMS_UPLOAD_DIR./' . $attachmentName;
//             $source = urldecode( SUPER_PLUGIN_DIR . $filePath );
//             if( file_exists( $source ) ) {
//                 SUPER_Common::delete_file( $source );
//             }
//             $fp = fopen( $source, "w" );
//             if($fp==false){
//                 SUPER_Common::output_message(
//                     $error = true,
//                     $msg = '<strong>Error:</strong> ' . esc_html__( 'Unable to write file', 'super-forms' ) . ' (' . $source . ')'
//                 );
//             }else{
//                 fwrite( $fp, $cardData );
//                 fclose( $fp );
//                 $attachments[$attachmentName] = SUPER_PLUGIN_FILE . $filePath;
//             }
//             return $attachments;
//         }

//         /**
//          * Formats a line (passed as a fields  array) as VCF and returns the VCF as a string.
//          *
//          *  @since      1.0.0
//         */
//         public static function array_to_vcf( array &$fields, $delimiter = ';', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false ) {
//             $delimiter_esc = preg_quote($delimiter, '/');
//             $enclosure_esc = preg_quote($enclosure, '/');
//             $output = array();
//             foreach ( $fields as $field ) {
//                 if ($field === null && $nullToMysqlNull) {
//                     $output[] = 'NULL';
//                     continue;
//                 }
//                 if ( $encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ) {
//                     $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
//                 }
//                 else {
//                     $output[] = $field;
//                 }
//             }
//             return implode( $delimiter, $output );
//         }


//         /**
//          * Hook into settings and add VCF Card settings
//          *
//          *  @since      1.0.0
//         */
//         public static function add_settings( $array, $x ) {
//             $array['vcf_attachment'] = array(        
//                 'hidden' => 'settings',
//                 'name' => esc_html__( 'VCF Card', 'super-forms' ),
//                 'label' => esc_html__( 'VCF Card Settings', 'super-forms' ),
//                 'fields' => array(
//                     'vcf_attachment_enable' => array(
//                         'desc' => esc_html__( 'This will attach a VCF file to the admin email', 'super-forms' ), 
//                         'default' => '',
//                         'type' => 'checkbox',
//                         'values' => array(
//                             'true' => esc_html__( 'Send VCF Card with form data to the admin email', 'super-forms' ),
//                         ),
//                         'filter' => true
//                     ),
//                     'vcf_attachment_name' => array(
//                         'name'=> esc_html__( 'The filename of the attachment', 'super-forms' ),
//                         'default'=> 'super-vcf-card',
//                         'filter'=>true,
//                         'parent'=>'vcf_attachment_enable',
//                         'filter_value'=>'true'
//                     ),
//                     'vcf_attachment_save_as' => array(
//                         'name'=> esc_html__( 'Choose what value to save for checkboxes & radio buttons', 'super-forms' ),
//                         'desc'=> esc_html__( 'When editing a field you can change these settings', 'super-forms' ),
//                         'default'=> 'admin_email_value',
//                         'type'=>'select', 
//                         'values'=>array(
//                             'admin_email_value' => esc_html__( 'Save the admin email value (default)', 'super-forms' ),
//                             'confirm_email_value' => esc_html__( 'Save the confirmation email value', 'super-forms' ),
//                             'entry_value' => esc_html__( 'Save the entry value', 'super-forms' ),
//                         ),
//                         'filter'=>true,
//                         'parent'=>'vcf_attachment_enable',
//                         'filter_value'=>'true'
//                     ),
//                     'vcf_attachment_exclude' => array(
//                         'name'=> esc_html__( 'Exclude fields from VCF file (put each field name on a new line)', 'super-forms' ),
//                         'desc'=> esc_html__( 'When saving the VCF these fields will be excluded from the VCF file', 'super-forms' ),
//                         'default'=> '',
//                         'type'=>'textarea', 
//                         'filter'=>true,
//                         'parent'=>'vcf_attachment_enable',
//                         'filter_value'=>'true'
//                     ),

//                     // @since 1.1.1 - custom settings for delimiter and enclosure
//                     'vcf_attachment_delimiter' => array(
//                         'name'=> esc_html__( 'Custom delimiter', 'super-forms' ),
//                         'desc' => esc_html__( 'Set a custom delimiter to seperate the values on each row', 'super-forms' ), 
//                         'default'=> ',',
//                         'filter'=>true,
//                         'parent'=>'vcf_attachment_enable',
//                         'filter_value'=>'true'
//                     ),
//                     'vcf_attachment_enclosure' => array(
//                         'name'=> esc_html__( 'Custom enclosure', 'super-forms' ),
//                         'desc' => esc_html__( 'Set a custom enclosure character for values', 'super-forms' ), 
//                         'default'=> '"',
//                         'filter'=>true,
//                         'parent'=>'vcf_attachment_enable',
//                         'filter_value'=>'true'
//                     ),
//                 )
//             );
//             return $array;
//         }



//     }
        
// endif;


// /**
//  * Returns the main instance of SUPER_VCF_Card to prevent the need to use globals.
//  *
//  * @return SUPER_VCF_Card
//  */
// if(!function_exists('SUPER_VCF_Card')){
//     function SUPER_VCF_Card() {
//         return SUPER_VCF_Card::instance();
//     }
//     // Global for backwards compatibility.
//     $GLOBALS['SUPER_VCF_Card'] = SUPER_VCF_Card();
// }
