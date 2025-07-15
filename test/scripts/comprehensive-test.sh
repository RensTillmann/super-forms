#!/bin/bash
# Comprehensive Super Forms Testing - Import + Functionality
# Tests actual form builder and frontend functionality

set -e

# Configuration
WORDPRESS_URL="http://localhost"
HOST_ORIGINAL_DIR="/scripts/../exports/original"
CONTAINER_WORK_DIR="/tmp/forms"
CONTAINER_ORIGINAL_DIR="$CONTAINER_WORK_DIR/original"
CONTAINER_SUCCESS_DIR="$CONTAINER_WORK_DIR/tested/success"
CONTAINER_FAILED_DIR="$CONTAINER_WORK_DIR/tested/failed"
RESULTS_FILE="$CONTAINER_WORK_DIR/comprehensive-test-results.json"
HOST_RESULTS_FILE="/scripts/../comprehensive-test-results.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧪 Super Forms Comprehensive Testing Suite${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Enable WordPress debugging
echo -e "${YELLOW}🔧 Enabling WordPress debugging...${NC}"
cd /var/www/html && wp eval-file /scripts/enable-debug.php --allow-root
echo -e "${GREEN}✅ Debug logging enabled${NC}"

# Setup clean working directories
echo -e "${YELLOW}🧹 Setting up clean test environment...${NC}"
rm -rf "$CONTAINER_WORK_DIR"
mkdir -p "$CONTAINER_ORIGINAL_DIR" "$CONTAINER_SUCCESS_DIR" "$CONTAINER_FAILED_DIR"

# Copy all forms to container working directory
echo -e "${YELLOW}📂 Copying forms to container...${NC}"
cp -r "$HOST_ORIGINAL_DIR"/* "$CONTAINER_ORIGINAL_DIR/"
echo -e "${GREEN}✅ Copied $(ls "$CONTAINER_ORIGINAL_DIR" | wc -l) forms${NC}"

# Create required Super Forms directories
echo -e "${YELLOW}📁 Creating required directories...${NC}"
mkdir -p /var/www/html/wp-content/uploads/tmp/sf/
echo -e "${GREEN}✅ Directories created${NC}"

# Initialize results
echo "{\"started\": \"$(date -Iseconds)\", \"total\": 0, \"import_success\": 0, \"functional_success\": 0, \"failed\": 0, \"results\": []}" > "$RESULTS_FILE"

# Test statistics
TOTAL_FORMS=0
IMPORT_SUCCESS_COUNT=0
FUNCTIONAL_SUCCESS_COUNT=0
FAILED_COUNT=0

# Check if WordPress is ready
echo -e "${YELLOW}🔍 Checking WordPress availability...${NC}"
if ! curl -f "$WORDPRESS_URL" > /dev/null 2>&1; then
    echo -e "${RED}❌ WordPress not accessible at $WORDPRESS_URL${NC}"
    exit 1
fi
echo -e "${GREEN}✅ WordPress is ready${NC}"

# Check if Super Forms plugin is active
echo -e "${YELLOW}🔍 Checking Super Forms plugin...${NC}"
if ! wp plugin is-active super-forms --allow-root --quiet; then
    echo -e "${YELLOW}🔌 Super Forms plugin not active, attempting to activate...${NC}"
    if wp plugin activate super-forms --allow-root --quiet 2>/dev/null; then
        echo -e "${GREEN}✅ Super Forms plugin activated successfully${NC}"
    else
        echo -e "${YELLOW}⚠️ Plugin activation had issues, but continuing...${NC}"
    fi
else
    echo -e "${GREEN}✅ Super Forms plugin is already active${NC}"
fi
echo ""

# Count total forms
TOTAL_FORMS=$(find "$CONTAINER_ORIGINAL_DIR" -name "form_*.json" | wc -l)
echo -e "${BLUE}📊 Found $TOTAL_FORMS forms to test comprehensively${NC}"
echo ""

# Test each form with comprehensive functionality testing
form_counter=0
for form_file in "$CONTAINER_ORIGINAL_DIR"/form_*.json; do
    if [ ! -f "$form_file" ]; then
        continue
    fi
    
    form_counter=$((form_counter + 1))
    form_name=$(basename "$form_file")
    form_id=$(echo "$form_name" | sed 's/form_\([0-9]*\)\.json/\1/')
    
    echo -e "${YELLOW}🧪 Testing ($form_counter/$TOTAL_FORMS): $form_name${NC}"
    
    # Create test result entry
    test_start=$(date -Iseconds)
    test_result="{
        \"form_id\": \"$form_id\",
        \"form_file\": \"$form_name\",
        \"started\": \"$test_start\",
        \"import_success\": false,
        \"functional_success\": false,
        \"builder_page_loads\": false,
        \"frontend_renders\": false,
        \"errors\": [],
        \"warnings\": [],
        \"form_analysis\": {}
    }"
    
    # Step 1: Import the form
    echo -e "  ${PURPLE}📥 Step 1: Importing form...${NC}"
    if import_result=$(cd /var/www/html && wp eval "
        \$GLOBALS['FORM_FILE_TO_IMPORT'] = '$form_file';
        include_once '/scripts/import-single-form.php';
    " --allow-root 2>&1); then
        
        if echo "$import_result" | grep -q '"success": true'; then
            echo -e "  ${GREEN}  ✅ Import successful${NC}"
            
            # Extract the imported post ID
            imported_id=$(echo "$import_result" | grep -o '"post_id": *[0-9]*' | grep -o '[0-9]*')
            
            if [ -n "$imported_id" ]; then
                echo -e "  ${BLUE}  📋 Imported as WordPress post ID: $imported_id${NC}"
                
                # Update test result with import success
                test_result=$(echo "$test_result" | jq '.import_success = true | .imported_post_id = '\"$imported_id\"'')
                IMPORT_SUCCESS_COUNT=$((IMPORT_SUCCESS_COUNT + 1))
                
                # Step 2: Test comprehensive functionality
                echo -e "  ${PURPLE}🔬 Step 2: Testing form functionality...${NC}"
                if functionality_result=$(cd /var/www/html && wp eval "
                    \$GLOBALS['FORM_ID_TO_TEST'] = '$imported_id';
                    include_once '/scripts/test-form-functionality.php';
                " --allow-root 2>&1); then
                    
                    # Parse functionality results
                    if echo "$functionality_result" | grep -q '"builder_page_loads": true'; then
                        echo -e "  ${GREEN}  ✅ Builder page functionality OK${NC}"
                    else
                        echo -e "  ${RED}  ❌ Builder page issues detected${NC}"
                    fi
                    
                    if echo "$functionality_result" | grep -q '"frontend_renders": true'; then
                        echo -e "  ${GREEN}  ✅ Frontend rendering OK${NC}"
                    else
                        echo -e "  ${YELLOW}  ⚠️ Frontend rendering issues${NC}"
                    fi
                    
                    # Check for complex features
                    if echo "$functionality_result" | grep -q '"complex_features"'; then
                        features=$(echo "$functionality_result" | grep -o '"complex_features": *\[[^]]*\]' | sed 's/"complex_features": *//')
                        if [ "$features" != "[]" ]; then
                            echo -e "  ${BLUE}  🎯 Complex features detected: $features${NC}"
                        fi
                    fi
                    
                    # Determine if functionality test passed
                    if echo "$functionality_result" | grep -q '"builder_page_loads": true' && echo "$functionality_result" | grep -q '"frontend_renders": true'; then
                        echo -e "  ${GREEN}  🎉 Comprehensive test PASSED${NC}"
                        cp "$form_file" "$CONTAINER_SUCCESS_DIR/"
                        FUNCTIONAL_SUCCESS_COUNT=$((FUNCTIONAL_SUCCESS_COUNT + 1))
                        test_result=$(echo "$test_result" | jq '.functional_success = true')
                    else
                        echo -e "  ${RED}  💥 Functionality issues detected${NC}"
                        cp "$form_file" "$CONTAINER_FAILED_DIR/"
                        FAILED_COUNT=$((FAILED_COUNT + 1))
                        
                        # Create detailed error log
                        error_log="$CONTAINER_FAILED_DIR/${form_name%.json}.functionality.log"
                        echo "$functionality_result" > "$error_log"
                    fi
                    
                    # Merge functionality results into test result
                    test_result=$(echo "$test_result $functionality_result" | jq -s '.[0] + .[1] | .completed = "'$(date -Iseconds)'"')
                    
                else
                    echo -e "  ${RED}  ❌ Functionality test failed${NC}"
                    echo -e "  ${RED}  Error: $functionality_result${NC}"
                    
                    cp "$form_file" "$CONTAINER_FAILED_DIR/"
                    FAILED_COUNT=$((FAILED_COUNT + 1))
                    
                    # Add error details
                    errors=$(echo "$functionality_result" | jq -R -s -c 'split("\n") | map(select(length > 0))')
                    test_result=$(echo "$test_result" | jq --argjson errors "$errors" '.errors = $errors | .completed = "'$(date -Iseconds)'"')
                fi
            else
                echo -e "  ${RED}  ❌ Could not extract imported post ID${NC}"
                cp "$form_file" "$CONTAINER_FAILED_DIR/"
                FAILED_COUNT=$((FAILED_COUNT + 1))
            fi
        else
            echo -e "  ${RED}  ❌ Import failed${NC}"
            echo -e "  ${RED}  Details: $import_result${NC}"
            
            cp "$form_file" "$CONTAINER_FAILED_DIR/"
            FAILED_COUNT=$((FAILED_COUNT + 1))
            
            errors=$(echo "$import_result" | jq -R -s -c 'split("\n") | map(select(length > 0))')
            test_result=$(echo "$test_result" | jq --argjson errors "$errors" '.errors = $errors | .completed = "'$(date -Iseconds)'"')
        fi
    else
        echo -e "  ${RED}  ❌ Import command failed${NC}"
        echo -e "  ${RED}  Error: $import_result${NC}"
        
        cp "$form_file" "$CONTAINER_FAILED_DIR/"
        FAILED_COUNT=$((FAILED_COUNT + 1))
        
        errors=$(echo "$import_result" | jq -R -s -c 'split("\n") | map(select(length > 0))')
        test_result=$(echo "$test_result" | jq --argjson errors "$errors" '.errors = $errors | .completed = "'$(date -Iseconds)'"')
    fi
    
    # Update results file
    jq --argjson result "$test_result" '.results += [$result] | .total = (.results | length)' "$RESULTS_FILE" > "${RESULTS_FILE}.tmp"
    mv "${RESULTS_FILE}.tmp" "$RESULTS_FILE"
    
    echo ""
    
    # Stop after first few forms for initial debugging
    if [ $form_counter -ge 5 ]; then
        echo -e "${YELLOW}🔍 Stopping after 5 forms for initial analysis...${NC}"
        break
    fi
done

# Final statistics
echo -e "${BLUE}📊 Comprehensive Test Results Summary${NC}"
echo -e "${BLUE}====================================${NC}"
echo -e "${GREEN}✅ Forms imported successfully: $IMPORT_SUCCESS_COUNT${NC}"
echo -e "${GREEN}🎯 Forms fully functional: $FUNCTIONAL_SUCCESS_COUNT${NC}"
echo -e "${RED}❌ Forms with issues: $FAILED_COUNT${NC}"
echo -e "${BLUE}📁 Total forms tested: $form_counter${NC}"

if [ $form_counter -gt 0 ]; then
    import_rate=$(echo "scale=1; $IMPORT_SUCCESS_COUNT * 100 / $form_counter" | bc -l)
    functional_rate=$(echo "scale=1; $FUNCTIONAL_SUCCESS_COUNT * 100 / $form_counter" | bc -l)
    echo -e "${BLUE}📈 Import success rate: ${import_rate}%${NC}"
    echo -e "${BLUE}🎯 Functional success rate: ${functional_rate}%${NC}"
fi

# Update final results
jq --arg completed "$(date -Iseconds)" --argjson import_success "$IMPORT_SUCCESS_COUNT" --argjson functional_success "$FUNCTIONAL_SUCCESS_COUNT" --argjson failed "$FAILED_COUNT" --argjson total "$form_counter" '
    .completed = $completed | 
    .import_success = $import_success | 
    .functional_success = $functional_success |
    .failed = $failed | 
    .total = $total |
    .import_rate = (if $total > 0 then ($import_success * 100 / $total) else 0 end) |
    .functional_rate = (if $total > 0 then ($functional_success * 100 / $total) else 0 end)
' "$RESULTS_FILE" > "${RESULTS_FILE}.tmp"
mv "${RESULTS_FILE}.tmp" "$RESULTS_FILE"

# Copy results back to host
cp "$RESULTS_FILE" "$HOST_RESULTS_FILE"

echo ""
echo -e "${BLUE}📄 Detailed results saved to: $HOST_RESULTS_FILE${NC}"

# Show failed forms if any
if [ $FAILED_COUNT -gt 0 ]; then
    echo ""
    echo -e "${RED}❌ Forms with issues:${NC}"
    ls -la "$CONTAINER_FAILED_DIR"/*.json 2>/dev/null || echo "No failed forms to display"
    echo ""
    echo -e "${YELLOW}💡 Check error logs in: $CONTAINER_FAILED_DIR/*.log${NC}"
fi

echo ""
echo -e "${GREEN}🎯 Comprehensive test suite completed!${NC}"
echo -e "${BLUE}💡 Next step: Analyze specific functionality issues and fix compatibility problems${NC}"