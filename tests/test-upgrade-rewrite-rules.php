<?php
/**
 * Plugin updates do not run the activation hook. A site that updates to a version with new rewrite
 * rules (for example 6.3 -> 6.4, which adds the Stripe return URLs) must get them without re-saving
 * the permalinks: the plugin flushes once per version, on wp_loaded.
 */
class Test_Super_Forms_Upgrade_Rewrite_Rules extends WP_UnitTestCase {
    public function set_up() {
        parent::set_up();
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure( '/%postname%/' );
        remove_action( 'wp_loaded', array( SUPER_Forms(), 'flush_rewrite_rules_once' ) );
    }

    public function tear_down() {
        remove_action( 'wp_loaded', array( SUPER_Forms(), 'flush_rewrite_rules_once' ) );
        delete_option( 'super_rewrite_rules_version' );
        parent::tear_down();
    }

    public function test_version_change_schedules_one_flush_that_adds_the_stripe_return_rules() {
        global $wp_rewrite;
        // State of a site that came from 6.3: stored rules without the Stripe routes, no recorded version.
        delete_option( 'super_rewrite_rules_version' );
        $rules = get_option( 'rewrite_rules' );
        $rules = is_array( $rules ) ? $rules : array();
        foreach( array_keys( $rules ) as $k ) if( strpos( $k, 'sfssid' ) === 0 ) unset( $rules[$k] );
        update_option( 'rewrite_rules', $rules );
        $wp_rewrite->init();

        SUPER_Forms()->rewrite_rules();
        $this->assertNotFalse( has_action( 'wp_loaded', array( SUPER_Forms(), 'flush_rewrite_rules_once' ) ) );

        SUPER_Forms()->flush_rewrite_rules_once();
        $this->assertSame( SUPER_VERSION, get_option( 'super_rewrite_rules_version' ) );
        $stored = get_option( 'rewrite_rules' );
        $this->assertArrayHasKey( 'sfssid\/success\/(.*)', $stored );
        $this->assertArrayHasKey( 'sfssid\/cancel\/(.*)', $stored );
    }

    public function test_same_version_does_not_flush_again() {
        update_option( 'super_rewrite_rules_version', SUPER_VERSION, false );
        SUPER_Forms()->rewrite_rules();
        $this->assertFalse( has_action( 'wp_loaded', array( SUPER_Forms(), 'flush_rewrite_rules_once' ) ) );
    }

    public function test_install_records_the_version_it_flushed_for() {
        delete_option( 'super_rewrite_rules_version' );
        SUPER_Install::install();
        $this->assertSame( SUPER_VERSION, get_option( 'super_rewrite_rules_version' ) );
    }
}
