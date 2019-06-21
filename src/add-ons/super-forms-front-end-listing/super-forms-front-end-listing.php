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
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
                // Actions since 1.0.0
                add_action( 'init', array( $this, 'update_plugin' ) );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );
            }
            if ( $this->is_request( 'ajax' ) ) {
            }

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
                    'filter' => array(
                        'field_type' => 'text',
                        'placeholder' => 'Search Order #',
                    )
                ),
                'entry_status' => array(
                    'name' => 'Status',
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
                'email' => array(
                    'name' => 'E-mail',
                    'filter' => array(
                        'field_type' => 'text',
                        'placeholder' => 'Filter',
                    )
                ),
            );
            $columns = array_merge($columns, $custom_columns);
            // Always put default date column at the end
            $columns['post_date'] = array(
                'name' => 'Date Created',
                'filter' => array(
                    'field_type' => 'datepicker',
                    'placeholder' => '10/07/2019'
                )
            );
            ?>
            <div class="super-fel">
                <div class="super-header">
                    <div class="super-select-all">
                        Select All
                    </div>
                    <div class="super-settings">
                        Settings
                    </div>
                    <div class="super-csv-export">
                        CSV Export
                    </div>
                </div>
                <div class="super-columns">
                    <!-- // # predefined columns are:
                    // - Order # (contact entry post_title)
                    // - Status (contact entry status)
                    // - Date Created (contact entry data)
                    // - Action (edit, quick view, delete) -->

                    <?php
                    foreach( $columns as $k => $v ) {
                        // If a max width was defined use it on the col-wrap
                        $styles = '';
                        if( !empty( $v['width'] ) ) {
                            $styles = 'width:' . $v['width'];
                        }
                        if( !empty( $styles ) ) {
                            $styles = ' style="' . $styles . '"';
                        }
                        ?>
                        <div class="super-col-wrap"<?php echo $styles; ?>>
                            <span class="super-col-name"><?php echo $v['name']; ?></span>
                            <div class="super-col-sort">
                                <span class="super-sort-down">↓</span>
                                <span class="super-sort-up">↑</span>
                            </div>
                            <?php
                            if( isset($v['filter']) && is_array($v['filter']) ) {
                                ?>
                                <div class="super-col-filter">
                                    <?php
                                    if($v['filter']['field_type']=='text'){
                                        echo '<input type="text" placeholder="' . $v['filter']['placeholder'] . '" />';
                                    }
                                    if($v['filter']['field_type']=='datepicker'){
                                        echo '<input type="date" placeholder="' . $v['filter']['placeholder'] . '" />';
                                    }
                                    if($v['filter']['field_type']=='dropdown'){
                                        echo '<select placeholder="' . $v['filter']['placeholder'] . '" >';
                                            foreach($v['filter']['items'] as $value => $name){
                                                echo '<option value="' . $value . '">' . $name . '</option>';
                                            }
                                        echo '</select>';
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?> 
                </div>

                <div class="super-entries">
                    <?php
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
                        ?>
                        <div class="super-entry">
                            <div class="super-col super-check"></div>
                            <?php
                            foreach( $columns as $ck => $cv ) {
                                // If a max width was defined use it on the col-wrap
                                $styles = '';
                                if( !empty( $cv['width'] ) ) {
                                    $styles = 'width:' . $cv['width'];
                                }
                                if( !empty( $styles ) ) {
                                    $styles = ' style="' . $styles . '"';
                                }
                                ?>
                                <div class="super-col super-<?php echo $ck; ?>"<?php echo $styles; ?>>
                                    <?php 
                                    if($ck=='post_title'){
                                        echo '<a href="#">' . $entry->post_title . '</a>';
                                    }elseif($ck=='entry_status'){
                                        //echo '<span class="super-status proposal-send">Proposal send</span>';
                                        if( (isset($statuses[$entry->status])) && ($entry->status!='') ) {
                                            echo '<span class="super-entry-status super-entry-status-' . $entry->status . '" style="color:' . $statuses[$entry->status]['color'] . ';background-color:' . $statuses[$entry->status]['bg_color'] . '">' . $statuses[$entry->status]['name'] . '</span>';
                                        }else{
                                            $post_status = get_post_status($entry->ID);
                                            if($post_status=='super_read'){
                                                echo '<span class="super-entry-status super-entry-status-' . $post_status . '" style="background-color:#d6d6d6;">' . esc_html__( 'Read', 'super-forms' ) . '</span>';
                                            }else{
                                                echo '<span class="super-entry-status super-entry-status-' . $post_status . '">' . esc_html__( 'Unread', 'super-forms' ) . '</span>';
                                            }
                                        }
                                    }elseif($ck=='post_date'){
                                        echo date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) );
                                        //echo get_the_date($entry->post_date);
                                    }else{
                                        // Check if this data key exists
                                        if(isset($data[$ck])){
                                            // Check if it has a value, if so print it
                                            if(isset($data[$ck]['value'])){
                                                echo $data[$ck]['value'];
                                            }else{
                                                // If not then it must be a special field, for instance file uploads
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="super-col super-actions">
                                <!-- <a href="http://metakraftlabs.net/woo/ronny-v2/?contact_entry_id=<?php echo $entry->ID; ?>" class="super-edit">Edit</a> -->
                                <a target="_blank" href="http://metakraftlabs.net/woo/ronny-v2/?contact_entry_id=11338" class="super-edit">Edit</a>
                                <span class="super-view">View</span>
                                <span class="super-delete">Delete</span>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div class="super-pagination">

                    <?php
                    $url = strtok($_SERVER["REQUEST_URI"], '?');
                    ?>
                    <select class="super-limit">
                        <option>10</option>
                        <option selected="selected">25</option>
                        <option>50</option>
                        <option>100</option>
                        <option>300</option>
                    </select>

                    <span class="super-results"><?php echo count($entries); ?> results</span>

                    <span class="super-next">></span>
                    <div class="super-nav">
                        <a href="<?php echo $url; ?>?page=1" class="super-page super-active">1</a>
                        <a href="<?php echo $url; ?>?page=2" class="super-page">2</a>
                        <a href="<?php echo $url; ?>?page=3" class="super-page">3</a>
                    </div>
                    <span class="super-prev"><</span>


                    <select class="super-switcher">
                        <option>1</option>
                        <option>2</option>
                    </select>

                    <span class="super-pages">Pages</span>


                </div>
            </div>
            <?php
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
