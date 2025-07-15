#!/bin/bash
# Setup Playwright for automated browser testing

echo "🎭 Setting up Playwright for automated testing..."

# Install Node.js if not present
if ! command -v node &> /dev/null; then
    echo "📦 Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y nodejs
fi

# Install Playwright
echo "🎭 Installing Playwright..."
npm install -g playwright@latest

# Install browsers
echo "🌐 Installing Playwright browsers..."
npx playwright install chromium

echo "✅ Playwright setup complete!"
node --version
npx playwright --version