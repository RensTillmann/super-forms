#!/bin/bash

# Monitor WordPress debug log in real-time
# Usage: ./monitor-debug-log.sh

echo "=== WordPress Debug Log Monitor ==="
echo "Starting real-time monitoring of WordPress debug.log..."
echo "Press Ctrl+C to stop monitoring"
echo ""

# Clear any old logs for a fresh start
echo "[$(date)] === Starting fresh debug log monitoring ===" | docker exec -i test-wordpress-1 tee -a /var/www/html/wp-content/debug.log > /dev/null

# Start tailing the debug log
docker exec test-wordpress-1 tail -f /var/www/html/wp-content/debug.log