<?php
/**
 * PDF Generator font data must be attached to the `super-common` script so it
 * runs after the localized `super_common_i18n` object.
 */

if ( ! class_exists( 'Super_Forms_Upload_Security_Test_Case' ) ) {
	require_once __DIR__ . '/test-security-upload-00-base.php';
}

class Test_PDF_Font_Inline_Script extends Super_Forms_Upload_Security_Test_Case {

	public function set_up() {
		parent::set_up();
		$GLOBALS['wp_scripts'] = new WP_Scripts();
	}

	public function tear_down() {
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		parent::tear_down();
	}

	private function render_pdf_form() {
		if ( ! class_exists( 'SUPER_PDF_Generator' ) ) {
			require_once dirname( __DIR__ ) . '/includes/extensions/pdf-generator/pdf-generator.php';
		}
		$pdf = SUPER_PDF_Generator::get_default_pdf_settings( array() );
		$pdf['generate']      = 'true';
		$pdf['textRendering'] = 'true';
		$pdf['cyrillicText']  = 'true';
		$pdf['arabicText']    = 'false';
		$form = $this->create_form( 'publish', array(), array( '_pdf' => $pdf ) );
		SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form ) );
	}

	public function test_fonts_attach_to_super_common_after_block() {
		$this->render_pdf_form();

		$after = wp_scripts()->get_data( 'super-common', 'after' );
		$this->assertIsArray( $after );
		$this->assertStringContainsString( 'super_common_i18n.fonts', implode( "\n", $after ) );
	}

	public function test_fonts_print_after_localized_var() {
		$this->render_pdf_form();

		ob_start();
		wp_print_scripts( array( 'super-common' ) );
		$out = ob_get_clean();

		$var_pos   = strpos( $out, 'var super_common_i18n' );
		$fonts_pos = strpos( $out, 'super_common_i18n.fonts' );
		$after_pos = strpos( $out, 'id="super-common-js-after"' );
		$this->assertNotFalse( $var_pos );
		$this->assertNotFalse( $fonts_pos );
		$this->assertNotFalse( $after_pos );
		$this->assertLessThan( $fonts_pos, $var_pos );
		$this->assertLessThan( $fonts_pos, $after_pos );
	}

	public function test_fonts_fall_back_to_footer_when_super_common_already_printed() {
		wp_scripts()->done[] = 'super-common';
		$this->render_pdf_form();

		remove_action( 'wp_footer', 'the_block_template_skip_link' );
		ob_start();
		do_action( 'wp_footer' );
		$out = ob_get_clean();

		$this->assertStringContainsString( 'super_common_i18n.fonts', $out );
	}
}
