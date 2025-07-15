#!/bin/bash
# Automated WordPress setup script that runs inside the container

set -e

echo "🚀 Starting automated WordPress setup..."

# Install required tools
apt-get update > /dev/null 2>&1
apt-get install -y jq bc curl unzip default-mysql-client > /dev/null 2>&1

# Install WP-CLI
if [ ! -f /usr/local/bin/wp ]; then
    echo "📦 Installing WP-CLI..."
    curl -s -S -L https://github.com/wp-cli/wp-cli/releases/download/v2.8.1/wp-cli-2.8.1.phar -o wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
fi

# Wait for MySQL to be ready
echo "⏳ Waiting for database..."
for i in {1..30}; do
    # Simple check if we can connect to MySQL port
    if timeout 2 bash -c "</dev/tcp/$WORDPRESS_DB_HOST/3306" 2>/dev/null; then
        echo "✅ Database connection established"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "❌ Database connection timeout"
        exit 1
    fi
    sleep 2
done

cd /var/www/html

# Download WordPress core if needed
if [ ! -f wp-config-sample.php ]; then
    echo "📦 Downloading WordPress core..."
    wp core download --allow-root --quiet
fi

# Generate wp-config.php if it doesn't exist
if [ ! -f wp-config.php ]; then
    echo "⚙️ Configuring WordPress with debugging enabled..."
    wp config create \
        --dbname="$WORDPRESS_DB_NAME" \
        --dbuser="$WORDPRESS_DB_USER" \
        --dbpass="$WORDPRESS_DB_PASSWORD" \
        --dbhost="$WORDPRESS_DB_HOST" \
        --allow-root --quiet
    
    # Enable debugging for testing environment
    wp config set WP_DEBUG true --allow-root --quiet
    wp config set WP_DEBUG_LOG true --allow-root --quiet
    wp config set WP_DEBUG_DISPLAY false --allow-root --quiet
    wp config set SCRIPT_DEBUG true --allow-root --quiet
    wp config set WP_ENVIRONMENT_TYPE 'development' --allow-root --quiet
    
    # Disable FTP requirements for plugin/theme installation
    wp config set FS_METHOD 'direct' --allow-root --quiet
    wp config set FTP_USER '' --allow-root --quiet
    wp config set FTP_PASS '' --allow-root --quiet
    wp config set FTP_HOST '' --allow-root --quiet
    
    echo "🐛 Debug logging enabled from start"
    echo "🔓 FTP requirements disabled"
fi

# Install WordPress if not already installed
if ! wp core is-installed --allow-root --quiet; then
    echo "🏗️ Installing WordPress..."
    wp core install \
        --url="http://localhost:8080" \
        --title="Super Forms Test Site" \
        --admin_user="admin" \
        --admin_password="admin" \
        --admin_email="admin@superforms.test" \
        --allow-root --quiet
fi

# Activate Super Forms plugin
echo "🔌 Activating Super Forms plugin..."
wp plugin activate super-forms --allow-root --quiet 2>/dev/null || echo "Plugin activation attempted"

# Set permalink structure
wp rewrite structure '/%postname%/' --allow-root --quiet

# Update site URLs
wp option update home 'http://localhost:8080' --allow-root --quiet
wp option update siteurl 'http://localhost:8080' --allow-root --quiet

# Install and activate WordPress Importer
echo "📥 Installing WordPress Importer..."
wp plugin install wordpress-importer --activate --allow-root --quiet

# Import Super Forms XML if it exists and not already imported
EXISTING_FORMS=$(wp post list --post_type=super_form --format=count --allow-root 2>/dev/null || echo "0")
if [ "$EXISTING_FORMS" -gt "10" ]; then
    echo "📊 Forms already imported (found $EXISTING_FORMS forms)"
elif [ -f /scripts/superforms-export.xml ]; then
    echo "📊 Importing Super Forms from XML..."
    echo "   (Skipping media attachments for faster import)"
    # Skip media/attachments and only import forms
    wp import /scripts/superforms-export.xml \
        --authors=create \
        --skip=attachment \
        --allow-root \
        2>&1 | grep -v "Failed to import Media" | grep -v "Remote server"
    echo "✅ Forms imported successfully!"
    
    # Count imported forms
    FORM_COUNT=$(wp post list --post_type=super_form --format=count --allow-root)
    echo "📈 Total forms imported: $FORM_COUNT"
else
    echo "⚠️  No XML export file found at /scripts/superforms-export.xml"
fi

echo "✅ WordPress setup complete!"
echo "📊 Ready to test all forms"
echo ""
echo "🌐 Access: http://localhost:8080"
echo "👤 Admin: admin / admin"