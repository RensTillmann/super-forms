/**
 * Super Forms - Developer Tools JavaScript
 *
 * @package Super Forms
 * @since 6.4.115
 */

/* global jQuery, ajaxurl, devtoolsData */

jQuery(document).ready(function($) {
    'use strict';

    // Get localized data from PHP
    var devtoolsNonce = devtoolsData.nonce;

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

        if (!confirm(message)) {
            return;
        }

        performCSVImport(csvFile, tagAsTest, autoMigrate);
    });

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

            if (step.action === 'reset_migration') {
                // Reset migration status
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'super_dev_reset_migration',
                        security: devtoolsNonce
                    },
                    success: function() {
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
                    success: function() {
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
                    success: function() {
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

    // Start migration
    $('#migration-start-btn').on('click', function() {
        if (confirm('Start migration process?')) {
            startMigration();
        }
    });

    // Pause migration
    $('#migration-pause-btn').on('click', function() {
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

    // Helper functions for cleanup queue operations
    function cleanupEmptyPosts(emptyCount, devtoolsNonce, appendMigrationLog, callback) {
        if (emptyCount === 0) {
            callback(0);
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_cleanup_empty',
                security: devtoolsNonce
            },
            success: function(response) {
                var deleted = 0;
                if (response.success) {
                    deleted = response.data.deleted;
                    if (deleted > 0) {
                        appendMigrationLog('✓ Deleted ' + deleted + ' empty posts');
                    }
                }
                callback(deleted);
            },
            error: function() {
                appendMigrationLog('✗ Failed to cleanup empty posts');
                callback(0);
            }
        });
    }

    function cleanupOrphanedMetadata(orphanedCount, devtoolsNonce, appendMigrationLog, callback) {
        if (orphanedCount === 0) {
            callback(0);
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_cleanup_orphaned',
                security: devtoolsNonce
            },
            success: function(response) {
                var deleted = 0;
                if (response.success) {
                    deleted = response.data.deleted;
                    if (deleted > 0) {
                        appendMigrationLog('✓ Deleted metadata for ' + deleted + ' orphaned posts');
                    }
                }
                callback(deleted);
            },
            error: function() {
                appendMigrationLog('✗ Failed to cleanup orphaned metadata');
                callback(0);
            }
        });
    }

    // Cleanup Queue (Empty Posts + Orphaned Metadata)
    $('#migration-cleanup-queue-btn').on('click', function() {
        var $btn = $(this);
        var emptyCount = parseInt($btn.data('empty')) || 0;
        var orphanedCount = parseInt($btn.data('orphaned')) || 0;
        var totalCount = emptyCount + orphanedCount;

        var confirmMsg = 'Delete ' + totalCount + ' items from cleanup queue?\n\n';
        if (emptyCount > 0) {
            confirmMsg += '• ' + emptyCount + ' empty posts (no form data)\n';
        }
        if (orphanedCount > 0) {
            confirmMsg += '• ' + orphanedCount + ' orphaned metadata entries\n';
        }
        confirmMsg += '\nThis will permanently remove these items from the database.';

        if (confirm(confirmMsg)) {
            $btn.prop('disabled', true).text('🗑️ Deleting...');

            // Run both cleanup operations sequentially
            cleanupEmptyPosts(emptyCount, devtoolsNonce, appendMigrationLog, function(deletedEmpty) {
                cleanupOrphanedMetadata(orphanedCount, devtoolsNonce, appendMigrationLog, function(deletedOrphaned) {
                    var totalDeleted = deletedEmpty + deletedOrphaned;
                    if (totalDeleted > 0) {
                        appendMigrationLog('✓ Cleanup complete: ' + totalDeleted + ' items removed');
                        updateMigrationStatus();
                        $btn.hide(); // Hide button after cleanup
                    } else {
                        appendMigrationLog('No items were deleted');
                        $btn.prop('disabled', false).html('🗑️ Clean Up Queue <span class="migration-cleanup-count">(0)</span>');
                    }
                });
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

    // Failed Entries - Re-verify button (without re-migration)
    $('#reverify-failed-btn').on('click', function() {
        var $failedRows = $('.failed-entries-section tbody tr');
        var totalFailed = $failedRows.length;

        if (!confirm('Re-verify all ' + totalFailed + ' failed entries?\n\nThis will check if they now pass verification without re-migrating the data.')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Re-verifying...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_reverify_failed_entries',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    var passed = response.data.passed;
                    var stillFailed = response.data.still_failed;

                    appendMigrationLog('✓ Re-verification complete: ' + passed + ' passed, ' + stillFailed + ' still failing');

                    if (passed > 0) {
                        appendMigrationLog('  ✓ ' + passed + ' entries removed from failed list');
                        // Reload to update the UI
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Re-verify Failed Entries');
                    }
                } else {
                    appendMigrationLog('✗ Re-verification failed: ' + response.data.message);
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Re-verify Failed Entries');
                }
            },
            error: function() {
                appendMigrationLog('✗ AJAX request failed');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Re-verify Failed Entries');
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

    // Start migration process (background mode via Action Scheduler)
    function startMigration() {
        $('#migration-start-btn').prop('disabled', true).text('Scheduling...');
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
                    var status = response.data.status || response.data;
                    var totalEntries = status.total_entries || 0;

                    // Show background mode message
                    appendMigrationLog('✓ Migration scheduled in background: ' + totalEntries + ' entries to migrate');
                    appendMigrationLog('ℹ Migration runs in background - you can close this page');

                    // Hide start button, show status
                    $('#migration-start-btn').hide();
                    $('#migration-pause-btn').hide(); // No pause in background mode

                    // Start polling for status updates (read-only)
                    migrationActive = true;
                    pollMigrationStatus();
                } else {
                    alert('Error: ' + response.data.message);
                    $('#migration-start-btn').prop('disabled', false).text('▶️ Start Migration');
                }
            },
            error: function() {
                alert('AJAX error occurred while starting migration');
                $('#migration-start-btn').prop('disabled', false).text('▶️ Start Migration');
            }
        });
    }

    // Poll migration status (read-only updates)
    function pollMigrationStatus() {
        if (!migrationActive) {
            return;
        }

        updateMigrationStatus();

        // Check if complete
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_get_status',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var status = response.data.status;

                    if (status === 'completed') {
                        appendMigrationLog('✓ Migration complete!');
                        migrationActive = false;
                        $('#migration-start-btn').text('▶️ Start Migration').show().prop('disabled', false);
                    } else if (status === 'in_progress') {
                        // Continue polling every 3 seconds
                        setTimeout(pollMigrationStatus, 3000);
                    }
                }
            }
        });
    }

    // Update migration progress UI
    function updateMigrationProgress(current, total, cleanupQueue) {
        var percent = (current / total) * 100;
        $('.migration-progress-fill').css('width', percent + '%');

        // Build progress text with cleanup queue if provided
        var progressText = current.toLocaleString() + ' / ' + total.toLocaleString() + ' (' + Math.round(percent) + '%)';

        if (cleanupQueue && (cleanupQueue.empty_posts > 0 || cleanupQueue.posts_without_data > 0 || cleanupQueue.orphaned_meta > 0)) {
            // Parse as integers to prevent string concatenation (WordPress transients can return strings)
            var emptyPosts = parseInt(cleanupQueue.empty_posts || 0, 10);
            var postsWithoutData = parseInt(cleanupQueue.posts_without_data || 0, 10);
            var orphanedMeta = parseInt(cleanupQueue.orphaned_meta || 0, 10);
            var totalCleanup = emptyPosts + postsWithoutData + orphanedMeta;

            // Build cleanup queue HTML
            var cleanupHtml = ' <span class="cleanup-queue-display" style="color: #f57c00; margin-left: 10px;">';
            cleanupHtml += '• <span class="cleanup-queue-count">' + totalCleanup.toLocaleString() + '</span> cleanup queue (';

            // Show breakdown
            var parts = [];
            if (emptyPosts > 0) {
                parts.push('<span class="cleanup-empty-count">' + emptyPosts.toLocaleString() + '</span> empty');
            }
            if (postsWithoutData > 0) {
                parts.push('<span class="cleanup-no-data-count">' + postsWithoutData.toLocaleString() + '</span> no data');
            }
            if (orphanedMeta > 0) {
                parts.push('<span class="cleanup-orphaned-count">' + orphanedMeta.toLocaleString() + '</span> orphaned');
            }
            cleanupHtml += parts.join(', ') + ')';

            // Add timestamp if available
            if (cleanupQueue.last_checked) {
                var timeSinceCheck = calculateTimeSince(cleanupQueue.last_checked);
                cleanupHtml += ' <span class="cleanup-last-checked" style="font-size: 11px; color: #999; font-style: italic;">';
                cleanupHtml += '— checked ' + timeSinceCheck + '</span>';
            }

            // Add refresh button
            cleanupHtml += ' <button id="refresh-cleanup-stats-btn" class="button button-small" style="margin-left: 8px; padding: 0 8px; height: 22px; line-height: 22px; font-size: 11px;">';
            cleanupHtml += '<span class="dashicons dashicons-update" style="font-size: 13px; width: 13px; height: 13px; margin-top: 4px;"></span>';
            cleanupHtml += '</button>';

            // Add loading spinner
            cleanupHtml += ' <span class="cleanup-stats-loading" style="display: none; margin-left: 5px; font-size: 11px;">';
            cleanupHtml += '<span class="dashicons dashicons-update-alt" style="font-size: 13px; width: 13px; height: 13px; animation: rotation 1s infinite linear;"></span>';
            cleanupHtml += ' Calculating...</span>';

            cleanupHtml += '</span>';

            progressText += cleanupHtml;
        }

        $('.migration-progress-text').html(progressText);
    }

    // Helper function to calculate time since timestamp
    function calculateTimeSince(timestamp) {
        var now = Math.floor(Date.now() / 1000); // Current time in seconds
        var diff = now - timestamp;

        if (diff < 60) {
            return 'just now';
        } else if (diff < 3600) {
            var minutes = Math.floor(diff / 60);
            return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
        } else {
            var hours = Math.floor(diff / 3600);
            return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
        }
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

                    // Update progress (with cleanup queue data)
                    if (status.total_entries > 0) {
                        updateMigrationProgress(status.migrated_entries, status.total_entries, status.cleanup_queue);
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
                    $('.serialized-count').text((stats.serialized_count || 0).toLocaleString());

                    // Update EAV stats (with null checks)
                    if (stats.eav_stats && stats.eav_stats.total_rows !== null) {
                        $('.eav-total-rows').text((stats.eav_stats.total_rows || 0).toLocaleString());
                        $('.eav-unique-entries').text((stats.eav_stats.unique_entries || 0).toLocaleString());
                        $('.eav-unique-fields').text(stats.eav_stats.unique_fields || 0);
                        $('.eav-avg-fields').text(parseFloat(stats.eav_stats.avg_fields_per_entry || 0).toFixed(1));
                        $('.eav-table-size').text(stats.eav_stats.table_size_mb || '0.00');
                    } else {
                        $('.eav-total-rows').text('0');
                        $('.eav-unique-entries').text('0');
                        $('.eav-unique-fields').text('0');
                        $('.eav-avg-fields').text('0.0');
                        $('.eav-table-size').text('0.00');
                    }

                    // Update index status
                    var indexHtml = '';
                    if (stats.index_status) {
                        $.each(stats.index_status, function(name, info) {
                            indexHtml += '<li>✓ ' + name + ' (' + info.columns.join(', ') + ')</li>';
                        });
                    }
                    $('.index-status').html(indexHtml || '<li>No indexes found</li>');
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
                $('#view-sample-entry-btn').prop('disabled', false).text('View Sample Entry Data');
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
        var migrationStatus = devtoolsData.migrationStatus;
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

    // ========================================
    // CLEANUP STATS REFRESH
    // ========================================

    // Refresh cleanup stats button (use event delegation since button is dynamically created)
    $(document).on('click', '#refresh-cleanup-stats-btn', function() {
        var $btn = $(this);
        var $loading = $('.cleanup-stats-loading');

        // Show loading, hide button
        $btn.prop('disabled', true);
        $loading.show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_refresh_cleanup_stats',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    // Show brief success feedback
                    appendMigrationLog('✓ ' + response.data.message);

                    // Trigger full status update to rebuild entire progress display
                    updateMigrationStatus();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    $loading.hide();
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('AJAX error occurred while refreshing cleanup stats');
                $loading.hide();
                $btn.prop('disabled', false);
            }
        });
    });

    // Helper functions for CSV import (defined at top level)
    function updateImportProgress(percent, text) {
        $('.super-devtools-import-progress-fill').css('width', percent + '%');
        $('.super-devtools-import-progress-text').text(text);
    }

    function logImport(message, level) {
        var timestamp = new Date().toLocaleTimeString();
        var className = 'import-log-' + (level || 'info');
        $('.super-devtools-import-log').prepend(
            '<div class="' + className + '">[' + timestamp + '] ' + message + '</div>'
        );
    }

    function displayImportStatistics(data, elapsed) {
        var html = '<h3>Import Statistics:</h3>';
        html += '<ul>';
        html += '<li><strong>Imported:</strong> ' + data.imported + ' entries</li>';
        html += '<li><strong>Skipped:</strong> ' + data.skipped + ' entries</li>';
        html += '<li><strong>Failed:</strong> ' + data.failed + ' entries</li>';
        html += '<li><strong>Time:</strong> ' + elapsed + 's</li>';
        html += '</ul>';
        $('#import-statistics').html(html).show();
    }
});

