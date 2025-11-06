# WooCommerce Development Methodology Analysis

**Date:** 2025-11-05
**Analyzed:** WooCommerce trunk branch
**Purpose:** Identify best practices to adopt for Super Forms development

---

## Executive Summary

WooCommerce uses a sophisticated, production-proven development methodology that emphasizes:

1. **Modular AI-Optimized Documentation** (CLAUDE.md files)
2. **Incremental Code Quality** (lint only changed files)
3. **Docker-Based Testing** (wp-env for isolated environments)
4. **React-First Admin UI** (TypeScript + Jest + WordPress packages)
5. **Automated Quality Gates** (Husky hooks for validation)

**Key Finding:** They do NOT use a `.claude/` directory. Instead, they use `.cursor/rules/` for Cursor AI IDE configuration and modular `CLAUDE.md` files throughout the codebase for AI assistant documentation.

---

## 1. Documentation Structure

### Modular CLAUDE.md Files

WooCommerce uses **context-specific documentation** rather than a single monolithic file:

```
/plugins/woocommerce/
  ├── CLAUDE.md                    ← PHP backend, high-level testing
  ├── client/admin/CLAUDE.md       ← React/TypeScript development
  ├── client/admin/client/settings-payments/CLAUDE.md ← Module-specific
  └── src/Internal/Admin/Settings/CLAUDE.md           ← Backend patterns

/packages/js/
  └── CLAUDE.md                    ← JavaScript package development

/plugins/woocommerce/tests/php/src/
  └── CLAUDE.md                    ← PHP testing patterns
```

**Benefits:**
- ✅ AI can find relevant info quickly (no scanning 5000 lines)
- ✅ Each doc covers one domain (PHP, React, specific module)
- ✅ Cross-references guide navigation ("See `client/admin/CLAUDE.md` for...")
- ✅ Easy to maintain (update only relevant section)

### Cursor AI Configuration (.cursor/rules/)

Found configuration files (`.mdc` format):

- `avoid-regex.mdc` - Regex best practices
- `code-quality.mdc` - General code quality standards
- `generate-pr-description.mdc` - Automated PR descriptions
- `git.mdc` - Git workflow rules
- `woo-build.mdc` - Build process instructions
- `woo-phpunit.mdc` - PHPUnit testing rules

**Format:**
```markdown
---
description: Description here
globs: **/*.php,**/*.js
alwaysApply: false
---

# Rule Title
Content here...
```

**Recommendation for Super Forms:**
Create `.cursor/rules/` directory with:
- `super-forms-build.mdc` - Build/watch commands
- `super-forms-phpunit.mdc` - Testing practices
- `super-forms-code-quality.mdc` - Standards
- `super-forms-react.mdc` - React development rules

---

## 2. Build Tooling & Scripts

### Package Manager: pnpm

**Why pnpm over npm:**
- Faster installs (hard-linked node_modules)
- Workspace support (monorepo friendly)
- Strict dependency management

**Installation:**
```bash
npm install -g pnpm
```

### Key Build Scripts

```json
{
  "scripts": {
    // Development
    "watch:build": "pnpm --filter=@woocommerce/plugin-woocommerce watch:build",
    "env:start": "wp-env start",
    "env:restart": "wp-env destroy && wp-env start --update",

    // Testing
    "test:php:env": "wp-env run tests-cli vendor/bin/phpunit -c phpunit.xml --verbose",
    "test:js": "pnpm test:js",

    // Linting (CRITICAL: Only changed files)
    "lint:php:changes": "composer run-script lint",
    "lint:changes:branch:js": "bash ./bin/eslint-branch.sh",

    // Building
    "build": "pnpm --if-present build:project",
    "build:zip": "./bin/build-zip.sh"
  }
}
```

**Note:** They use `pnpm workspaces` for monorepo structure.

---

## 3. Incremental Linting (CRITICAL!)

### The Golden Rule

> **"Only lint/fix specific files or changed files - NEVER the entire codebase"**

This appears in EVERY CLAUDE.md file with emphasis.

### Why This Matters

**Bad:** Running `pnpm lint:fix` on entire codebase
- ❌ Changes hundreds of unrelated files
- ❌ Creates massive, unreviewable commits
- ❌ Causes merge conflicts
- ❌ Introduces regressions in stable code
- ❌ AI might "helpfully" reformat working code

**Good:** Linting only changed files
- ✅ Commit only shows your changes
- ✅ Easy code review
- ✅ No accidental reformatting
- ✅ Respects existing code style

### Implementation

**PHP Linting:**
```bash
# Check only changed files
pnpm lint:php:changes

# Fix specific file
pnpm lint:php:fix -- src/includes/class-ajax.php

# ❌ NEVER run without arguments
pnpm lint:php        # NO
pnpm lint:php:fix    # NO
```

**JavaScript Linting:**
```bash
# Use npx eslint directly (not pnpm scripts)
npx eslint --fix src/assets/js/backend/developer-tools.js

# ❌ NEVER use these (lint entire ./client directory)
pnpm run lint               # NO
pnpm run lint:fix           # NO
```

**composer.json scripts:**
```json
{
  "scripts": {
    "lint": "phpcs-changed --git --git-base=master",
    "lint-branch": "phpcs-changed --git --git-base=origin/trunk",
    "lint-staged": "phpcs-changed --git --git-staged"
  }
}
```

---

## 4. wp-env (Docker Testing Environment)

### Configuration (.wp-env.json)

```json
{
  "core": "https://wordpress.org/wordpress-latest.zip",
  "phpVersion": "8.1",
  "plugins": [ "." ],
  "config": {
    "WP_DEBUG_LOG": true,
    "WP_DEBUG_DISPLAY": true,
    "ALTERNATE_WP_CRON": true
  },
  "env": {
    "development": {
      "mysqlPort": 58888
    },
    "tests": {
      "port": 8086,
      "mysqlPort": 58086,
      "plugins": [
        ".",
        "https://downloads.wordpress.org/plugin/akismet.zip"
      ]
    }
  }
}
```

### Usage

```bash
# Start environment
pnpm wp-env start
# → WordPress at http://localhost:8888
# → Admin: admin / password

# Run PHP tests in Docker
pnpm test:php:env

# Stop environment
pnpm wp-env stop

# Destroy and rebuild
pnpm wp-env destroy
pnpm wp-env start --update
```

### Benefits

- ✅ Fresh WordPress install in 30 seconds
- ✅ No conflicts with system PHP/MySQL
- ✅ Test different PHP versions easily
- ✅ Consistent environment across developers
- ✅ Can run multiple environments (dev, test, staging)

### How It Works with Siteground Dev

**WooCommerce Pattern:**
1. **Local (wp-env):** Fast iteration, unit tests, quick debugging
2. **Remote Dev:** Integration testing, server compatibility
3. **Production:** Final deployment

You can use BOTH simultaneously:
- `localhost:8888` - wp-env for rapid development
- `f4d.nl/dev` - Siteground for integration testing

---

## 5. React Admin UI Architecture

### Technology Stack

**Core:**
- React 18.3.x
- TypeScript 5.7.x
- Webpack 5

**WordPress Packages:**
- `@wordpress/components` - Pre-built UI components
- `@wordpress/data` - State management (Redux-like)
- `@wordpress/api-fetch` - REST API client
- `@wordpress/scripts` - Build tooling
- `@wordpress/i18n` - Translations

### Directory Structure

```
/plugins/woocommerce/client/admin/
  ├── client/
  │   ├── settings-payments/        ← Feature module
  │   │   ├── components/
  │   │   │   ├── status-badge/
  │   │   │   │   ├── status-badge.tsx
  │   │   │   │   ├── status-badge.scss
  │   │   │   │   └── test/
  │   │   │   │       └── status-badge.test.tsx
  │   │   ├── hooks/
  │   │   ├── types/
  │   │   └── CLAUDE.md
  │   └── ...
  ├── build/                         ← Output directory
  ├── jest.config.js
  ├── webpack.config.js
  └── CLAUDE.md
```

### Component Pattern

**TypeScript Component:**
```typescript
// status-badge.tsx
import { Badge } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface StatusBadgeProps {
    status: 'active' | 'inactive' | 'pending';
    label?: string;
}

export function StatusBadge({ status, label }: StatusBadgeProps) {
    const badgeColor = {
        active: 'success',
        inactive: 'error',
        pending: 'warning'
    }[status];

    return (
        <Badge color={badgeColor}>
            {label || __('Status', 'woocommerce')}
        </Badge>
    );
}
```

**Jest Test:**
```typescript
// test/status-badge.test.tsx
import { render } from '@testing-library/react';
import { StatusBadge } from '../status-badge';

describe('StatusBadge', () => {
    it('renders active status', () => {
        const { getByText } = render(<StatusBadge status="active" />);
        expect(getByText('Status')).toBeInTheDocument();
    });
});
```

### Running Tests

```bash
cd plugins/woocommerce/client/admin

# Run all tests
pnpm test:js

# Run specific file
pnpm test:js -- status-badge.test.tsx

# Watch mode
pnpm test:js -- --watch

# Coverage
pnpm test:js -- --coverage
```

---

## 6. Pre-Commit Hooks (Husky)

### Configuration

**post-merge hook:**
- Auto-installs dependencies if `pnpm-lock.yaml` changed
- Switches pnpm version automatically via corepack
- Cleans up stale wireit cache (>14 days)

**pre-push hook:**
- Confirms before pushing to `trunk` branch
- Validates syncpack mismatches (dependency version sync)
- **Notable:** They REMOVED linting from pre-push (too much friction)

**Recommendation:**
```bash
# Install husky
npm install --save-dev husky lint-staged

# Setup
npx husky install

# Add pre-commit hook
npx husky add .husky/pre-commit "npx lint-staged"
```

**package.json:**
```json
{
  "lint-staged": {
    "*.php": [
      "php -d display_errors=1 -l",
      "composer run-script lint-staged"
    ],
    "*.js": [
      "eslint --fix"
    ]
  }
}
```

---

## 7. CI/CD Configuration

### GitHub Actions

**Test Matrix:**
```yaml
tests:
  - PHP 8.4 + WordPress latest
  - PHP 8.3 + WordPress latest
  - PHP 8.1 + WordPress 6.4
  - E2E tests (Playwright)
  - JavaScript tests (Jest)
```

**Linting Strategy:**
```bash
# Only lint changed files in PR
lint:changes:branch <baseRef>
```

**Test Sharding:**
```json
{
  "shardingArguments": [
    "--testsuite=wc-phpunit-legacy",
    "--testsuite=wc-phpunit-main"
  ]
}
```

---

## 8. Code Quality Standards

### PHP Coding Standards

**Tool:** PHPCS with WordPress Coding Standards

**Common Issues & Fixes:**

| Issue | Wrong | Correct |
|-------|-------|---------|
| Translators comment | Before return | Before function call |
| File docblock | After `declare()` | Before `declare()` |
| Indentation | Spaces | Tabs only |

**Example:**
```php
// CORRECT
return sprintf(
    /* translators: %s: Gateway name. */
    esc_html__( '%s is not supported.', 'woocommerce' ),
    'Gateway'
);
```

### UI Copy Guidelines

**Always use sentence case**, not title case:

**Correct:**
- "Payment provider options"
- "Complete setup"

**Incorrect:**
- "Payment Provider Options"
- "Complete Setup"

**Exceptions:** Proper nouns, acronyms, brand names

---

## 9. Markdown Documentation Standards

### Linting

**Critical:** All CLAUDE.md files must pass `markdownlint` validation.

```bash
# Auto-fix first (handles most issues)
markdownlint --fix plugins/woocommerce/CLAUDE.md

# Check remaining errors
markdownlint plugins/woocommerce/CLAUDE.md
```

### Character Encoding

**Use UTF-8 box-drawing characters** for directory trees:
- ├── (not spaces or ASCII art)
- │
- └──

**If file becomes corrupted:**
```bash
tr -d '\000-\037' < file.md > file.clean.md && mv file.clean.md file.md
```

---

## 10. AI-Optimized Documentation Guidelines

### Structure for Fast Scanning

1. **Quick Reference at top** - Most common commands
2. **Tables for lookups** - Errors, patterns, commands
3. **Concise sections** - Under 20 lines when possible
4. **Code examples** - Correct vs incorrect
5. **Action-oriented** - "Do this" not "How it works"

### Example Structure

```markdown
# Module Name - Claude Code Documentation

## Quick Reference

\`\`\`bash
# Common commands here
\`\`\`

## Critical Rules
- Rule 1
- Rule 2

## Common Patterns
| Pattern | Code |
|---------|------|

## Known Issues
- Issue + fix in one line

## Related Documentation
- Link to other files
```

---

## 11. Recommendations for Super Forms

### Immediate (This Week)

1. **Extract JavaScript from PHP**
   - Move `/src/includes/admin/views/page-developer-tools.php` JS to separate file
   - Location: `/src/assets/js/backend/developer-tools.js`
   - Enqueue properly with `wp_enqueue_script()`

2. **Add ESLint Configuration**
   ```bash
   npm install --save-dev eslint @wordpress/eslint-plugin
   ```

3. **Create Incremental Linting Scripts**
   ```json
   {
     "scripts": {
       "lint:js:changed": "eslint $(git diff --name-only --diff-filter=ACMRTUXB HEAD | grep -E '\\.(js|jsx)$')",
       "lint:js:fix": "eslint --fix"
     }
   }
   ```

4. **Fix Line 5563 Syntax Error**
   - Locate the actual error in rendered HTML
   - Fix the PHP code generating invalid JavaScript

### Short-term (Next 2 Weeks)

1. **Set up wp-env**
   ```bash
   npm install @wordpress/env --save-dev
   ```

   Create `.wp-env.json`:
   ```json
   {
     "core": "WordPress/WordPress#6.8",
     "phpVersion": "8.0",
     "plugins": ["."],
     "config": {
       "DEBUG_SF": true,
       "WP_DEBUG": true
     }
   }
   ```

2. **Create Modular CLAUDE.md Files**
   ```
   /CLAUDE.md                        ← Overview, link to others
   /docs/CLAUDE.development.md       ← Build, testing, wp-env
   /docs/CLAUDE.javascript.md        ← React/JS standards
   /docs/CLAUDE.php.md               ← PHP patterns, WPCS
   /docs/CLAUDE.testing.md           ← PHPUnit, Jest
   ```

3. **Add Pre-Commit Hooks**
   ```bash
   npm install --save-dev husky lint-staged
   npx husky install
   ```

### Medium-term (1-2 Months)

1. **Convert Developer Tools to React**
   - Use `@wordpress/scripts` for build
   - Component-based architecture
   - Jest tests for components
   - REST API instead of AJAX actions

2. **TypeScript for New Features**
   - Catch errors at compile-time
   - Better IDE support
   - Self-documenting code

3. **Add PHPUnit Tests**
   - Test data access layer
   - Test migration logic
   - Integration tests via wp-env

### Long-term (3-6 Months)

1. **Monorepo Structure** (if plugin grows significantly)
   - `/packages/js/` - Shared JavaScript
   - `/packages/php/` - Shared PHP libraries
   - `/plugins/super-forms/` - Main plugin
   - `/plugins/super-forms-pro/` - Premium features

2. **CI/CD Pipeline**
   - GitHub Actions for automated testing
   - Automated deployment to staging
   - Release automation

3. **E2E Testing** (Playwright or Cypress)
   - Test full user workflows
   - Catch integration issues early

---

## 12. Key Takeaways

### What WooCommerce Does Well

1. **Modular Documentation** - Easy to find relevant info
2. **Incremental Linting** - Prevents mass reformatting disasters
3. **Docker Testing** - Consistent, isolated environments
4. **React + TypeScript** - Modern, maintainable admin UI
5. **Automated Quality Gates** - Catch issues before push

### What to Avoid

1. **Mass Linting** - Never lint entire codebase
2. **Monolithic Docs** - Split by domain/technology
3. **Inline JavaScript** - Always separate files
4. **Manual Testing Only** - Automate with wp-env

### Cultural Principles

1. **Developer Experience First** - Fast feedback loops
2. **Respect Existing Code** - Don't reformat working code
3. **Test Before Merge** - wp-env makes this easy
4. **Document for AI** - Optimize for scanning, not reading
5. **Incremental Improvements** - Don't boil the ocean

---

## 13. Resources

**WooCommerce Repository:**
- https://github.com/woocommerce/woocommerce

**WordPress Tools:**
- `@wordpress/env` - https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/
- `@wordpress/scripts` - https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/

**Testing:**
- PHPUnit - https://phpunit.de/
- Jest - https://jestjs.io/
- React Testing Library - https://testing-library.com/react

**Linting:**
- PHPCS - https://github.com/squizlabs/PHP_CodeSniffer
- WordPress Coding Standards - https://github.com/WordPress/WordPress-Coding-Standards
- ESLint - https://eslint.org/
- markdownlint - https://github.com/DavidAnson/markdownlint

---

**Document Version:** 1.0
**Last Updated:** 2025-11-05
**Maintained By:** AI Analysis of WooCommerce trunk branch
