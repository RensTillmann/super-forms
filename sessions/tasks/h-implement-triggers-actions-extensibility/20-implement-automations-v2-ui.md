# Phase 20: Automations - Modern React-Based Automation Management UI

## Terminology Reference

See [Phase 26: Terminology Standardization](26-terminology-standardization.md) for the definitive naming convention:
- **Automation** = The saved entity (container)
- **Trigger** = Event node that starts execution
- **Action** = Task node that does something
- **Condition** = Logic node for branching
- **Control** = Flow node (delay, schedule, stop)

## Overview

Build a modern React-based Automations management interface that replaces the legacy PHP/jQuery Triggers tab. This enables users to visually manage all 36 trigger events, 20 actions, and complex conditions through an intuitive UI. The Email v2 component will be embedded inline when configuring `send_email` actions.

## Problem Statement

### Current State (Old Triggers Tab)

| Limitation | Impact |
|------------|--------|
| Only 5 events exposed | Users can't access 31 other trigger events (payment, subscription, file, session) |
| Only 5 actions available | Users can't use 15 other actions (http_request, webhook, create_post, etc.) |
| Basic inline email config | No access to Email v2's rich builder, templates, visual editor |
| Simple conditions (f1, logic, f2) | Can't build complex AND/OR condition groups |
| No execution logs | Users can't debug why automations didn't fire |
| PHP/jQuery UI | Outdated, inconsistent with Email v2's modern React approach |
| Stored in postmeta | Scattered across `_super_trigger-%` keys, hard to query |

### New Automation System (No UI)

The backend automation system is powerful but invisible to users:
- **36 trigger events**: form.*, entry.*, payment.stripe.*, subscription.*, file.*, session.*
- **20 actions**: send_email, webhook, http_request, create_post, update_entry_status, etc.
- **Complex conditions**: AND/OR/NOT grouping with tag replacement
- **Execution logging**: Full audit trail with timing and errors
- **Database storage**: Proper relational tables for querying

## Architecture

### Target UI Layout (List-Based Prototype - SUPERSEDED)

> NOTE: This section describes an earlier list-based Automations UI with a single “TriggerSelector” and “ScopeSelector” at the automation level.
> Our **final architecture** (see Phase 22) uses a **visual node-based workflow builder** where:
> - Events are represented as **Trigger nodes on the canvas** (dragged from a palette)
> - Scope (this form / all forms / specific) and form targeting live in each **event node’s `config`**, not at the automation level
> - Conditions and actions are separate **Condition** and **Action** nodes connected in the graph
>
> When implementing the production UI, use this layout only as a rough UX inspiration for lists/logs, and follow **Phase 22** for the actual node-based canvas and node-level scope.

```
┌─────────────────────────────────────────────────────────────────────────┐
│  AUTOMATIONS TAB                                              [+ Add]   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────┐  ┌───────────────────────────────────────────────┐│
│  │ AUTOMATION LIST  │  │ AUTOMATION EDITOR                             ││
│  │                  │  │                                               ││
│  │ ○ Admin Email    │  │  Name: [Send Admin Email on Submission    ]  ││
│  │   form.submitted │  │                                               ││
│  │   ✓ Enabled      │  │  Trigger: [form.submitted               ▼]  ││
│  │                  │  │           ├─ Form Events                      ││
│  │ ○ Payment Email  │  │           │  ├─ form.loaded                   ││
│  │   payment.stripe │  │           │  ├─ form.submitted ←              ││
│  │   ✓ Enabled      │  │           │  ├─ form.validation_failed        ││
│  │                  │  │           │  └─ form.spam_detected            ││
│  │ ● Webhook →      │  │           ├─ Entry Events                     ││
│  │   entry.created  │  │           │  ├─ entry.created                 ││
│  │   ○ Disabled     │  │           │  └─ entry.status_changed          ││
│  │                  │  │           ├─ Payment Events (Stripe)          ││
│  │                  │  │           │  ├─ payment.stripe.checkout_completed│
│  │                  │  │           │  └─ ...                           ││
│  │                  │  │           └─ ...more categories               ││
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
│  │ [+ Add Automation]│  │  ┌─ SETTINGS ──────────────────────────────┐  ││
│  │                  │  │  │ Execution Order: [10]                   │  ││
│  │                  │  │  │ ☑ Enabled                               │  ││
│  │                  │  │  │ Scope: ○ This form ○ All forms ○ Specific│  ││
│  │                  │  │  └─────────────────────────────────────────┘  ││
│  │                  │  │                                               ││
│  │                  │  │  [View Execution Logs]  [Test Automation]     ││
│  │                  │  │                                               ││
│  └──────────────────┘  └───────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────────────┘

Storage (historical prototype): wp_superforms_automations + wp_superforms_automation_actions tables

> FINAL ARCHITECTURE (after Phases 25/26):
> - Visual mode: `wp_superforms_automations.workflow_graph` (JSON nodes/connections)
> - Code/List mode: `wp_superforms_automations` + `wp_superforms_automation_actions` (1:N actions for `type='code'`)
> Visual builder implementations should rely on `workflow_graph` for nodes/connections; `automation_actions` is only used for non-visual (code) automations.
```

### Data Flow

```
┌────────────────────┐     ┌────────────────────┐     ┌──────────────────┐
│  Automations Tab   │     │  Email v2 Tab      │     │  Old Triggers    │
│  (React)           │     │  (React)           │     │  Tab (PHP)       │
└─────────┬──────────┘     └─────────┬──────────┘     └────────┬─────────┘
          │                          │                          │
          │  Creates full            │  Creates email-only      │  Creates
          │  automations with        │  automations via sync    │  postmeta
          │  any action              │                          │  triggers
          │                          │                          │
          ▼                          ▼                          ▼
┌─────────────────────────────────────────────┐     ┌──────────────────┐
│  wp_superforms_automations                  │     │  _super_trigger- │
│  wp_superforms_automation_actions           │     │  postmeta        │
│                                             │     │  (legacy)        │
└─────────────────────────────────────────────┘     └──────────────────┘
          │                                                    │
          │                                                    │
          ▼                                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        SUPER_Automation_Executor                         │
│                        (unified execution engine)                        │
└─────────────────────────────────────────────────────────────────────────┘
```

### Component Architecture

```
/src/react/automations/
├── src/
│   ├── index.jsx                    # Entry point, mounts to #super-automations-root
│   ├── App.jsx                      # Main app container
│   │
│   ├── components/
│   │   ├── AutomationList/
│   │   │   ├── AutomationList.jsx      # Left sidebar list
│   │   │   ├── AutomationListItem.jsx  # Individual automation row
│   │   │   └── AddAutomationButton.jsx
│   │   │
│   │   ├── AutomationEditor/
│   │   │   ├── AutomationEditor.jsx    # Main editor container
│   │   │   ├── AutomationHeader.jsx    # Name, enabled toggle
│   │   │   ├── TriggerSelector.jsx     # (HISTORICAL) Automation-level event dropdown – superseded by event nodes in Phase 22
│   │   │   ├── ScopeSelector.jsx       # (HISTORICAL) Automation-level scope – superseded by node-level scope on trigger nodes
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
│   │   ├── useAutomationsStore.js       # Zustand store
│   │   ├── useFormTags.js               # Available {tags} for form
│   │   └── useTriggerEvents.js          # Registered trigger events list
│   │
│   ├── api/
│   │   ├── automations.js               # CRUD operations
│   │   └── logs.js                      # Fetch execution logs
│   │
│   └── styles/
│       └── index.css
│
├── package.json
└── webpack.config.js
```

## Trigger Events Registry

### Full Trigger Events List (36 events)

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
- [x] Fix Email v2 ↔ Automations sync (`sync_emails_to_automations`)
- [x] Implement `convert_automations_to_emails_format` for migration display
- [ ] Verify all 20 action classes work correctly
- [ ] REST API endpoints for automation CRUD

### Phase 20.1: Foundation & Scaffold
- [ ] Create `/src/react/automations/` directory structure
- [ ] Set up webpack config (copy from emails-v2)
- [ ] Create Zustand store (`useAutomationsStore`)
- [ ] Build PHP tab handler (`automations_tab()`)
- [ ] Pass data to React via `window.superAutomationsData`
- [ ] Basic App.jsx with AutomationList and AutomationEditor layout

### Phase 20.2: Automation CRUD
- [ ] AutomationList component with items
- [ ] Add/delete automation functionality
- [ ] AutomationEditor basic fields (name, enabled)
- [ ] TriggerSelector with categorized dropdown
- [ ] ScopeSelector (this form / all / specific)
- [ ] Save automation via AJAX
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
- [ ] Test Automation button
- [ ] Mock context data input
- [ ] Live execution preview
- [ ] Debug mode toggle
- [ ] Validation errors display

### Phase 20.9: Migration & Polish
- [ ] Migration tool: Old triggers → New automations
- [ ] Import/export automations (JSON)
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
// useAutomationsStore.js
const useAutomationsStore = create((set, get) => ({
  // State
  automations: [],
  activeAutomation: null,
  isDirty: false,
  isSaving: false,
  error: null,

  // Available options (loaded from PHP)
  triggerEvents: {},
  actions: {},
  formTags: [],

  // CRUD
  loadAutomations: async (formId) => { /* fetch from API */ },

  addAutomation: () => {
    const newAutomation = {
      id: `new_${Date.now()}`,
      name: 'New Automation',
      type: 'visual',
      workflow_graph: {
        nodes: [],
        connections: []
      },
      enabled: true,
      isNew: true,
    };
    set(state => ({
      automations: [...state.automations, newAutomation],
      activeAutomation: newAutomation.id,
      isDirty: true,
    }));
  },

  updateAutomation: (automationId, updates) => { /* ... */ },
  deleteAutomation: (automationId) => { /* ... */ },

  // Actions within automation
  addAction: (automationId, actionType) => { /* ... */ },
  updateAction: (automationId, actionId, updates) => { /* ... */ },
  removeAction: (automationId, actionId) => { /* ... */ },
  reorderActions: (automationId, oldIndex, newIndex) => { /* ... */ },

  // Conditions
  updateConditions: (automationId, conditions) => { /* ... */ },

  // Persistence
  save: async (formId) => { /* ... */ },

  // UI State
  setActiveAutomation: (automationId) => { /* ... */ },
}));
```

## REST API Endpoints

```php
// New endpoints for Automations
register_rest_route('super-forms/v1', '/forms/(?P<form_id>\d+)/automations', [
    [
        'methods' => 'GET',
        'callback' => 'get_form_automations',
        'permission_callback' => 'can_edit_form',
    ],
    [
        'methods' => 'POST',
        'callback' => 'create_automation',
        'permission_callback' => 'can_edit_form',
    ],
]);

register_rest_route('super-forms/v1', '/automations/(?P<id>\d+)', [
    [
        'methods' => 'GET',
        'callback' => 'get_automation',
    ],
    [
        'methods' => 'PUT',
        'callback' => 'update_automation',
    ],
    [
        'methods' => 'DELETE',
        'callback' => 'delete_automation',
    ],
]);

register_rest_route('super-forms/v1', '/automations/(?P<id>\d+)/test', [
    'methods' => 'POST',
    'callback' => 'test_automation',
]);

register_rest_route('super-forms/v1', '/automations/(?P<id>\d+)/logs', [
    'methods' => 'GET',
    'callback' => 'get_automation_logs',
]);
```

## Email v2 Component Extraction

To embed Email v2 in the Automations UI, we need to extract it as a reusable component:

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

// Usage in Automations UI:
// src/react/automations/src/components/Actions/configs/SendEmailConfig.jsx
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
- [ ] Users can create/edit/delete automations via React UI
- [ ] All 36 trigger events selectable with categories
- [ ] All 20 actions configurable with dedicated UIs
- [ ] Complex AND/OR conditions buildable visually
- [ ] Email v2 embedded inline for send_email action
- [ ] Automations saved to database tables
- [ ] Automations execute correctly via executor

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
- [ ] Old triggers importable to new automations system
- [ ] Clear deprecation path for old tab

## Technical Considerations

### Shared Components with Email v2
- Tag input with autocomplete
- Condition builder logic
- Loading/saving states
- Error handling patterns

### Build Configuration
- Separate webpack entry for automations
- Shared node_modules with emails-v2
- CSS modules or Tailwind for styling

### Performance
- Lazy load action config components
- Virtualized list for many automations
- Debounced auto-save
- Optimistic UI updates

## Dependencies

- Phase 1-5: Core automation system - COMPLETE
- Phase 11: Email migration - IN PROGRESS
- Email v2 React app - EXISTS
- All 20 action classes - COMPLETE

## Estimated Effort

| Phase | Effort | Priority |
|-------|--------|----------|
| 20.0 Prerequisites | 1 day | P0 |
| 20.1 Foundation | 2 days | P0 |
| 20.2 Automation CRUD | 2 days | P0 |
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

1. **Tab naming**: ✅ RESOLVED - Use "Automations" (see Phase 26)
2. **Coexistence**: Show both old and new tabs during transition, or replace immediately?
3. **Email v2 tab**: Keep separate or merge into Automations?
4. **Global automations**: How to handle automations that apply to all forms?
5. **Templates**: Pre-built automation templates (e.g., "Send admin email on submit")?

## Notes

- This is a significant React project (~26 days estimated)
- Consider implementing in phases, shipping incrementally
- Could potentially use a low-code builder library
- Email v2 extraction is critical path
- Old triggers system continues working during transition
