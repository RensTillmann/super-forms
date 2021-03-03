<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Listing')) :


    /**
     * Main SUPER_Listing Class
     *
     * @class SUPER_Listing
     * @version 1.0.0
     */
    final class SUPER_Listing {
    
        
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
        public $add_on_slug = 'listing';
        public $add_on_name = 'Front-end Listing';

        
        /**
         * @var SUPER_Listing The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Listing Instance
         *
         * Ensures only one instance of SUPER_Listing is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Listing()
         * @return SUPER_Listing - Main instance
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
         * SUPER_Listing Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_listing_loaded');
        }

        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *  @since      1.0.0
        */
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }

        
        /**
         * What type of request is this?
         *
         * string $type ajax, frontend or admin
         * @return bool
         *
         *  @since      1.0.0
        */
        private function is_request($type){
            switch ($type){
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        
        /**
         * Hook into actions and filters
         *
         *  @since      1.0.0
        */
        private function init_hooks() {
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
            add_action( 'init', array( $this, 'register_shortcodes' ) );
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 10, 1 );
                add_action( 'super_create_form_listing_tab', array( $this, 'add_tab_content' ) );
                // add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
                add_action( 'init', array( $this, 'update_plugin' ) );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );
                add_filter( 'super_enqueue_styles', array( $this, 'add_style' ), 10, 1 );
                add_filter( 'super_enqueue_scripts', array( $this, 'add_script' ), 10, 1 );
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 110, 2 );
            }

            add_action( 'wp_ajax_super_load_form_inside_modal', array( $this, 'load_form_inside_modal' ) );
            add_action( 'wp_ajax_nopriv_super_load_form_inside_modal', array( $this, 'load_form_inside_modal' ) );
        
            if( isset($_GET['super-fel-id']) ) {
                if(SUPER_PLUGIN_FILE){
                    SUPER_Forms()->enqueue_fontawesome_styles();
                    wp_enqueue_style( 'super-elements', SUPER_PLUGIN_FILE . 'assets/css/frontend/elements.css', array(), SUPER_VERSION );
                }
                add_filter( 'show_admin_bar', '__return_false', PHP_INT_MAX ); // We do not want to display the admin bar
                add_filter( 'template_include', array( $this, 'form_blank_page_template' ), PHP_INT_MAX );
            }

        }

        public static function getStandardColumns(){
            return array(
                'title' => array(
                    'name' => esc_html__( 'Title', 'super-forms' ),
                    'meta_key' => 'post_title'
                ),
                'status' => array(
                    'name' => esc_html__( 'Status', 'super-forms' ),
                    'meta_key' => 'entry_status'
                ),
                'date' => array(
                    'name' => esc_html__( 'Date', 'super-forms' ),
                    'meta_key' => 'post_date'
                ),
                'author_username' => array(
                    'name' => esc_html__( 'Author username', 'super-forms' ),
                    'meta_key' => 'username'
                ),
                'author_firstname' => array(
                    'name' => esc_html__( 'Author first name', 'super-forms' ),
                    'meta_key' => 'firstname'
                ),
                'author_lastname' => array(
                    'name' => esc_html__( 'Author last name', 'super-forms' ),
                    'meta_key' => 'lastname'
                ),
                'author_fullname' => array(
                    'name' => esc_html__( 'Author full name', 'super-forms' ),
                    'meta_key' => 'fullname'
                ),
                'author_nickname' => array(
                    'name' => esc_html__( 'Author nickname', 'super-forms' ),
                    'meta_key' => 'nickname'
                ),
                'author_display' => array(
                    'name' => esc_html__( 'Author display name', 'super-forms' ),
                    'meta_key' => 'display'
                ),
                'author_email' => array(
                    'name' => esc_html__( 'Author E-mail', 'super-forms' ),
                    'meta_key' => 'email'
                ),
                'author_id' => array(
                    'name' => esc_html__( 'Author ID', 'super-forms' ),
                    'meta_key' => 'id'
                )
            );
        }
        // Use custom template to load / display forms
        // When "Edit" entry button is clicked it will be loaded inside the modal through an iframe
        public static function form_blank_page_template( $template ) {
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

        /**
         * Hook into stylesheets of the form and add styles for the calculator element
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
            $functions['save_form_params_filter'][] = array(
                'name' => 'add_listing'
            );
            return $functions;
        }

        public static function add_style($styles){
            $assets_path = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            $styles['super-listing'] = array(
                'src'     => $assets_path . 'css/backend/styles.css',
                'deps'    => '',
                'version' => SUPER_Listing()->version,
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
            $scripts['super-listing'] = array(
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
            $tabs['listing'] = esc_html__( 'Listings', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            $tooltips = array(
                esc_html__( 'Give this listing a name', 'super-forms' ),
                esc_html__('Paste shortcode on any page', 'super-forms' ),
                esc_html__('Change Settings', 'super-forms' ),
                esc_html__('Delete Listing', 'super-forms' )
            );

            $form_id = absint($atts['form_id']);
            echo '<div class="super_transient"></div>';
            $slug = 'listing';
            $lists = array();
            if(isset($atts['settings']) && isset($atts['settings']['_'.$slug])){
                $lists = $atts['settings']['_'.$slug]['lists'];
            }
            if(count($lists)==0) {
                $lists[] = self::get_default_listing_settings(array());
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
            // Hiding/Showing elements in PDF
            // Header/Footer usage notice
            
            // Enable Front-end Listing
            echo '<div class="sfui-setting">';
                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    echo '<input type="checkbox" name="enable" value="true" checked="checked" />';
                    echo '<span>' . esc_html__( 'Enable Front-end Listing for this form', 'super-forms' ) . '</span>';
                echo '</label>';
            echo '</div>';

            // When enabled, we display the list with Front-end listings
            echo '<div class="sfui-repeater" data-k="lists">';
            // Repeater Item
            foreach($lists as $k => $v){
                //// Set default values if they don't exist
                $v = self::get_default_listing_settings($v);
                echo '<div class="sfui-repeater-item">';
                    echo '<div class="sfui-inline">';
                        echo '<div class="sfui-setting">';
                            echo '<label>';
                                echo '<input type="text" name="name" value="' . $v['name'] . '" />';
                                echo '<i>' . $tooltips[0] . '</i>';
                            echo '</label>';
                        echo '</div>';
                        echo '<div class="sfui-setting">';
                            echo '<label>';
                                // Get the correct shortcode for this list
                                $shortcode = '['.esc_html__( 'form-not-saved-yet', 'super-forms' ).']';
                                if( $form_id!=0 ) $shortcode = '[super_listing list=&quot;' . ($k+1) . '&quot; id=&quot;'. $form_id . '&quot;]';
                                echo '<input type="text" readonly="readonly" class="super-get-form-shortcodes" value="' . $shortcode. '" />';
                                echo '<i>' . $tooltips[1] . '</i>';
                            echo '</label>';
                        echo '</div>';
                        echo '<div class="sfui-btn sfui-round sfui-tooltip" title="' . esc_attr($tooltips[2]) . '" onclick="SUPER.ui.btn(event, this, \'toggleListingSettings\')"><i class="fas fa-cogs"></i></div>';
                        echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add list', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                        echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_attr($tooltips[3]) . '" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                    echo '</div>';

                    echo '<div class="sfui-setting-group">';
                        // Display based on
                        echo '<form class="sfui-setting">';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="display_based_on" value="this_form"' . ($v['display_based_on']==='this_form' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Only display entries based on this form', 'super-forms' ) . '</span>';
                                echo '</label>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="display_based_on" value="all_forms"' . ($v['display_based_on']==='all_forms' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Display entries based on all forms', 'super-forms' ) . '</span>';
                                echo '</label>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="display_based_on" value="specific_forms"' . ($v['display_based_on']==='specific_forms' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Display entries based on the following form ID\'s', 'super-forms' ) . ':</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="display_based_on;specific_forms">';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<input type="text" name="form_ids" placeholder="e.g: 123,124" value="' . sanitize_text_field($v['form_ids']) . '" />';
                                                echo '<span>(' . esc_html__( 'seperated by comma\'s', 'super-forms' ) . '</span>';
                                            echo '</label>';
                                        echo '</div>';
                                    echo '</div>';

                                echo '</label>';
                        echo '</form>';
                        // Entries within date range
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="date_range.enabled" value="true"' . ($v['date_range']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Only display entries within the following date range', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings sfui-inline" data-f="date_range.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'From', 'super-forms' ) . ': <i>(' . esc_html__( 'or leave blank for no minimum date', 'super-forms' ) . ')</i></span>';
                                            echo '<input type="date" name="date_range.from" value="' . sanitize_text_field($v['date_range']['from']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'Till', 'super-forms' ) . ': <i>(' . esc_html__( 'or leave blank for no maximum date', 'super-forms' ) . ')</i></span>';
                                            echo '<input type="date" name="date_range.till" value="' . sanitize_text_field($v['date_range']['till']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';

                        $standardColumns = self::getStandardColumns();
                        foreach($standardColumns as $sk => $sv){
                            echo '<div class="sfui-setting">';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="checkbox" name="'.$sk.'_column.enabled" value="true"' . ($v[$sk.'_column']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Show "'.$sv['name'].'" column', 'super-forms' ) . ':</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="'.$sk.'_column.enabled;true">';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span>' . esc_html__( 'Column name', 'super-forms' ) . '</span>';
                                                echo '<input type="text" name="'.$sk.'_column.name" value="' . sanitize_text_field($v[$sk.'_column']['name']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span>' . esc_html__( 'Filter placeholder', 'super-forms' ) . '</span>';
                                                echo '<input type="text" name="'.$sk.'_column.placeholder" value="' . sanitize_text_field($v[$sk.'_column']['placeholder']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
                                        if(in_array($sk, array('title', 'status', 'date'))){
                                            // Do not show for title, status, date columns
                                            echo '<div class="sfui-setting sfui-vertical">';
                                            echo '</div>';
                                        }else{
                                            echo '<div class="sfui-setting sfui-vertical">';
                                                echo '<label>';
                                                    echo '<span>';
                                                        echo esc_html__( 'Link', 'super-forms' ) . ':';
                                                    echo '</span>';
                                                    echo '<select name="'.$sk.'_column.link" onChange="SUPER.ui.updateSettings(event, this)">';
                                                        echo '<option ' . ($v[$sk.'_column']['link']=='none' ? ' selected="selected"' : '') . ' value="none">' . esc_html__( 'None', 'super-forms' ) . '</option>';
                                                        echo '<option ' . ($v[$sk.'_column']['link']=='author_posts' ? ' selected="selected"' : '') . ' value="author_posts">' . esc_html__( 'The author page', 'super-forms' ) . '</option>';
                                                        echo '<option ' . ($v[$sk.'_column']['link']=='edit_user' ? ' selected="selected"' : '') . ' value="edit_user">' . esc_html__( 'URL to edit user page', 'super-forms' ) . '</option>';
                                                        echo '<option ' . ($v[$sk.'_column']['link']=='email' ? ' selected="selected"' : '') . ' value="email">' . esc_html__( 'Link to E-mail address (mailto:)', 'super-forms' ) . '</option>';
                                                        // get_author_posts_url()
                                                        // get_edit_profile_url()
                                                        // get_edit_user_link()
                                                    echo '</select>';
                                                echo '</label>';
                                            echo '</div>';
                                        }
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span>' . esc_html__( 'Column width (px)', 'super-forms' ) . '</span>';
                                                echo '<input type="number" name="'.$sk.'_column.width" value="' . sanitize_text_field($v[$sk.'_column']['width']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span>' . esc_html__( 'Column order', 'super-forms' ) . '</span>';
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
                                echo '<input type="checkbox" name="custom_columns.enabled" value="true"' . ($v['custom_columns']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Show the following "Custom" columns', 'super-forms' ) . ':</span>';
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
                                                            echo '<span>' . esc_html__( 'Column name', 'super-forms' ) . ':</span>';
                                                            echo '<input type="text" name="name" value="' . sanitize_text_field($cv['name']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span>' . esc_html__( 'Map to the following field', 'super-forms' ) . ' <i>(' . esc_html__( 'enter a field name', 'super-forms') .')</i>:</span>';
                                                            echo '<input type="text" name="field_name" value="' . sanitize_text_field($cv['field_name']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span>';
                                                                echo esc_html__( 'Filter method', 'super-forms' ) . ':';
                                                            echo '</span>';
                                                            echo '<select name="filter" onChange="SUPER.ui.updateSettings(event, this)">';
                                                                echo '<option '.($cv['filter']=='none' ? ' selected="selected"' : '').' value="none">'.esc_html__( 'No filter', 'super-forms' ).'</option>';
                                                                echo '<option '.($cv['filter']=='text' ? ' selected="selected"' : '').' value="text">'.esc_html__( 'Text field (default)', 'super-forms' ).'</option>';
                                                                echo '<option '.($cv['filter']=='dropdown' ? ' selected="selected"' : '').' value="dropdown">'.esc_html__( 'Dropdown', 'super-forms' ).'</option>';
                                                            echo '</select>';
                                                            echo '<div class="sfui-sub-settings" data-f="filter;dropdown">';
                                                                echo '<div class="sfui-setting sfui-vertical">';
                                                                    echo '<label>';
                                                                        echo '<span>' . esc_html__( 'Filter options', 'super-forms' ) . ' <i>(' . esc_html__( 'put each on a new line', 'super-forms') .')</i>:</span>';
                                                                        echo '<textarea name="filter_items" placeholder="' . esc_attr__( "option_value1|Option Label 1\noption_value2|Option Label 2", 'super-forms') . '">' . $cv['filter_items'] . '</textarea>';
                                                                    echo '</label>';
                                                                echo '</div>';
                                                            echo '</div>';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span>' . esc_html__( 'Column width', 'super-forms' ) . ' <i>(' . esc_html__( 'in', 'super-forms') .' px)</i>:</span>';
                                                            echo '<input type="number" name="width" value="' . sanitize_text_field($cv['width']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                    echo '<div class="sfui-setting sfui-vertical">';
                                                        echo '<label>';
                                                            echo '<span>' . esc_html__( 'Column order', 'super-forms' ) . '</span>';
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

                        // Allow display any entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="display_any.enabled" value="true"' . ($v['display_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Allow the following users to display any entries (logged in users will always be able to view their own entries)', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="display_any.enabled;true">';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="display_any.user_roles" value="' . sanitize_text_field($v['display_any']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="display_any.user_ids" value="' . sanitize_text_field($v['display_any']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow editing any entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="edit_any.enabled" value="true"' . ($v['edit_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Allow the following users to edit any entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="edit_any.enabled;true">';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_any.method" value="modal"' . ($v['edit_any']['method']==='modal' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Open form in a modal (default)', 'super-forms' ) . '</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_any.method" value="url"' . ($v['edit_any']['method']==='url' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Open via form page (this requires "Form Location" to be defined under "Form Settings")', 'super-forms' ) . '</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_any.user_roles" value="' . sanitize_text_field($v['edit_any']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_any.user_ids" value="' . sanitize_text_field($v['edit_any']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow editing own entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="edit_own.enabled" value="true"' . ($v['edit_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Allow the following users to edit their own entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="edit_own.enabled;true">';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_own.method" value="modal"' . ($v['edit_own']['method']==='modal' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Open form in a modal (default)', 'super-forms' ) . '</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="edit_own.method" value="url"' . ($v['edit_own']['method']==='url' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Open via form page (this requires "Form Location" to be defined under "Form Settings")', 'super-forms' ) . '</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_own.user_roles" value="' . sanitize_text_field($v['edit_own']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="edit_own.user_ids" value="' . sanitize_text_field($v['edit_own']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow deleting any entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="delete_any.enabled" value="true"' . ($v['delete_any']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Allow the following users to delete any entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="delete_any.enabled;true">';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="checkbox" name="delete_any.permanent" value="true"' . ($v['delete_any']['permanent']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Bypass Trash and force delete (permanently deletes the entry)', 'super-forms' ) . ':</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<span>' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_any.user_roles" value="' . sanitize_text_field($v['delete_any']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_any.user_ids" value="' . sanitize_text_field($v['delete_any']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                        // Allow delete own entries
                        echo '<div class="sfui-setting">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="delete_own.enabled" value="true"' . ($v['delete_own']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Allow the following users to delete their own entries', 'super-forms' ) . ':</span>';
                                echo '<div class="sfui-sub-settings" data-f="delete_own.enabled;true">';
                                    echo '<div class="sfui-setting">';
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="checkbox" name="delete_own.permanent" value="true"' . ($v['delete_own']['permanent']==='true' ? ' checked="checked"' : '') . ' /><span>' . esc_html__( 'Bypass Trash and force delete (permanently deletes the entry)', 'super-forms' ) . ':</span>';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User roles:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: administrator,editor', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to allow all roles', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_own.user_roles" value="' . sanitize_text_field($v['delete_own']['user_roles']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                    echo '<div class="sfui-setting sfui-vertical">';
                                        echo '<label>';
                                            echo '<span>' . esc_html__( 'User ID\'s:', 'super-forms' ) . ' <i>(' . esc_html__( 'seperated by comma e.g: 32,2467,1870', 'super-forms') .')</i>, ' . esc_html__( 'or leave blank to only filter by the roles defined above', 'super-forms' ) . '</span>';
                                            echo '<input type="text" name="delete_own.user_ids" value="' . sanitize_text_field($v['delete_own']['user_ids']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }

        // Get default listing settings
        public static function get_default_listing_settings($list) {
            if(empty($list['name'])) $list['name'] = 'Listing #1';
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
                'order' => 1,
                'width' => 150
            );
            if(empty($list['status_column'])) $list['status_column'] = array(
                'enabled'=>'true',
                'name'=>esc_html__( 'Status', 'super-forms' ),
                'placeholder'=>esc_html__( '- choose status -', 'super-forms' ),
                'order' => 2,
                'width' => 150
            );
            if(empty($list['date_column'])) $list['date_column'] = array(
                'enabled' => 'true',
                'name' => esc_html__( 'Date created', 'super-forms' ),
                'placeholder' => esc_html__( 'Filter by date', 'super-forms' ),
                'order' => 3,
                'width' => 150
            );
            if(empty($list['author_username_column'])) $list['author_username_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Username', 'super-forms' ),
                'placeholder' => esc_html__( 'Username', 'super-forms' ),
                'link' => 'none',
                'order' => 4,
                'width' => 150
            );
            if(empty($list['author_firstname_column'])) $list['author_firstname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'First name', 'super-forms' ),
                'placeholder' => esc_html__( 'First name', 'super-forms' ),
                'link' => 'none',
                'order' => 5,
                'width' => 150
            );
            if(empty($list['author_lastname_column'])) $list['author_lastname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Last name', 'super-forms' ),
                'placeholder' => esc_html__( 'Last name', 'super-forms' ),
                'link' => 'none',
                'order' => 6,
                'width' => 150
            );
            if(empty($list['author_fullname_column'])) $list['author_fullname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Full name', 'super-forms' ),
                'placeholder' => esc_html__( 'Full name', 'super-forms' ),
                'link' => 'none',
                'order' => 7,
                'width' => 150
            );
            if(empty($list['author_nickname_column'])) $list['author_nickname_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Nickname', 'super-forms' ),
                'placeholder' => esc_html__( 'Nickname', 'super-forms' ),
                'link' => 'none',
                'order' => 8,
                'width' => 150
            );
            if(empty($list['author_display_column'])) $list['author_display_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Display name', 'super-forms' ),
                'placeholder' => esc_html__( 'Display name', 'super-forms' ),
                'link' => 'none',
                'order' => 9,
                'width' => 150
            );
            if(empty($list['author_email_column'])) $list['author_email_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'E-mail', 'super-forms' ),
                'placeholder' => esc_html__( 'E-mail', 'super-forms' ),
                'link' => 'none',
                'order' => 10,
                'width' => 150
            );
            if(empty($list['author_id_column'])) $list['author_id_column'] = array(
                'enabled' => 'false',
                'name' => esc_html__( 'Author ID', 'super-forms' ),
                'placeholder' => esc_html__( 'Author ID', 'super-forms' ),
                'link' => 'none',
                'order' => 11,
                'width' => 150
            );
            if(empty($list['custom_columns']) ) $list['custom_columns'] = array(
                'enabled' => 'false',
                'columns' => array(
                    array(
                        'filter' => 'none', // none, text, dropdown
                        'name' => 'E-mail',
                        'field_name' => 'email',
                        'width' => 150,
                        'filter_items' => '',
                        'order' => 5
                    )
                ),
            );
            // Display permissions
            if( empty($list['display_any']) ) $list['display_any'] = array(
                'enabled'=>'true',
                'user_roles'=>'administrator',
                'user_ids'=>''
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
            return $list;
        }

        // Return data for script handles.
        public static function register_shortcodes(){
            add_shortcode( 'super_listing', array( 'SUPER_Listing', 'super_listing_func' ) );
        }

        // The form shortcode that will generate the list/table with all Contact Entries
        public static function super_listing_func( $atts ) {
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
            $handle = 'super-listing';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'assets/js/frontend/script.js', array( 'super-common' ), SUPER_Listing()->version, false );  
            wp_localize_script(
                $handle,
                $name,
                array( 
                    'get_home_url' => get_home_url(),
                    'ajaxurl' => $ajax_url
                )
            );
            wp_enqueue_script( $handle );
            // wp_enqueue_script( 'super-listing', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/script.js', array( 'super-common' ), $this->version, false );  
            
            wp_enqueue_style( 'super-listing', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/styles.css', array(), SUPER_Listing()->version );
            SUPER_Forms()->enqueue_fontawesome_styles();

            // Get the settings for this specific list based on it's index
            $list_id = absint($atts['list'])-1;
            $lists = $settings['_listing']['lists'];
            if(!isset($lists[$list_id])){
                // The list does not exist
                $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with ID: %d', 'super-forms' ), $list_id);
                return $result;
            }
            // Set default values if they don't exist
            $list = self::get_default_listing_settings($lists[$list_id]);

            $columns = array(); 
            $standardColumns = self::getStandardColumns();
            foreach($standardColumns as $sk => $sv){
                if( $list[$sk.'_column']['enabled']==='true' ) {
                    if($sk=='entry_status'){
                        $items = array();
                        foreach(SUPER_Settings::get_entry_statuses() as $k => $v){
                            $items[$k] = $v['name']; 
                        }
                        $columns[$sv['meta_key']] = array(
                            'order' => absint($list[$sk.'_column']['order']),
                            'name' => $list[$sk.'_column']['name'],
                            'width' => absint($list[$sk.'_column']['width']),
                            'filter' => array(
                                'field_type' => 'dropdown',
                                'placeholder' => $list[$sk.'_column']['placeholder'],
                                'items' => $items
                            )
                        );
                    }else{
                        $columns[$sv['meta_key']] = array(
                            'order' => absint($list[$sk.'_column']['order']),
                            'name' => $list[$sk.'_column']['name'],
                            'width' => absint($list[$sk.'_column']['width']),
                            'filter' => array(
                                'field_type' => 'text',
                                'placeholder' => $list[$sk.'_column']['placeholder']
                            )
                        );
                        // If link available
                        if(isset($list[$sk.'_column']['link'])){
                            $columns[$sv['meta_key']]['link'] = $list[$sk.'_column']['link'];
                        }
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

            $limit = absint($list['limit']);
            // Check if custom limit was choosen by the user
            if( isset($_GET['limit']) ) {
                $limit = absint($_GET['limit']);
            }
            $offset = 0; // If page is 1, offset is 0, If page is 2 offset is 1 etc.
            $paged = (get_query_var('page')) ? get_query_var('page') : 1;
            $offset = $limit*($paged-1);

            $where = '';
            if( $list['display_based_on']=='this_form' ) {
                $where .= " AND post_parent = '" . absint($form_id) . "'";
            }

            // Filters by listing settings
            $allowDisplayAny = false;
            if(!empty($list['display_any'])) {
                if($list['display_any']['enabled']==='true'){
                    // Check if both roles and user ID's are empty
                    if( (empty($list['display_any']['user_roles'])) && (empty($list['display_any']['user_ids'])) ){
                        $allowDisplayAny = true;
                    }else{
                        $allowed_roles = preg_replace('/\s+/', '', $list['display_any']['user_roles']);
                        $allowed_roles = explode(",", $allowed_roles);
                        if( (!empty($list['display_any']['user_roles'])) && (empty($list['display_any']['user_ids'])) ){
                            // Only compare against user roles
                            foreach( $current_user->roles as $v ) {
                                if( in_array( $v, $allowed_roles ) ) {
                                    $allowDisplayAny = true;
                                }
                            }
                        }else{
                            if(empty($list['display_any']['user_roles'])) {
                                // Only compare against user ID
                                $allowed_ids = preg_replace('/\s+/', '', $list['display_any']['user_ids']);
                                $allowed_ids = explode(",", $allowed_ids);
                                if( in_array( $current_user->ID, $allowed_ids ) ) {
                                    $allowDisplayAny = true;
                                }
                            }else{
                                // Compare against both user roles and ids
                                foreach( $current_user->roles as $v ) {
                                    if( in_array( $v, $allowed_roles ) ) {
                                        $allowed_ids = preg_replace('/\s+/', '', $list['display_any']['user_ids']);
                                        $allowed_ids = explode(",", $allowed_ids);
                                        if( in_array( $current_user->ID, $allowed_ids ) ) {
                                            $allowDisplayAny = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if($allowDisplayAny===true){
                // Display all entries
            }else{
                // Only display entries based on the current logged in user
                $where .= ' AND post_author = "' . absint( $current_user->ID ) . '"';
            }

            // Filters by user
            $filters = '';
            // Check if filtering based on post_title
            if( !empty($_GET['post_title']) ) {
                $filters .= ' post_title LIKE "%' . sanitize_text_field( $_GET['post_title'] ) . '%"';
            }
            // Check if filtering based on post_date
            if( !empty($_GET['post_date']) ) {
                if( !empty($filters) ) $filters .= ' AND';
                $filters .= ' post_date LIKE "' . sanitize_text_field( $_GET['post_date'] ) . '%"';
            }
            // Check if filtering based on entry_status
            if( !empty($_GET['entry_status']) ) {
                 if( !empty($filters) ) $filters .= ' AND';
                 $filters .= ' entry_status.meta_value = "' . sanitize_text_field( $_GET['entry_status'] ) . '"';
                 //add_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['contact_entry_custom_status'] );
            }
            // Check if filtering based on post_author
            if( !empty($_GET['post_author']) ) {
                $filters .= ' post_author LIKE "%' . sanitize_text_field( $_GET['post_author'] ) . '%"';
            }
            if( !empty($filters) ) {
                $where .= ' AND (' . $filters . ')';
            }


            // Custom field filters
            $custom_fields_filters = '';
            foreach( $_GET as $k => $v ) {
                // Check if this is a custom field, by checking if first character of the string is a _ "underscore"
                if(substr($k, 0, 1)=='_'){
                    // Get field_name of this custom column by removing the _ "underscore"
                    $field_name = sanitize_text_field(substr($k, 1));
                    $value = sanitize_text_field($v);
                    if( !empty($custom_fields_filters) ) $custom_fields_filters .= ' OR';
                    $custom_fields_filters .= ' meta.meta_value REGEXP \'.*s:4:"name";s:[0-9]+:"' . $field_name . '";s:5:"value";s:[0-9]+:"(' . $value . ')";\'';
                }
            }
            if( !empty($custom_fields_filters) ) {
                $where .= ' AND (' . $custom_fields_filters . ')';
            }

            // SELECT ID, post_title, post_date, meta.meta_value AS data, 
            // (SELECT meta_value FROM wp_postmeta AS meta WHERE meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_status') AS status 
            // FROM wp_posts AS post 
            // INNER JOIN wp_postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data' 
            // WHERE post_type = 'super_contact_entry' AND (meta.meta_value REGEXP '.*s:4:"name";s:[0-9]+:"email";s:5:"value";s:[0-9]+:"(.*ng4design@.*)";' )
            // ORDER BY post_date DESC 
            // LIMIT 500

            $count_query = "
            SELECT COUNT(ID)
            FROM $wpdb->posts AS post 
            INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
            LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
            WHERE post_type = 'super_contact_entry'$where
            ORDER BY post_date DESC
            ";
            $results_found = $wpdb->get_var($count_query);

            $query = "
            SELECT 
            post.ID AS post_id, post.post_author AS post_author, post.post_title AS post_title, post.post_date AS post_date,
            meta.meta_value AS contact_entry_data,
            entry_status.meta_value AS status,
            first_name.meta_value AS first_name,
            last_name.meta_value AS last_name,
            nickname.meta_value AS nickname,
            author.user_login AS username, author.user_email AS email, author.display_name AS display_name
            FROM $wpdb->posts AS post 
            INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
            LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
            LEFT JOIN $wpdb->users AS author ON author.ID = post.post_author
            LEFT JOIN $wpdb->usermeta AS first_name ON first_name.user_id = post.post_author AND first_name.meta_key = 'first_name'
            LEFT JOIN $wpdb->usermeta AS last_name ON last_name.user_id = post.post_author AND last_name.meta_key = 'last_name'
            LEFT JOIN $wpdb->usermeta AS nickname ON nickname.user_id = post.post_author AND nickname.meta_key = 'nickname'
            WHERE post_type = 'super_contact_entry'$where
            ORDER BY post_date DESC
            LIMIT $limit
            OFFSET $offset
            ";
            $entries = $wpdb->get_results($query);

            $result = '';
            $result .= '<div class="super-fel">';
                $result .= '<div class="super-header">';
                    $result .= '<div class="super-select-all">';
                        $result .= 'Select All';
                    $result .= '</div>';
                    $result .= '<div class="super-settings">';
                        $result .= 'Settings';
                    $result .= '</div>';
                    $result .= '<div class="super-csv-export">';
                        $result .= 'CSV Export';
                    $result .= '</div>';
                $result .= '</div>';
                $result .= '<div class="super-fel-wrap">';
                    $result .= '<div class="super-columns">';
                        $entry = array();
                        if( isset($entries[0]) ) {
                            $entry = $entries[0];
                        }
                        $result .= '<div class="super-col super-actions">';
                            $actions = '<span class="super-edit"></span>';
                            $actions .= '<span class="super-view"></span>';
                            $actions .= '<span class="super-delete"></span>';
                            $result .= apply_filters( 'super_listing_actions_filter', $actions, $entry );
                        $result .= ' </div>';

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
                            $filtervalue = '';
                            if( !empty($_GET[$column_name]) ) {
                                $filtervalue = sanitize_text_field($_GET[$column_name]);
                            }

                            $result .= '<div class="super-col-wrap" data-name="' . $column_name . '"' . $styles . '>';
                                $result .= '<span class="super-col-name">' . $v['name'] . '</span>';
                                if( isset($v['filter']) && is_array($v['filter']) ) {
                                    $result .= '<div class="super-col-sort">';
                                        $result .= '<span class="super-sort-down" onclick="SUPER.frontEndListing.sort(this, \'up\')">↓</span>';
                                        $result .= '<span class="super-sort-up" onclick="SUPER.frontEndListing.sort(this, \'down\')">↑</span>';
                                    $result .= '</div>';
                                    $result .= '<div class="super-col-filter">';
                                        if($v['filter']['field_type']=='text'){
                                            $result .= '<input value="' . $filtervalue . '" autocomplete="new-password" name="' . $k . '" type="text" placeholder="' . $v['filter']['placeholder'] . '" />';
                                            $result .= '<span class="super-search" onclick="SUPER.frontEndListing.search(event, this)"></span>';
                                        }
                                        if($v['filter']['field_type']=='datepicker'){
                                            $result .= '<input value="' . $filtervalue . '" autocomplete="new-password" name="' . $k . '" type="date" placeholder="' . $v['filter']['placeholder'] . '"  onchange="SUPER.frontEndListing.search(event, this)" />';
                                        }
                                        if($v['filter']['field_type']=='dropdown'){
                                            $result .= '<select name="' . $k . '" placeholder="' . $v['filter']['placeholder'] . '" onchange="SUPER.frontEndListing.search(event, this)">';
                                                foreach( $v['filter']['items'] as $value => $name ) {
                                                    $result .= '<option value="' . $value . '"' . ( $filtervalue==$value ? ' selected="selected"' : '' ) . '>' . $name . '</option>';
                                                }
                                            $result .= '</select>';
                                        }
                                    $result .= '</div>';
                                }else{
                                    // It's a custom column, find out what filter method to use
                                    $result .= '<div class="super-col-filter">';
                                        if( !isset($v['filter']) ) $v['filter'] = 'text';
                                        if( $v['filter']=='text' ) {
                                            $result .= '<input value="' . $filtervalue . '" autocomplete="new-password" type="text" name="' . $v['field_name'] . '" placeholder="' . esc_attr__( 'Filter...', 'super-forms' ) . '" />';
                                            $result .= '<span class="super-search" onclick="SUPER.frontEndListing.search(event, this)"></span>';
                                        }
                                        if( $v['filter']=='dropdown' ) {
                                            $result .= '<select name="' . $k . '" onchange="SUPER.frontEndListing.search(event, this)">';
                                                $result .= '<option value=""' . ( empty($filtervalue) ? ' selected="selected"' : '' ) . '>' . esc_html__( '- filter -', 'super-forms' ) . '</option>';
                                                $filter_items = explode("\n", $v['filter_items']);
                                                foreach( $filter_items as $value ) {
                                                    $value = explode('|', $value);
                                                    $label = (isset($value[1]) ? $value[1] : 'undefined');
                                                    $value = (isset($value[0]) ? $value[0] : 'undefined');
                                                    $result .= '<option value="' . $value . '"' . ( $filtervalue==$value ? ' selected="selected"' : '' ) . '>' . $label . '</option>';
                                                }
                                            $result .= '</select>';
                                        }
                                    $result .= '</div>';
                                }
                            $result .= '</div>';
                        }
                    $result .= '</div>';

                    $result .= '<div class="super-entries">';
                        $result .= '<div class="super-scroll"></div>';
                        if( !class_exists( 'SUPER_Settings' ) ) require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
                        $global_settings = SUPER_Common::get_global_settings();
                        $statuses = SUPER_Settings::get_entry_statuses($global_settings);
                        foreach($entries as $entry){
                            $data = unserialize($entry->contact_entry_data);
                            $result .= '<div class="super-entry" data-id="' . $entry->post_id . '">';
                                $result .= '<div class="super-col super-check"></div>';
                                $result .= '<div class="super-col super-actions">';
                                    $actions = '';

                                    // EDIT ANY
                                    // Check if any user or own user is allowed to edit entry
                                    $allowEditMethod = '';
                                    $allowEditAny = false;
                                    if(!empty($list['edit_any'])) {
                                        if($list['edit_any']['enabled']==='true'){
                                            $allowEditMethod = $list['edit_any']['method'];
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
                                                        foreach( $current_user->roles as $v ) {
                                                            if( in_array( $v, $allowed_roles ) ) {
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
                                    }
                                    // EDIT OWN
                                    $allowEditOwn = false;
                                    if(!empty($list['edit_own'])) {
                                        if($list['edit_own']['enabled']==='true'){
                                            // First check if entry author ID equals logged in user ID
                                            if(absint($current_user->ID) === absint($entry->post_author)){
                                                $allowEditMethod = $list['edit_own']['method'];
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
                                                            foreach( $current_user->roles as $v ) {
                                                                if( in_array( $v, $allowed_roles ) ) {
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
                                                        foreach( $current_user->roles as $v ) {
                                                            if( in_array( $v, $allowed_roles ) ) {
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
                                    }
                                    // DELETE OWN
                                    $allowDeleteOwn = false;
                                    if(!empty($list['delete_own'])) {
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
                                                            foreach( $current_user->roles as $v ) {
                                                                if( in_array( $v, $allowed_roles ) ) {
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
                                    }
                                    $actions .= '<span class="super-view" onclick="SUPER.frontEndListing.viewEntry(this, '.$list_id.')"></span>';
                                    if($allowEditAny===true || $allowEditOwn===true){
                                        if($allowEditMethod=='url'){
                                            $url = $settings['form_location']; 
                                            $query = parse_url($url, PHP_URL_QUERY);
                                            // Returns a string if the URL has parameters or NULL if not
                                            if ($query) {
                                                $url .= '&contact_entry_id=' . $entry->ID;
                                            } else {
                                                $url .= '?contact_entry_id=' . $entry->ID;
                                            }
                                            $actions .= '<a class="super-edit" target="_blank" href="' . esc_url($url) . '"></a>';
                                        }else{
                                            $actions .= '<span class="super-edit" onclick="SUPER.frontEndListing.editEntry(this, '.$list_id.')"></span>';
                                        }
                                    }
                                    if($allowDeleteOwn===true || $allowDeleteAny===true){
                                        $actions .= '<span class="super-delete" onclick="SUPER.frontEndListing.deleteEntry(this, '.$list_id.')"></span>';
                                    }
                                    $result .= apply_filters( 'super_listing_actions_filter', $actions, $entry );
                                $result .= ' </div>';

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
                                        if($authorData && (!empty($cv['link']) && $cv['link']!='none')){
                                            if($cv['link']=='author_posts'){
                                                $result .= '<a href="'.get_author_posts_url($entry->post_author).'">';
                                            }elseif($cv['link']=='edit_user'){
                                                $result .= '<a href="'.get_edit_user_link($entry->post_author).'">';
                                            }elseif($cv['link']=='email'){
                                                $result .= '<a href="mailto:'.$authorData->user_email.'">';
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
                                            if( (isset($statuses[$entry->status])) && ($entry->status!='') ) {
                                                $result .= '<span class="super-entry-status super-entry-status-' . $entry->status . '" style="color:' . $statuses[$entry->status]['color'] . ';background-color:' . $statuses[$entry->status]['bg_color'] . '">' . $statuses[$entry->status]['name'] . '</span>';
                                            }else{
                                                $post_status = get_post_status($entry->post_id);
                                                if($post_status=='super_read'){
                                                    $result .= '<span class="super-entry-status super-entry-status-' . $post_status . '" style="background-color:#d6d6d6;">' . esc_html__( 'Read', 'super-forms' ) . '</span>';
                                                }else{
                                                    $result .= '<span class="super-entry-status super-entry-status-' . $post_status . '">' . esc_html__( 'Unread', 'super-forms' ) . '</span>';
                                                }
                                            }
                                        }elseif($column_key=='post_date'){
                                            $result .= date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) );
                                            //echo get_the_date($entry->post_date);
                                        }else{
                                            // Check if this data key exists
                                            if(isset($data[$column_key])){
                                                // Check if it has a value, if so print it
                                                if(isset($data[$column_key]['value'])){
                                                    if ( strpos( $data[$column_key]['value'], 'data:image/png;base64,') !== false ) {
                                                        // @IMPORTANT, escape the Data URL but make sure add it as an acceptable protocol 
                                                        // otherwise the signature will not be displayed
                                                        $result .= '<img src="' . esc_url( $data[$column_key]['value'], array( 'data' ) ) . '" />';
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
                                                                    $result .= '<a target="_blank" href="' . esc_url( $url ) . '">';
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
                    $result .= '</div>';
                $result .= '</div>';

                $result .= '<div class="super-pagination">';
                    
                    $result .= '<select class="super-limit" onchange="SUPER.frontEndListing.search(event, this)">';
                        $result .= '<option ' . ($limit==10 ? 'selected="selected" ' : '') . 'value="10">10</option>';
                        $result .= '<option ' . ($limit==25 ? 'selected="selected" ' : '') . 'value="25">25</option>';
                        $result .= '<option ' . ($limit==50 ? 'selected="selected" ' : '') . 'value="50">50</option>';
                        $result .= '<option ' . ($limit==100 ? 'selected="selected" ' : '') . 'value="100">100</option>';
                        $result .= '<option ' . ($limit==300 ? 'selected="selected" ' : '') . 'value="300">300</option>';
                    $result .= '</select>';

                    $result .= '<span class="super-results">' . $results_found . ' results</span>';

                    $result .= '<span class="super-next"></span>';
                    $result .= '<div class="super-nav">';
                        $url = esc_url(strtok($_SERVER["REQUEST_URI"], '?'));
                        $total_pages = ceil($results_found/$limit);
                        
                        // Previous 2 pages
                        if( $paged-2 > 1 ) {
                            $i = $paged-2;
                            $result .= '<a href="' . $url . '?page=' . $i . '" class="super-page' . ($paged==$i ? ' super-active' : '') . '" onclick="SUPER.frontEndListing.search(event, this)">' . $i . '</a>';
                        }
                        if( $paged-1 > 1 ) {
                            $i = $paged-1;
                            $result .= '<a href="' . $url . '?page=' . $i . '" class="super-page' . ($paged==$i ? ' super-active' : '') . '" onclick="SUPER.frontEndListing.search(event, this)">' . $i . '</a>';
                        }
                        
                        // Current page
                        $i = $paged;
                        $result .= '<a href="' . $url . '?page=' . $i . '" class="super-page' . ($paged==$i ? ' super-active' : '') . '" onclick="SUPER.frontEndListing.search(event, this)">' . $i . '</a>';
                        
                        // Next 2 pages
                        if( $paged+1 < $total_pages ) {
                            $i = $paged+1;
                            $result .= '<a href="' . $url . '?page=' . $i . '" class="super-page' . ($paged==$i ? ' super-active' : '') . '" onclick="SUPER.frontEndListing.search(event, this)">' . $i . '</a>';
                        }
                        if( $paged+2 < $total_pages ) {
                            $i = $paged+2;
                            $result .= '<a href="' . $url . '?page=' . $i . '" class="super-page' . ($paged==$i ? ' super-active' : '') . '" onclick="SUPER.frontEndListing.search(event, this)">' . $i . '</a>';
                        }

                        // if($paged>1){
                        //     // Pages to show before

                        // }
                        // if($paged==1){
                        //     // Only show pages after

                        // }
                        // $pages = 5

                          // Display page 3 (5-2)
                         // Display page 4 (5-1)
                        // Display page 5 
                         // Display page 6 (5+1)
                          // Display page 7 (5+2)

                        // $i = 0;
                        // while( $i < $total_pages ) {
                        //     $i++;
                        //     $result .= '<a href="' . $url . '?page=' . $i . '" class="super-page' . ($paged==$i ? ' super-active' : '') . '" onclick="SUPER.frontEndListing.search(event, this)">' . $i . '</a>';
                        // }
                    $result .= '</div>';
                    $result .= '<span class="super-prev"></span>';

                    $result .= '<select class="super-switcher" onchange="SUPER.frontEndListing.search(event, this)">';
                        $i = 0;
                        if($total_pages==0) $total_pages = 1;
                        while( $i < $total_pages ) {
                            $i++;
                            $result .= '<option' . ($paged==$i ? ' selected="selected"' : '') . '>' . $i . '</option>';
                        }
                    $result .= '</select>';

                    $result .= '<span class="super-pages">Pages</span>';

                $result .= '</div>';
            $result .= '</div>';
            return $result;
        }


        /**
         * Load Localisation files.
         * Note: the first-loaded translation file overrides any following ones if the same translation is present.
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'plugin_locale', get_locale(), 'super-forms' );
            load_textdomain( 'super-forms', WP_LANG_DIR . '/super-forms-' . $this->add_on_slug . '/super-forms-' . $this->add_on_slug . '-' . $locale . '.mo' );
            load_plugin_textdomain( 'super-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
        }


        /**
         * Display activation message for automatic updates
         *
         *  @since      1.0.0
        */
        public function display_activation_msg() {
            if( !class_exists('SUPER_Forms') ) {
                echo '<div class="notice notice-error">'; // notice-success
                    echo '<p>';
                    echo sprintf( 
                        esc_html__( '%sPlease note:%s You must install and activate %4$s%1$sSuper Forms%2$s%5$s in order to be able to use %1$s%s%2$s!', 'super_forms' ), 
                        '<strong>', 
                        '</strong>', 
                        'Super Forms - ' . $this->add_on_name, 
                        '<a target="_blank" href="https://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866">', 
                        '</a>' 
                    );
                    echo '</p>';
                echo '</div>';
            }
        }


        /**
         * Automatically update plugin from the repository
        */
        public function update_plugin() {
            if( defined('SUPER_PLUGIN_DIR') ) {
                if(include( SUPER_PLUGIN_DIR . '/includes/admin/plugin-update-checker/plugin-update-checker.php')){
                    $MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
                        'http://f4d.nl/@super-forms-updates/?action=get_metadata&slug=super-forms-' . $this->add_on_slug,  //Metadata URL
                        __FILE__, //Full path to the main plugin file.
                        'super-forms-' . $this->add_on_slug //Plugin slug. Usually it's the same as the name of the directory.
                    );
                }
            }
        }


        /**
         * Hook into settings and add Front-end Listing settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            
            // // First reminder settings
            // $array['listing'] = array(        
            //     'name' => esc_html__( 'Front-end Listing', 'super-forms' ),
            //     'label' => esc_html__( 'Front-end Listing', 'super-forms' ),
            //     'html' => array( '<style>.super-settings .super-listing-html-notice {display:none;}</style>', '<p class="super-listing-html-notice">' . sprintf( esc_html__( 'Need to send more E-mail reminders? You can increase the amount here:%s%s%sSuper Forms > Settings > Front-end Listing%s%s', 'super-forms' ), '<br />', '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#super-listing">', '<strong>', '</strong>', '</a>' ) . '</p>' ),
            //     'fields' => array(
            //         'email_reminder_amount' => array(
            //             'hidden' => true,
            //             'name' => esc_html__( 'Select how many individual E-mail reminders you require', 'super-forms' ),
            //             'desc' => esc_html__( 'If you need to send 10 reminders enter: 10', 'super-forms' ),
            //             'default' => SUPER_Settings::get_value( 0, 'email_reminder_amount', $settings['settings'], '3' )
            //         )
            //     )
            // );
             
            // if(empty($settings['settings']['email_reminder_amount'])) $settings['settings']['email_reminder_amount'] = 3;
            // $limit = absint($settings['settings']['email_reminder_amount']);
            // if($limit==0) $limit = 3;

            // $x = 1;
            // while($x <= $limit) {
            //     // Second reminder settings
            //     $reminder_settings = array(
            //         'email_reminder_'.$x => array(
            //             'hidden_setting' => true,
            //             'desc' => sprintf( esc_html__( 'Enable email reminder #%s', 'super-forms' ), $x ), 
            //             'default' => SUPER_Settings::get_value( 0, 'email_reminder_'.$x, $settings['settings'], '' ),
            //             'type' => 'checkbox',
            //             'values' => array(
            //                 'true' => sprintf( esc_html__( 'Enable email reminder #%s', 'super-forms' ), $x ),
            //             ),
            //             'filter' => true
            //         ),
            //         'email_reminder_'.$x.'_base_date' => array(
            //             'hidden_setting' => true,
            //             'name'=> esc_html__( 'Send reminder based on the following date:', 'super-forms' ),
            //             'label'=> esc_html__( 'Must be English formatted date. When using a datepicker that doesn\'t use the correct format, you can use the tag {date;timestamp} to retrieve the timestamp which will work correctly with any date format (leave blank to use the form submission date)', 'super-forms' ),
            //             'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_base_date', $settings['settings'], '' ),
            //             'filter'=>true,
            //             'parent'=>'email_reminder_'.$x,
            //             'filter_value'=>'true'
            //         ),
            //         'email_reminder_'.$x.'_date_offset' => array(
            //             'hidden_setting' => true,
            //             'name' => esc_html__( 'Define how many days after or before the reminder should be send based of the base date', 'super-forms' ),
            //             'label'=> esc_html__( '0 = The same day, 1 = Next day, 5 = Five days after, -1 = One day before, -3 = Three days before', 'super-forms' ),
            //             'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_date_offset', $settings['settings'], '0' ),
            //             'filter'=>true,
            //             'parent'=>'email_reminder_'.$x,
            //             'filter_value'=>'true'
            //         ),
            //         'email_reminder_'.$x.'_time_method' => array(
            //             'hidden_setting' => true,
            //             'name' => esc_html__( 'Send reminder at a fixed time, or by offset', 'super-forms' ),
            //             'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_time_method', $settings['settings'], 'fixed' ),
            //             'type' => 'select', 
            //             'values' => array(
            //                 'fixed' => esc_html__( 'Fixed (e.g: always at 09:00)', 'super-forms' ), 
            //                 'offset' => esc_html__( 'Offset (e.g: 2 hours after date)', 'super-forms' ),
            //             ),
            //             'filter'=>true,
            //             'parent'=>'email_reminder_'.$x,
            //             'filter_value'=>'true'
            //         ),
            //         'email_reminder_'.$x.'_time_fixed' => array(
            //             'hidden_setting' => true,
            //             'name' => esc_html__( 'Define at what time the reminder should be send', 'super-forms' ),
            //             'label'=> esc_html__( 'Use 24h format e.g: 13:00, 09:30 etc.', 'super-forms' ),
            //             'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_time_fixed', $settings['settings'], '09:00' ),
            //             'filter'=>true,
            //             'parent'=>'email_reminder_'.$x.'_time_method',
            //             'filter_value'=>'fixed'
            //         ),
            //         'email_reminder_'.$x.'_time_offset' => array(
            //             'hidden_setting' => true,
            //             'name' => esc_html__( 'Define at what offset the reminder should be send based of the base time', 'super-forms' ),
            //             'label'=> esc_html__( 'Example: 2 = Two hours after, -5 = Five hours before<br />(the base time will be the time of the form submission)', 'super-forms' ),
            //             'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_time_offset', $settings['settings'], '0' ),
            //             'filter'=>true,
            //             'parent'=>'email_reminder_'.$x.'_time_method',
            //             'filter_value'=>'offset'
            //         )
            //     );
            //     $array['listing']['fields'] = array_merge($array['listing']['fields'], $reminder_settings);


            //     $fields = $array['confirmation_email_settings']['fields'];
            //     $new_fields = array();
            //     foreach($fields as $k => $v){
            //         if($k=='confirm'){
            //             unset($fields[$k]);
            //             continue;
            //         }
            //         if( !empty($v['parent']) ) {
            //             if($v['parent']=='confirm'){
            //                 $v['parent'] = 'email_reminder_'.$x;
            //                 $v['filter_value'] = 'true';
            //             }else{
            //                 $v['parent'] = str_replace('confirm_', 'email_reminder_'.$x.'_', $v['parent']);
            //             }
            //         }
            //         unset($fields[$k]);
            //         $k = str_replace('confirm_', 'email_reminder_'.$x.'_', $k);
            //         if( !empty($v['default']) ) {
            //             $v['default'] = SUPER_Settings::get_value( 0, $k, $settings['settings'], $v['default'] );
            //         }
            //         $v['hidden_setting'] = true;
            //         $new_fields[$k] = $v;
            //     }
            //     $new_fields['email_reminder_'.$x.'_attachments'] = array(
            //         'hidden_setting' => true,
            //         'name' => sprintf( esc_html__( 'Attachments for reminder email #%s', 'super-forms' ), $x ),
            //         'desc' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
            //         'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_attachments', $settings['settings'], '' ),
            //         'type' => 'file',
            //         'multiple' => 'true',
            //         'filter'=>true,
            //         'parent'=>'email_reminder_'.$x,
            //         'filter_value'=>'true'
            //     );
            //     $array['listing']['fields'] = array_merge($array['listing']['fields'], $new_fields);
            //     $x++;
            // }

            // return $array;
        }


        /** 
         *  Get Ajax URL
         *
         *  @since      1.0.0
        */
        public function ajax_url() {
            return admin_url( 'admin-ajax.php', 'relative' );
        }

    }
        
endif;


/**
 * Returns the main instance of SUPER_Listing to prevent the need to use globals.
 *
 * @return SUPER_Listing
 */
if(!function_exists('SUPER_Listing')){
    function SUPER_Listing() {
        return SUPER_Listing::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Listing'] = SUPER_Listing();
}
