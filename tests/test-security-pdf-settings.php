<?php
/**
 * Regression coverage for PDF Generator default-settings resolution.
 *
 * Guards the "cyrillicText" legacy-default logic in
 * SUPER_PDF_Generator::get_default_pdf_settings(): a saved value on an
 * existing form must be respected, a missing value on an existing form must
 * fall back to the legacy 'true' default, and a brand new form (no builder
 * "id" in the request) must default to 'false'.
 *
 * @package Super_Forms\Tests
 */

class Test_Super_Forms_Pdf_Settings extends WP_UnitTestCase {

    private $original_get_id_exists;
    private $original_get_id_value;

    public function set_up() {
        parent::set_up();

        if( !class_exists( 'SUPER_PDF_Generator' ) ) {
            require_once( SUPER_PLUGIN_DIR . '/includes/extensions/pdf-generator/pdf-generator.php' );
        }

        $this->original_get_id_exists = array_key_exists( 'id', $_GET );
        $this->original_get_id_value = $this->original_get_id_exists ? $_GET['id'] : null;
    }

    public function tear_down() {
        if( $this->original_get_id_exists ) {
            $_GET['id'] = $this->original_get_id_value;
        } else {
            unset( $_GET['id'] );
        }

        parent::tear_down();
    }

    public function test_existing_form_keeps_explicitly_saved_cyrillic_value() {
        $_GET['id'] = '123';

        $resolved = SUPER_PDF_Generator::get_default_pdf_settings( array( 'cyrillicText' => 'true' ) );
        $this->assertSame( 'true', $resolved['cyrillicText'] );

        $resolved = SUPER_PDF_Generator::get_default_pdf_settings( array( 'cyrillicText' => 'false' ) );
        $this->assertSame( 'false', $resolved['cyrillicText'] );
    }

    public function test_existing_form_without_cyrillic_value_uses_legacy_true_default() {
        $_GET['id'] = '123';

        $resolved = SUPER_PDF_Generator::get_default_pdf_settings( array() );
        $this->assertSame( 'true', $resolved['cyrillicText'] );
    }

    public function test_new_form_without_cyrillic_value_defaults_to_false() {
        unset( $_GET['id'] );

        $resolved = SUPER_PDF_Generator::get_default_pdf_settings( array() );
        $this->assertSame( 'false', $resolved['cyrillicText'] );
    }
}
