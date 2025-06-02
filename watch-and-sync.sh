#!/bin/bash

# Auto-sync script that watches for file changes and syncs automatically
# Requires: inotify-tools (install with: sudo apt-get install inotify-tools)

LOCAL_PATH="/projects/super-forms/src/"
SYNC_SCRIPT="/projects/super-forms/sync-to-dev.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Starting file watcher for Super Forms...${NC}"
echo -e "${YELLOW}Watching: $LOCAL_PATH${NC}"
echo -e "${YELLOW}Will auto-sync changes to f4d.nl/dev${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop${NC}"
echo ""

# Check if inotify-tools is installed
if ! command -v inotifywait &> /dev/null; then
    echo -e "${RED}inotify-tools not found${NC}"
    echo -e "${YELLOW}Please install with: sudo apt-get install inotify-tools${NC}"
    exit 1
fi

# Watch for file changes and sync
inotifywait -m -r -e modify,create,delete,move --format '%w%f %e' "$LOCAL_PATH" |
while read file event; do
    # Skip certain files/directories
    if [[ "$file" =~ \.(log|tmp|swp)$ ]] || [[ "$file" =~ /(\.git|node_modules|dist)/ ]]; then
        continue
    fi
    
    echo -e "${BLUE}[$(date '+%H:%M:%S')] File changed: $file ($event)${NC}"
    echo -e "${YELLOW}Syncing to dev server...${NC}"
    
    # Run sync script
    if bash "$SYNC_SCRIPT"; then
        echo -e "${GREEN}✓ Sync completed successfully${NC}"
    else
        echo -e "${RED}✗ Sync failed${NC}"
    fi
    echo ""
done