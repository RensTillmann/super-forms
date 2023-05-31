<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if( !class_exists('SUPER_VCF_Attachment') ) :
    final class SUPER_VCF_Attachment {
        public $add_on_slug = 'vcf-attachment';
        public $add_on_name = 'VCF Attachment';
        protected static $_instance = null;
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct(){
            $this->init_hooks();
            do_action('super_vcf_attachment_loaded');
        }
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }
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
        private function init_hooks() {
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            add_filter( 'super_after_processing_files_data_filter', array( $this, 'create_vcard' ), 10, 2 );
        }
        public static function create_vcard($data, $atts){
            if(!empty($atts['settings']['vcard_enable'])){
                $atts['data'] = $data;
                if(empty($atts['settings']['vcard_name'])) {
                    $atts['settings']['vcard_name'] = '{first_name}-{last_name}-vcard';
                }
                $vcard_name = SUPER_Common::email_tags( $atts['settings']['vcard_name'], $atts['data'], $atts['settings'] );
                $vcard_content = SUPER_Common::email_tags( $atts['settings']['vcard_content'], $atts['data'], $atts['settings'] );
                try {
                    $value = array(
                        'label' => 'vCard:',
                        'name' => $vcard_name.'.vcf',
                        'value' => $vcard_name.'.vcf',
                        'type' => 'text/vcard'
                    );
                    unset($GLOBALS['super_upload_dir']);
                    add_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ));
                    if(empty($GLOBALS['super_upload_dir'])){
                        // upload directory is altered by filter: SUPER_Forms::filter_upload_dir()
                        $GLOBALS['super_upload_dir'] = wp_upload_dir();
                    }
                    $d = $GLOBALS['super_upload_dir'];
                    $basename = $vcard_name.'.vcf';
                    $filename = trailingslashit($d['path']) . $basename;
                    $file = fopen($filename, 'w');
                    $cardData = "BEGIN:VCARD\n";
                    $cardData .= trim($vcard_content);
                    $cardData .= "\nEND:VCARD";
                    fwrite($file, $cardData);
                    fclose($file);
                    // Add file to media library 
                    $attachment = array(
                        'post_mime_type' => 'text/vcard',
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    // Only insert attachment if we are in root directory
                    $is_secure_dir = substr($d['subdir'], 0, 3);
                    $wp_content_dir = str_replace(ABSPATH, "", WP_CONTENT_DIR);
                    if(strpos($d['subdir'], $wp_content_dir) === false){
                        $is_secure_dir = '/..'; // only to validate that this should be treated as a secure dir, it doesn't mean we go a directory up.
                    }
                    if($is_secure_dir==='/..' || $is_secure_dir==='../'){
                        // If secure upload, update URL:
                        $fileUrl = trailingslashit($d['baseurl']) . 'sfgtfi' . trailingslashit($d['subdir']) . $basename; 
                        $fileUrl = str_replace('../', '__/', $fileUrl); // replace `../` with `##/`
                        $value['url'] = $fileUrl;
                        $value['subdir'] = trailingslashit($d['subdir']) . $basename;
                        $value['path'] = $filename;
                    }else{
                        // Always unset after all elements have been processed
                        unset($GLOBALS['super_upload_dir']);
                        remove_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ));
                        $attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
                        add_post_meta($attachment_id, 'super-forms-form-upload-file', true);
                        $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
                        wp_update_attachment_metadata( $attachment_id,  $attach_data );
                        $value['url'] = wp_get_attachment_url( $attachment_id );
                        $value['attachment'] = $attachment_id;
                    }
                    $data['_vcard'] = array(
                        'label' => 'vCard',
                        'type' => 'files',
                        'files' => array($value)
                    );
                    // Exclude from Contact Entry?
                    if(strpos($atts['settings']['vcard_enable'], 'entry')===false){
                        $data['_vcard']['exclude_entry'] = 'true';
                    }
                    $exclude = 0;
                    if(strpos($atts['settings']['vcard_enable'], 'admin')===false && strpos($atts['settings']['vcard_enable'], 'confirm')===false){
                        $exclude = 2;
                    }
                    if(strpos($atts['settings']['vcard_enable'], 'admin')!==false && strpos($atts['settings']['vcard_enable'], 'confirm')===false){
                        $exclude = 1;
                    }
                    if(strpos($atts['settings']['vcard_enable'], 'admin')===false && strpos($atts['settings']['vcard_enable'], 'confirm')!==false){
                        $exclude = 3;
                    }
                    $data['_vcard']['exclude'] = $exclude;
                } catch (Exception $e) {
                    // Print error message
                    SUPER_Common::output_message( array(
                        'msg' => $e->getMessage()
                    ));
                }
            }
            return $data;
        }
        public static function add_settings( $array, $x ) {
            $array['vcard'] = array(        
                'hidden' => 'settings',
                'name' => esc_html__( 'vCard Attachment', 'super-forms' ),
                'label' => esc_html__( 'vCard Attachment Settings', 'super-forms' ),
                'fields' => array(
                    'vcard_enable' => array(
                        'desc' => esc_html__( 'Select to which E-mails to attach the vCard', 'super-forms' ), 
                        'default' => '',
                        'type' => 'checkbox',
                        'values' => array(
                            'admin' => esc_html__( 'Attach vCard to the admin E-mail', 'super-forms' ),
                            'confirm' => esc_html__( 'Attach vCard to the confirmation E-mail', 'super-forms' ),
                            'entry' => esc_html__( 'Attach vCard to Contact Entry', 'super-forms' ),
                        ),
                        'filter' => true
                    ),
                    'vcard_name' => array(
                        'name'=> esc_html__( 'vCard filename', 'super-forms' ),
                        'default'=> '{first_name}-{last_name}-vcard',
                        'filter'=>true,
                        'parent'=>'vcard_enable',
                        'filter_value'=>'admin,confirm,entry'
                    ),
                    'vcard_content' => array(
                        'name'=> esc_html__( 'vCard content', 'super-forms' ),
                        'label'=> sprintf( esc_html__( '%sClick here for a full list of vCard properties%s', 'super-forms' ), '<a href="https://en.wikipedia.org/wiki/VCard#Properties">', '</a>' ),
                        'default'=> "VERSION:4.0\nFN:{first_name} {last_name}\nGENDER:{gender}\nEMAIL;TYPE=work:{email}\nTEL;TYPE=cell:{phonenumber}",
                        'type'=>'textarea', 
                        'filter'=>true,
                        'parent'=>'vcard_enable',
                        'filter_value'=>'admin,confirm,entry'
                    )
                )
            );
            return $array;
        }
    }
endif;
if( !function_exists('SUPER_VCF_Attachment') ){
    function SUPER_VCF_Attachment() {
        return SUPER_VCF_Attachment::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_VCF_Attachment'] = SUPER_VCF_Attachment();
}