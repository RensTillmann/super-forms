#!/bin/bash

# Configuration for SiteGround dev server
REMOTE_HOST="f4d.nl"  # Using your SSH config alias
REMOTE_PATH="~/www/f4d.nl/public_html/dev/wp-content/plugins/super-forms/"
LOCAL_PATH="/projects/super-forms/src/"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Syncing Super Forms to dev server...${NC}"
echo -e "${BLUE}Using SSH config for: $REMOTE_HOST${NC}"
echo -e "${BLUE}Remote path: $REMOTE_PATH${NC}"

# Sync files excluding certain directories/files
echo -e "${BLUE}Starting file sync...${NC}"
rsync -avz --delete \
    --exclude='.git/' \
    --exclude='node_modules/' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='dist/' \
    --exclude='gitzip*' \
    --exclude='package-lock.json' \
    --progress \
    -e "ssh -o BatchMode=no" \
    "$LOCAL_PATH" \
    "$REMOTE_HOST:$REMOTE_PATH"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Files synced successfully to SiteGround dev server${NC}"
    echo -e "${GREEN}✓ Your changes are now live at f4d.nl/dev${NC}"
else
    echo -e "${RED}✗ Sync failed${NC}"
    exit 1
fi
