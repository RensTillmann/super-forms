<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Stripe')) :


    /**
     * Main SUPER_Stripe Class
     *
     * @class SUPER_Stripe
     * @version 1.0.0
     */
    final class SUPER_Stripe {
    
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'stripe';

        
        /**
         * @var SUPER_Stripe The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Stripe Instance
         *
         * Ensures only one instance of SUPER_Stripe is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Stripe()
         * @return SUPER_Stripe - Main instance
         *
         *  @since      1.0.0
        */
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        
        /**
         * SUPER_Stripe Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_stripe_loaded');
        }

        
        /**
         * Hook into actions and filters
         *
         *  @since      1.0.0
        */
        private function init_hooks() {
            if ( SUPER_Forms()->is_request( 'admin' ) ) {
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 10, 1 );
                add_action( 'super_create_form_stripe_tab', array( $this, 'add_tab_content' ) );
                add_filter( 'super_enqueue_styles', array( $this, 'add_style' ), 10, 1 );
                add_filter( 'super_enqueue_scripts', array( $this, 'add_script' ), 10, 1 );
            }
            add_action( 'wp_ajax_super_load_form_inside_modal', array( $this, 'load_form_inside_modal' ) );
            add_action( 'wp_ajax_nopriv_super_load_form_inside_modal', array( $this, 'load_form_inside_modal' ) );
            add_filter( 'super_before_form_render_settings_filter', array( $this, 'alter_form_settings_before_rendering' ), 10, 2 );
            add_filter( 'super_before_submit_form_settings_filter', array( $this, 'alter_form_settings_before_submit' ), 10, 2 );
        }

        public static function stripe_checkout_redirect(){
            require 'vendor/autoload.php';
            // This is your test secret API key.
            \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');
            header('Content-Type: application/json');
            $YOUR_DOMAIN = 'http://localhost:4242/public';
            $checkout_session = \Stripe\Checkout\Session::create(
                array(
                    'line_items' => array(
                        array(
                            # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
                            'price' => '{{PRICE_ID}}',
                            'quantity' => 1,
                        )
                    ),
                    'mode' => 'payment',
                    'automatic_tax' => array(
                        'enabled' => true,
                    ),
                )
            );
            header("HTTP/1.1 303 See Other");
            header("Location: " . $checkout_session->url);

        }

        // Required to change some settings when editing/updating an existing entry via Stripe Add-on
        public static function alter_form_settings_before_rendering($settings, $args){
            extract($args);
            if($id!=='' && $list_id!=='' && $entry_id!==''){
                // In order to edit entries we need to make sure some settings are not enabled
                $overrideSettings = array(
                    'update_contact_entry'=>'true',
                    'contact_entry_prevent_creation'=>'true',
                    'contact_entry_custom_status_update'=>'',
                    'save_form_progress'=>'',
                    'retrieve_last_entry_data'=>'',
                    'send'=>'no',
                    'confirm'=>'no',
                    'save_contact_entry'=>'no',
                    'form_disable_enter'=>'true',
                    'form_locker'=>'',
                    'user_form_locker'=>'',
                    'csv_attachment_enable'=>'',
                    'frontend_posting_action'=>'none',
                    'mailster_enabled'=>'',
                    'paypal_checkout'=>'',
                    'register_login_action'=>'none',
                    'woocommerce_checkout'=>'',
                    'zapier_enable'=>'',
                    'popup_enabled'=>'',
                    //'form_processing_overlay'=>'',
                    'form_show_thanks_msg'=>'',
                    'form_post_option'=>'',
                    'form_post_url'=>'',
                    'form_redirect_option'=>'',
                    'form_hide_after_submitting'=>'',
                    'form_clear_after_submitting'=>'',
                    '_pdf'=>''
                );
                foreach($overrideSettings as $k => $v){
                    $settings[$k] = $v;
                }
            }
            return $settings;
        }

        // Required to change some settings when editing/updating an existing entry via Stripe Add-on
        public static function alter_form_settings_before_submit($settings, $args){
            extract($args);
            if($list_id!==''){
                // In order to edit entries we need to make sure some settings are not enabled
                $overrideSettings = array(
                    'update_contact_entry'=>'true',
                    'contact_entry_prevent_creation'=>'true',
                    'contact_entry_custom_status_update'=>'',
                    'save_form_progress'=>'',
                    'retrieve_last_entry_data'=>'',
                    'send'=>'no',
                    'confirm'=>'no',
                    'save_contact_entry'=>'no',
                    'form_disable_enter'=>'true',
                    'form_locker'=>'',
                    'user_form_locker'=>'',
                    'csv_attachment_enable'=>'',
                    'frontend_posting_action'=>'none',
                    'mailster_enabled'=>'',
                    'paypal_checkout'=>'',
                    'register_login_action'=>'none',
                    'woocommerce_checkout'=>'',
                    'zapier_enable'=>'',
                    'popup_enabled'=>'',
                    //'form_processing_overlay'=>'',
                    'form_show_thanks_msg'=>'',
                    'form_post_option'=>'',
                    'form_post_url'=>'',
                    'form_redirect_option'=>'',
                    'form_hide_after_submitting'=>'',
                    'form_clear_after_submitting'=>'',
                    '_pdf'=>''
                );
                $global_settings = SUPER_Common::get_global_settings();
                $i = 1;
                while($i <= absint($global_settings['email_reminder_amount'])){
                    $overrideSettings['email_reminder_'.$i] = '';
                    $i++;
                }
                foreach($overrideSettings as $k => $v){
                    $settings[$k] = $v;
                }
            }
            return $settings;
        }

        public static function getStandardColumns(){
            return array(
                'title' => array(
                    'name' => esc_html__( 'Entry title', 'super-forms' ),
                    'meta_key' => 'post_title',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'entry_status' => array(
                    'name' => esc_html__( 'Entry status', 'super-forms' ),
                    'meta_key' => 'entry_status',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( '- select -', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'entry_date' => array(
                    'name' => esc_html__( 'Entry date', 'super-forms' ),
                    'meta_key' => 'entry_date',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'wc_order' => array(
                    'name' => esc_html__( 'WC order', 'super-forms' ),
                    'meta_key' => 'wc_order',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'wc_order_status' => array(
                    'name' => esc_html__( 'WC order status', 'super-forms' ),
                    'meta_key' => 'wc_order_status',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( '- select -', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'paypal_order' => array(
                    'name' => esc_html__( 'PayPal order', 'super-forms' ),
                    'meta_key' => 'paypal_order',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'paypal_order_status' => array(
                    'name' => esc_html__( 'PayPal order status', 'super-forms' ),
                    'meta_key' => 'paypal_order_status',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( '- select -', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'paypal_subscription' => array(
                    'name' => esc_html__( 'PayPal subscription', 'super-forms' ),
                    'meta_key' => 'paypal_subscription',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'paypal_subscription_status' => array(
                    'name' => esc_html__( 'PayPal subscription status', 'super-forms' ),
                    'meta_key' => 'paypal_subscription_status',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( '- select -', 'super-forms' )
                    ),
                    'sort' => 'true'
                ),
                'wp_post_title' => array(
                    'name' => esc_html__( 'Created post title', 'super-forms' ),
                    'meta_key' => 'wp_post_title',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'wp_post_status' => array(
                    'name' => esc_html__( 'Created post status', 'super-forms' ),
                    'meta_key' => 'wp_post_status',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( '- select -', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'generated_pdf' => array(
                    'name' => esc_html__( 'Generated PDF', 'super-forms' ),
                    'meta_key' => 'generated_pdf',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'false'
                ),
                'author_username' => array(
                    'name' => esc_html__( 'Author username', 'super-forms' ),
                    'meta_key' => 'author_username',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'author_firstname' => array(
                    'name' => esc_html__( 'Author first name', 'super-forms' ),
                    'meta_key' => 'author_firstname',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'author_lastname' => array(
                    'name' => esc_html__( 'Author last name', 'super-forms' ),
                    'meta_key' => 'author_lastname',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'author_fullname' => array(
                    'name' => esc_html__( 'Author full name', 'super-forms' ),
                    'meta_key' => 'author_fullname',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'author_nickname' => array(
                    'name' => esc_html__( 'Author nickname', 'super-forms' ),
                    'meta_key' => 'author_nickname',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'author_display' => array(
                    'name' => esc_html__( 'Author display name', 'super-forms' ),
                    'meta_key' => 'author_display',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'author_email' => array(
                    'name' => esc_html__( 'Author E-mail', 'super-forms' ),
                    'meta_key' => 'author_email',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                ),
                'author_id' => array(
                    'name' => esc_html__( 'Author ID', 'super-forms' ),
                    'meta_key' => 'author_id',
                    'filter' => array(
                        'enabled' => 'true',
                        'type' => 'text',
                        'placeholder' => esc_html__( 'search...', 'super-forms' ),
                    ),
                    'sort' => 'true'
                )
            );
        }
        // Use custom template to load / display forms
        // When "Edit" entry button is clicked it will be loaded inside the modal through an iframe
        public static function form_blank_page_template( $template ) {
            require_once( SUPER_PLUGIN_DIR . '/includes/class-common.php' );
            return dirname( __FILE__ ) . '/form-blank-page-template.php';
        }
        public static function load_form_inside_modal() {
            $entry_id = absint($_POST['entry_id']);
            // Check if invalid Entry ID
            if( $entry_id==0 ) {
                SUPER_Common::output_message(
                    $error = true,
                    $msg = esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id 
                );
                die();
            }
            // Check if this entry does not have the correct post type, if not then the entry doesn't exist
            if( get_post_type($entry_id)!='super_contact_entry' ) {
                SUPER_Common::output_message(
                    $error = true,
                    $msg = esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id 
                );
                die();
            }
            // Seems that everything is OK, continue and load the form
            $entry = get_post($entry_id);
            $form_id = $entry->post_parent; // This will hold the form ID
            // Now print out the form by executing the shortcode function
            echo SUPER_Shortcodes::super_form_func( array( 'id'=>$form_id ) );
            die();
        }
        public static function add_style($styles){
            // $assets_path = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            // $styles['super-stripe'] = array(
            //     'src'     => $assets_path . 'css/backend/styles.css',
            //     'deps'    => '',
            //     'version' => SUPER_VERSION,
            //     'media'   => 'all',
            //     'screen'  => array( 
            //         'super-forms_page_super_create_form'
            //     ),
            //     'method'  => 'enqueue',
            // );
            return $styles;
        }
        public static function add_script($scripts){
            //$assets_path = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            //$scripts['super-stripe'] = array(
            //    'src'     => $assets_path . 'js/backend/script.js',
            //    'deps'    => array( 'super-common' ),
            //    'version' => SUPER_VERSION,
            //    'footer'  => true,
            //    'screen'  => array(
            //        'super-forms_page_super_create_form'
            //    ),
            //    'method'  => 'enqueue',
            //);
            return $scripts;
        }
        public static function add_tab($tabs){
            $tabs['stripe'] = esc_html__( 'Stripe', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            $slug = SUPER_Stripe()->add_on_slug;
            $form_id = absint($atts['form_id']);
            // $lists = array();
            // if(isset($atts['settings']) && isset($atts['settings']['_'.$slug])){
            //     $lists = $atts['settings']['_'.$slug]['lists'];
            // }
            // if(count($lists)==0) {
            //     $lists[] = self::get_default_stripe_settings(array());
            // }
            // Stripe general information
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('About', 'super-forms').':</strong> ' . esc_html__( 'Stripe allow you to display Contact Entries in a list/table on the front-end. For each form you can have multiple stripe with their own settings. You can copy paste the stripe shortcode anywhere in your page to display the listing.', 'super-forms' );
            echo '</div>';
            
            // Enable stripe
            echo '<div class="sfui-setting">';
                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    echo '<input type="checkbox" name="enabled" value="true"' . (isset($atts['settings']['_stripe']) && $atts['settings']['_stripe']['enabled']==='true' ? ' checked="checked"' : '') . ' />';
                    echo '<span class="sfui-title">' . esc_html__( 'Enable stripe for this form', 'super-forms' ) . '</span>';
                echo '</label>';
                echo '<div class="sfui-sub-settings" data-f="enabled;true">';

                // When enabled, we display the list with stripe
                // echo '<div class="sfui-repeater" data-k="lists">';
                // // Repeater Item
                // foreach($lists as $k => $v){
                //     // Set default values if they don't exist
                //     $v = self::get_default_stripe_settings($v);
                //     echo '<div class="sfui-repeater-item">';
                //         echo '<div class="sfui-inline">';
                //             echo '<div class="sfui-setting sfui-vertical">';
                //                 echo '<label>';
                //                     echo '<input type="text" name="name" value="' . $v['name'] . '" />';
                //                     echo '<span class="sfui-label"><i>' . esc_html__( 'Give this listing a name', 'super-forms' ) . '</i></span>';
                //                 echo '</label>';
                //             echo '</div>';
                //             echo '<div class="sfui-setting sfui-vertical">';
                //                 echo '<label>';
                //                     // Get the correct shortcode for this list
                //                     $shortcode = '['.esc_html__( 'form-not-saved-yet', 'super-forms' ).']';
                //                     if( $form_id!=0 ) $shortcode = '[super_stripe list=&quot;' . ($k+1) . '&quot; id=&quot;'. $form_id . '&quot;]';
                //                     echo '<input type="text" readonly="readonly" class="super-get-form-shortcodes" value="' . $shortcode. '" />';
                //                     echo '<span class="sfui-label"><i>' . esc_html__('Paste shortcode on any page', 'super-forms' ) . '</i></span>';
                //                 echo '</label>';
                //             echo '</div>';
                //             echo '<div class="sfui-btn sfui-round sfui-tooltip" title="' . esc_html__('Change Settings', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'toggleStripeSettings\')"><i class="fas fa-cogs"></i></div>';
                //             echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add list', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                //             echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_html__('Delete Stripe', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                //         echo '</div>';

                //         echo '<div class="sfui-setting-group">';
                //             // Hide listing to specific user role/ids
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="display.enabled" value="true"' . ($v['display']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Only display this listing to the following users', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="display.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to display to all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="display.user_roles" value="' . sanitize_text_field($v['display']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only display to the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="display.user_ids" value="' . sanitize_text_field($v['display']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-title">' . esc_html__( 'HTML/message to display to users that can not see the listing (leave blank for none)', 'super-forms' ) . '</span>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'This message will be displayed if the listing is not visible to the user', 'super-forms' ) . '</span>';
                //                                 echo '<textarea name="display.message">' . $v['display']['message'] . '</textarea>';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';
                //             // Display based on
                //             echo '<form class="sfui-setting">';
                //                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                         echo '<input type="radio" name="retrieve" value="this_form"' . ($v['retrieve']==='this_form' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Only retrieve entries based on this form', 'super-forms' ) . '</span>';
                //                     echo '</label>';
                //                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                         echo '<input type="radio" name="retrieve" value="all_forms"' . ($v['retrieve']==='all_forms' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Retrieve entries based on all forms', 'super-forms' ) . '</span>';
                //                     echo '</label>';
                //                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                         echo '<input type="radio" name="retrieve" value="specific_forms"' . ($v['retrieve']==='specific_forms' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Retrieve entries based on the following form ID\'s', 'super-forms' ) . ':</span>';
                //                         echo '<div class="sfui-sub-settings sfui-inline" data-f="retrieve;specific_forms">';
                //                             echo '<div class="sfui-setting sfui-vertical">';
                //                                 echo '<label>';
                //                                     echo '<input type="text" name="form_ids" placeholder="e.g: 123,124" value="' . sanitize_text_field($v['form_ids']) . '" />';
                //                                     echo '<span class="sfui-label">(' . esc_html__( 'seperated by comma\'s', 'super-forms' ) . '</span>';
                //                                 echo '</label>';
                //                             echo '</div>';
                //                         echo '</div>';

                //                     echo '</label>';
                //             echo '</form>';
                //             // Entries within date range
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="date_range.enabled" value="true"' . ($v['date_range']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Only display entries within the following date range', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings sfui-inline" data-f="date_range.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical" style="width:auto;">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'From', 'super-forms' ) . ': <i>(' . esc_html__( 'or leave blank for no minimum date', 'super-forms' ) . ')</i></span>';
                //                                 echo '<input type="date" name="date_range.from" value="' . sanitize_text_field($v['date_range']['from']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical" style="width:auto;">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'Until', 'super-forms' ) . ': <i>(' . esc_html__( 'or leave blank for no maximum date', 'super-forms' ) . ')</i></span>';
                //                                 echo '<input type="date" name="date_range.until" value="' . sanitize_text_field($v['date_range']['until']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';
                //             // No entries based on filter
                //             echo '<div class="sfui-setting sfui-vertical">';
                //                 echo '<label>';
                //                     echo '<span class="sfui-title">' . esc_html__( 'HTML/message to display when there are no results based on filter (leave blank for none)', 'super-forms' ) . '</span>';
                //                     echo '<span class="sfui-label">' . esc_html__( 'This message will be displayed if there are no results based on the current filter', 'super-forms' ) . '</span>';
                //                     echo '<textarea name="noResultsFilterMessage">' . $v['noResultsFilterMessage'] . '</textarea>';
                //                 echo '</label>';
                //             echo '</div>';
                //             // No entries message
                //             echo '<div class="sfui-setting sfui-vertical">';
                //                 echo '<label>';
                //                     echo '<span class="sfui-title">' . esc_html__( 'HTML/message to display when there are no results (leave blank for none)', 'super-forms' ) . '</span>';
                //                     echo '<span class="sfui-label">' . esc_html__( 'This message will only be displayed if absolutely zero results are available for the current user.', 'super-forms' ) . '</span>';
                //                     echo '<textarea name="noResultsMessage">' . $v['noResultsMessage'] . '</textarea>';
                //                 echo '</label>';
                //                 echo '<div class="sfui-sub-settings sfui-active">';
                //                     echo '<div class="sfui-setting sfui-inline">';
                //                         echo '<label>';
                //                             echo '<input type="checkbox" name="onlyDisplayMessage" value="true"' . ($v['onlyDisplayMessage']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Also hide filters, pagination and other possible UI elements (only the message will be shown to the user)', 'super-forms' ) . '</span>';
                //                         echo '</label>';
                //                     echo '</div>';
                //                 echo '</div>';
                //             echo '</div>';

                //             // Which users can see all entries?
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="see_any.enabled" value="true"' . ($v['see_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to see all entries (note that logged in users will always be able to see their own entries)', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="see_any.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="see_any.user_roles" value="' . sanitize_text_field($v['see_any']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="see_any.user_ids" value="' . sanitize_text_field($v['see_any']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';

                //             // Allow viewing any entries
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="view_any.enabled" value="true"' . ($v['view_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to view any entries', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="view_any.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="view_any.user_roles" value="' . sanitize_text_field($v['view_any']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="view_any.user_ids" value="' . sanitize_text_field($v['view_any']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'View template HTML', 'super-forms' ) . ' <i>(you can use custom HTML to create your own view, leave blank to use default template' . esc_html__( '', 'super-forms') .')</i></span>';
                //                                 echo '<textarea name="view_any.html_template">' . $v['view_any']['html_template'] . '</textarea>';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'Loop fields HTML', 'super-forms' ) . ' <i>(if you use {loop_fields} inside your custom template, you can define the "row" here and retrieve the field values with {loop_label} and {loop_value} tags, leave blank to use the default loop HTML' . esc_html__( '', 'super-forms') .')</i></span>';
                //                                 echo '<textarea name="view_any.loop_html">' . $v['view_any']['loop_html'] . '</textarea>';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';
                //             // Allow viewing own entries
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="view_own.enabled" value="true"' . ($v['view_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to view their own entries', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="view_own.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="view_own.user_roles" value="' . sanitize_text_field($v['view_own']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="view_own.user_ids" value="' . sanitize_text_field($v['view_own']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'View template HTML', 'super-forms' ) . ' <i>(you can use custom HTML to create your own view, leave blank to use default template' . esc_html__( '', 'super-forms') .')</i></span>';
                //                                 echo '<textarea name="view_own.html_template">' . $v['view_own']['html_template'] . '</textarea>';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'Loop fields HTML', 'super-forms' ) . ' <i>(if you use {loop_fiels} inside your custom template, you can define the "row" here and retrieve the field values with {loop_label} and {loop_value} tags, leave blank to use the default loop HTML' . esc_html__( '', 'super-forms') .')</i></span>';
                //                                 echo '<textarea name="view_own.loop_html">' . $v['view_own']['loop_html'] . '</textarea>';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';

                //             // Allow editing any entries
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="edit_any.enabled" value="true"' . ($v['edit_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to edit any entries', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="edit_any.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="edit_any.user_roles" value="' . sanitize_text_field($v['edit_any']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="edit_any.user_ids" value="' . sanitize_text_field($v['edit_any']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';
                //             // Allow editing own entries
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="edit_own.enabled" value="true"' . ($v['edit_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to edit their own entries', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="edit_own.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="edit_own.user_roles" value="' . sanitize_text_field($v['edit_own']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="edit_own.user_ids" value="' . sanitize_text_field($v['edit_own']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';
                //             // Allow deleting any entries
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="delete_any.enabled" value="true"' . ($v['delete_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to delete any entries', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings sfui-vertical" data-f="delete_any.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="delete_any.user_roles" value="' . sanitize_text_field($v['delete_any']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="delete_any.user_ids" value="' . sanitize_text_field($v['delete_any']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting">';
                //                             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                                 echo '<input type="checkbox" name="delete_any.permanent" value="true"' . ($v['delete_any']['permanent']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Bypass Trash and force delete (permanently deletes the entry)', 'super-forms' ) . ':</span>';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';
                //             // Allow delete own entries
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="delete_own.enabled" value="true"' . ($v['delete_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to delete their own entries', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="delete_own.enabled;true">';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="delete_own.user_roles" value="' . sanitize_text_field($v['delete_own']['user_roles']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting sfui-vertical">';
                //                             echo '<label>';
                //                                 echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                //                                 echo '<input type="text" name="delete_own.user_ids" value="' . sanitize_text_field($v['delete_own']['user_ids']) . '" />';
                //                             echo '</label>';
                //                         echo '</div>';
                //                         echo '<div class="sfui-setting">';
                //                             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                                 echo '<input type="checkbox" name="delete_own.permanent" value="true"' . ($v['delete_own']['permanent']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Bypass Trash and force delete (permanently deletes the entry)', 'super-forms' ) . ':</span>';
                //                             echo '</label>';
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';

                //             $standardColumns = self::getStandardColumns();
                //             foreach($standardColumns as $sk => $sv){
                //                 echo '<div class="sfui-setting">';
                //                     echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                         echo '<input type="checkbox" name="'.$sk.'_column.enabled" value="true"' . ($v[$sk.'_column']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Show "'.$sv['name'].'" column', 'super-forms' ) . ':</span>';
                //                         echo '<div class="sfui-sub-settings sfui-inline" data-f="'.$sk.'_column.enabled;true">';
                //                             self::getColumnSettingFields($v, $sk.'_column', $sk, $sv);
                //                         echo '</div>';
                //                     echo '</label>';
                //                 echo '</div>';
                //             }
                //             // Custom columns
                //             echo '<div class="sfui-setting">';
                //                 echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                //                     echo '<input type="checkbox" name="custom_columns.enabled" value="true"' . ($v['custom_columns']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Show the following "Custom" columns', 'super-forms' ) . ':</span>';
                //                     echo '<div class="sfui-sub-settings" data-f="custom_columns.enabled;true">';
                //                         echo '<div class="sfui-repeater" data-k="custom_columns.columns">';
                //                             // Repeater Item
                //                             $columns = $v['custom_columns']['columns'];
                //                             foreach( $columns as $ck => $cv ) {
                //                                 echo '<div class="sfui-repeater-item">';
                //                                     echo '<div class="sfui-inline sfui-vertical">';
                //                                         self::getColumnSettingFields($v, '', $ck, $cv);
                //                                         echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Add item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                //                                         echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                //                                     echo '</div>';
                //                                 echo '</div>';
                //                             }
                //                         echo '</div>';
                //                     echo '</div>';
                //                 echo '</label>';
                //             echo '</div>';
                //         echo '</div>';
                //     echo '</div>';
                // }
                //echo '</div>';

                echo '</div>';
            echo '</div>';
        }
        public static function getColumnSettingFields($v, $pre, $key, $value){
            if(!empty($pre)){
                $customColumn = false;
                $v = $v[$pre];
                $pre = $pre.'.';
            }else{
                $customColumn = true;
                $v = $value;
            }
            echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                echo '<label>';
                    echo '<span class="sfui-label">' . esc_html__( 'Column name', 'super-forms' ) . '</span>';
                    echo '<input type="text" name="'.$pre.'name" value="' . sanitize_text_field($v['name']) . '" />';
                echo '</label>';
            echo '</div>';
            if($customColumn){
                echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                    echo '<label>';
                        echo '<span class="sfui-label">' . esc_html__( 'Field name', 'super-forms' ) . ':</span>';
                        echo '<input type="text" name="field_name" value="' . sanitize_text_field($v['field_name']) . '" />';
                        echo '<span class="sfui-label"><i>(' . esc_html__( 'enter the field name', 'super-forms') .')</i></span>';
                    echo '</label>';
                echo '</div>';
            }
            echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                echo '<span class="sfui-label">' . esc_html__( 'Allow sorting', 'super-forms' ) . '</span>';
                echo '<label>';
                    echo '<div class="sfui-inline">';
                        echo '<input type="checkbox" name="'.$pre.'sort" value="true"' . ($v['sort']==='true' ? ' checked="checked"' : '') . ' />';
                        echo '<span class="sfui-label">' . esc_html__( 'Yes', 'super-forms' ) . '</span>';
                    echo '</div>';
                echo '</label>';
            echo '</div>';
            echo '<div class="sfui-setting sfui-vertical">';
                echo '<label>';
                    echo '<span class="sfui-label">';
                        echo esc_html__( 'Link', 'super-forms' ) . ':';
                    echo '</span>';
                    echo '<select name="'.$pre.'link.type" onChange="SUPER.ui.updateSettings(event, this)">';
                        echo '<option ' . ($v['link']['type']=='none' ? ' selected="selected"' : '') . ' value="none">' . esc_html__( 'None', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='contact_entry' ? ' selected="selected"' : '') . ' value="contact_entry">' . esc_html__( 'Edit the contact entry (backend)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='wc_order_backend' ? ' selected="selected"' : '') . ' value="wc_order_backend">' . esc_html__( 'WooCommerce order (backend)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='wc_order_frontend' ? ' selected="selected"' : '') . ' value="wc_order_frontend">' . esc_html__( 'WooCommerce order (front-end)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='paypal_order' ? ' selected="selected"' : '') . ' value="paypal_order">' . esc_html__( 'PayPal order (backend)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='paypal_subscription' ? ' selected="selected"' : '') . ' value="paypal_subscription">' . esc_html__( 'PayPal subscription (backend)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='generated_pdf' ? ' selected="selected"' : '') . ' value="generated_pdf">' . esc_html__( 'Generated PDF file', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='post_backend' ? ' selected="selected"' : '') . ' value="post_backend">' . esc_html__( 'Created post/page (backend)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='post_frontend' ? ' selected="selected"' : '') . ' value="post_frontend">' . esc_html__( 'Created post/page (front-end)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='author_posts' ? ' selected="selected"' : '') . ' value="author_posts">' . esc_html__( 'The author page (front-end)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='author_edit' ? ' selected="selected"' : '') . ' value="author_edit">' . esc_html__( 'The author profile (backend)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='author_email' ? ' selected="selected"' : '') . ' value="author_email">' . esc_html__( 'Author E-mail address (mailto:)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='mailto' ? ' selected="selected"' : '') . ' value="mailto">' . esc_html__( 'E-mail address (mailto:)', 'super-forms' ) . '</option>';
                        echo '<option ' . ($v['link']['type']=='custom' ? ' selected="selected"' : '') . ' value="custom">' . esc_html__( 'Custom URL', 'super-forms' ) . '</option>';
                    echo '</select>';
                echo '</label>';
                echo '<div class="sfui-sub-settings" data-f="'.$pre.'link.type;custom">';
                    echo '<div class="sfui-vertical">';
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                echo '<span class="sfui-label">' . esc_html__( 'Enter custom URL (use {tags} if needed)', 'super-forms' ) . ':</span>';
                                echo '<input type="text" name="'.$pre.'link.url" value="' . $v['link']['url'] . '" />';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
            echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                echo '<label>';
                    echo '<span class="sfui-label">' . esc_html__( 'Width (px)', 'super-forms' ) . '</span>';
                    echo '<input type="number" name="'.$pre.'width" value="' . sanitize_text_field($v['width']) . '" />';
                echo '</label>';
            echo '</div>';
            echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                echo '<label>';
                    echo '<span class="sfui-label">' . esc_html__( 'Order', 'super-forms' ) . '</span>';
                    echo '<input type="number" name="'.$pre.'order" value="' . sanitize_text_field($v['order']) . '" />';
                echo '</label>';
            echo '</div>';
            echo '<div class="sfui-setting sfui-vertical" style="flex:2;">';
                echo '<span class="sfui-label">' . esc_html__( 'Allow filter', 'super-forms' ) . '</span>';
                echo '<label>';
                    echo '<div class="sfui-inline">';
                        echo '<input type="checkbox" name="'.$pre.'filter.enabled" value="true"' . ($v['filter']['enabled']==='true' ? ' checked="checked"' : '') . ' />';
                        echo '<span class="sfui-label">' . esc_html__( 'Yes', 'super-forms' ) . '</span>';
                    echo '</div>';
                echo '</label>';
                if($key!=='entry_date'){
                    echo '<div class="sfui-sub-settings sfui-vertical" data-f="'.$pre.'filter.enabled;true">';
                        echo '<label>';
                            echo '<span class="sfui-label">' . esc_html__( 'Filter placeholder', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="'.$pre.'filter.placeholder" value="' . sanitize_text_field($v['filter']['placeholder']) . '" />';
                        echo '</label>';
                        if($customColumn){
                            echo '<label>';
                                echo '<span class="sfui-label">'.esc_html__( 'Filter method', 'super-forms' ) . ':</span>';
                                echo '<select name="'.$pre.'filter.type" onChange="SUPER.ui.updateSettings(event, this)">';
                                    echo '<option '.($v['filter']['type']=='text' ? ' selected="selected"' : '').' value="text">'.esc_html__( 'Text field (default)', 'super-forms' ).'</option>';
                                    echo '<option '.($v['filter']['type']=='dropdown' ? ' selected="selected"' : '').' value="dropdown">'.esc_html__( 'Dropdown', 'super-forms' ).'</option>';
                                echo '</select>';
                            echo '</label>';
                            echo '<div class="sfui-sub-settings" data-f="'.$pre.'filter.type;dropdown">';
                                echo '<div class="sfui-setting sfui-vertical">';
                                    echo '<label>';
                                        echo '<span class="sfui-label">' . esc_html__( 'Filter options', 'super-forms' ) . ' <i>(' . esc_html__( 'put each on a new line', 'super-forms') .')</i>:</span>';
                                        echo '<textarea name="'.$pre.'filter.items" placeholder="' . esc_attr__( "option_value1|Option Label 1\noption_value2|Option Label 2", 'super-forms') . '">' . $v['filter']['items'] . '</textarea>';
                                    echo '</label>';
                                echo '</div>';
                            echo '</div>';
                        }
                    echo '</div>';
                }
            echo '</div>';
        }

        // Get default listing settings
        public static function get_default_stripe_settings($list) {
            if(empty($list['enabled'])) $list['enabled'] = 'false';
            // if(empty($list['name'])) $list['name'] = 'Stripe #1';
            // // Display
            // if( empty($list['display']) ) $list['display'] = array(
            //     'enabled'=>'true',
            //     'user_roles'=>'administrator',
            //     'user_ids'=>'',
            //     'message'=>"<div class=\"super-msg super-info\">\n    <h1>" . esc_html__( "You do not have permission to view this listing", "super-forms" ) . "</h1>\n</div>"
            // );
            // if(!isset($list['retrieve'])) $list['retrieve'] = 'this_form';
            // if(!isset($list['form_ids'])) $list['form_ids'] = '';
            // if(!isset($list['noResultsFilterMessage'])) $list['noResultsFilterMessage'] = "<div class=\"super-msg super-info\">\n    <h1>" . esc_html__( "No results found based on your filter", "super-forms" ) . "</h1>\n    Clear your filters or try a different filter.\n</div>";
            // if(!isset($list['noResultsMessage'])) $list['noResultsMessage'] = "<div class=\"super-msg super-info\">\n    <h1>" . esc_html__( "No results found", "super-forms" ) . "</h1>\n</div>";
            // if(!isset($list['onlyDisplayMessage'])) $list['onlyDisplayMessage'] = 'true';
            // if(empty($list['date_range'])) $list['date_range'] = array(
            //     'enabled'=>'false',
            //     'from'=>'',
            //     'until'=>''
            // );
            // if(empty($list['title_column'])) $list['title_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Title', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['entry_status_column'])) $list['entry_status_column'] = array(
            //     'enabled'=>'true',
            //     'name'=>esc_html__( 'Entry status', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( '- choose status -', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['entry_date_column'])) $list['entry_date_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Date', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 30,
            //     'width' => 290
            // );
            // if(empty($list['generated_pdf_column'])) $list['generated_pdf_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'PDF File', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );

            // if(empty($list['wc_order_column'])) $list['wc_order_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'WC Order', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 100
            // );
            // if(empty($list['wc_order_status_column'])) $list['wc_order_status_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'WC Order Status', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 140
            // );
            // if(empty($list['paypal_order_column'])) $list['paypal_order_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Paypal Order', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 160
            // );
            // if(empty($list['paypal_order_status_column'])) $list['paypal_order_status_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Paypal Order Status', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 160
            // );
            // if(empty($list['paypal_subscription_column'])) $list['paypal_subscription_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Subscription', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 160
            // );
            // if(empty($list['paypal_subscription_status_column'])) $list['paypal_subscription_status_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Subscription Status', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( '- select -', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 200
            // );
            // if(empty($list['wp_post_title_column'])) $list['wp_post_title_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Post Title', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['wp_post_status_column'])) $list['wp_post_status_column'] = array(
            //     'enabled' => 'true',
            //     'name' => esc_html__( 'Post Status', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( '- select -', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 110
            // );

            // if(empty($list['author_username_column'])) $list['author_username_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'Username', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['author_firstname_column'])) $list['author_firstname_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'First Name', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['author_lastname_column'])) $list['author_lastname_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'Last Name', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['author_fullname_column'])) $list['author_fullname_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'Full Name', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['author_nickname_column'])) $list['author_nickname_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'Nickname', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['author_display_column'])) $list['author_display_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'Display Name', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 150
            // );
            // if(empty($list['author_email_column'])) $list['author_email_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'E-mail', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 250
            // );
            // if(empty($list['author_id_column'])) $list['author_id_column'] = array(
            //     'enabled' => 'false',
            //     'name' => esc_html__( 'Author ID', 'super-forms' ),
            //     'filter' => array(
            //         'enabled' => 'true',
            //         'type' => 'text',
            //         'placeholder' => esc_html__( 'search...', 'super-forms' )
            //     ),
            //     'sort' => 'true',
            //     'link' => array(
            //         'type' => 'none',
            //         'url' => ''
            //     ),
            //     'order' => 10,
            //     'width' => 100
            // );
            // if(empty($list['custom_columns']) ) $list['custom_columns'] = array(
            //     'enabled' => 'true',
            //     'columns' => array(
            //         array(
            //             'name' => esc_html__( 'First Name', 'super-forms' ),
            //             'field_name' => 'first_name',
            //             'filter' => array(
            //                 'enabled' => 'true',
            //                 'type' => 'text', // text, dropdown
            //                 'items' => '',
            //                 'placeholder' => esc_html__( 'search...', 'super-forms' )
            //             ),
            //             'sort' => 'true',
            //             'link' => array(
            //                 'type' => 'none',
            //                 'url' => ''
            //             ),
            //             'width' => 150,
            //             'order' => 10
            //         ),
            //         array(
            //             'name' => esc_html__( 'Last Name', 'super-forms' ),
            //             'field_name' => 'last_name',
            //             'filter' => array(
            //                 'enabled' => 'true',
            //                 'type' => 'text', // text, dropdown
            //                 'items' => '',
            //                 'placeholder' => esc_html__( 'search...', 'super-forms' )
            //             ),
            //             'sort' => 'true',
            //             'link' => array(
            //                 'type' => 'none',
            //                 'url' => ''
            //             ),
            //             'width' => 150,
            //             'order' => 10
            //         ),
            //         array(
            //             'name' => esc_html__( 'E-mail', 'super-forms' ),
            //             'field_name' => 'email',
            //             'filter' => array(
            //                 'enabled' => 'true',
            //                 'type' => 'text', // text, dropdown
            //                 'items' => '',
            //                 'placeholder' => esc_html__( 'search...', 'super-forms' )
            //             ),
            //             'sort' => 'true',
            //             'link' => array(
            //                 'type' => 'none',
            //                 'url' => ''
            //             ),
            //             'width' => 150,
            //             'order' => 10
            //         ),
            //     ),
            // );
            // // See any permissions
            // if( empty($list['see_any']) ) $list['see_any'] = array(
            //     'enabled'=>'true',
            //     'user_roles'=>'administrator',
            //     'user_ids'=>''
            // );
            // // View permissions
            // $html_template = "<div class=\"super-listing-entry-details\">
//     <div class=\"super-listing-row super-title\">
//         <div class=\"super-listing-row-label\">" . esc_html__( "Entry title", "super-forms" ) . "</div>
//         <div class=\"super-listing-row-value\">{listing_entry_title}</div>
//     </div>\n
//     <div class=\"super-listing-row super-date\">
//         <div class=\"super-listing-row-label\">" . esc_html__( "Entry date", "super-forms" ) . "</div>
//         <div class=\"super-listing-row-value\">{listing_entry_date}</div>
//     </div>\n
//     <div class=\"super-listing-row super-entry-id\">
//         <div class=\"super-listing-row-label\">" . esc_html__( "Entry ID", "super-forms" ) . "</div>
//         <div class=\"super-listing-row-value\">{listing_entry_id}</div>
//     </div>
// </div>
// <div class=\"super-listing-entry-data\">
//     {loop_fields}
// </div>";
//             $loop_html = "<div class=\"super-listing-row\">
//     <div class=\"super-listing-row-label\">{loop_label}</div>
//     <div class=\"super-listing-row-value\">{loop_value}</div>
// </div>";
            // if( empty($list['view_any']) ) $list['view_any'] = array(
            //     'enabled'=>'true',
            //     'method'=>'modal',
            //     'user_roles'=>'administrator',
            //     'user_ids'=>'',
            //     'html_template' => $html_template,
            //     'loop_html' => $loop_html
            // );
            // if( empty($list['view_own']) ) $list['view_own'] = array(
            //     'enabled'=>'false',
            //     'method'=>'modal',
            //     'user_roles'=>'',
            //     'user_ids'=>'',
            //     'html_template' => $html_template,
            //     'loop_html' => $loop_html
            // );
            // // Edit permissions
            // if( empty($list['edit_any']) ) $list['edit_any'] = array(
            //     'enabled'=>'true',
            //     'method'=>'modal',
            //     'user_roles'=>'administrator',
            //     'user_ids'=>''
            // );
            // if( empty($list['edit_own']) ) $list['edit_own'] = array(
            //     'enabled'=>'false',
            //     'method'=>'modal',
            //     'user_roles'=>'',
            //     'user_ids'=>''
            // );
            // // Delete permissions
            // if( empty($list['delete_any']) ) $list['delete_any'] = array(
            //     'enabled'=>'true',
            //     'user_roles'=>'administrator',
            //     'user_ids'=>'',
            //     'permanent'=>'false'
            // );
            // if( empty($list['delete_own']) ) $list['delete_own'] = array(
            //     'enabled'=>'false',
            //     'user_roles'=>'',
            //     'user_ids'=>'',
            //     'permanent'=>'false'
            // );
            // if( empty($list['pagination']) ) $list['pagination'] = 'page';
            // if( empty($list['limit']) ) $list['limit'] = 25;

            // $list = apply_filters( 'super_stripe_default_settings_filter', $list );
            return $list;
        }

    }
endif;

/**
 * Returns the main instance of SUPER_Stripe to prevent the need to use globals.
 *
 * @return SUPER_Stripe
 */
if(!function_exists('SUPER_Stripe')){
    function SUPER_Stripe() {
        return SUPER_Stripe::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Stripe'] = SUPER_Stripe();
}
