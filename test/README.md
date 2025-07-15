# Super Forms Legacy Compatibility Testing

This testing suite validates that all 197 legacy forms from production can be successfully imported into the new Super Forms version with the updated tab-based settings system.

## Quick Start

1. **Start the test environment:**
   ```bash
   cd test/
   docker-compose up -d
   ```

2. **Wait for WordPress setup (1-2 minutes), then run tests:**
   ```bash
   docker-compose exec wpcli bash /scripts/test-forms.sh
   ```

3. **View results:**
   - **WordPress**: http://localhost:8080 (admin/admin)
   - **Test Results**: `test/test-results.json`
   - **Failed Forms**: `test/exports/tested/failed/`
   - **Success Forms**: `test/exports/tested/success/`

## What Gets Tested

- ✅ **Form Import**: WordPress post creation with correct metadata
- ✅ **Settings Migration**: PHP serialized settings → New tab system
- ✅ **Elements Validation**: JSON structure and element count verification
- ✅ **Rendering Test**: Basic form shortcode rendering
- ✅ **Data Integrity**: Original vs imported data comparison

## Test Results Structure

```
test/exports/
├── original/           # 197 extracted forms from XML export
├── tested/
│   ├── success/       # Successfully imported forms  
│   └── failed/        # Failed imports with error logs
└── test-results.json  # Comprehensive test report
```

## Manual Testing

Import a specific form:
```bash
docker-compose exec wpcli wp eval-file /scripts/import-form.php /scripts/../exports/original/form_927.json
```

Check WordPress database:
```bash
docker-compose exec wpcli wp post list --post_type=super_form
```

## Cleanup

```bash
docker-compose down -v  # Removes containers and data
```

## WordPress Environment

- **Version**: WordPress 6.8 with PHP 8.1
- **Database**: MySQL 8.0
- **Plugin**: Super Forms (mounted from `../src/`)
- **Admin**: admin/admin
- **URL**: http://localhost:8080

## Expected Results

- **Success Rate**: 95%+ (some forms may fail due to missing add-ons or specific configurations)
- **Common Issues**: Missing add-on dependencies, PHP serialization format changes
- **Settings Migration**: Old format should automatically convert to new tab-based system