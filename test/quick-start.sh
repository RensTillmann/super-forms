#!/bin/bash
# Quick start without importing all forms

set -e

echo "🚀 Super Forms Docker Quick Start"
echo "================================"
echo "This starts WordPress without importing all forms"
echo "(Use reset-and-import.sh for full import)"
echo ""

# Create a modified setup script without import
cat > /tmp/setup-wordpress-quick.sh << 'EOF'
#!/bin/bash
set -e

echo "🚀 Starting automated WordPress setup (Quick Mode)..."

apt-get update > /dev/null 2>&1
apt-get install -y jq bc curl unzip default-mysql-client > /dev/null 2>&1

# Install WP-CLI
if [ ! -f /usr/local/bin/wp ]; then
    echo "📦 Installing WP-CLI..."
    curl -s -S -L https://github.com/wp-cli/wp-cli/releases/download/v2.8.1/wp-cli-2.8.1.phar -o wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
fi

# Wait for MySQL
echo "⏳ Waiting for database..."
for i in {1..30}; do
    if timeout 2 bash -c "</dev/tcp/$WORDPRESS_DB_HOST/3306" 2>/dev/null; then
        echo "✅ Database ready"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "❌ Database timeout"
        exit 1
    fi
    sleep 2
done

cd /var/www/html

# Download WordPress if needed
if [ ! -f wp-config-sample.php ]; then
    echo "📦 Downloading WordPress..."
    wp core download --allow-root --quiet
fi

# Configure WordPress
if [ ! -f wp-config.php ]; then
    echo "⚙️ Configuring WordPress..."
    wp config create \
        --dbname="$WORDPRESS_DB_NAME" \
        --dbuser="$WORDPRESS_DB_USER" \
        --dbpass="$WORDPRESS_DB_PASSWORD" \
        --dbhost="$WORDPRESS_DB_HOST" \
        --allow-root --quiet
    
    # No FTP needed
    wp config set FS_METHOD 'direct' --allow-root --quiet
    wp config set WP_DEBUG true --allow-root --quiet
fi

# Install WordPress
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

# Activate Super Forms
echo "🔌 Activating Super Forms..."
wp plugin activate super-forms --allow-root --quiet 2>/dev/null || true

# Create a test form
echo "📝 Creating sample test form..."
wp eval 'include "/var/www/html/wp-content/plugins/super-forms/super-forms.php";
$post_id = wp_insert_post(array(
    "post_title" => "Test Form - JavaScript Validation",
    "post_type" => "super_form",
    "post_status" => "publish"
));
$elements = array(
    array(
        "tag" => "text",
        "group" => "form_elements",
        "data" => array(
            "name" => "test_field",
            "email" => "Test Field:",
            "placeholder" => "Type here to test",
            "conditional_action" => "show",
            "conditional_trigger" => "all",
            "conditional_items" => array(
                array("field" => "trigger", "logic" => "equal", "value" => "show")
            )
        )
    ),
    array(
        "tag" => "text",
        "group" => "form_elements",
        "data" => array(
            "name" => "trigger",
            "email" => "Type \"show\" to reveal field:",
            "placeholder" => "Type show"
        )
    )
);
update_post_meta($post_id, "_super_elements", $elements);
update_post_meta($post_id, "_super_form_settings", array("popup_enabled" => "true"));
echo "Created test form ID: $post_id";' --allow-root

echo ""
echo "✅ WordPress Quick Setup Complete!"
echo "📊 Ready for testing"
echo ""
echo "🌐 Access: http://localhost:8080"
echo "👤 Admin: admin / admin"
EOF

# Stop and clean
docker-compose down -v

# Start with custom setup
echo "🚀 Starting containers..."
docker-compose up -d

echo "⏳ Waiting for setup..."
sleep 10

# Run quick setup
docker-compose exec wordpress bash /tmp/setup-wordpress-quick.sh

echo ""
echo "✅ Quick start complete!"
echo ""
echo "You can now:"
echo "1. Access WordPress at http://localhost:8080/wp-admin/"
echo "2. Login with admin / admin"
echo "3. A test form has been created"
echo "4. To import all 197 forms, run: ./reset-and-import.sh"