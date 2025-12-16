<?php
/**
 * Generate File Action
 *
 * Generates files (PDF, CSV, HTML) from form data using templates.
 * Uses hybrid rendering: server-side for CSV/HTML, client-side instructions for PDF.
 *
 * @package Super_Forms
 * @subpackage Automations/Actions
 * @since 6.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUPER_Action_Generate_File extends SUPER_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'generate_file';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Generate File', 'super-forms' );
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'utility';
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Generate PDF, CSV, or HTML files from form data using customizable templates', 'super-forms' );
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name'        => 'output_format',
				'label'       => __( 'Output Format', 'super-forms' ),
				'type'        => 'select',
				'required'    => true,
				'default'     => 'pdf',
				'options'     => [
					'pdf'  => __( 'PDF Document', 'super-forms' ),
					'csv'  => __( 'CSV Spreadsheet', 'super-forms' ),
					'html' => __( 'HTML File', 'super-forms' ),
				],
				'description' => __( 'File format to generate', 'super-forms' ),
			],
			[
				'name'        => 'template_source',
				'label'       => __( 'Template Source', 'super-forms' ),
				'type'        => 'select',
				'required'    => true,
				'default'     => 'html',
				'options'     => [
					'html'          => __( 'Custom HTML Template', 'super-forms' ),
					'entry_summary' => __( 'Entry Summary (Auto)', 'super-forms' ),
					'fields_list'   => __( 'Fields List (Simple)', 'super-forms' ),
				],
				'description' => __( 'Source of content for the file', 'super-forms' ),
			],
			[
				'name'        => 'html_template',
				'label'       => __( 'HTML Template', 'super-forms' ),
				'type'        => 'wysiwyg',
				'required'    => false,
				'default'     => '<h1>Form Submission</h1><p>Name: {name}</p><p>Email: {email}</p>',
				'description' => __( 'HTML content with {field_name} tags for form data', 'super-forms' ),
				'show_if'     => [ 'template_source' => 'html' ],
			],
			[
				'name'        => 'fields_to_include',
				'label'       => __( 'Fields to Include', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => 'name, email, message',
				'description' => __( 'Comma-separated field names. Leave empty to include all fields.', 'super-forms' ),
				'show_if'     => [ 'template_source' => [ 'entry_summary', 'fields_list' ] ],
			],
			[
				'name'        => 'fields_to_exclude',
				'label'       => __( 'Fields to Exclude', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => 'hidden_field, internal_id',
				'description' => __( 'Comma-separated field names to exclude', 'super-forms' ),
			],
			[
				'name'        => 'filename_pattern',
				'label'       => __( 'Filename', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => 'document_{entry_id}',
				'placeholder' => 'receipt_{name}_{date}',
				'description' => __( 'Supports {field_name} tags. Extension added automatically.', 'super-forms' ),
			],
			[
				'name'        => 'page_size',
				'label'       => __( 'Page Size', 'super-forms' ),
				'type'        => 'select',
				'required'    => false,
				'default'     => 'a4',
				'options'     => [
					'a4'     => 'A4',
					'letter' => 'Letter',
					'legal'  => 'Legal',
				],
				'description' => __( 'Page size for PDF output', 'super-forms' ),
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'orientation',
				'label'       => __( 'Orientation', 'super-forms' ),
				'type'        => 'select',
				'required'    => false,
				'default'     => 'portrait',
				'options'     => [
					'portrait'  => __( 'Portrait', 'super-forms' ),
					'landscape' => __( 'Landscape', 'super-forms' ),
				],
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'pdf_generation_method',
				'label'       => __( 'PDF Generation Method', 'super-forms' ),
				'type'        => 'select',
				'required'    => false,
				'default'     => 'auto',
				'options'     => [
					'auto'   => __( 'Automatic (API with fallback)', 'super-forms' ),
					'api'    => __( 'API Only (vector PDF)', 'super-forms' ),
					'client' => __( 'Client-Side Only (legacy)', 'super-forms' ),
				],
				'description' => __( 'API generates high-quality vector PDFs with selectable text', 'super-forms' ),
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'pdf_header_template',
				'label'       => __( 'PDF Header', 'super-forms' ),
				'type'        => 'textarea',
				'required'    => false,
				'default'     => '',
				'placeholder' => '<div style="font-size:10px;text-align:center;">Company Name</div>',
				'description' => __( 'HTML template for page header. Supports {field_name} tags.', 'super-forms' ),
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'pdf_footer_template',
				'label'       => __( 'PDF Footer', 'super-forms' ),
				'type'        => 'textarea',
				'required'    => false,
				'default'     => '<div style="font-size:10px;text-align:center;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>',
				'placeholder' => 'Page <span class="pageNumber"></span>',
				'description' => __( 'HTML template for page footer. Use pageNumber and totalPages classes.', 'super-forms' ),
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'pdf_margin_top',
				'label'       => __( 'Top Margin', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '10mm',
				'placeholder' => '10mm',
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'pdf_margin_bottom',
				'label'       => __( 'Bottom Margin', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '10mm',
				'placeholder' => '10mm',
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'pdf_margin_left',
				'label'       => __( 'Left Margin', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '10mm',
				'placeholder' => '10mm',
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'pdf_margin_right',
				'label'       => __( 'Right Margin', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '10mm',
				'placeholder' => '10mm',
				'show_if'     => [ 'output_format' => 'pdf' ],
			],
			[
				'name'        => 'include_header',
				'label'       => __( 'Include Header', 'super-forms' ),
				'type'        => 'toggle',
				'required'    => false,
				'default'     => true,
				'description' => __( 'Include column headers in CSV', 'super-forms' ),
				'show_if'     => [ 'output_format' => 'csv' ],
			],
			[
				'name'        => 'csv_delimiter',
				'label'       => __( 'CSV Delimiter', 'super-forms' ),
				'type'        => 'select',
				'required'    => false,
				'default'     => ',',
				'options'     => [
					','  => __( 'Comma (,)', 'super-forms' ),
					';'  => __( 'Semicolon (;)', 'super-forms' ),
					'\t' => __( 'Tab', 'super-forms' ),
				],
				'show_if'     => [ 'output_format' => 'csv' ],
			],
			[
				'name'        => 'upload_to_media',
				'label'       => __( 'Save to Media Library', 'super-forms' ),
				'type'        => 'toggle',
				'required'    => false,
				'default'     => false,
				'description' => __( 'Store the generated file in WordPress Media Library', 'super-forms' ),
			],
			[
				'name'        => 'css_styles',
				'label'       => __( 'Custom CSS', 'super-forms' ),
				'type'        => 'textarea',
				'required'    => false,
				'default'     => '',
				'placeholder' => 'body { font-family: Arial, sans-serif; }',
				'description' => __( 'Custom CSS styles for PDF/HTML output', 'super-forms' ),
				'show_if'     => [ 'output_format' => [ 'pdf', 'html' ] ],
			],
		];
	}

	/**
	 * Execute the action
	 *
	 * @param array $context Event context data.
	 * @param array $config  Action configuration.
	 * @return array|WP_Error Result data or error.
	 */
	public function execute( $context, $config ) {
		$output_format = $config['output_format'] ?? 'pdf';

		switch ( $output_format ) {
			case 'csv':
				return $this->generate_csv( $context, $config );

			case 'html':
				return $this->generate_html( $context, $config );

			case 'pdf':
				return $this->generate_pdf( $context, $config );

			default:
				return new WP_Error(
					'unsupported_format',
					sprintf( __( 'Unsupported output format: %s', 'super-forms' ), $output_format )
				);
		}
	}

	/**
	 * Generate CSV file (server-side)
	 *
	 * @param array $context Event context.
	 * @param array $config  Action config.
	 * @return array|WP_Error Result.
	 */
	private function generate_csv( $context, $config ) {
		$form_data  = $this->get_filtered_data( $context, $config );
		$delimiter  = $config['csv_delimiter'] ?? ',';
		$include_header = $config['include_header'] ?? true;

		if ( '\t' === $delimiter ) {
			$delimiter = "\t";
		}

		// Build CSV content.
		$csv_content = '';

		if ( $include_header ) {
			$headers     = array_keys( $form_data );
			$csv_content .= $this->array_to_csv_line( $headers, $delimiter );
		}

		$values       = array_values( $form_data );
		$csv_content .= $this->array_to_csv_line( $values, $delimiter );

		// Generate filename.
		$filename = $this->generate_filename( $context, $config, 'csv' );

		// Save file.
		$file_path = $this->save_file( $csv_content, $filename, 'text/csv', $config );

		if ( is_wp_error( $file_path ) ) {
			return $file_path;
		}

		return [
			'success' => true,
			'data'    => [
				'file_url'      => $file_path['url'],
				'file_path'     => $file_path['path'],
				'filename'      => $filename,
				'file_size'     => strlen( $csv_content ),
				'attachment_id' => $file_path['attachment_id'] ?? null,
				'message'       => __( 'CSV file generated successfully', 'super-forms' ),
			],
		];
	}

	/**
	 * Generate HTML file (server-side)
	 *
	 * @param array $context Event context.
	 * @param array $config  Action config.
	 * @return array|WP_Error Result.
	 */
	private function generate_html( $context, $config ) {
		$html_content = $this->build_html_content( $context, $config );

		// Wrap in full HTML document.
		$css = $config['css_styles'] ?? '';
		$full_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' .
			wp_strip_all_tags( $css ) .
			'</style></head><body>' .
			$html_content .
			'</body></html>';

		// Generate filename.
		$filename = $this->generate_filename( $context, $config, 'html' );

		// Save file.
		$file_path = $this->save_file( $full_html, $filename, 'text/html', $config );

		if ( is_wp_error( $file_path ) ) {
			return $file_path;
		}

		return [
			'success' => true,
			'data'    => [
				'file_url'      => $file_path['url'],
				'file_path'     => $file_path['path'],
				'filename'      => $filename,
				'file_size'     => strlen( $full_html ),
				'attachment_id' => $file_path['attachment_id'] ?? null,
				'message'       => __( 'HTML file generated successfully', 'super-forms' ),
			],
		];
	}

	/**
	 * Generate PDF (hybrid: API primary, client/TCPDF fallback)
	 *
	 * Priority order:
	 * 1. PDF API Service (if enabled)
	 * 2. Client-side rendering (if frontend event)
	 * 3. TCPDF/DOMPDF (if available)
	 *
	 * @param array $context Event context.
	 * @param array $config  Action config.
	 * @return array|WP_Error Result.
	 */
	private function generate_pdf( $context, $config ) {
		$html_content = $this->build_html_content( $context, $config );
		$css          = $config['css_styles'] ?? '';
		$filename     = $this->generate_filename( $context, $config, 'pdf' );

		// Build full HTML with styles.
		$full_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' .
			$this->get_default_pdf_styles() .
			wp_strip_all_tags( $css ) .
			'</style></head><body>' .
			$html_content .
			'</body></html>';

		// Determine generation method.
		$method = $config['pdf_generation_method'] ?? 'auto';

		// Check if this is a frontend event (button click).
		$is_frontend_event = ! empty( $context['event_source'] ) && 'frontend' === $context['event_source'];
		$is_button_event   = ! empty( $context['event_id'] ) && strpos( $context['event_id'], 'button.' ) === 0;

		// Try PDF API first (unless explicitly set to client-only).
		if ( 'client' !== $method && $this->should_use_pdf_api( $config ) ) {
			$api_result = $this->generate_pdf_via_api( $full_html, $filename, $config, $context );

			if ( ! is_wp_error( $api_result ) ) {
				return $api_result;
			}

			// Log API error.
			$error_code = $api_result->get_error_code();
			$error_msg  = $api_result->get_error_message();

			// Don't fallback for license errors (prevent bypassing licensing).
			if ( in_array( $error_code, [ 'license_not_found', 'license_expired', 'license_suspended' ], true ) ) {
				// Cache the error for admin notice.
				set_transient( 'super_pdf_api_last_error', $error_code, HOUR_IN_SECONDS );
				return $api_result;
			}

			// If method is 'api', don't fallback.
			if ( 'api' === $method ) {
				return $api_result;
			}

			// Log and continue to fallback.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[Super Forms PDF] API error (%s): %s - falling back', $error_code, $error_msg ) );
		}

		// Fallback: Client-side rendering for frontend events.
		if ( $is_frontend_event || $is_button_event ) {
			return [
				'success' => true,
				'data'    => [
					'client_render' => true,
					'html'          => $full_html,
					'filename'      => $filename,
					'page_size'     => $config['page_size'] ?? 'a4',
					'orientation'   => $config['orientation'] ?? 'portrait',
					'message'       => __( 'PDF ready for download', 'super-forms' ),
				],
			];
		}

		// Fallback: Server-side libraries.
		if ( class_exists( 'TCPDF' ) ) {
			return $this->generate_pdf_tcpdf( $full_html, $filename, $config );
		}

		if ( class_exists( 'Dompdf\\Dompdf' ) ) {
			return $this->generate_pdf_dompdf( $full_html, $filename, $config );
		}

		// No generation method available.
		return new WP_Error(
			'pdf_not_available',
			__( 'PDF generation failed. Ensure your domain has an active PDF license, or install TCPDF/DOMPDF for server-side fallback.', 'super-forms' )
		);
	}

	/**
	 * Generate PDF using TCPDF (if available)
	 *
	 * @param string $html     HTML content.
	 * @param string $filename Filename.
	 * @param array  $config   Config.
	 * @return array|WP_Error Result.
	 */
	private function generate_pdf_tcpdf( $html, $filename, $config ) {
		try {
			$orientation = 'portrait' === ( $config['orientation'] ?? 'portrait' ) ? 'P' : 'L';
			$page_size   = strtoupper( $config['page_size'] ?? 'A4' );

			$pdf = new TCPDF( $orientation, 'mm', $page_size, true, 'UTF-8' );
			$pdf->SetCreator( 'Super Forms' );
			$pdf->SetAuthor( get_bloginfo( 'name' ) );
			$pdf->setPrintHeader( false );
			$pdf->setPrintFooter( false );
			$pdf->AddPage();
			$pdf->writeHTML( $html, true, false, true, false, '' );

			$upload_dir = wp_upload_dir();
			$file_path  = $upload_dir['basedir'] . '/super-forms-files/' . $filename;

			// Ensure directory exists.
			wp_mkdir_p( dirname( $file_path ) );

			$pdf->Output( $file_path, 'F' );

			$file_url = $upload_dir['baseurl'] . '/super-forms-files/' . $filename;

			return [
				'success' => true,
				'data'    => [
					'file_url'  => $file_url,
					'file_path' => $file_path,
					'filename'  => $filename,
					'file_size' => filesize( $file_path ),
					'message'   => __( 'PDF generated successfully', 'super-forms' ),
				],
			];
		} catch ( Exception $e ) {
			return new WP_Error( 'pdf_error', $e->getMessage() );
		}
	}

	/**
	 * Generate PDF using DOMPDF (if available)
	 *
	 * @param string $html     HTML content.
	 * @param string $filename Filename.
	 * @param array  $config   Config.
	 * @return array|WP_Error Result.
	 */
	private function generate_pdf_dompdf( $html, $filename, $config ) {
		try {
			$options = new \Dompdf\Options();
			$options->set( 'isHtml5ParserEnabled', true );
			$options->set( 'isRemoteEnabled', true );

			$dompdf = new \Dompdf\Dompdf( $options );
			$dompdf->loadHtml( $html );

			$page_size   = strtoupper( $config['page_size'] ?? 'A4' );
			$orientation = $config['orientation'] ?? 'portrait';
			$dompdf->setPaper( $page_size, $orientation );

			$dompdf->render();

			$upload_dir = wp_upload_dir();
			$file_path  = $upload_dir['basedir'] . '/super-forms-files/' . $filename;

			wp_mkdir_p( dirname( $file_path ) );

			file_put_contents( $file_path, $dompdf->output() );

			$file_url = $upload_dir['baseurl'] . '/super-forms-files/' . $filename;

			return [
				'success' => true,
				'data'    => [
					'file_url'  => $file_url,
					'file_path' => $file_path,
					'filename'  => $filename,
					'file_size' => filesize( $file_path ),
					'message'   => __( 'PDF generated successfully', 'super-forms' ),
				],
			];
		} catch ( Exception $e ) {
			return new WP_Error( 'pdf_error', $e->getMessage() );
		}
	}

	/**
	 * Check if PDF API should be used
	 *
	 * @param array $config Action config.
	 * @return bool
	 */
	private function should_use_pdf_api( $config ) {
		// Check global setting.
		$global_settings = get_option( 'super_settings', [] );
		$api_enabled     = $global_settings['pdf_api_enabled'] ?? true;

		if ( ! $api_enabled ) {
			return false;
		}

		/**
		 * Filter whether to use PDF API service.
		 *
		 * @param bool  $use_api Whether to use API.
		 * @param array $config  Action configuration.
		 */
		return apply_filters( 'super_use_pdf_api_service', true, $config );
	}

	/**
	 * Generate PDF via API service
	 *
	 * License validation is performed server-side by the API based on the site_url domain.
	 * No license key is stored or sent from WordPress.
	 *
	 * @param string $html     Full HTML content.
	 * @param string $filename Target filename.
	 * @param array  $config   Action configuration.
	 * @param array  $context  Event context.
	 * @return array|WP_Error Result data or error.
	 */
	private function generate_pdf_via_api( $html, $filename, $config, $context ) {
		// Build API request.
		$api_url = $this->get_pdf_api_endpoint();

		// License is validated server-side by domain lookup in MongoDB.
		// WordPress only sends the site_url - no license key required.
		$request_body = [
			'html'     => $html,
			'options'  => $this->build_pdf_api_options( $config, $context ),
			'site_url' => home_url( '/' ),
			'form_id'  => $context['form_id'] ?? null,
		];

		// Send request.
		$response = wp_remote_post( $api_url, [
			'timeout'     => 60, // PDF generation can take time.
			'httpversion' => '1.1',
			'headers'     => [
				'Content-Type' => 'application/json',
				'User-Agent'   => 'Super-Forms/' . ( defined( 'SUPER_VERSION' ) ? SUPER_VERSION : '6.7.0' ),
			],
			'body'        => wp_json_encode( $request_body ),
		] );

		// Handle connection errors.
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_connection_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'Could not connect to PDF API: %s', 'super-forms' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle API errors.
		if ( 200 !== $status_code || empty( $body['success'] ) ) {
			$error_code = $body['error_code'] ?? 'api_error';
			$error_msg  = $body['error'] ?? __( 'Unknown PDF API error', 'super-forms' );

			// Map error codes for better handling.
			$error_code_map = [
				'LICENSE_NOT_FOUND'   => 'license_not_found',
				'LICENSE_EXPIRED'     => 'license_expired',
				'LICENSE_SUSPENDED'   => 'license_suspended',
				'RATE_LIMIT_EXCEEDED' => 'rate_limit_exceeded',
				'RENDER_TIMEOUT'      => 'render_timeout',
				'CHROME_CRASHED'      => 'chrome_crashed',
				'PAYLOAD_TOO_LARGE'   => 'payload_too_large',
				'SERVICE_BUSY'        => 'service_busy',
			];

			$mapped_code = $error_code_map[ $error_code ] ?? $error_code;

			// Include retry_after for rate limiting.
			if ( 'rate_limit_exceeded' === $mapped_code && ! empty( $body['retry_after'] ) ) {
				$error_msg .= sprintf(
					/* translators: %d: seconds */
					' ' . __( 'Please try again in %d seconds.', 'super-forms' ),
					(int) $body['retry_after']
				);
			}

			return new WP_Error( $mapped_code, $error_msg, [
				'status_code' => $status_code,
				'retry_after' => $body['retry_after'] ?? null,
			] );
		}

		// Decode base64 PDF content.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$pdf_content = base64_decode( $body['pdf_base64'] );

		if ( false === $pdf_content ) {
			return new WP_Error(
				'decode_error',
				__( 'Failed to decode PDF content from API response', 'super-forms' )
			);
		}

		// Save to file system.
		$file_path = $this->save_file( $pdf_content, $filename, 'application/pdf', $config );

		if ( is_wp_error( $file_path ) ) {
			return $file_path;
		}

		return [
			'success' => true,
			'data'    => [
				'file_url'      => $file_path['url'],
				'file_path'     => $file_path['path'],
				'filename'      => $filename,
				'file_size'     => strlen( $pdf_content ),
				'pages'         => $body['pages'] ?? null,
				'render_time'   => $body['render_time_ms'] ?? null,
				'attachment_id' => $file_path['attachment_id'] ?? null,
				'generated_via' => 'api',
				'message'       => __( 'PDF generated successfully via API', 'super-forms' ),
			],
		];
	}

	/**
	 * Get PDF API endpoint URL
	 *
	 * @return string API endpoint URL.
	 */
	private function get_pdf_api_endpoint() {
		// Use constant for testing against dev API.
		if ( defined( 'SUPER_PDF_API_ENDPOINT' ) ) {
			return SUPER_PDF_API_ENDPOINT;
		}

		// Use main API endpoint.
		$api_base = defined( 'SUPER_API_ENDPOINT' ) ? SUPER_API_ENDPOINT : 'https://api.super-forms.com/v1';

		$endpoint = trailingslashit( $api_base ) . 'pdf/generate';

		/**
		 * Filter PDF API endpoint URL.
		 *
		 * @param string $endpoint API endpoint URL.
		 */
		return apply_filters( 'super_pdf_api_endpoint', $endpoint );
	}

	/**
	 * Build PDF API options from action config
	 *
	 * @param array $config  Action configuration.
	 * @param array $context Event context.
	 * @return array API options.
	 */
	private function build_pdf_api_options( $config, $context ) {
		$options = [
			'format'            => strtoupper( $config['page_size'] ?? 'A4' ),
			'orientation'       => $config['orientation'] ?? 'portrait',
			'margin'            => [
				'top'    => $config['pdf_margin_top'] ?? '10mm',
				'right'  => $config['pdf_margin_right'] ?? '10mm',
				'bottom' => $config['pdf_margin_bottom'] ?? '10mm',
				'left'   => $config['pdf_margin_left'] ?? '10mm',
			],
			'printBackground'   => true,
			'scale'             => 1.0,
			'preferCSSPageSize' => false,
		];

		// Add header/footer if configured.
		$header_template = $config['pdf_header_template'] ?? '';
		$footer_template = $config['pdf_footer_template'] ?? '';

		if ( ! empty( $header_template ) || ! empty( $footer_template ) ) {
			$options['displayHeaderFooter'] = true;

			if ( ! empty( $header_template ) ) {
				// Replace field tags in header.
				$options['headerTemplate'] = $this->replace_variables(
					$header_template,
					array_merge( $context, $context['form_data'] ?? [] ),
					[ 'sanitize' => 'html' ]
				);
			}

			if ( ! empty( $footer_template ) ) {
				// Replace field tags in footer.
				$options['footerTemplate'] = $this->replace_variables(
					$footer_template,
					array_merge( $context, $context['form_data'] ?? [] ),
					[ 'sanitize' => 'html' ]
				);
			}
		}

		/**
		 * Filter PDF API options.
		 *
		 * @param array $options API options.
		 * @param array $config  Action configuration.
		 * @param array $context Event context.
		 */
		return apply_filters( 'super_pdf_api_options', $options, $config, $context );
	}

	/**
	 * Build HTML content from template
	 *
	 * @param array $context Event context.
	 * @param array $config  Action config.
	 * @return string HTML content.
	 */
	private function build_html_content( $context, $config ) {
		$template_source = $config['template_source'] ?? 'html';
		$form_data       = $this->get_filtered_data( $context, $config );

		switch ( $template_source ) {
			case 'html':
				$template = $config['html_template'] ?? '';
				return $this->replace_variables( $template, array_merge( $context, $form_data ), [
					'sanitize'   => 'html',
					'allow_html' => true,
				] );

			case 'entry_summary':
				return $this->build_entry_summary( $form_data, $context );

			case 'fields_list':
				return $this->build_fields_list( $form_data );

			default:
				return '';
		}
	}

	/**
	 * Build entry summary HTML
	 *
	 * @param array $form_data Filtered form data.
	 * @param array $context   Event context.
	 * @return string HTML.
	 */
	private function build_entry_summary( $form_data, $context ) {
		$html = '<div class="entry-summary">';
		$html .= '<h2>' . esc_html__( 'Form Submission Summary', 'super-forms' ) . '</h2>';

		$html .= '<table class="summary-table" style="width:100%; border-collapse: collapse;">';
		$html .= '<thead><tr><th style="border: 1px solid #ddd; padding: 8px; text-align: left;">' .
			esc_html__( 'Field', 'super-forms' ) . '</th>';
		$html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">' .
			esc_html__( 'Value', 'super-forms' ) . '</th></tr></thead>';
		$html .= '<tbody>';

		foreach ( $form_data as $field => $value ) {
			$label        = ucfirst( str_replace( [ '_', '-' ], ' ', $field ) );
			$display_value = is_array( $value ) ? implode( ', ', $value ) : $value;

			$html .= '<tr>';
			$html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html( $label ) . '</td>';
			$html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html( $display_value ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';

		// Add metadata.
		$html .= '<p class="metadata" style="margin-top: 20px; font-size: 12px; color: #666;">';
		if ( ! empty( $context['entry_id'] ) ) {
			$html .= esc_html__( 'Entry ID: ', 'super-forms' ) . esc_html( $context['entry_id'] ) . '<br>';
		}
		$html .= esc_html__( 'Generated: ', 'super-forms' ) . esc_html( current_time( 'Y-m-d H:i:s' ) );
		$html .= '</p>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Build simple fields list HTML
	 *
	 * @param array $form_data Filtered form data.
	 * @return string HTML.
	 */
	private function build_fields_list( $form_data ) {
		$html = '<ul class="fields-list">';

		foreach ( $form_data as $field => $value ) {
			$label        = ucfirst( str_replace( [ '_', '-' ], ' ', $field ) );
			$display_value = is_array( $value ) ? implode( ', ', $value ) : $value;
			$html .= '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $display_value ) . '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Get filtered form data based on config
	 *
	 * @param array $context Event context.
	 * @param array $config  Action config.
	 * @return array Filtered form data.
	 */
	private function get_filtered_data( $context, $config ) {
		$form_data = $context['form_data'] ?? [];

		// Fields to include (whitelist).
		$include = $config['fields_to_include'] ?? '';
		if ( ! empty( $include ) ) {
			$include_fields = array_map( 'trim', explode( ',', $include ) );
			$form_data      = array_intersect_key( $form_data, array_flip( $include_fields ) );
		}

		// Fields to exclude (blacklist).
		$exclude = $config['fields_to_exclude'] ?? '';
		if ( ! empty( $exclude ) ) {
			$exclude_fields = array_map( 'trim', explode( ',', $exclude ) );
			$form_data      = array_diff_key( $form_data, array_flip( $exclude_fields ) );
		}

		// Filter out internal/hidden fields by default.
		$internal_prefixes = [ '_', 'hidden_', 'internal_' ];
		foreach ( array_keys( $form_data ) as $field ) {
			foreach ( $internal_prefixes as $prefix ) {
				if ( 0 === strpos( $field, $prefix ) ) {
					unset( $form_data[ $field ] );
					break;
				}
			}
		}

		return $form_data;
	}

	/**
	 * Generate filename with tag replacement
	 *
	 * @param array  $context   Event context.
	 * @param array  $config    Action config.
	 * @param string $extension File extension.
	 * @return string Filename.
	 */
	private function generate_filename( $context, $config, $extension ) {
		$pattern = $config['filename_pattern'] ?? 'document_{entry_id}';

		// Add standard context variables.
		$variables = array_merge(
			$context,
			$context['form_data'] ?? [],
			[
				'date'      => gmdate( 'Y-m-d' ),
				'time'      => gmdate( 'H-i-s' ),
				'timestamp' => time(),
			]
		);

		// Replace tags.
		$filename = $this->replace_variables( $pattern, $variables, [
			'sanitize'         => 'text',
			'missing_behavior' => 'empty',
		] );

		// Sanitize filename.
		$filename = sanitize_file_name( $filename );

		// Ensure we have a filename.
		if ( empty( $filename ) ) {
			$filename = 'document_' . time();
		}

		return $filename . '.' . $extension;
	}

	/**
	 * Save file to uploads directory
	 *
	 * @param string $content   File content.
	 * @param string $filename  Filename.
	 * @param string $mime_type MIME type.
	 * @param array  $config    Action config.
	 * @return array|WP_Error File path info or error.
	 */
	private function save_file( $content, $filename, $mime_type, $config ) {
		$upload_dir = wp_upload_dir();
		$dir_path   = $upload_dir['basedir'] . '/super-forms-files/';

		// Ensure directory exists.
		if ( ! wp_mkdir_p( $dir_path ) ) {
			return new WP_Error( 'directory_error', __( 'Could not create file directory', 'super-forms' ) );
		}

		// Add .htaccess protection.
		$htaccess_path = $dir_path . '.htaccess';
		if ( ! file_exists( $htaccess_path ) ) {
			file_put_contents( $htaccess_path, "Options -Indexes\n" );
		}

		$file_path = $dir_path . $filename;
		$file_url  = $upload_dir['baseurl'] . '/super-forms-files/' . $filename;

		// Write file.
		$written = file_put_contents( $file_path, $content );

		if ( false === $written ) {
			return new WP_Error( 'write_error', __( 'Could not write file', 'super-forms' ) );
		}

		$result = [
			'path' => $file_path,
			'url'  => $file_url,
		];

		// Upload to media library if requested.
		if ( ! empty( $config['upload_to_media'] ) ) {
			$attachment_id = $this->upload_to_media_library( $file_path, $filename, $mime_type );
			if ( ! is_wp_error( $attachment_id ) ) {
				$result['attachment_id'] = $attachment_id;
				$result['url']           = wp_get_attachment_url( $attachment_id );
			}
		}

		return $result;
	}

	/**
	 * Upload file to WordPress Media Library
	 *
	 * @param string $file_path Full file path.
	 * @param string $filename  Filename.
	 * @param string $mime_type MIME type.
	 * @return int|WP_Error Attachment ID or error.
	 */
	private function upload_to_media_library( $file_path, $filename, $mime_type ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file_array = [
			'name'     => $filename,
			'tmp_name' => $file_path,
		];

		$attachment_id = media_handle_sideload( $file_array, 0, null, [
			'post_mime_type' => $mime_type,
		] );

		return $attachment_id;
	}

	/**
	 * Convert array to CSV line
	 *
	 * @param array  $data      Data array.
	 * @param string $delimiter Delimiter character.
	 * @return string CSV line.
	 */
	private function array_to_csv_line( $data, $delimiter = ',' ) {
		$handle = fopen( 'php://temp', 'r+' );
		fputcsv( $handle, $data, $delimiter );
		rewind( $handle );
		$line = stream_get_contents( $handle );
		fclose( $handle );
		return $line;
	}

	/**
	 * Get default PDF styles
	 *
	 * @return string CSS styles.
	 */
	private function get_default_pdf_styles() {
		return '
			body {
				font-family: "Helvetica Neue", Arial, sans-serif;
				font-size: 12px;
				line-height: 1.5;
				color: #333;
				margin: 20px;
			}
			h1, h2, h3 { color: #222; margin-bottom: 10px; }
			table { width: 100%; border-collapse: collapse; margin: 15px 0; }
			th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
			th { background-color: #f5f5f5; font-weight: bold; }
			.metadata { font-size: 10px; color: #666; margin-top: 20px; }
		';
	}

	/**
	 * Supports async execution
	 *
	 * @return bool
	 */
	public function supports_async() {
		return true;
	}

	/**
	 * Get execution mode - sync for immediate file delivery
	 *
	 * @return string
	 */
	public function get_execution_mode() {
		return 'sync';
	}
}
