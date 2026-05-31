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
    
    public static function startClientSession( $x=array() ){
        extract( 
            shortcode_atts( 
                array( 
                    'force' => false,
                    'id' => false,
                    'secure' => false,
                    'httponly' => true,
                    'expires' => 60*60, //3600, // Defaults to 60 min. (60*60)
                    'exp_var' => 20*60, //1200 // Defaults to 20 min. (20*60)
                    'update_option' => true // by default it is set to true
                ), $x 
            )
        );
        if($force===false){
            // Only retrieve settings from front-end
            // Always use sessions for back-end (used for displaying update notifications)
            if( (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON') ){
                $global_settings = SUPER_Common::get_global_settings();
                if( isset($global_settings['allow_storing_cookies']) && $global_settings['allow_storing_cookies']=='0'){
                    // Do not set cookie
                    return false;
                }
            }
        }

        // $exp_var is used to only extend expiry of the cookie when `time() > $exp_var`
        // that way we don't have to write to the database that many times
        // by default the expiry is set to 1 hour, and the expiry variant is set to 30 min.
		$expires = apply_filters( 'super_cookie_expires_filter', $expires);
		$exp_var = apply_filters( 'super_cookie_exp_var_filter', $exp_var);
        $now = time();
        $expires = $now + $expires;
        $exp_var = $now + $exp_var;
		// Returns true if the page is using SSL (checks if HTTPS or on Port 443).
		// NB: this won’t work for websites behind some load balancers, especially Network Solutions hosted websites. To body up a fix, save this gist into the plugins folder and enable it. For details, read WordPress is_ssl() doesn’t work behind some load balancers.
		// Websites behind load balancers or reverse proxies that support HTTP_X_FORWARDED_PROTO can be fixed by adding the following code to the wp-config.php file, above the require_once call:
		// `if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') $_SERVER['HTTPS'] = 'on';`
		if(is_ssl()) $secure = true;
        // Allow devs to override these settings
		$secure = apply_filters('super_cookie_secure_filter', $secure);
		$httponly = apply_filters('super_cookie_httponly_filter', $httponly);

        $cookieName = '_sfs_id';
        if(isset($_COOKIE[$cookieName])) {
            // If cookie already exists, check if we need to extend expiry
            // First grab the cookie ID
            $id = $_COOKIE[$cookieName];
            // Now lookup this ID in the database
            $clientData = get_option( '_sfsdata_' . $id, false );
            if($clientData!==false){
                if($now > $clientData['exp_var']){
                    // We will want to extend expiration for this cookie
                    if(!headers_sent()){
                        @setcookie( $cookieName, $id, $expires, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly );
                        if($update_option) {
                            update_option( '_sfsdata_' . $id, array('expires'=>$expires, 'exp_var'=>$exp_var), 'no' );
                        }
                    }
                }
            }else{
                if($update_option) {
                    update_option( '_sfsdata_' . $id, array('expires'=>$expires, 'exp_var'=>$exp_var), 'no' );
                }
            }
        }else{
            if(!headers_sent()){
                $id = md5(uniqid(mt_rand(), true)) . $now;
                // We can only set a cookie when headers are not sent prior anyways
                @setcookie( $cookieName, $id, $expires, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly );
                if($update_option) {
                    update_option( '_sfsdata_' . $id, array('expires'=>$expires, 'exp_var'=>$exp_var), 'no' );
                }
            }
        }
        return $id;
    }
    public static function setClientData( $x ) {
        extract( 
            shortcode_atts( 
                array( 
                    'name' => 'undefined', 
                    'value' => '',
                    'expires' => 30*60, //1800, // Defaults to 30 min. (30*60)
                    'exp_var' => 10*60 //600 // Defaults to 10 min. (10*60)
                ), $x 
            )
        );
        // $exp_var is used to only extend expiry of the cookie when `time() > $exp_var`
        // that way we don't have to write to the database that many times
        // by default the expiry is set to 30 min., and the expiry variant is set to 10 min.

        // Default expiry filter
		$expires = apply_filters( 'super_client_data_expires_filter', $expires );
		$exp_var = apply_filters( 'super_client_data_exp_var_filter', $exp_var );

        // Allow expiry filtering for specific client data
		$expires = apply_filters( 'super_client_data_' . $name . '_expires_filter', $expires ); // e.g: `progress_1234`_expires_filter
		$exp_var = apply_filters( 'super_client_data_' . $name . '_exp_var_filter', $exp_var ); // e.g: `progress_1234`_exp_var_filter

        $now = time();
        $force = false;
        if($name==='sf_nonce') $force = true;
        $key = self::startClientSession(array('force'=>$force));
        if($key===false) return;
        if($key==='') return;
        $clientData = get_option( '_sfsdata_' . $key );
        if($value===false){
            // Unset client data
            if($clientData!==false){
                unset($clientData[$name]);
                if(count($clientData)<3){
                    // If empty, we can delete it, to clean it up
                    delete_option( '_sfsdata_' . $key );
                    return;
                }
            }
        }
        $clientData[$name] = array(
            'expires'=>$now + $expires,
            'exp_var'=>$now + $exp_var,
            'value'=>$value
        );
        // Cleanup old client data
        self::cleanupOldClientData($key, $clientData);
    }
    public static function cleanupOldClientData($key, $clientData) {
        $now = time();
        foreach($clientData as $name => $data){
            if(is_array($data)){
                if($data['expires'] < $now){
                    unset($clientData[$name]);
                }else{
                    if($data['exp_var'] < $now){
                        // Default expiry filter
                        $expires = apply_filters( 'super_client_data_expires_filter', 30*60 ); //1800, // Defaults to 30 min. (30*60)
                        $exp_var = apply_filters( 'super_client_data_exp_var_filter', 10*60 ); //600 // Defaults to 10 min. (10*60)
                        // Allow expiry filtering for specific client data
                        $expires = apply_filters( 'super_client_data_' . $name . '_expires_filter', $expires ); // e.g: `progress_1234`_expires_filter
                        $exp_var = apply_filters( 'super_client_data_' . $name . '_exp_var_filter', $exp_var ); // e.g: `progress_1234`_exp_var_filter
                        $clientData[$name]['expires'] = $now + $expires;
                        $clientData[$name]['exp_var'] = $now + $exp_var;
                    }
                }
                continue;
            }
        }
        if(count($clientData)<3){
            delete_option( '_sfsdata_' . $key );
            return;
        }else{
            if($clientData['expires'] < $now){
                delete_option( '_sfsdata_' . $key );
            }else{
                if(!headers_sent()){
                    if($clientData['exp_var'] < $now){
                        $expires = apply_filters( 'super_cookie_expires_filter', 60*60); //3600, // Defaults to 60 min. (60*60)
                        $exp_var = apply_filters( 'super_cookie_exp_var_filter', 20*60); //1200 // Defaults to 20 min. (20*60)
                        if(is_ssl()) $secure = true;
                        $secure = apply_filters('super_cookie_secure_filter', $secure);
                        $httponly = apply_filters('super_cookie_httponly_filter', $httponly);
                        $clientData['expires'] = $now + $expires;
                        $clientData['exp_var'] = $now + $exp_var;
                        $cookieName = '_sfs_id';
                        @setcookie( $cookieName, $key, $clientData['expires'], COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly );
                    }
                }
            }
        }
        update_option( '_sfsdata_' . $key, $clientData, 'no' );
    }

    public static function getClientData( $name ) {
        $force = false;
        if($name==='sf_nonce') $force = true;
        $cookieName = '_sfs_id';
        if(!isset($_COOKIE[$cookieName])) return false;
        $key = $_COOKIE[$cookieName];
        if($key===false) return false;
        if($key==='') return false;
        $clientData = get_option( '_sfsdata_' . $key );
        if(!isset($clientData[$name])) return false;
        if(!isset($clientData[$name]['value'])) return false;
        // If expired variation is reached, extend it
        $now = time();
        if($clientData[$name]['exp_var'] < $now){
            // Default expiry filter
            $expires = apply_filters( 'super_client_data_expires_filter', 30*60 ); //1800, // Defaults to 30 min. (30*60)
            $exp_var = apply_filters( 'super_client_data_exp_var_filter', 10*60 ); //600 // Defaults to 10 min. (10*60)
            // Allow expiry filtering for specific client data
            $expires = apply_filters( 'super_client_data_' . $name . '_expires_filter', $expires ); // e.g: `progress_1234`_expires_filter
            $exp_var = apply_filters( 'super_client_data_' . $name . '_exp_var_filter', $exp_var ); // e.g: `progress_1234`_exp_var_filter
            $clientData[$name]['expires'] = $now + $expires;
            $clientData[$name]['exp_var'] = $now + $exp_var;
            if(!headers_sent()){
                $expires = apply_filters( 'super_cookie_expires_filter', 60*60); //3600, // Defaults to 60 min. (60*60)
                $exp_var = apply_filters( 'super_cookie_exp_var_filter', 20*60); //1200 // Defaults to 20 min. (20*60)
                if(is_ssl()) $secure = true;
                $secure = apply_filters('super_cookie_secure_filter', $secure);
                $httponly = apply_filters('super_cookie_httponly_filter', $httponly);
                $clientData['expires'] = $now + $expires;
                $clientData['exp_var'] = $now + $exp_var;
                $cookieName = '_sfs_id';
                @setcookie( $cookieName, $key, $clientData['expires'], COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly );
            }
            update_option( '_sfsdata_' . $key, $clientData, 'no' );
        }
        return $clientData[$name]['value'];
    }

    public static function deleteOldClientData($limit=0) {
		global $wpdb;
        if($limit===0) $limit = 10; // Defaults to 100
        $limit = apply_filters( 'super_client_data_delete_limit_filter', absint($limit) ); // It's technically called a `Cookie name`, but we call it `key` here
        // Delete old deprecated sessions from previous Super Forms versions
        $now = time();
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_super\_session\_%' LIMIT 5000");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_sfs\_%' LIMIT 5000");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_sfsdata\_%' AND SUBSTRING_INDEX(SUBSTRING_INDEX(option_value, ';', 2), ':', -1) < {$now}");
	}

    public static function generate_nonce(){
        // Destroy old nonce, and generate new one
        SUPER_Common::setClientData( array( 'name'=> 'sf_nonce', 'value'=>false ) );
        $sf_nonce = md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true));
        SUPER_Common::setClientData( 
            array( 
                'name' => 'sf_nonce', 
                'value' => $sf_nonce,
                'expires' => 30, // nonce will expire after 30 sec. by default
                'exp_var' => 60*60 // there is no need to refresh a nonce, so we set it's expire variant to a higher value
            )
        );
        return $sf_nonce;
    }

    public static function verifyCSRF(){
        $sf_nonce = SUPER_Common::getClientData( 'sf_nonce' );
        $v = filter_input(INPUT_POST, 'sf_nonce', FILTER_SANITIZE_STRING);
        if(!$v || $v !== $sf_nonce){
            return false; // invalid
        }
        return true; // valid
    }

    public static function reset_setting_icons($v){
        $html  = '<div class="super-reset-settings-buttons">';
        if(!isset($v['v'])) $v['v'] = ''; // $v['default'];
        if(!isset($v['g'])) $v['g'] = ''; // $v['default'];
        if($v['default']==='_reset_'){
            $html .= '<i class="fas fa-undo-alt super-reset-default-value" title="' . esc_html__( 'Reset all to default value', 'super-forms' ) . '" data-value="'.esc_attr($v['default']).'"></i>';
            $html .= '<i class="fas fa-history super-reset-last-value" title="' . esc_html__( 'Reset all to last known value', 'super-forms' ) . '" data-value="'.esc_attr($v['v']).'"></i>';
            $html .= '<i class="fas fa-globe super-reset-global-value" title="' . esc_html__( 'Reset all to global value', 'super-forms' ) . '" data-value="'.esc_attr($v['g']).'"></i>';
            $html .= '<i class="fas fa-lock super-lock-global-setting" title="' . esc_html__( 'Lock all to global settings', 'super-forms' ) . '" data-value="'.esc_attr($v['g']).'"></i>';
        }else{
            $html .= '<i class="fas fa-undo-alt super-reset-default-value" title="' . esc_html__( 'Reset to default value', 'super-forms' ) . '" data-value="'.esc_attr($v['default']).'"></i>';
            $html .= '<i class="fas fa-history super-reset-last-value" title="' . esc_html__( 'Reset to last known value', 'super-forms' ) . '" data-value="'.esc_attr($v['v']).'"></i>';
            $html .= '<i class="fas fa-globe super-reset-global-value" title="' . esc_html__( 'Reset to global value', 'super-forms' ) . '" data-value="'.esc_attr($v['g']).'"></i>';
            $html .= '<i class="fas fa-lock super-lock-global-setting" title="' . esc_html__( 'Lock to global settings', 'super-forms' ) . '" data-value="'.esc_attr($v['g']).'"></i>';
        }
        $html .= '</div>';
        return $html;
    }
    public static function load_google_fonts($settings){
        // Import fonts
        $v = $settings;
        $v = array_filter($settings);
        $global_settings = SUPER_Common::get_global_settings();
        $v = array_merge($global_settings, $v);
        // Google fonts
        if( !isset( $v['font_google_fonts'] ) ) $v['font_google_fonts'] = '';
        $import_fonts = ""; // example: "@import url('https://fonts.googleapis.com/css2?family=PT+Sans&family=Roboto&display=swap');\n";
        if($v['font_google_fonts']!=''){
            $google_fonts = explode( "\n", $v['font_google_fonts'] );  
            foreach( $google_fonts as $font ) {
                //$import_fonts .= "@import url('".$font."');\n";
                $import_fonts .= '<link href="'.$font.'" rel="stylesheet">';
            }
        }
        $html = '';
        if(!empty($import_fonts)){
            $html .= '<link rel="preconnect" href="https://fonts.googleapis.com">';
            $html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
            $html .= $import_fonts;
        }
        return $html;
    }
     
    // @since 4.7.7 - US states (currently used by dropdown element only)
    public static function us_states(){
        return array( 'Alabama'=>'AL', 'Alaska'=>'AK', 'Arizona'=>'AZ', 'Arkansas'=>'AR', 'California'=>'CA', 'Colorado'=>'CO', 'Connecticut'=>'CT', 'Delaware'=>'DE', 'District of Columbia'=>'DC', 'Florida'=>'FL', 'Georgia'=>'GA', 'Hawaii'=>'HI', 'Idaho'=>'ID', 'Illinois'=>'IL', 'Indiana'=>'IN', 'Iowa'=>'IA', 'Kansas'=>'KS', 'Kentucky'=>'KY', 'Louisiana'=>'LA', 'Maine'=>'ME', 'Maryland'=>'MD', 'Massachusetts'=>'MA', 'Michigan'=>'MI', 'Minnesota'=>'MN', 'Mississippi'=>'MS', 'Missouri'=>'MO', 'Montana'=>'MT', 'Nebraska'=>'NE', 'Nevada'=>'NV', 'New Hampshire'=>'NH', 'New Jersey'=>'NJ', 'New Mexico'=>'NM', 'New York'=>'NY', 'North Carolina'=>'NC', 'North Dakota'=>'ND', 'Ohio'=>'OH', 'Oklahoma'=>'OK', 'Oregon'=>'OR', 'Pennsylvania'=>'PA', 'Rhode Island'=>'RI', 'South Carolina'=>'SC', 'South Dakota'=>'SD', 'Tennessee'=>'TN', 'Texas'=>'TX', 'Utah'=>'UT', 'Vermont'=>'VT', 'Virginia'=>'VA', 'Washington'=>'WA', 'West Virginia'=>'WV', 'Wisconsin'=>'WI', 'Wyoming'=>'WY');
    }
    // @since 4.7.7 - Countries (currently used by dropdown element only)
    public static function countries(){
        return array(
            array('shortname'=>esc_html__(esc_html__('Afghanistan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Islamic Republic of Afghanistan','super-forms'),'super-forms'),'iso3'=>'AFG','iso2'=>'AF'),array('shortname'=>esc_html__(esc_html__('Albania','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Albania','super-forms'),'super-forms'),'iso3'=>'ALB','iso2'=>'AL'),array('shortname'=>esc_html__(esc_html__('Algeria','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the People\'s Democratic Republic of Algeria','super-forms'),'super-forms'),'iso3'=>'DZA','iso2'=>'DZ'),array('shortname'=>esc_html__(esc_html__('Andorra','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Principality of Andorra','super-forms'),'super-forms'),'iso3'=>'AND','iso2'=>'AD'),array('shortname'=>esc_html__(esc_html__('Angola','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Angola','super-forms'),'super-forms'),'iso3'=>'AGO','iso2'=>'AO'),array('shortname'=>esc_html__(esc_html__('Antigua and Barbuda','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Antigua and Barbuda','super-forms'),'super-forms'),'iso3'=>'ATG','iso2'=>'AG'),array('shortname'=>esc_html__(esc_html__('Argentina','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Argentine Republic','super-forms'),'super-forms'),'iso3'=>'ARG','iso2'=>'AR'),array('shortname'=>esc_html__(esc_html__('Armenia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Armenia','super-forms'),'super-forms'),'iso3'=>'ARM','iso2'=>'AM'),array('shortname'=>esc_html__(esc_html__('Australia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Australia','super-forms'),'super-forms'),'iso3'=>'AUS','iso2'=>'AU'),array('shortname'=>esc_html__(esc_html__('Austria','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Austria','super-forms'),'super-forms'),'iso3'=>'AUT','iso2'=>'AT'),array('shortname'=>esc_html__(esc_html__('Azerbaijan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Azerbaijan','super-forms'),'super-forms'),'iso3'=>'AZE','iso2'=>'AZ'),array('shortname'=>esc_html__(esc_html__('Bahamas','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Commonwealth of the Bahamas','super-forms'),'super-forms'),'iso3'=>'BHS','iso2'=>'BS'),array('shortname'=>esc_html__(esc_html__('Bahrain','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Bahrain','super-forms'),'super-forms'),'iso3'=>'BHR','iso2'=>'BH'),array('shortname'=>esc_html__(esc_html__('Bangladesh','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the People\'s Republic of Bangladesh','super-forms'),'super-forms'),'iso3'=>'BGD','iso2'=>'BD'),array('shortname'=>esc_html__(esc_html__('Barbados','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Barbados','super-forms'),'super-forms'),'iso3'=>'BRB','iso2'=>'BB'),array('shortname'=>esc_html__(esc_html__('Belarus','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Belarus','super-forms'),'super-forms'),'iso3'=>'BLR','iso2'=>'BY'),array('shortname'=>esc_html__(esc_html__('Belgium','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Belgium','super-forms'),'super-forms'),'iso3'=>'BEL','iso2'=>'BE'),array('shortname'=>esc_html__(esc_html__('Belize','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Belize','super-forms'),'super-forms'),'iso3'=>'BLZ','iso2'=>'BZ'),array('shortname'=>esc_html__(esc_html__('Benin','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Benin','super-forms'),'super-forms'),'iso3'=>'BEN','iso2'=>'BJ'),array('shortname'=>esc_html__(esc_html__('Bhutan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Bhutan','super-forms'),'super-forms'),'iso3'=>'BTN','iso2'=>'BT'),array('shortname'=>esc_html__(esc_html__('Bolivia (Plurinational State of)','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Plurinational State of Bolivia','super-forms'),'super-forms'),'iso3'=>'BOL','iso2'=>'BO'),array('shortname'=>esc_html__(esc_html__('Bosnia and Herzegovina','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Bosnia and Herzegovina','super-forms'),'super-forms'),'iso3'=>'BIH','iso2'=>'BA'),array('shortname'=>esc_html__(esc_html__('Botswana','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Botswana','super-forms'),'super-forms'),'iso3'=>'BWA','iso2'=>'BW'),array('shortname'=>esc_html__(esc_html__('Brazil','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Federative Republic of Brazil','super-forms'),'super-forms'),'iso3'=>'BRA','iso2'=>'BR'),array('shortname'=>esc_html__(esc_html__('Brunei Darussalam','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Brunei Darussalam','super-forms'),'super-forms'),'iso3'=>'BRN','iso2'=>'BN'),array('shortname'=>esc_html__(esc_html__('Bulgaria','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Bulgaria','super-forms'),'super-forms'),'iso3'=>'BGR','iso2'=>'BG'),array('shortname'=>esc_html__(esc_html__('Burkina Faso','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Burkina Faso','super-forms'),'super-forms'),'iso3'=>'BFA','iso2'=>'BF'),array('shortname'=>esc_html__(esc_html__('Burundi','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Burundi','super-forms'),'super-forms'),'iso3'=>'BDI','iso2'=>'BI'),array('shortname'=>esc_html__(esc_html__('Cabo Verde','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Republic of Cabo Verde','super-forms'),'super-forms'),'iso3'=>'CPV','iso2'=>'CV'),array('shortname'=>esc_html__(esc_html__('Cambodia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Cambodia','super-forms'),'super-forms'),'iso3'=>'KHM','iso2'=>'KH'),array('shortname'=>esc_html__(esc_html__('Cameroon','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Cameroon','super-forms'),'super-forms'),'iso3'=>'CMR','iso2'=>'CM'),array('shortname'=>esc_html__(esc_html__('Canada','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Canada','super-forms'),'super-forms'),'iso3'=>'CAN','iso2'=>'CA'),array('shortname'=>esc_html__(esc_html__('Central African Republic','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Central African Republic','super-forms'),'super-forms'),'iso3'=>'CAF','iso2'=>'CF'),array('shortname'=>esc_html__(esc_html__('Chad','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Chad','super-forms'),'super-forms'),'iso3'=>'TCD','iso2'=>'TD'),array('shortname'=>esc_html__(esc_html__('Chile','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Chile','super-forms'),'super-forms'),'iso3'=>'CHL','iso2'=>'CL'),array('shortname'=>esc_html__(esc_html__('China','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the People\'s Republic of China','super-forms'),'super-forms'),'iso3'=>'CHN','iso2'=>'CN'),array('shortname'=>esc_html__(esc_html__('Colombia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Colombia','super-forms'),'super-forms'),'iso3'=>'COL','iso2'=>'CO'),array('shortname'=>esc_html__(esc_html__('Comoros','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Union of the Comoros','super-forms'),'super-forms'),'iso3'=>'COM','iso2'=>'KM'),array('shortname'=>esc_html__(esc_html__('Congo','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of the Congo','super-forms'),'super-forms'),'iso3'=>'COG','iso2'=>'CG'),array('shortname'=>esc_html__(esc_html__('Cook Islands','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Cook Islands','super-forms'),'super-forms'),'iso3'=>'COK','iso2'=>'CK'),array('shortname'=>esc_html__(esc_html__('Costa Rica','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Costa Rica','super-forms'),'super-forms'),'iso3'=>'CRI','iso2'=>'CR'),array('shortname'=>esc_html__(esc_html__('Croatia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Croatia','super-forms'),'super-forms'),'iso3'=>'HRV','iso2'=>'HR'),array('shortname'=>esc_html__(esc_html__('Cuba','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Cuba','super-forms'),'super-forms'),'iso3'=>'CUB','iso2'=>'CU'),array('shortname'=>esc_html__(esc_html__('Cyprus','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Cyprus','super-forms'),'super-forms'),'iso3'=>'CYP','iso2'=>'CY'),array('shortname'=>esc_html__(esc_html__('Czechia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Czech Republic','super-forms'),'super-forms'),'iso3'=>'CZE','iso2'=>'CZ'),array('shortname'=>esc_html__(esc_html__('Côte d\'Ivoire','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Côte d\'Ivoire','super-forms'),'super-forms'),'iso3'=>'CIV','iso2'=>'CI'),array('shortname'=>esc_html__(esc_html__('Democratic People\'s Republic of Korea','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Democratic People\'s Republic of Korea','super-forms'),'super-forms'),'iso3'=>'PRK','iso2'=>'KP'),array('shortname'=>esc_html__(esc_html__('Democratic Republic of the Congo','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Democratic Republic of the Congo','super-forms'),'super-forms'),'iso3'=>'COD','iso2'=>'CD'),array('shortname'=>esc_html__(esc_html__('Denmark','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Denmark','super-forms'),'super-forms'),'iso3'=>'DNK','iso2'=>'DK'),array('shortname'=>esc_html__(esc_html__('Djibouti','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Djibouti','super-forms'),'super-forms'),'iso3'=>'DJI','iso2'=>'DJ'),array('shortname'=>esc_html__(esc_html__('Dominica','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Commonwealth of Dominica','super-forms'),'super-forms'),'iso3'=>'DMA','iso2'=>'DM'),array('shortname'=>esc_html__(esc_html__('Dominican Republic','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Dominican Republic','super-forms'),'super-forms'),'iso3'=>'DOM','iso2'=>'DO'),array('shortname'=>esc_html__(esc_html__('Ecuador','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Ecuador','super-forms'),'super-forms'),'iso3'=>'ECU','iso2'=>'EC'),array('shortname'=>esc_html__(esc_html__('Egypt','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Arab Republic of Egypt','super-forms'),'super-forms'),'iso3'=>'EGY','iso2'=>'EG'),array('shortname'=>esc_html__(esc_html__('El Salvador','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of El Salvador','super-forms'),'super-forms'),'iso3'=>'SLV','iso2'=>'SV'),array('shortname'=>esc_html__(esc_html__('Equatorial Guinea','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Equatorial Guinea','super-forms'),'super-forms'),'iso3'=>'GNQ','iso2'=>'GQ'),array('shortname'=>esc_html__(esc_html__('Eritrea','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the State of Eritrea','super-forms'),'super-forms'),'iso3'=>'ERI','iso2'=>'ER'),array('shortname'=>esc_html__(esc_html__('Estonia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Estonia','super-forms'),'super-forms'),'iso3'=>'EST','iso2'=>'EE'),array('shortname'=>esc_html__(esc_html__('Eswatini','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Eswatini ','super-forms'),'super-forms'),'iso3'=>'SWZ','iso2'=>'SZ'),array('shortname'=>esc_html__(esc_html__('Ethiopia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Federal Democratic Republic of Ethiopia','super-forms'),'super-forms'),'iso3'=>'ETH','iso2'=>'ET'),array('shortname'=>esc_html__(esc_html__('Faroe Islands ','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Faroe Islands','super-forms'),'super-forms'),'iso3'=>'FRO','iso2'=>'FO'),array('shortname'=>esc_html__(esc_html__('Fiji','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Fiji','super-forms'),'super-forms'),'iso3'=>'FJI','iso2'=>'FJ'),array('shortname'=>esc_html__(esc_html__('Finland','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Finland','super-forms'),'super-forms'),'iso3'=>'FIN','iso2'=>'FI'),array('shortname'=>esc_html__(esc_html__('France','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the French Republic','super-forms'),'super-forms'),'iso3'=>'FRA','iso2'=>'FR'),array('shortname'=>esc_html__(esc_html__('Gabon','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Gabonese Republic','super-forms'),'super-forms'),'iso3'=>'GAB','iso2'=>'GA'),array('shortname'=>esc_html__(esc_html__('Gambia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of the Gambia','super-forms'),'super-forms'),'iso3'=>'GMB','iso2'=>'GM'),array('shortname'=>esc_html__(esc_html__('Georgia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Georgia','super-forms'),'super-forms'),'iso3'=>'GEO','iso2'=>'GE'),array('shortname'=>esc_html__(esc_html__('Germany','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Federal Republic of Germany','super-forms'),'super-forms'),'iso3'=>'DEU','iso2'=>'DE'),array('shortname'=>esc_html__(esc_html__('Ghana','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Ghana','super-forms'),'super-forms'),'iso3'=>'GHA','iso2'=>'GH'),array('shortname'=>esc_html__(esc_html__('Greece','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Hellenic Republic','super-forms'),'super-forms'),'iso3'=>'GRC','iso2'=>'GR'),array('shortname'=>esc_html__(esc_html__('Grenada','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Grenada','super-forms'),'super-forms'),'iso3'=>'GRD','iso2'=>'GD'),array('shortname'=>esc_html__(esc_html__('Guatemala','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Guatemala','super-forms'),'super-forms'),'iso3'=>'GTM','iso2'=>'GT'),array('shortname'=>esc_html__(esc_html__('Guinea','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Guinea','super-forms'),'super-forms'),'iso3'=>'GIN','iso2'=>'GN'),array('shortname'=>esc_html__(esc_html__('Guinea-Bissau','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Guinea-Bissau','super-forms'),'super-forms'),'iso3'=>'GNB','iso2'=>'GW'),array('shortname'=>esc_html__(esc_html__('Guyana','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Co-operative Republic of Guyana','super-forms'),'super-forms'),'iso3'=>'GUY','iso2'=>'GY'),array('shortname'=>esc_html__(esc_html__('Haiti','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Haiti','super-forms'),'super-forms'),'iso3'=>'HTI','iso2'=>'HT'),array('shortname'=>esc_html__(esc_html__('Honduras','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Honduras','super-forms'),'super-forms'),'iso3'=>'HND','iso2'=>'HN'),array('shortname'=>esc_html__(esc_html__('Hungary','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Hungary','super-forms'),'super-forms'),'iso3'=>'HUN','iso2'=>'HU'),array('shortname'=>esc_html__(esc_html__('Iceland','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Iceland','super-forms'),'super-forms'),'iso3'=>'ISL','iso2'=>'IS'),array('shortname'=>esc_html__(esc_html__('India','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of India','super-forms'),'super-forms'),'iso3'=>'IND','iso2'=>'IN'),array('shortname'=>esc_html__(esc_html__('Indonesia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Indonesia','super-forms'),'super-forms'),'iso3'=>'IDN','iso2'=>'ID'),array('shortname'=>esc_html__(esc_html__('Iran (Islamic Republic of)','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Islamic Republic of Iran','super-forms'),'super-forms'),'iso3'=>'IRN','iso2'=>'IR'),array('shortname'=>esc_html__(esc_html__('Iraq','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Iraq','super-forms'),'super-forms'),'iso3'=>'IRQ','iso2'=>'IQ'),array('shortname'=>esc_html__(esc_html__('Ireland','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Ireland','super-forms'),'super-forms'),'iso3'=>'IRL','iso2'=>'IE'),array('shortname'=>esc_html__(esc_html__('Israel','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the State of Israel','super-forms'),'super-forms'),'iso3'=>'ISR','iso2'=>'IL'),array('shortname'=>esc_html__(esc_html__('Italy','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Italy','super-forms'),'super-forms'),'iso3'=>'ITA','iso2'=>'IT'),array('shortname'=>esc_html__(esc_html__('Jamaica','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Jamaica','super-forms'),'super-forms'),'iso3'=>'JAM','iso2'=>'JM'),array('shortname'=>esc_html__(esc_html__('Japan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Japan','super-forms'),'super-forms'),'iso3'=>'JPN','iso2'=>'JP'),array('shortname'=>esc_html__(esc_html__('Jordan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Hashemite Kingdom of Jordan','super-forms'),'super-forms'),'iso3'=>'JOR','iso2'=>'JO'),array('shortname'=>esc_html__(esc_html__('Kazakhstan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Kazakhstan','super-forms'),'super-forms'),'iso3'=>'KAZ','iso2'=>'KZ'),array('shortname'=>esc_html__(esc_html__('Kenya','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Kenya','super-forms'),'super-forms'),'iso3'=>'KEN','iso2'=>'KE'),array('shortname'=>esc_html__(esc_html__('Kiribati','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Kiribati','super-forms'),'super-forms'),'iso3'=>'KIR','iso2'=>'KI'),array('shortname'=>esc_html__(esc_html__('Kosovo','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Republic of Kosovo','super-forms'),'super-forms'),'iso3'=>'XXK','iso2'=>'XK'),array('shortname'=>esc_html__(esc_html__('Kuwait','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the State of Kuwait','super-forms'),'super-forms'),'iso3'=>'KWT','iso2'=>'KW'),array('shortname'=>esc_html__(esc_html__('Kyrgyzstan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kyrgyz Republic','super-forms'),'super-forms'),'iso3'=>'KGZ','iso2'=>'KG'),array('shortname'=>esc_html__(esc_html__('Lao People\'s Democratic Republic','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Lao People\'s Democratic Republic','super-forms'),'super-forms'),'iso3'=>'LAO','iso2'=>'LA'),array('shortname'=>esc_html__(esc_html__('Latvia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Latvia','super-forms'),'super-forms'),'iso3'=>'LVA','iso2'=>'LV'),array('shortname'=>esc_html__(esc_html__('Lebanon','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Lebanese Republic','super-forms'),'super-forms'),'iso3'=>'LBN','iso2'=>'LB'),array('shortname'=>esc_html__(esc_html__('Lesotho','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Lesotho','super-forms'),'super-forms'),'iso3'=>'LSO','iso2'=>'LS'),array('shortname'=>esc_html__(esc_html__('Liberia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Liberia','super-forms'),'super-forms'),'iso3'=>'LBR','iso2'=>'LR'),array('shortname'=>esc_html__(esc_html__('Libya','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('State of Libya','super-forms'),'super-forms'),'iso3'=>'LBY','iso2'=>'LY'),array('shortname'=>esc_html__(esc_html__('Lithuania','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Lithuania','super-forms'),'super-forms'),'iso3'=>'LTU','iso2'=>'LT'),array('shortname'=>esc_html__(esc_html__('Luxembourg','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Grand Duchy of Luxembourg','super-forms'),'super-forms'),'iso3'=>'LUX','iso2'=>'LU'),array('shortname'=>esc_html__(esc_html__('Madagascar','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Madagascar','super-forms'),'super-forms'),'iso3'=>'MDG','iso2'=>'MG'),array('shortname'=>esc_html__(esc_html__('Malawi','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Malawi','super-forms'),'super-forms'),'iso3'=>'MWI','iso2'=>'MW'),array('shortname'=>esc_html__(esc_html__('Malaysia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Malaysia','super-forms'),'super-forms'),'iso3'=>'MYS','iso2'=>'MY'),array('shortname'=>esc_html__(esc_html__('Maldives','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Maldives','super-forms'),'super-forms'),'iso3'=>'MDV','iso2'=>'MV'),array('shortname'=>esc_html__(esc_html__('Mali','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Mali','super-forms'),'super-forms'),'iso3'=>'MLI','iso2'=>'ML'),array('shortname'=>esc_html__(esc_html__('Malta','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Malta','super-forms'),'super-forms'),'iso3'=>'MLT','iso2'=>'MT'),array('shortname'=>esc_html__(esc_html__('Marshall Islands','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of the Marshall Islands','super-forms'),'super-forms'),'iso3'=>'MHL','iso2'=>'MH'),array('shortname'=>esc_html__(esc_html__('Mauritania','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Islamic Republic of Mauritania','super-forms'),'super-forms'),'iso3'=>'MRT','iso2'=>'MR'),array('shortname'=>esc_html__(esc_html__('Mauritius','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Mauritius','super-forms'),'super-forms'),'iso3'=>'MUS','iso2'=>'MU'),array('shortname'=>esc_html__(esc_html__('Mexico','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the United Mexican States','super-forms'),'super-forms'),'iso3'=>'MEX','iso2'=>'MX'),array('shortname'=>esc_html__(esc_html__('Micronesia (Federated States of)','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Federated States of Micronesia','super-forms'),'super-forms'),'iso3'=>'FSM','iso2'=>'FM'),array('shortname'=>esc_html__(esc_html__('Monaco','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Principality of Monaco','super-forms'),'super-forms'),'iso3'=>'MCO','iso2'=>'MC'),array('shortname'=>esc_html__(esc_html__('Mongolia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Mongolia','super-forms'),'super-forms'),'iso3'=>'MNG','iso2'=>'MN'),array('shortname'=>esc_html__(esc_html__('Montenegro','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Montenegro','super-forms'),'super-forms'),'iso3'=>'MNE','iso2'=>'ME'),array('shortname'=>esc_html__(esc_html__('Morocco','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Morocco','super-forms'),'super-forms'),'iso3'=>'MAR','iso2'=>'MA'),array('shortname'=>esc_html__(esc_html__('Mozambique','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Mozambique','super-forms'),'super-forms'),'iso3'=>'MOZ','iso2'=>'MZ'),array('shortname'=>esc_html__(esc_html__('Myanmar','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of the Union of Myanmar','super-forms'),'super-forms'),'iso3'=>'MMR','iso2'=>'MM'),array('shortname'=>esc_html__(esc_html__('Namibia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Namibia','super-forms'),'super-forms'),'iso3'=>'NAM','iso2'=>'NA'),array('shortname'=>esc_html__(esc_html__('Nauru','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Nauru','super-forms'),'super-forms'),'iso3'=>'NRU','iso2'=>'NR'),array('shortname'=>esc_html__(esc_html__('Nepal','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Federal Democratic Republic of Nepal','super-forms'),'super-forms'),'iso3'=>'NPL','iso2'=>'NP'),array('shortname'=>esc_html__(esc_html__('Netherlands','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of the Netherlands','super-forms'),'super-forms'),'iso3'=>'NLD','iso2'=>'NL'),array('shortname'=>esc_html__(esc_html__('New Zealand','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('New Zealand','super-forms'),'super-forms'),'iso3'=>'NZL','iso2'=>'NZ'),array('shortname'=>esc_html__(esc_html__('Nicaragua','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Nicaragua','super-forms'),'super-forms'),'iso3'=>'NIC','iso2'=>'NI'),array('shortname'=>esc_html__(esc_html__('Niger','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of the Niger','super-forms'),'super-forms'),'iso3'=>'NER','iso2'=>'NE'),array('shortname'=>esc_html__(esc_html__('Nigeria','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Federal Republic of Nigeria','super-forms'),'super-forms'),'iso3'=>'NGA','iso2'=>'NG'),array('shortname'=>esc_html__(esc_html__('Niue','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Niue','super-forms'),'super-forms'),'iso3'=>'NIU','iso2'=>'NU'),array('shortname'=>esc_html__(esc_html__('North Macedonia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of North Macedonia ','super-forms'),'super-forms'),'iso3'=>'MKD','iso2'=>'MK'),array('shortname'=>esc_html__(esc_html__('Norway','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Norway','super-forms'),'super-forms'),'iso3'=>'NOR','iso2'=>'NO'),array('shortname'=>esc_html__(esc_html__('Oman','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Sultanate of Oman','super-forms'),'super-forms'),'iso3'=>'OMN','iso2'=>'OM'),array('shortname'=>esc_html__(esc_html__('Pakistan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Islamic Republic of Pakistan','super-forms'),'super-forms'),'iso3'=>'PAK','iso2'=>'PK'),array('shortname'=>esc_html__(esc_html__('Palau','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Palau','super-forms'),'super-forms'),'iso3'=>'PLW','iso2'=>'PW'),array('shortname'=>esc_html__(esc_html__('Panama','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Panama','super-forms'),'super-forms'),'iso3'=>'PAN','iso2'=>'PA'),array('shortname'=>esc_html__(esc_html__('Papua New Guinea','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Independent State of Papua New Guinea','super-forms'),'super-forms'),'iso3'=>'PNG','iso2'=>'PG'),array('shortname'=>esc_html__(esc_html__('Paraguay','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Paraguay','super-forms'),'super-forms'),'iso3'=>'PRY','iso2'=>'PY'),array('shortname'=>esc_html__(esc_html__('Peru','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Peru','super-forms'),'super-forms'),'iso3'=>'PER','iso2'=>'PE'),array('shortname'=>esc_html__(esc_html__('Philippines','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of the Philippines','super-forms'),'super-forms'),'iso3'=>'PHL','iso2'=>'PH'),array('shortname'=>esc_html__(esc_html__('Poland','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Poland','super-forms'),'super-forms'),'iso3'=>'POL','iso2'=>'PL'),array('shortname'=>esc_html__(esc_html__('Portugal','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Portuguese Republic','super-forms'),'super-forms'),'iso3'=>'PRT','iso2'=>'PT'),array('shortname'=>esc_html__(esc_html__('Qatar','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the State of Qatar','super-forms'),'super-forms'),'iso3'=>'QAT','iso2'=>'QA'),array('shortname'=>esc_html__(esc_html__('Republic of Korea','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Korea','super-forms'),'super-forms'),'iso3'=>'KOR','iso2'=>'KR'),array('shortname'=>esc_html__(esc_html__('Republic of Moldova','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Moldova','super-forms'),'super-forms'),'iso3'=>'MDA','iso2'=>'MD'),array('shortname'=>esc_html__(esc_html__('Romania','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Romania','super-forms'),'super-forms'),'iso3'=>'ROU','iso2'=>'RO'),array('shortname'=>esc_html__(esc_html__('Russian Federation','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Russian Federation','super-forms'),'super-forms'),'iso3'=>'RUS','iso2'=>'RU'),array('shortname'=>esc_html__(esc_html__('Rwanda','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Rwanda','super-forms'),'super-forms'),'iso3'=>'RWA','iso2'=>'RW'),array('shortname'=>esc_html__(esc_html__('Saint Kitts and Nevis','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Saint Kitts and Nevis','super-forms'),'super-forms'),'iso3'=>'KNA','iso2'=>'KN'),array('shortname'=>esc_html__(esc_html__('Saint Lucia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Saint Lucia','super-forms'),'super-forms'),'iso3'=>'LCA','iso2'=>'LC'),array('shortname'=>esc_html__(esc_html__('Saint Vincent and the Grenadines','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Saint Vincent and the Grenadines','super-forms'),'super-forms'),'iso3'=>'VCT','iso2'=>'VC'),array('shortname'=>esc_html__(esc_html__('Samoa','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Independent State of Samoa','super-forms'),'super-forms'),'iso3'=>'WSM','iso2'=>'WS'),array('shortname'=>esc_html__(esc_html__('San Marino','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of San Marino','super-forms'),'super-forms'),'iso3'=>'SMR','iso2'=>'SM'),array('shortname'=>esc_html__(esc_html__('Sao Tome and Principe','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Democratic Republic of Sao Tome and Principe','super-forms'),'super-forms'),'iso3'=>'STP','iso2'=>'ST'),array('shortname'=>esc_html__(esc_html__('Saudi Arabia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Saudi Arabia','super-forms'),'super-forms'),'iso3'=>'SAU','iso2'=>'SA'),array('shortname'=>esc_html__(esc_html__('Senegal','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Senegal','super-forms'),'super-forms'),'iso3'=>'SEN','iso2'=>'SN'),array('shortname'=>esc_html__(esc_html__('Serbia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Serbia','super-forms'),'super-forms'),'iso3'=>'SRB','iso2'=>'RS'),array('shortname'=>esc_html__(esc_html__('Seychelles','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Seychelles','super-forms'),'super-forms'),'iso3'=>'SYC','iso2'=>'SC'),array('shortname'=>esc_html__(esc_html__('Sierra Leone','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Sierra Leone','super-forms'),'super-forms'),'iso3'=>'SLE','iso2'=>'SL'),array('shortname'=>esc_html__(esc_html__('Singapore','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Singapore','super-forms'),'super-forms'),'iso3'=>'SGP','iso2'=>'SG'),array('shortname'=>esc_html__(esc_html__('Slovakia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Slovak Republic','super-forms'),'super-forms'),'iso3'=>'SVK','iso2'=>'SK'),array('shortname'=>esc_html__(esc_html__('Slovenia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Slovenia','super-forms'),'super-forms'),'iso3'=>'SVN','iso2'=>'SI'),array('shortname'=>esc_html__(esc_html__('Solomon Islands','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Solomon Islands','super-forms'),'super-forms'),'iso3'=>'SLB','iso2'=>'SB'),array('shortname'=>esc_html__(esc_html__('Somalia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Federal Republic of Somalia','super-forms'),'super-forms'),'iso3'=>'SOM','iso2'=>'SO'),array('shortname'=>esc_html__(esc_html__('South Africa','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of South Africa','super-forms'),'super-forms'),'iso3'=>'ZAF','iso2'=>'ZA'),array('shortname'=>esc_html__(esc_html__('South Sudan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of South Sudan','super-forms'),'super-forms'),'iso3'=>'SSD','iso2'=>'SS'),array('shortname'=>esc_html__(esc_html__('Spain','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Spain','super-forms'),'super-forms'),'iso3'=>'ESP','iso2'=>'ES'),array('shortname'=>esc_html__(esc_html__('Sri Lanka','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Democratic Socialist Republic of Sri Lanka','super-forms'),'super-forms'),'iso3'=>'LKA','iso2'=>'LK'),array('shortname'=>esc_html__(esc_html__('Sudan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of the Sudan','super-forms'),'super-forms'),'iso3'=>'SDN','iso2'=>'SD'),array('shortname'=>esc_html__(esc_html__('Suriname','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Suriname','super-forms'),'super-forms'),'iso3'=>'SUR','iso2'=>'SR'),array('shortname'=>esc_html__(esc_html__('Sweden','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Sweden','super-forms'),'super-forms'),'iso3'=>'SWE','iso2'=>'SE'),array('shortname'=>esc_html__(esc_html__('Switzerland','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Swiss Confederation','super-forms'),'super-forms'),'iso3'=>'CHE','iso2'=>'CH'),array('shortname'=>esc_html__(esc_html__('Syrian Arab Republic','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Syrian Arab Republic','super-forms'),'super-forms'),'iso3'=>'SYR','iso2'=>'SY'),array('shortname'=>esc_html__(esc_html__('Tajikistan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Tajikistan','super-forms'),'super-forms'),'iso3'=>'TJK','iso2'=>'TJ'),array('shortname'=>esc_html__(esc_html__('Thailand','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Thailand','super-forms'),'super-forms'),'iso3'=>'THA','iso2'=>'TH'),array('shortname'=>esc_html__(esc_html__('Timor-Leste','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Democratic Republic of Timor-Leste','super-forms'),'super-forms'),'iso3'=>'TLS','iso2'=>'TL'),array('shortname'=>esc_html__(esc_html__('Togo','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Togolese Republic','super-forms'),'super-forms'),'iso3'=>'TGO','iso2'=>'TG'),array('shortname'=>esc_html__(esc_html__('Tokelau ','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Tokelau','super-forms'),'super-forms'),'iso3'=>'TKL','iso2'=>'TK'),array('shortname'=>esc_html__(esc_html__('Tonga','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Kingdom of Tonga','super-forms'),'super-forms'),'iso3'=>'TON','iso2'=>'TO'),array('shortname'=>esc_html__(esc_html__('Trinidad and Tobago','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Trinidad and Tobago','super-forms'),'super-forms'),'iso3'=>'TTO','iso2'=>'TT'),array('shortname'=>esc_html__(esc_html__('Tunisia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Tunisia','super-forms'),'super-forms'),'iso3'=>'TUN','iso2'=>'TN'),array('shortname'=>esc_html__(esc_html__('Turkey','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Turkey','super-forms'),'super-forms'),'iso3'=>'TUR','iso2'=>'TR'),array('shortname'=>esc_html__(esc_html__('Turkmenistan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Turkmenistan','super-forms'),'super-forms'),'iso3'=>'TKM','iso2'=>'TM'),array('shortname'=>esc_html__(esc_html__('Tuvalu','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Tuvalu','super-forms'),'super-forms'),'iso3'=>'TUV','iso2'=>'TV'),array('shortname'=>esc_html__(esc_html__('Uganda','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Uganda','super-forms'),'super-forms'),'iso3'=>'UGA','iso2'=>'UG'),array('shortname'=>esc_html__(esc_html__('Ukraine','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('Ukraine','super-forms'),'super-forms'),'iso3'=>'UKR','iso2'=>'UA'),array('shortname'=>esc_html__(esc_html__('United Arab Emirates','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the United Arab Emirates','super-forms'),'super-forms'),'iso3'=>'ARE','iso2'=>'AE'),array('shortname'=>esc_html__(esc_html__('United Kingdom of Great Britain and Northern Ireland','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the United Kingdom of Great Britain and Northern Ireland','super-forms'),'super-forms'),'iso3'=>'GBR','iso2'=>'GB'),array('shortname'=>esc_html__(esc_html__('United Republic of Tanzania','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the United Republic of Tanzania','super-forms'),'super-forms'),'iso3'=>'TZA','iso2'=>'TZ'),array('shortname'=>esc_html__(esc_html__('United States of America','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the United States of America','super-forms'),'super-forms'),'iso3'=>'USA','iso2'=>'US'),array('shortname'=>esc_html__(esc_html__('Uruguay','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Eastern Republic of Uruguay','super-forms'),'super-forms'),'iso3'=>'URY','iso2'=>'UY'),array('shortname'=>esc_html__(esc_html__('Uzbekistan','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Uzbekistan','super-forms'),'super-forms'),'iso3'=>'UZB','iso2'=>'UZ'),array('shortname'=>esc_html__(esc_html__('Vanuatu','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Vanuatu','super-forms'),'super-forms'),'iso3'=>'VUT','iso2'=>'VU'),array('shortname'=>esc_html__(esc_html__('Venezuela (Bolivarian Republic of)','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Bolivarian Republic of Venezuela','super-forms'),'super-forms'),'iso3'=>'VEN','iso2'=>'VE'),array('shortname'=>esc_html__(esc_html__('Viet Nam','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Socialist Republic of Viet Nam','super-forms'),'super-forms'),'iso3'=>'VNM','iso2'=>'VN'),array('shortname'=>esc_html__(esc_html__('Yemen','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Yemen','super-forms'),'super-forms'),'iso3'=>'YEM','iso2'=>'YE'),array('shortname'=>esc_html__(esc_html__('Zambia','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Zambia','super-forms'),'super-forms'),'iso3'=>'ZMB','iso2'=>'ZM'),array('shortname'=>esc_html__(esc_html__('Zimbabwe','super-forms'),'super-forms'),'official'=>esc_html__(esc_html__('the Republic of Zimbabwe','super-forms'),'super-forms'),'iso3'=>'ZWE','iso2'=>'ZW')
        );
    }
    public static function get_dropdown_items($type){
        if($type=='states') $data = self::us_states();
        if($type=='countries_iso2' || $type=='countries_normal' || $type=='countries_full') $data = self::countries();
        $items = array();
        foreach($data as $label => $value){
            if($type=='countries_iso2'){
                $label = $value['shortname'];
                $value = $value['iso2'];
            }elseif($type=='countries_normal'){
                $label = $value['shortname'];
                $value = $value['shortname'];
            }elseif($type=='countries_full'){
                $label = $value['shortname'] . ' (' . $value['iso2'] . ')';
                $value = $value['shortname'].';'.$value['iso2'].';'.$value['iso3'].';'.$value['official'];
            }
            $items[] = array(
                'checked' => false,
                'label' => $label,
                'value' => $value
            );
        }
        return $items;
    }
    // Function to replace tags inside dynamic columns for conditional logic and conditional variable logic
    public static function replace_tags_dynamic_column_conditional_items($v, $dynamic_field_names, $settingName){
        if(isset($v['data'][$settingName])){
            foreach($v['data'][$settingName] as $ck => $cv){
                // Replace {tags}
                // `field`
                if(!empty($cv['field'])){
                    $str = $cv['field'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $str = str_replace($mv[0], '{'.$new_tag.'}', $str);
                        }
                    }
                    $v['data'][$settingName][$ck]['field'] = $str;
                }
                // `field_and`
                if(!empty($cv['field_and'])){
                    $str = $cv['field_and'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $str = str_replace($mv[0], '{'.$new_tag.'}', $str);
                        }
                    }
                    $v['data'][$settingName][$ck]['field_and'] = $str;
                }
                // `value`
                if(!empty($cv['value'])){
                    $str = $cv['value'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $cv['value'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['value']);
                        }
                    }
                    $v['data'][$settingName][$ck]['value'] = $cv['value'];
                }
                // `value_and`
                if(!empty($cv['value_and'])){
                    $str = $cv['value_and'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $cv['value_and'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['value_and']);
                        }
                    }
                    $v['data'][$settingName][$ck]['value_and'] = $cv['value_and'];
                }
                // `new_value`
                if(!empty($cv['new_value'])){
                    $str = $cv['new_value'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $cv['new_value'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['new_value']);
                        }
                    }
                    $v['data'][$settingName][$ck]['new_value'] = $cv['new_value'];
                }
            }
        }
        return $v;
    }

    // Function used for dynamic columns to replace {tags} in conditional logics with correct updated field names
    public static function replace_tags_dynamic_columns($x){
        extract( shortcode_atts( array( 'v'=>'', 're'=>'', 'i'=>0, 'dynamic_field_names'=>array(), 'inner_field_names'=>array(), 'dv'=>array()), $x ) );
        // Rename Email Label and Field name accordingly 
        if(!empty($v['data']['name'])){
            $current_name = $v['data']['name'];
            if(isset($inner_field_names[$current_name])){
                if($i>1){
                    $v['data']['name'] = $inner_field_names[$current_name]['name'] . '_' . $i;
                }else{
                    $v['data']['name'] = $inner_field_names[$current_name]['name'];
                }
                $v['data']['email'] = SUPER_Common::convert_field_email_label($inner_field_names[$current_name]['email'], $i);
            }
            if( !empty($dv[$current_name]) ) {
                if(!empty($dv[$current_name]['value'])) {
                    // Now override the "Default value" with the actual Entry data
                    $v['data']['value'] = $dv[$current_name]['value'];
                }
            }
        }
        if($i>1){
            // If inside dynamic column, and not the first dynamic column we need to replace all the {tags} accordingly
            // Rename conditional logics accordingly
            $v = SUPER_Common::replace_tags_dynamic_column_conditional_items($v, $dynamic_field_names, 'conditional_items');
            $v = SUPER_Common::replace_tags_dynamic_column_conditional_items($v, $dynamic_field_names, 'conditional_variable_items');
            // Replace HTML {tags}
            if($v['tag']=='html') {
                $str = $v['data']['html'];
                preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                foreach($matches as $mk => $mv){
                    // In case advanced tag is used explode it
                    $values = explode(";", $mv[1]);
                    if(in_array($values[0], $dynamic_field_names)){
                        $new_name = $values[0].'_'.$i;
                        $values[0] = $new_name;
                        $new_tag = implode(";", $values);
                        $v['data']['html'] = str_replace($mv[0], '{'.$new_tag.'}', $v['data']['html']);
                    }
                }
            }
            // Replace calculator math with correct {tags}
            if(isset($v['data']['math'])){
                $str = $v['data']['math'];
                preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                foreach($matches as $mk => $mv){
                    // In case advanced tag is used explode it
                    $values = explode(";", $mv[1]);
                    if(in_array($values[0], $dynamic_field_names)){
                        $new_name = $values[0].'_'.$i;
                        $values[0] = $new_name;
                        $new_tag = implode(";", $values);
                        $v['data']['math'] = str_replace($mv[0], '{'.$new_tag.'}', $v['data']['math']);
                    }
                }
            }
        }
        return $v;
    }


    // @since 4.7.7 - get the absolute default value of an element
    // this function is used specifically for dynamic column system
    public static function get_absolute_default_value($element, $shortcodes=false){
        $tag = $element['tag'];
        // Check if element belongs to one of those with `multi-items`, if not just grab the `value` setting
        $multi_item_elements = array('radio', 'checkbox', 'dropdown');
        if(in_array($tag, $multi_item_elements)){
            // Let's loop over the items and grab the ones that are selected by default
            if($tag=='radio'){
                if(!empty($element['data']['radio_items'])){
                    $items = $element['data']['radio_items'];
                    foreach($items as $v){
                        if($v['checked']==='1' || $v['checked']==='true'){
                            // Since radio buttons only can have one selected item return instantly
                            return $v['value'];
                        }
                    }
                }
                return '';
            }else{
                $values = array();
                if($tag=='checkbox'){
                    if(!empty($element['data']['checkbox_items'])){
                        $items = $element['data']['checkbox_items'];
                        foreach($items as $v){
                            if($v['checked']==='1' || $v['checked']==='true'){
                                $values[] = $v['value'];
                            }
                        }
                        return implode(',', $values);
                    }
                    return '';
                }
                if($tag=='dropdown'){
                    if(!empty($element['data']['dropdown_items'])){
                        $items = $element['data']['dropdown_items'];
                        foreach($items as $v){
                            if($v['checked']==='1' || $v['checked']==='true'){
                                $values[] = $v['value'];
                            }
                        }
                        return implode(',', $values);
                    }
                    return '';
                }
            }
        }else{
            // Not an element with `multi-items` let's return the `value` instead
            if(isset($element['data']['value'])){
                return $element['data']['value'];
            }else{
                // If no such data exists, check for element default setting
                $default_value = self::get_default_element_setting_value($shortcodes, $element['group'], $tag, 'general', 'value');
                // If no such data exists it will return an empty string
                return $default_value;
            }
        }
        return '';
    }


    /**
     * This function grabs any "Email label" setting value from a field, and converts it to the correct Email label if needed.
     * When a field is inside a dynamic column, it should for instance append the correct counter
     * Users can define where the counter itself should be placed by defining a %d inside the Email label
     * e.g: `Product %d quantity:` will be converted to `Product 4 quantity:`
     * We will also returned a trimmed version to remove any whitespaces at the start or end of the label
     */
    public static function convert_field_email_label($email_label, $counter, $clean=false){
        // Remove whitespaces from start and end
        $email_label = trim($email_label);
        if($counter<2){
            if($clean){
                return str_replace('%d', '', str_replace('%d ', '', $email_label));
            }else{
                return $email_label;
            }
        }
        $pos = strpos($email_label, '%d');
        if ($pos === false) {
            // Not found, just return with counter appended at the end
            return $email_label . ' ' . $counter;
        } else {
            // Found, return with counter replaced at correct position
            return str_replace('%d', $counter, $email_label);
        }
    }

    /**
     * This function takes the last comma or dot (if any) to make a clean float, ignoring thousand separator, currency or any other letter :
     */
    public static function tofloat($num) {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
       
        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        } 

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
        );
    }


    /**
     * Country Flags
     */
    public static function get_flags($key=null){
        $flags = array(
            // Africa
            'dz' => 'Algeria','ao' => 'Angola','bj' => 'Benin','bw' => 'Botswana','bf' => 'Burkina Faso','bi' => 'Burundi','cm' => 'Cameroon','cv' => 'Cape Verde','cf' => 'Central African Republic','td' => 'Chad','km' => 'Comoros','cg' => 'Congo','cd' => 'Congo, The Democratic Republic of the','ci' => 'Cote d\'Ivoire','dj' => 'Djibouti','eg' => 'Egypt','gq' => 'Equatorial Guinea','er' => 'Eritrea','et' => 'Ethiopia','ga' => 'Gabon','gm' => 'Gambia','gh' => 'Ghana','gn' => 'Guinea','gw' => 'Guinea-Bissau','ke' => 'Kenya','ls' => 'Lesotho','lr' => 'Liberia','ly' => 'Libya','mg' => 'Madagascar','mw' => 'Malawi','ml' => 'Mali','mr' => 'Mauritania','mu' => 'Mauritius','yt' => 'Mayotte','ma' => 'Morocco','mz' => 'Mozambique','na' => 'Namibia','ne' => 'Niger','ng' => 'Nigeria','re' => 'Reunion','rw' => 'Rwanda','sh' => 'Saint Helena','st' => 'Sao Tome and Principe','sn' => 'Senegal','sc' => 'Seychelles','sl' => 'Sierra Leone','so' => 'Somalia','za' => 'South Africa','ss' => 'South Sudan','sd' => 'Sudan','sz' => 'Swaziland','tz' => 'Tanzania','tg' => 'Togo','tn' => 'Tunisia','ug' => 'Uganda','eh' => 'Western Sahara','zm' => 'Zambia','zw' => 'Zimbabwe',

            // America
            'ai' => 'Anguilla','ag' => 'Antigua and Barbuda','ar' => 'Argentina','aw' => 'Aruba','bs' => 'Bahamas','bb' => 'Barbados','bz' => 'Belize','bm' => 'Bermuda','bo' => 'Bolivia, Plurinational State of','br' => 'Brazil','ca' => 'Canada','ky' => 'Cayman Islands','cl' => 'Chile','co' => 'Colombia','cr' => 'Costa Rica','cu' => 'Cuba','cw' => 'Curacao','dm' => 'Dominica','do' => 'Dominican Republic','ec' => 'Ecuador','sv' => 'El Salvador','fk' => 'Falkland Islands (Malvinas)','gf' => 'French Guiana','gl' => 'Greenland','gd' => 'Grenada','gp' => 'Guadeloupe','gt' => 'Guatemala','gy' => 'Guyana','ht' => 'Haiti','hn' => 'Honduras','jm' => 'Jamaica','mq' => 'Martinique','mx' => 'Mexico','ms' => 'Montserrat','an' => 'Netherlands Antilles','ni' => 'Nicaragua','pa' => 'Panama','py' => 'Paraguay','pe' => 'Peru','pr' => 'Puerto Rico','kn' => 'Saint Kitts and Nevis','lc' => 'Saint Lucia','pm' => 'Saint Pierre and Miquelon','vc' => 'Saint Vincent and the Grenadines','sx' => 'Sint Maarten','sr' => 'Suriname','tt' => 'Trinidad and Tobago','tc' => 'Turks and Caicos Islands','us' => 'United States','uy' => 'Uruguay','ve' => 'Venezuela, Bolivarian Republic of','vg' => 'Virgin Islands, British','vi' => 'Virgin Islands, U.S.',

            // Asia
            'af' => 'Afghanistan','am' => 'Armenia','az' => 'Azerbaijan','bh' => 'Bahrain','bd' => 'Bangladesh','bt' => 'Bhutan','bn' => 'Brunei Darussalam','kh' => 'Cambodia','cn' => 'China','cy' => 'Cyprus','ge' => 'Georgia','hk' => 'Hong Kong','in' => 'India','id' => 'Indonesia','ir' => 'Iran, Islamic Republic of','iq' => 'Iraq','il' => 'Israel','jp' => 'Japan','jo' => 'Jordan','kz' => 'Kazakhstan','kp' => 'Korea, Democratic People\'s Republic of','kr' => 'Korea, Republic of','kw' => 'Kuwait','kg' => 'Kyrgyzstan','la' => 'Lao People\'s Democratic Republic','lb' => 'Lebanon','mo' => 'Macao','my' => 'Malaysia','mv' => 'Maldives','mn' => 'Mongolia','mm' => 'Myanmar','np' => 'Nepal','om' => 'Oman','pk' => 'Pakistan','ps' => 'Palestinian Territory, Occupied','ph' => 'Philippines','qa' => 'Qatar','sa' => 'Saudi Arabia','sg' => 'Singapore','lk' => 'Sri Lanka','sy' => 'Syrian Arab Republic','tw' => 'Taiwan, Province of China','tj' => 'Tajikistan','th' => 'Thailand','tl' => 'Timor-Leste','tr' => 'Turkey','tm' => 'Turkmenistan','ae' => 'United Arab Emirates','uz' => 'Uzbekistan','vn' => 'Viet Nam','ye' => 'Yemen',

            // Europe
            'ax' => 'Aland Islands','al' => 'Albania','ad' => 'Andorra','at' => 'Austria','by' => 'Belarus','be' => 'Belgium','ba' => 'Bosnia and Herzegovina','bg' => 'Bulgaria','hr' => 'Croatia','cz' => 'Czech Republic','dk' => 'Denmark','ee' => 'Estonia','fo' => 'Faroe Islands','fi' => 'Finland','fr' => 'France','de' => 'Germany','gi' => 'Gibraltar','gr' => 'Greece','gg' => 'Guernsey','va' => 'Holy See (Vatican City State)','hu' => 'Hungary','is' => 'Iceland','ie' => 'Ireland','im' => 'Isle of Man','it' => 'Italy','je' => 'Jersey','xk' => 'Kosovo','lv' => 'Latvia','li' => 'Liechtenstein','lt' => 'Lithuania','lu' => 'Luxembourg','mk' => 'Macedonia, The Former Yugoslav Republic of','mt' => 'Malta','md' => 'Moldova, Republic of','mc' => 'Monaco','me' => 'Montenegro','nl' => 'Netherlands','no' => 'Norway','pl' => 'Poland','pt' => 'Portugal','ro' => 'Romania','ru' => 'Russian Federation','sm' => 'San Marino','rs' => 'Serbia','sk' => 'Slovakia','si' => 'Slovenia','es' => 'Spain','sj' => 'Svalbard and Jan Mayen','se' => 'Sweden','ch' => 'Switzerland','ua' => 'Ukraine','gb' => 'United Kingdom',

            // Australia and Oceania
            'as' => 'American Samoa','au' => 'Australia','ck' => 'Cook Islands','fj' => 'Fiji','pf' => 'French Polynesia','gu' => 'Guam','ki' => 'Kiribati','mh' => 'Marshall Islands','fm' => 'Micronesia, Federated States of','nr' => 'Nauru','nc' => 'New Caledonia','nz' => 'New Zealand','nu' => 'Niue','nf' => 'Norfolk Island','mp' => 'Northern Mariana Islands','pw' => 'Palau','pg' => 'Papua New Guinea','pn' => 'Pitcairn','ws' => 'Samoa','sb' => 'Solomon Islands','tk' => 'Tokelau','to' => 'Tonga','tv' => 'Tuvalu','vu' => 'Vanuatu','wf' => 'Wallis and Futuna',

            // Other areas
            'bv' => 'Bouvet Island','io' => 'British Indian Ocean Territory','ic' => 'Canary Islands','catalonia' => 'Catalonia','england' => 'England','eu' => 'European Union','tf' => 'French Southern Territories','hm' => 'Heard Island and McDonald Islands','kurdistan' => 'Kurdistan','scotland' => 'Scotland','somaliland' => 'Somaliland','gs' => 'South Georgia and the South Sandwich Islands','tibet' => 'Tibet','um' => 'United States Minor Outlying Islands','wales' => 'Wales','zanzibar' => 'Zanzibar'
        );
        if(!empty($key)){
            return $flags[$key];
        }
        return $flags;
    }


    /**
     * Get Form Translations
     */
    public static function get_form_translations($form_id){
        $translations = get_post_meta( $form_id, '_super_translations', true );
        return $translations;
    }


    /**
     * Font Awesome 5 Free backwards compatibility
     */
    public static function fontawesome_bwc($icon){
        $old_to_new = array(
            'address-book-o' => 'address-book', 'address-card-o' => 'address-card', 'area-chart' => 'chart-area', 'arrow-circle-o-down' => 'arrow-alt-circle-down', 'arrow-circle-o-left' => 'arrow-alt-circle-left', 'arrow-circle-o-right' => 'arrow-alt-circle-right', 'arrow-circle-o-up' => 'arrow-alt-circle-up', 'arrows' => 'arrows-alt', 'arrows-alt' => 'expand-arrows-alt', 'arrows-h' => 'arrows-alt-h', 'arrows-v' => 'arrows-alt-v', 'asl-interpreting' => 'american-sign-language-interpreting', 'automobile' => 'car', 'bank' => 'university', 'bar-chart' => 'chart-bar', 'bar-chart-o' => 'chart-bar', 'bathtub' => 'bath', 'battery' => 'battery-full', 'battery-0' => 'battery-empty', 'battery-1' => 'battery-quarter', 'battery-2' => 'battery-half', 'battery-3' => 'battery-three-quarters', 'battery-4' => 'battery-full', 'bell-o' => 'bell', 'bell-slash-o' => 'bell-slash', 'bitbucket-square' => 'bitbucket', 'bitcoin' => 'btc', 'bookmark-o' => 'bookmark', 'building-o' => 'building', 'cab' => 'taxi', 'calendar' => 'calendar-alt', 'calendar-check-o' => 'calendar-check', 'calendar-minus-o' => 'calendar-minus', 'calendar-o' => 'calendar', 'calendar-plus-o' => 'calendar-plus', 'calendar-times-o' => 'calendar-times', 'caret-square-o-down' => 'caret-square-down', 'caret-square-o-left' => 'caret-square-left', 'caret-square-o-right' => 'caret-square-right', 'caret-square-o-up' => 'caret-square-up', 'cc' => 'closed-captioning', 'chain' => 'link', 'chain-broken' => 'unlink', 'check-circle-o' => 'check-circle', 'check-square-o' => 'check-square', 'circle-o' => 'circle', 'circle-o-notch' => 'circle-notch', 'circle-thin' => 'circle', 'clock-o' => 'clock', 'close' => 'times', 'cloud-download' => 'cloud-download-alt', 'cloud-upload' => 'cloud-upload-alt', 'cny' => 'yen-sign', 'code-fork' => 'code-branch', 'comment-o' => 'comment', 'commenting' => 'comment-dots', 'commenting-o' => 'comment-dots', 'comments-o' => 'comments', 'credit-card-alt' => 'credit-card', 'cutlery' => 'utensils', 'dashboard' => 'tachometer-alt', 'deafness' => 'deaf', 'dedent' => 'outdent', 'diamond' => 'gem', 'dollar' => 'dollar-sign', 'dot-circle-o' => 'dot-circle', 'drivers-license' => 'id-card', 'drivers-license-o' => 'id-card', 'eercast' => 'sellcast', 'envelope-o' => 'envelope', 'envelope-open-o' => 'envelope-open', 'eur' => 'euro-sign', 'euro' => 'euro-sign', 'exchange' => 'exchange-alt', 'external-link' => 'external-link-alt', 'external-link-square' => 'external-link-square-alt', 'eyedropper' => 'eye-dropper', 'fa' => 'font-awesome', 'facebook' => 'facebook-f', 'facebook-official' => 'facebook', 'feed' => 'rss', 'file-archive-o' => 'file-archive', 'file-audio-o' => 'file-audio', 'file-code-o' => 'file-code', 'file-excel-o' => 'file-excel', 'file-image-o' => 'file-image', 'file-movie-o' => 'file-video', 'file-o' => 'file', 'file-pdf-o' => 'file-pdf', 'file-photo-o' => 'file-image', 'file-picture-o' => 'file-image', 'file-powerpoint-o' => 'file-powerpoint', 'file-sound-o' => 'file-audio', 'file-text' => 'file-alt', 'file-text-o' => 'file-alt', 'file-video-o' => 'file-video', 'file-word-o' => 'file-word', 'file-zip-o' => 'file-archive', 'files-o' => 'copy', 'flag-o' => 'flag', 'flash' => 'bolt', 'floppy-o' => 'save', 'folder-o' => 'folder', 'folder-open-o' => 'folder-open', 'frown-o' => 'frown', 'futbol-o' => 'futbol', 'gbp' => 'pound-sign', 'ge' => 'empire', 'gear' => 'cog', 'gears' => 'cogs', 'gittip' => 'gratipay', 'glass' => 'glass-martini', 'google-plus' => 'google-plus-g', 'google-plus-circle' => 'google-plus', 'google-plus-official' => 'google-plus', 'group' => 'users', 'hand-grab-o' => 'hand-rock', 'hand-lizard-o' => 'hand-lizard', 'hand-o-down' => 'hand-point-down', 'hand-o-left' => 'hand-point-left', 'hand-o-right' => 'hand-point-right', 'hand-o-up' => 'hand-point-up', 'hand-paper-o' => 'hand-paper', 'hand-peace-o' => 'hand-peace', 'hand-pointer-o' => 'hand-pointer', 'hand-rock-o' => 'hand-rock', 'hand-scissors-o' => 'hand-scissors', 'hand-spock-o' => 'hand-spock', 'hand-stop-o' => 'hand-paper', 'handshake-o' => 'handshake', 'hard-of-hearing' => 'deaf', 'hdd-o' => 'hdd', 'header' => 'heading', 'heart-o' => 'heart', 'hospital-o' => 'hospital', 'hotel' => 'bed', 'hourglass-1' => 'hourglass-start', 'hourglass-2' => 'hourglass-half', 'hourglass-3' => 'hourglass-end', 'hourglass-o' => 'hourglass', 'id-card-o' => 'id-card', 'ils' => 'shekel-sign', 'inr' => 'rupee-sign', 'institution' => 'university', 'intersex' => 'transgender', 'jpy' => 'yen-sign', 'keyboard-o' => 'keyboard', 'krw' => 'won-sign', 'legal' => 'gavel', 'lemon-o' => 'lemon', 'level-down' => 'level-down-alt', 'level-up' => 'level-up-alt', 'life-bouy' => 'life-ring', 'life-buoy' => 'life-ring', 'life-saver' => 'life-ring', 'lightbulb-o' => 'lightbulb', 'line-chart' => 'chart-line', 'linkedin' => 'linkedin-in', 'linkedin-square' => 'linkedin', 'long-arrow-down' => 'long-arrow-alt-down', 'long-arrow-left' => 'long-arrow-alt-left', 'long-arrow-right' => 'long-arrow-alt-right', 'long-arrow-up' => 'long-arrow-alt-up', 'mail-forward' => 'share', 'mail-reply' => 'reply', 'mail-reply-all' => 'reply-all', 'map-marker' => 'map-marker-alt', 'map-o' => 'map', 'meanpath' => 'font-awesome', 'meh-o' => 'meh', 'minus-square-o' => 'minus-square', 'mobile' => 'mobile-alt', 'mobile-phone' => 'mobile-alt', 'money' => 'money-bill-alt', 'moon-o' => 'moon', 'mortar-board' => 'graduation-cap', 'navicon' => 'bars', 'newspaper-o' => 'newspaper', 'paper-plane-o' => 'paper-plane', 'paste' => 'clipboard', 'pause-circle-o' => 'pause-circle', 'pencil' => 'pencil-alt', 'pencil-square' => 'pen-square', 'pencil-square-o' => 'edit', 'photo' => 'image', 'picture-o' => 'image', 'pie-chart' => 'chart-pie', 'play-circle-o' => 'play-circle', 'plus-square-o' => 'plus-square', 'question-circle-o' => 'question-circle', 'ra' => 'rebel', 'refresh' => 'sync', 'remove' => 'times', 'reorder' => 'bars', 'repeat' => 'redo', 'resistance' => 'rebel', 'rmb' => 'yen-sign', 'rotate-left' => 'undo', 'rotate-right' => 'redo', 'rouble' => 'ruble-sign', 'rub' => 'ruble-sign', 'ruble' => 'ruble-sign', 'rupee' => 'rupee-sign', 's15' => 'bath', 'scissors' => 'cut', 'send' => 'paper-plane', 'send-o' => 'paper-plane', 'share-square-o' => 'share-square', 'shekel' => 'shekel-sign', 'sheqel' => 'shekel-sign', 'shield' => 'shield-alt', 'sign-in' => 'sign-in-alt', 'sign-out' => 'sign-out-alt', 'signing' => 'sign-language', 'sliders' => 'sliders-h', 'smile-o' => 'smile', 'snowflake-o' => 'snowflake', 'soccer-ball-o' => 'futbol', 'sort-alpha-asc' => 'sort-alpha-down', 'sort-alpha-desc' => 'sort-alpha-up', 'sort-amount-asc' => 'sort-amount-down', 'sort-amount-desc' => 'sort-amount-up', 'sort-asc' => 'sort-up', 'sort-desc' => 'sort-down', 'sort-numeric-asc' => 'sort-numeric-down', 'sort-numeric-desc' => 'sort-numeric-up', 'spoon' => 'utensil-spoon', 'square-o' => 'square', 'star-half-empty' => 'star-half', 'star-half-full' => 'star-half', 'star-half-o' => 'star-half', 'star-o' => 'star', 'sticky-note-o' => 'sticky-note', 'stop-circle-o' => 'stop-circle', 'sun-o' => 'sun', 'support' => 'life-ring', 'tablet' => 'tablet-alt', 'tachometer' => 'tachometer-alt', 'television' => 'tv', 'thermometer' => 'thermometer-full', 'thermometer-0' => 'thermometer-empty', 'thermometer-1' => 'thermometer-quarter', 'thermometer-2' => 'thermometer-half', 'thermometer-3' => 'thermometer-three-quarters', 'thermometer-4' => 'thermometer-full', 'thumb-tack' => 'thumbtack', 'thumbs-o-down' => 'thumbs-down', 'thumbs-o-up' => 'thumbs-up', 'ticket' => 'ticket-alt', 'times-circle-o' => 'times-circle', 'times-rectangle' => 'window-close', 'times-rectangle-o' => 'window-close', 'toggle-down' => 'caret-square-down', 'toggle-left' => 'caret-square-left', 'toggle-right' => 'caret-square-right', 'toggle-up' => 'caret-square-up', 'trash' => 'trash-alt', 'trash-o' => 'trash-alt', 'try' => 'lira-sign', 'turkish-lira' => 'lira-sign', 'unsorted' => 'sort', 'usd' => 'dollar-sign', 'user-circle-o' => 'user-circle', 'user-o' => 'user', 'vcard' => 'address-card', 'vcard-o' => 'address-card', 'video-camera' => 'video', 'vimeo' => 'vimeo-v', 'volume-control-phone' => 'phone-volume', 'warning' => 'exclamation-triangle', 'wechat' => 'weixin', 'wheelchair-alt' => 'accessible-icon', 'window-close-o' => 'window-close', 'won' => 'won-sign', 'y-combinator-square' => 'hacker-news', 'yc' => 'y-combinator', 'yc-square' => 'hacker-news', 'yen' => 'yen-sign', 'youtube-play' => 'youtube',
        );
        // Return new if found
        if(isset($old_to_new[$icon])){
            return $old_to_new[$icon];
        }    
        // Otherwise just return original
        return $icon;
    }

    /**
     * Filter if() statements
     */
    public static function filter_if_statements($html=''){
        // If does not contain 'endif;' we can just return the `$html` without doing anything
        if(!strpos($html, 'endif;')) return $html;
        $re = '/\s*[\'|"]?(.*?)[\'|"]?\s*(==|!=|>=|<=|>|<|\?\?|!\?\?)\s*[\'|"]?(.*?)[\'|"]?\s*$/';
        $array = str_split($html);
        $if_index = 0;
        $skip_up_to = 0;
        $capture_elseifcontent = false;
        $capture_conditions = false;
        $capture_suffix = false;
        $statements = array();
        $prefix = '';
        $first_if_found = false;
        $depth = 0;
        foreach($array as $k => $v){
            if($skip_up_to!=0 && $skip_up_to > $k){
                continue;
            }
            if( !self::if_match($array, $k) && $first_if_found==false ) {
                $prefix .= $v;
            }else{
                $first_if_found = true;
                if($capture_conditions){
                    if( (isset($array[$k]) && $array[$k]===')') && 
                        (isset($array[$k+1]) && $array[$k+1]===':') ) {
                        $capture_elseifcontent = false;
                        $capture_suffix = false;
                        $capture_conditions = false;
                        $skip_up_to = $k+2;
                        continue;
                    }
                    if(!isset($statements[$if_index]['conditions'])){
                        $statements[$if_index]['conditions'] = '';
                    }
                    $statements[$if_index]['conditions'] .= $v;
                    continue;
                }
                if($depth==0){
                    if(self::if_match($array, $k)){
                        $if_index++;
                        $depth++;
                        $capture_elseifcontent = false;
                        $capture_suffix = false;
                        $capture_conditions = true;
                        $skip_up_to = $k+3;
                        continue;
                    }
                }else{
                    if(self::if_match($array, $k)){
                        $depth++;
                    }
                }
                if( (isset($array[$k]) && $array[$k]==='e') && 
                    (isset($array[$k+1]) && $array[$k+1]==='n') && 
                    (isset($array[$k+2]) && $array[$k+2]==='d') && 
                    (isset($array[$k+3]) && $array[$k+3]==='i') && 
                    (isset($array[$k+4]) && $array[$k+4]==='f') && 
                    (isset($array[$k+5]) && $array[$k+5]===';') ) {
                    $depth--;
                    if($depth==0){
                        $capture_elseifcontent = false;
                        $capture_conditions = false;
                        $capture_suffix = true;
                        $skip_up_to = $k+6;
                        continue;
                    }
                }
                if($depth==1){
                    if( (isset($array[$k]) && $array[$k]==='e') && 
                        (isset($array[$k+1]) && $array[$k+1]==='l') &&
                        (isset($array[$k+2]) && $array[$k+2]==='s') &&
                        (isset($array[$k+3]) && $array[$k+3]==='e') &&
                        (isset($array[$k+4]) && $array[$k+4]==='i') &&
                        (isset($array[$k+5]) && $array[$k+5]==='f') &&
                        (isset($array[$k+6]) && $array[$k+6]===':') ) {
                        $capture_elseifcontent = true;
                        $capture_suffix = false;
                        $capture_conditions = false;
                        $skip_up_to = $k+7;
                        continue;
                    }
                }
                if($depth==0){
                    if($capture_suffix){
                        if(!isset($statements[$if_index]['suffix'])) $statements[$if_index]['suffix'] = ''; 
                        $statements[$if_index]['suffix'] .= $v;
                        continue;
                    }
                }
                if($depth>=1){
                    if($capture_elseifcontent){
                        if(!isset($statements[$if_index]['elseif_content'])) $statements[$if_index]['elseif_content'] = '';
                        $statements[$if_index]['elseif_content'] .= $v;
                        continue;
                    }
                }
                if($depth>=1){
                    // Capture everything that is inside the statement
                    if(!isset($statements[$if_index]['inner_content'])) $statements[$if_index]['inner_content'] = '';
                    $statements[$if_index]['inner_content'] .= $v;
                    continue;
                }
            }
        }
        $result = '';
        foreach($statements as $k => $v){
            $show_counter = 0;
            $conditions = explode('&&', $v['conditions']);
            $method = '&&';
            if(count($conditions)==1){
                $conditions = explode('||', $v['conditions']);
                $method = '||';
            }
            foreach($conditions as $ck => $cv){
                preg_match($re, $cv, $matches);
                $v1 = $matches[1];
                $operator = $matches[2];
                $v2 = $matches[3];
                $show = false;
                if($operator==='==' && $v1==$v2) $show = true;
                if($operator==='!=' && $v1!=$v2) $show = true;
                if($operator==='>=' && $v1>=$v2) $show = true;
                if($operator==='<=' && $v1<=$v2) $show = true;
                if($operator==='>' && $v1>$v2) $show = true;
                if($operator==='<' && $v1<$v2) $show = true;
                if($operator==='??' && (strpos($v1, $v2)!==FALSE)) $show = true;
                if($operator==='!??' && (!strpos($v1, $v2)===FALSE)) $show = true;
                if($show){
                    $show_counter++;
                }
            }
            if($method=='||' && $show_counter>0){
                $result .= SUPER_Common::filter_if_statements($v['inner_content']);
            }else{
                if(count($conditions)===$show_counter){
                    $result .= SUPER_Common::filter_if_statements($v['inner_content']);
                }else{
                    if(!empty($v['elseif_content'])) $result .= SUPER_Common::filter_if_statements($v['elseif_content']);
                }
            }
            if(!empty($v['suffix'])) $result .= $v['suffix'];
        }
        return $prefix.$result;
    }


    /**
     * Find if() match
     */
    public static function if_match($array=array(), $k=0){
        if( (isset($array[$k]) && $array[$k]==='i') && 
            (isset($array[$k+1]) && $array[$k+1]==='f') && 
            (isset($array[$k+2]) && $array[$k+2]==='(') ) {
            return true;
        }
        return false;       
    }

    /**
     * Get data-fields attribute based on value that contains tags e.g: {option;2}_{color;3} would convert to [option][color]
     * $names array()
     * $value string
     */
    public static function get_tags_attributes($value){
        if($value==='') return '';
        $names = array();
        $r = '/foreach\(([-_a-zA-Z0-9]{1,})|([-_a-zA-Z0-9]{1,})\[.*?(\):)|(?:<%|{)([-_a-zA-Z0-9]{1,})(?:}|%>)|(?:<%|{)([-_a-zA-Z0-9]{1,});.*?(?:}|%>)|(?:<%|{)([-_a-zA-Z0-9]{1,})\[.*?(?:}|%>)/';
        $str = $value;
        preg_match_all($r, $str, $m, PREG_SET_ORDER, 0);
        foreach($m as $k => $v){
            $l = count($v);
            if(empty($v[$l-1])) continue;
            $n = $v[$l-1];
            $names[$n] = $n;
        }
        if(!empty($names)){
            return ' data-tags="{' . implode('}{', $names) . '}" data-original="' . esc_attr($value) . '"';
        }
        return '';
    }

    /**
     * Get data-fields attribute based on value that contains tags e.g: {option;2}_{color;3} would convert to [option][color]
     * $names array()
     * $value string
     */
    public static function get_data_fields_attribute($x){
        extract( shortcode_atts( array( 'names'=>array(), 'value'=>'', 'bwc'=>false ), $x ) );
        if(empty($value)) return $names;
        // If field name doesn't contain any curly braces, then append and prepend them and continue;
        if($bwc===true){
            if ( strpos( $value, '{') === false ) {
                $value = '{'.$value.'}';   
            } 
        }
        $r = '/foreach\(([-_a-zA-Z0-9]{1,})|([-_a-zA-Z0-9]{1,})\[.*?(\):)|(?:<%|{)([-_a-zA-Z0-9]{1,})(?:}|%>)|(?:<%|{)([-_a-zA-Z0-9]{1,});.*?(?:}|%>)|(?:<%|{)([-_a-zA-Z0-9]{1,})\[.*?(?:}|%>)/';
        preg_match_all($r, $value, $m, PREG_SET_ORDER, 0);
        foreach($m as $k => $v){
            $l = count($v);
            if(empty($v[$l-1])) continue;
            $n = $v[$l-1];
            $names[$n] = $n;
        }
        return $names;
    }


    /**
     * Get global settings
     *
     * @since 4.6.0
     */
    public static function get_global_settings(){
        if(!isset(SUPER_Forms()->global_settings)){
            SUPER_Forms()->global_settings = get_option( 'super_settings', array() );
            if(is_array(SUPER_Forms()->global_settings)){
                SUPER_Forms()->global_settings = array_map('stripslashes_deep', SUPER_Forms()->global_settings);
            }else{
                SUPER_Forms()->global_settings = stripslashes(SUPER_Forms()->global_settings);
            }
        }
        return SUPER_Forms()->global_settings;
    }


    /**
     * Get default settings
     *
     * @since 4.6.0
     */
    public static function get_default_settings($settings=null){
        if(!isset(SUPER_Forms()->default_settings)){
            // First retrieve all the fields and their default value
            if( !class_exists( 'SUPER_Settings' ) )  require_once( 'class-settings.php' ); 
            $fields = SUPER_Settings::fields( $settings );
            // Loop through all the settings and create a nice array so we can save it to our database
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
            SUPER_Forms()->default_settings = $array;
        }
        return SUPER_Forms()->default_settings;
    }


    /**
     * Get form settings
     *
     * @since 3.8.0
     */
    public static function get_form_settings($form_id) {
        if( !class_exists( 'SUPER_Settings' ) )  require_once( 'class-settings.php' ); 
        $form_settings = get_post_meta( absint($form_id), '_super_form_settings', true );
        if(!$form_settings) $form_settings = array();
        $global_settings = self::get_global_settings();
        $defaults = SUPER_Settings::get_defaults($global_settings);
        $global_settings = array_merge( $defaults, $global_settings );
        if(is_array($form_settings)){
            $settings = array_merge( $global_settings, $form_settings );
        }else{
            $settings = $form_settings;
        }
        return apply_filters( 'super_form_settings_filter', $settings, array( 'id'=>$form_id ) );
    }


    /**
     * Generate array with default values for each settings of a specific element 
     *
     * @since 3.8.0
     */
    public static function generate_array_default_element_settings($shortcodes=false, $group='', $tag='') {
        $defaults = array();
        if($shortcodes==false) $shortcodes = SUPER_Shortcodes::shortcodes();
        foreach($shortcodes[$group]['shortcodes'][$tag]['atts'] as $k => $v){
            if( !empty( $v['fields'] ) ) {
                foreach( $v['fields'] as $fk => $fv ) {
                    if( (isset($fv['type'])) && ($fv['type']=='multicolor') ) {
                        foreach( $fv['colors'] as $ck => $cv ) {
                            if( isset($fv['default']) ) $defaults[$ck] = $cv['default'];
                        }
                    }else{
                        if( isset($fv['default']) ) $defaults[$fk] = $fv['default'];
                    }
                }
            }
        }
        return $defaults;
    }


    /**
     * Get the entry data based on a WC order ID
     *
     * @since 3.8.0
     */
    public static function get_entry_data_by_wc_order_id($order_id, $skip){
        global $wpdb;
        $contact_entry_id = $wpdb->get_var("
            SELECT post_id 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_super_contact_entry_wc_order_id' 
            AND meta_value = '" . absint($order_id) . "'"
        );
        $data = get_post_meta( absint($contact_entry_id), '_super_contact_entry_data', true );
        if(!empty($data)){
            unset($data['hidden_form_id']);
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => $contact_entry_id,
                'type' => 'entry_id'
            );
            $entry_status = get_post_meta( absint($contact_entry_id), '_super_contact_entry_status', true );
            $data['hidden_contact_entry_status'] = array(
                'name' => 'hidden_contact_entry_status',
                'value' => $entry_status,
                'type' => 'var'
            );
            $entry_title = get_the_title(absint($contact_entry_id));
            $data['hidden_contact_entry_title'] = array(
                'name' => 'hidden_contact_entry_title',
                'value' => $entry_title,
                'type' => 'var'
            );
            if(!empty($skip)){
                $skip_fields = explode( "|", $skip );
                foreach($skip_fields as $field_name){
                    if( isset($data[$field_name]) ) {
                        unset($data[$field_name]);
                    }
                }
            }
        }
        return $data;
    }


    /**
     * Get the default value of a specific element setting
     *
     * @since 3.8.0
     */
    public static function get_default_element_setting_value($shortcodes=false, $group='', $tag='', $tab='', $name='') {
        if($shortcodes==false) $shortcodes = SUPER_Shortcodes::shortcodes();
        if(isset($shortcodes[$group]['shortcodes'][$tag]['atts'][$tab]['fields'][$name]['default'])){
            return $shortcodes[$group]['shortcodes'][$tag]['atts'][$tab]['fields'][$name]['default'];
        }else{
            return '';
        }
    }


    /**
     * Get the absolute default field setting value based on group ($parent) and field tag ($name)
     *
     * @since 3.4.0
     */
    public static function get_default_setting_value( $parent, $name ) {
        $fields = SUPER_Settings::fields();
        return $fields[$parent]['fields'][$name]['default'];
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
                // @since 1.0.0
                'before_validating_form_hook' => array(
                    array( 'name' => 'init_replace_datepicker_default_value_tags' ),
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' ),
                ),
                'after_validating_form_hook' => array(),

                'after_initializing_forms_hook' => array(
                    array( 'name' => 'init_replace_datepicker_default_value_tags' ),
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),
                'after_field_change_blur_hook' => array(
                    array( 'name' => 'init_replace_datepicker_default_value_tags' ),
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'calculate_distance' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),

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

                // @since 3.3.0
                'after_appending_duplicated_column_hook' => array(),

                // @since 4.7.0
                'before_submit_hook' => array(),

                // @since 4.9.0
                'after_init_common_fields' => array(
                    array( 'name' => 'init_distance_calculators' ),
                    array( 'name' => 'init_color_pickers' ),
                    array( 'name' => 'init_carouseljs' ),
                    array( 'name' => 'init_tooltips' ),
                    array( 'name' => 'init_datepicker' ),
                    array( 'name' => 'init_adaptive_placeholder' ),
                    array( 'name' => 'init_masked_input' ),
                    array( 'name' => 'init_currency_input' ),
                    array( 'name' => 'init_colorpicker' ),
                    array( 'name' => 'init_slider_field' ),
                    array( 'name' => 'init_button_colors' ),
                    array( 'name' => 'init_text_editors' ),
                    array( 'name' => 'init_fileupload_fields' ),
                    array( 'name' => 'init_international_phonenumber_fields' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'set_keyword_tags_width' ),
                    array( 'name' => 'rating' ),
                    array( 'name' => 'init_signature' ) // This should actually be called by the signature element, but it has been like this since the start.
                ),
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
     *  @param  boolean  $display  @since 3.4.0
     *  @param  boolean  $loading  @since 3.4.0
     *  @param  boolean  $json  @since 4.8.0 
     *  @param  boolean  $response_data  @since 4.9.0 
     *
     * @since 1.0.6
     * @deprecated 4.9.0 Use output_message()
     * @see output_message()
     */
    public static function output_error( $error=true, $msg='Missing required parameter $msg!', $redirect=null, $fields=array(), $display=true, $loading=false, $json=true, $response_data=array() ) {        
        _deprecated_function( __FUNCTION__, '4.9.0', 'output_message()' );
        self::output_message( $error, $msg, $redirect, $fields, $display, $loading, $json, $response_data );
    }
    public static function output_message( $error=true, $msg='Missing required parameter $msg!', $redirect=null, $fields=array(), $display=true, $loading=false, $json=true, $response_data=array() ) {        
        if($json!=true){
            // We will want to return the error/success message HTML instantly
            echo $msg;
        }else{
            // We will want to return a JSON string with the error/success message data
            $result = array(
                'error' => $error,
                'msg' => $msg,
            );
            if( $redirect!=null ) {
                $result['redirect']= $redirect;
            }
            $result['fields'] = $fields;
            $result['display'] = $display; // @since 3.4.0 - option to hide the message
            $result['loading'] = $loading; // @since 3.4.0 - option to keep the form at a loading state, when enabled, it will keep submit button at loading state and will not hide the form and prevents to scroll to top of page
            $result['response_data'] = $response_data; // @since 4.9.0 - holds the contact entry ID (if one was created, and the form ID), might be used in the future for other data.
            echo json_encode( $result );
        }
        die();
    }

    /**
     * Output the form elements on the backend (create form page) to allow to edit the elements
     *
     *  @param  integer  $id
     *
     * @since 1.0.0
     */
    public static function generate_backend_elements( $id=null, $shortcodes=null, $elements=null ) {
        
        // @since 1.0.6 - Make sure that we have all settings even if this form hasn't saved it yet when new settings where added by a add-on
        require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
        $settings = SUPER_Common::get_form_settings($id);
        $html = '';
        if( $elements!=false ) {
            if( $elements==null ) {
                $elements = get_post_meta( $id, '_super_elements', true );
            }
            // If elements are saved as JSON in database, convert to array
            if( !is_array( $elements) ) {
                $shortcode = json_decode(stripslashes($elements), true);
                if( $shortcode==null ) {
                    $shortcode = json_decode($elements, true);
                }
                // @since 4.3.0 - required to make sure any backslashes used in custom regex is escaped properly
                $elements = wp_slash($shortcode);
            }
            if( is_array( $elements) ) {
                foreach( $elements as $k => $v ) {
                    if( empty($v['data']) ) $v['data'] = null;
                    if( empty($v['inner']) ) $v['inner'] = null;
                    $html .= SUPER_Shortcodes::output_builder_html( array('tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings) );
                }
            }         
        }
        
        return $html;
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
    public static function generate_random_code($codesettings, $submittingForm=false, $counter=0){
        global $wpdb;
        // First check if we are submitting the form or not
        $invoice_key = (!empty($codesettings['invoice_key']) ? $codesettings['invoice_key'] : '');
        $length = $codesettings['len'];
        $characters = $codesettings['char'];
        $prefix = $codesettings['pre'];
        $invoice = $codesettings['inv'];
        $invoice_padding = $codesettings['invp'];
        $suffix = $codesettings['suf'];
        $uppercase = $codesettings['upper'];
        $lowercase = $codesettings['lower'];
        $char  = '';
        if( ($characters=='1') || ($characters=='2') || ($characters=='3') ) {
            $char .= '0123456789';
        }
        if( ($characters=='1') || ($characters=='2') || ($characters=='4') ) {
            if($uppercase=='true') $char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
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
        // @since 2.8.0 - invoice numbers
        if( $invoice=='true' ) {
            if ( ctype_digit( (string)$invoice_padding ) ) {
                $table = $wpdb->prefix . 'options';
                // This is the global invoice key ID, if user defines a custom one, we will save it under a different option name.
                // This allows for multiple usecases, for instance if you have a form that needs to generate a invoice, and if you have 
                // a seperate form that generates quotes. That way a next quote number could be "0025" while the next invoice number would be "0018"
                $option_name_old = '_super_form_invoice_number';
                $option_name = '_sf_invoice_number';
                if(!empty($invoice_key)){
                    $option_name_old .= '_' . $invoice_key;
                    $option_name .= '_' . $invoice_key;
                }
                $invoiceNumber = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = '%s' OR option_name = '%s'  ", $option_name_old, $option_name));
                // If this number doesn't exist yet create it
                if(!$invoiceNumber){
                    $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES ( %s, %d, %s ) ", array( $option_name, 0, 'no' ) ) );
                }
                $invoiceNumber = intval($invoiceNumber);
                if($submittingForm){
                    $invoiceNumber = $invoiceNumber+1;
                    $wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value = %d WHERE option_name = '%s' OR option_name = '%s'", $invoiceNumber, $option_name_old, $option_name));
                }else{
                    if($invoiceNumber===0){
                        $invoiceNumber = 1;
                    }
                }
                $code .= sprintf('%0'.$invoice_padding.'d', $invoiceNumber );
            }
        }
        $code = $prefix.$code.$suffix;
        if($submittingForm===false){
            // If we are not submitting the form we can return the code instantly
            return $code;
        }else{
            // Upon submitting the form, make sure code doesn't exist yet, if it does generate a new one
            $option_name_old = '_super_contact_entry_code-' . $code;
            $option_name = '_sf_unique_code-' . $code;
            $currentCode = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = '%s' OR option_name = '%s'", $option_name_old, $option_name));
            // If this code doesn't exist yet create it
            if(!$currentCode){
                $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES ( %s, %s, %s ) ", array( $option_name, $code, 'no' ) ) );
                return $code;
            }
            if($counter<50){ // just to make sure there won't be an endless loop
                $counter++;
                return self::generate_random_code(
                    array('invoice_key' => $invoice_key, 'len' => $length, 'char' => $characters, 'pre' => $prefix, 'inv' => $invoice, 'invp' => $invoice_padding, 'suf' => $suffix, 'upper' => $uppercase, 'lower' => $lowercase),
                    $submittingForm, 
                    $counter
                );
            }
        }
    }
    
    
    /**
     * Generate random folder number
     *
     * @since 1.0.0
    */
    public static function generate_random_folder( $folder ) {
        // Random folder must be 13 characters long
        // Since 32 bit system only allow a maximum of 2147483647 as int value
        // we will generate 2 random numbers seperately and combine them as one
        $folderName = rand(1000000, 9999999) . rand(100000, 999999);
        $folderPath = trailingslashit($folder) . $folderName;
        if( file_exists( $folderPath ) ) {
            self::generate_random_folder( $folder );
        }else{
            if ( !mkdir($folderPath, 0755, true) ) {
                $error = error_get_last();
                SUPER_Common::output_message(true, '<strong>' . esc_html__( 'Upload failed', 'super-forms' ) . ':</strong> ' . $error['message']);
            }
            return array(
                'folderPath' => $folderPath,
                'folderName' => $folderName
            );
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
    public static function decode_textarea_v5( $v, $value ) {
        if( empty( $value ) ) return $value;
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            if($v['type']==='html'){
                return stripslashes($value);
            }
            return esc_html(stripslashes($value));
        }
    }
    public static function decode_textarea( $value ) {
        // DEPCRECATED function, only being used by older Add-ons
        if( empty( $value ) ) return $value;
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            return esc_html(stripslashes($value));
        }
    }
    public static function decode( $value ) {
        if( empty( $value ) ) return $value;
        if( is_string( $value ) ) {
            // @since 3.9.0 - do not decode base64 images (signature)
            if ( strpos( $value, 'data:image/png;base64,') !== false ) {
                return $value;
            }else{
                return strip_tags( stripslashes( $value ), '<br>' );
            }
        }
        // @since 1.4 - also return integers
        return absint( $value );
    }
    public static function decode_email_header( $value ) {
        if( empty( $value ) ) return $value;
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            $emails = array();
            $value = explode( ",", $value );
            foreach($value as $v){
                if(sanitize_email( $v )){
                    $emails[] = sanitize_email( $v );
                }
            }
            return implode(',', $emails);
        }
    }

    public static function get_user_email() {
        $user_id = get_current_user_id();
        $user_email = '';
        if($user_id!==0){
            $user = get_user_by( 'ID', $user_id );
            if($user!==false){
                $user_email = $user->user_email;
            }
        }
        return $user_email;
    }

    /**
     * Create an array with tags that can be used in emails, this function also replaced tags when $value and $data are set
     *
     * @since 1.0.6
    */
    public static function email_tags( $value=null, $data=null, $settings=null, $user=null, $skip=true, $skipSecrets=false, $skipOptions=false ) {
        if( ($value==='') && ($skip==true) ) return '';
        $current_author = null;
        $current_user = wp_get_current_user();
        $product = false;

        // @since 4.9.402 - check if switching from language, if so grab initial tag values from session
        if( (isset($_POST['action'])) && ($_POST['action']=='super_language_switcher') ) {
            // Try to get data from session
            $tags = SUPER_Common::getClientData( 'tags_values' );
        }else{
            // @since 4.0.0 - retrieve author id if on profile page
            // First check if we are on the author profile page, and see if we can find author based on slug
            //get_current_user_id()
            $page_url = ( isset($_SERVER['HTTPS']) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $author_name = basename($page_url);
            $current_author = ( isset($_GET['author']) ? get_user_by('id', absint($_GET['author'])) : get_user_by('slug', $author_name) );
            if( $current_author ) {
                // This is an author profile page
                $author_id = $current_author->ID;
                $user_info = get_userdata($author_id);
                if($user_info!=false){
                    $author_email = $user_info->user_email;
                }
            }
            global $post;
            if( !isset( $post ) ) {
                if( isset( $_REQUEST['post_id'] ) ) {
                    $post_title = get_the_title( absint( $_REQUEST['post_id'] ) );
                    $post_id = (string)$_REQUEST['post_id'];
                    if ( class_exists( 'WooCommerce' ) ) {
                        $product = wc_get_product( $post_id );
                        if($product){
                            $product_regular_price = $product->get_regular_price();
                            $product_sale_price = $product->get_sale_price();
                            $product_price = $product->get_price();
                        }
                    }
                }
            }else{
                $post_title = get_the_title($post->ID);
                $post_permalink = get_permalink($post->ID);
                $post_id = (string)$post->ID;
                if(!isset($author_id)) $author_id = $post->post_author;
                $user_info = get_userdata($author_id);
                $current_author = $user_info;
                if($user_info!=false){
                    if(!isset($author_email)) $author_email = $user_info->user_email;
                }
                if ( class_exists( 'WooCommerce' ) ) {
                    $product = wc_get_product( $post_id );
                    if($product){
                        $product_regular_price = $product->get_regular_price();
                        $product_sale_price = $product->get_sale_price();
                        $product_price = $product->get_price();
                   }
               }
            }
 
            // Make sure all variables are set
            if(!isset($post_id)) $post_id = '';
            if(!isset($post_title)) $post_title = '';
            if(!isset($post_permalink)) $post_permalink = '';
            if(!isset($author_id)) $author_id = '';
            if(!isset($author_email)) $author_email = '';
    
            if(!isset($product_regular_price)) $product_regular_price = 0;
            if(!isset($product_sale_price)) $product_sale_price = 0;
            if(!isset($product_price)) $product_price = 0;
            
            $user_roles = implode(',', $current_user->roles); // @since 3.2.0
    
            // @since 3.3.0 - save http_referrer into a session
            // Only set if `{server_http_referrer_session}` tag was found
            // Or maybe rely on a filter to overrule this condition?
            $http_referrer = '';
            if(strpos($value, '{server_http_referrer_session}')!==false){
                $http_referrer = SUPER_Common::getClientData( 'server_http_referrer' );
                if( $http_referrer==false ) {
                    $http_referrer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
                }
                if(!empty($http_referrer)){
                    SUPER_Common::setClientData( array( 'name'=> 'server_http_referrer', 'value'=>$http_referrer  ) );
                }else{
                    SUPER_Common::setClientData( array( 'name'=> 'server_http_referrer', 'value'=>false ) );
                }
            }
            
            // @since 3.4.0 - Retrieve latest contact entry based on form ID
            // @since 3.4.0 - retrieve the lock count
            $last_entry_id = 0;
            $last_entry_status = '';
            $form_submission_count = '';
            if(!isset($settings['id'])) {
                $form_id = 0;
            }else{
                $form_id = $settings['id'];
            }
            if($form_id!=0){
                global $wpdb;
                $table = $wpdb->prefix . 'posts';
                $entry = $wpdb->get_results("
                SELECT  ID 
                FROM    $table 
                WHERE   post_parent = $form_id AND
                        post_status IN ('publish','super_unread','super_read') AND 
                        post_type = 'super_contact_entry'
                ORDER BY ID DESC
                LIMIT 1");
                if( isset($entry[0])) {
                    $last_entry_id = absint($entry[0]->ID);
                    $last_entry_status = get_post_meta( $entry[0]->ID, '_super_contact_entry_status', true );
                }
                $form_submission_count = absint(get_post_meta( $form_id, '_super_submission_count', true ));
            }
    
            // @since 4.7.7     - Get last entry status bas on currently logged in user
            $user_last_entry_id = 0;
            $user_last_entry_status = '';
            if( (isset($current_user)) && ($current_user->ID>0) ) {
                global $wpdb;
                $entry = $wpdb->get_results(
                    $wpdb->prepare("
                        SELECT  ID
                        FROM    $wpdb->posts
                        WHERE   post_parent = %d AND 
                                post_author = %d AND
                                post_status IN ('publish','super_unread','super_read') AND 
                                post_type = 'super_contact_entry'
                        ORDER BY ID DESC
                        LIMIT 1",
                        array( $form_id, $current_user->ID )
                    )
                );
                if( isset($entry[0])) {
                    $user_last_entry_id = absint($entry[0]->ID);
                    $user_last_entry_status = get_post_meta( $entry[0]->ID, '_super_contact_entry_status', true );
                }
            }
            
            // @since 4.7.7     - Get last entry status bas on currently logged in user
            $user_last_entry_id_any_form = 0;
            $user_last_entry_status_any_form = '';
            if( (isset($current_user)) && ($current_user->ID>0) ) {
                global $wpdb;
                $entry = $wpdb->get_results(
                    $wpdb->prepare("
                        SELECT  ID
                        FROM    $wpdb->posts
                        WHERE   post_author = %d AND
                                post_status IN ('publish','super_unread','super_read') AND 
                                post_type = 'super_contact_entry'
                        ORDER BY ID DESC
                        LIMIT 1",
                        array( $current_user->ID )
                    )
                );
                if( isset($entry[0])) {
                    $user_last_entry_id_any_form = absint($entry[0]->ID);
                    $user_last_entry_status_any_form = get_post_meta( $entry[0]->ID, '_super_contact_entry_status', true );
                }
            }
    
            $_SERVER_HTTP_REFERER = '';
            if( isset($_SERVER['HTTP_REFERER']) ) {
                $_SERVER_HTTP_REFERER = $_SERVER['HTTP_REFERER'];
            }

            // Generated PDF file (PDF Generator Add-on)
            $_generated_pdf_file_label = '';
            $_generated_pdf_file_name = '';
            $_generated_pdf_file_url = '';
            if( isset( $data['_generated_pdf_file']['files'] ) ) {
                foreach( $data['_generated_pdf_file']['files'] as $fk => $fv ) {
                    if(!isset($fv['url'])) continue;
                    $_generated_pdf_file_label = esc_html($fv['label']);
                    $_generated_pdf_file_name = esc_html($fv['name']);
                    $linkUrl = esc_url($fv['url']);
                    if( !empty( $fv['attachment'] ) ) { // only if file was inserted to Media Library
                        $linkUrl = wp_get_attachment_url( $fv['attachment'] );
                    }
                    $_generated_pdf_file_url = $linkUrl;
                }
            }
    
            $tags = array(
                'field_*****' => array(
                    esc_html__( 'Any field value submitted by the user', 'super-forms' ),
                    ''
                ),
                'field_label_*****' => array(
                    esc_html__( 'Any field value submitted by the user', 'super-forms' ),
                    ''
                ),
    
                // @since 4.4.0 - option to retrieve setting values from the form settings
                'form_setting_*****' => array(
                    esc_html__( 'Any setting value used for the form', 'super-forms' ),
                    ''
                ),
                'option_*****' => array(
                    esc_html__( 'Any option value from the database', 'super-forms' ),
                    ''
                ),
                'option_admin_email' => array(
                    esc_html__( 'E-mail address of blog administrator', 'super-forms' ),
                    get_option('admin_email')
                ),
                'option_blogname' => array(
                    esc_html__( 'Weblog title; set in General Options', 'super-forms' ),
                    get_option('blogname')
                ),
                'option_blogdescription' => array(
                    esc_html__( 'Tagline for your blog; set in General Options', 'super-forms' ),
                    get_option('blogdescription')
                ),
                'option_blog_charset' => array(
                    esc_html__( 'Blog Charset', 'super-forms' ),
                    get_option('blog_charset')
                ),
                'option_date_format' => array(
                    esc_html__( 'Date Format', 'super-forms' ),
                    get_option('date_format')
                ),            
                'option_default_category' => array(
                    esc_html__( 'Default post category; set in Writing Options', 'super-forms' ),
                    get_option('default_category')
                ),
                'option_home' => array(
                    esc_html__( 'The blog\'s home web address; set in General Options', 'super-forms' ),
                    home_url()
                ),
                'option_siteurl' => array(
                    esc_html__( 'WordPress web address; set in General Options', 'super-forms' ),
                    get_option('siteurl')
                ),
                'option_template' => array(
                    esc_html__( 'The current theme\'s name; set in Presentation', 'super-forms' ),
                    get_option('template')
                ),
                'option_start_of_week' => array(
                    esc_html__( 'Start of the week', 'super-forms' ),
                    get_option('start_of_week')
                ),
                'option_upload_path' => array(
                    esc_html__( 'Default upload location; set in Miscellaneous Options', 'super-forms' ),
                    get_option('upload_path')
                ),
                'option_posts_per_page' => array(
                    esc_html__( 'Posts per page', 'super-forms' ),
                    get_option('posts_per_page')
                ),
                'option_posts_per_rss' => array(
                    esc_html__( 'Posts per RSS feed', 'super-forms' ),
                    get_option('posts_per_rss')
                ),
                'real_ip' => array(
                    esc_html__( 'Retrieves the submitter\'s IP address', 'super-forms' ),
                    self::real_ip()
                ),
                'loop_label' => array(
                    esc_html__( 'Retrieves the field label for the field loop {loop_fields}', 'super-forms' ),
                ),
                'loop_value' => array(
                    esc_html__( 'Retrieves the field value for the field loop {loop_fields}', 'super-forms' ),
                ),
                'loop_fields' => array(
                    esc_html__( 'Retrieves the loop anywhere in your email', 'super-forms' ),
                ),
                'post_title' => array(
                    esc_html__( 'Retrieves the current page or post title', 'super-forms' ),
                    $post_title
                ),
                'post_id' => array(
                    esc_html__( 'Retrieves the current page or post ID', 'super-forms' ),
                    $post_id
                ),
    
                // @since 4.0.0 - return profile author ID and E-mail with tag
                'author_id' => array(
                    esc_html__( 'Retrieves the current author ID', 'super-forms' ),
                    $author_id
                ),
                'author_email' => array(
                    esc_html__( 'Retrieves the current author email', 'super-forms' ),
                    $author_email
                ),
    
                // @since 2.9.0 - return post author ID and E-mail with tag
                'post_author_id' => array(
                    esc_html__( 'Retrieves the current page or post author ID', 'super-forms' ),
                    $author_id
                ),
                'post_author_email' => array(
                    esc_html__( 'Retrieves the current page or post author email', 'super-forms' ),
                    $author_email
                ),
    
                // @since 3.0.0 - return post URL (permalink) with tag
                'post_permalink' => array(
                    esc_html__( 'Retrieves the current page URL', 'super-forms' ),
                    $post_permalink
                ),
    
    
                // @since 1.1.6
                'user_login' => array(
                    esc_html__( 'Retrieves the current logged in user login (username)', 'super-forms' ),
                    $current_user->user_login
                ),
                'user_email' => array(
                    esc_html__( 'Retrieves the current logged in user email', 'super-forms' ),
                    $current_user->user_email
                ),
                'user_firstname' => array(
                    esc_html__( 'Retrieves the current logged in user first name', 'super-forms' ),
                    $current_user->user_firstname
                ),
                'user_lastname' => array(
                    esc_html__( 'Retrieves the current logged in user last name', 'super-forms' ),
                    $current_user->user_lastname
                ),
                'user_display' => array(
                    esc_html__( 'Retrieves the current logged in user display name', 'super-forms' ),
                    $current_user->display_name
                ),
                'user_id' => array(
                    esc_html__( 'Retrieves the current logged in user ID', 'super-forms' ),
                    $current_user->ID
                ),
                'user_roles' => array(
                    esc_html__( 'Retrieves the current logged in user roles', 'super-forms' ),
                    $user_roles
                ),
    
                // @since 3.3.0 - tags to retrieve http_referrer (users previous location), and timestamp and date values
                'server_http_referrer' => array(
                    esc_html__( 'Retrieves the location where user came from (if exists any) before loading the page with the form', 'super-forms' ),
                    $_SERVER_HTTP_REFERER
                ),
                'server_http_referrer_session' => array(
                    esc_html__( 'Retrieves the location where user came from from a session (if exists any) before loading the page with the form', 'super-forms' ),
                    $http_referrer
                ),
                'server_timestamp_gmt' => array(
                    esc_html__( 'Retrieves the server timestamp (UTC/GMT)', 'super-forms' ),
                    strtotime(date_i18n('Y-m-d H:i:s', false, 'gmt'))
                ),
                'server_day_gmt' => array(
                    esc_html__( 'Retrieves the current day of the month (UTC/GMT)', 'super-forms' ),
                    date_i18n('d', false, 'gmt')
                ),
                'server_month_gmt' => array(
                    esc_html__( 'Retrieves the current month of the year (UTC/GMT)', 'super-forms' ),
                    date_i18n('m', false, 'gmt')
                ),
                'server_year_gmt' => array(
                    esc_html__( 'Retrieves the current year of time (UTC/GMT)', 'super-forms' ),
                    date_i18n('Y', false, 'gmt')
                ),
                'server_hour_gmt' => array(
                    esc_html__( 'Retrieves the current hour of the day (UTC/GMT)', 'super-forms' ),
                    date_i18n('H', false, 'gmt')
                ),
                'server_minute_gmt' => array(
                    esc_html__( 'Retrieves the current minute of the hour (UTC/GMT)', 'super-forms' ),
                    date_i18n('i', false, 'gmt')
                ),
                'server_seconds_gmt' => array(
                    esc_html__( 'Retrieves the current second of the minute (UTC/GMT)', 'super-forms' ),
                    date_i18n('s', false, 'gmt')
                ),
    
                // @since 3.4.0 - tags to return local times
                'server_timestamp' => array(
                    esc_html__( 'Retrieves the server timestamp (Local time)', 'super-forms' ),
                    strtotime(date_i18n('Y-m-d H:i:s', false, false))
                ),
                'server_day' => array(
                    esc_html__( 'Retrieves the current day of the month (Local time)', 'super-forms' ),
                    date_i18n('d', false, false)
                ),
                'server_month' => array(
                    esc_html__( 'Retrieves the current month of the year (Local time)', 'super-forms' ),
                    date_i18n('m', false, false)
                ),
                'server_year' => array(
                    esc_html__( 'Retrieves the current year of time (Local time)', 'super-forms' ),
                    date_i18n('Y', false, false)
                ),
                'server_hour' => array(
                    esc_html__( 'Retrieves the current hour of the day (Local time)', 'super-forms' ),
                    date_i18n('H', false, false)
                ),
                'server_minute' => array(
                    esc_html__( 'Retrieves the current minute of the hour (Local time)', 'super-forms' ),
                    date_i18n('i', false, false)
                ),
                'server_seconds' => array(
                    esc_html__( 'Retrieves the current second of the minute (Local time)', 'super-forms' ),
                    date_i18n('s', false, false)
                ),
    
                // @since 3.4.0 - retrieve the lock
                'submission_count' => array(
                    esc_html__( 'Retrieves the total submission count (if form locker is used)', 'super-forms' ),
                    $form_submission_count
                ),
    
                // @since 3.4.0 - retrieve the last entry status
                'last_entry_status' => array(
                    esc_html__( 'Retrieves the latest Contact Entry status', 'super-forms' ),
                    $last_entry_status
                ),
                // @since 4.7.7 - retrieve the last entry ID
                'last_entry_id' => array(
                    esc_html__( 'Retrieves the latest Contact Entry ID', 'super-forms' ),
                    $last_entry_id
                ),
                
                // @since 4.7.7 - retrieve last entry status and ID of the logged in user
                'user_last_entry_status' => array(
                    esc_html__( 'Retrieves the latest Contact Entry status of the logged in user based on current form ID', 'super-forms' ),
                    $user_last_entry_status
                ),
                'user_last_entry_id' => array(
                    esc_html__( 'Retrieves the latest Contact Entry ID of the logged in user based on current form ID', 'super-forms' ),
                    $user_last_entry_id
                ),
                'user_last_entry_status_any_form' => array(
                    esc_html__( 'Retrieves the latest Contact Entry status of the logged in user for any form', 'super-forms' ),
                    $user_last_entry_status_any_form
                ),
                'user_last_entry_id_any_form' => array(
                    esc_html__( 'Retrieves the latest Contact Entry ID of the logged in user for any form', 'super-forms' ),
                    $user_last_entry_id_any_form
                ),
                
                // PDF Generator data
                '_generated_pdf_file_label' => array(
                    esc_html__( 'Generated PDF Label', 'super-forms' ),
                    $_generated_pdf_file_label
                ),
                '_generated_pdf_file_name' => array(
                    esc_html__( 'Generated PDF name', 'super-forms' ),
                    $_generated_pdf_file_name
                ),
                '_generated_pdf_file_url' => array(
                    esc_html__( 'Generated PDF URL', 'super-forms' ),
                    $_generated_pdf_file_url
                ),
    
            );
            
            // Make sure to replace tags with correct user data
            if( $user!=null ) {
                $user_tags = array(
                    'user_id' => array(
                        esc_html__( 'User ID', 'super-forms' ),
                        $user->ID
                    ),
                    'user_login' => array(
                        esc_html__( 'User username', 'super-forms' ),
                        $user->user_login
                    ),
                    'display_name' => array(
                        esc_html__( 'User display name', 'super-forms' ),
                        $user->user_nicename
                    ),
                    'user_nicename' => array(
                        esc_html__( 'User nicename', 'super-forms' ),
                        $user->user_nicename
                    ),
                    'user_email' => array(
                        esc_html__( 'User email', 'super-forms' ),
                        $user->user_email
                    ),
                    'user_url' => array(
                        esc_html__( 'User URL (website)', 'super-forms' ),
                        $user->user_url
                    ),
                    'user_registered' => array(
                        esc_html__( 'User Registered (registration date)', 'super-forms' ),
                        $user->user_registered
                    )
                );
                $tags = array_merge( $tags, $user_tags );
            }
    
    
            // @since 3.6.0 - tags to retrieve cart information
            if ( class_exists( 'WooCommerce' ) ) {
                global $woocommerce;
                if($woocommerce->cart!=null){
                    $items = $woocommerce->cart->get_cart();
                    $cart_total = $woocommerce->cart->get_cart_total();
                    $cart_total_float = $woocommerce->cart->total;
                    $cart_items = '';
                    $cart_items_price = '';
                    foreach($items as $item => $values) { 
                        $cartProduct =  wc_get_product( $values['data']->get_id() ); 
                        $cart_items .= absint($values['quantity']) . 'x - ' . $cartProduct->get_title() . '<br />'; 
                        $cart_items_price .= absint($values['quantity']) . 'x - ' . $cartProduct->get_title() . ' (' . wc_price(get_post_meta($values['product_id'], '_price', true)) . ')<br />'; 
                    }
                }else{
                    $cart_total = 0;
                    $cart_total_float = 0;
                    $cart_items = '';
                    $cart_items_price = '';
                }
                $wc_tags = array(
                    'wc_cart_total' => array(
                        esc_html__( 'WC Cart Total', 'super-forms' ),
                        $cart_total
                    ),
                    'wc_cart_total_float' => array(
                        esc_html__( 'WC Cart Total (float format)', 'super-forms' ),
                        $cart_total_float
                    ),
                    'wc_cart_items' => array(
                        esc_html__( 'WC Cart Items', 'super-forms' ),
                        $cart_items
                    ),
                    'wc_cart_items_price' => array(
                        esc_html__( 'WC Cart Items + Price', 'super-forms' ),
                        $cart_items_price
                    )
                );
                // Only add if product exists/found
                if($product){
                    $wc_tags['product_regular_price'] = array( esc_html__( 'Product Regular Price', 'super-forms' ), $product_regular_price);
                    $wc_tags['product_sale_price'] = array( esc_html__( 'Product Sale Price', 'super-forms' ), $product_sale_price);
                    $wc_tags['product_price'] = array( esc_html__( 'Product Price', 'super-forms' ), $product_price);
                }
                $tags = array_merge( $tags, $wc_tags );
            }
            $tags = apply_filters( 'super_email_tags_filter', $tags );
            // Only store in case the language switch is enabled
            if(!empty($settings['i18n_switch']) && $settings['i18n_switch'] ==='true'){
                SUPER_Common::setClientData( array( 'name'=> 'tags_values', 'value'=>$tags  ) );
            }
        }
        
        // Return the new value with tags replaced for data
        if( $value!=null ) {

            // First loop through all the data (submitted by the user)
            if( $data!=null ) {
                foreach( $data as $k => $v ) {
                    if( isset( $v['type'] ) && $v['type']==='files' ) {
                        $allFileNames = array();
                        $allFileUrls = array();
                        $allFileLinks = array();
                        foreach($v['files'] as $fk => $fv){
                            if(!isset($fv['url']) && isset($fv['datauristring'])){ 
                                $fv['url'] = $fv['datauristring']; 
                                $v['files'][$fk]['url'] = $fv['datauristring']; 
                            }
                            if(!isset($fv['type']) && isset($fv['datauristring'])){ 
                                $fv['type'] = 'pdf'; 
                                $v['files'][$fk]['type'] = 'pdf'; 
                            }
                            if(!isset($fv['attachment']) && isset($fv['datauristring'])){ 
                                $fv['attachment'] = 0; 
                                $v['files'][$fk]['attachment'] = 0; 
                            }
                            $allFileNames[] = self::decode($fv['value']);
                            $allFileUrls[] = self::decode($fv['url']);
                            $allFileLinks[] = self::decode('<a href="'.esc_attr($fv['url']).'">'.$fv['value'].'</a>');
                        }
                        // Below filter should return a string, if it's still an array we will convert it into a string seperated by line breaks
                        $allFileNames = apply_filters( 'super_filter_all_file_names_filter', $allFileNames, array( 'fieldName'=>$k, 'fieldData'=>$v ) );
                        $allFileUrls = apply_filters( 'super_filter_all_file_urls_filter', $allFileUrls, array( 'fieldName'=>$k, 'fieldData'=>$v ) );
                        $allFileLinks = apply_filters( 'super_filter_all_file_links_filter', $allFileLinks, array( 'fieldName'=>$k, 'fieldData'=>$v ) );
                        if(is_array($allFileNames)) $allFileNames = implode('<br />', $allFileNames);
                        if(is_array($allFileUrls)) $allFileUrls = implode('<br />', $allFileUrls);
                        if(is_array($allFileLinks)) $allFileLinks = implode('<br />', $allFileLinks);
                        foreach($v['files'] as $fk => $fv){
                            // Returns the file name/basename by default e.g: `example.png`
                            $value = str_replace( '{' . $k . '}', $allFileNames, $value );
                            $value = str_replace( '{' . $k . ';allFileNames}', $allFileNames, $value ); // returns a list of all file names
                            $value = str_replace( '{' . $k . ';allFileUrls}', $allFileUrls, $value ); // returns a list of all file urls
                            $value = str_replace( '{' . $k . ';allFileLinks}', $allFileLinks, $value ); // returns a list of all files with link to file
                            // count (total files)
                            $value = str_replace( '{' . $k . ';count}', count($v['files']), $value );
                            $value = str_replace( '{' . $k . ';total_files}', count($v['files']), $value );
                            $value = str_replace( '{' . $k . ';total}', count($v['files']), $value );
                            $value = str_replace( '{' . $k . ';new_count}', count($v['files']), $value );
                            $value = str_replace( '{' . $k . ';existing_count}', count($v['files']), $value );
                            // URL
                            $value = str_replace( '{' . $k . ';url}', self::decode($fv['url']), $value );
                            $value = str_replace( '{' . $k . ';url['.$fk.']}', self::decode($fv['url']), $value );
                            // Extension
                            $ext = pathinfo($fv['value'], PATHINFO_EXTENSION);
                            $value = str_replace( '{' . $k . ';ext}', self::decode($ext), $value );
                            $value = str_replace( '{' . $k . ';ext['.$fk.']}', self::decode($ext), $value );
                            $value = str_replace( '{' . $k . ';extension}', self::decode($ext), $value );
                            $value = str_replace( '{' . $k . ';extension['.$fk.']}', self::decode($ext), $value );
                            // Type
                            $value = str_replace( '{' . $k . ';type}', self::decode($fv['type']), $value );
                            $value = str_replace( '{' . $k . ';type['.$fk.']}', self::decode($fv['type']), $value );
                            $value = str_replace( '{' . $k . ';mime}', self::decode($fv['type']), $value );
                            $value = str_replace( '{' . $k . ';mime['.$fk.']}', self::decode($fv['type']), $value );
                            // Name
                            $value = str_replace( '{' . $k . ';name}', self::decode($fv['value']), $value );
                            $value = str_replace( '{' . $k . ';name['.$fk.']}', self::decode($fv['value']), $value );
                            $value = str_replace( '{' . $k . ';basename}', self::decode($fv['value']), $value );
                            $value = str_replace( '{' . $k . ';basename['.$fk.']}', self::decode($fv['value']), $value );
                            // Attachment
                            if(isset($fv['attachment'])) $value = str_replace( '{' . $k . ';attachment_id}', self::decode($fv['attachment']), $value );
                            if(isset($fv['attachment'])) $value = str_replace( '{' . $k . ';attachment_id['.$fk.']}', self::decode($fv['attachment']), $value );
                            if(isset($fv['attachment'])) $value = str_replace( '{' . $k . ';attachment}', self::decode($fv['attachment']), $value );
                            if(isset($fv['attachment'])) $value = str_replace( '{' . $k . ';attachment['.$fk.']}', self::decode($fv['attachment']), $value );
                            // E-mail label
                            $value = str_replace( '{' . $k . ';label}', self::decode($v['label']), $value );
                        }
                        continue;
                    }
                    if( isset( $v['name'] ) ) {
                        if( (isset($v['type'])) && ($v['type']=='text') ) {
                            $v['value'] = self::decode_textarea( $v, $v['value'] );
                        }
                        if( isset( $v['timestamp'] ) ) {
                            $value = str_replace( '{' . $v['name'] . ';timestamp}', self::decode( $v['timestamp'] ), $value );
                        }
                        if( isset( $v['label'] ) ) {
                            $value = str_replace( '{field_label_' . $v['name'] . '}', self::decode( $v['label'] ), $value );
                        }
                        if( isset( $v['option_label'] ) ) {
                            if( !empty($v['replace_commas']) ) {
                                $v['option_label'] = str_replace( ',', $v['replace_commas'], $v['option_label'] );
                            }
                            $value = str_replace( '{' . $v['name'] . ';label}', self::decode( $v['option_label'] ), $value );
                        }
                        if( isset( $v['value'] ) ) {
                            if( !empty($v['replace_commas']) ) {
                                $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                            }
                            $value = str_replace( '{field_' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                        }
                    }
                }
            }

            // Now loop again through all the data (submitted by the user)
            if( $data!=null ) {
                foreach( $data as $k => $v ) {
                    if( isset( $v['name'] ) ) {
                        if( isset( $v['value'] ) ) {
                            if( (isset($v['type'])) && ($v['type']=='text') ) {
                                $v['value'] = nl2br( $v['value'] );
                            }
                            if( !empty($v['replace_commas']) ) {
                                $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                            }
                            $value = str_replace( '{' . $v['name'] . '}', self::decode( $v['value'] ), $value );
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

            // @since 4.4.0 - Loop through form settings
            // After replacing the settings {tag} with data, make sure to once more replace any possible {tags} 
            // (but only once, so we will skip this next time)
            if( is_array( $settings ) ) {
                foreach( $settings as $k => $v ) {
                    if(is_array($v)) continue;
                    $value = strval($value);
                    $value = str_replace( '{form_setting_' . $k . '}', self::decode( $v ), $value, $count );
                    // After replacing the settings {tag} with data, make sure to once more replace any possible {tags}
                    // Only execute if replacing took place
                    if ($count > 0) {
                        $value = self::email_tags( $value, $data, $settings, $user, $skip );
                    }
                }
            }
            
            // @since 4.0.1 - Let's try to replace author meta data
            if( $current_author!=null ) {
                // We possibly are looking for custom author meta data
                if ( strpos( $value, '{author_meta') !== false ) {
                    $meta_key = str_replace('{author_meta_', '', $value);
                    $meta_key = str_replace('}', '', $meta_key);
                    $value = get_user_meta( $current_author->ID, $meta_key, true ); 
                    if( $value=='' ) {
                        // Whenever no meta was found mostly we try to retrieve default values like user_login etc. (which is not meta data)
                        // first convert object to array then try retrieve the value by key
                        $value = $current_author->{$meta_key};
                    }
                    return $value;
                }
            }

            // @since 4.0.0 - Let's try to replace user meta data
            if( $current_user!=null ) {
                // We possibly are looking for custom user meta data
                if ( strpos( $value, '{user_meta') !== false ) {
                    $meta_key = str_replace('{user_meta_', '', $value);
                    $meta_key = str_replace('}', '', $meta_key);
                    $value = get_user_meta( $current_user->ID, $meta_key, true ); 
                    return $value;
                }
            }

            // @since 6.3.0 - Let's try to replace custom option data
            if( $skipOptions===false && (strpos($value, '{option_')!==false) ) {
                $option_key = str_replace('{option_', '', $value);
                $option_key = str_replace('}', '', $option_key);
                $keys = explode(';', $option_key);
                $value = get_option( $keys[0] );
                if(is_array($value)){
                    if(isset($keys[1])){
                        $key = $keys[1];
                        if(isset($value[$key])) return $value[$key];
                    }
                    $json = json_encode($value);
                    return $json;
                }
                return $value;
            }

            // @since 4.0.0 - Let's try to replace custom post meta data
            if( isset( $post ) ) {
                // We possibly are looking for custom user meta data
                if ( strpos( $value, '{post_meta') !== false ) {
                    $meta_key = str_replace('{post_meta_', '', $value);
                    $meta_key = str_replace('}', '', $meta_key);
                    $value = get_post_meta( $post->ID, $meta_key, true ); 
                    return $value;
                }
                // We possibly are looking for post terms (taxonomy)
                //$term_list = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'all' ) );
                if ( strpos( $value, '{post_term_slugs') !== false ) {
                    $taxonomy = str_replace('{post_term_slugs_', '', $value);
                    $taxonomy = str_replace('}', '', $taxonomy);
                    $return = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
                    if( is_wp_error( $return ) ) { return ''; } return implode(", ", $return);
                }
                if ( strpos( $value, '{post_term_names') !== false ) {
                    $taxonomy = str_replace('{post_term_names_', '', $value);
                    $taxonomy = str_replace('}', '', $taxonomy);
                    $return = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
                    if( is_wp_error( $return ) ) { return ''; } return implode(", ", $return);
                }
                if ( strpos( $value, '{post_term_ids') !== false ) {
                    $taxonomy = str_replace('{post_term_ids_', '', $value);
                    $taxonomy = str_replace('}', '', $taxonomy);
                    $return = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
                    if( is_wp_error( $return ) ) { return ''; } return implode(", ", $return);
                }
            }
            
            // Let's try to retrieve product attributes
            if ( class_exists( 'WooCommerce' ) ) {
                if( isset( $post ) ) {
                    if ( strpos( $value, '{product_attributes_') !== false ) {
                        global $product;
                        $meta_key = str_replace('{product_attributes_', '', $value);
                        $meta_key = str_replace('}', '', $meta_key);
                        $value = $product->get_attribute( $meta_key );
                        return $value;
                    }
                }
            }
            
            // @since 4.9.6 - local/global secrets
            if($skipSecrets===false){
                if(isset($data) && isset($data['hidden_form_id'])){
                    $form_id = absint($data['hidden_form_id']['value']);
                    if($form_id!=0){
                        $localSecrets = get_post_meta( $form_id, '_super_local_secrets', true );
                        if( is_array( $localSecrets ) ) {
                            foreach( $localSecrets as $v){
                                $value = str_replace( '{@' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                            }
                        }
                    }
                }
                $globalSecrets = get_option( 'super_global_secrets' );
                if( is_array( $globalSecrets ) ) {
                    foreach( $globalSecrets as $k => $v ) {
                        $value = str_replace( '{@' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                    }
                }
            }

            // Now return the final output
            return $value;

        }
        return $tags;
    }


    /**
     * Retrieve HTML for email loop
     * 
    */
    public static function retrieve_email_loop_html($atts){
        $data = $atts['data'];
        $settings = $atts['settings'];
        $exclude = $atts['exclude']; // Fields to exclude (currently used by Listings Add-on)
        $listing_loop = '';
        $email_loop = '';
        $confirm_loop = '';
        $attachments = array();
        $confirm_attachments = array();
        $string_attachments = array();
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            foreach( $data as $k => $v ) {
                // Skip dynamic data
                if($k=='_super_dynamic_data') continue;
                
                $row = $settings['email_loop'];
                $confirm_row = $row;
                $listing_row = '';
                if( isset($settings['confirm_email_loop']) ) {
                    $confirm_row = $settings['confirm_email_loop'];
                }
                // Used by Listings Add-on only
                if( isset($atts['listing_loop']) ) {
                    $listing_row = $atts['listing_loop'];
                }

                // Exclude from emails
                // 0 = Do not exclude from e-mails
                // 1 = Exclude from confirmation email
                // 2 = Exclude from all email
                // 3 = Exclude from admin email
                if( !isset( $v['exclude'] ) ) {
                    $v['exclude'] = 0;
                }
                if( $v['exclude']==2 ) {
                    // Exclude from all emails
                    continue;
                }

                /** 
                 *  Filter to control the email loop when something special needs to happen
                 *  e.g. Signature element needs to display image instead of the base64 code that the value contains
                 *
                 *  @param  string  $row
                 *  @param  array   $data
                 *
                 *  @since      1.0.9
                */
                $result = apply_filters( 'super_before_email_loop_data_filter', $row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                $confirm_result = apply_filters( 'super_before_email_loop_data_filter', $confirm_row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                $listing_result = apply_filters( 'super_before_listing_loop_data_filter', $listing_row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                $continue = false;
                if( isset( $result['status'] ) ) {
                    if( $result['status']=='continue' ) {
                        if( isset( $result['string_attachments'] ) ) {
                            $string_attachments = $result['string_attachments'];
                        }
                        $email_loop .= $result['row'];
                        $continue = true;
                    }
                }
                if( isset( $confirm_result['status'] ) ) {
                    if( $confirm_result['status']=='continue' ) {
                        if( isset( $confirm_result['string_attachments'] ) ) {
                            $string_attachments = $confirm_result['string_attachments'];
                        }
                        if( ( isset( $confirm_result['exclude'] ) ) && ( $confirm_result['exclude']==1 ) ) {
                        }else{
                            $confirm_loop .= $confirm_result['row'];
                        }
                        $continue = true;
                    }
                }
                if( isset( $listing_result['status'] ) ) {
                    if( $listing_result['status']=='continue' ) {
                        if( isset( $listing_result['string_attachments'] ) ) {
                            $string_attachments = $listing_result['string_attachments'];
                        }
                        if( ( isset( $listing_result['exclude'] ) ) && ( $listing_result['exclude']==1 ) ) {
                        }else{
                            $listing_loop .= $listing_result['row'];
                        }
                        $continue = true;
                    }
                }
                if($continue) continue;

                if( isset($v['type']) && $v['type']=='files' ) {
                    $files_value = '';
                    $files_value_listing = '';
                    if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
                        $v['value'] = '';
                        if( !empty( $v['label'] ) ) {
                            // Replace %d with empty string if exists
                            $v['label'] = str_replace('%d', '', $v['label']);
                            $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                            $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
                            $listing_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $listing_row );
                        }else{
                            $row = str_replace( '{loop_label}', '', $row );
                            $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
                            $listing_row = str_replace( '{loop_label}', '', $listing_row );
                        }
                        $files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
                    }else{
                        $v['value'] = '-';
                        foreach( $v['files'] as $key => $value ) {
                            // Check if user explicitely wants to remove files from {loop_fields} in emails
                            if(!empty($settings['file_upload_remove_from_email_loop'])) {
                                // Remove this row completely
                                $row = ''; 
                                $confirm_row = '';
                                $listing_row = '';
                            }else{
                                if( $key==0 ) {
                                    if( !empty( $v['label'] ) ) {
                                        $v['label'] = str_replace('%d', '', $v['label']);
                                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                        $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
                                        $listing_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $listing_row );
                                    }else{
                                        $row = str_replace( '{loop_label}', '', $row );
                                        $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
                                        $listing_row = str_replace( '{loop_label}', '', $listing_row );
                                    }
                                }
                                // In case the file was deleted we do not want to add a hyperlink that links to the file
                                // In case the user explicitely choose to remove the hyperlink
                                if( !empty($settings['file_upload_submission_delete']) || 
                                    !empty($settings['file_upload_remove_hyperlink_in_emails']) ) {
                                    $files_value .= $value['value'] . '<br />';
                                }else{
                                    $files_value .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html($value['value']) . '</a><br /><br />';
                                }
                                if( !empty($settings['file_upload_submission_delete']) ) {
                                    $files_value_listing .= $value['value'] . '<br />';
                                }else{
                                    $files_value_listing .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html($value['value']) . '</a><br />';
                                }
                            }
                            // Check if we should exclude the file from emails
                            // 0 = Do not exclude from e-mails
                            // 1 = Exclude from confirmation email
                            // 2 = Exclude from all email
                            // 3 = Exclude from admin email
                            if( $v['exclude']!=2 ) {
                                // Get either URL or Secure file path
                                $fileValue = '';
                                if(!empty($value['attachment'])){
                                    $fileValue = $value['url'];
                                }else{
                                    // See if this was a secure file upload
                                    if(!empty($value['path'])) $fileValue = wp_normalize_path(trailingslashit($value['path']) . $value['value']);
                                    if(!empty($value['subdir'])) $fileValue = $value['subdir'];
                                }
                                // 1 = Exclude from confirmation email
                                if( $v['exclude']==1 ) {
                                    $attachments[$value['value']] = $fileValue;
                                }else{
                                    // 3 = Exclude from admin email
                                    if( $v['exclude']==3 ) {
                                        $confirm_attachments[$value['value']] = $fileValue;
                                    }else{
                                        // Do not exclude
                                        $attachments[$value['value']] = $fileValue;
                                        $confirm_attachments[$value['value']] = $fileValue;
                                    }
                                }
                            }
                        }
                    }
                    $row = str_replace( '{loop_value}', $files_value, $row );
                    $confirm_row = str_replace( '{loop_value}', $files_value, $confirm_row );
                    $listing_row = str_replace( '{loop_value}', $files_value_listing, $listing_row );
                }else{
                    if( isset($v['type']) && (($v['type']=='form_id') || ($v['type']=='entry_id')) ) {
                        $row = '';
                        $confirm_row = '';
                        $listing_row = '';
                    }else{
                        if( !empty( $v['label'] ) ) {
                            $v['label'] = str_replace('%d', '', $v['label']);
                            $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                            $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
                            $listing_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $listing_row );
                        }else{
                            $row = str_replace( '{loop_label}', '', $row );
                            $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
                            $listing_row = str_replace( '{loop_label}', '', $listing_row );
                        }
                        // @since 1.2.7
                        if( isset( $v['admin_value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $row );
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $confirm_row );
                        }
                        if( isset( $v['confirm_value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['confirm_value'] = str_replace( ',', $v['replace_commas'], $v['confirm_value'] );
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['confirm_value'] ), $confirm_row );
                        }
                        if( isset( $v['value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $row );
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $confirm_row );
                            $listing_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $listing_row );
                        }

                    }
                }

                // @since 4.5.0 - check if value is empty, and if we need to exclude it from the email
                // 0 = Do not exclude from e-mails
                // 1 = Exclude from confirmation email
                // 2 = Exclude from all email
                // 3 = Exclude from admin email
                if( $v['exclude']==3 || ($settings['email_exclude_empty']=='true' && empty($v['value']) )) {
                    // Exclude from admin email loop
                }else{
                    $email_loop .= $row;
                }
                if( $v['exclude']==1 || ($settings['confirm_exclude_empty']=='true' && empty($v['value']) )) {
                    // Exclude from confirmation email loop
                }else{
                    $confirm_loop .= $confirm_row;
                }
                $listing_loop .= $listing_row;
            }
        }
        return array(
            'listing_loop' => $listing_loop,
            'email_loop' => $email_loop,
            'confirm_loop' => $confirm_loop,
            'attachments' => $attachments,
            'confirm_attachments' => $confirm_attachments,
            'string_attachments' => $string_attachments
        );
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
            if( file_exists( $file ) ) {
                unlink( $file );
            }
        }
    }
    public static function get_transient($x) { $html = ''; if($x['slug']!=='before_do_shortcode' && $x['slug']!=='before_do_shortcode_admin') $html = '<script>alert("Connection error! Please refresh the page to try again, or contact support.");</script>'; $response = wp_remote_post( SUPER_API_ENDPOINT . '/settings/transient', array( 'method' => 'POST', 'timeout' => 45, 'data_format' => 'body', 'headers' => array('Content-Type' => 'application/json; charset=utf-8'), 'body' => json_encode( array( 'slug' => $x['slug'], 'home_url' => get_home_url(), 'admin_url' => admin_url(), 'version' => SUPER_VERSION)))); if ( is_wp_error( $response ) ) { $html .= $response->get_error_message(); }else{ $body = $response['body']; $response = $response['response']; if($response['code']==200 && strpos($body, '{') === 0){ $object = json_decode($body); if($object->status==200){ $html = $object->body; } } } return $html; }

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
    public static function email( $to, $from, $from_name, $custom_reply=false, $reply='', $reply_name='', $cc='', $bcc='', $subject='', $body='', $settings=array(), $attachments=array(), $string_attachments=array() ) {

        $from = trim($from);
        $from_name = trim(preg_replace('/[\r\n]+/', '', $from_name)); //Strip breaks and trim
        $to = explode( ",", $to );

        // Get attachment paths
        $attachmentPaths = array();
        foreach( $attachments as $urlOrPath ) {
            // Normalize the path so we do not have double forward slashes
            $filePath = wp_normalize_path($urlOrPath);
            if(strpos($filePath, '//')!==false){
                // This is uploaded to the wp content directory
                $filePath = str_replace('https://', 'http://', $filePath );
                $path = str_replace(str_replace('https://', 'http://', content_url()), '', $filePath);
                $filePath = WP_CONTENT_DIR . $path;
            }else{
                // This is uploaded to a custom dir outside the wp content directory
                // Try to grab the real path
                $filePath = ABSPATH . str_replace('__/', '../', $filePath);
                $filePath = realpath($filePath);
            }
            $attachmentPaths[] = $filePath;
        }

        $global_settings = SUPER_Common::get_global_settings();
        if( !isset( $global_settings['smtp_enabled'] ) ) {
            $global_settings['smtp_enabled'] = 'disabled';
        }
        if( $global_settings['smtp_enabled']=='disabled' ) {
            SUPER_Common::setClientData( array( 'name'=> 'string_attachments', 'value'=>$string_attachments  ) );

            $headers = array();
            if(!empty($settings['header_additional'])){
                $headers = array_filter( explode( "\n", $settings['header_additional'] ) );
            } 
            $headers[] = "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"";
            
            // Set From: header
            if( empty( $from_name ) ) {
                $from_header = $from;
            }else{
                $from_header = $from_name . ' <' . $from . '>';
            }
            $headers[] = 'From: ' . $from_header;
            
            // Set Reply-To: header
            if( $custom_reply!=false ) {
                if( empty( $reply_name ) ) {
                    $reply_header = $reply;
                }else{
                    $reply_header = $reply_name . ' <' . $reply . '>';
                }
                $headers[] = 'Reply-To: ' . $reply_header;
            }else{
                $headers[] = 'Reply-To: ' . $from_header;
            }
            
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
            $result = wp_mail( $to, $subject, $body, $headers, $attachmentPaths );
            $error = '';
            if($result==false){
                $error = 'Email could not be send through wp_mail()';
            }
            // Return
            return array( 'result'=>$result, 'error'=>$error, 'mail'=>null );
        }else{
            // @since 4.9.551 - WordPress changed the location of PHPMailer apperantly...
            global $wp_version;
            if ( version_compare( $wp_version, '5.5', '<' ) ) {
                require_once(ABSPATH . WPINC . "/class-phpmailer.php");
                require_once(ABSPATH . WPINC . "/class-smtp.php");
                require_once(ABSPATH . WPINC . "/class-pop3.php");
                $phpmailer = new PHPMailer();
            }else{
                require_once(ABSPATH . WPINC . "/PHPMailer/PHPMailer.php");
                require_once(ABSPATH . WPINC . "/PHPMailer/SMTP.php");
                require_once(ABSPATH . WPINC . "/PHPMailer/Exception.php");
                require_once(ABSPATH . WPINC . "/class-pop3.php");
                $phpmailer = new \PHPMailer\PHPMailer\PHPMailer();
            }

            // Set mailer to use SMTP
            $phpmailer->isSMTP();

            // Specify main and backup SMTP servers
            $phpmailer->Host = $global_settings['smtp_host'];
            
            // Enable SMTP authentication
            if( $global_settings['smtp_auth']=='enabled' ) {
                $phpmailer->SMTPAuth = true;
            }

            // SMTP username
            $phpmailer->Username = $global_settings['smtp_username'];

            // SMTP password
            $phpmailer->Password = $global_settings['smtp_password'];  

            // Enable TLS encryption
            if( $global_settings['smtp_secure']!='' ) {
                $phpmailer->SMTPSecure = $global_settings['smtp_secure']; 
            }

            // Disable SMTPAutoTLS to avoid issues on servers with invalid certificates
            $phpmailer->SMTPAutoTLS = false;

            // TCP port to connect to
            $phpmailer->Port = $global_settings['smtp_port'];

            // Set Timeout
            $phpmailer->Timeout = $global_settings['smtp_timeout'];

            // Set keep alive
            if( $global_settings['smtp_keep_alive']=='enabled' ) {
                $phpmailer->SMTPKeepAlive = true;
            }

            // Set debug
            if( $global_settings['smtp_debug'] != 0 ) {
                $phpmailer->SMTPDebug = $global_settings['smtp_debug'];
                $phpmailer->Debugoutput = $global_settings['smtp_debug_output_mode'];

            }
        
            // Set From: header
            $phpmailer->setFrom($from, $from_name);

            // Add a recipient
            foreach( $to as $value ) {
                $phpmailer->addAddress($value); // Name 'Joe User' is optional
            }

            // Set Reply-To: header
            if( $custom_reply!=false ) {
                $phpmailer->addReplyTo($reply, $reply_name);
            }else{
                $phpmailer->addReplyTo($from, $from_name);
            }

            // Add CC
            if( !empty( $cc ) ) {
                $cc = explode( ",", $cc );
                foreach( $cc as $value ) {
                    $phpmailer->addCC($value);
                }
            }

            // Add BCC
            if( !empty( $bcc ) ) {
                $bcc = explode( ",", $bcc );
                foreach( $bcc as $value ) {
                    $phpmailer->addBCC($value);
                }
            }

            // Custom headers
            if( !empty( $settings['header_additional'] ) ) {
                $headers = explode( "\n", $settings['header_additional'] );
                foreach( $headers as $k => $v ) {
                    $phpmailer->addCustomHeader($v);
                }
            }

            // Add attachment(s)
            foreach( $attachmentPaths as $path ) {
                $phpmailer->addAttachment( $path );
            }

            // Add string attachment(s)
            foreach( $string_attachments as $v ) {
                if( $v['encoding']=='base64' && $v['type']=='image/png' ) {
                    $v['data'] = substr( $v['data'], strpos( $v['data'], "," ) );
                    $v['data'] = base64_decode( $v['data'] );
                }
                $phpmailer->AddStringAttachment( $v['data'], $v['filename'], $v['encoding'], $v['type'] );
            }

            // Set email format to HTML
            if( !isset( $settings['header_content_type'] ) ) $settings['header_content_type'] = 'html';
            if( $settings['header_content_type'] == 'html' ) {
                $phpmailer->isHTML(true);
            }else{
                $phpmailer->isHTML(false);
            }

            // CharSet
            if( !isset( $settings['header_charset'] ) ) $settings['header_charset'] = 'UTF-8';
            $phpmailer->CharSet = $settings['header_charset'];

            // Content-Type
            //$phpmailer->ContentType = 'multipart/mixed';

            // Content-Transfer-Encoding
            // Options: "8bit", "7bit", "binary", "base64", and "quoted-printable".
            //$phpmailer->Encoding = 'base64';

            // Subject
            $phpmailer->Subject = $subject;

            // Body
            $phpmailer->Body = $body;

            // Send the email
            $result = $phpmailer->send();

            // Explicit call to smtpClose() when keep alive is enabled
            if( $phpmailer->SMTPKeepAlive==true ) {
                $phpmailer->SmtpClose();
            }
            
            // Return
            return array( 'result'=>$result, 'error'=>$phpmailer->ErrorInfo, 'mail'=>$phpmailer );

        }
    }
}
endif;
