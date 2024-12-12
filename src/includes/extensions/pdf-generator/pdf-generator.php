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
            if( !empty($settings['_pdf']) && !empty($settings['_pdf']['generate']) && $settings['_pdf']['generate']=='true' ) {
                wp_enqueue_script( 'super-html-canvas', SUPER_PLUGIN_FILE.'includes/extensions/pdf-generator/super-html-canvas.min.js', array(), SUPER_VERSION, false );
                wp_enqueue_script( 'super-pdf-gen', SUPER_PLUGIN_FILE.'includes/extensions/pdf-generator/super-pdf-gen.min.js', array( 'super-html-canvas' ), SUPER_VERSION, false );
            }
        }
        public static function add_tab($tabs){
            $tabs['pdf'] = esc_html__( 'PDF', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            extract($atts);
            //error_log('t3: '.json_encode($pdf));
            $slug = SUPER_PDF_Generator()->add_on_slug;
            $s = self::get_default_pdf_settings($pdf);
            //error_log('t4: '.json_encode($s));
            $logic = array( '==' => '== Equal', '!=' => '!= Not equal', '??' => '?? Contains', '!!' => '!! Not contains', '>'  => '&gt; Greater than', '<'  => '&lt;  Less than', '>=' => '&gt;= Greater than or equal to', '<=' => '&lt;= Less than or equal');
            // Page format
            $formats = array();
            for($i=0; $i < 10; $i++){ $formats[] = array('v'=>'a'.$i); }
            for($i=0; $i < 10; $i++){ $formats[] = array('v'=>'b'.$i); }
            for($i=0; $i < 10; $i++){ $formats[] = array('v'=>'c'.$i); }
            $formats = array_merge($formats, array(array('v'=>'dl'), array('v'=>'letter'), array('v'=>'government-letter'), array('v'=>'legal'), array('v'=>'junior-legal'), array('v'=>'ledger'), array('v'=>'tabloid'), array('v'=>'credit-card'), array('v'=>'custom')));
            $nodes = array(
                array(
                    'notice' => 'hint', // hint/info
                    'content' => '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . esc_html__( 'If you want to hide elements from the PDF file, you can edit the element and configure this under the "PDF Settings" section.', 'super-forms' ),
                ),
                array(
                    'notice' => 'hint', // hint/info
                    'content' => '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . esc_html__( 'You can define a column to act as your PDF header/footer. Note that you can only define one header and one footer element. Headers and footers will be visible on every page in the PDF. You may use the {pdf_page} and {pdf_total_pages} tags for pagination purposes inside your header/footer.', 'super-forms' )
                ),
                array(
                    'name' => 'generate',
                    'title' => esc_html__( 'Enable Form to PDF generation', 'super-forms' ),
                    'type' => 'checkbox',
                    'default' => '',
                    'nodes' => array(
                        array(
                            'sub' => true, // sfui-sub-settings
                            'filter' => 'generate;true',
                            'nodes' => array(
                                array(
                                    //'width_auto' => false, // 'sfui-width-auto'
                                    'wrap' => false,
                                    'group' => true, // sfui-setting-group
                                    'group_name' => 'conditions',
                                    'inline' => true, // sfui-inline
                                    //'vertical' => true, // sfui-vertical
                                    'nodes' => array(
                                        array(
                                            'name' => 'enabled',
                                            'type' => 'checkbox',
                                            'default' => 'false',
                                            'title' => esc_html__( 'Only generate PDF when below condition is met', 'super-forms' ),
                                            'nodes' => array(
                                                array(
                                                    'sub' => true, // sfui-sub-settings
                                                    //'group' => true, // sfui-setting-group
                                                    'inline' => true, // sfui-inline
                                                    //'vertical' => true, // sfui-vertical
                                                    'filter' => 'conditions.enabled;true',
                                                    'nodes' => array(
                                                        array( 'name' => 'f1', 'type' => 'text', 'default' => '', 'placeholder' => 'e.g. {tag}',),
                                                        array( 'name' => 'logic', 'type' => 'select', 'options' => $logic, 'default' => '',),
                                                        array( 'name' => 'f2', 'type' => 'text', 'default' => '', 'placeholder' => 'e.g. true')
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'General settings', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'name' => 'debug',
                                            'title' => esc_html__( 'Enable debug mode', 'super-forms' ),
                                            'subline' => esc_html__( '(this will not submit the form, but directly download the generated PDF, only enable this when developing your form)', 'super-forms' ),
                                            'type' => 'checkbox',
                                            'default' => ''
                                        ),
                                        array(
                                            'name' => 'native',
                                            'title' => esc_html__( 'Enable native mode', 'super-forms' ) . ' <strong style="color:red;">('.esc_html__( 'recommended', 'super-forms' ).')</strong>',
                                            'subline' => esc_html__( '(smaller PDF size and faster rendering)', 'super-forms' ),
                                            'type' => 'checkbox',
                                            'default' => ''
                                        ),
                                        array(
                                            'name' => 'filename',
                                            'title' => esc_html__( 'PDF filename', 'super-forms' ),
                                            'subline' => esc_html__( 'use {tags} if needed', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'form.pdf',
                                            'i18n' => true
                                        ),
                                        array(
                                            'name' => 'emailLabel',
                                            'title' => esc_html__( 'E-mail label', 'super-forms' ),
                                            'subline' => esc_html__( 'use {tags} if needed', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'PDF file',
                                            'i18n' => true
                                        ),
                                        array(
                                            'name' => 'adminEmail',
                                            'title' => esc_html__( 'Attach generated PDF to admin email', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'true',
                                            'accepted_values' => array(array('v'=>'true'), array('v'=>'false'))
                                        ),
                                        array(
                                            'name' => 'confirmationEmail',
                                            'title' => esc_html__( 'Attach generated PDF to confirmation email', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'true',
                                            'accepted_values' => array(array('v'=>'true'), array('v'=>'false'))
                                        ),
                                        array(
                                            'name' => 'excludeEntry',
                                            'title' => esc_html__( 'Do not save PDF in Contact Entry', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'false',
                                            'accepted_values' => array(array('v'=>'true'), array('v'=>'false'))
                                        ),
                                        array(
                                            'width_auto' => true,
                                            'name' => 'downloadBtn',
                                            'title' => esc_html__( 'Show download button to the user after PDF was generated', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'true',
                                            'accepted_values' => array(array('v'=>'true'), array('v'=>'false'))
                                        ),
                                        array(
                                            'width_auto' => true,
                                            'name' => 'downloadBtnText',
                                            'title' => esc_html__( 'Download button text', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'true',
                                            'filter' => 'downloadBtn;true'
                                        ),
                                        array(
                                            'name' => 'generatingText',
                                            'title' => esc_html__( 'Generating text', 'super-forms' ),
                                            'subline' => esc_html__( 'Text displayed to the user while the PDF file is being generated', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'Generating PDF file...',
                                            'i18n' => true
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Page dimensions', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'wrap' => false,
                                            'group' => true,
                                            'group_name' => '',
                                            'vertical' => true,
                                            'nodes' => array(
                                                array(
                                                    'width_auto' => true,
                                                    'name' => 'orientation',
                                                    'title' => esc_html__( 'Page orientation', 'super-forms' ),
                                                    'type' => 'text',
                                                    'accepted_values' => array(array('v'=>'portrait'), array('v'=>'landscape')),
                                                    'default' => 'portrait'
                                                ),
                                                array(
                                                    'width_auto' => true,
                                                    'name' => 'format',
                                                    'title' => esc_html__( 'Page format', 'super-forms' ),
                                                    'type' => 'text',
                                                    'accepted_values' => $formats,
                                                    'default' => 'a4'
                                                ),
                                                array(
                                                    'width_auto' => true,
                                                    'name' => 'customFormat',
                                                    'title' => esc_html__( 'Custom page format in units defined above e.g: 210,297', 'super-forms' ),
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'filter' => 'format;custom'
                                                ),
                                                array(
                                                    'width_auto' => true,
                                                    'name' => 'unit',
                                                    'title' => esc_html__( 'Unit', 'super-forms' ),
                                                    'type' => 'text',
                                                    'accepted_values' => array(array('v'=>'mm', 'i'=>'(default)'), array('v'=>'pt'), array('v'=>'cm'), array('v'=>'in'), array('v'=>'px')),
                                                    'default' => 'mm'
                                                ),
                                                array(
                                                    'wrap' => false,
                                                    'padding' => false,
                                                    'group' => true,
                                                    'group_name' => 'margins',
                                                    'vertical' => true,
                                                    'nodes' => array(
                                                        array(
                                                            'toggle' => true,
                                                            'title' => esc_html__( 'Header margins (in units declared above)', 'super-forms' ),
                                                            'vertical' => true,
                                                            'nodes' => array(
                                                                array(
                                                                    'wrap' => false,
                                                                    'padding' => false,
                                                                    'group' => true,
                                                                    'group_name' => 'header',
                                                                    'inline' => true,
                                                                    'nodes' => array(
                                                                        array( 'width_auto' => true, 'name' => 'top', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Top', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'right', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Right', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'bottom', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Bottom', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'left', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Left', 'super-forms' )),
                                                                    )
                                                                )
                                                            )
                                                        ),
                                                        array(
                                                            'toggle' => true,
                                                            'title' => esc_html__( 'Body margins (in units declared above)', 'super-forms' ),
                                                            'vertical' => true,
                                                            'nodes' => array(
                                                                array(
                                                                    'wrap' => false,
                                                                    'padding' => false,
                                                                    'group' => true,
                                                                    'group_name' => 'body',
                                                                    'inline' => true,
                                                                    'nodes' => array(
                                                                        array( 'width_auto' => true, 'name' => 'top', 'type' => 'text', 'default' => '0', 'title' => esc_html__( 'Top', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'right', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Right', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'bottom', 'type' => 'text', 'default' => '0', 'title' => esc_html__( 'Bottom', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'left', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Left', 'super-forms' )),
                                                                    )
                                                                )
                                                            )
                                                        ),
                                                        array(
                                                            'toggle' => true,
                                                            'title' => esc_html__( 'Footer margins (in units declared above)', 'super-forms' ),
                                                            'vertical' => true,
                                                            'nodes' => array(
                                                                array(
                                                                    'wrap' => false,
                                                                    'padding' => false,
                                                                    'group' => true,
                                                                    'group_name' => 'footer',
                                                                    'inline' => true,
                                                                    'nodes' => array(
                                                                        array( 'width_auto' => true, 'name' => 'top', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Top', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'right', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Right', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'bottom', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Bottom', 'super-forms' )),
                                                                        array( 'width_auto' => true, 'name' => 'left', 'type' => 'text', 'default' => '5', 'title' => esc_html__( 'Left', 'super-forms' )),
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Render settings', 'super-forms' ),
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'name' => 'smartBreak',
                                            'title' => esc_html__( 'Smart page break', 'super-forms' ),
                                            'subline' => esc_html__( 'If the element does not fit on the current page, push it to the next page automatically. Recommended value is 95%, set to 0% to disable', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => '95'
                                        ),
                                        array(
                                            'name' => 'normalizeFonts',
                                            'title' => esc_html__( 'PDF font normalization', 'super-forms' ),
                                            'subline' => esc_html__( 'It is recommended to leave this option enable if possible', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'true',
                                            'accepted_values' => array(array('v'=>'true'), array('v'=>'false'))
                                        ),
                                        array(
                                            'name' => 'textRendering',
                                            'title' => esc_html__( 'PDF Text rendering', 'super-forms' ),
                                            'subline' => esc_html__( 'This makes it possible to search for text inside the PDF', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'true',
                                            'filter' => 'native;!true',
                                            'accepted_values' => array(array('v'=>'true'), array('v'=>'false'))
                                        ),
                                        array(
                                            'notice' => 'info', // hint/info
                                            'content' => 'Only set language script to <code>unicode</code> if required, to keep the PDF file size at a minimum.',
                                            'filter' => 'checkout;true'
                                        ),
                                        array(
                                            'name' => 'language',
                                            'title' => esc_html__( 'Language script', 'super-forms' ),
                                            'subline' => esc_html__( 'Or leave blank to use the default Latin (Roman) script', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'latin',
                                            'i18n' => true,
                                            'accepted_values' => array(
                                                array('v'=>'latin', 'i'=>'(default)'), 
                                                array('v'=>'greek'), // greek
                                                array('v'=>'cyrillic'), // russian
                                                array('v'=>'arabic'), // arabic
                                                array('v'=>'persian'), // Arabic based
                                                array('v'=>'urdu'), // Arabic based
                                                array('v'=>'devanagari'), // hindi
                                                array('v'=>'chinese'), // simplified chinese
                                                array('v'=>'hangul'), // korean
                                                array('v'=>'japanese'), // hiragana and katakana
                                                array('v'=>'hebrew'), 
                                                array('v'=>'thai'),
                                                array('v'=>'bengali'), 
                                                array('v'=>'tamil'), 
                                                array('v'=>'armenian'), 
                                                array('v'=>'georgian'), 
                                                array('v'=>'khmer'),
                                                array('v'=>'myanmar'),
                                                array('v'=>'sinhala'),
                                                array('v'=>'gujarati'), 
                                                array('v'=>'gurmukhi'), 
                                                array('v'=>'kannada'), 
                                                array('v'=>'lao'), 
                                                array('v'=>'malayalam'), 
                                                array('v'=>'oriya'), 
                                                array('v'=>'telugu'), 
                                                array('v'=>'tibetan'), 
                                                array('v'=>'unicode', 'i'=>'(multi-language)'),
                                            )
                                        ),
                                        array(
                                            'name' => 'fontSizeTuning',
                                            'title' => esc_html__( 'Fine tune font size', 'super-forms' ),
                                            'subline' => esc_html__( 'Enter a float value as the multiplier value', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => '1.00',
                                            'accepted_values' => array(
                                                array('v'=>'1.00', 'i'=>'(default)'), 
                                                array('v'=>'0.90', 'i'=>'(decrease)'), 
                                                array('v'=>'1.10', 'i'=>'(increase)')
                                            )
                                        ),
                                        array(
                                            'name' => 'imageQuality',
                                            'title' => esc_html__( 'Image processing speed', 'super-forms' ),
                                            'subline' => esc_html__( 'The PDF file size will be smaller when using a faster processing speed due to loss of image quality', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'FAST',
                                            'accepted_values' => array(
                                                array('v'=>'FAST', 'i'=>'(low image quality)'), 
                                                array('v'=>'MEDIUM', 'i'=>'(medium image quality)'), 
                                                array('v'=>'SLOW', 'i'=>'(high image quality)'), 
                                                array('v'=>'NONE', 'i'=>'(original image quality)')
                                            )
                                        ),
                                        array(
                                            'name' => 'renderScale',
                                            'title' => esc_html__( 'PDF render scale', 'super-forms' ),
                                            'subline' => esc_html__( 'The recommended render scale is between 1 and 3, the default scale is 2.', 'super-forms' ),
                                            'type' => 'text',
                                            'accepted_values' => array(array('v'=>'0.5'), array('v'=>'1'), array('v'=>'2.5'), array('v'=>'2', 'i'=>'(default)'), array('v'=>'3'))
                                        )
                                    )
                                ),
                                array(
                                    'wrap' => false,
                                    'group' => true,
                                    'vertical' => true,
                                    'nodes' => array(
                                        array(
                                            'toggle' => true,
                                            'title' => esc_html__( 'Translations (raw)', 'super-forms' ),
                                            'notice' => 'hint', // hint/info
                                            'content' => esc_html__( 'Although you can edit existing translated strings below, you may find it easier to use the [Translations] tab instead.', 'super-forms' ),
                                            'nodes' => array(
                                                array(
                                                    'name' => 'i18n',
                                                    'type' => 'textarea',
                                                    'default' => ''
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            );
            $prefix = array();
            SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);
        }
        // Get default PDF settings
        public static function get_default_pdf_settings($settings=array(), $s=array()){
            if(empty($s['generate'])) $s['generate'] = 'false';
            if(isset($s['debug'])) {
                if(empty($s['native'])) $s['native'] = 'false';
            }else{
                if(empty($s['native'])) $s['native'] = 'true';
            }
            if(empty($s['imageQuality'])) $s['imageQuality'] = 'FAST'; // 'NONE', 'FAST', 'MEDIUM' or 'SLOW'
            if(empty($s['debug'])) $s['debug'] = 'false';
            if(empty($s['smartBreak'])) $s['smartBreak'] = 95;
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
            if(empty($s['normalizeFonts'])) $s['normalizeFonts'] = 'true';
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
            $s = apply_filters('super_pdf_default_settings_filter', $s);
            if(isset($settings)){
                if($settings==='') $settings = array();
                $s = array_merge($s, $settings);
            }
            return $s;
        }
        public function pdf_form_js($js, $x){
            $form_id = $x['id'];
            if(absint($form_id)!==0){ 
                $settings = $x['settings'];
                if(isset($settings['_pdf'])){
                    $_pdf = wp_slash(wp_slash(SUPER_Common::safe_json_encode($settings['_pdf'], JSON_UNESCAPED_UNICODE)));
                    $js .= 'if(typeof SUPER === "undefined"){var SUPER = {};}if(typeof SUPER.form_js === "undefined"){ SUPER.form_js = {}; SUPER.form_js['.$form_id.'] = {}; }else{ if(!SUPER.form_js['.$form_id.']){ SUPER.form_js['.$form_id.'] = {}; } } debugger;SUPER.form_js['.$form_id.']["_pdf"] = JSON.parse("'.$_pdf.'");debugger;';
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
