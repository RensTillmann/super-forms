<?php
/**
 * Super Forms - Front-end Listing
 *
 * @package   Super Forms - Front-end Listing
 * @author    feeling4design
 * @link      http://codecanyon.net/user/feeling4design
 * @copyright 2019 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Front-end Listing
 * Plugin URI:  http://codecanyon.net/user/feeling4design
 * Description: Allows you to list contact entries on your front-end
 * Version:     1.0.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
*/

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
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
                add_action( 'init', array( $this, 'update_plugin' ) );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );
                
                add_filter( 'super_enqueue_styles', array( $this, 'add_style' ), 10, 1 );
                add_filter( 'super_enqueue_scripts', array( $this, 'add_script' ), 10, 1 );

            }
        }
        public static function add_style($styles){
            $assets_path = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            $styles['super-front-end-listing'] = array(
                'src'     => $assets_path . 'css/backend/style.css',
                'deps'    => '',
                'version' => $this->version,
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
            $tabs['front_end_listing'] = __( 'Front-end Listing', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            //array( 'form_id'=>$form_id, 'translations'=>$translations, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'theme_style'=>$theme_style, 'style_content'=>$style_content )
            $tooltips = array(
                __( 'Give this listing a name', 'super-forms' ),
                __('Paste shortcode on any page', 'super-forms' ),
                __('Change Settings', 'super-forms' ),
                __('Delete Listing', 'super-forms' )
            );
            ?>
            <ul class="front-end-listing-list">
                <li>
                    <div class="super-group">
                        <span><?php echo esc_html__( 'List Name', 'super-forms' ); ?>:</span>
                        <input type="text" class="super-tooltip" title="<?php echo esc_attr($tooltips[0]); ?>" data-title="<?php echo esc_attr($tooltips[0]); ?>" value="">
                    </div>
                    <div class="super-group">
                        <span><?php echo esc_html__( 'Shortcode', 'super-forms' ); ?>:</span>
                        <input type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="<?php echo esc_attr($tooltips[1]); ?>" data-title="<?php echo esc_attr($tooltips[1]); ?>" value="">
                    </div>
                    <div class="super-setting super-tooltip" onclick="SUPER.frontEndListing.toggleSettings(this)" title="<?php echo esc_attr($tooltips[2]); ?>" data-title="<?php echo esc_attr($tooltips[2]); ?>"></div>
                    <div class="super-delete super-tooltip" onclick="SUPER.frontEndListing.deleteListing(this)" title="<?php echo esc_attr($tooltips[3]); ?>" data-title="<?php echo esc_attr($tooltips[3]); ?>"></div>
                    <div class="super-settings">
                        <div class="super-radio" data-name="display_based_on">
                            <span class="super-active" onclick="SUPER.frontEndListing.radio(this)" data-value="this_form">Only display entries based on this Form</span>
                            <span onclick="SUPER.frontEndListing.radio(this)" data-value="all_forms">Display entries based on all forms</span>
                            <span onclick="SUPER.frontEndListing.radio(this)" data-value="specific_forms">Display entries based on the following Form ID's:<br /><input type="text" name="form_ids" placeholder="123,124" /><i>(seperate each ID with a comma)</i></span>
                        </div>
                        <div class="super-checkbox" data-name="date_range">
                            <span onclick="SUPER.frontEndListing.checkbox(event, this)">Only display entries within the following date range:</span>
                            <div class="super-sub-settings">
                                <div class="super-text">
                                    <span>From: <i>(or leave blank for no minimum date)</i></span>
                                    <input type="date" name="date_range_from" />
                                </div>
                                <div class="super-text">
                                    <span>Till: <i>(or leave blank for no maximum date)</i></span>
                                    <input type="date" name="date_range_till" />
                                </div>
                            </div>
                        </div>
                        <div class="super-checkbox" data-name="show_title">
                            <span onclick="SUPER.frontEndListing.checkbox(event, this)">Show "Title" column<input name="title_position" type="number" placeholder="Column position" /></span>
                        </div>
                        <div class="super-checkbox" data-name="show_status">
                            <span onclick="SUPER.frontEndListing.checkbox(event, this)">Show "Status" column<input name="status_position" type="number" placeholder="Column position" /></span>
                        </div>
                        <div class="super-checkbox" data-name="show_date">
                            <span onclick="SUPER.frontEndListing.checkbox(event, this)">Show "Date created" column<input name="date_position" type="number" placeholder="Column position" /></span>
                        </div>
                        <div class="super-checkbox" data-name="custom_columns">
                            <span onclick="SUPER.frontEndListing.checkbox(event, this)">Show the following "Custom" columns</span>
                            <div class="super-sub-settings">
                                <div class="super-text">
                                    <span>Column title:</span>
                                    <input type="text" name="column_title" />
                                </div>
                                <div class="super-text">
                                    <span>Map to the following field <i>(enter a field name)</i>:</span>
                                    <input type="text" name="field_name" />
                                </div>
                                <div class="super-text">
                                    <span>Column width <i>(in px)</i>:</span>
                                    <input type="text" name="column_width" />
                                </div>
                            </div>
                        </div>
                        <div class="super-checkbox" data-name="edit_any">
                            <span onclick="SUPER.frontEndListing.checkbox(event, this)">Allow the following users to edit any entry</span>
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
                            <span onclick="SUPER.frontEndListing.radio(this)" data-value="pages" class="super-active">Show pagination</span>
                            <span onclick="SUPER.frontEndListing.radio(this)" data-value="load_more">Show "Load More" button</span>
                        </div>
                        <div class="super-dropdown">
                            <span>Results per page:</span>
                            <select name="limit">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="300">300</option>
                            </select>
                        </div>
                    </div>
                </li>
            </ul>
            <div class="create-listing">
                <span class="super-create-listing" onclick="SUPER.frontEndListing.addListing(this)"><?php echo esc_html__( 'Add List', 'super-forms' ); ?></span>
            </div>
            <?php
        }

        // Return data for script handles.
        public static function register_shortcodes(){
            add_shortcode( 'super_listing', array( $this, 'super_listing_func' ) );
        }

        // The form shortcode that will generate the list/table with all Contact Entries
        public static function super_listing_func( $atts ) {
            wp_enqueue_style( 'super-front-end-listing', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/styles.css', array(), $this->version );

            extract( shortcode_atts( array(
                'form_id' => '', // Retrieve entries from specific form ID
                'user_id' => '', // Retrieve entries from specific user ID, by default only returns entries based on current logged in user ID
                'columns' => '' // A list with custom columns (form field names), empty defaults to: Entry Title, E-mail, Date
            ), $atts ) );

            $columns = array(
                'post_title' => array(
                    'name' => 'Order #',
                    'width' => '150px',
                    'filter' => array(
                        'field_type' => 'text',
                        'placeholder' => 'Search Order #',
                    )
                ),
                'entry_status' => array(
                    'name' => 'Status',
                    'width' => '150px',
                    'filter' => array(
                        'field_type' => 'dropdown',
                        'placeholder' => 'Search Order #',
                        'items' => array(
                            'proposal_send' => 'Proposal send',
                            'cancelled' => 'Cancelled',
                        ),
                    )
                ),
            );
            $custom_columns = array(
                'billing_company' => array(
                    'name' => 'Entity',
                    'width' => '100px',
                ),
                'total_monthly_payment' => array(
                    'name' => 'Monthly',
                    'width' => '100px',
                ),
                'lease_months' => array(
                    'name' => 'Lease Term',
                    'width' => '130px',
                )
            );

// For now these are the columns:
// billing_company|Entity
// post_title|Order Name
// total_monthly_payment|Monthly
// lease_months|Lease Term
// I will probably need 1-2 more but need to figure out a few things first

            $columns = array_merge($columns, $custom_columns);
            // Always put default date column at the end
            $columns['post_date'] = array(
                'name' => 'Date Created',
                'width' => '150px',
                'filter' => array(
                    'field_type' => 'datepicker',
                    'placeholder' => '10/07/2019'
                )
            );

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
                $result .= '<div class="super-columns">';
                    foreach( $columns as $k => $v ) {
                        // If a max width was defined use it on the col-wrap
                        $styles = '';
                        if( !empty( $v['width'] ) ) {
                            $styles = 'width:' . $v['width'];
                        }
                        if( !empty( $styles ) ) {
                            $styles = ' style="' . $styles . '"';
                        }

                        $result .= '<div class="super-col-wrap"' . $styles . '>';
                            $result .= '<span class="super-col-name">' . $v['name'] . '</span>';
                            $result .= '<div class="super-col-sort">';
                                $result .= '<span class="super-sort-down">↓</span>';
                                $result .= '<span class="super-sort-up">↑</span>';
                            $result .= '</div>';
                            if( isset($v['filter']) && is_array($v['filter']) ) {
                                $result .= '<div class="super-col-filter">';
                                    if($v['filter']['field_type']=='text'){
                                        $result .= '<input type="text" placeholder="' . $v['filter']['placeholder'] . '" />';
                                    }
                                    if($v['filter']['field_type']=='datepicker'){
                                        $result .= '<input type="date" placeholder="' . $v['filter']['placeholder'] . '" />';
                                    }
                                    if($v['filter']['field_type']=='dropdown'){
                                        $result .= '<select placeholder="' . $v['filter']['placeholder'] . '" >';
                                            foreach($v['filter']['items'] as $value => $name){
                                                $result .= '<option value="' . $value . '">' . $name . '</option>';
                                            }
                                        $result .= '</select>';
                                    }
                                $result .= '</div>';
                            }
                        $result .= '</div>';
                    }
                $result .= '</div>';

                $result .= '<div class="super-entries">';
                    global $wpdb;

                    $limit = 25;
                    $offset = 0; // If page is 1, offset is 0, If page is 2 offset is 1 etc.
                    $paged = (get_query_var('page')) ? get_query_var('page') : 1;
                    $offset = $limit*($paged-1);

                    $query = "
                    SELECT ID, post_title, post_date, 
                    (SELECT meta_value FROM $wpdb->postmeta AS meta WHERE meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data') AS data,
                    (SELECT meta_value FROM $wpdb->postmeta AS meta WHERE meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_status') AS status
                    FROM $wpdb->posts AS post 
                    WHERE post_type = 'super_contact_entry'
                    ORDER BY post_date DESC
                    LIMIT $limit
                    OFFSET $offset
                    ";
                    $entries = $wpdb->get_results($query);

                    if( !class_exists( 'SUPER_Settings' ) ) require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
                    $global_settings = SUPER_Common::get_global_settings();
                    $statuses = SUPER_Settings::get_entry_statuses($global_settings);
                    foreach($entries as $entry){
                        $data = unserialize($entry->data);
                        $result .= '<div class="super-entry">';
                            $result .= '<div class="super-col super-check"></div>';
                            foreach( $columns as $ck => $cv ) {
                                // If a max width was defined use it on the col-wrap
                                $styles = '';
                                if( !empty( $cv['width'] ) ) {
                                    $styles = 'width:' . $cv['width'];
                                }
                                if( !empty( $styles ) ) {
                                    $styles = ' style="' . $styles . '"';
                                }
                                $result .= '<div class="super-col super-' . $ck . '"' . $styles . '>';
                                    if($ck=='post_title'){
                                        $result .= '<a href="#">' . $entry->post_title . '</a>';
                                    }elseif($ck=='entry_status'){
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
                                    }elseif($ck=='post_date'){
                                        $result .= date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) );
                                        //echo get_the_date($entry->post_date);
                                    }else{
                                        // Check if this data key exists
                                        if(isset($data[$ck])){
                                            // Check if it has a value, if so print it
                                            if(isset($data[$ck]['value'])){
                                                $result .= $data[$ck]['value'];
                                            }else{
                                                // If not then it must be a special field, for instance file uploads
                                            }
                                        }
                                    }
                                $result .= '</div>';
                            }
                            $result .= '<div class="super-col super-actions">';
                                /*
                                <!-- <a href="http://metakraftlabs.net/woo/ronny-v2/?contact_entry_id=<?php echo $entry->ID; ?>" class="super-edit">Edit</a> -->
                                */
                                $result .= '<a target="_blank" href="http://cpq360.com/quote-builder/?contact_entry_id=11338" class="super-edit">Edit</a>';
                                $result .= '<span class="super-view">View</span>';
                                $result .= '<span class="super-delete">Delete</span>';
                           $result .= ' </div>';
                        $result .= '</div>';
                    }
                $result .= '</div>';

                $result .= '<div class="super-pagination">';

                    $url = strtok($_SERVER["REQUEST_URI"], '?');
                    $result .= '<select class="super-limit">';
                        $result .= '<option>10</option>';
                        $result .= '<option selected="selected">25</option>';
                        $result .= '<option>50</option>';
                        $result .= '<option>100</option>';
                        $result .= '<option>300</option>';
                    $result .= '</select>';

                    $result .= '<span class="super-results"><?php echo count($entries); ?> results</span>';

                    $result .= '<span class="super-next">></span>';
                    $result .= '<div class="super-nav">';
                        $result .= '<a href="<?php echo $url; ?>?page=1" class="super-page super-active">1</a>';
                        $result .= '<a href="<?php echo $url; ?>?page=2" class="super-page">2</a>';
                        $result .= '<a href="<?php echo $url; ?>?page=3" class="super-page">3</a>';
                    $result .= '</div>';
                    $result .= '<span class="super-prev"><</span>';


                    $result .= '<select class="super-switcher">';
                        $result .= '<option>1</option>';
                        $result .= '<option>2</option>';
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
            
            // First reminder settings
            $array['front_end_listing'] = array(        
                'name' => esc_html__( 'Front-end Listing', 'super-forms' ),
                'label' => esc_html__( 'Front-end Listing', 'super-forms' ),
                'html' => array( '<style>.super-settings .front-end-listing-html-notice {display:none;}</style>', '<p class="front-end-listing-html-notice">' . sprintf( esc_html__( 'Need to send more E-mail reminders? You can increase the amount here:%s%s%sSuper Forms > Settings > Front-end Listing%s%s', 'super-forms' ), '<br />', '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#front-end-listing">', '<strong>', '</strong>', '</a>' ) . '</p>' ),
                'fields' => array(
                    'email_reminder_amount' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Select how many individual E-mail reminders you require', 'super-forms' ),
                        'desc' => esc_html__( 'If you need to send 10 reminders enter: 10', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'email_reminder_amount', $settings['settings'], '3' )
                    )
                )
            );
             
            if(empty($settings['settings']['email_reminder_amount'])) $settings['settings']['email_reminder_amount'] = 3;
            $limit = absint($settings['settings']['email_reminder_amount']);
            if($limit==0) $limit = 3;

            $x = 1;
            while($x <= $limit) {
                // Second reminder settings
                $reminder_settings = array(
                    'email_reminder_'.$x => array(
                        'hidden_setting' => true,
                        'desc' => sprintf( esc_html__( 'Enable email reminder #%s', 'super-forms' ), $x ), 
                        'default' => SUPER_Settings::get_value( 0, 'email_reminder_'.$x, $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => sprintf( esc_html__( 'Enable email reminder #%s', 'super-forms' ), $x ),
                        ),
                        'filter' => true
                    ),
                    'email_reminder_'.$x.'_base_date' => array(
                        'hidden_setting' => true,
                        'name'=> esc_html__( 'Send reminder based on the following date:', 'super-forms' ),
                        'label'=> esc_html__( 'Must be English formatted date. When using a datepicker that doesn\'t use the correct format, you can use the tag {date;timestamp} to retrieve the timestamp which will work correctly with any date format (leave blank to use the form submission date)', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_base_date', $settings['settings'], '' ),
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x,
                        'filter_value'=>'true'
                    ),
                    'email_reminder_'.$x.'_date_offset' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Define how many days after or before the reminder should be send based of the base date', 'super-forms' ),
                        'label'=> esc_html__( '0 = The same day, 1 = Next day, 5 = Five days after, -1 = One day before, -3 = Three days before', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_date_offset', $settings['settings'], '0' ),
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x,
                        'filter_value'=>'true'
                    ),
                    'email_reminder_'.$x.'_time_method' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Send reminder at a fixed time, or by offset', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_time_method', $settings['settings'], 'fixed' ),
                        'type' => 'select', 
                        'values' => array(
                            'fixed' => esc_html__( 'Fixed (e.g: always at 09:00)', 'super-forms' ), 
                            'offset' => esc_html__( 'Offset (e.g: 2 hours after date)', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x,
                        'filter_value'=>'true'
                    ),
                    'email_reminder_'.$x.'_time_fixed' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Define at what time the reminder should be send', 'super-forms' ),
                        'label'=> esc_html__( 'Use 24h format e.g: 13:00, 09:30 etc.', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_time_fixed', $settings['settings'], '09:00' ),
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x.'_time_method',
                        'filter_value'=>'fixed'
                    ),
                    'email_reminder_'.$x.'_time_offset' => array(
                        'hidden_setting' => true,
                        'name' => esc_html__( 'Define at what offset the reminder should be send based of the base time', 'super-forms' ),
                        'label'=> esc_html__( 'Example: 2 = Two hours after, -5 = Five hours before<br />(the base time will be the time of the form submission)', 'super-forms' ),
                        'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_time_offset', $settings['settings'], '0' ),
                        'filter'=>true,
                        'parent'=>'email_reminder_'.$x.'_time_method',
                        'filter_value'=>'offset'
                    )
                );
                $array['front_end_listing']['fields'] = array_merge($array['front_end_listing']['fields'], $reminder_settings);


                $fields = $array['confirmation_email_settings']['fields'];
                $new_fields = array();
                foreach($fields as $k => $v){
                    if($k=='confirm'){
                        unset($fields[$k]);
                        continue;
                    }
                    if( !empty($v['parent']) ) {
                        if($v['parent']=='confirm'){
                            $v['parent'] = 'email_reminder_'.$x;
                            $v['filter_value'] = 'true';
                        }else{
                            $v['parent'] = str_replace('confirm_', 'email_reminder_'.$x.'_', $v['parent']);
                        }
                    }
                    unset($fields[$k]);
                    $k = str_replace('confirm_', 'email_reminder_'.$x.'_', $k);
                    if( !empty($v['default']) ) {
                        $v['default'] = SUPER_Settings::get_value( 0, $k, $settings['settings'], $v['default'] );
                    }
                    $v['hidden_setting'] = true;
                    $new_fields[$k] = $v;
                }
                $new_fields['email_reminder_'.$x.'_attachments'] = array(
                    'hidden_setting' => true,
                    'name' => sprintf( esc_html__( 'Attachments for reminder email #%s', 'super-forms' ), $x ),
                    'desc' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
                    'default'=> SUPER_Settings::get_value( 0, 'email_reminder_'.$x.'_attachments', $settings['settings'], '' ),
                    'type' => 'file',
                    'multiple' => 'true',
                    'filter'=>true,
                    'parent'=>'email_reminder_'.$x,
                    'filter_value'=>'true'
                );
                $array['front_end_listing']['fields'] = array_merge($array['front_end_listing']['fields'], $new_fields);
                $x++;
            }

            return $array;
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
