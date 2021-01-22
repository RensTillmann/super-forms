<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Recorder')) :
    final class SUPER_Recorder {
        protected static $_instance = null;
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct(){
            $this->init_hooks();
        }
        private function init_hooks() {
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_element' ), 10, 2 );
        }
        public static function add_element( $array, $attributes ) {
            // Include the predefined arrays
            require( SUPER_PLUGIN_DIR . '/includes/shortcodes/predefined-arrays.php' );
            $array['form_elements']['shortcodes']['audio_recorder_predefined'] = array(
                'name' => esc_html__( 'Audio recorder', 'super-forms' ),
                'icon' => 'microphone-alt',
                'predefined' => array(
                    array(
                        'tag' => 'file',
                        'group' => 'form_elements',
                        'data' => array(
                            'name' => esc_html__( 'audio_recorder', 'super-forms' ),
                            'email' => esc_html__( 'Recorded audio:', 'super-forms' ),
                            'type' => 'audio', // 'normal' // 'audio-plus-video' // 'audio' // 'screen' // 'audio-plus-screen'
                            'icon' => 'microphone-alt',
                            'minlength' => '1',
                            'error_position' => 'bottom-left'
                        )
                    )
                ),
                'atts' => array(),
            );
            return $array;
        }
    }
endif;

if(!function_exists('SUPER_Recorder')){
    function SUPER_Recorder() {
        return SUPER_Recorder::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Recorder'] = SUPER_Recorder();
}
