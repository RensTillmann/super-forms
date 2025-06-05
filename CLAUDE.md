# Super Forms - Project Configuration

## Task Management Guidelines

When I give you complex requests:
- Break down the task into smaller, focused steps before starting
- Ask me to confirm your approach before proceeding
- Work on one component at a time
- Use /compact between major sections to manage context

## When I ask you to "improve code"

Instead of scanning the entire codebase, ask me to specify:
- Which specific function or file section needs improvement
- What type of improvement I'm looking for (performance, readability, etc.)
- The specific issue I want addressed

## Summary instructions

When you are using compact, please focus on code changes

## Project Overview
Super Forms is a WordPress drag & drop form builder plugin with various add-ons and extensions.

## Development Commands

### Build & Production
- `npm run prod` - Full production build with all optimizations
- `npm run sass` - Compile SASS files
- `npm run zip` - Create distribution zip files

### Code Quality
- `npm run jshint` - Run JSHint on source files

### Cleanup
- `npm run delswaps` - Remove swap files and sass cache
- `npm run rmrf` - Clean dist folder and docs

## Project Structure
- `/src/` - Source files
  - `/add-ons/` - Plugin add-ons (calculator, csv, email reminders, etc.)
  - `/assets/` - CSS, JS, images, fonts
  - `/includes/` - Core PHP classes and extensions
  - `/i18n/` - Internationalization files
- `/dist/` - Production build output
- `/docs/` - Documentation

## Key Files
- `super-forms.php` - Main plugin file
- `src/includes/class-*.php` - Core functionality classes
- `src/assets/js/frontend/*.js` - JavaScript files related to the front-end (rendering form)
- `src/assets/js/backend/*.js` - JavaScript files related to the backend (in wordpress dashboard/admin side)
- `src/assets/js/backend/create-form.js` - Form builder (create form) JavaScript
- `src/assets/css/frontend/create-form.css` - CSS styles related to builder page in back-end
- `src/assets/css/backend/*.css` - CSS styles related to back-end UI
- `src/assets/css/frontend/*.css` - CSS styles related to front-end UI
- `src/assets/css/frontend/elements.sass` - Main frontend styles (compiled to CSS)

## Current Development Focus
Based on recent commits:
- Working on colorpicker field types for tabs and email templates
- Implementing dynamic group path handling
- Improving conditional logic and filter systems

## Testing & Validation
- Use `npm run jshint` for JavaScript linting
- No automated test suite currently configured

## WordPress Plugin Development Context

### Environment
- WordPress plugin requiring PHP 7.4+ and WordPress 5.8+
- Uses jQuery (but prefers vanilla JavaScript for frontend interactions and WordPress REST API
- Includes various third-party integrations (PayPal, Mailchimp, WooCommerce, etc.)

### Coding Standards
- Follow WordPress Coding Standards for PHP
- Use consistent indentation (4 spaces for PHP, 4 spaces for JS)
- Prefix all functions, classes objects with `super`, `SUPER`, or `sfui` to avoid conflicts 
- Use WordPress hooks and filters system for extensibility

### Common Patterns in This Codebase
- Form elements are defined in `/src/includes/shortcodes/`
- AJAX handlers are in `/src/includes/class-ajax.php`
- Frontend form rendering uses shortcode system
- Backend form builder uses drag-and-drop with jQuery UI

## Debugging Guidelines

No debugging guidelines defined try to use your best approach

## Performance Considerations

- Always try to look for the best optimized method
- Minimize database queries when possible and optimize them if possible
- Use WordPress transients for caching when appropriate
- Only load assets on pages that actually require them or Lazy load assets only when needed

## Security Best Practices

- Always sanitize user input using WordPress functions
- Use nonces for all AJAX requests
- Escape output with appropriate WordPress functions
- Validate capabilities before allowing admin actions

## Git Workflow

- Main branch: `master`
- Make atomic commits with clear messages
- Test changes locally before committing
- Run `npm run jshint` before committing JS changes

## Common Tasks Reference

### Adding a new form element:
1. Define element in `/src/includes/shortcodes/form-elements.php`
2. Add frontend JS in `/src/assets/js/frontend/elements.js`
3. Add backend JS in `/src/assets/js/backend/create-form.js`
4. Style in `/src/assets/css/frontend/elements.sass`

### Working with add-ons:
- Each add-on is self-contained in `/src/add-ons/[addon-name]/`
- Follow the existing add-on structure when creating new ones

### Working with build in extensions:
- Super Forms comes with build-in extensions such as Listings, Stripe, PDF generator
- These are located inside `/src/includes/extensions` and mostly require paymnet either via yearly manual payment or subscription via our third party custom build license system under Super Forms > Licenses
