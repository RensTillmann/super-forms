---
name: h-implement-triggers-actions-extensibility
branch: feature/h-implement-triggers-actions-extensibility
status: in-progress
created: 2025-11-20
---

# Implement Extensible Triggers/Actions System

## Problem/Goal

The current triggers/actions system is functionally solid but architecturally closed - it works well for the 5 built-in actions but provides zero extensibility for add-ons. Events and actions are hardcoded arrays with no registration API, making it impossible for add-ons to integrate properly.

**Core Issues:**
- Add-ons cannot register new events or actions
- No hooks/filters for extending the triggers UI
- Custom WP-Cron scheduling instead of Action Scheduler (unreliable)
- No action result tracking or error handling
- Limited conditional logic (single condition only)
- No support for complex integrations (API requests, response parsing, field mapping)

**Important Note:** The triggers/actions system has NOT been released yet - no customers are using it. This means we can refactor completely without migration scripts or backward compatibility concerns.

**Vision:**
Build a modular add-on ecosystem where external plugins can easily extend Super Forms with:
- CRM integrations (Salesforce, HubSpot, Pipedrive)
- AI services (OpenAI, Claude, Gemini, Groq)
- Google services (Sheets, Docs, Drive, Calendar)
- Email marketing (MailChimp, SendGrid, Mailster)
- Custom HTTP requests (Postman-like functionality)
- Real-time field interactions (on keypress, button click)

**Architecture Decision: Dedicated Admin Page**

Triggers are managed through a dedicated "Super Forms > Triggers" admin page (NOT within form builder):
- Centralized management of all triggers across all forms
- Advanced filtering by scope (form/global/user/role), event type, status
- Bulk operations (enable/disable/delete multiple triggers)
- Execution logs and debugging view
- Form builder integration: Simple "Triggers (3)" link to dedicated page

**Benefits:**
- Global triggers visible in one place (not hidden in individual forms)
- Better performance (direct table queries vs iterating form meta)
- Scope flexibility (form-specific, global, user-based, role-based)
- Future-proof for non-form triggers (WordPress events, WooCommerce, etc.)

## Scope System Architecture

Triggers can be scoped to different contexts, determining where and when they execute:

### Core Scopes (Phase 1)

1. **`form`** - Trigger bound to specific form
   - `scope_id = form_id`
   - Example: "Send confirmation email when Contact Form #123 is submitted"
   - Permissions: Form editors can manage (future)
   - Most common use case (90% of triggers)

2. **`global`** - Trigger applies to ALL forms
   - `scope_id = NULL`
   - Example: "Log all form submissions to external analytics"
   - Permissions: Requires `manage_options`
   - Power user feature

3. **`user`** - Trigger for specific user's submissions
   - `scope_id = user_id`
   - Example: "When user #5 submits ANY form, alert their manager"
   - Permissions: User can manage their own triggers (future)
   - Personal automation

4. **`role`** - Trigger for submissions by users with specific role
   - `scope_id = NULL` (role stored in conditions)
   - Example: "When 'Employee' role submits, require manager approval"
   - Permissions: Requires `manage_options`
   - Team-based automation

### Future Scopes (Multisite)

5. **`site`** - Trigger for specific site in network
   - `scope_id = blog_id`
   - Multisite only

6. **`network`** - Network-wide trigger
   - `scope_id = NULL`
   - Super admin only

### How Scopes Work with Events

Scope determines **which submissions** trigger evaluates:
- `form` scope: Only submissions from that specific form
- `global` scope: All form submissions across all forms
- `user` scope: Only submissions by that specific user (any form)
- `role` scope: Only submissions by users with that role (any form)

Additional filtering done via **conditions** (e.g., "payment gateway is Stripe", "total > 100").

## Subtasks

This task is divided into implementation phases, each documented as a separate subtask:

**Backend Infrastructure (Complete):**
1. **[Foundation and Registry System](01-implement-foundation-registry.md)** - Database schema, DAL, Manager, Registry, Conditions Engine, REST API (NO UI)
2. **[Action Scheduler Integration](02-implement-action-scheduler.md)** - Async execution via Action Scheduler
3. **[Execution and Logging](03-implement-execution-logging.md)** - Advanced logging, debugging, compliance
4. **[API and Security](04-implement-api-security.md)** - OAuth, encrypted credentials, rate limiting
5. **[HTTP Request Action](05-implement-http-request.md)** - Postman-like HTTP request builder
6. **[Payment and Subscription Events](06-implement-payment-subscription.md)** - Payment gateway integrations

**Testing Infrastructure (Complete):**
9. **[Test Fixtures and Payment OAuth](09-implement-test-fixtures-oauth.md)** - Comprehensive test forms, payment OAuth integration
10. **[Payment Webhook Tests](10-implement-payment-webhook-tests.md)** - Unit tests for payment OAuth and webhook handlers

**Sessions/Spam/Abort Architecture (Complete):**
1a. **[Sessions & Spam Detection](01a-implement-built-in-actions-spam-detection.md)** - Progressive sessions, spam/duplicate detection, abort flow
   - ✅ [Step 1: Sessions Table & DAL](01a-step1-sessions-table.md) - Complete
   - ✅ [Step 2: Client-Side Sessions](01a-step2-client-side-sessions.md) - Complete
   - ✅ [Step 3: Spam Detector](01a-step3-spam-detector.md) - Complete
   - ✅ [Step 4: Duplicate Detector](01a-step4-duplicate-detector.md) - Complete
   - ✅ [Step 5: Submission Flow Refactor](01a-step5-submission-flow-refactor.md) - Complete
   - ✅ [Step 6: Cleanup Scheduled Jobs](01a-step6-cleanup-scheduled-jobs.md) - Complete

**Feature Migration (Pending - Requires 1a):**
11. **[Email System Migration](11-implement-email-migration.md)** - Migrate Admin/Confirm emails to triggers, integrate Email v2 builder
12. **[WooCommerce Trigger Migration](12-implement-woocommerce-migration.md)** - Convert WooCommerce checkout to use triggers system
13. **[FluentCRM Integration](13-implement-fluentcrm.md)** - New WordPress-native CRM integration add-on

**Database Architecture (Priority - Performance):**
17. **[Contact Entries to Custom Table](17-migrate-entries-to-custom-table.md)** - Migrate `super_contact_entry` post type to dedicated `wp_superforms_entries` table
   - Custom table schema with optimized indexes
   - Entry Data Access Layer (SUPER_Entry_DAL)
   - Backwards compatibility hooks (get_post, get_post_meta, WP_Query)
   - Background migration via Action Scheduler
   - 30-day retention before cleanup
   - Custom admin list table replacement
   - REST API endpoints for entries

18. **[Payment Architecture & License/SaaS API](18-implement-payment-architecture.md)** - Comprehensive payment storage and license system
   - Subtasks:
     - [18a: Payment Tables & DAL](18a-payment-tables-dal.md) - Core payment/subscription tables
     - [18b: Payment Dashboard](18b-payment-dashboard.md) - Admin UI for payment management
     - [18c: License System & SaaS API](18c-license-saas-api.md) - License keys, validation API, activation management
     - [18d: Invoice Generation](18d-invoice-generation.md) - PDF invoices, email delivery
   - Epics & User Flows: [18-payment-epics.md](18-payment-epics.md)
   - Features:
     - Dedicated `wp_superforms_payments` table (not entry_meta)
     - Subscription lifecycle management with events audit trail
     - Polymorphic resource linking (entries, bookings, tickets, licenses)
     - Admin dashboard: view payments, process refunds, cancel subscriptions
     - License API for plugin/theme developers selling via Super Forms
     - Invoice generation and download

**Future Phases (Pending):**
7. **[Example Add-ons](07-implement-example-addons.md)** - Additional example add-ons (Slack, Google Sheets, HubSpot)
8. **[Real-time Interactions](08-implement-realtime-interactions.md)** - Client-side triggers, validation, duplicate detection
14. **[Analytics Dashboard](14-implement-analytics-dashboard.md)** - Form analytics, live sessions, abandonment tracking, field-level insights

**Note:** Admin UI implementation deferred to separate phase. UI will consume REST API built in Phase 1.

## Success Criteria

### Phase 1: Foundation and Registry System (Backend Only) - COMPLETE
- [x] Database tables created with scope support
- [x] Data Access Layer (DAL) with scope queries
- [x] Manager class with business logic and validation
- [x] Registry system for events and actions
- [x] Complex condition engine (AND/OR/NOT grouping, {tag} replacement)
- [x] Base action class with common functionality
- [x] Executor class (synchronous execution)
- [x] REST API v1 endpoints (full CRUD)
- [x] Unit tests (102 tests, 370 assertions, 0 failures)
- [x] NO backward compatibility needed (unreleased feature)
- [ ] NO admin UI (deferred to separate phase)
- [x] See [Phase 1 subtask](01-implement-foundation-registry.md) for details

### Phase 2: Action Scheduler Integration (Async Execution) ✅ COMPLETE
- [x] Executor enhanced with async execution via Action Scheduler
- [x] Scheduled/delayed action support
- [x] Queue management integration
- [x] Failed action retry mechanism
- [x] Note: Phase 1 builds synchronous executor, Phase 2 adds async layer
- [x] See [Phase 2 subtask](02-implement-action-scheduler.md) for details

### Phase 3: Execution and Logging - COMPLETE
- [x] Robust execution engine with error handling
- [x] Comprehensive logging system
- [x] Debug mode for development
- [x] Performance metrics tracking
- [x] GDPR compliance and audit trails
- [x] Admin log viewer with filtering/export
- [x] See [Phase 3 subtask](03-implement-execution-logging.md) for details

### Phase 4: API and Security - COMPLETE
- [x] Secure API credentials storage (AES-256-CBC encryption)
- [x] REST API endpoints (existing from Phase 1)
- [x] OAuth 2.0 support (PKCE, token refresh, provider registration)
- [x] Rate limiting implemented (pattern detection, security logging)
- [x] WordPress capabilities system (4 custom capabilities)
- [x] API key management (generation, validation, usage tracking)
- [x] See [Phase 4 subtask](04-implement-api-security.md) for details

### Phase 5: HTTP Request Action - COMPLETE
- [x] Support all HTTP methods (GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS)
- [x] Multiple authentication methods (None, Basic, Bearer, API Key, OAuth 2.0, Custom Header)
- [x] Flexible body formats (None, JSON, Form Data, XML, Raw, GraphQL, Auto)
- [x] Response parsing and mapping (JSON/XML path extraction, tag replacement)
- [x] Template system with 15 pre-built integrations
- [x] Debug mode and retry mechanism
- [x] See [Phase 5 subtask](05-implement-http-request.md) for details

### Phase 6: Payment Events - COMPLETE
- [x] Payment gateway events integrated (16 events: 8 Stripe, 8 PayPal)
- [x] Subscription lifecycle tracked (created, updated, cancelled, suspended, invoice_paid, invoice_failed)
- [x] Refund handling implemented (payment.paypal.capture_refunded event)
- [x] Webhook endpoints: `POST /webhooks/stripe`, `POST /webhooks/paypal`
- [x] Signature verification for both Stripe (HMAC-SHA256) and PayPal (API verification)
- [x] See [Phase 6 subtask](06-implement-payment-subscription.md) for details

### Phase 7: Example Add-ons
- [ ] Slack integration example
- [ ] Google Sheets example
- [ ] CRM Connector example
- [ ] Developer documentation
- [ ] See [Phase 7 subtask](07-implement-example-addons.md) for details

### Phase 8: Real-time Interactions
- [ ] Client-side event system (keyup, change, blur, focus)
- [ ] Debouncing and caching mechanisms
- [ ] Email validation and duplicate detection
- [ ] Dynamic field population from APIs
- [ ] Network failure handling and retry logic
- [ ] See [Phase 8 subtask](08-implement-realtime-interactions.md) for details

### Phase 9: Test Fixtures and Payment OAuth - COMPLETE
#### Part A: Test Fixtures (COMPLETE)
- [x] Form Factory creates comprehensive test forms (multi-step, repeater, signature, PDF)
- [x] Trigger Factory attaches triggers with all action types
- [x] Webhook Simulator generates valid Stripe/PayPal webhook payloads
- [x] Integration tests cover full submission → trigger → action flow
#### Part B: Payment OAuth (COMPLETE)
- [x] `SUPER_Payment_OAuth` class - Stripe Connect and PayPal OAuth flows
- [x] Stripe Quick Connect OAuth flow (platform credentials via super-forms.com)
- [x] PayPal Quick Connect OAuth flow (platform credentials)
- [x] Manual API key fallback for enterprise users (`save_manual_keys()`)
- [x] Token exchange and encrypted credential storage via `SUPER_Trigger_Credentials`
#### Part C: Payment Events (COMPLETE - overlaps with Phase 6)
- [x] All payment events registered in Registry (Stripe: 8, PayPal: 8)
- [x] Webhook signature verification (Stripe HMAC-SHA256, PayPal API verification)
- [ ] Form builder shows gateway connection status (UI deferred)
- [x] See [Phase 9 subtask](09-implement-test-fixtures-oauth.md) for details

### Phase 10: Payment Webhook Tests - COMPLETE
- [x] Payment event registration tests (16 events verified)
- [x] Stripe signature verification tests (security-critical)
- [x] Event mapping tests (gateway -> Super Forms events)
- [x] Context building tests (Stripe payload parsing)
- [x] Context building tests (PayPal payload parsing)
- [x] Edge cases: missing fields, malformed data, boundary conditions
- [x] See [Phase 10 subtask](10-implement-payment-webhook-tests.md) for details

### Phase 11: Email System Migration - IN PROGRESS (~80%)
- [x] Email v2 tab saves trigger configuration (facade pattern) - `sync_emails_to_triggers()`
- [x] Migration script converts legacy email settings to triggers - `migrate_form()`
- [x] Visual/HTML mode toggle with data loss prevention
- [x] HTML element type for raw HTML blocks in Email v2 builder
- [x] Bidirectional sync: Email v2 ↔ Triggers (`convert_triggers_to_emails_format()`)
- [x] Backward compatible: old settings still work during transition
- [ ] Admin emails sent via `send_email` action (needs testing)
- [ ] Confirmation emails sent via `send_email` action (needs testing)
- [ ] Email v2 templates render correctly through trigger system (needs testing)
- [ ] Logging shows email delivery status (needs testing)
- [x] See [Phase 11 subtask](11-implement-email-migration.md) for details

### Phase 12: WooCommerce Trigger Migration - PENDING
- [ ] WooCommerce actions registered (add_to_cart, create_order, etc.)
- [ ] WooCommerce events registered (order status changes)
- [ ] Migration script converts existing WooCommerce settings
- [ ] Product field mapping preserved
- [ ] Entry status updates via triggers on order status change
- [ ] Order creation logged in trigger logs
- [ ] See [Phase 12 subtask](12-implement-woocommerce-migration.md) for details

### Phase 13: FluentCRM Integration - PENDING
- [ ] `fluentcrm.create_contact` action creates/updates contacts
- [ ] `fluentcrm.add_to_lists` assigns contacts to lists
- [ ] `fluentcrm.apply_tags` applies tags to contacts
- [ ] `fluentcrm.remove_tags` removes tags
- [ ] `fluentcrm.start_automation` triggers automation funnels
- [ ] Error handling when FluentCRM not active
- [ ] See [Phase 13 subtask](13-implement-fluentcrm.md) for details

### Phase 14: Analytics Dashboard - PENDING
- [ ] Dedicated "Super Forms > Analytics" admin page
- [ ] Overview cards (submissions today/week/month/all-time)
- [ ] Submissions chart with selectable date range
- [ ] Top forms by submissions table
- [ ] Live sessions monitor (forms being filled NOW)
- [ ] Per-form analytics (conversion rate, abandonment, avg time)
- [ ] Field-level analytics (funnel, drop-off, corrections)
- [ ] Abandonment tracking with "last field before abandon" insight
- [ ] Custom tracking configuration per form
- [ ] Privacy-focused (all data local, configurable retention)
- [ ] Requires Phase 1a (sessions table) for live monitoring
- [ ] See [Phase 14 subtask](14-implement-analytics-dashboard.md) for details

### Phase 17: Contact Entries to Custom Table - IN PROGRESS (~75%)
- [x] `wp_superforms_entries` table created with optimized schema (core entry data)
- [x] `wp_superforms_entry_meta` table created (extensible metadata storage)
- [x] `SUPER_Entry_DAL` class with CRUD operations, meta methods, and flexible queries (45 tests)
- [x] Backwards compatibility hooks intercept `get_post()`, `get_post_meta()`, `WP_Query`
- [x] Migration hook integration in `class-background-migration.php`
- [x] Dual-read mode during migration (check both tables based on storage_mode)
- [x] class-ajax.php updated to use Entry DAL for create/update/delete
- [x] Trigger actions updated: update_entry_status, update_entry_field, delete_entry
- [x] Listings queries updated with new column aliases
- [x] REST API endpoints for entries (`/super-forms/v1/entries`) - `class-entry-rest-controller.php`
- [ ] Migration system moves entries from `wp_posts` to custom table (preserves IDs)
- [ ] 30-day retention period before post type cleanup
- [ ] Custom `SUPER_Entries_List_Table` replaces WordPress list table
- [ ] Remaining files: class-shortcodes.php, class-common.php (lower priority - BC layer handles)
- [ ] Extensions updated: Stripe, PayPal, WooCommerce (use entry_meta for IDs)
- [x] Performance: 10x+ improvement on entry list queries (verified via DAL)
- [ ] Zero data loss: All entries migrated with fields and metadata preserved
- [x] See [Phase 17 subtask](17-migrate-entries-to-custom-table.md) for details

### Phase 18: Payment Architecture - PENDING
- [ ] 6 payment-specific database tables (products, prices, coupons, payments, subscriptions, refunds)
- [ ] `SUPER_Payment_Product_DAL` - Product/price management with Stripe/PayPal sync
- [ ] `SUPER_Payment_Coupon_DAL` - Coupon management with validation and usage tracking
- [ ] `SUPER_Payment_DAL` - Payment processing with gateway abstraction
- [ ] `SUPER_Payment_Subscription_DAL` - Subscription lifecycle management
- [ ] Polymorphic payment linking (form, booking, product resources)
- [ ] Unified payment status machine (pending → processing → succeeded/failed → refunded)
- [ ] Stripe Connect and PayPal OAuth flows via `SUPER_Payment_OAuth`
- [ ] 13 payment/subscription events integrated with triggers system
- [ ] Webhook handlers with signature verification
- [ ] See [Phase 18 subtask](18-implement-payment-architecture.md) for details

### Phase 19: Appointments/Booking System - PENDING
- [ ] 15 booking-specific database tables (services, staff, schedules, bookings, customers, etc.)
- [ ] `SUPER_Booking_Service_DAL` - Service management with extras and pricing tiers
- [ ] `SUPER_Booking_Staff_DAL` - Staff management with schedules and breaks
- [ ] `SUPER_Booking_DAL` - Booking CRUD with status management (confirm, cancel, reschedule)
- [ ] `SUPER_Booking_Availability_Engine` - Slot calculation with buffer times and resource constraints
- [ ] `SUPER_Booking_Reminder_DAL` - Reminder scheduling via Action Scheduler
- [ ] `SUPER_Booking_Notification_Manager` - Email/SMS notifications via templates
- [ ] `SUPER_Booking_Calendar_Sync` - Two-way sync with Google Calendar and Outlook
- [ ] Form builder "Booking Calendar" field element with React frontend
- [ ] Recurring appointments with multiple frequencies
- [ ] Group bookings with capacity limits
- [ ] Waitlist management with auto-notification
- [ ] Resource management (rooms, equipment)
- [ ] Multi-location support with timezone handling
- [ ] 17 booking events and 10 booking actions for triggers
- [ ] Integration with Phase 18 payment tables
- [ ] See [Phase 19 subtask](19-implement-appointments-booking.md) for details

## Implementation Order

The phases should be implemented sequentially:

**Completed:**
1. ✅ **Phase 1 (Foundation)** - Backend infrastructure (database, DAL, manager, registry, conditions, executor, REST API)
2. ✅ **Phase 2 (Action Scheduler)** - Async execution layer
3. ✅ **Phase 3 (Execution/Logging)** - Advanced logging and debugging
4. ✅ **Phase 4 (API/Security)** - OAuth and encrypted credentials
5. ✅ **Phase 5 (HTTP Request)** - Most versatile action type
6. ✅ **Phase 6 (Payment Events)** - Integration with payment add-ons
9. ✅ **Phase 9 (Test Fixtures/OAuth)** - Comprehensive test forms, Stripe/PayPal OAuth
10. ✅ **Phase 10 (Payment Webhook Tests)** - Unit tests for payment OAuth/webhook security

**Sessions/Spam/Abort (Complete):**
1a. ✅ **Phase 1a (Sessions/Spam/Abort)** - All 6 steps complete
   - ✅ Step 1: Sessions Table & DAL
   - ✅ Step 2: Client-Side Sessions (auto-save, client token recovery)
   - ✅ Step 3: Spam Detector (honeypot, time, IP, keywords, Akismet)
   - ✅ Step 4: Duplicate Detector (email+time, IP+time, hash, custom fields)
   - ✅ Step 5: Submission Flow Refactor (pre-submission firewall)
   - ✅ Step 6: Cleanup Scheduled Jobs

**Feature Migration (In Progress):**
11. **Phase 11 (Email Migration)** - Migrate Admin/Confirm emails to triggers, integrate Email v2
12. **Phase 12 (WooCommerce Migration)** - Convert WooCommerce checkout to triggers
13. **Phase 13 (FluentCRM)** - New WordPress-native CRM integration

**Database Architecture (In Progress - ~75%):**
17. ⚡ **Phase 17 (Entries Custom Table)** - Core DAL and BC layer complete
   - ✅ Entry DAL with full CRUD + meta methods (45 tests passing)
   - ✅ Backwards compatibility layer active
   - ✅ class-ajax.php, trigger actions, listings updated
   - 🔲 Remaining: background migration, list table replacement

**Future:**
7. **Phase 7 (Additional Add-ons)** - Slack, Google Sheets, HubSpot integrations
8. **Phase 8 (Real-time)** - Client-side triggers, field validation, duplicate detection
14. **Phase 14 (Analytics Dashboard)** - Form analytics, live sessions, field-level insights
1.5. **Admin UI** - Dedicated "Triggers" admin page (deferred)

**Note:** Phase 1 builds complete backend without UI. UI can be implemented as separate phase using REST API.

**Why Phase 17 is Priority:**
- 10x+ query performance improvement for sites with 10K+ entries
- Removes entries from bloated `wp_posts` table
- Enables custom indexes for form-specific queries
- Aligns with Gravity Forms, WPForms, Formidable architecture
- Simplifies codebase (single source of truth vs post+meta+EAV)
- Required foundation for advanced entry management features

**Phase 1a Completed - What It Enabled:**
- ✅ Sessions table enables time-based spam detection (tracks form start time)
- ✅ Pre-submission firewall ensures spam/duplicates blocked BEFORE entry creation
- ✅ Abort flow allows triggers to stop submission
- ✅ Email/WooCommerce migrations now unblocked

## Context Manifest

### How the Current Triggers/Actions System Works

The existing triggers/actions system (in `/src/includes/class-triggers.php`) is a **basic WP-Cron-based email scheduler** that works but has architectural limitations preventing extensibility.

**Current Architecture Overview:**

When a form is submitted, the system processes triggers via hardcoded logic:

1. **Event Detection**: Form submission triggers are hardcoded in form processing code (not hook-based)
2. **Action Execution**: Only one action type exists: `send_email()` method in `SUPER_Triggers` class
3. **Scheduling**: Uses custom WP-Cron (`super_scheduled_trigger_actions`) + custom post type (`sf_scheduled_action`)
4. **Storage**: Scheduled actions stored as WordPress posts with serialized data in `post_content`
5. **Execution**: Cron job queries posts by `_super_scheduled_trigger_action_timestamp` meta key

**Current Data Flow (Email Sending Example):**

```
Form Submission (Frontend)
  ↓
SUPER_Ajax::submit_form() processes submission
  ↓
(Hardcoded) Check for triggers in form settings
  ↓
SUPER_Triggers::send_email($triggerEventParameters)
  ↓
If schedule enabled:
  - Create sf_scheduled_action post
  - Store serialized action data in post_content
  - Store timestamp in _super_scheduled_trigger_action_timestamp meta
  - Store parameters in _super_scheduled_trigger_action_data meta
  ↓
WP-Cron (super_scheduled_trigger_actions) runs every minute
  ↓
SUPER_Triggers::execute_scheduled_trigger_actions()
  - Queries posts by timestamp < current_time
  - Unserializes post_content to get action options
  - Calls hardcoded method (e.g., send_email)
  - Deletes post after sending
```

**Hardcoded Events and Actions:**

The system currently recognizes these events (implicitly, not via registry):
- `sf.after.submission` - After form submission completes
- Payment events (via payment add-ons, not unified)
- Post creation events (via Front-End Posting add-on)

Available actions (all hardcoded methods):
- `send_email()` - Send scheduled emails with loop fields
- `update_contact_entry_status()` - Change entry status
- `update_created_post_status()` - Change post status (can schedule future posts)
- `update_registered_user_login_status()` - Modify user login status (incomplete)
- `update_registered_user_role()` - Change user role (incomplete)

**Critical Limitations (Why Rebuild is Needed):**

1. **No Registry Pattern**: Events/actions exist as hardcoded strings and method names - impossible for add-ons to register new ones
2. **No Action Scheduler Integration**: Uses unreliable WP-Cron instead of bundled Action Scheduler library
3. **No Extensibility Hooks**: Zero `apply_filters()` or `do_action()` hooks for add-ons to tap into
4. **Fragile Scheduling**: Stores execution logic in serialized post_content (PHP object injection risk, debugging nightmare)
5. **No Error Handling**: Failed actions just disappear - no retry, no logging, no debugging info
6. **Single Condition Logic**: Only one condition per trigger (no AND/OR/NOT combinations)
7. **No Execution Logging**: Once action runs, no audit trail exists

**Form Settings Storage:**

Triggers are stored in `_super_form_settings` post meta (NOT separate meta key like listings):

```php
$settings = get_post_meta($form_id, '_super_form_settings', true);
$triggers = $settings['triggers']; // Array of trigger configurations

// Example structure (current):
$settings['triggers'] = array(
  array(
    'event' => 'sf.after.submission',
    'name' => 'Send confirmation email',
    'conditions' => array( /* single condition */ ),
    'actions' => array(
      array(
        'action' => 'send_email', // maps to SUPER_Triggers::send_email()
        'data' => array( /* email options */ )
      )
    )
  )
);
```

**Admin UI Integration:**

The form builder has a "Triggers" tab (line 126 in `page-create-form.php`):

```php
$tabs = array(
  'builder' => 'Builder',
  'emails' => 'Emails',
  'settings' => 'Settings',
  'triggers' => 'Triggers', // ← Existing tab
);
$tabs = apply_filters('super_create_form_tabs', $tabs); // ← Hook for extensions
```

Extensions add tabs via:
```php
add_filter('super_create_form_tabs', array($this, 'add_tab'), 10, 1);
add_action('super_create_form_stripe_tab', array($this, 'add_tab_content'));
```

The triggers UI uses custom JavaScript (see `/src/assets/js/backend/create-form.js` lines 1253, 1351, 1696) to handle trigger configuration. The UI system (`SUPER_UI` class) provides:
- Repeater fields (`data-r` attribute) for dynamic trigger/action rows
- Field grouping (`data-g` attribute) for organized settings
- Filter/toggle logic for conditional UI display
- i18n support for translating trigger configurations

**Existing Hook Points (Minimal):**

The current system has only 3 filter hooks:
- `super_before_sending_email_body_filter` - Modify email body before sending
- `super_before_sending_email_attachments_filter` - Modify attachments array
- `super_before_email_loop_data_filter` - Process individual fields in email loop

**No hooks exist for:**
- Registering new events
- Registering new actions
- Extending conditional logic
- Adding custom UI elements
- Hooking into execution lifecycle

### Payment Add-on Event System (Current State)

Payment add-ons have **their own event handling** - NOT integrated with triggers system.

**Stripe Webhook Flow:**

```php
// /src/includes/extensions/stripe/stripe.php
public static function handle_webhooks($wp) {
  // Listens for query var 'sfstripewebhook=true'
  $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

  switch($event->type) {
    case 'checkout.session.completed':
      // Process payment success
      SUPER_Common::cleanupFormSubmissionInfo($session->metadata->sfsi_id, $event->type);
      break;
    case 'customer.subscription.created':
    case 'customer.subscription.updated':
      self::afterSubscriptionUpdated($event);
      break;
    case 'customer.subscription.deleted':
      self::afterSubscriptionDeleted($event);
      break;
    // ... more events
  }
}
```

**Required Stripe Events** (hardcoded array):
```php
public static $required_events = array(
  'checkout.session.async_payment_failed',
  'checkout.session.async_payment_succeeded',
  'checkout.session.completed',
  'customer.subscription.created',
  'customer.subscription.updated',
  'customer.subscription.deleted',
  'invoice.paid',
  'invoice.payment_failed',
  'payment_intent.succeeded',
  'payment_intent.payment_failed',
);
```

**Subscription Status Actions:**

The Stripe extension updates entry/post status based on subscription state:

```php
// Entry status mapping
$s['subscription']['entry_status'] = array(
  array('status' => 'active', 'value' => 'active'),
  array('status' => 'canceled', 'value' => 'canceled'),
  // ...
);

// Post status mapping
$s['subscription']['post_status'] = array(
  array('status' => 'active', 'value' => 'publish'),
  array('status' => 'canceled', 'value' => 'draft'),
  // ...
);
```

**PayPal Integration:**

PayPal add-on (`/src/add-ons/super-forms-paypal/`) uses similar webhook pattern:
- Custom post type: `super_paypal_txn` for transactions
- Custom post type: `super_paypal_sub` for subscriptions
- IPN (Instant Payment Notification) webhook handler
- No integration with triggers system

**WooCommerce Integration:**

WooCommerce add-on (`/src/add-ons/super-forms-woocommerce/`) hooks into WooCommerce events:
```php
add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_contact_entry_link_to_order'));
add_filter('super_after_contact_entry_data_filter', array($this, 'add_entry_order_link'));
```

Creates WooCommerce orders after form submission but no unified event system.

### Action Scheduler Integration Points

**Already Bundled and Active:**

Action Scheduler v3.9.3 is loaded early in plugin bootstrap:

```php
// /src/super-forms.php line 169-173
if (file_exists(SUPER_PLUGIN_DIR . '/includes/lib/action-scheduler/action-scheduler.php')) {
  require_once SUPER_PLUGIN_DIR . '/includes/lib/action-scheduler/action-scheduler.php';
}
```

**Currently Used For:**

1. **EAV Migration** (`SUPER_Background_Migration`):
   ```php
   // Hook registration
   add_action(self::AS_BATCH_HOOK, array(__CLASS__, 'process_batch_action'));
   add_action(self::AS_HEALTH_CHECK_HOOK, array(__CLASS__, 'health_check_action'));

   // Scheduling
   as_schedule_single_action(time(), self::AS_BATCH_HOOK, $args, 'superforms');
   ```

2. **Cleanup Jobs**:
   ```php
   as_schedule_recurring_action(
     time(),
     300, // Every 5 minutes
     'super_cleanup_old_serialized_data',
     array(),
     'superforms'
   );
   ```

**Action Scheduler Functions Available:**

```php
// Single execution
as_schedule_single_action($timestamp, $hook, $args, $group);

// Recurring execution
as_schedule_recurring_action($timestamp, $interval_seconds, $hook, $args, $group);

// Cancel actions
as_unschedule_action($hook, $args, $group);
as_unschedule_all_actions($hook);

// Query actions
as_get_scheduled_actions($args);
as_next_scheduled_action($hook, $args, $group);
```

**Action Scheduler Tables** (created automatically):
- `wp_actionscheduler_actions` - Queue of scheduled actions
- `wp_actionscheduler_logs` - Execution history and errors
- `wp_actionscheduler_groups` - Action grouping
- `wp_actionscheduler_claims` - Lock mechanism for concurrent processing

**WP-Cron Fallback System:**

The plugin has a fallback for when WP-Cron fails (`SUPER_Cron_Fallback` class):
- Detects stale migration jobs (15 minute threshold)
- Provides 4 processing modes: sync, async, monitor, trigger
- Shows admin notice with "Database Upgrade Required" message
- Enables Action Scheduler async mode when `DISABLE_WP_CRON` detected
- Uses AJAX endpoints for manual trigger and progress polling

This fallback system should be **reused for trigger actions** - if Action Scheduler queue stalls, same detection/remediation applies.

### Data Access Layer Patterns

**CRITICAL**: All contact entry data operations must use `SUPER_Data_Access` abstraction layer.

**Why This Matters:**

The plugin is in the middle of migrating from serialized meta storage to EAV tables. During this transition:
- Some entries stored in `_super_contact_entry_data` meta (serialized)
- Some entries stored in `wp_superforms_entry_data` table (EAV)
- The Data Access Layer abstracts this complexity

**Required Usage Pattern:**

```php
// ✅ CORRECT - Always use Data Access Layer
$entry_data = SUPER_Data_Access::get_entry_data($entry_id);
SUPER_Data_Access::save_entry_data($entry_id, $data);
SUPER_Data_Access::update_entry_field($entry_id, 'field_name', 'new_value');
SUPER_Data_Access::delete_entry_data($entry_id);

// ❌ WRONG - Never access storage directly
$data = get_post_meta($entry_id, '_super_contact_entry_data', true); // Breaks during migration!
update_post_meta($entry_id, '_super_contact_entry_data', $data); // Doesn't write to EAV!
```

**Data Access Layer Methods:**

```php
class SUPER_Data_Access {
  // Get entry data (auto-detects EAV or serialized)
  public static function get_entry_data($entry_id);

  // Save entry data (dual-write during migration, EAV-only after)
  public static function save_entry_data($entry_id, $data, $force_format = null);

  // Update single field (more efficient than full save)
  public static function update_entry_field($entry_id, $field_name, $field_value);

  // Delete entry data (from both storage methods)
  public static function delete_entry_data($entry_id);

  // Internal methods (don't call directly)
  private static function get_from_eav_tables($entry_id);
  private static function get_from_serialized($entry_id);
  private static function save_to_eav_tables($entry_id, $data);
  private static function save_to_serialized($entry_id, $data);
}
```

**Migration State Detection:**

```php
$migration = get_option('superforms_eav_migration');
// States: 'not_started', 'in_progress', 'completed'
// Storage: 'serialized' or 'eav'

// Auto-routing based on state:
// - not_started: serialized only
// - in_progress: dual-write (both serialized + EAV)
// - completed (eav): EAV only
// - completed (serialized): rolled back to serialized
```

**Entry Data Structure:**

```php
// Structure returned by get_entry_data():
array(
  'field_name' => array(
    'name' => 'field_name',
    'value' => 'field value',
    'label' => 'Field Label',
    'type' => 'text',
    'exclude' => 0, // 0=include, 1=attachment only, 2=exclude all, 3=exclude admin
  ),
  // ... more fields
)
```

**Trigger Actions Must Use Data Access Layer:**

When implementing actions that read/write entry data:

```php
// Example: Update entry field action
public static function update_entry_field_action($params) {
  $entry_id = $params['entry_id'];
  $field_name = $params['field_name'];
  $new_value = $params['value'];

  // ✅ Use Data Access Layer
  SUPER_Data_Access::update_entry_field($entry_id, $field_name, $new_value);

  // ❌ DON'T do this
  // $data = get_post_meta($entry_id, '_super_contact_entry_data', true);
  // $data[$field_name]['value'] = $new_value;
  // update_post_meta($entry_id, '_super_contact_entry_data', $data);
}
```

### Database Schema Patterns

**Existing EAV Table** (for contact entries):

```sql
CREATE TABLE wp_superforms_entry_data (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id BIGINT(20) UNSIGNED NOT NULL,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  field_name VARCHAR(255) NOT NULL,
  field_value LONGTEXT,
  field_type VARCHAR(50),
  field_label VARCHAR(255),
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY entry_id (entry_id),
  KEY form_id (form_id),
  KEY field_name (field_name),
  KEY entry_field (entry_id, field_name),
  KEY field_value (field_value(191)),
  KEY form_field_filter (form_id, field_name, field_value(191)),
  KEY form_entry_field (form_id, entry_id, field_name)
) ENGINE=InnoDB;
```

**Table Creation Pattern** (from `SUPER_Install`):

```php
private static function create_tables() {
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $table_name = $wpdb->prefix . 'superforms_entry_data';
  $sql = "CREATE TABLE $table_name (...) ENGINE=InnoDB $charset_collate;";

  dbDelta($sql); // WordPress function that handles CREATE/ALTER intelligently

  // Run schema upgrades for existing installations
  self::upgrade_database_schema();
}
```

**Schema Upgrade Pattern** (safe for existing installs):

```php
private static function upgrade_database_schema() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'superforms_entry_data';

  // Check if table exists
  $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
  if ($table_exists !== $table_name) return;

  // Check if column exists before adding
  $column_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'form_id'",
    DB_NAME, $table_name
  ));

  if (!$column_exists) {
    $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN form_id BIGINT(20)...");
  }
}
```

**Installation Hooks:**

```php
// Plugin activation
register_activation_hook(__FILE__, array('SUPER_Install', 'install'));

// Plugin deactivation
register_deactivation_hook(__FILE__, array('SUPER_Install', 'deactivate'));
```

**Required Tables for New System:**

Based on the subtask requirements, we'll need:

1. `wp_superforms_triggers` - Trigger configuration registry
2. `wp_superforms_trigger_logs` - Execution history and debugging
3. `wp_superforms_api_credentials` - Encrypted API key storage
4. `wp_superforms_http_logs` - HTTP request/response logging

These should follow the same patterns as the entry_data table.

### Form Builder Integration Patterns

**Tab Registration System:**

Extensions add tabs to the form builder via filter + action pattern:

```php
// 1. Add tab to tabs array
add_filter('super_create_form_tabs', array($this, 'add_tab'), 10, 1);
public static function add_tab($tabs) {
  $tabs['stripe'] = esc_html__('Stripe', 'super-forms');
  return $tabs;
}

// 2. Provide tab content
add_action('super_create_form_stripe_tab', array($this, 'add_tab_content'));
public static function add_tab_content($atts) {
  // $atts contains: form_id, settings, woocommerce, listings, pdf, stripe, etc.
  $s = $atts['stripe']; // Settings for this tab

  // Render UI using SUPER_UI helper or custom HTML
  echo '<div class="super-stripe-settings">...</div>';
}
```

**Settings Storage Pattern:**

Most extensions store settings in separate meta keys (NOT in `_super_form_settings`):

```php
// Listings uses separate meta key
$listings = get_post_meta($form_id, '_listings', true);

// WooCommerce uses separate meta key
$woocommerce = get_post_meta($form_id, '_woocommerce', true);

// Stripe uses separate meta key
$stripe = get_post_meta($form_id, '_stripe', true);

// PDF uses separate meta key
$pdf = get_post_meta($form_id, '_pdf', true);

// BUT: Triggers currently stored in main settings
$settings = get_post_meta($form_id, '_super_form_settings', true);
$triggers = $settings['triggers'];
```

**Decision Point**: Should new trigger system use separate `_triggers` meta key or remain in `_super_form_settings`?

**Recommendation**: Use separate `_triggers` meta key because:
1. Consistent with other extensions (listings, stripe, pdf, woocommerce)
2. Allows for complex nested structures without bloating main settings
3. Easier to version and migrate independently
4. Better for i18n translation mapping (see Listings migration context)

**UI Rendering Helper** (`SUPER_UI` class):

```php
// Loop over settings nodes and render fields
SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);

// Key attributes for field organization:
// - data-g="group_name" - Group fields together
// - data-r="repeater_name" - Repeater field for dynamic rows
// - data-f="filter_condition" - Conditional display logic

// Example structure:
$nodes = array(
  array(
    'group' => true,
    'group_name' => 'email_settings',
    'nodes' => array(
      array('name' => 'to', 'type' => 'text', 'label' => 'To'),
      array('name' => 'subject', 'type' => 'text', 'label' => 'Subject'),
    )
  ),
  array(
    'type' => 'repeater',
    'name' => 'actions',
    'nodes' => array(
      array('name' => 'action_type', 'type' => 'dropdown'),
      array('name' => 'action_data', 'type' => 'textarea'),
    )
  )
);
```

**JavaScript Integration:**

Form builder JavaScript (`/src/assets/js/backend/create-form.js`) handles:

```javascript
// Get settings for a specific tab
SUPER.get_tab_settings({}, 'triggers', undefined, undefined, returnData);

// Special handling for triggers/stripe/listings/woocommerce/pdf tabs
if (slug === 'triggers' || slug === 'stripe') {
  // These tabs have special serialization logic
}

// i18n translation support
if (SUPER.ui.i18n.translating && slug === 'triggers') {
  // Load translated version of settings
}

// Save form data
params.form_data.triggers = JSON.parse(params.form_data.triggers);
```

**Filter System for Conditional UI:**

```html
<!-- Show field only when specific condition met -->
<div class="sfui-setting" data-f='{"field":"payment_enabled","value":"true"}'>
  <!-- Field only visible when payment_enabled = true -->
</div>

<!-- Multiple conditions (JSON array) -->
<div data-f='[{"field":"type","value":"stripe"},{"field":"mode","value":"test"}]'>
  <!-- AND logic: show when type=stripe AND mode=test -->
</div>
```

JavaScript evaluates `data-f` attributes to show/hide fields dynamically.

### Admin Settings and Global Configuration

**Global Settings Access:**

```php
// Get global plugin settings
$global_settings = SUPER_Settings::get_settings();

// Specific setting access
$setting_value = !empty($global_settings['setting_key']) ? $global_settings['setting_key'] : 'default';

// Example: Stripe global webhook secret
$endpoint_secret = $global_settings['stripe_' . $mode . '_webhook_secret'];
```

**Settings Structure:**

Settings are organized in tabs (similar to form builder):

```php
// /src/includes/class-settings.php
public static function get_settings() {
  $settings = get_option('super_settings');
  if (!$settings) {
    $settings = self::get_defaults();
  }
  return $settings;
}

public static function get_defaults() {
  return array(
    'smtp_enabled' => '',
    'smtp_host' => '',
    'file_upload_remove_from_media' => 'yes',
    // ... hundreds of settings
  );
}
```

**Adding Global Settings:**

Extensions can add settings to global settings page:

```php
add_filter('super_settings_after_custom_js_filter', array($this, 'add_settings'), 10, 2);

public static function add_settings($html, $settings) {
  $html .= '<div class="super-settings-stripe">';
  $html .= '<h3>Stripe Settings</h3>';
  // Render settings fields
  $html .= '</div>';
  return $html;
}
```

**Secrets Management:**

The plugin has a "Secrets" tab for sensitive data storage:

```php
// Form-level secrets
$localSecrets = get_post_meta($form_id, '_super_form_secrets', true);

// Global secrets (shared across all forms)
$globalSecrets = get_option('super_global_secrets');

// Structure:
array(
  'stripe_secret_key' => 'sk_test_...',
  'api_token' => 'token_...',
  // Stored as plain text in database - NOT encrypted currently
)
```

**SECURITY CONCERN**: Current secrets are stored as plain text in the database. The new system should implement proper encryption for API credentials.

### Hook and Filter Registration Patterns

**WordPress Hook Conventions:**

```php
// Action hooks - do something at specific point
do_action('super_before_save_form', $form_id, $settings);
add_action('super_before_save_form', 'my_callback', 10, 2);

// Filter hooks - modify data
$settings = apply_filters('super_form_settings_filter', $settings, $form_id);
add_filter('super_form_settings_filter', 'my_callback', 10, 2);
```

**Naming Pattern in Super Forms:**

```php
// Core plugin hooks - prefix with 'super'
do_action('super_loaded');
do_action('super_after_contact_entry_saved', $entry_id, $form_id);
apply_filters('super_before_sending_email_body_filter', $body, $context);

// Extension-specific hooks - prefix with extension name
do_action('super_stripe_loaded');
do_action('super_woocommerce_order_created', $order_id);
```

**Registration Pattern for Extensibility:**

For the new trigger/action system to be extensible, we need:

```php
// ✅ Proposed registry pattern
class SUPER_Trigger_Registry {
  private static $events = array();
  private static $actions = array();

  // Allow add-ons to register events
  public static function register_event($event_name, $args) {
    self::$events[$event_name] = $args;
    do_action('super_trigger_event_registered', $event_name, $args);
  }

  // Allow add-ons to register actions
  public static function register_action($action_name, $callback, $args) {
    self::$actions[$action_name] = array(
      'callback' => $callback,
      'args' => $args,
    );
    do_action('super_trigger_action_registered', $action_name, $args);
  }

  // Fire event and execute matching triggers
  public static function fire_event($event_name, $context) {
    do_action('super_before_trigger_event', $event_name, $context);

    // Find triggers for this event
    // Execute matching actions

    do_action('super_after_trigger_event', $event_name, $context);
  }
}

// Usage by add-ons:
add_action('plugins_loaded', 'my_addon_register_triggers');
function my_addon_register_triggers() {
  // Register custom event
  SUPER_Trigger_Registry::register_event('my_addon.custom_event', array(
    'label' => 'Custom Event Occurred',
    'description' => 'Fires when something happens',
    'context_fields' => array('field1', 'field2'),
  ));

  // Register custom action
  SUPER_Trigger_Registry::register_action('my_addon.custom_action',
    array('My_Addon', 'execute_action'),
    array(
      'label' => 'Do Custom Action',
      'fields' => array(/* UI fields */),
    )
  );
}
```

**Action Priority and Ordering:**

WordPress hooks support priority (default 10):

```php
add_action('super_trigger_event', 'low_priority_callback', 5);
add_action('super_trigger_event', 'default_priority_callback', 10);
add_action('super_trigger_event', 'high_priority_callback', 20);
```

The new trigger system should support action ordering within a trigger (not just hook priority).

### Error Handling and WP_Error Pattern

**WordPress Error Convention:**

```php
// Return WP_Error on failure
function my_function($entry_id) {
  if (!$entry_id) {
    return new WP_Error(
      'invalid_entry_id',
      __('Invalid entry ID provided', 'super-forms'),
      array('entry_id' => $entry_id) // Optional data
    );
  }

  // Check for errors
  if (is_wp_error($result)) {
    error_log('Error: ' . $result->get_error_message());
    $error_data = $result->get_error_data();
  }

  return $result;
}
```

**Data Access Layer Example:**

```php
// From SUPER_Data_Access class
public static function get_entry_data($entry_id) {
  if (empty($entry_id) || !is_numeric($entry_id)) {
    return new WP_Error('invalid_entry_id', __('Invalid entry ID', 'super-forms'));
  }

  $post = get_post($entry_id);
  if (!$post || $post->post_type !== 'super_contact_entry') {
    return new WP_Error('entry_not_found', __('Entry not found', 'super-forms'));
  }

  return $data;
}
```

**Trigger Actions Should Use WP_Error:**

```php
public static function execute_action($action_name, $context) {
  if (!isset(self::$actions[$action_name])) {
    return new WP_Error(
      'action_not_found',
      sprintf(__('Action "%s" not registered', 'super-forms'), $action_name),
      array('action' => $action_name, 'registered_actions' => array_keys(self::$actions))
    );
  }

  $callback = self::$actions[$action_name]['callback'];
  $result = call_user_func($callback, $context);

  if (is_wp_error($result)) {
    // Log error
    self::log_action_error($action_name, $result);
    return $result;
  }

  return $result;
}
```

### Critical Integration Points

**Form Submission Hook Points:**

The form submission flow is where triggers must be integrated:

```php
// /src/includes/class-ajax.php - submit_form() method
// Current flow (simplified):
1. Validate form data
2. Save contact entry (wp_insert_post)
3. Save entry data (SUPER_Data_Access::save_entry_data)
4. Process payment (if enabled)
5. Send emails (if configured)
6. Execute triggers (hardcoded check)
7. Redirect or show message

// Where to fire events:
do_action('super_before_save_contact_entry', $data, $settings);
do_action('super_after_save_contact_entry', $entry_id, $data, $settings);
do_action('super_before_payment_processing', $entry_id, $payment_data);
do_action('super_after_payment_success', $entry_id, $payment_data);
do_action('super_after_payment_failed', $entry_id, $error);
```

**Payment Integration Points:**

```php
// Stripe webhook events → trigger events
add_action('super_stripe_checkout_completed', function($session_data) {
  SUPER_Trigger_Registry::fire_event('payment.stripe.completed', array(
    'entry_id' => $session_data['entry_id'],
    'amount' => $session_data['amount'],
    'currency' => $session_data['currency'],
    'session' => $session_data,
  ));
});

// Similar for subscription events
add_action('super_stripe_subscription_created', function($sub_data) {
  SUPER_Trigger_Registry::fire_event('subscription.stripe.created', $sub_data);
});
```

**Email Reminder Pattern (Migrate to Action Scheduler):**

The Email Reminders add-on uses WP-Cron - should migrate to Action Scheduler pattern:

```php
// Current (WP-Cron):
if (!wp_next_scheduled('super_cron_reminders')) {
  wp_schedule_event(time(), 'every_minute', 'super_cron_reminders');
}

// New (Action Scheduler):
as_schedule_recurring_action(
  time(),
  60, // Every minute
  'super_process_scheduled_triggers',
  array(),
  'superforms_triggers'
);
```

**Background Processing Pattern:**

From the migration system:

```php
// Schedule batch processing
public static function schedule_batch() {
  $timestamp = time() + 5; // Start in 5 seconds

  as_schedule_single_action(
    $timestamp,
    self::AS_BATCH_HOOK, // 'superforms_migrate_batch'
    array('batch_size' => 50),
    'superforms' // Group name
  );
}

// Process batch
public static function process_batch_action() {
  // Acquire lock to prevent concurrent execution
  $lock = get_transient(self::LOCK_KEY);
  if ($lock) return; // Another batch is running

  set_transient(self::LOCK_KEY, time(), self::LOCK_DURATION);

  try {
    // Do work
    self::process_entries(50);

    // Schedule next batch if more work remains
    if (self::has_more_work()) {
      self::schedule_batch();
    }
  } finally {
    delete_transient(self::LOCK_KEY);
  }
}
```

**Retry and Error Handling:**

Action Scheduler has built-in retry:

```php
// Failed actions are automatically retried
// Configure retry strategy via Action Scheduler settings

// Check if action failed
$action_id = as_next_scheduled_action('my_hook');
if ($action_id) {
  $logs = as_get_action_logs($action_id);
  foreach ($logs as $log) {
    if ($log->get_message_type() === 'error') {
      // Handle error
    }
  }
}
```

### Technical Reference Details

**Required Function Signatures:**

```php
// Event registration
SUPER_Trigger_Registry::register_event(
  string $event_id,           // 'payment.stripe.succeeded'
  array $args = array(
    'label' => string,        // 'Stripe Payment Succeeded'
    'description' => string,  // 'Fires when payment completes'
    'context' => array(),     // Available data fields
    'category' => string,     // 'payment', 'form', 'user', etc.
  )
): void

// Action registration
SUPER_Trigger_Registry::register_action(
  string $action_id,          // 'http.request'
  callable $callback,         // Array($class, $method) or function
  array $args = array(
    'label' => string,        // 'HTTP Request'
    'description' => string,  // 'Make HTTP API call'
    'fields' => array(),      // UI field definitions
    'category' => string,     // 'integration', 'notification', etc.
    'async' => bool,          // Execute via Action Scheduler?
  )
): void

// Fire event
SUPER_Trigger_Registry::fire_event(
  string $event_id,           // 'form.submitted'
  array $context = array()    // Event data
): array // Returns array of execution results

// Execute action
SUPER_Trigger_Executor::execute_action(
  string $action_id,          // 'send.email'
  array $action_config,       // Configuration from trigger
  array $context              // Event context data
): bool|WP_Error
```

**Database Table Schemas:**

```sql
-- Trigger configuration (replaces post type storage)
CREATE TABLE wp_superforms_triggers (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  event_id VARCHAR(100) NOT NULL,
  trigger_name VARCHAR(255) NOT NULL,
  enabled TINYINT(1) DEFAULT 1,
  conditions TEXT,              -- JSON encoded conditions
  actions TEXT,                 -- JSON encoded actions
  priority INT DEFAULT 10,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY form_id (form_id),
  KEY event_id (event_id),
  KEY enabled (enabled)
) ENGINE=InnoDB;

-- Execution logging
CREATE TABLE wp_superforms_trigger_logs (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  trigger_id BIGINT(20) UNSIGNED NOT NULL,
  entry_id BIGINT(20) UNSIGNED,
  event_id VARCHAR(100) NOT NULL,
  action_id VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL,  -- 'success', 'failed', 'pending'
  error_message TEXT,
  execution_time FLOAT,         -- In seconds
  context_data LONGTEXT,        -- JSON
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY trigger_id (trigger_id),
  KEY entry_id (entry_id),
  KEY status (status),
  KEY created_at (created_at)
) ENGINE=InnoDB;

-- API credentials storage
CREATE TABLE wp_superforms_api_credentials (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  credential_name VARCHAR(100) NOT NULL,
  credential_type VARCHAR(50) NOT NULL, -- 'api_key', 'oauth2', 'basic_auth'
  credential_data TEXT,                 -- Encrypted JSON
  form_id BIGINT(20) UNSIGNED,          -- NULL = global
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY credential_name (credential_name),
  KEY form_id (form_id)
) ENGINE=InnoDB;
```

**File Structure:**

```
/src/includes/
  class-trigger-registry.php       - Event/action registration
  class-trigger-executor.php       - Execution engine
  class-trigger-logger.php         - Logging and debugging
  class-trigger-conditions.php     - Conditional logic engine
  class-api-credentials.php        - Encrypted credential storage

  /triggers/
    class-base-action.php          - Abstract base class
    /actions/
      class-action-http-request.php
      class-action-send-email.php
      class-action-webhook.php
    /events/
      class-event-form-submitted.php
      class-event-payment-completed.php
```

**Action Scheduler Usage:**

```php
// Schedule action execution
as_schedule_single_action(
  $timestamp,                          // When to execute
  'superforms_execute_trigger_action', // Hook name
  array(
    'trigger_id' => $trigger_id,
    'action_id' => $action_id,
    'context' => $context,
  ),
  'superforms_triggers'                // Group for organization
);

// Register hook handler
add_action('superforms_execute_trigger_action', array('SUPER_Trigger_Executor', 'execute_scheduled_action'), 10, 1);

// Query scheduled actions
$pending = as_get_scheduled_actions(array(
  'hook' => 'superforms_execute_trigger_action',
  'status' => 'pending',
  'group' => 'superforms_triggers',
));
```

## Data Architecture

### Core Concepts: Sessions, Submissions, and Entries

Super Forms uses a **three-concept architecture** that differs from most WordPress form plugins. This design enables cleaner data, better spam blocking, and flexible analytics.

| Concept | Purpose | Storage | Lifecycle |
|---------|---------|---------|-----------|
| **Sessions** | Temporary form-filling state | `wp_superforms_sessions` table | Created on first field focus → auto-saved on blur → recovered via client token → completed/abandoned/expired |
| **Submissions** | The act of submitting (immutable audit trail) | Implicit in flow, tracked via `wp_superforms_trigger_logs` | Single event during form submission |
| **Entries** | CRM data (the saved form data) | `super_contact_entry` post type + EAV tables | Created ONLY after spam/duplicate checks pass |

### Why This Architecture?

**Industry Standard Approach (Gravity Forms, WPForms, Formidable):**
```
User starts form → Draft Entry created → User submits → Entry status = "Completed"
                                                      → Spam detected? Entry status = "Spam"
```
- Draft IS an entry, just with different status
- Spam entries exist in your CRM data (even if filtered)
- No separation between form interaction and business data

**Super Forms Approach:**
```
User focuses field → Session created (temporary)
User fills form → Session auto-saved (recoverable)
User submits → Pre-submission firewall runs
            → Spam detected? Session marked "aborted", NO Entry created
            → Checks pass? Entry created (clean CRM data)
```

### Advantages of This Design

1. **Cleaner CRM Data:** Entries = legitimate submissions only (no spam polluting your data)
2. **Better Spam Blocking:** Can abort BEFORE any permanent record exists
3. **Flexible Sessions:** Sessions can expire/cleanup without affecting entry counts
4. **Analytics Separation:** Sessions track form interaction, entries track business data
5. **Storage Efficiency:** Spam sessions can be purged without entry cleanup
6. **Pre-submission Firewall:** Triggers can abort submission BEFORE entry creation

### Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ FORM INTERACTION (Sessions)                                      │
├─────────────────────────────────────────────────────────────────┤
│ 1. User loads form                                               │
│ 2. User focuses first field → SESSION CREATED (draft)           │
│ 3. User fills fields → SESSION AUTO-SAVED (on blur)             │
│    └─ Recoverable if browser crashes                            │
│ 4. User clicks Submit                                            │
└─────────────────────────────────────────────────────────────────┘
                               ↓
┌─────────────────────────────────────────────────────────────────┐
│ PRE-SUBMISSION FIREWALL                                          │
├─────────────────────────────────────────────────────────────────┤
│ 5. Spam detection (honeypot, time, IP, keywords, Akismet)       │
│ 6. Duplicate detection (email+time, IP+time, hash)              │
│ 7. Triggers can fire ABORT action                                │
│                                                                   │
│ [ABORT CHECKPOINT]                                               │
│ If spam/duplicate → Session marked "aborted", files cleaned      │
│                   → NO ENTRY CREATED, return message to user     │
└─────────────────────────────────────────────────────────────────┘
                               ↓ (only if checks pass)
┌─────────────────────────────────────────────────────────────────┐
│ ENTRY CREATION (CRM Data)                                        │
├─────────────────────────────────────────────────────────────────┤
│ 8. Session marked "completed"                                    │
│ 9. Entry created (super_contact_entry post type)                │
│ 10. Data saved via SUPER_Data_Access (EAV tables)               │
│ 11. Files moved to permanent storage                             │
│ 12. Post-submission triggers fire (emails, webhooks, etc.)      │
└─────────────────────────────────────────────────────────────────┘
                               ↓
┌─────────────────────────────────────────────────────────────────┐
│ AUDIT TRAIL (Trigger Logs)                                       │
├─────────────────────────────────────────────────────────────────┤
│ All events logged in wp_superforms_trigger_logs:                │
│ - session.started, session.auto_saved, session.completed        │
│ - form.spam_detected, form.duplicate_detected                   │
│ - entry.created, entry.saved                                    │
│ - form.submitted (final immutable audit point)                  │
└─────────────────────────────────────────────────────────────────┘
```

### Session Status Values

| Status | Description | Can Recover? |
|--------|-------------|--------------|
| `draft` | User actively filling form | Yes |
| `submitting` | Submit in progress | No |
| `completed` | Successfully submitted, entry created | No |
| `aborted` | Blocked by spam/duplicate detection | No |
| `abandoned` | No activity for 30+ minutes | Yes |
| `expired` | Session exceeded 24-hour lifetime | No |

## Terminology Decisions

### UI Terminology vs Code Terminology

| Concept | UI/User-Facing | Code/Database |
|---------|----------------|---------------|
| Incomplete form progress | "Draft" or "Saved Progress" | Session |
| Saved form data | "Entry" | Entry / Contact Entry |
| Recovery prompt | "Resume your form?" | Session recovery |
| Admin data view | "Entries" (menu label) | super_contact_entry |

**Rationale:**
- Users familiar with Gravity Forms, WPForms, Formidable expect "Entries"
- "Draft" is universally understood for incomplete work
- "Session" is implementation detail users don't need to know
- Recovery UX: "You have unsaved progress. Resume?" (not "Resume session")

### Naming Conventions

**Current:** `super_contact_entry` post type, "Contact Entries" menu
**Recommendation:** Simplify to "Entries" in UI

| Location | Current | Recommended |
|----------|---------|-------------|
| Admin menu | "Contact Entries" | "Entries" |
| Post type slug | `super_contact_entry` | Keep (BC) |
| User-facing | "Your submission" | "Your entry" or "Your submission" |
| Recovery prompt | N/A | "Resume your draft?" |

### Future Consideration: Entry Notes

Like Gravity Forms' `wp_gf_entry_notes` table, consider adding admin notes capability:

```sql
CREATE TABLE wp_superforms_entry_notes (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED NOT NULL,
  note_type VARCHAR(50) DEFAULT 'user', -- user, system, notification
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY entry_id (entry_id)
) ENGINE=InnoDB;
```

Use cases:
- Admin adds follow-up note: "Called customer, resolved issue"
- System note: "Email bounced - invalid address"
- Notification tracking: "Reminder sent on 2025-01-15"

## Use Cases and Examples

### Use Case 1: Contact Form with Spam Protection

**Scenario:** Simple contact form, high spam volume

**Configuration:**
- Honeypot field (always enabled)
- Time-based detection: minimum 3 seconds
- Keyword filter: common spam words
- On spam detected: Abort submission

**Flow:**
```
Bot submits in 0.5 seconds with honeypot filled
→ Session exists (created on field focus by bot script)
→ Spam detected: time < 3s AND honeypot filled
→ form.spam_detected event fires
→ Abort Submission action executes
→ Session marked "aborted"
→ NO entry created
→ Generic message shown (don't reveal detection)
```

### Use Case 2: Multi-Step Application with Save Progress

**Scenario:** Job application, 5 steps, users need to save and continue later

**Configuration:**
- Auto-save enabled (every field blur)
- Session recovery enabled
- 7-day session expiry (custom)

**Flow:**
```
Day 1: User fills steps 1-3, closes browser
→ Session created on first focus
→ Data auto-saved after each field
→ Session status: "draft"

Day 3: User returns to form
→ Check for recoverable session (by user ID or IP)
→ "Resume your draft?" prompt shown
→ User clicks "Resume"
→ Form populated with saved data
→ Session.resumed event fires

Day 3: User completes form
→ Pre-submission checks pass
→ Entry created
→ Session marked "completed"
```

### Use Case 3: Payment Form with Duplicate Prevention

**Scenario:** Registration form with payment, prevent double charges

**Configuration:**
- Duplicate detection: email + 10 minute window
- On duplicate: Abort with custom message

**Flow:**
```
User submits registration with payment
→ Entry created, payment processed
→ User clicks back button and submits again
→ Duplicate detected (same email within 10 min)
→ form.duplicate_detected event fires
→ Abort Submission action executes
→ "You've already registered. Check your email for confirmation."
→ NO second entry, NO double charge
```

### Use Case 4: Lead Form with CRM Integration

**Scenario:** Lead capture form syncs to HubSpot CRM

**Configuration:**
- Trigger on: form.submitted
- Action: HTTP Request to HubSpot API
- Retry: 3 attempts on failure

**Flow:**
```
User submits lead form
→ Pre-submission checks pass
→ Entry created in Super Forms
→ form.submitted event fires
→ HTTP Request action queued (async)
→ Action Scheduler executes request
→ Success: Lead created in HubSpot
→ Failure: Retry with exponential backoff
→ All attempts logged in trigger_logs
```

### Use Case 5: Conditional Email Based on Form Data

**Scenario:** Send different emails based on selected department

**Configuration:**
- Trigger 1: form.submitted WHERE department = "Sales"
  - Action: Send email to sales@company.com
- Trigger 2: form.submitted WHERE department = "Support"
  - Action: Send email to support@company.com

**Flow:**
```
User selects "Support" department
→ Entry created
→ form.submitted fires
→ Trigger 1 conditions: department = "Sales" → FALSE, skip
→ Trigger 2 conditions: department = "Support" → TRUE
→ Send email action executes → support@company.com receives
```

## Future Ideas

The following are conceptual ideas for future development, documented here for planning purposes.

### Future Phase 15: Ticket System Add-on

**Concept:** Transform form submissions into support tickets, managed via triggers.

**Architecture:**
- Separate from Entries (tickets have different lifecycle: open → in progress → resolved → closed)
- Custom post type: `super_ticket` or custom table
- Ticket-specific fields: priority, assignee, status, due date, SLA
- Thread/conversation support (multiple replies per ticket)

**Trigger Integration:**
```
Event: form.submitted (support form)
Conditions: form_id = 123 (support request form)
Actions:
  1. Create Ticket (new action type)
     - Title: {subject}
     - Description: {message}
     - Priority: {priority_field}
     - Assignee: Auto (round-robin or by department)
  2. Send Email to customer (ticket created confirmation)
  3. Send Email to assignee (new ticket notification)
```

**Ticket Events (for further automation):**
- `ticket.created`
- `ticket.assigned`
- `ticket.status_changed`
- `ticket.replied` (customer or agent)
- `ticket.resolved`
- `ticket.reopened`
- `ticket.escalated` (SLA breach)

**UI Considerations:**
- Dedicated "Tickets" admin page
- Kanban view (by status)
- List view with filters
- Individual ticket view with conversation thread
- Customer portal (view own tickets, add replies)

### Future Phase 16: Booking System Add-on

**Concept:** Enable appointment/booking forms with calendar integration.

**Architecture:**
- Bookings table with time slots
- Integration with Google Calendar, Outlook
- Availability rules (business hours, blocked dates)
- Resource management (rooms, staff, equipment)

**Trigger Integration:**
```
Event: booking.created
Actions:
  1. Send Email confirmation to customer
  2. Send Email notification to provider
  3. HTTP Request → Create Google Calendar event
  4. HTTP Request → Send SMS reminder (via Twilio)

Event: booking.reminder (scheduled 24h before)
Actions:
  1. Send Email reminder to customer
  2. Send SMS reminder to customer

Event: booking.cancelled
Actions:
  1. Send Email to customer (cancellation confirmed)
  2. HTTP Request → Delete Google Calendar event
  3. Update availability (free up slot)
```

**Booking Events:**
- `booking.created`
- `booking.confirmed` (if approval required)
- `booking.reminder` (scheduled)
- `booking.started` (appointment time reached)
- `booking.completed`
- `booking.cancelled`
- `booking.rescheduled`
- `booking.no_show`

**UI Considerations:**
- Calendar view for availability
- Form field: Date/time picker with live availability
- Admin calendar view (all bookings)
- Provider dashboard (my bookings)
- Customer portal (my appointments)

### Future Phase 17: Entries to Custom Tables Migration

**Concept:** Migrate from `super_contact_entry` post type to dedicated custom tables for better performance.

**Current Architecture:**
```
wp_posts (post_type = 'super_contact_entry')
  └── wp_superforms_entry_data (EAV table via SUPER_Data_Access)
```

**Proposed Architecture:**
```
wp_superforms_entries (dedicated table)
  ├── id, form_id, user_id, status, ip, created_at, updated_at
  └── wp_superforms_entry_data (unchanged EAV)
```

**Benefits:**
- Faster queries (no post type filtering)
- Custom indexes for form-specific queries
- Reduced wp_posts table bloat
- Align with Gravity Forms, WPForms architecture

**Migration Considerations:**
- Similar to EAV migration pattern already implemented
- Backwards compatibility layer for existing integrations
- Gradual migration with dual-read support

### Future Phase 18: Entry Notes and Activity Log

**Concept:** Add admin notes and activity tracking to entries.

**Features:**
- Manual admin notes (like Gravity Forms)
- Automatic activity log (who viewed, edited, exported)
- Email delivery tracking (opened, bounced)
- Integration history (CRM sync status)

**Database:**
```sql
CREATE TABLE wp_superforms_entry_notes (
  id BIGINT(20) UNSIGNED AUTO_INCREMENT,
  entry_id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED,
  note_type ENUM('note', 'system', 'email', 'integration'),
  content TEXT NOT NULL,
  metadata JSON,  -- For structured data (email status, API response)
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY entry_id (entry_id),
  KEY note_type (note_type)
);
```

### Future Phase 19: Advanced Entry Management

**Concept:** Enhanced entry list view and bulk operations.

**Features:**
- Customizable columns per form
- Saved filters/views
- Bulk actions via triggers
- Entry comparison (diff view)
- Entry versioning (track changes)
- Export templates (custom field selection)

### Future Phase 20: Form Analytics v2

**Concept:** Advanced analytics beyond Phase 14 dashboard.

**Features:**
- A/B testing support
- Conversion funnel visualization
- Field-level drop-off analysis
- Heatmaps (time spent per field)
- Predictive abandonment (ML-based)
- Integration with Google Analytics 4

## User Notes

### Architecture Considerations
- **No Backward Compatibility Required**: System unreleased, clean slate implementation
- **Dedicated Admin Page**: Triggers managed through "Super Forms > Triggers" menu (NOT form builder tabs)
- **Form Builder Link**: Add "Triggers (3)" link in forms list table pointing to dedicated page
- **Database Storage**: Triggers in custom tables (NOT post meta) for performance and flexibility
- **Scope System**: Support form/global/user/role scopes from day one
- **Action Scheduler Already Bundled**: v3.9.3 at `/src/includes/lib/action-scheduler/`
- **Existing Payment Add-ons**: PayPal, Stripe, WooCommerce integrations already exist
- **Data Layer Architecture**: Use `SUPER_Data_Access` layer for all entry data operations, NOT direct `update_post_meta`
- **Background Processing**: Use Action Scheduler for ALL background tasks (no WP-Cron)
- **REST API First**: Build REST API in Phase 1, UI consumes it in Phase 1.5
- **Permissions**: Start simple (`manage_options` only), enhance later

### Development Guidelines
- Follow WordPress coding standards
- Use WordPress APIs where possible (wpdb, options, etc.)
- **Entry Data Storage**: Always use `SUPER_Data_Access::update_entry_data()` for storing contact entry data
- **Scheduled Tasks**: All background/scheduled tasks must use Action Scheduler, not WP-Cron
- Ensure PHP 7.4+ compatibility
- Write unit tests for all new functionality
- Document all hooks and filters for developers

### Impact on Existing Features

The new triggers/actions system will affect several existing features:

1. **Form Submissions**
   - Current: Direct email sending and basic actions
   - Enhanced: Event-driven architecture with retry capability
   - Migration: Existing forms continue working, new features opt-in

2. **Email Notifications**
   - Current: Synchronous sending during form submission
   - Enhanced: Queued via Action Scheduler for reliability
   - Benefit: Better performance, automatic retries, failure tracking

3. **Payment Processing**
   - Current: Individual add-on handling
   - Enhanced: Unified event system for all payment gateways
   - Integration: Existing payment add-ons emit standardized events

4. **Data Storage**
   - Current: EAV tables via Data Access Layer
   - Enhanced: Trigger execution logs in separate tables
   - Compatibility: Full compatibility with existing EAV migration system

5. **Background Jobs**
   - Current: Mix of WP-Cron and Action Scheduler
   - Enhanced: Consolidate all background jobs to Action Scheduler
   - Benefit: Consistent queue management and monitoring

### Epic Use Cases

#### Epic 1: Enterprise Integration Suite
**User Stories:**
- As an enterprise user, I need to sync form submissions to Salesforce CRM
- As a marketing manager, I need to segment leads into MailChimp lists based on form responses
- As a support team, I need tickets created in Zendesk from support forms

**Edge Cases:**
- API rate limiting and retry strategies
- Credential rotation and OAuth token refresh
- Bulk operations for historical data sync
- Field mapping with data type conversions
- Handling API downtime gracefully

#### Epic 2: Advanced Conditional Logic
**User Stories:**
- As a form admin, I need complex conditions (AND/OR/NOT logic)
- As a business owner, I need different actions based on calculated values
- As a developer, I need custom PHP conditions for complex rules

**Edge Cases:**
- Circular condition dependencies
- Performance with 50+ conditions per form
- Condition evaluation order and precedence
- Dynamic conditions based on user roles/capabilities
- Time-based conditions (business hours, dates)

#### Epic 3: Real-time Form Interactions
**User Stories:**
- As a user, I want instant field validation via external APIs
- As a form designer, I need to show/hide fields based on external data
- As an admin, I need real-time duplicate detection during typing

**Edge Cases:**
- Debouncing rapid keystrokes
- Network latency and timeout handling
- Caching API responses for performance
- Fallback behavior when services unavailable
- Security for client-side API calls

#### Epic 4: Payment Lifecycle Management
**User Stories:**
- As a business owner, I need actions triggered on successful payments
- As an accountant, I need invoice generation on subscription renewals
- As a customer service rep, I need alerts for failed payments

**Edge Cases:**
- Partial refunds and proration
- Currency conversion handling
- Payment method changes mid-subscription
- Dunning process for failed payments
- Regulatory compliance (PCI, GDPR)

#### Epic 5: Developer Ecosystem
**User Stories:**
- As a developer, I need to create custom actions for clients
- As an agency, I need to package reusable trigger/action combinations
- As a plugin author, I need to integrate my plugin with Super Forms

**Edge Cases:**
- Version compatibility between add-ons
- Namespace conflicts between custom actions
- Performance impact of multiple add-ons
- Action execution priority and ordering
- Debugging tools for custom actions

#### Epic 6: Audit and Compliance
**User Stories:**
- As a compliance officer, I need audit logs of all trigger executions
- As an admin, I need to track data flow through integrations
- As a security auditor, I need to verify API credential usage

**Edge Cases:**
- Log retention policies and rotation
- GDPR data deletion requests
- Encrypted storage of sensitive logs
- Performance with millions of log entries
- Log export for external analysis

### Testing Requirements

**Unit Tests (80%+ coverage):**
- `SUPER_Trigger_DAL` - Database CRUD operations with scope queries
- `SUPER_Trigger_Manager` - Business logic, validation, permissions
- `SUPER_Trigger_Registry` - Event/action registration and retrieval
- `SUPER_Trigger_Conditions` - Complex condition evaluation (AND/OR/NOT, tag replacement)
- `SUPER_Trigger_Executor` - Synchronous action execution
- `SUPER_Trigger_Action_Base` - Abstract base class implementation
- `SUPER_Trigger_REST_Controller` - REST API endpoints (CRUD, validation)

**Integration Tests:**
- Test with high-volume forms (1000+ submissions/day)
- Test with multiple active triggers per form
- Test scope isolation (form triggers don't fire for other forms)
- Test global triggers fire for all forms
- Test Action Scheduler async execution (Phase 2)
- Test payment event reliability
- Test third-party add-on registration via hooks

**Performance Tests:**
- Trigger lookup performance with 100+ triggers
- Condition evaluation with deeply nested groups
- REST API response time (<200ms for list queries)

**Critical Path Tests:**
- **Data Layer Testing**: Verify all entry operations use `SUPER_Data_Access` methods
- **Background Job Testing**: Confirm all scheduled tasks use Action Scheduler
- **Scope Security**: Users cannot access triggers outside their permission scope
- **Tag Replacement**: All {tag} patterns replaced correctly in conditions/actions
- **Error Handling**: WP_Error returned consistently, no PHP fatal errors

## Work Log

### 2025-11-20
- Task created with 8 implementation phases
- Architecture decisions: Scope system, dedicated admin page, REST API-first approach

### 2025-11-21

#### Completed
- **Phase 1 Foundation COMMITTED**: 66 files, 32,285 insertions
  - 7 core classes: DAL, Manager, Registry, Conditions, Executor, REST Controller, Base Action
  - 19 built-in actions implemented
- **Test Infrastructure**: 23 event firing tests in test-event-firing.php
- **Developer Tools Enhancement**: Added "Trigger System Testing" UI section
  - PHP backend: fire_test_event(), get_trigger_logs(), clear_trigger_logs(), test_trigger()
  - AJAX handlers in class-migration-manager.php
  - JavaScript handlers (~240 lines in developer-tools.js)
  - CSS styling for success/error messages
- **Performance Benchmarks**: Created test-performance.php with 5 test methods
  - Trigger lookup (<20ms for 100 triggers)
  - Condition evaluation (<10ms for nested groups)
  - Tag replacement (<5ms per operation)
  - Full execution cycle (<100ms)
  - Memory usage (<10MB for 100 events)
- Code synced to dev server and verified functional

#### Status
- Phase 1: COMPLETE (committed)
- Event Firing Tests: COMPLETE
- Developer Tools: COMPLETE
- Performance Benchmarks: COMPLETE

#### Test Results (End-to-End)
- ✓ All 3 database tables created and verified
- ✓ All 7 foundation classes loaded successfully
- ✓ Log Message action registered and instantiated
- ✓ Trigger created programmatically
- ✓ Action attached to trigger
- ✓ Event fired and executed successfully
- ✓ Database logs created and verified
- ✓ File logs written to debug.log

#### Performance Metrics (Measured)
- Trigger execution: 0.67-2.07ms
- Action execution: 0.06-0.16ms
- Total overhead: <3ms for complete flow
- Tag replacement: Working perfectly (`{form_id}` → `999`)

#### Files Created (Phase 1)
**Foundation Classes (7):**
- `class-trigger-registry.php` (416 lines)
- `class-trigger-dal.php` (860 lines)
- `class-trigger-manager.php` (539 lines)
- `class-trigger-executor.php` (454 lines)
- `class-trigger-conditions.php` (602 lines)
- `class-trigger-action-base.php` (323 lines)
- `class-trigger-rest-controller.php`

**Action Classes (19):**
- class-action-send-email.php, class-action-update-entry-status.php
- class-action-update-entry-field.php, class-action-delete-entry.php
- class-action-webhook.php, class-action-create-post.php
- class-action-abort-submission.php, class-action-log-message.php
- class-action-stop-execution.php, class-action-set-variable.php
- class-action-redirect-user.php, class-action-update-post-meta.php
- class-action-update-user-meta.php, class-action-run-hook.php
- class-action-modify-user.php, class-action-increment-counter.php
- class-action-clear-cache.php, class-action-conditional.php
- class-action-delay-execution.php

**Test Infrastructure:**
- class-test-db-logger.php (348 lines)
- test-event-firing.php (408 lines)
- class-action-test-case.php (268 lines)
- test-action-log-message.php (230 lines)
- test-action-send-email.php (155 lines)

### 2025-11-22

#### Completed
- **Missing Event Registrations**: Added 6 events that were being fired but not registered
  - `entry.updated`, `entry.status_changed`, `entry.deleted`
  - `file.uploaded`, `file.upload_failed`, `file.deleted`
- **Event Firing Integration**: Added firing code to class-ajax.php for:
  - `entry.deleted` (admin delete + listings delete)
  - `file.upload_failed` (file size exceeded + wp_handle_upload error)
  - `file.deleted` (submission delete option + vcard delete)
- **Documentation Consolidation**: Merged IMPLEMENTATION_STATUS.md into README.md
  - Verified 18 events registered, 19 actions implemented
  - Removed redundant documentation files

#### Current Implementation Summary
- **Events Registered**: 18 (session: 4, form: 6, entry: 5, file: 3)
- **Actions Implemented**: 19/19 (100%)
- **Foundation Classes**: 7/7 (100%)
- **Database Tables**: 3 (triggers, trigger_actions, trigger_logs)

#### Next Steps
- Update CLAUDE.md with accurate event counts
- Begin Admin UI (Phase 1.5) when user provides style guidelines

#### Phase 2 Implementation (Action Scheduler)
- **SUPER_Trigger_Scheduler class** created (`/src/includes/class-trigger-scheduler.php`, ~500 lines)
  - Singleton wrapper for Action Scheduler
  - Methods: `schedule_action()`, `schedule_trigger_action()`, `schedule_recurring()`, `cancel_action()`
  - Retry mechanism with exponential backoff (2/4/8 min delays)
  - Rate limiting utility for API-heavy actions
  - Queue stats and monitoring methods
  - Hooks: `super_trigger_execute_scheduled_action`, `super_execute_delayed_trigger_actions`, `super_trigger_retry_failed_action`, `super_trigger_execute_recurring`

- **SUPER_Trigger_Executor enhanced** with async support:
  - Execution mode constants: `MODE_SYNC`, `MODE_ASYNC`, `MODE_AUTO`
  - `queue_action()` method for async scheduling
  - `get_action_execution_mode()` for smart sync/async decisions
  - `execute_trigger_sync()` and `execute_trigger_async()` convenience methods
  - Tracks `actions_queued` vs `actions_sync` in results

- **SUPER_Trigger_Action_Base enhanced** with async support:
  - `supports_async()` - whether action can run in background
  - `get_execution_mode()` - preferred mode (sync/async/auto)
  - `get_retry_config()` - retry settings (max retries, delays)
  - `should_retry()` - custom retry logic per action
  - `get_rate_limit_config()` - for API rate limiting
  - `get_metadata()` - all action info in one call

- **delay_execution action fixed**:
  - Fixed argument passing to Action Scheduler (wrap in array)
  - Now uses Scheduler constants for hook names and group

- **Sync-only actions** (must run during request):
  - `abort_submission`, `stop_execution`, `redirect_user`, `set_variable`, `conditional_action`
  - All override `supports_async() → false`

- **Async-preferred actions** (run in background):
  - `webhook` → `get_execution_mode() → 'async'`, custom retry (5 attempts, 1hr max)
  - `send_email` → `get_execution_mode() → 'async'`, custom retry (5min initial)

- **super-forms.php updated**:
  - Added `include_once 'class-trigger-scheduler.php'`
  - `init_triggers_system()` initializes Scheduler singleton

#### Status
- Phase 1: COMPLETE (committed)
- Phase 2: COMPLETE (Action Scheduler integration)
- Phase 3: COMPLETE (Execution and Logging)
- Phase 4: COMPLETE (API and Security)
- Event Firing Tests: COMPLETE
- Developer Tools: COMPLETE
- Performance Benchmarks: COMPLETE
- PHPUnit Tests: PASSING (102 tests, 370 assertions, 0 failures)

#### PHPUnit Test Fixes Session
**Test Results**: 29 errors + 4 failures → 102 tests, 370 assertions, 0 failures, 14 skipped (expected)

**Registry (`class-trigger-registry.php`)**:
- Added `reset()` method for test isolation
- Added `$initialized` flag to prevent double-initialization
- Fixed timing when `init` hook already fired

**Conditions (`class-trigger-conditions.php`)**:
- Fixed `replace_tags()` to check `data` and `form_data` arrays with `['value']` structure
- Fixed `evaluate_single()` to handle `{tag}` fields correctly
- Fixed `get_field_value()` to check `data` array
- Fixed nested group detection (check for `operator` + `rules` keys)

**DAL (`class-trigger-dal.php`)**:
- Fixed NULL `scope_id` handling - was storing 0 instead of NULL

**Executor (`class-trigger-executor.php`)**:
- Fixed WP_Error handling in `log_trigger_execution()`
- Added `super_trigger_event` and `super_trigger_action_executed` hooks

**Email Action (`class-action-send-email.php`)**:
- Added `success` and `to` keys to return value
- Fixed `process_attachments()` to handle both array and string input

**Scheduler (`class-trigger-scheduler.php`)**:
- Fixed `should_retry()` to return false for successful results
- Fixed `get_pending_count()` to handle array return from Action Scheduler

**Test Files**:
- Updated `test-trigger-registry.php` to use `reset()` method
- Added `class_exists` skip for `SUPER_Spam_Detector` tests
- Fixed assertions in `test-action-send-email.php`
- Fixed DB logger to handle array values

### Discovered During Implementation
[Date: 2025-11-22 / PHPUnit Testing Session]

#### PHPUnit Email Testing Limitation

During testing of the `send_email` action, we discovered that PHPUnit uses a mock mailer (`reset_phpmailer_instance()`) that captures emails instead of sending them. This means:

- Unit tests verify email construction and wp_mail() calls correctly
- Unit tests CANNOT verify actual email delivery
- Real email testing requires firing events in a live WordPress environment (e.g., via Developer Tools)
- The `tests_retrieve_phpmailer_instance()` function returns captured emails for assertion

**Impact**: Test `send_email` integration by using Developer Tools to fire test events manually, not PHPUnit.

#### Condition Engine: Nested Group Detection

The condition evaluation engine recognizes nested groups by TWO methods:
1. Explicit: `['type' => 'group', 'operator' => 'AND', 'rules' => [...]]`
2. Implicit: `['operator' => 'AND', 'rules' => [...]]` (any rule with `operator` + `rules` keys)

This was discovered when tests using the implicit format were failing. The `evaluate_single()` method now checks for both patterns before evaluating as a single rule.

**Impact**: When writing conditions programmatically, either format works. The implicit format is cleaner for nested structures.

#### Tag Replacement: Multi-Location Context Lookup

The `replace_tags()` and `get_field_value()` methods search for field values in multiple locations within the context:

1. Direct: `$context['field_name']`
2. Data array: `$context['data']['field_name']` or `$context['data']['field_name']['value']`
3. Form data: `$context['form_data']['field_name']` or `$context['form_data']['field_name']['value']`
4. Entry data: `$context['entry_data']['field_name']` or `$context['entry_data']['field_name']['value']`

This was discovered when tags like `{email}` were not being replaced because the value was nested in `$context['data']['email']['value']` rather than directly in `$context['email']`.

**Impact**: When firing events, you can pass data in any of these structures. The `['value']` sub-key format matches Super Forms' standard field data structure from `SUPER_Data_Access`.

#### Registry Initialization Timing

The `SUPER_Trigger_Registry` constructor checks `did_action('init')` to determine initialization timing:

- If `init` has NOT fired: Hooks `initialize()` to the `init` action
- If `init` has ALREADY fired (common in PHPUnit tests): Initializes immediately in constructor

This was causing double-initialization in tests. Solution: Added `$initialized` flag and `reset()` method for test isolation.

**Impact**: In PHPUnit tests, always call `SUPER_Trigger_Registry::get_instance()->reset()` in `setUp()` or use the `class-action-test-case.php` base class which handles this.

#### wpdb NULL Value Handling

WordPress `$wpdb->prepare()` with `%d` format converts PHP `NULL` to integer `0`. This was causing `scope_id = NULL` to be stored as `scope_id = 0` in the triggers table, breaking global scope queries.

**Solution**: Conditionally include `scope_id` in INSERT statements:
```php
// Wrong - stores 0 instead of NULL
$wpdb->prepare("INSERT INTO ... (scope_id) VALUES (%d)", $scope_id);

// Correct - stores actual NULL
$fields = ['scope' => $data['scope']];
if ($data['scope_id'] !== null) {
    $fields['scope_id'] = $data['scope_id'];
}
$wpdb->insert($table, $fields);
```

**Impact**: Any new database operations with nullable foreign keys must use conditional field inclusion, not `%d` format placeholders.

### Discovered During Implementation
[Date: 2025-11-23 / Phase 4 API Security Session]

#### Action Scheduler Test Initialization Timing

Action Scheduler has specific initialization timing that must be respected in PHPUnit tests:

- Action Scheduler registers at `plugins_loaded` priority 0
- Action Scheduler initializes at `plugins_loaded` priority 1
- Tests requiring Action Scheduler must hook at priority 2 or later

This was discovered when scheduler tests failed because Action Scheduler wasn't fully initialized. The fix was added to `tests/bootstrap.php`:

```php
// Ensure Action Scheduler is fully initialized before tests run
add_action('plugins_loaded', function() {
    // Action Scheduler is now ready
}, 2);
```

**Impact**: Any new test files that interact with `SUPER_Trigger_Scheduler` or Action Scheduler functions must ensure AS is initialized first. Use the existing bootstrap.php setup or add explicit priority 2+ hooks.

#### API Key validate_key() Return Structure

The `SUPER_Trigger_API_Keys::validate_key()` method returns an object with specific field names:

```php
// Returns object with:
// - id (int)
// - user_id (int)
// - permissions (array) - decoded from JSON
// - rate_limit (int)
// - key_name (string) - NOT 'name'
```

**Impact**: When consuming API key validation results, use `key_name` not `name` for the human-readable identifier.

#### Credentials Storage: User-Scoped Only

The `SUPER_Trigger_Credentials` class stores `form_id` in the database but the `get()` method does NOT filter by it - credentials are user-scoped only:

```php
// store() accepts form_id parameter
$credentials->store('service', 'key', 'value', $user_id, $form_id);

// get() only filters by user_id, ignoring form_id
$value = $credentials->get('service', 'key', $user_id);
```

This is intentional - credentials are owned by users, not forms. The `form_id` is stored for audit/compliance purposes only.

**Impact**: Don't expect per-form credential isolation. All credentials for a service/key combo are shared across the user's forms.

#### Phase 4 Class Initialization Order

Phase 4 classes have different initialization patterns:

1. **Singletons in `init_triggers_system()`** (called from `super-forms.php`):
   - `SUPER_Trigger_Security::instance()` - Rate limiting, pattern detection
   - `SUPER_Trigger_OAuth::instance()` - OAuth provider registration
   - `SUPER_Trigger_API_Keys::instance()` - API key management

2. **Self-initializing via WordPress hooks**:
   - `SUPER_Trigger_Permissions` - Hooks to `init` via `add_action('init', ...)` in class definition

3. **On-demand (no early initialization)**:
   - `SUPER_Trigger_Credentials` - Instantiated when needed, no singleton pattern

**Impact**: When extending or debugging, understand that Security/OAuth/API Keys are always active after `init_triggers_system()`, Permissions capabilities are added during `admin_init`, and Credentials are created fresh each use.

### Discovered During Implementation
[Date: 2025-11-23 / Phase 5 HTTP Request Action Session]

#### Action Class Autoloader Naming Convention

The `SUPER_Trigger_Registry::get_action_instance()` method (lines 485-508 in `class-trigger-registry.php`) implements lazy loading for action classes with a specific naming convention:

```php
// Class name to file path conversion:
// 1. SUPER_Action_HTTP_Request -> super-action-http-request (underscores to hyphens, lowercase)
// 2. super-action-http-request -> http-request (remove 'super-action-' prefix)
// 3. Final path: /includes/triggers/actions/class-action-http-request.php
```

**Impact**: When creating custom action classes:
- Class name must follow pattern: `SUPER_Action_{CamelCaseActionName}`
- File must be: `class-action-{hyphenated-lowercase-name}.php`
- Location: `/src/includes/triggers/actions/`
- Example: `SUPER_Action_My_Custom_Action` -> `class-action-my-custom-action.php`

#### HTTP Request Response Mapping Syntax

The HTTP Request action's `response_mapping` setting uses a specific line-based syntax for extracting values from API responses:

```
variable_name=json.path.to.value
user_id=data.user.id
items_count=response.items[0].count
```

- One mapping per line
- Format: `variable_name=path`
- Variables are stored in `$context['mapped_data']` for subsequent actions
- Hook `super_http_request_response_mapped` fires after mapping completes

**Impact**: When configuring HTTP Request actions, ensure response mapping follows this exact format. The extracted variables can be referenced in subsequent actions via `{mapped_data.variable_name}` or directly via the context array.

#### JSON Path Extraction Limitation (No Wildcards)

The `get_value_by_path()` method in `SUPER_Action_HTTP_Request` (lines 917-940) supports:

- Dot notation: `data.user.name`
- Numeric array indices: `items[0].id`, `data.results[2].value`

It does NOT support:
- Wildcard indices: `items[*].id` (would need to return array of all IDs)
- Negative indices: `items[-1]` (last item)
- Slice notation: `items[0:5]`
- Filter expressions: `items[?(@.active==true)]`

```php
// Works:
$this->get_value_by_path($data, 'users[0].email');  // First user's email

// Does NOT work:
$this->get_value_by_path($data, 'users[*].email');  // All users' emails
```

**Impact**: When mapping responses from APIs that return arrays, you can only extract values at specific indices. For bulk data extraction (e.g., all items from an array), use wildcard syntax `items[*].id` or custom post-processing via the `super_http_request_response_mapped` hook.

### Discovered During Implementation
[Date: 2025-11-23 / Phase 5 Dynamic Data Enhancements Session]

#### Wildcard Path Extraction Supports Nested Wildcards

The `get_values_by_wildcard_path()` method supports multiple wildcards in a single path for complex nested structures:

```php
// Single wildcard - extract all item IDs
$this->get_values_by_wildcard_path($data, 'items[*].id');
// Returns: [1, 2, 3, ...]

// Nested wildcards - extract SKUs from nested items
$this->get_values_by_wildcard_path($data, 'orders[*].items[*].sku');
// Returns: ['SKU-A', 'SKU-B', 'SKU-C', ...]
```

**Impact**: When mapping API responses with nested arrays, wildcard paths flatten results into a single array.

#### Pipe Modifiers Execute Left-to-Right

Response mapping pipe modifiers chain left-to-right:

```
items[*].price|sort|unique|join:,
```

1. Extract all prices via wildcard
2. Sort the array
3. Remove duplicates
4. Join with commas

**Impact**: Order matters. `|sort|unique` differs from `|unique|sort` when values have duplicates.

#### Repeater-to-Array Conversion Depth Limit

The `convert_repeater_to_array()` method has a maximum recursion depth of 5 levels to prevent infinite loops:

```php
private function convert_repeater_to_array($repeater_data, $field_filter = null, $depth = 0) {
    if ($depth > 5) return $rows; // Prevent infinite recursion
    // ...
}
```

**Impact**: Extremely deep nested repeaters (6+ levels) will have inner levels truncated. This is an intentional safety limit.

#### File/Attachment Modifiers Require URL Fields

The `|file_base64`, `|attachment_base64`, and related modifiers expect the field value to be a URL:

```php
// Works - value is a URL
$context['data']['profile_image'] = ['value' => 'https://example.com/image.jpg'];

// Does NOT work - value is base64 already
$context['data']['profile_image'] = ['value' => 'data:image/png;base64,...'];
```

**Impact**: These modifiers are designed for file upload fields that store URLs to uploaded files, not for fields that already contain encoded data.

### 2025-11-23

#### Completed
- **Phase 5: HTTP Request Action** - Full implementation complete
  - `SUPER_Action_HTTP_Request` class (~900 lines) - `/src/includes/triggers/actions/class-action-http-request.php`
    - All HTTP methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS
    - Authentication: None, Basic, Bearer, API Key, OAuth 2.0, Custom Header
    - Body types: None, JSON, Form Data, XML, Raw, GraphQL, Auto (smart detection)
    - Response parsing with JSON/XML path mapping to context variables
    - Tag replacement in URL, headers, body, and auth parameters
    - Retry mechanism with configurable attempts and delays
    - Debug mode for development/troubleshooting
  - `SUPER_HTTP_Request_Templates` class (~600 lines) - `/src/includes/triggers/class-http-request-templates.php`
    - 15 pre-built templates: Slack, Discord, Microsoft Teams, Zapier, Make (Integromat), n8n, Mailchimp, SendGrid, HubSpot, Salesforce, Airtable, Notion, Google Sheets, Telegram, Generic REST API
    - Template registration API for add-ons via `super_http_templates` filter
    - Import/Export JSON support for custom templates
    - Category organization (chat, automation, email, crm, database, other)
  - 3 AJAX endpoints added to Developer Tools for HTTP request testing
  - Action registered in trigger registry (now 20 actions total)
  - Unit tests: `test-action-http-request.php`, `test-http-request-templates.php`

#### Test Results
- **PHPUnit**: 251 tests, 870 assertions, 0 failures (updated after dynamic data enhancements)

#### Files Created
- `/src/includes/triggers/actions/class-action-http-request.php` (~900 lines)
- `/src/includes/triggers/class-http-request-templates.php` (~600 lines)
- `/tests/triggers/actions/test-action-http-request.php`
- `/tests/triggers/test-http-request-templates.php`

#### Files Modified
- `/src/includes/triggers/class-trigger-registry.php` - Added `http_request` action registration
- `/src/includes/class-developer-tools.php` - Added 3 AJAX handlers for HTTP testing
- `/src/includes/class-migration-manager.php` - Registered AJAX action hooks

#### Status
- Phase 1: COMPLETE
- Phase 2: COMPLETE (Action Scheduler integration)
- Phase 3: COMPLETE (Execution and Logging)
- Phase 4: COMPLETE (API and Security)
- Phase 5: COMPLETE (HTTP Request Action)
- Phase 6: COMPLETE (Payment Events - 16 events registered, webhook endpoints)
- Phase 7: NOT STARTED (Example Add-ons)
- Phase 8: NOT STARTED (Real-time Interactions)
- Phase 9 Part A: COMPLETE (Test Fixtures - verified)
- Phase 9 Part B: COMPLETE (Payment OAuth - SUPER_Payment_OAuth class)
- Phase 9 Part C: COMPLETE (Payment Events registration)

#### Phase 5 Dynamic Data Enhancements (Continued Session)
- **Wildcard Array Extraction `[*]`** - Response path wildcards for extracting arrays
  - `get_values_by_wildcard_path()` for paths like `items[*].id`
  - `parse_path_segments()` for path parsing
  - `extract_wildcard_values()` for recursive extraction
  - Supports nested wildcards: `orders[*].items[*].sku`

- **Pipe Modifiers for Response Mapping** (12 modifiers)
  - Basic: `|json`, `|first`, `|last`, `|count`, `|keys`, `|values`
  - Array: `|join`, `|flatten`, `|unique`, `|sort`, `|reverse`, `|slice`
  - File: `|files`, `|file_base64`, `|file_meta`
  - Signature: `|base64_data`, `|base64_mime`, `|base64_ext`
  - Attachment: `|attachment_url`, `|attachment_base64`, `|attachment_meta`

- **Nested Repeater Support**
  - Recursive conversion in `convert_repeater_to_array()` (max depth 5)
  - Auto-detection via `is_nested_repeater()` helper
  - Handles Orders -> Items -> Variants patterns (3+ levels)

- **Repeater-to-API Body Serialization**
  - `{repeater:field_name}` syntax for outgoing requests
  - `{repeater:field_name|fields:a,b}` for field filtering
  - `process_repeater_tags()` converts SF format to standard JSON arrays
  - `convert_repeater_to_array()` handles recursive conversion

- **Helper Methods Added**
  - `url_to_base64()` - Fetch URL and encode (5MB limit, HEAD check first)
  - `extract_file_meta()` - Parse URL for filename/extension/basename
  - `mime_to_extension()` - Map 30+ MIME types to file extensions

- **Test Coverage**: 251 tests, 870 assertions, all passing (~50 new tests)

#### Files Modified This Session
- `/src/includes/triggers/actions/class-action-http-request.php` (~400 lines added)
- `/tests/triggers/actions/test-action-http-request.php` (~450 lines added)

#### Sandbox Testing Infrastructure Implementation (Continued)

- **SUPER_Sandbox_Manager Rewrite** (`/src/includes/class-sandbox-manager.php`, ~830 lines)
  - Rewrote to support multiple form types: simple, comprehensive, repeater
  - Uses test fixtures from `SUPER_Test_Form_Factory` when available
  - Fixed context building to include `data` key for tag replacement
  - Added `run_test_suite()` for automated validation
  - Added `build_context()` helper for proper trigger execution context
  - Added `get_logs_for_entry()` and `get_test_data_for_type()` methods
  - Debug logging when WP_DEBUG enabled

- **Sandbox Test Runner Created** (`/tests/sandbox-runner.php`, ~300 lines)
  - CLI script for automated sandbox testing
  - Flags: `--status`, `--reset`, `--cleanup`, `--keep`, `--verbose`, `--json`, `--help`
  - Exit codes: 0=pass, 1=fail, 2=setup error
  - Creates sandbox if not exists
  - Tests all form types and reports results

- **Sync Script Enhanced** (`/sync-to-webserver.sh`)
  - Added `--sandbox` flag with modes: `keep` (default), `cleanup`, `reset`
  - Usage: `./sync-to-webserver.sh --sandbox=reset`
  - Can combine with `--test` for PHPUnit + sandbox tests
  - Proper exit code handling for CI integration

#### Discovery: Tag Replacement Context Structure

The original sandbox manager was passing `entry_data` but tag replacement needs `data` key with `['value']` structure. Fixed by adding `build_context()` method that properly formats context for trigger execution:

```php
// Wrong: Tags not replaced
$context = ['entry_data' => ['email' => 'test@example.com']];

// Correct: Tags replaced properly
$context = ['data' => ['email' => ['value' => 'test@example.com']]];
```

**Impact**: Any code firing trigger events must use the `['value']` sub-key structure in the `data` array for tag replacement to work correctly.

#### Sandbox Testing Debugged and Fixed (Session 2)

- **Sandbox Runner Class Loading Fix** (`/tests/sandbox-runner.php`)
  - Added explicit loading of `SUPER_Sandbox_Manager` class
  - The class is only loaded on-demand by Developer Tools, not during WordPress bootstrap
  - Required `require_once SUPER_PLUGIN_DIR . '/includes/class-sandbox-manager.php';`

- **Database Column Name Fix** (`/src/includes/class-sandbox-manager.php`)
  - Fixed `created_at` to `executed_at` in `get_logs_for_entry()` and `get_sandbox_logs()` queries
  - The `wp_superforms_trigger_logs` table schema uses `executed_at` column, not `created_at`

- **Test Data Budget Value Fix** (`/tests/fixtures/class-form-factory.php`)
  - Changed test budget from `25000` to `50000` in `get_test_submission_data()`
  - Required for conditional trigger (`budget > 25000`) to fire

- **Condition Operator Fix** (`/tests/fixtures/class-trigger-factory.php`)
  - Fixed `greater_than` to `>` in `create_conditional_trigger()`
  - The condition evaluator uses symbolic operators (`>`, `<`, `>=`, etc.) not word operators

- **Sandbox Test Results After Fixes**
  - simple: 2 triggers found, 2 executed, 4 logs (PASS)
  - comprehensive: 3 triggers found (including conditional), 3 executed, 6 logs (PASS)
  - repeater: 2 triggers found, 2 executed, 4 logs (PASS)
  - All 3 sandbox tests passing

### Discovered During Implementation
[Date: 2025-11-23 / Sandbox Testing Debugging Session]

#### SUPER_Sandbox_Manager On-Demand Loading

The `SUPER_Sandbox_Manager` class is NOT auto-loaded during WordPress bootstrap. It is only loaded when Developer Tools page is accessed. Any script that uses this class outside of the Developer Tools context must explicitly load it:

```php
require_once SUPER_PLUGIN_DIR . '/includes/class-sandbox-manager.php';
```

**Impact**: The sandbox-runner.php CLI script and any integration tests that use `SUPER_Sandbox_Manager` must include this explicit require statement.

#### Trigger Logs Table Column: executed_at (NOT created_at)

The `wp_superforms_trigger_logs` table uses `executed_at` for the timestamp column, not `created_at`. This is defined in `class-install.php`:

```sql
CREATE TABLE wp_superforms_trigger_logs (
  ...
  executed_at DATETIME NOT NULL,  -- NOT 'created_at'
  ...
)
```

**Impact**: Any queries against the trigger_logs table must use `executed_at`. The column name reflects that this is when the action was executed, which may differ from when the trigger was created.

#### Condition Operators Are Symbolic

The condition evaluator in `SUPER_Trigger_Conditions` uses symbolic operators, not word operators:

```php
// Correct - These work:
'operator' => '>'
'operator' => '<'
'operator' => '>='
'operator' => '<='
'operator' => '=='
'operator' => '!='
'operator' => 'contains'
'operator' => 'not_contains'

// Wrong - These do NOT work:
'operator' => 'greater_than'
'operator' => 'less_than'
```

**Impact**: When creating triggers programmatically or via fixtures, always use symbolic operators for numeric comparisons.

#### Phase 9 Part A Verification (Context Compaction Session)

- **Discovery**: Phase 9 Part A (Test Fixtures) was already fully implemented
  - `SUPER_Test_Form_Factory` - `/tests/fixtures/class-form-factory.php` (~844 lines)
  - `SUPER_Test_Trigger_Factory` - `/tests/fixtures/class-trigger-factory.php` (~539 lines)
  - `SUPER_Webhook_Simulator` - `/tests/fixtures/class-webhook-simulator.php` (~586 lines)
  - Integration tests - `/tests/integration/test-full-submission-flow.php` (~612 lines)
  - Sandbox runner - `/tests/sandbox-runner.php` (~364 lines)

- **Verification Testing**: All tests passing
  - PHPUnit integration tests: 17 tests, 100 assertions, 0 failures
  - Sandbox integration tests: 3/3 forms passed

- **Sandbox Test Results**:
  - simple form: 2 triggers found, 2 executed, 4 logs - PASS
  - comprehensive form: 3 triggers found (including conditional budget > 25000), 3 executed, 6 logs - PASS
  - repeater form: 2 triggers found, 2 executed, 4 logs - PASS

- **Status Update**:
  - Phase 9 Part A: COMPLETE (verified working)
  - Phase 9 Part B: COMPLETE (Payment OAuth and webhook endpoints)
  - Phase 9 Part C: COMPLETE (Payment Events registered)

#### Phase 10: Payment Webhook Tests - COMPLETE
- **Created `tests/triggers/test-payment-events.php`** (~750 lines, 28 test methods)

  **Payment Event Registration Tests (6 tests)**:
  - `test_stripe_payment_events_registered`
  - `test_stripe_subscription_events_registered`
  - `test_paypal_payment_events_registered`
  - `test_paypal_subscription_events_registered`
  - `test_payment_events_have_context_fields`
  - `test_payment_events_have_phase_6`

  **Stripe Signature Verification Tests (5 tests - SECURITY CRITICAL)**:
  - `test_valid_stripe_signature_passes` - HMAC-SHA256 verification
  - `test_invalid_stripe_signature_rejected` - WP_Error returned
  - `test_expired_timestamp_rejected` - Replay attack prevention (5min tolerance)
  - `test_malformed_signature_header_rejected` - Missing t=/v1= components
  - `test_boundary_timestamp_tolerance` - 299s passes, 301s fails

  **Event Mapping Tests (6 tests)**:
  - `test_stripe_checkout_completed_maps_correctly`
  - `test_stripe_payment_intent_events_map_correctly`
  - `test_stripe_subscription_events_map_correctly`
  - `test_paypal_capture_events_map_correctly`
  - `test_paypal_subscription_events_map_correctly`
  - `test_unknown_event_type_not_mapped`

  **Context Building Tests (10 tests)**:
  - `test_stripe_checkout_session_context_extraction`
  - `test_stripe_metadata_form_id_extraction`
  - `test_stripe_amount_conversion_cents_to_display`
  - `test_stripe_payment_intent_context_extraction`
  - `test_stripe_subscription_context_extraction`
  - `test_paypal_capture_context_extraction`
  - `test_paypal_custom_id_json_parsing`
  - `test_paypal_subscription_context_extraction`
  - `test_paypal_amount_display_formatting`
  - `test_stripe_payment_error_context`

  **Edge Case Tests**:
  - `test_missing_metadata_uses_zero_ids`
  - `test_malformed_paypal_custom_id`
  - `test_zero_amount_handled_correctly`
  - `test_large_amount_handled_correctly`

- **Test Results**: 282 tests, 1095 assertions, 0 failures, 14 skipped
- **Implementation Details**:
  - Used PHP Reflection to test private methods (`verify_stripe_signature`, `build_stripe_context`, etc.)
  - Helper method `generate_stripe_signature()` creates valid HMAC-SHA256 signatures
  - `ensure_builtins_loaded()` helper loads registry events for registration tests
  - Sample webhook payloads match actual Stripe/PayPal formats

#### Status Summary
- Phase 1: COMPLETE
- Phase 2: COMPLETE (Action Scheduler integration)
- Phase 3: COMPLETE (Execution and Logging)
- Phase 4: COMPLETE (API and Security)
- Phase 5: COMPLETE (HTTP Request Action)
- Phase 6: COMPLETE (Payment Events)
- Phase 7: NOT STARTED (Example Add-ons)
- Phase 8: NOT STARTED (Real-time Interactions)
- Phase 9: COMPLETE (Test Fixtures and Payment OAuth)
- Phase 10: COMPLETE (Payment Webhook Tests)

### Discovered During Implementation
[Date: 2025-11-23 / Phase 9 Part B Payment OAuth Session]

#### Payment Webhook Endpoints Use Public Access with Signature Verification

The payment webhook REST endpoints (`/wp-json/super-forms/v1/webhooks/stripe` and `/wp-json/super-forms/v1/webhooks/paypal`) are registered with `permission_callback: __return_true` because:

1. External payment processors (Stripe, PayPal) cannot authenticate with WordPress nonces/cookies
2. Security is instead enforced via cryptographic signature verification
3. Stripe uses HMAC-SHA256 with timestamp tolerance
4. PayPal requires an API call to verify webhook signatures

```php
// Webhook endpoint registration pattern:
register_rest_route( $namespace, '/webhooks/stripe', array(
    'methods'             => 'POST',
    'callback'            => array( $this, 'handle_stripe_webhook' ),
    'permission_callback' => '__return_true', // Public - verified by signature
) );
```

**Impact**: When adding new webhook endpoints for external services, use signature verification instead of WordPress authentication. Never rely on `permission_callback` alone for webhook security.

#### Stripe Signature Verification Components

Stripe webhook signatures consist of two parts parsed from the `Stripe-Signature` header:

```
Stripe-Signature: t=1732380000,v1=abc123hash...
```

- `t` = Unix timestamp when Stripe signed the payload
- `v1` = HMAC-SHA256 signature of `{timestamp}.{payload}` with webhook secret

Verification includes:
1. Parse header to extract `t` (timestamp) and `v1` (signature)
2. Check timestamp is within 5 minutes of current time (prevents replay attacks)
3. Compute expected signature: `hash_hmac('sha256', $timestamp . '.' . $payload, $webhook_secret)`
4. Use `hash_equals()` for timing-safe comparison

**Impact**: When testing Stripe webhooks locally, use Stripe CLI (`stripe listen --forward-to`) which handles signature generation, or disable verification in development.

#### PayPal Webhook Verification Requires API Call

Unlike Stripe's HMAC approach, PayPal verification requires an API call to `/v1/notifications/verify-webhook-signature`:

1. First obtain an access token via client credentials flow (`/v1/oauth2/token`)
2. Send verification request with all PayPal headers plus the webhook payload
3. Check `verification_status === 'SUCCESS'` in response

This adds latency (~200-500ms) but is required by PayPal's security model.

**Impact**: PayPal webhook handlers need configured `client_id` and `client_secret` for production verification. The implementation allows processing without verification in development (logged as warning).

#### Payment Event Context Must Extract form_id/entry_id from Metadata

Payment processors don't natively know about Super Forms. The `form_id` and `entry_id` must be:

1. **Sent during checkout** - Stored in Stripe `metadata` or PayPal `custom_id`
2. **Extracted during webhook** - Retrieved from event payload to enable trigger execution

```php
// Stripe: metadata stored in checkout session
$context['form_id'] = $event['data']['object']['metadata']['form_id'] ?? 0;
$context['entry_id'] = $event['data']['object']['metadata']['entry_id'] ?? 0;

// PayPal: custom_id may be JSON with form/entry IDs
$custom_id = $event['resource']['custom_id'] ?? '';
```

**Impact**: Payment form implementations must pass `form_id` and `entry_id` when creating Stripe Checkout sessions or PayPal orders. Without this, payment triggers cannot fire with form context.

#### Graceful Degradation When Credentials Not Configured

Both webhook handlers allow processing without signature verification when credentials are not configured:

```php
if ( empty( $webhook_secret ) ) {
    SUPER_Trigger_Logger::warning( 'Stripe webhook secret not configured - signature verification skipped' );
    // Continue processing instead of rejecting
}
```

This design choice enables:
- Development/testing without configuring real credentials
- Gradual rollout where not all users have credentials configured yet

**Impact**: In production, always configure webhook secrets. The warning log helps identify missing configuration without breaking functionality during development.

#### Architecture Alignment and Documentation Session (Continued)

- **3-Concept Architecture Review**: Analyzed all task files to verify alignment with Sessions/Submissions/Entries architecture
  - Sessions: Temporary form-filling state (`wp_superforms_sessions`)
  - Submissions: Immutable audit trail (via `wp_superforms_trigger_logs`)
  - Entries: Permanent CRM data (`super_contact_entry` post type + EAV)

- **Industry Research**: Comprehensive comparison with other form plugins
  - Gravity Forms: Entries + `wp_gf_draft_submissions` table (status-based drafts)
  - WPForms: Entries with Partial/Completed status field
  - Formidable Forms: Entries with Draft/In Progress/Abandoned/Submitted status
  - Fluent Forms: Entries + separate draft table
  - Ninja Forms: "Submissions" terminology
  - Typeform: "Responses" with Partial/Completed status
  - JotForm: "Submissions" with browser-based autofill

- **Key Finding**: Super Forms architecture is MORE sophisticated than industry standard
  - Most plugins create draft entries immediately (spam pollutes CRM data)
  - Super Forms separates Sessions (temporary) from Entries (permanent)
  - Pre-submission firewall can abort BEFORE any entry exists
  - Cleaner data model with storage efficiency

- **Documentation Added to README.md** (~450 lines):
  - **Data Architecture section**: Core concepts table, flow diagram, session statuses
  - **Terminology Decisions section**: UI ("Draft") vs code ("Session"), naming conventions
  - **Use Cases and Examples section**: 5 real-world scenarios with configuration/flow
  - **Future Ideas section**: 6 future phases documented (15-20)
    - Phase 15: Ticket System Add-on
    - Phase 16: Booking System Add-on
    - Phase 17: Entries to Custom Tables Migration
    - Phase 18: Entry Notes and Activity Log
    - Phase 19: Advanced Entry Management
    - Phase 20: Form Analytics v2

- **Terminology Decisions Documented**:
  - Use "Draft" in UI, "Session" in code/database
  - Rename "Contact Entries" to "Entries" in admin menu
  - Keep `super_contact_entry` post type slug for backwards compatibility
  - Entry Notes table schema defined for future implementation

### 2025-11-27

#### Completed
- **Task Documentation Accuracy Audit**: Reviewed README.md against actual implementation state
  - Discovered Phase 1a (Built-in Actions + Spam Detection) was COMPLETE but marked as "Pending"
    - All 6 steps implemented and working (sessions table, client-side manager, spam detector, duplicate detector, submission flow refactor, scheduled jobs cleanup)
    - Updated success criteria to "COMPLETE" with all checkmarks
  - Discovered Phase 11 (Email v2 ↔ Triggers Migration) is ~80% complete
    - Bidirectional sync working (`SUPER_Email_Trigger_Migration` class)
    - Migration script implemented with 30-day backward compatibility
    - Visual/HTML mode toggle operational in Email Builder v2
    - Updated success criteria from "PENDING" to "IN PROGRESS (~80%)"
  - Discovered Phase 17 (Entries to Custom Tables Migration) is ~75% complete
    - Entry DAL created (`SUPER_Entry_DAL` class)
    - Backwards compatibility layer working (reads from both post type and custom table)
    - AJAX endpoints updated for DAL usage
    - Updated success criteria from "PENDING (Priority)" to "IN PROGRESS (~75%)"
- **Implementation Order Section Updated**: Marked Phase 1a as complete in execution order list
- **Test Verification**: All PHPUnit tests passing (triggers + integration suites)

#### Decisions
- No new features implemented - session focused on documentation accuracy and alignment with actual code

#### Next Steps
- Complete remaining 20% of Phase 11 (Email v2 migration polish)
- Complete remaining 25% of Phase 17 (Entry migration finalization)
- Continue with next priority phase per task plan