<?php
/**
 * PHPUnit bootstrap for the Super Forms security tests.
 *
 * Layout: this repository root holds the harness (composer.json, phpunit.xml, tests/); the plugin is src/.
 * The verifier runs it as /workspace with the plugin at /workspace/src and WordPress' test suite at
 * /opt/wordpress-tests (copied to /workspace/wordpress because the suite deletes wp_upload_dir() on
 * every set_up and the image tree is read-only). Locally, set WP_PHPUNIT__DIR to a wordpress-develop
 * checkout's tests/phpunit and WP_TESTS_DB_* for a throwaway database.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$plugin = $root . '/src/super-forms.php';
$uploadBase = __DIR__ . '/test-security-upload-00-base.php';
$developRoot = getenv('WP_DEVELOP_DIR') !== false && getenv('WP_DEVELOP_DIR') !== '' ? rtrim((string) getenv('WP_DEVELOP_DIR'), '/') : '/opt/wordpress-tests';
$wordpressTests = getenv('WP_PHPUNIT__DIR') !== false && getenv('WP_PHPUNIT__DIR') !== '' ? rtrim((string) getenv('WP_PHPUNIT__DIR'), '/') : $developRoot . '/tests/phpunit';
$sampleConfig = $developRoot . '/wp-tests-config-sample.php';
$writableWordpress = is_dir('/workspace/wordpress') ? '/workspace/wordpress/' : $developRoot . '/src/';
$runtimeConfig = $root . '/wp-tests-config.php';

foreach (array('WP_TESTS_DB_NAME', 'WP_TESTS_DB_USER', 'WP_TESTS_DB_PASSWORD', 'WP_TESTS_DB_HOST') as $required) {
    if (getenv($required) === false || getenv($required) === '') {
        fwrite(STDERR, "PHPUnit database environment is incomplete ($required)\n");
        exit(66);
    }
}
if (!is_file($sampleConfig) || !is_file($wordpressTests . '/includes/functions.php') ||
    !is_file($wordpressTests . '/includes/bootstrap.php') || !is_file($plugin) ||
    !is_file($uploadBase) || !is_file($root . '/vendor/autoload.php') ||
    !is_file($writableWordpress . 'wp-settings.php') || !is_dir($writableWordpress . 'wp-content/themes')) {
    fwrite(STDERR, "PHPUnit harness prerequisites are missing\n");
    exit(66);
}

$config = file_get_contents($sampleConfig);
if ($config === false) {
    fwrite(STDERR, "PHPUnit could not read the WordPress test config template\n");
    exit(66);
}
$config = str_replace(
    array('youremptytestdbnamehere', 'yourusernamehere', 'yourpasswordhere', 'localhost', "dirname( __FILE__ ) . '/src/'", "__DIR__ . '/src/'"),
    array(getenv('WP_TESTS_DB_NAME'), getenv('WP_TESTS_DB_USER'), getenv('WP_TESTS_DB_PASSWORD'), getenv('WP_TESTS_DB_HOST'), var_export($writableWordpress, true), var_export($writableWordpress, true)),
    $config
);
if (file_put_contents($runtimeConfig, $config, LOCK_EX) === false) {
    fwrite(STDERR, "PHPUnit could not write its runtime WordPress test config\n");
    exit(66);
}
// wordpress-develop's bootstrap resolves the config through this constant.
define('WP_TESTS_CONFIG_FILE_PATH', $runtimeConfig);

require_once $root . '/vendor/autoload.php';
require_once $wordpressTests . '/includes/functions.php';

define('SUPER_FORMS_TESTS_PLUGIN_FILE', $plugin);
function super_forms_tests_load_plugin(): void {
    require SUPER_FORMS_TESTS_PLUGIN_FILE;
}
tests_add_filter('muplugins_loaded', 'super_forms_tests_load_plugin');
require $wordpressTests . '/includes/bootstrap.php';
require_once $uploadBase;
