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

## Development Workflow

### React Components (Email Builder v2)
When developing React components in `/src/react/emails-v2/`:

1. **Start webpack watch mode** (in a separate terminal):
   ```bash
   cd /projects/super-forms/src/react/emails-v2
   npm run watch
   ```
   This automatically recompiles React components when you save changes.

2. **Clear browser cache** after changes:
   - Hard refresh: Ctrl+F5 (Windows/Linux) or Cmd+Shift+R (Mac)
   - Or enable "Disable cache" in browser DevTools

3. **Important**: React component changes need to be compiled to:
   - `/src/assets/js/backend/emails-v2.js`
   - `/src/assets/css/backend/emails-v2.css`

### Remote Server Sync
- ⚠️ Stop syncing to webserver unless told otherwise

## Memory: Tab Settings Grouping
- Tab settings are sometimes grouped with attributes `data-g` or `data-r` for repeater elements

## WordPress Plugin Development Protocol

### Core WordPress Development Principles
- **WordPress Standards First**: Always follow WordPress Coding Standards (WPCS)
- **Security by Design**: Implement sanitization, nonces, and capability checks
- **Performance Minded**: Optimize queries, use caching, conditional loading
- **Accessibility Required**: Ensure WCAG 2.1 AA compliance
- **Translation Ready**: All strings must be translatable with proper text domain

### WordPress-Specific Testing Requirements
- **Frontend Validation**: Always test forms render correctly on frontend
- **Admin Functionality**: Verify all admin features work without errors
- **Database Operations**: Confirm all database queries execute properly
- **JavaScript Console**: Check for JS errors in browser console
- **Plugin Lifecycle**: Test activation, deactivation, and uninstall processes
- **Security Verification**: Confirm nonces, sanitization, and capability checks
- **Performance Impact**: Monitor query count and page load times

### WordPress Development Stack Requirements
- **PHPCS**: Run WordPress Coding Standards validation
- **Plugin Check**: Use WordPress.org Plugin Check tool
- **Security Scanning**: Regular WPScan vulnerability checks
- **Database Optimization**: Monitor Query Monitor for performance
- **Accessibility Testing**: Use accessibility validation tools

### WordPress-Specific File Change Protocol
When editing WordPress plugin files, ALWAYS:
1. Follow WordPress naming conventions (prefix with `super_forms_`)
2. Use WordPress functions instead of native PHP when available
3. Implement proper error handling with WP_Error
4. Add inline documentation with PHPDoc standards
5. Test both frontend and admin functionality

### Why We Ship Broken Code (And How to Stop)

Every AI assistant has done this: Made a change, thought "that looks right," told the user it's fixed, and then... it wasn't. The user comes back frustrated. We apologize. We try again. We waste everyone's time.

This happens because we're optimizing for speed over correctness. We see the code, understand the logic, and our pattern-matching says "this should work." But "should work" and "does work" are different universes.

#### The Protocol: Before You Say "Fixed"

**1. The 30-Second Reality Check**
Can you answer ALL of these with "yes"?

□ Did I run/build the code?
□ Did I trigger the exact feature I changed?
□ Did I see the expected result with my own observation (including in the front-end GUI)?
□ Did I check for error messages (console/logs/terminal)?
□ Would I bet $100 of my own money this works?

**2. Common Lies We Tell Ourselves**
- "The logic is correct, so it must work" → **Logic ≠ Working Code**
- "I fixed the obvious issue" → **The bug is never what you think**
- "It's a simple change" → **Simple changes cause complex failures**
- "The pattern matches working code" → **Context matters**

**3. The Embarrassment Test**
Before claiming something is fixed, ask yourself:
> "If the user screen-records themselves trying this feature and it fails,
> will I feel embarrassed when I see the video?"

If yes, you haven't tested enough.

#### Red Flags in Your Own Responses

If you catch yourself writing these phrases, STOP and actually test:
- "This should work now"
- "I've fixed the issue" (for the 2nd+ time)
- "Try it now" (without having tried it yourself)
- "The logic is correct so..."
- "I've made the necessary changes"

#### The Minimum Viable Test

For any change, no matter how small:

1. **UI Changes**: Actually click the button/link/form
2. **API Changes**: Make the actual API call with curl/PostMan
3. **Data Changes**: Query the database to verify the state
4. **Logic Changes**: Run the specific scenario that uses that logic
5. **Config Changes**: Restart the service and verify it loads

#### WordPress-Specific Testing Requirements

1. **Form Changes**: Load the form on frontend and test submission
2. **Admin Changes**: Access the admin area and verify functionality
3. **Database Changes**: Check WordPress database tables directly
4. **JavaScript Changes**: Open browser console and test interactions
5. **Plugin Changes**: Test activation, deactivation, and functionality

#### The Professional Pride Principle

Every time you claim something is fixed without testing, you're saying:
- "I value my time more than yours"
- "I'm okay with you discovering my mistakes"
- "I don't take pride in my craft"

That's not who we want to be.

#### Make It a Ritual

Before typing "fixed" or "should work now":
1. Pause
2. Run the actual test
3. See the actual result
4. Only then respond

**Time saved by skipping tests: 30 seconds**
**Time wasted when it doesn't work: 30 minutes**
**User trust lost: Immeasurable**

#### Bottom Line

The user isn't paying you to write code. They're paying you to solve problems. Untested code isn't a solution—it's a guess.

**Test your work. Every time. No exceptions.**

---
*Remember: The user describing a bug for the third time isn't thinking "wow, this AI is really trying." They're thinking "why am I wasting my time with this incompetent tool?"*

### WordPress Plugin Guidelines Reference

For comprehensive WordPress plugin development guidelines, security best practices, and automated validation processes, refer to `wp-plugin-guidelines.md` in this project.

**Key Guidelines to Remember:**
- Always sanitize user input with WordPress functions
- Use nonces for all form submissions and AJAX requests
- Escape all output with appropriate WordPress functions
- Check user capabilities before allowing admin actions
- Follow WordPress naming conventions for all code elements
- Use WordPress APIs instead of native PHP functions
- Implement proper error handling with WP_Error
- Test both frontend and admin functionality thoroughly