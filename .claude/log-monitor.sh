#!/bin/bash
# Claude Code Log Monitor
# Quick commands to monitor Claude activity

PROJECT_ROOT="/projects/super-forms"
LOG_DIR="$PROJECT_ROOT/.claude/logs"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 Claude Code Log Monitor${NC}"
echo "=================================="

# Check if logs directory exists
if [ ! -d "$LOG_DIR" ]; then
    echo -e "${RED}❌ Log directory not found: $LOG_DIR${NC}"
    exit 1
fi

case "${1:-summary}" in
    "summary"|"")
        echo -e "${GREEN}📊 Generating activity summary...${NC}"
        cd "$PROJECT_ROOT"
        python3 .claude/log-analyzer.py summary
        ;;
    "tail")
        LINES=${2:-20}
        echo -e "${GREEN}📜 Showing last $LINES log entries...${NC}"
        cd "$PROJECT_ROOT"
        python3 .claude/log-analyzer.py tail "$LINES"
        ;;
    "live")
        echo -e "${GREEN}👁️  Monitoring logs in real-time (Ctrl+C to stop)...${NC}"
        echo "Watching for new log entries..."
        # Monitor all log files for changes
        find "$LOG_DIR" -name "*.log" -exec tail -f {} +
        ;;
    "security")
        echo -e "${YELLOW}🔒 Security analysis:${NC}"
        cd "$PROJECT_ROOT"
        python3 .claude/log-analyzer.py security
        ;;
    "phpcs")
        echo -e "${YELLOW}✅ PHPCS analysis:${NC}"
        cd "$PROJECT_ROOT"
        python3 .claude/log-analyzer.py phpcs
        ;;
    "sync")
        echo -e "${YELLOW}🚀 Sync analysis:${NC}"
        cd "$PROJECT_ROOT"
        python3 .claude/log-analyzer.py sync
        ;;
    "clean")
        echo -e "${YELLOW}🧹 Cleaning old logs...${NC}"
        find "$LOG_DIR" -name "*.log" -mtime +7 -delete
        echo "Deleted logs older than 7 days"
        ;;
    "size")
        echo -e "${GREEN}📁 Log file sizes:${NC}"
        du -h "$LOG_DIR"/*.log 2>/dev/null || echo "No log files found"
        ;;
    "help")
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  summary          Show activity summary (default)"
        echo "  tail [lines]     Show recent log entries (default: 20)"
        echo "  live             Monitor logs in real-time"
        echo "  security         Show security analysis"
        echo "  phpcs           Show PHPCS analysis"
        echo "  sync            Show sync analysis"
        echo "  clean           Delete logs older than 7 days"
        echo "  size            Show log file sizes"
        echo "  help            Show this help"
        ;;
    *)
        echo -e "${RED}❌ Unknown command: $1${NC}"
        echo "Use '$0 help' for available commands"
        exit 1
        ;;
esac