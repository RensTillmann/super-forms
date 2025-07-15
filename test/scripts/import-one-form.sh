#!/bin/bash
# Import a single form for manual testing

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧪 Single Form Import & Manual Testing Setup${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Get form number from user input
if [ -z "$1" ]; then
    echo -e "${YELLOW}Usage: $0 <form_number>${NC}"
    echo -e "${YELLOW}Example: $0 125 (to import form_125.json)${NC}"
    echo ""
    echo -e "${BLUE}Available forms:${NC}"
    ls /scripts/../exports/original/form_*.json | head -10 | sed 's/.*form_/form_/' | sed 's/\.json//'
    echo "..."
    exit 1
fi

FORM_NUMBER="$1"
FORM_FILE="/scripts/../exports/original/form_${FORM_NUMBER}.json"

if [ ! -f "$FORM_FILE" ]; then
    echo -e "${RED}❌ Form file not found: $FORM_FILE${NC}"
    exit 1
fi

echo -e "${YELLOW}📂 Importing form: form_${FORM_NUMBER}.json${NC}"

# Enhanced debug configuration for better error detection
echo -e "${YELLOW}🔧 Configuring enhanced debugging...${NC}"
cd /var/www/html
wp eval-file /scripts/fix-debug-config.php --allow-root
echo -e "${GREEN}✅ Enhanced debugging enabled${NC}"

# Create required directories
mkdir -p /var/www/html/wp-content/uploads/tmp/sf/

# Clear debug log
echo "" > /var/www/html/wp-content/debug.log 2>/dev/null || true

# Import the form
echo -e "${YELLOW}📥 Importing form...${NC}"
import_result=$(wp eval "
    \$GLOBALS['FORM_FILE_TO_IMPORT'] = '$FORM_FILE';
    include_once '/scripts/import-single-form.php';
" --allow-root 2>&1)

if echo "$import_result" | grep -q '"success": true'; then
    # Extract the imported post ID and title
    imported_id=$(echo "$import_result" | grep -o '"post_id": *[0-9]*' | grep -o '[0-9]*')
    form_title=$(echo "$import_result" | grep -o '"title": *"[^"]*"' | sed 's/"title": *"//' | sed 's/"//')
    
    echo -e "${GREEN}✅ Form imported successfully!${NC}"
    echo ""
    echo -e "${BLUE}📋 Import Details:${NC}"
    echo -e "  Form Number: ${FORM_NUMBER}"
    echo -e "  WordPress Post ID: ${imported_id}"
    echo -e "  Form Title: ${form_title}"
    echo ""
    
    # Generate URLs for manual testing
    echo -e "${BLUE}🔗 Testing URLs:${NC}"
    echo -e "${YELLOW}WordPress Admin:${NC}"
    echo "  http://localhost:8080/wp-admin (admin/admin)"
    echo ""
    echo -e "${YELLOW}Form Builder Page:${NC}"
    echo "  http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=${imported_id}"
    echo ""
    echo -e "${YELLOW}Form Preview (if working):${NC}"
    echo "  http://localhost:8080/wp-admin/admin.php?page=super_create_form&id=${imported_id}&preview=true"
    echo ""
    
    # Create a test page with the form shortcode
    echo -e "${YELLOW}📄 Creating test page with form shortcode...${NC}"
    page_content="<!-- wp:shortcode -->[super_form id=\"${imported_id}\"]<!-- /wp:shortcode -->"
    page_title="Test: ${form_title}"
    
    page_id=$(wp post create --post_type=page --post_title="$page_title" --post_content="$page_content" --post_status=publish --allow-root --porcelain 2>&1)
    
    if [[ "$page_id" =~ ^[0-9]+$ ]]; then
        page_url=$(wp post url "$page_id" --allow-root)
        echo -e "${GREEN}✅ Test page created successfully!${NC}"
        echo -e "${YELLOW}Frontend Test Page:${NC}"
        echo "  $page_url"
    else
        echo -e "${YELLOW}⚠️ Could not create test page: $page_id${NC}"
        echo -e "${YELLOW}Manual shortcode for testing:${NC}"
        echo "  [super_form id=\"${imported_id}\"]"
    fi
    
    echo ""
    echo -e "${BLUE}🧪 Manual Testing Checklist:${NC}"
    echo -e "${YELLOW}Step 1: Form Builder Testing${NC}"
    echo "  1. Visit the Form Builder URL above"
    echo "  2. Check browser console for JavaScript errors (F12)"
    echo "  3. Check if form elements load correctly"
    echo "  4. Click on 'Settings' tab - verify settings are properly organized"
    echo "  5. Test different tabs (Form Settings, Email Settings, etc.)"
    echo ""
    echo -e "${YELLOW}Step 2: Frontend Testing${NC}"
    echo "  1. Visit the Frontend Test Page URL above"
    echo "  2. Check if form renders correctly"
    echo "  3. Check browser console for errors"
    echo "  4. Try filling out and submitting the form"
    echo ""
    echo -e "${YELLOW}Step 3: Check for PHP Errors${NC}"
    echo "  Run this command to see PHP errors:"
    echo "  docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log"
    echo ""
    echo -e "${YELLOW}Step 4: Automated Testing with Playwright${NC}"
    echo "  Setup Playwright (run once): docker-compose exec wordpress bash /scripts/setup-playwright.sh"
    echo "  Run automated test: docker-compose exec wordpress node /scripts/test-form-errors.js ${imported_id}"
    echo ""
    
    # Analyze form complexity
    echo -e "${BLUE}📊 Form Analysis:${NC}"
    analysis_result=$(wp eval "
        \$form_id = '$imported_id';
        \$elements = get_post_meta(\$form_id, '_super_elements', true);
        \$settings = get_post_meta(\$form_id, '_super_form_settings', true);
        
        if (!empty(\$elements)) {
            \$elements_array = json_decode(\$elements, true);
            if (\$elements_array) {
                \$element_count = count(\$elements_array);
                \$element_types = array();
                
                function count_elements(\$elements, &\$types) {
                    foreach (\$elements as \$element) {
                        if (isset(\$element['tag'])) {
                            \$types[\$element['tag']] = (\$types[\$element['tag']] ?? 0) + 1;
                        }
                        if (isset(\$element['inner']) && is_array(\$element['inner'])) {
                            count_elements(\$element['inner'], \$types);
                        }
                    }
                }
                
                count_elements(\$elements_array, \$element_types);
                echo 'Elements: ' . \$element_count . ' total' . PHP_EOL;
                foreach (\$element_types as \$type => \$count) {
                    echo '  - ' . \$type . ': ' . \$count . PHP_EOL;
                }
            }
        }
        
        if (!empty(\$settings)) {
            \$settings_array = unserialize(\$settings);
            if (\$settings_array && is_array(\$settings_array)) {
                echo 'Settings: ' . count(\$settings_array) . ' total' . PHP_EOL;
                
                // Check for complex features
                \$features = array();
                foreach (\$settings_array as \$key => \$value) {
                    if (strpos(\$key, 'paypal') !== false && !empty(\$value)) \$features[] = 'PayPal';
                    if (strpos(\$key, 'stripe') !== false && !empty(\$value)) \$features[] = 'Stripe';
                    if (strpos(\$key, 'mailchimp') !== false && !empty(\$value)) \$features[] = 'MailChimp';
                    if (strpos(\$key, 'woocommerce') !== false && !empty(\$value)) \$features[] = 'WooCommerce';
                }
                if (!empty(\$features)) {
                    echo 'Integrations: ' . implode(', ', array_unique(\$features)) . PHP_EOL;
                }
            }
        }
    " --allow-root 2>&1)
    
    echo "$analysis_result"
    
else
    echo -e "${RED}❌ Form import failed${NC}"
    echo -e "${RED}Error details:${NC}"
    echo "$import_result"
fi