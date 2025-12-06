---
name: 01-database-schema-dal
parent: m-implement-styles-themes-ui
status: pending
created: 2025-12-06
---

# Subtask 1: Database Schema & DAL

## Goal

Create the database table `wp_superforms_themes` and the `SUPER_Theme_DAL` class for theme CRUD operations. Seed system themes (Light, Dark) and stub themes on plugin activation.

## Success Criteria

- [ ] `wp_superforms_themes` table created in `class-install.php`
- [ ] Table includes: `id`, `name`, `slug`, `description`, `category`, `styles`, `preview_colors`, `is_system`, `is_stub`, `user_id`, `created_at`, `updated_at`
- [ ] `SUPER_Theme_DAL` class with CRUD methods
- [ ] System themes (Light, Dark) seeded on activation
- [ ] Stub themes seeded with `is_stub=1` flag
- [ ] All methods return `WP_Error` on failure

## Technical Specification

### Database Table Schema

Add to `class-install.php` in `create_tables()` method (after automations tables):

```php
// ─────────────────────────────────────────────────────────
// Themes Table
// @since 6.6.0
// ─────────────────────────────────────────────────────────

$table_name = $wpdb->prefix . 'superforms_themes';

$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'light',
    styles LONGTEXT NOT NULL,
    preview_colors TEXT,
    is_system TINYINT(1) DEFAULT 0,
    is_stub TINYINT(1) DEFAULT 0,
    user_id BIGINT(20) UNSIGNED,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug),
    KEY user_id (user_id),
    KEY category (category),
    KEY is_system (is_system)
) ENGINE={$engine} $charset_collate;";

dbDelta( $sql );
```

### DAL Class Structure

Create `/src/includes/class-theme-dal.php`:

```php
<?php
/**
 * Themes Data Access Layer
 *
 * Provides database abstraction for the theming system.
 * Themes are stored independently of forms and can be reused.
 *
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Theme_DAL
 * @since       6.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SUPER_Theme_DAL' ) ) :

class SUPER_Theme_DAL {

    // ─────────────────────────────────────────────────────────
    // THEME CRUD OPERATIONS
    // ─────────────────────────────────────────────────────────

    /**
     * Create new theme
     *
     * @param array $data Theme data (name, styles, category, etc.)
     * @return int|WP_Error Theme ID on success, WP_Error on failure
     */
    public static function create_theme( $data ) { ... }

    /**
     * Get theme by ID
     *
     * @param int $theme_id Theme ID
     * @return array|WP_Error Theme data or error
     */
    public static function get_theme( $theme_id ) { ... }

    /**
     * Get theme by slug
     *
     * @param string $slug Theme slug
     * @return array|WP_Error Theme data or error
     */
    public static function get_theme_by_slug( $slug ) { ... }

    /**
     * Get all themes with optional filters
     *
     * @param array $filters Optional filters (category, is_system, user_id, include_stubs)
     * @return array Array of themes
     */
    public static function get_all_themes( $filters = array() ) { ... }

    /**
     * Update theme
     *
     * @param int   $theme_id Theme ID
     * @param array $data     Data to update
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function update_theme( $theme_id, $data ) { ... }

    /**
     * Delete theme (only non-system themes)
     *
     * @param int $theme_id Theme ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function delete_theme( $theme_id ) { ... }

    // ─────────────────────────────────────────────────────────
    // SEEDING METHODS
    // ─────────────────────────────────────────────────────────

    /**
     * Seed system themes (Light, Dark)
     * Called on plugin activation
     */
    public static function seed_system_themes() { ... }

    /**
     * Seed stub themes (Minimal, Classic, Modern, etc.)
     * Called on plugin activation
     */
    public static function seed_stub_themes() { ... }

    // ─────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────

    /**
     * Generate unique slug from name
     */
    private static function generate_slug( $name ) { ... }

    /**
     * Check if theme is owned by user
     */
    public static function is_owner( $theme_id, $user_id ) { ... }
}

endif;
```

### System Themes Data

Light theme (from existing preset):
```php
array(
    'name'        => 'Light',
    'slug'        => 'light',
    'description' => 'Clean, professional look with subtle grays',
    'category'    => 'light',
    'styles'      => json_encode( LIGHT_PRESET ), // From React preset
    'preview_colors' => json_encode( ['#ffffff', '#1f2937', '#2563eb', '#d1d5db'] ),
    'is_system'   => 1,
    'is_stub'     => 0,
)
```

Dark theme (from existing preset):
```php
array(
    'name'        => 'Dark',
    'slug'        => 'dark',
    'description' => 'Modern dark mode with good contrast',
    'category'    => 'dark',
    'styles'      => json_encode( DARK_PRESET ), // From React preset
    'preview_colors' => json_encode( ['#1f2937', '#f9fafb', '#3b82f6', '#374151'] ),
    'is_system'   => 1,
    'is_stub'     => 0,
)
```

### Stub Themes Data

```php
$stub_themes = array(
    array(
        'name'        => 'Minimal',
        'slug'        => 'minimal',
        'description' => 'Borderless, maximum whitespace, understated',
        'category'    => 'minimal',
        'styles'      => '{}', // Empty - stub
        'preview_colors' => json_encode( ['#ffffff', '#374151', '#6b7280', '#f3f4f6'] ),
        'is_system'   => 1,
        'is_stub'     => 1,
    ),
    array(
        'name'        => 'Classic',
        'slug'        => 'classic',
        'description' => 'Traditional form styling, familiar',
        'category'    => 'light',
        'styles'      => '{}',
        'preview_colors' => json_encode( ['#ffffff', '#333333', '#0066cc', '#cccccc'] ),
        'is_system'   => 1,
        'is_stub'     => 1,
    ),
    array(
        'name'        => 'Modern',
        'slug'        => 'modern',
        'description' => 'Rounded corners, subtle shadows, contemporary',
        'category'    => 'light',
        'styles'      => '{}',
        'preview_colors' => json_encode( ['#ffffff', '#1e293b', '#6366f1', '#e2e8f0'] ),
        'is_system'   => 1,
        'is_stub'     => 1,
    ),
    array(
        'name'        => 'Corporate',
        'slug'        => 'corporate',
        'description' => 'Professional, trust-inspiring, blue tones',
        'category'    => 'light',
        'styles'      => '{}',
        'preview_colors' => json_encode( ['#f8fafc', '#0f172a', '#1d4ed8', '#cbd5e1'] ),
        'is_system'   => 1,
        'is_stub'     => 1,
    ),
    array(
        'name'        => 'Playful',
        'slug'        => 'playful',
        'description' => 'Colorful, friendly, very rounded',
        'category'    => 'light',
        'styles'      => '{}',
        'preview_colors' => json_encode( ['#fef3c7', '#7c2d12', '#f97316', '#fcd34d'] ),
        'is_system'   => 1,
        'is_stub'     => 1,
    ),
    array(
        'name'        => 'High Contrast',
        'slug'        => 'high-contrast',
        'description' => 'Accessibility-focused, WCAG AAA',
        'category'    => 'highContrast',
        'styles'      => '{}',
        'preview_colors' => json_encode( ['#000000', '#ffffff', '#ffff00', '#ffffff'] ),
        'is_system'   => 1,
        'is_stub'     => 1,
    ),
);
```

## Files to Create

1. `/src/includes/class-theme-dal.php` - DAL class

## Files to Modify

1. `/src/includes/class-install.php` - Add themes table creation
2. `/src/includes/class-super-forms.php` - Include DAL file, call seeding on activation

## Implementation Notes

- Follow existing DAL pattern from `class-automation-dal.php`
- Use `wp_parse_args()` for defaults
- Use `sanitize_text_field()`, `absint()` for sanitization
- JSON encode arrays before insert, decode on retrieval
- System themes cannot be deleted (`is_system = 1`)
- Custom themes have `user_id` set to creator
- Stubs are system themes with `is_stub = 1` flag

## Dependencies

- Existing style presets at `src/react/admin/schemas/styles/presets/`
- PHP needs to have matching JSON structure for styles
