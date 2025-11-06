# Testing & Quality Assurance Guide

## Testing Philosophy

**The Golden Rule:** Every change must be tested in the actual environment before claiming it's fixed.

- Untested code is not a solution—it's a guess
- "Looks correct" ≠ "Works correctly"
- If you can't test it, don't ship it
- User-reported bugs on untested "fixes" destroy trust

## WordPress-Specific Testing Requirements

### Frontend Validation

**ALWAYS test forms render correctly on frontend:**

1. Load the form on a public-facing page
2. Test form submission with various input types
3. Verify conditional logic works correctly
4. Check for JavaScript errors in browser console
5. Test on multiple browsers (Chrome, Firefox, Safari)
6. Test on mobile devices (responsive design)

**Testing Checklist:**
- [ ] Form renders without layout issues
- [ ] All field types display correctly
- [ ] Validation messages appear appropriately
- [ ] Form submission succeeds
- [ ] Success message displays
- [ ] Email notifications send correctly
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug.log

### Admin Functionality

**ALWAYS verify all admin features work without errors:**

1. Access WordPress admin area
2. Navigate to plugin settings/pages
3. Test AJAX operations (save, delete, update)
4. Verify UI updates correctly
5. Check for JavaScript errors in console
6. Check for PHP errors in debug.log

**Testing Checklist:**
- [ ] Admin pages load without errors
- [ ] Settings save successfully
- [ ] List tables display data correctly
- [ ] Search and filters work
- [ ] Bulk actions complete successfully
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug.log

### Database Operations

**ALWAYS confirm all database queries execute properly:**

```bash
# Check for database errors in debug.log
tail -f wp-content/debug.log | grep -E "(Error|Warning|Notice)"

# Verify data was saved
wp db query "SELECT * FROM wp_superforms_entry_data WHERE entry_id = 123;"

# Check migration status
wp option get superforms_eav_migration --format=json

# Monitor queries with Query Monitor plugin
# Admin bar → Query Monitor → Database Queries
```

**Testing Checklist:**
- [ ] Data saves to correct tables
- [ ] Queries use prepared statements (no SQL injection)
- [ ] Indexes are used (check EXPLAIN query)
- [ ] No duplicate queries (N+1 problem)
- [ ] Transaction logic works correctly
- [ ] Foreign key constraints respected

### JavaScript Console

**ALWAYS check for JS errors in browser console:**

1. Open browser DevTools (F12)
2. Navigate to Console tab
3. Clear console
4. Perform the action/feature
5. Look for errors, warnings, and failed requests
6. Check Network tab for failed AJAX requests

**Common Issues to Check:**
- Uncaught TypeError/ReferenceError
- Failed network requests (404, 500 errors)
- CORS issues
- Nonce verification failures
- Missing dependencies (jQuery, etc.)

### Plugin Lifecycle

**ALWAYS test activation, deactivation, and uninstall processes:**

```bash
# Via WP-CLI
wp plugin activate super-forms
wp plugin deactivate super-forms
wp plugin uninstall super-forms --skip-delete

# Check database tables created
wp db query "SHOW TABLES LIKE '%superforms%';"

# Check options created
wp option list --search=super --format=table
```

**Testing Checklist:**
- [ ] Plugin activates without errors
- [ ] Database tables created correctly
- [ ] Default options set properly
- [ ] Plugin deactivates cleanly
- [ ] No fatal errors or warnings
- [ ] Reactivation works correctly
- [ ] Uninstall removes data (if configured)

### Security Verification

**ALWAYS confirm nonces, sanitization, and capability checks:**

1. **Nonce Verification:**
   - Remove or modify nonce in request
   - Should fail with "Security check failed"

2. **Capability Checks:**
   - Login as non-admin user
   - Try to access admin pages
   - Should fail with permission error

3. **Input Sanitization:**
   - Try SQL injection: `'; DROP TABLE wp_posts; --`
   - Try XSS: `<script>alert('xss')</script>`
   - Should be sanitized/escaped

4. **File Upload Security:**
   - Try uploading PHP file disguised as image
   - Should reject non-allowed file types

5. **SQL Injection Prevention (v6.4.126 hardening):**
   - All DELETE queries use `$wpdb->prepare()` with placeholders
   - TRUNCATE TABLE operations validate table existence first
   - No direct integer interpolation in SQL queries
   - Table names sanitized with `esc_sql()` after whitelist validation

**Testing Checklist:**
- [ ] Nonces verified on all forms
- [ ] User capabilities checked
- [ ] All user input sanitized
- [ ] All output escaped
- [ ] SQL queries use prepared statements (including DELETE and cleanup queries)
- [ ] TRUNCATE operations validate table existence
- [ ] Table names validated against whitelist before use
- [ ] File uploads validate type and size

### Performance Impact

**ALWAYS monitor query count and page load times:**

1. Install Query Monitor plugin
2. Access page/feature
3. Check Query Monitor → Database Queries
4. Look for:
   - Total query count (should be <50 for admin pages)
   - Slow queries (>0.05s)
   - Duplicate queries (N+1 problem)
   - Queries not using indexes

**Testing Checklist:**
- [ ] Page load time <2 seconds
- [ ] Database queries <50 per page
- [ ] No queries >0.1s (slow query)
- [ ] No duplicate queries
- [ ] Memory usage <64MB
- [ ] No memory leaks on repeated actions

## Local Testing Environment (wp-env)

### Setup

```bash
# Install dependencies
npm install

# Start WordPress environment
npm run env:start

# Access site
# Frontend: http://localhost:8888
# Admin: http://localhost:8888/wp-admin
# Username: admin
# Password: password
```

### Testing Workflow

1. **Make code changes** in `/src/` directory
2. **Changes are live** immediately (plugin mapped to wp-env)
3. **Test in browser** at localhost:8888
4. **Check debug log** at `wp-content/debug.log` (inside Docker)
5. **Reset environment** if needed: `npm run env:restart`

### Accessing wp-env Shell

```bash
# Enter WordPress container
docker exec -it $(docker ps -q -f name=wordpress) bash

# Run WP-CLI commands
wp plugin list
wp option get superforms_eav_migration --format=json
wp db query "SHOW TABLES LIKE '%superforms%';"

# View debug log
tail -f wp-content/debug.log
```

### Database Access

```bash
# Access MySQL container
docker exec -it $(docker ps -q -f name=mysql) bash

# Connect to database
mysql -u root -p
# Password: password

# Use WordPress database
USE wordpress;
SHOW TABLES LIKE '%superforms%';
SELECT * FROM wp_superforms_entry_data LIMIT 10;
```

## Manual Testing Procedures

### Test Plan Template

For each feature/bug fix:

1. **What changed?** (Brief description)
2. **How to test?** (Step-by-step instructions)
3. **Expected result?** (What should happen)
4. **Actual result?** (What actually happened)
5. **Pass/Fail?** (Did it work?)

**Example:**
```
Feature: Re-verify Failed Entries button

What changed:
- Added "Re-verify Failed Entries" button to Developer Tools
- Added AJAX handler for re-verification
- Fixed JSON encoding bug in data access layer

How to test:
1. Navigate to Super Forms > Developer Tools
2. Check "Failed Entries" section
3. Click "Re-verify Failed Entries" button
4. Wait for AJAX request to complete
5. Check if entries moved from failed to passed

Expected result:
- Button shows "Re-verifying..." during request
- Success message shows count of passed entries
- Failed entries table updates to remove passed entries
- Page reloads if entries passed verification

Actual result:
✅ Button disabled during request
✅ Success message appeared
✅ Failed count decreased from 14 to 0
✅ Page reloaded after 1.5 seconds

Pass/Fail: PASS
```

### Regression Testing

When fixing bugs, test related functionality:

**Example:** When fixing data access layer:
1. Test entry creation (frontend form submission)
2. Test entry editing (admin area)
3. Test entry deletion
4. Test CSV export
5. Test entry search/filtering
6. Test migration process
7. Test data integrity verification

## Automated Testing

### Current Status

- ✅ JSHint for JavaScript linting (`npm run jshint`)
- ❌ No unit tests configured
- ❌ No integration tests configured
- ❌ No E2E tests configured

### Planned Testing Stack

**Unit Tests (PHP):**
- PHPUnit for WordPress plugin unit testing
- Mock WordPress functions
- Test business logic in isolation

**Integration Tests (PHP):**
- WordPress test suite
- Test database operations
- Test WordPress hooks/filters

**JavaScript Tests:**
- Jest for React components
- Test component rendering
- Test user interactions
- Test state management

**E2E Tests:**
- Playwright or Cypress
- Test critical user flows
- Test form submissions
- Test admin workflows

## Testing Checklist Template

Copy this for each feature/bug fix:

```
## Feature/Bug: [Description]

### Pre-Test Setup
- [ ] wp-env running at localhost:8888
- [ ] WP_DEBUG enabled in wp-config.php
- [ ] Query Monitor plugin active
- [ ] Browser DevTools open (Console + Network tabs)
- [ ] debug.log accessible and cleared

### Frontend Testing
- [ ] Form renders correctly
- [ ] Form submits successfully
- [ ] Validation works
- [ ] No JavaScript errors
- [ ] No PHP errors
- [ ] Mobile responsive

### Backend Testing
- [ ] Admin page loads
- [ ] Settings save correctly
- [ ] AJAX operations work
- [ ] No JavaScript errors
- [ ] No PHP errors

### Database Testing
- [ ] Data saves correctly
- [ ] Queries use prepared statements
- [ ] No duplicate queries
- [ ] Indexes used
- [ ] No SQL errors

### Security Testing
- [ ] Nonces verified
- [ ] Capabilities checked
- [ ] Input sanitized
- [ ] Output escaped
- [ ] No XSS vulnerabilities
- [ ] No SQL injection vulnerabilities

### Performance Testing
- [ ] Page load <2 seconds
- [ ] Query count <50
- [ ] No slow queries (>0.1s)
- [ ] Memory usage <64MB

### Regression Testing
- [ ] Related features still work
- [ ] No new bugs introduced
- [ ] Previous fixes not broken

### Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browsers

### Final Verification
- [ ] Feature works as expected
- [ ] All tests passed
- [ ] No errors in logs
- [ ] Code committed
- [ ] Documentation updated

**Tested by:** [Your Name]
**Date:** [YYYY-MM-DD]
**Result:** PASS/FAIL
**Notes:** [Any observations or issues]
```

## Debugging Failed Tests

### When Tests Fail

1. **Don't guess** - Read the actual error message
2. **Check logs** - debug.log, browser console, network tab
3. **Isolate issue** - Disable other plugins, use default theme
4. **Reproduce** - Can you trigger the bug consistently?
5. **Document** - Record exact steps to reproduce

### Common Test Failure Causes

**JavaScript Errors:**
- Missing dependencies (jQuery not loaded)
- Nonce expired (refresh page)
- AJAX URL incorrect (check `ajaxurl` variable)
- Syntax errors (check console)

**PHP Errors:**
- Missing file includes
- Undefined functions/classes
- Database connection errors
- Permission issues

**Database Errors:**
- Table doesn't exist (run migrations)
- Column missing (check schema version)
- SQL syntax error (check prepared statements)
- Duplicate entry (unique constraint violation)

**Performance Issues:**
- N+1 query problem (use bulk queries)
- Missing indexes (add to schema)
- Large dataset (add pagination)
- Slow query (optimize SQL)

## Test Coverage Goals

**Critical Paths (Must Test):**
- Form submission (frontend)
- Entry creation/editing (admin)
- Migration process
- Data export (CSV)
- Settings save

**Important Features (Should Test):**
- Form builder operations
- Conditional logic
- Email notifications
- Payment integrations
- Add-on functionality

**Nice-to-Have (Can Test):**
- UI animations
- Edge cases
- Error messages
- Loading states

## Testing Best Practices

1. **Test in clean environment** (fresh wp-env instance)
2. **Test with real data** (not just test entries)
3. **Test edge cases** (empty fields, long strings, special characters)
4. **Test error conditions** (network failure, database error)
5. **Test across user roles** (admin, editor, subscriber)
6. **Document test results** (what you tested, what worked/failed)
7. **Automate repetitive tests** (use WP-CLI scripts)

## Code Review Checklist

Before committing code:

- [ ] Code follows WordPress Coding Standards
- [ ] All functions have PHPDoc comments
- [ ] Input sanitized with WordPress functions
- [ ] Output escaped with WordPress functions
- [ ] Nonces verified on forms/AJAX
- [ ] Capabilities checked for admin actions
- [ ] Database queries use prepared statements
- [ ] No direct SQL interpolation
- [ ] Translation-ready strings (proper text domain)
- [ ] No hardcoded URLs or paths
- [ ] Follows project naming conventions (super_* prefix)
- [ ] JSHint passes (`npm run jshint`)
- [ ] ESLint passes (when configured)
- [ ] Tested in wp-env environment
- [ ] No errors in debug.log
- [ ] No JavaScript console errors
- [ ] Performance acceptable (Query Monitor)
- [ ] Documentation updated
- [ ] Commit message describes change clearly
