<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if(!class_exists('SUPER_PDF_Generator')) :
    final class SUPER_PDF_Generator {
        public $add_on_slug = 'pdf';
        protected static $_instance = null;
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct(){
            $this->init_hooks();
            do_action('SUPER_PDF_Generator_loaded');
        }
        private function init_hooks() {
            add_filter( 'super_shortcodes_end_filter', array( $this, 'pdf_element_settings' ), 10, 2 );
            add_filter( 'super_form_js_filter', array( $this, 'pdf_form_js' ), 10, 2);
            if ( SUPER_Forms()->is_request( 'admin' ) ) {
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 10, 1 );
                add_action( 'super_create_form_pdf_tab', array( $this, 'add_tab_content' ) );
            }
            add_filter( 'super_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
            add_action( 'super_after_enqueue_element_scripts_action', array( $this, 'enqueue_element_scripts' ), 10 );

        }
        public static function enqueue_scripts($scripts){
            $path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'includes/extensions/pdf-generator/';
            $scripts['super-html-canvas'] = array(
                'src'     => $path . 'super-html-canvas.min.js',
                'deps'    => array(),
                'version' => SUPER_VERSION,
                'footer'  => false,
                'screen'  => array(
                    'super-forms_page_super_create_form',
                ),
                'method'  => 'enqueue',
            );
            $scripts['super-pdf-gen'] = array(
                'src'     => $path . 'super-pdf-gen.min.js',
                'deps'    => array( 'super-html-canvas' ),
                'version' => SUPER_VERSION,
                'footer'  => false,
                'screen'  => array(
                    'super-forms_page_super_create_form',
                ),
                'method'  => 'enqueue',
            );
            return $scripts;
        }
        public static function enqueue_element_scripts($atts){
            $settings = $atts['settings'];
            // @since 4.9.500 - PDF Generation
            if( !empty($settings['_pdf']) && $settings['_pdf']['generate']=='true' ) {
                wp_enqueue_script( 'super-html-canvas', SUPER_PLUGIN_FILE.'includes/extensions/pdf-generator/super-html-canvas.min.js', array(), SUPER_VERSION, false );
                wp_enqueue_script( 'super-pdf-gen', SUPER_PLUGIN_FILE.'includes/extensions/pdf-generator/super-pdf-gen.min.js', array( 'super-html-canvas' ), SUPER_VERSION, false );
            }
        }
        public static function add_tab($tabs){
            $tabs['pdf'] = esc_html__( 'PDF', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            $slug = SUPER_PDF_Generator()->add_on_slug;
            $settings = (isset($atts['settings']['_'.$slug]) ? $atts['settings']['_'.$slug] : array());
            $s = self::get_default_pdf_settings($settings);
            
            // Hiding/Showing elements in PDF
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . esc_html__( 'By default all elements that are visible in your form will be printed onto the PDF unless defined otherwise under "PDF Settings" TAB when editing the element. Each element can be included or excluded specifically from the PDF or from the form. You can define this on a per element basis (including columns) by editing the element and navigating to "PDF Settings" section. Here you can define if the element should be only visible in the PDF or Form, or both.', 'super-forms' );
            echo '</div>';
            // Header/Footer usage notice
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . esc_html__( 'To use a header and footer for your PDF, you can edit any element (including columns) and navigate to "PDF Settings" section. Here you can define if the element should be used as a header or footer. Note that you can only use one header or footer element. To add multiple elements you should use a column instead.', 'super-forms' );
            echo '</div>';
            // {tags} usage notice
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . esc_html__( '{pdf_page} and {pdf_total_pages} tags can be used inside a HTML element to be used in your header/footer.', 'super-forms' );
            echo '</div>';
            
            // Enable PDF
            echo '<div class="sfui-setting">';
                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    echo '<input type="checkbox" name="generate" value="true"' . ($s['generate']==='true' ? ' checked="checked"' : '') . ' />';
                    echo '<span class="sfui-title">' . esc_html__( 'Enable Form to PDF generation', 'super-forms' ) . '</span>';
                echo '</label>';
                echo '<div class="sfui-sub-settings" data-f="generate;true">';
                    // Debug mode
                    echo '<div class="sfui-setting">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="checkbox" name="debug" value="true"' . ($s['debug']==='true' ? ' checked="checked"' : '') . ' />';
                            echo '<span class="sfui-title">' . esc_html__( 'Enable debug mode (this will not submit the form, but directly download the generated PDF, only enable this when developing your form)', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';
                    // PDF file name
                    echo '<div class="sfui-setting sfui-inline">';
                        echo '<span class="sfui-title">' . esc_html__( 'PDF filename', 'super-forms' ) . ':</span>';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="text" name="filename" value="' . esc_attr($s['filename']) . '" />';
                            echo '<span class="sfui-label">' . esc_html__( 'use {tags} if needed', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';
                    // Email label
                    echo '<div class="sfui-setting sfui-inline">';
                        echo '<span class="sfui-title">' . esc_html__( 'Email label', 'super-forms' ) . ':</span>';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="text" name="emailLabel" value="' . esc_attr($s['emailLabel']) . '" />';
                            echo '<span class="sfui-label">' . esc_html__( 'use {tags} if needed', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';
                    // Attach generated PDF to admin e-mail
                    echo '<div class="sfui-setting">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="checkbox" name="adminEmail" value="true"' . ($s['adminEmail']==='true' ? ' checked="checked"' : '') . ' />';
                            echo '<span class="sfui-title">' . esc_html__( 'Attach generated PDF to admin e-mail', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';
                    // Attach generated PDF to confirmation e-mail
                    echo '<div class="sfui-setting">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="checkbox" name="confirmationEmail" value="true"' . ($s['confirmationEmail']==='true' ? ' checked="checked"' : '') . ' />';
                            echo '<span class="sfui-title">' . esc_html__( 'Attach generated PDF to confirmation e-mail', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';
                    // Do not save PDF in Contact Entry
                    echo '<div class="sfui-setting">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="checkbox" name="excludeEntry" value="true"' . ($s['excludeEntry']==='true' ? ' checked="checked"' : '') . ' />';
                            echo '<span class="sfui-title">' . esc_html__( 'Do not save PDF in Contact Entry', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';
                    
                    // Show download button to the user after PDF was generated
                    echo '<div class="sfui-setting">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="checkbox" name="downloadBtn" value="true"' . ($s['downloadBtn']==='true' ? ' checked="checked"' : '') . ' />';
                            echo '<span class="sfui-title">' . esc_html__( 'Show download button to the user after PDF was generated', 'super-forms' ) . '</span>';
                        echo '</label>';
                        echo '<div class="sfui-sub-settings" data-f="downloadBtn;true">';
                            // Download button text
                            echo '<div class="sfui-setting sfui-inline">';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<span class="sfui-title">' . esc_html__( 'Download button text', 'super-forms' ) . ':</span>';
                                    echo '<input type="text" name="downloadBtnText" value="' . esc_attr($s['downloadBtnText']) . '" />';
                                echo '</label>';
                            echo '</div>';
                            // Generating text
                            echo '<div class="sfui-setting sfui-inline">';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<span class="sfui-title">' . esc_html__( 'Generating text', 'super-forms' ) . ':</span>';
                                    echo '<input type="text" name="generatingText" value="' . esc_attr($s['generatingText']) . '" />';
                                echo '</label>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    // Page orientation
                    echo '<form class="sfui-setting sfui-inline">';
                        echo '<span class="sfui-title">' . esc_html__( 'Page orientation', 'super-forms' ) . ':</span>';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="radio" name="orientation" value="portrait"' . ($s['orientation']==='portrait' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Portrait', 'super-forms' ) . '</span>';
                        echo '</label>';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="radio" name="orientation" value="landscape"' . ($s['orientation']==='landscape' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Landscape', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</form>';

                    // Unit
                    echo '<div class="sfui-setting sfui-inline">';
                        echo '<span class="sfui-title">' . esc_html__( 'Unit', 'super-forms' ) . ':</span>';
                        echo '<label>';
                            echo '<select name="unit" onChange="SUPER.ui.updateSettings(event, this)">';
                                echo '<option '.($s['unit']=='mm' ? ' selected="selected"' : '').' value="mm">mm ('.esc_html__('default').')</option>';
                                echo '<option '.($s['unit']=='pt' ? ' selected="selected"' : '').' value="pt">pt</option>';
                                echo '<option '.($s['unit']=='cm' ? ' selected="selected"' : '').' value="cm">cm</option>';
                                echo '<option '.($s['unit']=='in' ? ' selected="selected"' : '').' value="in">in</option>';
                                echo '<option '.($s['unit']=='px' ? ' selected="selected"' : '').' value="px">px</option>';
                            echo '</select>';
                        echo '</label>';
                    echo '</div>';
                    
                    // Page format
                    $formats = array();
                    $i = 0;
                    for($i=0; $i < 10; $i++){
                        $formats[] = 'a'.$i;
                    }
                    $i = 0;
                    for($i=0; $i < 10; $i++){
                        $formats[] = 'b'.$i;
                    }
                    $i = 0;
                    for($i=0; $i < 10; $i++){
                        $formats[] = 'c'.$i;
                    }
                    $formats = array_merge($formats, array('dl', 'letter', 'government-letter', 'legal', 'junior-legal', 'ledger', 'tabloid', 'credit-card'));
                    echo '<div class="sfui-setting sfui-inline">';
                        echo '<span class="sfui-title">' . esc_html__( 'Page format', 'super-forms' ) . ':</span>';
                        echo '<label>';
                            echo '<select name="format" onChange="SUPER.ui.updateSettings(event, this)">';
                            foreach($formats as $v){
                                echo '<option value="' . esc_attr($v) . '"'.($v==$s['format'] ? ' selected="selected"' : '') .'>' . ($v=='a4' ? $v . ' (' . esc_html__( 'default', 'super-forms' ) . ')' : $v) . '</option>';
                            }
                            echo '</select>';
                        echo '</label>';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<span class="sfui-title">' . esc_html__( 'Custom page format in units defined above e.g: 210,297', 'super-forms' ) . ':</span>';
                            echo '<input type="text" name="customFormat" value="' . esc_attr($s['customFormat']) . '" />';
                            echo '<span class="sfui-label">' . esc_html__( '(optional, leave blank for none)', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';

                    // Body margins (in units declared above)
                    echo '<div class="sfui-setting">';
                        echo '<span class="sfui-title">' . esc_html__( 'Body margins (in units declared above)', 'super-forms' ) . '</span>';
                        echo '<div class="sfui-setting sfui-inline">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'top', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.body.top" value="' . esc_attr($s['margins']['body']['top']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'right', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.body.right" value="' . esc_attr($s['margins']['body']['right']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'bottom', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.body.bottom" value="' . esc_attr($s['margins']['body']['bottom']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'left', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.body.left" value="' . esc_attr($s['margins']['body']['left']) . '" />';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                    // Header margins (in units declared above)
                    echo '<div class="sfui-setting">';
                        echo '<span class="sfui-title">' . esc_html__( 'Header margins (in units declared above)', 'super-forms' ) . '</span>';
                        echo '<span class="sfui-label">' . esc_html__( 'Note: if you wish to use a header make sure define one element in your form to act as the PDF header, you can do so under "PDF Settings" TAB when editing an element', 'super-forms' ) . '</span>';
                        echo '<div class="sfui-setting sfui-inline">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'top', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.header.top" value="' . esc_attr($s['margins']['header']['top']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'right', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.header.right" value="' . esc_attr($s['margins']['header']['right']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'bottom', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.header.bottom" value="' . esc_attr($s['margins']['header']['bottom']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'left', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.header.left" value="' . esc_attr($s['margins']['header']['left']) . '" />';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                    // Footer margins (in units declared above)
                    echo '<div class="sfui-setting">';
                        echo '<span class="sfui-title">' . esc_html__( 'Footer margins (in units declared above)', 'super-forms' ) . '</span>';
                        echo '<span class="sfui-label">' . esc_html__( 'Note: if you wish to use a footer make sure define one element in your form to act as the PDF footer, you can do so under "PDF Settings" TAB when editing an element', 'super-forms' ) . '</span>';
                        echo '<div class="sfui-setting sfui-inline">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'top', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.footer.top" value="' . esc_attr($s['margins']['footer']['top']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'right', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.footer.right" value="' . esc_attr($s['margins']['footer']['right']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'bottom', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.footer.bottom" value="' . esc_attr($s['margins']['footer']['bottom']) . '" />';
                            echo '</label>';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<span class="sfui-label">' . esc_html__( 'left', 'super-forms' ) . '</span>';
                                echo '<input type="number" name="margins.footer.left" value="' . esc_attr($s['margins']['footer']['left']) . '" />';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                    // PDF Text rendering
                    echo '<div class="sfui-setting sfui-inline">';
                        echo '<span class="sfui-title">' . esc_html__( 'PDF Text rendering', 'super-forms' ) . ':</span>';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="checkbox" name="textRendering" value="true"' . ($s['textRendering']==='true' ? ' checked="checked"' : '') . ' />';
                            echo '<span class="sfui-label">' . esc_html__( 'Enable (makes it possible to search for text inside the PDF)', 'super-forms' ) . '</span>';
                        echo '</label>';
                    echo '</div>';
                    // This allows to copy cyrillic text
                    echo '<div class="sfui-setting">';
                        echo '<div class="sfui-sub-settings" data-f="textRendering;true">';
                            // PDF Cyrillic text
                            echo '<div class="sfui-setting sfui-inline">';
                                echo '<span class="sfui-title">' . esc_html__( 'Cyrillic text', 'super-forms' ) . ':</span>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="checkbox" name="cyrillicText" value="true"' . ($s['cyrillicText']==='true' ? ' checked="checked"' : '') . ' />';
                                    echo '<span class="sfui-label">' . esc_html__( 'Enable (only enable this if your form uses cyrillic text)', 'super-forms' ) . '</span>';
                                echo '</label>';
                            echo '</div>';
                            // PDF Arabic text
                            echo '<div class="sfui-setting sfui-inline">';
                                echo '<span class="sfui-title">' . esc_html__( 'Arabic text', 'super-forms' ) . ':</span>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="checkbox" name="arabicText" value="true"' . ($s['arabicText']==='true' ? ' checked="checked"' : '') . ' />';
                                    echo '<span class="sfui-label">' . esc_html__( 'Enable (only enable this if your form uses arabic text)', 'super-forms' ) . '</span>';
                                echo '</label>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';
                    // PDF render scale
                    echo '<div class="sfui-setting sfui-inline">';
                        echo '<span class="sfui-title">' . esc_html__( 'PDF render scale', 'super-forms' ) . ':</span>';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<input type="number" name="renderScale" value="' . esc_attr($s['renderScale']) . '" />';
                            echo '<span class="sfui-label">' . esc_html__( 'recommended render scale is between 1 and 3 (the default scale is 2)', 'super-forms' ) . '</span>';
                        echo '</label>';
                        echo '<div class="sfui-notice sfui-desc">';
                            echo '<strong>'.esc_html__('Info', 'super-forms').':</strong> ' . esc_html__('Only lower the render scale when your PDF file size is becoming to large for your use case. This can happen when your form is relatively big. Keep in mind that you will lose "pixel" quality when lowering the render scale. When working with huge forms it is really important to check the PDF file size during development and to adjust the render scale accordingly.', 'super-forms' );
                        echo '</div>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';
        }
        // Get default PDF settings
        public static function get_default_pdf_settings($s) {
            if(empty($s['generate'])) $s['generate'] = 'false';
            if(empty($s['debug'])) $s['debug'] = 'false';
            if(empty($s['filename'])) $s['filename'] = esc_html__( 'form', 'super-forms' ).'.pdf';
            if(empty($s['emailLabel'])) $s['emailLabel'] = esc_html__( 'PDF file', 'super-forms' ).':';
            if(empty($s['adminEmail'])) $s['adminEmail'] = 'true';
            if(empty($s['confirmationEmail'])) $s['confirmationEmail'] = 'true';
            if(empty($s['excludeEntry'])) $s['excludeEntry'] = 'false';
            if(empty($s['downloadBtn'])) $s['downloadBtn'] = 'false';
            if(empty($s['downloadBtnText'])) $s['downloadBtnText'] = esc_html__( 'Download Summary', 'super-forms' );
            if(empty($s['generatingText'])) $s['generatingText'] = esc_html__( 'Generating PDF file...', 'super-forms' );
            if(empty($s['orientation'])) $s['orientation'] = 'portrait';
            if(empty($s['unit'])) $s['unit'] = 'mm';
            if(empty($s['format'])) $s['format'] = 'a4';
            if(empty($s['customFormat'])) $s['customFormat'] = '';
            if(empty($s['textRendering'])) $s['textRendering'] = 'true';
            // Only if form already exists otherwise set to false by default
            if(!empty($_GET['id'])){
                // Form already exists (previously saved)
                if(!isset($s['cyrillicText'])) {
                    $s['cyrillicText'] = 'true'; // makes sure that we don't break existing PDF Generations
                }else{
                    $s['cyrillicText'] = 'false'; // false by default
                }
            }
            if(empty($s['cyrillicText'])) $s['cyrillicText'] = 'false'; // disabled by default, unless otherwise specified
            if(empty($s['arabicText'])) $s['arabicText'] = 'false'; // disabled by default, unless otherwise specified
            if(empty($s['renderScale'])) $s['renderScale'] = '2';
            if(empty($s['margins'])) $s['margins'] = array(
                'body' => array(
                    'top' => 0,
                    'right' => 5,
                    'bottom' => 0,
                    'left' => 5
                ),
                'header' => array(
                    'top' => 5,
                    'right' => 5,
                    'bottom' => 5,
                    'left' => 5
                ),
                'footer' => array(
                    'top' => 5,
                    'right' => 5,
                    'bottom' => 5,
                    'left' => 5
                )
            );
            return $s;
        }
        public function pdf_form_js($js, $attr){
            $form_id = $attr['id'];
            if(absint($form_id)!==0){ 
                $settings = $attr['settings'];
                if(isset($settings['_pdf'])){
                    $_pdf = wp_slash(wp_slash(json_encode($settings['_pdf'], JSON_UNESCAPED_UNICODE)));
                    $js .= 'if(typeof SUPER.form_js === "undefined"){ SUPER.form_js = {}; SUPER.form_js['.$form_id.'] = {}; }else{ if(!SUPER.form_js['.$form_id.']){ SUPER.form_js['.$form_id.'] = {}; } } SUPER.form_js['.$form_id.']["_pdf"] = JSON.parse("'.$_pdf.'");';
                }
            }
            return $js;
        }
        public function pdf_element_settings($array, $attr){
            foreach($array as $group => $v){
                $shortcodes = $array[$group]['shortcodes'];
                foreach($shortcodes as $tag => $settings){
                    if( (isset($settings['callback'])) && (isset($array[$group]['shortcodes'][$tag])) && (isset($array[$group]['shortcodes'][$tag]['atts'])) ) {
                        if($tag==='pdf_page_break') continue;
                        $array[$group]['shortcodes'][$tag]['atts']['pdf'] = array(
                            'name' => esc_html__( 'PDF Settings', 'super-forms' ),
                            'fields' => array(
                                'pdfOption' => array(
                                    'name' => esc_html__( 'PDF options', 'super-forms' ), 
                                    'label' => esc_html__( 'Change the behavior of the element when the PDF is generated.', 'super-forms' ),
                                    'default' => ( !isset( $attr['pdfOption'] ) ? 'none' : $attr['pdfOption'] ),
                                    'type' => 'select',
                                    'values' => array(
                                        'none' => esc_html__( 'Show on Form and in PDF file (default)', 'super-forms' ), 
                                        'exclude' => esc_html__( 'Only show on Form', 'super-forms' ), 
                                        'include' => esc_html__( 'Only show in PDF file', 'super-forms' ),
                                        'header' => esc_html__( 'Use as PDF header', 'super-forms' ),
                                        'footer' => esc_html__( 'Use as PDF footer', 'super-forms' )
                                    ),
                                    'filter' => true
                                )
                            )
                        );
                    }
                }
            }
            return $array;
        }
    }
endif;

/**
 * Returns the main instance of SUPER_PDF_Generator to prevent the need to use globals.
 *
 * @return SUPER_PDF_Generator
 */
if(!function_exists('SUPER_PDF_Generator')){
    function SUPER_PDF_Generator() {
        return SUPER_PDF_Generator::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_PDF_Generator'] = SUPER_PDF_Generator();
}
