#!/bin/bash

# Super Forms Sync Script
# Syncs the local project to the remote webserver
#
# Usage:
#   ./sync-to-webserver.sh                    # Safe sync (no deletions)
#   ./sync-to-webserver.sh --delete           # Sync with deletions (requires confirmation)
#   ./sync-to-webserver.sh --test             # Sync + run all PHPUnit tests
#   ./sync-to-webserver.sh --test triggers    # Sync + run specific testsuite
#   ./sync-to-webserver.sh --sandbox          # Sync + run sandbox integration tests
#   ./sync-to-webserver.sh --sandbox=cleanup  # Sync + sandbox tests + cleanup
#   ./sync-to-webserver.sh --sandbox=reset    # Sync + reset sandbox + run tests
#   ./sync-to-webserver.sh --test --sandbox   # Sync + PHPUnit + sandbox tests
#   ./sync-to-webserver.sh --delete --test    # Combine flags

# Configuration
REMOTE_HOST="gnldm1014.siteground.biz"
REMOTE_PORT="18765"
REMOTE_USER="u2669-dvgugyayggy5"
REMOTE_PATH="/home/u2669-dvgugyayggy5/www/f4d.nl/public_html/dev/wp-content/plugins/super-forms/"
PRIVATE_KEY="~/.ssh/id_sftp"

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOCAL_PATH="$SCRIPT_DIR/src/"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse command-line arguments
USE_DELETE=false
RUN_TESTS=false
RUN_SANDBOX=false
SANDBOX_MODE="keep"  # keep, cleanup, reset
TEST_SUITE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --delete)
            USE_DELETE=true
            shift
            ;;
        --test)
            RUN_TESTS=true
            shift
            # Check if next argument is a testsuite name (not another flag)
            if [[ $# -gt 0 && ! "$1" =~ ^-- ]]; then
                TEST_SUITE="$1"
                shift
            fi
            ;;
        --sandbox)
            RUN_SANDBOX=true
            shift
            ;;
        --sandbox=*)
            RUN_SANDBOX=true
            SANDBOX_MODE="${1#*=}"
            shift
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

echo -e "${GREEN}🚀 Super Forms Sync Script${NC}"
echo "==============================="
echo "Local Path:  $LOCAL_PATH"
echo "Remote Host: $REMOTE_HOST:$REMOTE_PORT"
echo "Remote Path: $REMOTE_PATH"
if [ "$USE_DELETE" = true ]; then
    echo -e "Mode:        ${RED}DELETE MODE (removes remote files not in local)${NC}"
else
    echo -e "Mode:        ${GREEN}SAFE MODE (no deletions)${NC}"
fi
if [ "$RUN_TESTS" = true ]; then
    if [ -n "$TEST_SUITE" ]; then
        echo -e "Tests:       ${BLUE}Will run testsuite: $TEST_SUITE${NC}"
    else
        echo -e "Tests:       ${BLUE}Will run all tests${NC}"
    fi
fi
if [ "$RUN_SANDBOX" = true ]; then
    echo -e "Sandbox:     ${BLUE}Will run sandbox tests (mode: $SANDBOX_MODE)${NC}"
fi
echo "==============================="

# Check if private key exists
if [ ! -f ~/.ssh/id_sftp ]; then
    echo -e "${RED}❌ Private key not found at ~/.ssh/id_sftp${NC}"
    exit 1
fi

# Check if rsync is available
if ! command -v rsync &> /dev/null; then
    echo -e "${RED}❌ rsync is not installed${NC}"
    echo "Install with: sudo apt update && sudo apt install rsync"
    exit 1
fi

# If --delete flag is used, show preview and require confirmation
if [ "$USE_DELETE" = true ]; then
    echo ""
    echo -e "${YELLOW}⚠️  WARNING: --delete flag will remove remote files not found locally!${NC}"
    echo ""
    echo -e "${BLUE}Running dry-run to preview what would be deleted...${NC}"
    echo ""

    # Preview deletions for /src
    echo "=== /src folder deletions ==="
    DELETIONS_SRC=$(rsync -avzn --delete --itemize-changes \
        --exclude='.vscode/' \
        --exclude='.git/' \
        --exclude='.DS_Store' \
        --exclude='node_modules/' \
        --exclude='*.log' \
        -e "ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no" \
        "$LOCAL_PATH" \
        "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH" 2>&1 | grep "^deleting" || echo "(no files)")
    echo "$DELETIONS_SRC"

    echo ""
    echo "=== /test folder deletions ==="
    TEST_PATH="$SCRIPT_DIR/test/"
    DELETIONS_TEST=$(rsync -avzn --delete --itemize-changes \
        --exclude='.vscode/' \
        --exclude='.git/' \
        --exclude='.DS_Store' \
        --exclude='node_modules/' \
        --exclude='*.log' \
        -e "ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no" \
        "$TEST_PATH" \
        "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/test/" 2>&1 | grep "^deleting" || echo "(no files)")
    echo "$DELETIONS_TEST"

    echo ""
    echo "=== /tests folder deletions (PHPUnit tests) ==="
    TESTS_PATH="$SCRIPT_DIR/tests/"
    DELETIONS_TESTS=$(rsync -avzn --delete --itemize-changes \
        --exclude='.vscode/' \
        --exclude='.git/' \
        --exclude='.DS_Store' \
        --exclude='*.log' \
        -e "ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no" \
        "$TESTS_PATH" \
        "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/tests/" 2>&1 | grep "^deleting" || echo "(no files)")
    echo "$DELETIONS_TESTS"

    echo ""
    echo -e "${YELLOW}Do you want to proceed with these deletions?${NC}"
    echo -e "${RED}Type 'yes' to confirm (anything else cancels):${NC}"
    read -r CONFIRM
    if [[ ! "$CONFIRM" == "yes" ]]; then
        echo ""
        echo -e "${YELLOW}Sync cancelled.${NC}"
        exit 0
    fi
    echo ""
fi

echo -e "${GREEN}📡 Starting sync of /src folder...${NC}"

# Rsync command with SFTP over SSH
# --delete removes files on remote that don't exist locally (only if --delete flag used)
# --exclude excludes specified patterns
# -avz: archive mode, verbose, compress
# -e specifies SSH with custom port and key
# Note: LOCAL_PATH ends with / to sync contents of src/ into the remote directory
DELETE_FLAG=""
if [ "$USE_DELETE" = true ]; then
    DELETE_FLAG="--delete"
fi

rsync -avz $DELETE_FLAG \
    --exclude='.vscode/' \
    --exclude='.git/' \
    --exclude='.DS_Store' \
    --exclude='node_modules/' \
    --exclude='*.log' \
    -e "ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no" \
    "$LOCAL_PATH" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"

# Check if sync was successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ /src sync completed successfully!${NC}"
else
    echo -e "${RED}❌ /src sync failed!${NC}"
    exit 1
fi

echo -e "${GREEN}📡 Starting sync of /test folder...${NC}"

# Sync test directory
TEST_PATH="$SCRIPT_DIR/test/"
rsync -avz $DELETE_FLAG \
    --exclude='.vscode/' \
    --exclude='.git/' \
    --exclude='.DS_Store' \
    --exclude='node_modules/' \
    --exclude='*.log' \
    -e "ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no" \
    "$TEST_PATH" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/test/"

# Check if sync was successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ /test sync completed successfully!${NC}"
else
    echo -e "${RED}❌ /test sync failed!${NC}"
    exit 1
fi

echo -e "${GREEN}📡 Starting sync of /tests folder (PHPUnit tests)...${NC}"

# Sync tests directory (PHPUnit automated tests)
TESTS_PATH="$SCRIPT_DIR/tests/"
rsync -avz $DELETE_FLAG \
    --exclude='.vscode/' \
    --exclude='.git/' \
    --exclude='.DS_Store' \
    --exclude='*.log' \
    -e "ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no" \
    "$TESTS_PATH" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/tests/"

# Check if sync was successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ /tests sync completed successfully!${NC}"
else
    echo -e "${RED}❌ /tests sync failed!${NC}"
    exit 1
fi

echo -e "${GREEN}📡 Syncing phpunit.xml...${NC}"

# Sync phpunit.xml separately (it's in root, not in synced folders)
rsync -avz \
    -e "ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no" \
    "$SCRIPT_DIR/phpunit.xml" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/phpunit.xml"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ phpunit.xml sync completed!${NC}"
else
    echo -e "${YELLOW}⚠️ phpunit.xml sync failed (non-critical)${NC}"
fi

echo "==============================="
echo -e "${GREEN}🎉 Sync complete!${NC}"

# Run tests if --test flag was provided
if [ "$RUN_TESTS" = true ]; then
    echo ""
    echo -e "${BLUE}🧪 Running PHPUnit tests on remote server...${NC}"
    echo "==============================="

    # If a specific testsuite was provided, run just that one
    if [ -n "$TEST_SUITE" ]; then
        PHPUNIT_CMD="cd $REMOTE_PATH && php vendor/bin/phpunit --testsuite \"$TEST_SUITE\""
        ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no \
            "$REMOTE_USER@$REMOTE_HOST" "$PHPUNIT_CMD"
        TEST_EXIT_CODE=$?
    else
        # Run testsuites sequentially to avoid memory exhaustion
        # Each testsuite runs in its own PHP process with fresh memory
        # Note: "Super Forms Test Suite" excluded - runs EAV migration tests which
        # can exhaust memory on databases with many entries. Run separately with
        # --test "Super Forms Test Suite" if needed.
        TEST_EXIT_CODE=0
        TESTSUITES=("triggers" "integration")
        PASSED_SUITES=()
        FAILED_SUITES=()

        for SUITE in "${TESTSUITES[@]}"; do
            echo ""
            echo -e "${BLUE}Running testsuite: $SUITE${NC}"
            echo "-------------------------------"

            PHPUNIT_CMD="cd $REMOTE_PATH && php vendor/bin/phpunit --testsuite \"$SUITE\""
            ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no \
                "$REMOTE_USER@$REMOTE_HOST" "$PHPUNIT_CMD"
            SUITE_EXIT_CODE=$?

            if [ $SUITE_EXIT_CODE -eq 0 ]; then
                PASSED_SUITES+=("$SUITE")
                echo -e "${GREEN}✓ $SUITE passed${NC}"
            else
                FAILED_SUITES+=("$SUITE")
                echo -e "${RED}✗ $SUITE failed${NC}"
                TEST_EXIT_CODE=$SUITE_EXIT_CODE
            fi
        done

        # Print summary
        echo ""
        echo "==============================="
        echo -e "${BLUE}Test Summary:${NC}"
        if [ ${#PASSED_SUITES[@]} -gt 0 ]; then
            echo -e "${GREEN}Passed: ${PASSED_SUITES[*]}${NC}"
        fi
        if [ ${#FAILED_SUITES[@]} -gt 0 ]; then
            echo -e "${RED}Failed: ${FAILED_SUITES[*]}${NC}"
        fi
    fi

    echo "==============================="
    if [ $TEST_EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}✅ All tests passed!${NC}"
    else
        echo -e "${RED}❌ Some tests failed (exit code: $TEST_EXIT_CODE)${NC}"
    fi

    # If sandbox tests not requested, exit with test result
    if [ "$RUN_SANDBOX" != true ]; then
        exit $TEST_EXIT_CODE
    fi
fi

# Run sandbox tests if --sandbox flag was provided
if [ "$RUN_SANDBOX" = true ]; then
    echo ""
    echo -e "${BLUE}🧪 Running Sandbox Integration Tests...${NC}"
    echo "==============================="

    # Build sandbox runner command based on mode
    SANDBOX_FLAGS="--verbose"
    case $SANDBOX_MODE in
        cleanup)
            SANDBOX_FLAGS="$SANDBOX_FLAGS --cleanup"
            ;;
        reset)
            SANDBOX_FLAGS="$SANDBOX_FLAGS --reset"
            ;;
        keep)
            SANDBOX_FLAGS="$SANDBOX_FLAGS --keep"
            ;;
    esac

    # Run sandbox runner via PHP CLI
    SANDBOX_CMD="cd $REMOTE_PATH && php tests/sandbox-runner.php $SANDBOX_FLAGS"
    echo -e "${BLUE}Executing: php tests/sandbox-runner.php $SANDBOX_FLAGS${NC}"
    echo ""

    ssh -p $REMOTE_PORT -i $PRIVATE_KEY -o StrictHostKeyChecking=no \
        "$REMOTE_USER@$REMOTE_HOST" "$SANDBOX_CMD"
    SANDBOX_EXIT_CODE=$?

    echo ""
    echo "==============================="
    if [ $SANDBOX_EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}✅ Sandbox tests passed!${NC}"
    else
        echo -e "${RED}❌ Sandbox tests failed (exit code: $SANDBOX_EXIT_CODE)${NC}"
    fi

    # Combine exit codes (fail if either failed)
    if [ "$RUN_TESTS" = true ]; then
        if [ $TEST_EXIT_CODE -ne 0 ] || [ $SANDBOX_EXIT_CODE -ne 0 ]; then
            exit 1
        fi
    else
        exit $SANDBOX_EXIT_CODE
    fi
fi

echo -e "${GREEN}🎉 Done!${NC}"
