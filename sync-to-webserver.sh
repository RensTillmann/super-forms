#!/bin/bash

# Super Forms Sync Script
# Syncs the local project to the remote webserver

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
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Super Forms Sync Script${NC}"
echo "==============================="
echo "Local Path:  $LOCAL_PATH"
echo "Remote Host: $REMOTE_HOST:$REMOTE_PORT"
echo "Remote Path: $REMOTE_PATH"
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

# Confirm before sync
#echo -e "${YELLOW}⚠️  This will sync all files to the remote server.${NC}"
#read -p "Do you want to continue? (y/N): " -n 1 -r
#echo
#if [[ ! $REPLY =~ ^[Yy]$ ]]; then
#    echo "Sync cancelled."
#    exit 0
#fi

echo -e "${GREEN}📡 Starting sync of /src folder...${NC}"

# Rsync command with SFTP over SSH
# --delete removes files on remote that don't exist locally
# --exclude excludes specified patterns
# -avz: archive mode, verbose, compress
# -e specifies SSH with custom port and key
# Note: LOCAL_PATH ends with / to sync contents of src/ into the remote directory
rsync -avz --delete \
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
    echo -e "${GREEN}✅ Sync completed successfully!${NC}"
else
    echo -e "${RED}❌ Sync failed!${NC}"
    exit 1
fi

echo "==============================="
echo -e "${GREEN}🎉 Done!${NC}"
