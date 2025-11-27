# Phase 19: Appointments & Booking System Add-on

## Overview

A comprehensive appointment and booking system that integrates with Super Forms' form builder and triggers/actions system. This add-on enables service-based businesses (dentists, salons, consultants, fitness centers, clinics, tutors, etc.) to accept online bookings directly through their WordPress site.

## Market Research Summary

### Competitive Analysis

| Plugin | Key Strengths | Price | Limitations |
|--------|--------------|-------|-------------|
| **Amelia** | Beautiful UI, employee management, events, 80K+ customers | $49-$99/yr | Complex pricing tiers |
| **Bookly** | Highly customizable forms, 40+ languages, extensive add-ons | $89+ | Many features require paid add-ons |
| **LatePoint** | User-friendly dashboard, resources management | $49/yr | Newer, smaller ecosystem |
| **BookingPress** | 15+ payment gateways, group bookings, recurring | $79/yr | Some features behind paywall |
| **Simply Schedule Appointments** | Buffer time controls, form builder integration | $99/yr | Less suitable for multi-staff |
| **WooCommerce Bookings** | Deep WooCommerce integration | $249/yr | Requires WooCommerce |

### Essential Features (Must Have)

Based on research across all major competitors:

1. **Service & Staff Management**
   - Multiple services with different durations/prices
   - Staff members with individual schedules
   - Service-to-staff assignment
   - Staff capacity limits

2. **Availability & Scheduling**
   - Working hours per staff/location
   - Buffer time between appointments
   - Minimum booking notice (advance booking)
   - Maximum advance booking window
   - Break times and lunch hours
   - Special days (holidays, custom hours)

3. **Calendar Integration**
   - Google Calendar 2-way sync
   - Outlook/iCal sync
   - Internal booking calendar view
   - Prevent double-booking

4. **Booking Flow**
   - Service selection
   - Staff selection (optional)
   - Date/time picker
   - Customer information form
   - Confirmation step

5. **Payments & Deposits**
   - Full payment at booking
   - Deposit payment (% or fixed)
   - Pay on arrival option
   - Multiple payment gateways
   - Refund handling

6. **Notifications & Reminders**
   - Booking confirmation (email/SMS)
   - Appointment reminders (24h, 1h before)
   - Cancellation notifications
   - Rescheduling notifications
   - Staff notifications

7. **Customer Management**
   - Customer database
   - Booking history
   - Customer self-service (view/cancel/reschedule)
   - Guest booking (no account required)

### Advanced Features (Differentiators)

1. **Resource Management** (LatePoint specialty)
   - Rooms, equipment, vehicles as bookable resources
   - Resource-service assignment
   - Prevent double-booking of resources

2. **Group Bookings** (BookingPress specialty)
   - Multiple attendees per slot
   - Capacity limits
   - Group pricing

3. **Recurring Appointments** (BookingPress specialty)
   - Daily, weekly, monthly recurrence
   - Series management
   - Bulk cancellation

4. **Waitlist Management** (Dental software specialty)
   - Join waitlist when fully booked
   - Auto-notify when slot opens
   - One-click booking from waitlist

5. **Service Extras/Add-ons** (Amelia specialty)
   - Upsell additional services
   - Extra time options
   - Product add-ons

6. **Multi-Location Support** (Amelia/LatePoint)
   - Different locations with different staff
   - Location-specific services
   - Location-based availability

7. **Virtual Meetings** (LatePoint/Amelia)
   - Zoom integration
   - Google Meet integration
   - Auto-create meeting links

8. **Analytics & Reports**
   - Booking statistics
   - Revenue tracking
   - No-show rates
   - Peak time analysis
   - Staff performance

## Architecture Design

### Integration with Super Forms

The booking system integrates with Super Forms at multiple levels:

1. **Form Builder Integration**
   - "Booking Calendar" field element in form builder
   - Configurable: service selection, staff selection, date picker
   - Supports conditional logic (show/hide based on selections)

2. **Triggers/Actions Integration**
   - Events: `booking.created`, `booking.confirmed`, `booking.cancelled`, etc.
   - Actions: `send_booking_confirmation`, `create_calendar_event`, etc.

3. **Payment Integration**
   - Uses Phase 18 payment tables for booking payments
   - `resource_type = 'booking'`, `resource_id = booking_id`

4. **Entry Integration**
   - Booking creates a contact entry with booking metadata
   - Entry links to booking record

### Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ FORM CONFIGURATION (Admin)                                       │
├─────────────────────────────────────────────────────────────────┤
│ 1. Admin creates services (duration, price, capacity)            │
│ 2. Admin creates staff members (schedule, services offered)      │
│ 3. Admin creates locations (optional)                            │
│ 4. Admin creates resources (rooms, equipment - optional)         │
│ 5. Admin adds "Booking Calendar" field to form                   │
│ 6. Admin configures booking settings (buffer, notice, etc.)      │
└─────────────────────────────────────────────────────────────────┘
                               ↓
┌─────────────────────────────────────────────────────────────────┐
│ BOOKING FLOW (Frontend)                                          │
├─────────────────────────────────────────────────────────────────┤
│ 1. Customer loads form with booking calendar field               │
│ 2. Customer selects service → Available staff shown              │
│ 3. Customer selects staff (or "Any available")                   │
│ 4. Calendar shows available slots based on:                      │
│    - Staff working hours                                         │
│    - Existing bookings                                           │
│    - Buffer times                                                │
│    - Resource availability                                       │
│    - Google Calendar busy times (if synced)                      │
│ 5. Customer selects date/time slot                               │
│ 6. Customer fills remaining form fields                          │
│ 7. Customer proceeds to payment (if required)                    │
│ 8. Form submission creates booking                               │
└─────────────────────────────────────────────────────────────────┘
                               ↓
┌─────────────────────────────────────────────────────────────────┐
│ POST-BOOKING (Triggers)                                          │
├─────────────────────────────────────────────────────────────────┤
│ 1. `booking.created` event fires                                 │
│ 2. Trigger actions execute:                                      │
│    - Send confirmation email to customer                         │
│    - Send notification to staff                                  │
│    - Create Google Calendar event                                │
│    - Create Zoom meeting (if virtual)                           │
│ 3. Scheduled reminders queued via Action Scheduler               │
│ 4. `booking.confirmed` fires (if auto-confirm enabled)           │
└─────────────────────────────────────────────────────────────────┘
```

## Database Schema

### Table 1: `wp_superforms_booking_services`

Stores service definitions (what can be booked).

```sql
CREATE TABLE wp_superforms_booking_services (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Basic Info
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT,
  short_description VARCHAR(500),
  image_url VARCHAR(500),
  color VARCHAR(7) DEFAULT '#3498db',

  -- Duration & Pricing
  duration INT NOT NULL DEFAULT 60,              -- Minutes
  buffer_before INT DEFAULT 0,                   -- Minutes before
  buffer_after INT DEFAULT 0,                    -- Minutes after
  price DECIMAL(10,2) DEFAULT 0.00,
  deposit_type ENUM('none', 'fixed', 'percent') DEFAULT 'none',
  deposit_amount DECIMAL(10,2) DEFAULT 0.00,

  -- Capacity (for group bookings)
  min_capacity INT DEFAULT 1,
  max_capacity INT DEFAULT 1,

  -- Scheduling Rules
  min_booking_notice INT DEFAULT 60,             -- Minutes in advance
  max_booking_window INT DEFAULT 30,             -- Days in advance
  slot_duration INT DEFAULT NULL,                -- Custom slot length (NULL = service duration)

  -- Recurring Options
  allow_recurring TINYINT(1) DEFAULT 0,
  recurring_frequencies JSON,                    -- ["daily", "weekly", "biweekly", "monthly"]
  max_recurring INT DEFAULT 10,                  -- Max appointments in series

  -- Visibility & Status
  status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
  visibility ENUM('public', 'private', 'staff_only') DEFAULT 'public',
  sort_order INT DEFAULT 0,

  -- Category (for organization)
  category_id BIGINT(20) UNSIGNED,

  -- Metadata
  settings JSON,                                 -- Additional settings
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY status (status),
  KEY category_id (category_id),
  KEY sort_order (sort_order)
) ENGINE=InnoDB;
```

### Table 2: `wp_superforms_booking_service_extras`

Optional add-ons that can be purchased with a service.

```sql
CREATE TABLE wp_superforms_booking_service_extras (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  service_id BIGINT(20) UNSIGNED NOT NULL,

  name VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) DEFAULT 0.00,
  duration INT DEFAULT 0,                        -- Additional minutes
  max_quantity INT DEFAULT 1,

  status ENUM('active', 'inactive') DEFAULT 'active',
  sort_order INT DEFAULT 0,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  KEY service_id (service_id),
  KEY status (status),
  FOREIGN KEY (service_id) REFERENCES wp_superforms_booking_services(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 3: `wp_superforms_booking_staff`

Staff members who provide services.

```sql
CREATE TABLE wp_superforms_booking_staff (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT(20) UNSIGNED,                   -- WordPress user (optional)

  -- Basic Info
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  bio TEXT,
  avatar_url VARCHAR(500),
  color VARCHAR(7) DEFAULT '#2ecc71',

  -- Visibility & Status
  status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
  visibility ENUM('public', 'hidden') DEFAULT 'public',
  sort_order INT DEFAULT 0,

  -- Calendar Integration
  google_calendar_id VARCHAR(255),
  google_calendar_token JSON,                    -- Encrypted OAuth tokens
  outlook_calendar_id VARCHAR(255),
  outlook_calendar_token JSON,
  sync_mode ENUM('none', 'one_way', 'two_way') DEFAULT 'none',

  -- Notifications
  email_notifications TINYINT(1) DEFAULT 1,
  sms_notifications TINYINT(1) DEFAULT 0,
  sms_phone VARCHAR(50),

  -- Pricing Override
  price_modifier_type ENUM('none', 'fixed', 'percent') DEFAULT 'none',
  price_modifier_value DECIMAL(10,2) DEFAULT 0.00,

  -- Metadata
  settings JSON,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY status (status),
  KEY email (email)
) ENGINE=InnoDB;
```

### Table 4: `wp_superforms_booking_staff_services`

Many-to-many: Which staff can provide which services.

```sql
CREATE TABLE wp_superforms_booking_staff_services (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id BIGINT(20) UNSIGNED NOT NULL,
  service_id BIGINT(20) UNSIGNED NOT NULL,

  -- Per-staff service customization
  custom_duration INT,                           -- Override duration
  custom_price DECIMAL(10,2),                    -- Override price

  created_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY staff_service (staff_id, service_id),
  KEY service_id (service_id),
  FOREIGN KEY (staff_id) REFERENCES wp_superforms_booking_staff(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES wp_superforms_booking_services(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 5: `wp_superforms_booking_schedules`

Working hours for staff members.

```sql
CREATE TABLE wp_superforms_booking_schedules (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id BIGINT(20) UNSIGNED NOT NULL,
  location_id BIGINT(20) UNSIGNED,               -- NULL = all locations

  day_of_week TINYINT NOT NULL,                  -- 0=Sunday, 1=Monday, etc.
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,

  -- Break within this schedule
  break_start TIME,
  break_end TIME,

  is_active TINYINT(1) DEFAULT 1,

  PRIMARY KEY (id),
  KEY staff_id (staff_id),
  KEY location_id (location_id),
  KEY day_of_week (day_of_week),
  FOREIGN KEY (staff_id) REFERENCES wp_superforms_booking_staff(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 6: `wp_superforms_booking_special_days`

Holidays, custom hours, days off.

```sql
CREATE TABLE wp_superforms_booking_special_days (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id BIGINT(20) UNSIGNED,                  -- NULL = applies to all staff
  location_id BIGINT(20) UNSIGNED,               -- NULL = all locations

  date DATE NOT NULL,
  type ENUM('day_off', 'custom_hours', 'holiday') NOT NULL,

  -- For custom_hours type
  start_time TIME,
  end_time TIME,

  reason VARCHAR(255),

  created_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  KEY staff_id (staff_id),
  KEY date (date),
  KEY type (type),
  FOREIGN KEY (staff_id) REFERENCES wp_superforms_booking_staff(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 7: `wp_superforms_booking_locations`

Multi-location support.

```sql
CREATE TABLE wp_superforms_booking_locations (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT,

  -- Address
  address_line_1 VARCHAR(255),
  address_line_2 VARCHAR(255),
  city VARCHAR(100),
  state VARCHAR(100),
  postal_code VARCHAR(20),
  country VARCHAR(2),                            -- ISO country code

  -- Contact
  phone VARCHAR(50),
  email VARCHAR(255),

  -- Coordinates (for maps)
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),

  -- Timezone
  timezone VARCHAR(50) DEFAULT 'UTC',

  -- Status
  status ENUM('active', 'inactive') DEFAULT 'active',
  sort_order INT DEFAULT 0,

  -- Metadata
  settings JSON,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY status (status)
) ENGINE=InnoDB;
```

### Table 8: `wp_superforms_booking_resources`

Rooms, equipment, vehicles that can be booked alongside services.

```sql
CREATE TABLE wp_superforms_booking_resources (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id BIGINT(20) UNSIGNED,               -- NULL = all locations

  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT,

  resource_type VARCHAR(50) NOT NULL,            -- 'room', 'equipment', 'vehicle', custom
  quantity INT DEFAULT 1,                        -- How many available

  -- Status
  status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',

  -- Metadata
  settings JSON,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY location_id (location_id),
  KEY resource_type (resource_type),
  KEY status (status)
) ENGINE=InnoDB;
```

### Table 9: `wp_superforms_booking_service_resources`

Many-to-many: Which services require which resources.

```sql
CREATE TABLE wp_superforms_booking_service_resources (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  service_id BIGINT(20) UNSIGNED NOT NULL,
  resource_id BIGINT(20) UNSIGNED NOT NULL,

  quantity_required INT DEFAULT 1,               -- How many of this resource needed
  is_required TINYINT(1) DEFAULT 1,              -- Required or optional?

  PRIMARY KEY (id),
  UNIQUE KEY service_resource (service_id, resource_id),
  KEY resource_id (resource_id),
  FOREIGN KEY (service_id) REFERENCES wp_superforms_booking_services(id) ON DELETE CASCADE,
  FOREIGN KEY (resource_id) REFERENCES wp_superforms_booking_resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 10: `wp_superforms_bookings`

Main bookings table.

```sql
CREATE TABLE wp_superforms_bookings (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Reference IDs
  booking_uid VARCHAR(32) NOT NULL,              -- Public-facing ID (e.g., "BK-A1B2C3")
  form_id BIGINT(20) UNSIGNED,
  entry_id BIGINT(20) UNSIGNED,

  -- What's being booked
  service_id BIGINT(20) UNSIGNED NOT NULL,
  staff_id BIGINT(20) UNSIGNED NOT NULL,
  location_id BIGINT(20) UNSIGNED,

  -- Who's booking
  customer_id BIGINT(20) UNSIGNED,               -- NULL for guest bookings
  customer_name VARCHAR(255) NOT NULL,
  customer_email VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50),

  -- When
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  timezone VARCHAR(50) NOT NULL,

  -- Duration
  duration INT NOT NULL,                         -- Actual duration in minutes
  buffer_before INT DEFAULT 0,
  buffer_after INT DEFAULT 0,

  -- Capacity (for group bookings)
  attendees INT DEFAULT 1,

  -- Status
  status ENUM(
    'pending',           -- Awaiting confirmation/payment
    'confirmed',         -- Confirmed, ready to go
    'cancelled',         -- Cancelled by customer or admin
    'completed',         -- Appointment finished
    'no_show',          -- Customer didn't show up
    'rescheduled'       -- Moved to different time (see rescheduled_to_id)
  ) DEFAULT 'pending',

  -- Payment
  payment_status ENUM('pending', 'deposit_paid', 'paid', 'refunded', 'partial_refund') DEFAULT 'pending',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  deposit_amount DECIMAL(10,2) DEFAULT 0.00,
  paid_amount DECIMAL(10,2) DEFAULT 0.00,
  currency VARCHAR(3) DEFAULT 'USD',
  payment_id BIGINT(20) UNSIGNED,                -- FK to wp_superforms_payments

  -- Extras
  extras JSON,                                   -- Selected service extras

  -- Recurring
  is_recurring TINYINT(1) DEFAULT 0,
  recurring_id BIGINT(20) UNSIGNED,              -- FK to wp_superforms_booking_recurring

  -- Rescheduling
  rescheduled_from_id BIGINT(20) UNSIGNED,       -- Original booking if rescheduled
  rescheduled_to_id BIGINT(20) UNSIGNED,         -- New booking if rescheduled

  -- Virtual meeting
  meeting_type ENUM('in_person', 'zoom', 'google_meet', 'teams', 'phone', 'custom') DEFAULT 'in_person',
  meeting_url VARCHAR(500),
  meeting_data JSON,                             -- Meeting ID, password, etc.

  -- Notes
  customer_notes TEXT,
  admin_notes TEXT,
  internal_notes TEXT,

  -- Resources booked
  resources JSON,                                -- Array of resource_id

  -- Cancellation
  cancelled_at DATETIME,
  cancelled_by ENUM('customer', 'staff', 'admin', 'system'),
  cancellation_reason TEXT,

  -- Metadata
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
  KEY location_datetime (location_id, start_datetime, end_datetime),
  FOREIGN KEY (service_id) REFERENCES wp_superforms_booking_services(id),
  FOREIGN KEY (staff_id) REFERENCES wp_superforms_booking_staff(id),
  FOREIGN KEY (location_id) REFERENCES wp_superforms_booking_locations(id)
) ENGINE=InnoDB;
```

### Table 11: `wp_superforms_booking_recurring`

Manages recurring appointment series.

```sql
CREATE TABLE wp_superforms_booking_recurring (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Pattern
  frequency ENUM('daily', 'weekly', 'biweekly', 'monthly') NOT NULL,
  interval_value INT DEFAULT 1,                  -- Every X days/weeks/months
  day_of_week TINYINT,                           -- For weekly: 0-6
  day_of_month TINYINT,                          -- For monthly: 1-31

  -- Limits
  start_date DATE NOT NULL,
  end_date DATE,                                 -- NULL = no end date
  total_occurrences INT,                         -- OR limit by count
  remaining_occurrences INT,

  -- Status
  status ENUM('active', 'paused', 'cancelled', 'completed') DEFAULT 'active',

  -- Source booking (template)
  source_booking_id BIGINT(20) UNSIGNED NOT NULL,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  KEY source_booking_id (source_booking_id),
  KEY status (status),
  FOREIGN KEY (source_booking_id) REFERENCES wp_superforms_bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 12: `wp_superforms_booking_customers`

Customer database (extends WordPress users).

```sql
CREATE TABLE wp_superforms_booking_customers (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT(20) UNSIGNED,                   -- WordPress user (optional)

  -- Contact Info
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100),
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50),

  -- Address
  address_line_1 VARCHAR(255),
  address_line_2 VARCHAR(255),
  city VARCHAR(100),
  state VARCHAR(100),
  postal_code VARCHAR(20),
  country VARCHAR(2),

  -- Preferences
  preferred_staff_id BIGINT(20) UNSIGNED,
  preferred_location_id BIGINT(20) UNSIGNED,
  preferred_notification ENUM('email', 'sms', 'both') DEFAULT 'email',
  timezone VARCHAR(50),
  language VARCHAR(10) DEFAULT 'en',

  -- Statistics
  total_bookings INT DEFAULT 0,
  no_shows INT DEFAULT 0,
  cancellations INT DEFAULT 0,
  total_spent DECIMAL(10,2) DEFAULT 0.00,

  -- Status
  status ENUM('active', 'blocked') DEFAULT 'active',
  notes TEXT,

  -- Marketing
  marketing_consent TINYINT(1) DEFAULT 0,
  consent_date DATETIME,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  KEY user_id (user_id),
  UNIQUE KEY email (email),
  KEY phone (phone),
  KEY status (status)
) ENGINE=InnoDB;
```

### Table 13: `wp_superforms_booking_waitlist`

Waitlist for fully booked slots.

```sql
CREATE TABLE wp_superforms_booking_waitlist (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- What they want
  service_id BIGINT(20) UNSIGNED NOT NULL,
  staff_id BIGINT(20) UNSIGNED,                  -- NULL = any staff
  location_id BIGINT(20) UNSIGNED,               -- NULL = any location

  -- Preferred date/time range
  preferred_date DATE NOT NULL,
  preferred_time_start TIME,
  preferred_time_end TIME,

  -- Flexibility
  flexible_date TINYINT(1) DEFAULT 0,            -- Accept nearby dates?
  flexible_days INT DEFAULT 3,                   -- How many days flexibility
  flexible_staff TINYINT(1) DEFAULT 0,           -- Accept any staff?

  -- Who
  customer_id BIGINT(20) UNSIGNED,
  customer_name VARCHAR(255) NOT NULL,
  customer_email VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50),

  -- Status
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
  KEY customer_email (customer_email),
  FOREIGN KEY (service_id) REFERENCES wp_superforms_booking_services(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 14: `wp_superforms_booking_reminders`

Scheduled reminders for bookings.

```sql
CREATE TABLE wp_superforms_booking_reminders (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT(20) UNSIGNED NOT NULL,

  -- Timing
  reminder_type ENUM('before', 'after') DEFAULT 'before',
  time_offset INT NOT NULL,                      -- Minutes before/after

  -- Delivery
  channel ENUM('email', 'sms', 'both') DEFAULT 'email',
  recipient_type ENUM('customer', 'staff', 'both') DEFAULT 'customer',

  -- Status
  status ENUM('scheduled', 'sent', 'failed', 'cancelled') DEFAULT 'scheduled',
  scheduled_for DATETIME NOT NULL,
  sent_at DATETIME,

  -- Error tracking
  error_message TEXT,
  retry_count INT DEFAULT 0,

  -- Action Scheduler job
  action_scheduler_id BIGINT(20) UNSIGNED,

  created_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  KEY booking_id (booking_id),
  KEY scheduled_for (scheduled_for),
  KEY status (status),
  FOREIGN KEY (booking_id) REFERENCES wp_superforms_bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Table 15: `wp_superforms_booking_categories`

Service categories for organization.

```sql
CREATE TABLE wp_superforms_booking_categories (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT,
  image_url VARCHAR(500),
  color VARCHAR(7) DEFAULT '#9b59b6',

  parent_id BIGINT(20) UNSIGNED,                 -- For nested categories
  sort_order INT DEFAULT 0,

  status ENUM('active', 'inactive') DEFAULT 'active',

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY parent_id (parent_id),
  KEY sort_order (sort_order)
) ENGINE=InnoDB;
```

## Data Access Layer (DAL) Classes

### SUPER_Booking_Service_DAL

```php
class SUPER_Booking_Service_DAL {
    // CRUD
    public static function create(array $data): int|WP_Error;
    public static function get(int $id): ?object;
    public static function get_by_slug(string $slug): ?object;
    public static function update(int $id, array $data): bool|WP_Error;
    public static function delete(int $id): bool;

    // Queries
    public static function get_all(array $args = []): array;
    public static function get_active(): array;
    public static function get_by_category(int $category_id): array;
    public static function get_by_staff(int $staff_id): array;

    // Extras
    public static function get_extras(int $service_id): array;
    public static function add_extra(int $service_id, array $data): int;
    public static function update_extra(int $extra_id, array $data): bool;
    public static function delete_extra(int $extra_id): bool;
}
```

### SUPER_Booking_Staff_DAL

```php
class SUPER_Booking_Staff_DAL {
    // CRUD
    public static function create(array $data): int|WP_Error;
    public static function get(int $id): ?object;
    public static function get_by_user_id(int $user_id): ?object;
    public static function update(int $id, array $data): bool|WP_Error;
    public static function delete(int $id): bool;

    // Queries
    public static function get_all(array $args = []): array;
    public static function get_active(): array;
    public static function get_by_service(int $service_id): array;
    public static function get_by_location(int $location_id): array;

    // Services
    public static function assign_service(int $staff_id, int $service_id, array $options = []): bool;
    public static function unassign_service(int $staff_id, int $service_id): bool;
    public static function get_services(int $staff_id): array;

    // Schedule
    public static function get_schedule(int $staff_id, ?int $location_id = null): array;
    public static function set_schedule(int $staff_id, array $schedule): bool;
    public static function get_special_days(int $staff_id, string $start_date, string $end_date): array;
    public static function add_special_day(int $staff_id, array $data): int;
}
```

### SUPER_Booking_DAL

```php
class SUPER_Booking_DAL {
    // CRUD
    public static function create(array $data): int|WP_Error;
    public static function get(int $id): ?object;
    public static function get_by_uid(string $uid): ?object;
    public static function update(int $id, array $data): bool|WP_Error;
    public static function delete(int $id): bool;

    // Queries
    public static function get_all(array $args = []): array;
    public static function get_by_customer(int $customer_id, array $args = []): array;
    public static function get_by_customer_email(string $email, array $args = []): array;
    public static function get_by_staff(int $staff_id, array $args = []): array;
    public static function get_by_date_range(string $start, string $end, array $args = []): array;
    public static function get_by_entry(int $entry_id): ?object;

    // Status Management
    public static function confirm(int $id): bool|WP_Error;
    public static function cancel(int $id, string $cancelled_by, ?string $reason = null): bool|WP_Error;
    public static function complete(int $id): bool|WP_Error;
    public static function mark_no_show(int $id): bool|WP_Error;
    public static function reschedule(int $id, string $new_datetime): int|WP_Error;

    // Availability
    public static function is_slot_available(int $staff_id, string $datetime, int $duration, ?int $exclude_booking_id = null): bool;
    public static function get_conflicting_bookings(int $staff_id, string $start, string $end): array;
}
```

### SUPER_Booking_Availability_Engine

```php
class SUPER_Booking_Availability_Engine {
    /**
     * Get available time slots for a service/staff/location combination
     */
    public static function get_available_slots(
        int $service_id,
        ?int $staff_id = null,          // NULL = any available staff
        ?int $location_id = null,       // NULL = any location
        string $date,                    // Y-m-d format
        array $options = []
    ): array;

    /**
     * Get available dates in a date range
     */
    public static function get_available_dates(
        int $service_id,
        ?int $staff_id = null,
        ?int $location_id = null,
        string $start_date,
        string $end_date
    ): array;

    /**
     * Check if specific slot is bookable
     */
    public static function is_slot_bookable(
        int $service_id,
        int $staff_id,
        string $datetime,
        int $attendees = 1,
        ?int $location_id = null
    ): bool|WP_Error;

    /**
     * Get staff availability for a day
     */
    public static function get_staff_availability(
        int $staff_id,
        string $date,
        ?int $location_id = null
    ): array;

    /**
     * Check Google Calendar for busy times (if synced)
     */
    public static function get_external_busy_times(
        int $staff_id,
        string $start_datetime,
        string $end_datetime
    ): array;
}
```

## Registered Events

Events fired by the booking system for triggers:

```php
// Booking Lifecycle
'booking.created'              // New booking created
'booking.confirmed'            // Booking confirmed (payment received or auto-confirm)
'booking.cancelled'            // Booking cancelled
'booking.rescheduled'          // Booking moved to new time
'booking.completed'            // Appointment completed
'booking.no_show'              // Customer didn't show up

// Payment Events (complement Phase 18)
'booking.payment_received'     // Full payment received
'booking.deposit_received'     // Deposit payment received
'booking.refund_processed'     // Refund issued

// Reminder Events
'booking.reminder_24h'         // 24 hours before appointment
'booking.reminder_1h'          // 1 hour before appointment
'booking.reminder_custom'      // Custom reminder timing
'booking.followup'             // Post-appointment follow-up

// Recurring
'booking.recurring_created'    // New recurring series created
'booking.recurring_cancelled'  // Entire series cancelled

// Waitlist
'booking.waitlist_joined'      // Customer joined waitlist
'booking.waitlist_slot_opened' // Slot became available, waitlist notified
'booking.waitlist_converted'   // Waitlist entry converted to booking

// Staff Events
'booking.staff_assigned'       // Booking assigned to staff
'booking.staff_changed'        // Staff member changed
```

## Registered Actions

Actions available in triggers for bookings:

```php
// Notifications
'send_booking_confirmation'    // Email confirmation to customer
'send_booking_notification'    // Notify staff of new booking
'send_booking_reminder'        // Send reminder at specified time
'send_cancellation_notice'     // Notify of cancellation

// Calendar
'create_google_calendar_event' // Add to Google Calendar
'update_google_calendar_event' // Update existing event
'delete_google_calendar_event' // Remove from calendar

// Virtual Meetings
'create_zoom_meeting'          // Create Zoom meeting
'create_google_meet'           // Create Google Meet link

// CRM Integration
'update_customer_record'       // Update customer stats
'add_customer_note'            // Add note to customer

// Status
'confirm_booking'              // Auto-confirm booking
'cancel_booking'               // Cancel with reason
```

## REST API Endpoints

```
# Services
GET    /wp-json/super-forms/v1/booking/services
POST   /wp-json/super-forms/v1/booking/services
GET    /wp-json/super-forms/v1/booking/services/{id}
PUT    /wp-json/super-forms/v1/booking/services/{id}
DELETE /wp-json/super-forms/v1/booking/services/{id}

# Staff
GET    /wp-json/super-forms/v1/booking/staff
POST   /wp-json/super-forms/v1/booking/staff
GET    /wp-json/super-forms/v1/booking/staff/{id}
PUT    /wp-json/super-forms/v1/booking/staff/{id}
DELETE /wp-json/super-forms/v1/booking/staff/{id}
GET    /wp-json/super-forms/v1/booking/staff/{id}/schedule
PUT    /wp-json/super-forms/v1/booking/staff/{id}/schedule

# Locations
GET    /wp-json/super-forms/v1/booking/locations
POST   /wp-json/super-forms/v1/booking/locations
GET    /wp-json/super-forms/v1/booking/locations/{id}
PUT    /wp-json/super-forms/v1/booking/locations/{id}
DELETE /wp-json/super-forms/v1/booking/locations/{id}

# Availability
GET    /wp-json/super-forms/v1/booking/availability/slots
       ?service_id=X&staff_id=X&location_id=X&date=YYYY-MM-DD
GET    /wp-json/super-forms/v1/booking/availability/dates
       ?service_id=X&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD

# Bookings
GET    /wp-json/super-forms/v1/bookings
POST   /wp-json/super-forms/v1/bookings
GET    /wp-json/super-forms/v1/bookings/{id}
PUT    /wp-json/super-forms/v1/bookings/{id}
DELETE /wp-json/super-forms/v1/bookings/{id}
POST   /wp-json/super-forms/v1/bookings/{id}/confirm
POST   /wp-json/super-forms/v1/bookings/{id}/cancel
POST   /wp-json/super-forms/v1/bookings/{id}/reschedule
POST   /wp-json/super-forms/v1/bookings/{id}/complete
POST   /wp-json/super-forms/v1/bookings/{id}/no-show

# Customer Portal
GET    /wp-json/super-forms/v1/booking/my-bookings
       (requires authentication or booking_uid + email)
POST   /wp-json/super-forms/v1/booking/my-bookings/{uid}/cancel
POST   /wp-json/super-forms/v1/booking/my-bookings/{uid}/reschedule

# Waitlist
POST   /wp-json/super-forms/v1/booking/waitlist
GET    /wp-json/super-forms/v1/booking/waitlist/{id}
DELETE /wp-json/super-forms/v1/booking/waitlist/{id}

# Calendar Sync
GET    /wp-json/super-forms/v1/booking/calendar/google/auth
POST   /wp-json/super-forms/v1/booking/calendar/google/callback
POST   /wp-json/super-forms/v1/booking/calendar/google/disconnect
GET    /wp-json/super-forms/v1/booking/calendar/google/sync
```

## Form Builder Integration

### "Booking Calendar" Field Element

New form element added to the form builder:

```php
// Field settings
[
    'type' => 'booking_calendar',
    'label' => 'Select Appointment',

    // Service Selection
    'service_selection' => 'dropdown', // dropdown, radio, hidden
    'allowed_services' => [],          // Empty = all, or specific IDs
    'default_service' => null,

    // Staff Selection
    'staff_selection' => 'dropdown',   // dropdown, radio, hidden, any
    'allowed_staff' => [],             // Empty = all
    'show_staff_photos' => true,

    // Location Selection
    'location_selection' => 'dropdown', // dropdown, hidden
    'allowed_locations' => [],

    // Calendar Display
    'calendar_view' => 'month',        // month, week, list
    'show_timezone' => true,
    'customer_timezone' => true,       // Convert to customer's timezone

    // Booking Options
    'allow_group_booking' => false,
    'max_attendees' => 1,
    'show_extras' => true,

    // Restrictions
    'min_notice' => 60,                // Minutes (override service default)
    'max_advance' => 30,               // Days

    // Style
    'primary_color' => '#3498db',
    'show_prices' => true,
]
```

### Field Output Data

When form is submitted, the booking_calendar field returns:

```php
[
    'service_id' => 123,
    'service_name' => 'Dental Cleaning',
    'staff_id' => 45,
    'staff_name' => 'Dr. Smith',
    'location_id' => 7,
    'location_name' => 'Downtown Office',
    'datetime' => '2025-01-15 14:30:00',
    'timezone' => 'America/New_York',
    'duration' => 60,
    'attendees' => 1,
    'extras' => [
        ['id' => 1, 'name' => 'X-Ray', 'price' => 25.00, 'quantity' => 1]
    ],
    'total_price' => 125.00,
    'deposit_required' => 25.00,
]
```

## Subtasks

This task is divided into implementation phases:

### 19a: Booking Tables & DAL
- Database table creation (15 tables)
- Data Access Layer classes
- Migration/installation hooks

### 19b: Booking Calendar UI
- "Booking Calendar" form field element
- Frontend calendar component (React)
- Availability engine
- Service/staff/location selection UI

### 19c: Notifications & Reminders
- Reminder scheduling via Action Scheduler
- Email templates for booking notifications
- SMS integration (optional)
- Calendar sync (Google, Outlook)

### 19d: Admin Management UI
- Services management page
- Staff management page
- Locations management page
- Resources management page
- Booking calendar view
- Customer management

### 19e: Customer Portal
- Self-service booking management
- View/cancel/reschedule bookings
- Booking history
- Guest booking access via email + booking_uid

## Success Criteria

### Phase 19a: Tables & DAL
- [ ] All 15 database tables created with proper indexes
- [ ] DAL classes for all entities (Service, Staff, Booking, etc.)
- [ ] Unit tests for DAL operations
- [ ] Migration scripts for upgrades

### Phase 19b: Calendar UI
- [ ] Booking calendar field in form builder
- [ ] Real-time availability checking
- [ ] Service → Staff → Time slot selection flow
- [ ] Group booking support
- [ ] Service extras selection
- [ ] Mobile-responsive calendar

### Phase 19c: Notifications
- [ ] Confirmation emails (customer + staff)
- [ ] Reminder emails (configurable timing)
- [ ] Cancellation/reschedule notifications
- [ ] Google Calendar 2-way sync
- [ ] Outlook/iCal sync
- [ ] Action Scheduler for reminder queue

### Phase 19d: Admin UI
- [ ] Services CRUD with categories
- [ ] Staff management with schedules
- [ ] Location management
- [ ] Resource management
- [ ] Calendar view (day/week/month)
- [ ] Booking management (confirm/cancel/reschedule)
- [ ] Customer database

### Phase 19e: Customer Portal
- [ ] View upcoming bookings
- [ ] Cancel booking (within policy)
- [ ] Reschedule booking
- [ ] Booking history
- [ ] Guest access (no login required)

## Industry Use Cases

### Dentist Office
- Services: Cleaning ($100, 30min), Filling ($200, 45min), Root Canal ($800, 90min)
- Staff: Dr. Smith, Dr. Jones (different specialties)
- Resources: Room 1, Room 2 (dental chairs)
- Buffer: 15 min cleaning between patients
- Deposit: 20% for procedures over $500
- Reminders: 24h + 1h before

### Hair Salon
- Services: Haircut ($40, 45min), Color ($120, 2h), Highlights ($150, 2.5h)
- Staff: Multiple stylists with different skills/prices
- Multi-booking: Client books haircut + color together
- Group: Wedding party bookings (multiple stylists same time)
- Upsells: Deep conditioning (+$20), Styling (+$15)

### Fitness Trainer
- Services: Personal Training ($80/hr), Group Class ($25)
- Capacity: Personal = 1 person, Group = max 10
- Recurring: Weekly training sessions
- Virtual: Zoom option for remote clients
- Packages: Buy 10 sessions, save 15%

### Consultant/Coach
- Services: Discovery Call (free, 30min), Consultation ($150, 1h)
- Virtual-first: Google Meet links auto-created
- Timezone: Customer books in their timezone
- Notice: 48h minimum for consultations
- Deposit: Full payment required upfront

### Medical Clinic
- Services: General Checkup, Specialist Visit, Lab Work
- Staff: Multiple doctors with different specialties
- Resources: Examination rooms, lab equipment
- Waitlist: High-demand specialists
- HIPAA: Extra privacy considerations

## Sources

Research compiled from:
- [WPBeginner - 7 Best WordPress Appointment Plugins](https://www.wpbeginner.com/plugins/5-best-wordpress-appointment-and-booking-plugins/)
- [Zapier - 8 Best WordPress Booking Plugins](https://zapier.com/blog/best-wordpress-booking-plugin/)
- [Amelia WordPress Booking Plugin](https://wpamelia.com/)
- [Bookly WordPress Appointment Plugin](https://www.booking-wp-plugin.com/)
- [LatePoint Appointment Scheduling](https://latepoint.com/)
- [BookingPress Features](https://www.bookingpressplugin.com/features/)
- [Simply Schedule Appointments](https://simplyscheduleappointments.com/)
- [NexHealth Dental Scheduling](https://www.nexhealth.com/features/waitlist)
- [Booknetic Deposit Payments](https://www.booknetic.com/feature/wordpress-appointment-booking-plugin-with-deposit-payments)

## Work Log

### 2025-11-24

#### Completed
- Researched 6 major WordPress booking plugins (Amelia, Bookly, LatePoint, BookingPress, Simply Schedule Appointments, WooCommerce Bookings)
- Created main task file with comprehensive architecture:
  - Competitive analysis table with pricing and feature comparison
  - 15 database table schemas (services, staff, schedules, bookings, customers, waitlist, reminders, locations, resources, categories, etc.)
  - DAL class signatures for all entities (Service, Staff, Booking, Availability Engine)
  - 17 registered events for triggers system integration
  - 10+ registered actions for automations (notifications, calendar, meetings, CRM)
  - ~30 REST API endpoints across services, staff, locations, bookings, availability, waitlist, calendar sync
  - 5 industry use cases (Dentist Office, Hair Salon, Fitness Trainer, Consultant/Coach, Medical Clinic)
- Created `19-booking-epics.md` with 10 detailed implementation epics and ASCII UI mockups for admin and frontend flows
- Created `19a-booking-tables-dal.md` with database installation code and DAL class implementations
- Created `19b-booking-calendar-ui.md` with availability engine algorithm and form builder integration
- Created `19c-booking-notifications.md` with reminder scheduling, email/SMS templates, and calendar sync (Google/Outlook)
- Updated `README.md` to add Phase 18 and Phase 19 to the Subtasks section

#### Decisions
- Model after Amelia/LatePoint for UI patterns and Bookly for extensibility
- Use 15 normalized tables rather than JSON blobs for performance and querying
- Integrate with Phase 18 payment tables (`resource_type = 'booking'`)
- Leverage existing triggers/actions system for all booking events
- Use Action Scheduler for reminder queue (already bundled)

#### Files Created
- `/sessions/tasks/h-implement-triggers-actions-extensibility/19-implement-appointments-booking.md` (main task)
- `/sessions/tasks/h-implement-triggers-actions-extensibility/19-booking-epics.md` (epics with UI mockups)
- `/sessions/tasks/h-implement-triggers-actions-extensibility/19a-booking-tables-dal.md` (database + DAL)
- `/sessions/tasks/h-implement-triggers-actions-extensibility/19b-booking-calendar-ui.md` (calendar UI + availability)
- `/sessions/tasks/h-implement-triggers-actions-extensibility/19c-booking-notifications.md` (notifications + calendar sync)

#### Next Steps
- Begin Phase 19a implementation: Create booking database tables in `class-install.php`
- Implement DAL classes for Service, Staff, Location, Resource entities
- Write unit tests for DAL operations
