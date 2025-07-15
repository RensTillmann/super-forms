#!/bin/bash
# Auto-setup script for WordPress with Super Forms

# Wait for WordPress to be ready
while ! curl -f http://localhost:80 > /dev/null 2>&1; do
    echo "Waiting for WordPress to start..."
    sleep 5
done

echo "WordPress is ready, starting auto-setup..."

# Install WP-CLI if not available
if ! command -v wp &> /dev/null; then
    echo "Installing WP-CLI..."
    curl -O https://raw.githubusercontent.com/wp-cli/wp-cli/v2.8.1/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
fi

# Navigate to WordPress directory
cd /var/www/html

# Configure WordPress if not already configured
if [ ! -f wp-config.php ]; then
    echo "Configuring WordPress..."
    wp config create \
        --dbname=superforms_test \
        --dbuser=superforms \
        --dbpass=superforms123 \
        --dbhost=db \
        --allow-root
fi

# Install WordPress if not already installed
if ! wp core is-installed --allow-root; then
    echo "Installing WordPress..."
    wp core install \
        --url=http://localhost:8080 \
        --title="Super Forms Test Site" \
        --admin_user=admin \
        --admin_password=admin \
        --admin_email=admin@superforms.test \
        --allow-root
fi

# Activate Super Forms plugin
echo "Activating Super Forms plugin..."
wp plugin activate super-forms --allow-root

# Set permalink structure for better URLs
wp rewrite structure '/%postname%/' --allow-root

# Update site URL to match Docker setup
wp option update home 'http://localhost:8080' --allow-root
wp option update siteurl 'http://localhost:8080' --allow-root

echo "WordPress setup complete!"
echo "Access: http://localhost:8080"
echo "Admin: http://localhost:8080/wp-admin"
echo "User: admin / Password: admin"