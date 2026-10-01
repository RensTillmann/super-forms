<?php
/**
 * Security regressions for retrieve_method=users (and the author meta retrieve
 * method): WP user data and user meta that SUPER_Shortcodes::get_items()
 * (includes/class-shortcodes.php) renders into the public form HTML.
 * Also covers the {author_meta_*} tags that SUPER_Common::email_tags()
 * (includes/class-common.php) resolves for element default values, where
 * ?author=<id> makes the author request-chosen.
 *
 * @package Super_Forms_Tests
 */

class Test_Security_Users_Retrieve extends WP_UnitTestCase {

    private $created_users = array();
    private $filters = array();
    private $form_ids = array();
    private $original_current_user = 0;
    private $original_get = array();
    private $original_server = array();
    private $scope;
    private $users = array();

    public function set_up() {
        parent::set_up();
        $this->scope = 'sfusers' . substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 10 );
        $this->original_current_user = get_current_user_id();
        $this->original_get = $_GET;
        $this->original_server = $_SERVER;
        $_GET = array();
        // The author retrieve method rebuilds the page URL from the request
        // (includes/class-shortcodes.php:925).
        if( !isset( $_SERVER['HTTP_HOST'] ) ) $_SERVER['HTTP_HOST'] = 'example.org';
        if( !isset( $_SERVER['REQUEST_URI'] ) ) $_SERVER['REQUEST_URI'] = '/';
        wp_set_current_user( 0 );
        $this->users['alice'] = $this->seed_user( 'alice' );
        $this->users['bob'] = $this->seed_user( 'bob' );
    }

    public function tear_down() {
        foreach( array_reverse( $this->filters ) as $filter ) {
            remove_filter( $filter['tag'], $filter['callback'], $filter['priority'] );
        }
        $this->filters = array();
        wp_set_current_user( 0 );
        foreach( array_reverse( $this->form_ids ) as $form_id ) {
            wp_delete_post( $form_id, true );
        }
        $this->form_ids = array();
        if( !function_exists( 'wp_delete_user' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        foreach( array_reverse( $this->created_users ) as $user_id ) {
            if( get_userdata( $user_id ) ) {
                wp_delete_user( $user_id );
            }
        }
        $this->created_users = array();
        $_GET = $this->original_get;
        $_SERVER = $this->original_server;
        wp_set_current_user( $this->original_current_user );
        parent::tear_down();
    }

    /**
     * A subscriber with an e-mail address, a password hash, an activation key,
     * a `secret_token` user meta and a private `_private_note` user meta. Every
     * value carries a unique marker so the assertions cannot match by accident.
     */
    private function seed_user( $alias ) {
        global $wpdb;
        $marker = $this->scope . $alias;
        $user = array(
            'login' => 'login' . $marker,
            'nicename' => 'nice' . $marker,
            'email' => 'mail' . $marker . '@example.test',
            'display' => 'Display ' . ucfirst( $alias ) . ' ' . $marker,
            'first' => 'First' . $marker,
            'last' => 'Last' . $marker,
            'url' => 'https://url' . $marker . '.example.test/',
            'token' => 'token' . $marker,
            'private' => 'private' . $marker,
            'activation_key' => 'activation' . $marker,
        );
        $user['id'] = self::factory()->user->create( array(
            'user_login' => $user['login'],
            'user_nicename' => $user['nicename'],
            'user_email' => $user['email'],
            'user_pass' => 'pass' . $marker,
            'user_url' => $user['url'],
            'display_name' => $user['display'],
            'first_name' => $user['first'],
            'last_name' => $user['last'],
            'role' => 'subscriber',
        ) );
        $this->created_users[] = $user['id'];
        update_user_meta( $user['id'], 'secret_token', $user['token'] );
        update_user_meta( $user['id'], '_private_note', $user['private'] );
        $wpdb->update( $wpdb->users, array( 'user_activation_key' => $user['activation_key'] ), array( 'ID' => $user['id'] ) );
        clean_user_cache( $user['id'] );
        $data = get_userdata( $user['id'] );
        $this->assertSame( $user['activation_key'], $data->user_activation_key );
        $this->assertNotEmpty( $data->user_pass );
        $user['pass_hash'] = $data->user_pass;
        return $user;
    }

    private function act_as( $role ) {
        if( $role==='anonymous' ) {
            wp_set_current_user( 0 );
            return 0;
        }
        $user_id = self::factory()->user->create( array( 'role' => $role ) );
        $this->created_users[] = $user_id;
        wp_set_current_user( $user_id );
        return $user_id;
    }

    private function add_tracked_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
        add_filter( $tag, $callback, $priority, $accepted_args );
        $this->filters[] = array(
            'tag' => $tag,
            'callback' => $callback,
            'priority' => $priority,
        );
    }

    /**
     * A published form with one dropdown; retrieve_method=users unless overridden.
     */
    private function create_form( $element_data = array(), $tag = 'dropdown' ) {
        $form_id = self::factory()->post->create( array(
            'post_type' => 'super_form',
            'post_status' => 'publish',
        ) );
        $this->form_ids[] = $form_id;
        update_post_meta( $form_id, '_super_elements', array(
            array(
                'tag' => $tag,
                'group' => 'form_elements',
                'data' => array_merge( array(
                    'name' => 'assignee',
                    'retrieve_method' => 'users',
                    'retrieve_method_role_filters' => 'subscriber',
                ), $element_data ),
                'inner' => array(),
            ),
        ) );
        return $form_id;
    }

    /**
     * A published form with one hidden field whose default value is a {tag}:
     * SUPER_Shortcodes::get_default_value() (includes/class-shortcodes.php:176)
     * resolves it through SUPER_Common::email_tags() when the form renders.
     */
    private function create_hidden_form( $default_value ) {
        $form_id = self::factory()->post->create( array(
            'post_type' => 'super_form',
            'post_status' => 'publish',
        ) );
        $this->form_ids[] = $form_id;
        update_post_meta( $form_id, '_super_elements', array(
            array(
                'tag' => 'hidden',
                'group' => 'form_elements',
                'data' => array(
                    'name' => 'author_data',
                    'value' => $default_value,
                ),
                'inner' => array(),
            ),
        ) );
        return $form_id;
    }

    /**
     * Render through the public shortcode path (what an anonymous visitor gets).
     */
    private function render( $form_id ) {
        return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
    }

    private function assert_no_sensitive_user_data( $html, $user, $context ) {
        $this->assertStringNotContainsString( $user['email'], $html, $context . ': user_email leaked' );
        $this->assertStringNotContainsString( $user['login'], $html, $context . ': user_login leaked' );
        $this->assertStringNotContainsString( $user['pass_hash'], $html, $context . ': user_pass leaked' );
        $this->assertStringNotContainsString( $user['activation_key'], $html, $context . ': user_activation_key leaked' );
        $this->assertStringNotContainsString( $user['token'], $html, $context . ': secret_token meta leaked' );
        $this->assertStringNotContainsString( $user['private'], $html, $context . ': private _ meta leaked' );
    }

    public function test_anonymous_visitor_sees_ids_and_display_names_only() {
        $form_id = $this->create_form();

        $html = $this->render( $form_id );

        foreach( $this->users as $alias => $user ) {
            // The old default label '#{ID} - {first_name} {last_name} ({user_email})'
            // becomes '#{ID} - {display_name}' for unprivileged viewers.
            $this->assertStringContainsString( '<div>#' . $user['id'] . ' - ' . $user['display'] . '</div>', $html, $alias );
            $this->assertStringContainsString( 'data-value="' . $user['id'] . '"', $html, $alias );
            $this->assertStringNotContainsString( $user['first'], $html, $alias . ': first_name from the old default label' );
            $this->assert_no_sensitive_user_data( $html, $user, 'anonymous/' . $alias );
        }
    }

    public function test_anonymous_visitor_custom_label_and_value_keys_resolve_denied_fields_to_empty() {
        $form_id = $this->create_form( array(
            'retrieve_method_user_label' => '{display_name}|{first_name}|{last_name}|{user_email}|{user_login}|{user_pass}|{secret_token}|{_private_note}|{user_url}',
            'retrieve_method_user_meta_keys' => "ID\nuser_email\nsecret_token\nfirst_name\nuser_url",
        ) );

        $html = $this->render( $form_id );

        foreach( $this->users as $alias => $user ) {
            $this->assertStringContainsString(
                '<div>' . $user['display'] . '|' . $user['first'] . '|' . $user['last'] . '||||||</div>',
                $html,
                $alias . ': allowed tags kept, denied tags empty'
            );
            // Denied value keys keep their ;-position so {assignee;N} stays stable.
            $this->assertStringContainsString( 'data-value="' . $user['id'] . ';;;' . $user['first'] . ';"', $html, $alias );
            $this->assertStringNotContainsString( $user['url'], $html, $alias . ': user_url is not on the default allowlist' );
            $this->assert_no_sensitive_user_data( $html, $user, 'anonymous-custom/' . $alias );
        }
    }

    public function test_logged_in_viewer_without_list_users_is_unprivileged() {
        $this->act_as( 'subscriber' );
        $form_id = $this->create_form( array(
            'retrieve_method_user_label' => '{display_name}|{user_email}|{user_login}',
            'retrieve_method_user_meta_keys' => "ID\nuser_email",
        ) );

        $html = $this->render( $form_id );

        foreach( $this->users as $alias => $user ) {
            $this->assertStringContainsString( '<div>' . $user['display'] . '||</div>', $html, $alias );
            $this->assertStringContainsString( 'data-value="' . $user['id'] . ';"', $html, $alias );
            $this->assert_no_sensitive_user_data( $html, $user, 'subscriber/' . $alias );
        }
    }

    public function test_administrator_keeps_todays_behaviour_for_non_denied_fields() {
        $this->act_as( 'administrator' );
        $default_form_id = $this->create_form( array(
            'retrieve_method_user_meta_keys' => "ID\nuser_email",
        ) );
        $custom_form_id = $this->create_form( array(
            'retrieve_method_user_label' => '{user_login}|{user_nicename}|{secret_question}',
        ) );
        foreach( $this->users as $user ) {
            update_user_meta( $user['id'], 'secret_question', 'question' . $this->scope );
        }

        $default_html = $this->render( $default_form_id );
        $custom_html = $this->render( $custom_form_id );

        foreach( $this->users as $alias => $user ) {
            $this->assertStringContainsString(
                '<div>#' . $user['id'] . ' - ' . $user['first'] . ' ' . $user['last'] . ' (' . $user['email'] . ')</div>',
                $default_html,
                $alias . ': privileged viewers keep the old default label'
            );
            $this->assertStringContainsString( 'data-value="' . $user['id'] . ';' . $user['email'] . '"', $default_html, $alias );
            $this->assertStringContainsString( '<div>' . $user['login'] . '|' . $user['nicename'] . '|</div>', $custom_html, $alias . ': user_login stays available to privileged viewers, secret_* meta does not' );
        }
    }

    public function test_hard_denylist_applies_to_administrators_too() {
        $this->act_as( 'administrator' );
        $form_id = $this->create_form( array(
            'retrieve_method_user_label' => '{display_name}|{user_pass}|{secret_token}|{user_activation_key}|{wp_capabilities}|{_private_note}|{session_tokens}',
            'retrieve_method_user_meta_keys' => "ID\nuser_pass\nsecret_token\nuser_activation_key",
        ) );

        $html = $this->render( $form_id );

        foreach( $this->users as $alias => $user ) {
            $this->assertStringContainsString( '<div>' . $user['display'] . '||||||</div>', $html, $alias );
            $this->assertStringContainsString( 'data-value="' . $user['id'] . ';;;"', $html, $alias );
            $this->assertStringNotContainsString( $user['pass_hash'], $html, $alias . ': user_pass leaked to an administrator' );
            $this->assertStringNotContainsString( $user['token'], $html, $alias . ': secret_token leaked to an administrator' );
            $this->assertStringNotContainsString( $user['activation_key'], $html, $alias . ': user_activation_key leaked to an administrator' );
            $this->assertStringNotContainsString( $user['private'], $html, $alias . ': private _ meta leaked to an administrator' );
        }
    }

    public function test_filter_extends_the_allowlist_for_unprivileged_viewers_but_cannot_lift_the_denylist() {
        $calls = array();
        $this->add_tracked_filter(
            'super_users_retrieve_allowed_fields',
            function( $allowed, $privileged, $atts ) use ( &$calls ) {
                $calls[] = array( 'allowed' => $allowed, 'privileged' => $privileged, 'atts' => $atts );
                if( !$privileged ) {
                    $allowed[] = 'user_url';
                    $allowed[] = 'user_pass';
                    $allowed[] = 'secret_token';
                }
                return $allowed;
            },
            10,
            3
        );
        $form_id = $this->create_form( array(
            'retrieve_method_user_label' => '{display_name}|{user_url}|{user_pass}|{secret_token}',
            'retrieve_method_user_meta_keys' => "ID\nuser_url\nsecret_token",
        ) );

        $html = $this->render( $form_id );

        $this->assertNotEmpty( $calls, 'filter did not run' );
        $this->assertFalse( $calls[0]['privileged'] );
        $this->assertSame( array( 'ID', 'display_name', 'user_nicename', 'nickname', 'first_name', 'last_name' ), $calls[0]['allowed'] );
        $this->assertSame( 'assignee', $calls[0]['atts']['name'] );
        $this->assertSame( 'users', $calls[0]['atts']['retrieve_method'] );
        foreach( $this->users as $alias => $user ) {
            $this->assertStringContainsString( '<div>' . $user['display'] . '|' . $user['url'] . '||</div>', $html, $alias );
            $this->assertStringContainsString( 'data-value="' . $user['id'] . ';' . $user['url'] . ';"', $html, $alias );
            $this->assert_no_sensitive_user_data( $html, $user, 'filter/' . $alias );
        }

        $calls = array();
        $this->act_as( 'administrator' );
        $this->render( $form_id );
        $this->assertNotEmpty( $calls );
        $this->assertTrue( $calls[0]['privileged'] );
    }

    public function test_author_retrieve_method_never_renders_denied_meta_but_keeps_option_lists() {
        $alice = $this->users['alice'];
        update_user_meta( $alice['id'], 'assignee_options', "Red " . $this->scope . "|red\nBlue " . $this->scope . "|blue" );
        $denied_form_id = $this->create_form( array(
            'retrieve_method' => 'author',
            'retrieve_method_author_field' => 'secret_token',
        ) );
        $allowed_form_id = $this->create_form( array(
            'retrieve_method' => 'author',
            'retrieve_method_author_field' => 'assignee_options',
        ) );
        // ?author=<id> makes the target user attacker-chosen (class-shortcodes.php:927).
        $_GET['author'] = (string) $alice['id'];

        $denied_html = $this->render( $denied_form_id );
        $allowed_html = $this->render( $allowed_form_id );

        $this->assertStringNotContainsString( $alice['token'], $denied_html, 'secret_token meta rendered through the author retrieve method' );
        $this->assertStringContainsString( 'data-value="red" data-search-value="Red ' . $this->scope . '"', $allowed_html );
        $this->assertStringContainsString( '<div>Blue ' . $this->scope . '</div>', $allowed_html );
    }

    public function test_author_meta_tags_never_resolve_denied_fields_for_the_request_chosen_author() {
        $alice = $this->users['alice'];
        $forms = array();
        // WP_User data fields, user meta and public WP_User properties (caps/allcaps hold arrays).
        foreach( array( 'user_pass', 'user_activation_key', 'secret_token', '_private_note', 'session_tokens', 'wp_capabilities', 'caps', 'allcaps' ) as $key ) {
            $forms[$key] = $this->create_hidden_form( '{author_meta_' . $key . '}' );
        }
        // ?author=<id> makes the author attacker-chosen (includes/class-common.php:2080).
        $_GET['author'] = (string) $alice['id'];

        foreach( array( 'anonymous', 'administrator' ) as $role ) {
            $this->act_as( $role );
            foreach( $forms as $key => $form_id ) {
                $html = $this->render( $form_id );
                $this->assertStringContainsString( 'name="author_data" value=""', $html, $role . ': {author_meta_' . $key . '} must render empty' );
                $this->assert_no_sensitive_user_data( $html, $alice, $role . '/{author_meta_' . $key . '}' );
            }
        }
    }

    public function test_author_meta_tags_keep_resolving_profile_fields_and_ordinary_meta() {
        $alice = $this->users['alice'];
        update_user_meta( $alice['id'], 'description', 'Bio ' . $this->scope );
        $_GET['author'] = (string) $alice['id'];

        $expected = array(
            // user meta resolves through get_user_meta()
            '{author_meta_first_name}' => $alice['first'],
            '{author_meta_description}' => 'Bio ' . $this->scope,
            // WP_User data fields resolve through the (now restricted) property fallback
            '{author_meta_display_name}' => $alice['display'],
            '{author_meta_user_nicename}' => $alice['nicename'],
            '{author_meta_ID}' => (string) $alice['id'],
            // The contact-the-author pattern (a hidden {author_meta_user_email} / {author_email}
            // feeding the e-mail "To" header) is unchanged: the author e-mail stays resolvable.
            '{author_meta_user_email}' => $alice['email'],
            '{author_email}' => $alice['email'],
            // Other WP_User properties (roles, filter, ...) and unknown keys are no fallback source
            '{author_meta_roles}' => '',
            '{author_meta_filter}' => '',
            '{author_meta_no_such_field}' => '',
        );
        foreach( $expected as $tag => $value ) {
            $html = $this->render( $this->create_hidden_form( $tag ) );
            $this->assertStringContainsString( 'name="author_data" value="' . esc_attr( $value ) . '"', $html, $tag );
        }
    }

    public function test_field_helpers_deny_credentials_and_private_meta_for_everyone() {
        foreach( array( 'user_pass', 'USER_PASS', 'user_activation_key', 'session_tokens', 'wp_capabilities', 'wp_user_level', 'password', 'secret_token', 'api_key', 'stripe_secret', 'my_salt', 'password_hash', '_private_note', '_super_anything', '' ) as $key ) {
            $this->assertTrue( SUPER_Shortcodes::users_retrieve_field_denied( $key ), $key . ' must be denied' );
        }
        foreach( array( 'ID', 'display_name', 'user_nicename', 'nickname', 'first_name', 'last_name', 'user_email', 'user_login', 'user_url', 'billing_email', 'keyword', 'passport_number', 'description' ) as $key ) {
            $this->assertFalse( SUPER_Shortcodes::users_retrieve_field_denied( $key ), $key . ' must not be hard-denied' );
        }

        wp_set_current_user( 0 );
        $anonymous = SUPER_Shortcodes::users_retrieve_allowed_fields( array( 'name' => 'assignee' ) );
        $this->assertFalse( $anonymous['privileged'] );
        $this->assertSame( array( 'id', 'display_name', 'user_nicename', 'nickname', 'first_name', 'last_name' ), $anonymous['allowed'] );
        foreach( array( 'ID', 'id', 'display_name', 'first_name', 'last_name', 'nickname', 'user_nicename' ) as $key ) {
            $this->assertTrue( SUPER_Shortcodes::users_retrieve_field_allowed( $key, $anonymous ), $key . ' must be allowed for anonymous viewers' );
        }
        foreach( array( 'user_email', 'user_login', 'user_url', 'user_registered', 'billing_email', 'secret_token', 'user_pass', '_private_note' ) as $key ) {
            $this->assertFalse( SUPER_Shortcodes::users_retrieve_field_allowed( $key, $anonymous ), $key . ' must be denied for anonymous viewers' );
        }

        $this->act_as( 'administrator' );
        $privileged = SUPER_Shortcodes::users_retrieve_allowed_fields( array( 'name' => 'assignee' ) );
        $this->assertTrue( $privileged['privileged'] );
        foreach( array( 'user_email', 'user_login', 'user_url', 'billing_email', 'ID' ) as $key ) {
            $this->assertTrue( SUPER_Shortcodes::users_retrieve_field_allowed( $key, $privileged ), $key . ' must be allowed for privileged viewers' );
        }
        foreach( array( 'user_pass', 'user_activation_key', 'session_tokens', 'secret_token', 'wp_capabilities', '_private_note' ) as $key ) {
            $this->assertFalse( SUPER_Shortcodes::users_retrieve_field_allowed( $key, $privileged ), $key . ' must stay denied for privileged viewers' );
        }
    }
}
