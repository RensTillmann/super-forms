#!/bin/bash
# Reset Docker WordPress and import fresh forms from XML

set -e

echo "🔄 Super Forms Docker Reset & Import Script"
echo "=========================================="
echo "This will:"
echo "1. Stop and remove all containers"
echo "2. Delete all volumes (fresh database)"
echo "3. Start fresh WordPress"
echo "4. Import all 197 forms from XML"
echo ""
read -p "Continue? (y/N) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo "Cancelled."
    exit 0
fi

echo ""
echo "📦 Stopping containers..."
docker-compose down

echo "🗑️  Removing volumes for fresh start..."
docker-compose down -v

echo "🚀 Starting fresh WordPress..."
docker-compose up -d

echo "⏳ Waiting for containers to start..."
sleep 5

# First wait for database to be ready
echo "Checking database connection..."
for i in {1..30}; do
    if docker-compose exec -T db mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo "✅ Database is ready!"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "❌ Database failed to start"
        exit 1
    fi
    sleep 1
done

# Monitor WordPress setup and import process
echo ""
echo "📊 WordPress is setting up and importing forms..."
echo "This may take 2-3 minutes for all 197 forms..."
echo ""

# Follow the logs until setup is complete
docker-compose logs -f wordpress | while read line; do
    echo "$line"
    if [[ "$line" == *"WordPress setup complete!"* ]]; then
        echo ""
        echo "✅ Setup and import completed!"
        pkill -P $$ docker-compose
        break
    fi
done 2>/dev/null

# Give it a moment to stabilize
sleep 2

# Verify WordPress is accessible
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" == "200" ]] || [[ "$HTTP_CODE" == "302" ]]; then
    echo "✅ WordPress is accessible! (HTTP $HTTP_CODE)"
else
    echo "⚠️  WordPress might still be starting. Check http://localhost:8080"
fi

# The setup script will automatically:
# - Install WordPress
# - Configure FTP-free mode
# - Install WordPress Importer
# - Import the XML file
# - Activate Super Forms

echo ""
echo "🎉 Fresh WordPress with imported forms is ready!"
echo ""
echo "📊 Checking imported forms..."
docker-compose run --rm wpcli wp post list --post_type=super_form --format=count --allow-root
echo ""
echo "🌐 Access: http://localhost:8080/wp-admin/"
echo "👤 Login: admin / admin"
echo ""
echo "✅ You can now manually test all forms!"