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
        public $version = '1.0.0';


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
            if($args['id']!=='' && $args['list_id']!=='' && $args['entry_id']!==''){
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
                    'form_processing_overlay'=>'',
                    'form_show_thanks_msg'=>'',
                    'form_post_option'=>'',
                    'form_post_url'=>'',
                    'form_redirect_option'=>'',
                    'form_hide_after_submitting'=>'',
                    'form_clear_after_submitting'=>''
                );
                foreach($overrideSettings as $k => $v){
                    $settings[$k] = $v;
                }
            }
            return $settings;
        }

        // Required to change some settings when editing/updating an existing entry via Listings Add-on
        public static function alter_form_settings_before_submit($settings, $args){
            $post = $args['post'];
            // @since 4.7.7 - prevent new Contact Entry from being created
            $entry_id = absint( $post['entry_id'] );
            $list_id = sanitize_text_field( $post['list_id'] );
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
                    'form_processing_overlay'=>'',
                    'form_show_thanks_msg'=>'',
                    'form_post_option'=>'',
                    'form_post_url'=>'',
                    'form_redirect_option'=>'',
                    'form_hide_after_submitting'=>'',
                    'form_clear_after_submitting'=>''
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
                    'name' => esc_html__( 'Title', 'super-forms' ),
                    'meta_key' => 'post_title',
                    'sort' => 'true'
                ),
                'entry_status' => array(
                    'name' => esc_html__( 'Entry status', 'super-forms' ),
                    'meta_key' => 'entry_status',
                    'sort' => 'true'
                ),
                'date' => array(
                    'name' => esc_html__( 'Date', 'super-forms' ),
                    'meta_key' => 'date',
                    'sort' => 'true'
                ),
                'wc_order' => array(
                    'name' => esc_html__( 'WooCommerce order', 'super-forms' ),
                    'meta_key' => 'wc_order',
                    'sort' => 'true'
                ),
                'wc_order_status' => array(
                    'name' => esc_html__( 'WooCommerce order status', 'super-forms' ),
                    'meta_key' => 'wc_order_status',
                    'sort' => 'true'
                ),
                'paypal_order' => array(
                    'name' => esc_html__( 'PayPal order', 'super-forms' ),
                    'meta_key' => 'paypal_order',
                    'sort' => 'true'
                ),
                'paypal_order_status' => array(
                    'name' => esc_html__( 'PayPal order status', 'super-forms' ),
                    'meta_key' => 'paypal_order_status',
                    'sort' => 'true'
                ),
                'paypal_subscription' => array(
                    'name' => esc_html__( 'PayPal subscription', 'super-forms' ),
                    'meta_key' => 'paypal_subscription',
                    'sort' => 'true'
                ),
                'paypal_subscription_status' => array(
                    'name' => esc_html__( 'PayPal subscription status', 'super-forms' ),
                    'meta_key' => 'paypal_subscription_status',
                    'sort' => 'true'
                ),
                'wp_post_title' => array(
                    'name' => esc_html__( 'Created post title', 'super-forms' ),
                    'meta_key' => 'wp_post_title',
                    'sort' => 'true'
                ),
                'wp_post_status' => array(
                    'name' => esc_html__( 'Created post status', 'super-forms' ),
                    'meta_key' => 'wp_post_status',
                    'sort' => 'true'
                ),
                'generated_pdf' => array(
                    'name' => esc_html__( 'Generated PDF', 'super-forms' ),
                    'meta_key' => 'generated_pdf',
                    'sort' => 'false'
                ),
                'author_username' => array(
                    'name' => esc_html__( 'Author username', 'super-forms' ),
                    'meta_key' => 'username',
                    'sort' => 'true'
                ),
                'author_firstname' => array(
                    'name' => esc_html__( 'Author first name', 'super-forms' ),
                    'meta_key' => 'firstname',
                    'sort' => 'true'
                ),
                'author_lastname' => array(
                    'name' => esc_html__( 'Author last name', 'super-forms' ),
                    'meta_key' => 'lastname',
                    'sort' => 'true'
                ),
                'author_fullname' => array(
                    'name' => esc_html__( 'Author full name', 'super-forms' ),
                    'meta_key' => 'fullname',
                    'sort' => 'true'
                ),
                'author_nickname' => array(
                    'name' => esc_html__( 'Author nickname', 'super-forms' ),
                    'meta_key' => 'nickname',
                    'sort' => 'true'
                ),
                'author_display' => array(
                    'name' => esc_html__( 'Author display name', 'super-forms' ),
                    'meta_key' => 'display',
                    'sort' => 'true'
                ),
                'author_email' => array(
                    'name' => esc_html__( 'Author E-mail', 'super-forms' ),
                    'meta_key' => 'email',
                    'sort' => 'true'
                ),
                'author_id' => array(
                    'name' => esc_html__( 'Author ID', 'super-forms' ),
                    'meta_key' => 'id',
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
                'version' => SUPER_Listings()->version,
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
            echo '<div class="super_transient"></div>';
            $form_id = absint($atts['form_id']);
            $slug = 'listings';
            $lists = array();
            if(isset($atts['settings']) && isset($atts['settings']['_'.$slug])){
                $lists = $atts['settings']['_'.$slug]['lists'];
            }
            if(count($lists)==0) {
                $lists[] = self::get_default_listings_settings(array());
            }
            //$response = wp_remote_post(
            //    SUPER_API_ENDPOINT . '/settings/transient',
            //    array(
            //        'method' => 'POST',
            //        'timeout' => 45,
            //        'data_format' => 'body',
            //        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            //        'body' => json_encode(array(
            //            'transient_key' => 'GeltOsu18mZGzkelLWv2',
            //            'home_url' => get_home_url(),
            //            'admin_url' => admin_url()
            //        ))
            //    )
            //);
            //if ( is_wp_error( $response ) ) {
            //    $html .= $response->get_error_message();
            //}else{
            //    // Just an API error/notice/success message or HTML payload
            //    $body = $response['body'];
            //    $response = $response['response'];
            //    $transient = set_transient( 'super_transient', array('check'=>true), 0);
            //    if($response['code']==200 && strpos($body, '{') === 0){
            //        $object = json_decode($body);
            //        if($object->status==200){
            //            $html = $object->body;
            //        }
            //    }
            //}
            //echo $html;

            // Listing general information
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('About', 'super-forms').':</strong> ' . esc_html__( 'Listings allow you to display Contact Entries in a list/table on the front-end. For each form you can have multiple listings with their own settings. You can copy paste the listings shortcode anywhere in your page to display the listing.', 'super-forms' );
            echo '</div>';
            
            // Enable listings
            echo '<div class="sfui-setting">';
                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    echo '<input type="checkbox" name="enabled" value="true" checked="checked" />';
                    echo '<span class="sfui-title">' . esc_html__( 'Enable listings for this form', 'super-forms' ) . '</span>';
                echo '</label>';
                echo '<div class="sfui-sub-settings" data-f="enabled;true">';


            // When enabled, we display the list with listings
            echo '<div class="sfui-repeater" data-k="lists">';
            // Repeater Item
            foreach($lists as $k => $v){
                //// Set default values if they don't exist
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
                        // Display based on
                        echo '<form class="sfui-setting">';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="display_based_on" value="this_form"' . ($v['display_based_on']==='this_form' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Only display entries based on this form', 'super-forms' ) . '</span>';
                                echo '</label>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="display_based_on" value="all_forms"' . ($v['display_based_on']==='all_forms' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Display entries based on all forms', 'super-forms' ) . '</span>';
                                echo '</label>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="display_based_on" value="specific_forms"' . ($v['display_based_on']==='specific_forms' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Display entries based on the following form ID\'s', 'super-forms' ) . ':</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="display_based_on;specific_forms">';
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
                                            echo '<span class="sfui-label">' . esc_html__( 'Till', 'super-forms' ) . ': <i>(' . esc_html__( 'or leave blank for no maximum date', 'super-forms' ) . ')</i></span>';
                                            echo '<input type="date" name="date_range.till" value="' . sanitize_text_field($v['date_range']['till']) . '" />';
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
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_any.method" value="modal"' . ($v['edit_any']['method']==='modal' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Open form in a modal (default)', 'super-forms' ) . '</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_any.method" value="url"' . ($v['edit_any']['method']==='url' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Open via form page (this requires "Form Location" to be defined under "Form Settings")', 'super-forms' ) . '</span>';
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
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_own.method" value="modal"' . ($v['edit_own']['method']==='modal' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Open form in a modal (default)', 'super-forms' ) . '</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_own.method" value="url"' . ($v['edit_own']['method']==='url' ? ' checked="checked"' : '') . ' /><span class="sfui-label">' . esc_html__( 'Open via form page (this requires "Form Location" to be defined under "Form Settings")', 'super-forms' ) . '</span>';
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
                                    echo '<div class="sfui-setting">';
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
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span class="sfui-label">' . esc_html__( 'Column name', 'super-forms' ) . '</span>';
                                                echo '<input type="text" name="'.$sk.'_column.name" value="' . sanitize_text_field($v[$sk.'_column']['name']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span class="sfui-label">' . esc_html__( 'Filter placeholder', 'super-forms' ) . '</span>';
                                                echo '<input type="text" name="'.$sk.'_column.placeholder" value="' . sanitize_text_field($v[$sk.'_column']['placeholder']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                                            echo '<label>';
                                                echo '<span class="sfui-label">' . esc_html__( 'Allow sorting', 'super-forms' ) . '</span>';
                                                echo '<input type="checkbox" name="'.$sk.'_column.sort" value="true"' . ($v[$sk.'_column']['sort']==='true' ? ' checked="checked"' : '') . ' />';
                                            echo '</label>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span class="sfui-label">';
                                                    echo esc_html__( 'Link', 'super-forms' ) . ':';
                                                echo '</span>';
                                                echo '<select name="'.$sk.'_column.link.type" onChange="SUPER.ui.updateSettings(event, this)">';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='none' ? ' selected="selected"' : '') . ' value="none">' . esc_html__( 'None', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='contact_entry' ? ' selected="selected"' : '') . ' value="contact_entry">' . esc_html__( 'Edit the contact entry (backend)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='wc_order_backend' ? ' selected="selected"' : '') . ' value="wc_order_backend">' . esc_html__( 'WooCommerce order (backend)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='wc_order_frontend' ? ' selected="selected"' : '') . ' value="wc_order_frontend">' . esc_html__( 'WooCommerce order (front-end)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='paypal_order' ? ' selected="selected"' : '') . ' value="paypal_order">' . esc_html__( 'PayPal order (backend)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='generated_pdf' ? ' selected="selected"' : '') . ' value="generated_pdf">' . esc_html__( 'Generated PDF file', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='post_backend' ? ' selected="selected"' : '') . ' value="post_backend">' . esc_html__( 'Created post/page (backend)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='post_frontend' ? ' selected="selected"' : '') . ' value="post_frontend">' . esc_html__( 'Created post/page (front-end)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='author_posts' ? ' selected="selected"' : '') . ' value="author_posts">' . esc_html__( 'The author page (front-end)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='author_edit' ? ' selected="selected"' : '') . ' value="author_edit">' . esc_html__( 'URL to edit user page (backend)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='author_email' ? ' selected="selected"' : '') . ' value="author_email">' . esc_html__( 'Link to author E-mail address (href mailto:)', 'super-forms' ) . '</option>';
                                                    echo '<option ' . ($v[$sk.'_column']['link']['type']=='custom' ? ' selected="selected"' : '') . ' value="custom">' . esc_html__( 'Custom URL', 'super-forms' ) . '</option>';
                                                echo '</select>';
                                            echo '</label>';
                                            echo '<div class="sfui-sub-settings" data-f="'.$sk.'_column.link.type;custom">';
                                                echo '<div class="sfui-vertical">';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Enter custom URL (use {tags} if needed)', 'super-forms' ) . ':</span>';
                                                            echo '<input type="text" name="'.$sk.'_column.link.url" value="' . $v[$sk.'_column']['link']['url'] . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting">';
                                                        echo '<label>';
                                                            echo '<input type="checkbox" name="'.$sk.'_column.link.newTab" value="true"' . ($v[$sk.'_column']['link']['newTab']==='true' ? ' checked="checked"' : '') . ' />';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Open in new tab?', 'super-forms' ) . '</span>';
                                                        echo '</label>';
                                                    echo '</div>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                                            echo '<label>';
                                                echo '<span class="sfui-label">' . esc_html__( 'Column width (px)', 'super-forms' ) . '</span>';
                                                echo '<input type="number" name="'.$sk.'_column.width" value="' . sanitize_text_field($v[$sk.'_column']['width']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                                            echo '<label>';
                                                echo '<span class="sfui-label">' . esc_html__( 'Column order', 'super-forms' ) . '</span>';
                                                echo '<input type="number" name="'.$sk.'_column.order" value="' . sanitize_text_field($v[$sk.'_column']['order']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
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
                                                    if( !isset($cv['filter']) ) {
                                                        $cv['filter'] = 'text'; // Default filter to 'text'
                                                    }
                                                    echo '<span class="sfui-sort-up" onclick="SUPER.ui.sortRepeaterItem(this, \'up\')"></span>';
                                                    echo '<span class="sfui-sort-down" onclick="SUPER.ui.sortRepeaterItem(this, \'down\')"></span>';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Column name', 'super-forms' ) . ':</span>';
                                                            echo '<input type="text" name="name" value="' . sanitize_text_field($cv['name']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Map to the following field', 'super-forms' ) . ' <i>(' . esc_html__( 'enter a field name', 'super-forms') .')</i>:</span>';
                                                            echo '<input type="text" name="field_name" value="' . sanitize_text_field($cv['field_name']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Allow sorting', 'super-forms' ) . '</span>';
                                                            echo '<input type="checkbox" name="sort" value="true"' . ($cv['sort']==='true' ? ' checked="checked"' : '') . ' />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">';
                                                                echo esc_html__( 'Filter method', 'super-forms' ) . ':';
                                                            echo '</span>';
                                                            echo '<select name="filter" onChange="SUPER.ui.updateSettings(event, this)">';
                                                                echo '<option '.($cv['filter']=='text' ? ' selected="selected"' : '').' value="text">'.esc_html__( 'Text field (default)', 'super-forms' ).'</option>';
                                                                echo '<option '.($cv['filter']=='dropdown' ? ' selected="selected"' : '').' value="dropdown">'.esc_html__( 'Dropdown', 'super-forms' ).'</option>';
                                                                echo '<option '.($cv['filter']=='none' ? ' selected="selected"' : '').' value="none">'.esc_html__( 'No filter', 'super-forms' ).'</option>';
                                                            echo '</select>';
                                                            echo '<div class="sfui-sub-settings" data-f="filter;dropdown">';
                                                                echo '<div class="sfui-setting sfui-vertical">';
                                                                    echo '<label>';
                                                                        echo '<span class="sfui-label">' . esc_html__( 'Filter options', 'super-forms' ) . ' <i>(' . esc_html__( 'put each on a new line', 'super-forms') .')</i>:</span>';
                                                                        echo '<textarea name="filter_items" placeholder="' . esc_attr__( "option_value1|Option Label 1\noption_value2|Option Label 2", 'super-forms') . '">' . $cv['filter_items'] . '</textarea>';
                                                                    echo '</label>';
                                                                echo '</div>';
                                                            echo '</div>';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Column width', 'super-forms' ) . ' <i>(' . esc_html__( 'in', 'super-forms') .' px)</i>:</span>';
                                                            echo '<input type="number" name="width" value="' . sanitize_text_field($cv['width']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical" style="flex:0.5;">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Column order', 'super-forms' ) . '</span>';
                                                            echo '<input type="number" name="order" value="' . absint($cv['order']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
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

        // Get default listing settings
        public static function get_default_listings_settings($list) {
            if(empty($list['name'])) $list['name'] = 'Listing #1';
            if(empty($list['noResultsFilterMessage'])) $list['noResultsFilterMessage'] = file_get_contents('templates/no_results_filter_message.html', true);
            if(empty($list['noResultsMessage'])) $list['noResultsMessage'] = file_get_contents('templates/no_results_message.html', true);
            if(empty($list['onlyDisplayMessage'])) $list['onlyDisplayMessage'] = 'true';
            if(empty($list['display_based_on'])) $list['display_based_on'] = 'this_form';
            if(empty($list['form_ids'])) $list['form_ids'] = '';
            if(empty($list['date_range'])) $list['date_range'] = array(
                'enabled'=>'false',
                'from'=>'',
                'till'=>''
            );
            if(empty($list['title_column'])) $list['title_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Title', 'super-forms' ),
                'placeholder' => esc_html__( 'Filter by title', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 150
            );
            if(empty($list['entry_status_column'])) $list['entry_status_column'] = array(
                'enabled'=>'true',
                'name'=>esc_html__( 'Entry status', 'super-forms' ),
                'placeholder'=>esc_html__( '- choose status -', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 20,
                'width' => 150
            );
            if(empty($list['date_column'])) $list['date_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Date created', 'super-forms' ),
                'placeholder' => esc_html__( 'Filter by date', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 30,
                'width' => 150
            );
            if(empty($list['generated_pdf_column'])) $list['generated_pdf_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'PDF File', 'super-forms' ),
                'placeholder' => esc_html__( 'PDF File', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );

            if(empty($list['wc_order_column'])) $list['wc_order_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'WooCommerce Order', 'super-forms' ),
                'placeholder' => esc_html__( 'WooCommerce Order', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );
            if(empty($list['wc_order_status_column'])) $list['wc_order_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'WooCommerce Order Status', 'super-forms' ),
                'placeholder' => esc_html__( 'WooCommerce Order Status', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );
            if(empty($list['paypal_order_column'])) $list['paypal_order_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Order number', 'super-forms' ),
                'placeholder' => esc_html__( 'Order number', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );
            if(empty($list['paypal_order_status_column'])) $list['paypal_order_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Order status', 'super-forms' ),
                'placeholder' => esc_html__( 'Order status', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );
            if(empty($list['paypal_subscription_column'])) $list['paypal_subscription_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Subscription', 'super-forms' ),
                'placeholder' => esc_html__( 'Subscription', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );
            if(empty($list['paypal_subscription_status_column'])) $list['paypal_subscription_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Subscription status', 'super-forms' ),
                'placeholder' => esc_html__( 'Subscription status', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );
            if(empty($list['wp_post_title_column'])) $list['wp_post_title_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Post title', 'super-forms' ),
                'placeholder' => esc_html__( 'Post title', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );
            if(empty($list['wp_post_status_column'])) $list['wp_post_status_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Post status', 'super-forms' ),
                'placeholder' => esc_html__( 'Post status', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 40,
                'width' => 150
            );

            if(empty($list['author_username_column'])) $list['author_username_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Username', 'super-forms' ),
                'placeholder' => esc_html__( 'Username', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 50,
                'width' => 150
            );
            if(empty($list['author_firstname_column'])) $list['author_firstname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'First name', 'super-forms' ),
                'placeholder' => esc_html__( 'First name', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 60,
                'width' => 150
            );
            if(empty($list['author_lastname_column'])) $list['author_lastname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Last name', 'super-forms' ),
                'placeholder' => esc_html__( 'Last name', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 70,
                'width' => 150
            );
            if(empty($list['author_fullname_column'])) $list['author_fullname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Full name', 'super-forms' ),
                'placeholder' => esc_html__( 'Full name', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 80,
                'width' => 150
            );
            if(empty($list['author_nickname_column'])) $list['author_nickname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Nickname', 'super-forms' ),
                'placeholder' => esc_html__( 'Nickname', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 90,
                'width' => 150
            );
            if(empty($list['author_display_column'])) $list['author_display_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Display name', 'super-forms' ),
                'placeholder' => esc_html__( 'Display name', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 100,
                'width' => 150
            );
            if(empty($list['author_email_column'])) $list['author_email_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'E-mail', 'super-forms' ),
                'placeholder' => esc_html__( 'E-mail', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 110,
                'width' => 150
            );
            if(empty($list['author_id_column'])) $list['author_id_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Author ID', 'super-forms' ),
                'placeholder' => esc_html__( 'Author ID', 'super-forms' ),
                'sort' => 'true',
                'link' => array(
                    'newTab' => 'true',
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 120,
                'width' => 150
            );
            if(empty($list['custom_columns']) ) $list['custom_columns'] = array(
                'enabled' => 'false',
                'columns' => array(
                    array(
                        'filter' => 'text', // text, dropdown, none
                        'name' => 'E-mail',
                        'field_name' => 'email',
                        'sort' => 'true',
                        'width' => 150,
                        'filter_items' => '',
                        'order' => 1
                    )
                ),
            );
            // See any permissions
            if( empty($list['see_any']) ) $list['see_any'] = array(
                'enabled'=>'true',
                'user_roles'=>'administrator',
                'user_ids'=>''
            );
            // View permissions
            $html_template = file_get_contents('templates/view_any_html_template.html', true);
            $loop_html = file_get_contents('templates/view_any_loop_html.html', true);
            if( empty($list['view_any']) ) $list['view_any'] = array(
                'enabled'=>'true',
                'method'=>'modal',
                'user_roles'=>'administrator',
                'user_ids'=>'',
                'html_template' => $html_template,
                'loop_html' => $loop_html
            );
            $html_template = file_get_contents('templates/view_own_html_template.html', true);
            $loop_html = file_get_contents('templates/view_own_loop_html.html', true);
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

            // Enqueue scripts and styles
            $handle = 'super-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common.js', array( 'jquery' ), SUPER_VERSION, false );  

            // WPML langauge parameter to ajax URL's required for for instance when redirecting to WooCommerce checkout/cart page
            $ajax_url = SUPER_Forms()->ajax_url();
            $my_current_lang = apply_filters( 'wpml_current_language', NULL ); 
            if ( $my_current_lang ) $ajax_url = add_query_arg( 'lang', $my_current_lang, $ajax_url );

            if(!isset($settings['file_upload_image_library'])) $settings['file_upload_image_library'] = 0;
            $image_library = absint($settings['file_upload_image_library']);            

            wp_localize_script(
                $handle,
                $name,
                array( 
                    'ajaxurl'=>$ajax_url,
                    'preload'=>$settings['form_preload'],
                    'duration'=>$settings['form_duration'],
                    'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                    'loading'=>SUPER_Forms()->common_i18n['loading'],
                    'tab_index_exclusion' => SUPER_Forms()->common_i18n['tab_index_exclusion'],
                    'directions'=>SUPER_Forms()->common_i18n['directions'],
                    'errors'=>SUPER_Forms()->common_i18n['errors'],
                    // @since 3.6.0 - google tracking
                    'ga_tracking' => ( !isset( $settings['form_ga_tracking'] ) ? "" : $settings['form_ga_tracking'] ),
                    'image_library' => $image_library,  
                )
            );
            wp_enqueue_script( $handle );


            // Enqueue scripts and styles
            $handle = 'super-listings';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'assets/js/frontend/script.js', array( 'super-common' ), SUPER_Listings()->version, false );  
            wp_localize_script(
                $handle,
                $name,
                array( 
                    'get_home_url' => get_home_url(),
                    'ajaxurl' => $ajax_url
                )
            );
            wp_enqueue_script( $handle );
            // wp_enqueue_script( 'super-listings', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/script.js', array( 'super-common' ), $this->version, false );  
            
            wp_enqueue_style( 'super-listings', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/styles.css', array(), SUPER_Listings()->version );
            SUPER_Forms()->enqueue_fontawesome_styles();

            // Get the settings for this specific list based on it's index
            $list_id = absint($atts['list'])-1;
            $lists = $settings['_listings']['lists'];
            if(!isset($lists[$list_id])){
                // The list does not exist
                $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with ID: %d', 'super-forms' ), $list_id);
                return $result;
            }
            // Set default values if they don't exist
            $list = self::get_default_listings_settings($lists[$list_id]);

            $columns = array(); 
            $standardColumns = self::getStandardColumns();
            foreach($standardColumns as $sk => $sv){
                if( $list[$sk.'_column']['enabled']==='true' ) {
                    $columns[$sv['meta_key']] = array(
                        'order' => absint($list[$sk.'_column']['order']),
                        'name' => $list[$sk.'_column']['name'],
                        'width' => absint($list[$sk.'_column']['width']),
                        'sort' => 'true',
                        'link' => array(
                            'newTab' => 'true',
                            'type' => 'none',
                            'url' => ''
                        ),
                        'filter' => array(
                            'field_type' => 'text',
                            'placeholder' => $list[$sk.'_column']['placeholder']
                        )
                    );
                    if($sk=='date'){ // Entry date (form submission date) 
                        $columns[$sv['meta_key']]['filter'] = array(
                            'field_type' => 'datepicker',
                            'placeholder' => $list[$sk.'_column']['placeholder']
                        );
                    }
                    if($sk=='generated_pdf'){
                        $columns[$sv['meta_key']]['filter']['field_type'] = 'none';
                        $columns[$sv['meta_key']]['sort'] = 'none';
                    }
                    if($sk=='wc_order_status'){
                        $items = array(
                            '' => esc_html__( '- select -', 'super-forms' )
                        );
                        $wc_order_statuses = wc_get_order_statuses();
                        $items = array_merge($items, $wc_order_statuses);      
                        $columns[$sv['meta_key']]['filter'] = array(
                            'field_type' => 'dropdown',
                            'placeholder' => $list[$sk.'_column']['placeholder'],
                            'items' => $items
                        );
                    }
                    if($sk=='wp_post_status'){
                        $items = array(
                            '' => esc_html__( '- select -', 'super-forms' )
                        );
                        $items = array_merge($items, get_post_statuses());      
                        $columns[$sv['meta_key']]['filter'] = array(
                            'field_type' => 'dropdown',
                            'placeholder' => $list[$sk.'_column']['placeholder'],
                            'items' => $items
                        );
                    }
                    if($sk=='entry_status'){
                        $items = array();
                        foreach(SUPER_Settings::get_entry_statuses() as $k => $v){
                            $items[$k] = $v['name']; 
                        }
                        $items[''] = esc_html__( '- select -', 'super-forms' );
                        $columns[$sv['meta_key']]['filter'] = array(
                            'field_type' => 'dropdown',
                            'placeholder' => $list[$sk.'_column']['placeholder'],
                            'items' => $items
                        );
                    }
                    // If link available
                    if(isset($list[$sk.'_column']['link'])){
                        $columns[$sv['meta_key']]['link'] = $list[$sk.'_column']['link'];
                    }
                }
            }

            // Add custom columns if enabled
            if($list['custom_columns']['enabled']==='true'){
                $columns = array_merge($columns, $list['custom_columns']['columns']);      
            }

            // Now re-order all columns based on order number
            array_multisort(array_column($columns, 'order'), SORT_ASC, $columns);

            // For now these are the columns:
            // billing_company|Entity
            // post_title|Order Name
            // total_monthly_payment|Monthly
            // lease_months|Lease Term
            // I will probably need 1-2 more but need to figure out a few things first

            // $custom_columns = array(
            //     'billing_company' => array(
            //         'name' => 'Entity',
            //         'width' => '100px',
            //     ),
            //     'total_monthly_payment' => array(
            //         'name' => 'Monthly',
            //         'width' => '100px',
            //     ),
            //     'lease_months' => array(
            //         'name' => 'Lease Term',
            //         'width' => '130px',
            //     )
            // );
            // $columns = array_merge($columns, $custom_columns);

            // Filters by user
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
                                $filter_by_entry_data = ", SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value, 's:4:\"name\";s:$fckLength:\"$fck\";s:5:\"value\";', -1), '\";s:', 1), ':\"', -1) AS filterValue";
                                $having .= " HAVING filterValue LIKE '%$fcv%'";
                                break;
                            }
                        }
                    }
                }else{
                    // Filter by default column
                    if($fck=='date'){
                        $dateFilter = explode(';', $fcv);
                        if(!empty($dateFilter[1])){
                            $from = $dateFilter[0];
                            $till = $dateFilter[1];
                            $filters .= " post.post_date BETWEEN CAST('$from' AS DATE) AND CAST('$till' AS DATE)";
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
                        $filters .= ' wc_order.post_status LIKE "' . $fcv . '"';
                    }elseif($fck=='post_author'){
                        if( !empty($filters) ) $filters .= ' AND';
                        $filters .= ' post.post_author = "' . $fcv . '"';
                    }else{
                        //if( !empty($filters) ) $filters .= ' AND';
                        //$filters .= " $fck LIKE '%$fcv%'"; // Filter globally
                    }
                }
            }

            // Check if custom sort was choosen by the user
            $sc = 'post_date'; // sort column (defaults to 'date')
            if( !empty($_GET['sc']) ) {
                $sc = sanitize_text_field($_GET['sc']);
                if($sc==='date') $sc = 'post_date';
            }

            $order_by_entry_data = "";
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
            if( $list['display_based_on']=='this_form' ) {
                $where .= " AND post.post_parent = '" . absint($form_id) . "'";
                $whereWithoutFilters .= " AND post.post_parent = '" . absint($form_id) . "'";
            }
            
            $allow = self::get_action_permissions(array('list'=>$list));
            if($allow['allowSeeAny']===true){
                // Allow user to see any entries in the list
            }else{
                // Only allow to see entries that belong to the currently logged in user
                $where .= ' AND post.post_author = "' . absint( $current_user->ID ) . '"';
                $whereWithoutFilters .= ' AND post.post_author = "' . absint( $current_user->ID ) . '"';
            }

            if( !empty($filters) ) {
                $where .= ' AND (' . $filters . ')';
            }

            // temp disabled // Custom field filters
            // temp disabled $custom_fields_filters = '';
            // temp disabled foreach( $_GET as $k => $v ) {
            // temp disabled     // Check if this is a custom field, by checking if first character of the string is a _ "underscore"
            // temp disabled     if(substr($k, 0, 1)=='_'){
            // temp disabled         // Get field_name of this custom column by removing the _ "underscore"
            // temp disabled         $field_name = sanitize_text_field(substr($k, 1));
            // temp disabled         $value = sanitize_text_field($v);
            // temp disabled         if( !empty($custom_fields_filters) ) $custom_fields_filters .= ' OR';
            // temp disabled         $custom_fields_filters .= ' meta.meta_value REGEXP \'.*s:4:"name";s:[0-9]+:"' . $field_name . '";s:5:"value";s:[0-9]+:"(' . $value . ')";\'';
            // temp disabled     }
            // temp disabled }
            // temp disabled if( !empty($custom_fields_filters) ) {
            // temp disabled     $where .= ' AND (' . $custom_fields_filters . ')';
            // temp disabled }

            $count_query = "SELECT COUNT(post_id) AS total
            FROM (
                SELECT 
                post.ID AS post_id, 
                post.post_author AS post_author, 
                post.post_title AS post_title, 
                post.post_date AS post_date, 
                meta.meta_value AS contact_entry_data,
                entry_status.meta_value AS status,
                created_post.post_status AS created_post_status,
                created_post.post_title AS created_post_title, 
                wc_order.post_status AS wc_order_status, 
                wc_order.ID AS wc_order_number,
                first_name.meta_value AS first_name,
                last_name.meta_value AS last_name,
                nickname.meta_value AS nickname,
                author.user_login AS username,
                author.user_email AS email, 
                author.display_name AS display_name 
                $order_by_entry_data
                $filter_by_entry_data 
                FROM $wpdb->posts AS post 
                INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
                LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
                LEFT JOIN $wpdb->postmeta AS created_post_connection ON created_post_connection.post_id = post.ID AND created_post_connection.meta_key = '_super_created_post'
                LEFT JOIN $wpdb->posts AS created_post ON created_post.ID = created_post_connection.meta_value
                LEFT JOIN $wpdb->postmeta AS wc_order_connection ON wc_order_connection.post_id = post.ID AND wc_order_connection.meta_key = '_super_contact_entry_wc_order_id' 
                LEFT JOIN $wpdb->posts AS wc_order ON wc_order.ID = wc_order_connection.meta_value 
                LEFT JOIN $wpdb->users AS author ON author.ID = post.post_author
                LEFT JOIN $wpdb->usermeta AS first_name ON first_name.user_id = post.post_author AND first_name.meta_key = 'first_name'
                LEFT JOIN $wpdb->usermeta AS last_name ON last_name.user_id = post.post_author AND last_name.meta_key = 'last_name'
                LEFT JOIN $wpdb->usermeta AS nickname ON nickname.user_id = post.post_author AND nickname.meta_key = 'nickname'
                WHERE post.post_type = 'super_contact_entry'
                $where
                $having
            ) a";
            $results_found = $wpdb->get_var($count_query);
            $count_without_filters_query = "SELECT COUNT(post_id) AS total
            FROM (
                SELECT 
                post.ID AS post_id,
                post.post_author AS post_author,
                post.post_title AS post_title, 
                post.post_date AS post_date,
                meta.meta_value AS contact_entry_data,
                entry_status.meta_value AS status,
                created_post.post_status AS created_post_status,
                created_post.post_title AS created_post_title, 
                wc_order.post_status AS wc_order_status, 
                wc_order.ID AS wc_order_number,
                first_name.meta_value AS first_name,
                last_name.meta_value AS last_name,
                nickname.meta_value AS nickname, 
                author.user_login AS username,
                author.user_email AS email,
                author.display_name AS display_name 
                FROM $wpdb->posts AS post 
                INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
                LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
                LEFT JOIN $wpdb->postmeta AS created_post_connection ON created_post_connection.post_id = post.ID AND created_post_connection.meta_key = '_super_created_post'
                LEFT JOIN $wpdb->posts AS created_post ON created_post.ID = created_post_connection.meta_value
                LEFT JOIN $wpdb->postmeta AS wc_order_connection ON wc_order_connection.post_id = post.ID AND wc_order_connection.meta_key = '_super_contact_entry_wc_order_id' 
                LEFT JOIN $wpdb->posts AS wc_order ON wc_order.ID = wc_order_connection.meta_value 
                LEFT JOIN $wpdb->users AS author ON author.ID = post.post_author
                LEFT JOIN $wpdb->usermeta AS first_name ON first_name.user_id = post.post_author AND first_name.meta_key = 'first_name'
                LEFT JOIN $wpdb->usermeta AS last_name ON last_name.user_id = post.post_author AND last_name.meta_key = 'last_name'
                LEFT JOIN $wpdb->usermeta AS nickname ON nickname.user_id = post.post_author AND nickname.meta_key = 'nickname'
                WHERE post.post_type = 'super_contact_entry'
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
            post.ID AS post_id, 
            post.post_author AS post_author,
            post.post_title AS post_title,
            post.post_date AS post_date,
            meta.meta_value AS contact_entry_data,
            entry_status.meta_value AS status,
            created_post.post_status AS created_post_status,
            created_post.post_title AS created_post_title, 
            wc_order.post_status AS wc_order_status, 
            wc_order.ID AS wc_order_number,
            first_name.meta_value AS first_name,
            last_name.meta_value AS last_name,
            nickname.meta_value AS nickname,
            author.user_login AS username, 
            author.user_email AS email, 
            author.display_name AS display_name
            $order_by_entry_data
            $filter_by_entry_data
            FROM $wpdb->posts AS post 
            INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
            LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
            LEFT JOIN $wpdb->postmeta AS created_post_connection ON created_post_connection.post_id = post.ID AND created_post_connection.meta_key = '_super_created_post'
            LEFT JOIN $wpdb->posts AS created_post ON created_post.ID = created_post_connection.meta_value
            LEFT JOIN $wpdb->postmeta AS wc_order_connection ON wc_order_connection.post_id = post.ID AND wc_order_connection.meta_key = '_super_contact_entry_wc_order_id' 
            LEFT JOIN $wpdb->posts AS wc_order ON wc_order.ID = wc_order_connection.meta_value 
            LEFT JOIN $wpdb->users AS author ON author.ID = post.post_author
            LEFT JOIN $wpdb->usermeta AS first_name ON first_name.user_id = post.post_author AND first_name.meta_key = 'first_name'
            LEFT JOIN $wpdb->usermeta AS last_name ON last_name.user_id = post.post_author AND last_name.meta_key = 'last_name'
            LEFT JOIN $wpdb->usermeta AS nickname ON nickname.user_id = post.post_author AND nickname.meta_key = 'nickname'
            WHERE post.post_type = 'super_contact_entry'
            $where
            $having
            ORDER BY $order_by
            LIMIT $limit
            OFFSET $offset
            ";
            var_dump($query);
            $entries = $wpdb->get_results($query);

            $result = '';
            $result .= '<div class="super-listings" data-form-id="'.absint($form_id).'" data-list-id="'.absint($list_id).'">';
                //$result .= '<div class="super-header">';
                //    $result .= '<div class="super-select-all">';
                //        $result .= esc_html__( 'Select All', 'super-forms' );
                //    $result .= '</div>';
                //    $result .= '<div class="super-csv-export">';
                //        $result .= esc_html__( 'CSV Export', 'super-forms' );
                //    $result .= '</div>';
                //$result .= '</div>';
                $result .= '<div class="super-listings-wrap">';
                    if($absoluteZeroResults===true && $list['onlyDisplayMessage']==='true'){
                        // Do not show filters/columns
                    }else{
                        $result .= '<div class="super-columns">';
                            $entry = array();
                            if( isset($entries[0]) ) {
                                $entry = $entries[0];
                            }
                            $actions = '';
                            $actions .= '<span class="super-edit"></span>';
                            $actions .= '<span class="super-view"></span>';
                            $actions .= '<span class="super-delete"></span>';
                            if( ($list['view_any']['enabled']!=='true') &&
                                ($list['view_own']['enabled']!=='true') &&
                                ($list['edit_any']['enabled']!=='true') &&
                                ($list['edit_own']['enabled']!=='true') &&
                                ($list['delete_any']['enabled']!=='true') &&
                                ($list['delete_own']['enabled']!=='true') ){
                                $actions = '';
                            }
                            $actionsHeader = apply_filters( 'super_listings_actions_filter', $actions, $entry );
                            if(!empty($actionsHeader)){
                                $result .= '<div class="super-col super-actions">';
                                    $result .= $actions;
                                $result .= ' </div>';
                            }
                            foreach( $columns as $k => $v ) {
                                // If a max width was defined use it on the col-wrap
                                $styles = '';
                                if( !empty( $v['width'] ) ) {
                                    $styles = 'width:' . $v['width'] . 'px;';
                                }
                                if( !empty( $styles ) ) {
                                    $styles = ' style="' . $styles . '"';
                                }
                                if( isset($v['filter']) && is_array($v['filter']) ) {
                                    $column_name = $k;
                                }else{
                                    $column_name = '_' . $v['field_name']; // Custom columns are prefixed with a underscore for easy distinguishing
                                }

                                // Check if a filter was set for this column
                                $inputValue = (!empty($_GET['fc_'.$column_name]) ? sanitize_text_field($_GET['fc_'.$column_name]) : '');
                                $result .= '<div class="super-col-wrap" data-name="' . $column_name . '"' . $styles . '>';
                                    $result .= '<span class="super-col-name">' . $v['name'] . '</span>';
                                    $result .= '<div class="super-col-sort">';
                                        $result .= '<span class="super-sort-down" onclick="SUPER.frontEndListing.sort(event, this)">↓</span>';
                                        $result .= '<span class="super-sort-up" onclick="SUPER.frontEndListing.sort(event, this)">↑</span>';
                                    $result .= '</div>';
                                    if( isset($v['filter']) && is_array($v['filter']) ) {
                                        $result .= '<div class="super-col-filter">';
                                            if($v['filter']['field_type']=='text'){
                                                $result .= '<input value="' . $inputValue . '" autocomplete="new-password" name="' . $k . '" type="text" placeholder="' . $v['filter']['placeholder'] . '" />';
                                                $result .= '<span class="super-search" onclick="SUPER.frontEndListing.search(event, this)"></span>';
                                            }
                                            if($v['filter']['field_type']=='datepicker'){
                                                $fromTill = explode(';', $inputValue);
                                                $from = (isset($fromTill[0]) ? $fromTill[0] : '');
                                                $till = (isset($fromTill[1]) ? $fromTill[1] : '');
                                                $result .= '<input'.(!empty($v['width']) ? ' style="width:'.(absint($v['width'])/2-2).'px;"' : '').' value="' . $from . '" autocomplete="new-password" name="' . $k . '_from" type="date" placeholder="' . $v['filter']['placeholder'] . '"  onchange="SUPER.frontEndListing.search(event, this)" />';
                                                $result .= '<input'.(!empty($v['width']) ? ' style="width:'.(absint($v['width'])/2-2).'px;margin-left:4px;"' : '').' value="' . $till . '" autocomplete="new-password" name="' . $k . '_till" type="date" placeholder="' . $v['filter']['placeholder'] . '"  onchange="SUPER.frontEndListing.search(event, this)" />';
                                            }
                                            if($v['filter']['field_type']=='dropdown'){
                                                $result .= '<select name="' . $k . '" placeholder="' . $v['filter']['placeholder'] . '" onchange="SUPER.frontEndListing.search(event, this)">';
                                                    foreach( $v['filter']['items'] as $value => $name ) {
                                                        $result .= '<option value="' . $value . '"' . ( $inputValue==$value ? ' selected="selected"' : '' ) . '>' . $name . '</option>';
                                                    }
                                                $result .= '</select>';
                                            }
                                        $result .= '</div>';
                                    }else{
                                        // It's a custom column, find out what filter method to use
                                        $result .= '<div class="super-col-filter">';
                                            if( !isset($v['filter']) ) $v['filter'] = 'text';
                                            if( $v['filter']=='text' ) {
                                                $result .= '<input value="' . $inputValue . '" autocomplete="new-password" type="text" name="' . $v['field_name'] . '" placeholder="' . esc_attr__( 'Filter...', 'super-forms' ) . '" />';
                                                $result .= '<span class="super-search" onclick="SUPER.frontEndListing.search(event, this)"></span>';
                                            }
                                            if( $v['filter']=='dropdown' ) {
                                                $result .= '<select name="' . $k . '" onchange="SUPER.frontEndListing.search(event, this)">';
                                                    $result .= '<option value=""' . ( empty($inputValue) ? ' selected="selected"' : '' ) . '>' . esc_html__( '- filter -', 'super-forms' ) . '</option>';
                                                    $filter_items = explode("\n", $v['filter_items']);
                                                    foreach( $filter_items as $value ) {
                                                        $value = explode('|', $value);
                                                        $label = (isset($value[1]) ? $value[1] : 'undefined');
                                                        $value = (isset($value[0]) ? $value[0] : 'undefined');
                                                        $result .= '<option value="' . $value . '"' . ( $inputValue==$value ? ' selected="selected"' : '' ) . '>' . $label . '</option>';
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
                        $result .= '<div class="super-no-results">'.$list['noResultsMessage'].'</div>';
                    }
                    $result .= '<div class="super-entries">';
                        if(count($entries)===0 && !$absoluteZeroResults){
                            $result .= '<div class="super-no-results-filter">'.$list['noResultsFilterMessage'].'</div>';
                        }else{
                            $result .= '<div class="super-scroll"></div>';
                            if( !class_exists( 'SUPER_Settings' ) ) require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
                            $global_settings = SUPER_Common::get_global_settings();
                            $entry_statuses = SUPER_Settings::get_entry_statuses($global_settings);
                            $wp_post_statuses = get_post_statuses();
                            $wc_order_statuses = wc_get_order_statuses();
                            foreach($entries as $entry){
                                $data = unserialize($entry->contact_entry_data);
                                $result .= '<div class="super-entry" data-id="' . $entry->post_id . '">';
                                    $result .= '<div class="super-col super-check"></div>';
                                    if(!empty($actionsHeader)){
                                        $result .= '<div class="super-col super-actions">';
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
                                            $result .= apply_filters( 'super_listings_actions_filter', $actions, $entry );
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
                                            $authorData = get_userdata($entry->post_author);
                                            if(!empty($cv['link']) && $cv['link']['type']!='none'){
                                                if($cv['link']['type']=='custom'){
                                                    // Custom URL
                                                    $result .= esc_html($cv['link']['url']);
                                                }elseif($cv['link']['type']=='wc_order'){
                                                    // WooCommerce order (WC Checkout Add-on)
                                                }elseif($cv['link']['type']=='generated_pdf'){
                                                    // Generated PDF file (PDF Generator Add-on)
                                                }elseif($cv['link']['type']=='created_post'){
                                                    // Created post (Front-end Posting Add-on)
                                                }elseif($cv['link']['type']=='author_posts'){
                                                    // Link to author page
                                                    if($authorData) $result .= '<a'.($cv['link']['newTab']==='true' ? ' target="_blank"' : '') .'  href="'.get_author_posts_url($entry->post_author).'">';
                                                }elseif($cv['link']['type']=='author_edit'){
                                                    // Link to edit user
                                                    if($authorData) $result .= '<a'.($cv['link']['newTab']==='true' ? ' target="_blank"' : '') .' href="'.get_edit_user_link($entry->post_author).'">';
                                                }elseif($cv['link']['type']=='author_email'){
                                                    // Link to mail directly to the author E-mail address
                                                    if($authorData) $result .= '<a'.($cv['link']['newTab']==='true' ? ' target="_blank"' : '') .'  href="mailto:'.$authorData->user_email.'">';
                                                }
                                            }
                                            if($column_key=='post_title'){
                                                $result .= $entry->post_title;
                                            }elseif($authorData && $column_key=='username'){
                                                $result .= $authorData->user_login;
                                            }elseif($authorData && $column_key=='firstname'){
                                                $result .= $authorData->user_firstname;
                                            }elseif($authorData && $column_key=='lastname'){
                                                $result .= $authorData->user_lastname;
                                            }elseif($authorData && $column_key=='fullname'){
                                                $result .= $authorData->user_firstname.' '.$authorData->user_lastname;
                                            }elseif($authorData && $column_key=='nickname'){
                                                $result .= $authorData->nickname;
                                            }elseif($authorData && $column_key=='display'){
                                                $result .= $authorData->display_name;
                                            }elseif($authorData && $column_key=='email'){
                                                $result .= $authorData->user_email;
                                            }elseif($authorData && $column_key=='id'){
                                                $result .= $authorData->ID;
                                            }elseif($column_key=='entry_status'){
                                                if( (isset($entry_statuses[$entry->status])) && ($entry->status!='') ) {
                                                    $result .= '<span class="super-entry-status super-entry-status-' . $entry->status . '" style="color:' . $entry_statuses[$entry->status]['color'] . ';background-color:' . $entry_statuses[$entry->status]['bg_color'] . '">' . $entry_statuses[$entry->status]['name'] . '</span>';
                                                }else{
                                                    $post_status = get_post_status($entry->post_id);
                                                    if($post_status=='super_read'){
                                                        $result .= '<span class="super-entry-status super-entry-status-' . $post_status . '" style="background-color:#d6d6d6;">' . esc_html__( 'Read', 'super-forms' ) . '</span>';
                                                    }else{
                                                        $result .= '<span class="super-entry-status super-entry-status-' . $post_status . '">' . esc_html__( 'Unread', 'super-forms' ) . '</span>';
                                                    }
                                                }
                                            }elseif($column_key=='wp_post_title'){
                                                $post_id = get_post_meta( $entry->post_id, '_super_created_post', true );
                                                if(!empty($post_id)) $result .= get_the_title( $post_id );
                                            }elseif($column_key=='wp_post_status'){
                                                $post_id = get_post_meta( $entry->post_id, '_super_created_post', true );
                                                if(!empty($post_id)) {
                                                    $status = get_post_status($post_id);
                                                    $result .= $wp_post_statuses[$status];
                                                }
                                            }elseif($column_key=='generated_pdf'){
                                                if( isset( $data['_generated_pdf_file']['files'] ) ) {
                                                    foreach( $data['_generated_pdf_file']['files'] as $fk => $fv ) {
                                                        $url = $fv['url'];
                                                        if( !empty( $fv['attachment'] ) ) { // only if file was inserted to Media Library
                                                            $url = wp_get_attachment_url( $fv['attachment'] );
                                                        }
                                                        if($fk>0) echo '<br />';
                                                        if(!empty($url)){
                                                            $result .= '<a class="super-file" target="_blank" href="' . esc_url( $url ) . '">';
                                                        }
                                                        $result .= esc_html( $fv['value'] ); // The filename
                                                        if(!empty($url)){
                                                            $result .= '</a>';
                                                        }
                                                    }
                                                }
                                            }elseif($column_key=='wc_order'){
                                                $order_id = get_post_meta( $entry->post_id, '_super_contact_entry_wc_order_id', true );
                                                if(!empty($order_id)){
                                                    $order_id = absint($order_id);
                                                    if( $order_id!=0 ) $result .= '<a href="' . esc_url(get_admin_url() . 'post.php?post=' . $order_id . '&action=edit') . '">#' . $order_id . '</a>';
                                                }
                                            }elseif($column_key=='wc_order_status'){
                                                $order_id = get_post_meta( $entry->post_id, '_super_contact_entry_wc_order_id', true );
                                                if(!empty($order_id)){
                                                    $order_id = absint($order_id);
                                                    $order = wc_get_order( $order_id );
                                                    $order_status  = $order->get_status();
                                                    if($order_id!=0) $result .= '<mark class="order-status status-'.$order_status.' tips"><span>'.$wc_order_statuses['wc-'.$order_status].'</span></mark>';
                                                }
                                            }elseif($column_key=='date'){
                                                $date = date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) );
                                                $time = ' @ ' . date_i18n( get_option( 'time_format' ), strtotime( $entry->post_date ) );
                                                $result .= apply_filters( 'super_listings_date_filter', $date.$time, $entry );
                                            }else{
                                                // Check if this data key exists
                                                if(isset($data[$column_key])){
                                                    // Check if it has a value, if so print it
                                                    if(isset($data[$column_key]['value'])){
                                                        if ( strpos( $data[$column_key]['value'], 'data:image/png;base64,') !== false ) {
                                                            // @IMPORTANT, escape the Data URL but make sure add it as an acceptable protocol 
                                                            // otherwise the signature will not be displayed
                                                            $imgUrl = esc_url( $data[$column_key]['value'], array( 'data' ) );
                                                            $result .= '<a href="' . $imgUrl . '" download>';
                                                            $result .= '<img class="super-signature" src="' . $imgUrl . '" />';
                                                            $result .= '</a>';
                                                        }else{
                                                            $result .= esc_html($data[$column_key]['value']);
                                                        }
                                                    }else{
                                                        // If not then it must be a special field, for instance file uploads
                                                        if($data[$column_key]['type']==='files'){
                                                            if(isset($data[$column_key]['files'])){
                                                                $files = $data[$column_key]['files'];
                                                                foreach($files as $fk => $fv){
                                                                    $url = $fv['url'];
                                                                    if( !empty( $fv['attachment'] ) ) { // only if file was inserted to Media Library
                                                                        $url = wp_get_attachment_url( $fv['attachment'] );
                                                                    }
                                                                    if(!empty($url)){
                                                                        $result .= '<a target="_blank" download href="' . esc_url( $url ) . '">';
                                                                    }
                                                                    $result .= esc_html( $fv['value'] ); // The filename
                                                                    if(!empty($url)){
                                                                        $result .= '</a>';
                                                                    }
                                                                    $result .= '<br />';
                                                                }
                                                            }else{
                                                                $result .= esc_html__( 'No files uploaded', 'super-forms' );
                                                            }
                                                        }
                                                    }
                                                }else{
                                                    // No data found for this entry
                                                    $result .= '';
                                                }
                                            }
                                            if($authorData && (!empty($cv['link']) && $cv['link']!='none')){
                                                $result .= '</a>';
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
            return $result;
        }
        public static function get_action_permissions($atts){
            global $current_user;
            $list = $atts['list'];
            $entry = (isset($atts['entry']) ? $atts['entry'] : null);

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
                    if(absint($current_user->ID) === absint($entry->post_author)){
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
                    if(absint($current_user->ID) === absint($entry->post_author)){
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
                    if(absint($current_user->ID) === absint($entry->post_author)){
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
            return array(
                'allowSeeAny' => $allowSeeAny,
                'allowViewAny' => $allowViewAny,
                'allowViewOwn' => $allowViewOwn,
                'allowEditAny' => $allowEditAny,
                'allowEditOwn' => $allowEditOwn,
                'allowDeleteAny' => $allowDeleteAny,
                'allowDeleteOwn' => $allowDeleteOwn
            );
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
