#!/bin/bash
# Super Forms Legacy Compatibility Test Runner

set -e

# Configuration
WORDPRESS_URL="http://localhost:8080"
ORIGINAL_DIR="/scripts/../exports/original"
SUCCESS_DIR="/scripts/../exports/tested/success"
FAILED_DIR="/scripts/../exports/tested/failed"
RESULTS_FILE="/scripts/../test-results.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Initialize results
echo "{\"started\": \"$(date -Iseconds)\", \"total\": 0, \"success\": 0, \"failed\": 0, \"results\": []}" > "$RESULTS_FILE"

# Test statistics
TOTAL_FORMS=0
SUCCESS_COUNT=0
FAILED_COUNT=0

echo -e "${BLUE}🧪 Super Forms Legacy Compatibility Test Suite${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Check if WordPress is ready
echo -e "${YELLOW}🔍 Checking WordPress availability...${NC}"
if ! curl -f "$WORDPRESS_URL" > /dev/null 2>&1; then
    echo -e "${RED}❌ WordPress not accessible at $WORDPRESS_URL${NC}"
    exit 1
fi
echo -e "${GREEN}✅ WordPress is ready${NC}"

# Check if Super Forms plugin is active
echo -e "${YELLOW}🔍 Checking Super Forms plugin...${NC}"
if ! wp plugin is-active super-forms --allow-root; then
    echo -e "${RED}❌ Super Forms plugin is not active${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Super Forms plugin is active${NC}"
echo ""

# Count total forms
TOTAL_FORMS=$(find "$ORIGINAL_DIR" -name "form_*.json" | wc -l)
echo -e "${BLUE}📊 Found $TOTAL_FORMS forms to test${NC}"
echo ""

# Test each form
for form_file in "$ORIGINAL_DIR"/form_*.json; do
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
    
    # Try to import the form
    if import_result=$(wp eval-file /scripts/import-form.php "$form_file" --allow-root 2>&1); then
        # Parse the result
        if echo "$import_result" | grep -q "Form imported successfully"; then
            echo -e "${GREEN}  ✅ Import successful${NC}"
            
            # Move to success directory
            mv "$form_file" "$SUCCESS_DIR/"
            
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
            error_log="$FAILED_DIR/${form_name%.json}.error.log"
            echo "$import_result" > "$error_log"
            
            # Move to failed directory
            mv "$form_file" "$FAILED_DIR/"
            
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
        error_log="$FAILED_DIR/${form_name%.json}.error.log"
        echo "$import_result" > "$error_log"
        
        # Move to failed directory
        mv "$form_file" "$FAILED_DIR/"
        
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

echo ""
echo -e "${BLUE}📄 Detailed results saved to: $RESULTS_FILE${NC}"

# Show failed forms if any
if [ $FAILED_COUNT -gt 0 ]; then
    echo ""
    echo -e "${RED}❌ Failed Forms:${NC}"
    ls -la "$FAILED_DIR"/*.json 2>/dev/null || echo "No failed forms to display"
fi

echo ""
echo -e "${GREEN}🎯 Test suite completed!${NC}"