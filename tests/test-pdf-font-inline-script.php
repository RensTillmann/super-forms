<?php
require_once __DIR__ . '/test-security-upload-00-base.php';

/** The PDF font script must run after the localized `super_common_i18n`, even when optimizers reorder scripts. */
class Test_Pdf_Font_Inline_Script extends Super_Forms_Upload_Security_Test_Case {
    private $created_font_files = array();
    private $original_wp_scripts;

    public function set_up() {
        parent::set_up();
        $this->original_wp_scripts = isset($GLOBALS['wp_scripts']) ? $GLOBALS['wp_scripts'] : null;
        $GLOBALS['wp_scripts'] = new WP_Scripts();
        $font_dir = SUPER_PLUGIN_DIR . '/includes/extensions/pdf-generator/fonts/';
        if( !is_dir($font_dir) ) wp_mkdir_p($font_dir);
        // Existing non-empty files are reused so nothing is downloaded; only files this test creates are removed.
        foreach( array('json' => '{"regular":"x"}', 'woff' => 'x', 'woff2' => 'x') as $ext => $content ) {
            $file = $font_dir . 'cyrillic.' . $ext;
            if( file_exists($file) && filesize($file)>0 ) continue;
            file_put_contents($file, $content);
            $this->created_font_files[] = $file;
        }
    }

    public function tear_down() {
        foreach( $this->created_font_files as $file ) {
            if( file_exists($file) ) unlink($file);
        }
        $this->created_font_files = array();
        $GLOBALS['wp_scripts'] = $this->original_wp_scripts;
        parent::tear_down();
    }

    private function render_cyrillic_pdf_form() {
        // Build `_pdf` the way the plugin does, so super_form_func finds every key it reads.
        $pdf = SUPER_PDF_Generator::get_default_pdf_settings( array(
            'generate' => 'true',
            'textRendering' => 'true',
            'language' => 'cyrillic',
        ) );
        $form_id = $this->create_form( 'publish', array(), array( '_pdf' => $pdf ) );
        return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
    }

    public function test_fonts_attach_to_super_common_after_block() {
        $this->render_cyrillic_pdf_form();
        $after = wp_scripts()->get_data( 'super-common', 'after' );
        $this->assertIsArray( $after );
        $this->assertStringContainsString( 'super_common_i18n.fonts', implode( "\n", $after ) );
        $this->assertStringContainsString( 'pdf-generator', implode( "\n", $after ) );
    }

    public function test_fonts_print_after_localized_var() {
        $this->render_cyrillic_pdf_form();
        ob_start();
        wp_print_scripts( array( 'super-common' ) );
        $out = ob_get_clean();
        $var_pos = strpos( $out, 'var super_common_i18n' );
        $fonts_pos = strpos( $out, 'super_common_i18n.fonts' );
        $after_pos = strpos( $out, 'id="super-common-js-after"' );
        $this->assertNotFalse( $var_pos, $out );
        $this->assertNotFalse( $fonts_pos, $out );
        $this->assertNotFalse( $after_pos, $out );
        $this->assertLessThan( $fonts_pos, $var_pos );
        $this->assertLessThan( $fonts_pos, $after_pos );
    }

    public function test_fonts_fall_back_to_footer_when_super_common_already_printed() {
        wp_scripts()->done[] = 'super-common';
        $this->render_cyrillic_pdf_form();
        // Core's deprecated skip-link footer callback is unrelated; the test hooks are restored after each test.
        remove_action( 'wp_footer', 'the_block_template_skip_link' );
        ob_start();
        do_action( 'wp_footer' );
        $out = ob_get_clean();
        $this->assertStringContainsString( 'super_common_i18n.fonts', $out );
        $this->assertStringContainsString( "font-family:'SF-Unicode'", $out );
    }
}
