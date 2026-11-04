<?php
/**
 * generated_pdf_size_limit() bounds the PDF the plugin renders in the browser and
 * receives back as a data URI. It must not inherit the 5 MB default of a file upload
 * field: a long form renders a PDF well above 5 MB (a 7-page membership form produces
 * about 8.2 MB), and the submission was then rejected with "Invalid file upload
 * rejected." for every visitor after the strict PDF carrier checks shipped.
 *
 * @package Super_Forms_Tests
 */

class Test_Super_Forms_Generated_Pdf_Size_Limit extends WP_UnitTestCase {
    public static function set_up_before_class() {
        parent::set_up_before_class();
        if( !class_exists( 'SUPER_Ajax' ) ) {
            require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
        }
    }

    public function tear_down() {
        remove_all_filters( 'upload_size_limit' );
        parent::tear_down();
    }

    /** Report a roomy site upload limit so the assertion does not depend on the php.ini of the test runner. */
    private function site_allows( $bytes ) {
        add_filter( 'upload_size_limit', function () use ( $bytes ) { return $bytes; } );
    }

    private function limit( $pdf_settings ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'generated_pdf_size_limit' );
        $method->setAccessible( true );
        return $method->invoke( null, array( '_pdf' => $pdf_settings ) );
    }

    public function test_unset_filesize_does_not_use_the_upload_field_default() {
        $this->site_allows( 64 * 1024 * 1024 );
        $limit = $this->limit( array( 'generate' => 'true', 'filename' => 'form.pdf' ) );
        $this->assertIsArray( $limit );
        $this->assertSame( 10000000, $limit['bytes'], 'the 10 MB PDF bound applies, not the 5 MB upload-field default' );
    }

    public function test_an_eight_megabyte_generated_pdf_is_within_the_limit() {
        $this->site_allows( 64 * 1024 * 1024 );
        $limit = $this->limit( array( 'generate' => 'true' ) );
        $this->assertGreaterThan( 8215768, $limit['bytes'] );
    }

    public function test_a_configured_pdf_size_still_lowers_the_limit() {
        $this->site_allows( 64 * 1024 * 1024 );
        $limit = $this->limit( array( 'generate' => 'true', 'filesize' => '2' ) );
        $this->assertSame( 2000000, $limit['bytes'] );
    }

    public function test_a_configured_pdf_size_never_raises_the_ten_megabyte_bound() {
        $this->site_allows( 64 * 1024 * 1024 );
        $limit = $this->limit( array( 'generate' => 'true', 'filesize' => '50' ) );
        $this->assertSame( 10000000, $limit['bytes'] );
    }

    public function test_the_site_upload_limit_still_bounds_the_pdf() {
        $this->site_allows( 4 * 1024 * 1024 );
        $limit = $this->limit( array( 'generate' => 'true' ) );
        $this->assertSame( 4194304, $limit['bytes'] );
    }

    public function test_an_invalid_configured_size_is_refused() {
        $this->site_allows( 64 * 1024 * 1024 );
        $this->assertFalse( $this->limit( array( 'generate' => 'true', 'filesize' => '' ) ) );
        $this->assertFalse( $this->limit( array( 'generate' => 'true', 'filesize' => '0' ) ) );
    }
}
