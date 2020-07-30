<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Front_End_Listing')) :


    /**
     * Main SUPER_Front_End_Listing Class
     *
     * @class SUPER_Front_End_Listing
     * @version 1.0.0
     */
    final class SUPER_Front_End_Listing {
    
        
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
        public $add_on_slug = 'front-end-listing';
        public $add_on_name = 'Front-end Listing';

        
        /**
         * @var SUPER_Front_End_Listing The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Front_End_Listing Instance
         *
         * Ensures only one instance of SUPER_Front_End_Listing is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Front_End_Listing()
         * @return SUPER_Front_End_Listing - Main instance
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
         * SUPER_Front_End_Listing Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_front_end_listing_loaded');
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
                add_action( 'super_create_form_front_end_listing_tab', array( $this, 'add_tab_content' ) );
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
                'name' => 'add_listings'
            );
            return $functions;
        }

        public static function add_style($styles){
            $assets_path = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            $styles['super-front-end-listing'] = array(
                'src'     => $assets_path . 'css/backend/styles.css',
                'deps'    => '',
                'version' => SUPER_Front_End_Listing()->version,
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
            $scripts['super-front-end-listing'] = array(
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
            $tabs['front_end_listing'] = esc_html__( 'Front-end Listing', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            //array( 'form_id'=>$form_id, 'translations'=>$translations, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'theme_style'=>$theme_style, 'style_content'=>$style_content )
            $tooltips = array(
                esc_html__( 'Give this listing a name', 'super-forms' ),
                esc_html__('Paste shortcode on any page', 'super-forms' ),
                esc_html__('Change Settings', 'super-forms' ),
                esc_html__('Delete Listing', 'super-forms' )
            );
            $shortcode = '[form-not-saved-yet]';
            $form_id = absint($atts['form_id']);
            ?>
            <ul class="front-end-listing-list">
                <?php
                // If no listings where found just add a default list
                if( !isset($atts['settings']['_listings']) ) $atts['settings']['_listings'] = array();

                if( count($atts['settings']['_listings'])==0 ) {
                    $atts['settings']['_listings'][0]['name'] = '';
                }
                foreach( $atts['settings']['_listings'] as $k => $list ) {
                    // Set default values if they don't exist
                    $list = self::get_default_listing_settings($list);

                    // Get the correct shortcode for this list
                    if( $form_id!=0 ) {
                        if( $k==0 ) {
                            $shortcode = '[super_listing list=&quot;1&quot; id=&quot;'. $form_id . '&quot;]';
                        }else{
                            $shortcode = '[super_listing list=&quot;' . ($k+1) . '&quot; id=&quot;'. $form_id . '&quot;]';
                        }
                    }

                    // Show settings to the user
                    ?>
                    <li>
                        <div class="super-group">
                            <span><?php echo esc_html__( 'List Name', 'super-forms' ); ?>:</span>
                            <input name="name" type="text" class="super-tooltip" title="<?php echo esc_attr($tooltips[0]); ?>" data-title="<?php echo esc_attr($tooltips[0]); ?>" value="<?php echo $list['name']; ?>">
                        </div>
                        <div class="super-group">
                            <span><?php echo esc_html__( 'Shortcode', 'super-forms' ); ?>:</span>
                            <input type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="<?php echo esc_attr($tooltips[1]); ?>" data-title="<?php echo esc_attr($tooltips[1]); ?>" value="<?php echo $shortcode; ?>">
                        </div>
                        <div class="super-setting super-tooltip" onclick="SUPER.frontEndListing.toggleSettings(this)" title="<?php echo esc_attr($tooltips[2]); ?>" data-title="<?php echo esc_attr($tooltips[2]); ?>"></div>
                        <div class="super-delete super-tooltip" onclick="SUPER.frontEndListing.deleteListing(this)" title="<?php echo esc_attr($tooltips[3]); ?>" data-title="<?php echo esc_attr($tooltips[3]); ?>"></div>
                        <div class="super-settings">
                            <div class="super-radio" data-name="display_based_on">
                                <span <?php echo ($list['display_based_on']=='this_form' ? 'class="super-active" ' : ''); ?>onclick="SUPER.frontEndListing.radio(this)" data-value="this_form">Only display entries based on this Form</span>
                                <span <?php echo ($list['display_based_on']=='all_forms' ? 'class="super-active" ' : ''); ?>onclick="SUPER.frontEndListing.radio(this)" data-value="all_forms">Display entries based on all forms</span>
                                <span <?php echo ($list['display_based_on']=='specific_forms' ? 'class="super-active" ' : ''); ?>onclick="SUPER.frontEndListing.radio(this)" data-value="specific_forms">Display entries based on the following Form ID's:<br /><input type="text" name="form_ids" placeholder="123,124" value="<?php echo sanitize_text_field($list['form_ids']); ?>" /><i>(seperate each ID with a comma)</i></span>
                            </div>
                            <div class="super-checkbox<?php echo ($list['date_range']!==false ? ' super-active' : ''); ?>" data-name="date_range">
                                <span onclick="SUPER.frontEndListing.checkbox(event, this)">Only display entries within the following date range:</span>
                                <div class="super-sub-settings">
                                    <div class="super-text">
                                        <span>From: <i>(or leave blank for no minimum date)</i></span>
                                        <input type="date" name="from" value="<?php echo (!empty($list['from']) ? sanitize_text_field($list['from']) : ''); ?>" />
                                    </div>
                                    <div class="super-text">
                                        <span>Till: <i>(or leave blank for no maximum date)</i></span>
                                        <input type="date" name="till" value="<?php echo (!empty($list['till']) ? sanitize_text_field($list['till']) : ''); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="super-checkbox<?php echo ($list['show_title']==true ? ' super-active' : ''); ?>" data-name="show_title">
                                <span<?php echo ($list['show_title']==true ? ' class="super-active"' : ''); ?> onclick="SUPER.frontEndListing.checkbox(event, this)">
                                    <span>Show "Title" column</span>
                                    <input class="super-tooltip" name="name" value="<?php echo $list['show_title']['name']; ?>" type="text" title="Column name" data-title="Column name" />
                                    <input class="super-tooltip" name="placeholder" value="<?php echo $list['show_title']['placeholder']; ?>" type="text" title="Filter placeholder" data-title="Filter placeholder" />
                                    <input class="super-tooltip" name="width" value="<?php echo absint($list['show_title']['width']); ?>" type="number" title="Column width (px)" data-title="Column width (px)" />
                                    <input class="super-tooltip" name="position" value="<?php echo absint($list['show_title']['position']); ?>" type="number" title="Column position" data-title="Column position" />
                                </span>
                            </div>
                            <div class="super-checkbox<?php echo ($list['show_status']==true ? ' super-active' : ''); ?>" data-name="show_status">
                                <span<?php echo ($list['show_status']==true ? ' class="super-active"' : ''); ?> onclick="SUPER.frontEndListing.checkbox(event, this)">
                                    <span>Show "Status" column</span>
                                    <input class="super-tooltip" name="name" value="<?php echo $list['show_status']['name']; ?>" type="text" title="Column name" data-title="Column name" />
                                    <input class="super-tooltip" name="placeholder" value="<?php echo $list['show_status']['placeholder']; ?>" type="text" title="Filter placeholder" data-title="Filter placeholder" />
                                    <input class="super-tooltip" name="width" value="<?php echo absint($list['show_status']['width']); ?>" type="number" title="Column width (px)" data-title="Column width (px)" />
                                    <input class="super-tooltip" name="position" value="<?php echo absint($list['show_status']['position']); ?>" type="number" title="Column position" data-title="Column position" />
                                </span>
                            </div>
                            <div class="super-checkbox<?php echo ($list['show_date']==true ? ' super-active' : ''); ?>" data-name="show_date">
                                <span<?php echo ($list['show_date']==true ? ' class="super-active"' : ''); ?> onclick="SUPER.frontEndListing.checkbox(event, this)">
                                    <span>Show "Date created" column</span>
                                    <input class="super-tooltip" name="name" value="<?php echo $list['show_date']['name']; ?>" type="text" title="Column name" data-title="Column name" />
                                    <input class="super-tooltip" name="placeholder" value="<?php echo $list['show_date']['placeholder']; ?>" type="text" title="Filter placeholder" data-title="Filter placeholder" />
                                    <input class="super-tooltip" name="width" value="<?php echo absint($list['show_date']['width']); ?>" type="number" title="Column width (px)" data-title="Column width (px)" />
                                    <input class="super-tooltip" name="position" value="<?php echo absint($list['show_date']['position']); ?>" type="number" title="Column position" data-title="Column position" />
                                </span>
                            </div>
                            <div class="super-checkbox<?php echo ($list['custom_columns']==true ? ' super-active' : ''); ?>" data-name="custom_columns">
                                <span<?php echo ($list['custom_columns']==true ? ' class="super-active"' : ''); ?>  onclick="SUPER.frontEndListing.checkbox(event, this)">Show the following "Custom" columns</span>
                                <div class="super-sub-settings">
                                    <ul>
                                        <?php
                                        $columns = $list['columns'];
                                        foreach( $columns as $ck => $cv ) {
                                            if( !isset($cv['filter']) ) {
                                                $cv['filter'] = 'text'; // Default filter to 'text'
                                            }
                                            ?>   
                                            <li>
                                                <span class="sort-up" onclick="SUPER.frontEndListing.sortColumn(this, 'up')"></span>
                                                <span class="sort-down" onclick="SUPER.frontEndListing.sortColumn(this, 'down')"></span>
                                                <div class="super-text">
                                                    <span>Column name:</span>
                                                    <input type="text" name="name" value="<?php echo $cv['name']; ?>" />
                                                </div>
                                                <div class="super-text">
                                                    <span>Map to the following field <i>(enter a field name)</i>:</span>
                                                    <input type="text" name="field_name" value="<?php echo $cv['field_name']; ?>" />
                                                </div>
                                                <div class="super-text">
                                                    <span>Column width <i>(in px)</i>:</span>
                                                    <input type="number" name="width" value="<?php echo $cv['width']; ?>" />
                                                </div>

                                                <div class="super-text">
                                                    <span>Filter method:</span>
                                                    <select name="filter" onchange="SUPER.frontEndListing.showFilterItems(this)">
                                                        <option<?php echo ($cv['filter']=='none' ? ' selected="selected"' : ''); ?> value="none"><?php echo esc_html__( 'No filter', 'super-forms' ); ?></option>
                                                        <option<?php echo ($cv['filter']=='text' ? ' selected="selected"' : ''); ?> value="text"><?php echo esc_html__( 'Text field (default)', 'super-forms' ); ?></option>
                                                        <option<?php echo ($cv['filter']=='dropdown' ? ' selected="selected"' : ''); ?> value="dropdown"><?php echo esc_html__( 'Dropdown', 'super-forms' ); ?></option>
                                                    </select>
                                                </div>
                                                <div class="super-text super-filter-items"<?php echo ($cv['filter']!=='dropdown' ? ' style="display:none;"' : ''); ?>>
                                                    <span>Filter options <i>(put each on a new line)</i>:</span>
                                                    <textarea name="filter_items" placeholder="<?php echo esc_attr__( "option_value1|Option Label 1\noption_value2|Option Label 2", 'super-forms' ); ?>"><?php echo (isset($cv['filter_items']) ? $cv['filter_items'] : ''); ?></textarea>
                                                </div>

                                                <span class="super-add-column" onclick="SUPER.frontEndListing.addColumn(this)"></span>
                                                <span class="super-delete-column" onclick="SUPER.frontEndListing.deleteColumn(this)"></span>
                                            </li>
                                            <?php
                                        }
                                        ?>

                                    </ul>
                                </div>
                            </div>
                            <div class="super-checkbox<?php echo ($list['edit_any']!==false ? ' super-active' : ''); ?>" data-name="edit_any">
                                <span onclick="SUPER.frontEndListing.checkbox(event, this)">Allow the following users to edit any entry</span>
                                <div class="super-sub-settings">
				    <div class="super-radio" data-name="method">
					<span <?php echo ($list['edit_any']['method']=='modal' ? 'class="super-active" ' : ''); ?>onclick="SUPER.frontEndListing.radio(this)" data-value="modal"><?php echo esc_html__( 'Open form in a modal (default)', 'super-forms' ); ?></span>
					<span <?php echo ($list['edit_any']['method']=='url' ? 'class="super-active" ' : ''); ?>onclick="SUPER.frontEndListing.radio(this)" data-value="url"><?php echo esc_html__( 'Open via form page (Form Location must be configured for your forms)', 'super-forms' ); ?></span>
				    </div>
                                    <div class="super-text">
                                        <span>User roles:</span>
                                        <input type="text" name="user_roles" />
                                    </div>
                                    <div class="super-text">
                                        <span>User ID's:</span>
                                        <input type="text" name="user_ids" />
                                    </div>
                                </div>
                            </div>
                            <div class="super-checkbox" data-name="edit_own">
                                <span onclick="SUPER.frontEndListing.checkbox(event, this)">Allow the following users to edit their own entries</span>
                                <div class="super-sub-settings">
                                    <div class="super-text">
                                        <span>User roles:</span>
                                        <input type="text" name="user_roles" />
                                    </div>
                                    <div class="super-text">
                                        <span>User ID's:</span>
                                        <input type="text" name="user_ids" />
                                    </div>
                                </div>
                            </div>
                            <div class="super-checkbox" data-name="delete_any">
                                <span onclick="SUPER.frontEndListing.checkbox(event, this)">Allow the following users to delete any entry</span>
                                <div class="super-sub-settings">
                                    <div class="super-text">
                                        <span>User roles:</span>
                                        <input type="text" name="user_roles" />
                                    </div>
                                    <div class="super-text">
                                        <span>User ID's:</span>
                                        <input type="text" name="user_ids" />
                                    </div>
                                </div>
                            </div>
                            <div class="super-checkbox" data-name="delete_own">
                                <span onclick="SUPER.frontEndListing.checkbox(event, this)">Allow the following users to delete their own entries</span>
                                <div class="super-sub-settings">
                                    <div class="super-text">
                                        <span>User roles:</span>
                                        <input type="text" name="user_roles" />
                                    </div>
                                    <div class="super-text">
                                        <span>User ID's:</span>
                                        <input type="text" name="user_ids" />
                                    </div>
                                </div>
                            </div>
                            <div class="super-radio" data-name="pagination">
                                <span <?php echo ($list['pagination']=='pages' ? 'class="super-active" ' : ''); ?>onclick="SUPER.frontEndListing.radio(this)" data-value="pages" class="super-active">Show pagination</span>
                                <span <?php echo ($list['pagination']=='load_more' ? 'class="super-active" ' : ''); ?>onclick="SUPER.frontEndListing.radio(this)" data-value="load_more">Show "Load More" button</span>
                            </div>
                            <div class="super-dropdown">
                                <span>Results per page:</span>
                                <select name="limit">
                                    <option <?php echo ($list['limit']==10 ? 'selected="selected" ' : ''); ?>value="10">10</option>
                                    <option <?php echo ($list['limit']==25 ? 'selected="selected" ' : ''); ?>value="25">25</option>
                                    <option <?php echo ($list['limit']==50 ? 'selected="selected" ' : ''); ?>value="50">50</option>
                                    <option <?php echo ($list['limit']==100 ? 'selected="selected" ' : ''); ?>value="100">100</option>
                                    <option <?php echo ($list['limit']==300 ? 'selected="selected" ' : ''); ?>value="300">300</option>
                                </select>
                            </div>
                        </div>
                    </li>
                    <?php
                }
                ?>
            </ul>
            <div class="create-listing">
                <span class="super-create-listing" onclick="SUPER.frontEndListing.addListing(this)"><?php echo esc_html__( 'Add List', 'super-forms' ); ?></span>
            </div>
            <?php
        }

        // Get default listing settings
        public static function get_default_listing_settings($list) {
            if( empty($list['edit_method']) ) $list['edit_method'] = 'modal';
            if( empty($list['display_based_on']) ) $list['display_based_on'] = 'this_form';
            if( empty($list['form_ids']) ) $list['form_ids'] = '';

            if( empty($list['date_range']) || (!is_array($list['date_range'])) ) $list['date_range'] = array();
            if( empty($list['date_range']['from']) ) $list['date_range']['from'] = '';
            if( empty($list['date_range']['till']) ) $list['date_range']['till'] = '';
	   
            if( empty($list['show_title']) || (!is_array($list['show_title'])) ) $list['show_title'] = array();
            if( empty($list['show_title']['name']) ) $list['show_title']['name'] = esc_html__( 'Title', 'super-forms' );
            if( empty($list['show_title']['placeholder']) ) $list['show_title']['placeholder'] = esc_html__( 'Filter by title', 'super-forms' );
            if( empty($list['show_title']['position']) ) $list['show_title']['position'] = 1;
            if( empty($list['show_title']['width']) ) $list['show_title']['width'] = 150;

            if( empty($list['show_status']) || (!is_array($list['show_status'])) ) $list['show_status'] = array();
            if( empty($list['show_status']['name']) ) $list['show_status']['name'] = esc_html__( 'Status', 'super-forms' );
            if( empty($list['show_status']['placeholder']) ) $list['show_status']['placeholder'] = esc_html__( '- choose status -', 'super-forms' );
            if( empty($list['show_status']['position']) ) $list['show_status']['position'] = 2;
            if( empty($list['show_status']['width']) ) $list['show_status']['width'] = 150;
	    
            if( empty($list['show_date']) || (!is_array($list['show_date'])) ) $list['show_date'] = array();
            if( empty($list['show_date']['name']) ) $list['show_date']['name'] = esc_html__( 'Date created', 'super-forms' );
            if( empty($list['show_date']['placeholder']) ) $list['show_date']['placeholder'] = esc_html__( 'Filter by date', 'super-forms' );
            if( empty($list['show_date']['position']) ) $list['show_date']['position'] = 3;
            if( empty($list['show_date']['width']) ) $list['show_date']['width'] = 150;

            if( empty($list['custom_columns']) ) $list['custom_columns'] = false;
            if( empty($list['columns']) || (!is_array($list['columns'])) ) $list['columns'] = array(
                array(
                    'name' => 'E-mail',
                    'field_name' => 'email',
                    'width' => 150
                )
            );
	    
            if( empty($list['edit_any']) ) $list['edit_any'] = array();
            if( empty($list['edit_any']['method']) ) $list['edit_any']['method'] = 'modal';

            if( empty($list['pagination']) ) $list['pagination'] = 'page';
            if( empty($list['limit']) ) $list['limit'] = 25;
            return $list;
        }

        // Return data for script handles.
        public static function register_shortcodes(){
            add_shortcode( 'super_listing', array( 'SUPER_Front_End_Listing', 'super_listing_func' ) );
        }

        // The form shortcode that will generate the list/table with all Contact Entries
        public static function super_listing_func( $atts ) {

            extract( shortcode_atts( array(
                'id' => '', // Retrieve entries from specific form ID
                'list' => '' // Determine what list settings to use
            ), $atts ) );

            // Sanitize the ID
            $form_id = absint($id);

            // Check if the post exists
            if ( FALSE === get_post_status( $form_id ) ) {
                // The post does not exist
                $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with Form ID: %d', 'super-forms' ), $form_id);
                return $result;
            }else{
                // Check if the post is a super_form post type
                $post_type = get_post_type($form_id);
                if( $post_type!='super_form' ) {
                        $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with Form ID: %d', 'super-forms' ), $form_id);
                        return $result;
                }
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
            $handle = 'super-front-end-listing';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'assets/js/frontend/script.js', array( 'super-common' ), SUPER_Front_End_Listing()->version, false );  
            wp_localize_script(
                $handle,
                $name,
                array( 
                    'get_home_url' => get_home_url(),
                    'ajaxurl' => $ajax_url,
                    'wp_root' => ABSPATH
                )
            );
            wp_enqueue_script( $handle );
            // wp_enqueue_script( 'super-front-end-listing', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/script.js', array( 'super-common' ), $this->version, false );  
            
            wp_enqueue_style( 'super-front-end-listing', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/styles.css', array(), SUPER_Front_End_Listing()->version );
            SUPER_Forms()->enqueue_fontawesome_styles();

            // Get the settings for this specific list based on it's index
            $list_id = absint($atts['list'])-1;
            if(!isset($settings['_listings'][$list_id])){
                // The list does not exist
                $result = '<strong>'.esc_html__('Error', 'super-forms' ).':</strong> '.sprintf(esc_html__('Super Forms could not find a listing with ID: %d', 'super-forms' ), $list_id);
                return $result;
            }
            // Set default values if they don't exist
            $list = self::get_default_listing_settings($settings['_listings'][$list_id]);

            $columns = array();
            // Check if "Title" column is enabled
            if( $list['show_title']!==true ) {
                $columns['post_title'] = array(
                    'position' => absint($list['show_title']['position']),
                    'name' => $list['show_title']['name'],
                    'width' => absint($list['show_title']['width']),
                    'filter' => array(
                        'field_type' => 'text',
                        'placeholder' => $list['show_title']['placeholder']
                    )
                );
            }
            // Check if "Status" column is enabled
            if( $list['show_status']==true ) {
                $items = array();
                foreach(SUPER_Settings::get_entry_statuses() as $k => $v){
                    $items[$k] = $v['name']; 
                }
                $columns['entry_status'] = array(
                    'position' => absint($list['show_status']['position']),
                    'name' => $list['show_status']['name'],
                    'width' => absint($list['show_status']['width']),
                    'filter' => array(
                        'field_type' => 'dropdown',
                        'placeholder' => $list['show_status']['placeholder'],
                        'items' => $items
                    )
                );
            }
            // Check if "Date" column is enabled
            if( $list['show_date']==true ) {
                // Always put default date column at the end
                $columns['post_date'] = array(
                    'position' => absint($list['show_date']['position']),
                    'name' => $list['show_date']['name'],
                    'width' => absint($list['show_date']['width']),
                    'filter' => array(
                        'field_type' => 'datepicker',
                        'placeholder' => $list['show_date']['placeholder']
                    )
                );
            }

            // Add custom columns if enabled
            if($list['custom_columns']==true){
                $columns = array_merge($columns, $list['columns']);      
            }

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

            global $wpdb;

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
            SELECT ID, post_title, post_date, meta.meta_value AS data, entry_status.meta_value AS status
            FROM $wpdb->posts AS post 
            INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data'
            LEFT JOIN $wpdb->postmeta AS entry_status ON entry_status.post_id = post.ID AND entry_status.meta_key = '_super_contact_entry_status'
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
                            $result .= apply_filters( 'super_front_end_listing_actions_filter', $actions, $entry );
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
                            $data = unserialize($entry->data);
                            $result .= '<div class="super-entry" data-id="' . $entry->ID . '">';
                                $result .= '<div class="super-col super-check"></div>';
                                $result .= '<div class="super-col super-actions">';
                                    if( (!empty($list['edit_any'])) && ($list['edit_any']['method']=='url') ) {
                                        $url = $settings['form_location']; 
                                        $query = parse_url($url, PHP_URL_QUERY);
                                        // Returns a string if the URL has parameters or NULL if not
                                        if ($query) {
                                            $url .= '&contact_entry_id=' . $entry->ID;
                                        } else {
                                            $url .= '?contact_entry_id=' . $entry->ID;
                                        }
                                        $actions = '<a class="super-edit" target="_blank" href="' . esc_url($url) . '"></a>';
                                    }else{
                                        $actions = '<span class="super-edit" onclick="SUPER.frontEndListing.editEntry(this)"></span>';
                                    }
                                    $actions .= '<span class="super-view" onclick="SUPER.frontEndListing.viewEntry(this)"></span>';
                                    $actions .= '<span class="super-delete" onclick="SUPER.frontEndListing.deleteEntry(this)"></span>';
                                    $result .= apply_filters( 'super_front_end_listing_actions_filter', $actions, $entry );
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
                                        if($column_key=='post_title'){
                                            $result .= $entry->post_title;
                                        }elseif($column_key=='entry_status'){
                                            if( (isset($statuses[$entry->status])) && ($entry->status!='') ) {
                                                $result .= '<span class="super-entry-status super-entry-status-' . $entry->status . '" style="color:' . $statuses[$entry->status]['color'] . ';background-color:' . $statuses[$entry->status]['bg_color'] . '">' . $statuses[$entry->status]['name'] . '</span>';
                                            }else{
                                                $post_status = get_post_status($entry->ID);
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
                                                    $result .= $data[$column_key]['value'];
                                                }else{
                                                    // If not then it must be a special field, for instance file uploads
                                                }
                                            }else{
                                                // No data found for this entry
                                                $result .= '';
                                            }
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
            // $array['front_end_listing'] = array(        
            //     'name' => esc_html__( 'Front-end Listing', 'super-forms' ),
            //     'label' => esc_html__( 'Front-end Listing', 'super-forms' ),
            //     'html' => array( '<style>.super-settings .front-end-listing-html-notice {display:none;}</style>', '<p class="front-end-listing-html-notice">' . sprintf( esc_html__( 'Need to send more E-mail reminders? You can increase the amount here:%s%s%sSuper Forms > Settings > Front-end Listing%s%s', 'super-forms' ), '<br />', '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#front-end-listing">', '<strong>', '</strong>', '</a>' ) . '</p>' ),
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
            //     $array['front_end_listing']['fields'] = array_merge($array['front_end_listing']['fields'], $reminder_settings);


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
            //     $array['front_end_listing']['fields'] = array_merge($array['front_end_listing']['fields'], $new_fields);
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
 * Returns the main instance of SUPER_Front_End_Listing to prevent the need to use globals.
 *
 * @return SUPER_Front_End_Listing
 */
if(!function_exists('SUPER_Front_End_Listing')){
    function SUPER_Front_End_Listing() {
        return SUPER_Front_End_Listing::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Front_End_Listing'] = SUPER_Front_End_Listing();
}
