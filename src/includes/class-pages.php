<?php
/**
 * Callbacks to generate pages
 *
 * @author      WebRehab
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

        echo '<div class="super-raw-code-emails-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sEmails settings:%s', 'super-forms' ), '<strong>', '</strong>' );
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
        
        echo '<div class="super-raw-code-theme-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sTheme settings:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';
        
        echo '<div class="super-raw-code-trigger-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sTrigger settings:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';

        echo '<div class="super-raw-code-woocommerce-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sWooCommerce settings:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';

        echo '<div class="super-raw-code-listings-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sListings settings:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';

        echo '<div class="super-raw-code-pdf-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sPDF settings:%s', 'super-forms' ), '<strong>', '</strong>' );
            echo '</p>';
            echo '<textarea></textarea>';
        echo '</div>';

        echo '<div class="super-raw-code-stripe-settings">';
            echo '<p class="sfui-notice sfui-yellow">';
            echo sprintf( esc_html__( '%sStripe settings:%s', 'super-forms' ), '<strong>', '</strong>' );
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
        error_log($settings['i18n_switch']);
        if(empty($settings['i18n_disable_browser_translation'])) $settings['i18n_disable_browser_translation'] = 'true';
        if(empty($settings['i18n_switch'])) $settings['i18n_switch'] = 'false';
        ?>
        <div class="super-setting">
            <?php
            error_log(json_encode($settings));
            error_log($settings['i18n_switch']);
            ?>
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
    public static function emails_tab($atts){
        error_log('emails_tab()');
        error_log(json_encode($atts));
        $form_id = $atts['form_id'];
        $version = $atts['version'];
        $settings = $atts['settings'];
        $s = $atts['settings'];
        // Get emails settings
        $emails = SUPER_Common::get_form_emails_settings($form_id);
        $logic = array( '==' => '== Equal', '!=' => '!= Not equal', '??' => '?? Contains', '!!' => '!! Not contains', '>'  => '&gt; Greater than', '<'  => '&lt;  Less than', '>=' => '&gt;= Greater than or equal to', '<=' => '&lt;= Less than or equal');
        $nodes = array(
            array(
                'notice' => 'hint', // hint/info
                'content' => '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . esc_html__('Make sure to define the correct From E-mail header so that it matches your domain name. If you want to send more E-mails you can define them under the [Triggers] tab.', 'super-forms')
            ),
            array(
                'name' => 'emails',
                'type' => 'repeater',
                'nodes' => array( // repeater item
                    array(
                        //'width_auto' => false, // 'sfui-width-auto'
                        'padding' => false,
                        'wrap' => false,
                        'group' => true, // sfui-setting-group
                        'group_name' => '',
                        'inline' => true, // sfui-inline
                        //'vertical' => true, // sfui-vertical
                        //'filter' => 'event;!',
                        'nodes' => array(
                            array(
                                'width_auto' => true, // 'sfui-width-auto'
                                'name' => 'enabled',
                                'title' => 'Enabled',
                                'type' => 'checkbox',
                                'default' => ''
                            ),
                            array(
                                'name' => 'description',
                                'subline' => 'Describe what kind of E-mail this is e.g. Notify customer or Notify site owner',
                                'type' => 'text',
                                'default' => 'Notification E-mail',
                                'placeholder' => 'e.g. E-mail customer that their request was submitted'
                            )
                        )
                    ),

                    //array(
                    //    'toggle' => true,
                    //    'title' => esc_html__( 'E-mail settings', 'super-forms' ),
                    //    //'notice' => 'hint', // hint/info
                    //    //'content' => sprintf( esc_html__( 'The `From email` should end with %s for E-mails to work. If you are using an email provider (Gmail, Yahoo, Outlook.com, etc) it should be the email address of that account. If you have problems with E-mail delivery you can read this guide on possible solutions: %sEmail delivery problems%s', 'super-forms' ), '<strong style="color:red;">@' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>', '<a class="sf-docs" target="_blank" href="https://docs.super-forms.com/common-problems/index/email-delivery-problems">', '</a>' ),
                    //    'nodes' => array(

                    // Split layout wrapper - forces side-by-side layout
                    array(
                        'inline' => true, // Force inline layout for side-by-side display
                        'nodes' => array(
                            // Email Settings Toggle (50% width, left side)
                            array(
                                'wrap' => false,
                                'group' => true, // sfui-setting-group
                                'group_name' => '',
                                'vertical' => true, // sfui-vertical
                                'width' => 50, // Split layout - 50% width
                                'filter' => 'enabled;true',
                                'toggle' => true,
                                'title' => esc_html__( 'E-mail settings', 'super-forms' ),
                        'nodes' => array(
                            array(
                                'width_auto' => true, // 'sfui-width-auto'
                                'wrap' => false,
                                'group' => true, // sfui-setting-group
                                'group_name' => 'conditions',
                                'inline' => true, // sfui-inline
                                //'vertical' => true, // sfui-vertical
                                //'filter' => 'enabled;true',
                                'nodes' => array(
                                    array(
                                        'name' => 'enabled',
                                        'type' => 'checkbox',
                                        'default' => 'false',
                                        'title' => esc_html__( 'Only send this E-mail when below condition is met', 'super-forms' ),
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
                                'group_name' => 'data',
                                'vertical' => true,
                                //'filter' => 'enabled;true',
                                'nodes' => array(
                                    array(
                                        'toggle' => true,
                                        'title' => esc_html__( 'E-mail headers', 'super-forms' ),
                                        'notice' => 'hint', // hint/info
                                        'content' => sprintf( esc_html__( 'The `From email` should end with %s for E-mails to work. If you are using an email provider (Gmail, Yahoo, Outlook.com, etc) it should be the email address of that account. If you have problems with E-mail delivery you can read this guide on possible solutions: %sEmail delivery problems%s', 'super-forms' ), '<strong style="color:red;">@' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>', '<a class="sf-docs" target="_blank" href="https://docs.super-forms.com/common-problems/index/email-delivery-problems">', '</a>' ),
                                        'nodes' => array(
                                            array(
                                                'name' => 'to',
                                                'title' => esc_html__( 'To', 'super-forms' ),
                                                'subline' => esc_html__( 'Where the E-mail will be delivered to e.g. {email}', 'super-forms' ),
                                                'type' => 'text',
                                                'default' => '{email}',
                                                'reset' => true,
                                                'i18n' => true
                                            ),
                                            array(
                                                'name' => 'from_email',
                                                'title' => esc_html__( 'From email', 'super-forms' ),
                                                'subline' => sprintf( esc_html__( 'Your company E-mail address e.g. info%s', 'super-forms' ), '<strong style="color:red;">@' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>' ),
                                                'type' => 'text',
                                                'default' => 'no-reply@'.str_replace('www.', '', $_SERVER["SERVER_NAME"]),
                                                'reset' => true,
                                                'i18n' => true
                                            ),
                                            array(
                                                'name' => 'from_name',
                                                'title' => esc_html__( 'From name', 'super-forms' ),
                                                'subline' => esc_html__( 'Your company name e.g. Starbucks', 'super-forms' ),
                                                'type' => 'text',
                                                'default' => '{option_blogname}',
                                                'reset' => true,
                                                'i18n' => true
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
                                                                'i18n' => true
                                                            ),
                                                            array(
                                                                'name' => 'name',
                                                                'title' => esc_html__( 'Reply-To name (optional)', 'super-forms' ),
                                                                'subline' => esc_html__( 'The name of the person or company', 'super-forms' ),
                                                                'type' => 'text',
                                                                'default' => '',
                                                                'i18n' => true
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
                                                'reset' => true,
                                                'i18n' => true
                                            ),
                                            array(
                                                'name' => 'body',
                                                'type' => 'textarea',
                                                'tinymce' => true,
                                                'default' => sprintf( esc_html__( "The following information has been sent by the submitter:%sBest regards, %s", 'super-forms' ), '<br /><br />{loop_fields}<br /><br />', '{option_blogname}' ),
                                                'title' => esc_html__( 'Body', 'super-forms' ),
                                                'i18n' => true
                                            ),
                                            array(
                                                'name' => 'attachments',
                                                'title' => esc_html__( 'Attachments', 'super-forms' ),
                                                'label' => esc_html__( 'Hold Ctrl to add multiple files', 'super-forms' ),
                                                'type' => 'files', // file
                                                'multiple' => true,
                                                'default' => '',
                                                'i18n' => true
                                            ),
                                            array(
                                                'wrap' => false,
                                                'group' => true,
                                                'group_name' => 'csv_attachment',
                                                'vertical' => true,
                                                'nodes' => array(
                                                    array(
                                                        'toggle' => true,
                                                        'title' => esc_html__( 'CSV Attachment', 'super-forms' ),
                                                        'vertical' => true, // sfui-vertical
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'enabled',
                                                                'title' => esc_html__( 'Attach a CSV file with the form data', 'super-forms' ),
                                                                'type' => 'checkbox',
                                                                'default' => 'false'
                                                            ),
                                                            array(
                                                                'wrap' => false,
                                                                'group' => true, 
                                                                'group_name' => '',
                                                                'vertical' => true,
                                                                'padding' => false,
                                                                'filter' => 'csv_attachment.enabled;true',
                                                                'nodes' => array(
                                                                    array(
                                                                        'name' => 'name',
                                                                        'title' => esc_html__( 'The filename of the attachment', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default'=> 'super-csv-attachment',
                                                                        'reset'=>true,
                                                                        'i18n' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'save_as',
                                                                        'title'=> esc_html__( 'Choose what setting to use for dropdowns, checkboxes & radio buttons', 'super-forms' ),
                                                                        'subline'=> esc_html__( 'When editing a field you can define how to process the selected option, either to use the Label, or to use the Value.', 'super-forms' ),
                                                                        'type' => 'select', // dropdown
                                                                        'options' => array(
                                                                            'admin_email_value' => esc_html__( 'Use the admin email setting (default)', 'super-forms' ),
                                                                            'confirm_email_value' => esc_html__( 'Use the confirmation email setting', 'super-forms' ),
                                                                            'entry_value' => esc_html__( 'Use the contact entry setting', 'super-forms' ),
                                                                        ),
                                                                        'default'=>'admin_email_value',
                                                                        'reset'=>true
                                                                    ),
                                                                    array(
                                                                        'name' => 'delimiter',
                                                                        'title'=> esc_html__( 'Custom delimiter', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Set a custom delimiter to separate the values on each row', 'super-forms' ), 
                                                                        'type' => 'text', // dropdown
                                                                        'default'=>',',
                                                                        'reset'=>true
                                                                    ),
                                                                    array(
                                                                        'name' => 'enclosure',
                                                                        'title'=> esc_html__( 'Custom enclosure', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Set a custom enclosure character for values', 'super-forms' ), 
                                                                        'type' => 'text', // dropdown
                                                                        'default'=>'"',
                                                                        'reset'=>true
                                                                    ),
                                                                    array(
                                                                        'name' => 'exclude_fields',
                                                                        'type' => 'repeater',
                                                                        'toggle' => true,
                                                                        'title'=> esc_html__( 'Exclude fields from CSV file', 'super-forms' ),
                                                                        'label'=> esc_html__( 'When saving the CSV these fields will be excluded from the CSV file', 'super-forms' ),
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
                                                            )
                                                        )
                                                    )
                                                )
                                            ),
                                            array(
                                                'wrap' => false,
                                                'group' => true,
                                                'group_name' => 'xml_attachment',
                                                'vertical' => true,
                                                'nodes' => array(
                                                    array(
                                                        'toggle' => true,
                                                        'title' => esc_html__( 'XML Attachment', 'super-forms' ),
                                                        'vertical' => true, // sfui-vertical
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'enabled',
                                                                'title' => esc_html__( 'Attach a XML file with the form data', 'super-forms' ),
                                                                'type' => 'checkbox',
                                                                'default' => 'false'
                                                            ),
                                                            array(
                                                                'wrap' => false,
                                                                'group' => true, 
                                                                'group_name' => '',
                                                                'vertical' => true,
                                                                'padding' => false,
                                                                'filter' => 'xml_attachment.enabled;true',
                                                                'nodes' => array(
                                                                    array(
                                                                        'name' => 'name',
                                                                        'title' => esc_html__( 'The filename of the attachment', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default'=> 'super-xml-attachment',
                                                                        'reset'=>true,
                                                                        'i18n' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'content',
                                                                        'title'=> esc_html__( 'The XML content', 'super-forms' ),
                                                                        'subline'=> esc_html__( 'Use {tags} to retrieve form data', 'super-forms' ),
                                                                        'type'=>'textarea', 
                                                                        'default'=> "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<submission>\n<email>{email}</email>\n<name>{name}</name>\n<date>{submission_date}</date>\n<message>{message}</message>\n</submission>",
                                                                        'reset'=>true
                                                                    ),
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    ),
                                    array(
                                        'toggle' => true,
                                        'title' => esc_html__( 'E-mail template', 'super-forms' ),
                                        'vertical' => true, // sfui-vertical
                                        'nodes' => array(
                                            array(
                                                'wrap' => false,
                                                'group' => true,
                                                'group_name' => 'template',
                                                'vertical' => true,
                                                'nodes' => array(
                                                    array(
                                                        'name' => 'slug',
                                                        'title' => esc_html__( 'Select email template', 'super-forms' ),
                                                        'subline' => esc_html__( 'Choose which email template you would like to use', 'super-forms' ),
                                                        'type' => 'select',
                                                        'options'=>array(
                                                            'none' => esc_html__('Do not use the template', 'super-forms' ),
                                                            'email_template_1' => esc_html__( 'Use E-mail template', 'super-forms' )
                                                        ),
                                                        'default' => 'none',
                                                        'reset' => true,
                                                        'i18n' => false
                                                    ),
                                                    array(
                                                        'name' => 'logo',
                                                        'title' => esc_html__( 'Logo', 'super-forms' ),
                                                        'label' => esc_html__( 'Upload a logo to use for this email template', 'super-forms' ),
                                                        'type' => 'files', // file
                                                        'multiple' => false,
                                                        'default' => '',
                                                        'filter' => 'template.slug;email_template_1',
                                                        'i18n' => false
                                                    ),
                                                    array(
                                                        'name' => 'title',
                                                        'title' => esc_html__( 'Title', 'super-forms' ),
                                                        'subline' => esc_html__( 'A title to display below your logo', 'super-forms' ),
                                                        'default' => esc_html__( 'Your title', 'super-forms' ),
                                                        'type' => 'text',
                                                        'filter' => 'template.slug;email_template_1',
                                                        'reset' => true,
                                                        'i18n' => true
                                                    ),
                                                    array(
                                                        'name' => 'subtitle',
                                                        'title' => esc_html__( 'Subtitle', 'super-forms' ),
                                                        'subline' => esc_html__( 'A subtitle to display before the email body (content)', 'super-forms' ),
                                                        'default' => esc_html__( 'Your subtitle', 'super-forms' ),
                                                        'type' => 'text',
                                                        'filter' => 'template.slug;email_template_1',
                                                        'reset' => true,
                                                        'i18n' => true
                                                    ),
                                                    array(
                                                        'name' => 'copyright',
                                                        'title' => esc_html__( 'Copyright', 'super-forms' ),
                                                        'subline' => esc_html__( 'Enter anything you like for the copyright section', 'super-forms' ),
                                                        'default' => esc_html__( '&copy; Company Name and Address 2016', 'super-forms' ),
                                                        'type' => 'textarea',
                                                        'filter' => 'template.slug;email_template_1',
                                                        'reset' => true,
                                                        'i18n' => true
                                                    ),
                                                    array(
                                                        'name' => 'socials',
                                                        'title' => esc_html__( 'Social media icons', 'super-forms' ),
                                                        'subline' => esc_html__( 'Put each social icon on a new line', 'super-forms' ),
                                                        'default' => 'url_facebook_page|url_social_icon|Facebook',
                                                        'type' => 'textarea',
                                                        'filter' => 'template.slug;email_template_1',
                                                        'reset' => true,
                                                        'i18n' => false
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'header_colors',
                                                        'vertical' => true,
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'bg',
                                                                'inline' => true,
                                                                'subline' => esc_html__( 'Header background color', 'super-forms' ),
                                                                'default' => '#5ba1d3',
                                                                'type' => 'color',
                                                                'filter' => 'template.slug;email_template_1',
                                                                'reset' => true,
                                                                'i18n' => false
                                                            ),
                                                            array(
                                                                'name' => 'title',
                                                                'inline' => true,
                                                                'subline' => esc_html__( 'Header title color', 'super-forms' ),
                                                                'default' => '#ffffff',
                                                                'type' => 'color',
                                                                'filter' => 'template.slug;email_template_1',
                                                                'reset' => true,
                                                                'i18n' => false
                                                            ),
                                                        )
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'body_colors',
                                                        'vertical' => true,
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'bg',
                                                                'inline' => true,
                                                                'subline' => esc_html__( 'Body background color', 'super-forms' ),
                                                                'default' => '#ffffff',
                                                                'type' => 'color',
                                                                'filter' => 'template.slug;email_template_1',
                                                                'reset' => true,
                                                                'i18n' => false
                                                            ),
                                                            array(
                                                                'name' => 'subtitle',
                                                                'inline' => true,
                                                                'subline' => esc_html__( 'Body subtitle color', 'super-forms' ),
                                                                'default' => '#474747',
                                                                'type' => 'color',
                                                                'filter' => 'template.slug;email_template_1',
                                                                'reset' => true,
                                                                'i18n' => false
                                                            ),
                                                            array(
                                                                'name' => 'font',
                                                                'inline' => true,
                                                                'subline' => esc_html__( 'Body font color', 'super-forms' ),
                                                                'default' => '#9e9e9e',
                                                                'type' => 'color',
                                                                'filter' => 'template.slug;email_template_1',
                                                                'reset' => true,
                                                                'i18n' => false
                                                            )
                                                        )
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'footer_colors',
                                                        'vertical' => true,
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'bg',
                                                                'inline' => true,
                                                                'subline' => esc_html__( 'Footer background color', 'super-forms' ),
                                                                'default' => '#ee4c50',
                                                                'type' => 'color',
                                                                'filter' => 'template.slug;email_template_1',
                                                                'reset' => true,
                                                                'i18n' => false
                                                            ),
                                                            array(
                                                                'name' => 'font',
                                                                'inline' => true,
                                                                'subline' => esc_html__( 'Footer font color', 'super-forms' ),
                                                                'default' => '#ffffff',
                                                                'type' => 'color',
                                                                'filter' => 'template.slug;email_template_1',
                                                                'reset' => true,
                                                                'i18n' => false
                                                            )
                                                        )
                                                    )
                                                )
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
                                                'i18n' => true
                                            ),
                                            array(
                                                'wrap' => false,
                                                'padding' => false,
                                                'group' => true, // sfui-setting-group
                                                'group_name' => 'headers',
                                                'vertical' => true, // sfui-vertical
                                                //'filter' => '',
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
                            ),
                            array(
                                'wrap' => false,
                                'group' => true,
                                'vertical' => true,
                                //'filter' => 'enabled;true',
                                'nodes' => array(
                                    array(
                                        'toggle' => true,
                                        'title' => esc_html__( 'Translations (raw)', 'super-forms' ),
                                        'notice' => 'hint', // hint/info
                                        'content' => esc_html__( 'Although you can edit existing translated strings below, you may find it easier to use the [Translations] tab instead.', 'super-forms' ),
                                        'nodes' => array(
                                            array(
                                                'name' => 'i18n',
                                                'type' => 'textarea',
                                                'default' => ''
                                            )
                                        )
                                    )
                                )
                            )
                        )
                            ),

                            // Email Preview Toggle (50% width, right side)
                            array(
                                'toggle' => true,
                                'title' => esc_html__( 'E-mail Preview', 'super-forms' ),
                                'vertical' => true, // sfui-vertical
                                'width' => 50, // Split layout - 50% width
                                'filter' => 'enabled;true',
                                'nodes' => array(
                                    array(
                                        'type' => 'email_preview',
                                        'name' => 'preview', // Dummy name required for field processing
                                        'wrap' => false // Don't wrap in additional containers
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
        $s = array('emails' => $emails);
        $prefix = array();
        SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);
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


        // Statuses for both entries and posts (to trash and delete the entry or status)
        $trashStatus = array('v'=>'trash', 'i'=>'('.esc_html__( 'put in recycle bin', 'super-forms' ).')');
        $deleteStatus = array('v'=>'delete', 'i'=>'('.esc_html__( 'delete permanently', 'super-forms' ).')');

        // Entry statuses
        $statuses = SUPER_Settings::get_entry_statuses();
        foreach($statuses as $k => $v) {
            if($k==='') continue;
            $entryStatusesValues[] = array('v'=>$k);
        }
        $entryStatusesValues[] = $trashStatus;
        $entryStatusesValues[] = $deleteStatus;
        // Post statuses
        $postStatusesValues = array();
        $statuses = array('publish' => '('.esc_html__( 'default', 'super-forms' ).')', '{date;timestamp}' => '('.esc_html__( 'future/publish on specific date', 'super-forms' ).')', 'draft' => '', 'pending' => '', 'private' => '');
        foreach($statuses as $k => $v) {
            $postStatusesValues[] = array('v'=>$k, 'i'=>$v);
        }
        $postStatusesValues[] = $trashStatus;
        $postStatusesValues[] = $deleteStatus;

        // User roles
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters( 'editable_roles', $all_roles );
        $roleValues = array();
        foreach( $editable_roles as $k => $v ) {
            $roleValues[] = array('v'=>$k);
        }
        // Registered user login statuses
        $userLoginStatusesValues = array();
        $statuses = array(
            'active' => '('.esc_html__( 'allow login', 'super-forms' ).')',
            'pending' => '('.esc_html__( 'human verification required', 'super-forms' ).')',
            'paused' => '('.esc_html__( 'temporarily disable login', 'super-forms' ).')',
            'blocked' => '('.esc_html__( 'use this to ban a user', 'super-forms' ).')',
            'payment_past_due' => '('.esc_html__( 'when subscription charge failed', 'super-forms' ).')',
            'signup_payment_processing' => '('.esc_html__( 'signup payment is processing', 'super-forms' ) .')'
            //'payment_processing' => esc_html__( 'Payment processing', 'super-forms' ),
            //'payment_required' => esc_html__( 'Payment required', 'super-forms' ),
        );
        foreach($statuses as $k => $v) {
            $userLoginStatusesValues[] = array('v'=>$k, 'i'=>$v);
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
                    'sf.before.submission' => 'Super Forms - Before form submission',
                    'sf.after.submission' => 'Super Forms - After form submission',
                    'sf.submission.validation' => 'Super Forms - Validate form data',
                    'sf.after.account.registration' => 'Super Forms - After account registration'
                )
            ),
            array(
                'label' => 'WooCommerce',
                'items' => array(
                    'wc.order.status.completed' => 'WooCommerce - Order status changes to `completed`'
                )
            ),
            array(
                'label' => 'Stripe',
                'items' => array(
                    'stripe.checkout.session.completed' => 'Stripe - Checkout session completed',
                    'stripe.checkout.session.async_payment_failed' => 'Stripe - Checkout session async payment failed',
                    'stripe.fulfill_order' => 'Stripe - Fulfill order'
                )
            ),
            array(
                'label' => 'PayPal',
                'items' => array(
					'paypal.ipn.payment.verified' => 'PayPal - Order Fulfilled',
					'paypal.ipn.payment.refunded' => 'PayPal - Payment refunded',
					'paypal.ipn.subscription.payment.failed' => 'PayPal - Payment failed',
					'paypal.ipn.subscription.changed' => 'PayPal - Subscription changed',
					'paypal.ipn.subscription.expired' => 'PayPal - Subscription expired'
                )
            )
        );
        // Possible actions to choose from
        $actions = array(
            '' => '- choose an action - ',
            'send_email' => 'Send an E-mail',
            //'send_account_verification_email' => 'Send account verification E-mail (to verify email address)',
            'update_contact_entry_status' => 'Update Contact Entry Status',
            'update_created_post_status' => 'Update Created Post Status',
            'update_registered_user_login_status' => 'Update Registered User Login Status',
            'update_registered_user_role' => 'Update Registered User Role'



            // tmp 'insert_db_row' => 'Insert row to database table',
            // tmp 'validate_field' => 'Validate field value',
            // tmp 'create_post' => 'Create a post/page/product'
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
                        //'inline' => true, // sfui-inline
                        'vertical' => true, // sfui-vertical
                        'filter' => array(
                            array('field' => 'enabled', 'operator' => '=', 'value' => 'true'),
                            array('field' => 'event', 'operator' => '!=', 'value' => '')
                        ),
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
                                                        'group_name' => 'data',
                                                        'vertical' => true,
                                                        'filter' => 'action;update_contact_entry_status',
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'status',
                                                                'title' => esc_html__( 'Update Contact Entry Status to', 'super-forms' ),
                                                                'label' => sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ),
                                                                'accepted_values' => $entryStatusesValues,
                                                                'type' => 'text',
                                                                'default' => 'completed',
                                                                'reset' => true
                                                            ),
                                                        )
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'data',
                                                        'vertical' => true,
                                                        'filter' => 'action;update_created_post_status',
                                                        'nodes' => array(
                                                            array(
                                                                'notice' => 'hint', // hint/info
                                                                'content' => '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . sprintf( esc_html__('To schedule the post to become published automatically on a chosen date, simply enter the timestamp below. Must be English formatted date e.g: %s25-03-2025%s. When using a datepicker that doesn\'t use the correct format, you can use the tag %s{date;timestamp}%s (if your datepicker is named `date`) to retrieve the timestamp which will work correctly with any date format.', 'super-forms'), '<code>', '</code>', '<code>', '</code>')
                                                            ),
                                                            array(
                                                                'name' => 'status',
                                                                'title' => esc_html__( 'Update Created Post Status to', 'super-forms' ),
                                                                'label' => esc_html__( 'Used in combination with creating a new post after submitting a form', 'super-forms' ),
                                                                'accepted_values' => $postStatusesValues,
                                                                'type' => 'text',
                                                                'default' => 'publish',
                                                                'reset' => true
                                                            ),
                                                        )
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'data',
                                                        'vertical' => true,
                                                        'filter' => 'action;update_registered_user_login_status',
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'status',
                                                                'title' => esc_html__( 'Update Registered User Login Status to', 'super-forms' ),
                                                                'label' => esc_html__( 'Used in combination with the registartion of a new user after submitting a form', 'super-forms' ),
                                                                'accepted_values' => $userLoginStatusesValues,
                                                                'type' => 'text',
                                                                'default' => 'active',
                                                                'reset' => true
                                                            ),
                                                        )
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'data',
                                                        'vertical' => true,
                                                        'filter' => 'action;update_registered_user_role',
                                                        'nodes' => array(
                                                            array(
                                                                'name' => 'status',
                                                                'title' => esc_html__( 'Update Registered User Role to', 'super-forms' ),
                                                                'label' => esc_html__( 'Used in combination with the registartion of a new user after submitting a form', 'super-forms' ),
                                                                'accepted_values' => $roleValues,
                                                                'type' => 'text',
                                                                'default' => 'active',
                                                                'reset' => true
                                                            ),
                                                        )
                                                    ),
                                                    array(
                                                        'wrap' => false,
                                                        'group' => true,
                                                        'group_name' => 'data',
                                                        'vertical' => true,
                                                        'filter' => 'action;send_email',
                                                        //'filter' => 'action;send_email,send_account_verification_email',
                                                        'nodes' => array(
                                                            array(
                                                                'toggle' => true,
                                                                'title' => esc_html__( 'E-mail headers', 'super-forms' ),
                                                                'notice' => 'hint', // hint/info
                                                                'content' => sprintf( esc_html__( 'The `From email` should end with %s for E-mails to work. If you are using an email provider (Gmail, Yahoo, Outlook.com, etc) it should be the email address of that account. If you have problems with E-mail delivery you can read this guide on possible solutions: %sEmail delivery problems%s', 'super-forms' ), '<strong style="color:red;">@' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>', '<a class="sf-docs" target="_blank" href="https://docs.super-forms.com/common-problems/index/email-delivery-problems">', '</a>' ),
                                                                'nodes' => array(
                                                                    array(
                                                                        'name' => 'to',
                                                                        'title' => esc_html__( 'To', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Where the E-mail will be delivered to e.g. {email}', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default' => '{email}',
                                                                        'reset' => true,
                                                                        'i18n' => true,
                                                                        'filter' => 'action;send_email'
                                                                    ),
                                                                    array(
                                                                        'name' => 'from_email',
                                                                        'title' => esc_html__( 'From email', 'super-forms' ),
                                                                        'subline' => sprintf( esc_html__( 'Your company E-mail address e.g. info%s', 'super-forms' ), '<strong style="color:red;">@' . str_replace('www.', '', $_SERVER["SERVER_NAME"]) . '</strong>' ),
                                                                        'type' => 'text',
                                                                        'default' => 'no-reply@'.str_replace('www.', '', $_SERVER["SERVER_NAME"]),
                                                                        'reset' => true,
                                                                        'i18n' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'from_name',
                                                                        'title' => esc_html__( 'From name', 'super-forms' ),
                                                                        'subline' => esc_html__( 'Your company name e.g. Starbucks', 'super-forms' ),
                                                                        'type' => 'text',
                                                                        'default' => '{option_blogname}',
                                                                        'reset' => true,
                                                                        'i18n' => true
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
                                                                                        'i18n' => true
                                                                                    ),
                                                                                    array(
                                                                                        'name' => 'name',
                                                                                        'title' => esc_html__( 'Reply-To name (optional)', 'super-forms' ),
                                                                                        'subline' => esc_html__( 'The name of the person or company', 'super-forms' ),
                                                                                        'type' => 'text',
                                                                                        'default' => '',
                                                                                        'i18n' => true
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
                                                                        'notice' => 'hint', // hint/info
                                                                        'content' => '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . sprintf( esc_html__('Use %s and %s tags to display the expiry in time and the retry payment link itself inside your E-mail body.', 'super-forms'), '{stripe_retry_payment_expiry}', '{stripe_retry_payment_url}' ),
                                                                        'filter' => 'event;stripe.checkout.session.async_payment_failed'
                                                                    ),
                                                                    array(
                                                                        'notice' => 'hint', // hint/info
                                                                        'content' => '<strong>'.esc_html__('Example retry payment email', 'super-forms').':</strong><br /><br />' . sprintf( esc_html__( 'Payment failed please try again by clicking the below URL.%sThe below link will be valid for %s hours before your order is removed.%s%s', 'super-forms'), '<br />', '{stripe_retry_payment_expiry}', '<br /><br />', '<a href="{stripe_retry_payment_url}">{stripe_retry_payment_url}</a>' ),
                                                                        'filter' => 'event;stripe.checkout.session.async_payment_failed'
                                                                    ),
                                                                    array(
                                                                        'name' => 'subject',
                                                                        'type' => 'text',
                                                                        'default' => esc_html__( 'New question', 'super-forms' ),
                                                                        'title' => esc_html__( 'Subject', 'super-forms' ),
                                                                        'reset' => true,
                                                                        'i18n' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'body',
                                                                        'type' => 'textarea',
                                                                        'tinymce' => true,
                                                                        'default' => sprintf( esc_html__( "The following information has been sent by the submitter:%sBest regards, %s", 'super-forms' ), '<br /><br />{loop_fields}<br /><br />', '{option_blogname}' ),
                                                                        'title' => esc_html__( 'Body', 'super-forms' ),
                                                                        'i18n' => true
                                                                    ),
                                                                    array(
                                                                        'name' => 'attachments',
                                                                        'title' => esc_html__( 'Attachments', 'super-forms' ),
                                                                        'label' => esc_html__( 'Hold Ctrl to add multiple files', 'super-forms' ),
                                                                        'type' => 'files', // file
                                                                        'default' => '',
                                                                        'i18n' => true
                                                                    ),
                                                                    array(
                                                                        'wrap' => false,
                                                                        'group' => true,
                                                                        'group_name' => 'csv_attachment',
                                                                        'vertical' => true,
                                                                        'filter' => 'action;send_email',
                                                                        'nodes' => array(
                                                                            array(
                                                                                'toggle' => true,
                                                                                'title' => esc_html__( 'CSV Attachment', 'super-forms' ),
                                                                                'vertical' => true, // sfui-vertical
                                                                                'nodes' => array(
                                                                                    array(
                                                                                        'name' => 'enabled',
                                                                                        'title' => esc_html__( 'Attach a CSV file with the form data', 'super-forms' ),
                                                                                        'type' => 'checkbox',
                                                                                        'default' => 'false'
                                                                                    ),
                                                                                    array(
                                                                                        'wrap' => false,
                                                                                        'group' => true, 
                                                                                        'group_name' => '',
                                                                                        'vertical' => true,
                                                                                        'padding' => false,
                                                                                        'filter' => 'csv_attachment.enabled;true',
                                                                                        'nodes' => array(
                                                                                            array(
                                                                                                'name' => 'name',
                                                                                                'title' => esc_html__( 'The filename of the attachment', 'super-forms' ),
                                                                                                'type' => 'text',
                                                                                                'default'=> 'super-csv-attachment',
                                                                                                'reset'=>true,
                                                                                                'i18n' => true
                                                                                            ),
                                                                                            array(
                                                                                                'name' => 'save_as',
                                                                                                'title'=> esc_html__( 'Choose what value to save for checkboxes & radio buttons', 'super-forms' ),
                                                                                                'subline'=> esc_html__( 'When editing a field you can change these settings', 'super-forms' ),
                                                                                                'type' => 'select', // dropdown
                                                                                                'options' => array(
                                                                                                    'admin_email_value' => esc_html__( 'Save the admin email value (default)', 'super-forms' ),
                                                                                                    'confirm_email_value' => esc_html__( 'Save the confirmation email value', 'super-forms' ),
                                                                                                    'entry_value' => esc_html__( 'Save the entry value', 'super-forms' ),
                                                                                                ),
                                                                                                'default'=>'admin_email_value',
                                                                                                'reset'=>true
                                                                                            ),
                                                                                            array(
                                                                                                'name' => 'delimiter',
                                                                                                'title'=> esc_html__( 'Custom delimiter', 'super-forms' ),
                                                                                                'subline' => esc_html__( 'Set a custom delimiter to separate the values on each row', 'super-forms' ), 
                                                                                                'type' => 'text', // dropdown
                                                                                                'default'=>',',
                                                                                                'reset'=>true
                                                                                            ),
                                                                                            array(
                                                                                                'name' => 'enclosure',
                                                                                                'title'=> esc_html__( 'Custom enclosure', 'super-forms' ),
                                                                                                'subline' => esc_html__( 'Set a custom enclosure character for values', 'super-forms' ), 
                                                                                                'type' => 'text', // dropdown
                                                                                                'default'=>'"',
                                                                                                'reset'=>true
                                                                                            ),
                                                                                            array(
                                                                                                'name' => 'exclude_fields',
                                                                                                'type' => 'repeater',
                                                                                                'toggle' => true,
                                                                                                'title'=> esc_html__( 'Exclude fields from CSV file', 'super-forms' ),
                                                                                                'label'=> esc_html__( 'When saving the CSV these fields will be excluded from the CSV file', 'super-forms' ),
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
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    ),
                                                                    array(
                                                                        'wrap' => false,
                                                                        'group' => true,
                                                                        'group_name' => 'xml_attachment',
                                                                        'vertical' => true,
                                                                        'filter' => 'action;send_email',
                                                                        'nodes' => array(
                                                                            array(
                                                                                'toggle' => true,
                                                                                'title' => esc_html__( 'XML Attachment', 'super-forms' ),
                                                                                'vertical' => true, // sfui-vertical
                                                                                'nodes' => array(
                                                                                    array(
                                                                                        'name' => 'enabled',
                                                                                        'title' => esc_html__( 'Attach a XML file with the form data', 'super-forms' ),
                                                                                        'type' => 'checkbox',
                                                                                        'default' => 'false'
                                                                                    ),
                                                                                    array(
                                                                                        'wrap' => false,
                                                                                        'group' => true, 
                                                                                        'group_name' => '',
                                                                                        'vertical' => true,
                                                                                        'padding' => false,
                                                                                        'filter' => 'xml_attachment.enabled;true',
                                                                                        'nodes' => array(
                                                                                            array(
                                                                                                'name' => 'name',
                                                                                                'title' => esc_html__( 'The filename of the attachment', 'super-forms' ),
                                                                                                'type' => 'text',
                                                                                                'default'=> 'super-xml-attachment',
                                                                                                'reset'=>true,
                                                                                                'i18n' => true
                                                                                            ),
                                                                                            array(
                                                                                                'name' => 'content',
                                                                                                'title'=> esc_html__( 'The XML content', 'super-forms' ),
                                                                                                'subline'=> esc_html__( 'Use {tags} to retrieve form data', 'super-forms' ),
                                                                                                'type'=>'textarea', 
                                                                                                'default'=> "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<submission>\n<email>{email}</email>\n<name>{name}</name>\n<date>{submission_date}</date>\n<message>{message}</message>\n</submission>",
                                                                                                'reset'=>true
                                                                                            ),
                                                                                        )
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
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
                                                                        'i18n' => true
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
                                                    ),
                                                )
                                            )
                                        )
                                    )
                                )
                            ),
                            array(
                                'wrap' => false,
                                'group' => true,
                                'vertical' => true,
                                //'filter' => 'action;send_email,send_account_verification_email',
                                //'filter' => 'action;send_email',
                                'nodes' => array(
                                    array(
                                        'toggle' => true,
                                        'title' => esc_html__( 'Translations (raw)', 'super-forms' ),
                                        'notice' => 'hint', // hint/info
                                        'content' => esc_html__( 'Although you can edit existing translated strings below, you may find it easier to use the [Translations] tab instead.', 'super-forms' ),
                                        'nodes' => array(
                                            array(
                                                'name' => 'i18n',
                                                'type' => 'textarea',
                                                'default' => ''
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
        $s = array('triggers' => $triggers);
        $prefix = array();
        SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);
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
        $woocommerce = SUPER_Common::get_form_woocommerce_settings($form_id);
        $listings = SUPER_Common::get_form_listings_settings($form_id);

        $pdf = SUPER_Common::get_form_pdf_settings($form_id);
        //error_log('get_form_stripe_settings(4)');
        $stripe = SUPER_Common::get_form_stripe_settings($form_id);

        // Retrieve all form setting fields with the correct default values
        $fields = SUPER_Settings::fields($settings);

        // Get all available shortcodes
        $shortcodes = SUPER_Shortcodes::shortcodes();
        
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
                            <input placeholder="<?php _e( 'Contact Entry Title', 'super-forms' ); ?>" type="text" name="super_contact_entry_post_title" size="30" value="<?php echo $entry_title; ?>" id="title" spellcheck="true" autocomplete="off">
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
                                                        //var_dump($stripe_connections);
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
