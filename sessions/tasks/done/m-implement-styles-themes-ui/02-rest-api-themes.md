---
name: 02-rest-api-themes
parent: m-implement-styles-themes-ui
status: pending
created: 2025-12-06
---

# Subtask 2: REST API for Themes

## Goal

Create `SUPER_Theme_REST_Controller` class with endpoints for theme CRUD operations. Enable applying themes to forms.

## Success Criteria

- [ ] `SUPER_Theme_REST_Controller` class created
- [ ] `GET /themes` - List all themes (with filters)
- [ ] `GET /themes/{id}` - Get single theme
- [ ] `POST /themes` - Create custom theme
- [ ] `PUT /themes/{id}` - Update theme
- [ ] `DELETE /themes/{id}` - Delete custom theme (not system themes)
- [ ] `POST /themes/{id}/apply` - Apply theme to form
- [ ] Permission checks using existing pattern (cookie auth + API key)
- [ ] listThemes returns `is_stub` flag for UI badge

## Technical Specification

### Endpoint Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/super-forms/v1/themes` | List themes with filters |
| GET | `/super-forms/v1/themes/{id}` | Get single theme by ID |
| GET | `/super-forms/v1/themes/slug/{slug}` | Get theme by slug |
| POST | `/super-forms/v1/themes` | Create custom theme |
| PUT | `/super-forms/v1/themes/{id}` | Update theme |
| DELETE | `/super-forms/v1/themes/{id}` | Delete theme |
| POST | `/super-forms/v1/themes/{id}/apply` | Apply theme to form |

### Controller Class Structure

Create `/src/includes/class-theme-rest-controller.php`:

```php
<?php
/**
 * Theme REST API Controller
 *
 * Provides REST API v1 endpoints for the theming system.
 *
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Theme_REST_Controller
 * @since       6.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SUPER_Theme_REST_Controller' ) ) :

class SUPER_Theme_REST_Controller extends WP_REST_Controller {

    protected $namespace = 'super-forms/v1';

    public function register_routes() {
        // Collection: GET (list) + POST (create)
        register_rest_route(
            $this->namespace,
            '/themes',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_themes' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_theme' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                    'args'                => $this->get_create_theme_args(),
                ),
            )
        );

        // Single item: GET, PUT, DELETE
        register_rest_route(
            $this->namespace,
            '/themes/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_theme' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_theme' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                    'args'                => $this->get_update_theme_args(),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_theme' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
            )
        );

        // Get by slug
        register_rest_route(
            $this->namespace,
            '/themes/slug/(?P<slug>[a-z0-9-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_theme_by_slug' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Apply theme to form
        register_rest_route(
            $this->namespace,
            '/themes/(?P<id>[\d]+)/apply',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'apply_theme' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'form_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'description'       => 'Form ID to apply theme to',
                    ),
                ),
            )
        );
    }
}

endif;
```

### Endpoint Implementations

#### GET /themes (List)

```php
public function get_themes( $request ) {
    $params = $request->get_params();

    $filters = array(
        'category'      => $params['category'] ?? null,
        'include_stubs' => $params['include_stubs'] ?? true,
        'user_id'       => $params['user_only'] ? get_current_user_id() : null,
    );

    $themes = SUPER_Theme_DAL::get_all_themes( $filters );

    // Decode styles JSON for each theme
    foreach ( $themes as &$theme ) {
        $theme['styles'] = json_decode( $theme['styles'], true );
        $theme['preview_colors'] = json_decode( $theme['preview_colors'], true );
        $theme['is_system'] = (bool) $theme['is_system'];
        $theme['is_stub'] = (bool) $theme['is_stub'];
    }

    return rest_ensure_response( $themes );
}
```

**Query Parameters:**
- `category` (string): Filter by category (light, dark, minimal, etc.)
- `include_stubs` (boolean, default: true): Include stub themes
- `user_only` (boolean, default: false): Only user's custom themes

#### POST /themes (Create)

```php
public function create_theme( $request ) {
    $params = $request->get_json_params();

    $theme_data = array(
        'name'           => $params['name'] ?? '',
        'description'    => $params['description'] ?? '',
        'category'       => $params['category'] ?? 'light',
        'styles'         => $params['styles'] ?? array(),
        'preview_colors' => $params['preview_colors'] ?? array(),
        'user_id'        => get_current_user_id(),
    );

    $theme_id = SUPER_Theme_DAL::create_theme( $theme_data );

    if ( is_wp_error( $theme_id ) ) {
        return $theme_id;
    }

    $theme = SUPER_Theme_DAL::get_theme( $theme_id );
    return rest_ensure_response( $theme );
}
```

**Request Body:**
```json
{
  "name": "My Custom Theme",
  "description": "A theme based on my brand colors",
  "category": "light",
  "styles": { ... },
  "preview_colors": ["#ffffff", "#333333", "#0066cc", "#eeeeee"]
}
```

#### POST /themes/{id}/apply

```php
public function apply_theme( $request ) {
    $theme_id = absint( $request['id'] );
    $form_id = absint( $request['form_id'] );

    // Verify theme exists and is not a stub
    $theme = SUPER_Theme_DAL::get_theme( $theme_id );
    if ( is_wp_error( $theme ) ) {
        return $theme;
    }

    if ( $theme['is_stub'] ) {
        return new WP_Error(
            'theme_is_stub',
            __( 'Cannot apply a stub theme. This theme is coming soon.', 'super-forms' ),
            array( 'status' => 400 )
        );
    }

    // Get form and update settings
    $form = SUPER_Form_DAL::get_form( $form_id );
    if ( is_wp_error( $form ) ) {
        return $form;
    }

    // Update form settings with theme
    $settings = json_decode( $form['settings'], true ) ?? array();
    $settings['currentThemeId'] = $theme_id;
    $settings['globalStyles'] = json_decode( $theme['styles'], true );

    $result = SUPER_Form_DAL::update_form( $form_id, array(
        'settings' => $settings,
    ) );

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    return rest_ensure_response( array(
        'success'  => true,
        'theme_id' => $theme_id,
        'form_id'  => $form_id,
        'message'  => sprintf( __( 'Theme "%s" applied to form.', 'super-forms' ), $theme['name'] ),
    ) );
}
```

### Response Format

**Theme Object:**
```json
{
  "id": 1,
  "name": "Light",
  "slug": "light",
  "description": "Clean, professional look with subtle grays",
  "category": "light",
  "styles": { ... },
  "preview_colors": ["#ffffff", "#1f2937", "#2563eb", "#d1d5db"],
  "is_system": true,
  "is_stub": false,
  "user_id": null,
  "created_at": "2025-12-06 12:00:00",
  "updated_at": "2025-12-06 12:00:00"
}
```

### Permission Handling

Copy permission pattern from `SUPER_Automation_REST_Controller`:

```php
public function check_permission( $request = null ) {
    // Check for API key authentication first
    if ( $request ) {
        $api_key = $request->get_header( 'X-API-Key' );
        if ( $api_key ) {
            return $this->authenticate_api_key( $api_key, $request );
        }
    }

    // WordPress authentication fallback
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'Authentication required.', 'super-forms' ),
            array( 'status' => 401 )
        );
    }

    // Check user capability
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to manage themes.', 'super-forms' ),
            array( 'status' => 403 )
        );
    }

    return true;
}
```

### Delete Validation

Custom themes can be deleted, system themes cannot:

```php
public function delete_theme( $request ) {
    $theme_id = absint( $request['id'] );

    $theme = SUPER_Theme_DAL::get_theme( $theme_id );
    if ( is_wp_error( $theme ) ) {
        return $theme;
    }

    if ( $theme['is_system'] ) {
        return new WP_Error(
            'cannot_delete_system_theme',
            __( 'System themes cannot be deleted.', 'super-forms' ),
            array( 'status' => 403 )
        );
    }

    // Check ownership for custom themes
    if ( $theme['user_id'] != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'You can only delete your own themes.', 'super-forms' ),
            array( 'status' => 403 )
        );
    }

    $result = SUPER_Theme_DAL::delete_theme( $theme_id );

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    return rest_ensure_response( array(
        'success' => true,
        'deleted' => $theme_id,
    ) );
}
```

## Files to Create

1. `/src/includes/class-theme-rest-controller.php` - REST controller

## Files to Modify

1. `/src/includes/class-super-forms.php` - Include controller, register routes on `rest_api_init`

## Implementation Notes

- Follow existing REST controller pattern from `class-automation-rest-controller.php`
- Return `WP_Error` objects directly - WordPress converts them to proper error responses
- JSON decode `styles` and `preview_colors` when returning to frontend
- Validate stub status before allowing apply
- Ownership check on delete (user_id must match or be admin)

## Dependencies

- Subtask 01 (Database Schema & DAL) must be complete
- Existing `SUPER_Form_DAL` for form updates
