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
            $title = __( 'Form Name', 'super' );
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
            <h2><?php echo __('Contact entry','super'); ?> #<?php echo $id; ?></h2>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="postbox-container-1" class="postbox-container">
                        <div id="side-sortables" class="meta-box-sortables ui-sortable">
                            <div id="submitdiv" class="postbox ">
                                <div class="handlediv" title="">
                                    <br>
                                </div>
                                <h3 class="hndle ui-sortable-handle">
                                    <span><?php echo __('Lead Details','super'); ?>:</span>
                                </h3>
                                <div class="inside">
                                    <div class="submitbox" id="submitpost">
                                        <div id="minor-publishing">
                                            <div class="misc-pub-section">
                                                <span><?php echo __('Submitted','super').':'; ?> <strong><?php echo $date.' @ '.$time; ?></strong></span>
                                            </div>
                                            <div class="misc-pub-section">
                                                <span><?php echo __('IP-address','super').':'; ?> <strong><?php if(empty($ip)){ echo __('Unknown','super'); }else{ echo $ip; } ?></strong></span>
                                            </div>                                        
                                            <div class="clear"></div>
                                        </div>

                                        <div id="major-publishing-actions">
                                            <div id="delete-action">
                                                <a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo $id; ?>" href="#"><?php echo __('Move to Trash','super'); ?></a>
                                            </div>
                                            <div id="publishing-action">
                                                <span class="spinner"></span>
                                                <input name="print" type="submit" class="super-print-contact-entry button button-primary button-large" accesskey="p" value="<?php echo __('Print','super'); ?>">
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
                                    <span><?php echo __('Lead Information','super'); ?>:</span>
                                </h3>
                                <?php
                                $data = get_post_meta($_GET['id'], '_super_contact_entry_data', true);
                                $shipping = 0;
                                $currency = '';
                                $data[] = array();
                                foreach($data as $k => $v){
                                    if((isset($v['type'])) && ($v['type']=='product')){
                                        $data['products'][] = $v;
                                    }elseif((isset($v['type'])) && ($v['type']=='total')){
                                        $data['totals'][] = $v;
                                    }elseif((isset($v['type'])) && ($v['type']=='shipping')){
                                        $data['shippings'][] = $v;
                                    }elseif((isset($v['type'])) && ($v['type']=='discount')){
                                        $data['discounts'][] = $v;
                                    }elseif((isset($v['type'])) && (($v['type']=='field') || ($v['type']=='barcode') || ($v['type']=='files'))){
                                        $data['fields'][] = $v;
                                    }elseif((isset($v['type'])) && ($v['type']=='form_id')){
                                        $data['form_id'][] = $v;
                                    }
                                }
                                ?>
                                <div class="inside">
                                    <?php
                                    if((isset($data['products'])) && (count($data['products'])>0)){
                                        $counter = 0;
                                        $subtotal = 0;
                                        foreach($data['products'] as $k => $v){
                                            if($counter==0){
                                                ?>
                                                <table class="super-product-listing" cellspacing="0">
                                                    <thead>
                                                        <tr>
                                                            <th><?php _e('Quantity','super'); ?></th>
                                                            <th><?php _e('Product','super'); ?></th>
                                                            <th><?php _e('Apiece','super'); ?></th>
                                                            <th><?php _e('Price','super'); ?></th>
                                                        </tr>
                                                    </thead>
                                                <tbody>
                                                <?php
                                            }$counter++;
                                            ?>
                                            <tr>
                                                <td class="super-product-quantity"><?php echo $v['value']; ?> x</td>
                                                <td class="super-product-name"><?php echo $v['label']; ?></td>
                                                <td class="super-product-price"><?php echo $v['currency'].number_format((float)($v['price']), 2, '.', ''); ?></td>
                                                <td class="super-product-total"><?php echo $v['currency'].(number_format((float)($v['price']*$v['value']), 2, '.', '')); ?></td>
                                            </tr>
                                            <?php
                                            $subtotal = $subtotal + ($v['price']*$v['value']);
                                            $currency = $v['currency'];
                                        }
                                        ?>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2">&nbsp;</td>
                                                <td class="super-product-subtotal-label textright">Subtotal</td>
                                                <td class="super-product-subtotal"><?php echo $currency.(number_format((float)$subtotal, 2, '.', '')); ?></td>
                                            </tr>
                                            <?php
                                            if((isset($data['discounts'])) && (count($data['discounts'])>0)){
                                                foreach($data['discounts'] as $k => $v){
                                                    ?>
                                                    <tr>
                                                        <td colspan="2">&nbsp;</td>
                                                        <td class="super-product-discount-label textright">Discount</td>
                                                        <td class="super-product-discount"><?php echo $v['value'].'%'; ?></td>
                                                    </tr>
                                                    <?php
                                                    $discount = ($subtotal/100) * $v['value'];
                                                    $subtotal = $subtotal - $discount;
                                                }
                                            }
                                            if((isset($data['shippings'])) && (count($data['shippings'])>0)){
                                                foreach($data['shippings'] as $k => $v){
                                                    ?>
                                                    <tr>
                                                        <td colspan="2">&nbsp;</td>
                                                        <td class="super-product-shipping-label textright">Shipping</td>
                                                        <td class="super-product-shipping"><?php echo $currency.(number_format((float)$v['value'], 2, '.', '')); ?></td>
                                                    </tr>
                                                    <?php
                                                    $shipping = $shipping + $v['value'];
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td colspan="2">&nbsp;</td>
                                                <td class="super-product-total-label textright">Total</td>
                                                <td class="super-product-total"><?php echo $currency.(number_format((float)($shipping+$subtotal), 2, '.', '')); ?></td>
                                            </tr>                                        
                                        </tfoot>
                                        </table>
                                        <?php
                                    }
                                    echo '<table>';
                                        if((isset($data['fields'])) && (count($data['fields'])>0)){
                                            foreach( $data['fields'] as $k => $v ){
                                                if($v['type']=='barcode'){
                                                    echo '<tr><th align="right">'.$v['label'].':</th><td>';
                                                    echo '<div class="super-barcode">';
                                                        echo '<div class="super-barcode-target"></div>';
                                                        echo '<input type="hidden" value="'.$v['value'].'" data-barcodetype="'.$v['barcodetype'].'" data-modulesize="'.$v['modulesize'].'" data-quietzone="'.$v['quietzone'].'" data-rectangular="'.$v['rectangular'].'" data-barheight="'.$v['barheight'].'" data-barwidth="'.$v['barwidth'].'" />';
                                                    echo '</div>';
                                                }else if($v['type']=='files'){
                                                    if(isset($v['files'])){
                                                        foreach($v['files'] as $fk => $fv){
                                                            if($fk==0){
                                                                echo '<tr><th align="right">'.$fv['label'].':</th><td><span class="super-contact-entry-data-value"><a target="_blank" href="'.$fv['url'].'">'.$fv['value'].'</a></span></td></tr>';
                                                            }else{
                                                                echo '<tr><th align="right">&nbsp;</th><td><span class="super-contact-entry-data-value"><a target="_blank" href="'.$fv['url'].'">'.$fv['value'].'</a></span></td></tr>';
                                                            }
                                                        }
                                                    }
                                                }else if($v['type']=='field'){
                                                    if (strpos($v['value'], 'data:image/png;base64,') !== false) {
                                                        echo '<tr><th align="right">'.$v['label'].':</th><td><span class="super-contact-entry-data-value"><img src="' . $v['value'] . '" /></span></td></tr>';
                                                    }else{
                                                        echo '<tr><th align="right">'.$v['label'].':</th><td><span class="super-contact-entry-data-value">'.$v['value'].'</span></td></tr>';
                                                    }
                                                }
                                            }
                                        }
                                        echo '<tr><th align="right">&nbsp;</th><td><span class="super-contact-entry-data-value">&nbsp;</span></td></tr>';
                                        echo '<tr><th align="right">'.__('Based on Form','super').':</th><td><span class="super-contact-entry-data-value">';
                                        echo '<a href="admin.php?page=super_create_form&id='.$data['form_id'][0]['value'].'">'.get_the_title($data['form_id'][0]['value']).'</a>';
                                        echo '</span></td></tr>';
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