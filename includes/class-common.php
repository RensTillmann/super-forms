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

if(!class_exists('SUPER_Common')) :

/**
 * SUPER_Common
 */
class SUPER_Common {
        
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
        return nl2br( urldecode( strip_tags( $value ) ) );
    }
    public static function decode( $value ) {
        return urldecode( strip_tags( $value ) );
    }
    public static function decode_email_header( $value ) {
        return urldecode( $value );
    }


    /**
     * Replaces the tags with the according user data
     *
     * @since 1.0.0
    */
    public static function replace_tag( $value, $data ) {
        foreach($data as $k => $v) {
            if((isset($v['name'])) && (isset($v['value']))){
                $value = str_replace('{field_'.$v['name'].'}', self::decode($v['value']), $value);
            }
        }
        $value = str_replace('{option_admin_email}', get_option('admin_email'), $value);  
        $value = str_replace('{option_blogname}', get_option('blogname'), $value);  
        $value = str_replace('{option_blogdescription}', get_option('blogdescription'), $value);  
        $value = str_replace('{option_blog_charset}', get_option('blog_charset'), $value);  
        $value = str_replace('{option_date_format}', get_option('date_format'), $value);  
        $value = str_replace('{option_default_category}', get_option('default_category'), $value);  
        $value = str_replace('{option_home}', home_url(), $value);  
        $value = str_replace('{option_siteurl}', get_option('siteurl'), $value);  
        $value = str_replace('{option_template}', get_option('template'), $value);  
        $value = str_replace('{option_start_of_week}', get_option('start_of_week'), $value);  
        $value = str_replace('{option_upload_path}', get_option('upload_path'), $value);  
        $value = str_replace('{option_posts_per_page}', get_option('posts_per_page'), $value);  
        $value = str_replace('{option_posts_per_rss}', get_option('posts_per_rss'), $value); 
        return $value;    
    }


    /**
     * Function to send email over SMTP
     *
     * @since 1.0.0
    */
    public static function authSendEmail( $from, $to, $subject, $message, $settings ) {

        $smtpServer = $settings['smtp_server'];  //ip address of the mail server.  This can also be the local domain name
        $port = $settings['smtp_port'];         // should be 25 by default, but needs to be whichever port the mail server will be using for smtp 
        $timeout = $settings['smtp_timeout'];    // typical timeout. try 45 for slow servers
        $username = $settings['smtp_username'];  // the login for your smtp
        $password = $settings['smtp_password'];  // the password for your smtp
        $localhost = $settings['smtp_host'];     // Defined for the web server.  Since this is where we are gathering the details for the email
        $newLine = "\r\n";                      // aka, carrage return line feed. var just for newlines in MS
        $content_type = $settings['header_content_type'];
        $header_additional = $settings['header_additional'];
        $secure = $settings['smtp_ssl'];         // change to 1 if your server is running under SSL    
        if($secure==1){
            $localhost = 'ssl://'.$localhost;
            $port = 465;
        }
        $smtpConnect = fsockopen($smtpServer, $port, $errno, $errstr, $timeout);
        $smtpResponse = fgets($smtpConnect, 4096);
        if(empty($smtpConnect)){
            $output = "Failed to connect: $smtpResponse";
            return $output;
        }else{
            $logArray['connection'] = "Connected: $smtpResponse";
        }

        //Request Auth Login
        fputs($smtpConnect,"AUTH LOGIN" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['authrequest'] = "$smtpResponse";

        //Send username
        fputs($smtpConnect, base64_encode($username) . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['authusername'] = "$smtpResponse";

        //Send password
        fputs($smtpConnect, base64_encode($password) . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['authpassword'] = "$smtpResponse";

        //Say Hello to SMTP
        fputs($smtpConnect, "HELO $localhost" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['heloresponse'] = "$smtpResponse";

        //Email From
        $pattern = '/([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' . '(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)/i';
        preg_match ($pattern, $from, $matches);
        $from_email = $matches[0];
        
        fputs($smtpConnect, "MAIL FROM: $from_email" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['mailfromresponse'] = "$smtpResponse";

        //Email To
        $pattern = '/([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' . '(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)/i';
        preg_match ($pattern, $to, $matches);
        $to_email = $matches[0];
        
        fputs($smtpConnect, "RCPT TO: $to_email" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['mailtoresponse'] = "$smtpResponse";

        //The Email
        fputs($smtpConnect, "DATA" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['data1response'] = "$smtpResponse";

        //Construct Headers
        $headers = "MIME-Version: 1.0" . $newLine;
        $headers .= "Content-type: text/$content_type; charset=UTF-8" . $newLine;
        $headers .= "To: $to" . $newLine;
        $headers .= "Reply-To: $from" . $newLine;
        $headers .= "From: $from" . $newLine;
        $headers .= $header_additional;
        $headers .= "X-Mailer: PHP/".phpversion();

        fputs($smtpConnect, "To: $to_email\nFrom: $from_email\nSubject: $subject\n$headers\n\n$message\n.\n");
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['data2response'] = "$smtpResponse";    

        // Say Bye to SMTP
        fputs($smtpConnect,"QUIT" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['quitresponse'] = "$smtpResponse";
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

}
endif;