<?php
/**
 * Developer Tools Page (DEBUG mode only)
 *
 * @package Super Forms
 * @since   6.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Require admin capability
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'super-forms'));
}

// Create nonce for AJAX requests
$nonce = wp_create_nonce('super-form-builder');
?>

<div class="wrap super-developer-tools">
    <h1><?php echo esc_html__('Developer Tools', 'super-forms'); ?></h1>

    <!-- Intro Notice -->
    <div class="sfui-notice sfui-blue">
        <h3><?php echo esc_html__('About Developer Tools', 'super-forms'); ?></h3>
        <p><?php echo esc_html__('This page provides tools for testing the EAV migration system with synthetic data. Only visible when DEBUG_SF = true in wp-config.php.', 'super-forms'); ?></p>
    </div>

    <!-- Warning Notice -->
    <div class="sfui-notice sfui-yellow">
        <h3><?php echo esc_html__('Debug Mode Active', 'super-forms'); ?></h3>
        <p><?php echo esc_html__('This page is for development and testing only. Do not use in production.', 'super-forms'); ?></p>
    </div>

    <!-- Quick Actions Section -->
    <div class="super-devtools-section quick-actions-section">
        <h2><?php echo esc_html__('Quick Actions', 'super-forms'); ?></h2>
        <div class="quick-actions-bar">
            <button id="full-test-cycle-btn" class="button button-primary button-hero">
                <span class="dashicons dashicons-performance"></span>
                <?php echo esc_html__('🎯 Full Test Cycle', 'super-forms'); ?>
            </button>
            <button id="reset-everything-btn" class="button button-secondary button-hero">
                <span class="dashicons dashicons-update"></span>
                <?php echo esc_html__('🔄 Reset Everything', 'super-forms'); ?>
            </button>
            <a href="https://github.com/RensTillmann/super-forms/wiki/Developer-Tools" class="button button-secondary" target="_blank">
                <span class="dashicons dashicons-media-document"></span>
                <?php echo esc_html__('📊 Documentation', 'super-forms'); ?>
            </a>
        </div>
        <div id="full-test-cycle-progress" class="test-cycle-progress" style="display: none;">
            <h3><?php echo esc_html__('Test Cycle Progress', 'super-forms'); ?></h3>
            <div class="progress-steps">
                <div class="progress-step" data-step="generate">
                    <span class="step-icon">⏳</span>
                    <span class="step-label"><?php echo esc_html__('Generate Test Data', 'super-forms'); ?></span>
                    <span class="step-status"></span>
                </div>
                <div class="progress-step" data-step="migrate">
                    <span class="step-icon">⏳</span>
                    <span class="step-label"><?php echo esc_html__('Run Migration', 'super-forms'); ?></span>
                    <span class="step-status"></span>
                </div>
                <div class="progress-step" data-step="verify">
                    <span class="step-icon">⏳</span>
                    <span class="step-label"><?php echo esc_html__('Verify Data Integrity', 'super-forms'); ?></span>
                    <span class="step-status"></span>
                </div>
                <div class="progress-step" data-step="benchmark">
                    <span class="step-icon">⏳</span>
                    <span class="step-label"><?php echo esc_html__('Run Benchmarks', 'super-forms'); ?></span>
                    <span class="step-status"></span>
                </div>
                <div class="progress-step" data-step="report">
                    <span class="step-icon">⏳</span>
                    <span class="step-label"><?php echo esc_html__('Generate Report', 'super-forms'); ?></span>
                    <span class="step-status"></span>
                </div>
            </div>
            <div class="test-cycle-results" style="display: none;">
                <h4><?php echo esc_html__('Test Cycle Complete!', 'super-forms'); ?></h4>
                <button id="download-test-report-btn" class="button button-primary">
                    <?php echo esc_html__('📥 Download Full Report', 'super-forms'); ?>
                </button>
            </div>
        </div>
        <p class="description">
            <?php echo esc_html__('Full Test Cycle runs an automated workflow: Generate 1000 entries → Migrate → Verify → Benchmark → Report', 'super-forms'); ?>
        </p>
    </div>

    <!-- Test Data Generator Section -->
    <!-- Test Data Generator Section with Tabs -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('1. Test Data Generator', 'super-forms'); ?></h2>
        
        <!-- Tabs -->
        <div class="super-devtools-tabs">
            <button class="super-devtools-tab active" data-tab="generate"><?php echo esc_html__('Generate', 'super-forms'); ?></button>
            <button class="super-devtools-tab" data-tab="import"><?php echo esc_html__('Import', 'super-forms'); ?></button>
        </div>

        <!-- Generate Tab Content -->
        <div id="tab-generate" class="super-devtools-tab-content active">
            <p><?php echo esc_html__('Generate synthetic contact entries in old serialized format for migration testing.', 'super-forms'); ?></p>

            <!-- Entry Count -->
            <h3><?php echo esc_html__('Entry Count:', 'super-forms'); ?></h3>
            <p>
                <label><input type="radio" name="entry_count" value="10" checked> 10</label>
                <label><input type="radio" name="entry_count" value="100"> 100</label>
                <label><input type="radio" name="entry_count" value="1000"> 1,000</label>
                <label><input type="radio" name="entry_count" value="10000"> 10,000</label>
                <label><input type="radio" name="entry_count" value="custom"> Custom: <input type="number" id="custom-entry-count" min="1" max="100000" value="50" style="width: 100px;"></label>
            </p>

            <!-- Data Complexity -->
            <h3><?php echo esc_html__('Data Complexity:', 'super-forms'); ?></h3>
            <p>
                <label><input type="checkbox" name="complexity[]" value="basic_text" checked> Basic text (name, email, phone)</label><br>
                <label><input type="checkbox" name="complexity[]" value="special_chars"> Special characters (UTF-8: é, ñ, 中文, emoji)</label><br>
                <label><input type="checkbox" name="complexity[]" value="long_text"> Long text fields (&gt;10KB lorem ipsum)</label><br>
                <label><input type="checkbox" name="complexity[]" value="numeric"> Numeric values (integers, decimals)</label><br>
                <label><input type="checkbox" name="complexity[]" value="empty"> Empty/null values</label><br>
                <label><input type="checkbox" name="complexity[]" value="arrays"> Checkbox arrays (multi-select)</label><br>
                <label><input type="checkbox" name="complexity[]" value="files"> File upload URLs</label>
            </p>

            <!-- Form Assignment -->
            <h3><?php echo esc_html__('Assign to Form:', 'super-forms'); ?></h3>
            <p>
                <select id="test-form-id">
                    <option value="0"><?php echo esc_html__('None (Generic)', 'super-forms'); ?></option>
                    <?php
                    $forms = get_posts(array(
                        'post_type' => 'super_form',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    foreach ($forms as $form) :
                    ?>
                        <option value="<?php echo esc_attr($form->ID); ?>">
                            <?php echo esc_html($form->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <!-- Date Distribution -->
            <h3><?php echo esc_html__('Entry Dates:', 'super-forms'); ?></h3>
            <p>
                <label><input type="radio" name="date_mode" value="today" checked> All today</label><br>
                <label><input type="radio" name="date_mode" value="random_30_days"> Random past 30 days</label><br>
                <label><input type="radio" name="date_mode" value="random_year"> Random past year</label>
            </p>

            <!-- Actions -->
            <p>
                <button id="generate-entries-btn" class="button button-primary"><?php echo esc_html__('Generate Test Entries', 'super-forms'); ?></button>
                <button id="delete-test-entries-btn" class="button button-secondary"><?php echo esc_html__('Delete All Test Entries', 'super-forms'); ?></button>
            </p>

            <!-- Progress -->
            <div class="super-devtools-progress-bar" style="display: none;">
                <div class="super-devtools-progress-fill" style="width: 0%;"></div>
            </div>
            <span class="super-devtools-progress-text"></span>

            <!-- Log Area -->
            <div class="super-devtools-log"></div>
        </div>

        <!-- Import Tab Content -->
        <div id="tab-import" class="super-devtools-tab-content">
            <p><?php echo esc_html__('Import real contact entry data from CSV files exported from production sites. Test with real-world data patterns to validate migration performance.', 'super-forms'); ?></p>

            <!-- Pre-uploaded Test Files -->
            <h3><?php echo esc_html__('Select Pre-uploaded Test File:', 'super-forms'); ?></h3>
            <p>
                <select id="preloaded-test-file">
                    <option value=""><?php echo esc_html__('-- Select a test file --', 'super-forms'); ?></option>
                    <option value="superforms-test-data-3943-entries.csv"><?php echo esc_html__('CSV: 3,943 entries (3.4 MB)', 'super-forms'); ?></option>
                    <option value="superforms-test-data-3596-entries.csv"><?php echo esc_html__('CSV: 3,596 entries (2.8 MB)', 'super-forms'); ?></option>
                    <option value="superforms-test-data-26581-entries.csv"><?php echo esc_html__('CSV: 26,581 entries (18 MB)', 'super-forms'); ?></option>
                    <option value="superforms-import.xml"><?php echo esc_html__('XML: WordPress Export (484 MB)', 'super-forms'); ?></option>
                </select>
                <button type="button" class="button button-secondary" id="use-preloaded-btn" disabled><?php echo esc_html__('Use Selected File', 'super-forms'); ?></button>
            </p>
            <p class="description">
                <?php echo esc_html__('These are production export files pre-uploaded to the server for testing purposes.', 'super-forms'); ?>
            </p>

            <p style="text-align: center; margin: 20px 0; font-weight: 600; color: #666;">
                <?php echo esc_html__('— OR —', 'super-forms'); ?>
            </p>

            <!-- File Upload Area -->
            <h3><?php echo esc_html__('Upload Your Own CSV File:', 'super-forms'); ?></h3>

            <!-- File Upload Area -->
            <h3><?php echo esc_html__('Upload CSV File:', 'super-forms'); ?></h3>
            <div class="super-devtools-upload-area" id="csv-upload-area">
                <div class="super-devtools-upload-dropzone">
                    <input type="file" id="csv-file-input" accept=".csv" style="display: none;">
                    <p class="upload-instructions">
                        <strong><?php echo esc_html__('Drag & drop your CSV file here', 'super-forms'); ?></strong><br>
                        <?php echo esc_html__('or', 'super-forms'); ?><br>
                        <button type="button" class="button button-secondary" id="select-csv-btn"><?php echo esc_html__('Select CSV File', 'super-forms'); ?></button>
                    </p>
                    <p class="upload-hint">
                        <?php echo esc_html__('Supports Super Forms CSV export format. Files can be any size.', 'super-forms'); ?>
                    </p>
                </div>
                <div id="csv-file-info" style="display: none;">
                    <p><strong><?php echo esc_html__('Selected file:', 'super-forms'); ?></strong> <span id="csv-filename"></span></p>
                    <p><?php echo esc_html__('Size:', 'super-forms'); ?> <span id="csv-filesize"></span></p>
                </div>
            </div>

            <!-- Import Options -->
            <h3><?php echo esc_html__('Import Options:', 'super-forms'); ?></h3>
            <p>
                <label>
                    <input type="checkbox" id="tag-as-test" checked>
                    <?php echo esc_html__('Tag as test entries', 'super-forms'); ?>
                    <span class="description">(<?php echo esc_html__('Adds _super_test_entry meta for easy cleanup', 'super-forms'); ?>)</span>
                </label><br>
                <label>
                    <input type="checkbox" id="auto-migrate" checked>
                    <?php echo esc_html__('Auto-migrate after import', 'super-forms'); ?>
                    <span class="description">(<?php echo esc_html__('Automatically migrates imported entries to EAV format', 'super-forms'); ?>)</span>
                </label>
            </p>

            <!-- Actions -->
            <p>
                <button id="import-csv-btn" class="button button-primary" disabled><?php echo esc_html__('Import CSV', 'super-forms'); ?></button>
                <button id="cancel-import-btn" class="button button-secondary" style="display: none;"><?php echo esc_html__('Cancel Import', 'super-forms'); ?></button>
            </p>

            <!-- Progress -->
            <div class="super-devtools-import-progress-bar" style="display: none;">
                <div class="super-devtools-import-progress-fill" style="width: 0%;"></div>
            </div>
            <span class="super-devtools-import-progress-text"></span>

            <!-- Import Statistics -->
            <div id="import-statistics" style="display: none; margin-top: 20px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa;">
                <h4><?php echo esc_html__('Import Results:', 'super-forms'); ?></h4>
                <table class="widefat" style="max-width: 600px;">
                    <tbody>
                        <tr>
                            <th><?php echo esc_html__('Total Entries:', 'super-forms'); ?></th>
                            <td id="stat-total-entries">0</td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Successfully Imported:', 'super-forms'); ?></th>
                            <td id="stat-imported-entries">0</td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Forms:', 'super-forms'); ?></th>
                            <td id="stat-forms">0</td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Unique Fields:', 'super-forms'); ?></th>
                            <td id="stat-fields">0</td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('File Size:', 'super-forms'); ?></th>
                            <td id="stat-filesize">0</td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Import Time:', 'super-forms'); ?></th>
                            <td id="stat-import-time">0s</td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Errors:', 'super-forms'); ?></th>
                            <td id="stat-errors">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Import Log Area -->
            <div class="super-devtools-import-log"></div>
        </div>
    </div>


    <!-- Migration Controls Section -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('2. Migration Controls', 'super-forms'); ?></h2>

        <?php
        $migration_status = SUPER_Migration_Manager::get_migration_status();
        $status = !empty($migration_status) ? $migration_status['status'] : 'not_started';
        $using_storage = !empty($migration_status) ? $migration_status['using_storage'] : 'serialized';
        $total = !empty($migration_status) ? $migration_status['total_entries'] : 0;
        $migrated = !empty($migration_status) ? $migration_status['migrated_entries'] : 0;
        $progress = $total > 0 ? round(($migrated / $total) * 100, 2) : 0;
        ?>

        <!-- Current Status -->
        <table class="widefat" style="max-width: 600px;">
            <tbody>
                <tr>
                    <th style="width: 200px;"><?php echo esc_html__('Current Status:', 'super-forms'); ?></th>
                    <td>
                        <span class="migration-status-badge" data-status="<?php echo esc_attr($status); ?>">
                            <?php
                            if ($status === 'not_started') {
                                echo '<span class="sfui-badge sfui-grey">● Not Started</span>';
                            } elseif ($status === 'in_progress') {
                                echo '<span class="sfui-badge sfui-blue">● In Progress</span>';
                            } elseif ($status === 'completed') {
                                echo '<span class="sfui-badge sfui-green">● Completed</span>';
                            }
                            ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Using Storage:', 'super-forms'); ?></th>
                    <td class="migration-using-storage">
                        <?php echo esc_html($using_storage === 'eav' ? 'EAV Tables' : 'Serialized'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Progress:', 'super-forms'); ?></th>
                    <td>
                        <div class="super-migration-progress-bar">
                            <div class="super-migration-progress-fill migration-progress-fill" style="width: <?php echo esc_attr($progress); ?>%;"></div>
                        </div>
                        <span class="migration-progress-text"><?php echo esc_html(number_format($migrated)); ?> / <?php echo esc_html(number_format($total)); ?> (<?php echo esc_html($progress); ?>%)</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Background Migration Status -->
        <?php
        $bg_status = class_exists('SUPER_Background_Migration') ? SUPER_Background_Migration::get_status() : array();
        $bg_enabled = !empty($bg_status['enabled']) ? $bg_status['enabled'] : false;
        $bg_locked = !empty($bg_status['locked']) ? $bg_status['locked'] : false;
        $using_as = !empty($bg_status['using_action_scheduler']) ? $bg_status['using_action_scheduler'] : false;
        $has_scheduled = !empty($bg_status['has_scheduled_batches']) ? $bg_status['has_scheduled_batches'] : false;
        $last_batch = !empty($migration_status['last_batch_processed_at']) ? $migration_status['last_batch_processed_at'] : '';
        $triggered_by = !empty($migration_status['auto_triggered_by']) ? $migration_status['auto_triggered_by'] : '';
        $health_checks = !empty($migration_status['health_check_count']) ? $migration_status['health_check_count'] : 0;
        ?>

        <h3 style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
            <?php echo esc_html__('Background Migration:', 'super-forms'); ?>
        </h3>

        <table class="widefat" style="max-width: 600px;">
            <tbody>
                <tr>
                    <th style="width: 200px;"><?php echo esc_html__('Background Processing:', 'super-forms'); ?></th>
                    <td>
                        <?php if ($bg_enabled): ?>
                            <span class="sfui-badge sfui-green">● Enabled</span>
                        <?php else: ?>
                            <span class="sfui-badge sfui-grey">○ Disabled</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Processing Engine:', 'super-forms'); ?></th>
                    <td>
                        <?php if ($using_as): ?>
                            <strong>Action Scheduler</strong>
                            <?php if (function_exists('as_get_scheduler')): ?>
                                (<a href="<?php echo admin_url('tools.php?page=action-scheduler'); ?>" target="_blank">View Queue</a>)
                            <?php endif; ?>
                        <?php else: ?>
                            WP-Cron (Fallback)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Current State:', 'super-forms'); ?></th>
                    <td>
                        <?php if ($bg_locked): ?>
                            <span class="sfui-badge sfui-blue">● Processing</span>
                        <?php elseif ($has_scheduled): ?>
                            <span class="sfui-badge sfui-orange">● Scheduled</span>
                        <?php else: ?>
                            <span class="sfui-badge sfui-grey">○ Idle</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($last_batch): ?>
                <tr>
                    <th><?php echo esc_html__('Last Batch Processed:', 'super-forms'); ?></th>
                    <td><?php echo esc_html($last_batch); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($triggered_by): ?>
                <tr>
                    <th><?php echo esc_html__('Triggered By:', 'super-forms'); ?></th>
                    <td><?php echo esc_html(ucfirst($triggered_by)); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php echo esc_html__('Health Checks:', 'super-forms'); ?></th>
                    <td><?php echo number_format($health_checks); ?> completed</td>
                </tr>
            </tbody>
        </table>

        <p style="background: #f0f8ff; padding: 12px; border-left: 4px solid #2271b1; margin-top: 15px;">
            <strong>ℹ️ About Background Migration:</strong><br>
            When enabled, migration runs completely in the background. You can safely close your browser—the migration will continue on the server. Daily health checks ensure completion even if interrupted. Entries are verified after migration and serialized data is preserved as backup.
        </p>

        <!-- Actions -->
        <h3 style="margin-top: 20px;"><?php echo esc_html__('Actions:', 'super-forms'); ?></h3>
        <p>
            <button id="migration-start-btn" class="button button-primary">
                <?php echo esc_html__('▶️ Start Migration', 'super-forms'); ?>
            </button>
            <button id="migration-pause-btn" class="button button-secondary" style="display: none;">
                <?php echo esc_html__('⏸️ Pause', 'super-forms'); ?>
            </button>
        </p>

        <!-- Advanced Controls -->
        <h3><?php echo esc_html__('Advanced:', 'super-forms'); ?></h3>
        <p>
            <button id="migration-reset-btn" class="button button-secondary">
                <?php echo esc_html__('🔄 Reset to Not Started', 'super-forms'); ?>
            </button>
            <button id="migration-force-complete-btn" class="button button-secondary">
                <?php echo esc_html__('⚡ Force Complete (skip migration)', 'super-forms'); ?>
            </button>
            <button id="migration-rollback-btn" class="button button-secondary">
                <?php echo esc_html__('🔙 Rollback to Serialized', 'super-forms'); ?>
            </button>
            <button id="migration-force-eav-btn" class="button button-secondary">
                <?php echo esc_html__('⏩ Force Switch to EAV', 'super-forms'); ?>
            </button>
        </p>

        <!-- Settings -->
        <h3><?php echo esc_html__('Settings:', 'super-forms'); ?></h3>
        <p>
            <label>
                <?php echo esc_html__('Batch Size:', 'super-forms'); ?>
                <select id="migration-batch-size">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </label>
            &nbsp;&nbsp;
            <label>
                <?php echo esc_html__('Delay:', 'super-forms'); ?>
                <select id="migration-delay">
                    <option value="0">0ms</option>
                    <option value="100">100ms</option>
                    <option value="250">250ms</option>
                    <option value="500" selected>500ms</option>
                    <option value="1000">1000ms</option>
                </select>
            </label>
        </p>

        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=super_migration')); ?>" class="button button-secondary">
                <?php echo esc_html__('Open Migration Admin Page →', 'super-forms'); ?>
            </a>
        </p>

        <!-- Failed Entries Section -->
        <?php
        $failed_entries = !empty($migration_status['failed_entries']) ? $migration_status['failed_entries'] : array();
        if (!empty($failed_entries)) :
        ?>
        <div class="failed-entries-section" style="margin-top: 30px;">
            <h3 style="color: #d32f2f;"><?php echo esc_html__('Failed Entries', 'super-forms'); ?> (<?php echo count($failed_entries); ?> entries)</h3>
            <p><?php echo esc_html__('The following entries failed to migrate. You can view the diff to see what went wrong and retry individual entries.', 'super-forms'); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;"><?php echo esc_html__('Entry ID', 'super-forms'); ?></th>
                        <th style="width: 200px;"><?php echo esc_html__('Failed At', 'super-forms'); ?></th>
                        <th><?php echo esc_html__('Reason', 'super-forms'); ?></th>
                        <th style="width: 300px;"><?php echo esc_html__('Actions', 'super-forms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($failed_entries as $entry_id => $error_message) :
                        $entry = get_post($entry_id);
                        $failed_at = !empty($entry) ? get_the_modified_time('Y-m-d H:i:s', $entry) : 'Unknown';
                    ?>
                    <tr data-entry-id="<?php echo esc_attr($entry_id); ?>">
                        <td><strong>#<?php echo esc_html($entry_id); ?></strong></td>
                        <td><?php echo esc_html($failed_at); ?></td>
                        <td><?php echo esc_html($error_message); ?></td>
                        <td>
                            <button class="button button-small view-diff-btn" data-entry-id="<?php echo esc_attr($entry_id); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php echo esc_html__('View Diff', 'super-forms'); ?>
                            </button>
                            <button class="button button-small retry-entry-btn" data-entry-id="<?php echo esc_attr($entry_id); ?>">
                                <span class="dashicons dashicons-update"></span>
                                <?php echo esc_html__('Retry', 'super-forms'); ?>
                            </button>
                            <?php if (!empty($entry)) : ?>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $entry_id . '&action=edit')); ?>" class="button button-small" target="_blank">
                                <span class="dashicons dashicons-edit"></span>
                                <?php echo esc_html__('Edit Entry', 'super-forms'); ?>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top: 15px;">
                <button id="retry-all-failed-btn" class="button button-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php echo esc_html__('Retry All Failed Entries', 'super-forms'); ?>
                </button>
            </p>
        </div>
        <?php endif; ?>

        <!-- Log Area -->
        <div class="migration-log"></div>
    </div>

    <!-- Automated Verification Section -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('3. Automated Verification', 'super-forms'); ?></h2>
        <p><?php echo esc_html__('Run automated tests to verify data integrity after migration.', 'super-forms'); ?></p>

        <p>
            <button id="run-all-tests-btn" class="button button-primary">
                <?php echo esc_html__('▶️ Run All Tests', 'super-forms'); ?>
            </button>
            <button id="run-selected-tests-btn" class="button button-secondary">
                <?php echo esc_html__('Run Selected Tests', 'super-forms'); ?>
            </button>
        </p>

        <h3><?php echo esc_html__('Tests:', 'super-forms'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th style="width: 30px;"><input type="checkbox" id="select-all-tests"></th>
                    <th><?php echo esc_html__('Test', 'super-forms'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Status', 'super-forms'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Time', 'super-forms'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="data_integrity" checked></td>
                    <td>Data Integrity (EAV ↔ Serialized)</td>
                    <td class="test-status" data-test="data_integrity">⏸️ Idle</td>
                    <td class="test-time" data-test="data_integrity">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="field_count_match" checked></td>
                    <td>Field Count Match</td>
                    <td class="test-status" data-test="field_count_match">⏸️ Idle</td>
                    <td class="test-time" data-test="field_count_match">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="field_values_match" checked></td>
                    <td>Field Values Match</td>
                    <td class="test-status" data-test="field_values_match">⏸️ Idle</td>
                    <td class="test-time" data-test="field_values_match">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="csv_export_comparison" checked></td>
                    <td>CSV Export Byte-Comparison</td>
                    <td class="test-status" data-test="csv_export_comparison">⏸️ Idle</td>
                    <td class="test-time" data-test="csv_export_comparison">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="csv_import_roundtrip" checked></td>
                    <td>CSV Import Roundtrip</td>
                    <td class="test-status" data-test="csv_import_roundtrip">⏸️ Idle</td>
                    <td class="test-time" data-test="csv_import_roundtrip">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="listings_query_accuracy" checked></td>
                    <td>Listings Query Accuracy</td>
                    <td class="test-status" data-test="listings_query_accuracy">⏸️ Idle</td>
                    <td class="test-time" data-test="listings_query_accuracy">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="search_query_accuracy" checked></td>
                    <td>Search Query Accuracy</td>
                    <td class="test-status" data-test="search_query_accuracy">⏸️ Idle</td>
                    <td class="test-time" data-test="search_query_accuracy">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="bulk_fetch_consistency" checked></td>
                    <td>Bulk Fetch Consistency</td>
                    <td class="test-status" data-test="bulk_fetch_consistency">⏸️ Idle</td>
                    <td class="test-time" data-test="bulk_fetch_consistency">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="empty_entry_handling" checked></td>
                    <td>Empty Entry Handling</td>
                    <td class="test-status" data-test="empty_entry_handling">⏸️ Idle</td>
                    <td class="test-time" data-test="empty_entry_handling">--</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="tests[]" value="special_characters_preservation" checked></td>
                    <td>Special Characters Preservation</td>
                    <td class="test-status" data-test="special_characters_preservation">⏸️ Idle</td>
                    <td class="test-time" data-test="special_characters_preservation">--</td>
                </tr>
            </tbody>
        </table>

        <p class="verification-summary">
            <strong><?php echo esc_html__('Summary:', 'super-forms'); ?></strong>
            <span class="summary-text">0/10 passed, 0 failed, 10 not run</span>
        </p>

        <div class="verification-results"></div>

        <p>
            <button id="download-test-report-json" class="button button-secondary" disabled>
                <?php echo esc_html__('Download Test Report (JSON)', 'super-forms'); ?>
            </button>
        </p>
    </div>

    <!-- Performance Benchmarks Section -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('4. Performance Benchmarks', 'super-forms'); ?></h2>
        <p><?php echo esc_html__('Measure real-world performance improvements of EAV storage vs serialized storage.', 'super-forms'); ?></p>

        <p>
            <button id="run-all-benchmarks-btn" class="button button-primary">
                <?php echo esc_html__('▶️ Run All Benchmarks', 'super-forms'); ?>
            </button>
            <button id="run-selected-benchmarks-btn" class="button button-secondary">
                <?php echo esc_html__('Run Selected', 'super-forms'); ?>
            </button>
        </p>

        <p>
            <label>
                <?php echo esc_html__('Entry Count for Tests:', 'super-forms'); ?>
                <select id="benchmark-entry-count">
                    <option value="10">10</option>
                    <option value="100" selected>100</option>
                    <option value="1000">1,000</option>
                    <option value="10000">10,000</option>
                </select>
            </label>
        </p>

        <h3><?php echo esc_html__('Benchmarks:', 'super-forms'); ?></h3>

        <!-- CSV Export Benchmark -->
        <div class="benchmark-result" data-benchmark="csv_export" style="display: none;">
            <h4>
                <input type="checkbox" name="benchmarks[]" value="csv_export" checked>
                <?php echo esc_html__('CSV Export (N entries)', 'super-forms'); ?>
            </h4>
            <div class="benchmark-bars">
                <div class="benchmark-bar-row">
                    <span class="benchmark-label">Serialized:</span>
                    <div class="benchmark-bar-container">
                        <div class="benchmark-bar serialized" style="width: 0%"></div>
                    </div>
                    <span class="benchmark-time serialized">--</span>
                </div>
                <div class="benchmark-bar-row">
                    <span class="benchmark-label">EAV:</span>
                    <div class="benchmark-bar-container">
                        <div class="benchmark-bar eav" style="width: 0%"></div>
                    </div>
                    <span class="benchmark-time eav">--</span>
                </div>
            </div>
            <p class="benchmark-improvement">Improvement: <strong>--</strong></p>
        </div>

        <!-- Listings Filter Benchmark -->
        <div class="benchmark-result" data-benchmark="listings_filter" style="display: none;">
            <h4>
                <input type="checkbox" name="benchmarks[]" value="listings_filter" checked>
                <?php echo esc_html__('Listings Filter (field="email" value="@test.com")', 'super-forms'); ?>
            </h4>
            <div class="benchmark-bars">
                <div class="benchmark-bar-row">
                    <span class="benchmark-label">Serialized:</span>
                    <div class="benchmark-bar-container">
                        <div class="benchmark-bar serialized" style="width: 0%"></div>
                    </div>
                    <span class="benchmark-time serialized">--</span>
                </div>
                <div class="benchmark-bar-row">
                    <span class="benchmark-label">EAV:</span>
                    <div class="benchmark-bar-container">
                        <div class="benchmark-bar eav" style="width: 0%"></div>
                    </div>
                    <span class="benchmark-time eav">--</span>
                </div>
            </div>
            <p class="benchmark-improvement">Improvement: <strong>--</strong></p>
        </div>

        <!-- Admin Search Benchmark -->
        <div class="benchmark-result" data-benchmark="admin_search" style="display: none;">
            <h4>
                <input type="checkbox" name="benchmarks[]" value="admin_search" checked>
                <?php echo esc_html__('Admin Search (keyword="test")', 'super-forms'); ?>
            </h4>
            <div class="benchmark-bars">
                <div class="benchmark-bar-row">
                    <span class="benchmark-label">Serialized:</span>
                    <div class="benchmark-bar-container">
                        <div class="benchmark-bar serialized" style="width: 0%"></div>
                    </div>
                    <span class="benchmark-time serialized">--</span>
                </div>
                <div class="benchmark-bar-row">
                    <span class="benchmark-label">EAV:</span>
                    <div class="benchmark-bar-container">
                        <div class="benchmark-bar eav" style="width: 0%"></div>
                    </div>
                    <span class="benchmark-time eav">--</span>
                </div>
            </div>
            <p class="benchmark-improvement">Improvement: <strong>--</strong></p>
        </div>

        <p style="margin-top: 20px;">
            <button id="download-benchmark-report" class="button button-secondary" disabled>
                <?php echo esc_html__('Download Benchmark Report', 'super-forms'); ?>
            </button>
            <button id="compare-with-previous" class="button button-secondary" disabled>
                <?php echo esc_html__('Compare with Previous', 'super-forms'); ?>
            </button>
        </p>

        <div id="benchmark-comparison-display" style="display: none; margin-top: 20px;">
            <h4><?php echo esc_html__('Comparison with Previous Run:', 'super-forms'); ?></h4>
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Benchmark', 'super-forms'); ?></th>
                        <th><?php echo esc_html__('Previous', 'super-forms'); ?></th>
                        <th><?php echo esc_html__('Current', 'super-forms'); ?></th>
                        <th><?php echo esc_html__('Change', 'super-forms'); ?></th>
                    </tr>
                </thead>
                <tbody id="comparison-table-body">
                    <!-- Populated via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Database Inspector Section -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('5. Database Inspector', 'super-forms'); ?></h2>
        <p><?php echo esc_html__('View database state and statistics.', 'super-forms'); ?></p>

        <button id="refresh-db-stats-btn" class="button button-secondary">
            <?php echo esc_html__('🔄 Refresh Statistics', 'super-forms'); ?>
        </button>

        <h3><?php echo esc_html__('wp_postmeta (Serialized):', 'super-forms'); ?></h3>
        <p>
            └─ _super_contact_entry_data: <strong class="serialized-count">--</strong> rows
        </p>

        <h3><?php echo esc_html__('wp_superforms_entry_data (EAV):', 'super-forms'); ?></h3>
        <ul class="eav-stats">
            <li>└─ Total rows: <strong class="eav-total-rows">--</strong></li>
            <li>└─ Unique entries: <strong class="eav-unique-entries">--</strong></li>
            <li>└─ Unique field names: <strong class="eav-unique-fields">--</strong></li>
            <li>└─ Avg fields per entry: <strong class="eav-avg-fields">--</strong></li>
            <li>└─ Table size: <strong class="eav-table-size">--</strong> MB</li>
        </ul>

        <h3><?php echo esc_html__('Index Status:', 'super-forms'); ?></h3>
        <ul class="index-status">
            <!-- Populated via JavaScript -->
        </ul>

        <p>
            <button id="run-analyze-table-btn" class="button button-secondary">
                <?php echo esc_html__('Run ANALYZE TABLE', 'super-forms'); ?>
            </button>
            <button id="view-sample-entry-btn" class="button button-secondary">
                <?php echo esc_html__('View Sample Entry Data', 'super-forms'); ?>
            </button>
        </p>

        <div id="sample-entry-data-display" style="display: none; margin-top: 20px;">
            <h4><?php echo esc_html__('Sample Entry Data:', 'super-forms'); ?></h4>
            <pre id="sample-entry-data-content" style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;"></pre>
        </div>

        <div id="analyze-table-result" style="display: none; margin-top: 20px;">
            <h4><?php echo esc_html__('ANALYZE TABLE Result:', 'super-forms'); ?></h4>
            <pre id="analyze-table-content" style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; font-family: monospace; font-size: 12px;"></pre>
        </div>
    </div>

    <!-- Cleanup & Reset Section -->
    <div class="super-devtools-section">
        <h2 style="color: #d32f2f;"><?php echo esc_html__('6. Cleanup & Reset', 'super-forms'); ?></h2>
        <div class="sfui-notice" style="border-left-color: #d32f2f; background: #ffebee;">
            <h3>⚠️ <?php echo esc_html__('DANGER ZONE', 'super-forms'); ?></h3>
            <p><?php echo esc_html__('These actions affect the database. Use with caution.', 'super-forms'); ?></p>
        </div>

        <h3><?php echo esc_html__('Selective Cleanup:', 'super-forms'); ?></h3>
        <p>
            <button id="delete-test-entries-btn" class="button button-secondary">
                <?php echo esc_html__('Delete Test Entries Only', 'super-forms'); ?>
            </button>
            <span class="test-entries-count">(0 found)</span>
        </p>
        <p>
            <button id="delete-all-eav-btn" class="button button-secondary">
                <?php echo esc_html__('Delete All EAV Data', 'super-forms'); ?>
            </button>
            <span class="description">(keeps serialized)</span>
        </p>
        <p>
            <button id="delete-all-serialized-btn" class="button button-secondary">
                <?php echo esc_html__('Delete All Serialized Data', 'super-forms'); ?>
            </button>
            <span class="description">(keeps EAV)</span>
        </p>

        <h3 style="color: #d32f2f; margin-top: 30px;"><?php echo esc_html__('⚠️ Nuclear Option:', 'super-forms'); ?></h3>
        <div class="sfui-notice" style="border-left-color: #d32f2f; background: #ffebee; margin-bottom: 15px;">
            <p style="margin: 0; font-weight: 600;">
                <?php echo esc_html__('This will delete ALL contact entries (test and real), all EAV data, all serialized data, and reset migration status. This CANNOT be undone!', 'super-forms'); ?>
            </p>
        </div>
        <p>
            <button id="delete-everything-reset-btn" class="button" style="background: #d32f2f; color: #fff; border-color: #b71c1c;">
                <?php echo esc_html__('🗑️ Delete Everything & Reset', 'super-forms'); ?>
            </button>
        </p>

        <h3><?php echo esc_html__('Database Maintenance:', 'super-forms'); ?></h3>
        <p>
            <button id="optimize-tables-btn" class="button button-secondary">
                <?php echo esc_html__('Optimize EAV Tables', 'super-forms'); ?>
            </button>
            <button id="rebuild-indexes-btn" class="button button-secondary">
                <?php echo esc_html__('Rebuild Indexes', 'super-forms'); ?>
            </button>
            <button id="vacuum-orphaned-btn" class="button button-secondary">
                <?php echo esc_html__('Vacuum Orphaned Data', 'super-forms'); ?>
            </button>
        </p>
    </div>

    <!-- Developer Utilities Section -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('7. Developer Utilities', 'super-forms'); ?></h2>

        <p>
            <button id="export-migration-status-btn" class="button button-secondary">
                <?php echo esc_html__('Export Migration Status (JSON)', 'super-forms'); ?>
            </button>
            <button id="view-migration-logs-btn" class="button button-secondary">
                <?php echo esc_html__('View Migration Logs', 'super-forms'); ?>
            </button>
            <button id="view-php-errors-btn" class="button button-secondary">
                <?php echo esc_html__('View PHP Error Log', 'super-forms'); ?>
            </button>
            <button id="toggle-query-debug-btn" class="button button-secondary">
                <?php echo esc_html__('Enable Query Debugging', 'super-forms'); ?>
            </button>
        </p>

        <div id="migration-logs-display" style="display: none; margin-top: 20px;">
            <h4><?php echo esc_html__('Migration Logs (Last 100 entries):', 'super-forms'); ?></h4>
            <pre id="migration-logs-content" style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 11px; line-height: 1.4;"></pre>
        </div>

        <div id="php-errors-display" style="display: none; margin-top: 20px;">
            <h4><?php echo esc_html__('PHP Error Log (Last 100 entries):', 'super-forms'); ?></h4>
            <pre id="php-errors-content" style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 11px; line-height: 1.4;"></pre>
        </div>

        <div id="query-debug-status" style="display: none; margin-top: 20px; padding: 15px; background: #e7f5fe; border-left: 4px solid #00a0d2;">
            <strong><?php echo esc_html__('Query Debugging:', 'super-forms'); ?></strong> <span id="query-debug-state">Disabled</span>
            <div id="query-debug-output" style="margin-top: 10px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 11px;"></div>
        </div>
        </p>

        <h3><?php echo esc_html__('Quick SQL:', 'super-forms'); ?></h3>
        <p>
            <select id="quick-sql-templates" style="width: 400px;">
                <option value="">-- Select Query --</option>
                <option value="count_eav_total">Count EAV Total Rows</option>
                <option value="count_eav_entries">Count EAV Unique Entries</option>
                <option value="count_serialized">Count Serialized Entries</option>
                <option value="show_indexes">Show EAV Indexes</option>
                <option value="table_stats">EAV Table Statistics</option>
                <option value="recent_entries">Recent Entries</option>
                <option value="field_names">Field Names with Counts</option>
                <option value="entry_count_by_form">Entry Count by Form</option>
                <option value="test_entry_count">Test Entry Count</option>
            </select>
            <button id="execute-sql-btn" class="button button-secondary">
                <?php echo esc_html__('Execute Query', 'super-forms'); ?>
            </button>
        </p>

        <div class="sql-results" style="display: none;">
            <h4><?php echo esc_html__('Results:', 'super-forms'); ?></h4>
            <pre class="sql-results-content"></pre>
        </div>
    </div>

    <!-- Hidden nonce for AJAX -->
    <input type="hidden" id="super-devtools-nonce" value="<?php echo esc_attr($nonce); ?>" />
</div>

<style>
.super-developer-tools {
    margin: 20px 20px 20px 0;
}

.super-devtools-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.super-devtools-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.sfui-notice {
    padding: 15px;
    margin: 20px 0;
    border-left: 4px solid #0073aa;
    background: #fff;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sfui-notice h3 {
    margin-top: 0;
    font-size: 14px;
}

.sfui-notice.sfui-blue {
    border-left-color: #0073aa;
}

.sfui-notice.sfui-yellow {
    border-left-color: #f0b429;
}

.sfui-notice ul {
    margin: 10px 0 10px 20px;
}

.sfui-notice ul li {
    margin: 5px 0;
}

/* Quick Actions Section Styles */
.quick-actions-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: #fff;
}

.quick-actions-section h2 {
    color: #fff;
    border-bottom-color: rgba(255,255,255,0.3);
}

.quick-actions-section .description {
    color: rgba(255,255,255,0.9);
}

.quick-actions-bar {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.quick-actions-bar .button-hero {
    font-size: 16px;
    padding: 10px 30px;
    height: auto;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.quick-actions-bar .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.test-cycle-progress {
    background: rgba(255,255,255,0.95);
    padding: 20px;
    border-radius: 5px;
    margin: 20px 0;
    color: #333;
}

.test-cycle-progress h3 {
    margin-top: 0;
    color: #333;
}

.progress-steps {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.progress-step {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
    border-left: 4px solid #ddd;
    transition: all 0.3s ease;
}

.progress-step.active {
    border-left-color: #667eea;
    background: #f0f4ff;
}

.progress-step.completed {
    border-left-color: #4caf50;
    background: #f1f8f4;
}

.progress-step.error {
    border-left-color: #f44336;
    background: #fff5f5;
}

.progress-step .step-icon {
    font-size: 24px;
    width: 30px;
    text-align: center;
}

.progress-step .step-label {
    flex: 1;
    font-weight: 600;
    color: #333;
}

.progress-step .step-status {
    font-size: 12px;
    color: #666;
}

.test-cycle-results {
    margin-top: 20px;
    padding: 20px;
    background: #f0f8f4;
    border-radius: 5px;
    text-align: center;
}

.test-cycle-results h4 {
    color: #4caf50;
    margin-top: 0;
}

.super-devtools-progress-bar {
    width: 100%;
    height: 30px;
    background: #e0e0e0;
    margin: 10px 0;
    border-radius: 3px;
    overflow: hidden;
}

.super-devtools-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4caf50, #388e3c);
    transition: width 0.3s ease;
}

.super-devtools-progress-text {
    font-weight: bold;
    color: #333;
}

.super-devtools-log {
    margin-top: 15px;
    max-height: 300px;
    overflow-y: auto;
    background: #f5f5f5;
    padding: 10px;
    border: 1px solid #ddd;
    font-family: monospace;
    font-size: 12px;
    display: none;
}

.super-devtools-log div {
    padding: 2px 0;
    border-bottom: 1px solid #e0e0e0;
}

.super-devtools-log div:last-child {
    border-bottom: none;
}

/* Migration Controls Styles */
.super-migration-progress-bar {
    width: 100%;
    height: 20px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
    margin: 5px 0;
}

.super-migration-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2196f3, #1976d2);
    transition: width 0.3s ease;
}

.migration-progress-text {
    font-size: 13px;
    color: #333;
}

.migration-log {
    margin-top: 15px;
    max-height: 250px;
    overflow-y: auto;
    background: #f5f5f5;
    padding: 10px;
    border: 1px solid #ddd;
    font-family: monospace;
    font-size: 12px;
    display: none;
}

.migration-log div {
    padding: 2px 0;
    border-bottom: 1px solid #e0e0e0;
}

.migration-log div:last-child {
    border-bottom: none;
}

.sfui-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.sfui-badge.sfui-grey {
    background: #9e9e9e;
    color: #fff;
}

.sfui-badge.sfui-blue {
    background: #2196f3;
    color: #fff;
}

.sfui-badge.sfui-green {
    background: #4caf50;
    color: #fff;
}

/* Verification Test Styles */
.verification-tests-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

.verification-tests-table th,
.verification-tests-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

.verification-tests-table th {
    background: #f5f5f5;
    font-weight: 600;
}

.verification-tests-table td input[type="checkbox"] {
    margin: 0;
}

.test-status {
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 3px;
    display: inline-block;
}

.test-passed {
    color: #4caf50;
}

.test-failed {
    color: #f44336;
}

.test-time {
    color: #666;
    font-size: 12px;
}

.verification-summary {
    margin: 15px 0;
    padding: 15px;
    background: #f5f5f5;
    border-left: 4px solid #2196f3;
}

.summary-text {
    font-weight: 600;
    color: #333;
}

.verification-results {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
}

.verification-result {
    padding: 10px;
    margin: 10px 0;
    border-left: 4px solid #ddd;
}

.verification-result.result-pass {
    border-left-color: #4caf50;
    background: #f1f8f4;
}

.verification-result.result-fail {
    border-left-color: #f44336;
    background: #fef1f0;
}

.verification-result strong {
    display: block;
    margin-bottom: 5px;
}

.verification-result ul {
    margin: 10px 0 0 20px;
    font-size: 12px;
    color: #666;
}

.verification-result ul li {
    margin: 5px 0;
}

/* Benchmark Styles */
.benchmark-result {
    background: #f9f9f9;
    padding: 15px;
    margin: 10px 0;
    border-left: 3px solid #0073aa;
}

.benchmark-result h4 {
    margin-top: 0;
}

.benchmark-bars {
    margin: 15px 0;
}

.benchmark-bar-row {
    display: flex;
    align-items: center;
    margin: 8px 0;
}

.benchmark-label {
    width: 100px;
    font-weight: bold;
}

.benchmark-bar-container {
    flex: 1;
    height: 30px;
    background: #e0e0e0;
    position: relative;
    margin: 0 10px;
}

.benchmark-bar {
    height: 100%;
    transition: width 0.5s ease;
}

.benchmark-bar.serialized {
    background: linear-gradient(90deg, #f44336, #d32f2f);
}

.benchmark-bar.eav {
    background: linear-gradient(90deg, #4caf50, #388e3c);
}

.benchmark-time {
    width: 100px;
    text-align: right;
    font-family: monospace;
}

.benchmark-improvement {
    font-size: 16px;
    margin: 10px 0;
}

.benchmark-improvement strong {
    color: #4caf50;
    font-size: 20px;
}

/* Tab Styles */
.super-devtools-tabs {
    display: flex;
    border-bottom: 2px solid #ccc;
    margin-bottom: 20px;
}

.super-devtools-tab {
    background: #f5f5f5;
    border: 1px solid #ccc;
    border-bottom: none;
    padding: 10px 20px;
    cursor: pointer;
    margin-right: 5px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.super-devtools-tab:hover {
    background: #e0e0e0;
}

.super-devtools-tab.active {
    background: #fff;
    border-bottom: 2px solid #fff;
    margin-bottom: -2px;
    color: #0073aa;
}

.super-devtools-tab-content {
    display: none;
}

.super-devtools-tab-content.active {
    display: block;
}

/* Upload Area Styles */
.super-devtools-upload-area {
    margin: 20px 0;
}

.super-devtools-upload-dropzone {
    border: 3px dashed #ccc;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    background: #fafafa;
    transition: all 0.3s ease;
    cursor: pointer;
}

.super-devtools-upload-dropzone:hover {
    border-color: #0073aa;
    background: #f0f8ff;
}

.super-devtools-upload-dropzone.drag-over {
    border-color: #0073aa;
    background: #e7f5fe;
    border-style: solid;
}

.super-devtools-upload-dropzone .upload-instructions {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.super-devtools-upload-dropzone .upload-instructions strong {
    font-size: 18px;
    color: #0073aa;
}

.super-devtools-upload-dropzone .upload-hint {
    margin: 10px 0 0 0;
    font-size: 12px;
    color: #666;
}

#csv-file-info {
    margin-top: 15px;
    padding: 15px;
    background: #e7f5fe;
    border-left: 4px solid #0073aa;
}

#csv-file-info p {
    margin: 5px 0;
}

/* Import Progress Styles */
.super-devtools-import-progress-bar {
    width: 100%;
    height: 30px;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    margin: 20px 0;
}

.super-devtools-import-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa 0%, #00a0d2 100%);
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.super-devtools-import-progress-text {
    display: block;
    margin-top: 10px;
    color: #333;
    font-weight: 600;
}

.super-devtools-import-log {
    margin-top: 20px;
    padding: 15px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 400px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.6;
}

.super-devtools-import-log .log-entry {
    margin-bottom: 5px;
}

.super-devtools-import-log .log-success {
    color: #4caf50;
}

.super-devtools-import-log .log-error {
    color: #d32f2f;
}

.super-devtools-import-log .log-info {
    color: #0073aa;
}

/* Diff Viewer Modal Styles */
.diff-viewer-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100000;
    overflow-y: auto;
}

.diff-viewer-modal {
    background: #fff;
    max-width: 900px;
    margin: 50px auto;
    padding: 30px;
    border-radius: 4px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
    position: relative;
}

.diff-viewer-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    background: none;
    border: none;
    padding: 5px 10px;
}

.diff-viewer-modal-close:hover {
    color: #333;
}

.diff-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.diff-table thead th {
    background: #f5f5f5;
    padding: 10px;
    text-align: left;
    border-bottom: 2px solid #ddd;
    font-weight: 600;
}

.diff-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

.diff-table tr.match td {
    background: #f0f9ff;
}

.diff-table tr.mismatch td {
    background: #fff3cd;
}

.diff-table tr.missing td {
    background: #f8d7da;
}

.diff-table .status-icon {
    width: 20px;
    height: 20px;
    display: inline-block;
}

.diff-table .dashicons {
    font-size: 20px;
    line-height: 1;
}

.diff-table .dashicons-yes-alt {
    color: #46b450;
}

.diff-table .dashicons-warning {
    color: #f0b849;
}

.diff-table .dashicons-dismiss {
    color: #dc3232;
}

/* Responsive Design - WordPress Admin Breakpoints */

/* Tablet and below (782px is WordPress admin mobile breakpoint) */
@media screen and (max-width: 782px) {
    .super-developer-tools {
        margin: 10px 10px 10px 0;
    }

    .super-devtools-section {
        padding: 15px;
        margin: 15px 0;
    }

    .quick-actions-bar {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .quick-actions-bar .button {
        width: 100%;
        margin: 0 !important;
    }

    .super-devtools-section h2,
    .super-devtools-section h3 {
        font-size: 16px;
    }

    /* Stack buttons vertically */
    .super-devtools-section p .button {
        display: block;
        width: 100%;
        margin: 5px 0 !important;
        text-align: center;
    }

    /* Make tables scrollable */
    .widefat {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    /* Form controls full width */
    .super-devtools-section select,
    .super-devtools-section input[type="text"],
    .super-devtools-section input[type="number"] {
        width: 100%;
        max-width: none;
    }

    /* Tabs */
    .super-devtools-tabs {
        display: flex;
        flex-wrap: wrap;
    }

    .super-devtools-tab {
        flex: 1;
        min-width: 120px;
    }

    /* Progress steps */
    .progress-steps {
        flex-direction: column;
    }

    .progress-step {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #ddd;
        padding: 10px;
    }

    .progress-step:last-child {
        border-bottom: none;
    }

    /* Benchmark result bars */
    .benchmark-result {
        padding: 10px;
    }

    .benchmark-bars {
        flex-direction: column;
    }

    .benchmark-bar {
        margin: 5px 0;
    }

    /* Modal */
    .diff-viewer-modal {
        width: 95%;
        max-width: none;
        margin: 20px auto;
        max-height: 90vh;
    }

    .diff-table {
        font-size: 12px;
    }

    .diff-table th,
    .diff-table td {
        padding: 8px 4px;
    }
}

/* Mobile phones (600px and below) */
@media screen and (max-width: 600px) {
    .super-developer-tools {
        margin: 5px;
    }

    .super-devtools-section {
        padding: 10px;
        margin: 10px 0;
    }

    .super-devtools-section h2 {
        font-size: 14px;
        padding-bottom: 8px;
    }

    .super-devtools-section h3 {
        font-size: 13px;
    }

    .super-devtools-section p {
        font-size: 13px;
    }

    /* Checkboxes and radios with labels */
    .super-devtools-section label {
        display: block;
        margin: 8px 0;
        font-size: 13px;
    }

    /* Buttons smaller on mobile */
    .button,
    .button-primary,
    .button-secondary {
        padding: 8px 12px;
        font-size: 13px;
    }

    .button-hero {
        padding: 10px 14px;
        font-size: 14px;
    }

    /* Notices */
    .sfui-notice {
        padding: 10px;
        margin: 10px 0;
    }

    .sfui-notice h3 {
        font-size: 13px;
    }

    .sfui-notice p {
        font-size: 12px;
    }

    /* Log areas */
    .super-devtools-log,
    .super-devtools-import-log {
        max-height: 200px;
        font-size: 11px;
        padding: 8px;
    }

    /* Statistics tables */
    .widefat th,
    .widefat td {
        padding: 6px 4px;
        font-size: 12px;
    }

    /* Upload dropzone */
    .super-devtools-upload-dropzone {
        padding: 20px 10px;
    }

    .upload-instructions {
        font-size: 13px;
    }

    .upload-hint {
        font-size: 11px;
    }

    /* Modal full screen on mobile */
    .diff-viewer-modal {
        width: 100%;
        height: 100%;
        max-height: 100vh;
        margin: 0;
        border-radius: 0;
    }

    .diff-viewer-modal-close {
        font-size: 24px;
        top: 5px;
        right: 5px;
    }

    .diff-table {
        font-size: 11px;
    }

    .diff-table th,
    .diff-table td {
        padding: 6px 2px;
        word-break: break-word;
    }

    /* Hide certain columns on very small screens */
    .diff-table th:nth-child(2),
    .diff-table td:nth-child(2) {
        display: none;
    }
}

/* Very small screens (480px and below) */
@media screen and (max-width: 480px) {
    .super-devtools-section h2 {
        font-size: 13px;
    }

    .button,
    .button-primary,
    .button-secondary {
        padding: 6px 10px;
        font-size: 12px;
    }

    .button-hero {
        padding: 8px 12px;
        font-size: 13px;
    }

    /* Hide dashicons on very small screens to save space */
    .button .dashicons {
        display: none;
    }

    /* Stack everything */
    .progress-step .step-icon {
        font-size: 16px;
    }

    .progress-step .step-label {
        font-size: 12px;
    }

    /* Compact forms */
    .super-devtools-section input[type="radio"],
    .super-devtools-section input[type="checkbox"] {
        margin-right: 5px;
    }
}

</style>

<!-- Diff Viewer Modal -->
<div id="diff-viewer-modal-overlay" class="diff-viewer-modal-overlay">
    <div class="diff-viewer-modal">
        <button class="diff-viewer-modal-close" onclick="jQuery('#diff-viewer-modal-overlay').hide();">&times;</button>
        <h2><?php echo esc_html__('Entry Data Comparison', 'super-forms'); ?></h2>
        <p id="diff-viewer-entry-info"><?php echo esc_html__('Loading...', 'super-forms'); ?></p>

        <div id="diff-viewer-content">
            <table class="diff-table">
                <thead>
                    <tr>
                        <th style="width: 200px;"><?php echo esc_html__('Field Name', 'super-forms'); ?></th>
                        <th><?php echo esc_html__('Serialized (Expected)', 'super-forms'); ?></th>
                        <th><?php echo esc_html__('EAV (Actual)', 'super-forms'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Status', 'super-forms'); ?></th>
                    </tr>
                </thead>
                <tbody id="diff-viewer-tbody">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-update-alt" style="font-size: 40px; animation: spin 1s linear infinite;"></span>
                            <p><?php echo esc_html__('Loading diff data...', 'super-forms'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Nonce available for future AJAX calls
    var devtoolsNonce = $('#super-devtools-nonce').val();

    console.log('Developer Tools page loaded. Nonce ready for AJAX.');

    // ========================================
    // Tab Switching Functionality
    // ========================================
    $('.super-devtools-tab').on('click', function() {
        var tabName = $(this).data('tab');
        
        // Update tab buttons
        $('.super-devtools-tab').removeClass('active');
        $(this).addClass('active');
        
        // Update tab content
        $('.super-devtools-tab-content').removeClass('active');
        $('#tab-' + tabName).addClass('active');
    });

    // ========================================
    // CSV Import Functionality
    // ========================================
    var csvFile = null;
    var importInProgress = false;
    var preloadedFile = null;

    // Handle preloaded file selection
    $('#preloaded-test-file').on('change', function() {
        var selectedFile = $(this).val();
        if (selectedFile) {
            $('#use-preloaded-btn').prop('disabled', false);
        } else {
            $('#use-preloaded-btn').prop('disabled', true);
        }
    });

    // Use preloaded file button
    $('#use-preloaded-btn').on('click', function() {
        var selectedFile = $('#preloaded-test-file').val();
        if (!selectedFile) {
            alert('Please select a test file first.');
            return;
        }

        // Set as active file
        preloadedFile = selectedFile;
        csvFile = null; // Clear any uploaded file

        // Display file info
        $('#csv-filename').text(selectedFile);
        $('#csv-filesize').text('Pre-uploaded on server');
        $('#csv-file-info').show();
        $('#import-csv-btn').prop('disabled', false);

        // Clear file input
        $('#csv-file-input').val('');
    });

    // File input change
    $('#csv-file-input').on('change', function(e) {
        if (e.target.files && e.target.files.length > 0) {
            csvFile = e.target.files[0];
            preloadedFile = null; // Clear preloaded file selection
            $('#preloaded-test-file').val(''); // Reset dropdown
            displayFileInfo(csvFile);
        }
    });


    // Select file button
    $('#select-csv-btn').on('click', function() {
        $('#csv-file-input').click();
    });

    // Drag and drop
    var dropzone = $('.super-devtools-upload-dropzone');
    
    dropzone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    
    dropzone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    
    dropzone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        
        if (e.originalEvent.dataTransfer.files && e.originalEvent.dataTransfer.files.length > 0) {
            csvFile = e.originalEvent.dataTransfer.files[0];
            
            // Validate file type
            if (!csvFile.name.toLowerCase().endsWith('.csv')) {
                alert('Please upload a CSV file.');
                csvFile = null;
                return;
            }
            
            displayFileInfo(csvFile);
            // Trigger file input change for form data
            var dataTransfer = new DataTransfer();
            dataTransfer.items.add(csvFile);
            document.getElementById('csv-file-input').files = dataTransfer.files;
        }
    });
    
    // Display file info
    function displayFileInfo(file) {
        $('#csv-filename').text(file.name);
        $('#csv-filesize').text(formatBytes(file.size));
        $('#csv-file-info').show();
        $('#import-csv-btn').prop('disabled', false);
    }
    
    // Format bytes helper
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Import CSV button
    $('#import-csv-btn').on('click', function() {
        if (!csvFile) {
            alert('Please select a CSV file first.');
            return;
        }
        
        if (importInProgress) {
            alert('An import is already in progress.');
            return;
        }
        
        // Get options
        var tagAsTest = $('#tag-as-test').is(':checked');
        var autoMigrate = $('#auto-migrate').is(':checked');
        
        // Confirm import
        var message = 'Import ' + csvFile.name + ' (' + formatBytes(csvFile.size) + ')?\n\n';
        message += 'Options:\n';
        message += '- Tag as test entries: ' + (tagAsTest ? 'Yes' : 'No') + '\n';
        message += '- Auto-migrate after import: ' + (autoMigrate ? 'Yes' : 'No');
        
    
    // Perform CSV import (handles both uploaded and preloaded files)
    function performCSVImport(file, tagAsTest, autoMigrate) {
        importInProgress = true;
        var startTime = Date.now();
        
        // Determine if using preloaded file or uploaded file
        var isPreloaded = (preloadedFile !== null);
        var fileName = isPreloaded ? preloadedFile : file.name;
        
        // UI updates
        $('#import-csv-btn').prop('disabled', true).text('Importing...');
        $('#cancel-import-btn').show();
        $('.super-devtools-import-progress-bar').show();
        $('.super-devtools-import-progress-text').text(isPreloaded ? 'Processing server file...' : 'Uploading file...');
        $('#import-statistics').hide();
        $('.super-devtools-import-log').empty().show();
        
        logImport('Starting import of ' + fileName + '...', 'info');
        
        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'super_import_csv_entries');
        formData.append('security', devtoolsNonce);
        formData.append('tag_as_test', tagAsTest ? '1' : '0');
        formData.append('auto_migrate', autoMigrate ? '1' : '0');
        
        if (isPreloaded) {
            // Send preloaded filename
            formData.append('preloaded_file', preloadedFile);
            logImport('Using pre-uploaded server file: ' + preloadedFile, 'info');
        } else {
            // Send uploaded file
            formData.append('csv_file', file);
        }
        
        // AJAX upload with progress
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                if (!isPreloaded) {
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = (e.loaded / e.total) * 100;
                            updateImportProgress(percentComplete, 'Uploading... ' + Math.round(percentComplete) + '%');
                        }
                    }, false);
                }
                return xhr;
            },
            success: function(response) {
                importInProgress = false;
                
                if (response.success) {
                    var elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
                    updateImportProgress(100, 'Import complete!');
                    logImport('Import successful! (' + elapsed + 's)', 'success');
                    
                    // Display statistics
                    displayImportStatistics(response.data, elapsed);
                    
                    // Auto-migrate if enabled
                    if (autoMigrate && response.data.imported > 0) {
                        logImport('Starting auto-migration...', 'info');
                        triggerAutoMigration(response.data.imported);
                    }
                } else {
                    logImport('Import failed: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    alert('Import failed: ' + (response.data ? response.data.message : 'Unknown error'));
                }
                
                resetImportUI();
            },
            error: function(xhr, status, error) {
                importInProgress = false;
                logImport('AJAX error: ' + error, 'error');
                alert('Import failed: ' + error);
                resetImportUI();
            }
        });
    }
    }
    
    // Trigger auto-migration
    function triggerAutoMigration(entryCount) {
        // Switch to Migration Controls section and trigger migration
        $('html, body').animate({
            scrollTop: $('#migrate-to-eav-btn').offset().top - 100
        }, 1000);
        
        logImport('Triggering migration for ' + entryCount + ' imported entries...', 'info');
        
        // Trigger migration button click after scroll
        setTimeout(function() {
            $('#migrate-to-eav-btn').click();
            logImport('Migration initiated. See Migration Controls section for progress.', 'success');
        }, 1200);
    }
    
    // Reset import UI
    function resetImportUI() {
        $('#import-csv-btn').prop('disabled', false).text('Import CSV');
        $('#cancel-import-btn').hide();
        setTimeout(function() {
            $('.super-devtools-import-progress-bar').hide();
            $('.super-devtools-import-progress-text').text('');
            updateImportProgress(0, '');
        }, 2000);
    }


    // Full Test Cycle functionality
    var testCycleData = {
        generate_result: null,
        migrate_result: null,
        verify_result: null,
        benchmark_result: null,
        timestamp: null
    };

    $('#full-test-cycle-btn').on('click', function() {
        if (confirm('Run Full Test Cycle? This will:\n1. Generate 1000 test entries\n2. Migrate them to EAV\n3. Run verification tests\n4. Run performance benchmarks\n5. Generate a comprehensive report\n\nThis may take several minutes.')) {
            runFullTestCycle();
        }
    });

    function runFullTestCycle() {
        // Reset cycle data
        testCycleData = {
            generate_result: null,
            migrate_result: null,
            verify_result: null,
            benchmark_result: null,
            timestamp: new Date().toISOString()
        };

        // Show progress area and disable button
        $('#full-test-cycle-progress').show();
        $('#full-test-cycle-btn').prop('disabled', true);
        $('.test-cycle-results').hide();

        // Reset all steps
        $('.progress-step').removeClass('active completed error');
        $('.progress-step .step-icon').text('⏳');
        $('.progress-step .step-status').text('');

        // Start with Step 1: Generate
        runTestCycleStep1_Generate();
    }

    function runTestCycleStep1_Generate() {
        updateStepStatus('generate', 'active', '⏳', 'Generating 1000 test entries...');

        // Use the same batch generation logic as the normal generator
        var totalToGenerate = 1000;
        var totalGenerated = 0;
        var batchSize = 50;
        var formId = 0; // Generic
        var dateMode = 'recent';
        var complexity = ['basic_text', 'special_chars', 'numeric', 'arrays'];

        function generateBatchForCycle() {
            var remaining = totalToGenerate - totalGenerated;
            var thisBatch = Math.min(batchSize, remaining);

            if (thisBatch <= 0) {
                // Generation complete
                testCycleData.generate_result = {
                    total_generated: totalGenerated,
                    success: true
                };
                updateStepStatus('generate', 'completed', '✓', 'Generated ' + totalGenerated + ' entries');

                // Move to Step 2: Migrate
                setTimeout(function() {
                    runTestCycleStep2_Migrate();
                }, 1000);
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_generate_entries',
                    security: devtoolsNonce,
                    count: thisBatch,
                    form_id: formId,
                    date_mode: dateMode,
                    complexity: complexity,
                    batch_offset: totalGenerated
                },
                success: function(response) {
                    if (response.success) {
                        totalGenerated += response.data.generated;
                        updateStepStatus('generate', 'active', '⏳',
                            'Generating... (' + totalGenerated + ' / ' + totalToGenerate + ')');

                        // Continue with next batch
                        setTimeout(function() {
                            generateBatchForCycle();
                        }, 500);
                    } else {
                        updateStepStatus('generate', 'error', '✗',
                            'Error: ' + (response.data ? response.data.message : 'Unknown error'));
                        endTestCycle(false);
                    }
                },
                error: function() {
                    updateStepStatus('generate', 'error', '✗', 'AJAX error during generation');
                    endTestCycle(false);
                }
            });
        }

        generateBatchForCycle();
    }

    function runTestCycleStep2_Migrate() {
        updateStepStatus('migrate', 'active', '⏳', 'Running migration...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_run_migration',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    testCycleData.migrate_result = response.data;
                    updateStepStatus('migrate', 'completed', '✓',
                        'Migrated ' + response.data.entries_migrated + ' entries');

                    // Move to Step 3: Verify
                    setTimeout(function() {
                        runTestCycleStep3_Verify();
                    }, 1000);
                } else {
                    updateStepStatus('migrate', 'error', '✗',
                        'Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    endTestCycle(false);
                }
            },
            error: function() {
                updateStepStatus('migrate', 'error', '✗', 'AJAX error during migration');
                endTestCycle(false);
            }
        });
    }

    function runTestCycleStep3_Verify() {
        updateStepStatus('verify', 'active', '⏳', 'Running verification tests...');

        var allTests = [
            'data_integrity',
            'field_count_match',
            'field_values_match',
            'csv_export_comparison',
            'listings_query_accuracy',
            'search_query_accuracy',
            'bulk_fetch_consistency',
            'empty_entry_handling',
            'special_characters_preservation'
        ];

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_run_verification',
                security: devtoolsNonce,
                tests: allTests
            },
            success: function(response) {
                if (response.success) {
                    testCycleData.verify_result = response.data;
                    var passed = response.data.summary.passed;
                    var total = response.data.summary.total;

                    if (response.data.summary.failed === 0) {
                        updateStepStatus('verify', 'completed', '✓',
                            'All ' + total + ' tests passed');

                        // Move to Step 4: Benchmark
                        setTimeout(function() {
                            runTestCycleStep4_Benchmark();
                        }, 1000);
                    } else {
                        updateStepStatus('verify', 'error', '✗',
                            passed + '/' + total + ' tests passed, ' + response.data.summary.failed + ' failed');
                        endTestCycle(false);
                    }
                } else {
                    updateStepStatus('verify', 'error', '✗',
                        'Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    endTestCycle(false);
                }
            },
            error: function() {
                updateStepStatus('verify', 'error', '✗', 'AJAX error during verification');
                endTestCycle(false);
            }
        });
    }

    function runTestCycleStep4_Benchmark() {
        updateStepStatus('benchmark', 'active', '⏳', 'Running performance benchmarks...');

        var benchmarks = ['csv_export', 'listings_filter', 'admin_search'];

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_run_benchmarks',
                security: devtoolsNonce,
                benchmarks: benchmarks,
                entry_count: 1000
            },
            success: function(response) {
                if (response.success) {
                    testCycleData.benchmark_result = response.data;

                    // Calculate average improvement
                    var improvements = [];
                    $.each(response.data.results, function(key, result) {
                        if (result.improvement) {
                            improvements.push(result.improvement);
                        }
                    });
                    var avgImprovement = improvements.length > 0
                        ? (improvements.reduce((a,b) => a + b, 0) / improvements.length).toFixed(1)
                        : 0;

                    updateStepStatus('benchmark', 'completed', '✓',
                        'Average ' + avgImprovement + 'x improvement');

                    // Move to Step 5: Report
                    setTimeout(function() {
                        runTestCycleStep5_Report();
                    }, 1000);
                } else {
                    updateStepStatus('benchmark', 'error', '✗',
                        'Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    endTestCycle(false);
                }
            },
            error: function() {
                updateStepStatus('benchmark', 'error', '✗', 'AJAX error during benchmarks');
                endTestCycle(false);
            }
        });
    }

    function runTestCycleStep5_Report() {
        updateStepStatus('report', 'active', '⏳', 'Generating report...');

        // Report is generated client-side from collected data
        setTimeout(function() {
            updateStepStatus('report', 'completed', '✓', 'Report ready');
            endTestCycle(true);
        }, 500);
    }

    function updateStepStatus(step, state, icon, statusText) {
        var $step = $('.progress-step[data-step="' + step + '"]');
        $step.removeClass('active completed error').addClass(state);
        $step.find('.step-icon').text(icon);
        $step.find('.step-status').text(statusText);
    }

    function endTestCycle(success) {
        $('#full-test-cycle-btn').prop('disabled', false);

        if (success) {
            $('.test-cycle-results').show();
        }
    }

    // Download test report
    $('#download-test-report-btn').on('click', function() {
        var report = {
            test_cycle_timestamp: testCycleData.timestamp,
            summary: {
                total_entries_generated: testCycleData.generate_result ? testCycleData.generate_result.total_generated : 0,
                entries_migrated: testCycleData.migrate_result ? testCycleData.migrate_result.entries_migrated : 0,
                verification_tests_passed: testCycleData.verify_result ? testCycleData.verify_result.summary.passed : 0,
                verification_tests_failed: testCycleData.verify_result ? testCycleData.verify_result.summary.failed : 0,
            },
            generation: testCycleData.generate_result,
            migration: testCycleData.migrate_result,
            verification: testCycleData.verify_result,
            benchmarks: testCycleData.benchmark_result
        };

        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(report, null, 2));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "full-test-cycle-report-" + Date.now() + ".json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });

    // Reset Everything functionality
    $('#reset-everything-btn').on('click', function() {
        var userInput = prompt(
            'WARNING: This will delete ALL entries (including non-test entries) and reset the migration!\n\n' +
            'This action CANNOT be undone!\n\n' +
            'Type "DELETE EVERYTHING" to confirm:'
        );

        if (userInput === 'DELETE EVERYTHING') {
            if (confirm('Are you absolutely sure? This will delete:\n' +
                '• All contact entries (test and real)\n' +
                '• All EAV data\n' +
                '• All serialized data\n' +
                '• Migration status\n\n' +
                'Last chance to cancel!')) {
                runResetEverything();
            }
        } else if (userInput !== null) {
            alert('Reset cancelled. You must type exactly: DELETE EVERYTHING');
        }
    });

    function runResetEverything() {
        $('#reset-everything-btn').prop('disabled', true).text('Resetting...');

        var resetSteps = [
            { action: 'delete_all_entries', label: 'Deleting all contact entries' },
            { action: 'delete_all_eav', label: 'Deleting all EAV data' },
            { action: 'delete_all_serialized', label: 'Deleting all serialized data' },
            { action: 'reset_migration', label: 'Resetting migration status' }
        ];

        var currentStep = 0;

        function executeResetStep() {
            if (currentStep >= resetSteps.length) {
                // All steps complete
                alert('✓ Reset complete! All data has been deleted and migration has been reset.');
                $('#reset-everything-btn').prop('disabled', false).text('🔄 Reset Everything');

                // Refresh database stats if available
                if ($('#refresh-db-stats-btn').length) {
                    $('#refresh-db-stats-btn').click();
                }
                return;
            }

            var step = resetSteps[currentStep];
            console.log(step.label + '...');

            if (step.action === 'reset_migration') {
                // Reset migration status
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'super_dev_reset_migration',
                        security: devtoolsNonce
                    },
                    success: function(response) {
                        console.log('✓ ' + step.label + ' complete');
                        currentStep++;
                        setTimeout(executeResetStep, 500);
                    },
                    error: function() {
                        alert('✗ Error during: ' + step.label);
                        $('#reset-everything-btn').prop('disabled', false).text('🔄 Reset Everything');
                    }
                });
            } else if (step.action === 'delete_all_entries') {
                // Delete all posts of type super_contact_entry
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'super_dev_cleanup_data',
                        security: devtoolsNonce,
                        cleanup_action: 'delete_all_entries'
                    },
                    success: function(response) {
                        console.log('✓ ' + step.label + ' complete');
                        currentStep++;
                        setTimeout(executeResetStep, 500);
                    },
                    error: function() {
                        alert('✗ Error during: ' + step.label);
                        $('#reset-everything-btn').prop('disabled', false).text('🔄 Reset Everything');
                    }
                });
            } else {
                // Other cleanup actions
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'super_dev_cleanup_data',
                        security: devtoolsNonce,
                        cleanup_action: step.action
                    },
                    success: function(response) {
                        console.log('✓ ' + step.label + ' complete');
                        currentStep++;
                        setTimeout(executeResetStep, 500);
                    },
                    error: function() {
                        alert('✗ Error during: ' + step.label);
                        $('#reset-everything-btn').prop('disabled', false).text('🔄 Reset Everything');
                    }
                });
            }
        }

        executeResetStep();
    }

    // Test Data Generator functionality
    var totalToGenerate = 0;
    var totalGenerated = 0;
    var batchSize = 50;

    // Generate entries button
    $('#generate-entries-btn').on('click', function() {
        // Get entry count
        var entryCount = $('input[name="entry_count"]:checked').val();
        if (entryCount === 'custom') {
            entryCount = parseInt($('#custom-entry-count').val());
        } else {
            entryCount = parseInt(entryCount);
        }

        if (!entryCount || entryCount < 1) {
            alert('Please enter a valid entry count');
            return;
        }

        // Get complexity
        var complexity = [];
        $('input[name="complexity[]"]:checked').each(function() {
            complexity.push($(this).val());
        });

        if (complexity.length === 0) {
            alert('Please select at least one data complexity option');
            return;
        }

        // Get form ID
        var formId = parseInt($('#test-form-id').val());

        // Get date mode
        var dateMode = $('input[name="date_mode"]:checked').val();

        // Start generation
        totalToGenerate = entryCount;
        totalGenerated = 0;
        $('.super-devtools-progress-bar').show();
        $('.super-devtools-log').show().empty();
        appendLog('Starting generation of ' + totalToGenerate + ' entries...');
        $('#generate-entries-btn').prop('disabled', true).text('Generating...');

        generateBatch(formId, dateMode, complexity);
    });

    function generateBatch(formId, dateMode, complexity) {
        var remaining = totalToGenerate - totalGenerated;
        var thisBatch = Math.min(batchSize, remaining);

        if (thisBatch <= 0) {
            // All done
            appendLog('✓ Generation complete: ' + totalGenerated + ' entries created');
            $('#generate-entries-btn').prop('disabled', false).text('Generate Test Entries');
            $('.super-devtools-progress-bar').hide();
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_generate_entries',
                security: devtoolsNonce,
                count: thisBatch,
                form_id: formId,
                date_mode: dateMode,
                complexity: complexity,
                batch_offset: totalGenerated
            },
            success: function(response) {
                if (response.success) {
                    totalGenerated += response.data.generated;
                    updateProgress(totalGenerated, totalToGenerate);
                    appendLog('Generated batch: ' + response.data.generated + ' entries (' + totalGenerated + ' / ' + totalToGenerate + ')');

                    if (response.data.failed > 0) {
                        appendLog('⚠ ' + response.data.failed + ' entries failed');
                    }

                    // Continue with next batch after short delay
                    setTimeout(function() {
                        generateBatch(formId, dateMode, complexity);
                    }, 500);
                } else {
                    appendLog('✗ Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    $('#generate-entries-btn').prop('disabled', false).text('Generate Test Entries');
                }
            },
            error: function() {
                appendLog('✗ AJAX error occurred');
                $('#generate-entries-btn').prop('disabled', false).text('Generate Test Entries');
            }
        });
    }

    function updateProgress(current, total) {
        var percent = (current / total) * 100;
        $('.super-devtools-progress-fill').css('width', percent + '%');
        $('.super-devtools-progress-text').text(current + ' / ' + total + ' (' + Math.round(percent) + '%)');
    }

    function appendLog(message) {
        var timestamp = new Date().toLocaleTimeString();
        $('.super-devtools-log').prepend(
            '<div>[' + timestamp + '] ' + message + '</div>'
        );
    }

    // Delete test entries button
    $('#delete-test-entries-btn').on('click', function() {
        if (!confirm('Delete all test entries? This cannot be undone.')) {
            return;
        }

        $(this).prop('disabled', true).text('Deleting...');
        $('.super-devtools-log').show().empty();
        appendLog('Deleting test entries...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_delete_test_entries',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    appendLog('✓ ' + response.data.message);
                } else {
                    appendLog('✗ Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
                $('#delete-test-entries-btn').prop('disabled', false).text('Delete All Test Entries');
            },
            error: function() {
                appendLog('✗ AJAX error occurred');
                $('#delete-test-entries-btn').prop('disabled', false).text('Delete All Test Entries');
            }
        });
    });

    // Delete Everything & Reset button (in Cleanup section)
    $('#delete-everything-reset-btn').on('click', function() {
        var userInput = prompt(
            'WARNING: This will delete ALL entries (including non-test entries) and reset the migration!\n\n' +
            'This action CANNOT be undone!\n\n' +
            'Type "DELETE EVERYTHING" to confirm:'
        );

        if (userInput === 'DELETE EVERYTHING') {
            if (confirm('Are you absolutely sure? This will delete:\n' +
                '• All contact entries (test and real)\n' +
                '• All EAV data\n' +
                '• All serialized data\n' +
                '• Migration status\n\n' +
                'Last chance to cancel!')) {
                runResetEverything();
            }
        } else if (userInput !== null) {
            alert('Reset cancelled. You must type exactly: DELETE EVERYTHING');
        }
    });

    // ======================================
    // Migration Controls (Phase 3)
    // ======================================
    var migrationActive = false;
    var migrationPaused = false;

    // Start migration
    $('#migration-start-btn').on('click', function() {
        if (confirm('Start migration process?')) {
            startMigration();
        }
    });

    // Pause migration
    $('#migration-pause-btn').on('click', function() {
        migrationPaused = true;
        $('#migration-pause-btn').hide();
        $('#migration-start-btn').text('▶️ Resume Migration').show();
        appendMigrationLog('Migration paused by user');
    });

    // Reset migration
    $('#migration-reset-btn').on('click', function() {
        if (confirm('Reset migration to "Not Started"? This will clear all progress.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_migration_reset',
                    security: devtoolsNonce
                },
                success: function(response) {
                    if (response.success) {
                        appendMigrationLog('✓ Migration reset to "Not Started"');
                        updateMigrationStatus();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Force complete
    $('#migration-force-complete-btn').on('click', function() {
        if (confirm('Force mark migration as complete WITHOUT actually migrating data?\n\nWARNING: This is for testing only!')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_migration_force_complete',
                    security: devtoolsNonce
                },
                success: function(response) {
                    if (response.success) {
                        appendMigrationLog('✓ Migration marked as complete (forced)');
                        updateMigrationStatus();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Rollback
    $('#migration-rollback-btn').on('click', function() {
        if (confirm('Rollback to serialized storage? Migration can be re-run later.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_migration_rollback',
                    security: devtoolsNonce
                },
                success: function(response) {
                    if (response.success) {
                        appendMigrationLog('✓ Rolled back to serialized storage');
                        updateMigrationStatus();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Force EAV switch
    $('#migration-force-eav-btn').on('click', function() {
        if (confirm('Force switch to EAV storage WITHOUT migrating?\n\nWARNING: Only use if EAV data already exists!')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_migration_force_eav',
                    security: devtoolsNonce
                },
                success: function(response) {
                    if (response.success) {
                        appendMigrationLog('✓ Forced switch to EAV storage');
                        updateMigrationStatus();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Failed Entries - View Diff button
    $(document).on('click', '.view-diff-btn', function() {
        var entryId = $(this).data('entry-id');

        // Show modal with loading state
        $('#diff-viewer-modal-overlay').show();
        $('#diff-viewer-entry-info').text('Entry #' + entryId);
        $('#diff-viewer-tbody').html('<tr><td colspan="4" style="text-align: center; padding: 40px;"><span class="dashicons dashicons-update-alt" style="font-size: 40px; animation: spin 1s linear infinite;"></span><p>Loading diff data...</p></td></tr>');

        // Fetch diff data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_get_entry_diff',
                security: devtoolsNonce,
                entry_id: entryId
            },
            success: function(response) {
                if (response.success) {
                    var diffRows = response.data.diff_rows;
                    var html = '';

                    if (diffRows.length === 0) {
                        html = '<tr><td colspan="4" style="text-align: center; padding: 40px;">No data found for this entry.</td></tr>';
                    } else {
                        diffRows.forEach(function(row) {
                            var statusClass = row.status;
                            var statusIcon = '';
                            var statusText = '';

                            if (row.status === 'match') {
                                statusIcon = '<span class="dashicons dashicons-yes-alt"></span>';
                                statusText = 'Match';
                            } else if (row.status === 'mismatch') {
                                statusIcon = '<span class="dashicons dashicons-warning"></span>';
                                statusText = 'Mismatch';
                            } else if (row.status === 'missing') {
                                statusIcon = '<span class="dashicons dashicons-dismiss"></span>';
                                statusText = 'Missing';
                            } else if (row.status === 'extra') {
                                statusIcon = '<span class="dashicons dashicons-info"></span>';
                                statusText = 'Extra';
                            }

                            var serializedValue = row.serialized_value !== null ? escapeHtml(String(row.serialized_value)) : '<em>null</em>';
                            var eavValue = row.eav_value !== null ? escapeHtml(String(row.eav_value)) : '<em>null</em>';

                            html += '<tr class="' + statusClass + '">';
                            html += '<td><strong>' + escapeHtml(row.field_name) + '</strong></td>';
                            html += '<td>' + serializedValue + '</td>';
                            html += '<td>' + eavValue + '</td>';
                            html += '<td>' + statusIcon + ' ' + statusText + '</td>';
                            html += '</tr>';
                        });
                    }

                    $('#diff-viewer-tbody').html(html);
                } else {
                    $('#diff-viewer-tbody').html('<tr><td colspan="4" style="text-align: center; padding: 40px; color: #d32f2f;">Error: ' + response.data.message + '</td></tr>');
                }
            },
            error: function() {
                $('#diff-viewer-tbody').html('<tr><td colspan="4" style="text-align: center; padding: 40px; color: #d32f2f;">AJAX request failed</td></tr>');
            }
        });
    });

    // Failed Entries - Retry single entry button
    $(document).on('click', '.retry-entry-btn', function() {
        var $btn = $(this);
        var entryId = $btn.data('entry-id');
        var $row = $btn.closest('tr');

        if (!confirm('Retry migration for Entry #' + entryId + '?')) {
            return;
        }

        $btn.prop('disabled', true).text('Retrying...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_retry_failed_entry',
                security: devtoolsNonce,
                entry_id: entryId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() {
                        $(this).remove();

                        // Update failed count
                        var remainingCount = $('.failed-entries-section tbody tr').length;
                        if (remainingCount === 0) {
                            $('.failed-entries-section').fadeOut();
                        } else {
                            $('.failed-entries-section h3').text('Failed Entries (' + remainingCount + ' entries)');
                        }
                    });
                    appendMigrationLog('✓ ' + response.data.message);
                    updateMigrationStatus();
                } else {
                    alert('Error: ' + response.data.message);
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Retry');
                }
            },
            error: function() {
                alert('AJAX request failed');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Retry');
            }
        });
    });

    // Failed Entries - Retry all button
    $('#retry-all-failed-btn').on('click', function() {
        var $failedRows = $('.failed-entries-section tbody tr');
        var totalFailed = $failedRows.length;

        if (!confirm('Retry migration for all ' + totalFailed + ' failed entries?')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Retrying all...');

        var entryIds = [];
        $failedRows.each(function() {
            entryIds.push($(this).data('entry-id'));
        });

        var successCount = 0;
        var failCount = 0;

        function retryNext(index) {
            if (index >= entryIds.length) {
                // All done
                appendMigrationLog('✓ Retry all completed: ' + successCount + ' succeeded, ' + failCount + ' failed');
                updateMigrationStatus();
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Retry All Failed Entries');

                if (successCount > 0) {
                    location.reload(); // Reload to update failed entries list
                }
                return;
            }

            var entryId = entryIds[index];
            appendMigrationLog('Retrying entry #' + entryId + '...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_retry_failed_entry',
                    security: devtoolsNonce,
                    entry_id: entryId
                },
                success: function(response) {
                    if (response.success) {
                        successCount++;
                        appendMigrationLog('  ✓ Entry #' + entryId + ' succeeded');
                    } else {
                        failCount++;
                        appendMigrationLog('  ✗ Entry #' + entryId + ' failed: ' + response.data.message);
                    }
                    retryNext(index + 1);
                },
                error: function() {
                    failCount++;
                    appendMigrationLog('  ✗ Entry #' + entryId + ' failed: AJAX error');
                    retryNext(index + 1);
                }
            });
        }

        retryNext(0);
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Start migration process
    function startMigration() {
        migrationActive = true;
        migrationPaused = false;
        $('#migration-start-btn').hide();
        $('#migration-pause-btn').show();
        $('.migration-log').show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_start',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    appendMigrationLog('✓ Migration started: ' + response.data.total_entries + ' entries to migrate');
                    processMigrationBatch();
                } else {
                    alert('Error: ' + response.data.message);
                    migrationActive = false;
                    $('#migration-start-btn').show();
                    $('#migration-pause-btn').hide();
                }
            }
        });
    }

    // Process migration batch
    function processMigrationBatch() {
        if (!migrationActive || migrationPaused) {
            return;
        }

        var batchSize = parseInt($('#migration-batch-size').val());
        var delay = parseInt($('#migration-delay').val());

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_process_batch',
                security: devtoolsNonce,
                batch_size: batchSize
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;

                    // Update progress
                    if (data.total_entries && data.total_entries > 0) {
                        updateMigrationProgress(data.migrated_entries || data.total_processed, data.total_entries);
                    }

                    // Log batch result
                    var processed = data.processed || data.batch_processed || 0;
                    var migrated = data.migrated_entries || data.total_processed || 0;
                    var total = data.total_entries || 0;
                    appendMigrationLog('Processed batch: ' + processed + ' entries (' + migrated + ' / ' + total + ')');

                    // Check if complete
                    if (data.status === 'completed' || data.is_complete) {
                        appendMigrationLog('✓ Migration complete!');
                        migrationActive = false;
                        $('#migration-start-btn').text('▶️ Start Migration').show();
                        $('#migration-pause-btn').hide();
                        updateMigrationStatus();
                    } else {
                        // Continue with next batch
                        setTimeout(processMigrationBatch, delay);
                    }
                } else {
                    appendMigrationLog('✗ Error: ' + response.data.message);
                    migrationActive = false;
                    $('#migration-start-btn').show();
                    $('#migration-pause-btn').hide();
                }
            },
            error: function() {
                appendMigrationLog('✗ AJAX error occurred');
                migrationActive = false;
                $('#migration-start-btn').show();
                $('#migration-pause-btn').hide();
            }
        });
    }

    // Update migration progress UI
    function updateMigrationProgress(current, total) {
        var percent = (current / total) * 100;
        $('.migration-progress-fill').css('width', percent + '%');
        $('.migration-progress-text').text(current.toLocaleString() + ' / ' + total.toLocaleString() + ' (' + Math.round(percent) + '%)');
    }

    // Update migration status display
    function updateMigrationStatus() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_get_status',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var status = response.data;

                    // Update status badge
                    var statusText = status.status === 'not_started' ? 'Not Started' :
                                    status.status === 'in_progress' ? 'In Progress' : 'Completed';
                    var badgeClass = status.status === 'not_started' ? 'sfui-grey' :
                                    status.status === 'in_progress' ? 'sfui-blue' : 'sfui-green';
                    $('.migration-status-badge').html('<span class="sfui-badge ' + badgeClass + '">● ' + statusText + '</span>');

                    // Update storage
                    $('.migration-using-storage').text(status.using_storage === 'eav' ? 'EAV Tables' : 'Serialized');

                    // Update progress
                    if (status.total_entries > 0) {
                        updateMigrationProgress(status.migrated_entries, status.total_entries);
                    }
                }
            }
        });
    }

    // Append to migration log
    function appendMigrationLog(message) {
        var timestamp = new Date().toLocaleTimeString();
        $('.migration-log').prepend('<div>[' + timestamp + '] ' + message + '</div>');
    }

    // Poll status every 5 seconds when not actively migrating
    setInterval(function() {
        if (!migrationActive) {
            updateMigrationStatus();
        }
    }, 5000);

    // ========================================
    // VERIFICATION FUNCTIONALITY
    // ========================================

    // Run all tests
    $('#run-all-tests-btn').on('click', function() {
        $('input[name="tests[]"]').prop('checked', true);
        runVerificationTests();
    });

    // Run selected tests
    $('#run-selected-tests-btn').on('click', function() {
        runVerificationTests();
    });

    // Select all tests
    $('#select-all-tests').on('change', function() {
        $('input[name="tests[]"]').prop('checked', $(this).is(':checked'));
    });

    // Run verification tests
    function runVerificationTests() {
        var selectedTests = [];
        $('input[name="tests[]"]:checked').each(function() {
            selectedTests.push($(this).val());
        });

        if (selectedTests.length === 0) {
            alert('Please select at least one test');
            return;
        }

        // Reset UI
        $('.test-status').text('⏳ Running...');
        $('.test-time').text('--');
        $('.verification-results').empty();
        $('#download-test-report-json').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_run_verification',
                security: devtoolsNonce,
                tests: selectedTests
            },
            success: function(response) {
                if (response.success) {
                    displayVerificationResults(response.data);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }

    // Display verification results
    function displayVerificationResults(data) {
        var results = data.results;
        var summary = data.summary;

        // Update test statuses
        $.each(results, function(test, result) {
            var statusText = result.passed ? '✓ Passed' : '✗ Failed';
            var statusClass = result.passed ? 'test-passed' : 'test-failed';

            $('.test-status[data-test="' + test + '"]')
                .text(statusText)
                .removeClass('test-passed test-failed')
                .addClass(statusClass);

            $('.test-time[data-test="' + test + '"]').text(result.time_ms + 'ms');
        });

        // Update summary
        $('.summary-text').text(
            summary.passed + '/' + summary.total + ' passed, ' +
            summary.failed + ' failed, ' +
            (10 - summary.total) + ' not run'
        );

        // Display detailed results
        var resultsHtml = '<h3>Detailed Results:</h3>';
        $.each(results, function(test, result) {
            resultsHtml += '<div class="verification-result ' + (result.passed ? 'result-pass' : 'result-fail') + '">';
            resultsHtml += '<strong>' + (result.passed ? '✓' : '✗') + ' ' + test + '</strong>: ' + result.message;

            if (!result.passed && result.errors) {
                resultsHtml += '<ul>';
                if (Array.isArray(result.errors)) {
                    $.each(result.errors, function(i, error) {
                        resultsHtml += '<li>' + error + '</li>';
                    });
                } else {
                    $.each(result.errors, function(entry_id, error) {
                        resultsHtml += '<li>Entry #' + entry_id + ': ' + JSON.stringify(error) + '</li>';
                    });
                }
                resultsHtml += '</ul>';
            }

            resultsHtml += '</div>';
        });

        $('.verification-results').html(resultsHtml);

        // Enable export button
        $('#download-test-report-json').prop('disabled', false);

        // Store results for export
        window.verificationResults = data;
    }

    // Download JSON report
    $('#download-test-report-json').on('click', function() {
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(window.verificationResults, null, 2));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "verification-report.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });

    // ========================================
    // BENCHMARK FUNCTIONALITY
    // ========================================

    // Run all benchmarks
    $('#run-all-benchmarks-btn').on('click', function() {
        $('input[name="benchmarks[]"]').prop('checked', true);
        runBenchmarks();
    });

    // Run selected benchmarks
    $('#run-selected-benchmarks-btn').on('click', function() {
        runBenchmarks();
    });

    function runBenchmarks() {
        var selectedBenchmarks = [];
        $('input[name="benchmarks[]"]:checked').each(function() {
            selectedBenchmarks.push($(this).val());
        });

        if (selectedBenchmarks.length === 0) {
            alert('Please select at least one benchmark');
            return;
        }

        var entryCount = parseInt($('#benchmark-entry-count').val());

        // Show benchmark result containers
        selectedBenchmarks.forEach(function(benchmark) {
            $('.benchmark-result[data-benchmark="' + benchmark + '"]').show();
        });

        // Disable buttons
        $('#run-all-benchmarks-btn, #run-selected-benchmarks-btn').prop('disabled', true).text('Running...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_run_benchmarks',
                security: devtoolsNonce,
                benchmarks: selectedBenchmarks,
                entry_count: entryCount
            },
            success: function(response) {
                if (response.success) {
                    displayBenchmarkResults(response.data.results);
                    $('#download-benchmark-report, #compare-with-previous').prop('disabled', false);
                    window.benchmarkResults = response.data;
                } else {
                    alert('Error: ' + response.data.message);
                }

                $('#run-all-benchmarks-btn').prop('disabled', false).text('▶️ Run All Benchmarks');
                $('#run-selected-benchmarks-btn').prop('disabled', false).text('Run Selected');
            }
        });
    }

    function displayBenchmarkResults(results) {
        $.each(results, function(benchmark, result) {
            if (result.error) {
                $('.benchmark-result[data-benchmark="' + benchmark + '"] .benchmark-improvement strong')
                    .text('Error: ' + result.message)
                    .css('color', '#f44336');
                return;
            }

            var $container = $('.benchmark-result[data-benchmark="' + benchmark + '"]');

            // Calculate relative widths (max = 100%)
            var maxTime = Math.max(result.time_serialized, result.time_eav);
            var serWidth = (result.time_serialized / maxTime) * 100;
            var eavWidth = (result.time_eav / maxTime) * 100;

            // Update progress bars
            $container.find('.benchmark-bar.serialized').css('width', serWidth + '%');
            $container.find('.benchmark-bar.eav').css('width', eavWidth + '%');

            // Update time labels
            $container.find('.benchmark-time.serialized').text(result.time_serialized + 'ms');
            $container.find('.benchmark-time.eav').text(result.time_eav + 'ms');

            // Update improvement
            var improvementText = result.improvement + 'x faster';
            if (result.improvement >= 50) {
                improvementText += ' 🔥';
            } else if (result.improvement >= 10) {
                improvementText += ' ⚡';
            }
            $container.find('.benchmark-improvement strong').text(improvementText);
        });
    }

    // Download benchmark report
    $('#download-benchmark-report').on('click', function() {
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(window.benchmarkResults, null, 2));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "benchmark-report.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });

    // Compare with previous benchmark results
    var previousBenchmarkResults = null;

    // Load previous results from server on page load
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_dev_get_previous_benchmarks',
            security: devtoolsNonce
        },
        success: function(response) {
            if (response.success && response.data) {
                previousBenchmarkResults = response.data;
                // Enable compare button if we have previous results and current results
                if (window.benchmarkResults) {
                    $('#compare-with-previous').prop('disabled', false);
                }
            }
        }
    });

    $('#compare-with-previous').on('click', function() {
        if (!previousBenchmarkResults) {
            alert('No previous benchmark results found.');
            return;
        }

        if (!window.benchmarkResults) {
            alert('Run benchmarks first before comparing.');
            return;
        }

        // Build comparison table
        var tableHtml = '';
        $.each(window.benchmarkResults.results, function(benchmark, currentResult) {
            if (previousBenchmarkResults.results && previousBenchmarkResults.results[benchmark]) {
                var prevResult = previousBenchmarkResults.results[benchmark];

                // Calculate change
                var prevImprovement = prevResult.improvement || 0;
                var currentImprovement = currentResult.improvement || 0;
                var change = ((currentImprovement - prevImprovement) / prevImprovement * 100).toFixed(1);
                var changeIcon = '';
                var changeStyle = '';

                if (change > 5) {
                    changeIcon = '↑ ';
                    changeStyle = 'color: #4caf50; font-weight: bold;';
                } else if (change < -5) {
                    changeIcon = '↓ ';
                    changeStyle = 'color: #f44336; font-weight: bold;';
                } else {
                    changeIcon = '→ ';
                    changeStyle = 'color: #666;';
                }

                tableHtml += '<tr>';
                tableHtml += '<td>' + benchmark.replace(/_/g, ' ').toUpperCase() + '</td>';
                tableHtml += '<td>' + prevImprovement.toFixed(1) + 'x</td>';
                tableHtml += '<td>' + currentImprovement.toFixed(1) + 'x</td>';
                tableHtml += '<td style="' + changeStyle + '">' + changeIcon + change + '%</td>';
                tableHtml += '</tr>';
            }
        });

        if (tableHtml) {
            $('#comparison-table-body').html(tableHtml);
            $('#benchmark-comparison-display').show();
        } else {
            alert('No matching benchmarks found for comparison.');
        }
    });

    // ========================================
    // DATABASE INSPECTOR FUNCTIONALITY
    // ========================================

    // Refresh database statistics
    $('#refresh-db-stats-btn').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_get_db_stats',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data;

                    // Update serialized count
                    $('.serialized-count').text(stats.serialized_count.toLocaleString());

                    // Update EAV stats
                    $('.eav-total-rows').text(stats.eav_stats.total_rows.toLocaleString());
                    $('.eav-unique-entries').text(stats.eav_stats.unique_entries.toLocaleString());
                    $('.eav-unique-fields').text(stats.eav_stats.unique_fields);
                    $('.eav-avg-fields').text(parseFloat(stats.eav_stats.avg_fields_per_entry).toFixed(1));
                    $('.eav-table-size').text(stats.eav_stats.table_size_mb);

                    // Update index status
                    var indexHtml = '';
                    $.each(stats.index_status, function(name, info) {
                        indexHtml += '<li>✓ ' + name + ' (' + info.columns.join(', ') + ')</li>';
                    });
                    $('.index-status').html(indexHtml);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // View Sample Entry Data
    $('#view-sample-entry-btn').on('click', function() {
        $(this).prop('disabled', true).text('Loading...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_get_sample_entry',
                security: devtoolsNonce
            },
            success: function(response) {
                $('#view-sample-entry-btn').prop('disabled', false).text('View Sample Entry Data');

                if (response.success) {
                    $('#sample-entry-data-content').text(JSON.stringify(response.data, null, 2));
                    $('#sample-entry-data-display').show();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                $('#view-sample-entry-btn').prop('disabled', false').text('View Sample Entry Data');
                alert('AJAX error occurred');
            }
        });
    });

    // Run ANALYZE TABLE
    $('#run-analyze-table-btn').on('click', function() {
        $(this).prop('disabled', true).text('Running...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_analyze_table',
                security: devtoolsNonce
            },
            success: function(response) {
                $('#run-analyze-table-btn').prop('disabled', false).text('Run ANALYZE TABLE');

                if (response.success) {
                    $('#analyze-table-content').text(JSON.stringify(response.data, null, 2));
                    $('#analyze-table-result').show();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                $('#run-analyze-table-btn').prop('disabled', false).text('Run ANALYZE TABLE');
                alert('AJAX error occurred');
            }
        });
    });

    // ========================================
    // CLEANUP FUNCTIONALITY
    // ========================================

    // Delete test entries
    $('#delete-test-entries-btn').on('click', function() {
        if (confirm('Delete all test entries? This cannot be undone.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_cleanup_data',
                    security: devtoolsNonce,
                    cleanup_action: 'delete_test_entries'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#refresh-db-stats-btn').click();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Delete all EAV
    $('#delete-all-eav-btn').on('click', function() {
        if (confirm('Delete ALL EAV data? Serialized data will be kept.\n\nThis cannot be undone!')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_cleanup_data',
                    security: devtoolsNonce,
                    cleanup_action: 'delete_all_eav'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#refresh-db-stats-btn').click();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Delete all serialized
    $('#delete-all-serialized-btn').on('click', function() {
        if (confirm('Delete ALL serialized data? EAV data will be kept.\n\nThis cannot be undone!')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_cleanup_data',
                    security: devtoolsNonce,
                    cleanup_action: 'delete_all_serialized'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#refresh-db-stats-btn').click();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Vacuum orphaned data
    $('#vacuum-orphaned-btn').on('click', function() {
        if (confirm('Remove orphaned EAV data? This is safe and recommended.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_cleanup_data',
                    security: devtoolsNonce,
                    cleanup_action: 'vacuum_orphaned'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#refresh-db-stats-btn').click();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Optimize tables
    $('#optimize-tables-btn').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_optimize_tables',
                security: devtoolsNonce,
                optimize_action: 'optimize'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // Rebuild indexes
    $('#rebuild-indexes-btn').on('click', function() {
        if (confirm('Rebuild all EAV indexes? This may take a few seconds.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_optimize_tables',
                    security: devtoolsNonce,
                    optimize_action: 'rebuild_indexes'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#refresh-db-stats-btn').click();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // ========================================
    // DEVELOPER UTILITIES FUNCTIONALITY
    // ========================================

    // Execute SQL query
    $('#execute-sql-btn').on('click', function() {
        var queryKey = $('#quick-sql-templates').val();
        if (!queryKey) {
            alert('Please select a query');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_execute_sql',
                security: devtoolsNonce,
                query_key: queryKey
            },
            success: function(response) {
                if (response.success) {
                    $('.sql-results').show();
                    $('.sql-results-content').text(JSON.stringify(response.data.results, null, 2));
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // Export migration status
    $('#export-migration-status-btn').on('click', function() {
        var migrationStatus = <?php echo wp_json_encode(SUPER_Migration_Manager::get_migration_status()); ?>;
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(migrationStatus, null, 2));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "migration-status.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });

    // View Migration Logs
    $('#view-migration-logs-btn').on('click', function() {
        $(this).prop('disabled', true).text('Loading...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_view_logs',
                security: devtoolsNonce,
                log_type: 'migration'
            },
            success: function(response) {
                $('#view-migration-logs-btn').prop('disabled', false).text('View Migration Logs');

                if (response.success) {
                    $('#migration-logs-content').text(response.data.log_content);
                    $('#migration-logs-display').show();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                $('#view-migration-logs-btn').prop('disabled', false).text('View Migration Logs');
                alert('AJAX error occurred');
            }
        });
    });

    // View PHP Error Log
    $('#view-php-errors-btn').on('click', function() {
        $(this).prop('disabled', true).text('Loading...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_view_logs',
                security: devtoolsNonce,
                log_type: 'php_errors'
            },
            success: function(response) {
                $('#view-php-errors-btn').prop('disabled', false).text('View PHP Error Log');

                if (response.success) {
                    $('#php-errors-content').text(response.data.log_content);
                    $('#php-errors-display').show();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                $('#view-php-errors-btn').prop('disabled', false).text('View PHP Error Log');
                alert('AJAX error occurred');
            }
        });
    });

    // Toggle Query Debugging
    var queryDebugEnabled = false;

    $('#toggle-query-debug-btn').on('click', function() {
        queryDebugEnabled = !queryDebugEnabled;

        if (queryDebugEnabled) {
            $(this).text('Disable Query Debugging').addClass('button-primary').removeClass('button-secondary');
            $('#query-debug-status').show();
            $('#query-debug-state').text('Enabled');

            // Start capturing queries
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_toggle_query_debug',
                    security: devtoolsNonce,
                    enabled: true
                },
                success: function(response) {
                    if (response.success) {
                        $('#query-debug-output').html('<p>Query debugging enabled. Queries will be logged below as they execute.</p>');

                        // Poll for new queries every 2 seconds
                        window.queryDebugInterval = setInterval(function() {
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'super_dev_get_query_log',
                                    security: devtoolsNonce
                                },
                                success: function(response) {
                                    if (response.success && response.data.queries) {
                                        var queryHtml = '';
                                        $.each(response.data.queries, function(i, query) {
                                            queryHtml += '<div style="margin-bottom: 10px; padding: 5px; background: #fff; border-left: 3px solid #0073aa;">';
                                            queryHtml += '<strong>Query ' + (i+1) + ':</strong> ' + query.sql + '<br>';
                                            queryHtml += '<small>Time: ' + query.time + 's</small>';
                                            queryHtml += '</div>';
                                        });
                                        $('#query-debug-output').html(queryHtml);
                                    }
                                }
                            });
                        }, 2000);
                    }
                }
            });
        } else {
            $(this).text('Enable Query Debugging').removeClass('button-primary').addClass('button-secondary');
            $('#query-debug-status').hide();
            $('#query-debug-state').text('Disabled');

            // Stop capturing queries
            clearInterval(window.queryDebugInterval);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'super_dev_toggle_query_debug',
                    security: devtoolsNonce,
                    enabled: false
                }
            });
        }
    });

    // Load stats on page load
    $('#refresh-db-stats-btn').click();
});
</script>
