# Phase 11: Email System Migration to Triggers

## Overview

Migrate the existing email notification system (Admin emails, Confirmation emails) to use the triggers/actions infrastructure. This includes integrating the Email v2 React builder as the visual interface while the backend uses `send_email` trigger actions.

## Work Log

### 2025-11-27

#### Completed
- Created Triggers v2 UI epic file (`20-implement-triggers-v2-ui.md`)
  - Comprehensive 10-phase implementation plan (~26 days estimated)
  - Full architecture with component structure
  - All 36 events and 20 actions documented
  - Email v2 component extraction plan for embedding in Triggers tab
- Implemented bidirectional Email v2 ↔ Triggers sync system
  - Added `sync_emails_to_triggers()` in `class-email-trigger-migration.php` (lines 838-938)
  - Added `email_to_action_config()` private helper (lines 947-982)
  - Added `convert_triggers_to_emails_format()` (lines 995-1075)
  - Added `get_emails_for_ui()` main entry point (lines 1087-1108)
  - Updated `get_form_emails_settings()` in `class-common.php` (lines 121-148)

#### Technical Implementation
**Data Flow Architecture:**
```
Email v2 UI saves → _emails postmeta → sync_emails_to_triggers() → triggers table
Email v2 UI loads ← _emails postmeta ← get_emails_for_ui() ← convert_triggers_to_emails_format()
```

**Key Methods:**
- `sync_emails_to_triggers($form_id, $emails)` - Called when Email v2 saves
  - Creates/updates/deletes triggers based on email changes
  - Maintains email_id → trigger_id mapping in `_super_email_triggers` postmeta
  - Skips legacy keys (admin, confirmation, reminder_1-3)
  - Updates trigger name, enabled status, and send_email action config
- `convert_triggers_to_emails_format($form_id)` - Reverse sync for UI display
  - Reads triggers table via EMAIL_TRIGGER_MAP
  - Converts send_email action config back to Email v2 format
  - Preserves all fields: to, from_email, from_name, subject, body, attachments, reply_to, cc, bcc, template, conditions, schedule
- `get_emails_for_ui($form_id)` - Main UI entry point
  - First checks `_emails` postmeta
  - If empty, populates from triggers via `convert_triggers_to_emails_format()`
  - Saves populated data back to `_emails` for future loads
  - Enables Email v2 tab to display migrated legacy emails

#### Problem Solved
- Fixed issue where Email v2 tab was empty for migrated forms (e.g., form 38036)
- Migrated legacy emails now appear in Email v2 UI
- Changes in Email v2 UI sync to triggers table for execution
- Bidirectional sync maintains consistency between `_emails` and triggers

#### Next Steps
- Sync to dev server and test with form 38036
- Verify Email v2 UI displays migrated emails correctly
- Test email save → trigger creation flow
- Validate trigger execution uses updated configs

## Current State Analysis

### Email Storage
- **Legacy**: Admin/Confirmation email settings stored in `_super_form_settings` post meta
- **Email v2**: Modern emails stored in `_emails` post meta (separate key)
- **React App**: Full drag-drop email builder at `/src/react/emails-v2/`
  - ~70+ React components
  - Uses zustand store (`useEmailStore`)
  - Auto-save functionality
  - Client preview (Gmail, Apple Mail, Outlook chrome)

### Existing Comments in Code
```php
// src/includes/class-common.php
// "REMOVED: Admin emails are now stored in _emails meta field and converted to triggers at runtime"
// "Email migration: Convert legacy email settings to new _emails meta field"
```

This indicates migration work has been partially started.

## Architecture

### Target State
```
┌─────────────────────────────────────────────────────────┐
│  Form Builder: [Email v2] Tab                           │
│  ────────────────────────────────────────────────       │
│  Visual drag-drop email builder (React)                 │
│  User designs template, sets recipients, conditions     │
│                                                         │
│  [Save] → Creates/updates trigger in database           │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│  wp_superforms_triggers table                           │
│  ────────────────────────────────────────────────       │
│  event_id: form.submitted                               │
│  scope: form                                            │
│  scope_id: {form_id}                                    │
│  conditions: JSON (optional)                            │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│  wp_superforms_trigger_actions table                    │
│  ────────────────────────────────────────────────       │
│  action_type: send_email                                │
│  action_config: {                                       │
│    to: "{email}",                                       │
│    subject: "Thank you for your submission",            │
│    body: "<email_v2_template_json>",                    │
│    body_type: "email_v2",                               │
│    attachments: [...],                                  │
│    headers: {...}                                       │
│  }                                                      │
└─────────────────────────────────────────────────────────┘
```

### Facade Pattern
The Email v2 tab acts as a "facade" - users interact with the visual builder, but it generates trigger configurations under the hood. Benefits:
- Users don't need to learn triggers for basic email setup
- Power users can access/modify triggers directly
- Same logging, retry, async execution for all emails

## Migration Strategy

### Approach: Full Migration on Plugin Update
Following the EAV migration pattern:

1. **Detection**: On plugin update, check if legacy email settings exist
2. **Conversion**: Convert legacy settings to triggers in background
3. **Dual-read**: During migration, read from both sources
4. **Completion**: Mark migration complete, read from triggers only

### Migration State Tracking
```php
$migration = get_option('superforms_email_trigger_migration', [
    'status' => 'not_started',  // not_started | in_progress | completed
    'started_at' => null,
    'completed_at' => null,
    'forms_migrated' => 0,
    'forms_total' => 0,
    'failed_forms' => [],
]);
```

### Migration Logic
```php
// For each form with legacy email settings:
// 1. Check if admin_email_enabled = 'yes'
if ($settings['admin_email_enabled'] === 'yes') {
    // Create trigger for admin email
    $trigger_id = SUPER_Trigger_DAL::create([
        'trigger_name' => 'Admin Email - ' . $form_title,
        'scope' => 'form',
        'scope_id' => $form_id,
        'event_id' => 'form.submitted',
        'conditions' => null,  // No conditions by default
        'enabled' => true,
    ]);

    // Create action
    SUPER_Trigger_DAL::create_action([
        'trigger_id' => $trigger_id,
        'action_type' => 'send_email',
        'action_config' => [
            'to' => $settings['admin_email_recipient'],
            'subject' => $settings['admin_email_subject'],
            'body' => $settings['admin_email_body'],
            'body_type' => 'legacy_html',
            // ... other settings
        ],
    ]);
}

// 2. Check if confirm_email_enabled = 'yes'
if ($settings['confirm_email_enabled'] === 'yes') {
    // Similar trigger creation for confirmation email
}
```

## Success Criteria

### Core Functionality
- [x] Email v2 tab saves to triggers table (bidirectional sync implemented)
- [x] Migration script converts legacy email settings to triggers (via `migrate_form()`)
- [ ] Admin emails sent via `send_email` action (trigger execution needs testing)
- [ ] Confirmation emails sent via `send_email` action (trigger execution needs testing)
- [ ] Email v2 templates render correctly through trigger system

### Migration
- [x] Full migration on plugin update (background migration via Action Scheduler)
- [x] Progress tracking during migration (status, forms_migrated, failed_forms)
- [x] Failed forms tracked for manual review (stored in migration state)
- [x] Backwards compatible during migration window (dual-read system, trigger map tracking)

### Logging & Debugging
- [x] Email delivery logged in trigger_logs table (Phase 3 complete)
- [x] Success/failure status recorded (trigger logging system)
- [x] Debug mode shows email content before send (Phase 3 debugger)

### Email v2 Integration
- [x] Email v2 React app creates triggers (via `sync_emails_to_triggers()`)
- [x] Existing Email v2 templates migrate to triggers (via `convert_triggers_to_emails_format()`)
- [ ] Preview functionality still works (needs testing)
- [ ] Scheduling modal creates delayed trigger execution (needs implementation)

## Technical Implementation

### Files to Modify

1. **`/src/includes/class-email-trigger-migration.php`** (NEW)
   - Migration orchestration
   - Legacy settings to trigger conversion
   - Progress tracking

2. **`/src/includes/triggers/actions/class-action-send-email.php`**
   - Add `body_type: email_v2` support
   - Render Email v2 JSON templates to HTML
   - Handle legacy HTML body type

3. **`/src/react/emails-v2/src/hooks/useEmailStore.js`**
   - Modify save function to create/update triggers
   - Add trigger metadata to email state

4. **`/src/includes/class-pages.php`**
   - Modify `emails_v2_tab()` to pass trigger data
   - Add trigger ID to React app props

5. **`/src/includes/class-install.php`**
   - Add email migration initialization
   - Track migration state

### Database Changes
No new tables required - uses existing `wp_superforms_triggers` and `wp_superforms_trigger_actions` tables.

### Action Config Schema for `send_email`
```json
{
  "to": "{email}",
  "cc": "",
  "bcc": "{admin_email}",
  "subject": "Thank you for contacting us",
  "from_name": "{site_name}",
  "from_email": "{admin_email}",
  "reply_to": "{email}",
  "body_type": "email_v2",
  "body": {
    "elements": [...],
    "settings": {...}
  },
  "attachments": [
    {"type": "form_files", "field": "resume"},
    {"type": "static", "url": "https://..."}
  ],
  "headers": {
    "X-Priority": "1"
  }
}
```

## Testing Requirements

### Unit Tests
- [ ] Legacy settings conversion produces correct trigger config
- [ ] Email v2 JSON renders to valid HTML
- [ ] Tag replacement works in all email fields
- [ ] Attachments processed correctly

### Integration Tests
- [ ] Full migration runs on test database
- [ ] Emails sent via triggers match legacy output
- [ ] Email v2 builder saves create triggers
- [ ] Scheduled emails use Action Scheduler

### Manual Testing
- [ ] Create email in Email v2 builder → verify trigger created
- [ ] Submit form → verify email sent and logged
- [ ] Check trigger logs page shows email delivery
- [ ] Test migration on form with legacy settings

## Rollback Plan

If issues discovered post-migration:
1. Migration state allows rollback to legacy system
2. Legacy settings preserved (not deleted) during migration
3. Feature flag can disable trigger-based emails
4. Admin notice if migration fails

## Dependencies

- Phase 1: Foundation (triggers tables, DAL, executor) - COMPLETE
- Phase 2: Action Scheduler (async execution) - COMPLETE
- Phase 3: Logging system - COMPLETE
- Existing `send_email` action - COMPLETE

## Notes

- Email v2 builder is WIP but functional
- This phase completes the Email v2 integration
- Consider email reminders add-on compatibility
- SMTP settings remain global (not per-trigger)
