<?php
/**
 * Super Forms Triggers Class.
 *
 * @author      WebRehab
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


    public static function execute_scheduled_trigger_actions(){
        error_log('execute_scheduled_trigger_actions() started');
        // Retrieve reminders from database based on post_meta named `_super_reminder_timestamp` based on the timestamp we can determine if we need to send the reminder yet
        global $wpdb;
        $current_timestamp = strtotime(date('Y-m-d H:i', time()));
        error_log('Current timestamp for checking scheduled actions: ' . $current_timestamp);
        $query = "SELECT post_id, meta_value AS timestamp, post_content,
        (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_super_scheduled_trigger_action_data' AND r.post_id = post_id) AS triggerEventParameters
        FROM $wpdb->postmeta AS r INNER JOIN $wpdb->posts ON ID = post_id
        WHERE meta_key = '_super_scheduled_trigger_action_timestamp' AND meta_value < %d";
        error_log('Executing query: ' . $wpdb->prepare($query, $current_timestamp));
        $scheduled_actions = $wpdb->get_results($wpdb->prepare($query, $current_timestamp));
        error_log('Found ' . count($scheduled_actions) . ' scheduled actions to process');
        foreach($scheduled_actions as $k => $v){
            $scheduled_action_id = $v->post_id;
            error_log('Processing scheduled action ID: ' . $scheduled_action_id);
            $trigger_options = maybe_unserialize($v->post_content);
            error_log('trigger_options: '.json_encode($trigger_options));
            $triggerEventParameters = maybe_unserialize($v->triggerEventParameters);
            $triggerEventParameters['action'] = $trigger_options;
            $triggerEventParameters['scheduled_action_id'] = $scheduled_action_id;
            error_log('triggerEventParameters: '.json_encode($triggerEventParameters));
            // Check if trigger function (action) exists e.g. send_email()
            if(method_exists('SUPER_Triggers', $triggerEventParameters['actionName'])) {
                error_log('Calling action method: ' . $triggerEventParameters['actionName']);
                call_user_func(array('SUPER_Triggers', $trigger_options['action']), $triggerEventParameters);
            }else{
                error_log("Trigger event `".$triggerEventParameters['triggerName']."` tried to call an action named `".$trigger_options['action']."` but such action doesn't exist");
            }
        }
        error_log('execute_scheduled_trigger_actions() completed');
    }

    public static function update_contact_entry_status($x){
        error_log('update_contact_entry_status()');
        extract($x);
        extract($sfsi);
        // Check if we need to grab the settings
        if(!isset($settings)) $settings = SUPER_Common::get_form_settings($form_id);
        error_log('Trigger action updated status of Contact Entry #'.$entry_id.' to: '.$action['data']['status']);
        update_post_meta($entry_id, '_super_contact_entry_status', $action['data']['status']);
    }
    public static function update_created_post_status($x){
        error_log('update_created_post_status()');
        extract($x);
        extract($sfsi);
        // Check if we need to grab the settings
        if(!isset($settings)) $settings = SUPER_Common::get_form_settings($form_id);
        error_log('$action[data][status]: '.$action["data"]["status"]);
        $action['data']['status'] = SUPER_Common::email_tags($action['data']['status'], $data, $settings);
        $isDateValue = false;
        $status = $action['data']['status'];
        error_log('status: '.$status);
        // Check if the status is already a timestamp
        if (is_numeric($status) && (int)$status == $status && $status > 0) {
            // Convert timestamp to datetime format
            $timestamp = (int)$status;
            error_log('timestamp 1: '.$timestamp);
            $isDateValue = true;
        } elseif (strtotime($status)) {
            // Convert date string to timestamp
            $timestamp = strtotime($status);
            error_log('timestamp 2: '.$timestamp);
            $isDateValue = true;
        } else {
            // Not a valid timestamp or date, exit or log an error
        }
        if($isDateValue===true){
            // Format the timestamp for WordPress `post_date` and `post_date_gmt`
            $post_date = date('Y-m-d H:i:s', $timestamp); // Local date
            error_log('post_date: '.$post_date);
            $post_date_gmt = gmdate('Y-m-d H:i:s', $timestamp); // GMT date
            error_log('post_date_gmt: '.$post_date_gmt);
            // Update the post with the future status and date
            $update_data = [
                'ID' => $created_post,
                'post_date' => $post_date,
                'post_date_gmt' => $post_date_gmt,
                'post_status' => 'future',
            ];
            // Use WordPress wp_update_post to update the post
            $result = wp_update_post($update_data);
            // Check for errors
            if(is_wp_error($result)){
                error_log('Error updating post: ' . $result->get_error_message());
            } else {
                error_log('Trigger action updated status of Created Post #'.$created_post.' to future date: '.$post_date);
            }
        }else{
            error_log('Trigger action updated status of Created Post #'.$created_post.' to: '.$action['data']['status']);
            wp_update_post(array('ID'=>$created_post, 'post_status'=>$action['data']['status']));
        }
    }
    public static function update_registered_user_login_status($x){
        error_log('update_registered_user_login_status()');
        extract($x);
        extract($sfsi);
        // Check if we need to grab the settings
        if(!isset($settings)) $settings = SUPER_Common::get_form_settings($form_id);
        error_log(json_encode($sfsi));
    }
    public static function update_registered_user_role($x){
        error_log('update_registered_user_role()');
        extract($x);
        extract($sfsi);
        // Check if we need to grab the settings
        if(!isset($settings)) $settings = SUPER_Common::get_form_settings($form_id);
        error_log(json_encode($sfsi));
    }

    public static function send_email($x){
        //error_log('Trigger: send_email()');
        //error_log('x: '.json_encode($x));
        extract($x);
        //error_log('sfsi: '.json_encode($sfsi));
        extract($sfsi);
        // Check if we need to grab the settings
        if(!isset($settings)) $settings = SUPER_Common::get_form_settings($form_id);
        // Grab action name
        $actionName = $action['action'];
        // Get action options
        $options = $action['data'];
        // Check for translations, and merge
        if(!empty($i18n)){
            $translated_options = ((isset($action['i18n']) && is_array($action['i18n'])) ? $action['i18n'] : array()); // In case this is a translated version
            if(isset($translated_options[$i18n])){
                // Merge any options with translated options
                //$options = array_merge($options, $translated_options[$i18n]);
                $options = SUPER_Common::merge_i18n_options($options, $translated_options[$i18n]);
            }
        }
        // Check if this trigger action needs to be scheduled
        if($options['schedule']['enabled']==='true'){
            $schedules = $options['schedule']['schedules'];
            foreach($schedules as $k => $v){
                // Determine the date
                if(empty($v['days'])) $v['days'] = 0;
                if(empty($v['offset'])) $v['offset'] = 0;
                if(empty($v['date'])) $v['date'] = date('Y-m-d', time());
                $v['days'] = SUPER_Common::email_tags($v['days'], $data, $settings );
                if(!is_numeric($v['days'])) $v['days'] = 0;
                $v['offset'] = SUPER_Common::email_tags($v['offset'], $data, $settings );
                // 86400 = 1 day (24 hours)
                $days_offset = 86400 * $v['days'];
                if(strpos($v['date'], ';timestamp')!==false){
                    $base_date = SUPER_Common::email_tags( $v['date'], $data, $settings );
                    $base_date = $base_date/1000;
                    $scheduled_date = date('Y-m-d', $base_date + $days_offset);
                }else{
                    $base_date = SUPER_Common::email_tags( $v['date'], $data, $settings );
                    $scheduled_date = date('Y-m-d', strtotime($base_date) + $days_offset);
                }
                // Send at a fixed time
                $scheduled_time = date('H:i', time());
                $scheduled_real_date = date('Y-m-d H:i:s', time());
                if($v['method']==='time'){
                    $scheduled_time = SUPER_Common::email_tags($v['time'], $data, $settings);
                    // Test if time was set to 24 hour format
                    if(!preg_match("#([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1}):[0-5]{1}[0-9]{1}#", $scheduled_time)){
                        SUPER_Common::output_message( array(
                            'msg' => $scheduled_time . esc_html__( 'is not a valid 24-hour clock format, please correct and make sure to use a 24-hour format e.g: 21:45', 'super-forms' ),
                            'form_id' => $form_id
                        ));
                    }
                    $scheduled_real_date = date('Y-m-d H:i', strtotime($scheduled_date.' '.$scheduled_time));
                }
                if($v['method']==='offset'){
                    // Send based of form submission + an time offset
                    $base_time = date('H:i', time());
                    // 3600 = 1 hour (60 minutes)
                    $offset = SUPER_Common::email_tags($v['offset'], $data, $settings);
                    error_log('Trigger offset value: ' . $offset);
                    // Convert offset to float, fallback to 0 if conversion fails
                    $offset = is_numeric($offset) ? (float)$offset : 0;
                    if($offset==0){
                        // For immediate sending, use current time including seconds
                        $scheduled_trigger_action_timestamp = time();
                        $scheduled_real_date = date('Y-m-d H:i:s', $scheduled_trigger_action_timestamp);
                        error_log('Immediate sending mode (offset=0)');
                    }else{
                        $time_offset = 3600 * $offset;
                        $scheduled_time = date('H:i', strtotime($base_time) + $time_offset);
                        $dateString = date('Y-m-d H:i', strtotime($scheduled_date.' '.$scheduled_time));
                        $durationInHours = $v['offset'];
                        $durationInSeconds = $time_offset;
                        $dateTime = new DateTime($dateString);
                        $dateTime->modify('+' . $durationInSeconds . ' seconds');
                        $scheduled_real_date = $dateTime->format('Y-m-d H:i:s');
                        $scheduled_trigger_action_timestamp = strtotime($scheduled_real_date);
                    }
                }
                error_log('scheduled_real_date: '.$scheduled_real_date);
                error_log('scheduled_trigger_action_timestamp: '.$scheduled_trigger_action_timestamp);
                error_log('time: '.time());
                if($scheduled_trigger_action_timestamp < time()){
                    // Try to increase by 1 day
                    error_log('Super Forms [ERROR]: automatically increased ' . $scheduled_real_date . ' scheduled date with 1 day because it is in the past.');
                    $scheduled_real_date = date('Y-m-d H:i', strtotime($scheduled_real_date) + 86400);
                    $scheduled_trigger_action_timestamp = strtotime($scheduled_real_date);
                    if($scheduled_trigger_action_timestamp < time()){
                        // Just try to add 1 extra day to the current date
                        error_log('Super Forms [ERROR]: ' . $scheduled_real_date . ' can not be used as a schedule date for trigger '.$triggerName.' (form id: '.$form_id.') because it is in the past, please check your settings under [Triggers] tab on the form builder.');
                        SUPER_Common::output_message( array(
                            'msg' => '<strong>' . $scheduled_real_date . '</strong> can not be used as a schedule date for trigger '.$triggerName.' because it is in the past, please check your settings under [Triggers] tab on the form builder.',
                            'form_id' => $form_id
                        ));

                    }
                }
                // Insert reminder into database
                // Make sure to disabled the schedule so that when the action is called on the scheduled date, it won't re-create a new one and instead actually execute the action
                $action['data']['schedule']['enabled'] = 'false';
                $post = array(
                    'post_title' => $eventName.'->'.$actionName,
                    'post_content' => maybe_serialize($action),
                    'post_type' => 'sf_scheduled_action', // max 20 characters long: varchar(20)
                    'post_status' => 'queued', // `queued` = scheduled to be send, `send` = has been sent
                    'post_parent' => $form_id // Keep reference to the form
                );
                error_log('Creating scheduled action post with data: ' . json_encode($post));
                
                // Check if post type exists
                if(!post_type_exists('sf_scheduled_action')) {
                    error_log('ERROR: Post type sf_scheduled_action does not exist!');
                    // Register the post type if it doesn't exist
                    register_post_type('sf_scheduled_action', array(
                        'public' => false,
                        'label'  => 'Scheduled Actions'
                    ));
                    error_log('Registered sf_scheduled_action post type');
                }
                
                $scheduled_trigger_action_id = wp_insert_post($post);         
                error_log('Created scheduled action post with ID: ' . $scheduled_trigger_action_id);
                
                // Verify the post was created
                $created_post = get_post($scheduled_trigger_action_id);
                if($created_post) {
                    error_log('Verified post exists with status: ' . $created_post->post_status);
                } else {
                    error_log('ERROR: Could not verify post exists after creation!');
                }
                if(is_wp_error($scheduled_trigger_action_id)){
                    $errors = $scheduled_trigger_action_id->get_error_messages();
                    foreach($errors as $error){
                        error_log('Super Forms [ERROR]: unable to create scheduled trigger action '.$triggerName.' (form id: '.$form_id.'), '.$error);
                        SUPER_Common::output_message( array(
                            'msg' => 'Unable to create scheduled trigger action '.$triggerName.', '.$error,
                            'form_id' => $form_id
                        ));
                    }
                }
                // Save the timestamp for this reminder, we will use this to check when to send the reminder
                $meta_result = add_post_meta($scheduled_trigger_action_id, '_super_scheduled_trigger_action_timestamp', $scheduled_trigger_action_timestamp);
                error_log('Added timestamp meta with result: ' . ($meta_result ? 'true' : 'false'));
                // Save the action options (settings)
                // Save all submission data post meta for this reminder
                unset($sfsi['post']);
                unset($sfsi['settings']); // for scheduled actions we will grab the settings based on the form ID when executed
                $triggerEventParameters = array(
                    'form_id'=>$form_id,
                    'eventName'=>$eventName,  // e.g. 'sf.after.submission'
                    'triggerName'=>$triggerName,  // e.g. 'E-mail reminder #2'
                    'actionName'=>$actionName, // e.g. 'send_email'
                    'order'=>$action['order'], // e.g. 'send_email'
                    'sfsi'=>$sfsi
                );
                add_post_meta($scheduled_trigger_action_id, '_super_scheduled_trigger_action_data', $triggerEventParameters);
                //error_log('trigger action '.$actionName.' has been scheduled for '.$scheduled_real_date);
            }
            return true;
        }

        $loops = self::retrieve_email_loop_html($data, $settings, $options);
        //error_log('retrieve_email_loop_html()');
        //error_log(json_encode($loops));
        $email_loop = $options['loop_open'].$loops['email_loop'].$options['loop_close'];
        //error_log($email_loop);
        $attachments = $loops['attachments'];
        $string_attachments = $loops['string_attachments'];
        $email_body = $options['body'];
        $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
        $email_body = apply_filters( 'super_before_sending_email_body_filter', $email_body, array( 'settings'=>$settings, 'email_loop'=>$email_loop, 'data'=>$data ) );
        $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
        // @since 4.9.5 - RTL email setting
        if(isset($options['rtl']) && $options['rtl']=='true') $email_body = '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';
        $email_body = do_shortcode($email_body);
        $to = SUPER_Common::decode_email_header(SUPER_Common::email_tags($options['to'], $data, $settings));
        $from = SUPER_Common::decode_email_header(SUPER_Common::email_tags($options['from_email'], $data, $settings));
        $from_name = SUPER_Common::decode(SUPER_Common::email_tags($options['from_name'], $data, $settings));
        $cc = SUPER_Common::decode_email_header(SUPER_Common::email_tags($options['cc'], $data, $settings));
        $bcc = SUPER_Common::decode_email_header(SUPER_Common::email_tags($options['bcc'], $data, $settings));
        $subject = SUPER_Common::decode(SUPER_Common::email_tags($options['subject'], $data, $settings));
        $email_params = array(
            'to'=>$to,
            'from'=>$from,
            'from_name'=>$from_name,
            'cc'=>$cc,
            'bcc'=>$bcc,
            'subject'=>$subject,
            'body'=>$email_body,
            'settings'=>$settings,
            'string_attachments'=>$string_attachments,
            'header_additional'=>SUPER_Common::email_tags($options['header_additional'], $data, $settings),
            'charset'=>SUPER_Common::email_tags($options['charset'], $data, $settings),
            'content_type'=>SUPER_Common::email_tags($options['content_type'], $data, $settings),
        );
        if($options['reply_to']['enabled']==='true'){
            $email_params['custom_reply'] = true;
            $email_params['reply'] = SUPER_Common::decode_email_header(SUPER_Common::email_tags($options['reply_to']['email'], $data, $settings));
            $email_params['reply_name'] = SUPER_Common::decode(SUPER_Common::email_tags($options['reply_to']['name'], $data, $settings));
        }
        $email_attachments = array();
        if(isset($options['attachments'])) $email_attachments = explode( ',', $options['attachments'] );
        foreach($email_attachments as $k => $v){
            $file = get_attached_file($v);
            if( $file ) {
                $url = wp_get_attachment_url($v);
                $filename = basename ( $file );
                $attachments[$filename] = $url;
            }
        }
        $attachments = apply_filters( 'super_before_sending_email_attachments_filter', $attachments, array( 'atts'=>$x, 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body ) );
        $email_params['attachments'] = $attachments;
        // Send the email
        $mail = SUPER_Common::email( $email_params );
        // Return error message
        if(!empty($mail->ErrorInfo)){
            $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
            SUPER_Common::output_message( array( 
                'msg' => $msg,
                'form_id' => absint($form_id)
            ));
        }else{
            // If this was triggered based of a scheduled action, then delete it
            if(!empty($scheduled_action_id)) wp_delete_post($scheduled_action_id, true);
        }
    }

    public static function retrieve_email_loop_html($data, $settings, $options){
        if($options['exclude']['enabled']==='true'){
            foreach($options['exclude']['exclude_fields'] as $v){
                if(isset($data[$v['name']])){
                    unset($data[$v['name']]);
                }
            }
        }
        $loop = $options['loop'];
        $email_loop = '';
        $attachments = array();
        $string_attachments = array();
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            foreach( $data as $k => $v ) {
                // Skip excluded fields

                // Skip dynamic data
                if($k=='_super_dynamic_data') continue;
                $row = $loop;
                if( !isset( $v['exclude'] ) ) {
                    $v['exclude'] = 0;
                }
                if( $v['exclude']==2 ) {
                    // Exclude from all emails
                    continue;
                }
                $result = apply_filters( 'super_before_email_loop_data_filter', $row, array( 'type'=>'admin', 'v'=>$v, 'string_attachments'=>$string_attachments  ) );
                $continue = false;
                if( isset( $result['status'] ) ) {
                    if( $result['status']=='continue' ) {
                        if( isset( $result['string_attachments'] ) ) {
                            $string_attachments = $result['string_attachments'];
                        }
                        if( ( isset( $result['exclude'] ) ) && ( $result['exclude']==3 ) ) {
                        }else{
                            $email_loop .= $result['row'];
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
                        }else{
                            $row = str_replace( '{loop_label}', '', $row );
                        }
                        $files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
                    }else{
                        $v['value'] = '-';
                        foreach( $v['files'] as $key => $value ) {
                            // Check if user explicitely wants to remove files from {loop_fields} in emails
                            if(!empty($settings['file_upload_remove_from_email_loop'])) {
                                // Remove this row completely
                                $row = ''; 
                            }else{
                                if( $key==0 ) {
                                    if( !empty( $v['label'] ) ) {
                                        $v['label'] = str_replace('%d', '', $v['label']);
                                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                    }else{
                                        $row = str_replace( '{loop_label}', '', $row );
                                    }
                                }
                                // In case the file was deleted we do not want to add a hyperlink that links to the file
                                // In case the user explicitely choose to remove the hyperlink
                                if( !empty($settings['file_upload_submission_delete']) || 
                                    !empty($settings['file_upload_remove_hyperlink_in_emails']) ) {
                                    $files_value .= $value['value'] . '<br />';
                                }else{
                                    if($k==='_vcard' && !empty($settings['vcard_delete']) && $settings['vcard_delete']==='true'){
                                        $files_value .= $value['value'] . '<br />';
                                    }else{
                                        $files_value .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html($value['value']) . '</a><br /><br />';
                                    }
                                }
                                if( !empty($settings['file_upload_submission_delete']) ) {
                                    $files_value_listing .= $value['value'] . '<br />';
                                }else{
                                    if($k==='_vcard' && !empty($settings['vcard_delete']) && $settings['vcard_delete']==='true'){
                                        $files_value_listing .= $value['value'] . '<br />';
                                    }else{
                                        $files_value_listing .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html($value['value']) . '</a><br />';
                                    }
                                }
                            }
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
                                if( $v['exclude']==1 ) {
                                    $attachments[$value['value']] = $fileValue;
                                }else{
                                    // 3 = Exclude from admin email
                                    if( $v['exclude']==3 ) {
                                    }else{
                                        // Do not exclude
                                        $attachments[$value['value']] = $fileValue;
                                    }
                                }
                            }
                        }
                    }
                    $row = str_replace( '{loop_value}', $files_value, $row );
                }else{
                    if( isset($v['type']) && (($v['type']=='form_id') || ($v['type']=='entry_id')) ) {
                        $row = '';
                    }else{
                        if( !empty( $v['label'] ) ) {
                            $v['label'] = str_replace('%d', '', $v['label']);
                            $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                        }else{
                            $row = str_replace( '{loop_label}', '', $row );
                        }
                        // @since 1.2.7
                        if( isset( $v['admin_value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $row );
                        }
                        if( isset( $v['value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $row );
                        }

                    }
                }
                if( $v['exclude']==3 || ($options['exclude_empty']==='true' && (empty($v['value']) || $v['value']=='0') )) {
                    // Exclude from admin email loop
                }else{
                    $email_loop .= $row;
                }
            }
        }
        return array(
            'email_loop' => $email_loop,
            'attachments' => $attachments,
            'string_attachments' => $string_attachments,
        );
    }
}
endif;
