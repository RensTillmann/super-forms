<?php
/**
 * Class for handling Ajax requests
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Ajax
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * SUPER_Ajax Class
 */
class SUPER_Ajax {
    
    /** 
	 *	Define ajax callback functions
	 *
	 *	@since		1.0.0
	 */
	public static function init() {

		$ajax_events = array(
            
            // Ajax action                  => nopriv
            //'example'                     => true,
            
            'verify_recaptcha'              => true,

            'mark_unread'                   => false,
            'mark_read'                     => false,
            'delete_contact_entry'          => false,

            
            'save_settings'                 => false,
            
            'get_element_builder_html'      => false,
            
            'load_element_settings'         => false,

            'save_form'                     => false,

            'load_form'                     => false,
            
            'delete_form'                   => false,

            'load_preview'                  => false,

            'send_email'                    => true,

            'load_default_settings'         => false,


		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}


    /** 
     *  Verify the Google reCAPTCHA
     *
     *  @since      1.0.0
    */
    public static function verify_recaptcha() {
        $settings = get_option( 'super_settings' );
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => $settings['form_recaptcha_secret'], 
            'response' => $_REQUEST['response']
        );
        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query( $data ),
            ),
        );
        $context  = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        $result = json_decode( $result, true );
        if( $result['success'] ) {
            echo 1; //Success!
        }else{
            echo 0; //Error!
        }
        die();
    }    
    

    /** 
     *  Save the default settings
     *
     *  @since      1.0.0
    */
    public static function mark_unread() {
        $my_post = array(
            'ID' => $_POST['contact_entry'],
            'post_status' => 'super_unread',
        );
        wp_update_post( $my_post );
        die();
    }
    public static function mark_read() {
        $my_post = array(
            'ID' => $_POST['contact_entry'],
            'post_status' => 'super_read',
        );
        wp_update_post( $my_post );
        die();
    }
    public static function delete_contact_entry() {
        wp_trash_post( $_POST['contact_entry'] );
        die();
    }

    
    /** 
     *  Save the default settings
     *
     *  @since      1.0.0
    */
    public static function save_settings() {
        
        $array = array();
        foreach( $_REQUEST['data'] as $k => $v ) {
            $array[$v['name']] = $v['value'];
        }
        update_option( 'super_settings', $array );
        
        die();
        
    }

    /** 
     *  Load the default settings (Settings page)
     *
     *  @since      1.0.0
    */
    public static function load_default_settings() {
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
        update_option('super_settings', $array);
        die();
    }


    /** 
     *  Loads the form preview on backedn (create form page)
     *
     *  @since      1.0.0
    */
    public static function load_preview() {
        $id = absint( $_REQUEST['id'] );
        echo SUPER_Shortcodes::super_form_func( array( 'id'=>$id ) );
        //echo do_shortcode('[super_form id="' . $id . '"]');
        die();
    }


    /** 
     *  Loads an existing form from the Examples dropdown
     *
     *  @since      1.0.0
    */
    public static function load_form(){
        if($_REQUEST['id']==0){
            $shortcode = '[{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"first_name","email":"First name:","label":"","description":"","placeholder":"Your First Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"name","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"last_name","email":"Last name:","label":"","description":"","placeholder":"Your Last Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"name","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"email","email":"Email address:","label":"","description":"","placeholder":"Your Email Address","tooltip":"","validation":"email","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"envelope","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"first_name","logic":"contains","value":""}]}},{"tag":"textarea","group":"form_elements","inner":"","data":{"name":"question","email":"Question","placeholder":"Ask us any questions...","validation":"none","icon_position":"outside","icon_align":"left","icon":"question","conditional_action":"disabled","conditional_trigger":"all"}}],"data":{"size":"1/1","margin":"","conditional_action":"disabled"}}]';
        }else{
            $shortcode = get_post_meta( absint( $_REQUEST['id'] ), '_super_elements', true );
        }
        echo $shortcode;
        die();
    }


    /** 
     *  Saves the form with all it's settings
     *
     *  @since      1.0.0
    */
    public static function save_form() {
        
        $id = absint( $_POST['id'] );
        $settings = array();
        foreach( $_REQUEST['settings'] as $k => $v ) {
            $settings[$v['name']] = $v['value'];
        }
        if( empty( $id ) ) {
            $form = array(
                'post_title' => $_POST['title'],
                'post_status' => 'publish',
                'post_type'  => 'super_form'
            );
            $id = wp_insert_post( $form ); 
            add_post_meta( $id, '_super_elements', $_POST['shortcode'] );
            add_post_meta( $id, '_super_form_settings', $settings );
        }else{
            $form = array(
                'ID' => $id,
                'post_title'  => $_POST['title']
            );
            wp_update_post( $form );
            update_post_meta( $id, '_super_elements', $_POST['shortcode'] );
            update_post_meta( $id, '_super_form_settings', $settings );
        }
        echo $id;
        die();

    }


    /** 
     *  Deletes the form with all it's settings
     *
     *  @since      1.0.0
    */
    public static function delete_form() {
        wp_delete_post( absint( $_POST['id'] ), true );
        die();
    }


    /** 
	 *	Function to load all element settings while editing the element (create form page / settings tabs)
	 *
     *  @param  string  $tag
     *  @param  array   $data
	 *
     *	@since		1.0.0
	*/
    public static function load_element_settings( $tag=null, $group=null, $data=null ) {
        
        if($tag==null){
            $tag = $_POST['tag'];
        }
        if($group==null){
            $group = $_POST['group'];
        }
        if($data==null){
            $data = $_POST['data'];
        } 
        $array = SUPER_Shortcodes::shortcodes();
        $tabs = $array[$group]['shortcodes'][$tag]['atts'];
        
        $result = '';    
        $result .= '<div class="super-element-settings-tabs">';
            $result .= '<select>';
                $i = 0;
                foreach( $tabs as $k => $v ){
                    $result .= '<option ' . ( $i==0 ? 'selected="selected"' : '' ) . ' value="' . $i . '">' . $v['name'] . '</option>';
                    $i++;
                }
            $result .= '</select>';
        $result .= '</div>';
        
        $i = 0;
        foreach( $tabs as $k => $v ){                
            $result .= '<div class="tab-content' . ( $i==0 ? ' active' : '' ) . '">';
                if( isset( $v['fields'] ) ) {
                    foreach( $v['fields'] as $fk => $fv ) {
                        $filter = '';
                        $parent = '';
                        $filtervalue = '';
                        if( ( isset( $fv['filter'] ) ) && ( $fv['filter']==true ) ) {
                            $filter = ' filter';
                            if( isset( $fv['parent'] ) ) $parent = ' data-parent="' . $fv['parent'] . '"';
                            if( isset( $fv['filter_value'] ) ) $filtervalue = ' data-filtervalue="' . $fv['filter_value'] . '"';
                        }
                        $hidden = '';
                        if( isset( $fv['hidden'] ) && ( $fv['hidden']==true ) ) {
                            $hidden = ' hidden';
                        }
                        $result .= '<div class="field' . $filter . $hidden . '"' . $parent . '' . $filtervalue . '>';
                            if( isset( $fv['name'] ) ) $result .= '<div class="field-name">' . $fv['name'] . '</div>';
                            if( isset( $fv['desc'] ) ) $result .= '<i class="info popup" title="" data-placement="bottom" data-original-title="' . $fv['desc'] . '"></i>';
                            if( isset( $fv['label'] ) ) $result .= '<div class="field-label">' . $fv['label'] . '</div>';
                            $result .= '<div class="field-input">';
                                if( !isset( $fv['type'] ) ) $fv['type'] = 'text';
                                if( method_exists( 'SUPER_Field_Types', $fv['type'] ) ) {
                                    if( isset( $data[$fk] ) ) $fv['default'] = $data[$fk];
                                    $result .= call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv, $data );
                                }
                            $result .= '</div>';
                        $result .= '</div>';
                    }
                }
            $result .= '</div>';
            $i = 1;
        }
        $result .= '<span class="super-button update-element">' . __( 'Update Element', 'super' ) . '</span>';
        $result .= '<span class="super-button cancel-update">' . __( 'Close', 'super' ) . '</span>';
        echo $result;        
        die();
        
    }
    
    
    /** 
	 *	Retrieve the HTML for the element that is being dropped inside a dropable element
	 *
     *  @param  string  $tag
     *  @param  array   $inner
     *  @param  array   $data
     *  @param  integer $method
	 *
     *	@since		1.0.0
	*/
    public static function get_element_builder_html( $tag=null, $group=null, $inner=null, $data=null, $method=1 ) {

        include_once(SUPER_PLUGIN_DIR.'/includes/class-shortcodes.php' );
        $shortcodes = SUPER_Shortcodes::shortcodes();

        $predefined = '';
        if( isset( $_REQUEST['predefined'] ) ) {
            $predefined = $_REQUEST['predefined'];
        }
        if( $predefined!='' ) {
            $result = '';
            foreach( $predefined as $k => $v ) {
                // Output builder HTML (element and with action buttons)
                $result .= SUPER_Shortcodes::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes );
            }
        }else{

            if($tag==null){
                $tag = $_REQUEST['tag'];
            }
            if($group==null){
                $group = $_REQUEST['group'];
            }
            $builder = 1;
            if(isset($_REQUEST['builder'])){
                $builder = $_REQUEST['builder'];
            }
            if(empty($inner)) {
                $inner = array();
                if(isset($_REQUEST['inner'])){
                    $inner = $_REQUEST['inner'];
                }
            }
            if(empty($data)) {
                $data = array();
                if(isset($_REQUEST['data'])){
                    $data = $_REQUEST['data'];
                }
            }

            if($builder==0){
                // Output element HTML only
                $result = SUPER_Shortcodes::output_element_html( $tag, $group, $data, $inner, $shortcodes );
            }else{
                // Output builder HTML (element and with action buttons)
                $result = SUPER_Shortcodes::output_builder_html( $tag, $group, $data, $inner, $shortcodes );
            }
        }
           
        // Return method
        if($method==1){
            echo $result;
        }else{
            return $result;
        }

        die();        
    }


    /** 
     *  Send an email with the submitted form data
     *
     *  @param  array  $settings
     *
     *  @since      1.0.0
    */
    public static function send_email( $settings=null ) {

        $data = array();
        if( isset( $_REQUEST['data'] ) ) {
            $data = $_REQUEST['data'];
        }

        $form_id = 0;
        if( $settings==null ) {
            $form_id = absint( $_POST['form_id'] );
            $settings = get_post_meta( $form_id, '_super_form_settings', true );
        }
        $duration = $settings['form_duration'];
        
        do_action( 'super_before_sending_email_hook', array( 'post'=>$_POST, 'settings'=>$settings ) );
        
        if( !empty( $settings['header_additional'] ) ) {
            $header_additional = '';
            if( !empty( $settings['header_additional'] ) ) {
                $headers = explode( "\n", $settings['header_additional'] );   
                foreach( $headers as $k => $v ) {
                    $header_additional .= $v . "\r\n";
                }
            }
            $settings['header_additional'] = $header_additional;
        }

        if( $settings['save_contact_entry']=='yes' ) {
            $post = array(
                'post_status' => 'super_unread',
                'post_type'  => 'super_contact_entry' ,
            ); 
            $contact_entry_id = wp_insert_post($post); 
            add_post_meta( $contact_entry_id, '_super_contact_entry_data', $data);
            add_post_meta( $contact_entry_id, '_super_contact_entry_ip', SUPER_Common::real_ip() );
            $contact_entry = array(
                'ID' => $contact_entry_id,
                'post_title'  => __( 'Contact entry', 'super' ) . ' ' . $contact_entry_id,
            );
            wp_update_post( $contact_entry );
        }

        $settings = apply_filters( 'super_before_sending_email_settings_filter', $settings );
        
        $email_loop = '';
        $confirm_loop = '';
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            foreach( $data as $k => $v ) {
                $row = $settings['email_loop'];
                if( $v['type']=='files' ) {
                    $files_value = '';
                    if( !isset( $v['files'] ) ) {
                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                        $files_value .= __( 'User did not upload any files (upload field is not set as required)', 'super' );
                    }else{
                        if( count( $v['files'] )==0 ) {
                            $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                            $files_value .= __( 'User did not upload any files (upload field is not set as required)', 'super' );
                        }else{
                            foreach( $v['files'] as $key => $value ) {
                                if( $key==0 ) {
                                    $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                }
                                $files_value .= '<a href="' . $value['url'] . '" target="_blank">' . $value['value'] . '</a><br /><br />';
                            }
                        }
                    }
                    $row = str_replace( '{loop_value}', $files_value, $row );
                }else{
                    if( $v['type']=='form_id' ) {
                        $row = '';
                    }else{
                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                        $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['value'] ), $row );
                    }
                }
                $email_loop .= $row;
                if( ( isset( $v['exclude'] ) ) && ( $v['exclude']!=1 ) ) {
                    $confirm_loop .= $row;
                }
            }
        }
        if(!empty($settings['email_body_open'])) $settings['email_body_open'] = $settings['email_body_open'] . '<br /><br />';
        if(!empty($settings['email_body'])) $settings['email_body'] = $settings['email_body'] . '<br /><br />';
        $email_body = $settings['email_body_open'] . $settings['email_body'] . $settings['email_body_close'];
        $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
        $email_body = str_replace( '{real_ip}', SUPER_Common::real_ip(), $email_body );
        $email_body = SUPER_Common::replace_tag( $email_body, $data);
        $email_body = apply_filters( 'super_before_sending_email_body_filter', $email_body, array( 'settings'=>$settings, 'email_loop'=>$email_loop, 'data'=>$data ) );
        $content_type = $settings['header_content_type'];
        if( $settings['send']=='yes' ) {
            if( $settings['header_from_type']=='default' ) {
                $settings['header_from'] = get_option('blogname' ) . ' <' . get_option( 'admin_email' ) . '>';
            }
            $to = SUPER_Common::decode_email_header( SUPER_Common::replace_tag( $settings['header_to'], $data) );
            $from = SUPER_Common::decode_email_header( SUPER_Common::replace_tag( $settings['header_from'], $data) );
            $cc = SUPER_Common::decode_email_header( SUPER_Common::replace_tag( $settings['header_cc'], $data) );
            $bcc = SUPER_Common::decode_email_header( SUPER_Common::replace_tag( $settings['header_bcc'], $data) );
            $subject = SUPER_Common::decode( SUPER_Common::replace_tag( $settings['header_subject'], $data) );
            $to = explode( ",", $to );  
            foreach( $to as $value ) {
                if( !empty( $settings['smtp_host'] ) ) {
                    SUPER_Common::authSendEmail( $from, $cc, $bcc, $value, $subject, $email_body, $settings );
                }else{
                    $headers  = "Content-Type: text/$content_type; charset=UTF-8\r\n"; //ISO-8859-1 or ISO-8859-14 or ISO-8859-15 or UTF-8
                    $headers .= "MIME-Version: 1.0\r\n";
                    $headers .= "Reply-To: $from\r\n";
                    $headers .= "From: $from\r\n";
                    if( !empty( $cc ) ) $headers .= "Cc: $cc\r\n";
                    if( !empty( $bcc ) ) $headers .= "Bcc: $bcc\r\n";                    
                    $headers .= "X-Mailer: PHP/" . phpversion();
                    $headers .= $settings['header_additional'];
                    wp_mail( $value, $subject, $email_body, $headers );
                }
            }
        }
        if( $settings['confirm']=='yes' ) {
            $settings['header_additional'] = '';
            if(!empty($settings['confirm_body_open'])) $settings['confirm_body_open'] = $settings['confirm_body_open'] . '<br /><br />';
            if(!empty($settings['confirm_body'])) $settings['confirm_body'] = $settings['confirm_body'] . '<br /><br />';
            $email_body = $settings['confirm_body_open'] . $settings['confirm_body'] . $settings['confirm_body_close'];
            $email_body = str_replace( '{loop_fields}', $confirm_loop, $email_body );
            $email_body = str_replace( '{real_ip}', SUPER_Common::real_ip(), $email_body );
            $email_body = SUPER_Common::replace_tag( $email_body, $data);
            $email_body = apply_filters( 'super_before_sending_confirm_body_filter', $email_body, array( 'settings'=>$settings, 'confirm_loop'=>$confirm_loop, 'data'=>$data ) );
            $to = SUPER_Common::decode_email_header( SUPER_Common::replace_tag( $settings['confirm_to'], $data) );
            $from = SUPER_Common::decode_email_header( SUPER_Common::replace_tag( $settings['confirm_from'], $data) );
            $subject = SUPER_Common::decode( SUPER_Common::replace_tag( $settings['confirm_subject'], $data) );
            $to = explode( ",", $to );  
            foreach( $to as $value ) {
                if( !empty( $settings['smtp_host'] ) ) {
                    SUPER_Common::authSendEmail( $from, $value, $subject, $email_body, $settings );
                }else{
                    $content_type = $settings['header_content_type'];
                    $headers  = "Content-Type: text/$content_type; charset=UTF-8\r\n";
                    $headers .= "MIME-Version: 1.0\r\n";
                    $headers .= "Reply-To: $from\r\n";
                    $headers .= "From: $from\r\n";
                    $headers .= "X-Mailer: PHP/".phpversion();
                    wp_mail( $value, $subject, $email_body, $headers );
                }
            }
        }
        if( $form_id!=0 ) {
            ?>
            <script>
                <?php
                if( !empty( $settings['form_redirect_option'] ) ) {
                    if( $settings['form_redirect_option']=='page' ) {
                        ?>window.location.replace("<?php echo get_permalink( $settings['form_redirect_page'] ); ?>");<?php
                    }
                    if( $settings['form_redirect_option']=='custom' ) {
                        ?>window.location.replace("<?php echo $settings['form_redirect']; ?>");<?php
                    }
                }else{
                    ?>
                    var $form = $('.super-form-<?php echo $form_id; ?>');
                    $form.find('.super-field').fadeOut(<?php echo $duration; ?>);
                    setTimeout(function () {
                        $form.find('.super-field').remove();
                        $form.append('<h1 class="super-thanks-title"><?php echo do_shortcode($settings['form_thanks_title']); ?></h1>');
                        $form.append('<p class="super-thanks-description"><?php echo do_shortcode($settings['form_thanks_description']); ?></p>');
                        $form.children('.super-thanks-title').css('display', 'none').fadeIn(<?php echo $duration; ?>);
                        $form.children('.super-thanks-description').css('display', 'none').fadeIn(<?php echo $duration; ?>);
                    }, <?php echo $duration; ?>);
                    $('html, body').animate({
                        scrollTop: $form.offset().top-200
                    }, 1000);
                    <?php
                }
                ?>
            </script>
            <?php
            die();
        }
    }

}
SUPER_Ajax::init();