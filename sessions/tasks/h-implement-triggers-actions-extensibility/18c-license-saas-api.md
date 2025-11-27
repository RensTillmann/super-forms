# Phase 18c: License System and SaaS API

## Status: PLANNING

## Overview

Implement a flexible license management system that enables developers to:
- Sell software licenses via Super Forms
- Validate licenses from external applications via REST API
- Manage site activations and limits
- Support both one-time (perpetual) and subscription-based licensing
- Track usage and enforce feature limits

**Target Users:** WordPress plugin/theme developers, SaaS builders, digital product sellers

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        License System Architecture                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  SELLER'S SITE (Super Forms)              BUYER'S SITE (Customer)           │
│  ─────────────────────────────            ────────────────────────           │
│                                                                              │
│  ┌─────────────────────────┐              ┌─────────────────────────┐       │
│  │ Purchase Form           │              │ Your Plugin/Theme       │       │
│  │ - Product selection     │              │ - License key input     │       │
│  │ - Payment (Stripe)      │              │ - Activation logic      │       │
│  │ - Account creation      │              │ - Feature gating        │       │
│  └───────────┬─────────────┘              └───────────┬─────────────┘       │
│              │                                        │                      │
│              ▼                                        ▼                      │
│  ┌─────────────────────────┐              ┌─────────────────────────┐       │
│  │ Trigger: Payment Success│              │ API Call: Validate      │       │
│  │ Action: Create License  │◀────────────▶│ API Call: Activate      │       │
│  └───────────┬─────────────┘   REST API   │ API Call: Deactivate    │       │
│              │                            └─────────────────────────┘       │
│              ▼                                                               │
│  ┌─────────────────────────┐                                                │
│  │ wp_superforms_licenses  │                                                │
│  │ wp_superforms_license_  │                                                │
│  │ activations             │                                                │
│  └─────────────────────────┘                                                │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Database Schema

### Table: `wp_superforms_licenses`

```sql
CREATE TABLE {$prefix}superforms_licenses (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- License Key (public identifier)
    license_key VARCHAR(255) NOT NULL,

    -- License Secret (for API authentication, optional)
    -- Hashed with wp_hash_password() for security
    license_secret VARCHAR(255) DEFAULT NULL,

    -- Product Information
    product_id VARCHAR(100) NOT NULL,              -- Developer's product identifier
    product_name VARCHAR(255) DEFAULT NULL,        -- Human-readable name
    product_variant VARCHAR(100) DEFAULT NULL,     -- 'lite', 'pro', 'agency', etc.

    -- Customer Information
    user_id BIGINT(20) UNSIGNED DEFAULT 0,         -- WP user ID (if registered)
    customer_email VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) DEFAULT NULL,
    customer_company VARCHAR(255) DEFAULT NULL,

    -- Payment/Subscription Links
    payment_id BIGINT(20) UNSIGNED DEFAULT NULL,   -- Initial purchase payment
    subscription_id BIGINT(20) UNSIGNED DEFAULT NULL,  -- If recurring license

    -- License Type
    license_type VARCHAR(30) NOT NULL DEFAULT 'perpetual',
    -- 'perpetual'    - Never expires (may have update limits)
    -- 'subscription' - Tied to subscription status
    -- 'trial'        - Time-limited trial
    -- 'free'         - Free tier (feature-limited)

    -- Status
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    -- 'pending'      - Awaiting payment/verification
    -- 'active'       - License is valid
    -- 'expired'      - Past valid_until date
    -- 'suspended'    - Temporarily disabled (payment issue)
    -- 'revoked'      - Permanently disabled (TOS violation)

    -- Validity Period
    valid_from DATETIME NOT NULL,
    valid_until DATETIME DEFAULT NULL,             -- NULL = perpetual

    -- Activation Limits
    max_activations INT DEFAULT 1,                 -- -1 = unlimited
    current_activations INT DEFAULT 0,

    -- Features and Limits (flexible JSON)
    features JSON DEFAULT NULL,
    -- Example: {
    --   "api_calls_monthly": 10000,
    --   "storage_gb": 5,
    --   "premium_support": true,
    --   "white_label": false,
    --   "allowed_domains": ["*.mycompany.com"]
    -- }

    -- Usage Tracking
    last_validated_at DATETIME DEFAULT NULL,
    last_validated_ip VARCHAR(45) DEFAULT NULL,
    last_validated_site VARCHAR(255) DEFAULT NULL,
    validation_count INT DEFAULT 0,

    -- Notes (admin-only)
    admin_notes TEXT DEFAULT NULL,

    -- Timestamps
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY license_key (license_key),
    KEY product_id (product_id),
    KEY customer_email (customer_email),
    KEY subscription_id (subscription_id),
    KEY status (status),
    KEY valid_until (valid_until),
    KEY license_type (license_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table: `wp_superforms_license_activations`

```sql
CREATE TABLE {$prefix}superforms_license_activations (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id BIGINT(20) UNSIGNED NOT NULL,

    -- Activation Identity
    site_url VARCHAR(255) NOT NULL,                -- https://example.com
    site_name VARCHAR(255) DEFAULT NULL,           -- WordPress site title
    activation_token VARCHAR(255) NOT NULL,        -- Unique token for this activation

    -- Environment Information (for debugging/support)
    ip_address VARCHAR(45) DEFAULT NULL,
    php_version VARCHAR(20) DEFAULT NULL,
    wp_version VARCHAR(20) DEFAULT NULL,
    product_version VARCHAR(20) DEFAULT NULL,      -- Version of licensed product
    server_software VARCHAR(255) DEFAULT NULL,     -- Apache/Nginx/etc.

    -- Activation Metadata (flexible)
    meta JSON DEFAULT NULL,
    -- Example: {
    --   "multisite": false,
    --   "active_plugins": 15,
    --   "theme": "astra"
    -- }

    -- Status
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    -- 'active'       - Currently activated
    -- 'deactivated'  - User deactivated
    -- 'expired'      - Activation expired (for time-limited)

    -- Timestamps
    activated_at DATETIME NOT NULL,
    deactivated_at DATETIME DEFAULT NULL,
    last_check_at DATETIME DEFAULT NULL,           -- Last validation ping

    PRIMARY KEY (id),
    UNIQUE KEY activation_token (activation_token),
    UNIQUE KEY license_site (license_id, site_url(191)),  -- One activation per site
    KEY license_id (license_id),
    KEY site_url (site_url(191)),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## License Key Format

### Standard Format
```
{PREFIX}-{SEGMENT1}-{SEGMENT2}-{SEGMENT3}-{SEGMENT4}

Example: SF-A1B2-C3D4-E5F6-G7H8
```

### Custom Prefix Support
Developers can customize the prefix for their products:
```
MYPLUGIN-A1B2-C3D4-E5F6-G7H8
THEME-A1B2-C3D4-E5F6-G7H8
SAAS-A1B2-C3D4-E5F6-G7H8
```

### Generation Algorithm
```php
public static function generate_key( string $prefix = 'SF' ): string {
    $segments = array();
    for ( $i = 0; $i < 4; $i++ ) {
        $segments[] = strtoupper( wp_generate_password( 4, false, false ) );
    }
    return $prefix . '-' . implode( '-', $segments );
}
```

## REST API Endpoints

### Public Endpoints (No Authentication Required)

#### POST `/licenses/validate`
Validate a license key and get license details.

**Request:**
```json
{
  "license_key": "SF-A1B2-C3D4-E5F6-G7H8",
  "site_url": "https://clientsite.com",
  "product_id": "my-plugin",
  "product_version": "2.5.0"
}
```

**Response (Valid):**
```json
{
  "success": true,
  "license": {
    "license_key": "SF-A1B2-C3D4-E5F6-G7H8",
    "product_id": "my-plugin",
    "product_variant": "pro",
    "status": "active",
    "license_type": "perpetual",
    "valid_until": null,
    "activations_used": 2,
    "activations_limit": 5,
    "features": {
      "premium_support": true,
      "white_label": false
    },
    "is_activated_on_site": true
  }
}
```

**Response (Invalid):**
```json
{
  "success": false,
  "error_code": "license_expired",
  "error_message": "This license expired on November 24, 2024.",
  "license": {
    "status": "expired",
    "valid_until": "2024-11-24"
  }
}
```

**Error Codes:**
| Code | Description |
|------|-------------|
| `license_not_found` | License key does not exist |
| `license_expired` | License has passed valid_until date |
| `license_suspended` | License temporarily disabled |
| `license_revoked` | License permanently disabled |
| `product_mismatch` | License is for different product |
| `invalid_request` | Missing required parameters |

---

#### POST `/licenses/activate`
Activate a license on a site.

**Request:**
```json
{
  "license_key": "SF-A1B2-C3D4-E5F6-G7H8",
  "site_url": "https://newsite.com",
  "site_name": "My New Site",
  "product_version": "2.5.0",
  "environment": {
    "php_version": "8.2.0",
    "wp_version": "6.4.2"
  }
}
```

**Response (Success):**
```json
{
  "success": true,
  "activation_token": "act_a1b2c3d4e5f6g7h8i9j0",
  "license": {
    "license_key": "SF-A1B2-C3D4-E5F6-G7H8",
    "status": "active",
    "activations_used": 3,
    "activations_limit": 5,
    "features": { ... }
  },
  "message": "License activated successfully on https://newsite.com"
}
```

**Response (Limit Reached):**
```json
{
  "success": false,
  "error_code": "activation_limit_reached",
  "error_message": "This license has reached its activation limit (5/5 sites).",
  "activations": [
    {
      "site_url": "https://site1.com",
      "site_name": "Site One",
      "activated_at": "2025-06-15T10:30:00Z"
    },
    // ... more activations
  ],
  "upgrade_url": "https://yoursite.com/upgrade"
}
```

---

#### POST `/licenses/deactivate`
Deactivate a license from a site.

**Request:**
```json
{
  "license_key": "SF-A1B2-C3D4-E5F6-G7H8",
  "site_url": "https://oldsite.com",
  "activation_token": "act_a1b2c3d4e5f6g7h8i9j0"
}
```

**Response:**
```json
{
  "success": true,
  "message": "License deactivated from https://oldsite.com",
  "activations_remaining": 4,
  "activations_limit": 5
}
```

---

#### GET `/licenses/check`
Quick validity check (minimal data, fast response).

**Request:**
```
GET /licenses/check?license_key=SF-A1B2-C3D4-E5F6-G7H8&site_url=https://mysite.com
```

**Response:**
```json
{
  "valid": true,
  "status": "active",
  "activated": true
}
```

---

### Admin Endpoints (Requires Authentication)

#### GET `/admin/licenses`
List all licenses with filtering.

**Parameters:**
- `product_id` - Filter by product
- `status` - Filter by status
- `customer_email` - Search by email
- `search` - Search in key/email/name
- `per_page` - Results per page (default: 20)
- `page` - Page number

---

#### POST `/admin/licenses`
Create a license manually.

**Request:**
```json
{
  "product_id": "my-plugin",
  "product_variant": "pro",
  "customer_email": "customer@example.com",
  "customer_name": "John Doe",
  "license_type": "perpetual",
  "max_activations": 3,
  "features": {
    "premium_support": true
  },
  "admin_notes": "Complimentary license for beta tester"
}
```

---

#### PUT `/admin/licenses/{id}`
Update a license.

---

#### DELETE `/admin/licenses/{id}`
Revoke a license (soft delete - sets status to 'revoked').

---

## Data Access Layer: SUPER_License_DAL

```php
class SUPER_License_DAL {

    /**
     * Create a new license
     *
     * @param array $data License data
     * @return int|WP_Error License ID or error
     */
    public static function create( array $data ): int|WP_Error {
        global $wpdb;

        $defaults = array(
            'license_key'         => self::generate_key(),
            'product_id'          => '',
            'product_name'        => '',
            'product_variant'     => '',
            'customer_email'      => '',
            'customer_name'       => '',
            'license_type'        => 'perpetual',
            'status'              => 'active',
            'valid_from'          => current_time( 'mysql' ),
            'valid_until'         => null,
            'max_activations'     => 1,
            'current_activations' => 0,
            'features'            => null,
            'created_at'          => current_time( 'mysql' ),
            'updated_at'          => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        // Validation
        if ( empty( $data['product_id'] ) ) {
            return new WP_Error( 'missing_product_id', 'Product ID is required' );
        }
        if ( empty( $data['customer_email'] ) || ! is_email( $data['customer_email'] ) ) {
            return new WP_Error( 'invalid_email', 'Valid customer email is required' );
        }

        // JSON encode features
        if ( is_array( $data['features'] ) ) {
            $data['features'] = wp_json_encode( $data['features'] );
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'superforms_licenses',
            $data,
            self::get_format( $data )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_error', $wpdb->last_error );
        }

        $license_id = $wpdb->insert_id;

        do_action( 'super_license_created', $license_id, $data );

        return $license_id;
    }

    /**
     * Generate unique license key
     *
     * @param string $prefix Key prefix (default: SF)
     * @return string License key
     */
    public static function generate_key( string $prefix = 'SF' ): string {
        global $wpdb;

        do {
            $segments = array();
            for ( $i = 0; $i < 4; $i++ ) {
                $segments[] = strtoupper( wp_generate_password( 4, false, false ) );
            }
            $key = $prefix . '-' . implode( '-', $segments );

            // Check uniqueness
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_licenses WHERE license_key = %s",
                $key
            ) );
        } while ( $exists > 0 );

        return $key;
    }

    /**
     * Validate a license
     *
     * @param string $license_key License key
     * @param string $site_url    Site URL (optional)
     * @param string $product_id  Product ID (optional)
     * @return array Validation result
     */
    public static function validate( string $license_key, string $site_url = '', string $product_id = '' ): array {
        $license = self::get_by_key( $license_key );

        if ( ! $license ) {
            return array(
                'valid'        => false,
                'error_code'   => 'license_not_found',
                'error_message' => 'License key not found.',
            );
        }

        // Check product match
        if ( $product_id && $license->product_id !== $product_id ) {
            return array(
                'valid'        => false,
                'error_code'   => 'product_mismatch',
                'error_message' => 'This license is for a different product.',
                'license'      => self::sanitize_for_response( $license ),
            );
        }

        // Check status
        if ( $license->status !== 'active' ) {
            $messages = array(
                'pending'   => 'License is pending activation.',
                'expired'   => 'License has expired.',
                'suspended' => 'License has been suspended.',
                'revoked'   => 'License has been revoked.',
            );
            return array(
                'valid'        => false,
                'error_code'   => 'license_' . $license->status,
                'error_message' => $messages[ $license->status ] ?? 'License is not active.',
                'license'      => self::sanitize_for_response( $license ),
            );
        }

        // Check expiration
        if ( $license->valid_until && strtotime( $license->valid_until ) < time() ) {
            // Auto-expire the license
            self::update( $license->id, array( 'status' => 'expired' ) );
            return array(
                'valid'        => false,
                'error_code'   => 'license_expired',
                'error_message' => sprintf( 'License expired on %s.', date_i18n( get_option( 'date_format' ), strtotime( $license->valid_until ) ) ),
                'license'      => self::sanitize_for_response( $license ),
            );
        }

        // Check if activated on this site
        $is_activated = false;
        if ( $site_url ) {
            $is_activated = self::is_activated_on_site( $license->id, $site_url );
        }

        // Update validation tracking
        self::update( $license->id, array(
            'last_validated_at'   => current_time( 'mysql' ),
            'last_validated_site' => $site_url,
            'validation_count'    => $license->validation_count + 1,
        ) );

        return array(
            'valid'   => true,
            'license' => array_merge(
                self::sanitize_for_response( $license ),
                array( 'is_activated_on_site' => $is_activated )
            ),
        );
    }

    /**
     * Activate license on a site
     *
     * @param string $license_key License key
     * @param string $site_url    Site URL
     * @param array  $meta        Additional metadata
     * @return array|WP_Error Activation result or error
     */
    public static function activate( string $license_key, string $site_url, array $meta = array() ): array|WP_Error {
        global $wpdb;

        $license = self::get_by_key( $license_key );

        if ( ! $license ) {
            return new WP_Error( 'license_not_found', 'License key not found.' );
        }

        if ( $license->status !== 'active' ) {
            return new WP_Error( 'license_' . $license->status, 'License is not active.' );
        }

        // Check expiration
        if ( $license->valid_until && strtotime( $license->valid_until ) < time() ) {
            return new WP_Error( 'license_expired', 'License has expired.' );
        }

        // Normalize URL
        $site_url = trailingslashit( strtolower( $site_url ) );

        // Check if already activated on this site
        $existing = self::get_activation( $license->id, $site_url );
        if ( $existing && $existing->status === 'active' ) {
            return array(
                'success'          => true,
                'activation_token' => $existing->activation_token,
                'license'          => self::sanitize_for_response( $license ),
                'message'          => 'License already activated on this site.',
                'already_active'   => true,
            );
        }

        // Check activation limit
        if ( $license->max_activations > 0 && $license->current_activations >= $license->max_activations ) {
            $activations = self::get_activations( $license->id );
            return new WP_Error( 'activation_limit_reached', sprintf(
                'License has reached its activation limit (%d/%d sites).',
                $license->current_activations,
                $license->max_activations
            ), array( 'activations' => $activations ) );
        }

        // Generate activation token
        $activation_token = 'act_' . wp_generate_password( 24, false, false );

        // Create activation record
        $activation_data = array(
            'license_id'       => $license->id,
            'site_url'         => $site_url,
            'site_name'        => $meta['site_name'] ?? '',
            'activation_token' => $activation_token,
            'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? null,
            'php_version'      => $meta['php_version'] ?? null,
            'wp_version'       => $meta['wp_version'] ?? null,
            'product_version'  => $meta['product_version'] ?? null,
            'server_software'  => $meta['server_software'] ?? null,
            'meta'             => ! empty( $meta['extra'] ) ? wp_json_encode( $meta['extra'] ) : null,
            'status'           => 'active',
            'activated_at'     => current_time( 'mysql' ),
        );

        // Reactivate if previously deactivated
        if ( $existing ) {
            $wpdb->update(
                $wpdb->prefix . 'superforms_license_activations',
                $activation_data,
                array( 'id' => $existing->id )
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'superforms_license_activations',
                $activation_data
            );
        }

        // Update activation count
        self::update( $license->id, array(
            'current_activations' => $license->current_activations + 1,
        ) );

        do_action( 'super_license_activated', $license->id, $site_url, $activation_token );

        return array(
            'success'          => true,
            'activation_token' => $activation_token,
            'license'          => self::sanitize_for_response( self::get( $license->id ) ),
            'message'          => sprintf( 'License activated on %s', $site_url ),
        );
    }

    /**
     * Deactivate license from a site
     *
     * @param string $license_key      License key
     * @param string $site_url         Site URL
     * @param string $activation_token Activation token (for verification)
     * @return bool|WP_Error
     */
    public static function deactivate( string $license_key, string $site_url, string $activation_token = '' ): bool|WP_Error {
        global $wpdb;

        $license = self::get_by_key( $license_key );

        if ( ! $license ) {
            return new WP_Error( 'license_not_found', 'License key not found.' );
        }

        $site_url = trailingslashit( strtolower( $site_url ) );

        $activation = self::get_activation( $license->id, $site_url );

        if ( ! $activation || $activation->status !== 'active' ) {
            return new WP_Error( 'not_activated', 'License is not activated on this site.' );
        }

        // Verify activation token if provided
        if ( $activation_token && $activation->activation_token !== $activation_token ) {
            return new WP_Error( 'invalid_token', 'Invalid activation token.' );
        }

        // Deactivate
        $wpdb->update(
            $wpdb->prefix . 'superforms_license_activations',
            array(
                'status'         => 'deactivated',
                'deactivated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $activation->id )
        );

        // Update activation count
        self::update( $license->id, array(
            'current_activations' => max( 0, $license->current_activations - 1 ),
        ) );

        do_action( 'super_license_deactivated', $license->id, $site_url );

        return true;
    }

    /**
     * Check if a feature is enabled for a license
     *
     * @param string $license_key License key
     * @param string $feature     Feature key
     * @return bool
     */
    public static function check_feature( string $license_key, string $feature ): bool {
        $license = self::get_by_key( $license_key );

        if ( ! $license || $license->status !== 'active' ) {
            return false;
        }

        $features = json_decode( $license->features, true ) ?: array();

        return ! empty( $features[ $feature ] );
    }

    /**
     * Get feature value (for limits)
     *
     * @param string $license_key License key
     * @param string $feature     Feature key
     * @param mixed  $default     Default value
     * @return mixed
     */
    public static function get_feature_value( string $license_key, string $feature, $default = null ) {
        $license = self::get_by_key( $license_key );

        if ( ! $license ) {
            return $default;
        }

        $features = json_decode( $license->features, true ) ?: array();

        return $features[ $feature ] ?? $default;
    }

    // ... Additional methods: get, get_by_key, update, query, get_activations, etc.
}
```

## Trigger Action: Create License

Add a new action type for automatically creating licenses on payment success.

```php
class SUPER_Action_Create_License extends SUPER_Trigger_Action_Base {

    public static function get_type(): string {
        return 'create_license';
    }

    public static function get_label(): string {
        return __( 'Create License', 'super-forms' );
    }

    public static function get_description(): string {
        return __( 'Generate a license key for the customer', 'super-forms' );
    }

    public static function get_settings_schema(): array {
        return array(
            'product_id' => array(
                'type'        => 'text',
                'label'       => __( 'Product ID', 'super-forms' ),
                'description' => __( 'Unique identifier for your product', 'super-forms' ),
                'required'    => true,
            ),
            'product_name' => array(
                'type'  => 'text',
                'label' => __( 'Product Name', 'super-forms' ),
            ),
            'product_variant' => array(
                'type'        => 'text',
                'label'       => __( 'Product Variant', 'super-forms' ),
                'description' => __( 'e.g., "pro", "basic", "enterprise"', 'super-forms' ),
                'supports_tags' => true,
            ),
            'license_type' => array(
                'type'    => 'select',
                'label'   => __( 'License Type', 'super-forms' ),
                'options' => array(
                    'perpetual'    => __( 'Perpetual (never expires)', 'super-forms' ),
                    'subscription' => __( 'Subscription (tied to payment)', 'super-forms' ),
                    'trial'        => __( 'Trial (time-limited)', 'super-forms' ),
                ),
                'default' => 'perpetual',
            ),
            'max_activations' => array(
                'type'    => 'number',
                'label'   => __( 'Max Activations', 'super-forms' ),
                'default' => 1,
                'min'     => -1,
                'description' => __( '-1 for unlimited', 'super-forms' ),
                'supports_tags' => true,
            ),
            'valid_days' => array(
                'type'        => 'number',
                'label'       => __( 'Valid For (days)', 'super-forms' ),
                'description' => __( 'Leave empty for perpetual', 'super-forms' ),
                'supports_tags' => true,
            ),
            'features' => array(
                'type'        => 'textarea',
                'label'       => __( 'Features (JSON)', 'super-forms' ),
                'description' => __( 'e.g., {"premium_support": true, "api_calls": 10000}', 'super-forms' ),
            ),
            'key_prefix' => array(
                'type'    => 'text',
                'label'   => __( 'License Key Prefix', 'super-forms' ),
                'default' => 'SF',
            ),
            'send_email' => array(
                'type'    => 'checkbox',
                'label'   => __( 'Send license key via email', 'super-forms' ),
                'default' => true,
            ),
        );
    }

    public function execute( array $context ): array {
        $settings = $this->get_settings();

        // Get customer email from form data or payment
        $customer_email = $context['data']['email'] ?? $context['payment']['customer_email'] ?? '';
        $customer_name  = $context['data']['name'] ?? $context['data']['first_name'] ?? '';

        if ( empty( $customer_email ) ) {
            return array(
                'success' => false,
                'message' => 'Customer email not found in form data.',
            );
        }

        // Calculate valid_until
        $valid_until = null;
        if ( ! empty( $settings['valid_days'] ) ) {
            $days = intval( $this->replace_tags( $settings['valid_days'], $context ) );
            $valid_until = date( 'Y-m-d H:i:s', strtotime( "+{$days} days" ) );
        }

        // Parse features JSON
        $features = null;
        if ( ! empty( $settings['features'] ) ) {
            $features = json_decode( $settings['features'], true );
        }

        // Create license
        $license_id = SUPER_License_DAL::create( array(
            'license_key'     => SUPER_License_DAL::generate_key( $settings['key_prefix'] ?? 'SF' ),
            'product_id'      => $settings['product_id'],
            'product_name'    => $settings['product_name'] ?? '',
            'product_variant' => $this->replace_tags( $settings['product_variant'] ?? '', $context ),
            'customer_email'  => $customer_email,
            'customer_name'   => $customer_name,
            'user_id'         => $context['user_id'] ?? 0,
            'payment_id'      => $context['payment_id'] ?? null,
            'subscription_id' => $context['subscription_id'] ?? null,
            'license_type'    => $settings['license_type'] ?? 'perpetual',
            'max_activations' => intval( $this->replace_tags( $settings['max_activations'] ?? '1', $context ) ),
            'valid_until'     => $valid_until,
            'features'        => $features,
        ) );

        if ( is_wp_error( $license_id ) ) {
            return array(
                'success' => false,
                'message' => $license_id->get_error_message(),
            );
        }

        $license = SUPER_License_DAL::get( $license_id );

        // Store license key in context for subsequent actions
        $context['license_key'] = $license->license_key;
        $context['license_id']  = $license_id;

        // Optionally send email
        if ( ! empty( $settings['send_email'] ) ) {
            do_action( 'super_send_license_email', $license, $context );
        }

        return array(
            'success'     => true,
            'license_id'  => $license_id,
            'license_key' => $license->license_key,
            'message'     => sprintf( 'License created: %s', $license->license_key ),
        );
    }
}
```

## Integration Example: Plugin Developer SDK

Provide a simple SDK class that plugin developers can include:

```php
/**
 * Super Forms License Client
 *
 * Include this file in your plugin to validate licenses.
 *
 * Usage:
 *   $client = new SF_License_Client( 'https://yoursite.com', 'your-product-id' );
 *   $result = $client->activate( $license_key );
 */
class SF_License_Client {

    private $api_url;
    private $product_id;
    private $product_version;

    public function __construct( string $api_url, string $product_id, string $version = '1.0.0' ) {
        $this->api_url         = trailingslashit( $api_url ) . 'wp-json/super-forms/v1/licenses';
        $this->product_id      = $product_id;
        $this->product_version = $version;
    }

    public function validate( string $license_key ): array {
        return $this->request( 'validate', array(
            'license_key'     => $license_key,
            'site_url'        => home_url(),
            'product_id'      => $this->product_id,
            'product_version' => $this->product_version,
        ) );
    }

    public function activate( string $license_key ): array {
        return $this->request( 'activate', array(
            'license_key'     => $license_key,
            'site_url'        => home_url(),
            'site_name'       => get_bloginfo( 'name' ),
            'product_version' => $this->product_version,
            'environment'     => array(
                'php_version' => PHP_VERSION,
                'wp_version'  => get_bloginfo( 'version' ),
            ),
        ) );
    }

    public function deactivate( string $license_key, string $activation_token ): array {
        return $this->request( 'deactivate', array(
            'license_key'      => $license_key,
            'site_url'         => home_url(),
            'activation_token' => $activation_token,
        ) );
    }

    public function check( string $license_key ): array {
        $response = wp_remote_get( add_query_arg( array(
            'license_key' => $license_key,
            'site_url'    => home_url(),
        ), $this->api_url . '/check' ) );

        if ( is_wp_error( $response ) ) {
            return array( 'valid' => false, 'error' => $response->get_error_message() );
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    private function request( string $endpoint, array $data ): array {
        $response = wp_remote_post( $this->api_url . '/' . $endpoint, array(
            'body'    => wp_json_encode( $data ),
            'headers' => array( 'Content-Type' => 'application/json' ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error_code' => 'connection_error',
                'error_message' => $response->get_error_message(),
            );
        }

        return json_decode( wp_remote_retrieve_body( $response ), true ) ?: array(
            'success' => false,
            'error_code' => 'invalid_response',
            'error_message' => 'Invalid response from license server.',
        );
    }
}
```

## Subscription-License Synchronization

When subscription status changes, automatically update linked licenses:

```php
// Hook into subscription events
add_action( 'super_subscription_canceled', function( $subscription_id ) {
    $licenses = SUPER_License_DAL::get_by_subscription( $subscription_id );
    foreach ( $licenses as $license ) {
        SUPER_License_DAL::update( $license->id, array(
            'status' => 'expired',
        ) );
    }
} );

add_action( 'super_subscription_renewed', function( $subscription_id, $payment_id ) {
    $licenses = SUPER_License_DAL::get_by_subscription( $subscription_id );
    foreach ( $licenses as $license ) {
        // Extend validity if time-limited
        if ( $license->valid_until ) {
            SUPER_License_DAL::update( $license->id, array(
                'valid_until' => date( 'Y-m-d H:i:s', strtotime( $license->valid_until . ' +1 month' ) ),
            ) );
        }
    }
}, 10, 2 );

add_action( 'super_subscription_suspended', function( $subscription_id ) {
    $licenses = SUPER_License_DAL::get_by_subscription( $subscription_id );
    foreach ( $licenses as $license ) {
        SUPER_License_DAL::update( $license->id, array(
            'status' => 'suspended',
        ) );
    }
} );
```

## Admin UI

### License List Table
```
Super Forms > Licenses
┌─────────────────────────────────────────────────────────────────────────────┐
│ Licenses                                    [Add New] [Export] [Settings]   │
├─────────────────────────────────────────────────────────────────────────────┤
│ Product: [All ▼]  Status: [All ▼]  Type: [All ▼]  [Search licenses...]     │
├─────────────────────────────────────────────────────────────────────────────┤
│ □  License Key          Product      Customer         Sites    Status       │
├─────────────────────────────────────────────────────────────────────────────┤
│ □  SF-A1B2-C3D4-...    SEO Pro      john@example     2/5      ● Active     │
│ □  SF-E5F6-G7H8-...    SEO Pro      jane@company     5/5      ● Active     │
│ □  SF-I9J0-K1L2-...    Theme Pro    bob@startup      1/1      ○ Expired    │
│ □  SF-M3N4-O5P6-...    SEO Pro      alice@agency     0/∞      ● Active     │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Single License View
```
┌─────────────────────────────────────────────────────────────────────────────┐
│ License: SF-A1B2-C3D4-E5F6-G7H8                               [← Back]      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ Status: ● Active                    Type: Perpetual                         │
│ Product: SEO Pro (pro)              Created: Nov 24, 2025                   │
│                                                                             │
│ Customer                            Activations                             │
│ ─────────                           ───────────                             │
│ Name: John Doe                      Used: 2 / 5 sites                       │
│ Email: john@example.com                                                     │
│                                                                             │
│ Activated Sites:                                                            │
│ ┌─────────────────────────────────────────────────────────────────────┐    │
│ │ Site                          Activated        Version    [Actions] │    │
│ │ https://site1.com             Nov 20, 2025     2.5.0     [Revoke]  │    │
│ │ https://site2.com             Nov 22, 2025     2.5.0     [Revoke]  │    │
│ └─────────────────────────────────────────────────────────────────────┘    │
│                                                                             │
│ Features:                                                                   │
│ ☑ Premium Support                                                          │
│ ☑ White Label                                                              │
│ ☐ API Access (not included)                                                │
│                                                                             │
│ Actions:                                                                    │
│ [Suspend License] [Revoke License] [Extend Validity] [Email Customer]      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Success Criteria

- [ ] License keys generated with configurable prefix
- [ ] Activation/deactivation works reliably
- [ ] Subscription-license sync automatic
- [ ] API response time < 100ms (cached)
- [ ] Rate limiting prevents abuse (60 req/min)
- [ ] Admin can manage all licenses
- [ ] SDK provided for plugin developers
- [ ] Feature gating works correctly

## Security Considerations

1. **Rate Limiting**: 60 requests/minute per IP
2. **Token Verification**: Deactivation requires activation token
3. **IP Logging**: Track validation requests for abuse detection
4. **Secret Hash**: Optional license secret uses `wp_hash_password()`
5. **Domain Validation**: Normalize URLs to prevent duplicates
6. **Webhook Verification**: Payment webhooks verified before license creation
