# Super Forms Docker Testing Guide

## 🚀 Quick Start

### Fresh Install with All Forms
```bash
cd /projects/super-forms/test
./reset-and-import.sh
```

This will:
- Stop and remove existing containers
- Delete all data (fresh database)
- Start new WordPress instance
- Automatically import all 197 forms from XML
- No FTP credentials required!

### Access WordPress
- URL: http://localhost:8080/wp-admin/
- Username: `admin`
- Password: `admin`

## 🔧 Features

### No FTP Credentials Required
The Docker setup now includes:
```php
define('FS_METHOD', 'direct');
```
This allows you to:
- Install plugins without FTP
- Install themes without FTP
- Update WordPress without FTP

### Automatic XML Import
On every fresh start, the setup automatically:
1. Installs WordPress Importer plugin
2. Imports `superforms-export.xml` with all 197 forms
3. Creates authors as needed
4. Activates Super Forms plugin

## 📝 Manual Testing Workflow

### 1. Start Fresh
```bash
./reset-and-import.sh
```

### 2. Test Forms
1. Go to Super Forms > All Forms
2. You'll see all 197 imported forms
3. Edit any form to test:
   - JavaScript errors
   - Loading performance
   - Conditional logic
   - Email settings
   - Tab switching

### 3. Check Specific Issues
- **Form 8**: Originally had JavaScript syntax error
- **Forms with popups**: Test GSAP/css-plugin.js
- **Forms with email reminders**: Test migration
- **Forms with conditional logic**: Test performance

## 🛠️ Docker Commands

### Start containers
```bash
docker-compose up -d
```

### Stop containers
```bash
docker-compose down
```

### Reset everything (fresh install)
```bash
docker-compose down -v
docker-compose up -d
```

### View logs
```bash
docker-compose logs -f wordpress
```

### Run WP-CLI commands
```bash
docker-compose run --rm wpcli wp post list --post_type=super_form
```

## 🐛 Debugging

### Check PHP errors
```bash
docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

### Check imported forms
```bash
docker-compose run --rm wpcli wp post list --post_type=super_form --format=table
```

### Test specific form
```bash
# Replace 123 with form ID
docker-compose run --rm wpcli wp post get 123 --field=post_title
```

## 📊 What Gets Imported

The XML file contains:
- 163 unique published forms (after removing backups/duplicates)
- All form settings
- All form elements
- Email configurations
- Conditional logic rules
- Form metadata

**Note**: The XML includes backup versions of forms. The import process automatically filters these out, keeping only published forms.

## ✅ Testing Checklist

For each form, check:
- [ ] Form loads without hanging
- [ ] No JavaScript errors in console
- [ ] Tabs switch smoothly
- [ ] Conditional logic works
- [ ] Form preview displays correctly
- [ ] Settings save properly

## 🔄 Updating the Plugin

When you make changes to the plugin:
1. Files are automatically synced (mounted volume)
2. No need to restart Docker
3. Just refresh the browser

## 🎯 Known Issues Fixed

1. **JavaScript Syntax Error**: Fixed in css-plugin.js
2. **Infinite Loops**: Fixed with debouncing and dependency tracking
3. **Form 8 Hanging**: Fixed by handling corrupted data

## 💡 Tips

- Use browser DevTools (F12) to monitor console
- Test forms with complex conditional logic for performance
- Check forms with email reminders for proper migration
- Try forms with popup features to verify css-plugin.js fix

## 🚨 Troubleshooting

### Container won't start
```bash
docker-compose logs wordpress
```

### Forms not importing
Check if XML file exists:
```bash
ls -la test/superforms-export.xml
```

### Permission issues
```bash
docker-compose exec wordpress chown -R www-data:www-data /var/www/html
```

---

Happy testing! 🎉