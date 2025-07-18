<?php
/**
 * Class for handling Ajax requests
 *
 * @author      WebRehab
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Ajax
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Ajax' ) ) :

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
				// 'example'                     => true,
				'new_version_check'             => false,
				'mark_unread'                   => false,
				'mark_read'                     => false,
				'delete_contact_entry'          => false,
				'save_settings'                 => false,
				'get_element_builder_html'      => false,
				'load_element_settings'         => false,
				'save_form'                     => false,
				'delete_form'                   => false,
				'load_preview'                  => false,
				'switch_language'               => false, // @since 4.7.0

				'retrieve_variable_conditions'  => true,
				'create_nonce'                  => true,
				'upload_files'                  => true,
				'submit_form'                   => true,
				'save_form_progress'            => true,  // @since 3.2.0
				'language_switcher'             => true,  // @since 4.7.0

				'load_default_settings'         => false,
				'import_global_settings'        => false,
				'export_entries'                => false, // @since 1.1.9
				'prepare_contact_entry_import'  => false, // @since 1.2.6
				'import_contact_entries'        => false, // @since 1.2.6

				'demos_install_item'            => false, // @since 1.2.8

				'get_entry_export_columns'      => false, // @since 1.7
				'export_selected_entries'       => false, // @since 1.7
				'update_contact_entry'          => false, // @since 1.7

				'export_forms'                  => false, // @since 1.9
				'start_forms_import'            => false, // @since 1.9

				'populate_form_data'            => true,  // @since 2.2.0
				'search_wc_orders'              => true,

				'restore_backup'                => false, // @since 3.1.0
				'delete_backups'                => false, // @since 3.1.0

				'bulk_edit_entries'             => false, // @since 3.4.0
				'reset_submission_counter'      => false, // @since 3.4.0

				'undo_redo'                     => false, // @since 3.8.0

				'reset_user_submission_counter' => false, // @since 3.8.0

				'print_custom_html'             => true, // @since 3.9.0

				'export_single_form'            => false, // @since 4.0.0
				'import_single_form'            => false, // @since 4.0.0
				'reset_form_settings'           => false, // @since 4.0.0
				'tutorial_do_not_show_again'    => false, // @since 4.0.0

				'update_unique_code'            => true, // @since 4.9.46

				'api_cancel_subscription'       => false,
				'api_transfer_license'          => false,
				'api_start_trial'               => false,
				'api_checkout'                  => false,
				'api_register_user'             => false,
				'api_login_user'                => false,
				'api_send_reset_password_email' => false,
				'api_reset_password'            => false,
				'api_logout_user'               => false,
				'api_verify_code'               => false,
				'api_auth'                      => false,
				'api_submit_feedback'           => false,

				'listings_view_entry'           => true,
				'listings_edit_entry'           => true,
				'listings_delete_entry'         => false,

				'ui_i18n_reload_attachments'    => false, // used for triggers to reload the correct attachment image when switching between translations

				'send_test_email'               => false, // Send test email from form builder

			);
			foreach ( $ajax_events as $ajax_event => $nopriv ) {
				add_action( 'wp_ajax_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				}
			}
		}

		public static function new_version_check() {
			if ( ( $_POST['modifiedTime'] + 10 ) < get_post_modified_time( 'U', false, $_POST['form_id'] ) ) {
				echo 'true'; // there is a newer version
			} else {
				echo 'false'; // this is the latest version
			}
			die();
		}

		public static function ui_i18n_reload_attachments() {
			$fileIDs = $_POST['fileIDs'];
			$files   = array();
			foreach ( $fileIDs as $id ) {
				$id         = absint( $id );
				$attachment = wp_get_attachment_metadata( $id ); // Get attachment metadata
				$mime_type  = get_post_mime_type( $id );
				$files[]    = array(
					'id'       => $id,
					'url'      => ( substr( $mime_type, 0, 5 ) === 'image' ? wp_get_attachment_url( $id ) : wp_mime_type_icon( $mime_type ) ),
					'editLink' => get_edit_post_link( $id ),
					'filename' => basename( get_attached_file( $id ) ),
				);
			}
			echo SUPER_Common::safe_json_encode( $files );
			die();
		}

		// Used when Ajax lookup is enabled
		public static function retrieve_variable_conditions() {
			// Get form elements
			$elements = get_post_meta( $_POST['form_id'], '_super_elements', true );
			// Get field settings
			$atts = SUPER_Common::get_element_settings( $elements, $_POST['field_name'] );
			// When retrieve method is CSV
			if ( ( ! empty( $atts['conditional_variable_method'] ) ) && ( $atts['conditional_variable_method'] == 'csv' ) ) {
				$delimiter = ',';
				$enclosure = '"';
				if ( ! empty( $atts['conditional_variable_delimiter'] ) ) {
					$delimiter = $atts['conditional_variable_delimiter'];
				}
				if ( ! empty( $atts['conditional_variable_enclosure'] ) ) {
					$enclosure = stripslashes( $atts['conditional_variable_enclosure'] );
				}
				if ( strlen( $delimiter ) !== 1 ) {
					$delimiter = ',';
				}
				if ( strlen( $enclosure ) !== 1 ) {
					$enclosure = '"';
				}
				$file       = get_attached_file( $atts['conditional_variable_csv'] );
				$rows       = array();
				$conditions = array();
				if ( $file ) {
					$row = 0;
					if ( ( ! empty( $file ) ) && ( ( $handle = fopen( $file, 'r' ) ) !== false ) ) {
						// Progress file pointer and get first 3 characters to compare to the BOM string.
						$bom = "\xef\xbb\xbf"; // BOM as a string for comparison.
						if ( fgets( $handle, 4 ) !== $bom ) {
							rewind( $handle ); // BOM not found - rewind pointer to start of file.
						}
						while ( ( $data = fgetcsv( $handle, 10000, $delimiter, $enclosure ) ) !== false ) {
							$rows[] = $data;
						}
						fclose( $handle );
						$columns = $rows[0];
						foreach ( $rows as $k => $v ) {
							if ( $k == 0 ) {
								continue;
							}
							foreach ( $v as $kk => $vv ) {
								if ( $kk == 0 ) {
									continue;
								}
								if ( $atts['conditional_variable_and_method'] === 'and' && $atts['conditional_variable_logic'] === 'equal' && $atts['conditional_variable_logic_and'] === 'equal' ) {
									// A more compact version
									if ( isset( $columns[ $kk ] ) ) {
										$conditions[] = array(
											'field'     => '{' . $atts['conditional_variable_row'] . '}_{' . $atts['conditional_variable_col'] . '}',
											'logic'     => $atts['conditional_variable_logic'],
											'value'     => $v[0] . '_' . $columns[ $kk ],
											'new_value' => $vv,
										);
									} else {
										$conditions[] = array(
											'field'     => $atts['conditional_variable_row'],
											'logic'     => $atts['conditional_variable_logic'],
											'value'     => $v[0],
											'new_value' => $vv,
										);
									}
								} else {
									$conditions[] = array(
										'field'      => $atts['conditional_variable_row'],
										'logic'      => $atts['conditional_variable_logic'], // 'greater_than_or_equal',
										'value'      => $v[0],
										'and_method' => $atts['conditional_variable_and_method'], // 'and',
										'field_and'  => $atts['conditional_variable_col'],
										'logic_and'  => $atts['conditional_variable_logic_and'], // 'greater_than_or_equal',
										'value_and'  => ( isset( $columns[ $kk ] ) ? $columns[ $kk ] : '' ),
										'new_value'  => $vv,
									);
								}
							}
						}
					}
				}
				$atts['conditional_items'] = $conditions;
			}
			if ( $atts['conditional_items'] != null ) {
				// @since 4.2.0 - filter hook to change variable conditions on the fly for specific field
				if ( ! empty( $atts['name'] ) ) {
					$atts['conditional_items'] = apply_filters( 'super_variable_conditions_' . $atts['name'] . '_filter', $atts['conditional_items'], array( 'atts' => $atts ) );
				}
				// @since 2.3.0 - speed improvement for variable field
				// append the field names ad attribute that the conditions being applied to, so we can filter on it on field change with javascript
				$names = array();
				if ( is_array( $atts['conditional_items'] ) ) {
					foreach ( $atts['conditional_items'] as $k => $v ) {
						if ( isset( $v['field'] ) ) {
							$names = SUPER_Common::get_data_fields_attribute(
								array(
									'names' => $names,
									'value' => $v['field'],
									'bwc'   => true,
								)
							);
							$names = SUPER_Common::get_data_fields_attribute(
								array(
									'names' => $names,
									'value' => $v['value'],
								)
							);
						}
						if ( isset( $v['and_method'] ) && isset( $v['field_and'] ) ) {
							$names = SUPER_Common::get_data_fields_attribute(
								array(
									'names' => $names,
									'value' => $v['field_and'],
									'bwc'   => true,
								)
							);
							$names = SUPER_Common::get_data_fields_attribute(
								array(
									'names' => $names,
									'value' => $v['value_and'],
								)
							);
						}
						if ( isset( $v['new_value'] ) ) {
							$names = SUPER_Common::get_data_fields_attribute(
								array(
									'names' => $names,
									'value' => $v['new_value'],
								)
							);
						}
					}
				}

				// @since 1.7 - use json instead of HTML for speed improvements
				$compact = array();
				foreach ( $atts['conditional_items'] as $k => $v ) {
					$compact[ $k ]['l'] = $v['logic'];
					if ( isset( $v['and_method'] ) ) {
						$compact[ $k ]['a'] = $v['and_method'];
					}
					if ( isset( $v['logic_and'] ) ) {
						$compact[ $k ]['la'] = $v['logic_and'];
					}
					if ( isset( $v['new_value'] ) ) {
						$compact[ $k ]['n'] = $v['new_value'];
					}
					// If normalize is enabled
					if ( ! empty( $atts['conditional_variable_normalize'] ) ) {
						if ( $atts['conditional_variable_normalize'] === 'remove_accents' ) {
							// Using WP build in remove_accents() function
							$compact[ $k ]['f'] = remove_accents( $v['field'] );
							$compact[ $k ]['v'] = remove_accents( $v['value'] );
							if ( isset( $v['field_and'] ) ) {
								$compact[ $k ]['fa'] = remove_accents( $v['field_and'] );
							}
							if ( isset( $v['value_and'] ) ) {
								$compact[ $k ]['va'] = remove_accents( $v['value_and'] );
							}
							continue;
						}
						if ( $atts['conditional_variable_normalize'] === 'transliterator' ) {
							// Using translaterator - https://www.php.net/manual/en/transliterator.transliterate.php#111939
							$compact[ $k ]['f'] = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $v['field'] );
							$compact[ $k ]['v'] = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $v['value'] );
							if ( isset( $v['field_and'] ) ) {
								$compact[ $k ]['fa'] = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $v['field_and'] );
							}
							if ( isset( $v['value_and'] ) ) {
								$compact[ $k ]['va'] = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $v['value_and'] );
							}
							continue;
						}
						if ( $atts['conditional_variable_normalize'] === 'iconv' ) {
							// Using iconv() - https://www.php.net/manual/en/function.iconv.php
							$compact[ $k ]['f'] = iconv( 'utf-8', 'ascii//TRANSLIT', $v['field'] );
							$compact[ $k ]['v'] = iconv( 'utf-8', 'ascii//TRANSLIT', $v['value'] );
							if ( isset( $v['field_and'] ) ) {
								$compact[ $k ]['fa'] = iconv( 'utf-8', 'ascii//TRANSLIT', $v['field_and'] );
							}
							if ( isset( $v['value_and'] ) ) {
								$compact[ $k ]['va'] = iconv( 'utf-8', 'ascii//TRANSLIT', $v['value_and'] );
							}
							continue;
						}
					} else {
						$compact[ $k ]['f'] = $v['field'];
						$compact[ $k ]['v'] = $v['value'];
						if ( isset( $v['field_and'] ) ) {
							$compact[ $k ]['fa'] = $v['field_and'];
						}
						if ( isset( $v['value_and'] ) ) {
							$compact[ $k ]['va'] = $v['value_and'];
						}
					}
				}
				echo SUPER_Common::safe_json_encode(
					array(
						'field_name' => $_POST['field_name'],
						'conditions' => $compact,
					)
				);
				die();
			}
			echo SUPER_Common::safe_json_encode(
				array(
					'field_name' => $_POST['field_name'],
					'conditions' => array(),
				)
			);
			die();
		}
		public static function create_nonce() {
			echo SUPER_Common::generate_nonce();
			die();
		}
		public static function listings_view_entry() {
			require_once SUPER_PLUGIN_DIR . '/includes/class-common.php';
			require_once SUPER_PLUGIN_DIR . '/includes/extensions/listings/form-blank-page-template.php';
			die();
		}
		public static function listings_edit_entry() {
			if ( ! empty( $_POST['i18n'] ) ) {
				SUPER_Forms()->i18n = SUPER_Common::get_payload_i18n();
			}
			// error_log('test2: '.SUPER_Forms()->i18n);
			require_once SUPER_PLUGIN_DIR . '/includes/class-common.php';
			require_once SUPER_PLUGIN_DIR . '/includes/extensions/listings/form-blank-page-template.php';
			die();
		}
		public static function listings_delete_entry() {
			// First check if user is logged in
			// Obviously only logged in user are allowed to delete entries
			// Let's check if the user is logged in
			$current_user_id = get_current_user_id();
			if ( $current_user_id == 0 ) {
				// User is not logged in
				echo esc_html__( 'To delete this entry you must be logged in.', 'super-forms' );
				die();
			}

			$entry_id = absint( $_POST['entry_id'] );
			$form_id  = absint( $_POST['form_id'] );
			$list_id  = absint( $_POST['list_id'] );
			$settings = SUPER_Common::get_form_settings( $form_id );
			$lists    = $settings['_listings']['lists'];
			if ( ! isset( $lists[ $list_id ] ) ) {
				// The list does not exist
				echo esc_html__( 'Permission denied, because this list does not exist', 'super-forms' );
				die();
			} else {
				// Check if invalid Entry ID
				if ( ( $entry_id == 0 ) || ( get_post_type( $entry_id ) != 'super_contact_entry' ) ) {
					echo esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id;
				} else {
					// Set default values if they don't exist
					$list = SUPER_Listings::get_default_listings_settings( array( 'list' => $lists[ $list_id ] ) );
				}
			}
			$entry          = get_post( $entry_id );
			$allow          = SUPER_Listings::get_action_permissions(
				array(
					'list'  => $list,
					'entry' => $entry,
				)
			);
			$allowDeleteAny = $allow['allowDeleteAny'];
			$allowDeleteOwn = $allow['allowDeleteOwn'];
			if ( $allowDeleteAny === false && $allowDeleteOwn === false ) {
				// User is not allowed because doesn\'t have proper role to delete entries
				echo esc_html__( 'You do not have permission to delete this entry.', 'super-forms' );
				die();
			}
			if ( $allowDeleteAny ) {
				// Allowed to delete any entry
				if ( $list['delete_any']['permanent'] === 'true' ) {
					wp_delete_post( $entry_id, true );
					echo '1';
					die();
				}
				wp_trash_post( $entry_id );
				echo '1'; // return 1 if successfully deleted entry
				die();
			}
			if ( $allowDeleteOwn === true && absint( $entry->post_author ) === $current_user_id ) {
				// Allowed to delete his own entry
				if ( $list['delete_own']['permanent'] === 'true' ) {
					wp_delete_post( $entry_id, true );
					echo '1';
					die();
				}
				wp_trash_post( $entry_id );
				echo '1'; // return 1 if successfully deleted entry
				die();
			}
			die();
		}

		public static function api_get_auth() {
			if ( isset( $_COOKIE['super_forms'] ) ) {
				return ( $_COOKIE['super_forms'] );
			}
			return array( 'wp_admin' => 'false' );
		}
		public static function api_auth() {
			$auth   = $_POST['auth'];
			$result = setcookie(
				'super_forms[wp_admin]', // name
				$auth, // value
				time() + 60 * 120, // expires after 15 minutes
				'',  // path
				'', // domain
				false, // secure (many WP dashboard might not have valid certificate, or are not forced to https protocol)
				true // httponly
			);
			echo ( $result === true ? 'true' : 'false' );
			die();
		}
		public static function api_submit_feedback() {
			$custom_args = array(
				'body' => ( array(
					'addon_title' => $_POST['addon_title'],
					'feedback'    => $_POST['feedback'],
					'email'       => $_POST['email'],
				) ),
			);
			self::api_do_request( 'feedback/submit', $custom_args );
		}
		public static function api_verify_code() {
			$custom_args = array(
				'body' => ( array(
					'code' => $_POST['code'],
				) ),
			);
			self::api_do_request( 'verify/code', $custom_args );
		}
		public static function api_register_user() {
			$custom_args = array(
				'body' => ( array(
					'email'    => $_POST['email'],
					'password' => $_POST['password'],
				) ),
			);
			self::api_do_request( 'register', $custom_args );
		}
		public static function api_send_reset_password_email() {
			$custom_args = array(
				'body' => ( array(
					'email' => $_POST['email'],
					'data'  => $_POST['data'],
				) ),
			);
			self::api_do_request( 'send_reset_password_email', $custom_args );
		}
		public static function api_reset_password() {
			$custom_args = array(
				'body' => ( array(
					'code'     => $_POST['code'],
					'password' => $_POST['password'],
				) ),
			);
			self::api_do_request( 'reset_password', $custom_args );
		}
		public static function api_login_user() {
			$custom_args = array(
				'body' => ( array(
					'email'    => $_POST['email'],
					'password' => $_POST['password'],
				) ),
			);
			self::api_do_request( 'login', $custom_args );
		}
		public static function api_logout_user() {
			self::api_do_request( 'logout', array() );
		}
		public static function api_transfer_license() {
			$custom_args = array(
				'body' => ( array(
					'domain' => ( isset( $_POST['domain'] ) ? $_POST['domain'] : '' ),
					'slug'   => $_POST['slug'],
					'data'   => $_POST['data'],
				) ),
			);
			self::api_do_request( 'license/transfer', $custom_args );
		}
		public static function api_cancel_subscription() {
			$custom_args = array(
				'body' => ( array(
					'domain' => ( isset( $_POST['domain'] ) ? $_POST['domain'] : '' ),
					'slug'   => $_POST['slug'],
					'data'   => $_POST['data'],
				) ),
			);
			self::api_do_request( 'addons/cancel', $custom_args );
		}
		public static function api_start_trial() {
			$custom_args = array(
				'body' => ( array(
					'slug' => $_POST['slug'],
					'l'    => ( isset( $_POST['l'] ) ? $_POST['l'] : '' ),
					'data' => $_POST['data'],
				) ),
			);
			self::api_do_request( 'addons/start_trial', $custom_args );
		}
		public static function api_checkout() {
			$custom_args = array( 'body' => $_POST );
			self::api_do_request( 'addons/checkout', $custom_args );
		}

		public static function api_do_request( $route, $custom_args, $method = 'echo' ) {
			$args = self::api_default_post_args( $custom_args );
			if ( $route === 'logout' ) {
				setcookie( 'super_forms[wp_admin]', '', time() - 3600 );
			}
			$api_endpoint = ( isset( $_POST['api_endpoint'] ) ? $_POST['api_endpoint'] : SUPER_API_ENDPOINT );
			$r            = wp_remote_post( $api_endpoint . '/' . $route, $args );
			$response     = self::api_handle_response( $r, $args );
			if ( $method == 'return' ) {
				return $response;
			}
			if ( $method == 'echo' ) {
				echo $response;
			}
			die();
		}

		public static function api_default_post_args( $custom_args ) {
			$default_args                = array(
				'method'      => 'POST',
				'timeout'     => 45,
				'data_format' => 'body',
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			);
			$custom_args['body']['auth'] = self::api_get_auth();
			$custom_args['body']         = SUPER_Common::safe_json_encode( $custom_args['body'] );
			return array_merge( $default_args, $custom_args );
		}

		public static function api_handle_response( $r, $args ) {
			$body = '';
			if ( is_wp_error( $r ) ) {
				$err   = $r->get_error_message();
				$body .= '<div class="error notice" style="margin-top:50px;">';
				$body .= '<p>' . esc_html__( 'Unable to load content, please refresh the page, or try again later.', 'super-forms' ) . '</p>';
				$body .= '<p>Error returned by server:</p><textarea style="width:100%;height:50px;">' . $err . '</textarea>';
				$body .= '</div>';
			} else {
				// Just an API error/notice/success message or HTML payload
				$body .= $r['body'];
			}
			return $body;
		}

		// @since 4.9.46
		// Update unique code when browser "Back" button was pressed
		// Otherwise some browsers might retain the previously generated code
		// Which causes duplicated (none unique) codes
		public static function update_unique_code() {
			$submittingForm = ( $_POST['submittingForm'] === 'true' ? true : false );
			$codesettings   = wp_unslash( $_POST['codesettings'] );
			$codesettings   = json_decode( $codesettings, true );
			echo SUPER_Common::generate_random_code( $codesettings, $submittingForm );
			die();
		}

		/**
		 *  Switch language from Front-end, reloads all form elements for choosen langauge
		 *
		 *  @since      4.7.0
		 */
		public static function language_switcher() {
			$atts = array(
				'id'         => absint( $_POST['form_id'] ),
				'i18n'       => SUPER_Common::get_payload_i18n(),
				'parameters' => ( isset( $_POST['parameters'] ) ? $_POST['parameters'] : array() ),
			);
			// Check if languages are used
			$verified = true;
			$settings = SUPER_Common::get_form_settings( $atts['id'], true );
			if ( empty( $settings['i18n_switch'] ) ) {
				$settings['i18n_switch'] = 'false';
			}
			if ( $settings['i18n_switch'] !== 'true' ) {
				// No need to do this, return message
				$verified = false;
			}
			$translations = SUPER_Common::get_form_translations( $atts['id'] );
			if ( ( ! is_array( $translations ) ) || ( ( is_array( $translations ) ) && ( count( $translations ) < 2 ) ) ) {
				// No need to do this, return message
				$verified = false;
			}
			// tmp $csrfValidation = SUPER_Common::verifyCSRF();
			// tmp if(!$csrfValidation){
			// tmp     // Only check when not disabled by the user.
			// tmp     // Some users want to use/load their forms via an iframe from a different domain name
			// tmp     // In this case sessions won't work  because of browsers "SameSite by default cookies"
			// tmp     $global_settings = SUPER_Common::get_global_settings();
			// tmp     if(!empty($global_settings['csrf_check']) && $global_settings['csrf_check']==='false'){
			// tmp         // Check was disabled by the user, skip it
			// tmp     }else{
			// tmp         // Return error
			// tmp         $verified = false;
			// tmp     }
			// tmp }
			if ( $verified === false ) {
				SUPER_Common::output_message(
					array(
						'msg' => esc_html__( 'Unable to switch language, session expired!', 'super-forms' ),
					)
				);
			}
			// @since 4.7.0 - translation RTL
			// check if the translation has enable RTL mode
			$rtl = false;
			if ( ! empty( $translations[ $atts['i18n'] ] ) && ! empty( $translations[ $atts['i18n'] ]['rtl'] ) ) {
				if ( $translations[ $atts['i18n'] ]['rtl'] == 'true' ) {
					$rtl = true;
				}
			}
			// This will grab only the elements of the form. We can then return it and add it inside the <form> tag
			$data = array(
				'html' => SUPER_Shortcodes::super_form_func( $atts, true ),
				'rtl'  => $rtl,
			);
			echo SUPER_Common::safe_json_encode( $data );
			die();
		}


		/**
		 *  Switch from builder to language mode in Back-end
		 *  This will reload all form elements and also reload form settings
		 *
		 *  @since      4.7.0
		 */
		public static function switch_language() {
			$form_id = absint( $_POST['form_id'] );
			// Retrieve all settings with the correct default values
			$settings = SUPER_Common::get_form_settings( $form_id );

			// @since 4.7.0 - translation
			if ( ! empty( $_POST['i18n'] ) ) {
				$i18n = SUPER_Common::get_payload_i18n();
				if ( ( ! empty( $settings['i18n'] ) ) && ( ! empty( $settings['i18n'][ $i18n ] ) ) ) {
					$settings = array_replace_recursive( $settings, $settings['i18n'][ $i18n ] );
				}
			}

			$fields        = SUPER_Settings::fields( $settings );
			$settings_html = '';

			$settings_html .= '<div class="super-form-settings-tabs">';
			$settings_html .= '<select>';
			$i              = 0;
			foreach ( $fields as $key => $value ) {
				if ( ( ( ! isset( $value['hidden'] ) ) || ( $value['hidden'] == false ) || ( $value['hidden'] === 'settings' ) ) && ( ! empty( $value['name'] ) ) ) {
					$settings_html .= '<option value="' . $i . '" ' . ( $i == 0 ? 'selected="selected"' : '' ) . '>' . $value['name'] . '</option>';
					++$i;
				}
			}
			$settings_html .= '</select>';
			$settings_html .= SUPER_Common::reset_setting_icons(
				array(
					'default' => '_reset_',
					'g'       => '_reset_',
					'v'       => '_reset_',
				)
			);
			$settings_html .= '</div>';
			$counter        = 0;

			foreach ( $fields as $key => $value ) {
				if ( ( ( ! isset( $value['hidden'] ) ) || ( $value['hidden'] == false ) || ( $value['hidden'] === 'settings' ) ) && ( ! empty( $value['name'] ) ) ) {
					$settings_html .= '<div class="tab-content ' . ( $counter == 0 ? 'super-active' : '' ) . '">';
					if ( isset( $value['html'] ) ) {
						foreach ( $value['html'] as $v ) {
							$settings_html .= $v;
						}
					}
					if ( isset( $value['fields'] ) ) {
						foreach ( $value['fields'] as $k => $v ) {
							if ( empty( SUPER_Forms()->i18n ) ) {
								if ( ( ! isset( $v['hidden'] ) ) || ( $v['hidden'] == false ) ) {
									$filter      = '';
									$parent      = '';
									$filtervalue = '';
									if ( ( isset( $v['filter'] ) ) && ( $v['filter'] == true ) ) {
										$filter = ' super-filter';
										if ( isset( $v['parent'] ) ) {
											$parent = ' data-parent="' . esc_attr( $v['parent'] ) . '"';
										}
										if ( isset( $v['filter_value'] ) ) {
											$filtervalue = ' data-filtervalue="' . esc_attr( $v['filter_value'] ) . '"';
										}
									}
									$settings_html .= '<div class="super-field' . $filter . '"' . $parent . '' . $filtervalue;
									$settings_html .= '>';
									if ( isset( $v['name'] ) ) {
										$settings_html .= '<div class="super-field-name">' . ( $v['name'] );
										if ( isset( $v['desc'] ) ) {
											$settings_html .= '<i class="info super-tooltip" title="' . esc_attr( $v['desc'] ) . '"></i>';
										}
									}
									if ( isset( $v['label'] ) ) {
										$settings_html .= '<div class="super-field-label">' . nl2br( $v['label'] );
										if ( ! isset( $v['name'] ) && isset( $v['desc'] ) ) {
											$settings_html .= '<i class="info super-tooltip" title="' . esc_attr( $v['desc'] ) . '"></i>';
										}
									}
									if ( isset( $v['label'] ) ) {
										$settings_html .= '</div>';
									}
									if ( isset( $v['name'] ) ) {
										$settings_html .= '</div>';
									}
									$settings_html .= SUPER_Common::docs( $v );
									$settings_html .= '<div class="super-field-input">';
									if ( ! isset( $v['type'] ) ) {
										$v['type'] = 'text';
									}
										$settings_html .= call_user_func( array( 'SUPER_Field_Types', $v['type'] ), $k, $v );
									$settings_html     .= '</div>';
									$settings_html     .= '</div>';
								}
							} elseif ( ( ! isset( $v['hidden'] ) ) || ( $v['hidden'] == false ) ) {
									$filter      = '';
									$parent      = '';
									$filtervalue = '';
								if ( ( isset( $v['filter'] ) ) && ( $v['filter'] == true ) ) {
									$filter = ' super-filter';
									if ( isset( $v['parent'] ) ) {
										$parent = ' data-parent="' . esc_attr( $v['parent'] ) . '"';
									}
									if ( isset( $v['filter_value'] ) ) {
										$filtervalue = ' data-filtervalue="' . esc_attr( $v['filter_value'] ) . '"';
									}
								}
									$invisible = '';
								if ( empty( $v['i18n'] ) ) {
									$invisible = ' super-i18n-hidden';
								}
								if ( ! isset( $v['type'] ) ) {
									$v['type'] = 'text';
								}
									$settings_html .= '<div class="super-field super-field-type-' . $v['type'] . $invisible . $filter . '"' . $parent . '' . $filtervalue;
									$settings_html .= '>';
								if ( isset( $v['name'] ) ) {
									$settings_html .= '<div class="super-field-name">' . ( $v['name'] );
									if ( isset( $v['desc'] ) ) {
										$settings_html .= '<i class="info super-tooltip" title="' . esc_attr( $v['desc'] ) . '"></i>';
									}
								}
								if ( isset( $v['label'] ) ) {
									$settings_html .= '<div class="super-field-label">' . nl2br( $v['label'] );
									if ( ! isset( $v['name'] ) && isset( $v['desc'] ) ) {
										$settings_html .= '<i class="info super-tooltip" title="' . esc_attr( $v['desc'] ) . '"></i>';
									}
								}
								if ( isset( $v['label'] ) ) {
									$settings_html .= '</div>';
								}
								if ( isset( $v['name'] ) ) {
									$settings_html .= '</div>';
								}
									$settings_html .= SUPER_Common::docs( $v );
									$settings_html .= '<div class="super-field-input">';
								if ( ! isset( $v['type'] ) ) {
									$v['type'] = 'text';
								}
										$settings_html .= call_user_func( array( 'SUPER_Field_Types', $v['type'] ), $k, $v );
									$settings_html     .= '</div>';
									$settings_html     .= '</div>';
							}
						}
					}
					$settings_html .= '</div>';
				}
				++$counter;
			}

			// Retrieve all form elements
			$elements      = get_post_meta( $form_id, '_super_elements', true );
			$shortcodes    = SUPER_Shortcodes::shortcodes();
			$elements_html = SUPER_Common::generate_backend_elements( $form_id, $shortcodes, $elements );

			// Return elements and settings
			$data = array(
				'elements' => $elements_html,
				'settings' => $settings_html,
			);
			echo SUPER_Common::safe_json_encode( $data );
			die();
		}


		/**
		 *  Do not show intro tutorial
		 *
		 *  @since      4.0.0
		 */
		public static function tutorial_do_not_show_again() {
			$status = sanitize_text_field( $_POST['status'] );
			if ( $status === 'false' ) {
				$status = 'true';
			} else {
				$status = 'false';
			}
			update_option( 'super_skip_tutorial', $status );
			die();
		}

		/**
		 *  Replace {tags} for custom HTML print buttons
		 *
		 *  @since      3.9.0
		 */
		public static function print_custom_html() {
			$file_id = absint( $_POST['file_id'] );
			$file    = wp_get_attachment_url( $file_id );
			if ( $file ) {
				$html = wp_remote_fopen( $file );
				$data = array();
				if ( isset( $_POST['data'] ) ) {
					$data = $_POST['data'];
				}
				$settings = SUPER_Common::get_form_settings( $data['hidden_form_id']['value'] );
				$html     = SUPER_Forms()->email_if_statements(
					$html,
					array(
						'settings' => $settings,
						'data'     => $data,
					)
				);
				$html     = SUPER_Common::email_tags( $html, $data, $settings );
				echo $html;
			} else {
				echo '404 file with ID #' . $file_id . ' not found!';
			}
			die();
		}


		/**
		 *  Load form elements after Redo/Undo buttons is clicked
		 *
		 *  @since      3.8.0
		 */
		public static function undo_redo() {
			$form_id    = absint( $_POST['form_id'] );
			$elements   = $_POST['elements'];
			$elements   = wp_unslash( $elements );
			$elements   = json_decode( $elements, true );
			$shortcodes = SUPER_Shortcodes::shortcodes();
			$form_html  = SUPER_Common::generate_backend_elements( $form_id, $shortcodes, $elements );
			echo $form_html;
			die();
		}

		/**
		 *  Reset submission counter (locker)
		 *
		 *  @since      3.4.0
		 */
		public static function reset_submission_counter() {
			$form_id = absint( $_POST['form_id'] );
			$counter = absint( $_POST['counter'] );
			if ( $counter == 0 ) {
				delete_post_meta( $form_id, '_super_submission_count' );
			} else {
				update_post_meta( $form_id, '_super_submission_count', $counter );
			}
			die();
		}


		/**
		 *  Reset users submission counter (locker)
		 *
		 *  @since      3.8.0
		 */
		public static function reset_user_submission_counter() {
			$form_id = absint( $_POST['form_id'] );
			delete_post_meta( $form_id, '_super_user_submission_counter' );
			die();
		}


		/**
		 *  Bulk edit contact entry status
		 *
		 *  @since      3.4.0
		 */
		public static function bulk_edit_entries() {
			if ( ( isset( $_POST['entry_status'] ) ) && ( $_POST['entry_status'] != -1 ) ) {
				$post_ids = ( ! empty( $_POST['post_ids'] ) ) ? $_POST['post_ids'] : array();
				if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
					$entry_status = $_POST['entry_status'];
					foreach ( $post_ids as $post_id ) {
						if ( $entry_status == '' ) {
							delete_post_meta( $post_id, '_super_contact_entry_status' );
						} else {
							update_post_meta( $post_id, '_super_contact_entry_status', $entry_status );
						}
					}
				}
			}
			die();
		}


		/**
		 *  Delete all backups
		 *
		 *  @since      3.1.0
		 */
		public static function delete_backups() {
			global $wpdb;
			$form_id = absint( $_POST['form_id'] );

			// Only delete selected backup
			if ( isset( $_POST['backup_id'] ) ) {
				wp_delete_post( absint( $_POST['backup_id'] ), true );
				die();
			}

			// Delete form backups
			$args    = array(
				'post_parent'    => $form_id,
				'post_type'      => 'super_form',
				'post_status'    => 'backup',
				'posts_per_page' => -1, // Make sure all matching backups will be retrieved
			);
			$backups = get_posts( $args );
			if ( is_array( $backups ) && count( $backups ) > 0 ) {
				foreach ( $backups as $v ) {
					wp_delete_post( $v->ID, true );
				}
			}
			die();
		}


		/**
		 *  Restore selected backup
		 *
		 *  @since      3.1.0
		 */
		public static function restore_backup() {
			global $wpdb;
			$form_id = absint( $_POST['form_id'] );

			// Only refresh backup list
			if ( ! isset( $_POST['backup_id'] ) ) {
				$args    = array(
					'post_parent'    => $form_id,
					'post_type'      => 'super_form',
					'post_status'    => 'backup',
					'posts_per_page' => -1, // Make sure all matching backups will be retrieved
				);
				$backups = get_posts( $args );
				if ( count( $backups ) == 0 ) {
					echo '<i>' . esc_html__( 'No backups found...', 'super-forms' ) . '</i>';
				} else {
					$today     = date_i18n( 'd-m-Y' );
					$yesterday = date_i18n( 'd-m-Y', strtotime( $today . ' -1 day' ) );
					echo '<ul>';
					foreach ( $backups as $k => $v ) {
						echo '<li data-id="' . $v->ID . '">';
						echo '<i></i>';
						$date           = date_i18n( 'd-m-Y', strtotime( $v->post_date ) );
						$post_author_id = $v->post_author;
						if ( $today == $date ) {
							$to_time   = strtotime( date_i18n( 'Y-m-d H:i:s' ) );
							$from_time = strtotime( $v->post_date );
							$minutes   = round( abs( $to_time - $from_time ) / 60, 0 );
							echo 'Today @ ' . date_i18n( 'H:i:s', strtotime( $v->post_date ) ) . ' <strong>(' . $minutes . ( $minutes == 1 ? ' minute' : ' minutes' ) . ' ago)</strong>';
						} elseif ( $yesterday == $date ) {
							echo 'Yesterday @ ' . date_i18n( 'H:i:s', strtotime( $v->post_date ) );
						} else {
							echo date_i18n( 'd M Y @ H:i:s', strtotime( $v->post_date ) );
						}
						if ( ! empty( $post_author_id ) ) {
							$user_info = get_userdata( $post_author_id );
							// In case user no longer exists
							if ( $user_info !== false ) {
								echo ' by: <a href="' . esc_url( get_edit_user_link( $user_info->ID ) ) . '">' . $user_info->display_name . '</a>';
							}
						}
						echo '<span>' . esc_html__( 'Restore backup', 'super-forms' ) . '</span></li>';
					}
					echo '</ul>';
				}
				die();
			}
			$form_id   = absint( $_POST['form_id'] );
			$backup_id = absint( $_POST['backup_id'] );

			$elements = get_post_meta( $backup_id, '_super_elements', true );
			if ( ! is_array( $elements ) ) {
				$elements = json_decode( $elements, true );
			}
			foreach ( $elements as $k => $v ) {
				if ( ! empty( $elements[ $k ]['data']['retrieve_method_google_sheet_credentials'] ) ) {
					$elements[ $k ]['data']['retrieve_method_google_sheet_credentials'] = wp_slash( $elements[ $k ]['data']['retrieve_method_google_sheet_credentials'] );
				}
			}
			update_post_meta( $form_id, '_super_elements', $elements );

			$settings = SUPER_Common::get_form_settings( $backup_id );
			update_post_meta( $form_id, '_super_form_settings', $settings );

			$version = get_post_meta( $backup_id, '_super_version', true );
			update_post_meta( $form_id, '_super_version', $version );

			$s = SUPER_Common::get_form_triggers( $backup_id );
			error_log( 'save_form_triggers(2)' );
			SUPER_Common::save_form_triggers( $s, $backup_id );

			$s = SUPER_Common::get_form_woocommerce_settings( $backup_id );
			SUPER_Common::save_form_woocommerce_settings( $s, $backup_id );

			$s = SUPER_Common::get_form_listings_settings( $backup_id );
			error_log( 'save_form_listings_settings($backup_id)' );
			SUPER_Common::save_form_listings_settings( $s, $backup_id );

			$s = SUPER_Common::get_form_pdf_settings( $backup_id );
			SUPER_Common::save_form_pdf_settings( $s, $backup_id );

			$s = SUPER_Common::get_form_stripe_settings( $backup_id );
			SUPER_Common::save_form_stripe_settings( $s, $backup_id );

			// @since 4.7.0 - translations
			$translations = SUPER_Common::get_form_translations( $backup_id );
			update_post_meta( $form_id, '_super_translations', $translations );
			// Required to update modified date
			wp_update_post(
				array(
					'ID'         => $form_id,
					'post_title' => get_the_title( $form_id ),
				)
			);
			die();
		}


		/**
		 *  Search WC orders
		 *
		 *  @since      4.6.0
		 */
		public static function search_wc_orders() {
			$value    = sanitize_text_field( $_POST['value'] );
			$method   = sanitize_text_field( $_POST['method'] );
			$filterby = sanitize_text_field( $_POST['filterby'] );
			if ( empty( $filterby ) ) {
				$filterby = 'ID;_billing_email;_billing_address_1;_billing_postcode;_billing_first_name;_billing_last_name;_billing_company';
			}
			$default_return_label = '[Order #{ID} - {_billing_email}, {_billing_first_name} {_billing_last_name}]';
			if ( ! empty( $_POST['return_label'] ) ) {
				$default_return_label = sanitize_text_field( $_POST['return_label'] );
			}
			$return_value = 'ID;_billing_email;_billing_first_name;_billing_last_name';
			if ( ! empty( $_POST['return_value'] ) ) {
				$return_value = sanitize_text_field( $_POST['return_value'] );
			}
			$populate = sanitize_text_field( $_POST['populate'] );
			$skip     = sanitize_text_field( $_POST['skip'] );
			$query    = "(post_type = 'shop_order') AND (";
			if ( $method == 'equals' ) {
				$query .= "(wc_order.ID LIKE '$value')";
			}
			if ( $method == 'contains' ) {
				$query .= "(wc_order.ID LIKE '%$value%')";
			}
			global $wpdb;
			$filterby = explode( ';', $filterby );
			foreach ( $filterby as $k => $v ) {
				if ( ! empty( $v ) ) {
					if ( $method == 'equals' ) {
						$query .= " OR (meta.meta_key = '" . $v . "' AND meta.meta_value LIKE '$value')";
					}
					if ( $method == 'contains' ) {
						$query .= " OR (meta.meta_key = '" . $v . "' AND meta.meta_value LIKE '%$value%')";
					}
				}
			}
			$query .= ')';
			if ( ! empty( $_POST['status'] ) ) {
				$status = sanitize_text_field( $_POST['status'] );
				$status = explode( ';', $status );
				foreach ( $status as $k => $v ) {
					$status[ $k ] = trim( $v );
				}
				$status = "'" . implode( "','", $status ) . "'";
				$query .= "AND wc_order.post_status IN ($status)";
			}
			$query        = "SELECT wc_order.*
        FROM $wpdb->posts AS wc_order
        INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = wc_order.ID
        WHERE $query
        GROUP BY wc_order.ID
        LIMIT 50";
			$orders       = $wpdb->get_results( $query );
			$regex        = '/\{(.+?)\}/';
			$orders_array = array();
			foreach ( $orders as $k => $v ) {
				$v = (array) $v;
				// Replace all {tags} and build the user label
				$order_label = $default_return_label;
				preg_match_all( $regex, $order_label, $matches, PREG_SET_ORDER, 0 );
				foreach ( $matches as $mk => $mv ) {
					if ( isset( $mv[1] ) && isset( $v[ $mv[1] ] ) ) {
						$order_label = str_replace( '{' . $mv[1] . '}', $v[ $mv[1] ], $order_label );
					} else {
						// Maybe we need to search in user meta data
						$meta_value  = get_post_meta( $v['ID'], $mv[1], true );
						$order_label = str_replace( '{' . $mv[1] . '}', $meta_value, $order_label );
					}
				}
				// Replace all meta_keys and build the user value
				$mk          = explode( ';', $return_value );
				$order_value = array();
				foreach ( $mk as $mv ) {
					if ( isset( $v[ $mv ] ) ) {
						$order_value[] = $v[ $mv ];
					} else {
						// Maybe we need to search in user meta data
						$meta_value    = get_post_meta( $v['ID'], $mv, true );
						$order_value[] = $meta_value;
					}
				}
				$orders_array[] = array(
					'label' => $order_label,
					'value' => implode( ';', $order_value ),
				);
			}
			foreach ( $orders_array as $k => $v ) {
				echo '<li class="super-item" data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . esc_html( $v['label'] ) . '</li>';
			}
			die();
		}

		/**
		 *  Populate form with contact entry data
		 *
		 *  @since      2.2.0
		 */
		public static function populate_form_data() {
			global $wpdb;
			// @since 4.6.0 - check if we are looking up entry data based on a WC order
			if ( isset( $_POST['order_id'] ) ) {
				$order_id = absint( $_POST['order_id'] );
				$skip     = sanitize_text_field( $_POST['skip'] );
				$data     = SUPER_Common::get_entry_data_by_wc_order_id( $order_id, $skip );
				echo SUPER_Common::safe_json_encode( $data );
			} else {
				$value      = sanitize_text_field( $_POST['value'] );
				$method     = sanitize_text_field( $_POST['method'] );
				$table      = $wpdb->prefix . 'posts';
				$table_meta = $wpdb->prefix . 'postmeta';
				if ( $method == 'equals' ) {
					$query = "post_title = BINARY '$value'";
				}
				if ( $method == 'contains' ) {
					$query = "post_title LIKE BINARY '%$value%'";
				}
				$entry = $wpdb->get_results( "SELECT ID FROM $table WHERE $query AND post_status IN ('publish','super_unread','super_read') AND post_type = 'super_contact_entry' LIMIT 1" );
				$data  = array();
				if ( isset( $entry[0] ) ) {
					$data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
					unset( $data['hidden_form_id'] );
					$entry_status = get_post_meta( absint( $entry[0]->ID ), '_super_contact_entry_status', true );
					// If entry status is empty, return the post status instead
					if ( empty( $entry_status ) ) {
						$entry_status = get_post_status( $entry[0]->ID );
					}
					$data['hidden_contact_entry_status'] = array(
						'name'  => 'hidden_contact_entry_status',
						'value' => $entry_status,
						'type'  => 'var',
					);
					$data['hidden_contact_entry_id']     = array(
						'name'  => 'hidden_contact_entry_id',
						'value' => $entry[0]->ID,
						'type'  => 'entry_id',
					);
					$entry_title                         = get_the_title( $entry[0]->ID );
					$data['hidden_contact_entry_title']  = array(
						'name'  => 'hidden_contact_entry_title',
						'value' => $entry_title,
						'type'  => 'var',
					);
					// @since 3.2.0 - skip specific fields from being populated
					$skip        = sanitize_text_field( $_POST['skip'] );
					$skip_fields = explode( '|', $skip );
					foreach ( $skip_fields as $field_name ) {
						if ( isset( $data[ $field_name ] ) ) {
							unset( $data[ $field_name ] );
						}
					}
				}
				echo SUPER_Common::safe_json_encode( $data );
			}
			die();
		}


		/**
		 *  Update contact entry data
		 *
		 *  @since      1.7
		 */
		public static function update_contact_entry() {
			$id       = absint( $_POST['id'] );
			$new_data = $_POST['data'];

			// @since 3.3.0 - update Contact Entry title
			$entry_title = $new_data['super_contact_entry_post_title'];
			unset( $new_data['super_contact_entry_post_title'] );
			$entry = array(
				'ID'         => $id,
				'post_title' => $entry_title,
			);
			wp_update_post( $entry );

			// @since 3.4.0 - update contact entry status
			$entry_status = $_POST['entry_status'];
			update_post_meta( $id, '_super_contact_entry_status', $entry_status );

			$data = get_post_meta( $id, '_super_contact_entry_data', true );
			// If doesn't exist, we don't have to do anything, must be of type Array
			if ( ( $data !== '' ) && ( is_array( $data ) ) ) {
				foreach ( $data as $k => $v ) {
					// Assign new value only if it exists
					if ( isset( $new_data[ $k ] ) ) {
						$data[ $k ]['value'] = $new_data[ $k ];
					}
				}
				update_post_meta( $id, '_super_contact_entry_data', $data );
			}
			SUPER_Common::output_message(
				array(
					'error' => false,
					'msg'   => esc_html__( 'Contact entry updated.', 'super-forms' ),
				)
			);
			die();
		}


		/**
		 *  Export selected entries to CSV
		 *
		 *  @since      1.7
		 */
		public static function export_selected_entries() {
			$columns = $_POST['columns'];
			$query   = $_POST['query'];
			$rows    = array();
			foreach ( $columns as $k => $v ) {
				$rows[0][ $k ] = $v;
			}

			$order_by = 'ASC'; // Default
			if ( isset( $_POST['order_by'] ) ) {
				$order_by = sanitize_text_field( $_POST['order_by'] );
			}
			$order_by_filter = "entry.post_date $order_by";

			global $wpdb;
			$table      = $wpdb->prefix . 'posts';
			$table_meta = $wpdb->prefix . 'postmeta';
			$entries    = $wpdb->get_results(
				"
        SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry' AND entry.ID IN ($query)
        ORDER BY $order_by_filter"
			);

			foreach ( $entries as $k => $v ) {
				$data                               = unserialize( $v->data );
				$data['entry_id']['value']          = $v->ID;
				$data['entry_title']['value']       = $v->post_title;
				$data['entry_date']['value']        = $v->post_date;
				$data['entry_author']['value']      = $v->post_author;
				$data['entry_status']['value']      = $v->post_status;
				$data['entry_ip']['value']          = get_post_meta( $v->ID, '_super_contact_entry_ip', true );
				$data['entry_wc_order_id']['value'] = get_post_meta( $v->ID, '_super_contact_entry_wc_order_id', true );

				// @since 3.4.0 - custom entry status
				$data['entry_custom_status']['value'] = get_post_meta( $v->ID, '_super_contact_entry_status', true );

				$entries[ $k ] = $data;
			}
			// Filter to alter for instance the "entry_date" format from 19:00 to 06:00 Pm
			$entries = apply_filters( 'super_export_selected_entries_filter', $entries );

			foreach ( $entries as $k => $v ) {
				foreach ( $columns as $ck => $cv ) {
					if ( isset( $v[ $ck ] ) ) {
						if ( ( isset( $v[ $ck ]['type'] ) ) && ( $v[ $ck ]['type'] == 'files' ) ) {
							$files = '';
							if ( ( isset( $v[ $ck ]['files'] ) ) && ( count( $v[ $ck ]['files'] ) != 0 ) ) {
								foreach ( $v[ $ck ]['files'] as $fk => $fv ) {
									if ( $fk == 0 ) {
										$files .= $fv['url'];
									} else {
										$files .= PHP_EOL . $fv['url'];
									}
								}
							}
							$rows[ $k + 1 ][] = $files;
						} elseif ( ! isset( $v[ $ck ]['value'] ) ) {
								$rows[ $k + 1 ][] = '';
						} else {
							$rows[ $k + 1 ][] = $v[ $ck ]['value'];
						}
					} else {
						$rows[ $k + 1 ][] = '';
					}
				}
			}
			try {
				$d        = wp_upload_dir();
				$basename = 'super-contact-entries-' . strtotime( date_i18n( 'Y-m-d H:i:s' ) ) . '.csv';
				$filename = trailingslashit( $d['path'] ) . $basename;
				$fp       = fopen( $filename, 'w' );
				// @since 3.1.0 - write file header (byte order mark) for correct encoding to fix UTF-8 in Excel
				$bom = apply_filters( 'super_csv_bom_header_filter', chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
				if ( fwrite( $fp, $bom ) === false ) {
					// Print error message
					SUPER_Common::output_message(
						array(
							'msg' => "Unable to write to file ($filename)",
						)
					);
				}
				$delimiter = wp_unslash( sanitize_text_field( $_POST['delimiter'] ) );
				$enclosure = wp_unslash( sanitize_text_field( $_POST['enclosure'] ) );
				if ( empty( $delimiter ) ) {
					$delimiter = ',';
				}
				if ( empty( $enclosure ) ) {
					$enclosure = '"';
				}
				foreach ( $rows as $fields ) {
					fputcsv( $fp, $fields, $delimiter, $enclosure, PHP_EOL );
				}
				fclose( $fp );
				$attachment    = array(
					'post_mime_type' => 'text/csv',
					'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);
				$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
				$attach_data   = wp_generate_attachment_metadata( $attachment_id, $filename );
				wp_update_attachment_metadata( $attachment_id, $attach_data );
				echo add_query_arg( array( 'sfdlfi' => $attachment_id ), home_url() );
				die();
			} catch ( Exception $e ) {
				// Print error message
				SUPER_Common::output_message(
					array(
						'msg' => $e->getMessage(),
					)
				);
			}
		}


		/**
		 *  Return entry export columns
		 *
		 *  @since      1.7
		 */
		public static function get_entry_export_columns() {
			global $wpdb;

			$global_settings = SUPER_Common::get_global_settings();
			$fields          = explode( "\n", $global_settings['backend_contact_entry_list_fields'] );

			$column_settings = array();
			foreach ( $fields as $k ) {
				$field                        = explode( '|', $k );
				$column_settings[ $field[0] ] = $field[1];
			}

			$entries = $_POST['entries'];
			$query   = '';
			foreach ( $entries as $k => $v ) {
				if ( $k == 0 ) {
					$query .= $v;
				} else {
					$query .= ',' . $v;
				}
			}
			$table      = $wpdb->prefix . 'posts';
			$table_meta = $wpdb->prefix . 'postmeta';
			$results    = $wpdb->get_results(
				"
        SELECT meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry' AND entry.ID IN ($query)"
			);
			$columns    = array();
			$columns[]  = 'entry_id';
			$columns[]  = 'entry_title';
			$columns[]  = 'entry_date';
			$columns[]  = 'entry_author';
			$columns[]  = 'entry_status';
			foreach ( $results as $k => $v ) {
				$data = unserialize( $v->data );
				foreach ( $data as $dk => $dv ) {
					if ( ! in_array( $dk, $columns ) ) {
						$columns[] = $dk;
					}
				}
			}
			$columns[] = 'entry_ip';
			$columns[] = 'entry_wc_order_id';
			echo '<div class="super-contact-entries-export-modal">';
			echo '<span class="button super-export-selected-columns-toggle" style="margin-top:10px;">' . esc_html__( 'Toggle all fields', 'super-forms' ) . '</span>';
			echo '<span class="button button-primary button-large super-export-selected-columns" style="margin: 10px 30px 0px 0px;">' . esc_html__( 'Export', 'super-forms' ) . '</span>';
			echo '<ul class="super-export-entry-columns">';
			foreach ( $columns as $k => $v ) {
				echo '<li class="super-entry-column" data-name="' . esc_attr( $v ) . '">';
				echo '<input type="checkbox"' . ( ( isset( $column_settings[ $v ] ) ) ? ' checked="checked"' : '' ) . ' />';
				echo '<span class="name">' . $v . '</span>';
				echo '<input type="text" value="' . ( ( isset( $column_settings[ $v ] ) ) ? $column_settings[ $v ] : $v ) . '" />';
				echo '<span class="sort"></span>';
				echo '</li>';
			}
			echo '</ul>';
			echo '<input type="hidden" name="query" value="' . $query . '" />';
			echo '<label>' . esc_html__( 'Delimiter', 'super-forms' ) . ': <input type="text" name="delimiter" value="," /></label>';
			echo '<label>' . esc_html__( 'Enclosure', 'super-forms' ) . ': <input type="text" name="enclosure" value="' . esc_attr( '"' ) . '" /></label>';
			echo '<label>' . esc_html__( 'Sort', 'super-forms' ) . ': <select name="order_by"><option value="ASC">' . esc_html__( 'ASC - Oldest first (default)', 'super-forms' ) . '</option><option value="DESC">' . esc_html__( 'DESC - Newest first', 'super-forms' ) . '</option></select></label>';
			echo '<span class="button button-primary button-large super-export-selected-columns" style="margin: 0px 30px 0px 0px;">' . esc_html__( 'Export', 'super-forms' ) . '</span>';
			echo '</div>';
			die();
		}


		/**
		 *  Install demos item
		 *
		 *  @since      1.2.8
		 */
		public static function demos_install_item() {
			$title = $_POST['title'];
			if ( ! empty( $_POST['import'] ) ) {
				$import   = maybe_unserialize( stripslashes( $_POST['import'] ) );
				$settings = $import['settings'];
				$elements = $import['elements'];
			} else {
				$settings = json_decode( stripslashes( $_POST['settings'] ), true );
				$elements = json_decode( stripslashes( $_POST['elements'] ), true );
			}
			$form = array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => 'super_form',
			);
			$id   = wp_insert_post( $form );
			add_post_meta( $id, '_super_form_settings', $settings );
			add_post_meta( $id, '_super_elements', $elements );
			echo $id;
			die();
		}


		/**
		 *  Mark as read/unread
		 *
		 *  @since      1.0.0
		 */
		public static function mark_unread() {
			$my_post = array(
				'ID'          => $_POST['contact_entry'],
				'post_status' => 'super_unread',
			);
			wp_update_post( $my_post );
			die();
		}
		public static function mark_read() {
			$my_post = array(
				'ID'          => $_POST['contact_entry'],
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
			foreach ( $_POST['data'] as $k => $v ) {
				$array[ $v['name'] ] = $v['value'];
			}
			if ( $array['smtp_enabled'] == 'enabled' ) {
				// @since 4.9.551 - WordPress changed the location of PHPMailer apperantly...
				global $wp_version;
				if ( version_compare( $wp_version, '5.5', '<' ) ) {
					require_once ABSPATH . WPINC . '/class-phpmailer.php';
					require_once ABSPATH . WPINC . '/class-smtp.php';
					require_once ABSPATH . WPINC . '/class-pop3.php';
					$phpmailer = new PHPMailer();
				} else {
					require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
					require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
					require_once ABSPATH . WPINC . '/class-pop3.php';
					$phpmailer = new \PHPMailer\PHPMailer\PHPMailer();
				}

				$phpmailer->isSMTP();
				$phpmailer->Host     = $array['smtp_host'];
				$phpmailer->Port     = $array['smtp_port'];
				$phpmailer->Username = $array['smtp_username'];
				$phpmailer->Password = $array['smtp_password'];
				if ( $array['smtp_auth'] == 'enabled' ) {
					$phpmailer->SMTPAuth = true;
				}
				if ( $array['smtp_secure'] != '' ) {
					$phpmailer->SMTPSecure = $array['smtp_secure'];
				}
				try {
					if ( $phpmailer->smtpConnect() ) {
						$phpmailer->smtpClose();
					} else {
						SUPER_Common::output_message(
							array(
								'error' => 'smtp_error',
								'msg'   => esc_html__( 'Invalid SMTP settings!', 'super-forms' ),
							)
						);
						die();
					}
				} catch ( Exception $e ) {
					SUPER_Common::output_message(
						array(
							'error' => 'smtp_error',
							'msg'   => $e->getMessage(),
						)
					);
					die();
				}
			}
			update_option( 'super_settings', $array );
			SUPER_Common::output_message(
				array(
					'error' => false,
					'msg'   => '',
				)
			);
			die();
		}


		/**
		 *  Load the default settings (Settings page)
		 *
		 *  @since      1.0.0
		 */
		public static function load_default_settings() {
			$default_settings = SUPER_Settings::get_defaults();
			update_option( 'super_settings', $default_settings );
			die();
		}


		/**
		 *  Import Contact Entries (from CSV file)
		 *
		 *  @since      1.2.6
		 */
		public static function import_contact_entries() {
			$file_id            = absint( $_POST['file_id'] );
			$column_connections = $_POST['column_connections'];
			$skip_first         = $_POST['skip_first'];
			$delimiter          = ',';
			if ( isset( $_POST['import_delimiter'] ) ) {
				$delimiter = $_POST['import_delimiter'];
			}
			$enclosure = '"';
			if ( isset( $_POST['import_enclosure'] ) ) {
				$enclosure = stripslashes( $_POST['import_enclosure'] );
			}
			$file    = get_attached_file( $file_id );
			$columns = array();
			$entries = array();
			if ( $file ) {
				$row = 0;
				if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
					// Progress file pointer and get first 3 characters to compare to the BOM string.
					$bom = "\xef\xbb\xbf"; // BOM as a string for comparison.
					if ( fgets( $handle, 4 ) !== $bom ) {
						rewind( $handle ); // BOM not found - rewind pointer to start of file.
					}
					while ( ( $data = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
						if ( ( $skip_first == 'true' ) && ( $row == 0 ) ) {
							++$row;
							continue;
						}
						$num = count( $data );
						++$row;
						foreach ( $data as $k => $v ) {
							$column_type  = $column_connections[ $k ]['column'];
							$column_name  = $column_connections[ $k ]['name'];
							$column_label = $column_connections[ $k ]['label'];
							if ( $column_type == 'form_id' ) {
								$column_name                             = 'hidden_form_id';
								$entries[ $row ]['data'][ $column_name ] = array(
									'name'  => $column_name,
									'value' => $v,
									'type'  => $column_type,
								);
								continue;
							} elseif ( $column_type == 'file' ) {
								$files                                   = explode( ',', $v );
								$entries[ $row ]['data'][ $column_name ] = array(
									'name'  => $column_name,
									'label' => $column_label,
									'type'  => 'files',
									'files' => array(),
								);
								foreach ( $files as $k => $v ) {
									$entries[ $row ]['data'][ $column_name ]['files'][ $k ] = array(
										'name'  => $column_name,
										'label' => $column_label,
										'value' => $v,
									);
								}
								continue;
							} else {
								$entries[ $row ]['data'][ $column_name ] = array(
									'name'  => $column_name,
									'label' => $column_label,
									'value' => $v,
									'type'  => $column_type,
								);
								continue;
							}
							$entries[ $row ][ $column_type ] = $v;
						}
					}
					fclose( $handle );
				}
			}

			$global_settings = SUPER_Common::get_global_settings();
			foreach ( $entries as $k => $v ) {
				$data        = $v['data'];
				$post_author = 0;
				if ( isset( $v['post_author'] ) ) {
					$post_author = absint( $v['post_author'] );
				}
				$post_date = 0;
				if ( isset( $v['post_date'] ) ) {
					$post_date = $v['post_date'];
				}
				$ip_address = '';
				if ( isset( $v['ip_address'] ) ) {
					$ip_address = $v['ip_address'];
				}
				$post             = array(
					'post_status' => 'super_unread',
					'post_type'   => 'super_contact_entry',
					'post_author' => $post_author,
					'post_date'   => $post_date,
				);
				$contact_entry_id = wp_insert_post( $post );
				if ( $contact_entry_id != 0 ) {
					add_post_meta( $contact_entry_id, '_super_contact_entry_data', $data );
					add_post_meta( $contact_entry_id, '_super_contact_entry_ip', $ip_address );
					if ( isset( $v['post_title'] ) ) {
						$contact_entry_title = $v['post_title'];
					} else {
						$contact_entry_title = esc_html__( 'Contact entry', 'super-forms' );
					}
					if ( $global_settings['contact_entry_add_id'] == 'true' ) {
						$contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
					}
					$contact_entry = array(
						'ID'         => $contact_entry_id,
						'post_title' => $contact_entry_title,
					);
					wp_update_post( $contact_entry );
					++$imported;
				}
			}

			echo '<div class="message super-success">';
			printf( esc_html__( '%1$d of %2$d contact entries imported!', 'super-forms' ), $imported, count( $entries ) );
			echo '</div>';
			die();
		}


		/**
		 *  Prepare Contact Entries Import (from CSV file)
		 *
		 *  @since      1.2.6
		 */
		public static function prepare_contact_entry_import() {
			$file_id   = absint( $_POST['file_id'] );
			$delimiter = ',';
			if ( isset( $_POST['import_delimiter'] ) ) {
				$delimiter = $_POST['import_delimiter'];
			}
			$enclosure = '"';
			if ( isset( $_POST['import_enclosure'] ) ) {
				$enclosure = stripslashes( $_POST['import_enclosure'] );
			}
			$file    = get_attached_file( $file_id );
			$columns = array();
			if ( $file ) {
				$row = 1;
				if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
					// Progress file pointer and get first 3 characters to compare to the BOM string.
					$bom = "\xef\xbb\xbf"; // BOM as a string for comparison.
					if ( fgets( $handle, 4 ) !== $bom ) {
						rewind( $handle ); // BOM not found - rewind pointer to start of file.
					}
					while ( ( $data = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== false ) {
						$num = count( $data );
						++$row;
						$value = 'undefined';
						$title = 'undefined';
						for ( $c = 0; $c < $num; $c++ ) {
							$columns[] = $data[ $c ];
						}
						break;
					}
					fclose( $handle );
				}
			}
			echo SUPER_Common::safe_json_encode( $columns );
			die();
		}


		/**
		 *  Export single Form
		 *
		 *  @since      4.0.0
		 */
		public static function export_single_form() {
			$form_id = absint( $_POST['form_id'] );
			if ( $form_id == 0 ) {
				$title = esc_html__( 'Form Name', 'super-forms' );
			} else {
				$title = get_the_title( $form_id );
			}
			$formSettings        = $_POST['formSettings'];
			$formElements        = wp_unslash( $_POST['formElements'] );
			$formElements        = json_decode( $formElements, true );
			$emailsSettings      = get_post_meta( $form_id, '_emails', true );
			$triggerSettings     = get_post_meta( $form_id, '_super_triggers', true );
			$woocommerceSettings = get_post_meta( $form_id, '_super_woocommerce', true );
			$listingsSettings    = get_post_meta( $form_id, '_super_listings', true );
			$pdfSettings         = get_post_meta( $form_id, '_super_pdf', true );
			$stripeSettings      = get_post_meta( $form_id, '_super_stripe', true );
			$translationSettings = get_post_meta( $form_id, '_super_translations', true );
			$secretsSettings     = get_post_meta( $form_id, '_super_local_secrets', true );
			$version             = get_post_meta( $form_id, '_super_version', true );
			$export              = array(
				'version'      => $version,
				'title'        => $title,
				'settings'     => $formSettings,
				'elements'     => $formElements,
				'emails'       => $emailsSettings,
				'triggers'     => $triggerSettings,
				'woocommerce'  => $woocommerceSettings,
				'listings'     => $listingsSettings,
				'pdf'          => $pdfSettings,
				'stripe'       => $stripeSettings,
				'translations' => $translationSettings,
				'secrets'      => $secretsSettings,
			);

			// Get file URL for attachment ID's to be able to import missing files cross-site
			$export   = maybe_serialize( $export );
			$basename = $title . '-super-forms-export-' . strtotime( date_i18n( 'Y-m-d H:i:s' ) ) . '.txt';
			$basename = sanitize_file_name( $basename );
			try {
				$d        = wp_upload_dir();
				$filename = trailingslashit( $d['path'] ) . $basename;
				file_put_contents( $filename, $export );
				$attachment    = array(
					'post_mime_type' => 'text/plain',
					'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);
				$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
				$attach_data   = wp_generate_attachment_metadata( $attachment_id, $filename );
				wp_update_attachment_metadata( $attachment_id, $attach_data );
				echo add_query_arg( array( 'sfdlfi' => $attachment_id ), home_url() );
				die();
			} catch ( Exception $e ) {
				// Print error message
				SUPER_Common::output_message(
					array(
						'msg' => $e->getMessage(),
					)
				);
			}
		}


		/**
		 *  Import single Form
		 *
		 *  @since      4.0.0
		 */
		public static function import_single_form() {
			$form_id = absint( $_POST['form_id'] );
			$file_id = absint( $_POST['file_id'] );
			// What do we need to import?
			$import_elements     = ( isset( $_POST['elements'] ) ? $_POST['elements'] : 'false' ); // Form elements
			$import_settings     = ( isset( $_POST['settings'] ) ? $_POST['settings'] : 'false' ); // Form settings
			$import_emails       = ( isset( $_POST['emails'] ) ? $_POST['emails'] : 'false' ); // Emails
			$import_triggers     = ( isset( $_POST['triggers'] ) ? $_POST['triggers'] : 'false' ); // Triggers
			$import_woocommerce  = ( isset( $_POST['woocommerce'] ) ? $_POST['woocommerce'] : 'false' ); // WooCommerce settings
			$import_listings     = ( isset( $_POST['listings'] ) ? $_POST['listings'] : 'false' ); // Listings settings
			$import_pdf          = ( isset( $_POST['pdf'] ) ? $_POST['pdf'] : 'false' ); // PDF settings
			$import_stripe       = ( isset( $_POST['stripe'] ) ? $_POST['stripe'] : 'false' ); // Stripe settings
			$import_translations = ( isset( $_POST['translations'] ) ? $_POST['translations'] : 'false' ); // Translation settings
			$import_secrets      = ( isset( $_POST['secrets'] ) ? $_POST['secrets'] : 'false' ); // Secrets settings
			$file                = wp_get_attachment_url( $file_id );
			if ( $file ) {
				$contents = wp_remote_fopen( $file );
				// Remove <html> tag at the beginning if exists
				$html_tag = substr( $contents, 0, 6 );
				if ( $html_tag === '<html>' ) {
					$contents = substr( $contents, 6 );
				}
				// Check if content is json (backward compatibility import from older SF versions)
				json_decode( $contents );
				if ( json_last_error() == JSON_ERROR_NONE ) {
					$contents = json_decode( $contents, true )[0];
				}
				$contents       = maybe_unserialize( $contents );
				$_POST['title'] = ( isset( $contents['title'] ) ? $contents['title'] : $contents['post_title'] );
				$form_data      = array( 'version' => '6.0.0' ); // default to this version if no version number exists, this is required to move/convert some form settings to the [Triggers] TAB
				if ( ! empty( $contents['version'] ) ) {
					$form_data['version'] = $contents['version'];
				}
				if ( $import_elements == 'true' && isset( $contents['elements'] ) ) {
					$form_data['elements'] = $contents['elements'];
				}
				if ( $import_settings == 'true' && isset( $contents['settings'] ) ) {
					$form_data['settings'] = $contents['settings'];
				}
				if ( $import_emails == 'true' && isset( $contents['emails'] ) ) {
					$form_data['emails'] = $contents['emails'];
				}
				if ( $import_triggers == 'true' && isset( $contents['triggers'] ) ) {
					$form_data['triggers'] = $contents['triggers'];
				}
				if ( $import_woocommerce == 'true' && isset( $contents['woocommerce'] ) ) {
					$form_data['woocommerce'] = $contents['woocommerce'];
				}
				if ( $import_listings == 'true' && isset( $contents['listings'] ) ) {
					$form_data['listings'] = $contents['listings'];
				}
				if ( $import_pdf == 'true' && isset( $contents['pdf'] ) ) {
					$form_data['pdf'] = $contents['pdf'];
				}
				if ( $import_stripe == 'true' && isset( $contents['stripe'] ) ) {
					$form_data['stripe'] = $contents['stripe'];
				}
				if ( $import_translations == 'true' && isset( $contents['translations'] ) ) {
					$form_data['translations'] = $contents['translations'];
				}
				if ( $import_secrets == 'true' && isset( $contents['secrets'] ) ) {
					$_POST['localSecrets'] = $contents['secrets'];
				}
				$_POST['form_data'] = $form_data;
				$response           = self::save_form();
				echo $response;
			} else {
				SUPER_Common::output_message(
					array(
						'msg' => sprintf( esc_html__( 'Import file #%d could not be located', 'super-forms' ), $file_id ),
					)
				);
			}
			die();
		}


		/**
		 *  Reset form settings
		 *
		 *  @since      4.0.0
		 */
		public static function reset_form_settings() {
			$form_id         = absint( $_POST['form_id'] );
			$global_settings = SUPER_Common::get_global_settings();
			update_post_meta( $form_id, '_super_form_settings', $global_settings );
			echo $form_id;
			die();
		}


		/**
		 *  Export Forms
		 *
		 *  @since      1.9
		 */
		public static function export_forms() {
			try {
				ini_set( 'max_execution_time', 0 );
				global $wpdb;
				$offset     = absint( $_POST['offset'] );
				$limit      = absint( $_POST['limit'] );
				$table      = $wpdb->prefix . 'posts';
				$table_meta = $wpdb->prefix . 'postmeta';
				if ( $_POST['found'] === '' ) {
					// Return total forms
					$found = absint(
						$wpdb->get_var(
							"
                SELECT COUNT(form.ID) 
                FROM $table AS form 
                WHERE form.post_status IN ('publish') AND form.post_type = 'super_form'"
						)
					);
				} else {
					$found = absint( $_POST['found'] );
				}
				$forms    = $wpdb->get_results(
					"
            SELECT form.ID, form.post_author, form.post_date, form.post_date_gmt, form.post_title, form.post_status
            FROM $table AS form WHERE form.post_status IN ('publish') AND form.post_type = 'super_form' 
            LIMIT $limit OFFSET $offset",
					ARRAY_A
				);
				$d        = wp_upload_dir();
				$basename = 'super-forms-export-' . strtotime( date_i18n( 'Y-m-d H:i:s' ) ) . '.txt';
				$filename = trailingslashit( $d['path'] ) . $basename;
				$fp       = fopen( $filename, 'w' );
				foreach ( $forms as $k => $v ) {
					$form_id                 = $v['ID'];
					$settings                = SUPER_Common::get_form_settings( $form_id );
					$forms[ $k ]['settings'] = $settings;
					$elements                = get_post_meta( $form_id, '_super_elements', true );
					if ( is_array( $elements ) ) {
						$forms[ $k ]['elements'] = $elements;
					} else {
						$forms[ $k ]['elements'] = json_decode( $elements, true );
					}
					$emails                      = get_post_meta( $form_id, '_emails', true );
					$forms[ $k ]['emails']       = $emails;
					$triggers                    = get_post_meta( $form_id, '_super_triggers', true );
					$forms[ $k ]['triggers']     = $triggers;
					$translations                = get_post_meta( $form_id, '_super_translations', true );
					$forms[ $k ]['translations'] = $translations;
					$secretsSettings             = get_post_meta( $form_id, '_super_local_secrets', true );
					$forms[ $k ]['secrets']      = $secretsSettings;
				}
				$content = SUPER_Common::safe_json_encode( $forms );
				fwrite( $fp, $content );
				fclose( $fp );
				$attachment_id = 0;
				if ( $offset + $limit > $found ) {
					$attachment    = array(
						'post_mime_type' => 'text/plain',
						'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);
					$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
					$attach_data   = wp_generate_attachment_metadata( $attachment_id, $filename );
					wp_update_attachment_metadata( $attachment_id, $attach_data );
				}
				echo SUPER_Common::safe_json_encode(
					array(
						'file_url' => add_query_arg( array( 'sfdlfi' => $attachment_id ), home_url() ),
						'offset'   => $offset + $limit,
						'found'    => $found,
					)
				);
				die();
			} catch ( Exception $e ) {
				// Print error message
				SUPER_Common::output_message(
					array(
						'msg' => $e->getMessage(),
					)
				);
			}
		}


		/**
		 *  Prepare Forms Import (from TXT file)
		 *
		 *  @since      1.9
		 */
		public static function start_forms_import() {
			$file_id = absint( $_POST['file_id'] );
			$url     = wp_get_attachment_url( $file_id );
			$request = wp_safe_remote_get( $url, $args );
			if ( is_wp_error( $request ) ) {
				error_log( $request->get_error_message() );
				wp_send_json_error( $request->get_error_message() );
				die();
			}
			$contents = wp_remote_retrieve_body( $request );
			if ( is_wp_error( $contents ) ) {
				error_log( $request->get_error_message() );
				wp_send_json_error( $contents->get_error_message() );
				die();
			}
			// Remove <html> tag at the beginning if exists
			$html_tag = substr( $contents, 0, 6 );
			if ( $html_tag === '<html>' ) {
				$contents = substr( $contents, 6 );
			}
			$forms = json_decode( $contents, true );
			if ( ! is_array( $forms ) ) {
				error_log( 'Import file is empty or you do not have permission to read it.' );
				error_log( $contents );
				wp_send_json_error( 'No permission to read import file or file is corrupted. Please check the server error log for more details, or try disabling `Basic access authentication` (if enabled).' );
				die();
			}
			foreach ( $forms as $k => $v ) {
				$form    = array(
					'post_author'   => $v['post_author'],
					'post_date'     => $v['post_date'],
					'post_date_gmt' => $v['post_date_gmt'],
					'post_title'    => $v['post_title'],
					'post_status'   => $v['post_status'],
					'post_type'     => 'super_form',
				);
				$form_id = wp_insert_post( $form );
				add_post_meta( $form_id, '_super_form_settings', $v['settings'] );

				// Save elements JSON
				$elements = $v['elements'];
				if ( ! is_array( $elements ) ) {
					$elements = json_decode( $elements, true );
				}
				add_post_meta( $form_id, '_super_elements', $elements );
				// Save emails settings JSON
				$emails = $v['emails'];
				if ( ! is_array( $emails ) ) {
					$emails = json_decode( $emails, true );
				}
				add_post_meta( $form_id, '_emails', $emails );
				// Save triggers settings JSON
				error_log( 'save_form_triggers(3)' );
				if ( isset( $v['triggers'] ) ) {
					SUPER_Common::save_form_triggers( $v['triggers'], $form_id );
				}
				// Save WooCommerce settings JSON
				if ( isset( $v['woocommerce'] ) ) {
					SUPER_Common::save_form_woocommerce_settings( $v['woocommerce'], $form_id );
				}
				error_log( 'save_form_listings_settings(5)' );
				// Save Listings settings JSON
				if ( isset( $v['listings'] ) ) {
					SUPER_Common::save_form_listings_settings( $v['listings'], $form_id );
				}
				// Save PDF settings JSON
				if ( isset( $v['pdf'] ) ) {
					SUPER_Common::save_form_pdf_settings( $v['pdf'], $form_id );
				}
				// Save Stripe settings JSON
				if ( isset( $v['stripe'] ) ) {
					SUPER_Common::save_form_stripe_settings( $v['stripe'], $form_id );
				}
				// Save Translations settings JSON
				if ( isset( $v['translations'] ) ) {
					add_post_meta( $form_id, '_super_translations', $v['translations'] );
				}
				// Save Secrets JSON
				if ( isset( $v['secrets'] ) ) {
					add_post_meta( $form_id, '_super_local_secrets', $v['secrets'] );
				}
			}
			wp_send_json_success();
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
			if ( isset( $_POST['type'] ) ) {
				$type = $_POST['type'];
			}
			$form_ids = '';
			if ( isset( $_POST['form_ids'] ) ) {
				$ids      = trim( sanitize_text_field( $_POST['form_ids'] ) );
				$ids      = explode( ',', $ids );
				$form_ids = array();
				foreach ( $ids as $k => $v ) {
					if ( empty( $v ) ) {
						continue;
					}
					$form_ids[ absint( $v ) ] = absint( $v );
				}
				unset( $ids );
			}
			if ( ! empty( $form_ids ) ) {
				$form_ids    = implode( ', ', $form_ids );
				$form_filter = " AND entry.post_parent IN ($form_ids)";
			}
			$order_by = 'ASC'; // Default
			if ( isset( $_POST['order_by'] ) ) {
				$order_by = sanitize_text_field( $_POST['order_by'] );
			}
			$order_by_filter = "entry.post_date $order_by";

			$from        = '';
			$till        = '';
			$range_query = '';
			if ( isset( $_POST['from'] ) ) {
				$from = $_POST['from'];
			}
			if ( isset( $_POST['till'] ) ) {
				$till = $_POST['till'];
			}
			if ( ( $from != '' ) && ( $till != '' ) ) {
				$from        = date_i18n( 'Y-m-d', strtotime( $from ) );
				$till        = date_i18n( 'Y-m-d', strtotime( $till ) );
				$range_query = " AND ((entry.post_date LIKE '$from%' OR entry.post_date LIKE '$till%') OR (entry.post_date BETWEEN '$from' AND '$till'))";
			}
			$table      = $wpdb->prefix . 'posts';
			$table_meta = $wpdb->prefix . 'postmeta';
			$entries    = $wpdb->get_results(
				"SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry' $form_filter $range_query
        ORDER BY $order_by_filter"
			);

			$rows      = array();
			$columns   = array();
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
			foreach ( $entries as $k => $v ) {
				$data = unserialize( $v->data );
				foreach ( $data as $dk => $dv ) {
					if ( ! in_array( $dk, $columns ) ) {
						$columns[] = $dk;
						$rows[0][] = $dk;
					}
				}
				$data['entry_id']['value']          = $v->ID;
				$data['entry_title']['value']       = $v->post_title;
				$data['entry_date']['value']        = $v->post_date;
				$data['entry_author']['value']      = $v->post_author;
				$data['entry_status']['value']      = $v->post_status;
				$data['entry_ip']['value']          = get_post_meta( $v->ID, '_super_contact_entry_ip', true );
				$data['entry_wc_order_id']['value'] = get_post_meta( $v->ID, '_super_contact_entry_wc_order_id', true );

				// @since 3.4.0 - custom entry status
				$data['entry_custom_status']['value'] = get_post_meta( $v->ID, '_super_contact_entry_status', true );

				$entries[ $k ] = $data;
			}
			$rows[0][] = 'entry_ip';
			$columns[] = 'entry_ip';
			$rows[0][] = 'entry_wc_order_id';
			$columns[] = 'entry_wc_order_id';

			foreach ( $entries as $k => $v ) {
				foreach ( $columns as $cv ) {
					if ( isset( $v[ $cv ] ) ) {
						if ( ( isset( $v[ $cv ]['type'] ) ) && ( $v[ $cv ]['type'] == 'files' ) ) {
							$files = '';
							if ( ( isset( $v[ $cv ]['files'] ) ) && ( count( $v[ $cv ]['files'] ) != 0 ) ) {
								foreach ( $v[ $cv ]['files'] as $fk => $fv ) {
									if ( $fk == 0 ) {
										$files .= $fv['url'];
									} else {
										$files .= PHP_EOL . $fv['url'];
									}
								}
							}
							$rows[ $k + 1 ][] = $files;
						} elseif ( ! isset( $v[ $cv ]['value'] ) ) {
								$rows[ $k + 1 ][] = '';
						} else {
							$rows[ $k + 1 ][] = $v[ $cv ]['value'];
						}
					} else {
						$rows[ $k + 1 ][] = '';
					}
				}
			}
			try {
				$d        = wp_upload_dir();
				$basename = 'super-contact-entries-' . strtotime( date_i18n( 'Y-m-d H:i:s' ) ) . '.csv';
				$filename = trailingslashit( $d['path'] ) . $basename;
				$fp       = fopen( $filename, 'w' );
				// @since 3.1.0 - write file header (byte order mark) for correct encoding to fix UTF-8 in Excel
				$bom = apply_filters( 'super_csv_bom_header_filter', chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
				if ( fwrite( $fp, $bom ) === false ) {
					// Print error message
					SUPER_Common::output_message(
						array(
							'msg' => "Unable to write to file ($filename)",
						)
					);
				}
				$delimiter = wp_unslash( sanitize_text_field( $_POST['delimiter'] ) );
				$enclosure = wp_unslash( sanitize_text_field( $_POST['enclosure'] ) );
				if ( empty( $delimiter ) ) {
					$delimiter = ',';
				}
				if ( empty( $enclosure ) ) {
					$enclosure = '"';
				}
				foreach ( $rows as $fields ) {
					fputcsv( $fp, $fields, $delimiter, $enclosure, PHP_EOL );
				}
				fclose( $fp );
				$attachment    = array(
					'post_mime_type' => 'text/csv',
					'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);
				$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
				$attach_data   = wp_generate_attachment_metadata( $attachment_id, $filename );
				wp_update_attachment_metadata( $attachment_id, $attach_data );
				echo add_query_arg( array( 'sfdlfi' => $attachment_id ), home_url() );
				die();
			} catch ( Exception $e ) {
				// Print error message
				SUPER_Common::output_message(
					array(
						'msg' => $e->getMessage(),
					)
				);
			}
		}


		/**
		 *  Import Global Settings (from settings page)
		 *
		 *  @since      1.0.6
		 */
		public static function import_global_settings() {
			if ( ( isset( $_POST['method'] ) ) && ( $_POST['method'] == 'load-default' ) ) {
				$settings = SUPER_Settings::get_defaults();
			} else {
				$settings = $_POST['settings'];
				$settings = json_decode( stripslashes( $settings ), true );
				if ( json_last_error() != 0 ) {
					error_log( 'JSON error: ' . json_last_error() );
					var_dump( 'JSON error: ' . json_last_error() );
				}
			}
			update_option( 'super_settings', $settings );
			die();
		}


		/**
		 *  Loads the form preview on backend (create form page)
		 *
		 *  @since      1.0.0
		 */
		public static function load_preview() {
			$form_id = absint( $_POST['form_id'] );
			echo SUPER_Shortcodes::super_form_func( array( 'id' => $form_id ) );
			die();
		}


		/**
		 *  Clear deleted translations
		 *
		 *  @since      4.7.0
		 */
		public static function clear_i18n( $elements = array(), $translations = array() ) {
			if ( ! empty( $elements ) ) {
				foreach ( $elements as $k => $v ) {
					// Check if has inner elements
					if ( ! empty( $v['inner'] ) ) {
						$elements[ $k ]['inner'] = self::clear_i18n( $v['inner'], $translations );
					} else {
						// Just remove deleted translations
						if ( ! empty( $v['data']['i18n'] ) && is_array( $v['data']['i18n'] ) ) {
							foreach ( $v['data']['i18n'] as $ik => $iv ) {
								if ( ! isset( $translations[ $ik ] ) ) {
									// Delete translation
									unset( $elements[ $k ]['data']['i18n'][ $ik ] );
								}
							}
						}
					}
				}
			}
			return $elements;
		}


		/**
		 *  Saves the form with all it's settings
		 *
		 *  @since      1.0.0
		 */
		public static function save_form() {
			error_log( 'save_form()' );
			// Normal form save:
			$action    = ( isset( $_POST['action'] ) ? $_POST['action'] : '' );
			$form_id   = ( ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0 );
			$title     = ( ! empty( $_POST['title'] ) ? $_POST['title'] : esc_html__( 'Form Name', 'super-forms' ) );
			$form_data = ( ! empty( $_POST['form_data'] ) ? $_POST['form_data'] : '' );
			$version   = ( ! empty( $form_data['version'] ) ? $form_data['version'] : '' );
			// Check if one of the keys doesn't exist, this is the case when the server was unable to process this request
			// because the form is to large to be saved by this specific server
			// Except when importing a form from file...
			if ( ! isset( $_POST['z'] ) && $action !== 'super_import_single_form' ) {
				SUPER_Common::output_message( array( 'msg' => esc_html__( 'Error: please increase the `max_input_vars` value your php.ini (or via .htaccess) in order to save the form.', 'super-forms' ) ) );
			}
			if ( ! is_array( $form_data ) && $action !== 'super_import_single_form' ) {
				$form_data = wp_unslash( $form_data );
				$form_data = json_decode( $form_data, true );
			}
			$re_slash = array( 'emails', 'elements', 'settings', 'triggers', 'woocommerce', 'listings', 'pdf', 'stripe' );
			foreach ( $form_data as $k => $v ) {
				// Re-slash is required to keep "Custom regex" working e.g: \\d will become \\\\d
				// Re-slash is required to keep Custom CSS {content: '\x123';} working
				if ( in_array( $k, $re_slash ) ) {
					$form_data[ $k ] = wp_slash( $form_data[ $k ] );
				}
			}
			extract( $form_data );
			error_log( json_encode( $triggers ) );
			if ( ! isset( $emails ) ) {
				$emails = array();
			}
			if ( ! isset( $triggers ) ) {
				$triggers = array();
			}
			if ( ! isset( $woocommerce ) ) {
				$woocommerce = array();
			}
			if ( ! isset( $listings ) ) {
				$listings = array();
			}
			if ( ! isset( $pdf ) ) {
				$pdf = array();
			}
			if ( ! isset( $stripe ) ) {
				$stripe = array();
			}
			$local_secrets  = ( ! empty( $_POST['localSecrets'] ) ? $_POST['localSecrets'] : '' );
			$global_secrets = ( ! empty( $_POST['globalSecrets'] ) ? $_POST['globalSecrets'] : '' );
			// We must delete/clear any translations that no longer exist
			if ( ! isset( $elements ) ) {
				$elements = array();
			}
			if ( ! is_array( $elements ) ) {
				$elements = array();
			}
			$elements = self::clear_i18n( $elements, $translations );
			// @since 3.9.0 - don't save settings that are the same as global settings
			// Get global settings
			$global_settings = SUPER_Common::get_global_settings();
			// @since 4.7.0 - translation language switcher
			if ( isset( $i18n_disable_browser_translation ) ) {
				$settings['i18n_disable_browser_translation'] = sanitize_text_field( $i18n_disable_browser_translation );
			}
			error_log( $i18n_switch );
			if ( isset( $i18n_switch ) ) {
				$settings['i18n_switch'] = sanitize_text_field( $i18n_switch );
			}
			error_log( $settings['i18n_switch'] );
			if ( empty( $form_id ) ) {
				// Create a new form
				$form    = array(
					'post_author' => get_current_user_id(),
					'post_title'  => $title,
					'post_status' => 'publish',
					'post_type'   => 'super_form',
				);
				$form_id = wp_insert_post( $form );
				// $form_id = SUPER_Common::wp_insert_post_fast($form);
				error_log( '&&&&&& save_form_meta(1)' );
				self::save_form_meta(
					array(
						'version'        => ( $action === 'super_import_single_form' ? $version : SUPER_VERSION ),
						'title'          => $title,
						'action'         => $action,
						'form_id'        => $form_id,
						'settings'       => $settings,
						'elements'       => $elements,
						'emails'         => $emails,
						'triggers'       => $triggers,
						'woocommerce'    => $woocommerce,
						'listings'       => $listings,
						'pdf'            => $pdf,
						'stripe'         => $stripe,
						'translations'   => $translations,
						'local_secrets'  => $local_secrets,
						'global_secrets' => $global_secrets,
						'new'            => true,
						'backup'         => false,
					)
				);
			} else {
				// Update existing form
				$form = array(
					'ID'          => $form_id,
					'post_title'  => $title,
					'post_status' => 'publish',
				);
				wp_update_post( $form );
				// SUPER_Common::wp_update_post_fast($form);
				// tmp if(!empty($i18n)){
				// tmp     // Merge with existing form settings
				// tmp     $form_settings = SUPER_Common::get_form_settings($form_id);
				// tmp     error_log('&&&&&& $form_settings:');
				// tmp     error_log(json_encode($form_settings));
				// tmp     // Add language to the form settings
				// tmp     $form_settings['i18n'][$i18n] = $settings;
				// tmp     $settings = $form_settings;
				// tmp     error_log('&&&&&& $settings:');
				// tmp     error_log(json_encode($settings));
				// tmp }else{
				error_log( '&&&&&& $settings 2.1:' );
				error_log( json_encode( $settings ) );
				$form_settings = SUPER_Common::get_form_settings( $form_id );
				error_log( '&&&&&& $form_settings 2.1:' );
				error_log( json_encode( $form_settings ) );
				if ( ! empty( $form_settings['i18n'] ) ) {
					error_log( '&&&&&& $form_settings.i18n 2:' );
					error_log( json_encode( $form_settings['i18n'] ) );
					$settings['i18n'] = $form_settings['i18n'];
				}
				error_log( '&&&&&& $settings 2.2:' );
				error_log( json_encode( $settings ) );
				// tmp }
				error_log( '&&&&&& save_form_meta(2)' );
				self::save_form_meta(
					array(
						'version'        => $version,
						'title'          => $title,
						'action'         => $action,
						'form_id'        => $form_id,
						'settings'       => $settings,
						'elements'       => $elements,
						'emails'         => $emails,
						'triggers'       => $triggers,
						'woocommerce'    => $woocommerce,
						'listings'       => $listings,
						'pdf'            => $pdf,
						'stripe'         => $stripe,
						'translations'   => $translations,
						'local_secrets'  => $local_secrets,
						'global_secrets' => $global_secrets,
						'new'            => false,
						'backup'         => false,
					)
				);
			}
			if ( $action === 'super_save_form' ) {
				// Only update global secrets if we are not importing a form
				update_option( 'super_global_secrets', $global_secrets );
				echo SUPER_Common::safe_json_encode(
					array(
						'form_id'      => $form_id,
						'modifiedTime' => get_post_modified_time( 'U', false, $form_id ),
					)
				);
				die();
			}
			// Importing single form, we must return the form ID
			if ( $action === 'super_import_single_form' ) {
				return $form_id;
			}
		}
		public static function save_form_meta( $x ) {
			error_log( 'save_form_meta()' );
			extract( $x );
			if ( $new === true ) {
				error_log( '@@@ save_form_meta() $emails(1):' );
				error_log( json_encode( $emails ) );
				add_post_meta( $form_id, '_super_version', $version );
				add_post_meta( $form_id, '_super_form_settings', $settings );
				add_post_meta( $form_id, '_emails', $emails );
				add_post_meta( $form_id, '_super_elements', $elements );
				add_post_meta( $form_id, '_super_translations', $translations );
				add_post_meta( $form_id, '_super_local_secrets', $local_secrets );
				error_log( 'save_form_triggers(4)' );
				SUPER_Common::save_form_triggers( $triggers, $form_id );
				SUPER_Common::save_form_woocommerce_settings( $woocommerce, $form_id );
				error_log( 'save_form_listings_settings(1)' );
				SUPER_Common::save_form_listings_settings( $listings, $form_id );
				SUPER_Common::save_form_pdf_settings( $pdf, $form_id );
				SUPER_Common::save_form_stripe_settings( $stripe, $form_id );
			} else {
				if ( $action === 'super_save_form' ) {
					error_log( '@@@ save_form_meta() $emails(2):' );
					error_log( json_encode( $emails ) );
					update_post_meta( $form_id, '_super_version', SUPER_VERSION );
					update_post_meta( $form_id, '_super_form_settings', $settings );
					update_post_meta( $form_id, '_super_elements', $elements );
					update_post_meta( $form_id, '_emails', $emails );
					update_post_meta( $form_id, '_super_translations', $translations );
					update_post_meta( $form_id, '_super_local_secrets', $local_secrets );
					error_log( 'save_form_triggers(5)' );
					SUPER_Common::save_form_triggers( $triggers, $form_id );
					SUPER_Common::save_form_woocommerce_settings( $woocommerce, $form_id );
					error_log( 'save_form_listings_settings(2)' );
					SUPER_Common::save_form_listings_settings( $listings, $form_id );
					SUPER_Common::save_form_pdf_settings( $pdf, $form_id );
					SUPER_Common::save_form_stripe_settings( $stripe, $form_id );
				}
				if ( $action === 'super_import_single_form' ) {
					update_post_meta( $form_id, '_super_version', $version );
					error_log( '@@@ save_form_meta() $emails(3):' );
					error_log( json_encode( $emails ) );
					if ( ! empty( $settings ) ) {
						update_post_meta( $form_id, '_super_form_settings', $settings );
					}
					if ( ! empty( $elements ) ) {
						update_post_meta( $form_id, '_super_elements', $elements );
					}
					if ( ! empty( $emails ) ) {
						update_post_meta( $form_id, '_emails', $emails );
					}
					error_log( 'save_form_triggers(6)' );
					if ( ! empty( $triggers ) ) {
						SUPER_Common::save_form_triggers( $triggers, $form_id );
					}
					if ( ! empty( $woocommerce ) ) {
						SUPER_Common::save_form_woocommerce_settings( $woocommerce, $form_id );
					}
					error_log( 'save_form_listings_settings(3)' );
					if ( ! empty( $listings ) ) {
						SUPER_Common::save_form_listings_settings( $listings, $form_id );
					}
					if ( ! empty( $pdf ) ) {
						SUPER_Common::save_form_pdf_settings( $pdf, $form_id );
					}
					if ( ! empty( $stripe ) ) {
						SUPER_Common::save_form_stripe_settings( $stripe, $form_id );
					}
					if ( ! empty( $translations ) ) {
						update_post_meta( $form_id, '_super_translations', $translations );
					}
					if ( ! empty( $local_secrets ) ) {
						update_post_meta( $form_id, '_super_local_secrets', $local_secrets );
					}
				}
				// @since 3.1.0 - save history (store a total of 50 backups into db)
				if ( $backup === false ) {
					$form      = array(
						'post_parent' => $form_id,
						'post_title'  => $title,
						'post_status' => 'backup',
						'post_type'   => 'super_form',
					);
					$backup_id = wp_insert_post( $form );
					// $backup_id = SUPER_Common::wp_insert_post_fast($form);
					error_log( '&&&&&& save_form_meta(3)' );
					self::save_form_meta(
						array(
							'title'          => $title,
							'action'         => $action,
							'form_id'        => $backup_id,
							'settings'       => $settings,
							'elements'       => $elements,
							'emails'         => $emails,
							'triggers'       => $triggers,
							'woocommerce'    => $woocommerce,
							'listings'       => $listings,
							'pdf'            => $pdf,
							'stripe'         => $stripe,
							'translations'   => $translations,
							'local_secrets'  => $local_secrets,
							'global_secrets' => $global_secrets,
							'new'            => false,
							'backup'         => true,
						)
					);
					// Delete backups 50+
					$args    = array(
						'post_parent'    => $form_id,
						'post_type'      => 'super_form',
						'post_status'    => 'backup',
						'posts_per_page' => -1, // Make sure all matching backups will be retrieved
					);
					$backups = get_posts( $args );
					if ( is_array( $backups ) && count( $backups ) !== 0 ) {
						foreach ( $backups as $k => $v ) {
							if ( $k < 50 ) {
								continue;
							}
							wp_delete_post( $v->ID, true );
						}
					}
				}
			}
		}

		/**
		 *  Deletes the form with all it's settings
		 *
		 *  @since      1.0.0
		 */
		public static function delete_form() {
			$form_id = absint( $_POST['form_id'] );

			// @since 3.1.0 - also delete backups
			$args    = array(
				'post_parent'    => $form_id,
				'post_type'      => 'super_form',
				'post_status'    => 'backup',
				'posts_per_page' => -1, // Make sure all matching backups will be retrieved
			);
			$backups = get_posts( $args );
			if ( is_array( $backups ) && count( $backups ) > 0 ) {
				// Delete all the Children of the Parent Page
				foreach ( $backups as $v ) {
					wp_delete_post( $v->ID, true );
				}
			}

			// Delete the form
			wp_delete_post( $form_id, true );

			die();
		}

		// Helper function, to loop over all element settings
		public static function loop_over_element_setting_fields( $fields, $data, $shortcodes, $group, $tag, $k ) {
			$result = '';
			foreach ( $fields  as $fk => $fv ) {
				$default = SUPER_Common::get_default_element_setting_value( $shortcodes, $group, $tag, $k, $fk );
				$fv['v'] = $default; // if doesn't exists, fallback to default value
				if ( isset( $data[ $fk ] ) ) {
					$fv['v'] = $data[ $fk ];
				}
				$filter      = '';
				$parent      = '';
				$filtervalue = '';
				if ( ( isset( $fv['filter'] ) ) && ( $fv['filter'] == true ) ) {
					$filter = ' super-filter';
					if ( isset( $fv['parent'] ) ) {
						$parent = ' data-parent="' . $fv['parent'] . '"';
					}
					if ( isset( $fv['filter_value'] ) ) {
						$filtervalue = ' data-filtervalue="' . $fv['filter_value'] . '"';
					}
				}
				$hidden = '';
				if ( isset( $fv['hidden'] ) && ( $fv['hidden'] == true ) ) {
					$hidden = ' super-hidden';
				}
				if ( ! isset( $fv['type'] ) ) {
					$fv['type'] = 'text';
				}
				$result .= '<div class="super-field super-field-type-' . $fv['type'] . $filter . $hidden . '"' . $parent . '' . $filtervalue . '>';
				if ( isset( $fv['name'] ) ) {
					$result .= '<div class="super-field-name">' . ( $fv['name'] );
					if ( isset( $fv['desc'] ) ) {
						$result .= '<i class="info super-tooltip" title="' . esc_attr( $fv['desc'] ) . '"></i>';
					}
				}
				if ( isset( $fv['label'] ) ) {
					$result .= '<div class="super-field-label">' . nl2br( $fv['label'] );
					if ( ! isset( $fv['name'] ) && isset( $fv['desc'] ) ) {
						$result .= '<i class="info super-tooltip" title="' . esc_attr( $fv['desc'] ) . '"></i>';
					}
				}
				if ( isset( $fv['label'] ) ) {
					$result .= '</div>';
				}
				if ( isset( $fv['name'] ) ) {
					$result .= '</div>';
				}
				$result .= SUPER_Common::docs( $fv );
				$result .= '<div class="super-field-input"';
				if ( ! empty( $fv['allow_empty'] ) ) {
					$result .= ' data-allow-empty="true"';
				}
				if ( ( $default !== '' ) && ( ! is_array( $default ) ) ) {
					$result .= ' data-default="' . $default . '"';
				}
				if ( ! empty( $fv['_styles'] ) ) {
					$result .= ' data-styles="' . esc_attr( SUPER_Common::safe_json_encode( $fv['_styles'] ) ) . '"';
				}
				$result .= '>';
				if ( method_exists( 'SUPER_Field_Types', $fv['type'] ) ) {
					if ( isset( $data[ $fk ] ) ) {
						$fv['default'] = $data[ $fk ];
					}
					$result .= call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv, $data );
				}
				$result .= '</div>';
				$result .= '</div>';
			}
			return $result;
		}

		/**
		 *  Function to load all element settings while editing the element (create form page / settings tabs)
		 *
		 *  @param  string $tag
		 *  @param  array  $data
		 *
		 *  @since      1.0.0
		 */
		public static function load_element_settings( $tag = null, $group = null, $data = null ) {
			if ( $tag == null ) {
				$tag = $_POST['tag'];
			}
			if ( $group == null ) {
				$group = $_POST['group'];
			}
			if ( $data == null ) {
				$data = $_POST['data'];
			}

			$settings   = SUPER_Common::get_form_settings( $_POST['form_id'] );
			$shortcodes = SUPER_Shortcodes::shortcodes( false, false, false );
			$array      = SUPER_Shortcodes::shortcodes( false, $data, false );
			$tabs       = $array[ $group ]['shortcodes'][ $tag ]['atts'];
			$result     = '';
			if ( $tag === 'html' ) {
				if ( ! isset( $data['exclude'] ) ) {
					$data['exclude'] = '2';
				}
				if ( ! isset( $data['exclude_entry'] ) ) {
					$data['exclude_entry'] = 'true';
				}
			}
			$translating = $_POST['translating'];
			if ( $translating == 'false' ) {
				$result .= '<div class="super-element-settings-tabs">';
				$result .= '<select>';
					$i   = 0;
				foreach ( $tabs as $k => $v ) {
					$result .= '<option ' . ( $i == 0 ? 'selected="selected"' : '' ) . ' value="' . $i . '" data-key="' . $k . '">' . $v['name'] . '</option>';
					++$i;
				}
				$result .= '</select>';
				$result .= '</div>';
				$i       = 0;
				foreach ( $tabs as $k => $v ) {
					$result .= '<div class="tab-content' . ( $i == 0 ? ' super-active' : '' ) . '" data-key="' . $k . '">';
					if ( $k === 'icon' && $settings['theme_hide_icons'] === 'yes' ) {
						$result .= '<strong style="color:red;">' . esc_html__( 'Please note', 'super-forms' ) . ':</strong> ' . esc_html__( 'Your icons will not be displayed because you currently have enabled the option to hide field icons under "Form Settings > Theme & Colors > Hide field icons"', 'super-forms' );
					}
					if ( $k === 'distance_calculator' && empty( $settings['form_google_places_api'] ) ) {
						$result .= '<strong style="color:red;">' . esc_html__( 'Please note', 'super-forms' ) . ':</strong> ' . sprintf( esc_html__( 'In order to use this feature you must provide your Google API key in %1$sSuper Forms > Settings > Form Settings%2$s', 'super-forms' ), '<a target="_blank" href="' . esc_url( admin_url() . 'admin.php?page=super_settings#form-settings' ) . '">', '</a>' );
					}
					if ( isset( $v['fields'] ) ) {
						$result .= self::loop_over_element_setting_fields( $v['fields'], $data, $shortcodes, $group, $tag, $k );
					} else {
						// Display subtabs
						unset( $v['name'] );
						$result .= '<div class="super-subtabs">';
						$i       = 0;
						foreach ( $v as $stk => $stv ) {
							$result .= '<div class="super-subtab' . ( $i == 0 ? ' super-active' : '' ) . '">' . $stv['name'] . '</div>';
							++$i;
						}
						$result .= '</div>';
						$result .= '<div class="super-subtabscontent">';
						$i       = 0;
						foreach ( $v as $stk => $stv ) {
							$result .= '<div class="super-subtabcontent' . ( $i == 0 ? ' super-active' : '' ) . '">';
								// Loop over all fields belonging to this Sub TAB
								$result .= self::loop_over_element_setting_fields( $stv['fields'], $data, $shortcodes, $group, $tag, $k );
							$result     .= '</div>';
							++$i;
						}
						$result .= '</div>';
					}
					$result .= '</div>';
					$i       = 1;
				}
			} else {
				$result .= '<div class="tab-content super-active">';
				foreach ( $tabs as $k => $v ) {
					if ( isset( $v['fields'] ) ) {
						foreach ( $v['fields'] as $fk => $fv ) {
							if ( ! isset( $data[ $fk ] ) || empty( $fv['i18n'] ) ) {
								continue;
							}
							if ( ( isset( $fv['filter'] ) ) && ( $fv['filter'] == true ) && ( isset( $fv['parent'] ) ) ) {
								if ( strpos( $v['fields'][ $fv['parent'] ]['default'], $fv['filter_value'] ) === false ) {
									if ( isset( $fv['filterAlwaysShowOnTranslate'] ) && $fv['filterAlwaysShowOnTranslate'] == true ) {
										// Do not skip, currently used by
										// international phone number to display different
										// preferred/default flags based on choosen language
									} else {
										// Skip (don't show this setting)
										continue;
									}
								}
							}
							$default = SUPER_Common::get_default_element_setting_value( $shortcodes, $group, $tag, $k, $fk );

							$hidden = '';
							if ( isset( $fv['hidden'] ) && ( $fv['hidden'] == true ) ) {
								$hidden = ' hidden';
							}
							$result .= '<div class="super-field' . $hidden . '">';
							if ( isset( $fv['name'] ) ) {
								$result .= '<div class="super-field-name">' . ( $fv['name'] );
								if ( isset( $fv['desc'] ) ) {
									$result .= '<i class="info super-tooltip" title="' . esc_attr( $fv['desc'] ) . '"></i>';
								}
							}
							if ( isset( $fv['label'] ) ) {
								$result .= '<div class="super-field-label">' . nl2br( $fv['label'] );
								if ( ! isset( $fv['name'] ) && isset( $fv['desc'] ) ) {
									$result .= '<i class="info super-tooltip" title="' . esc_attr( $fv['desc'] ) . '"></i>';
								}
							}
							if ( isset( $fv['label'] ) ) {
								$result .= '</div>';
							}
							if ( isset( $fv['name'] ) ) {
								$result .= '</div>';
							}
								$result .= SUPER_Common::docs( $fv );
								$result .= '<div class="super-field-input"';
							if ( ! empty( $fv['allow_empty'] ) ) {
								$result .= ' data-allow-empty="true"';
							}
							if ( ( $default !== '' ) && ( ! is_array( $default ) ) ) {
								$result .= ' data-default="' . $default . '"';
							}
								$result .= '>';
							if ( ! isset( $fv['type'] ) ) {
								$fv['type'] = 'text';
							}
							if ( method_exists( 'SUPER_Field_Types', $fv['type'] ) ) {
								$fv['v'] = $default; // if doesn't exists, fallback to default value
								if ( isset( $data['i18n'] ) && isset( $data['i18n'][ SUPER_Forms()->i18n ] ) ) {
									if ( isset( $data['i18n'][ SUPER_Forms()->i18n ][ $fk ] ) ) {
										$fv['v'] = $data['i18n'][ SUPER_Forms()->i18n ][ $fk ];
									} elseif ( isset( $data[ $fk ] ) ) {
											$fv['v'] = $data[ $fk ];
									}
								} elseif ( isset( $data[ $fk ] ) ) {
										$fv['v'] = $data[ $fk ];
								}
								$result .= call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv, $data );
							}
								$result .= '</div>';
							$result     .= '</div>';
						}
					}
				}
				$result .= '</div>';
			}
			$result .= '<span class="super-button super-update-element">' . esc_html__( 'Update Element', 'super-forms' ) . '</span>';
			$result .= '<span class="super-button super-cancel-update">' . esc_html__( 'Close', 'super-forms' ) . '</span>';
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
		public static function get_element_builder_html( $tag = null, $group = null, $inner = null, $data = null, $method = 1 ) {
			$i18n    = SUPER_Common::get_payload_i18n();
			$form_id = 0;
			if ( isset( $_POST['form_id'] ) ) {
				$form_id = absint( $_POST['form_id'] );
			}
			$settings = SUPER_Common::get_form_settings( $form_id );

			include_once SUPER_PLUGIN_DIR . '/includes/class-shortcodes.php';
			$shortcodes = SUPER_Shortcodes::shortcodes();

			$predefined = '';
			if ( isset( $_POST['predefined'] ) ) {
				$predefined = $_POST['predefined'];
			}
			if ( $predefined != '' ) {
				$result     = '';
				$predefined = wp_unslash( $predefined );
				foreach ( $predefined as $k => $v ) {
					// Output builder HTML (element and with action buttons)
					if ( empty( $v['data'] ) ) {
						$v['data'] = null;
					}
					if ( empty( $v['inner'] ) ) {
						$v['inner'] = null;
					}
					$result .= SUPER_Shortcodes::output_builder_html(
						array(
							'tag'        => $v['tag'],
							'group'      => $v['group'],
							'data'       => $v['data'],
							'inner'      => $v['inner'],
							'shortcodes' => $shortcodes,
							'settings'   => $settings,
							'predefined' => true,
						)
					);
				}
			} else {
				if ( $tag == null ) {
					$tag = $_POST['tag'];
				}
				if ( $group == null ) {
					$group = $_POST['group'];
				}
				$builder = 1;
				if ( isset( $_POST['builder'] ) ) {
					$builder = $_POST['builder'];
				}
				if ( empty( $inner ) ) {
					$inner = array();
					if ( isset( $_POST['inner'] ) ) {
						$inner = $_POST['inner'];
					}
				}
				if ( empty( $data ) ) {
					$data = array();
					if ( isset( $_POST['data'] ) ) {
						$data = $_POST['data'];
					}
				}
				if ( is_array( $data ) ) {
					$data = array_map( 'stripslashes_deep', $data );
				}
				// If updating TAB element, we only want to update the TABs, not the content
				$builder = explode( ';', $builder );
				$from    = $builder[0];
				if ( $from == 'tabs' || $from == 'accordion' || $from == 'list' ) {
					// Make sure the correct layout is send (required in case we are translating the element, otherwise it would default to TAB layout
					if ( $_POST['translating'] == 'true' ) {
						$builder[1]     = $from;
						$data['layout'] = $from;
					}
					$result = SUPER_Shortcodes::output_builder_html(
						array(
							'tag'        => $tag,
							'group'      => $group,
							'data'       => $data,
							'inner'      => $inner,
							'shortcodes' => $shortcodes,
							'settings'   => $settings,
							'builder'    => $builder,
						)
					);
				} elseif ( $from == 0 ) {
						// Output element HTML only
						$result = SUPER_Shortcodes::output_element_html(
							array(
								'grid'       => null,
								'tag'        => $tag,
								'group'      => $group,
								'data'       => $data,
								'inner'      => $inner,
								'shortcodes' => $shortcodes,
								'settings'   => $settings,
								'i18n'       => $i18n,
								'builder'    => false,
							)
						);
				} else {
					// Output builder HTML (element and with action buttons)
					$result = SUPER_Shortcodes::output_builder_html(
						array(
							'tag'        => $tag,
							'group'      => $group,
							'data'       => $data,
							'inner'      => $inner,
							'shortcodes' => $shortcodes,
							'settings'   => $settings,
						)
					);
				}
			}

			// Return method
			if ( $method == 1 ) {
				echo $result;
			} else {
				return $result;
			}

			die();
		}

		public static function submit_form_checks( $skipChecks = false ) {
			$csrfValidation = SUPER_Common::verifyCSRF();
			if ( ! $csrfValidation && empty( $GLOBALS['super_csrf'] ) ) {
				// Only if not previously validated
				// For example if files are being uploaded by the user
				$GLOBALS['super_csrf'] = true;
				// Only check when not disabled by the user.
				// Some users want to use/load their forms via an iframe from a different domain name
				// In this case sessions won't work  because of browsers "SameSite by default cookies"
				$global_settings = SUPER_Common::get_global_settings();
				if ( ! empty( $global_settings['csrf_check'] ) && $global_settings['csrf_check'] === 'false' ) {
					// Check was disabled by the user, skip it
				} else {
					// Return error
					SUPER_Common::output_message(
						array(
							'type' => 'session_expired',
							'msg'  => esc_html__( 'Unable to submit form, session expired!', 'super-forms' ),
						)
					);
				}
			}
			// Check if form_id exists, this is always required
			// If it doesn't exist it is most likely due the server not being able to process all the data
			// In that case "max_input_vars" should be increased
			if ( empty( $_POST['form_id'] ) ) {
				// First try to increase it manually
				// If it fails, tell the user about it, so they can contact the webmaster
				$max_input_vars        = ini_get( 'max_input_vars' );
				$double_max_input_vars = round( ini_get( 'max_input_vars' ) * 2, 0 );
				if ( ini_set( 'max_input_vars', $double_max_input_vars ) == false ) {
					// Failed, notify user
					SUPER_Common::output_message(
						array(
							'msg' => sprintf( esc_html__( 'Error: the server could not submit this form because it reached it\'s "max_input_vars" limit of %s' . ini_get( 'max_input_vars' ) . '%s. Please contact your webmaster and increase this limit inside your php.ini file!', 'super-forms' ), '<strong>', '</strong>' ),
						)
					);
				} else {
					// Success, notify user to try again
					SUPER_Common::output_message(
						array(
							'msg' => sprintf( esc_html__( 'Error: the server could not submit this form because it reached it\'s "max_input_vars" limit of %s' . $max_input_vars . '%s. We manually increased this limit to %s' . $double_max_input_vars . '%s. Please refresh this page and try again!', 'super-forms' ), '<strong>', '</strong>' ),
						)
					);
				}
			}
			$data = array();
			if ( ! empty( $_POST['data'] ) ) {
				$data = wp_unslash( $_POST['data'] );
				$data = json_decode( $data, true );
				$data = wp_slash( $data );
				unset( $_POST['data'] );
			}
			// @since 3.2.0
			// - If honeypot captcha field is not empty just cancel the request completely
			// - Also make sure to unset the field for saving, because we do not need this field to be saved
			if ( ! empty( $data['super_hp'] ) ) {
				exit;
			}
			unset( $data['super_hp'] );
			// Return extra data via ajax response
			$response_data = array();
			// Get form settings
			$form_id                  = absint( $_POST['form_id'] );
			$response_data['form_id'] = $form_id;
			$settings                 = SUPER_Common::get_form_settings( $form_id );
			// @since 4.4.0 - Let's unset some settings we don't need
			unset( $settings['theme_custom_js'] );
			unset( $settings['theme_custom_css'] );
			unset( $settings['form_custom_css'] );

			// @since 4.7.0 - translation
			$i18n = '';
			if ( isset( $_POST['i18n'] ) && ! empty( $_POST['i18n'] ) ) {
				$i18n = SUPER_Common::get_payload_i18n();
				if ( ( ! empty( $settings['i18n'] ) ) && ( ! empty( $settings['i18n'][ $i18n ] ) ) ) {
					$settings = array_replace_recursive( $settings, $settings['i18n'][ $i18n ] );
				}
			}
			unset( $settings['i18n'] );

			// @since 1.7.6
			$data = apply_filters(
				'super_before_sending_email_data_filter',
				$data,
				array(
					'i18n'     => $i18n,
					'data'     => $data,
					'post'     => $_POST,
					'settings' => $settings,
				)
			);

			// Before we continue we might want to alter the form settings
			$entry_id = ( isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : '' );
			$list_id  = ( isset( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : '' );
			$settings = apply_filters(
				'super_before_submit_form_settings_filter',
				$settings,
				array(
					'i18n'     => $i18n,
					'data'     => $data,
					'post'     => $_POST,
					'entry_id' => $entry_id,
					'list_id'  => $list_id,
				)
			);

			// @since 4.6.0 - verify reCAPTCHA token
			if ( $skipChecks === false ) {
				if ( ! empty( $_POST['version'] ) ) {
					$version = sanitize_text_field( $_POST['version'] );
					$secret  = $settings['form_recaptcha_secret'];
					if ( $version === 'v3' ) {
						$secret = $settings['form_recaptcha_v3_secret'];
					}
					$url  = 'https://www.google.com/recaptcha/api/siteverify';
					$args = array(
						'secret'   => $secret,
						'response' => $_POST['token'],
					);
					// @since 1.2.2   use wp_remote_post instead of file_get_contents because of the 15 sec. open connection on some hosts
					$response = wp_remote_post(
						$url,
						array(
							'timeout' => 45,
							'body'    => $args,
						)
					);
					if ( is_wp_error( $response ) ) {
						$error_message = $response->get_error_message();
						SUPER_Common::output_message(
							array(
								'msg' => esc_html__( 'Something went wrong:', 'super-forms' ) . ' ' . $error_message,
							)
						);
					} else {
						$result = json_decode( $response['body'], true );
						if ( $result['success'] !== true ) {
							SUPER_Common::output_message(
								array(
									'msg' => esc_html__( 'Google reCAPTCHA verification failed!', 'super-forms' ),
								)
							);
						}
					}
				}

				// @since 3.4.0 - Lock form after specific amount of submissions (based on total contact entries created)
				if ( ! empty( $settings['form_locker'] ) ) {
					if ( ! isset( $settings['form_locker_allow_submit'] ) ) {
						$settings['form_locker_allow_submit'] = 'false';
					}
					if ( $settings['form_locker_allow_submit'] !== 'true' ) {
						if ( ! isset( $settings['form_locker_limit'] ) ) {
							$settings['form_locker_limit'] = 0;
						}
						$limit = $settings['form_locker_limit'];
						$count = get_post_meta( $form_id, '_super_submission_count', true );
						if ( $count >= $limit ) {
							$msg = '';
							if ( $settings['form_locker_msg_title'] != '' ) {
								$msg .= '<h1>' . $settings['form_locker_msg_title'] . '</h1>';
							}
							$msg .= nl2br( $settings['form_locker_msg_desc'] );
							SUPER_Common::output_message(
								array(
									'msg' => $msg,
								)
							);
						}
					}
				}

				// @since 3.8.0 - Lock form after specific amount of submissions for logged in user (based on total contact entries created by user)
				if ( ! empty( $settings['user_form_locker'] ) ) {
					// Let's check if the user is logged in
					$current_user_id = get_current_user_id();
					if ( $current_user_id != 0 ) {
						if ( ! isset( $settings['user_form_locker_allow_submit'] ) ) {
							$settings['user_form_locker_allow_submit'] = 'false';
						}
						if ( $settings['user_form_locker_allow_submit'] !== 'true' ) {
							$user_limits = get_post_meta( $form_id, '_super_user_submission_counter', true );
							$count       = 0;
							if ( ! empty( $user_limits[ $current_user_id ] ) ) {
								$count = absint( $user_limits[ $current_user_id ] ) + 1;
							}
							$limit = 0;
							if ( ! empty( $settings['user_form_locker_limit'] ) ) {
								$limit = absint( $settings['user_form_locker_limit'] );
							}
							if ( $count > $limit ) {
								$msg = '';
								if ( $settings['user_form_locker_msg_title'] != '' ) {
									$msg .= '<h1>' . $settings['user_form_locker_msg_title'] . '</h1>';
								}
								$msg .= nl2br( $settings['user_form_locker_msg_desc'] );
								SUPER_Common::output_message(
									array(
										'msg' => $msg,
									)
								);
							}
						}
					}
				}
			}

			// Get/set unique submission identifier
			$sfsi_id = SUPER_Common::getClientData( 'unique_submission_id_' . $form_id );
			// error_log('Unique submission ID: '.$sfsi_id);
			if ( $sfsi_id === false ) {
				// error_log('Generate a new unique submission ID');
				$sfsi_id = md5( uniqid( mt_rand(), true ) );
				// error_log('Unique submission ID: '.$sfsi_id);
				// . '.' . $expires . '.'. $exp_var;
				$sfsi_id = SUPER_Common::setClientData(
					array(
						'name'  => 'unique_submission_id_' . $form_id,
						'value' => $sfsi_id,
					)
				);
			} else {
				// Update to increase expiry
				// error_log('Update session ID to increase expiry');
				$s = explode( '.', $sfsi_id );
				// error_log('@@@@@@@@DELETE _sfsi_.'.$sfsi_id);
				delete_option( '_sfsi_' . $s[0] . '.' . $s[1] );
				$sfsi_id = $s[0];
				$sfsi_id = SUPER_Common::setClientData(
					array(
						'name'  => 'unique_submission_id_' . $form_id,
						'value' => $sfsi_id,
					// 'expires' => 60*60, // 60 min. (30*60)
					// 'exp_var' => 20*60 // 20 min. (20*60)
					)
				);
			}
			// error_log('4: '.$i18n);
			$x    = array(
				'i18n'          => $i18n,
				'sfsi_id'       => $sfsi_id,
				'data'          => $data,
				'form_id'       => $form_id,
				'entry_id'      => $entry_id,
				'list_id'       => $list_id,
				'settings'      => $settings,
				'response_data' => $response_data,
				'post'          => $_POST,
			);
			$sfsi = $x;
			// error_log('SFSI $x: '.json_encode($x));
			// error_log('SFSI $x[sfsi_id]: '.json_encode($x['sfsi_id']));
			// error_log('SFSI $x[data]: '.json_encode($x['data']));
			// Store currently logged in user id
			$sfsi['user_id']   = get_current_user_id(); // currently logged in user ID
			$sfsi['referer']   = wp_get_referer(); // page URL before loading form page
			$sfsi['permalink'] = get_permalink(); // page URL user submitted the form from
			$files             = array();
			foreach ( $data as $k => $v ) {
				if ( isset( $v['type'] ) && $v['type'] === 'files' ) {
					foreach ( $v['files'] as $f ) {
						$x = array();
						if ( ! empty( $f['attachment'] ) ) {
							$x['attachment'] = $f['attachment'];
						}
						if ( ! empty( $f['path'] ) ) {
							$x['path'] = $f['path'];
						}
						if ( ! empty( $f['subdir'] ) ) {
							$x['subdir'] = $f['subdir'];
						}
						$files[] = $x;
					}
				}
			}
			$sfsi['files'] = $files;
			// unset($sfsi['settings']); // remove settings, not needed
			// error_log('@1 updating _sfsi_ data');
			if ( ! isset( $sfsi['i18n'] ) ) {
				// error_log('@ i18n for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['i18n'] = $i18n;
			}
			if ( ! isset( $sfsi['files'] ) ) {
				// error_log('@ files for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['files'] = $files;
			}
			if ( ! isset( $sfsi['sfsi_id'] ) ) {
				// error_log('@ sfs_uid for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['sfsi_id'] = $sfsi_id;
			}
			if ( ! isset( $sfsi['response_data'] ) ) {
				// error_log('@ response_data for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['response_data'] = $response_data;
			}
			if ( ! isset( $sfsi['data'] ) ) {
				// error_log('@ data for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['data'] = $data;
			}
			if ( ! isset( $sfsi['list_id'] ) ) {
				// error_log('@ list_id for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['list_id'] = $list_id;
			}
			if ( ! isset( $sfsi['entry_id'] ) ) {
				// error_log('@ entry_id for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['entry_id'] = $contact_entry_id;
			}
			if ( ! isset( $sfsi['form_id'] ) ) {
				// error_log('@ form_id for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['form_id'] = $form_id;
			}
			// error_log('@@@@@@@@UPDATE _sfsi_.'.$sfsi_id.': '.json_encode($sfsi));

			update_option( '_sfsi_' . $sfsi_id, $sfsi );

			// error_log('before trigger (sf.before.submission)');
			SUPER_Common::triggerEvent( 'sf.before.submission', $sfsi );
			// error_log('after trigger (sf.before.submission)');
			return $sfsi;
			/*
			array(
			'i18n' => $i18n,
			'sfsi_id' => $sfsi_id,
			'sfsi'=>$sfsi,
			'data'=>$data,
			'form_id'=>$form_id,
			'entry_id'=>$entry_id,
			'list_id'=>$list_id,
			'settings'=>$settings,
			'response_data'=>$response_data
			);
			*/
		}
		public static function upload_files() {
			$sfsi          = self::submit_form_checks();
			$i18n          = $sfsi['i18n'];
			$sfsi_id       = $sfsi['sfsi_id'];
			$form_id       = $sfsi['form_id'];
			$entry_id      = absint( $sfsi['entry_id'] );
			$list_id       = absint( $sfsi['list_id'] );
			$settings      = $sfsi['settings'];
			$response_data = $sfsi['response_data'];
			$data          = $sfsi['data'];
			$odata         = $data;
			// Dependencies for file upload
			$global_settings = SUPER_Common::get_global_settings();
			$defaults        = SUPER_Settings::get_defaults( $global_settings );
			$global_settings = array_merge( $defaults, $global_settings );
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$files         = ( isset( $_FILES['files'] ) ? $_FILES['files'] : array() );
			$wp_mime_types = wp_get_mime_types();
			$formEelements = get_post_meta( $form_id, '_super_elements', true );
			// Below mime types were grabbed from: http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
			$mime_types = array(
				'ez'          => 'application/andrew-inset',
				'aw'          => 'application/applixware',
				'atom'        => 'application/atom+xml',
				'atomcat'     => 'application/atomcat+xml',
				'atomsvc'     => 'application/atomsvc+xml',
				'ccxml'       => 'application/ccxml+xml',
				'cdmia'       => 'application/cdmi-capability',
				'cdmic'       => 'application/cdmi-container',
				'cdmid'       => 'application/cdmi-domain',
				'cdmio'       => 'application/cdmi-object',
				'cdmiq'       => 'application/cdmi-queue',
				'cu'          => 'application/cu-seeme',
				'davmount'    => 'application/davmount+xml',
				'dbk'         => 'application/docbook+xml',
				'dssc'        => 'application/dssc+der',
				'xdssc'       => 'application/dssc+xml',
				'ecma'        => 'application/ecmascript',
				'emma'        => 'application/emma+xml',
				'epub'        => 'application/epub+zip',
				'exi'         => 'application/exi',
				'pfr'         => 'application/font-tdpfr',
				'gml'         => 'application/gml+xml',
				'gpx'         => 'application/gpx+xml',
				'gxf'         => 'application/gxf',
				'stk'         => 'application/hyperstudio',
				'ink'         => 'application/inkml+xml',
				'inkml'       => 'application/inkml+xml',
				'ipfix'       => 'application/ipfix',
				'jar'         => 'application/java-archive',
				'ser'         => 'application/java-serialized-object',
				'json'        => 'application/json',
				'jsonml'      => 'application/jsonml+json',
				'lostxml'     => 'application/lost+xml',
				'hqx'         => 'application/mac-binhex40',
				'cpt'         => 'application/mac-compactpro',
				'mads'        => 'application/mads+xml',
				'mrc'         => 'application/marc',
				'mrcx'        => 'application/marcxml+xml',
				'nb'          => 'application/mathematica',
				'mathml'      => 'application/mathml+xml',
				'mbox'        => 'application/mbox',
				'mscml'       => 'application/mediaservercontrol+xml',
				'metalink'    => 'application/metalink+xml',
				'meta4'       => 'application/metalink4+xml',
				'mets'        => 'application/mets+xml',
				'mods'        => 'application/mods+xml',
				'm21'         => 'application/mp21',
				'mp21'        => 'application/mp21',
				'mp4s'        => 'application/mp4',
				'mxf'         => 'application/mxf',
				'bin'         => 'application/octet-stream',
				'dms'         => 'application/octet-stream',
				'lrf'         => 'application/octet-stream',
				'mar'         => 'application/octet-stream',
				'so'          => 'application/octet-stream',
				'dist'        => 'application/octet-stream',
				'distz'       => 'application/octet-stream',
				'bpk'         => 'application/octet-stream',
				'dump'        => 'application/octet-stream',
				'elc'         => 'application/octet-stream',
				'deploy'      => 'application/octet-stream',
				'oda'         => 'application/oda',
				'opf'         => 'application/oebps-package+xml',
				'ogx'         => 'application/ogg',
				'omdoc'       => 'application/omdoc+xml',
				'xer'         => 'application/patch-ops-error+xml',
				'pgp'         => 'application/pgp-encrypted',
				'sig'         => 'application/pgp-signature',
				'prf'         => 'application/pics-rules',
				'p10'         => 'application/pkcs10',
				'p7m'         => 'application/pkcs7-mime',
				'p7c'         => 'application/pkcs7-mime',
				'p7s'         => 'application/pkcs7-signature',
				'p8'          => 'application/pkcs8',
				'cer'         => 'application/pkix-cert',
				'crl'         => 'application/pkix-crl',
				'pkipath'     => 'application/pkix-pkipath',
				'pki'         => 'application/pkixcmp',
				'pls'         => 'application/pls+xml',
				'ai'          => 'application/postscript',
				'eps'         => 'application/postscript',
				'cww'         => 'application/prs.cww',
				'pskcxml'     => 'application/pskc+xml',
				'rdf'         => 'application/rdf+xml',
				'rif'         => 'application/reginfo+xml',
				'rnc'         => 'application/relax-ng-compact-syntax',
				'rl'          => 'application/resource-lists+xml',
				'rld'         => 'application/resource-lists-diff+xml',
				'gbr'         => 'application/rpki-ghostbusters',
				'mft'         => 'application/rpki-manifest',
				'roa'         => 'application/rpki-roa',
				'rsd'         => 'application/rsd+xml',
				'rss'         => 'application/rss+xml',
				'sbml'        => 'application/sbml+xml',
				'scq'         => 'application/scvp-cv-request',
				'scs'         => 'application/scvp-cv-response',
				'spq'         => 'application/scvp-vp-request',
				'spp'         => 'application/scvp-vp-response',
				'sdp'         => 'application/sdp',
				'setpay'      => 'application/set-payment-initiation',
				'setreg'      => 'application/set-registration-initiation',
				'shf'         => 'application/shf+xml',
				'smi'         => 'application/smil+xml',
				'smil'        => 'application/smil+xml',
				'rq'          => 'application/sparql-query',
				'srx'         => 'application/sparql-results+xml',
				'gram'        => 'application/srgs',
				'grxml'       => 'application/srgs+xml',
				'sru'         => 'application/sru+xml',
				'ssdl'        => 'application/ssdl+xml',
				'ssml'        => 'application/ssml+xml',
				'tei'         => 'application/tei+xml',
				'teicorpus'   => 'application/tei+xml',
				'tfi'         => 'application/thraud+xml',
				'tsd'         => 'application/timestamped-data',
				'plb'         => 'application/vnd.3gpp.pic-bw-large',
				'psb'         => 'application/vnd.3gpp.pic-bw-small',
				'pvb'         => 'application/vnd.3gpp.pic-bw-var',
				'tcap'        => 'application/vnd.3gpp2.tcap',
				'pwn'         => 'application/vnd.3m.post-it-notes',
				'aso'         => 'application/vnd.accpac.simply.aso',
				'imp'         => 'application/vnd.accpac.simply.imp',
				'acu'         => 'application/vnd.acucobol',
				'atc'         => 'application/vnd.acucorp',
				'acutc'       => 'application/vnd.acucorp',
				'air'         => 'application/vnd.adobe.air-application-installer-package+zip',
				'fcdt'        => 'application/vnd.adobe.formscentral.fcdt',
				'fxpl'        => 'application/vnd.adobe.fxp',
				'xdp'         => 'application/vnd.adobe.xdp+xml',
				'xfdf'        => 'application/vnd.adobe.xfdf',
				'ahead'       => 'application/vnd.ahead.space',
				'azf'         => 'application/vnd.airzip.filesecure.azf',
				'azs'         => 'application/vnd.airzip.filesecure.azs',
				'azw'         => 'application/vnd.amazon.ebook',
				'acc'         => 'application/vnd.americandynamics.acc',
				'ami'         => 'application/vnd.amiga.ami',
				'apk'         => 'application/vnd.android.package-archive',
				'cii'         => 'application/vnd.anser-web-certificate-issue-initiation',
				'fti'         => 'application/vnd.anser-web-funds-transfer-initiation',
				'atx'         => 'application/vnd.antix.game-component',
				'mpkg'        => 'application/vnd.apple.installer+xml',
				'm3u8'        => 'application/vnd.apple.mpegurl',
				'swi'         => 'application/vnd.aristanetworks.swi',
				'iota'        => 'application/vnd.astraea-software.iota',
				'aep'         => 'application/vnd.audiograph',
				'mpm'         => 'application/vnd.blueice.multipass',
				'bmi'         => 'application/vnd.bmi',
				'rep'         => 'application/vnd.businessobjects',
				'cdxml'       => 'application/vnd.chemdraw+xml',
				'mmd'         => 'application/vnd.chipnuts.karaoke-mmd',
				'cdy'         => 'application/vnd.cinderella',
				'rp9'         => 'application/vnd.cloanto.rp9',
				'c4g'         => 'application/vnd.clonk.c4group',
				'c4d'         => 'application/vnd.clonk.c4group',
				'c4f'         => 'application/vnd.clonk.c4group',
				'c4p'         => 'application/vnd.clonk.c4group',
				'c4u'         => 'application/vnd.clonk.c4group',
				'c11amc'      => 'application/vnd.cluetrust.cartomobile-config',
				'c11amz'      => 'application/vnd.cluetrust.cartomobile-config-pkg',
				'csp'         => 'application/vnd.commonspace',
				'cdbcmsg'     => 'application/vnd.contact.cmsg',
				'cmc'         => 'application/vnd.cosmocaller',
				'clkx'        => 'application/vnd.crick.clicker',
				'clkk'        => 'application/vnd.crick.clicker.keyboard',
				'clkp'        => 'application/vnd.crick.clicker.palette',
				'clkt'        => 'application/vnd.crick.clicker.template',
				'clkw'        => 'application/vnd.crick.clicker.wordbank',
				'wbs'         => 'application/vnd.criticaltools.wbs+xml',
				'pml'         => 'application/vnd.ctc-posml',
				'ppd'         => 'application/vnd.cups-ppd',
				'car'         => 'application/vnd.curl.car',
				'pcurl'       => 'application/vnd.curl.pcurl',
				'dart'        => 'application/vnd.dart',
				'rdz'         => 'application/vnd.data-vision.rdz',
				'uvf'         => 'application/vnd.dece.data',
				'uvvf'        => 'application/vnd.dece.data',
				'uvd'         => 'application/vnd.dece.data',
				'uvvd'        => 'application/vnd.dece.data',
				'uvt'         => 'application/vnd.dece.ttml+xml',
				'uvvt'        => 'application/vnd.dece.ttml+xml',
				'uvx'         => 'application/vnd.dece.unspecified',
				'uvvx'        => 'application/vnd.dece.unspecified',
				'uvz'         => 'application/vnd.dece.zip',
				'uvvz'        => 'application/vnd.dece.zip',
				'fe_launch'   => 'application/vnd.denovo.fcselayout-link',
				'dna'         => 'application/vnd.dna',
				'mlp'         => 'application/vnd.dolby.mlp',
				'dpg'         => 'application/vnd.dpgraph',
				'dfac'        => 'application/vnd.dreamfactory',
				'kpxx'        => 'application/vnd.ds-keypoint',
				'ait'         => 'application/vnd.dvb.ait',
				'svc'         => 'application/vnd.dvb.service',
				'geo'         => 'application/vnd.dynageo',
				'mag'         => 'application/vnd.ecowin.chart',
				'nml'         => 'application/vnd.enliven',
				'esf'         => 'application/vnd.epson.esf',
				'msf'         => 'application/vnd.epson.msf',
				'qam'         => 'application/vnd.epson.quickanime',
				'slt'         => 'application/vnd.epson.salt',
				'ssf'         => 'application/vnd.epson.ssf',
				'es3'         => 'application/vnd.eszigno3+xml',
				'et3'         => 'application/vnd.eszigno3+xml',
				'ez2'         => 'application/vnd.ezpix-album',
				'ez3'         => 'application/vnd.ezpix-package',
				'fdf'         => 'application/vnd.fdf',
				'mseed'       => 'application/vnd.fdsn.mseed',
				'seed'        => 'application/vnd.fdsn.seed',
				'dataless'    => 'application/vnd.fdsn.seed',
				'gph'         => 'application/vnd.flographit',
				'ftc'         => 'application/vnd.fluxtime.clip',
				'fm'          => 'application/vnd.framemaker',
				'frame'       => 'application/vnd.framemaker',
				'maker'       => 'application/vnd.framemaker',
				'book'        => 'application/vnd.framemaker',
				'fnc'         => 'application/vnd.frogans.fnc',
				'ltf'         => 'application/vnd.frogans.ltf',
				'fsc'         => 'application/vnd.fsc.weblaunch',
				'oas'         => 'application/vnd.fujitsu.oasys',
				'oa2'         => 'application/vnd.fujitsu.oasys2',
				'oa3'         => 'application/vnd.fujitsu.oasys3',
				'fg5'         => 'application/vnd.fujitsu.oasysgp',
				'bh2'         => 'application/vnd.fujitsu.oasysprs',
				'ddd'         => 'application/vnd.fujixerox.ddd',
				'xdw'         => 'application/vnd.fujixerox.docuworks',
				'xbd'         => 'application/vnd.fujixerox.docuworks.binder',
				'fzs'         => 'application/vnd.fuzzysheet',
				'txd'         => 'application/vnd.genomatix.tuxedo',
				'ggb'         => 'application/vnd.geogebra.file',
				'ggt'         => 'application/vnd.geogebra.tool',
				'gex'         => 'application/vnd.geometry-explorer',
				'gre'         => 'application/vnd.geometry-explorer',
				'gxt'         => 'application/vnd.geonext',
				'g2w'         => 'application/vnd.geoplan',
				'g3w'         => 'application/vnd.geospace',
				'gmx'         => 'application/vnd.gmx',
				'kml'         => 'application/vnd.google-earth.kml+xml',
				'kmz'         => 'application/vnd.google-earth.kmz',
				'gqf'         => 'application/vnd.grafeq',
				'gqs'         => 'application/vnd.grafeq',
				'gac'         => 'application/vnd.groove-account',
				'ghf'         => 'application/vnd.groove-help',
				'gim'         => 'application/vnd.groove-identity-message',
				'grv'         => 'application/vnd.groove-injector',
				'gtm'         => 'application/vnd.groove-tool-message',
				'tpl'         => 'application/vnd.groove-tool-template',
				'vcg'         => 'application/vnd.groove-vcard',
				'hal'         => 'application/vnd.hal+xml',
				'zmm'         => 'application/vnd.handheld-entertainment+xml',
				'hbci'        => 'application/vnd.hbci',
				'les'         => 'application/vnd.hhe.lesson-player',
				'hpgl'        => 'application/vnd.hp-hpgl',
				'hpid'        => 'application/vnd.hp-hpid',
				'hps'         => 'application/vnd.hp-hps',
				'jlt'         => 'application/vnd.hp-jlyt',
				'pcl'         => 'application/vnd.hp-pcl',
				'pclxl'       => 'application/vnd.hp-pclxl',
				'sfd-hdstx'   => 'application/vnd.hydrostatix.sof-data',
				'mpy'         => 'application/vnd.ibm.minipay',
				'afp'         => 'application/vnd.ibm.modcap',
				'listafp'     => 'application/vnd.ibm.modcap',
				'list3820'    => 'application/vnd.ibm.modcap',
				'irm'         => 'application/vnd.ibm.rights-management',
				'icc'         => 'application/vnd.iccprofile',
				'icm'         => 'application/vnd.iccprofile',
				'igl'         => 'application/vnd.igloader',
				'ivp'         => 'application/vnd.immervision-ivp',
				'ivu'         => 'application/vnd.immervision-ivu',
				'igm'         => 'application/vnd.insors.igm',
				'xpw'         => 'application/vnd.intercon.formnet',
				'xpx'         => 'application/vnd.intercon.formnet',
				'i2g'         => 'application/vnd.intergeo',
				'qbo'         => 'application/vnd.intu.qbo',
				'qfx'         => 'application/vnd.intu.qfx',
				'rcprofile'   => 'application/vnd.ipunplugged.rcprofile',
				'irp'         => 'application/vnd.irepository.package+xml',
				'xpr'         => 'application/vnd.is-xpr',
				'fcs'         => 'application/vnd.isac.fcs',
				'jam'         => 'application/vnd.jam',
				'rms'         => 'application/vnd.jcp.javame.midlet-rms',
				'jisp'        => 'application/vnd.jisp',
				'joda'        => 'application/vnd.joost.joda-archive',
				'ktz'         => 'application/vnd.kahootz',
				'ktr'         => 'application/vnd.kahootz',
				'karbon'      => 'application/vnd.kde.karbon',
				'chrt'        => 'application/vnd.kde.kchart',
				'kfo'         => 'application/vnd.kde.kformula',
				'flw'         => 'application/vnd.kde.kivio',
				'kon'         => 'application/vnd.kde.kontour',
				'kpr'         => 'application/vnd.kde.kpresenter',
				'kpt'         => 'application/vnd.kde.kpresenter',
				'ksp'         => 'application/vnd.kde.kspread',
				'kwd'         => 'application/vnd.kde.kword',
				'kwt'         => 'application/vnd.kde.kword',
				'htke'        => 'application/vnd.kenameaapp',
				'kia'         => 'application/vnd.kidspiration',
				'kne'         => 'application/vnd.kinar',
				'knp'         => 'application/vnd.kinar',
				'skp'         => 'application/vnd.koan',
				'skd'         => 'application/vnd.koan',
				'skt'         => 'application/vnd.koan',
				'skm'         => 'application/vnd.koan',
				'sse'         => 'application/vnd.kodak-descriptor',
				'lasxml'      => 'application/vnd.las.las+xml',
				'lbd'         => 'application/vnd.llamagraphics.life-balance.desktop',
				'lbe'         => 'application/vnd.llamagraphics.life-balance.exchange+xml',
				'123'         => 'application/vnd.lotus-1-2-3',
				'apr'         => 'application/vnd.lotus-approach',
				'pre'         => 'application/vnd.lotus-freelance',
				'nsf'         => 'application/vnd.lotus-notes',
				'org'         => 'application/vnd.lotus-organizer',
				'scm'         => 'application/vnd.lotus-screencam',
				'lwp'         => 'application/vnd.lotus-wordpro',
				'portpkg'     => 'application/vnd.macports.portpkg',
				'mcd'         => 'application/vnd.mcd',
				'mc1'         => 'application/vnd.medcalcdata',
				'cdkey'       => 'application/vnd.mediastation.cdkey',
				'mwf'         => 'application/vnd.mfer',
				'mfm'         => 'application/vnd.mfmp',
				'flo'         => 'application/vnd.micrografx.flo',
				'igx'         => 'application/vnd.micrografx.igx',
				'mif'         => 'application/vnd.mif',
				'daf'         => 'application/vnd.mobius.daf',
				'dis'         => 'application/vnd.mobius.dis',
				'mbk'         => 'application/vnd.mobius.mbk',
				'mqy'         => 'application/vnd.mobius.mqy',
				'msl'         => 'application/vnd.mobius.msl',
				'plc'         => 'application/vnd.mobius.plc',
				'txf'         => 'application/vnd.mobius.txf',
				'mpn'         => 'application/vnd.mophun.application',
				'mpc'         => 'application/vnd.mophun.certificate',
				'xul'         => 'application/vnd.mozilla.xul+xml',
				'cil'         => 'application/vnd.ms-artgalry',
				'cab'         => 'application/vnd.ms-cab-compressed',
				'xlm'         => 'application/vnd.ms-excel',
				'xlc'         => 'application/vnd.ms-excel',
				'eot'         => 'application/vnd.ms-fontobject',
				'chm'         => 'application/vnd.ms-htmlhelp',
				'ims'         => 'application/vnd.ms-ims',
				'lrm'         => 'application/vnd.ms-lrm',
				'thmx'        => 'application/vnd.ms-officetheme',
				'cat'         => 'application/vnd.ms-pki.seccat',
				'stl'         => 'application/vnd.ms-pki.stl',
				'mpt'         => 'application/vnd.ms-project',
				'wps'         => 'application/vnd.ms-works',
				'wks'         => 'application/vnd.ms-works',
				'wcm'         => 'application/vnd.ms-works',
				'wdb'         => 'application/vnd.ms-works',
				'wpl'         => 'application/vnd.ms-wpl',
				'mseq'        => 'application/vnd.mseq',
				'mus'         => 'application/vnd.musician',
				'msty'        => 'application/vnd.muvee.style',
				'taglet'      => 'application/vnd.mynfc',
				'nlu'         => 'application/vnd.neurolanguage.nlu',
				'ntf'         => 'application/vnd.nitf',
				'nitf'        => 'application/vnd.nitf',
				'nnd'         => 'application/vnd.noblenet-directory',
				'nns'         => 'application/vnd.noblenet-sealer',
				'nnw'         => 'application/vnd.noblenet-web',
				'ngdat'       => 'application/vnd.nokia.n-gage.data',
				'n-gage'      => 'application/vnd.nokia.n-gage.symbian.install',
				'rpst'        => 'application/vnd.nokia.radio-preset',
				'rpss'        => 'application/vnd.nokia.radio-presets',
				'edm'         => 'application/vnd.novadigm.edm',
				'edx'         => 'application/vnd.novadigm.edx',
				'ext'         => 'application/vnd.novadigm.ext',
				'otc'         => 'application/vnd.oasis.opendocument.chart-template',
				'odft'        => 'application/vnd.oasis.opendocument.formula-template',
				'otg'         => 'application/vnd.oasis.opendocument.graphics-template',
				'odi'         => 'application/vnd.oasis.opendocument.image',
				'oti'         => 'application/vnd.oasis.opendocument.image-template',
				'otp'         => 'application/vnd.oasis.opendocument.presentation-template',
				'ots'         => 'application/vnd.oasis.opendocument.spreadsheet-template',
				'odm'         => 'application/vnd.oasis.opendocument.text-master',
				'ott'         => 'application/vnd.oasis.opendocument.text-template',
				'oth'         => 'application/vnd.oasis.opendocument.text-web',
				'xo'          => 'application/vnd.olpc-sugar',
				'dd2'         => 'application/vnd.oma.dd2+xml',
				'oxt'         => 'application/vnd.openofficeorg.extension',
				'mgp'         => 'application/vnd.osgeo.mapguide.package',
				'esa'         => 'application/vnd.osgi.subsystem',
				'pdb'         => 'application/vnd.palm',
				'pqa'         => 'application/vnd.palm',
				'oprc'        => 'application/vnd.palm',
				'paw'         => 'application/vnd.pawaafile',
				'str'         => 'application/vnd.pg.format',
				'ei6'         => 'application/vnd.pg.osasli',
				'efif'        => 'application/vnd.picsel',
				'wg'          => 'application/vnd.pmi.widget',
				'plf'         => 'application/vnd.pocketlearn',
				'pbd'         => 'application/vnd.powerbuilder6',
				'box'         => 'application/vnd.previewsystems.box',
				'mgz'         => 'application/vnd.proteus.magazine',
				'qps'         => 'application/vnd.publishare-delta-tree',
				'ptid'        => 'application/vnd.pvi.ptid1',
				'qxd'         => 'application/vnd.quark.quarkxpress',
				'qxt'         => 'application/vnd.quark.quarkxpress',
				'qwd'         => 'application/vnd.quark.quarkxpress',
				'qwt'         => 'application/vnd.quark.quarkxpress',
				'qxl'         => 'application/vnd.quark.quarkxpress',
				'qxb'         => 'application/vnd.quark.quarkxpress',
				'bed'         => 'application/vnd.realvnc.bed',
				'mxl'         => 'application/vnd.recordare.musicxml',
				'musicxml'    => 'application/vnd.recordare.musicxml+xml',
				'cryptonote'  => 'application/vnd.rig.cryptonote',
				'cod'         => 'application/vnd.rim.cod',
				'rm'          => 'application/vnd.rn-realmedia',
				'rmvb'        => 'application/vnd.rn-realmedia-vbr',
				'link66'      => 'application/vnd.route66.link66+xml',
				'st'          => 'application/vnd.sailingtracker.track',
				'see'         => 'application/vnd.seemail',
				'sema'        => 'application/vnd.sema',
				'semd'        => 'application/vnd.semd',
				'semf'        => 'application/vnd.semf',
				'ifm'         => 'application/vnd.shana.informed.formdata',
				'itp'         => 'application/vnd.shana.informed.formtemplate',
				'iif'         => 'application/vnd.shana.informed.interchange',
				'ipk'         => 'application/vnd.shana.informed.package',
				'twd'         => 'application/vnd.simtech-mindmapper',
				'twds'        => 'application/vnd.simtech-mindmapper',
				'mmf'         => 'application/vnd.smaf',
				'teacher'     => 'application/vnd.smart.teacher',
				'sdkm'        => 'application/vnd.solent.sdkm+xml',
				'sdkd'        => 'application/vnd.solent.sdkm+xml',
				'dxp'         => 'application/vnd.spotfire.dxp',
				'sfs'         => 'application/vnd.spotfire.sfs',
				'sdc'         => 'application/vnd.stardivision.calc',
				'sda'         => 'application/vnd.stardivision.draw',
				'sdd'         => 'application/vnd.stardivision.impress',
				'smf'         => 'application/vnd.stardivision.math',
				'sdw'         => 'application/vnd.stardivision.writer',
				'vor'         => 'application/vnd.stardivision.writer',
				'sgl'         => 'application/vnd.stardivision.writer-global',
				'smzip'       => 'application/vnd.stepmania.package',
				'sxc'         => 'application/vnd.sun.xml.calc',
				'stc'         => 'application/vnd.sun.xml.calc.template',
				'sxd'         => 'application/vnd.sun.xml.draw',
				'std'         => 'application/vnd.sun.xml.draw.template',
				'sxi'         => 'application/vnd.sun.xml.impress',
				'sti'         => 'application/vnd.sun.xml.impress.template',
				'sxm'         => 'application/vnd.sun.xml.math',
				'sxw'         => 'application/vnd.sun.xml.writer',
				'sxg'         => 'application/vnd.sun.xml.writer.global',
				'stw'         => 'application/vnd.sun.xml.writer.template',
				'sus'         => 'application/vnd.sus-calendar',
				'susp'        => 'application/vnd.sus-calendar',
				'svd'         => 'application/vnd.svd',
				'sis'         => 'application/vnd.symbian.install',
				'sisx'        => 'application/vnd.symbian.install',
				'xsm'         => 'application/vnd.syncml+xml',
				'bdm'         => 'application/vnd.syncml.dm+wbxml',
				'xdm'         => 'application/vnd.syncml.dm+xml',
				'tao'         => 'application/vnd.tao.intent-module-archive',
				'pcap'        => 'application/vnd.tcpdump.pcap',
				'cap'         => 'application/vnd.tcpdump.pcap',
				'dmp'         => 'application/vnd.tcpdump.pcap',
				'tmo'         => 'application/vnd.tmobile-livetv',
				'tpt'         => 'application/vnd.trid.tpt',
				'mxs'         => 'application/vnd.triscape.mxs',
				'tra'         => 'application/vnd.trueapp',
				'ufd'         => 'application/vnd.ufdl',
				'ufdl'        => 'application/vnd.ufdl',
				'utz'         => 'application/vnd.uiq.theme',
				'umj'         => 'application/vnd.umajin',
				'unityweb'    => 'application/vnd.unity',
				'uoml'        => 'application/vnd.uoml+xml',
				'vcx'         => 'application/vnd.vcx',
				'vsd'         => 'application/vnd.visio',
				'vst'         => 'application/vnd.visio',
				'vss'         => 'application/vnd.visio',
				'vsw'         => 'application/vnd.visio',
				'vis'         => 'application/vnd.visionary',
				'vsf'         => 'application/vnd.vsf',
				'wbxml'       => 'application/vnd.wap.wbxml',
				'wmlc'        => 'application/vnd.wap.wmlc',
				'wmlsc'       => 'application/vnd.wap.wmlscriptc',
				'wtb'         => 'application/vnd.webturbo',
				'nbp'         => 'application/vnd.wolfram.player',
				'wqd'         => 'application/vnd.wqd',
				'stf'         => 'application/vnd.wt.stf',
				'xar'         => 'application/vnd.xara',
				'xfdl'        => 'application/vnd.xfdl',
				'hvd'         => 'application/vnd.yamaha.hv-dic',
				'hvs'         => 'application/vnd.yamaha.hv-script',
				'hvp'         => 'application/vnd.yamaha.hv-voice',
				'osf'         => 'application/vnd.yamaha.openscoreformat',
				'osfpvg'      => 'application/vnd.yamaha.openscoreformat.osfpvg+xml',
				'saf'         => 'application/vnd.yamaha.smaf-audio',
				'spf'         => 'application/vnd.yamaha.smaf-phrase',
				'cmp'         => 'application/vnd.yellowriver-custom-menu',
				'zir'         => 'application/vnd.zul',
				'zirz'        => 'application/vnd.zul',
				'zaz'         => 'application/vnd.zzazz.deck+xml',
				'vxml'        => 'application/voicexml+xml',
				'wgt'         => 'application/widget',
				'hlp'         => 'application/winhlp',
				'wsdl'        => 'application/wsdl+xml',
				'wspolicy'    => 'application/wspolicy+xml',
				'abw'         => 'application/x-abiword',
				'ace'         => 'application/x-ace-compressed',
				'dmg'         => 'application/x-apple-diskimage',
				'aab'         => 'application/x-authorware-bin',
				'x32'         => 'application/x-authorware-bin',
				'u32'         => 'application/x-authorware-bin',
				'vox'         => 'application/x-authorware-bin',
				'aam'         => 'application/x-authorware-map',
				'aas'         => 'application/x-authorware-seg',
				'bcpio'       => 'application/x-bcpio',
				'torrent'     => 'application/x-bittorrent',
				'blb'         => 'application/x-blorb',
				'blorb'       => 'application/x-blorb',
				'bz'          => 'application/x-bzip',
				'bz2'         => 'application/x-bzip2',
				'boz'         => 'application/x-bzip2',
				'cbr'         => 'application/x-cbr',
				'cba'         => 'application/x-cbr',
				'cbt'         => 'application/x-cbr',
				'cbz'         => 'application/x-cbr',
				'cb7'         => 'application/x-cbr',
				'vcd'         => 'application/x-cdlink',
				'cfs'         => 'application/x-cfs-compressed',
				'chat'        => 'application/x-chat',
				'pgn'         => 'application/x-chess-pgn',
				'nsc'         => 'application/x-conference',
				'cpio'        => 'application/x-cpio',
				'csh'         => 'application/x-csh',
				'deb'         => 'application/x-debian-package',
				'udeb'        => 'application/x-debian-package',
				'dgc'         => 'application/x-dgc-compressed',
				'dir'         => 'application/x-director',
				'dcr'         => 'application/x-director',
				'dxr'         => 'application/x-director',
				'cst'         => 'application/x-director',
				'cct'         => 'application/x-director',
				'cxt'         => 'application/x-director',
				'w3d'         => 'application/x-director',
				'fgd'         => 'application/x-director',
				'swa'         => 'application/x-director',
				'wad'         => 'application/x-doom',
				'ncx'         => 'application/x-dtbncx+xml',
				'dtb'         => 'application/x-dtbook+xml',
				'res'         => 'application/x-dtbresource+xml',
				'dvi'         => 'application/x-dvi',
				'evy'         => 'application/x-envoy',
				'eva'         => 'application/x-eva',
				'bdf'         => 'application/x-font-bdf',
				'gsf'         => 'application/x-font-ghostscript',
				'psf'         => 'application/x-font-linux-psf',
				'pcf'         => 'application/x-font-pcf',
				'snf'         => 'application/x-font-snf',
				'pfa'         => 'application/x-font-type1',
				'pfb'         => 'application/x-font-type1',
				'pfm'         => 'application/x-font-type1',
				'afm'         => 'application/x-font-type1',
				'arc'         => 'application/x-freearc',
				'spl'         => 'application/x-futuresplash',
				'gca'         => 'application/x-gca-compressed',
				'ulx'         => 'application/x-glulx',
				'gnumeric'    => 'application/x-gnumeric',
				'gramps'      => 'application/x-gramps-xml',
				'gtar'        => 'application/x-gtar',
				'hdf'         => 'application/x-hdf',
				'install'     => 'application/x-install-instructions',
				'iso'         => 'application/x-iso9660-image',
				'jnlp'        => 'application/x-java-jnlp-file',
				'latex'       => 'application/x-latex',
				'lzh'         => 'application/x-lzh-compressed',
				'lha'         => 'application/x-lzh-compressed',
				'mie'         => 'application/x-mie',
				'prc'         => 'application/x-mobipocket-ebook',
				'mobi'        => 'application/x-mobipocket-ebook',
				'application' => 'application/x-ms-application',
				'lnk'         => 'application/x-ms-shortcut',
				'wmd'         => 'application/x-ms-wmd',
				'wmz'         => 'application/x-ms-wmz',
				'xbap'        => 'application/x-ms-xbap',
				'obd'         => 'application/x-msbinder',
				'crd'         => 'application/x-mscardfile',
				'clp'         => 'application/x-msclip',
				'dll'         => 'application/x-msdownload',
				'com'         => 'application/x-msdownload',
				'bat'         => 'application/x-msdownload',
				'msi'         => 'application/x-msdownload',
				'mvb'         => 'application/x-msmediaview',
				'm13'         => 'application/x-msmediaview',
				'm14'         => 'application/x-msmediaview',
				'wmf'         => 'application/x-msmetafile',
				'wmz'         => 'application/x-msmetafile',
				'emf'         => 'application/x-msmetafile',
				'emz'         => 'application/x-msmetafile',
				'mny'         => 'application/x-msmoney',
				'pub'         => 'application/x-mspublisher',
				'scd'         => 'application/x-msschedule',
				'trm'         => 'application/x-msterminal',
				'nc'          => 'application/x-netcdf',
				'cdf'         => 'application/x-netcdf',
				'nzb'         => 'application/x-nzb',
				'p12'         => 'application/x-pkcs12',
				'pfx'         => 'application/x-pkcs12',
				'p7b'         => 'application/x-pkcs7-certificates',
				'spc'         => 'application/x-pkcs7-certificates',
				'p7r'         => 'application/x-pkcs7-certreqresp',
				'ris'         => 'application/x-research-info-systems',
				'sh'          => 'application/x-sh',
				'shar'        => 'application/x-shar',
				'xap'         => 'application/x-silverlight-app',
				'sql'         => 'application/x-sql',
				'sit'         => 'application/x-stuffit',
				'sitx'        => 'application/x-stuffitx',
				'sv4cpio'     => 'application/x-sv4cpio',
				'sv4crc'      => 'application/x-sv4crc',
				't3'          => 'application/x-t3vm-image',
				'gam'         => 'application/x-tads',
				'tcl'         => 'application/x-tcl',
				'tex'         => 'application/x-tex',
				'tfm'         => 'application/x-tex-tfm',
				'texinfo'     => 'application/x-texinfo',
				'texi'        => 'application/x-texinfo',
				'obj'         => 'application/x-tgif',
				'ustar'       => 'application/x-ustar',
				'src'         => 'application/x-wais-source',
				'der'         => 'application/x-x509-ca-cert',
				'crt'         => 'application/x-x509-ca-cert',
				'fig'         => 'application/x-xfig',
				'xlf'         => 'application/x-xliff+xml',
				'xpi'         => 'application/x-xpinstall',
				'xz'          => 'application/x-xz',
				'z1'          => 'application/x-zmachine',
				'z2'          => 'application/x-zmachine',
				'z3'          => 'application/x-zmachine',
				'z4'          => 'application/x-zmachine',
				'z5'          => 'application/x-zmachine',
				'z6'          => 'application/x-zmachine',
				'z7'          => 'application/x-zmachine',
				'z8'          => 'application/x-zmachine',
				'xaml'        => 'application/xaml+xml',
				'xdf'         => 'application/xcap-diff+xml',
				'xenc'        => 'application/xenc+xml',
				'xhtml'       => 'application/xhtml+xml',
				'xht'         => 'application/xhtml+xml',
				'xml'         => 'application/xml',
				'xsl'         => 'application/xml',
				'dtd'         => 'application/xml-dtd',
				'xop'         => 'application/xop+xml',
				'xpl'         => 'application/xproc+xml',
				'xslt'        => 'application/xslt+xml',
				'xspf'        => 'application/xspf+xml',
				'mxml'        => 'application/xv+xml',
				'xhvml'       => 'application/xv+xml',
				'xvml'        => 'application/xv+xml',
				'xvm'         => 'application/xv+xml',
				'yang'        => 'application/yang',
				'yin'         => 'application/yin+xml',
				'adp'         => 'audio/adpcm',
				'au'          => 'audio/basic',
				'snd'         => 'audio/basic',
				'kar'         => 'audio/midi',
				'rmi'         => 'audio/midi',
				'mp4a'        => 'audio/mp4',
				'mpga'        => 'audio/mpeg',
				'mp2'         => 'audio/mpeg',
				'mp2a'        => 'audio/mpeg',
				'm2a'         => 'audio/mpeg',
				'm3a'         => 'audio/mpeg',
				'spx'         => 'audio/ogg',
				'opus'        => 'audio/ogg',
				's3m'         => 'audio/s3m',
				'sil'         => 'audio/silk',
				'uva'         => 'audio/vnd.dece.audio',
				'uvva'        => 'audio/vnd.dece.audio',
				'eol'         => 'audio/vnd.digital-winds',
				'dra'         => 'audio/vnd.dra',
				'dts'         => 'audio/vnd.dts',
				'dtshd'       => 'audio/vnd.dts.hd',
				'lvp'         => 'audio/vnd.lucent.voice',
				'pya'         => 'audio/vnd.ms-playready.media.pya',
				'ecelp4800'   => 'audio/vnd.nuera.ecelp4800',
				'ecelp7470'   => 'audio/vnd.nuera.ecelp7470',
				'ecelp9600'   => 'audio/vnd.nuera.ecelp9600',
				'rip'         => 'audio/vnd.rip',
				'weba'        => 'audio/webm',
				'aif'         => 'audio/x-aiff',
				'aiff'        => 'audio/x-aiff',
				'aifc'        => 'audio/x-aiff',
				'caf'         => 'audio/x-caf',
				'm3u'         => 'audio/x-mpegurl',
				'rmp'         => 'audio/x-pn-realaudio-plugin',
				'xm'          => 'audio/xm',
				'cdx'         => 'chemical/x-cdx',
				'cif'         => 'chemical/x-cif',
				'cmdf'        => 'chemical/x-cmdf',
				'cml'         => 'chemical/x-cml',
				'csml'        => 'chemical/x-csml',
				'xyz'         => 'chemical/x-xyz',
				'ttc'         => 'font/collection',
				'otf'         => 'font/otf',
				'ttf'         => 'font/ttf',
				'woff'        => 'font/woff',
				'woff2'       => 'font/woff2',
				'cgm'         => 'image/cgm',
				'g3'          => 'image/g3fax',
				'ief'         => 'image/ief',
				'ktx'         => 'image/ktx',
				'btif'        => 'image/prs.btif',
				'sgi'         => 'image/sgi',
				'svg'         => 'image/svg+xml',
				'svgz'        => 'image/svg+xml',
				'uvi'         => 'image/vnd.dece.graphic',
				'uvvi'        => 'image/vnd.dece.graphic',
				'uvg'         => 'image/vnd.dece.graphic',
				'uvvg'        => 'image/vnd.dece.graphic',
				'djvu'        => 'image/vnd.djvu',
				'djv'         => 'image/vnd.djvu',
				'sub'         => 'image/vnd.dvb.subtitle',
				'dwg'         => 'image/vnd.dwg',
				'dxf'         => 'image/vnd.dxf',
				'fbs'         => 'image/vnd.fastbidsheet',
				'fpx'         => 'image/vnd.fpx',
				'fst'         => 'image/vnd.fst',
				'mmr'         => 'image/vnd.fujixerox.edmics-mmr',
				'rlc'         => 'image/vnd.fujixerox.edmics-rlc',
				'mdi'         => 'image/vnd.ms-modi',
				'wdp'         => 'image/vnd.ms-photo',
				'npx'         => 'image/vnd.net-fpx',
				'wbmp'        => 'image/vnd.wap.wbmp',
				'xif'         => 'image/vnd.xiff',
				'3ds'         => 'image/x-3ds',
				'ras'         => 'image/x-cmu-raster',
				'cmx'         => 'image/x-cmx',
				'fh'          => 'image/x-freehand',
				'fhc'         => 'image/x-freehand',
				'fh4'         => 'image/x-freehand',
				'fh5'         => 'image/x-freehand',
				'fh7'         => 'image/x-freehand',
				'sid'         => 'image/x-mrsid-image',
				'pcx'         => 'image/x-pcx',
				'pic'         => 'image/x-pict',
				'pct'         => 'image/x-pict',
				'pnm'         => 'image/x-portable-anymap',
				'pbm'         => 'image/x-portable-bitmap',
				'pgm'         => 'image/x-portable-graymap',
				'ppm'         => 'image/x-portable-pixmap',
				'rgb'         => 'image/x-rgb',
				'tga'         => 'image/x-tga',
				'xbm'         => 'image/x-xbitmap',
				'xpm'         => 'image/x-xpixmap',
				'xwd'         => 'image/x-xwindowdump',
				'eml'         => 'message/rfc822',
				'mime'        => 'message/rfc822',
				'igs'         => 'model/iges',
				'iges'        => 'model/iges',
				'msh'         => 'model/mesh',
				'mesh'        => 'model/mesh',
				'silo'        => 'model/mesh',
				'dae'         => 'model/vnd.collada+xml',
				'dwf'         => 'model/vnd.dwf',
				'gdl'         => 'model/vnd.gdl',
				'gtw'         => 'model/vnd.gtw',
				'mts'         => 'model/vnd.mts',
				'vtu'         => 'model/vnd.vtu',
				'wrl'         => 'model/vrml',
				'vrml'        => 'model/vrml',
				'x3db'        => 'model/x3d+binary',
				'x3dbz'       => 'model/x3d+binary',
				'x3dv'        => 'model/x3d+vrml',
				'x3dvz'       => 'model/x3d+vrml',
				'x3d'         => 'model/x3d+xml',
				'x3dz'        => 'model/x3d+xml',
				'appcache'    => 'text/cache-manifest',
				'ifb'         => 'text/calendar',
				'n3'          => 'text/n3',
				'text'        => 'text/plain',
				'conf'        => 'text/plain',
				'def'         => 'text/plain',
				'list'        => 'text/plain',
				'log'         => 'text/plain',
				'in'          => 'text/plain',
				'dsc'         => 'text/prs.lines.tag',
				'sgml'        => 'text/sgml',
				'sgm'         => 'text/sgml',
				'tr'          => 'text/troff',
				'roff'        => 'text/troff',
				'man'         => 'text/troff',
				'me'          => 'text/troff',
				'ms'          => 'text/troff',
				'ttl'         => 'text/turtle',
				'uri'         => 'text/uri-list',
				'uris'        => 'text/uri-list',
				'urls'        => 'text/uri-list',
				'vcard'       => 'text/vcard',
				'curl'        => 'text/vnd.curl',
				'dcurl'       => 'text/vnd.curl.dcurl',
				'mcurl'       => 'text/vnd.curl.mcurl',
				'scurl'       => 'text/vnd.curl.scurl',
				'sub'         => 'text/vnd.dvb.subtitle',
				'fly'         => 'text/vnd.fly',
				'flx'         => 'text/vnd.fmi.flexstor',
				'3dml'        => 'text/vnd.in3d.3dml',
				'spot'        => 'text/vnd.in3d.spot',
				'jad'         => 'text/vnd.sun.j2me.app-descriptor',
				'wml'         => 'text/vnd.wap.wml',
				'wmls'        => 'text/vnd.wap.wmlscript',
				'asm'         => 'text/x-asm',
				'cxx'         => 'text/x-c',
				'cpp'         => 'text/x-c',
				'hh'          => 'text/x-c',
				'dic'         => 'text/x-c',
				'for'         => 'text/x-fortran',
				'f77'         => 'text/x-fortran',
				'f90'         => 'text/x-fortran',
				'java'        => 'text/x-java-source',
				'nfo'         => 'text/x-nfo',
				'opml'        => 'text/x-opml',
				'pas'         => 'text/x-pascal',
				'etx'         => 'text/x-setext',
				'sfv'         => 'text/x-sfv',
				'uu'          => 'text/x-uuencode',
				'vcs'         => 'text/x-vcalendar',
				'vcf'         => 'text/x-vcard',
				'h261'        => 'video/h261',
				'h263'        => 'video/h263',
				'h264'        => 'video/h264',
				'jpgv'        => 'video/jpeg',
				'jpm'         => 'video/jpm',
				'jpgm'        => 'video/jpm',
				'mj2'         => 'video/mj2',
				'mjp2'        => 'video/mj2',
				'mp4v'        => 'video/mp4',
				'mpg4'        => 'video/mp4',
				'm1v'         => 'video/mpeg',
				'm2v'         => 'video/mpeg',
				'uvh'         => 'video/vnd.dece.hd',
				'uvvh'        => 'video/vnd.dece.hd',
				'uvm'         => 'video/vnd.dece.mobile',
				'uvvm'        => 'video/vnd.dece.mobile',
				'uvp'         => 'video/vnd.dece.pd',
				'uvvp'        => 'video/vnd.dece.pd',
				'uvs'         => 'video/vnd.dece.sd',
				'uvvs'        => 'video/vnd.dece.sd',
				'uvv'         => 'video/vnd.dece.video',
				'uvvv'        => 'video/vnd.dece.video',
				'dvb'         => 'video/vnd.dvb.file',
				'fvt'         => 'video/vnd.fvt',
				'mxu'         => 'video/vnd.mpegurl',
				'm4u'         => 'video/vnd.mpegurl',
				'pyv'         => 'video/vnd.ms-playready.media.pyv',
				'uvu'         => 'video/vnd.uvvu.mp4',
				'uvvu'        => 'video/vnd.uvvu.mp4',
				'viv'         => 'video/vnd.vivo',
				'f4v'         => 'video/x-f4v',
				'fli'         => 'video/x-fli',
				'mk3d'        => 'video/x-matroska',
				'mks'         => 'video/x-matroska',
				'mng'         => 'video/x-mng',
				'vob'         => 'video/x-ms-vob',
				'wvx'         => 'video/x-ms-wvx',
				'movie'       => 'video/x-sgi-movie',
				'smv'         => 'video/x-smv',
				'ice'         => 'x-conference/x-cooltalk',
			);
			// Merge with WP mime types
			$mime_types = array_merge( $wp_mime_types, $mime_types );
			// Allow developers to filter mime types
			$mime_types = apply_filters( 'super_file_upload_mime_types_validation', $mime_types );
			$str        = SUPER_Common::safe_json_encode( $formEelements );
			if ( ! empty( $files ) ) {
				foreach ( $files['name'] as $fieldName => $fileInfo ) {
					$re = '/"data":{"name":"' . $fieldName . '".*?extensions":"(.*?)"/m';
					preg_match( $re, $str, $matches, PREG_OFFSET_CAPTURE, 0 );
					$extensions = array();
					$extensions = 'jpg|jpeg|png|gif|pdf';
					if ( ! empty( $matches ) && ! empty( $matches[1] ) && ! empty( $matches[1][0] ) ) {
						$extensions = $matches[1][0];
					}
					$extensions         = explode( '|', strtolower( $extensions ) );
					$allowed_mime_types = array();
					foreach ( $extensions as $ext ) {
						$key = current( preg_grep( '/' . $ext . '/', array_keys( $mime_types ) ) );
						if ( $key ) {
							$allowed_mime_types[ $ext ] = $mime_types[ $key ];
						}
					}
					$GLOBALS['super_allowed_mime_types'] = $allowed_mime_types;
					add_filter(
						'upload_mimes',
						function ( $mime_types ) {
							return $GLOBALS['super_allowed_mime_types'];
						}
					);
					add_filter(
						'wp_check_filetype_and_ext',
						function ( $types, $file, $filename, $mimes ) {
							// Do basic extension validation and MIME mapping
							$wp_filetype        = wp_check_filetype( $filename, $mimes );
							$ext                = $wp_filetype['ext'];
							$type               = $wp_filetype['type'];
							$allowed_mime_types = $GLOBALS['super_allowed_mime_types'];
							$key                = current( preg_grep( '/' . $ext . '/', array_keys( $allowed_mime_types ) ) );
							if ( $key ) {
								$types['ext']  = $ext;
								$types['type'] = $type;
							}
							return $types;
						},
						99,
						4
					);

					$re = '/"data":{"name":"' . $fieldName . '".*?filesize":"(.*?)"/m';
					preg_match( $re, $str, $matches, PREG_OFFSET_CAPTURE, 0 );
					$extensions  = array();
					$maxFileSize = 5 * 1000000; // defaults to 5 MB
					if ( ! empty( $matches ) && ! empty( $matches[1] ) && ! empty( $matches[1][0] ) ) {
						$fileSize    = SUPER_Common::tofloat( $matches[1][0] );
						$maxFileSize = $fileSize * 1000000; // e.g (5 * 1000000) = 5 MB
					}
					// Get allowed mime types based on this field
					foreach ( $fileInfo as $k => $v ) {
						$file = array(
							'name'     => $files['name'][ $fieldName ][ $k ],
							'type'     => $files['type'][ $fieldName ][ $k ],
							'tmp_name' => $files['tmp_name'][ $fieldName ][ $k ],
							'error'    => $files['error'][ $fieldName ][ $k ],
							'size'     => $files['size'][ $fieldName ][ $k ],
						);
						// Check file size
						if ( $file['size'] > $maxFileSize ) {
							SUPER_Common::output_message(
								array(
									'msg' => esc_html__( 'The file size exceeded the filesize limitation of ' . $fileSize . ' MB.', 'super-forms' ),
								)
							);
						}
						unset( $GLOBALS['super_upload_dir'] );
						add_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ) );
						if ( empty( $GLOBALS['super_upload_dir'] ) ) {
							// upload directory is altered by filter: SUPER_Forms::filter_upload_dir()
							$GLOBALS['super_upload_dir'] = wp_upload_dir();
						}
						$d = $GLOBALS['super_upload_dir'];

						/*
						add_action('wp_handle_upload', function($file){
						/*
						$image = imagecreatefromstring(file_get_contents($file['tmp_name']));
						$exif = exif_read_data($_FILES['image_upload']['tmp_name']);
						if(!empty($exif['Orientation'])) {
							switch($exif['Orientation']) {
								case 8:
									$image = imagerotate($image, 90, 0);
									break;
								case 3:
									$image = imagerotate($image, 180, 0);
									break;
								case 6:
									$image = imagerotate($image, -90, 0);
									break;
							}
						}
						*/
						/*
						$image = wp_get_image_editor($file['file']);
						if(!is_wp_error($image)){
							$exif = exif_read_data($file['file']);
							$orientation = $exif['Orientation'];
							if (!empty($orientation)) {
								switch ($orientation) {
									case 8:
										$image->rotate(90);
										break;
									case 3:
										$image->rotate(180);
										break;
									case 6:
										$image->rotate(-90);
										break;
								}
							}
							$image->save($file['file']);
						}
						return $file;
						});
						*/

						$uploaded_file = wp_handle_upload( $file, array( 'test_form' => false ) );
						$filename      = $uploaded_file['file'];
						if ( isset( $uploaded_file['error'] ) ) {
							SUPER_Common::output_message(
								array(
									'msg' => $uploaded_file['error'],
								)
							);
						}
						// Add file to media library
						$attachment                                = array(
							'post_mime_type' => $uploaded_file['type'],
							'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
							'post_content'   => '',
							'post_status'    => 'inherit',
						);
						$data[ $fieldName ]['files'][ $k ]['type'] = $uploaded_file['type'];
						// Only insert attachment if we are in root directory
						$is_secure_dir  = substr( $d['subdir'], 0, 3 );
						$wp_content_dir = str_replace( ABSPATH, '', WP_CONTENT_DIR );
						if ( strpos( $d['subdir'], $wp_content_dir ) === false ) {
							$is_secure_dir = '/..'; // only to validate that this should be treated as a secure dir, it doesn't mean we go a directory up.
						}
						if ( $is_secure_dir === '/..' || $is_secure_dir === '../' ) {
							// If secure upload, update URL:
							$fileSubdir                                  = trailingslashit( $d['subdir'] ) . basename( $filename );
							$fileUrl                                     = trailingslashit( $d['baseurl'] ) . 'sfgtfi' . $fileSubdir;
							$fileUrl                                     = str_replace( '../', '__/', $fileUrl ); // replace `../` with `##/`
							$data[ $fieldName ]['files'][ $k ]['url']    = $fileUrl;
							$data[ $fieldName ]['files'][ $k ]['subdir'] = $fileSubdir; // dir relative to site root
							// $data[$fieldName]['files'][$k]['path'] = $filename; // full path to file on srever
						} else {
							// Always unset after all elements have been processed
							unset( $GLOBALS['super_upload_dir'] );
							remove_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ) );
							$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
							add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );
							$attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
							wp_update_attachment_metadata( $attachment_id, $attach_data );
							$data[ $fieldName ]['files'][ $k ]['url']        = wp_get_attachment_url( $attachment_id );
							$data[ $fieldName ]['files'][ $k ]['attachment'] = $attachment_id;
						}
						$data[ $fieldName ]['files'][ $k ]['value'] = basename( $filename );
					}
				}
			}
			$response = array(
				'files'    => $data,
				'sf_nonce' => SUPER_Common::generate_nonce(),
			);
			$sfsi     = array(
				'i18n'          => $i18n,
				'sfsi_id'       => $sfsi_id,
				'data'          => $odata,
				'files'         => $data,
				'form_id'       => $form_id,
				'entry_id'      => $entry_id,
				'list_id'       => $list_id,
				'settings'      => $settings,
				'response_data' => $response_data,
				'post'          => $_POST,
			);
			// error_log('@2 updating _sfsi_ data');
			if ( ! isset( $sfsi['i18n'] ) ) {
				// error_log('@ i18n for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['i18n'] = $i18n;
			}
			if ( ! isset( $sfsi['sfsi_id'] ) ) {
				// error_log('@ sfs_uid for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['sfsi_id'] = $sfsi_id;
			}
			if ( ! isset( $sfsi['post'] ) ) {
				// error_log('@ post for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['post'] = $_POST;
			}
			if ( ! isset( $sfsi['response_data'] ) ) {
				// error_log('@ response_data for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['response_data'] = $response_data;
			}
			if ( ! isset( $sfsi['data'] ) ) {
				// error_log('@ data for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['data'] = $odata;
			}
			if ( ! isset( $sfsi['settings'] ) ) {
				// error_log('@ settings for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['settings'] = $settings;
			}
			if ( ! isset( $sfsi['list_id'] ) ) {
				// error_log('@ list_id for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['list_id'] = $list_id;
			}
			if ( ! isset( $sfsi['entry_id'] ) ) {
				// error_log('@ entry_id for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['entry_id'] = $contact_entry_id;
			}
			if ( ! isset( $sfsi['attachments'] ) ) {
				// error_log('@ attachments for _sfsi_'.$sfsi_id.' was not set');
				if ( empty( $attachments ) ) {
					// error_log('$attachments are empty, this trigger might be called to early');
					$attachments = array();
				}
				$sfsi['attachments'] = $attachments;
			}
			if ( ! isset( $sfsi['form_id'] ) ) {
				// error_log('@ form_id for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['form_id'] = $form_id;
			}
			if ( ! isset( $sfsi['files'] ) ) {
				// error_log('@ files for _sfsi_'.$sfsi_id.' was not set');
				$sfsi['files'] = $data;
			}
			// error_log('@@@@@@@@UPDATE _sfsi_.'.$sfsi_id.': '.json_encode($sfsi));
			update_option( '_sfsi_' . $sfsi_id, $sfsi );
			SUPER_Common::triggerEvent( 'sf.after.files.uploaded', $sfsi );
			echo SUPER_Common::safe_json_encode( $response );
			die();
		}


		/**
		 *  Save form progress in session after field change
		 *
		 *  @since      3.1.0
		 */
		public static function save_form_progress() {
			if ( ! empty( $_POST['form_id'] ) ) {
				$form_id = absint( $_POST['form_id'] );
				$data    = false; // Clear data by default
				if ( ! empty( $_POST['data'] ) ) {
					$data = wp_unslash( $_POST['data'] );
					$data = json_decode( $data, true );
				}
				SUPER_Common::setClientData(
					array(
						'name'  => 'progress_' . $form_id,
						'value' => $data,
					)
				);
			}
			die();
		}


		public static function submit_form() {
			do_action( 'super_before_submit_form', array( 'post' => $_POST ) );
			if ( empty( $_POST['fileUpload'] ) ) {
				$sfsi = self::submit_form_checks();
			} else {
				$sfsi = self::submit_form_checks( true );
			}
			// error_log('6: '.$sfsi['i18n']);
			$i18n = $sfsi['i18n'];
			// error_log('7: '.$i18n);
			$form_id = $sfsi['form_id'];
			// Get/set unique submission identifier
			$sfsi_id  = $sfsi['sfsi_id'];
			$data     = $sfsi['data'];
			$entry_id = absint( $sfsi['entry_id'] );
			$list_id  = absint( $sfsi['list_id'] );
			$settings = $sfsi['settings'];
			// error_log('submit_form(1)::$settings');
			// error_log(json_encode($settings));
			$response_data = $sfsi['response_data'];
			do_action( 'super_before_processing_data', array( 'atts' => $sfsi ) );
			if ( ( isset( $data ) ) && ( count( $data ) > 0 ) ) {
				foreach ( $data as $k => $v ) {
					if ( ! isset( $v['type'] ) ) {
						continue;
					}
					if ( $v['type'] == 'files' ) {
						if ( ( isset( $v['files'] ) ) && ( count( $v['files'] ) != 0 ) ) {
							foreach ( $v['files'] as $key => $value ) {
								// If there is a generated PDF let it act as a regular file upload
								// Try to generate PDF file
								if ( isset( $value['datauristring'] ) ) {
									try {
										$imgData = str_replace( ' ', '+', $value['datauristring'] );
										unset( $value['datauristring'] );
										$imgData = substr( $imgData, strpos( $imgData, ',' ) + 1 );
										$imgData = base64_decode( $imgData );
										unset( $GLOBALS['super_upload_dir'] );
										add_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ) );
										if ( empty( $GLOBALS['super_upload_dir'] ) ) {
											// upload directory is altered by filter: SUPER_Forms::filter_upload_dir()
											$GLOBALS['super_upload_dir'] = wp_upload_dir();
										}
										$d              = $GLOBALS['super_upload_dir'];
										$value['value'] = SUPER_Common::email_tags( $value['value'], $data, $settings );
										$value['label'] = SUPER_Common::email_tags( $value['label'], $data, $settings );
										$basename       = $value['value'];
										$filename       = trailingslashit( $d['path'] ) . $basename;
										$file           = fopen( $filename, 'w' );
										fwrite( $file, $imgData );
										fclose( $file );
										// Add file to media library
										$attachment = array(
											'post_mime_type' => 'application/pdf', // $uploaded_file['type'],
											'post_title'   => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
											'post_content' => '',
											'post_status'  => 'inherit',
										);
										// Only insert attachment if we are in root directory
										$is_secure_dir  = substr( $d['subdir'], 0, 3 );
										$value['type']  = 'application/pdf';
										$wp_content_dir = str_replace( ABSPATH, '', WP_CONTENT_DIR );
										if ( strpos( $d['subdir'], $wp_content_dir ) === false ) {
											$is_secure_dir = '/..'; // only to validate that this should be treated as a secure dir, it doesn't mean we go a directory up.
										}
										if ( $is_secure_dir === '/..' || $is_secure_dir === '../' ) {
											// If secure upload, update URL:
											$fileUrl         = trailingslashit( $d['baseurl'] ) . 'sfgtfi' . trailingslashit( $d['subdir'] ) . $basename;
											$fileUrl         = str_replace( '../', '__/', $fileUrl ); // replace `../` with `##/`
											$value['url']    = $fileUrl;
											$value['subdir'] = trailingslashit( $d['subdir'] ) . $basename;
											$value['path']   = $filename;
										} else {
											// Always unset after all elements have been processed
											unset( $GLOBALS['super_upload_dir'] );
											remove_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ) );
											$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
											add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );
											$attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
											wp_update_attachment_metadata( $attachment_id, $attach_data );
											$value['url']        = wp_get_attachment_url( $attachment_id );
											$value['attachment'] = $attachment_id;
										}
										$data[ $k ]['files'][ $key ] = $value;
										// Exclude from Contact Entry?
										if ( ! empty( $settings['_pdf']['excludeEntry'] ) && $settings['_pdf']['excludeEntry'] === 'true' ) {
											$data[ $k ]['exclude_entry'] = 'true';
										}
									} catch ( Exception $e ) {
										// Print error message
										SUPER_Common::output_message(
											array(
												'msg' => $e->getMessage(),
											)
										);
									}
								}
							}
						}
					} else {
						// @since 1.2.9 - Save [label] or both [value and label], make sure we set the correct value if we do not want to save only the value of the element
						if ( isset( $v['entry_value'] ) ) {
							$data[ $k ]['value'] = $v['entry_value'];
						}
					}
				}
			}

			// @since 4.9.5
			$data = apply_filters(
				'super_after_processing_files_data_filter',
				$data,
				array(
					'post'     => $_POST,
					'settings' => $settings,
				)
			);
			// Store files, so we can delete files when user clicks the `cancel` button
			$files = array();
			foreach ( $data as $k => $v ) {
				if ( isset( $v['type'] ) && $v['type'] === 'files' ) {
					foreach ( $v['files'] as $f ) {
						$x = array();
						if ( ! empty( $f['attachment'] ) ) {
							$x['attachment'] = $f['attachment'];
						}
						if ( ! empty( $f['path'] ) ) {
							$x['path'] = $f['path'];
						}
						if ( ! empty( $f['subdir'] ) ) {
							$x['subdir'] = $f['subdir'];
						}
						$files[] = $x;
					}
				}
			}
			$sfsi['files'] = $files;
			unset( $GLOBALS['super_upload_dir'] );
			unset( $GLOBALS['super_allowed_mime_types'] );
			$sfsi['data'] = $data;
			// error_log('@@@@@@@@UPDATE _sfsi_.'.$sfsi_id.': '.json_encode($sfsi));
			update_option( '_sfsi_' . $sfsi_id, $sfsi );

			if ( ! empty( $settings['header_additional'] ) ) {
				$header_additional = '';
				if ( ! empty( $settings['header_additional'] ) ) {
					$headers = explode( "\n", $settings['header_additional'] );
					foreach ( $headers as $k => $v ) {
						// @since 1.2.6.92
						$v                  = SUPER_Common::email_tags( $v, $data, $settings );
						$header_additional .= $v . "\r\n";
					}
				}
				$settings['header_additional'] = $header_additional;
			}

			// @since 4.0.0 - check if we do not want to save contact entry conditionally
			if ( ! empty( $settings['conditionally_save_entry'] ) ) {
				$settings['save_contact_entry'] = 'no';
				if ( ! empty( $settings['conditionally_save_entry_check'] ) ) {
					$values = explode( ',', $settings['conditionally_save_entry_check'] );
					// let's replace tags with values
					foreach ( $values as $k => $v ) {
						$values[ $k ] = SUPER_Common::email_tags( $v, $data, $settings );
					}
					if ( ! isset( $values[0] ) ) {
						$values[0] = '';
					}
					if ( ! isset( $values[1] ) ) {
						$values[1] = '=='; // is either == or !=   (== by default)
					}
					if ( ! isset( $values[2] ) ) {
						$values[2] = '';
					}

					// if at least 1 of the 2 is not empty then apply the check otherwise skip it completely
					if ( ( $values[0] != '' ) || ( $values[2] != '' ) ) {
						// Check if values match eachother
						if ( ( $values[1] == '==' ) && ( $values[0] == $values[2] ) ) {
							// we do not want to save the contact entry
							$settings['save_contact_entry'] = 'yes';
						}
						// Check if values do not match eachother
						if ( ( $values[1] == '!=' ) && ( $values[0] != $values[2] ) ) {
							// we do not want to save the contact entry
							$settings['save_contact_entry'] = 'yes';
						}
					}
				}
			}

			if ( ( $entry_id != 0 ) && ( ! empty( $settings['contact_entry_prevent_creation'] ) ) ) {
				$settings['save_contact_entry'] = 'no';
			}

			$contact_entry_id = null;
			if ( $settings['save_contact_entry'] == 'yes' ) {
				// First save the entry simply because we need the ID
				$post = array(
					'post_status' => 'super_unread',
					'post_type'   => 'super_contact_entry',
					'post_parent' => $form_id, // @since 1.7 - save the form ID as the parent
				);
				// @since 3.8.0 - save the post author based on session if set (currently used by Register & Login)
				$post_author = SUPER_Common::getClientData( 'super_forms_registered_user_id' );
				if ( $post_author != false ) {
					$post['post_author'] = absint( $post_author );
				}
				$contact_entry_id         = wp_insert_post( $post );
				$sfsi['contact_entry_id'] = $contact_entry_id;
				$sfsi['entry_id']         = $contact_entry_id;
				// error_log('@@@@@@@@UPDATE _sfsi_.'.$sfsi_id.': '.json_encode($sfsi));
				update_option( '_sfsi_' . $sfsi_id, $sfsi );

				// Store entry ID for later use
				set_transient( 'super_form_authenticated_entry_id_' . $contact_entry_id, $contact_entry_id, 30 ); // Expires in 30 seconds

				// Check if we prevent saving duplicate entry titles
				// Return error message to user
				$contact_entry_title = esc_html__( 'Contact entry', 'super-forms' );
				if ( ! isset( $settings['enable_custom_entry_title'] ) ) {
					$settings['enable_custom_entry_title'] = '';
				}
				if ( $settings['enable_custom_entry_title'] == 'true' ) {
					if ( ! isset( $settings['contact_entry_title'] ) ) {
						$settings['contact_entry_title'] = $contact_entry_title;
					}
					if ( ! isset( $settings['contact_entry_add_id'] ) ) {
						$settings['contact_entry_add_id'] = '';
					}
					$contact_entry_title = SUPER_Common::email_tags( $settings['contact_entry_title'], $data, $settings );
					if ( $settings['contact_entry_add_id'] == 'true' ) {
						if ( $contact_entry_title == '' ) {
							$contact_entry_title = $contact_entry_id;
						} else {
							$contact_entry_title = $contact_entry_title . $contact_entry_id;
						}
					}
				} else {
					$contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
				}
				// Update title
				$post = array(
					'ID'         => $contact_entry_id,
					'post_title' => $contact_entry_title,
				);
				wp_update_post( $post );

				// @since 4.9.600 - check if entry title already exists
				if ( ! empty( $settings['contact_entry_unique_title'] ) && $settings['contact_entry_unique_title'] === 'true' ) {
					if ( empty( $settings['contact_entry_unique_title_compare'] ) ) {
						$settings['contact_entry_unique_title_compare'] = 'form';
					}
					global $wpdb;
					$total = 0;
					if ( empty( $settings['contact_entry_unique_title_trashed'] ) ) {
						$settings['contact_entry_unique_title_trashed'] = '';
					}
					// By default we do not compare against trashed entries
					$trash_compare = "post_status != 'trash' AND ";
					if ( $settings['contact_entry_unique_title_trashed'] === 'true' ) {
						// If user also wishes to compare against trashed entries
						$trash_compare = '';
					}
					if ( $settings['contact_entry_unique_title_compare'] === 'form' ) {
						$query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE $trash_compare post_type = 'super_contact_entry' AND post_parent = '%d' AND post_title = '%s'", $form_id, $contact_entry_title );
						$total = $wpdb->get_var( $query );
					} elseif ( $settings['contact_entry_unique_title_compare'] === 'global' ) {
						$query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE $trash_compare post_type = 'super_contact_entry' AND post_title = '%s'", $contact_entry_title );
						$total = $wpdb->get_var( $query );
					} elseif ( $settings['contact_entry_unique_title_compare'] === 'ids' ) {
						if ( empty( $settings['contact_entry_unique_title_form_ids'] ) ) {
							$settings['contact_entry_unique_title_form_ids'] = '';
						}
						$ids      = $settings['contact_entry_unique_title_form_ids'];
						$ids      = sanitize_text_field( $ids );
						$ids      = explode( ',', $ids );
						$form_ids = array();
						foreach ( $ids as $k => $v ) {
							$v = trim( $v );
							if ( empty( $v ) ) {
								continue;
							}
							$form_ids[ $k ] = absint( $v );
						}
						unset( $ids );
						$form_ids_placeholder = implode( ', ', array_fill( 0, count( $form_ids ), '%d' ) );
						$prepare_values       = array_merge( $form_ids, array( $contact_entry_title ) );
						$query                = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE $trash_compare post_type = 'super_contact_entry' AND post_parent IN ($form_ids_placeholder) AND post_title = '%s'", $prepare_values );
						$total                = $wpdb->get_var( $query );
					}
					if ( $total > 1 ) { // If 2 entries found, it means the current created entry has the same title as an already existing entry
						wp_delete_post( $contact_entry_id, true );
						SUPER_Common::output_message(
							array(
								'msg'     => esc_html( SUPER_Common::email_tags( $settings['contact_entry_unique_title_msg'], $data, $settings ) ),
								'form_id' => absint( $form_id ),
							)
						);
					}
				}

				$response_data['contact_entry_id'] = $contact_entry_id;

				// @since 3.4.0 - save custom contact entry status
				if ( ! empty( $_POST['entry_status'] ) ) {
					$entry_status = sanitize_text_field( $_POST['entry_status'] );
					if ( $entry_status != '' ) {
						$settings['contact_entry_custom_status'] = $entry_status;
					}
				}
				update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['contact_entry_custom_status'] );

				// @since 1.4 - add the contact entry ID to the data array so we can use it to retrieve it with {tags}
				$data['contact_entry_id']['name']  = 'contact_entry_id';
				$data['contact_entry_id']['value'] = $contact_entry_id;
				$data['contact_entry_id']['label'] = '';
				$data['contact_entry_id']['type']  = 'form_id';

				// Update attachment post_parent to contact entry ID
				foreach ( $data as $k => $v ) {
					if ( ( isset( $v['type'] ) ) && ( $v['type'] == 'files' ) ) {
						if ( ( isset( $v['files'] ) ) && ( count( $v['files'] ) != 0 ) ) {
							foreach ( $v['files'] as $file ) {
								$attachment = array(
									'ID'          => ( ! empty( $file['attachment'] ) ? absint( $file['attachment'] ) : 0 ),
									'post_parent' => $contact_entry_id,
								);
								wp_update_post( $attachment );
							}
						}
					}
				}
			}

			// @since 3.3.0 - exclude fields from saving as contact entry
			if ( ! isset( $settings['contact_entry_exclude_empty'] ) ) {
				$settings['contact_entry_exclude_empty'] = '';
			}
			$final_entry_data = array();
			if ( ( $settings['save_contact_entry'] == 'yes' ) || ( $entry_id != 0 ) ) {
				foreach ( $data as $k => $v ) {
					if ( ( isset( $v['exclude_entry'] ) ) && ( $v['exclude_entry'] == 'true' ) ) {
						continue;
					} elseif ( isset( $v['type'] ) && ( $v['type'] == 'form_id' || $v['type'] == 'entry_id' ) ) {
							// Neve exclude these 2 types
							$final_entry_data[ $k ] = $v;
					} else {
						// @since 4.5.0 - check if value is empty, and if we need to exclude it from being saved in the contact entry
						if ( isset( $v['type'] ) && $v['type'] == 'files' ) {
							if ( $settings['contact_entry_exclude_empty'] == 'true' && ( ( ! isset( $v['files'] ) ) || ( count( $v['files'] ) == 0 ) ) ) {
							} else {
								$final_entry_data[ $k ] = $v;
							}
						} elseif ( $settings['contact_entry_exclude_empty'] == 'true' && ( empty( $v['value'] ) || $v['value'] == '0' ) ) {
								// Except for _super_dynamic_data
							if ( $k == '_super_dynamic_data' ) {
								$final_entry_data[ $k ] = $v;
							}
						} else {
							if ( ! empty( $v['value'] ) ) {
								$v['value'] = SUPER_Common::email_tags( $v['value'], $data, $settings );
							}
							$final_entry_data[ $k ] = $v;
						}
					}
				}
			}
			if ( isset( $final_entry_data['hidden_list_id'] ) ) {
				unset( $final_entry_data['hidden_list_id'] );
			}

			// @since 2.2.0 - update contact entry data by ID
			if ( $entry_id != 0 ) {
				$update_entry_status = false;
				if ( isset( $final_entry_data['update_entry_status'] ) ) {
					$update_entry_status = $final_entry_data['update_entry_status']['value'];
					unset( $final_entry_data['update_entry_status'] );
				}
				$result = update_post_meta( $entry_id, '_super_contact_entry_data', $final_entry_data );

				// Check if we prevent saving duplicate entry titles
				// Return error message to user
				$contact_entry_title = esc_html__( 'Contact entry', 'super-forms' );
				if ( ! isset( $settings['enable_custom_entry_title'] ) ) {
					$settings['enable_custom_entry_title'] = '';
				}
				if ( $settings['enable_custom_entry_title'] == 'true' ) {
					if ( ! isset( $settings['contact_entry_title'] ) ) {
						$settings['contact_entry_title'] = $contact_entry_title;
					}
					if ( ! isset( $settings['contact_entry_add_id'] ) ) {
						$settings['contact_entry_add_id'] = '';
					}
					$contact_entry_title = SUPER_Common::email_tags( $settings['contact_entry_title'], $data, $settings );
					if ( $settings['contact_entry_add_id'] == 'true' ) {
						if ( $contact_entry_title == '' ) {
							$contact_entry_title = $entry_id;
						} else {
							$contact_entry_title = $contact_entry_title . $entry_id;
						}
					}
				} else {
					$contact_entry_title = $contact_entry_title . ' ' . $entry_id;
				}
				// Update title
				$post = array(
					'ID'         => $entry_id,
					'post_title' => $contact_entry_title,
				);
				wp_update_post( $post );
				if ( $update_entry_status !== false ) {
					update_post_meta( $entry_id, '_super_contact_entry_status', $update_entry_status );
					$global_settings               = SUPER_Common::get_global_settings();
					$entry_statuses                = SUPER_Settings::get_entry_statuses( $global_settings );
					$_entry_status                 = ( isset( $entry_statuses[ $update_entry_status ] ) ? $entry_statuses[ $update_entry_status ] : $entry_statuses[''] );
					$_entry_status['key']          = $update_entry_status;
					$response_data['entry_status'] = $_entry_status;
				}
				$list_id = '';
				if ( isset( $_POST['list_id'] ) ) {
					$list_id = $_POST['list_id'];
				}
				if ( $update_entry_status === false ) {
					if ( $list_id === '' ) {
						update_post_meta( $entry_id, '_super_contact_entry_status', $settings['contact_entry_custom_status_update'] );
					}
				}
				if ( $list_id !== '' && isset( $settings['_listings'] ) && isset( $settings['_listings']['lists'] ) && isset( $settings['_listings']['lists'][ $list_id ] ) ) {
					$list                                     = SUPER_Listings::get_default_listings_settings( array( 'list' => $settings['_listings']['lists'][ $list_id ] ) );
					$response_data['form_processing_overlay'] = $list['form_processing_overlay'];
					$response_data['close_form_processing_overlay']     = $list['close_form_processing_overlay'];
					$response_data['close_editor_window_after_editing'] = $list['close_editor_window_after_editing'];
				}
			}

			$global_settings = SUPER_Common::get_global_settings();
			if ( $settings['save_contact_entry'] == 'yes' ) {
				add_post_meta( $contact_entry_id, '_super_contact_entry_data', $final_entry_data );
				if ( ( isset( $global_settings['backend_contact_entry_list_ip'] ) ) && ( $global_settings['backend_contact_entry_list_ip'] == 'true' ) ) {
					add_post_meta( $contact_entry_id, '_super_contact_entry_ip', SUPER_Common::real_ip() );
				}

				/**
				 *  Hook after inserting contact entry
				 *
				 *  @param  post    $_POST
				 *  @param  array   $settings
				 *  @param  int     $contact_entry_id    @since v1.2.2
				 *
				 *  @since      1.2.9
				*/
				do_action(
					'super_after_saving_contact_entry_action',
					array(
						'sfsi_id'  => $sfsi_id,
						'post'     => $_POST,
						'data'     => $data,
						'settings' => $settings,
						'entry_id' => $contact_entry_id,
					)
				);

			}

			$settings = apply_filters( 'super_before_sending_email_settings_filter', $settings );

			if ( ! isset( $settings['email_exclude_empty'] ) ) {
				$settings['email_exclude_empty'] = '';
			}
			if ( ! isset( $settings['confirm_exclude_empty'] ) ) {
				$settings['confirm_exclude_empty'] = '';
			}

			// deprecated $loops = SUPER_Common::retrieve_email_loop_html(array('data'=>$data, 'settings'=>$settings, 'exclude'=>array()));
			// deprecated $email_loop = $loops['email_loop'];
			// deprecated $confirm_loop = $loops['confirm_loop'];
			// deprecated $attachments = $loops['attachments'];
			// deprecated $confirm_attachments = $loops['confirm_attachments'];
			// deprecated $string_attachments = $loops['string_attachments'];
			// deprecated $confirm_string_attachments = $loops['confirm_string_attachments'];

			// @since 4.9.5 - override setting with global email settings
			// If we made it to here, retrieve global settings and check if any settings have "Force" enabled
			// meaning we should ignore any settings from the form itself and use the global setting instead
			$overrideSettings = array(
				// Set global 'To' header, can override 'header_to' and 'confirm_to' settings
				'global_email_to_admin'   => 'header_to',
				'global_email_to_confirm' => 'confirm_to',
				// Set global 'From' header, can override 'header_from' and 'confirm_from' settings
				'global_email_from'       => array( 'header_from', 'confirm_from' ),
				// Set global 'From name' header, can override 'header_from_name' and 'confirm_from_name' settings
				'global_email_from_name'  => array( 'header_from_name', 'confirm_from_name' ),
				// Set global 'Reply to' header, can override 'header_reply' and 'confirm_reply' settings
				'global_email_reply'      => array( 'header_reply', 'confirm_reply' ),
				// Set global 'Reply name' header, can override 'header_reply_name' and 'confirm_reply_name' settings
				'global_email_reply_name' => array( 'header_reply_name', 'confirm_reply_name' ),
			);
			foreach ( $overrideSettings as $k => $v ) {
				if ( $k === 'global_email_to_admin' ) {
					if ( ! empty( $global_settings[ $k . '_admin_force' ] ) ) {
						$settings['header_to'] = $global_settings[ $k ];
					}
				}
				if ( $k === 'global_email_to_confirm' ) {
					if ( ! empty( $global_settings[ $k . '_confirm_force' ] ) ) {
						$settings['confirm_to'] = $global_settings[ $k ];
					}
				}
				if ( $k === 'global_email_from' ) {
					if ( ! empty( $global_settings[ $k . '_admin_force' ] ) ) {
						$settings['header_from'] = $global_settings[ $k ];
					}
					if ( ! empty( $global_settings[ $k . '_confirm_force' ] ) ) {
						$settings['confirm_from'] = $global_settings[ $k ];
					}
				}
				if ( $k === 'global_email_from_name' ) {
					if ( ! empty( $global_settings[ $k . '_admin_force' ] ) ) {
						$settings['header_from_name'] = $global_settings[ $k ];
					}
					if ( ! empty( $global_settings[ $k . '_confirm_force' ] ) ) {
						$settings['confirm_from_name'] = $global_settings[ $k ];
					}
				}
				if ( $k === 'global_email_reply' ) {
					if ( ! empty( $global_settings[ $k . '_admin_force' ] ) ) {
						$settings['header_reply'] = $global_settings[ $k ];
					}
					if ( ! empty( $global_settings[ $k . '_confirm_force' ] ) ) {
						$settings['confirm_reply'] = $global_settings[ $k ];
					}
				}
				if ( $k === 'global_email_reply_name' ) {
					if ( ! empty( $global_settings[ $k . '_admin_force' ] ) ) {
						$settings['header_reply_name'] = $global_settings[ $k ];
					}
					if ( ! empty( $global_settings[ $k . '_confirm_force' ] ) ) {
						$settings['confirm_reply_name'] = $global_settings[ $k ];
					}
				}
			}

			do_action(
				'super_before_sending_email_hook',
				array(
					'sfsi_id'       => $sfsi_id,
					'data'          => $data,
					'form_id'       => $form_id,
					'entry_id'      => $entry_id,
					'list_id'       => $list_id,
					'settings'      => $settings,
					'response_data' => $response_data,
					'post'          => $_POST,
				)
			);
			// We must retrieve the new session info, because the register & login might have updated the `user_id` value
			$sfsi = get_option( '_sfsi_' . $sfsi_id );
			// error_log('@@@@@@@@GET _sfsi_.'.$sfsi_id.': '.json_encode($sfsi));
			// deprecated if( $settings['send']=='yes' ) {
			// deprecated     $email_body = $settings['email_body'];
			// deprecated     $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
			// deprecated     $email_body = apply_filters( 'super_before_sending_email_body_filter', $email_body, array( 'settings'=>$settings, 'email_loop'=>$email_loop, 'data'=>$data ) );
			// deprecated     $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
			// deprecated
			// deprecated     // @since 3.1.0 - optionally automatically add line breaks
			// deprecated     if(!isset($settings['email_body_nl2br'])) $settings['email_body_nl2br'] = 'true';
			// deprecated     if($settings['email_body_nl2br']=='true') $email_body = nl2br( $email_body );
			// deprecated
			// deprecated     // @since 4.9.5 - RTL email setting
			// deprecated     if(!isset($settings['email_rtl'])) $settings['email_rtl'] = '';
			// deprecated     if($settings['email_rtl']=='true') $email_body =  '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';

			// deprecated     $email_body = do_shortcode($email_body);
			// deprecated     if( !isset( $settings['header_from_type'] ) ) $settings['header_from_type'] = 'default';
			// deprecated     if( $settings['header_from_type']=='default' ) {
			// deprecated         $settings['header_from_name'] = get_option( 'blogname' );
			// deprecated         $settings['header_from'] = get_option( 'admin_email' );
			// deprecated     }
			// deprecated     if( !isset( $settings['header_from_name'] ) ) $settings['header_from_name'] = get_option( 'blogname' );
			// deprecated     if( !isset( $settings['header_from'] ) ) $settings['header_from'] = get_option( 'admin_email' );

			// deprecated     $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_to'], $data, $settings ) );
			// deprecated     $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_from'], $data, $settings ) );
			// deprecated     $from_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings ) );
			// deprecated
			// deprecated     $cc = '';
			// deprecated     if( !empty($settings['header_cc']) ) {
			// deprecated         $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_cc'], $data, $settings ) );
			// deprecated     }
			// deprecated     $bcc = '';
			// deprecated     if( !empty($settings['header_bcc']) ) {
			// deprecated         $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_bcc'], $data, $settings ) );
			// deprecated     }
			// deprecated
			// deprecated     $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_subject'], $data, $settings ) );

			// deprecated     // @since 2.8.0 - custom reply to headers
			// deprecated     if( !isset($settings['header_reply_enabled']) ) $settings['header_reply_enabled'] = false;
			// deprecated     $reply = '';
			// deprecated     $reply_name = '';
			// deprecated     if( $settings['header_reply_enabled']==false ) {
			// deprecated         $custom_reply = false;
			// deprecated     }else{
			// deprecated         $custom_reply = true;
			// deprecated         if( !isset($settings['header_reply']) ) $settings['header_reply'] = '';
			// deprecated         if( !isset($settings['header_reply_name']) ) $settings['header_reply_name'] = '';
			// deprecated         $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply'], $data, $settings ) );
			// deprecated         $reply_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_reply_name'], $data, $settings ) );
			// deprecated     }

			// deprecated     // @since 3.3.2 - default admin email attachments
			// deprecated     if( !empty($settings['admin_attachments']) ) {
			// deprecated         $email_attachments = explode( ',', $settings['admin_attachments'] );
			// deprecated         foreach($email_attachments as $k => $v){
			// deprecated             $file = get_attached_file($v);
			// deprecated             if( $file ) {
			// deprecated                 $url = wp_get_attachment_url($v);
			// deprecated                 $filename = basename ( $file );
			// deprecated                 $attachments[$filename] = $url;
			// deprecated             }
			// deprecated         }
			// deprecated     }

			// deprecated     // @since 2.0
			// deprecated     $attachments = apply_filters( 'super_before_sending_email_attachments_filter', $attachments, array( 'atts'=>$sfsi, 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body ) );

			// deprecated     // Send the email
			// deprecated     $params = array( 'to'=>$to, 'from'=>$from, 'from_name'=>$from_name, 'custom_reply'=>$custom_reply, 'reply'=>$reply, 'reply_name'=>$reply_name, 'cc'=>$cc, 'bcc'=>$bcc, 'subject'=>$subject, 'body'=>$email_body, 'settings'=>$settings, 'attachments'=>$attachments, 'string_attachments'=>$string_attachments );
			// deprecated     $mail = SUPER_Common::email( $params );
			// deprecated
			// deprecated     // Return error message
			// deprecated     if( !empty( $mail->ErrorInfo ) ) {
			// deprecated         $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
			// deprecated         SUPER_Common::output_message( array(
			// deprecated             'msg' => $msg,
			// deprecated             'form_id' => absint($form_id)
			// deprecated         ));
			// deprecated     }
			// deprecated }
			// deprecated if( $settings['confirm']=='yes' ) {
			// deprecated
			// deprecated     // @since 2.8.0 - additional header support for confirmation emails
			// deprecated     if( !isset($settings['confirm_header_additional']) ) $settings['confirm_header_additional'] = '';
			// deprecated     $settings['header_additional'] = $settings['confirm_header_additional'];
			// deprecated     $email_body = $settings['confirm_body'];
			// deprecated     $email_body = str_replace( '{loop_fields}', $confirm_loop, $email_body );
			// deprecated     $email_body = apply_filters( 'super_before_sending_confirm_body_filter', $email_body, array( 'settings'=>$settings, 'confirm_loop'=>$confirm_loop, 'data'=>$data ) );
			// deprecated     $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );

			// deprecated     // @since 3.1.0 - optionally automatically add line breaks
			// deprecated     if(!isset($settings['confirm_body_nl2br'])) $settings['confirm_body_nl2br'] = 'true';
			// deprecated     if($settings['confirm_body_nl2br']=='true') $email_body = nl2br( $email_body );

			// deprecated     // @since 4.9.5 - RTL email setting
			// deprecated     if(!isset($settings['confirm_rtl'])) $settings['confirm_rtl'] = '';
			// deprecated     if($settings['confirm_rtl']=='true') $email_body = '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';
			// deprecated
			// deprecated     $email_body = do_shortcode($email_body);
			// deprecated     if( !isset( $settings['confirm_from_type'] ) ) $settings['confirm_from_type'] = 'default';
			// deprecated     if( $settings['confirm_from_type']=='default' ) {
			// deprecated         $settings['confirm_from_name'] = get_option( 'blogname' );
			// deprecated         $settings['confirm_from'] = get_option( 'admin_email' );
			// deprecated     }
			// deprecated     if( !isset( $settings['confirm_from_name'] ) ) $settings['confirm_from_name'] = get_option( 'blogname' );
			// deprecated     if( !isset( $settings['confirm_from'] ) ) $settings['confirm_from'] = get_option( 'admin_email' );
			// deprecated     $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_to'], $data, $settings ) );
			// deprecated     $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_from'], $data, $settings ) );
			// deprecated     $from_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_from_name'], $data, $settings ) );
			// deprecated     $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_subject'], $data, $settings ) );

			// deprecated     // @since 2.8.0 - cc and bcc support for confirmation emails
			// deprecated     $cc = '';
			// deprecated     if( !empty($settings['confirm_header_cc']) ) {
			// deprecated         $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_header_cc'], $data, $settings ) );
			// deprecated     }
			// deprecated     $bcc = '';
			// deprecated     if( !empty($settings['confirm_header_bcc']) ) {
			// deprecated         $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_header_bcc'], $data, $settings ) );
			// deprecated     }

			// deprecated     // @since 2.8.0 - custom reply to headers
			// deprecated     if( !isset($settings['confirm_header_reply_enabled']) ) $settings['confirm_header_reply_enabled'] = false;
			// deprecated     $reply = '';
			// deprecated     $reply_name = '';
			// deprecated     if( $settings['confirm_header_reply_enabled']==false ) {
			// deprecated         $custom_reply = false;
			// deprecated     }else{
			// deprecated         $custom_reply = true;
			// deprecated         if( !isset($settings['confirm_header_reply']) ) $settings['confirm_header_reply'] = '';
			// deprecated         if( !isset($settings['confirm_header_reply_name']) ) $settings['confirm_header_reply_name'] = '';
			// deprecated         $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_header_reply'], $data, $settings ) );
			// deprecated         $reply_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_header_reply_name'], $data, $settings ) );
			// deprecated     }

			// deprecated     // @since 3.3.2 - default confirm email attachments
			// deprecated     if( !empty($settings['confirm_attachments']) ) {
			// deprecated         $email_attachments = explode( ',', $settings['confirm_attachments'] );
			// deprecated         foreach($email_attachments as $k => $v){
			// deprecated             $file = get_attached_file($v);
			// deprecated             if( $file ) {
			// deprecated                 $url = wp_get_attachment_url($v);
			// deprecated                 $filename = basename ( $file );
			// deprecated                 $confirm_attachments[$filename] = $url;
			// deprecated             }
			// deprecated         }
			// deprecated     }

			// deprecated     // @since 2.0
			// deprecated     $confirm_attachments = apply_filters( 'super_before_sending_email_confirm_attachments_filter', $confirm_attachments, array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body )  );

			// deprecated     // Send the email
			// deprecated     $params = array( 'to'=>$to, 'from'=>$from, 'from_name'=>$from_name, 'custom_reply'=>$custom_reply, 'reply'=>$reply, 'reply_name'=>$reply_name, 'cc'=>$cc, 'bcc'=>$bcc, 'subject'=>$subject, 'body'=>$email_body, 'settings'=>$settings, 'attachments'=>$confirm_attachments, 'string_attachments'=>$confirm_string_attachments );
			// deprecated     $mail = SUPER_Common::email( $params );

			// deprecated     // Return error message
			// deprecated     if( !empty( $mail->ErrorInfo ) ) {
			// deprecated         $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
			// deprecated         SUPER_Common::output_message( array(
			// deprecated             'msg' => $msg,
			// deprecated             'form_id' => absint($form_id)
			// deprecated         ));
			// deprecated     }
			// deprecated }
			if ( $form_id != 0 ) {

				// @since 3.4.0 - Form Locker - Lock form after specific amount of submissions (based on total contact entries created)
				if ( ( isset( $settings['form_locker'] ) ) && ( $settings['form_locker'] == 'true' ) ) {
					$count = get_post_meta( $form_id, '_super_submission_count', true );
					update_post_meta( $form_id, '_super_submission_count', absint( $count ) + 1 );
					update_post_meta( $form_id, '_super_last_submission_date', date_i18n( 'Y-m-d H:i:s' ) );
				}

				// @since 3.8.0 - Lock form after specific amount of submissions for logged in user (based on total contact entries created by user)
				if ( ( isset( $settings['user_form_locker'] ) ) && ( $settings['user_form_locker'] == 'true' ) ) {
					// Let's check if the user is logged in
					$current_user_id = get_current_user_id();
					if ( $current_user_id != 0 ) {
						$user_limits = get_post_meta( $form_id, '_super_user_submission_counter', true );
						if ( ! is_array( $user_limits ) ) {
							$user_limits = array();
						}
						if ( empty( $user_limits[ $current_user_id ] ) ) {
							$user_limits[ $current_user_id ] = 1;
						} else {
							$user_limits[ $current_user_id ] = absint( $user_limits[ $current_user_id ] ) + 1;
						}
						update_post_meta( $form_id, '_super_user_submission_counter', $user_limits );
						update_post_meta( $form_id, '_super_last_submission_date', date_i18n( 'Y-m-d H:i:s' ) );
					}
				}

				// @since 3.6.0 - custom POST parameters method
				if ( empty( $settings['form_post_option'] ) ) {
					$settings['form_post_option'] = '';
				}
				if ( empty( $settings['form_post_custom'] ) ) {
					$settings['form_post_custom'] = '';
				}
				if ( $settings['form_post_option'] == 'true' && $settings['form_post_custom'] == 'true' ) {
					$parameter = array();
					if ( empty( $settings['form_post_parameters'] ) ) {
						$settings['form_post_parameters'] = '';
					}
					if ( trim( $settings['form_post_parameters'] ) == '' ) {
						// When left empty we will send all form data
						foreach ( $data as $k => $v ) {
							if ( $v['type'] == 'files' ) {
								$files = array();
								if ( ( ! isset( $v['files'] ) ) || ( count( $v['files'] ) == 0 ) ) {
									$v['value'] = '';
								} else {
									$v['value'] = '-';
									foreach ( $v['files'] as $key => $value ) {
										$files[] = $value['url'];
									}
								}
								$parameters[ $k ] = $files;
							} else {
								$parameters[ $v['name'] ] = $v['value'];
							}
						}
					} else {
						// If not empty only send specific fields
						$form_post_parameters     = explode( "\n", $settings['form_post_parameters'] );
						$new_form_post_parameters = $form_post_parameters;
						foreach ( $form_post_parameters as $k => $v ) {
							$parameter = explode( '|', $v );
							if ( isset( $parameter[0] ) ) {
								$parameter_key = trim( $parameter[0], '{}' );
							}
							if ( isset( $parameter[1] ) ) {
								$parameter_tag = trim( $parameter[1], '{}' );
							}

							$looped = array();
							$i      = 2;
							while ( isset( $data[ $parameter_key . '_' . ( $i ) ] ) ) {
								if ( ! in_array( $i, $looped ) ) {
									$new_line = '';
									if ( $parameter[0][0] == '{' ) {
										$new_line .= '{' . $parameter_key . '_' . $i . '}';
									} else {
										$new_line .= $parameter[0]; }
									if ( $parameter[1][0] == '{' ) {
										$new_line .= '|{' . $parameter_tag . '_' . $i . '}';
									} else {
										$new_line .= '|' . $parameter[1]; }
									$new_form_post_parameters[] = $new_line;
									$looped[ $i ]               = $i;
									++$i;
								} else {
									break;
								}
							}

							$i = 2;
							while ( isset( $data[ $parameter_tag . '_' . ( $i ) ] ) ) {
								if ( ! in_array( $i, $looped ) ) {
									$new_line = '';
									if ( $parameter[0][0] == '{' ) {
										$new_line .= '{' . $parameter_key . '_' . $i . '}';
									} else {
										$new_line .= $parameter[0]; }
									if ( $parameter[1][0] == '{' ) {
										$new_line .= '|{' . $parameter_tag . '_' . $i . '}';
									} else {
										$new_line .= '|' . $parameter[1]; }
									$new_form_post_parameters[] = $new_line;
									$looped[ $i ]               = $i;
									++$i;
								} else {
									break;
								}
							}
						}
						foreach ( $new_form_post_parameters as $k => $v ) {
							if ( empty( $v ) ) {
								continue;
							}
							$parameter            = explode( '|', $v );
							$key                  = '';
							$value                = '';
							$product_variation_id = '';
							$product_price        = '';
							if ( isset( $parameter[0] ) ) {
								$key = SUPER_Common::email_tags( $parameter[0], $data, $settings );
							}
							if ( isset( $parameter[1] ) ) {
								$value = SUPER_Common::email_tags( $parameter[1], $data, $settings );
							}
							$parameters[ $key ] = $value;
						}
					}

					// Include dynamic data
					if ( ! empty( $settings['form_post_incl_dynamic_data'] ) && isset( $data['_super_dynamic_data'] ) ) {
						$parameters['_super_dynamic_data'] = $data['_super_dynamic_data'];
					}

					if ( empty( $settings['form_post_json'] ) ) {
						$settings['form_post_json'] = '';
					}
					if ( empty( $settings['form_post_timeout'] ) ) {
						$settings['form_post_timeout'] = '5';
					}
					if ( empty( $settings['form_post_http_version'] ) ) {
						$settings['form_post_http_version'] = '1.0';
					}
					if ( empty( $settings['form_post_debug'] ) ) {
						$settings['form_post_debug'] = '';
					}

					$headers = array();
					if ( $settings['form_post_json'] == 'true' ) {
						$headers    = array( 'Content-Type' => 'application/json; charset=utf-8' );
						$parameters = SUPER_Common::safe_json_encode( $parameters );
					}

					$sfsi['post_body'] = $parameters;
					// error_log('@@@@@@@@UPDATE _sfsi_.'.$sfsi_id.': '.json_encode($sfsi));
					update_option( '_sfsi_' . $sfsi_id, $sfsi );

					$response = wp_remote_post(
						$settings['form_post_url'],
						array(
							'method'      => 'POST',
							'timeout'     => $settings['form_post_timeout'],
							'httpversion' => $settings['form_post_http_version'],
							'headers'     => $headers,
							'body'        => $parameters,
						)
					);
					if ( is_wp_error( $response ) ) {
						$error_message = $response->get_error_message();
						SUPER_Common::output_message(
							array(
								'msg'     => $error_message,
								'form_id' => absint( $form_id ),
							)
						);
					}

					// Clear form progression
					SUPER_Common::setClientData(
						array(
							'name'  => 'progress_' . $form_id,
							'value' => false,
						)
					);

					$sfsi['post_response'] = $response;
					// error_log('@@@@@@@@UPDATE _sfsi_.'.$sfsi_id.': '.json_encode($sfsi));
					update_option( '_sfsi_' . $sfsi_id, $sfsi );

					do_action( 'super_after_wp_remote_post_action', $response );

					if ( $settings['form_post_debug'] == 'true' ) {
						// Check if Array, if so convert to json
						if ( is_array( $parameters ) ) {
							$parameters_output = SUPER_Common::safe_json_encode( $parameters );
						} else {
							$parameters_output = $parameters;
						}
						SUPER_Common::output_message(
							array(
								'error'    => false,
								'msg'      => '<strong>POST data:</strong><br /><textarea style="min-height:150px;width:100%;font-size:12px;">' . $parameters_output . '</textarea><br /><br /><strong>Response:</strong><br /><textarea style="min-height:150px;width:100%;font-size:12px;">' . $response['body'] . '</textarea>',
								'redirect' => false,
								'form_id'  => absint( $form_id ),
							)
						);
					}
				}

				// Clear form progression
				SUPER_Common::setClientData(
					array(
						'name'  => 'progress_' . $form_id,
						'value' => false,
					)
				);

				/**
				 *  Hook before outputing the success message or redirect after a succesfull submitted form
				 *
				 *  @param  post    $_POST
				 *  @param  array   $settings
				 *  @param  int     $contact_entry_id    @since v1.2.2
				 *
				 *  @since      1.0.2
				*/

				// @since 4.6.0 - also parse all attachments (useful for external file storage through for instance Zapier)
				$attachments         = array(
					'attachments'         => ( isset( $attachments ) ? $attachments : array() ),
					'confirm_attachments' => ( isset( $confirm_attachments ) ? $confirm_attachments : array() ),
					'string_attachments'  => ( isset( $string_attachments ) ? $string_attachments : array() ),
				);
				$attachments         = apply_filters(
					'super_attachments_filter',
					$attachments,
					array(
						'post'        => $_POST,
						'data'        => $data,
						'settings'    => $settings,
						'entry_id'    => $contact_entry_id,
						'attachments' => $attachments,
					)
				);
				$sfsi['attachments'] = $attachments;
				error_log( '@@@@@@@@BEFORE _sfsi_.' . $sfsi_id . ': ' . json_encode( $sfsi ) );
				if ( ! isset( $sfsi['attachments'] ) ) {
					error_log( '@ attachments for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['attachments'] = $attachments;
				}
				if ( ! isset( $sfsi['i18n'] ) ) {
					error_log( '@ i18n for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['i18n'] = $i18n;
				}
				if ( ! isset( $sfsi['sfsi_id'] ) ) {
					error_log( '@ sfs_uid for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['sfsi_id'] = $sfsi_id;
				}
				if ( ! isset( $sfsi['post'] ) ) {
					error_log( '@ post for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['post'] = $_POST;
				}
				if ( ! isset( $sfsi['data'] ) ) {
					error_log( '@ data for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['data'] = $data;
				}
				if ( ! isset( $sfsi['settings'] ) ) {
					error_log( '@ settings for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['settings'] = $settings;
				}
				if ( ! isset( $sfsi['entry_id'] ) ) {
					error_log( '@ entry_id for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['entry_id'] = $contact_entry_id;
				}
				if ( ! isset( $sfsi['form_id'] ) ) {
					error_log( '@ form_id for _sfsi_' . $sfsi_id . ' was not set' );
					$sfsi['form_id'] = $form_id;
				}
				error_log( '@@@@@@@@UPDATE _sfsi_.' . $sfsi_id . ': ' . json_encode( $sfsi ) );
				update_option( '_sfsi_' . $sfsi_id, $sfsi );
				SUPER_Common::triggerEvent( 'sf.after.submission', $sfsi );
				do_action(
					'super_before_email_success_msg_action',
					array(
						'i18n'        => $i18n,
						'sfsi_id'     => $sfsi_id,
						'post'        => $_POST,
						'data'        => $data,
						'settings'    => $settings,
						'entry_id'    => $contact_entry_id,
						'attachments' => $attachments,
						'form_id'     => $form_id,
					)
				);
				// If the option to delete files after form submission is enabled remove all uploaded files from the server
				if ( ! empty( $settings['file_upload_submission_delete'] ) ) {
					// Loop through all data with field typ 'files' and look for any uploaded attachments
					foreach ( $data as $k => $v ) {
						if ( $v['type'] == 'files' ) {
							if ( ( isset( $v['files'] ) ) && ( count( $v['files'] ) != 0 ) ) {
								foreach ( $v['files'] as $file ) {
									if ( ! empty( $file['attachment'] ) ) {
										wp_delete_attachment( absint( $file['attachment'] ), true );
									} else {
										if ( ! empty( $file['subdir'] ) ) {
											$filePath = realpath( ABSPATH . $file['subdir'] );
											SUPER_Common::delete_dir( dirname( $filePath ) );
											continue;
										}
										if ( ! empty( $file['path'] ) ) {
											SUPER_Common::delete_dir( $file['path'] );
										}
									}
								}
							}
						}
					}
				} else {
					// Check if we must delete vCards after form submission
					if ( ( ! empty( $settings['vcard_delete'] ) ) &&
					( $settings['vcard_delete'] === 'true' ) &&
					( isset( $data['_vcard'] ) ) &&
					( isset( $data['_vcard']['files'] ) ) &&
					( count( $data['_vcard']['files'] ) != 0 ) ) {
						foreach ( $data['_vcard']['files'] as $file ) {
							if ( ! empty( $file['attachment'] ) ) {
								wp_delete_attachment( absint( $file['attachment'] ), true );
							} else {
								if ( ! empty( $file['subdir'] ) ) {
									$filePath = realpath( ABSPATH . $file['subdir'] );
									SUPER_Common::delete_dir( dirname( $filePath ) );
									continue;
								}
								if ( ! empty( $file['path'] ) ) {
									SUPER_Common::delete_dir( $file['path'] );
								}
							}
						}
					}
				}

				// Currently used by Stripe to redirect to checkout session
				do_action(
					'super_before_redirect_action',
					array(
						'sfsi'        => $sfsi,
						'form_id'     => $form_id,
						'sfsi_id'     => $sfsi_id,
						'post'        => $_POST,
						'data'        => $data,
						'settings'    => $settings,
						'entry_id'    => $contact_entry_id,
						'attachments' => $attachments,
					)
				);

				// Clear form progression
				SUPER_Common::setClientData(
					array(
						'name'  => 'progress_' . $form_id,
						'value' => false,
					)
				);

				// Return message or redirect and save message to session
				$redirect = null;
				$save_msg = false;
				if ( ( isset( $settings['form_show_thanks_msg'] ) ) && ( $settings['form_show_thanks_msg'] == 'true' ) ) {
					$save_msg = true;
				}
				$settings['form_thanks_title'] = '<h1>' . $settings['form_thanks_title'] . '</h1>';

				$msg = do_shortcode( $settings['form_thanks_title'] . $settings['form_thanks_description'] );

				// @since 4.1.0 - option to do if statements in success message
				$msg = SUPER_Forms()->email_if_statements(
					$msg,
					array(
						'settings' => $settings,
						'data'     => $data,
					)
				);

				$msg          = SUPER_Common::email_tags( $msg, $data, $settings );
				$session_data = array(
					'msg'      => $msg,
					'type'     => 'success',
					'data'     => $data,
					'settings' => $settings,
					'entry_id' => $contact_entry_id,
				);
				if ( ! empty( $settings['form_redirect_option'] ) ) {
					if ( $settings['form_redirect_option'] == 'page' ) {
						$redirect = get_permalink( $settings['form_redirect_page'] );
					}
					if ( $settings['form_redirect_option'] == 'custom' ) {
						$redirect = SUPER_Common::email_tags( $settings['form_redirect'], $data, $settings );
					}
					if ( $save_msg == true ) {
						SUPER_Common::setClientData(
							array(
								'name'  => 'msg',
								'value' => $session_data,
							)
						);
					}
				}
				if ( ( ! empty( $settings['form_post_option'] ) ) && ( $save_msg == true ) ) {
					// Only store the message into a session if the form is submitted as a normal POST request.
					// This will not be the case when `custom parameter string for POST method` is enabled.
					// In this case we do not want to store the thank you message into a session because if we
					// navigate to a different page it would show the thank you message again to the user while
					// it was already displayed to the user
					if ( $settings['form_post_custom'] !== 'true' ) {
						SUPER_Common::setClientData(
							array(
								'name'  => 'msg',
								'value' => $session_data,
							)
						);
					}
				}
				if ( $save_msg == false ) {
					$msg = '';
				}

				/**
				 *  Filter to control the redirect URL
				 *  e.g. Currenlty used for Front-end Posting to redirect to the created post
				 *
				 *  @param  array  $data
				 *  @param  array  $settings
				 *
				 *  @since      4.3.0
				*/
				$redirect = apply_filters(
					'super_redirect_url_filter',
					$redirect,
					array(
						'data'     => $data,
						'settings' => $settings,
					)
				);
				if ( $redirect !== '' && $redirect !== false ) {
					// tmp $sfsi['redirectedTo'] = $redirect;
					// tmp update_option('_sfsi_' . $sfsi_id, $sfsi );
				}

				SUPER_Common::triggerEvent( 'sf.submission.finalized', $sfsi );
				// Clean up submission info
				// If we are redirecting to Stripe or PayPal checkout we won't reach this (nothing to worry about here)
				// error_log('@@@@@@@@DELETE _sfsi_.'.$sfsi_id);
				delete_option( '_sfsi_' . $sfsi_id );
				$response_data['sf_nonce'] = SUPER_Common::generate_nonce();
				// Required by Listings to replace the old PDF URL with the newly generated URL:
				if ( isset( $sfsi['data']['_generated_pdf_file'] ) ) {
					$response_data['_generated_pdf_file'] = $sfsi['data']['_generated_pdf_file'];
				}
				SUPER_Common::output_message(
					array(
						'error'         => false,
						'msg'           => $msg,
						'redirect'      => $redirect,
						'fields'        => array(),
						'display'       => true,
						'loading'       => false,
						'json'          => true,
						'response_data' => $response_data,
						'form_id'       => absint( $form_id ),
					)
				);
				die();
			}
		}

		/**
		 * Send test email from form builder
		 *
		 * @since 1.0.0
		 */
		public static function send_test_email() {
			// Check permissions (admin-only feature)
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to send test emails', 'super-forms' ) ) );
			}

			// Get parameters
			$form_id        = absint( $_POST['form_id'] );
			$data_type      = sanitize_text_field( $_POST['data_type'] );
			$entry_id       = sanitize_text_field( $_POST['entry_id'] );
			$test_recipient = sanitize_email( $_POST['test_recipient'] );
			$email_settings = isset( $_POST['email_settings'] ) ? $_POST['email_settings'] : array();

			if ( ! $test_recipient || ! is_email( $test_recipient ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid test recipient email address', 'super-forms' ) ) );
			}

			// Check if form exists (if form_id is 0, it's a new unsaved form)
			$form_exists = ( $form_id > 0 );
			$settings    = array();
			$elements    = array();

			if ( $form_exists ) {
				// Get form settings
				$settings = SUPER_Common::get_form_settings( $form_id );
				if ( ! $settings ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Form not found', 'super-forms' ) ) );
				}

				// Get form elements from meta data
				$elements = get_post_meta( $form_id, '_super_elements', true );
				if ( ! is_array( $elements ) ) {
					$elements = array();
				}
			} else {
				// For unsaved forms, force dummy data
				$data_type = 'dummy';
				// Use default settings
				$settings = SUPER_Common::get_default_settings();
				$elements = array();
			}

			// Prepare data based on type
			$data = array();

			if ( $data_type === 'entry' && $form_exists ) {
				// Try to get entry data
				$entry = null;

				if ( $entry_id ) {
					// Get specific entry
					$entry = get_post( $entry_id );
					if ( $entry && $entry->post_type === 'super_contact_entry' && $entry->post_parent == $form_id ) {
						// Valid entry found
					} else {
						$entry = null;
					}
				} else {
					// Get latest entry for this form
					$args    = array(
						'post_type'      => 'super_contact_entry',
						'post_status'    => array( 'super_unread', 'super_read' ),
						'post_parent'    => $form_id,
						'posts_per_page' => 1,
						'orderby'        => 'date',
						'order'          => 'DESC',
					);
					$entries = get_posts( $args );
					if ( ! empty( $entries ) ) {
						$entry = $entries[0];
					}
				}

				if ( $entry ) {
					// Get entry data from post meta
					$entry_data = get_post_meta( $entry->ID, '_super_contact_entry_data', true );
					if ( is_array( $entry_data ) ) {
						$data = $entry_data;
					}
				} else {
					// Fall back to dummy data if entry not found
					$data_type = 'dummy';
				}
			}

			if ( $data_type === 'dummy' || ! $form_exists ) {
				// Generate dummy data
				$dummy_data = self::generate_dummy_data( $elements );
				$data       = $dummy_data['data'];
			}

			// Use email settings from JavaScript if provided, otherwise use defaults
			if ( ! empty( $email_settings ) && is_array( $email_settings ) ) {
				$email_config = array(
					'to'            => $test_recipient, // Override recipient for test
					'from'          => sanitize_email( $email_settings['from'] ) ?: get_option( 'admin_email' ),
					'from_name'     => sanitize_text_field( $email_settings['from_name'] ) ?: get_bloginfo( 'name' ),
					'reply'         => isset( $email_settings['reply'] ) ? sanitize_email( $email_settings['reply'] ) : '',
					'reply_name'    => isset( $email_settings['reply_name'] ) ? sanitize_text_field( $email_settings['reply_name'] ) : '',
					'cc'            => sanitize_text_field( $email_settings['cc'] ) ?: '',
					'bcc'           => sanitize_text_field( $email_settings['bcc'] ) ?: '',
					'subject'       => '[TEST] ' . sanitize_text_field( $email_settings['subject'] ) ?: '[TEST] Form Submission',
					'body'          => wp_kses_post( $email_settings['body'] ) ?: 'Form submission data:<br><br>{loop_fields}',
					'attachments'   => isset( $email_settings['attachments'] ) ? $email_settings['attachments'] : array(),
					'csv_enabled'   => isset( $email_settings['csv_enabled'] ) ? $email_settings['csv_enabled'] : false,
					'csv_filename'  => isset( $email_settings['csv_filename'] ) ? sanitize_text_field( $email_settings['csv_filename'] ) : '',
					'xml_enabled'   => isset( $email_settings['xml_enabled'] ) ? $email_settings['xml_enabled'] : false,
					'xml_filename'  => isset( $email_settings['xml_filename'] ) ? sanitize_text_field( $email_settings['xml_filename'] ) : '',
					'loop_open'     => isset( $email_settings['loop_open'] ) ? wp_kses_post( $email_settings['loop_open'] ) : '<table cellpadding="5">',
					'loop'          => isset( $email_settings['loop'] ) ? wp_kses_post( $email_settings['loop'] ) : '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>',
					'loop_close'    => isset( $email_settings['loop_close'] ) ? wp_kses_post( $email_settings['loop_close'] ) : '</table>',
					'exclude_empty' => isset( $email_settings['exclude_empty'] ) ? $email_settings['exclude_empty'] : true,
				);
			} else {
				// Fallback if no email settings provided
				$email_config = array(
					'to'            => $test_recipient,
					'from'          => get_option( 'admin_email' ),
					'from_name'     => get_bloginfo( 'name' ),
					'reply'         => '',
					'reply_name'    => '',
					'cc'            => '',
					'bcc'           => '',
					'subject'       => '[TEST] Form Submission Test',
					'body'          => 'This is a test email from your form:<br><br>{loop_fields}<br><br>Best regards,<br>{option_blogname}',
					'attachments'   => array(),
					'loop_open'     => '<table cellpadding="5">',
					'loop'          => '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>',
					'loop_close'    => '</table>',
					'exclude_empty' => true,
				);
			}

			// Create the action structure expected by send_email
			$action = array(
				'action' => 'send_email',
				'data'   => $email_config,
			);

			// Create a minimal trigger structure
			$trigger = array(
				'trigger' => 'email',
				'actions' => array( $action ),
			);

			// Prepare submission info
			$sfsi = array(
				'form_id'         => $form_id,
				'data'            => $data,
				'files'           => array(),
				'settings'        => $settings,
				'i18n'            => '',
				'entry_id'        => 0,
				'generated_files' => array(),
			);

			// For test emails, manually replace {loop_fields} with dummy data using user's loop settings
			$processed_body = $email_config['body'];
			if ( strpos( $processed_body, '{loop_fields}' ) !== false ) {
				$email_loop = '';
				foreach ( $data as $field_name => $field_data ) {
					if ( isset( $field_data['label'] ) && isset( $field_data['value'] ) ) {
						// Skip empty fields if exclude_empty is enabled
						if ( $email_config['exclude_empty'] && empty( $field_data['value'] ) ) {
							continue;
						}

						// Process each field using the user's loop template
						$row         = $email_config['loop'];
						$row         = str_replace( '{loop_label}', esc_html( $field_data['label'] ), $row );
						$row         = str_replace( '{loop_value}', nl2br( esc_html( $field_data['value'] ) ), $row );
						$email_loop .= $row;
					}
				}

				// Wrap with loop_open and loop_close
				$loop_fields_content = $email_config['loop_open'] . $email_loop . $email_config['loop_close'];
				$processed_body      = str_replace( '{loop_fields}', $loop_fields_content, $processed_body );
			}

			// Process attachments - convert attachment IDs to file paths
			$attachments = array();

			if ( ! empty( $email_config['attachments'] ) && is_array( $email_config['attachments'] ) ) {
				foreach ( $email_config['attachments'] as $attachment_id ) {
					$attachment_id = absint( $attachment_id );

					if ( $attachment_id > 0 ) {
						// Get attachment URL (SUPER_Common::email expects URLs)
						$file_url = wp_get_attachment_url( $attachment_id );
						if ( $file_url ) {
							$attachments[] = $file_url;
						}
					}
				}
			}

			// Process other email tags
			$body       = SUPER_Common::email_tags( $processed_body, $data, $settings );
			$subject    = SUPER_Common::email_tags( $email_config['subject'], $data, $settings );
			$to         = SUPER_Common::email_tags( $email_config['to'], $data, $settings );
			$from       = SUPER_Common::email_tags( $email_config['from'], $data, $settings );
			$from_name  = SUPER_Common::email_tags( $email_config['from_name'], $data, $settings );
			$reply      = SUPER_Common::email_tags( $email_config['reply'], $data, $settings );
			$reply_name = SUPER_Common::email_tags( $email_config['reply_name'], $data, $settings );

			// Handle CSV attachment
			if ( ! empty( $email_config['csv_enabled'] ) ) {
				// Generate CSV content (this would need to be implemented based on form data)
				$csv_filename = ! empty( $email_config['csv_filename'] ) ? $email_config['csv_filename'] : 'form-entries.csv';
				// Note: For test email, we can skip actual CSV generation or create a simple test CSV
			}

			// Handle XML attachment
			if ( ! empty( $email_config['xml_enabled'] ) ) {
				// Generate XML content (this would need to be implemented based on form data)
				$xml_filename = ! empty( $email_config['xml_filename'] ) ? $email_config['xml_filename'] : 'form-entries.xml';
				// Note: For test email, we can skip actual XML generation or create a simple test XML
			}

			// Send email using SUPER_Common::email method
			$email_params = array(
				'to'          => $to,
				'from'        => $from,
				'from_name'   => $from_name,
				'reply'       => $reply,
				'reply_name'  => $reply_name,
				'cc'          => $email_config['cc'],
				'bcc'         => $email_config['bcc'],
				'subject'     => $subject,
				'body'        => $body,
				'settings'    => $settings,
				'attachments' => $attachments,
			);

			$result = SUPER_Common::email( $email_params );

			if ( $result['result'] ) {
				wp_send_json_success(
					array(
						'message' => sprintf(
							esc_html__( 'Test email sent successfully to %s', 'super-forms' ),
							$test_recipient
						),
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => sprintf(
							esc_html__( 'Failed to send test email: %s', 'super-forms' ),
							$result['error']
						),
					)
				);
			}
		}

		/**
		 * Generate dummy data based on form elements
		 *
		 * @param array $elements Form elements
		 * @return array
		 */
		private static function generate_dummy_data( $elements ) {
			$data = array();

			// Always generate comprehensive dummy data for test emails
			// If no elements provided, or for test email purposes, generate basic dummy data
			if ( empty( $elements ) || true ) { // Always use dummy data for test emails
				$data = array(
					'first_name' => array(
						'name'  => 'first_name',
						'label' => 'First Name',
						'value' => 'John',
						'type'  => 'text',
					),
					'last_name'  => array(
						'name'  => 'last_name',
						'label' => 'Last Name',
						'value' => 'Doe',
						'type'  => 'text',
					),
					'email'      => array(
						'name'  => 'email',
						'label' => 'E-mail address',
						'value' => 'john.doe@example.com',
						'type'  => 'email',
					),
					'phone'      => array(
						'name'  => 'phone',
						'label' => 'Phone number',
						'value' => '+1 (555) 123-4567',
						'type'  => 'phone',
					),
					'question'   => array(
						'name'  => 'question',
						'label' => 'Question',
						'value' => "What services are you interested in?\nI am particularly interested in web development and design services.\n\nRegards John",
						'type'  => 'text',
					),
					'choice'     => array(
						'name'  => 'choice',
						'label' => 'Select your preference',
						'value' => 'First choice',
						'type'  => 'radio',
					),
				);
				return array( 'data' => $data );
			}

			// Helper function to process elements recursively
			$process_elements = function ( $elements ) use ( &$data, &$process_elements ) {
				if ( ! is_array( $elements ) ) {
					return;
				}

				foreach ( $elements as $element ) {
					if ( isset( $element['tag'] ) ) {
						$tag = $element['tag'];
						// The element structure uses 'data' array for attributes
						$element_data = isset( $element['data'] ) ? $element['data'] : array();
						$name         = isset( $element_data['name'] ) ? $element_data['name'] : '';
						$label        = isset( $element_data['label'] ) ? $element_data['label'] : ucfirst( str_replace( '_', ' ', $name ) );

						if ( $name ) {
							switch ( $tag ) {
								case 'text':
								case 'name':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => 'Test ' . ucfirst( str_replace( '_', ' ', $name ) ),
										'type'  => 'text',
									);
									break;

								case 'email':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => 'test@example.com',
										'type'  => 'email',
									);
									break;

								case 'phone':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => '+1234567890',
										'type'  => 'phone',
									);
									break;

								case 'textarea':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => 'This is test content for the ' . $label . ' field.',
										'type'  => 'textarea',
									);
									break;

								case 'dropdown':
								case 'radio':
									// For radio, use the first option
									$value = 'Option 1';
									if ( isset( $element_data['radio_items'] ) && is_array( $element_data['radio_items'] ) && ! empty( $element_data['radio_items'] ) ) {
										$value = $element_data['radio_items'][0]['label'];
									}
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => $value,
										'type'  => $tag,
									);
									break;

								case 'checkbox':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => 'Checked',
										'type'  => 'checkbox',
									);
									break;

								case 'number':
								case 'currency':
								case 'quantity':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => '123',
										'type'  => $tag,
									);
									break;

								case 'date':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => date( 'Y-m-d' ),
										'type'  => 'date',
									);
									break;

								case 'time':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => date( 'H:i' ),
										'type'  => 'time',
									);
									break;

								case 'file':
									$data[ $name ] = array(
										'name'  => $name,
										'label' => $label,
										'value' => 'test-file.pdf',
										'type'  => 'files',
										'files' => array(
											array(
												'name'  => $name,
												'label' => $label,
												'value' => 'test-file.pdf',
												'url'   => site_url( '/wp-content/uploads/test-file.pdf' ),
												'attachment' => 0,
											),
										),
									);
									break;
							}
						}
					}

					// Process child elements
					if ( isset( $element['inner'] ) && is_array( $element['inner'] ) ) {
						$process_elements( $element['inner'] );
					}
				}
			};

			$process_elements( $elements );

			return array( 'data' => $data );
		}
	}
endif;
SUPER_Ajax::init();
