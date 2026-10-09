<?php
/**
 * No real Stripe secret, restricted, webhook or publishable key may ship in the plugin package.
 * Scans every file under the plugin root (the PHPUnit runner lays out the `git archive` tree there).
 * Failures name file:line and the pattern only, never the matched value.
 */
class Test_Packaged_Secrets extends WP_UnitTestCase {
    const PATTERNS = array(
        'stripe_secret' => '/(?:sk|rk)_(?:live|test)_([A-Za-z0-9]{10,})/',
        'stripe_webhook_secret' => '/whsec_([A-Za-z0-9]{10,})/',
        'stripe_publishable' => '/pk_(?:live|test)_([A-Za-z0-9]{10,})/',
    );
    // Stripe's documented sample key bodies.
    const DOCUMENTED_SAMPLES = array( 'BQokikJOvBiI2HlWgH4olfQ2', '4eC39HqLyjWDarjtT1zdp7dc', 'TYooMQauvdEDq54NiTphI7jx' );

    public static function secret_findings( $root ) {
        $findings = array();
        $root = rtrim( str_replace( chr( 92 ), '/', $root ), '/' );
        $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
        foreach( $files as $file ) {
            $relative = substr( str_replace( chr( 92 ), '/', $file->getPathname() ), strlen( $root ) + 1 );
            // ponytail: harness-only top-level dirs (export-ignored tests/, composer vendor/) are skipped by name.
            if( preg_match( '#^(?:tests|vendor|node_modules)/#', $relative ) ) continue;
            if( !$file->isFile() ) continue;
            foreach( explode( "\n", (string) file_get_contents( $file->getPathname() ) ) as $index => $line ) {
                foreach( self::PATTERNS as $name => $pattern ) {
                    if( !preg_match_all( $pattern, $line, $matches ) ) continue;
                    foreach( $matches[1] as $body ) {
                        if( count( array_unique( str_split( $body ) ) ) <= 3 || in_array( $body, self::DOCUMENTED_SAMPLES, true ) ) continue;
                        $findings[] = $relative . ':' . ( $index + 1 ) . ' ' . $name;
                    }
                }
            }
        }
        sort( $findings );
        return $findings;
    }

    public function test_the_plugin_package_ships_no_real_stripe_keys() {
        $findings = self::secret_findings( SUPER_PLUGIN_DIR );
        $this->assertSame( array(), $findings, "Hard-coded Stripe keys in the package:\n" . implode( "\n", $findings ) );
    }

    public function test_the_scan_detects_keys_and_allows_placeholders_and_documented_samples() {
        $dir = trailingslashit( get_temp_dir() ) . 'sf-secret-scan-' . wp_generate_password( 8, false );
        wp_mkdir_p( $dir . '/tests' );
        $body = implode( '', array_map( function( $i ) { return chr( 97 + $i % 26 ) . ( $i % 10 ); }, range( 1, 12 ) ) ); // synthetic, not a key
        file_put_contents( $dir . '/a.php', "x\n'sk_" . "test_" . $body . "'\n'rk_" . "live_" . $body . "' 'whsec_" . $body . "'\n'pk_" . "test_" . $body . "'\n" );
        file_put_contents( $dir . '/b.php', "'sk_" . "test_xxxxxxxxxxxxxxxxxxxxxxxx' 'sk_" . "test_4eC39HqLyjWDarjtT1zdp7dc' 'pk_" . "test_TYooMQauvdEDq54NiTphI7jx'\n" );
        file_put_contents( $dir . '/tests/c.php', "'sk_" . "test_" . $body . "'\n" );
        $this->assertSame(
            array( 'a.php:2 stripe_secret', 'a.php:3 stripe_secret', 'a.php:3 stripe_webhook_secret', 'a.php:4 stripe_publishable' ),
            self::secret_findings( $dir )
        );
        foreach( array( 'a.php', 'b.php', 'tests/c.php' ) as $f ) unlink( $dir . '/' . $f );
        rmdir( $dir . '/tests' ); rmdir( $dir );
    }
}
