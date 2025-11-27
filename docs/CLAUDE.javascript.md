# JavaScript & React Development Guide

## React Components (Email Builder v2)

When developing React components in `/src/react/emails-v2/`:

### 1. Development Mode (REQUIRED for all development work)

```bash
cd /projects/super-forms/src/react/emails-v2
npm run watch
```

This runs webpack in development mode with:
- **Unminified code** for debugging
- **React DevTools support** (Components & Profiler tabs)
- **Source maps** for easier debugging
- **Automatic recompilation** when files change

**⚠️ ALWAYS use `npm run watch` during development - NEVER use `npm run build`**

### 2. Production Mode (ONLY for final releases)

```bash
cd /projects/super-forms/src/react/emails-v2
npm run build
```

**⚠️ WARNING**: Use ONLY for final production releases.
- No debugging capabilities
- Minified code makes troubleshooting impossible
- Component names are obfuscated
- Should NEVER be used during development or testing

### 3. Development Debugging Setup

- **Install React DevTools** browser extension
- **Open DevTools** (F12) → look for **Components** and **Profiler** tabs
- **Development build required** - component names only visible in dev mode
- **Hard refresh** after switching modes: Ctrl+F5 (Windows/Linux) or Cmd+Shift+R (Mac)

### 4. React Component Debugging

- **Components tab**: View component tree, props, and state in real-time
- **Console logs**: Added throughout components for debugging
- **State changes**: Watch state updates live in React DevTools
- **Event handlers**: Debug onClick, onChange events through Components tab

### 5. Compiled Output

React component changes compile to:
- `/src/assets/js/backend/emails-v2.js` (5.7MB dev vs 424KB prod)
- `/src/assets/css/backend/emails-v2.css`

**⚠️ Important**: Always use `npm run watch` (development mode) when debugging React components. Production builds remove all debugging capabilities.

### 6. Email v2 ↔ Triggers Backend Integration

**Data Flow (since 6.5.0):**
The Email v2 React app stores email data in `_emails` postmeta, which automatically syncs to the triggers system via `SUPER_Email_Trigger_Migration`:

- **Save**: React app saves to `_emails` → `save_form_emails_settings()` → `sync_emails_to_triggers()` → triggers table
- **Load**: React app loads from `_emails` ← `get_form_emails_settings()` ← `get_emails_for_ui()` ← triggers table (if `_emails` empty)

**Key Points:**
- Email v2 UI is unaware of triggers system (facade pattern)
- Each email becomes a `send_email` action on `form.submitted` event
- Sync maintains `_super_email_triggers` postmeta mapping (email_id → trigger_id)
- Changes in Email v2 UI automatically update trigger configurations
- Migrated legacy emails appear in Email v2 tab via reverse sync

**Implementation Files:**
- Backend sync: `/src/includes/class-email-trigger-migration.php`
- Integration hooks: `/src/includes/class-common.php` lines 121-156
- React app storage: Stores in `_emails` postmeta (sync transparent to React code)

## Vanilla JavaScript Components (Frontend)

### Session Manager (Progressive Form Saving)

**Location:** `/src/assets/js/frontend/session-manager.js`

**Architecture:**
- **Zero Dependencies**: Pure vanilla JavaScript (no jQuery, no libraries)
- **Modern APIs**: Uses `fetch()`, `AbortController`, `crypto.randomUUID()`, localStorage
- **Diff-Tracking**: Sends only changed fields to server (bandwidth efficient)
- **Event-Driven**: Custom events for integration (`super:session:restored`, `super:form:submitted`)

**Key Features:**
1. **Automatic Session Creation**: First field focus triggers session creation
2. **Debounced Auto-Save**: 500ms debounce on blur/change events
3. **Request Cancellation**: AbortController cancels in-flight requests when user types fast
4. **Session Recovery**: Shows recovery banner on form load if unsaved data exists
5. **Client Token**: UUID v4 stored in localStorage for anonymous session identification

**Performance Patterns:**
```javascript
// Diff-only updates (bandwidth efficient)
var changes = {};
for (key in currentData) {
    if (state.lastSavedData[key] !== currentData[key]) {
        changes[key] = currentData[key];
    }
}

// AbortController pattern (prevent race conditions)
if (state.abortController) {
    state.abortController.abort(); // Cancel previous
}
state.abortController = new AbortController();
fetch(url, { signal: state.abortController.signal });
```

**Integration Points:**
- Enqueued in: `/src/super-forms.php` (lines 2266-2276)
- AJAX handlers: `/src/includes/class-ajax.php` (lines 8176-8475)
- Dependencies: None (runs standalone)
- Global object: `window.SUPER_SessionManager`

**Browser Compatibility:**
- Modern browsers: Uses `crypto.randomUUID()`
- Legacy fallback: Custom UUID generator for older browsers
- IE11: Not supported (uses `fetch`, `AbortController`, arrow functions)

**Development Notes:**
- No build process required (direct source file)
- Browser console shows debug logs: `[Super Forms] Session save failed:`
- Custom events fire for lifecycle hooks
- Graceful degradation if AJAX fails (form still works)

## UI Component Guidelines

### Icons - CRITICAL RULES

- **ONLY use Lucide React icons** for ALL UI components - NO EXCEPTIONS
- **NEVER use emoji icons (❌ 📧 🔔 📅 etc.)** - ALWAYS replace with Lucide icons
- **NEVER use custom SVG icons** - always find appropriate Lucide icon
- Import from `lucide-react`: `import { IconName } from 'lucide-react'`
- Standard icon sizing: `className="ev2-w-4 ev2-h-4"` for buttons, `ev2-w-5 ev2-h-5` for larger elements
- Examples: `<Mail />`, `<Bell />`, `<Calendar />`, `<Settings />`, etc.
- Documentation, examples, and help text MUST use Lucide icons, NOT emojis
- If tempted to use an emoji, STOP and find the appropriate Lucide icon instead

### UI Consistency

- Use Tailwind CSS with `ev2-` prefix for all styling
- Follow existing component patterns and naming conventions
- Maintain consistent spacing, colors, and interaction patterns
- Always use Lucide icons for visual elements in the UI - no emoji icons anywhere

## Automated Code Quality Hooks

### React/JavaScript File Changes - MANDATORY VALIDATION

After ANY change to React/JavaScript files (`.js`, `.jsx`, `.ts`, `.tsx`):

#### HOOK 1: Build Validation (MANDATORY)

```bash
cd /projects/super-forms/src/react/emails-v2 && npm run build
```
- **FAIL** → Fix syntax errors immediately, re-run until pass
- **PASS** → Continue with changes

#### HOOK 2: Development Mode Validation (MANDATORY)

```bash
cd /projects/super-forms/src/react/emails-v2 && npm run watch &
```
- Always run in development mode for debugging
- Check browser console for errors
- Verify functionality works as expected

#### HOOK 3: Syntax Pre-Check (RECOMMENDED)

Before making complex changes, run:
```bash
cd /projects/super-forms/src/react/emails-v2 && npx eslint src/ --ext .js,.jsx
```

## ESLint Configuration

### Incremental Linting Pattern (WooCommerce Pattern)

Following WooCommerce's approach, we implement **incremental linting** to prevent mass code changes:

**Benefits:**
- Only lint changed files (prevents 10,000+ line suggestions)
- Pre-commit hooks catch errors before deployment
- Gradual improvement without blocking development
- AI cannot break the entire codebase with lint "fixes"

**Implementation:**
1. `.eslintrc.json` - ESLint configuration
2. `.eslintignore` - Ignore legacy files (lint on edit only)
3. `package.json` scripts - Lint commands
4. Pre-commit hooks with `lint-staged` - Auto-lint changed files

### ESLint Configuration File

Location: `.eslintrc.json` (to be created)

```json
{
  "env": {
    "browser": true,
    "es6": true,
    "jquery": true
  },
  "extends": ["eslint:recommended"],
  "parserOptions": {
    "ecmaVersion": 2020,
    "sourceType": "module"
  },
  "rules": {
    "no-console": "off",
    "no-unused-vars": "warn",
    "no-undef": "error",
    "semi": ["error", "always"],
    "quotes": ["warn", "single"],
    "indent": ["warn", 4],
    "brace-style": ["warn", "1tbs"],
    "comma-dangle": ["warn", "never"],
    "no-trailing-spaces": "warn"
  },
  "globals": {
    "wp": "readonly",
    "jQuery": "readonly",
    "ajaxurl": "readonly",
    "SUPER": "readonly"
  }
}
```

### Package.json Scripts

```json
{
  "scripts": {
    "lint": "eslint src/assets/js/**/*.js --max-warnings=0",
    "lint:fix": "eslint src/assets/js/**/*.js --fix",
    "lint:staged": "lint-staged"
  }
}
```

### Pre-Commit Hooks with Husky & lint-staged

**Installation:**
```bash
npm install --save-dev husky lint-staged
npx husky install
```

**Configuration:**

`.husky/pre-commit`:
```bash
#!/bin/sh
. "$(dirname "$0")/_/husky.sh"

npm run lint:staged
```

`package.json`:
```json
{
  "lint-staged": {
    "src/assets/js/**/*.js": [
      "eslint --fix",
      "git add"
    ]
  }
}
```

## Auto-Fix Protocol for Common Errors

### JavaScript/React Syntax Errors

1. **Missing commas in object spreads** - Always check `...condition && { }` syntax
2. **Unclosed parentheses/braces** - Count opening/closing brackets
3. **Import statement errors** - Verify all imports exist and are spelled correctly
4. **JSX syntax errors** - Ensure proper JSX attribute syntax

### Zustand Store Errors

- Use `createWithEqualityFn` from `zustand/traditional` instead of deprecated `create`

### Common JavaScript Patterns to Avoid

**❌ BAD - Nested <p> tags:**
```html
<p>
    <button>Click</button>
    <p>Description</p>  <!-- Invalid! -->
</p>
```

**✅ GOOD - Use <div> for containers:**
```html
<div>
    <button>Click</button>
    <p>Description</p>
</div>
```

**❌ BAD - Duplicate closing braces:**
```javascript
function example() {
    if (condition) {
        doSomething();
    }
    }  // Extra brace!
}
```

**✅ GOOD - Proper brace matching:**
```javascript
function example() {
    if (condition) {
        doSomething();
    }
}
```

## Error Recovery Workflow

When build fails:
1. **READ THE ERROR** - Don't guess, read the exact line number and error
2. **FIX IMMEDIATELY** - Don't continue with other changes
3. **RE-RUN BUILD** - Verify fix works
4. **COMMIT WORKING CODE** - Only commit when build passes

## Pre-Edit Checklist

Before editing complex files:
- [ ] Know the exact line numbers to change
- [ ] Understand the surrounding syntax context
- [ ] Have a plan for testing the change
- [ ] Build is currently passing

## Extract Inline JavaScript Pattern

### Problem: Inline JavaScript in PHP Files

Large PHP files like `page-developer-tools.php` contain thousands of lines of inline JavaScript that cannot be linted or validated before deployment.

### Solution: Extract to Separate Files

**Benefits:**
1. ESLint can validate syntax before deployment
2. Pre-commit hooks catch errors automatically
3. Easier to maintain and debug
4. Can use modern JavaScript modules
5. Browser caching improves performance

**Process:**

1. **Extract JavaScript to separate file:**
   - Create `/src/assets/js/backend/developer-tools.js`
   - Move all `<script>` content from PHP file
   - Convert to proper JavaScript file with strict mode

2. **Update PHP to enqueue script:**
   ```php
   wp_enqueue_script(
       'super-forms-developer-tools',
       plugins_url('/assets/js/backend/developer-tools.js', __FILE__),
       array('jquery'),
       SUPER_VERSION,
       true
   );

   // Pass PHP variables to JavaScript
   wp_localize_script('super-forms-developer-tools', 'devtoolsData', array(
       'ajaxurl' => admin_url('admin-ajax.php'),
       'nonce' => wp_create_nonce('super-form-builder'),
       'migration' => $migration_state
   ));
   ```

3. **Update JavaScript to use localized data:**
   ```javascript
   jQuery(document).ready(function($) {
       const ajaxurl = devtoolsData.ajaxurl;
       const nonce = devtoolsData.nonce;
       const migration = devtoolsData.migration;

       // Rest of JavaScript code...
   });
   ```

## WooCommerce Best Practices

Based on analysis of WooCommerce's codebase:

### Use pnpm Instead of npm

WooCommerce uses pnpm for faster, more efficient package management:
```bash
# Install pnpm globally
npm install -g pnpm

# Use pnpm for all package operations
pnpm install
pnpm add --save-dev package-name
```

### Modular Documentation

- Keep documentation close to code
- Use `.cursor/rules/` for IDE-specific guidance
- Split large docs into domain-specific files

### Testing Philosophy

- Unit tests for business logic
- Integration tests for workflows
- E2E tests for critical paths
- Test React components with Jest

## Debugging Guidelines

### Browser Console

Always check browser console for errors:
```javascript
// Add debug logging
console.log('[SF Debug]', 'Variable:', variable);
console.error('[SF Error]', 'Failed:', error);
console.warn('[SF Warning]', 'Deprecated:', method);
```

### React DevTools

1. Install React DevTools browser extension
2. Open browser DevTools (F12)
3. Navigate to "Components" tab
4. Inspect component props, state, and hooks
5. Use "Profiler" tab for performance analysis

### Network Tab

Monitor AJAX requests:
1. Open DevTools → Network tab
2. Filter by "XHR" or "Fetch"
3. Check request payload and response
4. Verify status codes (200, 400, 500)
5. Check response data structure

## Performance Considerations

### Lazy Loading

- Only load assets on pages that require them
- Use conditional `wp_enqueue_script()` based on current page
- Load heavy libraries only when needed

### Debouncing

```javascript
// Debounce search inputs
let searchTimeout;
$('#search-input').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch($(this).val());
    }, 300);
});
```

### Optimize jQuery Selectors

```javascript
// ❌ BAD - Multiple DOM queries
$('.button').on('click', function() {
    $('.result').text('Loading...');
    $('.result').show();
});

// ✅ GOOD - Cache selector
const $result = $('.result');
$('.button').on('click', function() {
    $result.text('Loading...').show();
});
```

## Security Best Practices

### Nonce Verification

Always include nonces in AJAX requests:
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'super_action',
        security: devtoolsData.nonce,  // Always include nonce
        entry_id: entryId
    },
    success: function(response) {
        // Handle response
    }
});
```

### Escape Output

```javascript
// ❌ BAD - Direct HTML injection
$('#result').html(userInput);

// ✅ GOOD - Escape with text() or sanitize
$('#result').text(userInput);
// OR use DOMPurify for HTML content
$('#result').html(DOMPurify.sanitize(userInput));
```

### Validate Input

```javascript
// Always validate user input
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

if (!validateEmail(userEmail)) {
    alert('Invalid email address');
    return false;
}
```

## Extension JavaScript Patterns

### Backend Settings Management

When saving extension settings in WordPress admin, ensure JavaScript output matches PHP expectations.

**Critical Rules:**
1. **Respect field grouping** - If PHP defines `group_name`, save in that group
2. **Use arrays not objects** - For repeatable items, use `[]` not `{}`
3. **Match PHP structure** - JavaScript output must match what PHP expects to read

**Example: Listings Extension Backend Script**

```javascript
// ✅ GOOD - Save in groups matching PHP definition
data.formSettings._listings = {lists: []};

for (var key = 0; key < list.length; key++) {
    var listItem = {};

    // Generate/preserve unique ID
    var idInput = list[key].querySelector('input[name="id"]');
    listItem.id = idInput ? idInput.value : '';

    // Group fields as defined in PHP
    listItem.display = {
        retrieve: list[key].querySelector('[data-name="retrieve"]').querySelector('.super-active').dataset.value,
        form_ids: list[key].querySelector('input[name="form_ids"]').value
    };

    // Save repeatable items as arrays
    listItem.custom_columns = {
        columns: []  // Array, not object
    };

    var columns = list[key].querySelectorAll('.super-listings-list div[data-name="custom_columns"] li');
    for (var ckey = 0; ckey < columns.length; ckey++) {
        listItem.custom_columns.columns.push({
            name: columns[ckey].querySelector('input[name="name"]').value,
            field_name: columns[ckey].querySelector('input[name="field_name"]').value
        });
    }

    data.formSettings._listings.lists.push(listItem);
}
```

**❌ BAD - Common Mistakes:**

```javascript
// WRONG: Using object with numeric keys instead of array
data.formSettings._listings = {};
for (var key = 0; key < list.length; key++) {
    data.formSettings._listings[key] = {};  // Creates {"0": {}, "1": {}}
}

// WRONG: Saving at top level when PHP expects grouped
listItem.retrieve = value;  // Should be listItem.display.retrieve

// WRONG: Object for repeatable items
listItem.custom_columns = {
    columns: {}  // Should be []
};
for (var i = 0; i < items.length; i++) {
    listItem.custom_columns.columns[i] = item;  // Creates {"0": {}, "1": {}}
}
```

**Why This Matters:**

If JavaScript saves in different structure than PHP expects:
- Migration required for backward compatibility
- Fields appear empty in admin UI after save
- Frontend fails to read settings correctly
- Data loss when users re-save settings

**Reference:** Listings extension (v6.4.127) - see `/home/rens/super-forms/src/includes/extensions/listings/assets/js/backend/script.js` lines 95-172

### Data Structure Consistency Checklist

Before implementing extension settings JavaScript:

- [ ] Review PHP field definitions for `group_name` attributes
- [ ] Check if fields are in groups (look for `group_name` in PHP)
- [ ] Verify repeatable items use arrays `[]` not objects `{}`
- [ ] Test that saved data matches PHP structure exactly
- [ ] Add inline comments explaining backward compatibility context
- [ ] Verify admin UI loads saved settings correctly

**Testing Pattern:**

```javascript
// 1. Save settings in admin
console.log('Saving:', JSON.stringify(data.formSettings._listings, null, 2));

// 2. Reload page and check browser console
// 3. Verify structure matches what JavaScript saved
// 4. Check no fields appear empty that should have values
```
