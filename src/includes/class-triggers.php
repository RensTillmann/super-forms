<?php
/**
 * Super Forms Triggers Class.
 *
 * @author      feeling4design
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Triggers
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Triggers' ) ) :

/**
 * SUPER_Triggers
 */
class SUPER_Triggers {

    public static function send_email($eventName, $actionName, $form_id, $x){
        error_log('Action `'.$actionName.'` was executed, by trigger event `'.$eventName.'` for fomr ID: `'.$form_id.'`');
        error_log(json_encode($x));
        // not used due to tinyMCE if($x['line_breaks']==='true') $x['body'] = nl2br($x['body']);
        $custom_reply = false;
        if(!empty($x['reply_to'])) $custom_reply = true;
        $settings = SUPER_Common::get_form_settings($form_id);
        $settings['header_additional'] = $x['headers'];
        $settings['header_content_type'] = $x['content_type'];
        $settings['header_charset'] = $x['charset'];

        // @TODO:
        //    'attachments'=>array(),
        //    'string_attachments'=>array()

        $mail = SUPER_Common::email(array(
            'to'=>$x['to'],
            'from'=>$x['from'],
            'reply'=>$x['reply_to'],
            'custom_reply'=>$custom_reply,
            'subject'=>$x['subject'],
            'body'=>$x['body'],
            'cc'=>$x['cc'],
            'bcc'=>$x['bcc'],
            'settings'=>$settings
        ));
        if($mail==false){
            SUPER_Common::output_message( array(
                'msg' => 'Super Forms E-mail trigger could not be send!',
                'form_id' => absint($form_id)
            ));
        }
    }

}
endif;
