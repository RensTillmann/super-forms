# WordPress Integration Guide - PDF API Service

**Document Version:** 1.0
**Date:** 2025-12-16
**Target:** Super Forms Plugin v6.8.0+

---

## Overview

This document details the PHP changes required to integrate the PDF API service (`api.super-forms.com/v1/pdf/generate`) into the Super Forms WordPress plugin.

### Files to Modify

| File | Changes |
|------|---------|
| `src/includes/automations/actions/class-action-generate-file.php` | Add API integration methods |
| `src/includes/class-settings.php` | Add PDF API settings fields |

### New Files to Create

None required - all changes are modifications to existing files.

---

## 1. Generate File Action Modifications

### File: `src/includes/automations/actions/class-action-generate-file.php`

#### 1.1 Add PDF API Settings to Schema

Add new settings fields for PDF API configuration in `get_settings_schema()`:

```php
/**
 * Get settings schema for UI
 *
 * @return array
 */
public function get_settings_schema() {
    return [
        // ... existing settings ...

        // ADD after 'orientation' setting:
        [
            'name'        => 'pdf_generation_method',
            'label'       => __( 'PDF Generation Method', 'super-forms' ),
            'type'        => 'select',
            'required'    => false,
            'default'     => 'auto',
            'options'     => [
                'auto'      => __( 'Automatic (API with fallback)', 'super-forms' ),
                'api'       => __( 'API Only (vector PDF)', 'super-forms' ),
                'client'    => __( 'Client-Side Only (legacy)', 'super-forms' ),
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

        // ... existing settings continue ...
    ];
}
```

#### 1.2 Modify generate_pdf() Method

Replace the existing `generate_pdf()` method with this enhanced version:

```php
/**
 * Generate PDF (hybrid: API primary, client/TCPDF fallback)
 *
 * Priority order:
 * 1. PDF API Service (if enabled and licensed)
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
            return $api_result;
        }

        // If method is 'api', don't fallback.
        if ( 'api' === $method ) {
            return $api_result;
        }

        // Log and continue to fallback.
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
        __( 'PDF generation failed. Please check your PDF API license key in Settings, or install TCPDF/DOMPDF for server-side fallback.', 'super-forms' )
    );
}
```

#### 1.3 Add PDF API Integration Methods

Add these new methods to the class:

```php
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
            'User-Agent'   => 'Super-Forms/' . SUPER_VERSION,
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
            'LICENSE_NOT_FOUND'    => 'license_not_found',
            'LICENSE_EXPIRED'      => 'license_expired',
            'LICENSE_SUSPENDED'    => 'license_suspended',
            'RATE_LIMIT_EXCEEDED'  => 'rate_limit_exceeded',
            'RENDER_TIMEOUT'       => 'render_timeout',
            'CHROME_CRASHED'       => 'chrome_crashed',
            'PAYLOAD_TOO_LARGE'    => 'payload_too_large',
            'SERVICE_BUSY'         => 'service_busy',
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

    return trailingslashit( $api_base ) . 'pdf/generate';
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
        'format'      => strtoupper( $config['page_size'] ?? 'A4' ),
        'orientation' => $config['orientation'] ?? 'portrait',
        'margin'      => [
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
```

---

## 2. Global Settings Configuration

### File: `src/includes/class-settings.php`

Add the following settings fields to the appropriate section (likely "General" or create a new "PDF Settings" section):

> **Note:** License validation is handled server-side by the PDF API based on your site's domain. No license key configuration is needed in WordPress - the API looks up your domain in its database automatically.

```php
/**
 * PDF API Settings Section
 */
[
    'name'        => 'pdf_api_section',
    'label'       => __( 'PDF API Service', 'super-forms' ),
    'type'        => 'section',
    'description' => __( 'The PDF API generates high-quality vector PDFs with selectable text. License is validated automatically by your site domain.', 'super-forms' ),
],
[
    'name'        => 'pdf_api_enabled',
    'label'       => __( 'Enable PDF API', 'super-forms' ),
    'type'        => 'toggle',
    'required'    => false,
    'default'     => true,
    'description' => __( 'Use the Super Forms PDF API for server-side PDF generation. Your site must have an active PDF license.', 'super-forms' ),
],
[
    'name'        => 'pdf_api_fallback',
    'label'       => __( 'Fallback Behavior', 'super-forms' ),
    'type'        => 'select',
    'required'    => false,
    'default'     => 'auto',
    'options'     => [
        'auto'          => __( 'Automatic (fall back on network errors only)', 'super-forms' ),
        'always'        => __( 'Always fall back if API fails', 'super-forms' ),
        'never'         => __( 'Never fall back (fail if API unavailable)', 'super-forms' ),
    ],
    'description' => __( 'How to handle API failures. License errors never fall back to prevent bypassing licensing.', 'super-forms' ),
    'show_if'     => [ 'pdf_api_enabled' => true ],
],
```

---

## 3. Constants for Development/Testing

Add to `wp-config.php` for local development or testing:

```php
// Use development API endpoint (for testing against staging/dev API).
define( 'SUPER_PDF_API_ENDPOINT', 'https://api.dev.super-forms.com/v1/pdf/generate' );

// Override the base API URL (affects all API calls).
define( 'SUPER_API_ENDPOINT', 'https://api.super-forms.com/v1' );
```

> **Note:** No license key constant is needed. The API validates your license by looking up your site's domain in its database automatically.

---

## 4. Error Handling Reference

### Error Codes and User Messages

| Error Code | HTTP | User Message | Action |
|------------|------|--------------|--------|
| `license_not_found` | 403 | "No active license found for this domain" | Don't fallback, show licensing info |
| `license_expired` | 403 | "License has expired" | Don't fallback, show renewal |
| `license_suspended` | 403 | "License has been suspended" | Don't fallback, contact support |
| `rate_limit_exceeded` | 429 | "Rate limit exceeded, try in X seconds" | Schedule retry |
| `api_connection_error` | - | "Could not connect to PDF API" | Fallback to client/TCPDF |
| `render_timeout` | 500 | "PDF generation timed out" | Fallback |
| `chrome_crashed` | 500 | "PDF generation failed" | Fallback |
| `payload_too_large` | 413 | "HTML content too large" | Show size limit info |
| `service_busy` | 503 | "Service busy, please retry" | Fallback or retry |

> **Note:** License validation is done server-side by domain lookup. WordPress sends only `site_url` - no license key configuration is needed in WordPress.

### Admin Notices

Consider adding admin notices for license status (optional, requires API health check endpoint):

```php
/**
 * Show admin notice for PDF API license status.
 *
 * This is optional - only useful if you implement a license status check endpoint.
 */
add_action( 'admin_notices', function() {
    // Only show on Super Forms pages.
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'super' ) === false ) {
        return;
    }

    // Check if we have a cached license error from a recent PDF generation.
    $last_error = get_transient( 'super_pdf_api_last_error' );

    if ( $last_error && in_array( $last_error, [ 'license_not_found', 'license_expired', 'license_suspended' ], true ) ) {
        echo '<div class="notice notice-warning"><p>';
        printf(
            /* translators: %s: domain name */
            esc_html__( 'Super Forms PDF API: No active license found for domain "%s". Please ensure your domain is registered with an active PDF license.', 'super-forms' ),
            esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) )
        );
        echo '</p></div>';
    }
} );
```

---

## 5. Action Scheduler Integration (Optional)

For handling rate-limited requests in async context, integrate with Action Scheduler:

```php
/**
 * Retry PDF generation after rate limit.
 *
 * @param int   $automation_id Automation ID.
 * @param int   $action_id     Action ID.
 * @param array $context       Event context.
 * @param array $config        Action config.
 */
add_action( 'super_retry_pdf_generation', function( $automation_id, $action_id, $context, $config ) {
    $action = new SUPER_Action_Generate_File();
    $result = $action->execute( $context, $config );

    // Log result.
    SUPER_Automation_DAL::log_action_result( $automation_id, $action_id, $result );
}, 10, 4 );

// In generate_pdf_via_api(), when rate limited:
if ( 'rate_limit_exceeded' === $mapped_code && ! empty( $body['retry_after'] ) && $this->supports_async() ) {
    // Schedule retry.
    as_schedule_single_action(
        time() + (int) $body['retry_after'],
        'super_retry_pdf_generation',
        [
            'automation_id' => $context['automation_id'] ?? 0,
            'action_id'     => $config['_action_id'] ?? 0,
            'context'       => $context,
            'config'        => $config,
        ],
        'super-forms-pdf'
    );

    return new WP_Error(
        'rate_limit_scheduled',
        sprintf(
            /* translators: %d: seconds */
            __( 'Rate limit exceeded. PDF generation scheduled for retry in %d seconds.', 'super-forms' ),
            (int) $body['retry_after']
        )
    );
}
```

---

## 6. Filters and Hooks Reference

### Available Filters

```php
/**
 * Filter whether to use PDF API service.
 *
 * @param bool  $use_api Whether to use API.
 * @param array $config  Action configuration.
 */
apply_filters( 'super_use_pdf_api_service', $use_api, $config );

/**
 * Filter PDF API options before request.
 *
 * @param array $options API options.
 * @param array $config  Action configuration.
 * @param array $context Event context.
 */
apply_filters( 'super_pdf_api_options', $options, $config, $context );

/**
 * Filter PDF API endpoint URL.
 *
 * @param string $endpoint API endpoint URL.
 */
apply_filters( 'super_pdf_api_endpoint', $endpoint );
```

### Usage Examples

```php
// Force API-only mode for specific forms.
add_filter( 'super_use_pdf_api_service', function( $use_api, $config ) {
    // Always use API for invoice forms.
    if ( isset( $config['_form_id'] ) && in_array( $config['_form_id'], [ 123, 456 ] ) ) {
        return true;
    }
    return $use_api;
}, 10, 2 );

// Add custom watermark to all PDFs.
add_filter( 'super_pdf_api_options', function( $options, $config, $context ) {
    // Add watermark text to footer.
    if ( ! isset( $options['footerTemplate'] ) ) {
        $options['displayHeaderFooter'] = true;
        $options['footerTemplate'] = '<div style="font-size:8px;text-align:center;color:#ccc;">CONFIDENTIAL</div>';
    }
    return $options;
}, 10, 3 );
```

---

## 7. Migration Guide

### For Existing Forms Using PDF Generation

1. **No changes required for most users** - The system defaults to `auto` mode which uses API when available and falls back gracefully.

2. **To opt-in to API-only mode:**
   - Edit the Generate File action in the automation
   - Set "PDF Generation Method" to "API Only (vector PDF)"

3. **To keep client-side only (legacy):**
   - Set "PDF Generation Method" to "Client-Side Only (legacy)"

### Breaking Changes

None. All existing PDF configurations continue to work with client-side generation until API is enabled.

### Deprecation Notice

In a future version (v7.0.0), client-side PDF generation may be deprecated in favor of API-only. Forms using client-side will continue to work but will show a notice encouraging migration.

---

## 8. Security Considerations

1. **License Validation:**
   - License is validated server-side by the PDF API
   - API looks up the requesting domain in MongoDB
   - No license keys stored or transmitted from WordPress
   - Site URL (domain) is the only identifying information sent

2. **HTML Content:**
   - All user-generated content goes through `wp_strip_all_tags()` for CSS
   - Field values sanitized via `esc_html()` in templates
   - API enforces 5MB limit on HTML content

3. **Network Security:**
   - API calls use HTTPS only
   - WordPress HTTP API handles SSL verification
   - Timeout set to 60 seconds to prevent hanging

---

## 9. Debugging

### Enable Debug Logging

```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// Logs will appear in wp-content/debug.log
// Look for: [Super Forms PDF]
```

### Manual API Test

```php
// Add to functions.php temporarily for testing.
add_action( 'admin_init', function() {
    if ( ! isset( $_GET['test_pdf_api'] ) ) {
        return;
    }

    $action = new SUPER_Action_Generate_File();

    // Use reflection to call private method.
    $method = new ReflectionMethod( $action, 'generate_pdf_via_api' );
    $method->setAccessible( true );

    $result = $method->invoke(
        $action,
        '<html><body><h1>Test PDF</h1><p>Generated at ' . current_time( 'mysql' ) . '</p></body></html>',
        'test_' . time() . '.pdf',
        [ 'page_size' => 'a4', 'orientation' => 'portrait' ],
        [ 'form_id' => 1 ]
    );

    echo '<pre>';
    print_r( $result );
    echo '</pre>';
    exit;
} );
// Visit: /wp-admin/?test_pdf_api=1
```
