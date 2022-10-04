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

    public static function send_email($name, $x){
        error_log('Action `send_email` was executed, by trigger event `'.$name.'`');
        error_log(json_encode($x));
    }

}
endif;
