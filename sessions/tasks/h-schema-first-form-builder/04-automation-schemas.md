---
name: 04-automation-schemas
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 4: Automation Node Schemas

## Goal

Create schemas for all automation workflow nodes (triggers, actions, conditions, control).

## Dependencies

- Phase 1 (Schema Foundation) must be complete

## Node Categories

### Triggers (6 nodes)

Events that start automations:

| ID | Name | Priority |
|----|------|----------|
| `form.submitted` | Form Submitted | High |
| `entry.updated` | Entry Updated | Medium |
| `entry.deleted` | Entry Deleted | Low |
| `schedule` | Schedule (cron) | Medium |
| `webhook` | Webhook Received | Medium |
| `payment.completed` | Payment Completed | Low |

### Actions (10 nodes)

Tasks the automation performs:

| ID | Name | Priority |
|----|------|----------|
| `send_email` | Send Email | High |
| `http_request` | HTTP Request | High |
| `create_entry` | Create Entry | Medium |
| `update_entry` | Update Entry | Medium |
| `delete_entry` | Delete Entry | Low |
| `create_post` | Create Post | Medium |
| `update_user` | Update User | Low |
| `log_message` | Log Message | Low |
| `set_variable` | Set Variable | Medium |
| `webhook` | Send Webhook | Medium |

### Conditions (5 nodes)

Branching logic:

| ID | Name | Priority |
|----|------|----------|
| `field_comparison` | Field Comparison | High |
| `entry_exists` | Entry Exists | Low |
| `user_role` | User Role Check | Medium |
| `a_b_test` | A/B Test | Low |
| `custom_condition` | Custom PHP | Low |

### Control (4 nodes)

Flow control:

| ID | Name | Priority |
|----|------|----------|
| `delay` | Delay | High |
| `schedule` | Schedule Until | Medium |
| `stop_execution` | Stop Execution | Low |
| `loop` | Loop | Low |

## Node Schema Structure

```typescript
interface NodeSchema {
  id: string;                    // Unique identifier
  name: string;                  // Display name
  description: string;           // Short description
  category: 'trigger' | 'action' | 'condition' | 'control';
  icon: string;                  // Lucide icon name

  // Configuration properties
  config: Record<string, PropertySchema>;

  // Connection points
  inputs: string[];              // Input handle names
  outputs: string[];             // Output handle names ('dynamic' for A/B test)

  // Context provided to downstream nodes
  availableContext?: string[];   // For triggers
  outputContext?: string[];      // For actions that add context
}
```

## File Structure

```
/src/schemas/automations/
├── types.ts                 # NodeSchema interface
├── triggers.schema.ts       # All trigger schemas
├── actions.schema.ts        # All action schemas
├── conditions.schema.ts     # All condition schemas
├── control.schema.ts        # All control schemas
└── index.ts                 # Exports
```

## Schema Details

### Trigger Example: form.submitted

```typescript
'form.submitted': {
  id: 'form.submitted',
  name: 'Form Submitted',
  description: 'Triggers when a form is successfully submitted',
  category: 'trigger',
  icon: 'send',

  config: {
    formId: {
      type: PropertyType.SELECT,
      label: 'Form',
      options: 'forms',  // Dynamically populated
      required: true,
    },
  },

  inputs: [],
  outputs: ['success'],

  availableContext: [
    'form_id',
    'entry_id',
    'form_data',
    'user_id',
    'user_email',
    'ip_address',
    'submission_time',
  ],
}
```

### Action Example: send_email

```typescript
'send_email': {
  id: 'send_email',
  name: 'Send Email',
  description: 'Send an email notification',
  category: 'action',
  icon: 'mail',

  config: {
    to: {
      type: PropertyType.STRING,
      label: 'To',
      required: true,
      supportsTags: true,
      description: 'Use {email} for submitted email',
    },
    subject: {
      type: PropertyType.STRING,
      label: 'Subject',
      required: true,
      supportsTags: true,
    },
    fromName: {
      type: PropertyType.STRING,
      label: 'From Name',
      supportsTags: true,
    },
    fromEmail: {
      type: PropertyType.STRING,
      label: 'From Email',
      supportsTags: true,
    },
    replyTo: {
      type: PropertyType.STRING,
      label: 'Reply To',
      supportsTags: true,
    },
    emailBody: {
      type: PropertyType.EMAIL_TEMPLATE,
      label: 'Email Body',
      description: 'Design with visual builder',
    },
    attachments: {
      type: PropertyType.STRING,
      label: 'Attachments',
      description: 'Use {file_field} for uploads',
      supportsTags: true,
    },
  },

  inputs: ['trigger'],
  outputs: ['success', 'failure'],
}
```

### Condition Example: field_comparison

```typescript
'field_comparison': {
  id: 'field_comparison',
  name: 'Field Comparison',
  description: 'Branch based on field value',
  category: 'condition',
  icon: 'git-branch',

  config: {
    field: {
      type: PropertyType.FIELD_REFERENCE,
      label: 'Field',
      required: true,
    },
    operator: {
      type: PropertyType.SELECT,
      label: 'Operator',
      options: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not Equals' },
        { value: 'contains', label: 'Contains' },
        { value: 'greater_than', label: 'Greater Than' },
        { value: 'less_than', label: 'Less Than' },
        { value: 'is_empty', label: 'Is Empty' },
        { value: 'is_not_empty', label: 'Is Not Empty' },
      ],
    },
    value: {
      type: PropertyType.STRING,
      label: 'Value',
      supportsTags: true,
      showWhen: { field: 'operator', notIn: ['is_empty', 'is_not_empty'] },
    },
  },

  inputs: ['trigger'],
  outputs: ['true', 'false'],
}
```

### Control Example: delay

```typescript
'delay': {
  id: 'delay',
  name: 'Delay',
  description: 'Wait before continuing',
  category: 'control',
  icon: 'timer',

  config: {
    delayType: {
      type: PropertyType.SELECT,
      label: 'Delay Type',
      options: [
        { value: 'duration', label: 'Duration' },
        { value: 'until', label: 'Until specific time' },
      ],
    },
    duration: {
      type: PropertyType.NUMBER,
      label: 'Duration',
      min: 1,
      showWhen: { field: 'delayType', equals: 'duration' },
    },
    durationUnit: {
      type: PropertyType.SELECT,
      label: 'Unit',
      options: [
        { value: 'seconds', label: 'Seconds' },
        { value: 'minutes', label: 'Minutes' },
        { value: 'hours', label: 'Hours' },
        { value: 'days', label: 'Days' },
      ],
      default: 'minutes',
      showWhen: { field: 'delayType', equals: 'duration' },
    },
    untilTime: {
      type: PropertyType.DATETIME,
      label: 'Until',
      showWhen: { field: 'delayType', equals: 'until' },
      supportsTags: true,
    },
  },

  inputs: ['trigger'],
  outputs: ['next'],
}
```

## Dynamic Options

Some properties have dynamic options loaded at runtime:

| Option Key | Source |
|------------|--------|
| `forms` | GET /super-forms/v1/forms |
| `form_fields` | GET /super-forms/v1/forms/{id}/fields |
| `wp_post_types` | WordPress post types |
| `wp_users` | WordPress users |
| `wp_roles` | WordPress roles |
| `wp_pages` | WordPress pages |

Mark these with `options: 'key_name'` (string instead of array).

## Acceptance Criteria

- [ ] All trigger schemas complete (6)
- [ ] All action schemas complete (10)
- [ ] All condition schemas complete (5)
- [ ] All control schemas complete (4)
- [ ] `supportsTags` marked on appropriate fields
- [ ] `showWhen` conditions work correctly
- [ ] Dynamic options use string keys
- [ ] `availableContext` defined for triggers
- [ ] `outputContext` defined where applicable
- [ ] Exports work from index.ts

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Section 8)
- **Foundation**: `/src/schemas/types.ts`
- **Existing Registry**: `src/includes/automations/class-automation-registry.php`
- **Output**: `/src/schemas/automations/`

### Reference
- Current node types: `src/react/admin/components/form-builder/automations/types/workflow.types.ts`
- Node editor: `src/react/admin/components/form-builder/automations/hooks/useNodeEditor.ts`

## Work Log
- [2025-12-03] Task created
