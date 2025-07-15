#!/bin/bash

echo "=== FRESH WORDPRESS EMAIL MIGRATION TEST ==="
echo "This script will:"
echo "1. Clean up existing Super Forms data"
echo "2. Import the Loan Pre-Qualification form with version fix"
echo "3. Test email migration functionality"
echo "4. Provide form builder URL for manual verification"
echo ""

# Function to run PHP scripts through WordPress
run_wp_script() {
    local script_path="$1"
    echo "Running: $script_path"
    
    # Try different methods to run the script
    if command -v wp &> /dev/null; then
        echo "Using WP-CLI..."
        wp eval-file "$script_path"
    else
        echo "WP-CLI not available, running directly through PHP..."
        cd /projects/super-forms/test/scripts
        php "$script_path"
    fi
}

echo "Step 1: Testing the import and migration process..."
run_wp_script "fresh-wordpress-test.php"

echo ""
echo "=== MANUAL VERIFICATION STEPS ==="
echo "1. Navigate to: http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=<FORM_ID>"
echo "2. Click on the 'Emails' tab"
echo "3. Look for these sections:"
echo "   - Email headers"
echo "   - Email settings"
echo "   - Email content"
echo "   - Advanced options"
echo "4. Verify these specific settings:"
echo "   - Admin recipients: wisconsinhardmoney@gmail.com, info.wisconsinhardmoney@gmail.com, michelle.wisconsinhardmoney@gmail.com"
echo "   - Admin subject: 'Loan Pre-Qualification'"
echo "   - Confirmation subject: 'Thank you!'"
echo ""

echo "If you see default values like 'no-reply@localhost', the migration failed."
echo "If you see the correct email addresses and subjects, the migration succeeded!"