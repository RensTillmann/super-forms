<?php
/**
 * Super Forms Common Class.
 *
 * @author      feeling4design
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Common
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Common' ) ) :

/**
 * SUPER_Common
 */
class SUPER_Common {

    /**
     * Returns error and success messages
     *
     *  @param  boolean  $error
     *  @param  varchar  $msg
     *  @param  varchar  $redirect
     *  @param  array    $fields
     *
     * @since 1.0.6
     */
    public static function output_error( $error=true, $msg='', $redirect=null, $fields=array() ) {        
        if( $msg=='' ) {
            $msg = __( 'Something went wrong, try again!', 'super' );
        }
        $result = array(
            'error' => $error,
            'msg' => $msg,
        );
        if( $redirect!=null ) {
            $result['redirect']= $redirect;
        }
        $result['fields'] = $fields;
        echo json_encode( $result );
        die();
    }

    /**
     * Output the form elements on the backend (create form page) to allow to edit the elements
     *
     *  @param  integer  $id
     *
     * @since 1.0.0
     */
    public static function generate_backend_elements( $id=null, $shortcodes=null ) {
        $elements = json_decode( get_post_meta( $id, '_super_elements', true ) );
        if( $elements!=null ) {
            foreach( $elements as $k => $v ) {
                echo SUPER_Shortcodes::output_builder_html( $v->tag, $v->group, $v->data, $v->inner, $shortcodes );
            }
        }
    }

    /**
	 * Return list with all posts filtered by specific post type
     *
     *  @param  string  $type
     *
     * @since 1.0.0
	 */
    public static function list_posts_by_type_array( $type ) {
        $list = array();
        $list[''] = '- Select a '.$type.' -';
        $args = array();
        $args['sort_order'] = 'ASC';
        $args['sort_column'] = 'post_title';
        $args['post_type'] = $type;
        $args['post_status'] = 'publish';
        $pages = get_pages($args); 
        if($pages!=false){
            foreach($pages as $page){
                $list[$page->ID] = $page->post_title;
            }
        }
        return $list;
	}
    
	/**
	 * Check if specific time can be found between a time range
     *
     * @since 1.0.0
	*/
    public static function check_time($t1, $t2, $tn, $opposite=false) {
        $t1 = +str_replace(":", "", $t1);
        $t2 = +str_replace(":", "", $t2);
        $tn = +str_replace(":", "", $tn);       
        if ($t2 >= $t1) {
            if($opposite==true){
                return $t1 < $tn && $tn < $t2;
            }else{
                return $t1 <= $tn && $tn < $t2;
            }
        } else {
            if($opposite==true){
                return ! ($t2 < $tn && $tn < $t1);
            }else{
                return ! ($t2 <= $tn && $tn < $t1);
            }
        }
    }
    

    /**
     * Get the IP address of the user that submitted the form
     *
     * @since 1.0.0
    */
    public static function real_ip() {
        foreach (array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ) as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }


    /**
     * Decodes the values of the submitted data
     *
     * @since 1.0.0
    */
    public static function decode_textarea( $value ) {
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            return nl2br( urldecode( strip_tags( stripslashes( $value ) ) ) );
        }
    }
    public static function decode( $value ) {
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            return urldecode( strip_tags( stripslashes( $value ) ) );
        }
    }
    public static function decode_email_header( $value ) {
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            return urldecode( $value );
        }
    }


    /**
     * Create an array with tags th$name, at can be used in emails, this function also replaced tags when $value and $data are set
     *
     * @since 1.0.6
    */
    public static function email_tags( $value=null, $data=null, $settings=null, $user=null ) {
        global $post;
        if( !isset( $post ) ) {
            $post_id = '';
        }else{
            $post_id = (string)$post->ID;
        }
        $tags = array(
            'field_*****' => array(
                __( 'Any field value submitted by the user', 'super' ),
                ''
            ),
            'option_admin_email' => array(
                __( 'E-mail address of blog administrator', 'super' ),
                get_option('admin_email')
            ),
            'option_blogname' => array(
                __( 'Weblog title; set in General Options', 'super' ),
                get_option('blogname')
            ),
            'option_blogdescription' => array(
                __( 'Tagline for your blog; set in General Options', 'super' ),
                get_option('blogdescription')
            ),
            'option_blog_charset' => array(
                __( 'Blog Charset', 'super' ),
                get_option('blog_charset')
            ),
            'option_date_format' => array(
                __( 'Date Format', 'super' ),
                get_option('date_format')
            ),            
            'option_default_category' => array(
                __( 'Default post category; set in Writing Options', 'super' ),
                get_option('default_category')
            ),
            'option_home' => array(
                __( 'The blog\'s home web address; set in General Options', 'super' ),
                home_url()
            ),
            'option_siteurl' => array(
                __( 'WordPress web address; set in General Options', 'super' ),
                get_option('siteurl')
            ),
            'option_template' => array(
                __( 'The current theme\'s name; set in Presentation', 'super' ),
                get_option('template')
            ),
            'option_start_of_week' => array(
                __( 'Start of the week', 'super' ),
                get_option('start_of_week')
            ),
            'option_upload_path' => array(
                __( 'Default upload location; set in Miscellaneous Options', 'super' ),
                get_option('upload_path')
            ),
            'option_posts_per_page' => array(
                __( 'Posts per page', 'super' ),
                get_option('posts_per_page')
            ),
            'option_posts_per_rss' => array(
                __( 'Posts per RSS feed', 'super' ),
                get_option('posts_per_rss')
            ),
            'real_ip' => array(
                __( 'Retrieves the submitter\'s IP address', 'super' ),
                self::real_ip()
            ),
            'loop_label' => array(
                __( 'Retrieves the field label for the field loop {loop_fields}', 'super' ),
            ),
            'loop_value' => array(
                __( 'Retrieves the field value for the field loop {loop_fields}', 'super' ),
            ),
            'loop_fields' => array(
                __( 'Retrieves the loop anywhere in your email', 'super' ),
            ),
            'post_title' => array(
                __( 'Retreives the current page or post title', 'super' ),
                get_the_title()
            ),
            'post_id' => array(
                __( 'Retreives the current page or post ID', 'super' ),
                $post_id
            ),            
        );
        
        // Make sure to replace tags with correct user data
        if( $user!=null ) {
            $user_tags = array(
                'user_id' => array(
                    __( 'User ID', 'super' ),
                    $user->ID
                ),
                'user_login' => array(
                    __( 'User username', 'super' ),
                    $user->user_login
                ),
                'display_name' => array(
                    __( 'User display name', 'super' ),
                    $user->user_nicename
                ),
                'user_nicename' => array(
                    __( 'User nicename', 'super' ),
                    $user->user_nicename
                ),
                'user_email' => array(
                    __( 'User email', 'super' ),
                    $user->user_email
                ),
                'user_url' => array(
                    __( 'User URL (website)', 'super' ),
                    $user->user_url
                ),
                'user_registered' => array(
                    __( 'User Registered (registration date)', 'super' ),
                    $user->user_registered
                )
            );
            $tags = array_merge( $tags, $user_tags );
        }

        $tags = apply_filters( 'super_email_tags_filter', $tags );
        
        // Return the new value with tags replaced for data
        if( $value!=null ) {

            // First loop through all the data (submitted by the user)
            if( $data!=null ) {
                foreach( $data as $k => $v ) {
                    if( ( isset( $v['name'] ) ) && ( isset( $v['value'] ) ) ) {
                        $value = str_replace( '{field_' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                    }
                }
            }

            // Now replace all the tags inside the value with the correct data
            foreach( $tags as $k => $v ) {
                if( isset( $v[1] ) ) {
                    $value = str_replace( '{'. $k .'}', self::decode( $v[1] ), $value );
                }
            }

            // Now return the final output
            return $value;

        }
        return $tags;
    }


    /**
     * Replaces the tags with the according user data
     *
     * @since 1.0.0
     * @deprecated since version 1.0.6
     *
     * public static function replace_tag( $value, $data )
    */


    /**
     * Function to send email over SMTP
     *
     * authSendEmail()
     *
     * @since 1.0.0
     * @deprecated since version 1.0.6
    */


    /**
     * Adjust the brightness of any given color (used for our focus and hover colors)
     *
     * @since 1.0.0
    */
    public static function adjust_brightness( $hex, $steps ) {
        
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Format the hex color string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }

        // Get decimal values
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));

        // Adjust number of steps and keep it inside 0 to 255
        $r = max(0,min(255,$r + $steps));
        $g = max(0,min(255,$g + $steps));  
        $b = max(0,min(255,$b + $steps));

        $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return '#'.$r_hex.$g_hex.$b_hex;
    }


    /**
     * Send emails
     *
     * @since 1.0.6
    */
    public static function email( $to, $from, $from_name, $cc, $bcc, $subject, $body, $settings, $attachments=array(), $string_attachments=array() ) {
        
        $smtp_settings = get_option( 'super_settings' );
        if( !isset( $smtp_settings['smtp_enabled'] ) ) {
            $smtp_settings['smtp_enabled'] = 'disabled';
        }
        if ( !class_exists( 'PHPMailer' ) ) {
            require_once( 'phpmailer/class.phpmailer.php' );
            if( $smtp_settings['smtp_enabled']=='enabled' ) {
                require_once( 'phpmailer/class.smtp.php' );
            }
        }
        $mail = new PHPMailer;

        if( $smtp_settings['smtp_enabled']=='enabled' ) {
            
            // Set mailer to use SMTP
            $mail->isSMTP();

            // Specify main and backup SMTP servers
            $mail->Host = $smtp_settings['smtp_host'];
            
            // Enable SMTP authentication
            if( $smtp_settings['smtp_auth']=='enabled' ) {
                $mail->SMTPAuth = true;
            }

            // SMTP username
            $mail->Username = $smtp_settings['smtp_username'];

            // SMTP password
            $mail->Password = $smtp_settings['smtp_password'];  

            // Enable TLS encryption
            if( $smtp_settings['smtp_secure']!='' ) {
                $mail->SMTPSecure = $smtp_settings['smtp_secure']; 
            }

            // TCP port to connect to
            $mail->Port = $smtp_settings['smtp_port'];

            // Set Timeout
            $mail->Timeout = $smtp_settings['smtp_timeout'];

            // Set keep alive
            if( $smtp_settings['smtp_keep_alive']=='enabled' ) {
                $mail->SMTPKeepAlive = true;
            }

            // Set debug
            if( $smtp_settings['smtp_debug'] != 0 ) {
                $mail->SMTPDebug = $smtp_settings['smtp_debug'];
                $mail->Debugoutput = $smtp_settings['smtp_debug_output_mode'];

            }
        }

        // From
        $mail->setFrom($from, $from_name);

        // Add a recipient
        $to = explode( ",", $to );  
        foreach( $to as $value ) {
            $mail->addAddress($value); // Name 'Joe User' is optional
        }

        // Reply To
        $mail->addReplyTo($from, $from_name);

        // Add CC
        if( !empty( $cc ) ) {
            $cc = explode( ",", $cc );
            foreach( $cc as $value ) {
                $mail->addCC($value);
            }
        }

        // Add BCC
        if( !empty( $bcc ) ) {
            $bcc = explode( ",", $bcc );
            foreach( $bcc as $value ) {
                $mail->addBCC($value);
            }
        }

        // Custom headers
        if( !empty( $settings['header_additional'] ) ) {
            $headers = explode( "\n", $settings['header_additional'] );   
            foreach( $headers as $k => $v ) {
                $this->addCustomHeader($v);
            }
        }

        // Add attachment(s)
        foreach( $attachments as $k => $v ) {
            $path = str_replace( "https://", "http://", SUPER_PLUGIN_FILE );
            $v = str_replace( "https://", "http://", $v );
            $v = str_replace( $path, "", $v );
            $v = SUPER_PLUGIN_DIR . '/' . $v;
            $v = rawurldecode($v);
            $mail->addAttachment( $v, $k );
        }

        // Add string attachment(s)
        foreach( $string_attachments as $v ) {
            $mail->AddStringAttachment( $v['data'], $v['filename'], $v['encoding'], $v['type'] );
        }

        // Set email format to HTML
        if( $settings['header_content_type'] == 'html' ) {
            $mail->isHTML(true);
        }else{
            $mail->isHTML(false);
        }

        // CharSet
        if( !isset( $settings['header_charset'] ) ) $settings['header_charset'] = 'UTF-8';
        $mail->CharSet = $settings['header_charset'];

        // Content-Type
        //$mail->ContentType = 'multipart/mixed';

        // Content-Transfer-Encoding
        // Options: "8bit", "7bit", "binary", "base64", and "quoted-printable".
        //$mail->Encoding = 'base64';

        // Subject
        $mail->Subject = $subject;

        // Body
        $mail->Body = $body;

        // Send the email
        $result = $mail->send();

        // Explicit call to smtpClose() when keep alive is enabled
        if( $mail->SMTPKeepAlive==true ) {
            $mail->SmtpClose();
        }

        // Return
        return array( 'result'=>$result, 'error'=>$mail->ErrorInfo, 'mail'=>$mail );

    }

}
endif;