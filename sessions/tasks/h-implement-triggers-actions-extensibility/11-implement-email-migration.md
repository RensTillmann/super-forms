# Phase 11: Email System Migration to Automations

## Terminology Reference

See [Phase 26: Terminology Standardization](26-terminology-standardization.md) for the definitive naming convention:
- **Automation** = The saved entity (container)
- **Trigger** = Event node that starts execution
- **Action** = Task node that does something
- **Condition** = Logic node for branching
- **Control** = Flow node (delay, schedule, stop)

## Overview

Migrate the existing email notification system (Admin emails, Confirmation emails) to use the automations/actions infrastructure. This includes integrating the Email v2 React builder as the visual interface while the backend uses `send_email` action nodes.

## Work Log

### 2025-11-27 (Session 1)

#### Completed
- Created Automations UI epic file (`20-implement-triggers-v2-ui.md`)
- Implemented bidirectional Email v2 ↔ Automations sync system
  - `sync_emails_to_automations()` - Email v2 UI → automations table
  - `convert_automations_to_emails_format()` - Automations → Email v2 format
  - `get_emails_for_ui()` - Main entry point for UI data loading
  - Fixed empty Email v2 tab for migrated forms

### 2025-11-27 (Session 2) - Visual/HTML Mode Toggle

#### Completed
- Implemented mode toggle infrastructure in `EmailClientBuilder.jsx`
  - Added `mode` state (visual/html) with local storage persistence
  - Confirmation dialogs when switching modes (data loss warnings)
  - Visual→HTML conversion uses `generateHtml()` from builder
  - HTML→Visual wraps content in editable HTML element
- Added HTML element type to Email v2 builder
  - Created `HtmlElement.jsx` component with TinyMCE integration
  - Added to `ElementPaletteHorizontal.jsx` (Dynamic category)
  - Registered in `useEmailBuilder.js` element types
  - Added HTML case to `generateHtml()` renderer
  - Updated `ElementRenderer.jsx` to handle html type
- UI enhancements in `GmailChrome.jsx`
  - Toggle buttons next to "Attach files" button
  - Visual mode: Shows drag-drop builder
  - HTML mode: Shows TinyMCE editor with full HTML content
- Code quality improvements
  - Fixed ESLint errors in `developer-tools.js` (ternary operators)
  - Fixed ESLint errors in `session-manager.js` (AbortController)
  - Excluded `common.js` from commit (pre-existing polyfill issues)

#### Technical Implementation
**Mode Toggle System:**
- State stored in localStorage as `emailBuilderMode_{emailId}`
- Prevents accidental data loss with confirmation dialogs
- Maintains builder state during mode switches
- HTML element allows raw HTML editing within visual builder

**Files Modified:**
- `src/react/emails-v2/src/components/Preview/EmailClientBuilder.jsx` (major rewrite)
- `src/react/emails-v2/src/components/Preview/ClientChrome/GmailChrome.jsx` (toggle UI)
- `src/react/emails-v2/src/components/Builder/Elements/HtmlElement.jsx` (new)
- `src/react/emails-v2/src/components/Builder/ElementPaletteHorizontal.jsx` (added html)
- `src/react/emails-v2/src/components/Builder/Elements/ElementRenderer.jsx` (html support)
- `src/react/emails-v2/src/hooks/useEmailBuilder.js` (html element type + renderer)
- `src/assets/js/backend/developer-tools.js` (ESLint fixes)
- `src/assets/js/frontend/session-manager.js` (ESLint fixes)

#### Commit
- Hash: `9fe81b7e`
- Message: "Add Email v2 Visual/HTML mode toggle and Phase 2 infrastructure"
- Status: Pushed to remote

### 2025-11-30 (Session 3) - Email Builder Consolidation (Phase 11.3)

#### Completed
- Consolidated email builder into admin bundle
  - Moved `/src/react/emails-v2/` → `/src/react/admin/components/email-builder/`
  - Deleted entire emails-v2 directory (~33,000 lines removed)
  - Fixed 70+ import paths (removed `sfui:` prefix, updated to `@/` alias)
  - Created centralized exports in `email-builder/index.js`
- Built `SendEmailModal` component for workflow integration
  - Full-screen modal with embedded email builder
  - Integrates with visual workflow system
  - Node configuration UI pattern
- Updated build architecture
  - Single unified admin bundle (799KB)
  - Eliminated dual-build duplication (emails-v2.js + admin.js)
  - Removed separate webpack config and package.json
  - Deleted build outputs: `emails-v2.js/css` files
- Documentation updates
  - Updated `CLAUDE.md` with email builder location and exports
  - Updated `docs/CLAUDE.javascript.md` with new architecture
  - Added integration patterns and examples
  - Marked legacy paths as deprecated

#### Technical Implementation
**Email Builder as Reusable Library:**
- Exported 48 components/hooks from `email-builder/index.js`
- Main exports: `EmailBuilderIntegrated`, `EmailClientBuilder`, `useEmailBuilder`, `generateHtmlFromElements`
- Supports multiple integration patterns: standalone, workflow modal, custom

**SendEmailModal Integration:**
- Location: `/src/react/admin/components/form-builder/triggers/modals/SendEmailModal.tsx`
- Features: Full email builder UI, node settings header, enable/disable toggle
- Props: `node`, `onUpdateNode`, `isOpen`, `onClose`

**Files Modified:**
- Deleted: `/src/react/emails-v2/` (entire directory, ~108 files)
- Created: `/src/react/admin/components/email-builder/` (moved components)
- Created: `/src/react/admin/components/email-builder/index.js` (exports)
- Created: `/src/react/admin/components/form-builder/triggers/modals/SendEmailModal.tsx`
- Updated: `CLAUDE.md`, `docs/CLAUDE.javascript.md`
- Updated: Task README with Phase 11.3 completion

#### Commit
- Hash: TBD
- Message: "Phase 11.3: Consolidate email builder into admin bundle"
- Status: Ready for commit

## Current State Analysis

### Email Storage
- **Legacy**: Admin/Confirmation email settings stored in `_super_form_settings` post meta
- **Email v2**: Modern emails stored in `_emails` post meta (separate key)
- **React Components**: Email builder integrated into admin bundle at `/src/react/admin/components/email-builder/`
  - 70+ React components exported as reusable library
  - Uses zustand store (`useEmailBuilder`, `useEmailStore`)
  - Auto-save functionality
  - Client preview (Gmail, Apple Mail, Outlook chrome)
  - Single unified build (admin.js)

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
│  [Save] → Creates/updates automation in database        │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│  wp_superforms_automations table                        │
│  ────────────────────────────────────────────────       │
│  name: "Admin Email - Form Name"                        │
│  type: "code"                                           │
│  workflow_graph: { nodes: [...], connections: [...] }   │
│  enabled: true                                          │
│  NOTE: Scope configured in trigger node config!         │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│  wp_superforms_automation_actions table                 │
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
The Email v2 tab acts as a "facade" - users interact with the visual builder, but it generates automation configurations under the hood. Benefits:
- Users don't need to learn automations for basic email setup
- Power users can access/modify automations directly
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
    // Create automation for admin email with node-level scope
    $workflow_graph = [
        'nodes' => [
            [
                'category' => 'trigger',
                'type' => 'form.submitted',
                'config' => [
                    'scope' => 'current',  // Node-level scope!
                    'formId' => $form_id,
                ],
            ],
            // ... email action node config
        ],
    ];

    $automation_id = SUPER_Automation_DAL::create([
        'name' => 'Admin Email - ' . $form_title,
        'type' => 'code',
        'workflow_graph' => json_encode($workflow_graph),
        'enabled' => true,
    ]);

    // Create action
    SUPER_Automation_DAL::create_action([
        'automation_id' => $automation_id,
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
    // Similar automation creation for confirmation email
}
```

## Success Criteria

### Core Functionality
- [x] Email v2 tab saves to automations table (bidirectional sync implemented)
- [x] Migration script converts legacy email settings to automations (via `migrate_form()`)
- [x] Visual/HTML mode toggle implemented
- [ ] Admin emails sent via `send_email` action (needs testing)
- [ ] Confirmation emails sent via `send_email` action (needs testing)
- [ ] Email v2 templates render correctly through automation system (needs testing)

### Migration
- [x] Full migration on plugin update (background migration via Action Scheduler)
- [x] Progress tracking during migration
- [x] Failed forms tracked for manual review
- [x] Backwards compatible during migration window

### Email v2 Builder Enhancements
- [x] Visual mode with drag-drop builder
- [x] HTML mode with TinyMCE editor
- [x] Mode switching with data loss prevention
- [x] HTML element type for raw HTML blocks
- [ ] Preview functionality testing
- [ ] Scheduling modal integration (deferred)

## Technical Implementation

### Files to Modify

1. **`/src/includes/class-email-automation-migration.php`** (NEW)
   - Migration orchestration
   - Legacy settings to automation conversion
   - Progress tracking

2. **`/src/includes/automations/actions/class-action-send-email.php`**
   - Add `body_type: email_v2` support
   - Render Email v2 JSON templates to HTML
   - Handle legacy HTML body type

3. **`/src/react/admin/components/email-builder/hooks/useEmailStore.js`** (moved from emails-v2)
   - Modify save function to create/update automations
   - Add automation metadata to email state

4. **`/src/includes/class-pages.php`**
   - Modify `emails_v2_tab()` to pass automation data
   - Add automation ID to React app props

5. **`/src/includes/class-install.php`**
   - Add email migration initialization
   - Track migration state

### Database Changes
No new tables required - uses existing `wp_superforms_automations` and `wp_superforms_automation_actions` tables.

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

### Manual Testing (Next Session)
- [ ] Test Visual/HTML mode toggle at https://f4d.nl/dev/wp-admin/admin.php?page=super_create_form&id=38036
  - [ ] Switch from Visual to HTML mode
  - [ ] Verify HTML content generated correctly
  - [ ] Switch from HTML to Visual mode
  - [ ] Verify HTML element created and editable
  - [ ] Test data loss prevention dialogs
- [ ] Test Email v2 builder workflow
  - [ ] Create new email in Visual mode
  - [ ] Verify automation created in database
  - [ ] Edit email and verify automation updated
  - [ ] Delete email and verify automation removed
- [ ] Test migrated email display
  - [ ] Load form 38036 Email v2 tab
  - [ ] Verify migrated emails appear correctly
  - [ ] Test editing migrated email
- [ ] Test email delivery
  - [ ] Submit form with admin email configured
  - [ ] Verify email sent via automation system
  - [ ] Check automation logs for delivery record

### Integration Tests (Future)
- [ ] Legacy settings conversion produces correct automation config
- [ ] Email v2 JSON renders to valid HTML
- [ ] Tag replacement works in all email fields
- [ ] Attachments processed correctly
- [ ] Full migration runs on test database

## Rollback Plan

If issues discovered post-migration:
1. Migration state allows rollback to legacy system
2. Legacy settings preserved (not deleted) during migration
3. Feature flag can disable automation-based emails
4. Admin notice if migration fails

## Dependencies

- Phase 1: Foundation (automations tables, DAL, executor) - COMPLETE
- Phase 2: Action Scheduler (async execution) - COMPLETE
- Phase 3: Logging system - COMPLETE
- Existing `send_email` action - COMPLETE

## Notes

- Email v2 builder is WIP but functional
- This phase completes the Email v2 integration
- Consider email reminders add-on compatibility
- SMTP settings remain global (not per-automation)
