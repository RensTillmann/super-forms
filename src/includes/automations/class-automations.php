<?php
/**
 * Super Forms Triggers Class.
 *
 * @author      WebRehab
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Automations
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automations' ) ) :

	/**
	 * SUPER_Automations
	 */
	class SUPER_Automations {


		public static function execute_scheduled_automation_actions() {
			error_log( 'execute_scheduled_automation_actions() started' );
			// Retrieve reminders from database based on post_meta named `_super_reminder_timestamp` based on the timestamp we can determine if we need to send the reminder yet
			global $wpdb;
			$current_timestamp = strtotime( date( 'Y-m-d H:i', time() ) );
			error_log( 'Current timestamp for checking scheduled actions: ' . $current_timestamp );
			$query = "SELECT post_id, meta_value AS timestamp, post_content,
        (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_super_scheduled_automation_action_data' AND r.post_id = post_id) AS automationEventParameters
        FROM $wpdb->postmeta AS r INNER JOIN $wpdb->posts ON ID = post_id
        WHERE meta_key = '_super_scheduled_automation_action_timestamp' AND meta_value < %d";
			error_log( 'Executing query: ' . $wpdb->prepare( $query, $current_timestamp ) );
			$scheduled_actions = $wpdb->get_results( $wpdb->prepare( $query, $current_timestamp ) );
			error_log( 'Found ' . count( $scheduled_actions ) . ' scheduled actions to process' );
			foreach ( $scheduled_actions as $k => $v ) {
				$scheduled_action_id = $v->post_id;
				error_log( 'Processing scheduled action ID: ' . $scheduled_action_id );
				$automation_options = maybe_unserialize( $v->post_content );
				error_log( 'automation_options: ' . json_encode( $automation_options ) );
				$automationEventParameters                        = maybe_unserialize( $v->automationEventParameters );
				$automationEventParameters['action']              = $automation_options;
				$automationEventParameters['scheduled_action_id'] = $scheduled_action_id;
				error_log( 'automationEventParameters: ' . json_encode( $automationEventParameters ) );
				// Check if automation function (action) exists e.g. send_email()
				if ( method_exists( 'SUPER_Automations', $automationEventParameters['actionName'] ) ) {
					error_log( 'Calling action method: ' . $automationEventParameters['actionName'] );
					call_user_func( array( 'SUPER_Automations', $automation_options['action'] ), $automationEventParameters );
				} else {
					error_log( 'Automation event `' . $automationEventParameters['triggerName'] . '` tried to call an action named `' . $automation_options['action'] . "` but such action doesn't exist" );
				}
			}
			error_log( 'execute_scheduled_automation_actions() completed' );
		}

		public static function update_contact_entry_status( $x ) {
			error_log( 'update_contact_entry_status()' );
			extract( $x );
			extract( $sfsi );
			// Check if we need to grab the settings
			if ( ! isset( $settings ) ) {
				$settings = SUPER_Common::get_form_settings( $form_id );
			}
			error_log( 'Automation action updated status of Contact Entry #' . $entry_id . ' to: ' . $action['data']['status'] );
			update_post_meta( $entry_id, '_super_contact_entry_status', $action['data']['status'] );
		}
		public static function update_created_post_status( $x ) {
			error_log( 'update_created_post_status()' );
			extract( $x );
			extract( $sfsi );
			// Check if we need to grab the settings
			if ( ! isset( $settings ) ) {
				$settings = SUPER_Common::get_form_settings( $form_id );
			}
			error_log( '$action[data][status]: ' . $action['data']['status'] );
			$action['data']['status'] = SUPER_Common::email_tags( $action['data']['status'], $data, $settings );
			$isDateValue              = false;
			$status                   = $action['data']['status'];
			error_log( 'status: ' . $status );
			// Check if the status is already a timestamp
			if ( is_numeric( $status ) && (int) $status == $status && $status > 0 ) {
				// Convert timestamp to datetime format
				$timestamp = (int) $status;
				error_log( 'timestamp 1: ' . $timestamp );
				$isDateValue = true;
			} elseif ( strtotime( $status ) ) {
				// Convert date string to timestamp
				$timestamp = strtotime( $status );
				error_log( 'timestamp 2: ' . $timestamp );
				$isDateValue = true;
			} else {
				// Not a valid timestamp or date, exit or log an error
			}
			if ( $isDateValue === true ) {
				// Format the timestamp for WordPress `post_date` and `post_date_gmt`
				$post_date = date( 'Y-m-d H:i:s', $timestamp ); // Local date
				error_log( 'post_date: ' . $post_date );
				$post_date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp ); // GMT date
				error_log( 'post_date_gmt: ' . $post_date_gmt );
				// Update the post with the future status and date
				$update_data = array(
					'ID'            => $created_post,
					'post_date'     => $post_date,
					'post_date_gmt' => $post_date_gmt,
					'post_status'   => 'future',
				);
				// Use WordPress wp_update_post to update the post
				$result = wp_update_post( $update_data );
				// Check for errors
				if ( is_wp_error( $result ) ) {
					error_log( 'Error updating post: ' . $result->get_error_message() );
				} else {
					error_log( 'Automation action updated status of Created Post #' . $created_post . ' to future date: ' . $post_date );
				}
			} else {
				error_log( 'Automation action updated status of Created Post #' . $created_post . ' to: ' . $action['data']['status'] );
				wp_update_post(
					array(
						'ID'          => $created_post,
						'post_status' => $action['data']['status'],
					)
				);
			}
		}
		public static function update_registered_user_login_status( $x ) {
			error_log( 'update_registered_user_login_status()' );
			extract( $x );
			extract( $sfsi );
			// Check if we need to grab the settings
			if ( ! isset( $settings ) ) {
				$settings = SUPER_Common::get_form_settings( $form_id );
			}
			error_log( json_encode( $sfsi ) );
		}
		public static function update_registered_user_role( $x ) {
			error_log( 'update_registered_user_role()' );
			extract( $x );
			extract( $sfsi );
			// Check if we need to grab the settings
			if ( ! isset( $settings ) ) {
				$settings = SUPER_Common::get_form_settings( $form_id );
			}
			error_log( json_encode( $sfsi ) );
		}

		public static function send_email( $x ) {
			extract( $x );
			extract( $sfsi );
			// Check if we need to grab the settings
			if ( ! isset( $settings ) ) {
				$settings = SUPER_Common::get_form_settings( $form_id );
			}
			// Grab action name
			$actionName = $action['action'];

			// Get action options
			$options = $action['data'];
			// Check for translations, and merge
			if ( ! empty( $i18n ) ) {
				$translated_options = ( ( isset( $action['i18n'] ) && is_array( $action['i18n'] ) ) ? $action['i18n'] : array() ); // In case this is a translated version
				if ( isset( $translated_options[ $i18n ] ) ) {
					// Merge any options with translated options
					$options = SUPER_Common::merge_i18n_options( $options, $translated_options[ $i18n ] );
				}
			}
			// Check if this automation action needs to be scheduled
			error_log( 'Checking if automation action needs to be scheduled...' );
			error_log( 'Email body content: ' . print_r( $options['body'], true ) );
			$instant = false;
			if ( $options['schedule']['enabled'] === 'true' ) {
				error_log( 'Schedule is enabled, processing schedules...' );
				$schedules = $options['schedule']['schedules'];
				foreach ( $schedules as $k => $v ) {
					error_log( 'Processing schedule #' . $k );
					if ( $v['method'] === 'offset' ) {
						error_log( 'Schedule method is offset' );
						$offset = SUPER_Common::email_tags( $v['offset'], $data, $settings );
						error_log( 'Raw offset value: ' . $offset );
						$offset = is_numeric( $offset ) ? (float) $offset : 0;
						error_log( 'Parsed offset value: ' . $offset );
						if ( $offset == 0 ) {
							// Extract email sending logic into reusable function
							error_log( 'Offset is 0, sending email immediately' );
							error_log( 'Email body before sending: ' . print_r( $options['body'], true ) );
							$instant = true;
							self::send_automation_email( $data, $settings, $options, $x, $form_id, null );
							continue;
						}
					}
					// Determine the date
					error_log( 'Determining schedule date...' );
					if ( empty( $v['days'] ) ) {
						$v['days'] = 0;
					}
					if ( empty( $v['offset'] ) ) {
						$v['offset'] = 0;
					}
					if ( empty( $v['date'] ) ) {
						$v['date'] = date( 'Y-m-d', time() );
					}
					$v['days'] = SUPER_Common::email_tags( $v['days'], $data, $settings );
					if ( ! is_numeric( $v['days'] ) ) {
						$v['days'] = 0;
					}
					$v['offset'] = SUPER_Common::email_tags( $v['offset'], $data, $settings );
					// 86400 = 1 day (24 hours)
					$days_offset = 86400 * $v['days'];
					error_log( 'Days offset in seconds: ' . $days_offset );

					if ( strpos( $v['date'], ';timestamp' ) !== false ) {
						error_log( 'Using timestamp date format' );
						$base_date = SUPER_Common::email_tags( $v['date'], $data, $settings );

						// Check if tag replacement returned a valid numeric timestamp
						if ( empty( $base_date ) || ! is_numeric( $base_date ) ) {
							// Tag was empty (excluded field) or invalid - skip this schedule
							error_log( 'Warning: timestamp tag replacement returned empty or non-numeric value: "' . $base_date . '" - skipping schedule #' . ( $k + 1 ) );
							continue; // Skip to next schedule
						}

						// Convert milliseconds to seconds (ensure type safety for PHP 8+)
						$base_date      = (int) floor( (float) $base_date / 1000 );
						$scheduled_date = date( 'Y-m-d', $base_date + $days_offset );
					} else {
						error_log( 'Using standard date format' );
						$base_date = SUPER_Common::email_tags( $v['date'], $data, $settings );

						// Check if tag replacement returned a valid date
						if ( empty( $base_date ) ) {
							// Tag was empty (excluded field) - skip this schedule
							error_log( 'Warning: date tag replacement returned empty value - skipping schedule #' . ( $k + 1 ) );
							continue; // Skip to next schedule
						}

						$parsed_timestamp = strtotime( $base_date );
						if ( $parsed_timestamp === false ) {
							// Invalid date format - skip this schedule
							error_log( 'Warning: date tag replacement returned invalid date format: "' . $base_date . '" - skipping schedule #' . ( $k + 1 ) );
							continue; // Skip to next schedule
						}

						$scheduled_date = date( 'Y-m-d', $parsed_timestamp + $days_offset );
					}
					error_log( 'Calculated scheduled_date: ' . $scheduled_date );

					// Send at a fixed time
					$scheduled_time      = date( 'H:i', time() );
					$scheduled_real_date = date( 'Y-m-d H:i:s', time() );
					if ( $v['method'] === 'time' ) {
						error_log( 'Schedule method is fixed time' );
						$scheduled_time = SUPER_Common::email_tags( $v['time'], $data, $settings );
						error_log( 'Fixed scheduled_time: ' . $scheduled_time );
						// Test if time was set to 24 hour format
						if ( ! preg_match( '#([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1}):[0-5]{1}[0-9]{1}#', $scheduled_time ) ) {
							SUPER_Common::output_message(
								array(
									'msg'     => $scheduled_time . esc_html__( 'is not a valid 24-hour clock format, please correct and make sure to use a 24-hour format e.g: 21:45', 'super-forms' ),
									'form_id' => $form_id,
								)
							);
						}
						$scheduled_real_date = date( 'Y-m-d H:i', strtotime( $scheduled_date . ' ' . $scheduled_time ) );
					}
					if ( $v['method'] === 'offset' ) {
						error_log( 'Schedule method is offset time' );
						// Send based of form submission + an time offset
						$base_time = date( 'H:i', time() );
						error_log( 'Base time: ' . $base_time );
						// 3600 = 1 hour (60 minutes)
						$offset = SUPER_Common::email_tags( $v['offset'], $data, $settings );
						error_log( 'Automation offset value: ' . $offset );
						// Convert offset to float, fallback to 0 if conversion fails
						$offset = is_numeric( $offset ) ? (float) $offset : 0;
						if ( $offset == 0 ) {
							// For immediate sending, use current time including seconds
							$scheduled_automation_action_timestamp = time();
							$scheduled_real_date                = date( 'Y-m-d H:i:s', $scheduled_automation_action_timestamp );
							error_log( 'Immediate sending mode (offset=0), scheduled_real_date: ' . $scheduled_real_date );
						} else {
							$time_offset = 3600 * $offset;
							error_log( 'Time offset in seconds: ' . $time_offset );
							$scheduled_time    = date( 'H:i', strtotime( $base_time ) + $time_offset );
							$dateString        = date( 'Y-m-d H:i', strtotime( $scheduled_date . ' ' . $scheduled_time ) );
							$durationInHours   = $v['offset'];
							$durationInSeconds = $time_offset;
							$dateTime          = new DateTime( $dateString );
							$dateTime->modify( '+' . $durationInSeconds . ' seconds' );
							$scheduled_real_date                = $dateTime->format( 'Y-m-d H:i:s' );
							$scheduled_automation_action_timestamp = strtotime( $scheduled_real_date );
							error_log( 'Offset sending mode, scheduled_real_date: ' . $scheduled_real_date );
						}
					}
					error_log( 'Final scheduled_real_date: ' . $scheduled_real_date );
					error_log( 'Final scheduled_automation_action_timestamp: ' . $scheduled_automation_action_timestamp );
					error_log( 'Current time: ' . time() );
					if ( $scheduled_automation_action_timestamp < time() ) {
						// Try to increase by 1 day
						error_log( 'Schedule is in past, adding 1 day' );
						error_log( 'Super Forms [ERROR]: automatically increased ' . $scheduled_real_date . ' scheduled date with 1 day because it is in the past.' );
						$scheduled_real_date                = date( 'Y-m-d H:i', strtotime( $scheduled_real_date ) + 86400 );
						$scheduled_automation_action_timestamp = strtotime( $scheduled_real_date );
						error_log( 'New scheduled_real_date after adding 1 day: ' . $scheduled_real_date );
						if ( $scheduled_automation_action_timestamp < time() ) {
							// Just try to add 1 extra day to the current date
							error_log( 'Schedule still in past after adding 1 day, throwing error' );
							error_log( 'Super Forms [ERROR]: ' . $scheduled_real_date . ' can not be used as a schedule date for automation ' . $triggerName . ' (form id: ' . $form_id . ') because it is in the past, please check your settings under [Triggers] tab on the form builder.' );
							SUPER_Common::output_message(
								array(
									'msg'     => '<strong>' . $scheduled_real_date . '</strong> can not be used as a schedule date for automation ' . $triggerName . ' because it is in the past, please check your settings under [Triggers] tab on the form builder.',
									'form_id' => $form_id,
								)
							);

						}
					}
					// Insert reminder into database
					// Make sure to disabled the schedule so that when the action is called on the scheduled date, it won't re-create a new one and instead actually execute the action
					error_log( 'Creating scheduled action post...' );
					error_log( 'Email body before serialization: ' . print_r( $options['body'], true ) );
					$action['data']['schedule']['enabled'] = 'false';
					$post                                  = array(
						'post_title'   => $eventName . '->' . $actionName,
						'post_content' => maybe_serialize( $action ),
						'post_type'    => 'sf_scheduled_action', // max 20 characters long: varchar(20)
						'post_status'  => 'queued', // `queued` = scheduled to be send, `send` = has been sent
						'post_parent'  => $form_id, // Keep reference to the form
					);
					error_log( 'Creating scheduled action post with data: ' . json_encode( $post ) );

					// Check if post type exists
					if ( ! post_type_exists( 'sf_scheduled_action' ) ) {
						error_log( 'ERROR: Post type sf_scheduled_action does not exist!' );
						// Register the post type if it doesn't exist
						register_post_type(
							'sf_scheduled_action',
							array(
								'public' => false,
								'label'  => 'Scheduled Actions',
							)
						);
						error_log( 'Registered sf_scheduled_action post type' );
					}

					$scheduled_automation_action_id = wp_insert_post( $post );
					error_log( 'Created scheduled action post with ID: ' . $scheduled_automation_action_id );

					// Verify the post was created
					$created_post = get_post( $scheduled_automation_action_id );
					if ( $created_post ) {
						error_log( 'Verified post exists with status: ' . $created_post->post_status );
						error_log( 'Stored email body: ' . print_r( maybe_unserialize( $created_post->post_content ), true ) );
					} else {
						error_log( 'ERROR: Could not verify post exists after creation!' );
					}
					if ( is_wp_error( $scheduled_automation_action_id ) ) {
						$errors = $scheduled_automation_action_id->get_error_messages();
						foreach ( $errors as $error ) {
							error_log( 'Super Forms [ERROR]: unable to create scheduled automation action ' . $triggerName . ' (form id: ' . $form_id . '), ' . $error );
							SUPER_Common::output_message(
								array(
									'msg'     => 'Unable to create scheduled automation action ' . $triggerName . ', ' . $error,
									'form_id' => $form_id,
								)
							);
						}
					}
					// Save the timestamp for this reminder, we will use this to check when to send the reminder
					$meta_result = add_post_meta( $scheduled_automation_action_id, '_super_scheduled_automation_action_timestamp', $scheduled_automation_action_timestamp );
					error_log( 'Added timestamp meta with result: ' . ( $meta_result ? 'true' : 'false' ) );
					// Save the action options (settings)
					// Save all submission data post meta for this reminder
					unset( $sfsi['post'] );
					unset( $sfsi['settings'] ); // for scheduled actions we will grab the settings based on the form ID when executed
					$automationEventParameters = array(
						'form_id'     => $form_id,
						'eventName'   => $eventName,  // e.g. 'sf.after.submission'
						'triggerName' => $triggerName,  // e.g. 'E-mail reminder #2'
						'actionName'  => $actionName, // e.g. 'send_email'
						'order'       => $action['order'], // e.g. 'send_email'
						'sfsi'        => $sfsi,
					);
					add_post_meta( $scheduled_automation_action_id, '_super_scheduled_automation_action_data', $automationEventParameters );
					error_log( 'Added automation event parameters to post meta' );
				}
			}
			if ( $instant === false ) {
				error_log( 'No instant sending required, sending automation email normally' );
				error_log( 'Email body before normal sending: ' . print_r( $options['body'], true ) );
				// Extract email sending logic into reusable function
				self::send_automation_email( $data, $settings, $options, $x, $form_id, $scheduled_action_id );
			}
		}

		public static function send_automation_email( $data, $settings, $options, $x, $form_id, $scheduled_action_id = null ) {
			error_log( 'Starting send_automation_email()' );
			error_log( 'Form ID: ' . $form_id );
			error_log( 'Scheduled action ID: ' . ( $scheduled_action_id ? $scheduled_action_id : 'none' ) );

			error_log( 'Retrieving email loop HTML...' );
			$loops              = self::retrieve_email_loop_html( $data, $settings, $options );
			$email_loop         = $options['loop_open'] . $loops['email_loop'] . $options['loop_close'];
			$attachments        = $loops['attachments'];
			$string_attachments = $loops['string_attachments'];

			error_log( 'Processing email body...' );
			$email_body = $options['body'];
			error_log( 'Original email body: ' . $email_body );
			$email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
			error_log( 'Email body after loop fields replacement: ' . $email_body );
			$email_body = apply_filters(
				'super_before_sending_email_body_filter',
				$email_body,
				array(
					'settings'   => $settings,
					'email_loop' => $email_loop,
					'data'       => $data,
				)
			);
			$email_body = SUPER_Common::email_tags( $email_body, $data, $settings );

			// @since 4.9.5 - RTL email setting
			if ( isset( $options['rtl'] ) && $options['rtl'] == 'true' ) {
				error_log( 'Applying RTL formatting to email body' );
				$email_body = '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';
			}
			$email_body = do_shortcode( $email_body );
			error_log( 'Final email body after all processing: ' . $email_body );

			error_log( 'Processing email headers...' );
			$to        = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $options['to'], $data, $settings ) );
			$from      = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $options['from_email'], $data, $settings ) );
			$from_name = SUPER_Common::decode( SUPER_Common::email_tags( $options['from_name'], $data, $settings ) );
			$cc        = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $options['cc'], $data, $settings ) );
			$bcc       = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $options['bcc'], $data, $settings ) );
			$subject   = SUPER_Common::decode( SUPER_Common::email_tags( $options['subject'], $data, $settings ) );

			error_log( 'Email headers processed:' );
			error_log( 'To: ' . $to );
			error_log( 'From: ' . $from );
			error_log( 'From Name: ' . $from_name );
			error_log( 'Subject: ' . $subject );

			$email_params = array(
				'to'                 => $to,
				'from'               => $from,
				'from_name'          => $from_name,
				'cc'                 => $cc,
				'bcc'                => $bcc,
				'subject'            => $subject,
				'body'               => $email_body,
				'settings'           => $settings,
				'string_attachments' => $string_attachments,
				'header_additional'  => SUPER_Common::email_tags( $options['header_additional'], $data, $settings ),
				'charset'            => SUPER_Common::email_tags( $options['charset'], $data, $settings ),
				'content_type'       => SUPER_Common::email_tags( $options['content_type'], $data, $settings ),
			);

			if ( $options['reply_to']['enabled'] === 'true' ) {
				error_log( 'Setting custom reply-to headers' );
				$email_params['custom_reply'] = true;
				$email_params['reply']        = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $options['reply_to']['email'], $data, $settings ) );
				$email_params['reply_name']   = SUPER_Common::decode( SUPER_Common::email_tags( $options['reply_to']['name'], $data, $settings ) );
				error_log( 'Reply-to: ' . $email_params['reply'] . ' (' . $email_params['reply_name'] . ')' );
			}

			error_log( 'Processing attachments...' );
			$email_attachments = array();
			if ( isset( $options['attachments'] ) && ! empty( $options['attachments'] ) ) {
				$email_attachments = explode( ',', $options['attachments'] );
				error_log( 'Found ' . count( $email_attachments ) . ' attachments to process' );
			}

			foreach ( $email_attachments as $k => $v ) {
				error_log( 'Processing attachment ID: ' . $v );
				$file = get_attached_file( $v );
				if ( $file ) {
					$url                      = wp_get_attachment_url( $v );
					$filename                 = basename( $file );
					$attachments[ $filename ] = $url;
					error_log( 'Added attachment: ' . $filename . ' (' . $url . ')' );
				} else {
					error_log( 'Could not find file for attachment ID: ' . $v );
				}
			}

			error_log( 'Processing attachments through filter...' );
			error_log( 'Current attachments before filter: ' . json_encode( $attachments ) );
			$attachments = apply_filters(
				'super_before_sending_email_attachments_filter',
				$attachments,
				array(
					'options'    => $options,
					'atts'       => $x,
					'settings'   => $settings,
					'data'       => $data,
					'email_body' => $email_body,
				)
			);
			error_log( 'Attachments after filter: ' . json_encode( $attachments ) );
			$email_params['attachments'] = $attachments;
			error_log( 'Added attachments to email parameters' );

			error_log( 'Sending email...' );
			$mail = SUPER_Common::email( $email_params );

			if ( ! empty( $mail->ErrorInfo ) ) {
				error_log( 'Email sending failed with error: ' . $mail->ErrorInfo );
				$msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
				SUPER_Common::output_message(
					array(
						'msg'     => $msg,
						'form_id' => absint( $form_id ),
					)
				);
			} else {
				error_log( 'Email sent successfully' );
				if ( ! empty( $scheduled_action_id ) ) {
					error_log( 'Deleting scheduled action post: ' . $scheduled_action_id );
					wp_delete_post( $scheduled_action_id, true );
				}
			}
			error_log( 'Completed send_automation_email()' );
		}


		public static function retrieve_email_loop_html( $data, $settings, $options ) {
			if ( $options['exclude']['enabled'] === 'true' ) {
				foreach ( $options['exclude']['exclude_fields'] as $v ) {
					if ( isset( $data[ $v['name'] ] ) ) {
						unset( $data[ $v['name'] ] );
					}
				}
			}
			$loop               = $options['loop'];
			$email_loop         = '';
			$attachments        = array();
			$string_attachments = array();
			if ( ( isset( $data ) ) && ( count( $data ) > 0 ) ) {
				foreach ( $data as $k => $v ) {
					// Skip excluded fields

					// Skip dynamic data
					if ( $k == '_super_dynamic_data' ) {
						continue;
					}
					$row = $loop;
					if ( ! isset( $v['exclude'] ) ) {
						$v['exclude'] = 0;
					}
					if ( $v['exclude'] == 2 ) {
						// Exclude from all emails
						continue;
					}
					$result   = apply_filters(
						'super_before_email_loop_data_filter',
						$row,
						array(
							'type'               => 'admin',
							'v'                  => $v,
							'string_attachments' => $string_attachments,
						)
					);
					$continue = false;
					if ( isset( $result['status'] ) ) {
						if ( $result['status'] == 'continue' ) {
							if ( isset( $result['string_attachments'] ) ) {
								$string_attachments = $result['string_attachments'];
							}
							if ( ( isset( $result['exclude'] ) ) && ( $result['exclude'] == 3 ) ) {
							} else {
								$email_loop .= $result['row'];
							}
							$continue = true;
						}
					}
					if ( $continue ) {
						continue;
					}

					if ( isset( $v['type'] ) && $v['type'] == 'files' ) {
						$files_value         = '';
						$files_value_listing = '';
						if ( ( ! isset( $v['files'] ) ) || ( count( $v['files'] ) == 0 ) ) {
							$v['value'] = '';
							if ( ! empty( $v['label'] ) ) {
								// Replace %d with empty string if exists
								$v['label'] = str_replace( '%d', '', $v['label'] );
								$row        = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
							} else {
								$row = str_replace( '{loop_label}', '', $row );
							}
							$files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
						} else {
							$v['value'] = '-';
							foreach ( $v['files'] as $key => $value ) {
								// Check if user explicitely wants to remove files from {loop_fields} in emails
								if ( ! empty( $settings['file_upload_remove_from_email_loop'] ) ) {
									// Remove this row completely
									$row = '';
								} else {
									if ( $key == 0 ) {
										if ( ! empty( $v['label'] ) ) {
											$v['label'] = str_replace( '%d', '', $v['label'] );
											$row        = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
										} else {
											$row = str_replace( '{loop_label}', '', $row );
										}
									}
									// In case the file was deleted we do not want to add a hyperlink that links to the file
									// In case the user explicitely choose to remove the hyperlink
									if ( ! empty( $settings['file_upload_submission_delete'] ) ||
									! empty( $settings['file_upload_remove_hyperlink_in_emails'] ) ) {
										$files_value .= $value['value'] . '<br />';
									} elseif ( $k === '_vcard' && ! empty( $settings['vcard_delete'] ) && $settings['vcard_delete'] === 'true' ) {
											$files_value .= $value['value'] . '<br />';
									} else {
										$files_value .= '<a href="' . esc_url( $value['url'] ) . '" target="_blank">' . esc_html( $value['value'] ) . '</a><br /><br />';
									}
									if ( ! empty( $settings['file_upload_submission_delete'] ) ) {
										$files_value_listing .= $value['value'] . '<br />';
									} elseif ( $k === '_vcard' && ! empty( $settings['vcard_delete'] ) && $settings['vcard_delete'] === 'true' ) {
											$files_value_listing .= $value['value'] . '<br />';
									} else {
										$files_value_listing .= '<a href="' . esc_url( $value['url'] ) . '" target="_blank">' . esc_html( $value['value'] ) . '</a><br />';
									}
								}
								if ( $v['exclude'] != 2 ) {
									// Get either URL or Secure file path
									$fileValue = '';
									if ( ! empty( $value['attachment'] ) ) {
										$fileValue = $value['url'];
									} else {
										// See if this was a secure file upload
										if ( ! empty( $value['path'] ) ) {
											$fileValue = wp_normalize_path( trailingslashit( $value['path'] ) . $value['value'] );
										}
										if ( ! empty( $value['subdir'] ) ) {
											$fileValue = $value['subdir'];
										}
									}
									if ( $v['exclude'] == 1 ) {
										$attachments[ $value['value'] ] = $fileValue;
									} else {
										// 3 = Exclude from admin email
										if ( $v['exclude'] == 3 ) {
										} else {
											// Do not exclude
											$attachments[ $value['value'] ] = $fileValue;
										}
									}
								}
							}
						}
						$row = str_replace( '{loop_value}', $files_value, $row );
					} elseif ( isset( $v['type'] ) && ( ( $v['type'] == 'form_id' ) || ( $v['type'] == 'entry_id' ) ) ) {
							$row = '';
					} else {
						if ( ! empty( $v['label'] ) ) {
							$v['label'] = str_replace( '%d', '', $v['label'] );
							$row        = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
						} else {
							$row = str_replace( '{loop_label}', '', $row );
						}
						// @since 1.2.7
						if ( isset( $v['admin_value'] ) ) {
							// @since 3.9.0 - replace comma's with HTML
							if ( ! empty( $v['replace_commas'] ) ) {
								$v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
							}
							$row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $row );
						}
						if ( isset( $v['value'] ) ) {
							// @since 3.9.0 - replace comma's with HTML
							if ( ! empty( $v['replace_commas'] ) ) {
								$v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
							}
							$row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $row );
						}
					}
					if ( $v['exclude'] == 3 || ( $options['exclude_empty'] === 'true' && ( empty( $v['value'] ) || $v['value'] == '0' ) ) ) {
						// Exclude from admin email loop
					} else {
						$email_loop .= $row;
					}
				}
			}
			return array(
				'email_loop'         => $email_loop,
				'attachments'        => $attachments,
				'string_attachments' => $string_attachments,
			);
		}
	}
endif;
