#!/bin/bash
# One-command test runner for Super Forms legacy compatibility

set -e

echo "🧪 Super Forms Legacy Compatibility Test Runner"
echo "=============================================="
echo ""

# Restart containers to ensure clean state
echo "🔄 Restarting containers..."
docker-compose down -v --remove-orphans > /dev/null 2>&1 || true
docker-compose up -d

echo "⏳ Waiting for containers to start..."
sleep 10

# Run setup inside WordPress container
echo "🚀 Setting up WordPress..."
docker-compose exec -T wordpress bash /setup-wordpress.sh

echo ""
echo "🧪 Starting form compatibility tests..."
echo ""

# Run the test suite (clean version)
docker-compose exec -T wordpress bash -c "
export TERM=xterm
cd /var/www/html
bash /scripts/test-forms-clean.sh
"

echo ""
echo "📊 Test completed! Check results:"
echo "- Original forms: test/exports/original/ (UNTOUCHED)"
echo "- Test report: test/test-results.json"
echo "- Container results: /tmp/forms/ (inside container)"
echo ""
echo "💡 Your original export files remain clean for easy re-testing!"