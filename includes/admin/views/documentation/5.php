<code>
<?php 
ob_start();
require_once( SUPER_PLUGIN_DIR . '/docs/changelog.md' );
$changelog = ob_get_clean();
echo nl2br($changelog);
?>
</code>