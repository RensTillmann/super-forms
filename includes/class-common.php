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
     * Get the author username by license
     *
     * @since 1.2.8
     */
    public static function get_author_by_license( $license=null ) {
        if($license==null){
            $settings = get_option( 'super_settings' );
            $license = $settings['license'];
        }
        $url = 'http://f4d.nl/super-forms/?api=get-license-author&key=' . $license;
        $response = wp_remote_get( $url, array('timeout'=>60) );
        return $response['body'];
    }


    /**
     * Return the dynamic functions (used to hook into javascript)
     *
     * @since 1.1.3
     */
    public static function get_dynamic_functions() {
        return apply_filters(
            'super_common_js_dynamic_functions_filter', 
            array(
                'before_validating_form_hook' => array(),
                'after_validating_form_hook' => array(),
                'after_initializing_forms_hook' => array(),
                'after_dropdown_change_hook' => array(),
                'after_field_change_blur_hook' => array(),
                'after_radio_change_hook' => array(),
                'after_checkbox_change_hook' => array(),
                
                // @since 1.2.8
                'after_email_send_hook' => array(),

                // @since 1.3
                'after_responsive_form_hook' => array(),
                'after_form_data_collected_hook' => array(),
                'after_duplicate_column_fields_hook' => array(),
 
                // @since 1.9
                'before_submit_button_click_hook' => array(),
                'after_preview_loaded_hook' => array(),

                // @since 2.0.0
                'after_form_cleared_hook' => array(),
                
                // @since 2.1.0
                'before_scrolling_to_error_hook' => array(),
                'before_scrolling_to_message_hook' => array(),
                
                // @since 2.4.0
                'after_duplicating_column_hook' => array(),

            )
        );
    }


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
            $msg = __( 'Something went wrong, try again!', 'super-forms' );
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
        
        /** 
         *  Make sure that we have all settings even if this form hasn't saved it yet when new settings where added by a add-on
         *
         *  @since      1.0.6
        */
        require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
        $fields = SUPER_Settings::fields( null, 1 );
        $array = array();
        
        foreach( $fields as $k => $v ) {
            if( !isset( $v['fields'] ) ) continue;
            foreach( $v['fields'] as $fk => $fv ) {
                if( ( isset( $fv['type'] ) ) && ( $fv['type']=='multicolor' ) ) {
                    foreach( $fv['colors'] as $ck => $cv ) {
                        if( !isset( $cv['default'] ) ) $cv['default'] = '';
                        $array[$ck] = $cv['default'];
                    }
                }else{
                    if( !isset( $fv['default'] ) ) $fv['default'] = '';
                    $array[$fk] = $fv['default'];
                }
            }
        }
        $settings = get_post_meta($id, '_super_form_settings', true );
        if( is_array( $settings ) ) {
            $settings = array_merge( $array, $settings );
        }else{
            $settings = array();
        }

        // @since 1.2.4     - added the form ID to the settings array
        $settings['id'] = $id;

        $elements = json_decode( get_post_meta( $id, '_super_elements', true ) );
        if( $elements!=null ) {
            foreach( $elements as $k => $v ) {
                echo SUPER_Shortcodes::output_builder_html( $v->tag, $v->group, $v->data, $v->inner, $shortcodes, $settings );
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
     * Generate random code
     *
     * @since 2.2.0
    */
    public static function generate_random_code($length, $characters, $prefix, $suffix, $upercase, $lowercase) {
        $char  = '';
        if( ($characters=='1') || ($characters=='2') || ($characters=='3') ) {
            $char .= '0123456789';
        }
        if( ($characters=='1') || ($characters=='2') || ($characters=='4') ) {
            if($upercase=='true') $char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            if($lowercase=='true') $char .= 'abcdefghijklmnopqrstuvwxyz';
        }
        if($characters=='2') {
            $char .= '!@#$%^&*()';
        }
        $charactersLength = strlen($char);
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $char[rand(0, $charactersLength - 1)];
        }

        // Now we have generated the code check if it already exists
        global $wpdb;
        $table = $wpdb->prefix . 'postmeta';
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE meta_key = '_super_contact_entry_code' AND meta_value = '$code'");
        if($exists==0){
            return $code;
        }else{
            return generate_random_code($length, $characters, $prefix, $suffix, $upercase, $lowercase);
        }
    }


    /**
     * Generate random folder number
     *
     * @since 1.0.0
    */
    public static function generate_random_folder( $folder ) {
        $number = rand( 100000000, 999999999 );
        $new_folder = $folder . '/' . $number;
        if( file_exists( $new_folder ) ) {
            self::generate_random_folder( $folder );
        }else{
            if( !file_exists( $new_folder ) ) {
                mkdir( $new_folder, 0755, true );
                return $new_folder;
            }else{
                return $new_folder;
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
            return nl2br( urldecode( stripslashes( $value ) ) );
        }
    }
    public static function decode( $value ) {
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            return urldecode( strip_tags( stripslashes( $value ) ) );
        }else{
            // @since 1.4 - also return integers
            return absint( $value );
        }
    }
    public static function decode_email_header( $value ) {
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            return urldecode( $value );
        }
    }


    /**
     * Create an array with tags that can be used in emails, this function also replaced tags when $value and $data are set
     *
     * @since 1.0.6
    */
    public static function email_tags( $value=null, $data=null, $settings=null, $user=null ) {
        global $post;
        if( !isset( $post ) ) {
            if( isset( $_REQUEST['post_id'] ) ) {
                $post_title = get_the_title( absint( $_REQUEST['post_id'] ) );
                $post_id = (string)$_REQUEST['post_id'];
            }else{
                $post_title = '';
                $post_id = '';
            }
        }else{
            $post_title = get_the_title($post->ID);
            $post_id = (string)$post->ID;
        }
        $current_user = wp_get_current_user();
        $tags = array(
            'field_*****' => array(
                __( 'Any field value submitted by the user', 'super-forms' ),
                ''
            ),
            'field_label_*****' => array(
                __( 'Any field value submitted by the user', 'super-forms' ),
                ''
            ),
            'option_admin_email' => array(
                __( 'E-mail address of blog administrator', 'super-forms' ),
                get_option('admin_email')
            ),
            'option_blogname' => array(
                __( 'Weblog title; set in General Options', 'super-forms' ),
                get_option('blogname')
            ),
            'option_blogdescription' => array(
                __( 'Tagline for your blog; set in General Options', 'super-forms' ),
                get_option('blogdescription')
            ),
            'option_blog_charset' => array(
                __( 'Blog Charset', 'super-forms' ),
                get_option('blog_charset')
            ),
            'option_date_format' => array(
                __( 'Date Format', 'super-forms' ),
                get_option('date_format')
            ),            
            'option_default_category' => array(
                __( 'Default post category; set in Writing Options', 'super-forms' ),
                get_option('default_category')
            ),
            'option_home' => array(
                __( 'The blog\'s home web address; set in General Options', 'super-forms' ),
                home_url()
            ),
            'option_siteurl' => array(
                __( 'WordPress web address; set in General Options', 'super-forms' ),
                get_option('siteurl')
            ),
            'option_template' => array(
                __( 'The current theme\'s name; set in Presentation', 'super-forms' ),
                get_option('template')
            ),
            'option_start_of_week' => array(
                __( 'Start of the week', 'super-forms' ),
                get_option('start_of_week')
            ),
            'option_upload_path' => array(
                __( 'Default upload location; set in Miscellaneous Options', 'super-forms' ),
                get_option('upload_path')
            ),
            'option_posts_per_page' => array(
                __( 'Posts per page', 'super-forms' ),
                get_option('posts_per_page')
            ),
            'option_posts_per_rss' => array(
                __( 'Posts per RSS feed', 'super-forms' ),
                get_option('posts_per_rss')
            ),
            'real_ip' => array(
                __( 'Retrieves the submitter\'s IP address', 'super-forms' ),
                self::real_ip()
            ),
            'loop_label' => array(
                __( 'Retrieves the field label for the field loop {loop_fields}', 'super-forms' ),
            ),
            'loop_value' => array(
                __( 'Retrieves the field value for the field loop {loop_fields}', 'super-forms' ),
            ),
            'loop_fields' => array(
                __( 'Retrieves the loop anywhere in your email', 'super-forms' ),
            ),
            'post_title' => array(
                __( 'Retreives the current page or post title', 'super-forms' ),
                $post_title
            ),
            'post_id' => array(
                __( 'Retreives the current page or post ID', 'super-forms' ),
                $post_id
            ),

            // @since 1.1.6
            'user_login' => array(
                __( 'Retreives the current logged in user login (username)', 'super-forms' ),
                $current_user->user_login
            ),
            'user_email' => array(
                __( 'Retreives the current logged in user email', 'super-forms' ),
                $current_user->user_email
            ),
            'user_firstname' => array(
                __( 'Retreives the current logged in user first name', 'super-forms' ),
                $current_user->user_firstname
            ),
            'user_lastname' => array(
                __( 'Retreives the current logged in user last name', 'super-forms' ),
                $current_user->user_lastname
            ),
            'user_display' => array(
                __( 'Retreives the current logged in user display name', 'super-forms' ),
                $current_user->display_name
            ),
            'user_id' => array(
                __( 'Retreives the current logged in user ID', 'super-forms' ),
                $current_user->ID
            ),

        );
        
        // Make sure to replace tags with correct user data
        if( $user!=null ) {
            $user_tags = array(
                'user_id' => array(
                    __( 'User ID', 'super-forms' ),
                    $user->ID
                ),
                'user_login' => array(
                    __( 'User username', 'super-forms' ),
                    $user->user_login
                ),
                'display_name' => array(
                    __( 'User display name', 'super-forms' ),
                    $user->user_nicename
                ),
                'user_nicename' => array(
                    __( 'User nicename', 'super-forms' ),
                    $user->user_nicename
                ),
                'user_email' => array(
                    __( 'User email', 'super-forms' ),
                    $user->user_email
                ),
                'user_url' => array(
                    __( 'User URL (website)', 'super-forms' ),
                    $user->user_url
                ),
                'user_registered' => array(
                    __( 'User Registered (registration date)', 'super-forms' ),
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
                    if( isset( $v['name'] ) ) {
                        if( isset( $v['label'] ) ) {
                            $value = str_replace( '{field_label_' . $v['name'] . '}', self::decode( $v['label'] ), $value );
                        }
                        if( isset( $v['value'] ) ) {
                            $value = str_replace( '{field_' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                        }
                    }
                }
            }

            // Now replace all the tags inside the value with the correct data
            foreach( $tags as $k => $v ) {
                if( isset( $v[1] ) ) {
                    $value = str_replace( '{'. $k .'}', self::decode( $v[1] ), $value );
                }
            }

            // Now loop again through all the data (submitted by the user)
            if( $data!=null ) {
                foreach( $data as $k => $v ) {
                    if( isset( $v['name'] ) ) {
                        if( isset( $v['value'] ) ) {
                            $value = str_replace( '{' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                        }
                    }
                }
            }

            // Now return the final output
            return $value;

        }
        return $tags;
    }

    /**
     * Remove directory and it's contents
     *
     * @since 1.1.8
    */
    public static function delete_dir($dir) {
        if ( (is_dir( $dir )) && (ABSPATH!=$dir) ) {
            if ( substr( $dir, strlen( $dir ) - 1, 1 ) != '/' ) {
                $dir .= '/';
            }
            $files = glob( $dir . '*', GLOB_MARK );
            foreach ( $files as $file ) {
                if ( is_dir( $file ) ) {
                    self::delete_dir( $file );
                } else {
                    unlink( $file );
                }
            }
            rmdir($dir);
        }
    }


    /**
     * Remove file
     *
     * @since 1.1.9
    */
    public static function delete_file($file) {
        if ( !is_dir( $file ) ) {
            unlink( $file );
        }
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
     * Convert HEX color to RGB color format
     *
     * @since 1.3
    */
    public static function hex2rgb( $hex, $opacity=1 ) {
        $hex = str_replace("#", "", $hex);

        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        $rgb = array($r, $g, $b, $opacity);
        return 'rgba(' . (implode(",", $rgb)) . ')'; // returns the rgb values separated by commas
        //return $rgb; // returns an array with the rgb values
    }


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

        $from = trim($from);
        $from_name = trim(preg_replace('/[\r\n]+/', '', $from_name)); //Strip breaks and trim
        $to = explode( ",", $to );
        $smtp_settings = get_option( 'super_settings' );
        if( !isset( $smtp_settings['smtp_enabled'] ) ) {
            $smtp_settings['smtp_enabled'] = 'disabled';
        }
        if( $smtp_settings['smtp_enabled']=='disabled' ) {
            $wpmail_attachments = array();
            foreach( $attachments as $k => $v ) {
                $v = str_replace(content_url(), '', $v);
                $wpmail_attachments[] = WP_CONTENT_DIR . $v;
            }
            $_SESSION['super_string_attachments'] = $string_attachments;

            $headers = explode( "\n", $settings['header_additional'] );
            $headers[] = "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"";
            if( empty( $from_name ) ) {
                $from_header = $from;
            }else{
                $from_header = $from_name . ' <' . $from . '>';
            }
            $headers[] = 'From: ' . $from_header;
            $headers[] = 'Reply-To: ' . $from_header;
            // Add CC
            if( !empty( $cc ) ) {
                $cc = explode( ",", $cc );
                foreach( $cc as $value ) {
                    $headers[] = 'Cc: ' . trim($value);
                }
            }
            // Add BCC
            if( !empty( $bcc ) ) {
                $bcc = explode( ",", $bcc );
                foreach( $bcc as $value ) {
                    $headers[] = 'Bcc: ' . trim($value);
                }
            }
            $result = wp_mail( $to, $subject, $body, $headers, $wpmail_attachments );
            $error = '';
            if($result==false){
                $error = 'Email could not be send through wp_mail()';
            }
            // Return
            return array( 'result'=>$result, 'error'=>$error, 'mail'=>null );
        }else{
            if ( !class_exists( 'PHPMailer' ) ) {
                require_once( 'phpmailer/class.phpmailer.php' );
                if( $smtp_settings['smtp_enabled']=='enabled' ) {
                    require_once( 'phpmailer/class.smtp.php' );
                }
            }
            $mail = new PHPMailer;

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
        
            // From
            $mail->setFrom($from, $from_name);

            // Add a recipient
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
                $v = str_replace(content_url(), '', $v);
                $mail->addAttachment( WP_CONTENT_DIR . $v );
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
}
endif;