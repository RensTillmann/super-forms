<?php
/**
 * Mass Import All 197 Super Forms - Legacy Compatibility Testing
 * 
 * This script imports all forms from the exports/original/ directory
 * and provides detailed progress tracking and error reporting.
 */

if (!defined('WP_CLI') && !defined('ABSPATH')) {
    die('This script must be run through WP-CLI or WordPress');
}

function import_all_super_forms() {
    $exports_dir = '/scripts/../exports/original/';
    $results = array(
        'total_forms' => 0,
        'successful_imports' => 0,
        'failed_imports' => 0,
        'forms' => array(),
        'errors' => array()
    );
    
    error_log('=== STARTING MASS IMPORT OF ALL SUPER FORMS ===');
    error_log('Import directory: ' . $exports_dir);
    
    // Get all JSON files
    $form_files = glob($exports_dir . 'form_*.json');
    $results['total_forms'] = count($form_files);
    
    error_log('Found ' . $results['total_forms'] . ' forms to import');
    
    if (empty($form_files)) {
        error_log('ERROR: No form files found in directory: ' . $exports_dir);
        return $results;
    }
    
    // Sort files by form ID for consistent processing
    usort($form_files, function($a, $b) {
        $id_a = intval(preg_replace('/.*form_(\d+)\.json/', '$1', $a));
        $id_b = intval(preg_replace('/.*form_(\d+)\.json/', '$1', $b));
        return $id_a - $id_b;
    });
    
    foreach ($form_files as $index => $form_file) {
        $form_id = preg_replace('/.*form_(\d+)\.json/', '$1', basename($form_file));
        $progress = $index + 1;
        
        error_log("Processing form {$progress}/{$results['total_forms']}: Form ID {$form_id}");
        
        try {
            // Import the form
            $import_result = import_super_form_direct($form_file);
            
            if ($import_result['success']) {
                $results['successful_imports']++;
                $results['forms'][$form_id] = array(
                    'status' => 'success',
                    'post_id' => $import_result['post_id'],
                    'title' => $import_result['title'],
                    'original_id' => $import_result['original_id']
                );
                error_log("✓ Form {$form_id} imported successfully (Post ID: {$import_result['post_id']})");
            } else {
                $results['failed_imports']++;
                $results['forms'][$form_id] = array(
                    'status' => 'failed',
                    'error' => $import_result['error']
                );
                $results['errors'][] = "Form {$form_id}: " . $import_result['error'];
                error_log("✗ Form {$form_id} failed: " . $import_result['error']);
            }
            
        } catch (Exception $e) {
            $results['failed_imports']++;
            $error_msg = "Exception importing form {$form_id}: " . $e->getMessage();
            $results['forms'][$form_id] = array(
                'status' => 'exception',
                'error' => $error_msg
            );
            $results['errors'][] = $error_msg;
            error_log("✗ " . $error_msg);
        }
        
        // Progress update every 10 forms
        if ($progress % 10 == 0) {
            error_log("Progress: {$progress}/{$results['total_forms']} forms processed");
        }
    }
    
    // Final summary
    error_log('=== MASS IMPORT COMPLETED ===');
    error_log("Total forms: {$results['total_forms']}");
    error_log("Successful imports: {$results['successful_imports']}");
    error_log("Failed imports: {$results['failed_imports']}");
    
    if (!empty($results['errors'])) {
        error_log('ERRORS ENCOUNTERED:');
        foreach ($results['errors'] as $error) {
            error_log('  - ' . $error);
        }
    }
    
    return $results;
}

// Include the single form import function
include_once('/scripts/import-single-form.php');

// Execute the mass import
$import_results = import_all_super_forms();

// Output results as JSON for easy parsing
echo json_encode($import_results, JSON_PRETTY_PRINT);
?>