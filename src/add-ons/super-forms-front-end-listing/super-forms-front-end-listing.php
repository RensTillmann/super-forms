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
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 110, 2 );
            }
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
                            <div class="super-checkbox<?php echo ($list['date_range']==true ? ' super-active' : ''); ?>" data-name="date_range">
                                <span onclick="SUPER.frontEndListing.checkbox(event, this)">Only display entries within the following date range:</span>
                                <div class="super-sub-settings">
                                    <div class="super-text">
                                        <span>From: <i>(or leave blank for no minimum date)</i></span>
                                        <input type="date" name="date_range_from" value="<?php echo sanitize_text_field($list['date_range_from']); ?>" />
                                    </div>
                                    <div class="super-text">
                                        <span>Till: <i>(or leave blank for no maximum date)</i></span>
                                        <input type="date" name="date_range_till" value="<?php echo sanitize_text_field($list['date_range_till']); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="super-checkbox<?php echo ($list['show_title']==true ? ' super-active' : ''); ?>" data-name="show_title">
                                <span<?php echo ($list['show_title']==true ? ' class="super-active"' : ''); ?> onclick="SUPER.frontEndListing.checkbox(event, this)">
                                    <span>Show "Title" column</span>
                                    <input class="super-tooltip" name="title_name" value="<?php echo $list['title_name']; ?>" type="text" title="Column name" data-title="Column name" />
                                    <input class="super-tooltip" name="title_placeholder" value="<?php echo $list['title_placeholder']; ?>" type="text" title="Filter placeholder" data-title="Filter placeholder" />
                                    <input class="super-tooltip" name="title_width" value="<?php echo absint($list['title_width']); ?>" type="number" title="Column width (px)" data-title="Column width (px)" />
                                    <input class="super-tooltip" name="title_position" value="<?php echo absint($list['title_position']); ?>" type="number" title="Column position" data-title="Column position" />
                                </span>
                            </div>
                            <div class="super-checkbox<?php echo ($list['show_status']==true ? ' super-active' : ''); ?>" data-name="show_status">
                                <span<?php echo ($list['show_status']==true ? ' class="super-active"' : ''); ?> onclick="SUPER.frontEndListing.checkbox(event, this)">
                                    <span>Show "Status" column</span>
                                    <input class="super-tooltip" name="status_name" value="<?php echo $list['status_name']; ?>" type="text" title="Column name" data-title="Column name" />
                                    <input class="super-tooltip" name="status_placeholder" value="<?php echo $list['status_placeholder']; ?>" type="text" title="Filter placeholder" data-title="Filter placeholder" />
                                    <input class="super-tooltip" name="status_width" value="<?php echo absint($list['status_width']); ?>" type="number" title="Column width (px)" data-title="Column width (px)" />
                                    <input class="super-tooltip" name="status_position" value="<?php echo absint($list['status_position']); ?>" type="number" title="Column position" data-title="Column position" />
                                </span>
                            </div>
                            <div class="super-checkbox<?php echo ($list['show_date']==true ? ' super-active' : ''); ?>" data-name="show_date">
                                <span<?php echo ($list['show_date']==true ? ' class="super-active"' : ''); ?> onclick="SUPER.frontEndListing.checkbox(event, this)">
                                    <span>Show "Date created" column</span>
                                    <input class="super-tooltip" name="date_name" value="<?php echo $list['date_name']; ?>" type="text" title="Column name" data-title="Column name" />
                                    <input class="super-tooltip" name="date_placeholder" value="<?php echo $list['date_placeholder']; ?>" type="text" title="Filter placeholder" data-title="Filter placeholder" />
                                    <input class="super-tooltip" name="date_width" value="<?php echo absint($list['date_width']); ?>" type="number" title="Column width (px)" data-title="Column width (px)" />
                                    <input class="super-tooltip" name="date_position" value="<?php echo absint($list['date_position']); ?>" type="number" title="Column position" data-title="Column position" />
                                </span>
                            </div>
                            <div class="super-checkbox<?php echo ($list['custom_columns']==true ? ' super-active' : ''); ?>" data-name="custom_columns">
                                <span<?php echo ($list['custom_columns']==true ? ' class="super-active"' : ''); ?>  onclick="SUPER.frontEndListing.checkbox(event, this)">Show the following "Custom" columns</span>
                                <div class="super-sub-settings">
                                    <ul>
                                        <?php
                                        $columns = $list['columns'];
                                        foreach( $columns as $ck => $cv ) {
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
                                                <span class="add-column" onclick="SUPER.frontEndListing.addColumn(this)"></span>
                                                <span class="delete-column" onclick="SUPER.frontEndListing.deleteColumn(this)"></span>
                                            </li>
                                            <?php
                                        }
                                        ?>

                                    </ul>
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
            if( empty($list['display_based_on']) ) $list['display_based_on'] = 'this_form';
            if( empty($list['form_ids']) ) $list['form_ids'] = '';
            if( empty($list['date_range']) ) $list['date_range'] = false;
            if( empty($list['date_range_from']) ) $list['date_range_from'] = '';
            if( empty($list['date_range_till']) ) $list['date_range_till'] = '';
            if( empty($list['show_title']) ) $list['show_title'] = true;
            if( empty($list['title_name']) ) $list['title_name'] = __( 'Title', 'super-forms' );
            if( empty($list['title_placeholder']) ) $list['title_placeholder'] = __( 'Filter by title', 'super-forms' );
            if( empty($list['title_position']) ) $list['title_position'] = 1;
            if( empty($list['title_width']) ) $list['title_width'] = 150;
            if( empty($list['show_status']) ) $list['show_status'] = true;
            if( empty($list['status_name']) ) $list['status_name'] = __( 'Status', 'super-forms' );
            if( empty($list['status_placeholder']) ) $list['status_placeholder'] = __( '- choose status -', 'super-forms' );
            if( empty($list['status_position']) ) $list['status_position'] = 2;
            if( empty($list['status_width']) ) $list['status_width'] = 150;
            if( empty($list['show_date']) ) $list['show_date'] = true;
            if( empty($list['date_name']) ) $list['date_name'] = __( 'Date created', 'super-forms' );
            if( empty($list['date_placeholder']) ) $list['date_placeholder'] = __( 'Filter by date', 'super-forms' );
            if( empty($list['date_position']) ) $list['date_position'] = 3;
            if( empty($list['date_width']) ) $list['date_width'] = 150;
            if( empty($list['custom_columns']) ) $list['custom_columns'] = false;
            if( empty($list['columns']) ) $list['columns'] = array(
                array(
                    'name' => 'E-mail',
                    'field_name' => 'email',
                    'width' => 150
                )
            );
            if( empty($list['pagination']) ) $list['pagination'] = 'page';
            if( empty($list['limit']) ) $list['limit'] = 25;
            return $list;
        }

        // Return data for script handles.
        public static function register_shortcodes(){
            add_shortcode( 'super_listing', array( $this, 'super_listing_func' ) );
        }

        // The form shortcode that will generate the list/table with all Contact Entries
        public static function super_listing_func( $atts ) {
            wp_enqueue_style( 'super-front-end-listing', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/styles.css', array(), $this->version );

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
            if( $list['show_title']==true ) {
                $columns['post_title'] = array(
                    'position' => absint($list['title_position']),
                    'name' => $list['title_name'],
                    'width' => absint($list['title_width']),
                    'filter' => array(
                        'field_type' => 'text',
                        'placeholder' => $list['title_placeholder']
                    )
                );
            }
            // Check if "Status" column is enabled
            if( $list['show_status']==true ) {
                $columns['entry_status'] = array(
                    'position' => absint($list['status_position']),
                    'name' => $list['status_name'],
                    'width' => absint($list['status_width']),
                    'filter' => array(
                        'field_type' => 'dropdown',
                        'placeholder' => $list['status_placeholder'],
                        'items' => array(
                            'proposal_send' => 'Proposal send',
                            'cancelled' => 'Cancelled',
                        ),
                    )
                );
            }
            // Check if "Date" column is enabled
            if( $list['show_date']==true ) {
                // Always put default date column at the end
                $columns['post_date'] = array(
                    'position' => absint($list['date_position']),
                    'name' => $list['date_name'],
                    'width' => absint($list['date_width']),
                    'filter' => array(
                        'field_type' => 'datepicker',
                        'placeholder' => $list['date_placeholder']
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
                        foreach( $columns as $k => $v ) {
                            // If a max width was defined use it on the col-wrap
                            $styles = '';
                            if( !empty( $v['width'] ) ) {
                                $styles = 'width:' . $v['width'] . 'px;';
                            }
                            if( !empty( $styles ) ) {
                                $styles = ' style="' . $styles . '"';
                            }

                            $result .= '<div class="super-col-wrap"' . $styles . '>';
                                $result .= '<span class="super-col-name">' . $v['name'] . '</span>';
                                if( isset($v['filter']) && is_array($v['filter']) ) {
                                    $result .= '<div class="super-col-sort">';
                                        $result .= '<span class="super-sort-down">↓</span>';
                                        $result .= '<span class="super-sort-up">↑</span>';
                                    $result .= '</div>';
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
                                }else{
                                    // It's a custom column do global search
                                    $result .= '<div class="super-col-filter">';
                                        $result .= '<input type="text" placeholder="' . esc_attr( __( 'Search...', 'super-forms' ) ) . '" />';
                                    $result .= '</div>';
                                }
                            $result .= '</div>';
                        }
                    $result .= '</div>';

                    $result .= '<div class="super-entries">';
                        $result .= '<div class="super-scroll"></div>';
                        global $wpdb;

                        $limit = absint($list['limit']);
                        $offset = 0; // If page is 1, offset is 0, If page is 2 offset is 1 etc.
                        $paged = (get_query_var('page')) ? get_query_var('page') : 1;
                        $offset = $limit*($paged-1);

                        $where = '';
                        if( $list['display_based_on']=='this_form' ) {
                            $where .= " AND post_parent = '" . absint($form_id) . "'";
                        }

                        $query = "
                        SELECT ID, post_title, post_date, 
                        (SELECT meta_value FROM $wpdb->postmeta AS meta WHERE meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_data') AS data,
                        (SELECT meta_value FROM $wpdb->postmeta AS meta WHERE meta.post_id = post.ID AND meta.meta_key = '_super_contact_entry_status') AS status
                        FROM $wpdb->posts AS post 
                        WHERE post_type = 'super_contact_entry'$where
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
                                        $styles = 'width:' . $cv['width'] . 'px;';
                                    }
                                    if( !empty( $styles ) ) {
                                        $styles = ' style="' . $styles . '"';
                                    }

                                    $column_key = ( isset($cv['field_name']) ? $cv['field_name'] : $ck );

                                    $result .= '<div class="super-col super-' . $column_key . '"' . $styles . '>';
                                        if($column_key=='post_title'){
                                            $result .= '<a href="#">' . $entry->post_title . '</a>';
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
                                                $result .= 'empty';
                                            }
                                        }
                                    $result .= '</div>';
                                }
                                $result .= '<div class="super-col super-actions">';
                                    $actions = '<a target="_blank" href="http://cpq360.com/quote-builder/?contact_entry_id=' . $entry->ID . '" class="super-edit">Edit</a>';
                                    $actions .= '<span class="super-view">View</span>';
                                    $actions .= '<span class="super-delete">Delete</span>';
                                    $result .= apply_filters( 'super_front_end_listing_actions_filter', $actions, $entry );
                               $result .= ' </div>';
                            $result .= '</div>';
                        }
                    $result .= '</div>';
                $result .= '</div>';

                $result .= '<div class="super-pagination">';
                    
                    $result .= '<select class="super-limit">';
                        $result .= '<option ' . ($limit==10 ? 'selected="selected" ' : '') . 'value="10">10</option>';
                        $result .= '<option ' . ($limit==25 ? 'selected="selected" ' : '') . 'value="25">25</option>';
                        $result .= '<option ' . ($limit==50 ? 'selected="selected" ' : '') . 'value="50">50</option>';
                        $result .= '<option ' . ($limit==100 ? 'selected="selected" ' : '') . 'value="100">100</option>';
                        $result .= '<option ' . ($limit==300 ? 'selected="selected" ' : '') . 'value="300">300</option>';
                    $result .= '</select>';

                    $result .= '<span class="super-results">' . count($entries) . ' results</span>';

                    $result .= '<span class="super-next">></span>';
                    $result .= '<div class="super-nav">';
                        $url = strtok($_SERVER["REQUEST_URI"], '?');
                        $result .= '<a href="' . $url . '?page=1" class="super-page super-active">1</a>';
                        $result .= '<a href="' . $url . '?page=2" class="super-page">2</a>';
                        $result .= '<a href="' . $url . '?page=3" class="super-page">3</a>';
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
