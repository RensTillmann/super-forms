# Phase 20: Triggers v2 - Modern React-Based Trigger Management UI

## Overview

Build a modern React-based Triggers management interface that replaces the legacy PHP/jQuery Triggers tab. This enables users to visually manage all 36 events, 20 actions, and complex conditions through an intuitive UI. The Email v2 component will be embedded inline when configuring `send_email` actions.

## Problem Statement

### Current State (Old Triggers Tab)

| Limitation | Impact |
|------------|--------|
| Only 5 events exposed | Users can't access 31 other events (payment, subscription, file, session) |
| Only 5 actions available | Users can't use 15 other actions (http_request, webhook, create_post, etc.) |
| Basic inline email config | No access to Email v2's rich builder, templates, visual editor |
| Simple conditions (f1, logic, f2) | Can't build complex AND/OR condition groups |
| No execution logs | Users can't debug why triggers didn't fire |
| PHP/jQuery UI | Outdated, inconsistent with Email v2's modern React approach |
| Stored in postmeta | Scattered across `_super_trigger-%` keys, hard to query |

### New Trigger System (No UI)

The backend trigger system is powerful but invisible to users:
- **36 events**: form.*, entry.*, payment.stripe.*, subscription.*, file.*, session.*
- **20 actions**: send_email, webhook, http_request, create_post, update_entry_status, etc.
- **Complex conditions**: AND/OR/NOT grouping with tag replacement
- **Execution logging**: Full audit trail with timing and errors
- **Database storage**: Proper relational tables for querying

## Architecture

### Target UI Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  TRIGGERS v2 TAB                                              [+ Add]   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────┐  ┌───────────────────────────────────────────────┐│
│  │ TRIGGER LIST     │  │ TRIGGER EDITOR                                ││
│  │                  │  │                                               ││
│  │ ○ Admin Email    │  │  Name: [Send Admin Email on Submission    ]  ││
│  │   form.submitted │  │                                               ││
│  │   ✓ Enabled      │  │  Event: [form.submitted                  ▼]  ││
│  │                  │  │         ├─ Form Events                        ││
│  │ ○ Payment Email  │  │         │  ├─ form.loaded                     ││
│  │   payment.stripe │  │         │  ├─ form.submitted ←                ││
│  │   ✓ Enabled      │  │         │  ├─ form.validation_failed          ││
│  │                  │  │         │  └─ form.spam_detected              ││
│  │ ● Webhook →      │  │         ├─ Entry Events                       ││
│  │   entry.created  │  │         │  ├─ entry.created                   ││
│  │   ○ Disabled     │  │         │  └─ entry.status_changed            ││
│  │                  │  │         ├─ Payment Events (Stripe)            ││
│  │                  │  │         │  ├─ payment.stripe.checkout_completed│
│  │                  │  │         │  └─ ...                             ││
│  │                  │  │         └─ ...more categories                 ││
│  │                  │  │                                               ││
│  │                  │  │  ┌─ CONDITIONS ────────────────────────────┐  ││
│  │                  │  │  │ ☑ Enable conditions                     │  ││
│  │                  │  │  │                                         │  ││
│  │                  │  │  │  ┌─ AND ─────────────────────────────┐  │  ││
│  │                  │  │  │  │ {payment_status} == "completed"   │  │  ││
│  │                  │  │  │  │ {amount} > 100                    │  │  ││
│  │                  │  │  │  │ [+ Add condition]                 │  │  ││
│  │                  │  │  │  └───────────────────────────────────┘  │  ││
│  │                  │  │  │  [+ Add OR group]                       │  ││
│  │                  │  │  └─────────────────────────────────────────┘  ││
│  │                  │  │                                               ││
│  │                  │  │  ┌─ ACTIONS ───────────────────────────────┐  ││
│  │                  │  │  │                                         │  ││
│  │                  │  │  │  1. send_email              [Edit] [×]  │  ││
│  │                  │  │  │     ┌─────────────────────────────────┐ │  ││
│  │                  │  │  │     │      EMAIL v2 COMPONENT         │ │  ││
│  │                  │  │  │     │      (embedded inline)          │ │  ││
│  │                  │  │  │     │                                 │ │  ││
│  │                  │  │  │     │  To: [{email}              ]    │ │  ││
│  │                  │  │  │     │  Subject: [Thank you!      ]    │ │  ││
│  │                  │  │  │     │  [Open Visual Builder]          │ │  ││
│  │                  │  │  │     └─────────────────────────────────┘ │  ││
│  │                  │  │  │                                         │  ││
│  │                  │  │  │  2. update_entry_status      [Edit] [×]  │  ││
│  │                  │  │  │     Status: [completed ▼]               │  ││
│  │                  │  │  │                                         │  ││
│  │                  │  │  │  [+ Add Action]                         │  ││
│  │                  │  │  └─────────────────────────────────────────┘  ││
│  │                  │  │                                               ││
│  │ [+ Add Trigger]  │  │  ┌─ SETTINGS ──────────────────────────────┐  ││
│  │                  │  │  │ Execution Order: [10]                   │  ││
│  │                  │  │  │ ☑ Enabled                               │  ││
│  │                  │  │  │ Scope: ○ This form ○ All forms ○ Specific│  ││
│  │                  │  │  └─────────────────────────────────────────┘  ││
│  │                  │  │                                               ││
│  │                  │  │  [View Execution Logs]  [Test Trigger]        ││
│  │                  │  │                                               ││
│  └──────────────────┘  └───────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────────────┘

Storage: wp_superforms_triggers + wp_superforms_trigger_actions tables
```

### Data Flow

```
┌────────────────────┐     ┌────────────────────┐     ┌──────────────────┐
│  Triggers v2 Tab   │     │  Email v2 Tab      │     │  Old Triggers    │
│  (React)           │     │  (React)           │     │  Tab (PHP)       │
└─────────┬──────────┘     └─────────┬──────────┘     └────────┬─────────┘
          │                          │                          │
          │  Creates full            │  Creates email-only      │  Creates
          │  triggers with           │  triggers via sync       │  postmeta
          │  any action              │                          │  triggers
          │                          │                          │
          ▼                          ▼                          ▼
┌─────────────────────────────────────────────┐     ┌──────────────────┐
│  wp_superforms_triggers                     │     │  _super_trigger- │
│  wp_superforms_trigger_actions              │     │  postmeta        │
│                                             │     │  (legacy)        │
└─────────────────────────────────────────────┘     └──────────────────┘
          │                                                    │
          │                                                    │
          ▼                                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        SUPER_Trigger_Executor                            │
│                        (unified execution engine)                        │
└─────────────────────────────────────────────────────────────────────────┘
```

### Component Architecture

```
/src/react/triggers-v2/
├── src/
│   ├── index.jsx                    # Entry point, mounts to #super-triggers-v2-root
│   ├── App.jsx                      # Main app container
│   │
│   ├── components/
│   │   ├── TriggerList/
│   │   │   ├── TriggerList.jsx      # Left sidebar list
│   │   │   ├── TriggerListItem.jsx  # Individual trigger row
│   │   │   └── AddTriggerButton.jsx
│   │   │
│   │   ├── TriggerEditor/
│   │   │   ├── TriggerEditor.jsx    # Main editor container
│   │   │   ├── TriggerHeader.jsx    # Name, enabled toggle
│   │   │   ├── EventSelector.jsx    # Categorized event dropdown
│   │   │   ├── ScopeSelector.jsx    # This form / All forms / Specific
│   │   │   └── ExecutionOrder.jsx
│   │   │
│   │   ├── Conditions/
│   │   │   ├── ConditionsBuilder.jsx    # Visual AND/OR builder
│   │   │   ├── ConditionGroup.jsx       # AND or OR group
│   │   │   ├── ConditionRow.jsx         # Single condition (f1, logic, f2)
│   │   │   └── TagSelector.jsx          # Autocomplete for {tags}
│   │   │
│   │   ├── Actions/
│   │   │   ├── ActionsList.jsx          # Sortable actions list
│   │   │   ├── ActionItem.jsx           # Single action container
│   │   │   ├── ActionSelector.jsx       # Action type dropdown
│   │   │   │
│   │   │   ├── configs/                 # Action-specific config UIs
│   │   │   │   ├── SendEmailConfig.jsx  # EMBEDS Email v2 component
│   │   │   │   ├── WebhookConfig.jsx
│   │   │   │   ├── HttpRequestConfig.jsx
│   │   │   │   ├── UpdateEntryStatusConfig.jsx
│   │   │   │   ├── CreatePostConfig.jsx
│   │   │   │   ├── SetVariableConfig.jsx
│   │   │   │   └── ... (20 action configs)
│   │   │   │
│   │   │   └── ActionCondition.jsx      # Per-action condition
│   │   │
│   │   ├── Logs/
│   │   │   ├── ExecutionLogs.jsx        # Inline logs viewer
│   │   │   └── LogEntry.jsx
│   │   │
│   │   └── shared/
│   │       ├── TagInput.jsx             # Input with {tag} autocomplete
│   │       ├── ConfirmDialog.jsx
│   │       └── LoadingSpinner.jsx
│   │
│   ├── hooks/
│   │   ├── useTriggersStore.js          # Zustand store
│   │   ├── useFormTags.js               # Available {tags} for form
│   │   └── useEvents.js                 # Registered events list
│   │
│   ├── api/
│   │   ├── triggers.js                  # CRUD operations
│   │   └── logs.js                      # Fetch execution logs
│   │
│   └── styles/
│       └── index.css
│
├── package.json
└── webpack.config.js
```

## Events Registry

### Full Events List (36 events)

```javascript
const events = {
  'Session Events': {
    'session.started': 'User starts filling form',
    'session.auto_saved': 'Form auto-saved (partial)',
    'session.resumed': 'User resumes saved form',
    'session.completed': 'Session marked complete',
    'session.abandoned': 'Form abandoned (timeout)',
    'session.expired': 'Session expired',
  },
  'Form Events': {
    'form.loaded': 'Form displayed to user',
    'form.before_submit': 'Before form submission',
    'form.submitted': 'Form successfully submitted',
    'form.spam_detected': 'Spam submission blocked',
    'form.validation_failed': 'Validation errors occurred',
    'form.duplicate_detected': 'Duplicate submission detected',
  },
  'Entry Events': {
    'entry.created': 'New entry created',
    'entry.saved': 'Entry saved (create or update)',
    'entry.updated': 'Existing entry updated',
    'entry.status_changed': 'Entry status changed',
    'entry.deleted': 'Entry deleted',
  },
  'File Events': {
    'file.uploaded': 'File uploaded successfully',
    'file.upload_failed': 'File upload failed',
    'file.deleted': 'File deleted',
  },
  'Payment Events (Stripe)': {
    'payment.stripe.checkout_completed': 'Checkout session completed',
    'payment.stripe.payment_succeeded': 'Payment succeeded',
    'payment.stripe.payment_failed': 'Payment failed',
  },
  'Subscription Events (Stripe)': {
    'subscription.stripe.created': 'Subscription created',
    'subscription.stripe.updated': 'Subscription updated',
    'subscription.stripe.cancelled': 'Subscription cancelled',
    'subscription.stripe.invoice_paid': 'Invoice paid',
    'subscription.stripe.invoice_failed': 'Invoice payment failed',
  },
  'Payment Events (PayPal)': {
    'payment.paypal.capture_completed': 'Payment captured',
    'payment.paypal.capture_denied': 'Payment denied',
    'payment.paypal.capture_refunded': 'Payment refunded',
  },
  'Subscription Events (PayPal)': {
    'subscription.paypal.created': 'Subscription created',
    'subscription.paypal.activated': 'Subscription activated',
    'subscription.paypal.cancelled': 'Subscription cancelled',
    'subscription.paypal.suspended': 'Subscription suspended',
    'subscription.paypal.payment_failed': 'Payment failed',
  },
};
```

## Actions Registry

### Full Actions List (20 actions)

```javascript
const actions = {
  'Communication': {
    'send_email': {
      label: 'Send Email',
      description: 'Send an email notification',
      configComponent: 'SendEmailConfig',  // Embeds Email v2
    },
    'webhook': {
      label: 'Send Webhook',
      description: 'POST data to external URL',
      configComponent: 'WebhookConfig',
    },
  },
  'Integration': {
    'http_request': {
      label: 'HTTP Request',
      description: 'Make HTTP request (like Postman)',
      configComponent: 'HttpRequestConfig',
    },
  },
  'Data Management': {
    'update_entry_status': {
      label: 'Update Entry Status',
      description: 'Change entry status',
      configComponent: 'UpdateEntryStatusConfig',
    },
    'update_entry_field': {
      label: 'Update Entry Field',
      description: 'Modify entry field value',
      configComponent: 'UpdateEntryFieldConfig',
    },
    'delete_entry': {
      label: 'Delete Entry',
      description: 'Delete the contact entry',
      configComponent: 'DeleteEntryConfig',
    },
    'increment_counter': {
      label: 'Increment Counter',
      description: 'Increment a numeric counter',
      configComponent: 'IncrementCounterConfig',
    },
    'set_variable': {
      label: 'Set Variable',
      description: 'Set a variable for use in subsequent actions',
      configComponent: 'SetVariableConfig',
    },
  },
  'WordPress': {
    'create_post': {
      label: 'Create Post',
      description: 'Create post/page/CPT',
      configComponent: 'CreatePostConfig',
    },
    'update_post_meta': {
      label: 'Update Post Meta',
      description: 'Update post meta field',
      configComponent: 'UpdatePostMetaConfig',
    },
    'update_user_meta': {
      label: 'Update User Meta',
      description: 'Update user meta field',
      configComponent: 'UpdateUserMetaConfig',
    },
    'modify_user': {
      label: 'Modify User',
      description: 'Update user role/status',
      configComponent: 'ModifyUserConfig',
    },
  },
  'Flow Control': {
    'abort_submission': {
      label: 'Abort Submission',
      description: 'Stop form submission with error',
      configComponent: 'AbortSubmissionConfig',
    },
    'redirect_user': {
      label: 'Redirect User',
      description: 'Redirect to URL after submission',
      configComponent: 'RedirectUserConfig',
    },
    'stop_execution': {
      label: 'Stop Execution',
      description: 'Stop remaining actions',
      configComponent: 'StopExecutionConfig',
    },
    'conditional_action': {
      label: 'Conditional Branch',
      description: 'Execute nested actions conditionally',
      configComponent: 'ConditionalActionConfig',
    },
    'delay_execution': {
      label: 'Delay Execution',
      description: 'Wait before next action',
      configComponent: 'DelayExecutionConfig',
    },
  },
  'Utility': {
    'log_message': {
      label: 'Log Message',
      description: 'Log message for debugging',
      configComponent: 'LogMessageConfig',
    },
    'run_hook': {
      label: 'Run WordPress Hook',
      description: 'Execute do_action() hook',
      configComponent: 'RunHookConfig',
    },
    'clear_cache': {
      label: 'Clear Cache',
      description: 'Clear various caches',
      configComponent: 'ClearCacheConfig',
    },
  },
};
```

## Implementation Phases

### Phase 20.0: Prerequisites (Current work)
- [x] Fix Email v2 ↔ Triggers sync (`sync_emails_to_triggers`)
- [x] Implement `convert_triggers_to_emails_format` for migration display
- [ ] Verify all 20 action classes work correctly
- [ ] REST API endpoints for trigger CRUD

### Phase 20.1: Foundation & Scaffold
- [ ] Create `/src/react/triggers-v2/` directory structure
- [ ] Set up webpack config (copy from emails-v2)
- [ ] Create Zustand store (`useTriggersStore`)
- [ ] Build PHP tab handler (`triggers_v2_tab()`)
- [ ] Pass data to React via `window.superTriggersV2Data`
- [ ] Basic App.jsx with TriggerList and TriggerEditor layout

### Phase 20.2: Trigger CRUD
- [ ] TriggerList component with items
- [ ] Add/delete trigger functionality
- [ ] TriggerEditor basic fields (name, enabled)
- [ ] EventSelector with categorized dropdown
- [ ] ScopeSelector (this form / all / specific)
- [ ] Save trigger via AJAX
- [ ] Execution order input

### Phase 20.3: Conditions Builder
- [ ] ConditionsBuilder container
- [ ] ConditionGroup (AND/OR toggle)
- [ ] ConditionRow (f1, logic, f2)
- [ ] TagSelector with autocomplete
- [ ] Add/remove conditions
- [ ] Add/remove groups
- [ ] Complex nested conditions support

### Phase 20.4: Actions Framework
- [ ] ActionsList with drag-drop reorder
- [ ] ActionItem container
- [ ] ActionSelector dropdown (categorized)
- [ ] Per-action conditions
- [ ] Action enable/disable toggle
- [ ] Base action config component

### Phase 20.5: Email v2 Integration
- [ ] Extract Email v2 as embeddable component
- [ ] SendEmailConfig embeds Email v2
- [ ] Two modes: inline summary / full builder modal
- [ ] Sync between embedded and standalone Email v2
- [ ] Handle email templates

### Phase 20.6: Action Config UIs
- [ ] WebhookConfig (URL, method, headers, test)
- [ ] HttpRequestConfig (full Postman-like UI)
- [ ] UpdateEntryStatusConfig (status dropdown)
- [ ] UpdateEntryFieldConfig (field, value)
- [ ] CreatePostConfig (post type, fields mapping)
- [ ] SetVariableConfig (name, value, scope)
- [ ] DelayExecutionConfig (duration, type)
- [ ] ... remaining action configs

### Phase 20.7: Execution Logs
- [ ] ExecutionLogs panel (collapsible)
- [ ] Fetch logs via AJAX
- [ ] LogEntry display (time, status, details)
- [ ] Filter by status (success/error)
- [ ] Pagination
- [ ] Link to full logs page

### Phase 20.8: Testing & Debug
- [ ] Test Trigger button
- [ ] Mock context data input
- [ ] Live execution preview
- [ ] Debug mode toggle
- [ ] Validation errors display

### Phase 20.9: Migration & Polish
- [ ] Migration tool: Old triggers → New triggers
- [ ] Import/export triggers (JSON)
- [ ] Keyboard shortcuts
- [ ] Loading states
- [ ] Error handling
- [ ] Responsive design
- [ ] Accessibility (a11y)

### Phase 20.10: Documentation & Deprecation
- [ ] User documentation
- [ ] Developer hooks documentation
- [ ] Deprecation notice on old triggers tab
- [ ] Migration guide for users

## Zustand Store Schema

```javascript
// useTriggersStore.js
const useTriggersStore = create((set, get) => ({
  // State
  triggers: [],
  activeTrigger: null,
  isDirty: false,
  isSaving: false,
  error: null,

  // Available options (loaded from PHP)
  events: {},
  actions: {},
  formTags: [],

  // CRUD
  loadTriggers: async (formId) => { /* fetch from API */ },

  addTrigger: () => {
    const newTrigger = {
      id: `new_${Date.now()}`,
      name: 'New Trigger',
      event_id: '',
      scope: 'form',
      scope_id: formId,
      conditions: { enabled: false, groups: [] },
      actions: [],
      enabled: true,
      execution_order: 10,
      isNew: true,
    };
    set(state => ({
      triggers: [...state.triggers, newTrigger],
      activeTrigger: newTrigger.id,
      isDirty: true,
    }));
  },

  updateTrigger: (triggerId, updates) => { /* ... */ },
  deleteTrigger: (triggerId) => { /* ... */ },

  // Actions within trigger
  addAction: (triggerId, actionType) => { /* ... */ },
  updateAction: (triggerId, actionId, updates) => { /* ... */ },
  removeAction: (triggerId, actionId) => { /* ... */ },
  reorderActions: (triggerId, oldIndex, newIndex) => { /* ... */ },

  // Conditions
  updateConditions: (triggerId, conditions) => { /* ... */ },

  // Persistence
  save: async (formId) => { /* ... */ },

  // UI State
  setActiveTrigger: (triggerId) => { /* ... */ },
}));
```

## REST API Endpoints

```php
// New endpoints for Triggers v2
register_rest_route('super-forms/v1', '/forms/(?P<form_id>\d+)/triggers', [
    [
        'methods' => 'GET',
        'callback' => 'get_form_triggers',
        'permission_callback' => 'can_edit_form',
    ],
    [
        'methods' => 'POST',
        'callback' => 'create_trigger',
        'permission_callback' => 'can_edit_form',
    ],
]);

register_rest_route('super-forms/v1', '/triggers/(?P<id>\d+)', [
    [
        'methods' => 'GET',
        'callback' => 'get_trigger',
    ],
    [
        'methods' => 'PUT',
        'callback' => 'update_trigger',
    ],
    [
        'methods' => 'DELETE',
        'callback' => 'delete_trigger',
    ],
]);

register_rest_route('super-forms/v1', '/triggers/(?P<id>\d+)/test', [
    'methods' => 'POST',
    'callback' => 'test_trigger',
]);

register_rest_route('super-forms/v1', '/triggers/(?P<id>\d+)/logs', [
    'methods' => 'GET',
    'callback' => 'get_trigger_logs',
]);
```

## Email v2 Component Extraction

To embed Email v2 in Triggers v2, we need to extract it as a reusable component:

```javascript
// Current: Standalone Email v2 app
// src/react/emails-v2/src/index.jsx
ReactDOM.createRoot(rootElement).render(<App {...data} />);

// New: Exportable component
// src/react/emails-v2/src/EmailBuilder.jsx
export const EmailBuilder = ({
  email,           // Single email config
  onChange,        // Callback when email changes
  onSave,          // Optional save handler
  mode = 'full',   // 'full' | 'compact' | 'modal'
  formTags = [],   // Available {tags}
}) => {
  // ... component implementation
};

// Usage in Triggers v2:
// src/react/triggers-v2/src/components/Actions/configs/SendEmailConfig.jsx
import { EmailBuilder } from '@super-forms/emails-v2';

const SendEmailConfig = ({ action, onChange }) => {
  const [showBuilder, setShowBuilder] = useState(false);

  return (
    <div>
      {/* Compact summary */}
      <div className="email-summary">
        <span>To: {action.config.to}</span>
        <span>Subject: {action.config.subject}</span>
        <button onClick={() => setShowBuilder(true)}>
          Edit Email
        </button>
      </div>

      {/* Full builder modal */}
      {showBuilder && (
        <Modal onClose={() => setShowBuilder(false)}>
          <EmailBuilder
            email={action.config}
            onChange={(updated) => onChange({ ...action, config: updated })}
            mode="full"
          />
        </Modal>
      )}
    </div>
  );
};
```

## Success Criteria

### Core Functionality
- [ ] Users can create/edit/delete triggers via React UI
- [ ] All 36 events selectable with categories
- [ ] All 20 actions configurable with dedicated UIs
- [ ] Complex AND/OR conditions buildable visually
- [ ] Email v2 embedded inline for send_email action
- [ ] Triggers saved to database tables
- [ ] Triggers execute correctly via executor

### User Experience
- [ ] Intuitive, modern interface
- [ ] Consistent with Email v2 design language
- [ ] Fast, responsive interactions
- [ ] Clear feedback on save/errors
- [ ] Execution logs visible inline

### Integration
- [ ] Works alongside Email v2 tab
- [ ] Works alongside old triggers tab (during transition)
- [ ] Proper data sync between systems
- [ ] REST API fully functional

### Migration
- [ ] Old triggers importable to new system
- [ ] Clear deprecation path for old tab

## Technical Considerations

### Shared Components with Email v2
- Tag input with autocomplete
- Condition builder logic
- Loading/saving states
- Error handling patterns

### Build Configuration
- Separate webpack entry for triggers-v2
- Shared node_modules with emails-v2
- CSS modules or Tailwind for styling

### Performance
- Lazy load action config components
- Virtualized list for many triggers
- Debounced auto-save
- Optimistic UI updates

## Dependencies

- Phase 1-5: Core trigger system - COMPLETE
- Phase 11: Email migration - IN PROGRESS
- Email v2 React app - EXISTS
- All 20 action classes - COMPLETE

## Estimated Effort

| Phase | Effort | Priority |
|-------|--------|----------|
| 20.0 Prerequisites | 1 day | P0 |
| 20.1 Foundation | 2 days | P0 |
| 20.2 Trigger CRUD | 2 days | P0 |
| 20.3 Conditions | 3 days | P1 |
| 20.4 Actions Framework | 2 days | P0 |
| 20.5 Email v2 Integration | 3 days | P0 |
| 20.6 Action Configs | 5 days | P1 |
| 20.7 Execution Logs | 2 days | P2 |
| 20.8 Testing/Debug | 2 days | P1 |
| 20.9 Migration/Polish | 3 days | P2 |
| 20.10 Documentation | 1 day | P2 |
| **Total** | **~26 days** | |

## Open Questions

1. **Tab naming**: "Triggers v2" or "Automation" or "Workflows"?
2. **Coexistence**: Show both old and new tabs during transition, or replace immediately?
3. **Email v2 tab**: Keep separate or merge into Triggers v2?
4. **Global triggers**: How to handle triggers that apply to all forms?
5. **Templates**: Pre-built trigger templates (e.g., "Send admin email on submit")?

## Notes

- This is a significant React project (~26 days estimated)
- Consider implementing in phases, shipping incrementally
- Could potentially use a low-code builder library
- Email v2 extraction is critical path
- Old triggers system continues working during transition
