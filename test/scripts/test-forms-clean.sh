#!/bin/bash
# Super Forms Legacy Compatibility Test Runner - Clean Version
# Copies files instead of moving them to keep host files untouched

set -e

# Configuration
WORDPRESS_URL="http://localhost"
HOST_ORIGINAL_DIR="/scripts/../exports/original"
CONTAINER_WORK_DIR="/tmp/forms"
CONTAINER_ORIGINAL_DIR="$CONTAINER_WORK_DIR/original"
CONTAINER_SUCCESS_DIR="$CONTAINER_WORK_DIR/tested/success"
CONTAINER_FAILED_DIR="$CONTAINER_WORK_DIR/tested/failed"
RESULTS_FILE="$CONTAINER_WORK_DIR/test-results.json"
HOST_RESULTS_FILE="/scripts/../test-results.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧪 Super Forms Legacy Compatibility Test Suite (Clean Mode)${NC}"
echo -e "${BLUE}=========================================================${NC}"
echo ""

# Setup clean working directories in container
echo -e "${YELLOW}🧹 Setting up clean test environment...${NC}"
rm -rf "$CONTAINER_WORK_DIR"
mkdir -p "$CONTAINER_ORIGINAL_DIR" "$CONTAINER_SUCCESS_DIR" "$CONTAINER_FAILED_DIR"

# Copy all forms to container working directory
echo -e "${YELLOW}📂 Copying forms to container...${NC}"
cp -r "$HOST_ORIGINAL_DIR"/* "$CONTAINER_ORIGINAL_DIR/"
echo -e "${GREEN}✅ Copied $(ls "$CONTAINER_ORIGINAL_DIR" | wc -l) forms${NC}"

# Initialize results
echo "{\"started\": \"$(date -Iseconds)\", \"total\": 0, \"success\": 0, \"failed\": 0, \"results\": []}" > "$RESULTS_FILE"

# Test statistics
TOTAL_FORMS=0
SUCCESS_COUNT=0
FAILED_COUNT=0

# Check if WordPress is ready
echo -e "${YELLOW}🔍 Checking WordPress availability...${NC}"
if ! curl -f "$WORDPRESS_URL" > /dev/null 2>&1; then
    echo -e "${RED}❌ WordPress not accessible at $WORDPRESS_URL${NC}"
    exit 1
fi
echo -e "${GREEN}✅ WordPress is ready${NC}"

# Create required Super Forms directories
echo -e "${YELLOW}📁 Creating required directories...${NC}"
mkdir -p /var/www/html/wp-content/uploads/tmp/sf/
echo -e "${GREEN}✅ Directories created${NC}"

# Check if Super Forms plugin is active
echo -e "${YELLOW}🔍 Checking Super Forms plugin...${NC}"
if ! wp plugin is-active super-forms --allow-root --quiet; then
    echo -e "${YELLOW}🔌 Super Forms plugin not active, attempting to activate...${NC}"
    
    # Check if plugin exists first
    if wp plugin list --name=super-forms --allow-root --quiet | grep -q "super-forms"; then
        # Try to activate the plugin
        if wp plugin activate super-forms --allow-root --quiet; then
            echo -e "${GREEN}✅ Super Forms plugin activated successfully${NC}"
        else
            echo -e "${RED}❌ Failed to activate Super Forms plugin${NC}"
            echo -e "${YELLOW}📋 Available plugins:${NC}"
            wp plugin list --allow-root
            exit 1
        fi
    else
        echo -e "${RED}❌ Super Forms plugin not found${NC}"
        echo -e "${YELLOW}📋 Available plugins:${NC}"
        wp plugin list --allow-root
        exit 1
    fi
else
    echo -e "${GREEN}✅ Super Forms plugin is already active${NC}"
fi
echo ""

# Count total forms
TOTAL_FORMS=$(find "$CONTAINER_ORIGINAL_DIR" -name "form_*.json" | wc -l)
echo -e "${BLUE}📊 Found $TOTAL_FORMS forms to test${NC}"
echo ""

# Test each form
for form_file in "$CONTAINER_ORIGINAL_DIR"/form_*.json; do
    if [ ! -f "$form_file" ]; then
        continue
    fi
    
    form_name=$(basename "$form_file")
    form_id=$(echo "$form_name" | sed 's/form_\([0-9]*\)\.json/\1/')
    
    echo -e "${YELLOW}🧪 Testing: $form_name${NC}"
    
    # Create test result entry
    test_start=$(date -Iseconds)
    test_result="{
        \"form_id\": \"$form_id\",
        \"form_file\": \"$form_name\",
        \"started\": \"$test_start\",
        \"success\": false,
        \"errors\": [],
        \"warnings\": []
    }"
    
    # Debug: Check if form file exists
    echo "  📁 Form file path: $form_file"
    if [ ! -f "$form_file" ]; then
        echo "  ❌ Form file not found: $form_file"
        continue
    fi
    
    # Try to import the form using simplified approach
    if import_result=$(cd /var/www/html && wp eval "
        \$GLOBALS['FORM_FILE_TO_IMPORT'] = '$form_file';
        include_once '/scripts/import-single-form.php';
    " --allow-root 2>&1); then
        # Parse the result
        if echo "$import_result" | grep -q "Form imported successfully"; then
            echo -e "${GREEN}  ✅ Import successful${NC}"
            
            # Copy to success directory (not move)
            cp "$form_file" "$CONTAINER_SUCCESS_DIR/"
            
            # Update statistics
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
            
            # Update test result
            test_result=$(echo "$test_result" | jq '.success = true | .completed = "'$(date -Iseconds)'"')
            
            # Extract any warnings
            if echo "$import_result" | grep -q "warning"; then
                warnings=$(echo "$import_result" | grep "warning" | jq -R -s -c 'split("\n") | map(select(length > 0))')
                test_result=$(echo "$test_result" | jq --argjson warnings "$warnings" '.warnings = $warnings')
            fi
            
        else
            echo -e "${RED}  ❌ Import validation failed${NC}"
            echo "  Details: $import_result"
            
            # Create error log
            error_log="$CONTAINER_FAILED_DIR/${form_name%.json}.error.log"
            echo "$import_result" > "$error_log"
            
            # Copy to failed directory (not move)
            cp "$form_file" "$CONTAINER_FAILED_DIR/"
            
            # Update statistics
            FAILED_COUNT=$((FAILED_COUNT + 1))
            
            # Update test result
            errors=$(echo "$import_result" | jq -R -s -c 'split("\n") | map(select(length > 0))')
            test_result=$(echo "$test_result" | jq --argjson errors "$errors" '.errors = $errors | .completed = "'$(date -Iseconds)'"')
        fi
    else
        echo -e "${RED}  ❌ Import failed${NC}"
        echo "  Error: $import_result"
        
        # Create error log
        error_log="$CONTAINER_FAILED_DIR/${form_name%.json}.error.log"
        echo "$import_result" > "$error_log"
        
        # Copy to failed directory (not move)
        cp "$form_file" "$CONTAINER_FAILED_DIR/"
        
        # Update statistics
        FAILED_COUNT=$((FAILED_COUNT + 1))
        
        # Update test result
        errors=$(echo "$import_result" | jq -R -s -c 'split("\n") | map(select(length > 0))')
        test_result=$(echo "$test_result" | jq --argjson errors "$errors" '.errors = $errors | .completed = "'$(date -Iseconds)'"')
    fi
    
    # Update results file
    jq --argjson result "$test_result" '.results += [$result] | .total = (.results | length)' "$RESULTS_FILE" > "${RESULTS_FILE}.tmp"
    mv "${RESULTS_FILE}.tmp" "$RESULTS_FILE"
    
    echo ""
done

# Final statistics
echo -e "${BLUE}📊 Test Results Summary${NC}"
echo -e "${BLUE}======================${NC}"
echo -e "${GREEN}✅ Successful imports: $SUCCESS_COUNT${NC}"
echo -e "${RED}❌ Failed imports: $FAILED_COUNT${NC}"
echo -e "${BLUE}📁 Total forms tested: $TOTAL_FORMS${NC}"

if [ $TOTAL_FORMS -gt 0 ]; then
    success_rate=$(echo "scale=1; $SUCCESS_COUNT * 100 / $TOTAL_FORMS" | bc -l)
    echo -e "${BLUE}📈 Success rate: ${success_rate}%${NC}"
fi

# Update final results
jq --arg completed "$(date -Iseconds)" --argjson success "$SUCCESS_COUNT" --argjson failed "$FAILED_COUNT" --argjson total "$TOTAL_FORMS" '
    .completed = $completed | 
    .success = $success | 
    .failed = $failed | 
    .total = $total |
    .success_rate = (if $total > 0 then ($success * 100 / $total) else 0 end)
' "$RESULTS_FILE" > "${RESULTS_FILE}.tmp"
mv "${RESULTS_FILE}.tmp" "$RESULTS_FILE"

# Copy results back to host
cp "$RESULTS_FILE" "$HOST_RESULTS_FILE"

echo ""
echo -e "${BLUE}📄 Detailed results saved to: $HOST_RESULTS_FILE${NC}"
echo -e "${BLUE}🗂️ Container results located in: $CONTAINER_WORK_DIR${NC}"

# Show failed forms if any
if [ $FAILED_COUNT -gt 0 ]; then
    echo ""
    echo -e "${RED}❌ Failed Forms (in container):${NC}"
    ls -la "$CONTAINER_FAILED_DIR"/*.json 2>/dev/null || echo "No failed forms to display"
fi

echo ""
echo -e "${GREEN}🎯 Test suite completed!${NC}"
echo -e "${BLUE}💡 Host files remain untouched - you can re-run tests anytime${NC}"