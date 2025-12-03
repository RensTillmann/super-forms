<?php
/**
 * Force complete form migration
 * Run via: wp eval-file test/force-complete-migration.php
 */

if (!class_exists('SUPER_Form_Background_Migration')) {
    echo "Migration class not found\n";
    exit(1);
}

echo "Starting forced migration of all remaining forms...\n\n";

// Get initial status
$status = SUPER_Form_Background_Migration::get_migration_status();
echo "Initial status:\n";
echo "Total: {$status['total_forms']}\n";
echo "Migrated: {$status['migrated']}\n";
echo "Remaining: {$status['remaining']}\n\n";

// Process batches until complete
$batch_num = 1;
$max_batches = 600; // Safety limit (14151 forms / 25 per batch = ~567 batches)

while (true) {
    $status = SUPER_Form_Background_Migration::get_migration_status();

    if ($status['is_complete']) {
        echo "\nMigration complete!\n";
        break;
    }

    if ($batch_num > $max_batches) {
        echo "\nReached maximum batch limit. Check for errors.\n";
        break;
    }

    echo "Batch {$batch_num}: Processing...";

    // Process one batch
    SUPER_Form_Background_Migration::process_batch();

    $status = SUPER_Form_Background_Migration::get_migration_status();
    echo " Done. Migrated: {$status['migrated']}/{$status['total_forms']} ({$status['progress_percent']}%)\n";

    $batch_num++;

    // Show progress every 50 batches
    if ($batch_num % 50 === 0) {
        echo "\nProgress update after 50 batches:\n";
        echo "Migrated: {$status['migrated']}\n";
        echo "Remaining: {$status['remaining']}\n";
        echo "Failed: {$status['failed_count']}\n\n";
    }
}

// Final status
$final_status = SUPER_Form_Background_Migration::get_migration_status();
echo "\n=== FINAL STATUS ===\n";
echo "Total Forms: {$final_status['total_forms']}\n";
echo "Migrated: {$final_status['migrated']}\n";
echo "Failed: {$final_status['failed_count']}\n";
echo "Progress: {$final_status['progress_percent']}%\n";
echo "Status: {$final_status['status']}\n";

if ($final_status['failed_count'] > 0) {
    echo "\nFailed form IDs: " . implode(", ", array_slice($final_status['failed_forms'], 0, 10)) . "\n";
    if (count($final_status['failed_forms']) > 10) {
        echo "... and " . (count($final_status['failed_forms']) - 10) . " more\n";
    }
}
