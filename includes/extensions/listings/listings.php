<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Listings')) :


    /**
     * Main SUPER_Listings Class
     *
     * @class SUPER_Listings
     * @version 1.0.0
     */
    final class SUPER_Listings {
    
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'listings';

        
        /**
         * @var SUPER_Listings The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Listings Instance
         *
         * Ensures only one instance of SUPER_Listings is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Listings()
         * @return SUPER_Listings - Main instance
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
         * SUPER_Listings Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_listings_loaded');
        }

        
        /**
         * Hook into actions and filters
         *
         *  @since      1.0.0
        */
        private function init_hooks() {
            add_action( 'init', array( $this, 'register_shortcodes' ) );
            if ( SUPER_Forms()->is_request( 'admin' ) ) {
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 10, 1 );
                add_action( 'super_create_form_listings_tab', array( $this, 'add_tab_content' ) );
                add_filter( 'super_enqueue_styles', array( $this, 'add_style' ), 10, 1 );
                add_filter( 'super_enqueue_scripts', array( $this, 'add_script' ), 10, 1 );
            }
            add_action( 'wp_ajax_super_load_form_inside_modal', array( $this, 'load_form_inside_modal' ) );
            add_action( 'wp_ajax_nopriv_super_load_form_inside_modal', array( $this, 'load_form_inside_modal' ) );
            add_filter( 'super_before_form_render_settings_filter', array( $this, 'alter_form_settings_before_rendering' ), 10, 2 );
            add_filter( 'super_before_submit_form_settings_filter', array( $this, 'alter_form_settings_before_submit' ), 10, 2 );
        }

        // Required to change some settings when editing/updating an existing entry via Listings Add-on
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

        // Required to change some settings when editing/updating an existing entry via Listings Add-on
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
            $assets_path = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            $styles['super-listings'] = array(
                'src'     => $assets_path . 'css/backend/styles.css',
                'deps'    => '',
                'version' => SUPER_VERSION,
                'media'   => 'all',
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method'  => 'enqueue',
            );
            return $styles;
        }
        public static function add_script($scripts){
            $assets_path = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            $scripts['super-listings'] = array(
                'src'     => $assets_path . 'js/backend/script.js',
                'deps'    => array( 'super-common' ),
                'version' => SUPER_VERSION,
                'footer'  => true,
                'screen'  => array(
                    'super-forms_page_super_create_form'
                ),
                'method'  => 'enqueue',
            );
            return $scripts;
        }
        public static function add_tab($tabs){
            $tabs['listings'] = esc_html__( 'Listings', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            $slug = SUPER_Listings()->add_on_slug;
            $form_id = absint($atts['form_id']);
            $lists = array();
            if(isset($atts['settings']) && isset($atts['settings']['_'.$slug])){
                $lists = $atts['settings']['_'.$slug]['lists'];
            }
            if(count($lists)==0) {
                $lists[] = self::get_default_listings_settings(array());
            }
            // Listing general information
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('About', 'super-forms').':</strong> ' . esc_html__( 'Listings allow you to display Contact Entries in a list/table on the front-end. For each form you can have multiple listings with their own settings. You can copy paste the listings shortcode anywhere in your page to display the listing.', 'super-forms' );
            echo '</div>';
            
            // Enable listings
            echo '<div class="sfui-setting">';
                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    echo '<input type="checkbox" name="enabled" value="true"' . (isset($atts['settings']['_listings']) && $atts['settings']['_listings']['enabled']==='true' ? ' checked="checked"' : '') . ' />';
                    echo '<span class="sfui-title">' . esc_html__( 'Enable listings for this form', 'super-forms' ) . '</span>';
                echo '</label>';
                echo '<div class="sfui-sub-settings" data-f="enabled;true">';


            // When enabled, we display the list with listings
            echo '<div class="sfui-repeater" data-k="lists">';
            // Repeater Item
            foreach($lists as $k => $v){
                // Set default values if they don't exist
                $v = self::get_default_listings_settings($v);
                echo '<div class="sfui-repeater-item">';
                    echo '<div class="sfui-inline">';
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                echo '<input type="text" name="name" value="' . $v['name'] . '" />';
                                echo '<span class="sfui-label"><i>' . esc_html__( 'Give this listing a name', 'super-forms' ) . '</i></span>';
                            echo '</label>';
                        echo '</div>';
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                // Get the correct shortcode for this list
                                $shortcode = '['.esc_html__( 'form-not-saved-yet', 'super-forms' ).']';
                                if( $form_id!=0 ) $shortcode = '[super_listings list=&quot;' . ($k+1) . '&quot; id=&quot;'. $form_id . '&quot;]';
                                echo '<input type="text" readonly="readonly" class="super-get-form-shortcodes" value="' . $shortcode. '" />';
                                echo '<span class="sfui-label"><i>' . esc_html__('Paste shortcode on any page', 'super-forms' ) . '</i></span>';
                            echo '</label>';
                        echo '</div>';
                        echo '<div class="sfui-btn sfui-round sfui-tooltip" title="' . esc_html__('Change Settings', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'toggleListingSettings\')"><i class="fas fa-cogs"></i></div>';
                        echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add list', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                        echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_html__('Delete Listing', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                    echo '</div>';

                    echo '<div class="sfui-setting-group">';
                        // Hide listing to specific user role/ids
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="display.enabled" value="true"' . ($v['display']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Only display this listing to the following users', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="display.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to display to all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="display.user_roles" value="' . sanitize_text_field($v['display']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only display to the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="display.user_ids" value="' . sanitize_text_field($v['display']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-title">' . esc_html__( 'HTML/message to display to users that can not see the listing (leave blank for none)', 'super-forms' ) . '</span>';
                                            echo '<span class="sfui-label">' . esc_html__( 'This message will be displayed if the listing is not visible to the user', 'super-forms' ) . '</span>';
                                            echo '<textarea name="display.message">' . $v['display']['message'] . '</textarea>';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Display based on
                        echo '<form class="sfui-setting">';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="retrieve" value="this_form"' . ($v['retrieve']==='this_form' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Only retrieve entries based on this form', 'super-forms' ) . '</span>';
                                echo '</label>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="retrieve" value="all_forms"' . ($v['retrieve']==='all_forms' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Retrieve entries based on all forms', 'super-forms' ) . '</span>';
                                echo '</label>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="retrieve" value="specific_forms"' . ($v['retrieve']==='specific_forms' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Retrieve entries based on the following form ID\'s', 'super-forms' ) . ':</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="retrieve;specific_forms">';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<input type="text" name="form_ids" placeholder="e.g: 123,124" value="' . sanitize_text_field($v['form_ids']) . '" />';
                                                echo '<span class="sfui-label">(' . esc_html__( 'seperated by comma\'s', 'super-forms' ) . '</span>';
                                            echo '</label>';
                                        echo '</div>';
                                    echo '</div>';

                                echo '</label>';
                        echo '</form>';
                        // Entries within date range
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="date_range.enabled" value="true"' . ($v['date_range']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Only display entries within the following date range', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings sfui-inline" data-f="date_range.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical" style="width:auto;">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'From', 'super-forms' ) . ': <i>(' . esc_html__( 'or leave blank for no minimum date', 'super-forms' ) . ')</i></span>';
                                            echo '<input type="date" name="date_range.from" value="' . sanitize_text_field($v['date_range']['from']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical" style="width:auto;">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'Until', 'super-forms' ) . ': <i>(' . esc_html__( 'or leave blank for no maximum date', 'super-forms' ) . ')</i></span>';
                                            echo '<input type="date" name="date_range.until" value="' . sanitize_text_field($v['date_range']['until']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // No entries based on filter
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'HTML/message to display when there are no results based on filter (leave blank for none)', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'This message will be displayed if there are no results based on the current filter', 'super-forms' ) . '</span>';
                                echo '<textarea name="noResultsFilterMessage">' . $v['noResultsFilterMessage'] . '</textarea>';
                            echo '</label>';
                        echo '</div>';
                        // No entries message
                        echo '<div class="sfui-setting sfui-vertical">';
                            echo '<label>';
                                echo '<span class="sfui-title">' . esc_html__( 'HTML/message to display when there are no results (leave blank for none)', 'super-forms' ) . '</span>';
                                echo '<span class="sfui-label">' . esc_html__( 'This message will only be displayed if absolutely zero results are available for the current user.', 'super-forms' ) . '</span>';
                                echo '<textarea name="noResultsMessage">' . $v['noResultsMessage'] . '</textarea>';
                            echo '</label>';
                            echo '<div class="sfui-sub-settings sfui-active">';
                                echo '<div class="sfui-setting sfui-inline">';
                                    echo '<label>';
                                        echo '<input type="checkbox" name="onlyDisplayMessage" value="true"' . ($v['onlyDisplayMessage']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Also hide filters, pagination and other possible UI elements (only the message will be shown to the user)', 'super-forms' ) . '</span>';
                                    echo '</label>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';

                        // Which users can see all entries?
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="see_any.enabled" value="true"' . ($v['see_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to see all entries (note that logged in users will always be able to see their own entries)', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="see_any.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="see_any.user_roles" value="' . sanitize_text_field($v['see_any']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="see_any.user_ids" value="' . sanitize_text_field($v['see_any']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';

                        // Allow viewing any entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="view_any.enabled" value="true"' . ($v['view_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to view any entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="view_any.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="view_any.user_roles" value="' . sanitize_text_field($v['view_any']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="view_any.user_ids" value="' . sanitize_text_field($v['view_any']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'View template HTML', 'super-forms' ) . ' <i>(you can use custom HTML to create your own view, leave blank to use default template' . esc_html__( '', 'super-forms') .')</i></span>';
                                            echo '<textarea name="view_any.html_template">' . $v['view_any']['html_template'] . '</textarea>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'Loop fields HTML', 'super-forms' ) . ' <i>(if you use {loop_fields} inside your custom template, you can define the "row" here and retrieve the field values with {loop_label} and {loop_value} tags, leave blank to use the default loop HTML' . esc_html__( '', 'super-forms') .')</i></span>';
                                            echo '<textarea name="view_any.loop_html">' . $v['view_any']['loop_html'] . '</textarea>';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow viewing own entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="view_own.enabled" value="true"' . ($v['view_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to view their own entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="view_own.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="view_own.user_roles" value="' . sanitize_text_field($v['view_own']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="view_own.user_ids" value="' . sanitize_text_field($v['view_own']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'View template HTML', 'super-forms' ) . ' <i>(you can use custom HTML to create your own view, leave blank to use default template' . esc_html__( '', 'super-forms') .')</i></span>';
                                            echo '<textarea name="view_own.html_template">' . $v['view_own']['html_template'] . '</textarea>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'Loop fields HTML', 'super-forms' ) . ' <i>(if you use {loop_fiels} inside your custom template, you can define the "row" here and retrieve the field values with {loop_label} and {loop_value} tags, leave blank to use the default loop HTML' . esc_html__( '', 'super-forms') .')</i></span>';
                                            echo '<textarea name="view_own.loop_html">' . $v['view_own']['loop_html'] . '</textarea>';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';

                        // Allow editing any entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="edit_any.enabled" value="true"' . ($v['edit_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to edit any entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="edit_any.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_any.user_roles" value="' . sanitize_text_field($v['edit_any']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_any.user_ids" value="' . sanitize_text_field($v['edit_any']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow editing own entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="edit_own.enabled" value="true"' . ($v['edit_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to edit their own entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="edit_own.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_own.user_roles" value="' . sanitize_text_field($v['edit_own']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_own.user_ids" value="' . sanitize_text_field($v['edit_own']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow deleting any entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="delete_any.enabled" value="true"' . ($v['delete_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to delete any entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings sfui-vertical" data-f="delete_any.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_any.user_roles" value="' . sanitize_text_field($v['delete_any']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_any.user_ids" value="' . sanitize_text_field($v['delete_any']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="checkbox" name="delete_any.permanent" value="true"' . ($v['delete_any']['permanent']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Bypass Trash and force delete (permanently deletes the entry)', 'super-forms' ) . ':</span>';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow delete own entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="delete_own.enabled" value="true"' . ($v['delete_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Allow the following users to delete their own entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="delete_own.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_own.user_roles" value="' . sanitize_text_field($v['delete_own']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span class="sfui-label">' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_own.user_ids" value="' . sanitize_text_field($v['delete_own']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="checkbox" name="delete_own.permanent" value="true"' . ($v['delete_own']['permanent']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Bypass Trash and force delete (permanently deletes the entry)', 'super-forms' ) . ':</span>';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';

                        $standardColumns = self::getStandardColumns();
                        foreach($standardColumns as $sk => $sv){
                            echo '<div class="sfui-setting">';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="checkbox" name="'.$sk.'_column.enabled" value="true"' . ($v[$sk.'_column']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Show "'.$sv['name'].'" column', 'super-forms' ) . ':</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="'.$sk.'_column.enabled;true">';
                                        self::getColumnSettingFields($v, $sk.'_column', $sk, $sv);
                                    echo '</div>';
                                echo '</label>';
                            echo '</div>';
                        }
                        // Custom columns
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="custom_columns.enabled" value="true"' . ($v['custom_columns']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Show the following "Custom" columns', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="custom_columns.enabled;true">';
                                    echo '<div class="sfui-repeater" data-k="custom_columns.columns">';
                                        // Repeater Item
                                        $columns = $v['custom_columns']['columns'];
                                        foreach( $columns as $ck => $cv ) {
                                            echo '<div class="sfui-repeater-item">';
                                                echo '<div class="sfui-inline sfui-vertical">';
                                                    self::getColumnSettingFields($v, '', $ck, $cv);
                                                    echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Add item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                                                    echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                                                echo '</div>';
                                            echo '</div>';
                                        }
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            }
            echo '</div>';

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
        public static function get_default_listings_settings($list) {
            if(empty($list['enabled'])) $list['enabled'] = 'false';
            if(empty($list['name'])) $list['name'] = 'Listing #1';
            // Display
            if( empty($list['display']) ) $list['display'] = array(
                'enabled'=>'true',
                'user_roles'=>'administrator',
                'user_ids'=>'',
                'message'=>"<div class=\"super-msg super-info\">\n    <h1>" . esc_html__( "You do not have permission to view this listing", "super-forms" ) . "</h1>\n</div>"
            );
            if(!isset($list['retrieve'])) $list['retrieve'] = 'this_form';
            if(!isset($list['form_ids'])) $list['form_ids'] = '';
            if(!isset($list['noResultsFilterMessage'])) $list['noResultsFilterMessage'] = "<div class=\"super-msg super-info\">\n    <h1>" . esc_html__( "No results found based on your filter", "super-forms" ) . "</h1>\n    Clear your filters or try a different filter.\n</div>";
            if(!isset($list['noResultsMessage'])) $list['noResultsMessage'] = "<div class=\"super-msg super-info\">\n    <h1>" . esc_html__( "No results found", "super-forms" ) . "</h1>\n</div>";
            if(!isset($list['onlyDisplayMessage'])) $list['onlyDisplayMessage'] = 'true';
            if(empty($list['date_range'])) $list['date_range'] = array(
                'enabled'=>'false',
                'from'=>'',
                'until'=>''
            );
            if(empty($list['title_column'])) $list['title_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Title', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['entry_status_column'])) $list['entry_status_column'] = array(
                'enabled'=>'true',
                'name'=>esc_html__( 'Entry status', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( '- choose status -', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['entry_date_column'])) $list['entry_date_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Date', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 30,
                'width' => 290
            );
            if(empty($list['generated_pdf_column'])) $list['generated_pdf_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'PDF File', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );

            if(empty($list['wc_order_column'])) $list['wc_order_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'WC Order', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 100
            );
            if(empty($list['wc_order_status_column'])) $list['wc_order_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'WC Order Status', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 140
            );
            if(empty($list['paypal_order_column'])) $list['paypal_order_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Paypal Order', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 160
            );
            if(empty($list['paypal_order_status_column'])) $list['paypal_order_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Paypal Order Status', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' ),
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 160
            );
            if(empty($list['paypal_subscription_column'])) $list['paypal_subscription_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Subscription', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 160
            );
            if(empty($list['paypal_subscription_status_column'])) $list['paypal_subscription_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Subscription Status', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( '- select -', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 200
            );
            if(empty($list['wp_post_title_column'])) $list['wp_post_title_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Post Title', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['wp_post_status_column'])) $list['wp_post_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Post Status', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( '- select -', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 110
            );

            if(empty($list['author_username_column'])) $list['author_username_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Username', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['author_firstname_column'])) $list['author_firstname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'First Name', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['author_lastname_column'])) $list['author_lastname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Last Name', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['author_fullname_column'])) $list['author_fullname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Full Name', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['author_nickname_column'])) $list['author_nickname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Nickname', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['author_display_column'])) $list['author_display_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Display Name', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['author_email_column'])) $list['author_email_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'E-mail', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 250
            );
            if(empty($list['author_id_column'])) $list['author_id_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Author ID', 'super-forms' ),
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => esc_html__( 'search...', 'super-forms' )
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 100
            );
            if(empty($list['custom_columns']) ) $list['custom_columns'] = array(
                'enabled' => 'true',
                'columns' => array(
                    array(
                        'name' => esc_html__( 'First Name', 'super-forms' ),
                        'field_name' => 'first_name',
                        'filter' => array(
                            'enabled' => 'true',
                            'type' => 'text', // text, dropdown
                            'items' => '',
                            'placeholder' => esc_html__( 'search...', 'super-forms' )
                        ),
                        'sort' => 'true',
                        'link' => array(
                            'type' => 'none',
                            'url' => ''
                        ),
                        'width' => 150,
                        'order' => 10
                    ),
                    array(
                        'name' => esc_html__( 'Last Name', 'super-forms' ),
                        'field_name' => 'last_name',
                        'filter' => array(
                            'enabled' => 'true',
                            'type' => 'text', // text, dropdown
                            'items' => '',
                            'placeholder' => esc_html__( 'search...', 'super-forms' )
                        ),
                        'sort' => 'true',
                        'link' => array(
                            'type' => 'none',
                            'url' => ''
                        ),
                        'width' => 150,
                        'order' => 10
                    ),
                    array(
                        'name' => esc_html__( 'E-mail', 'super-forms' ),
                        'field_name' => 'email',
                        'filter' => array(
                            'enabled' => 'true',
                            'type' => 'text', // text, dropdown
                            'items' => '',
                            'placeholder' => esc_html__( 'search...', 'super-forms' )
                        ),
                        'sort' => 'true',
                        'link' => array(
                            'type' => 'none',
                            'url' => ''
                        ),
                        'width' => 150,
                        'order' => 10
                    ),
                ),
            );
            // See any permissions
            if( empty($list['see_any']) ) $list['see_any'] = array(
                'enabled'=>'true',
                'user_roles'=>'administrator',
                'user_ids'=>''
            );
            // View permissions
            $html_template = "<div class=\"super-listing-entry-details\">
    <div class=\"super-listing-row super-title\">
        <div class=\"super-listing-row-label\">" . esc_html__( "Entry title", "super-forms" ) . "</div>
        <div class=\"super-listing-row-value\">{listing_entry_title}</div>
    </div>\n
    <div class=\"super-listing-row super-date\">
        <div class=\"super-listing-row-label\">" . esc_html__( "Entry date", "super-forms" ) . "</div>
        <div class=\"super-listing-row-value\">{listing_entry_date}</div>
    </div>\n
    <div class=\"super-listing-row super-entry-id\">
        <div class=\"super-listing-row-label\">" . esc_html__( "Entry ID", "super-forms" ) . "</div>
        <div class=\"super-listing-row-value\">{listing_entry_id}</div>
    </div>
</div>
<div class=\"super-listing-entry-data\">
    {loop_fields}
</div>";
            $loop_html = "<div class=\"super-listing-row\">
    <div class=\"super-listing-row-label\">{loop_label}</div>
    <div class=\"super-listing-row-value\">{loop_value}</div>
</div>";
            if( empty($list['view_any']) ) $list['view_any'] = array(
                'enabled'=>'true',
                'method'=>'modal',
                'user_roles'=>'administrator',
                'user_ids'=>'',
                'html_template' => $html_template,
                'loop_html' => $loop_html
            );
            if( empty($list['view_own']) ) $list['view_own'] = array(
                'enabled'=>'false',
                'method'=>'modal',
                'user_roles'=>'',
                'user_ids'=>'',
                'html_template' => $html_template,
                'loop_html' => $loop_html
            );
            // Edit permissions
            if( empty($list['edit_any']) ) $list['edit_any'] = array(
                'enabled'=>'true',
                'method'=>'modal',
                'user_roles'=>'administrator',
                'user_ids'=>''
            );
            if( empty($list['edit_own']) ) $list['edit_own'] = array(
                'enabled'=>'false',
                'method'=>'modal',
                'user_roles'=>'',
                'user_ids'=>''
            );
            // Delete permissions
            if( empty($list['delete_any']) ) $list['delete_any'] = array(
                'enabled'=>'true',
                'user_roles'=>'administrator',
                'user_ids'=>'',
                'permanent'=>'false'
            );
            if( empty($list['delete_own']) ) $list['delete_own'] = array(
                'enabled'=>'false',
                'user_roles'=>'',
                'user_ids'=>'',
                'permanent'=>'false'
            );
            if( empty($list['pagination']) ) $list['pagination'] = 'page';
            if( empty($list['limit']) ) $list['limit'] = 25;

            $list = apply_filters( 'super_listings_default_settings_filter', $list );
            return $list;
        }

        // Return data for script handles.
        public static function register_shortcodes(){
            add_shortcode( 'super_listings', array( 'SUPER_Listings', 'super_listings_func' ) );
        }

        // The form shortcode that will generate the list/table with all Contact Entries
        public static function super_listings_func( $atts ) {
            global $wpdb, $current_user;

            extract( shortcode_atts( array(
                'id' => '', // Retrieve entries from specific form ID
                'list' => '' // Determine what list settings to use
            ), $atts ) );

            if(!empty($_POST['action']) && ($_POST['action']==='elementor_ajax') && is_admin()){
                return '<p style="color:red;font-size:12px;"><strong>' . esc_html__('Note', 'super-forms' ).':</strong> ' . esc_html__('Super Forms Listings will only be generated on the front-end', 'super-forms' ) . ' - <code>' . sprintf('[super_listings list="%d" id="%d"]', $list, $id) . '</code></p>';
            }

            // Sanitize the ID
            $form_id = absint($id);
            $post_status = get_post_status($form_id);
            $post_type = get_post_type($form_id);
            $found = false;
            if($post_status==='publish' && $post_type==='super_form'){
                $found = true;
            }
            if($found===false){ // Form does not exists
                return '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with Form ID: %d', 'super-forms' ), $form_id);
            }

            $settings = SUPER_Common::get_form_settings($form_id);
            
            // Load styles and scripts
            SUPER_Forms()->enqueue_element_styles();
            SUPER_Forms()->enqueue_element_scripts($settings, false, $form_id);

            // Enqueue scripts and styles
            $handle = 'super-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common.js', array( 'jquery' ), SUPER_VERSION, false );  

            // WPML langauge parameter to ajax URL's required for for instance when redirecting to WooCommerce checkout/cart page
            $ajax_url = SUPER_Forms()->ajax_url();
            $my_current_lang = apply_filters( 'wpml_current_language', NULL ); 
            if ( $my_current_lang ) $ajax_url = add_query_arg( 'lang', $my_current_lang, $ajax_url );

            wp_localize_script(
                $handle,
                $name,
                array( 
                    'ajaxurl'=>$ajax_url,
                    'preload'=>$settings['form_preload'],
                    'duration'=>$settings['form_duration'],
                    'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                    'loadingOverlay'=>SUPER_Forms()->common_i18n['loadingOverlay'],
                    'loading'=>SUPER_Forms()->common_i18n['loading'],
                    'tab_index_exclusion' => SUPER_Forms()->common_i18n['tab_index_exclusion'],
                    'directions'=>SUPER_Forms()->common_i18n['directions'],
                    'errors'=>SUPER_Forms()->common_i18n['errors'],
                    // @since 3.6.0 - google tracking
                    'ga_tracking' => ( !isset( $settings['form_ga_tracking'] ) ? "" : $settings['form_ga_tracking'] ),
                    'super_int_phone_utils' => SUPER_PLUGIN_FILE . 'assets/js/frontend/int-phone-utils.js'
                )
            );
            wp_enqueue_script( $handle );

            // Enqueue scripts and styles
            $handle = 'super-listings';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'assets/js/frontend/script.js', array( 'super-common' ), SUPER_VERSION, false );  
            wp_localize_script(
                $handle,
                $name,
                array( 
                    'get_home_url' => get_home_url(),
                    'ajaxurl' => $ajax_url
                )
            );
            wp_enqueue_script( $handle );
            wp_enqueue_style( 'super-listings', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/styles.css', array(), SUPER_VERSION );
            SUPER_Forms()->enqueue_fontawesome_styles();

            // Get the settings for this specific list based on it's index
            $list_id = absint($atts['list'])-1;
            if(!isset($settings['_listings'])){
                // The list does not exist
                $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with ID: %d', 'super-forms' ), $list_id);
                return $result;
            }
            $lists = $settings['_listings']['lists'];
            if(!isset($lists[$list_id])){
                // The list does not exist
                $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with ID: %d', 'super-forms' ), $list_id);
                return $result;
            }
            // Set default values if they don't exist
            $list = self::get_default_listings_settings($lists[$list_id]);
            $allow = self::get_action_permissions(array('list'=>$list));
            $allowDisplay = $allow['allowDisplay'];
            if($allowDisplay===false){
                return do_shortcode($list['display']['message']);
            }

            $allowViewAny = $allow['allowViewAny'];
            $allowViewOwn = $allow['allowViewOwn'];
            $allowEditAny = $allow['allowEditAny'];
            $allowEditOwn = $allow['allowEditOwn'];
            $allowDeleteAny = $allow['allowDeleteAny'];
            $allowDeleteOwn = $allow['allowDeleteOwn'];

            $columns = array(); 
            $standardColumns = self::getStandardColumns();
            foreach($standardColumns as $sk => $sv){
                if( $list[$sk.'_column']['enabled']==='true' ) {
                    $columns[$sv['meta_key']] = array(
                        'order' => absint($list[$sk.'_column']['order']),
                        'name' => $list[$sk.'_column']['name'],
                        'width' => absint($list[$sk.'_column']['width']),
                        'filter' => $list[$sk.'_column']['filter'],
                        'sort' => $list[$sk.'_column']['sort'],
                        'link' => array(
                            'type' => 'none',
                            'url' => ''
                        )
                    );
                    // If link available
                    if(isset($list[$sk.'_column']['link'])){
                        $columns[$sv['meta_key']]['link'] = $list[$sk.'_column']['link'];
                    }
                    if(class_exists('SUPER_PayPal')) {
                        if($sk=='paypal_order_status'){
                            $items = array();
                            $paypal_payment_statuses = SUPER_PayPal::$paypal_payment_statuses;
                            foreach($paypal_payment_statuses as $pk => $pv){
                                $items[$pv['label']] = $pv['label'];
                            }
                            $columns[$sv['meta_key']]['filter']['type'] = 'dropdown';
                            $columns[$sv['meta_key']]['filter']['items'] = $items;
                        }
                        if($sk=='paypal_subscription_status'){
                            $items = array(
                                'Active' => 'Active',
                                'Suspended' => 'Suspended',
                                'Canceled' => 'Canceled'
                            );
                            $columns[$sv['meta_key']]['filter']['type'] = 'dropdown';
                            $columns[$sv['meta_key']]['filter']['items'] = $items;
                        }
                    }
                    if($sk=='wc_order_status'){
                        if (function_exists('wc_get_order_statuses')) {
                            $wc_order_statuses = wc_get_order_statuses();
                            //$items = array_merge($items, $wc_order_statuses);      
                            $items = $wc_order_statuses;      
                            $columns[$sv['meta_key']]['filter']['type'] = 'dropdown';
                            $columns[$sv['meta_key']]['filter']['items'] = $items;
                        }
                    }
                    if($sk=='entry_status'){
                        $items = array();
                        foreach(SUPER_Settings::get_entry_statuses() as $k => $v){
                            $items[$k] = $v['name']; 
                        }
                        unset($items['']);
                        $columns[$sv['meta_key']]['filter']['type'] = 'dropdown';
                        $columns[$sv['meta_key']]['filter']['items'] = $items;
                    }
                    if($sk=='wp_post_status'){
                        $items = get_post_statuses();
                        $columns[$sv['meta_key']]['filter']['type'] = 'dropdown';
                        $columns[$sv['meta_key']]['filter']['items'] = $items;
                    }
                }
            }

            // Add custom columns if enabled
            if($list['custom_columns']['enabled']==='true'){
                $columns = array_merge($columns, $list['custom_columns']['columns']);      
            }

            // Now re-order all columns based on order number
            array_multisort(array_column($columns, 'order'), SORT_ASC, $columns);

            // Filters by user
            $hasFilters = false;
            $filters = '';

            $limit = absint($list['limit']);
            // Check if custom limit was choosen by the user
            if( isset($_GET['limit']) ) {
                $limit = absint($_GET['limit']);
            }

            // Check if we need to filter on a column
            $filterColumns = array();
            foreach($_GET as $gk => $gv){
                if(substr($gk, 0, 3)!=='fc_') continue;
                // is a filter column
                $filterColumns[sanitize_text_field(substr($gk, 3, strlen($gk)))] = sanitize_text_field($gv);
            }
            
            // Filter by entry data
            $having = '';
            $filter_by_entry_data = "";
            // Now first check if this is a custom column
            // Custom column always starts with underscore
            foreach($filterColumns as $fck => $fcv){
                if($fck[0]==='_'){ // starts with underscore, which means this is custom column
                    $fck = substr($fck, 1);
                    // If so, it means that we need to filter the contact entry data
                    if($list['custom_columns']['enabled']==='true'){
                        $customColumns = $list['custom_columns']['columns'];
                        foreach($customColumns as $cv){
                            if($cv['field_name']==$fck){
                                $fckLength = strlen($fck);
                                $filter_by_entry_data .= ", SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value, 's:4:\"name\";s:$fckLength:\"$fck\";s:5:\"value\";', -1), '\";s:', 1), ':\"', -1) AS filterValue";
                                if( !empty($having) ){
                                    $having .= " AND filterValue LIKE '%$fcv%'";
                                }else{
                                    $having .= " HAVING filterValue LIKE '%$fcv%'";
                                }
                                break;
                            }
                        }
                    }
                }else{
                    // Filter by default column
                    if($fck=='entry_date'){
                        $dateFilter = explode(';', $fcv);
                        if(!empty($dateFilter[1])){
                            $from = $dateFilter[0];
                            $until = $dateFilter[1];
                            $filters .= " post.post_date BETWEEN CAST('$from' AS DATE) AND CAST('$until' AS DATE)";
                        }else{
                            $from = $dateFilter[0];
                            $filters .= " post.post_date LIKE '$from%'"; // Only filter starting with
                        }
                    }elseif($fck=='post_title') {
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' post.post_title LIKE "%' . $fcv . '%"';
                    }elseif($fck=='wp_post_title'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' created_post.post_title LIKE "%' . $fcv . '%"';
                    }elseif($fck=='wp_post_status'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' created_post.post_status = "' . $fcv . '"';
                    }elseif($fck=='entry_status'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' entry_status.meta_value = "' . $fcv . '"';
                    }elseif($fck=='wc_order'){
                        if( !empty($filters) ) $filters .= ' AND';
                        // If starts with hashtag then remove it
                        if(substr($fcv, 0, 1)=='#') $fcv = substr($fcv, 1, strlen($fcv));
                        $filters .= ' wc_order.ID LIKE "%' . $fcv . '%"';
                    }elseif($fck=='wc_order_status'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' wc_order.post_status = "' . $fcv . '"';
                    }elseif($fck=='generated_pdf'){
                        if( !empty($having) ){
                            $having .= ' AND pdfFileName LIKE "%'.$fcv.'%"';
                        }else{
                            $having .= ' HAVING pdfFileName LIKE "%'.$fcv.'%"';
                        }
                    }elseif($fck=='paypal_order'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' paypal_order.post_title LIKE "%' . $fcv . '%"';
                    }elseif($fck=='paypal_order_status'){
                        if( !empty($having) ){
                            $having .= ' AND paypalTxnStatus LIKE "%'.$fcv.'%"';
                        }else{
                            $having .= ' HAVING paypalTxnStatus LIKE "%'.$fcv.'%"';
                        }
                    }elseif($fck=='paypal_subscription'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' paypal_order.post_title LIKE "%' . $fcv . '%"';
                    }elseif($fck=='paypal_subscription_status'){
                        if( !empty($having) ){
                            $having .= ' AND paypalSubscriptionStatus = "'.$fcv.'"';
                        }else{
                            $having .= ' HAVING paypalSubscriptionStatus = "'.$fcv.'"';
                        }
                    }elseif($fck=='author_username'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' author.user_login LIKE "%' . $fcv . '%"';
                    }elseif($fck=='author_firstname'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' author_firstname.meta_value LIKE "%' . $fcv . '%"';
                    }elseif($fck=='author_lastname'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' author_lastname.meta_value LIKE "%' . $fcv . '%"';
                    }elseif($fck=='author_fullname'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' CONCAT(author_firstname.meta_value, author_lastname.meta_value) LIKE "%' . $fcv . '%"';
                    }elseif($fck=='author_nickname'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' author_nickname.meta_value LIKE "%' . $fcv . '%"';
                    }elseif($fck=='author_display'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' author.display_name LIKE "%' . $fcv . '%"';
                    }elseif($fck=='author_email'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' author.user_email LIKE "%' . $fcv . '%"';
                    }elseif($fck=='author_id'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' post.post_author = "' . $fcv . '"';
                    }else{
                    }
                }
            }
            $other_selectors = "
paypal_order.ID AS paypalOrderId,
SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:8:\"txn_type\";', -1), '\";s:', 1), ':\"', -1) AS paypalTxnType,
SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value, 's:19:\"_generated_pdf_file\";', -1), '\";s:5:\"value\";', 1), ':\"', -1) AS pdfFileName,
CASE
    WHEN paypal_order.post_type = 'super_paypal_txn' THEN (
      CASE 
        WHEN SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:8:\"txn_type\";', -1), '\";s:', 1), ':\"', -1)='subscr_payment' THEN SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:14:\"payment_status\";', -1), '\";s:', 1), ':\"', -1)
      END
    )
END AS paypalTxnStatus,
CASE
    WHEN paypal_order.post_type = 'super_paypal_sub' THEN (
      CASE 
        WHEN SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:8:\"txn_type\";', -1), '\";s:', 1), ':\"', -1)='subscr_signup' THEN \"Active\"
        WHEN SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:8:\"txn_type\";', -1), '\";s:', 1), ':\"', -1)='recurring_payment_suspended' THEN \"Suspended\"
        WHEN SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:8:\"txn_type\";', -1), '\";s:', 1), ':\"', -1)='subscr_cancel' THEN \"Canceled\"
      END
    )
END AS paypalSubscriptionStatus,
CASE 
  WHEN paypal_order.post_type = 'super_paypal_txn' THEN SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:6:\"txn_id\";', -1), '\";s:', 1), ':\"', -1)
  WHEN paypal_order.post_type = 'super_paypal_sub' THEN NULL
END AS paypalTxnId, 
CASE 
  WHEN paypal_order.post_type = 'super_paypal_txn' THEN NULL
  WHEN paypal_order.post_type = 'super_paypal_sub' THEN SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(paypal_txn_data.meta_value, 's:9:\"subscr_id\";', -1), '\";s:', 1), ':\"', -1)
END AS paypalSubscriptionId
";

            // Check if custom sort was choosen by the user
            $order_by_entry_data = "";
            $sc = 'post_date'; // sort column (defaults to 'date')
            $originalSc = $sc;
            if( !empty($_GET['sc']) ) {
                $sc = sanitize_text_field($_GET['sc']);
                $originalSc = $sc;
                // Entry date
                if($sc==='entry_date') $sc = 'post_date';
                // Paypal transactions
                if($sc==='paypal_order') $sc = 'paypalTxnId';
                if($sc==='paypal_order_status') $sc = 'paypalTxnStatus';
                // Paypal subscriptions
                if($sc==='paypal_subscription') $sc = 'paypalSubscriptionId';
                if($sc==='paypal_subscription_status') $sc = 'paypalSubscriptionStatus';
                // Generated PDF file
                if($sc==='generated_pdf'){
                    $order_by_entry_data = ", SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value, 's:19:\"_generated_pdf_file\";', -1), '\";s:5:\"value\";', 1), ':\"', -1) AS orderValue";
                } 
            }

            // Now first check if this is a custom column
            // Custom column always starts with underscore
            if($sc[0]=='_'){
                $sc = substr($sc, 1);
                // If so, it means that we need to filter the contact entry data
                if($list['custom_columns']['enabled']==='true'){
                    $customColumns = $list['custom_columns']['columns'];
                    foreach($customColumns as $cv){
                        if($cv['field_name']==$sc){
                            $scLength = strlen($sc);
                            $order_by_entry_data = ", SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value, 's:4:\"name\";s:$scLength:\"$sc\";s:5:\"value\";', -1), '\";s:', 1), ':\"', -1) AS orderValue";
                            break;
                        }
                    }
                }
            }
            
            // Sort method, either `a` (ASC) or `d` (DESC)` (defaults to ASC)
            $sm = 'DESC'; 
            if( (!empty($_GET['sm'])) && ($_GET['sm']=='a') ){
                $sm = 'ASC';
            }
            $order_by = "$sc $sm";
            if(!empty($order_by_entry_data)){
                $order_by = "orderValue $sm";
            }

            $offset = 0; // If page is 1, offset is 0, If page is 2 offset is 1 etc.
            $currentPage = 1;
            if( !empty($_GET['sfp']) ) {
                $currentPage = absint($_GET['sfp']);
            }
            $offset = $limit*($currentPage-1);

            $where = '';
            $whereWithoutFilters = '';
            if( $list['retrieve']=='this_form' ) {
                $where .= " AND post.post_parent = '" . absint($form_id) . "'";
                $whereWithoutFilters .= " AND post.post_parent = '" . absint($form_id) . "'";
            }

            if( $list['date_range']['enabled']==='true'){
                $from = $list['date_range']['from'];
                $until = $list['date_range']['until'];
                if( !empty($from) || !empty($until) ){
                    if( !empty($from) && empty($until) ){
                        $where .= " AND DATE(post.post_date) >= CAST('$from' AS DATE)";
                    }
                    if( empty($from) && !empty($until) ){
                        $where .= " AND DATE(post.post_date) <= CAST('$until' AS DATE)";
                    }
                    if( !empty($from) && !empty($until) ){
                        $where .= " AND post.post_date BETWEEN CAST('$from' AS DATE) AND CAST('$until' AS DATE)";
                    }
                }
            }
            
            if($allow['allowSeeAny']===true){
                // Allow user to see any entries in the list
            }else{
                // Only allow to see entries that belong to the currently logged in user
                $where .= ' AND post.post_author = "' . absint( $current_user->ID ) . '"';
                $whereWithoutFilters .= ' AND post.post_author = "' . absint( $current_user->ID ) . '"';
            }

            if( !empty($filters) ) {
                $hasFilters = true;
                $where .= ' AND (' . $filters . ')';
            }
            if( !empty($having) ) {
                $hasFilters = true;
            }

            $count_query = "SELECT COUNT(entry_id) AS total
            FROM (
                SELECT 
                post.ID AS entry_id, 
                post.post_title AS post_title, 
                post.post_date AS post_date, 
                meta.meta_value AS contact_entry_data,
                entry_status.meta_value AS status,
                created_post.ID AS created_post_id, 
                created_post.post_status AS created_post_status,
                created_post.post_title AS created_post_title, 
                wc_order.post_status AS wc_order_status, 
                wc_order.ID AS wc_order_number,
                paypal_order.post_status AS paypal_order_status, 
                paypal_order.post_title AS paypal_order_number,
                paypal_order.ID AS paypal_order_id,
                post.post_author AS author_id, 
                author_firstname.meta_value AS author_firstname,
                author_lastname.meta_value AS author_lastname,
                author_nickname.meta_value AS nickname,
                author.user_login AS author_username,
                author.user_email AS author_email, 
                author.display_name AS author_display_name,
                $other_selectors
                $order_by_entry_data
                $filter_by_entry_data 
                FROM $wpdb->posts AS post 
                INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
                LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
                LEFT JOIN $wpdb->postmeta AS created_post_connection ON created_post_connection.post_id = post.ID AND created_post_connection.meta_key = '_super_created_post'
                LEFT JOIN $wpdb->posts AS created_post ON created_post.ID = created_post_connection.meta_value
                LEFT JOIN $wpdb->postmeta AS wc_order_connection ON wc_order_connection.post_id = post.ID AND wc_order_connection.meta_key = '_super_contact_entry_wc_order_id' 
                LEFT JOIN $wpdb->posts AS wc_order ON wc_order.ID = wc_order_connection.meta_value 
                LEFT JOIN $wpdb->postmeta AS paypal_order_connection ON paypal_order_connection.post_id = post.ID AND paypal_order_connection.meta_key = '_super_contact_entry_paypal_order_id' 
                LEFT JOIN $wpdb->posts AS paypal_order ON paypal_order.ID = paypal_order_connection.meta_value 
                LEFT JOIN $wpdb->postmeta AS paypal_txn_data ON paypal_txn_data.post_id = paypal_order_connection.meta_value AND paypal_txn_data.meta_key = '_super_txn_data' 
                LEFT JOIN $wpdb->users AS author ON author.ID = post.post_author
                LEFT JOIN $wpdb->usermeta AS author_firstname ON author_firstname.user_id = post.post_author AND author_firstname.meta_key = 'first_name'
                LEFT JOIN $wpdb->usermeta AS author_lastname ON author_lastname.user_id = post.post_author AND author_lastname.meta_key = 'last_name'
                LEFT JOIN $wpdb->usermeta AS author_nickname ON author_nickname.user_id = post.post_author AND author_nickname.meta_key = 'nickname'
                WHERE post.post_type = 'super_contact_entry' AND post.post_status != 'trash'
                $where
                $having
            ) a";
            $results_found = $wpdb->get_var($count_query);
            $count_without_filters_query = "SELECT COUNT(entry_id) AS total
            FROM (
                SELECT 
                post.ID AS entry_id,
                post.post_title AS post_title, 
                post.post_date AS post_date,
                meta.meta_value AS contact_entry_data,
                entry_status.meta_value AS status,
                created_post.ID AS created_post_id, 
                created_post.post_status AS created_post_status,
                created_post.post_title AS created_post_title, 
                wc_order.post_status AS wc_order_status, 
                wc_order.ID AS wc_order_number,
                paypal_order.post_status AS paypal_order_status, 
                paypal_order.post_title AS paypal_order_number,
                paypal_order.ID AS paypal_order_id,
                post.post_author AS author_id,
                author_firstname.meta_value AS author_firstname,
                author_lastname.meta_value AS author_lastname,
                author_nickname.meta_value AS author_nickname, 
                author.user_login AS author_username,
                author.user_email AS author_email,
                author.display_name AS author_display_name
                FROM $wpdb->posts AS post 
                INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
                LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
                LEFT JOIN $wpdb->postmeta AS created_post_connection ON created_post_connection.post_id = post.ID AND created_post_connection.meta_key = '_super_created_post'
                LEFT JOIN $wpdb->posts AS created_post ON created_post.ID = created_post_connection.meta_value
                LEFT JOIN $wpdb->postmeta AS wc_order_connection ON wc_order_connection.post_id = post.ID AND wc_order_connection.meta_key = '_super_contact_entry_wc_order_id' 
                LEFT JOIN $wpdb->posts AS wc_order ON wc_order.ID = wc_order_connection.meta_value 
                LEFT JOIN $wpdb->postmeta AS paypal_order_connection ON paypal_order_connection.post_id = post.ID AND paypal_order_connection.meta_key = '_super_contact_entry_paypal_order_id' 
                LEFT JOIN $wpdb->posts AS paypal_order ON paypal_order.ID = paypal_order_connection.meta_value 
                LEFT JOIN $wpdb->postmeta AS paypal_txn_data ON paypal_txn_data.post_id = paypal_order_connection.meta_value AND paypal_txn_data.meta_key = '_super_txn_data' 
                LEFT JOIN $wpdb->users AS author ON author.ID = post.post_author
                LEFT JOIN $wpdb->usermeta AS author_firstname ON author_firstname.user_id = post.post_author AND author_firstname.meta_key = 'first_name'
                LEFT JOIN $wpdb->usermeta AS author_lastname ON author_lastname.user_id = post.post_author AND author_lastname.meta_key = 'last_name'
                LEFT JOIN $wpdb->usermeta AS author_nickname ON author_nickname.user_id = post.post_author AND author_nickname.meta_key = 'nickname'
                WHERE post.post_type = 'super_contact_entry' AND post.post_status != 'trash'
                $whereWithoutFilters
            ) a";
            $absoluteZeroResults = $wpdb->get_var($count_without_filters_query);
            if(absint($absoluteZeroResults)===0) {
                $absoluteZeroResults = true;
            }else{
                $absoluteZeroResults = false;
            } 

            $query = "
            SELECT 
            post.ID AS entry_id, 
            post.post_type AS post_type,
            post.post_title AS post_title,
            post.post_date AS post_date,
            meta.meta_value AS contact_entry_data,
            entry_status.meta_value AS status,
            created_post.ID AS created_post_id, 
            created_post.post_status AS created_post_status,
            created_post.post_title AS created_post_title, 
            wc_order.post_status AS wc_order_status, 
            wc_order.ID AS wc_order_number,
            paypal_order.post_status AS paypal_order_status, 
            paypal_order.post_title AS paypal_order_number,
            paypal_order.ID AS paypal_order_id,
            post.post_author AS author_id,
            author_firstname.meta_value AS author_firstname,
            author_lastname.meta_value AS author_lastname,
            author_nickname.meta_value AS author_nickname,
            author.user_login AS author_username, 
            author.user_email AS author_email, 
            author.display_name AS author_display_name,
            $other_selectors
            $order_by_entry_data
            $filter_by_entry_data
            FROM $wpdb->posts AS post 
            INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
            LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
            LEFT JOIN $wpdb->postmeta AS created_post_connection ON created_post_connection.post_id = post.ID AND created_post_connection.meta_key = '_super_created_post'
            LEFT JOIN $wpdb->posts AS created_post ON created_post.ID = created_post_connection.meta_value
            LEFT JOIN $wpdb->postmeta AS wc_order_connection ON wc_order_connection.post_id = post.ID AND wc_order_connection.meta_key = '_super_contact_entry_wc_order_id' 
            LEFT JOIN $wpdb->posts AS wc_order ON wc_order.ID = wc_order_connection.meta_value 
            LEFT JOIN $wpdb->postmeta AS paypal_order_connection ON paypal_order_connection.post_id = post.ID AND paypal_order_connection.meta_key = '_super_contact_entry_paypal_order_id' 
            LEFT JOIN $wpdb->posts AS paypal_order ON paypal_order.ID = paypal_order_connection.meta_value 
            LEFT JOIN $wpdb->postmeta AS paypal_txn_data ON paypal_txn_data.post_id = paypal_order_connection.meta_value AND paypal_txn_data.meta_key = '_super_txn_data' 
            LEFT JOIN $wpdb->users AS author ON author.ID = post.post_author
            LEFT JOIN $wpdb->usermeta AS author_firstname ON author_firstname.user_id = post.post_author AND author_firstname.meta_key = 'first_name'
            LEFT JOIN $wpdb->usermeta AS author_lastname ON author_lastname.user_id = post.post_author AND author_lastname.meta_key = 'last_name'
            LEFT JOIN $wpdb->usermeta AS author_nickname ON author_nickname.user_id = post.post_author AND author_nickname.meta_key = 'nickname'
            WHERE post.post_type = 'super_contact_entry' AND post.post_status != 'trash'
            $where
            $having
            ORDER BY $order_by
            LIMIT $limit
            OFFSET $offset
            ";
            $entries = $wpdb->get_results($query);

            $result = '';
            $result .= SUPER_Common::load_google_fonts($settings);
            $result .= '<div class="super-listings'.($hasFilters ? ' super-has-filters' : '').'" data-form-id="'.absint($form_id).'" data-list-id="'.absint($list_id).'">';
                $result .= '<div class="super-listings-wrap">';
                    if($absoluteZeroResults===true && $list['onlyDisplayMessage']==='true'){
                        // Do not show filters/columns
                    }else{
                        $actions = '';
                        if($allowViewAny===true || $allowViewOwn===true){
                            $actions .= '<span class="super-view" onclick="SUPER.frontEndListing.viewEntry(this, '.$list_id.')"></span>';
                        }
                        if($allowEditAny===true || $allowEditOwn===true){
                            $actions .= '<span class="super-edit" onclick="SUPER.frontEndListing.editEntry(this, '.$list_id.')"></span>';
                        }
                        if($allowDeleteAny===true || $allowDeleteOwn===true){
                            $actions .= '<span class="super-delete" onclick="SUPER.frontEndListing.deleteEntry(this, '.$list_id.')"></span>';
                        }
                        if(!empty($actions)) $result .= '<div class="super-actions-dummy"></div>';
                        if($hasFilters){
                            $result .= '<div class="super-clear" title="'.esc_html__( 'Reset filters', 'super-forms' ).'">';
                                $result .= '<span onclick="SUPER.frontEndListing.clearFilter(event)">'.esc_html__( 'Clear', 'super-forms' ).'</span>';
                            $result .= ' </div>';
                        }
                        $result .= '<div class="super-columns">';
                            if(!empty($actions)) $result .= '<div class="super-actions-dummy"></div>';
                            foreach( $columns as $k => $v ) {
                                $column_name = $k;
                                if(isset($v['field_name'])){
                                    $column_name = '_' . $v['field_name']; // Custom columns are prefixed with a underscore for easy distinguishing
                                }
                                // If a max width was defined use it on the col-wrap
                                $styles = '';
                                if( !empty( $v['width'] ) ) {
                                    $styles = 'width:' . $v['width'] . 'px;';
                                }
                                if( !empty( $styles ) ) {
                                    $styles = ' style="' . $styles . '"';
                                }

                                // Check if a filter was set for this column
                                $inputValue = (!empty($_GET['fc_'.$column_name]) ? sanitize_text_field($_GET['fc_'.$column_name]) : '');
                                $result .= '<div class="super-col-wrap '.($column_name===$originalSc ? 'super-sort-'.strtolower($sm) : '').'" data-name="' . $column_name . '"' . $styles . '>';
                                    $result .= '<span class="super-col-name">' . $v['name'] . '</span>';
                                    if( isset($v['sort']) && $v['sort']==='true' ) {
                                        $result .= '<div class="super-col-sort">';
                                            $result .= '<span class="super-sort-down" onclick="SUPER.frontEndListing.sort(event, this)">↓</span>';
                                            $result .= '<span class="super-sort-up" onclick="SUPER.frontEndListing.sort(event, this)">↑</span>';
                                        $result .= '</div>';
                                    }
                                    if($v['filter']['enabled']==='true'){
                                        $result .= '<div class="super-col-filter">';
                                            if(empty($v['filter']['type'])) $v['filter']['type'] = 'text';
                                            if($column_name==='entry_date'){
                                                $v['filter']['type'] = 'datepicker';
                                            }
                                            if($v['filter']['type']=='text'){
                                                $result .= '<input value="' . $inputValue . '" autocomplete="new-password" name="' . $k . '" type="text" placeholder="' . $v['filter']['placeholder'] . '" />';
                                                $result .= '<span class="super-search" onclick="SUPER.frontEndListing.search(event, this)"></span>';
                                            }
                                            if($v['filter']['type']=='datepicker'){
                                                $fromUntil = explode(';', $inputValue);
                                                $from = (isset($fromUntil[0]) ? $fromUntil[0] : '');
                                                $until = (isset($fromUntil[1]) ? $fromUntil[1] : '');
                                                $result .= '<input'.(!empty($v['width']) ? ' style="width:'.(absint($v['width'])/2-2).'px;"' : '').' value="' . $from . '" autocomplete="new-password" name="' . $k . '_from" type="date"  onchange="SUPER.frontEndListing.search(event, this)" />';
                                                $result .= '<input'.(!empty($v['width']) ? ' style="width:'.(absint($v['width'])/2-2).'px;margin-left:4px;"' : '').' value="' . $until . '" autocomplete="new-password" name="' . $k . '_until" type="date" onchange="SUPER.frontEndListing.search(event, this)" />';
                                            }
                                            if( $v['filter']['type']=='dropdown' ) {
                                                $result .= '<select name="' . $k . '" onchange="SUPER.frontEndListing.search(event, this)">';
                                                    $result .= '<option value=""' . ( empty($inputValue) ? ' selected="selected"' : '' ) . '>' . $v['filter']['placeholder'] . '</option>';
                                                    if(is_array($v['filter']['items'])){
                                                        $items = $v['filter']['items'];
                                                        foreach( $items as $value => $label ) {
                                                            $result .= '<option value="' . $value . '"' . ( $inputValue==$value ? ' selected="selected"' : '' ) . '>' . $label . '</option>';
                                                        }
                                                    }else{
                                                        $items = explode("\n", $v['filter']['items']);
                                                        foreach( $items as $value ) {
                                                            $value = explode('|', $value);
                                                            $label = (isset($value[1]) ? $value[1] : 'undefined');
                                                            $value = (isset($value[0]) ? $value[0] : 'undefined');
                                                            $result .= '<option value="' . $value . '"' . ( $inputValue==$value ? ' selected="selected"' : '' ) . '>' . $label . '</option>';
                                                        }
                                                    }
                                                $result .= '</select>';
                                            }
                                        $result .= '</div>';
                                    }
                                $result .= '</div>';
                            }
                        $result .= '</div>';
                    }
                    if($absoluteZeroResults){
                        $result .= '<div class="super-no-results">'.do_shortcode($list['noResultsMessage']).'</div>';
                    }
                    $result .= '<div class="super-entries">';
                        if(count($entries)===0 && !$absoluteZeroResults){
                            $result .= '<div class="super-no-results-filter">'.do_shortcode($list['noResultsFilterMessage']).'</div>';
                        }else{
                            $result .= '<div class="super-scroll"></div>';
                            if( !class_exists( 'SUPER_Settings' ) ) require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
                            $global_settings = SUPER_Common::get_global_settings();
                            $entry_statuses = SUPER_Settings::get_entry_statuses($global_settings);
                            $wp_post_statuses = get_post_statuses();
                            $wc_order_statuses = array();
                            if (function_exists('wc_get_order_statuses')) {
                                $wc_order_statuses = wc_get_order_statuses();
                            }
                            if(class_exists('SUPER_PayPal')) {
                                $paypal_payment_statuses = SUPER_PayPal::$paypal_payment_statuses;
                            }
                            foreach($entries as $entry){
                                $data = unserialize($entry->contact_entry_data);
                                $result .= '<div class="super-entry" data-id="' . $entry->entry_id . '">';
                                    $allow = self::get_action_permissions(array('list'=>$list, 'entry'=>$entry));
                                    $allowViewAny = $allow['allowViewAny'];
                                    $allowViewOwn = $allow['allowViewOwn'];
                                    $allowEditAny = $allow['allowEditAny'];
                                    $allowEditOwn = $allow['allowEditOwn'];
                                    $allowDeleteAny = $allow['allowDeleteAny'];
                                    $allowDeleteOwn = $allow['allowDeleteOwn'];
                                    $actions = '';
                                    if($allowViewAny===true || $allowViewOwn===true){
                                        $actions .= '<span class="super-view" onclick="SUPER.frontEndListing.viewEntry(this, '.$list_id.')"></span>';
                                    }
                                    if($allowEditAny===true || $allowEditOwn===true){
                                        $actions .= '<span class="super-edit" onclick="SUPER.frontEndListing.editEntry(this, '.$list_id.')"></span>';
                                    }
                                    if($allowDeleteAny===true || $allowDeleteOwn===true){
                                        $actions .= '<span class="super-delete" onclick="SUPER.frontEndListing.deleteEntry(this, '.$list_id.')"></span>';
                                    }
                                    if(!empty($actions)){
                                        $result .= '<div class="super-col super-actions">';
                                            $result .= '<div class="super-toggle">';
                                                $result .= '<div class="super-actions-menu">';
                                                    $result .= apply_filters( 'super_listings_actions_filter', $actions, $entry );
                                                $result .= ' </div>';
                                            $result .= '</div>';
                                        $result .= ' </div>';
                                    }
    
                                    foreach( $columns as $ck => $cv ) {
                                        // If a max width was defined use it on the col-wrap
                                        $styles = '';
                                        if( !empty( $cv['width'] ) ) {
                                            $styles = 'width:' . $cv['width'] . 'px;';
                                        }
                                        if( !empty( $styles ) ) {
                                            $styles = ' style="' . $styles . '"';
                                        }
                                        $column_key = ( isset($cv['field_name']) ? $cv['field_name'] : $ck );
                                        $result .= '<div class="super-col super-' . $column_key . '"' . $styles . '>';
                                            $cellValue = '';
                                            $linkUrl = '';
                                            $linkType = '';
                                            $linkTitle = '';
                                            if(!empty($cv['link']) && $cv['link']['type']!='none'){
                                                $lt = $cv['link']['type'];
                                                if($lt=='custom'){
                                                    // Custom URL
                                                    $linkUrl = $cv['link']['url'];
                                                }elseif($lt==='contact_entry'){
                                                    $linkUrl = get_admin_url() . '?page=super_contact_entry&id='.$entry->entry_id;
                                                    $linkType = 'edit';
                                                    $linkTitle = esc_html__( 'Edit contact entry', 'super-forms' );
                                                }elseif($lt=='wc_order_backend'){
                                                    // WooCommerce order backend (edit) (WC Checkout)
                                                    $linkUrl = get_edit_post_link($entry->wc_order_number);
                                                    $linkType = 'edit';
                                                    $linkTitle = esc_html__( 'Edit order', 'super-forms' );
                                                }elseif($lt=='wc_order_frontend'){
                                                    // WooCommerce order front-end (view) (WC Checkout)
                                                    if (function_exists('wc_get_order')) {
                                                        $order = wc_get_order($entry->wc_order_number);
                                                        if($order) {
                                                            $linkUrl = $order->get_checkout_order_received_url();
                                                            $linkType = 'view';
                                                            $linkTitle = esc_html__( 'View order', 'super-forms' );
                                                        }
                                                    }
                                                }elseif($lt=='paypal_order'){
                                                    // Paypal order (Paypal)
                                                    $linkUrl = admin_url() . 'admin.php?page=super_paypal_txn&id=' . $entry->paypalOrderId;
                                                    $linkType = 'view';
                                                    $linkTitle = esc_html__( 'View order', 'super-forms' );
                                                }elseif($lt=='paypal_subscription'){
                                                    // Paypal subscription (Paypal)
                                                    $linkUrl = admin_url() . 'admin.php?page=super_paypal_sub&id=' . $entry->paypalOrderId;
                                                    $linkType = 'view';
                                                    $linkTitle = esc_html__( 'View subscription', 'super-forms' );
                                                }elseif($lt=='generated_pdf'){
                                                    // Generated PDF file (PDF Generator)
                                                    if( isset( $data['_generated_pdf_file']['files'] ) ) {
                                                        foreach( $data['_generated_pdf_file']['files'] as $fk => $fv ) {
                                                            $linkUrl = $fv['url'];
                                                            if( !empty( $fv['attachment'] ) ) { // only if file was inserted to Media Library
                                                                $linkUrl = wp_get_attachment_url( $fv['attachment'] );
                                                                if($linkUrl===false) $linkUrl = '';
                                                            }
                                                            $linkType = 'download';
                                                            $linkTitle = esc_html__( 'Download PDF', 'super-forms' );
                                                        }
                                                    }
                                                }elseif($lt=='post_backend'){
                                                    // Created post (Front-end Posting)
                                                    $linkUrl = get_edit_post_link($entry->created_post_id);
                                                    $linkType = 'edit';
                                                    $linkTitle = esc_html__( 'Edit post', 'super-forms' );
                                                }elseif($lt=='post_frontend'){
                                                    // Created post (Front-end Posting)
                                                    $linkUrl = get_permalink($entry->created_post_id);
                                                    $linkType = 'view';
                                                    $linkTitle = esc_html__( 'View post', 'super-forms' );
                                                }elseif($lt=='author_posts'){
                                                    // Link to author page
                                                    if($entry->author_id) {
                                                        $linkUrl = get_author_posts_url($entry->author_id);
                                                        $linkType = 'view';
                                                        $linkTitle = esc_html__( 'View author', 'super-forms' );
                                                    }
                                                }elseif($lt=='author_edit'){
                                                    // Link to edit user
                                                    if($entry->author_id) {
                                                        $linkUrl = get_edit_user_link($entry->author_id);
                                                        $linkType = 'edit';
                                                        $linkTitle = esc_html__( 'Edit author', 'super-forms' );
                                                    }
                                                }elseif($lt=='author_email'){
                                                    // Link to mail directly to the author E-mail address
                                                    if($entry->author_id) {
                                                        $linkUrl = 'mailto:'.$entry->author_email;
                                                        $linkType = 'mail';
                                                        $linkTitle = esc_html__( 'Send E-mail to author', 'super-forms' );
                                                    }
                                                }elseif($lt=='mailto'){
                                                    // Link to mail directly to the author E-mail address
                                                    $linkUrl = 'mailto';
                                                    $linkType = 'mail';
                                                    $linkTitle = esc_html__( 'Send E-mail', 'super-forms' );
                                                }
                                            }
                                            if($column_key=='post_title'){
                                                $cellValue = esc_html($entry->post_title);
                                            }elseif($entry->author_id && $column_key=='author_username'){
                                                $cellValue = esc_html($entry->author_username);
                                            }elseif($entry->author_id && $column_key=='author_firstname'){
                                                $cellValue = esc_html($entry->author_firstname);
                                            }elseif($entry->author_id && $column_key=='author_lastname'){
                                                $cellValue = esc_html($entry->author_lastname);
                                            }elseif($entry->author_id && $column_key=='author_fullname'){
                                                $cellValue = esc_html($entry->author_firstname.' '.$entry->author_lastname);
                                            }elseif($entry->author_id && $column_key=='author_nickname'){
                                                $cellValue = esc_html($entry->author_nickname);
                                            }elseif($entry->author_id && $column_key=='author_display'){
                                                $cellValue = esc_html($entry->author_display_name);
                                            }elseif($entry->author_id && $column_key=='author_email'){
                                                $cellValue = esc_html($entry->author_email);
                                            }elseif($entry->author_id && $column_key=='author_id'){
                                                $cellValue = esc_html($entry->author_id);
                                            }elseif($column_key=='entry_status'){
                                                if( (isset($entry_statuses[$entry->status])) && ($entry->status!='') ) {
                                                    $cellValue = '<span class="super-entry-status super-entry-status-' . $entry->status . '" style="color:' . $entry_statuses[$entry->status]['color'] . ';background-color:' . $entry_statuses[$entry->status]['bg_color'] . '">' . $entry_statuses[$entry->status]['name'] . '</span>';
                                                }else{
                                                    $post_status = get_post_status($entry->entry_id);
                                                    if($post_status=='super_read'){
                                                        $cellValue = '<span class="super-entry-status super-entry-status-' . $post_status . '" style="background-color:#d6d6d6;">' . esc_html__( 'Read', 'super-forms' ) . '</span>';
                                                    }else{
                                                        $cellValue = '<span class="super-entry-status super-entry-status-' . $post_status . '">' . esc_html__( 'Unread', 'super-forms' ) . '</span>';
                                                    }
                                                }
                                            }elseif($column_key=='wp_post_title'){
                                                $post_id = get_post_meta( $entry->entry_id, '_super_created_post', true );
                                                if(!empty($post_id)){
                                                    $cellValue = esc_html(get_the_title($post_id));
                                                } 
                                            }elseif($column_key=='wp_post_status'){
                                                $post_id = get_post_meta( $entry->entry_id, '_super_created_post', true );
                                                if(!empty($post_id)) {
                                                    $status = get_post_status($post_id);
                                                    $cellValue = esc_html($wp_post_statuses[$status]);
                                                }
                                            }elseif($column_key=='generated_pdf'){
                                                if( isset( $data['_generated_pdf_file']['files'] ) ) {
                                                    foreach( $data['_generated_pdf_file']['files'] as $fk => $fv ) {
                                                        if($fk>0) echo '<br />';
                                                        $cellValue .= esc_html( $fv['value'] ); // The filename
                                                    }
                                                }
                                            }elseif($column_key=='wc_order'){
                                                $order_id = get_post_meta( $entry->entry_id, '_super_contact_entry_wc_order_id', true );
                                                if(!empty($order_id)){
                                                    $order_id = absint($order_id);
                                                    if( $order_id!=0 ) {
                                                        $cellValue = '#' . $order_id;
                                                    }
                                                }
                                            }elseif($column_key=='wc_order_status'){
                                                $order_id = get_post_meta( $entry->entry_id, '_super_contact_entry_wc_order_id', true );
                                                if(!empty($order_id)){
                                                    $order_id = absint($order_id);
                                                    $order = wc_get_order( $order_id );
                                                    $order_status  = $order->get_status();
                                                    if($order_id!=0) {
                                                        $cellValue = '<mark class="order-status status-'.$order_status.' tips"><span>'.$wc_order_statuses['wc-'.$order_status].'</span></mark>';
                                                    }
                                                }
                                            }elseif($column_key=='paypal_order'){
                                                $cellValue = esc_html($entry->paypalTxnId);
                                            }elseif($column_key=='paypal_order_status'){
                                                $status = $entry->paypalTxnStatus;
                                                if($status){
                                                    $value = $paypal_payment_statuses[$status];
                                                    if( (isset($entry_statuses[$status])) && ($status!='') ) {
                                                        $cellValue = '<span class="super-txn-status super-txn-status-' . strtolower($status) . '" style="color:' . $entry_statuses[$txn_data['payment_status']]['color'] . ';background-color:' . $entry_statuses[$txn_data['payment_status']]['bg_color'] . '">' . $value['label'] . '</span>';
                                                    }else{
                                                        $cellValue = '<span class="super-txn-status super-txn-status-' . strtolower($status) . '">' . esc_html($value['label']) . '</span>';
                                                    }
                                                }
                                            }elseif($column_key=='paypal_subscription'){
                                                $cellValue = esc_html($entry->paypalSubscriptionId);
                                            }elseif($column_key=='paypal_subscription_status'){
                                                $sub_id = get_post_meta( $entry->entry_id, '_super_contact_entry_paypal_order_id', true );
                                                if(!empty($sub_id) && get_post_type($sub_id)==='super_paypal_sub'){
                                                    $txn_data = get_post_meta( $sub_id, '_super_txn_data', true );
                                                    if( ($txn_data['txn_type']=='subscr_signup') || ($txn_data['txn_type']=='subscr_modify') || ($txn_data['txn_type']=='subscr_cancel') || ($txn_data['txn_type']=='recurring_payment_suspended') ) {
                                                        $status = 'Active';
                                                        if( isset($txn_data['profile_status']) ) {
                                                            $status = $txn_data['profile_status'];
                                                        }
                                                        if( $txn_data['txn_type']=='recurring_payment_suspended' ) {
                                                            $status = esc_html__( 'Suspended', 'super-forms' );
                                                        }
                                                        if( $txn_data['txn_type']=='subscr_cancel' ) {
                                                            $status = esc_html__( 'Canceled', 'super-forms' );
                                                        }
                                                        $cellValue = '<span class="super-txn-status super-txn-status-' . strtolower($status) . '">' . esc_html($status) . '</span>';
                                                    }
                                                }
                                            }elseif($column_key=='entry_date'){
                                                $date = date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) );
                                                $time = ' @ ' . date_i18n( get_option( 'time_format' ), strtotime( $entry->post_date ) );
                                                $cellValue = apply_filters( 'super_listings_date_filter', $date.$time, $entry );
                                            }else{
                                                // Check if this data key exists
                                                if(isset($data[$column_key])){
                                                    // Check if it has a value, if so print it
                                                    if(isset($data[$column_key]['value'])){
                                                        if ( strpos( $data[$column_key]['value'], 'data:image/png;base64,') !== false ) {
                                                            // @IMPORTANT, escape the Data URL but make sure add it as an acceptable protocol 
                                                            // otherwise the signature will not be displayed
                                                            $linkUrl = '';
                                                            $imgUrl = esc_url( $data[$column_key]['value'], array( 'data' ) );
                                                            $cellValue = '<a href="' . $imgUrl . '" download>';
                                                            $cellValue .= '<img class="super-signature" src="' . $imgUrl . '" />';
                                                            $cellValue .= '<span class="super-icon-download"></span></a>';
                                                        }else{
                                                            $cellValue = esc_html($data[$column_key]['value']);
                                                        }
                                                    }else{
                                                        // If not then it must be a special field, for instance file uploads
                                                        if($data[$column_key]['type']==='files'){
                                                            $linkUrl = '';
                                                            if(isset($data[$column_key]['files'])){
                                                                $files = $data[$column_key]['files'];
                                                                foreach($files as $fk => $fv){
                                                                    $url = (!empty($fv['url']) ? $fv['url'] : '');
                                                                    if( !empty( $fv['attachment'] ) ) { // only if file was inserted to Media Library
                                                                        $url = wp_get_attachment_url( $fv['attachment'] );
                                                                    }
                                                                    if(!empty($url)){
                                                                        $cellValue .= '<a target="_blank" download href="' . esc_url( $url ) . '">';
                                                                    }
                                                                    $cellValue .= esc_html( $fv['value'] ); // The filename
                                                                    if(!empty($url)){
                                                                        $cellValue .= '<span class="super-icon-download"></span></a>';
                                                                    }
                                                                    $cellValue .= '<br />';
                                                                }
                                                            }else{
                                                                $cellValue = esc_html__( 'No files uploaded', 'super-forms' );
                                                            }
                                                        }
                                                    }
                                                }else{
                                                    // No data found for this entry
                                                }
                                            }
                                            if($linkUrl!==''){
                                                if($linkUrl==='mailto') $linkUrl = 'mailto:'.$cellValue;
                                                $result .= '<a target="_blank" href="' . esc_url($linkUrl) . '">';
                                                $result .= $cellValue;
                                                if(!empty($cellValue)){
                                                    if($linkType==='edit') $result .= '<span class="super-icon-edit" title="'.esc_attr($linkTitle).'"></span>';
                                                    if($linkType==='view') $result .= '<span class="super-icon-view" title="'.esc_attr($linkTitle).'"></span>';
                                                    if($linkType==='download') $result .= '<span class="super-icon-download" title="'.esc_attr($linkTitle).'"></span>';
                                                    if($linkType==='mail') $result .= '<span class="super-icon-mail" title="'.esc_attr($linkTitle).'"></span>';
                                                }
                                                $result .= '</a>';
                                            }else{
                                                $result .= $cellValue;
                                            }
                                        $result .= '</div>';
                                    }
                                $result .= '</div>';
                            }
                        }
                    $result .= '</div>';
                $result .= '</div>';

                if($absoluteZeroResults===true && $list['onlyDisplayMessage']==='true'){
                    // Do not show pagination
                }else{
                    $result .= '<div class="super-pagination">';
                        $result .= '<span class="super-pages">' . esc_html__( 'Page', 'super-forms' ) . '</span>';
                        if($currentPage>1){
                            $result .= '<span class="super-prev" onclick="SUPER.frontEndListing.changePage(event, this)"></span>';
                        }
                        $result .= '<select class="super-switcher" onchange="SUPER.frontEndListing.changePage(event, this)">';
                            $totalPages = ceil($results_found/$limit);
                            if($totalPages <= 0) $totalPages = 1;
                            $i = 0;
                            while( $i < $totalPages ) {
                                $i++;
                                $result .= '<option' . ($currentPage==$i ? ' selected="selected"' : '') . '>' . $i . '</option>';
                            }
                        $result .= '</select>';
                        if($currentPage<$totalPages){
                            $result .= '<span class="super-next" onclick="SUPER.frontEndListing.changePage(event, this)"></span>';
                        }
                        $result .= '<span class="super-results">';
                            $result .= $results_found . ' ';
                            if($results_found==1){
                                $result .= esc_html__( 'result', 'super-forms' );
                            }else{
                                $result .= esc_html__( 'results', 'super-forms' );
                            }
                        $result .= '</span>';
                        $result .= '<select class="super-limit" onchange="SUPER.frontEndListing.limit(event, this)">';
                            $result .= '<option ' . ($limit==1 ? 'selected="selected" ' : '') . 'value="1">1</option>';
                            $result .= '<option ' . ($limit==10 ? 'selected="selected" ' : '') . 'value="10">10</option>';
                            $result .= '<option ' . ($limit==25 ? 'selected="selected" ' : '') . 'value="25">25</option>';
                            $result .= '<option ' . ($limit==50 ? 'selected="selected" ' : '') . 'value="50">50</option>';
                            $result .= '<option ' . ($limit==100 ? 'selected="selected" ' : '') . 'value="100">100</option>';
                            $result .= '<option ' . ($limit==300 ? 'selected="selected" ' : '') . 'value="300">300</option>';
                        $result .= '</select>';
                    $result .= '</div>';
                }
            $result .= '</div>';

            $css = require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
            $css .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/fonts.php' );
            $css .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/colors.php' );
            if( $css!='' ) $result .= '<style type="text/css">' . $css . '</style>';

            return $result;
        }
        public static function get_action_permissions($atts){
            global $current_user;
            $list = $atts['list'];
            $entry = (array) (isset($atts['entry']) ? $atts['entry'] : null);
            $authorId = 0;
            if(isset($entry)){
                if(isset($entry['author_id'])){
                    $authorId = $entry['author_id'];
                }
                if(isset($entry['post_author'])){
                    $authorId = $entry['post_author'];
                }
            }

            // Display listings (wether or not the listing should be generated/displayed to this user)
            $allowDisplay = true;
            if(!empty($list['display'])){
                if($list['display']['enabled']==='true'){
                    $allowDisplay = false;
                    // Check if both roles and user ID's are empty
                    if( (empty($list['display']['user_roles'])) && (empty($list['display']['user_ids'])) ){
                        $allowDisplay = true;
                    }else{
                        $allowed_roles = preg_replace('/\s+/', '', $list['display']['user_roles']);
                        $allowed_roles = explode(",", $allowed_roles);
                        if( (!empty($list['display']['user_roles'])) && (empty($list['display']['user_ids'])) ){
                            // Only compare against user roles
                            foreach( $current_user->roles as $v ) {
                                if( in_array( $v, $allowed_roles ) ) {
                                    $allowDisplay = true;
                                }
                            }
                        }else{
                            if(empty($list['display']['user_roles'])) {
                                // Only compare against user ID
                                $allowed_ids = preg_replace('/\s+/', '', $list['display']['user_ids']);
                                $allowed_ids = explode(",", $allowed_ids);
                                if( in_array( $current_user->ID, $allowed_ids ) ) {
                                    $allowDisplay = true;
                                }
                            }else{
                                // Compare against both user roles and ids
                                if(!empty($list['display']['user_ids'])) {
                                    foreach( $current_user->roles as $v ) {
                                        if( in_array( $v, $allowed_roles ) ) {
                                            $allowDisplay = true;
                                        }
                                    }
                                }
                                if(!empty($list['display']['user_ids'])) {
                                    $allowed_ids = preg_replace('/\s+/', '', $list['display']['user_ids']);
                                    $allowed_ids = explode(",", $allowed_ids);
                                    if( in_array( $current_user->ID, $allowed_ids ) ) {
                                        $allowDisplay = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // SEE ANY (logged in users can always see their own entries in the list)
            $allowSeeAny = false;
            if(!empty($list['see_any'])) {
                if($list['see_any']['enabled']==='true'){
                    // Check if both roles and user ID's are empty
                    if( (empty($list['see_any']['user_roles'])) && (empty($list['see_any']['user_ids'])) ){
                        $allowSeeAny = true;
                    }else{
                        $allowed_roles = preg_replace('/\s+/', '', $list['see_any']['user_roles']);
                        $allowed_roles = explode(",", $allowed_roles);
                        if( (!empty($list['see_any']['user_roles'])) && (empty($list['see_any']['user_ids'])) ){
                            // Only compare against user roles
                            foreach( $current_user->roles as $v ) {
                                if( in_array( $v, $allowed_roles ) ) {
                                    $allowSeeAny = true;
                                }
                            }
                        }else{
                            if(empty($list['see_any']['user_roles'])) {
                                // Only compare against user ID
                                $allowed_ids = preg_replace('/\s+/', '', $list['see_any']['user_ids']);
                                $allowed_ids = explode(",", $allowed_ids);
                                if( in_array( $current_user->ID, $allowed_ids ) ) {
                                    $allowSeeAny = true;
                                }
                            }else{
                                // Compare against both user roles and ids
                                if(!empty($list['see_any']['user_ids'])) {
                                    foreach( $current_user->roles as $v ) {
                                        if( in_array( $v, $allowed_roles ) ) {
                                            $allowSeeAny = true;
                                        }
                                    }
                                }
                                if(!empty($list['see_any']['user_ids'])) {
                                    $allowed_ids = preg_replace('/\s+/', '', $list['see_any']['user_ids']);
                                    $allowed_ids = explode(",", $allowed_ids);
                                    if( in_array( $current_user->ID, $allowed_ids ) ) {
                                        $allowSeeAny = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // VIEW ANY (allow clicking the "view" icon which will open the entry data in a popup with a optional custom HTML template)
            $allowViewAny = false;
            if(!empty($list['view_any'])) {
                if($list['view_any']['enabled']==='true'){
                    // Check if both roles and user ID's are empty
                    if( (empty($list['view_any']['user_roles'])) && (empty($list['view_any']['user_ids'])) ){
                        $allowViewAny = true;
                    }else{
                        $allowed_roles = preg_replace('/\s+/', '', $list['view_any']['user_roles']);
                        $allowed_roles = explode(",", $allowed_roles);
                        if( (!empty($list['view_any']['user_roles'])) && (empty($list['view_any']['user_ids'])) ){
                            // Only compare against user roles
                            foreach( $current_user->roles as $v ) {
                                if( in_array( $v, $allowed_roles ) ) {
                                    $allowViewAny = true;
                                }
                            }
                        }else{
                            if(empty($list['view_any']['user_roles'])) {
                                // Only compare against user ID
                                $allowed_ids = preg_replace('/\s+/', '', $list['view_any']['user_ids']);
                                $allowed_ids = explode(",", $allowed_ids);
                                if( in_array( $current_user->ID, $allowed_ids ) ) {
                                    $allowViewAny = true;
                                }
                            }else{
                                // Compare against both user roles and ids
                                if(!empty($list['view_any']['user_ids'])) {
                                    foreach( $current_user->roles as $v ) {
                                        if( in_array( $v, $allowed_roles ) ) {
                                            $allowViewAny = true;
                                        }
                                    }
                                }
                                if(!empty($list['view_any']['user_ids'])) {
                                    $allowed_ids = preg_replace('/\s+/', '', $list['view_any']['user_ids']);
                                    $allowed_ids = explode(",", $allowed_ids);
                                    if( in_array( $current_user->ID, $allowed_ids ) ) {
                                        $allowViewAny = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // VIEW OWN (allow clicking the "view" icon which will open the entry data in a popup with a optional custom HTML template)
            $allowViewOwn = false;
            if(!empty($list['view_own']) && isset($entry)) {
                if($list['view_own']['enabled']==='true'){
                    // First check if entry author ID equals logged in user ID
                    if(absint($current_user->ID) === absint($authorId)){
                        $allowViewOwn = true;
                    }
                }
            }

            // EDIT ANY
            // Check if any user or own user is allowed to edit entry
            $allowEditAny = false;
            if(!empty($list['edit_any'])) {
                if($list['edit_any']['enabled']==='true'){
                    // Check if both roles and user ID's are empty
                    if( (empty($list['edit_any']['user_roles'])) && (empty($list['edit_any']['user_ids'])) ){
                        $allowEditAny = true;
                    }else{
                        $allowed_roles = preg_replace('/\s+/', '', $list['edit_any']['user_roles']);
                        $allowed_roles = explode(",", $allowed_roles);
                        if( (!empty($list['edit_any']['user_roles'])) && (empty($list['edit_any']['user_ids'])) ){
                            // Only compare against user roles
                            foreach( $current_user->roles as $v ) {
                                if( in_array( $v, $allowed_roles ) ) {
                                    $allowEditAny = true;
                                }
                            }
                        }else{
                            if(empty($list['edit_any']['user_roles'])) {
                                // Only compare against user ID
                                $allowed_ids = preg_replace('/\s+/', '', $list['edit_any']['user_ids']);
                                $allowed_ids = explode(",", $allowed_ids);
                                if( in_array( $current_user->ID, $allowed_ids ) ) {
                                    $allowEditAny = true;
                                }
                            }else{
                                // Compare against both user roles and ids
                                if(!empty($list['edit_any']['user_ids'])) {
                                    foreach( $current_user->roles as $v ) {
                                        if( in_array( $v, $allowed_roles ) ) {
                                            $allowEditAny = true;
                                        }
                                    }
                                }
                                if(!empty($list['edit_any']['user_ids'])) {
                                    $allowed_ids = preg_replace('/\s+/', '', $list['edit_any']['user_ids']);
                                    $allowed_ids = explode(",", $allowed_ids);
                                    if( in_array( $current_user->ID, $allowed_ids ) ) {
                                        $allowEditAny = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // EDIT OWN
            $allowEditOwn = false;
            if(!empty($list['edit_own']) && isset($entry)) {
                if($list['edit_own']['enabled']==='true'){
                    // First check if entry author ID equals logged in user ID
                    if(absint($current_user->ID) === absint($authorId)){
                        // Check if both roles and user ID's are empty
                        if( (empty($list['edit_own']['user_roles'])) && (empty($list['edit_own']['user_ids'])) ){
                            $allowEditOwn = true;
                        }else{
                            $allowed_roles = preg_replace('/\s+/', '', $list['edit_own']['user_roles']);
                            $allowed_roles = explode(",", $allowed_roles);
                            if( (!empty($list['edit_own']['user_roles'])) && (empty($list['edit_own']['user_ids'])) ){
                                // Only compare against user roles
                                foreach( $current_user->roles as $v ) {
                                    if( in_array( $v, $allowed_roles ) ) {
                                        $allowEditOwn = true;
                                    }
                                }
                            }else{
                                if(empty($list['edit_own']['user_roles'])) {
                                    // Only compare against user ID
                                    $allowed_ids = preg_replace('/\s+/', '', $list['edit_own']['user_ids']);
                                    $allowed_ids = explode(",", $allowed_ids);
                                    if( in_array( $current_user->ID, $allowed_ids ) ) {
                                        $allowEditOwn = true;
                                    }
                                }else{
                                    // Compare against both user roles and ids
                                    if(!empty($list['edit_own']['user_ids'])) {
                                        foreach( $current_user->roles as $v ) {
                                            if( in_array( $v, $allowed_roles ) ) {
                                                $allowEditOwn = true;
                                            }
                                        }
                                    }
                                    if(!empty($list['edit_own']['user_ids'])) {
                                        $allowed_ids = preg_replace('/\s+/', '', $list['edit_own']['user_ids']);
                                        $allowed_ids = explode(",", $allowed_ids);
                                        if( in_array( $current_user->ID, $allowed_ids ) ) {
                                            $allowEditOwn = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // DELETE ANY
            $allowDeleteAny = false;
            if(!empty($list['delete_any'])) {
                if($list['delete_any']['enabled']==='true'){
                    // Check if both roles and user ID's are empty
                    if( (empty($list['delete_any']['user_roles'])) && (empty($list['delete_any']['user_ids'])) ){
                        $allowDeleteAny = true;
                    }else{
                        $allowed_roles = preg_replace('/\s+/', '', $list['delete_any']['user_roles']);
                        $allowed_roles = explode(",", $allowed_roles);
                        if( (!empty($list['delete_any']['user_roles'])) && (empty($list['delete_any']['user_ids'])) ){
                            // Only compare against user roles
                            foreach( $current_user->roles as $v ) {
                                if( in_array( $v, $allowed_roles ) ) {
                                    $allowDeleteAny = true;
                                }
                            }
                        }else{
                            if(empty($list['delete_any']['user_roles'])) {
                                // Only compare against user ID
                                $allowed_ids = preg_replace('/\s+/', '', $list['delete_any']['user_ids']);
                                $allowed_ids = explode(",", $allowed_ids);
                                if( in_array( $current_user->ID, $allowed_ids ) ) {
                                    $allowDeleteAny = true;
                                }
                            }else{
                                // Compare against both user roles and ids
                                if(!empty($list['delete_any']['user_ids'])) {
                                    foreach( $current_user->roles as $v ) {
                                        if( in_array( $v, $allowed_roles ) ) {
                                            $allowDeleteAny = true;
                                        }
                                    }
                                }
                                if(!empty($list['delete_any']['user_ids'])) {
                                    $allowed_ids = preg_replace('/\s+/', '', $list['delete_any']['user_ids']);
                                    $allowed_ids = explode(",", $allowed_ids);
                                    if( in_array( $current_user->ID, $allowed_ids ) ) {
                                        $allowDeleteAny = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // DELETE OWN
            $allowDeleteOwn = false;
            if(!empty($list['delete_own']) && isset($entry)) {
                if($list['delete_own']['enabled']==='true'){
                    // First check if entry author ID equals logged in user ID
                    if(absint($current_user->ID) === absint($authorId)){
                        // Check if both roles and user ID's are empty
                        if( (empty($list['delete_own']['user_roles'])) && (empty($list['delete_own']['user_ids'])) ){
                            $allowDeleteOwn = true;
                        }else{
                            $allowed_roles = preg_replace('/\s+/', '', $list['delete_own']['user_roles']);
                            $allowed_roles = explode(",", $allowed_roles);
                            if( (!empty($list['delete_own']['user_roles'])) && (empty($list['delete_own']['user_ids'])) ){
                                // Only compare against user roles
                                foreach( $current_user->roles as $v ) {
                                    if( in_array( $v, $allowed_roles ) ) {
                                        $allowDeleteOwn = true;
                                    }
                                }
                            }else{
                                if(empty($list['delete_own']['user_roles'])) {
                                    // Only compare against user ID
                                    $allowed_ids = preg_replace('/\s+/', '', $list['delete_own']['user_ids']);
                                    $allowed_ids = explode(",", $allowed_ids);
                                    if( in_array( $current_user->ID, $allowed_ids ) ) {
                                        $allowDeleteOwn= true;
                                    }
                                }else{
                                    // Compare against both user roles and ids
                                    if(!empty($list['delete_own']['user_ids'])) {
                                        foreach( $current_user->roles as $v ) {
                                            if( in_array( $v, $allowed_roles ) ) {
                                                $allowDeleteOwn= true;
                                            }
                                        }
                                    }
                                    if(!empty($list['delete_own']['user_ids'])) {
                                        $allowed_ids = preg_replace('/\s+/', '', $list['delete_own']['user_ids']);
                                        $allowed_ids = explode(",", $allowed_ids);
                                        if( in_array( $current_user->ID, $allowed_ids ) ) {
                                            $allowDeleteOwn= true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $return = array(
                'allowDisplay' => $allowDisplay,
                'allowSeeAny' => $allowSeeAny,
                'allowViewAny' => $allowViewAny,
                'allowViewOwn' => $allowViewOwn,
                'allowEditAny' => $allowEditAny,
                'allowEditOwn' => $allowEditOwn,
                'allowDeleteAny' => $allowDeleteAny,
                'allowDeleteOwn' => $allowDeleteOwn
            );
            return $return;
        }
    }
endif;

/**
 * Returns the main instance of SUPER_Listings to prevent the need to use globals.
 *
 * @return SUPER_Listings
 */
if(!function_exists('SUPER_Listings')){
    function SUPER_Listings() {
        return SUPER_Listings::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Listings'] = SUPER_Listings();
}
