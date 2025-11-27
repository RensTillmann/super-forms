<?php
/**
 * Test Form Factory
 *
 * Creates comprehensive test forms for PHPUnit testing
 *
 * @package Super_Forms
 * @subpackage Tests/Fixtures
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SUPER_Test_Form_Factory Class
 *
 * Factory for creating test forms with all field types and configurations
 */
class SUPER_Test_Form_Factory {

	/**
	 * Created form IDs for cleanup
	 *
	 * @var array
	 */
	private static $created_forms = array();

	/**
	 * Created entry IDs for cleanup
	 *
	 * @var array
	 */
	private static $created_entries = array();

	/**
	 * Create a comprehensive form with all field types
	 *
	 * Multi-step form with:
	 * - Step 1: Personal Information (text, email, phone, dropdown)
	 * - Step 2: Project Details (textarea, checkbox, radio, file upload)
	 * - Step 3: Review & Submit (hidden fields, conditional logic)
	 *
	 * @param array $args Optional. Override default form settings.
	 * @return int Form ID
	 */
	public static function create_comprehensive_form( $args = array() ) {
		$defaults = array(
			'title' => 'Comprehensive Test Form',
		);
		$args = wp_parse_args( $args, $defaults );

		// Create multi-step elements
		$elements = array(
			// Step 1: Personal Information
			self::create_multipart_element( 'Step 1: Personal Info', 0 ),
			self::create_column_element( '1/2', array(
				self::create_text_element( 'first_name', 'First Name', array(
					'validation' => 'empty',
					'placeholder' => 'Your first name',
				)),
			)),
			self::create_column_element( '1/2', array(
				self::create_text_element( 'last_name', 'Last Name', array(
					'validation' => 'empty',
					'placeholder' => 'Your last name',
				)),
			)),
			self::create_text_element( 'email', 'Email Address', array(
				'type' => 'email',
				'validation' => 'email',
				'placeholder' => 'email@example.com',
				'icon' => 'envelope;far',
			)),
			self::create_text_element( 'phone', 'Phone Number', array(
				'validation' => 'phone',
				'placeholder' => '+1 (555) 123-4567',
				'icon' => 'phone',
			)),
			self::create_dropdown_element( 'country', 'Country', array(
				array( 'label' => 'United States', 'value' => 'US' ),
				array( 'label' => 'Canada', 'value' => 'CA' ),
				array( 'label' => 'United Kingdom', 'value' => 'UK' ),
				array( 'label' => 'Australia', 'value' => 'AU' ),
				array( 'label' => 'Other', 'value' => 'other' ),
			)),

			// Step 2: Project Details
			self::create_multipart_element( 'Step 2: Project Details', 1 ),
			self::create_textarea_element( 'message', 'Project Description', array(
				'placeholder' => 'Tell us about your project...',
				'validation' => 'empty',
			)),
			self::create_dropdown_element( 'budget', 'Budget Range', array(
				array( 'label' => 'Under $5,000', 'value' => '5000' ),
				array( 'label' => '$5,000 - $10,000', 'value' => '10000' ),
				array( 'label' => '$10,000 - $25,000', 'value' => '25000' ),
				array( 'label' => '$25,000 - $50,000', 'value' => '50000' ),
				array( 'label' => 'Over $50,000', 'value' => '50001' ),
			)),
			self::create_checkbox_element( 'services', 'Services Needed', array(
				array( 'label' => 'Web Design', 'value' => 'web_design' ),
				array( 'label' => 'Web Development', 'value' => 'web_dev' ),
				array( 'label' => 'Mobile App', 'value' => 'mobile' ),
				array( 'label' => 'SEO', 'value' => 'seo' ),
				array( 'label' => 'Marketing', 'value' => 'marketing' ),
			)),
			self::create_radio_element( 'timeline', 'Project Timeline', array(
				array( 'label' => 'ASAP', 'value' => 'asap' ),
				array( 'label' => '1-3 months', 'value' => '1-3months' ),
				array( 'label' => '3-6 months', 'value' => '3-6months' ),
				array( 'label' => '6+ months', 'value' => '6+months' ),
			)),
			self::create_file_upload_element( 'attachments', 'Project Files', array(
				'extensions' => 'pdf,doc,docx,jpg,png',
				'max_size' => 5242880, // 5MB
			)),

			// Step 3: Review & Submit (with conditional logic)
			self::create_multipart_element( 'Step 3: Review & Submit', 2 ),
			self::create_hidden_element( 'form_timestamp', '{server_timestamp}' ),
			self::create_hidden_element( 'user_ip', '{ip}' ),
			// Enterprise requirements field - conditional on budget > $25K
			self::create_textarea_element( 'enterprise_requirements', 'Enterprise Requirements', array(
				'placeholder' => 'Please describe your enterprise-level requirements...',
				'conditional_action' => 'show',
				'conditional_trigger' => 'budget',
				'conditional_items' => array(
					array(
						'field' => 'budget',
						'logic' => 'greater_than',
						'value' => '25000',
					),
				),
			)),
			self::create_checkbox_element( 'terms', 'Terms & Conditions', array(
				array( 'label' => 'I agree to the terms and conditions', 'value' => 'agreed' ),
			), array( 'validation' => 'empty' )),
		);

		return self::create_form( $args['title'], $elements, self::get_default_settings() );
	}

	/**
	 * Create a simple contact form
	 *
	 * Basic form with name, email, subject, message
	 *
	 * @param array $args Optional. Override default form settings.
	 * @return int Form ID
	 */
	public static function create_simple_form( $args = array() ) {
		$defaults = array(
			'title' => 'Simple Contact Form',
		);
		$args = wp_parse_args( $args, $defaults );

		$elements = array(
			self::create_text_element( 'name', 'Your Name', array(
				'validation' => 'empty',
				'placeholder' => 'John Doe',
			)),
			self::create_text_element( 'email', 'Email Address', array(
				'type' => 'email',
				'validation' => 'email',
				'placeholder' => 'email@example.com',
			)),
			self::create_text_element( 'subject', 'Subject', array(
				'placeholder' => 'Subject of your message',
			)),
			self::create_textarea_element( 'message', 'Message', array(
				'validation' => 'empty',
				'placeholder' => 'Your message here...',
			)),
		);

		return self::create_form( $args['title'], $elements, self::get_default_settings() );
	}

	/**
	 * Create a multi-step form (without signature/PDF)
	 *
	 * @param array $args Optional. Override default form settings.
	 * @return int Form ID
	 */
	public static function create_multistep_form( $args = array() ) {
		$defaults = array(
			'title' => 'Multi-Step Form',
			'steps' => 3,
		);
		$args = wp_parse_args( $args, $defaults );

		$elements = array();

		// Step 1
		$elements[] = self::create_multipart_element( 'Step 1: Contact Info', 0 );
		$elements[] = self::create_text_element( 'name', 'Full Name', array( 'validation' => 'empty' ) );
		$elements[] = self::create_text_element( 'email', 'Email', array( 'validation' => 'email', 'type' => 'email' ) );

		// Step 2
		$elements[] = self::create_multipart_element( 'Step 2: Details', 1 );
		$elements[] = self::create_textarea_element( 'details', 'Additional Details' );
		$elements[] = self::create_dropdown_element( 'priority', 'Priority', array(
			array( 'label' => 'Low', 'value' => 'low' ),
			array( 'label' => 'Medium', 'value' => 'medium' ),
			array( 'label' => 'High', 'value' => 'high' ),
		));

		// Step 3
		$elements[] = self::create_multipart_element( 'Step 3: Confirmation', 2 );
		$elements[] = self::create_checkbox_element( 'confirm', 'Confirmation', array(
			array( 'label' => 'I confirm the information above is correct', 'value' => 'yes' ),
		), array( 'validation' => 'empty' ));

		return self::create_form( $args['title'], $elements, self::get_default_settings() );
	}

	/**
	 * Create a form with repeater (dynamic columns) fields
	 *
	 * @param array $args Optional. Override default form settings.
	 * @return int Form ID
	 */
	public static function create_repeater_form( $args = array() ) {
		$defaults = array(
			'title' => 'Repeater Test Form',
		);
		$args = wp_parse_args( $args, $defaults );

		$elements = array(
			self::create_text_element( 'company_name', 'Company Name', array( 'validation' => 'empty' ) ),
			// Repeater for team members
			self::create_column_element( '1/1', array(
				self::create_text_element( 'team_member_name', 'Team Member Name' ),
				self::create_text_element( 'team_member_email', 'Team Member Email', array( 'type' => 'email' ) ),
				self::create_dropdown_element( 'team_member_role', 'Role', array(
					array( 'label' => 'Developer', 'value' => 'developer' ),
					array( 'label' => 'Designer', 'value' => 'designer' ),
					array( 'label' => 'Manager', 'value' => 'manager' ),
				)),
			), array(
				'duplicate' => 'enabled',
				'duplicate_limit' => 10,
			)),
			self::create_textarea_element( 'notes', 'Additional Notes' ),
		);

		return self::create_form( $args['title'], $elements, self::get_default_settings() );
	}

	/**
	 * Create a user registration form
	 *
	 * @param array $args Optional. Override default form settings.
	 * @return int Form ID
	 */
	public static function create_registration_form( $args = array() ) {
		$defaults = array(
			'title' => 'User Registration Form',
		);
		$args = wp_parse_args( $args, $defaults );

		$elements = array(
			self::create_text_element( 'username', 'Username', array(
				'validation' => 'empty',
				'placeholder' => 'Choose a username',
			)),
			self::create_text_element( 'email', 'Email Address', array(
				'type' => 'email',
				'validation' => 'email',
			)),
			self::create_text_element( 'password', 'Password', array(
				'type' => 'password',
				'validation' => 'empty',
			)),
			self::create_text_element( 'password_confirm', 'Confirm Password', array(
				'type' => 'password',
				'validation' => 'empty',
			)),
			self::create_text_element( 'first_name', 'First Name' ),
			self::create_text_element( 'last_name', 'Last Name' ),
		);

		$settings = self::get_default_settings();
		$settings['register_login_user'] = 'true';

		return self::create_form( $args['title'], $elements, $settings );
	}

	/**
	 * Get matching test submission data for comprehensive form
	 *
	 * @return array Submission data matching comprehensive form fields
	 */
	public static function get_test_submission_data() {
		return array(
			'first_name' => array(
				'name' => 'first_name',
				'value' => 'John',
				'label' => 'First Name',
				'type' => 'text',
			),
			'last_name' => array(
				'name' => 'last_name',
				'value' => 'Doe',
				'label' => 'Last Name',
				'type' => 'text',
			),
			'email' => array(
				'name' => 'email',
				'value' => 'john.doe@example.com',
				'label' => 'Email Address',
				'type' => 'text',
			),
			'phone' => array(
				'name' => 'phone',
				'value' => '+1 (555) 123-4567',
				'label' => 'Phone Number',
				'type' => 'text',
			),
			'country' => array(
				'name' => 'country',
				'value' => 'US',
				'label' => 'Country',
				'type' => 'dropdown',
			),
			'message' => array(
				'name' => 'message',
				'value' => 'This is a test project description for our comprehensive test form.',
				'label' => 'Project Description',
				'type' => 'textarea',
			),
			'budget' => array(
				'name' => 'budget',
				'value' => '50000', // Must be > 25000 to trigger "high budget" conditions
				'label' => 'Budget Range',
				'type' => 'dropdown',
			),
			'services' => array(
				'name' => 'services',
				'value' => 'web_design,web_dev',
				'label' => 'Services Needed',
				'type' => 'checkbox',
			),
			'timeline' => array(
				'name' => 'timeline',
				'value' => '1-3months',
				'label' => 'Project Timeline',
				'type' => 'radio',
			),
			'terms' => array(
				'name' => 'terms',
				'value' => 'agreed',
				'label' => 'Terms & Conditions',
				'type' => 'checkbox',
			),
			'form_timestamp' => array(
				'name' => 'form_timestamp',
				'value' => current_time( 'mysql' ),
				'label' => 'Form Timestamp',
				'type' => 'hidden',
			),
			'user_ip' => array(
				'name' => 'user_ip',
				'value' => '127.0.0.1',
				'label' => 'User IP',
				'type' => 'hidden',
			),
		);
	}

	/**
	 * Get matching test submission data for simple form
	 *
	 * @return array Submission data matching simple form fields
	 */
	public static function get_simple_submission_data() {
		return array(
			'name' => array(
				'name' => 'name',
				'value' => 'Jane Smith',
				'label' => 'Your Name',
				'type' => 'text',
			),
			'email' => array(
				'name' => 'email',
				'value' => 'jane.smith@example.com',
				'label' => 'Email Address',
				'type' => 'text',
			),
			'subject' => array(
				'name' => 'subject',
				'value' => 'Test Subject',
				'label' => 'Subject',
				'type' => 'text',
			),
			'message' => array(
				'name' => 'message',
				'value' => 'This is a test message from the simple contact form.',
				'label' => 'Message',
				'type' => 'textarea',
			),
		);
	}

	/**
	 * Get test submission data with a specific amount for payment forms
	 *
	 * @param float $amount Payment amount.
	 * @return array Submission data for payment forms
	 */
	public static function get_payment_submission_data( $amount = 99.99 ) {
		$data = self::get_simple_submission_data();
		$data['payment_amount'] = array(
			'name' => 'payment_amount',
			'value' => (string) $amount,
			'label' => 'Payment Amount',
			'type' => 'hidden',
		);
		$data['payment_currency'] = array(
			'name' => 'payment_currency',
			'value' => 'USD',
			'label' => 'Currency',
			'type' => 'hidden',
		);
		return $data;
	}

	/**
	 * Get test submission data for repeater form
	 *
	 * @param int $team_count Number of team members.
	 * @return array Submission data with repeater fields
	 */
	public static function get_repeater_submission_data( $team_count = 3 ) {
		$data = array(
			'company_name' => array(
				'name' => 'company_name',
				'value' => 'Acme Corporation',
				'label' => 'Company Name',
				'type' => 'text',
			),
			'notes' => array(
				'name' => 'notes',
				'value' => 'Additional notes about the team.',
				'label' => 'Additional Notes',
				'type' => 'textarea',
			),
		);

		// Add repeater data
		for ( $i = 0; $i < $team_count; $i++ ) {
			$suffix = $i > 0 ? ";{$i}" : '';
			$data["team_member_name{$suffix}"] = array(
				'name' => "team_member_name{$suffix}",
				'value' => "Team Member " . ( $i + 1 ),
				'label' => 'Team Member Name',
				'type' => 'text',
			);
			$data["team_member_email{$suffix}"] = array(
				'name' => "team_member_email{$suffix}",
				'value' => "member" . ( $i + 1 ) . "@example.com",
				'label' => 'Team Member Email',
				'type' => 'text',
			);
			$data["team_member_role{$suffix}"] = array(
				'name' => "team_member_role{$suffix}",
				'value' => $i % 3 === 0 ? 'developer' : ( $i % 3 === 1 ? 'designer' : 'manager' ),
				'label' => 'Role',
				'type' => 'dropdown',
			);
		}

		return $data;
	}

	/**
	 * Create a contact entry for a form
	 *
	 * @param int   $form_id Form ID.
	 * @param array $data    Entry data.
	 * @param array $args    Optional. Additional entry arguments.
	 * @return int Entry ID
	 */
	public static function create_entry( $form_id, $data = array(), $args = array() ) {
		$defaults = array(
			'status' => 'publish',
			'title'  => 'Test Entry ' . uniqid(),
		);
		$args = wp_parse_args( $args, $defaults );

		$entry_id = wp_insert_post( array(
			'post_type'   => 'super_contact_entry',
			'post_status' => $args['status'],
			'post_title'  => $args['title'],
			'post_parent' => $form_id,
		) );

		if ( is_wp_error( $entry_id ) ) {
			return $entry_id;
		}

		// Store form ID
		update_post_meta( $entry_id, '_super_form_id', $form_id );

		// Store entry data using Data Access Layer if available
		if ( class_exists( 'SUPER_Data_Access' ) ) {
			SUPER_Data_Access::save_entry_data( $entry_id, $data );
		} else {
			update_post_meta( $entry_id, '_super_contact_entry_data', $data );
		}

		// Mark as test entry for cleanup
		update_post_meta( $entry_id, '_super_test_entry', true );

		self::$created_entries[] = $entry_id;

		return $entry_id;
	}

	/**
	 * Clean up all created forms and entries
	 */
	public static function cleanup() {
		// Delete created entries
		foreach ( self::$created_entries as $entry_id ) {
			wp_delete_post( $entry_id, true );
		}
		self::$created_entries = array();

		// Delete created forms
		foreach ( self::$created_forms as $form_id ) {
			wp_delete_post( $form_id, true );
		}
		self::$created_forms = array();
	}

	/**
	 * Get all created form IDs
	 *
	 * @return array Form IDs
	 */
	public static function get_created_forms() {
		return self::$created_forms;
	}

	/**
	 * Get all created entry IDs
	 *
	 * @return array Entry IDs
	 */
	public static function get_created_entries() {
		return self::$created_entries;
	}

	// =========================================================================
	// Private Helper Methods
	// =========================================================================

	/**
	 * Create a form post with elements and settings
	 *
	 * @param string $title    Form title.
	 * @param array  $elements Form elements.
	 * @param array  $settings Form settings.
	 * @return int Form ID
	 */
	private static function create_form( $title, $elements, $settings ) {
		$form_id = wp_insert_post( array(
			'post_type'   => 'super_form',
			'post_status' => 'publish',
			'post_title'  => $title,
		) );

		if ( is_wp_error( $form_id ) ) {
			return $form_id;
		}

		// Store elements as JSON
		update_post_meta( $form_id, '_super_elements', wp_json_encode( $elements ) );

		// Store settings
		update_post_meta( $form_id, '_super_form_settings', $settings );

		// Mark as test form for cleanup
		update_post_meta( $form_id, '_super_test_form', true );

		self::$created_forms[] = $form_id;

		return $form_id;
	}

	/**
	 * Get default form settings
	 *
	 * @return array Default settings
	 */
	private static function get_default_settings() {
		return array(
			'save_contact_entry'          => 'yes',
			'form_submit_button_text'     => 'Submit',
			'form_submit_button_loading'  => 'Loading...',
			'form_show_thanks_msg'        => 'yes',
			'form_thanks_title'           => 'Thank You!',
			'form_thanks_description'     => 'Your submission has been received.',
			'form_preload'                => '1',
			'form_duration'               => '500',
			'theme_form_margin'           => '0px 0px 30px 0px',
			'form_recaptcha'              => '',
			'send'                        => '',
			'confirm'                     => '',
		);
	}

	/**
	 * Create a text element
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param array  $atts  Additional attributes.
	 * @return array Element array
	 */
	private static function create_text_element( $name, $label, $atts = array() ) {
		$defaults = array(
			'name'              => $name,
			'email'             => $label . ':',
			'placeholder'       => '',
			'placeholderFilled' => $label,
			'type'              => 'text',
			'validation'        => '',
			'icon'              => '',
		);
		return array(
			'tag'   => 'text',
			'group' => 'form_elements',
			'data'  => wp_parse_args( $atts, $defaults ),
		);
	}

	/**
	 * Create a textarea element
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param array  $atts  Additional attributes.
	 * @return array Element array
	 */
	private static function create_textarea_element( $name, $label, $atts = array() ) {
		$defaults = array(
			'name'        => $name,
			'email'       => $label . ':',
			'placeholder' => '',
			'validation'  => '',
			'rows'        => 4,
		);
		return array(
			'tag'   => 'textarea',
			'group' => 'form_elements',
			'data'  => wp_parse_args( $atts, $defaults ),
		);
	}

	/**
	 * Create a dropdown element
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param array  $options Dropdown options (label/value pairs).
	 * @param array  $atts    Additional attributes.
	 * @return array Element array
	 */
	private static function create_dropdown_element( $name, $label, $options, $atts = array() ) {
		$items = array();
		foreach ( $options as $option ) {
			$items[] = array(
				'checked' => false,
				'label'   => $option['label'],
				'value'   => $option['value'],
			);
		}

		$defaults = array(
			'name'           => $name,
			'email'          => $label . ':',
			'placeholder'    => '- Select -',
			'validation'     => '',
			'dropdown_items' => $items,
		);
		return array(
			'tag'   => 'dropdown',
			'group' => 'form_elements',
			'data'  => wp_parse_args( $atts, $defaults ),
		);
	}

	/**
	 * Create a checkbox element
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param array  $options Checkbox options (label/value pairs).
	 * @param array  $atts    Additional attributes.
	 * @return array Element array
	 */
	private static function create_checkbox_element( $name, $label, $options, $atts = array() ) {
		$items = array();
		foreach ( $options as $option ) {
			$items[] = array(
				'checked' => false,
				'label'   => $option['label'],
				'value'   => $option['value'],
			);
		}

		$defaults = array(
			'name'           => $name,
			'email'          => $label . ':',
			'validation'     => '',
			'checkbox_items' => $items,
		);
		return array(
			'tag'   => 'checkbox',
			'group' => 'form_elements',
			'data'  => wp_parse_args( $atts, $defaults ),
		);
	}

	/**
	 * Create a radio element
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param array  $options Radio options (label/value pairs).
	 * @param array  $atts    Additional attributes.
	 * @return array Element array
	 */
	private static function create_radio_element( $name, $label, $options, $atts = array() ) {
		$items = array();
		foreach ( $options as $option ) {
			$items[] = array(
				'checked' => false,
				'label'   => $option['label'],
				'value'   => $option['value'],
			);
		}

		$defaults = array(
			'name'        => $name,
			'email'       => $label . ':',
			'validation'  => '',
			'radio_items' => $items,
		);
		return array(
			'tag'   => 'radio',
			'group' => 'form_elements',
			'data'  => wp_parse_args( $atts, $defaults ),
		);
	}

	/**
	 * Create a hidden element
	 *
	 * @param string $name  Field name.
	 * @param string $value Field value (can include {tags}).
	 * @return array Element array
	 */
	private static function create_hidden_element( $name, $value ) {
		return array(
			'tag'   => 'hidden',
			'group' => 'form_elements',
			'data'  => array(
				'name'  => $name,
				'value' => $value,
				'email' => $name . ':',
			),
		);
	}

	/**
	 * Create a file upload element
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param array  $atts  Additional attributes.
	 * @return array Element array
	 */
	private static function create_file_upload_element( $name, $label, $atts = array() ) {
		$defaults = array(
			'name'                => $name,
			'email'               => $label . ':',
			'extensions'          => 'pdf,doc,docx',
			'max_size'            => 5242880,
			'max_files'           => 5,
			'placeholder'         => 'Drop files here or click to upload',
			'validation'          => '',
		);
		return array(
			'tag'   => 'file',
			'group' => 'form_elements',
			'data'  => wp_parse_args( $atts, $defaults ),
		);
	}

	/**
	 * Create a column layout element
	 *
	 * @param string $size   Column size (1/1, 1/2, 1/3, etc.).
	 * @param array  $inner  Inner elements.
	 * @param array  $atts   Additional attributes.
	 * @return array Element array
	 */
	private static function create_column_element( $size, $inner, $atts = array() ) {
		$defaults = array(
			'size' => $size,
		);
		return array(
			'tag'   => 'column',
			'group' => 'layout_elements',
			'data'  => wp_parse_args( $atts, $defaults ),
			'inner' => $inner,
		);
	}

	/**
	 * Create a multipart (step) element
	 *
	 * @param string $title Step title.
	 * @param int    $step  Step number (0-based).
	 * @return array Element array
	 */
	private static function create_multipart_element( $title, $step ) {
		return array(
			'tag'   => 'multipart',
			'group' => 'layout_elements',
			'data'  => array(
				'step'  => $step,
				'title' => $title,
				'icon'  => '',
			),
		);
	}
}
