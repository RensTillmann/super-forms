# WordPress Plugin Development Automation Setup

*Setup Guide for Super Forms WordPress Plugin Development*

## Overview

This project now includes comprehensive WordPress plugin development automation with:
- **PHPCS** with WordPress Coding Standards
- **Plugin Check** integration
- **Security validation** scripts
- **Automated hooks** for quality assurance
- **Comprehensive guidelines** and best practices

## Quick Start

### 1. Install Development Dependencies

```bash
# Install Composer dependencies (PHPCS, WordPress standards)
composer install --dev

# Install Node.js dependencies (existing)
npm install
```

### 2. Install WordPress Development Tools

```bash
# Install WP-CLI (if not already installed)
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Install Plugin Check plugin
wp plugin install plugin-check --activate
```

### 3. Verify Setup

```bash
# Test PHPCS installation
vendor/bin/phpcs --version

# Test WordPress standards
vendor/bin/phpcs --standard=WordPress --version

# Test security script
python3 wp-security-check.py --help
```

## Automated Development Workflow

### Claude Code Hooks

The automation system includes several hooks that run automatically:

#### PreToolUse Hooks
- **Command Validation**: Suggests performance improvements (use `rg` instead of `grep`)
- **WordPress Pre-validation**: Reminds about WordPress security and naming conventions

#### PostToolUse Hooks
- **PHPCS Validation**: Runs WordPress coding standards check
- **JavaScript Validation**: Runs JSHint for JavaScript files
- **Plugin Check**: Validates against WordPress.org requirements
- **Security Validation**: Checks for common security issues
- **Auto-sync**: Syncs to webserver after changes

#### Stop Hooks
- **Final Validation**: Comprehensive checklist and final code quality checks

### Manual Tools

#### 1. PHPCS - WordPress Coding Standards

```bash
# Run all checks
composer run phpcs

# Fix auto-fixable issues
composer run phpcs:fix

# Generate detailed report
composer run phpcs:report

# Check PHP compatibility
composer run compatibility

# Full validation suite
composer run validate
```

#### 2. Plugin Check - WordPress.org Validation

```bash
# Basic plugin check
wp plugin check super-forms

# Detailed check with specific categories
wp plugin check super-forms --checks=security,performance,accessibility

# Generate report
wp plugin check super-forms --format=json > plugin-check-report.json
```

#### 3. Security Scanner

```bash
# Basic security scan
python3 wp-security-check.py

# Verbose output
python3 wp-security-check.py --verbose

# JSON output
python3 wp-security-check.py --json

# Save report
python3 wp-security-check.py --output security-report.json
```

## Development Guidelines

### File Structure

```
super-forms/
├── wp-plugin-guidelines.md       # Comprehensive development guidelines
├── CLAUDE.md                     # Project-specific Claude instructions
├── composer.json                 # PHP dependencies and scripts
├── phpcs.xml                     # PHPCS configuration
├── wp-security-check.py          # Security validation script
├── .claude/
│   └── settings.json             # Claude Code automation hooks
└── src/                          # Plugin source code
```

### WordPress Security Checklist

When editing WordPress files, ensure:

- [ ] **Nonce verification** for all form submissions
- [ ] **Input sanitization** using WordPress functions
- [ ] **Output escaping** with appropriate context
- [ ] **Capability checks** for admin functionality
- [ ] **Prepared statements** for database queries
- [ ] **Proper prefixing** for all global functions/variables

### Code Quality Standards

1. **WordPress Coding Standards**: Follow WPCS rules
2. **Security First**: Implement security by design
3. **Performance**: Optimize queries and asset loading
4. **Accessibility**: Ensure WCAG 2.1 AA compliance
5. **Internationalization**: Make all strings translatable

## Integration with Existing Workflow

### NPM Scripts (Enhanced)

```json
{
  "scripts": {
    "prod": "npm run sass && npm run phpcs && npm run security-check",
    "dev": "npm run sass && npm run phpcs:fix",
    "phpcs": "vendor/bin/phpcs",
    "phpcs:fix": "vendor/bin/phpcbf",
    "security-check": "python3 wp-security-check.py",
    "wp-check": "wp plugin check super-forms"
  }
}
```

### Git Hooks (Optional)

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash
echo "Running WordPress plugin validation..."

# Run PHPCS
composer run phpcs || exit 1

# Run security check
python3 wp-security-check.py || exit 1

# Run plugin check if available
if command -v wp >/dev/null 2>&1; then
    wp plugin check super-forms || exit 1
fi

echo "✅ All checks passed!"
```

## Troubleshooting

### Common Issues

1. **PHPCS not found**
   ```bash
   composer install --dev
   ```

2. **WordPress standards not available**
   ```bash
   composer require --dev wp-coding-standards/wpcs
   ```

3. **Plugin Check not available**
   ```bash
   wp plugin install plugin-check --activate
   ```

4. **Python script permissions**
   ```bash
   chmod +x wp-security-check.py
   ```

### Performance Optimization

If checks are slow:
- Use `--parallel=20` for PHPCS
- Exclude unnecessary directories in `phpcs.xml`
- Run security checks only on changed files

## Continuous Integration

For automated testing in CI/CD:

```yaml
name: WordPress Plugin Validation
on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          
      - name: Install dependencies
        run: composer install --dev
        
      - name: Run PHPCS
        run: composer run phpcs
        
      - name: Run security check
        run: python3 wp-security-check.py
        
      - name: Setup WordPress CLI
        run: |
          curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
          chmod +x wp-cli.phar
          sudo mv wp-cli.phar /usr/local/bin/wp
          
      - name: Run Plugin Check
        run: wp plugin check super-forms
```

## Resources

### Documentation Files
- `wp-plugin-guidelines.md` - Complete WordPress development guidelines
- `CLAUDE.md` - Project-specific development instructions
- `composer.json` - PHP tooling configuration
- `phpcs.xml` - Code quality rules configuration

### External Resources
- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)
- [Plugin Check Documentation](https://wordpress.org/plugins/plugin-check/)
- [WP-CLI Documentation](https://wp-cli.org/)

## Support

For issues with the automation setup:
1. Check the troubleshooting section above
2. Review the error messages from individual tools
3. Consult the WordPress Plugin Guidelines (`wp-plugin-guidelines.md`)
4. Verify all dependencies are installed correctly

---

*This automation setup ensures high-quality, secure WordPress plugin development while maintaining development velocity and reducing manual validation overhead.*