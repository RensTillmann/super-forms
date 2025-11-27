# Phase 19a: Booking Tables & Data Access Layer

## Overview

This subtask covers the database infrastructure for the booking system, including all table creation, indexes, foreign keys, and the Data Access Layer classes.

## Prerequisites

- Phase 18 (Payment Architecture) - for payment integration
- Triggers/Actions system - for event firing

## Database Tables

### Table Creation Order (Foreign Key Dependencies)

1. `wp_superforms_booking_categories` (no dependencies)
2. `wp_superforms_booking_locations` (no dependencies)
3. `wp_superforms_booking_services` (depends on categories)
4. `wp_superforms_booking_service_extras` (depends on services)
5. `wp_superforms_booking_resources` (depends on locations)
6. `wp_superforms_booking_service_resources` (depends on services, resources)
7. `wp_superforms_booking_staff` (no dependencies)
8. `wp_superforms_booking_staff_services` (depends on staff, services)
9. `wp_superforms_booking_schedules` (depends on staff, locations)
10. `wp_superforms_booking_special_days` (depends on staff, locations)
11. `wp_superforms_booking_customers` (no dependencies)
12. `wp_superforms_bookings` (depends on services, staff, locations, customers)
13. `wp_superforms_booking_recurring` (depends on bookings)
14. `wp_superforms_booking_waitlist` (depends on services, staff, locations, customers)
15. `wp_superforms_booking_reminders` (depends on bookings)

### Installation Code

```php
// In class-install.php

/**
 * Create booking system tables
 */
private static function create_booking_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Table 1: Categories
    $table_categories = $wpdb->prefix . 'superforms_booking_categories';
    $sql_categories = "CREATE TABLE $table_categories (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        color VARCHAR(7) DEFAULT '#9b59b6',
        parent_id BIGINT(20) UNSIGNED,
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY parent_id (parent_id),
        KEY sort_order (sort_order)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_categories);

    // Table 2: Locations
    $table_locations = $wpdb->prefix . 'superforms_booking_locations';
    $sql_locations = "CREATE TABLE $table_locations (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        address_line_1 VARCHAR(255),
        address_line_2 VARCHAR(255),
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(2),
        phone VARCHAR(50),
        email VARCHAR(255),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        timezone VARCHAR(50) DEFAULT 'UTC',
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        settings JSON,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY status (status)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_locations);

    // Table 3: Services
    $table_services = $wpdb->prefix . 'superforms_booking_services';
    $sql_services = "CREATE TABLE $table_services (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        short_description VARCHAR(500),
        image_url VARCHAR(500),
        color VARCHAR(7) DEFAULT '#3498db',
        duration INT NOT NULL DEFAULT 60,
        buffer_before INT DEFAULT 0,
        buffer_after INT DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        deposit_type ENUM('none', 'fixed', 'percent') DEFAULT 'none',
        deposit_amount DECIMAL(10,2) DEFAULT 0.00,
        min_capacity INT DEFAULT 1,
        max_capacity INT DEFAULT 1,
        min_booking_notice INT DEFAULT 60,
        max_booking_window INT DEFAULT 30,
        slot_duration INT DEFAULT NULL,
        allow_recurring TINYINT(1) DEFAULT 0,
        recurring_frequencies JSON,
        max_recurring INT DEFAULT 10,
        status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
        visibility ENUM('public', 'private', 'staff_only') DEFAULT 'public',
        sort_order INT DEFAULT 0,
        category_id BIGINT(20) UNSIGNED,
        settings JSON,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY status (status),
        KEY category_id (category_id),
        KEY sort_order (sort_order)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_services);

    // Table 4: Service Extras
    $table_extras = $wpdb->prefix . 'superforms_booking_service_extras';
    $sql_extras = "CREATE TABLE $table_extras (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) DEFAULT 0.00,
        duration INT DEFAULT 0,
        max_quantity INT DEFAULT 1,
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY service_id (service_id),
        KEY status (status)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_extras);

    // Table 5: Resources
    $table_resources = $wpdb->prefix . 'superforms_booking_resources';
    $sql_resources = "CREATE TABLE $table_resources (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        location_id BIGINT(20) UNSIGNED,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        resource_type VARCHAR(50) NOT NULL,
        quantity INT DEFAULT 1,
        status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
        settings JSON,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY location_id (location_id),
        KEY resource_type (resource_type),
        KEY status (status)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_resources);

    // Table 6: Service Resources (M2M)
    $table_service_resources = $wpdb->prefix . 'superforms_booking_service_resources';
    $sql_service_resources = "CREATE TABLE $table_service_resources (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id BIGINT(20) UNSIGNED NOT NULL,
        resource_id BIGINT(20) UNSIGNED NOT NULL,
        quantity_required INT DEFAULT 1,
        is_required TINYINT(1) DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY service_resource (service_id, resource_id),
        KEY resource_id (resource_id)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_service_resources);

    // Table 7: Staff
    $table_staff = $wpdb->prefix . 'superforms_booking_staff';
    $sql_staff = "CREATE TABLE $table_staff (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        bio TEXT,
        avatar_url VARCHAR(500),
        color VARCHAR(7) DEFAULT '#2ecc71',
        status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
        visibility ENUM('public', 'hidden') DEFAULT 'public',
        sort_order INT DEFAULT 0,
        google_calendar_id VARCHAR(255),
        google_calendar_token JSON,
        outlook_calendar_id VARCHAR(255),
        outlook_calendar_token JSON,
        sync_mode ENUM('none', 'one_way', 'two_way') DEFAULT 'none',
        email_notifications TINYINT(1) DEFAULT 1,
        sms_notifications TINYINT(1) DEFAULT 0,
        sms_phone VARCHAR(50),
        price_modifier_type ENUM('none', 'fixed', 'percent') DEFAULT 'none',
        price_modifier_value DECIMAL(10,2) DEFAULT 0.00,
        settings JSON,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY email (email)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_staff);

    // Table 8: Staff Services (M2M)
    $table_staff_services = $wpdb->prefix . 'superforms_booking_staff_services';
    $sql_staff_services = "CREATE TABLE $table_staff_services (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        staff_id BIGINT(20) UNSIGNED NOT NULL,
        service_id BIGINT(20) UNSIGNED NOT NULL,
        custom_duration INT,
        custom_price DECIMAL(10,2),
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY staff_service (staff_id, service_id),
        KEY service_id (service_id)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_staff_services);

    // Table 9: Schedules
    $table_schedules = $wpdb->prefix . 'superforms_booking_schedules';
    $sql_schedules = "CREATE TABLE $table_schedules (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        staff_id BIGINT(20) UNSIGNED NOT NULL,
        location_id BIGINT(20) UNSIGNED,
        day_of_week TINYINT NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        break_start TIME,
        break_end TIME,
        is_active TINYINT(1) DEFAULT 1,
        PRIMARY KEY (id),
        KEY staff_id (staff_id),
        KEY location_id (location_id),
        KEY day_of_week (day_of_week)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_schedules);

    // Table 10: Special Days
    $table_special_days = $wpdb->prefix . 'superforms_booking_special_days';
    $sql_special_days = "CREATE TABLE $table_special_days (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        staff_id BIGINT(20) UNSIGNED,
        location_id BIGINT(20) UNSIGNED,
        date DATE NOT NULL,
        type ENUM('day_off', 'custom_hours', 'holiday') NOT NULL,
        start_time TIME,
        end_time TIME,
        reason VARCHAR(255),
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY staff_id (staff_id),
        KEY date (date),
        KEY type (type)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_special_days);

    // Table 11: Customers
    $table_customers = $wpdb->prefix . 'superforms_booking_customers';
    $sql_customers = "CREATE TABLE $table_customers (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100),
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        address_line_1 VARCHAR(255),
        address_line_2 VARCHAR(255),
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(2),
        preferred_staff_id BIGINT(20) UNSIGNED,
        preferred_location_id BIGINT(20) UNSIGNED,
        preferred_notification ENUM('email', 'sms', 'both') DEFAULT 'email',
        timezone VARCHAR(50),
        language VARCHAR(10) DEFAULT 'en',
        total_bookings INT DEFAULT 0,
        no_shows INT DEFAULT 0,
        cancellations INT DEFAULT 0,
        total_spent DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('active', 'blocked') DEFAULT 'active',
        notes TEXT,
        marketing_consent TINYINT(1) DEFAULT 0,
        consent_date DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        UNIQUE KEY email (email),
        KEY phone (phone),
        KEY status (status)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_customers);

    // Table 12: Bookings (Main Table)
    $table_bookings = $wpdb->prefix . 'superforms_bookings';
    $sql_bookings = "CREATE TABLE $table_bookings (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_uid VARCHAR(32) NOT NULL,
        form_id BIGINT(20) UNSIGNED,
        entry_id BIGINT(20) UNSIGNED,
        service_id BIGINT(20) UNSIGNED NOT NULL,
        staff_id BIGINT(20) UNSIGNED NOT NULL,
        location_id BIGINT(20) UNSIGNED,
        customer_id BIGINT(20) UNSIGNED,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50),
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        timezone VARCHAR(50) NOT NULL,
        duration INT NOT NULL,
        buffer_before INT DEFAULT 0,
        buffer_after INT DEFAULT 0,
        attendees INT DEFAULT 1,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show', 'rescheduled') DEFAULT 'pending',
        payment_status ENUM('pending', 'deposit_paid', 'paid', 'refunded', 'partial_refund') DEFAULT 'pending',
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        deposit_amount DECIMAL(10,2) DEFAULT 0.00,
        paid_amount DECIMAL(10,2) DEFAULT 0.00,
        currency VARCHAR(3) DEFAULT 'USD',
        payment_id BIGINT(20) UNSIGNED,
        extras JSON,
        is_recurring TINYINT(1) DEFAULT 0,
        recurring_id BIGINT(20) UNSIGNED,
        rescheduled_from_id BIGINT(20) UNSIGNED,
        rescheduled_to_id BIGINT(20) UNSIGNED,
        meeting_type ENUM('in_person', 'zoom', 'google_meet', 'teams', 'phone', 'custom') DEFAULT 'in_person',
        meeting_url VARCHAR(500),
        meeting_data JSON,
        customer_notes TEXT,
        admin_notes TEXT,
        internal_notes TEXT,
        resources JSON,
        cancelled_at DATETIME,
        cancelled_by ENUM('customer', 'staff', 'admin', 'system'),
        cancellation_reason TEXT,
        source ENUM('form', 'admin', 'api', 'import') DEFAULT 'form',
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY booking_uid (booking_uid),
        KEY form_id (form_id),
        KEY entry_id (entry_id),
        KEY service_id (service_id),
        KEY staff_id (staff_id),
        KEY location_id (location_id),
        KEY customer_id (customer_id),
        KEY customer_email (customer_email),
        KEY start_datetime (start_datetime),
        KEY status (status),
        KEY payment_status (payment_status),
        KEY recurring_id (recurring_id),
        KEY staff_datetime (staff_id, start_datetime, end_datetime),
        KEY location_datetime (location_id, start_datetime, end_datetime)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_bookings);

    // Table 13: Recurring
    $table_recurring = $wpdb->prefix . 'superforms_booking_recurring';
    $sql_recurring = "CREATE TABLE $table_recurring (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        frequency ENUM('daily', 'weekly', 'biweekly', 'monthly') NOT NULL,
        interval_value INT DEFAULT 1,
        day_of_week TINYINT,
        day_of_month TINYINT,
        start_date DATE NOT NULL,
        end_date DATE,
        total_occurrences INT,
        remaining_occurrences INT,
        status ENUM('active', 'paused', 'cancelled', 'completed') DEFAULT 'active',
        source_booking_id BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY source_booking_id (source_booking_id),
        KEY status (status)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_recurring);

    // Table 14: Waitlist
    $table_waitlist = $wpdb->prefix . 'superforms_booking_waitlist';
    $sql_waitlist = "CREATE TABLE $table_waitlist (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id BIGINT(20) UNSIGNED NOT NULL,
        staff_id BIGINT(20) UNSIGNED,
        location_id BIGINT(20) UNSIGNED,
        preferred_date DATE NOT NULL,
        preferred_time_start TIME,
        preferred_time_end TIME,
        flexible_date TINYINT(1) DEFAULT 0,
        flexible_days INT DEFAULT 3,
        flexible_staff TINYINT(1) DEFAULT 0,
        customer_id BIGINT(20) UNSIGNED,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50),
        status ENUM('waiting', 'notified', 'booked', 'expired', 'cancelled') DEFAULT 'waiting',
        notified_at DATETIME,
        notified_count INT DEFAULT 0,
        expires_at DATETIME,
        notes TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY service_id (service_id),
        KEY staff_id (staff_id),
        KEY preferred_date (preferred_date),
        KEY status (status),
        KEY customer_email (customer_email)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_waitlist);

    // Table 15: Reminders
    $table_reminders = $wpdb->prefix . 'superforms_booking_reminders';
    $sql_reminders = "CREATE TABLE $table_reminders (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_id BIGINT(20) UNSIGNED NOT NULL,
        reminder_type ENUM('before', 'after') DEFAULT 'before',
        time_offset INT NOT NULL,
        channel ENUM('email', 'sms', 'both') DEFAULT 'email',
        recipient_type ENUM('customer', 'staff', 'both') DEFAULT 'customer',
        status ENUM('scheduled', 'sent', 'failed', 'cancelled') DEFAULT 'scheduled',
        scheduled_for DATETIME NOT NULL,
        sent_at DATETIME,
        error_message TEXT,
        retry_count INT DEFAULT 0,
        action_scheduler_id BIGINT(20) UNSIGNED,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY booking_id (booking_id),
        KEY scheduled_for (scheduled_for),
        KEY status (status)
    ) ENGINE=InnoDB $charset_collate;";
    dbDelta($sql_reminders);
}
```

## Data Access Layer Classes

### File Structure

```
/src/includes/booking/
├── class-booking-service-dal.php
├── class-booking-staff-dal.php
├── class-booking-location-dal.php
├── class-booking-resource-dal.php
├── class-booking-customer-dal.php
├── class-booking-dal.php
├── class-booking-waitlist-dal.php
├── class-booking-recurring-dal.php
├── class-booking-reminder-dal.php
├── class-booking-availability-engine.php
└── class-booking-manager.php
```

### SUPER_Booking_Service_DAL

```php
<?php
/**
 * Booking Service Data Access Layer
 *
 * @package Super_Forms
 * @since   6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Booking_Service_DAL {

    /**
     * Table name
     */
    private static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'superforms_booking_services';
    }

    /**
     * Extras table name
     */
    private static function extras_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'superforms_booking_service_extras';
    }

    /**
     * Create a new service
     */
    public static function create(array $data): int|WP_Error {
        global $wpdb;

        // Validate required fields
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Service name is required', 'super-forms'));
        }

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }

        // Check for duplicate slug
        $existing = self::get_by_slug($data['slug']);
        if ($existing) {
            $data['slug'] = $data['slug'] . '-' . wp_generate_password(4, false, false);
        }

        // Set defaults
        $defaults = [
            'duration' => 60,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'price' => 0.00,
            'deposit_type' => 'none',
            'deposit_amount' => 0.00,
            'min_capacity' => 1,
            'max_capacity' => 1,
            'min_booking_notice' => 60,
            'max_booking_window' => 30,
            'allow_recurring' => 0,
            'max_recurring' => 10,
            'status' => 'active',
            'visibility' => 'public',
            'sort_order' => 0,
        ];

        $data = wp_parse_args($data, $defaults);
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        // JSON encode arrays
        if (isset($data['recurring_frequencies']) && is_array($data['recurring_frequencies'])) {
            $data['recurring_frequencies'] = wp_json_encode($data['recurring_frequencies']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = wp_json_encode($data['settings']);
        }

        $result = $wpdb->insert(self::table_name(), $data);

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        $service_id = $wpdb->insert_id;

        // Fire event
        do_action('super_booking_service_created', $service_id, $data);

        return $service_id;
    }

    /**
     * Get service by ID
     */
    public static function get(int $id): ?object {
        global $wpdb;

        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            self::table_name(),
            $id
        ));

        if ($service) {
            $service = self::decode_json_fields($service);
        }

        return $service;
    }

    /**
     * Get service by slug
     */
    public static function get_by_slug(string $slug): ?object {
        global $wpdb;

        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE slug = %s",
            self::table_name(),
            $slug
        ));

        if ($service) {
            $service = self::decode_json_fields($service);
        }

        return $service;
    }

    /**
     * Update service
     */
    public static function update(int $id, array $data): bool|WP_Error {
        global $wpdb;

        $existing = self::get($id);
        if (!$existing) {
            return new WP_Error('not_found', __('Service not found', 'super-forms'));
        }

        $data['updated_at'] = current_time('mysql');

        // JSON encode arrays
        if (isset($data['recurring_frequencies']) && is_array($data['recurring_frequencies'])) {
            $data['recurring_frequencies'] = wp_json_encode($data['recurring_frequencies']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = wp_json_encode($data['settings']);
        }

        $result = $wpdb->update(
            self::table_name(),
            $data,
            ['id' => $id]
        );

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        do_action('super_booking_service_updated', $id, $data);

        return true;
    }

    /**
     * Delete service
     */
    public static function delete(int $id): bool {
        global $wpdb;

        // Check for existing bookings
        $bookings_table = $wpdb->prefix . 'superforms_bookings';
        $has_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE service_id = %d",
            $bookings_table,
            $id
        ));

        if ($has_bookings > 0) {
            // Soft delete - archive instead
            return self::update($id, ['status' => 'archived']) === true;
        }

        // Hard delete
        $result = $wpdb->delete(self::table_name(), ['id' => $id]);

        if ($result) {
            // Delete related extras
            $wpdb->delete(self::extras_table(), ['service_id' => $id]);

            do_action('super_booking_service_deleted', $id);
        }

        return $result !== false;
    }

    /**
     * Get all services
     */
    public static function get_all(array $args = []): array {
        global $wpdb;

        $defaults = [
            'status' => null,
            'visibility' => null,
            'category_id' => null,
            'orderby' => 'sort_order',
            'order' => 'ASC',
            'limit' => 100,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $values = [];

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['visibility']) {
            $where[] = 'visibility = %s';
            $values[] = $args['visibility'];
        }

        if ($args['category_id']) {
            $where[] = 'category_id = %d';
            $values[] = $args['category_id'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'sort_order ASC';

        $sql = "SELECT * FROM " . self::table_name() . " $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        $services = $wpdb->get_results($sql);

        return array_map([self::class, 'decode_json_fields'], $services);
    }

    /**
     * Get active services (public)
     */
    public static function get_active(): array {
        return self::get_all([
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    /**
     * Get services by category
     */
    public static function get_by_category(int $category_id): array {
        return self::get_all(['category_id' => $category_id]);
    }

    /**
     * Get services available for a staff member
     */
    public static function get_by_staff(int $staff_id): array {
        global $wpdb;

        $services_table = self::table_name();
        $staff_services_table = $wpdb->prefix . 'superforms_booking_staff_services';

        $sql = $wpdb->prepare(
            "SELECT s.*, ss.custom_duration, ss.custom_price
             FROM %i s
             INNER JOIN %i ss ON s.id = ss.service_id
             WHERE ss.staff_id = %d AND s.status = 'active'
             ORDER BY s.sort_order ASC",
            $services_table,
            $staff_services_table,
            $staff_id
        );

        $services = $wpdb->get_results($sql);

        return array_map([self::class, 'decode_json_fields'], $services);
    }

    /**
     * Get service extras
     */
    public static function get_extras(int $service_id): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i WHERE service_id = %d AND status = 'active' ORDER BY sort_order ASC",
            self::extras_table(),
            $service_id
        ));
    }

    /**
     * Add service extra
     */
    public static function add_extra(int $service_id, array $data): int|WP_Error {
        global $wpdb;

        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Extra name is required', 'super-forms'));
        }

        $data['service_id'] = $service_id;
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        $defaults = [
            'price' => 0.00,
            'duration' => 0,
            'max_quantity' => 1,
            'status' => 'active',
            'sort_order' => 0,
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(self::extras_table(), $data);

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Update service extra
     */
    public static function update_extra(int $extra_id, array $data): bool {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            self::extras_table(),
            $data,
            ['id' => $extra_id]
        );

        return $result !== false;
    }

    /**
     * Delete service extra
     */
    public static function delete_extra(int $extra_id): bool {
        global $wpdb;

        return $wpdb->delete(self::extras_table(), ['id' => $extra_id]) !== false;
    }

    /**
     * Calculate total price for service + extras
     */
    public static function calculate_price(int $service_id, array $extras = [], int $attendees = 1, ?int $staff_id = null): array {
        $service = self::get($service_id);
        if (!$service) {
            return ['error' => 'Service not found'];
        }

        $base_price = (float) $service->price;

        // Check for staff custom price
        if ($staff_id) {
            global $wpdb;
            $custom = $wpdb->get_row($wpdb->prepare(
                "SELECT custom_price FROM %i WHERE staff_id = %d AND service_id = %d",
                $wpdb->prefix . 'superforms_booking_staff_services',
                $staff_id,
                $service_id
            ));
            if ($custom && $custom->custom_price !== null) {
                $base_price = (float) $custom->custom_price;
            }
        }

        // Calculate extras
        $extras_total = 0;
        $extras_duration = 0;
        $service_extras = self::get_extras($service_id);
        $extras_details = [];

        foreach ($extras as $extra_id => $quantity) {
            foreach ($service_extras as $extra) {
                if ($extra->id == $extra_id) {
                    $qty = min((int) $quantity, (int) $extra->max_quantity);
                    $extras_total += (float) $extra->price * $qty;
                    $extras_duration += (int) $extra->duration * $qty;
                    $extras_details[] = [
                        'id' => $extra->id,
                        'name' => $extra->name,
                        'price' => $extra->price,
                        'quantity' => $qty,
                        'subtotal' => (float) $extra->price * $qty,
                    ];
                    break;
                }
            }
        }

        // Calculate totals
        $subtotal = ($base_price * $attendees) + $extras_total;
        $deposit = 0;

        if ($service->deposit_type === 'fixed') {
            $deposit = (float) $service->deposit_amount;
        } elseif ($service->deposit_type === 'percent') {
            $deposit = $subtotal * ((float) $service->deposit_amount / 100);
        }

        return [
            'service_price' => $base_price,
            'attendees' => $attendees,
            'extras' => $extras_details,
            'extras_total' => $extras_total,
            'extras_duration' => $extras_duration,
            'subtotal' => $subtotal,
            'deposit_type' => $service->deposit_type,
            'deposit_amount' => $deposit,
            'total_duration' => (int) $service->duration + $extras_duration,
        ];
    }

    /**
     * Decode JSON fields
     */
    private static function decode_json_fields(object $service): object {
        if (!empty($service->recurring_frequencies)) {
            $service->recurring_frequencies = json_decode($service->recurring_frequencies, true);
        }
        if (!empty($service->settings)) {
            $service->settings = json_decode($service->settings, true);
        }
        return $service;
    }
}
```

### SUPER_Booking_DAL (Main Bookings)

```php
<?php
/**
 * Booking Data Access Layer
 *
 * @package Super_Forms
 * @since   6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Booking_DAL {

    /**
     * Table name
     */
    private static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'superforms_bookings';
    }

    /**
     * Generate unique booking UID
     */
    private static function generate_uid(): string {
        return 'BK-' . strtoupper(wp_generate_password(6, false, false));
    }

    /**
     * Create a new booking
     */
    public static function create(array $data): int|WP_Error {
        global $wpdb;

        // Validate required fields
        $required = ['service_id', 'staff_id', 'customer_name', 'customer_email', 'start_datetime', 'timezone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field %s is required', 'super-forms'), $field));
            }
        }

        // Validate email
        if (!is_email($data['customer_email'])) {
            return new WP_Error('invalid_email', __('Invalid email address', 'super-forms'));
        }

        // Get service for duration and pricing
        $service = SUPER_Booking_Service_DAL::get($data['service_id']);
        if (!$service) {
            return new WP_Error('invalid_service', __('Service not found', 'super-forms'));
        }

        // Calculate end time if not provided
        $duration = $data['duration'] ?? $service->duration;
        if (empty($data['end_datetime'])) {
            $start = new DateTime($data['start_datetime']);
            $start->modify("+{$duration} minutes");
            $data['end_datetime'] = $start->format('Y-m-d H:i:s');
        }

        // Check availability
        $is_available = SUPER_Booking_Availability_Engine::is_slot_bookable(
            $data['service_id'],
            $data['staff_id'],
            $data['start_datetime'],
            $data['attendees'] ?? 1,
            $data['location_id'] ?? null
        );

        if (is_wp_error($is_available)) {
            return $is_available;
        }

        if (!$is_available) {
            return new WP_Error('slot_unavailable', __('This time slot is no longer available', 'super-forms'));
        }

        // Generate UID
        $data['booking_uid'] = self::generate_uid();

        // Set defaults
        $defaults = [
            'duration' => $duration,
            'buffer_before' => $service->buffer_before,
            'buffer_after' => $service->buffer_after,
            'attendees' => 1,
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => 0.00,
            'deposit_amount' => 0.00,
            'paid_amount' => 0.00,
            'currency' => 'USD',
            'meeting_type' => 'in_person',
            'source' => 'form',
            'is_recurring' => 0,
        ];

        $data = wp_parse_args($data, $defaults);
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        // JSON encode arrays
        if (isset($data['extras']) && is_array($data['extras'])) {
            $data['extras'] = wp_json_encode($data['extras']);
        }
        if (isset($data['resources']) && is_array($data['resources'])) {
            $data['resources'] = wp_json_encode($data['resources']);
        }
        if (isset($data['meeting_data']) && is_array($data['meeting_data'])) {
            $data['meeting_data'] = wp_json_encode($data['meeting_data']);
        }

        $result = $wpdb->insert(self::table_name(), $data);

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        $booking_id = $wpdb->insert_id;

        // Update customer stats
        if (!empty($data['customer_id'])) {
            SUPER_Booking_Customer_DAL::increment_bookings($data['customer_id']);
        }

        // Fire trigger event
        $booking = self::get($booking_id);
        SUPER_Trigger_Registry::get_instance()->fire_event('booking.created', [
            'booking_id' => $booking_id,
            'booking_uid' => $data['booking_uid'],
            'service_id' => $data['service_id'],
            'staff_id' => $data['staff_id'],
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'start_datetime' => $data['start_datetime'],
            'end_datetime' => $data['end_datetime'],
            'total_amount' => $data['total_amount'],
            'booking' => $booking,
        ]);

        return $booking_id;
    }

    /**
     * Get booking by ID
     */
    public static function get(int $id): ?object {
        global $wpdb;

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            self::table_name(),
            $id
        ));

        if ($booking) {
            $booking = self::decode_json_fields($booking);
            $booking = self::enrich_booking($booking);
        }

        return $booking;
    }

    /**
     * Get booking by UID
     */
    public static function get_by_uid(string $uid): ?object {
        global $wpdb;

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE booking_uid = %s",
            self::table_name(),
            $uid
        ));

        if ($booking) {
            $booking = self::decode_json_fields($booking);
            $booking = self::enrich_booking($booking);
        }

        return $booking;
    }

    /**
     * Update booking
     */
    public static function update(int $id, array $data): bool|WP_Error {
        global $wpdb;

        $existing = self::get($id);
        if (!$existing) {
            return new WP_Error('not_found', __('Booking not found', 'super-forms'));
        }

        $data['updated_at'] = current_time('mysql');

        // JSON encode arrays
        if (isset($data['extras']) && is_array($data['extras'])) {
            $data['extras'] = wp_json_encode($data['extras']);
        }
        if (isset($data['resources']) && is_array($data['resources'])) {
            $data['resources'] = wp_json_encode($data['resources']);
        }
        if (isset($data['meeting_data']) && is_array($data['meeting_data'])) {
            $data['meeting_data'] = wp_json_encode($data['meeting_data']);
        }

        $result = $wpdb->update(
            self::table_name(),
            $data,
            ['id' => $id]
        );

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        do_action('super_booking_updated', $id, $data);

        return true;
    }

    /**
     * Confirm booking
     */
    public static function confirm(int $id): bool|WP_Error {
        $booking = self::get($id);
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'super-forms'));
        }

        if ($booking->status !== 'pending') {
            return new WP_Error('invalid_status', __('Only pending bookings can be confirmed', 'super-forms'));
        }

        $result = self::update($id, ['status' => 'confirmed']);

        if ($result === true) {
            SUPER_Trigger_Registry::get_instance()->fire_event('booking.confirmed', [
                'booking_id' => $id,
                'booking_uid' => $booking->booking_uid,
                'customer_email' => $booking->customer_email,
                'booking' => $booking,
            ]);

            // Schedule reminders
            SUPER_Booking_Reminder_DAL::schedule_default_reminders($id);
        }

        return $result;
    }

    /**
     * Cancel booking
     */
    public static function cancel(int $id, string $cancelled_by, ?string $reason = null): bool|WP_Error {
        $booking = self::get($id);
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'super-forms'));
        }

        if (in_array($booking->status, ['cancelled', 'completed', 'no_show'])) {
            return new WP_Error('invalid_status', __('This booking cannot be cancelled', 'super-forms'));
        }

        $result = self::update($id, [
            'status' => 'cancelled',
            'cancelled_at' => current_time('mysql'),
            'cancelled_by' => $cancelled_by,
            'cancellation_reason' => $reason,
        ]);

        if ($result === true) {
            // Update customer stats
            if ($booking->customer_id) {
                SUPER_Booking_Customer_DAL::increment_cancellations($booking->customer_id);
            }

            // Cancel scheduled reminders
            SUPER_Booking_Reminder_DAL::cancel_for_booking($id);

            // Fire trigger
            SUPER_Trigger_Registry::get_instance()->fire_event('booking.cancelled', [
                'booking_id' => $id,
                'booking_uid' => $booking->booking_uid,
                'customer_email' => $booking->customer_email,
                'cancelled_by' => $cancelled_by,
                'reason' => $reason,
                'booking' => $booking,
            ]);

            // Check waitlist
            SUPER_Booking_Waitlist_DAL::notify_for_cancelled_booking($booking);
        }

        return $result;
    }

    /**
     * Complete booking
     */
    public static function complete(int $id): bool|WP_Error {
        $booking = self::get($id);
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'super-forms'));
        }

        $result = self::update($id, ['status' => 'completed']);

        if ($result === true) {
            SUPER_Trigger_Registry::get_instance()->fire_event('booking.completed', [
                'booking_id' => $id,
                'booking_uid' => $booking->booking_uid,
                'customer_email' => $booking->customer_email,
                'booking' => $booking,
            ]);
        }

        return $result;
    }

    /**
     * Mark as no-show
     */
    public static function mark_no_show(int $id): bool|WP_Error {
        $booking = self::get($id);
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'super-forms'));
        }

        $result = self::update($id, ['status' => 'no_show']);

        if ($result === true) {
            // Update customer stats
            if ($booking->customer_id) {
                SUPER_Booking_Customer_DAL::increment_no_shows($booking->customer_id);
            }

            SUPER_Trigger_Registry::get_instance()->fire_event('booking.no_show', [
                'booking_id' => $id,
                'booking_uid' => $booking->booking_uid,
                'customer_email' => $booking->customer_email,
                'booking' => $booking,
            ]);
        }

        return $result;
    }

    /**
     * Reschedule booking
     */
    public static function reschedule(int $id, string $new_datetime): int|WP_Error {
        $booking = self::get($id);
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'super-forms'));
        }

        // Check new slot availability
        $is_available = SUPER_Booking_Availability_Engine::is_slot_bookable(
            $booking->service_id,
            $booking->staff_id,
            $new_datetime,
            $booking->attendees,
            $booking->location_id,
            $id // Exclude current booking
        );

        if (is_wp_error($is_available)) {
            return $is_available;
        }

        if (!$is_available) {
            return new WP_Error('slot_unavailable', __('The new time slot is not available', 'super-forms'));
        }

        // Calculate new end time
        $start = new DateTime($new_datetime);
        $start->modify("+{$booking->duration} minutes");
        $new_end_datetime = $start->format('Y-m-d H:i:s');

        // Mark old booking as rescheduled
        self::update($id, ['status' => 'rescheduled']);

        // Create new booking
        $new_booking_data = [
            'service_id' => $booking->service_id,
            'staff_id' => $booking->staff_id,
            'location_id' => $booking->location_id,
            'customer_id' => $booking->customer_id,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'start_datetime' => $new_datetime,
            'end_datetime' => $new_end_datetime,
            'timezone' => $booking->timezone,
            'duration' => $booking->duration,
            'buffer_before' => $booking->buffer_before,
            'buffer_after' => $booking->buffer_after,
            'attendees' => $booking->attendees,
            'status' => 'confirmed',
            'payment_status' => $booking->payment_status,
            'total_amount' => $booking->total_amount,
            'deposit_amount' => $booking->deposit_amount,
            'paid_amount' => $booking->paid_amount,
            'currency' => $booking->currency,
            'payment_id' => $booking->payment_id,
            'extras' => $booking->extras,
            'rescheduled_from_id' => $id,
            'source' => 'form',
        ];

        $new_booking_id = self::create($new_booking_data);

        if (is_wp_error($new_booking_id)) {
            // Rollback
            self::update($id, ['status' => 'confirmed']);
            return $new_booking_id;
        }

        // Update old booking with reference to new one
        self::update($id, ['rescheduled_to_id' => $new_booking_id]);

        // Cancel old reminders, schedule new ones
        SUPER_Booking_Reminder_DAL::cancel_for_booking($id);
        SUPER_Booking_Reminder_DAL::schedule_default_reminders($new_booking_id);

        // Fire trigger
        SUPER_Trigger_Registry::get_instance()->fire_event('booking.rescheduled', [
            'booking_id' => $new_booking_id,
            'original_booking_id' => $id,
            'old_datetime' => $booking->start_datetime,
            'new_datetime' => $new_datetime,
            'customer_email' => $booking->customer_email,
            'booking' => self::get($new_booking_id),
        ]);

        return $new_booking_id;
    }

    /**
     * Get bookings by customer
     */
    public static function get_by_customer(int $customer_id, array $args = []): array {
        $args['customer_id'] = $customer_id;
        return self::get_all($args);
    }

    /**
     * Get bookings by customer email
     */
    public static function get_by_customer_email(string $email, array $args = []): array {
        $args['customer_email'] = $email;
        return self::get_all($args);
    }

    /**
     * Get bookings by staff
     */
    public static function get_by_staff(int $staff_id, array $args = []): array {
        $args['staff_id'] = $staff_id;
        return self::get_all($args);
    }

    /**
     * Get bookings by date range
     */
    public static function get_by_date_range(string $start, string $end, array $args = []): array {
        $args['date_from'] = $start;
        $args['date_to'] = $end;
        return self::get_all($args);
    }

    /**
     * Get all bookings with filters
     */
    public static function get_all(array $args = []): array {
        global $wpdb;

        $defaults = [
            'status' => null,
            'payment_status' => null,
            'service_id' => null,
            'staff_id' => null,
            'location_id' => null,
            'customer_id' => null,
            'customer_email' => null,
            'date_from' => null,
            'date_to' => null,
            'orderby' => 'start_datetime',
            'order' => 'ASC',
            'limit' => 100,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $values = [];

        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $values = array_merge($values, $args['status']);
            } else {
                $where[] = 'status = %s';
                $values[] = $args['status'];
            }
        }

        if ($args['payment_status']) {
            $where[] = 'payment_status = %s';
            $values[] = $args['payment_status'];
        }

        if ($args['service_id']) {
            $where[] = 'service_id = %d';
            $values[] = $args['service_id'];
        }

        if ($args['staff_id']) {
            $where[] = 'staff_id = %d';
            $values[] = $args['staff_id'];
        }

        if ($args['location_id']) {
            $where[] = 'location_id = %d';
            $values[] = $args['location_id'];
        }

        if ($args['customer_id']) {
            $where[] = 'customer_id = %d';
            $values[] = $args['customer_id'];
        }

        if ($args['customer_email']) {
            $where[] = 'customer_email = %s';
            $values[] = $args['customer_email'];
        }

        if ($args['date_from']) {
            $where[] = 'start_datetime >= %s';
            $values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'start_datetime <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'start_datetime ASC';

        $sql = "SELECT * FROM " . self::table_name() . " $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        $bookings = $wpdb->get_results($sql);

        return array_map(function($booking) {
            $booking = self::decode_json_fields($booking);
            return self::enrich_booking($booking);
        }, $bookings);
    }

    /**
     * Check if slot is available
     */
    public static function is_slot_available(int $staff_id, string $datetime, int $duration, ?int $exclude_booking_id = null): bool {
        global $wpdb;

        $start = new DateTime($datetime);
        $end = clone $start;
        $end->modify("+{$duration} minutes");

        $exclude_clause = $exclude_booking_id ? $wpdb->prepare(' AND id != %d', $exclude_booking_id) : '';

        $conflicts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM %i
             WHERE staff_id = %d
             AND status IN ('pending', 'confirmed')
             AND (
                 (start_datetime <= %s AND end_datetime > %s)
                 OR (start_datetime < %s AND end_datetime >= %s)
                 OR (start_datetime >= %s AND end_datetime <= %s)
             )
             $exclude_clause",
            self::table_name(),
            $staff_id,
            $start->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        ));

        return (int) $conflicts === 0;
    }

    /**
     * Decode JSON fields
     */
    private static function decode_json_fields(object $booking): object {
        if (!empty($booking->extras)) {
            $booking->extras = json_decode($booking->extras, true);
        }
        if (!empty($booking->resources)) {
            $booking->resources = json_decode($booking->resources, true);
        }
        if (!empty($booking->meeting_data)) {
            $booking->meeting_data = json_decode($booking->meeting_data, true);
        }
        return $booking;
    }

    /**
     * Enrich booking with related data
     */
    private static function enrich_booking(object $booking): object {
        $booking->service = SUPER_Booking_Service_DAL::get($booking->service_id);
        $booking->staff = SUPER_Booking_Staff_DAL::get($booking->staff_id);
        if ($booking->location_id) {
            $booking->location = SUPER_Booking_Location_DAL::get($booking->location_id);
        }
        return $booking;
    }
}
```

## Success Criteria

- [ ] All 15 database tables created successfully
- [ ] Foreign key relationships verified
- [ ] Indexes optimized for common queries
- [ ] SUPER_Booking_Service_DAL complete with all methods
- [ ] SUPER_Booking_Staff_DAL complete with schedule management
- [ ] SUPER_Booking_DAL complete with status management
- [ ] SUPER_Booking_Customer_DAL complete with statistics
- [ ] SUPER_Booking_Availability_Engine implemented
- [ ] Unit tests for all DAL operations
- [ ] Events fire correctly (booking.created, booking.confirmed, etc.)
- [ ] Integration with Phase 18 payment tables
