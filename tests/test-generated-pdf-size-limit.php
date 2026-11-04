<?php

require_once __DIR__ . '/test-security-upload-00-base.php';
require_once __DIR__ . '/generated-pdf-signature-fixture.php';

/**
 * The server-side counterpart of the generated PDF: whatever the browser built and
 * posted back as a PDF data URI, materialize_generated_pdf() has to accept, store and
 * hand on as a normal file.
 *
 * The size limit exists so a forged or runaway payload cannot fill the disk. It must
 * measure the plugin's own output by what the site allows, not by the 5 MB default of
 * the generic file-upload element: a form with Signature elements embeds every
 * signature and page break into the PDF, so an ordinary membership form can exceed it
 * while the site's PHP upload cap still has room. When that happens the submission is
 * refused with "Invalid file upload rejected." - reported to the visitor as a failed
 * upload on a form that has no File Upload element at all.
 *
 * These are the assertions the suite was missing: the existing generated-PDF tests use
 * a 68 byte PDF and either an explicitly tiny limit or no limit at all, so nothing
 * exercised a real-world PDF against the default limit.
 */
class Test_Super_Forms_Generated_Pdf_Size_Limit extends Super_Forms_Upload_Security_Test_Case {

    private function pdf_settings( $overrides=array() ) {
        return array(
            '_pdf' => array_merge(
                array(
                    'generate' => 'true',
                    'filename' => 'form.pdf',
                    'emailLabel' => esc_html__( 'PDF file', 'super-forms' ) . ':',
                ),
                $overrides
            ),
            'send' => 'no',
            'confirm' => 'no',
            'save_contact_entry' => 'no',
        );
    }

    private function signature_submission( $pdf_bytes ) {
        $data = super_forms_test_signature_values();
        return array_merge(
            $data,
            super_forms_test_generated_pdf_carrier( 'data:application/pdf;base64,' . base64_encode( $pdf_bytes ) )
        );
    }

    private function materialize( $pdf_bytes, $settings ) {
        $form_id = $this->create_form( 'publish', super_forms_test_signature_elements(), $settings );
        $data = $this->signature_submission( $pdf_bytes );
        $this->set_request( $form_id, $data );
        return $this->invoke_ajax_private(
            'materialize_generated_pdf',
            array( $data, $form_id, $settings )
        );
    }

    private function track_materialized( $materialized ) {
        foreach( $materialized['owned_files'] as $owned ) {
            $this->track_owned_cleanup( $owned );
        }
        foreach( $materialized['data']['_generated_pdf_file']['files'] as $file ) {
            if( !empty( $file['attachment'] ) ) {
                $this->attachment_ids[] = absint( $file['attachment'] );
            }
        }
        return $materialized['data']['_generated_pdf_file']['files'][0];
    }

    /**
     * A three-Signature membership form producing a PDF above the 5 MB upload default,
     * on a site whose PHP accepts 20 MB. This is the submission that today dies with
     * "Invalid file upload rejected." although every byte of the PDF came from the
     * plugin's own generator.
     */
    public function test_default_size_limit_accepts_the_plugins_own_pdf_with_three_signatures() {
        // A normal WordPress host: PHP upload_max_filesize/post_max_size allow 20 MB, which
        // is what wp_max_upload_size() reports and upload_size_limit filters.
        $this->add_upload_filter( 'upload_size_limit', static function() {
            return 20000000;
        } );

        $pdf_bytes = super_forms_test_build_signature_pdf( 8215751 );
        $this->assertSame( 8215751, strlen( $pdf_bytes ) );

        $settings = $this->pdf_settings();
        $materialized = $this->materialize( $pdf_bytes, $settings );
        if( is_wp_error( $materialized ) ) {
            $this->fail(
                'A generated PDF of ' . number_format( strlen( $pdf_bytes ) )
                . ' bytes, built by the plugin itself for a form with three Signature elements,'
                . ' was refused by the upload validator: "' . $materialized->get_error_message() . '".'
            );
        }

        $file = $this->track_materialized( $materialized );
        $this->assertArrayNotHasKey( 'datauristring', $file, 'The raw data URI must not survive materialization.' );
        $this->assertSame( 8215751, $file['size'] );
        $this->assertSame( 'application/pdf', $file['type'] );
        $stored = !empty( $file['path'] ) ? $file['path'] : get_attached_file( $file['attachment'] );
        $this->assertNotFalse( $stored );
        $this->assertSame( 8215751, filesize( $stored ), 'The stored PDF must keep every byte of the signed form.' );
    }

    /**
     * The limit is a ceiling, not decoration: a payload above what the site itself
     * accepts is still refused, with no file, receipt or option left behind.
     */
    public function test_default_size_limit_still_rejects_a_generated_pdf_above_the_site_upload_cap() {
        $this->add_upload_filter( 'upload_size_limit', static function() {
            return 20000000;
        } );

        $pdf_bytes = super_forms_test_build_signature_pdf( 21000000, 7, 3 );
        $settings = $this->pdf_settings();
        $result = $this->materialize( $pdf_bytes, $settings );

        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertSame( 'invalid_generated_pdf', $result->get_error_code() );
        $this->assertSame( 'Invalid file upload rejected.', $result->get_error_message() );
    }

    /**
     * An administrator who sets the PDF file size keeps that setting: the fix for the
     * false refusal must not turn the check off.
     */
    public function test_explicit_pdf_filesize_setting_is_still_enforced() {
        $pdf_bytes = super_forms_test_build_signature_pdf( 1500000, 7, 3 );
        $settings = $this->pdf_settings( array( 'filesize' => '1' ) );
        $result = $this->materialize( $pdf_bytes, $settings );

        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertSame( 'Invalid file upload rejected.', $result->get_error_message() );
    }
}
