<?php
/**
 * Callbacks to generate pages
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Pages
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Pages' ) ) :

/**
 * SUPER_Pages
 */
class SUPER_Pages {
        
	/**
	 * Handles the output for the settings page in admin
	 */
	public static function settings() {
    
        // Get all available setting fields
        $fields = SUPER_Settings::fields();
        
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );

        // Include the file that handles the view
        include_once(SUPER_PLUGIN_DIR.'/includes/admin/views/page-settings.php' );

    }
    
    
	/**
	 * Handles the output for the create form page in admin
	 */
	public static function create_form() {
    
        // Get all Forms created with Super Forms (post type: super_form)
        $args = array(
            'post_type' => 'super_form', //We want to retrieve all the Forms
            'posts_per_page' => -1 //Make sure all matching forms will be retrieved
        );
        $forms = get_posts( $args );

        // Get the values of the current form
        $values = array();
        
        /** 
         *  Make sure that we have all settings even if this form hasn't saved it yet when new settings where added by a add-on
         *
         *  @since      1.0.6
        */
        $fields = SUPER_Settings::fields( null, 1 );
        $array = array();
        foreach( $fields as $k => $v ) {
            if( !isset( $v['fields'] ) ) continue;
            foreach( $v['fields'] as $fk => $fv ) {
                if( ( isset( $fv['type'] ) ) && ( $fv['type']=='multicolor' ) ) {
                    foreach( $fv['colors'] as $ck => $cv ) {
                        if( !isset( $cv['default'] ) ) $cv['default'] = '';
                        $array[$ck] = $cv['default'];
                    }
                }else{
                    if( !isset( $fv['default'] ) ) $fv['default'] = '';
                    $array[$fk] = $fv['default'];
                }
            }
        }

        // Check if we are editing an existing Form
        if( !isset( $_GET['id'] ) ) {
            $post_ID = 0;
            $title = __( 'Form Name', 'super-forms' );
            $settings = get_option( 'super_settings' );
        }else{
            $post_ID = absint( $_GET['id'] );
            $title = get_the_title( $post_ID );          
            $settings = get_post_meta( $post_ID, '_super_form_settings', true );
        }
        if( is_array( $settings ) ) {
            $settings = array_merge( $array, $settings );
        }else{
            $settings = $array;
        }

        // Retrieve all settings with the correct default values
        $form_settings = SUPER_Settings::fields( $settings );
        
        // Get all available shortcodes
        $shortcodes = SUPER_Shortcodes::shortcodes();
        
        // Include the file that handles the view
        include_once( SUPER_PLUGIN_DIR . '/includes/admin/views/page-create-form.php' );
       
    }


    /**
     * List of all the demo forms & community forms
     */
    public static function marketplace() {
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_style( 'thickbox' );
        
        $settings = get_option( 'super_settings' );
        $url = 'http://f4d.nl/super-forms/?api=get-license-author&key=' . $settings['license'];
        $response = wp_remote_get( $url, array('timeout'=>60) );
        $author = $response['body'];
        
        if( !isset( $_GET['s'] ) ) {
            $s = '';
        }else{
            $s = sanitize_text_field($_GET['s']);
        }
        if( !isset( $_GET['tag'] ) ) {
            $tag = '';
        }else{
            $tag = sanitize_text_field($_GET['tag']);
        }
        if( !isset( $_GET['tab'] ) ) {
            $tab = 'newest';
        }else{
            $tab = sanitize_text_field($_GET['tab']);
        }
        if( !isset( $_GET['item'] ) ) {
            $id = 0;
        }else{
            $id = absint($_GET['item']);
        }
        if( !isset( $_GET['paged'] ) ) {
            $paged = 1;
        }else{
            $paged = absint($_GET['paged']);
        }
        $paged_limit = 9;

        // Get marketplace items
        $items = array();
        $args = array(
            'api' => 'get-items',
            'author' => $author,
            's' => $s,
            'tag' => $tag,
            'tab' => $tab,
            'id' => $id,
            'paged' => $paged,
            'paged_limit' => $paged_limit,
            'type' => 0
        );
        $url = 'http://f4d.nl/super-forms/';
        $response = wp_remote_post( 
            $url, 
            array(
                'timeout' => 45,
                'body' => $args
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $items = $response['body'];
            $items = json_decode($items);
        }


        // Get marketplace items
        $total = 0;
        $total_pages = 0;
        $args = array(
            'api' => 'get-items-total',
            'author' => $author,
            's' => $s,
            'tag' => $tag,
            'tab' => $tab,
            'id' => $id,
            'paged' => $paged,
            'paged_limit' => $paged_limit,
            'type' => 0
        );
        $url = 'http://f4d.nl/super-forms/';
        $response = wp_remote_post( 
            $url, 
            array(
                'timeout' => 45,
                'body' => $args
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $total = $response['body'];
            $total = json_decode($total);
            $total = $total[0]->total;
            $total_pages = ceil($total/$paged_limit);
        }

        // Get tags
        $tags = array();
        $args = array(
            'api' => 'get-tags',
            'type' => 0
        );
        $url = 'http://f4d.nl/super-forms/';
        $response = wp_remote_post( 
            $url, 
            array(
                'timeout' => 45,
                'body' => $args
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $tags = $response['body'];
            $tags = json_decode($tags);
        }
        
        $url = 'http://f4d.nl/super-forms/?api=get-marketplace-payments&author=' . $author;
        $response = wp_remote_get( $url, array('timeout'=>60) );
        $licenses = $response['body'];
        $licenses = json_decode($licenses);
        $licenses_new = array();
        if( isset( $licenses[0] ) ) {
            foreach( $licenses[0] as $k => $v ) {
                $licenses_new[] = $v;
            }
        }

        include_once( SUPER_PLUGIN_DIR . '/includes/admin/views/page-marketplace.php' );
    }


    /**
     * List of all the contact entries
     */
    public static function contact_entries() {

    }


    /**
     * Handles the output for the view contact entry page in admin
     */
    public static function contact_entry() {
        $id = $_GET['id'];
        $my_post = array(
            'ID' => $id,
            'post_status' => 'super_read',
        );
        wp_update_post($my_post);
        $date = get_the_date(false,$id);
        $time = get_the_time(false,$id);
        $ip = get_post_meta($id, '_super_contact_entry_ip', true);
        ?>
        <script>
            jQuery('.toplevel_page_super_forms').removeClass('wp-not-current-submenu').addClass('wp-menu-open wp-has-current-submenu');
            jQuery('.toplevel_page_super_forms').find('li:eq(4)').addClass('current');
        </script>
        <div class="wrap">
            <h2><?php echo get_the_title($id); ?></h2>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="postbox-container-1" class="postbox-container">
                        <div id="side-sortables" class="meta-box-sortables ui-sortable">
                            <div id="submitdiv" class="postbox ">
                                <div class="handlediv" title="">
                                    <br>
                                </div>
                                <h3 class="hndle ui-sortable-handle">
                                    <span><?php echo __('Lead Details', 'super-forms' ); ?>:</span>
                                </h3>
                                <div class="inside">
                                    <div class="submitbox" id="submitpost">
                                        <div id="minor-publishing">
                                            <div class="misc-pub-section">
                                                <span><?php echo __('Submitted', 'super-forms' ).':'; ?> <strong><?php echo $date.' @ '.$time; ?></strong></span>
                                            </div>
                                            <div class="misc-pub-section">
                                                <span><?php echo __('IP-address', 'super-forms' ).':'; ?> <strong><?php if(empty($ip)){ echo __('Unknown', 'super-forms' ); }else{ echo $ip; } ?></strong></span>
                                            </div>                                        
                                            <div class="clear"></div>
                                        </div>

                                        <div id="major-publishing-actions">
                                            <div id="delete-action">
                                                <a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo absint($id); ?>" href="#"><?php echo __('Move to Trash', 'super-forms' ); ?></a>
                                            </div>
                                            <div id="publishing-action">
                                                <span class="spinner"></span>
                                                <input name="print" type="submit" class="super-print-contact-entry button button-large" value="<?php echo __('Print', 'super-forms' ); ?>">
                                                <input name="save" type="submit" class="super-update-contact-entry button button-primary button-large" data-contact-entry="<?php echo absint($id); ?>" value="<?php echo __('Update', 'super-forms' ); ?>">
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="postbox-container-2" class="postbox-container">
                        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                            <div id="super-contact-entry-data" class="postbox ">
                                <div class="handlediv" title="">
                                    <br>
                                </div>
                                <h3 class="hndle ui-sortable-handle">
                                    <span><?php echo __('Lead Information', 'super-forms' ); ?>:</span>
                                </h3>
                                <?php
                                $data = get_post_meta($_GET['id'], '_super_contact_entry_data', true);
                                $shipping = 0;
                                $currency = '';
                                $data[] = array();
                                foreach($data as $k => $v){
                                    if((isset($v['type'])) && (($v['type']=='varchar') || ($v['type']=='var') || ($v['type']=='text') || ($v['type']=='field') || ($v['type']=='barcode') || ($v['type']=='files'))){
                                        $data['fields'][] = $v;
                                    }elseif((isset($v['type'])) && ($v['type']=='form_id')){
                                        $data['form_id'][] = $v;
                                    }
                                }
                                ?>
                                <div class="inside">
                                    <?php
                                    echo '<table>';
                                        if( ( isset($data['fields']) ) && (count($data['fields'])>0) ) {
                                            foreach( $data['fields'] as $k => $v ) {
                                                if( $v['type']=='barcode' ) {
                                                    echo '<tr><th align="right">' . $v['label'] . '</th><td>';
                                                    echo '<div class="super-barcode">';
                                                        echo '<div class="super-barcode-target"></div>';
                                                        echo '<input type="hidden" value="' . $v['value'] . '" data-barcodetype="' . $v['barcodetype'] . '" data-modulesize="' . $v['modulesize'] . '" data-quietzone="' . $v['quietzone'] . '" data-rectangular="' . $v['rectangular'] . '" data-barheight="' . $v['barheight'] . '" data-barwidth="' . $v['barwidth'] . '" />';
                                                    echo '</div>';
                                                }else if( $v['type']=='files' ) {
                                                    if( isset( $v['files'] ) ) {
                                                        foreach( $v['files'] as $fk => $fv ) {
                                                            $url = $fv['url'];
                                                            if( isset( $fv['attachment'] ) ) {
                                                                $url = wp_get_attachment_url( $fv['attachment'] );
                                                            }
                                                            if( $fk==0 ) {
                                                                echo '<tr><th align="right">' . $fv['label'] . '</th><td><span class="super-contact-entry-data-value"><a target="_blank" href="' . $url . '">' . $fv['value'] . '</a></span></td></tr>';
                                                            }else{
                                                                echo '<tr><th align="right">&nbsp;</th><td><span class="super-contact-entry-data-value"><a target="_blank" href="' . $url . '">' . $fv['value'] . '</a></span></td></tr>';
                                                            }
                                                        }
                                                    }
                                                }else if( ($v['type']=='varchar') || ($v['type']=='var') || ($v['type']=='field') ) {
                                                    if ( strpos( $v['value'], 'data:image/png;base64,') !== false ) {
                                                        echo '<tr><th align="right">' . $v['label'] . '</th><td><span class="super-contact-entry-data-value"><img src="' . $v['value'] . '" /></span></td></tr>';

                                                        // @since 2.3 - convert it to an actual image (for future reference)
                                                        /*
                                                        $img_data = $v['value'];
                                                        list($type, $img_data) = explode(';', $img_data);
                                                        list(, $img_data) = explode(',', $img_data);
                                                        $img_data = base64_decode($img_data);
                                                        $img_path = SUPER_PLUGIN_DIR . "/uploads/php/files/" . $v['name'] . "-" . $data['form_id'][0]['value'] . ".png"; 
                                                        file_put_contents($img_path, $img_data);
                                                        $img_url = SUPER_PLUGIN_FILE . "uploads/php/files/" . $v['name'] . "-" . $data['form_id'][0]['value'] . ".png";
                                                        echo '<tr><th align="right">' . $v['label'] . '</th><td><span class="super-contact-entry-data-value"><img src="' . $img_url . '" /></span></td></tr>';
                                                        */

                                                    }else{
                                                        echo '<tr>';
                                                        echo '<th align="right">' . $v['label'] . '</th>';
                                                        echo '<td>';
                                                        echo '<span class="super-contact-entry-data-value">';
                                                        echo '<input class="super-shortcode-field" type="text" name="' . $v['name'] . '" value="' . $v['value'] . '" />';
                                                        echo '</span>';
                                                        echo '</td>';
                                                        echo '</tr>';
                                                    }
                                                }else if( $v['type']=='text' ) {
                                                    echo '<tr>';
                                                    echo '<th align="right">' . $v['label'] . '</th>';
                                                    echo '<td>';
                                                    echo '<span class="super-contact-entry-data-value">';
                                                    echo '<textarea class="super-shortcode-field" name="' . $v['name'] . '">' . $v['value'] . '</textarea>';
                                                    echo '</span>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                        }
                                        echo '<tr><th align="right">&nbsp;</th><td><span class="super-contact-entry-data-value">&nbsp;</span></td></tr>';
                                        echo '<tr><th align="right">' . __( 'Based on Form', 'super-forms' ) . ':</th><td><span class="super-contact-entry-data-value">';
                                        echo '<input type="hidden" class="super-shortcode-field" name="form_id" value="' . absint($data['form_id'][0]['value']) . '" />';
                                        echo '<a href="admin.php?page=super_create_form&id=' . $data['form_id'][0]['value'] . '">' . get_the_title( $data['form_id'][0]['value'] ) . '</a>';
                                        echo '</span></td></tr>';

                                        echo apply_filters( 'super_after_contact_entry_data_filter', '', array( 'entry_id'=>$_GET['id'], 'data'=>$data ) );

                                    echo '</table>';
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div id="advanced-sortables" class="meta-box-sortables ui-sortable"></div>
                    </div>
                </div>
                <!-- /post-body -->
                <br class="clear">
            </div>
        <?php
    }     
    
    
}
endif;