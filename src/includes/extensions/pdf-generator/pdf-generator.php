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
            $slug = SUPER_PDF_Generator()->add_on_slug;
            $settings = (isset($atts['settings']['_'.$slug]) ? $atts['settings']['_'.$slug] : array());
            $s = self::get_default_pdf_settings($settings);
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
                                            'default' => 'form.pdf'
                                        ),
                                        array(
                                            'name' => 'emailLabel',
                                            'title' => esc_html__( 'E-mail label', 'super-forms' ),
                                            'subline' => esc_html__( 'use {tags} if needed', 'super-forms' ),
                                            'type' => 'text',
                                            'default' => 'PDF file'
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
                                            'default' => 'Generating PDF file...'
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
                            )
                        )
                    )
                ),

            // tmp         // PDF render scale
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'PDF render scale', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="number" name="renderScale" value="' . esc_attr($s['renderScale']) . '" />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( 'recommended render scale is between 1 and 3 (the default scale is 2)', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp             echo '<div class="sfui-notice sfui-desc">';
            // tmp                 echo '<strong>'.esc_html__('Info', 'super-forms').':</strong> ' . esc_html__('Only lower the render scale when your PDF file size is becoming to large for your use case. This can happen when your form is relatively big. Keep in mind that you will lose "pixel" quality when lowering the render scale. When working with huge forms it is really important to check the PDF file size during development and to adjust the render scale accordingly.', 'super-forms' );
            // tmp             echo '</div>';
            // tmp         echo '</div>';




            );
            $prefix = array();
            SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);

            // tmp // Hiding/Showing elements in PDF
            // tmp echo '<div class="sfui-notice sfui-desc">';
            // tmp     echo '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . esc_html__( 'If you want to hide elements from the PDF file, you can edit the element and configure this under the "PDF Settings" section.', 'super-forms' );
            // tmp echo '</div>';
            // tmp // Header/Footer usage notice
            // tmp echo '<div class="sfui-notice sfui-desc">';
            // tmp     echo '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . esc_html__( 'You can define a column to act as your PDF header/footer. Note that you can only define one header and one footer element. Headers and footers will be visible on every page in the PDF. You may use the {pdf_page} and {pdf_total_pages} tags for pagination purposes inside your header/footer.', 'super-forms' );
            // tmp echo '</div>';
            // tmp // Enable PDF
            // tmp echo '<div class="sfui-setting">';
            // tmp     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp         echo '<input type="checkbox" name="generate" value="true"' . ($s['generate']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp         echo '<span class="sfui-title">' . esc_html__( 'Enable Form to PDF generation', 'super-forms' ) . '</span>';
            // tmp     echo '</label>';
            // tmp     echo '<div class="sfui-sub-settings" data-f="generate;true">';
            // tmp         // Debug mode
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="checkbox" name="debug" value="true"' . ($s['debug']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<span class="sfui-title">' . esc_html__( 'Enable debug mode (this will not submit the form, but directly download the generated PDF, only enable this when developing your form)', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // Native mode
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)" style="align-items: flex-start;">';
            // tmp                 echo '<input type="checkbox" name="native" value="true"' . ($s['native']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<div class="sfui-vertical" style="display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start;">';
            // tmp                     echo '<span class="sfui-title">' . esc_html__( 'Enable native mode', 'super-forms' ) . ' <strong style="color:red;">(recommended)</strong></span>';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'Smaller PDF size and faster rendering.', 'super-forms' ) . '</span>';
            // tmp                 echo '</div>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // Smart page breaks
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Smart page break', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="number" min="0" max="99" name="smartBreak" value="' . esc_attr($s['smartBreak']) . '" />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( 'If the element does not fit on the current page, push it to the next page automatically. Recommended value is 95%, set to 0% to disable', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // PDF file name
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'PDF filename', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="text" name="filename" value="' . esc_attr($s['filename']) . '" />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( 'use {tags} if needed', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // Email label
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Email label', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="text" name="emailLabel" value="' . esc_attr($s['emailLabel']) . '" />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( 'use {tags} if needed', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // Attach generated PDF to admin e-mail
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="checkbox" name="adminEmail" value="true"' . ($s['adminEmail']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<span class="sfui-title">' . esc_html__( 'Attach generated PDF to admin e-mail', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // Attach generated PDF to confirmation e-mail
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="checkbox" name="confirmationEmail" value="true"' . ($s['confirmationEmail']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<span class="sfui-title">' . esc_html__( 'Attach generated PDF to confirmation e-mail', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // Do not save PDF in Contact Entry
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="checkbox" name="excludeEntry" value="true"' . ($s['excludeEntry']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<span class="sfui-title">' . esc_html__( 'Do not save PDF in Contact Entry', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         
            // tmp         // Show download button to the user after PDF was generated
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="checkbox" name="downloadBtn" value="true"' . ($s['downloadBtn']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<span class="sfui-title">' . esc_html__( 'Show download button to the user after PDF was generated', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp             echo '<div class="sfui-sub-settings" data-f="downloadBtn;true">';
            // tmp                 // Download button text
            // tmp                 echo '<div class="sfui-setting sfui-inline">';
            // tmp                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                         echo '<span class="sfui-title">' . esc_html__( 'Download button text', 'super-forms' ) . ':</span>';
            // tmp                         echo '<input type="text" name="downloadBtnText" value="' . esc_attr($s['downloadBtnText']) . '" />';
            // tmp                     echo '</label>';
            // tmp                 echo '</div>';
            // tmp                 // Generating text
            // tmp                 echo '<div class="sfui-setting sfui-inline">';
            // tmp                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                         echo '<span class="sfui-title">' . esc_html__( 'Generating text', 'super-forms' ) . ':</span>';
            // tmp                         echo '<input type="text" name="generatingText" value="' . esc_attr($s['generatingText']) . '" />';
            // tmp                     echo '</label>';
            // tmp                 echo '</div>';
            // tmp             echo '</div>';
            // tmp         echo '</div>';

            // tmp         // Page orientation
            // tmp         echo '<form class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Page orientation', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="radio" name="orientation" value="portrait"' . ($s['orientation']==='portrait' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Portrait', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="radio" name="orientation" value="landscape"' . ($s['orientation']==='landscape' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Landscape', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</form>';

            // tmp         // Unit
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Unit', 'super-forms' ) . ':</span>';
            // tmp             echo '<label>';
            // tmp                 echo '<select name="unit" onChange="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<option '.($s['unit']=='mm' ? ' selected="selected"' : '').' value="mm">mm ('.esc_html__('default').')</option>';
            // tmp                     echo '<option '.($s['unit']=='pt' ? ' selected="selected"' : '').' value="pt">pt</option>';
            // tmp                     echo '<option '.($s['unit']=='cm' ? ' selected="selected"' : '').' value="cm">cm</option>';
            // tmp                     echo '<option '.($s['unit']=='in' ? ' selected="selected"' : '').' value="in">in</option>';
            // tmp                     echo '<option '.($s['unit']=='px' ? ' selected="selected"' : '').' value="px">px</option>';
            // tmp                 echo '</select>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         
            // tmp         // Page format
            // tmp         $formats = array();
            // tmp         $i = 0;
            // tmp         for($i=0; $i < 10; $i++){
            // tmp             $formats[] = 'a'.$i;
            // tmp         }
            // tmp         $i = 0;
            // tmp         for($i=0; $i < 10; $i++){
            // tmp             $formats[] = 'b'.$i;
            // tmp         }
            // tmp         $i = 0;
            // tmp         for($i=0; $i < 10; $i++){
            // tmp             $formats[] = 'c'.$i;
            // tmp         }
            // tmp         $formats = array_merge($formats, array('dl', 'letter', 'government-letter', 'legal', 'junior-legal', 'ledger', 'tabloid', 'credit-card'));
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Page format', 'super-forms' ) . ':</span>';
            // tmp             echo '<label>';
            // tmp                 echo '<select name="format" onChange="SUPER.ui.updateSettings(event, this)">';
            // tmp                 foreach($formats as $v){
            // tmp                     echo '<option value="' . esc_attr($v) . '"'.($v==$s['format'] ? ' selected="selected"' : '') .'>' . ($v=='a4' ? $v . ' (' . esc_html__( 'default', 'super-forms' ) . ')' : $v) . '</option>';
            // tmp                 }
            // tmp                 echo '</select>';
            // tmp             echo '</label>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<span class="sfui-title">' . esc_html__( 'Custom page format in units defined above e.g: 210,297', 'super-forms' ) . ':</span>';
            // tmp                 echo '<input type="text" name="customFormat" value="' . esc_attr($s['customFormat']) . '" />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( '(optional, leave blank for none)', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';

            // tmp         // Body margins (in units declared above)
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Body margins (in units declared above)', 'super-forms' ) . '</span>';
            // tmp             echo '<div class="sfui-setting sfui-inline">';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'top', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.body.top" value="' . esc_attr($s['margins']['body']['top']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'right', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.body.right" value="' . esc_attr($s['margins']['body']['right']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'bottom', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.body.bottom" value="' . esc_attr($s['margins']['body']['bottom']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'left', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.body.left" value="' . esc_attr($s['margins']['body']['left']) . '" />';
            // tmp                 echo '</label>';
            // tmp             echo '</div>';
            // tmp         echo '</div>';
            // tmp         // Header margins (in units declared above)
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Header margins (in units declared above)', 'super-forms' ) . '</span>';
            // tmp             echo '<span class="sfui-label">' . esc_html__( 'Note: if you wish to use a header make sure define one element in your form to act as the PDF header, you can do so under "PDF Settings" TAB when editing an element', 'super-forms' ) . '</span>';
            // tmp             echo '<div class="sfui-setting sfui-inline">';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'top', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.header.top" value="' . esc_attr($s['margins']['header']['top']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'right', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.header.right" value="' . esc_attr($s['margins']['header']['right']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'bottom', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.header.bottom" value="' . esc_attr($s['margins']['header']['bottom']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'left', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.header.left" value="' . esc_attr($s['margins']['header']['left']) . '" />';
            // tmp                 echo '</label>';
            // tmp             echo '</div>';
            // tmp         echo '</div>';
            // tmp         // Footer margins (in units declared above)
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Footer margins (in units declared above)', 'super-forms' ) . '</span>';
            // tmp             echo '<span class="sfui-label">' . esc_html__( 'Note: if you wish to use a footer make sure define one element in your form to act as the PDF footer, you can do so under "PDF Settings" TAB when editing an element', 'super-forms' ) . '</span>';
            // tmp             echo '<div class="sfui-setting sfui-inline">';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'top', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.footer.top" value="' . esc_attr($s['margins']['footer']['top']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'right', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.footer.right" value="' . esc_attr($s['margins']['footer']['right']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'bottom', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.footer.bottom" value="' . esc_attr($s['margins']['footer']['bottom']) . '" />';
            // tmp                 echo '</label>';
            // tmp                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<span class="sfui-label">' . esc_html__( 'left', 'super-forms' ) . '</span>';
            // tmp                     echo '<input type="number" name="margins.footer.left" value="' . esc_attr($s['margins']['footer']['left']) . '" />';
            // tmp                 echo '</label>';
            // tmp             echo '</div>';
            // tmp         echo '</div>';
            // tmp         // PDF Text rendering
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'PDF font normalization', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="checkbox" name="normalizeFonts" value="true"' . ($s['normalizeFonts']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( 'Enable (recommended)', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'PDF Text rendering', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="checkbox" name="textRendering" value="true"' . ($s['textRendering']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( 'Enable (makes it possible to search for text inside the PDF)', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp         echo '</div>';
            // tmp         // This allows to copy cyrillic text
            // tmp         echo '<div class="sfui-setting">';
            // tmp             echo '<div class="sfui-sub-settings" data-f="textRendering;true">';
            // tmp                 // PDF Cyrillic text
            // tmp                 echo '<div class="sfui-setting sfui-inline">';
            // tmp                     echo '<span class="sfui-title">' . esc_html__( 'Cyrillic text', 'super-forms' ) . ':</span>';
            // tmp                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                         echo '<input type="checkbox" name="cyrillicText" value="true"' . ($s['cyrillicText']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                         echo '<span class="sfui-label">' . esc_html__( 'Enable (only enable this if your form uses cyrillic text)', 'super-forms' ) . '</span>';
            // tmp                     echo '</label>';
            // tmp                 echo '</div>';
            // tmp                 // PDF Arabic text
            // tmp                 echo '<div class="sfui-setting sfui-inline">';
            // tmp                     echo '<span class="sfui-title">' . esc_html__( 'Arabic text', 'super-forms' ) . ':</span>';
            // tmp                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                         echo '<input type="checkbox" name="arabicText" value="true"' . ($s['arabicText']==='true' ? ' checked="checked"' : '') . ' />';
            // tmp                         echo '<span class="sfui-label">' . esc_html__( 'Enable (only enable this if your form uses arabic text)', 'super-forms' ) . '</span>';
            // tmp                     echo '</label>';
            // tmp                 echo '</div>';
            // tmp             echo '</div>';
            // tmp         echo '</div>';
            // tmp         // Image quality
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'Image quality', 'super-forms' ) . ':</span>';
            // tmp             echo '<label>';
            // tmp                 echo '<select name="imageQuality" onChange="SUPER.ui.updateSettings(event, this)">';
            // tmp                     echo '<option '.($s['imageQuality']=='FAST' ? ' selected="selected"' : '').' value="FAST">Low ('.esc_html__('default').')</option>';
            // tmp                     echo '<option '.($s['imageQuality']=='MEDIUM' ? ' selected="selected"' : '').' value="MEDIUM">Medium</option>';
            // tmp                     echo '<option '.($s['imageQuality']=='SLOW' ? ' selected="selected"' : '').' value="SLOW">High</option>';
            // tmp                     echo '<option '.($s['imageQuality']=='NONE' ? ' selected="selected"' : '').' value="NONE">Original</option>';
            // tmp                 echo '</select>';
            // tmp             echo '</label>';
            // tmp             echo '<span class="sfui-label">' . esc_html__( 'PDF file size will be smaller when using lower quality', 'super-forms' ) . '</span>';
            // tmp         echo '</div>';
            // tmp         // PDF render scale
            // tmp         echo '<div class="sfui-setting sfui-inline">';
            // tmp             echo '<span class="sfui-title">' . esc_html__( 'PDF render scale', 'super-forms' ) . ':</span>';
            // tmp             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
            // tmp                 echo '<input type="number" name="renderScale" value="' . esc_attr($s['renderScale']) . '" />';
            // tmp                 echo '<span class="sfui-label">' . esc_html__( 'recommended render scale is between 1 and 3 (the default scale is 2)', 'super-forms' ) . '</span>';
            // tmp             echo '</label>';
            // tmp             echo '<div class="sfui-notice sfui-desc">';
            // tmp                 echo '<strong>'.esc_html__('Info', 'super-forms').':</strong> ' . esc_html__('Only lower the render scale when your PDF file size is becoming to large for your use case. This can happen when your form is relatively big. Keep in mind that you will lose "pixel" quality when lowering the render scale. When working with huge forms it is really important to check the PDF file size during development and to adjust the render scale accordingly.', 'super-forms' );
            // tmp             echo '</div>';
            // tmp         echo '</div>';

            // tmp     echo '</div>';
            // tmp echo '</div>';
        }
        // Get default PDF settings
        public static function get_default_pdf_settings($s) {
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
            return $s;
        }
        public function pdf_form_js($js, $x){
            $form_id = $x['id'];
            if(absint($form_id)!==0){ 
                $settings = $x['settings'];
                if(isset($settings['_pdf'])){
                    $_pdf = wp_slash(wp_slash(json_encode($settings['_pdf'], JSON_UNESCAPED_UNICODE)));
                    $js .= 'if(typeof SUPER === "undefined"){var SUPER = {};}if(typeof SUPER.form_js === "undefined"){ SUPER.form_js = {}; SUPER.form_js['.$form_id.'] = {}; }else{ if(!SUPER.form_js['.$form_id.']){ SUPER.form_js['.$form_id.'] = {}; } } SUPER.form_js['.$form_id.']["_pdf"] = JSON.parse("'.$_pdf.'");';
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
