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
     * Handles the output for the Add-ons page in admin
     */
    public static function addons() {
        // Include the file that handles the view
        include_once( SUPER_PLUGIN_DIR . '/includes/class-ajax.php' );
        $userEmail = SUPER_Common::get_user_email();
        $custom_args = array(
            'body' => array(
                'action' => 'super_api_subscribe_addon',
                'plugin_version' => SUPER_VERSION,
                'api_endpoint' => SUPER_API_ENDPOINT,
                'api_version' => SUPER_API_VERSION,
                'home_url' => get_option('home'),
                'site_url' => site_url(),
                'protocol' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http",
                'email' => $userEmail,
                'addons_activated' => array('super-forms'=>SUPER_VERSION),
                'addons_url' => admin_url( 'admin.php?page=super_addons' ),
                'ajax_url' => admin_url( 'admin-ajax.php', 'relative' ),
                'reset_password' => (isset($_GET['reset_password']) ? $_GET['reset_password'] : '')
            )
        );
        echo SUPER_Ajax::api_do_request('addons/list', $custom_args, 'return');
    }


    /**
     * Handles the output for the settings page in admin
     */
    public static function settings() {
        $g = SUPER_Common::get_global_settings();

        // Get all available setting fields
        $fields = SUPER_Settings::fields();
        
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION );

        // Include the file that handles the view
        include_once(SUPER_PLUGIN_DIR.'/includes/admin/views/page-settings.php' );

    }
    
    /**
     * Handle TAB outputs on builder tab
     */
    public static function builder_tab($atts) {
        extract($atts);
        $elements = get_post_meta( $form_id, '_super_elements', true );
        $form_html = SUPER_Common::generate_backend_elements($form_id, $shortcodes, $elements);
        // Display translation mode message to the user if translation mode is enabled
        echo '<div class="super-translation-mode-notice">';
            echo '<p>' . esc_html__( 'Currently in translation mode for language', 'super-forms' ) . ': <span class="super-i18n-language"></span></p>';
        echo '</div>';
        ?>
        <div class="super-preview-elements super-form <?php echo $theme_style; ?> super-dropable<?php echo (!empty($settings['theme_rtl']) ? ' super-rtl' : ''); ?><?php echo (!empty($settings['enable_adaptive_placeholders']) ? ' super-adaptive' : ''); ?> super-form-<?php echo $form_id; ?>"><?php echo $form_html; ?></div>
        <style type="text/css"><?php echo apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$form_id, 'settings'=>$settings ) ) . $settings['theme_custom_css']; ?></style>
        <?php
    }
    
    /**
     * Handle TAB outputs for secrets tab
     * Secrets can be used to store and retrieve data on server side
     * This can be useful if you do not want to place sensitive data in the form source code
     * A good usecase would be if you are conditionally sending to a specific email address
     * In that case you might not want to add the email address in the HTML source code (to avoid SPAM)
     * You can define a secret for each email address, which would be linked to a specific tag name e.g: {$secret_XXXXX}
     * This tag can then be used anywhere in your form settings
     * (secrets will only work upon form submission, it will not work on page load, this is the whole purpose of secrets really)
     * You can define local secrets and global secrets, local secrets can only be used on the current form, while global secrets can be used on any form you create.
     * This includes forms you created in the past, and forms you will create in the future.
     */
    public static function secrets_tab($atts) {
        extract($atts);
        echo '<div class="super-secrets">';
            echo '<div class="sfui-notice sfui-yellow">';
                echo '<strong>' . esc_html__('Not sure what secrets are?', 'super-forms') . ':</strong> <a href="https://docs.super-forms.com/features/advanced/secrets" target="_blank">' . esc_html__( 'Read the documentation here!', 'super-forms' ) . '</a>';
            echo '</div>';
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>' . esc_html__('Tip', 'super-forms') . ':</strong> ';
                echo esc_html__( 'It is best practice to use local secrets unless you have a specific use case that requires the use for global secrets.', 'super-forms');
            echo '</div>';
            echo '<div class="super-local-secrets">';
                echo '<h3>' . esc_html__( 'Local secrets', 'super-forms' ) . '</h3>';
                echo '<div class="sfui-notice sfui-desc">';
                    echo '<strong>' . esc_html__('Info', 'super-forms') . ':</strong> ';
                    echo esc_html__( 'Local secrets can only be used in this form only', 'super-forms' );
                echo '</div>';
                echo '<ul>';
                    if( (is_array($secrets['local'])) && (!empty($secrets['local'])) ) {
                        foreach($secrets['local'] as $k => $v){
                            echo '<li>';
                                echo '<span class="super-secret-tag">{@' . $v['name'] . '}</span>';
                                echo '<input value="' . $v['name'] . '" type="text" name="secretName" placeholder="' . esc_html__('Local secret name', 'super-forms') . '" />';
                                echo '<input value="' . $v['value'] . '" type="text" name="secretValue" placeholder="' . esc_html__('Local secret value', 'super-forms') . '" />';
                                echo '<span class="super-delete-secret sfui-btn sfui-icon sfui-red">';
                                    echo '<i class="fas fa-trash"></i>';
                                echo '</span>';
                                echo '<span class="super-add-secret sfui-btn sfui-icon sfui-grey">';
                                    echo '<i class="fas fa-plus"></i>';
                                echo '</span>';
                            echo '</li>';
                        }
                    }else{
                        echo '<li>';
                            echo '<span class="super-secret-tag"></span>';
                            echo '<input type="text" name="secretName" placeholder="' . esc_html__('Local secret name', 'super-forms') . '" />';
                            echo '<input type="text" name="secretValue" placeholder="' . esc_html__('Local secret value', 'super-forms') . '" />';
                            echo '<span class="super-delete-secret sfui-btn sfui-icon sfui-red">';
                                echo '<i class="fas fa-trash"></i>';
                            echo '</span>';
                            echo '<span class="super-add-secret sfui-btn sfui-icon sfui-grey">';
                                echo '<i class="fas fa-plus"></i>';
                            echo '</span>';
                        echo '</li>';
                    }
                echo '</ul>';
            echo '</div>';
            echo '<div class="super-global-secrets">';
                echo '<h3>' . esc_html__( 'Global secrets', 'super-forms' ) . '</h3>';
                echo '<div class="sfui-notice sfui-desc">';
                    echo '<strong>' . esc_html__('Info', 'super-forms') . ':</strong> ';
                    echo esc_html__( 'Global secrets can be used in all your forms. Make sure to not alter any existing secrets or it could possibly impact previously created forms that are using global secrets.', 'super-forms' );
                echo '</div>';
                echo '<ul>';
                    if( (is_array($secrets['global'])) && (!empty($secrets['global'])) ) {
                        foreach($secrets['global'] as $k => $v){
                            echo '<li>';
                                echo '<span class="super-secret-tag">{@' . $v['name'] . '}</span>';
                                echo '<input value="' . $v['name'] . '" disabled type="text" name="secretName" placeholder="' . esc_html__('Global secret name', 'super-forms') . '" />';
                                echo '<input value="' . $v['value'] . '" disabled type="text" name="secretValue" placeholder="' . esc_html__('Global secret value', 'super-forms') . '" />';
                                echo '<span class="super-delete-secret sfui-btn sfui-icon sfui-red">';
                                    echo '<i class="fas fa-trash"></i>';
                                echo '</span>';
                                echo '<span class="super-edit-secret sfui-btn sfui-icon sfui-green">';
                                    echo '<i class="fas fa-pencil-alt"></i>';
                                echo '</span>';
                                echo '<span class="super-add-secret sfui-btn sfui-icon sfui-grey">';
                                    echo '<i class="fas fa-plus"></i>';
                                echo '</span>';
                            echo '</li>';
                        }
                    }else{
                        echo '<li>';
                            echo '<span class="super-secret-tag"></span>';
                            echo '<input disabled type="text" name="secretName" placeholder="' . esc_html__('Global secret name', 'super-forms') . '" />';
                            echo '<input disabled type="text" name="secretValue" placeholder="' . esc_html__('Global secret value', 'super-forms') . '" />';
                            echo '<span class="super-delete-secret sfui-btn sfui-icon sfui-red">';
                                echo '<i class="fas fa-trash"></i>';
                            echo '</span>';
                            echo '<span class="super-edit-secret sfui-btn sfui-icon sfui-green">';
                                echo '<i class="fas fa-pencil-alt"></i>';
                            echo '</span>';
                            echo '<span class="super-add-secret sfui-btn sfui-icon sfui-grey">';
                                echo '<i class="fas fa-plus"></i>';
                            echo '</span>';
                        echo '</li>';
                    }
                echo '</ul>';
            echo '</div>';
        echo '</div>';
    }

    /**
     * Handle TAB outputs for code tab (edit raw form code)
     */
    public static function code_tab($atts) {
        extract($atts);
        echo '<div class="super-raw-code-form-elements">';
            echo '<p class="sfui-notice sfui-yellow">';
                echo sprintf( esc_html__( '%sForm elements:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';

        echo '<div class="super-raw-code-form-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sForm settings:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '<label class="super-retain-underlying-global-values"><input checked="checked" type="checkbox" name="retain_underlying_global_values" /><span>' . esc_html__( 'Retain underlying global value (recommended when exporting to other sites)', 'super-forms' ) . '</span></label>';
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';
        
        echo '<div class="super-raw-code-trigger-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sTrigger settings:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';

        echo '<div class="super-raw-code-translation-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sTranslation settings:%s (this only includes the translation settings, not the actual strings, this is stored in the "Form elements" code)', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
            echo '<span class="super-update-raw-code sfui-btn sfui-icon sfui-green">';
                echo '<i class="fas fa-save"></i>';
                echo '<span>' . esc_html__( 'Update all', 'super-forms' ) . '<span>';
            echo '</span>';
        echo '</div>';

    }

    public static function translations_tab($atts) {
        extract($atts);
        require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
        $available_translations = wp_get_available_translations();
        $language_placeholder = esc_html__( 'Choose language', 'super-forms' );
        $flags_placeholder = esc_html__( 'Choose a flag', 'super-forms' );
        $flags = SUPER_Common::get_flags();
        if(empty($settings['i18n_disable_browser_translation'])) $settings['i18n_disable_browser_translation'] = 'true';
        if(empty($settings['i18n_switch'])) $settings['i18n_switch'] = 'false';
        ?>
        <div class="super-setting">
            <div class="super-i18n-switch<?php echo ($settings['i18n_switch']=='true' ? ' super-active' : ''); ?>">
                <?php echo esc_html__('Add Language Switch', 'super-forms' ) . ' <span>(' . esc_html__( 'this will add a dropdown at the top of your form from which the user can choose a language', 'super-forms') . ')</span>'; ?>
            </div>
            <div class="super-i18n-disable-browser-translation<?php echo ($settings['i18n_disable_browser_translation']=='true' ? ' super-active' : ''); ?>">
                <?php echo esc_html__('Disable browser translation (recommended)', 'super-forms' ) . ' <span>(' . esc_html__( 'disallow browsers to translate the form', 'super-forms') . ')</span>'; ?>
            </div>
            <ul class="super-translations-list">
                <li>
                    <div class="super-group">
                        <div class="super-dropdown" data-name="language" data-placeholder="- <?php echo $language_placeholder; ?> -">
                            <div class="super-dropdown-placeholder">- <?php echo $language_placeholder; ?> -</div>
                            <div class="super-dropdown-search"><input type="text" placeholder="<?php echo esc_html__( 'Filter', 'super-forms' ); ?>..." /></div>
                            <ul class="super-dropdown-list">
                                <?php
                                foreach($available_translations as $k => $v){
                                    echo '<li class="super-item" data-value="' . $v['language'] . '">' . $v['native_name'] . '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <div class="super-group">
                        <div class="super-dropdown" data-name="flag" data-placeholder="- <?php echo $flags_placeholder; ?> -">
                            <div class="super-dropdown-placeholder">- <?php echo $flags_placeholder; ?> -</div>
                            <div class="super-dropdown-search"><input type="text" placeholder="<?php echo esc_html__( 'Filter', 'super-forms' ); ?>..." /></div>
                            <ul class="super-dropdown-list">
                                <?php
                                foreach($flags as $k => $v){
                                    echo '<li class="super-item" data-value="' . $k . '"><img src="'. esc_url(SUPER_PLUGIN_FILE . 'assets/images/blank.gif') . '" class="flag flag-' . $k . '" />' . $v . '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <div class="super-group super-rtl super-tooltip" data-title="<?php echo esc_html__('Enable Right To Left Layout', 'super-forms' ); ?>">
                        RTL
                    </div>
                    <input type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" data-title="<?php echo esc_html__('Paste shortcode on any page', 'super-forms' ); ?>" value="choose a language first!">
                    <div class="super-edit super-tooltip" data-title="<?php echo esc_html__('Edit Translation', 'super-forms' ); ?>"></div>
                    <div class="super-delete super-tooltip" data-title="<?php echo esc_html__('Delete Translation', 'super-forms' ); ?>"></div>
                </li>

                <?php
                if(!empty($translations)){
                    $i = 0;
                    foreach($translations as $k => $v){
                        ?>
                        <li<?php echo ($i==0 ? ' class="super-default-language"' : ''); ?>>
                            <div class="super-group">
                                <?php
                                if($i==0){
                                    echo '<span>' . esc_html__( 'Default language', 'super-forms' ) . ':</span>';
                                }
                                ?>
                                <div class="super-dropdown" data-name="language" data-placeholder="- <?php echo $language_placeholder; ?> -">
                                    <div class="super-dropdown-placeholder"><?php echo $v['language']; ?></div>
                                    <div class="super-dropdown-search"><input type="text" placeholder="<?php echo esc_html__( 'Filter', 'super-forms' ); ?>..." /></div>
                                    <ul class="super-dropdown-list">
                                        <?php
                                        foreach($available_translations as $tk => $tv){
                                            echo '<li data-value="' . $tv['language'] . '" class="super-item' . ($tv['language']==$k ? ' super-active' : '') . '">' . $tv['native_name'] . '</li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="super-group">
                                <?php
                                if($i==0){
                                    echo '<span>' . esc_html__( 'Choose a flag for this language', 'super-forms' ) . ':</span>';
                                }
                                ?>
                                <div class="super-dropdown" data-name="flag" data-placeholder="- <?php echo $flags_placeholder; ?> -">
                                    <div class="super-dropdown-placeholder"><?php echo '<img src="'. esc_url(SUPER_PLUGIN_FILE . 'assets/images/blank.gif') . '" class="flag flag-' . $v['flag'] . '" />' . $flags[$v['flag']]; ?></div>
                                    <div class="super-dropdown-search"><input type="text" placeholder="<?php echo esc_html__( 'Filter', 'super-forms' ); ?>..." /></div>
                                    <ul class="super-dropdown-list">
                                        <?php
                                        foreach($flags as $fk => $fv){
                                            echo '<li data-value="' . $fk . '" class="super-item' . ($fk==$v['flag'] ? ' super-active' : '') . '"><img src="'. esc_url(SUPER_PLUGIN_FILE . 'assets/images/blank.gif') . '" class="flag flag-' . $fk . '" />' . $fv . '</li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="super-group super-rtl<?php echo ($v['rtl']=='true' ? ' super-active' : ''); ?> super-tooltip" title="<?php echo esc_html__('Enable Right To Left Layout', 'super-forms' ); ?>">
                                RTL
                            </div>

                            <?php
                            $shortcode = '[form-not-saved-yet]';
                            if($form_id!=0){
                                if($i==0){
                                    $shortcode = '[super_form id=&quot;'. $form_id . '&quot;]';
                                }else{
                                    $shortcode = '[super_form i18n=&quot;' . $k . '&quot; id=&quot;'. $form_id . '&quot;]';
                                }
                            }
                            ?>
                            <input type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="<?php echo esc_html__('Paste shortcode on any page', 'super-forms' ); ?>" value="<?php echo $shortcode; ?>">
                            <div class="super-edit super-tooltip" title="<?php echo ($i==0 ? esc_html__('Return to builder', 'super-forms' ) : esc_html__('Edit Translation', 'super-forms' )); ?>"></div>
                            <?php
                            if($i>0){
                                echo '<div class="super-delete super-tooltip" title="' . esc_html__('Delete Translation', 'super-forms' ) . '"></div>';
                            }
                            ?>
                        </li>
                        <?php
                        $i++;
                    }
                }else{
                    ?>
                    <li class="super-default-language">
                        <div class="super-group">
                            <span><?php echo esc_html__( 'Default language', 'super-forms' ); ?>:</span>
                            <div class="super-dropdown" data-name="language" data-placeholder="- <?php echo $language_placeholder; ?> -">
                                <div class="super-dropdown-placeholder">- <?php echo $language_placeholder; ?> -</div>
                                <div class="super-dropdown-search"><input type="text" placeholder="<?php echo esc_html__( 'Filter', 'super-forms' ); ?>..." /></div>
                                <ul class="super-dropdown-list">
                                    <?php
                                    foreach($available_translations as $k => $v){
                                        echo '<li class="super-item" data-value="' . $v['language'] . '">' . $v['native_name'] . '</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <div class="super-group">
                            <span><?php echo esc_html__( 'Choose a flag for this language', 'super-forms' ); ?>:</span>
                            <div class="super-dropdown" data-name="flag" data-placeholder="- <?php echo $flags_placeholder; ?> -">
                                <div class="super-dropdown-placeholder">- <?php echo $flags_placeholder; ?> -</div>
                                <div class="super-dropdown-search"><input type="text" placeholder="<?php echo esc_html__( 'Filter', 'super-forms' ); ?>..." /></div>
                                <ul class="super-dropdown-list">
                                    <?php
                                    foreach($flags as $k => $v){
                                        echo '<li class="super-item" data-value="' . $k . '"><img src="'. esc_url(SUPER_PLUGIN_FILE . 'assets/images/blank.gif') . '" class="flag flag-' . $k . '" />' . $v . '</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <div class="super-group super-rtl super-tooltip" title="<?php echo esc_html__('Enable Right To Left Layout', 'super-forms' ); ?>">
                            RTL
                        </div>
                        <?php
                        $shortcode = '[form-not-saved-yet]';
                        $i18n = '';
                        if($form_id!=0){
                            if($i18n!=''){
                                $shortcode = '[super_form i18n="' . $i18n . '" id="'. $form_id . '"]';
                            }else{
                                $shortcode = '';
                            }
                        }
                        ?>
                        <input type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="<?php echo esc_html__('Paste shortcode on any page', 'super-forms' ); ?>" value="<?php echo $shortcode; ?>">
                        <div class="super-edit super-tooltip" title="<?php echo esc_html__('Return to builder', 'super-forms' ); ?>"></div>
                    </li>
                    <?php
                }
                ?>

            </ul>

            <div class="create-translation-wrapper">
                <span class="super-button super-create-translation super-save"><?php echo esc_html__( 'Add Translation', 'super-forms' ); ?></span>
            </div>

        </div>
        <?php
    }
    public static function get_default_trigger_settings($trigger) {
        if(empty($trigger['active'])) $trigger['active'] = 'true';
        if(empty($trigger['name'])) $trigger['name'] = 'Trigger #1';
        if(empty($trigger['desc'])) $trigger['desc'] = '';
        if(empty($trigger['listen_to'])) $trigger['listen_to'] = '';
        if(empty($trigger['listen_to_ids'])) $trigger['listen_to_ids'] = '';
        if(empty($trigger['order'])) $trigger['order'] = 1;
        if(empty($trigger['event'])) $trigger['event'] = '';
        $trigger = apply_filters( 'super_triggers_default_settings_filter', $trigger );
        return $trigger;
    }
    public static function triggers_tab($atts) {
        $form_id = $atts['form_id'];
        $version = $atts['version'];
        $settings = $atts['settings'];
        $s = $atts['settings'];

        // Get trigger settings
        $triggers = SUPER_Common::get_form_triggers($form_id);
        $statuses = SUPER_Settings::get_entry_statuses();
        if(!isset($statuses['delete'])) $statuses['delete'] = 'Delete';
        $entryStatusesCode = '';
        foreach($statuses as $k => $v) {
            if($k==='') continue;
            if($entryStatusesCode!=='') $entryStatusesCode .= ', ';
            $entryStatusesCode .= '<code>'.$k.'</code>';
        }

        $postStatusesCode = '';
        $statuses = array(
            'publish' => esc_html__( 'Publish (default)', 'super-forms' ),
            'future' => esc_html__( 'Future', 'super-forms' ),
            'draft' => esc_html__( 'Draft', 'super-forms' ),
            'pending' => esc_html__( 'Pending', 'super-forms' ),
            'private' => esc_html__( 'Private', 'super-forms' ),
            'trash' => esc_html__( 'Trash', 'super-forms' ),
            'auto-draft' => esc_html__( 'Auto-Draft', 'super-forms' ),
            'delete' => esc_html__( 'Delete', 'super-forms' )
        );
        foreach($statuses as $k => $v) {
            if($k==='') continue;
            if($postStatusesCode!=='') $postStatusesCode .= ', ';
            $postStatusesCode .= '<code>'.$k.'</code>';
        }

        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters( 'editable_roles', $all_roles );
        $rolesCode = '';
        foreach($editable_roles as $k => $v){
            if($rolesCode!=='') $rolesCode .= ', ';
            $rolesCode .= '<code>'.$k.'</code>';
        }

        // Possible events to choose from
        $events = array(
            '' => '- choose an event - ',
            array(
                'label' => 'Super Forms',
                'items' => array(
                    'sf.before.submission' => 'Before form submission',
                    'sf.after.submission' => 'After form submission',
                    'sf.submission.validation' => 'Validate form data'
                )
            ),
            array(
                'label' => 'WooCommerce',
                'items' => array(
                    'wc.order.status.completed' => 'Order status changes to `completed`'
                )
            ),
            array(
                'label' => 'Stripe',
                'items' => array(
                    'stripe.checkout.session.completed' => 'Checkout session completed',
                    'stripe.checkout.session.async_payment_failed' => 'Checkout session async payment failed',
                    'stripe.fulfill_order' => 'Fulfill order'
                )
            )
        );
        // Possible actions to choose from
        $actions = array(
            '' => '- choose an action - ',
            'send_email' => 'Send an E-mail',
            'insert_db_row' => 'Insert row to database table',
            'validate_field' => 'Validate field value',
            'create_post' => 'Create a post/page/product'
        );
        $logic = array( '==' => '== Equal', '!=' => '!= Not equal', '??' => '?? Contains', '!!' => '!! Not contains', '>'  => '&gt; Greater than', '<'  => '&lt;  Less than', '>=' => '&gt;= Greater than or equal to', '<=' => '&lt;= Less than or equal');

        // Enable WooCommerce Checkout & Instant Order
        $nodes = array(
            array(
                'notice' => 'hint', // hint/info
                'content' => '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . esc_html__('With triggers you can execute specific actions based on events that occur on your WordPress site.', 'super-forms')
            ),
            array(
                'name' => 'triggers',
                'type' => 'repeater',
                'nodes' => array( // repeater item
                    array(
                        'inline' => true,
                        'padding' => false,
                        'nodes' => array(
                            array(
                                'width_auto' => true, // 'sfui-width-auto'
                                'name' => 'enabled',
                                'title' => 'Enabled',
                                'type' => 'checkbox',
                                'default' => ''
                            ),
                            array(
                                'name' => 'event',
                                'subline' => 'Choose an event that will trigger your action(s)',
                                'type' => 'select',
                                'options' => $events,
                                'default' => ''
                            ),
                            array(
                                'name' => 'name',
                                'subline' => 'Trigger name or description',
                                'type' => 'text',
                                'default' => 'Trigger #1',
                                'placeholder' => 'e.g. Send E-mail when WooCommerce order status changed to `completed`'
                            ),
                            array(
                                'name' => 'listen_to',
                                'subline' => 'Trigger for the specified form(s)',
                                'type' => 'select',
                                'options' => array(
                                    '' => 'Current form (default)',
                                    'all' => 'All forms (globally)',
                                    'id' => 'Specific forms only'
                                ),
                                'default' => ''
                            ),
                            array(
                                'filter' => 'listen_to;id',
                                'name' => 'ids',
                                'subline' => 'Separate each form ID with a comma',
                                'type' => 'text',
                                'default' => ''
                            ),
                            array(
                                'vertical' => true,
                                'name' => 'order',
                                'subline' => 'Execution order (low number executes first)',
                                'type' => 'number',
                                'default' => '1'
                            ),
                        )
                    ),

                    array(
                        //'width_auto' => false, // 'sfui-width-auto'
                        'wrap' => false,
                        'group' => true, // sfui-setting-group
                        'group_name' => '',
                        'inline' => true, // sfui-inline
                        //'vertical' => true, // sfui-vertical
                        'filter' => 'event;!',
                        'nodes' => array(
                            array(
                                'toggle' => true,
                                'title' => esc_html__( 'When above event fires, execute below actions', 'super-forms' ),
                                'nodes' => array(
                                    array(
                                        'name' => 'actions',
                                        'type' => 'repeater',
                                        'nodes' => array( // repeater item
                                            array(
                                                'inline' => true,
                                                'padding' => false,
                                                'nodes' => array(
                                                    array(
                                                        //'vertical' => true, // sfui-vertical
                                                        'width_auto' => true, // 'sfui-width-auto'
                                                        'name' => 'action',
                                                        'subline' => 'The action to perform when the event is triggered',
                                                        'type' => 'select',
                                                        'options' => $actions,
                                                        'default' => ''
                                                    ),
                                                    array(
                                                        'width_auto' => true, // 'sfui-width-auto'
                                                        'vertical' => true,
                                                        'name' => 'order',
                                                        'subline' => 'Execution order (low number executes first)',
                                                        'type' => 'number',
                                                        'default' => '1'
                                                    ),
                                                )
                                            ),
                                            array(
                                                'toggle' => true,
                                                'title' => esc_html__( 'Action settings', 'super-forms' ),
                                                'filter' => 'action;!',
                                                'vertical' => true, // sfui-vertical
                                                'nodes' => array(
                                                    array(
                                                        'width_auto' => true, // 'sfui-width-auto'
                                                        'wrap' => false,
                                                        'group' => true, // sfui-setting-group
                                                        'group_name' => 'conditions',
                                                        'inline' => true, // sfui-inline
                                                        //'vertical' => true, // sfui-vertical
                                                        //'filter' => 'action;!',
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'enabled',
                                                                'type' => 'checkbox',
                                                                'default' => 'false',
                                                                'title' => esc_html__( 'Only execute this action when below condition is met', 'super-forms' ),
                                                                'nodes' => array(
                                                                    array(
                                                                        'padding' => false,
                                                                        'sub' => true, // sfui-sub-settings
                                                                        //'group' => true, // sfui-setting-group
                                                                        'inline' => true, // sfui-inline
                                                                        //'vertical' => true, // sfui-vertical
                                                                        'filter' => 'conditions.enabled;true',
                                                                        'nodes' => array(
                                                                            array(
                                                                                'name' => 'f1',
                                                                                'type' => 'text',
                                                                                'default' => '',
                                                                                'placeholder' => 'e.g. {tag}',
                                                                            ),
                                                                            array(
                                                                                'name' => 'logic',
                                                                                'type' => 'select', // dropdown
                                                                                'options' => $logic,
                                                                                'default' => '',
                                                                            ),
                                                                            array(
                                                                                'name' => 'f2',
                                                                                'type' => 'text',
                                                                                'default' => '',
                                                                                'placeholder' => 'e.g. true'
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'email',
                                                        'vertical' => true,
                                                        'filter' => 'action;send_email',
                                                        'nodes' => array(
                                                            array(
                                                                'toggle' => true,
                                                                'title' => esc_html__( 'E-mail headers', 'super-forms' ),
                                                                'notice' => 'hint', // hint/info
                                                                'content' => sprintf( esc_html__( 'The `From email` should end with %s for E-mails to work. If you are using an email provider (Gmail, Yahoo, Outlook.com, etc) it should be the email address of that account. If you have problems with E-mail delivery you can read this guide on possible solutions: %sEmail delivery problems%s', 'super-forms' ), '<strong style="color:red;">@' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>', '<a href="https://docs.super-forms.com/common-problems/index/email-delivery-problems">', '</a>' ),
                                                                'nodes' => array(
                                                                    array(
                                                                        'name' => 'to',
                                                                        'title' => esc_html__( 'To', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Where the E-mail will be delivered to e.g. {email}', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default' => '{email}',
                                                                        'reset' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'from_email',
                                                                        'title' => esc_html__( 'From email', 'super-forms' ),
                                                                        'subline' => sprintf( esc_html__( 'Your company E-mail address e.g. info%s', 'super-forms' ), '<strong style="color:red;">@' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>' ),
                                                                        'type' => 'text',
                                                                        'default' => 'no-reply@'.str_replace('www.', '', $_SERVER["SERVER_NAME"]),
                                                                        'reset' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'from_name',
                                                                        'title' => esc_html__( 'From name', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Your company name e.g. Starbucks', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default' => '{option_blogname}',
                                                                        'reset' => true
                                                                    ),
                                                                    array(
                                                                        'wrap' => false,
                                                                        'width_full' => true,
                                                                        'group' => true, 
                                                                        'group_name' => 'reply_to',
                                                                        'vertical' => true, 
                                                                        'nodes' => array(
                                                                            array(
                                                                                'name' => 'enabled',
                                                                                'title' => esc_html__( 'Reply to a different email address (optional)', 'super-forms' ),
                                                                                'type' => 'checkbox',
                                                                                'default' => 'false'
                                                                            ),
                                                                            array(
                                                                                'wrap' => false,
                                                                                'group' => true, 
                                                                                'group_name' => '',
                                                                                'inline' => true, 
                                                                                'padding' => false,
                                                                                'filter' => 'reply_to.enabled;true',
                                                                                'nodes' => array(
                                                                                    array(
                                                                                        'name' => 'email',
                                                                                        'title' => esc_html__( 'Reply-To email', 'super-forms' ),
                                                                                        'subline' => esc_html__( 'The email address to reply to', 'super-forms' ),
                                                                                        'type' => 'text',
                                                                                        'default' => '',
                                                                                    ),
                                                                                    array(
                                                                                        'name' => 'name',
                                                                                        'title' => esc_html__( 'Reply-To name (optional)', 'super-forms' ),
                                                                                        'subline' => esc_html__( 'The name of the person or company', 'super-forms' ),
                                                                                        'type' => 'text',
                                                                                        'default' => '',
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    ),
                                                                )
                                                            ),
                                                            array(
                                                                'toggle' => true,
                                                                'title' => esc_html__( 'E-mail content', 'super-forms' ),
                                                                'vertical' => true, // sfui-vertical
                                                                'nodes' => array(
                                                                    array(
                                                                        'name' => 'subject',
                                                                        'type' => 'text',
                                                                        'default' => esc_html__( 'New question', 'super-forms' ),
                                                                        'title' => esc_html__( 'Subject', 'super-forms' ),
                                                                        'reset' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'body',
                                                                        'type' => 'textarea',
                                                                        'tinymce' => true,
                                                                        'default' => sprintf( esc_html__( "The following information has been send by the submitter:\n\n%s\n\nBest regards, %s", 'super-forms' ), '{loop_fields}', '{option_blogname}' ),
                                                                        'title' => esc_html__( 'Body', 'super-forms' )
                                                                    ),
                                                                    array(
                                                                        'name' => 'attachments',
                                                                        'title' => esc_html__( 'Attachments', 'super-forms' ),
                                                                        'label' => esc_html__( 'Hold Ctrl to add multiple files', 'super-forms' ),
                                                                        'type' => 'files', // file
                                                                        'default' => '',
                                                                    )
                                                                )
                                                            ),
                                                            array(
                                                                'toggle' => true,
                                                                'title' => esc_html__( 'Advanced options', 'super-forms' ),
                                                                'vertical' => true, // sfui-vertical
                                                                'nodes' => array(
                                                                    array(
                                                                        'name' => 'loop_open',
                                                                        'type' => 'textarea',
                                                                        'default' => '<table cellpadding="5">',
                                                                        'title' => esc_html__( 'Loop start HTML', 'super-forms' ),
                                                                        'subline' => esc_html__( 'If your loop is a table, this should be the table opening tag', 'super-forms' ),                                                                         
                                                                        'reset' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'loop',
                                                                        'type' => 'textarea',
                                                                        'default' => '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>',
                                                                        'title' => esc_html__( 'Loop content', 'super-forms' ),
                                                                        'subline' => esc_html__( 'The {loop_fields} tag will be replaced with this content. Use {loop_label} and {loop_value} to retrieve the field labels and their values', 'super-forms' ),
                                                                        'reset' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'loop_close',
                                                                        'type' => 'textarea',
                                                                        'default' => '</table>',
                                                                        'title' => esc_html__( 'Loop end HTML', 'super-forms' ),
                                                                        'subline' => esc_html__( 'If your loop is a table, this should be the table closing tag', 'super-forms' ),
                                                                        'reset' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'exclude_empty',
                                                                        'type' => 'checkbox',
                                                                        'default' => 'true',
                                                                        'title' => esc_html__( 'Exclude empty values from {loop_fieds}', 'super-forms' ),
                                                                        'subline' => esc_html__( 'This will strip out any fields that where not filled out by the user', 'super-forms' )
                                                                    ),
                                                                    array(
                                                                        'wrap' => false,
                                                                        'padding' => false,
                                                                        'group' => true, // sfui-setting-group
                                                                        'group_name' => 'exclude',
                                                                        'vertical' => true, // sfui-vertical
                                                                        'nodes' => array(
                                                                            array(
                                                                                'name' => 'enabled',
                                                                                'type' => 'checkbox',
                                                                                'default' => 'true',
                                                                                'title' => 'Exclude specific fields from the {loop_fieds}'
                                                                            ),
                                                                            array(
                                                                                'name' => 'exclude_fields',
                                                                                'type' => 'repeater',
                                                                                'filter' => 'exclude.enabled;true',
                                                                                'nodes' => array( // repeater item
                                                                                    array(
                                                                                        'name' => 'name',
                                                                                        'subline' => 'Field name',
                                                                                        'type' => 'text',
                                                                                        'default' => '',
                                                                                        'placeholder' => 'e.g. birth_date'
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    ),
                                                                    array(
                                                                        'name' => 'rtl',
                                                                        'type' => 'checkbox',
                                                                        'default' => 'false',
                                                                        'title' => esc_html__( 'Enable RTL E-mail layout', 'super-forms' ),
                                                                        'subline' => esc_html__( 'This will apply a right to left layout for your emails.', 'super-forms' ),
                                                                        'accepted_values' => array(
                                                                            array('v'=>'true'), 
                                                                            array('v'=>'false')
                                                                        ),
                                                                    ),
                                                                    array(
                                                                        'wrap' => false,
                                                                        'padding' => false,
                                                                        'group' => true, // sfui-setting-group
                                                                        'group_name' => 'headers',
                                                                        'vertical' => true, // sfui-vertical
                                                                        'filter' => '',
                                                                        'nodes' => array(
                                                                            array(
                                                                                'name' => 'enabled',
                                                                                'type' => 'checkbox',
                                                                                'default' => 'false',
                                                                                'title' => esc_html__( 'Define custom E-mail headers', 'super-forms' )
                                                                            ),
                                                                            array(
                                                                                'name' => 'headers',
                                                                                'inline' => true, // sfui-vertical
                                                                                'type' => 'repeater',
                                                                                'filter' => 'headers.enabled;true',
                                                                                'nodes' => array( // repeater item
                                                                                    array(
                                                                                        'name' => 'name',
                                                                                        'subline' => 'Header name/key',
                                                                                        'type' => 'text',
                                                                                        'default' => '',
                                                                                        'placeholder' => 'e.g. X-Custom-Header'
                                                                                    ),
                                                                                    array(
                                                                                        'vertical' => true,
                                                                                        'name' => 'value',
                                                                                        'subline' => 'Header value',
                                                                                        'type' => 'text',
                                                                                        'placeholder' => 'e.g. foobar'
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    ),
                                                                    array(
                                                                        'name' => 'cc',
                                                                        'title' => esc_html__( 'CC', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Send copy to following address(es)', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default' => '',
                                                                    ),
                                                                    array(
                                                                        'name' => 'bcc',
                                                                        'title' => esc_html__( 'BCC', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Send copy to following address(es), without being able to see the address', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default' => '',
                                                                    ),
                                                                    array(
                                                                        'name' => 'header_additional',
                                                                        'title' => esc_html__( 'Additional Headers', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Add any extra email headers here', 'super-forms' ),
                                                                        'type' => 'textarea',
                                                                        'default' => '',
                                                                    ),
                                                                    array(
                                                                        'name' => 'content_type',
                                                                        'title' => 'Content type',
                                                                        'subline' => '',
                                                                        'accepted_values' => array(
                                                                            array('v'=>'html', 'i'=>'(default)'), 
                                                                            array('v'=>'plain','i'=>'(plain text)')
                                                                        ),
                                                                        'type' => 'text',
                                                                        'default' => 'html'
                                                                    ),
                                                                    array(
                                                                        'name' => 'charset',
                                                                        'title' => 'Charset',
                                                                        'subline' => 'The charset to use for this email. Example: UTF-8 or ISO-8859-1',
                                                                        'type' => 'text',
                                                                        'default' => 'UTF-8'
                                                                    ),
                                                                )
                                                            ),
                                                            array(
                                                                'toggle' => true,
                                                                'title' => esc_html__( 'Schedule (optional)', 'super-forms' ),
                                                                'vertical' => true, // sfui-vertical
                                                                'nodes' => array(
                                                                    array(
                                                                        'wrap' => false,
                                                                        'padding' => false,
                                                                        'group' => true,
                                                                        'group_name' => 'schedule',
                                                                        'vertical' => true,
                                                                        'nodes' => array(
                                                                            array(
                                                                                'name' => 'enabled',
                                                                                'type' => 'checkbox',
                                                                                'default' => 'false',
                                                                                'title' => esc_html__( 'Enable scheduled execution', 'super-forms' )
                                                                            ),
                                                                            array(
                                                                                'name' => 'schedules',
                                                                                'type' => 'repeater',
                                                                                'inline' => true,
                                                                                'filter' => 'schedule.enabled;true',
                                                                                'nodes' => array(
                                                                                    array(
                                                                                        'name' => 'date',
                                                                                        'title' => 'Base date (leave blank to use the event date)',
                                                                                        'subline' => 'Must be English formatted date e.g: `25-03-2020`. When using a datepicker that doesn\'t use the correct format, you can use the tag <code>{date;timestamp}</code> to retrieve the timestamp which will work correctly with any date format (leave blank to use the form submission date)',
                                                                                        'type' => 'text',
                                                                                        'default' => ''
                                                                                    ),
                                                                                    array(
                                                                                        'name' => 'days',
                                                                                        'title' => 'Execute after or before (in days) based of the base date.',
                                                                                        'subline' => '0 = The same day, 1 = One day after, 5 = Five days later, -1 = One day before, -3 = Three days before',
                                                                                        'type' => 'text',
                                                                                        'default' => '0'
                                                                                    ),
                                                                                    array(
                                                                                        'name' => 'method',
                                                                                        'title' => 'Execute at a specific time or offset',
                                                                                        'subline' => '',
                                                                                        'accepted_values' => array(
                                                                                            array('v'=>'instant'), 
                                                                                            array('v'=>'time','i'=>'(at a fixed time e.g. at 09:00)'),
                                                                                            array('v'=>'offset','i'=>'(relative to the base date e.g. 2 hours after)')
                                                                                        ),
                                                                                        'type' => 'text',
                                                                                        'default' => 'time'
                                                                                    ),
                                                                                    array(
                                                                                        'name' => 'time',
                                                                                        'title' => 'Time',
                                                                                        'subline' => 'Use 24h format e.g: 09:30, 14:00, 18:30 etc.',
                                                                                        'accepted_values' => array(
                                                                                            array('v'=>'09:00'),
                                                                                            array('v'=>'09:15'),
                                                                                            array('v'=>'09:30', 'i'=>'etc.')
                                                                                        ),
                                                                                        'type' => 'text',
                                                                                        'default' => '09:00',
                                                                                        'filter' => 'method;time'
                                                                                    ),
                                                                                    array(
                                                                                        'name' => 'offset',
                                                                                        'title' => 'Offset',
                                                                                        'subline' => 'Enter an offset based of the base date.',
                                                                                        'accepted_values' => array(
                                                                                            array('v'=>'0', 'i'=>'(instantly)'), 
                                                                                            array('v'=>'0.08', 'i'=>'(after 5 min.)'), 
                                                                                            array('v'=>'0.16', 'i'=>'(after 10 min.)'), 
                                                                                            array('v'=>'0.5', 'i'=>'(after 30 min.)'), 
                                                                                            array('v'=>'2', 'i'=>'(after two hours)'), 
                                                                                            array('v'=>'-5', 'i'=>'(fie hours prior)')
                                                                                        ),
                                                                                        'type' => 'text',
                                                                                        'default' => '0',
                                                                                        'filter' => 'method;offset'
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )

                )
            )

            // tmp array(
            // tmp     'name' => 'checkout',
            // tmp     'type' => 'checkbox',
            // tmp     'default' => 'false',
            // tmp     'title' => esc_html__( 'Enable WooCommerce Checkout', 'super-forms' ),
            // tmp     'nodes' => array(
            // tmp         array(
            // tmp             'sub' => true, // sfui-sub-settings
            // tmp             'filter' => 'checkout;true',
            // tmp             'nodes' => array(
            // tmp                 array(
            // tmp                     //'width_auto' => false, // 'sfui-width-auto'
            // tmp                     'wrap' => false,
            // tmp                     'group' => true, // sfui-setting-group
            // tmp                     'group_name' => 'checkout_conditionally',
            // tmp                     'inline' => true, // sfui-inline
            // tmp                     //'vertical' => true, // sfui-vertical
            // tmp                     'filter' => 'checkout;true',
            // tmp                     'nodes' => array(
            // tmp                         array(
            // tmp                             'name' => 'enabled',
            // tmp                             'type' => 'checkbox',
            // tmp                             'default' => 'false',
            // tmp                             'title' => esc_html__( 'Only checkout when below condition is met', 'super-forms' ),
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'sub' => true, // sfui-sub-settings
            // tmp                                     //'group' => true, // sfui-setting-group
            // tmp                                     'inline' => true, // sfui-inline
            // tmp                                     //'vertical' => true, // sfui-vertical
            // tmp                                     'filter' => 'checkout_conditionally.enabled;true',
            // tmp                                     'nodes' => array(
            // tmp                                         array(
            // tmp                                             'name' => 'f1',
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. {tag}',
            // tmp                                         ),
            // tmp                                         array(
            // tmp                                             'name' => 'logic',
            // tmp                                             'type' => 'select', // dropdown
            // tmp                                             'options' => array(
            // tmp                                                 '==' => '== Equal',
            // tmp                                                 '!=' => '!= Not equal',
            // tmp                                                 '??' => '?? Contains',
            // tmp                                                 '!!' => '!! Not contains',
            // tmp                                                 '>'  => '&gt; Greater than',
            // tmp                                                 '<'  => '&lt;  Less than',
            // tmp                                                 '>=' => '&gt;= Greater than or equal to',
            // tmp                                                 '<=' => '&lt;= Less than or equal'
            // tmp                                             ),
            // tmp                                             'default' => '',
            // tmp                                         ),
            // tmp                                         array(
            // tmp                                             'name' => 'f2',
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. true'
            // tmp                                         )
            // tmp                                     )
            // tmp                                 )
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp                 array(
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Define products', 'super-forms' ) . '<span style="margin-left:10px;color:red;">(required)</span>',
            // tmp                     'nodes' => array(
            // tmp                         array(
            // tmp                             'name' => 'products',
            // tmp                             'type' => 'repeater',
            // tmp                             'title' => esc_html__( 'Products to add to the cart/checkout', 'super-forms' ),
            // tmp                             'nodes' => array( // repeater item
            // tmp                                 array(
            // tmp                                     'inline' => true,
            // tmp                                     'padding' => false,
            // tmp                                     'nodes' => array(
            // tmp                                         array(
            // tmp                                             'vertical' => true, // sfui-vertical
            // tmp                                             'name' => 'id',
            // tmp                                             'title' => 'Product ID',
            // tmp                                             'label' => 'Enter the WooCommerce product ID',
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. {product_id}'
            // tmp                                         ),
            // tmp                                         array(
            // tmp                                             'vertical' => true, // sfui-vertical
            // tmp                                             'name' => 'qty',
            // tmp                                             'title' => 'Cart quantity',
            // tmp                                             'label' => 'How many items to add to the cart',
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. {item_quantity}'
            // tmp                                         ),
            // tmp                                         array(
            // tmp                                             'vertical' => true, // sfui-vertical
            // tmp                                             'name' => 'price',
            // tmp                                             'title' => 'Dynamic price',
            // tmp                                             'label' => 'Leave blank if you do not have the Name Your Price plugin installed',
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. {dynamic_price}'
            // tmp                                         ),
            // tmp                                         array(
            // tmp                                             'vertical' => true, // sfui-vertical
            // tmp                                             'name' => 'variation',
            // tmp                                             'title' => 'Variation ID (optional)',
            // tmp                                             'label' => 'If a product has variations, you can enter the variation ID here',
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. {variation_id}'
            // tmp                                         )
            // tmp                                     )
            // tmp                                 ),


            // tmp                                 array(
            // tmp                                     'name' => 'meta',
            // tmp                                     'type' => 'checkbox',
            // tmp                                     'default' => 'false',
            // tmp                                     'title' => esc_html__( 'Product meta data (optional)', 'super-forms' ),
            // tmp                                     'nodes' => array(
            // tmp                                         array(
            // tmp                                             'sub' => true, // sfui-sub-settings
            // tmp                                             'filter' => 'meta;true',
            // tmp                                             'nodes' => array(
            // tmp                                                 array(
            // tmp                                                     'name' => 'items',
            // tmp                                                     'type' => 'repeater',
            // tmp                                                     'nodes' => array( // repeater item
            // tmp                                                         array(
            // tmp                                                             'inline' => true,
            // tmp                                                             'padding' => false,
            // tmp                                                             'nodes' => array(
            // tmp                                                                 array(
            // tmp                                                                     'vertical' => true, // sfui-vertical
            // tmp                                                                     'name' => 'label',
            // tmp                                                                     'title' => 'Label',
            // tmp                                                                     'label' => 'Define the meta label, for instance `Color`',
            // tmp                                                                     'type' => 'text',
            // tmp                                                                     'default' => '',
            // tmp                                                                     'placeholder' => 'e.g. Color'
            // tmp                                                                 ),
            // tmp                                                                 array(
            // tmp                                                                     'vertical' => true, // sfui-vertical
            // tmp                                                                     'name' => 'value',
            // tmp                                                                     'title' => 'Value',
            // tmp                                                                     'label' => 'Define the meta value, for instance `red`',
            // tmp                                                                     'type' => 'text',
            // tmp                                                                     'default' => '',
            // tmp                                                                     'placeholder' => 'e.g. red'
            // tmp                                                                 ),
            // tmp                                                             )
            // tmp                                                         )
            // tmp                                                     )
            // tmp                                                 )
            // tmp                                             )
            // tmp                                         )
            // tmp                                     )
            // tmp                                 )
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp                 array(
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Checkout fee(s)', 'super-forms' ),
            // tmp                     'nodes' => array(
            // tmp                         array(
            // tmp                             //'width_auto' => false, // 'sfui-width-auto'
            // tmp                             'wrap' => false,
            // tmp                             'group' => true, // sfui-setting-group
            // tmp                             'group_name' => 'fees',
            // tmp                             //'inline' => true, // sfui-inline
            // tmp                             'vertical' => true, // sfui-vertical
            // tmp                             'filter' => 'checkout;true',
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'name' => 'enabled',
            // tmp                                     'type' => 'checkbox',
            // tmp                                     'default' => 'false',
            // tmp                                     'title' => esc_html__( 'Add checkout fee(s)', 'super-forms' ),
            // tmp                                 ),
            // tmp                                 array(
            // tmp                                     'sub' => true, // sfui-sub-settings
            // tmp                                     'filter' => 'fees.enabled;true',
            // tmp                                     'nodes' => array(
            // tmp                                         array(
            // tmp                                             'name' => 'items',
            // tmp                                             'type' => 'repeater',
            // tmp                                             'nodes' => array( // repeater item
            // tmp                                                 array(
            // tmp                                                     'inline' => true,
            // tmp                                                     'padding' => false,
            // tmp                                                     'nodes' => array(
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'name',
            // tmp                                                             'title' => 'Fee name',
            // tmp                                                             'label' => 'Enter the name/label of the fee',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. Administration fee'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'amount',
            // tmp                                                             'title' => 'Amount',
            // tmp                                                             'label' => 'Enter the fee amount (must be a float value)',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. 5.95'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'taxable',
            // tmp                                                             'title' => 'Taxable',
            // tmp                                                             'label' => 'Accepted values: <code>true</code> or <code>false</code>',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => 'false',
            // tmp                                                             'placeholder' => 'e.g. false'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'tax_class',
            // tmp                                                             'title' => 'Tax class',
            // tmp                                                             'label' => 'e.g. <code>none</code>, <code>standard</code>, <code>reduced-rate</code>, <code>zero-rate</code>',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => 'none',
            // tmp                                                             'placeholder' => 'e.g. none'
            // tmp                                                         )
            // tmp                                                     )
            // tmp                                                 )
            // tmp                                             )
            // tmp                                         )
            // tmp                                     )
            // tmp                                 )
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp                 array(
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Populate checkout fields with form data', 'super-forms' ),
            // tmp                     'nodes' => array(
            // tmp                         array(
            // tmp                             //'width_auto' => false, // 'sfui-width-auto'
            // tmp                             'wrap' => false,
            // tmp                             'group' => true, // sfui-setting-group
            // tmp                             'group_name' => 'populate',
            // tmp                             //'inline' => true, // sfui-inline
            // tmp                             'vertical' => true, // sfui-vertical
            // tmp                             'filter' => 'checkout;true',
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'name' => 'enabled',
            // tmp                                     'type' => 'checkbox',
            // tmp                                     'default' => 'false',
            // tmp                                     'title' => esc_html__( 'Populate checkout fields with form data', 'super-forms' ),
            // tmp                                 ),
            // tmp                                 array(
            // tmp                                     'sub' => true, // sfui-sub-settings
            // tmp                                     'filter' => 'populate.enabled;true',
            // tmp                                     'nodes' => array(
            // tmp                                         array(
            // tmp                                             'name' => 'items',
            // tmp                                             'type' => 'repeater',
            // tmp                                             'nodes' => array( // repeater item
            // tmp                                                 array(
            // tmp                                                     'inline' => true,
            // tmp                                                     'padding' => false,
            // tmp                                                     'nodes' => array(
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'name',
            // tmp                                                             'title' => 'Checkout field name',
            // tmp                                                             'subline' => 'Enter the field name of this checkout field. Available field names: <code>billing_country</code>, <code>shipping_country</code>, <code>billing_first_name</code>, <code>billing_last_name</code>, <code>billing_company</code>, <code>billing_country</code>, <code>billing_address_1</code>, <code>billing_address_2</code>, <code>billing_postcode</code>, <code>billing_city</code>, <code>billing_state</code>, <code>billing_phone</code>, <code>billing_email</code>, <code>order_comment</code>',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. billing_first_name'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'value',
            // tmp                                                             'title' => 'Value',
            // tmp                                                             'subline' => 'The value to set the field to',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. {first_name}'
            // tmp                                                         )
            // tmp                                                     )
            // tmp                                                 )
            // tmp                                             )
            // tmp                                         )
            // tmp                                     )
            // tmp                                 )
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp                 array(
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Custom checkout fields', 'super-forms' ),
            // tmp                     'nodes' => array(
            // tmp                         array(
            // tmp                             //'width_auto' => false, // 'sfui-width-auto'
            // tmp                             'wrap' => false,
            // tmp                             'group' => true, // sfui-setting-group
            // tmp                             'group_name' => 'fields',
            // tmp                             //'inline' => true, // sfui-inline
            // tmp                             'vertical' => true, // sfui-vertical
            // tmp                             'filter' => 'checkout;true',
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'name' => 'enabled',
            // tmp                                     'type' => 'checkbox',
            // tmp                                     'default' => 'false',
            // tmp                                     'title' => esc_html__( 'Add custom checkout field(s)', 'super-forms' ),
            // tmp                                 ),
            // tmp                                 array(
            // tmp                                     'sub' => true, // sfui-sub-settings
            // tmp                                     'filter' => 'fields.enabled;true',
            // tmp                                     'nodes' => array(
            // tmp                                         array(
            // tmp                                             'name' => 'items',
            // tmp                                             'type' => 'repeater',
            // tmp                                             'nodes' => array( // repeater item
            // tmp                                                 array(
            // tmp                                                     'inline' => true,
            // tmp                                                     'padding' => false,
            // tmp                                                     'nodes' => array(
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'type',
            // tmp                                                             'title' => 'Type',
            // tmp                                                             'type' => 'select',
            // tmp                                                             'options' => array(
            // tmp                                                                 'text' => 'Text',
            // tmp                                                                 'textarea' => 'Textarea',
            // tmp                                                                 'password' => 'Password',
            // tmp                                                                 'select' => 'Select (dropdown)'
            // tmp                                                             ),
            // tmp                                                             'default' => 'text'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'type' => 'text',
            // tmp                                                             'name' => 'name',
            // tmp                                                             'title' => 'Name',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. '
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'label',
            // tmp                                                             'title' => 'Label',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. '
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'placeholder',
            // tmp                                                             'title' => 'Placeholder',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. '
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'value',
            // tmp                                                             'title' => 'Value',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. {tag}'
            // tmp                                                         ),
            // tmp                                                     )
            // tmp                                                 ),
            // tmp                                                 array(
            // tmp                                                     'inline' => true,
            // tmp                                                     'padding' => false,
            // tmp                                                     'nodes' => array(
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'required',
            // tmp                                                             'title' => 'Required',
            // tmp                                                             'label' => 'Accepted values: <code>true</code> or <code>false</code>',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => 'true',
            // tmp                                                             'placeholder' => 'e.g. true'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'section',
            // tmp                                                             'title' => 'Section',
            // tmp                                                             'label' => 'Choose where to put the field',
            // tmp                                                             'type' => 'select',
            // tmp                                                             'options' => array(
            // tmp                                                                 'billing' => 'Billing',
            // tmp                                                                 'shipping' => 'Shipping',
            // tmp                                                                 'account' => 'Account',
            // tmp                                                                 'order' => 'Order'
            // tmp                                                             ),
            // tmp                                                             'default' => 'billing'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'clear',
            // tmp                                                             'title' => 'Clear',
            // tmp                                                             'label' => 'Puts the field on a single row. Accepted values: <code>true</code> or <code>false</code>',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => 'true',
            // tmp                                                             'placeholder' => 'e.g. true'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'class',
            // tmp                                                             'title' => 'Class',
            // tmp                                                             'label' => 'Apply a custom class name for the input',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. my-custom-input-classname'
            // tmp                                                         ),
            // tmp                                                         array(
            // tmp                                                             'vertical' => true, // sfui-vertical
            // tmp                                                             'name' => 'label_class',
            // tmp                                                             'title' => 'Label class',
            // tmp                                                             'label' => 'Apply a custom class name for the label',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'type' => 'text',
            // tmp                                                             'default' => '',
            // tmp                                                             'placeholder' => 'e.g. my-custom-label-classname'
            // tmp                                                         ),
            // tmp                                                     )
            // tmp                                                 ),
            // tmp                                                 array(
            // tmp                                                     'inline' => true,
            // tmp                                                     'padding' => false,
            // tmp                                                     'nodes' => array(
            // tmp                                                         array(
            // tmp                                                             'name' => 'skip',
            // tmp                                                             'type' => 'checkbox',
            // tmp                                                             'default' => 'false',
            // tmp                                                             'title' => esc_html__( 'Only add if field is not conditionally hidden', 'super-forms' )
            // tmp                                                         ),
            // tmp                                                     )
            // tmp                                                 ),
            // tmp                                                 // Dropdown items
            // tmp                                                 array(
            // tmp                                                     'wrap' => false,
            // tmp                                                     'group' => true, // sfui-setting-group
            // tmp                                                     'group_name' => '',
            // tmp                                                     'inline' => true, // sfui-inline
            // tmp                                                     //'vertical' => true, // sfui-vertical
            // tmp                                                     //'filter' => 'type;select',
            // tmp                                                     'filter' => 'fields.type;select',
            // tmp                                                     'nodes' => array(
            // tmp                                                         array(
            // tmp                                                             'name' => 'options',
            // tmp                                                             'type' => 'repeater',
            // tmp                                                             'title' => esc_html__( 'Dropdown items', 'super-forms' ),
            // tmp                                                             'nodes' => array( // repeater item
            // tmp                                                                 array(
            // tmp                                                                     'inline' => true,
            // tmp                                                                     'padding' => false,
            // tmp                                                                     'nodes' => array(
            // tmp                                                                         array(
            // tmp                                                                             'vertical' => true, // sfui-vertical
            // tmp                                                                             'name' => 'label',
            // tmp                                                                             'title' => 'Item label',
            // tmp                                                                             'type' => 'text',
            // tmp                                                                             'default' => '',
            // tmp                                                                             'placeholder' => 'e.g. Red'
            // tmp                                                                         ),
            // tmp                                                                         array(
            // tmp                                                                             'vertical' => true, // sfui-vertical
            // tmp                                                                             'name' => 'value',
            // tmp                                                                             'title' => 'Item value',
            // tmp                                                                             'type' => 'text',
            // tmp                                                                             'default' => '',
            // tmp                                                                             'placeholder' => 'e.g. red'
            // tmp                                                                         )
            // tmp                                                                     )
            // tmp                                                                 )
            // tmp                                                             )
            // tmp                                                         )
            // tmp                                                     )
            // tmp                                                 )
            // tmp                                             )
            // tmp                                         )
            // tmp                                     )
            // tmp                                 )
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp                 // Update entry status when WooCommerce status changes
            // tmp                 array(
            // tmp                     'name' => 'entry_status',
            // tmp                     'type' => 'repeater',
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Update entry status when WooCommerce Order status changes', 'super-forms' ),
            // tmp                     'nodes' => array( // repeater item
            // tmp                         array(
            // tmp                             'inline' => true,
            // tmp                             'padding' => false,
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'order',
            // tmp                                     'title' => 'Order status',
            // tmp                                     'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. completed'
            // tmp                                 ),
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'entry',
            // tmp                                     'title' => 'Entry status',
            // tmp                                     'subline' => esc_html__( 'Leave blank or delete to keep the current entry status unchanged. Accepted values are:', 'super-forms' ). ' ' . $entryStatusesCode . '. ' . sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ),
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. completed'
            // tmp                                 ),
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp                 // Update post status when WooCommerce status changes
            // tmp                 array(
            // tmp                     'name' => 'post_status',
            // tmp                     'type' => 'repeater',
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Update post status when WooCommerce Order status changes', 'super-forms' ),
            // tmp                     'nodes' => array( // repeater item
            // tmp                         array(
            // tmp                             'inline' => true,
            // tmp                             'padding' => false,
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'order',
            // tmp                                     'title' => 'Order status',
            // tmp                                     'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. completed'
            // tmp                                 ),
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'post',
            // tmp                                     'title' => 'Post status',
            // tmp                                     'subline' => esc_html__( 'Leave blank or delete to keep the current post status unchanged. Accepted values are:', 'super-forms' ). ' ' . $postStatusesCode . '.',
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. publish'
            // tmp                                 ),
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp                 // Update login status when WooCommerce status changes
            // tmp                 array(
            // tmp                     'name' => 'login_status',
            // tmp                     'type' => 'repeater',
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Update user login status when WooCommerce Order status changes', 'super-forms' ),
            // tmp                     'nodes' => array( // repeater item
            // tmp                         array(
            // tmp                             'inline' => true,
            // tmp                             'padding' => false,
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'order',
            // tmp                                     'title' => 'Order status',
            // tmp                                     'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. completed'
            // tmp                                 ),
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'login_status',
            // tmp                                     'title' => 'User login status',
            // tmp                                     'subline' => esc_html__( 'Leave blank or delete to keep the current user status unchanged. Accepted values are:', 'super-forms' ). ' <code>active</code>, <code>pending</code>, <code>payment_required</code>, <code>blocked</code>.',
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. active'
            // tmp                                 ),
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),

            // tmp                 // Update user role when WooCommerce status changes
            // tmp                 array(
            // tmp                     'name' => 'user_role',
            // tmp                     'type' => 'repeater',
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Update user role when WooCommerce Order status changes', 'super-forms' ),
            // tmp                     'nodes' => array( // repeater item
            // tmp                         array(
            // tmp                             'inline' => true,
            // tmp                             'padding' => false,
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'order',
            // tmp                                     'title' => 'Order status',
            // tmp                                     'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. completed'
            // tmp                                 ),
            // tmp                                 array(
            // tmp                                     'vertical' => true, // sfui-vertical
            // tmp                                     'name' => 'user_role',
            // tmp                                     'title' => 'User role',
            // tmp                                     'subline' => esc_html__( 'Leave blank or delete to keep the current user role unchanged. Accepted values are:', 'super-forms' ). ' ' . $rolesCode . '.',
            // tmp                                     'type' => 'text',
            // tmp                                     'default' => '',
            // tmp                                     'placeholder' => 'e.g. subscriber'
            // tmp                                 ),
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),

            // tmp                 array(
            // tmp                     'toggle' => true,
            // tmp                     'title' => esc_html__( 'Send email after payment completed', 'super-forms' ),
            // tmp                     'nodes' => array(
            // tmp                         array(
            // tmp                             'notice' => 'info', // hint/info
            // tmp                             'content' => 'To send an email after a WooCommerce order is completed, you can create a new action under the Triggers tab.',
            // tmp                             'filter' => 'checkout;true'
            // tmp                             //'width_auto' => false, // 'sfui-width-auto'
            // tmp                             //'wrap' => false,
            // tmp                             //'group' => true, // sfui-setting-group
            // tmp                             //'group_name' => 'emails',
            // tmp                             //'inline' => true, // sfui-inline
            // tmp                             //'vertical' => true, // sfui-vertical
            // tmp                             // tmp 'nodes' => array(
            // tmp                             // tmp     array(
            // tmp                             // tmp         'name' => 'status',
            // tmp                             // tmp         'type' => 'text',
            // tmp                             // tmp         'default' => '',
            // tmp                             // tmp         'title' => esc_html__( 'When order status changes to', 'super-forms' ),
            // tmp                             // tmp     ),
            // tmp                             // tmp     array(
            // tmp                             // tmp         'sub' => true, // sfui-sub-settings
            // tmp                             // tmp         'filter' => 'status;completed',
            // tmp                             // tmp         'nodes' => array(
            // tmp                             // tmp             array(
            // tmp                             // tmp                 'name' => 'to',
            // tmp                             // tmp                 'type' => 'text',
            // tmp                             // tmp                 'default' => '',
            // tmp                             // tmp                 'title' => esc_html__( 'To:', 'super-forms' ),
            // tmp                             // tmp             ),
            // tmp                             // tmp         )
            // tmp                             // tmp     )
            // tmp                             // tmp )
            // tmp                         )
            // tmp                     )
            // tmp                 ),


            // tmp                 array(
            // tmp                     'vertical' => true, // sfui-vertical
            // tmp                     'name' => 'redirect',
            // tmp                     'title' => 'Redirect to:',
            // tmp                     'subline' => 'Redirect to Checkout, Cart or use form redirect. Accepted values: <code>checkout</code>, <code>cart</code> or <code>none</code>',
            // tmp                     'type' => 'text',
            // tmp                     'default' => 'checkout'
            // tmp                 ),
            // tmp                 array(
            // tmp                     'inline' => true, // sfui-inline
            // tmp                     'name' => 'empty_cart',
            // tmp                     'type' => 'checkbox',
            // tmp                     'default' => 'false',
            // tmp                     'title' => esc_html__( 'Empty cart before adding products', 'super-forms' ),
            // tmp                 ),
            // tmp                 array(
            // tmp                     'inline' => true, // sfui-inline
            // tmp                     'name' => 'remove_fees',
            // tmp                     'type' => 'checkbox',
            // tmp                     'default' => 'false',
            // tmp                     'title' => esc_html__( 'Remove/clear fees before redirecting to checkout/cart', 'super-forms' ),
            // tmp                 ),
            // tmp                 array(
            // tmp                     'inline' => true, // sfui-inline
            // tmp                     'name' => 'remove_coupons',
            // tmp                     'type' => 'checkbox',
            // tmp                     'default' => 'false',
            // tmp                     'title' => esc_html__( 'Remove/clear coupons before redirecting to checkout/cart', 'super-forms' ),
            // tmp                 ),
            // tmp                 array(
            // tmp                     'vertical' => true, // sfui-vertical
            // tmp                     'name' => 'coupon',
            // tmp                     'type' => 'text',
            // tmp                     'placeholder' => 'e.g. {coupon_code}',
            // tmp                     'default' => '',
            // tmp                     'title' => esc_html__( 'Apply a coupon code', 'super-forms' )
            // tmp                 ),

            // tmp             )
            // tmp         )
            // tmp     )
            // tmp ),
            // tmp array(
            // tmp     'name' => 'instant',
            // tmp     'type' => 'checkbox',
            // tmp     'default' => 'false',
            // tmp     'title' => esc_html__( 'Enable WooCommerce Instant Order', 'super-forms' ),
            // tmp     'nodes' => array(
            // tmp         array(
            // tmp             'sub' => true, // sfui-sub-settings
            // tmp             'filter' => 'instant;true',
            // tmp             'nodes' => array(
            // tmp                 array(
            // tmp                     'wrap' => false,
            // tmp                     'group' => true, // sfui-setting-group
            // tmp                     'group_name' => 'instant_conditionally',
            // tmp                     'inline' => true, // sfui-inline
            // tmp                     'filter' => 'instant;true',
            // tmp                     'nodes' => array(
            // tmp                         array(
            // tmp                             'name' => 'enabled',
            // tmp                             'type' => 'checkbox',
            // tmp                             'default' => 'false',
            // tmp                             'title' => esc_html__( 'Only create the order when below condition is met', 'super-forms' ),
            // tmp                             'nodes' => array(
            // tmp                                 array(
            // tmp                                     'inline' => true, // sfui-inline
            // tmp                                     'filter' => 'instant_conditionally.enabled;true',
            // tmp                                     'nodes' => array(
            // tmp                                         array(
            // tmp                                             'wrap' => false,
            // tmp                                             'name' => 'f1',
            // tmp                                             'inline' => true,
            // tmp                                             'padding' => false,
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. {tag}',
            // tmp                                         ),
            // tmp                                         array(
            // tmp                                             'wrap' => false,
            // tmp                                             'name' => 'logic',
            // tmp                                             'padding' => false,
            // tmp                                             'type' => 'select', // dropdown
            // tmp                                             'options' => array(
            // tmp                                                 '==' => '== Equal',
            // tmp                                                 '!=' => '!= Not equal',
            // tmp                                                 '??' => '?? Contains',
            // tmp                                                 '!!' => '!! Not contains',
            // tmp                                                 '>'  => '&gt; Greater than',
            // tmp                                                 '<'  => '&lt;  Less than',
            // tmp                                                 '>=' => '&gt;= Greater than or equal to',
            // tmp                                                 '<=' => '&lt;= Less than or equal'
            // tmp                                             ),
            // tmp                                             'default' => '',
            // tmp                                         ),
            // tmp                                         array(
            // tmp                                             'wrap' => false,
            // tmp                                             'name' => 'f2',
            // tmp                                             'inline' => true,
            // tmp                                             'padding' => false,
            // tmp                                             'type' => 'text',
            // tmp                                             'default' => '',
            // tmp                                             'placeholder' => 'e.g. true'
            // tmp                                         )
            // tmp                                     )
            // tmp                                 )
            // tmp                             )
            // tmp                         )
            // tmp                     )
            // tmp                 ),
            // tmp             )
            // tmp         )
            // tmp     )
            // tmp ),
        );
        $s = array('triggers' => $triggers);
        $prefix = array();
        SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);

        //SUPER_UI::loop_over_tab_setting_nodes($triggers, $nodes, $prefix);

        // tmp disabled // tmp $triggers = SUPER_Common::get_form_triggers($atts['form_id']);
        // tmp disabled // tmp if(count($triggers)===0) {
        // tmp disabled // tmp     $triggers[] = self::get_default_trigger_settings(array());
        // tmp disabled // tmp }
        // tmp disabled echo '<div class="sfui-notice sfui-desc">';
        // tmp disabled     echo '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . esc_html__('With triggers you can execute specific actions based on events that occur on your WordPress site.', 'super-forms');
        // tmp disabled echo '</div>';
        // tmp disabled // Enable listings
        // tmp disabled echo '<div class="sfui-setting">';
        // tmp disabled     //echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled     //    echo '<input type="checkbox" name="enabled" value="true"' . ($enabled==='true' ? ' checked="checked"' : '') . ' />';
        // tmp disabled     //    echo '<span class="sfui-title">' . esc_html__( 'Enable triggers for this form', 'super-forms' ) . '</span>';
        // tmp disabled     //echo '</label>';
        // tmp disabled     //echo '<div class="sfui-sub-settings">';
        // tmp disabled         // When enabled, we display the list with listings
        // tmp disabled         echo '<div class="sfui-repeater" data-k="triggers">';
        // tmp disabled         // Repeater Item
        // tmp disabled         $events = array(
        // tmp disabled             array(
        // tmp disabled                 'label' => 'Form Events',
        // tmp disabled                 'items' => array(
        // tmp disabled                     'sf.before.submission' => 'sf.before.submission',
        // tmp disabled                     'sf.after.submission' => 'sf.after.submission',
        // tmp disabled                     'sf.submission.validation' => 'sf.submission.validation'
        // tmp disabled                 )
        // tmp disabled             ),
        // tmp disabled             array(
        // tmp disabled                 'label' => 'Stripe',
        // tmp disabled                 'items' => array(
        // tmp disabled                     'stripe.checkout.session.completed' => 'stripe.checkout.session.completed',
        // tmp disabled                     'stripe.checkout.session.async_payment_failed' => 'stripe.checkout.session.async_payment_failed',
        // tmp disabled                     'stripe.fulfill_order' => 'stripe.fulfill_order'
        // tmp disabled                 )
        // tmp disabled             )
        // tmp disabled         );
        // tmp disabled         $actions = array(
        // tmp disabled             'send_email' => 'Send an E-mail',
        // tmp disabled             'insert_db_row' => 'Insert row to database table',
        // tmp disabled             'validate_field' => 'Validate field value',
        // tmp disabled             'create_post' => 'Create a post/page/product'
        // tmp disabled         );
        // tmp disabled         foreach($triggers as $k => $v){
        // tmp disabled             // Set default values if they don't exist
        // tmp disabled             $v = self::get_default_trigger_settings($v);
        // tmp disabled             echo '<div class="sfui-repeater-item">';
        // tmp disabled                 echo '<div class="sfui-setting sfui-inline">';
        // tmp disabled                     // 1. Trigger - Choose an event
        // tmp disabled                     // [name] - [description] - [execution_order]
        // tmp disabled                     echo '<div class="sfui-setting sfui-inline sfui-width-auto">';
        // tmp disabled                         echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled                             echo '<input type="checkbox" name="active" value="true"' . ($v['active']==='true' ? ' checked="checked"' : '') . ' />';
        // tmp disabled                             echo '<span class="sfui-title">' . esc_html__( 'Enabled', 'super-forms' ) . '</span>';
        // tmp disabled                         echo '</label>';
        // tmp disabled                     echo '</div>';
        // tmp disabled                     // 2. Event - Choose an event that triggers your action (this is what starts executing the action)
        // tmp disabled                     echo '<div class="sfui-setting sfui-vertical sfui-width-auto">';
        // tmp disabled                         echo '<label>';
        // tmp disabled                             echo '<select name="event" onChange="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled                                 echo '<option value=""'.($v['event']==='' ? ' selected="selected"' : '').'>- choose an event -</option>';
        // tmp disabled                                 $hadLabel = false;
        // tmp disabled                                 foreach($events as $ek => $ev){
        // tmp disabled                                     if(isset($ev['label'])){
        // tmp disabled                                         $hadLabel = true;
        // tmp disabled                                         echo '<optgroup label="'.$ev['label'].'">';
        // tmp disabled                                     }
        // tmp disabled                                     $count = 0;
        // tmp disabled                                     foreach($ev['items'] as $eek => $eev){
        // tmp disabled                                         echo '<option value="'.$eek.'"'.($v['event']===$eek ? ' selected="selected"' : '').'>'.$eev.'</option>';
        // tmp disabled                                         $count++;
        // tmp disabled                                         if(count($ev['items'])===$count){
        // tmp disabled                                             echo '</optgroup>';
        // tmp disabled                                         }
        // tmp disabled                                     }
        // tmp disabled                                     //foreach($ev['items'] as $eek => $eev){
        // tmp disabled                                     //var_dump($ev);
        // tmp disabled                                     //// form_events
        // tmp disabled                                     //// - label

        // tmp disabled                                     //// webhooks
        // tmp disabled                                     //// - label
        // tmp disabled                                     //$i = 0;
        // tmp disabled                                     //foreach($ev['items'] as $eek => $eev){
        // tmp disabled                                     //    if(!isset($eev['items']) ){
        // tmp disabled                                     //        if($i===0) {
        // tmp disabled                                     //            echo '<optgroup label="'.$ev['label'].'">';
        // tmp disabled                                     //        }
        // tmp disabled                                     //        echo '<option value="'.$eek.'"'.($v['event']===$eek ? ' selected="selected"' : '').'>'.$eev.'</option>';
        // tmp disabled                                     //    }
        // tmp disabled                                     //    if(isset($eev['label'])){
        // tmp disabled                                     //        echo '<optgroup label="'.$ev['label'].' > '.$eev['label'].'">'; // Webhooks > Stripe
        // tmp disabled                                     //    }
        // tmp disabled                                     //    foreach($eev['items'] as $eeek => $eeev){ 
        // tmp disabled                                     //        echo '<option value="'.$eeek.'"'.($v['event']===$eeek ? ' selected="selected"' : '').'>'.$eeev.'</option>';
        // tmp disabled                                     //    }
        // tmp disabled                                     //    if(!isset($eev['items']) && $i===(count($ev['items']))){
        // tmp disabled                                     //        echo '</optgroup>';
        // tmp disabled                                     //    }else{
        // tmp disabled                                     //        echo '</optgroup>';
        // tmp disabled                                     //    }
        // tmp disabled                                     //    $i++;
        // tmp disabled                                     //}
        // tmp disabled                                     //echo '</optgroup>';
        // tmp disabled                                 }
        // tmp disabled                             echo '</select>';
        // tmp disabled                             echo '<span class="sfui-label"><i>' . esc_html__( 'Choose an event that will trigger your action(s)', 'super-forms' ) . '</i></span>';
        // tmp disabled                         echo '</label>';
        // tmp disabled                     echo '</div>';
        // tmp disabled                     echo '<div class="sfui-setting sfui-vertical sfui-width-auto">';
        // tmp disabled                         echo '<label>';
        // tmp disabled                             echo '<input type="text" name="name" value="' . $v['name'] . '" />';
        // tmp disabled                             echo '<span class="sfui-label"><i>' . esc_html__( 'Trigger name', 'super-forms' ) . '</i></span>';
        // tmp disabled                         echo '</label>';
        // tmp disabled                     echo '</div>';
        // tmp disabled                     echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                         echo '<label>';
        // tmp disabled                             echo '<input type="text" name="desc" placeholder="'.esc_html__( 'Describe what this trigger does...', 'super-forms' ).'" value="' . $v['desc'] . '" />';
        // tmp disabled                             echo '<span class="sfui-label"><i>' . esc_html__( 'Description (to remember what it does)', 'super-forms' ) . '</i></span>';
        // tmp disabled                         echo '</label>';
        // tmp disabled                     echo '</div>';

        // tmp disabled                     echo '<div class="sfui-setting sfui-vertical sfui-width-auto">';
        // tmp disabled                         echo '<label>';
        // tmp disabled                             echo '<select name="listen_to" onChange="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled                                 echo '<option value=""'.($v['listen_to']==='' ? ' selected="selected"' : '').'>'.esc_html__('Current form','super-forms').' ('.esc_html('default','super-forms').')</option>';
        // tmp disabled                                 echo '<option value="all"'.($v['listen_to']==='all' ? ' selected="selected"' : '').'>'.esc_html__('All forms','super-forms').' ('.esc_html__('globally','super-forms').')</option>';
        // tmp disabled                                 echo '<option value="id"'.($v['listen_to']==='id' ? ' selected="selected"' : '').'>'.esc_html__('Specific forms only','super-forms').'</option>';
        // tmp disabled                             echo '</select>';
        // tmp disabled                             echo '<span class="sfui-label"><i>' . esc_html__( 'Trigger for the specified form(s)', 'super-forms' ) . '</i></span>';
        // tmp disabled                         echo '</label>';
        // tmp disabled                     echo '</div>';
        // tmp disabled                     echo '<div class="sfui-setting sfui-vertical sfui-width-auto" data-f="listen_to;id">';
        // tmp disabled                         echo '<label>';
        // tmp disabled                             echo '<input type="text" name="listen_to_ids" value="' . $v['listen_to_ids'] . '" />';
        // tmp disabled                             echo '<span class="sfui-label"><i>' . esc_html__( 'Separate each form ID with a comma', 'super-forms' ) . '</i></span>';
        // tmp disabled                         echo '</label>';
        // tmp disabled                     echo '</div>';

        // tmp disabled                     echo '<div class="sfui-setting sfui-vertical sfui-width-auto">';
        // tmp disabled                         echo '<label>';
        // tmp disabled                             echo '<input type="number" name="order" value="' . $v['order'] . '" />';
        // tmp disabled                             echo '<span class="sfui-label"><i>' . esc_html__( 'Execution order (low number executes first)', 'super-forms' ) . '</i></span>';
        // tmp disabled                         echo '</label>';
        // tmp disabled                     echo '</div>';
        // tmp disabled                     echo '<div class="sfui-btn sfui-round sfui-tooltip" title="' . esc_html__('Change Settings', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'toggleRepeaterSettings\')"><i class="fas fa-cogs"></i></div>';
        // tmp disabled                     echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add trigger', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
        // tmp disabled                     echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_html__('Delete trigger', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
        // tmp disabled                 echo '</div>';

        // tmp disabled                 echo '<div class="sfui-setting-group">';
        // tmp disabled                     echo '<div class="sfui-setting" data-f="event;">';
        // tmp disabled                         echo '<div class="sfui-notice sfui-info">';
        // tmp disabled                             echo '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . esc_html__('To define actions, you must first choose an event for the trigger above.', 'super-forms');
        // tmp disabled                         echo '</div>';
        // tmp disabled                     echo '</div>';
        // tmp disabled                     // Hide listing to specific user role/ids
        // tmp disabled                     echo '<div class="sfui-setting sfui-inline" data-f="event;!">';
        // tmp disabled                         echo '<div class="sfui-sub-settings">';
        // tmp disabled                             echo '<div class="sfui-repeater" data-k="actions">';
        // tmp disabled                                 // Loop over actions for this event
        // tmp disabled                                 if(!isset($v['actions'])) $v['actions'] = array(array('action'=>''));
        // tmp disabled                                 foreach($v['actions'] as $ik => $iv){
        // tmp disabled                                     $iv = array_merge(
        // tmp disabled                                         array(
        // tmp disabled                                             'action'=>'', 
        // tmp disabled                                             'conditionally'=>'', 
        // tmp disabled                                             'logic'=>'', 
        // tmp disabled                                             'f1'=>'', 
        // tmp disabled                                             'f2'=>'', 
        // tmp disabled                                             'to'=>'', 
        // tmp disabled                                             'from'=>'', 
        // tmp disabled                                             'reply_to'=>'', 
        // tmp disabled                                             'subject'=>'',
        // tmp disabled                                             'body'=>'', 
        // tmp disabled                                             //'line_breaks'=>'true',  not used due to tinyMCE
        // tmp disabled                                             'cc'=>'', 
        // tmp disabled                                             'bcc'=>'', 
        // tmp disabled                                             'headers'=>'',
        // tmp disabled                                             'content_type'=>'html',
        // tmp disabled                                             'charset'=>'UTF-8'
        // tmp disabled                                         ), 
        // tmp disabled                                         $iv
        // tmp disabled                                     );

        // tmp disabled                                     echo '<div class="sfui-repeater-item">';
        // tmp disabled                                         echo '<div class="sfui-setting sfui-inline" style="flex:1;">';
        // tmp disabled                                             // 3. Action - The action that is executed/performed
        // tmp disabled                                             // [+] Add another action
        // tmp disabled                                             echo '<div class="sfui-setting sfui-vertical sfui-width-auto">';
        // tmp disabled                                                 echo '<label>';
        // tmp disabled                                                     echo '<select name="action" onChange="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled                                                     echo '<option value=""'.($iv['action']==='' ? ' selected="selected"' : '').'>- choose an action -</option>';
        // tmp disabled                                                     foreach($actions as $ak => $av){
        // tmp disabled                                                         echo '<option value="'.$ak.'"'.($iv['action']===$ak ? ' selected="selected"' : '').'>'.$av.'</option>';
        // tmp disabled                                                     }
        // tmp disabled                                                     echo '</select>';
        // tmp disabled                                                     echo '<span class="sfui-label">' . esc_html__('The action to perform when the event is triggered', 'super-forms') . '</span>';
        // tmp disabled                                                 echo '</label>';
        // tmp disabled                                                 echo '<div class="sfui-setting sfui-inline sfui-no-padding">';
        // tmp disabled                                                     echo '<div class="sfui-btn sfui-round sfui-tooltip" title="' . esc_html__('Change action settings', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'toggleRepeaterSettings\')"><i class="fas fa-cogs"></i></div>';
        // tmp disabled                                                     //echo '<div class="sfui-btn sfui-blue sfui-round sfui-tooltip" title="' . esc_html__('Conditional logic', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'toggleConditionSettings\')"><i class="fas fa-arrows-split-up-and-left"></i></div>';
        // tmp disabled                                                     echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add action', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
        // tmp disabled                                                     echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_html__('Delete action', 'super-forms' ) . '" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
        // tmp disabled                                                 echo '</div>';
        // tmp disabled                                             echo '</div>';
        // tmp disabled                                             echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                 echo '<div class="sfui-setting-group sfui-vertical" data-f="action;!">';
        // tmp disabled                                                     echo '<div class="sfui-setting-group sfui-inline" data-f="action;!">';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-no-padding sfui-width-auto">';
        // tmp disabled                                                             echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled                                                                 echo '<input type="checkbox" name="conditionally" value="true"' . ($iv['conditionally']==='true' ? ' checked="checked"' : '') . ' />';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__( 'Execute conditionally', 'super-forms' ) . ' (' . esc_html__( 'optional', 'super-forms' ) .')</span>';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-no-padding sfui-inline" data-f="conditionally;true">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<input type="text" name="f1" placeholder="{field}" value="' . $iv['f1'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<select name="logic">';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='' ?   ' selected="selected"' : '').' selected="selected" value="">---</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='==' ? ' selected="selected"' : '').' value="==">== Equal</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='!=' ? ' selected="selected"' : '').' value="!=">!= Not equal</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='??' ? ' selected="selected"' : '').' value="??">?? Contains</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='!!' ? ' selected="selected"' : '').' value="!!">!! Not contains</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='>' ?  ' selected="selected"' : '').' value=">">&gt; Greater than</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='<' ?  ' selected="selected"' : '').' value="<">&lt;  Less than</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='>=' ? ' selected="selected"' : '').' value=">=">&gt;= Greater than or equal to</option>';
        // tmp disabled                                                                     echo '<option'.($iv['logic']==='<=' ? ' selected="selected"' : '').' value="<=">&lt;= Less than or equal</option>';
        // tmp disabled                                                                 echo '</select>';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<input type="text" name="f2" placeholder="'.esc_html__( 'Comparison value', 'super-forms' ).'" value="' . $iv['f2'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                     echo '</div>';
        // tmp disabled                                                     echo '<div class="sfui-sub-settings" data-f="action;send_email">';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('To', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<input type="text" name="to" value="' . $iv['to'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('From', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<input type="text" name="from" value="' . $iv['from'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('Reply-To', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<input type="text" name="reply_to" value="' . $iv['reply_to'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('Subject', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<input type="text" name="subject" value="' . $iv['subject'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('Body', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<textarea name="body" id="'.($k.'-'.$ik.'-'.$iv['action']).'-body" class="sfui-textarea-tinymce">'.esc_textarea(wp_unslash($iv['body'])).'</textarea>';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         // not needed due to tinyMCE echo '<div class="sfui-setting sfui-no-padding">';
        // tmp disabled                                                         // not needed due to tinyMCE     echo '<label>';
        // tmp disabled                                                         // not needed due to tinyMCE         echo '<input type="checkbox" name="line_breaks" value="true"' . ($iv['line_breaks']==='true' ? ' checked="checked"' : '') . ' />';
        // tmp disabled                                                         // not needed due to tinyMCE         echo '<span class="sfui-title">' . esc_html__( 'Enable line breaks', 'super-forms' ) . '</span>';
        // tmp disabled                                                         // not needed due to tinyMCE     echo '</label>';
        // tmp disabled                                                         // not needed due to tinyMCE echo '</div>';

        // tmp disabled                                                         // @TODO:
        // tmp disabled                                                         // 'attachments'=>array(),
        // tmp disabled                                                         // 'string_attachments'=>array()
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('Attachments', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('String attachments', 'super-forms') . ':</span>';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';

        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('CC', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<input type="text" name="cc" value="' . $iv['cc'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('BCC', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<input type="text" name="bcc" value="' . $iv['bcc'] . '" />';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical sfui-width-auto">';
        // tmp disabled                                                             echo '<label>';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('Content type', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<select name="content_type" onChange="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled                                                                     echo '<option value=""'.($iv['content_type']==='' ? ' selected="selected"' : '').'>- choose an event -</option>';
        // tmp disabled                                                                     echo '<option value="html"'.($iv['content_type']==='html' ? ' selected="selected"' : '').'>HTML</option>';
        // tmp disabled                                                                     echo '<option value="plain"'.($iv['content_type']==='plain' ? ' selected="selected"' : '').'>Plain text</option>';
        // tmp disabled                                                                 echo '</select>';
        // tmp disabled                                                                 echo '<span class="sfui-label"><i>' . esc_html__( 'The content type to use for this email', 'super-forms' ) . '</i></span>';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('Charset', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<input type="text" name="charset" value="' . $iv['charset'] . '" />';
        // tmp disabled                                                                 echo '<span class="sfui-label"><i>' . sprintf( esc_html__( 'The charset to use for this email.%sExample: UTF-8 or ISO-8859-1', 'super-forms' ), ' ' ) . '</i></span>';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                         echo '<div class="sfui-setting sfui-vertical">';
        // tmp disabled                                                             echo '<label class="sfui-no-padding">';
        // tmp disabled                                                                 echo '<span class="sfui-title">' . esc_html__('Additional headers', 'super-forms') . ':</span>';
        // tmp disabled                                                                 echo '<textarea name="headers">'.$iv['headers'].'</textarea>';
        // tmp disabled                                                             echo '</label>';
        // tmp disabled                                                         echo '</div>';
        // tmp disabled                                                     echo '</div>';
        // tmp disabled                                                 echo '</div>';
        // tmp disabled                                             echo '</div>';
        // tmp disabled                                         echo '</div>';
        // tmp disabled                                     echo '</div>';
        // tmp disabled                                 }
        // tmp disabled                             echo '</div>';
        // tmp disabled                         echo '</div>';
        // tmp disabled                     echo '</div>';

        // tmp disabled                     //// Custom columns
        // tmp disabled                     //echo '<div class="sfui-setting">';
        // tmp disabled                     //    echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
        // tmp disabled                     //        echo '<input type="checkbox" name="custom_columns.enabled" value="true"' . ($v['custom_columns']['enabled']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Show the following "Custom" columns', 'super-forms' ) . ':</span>';
        // tmp disabled                     //        echo '<div class="sfui-sub-settings" data-f="custom_columns.enabled;true">';
        // tmp disabled                     //            echo '<div class="sfui-repeater" data-k="custom_columns.columns">';
        // tmp disabled                     //                // Repeater Item
        // tmp disabled                     //                $columns = $v['custom_columns']['columns'];
        // tmp disabled                     //                foreach( $columns as $ck => $cv ) {
        // tmp disabled                     //                    echo '<div class="sfui-repeater-item">';
        // tmp disabled                     //                        echo '<div class="sfui-inline sfui-vertical">';
        // tmp disabled                     //                            self::getColumnSettingFields($v, '', $ck, $cv);
        // tmp disabled                     //                            echo '<div class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Add item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
        // tmp disabled                     //                            echo '<div class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
        // tmp disabled                     //                        echo '</div>';
        // tmp disabled                     //                    echo '</div>';
        // tmp disabled                     //                }
        // tmp disabled                     //            echo '</div>';
        // tmp disabled                     //        echo '</div>';
        // tmp disabled                     //    echo '</label>';
        // tmp disabled                     //echo '</div>';

        // tmp disabled                 echo '</div>';
        // tmp disabled             echo '</div>';
        // tmp disabled         }
        // tmp disabled         echo '</div>';
        // tmp disabled     //echo '</div>';
        // tmp disabled echo '</div>';
    }


    /**
     * Handles the output for the create form page in admin
     */
    public static function create_form() {
        include_once( 'class-ui.php' );
        // Get all Forms created with Super Forms (post type: super_form)
        $args = array(
            'post_type' => 'super_form', //We want to retrieve all the Forms
            'posts_per_page' => -1 //Make sure all matching forms will be retrieved
        );
        $forms = get_posts( $args );

        // Check if we are editing an existing Form
        if( isset( $_GET['id'] ) ) {
            $form_id = absint( $_GET['id'] );
            $title = get_the_title( $form_id );
            // @since 3.1.0 - get all Backups for this form.
            $args = array(
                'post_parent' => $form_id,
                'post_type' => 'super_form',
                'post_status' => 'backup',
                'posts_per_page' => -1 //Make sure all matching backups will be retrieved
            );
            $backups = get_posts( $args );
        }else{
            $form_id = 0;
            $title = esc_html__( 'Form Name', 'super-forms' );
        }
        $settings = SUPER_Common::get_form_settings($form_id);

        // Retrieve all form setting fields with the correct default values
        $fields = SUPER_Settings::fields( $settings );

        // Get all available shortcodes
        $shortcodes = SUPER_Shortcodes::shortcodes();
        
        // @since 4.7.0 - translations
        //$triggers = SUPER_Common::get_form_triggers($form_id);

        // @since 4.7.0 - translations
        $translations = SUPER_Common::get_form_translations($form_id);

        // @since 4.9.6 - secrets
        $localSecrets = get_post_meta($form_id, '_super_local_secrets', true);
        $globalSecrets = get_option( 'super_global_secrets' );
        $version = get_post_meta( $form_id, '_super_version', true );

        // Include the file that handles the view
        include_once( SUPER_PLUGIN_DIR . '/includes/admin/views/page-create-form.php' );
       
    }


    /**
     * List of all the demo forms & community forms
     */
    public static function demos() {
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_style( 'thickbox' );  
        include_once( SUPER_PLUGIN_DIR . '/includes/admin/views/page-demos.php' );
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
        $entry_id = $_GET['id'];
        if ( (FALSE === get_post_status($entry_id)) && (get_post_type($entry_id)!='super_contact_entry') ) {
            // The post does not exist
            echo 'This contact entry does not exist.';
        } else {
            if(get_post_status($entry_id)!=='super_read'){
                $my_post = array(
                    'ID' => $entry_id,
                    'post_status' => 'super_read',
                );
                wp_update_post($my_post);
			}
            $date = get_the_date(false,$entry_id);
            $time = get_the_time(false,$entry_id);
            $ip = get_post_meta($entry_id, '_super_contact_entry_ip', true);
            $entry_status = get_post_meta($entry_id, '_super_contact_entry_status', true);
            $global_settings = SUPER_Common::get_global_settings();
            $data = get_post_meta($_GET['id'], '_super_contact_entry_data', true);
            if(is_array($data)){
                foreach($data as $k => $v){
                    if( (isset($v['type'])) && (
                        ($v['type']=='varchar') || 
                        ($v['type']=='var') || 
                        ($v['type']=='text') || 
                        ($v['type']=='html') || 
                        ($v['type']=='google_address') || 
                        ($v['type']=='field') || 
                        ($v['type']=='barcode') || 
                        ($v['type']=='files')) ) {
                        $data['fields'][] = $v;
                    }elseif((isset($v['type'])) && ($v['type']=='form_id')){
                        $data['form_id'][] = $v;
                    }
                }
            }
                                    
            // @since 3.4.0  - custom contact entry status
            $statuses = SUPER_Settings::get_entry_statuses($global_settings);
            $entry_title = esc_html(get_the_title($entry_id));
            $page_title = esc_html($entry_title). ' ‹ '. get_bloginfo('name') . ' — WordPress';
            ?>
            <script>
                document.title = '<?php echo $page_title; ?>';
                jQuery('.toplevel_page_super_forms').removeClass('wp-not-current-submenu').addClass('wp-menu-open wp-has-current-submenu');
                jQuery('.toplevel_page_super_forms').find('li:eq(4)').addClass('current');

            </script>
            <div class="wrap">

                <div id="poststuff">

                    <div id="titlediv" style="margin-bottom:10px;">
                        <div id="titlewrap">
                            <input placeholder="<?php _e( 'Contact Entry Title', 'super-forms' ); ?>" type="text" name="super_contact_entry_post_title" size="30" value="<?php echo $entry_title; ?>" id="title" spellcheck="true" autocomplete="false">
                        </div>
                    </div>

                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="postbox-container-1" class="postbox-container">
                            <div>
                                <div id="submitdiv" class="postbox">
                                    <div class="inside">
                                        <div class="submitbox" id="submitpost">
                                            <div id="minor-publishing">
                                                <h3 style="margin:0;padding:20px 10px;"><span><?php echo esc_html__('Lead Details', 'super-forms' ); ?>:</span></h3>
                                                <div class="misc-pub-section">
                                                    <span><?php echo esc_html__('Submitted', 'super-forms' ).':'; ?> <strong><?php echo $date.' @ '.$time; ?></strong></span>
                                                </div>
                                                <div class="misc-pub-section">
                                                    <span><?php echo esc_html__('IP-address', 'super-forms' ).':'; ?> <strong><?php if(empty($ip)){ echo esc_html__('Unknown', 'super-forms' ); }else{ echo $ip; } ?></strong></span>
                                                </div>
                                                <div class="misc-pub-section">
                                                    <?php echo '<span>' . esc_html__('Based on Form', 'super-forms' ) . ': <strong><a href="' . esc_url('admin.php?page=super_create_form&id=' . $data['form_id'][0]['value']) . '">' . get_the_title( $data['form_id'][0]['value'] ) . '</a></strong></span>'; ?>
                                                </div>
                                                <?php
                                                if(SUPER_WC_ACTIVE){
                                                    $wc_order_id = get_post_meta($entry_id, '_super_contact_entry_wc_order_id', true);
                                                    if(!empty($wc_order_id)){
                                                        ?>
                                                        <div class="misc-pub-section">
                                                            <span><?php echo esc_html__('WooCommerce Order', 'super-forms' ).':'; ?> <strong><?php echo '<a href="' . esc_url(get_edit_post_link($wc_order_id,'')).'">#'.$wc_order_id.'</a>'; ?></strong></span>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                                do_action( 'after_contact_entry_metabox_hook', $entry_id );
                                                $post_author_id = get_post_field( 'post_author', $entry_id );
                                                if( !empty($post_author_id) ) {
                                                    $user_info = get_userdata($post_author_id);
                                                    // In case user no longer exists
                                                    if($user_info!==false){ 
                                                        echo '<div class="misc-pub-section">';
                                                            echo '<span>' . esc_html__( 'Submitted by', 'super-forms' ) . ': <a href="' . esc_url(get_edit_user_link($user_info->ID)) . '"><strong>' . $user_info->display_name . '</strong></a></span>';
                                                        echo '</div>';
                                                    }
                                                }
                                                ?>
                                                <div class="misc-pub-section">
                                                    <?php
                                                    echo '<span>' . esc_html__('Entry status', 'super-forms' ).':&nbsp;</span>';
                                                    echo '<select name="entry_status">';
                                                    foreach($statuses as $k => $v){
                                                        echo '<option value="'.$k.'" ' . ($entry_status==$k ? 'selected="selected"' : '') . '>'.$v['name'].'</option>';
                                                    }
                                                    echo '</select>';
                                                    ?>
                                                </div>
                                                <div class="clear"></div>
                                            </div>

                                            <?php
                                            $stripe_connections = get_post_meta($entry_id, '_super_stripe_connections', true);
                                            if(is_array($stripe_connections) && count($stripe_connections)>0){
                                                ?>
                                                <div id="minor-publishing">
                                                    <h3 style="margin:0;padding-left: 10px;"><span><?php echo esc_html__('Stripe Details', 'super-forms' ); ?>:</span></h3>
                                                    <div class="misc-pub-section">
                                                        <?php
                                                        var_dump($stripe_connections);
                                                        // 'payment_intent' => $payment_intent,
                                                        // 'invoice' => $invoice,
                                                        // 'customer' => $customer,
                                                        // 'subscription' => $subscription
                                                        ?>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <?php
                                            }
                                            ?>

                                            <div id="major-publishing-actions">
                                                <div id="delete-action">
                                                    <a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo absint($entry_id); ?>" href="#"><?php echo esc_html__('Move to Trash', 'super-forms' ); ?></a>
                                                </div>
                                                <div id="publishing-action">
                                                    <span class="spinner"></span>
                                                    <input name="print" type="submit" class="super-print-contact-entry button button-large" value="<?php echo esc_html__('Print', 'super-forms' ); ?>">
                                                    <input name="save" type="submit" class="super-update-contact-entry button button-primary button-large" data-contact-entry="<?php echo absint($entry_id); ?>" value="<?php echo esc_html__('Update', 'super-forms' ); ?>">
                                                </div>
                                                <div class="clear"></div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="postbox-container-2" class="postbox-container">
                            <div>
                                <div id="super-contact-entry-data" class="postbox">
                                    <?php
                                    $shipping = 0;
                                    $currency = '';
                                    ?>
                                    <div class="inside">
                                        <?php
                                        echo '<table>';
                                            if( ( isset($data['fields']) ) && (count($data['fields'])>0) ) {
                                                foreach( $data['fields'] as $k => $v ) {
                                                    if(empty($v['label'])) $v['label'] = '';
                                                    $v['label'] = SUPER_Common::convert_field_email_label($v['label'], 0, true);
                                                    if( $v['type']=='barcode' ) {
                                                        echo '<tr><th align="right">' . $v['label'] . '</th><td>';
                                                        echo '<div class="super-barcode">';
                                                            echo '<div class="super-barcode-target"></div>';
                                                            echo '<input type="hidden" value="' . $v['value'] . '" data-barcodetype="' . $v['barcodetype'] . '" data-modulesize="' . $v['modulesize'] . '" data-quietzone="' . $v['quietzone'] . '" data-rectangular="' . $v['rectangular'] . '" data-barheight="' . $v['barheight'] . '" data-barwidth="' . $v['barwidth'] . '" />';
                                                        echo '</div>';
                                                    }else if( $v['type']=='files' ) {
                                                        if( isset( $v['files'] ) ) {
                                                            foreach( $v['files'] as $fk => $fv ) {
                                                                if( $fk==0 ) {
                                                                    $fv['label'] = SUPER_Common::convert_field_email_label($fv['label'], 0, true);
                                                                    echo '<tr class="super-file-upload"><th align="right">' . esc_html( $fv['label'] ) . '</th>';
                                                                    echo '<td><span class="super-contact-entry-data-value">';
                                                                }
                                                                $url = '';
                                                                if(isset($fv['url'])){
                                                                    $url = $fv['url'];
                                                                }
                                                                if( !empty( $fv['attachment'] ) ) { // only if file was inserted to Media Library
                                                                    $url = wp_get_attachment_url( $fv['attachment'] );
                                                                }
                                                                if($fk>0) echo '<br />';
                                                                if(!empty($url)){
                                                                    echo '<a class="super-file" target="_blank" href="' . esc_url( $url ) . '">';
                                                                }
                                                                echo esc_html( $fv['value'] ); // The filename
                                                                if(!empty($url)){
                                                                    echo '</a>';
                                                                }
                                                            }
                                                            echo '</span></td></tr>';
                                                        }else{
                                                            echo '<tr><th align="right">' . esc_html( $v['label'] ) . '</th><td><span class="super-contact-entry-data-value">';
                                                            echo '<input type="text" disabled="disabled" value="' . esc_html__( 'No files uploaded', 'super-forms' ) . '" />';
                                                            echo '</span></td></tr>';
                                                        }
                                                    }else if( ($v['type']=='varchar') || ($v['type']=='var') || ($v['type']=='field') || ($v['type']=='google_address') ) {
                                                        if( !isset($v['value']) ) $v['value'] = '';
                                                        if ( ( strpos( $v['value'], 'data:image/png;base64,') !== false ) || ( strpos( $v['value'], 'data:image/jpeg;base64,') !== false ) ) {
                                                            echo '<tr class="super-signature"><th align="right">' . esc_html( $v['label'] );
                                                            echo '</th><td><span class="super-contact-entry-data-value">';
                                                            // @IMPORTANT, escape the Data URL but make sure add it as an acceptable protocol 
                                                            // otherwise the signature will not be displayed
                                                            echo '<img src="' . esc_url( $v['value'], array( 'data' ) ) . '" />';
                                                            echo '</span></td></tr>';
                                                        }else{
                                                            echo '<tr>';
                                                            if( empty($v['label']) ) $v['label'] = '&nbsp;';
                                                            echo '<th align="right">' . esc_html( $v['label'] ) . '</th>';
                                                            echo '<td>';
                                                            echo '<span class="super-contact-entry-data-value">';
                                                            echo '<input class="super-shortcode-field" type="text" name="' . esc_attr( $v['name'] ) . '" value="' . esc_attr( $v['value'] ) . '" />';
                                                            echo '</span>';
                                                            echo '</td>';
                                                            echo '</tr>';
                                                        }
                                                    }else if( $v['type']=='text' ) {
                                                        echo '<tr>';
                                                        echo '<th align="right">' . esc_html( $v['label'] ) . '</th>';
                                                        echo '<td>';
                                                        echo '<span class="super-contact-entry-data-value">';
                                                        echo '<textarea class="super-shortcode-field" name="' . esc_attr( $v['name'] ) . '">' . esc_html( $v['value'] ) . '</textarea>';
                                                        echo '</span>';
                                                        echo '</td>';
                                                        echo '</tr>';
                                                    }else if( $v['type']=='html' ) {
                                                        echo '<tr>';
                                                        echo '<th align="right">' . esc_html( $v['label'] ) . '</th>';
                                                        echo '<td style="vertical-align:top;padding:6px 5px 5px 0px;">' . $v['value'] . '</td>';
                                                        echo '</tr>';
                                                    }
                                                }
                                            }
                                            echo '<input type="hidden" class="super-shortcode-field" name="form_id" value="' . absint($data['form_id'][0]['value']) . '" />';

                                            echo apply_filters( 'super_after_contact_entry_data_filter', '', array( 'entry_id'=>$_GET['id'], 'data'=>$data ) );

                                        echo '</table>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /post-body -->
                    <br class="clear">
                </div>
            <?php
        }
    }    
}
endif;
