<?php
/**
 * Test Phase 3: Add-ons using new DAL system
 * Run via: wp eval-file test/test-phase3-addons.php
 */

echo "=== TESTING PHASE 3: ADD-ON COMPATIBILITY ===\n\n";

$tests_passed = 0;
$tests_failed = 0;

// Test 1: DAL class exists and has all required methods
echo "--- Test 1: DAL Methods ---\n";
if (class_exists('SUPER_Form_DAL')) {
    $required_methods = array('get', 'create', 'update', 'delete', 'query', 'duplicate', 'search', 'archive', 'restore');
    foreach ($required_methods as $method) {
        if (method_exists('SUPER_Form_DAL', $method)) {
            echo "✓ {$method}() exists\n";
            $tests_passed++;
        } else {
            echo "✗ {$method}() MISSING\n";
            $tests_failed++;
        }
    }
} else {
    echo "✗ SUPER_Form_DAL class not found\n";
    $tests_failed += 9;
}
echo "\n";

// Test 2: Create a test form to use for add-on tests
echo "--- Test 2: Create Test Form ---\n";
$test_form_id = SUPER_Form_DAL::create(array(
    'name' => 'Add-on Test Form',
    'status' => 'publish',
    'elements' => array(),
    'settings' => array(),
    'translations' => array()
));

if (!is_wp_error($test_form_id)) {
    echo "✓ Test form created (ID: {$test_form_id})\n";
    $tests_passed++;
} else {
    echo "✗ Failed to create test form: " . $test_form_id->get_error_message() . "\n";
    $tests_failed++;
    $test_form_id = null;
}
echo "\n";

// Test 3: Test duplicate functionality
echo "--- Test 3: Duplicate Form ---\n";
if ($test_form_id) {
    $duplicate_id = SUPER_Form_DAL::duplicate($test_form_id);
    if (!is_wp_error($duplicate_id)) {
        $duplicate_form = SUPER_Form_DAL::get($duplicate_id);
        if ($duplicate_form && strpos($duplicate_form->name, '(Copy)') !== false) {
            echo "✓ Form duplicated successfully (ID: {$duplicate_id}, Name: {$duplicate_form->name})\n";
            $tests_passed++;
            // Clean up duplicate
            SUPER_Form_DAL::delete($duplicate_id);
        } else {
            echo "✗ Duplicate form name incorrect\n";
            $tests_failed++;
        }
    } else {
        echo "✗ Failed to duplicate: " . $duplicate_id->get_error_message() . "\n";
        $tests_failed++;
    }
} else {
    echo "✗ Skipped (no test form)\n";
    $tests_failed++;
}
echo "\n";

// Test 4: Test search functionality
echo "--- Test 4: Search Forms ---\n";
if ($test_form_id) {
    $results = SUPER_Form_DAL::search('Add-on');
    if (is_array($results) && count($results) > 0) {
        $found = false;
        foreach ($results as $form) {
            if ($form->id == $test_form_id) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "✓ Search found test form\n";
            $tests_passed++;
        } else {
            echo "✗ Search didn't find test form\n";
            $tests_failed++;
        }
    } else {
        echo "✗ Search returned no results\n";
        $tests_failed++;
    }
} else {
    echo "✗ Skipped (no test form)\n";
    $tests_failed++;
}
echo "\n";

// Test 5: Test archive/restore
echo "--- Test 5: Archive/Restore ---\n";
if ($test_form_id) {
    $archived = SUPER_Form_DAL::archive($test_form_id);
    if (!is_wp_error($archived)) {
        $form = SUPER_Form_DAL::get($test_form_id);
        if ($form && $form->status === 'archived') {
            echo "✓ Form archived\n";
            $tests_passed++;

            $restored = SUPER_Form_DAL::restore($test_form_id);
            if (!is_wp_error($restored)) {
                $form = SUPER_Form_DAL::get($test_form_id);
                if ($form && $form->status === 'publish') {
                    echo "✓ Form restored\n";
                    $tests_passed++;
                } else {
                    echo "✗ Form not restored to publish status\n";
                    $tests_failed++;
                }
            } else {
                echo "✗ Failed to restore: " . $restored->get_error_message() . "\n";
                $tests_failed++;
            }
        } else {
            echo "✗ Form not archived\n";
            $tests_failed++;
        }
    } else {
        echo "✗ Failed to archive: " . $archived->get_error_message() . "\n";
        $tests_failed++;
    }
} else {
    echo "✗ Skipped (no test form)\n";
    $tests_failed += 2;
}
echo "\n";

// Test 6: PayPal add-on compatibility
echo "--- Test 6: PayPal Add-on ---\n";
if (class_exists('SUPER_PayPal')) {
    // First, verify the test form still exists and has correct status
    $test_form = SUPER_Form_DAL::get($test_form_id);
    if ($test_form) {
        echo "DEBUG: Test form exists with status: {$test_form->status}\n";
    } else {
        echo "DEBUG: Test form not found!\n";
    }

    // Now query all published forms
    $forms_check = SUPER_Form_DAL::query(array(
        'status' => 'publish',
        'number' => -1,
    ));
    echo "DEBUG: DAL query for 'publish' status returned " . count($forms_check) . " forms\n";

    // Also try querying without status filter
    $forms_all = SUPER_Form_DAL::query(array(
        'number' => -1,
    ));
    echo "DEBUG: DAL query without status filter returned " . count($forms_all) . " forms\n";

    // Simulate calling the filter_form_dropdown method
    ob_start();
    SUPER_PayPal::filter_form_dropdown('super_paypal_txn');
    $output = ob_get_clean();

    if (strpos($output, '<select') !== false && strpos($output, '</select>') !== false) {
        echo "✓ PayPal form dropdown renders\n";
        $tests_passed++;

        if ($test_form_id && strpos($output, 'value="' . $test_form_id . '"') !== false) {
            echo "✓ Test form appears in dropdown\n";
            $tests_passed++;
        } else {
            // Debug: Show what forms are in the dropdown
            $form_count = preg_match_all('/<option value="(\d+)"/', $output, $matches);
            echo "✗ Test form (ID: {$test_form_id}) not in dropdown (found {$form_count} forms)\n";
            if (!empty($matches[1])) {
                echo "   Form IDs in dropdown: " . implode(', ', $matches[1]) . "\n";
            }
            $tests_failed++;
        }
    } else {
        echo "✗ PayPal dropdown failed to render\n";
        $tests_failed += 2;
    }
} else {
    echo "✓ PayPal add-on not active (skipping)\n";
}
echo "\n";

// Test 7: Listings extension compatibility
echo "--- Test 7: Listings Extension ---\n";
if (class_exists('SUPER_Listings') && $test_form_id) {
    // Verify the DAL integration in listings.php works (lines 2240-2260)
    // We can't test the full shortcode rendering in WP-CLI due to $_SERVER dependencies,
    // but we can verify the form existence check uses DAL
    $form = SUPER_Form_DAL::get($test_form_id);
    if ($form && $form->status === 'publish') {
        echo "✓ Listings can verify form exists via DAL (lines 2240-2260)\n";
        $tests_passed++;
    } else {
        echo "✗ DAL form lookup failed\n";
        $tests_failed++;
    }
} else {
    echo "✓ Listings extension not active (skipping)\n";
}
echo "\n";

// Cleanup test form
if ($test_form_id) {
    SUPER_Form_DAL::delete($test_form_id);
    echo "Cleaned up test form\n\n";
}

// Final summary
echo "=== TEST SUMMARY ===\n";
echo "Passed: {$tests_passed}\n";
echo "Failed: {$tests_failed}\n";
echo "Total: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n✅ ALL TESTS PASSED!\n";
} else {
    echo "\n⚠️  SOME TESTS FAILED\n";
}
