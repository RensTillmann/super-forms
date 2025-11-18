#!/bin/bash

# Super Forms Sync Script
# Syncs the local project to the remote webserver
#
# Usage:
#   ./sync-to-webserver.sh          # Safe sync (no deletions)
#   ./sync-to-webserver.sh --delete # Sync with deletions (requires confirmation)

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
if [[ "$1" == "--delete" ]]; then
    USE_DELETE=true
fi

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

echo "==============================="
echo -e "${GREEN}🎉 Done!${NC}"
