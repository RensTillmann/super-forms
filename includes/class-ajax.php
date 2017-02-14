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

if( !class_exists( 'SUPER_Ajax' ) ) :

/**
 * SUPER_Ajax Class
 */
class SUPER_Ajax {
    
    /** 
     *  Define ajax callback functions
     *
     *  @since      1.0.0
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
            'deactivate'                    => false,
            'import_settings'               => false,
            'export_entries'                => false, // @since 1.1.9
            'prepare_contact_entry_import'  => false, // @since 1.2.6
            'import_contact_entries'        => false, // @since 1.2.6

            'marketplace_report_abuse'      => false, // @since 1.2.8
            'marketplace_add_item'          => false, // @since 1.2.8
            'marketplace_install_item'      => false, // @since 1.2.8
            'marketplace_purchase_item'     => false, // @since 1.2.8
            'marketplace_rate_item'         => false, // @since 1.2.8

            'get_entry_export_columns'      => false, // @since 1.7
            'export_selected_entries'       => false, // @since 1.7
            'update_contact_entry'          => false, // @since 1.7

            'activate_add_on'               => false, // @since 1.9
            'deactivate_add_on'             => false, // @since 1.9

            'export_forms'                  => false, // @since 1.9
            'start_forms_import'            => false, // @since 1.9

            'populate_form_data'            => true,  // @since 2.2.0


        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }


    /** 
     *  Populate form with contact entry data
     *
     *  @since      2.2.0
    */
    public static function populate_form_data() {
        global $wpdb;
        $value = sanitize_text_field($_POST['value']);
        $method = sanitize_text_field($_POST['method']);
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        if($method=='equals') $query = "post_title = BINARY '$value'";
        if($method=='contains') $query = "post_title LIKE BINARY '%$value%'";
        $entry = $wpdb->get_results("SELECT ID FROM $table WHERE $query AND post_status IN ('publish','super_unread','super_read') AND post_type = 'super_contact_entry' LIMIT 1");
        $data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
        unset($data['hidden_form_id']);
        if( isset($entry[0])) {
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => $entry[0]->ID,
                'type' => 'entry_id'
            );
        }
        echo json_encode($data);
        die();
    }


    /** 
     *  Update contact entry data
     *
     *  @since      1.7
    */
    public static function update_contact_entry() {
        $id = absint( $_REQUEST['id'] );
        $new_data = $_REQUEST['data'];
        $data = get_post_meta( $id, '_super_contact_entry_data', true );
        $data[] = array();
        foreach($data as $k => $v){
            if(isset($new_data[$k])) {
                $data[$k]['value'] = $new_data[$k];
            }
        }
        $result = update_post_meta( $id, '_super_contact_entry_data', $data);
        if($result){
            SUPER_Common::output_error(
                $error = false,
                $msg = __( 'Contact entry updated.', 'super-forms' )
            );
        }else{
            SUPER_Common::output_error(
                $error = true,
                $msg = __( 'Failed to update contact entry.', 'super-forms' )
            );
        }
        die();
    }


    /** 
     *  Export selected entries to CSV
     *
     *  @since      1.7
    */
    public static function export_selected_entries() {
        $columns = $_REQUEST['columns'];
        $query = $_REQUEST['query'];
        $rows = array();
        foreach( $columns as $k => $v ) {
            $rows[0][$k] = $v;
        }

        global $wpdb;
        $delimiter = ',';
        $enclosure = '"';
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $entries = $wpdb->get_results("
        SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry' AND entry.ID IN ($query)");

        foreach( $entries as $k => $v ) {
            $data = unserialize( $v->data );
            $data['entry_id']['value'] = $v->ID;
            $data['entry_title']['value'] = $v->post_title;
            $data['entry_date']['value'] = $v->post_date;
            $data['entry_author']['value'] = $v->post_author;
            $data['entry_status']['value'] = $v->post_status;
            $data['entry_ip']['value'] = get_post_meta( $v->ID, '_super_contact_entry_ip', true );
            $entries[$k] = $data;
        }

        foreach( $entries as $k => $v ) {
            foreach( $columns as $ck => $cv ) {
                if( isset( $v[$ck] ) ) {
                    if( (isset($v[$ck]['type'])) && ($v[$ck]['type'] == 'files') ) {
                        $files = '';
                        if( ( isset( $v[$ck]['files'] ) ) && ( count( $v[$ck]['files'] )!=0 ) ) {
                            foreach( $v[$ck]['files'] as $fk => $fv ) {
                                if( $fk==0 ) {
                                    $files .= $fv['url'];
                                }else{
                                    $files .= "\n" . $fv['url'];
                                }
                            }
                        }
                        $rows[$k+1][] = $files;
                    }else{
                        if( !isset($v[$ck]['value']) ) {
                            $rows[$k+1][] = '';
                        }else{
                            $rows[$k+1][] = $v[$ck]['value'];
                        }
                    }
                }else{
                    $rows[$k+1][] = '';
                }
            }
        }
        $file_location = '/uploads/php/files/super-contact-entries.csv';
        $source = urldecode( SUPER_PLUGIN_DIR . $file_location );
        if( file_exists( $source ) ) {
            SUPER_Common::delete_file( $source );
        }
        $fp = fopen( $source, 'w' );
        foreach ( $rows as $fields ) {
            fputcsv( $fp, $fields, $delimiter, $enclosure );
        }
        fclose( $fp );
        echo SUPER_PLUGIN_FILE . $file_location;
        die();
    }


    /** 
     *  Return entry export columns
     *
     *  @since      1.7
    */
    public static function get_entry_export_columns() {
        global $wpdb;

        $settings = get_option( 'super_settings' );
        $fields = explode( "\n", $settings['backend_contact_entry_list_fields'] );
        if( !isset($settings['backend_contact_entry_list_form']) ) $settings['backend_contact_entry_list_form'] = 'true';
        
        $column_settings = array();
        foreach( $fields as $k ) {
            $field = explode( "|", $k );
            $column_settings[$field[0]] = $field[1];
        }

        $entries = $_REQUEST['entries'];
        $query = '';
        foreach( $entries as $k => $v ) {
            if( $k==0 ) {
                $query .= $v;
            }else{
                $query .= ',' . $v;
            }
        }
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $results = $wpdb->get_results("
        SELECT meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry' AND entry.ID IN ($query)");
        $columns = array();
        $columns[] = 'entry_id';
        $columns[] = 'entry_title';
        $columns[] = 'entry_date';
        $columns[] = 'entry_author';
        $columns[] = 'entry_status';
        foreach( $results as $k => $v ) {
            $data = unserialize( $v->data );
            foreach( $data as $dk => $dv ) {
                if ( !in_array( $dk, $columns ) ) {
                    $columns[] = $dk;
                }
            }
        }
        $columns[] = 'entry_ip';
        echo '<ul class="super-export-entry-columns">';
        foreach( $columns as $k => $v ) {
            echo '<li class="super-entry-column">';
            echo '<input type="checkbox"' . ((isset($column_settings[$v])) ? ' checked="checked"' : '') . ' />';
            echo '<span class="name">' . $v . '</span>';
            echo '<input type="text" value="' . ((isset($column_settings[$v])) ? $column_settings[$v] : $v) . '" />';
            echo '<span class="sort"></span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<input type="hidden" name="query" value="' . $query . '" />';
        echo '<span class="button button-primary button-large super-export-selected-columns">Export</span>';
        die();
    }


    /** 
     *  Rate marketplace item
     *
     *  @since      1.2.8
    */
    public static function marketplace_rate_item() {

        $author = SUPER_Common::get_author_by_license();
        $item_id = absint($_POST['item']);

        // Get marketplace item
        $items = array();
        $args = array(
            'api' => 'get-items',
            'author' => $author,
            's' => '',
            'tag' => '',
            'tab' => '',
            'id' => $item_id,
            'type' => 0
        );
        $url = 'http://f4d.nl/super-forms/';
        $response = wp_remote_post( 
            $url, 
            array(
                'timeout' => 45,
                'body' => $args
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            SUPER_Common::output_error(
                $error = true,
                $msg = __( 'Something went wrong', 'super-forms' ) . ': ' . $error_message
            );
        } else {
            $item = $response['body'];
            $item = json_decode($item);
            $item = $item[0];
        }

        if( $item->price!=0 ) {
            $url = 'http://f4d.nl/super-forms/?api=get-marketplace-payments&author=' . $author;
            $response = wp_remote_get( $url, array('timeout'=>60) );
            $licenses = $response['body'];
            $licenses = json_decode($licenses);
            $licenses_new = array();
            if( isset( $licenses[0] ) ) {
                foreach( $licenses[0] as $k => $v ) {
                    $licenses_new[] = $v;
                }
            }
            if( !in_array( $item_id, $licenses_new ) ) {
                $error_message = $response->get_error_message();
                SUPER_Common::output_error(
                    $error = true,
                    $msg = __( 'You do not own this form, so you are not allowed to rate it!', 'super-forms' ) . ': ' . $error_message
                );    
            }
        }

        $rating = absint($_POST['rating']);
        if($author==''){
            SUPER_Common::output_error(
                $error = true,
                $msg = __( 'You haven\'t activated Super Forms yet, please activate the plugin in order to rate items!', 'super-forms' )
            );
        }else{
            $url = 'http://f4d.nl/super-forms/';
            $args = array(
                'api' => 'marketplace-rate-item', 
                'item' => $item_id,
                'user' => $author,
                'rating' => $rating
            );
            $response = wp_remote_post( $url, array( 'timeout' => 45, 'body' => $args ) );
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                SUPER_Common::output_error(
                    $error = true,
                    $msg = __( 'Something went wrong', 'super-forms' ) . ': ' . $error_message
                );
            } else {
                if($response['body']=='true'){
                    SUPER_Common::output_error(
                        $error = false,
                        $msg = '-'
                    );
                }else{
                    SUPER_Common::output_error(
                        $error = false,
                        $msg = __( 'Something went wrong while adding your form', 'super-forms' ) . ': ' . $response['body']
                    );
                }
            }

        }
        die();
        
    }


    /** 
     *  Purchase marketplace item
     *
     *  @since      1.2.8
    */
    public static function marketplace_purchase_item() {
        $author = SUPER_Common::get_author_by_license();
        if($author==''){
            SUPER_Common::output_error(
                $error = true,
                $msg = __( 'You haven\'t activated Super Forms yet, please activate the plugin in order to purchase this item!', 'super-forms' )
            );
        }else{
            echo $author;
        }
        die();
    }


    /** 
     *  Install marketplace item
     *
     *  @since      1.2.8
    */
    public static function marketplace_install_item() {
        $author = SUPER_Common::get_author_by_license();
        $item = absint($_POST['item']);
        $url = 'http://f4d.nl/super-forms/';
        $args = array(
            'api' => 'marketplace-install-item', 
            'item' => $item,
            'user' => $author
        );
        $response = wp_remote_post( 
            $url, 
            array(
                'timeout' => 45,
                'body' => $args
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            SUPER_Common::output_error(
                $error = true,
                $msg = __( 'Something went wrong', 'super-forms' ) . ': ' . $error_message
            );
        } else {
            $response_body = $response['body'];
            $response = json_decode($response['body']);
            if($response->error==false){
                $form = array(
                    'post_title' => $response->title,
                    'post_status' => 'publish',
                    'post_type'  => 'super_form'
                );
                $id = wp_insert_post( $form );
                $response->id = $id;
                $response_body = $response;
                $raw_shortcode = json_encode($response->fields);
                $response->settings = (array) $response->settings;
                add_post_meta( $id, '_super_elements', wp_slash($raw_shortcode) );
                add_post_meta( $id, '_super_form_settings', $response->settings );
                if($response->css!=''){
                    add_post_meta( $id, '_super_form_css', $response->css );
                }
                echo json_encode($response_body);
            }else{
                echo $response_body;
            }
        }
        die();
    }


    /** 
     *  Add marketplace item
     *
     *  @since      1.2.8
    */
    public static function marketplace_add_item() {
        
        $license = get_option( 'super_settings' );
        $license = $license['license'];
        $author = SUPER_Common::get_author_by_license($license);
        if($author==''){
            SUPER_Common::output_error(
                $error = true,
                $msg = __( 'You haven\'t activated Super Forms yet, please activate the plugin in order to add your form to the marketplace!', 'super-forms' )
            );
        }else{
            $form = absint($_POST['form']);
            $price = absint($_POST['price']);
            $paypal = sanitize_email($_POST['paypal']);
            $email = sanitize_email($_POST['email']);
            $tags = $_POST['tags'];
            $settings = get_post_meta( $form, '_super_form_settings', true );
            $fields = get_post_meta( $form, '_super_elements', true );
            $fields = json_decode($fields, true);
            if( !isset( $settings['form_custom_css'] ) ) {
                $css = '';
            }else{
                $css = $settings['form_custom_css'];
            }
            $url = 'http://f4d.nl/super-forms/';
            $args = array(
                'api' => 'marketplace-add-item', 
                'title' => get_the_title($form),
                'author' => $author,
                'email' => $email,
                'tags' => $tags,
                'license' => $license,
                'settings' => $settings,
                'fields' => $fields,
                'css' => $css,
                'price' => $price,
                'paypal' => $paypal
            );
            $response = wp_remote_post( 
                $url, 
                array(
                    'timeout' => 45,
                    'body' => $args
                )
            );
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                SUPER_Common::output_error(
                    $error = true,
                    $msg = __( 'Something went wrong', 'super-forms' ) . ': ' . $error_message
                );
            } else {
                if($response['body']=='true'){
                    $items_added_date = get_option( 'super_marketplace_items_added_date', date('Y-m-d') );
                    if( strtotime($items_added_date)<strtotime(date('Y-m-d')) ) {
                        delete_option( 'super_marketplace_items_added' );
                        delete_option( 'super_marketplace_items_added_date' );
                    }
                    $items_added = get_option( 'super_marketplace_items_added', array() );
                    if( !in_array( $form, $items_added ) ) {
                        $items_added[] = $form;
                    }
                    update_option( 'super_marketplace_items_added', $items_added );
                    update_option( 'super_marketplace_items_added_date', date('Y-m-d') );
                    SUPER_Common::output_error(
                        $error = false,
                        $msg = '-',
                        $redirect = $admin_url . 'admin.php?page=super_marketplace&tab=your-forms&added=1'
                    );
                }else{
                    SUPER_Common::output_error(
                        $error = false,
                        $msg = __( 'Something went wrong while adding your form', 'super-forms' ) . ': ' . $response['body']
                    );
                }
            }
        }
        die();
    }


    /** 
     *  Report marketplace item
     *
     *  @since      1.2.8
    */
    public static function marketplace_report_abuse() {

        $author = SUPER_Common::get_author_by_license();
        if($author==''){
            SUPER_Common::output_error(
                $error = true,
                $msg = __( 'You haven\'t activated Super Forms yet, please activate the plugin in order to add your form to the marketplace!', 'super-forms' )
            );
        }else{
            $id = absint( $_REQUEST['id'] );
            $reason = sanitize_text_field( $_REQUEST['reason'] );
            $url = 'http://f4d.nl/super-forms/';
            $args = array(
                'api' => 'marketplace-report', 
                'id' => $id, 
                'reason' => $reason,
                'user' => $author
            );
            $response = wp_remote_post( 
                $url, 
                array(
                    'timeout' => 45,
                    'body' => $args
                )
            );
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
            } else {
                echo $response['body'];
            }
        }
        die();
    }


    /** 
     *  Verify the Google reCAPTCHA
     *
     *  @since      1.0.0
    */
    public static function verify_recaptcha() {
        $settings = get_option( 'super_settings' );
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $args = array(
            'secret' => $settings['form_recaptcha_secret'], 
            'response' => $_REQUEST['response']
        );
        // @since 1.2.2   use wp_remote_post instead of file_get_contents because of the 15 sec. open connection on some hosts
        $response = wp_remote_post( 
            $url, 
            array(
                'timeout' => 45,
                'body' => $args
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result = json_decode( $response['body'], true );
            if( $result['success']==true ) {
                echo 1; //Success!

            }else{
                echo 1; //Error!
            }
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
        if($array['smtp_enabled']=='enabled'){
            if ( !class_exists( 'PHPMailer' ) ) {
                require_once( 'phpmailer/class.phpmailer.php' );
            }
            if ( !class_exists( 'SMTP' ) ) {
                require_once( 'phpmailer/class.smtp.php' );
            }
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = $array['smtp_host'];
            $mail->Username = $array['smtp_username'];
            $mail->Password = $array['smtp_password'];
            $mail->Port = $array['smtp_port'];
            if( $array['smtp_auth']=='enabled' ) $mail->SMTPAuth = true;
            if( $array['smtp_secure']!='' ) $mail->SMTPSecure = $array['smtp_secure']; 
            if($mail->smtpConnect()!==true){
                $reflector = new \ReflectionClass($mail);
                $classProperty = $reflector->getProperty('language');
                $classProperty->setAccessible(true);
                $error_data = $classProperty->getValue($mail);
                foreach($error_data as $ek => $ev){
                    SUPER_Common::output_error(
                        $error='smtp_error',
                        $ev
                    );
                    die();
                }
                SUPER_Common::output_error(
                    $error='smtp_error',
                    __( 'Invalid SMTP settings!', 'super-forms' )
                );
                die();
            }
        }
        update_option( 'super_settings', $array );
        
        $domain = $_SERVER['SERVER_NAME'];
        $url = 'http://f4d.nl/super-forms/?api=license-check&key=' . $array['license'] . '&domain=' . $domain;
        $response = wp_remote_get( $url, array('timeout'=>60) );
        $result = $response['body'];
        if( $result==false ) {
            $result = 'offline';
        }
        if($result=='activated'){
            update_option( 'image_default_positioning', 1 );
            $error=false;
            $msg = __( 'Plugin is activated!', 'super-forms' );
        }else{
            $error=true;
            if($result=='activate'){
                update_option( 'image_default_positioning', 1 );
                $error=false;
                $msg = __( 'Product successfully activated!', 'super-forms' );
            }
            if($result=='used'){
                update_option( 'image_default_positioning', 0 );
                $msg = __( 'Purchase code already used on an other domain, could not activate the plugin!<br />Please <a target="_blank" href="http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866">purchase another license</a> in order to activate the plugin..', 'super-forms' );
            }
            if($result=='invalid'){
                update_option( 'image_default_positioning', 0 );
                $msg = __( 'Invalid purchase code, please check and try again!', 'super-forms' );
            }                
            if($result=='error'){
                update_option( 'image_default_positioning', 0 );
                $msg = __( 'Either the Purchase Code was empty or something else went wrong', 'super-forms' );
            }
            if($result=='offline'){
                update_option( 'image_default_positioning', 1 );
                $msg = __( 'Could\'t connect database to check Purchase Code. Plugin activated manually.', 'super-forms' );
            } 
            if( ($result!='activate') && ($result!='used') && ($result!='invalid') && ($result!='error') && ($result!='offline')  ) {
                $msg = __( 'We couldn\'t check if your activation code is valid because your Access control configuration prevents your request from being allowed at this time. Please contact your service provider to resolve this problem. For now we have temporarily activated your plugin. Make sure you fix this issue.', 'super-forms' );
                update_option( 'image_default_positioning', 1 );
            }
        }
        SUPER_Common::output_error(
            $error,
            $msg
        );
        die();
    }


    /** 
     *  Deactivate plugin
     *
     *  @since      1.1.5
    */
    public static function deactivate() {
        $array = array();
        foreach( $_REQUEST['data'] as $k => $v ) {
            $array[$v['name']] = $v['value'];
        }
        $license = $array['license'];
        $domain = $_SERVER['SERVER_NAME'];
        $url = 'http://f4d.nl/super-forms/?api=license-deactivate&key=' . $license . '&domain=' . $domain;
        $response = wp_remote_get( $url, array('timeout'=>60) );
        $result = $response['body'];
        if( $result==false ) {
            $result = 'offline';
        }
        if($result=='deactivate'){
            update_option( 'image_default_positioning', 0 );
            $error=false;
            $msg = __( 'Plugin has been deactivated!', 'super-forms' );
        }else{
            $error=true;
            if($result=='invalid'){
                update_option( 'image_default_positioning', 0 );
                $msg = __( 'Invalid purchase code, please check and try again!', 'super-forms' );
            }                
            if($result=='error'){
                update_option( 'image_default_positioning', 0 );
                $msg = __( 'Either the Purchase Code was empty or something else went wrong', 'super-forms' );
            }
            if($result=='offline'){
                update_option( 'image_default_positioning', 1 );
                $msg = __( 'Could\'t connect database to check Purchase Code. Plugin activated manually.', 'super-forms' );
            }            
        }
        SUPER_Common::output_error(
            $error,
            $msg
        );
        die();
    }


    /** 
     *  Activate add-on
     *
     *  @since      1.9
    */
    public static function activate_add_on() {
        $add_on = $_REQUEST['add_on'];
        $license = $_REQUEST['license'];
        $settings = get_option( 'super_settings' );
        $settings['license_' . $add_on] = $license;
        update_option( 'super_settings', $settings );

        $domain = $_SERVER['SERVER_NAME'];
        $url = 'http://f4d.nl/super-forms/?api=license-add-on-check&add-on=' . $add_on . '&key=' . $license . '&domain=' . $domain;
        $response = wp_remote_get( $url, array('timeout'=>60) );
        $result = $response['body'];
        if( $result==false ) {
            $result = 'offline';
        }
        if($result=='activated'){
            update_option( 'sac_' . $add_on, 1 );
            $error=false;
            $msg = __( 'Add-on is activated!', 'super-forms' );
        }else{
            $error=true;
            if($result=='activate'){
                update_option( 'sac_' . $add_on, 1 );
                $error=false;
                $msg = __( 'Add-on successfully activated!', 'super-forms' );
            }
            if($result=='used'){
                update_option( 'sac_' . $add_on, 0 );
                $msg = __( 'Purchase code already used on an other domain, could not activate the Add-on!<br />Please <a target="_blank" href="https://codecanyon.net/user/feeling4design/portfolio">purchase another license</a> in order to activate the Add-on.', 'super-forms' );
            }
            if($result=='invalid'){
                update_option( 'sac_' . $add_on, 0 );
                $msg = __( 'Invalid purchase code, please check and try again!', 'super-forms' );
            }                
            if($result=='error'){
                update_option( 'sac_' . $add_on, 0 );
                $msg = __( 'Either the Purchase Code was empty or something else went wrong', 'super-forms' );
            }
            if($result=='offline'){
                update_option( 'sac_' . $add_on, 1 );
                $msg = __( 'Could\'t connect database to check Purchase Code. Add-on activated manually.', 'super-forms' );
            } 
            if( ($result!='activate') && ($result!='used') && ($result!='invalid') && ($result!='error') && ($result!='offline')  ) {
                $msg = __( 'We couldn\'t check if your activation code is valid because your Access control configuration prevents your request from being allowed at this time. Please contact your service provider to resolve this problem. For now we have temporarily activated your Add-on. Make sure you fix this issue.', 'super-forms' );
                update_option( 'sac_' . $add_on, 1 );
            }
        }
        SUPER_Common::output_error(
            $error,
            $msg
        );
        die();
    }


    /** 
     *  Deactivate add-on
     *
     *  @since      1.9
    */
    public static function deactivate_add_on() {
        $add_on = $_REQUEST['add_on'];
        $license = $_REQUEST['license'];
        $domain = $_SERVER['SERVER_NAME'];
        $url = 'http://f4d.nl/super-forms/?api=license-deactivate-add-on&add-on=' . $add_on . '&key=' . $license . '&domain=' . $domain;
        $response = wp_remote_get( $url, array('timeout'=>60) );
        $result = $response['body'];
        if( $result==false ) {
            $result = 'offline';
        }
        if($result=='deactivate'){
            update_option( 'sac_' . $add_on, 0 );
            $error=false;
            $msg = __( 'Add-on has been deactivated!', 'super-forms' );
        }else{
            $error=true;
            if($result=='invalid'){
                update_option( 'sac_' . $add_on, 0 );
                $msg = __( 'Invalid purchase code, please check and try again!', 'super-forms' );
            }                
            if($result=='error'){
                update_option( 'sac_' . $add_on, 0 );
                $msg = __( 'Either the Purchase Code was empty or something else went wrong', 'super-forms' );
            }
            if($result=='offline'){
                update_option( 'sac_' . $add_on, 1 );
                $msg = __( 'Could\'t connect database to deactivate the Add-on, please try again later.', 'super-forms' );
            }            
        }
        SUPER_Common::output_error(
            $error,
            $msg
        );
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
     *  Import Contact Entries (from CSV file)
     *
     *  @since      1.2.6
    */
    public static function import_contact_entries() {
        $file_id = absint( $_REQUEST['file_id'] );
        $column_connections = $_REQUEST['column_connections'];
        $skip_first = $_REQUEST['skip_first'];
        $delimiter = ',';
        if( isset( $_REQUEST['import_delimiter'] ) ) {
            $delimiter = $_REQUEST['import_delimiter'];
        }
        $enclosure = '"';
        if( isset( $_REQUEST['import_enclosure'] ) ) {
            $enclosure = stripslashes($_REQUEST['import_enclosure']);
        }
        $file = get_attached_file($file_id);
        $columns = array();
        $entries = array();
        if( $file ) {
            $row = 0;
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
                    $data = array_map( "utf8_encode", $data );
                    if( ( $skip_first=='true' ) && ( $row==0 ) ) {
                        $row++;
                        continue;
                    }
                    $num = count($data);
                    $row++;
                    foreach( $data as $k => $v ) {
                        $column_type = $column_connections[$k]['column'];
                        $column_name = $column_connections[$k]['name'];
                        $column_label = $column_connections[$k]['label'];
                        if( $column_type=='form_id' ) {
                            $column_name = 'hidden_form_id';
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'value' => $v,
                                'type' => $column_type
                            );
                            continue;
                        }
                        if( $column_type=='var' ) {
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'label' => $column_label,
                                'value' => $v,
                                'type' => $column_type
                            );
                            continue;
                        }
                        if( $column_type=='text' ) {
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'label' => $column_label,
                                'value' => $v,
                                'type' => $column_type
                            );
                            continue;
                        }
                        if( $column_type=='file' ) {
                            $files = explode( ",", $v );   
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'label' => $column_label,
                                'type' => 'files',
                                'files' => array()
                            );
                            foreach( $files as $k => $v ) {
                                $entries[$row]['data'][$column_name]['files'][$k] = array(
                                    'name' => $column_name,
                                    'label' => $column_label,
                                    'value' => $v,
                                );
                            }
                            continue;
                        }
                        $entries[$row][$column_type] = $v;
                    }
                }
                fclose($handle);
            }
        }

        /*
        $json = '';
        foreach( $entries as $k => $v ) {
            $json .= '{';
            $json .= '"field":"source",';
            $json .= '"logic":"not_equal",';
            $json .= '"value":"English",';
            $json .= '"and_method":"and",';
            $json .= '"field_and":"target",';
            $json .= '"logic_and":"equal",';
            $json .= '"value_and":"'.$v['post_title'].'",';
            $json .= '"new_value":"'.str_replace(',', '.', $v['post_date']).'"';
            $json .= '},';
            $json .= '{';
            $json .= '"field":"source",';
            $json .= '"logic":"equal",';
            $json .= '"value":"'.$v['post_title'].'",';
            $json .= '"and_method":"and",';
            $json .= '"field_and":"target",';
            $json .= '"logic_and":"equal",';
            $json .= '"value_and":"'.$v['post_author'].'",';
            $json .= '"new_value":"'.str_replace(',', '.', $v['post_date']).'"';
            $json .= '},';
        }
        echo $json;
        exit;
        */

        $settings = get_option( 'super_settings' );
        foreach( $entries as $k => $v ) {
            $data = $v['data'];
            $post_author = 0;
            if( isset( $v['post_author'] ) ) {
                $post_author = absint( $v['post_author'] );
            }
            $post_date = 0;
            if( isset( $v['post_date'] ) ) {
                $post_date = $v['post_date'];
            }
            $ip_address = '';
            if( isset( $v['ip_address'] ) ) {
                $ip_address = $v['ip_address'];
            }
            $post = array(
                'post_status' => 'super_unread',
                'post_type'  => 'super_contact_entry',
                'post_author' => $post_author,
                'post_date' => $post_date
            ); 
            $contact_entry_id = wp_insert_post($post);
            if( $contact_entry_id!=0 ) {
                add_post_meta( $contact_entry_id, '_super_contact_entry_data', $data);
                add_post_meta( $contact_entry_id, '_super_contact_entry_ip', $ip_address );
                if( isset( $v['post_title'] ) ) {
                    $contact_entry_title = $v['post_title'];
                }else{
                    $contact_entry_title = __( 'Contact entry', 'super-forms' );
                }
                if( $settings['contact_entry_add_id']=='true' ) {
                    $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
                }
                $contact_entry = array(
                    'ID' => $contact_entry_id,
                    'post_title'  => $contact_entry_title,
                );
                wp_update_post( $contact_entry );
                $imported++;
            }
        }

        echo '<div class="message success">';
        echo sprintf( __( '%d of %d contact entries imported!', 'super-forms' ), $imported, count($entries) );
        echo '</div>';
        die();

    }


    /** 
     *  Prepare Contact Entries Import (from CSV file)
     *
     *  @since      1.2.6
    */
    public static function prepare_contact_entry_import() {
        $file_id = absint( $_REQUEST['file_id'] );
        $delimiter = ',';
        if( isset( $_REQUEST['import_delimiter'] ) ) {
            $delimiter = $_REQUEST['import_delimiter'];
        }
        $enclosure = '"';
        if( isset( $_REQUEST['import_enclosure'] ) ) {
            $enclosure = stripslashes($_REQUEST['import_enclosure']);
        }
        $file = get_attached_file($file_id);
        $columns = array();
        if( $file ) {
            $row = 1;
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
                    $data = array_map( "utf8_encode", $data );
                    $num = count($data);
                    $row++;
                    $value = 'undefined';
                    $title = 'undefined';
                    for ( $c=0; $c < $num; $c++ ) {
                        $columns[] = $data[$c];
                    }
                    break;
                }
                fclose($handle);
            }
        }
        echo json_encode($columns);
        die();
    }


    /** 
     *  Export Forms
     *
     *  @since      1.9
    */
    public static function export_forms() {
        global $wpdb;
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        
        $file_location = '/uploads/php/files/super-forms-export.txt';
        $source = urldecode( SUPER_PLUGIN_DIR . $file_location );
        
        $forms = $wpdb->get_results("
        SELECT 
        form.ID,
        form.post_author,
        form.post_date,
        form.post_date_gmt,
        form.post_title,
        form.post_status
        FROM $table AS form WHERE form.post_type = 'super_form' LIMIT 1", ARRAY_A);
        foreach( $forms as $k => $v ) {
            $id = $v['ID'];
            $elements = get_post_meta( $id, '_super_elements', true );
            $settings = get_post_meta( $id, '_super_form_settings', true );
            $forms[$k]['elements'] = json_decode($elements, true);
            $forms[$k]['settings'] = $settings;
        }
        $content = json_encode($forms);
        file_put_contents($source, $content);
        echo SUPER_PLUGIN_FILE . $file_location;
        /*
        $content = file_get_contents($source);
        $json = json_decode($content, true);
        var_dump($json);
        */
        die();
    }


    /** 
     *  Prepare Forms Import (from TXT file)
     *
     *  @since      1.9
    */
    public static function start_forms_import() {
        $file_id = absint( $_REQUEST['file_id'] );
        $source = get_attached_file($file_id);
        $json = file_get_contents($source);
        $forms = json_decode($json, true);
        foreach($forms as $k => $v){
            $form = array(
                'post_author' => $v['post_author'],
                'post_date' => $v['post_date'],
                'post_date_gmt' => $v['post_date_gmt'],
                'post_title' => $v['post_title'],
                'post_status' => $v['post_status'],
                'post_type'  => 'super_form'
            );
            $id = wp_insert_post( $form );
            add_post_meta( $id, '_super_elements', wp_slash(json_encode($v['elements'])) );
            add_post_meta( $id, '_super_form_settings', $v['settings'] );
        }
        die();
    }


    /** 
     *  Export Contact Entries (to CSV or TSV)
     *
     *  @since      1.1.9
    */
    public static function export_entries() {
        global $wpdb;
        $type = 'csv';
        if( isset( $_REQUEST['type'] ) ) {
            $type = $_REQUEST['type'];
        }
        $from = '';
        $till = '';
        $range_query = '';
        if( isset( $_REQUEST['from'] ) ) {
            $from = $_REQUEST['from'];
        }
        if( isset( $_REQUEST['till'] ) ) {
            $till = $_REQUEST['till'];
        }
        if( ($from!='') && ($till!='') ) {
            $from = date( 'Y-m-d', strtotime( $from ) );
            $till = date( 'Y-m-d', strtotime( $till ) );
            $range_query = " AND ((entry.post_date LIKE '$from%' OR entry.post_date LIKE '$till%') OR (entry.post_date BETWEEN '$from' AND '$till'))";
        }

        $delimiter = ',';
        if( isset( $_REQUEST['delimiter'] ) ) {
            $delimiter = $_REQUEST['delimiter'];
        }
        $enclosure = '"';
        if( isset( $_REQUEST['enclosure'] ) ) {
            $enclosure = stripslashes($_REQUEST['enclosure']);
        }
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $entries = $wpdb->get_results("
        SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry'$range_query");

        $rows = array();
        $columns = array();
        $rows[0][] = 'entry_id';
        $rows[0][] = 'entry_title';
        $rows[0][] = 'entry_date';
        $rows[0][] = 'entry_author';
        $rows[0][] = 'entry_status';
        $columns[] = 'entry_id';
        $columns[] = 'entry_title';
        $columns[] = 'entry_date';
        $columns[] = 'entry_author';
        $columns[] = 'entry_status';
        foreach( $entries as $k => $v ) {
            $data = unserialize( $v->data );
            foreach( $data as $dk => $dv ) {
                if ( !in_array( $dk, $columns ) ) {
                    $columns[] = $dk;
                    $rows[0][] = $dk;
                }
            }
            $data['entry_id']['value'] = $v->ID;
            $data['entry_title']['value'] = $v->post_title;
            $data['entry_date']['value'] = $v->post_date;
            $data['entry_author']['value'] = $v->post_author;
            $data['entry_status']['value'] = $v->post_status;
            $data['entry_ip']['value'] = get_post_meta( $v->ID, '_super_contact_entry_ip', true );
            $entries[$k] = $data;
        }
        $rows[0][] = 'entry_ip';
        $columns[] = 'entry_ip';

        foreach( $entries as $k => $v ) {
            foreach( $columns as $cv ) {
                if( isset( $v[$cv] ) ) {
                    if( (isset($v[$cv]['type'])) && ($v[$cv]['type'] == 'files') ) {
                        $files = '';
                        if( ( isset( $v[$cv]['files'] ) ) && ( count( $v[$cv]['files'] )!=0 ) ) {
                            foreach( $v[$cv]['files'] as $fk => $fv ) {
                                if( $fk==0 ) {
                                    $files .= $fv['url'];
                                }else{
                                    $files .= "\n" . $fv['url'];
                                }
                            }
                        }
                        $rows[$k+1][] = $files;
                    }else{
                        if( !isset($v[$cv]['value']) ) {
                            $rows[$k+1][] = '';
                        }else{
                            $rows[$k+1][] = $v[$cv]['value'];
                        }
                    }
                }else{
                    $rows[$k+1][] = '';
                }
            }
        }
        $file_location = '/uploads/php/files/super-contact-entries.csv';
        $source = urldecode( SUPER_PLUGIN_DIR . $file_location );
        if( file_exists( $source ) ) {
            SUPER_Common::delete_file( $source );
        }
        $fp = fopen( $source, 'w' );
        foreach ( $rows as $fields ) {
            fputcsv( $fp, $fields, $delimiter, $enclosure );
        }
        fclose( $fp );
        echo SUPER_PLUGIN_FILE . $file_location;
        die();
    }


    /** 
     *  Import Settings (from both Create Form and Settings page)
     *
     *  @since      1.0.6
    */
    public static function import_settings() {
        $id = 0;
        $title = __( 'Form Name', 'super-forms' );
        if( isset( $_REQUEST['title'] ) ) {
            $title = $_REQUEST['title'];
        }
        $shortcode = array();
        if( isset( $_REQUEST['shortcode'] ) ) {
            $shortcode = $_REQUEST['shortcode'];
        }
        $settings = $_REQUEST['settings'];
        $settings = json_decode( stripslashes( $settings ), true );
        if( ( isset ( $_REQUEST['method'] ) ) && ( $_REQUEST['method']=='load-default-form-settings' ) ) {
            $settings = get_option( 'super_settings' );
        }
        if( json_last_error() != 0 ) {
            var_dump( 'JSON error: ' . json_last_error() );
        }
        if( isset( $_REQUEST['id'] ) ) {
            $id = absint( $_REQUEST['id'] );
            if( $id==0 ) {
                $id = self::save_form( $id, $shortcode, $settings, $title );
            }else{
                update_post_meta( $id, '_super_elements', $shortcode );
                update_post_meta( $id, '_super_form_settings', $settings );
            }
        }else{
            update_option( 'super_settings', $settings );    
        }
        if( ( isset ( $_REQUEST['method'] ) ) && ( $_REQUEST['method']=='load-default' ) ) {
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
            if( $id!=0 ) {
                update_post_meta( $id, '_super_form_settings', $array );
            }else{
                update_option( 'super_settings', $array );    
            }
        }
        echo $id;
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
    public static function save_form( $id=null, $shortcode=array(), $settings=null, $title=null ) {
        
        if( $id==null ) {
            $id = $_POST['id'];
        }
        $id = absint( $id );
        if( isset( $_POST['shortcode'] ) ) {
            $shortcode = $_POST['shortcode'];
        }
        if( $settings==null ) {
            $settings = array();
            foreach( $_REQUEST['settings'] as $k => $v ) {
                $settings[$v['name']] = $v['value'];
            }
        }
        if( $title==null) {
            $title = __( 'Form Name', 'super-forms' );
        }
        if( isset( $_POST['title'] ) ) {
            $title = $_POST['title'];
        }
        if( empty( $id ) ) {
            $form = array(
                'post_title' => $title,
                'post_status' => 'publish',
                'post_type'  => 'super_form'
            );
            $id = wp_insert_post( $form ); 
            add_post_meta( $id, '_super_elements', $_POST['shortcode'] );
            add_post_meta( $id, '_super_form_settings', $settings );
        }else{
            $form = array(
                'ID' => $id,
                'post_title'  => $title
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
     *  Function to load all element settings while editing the element (create form page / settings tabs)
     *
     *  @param  string  $tag
     *  @param  array   $data
     *
     *  @since      1.0.0
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

        $array = SUPER_Shortcodes::shortcodes( false, $data, false );
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
                            if( isset( $fv['desc'] ) ) $result .= '<i class="info super-tooltip" title="' . $fv['desc'] . '"></i>';
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
        $result .= '<span class="super-button update-element">' . __( 'Update Element', 'super-forms' ) . '</span>';
        $result .= '<span class="super-button cancel-update">' . __( 'Close', 'super-forms' ) . '</span>';
        echo $result;        
        die();
        
    }
    
    
    /** 
     *  Retrieve the HTML for the element that is being dropped inside a dropable element
     *
     *  @param  string  $tag
     *  @param  array   $inner
     *  @param  array   $data
     *  @param  integer $method
     *
     *  @since      1.0.0
    */
    public static function get_element_builder_html( $tag=null, $group=null, $inner=null, $data=null, $method=1 ) {

        $form_id = 0;
        if( isset( $_REQUEST['form_id'] ) ) {
            $form_id = absint( $_REQUEST['form_id'] );
            $settings = get_post_meta( $form_id, '_super_form_settings', true );
            if( $settings==false ) {
                $settings = get_option( 'super_settings' );
            }
        }else{
            $settings = get_option( 'super_settings' );
        }

        include_once( SUPER_PLUGIN_DIR . '/includes/class-shortcodes.php' );
        $shortcodes = SUPER_Shortcodes::shortcodes();

        $predefined = '';
        if( isset( $_REQUEST['predefined'] ) ) {
            $predefined = $_REQUEST['predefined'];
        }
        if( $predefined!='' ) {
            $result = '';
            foreach( $predefined as $k => $v ) {
                // Output builder HTML (element and with action buttons)
                $result .= SUPER_Shortcodes::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
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
                $result = SUPER_Shortcodes::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings );
            }else{
                // Output builder HTML (element and with action buttons)
                $result = SUPER_Shortcodes::output_builder_html( $tag, $group, $data, $inner, $shortcodes, $settings );
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

        // @since 1.7.6
        $data = apply_filters( 'super_before_sending_email_data_filter', $data, array( 'post'=>$_POST, 'settings'=>$settings ) );        

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
                    
                    // @since 1.2.6.92
                    $v = SUPER_Common::email_tags( $v, $data, $settings );
                    
                    $header_additional .= $v . "\r\n";
                }
            }
            $settings['header_additional'] = $header_additional;
        }



        /** 
         *  Make sure to also save the file into the WP Media Library
         *  In case a user deletes Super Forms these files are not instantly deleted without warning
         *
         *  @since      1.1.8
        */
        
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            $delete_dirs = array();
            foreach( $data as $k => $v ) {
                if( $v['type']=='files' ) {
                    if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                        foreach( $v['files'] as $key => $value ) {                              
                            $domain_url_without_http = str_replace( 'http://', '', site_url() );
                            $domain_url_without_http = str_replace( 'https://', '', $domain_url_without_http );
                            $image_url_without_http = str_replace( 'http://' . (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : ''), '', $value['url'] );
                            $image_url_without_http = str_replace( 'https://' . (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : ''), '', $image_url_without_http );
                            $image_url_without_http = str_replace( $domain_url_without_http, '', $image_url_without_http );

                            // @since 1.3
                            // Make sure to skip this file if it's source location is invalid
                            if (strpos($image_url_without_http, 'uploads/php/files') !== false) {

                                $source = urldecode( ABSPATH . $image_url_without_http );
                                $wp_upload_dir = wp_upload_dir();
                                $folder = $wp_upload_dir['basedir'] . '/superforms' . $wp_upload_dir["subdir"];
                                $unique_folder = SUPER_Common::generate_random_folder($folder);
                                $newfile = $unique_folder . '/' . basename( $source );
                                if ( !copy( $source, $newfile ) ) {
                                    $dir = str_replace( basename( $source ), '', $source );
                                    SUPER_Common::delete_dir( $dir );
                                    SUPER_Common::delete_dir( $unique_folder );
                                    SUPER_Common::output_error(
                                        $error = true,
                                        $msg = __( 'Failed to copy', 'super-forms' ) . '"'.$source.'" to: "'.$newfile.'"',
                                        $redirect = $redirect
                                    );
                                    die();
                                }else{
                                    $dir = str_replace( basename( $source ), '', $source );
                                    if( !empty( $dir ) ) {
                                        $delete_dirs[] = $dir;
                                    }
                                    $filename = $newfile;
                                    $parent_post_id = $contact_entry_id;
                                    $filetype = wp_check_filetype( basename( $filename ), null );
                                    $wp_upload_dir = wp_upload_dir();
                                    $attachment = array(
                                        'post_mime_type' => $filetype['type'],
                                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                                        'post_content'   => '',
                                        'post_status'    => 'inherit'
                                    );
                                    $attach_id = wp_insert_attachment( $attachment, $filename );

                                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                                    $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                                    wp_update_attachment_metadata( $attach_id,  $attach_data );
                                    
                                    $data[$k]['files'][$key]['attachment'] = $attach_id;
                                    $data[$k]['files'][$key]['url'] = wp_get_attachment_url( $attach_id );
                                }
                            }
                        }
                    }
                }else{
                    // @since 1.2.9 - Save [label] or both [value and label], make sure we set the correct value if we do not want to save only the value of the element
                    if( isset( $v['entry_value'] ) ) {
                        $data[$k]['value'] = $v['entry_value'];
                    }
                }                   
            }
            foreach( $delete_dirs as $dir ) {
                SUPER_Common::delete_dir( $dir );
            }
        }

        $contact_entry_id = null;
        if( $settings['save_contact_entry']=='yes' ) {
            $post = array(
                'post_status' => 'super_unread',
                'post_type' => 'super_contact_entry' ,
                'post_parent' => $data['hidden_form_id']['value'] // @since 1.7 - save the form ID as the parent
            ); 
            $contact_entry_id = wp_insert_post($post); 

            // @since 2.2.0 - save generated code(s)
            foreach( $data as $k => $v ) {
                if( (isset($v['code'])) && ($v['code']=='true') ) {
                    add_post_meta( $contact_entry_id, '_super_contact_entry_code', $v['value']);
                }
            }

            // @since 1.4 - add the contact entry ID to the data array so we can use it to retrieve it with {tags}
            $data['contact_entry_id']['name'] = 'contact_entry_id';
            $data['contact_entry_id']['value'] = $contact_entry_id;
            $data['contact_entry_id']['label'] = '';
            $data['contact_entry_id']['type'] = 'form_id';

            add_post_meta( $contact_entry_id, '_super_contact_entry_data', $data);
            add_post_meta( $contact_entry_id, '_super_contact_entry_ip', SUPER_Common::real_ip() );
            
            // @since 1.2.6     - custom contact entry titles
            $contact_entry_title = __( 'Contact entry', 'super-forms' );
            if( !isset( $settings['enable_custom_entry_title'] ) ) $settings['enable_custom_entry_title'] = '';
            if( $settings['enable_custom_entry_title']=='true' ) {
                if( !isset( $settings['contact_entry_title'] ) ) $settings['contact_entry_title'] = $contact_entry_title;
                if( !isset( $settings['contact_entry_add_id'] ) ) $settings['contact_entry_add_id'] = '';
                $contact_entry_title = SUPER_Common::email_tags( $settings['contact_entry_title'], $data, $settings );
                if($settings['contact_entry_add_id']=='true'){
                    if($contact_entry_title==''){
                        $contact_entry_title = $contact_entry_id;
                    }else{
                        $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
                    }
                }
            }else{
                $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
            }

            $contact_entry = array(
                'ID' => $contact_entry_id,
                'post_title' => $contact_entry_title,
            );
            wp_update_post( $contact_entry );

            /** 
             *  Hook after inserting contact entry
             *
             *  @param  post    $_POST
             *  @param  array   $settings
             *  @param  int     $contact_entry_id    @since v1.2.2
             *
             *  @since      1.2.9
            */
            do_action( 'super_after_saving_contact_entry_action', array( 'post'=>$_POST, 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id ) );

        }

        // @since 2.2.0 - update contact entry date by ID
        $entry_id = absint( $_POST['entry_id'] );
        if($entry_id!=0){
            $result = update_post_meta( $entry_id, '_super_contact_entry_data', $data);
        }

        $settings = apply_filters( 'super_before_sending_email_settings_filter', $settings );
        
        $email_loop = '';
        $confirm_loop = '';
        $attachments = array();
        $confirm_attachments = array();
        $string_attachments = array();
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            foreach( $data as $k => $v ) {
                $row = $settings['email_loop'];
                if( !isset( $v['exclude'] ) ) {
                    $v['exclude'] = 0;
                }
                if( $v['exclude']==2 ) {
                    continue;
                }

                /** 
                 *  Filter to control the email loop when something special needs to happen
                 *  e.g. Signature Add-on needs to display image instead of the base64 code that the value contains
                 *
                 *  @param  string  $row
                 *  @param  array   $data
                 *
                 *  @since      1.0.9
                */
                $result = apply_filters( 'super_before_email_loop_data_filter', $row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                if( isset( $result['status'] ) ) {
                    if( $result['status']=='continue' ) {
                        if( isset( $result['string_attachments'] ) ) {
                            $string_attachments = $result['string_attachments'];
                        }
                        if( ( isset( $result['exclude'] ) ) && ( $result['exclude']==1 ) ) {
                            $email_loop .= $result['row'];
                        }else{
                            $email_loop .= $result['row'];
                            $confirm_loop .= $result['row'];
                        }
                        continue;
                    }
                }

                if( $v['type']=='files' ) {
                    $files_value = '';
                    if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
                        if( isset( $v['label'] ) ) $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                        $files_value .= __( 'User did not upload any files', 'super-forms' );
                    }else{
                        foreach( $v['files'] as $key => $value ) {
                            if( $key==0 ) {
                                if( isset( $v['label'] ) ) $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                            }
                            $files_value .= '<a href="' . $value['url'] . '" target="_blank">' . $value['value'] . '</a><br /><br />';
                            if( $v['exclude']!=2 ) {
                                if( $v['exclude']==1 ) {
                                    $attachments[$value['value']] = $value['url'];
                                }else{
                                    $attachments[$value['value']] = $value['url'];
                                    $confirm_attachments[$value['value']] = $value['url'];
                                }
                            }
                        }
                    }
                    $row = str_replace( '{loop_value}', $files_value, $row );
                }else{
                    if( ($v['type']=='form_id') || ($v['type']=='entry_id') ) {
                        $row = '';
                        $confirm_row = '';
                    }else{
                        if( isset( $v['label'] ) ) $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                        
                        // @since 1.2.7
                        $confirm_row = $row;
                        if( isset( $v['admin_value'] ) ) {
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['admin_value'] ), $row );
                        }
                        if( isset( $v['confirm_value'] ) ) {
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['confirm_value'] ), $confirm_row );
                        }
                        if( isset( $v['value'] ) ) {
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['value'] ), $row );
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['value'] ), $confirm_row );
                        }

                    }
                }
                if( $v['exclude']==1 ) {
                    $email_loop .= $row;
                }else{
                    $email_loop .= $row;
                    
                    // @since 1.2.7
                    if( isset( $confirm_row) ) {
                        $confirm_loop .= $confirm_row;
                    }else{
                        $confirm_loop .= $row;
                    }
                }                    
            }
        }
        if( $settings['send']=='yes' ) {
            if(!empty($settings['email_body_open'])) $settings['email_body_open'] = $settings['email_body_open'] . '<br /><br />';
            if(!empty($settings['email_body'])) $settings['email_body'] = $settings['email_body'] . '<br /><br />';
            $email_body = $settings['email_body_open'] . $settings['email_body'] . $settings['email_body_close'];
            $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
            $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
            $email_body = nl2br( $email_body );
            $email_body = apply_filters( 'super_before_sending_email_body_filter', $email_body, array( 'settings'=>$settings, 'email_loop'=>$email_loop, 'data'=>$data ) );
            if( !isset( $settings['header_from_type'] ) ) $settings['header_from_type'] = 'default';
            if( $settings['header_from_type']=='default' ) {
                $settings['header_from_name'] = get_option( 'blogname' );
                $settings['header_from'] = get_option( 'admin_email' );
            }
            if( !isset( $settings['header_from_name'] ) ) $settings['header_from_name'] = get_option( 'blogname' );
            if( !isset( $settings['header_from'] ) ) $settings['header_from'] = get_option( 'admin_email' );

            $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_to'], $data, $settings ) );
            $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_from'], $data, $settings ) );
            $from_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings ) );
            $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_cc'], $data, $settings ) );
            $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_bcc'], $data, $settings ) );
            $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_subject'], $data, $settings ) );

            // @since 2.0
            $attachments = apply_filters( 'super_before_sending_email_attachments_filter', $attachments, array( 'settings'=>$settings, 'data'=>$data ) );
            
            // Send the email
            $mail = SUPER_Common::email( $to, $from, $from_name, $cc, $bcc, $subject, $email_body, $settings, $attachments, $string_attachments );

            // Return error message
            if( !empty( $mail->ErrorInfo ) ) {
                $msg = __( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
                SUPER_Common::output_error( $error=true, $msg );
            }
        }
        if( $settings['confirm']=='yes' ) {
            $settings['header_additional'] = '';
            if(!empty($settings['confirm_body_open'])) $settings['confirm_body_open'] = $settings['confirm_body_open'] . '<br /><br />';
            if(!empty($settings['confirm_body'])) $settings['confirm_body'] = $settings['confirm_body'] . '<br /><br />';
            $email_body = $settings['confirm_body_open'] . $settings['confirm_body'] . $settings['confirm_body_close'];
            $email_body = str_replace( '{loop_fields}', $confirm_loop, $email_body );
            $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
            $email_body = nl2br( $email_body );
            $email_body = apply_filters( 'super_before_sending_confirm_body_filter', $email_body, array( 'settings'=>$settings, 'confirm_loop'=>$confirm_loop, 'data'=>$data ) );
            if( !isset( $settings['confirm_from_type'] ) ) $settings['confirm_from_type'] = 'default';
            if( $settings['confirm_from_type']=='default' ) {
                $settings['confirm_from_name'] = get_option( 'blogname' );
                $settings['confirm_from'] = get_option( 'admin_email' );
            }
            if( !isset( $settings['confirm_from_name'] ) ) $settings['confirm_from_name'] = get_option( 'blogname' );
            if( !isset( $settings['confirm_from'] ) ) $settings['confirm_from'] = get_option( 'admin_email' );
            $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_to'], $data, $settings ) );
            $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_from'], $data, $settings ) );
            $from_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_from_name'], $data, $settings ) );
            $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_subject'], $data, $settings ) );

            // @since 2.0
            $confirm_attachments = apply_filters( 'super_before_sending_email_confirm_attachments_filter', $confirm_attachments, array( 'settings'=>$settings, 'data'=>$data )  );

            // Send the email
            $mail = SUPER_Common::email( $to, $from, $from_name, '', '', $subject, $email_body, $settings, $confirm_attachments, $string_attachments );

            // Return error message
            if( !empty( $mail->ErrorInfo ) ) {
                $msg = __( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
                SUPER_Common::output_error( $error=true, $msg );
            }
        }
        if( $form_id!=0 ) {

            /** 
             *  Hook before outputing the success message or redirect after a succesfull submitted form
             *
             *  @param  post    $_POST
             *  @param  array   $settings
             *  @param  int     $contact_entry_id    @since v1.2.2
             *
             *  @since      1.0.2
            */
            do_action( 'super_before_email_success_msg_action', array( 'post'=>$_POST, 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id ) );

            // Return message or redirect and save message to session
            $redirect = null;
            $msg_empty = false;
            if( (empty($settings['form_thanks_description'])) && (empty($settings['form_thanks_title'])) ) {
                $msg_empty = true;
            }
            $settings['form_thanks_title'] = '<h1>' . $settings['form_thanks_title'] . '</h1>';
            $msg = do_shortcode( $settings['form_thanks_title'] . $settings['form_thanks_description'] );
            $msg = SUPER_Common::email_tags( $msg, $data, $settings );
            if( !empty( $settings['form_redirect_option'] ) ) {
                if( $settings['form_redirect_option']=='page' ) {
                    $redirect = get_permalink( $settings['form_redirect_page'] );
                }
                if( $settings['form_redirect_option']=='custom' ) {
                    $redirect = SUPER_Common::email_tags( $settings['form_redirect'], $data, $settings );
                }
                if( $msg_empty==false ) {
                    $_SESSION['super_msg'] = array( 'msg'=>$msg, 'type'=>'success', 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id );
                }
            }
            SUPER_Common::output_error(
                $error = false,
                $msg = $msg,
                $redirect = $redirect
            );
            die();
        }
    }

}
endif;
SUPER_Ajax::init();     