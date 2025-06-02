# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Super Forms is a WordPress drag & drop form builder plugin (version 6.4.102) that provides advanced form creation capabilities with extensive add-on support. The plugin follows WordPress coding standards and uses a modular architecture.

## Build Commands

### Development Commands
- `npm run sass` - Compile SASS to CSS for frontend elements
- `npm run jshint` - Run JSHint linting on source files
- `npm run delswaps` - Clean up swap files and cache directories

### Production Build
- `npm run prod` - Full production build including compilation, file copying, and zipping
- `npm run zip` - Create distribution ZIP files (runs bash zip.sh)
- `npm run copyfiles` - Copy source files to distribution directory
- `npm run delsass` - Remove SASS and source map files from distribution

### Development Workflow
This project uses a custom build system defined in package.json scripts rather than modern bundlers. The build process:
1. Compiles SASS to CSS
2. Copies files from `src/` to `dist/super-forms/`
3. Removes development files
4. Creates plugin ZIP files for distribution

## Core Architecture

### Main Plugin Structure
- **Entry Point**: `src/super-forms.php` - Main plugin file containing the `SUPER_Forms` class
- **Core Classes**: Located in `src/includes/` directory with class-based architecture using `SUPER_` prefix
- **Shortcodes System**: Form elements and HTML elements are rendered via shortcode system in `src/includes/shortcodes/`
- **Add-ons**: Modular extensions located in `src/add-ons/` directory, each with independent functionality

### Key Core Classes
- `SUPER_Forms` - Main plugin class, handles initialization and global settings
- `SUPER_Common` - Utility functions and common operations
- `SUPER_Shortcodes` - Handles form rendering and shortcode processing
- `SUPER_Field_Types` - Manages different form field types and their rendering
- `SUPER_Ajax` - Handles all AJAX requests and form submissions
- `SUPER_Post_Types` - Registers custom post types (forms, entries)
- `SUPER_Settings` - Manages plugin settings and configuration
- `SUPER_Pages` - Admin page rendering and management

### Form System Architecture
- Forms are stored as custom post types with JSON-encoded form structure
- Form elements are defined in `src/includes/shortcodes/form-elements.php` 
- HTML elements for layout in `src/includes/shortcodes/html-elements.php`
- Layout elements for columns/structure in `src/includes/shortcodes/layout-elements.php`
- Field data retrieved via `predefined-arrays.php` for dropdowns and options

### Add-on System
The plugin uses a modular add-on architecture where each add-on in `src/add-ons/` provides:
- Independent PHP file with hooks into core system
- Optional assets (CSS/JS) in dedicated directories
- Separate changelog and uninstall files
- Examples: Calculator, PayPal payments, Signature capture, WooCommerce integration

### Asset Management
- Frontend assets: `src/assets/css/frontend/` and `src/assets/js/frontend/`
- Backend admin assets: `src/assets/css/backend/` and `src/assets/js/backend/`
- Assets are enqueued via WordPress hooks in core classes
- Third-party libraries in `src/lib/` directory (TinyMCE, Google APIs)
- Main CSS is compiled from SASS: `src/assets/css/frontend/elements.sass`

### Extensions vs Add-ons
- **Extensions** (`src/includes/extensions/`): Built-in advanced features (PDF generator, Stripe, Listings)
- **Add-ons** (`src/add-ons/`): Optional modular features that can be independently activated

### Internationalization
- Translation files in `src/i18n/languages/` with extensive language support
- Uses WordPress i18n system with 'super-forms' text domain

## Development Notes

- Plugin follows WordPress coding standards and hooks system
- Uses npm build system for SASS compilation and distribution packaging
- Uses WordPress native JavaScript libraries and jQuery
- Form builder uses drag-and-drop interface with JSON storage
- AJAX-heavy interface for form creation and submission
- Extensive use of WordPress filters and actions for extensibility
- Development files are in `src/` directory, distribution builds go to `dist/`

## File Organization

The codebase separates concerns clearly:
- Core functionality in `src/includes/`
- User interface assets in `src/assets/`
- Documentation in `src/docs/` and separate docs site
- Extensions for advanced features
- Add-ons for optional functionality
- Internationalization support