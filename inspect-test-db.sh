#!/bin/bash
# Inspect Test Database
#
# View test execution data logged to wp_superforms_test_log table
#
# Usage:
#   ./inspect-test-db.sh --events       # View all events fired
#   ./inspect-test-db.sh --failures     # View failed assertions
#   ./inspect-test-db.sh --errors       # View all errors
#   ./inspect-test-db.sh --summary      # View run summary
#   ./inspect-test-db.sh --tests        # View test execution times
#   ./inspect-test-db.sh --query "SQL"  # Custom query
#   ./inspect-test-db.sh --clear        # Clear old test data (keep last run)

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Database config (read from wp-config.php or environment)
DB_NAME="${DB_NAME:-wordpress}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-localhost}"
TABLE="wp_superforms_test_log"

# MySQL command
MYSQL_CMD="mysql -u${DB_USER} -h${DB_HOST}"
if [ -n "$DB_PASS" ]; then
    MYSQL_CMD="$MYSQL_CMD -p${DB_PASS}"
fi
MYSQL_CMD="$MYSQL_CMD ${DB_NAME}"

# Helper functions
run_query() {
    echo "$1" | $MYSQL_CMD 2>/dev/null
}

format_json() {
    if command -v jq &> /dev/null; then
        jq '.' 2>/dev/null || cat
    else
        cat
    fi
}

# Parse arguments
ACTION="${1:---summary}"

case $ACTION in
    --events)
        echo -e "${BLUE}📋 All Events Fired (Latest Run)${NC}"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        run_query "
        SELECT
            id,
            test_method,
            event_id,
            JSON_PRETTY(context_data) as context,
            created_at
        FROM ${TABLE}
        WHERE log_type = 'event'
        AND test_run_id = (SELECT test_run_id FROM ${TABLE} ORDER BY id DESC LIMIT 1)
        ORDER BY id ASC
        " | column -t -s$'\t'
        ;;

    --failures)
        echo -e "${RED}❌ Failed Assertions (Latest Run)${NC}"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        run_query "
        SELECT
            id,
            test_method,
            assertion_type,
            expected_value,
            actual_value,
            error_message,
            created_at
        FROM ${TABLE}
        WHERE log_type = 'assertion' AND status = 'fail'
        AND test_run_id = (SELECT test_run_id FROM ${TABLE} ORDER BY id DESC LIMIT 1)
        ORDER BY id ASC
        " | column -t -s$'\t'
        ;;

    --errors)
        echo -e "${RED}🐛 Errors (Latest Run)${NC}"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        run_query "
        SELECT
            id,
            test_class,
            test_method,
            error_message,
            LEFT(stack_trace, 200) as stack_trace,
            created_at
        FROM ${TABLE}
        WHERE log_type = 'error'
        AND test_run_id = (SELECT test_run_id FROM ${TABLE} ORDER BY id DESC LIMIT 1)
        ORDER BY id ASC
        " | column -t -s$'\t'
        ;;

    --summary)
        echo -e "${CYAN}📊 Test Run Summary${NC}"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

        # Get latest run ID
        RUN_ID=$(run_query "SELECT test_run_id FROM ${TABLE} ORDER BY id DESC LIMIT 1" | tail -1)

        if [ -z "$RUN_ID" ]; then
            echo -e "${YELLOW}No test runs found in database${NC}"
            exit 0
        fi

        echo -e "${GREEN}Run ID:${NC} $RUN_ID"
        echo ""

        # Tests summary
        echo -e "${BLUE}Tests:${NC}"
        run_query "
        SELECT
            COUNT(DISTINCT CONCAT(test_class, '::', test_method)) as 'Total Tests',
            SUM(CASE WHEN status = 'pass' THEN 1 ELSE 0 END) as 'Passed',
            SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) as 'Failed',
            ROUND(SUM(execution_time_ms), 2) as 'Total Time (ms)'
        FROM ${TABLE}
        WHERE test_run_id = '${RUN_ID}' AND log_type = 'test_end'
        " | column -t -s$'\t'

        echo ""

        # Events summary
        echo -e "${BLUE}Events:${NC}"
        run_query "
        SELECT
            COUNT(*) as 'Total Events Fired'
        FROM ${TABLE}
        WHERE test_run_id = '${RUN_ID}' AND log_type = 'event'
        " | column -t -s$'\t'

        echo ""

        # Assertions summary
        echo -e "${BLUE}Assertions:${NC}"
        run_query "
        SELECT
            COUNT(*) as 'Total Assertions',
            SUM(CASE WHEN status = 'pass' THEN 1 ELSE 0 END) as 'Passed',
            SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) as 'Failed'
        FROM ${TABLE}
        WHERE test_run_id = '${RUN_ID}' AND log_type = 'assertion'
        " | column -t -s$'\t'

        echo ""

        # Errors summary
        echo -e "${BLUE}Errors:${NC}"
        run_query "
        SELECT
            COUNT(*) as 'Total Errors'
        FROM ${TABLE}
        WHERE test_run_id = '${RUN_ID}' AND log_type = 'error'
        " | column -t -s$'\t'

        echo ""
        echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo ""
        echo -e "View details:"
        echo -e "  ${GREEN}./inspect-test-db.sh --events${NC}     # All events"
        echo -e "  ${GREEN}./inspect-test-db.sh --failures${NC}   # Failed assertions"
        echo -e "  ${GREEN}./inspect-test-db.sh --errors${NC}     # Errors"
        echo -e "  ${GREEN}./inspect-test-db.sh --tests${NC}      # Test execution times"
        ;;

    --tests)
        echo -e "${BLUE}⏱️  Test Execution Times (Latest Run)${NC}"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        run_query "
        SELECT
            test_class,
            test_method,
            status,
            ROUND(execution_time_ms, 2) as 'Time (ms)',
            created_at
        FROM ${TABLE}
        WHERE log_type = 'test_end'
        AND test_run_id = (SELECT test_run_id FROM ${TABLE} ORDER BY id DESC LIMIT 1)
        ORDER BY execution_time_ms DESC
        " | column -t -s$'\t'
        ;;

    --query)
        if [ -z "$2" ]; then
            echo -e "${RED}Error: No query provided${NC}"
            echo "Usage: ./inspect-test-db.sh --query \"SELECT * FROM ${TABLE} LIMIT 10\""
            exit 1
        fi
        echo -e "${BLUE}📝 Custom Query Result${NC}"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        run_query "$2" | column -t -s$'\t'
        ;;

    --clear)
        echo -e "${YELLOW}🗑️  Clearing old test data (keeping latest run)...${NC}"
        RUN_ID=$(run_query "SELECT test_run_id FROM ${TABLE} ORDER BY id DESC LIMIT 1" | tail -1)

        if [ -z "$RUN_ID" ]; then
            echo -e "${YELLOW}No test runs found${NC}"
            exit 0
        fi

        run_query "DELETE FROM ${TABLE} WHERE test_run_id != '${RUN_ID}'"

        DELETED=$(run_query "SELECT ROW_COUNT()")
        echo -e "${GREEN}✅ Deleted ${DELETED} old records${NC}"
        echo -e "${BLUE}Latest run preserved: ${RUN_ID}${NC}"
        ;;

    --help|*)
        echo -e "${BLUE}Test Database Inspector${NC}"
        echo ""
        echo "Usage: ./inspect-test-db.sh [OPTION]"
        echo ""
        echo "Options:"
        echo "  --events       View all events fired in latest test run"
        echo "  --failures     View failed assertions"
        echo "  --errors       View all errors"
        echo "  --summary      View run summary (default)"
        echo "  --tests        View test execution times"
        echo "  --query \"SQL\"  Run custom SQL query"
        echo "  --clear        Clear old test data (keep latest run)"
        echo "  --help         Show this help message"
        echo ""
        echo "Examples:"
        echo "  ./inspect-test-db.sh --events"
        echo "  ./inspect-test-db.sh --failures"
        echo "  ./inspect-test-db.sh --query \"SELECT * FROM ${TABLE} WHERE event_id LIKE '%spam%'\""
        ;;
esac
