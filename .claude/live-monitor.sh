#!/bin/bash
# Claude Code Live Activity Monitor
# Real-time monitoring with timestamps and filtering

PROJECT_ROOT="/projects/super-forms"
LOG_DIR="$PROJECT_ROOT/.claude/logs"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Clear screen and show header
clear
echo -e "${BLUE}┌─────────────────────────────────────────────────────────────────┐${NC}"
echo -e "${BLUE}│${NC}                ${GREEN}🔍 CLAUDE CODE LIVE MONITOR${NC}                   ${BLUE}│${NC}"
echo -e "${BLUE}│${NC}                    ${YELLOW}Press Ctrl+C to exit${NC}                     ${BLUE}│${NC}"
echo -e "${BLUE}└─────────────────────────────────────────────────────────────────┘${NC}"
echo ""

# Function to format log entries with colors
format_log_entry() {
    local line="$1"
    local timestamp=$(echo "$line" | cut -d'|' -f1 | xargs)
    local type=$(echo "$line" | cut -d'|' -f2 | xargs)
    local details=$(echo "$line" | cut -d'|' -f3- | xargs)
    
    case "$type" in
        "HOOK")
            echo -e "${timestamp} ${PURPLE}🎣 HOOK${NC}     │ ${details}"
            ;;
        "SECURITY")
            if [[ "$details" == *"clean"* ]]; then
                echo -e "${timestamp} ${GREEN}🔒 SECURITY${NC}  │ ${details}"
            else
                echo -e "${timestamp} ${YELLOW}⚠️  SECURITY${NC}  │ ${details}"
            fi
            ;;
        "PHPCS")
            if [[ "$details" == *"0 violations"* ]]; then
                echo -e "${timestamp} ${GREEN}✅ PHPCS${NC}    │ ${details}"
            else
                echo -e "${timestamp} ${YELLOW}📝 PHPCS${NC}    │ ${details}"
            fi
            ;;
        "SYNC")
            if [[ "$details" == *"✅"* ]]; then
                echo -e "${timestamp} ${GREEN}🚀 SYNC${NC}     │ ${details}"
            else
                echo -e "${timestamp} ${RED}❌ SYNC${NC}     │ ${details}"
            fi
            ;;
        "COMMAND")
            echo -e "${timestamp} ${CYAN}⚡ COMMAND${NC}  │ ${details}"
            ;;
        *)
            echo -e "${timestamp} ${BLUE}📋 ${type}${NC} │ ${details}"
            ;;
    esac
}

# Function to show recent activity first
show_recent_activity() {
    echo -e "${YELLOW}📜 Recent activity (last 5 events):${NC}"
    echo "─────────────────────────────────────────────────────────────────"
    
    cd "$PROJECT_ROOT"
    python3 .claude/log-analyzer.py tail 5 | grep -E "^\d{4}-\d{2}-\d{2}" | while read line; do
        format_log_entry "$line"
    done
    
    echo "─────────────────────────────────────────────────────────────────"
    echo -e "${GREEN}👁️  Watching for new activity...${NC}"
    echo ""
}

# Check if logs directory exists
if [ ! -d "$LOG_DIR" ]; then
    echo -e "${RED}❌ Log directory not found: $LOG_DIR${NC}"
    echo "Make sure you're in the Super Forms project directory"
    exit 1
fi

# Show recent activity first
show_recent_activity

# Monitor all log files for new entries
if command -v inotifywait >/dev/null 2>&1; then
    # Use inotifywait for efficient monitoring (if available)
    inotifywait -m -e modify "$LOG_DIR"/*.log 2>/dev/null | while read path action file; do
        echo -e "${GREEN}📝 New activity in ${file}${NC}"
        # Show the new entry
        tail -n 1 "$LOG_DIR/$file" | while read line; do
            if [[ "$line" == *"timestamp"* ]]; then
                # Parse JSON log entry
                timestamp=$(echo "$line" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data['timestamp'][:19])")
                event_type=$(echo "$line" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data['event_type'].upper())")
                echo -e "${timestamp} ${GREEN}📋 ${event_type}${NC} │ New log entry"
            fi
        done
    done
else
    # Fallback: use tail -f for monitoring
    echo -e "${YELLOW}💡 Install inotify-tools for better monitoring: sudo apt install inotify-tools${NC}"
    echo ""
    tail -f "$LOG_DIR"/*.log | while read line; do
        if [[ "$line" == *"timestamp"* ]]; then
            # Parse JSON log entry
            timestamp=$(echo "$line" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data['timestamp'][:19])" 2>/dev/null || echo "$(date '+%Y-%m-%d %H:%M:%S')")
            event_type=$(echo "$line" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data['event_type'].upper())" 2>/dev/null || echo "ACTIVITY")
            echo -e "${timestamp} ${GREEN}📋 ${event_type}${NC} │ New activity detected"
        fi
    done
fi