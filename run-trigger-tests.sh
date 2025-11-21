#!/bin/bash
# Run Trigger System Tests
#
# Usage:
#   ./run-trigger-tests.sh              # Run all trigger tests
#   ./run-trigger-tests.sh --coverage   # Run with coverage report
#   ./run-trigger-tests.sh --verbose    # Run with verbose output
#   ./run-trigger-tests.sh --filter=test_name  # Run specific test

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧪 Super Forms Trigger System Tests${NC}"
echo "======================================"
echo ""
echo -e "${YELLOW}📊 Test Database Logging Enabled${NC}"
echo "• Events, assertions, and errors logged to: wp_superforms_test_log"
echo "• Table will be dropped and recreated for fresh start"
echo ""

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}⚠️  Composer dependencies not found. Installing...${NC}"
    composer install --dev
    echo ""
fi

# Parse arguments
COVERAGE=false
VERBOSE=false
FILTER=""

for arg in "$@"; do
    case $arg in
        --coverage)
            COVERAGE=true
            ;;
        --verbose)
            VERBOSE=true
            ;;
        --filter=*)
            FILTER="${arg#*=}"
            ;;
    esac
done

# Build PHPUnit command
CMD="vendor/bin/phpunit"

# Add test suite
CMD="$CMD --testsuite 'Triggers and Actions'"

# Add filter if specified
if [ -n "$FILTER" ]; then
    CMD="$CMD --filter $FILTER"
    echo -e "${BLUE}🔍 Running specific test: $FILTER${NC}"
    echo ""
fi

# Add coverage if requested
if [ "$COVERAGE" = true ]; then
    echo -e "${BLUE}📊 Running with coverage report${NC}"
    CMD="$CMD --coverage-html tests/coverage/triggers"
    echo ""
fi

# Add verbose if requested
if [ "$VERBOSE" = true ]; then
    CMD="$CMD --verbose"
fi

# Run the tests
echo -e "${GREEN}🚀 Running trigger tests...${NC}"
echo ""

if eval $CMD; then
    echo ""
    echo -e "${GREEN}✅ All tests passed!${NC}"

    if [ "$COVERAGE" = true ]; then
        echo ""
        echo -e "${BLUE}📊 Coverage report generated: tests/coverage/triggers/index.html${NC}"
    fi

    exit 0
else
    echo ""
    echo -e "${RED}❌ Some tests failed!${NC}"
    exit 1
fi
