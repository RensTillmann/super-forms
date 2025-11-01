<?php
/**
 * Migration Control Page
 *
 * @package Super Forms
 * @since   6.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Get current migration status
$migration_status = SUPER_Migration_Manager::get_migration_status();
$nonce            = wp_create_nonce( 'super-form-builder' );
?>

<div class="wrap super-migration-page">
	<h1><?php echo esc_html__( 'Contact Entry Data Migration', 'super-forms' ); ?></h1>

	<div class="super-migration-intro">
		<div class="sfui-notice sfui-blue">
			<h3><?php echo esc_html__( 'About This Migration', 'super-forms' ); ?></h3>
			<p><?php echo esc_html__( 'This migration converts your contact entry data from serialized storage to a more efficient database structure (EAV tables). This enables:', 'super-forms' ); ?></p>
			<ul>
				<li><?php echo esc_html__( '30-60x faster searching and filtering of entries', 'super-forms' ); ?></li>
				<li><?php echo esc_html__( 'Better database indexing for improved performance', 'super-forms' ); ?></li>
				<li><?php echo esc_html__( 'Advanced query capabilities for future features', 'super-forms' ); ?></li>
			</ul>
		</div>

		<div class="sfui-notice sfui-yellow">
			<h3><?php echo esc_html__( 'Important Information', 'super-forms' ); ?></h3>
			<ul>
				<li><?php echo esc_html__( 'The migration processes 10 entries at a time to prevent timeouts', 'super-forms' ); ?></li>
				<li><?php echo esc_html__( 'Your original data is preserved and can be rolled back anytime', 'super-forms' ); ?></li>
				<li><?php echo esc_html__( 'Do not close this page while migration is running', 'super-forms' ); ?></li>
				<li><?php echo esc_html__( 'It is recommended to backup your database before starting', 'super-forms' ); ?></li>
			</ul>
		</div>
	</div>

	<div class="super-migration-status-card">
		<h2><?php echo esc_html__( 'Migration Status', 'super-forms' ); ?></h2>

		<table class="widefat">
			<tbody>
				<tr>
					<th><?php echo esc_html__( 'Current Status', 'super-forms' ); ?>:</th>
					<td>
						<span class="super-migration-status-text">
							<?php
							if ( empty( $migration_status ) || $migration_status['status'] === 'not_started' ) {
								echo '<span class="sfui-badge sfui-grey">' . esc_html__( 'Not Started', 'super-forms' ) . '</span>';
							} elseif ( $migration_status['status'] === 'in_progress' ) {
								echo '<span class="sfui-badge sfui-blue">' . esc_html__( 'In Progress', 'super-forms' ) . '</span>';
							} elseif ( $migration_status['status'] === 'completed' ) {
								echo '<span class="sfui-badge sfui-green">' . esc_html__( 'Completed', 'super-forms' ) . '</span>';
							}
							?>
						</span>
					</td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Storage Method', 'super-forms' ); ?>:</th>
					<td>
						<span class="super-migration-storage-text">
							<?php
							if ( empty( $migration_status ) || $migration_status['using_storage'] === 'serialized' ) {
								echo esc_html__( 'Serialized (Legacy)', 'super-forms' );
							} else {
								echo esc_html__( 'EAV Tables (Optimized)', 'super-forms' );
							}
							?>
						</span>
					</td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Total Entries', 'super-forms' ); ?>:</th>
					<td><span class="super-migration-total"><?php echo isset( $migration_status['total_entries'] ) ? number_format( $migration_status['total_entries'] ) : '0'; ?></span></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Migrated Entries', 'super-forms' ); ?>:</th>
					<td><span class="super-migration-processed"><?php echo isset( $migration_status['migrated_entries'] ) ? number_format( $migration_status['migrated_entries'] ) : '0'; ?></span></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Failed Entries', 'super-forms' ); ?>:</th>
					<td><span class="super-migration-failed"><?php echo isset( $migration_status['failed_entries'] ) ? count( $migration_status['failed_entries'] ) : '0'; ?></span></td>
				</tr>
				<tr class="super-migration-progress-row">
					<th><?php echo esc_html__( 'Progress', 'super-forms' ); ?>:</th>
					<td>
						<div class="super-migration-progress-bar">
							<div class="super-migration-progress-fill" style="width: <?php echo isset( $migration_status['total_entries'] ) && $migration_status['total_entries'] > 0 ? ( $migration_status['migrated_entries'] / $migration_status['total_entries'] ) * 100 : 0; ?>%;"></div>
						</div>
						<span class="super-migration-progress-text">
							<?php
							if ( isset( $migration_status['total_entries'] ) && $migration_status['total_entries'] > 0 ) {
								echo round( ( $migration_status['migrated_entries'] / $migration_status['total_entries'] ) * 100, 2 ) . '%';
							} else {
								echo '0%';
							}
							?>
						</span>
					</td>
				</tr>
			</tbody>
		</table>

		<div class="super-migration-controls">
			<?php if ( empty( $migration_status ) || $migration_status['status'] === 'not_started' ) : ?>
				<button type="button" class="button button-primary button-hero super-migration-start">
					<?php echo esc_html__( 'Start Migration', 'super-forms' ); ?>
				</button>
			<?php elseif ( $migration_status['status'] === 'in_progress' ) : ?>
				<button type="button" class="button button-primary button-hero super-migration-resume" disabled>
					<?php echo esc_html__( 'Migrating...', 'super-forms' ); ?>
				</button>
			<?php elseif ( $migration_status['status'] === 'completed' ) : ?>
				<div class="sfui-notice sfui-green">
					<p><strong><?php echo esc_html__( 'Migration Complete!', 'super-forms' ); ?></strong></p>
					<p><?php echo esc_html__( 'Your contact entries are now using the optimized EAV storage. If you experience any issues, you can rollback to the original storage method below.', 'super-forms' ); ?></p>
				</div>
				<?php if ( $migration_status['using_storage'] === 'eav' ) : ?>
					<button type="button" class="button button-secondary super-migration-rollback">
						<?php echo esc_html__( 'Rollback to Serialized Storage', 'super-forms' ); ?>
					</button>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="super-migration-log"></div>
	</div>

	<?php if ( ! empty( $migration_status ) && $migration_status['status'] === 'completed' ) : ?>
	<div class="super-validation-card">
		<h2><?php echo esc_html__( 'Data Integrity Validation', 'super-forms' ); ?></h2>

		<div class="sfui-notice sfui-blue">
			<p><?php echo esc_html__( 'Validate that the migration was successful by comparing data in both storage methods. This checks for any data loss or corruption during the migration process.', 'super-forms' ); ?></p>
		</div>

		<div class="super-validation-status" style="display: none;">
			<table class="widefat">
				<tbody>
					<tr>
						<th><?php echo esc_html__( 'Validation Status', 'super-forms' ); ?>:</th>
						<td><span class="super-validation-status-text">-</span></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Entries Validated', 'super-forms' ); ?>:</th>
						<td><span class="super-validation-processed">0</span> / <span class="super-validation-total">0</span></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Valid Entries', 'super-forms' ); ?>:</th>
						<td><span class="super-validation-valid">0</span></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Invalid Entries', 'super-forms' ); ?>:</th>
						<td><span class="super-validation-invalid">0</span></td>
					</tr>
					<tr class="super-validation-progress-row">
						<th><?php echo esc_html__( 'Progress', 'super-forms' ); ?>:</th>
						<td>
							<div class="super-validation-progress-bar">
								<div class="super-validation-progress-fill" style="width: 0%;"></div>
							</div>
							<span class="super-validation-progress-text">0%</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="super-validation-controls">
			<button type="button" class="button button-primary super-validation-start">
				<?php echo esc_html__( 'Start Validation', 'super-forms' ); ?>
			</button>
			<button type="button" class="button button-secondary super-validation-stop" style="display: none;">
				<?php echo esc_html__( 'Stop Validation', 'super-forms' ); ?>
			</button>
		</div>

		<div class="super-validation-results" style="display: none;">
			<h3><?php echo esc_html__( 'Validation Results', 'super-forms' ); ?></h3>
			<div class="super-validation-results-content"></div>
		</div>

		<div class="super-validation-log"></div>
	</div>
	<?php endif; ?>
</div>

<input type="hidden" id="super-migration-nonce" value="<?php echo esc_attr( $nonce ); ?>" />

<style>
.super-migration-page {
	max-width: 1200px;
}

.super-migration-intro {
	margin: 20px 0;
}

.sfui-notice {
	padding: 15px 20px;
	margin: 15px 0;
	border-left: 4px solid;
	background: #fff;
}

.sfui-notice.sfui-blue {
	border-color: #2271b1;
	background: #f0f6fc;
}

.sfui-notice.sfui-yellow {
	border-color: #dba617;
	background: #fcf9e8;
}

.sfui-notice.sfui-green {
	border-color: #00a32a;
	background: #edfaef;
}

.sfui-notice h3 {
	margin-top: 0;
	font-size: 16px;
}

.sfui-notice ul {
	margin: 10px 0 10px 20px;
}

.sfui-notice ul li {
	margin: 5px 0;
}

.super-migration-status-card {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	margin: 20px 0;
}

.super-migration-status-card h2 {
	margin-top: 0;
	border-bottom: 1px solid #ccd0d4;
	padding-bottom: 10px;
}

.super-migration-status-card table {
	margin: 20px 0;
}

.super-migration-status-card th {
	width: 200px;
	text-align: left;
	font-weight: 600;
}

.sfui-badge {
	display: inline-block;
	padding: 4px 12px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
}

.sfui-badge.sfui-grey {
	background: #dcdcde;
	color: #50575e;
}

.sfui-badge.sfui-blue {
	background: #2271b1;
	color: #fff;
}

.sfui-badge.sfui-green {
	background: #00a32a;
	color: #fff;
}

.super-migration-progress-bar {
	width: 100%;
	height: 30px;
	background: #f0f0f1;
	border-radius: 3px;
	overflow: hidden;
	margin: 5px 0;
}

.super-migration-progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #2271b1 0%, #135e96 100%);
	transition: width 0.3s ease;
}

.super-migration-progress-text {
	display: inline-block;
	font-weight: 600;
	color: #2271b1;
}

.super-migration-controls {
	margin: 20px 0;
	text-align: center;
}

.super-migration-controls .button {
	margin: 0 5px;
}

.super-migration-log {
	margin: 20px 0;
	padding: 15px;
	background: #f6f7f7;
	border-radius: 3px;
	max-height: 300px;
	overflow-y: auto;
	display: none;
}

.super-migration-log.active {
	display: block;
}

.super-migration-log-entry {
	margin: 5px 0;
	padding: 8px 12px;
	background: #fff;
	border-left: 3px solid #2271b1;
	font-family: monospace;
	font-size: 13px;
}

.super-migration-log-entry.error {
	border-left-color: #d63638;
	background: #fcf0f1;
}

.super-migration-log-entry.success {
	border-left-color: #00a32a;
	background: #edfaef;
}

/* Validation Styles */
.super-validation-card {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	margin: 20px 0;
}

.super-validation-card h2 {
	margin-top: 0;
	border-bottom: 1px solid #ccd0d4;
	padding-bottom: 10px;
}

.super-validation-status {
	margin: 20px 0;
}

.super-validation-status table {
	margin: 20px 0;
}

.super-validation-status th {
	width: 200px;
	text-align: left;
	font-weight: 600;
}

.super-validation-progress-bar {
	width: 100%;
	height: 30px;
	background: #f0f0f1;
	border-radius: 3px;
	overflow: hidden;
	margin: 5px 0;
}

.super-validation-progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #00a32a 0%, #008a24 100%);
	transition: width 0.3s ease;
}

.super-validation-progress-text {
	display: inline-block;
	font-weight: 600;
	color: #00a32a;
}

.super-validation-controls {
	margin: 20px 0;
	text-align: center;
}

.super-validation-controls .button {
	margin: 0 5px;
}

.super-validation-results {
	margin: 20px 0;
}

.super-validation-results h3 {
	margin-top: 20px;
	font-size: 16px;
	border-bottom: 1px solid #ccd0d4;
	padding-bottom: 10px;
}

.super-validation-results-content {
	margin: 15px 0;
}

.validation-error-item {
	padding: 10px 15px;
	margin: 10px 0;
	background: #fcf0f1;
	border-left: 4px solid #d63638;
	border-radius: 3px;
}

.validation-error-item h4 {
	margin: 0 0 8px 0;
	color: #d63638;
	font-size: 14px;
}

.validation-error-item ul {
	margin: 5px 0 0 20px;
	font-size: 13px;
}

.validation-error-item ul li {
	margin: 3px 0;
}

.super-validation-log {
	margin: 20px 0;
	padding: 15px;
	background: #f6f7f7;
	border-radius: 3px;
	max-height: 300px;
	overflow-y: auto;
	display: none;
}

.super-validation-log.active {
	display: block;
}

.super-validation-log-entry {
	margin: 5px 0;
	padding: 8px 12px;
	background: #fff;
	border-left: 3px solid #00a32a;
	font-family: monospace;
	font-size: 13px;
}

.super-validation-log-entry.error {
	border-left-color: #d63638;
	background: #fcf0f1;
}

.super-validation-log-entry.success {
	border-left-color: #00a32a;
	background: #edfaef;
}
</style>

<script>
jQuery(document).ready(function($) {
	var migrationRunning = false;
	var migrationNonce = $('#super-migration-nonce').val();

	function updateStatus() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'super_migration_get_status',
				security: migrationNonce
			},
			success: function(response) {
				if (response.success && response.data) {
					var status = response.data;

					// Update status badge
					var statusBadge = '';
					if (status.status === 'not_started') {
						statusBadge = '<span class="sfui-badge sfui-grey"><?php echo esc_js( __( 'Not Started', 'super-forms' ) ); ?></span>';
					} else if (status.status === 'in_progress') {
						statusBadge = '<span class="sfui-badge sfui-blue"><?php echo esc_js( __( 'In Progress', 'super-forms' ) ); ?></span>';
					} else if (status.status === 'completed') {
						statusBadge = '<span class="sfui-badge sfui-green"><?php echo esc_js( __( 'Completed', 'super-forms' ) ); ?></span>';
					}
					$('.super-migration-status-text').html(statusBadge);

					// Update storage method
					var storageText = status.using_storage === 'eav' ? '<?php echo esc_js( __( 'EAV Tables (Optimized)', 'super-forms' ) ); ?>' : '<?php echo esc_js( __( 'Serialized (Legacy)', 'super-forms' ) ); ?>';
					$('.super-migration-storage-text').text(storageText);

					// Update counts
					$('.super-migration-total').text(status.total_entries.toLocaleString());
					$('.super-migration-processed').text(status.migrated_entries.toLocaleString());
					$('.super-migration-failed').text(Object.keys(status.failed_entries || {}).length);

					// Update progress
					var progress = status.total_entries > 0 ? (status.migrated_entries / status.total_entries) * 100 : 0;
					$('.super-migration-progress-fill').css('width', progress + '%');
					$('.super-migration-progress-text').text(progress.toFixed(2) + '%');
				}
			}
		});
	}

	function processBatch() {
		if (!migrationRunning) return;

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'super_migration_process_batch',
				security: migrationNonce
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;

					addLog('Processed ' + data.processed + ' entries. Progress: ' + data.progress + '%', 'success');

					// Update UI
					updateStatus();

					if (data.is_complete) {
						migrationRunning = false;
						addLog('Migration completed successfully!', 'success');
						$('.super-migration-resume').prop('disabled', true).text('<?php echo esc_js( __( 'Migration Complete', 'super-forms' ) ); ?>');

						// Reload page after 2 seconds
						setTimeout(function() {
							location.reload();
						}, 2000);
					} else {
						// Process next batch
						setTimeout(processBatch, 500);
					}
				} else {
					migrationRunning = false;
					addLog('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
					$('.super-migration-resume').prop('disabled', false).text('<?php echo esc_js( __( 'Resume Migration', 'super-forms' ) ); ?>');
				}
			},
			error: function() {
				migrationRunning = false;
				addLog('AJAX error occurred', 'error');
				$('.super-migration-resume').prop('disabled', false).text('<?php echo esc_js( __( 'Resume Migration', 'super-forms' ) ); ?>');
			}
		});
	}

	function addLog(message, type) {
		var logClass = type || 'info';
		var timestamp = new Date().toLocaleTimeString();
		var logEntry = $('<div class="super-migration-log-entry ' + logClass + '">[' + timestamp + '] ' + message + '</div>');
		$('.super-migration-log').addClass('active').prepend(logEntry);
	}

	$('.super-migration-start').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to start the migration? Make sure you have backed up your database first.', 'super-forms' ) ); ?>')) {
			return;
		}

		$(this).prop('disabled', true).text('<?php echo esc_js( __( 'Starting...', 'super-forms' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'super_migration_start',
				security: migrationNonce
			},
			success: function(response) {
				if (response.success) {
					addLog('Migration started', 'success');
					migrationRunning = true;
					$('.super-migration-start').replaceWith('<button type="button" class="button button-primary button-hero super-migration-resume" disabled><?php echo esc_js( __( 'Migrating...', 'super-forms' ) ); ?></button>');
					processBatch();
				} else {
					addLog('Error starting migration: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
					$('.super-migration-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Migration', 'super-forms' ) ); ?>');
				}
			},
			error: function() {
				addLog('AJAX error occurred', 'error');
				$('.super-migration-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Migration', 'super-forms' ) ); ?>');
			}
		});
	});

	$('.super-migration-rollback').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to rollback to serialized storage? This will switch back to the old storage method.', 'super-forms' ) ); ?>')) {
			return;
		}

		$(this).prop('disabled', true).text('<?php echo esc_js( __( 'Rolling back...', 'super-forms' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'super_migration_rollback',
				security: migrationNonce
			},
			success: function(response) {
				if (response.success) {
					addLog('Rollback successful', 'success');
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					addLog('Error during rollback: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
					$('.super-migration-rollback').prop('disabled', false).text('<?php echo esc_js( __( 'Rollback to Serialized Storage', 'super-forms' ) ); ?>');
				}
			},
			error: function() {
				addLog('AJAX error occurred', 'error');
				$('.super-migration-rollback').prop('disabled', false).text('<?php echo esc_js( __( 'Rollback to Serialized Storage', 'super-forms' ) ); ?>');
			}
		});
	});

	// Validation functionality
	var validationRunning = false;
	var validationStopped = false;

	function addValidationLog(message, type) {
		var $log = $('.super-validation-log');
		var $entry = $('<div class="super-validation-log-entry ' + (type || '') + '"></div>').text(message);
		$log.addClass('active').append($entry);
		$log.scrollTop($log[0].scrollHeight);
	}

	function updateValidationProgress(processed, total, valid, invalid) {
		var percentage = total > 0 ? Math.round((processed / total) * 100) : 0;

		$('.super-validation-processed').text(processed.toLocaleString());
		$('.super-validation-total').text(total.toLocaleString());
		$('.super-validation-valid').text(valid.toLocaleString());
		$('.super-validation-invalid').text(invalid.toLocaleString());
		$('.super-validation-progress-fill').css('width', percentage + '%');
		$('.super-validation-progress-text').text(percentage + '%');

		if (processed === 0 && total === 0) {
			$('.super-validation-status-text').html('<span class="sfui-badge sfui-grey"><?php echo esc_js( __( 'Not Started', 'super-forms' ) ); ?></span>');
		} else if (processed < total) {
			$('.super-validation-status-text').html('<span class="sfui-badge sfui-blue"><?php echo esc_js( __( 'Validating...', 'super-forms' ) ); ?></span>');
		} else if (invalid === 0) {
			$('.super-validation-status-text').html('<span class="sfui-badge sfui-green"><?php echo esc_js( __( 'All Valid', 'super-forms' ) ); ?></span>');
		} else {
			$('.super-validation-status-text').html('<span class="sfui-badge sfui-yellow"><?php echo esc_js( __( 'Validation Complete', 'super-forms' ) ); ?></span>');
		}
	}

	function processValidationBatch(entryIds, offset, batchSize) {
		if (validationStopped) {
			validationRunning = false;
			validationStopped = false;
			$('.super-validation-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Validation', 'super-forms' ) ); ?>');
			$('.super-validation-stop').hide();
			addValidationLog('Validation stopped by user', 'error');
			return;
		}

		var currentBatch = entryIds.slice(offset, offset + batchSize);

		if (currentBatch.length === 0) {
			validationRunning = false;
			$('.super-validation-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Validation', 'super-forms' ) ); ?>');
			$('.super-validation-stop').hide();
			addValidationLog('Validation complete', 'success');
			return;
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'super_validate_entries',
				security: migrationNonce,
				entry_ids: currentBatch
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;
					var currentValid = parseInt($('.super-validation-valid').text().replace(/,/g, '')) + data.valid;
					var currentInvalid = parseInt($('.super-validation-invalid').text().replace(/,/g, '')) + data.invalid;
					var processed = offset + currentBatch.length;

					updateValidationProgress(processed, entryIds.length, currentValid, currentInvalid);

					addValidationLog('Validated ' + currentBatch.length + ' entries: ' + data.valid + ' valid, ' + data.invalid + ' invalid', data.invalid > 0 ? 'error' : 'success');

					// Display errors if any
					if (data.invalid > 0 && data.errors) {
						$('.super-validation-results').show();
						var $content = $('.super-validation-results-content');

						Object.keys(data.errors).forEach(function(entryId) {
							var error = data.errors[entryId];
							var $errorItem = $('<div class="validation-error-item"></div>');
							$errorItem.append('<h4>Entry ID: ' + entryId + ' - ' + error.error + '</h4>');

							if (error.mismatches && error.mismatches.length > 0) {
								var $list = $('<ul></ul>');
								error.mismatches.forEach(function(mismatch) {
									var listItem = '<strong>' + mismatch.field + '</strong>: ' + mismatch.issue;
									if (mismatch.serialized_value) {
										listItem += ' (Serialized: ' + mismatch.serialized_value + ' | EAV: ' + mismatch.eav_value + ')';
									}
									$list.append('<li>' + listItem + '</li>');
								});
								$errorItem.append($list);
							} else if (error.missing_fields) {
								$errorItem.append('<p>' + error.missing_fields + ' fields missing in EAV</p>');
							} else if (error.extra_fields) {
								$errorItem.append('<p>' + error.extra_fields + ' extra fields in EAV</p>');
							}

							$content.append($errorItem);
						});
					}

					// Continue with next batch
					processValidationBatch(entryIds, offset + batchSize, batchSize);
				} else {
					addValidationLog('Error during validation: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
					validationRunning = false;
					$('.super-validation-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Validation', 'super-forms' ) ); ?>');
					$('.super-validation-stop').hide();
				}
			},
			error: function() {
				addValidationLog('AJAX error occurred', 'error');
				validationRunning = false;
				$('.super-validation-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Validation', 'super-forms' ) ); ?>');
				$('.super-validation-stop').hide();
			}
		});
	}

	$('.super-validation-start').on('click', function() {
		if (validationRunning) {
			return;
		}

		$(this).prop('disabled', true).text('<?php echo esc_js( __( 'Starting...', 'super-forms' ) ); ?>');

		// Reset UI
		$('.super-validation-status').show();
		$('.super-validation-results').hide();
		$('.super-validation-results-content').empty();
		$('.super-validation-log').empty();
		validationStopped = false;
		updateValidationProgress(0, 0, 0, 0);

		// Get all entry IDs
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'super_get_entry_ids',
				security: migrationNonce
			},
			success: function(response) {
				if (response.success && response.data.entry_ids) {
					addValidationLog('Starting validation of ' + response.data.entry_ids.length + ' entries', 'success');
					validationRunning = true;
					$('.super-validation-start').text('<?php echo esc_js( __( 'Validating...', 'super-forms' ) ); ?>');
					$('.super-validation-stop').show();
					updateValidationProgress(0, response.data.entry_ids.length, 0, 0);
					processValidationBatch(response.data.entry_ids, 0, 50); // Process 50 entries per batch
				} else {
					addValidationLog('Error: ' + (response.data && response.data.message ? response.data.message : 'No entries found'), 'error');
					$('.super-validation-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Validation', 'super-forms' ) ); ?>');
				}
			},
			error: function() {
				addValidationLog('AJAX error occurred', 'error');
				$('.super-validation-start').prop('disabled', false).text('<?php echo esc_js( __( 'Start Validation', 'super-forms' ) ); ?>');
			}
		});
	});

	$('.super-validation-stop').on('click', function() {
		if (!validationRunning) {
			return;
		}

		validationStopped = true;
		$(this).prop('disabled', true).text('<?php echo esc_js( __( 'Stopping...', 'super-forms' ) ); ?>');
	});
});
</script>
