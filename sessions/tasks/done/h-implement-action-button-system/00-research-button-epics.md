---
name: 00-research-button-epics
status: completed
created: 2025-12-15
completed: 2025-12-15
---

# Research: Button Use Case Epics

## Problem/Goal

Research and document comprehensive use cases for action buttons in form builders. This research will inform:

1. **Button element schema design** - What properties/configs are needed
2. **Automation node types** - What new nodes should be created
3. **Event system requirements** - How button events flow to automations
4. **MCP tool design** - How LLMs can configure buttons effectively

## Success Criteria

- [x] Use Brave search to research button patterns in popular form builders
- [x] Research automation/workflow trigger patterns from tools like Zapier, Make, n8n
- [x] Document 10+ distinct epics with full schema implications
- [x] Create epic files in `epics/` directory
- [x] Map Zod schema requirements for each epic
- [x] Define MCP tool parameters needed for LLM integration
- [x] Identify new automation node types required

## Research Summary

### Key Findings from Form Builders

**JotForm:**
- Multiple submit buttons possible but trigger same action
- Card layout with automatic Back/Next buttons
- Conditional show/hide of submit button
- "Save and Continue Later" with email resume link
- Button styling customization

**Gravity Forms:**
- Submit button customizable via `gform_submit_button` filter
- Multiple buttons via HTML field + custom JavaScript
- Button field type addon (supports button/submit/reset types)
- Multi-Page Navigation perk with custom page links
- Advanced Save & Continue with auto-saving

**Form.io (Most Relevant Pattern):**
- Button component with `action` property: `'submit'` | `'saveState'` | `'reset'`
- `saveState` action with `state: 'draft'` for save-as-draft
- Auto-save every N seconds with `saveDraftThrottle`
- Manual + automatic draft saving patterns

**Typeform:**
- One question per page, conversational style
- Conditional logic for question branching
- Less focus on multi-button patterns

### Key Findings from Automation Tools

**n8n:**
- Webhook node as trigger - any HTTP POST can start workflow
- Form Trigger node with customizable Button Label
- Multiple triggers per workflow supported
- Pause workflow to wait for external events

**Zapier:**
- "Trigger Zaps from webhooks" pattern
- REST Hooks for webhook subscriptions
- One trigger per Zap (limitation)
- Webhooks are premium feature

**Make (Integromat):**
- Webhook module as universal trigger
- Can chain multiple actions after trigger
- Supports await/async patterns

## Epics Created (14 Total)

| # | Epic | Action Type | New Nodes Needed |
|---|------|-------------|------------------|
| 01 | [Submit Form](./epics/01-submit-form.md) | `submit` | None |
| 02 | [Save Draft](./epics/02-save-draft.md) | `save_state` | `generate_temp_access` |
| 03 | [Generate PDF](./epics/03-generate-pdf.md) | `trigger_automation` | `generate_file` |
| 04 | [Open Modal](./epics/04-open-modal.md) | `open_overlay` | `show_overlay` |
| 05 | [Open Drawer](./epics/05-open-drawer.md) | `open_overlay` | `show_overlay` |
| 06 | [Navigate Wizard](./epics/06-navigate-wizard.md) | `navigate` | `wizard_control` |
| 07 | [Payment/Checkout](./epics/07-payment-checkout.md) | `trigger_automation` | `create_checkout_session`, `verify_payment` |
| 08 | [Webhook/External](./epics/08-webhook-external.md) | `trigger_automation` | `await_webhook_response` |
| 09 | [Show/Hide Element](./epics/09-show-hide-element.md) | `toggle_visibility` | None (frontend only) |
| 10 | [Reset Form](./epics/10-reset-form.md) | `reset` | None (frontend only) |
| 11 | [Download File](./epics/11-download-file.md) | `trigger_automation` | `create_download_response` |
| 12 | [Calculate/Validate](./epics/12-calculate-validate.md) | `trigger_automation` | `calculate`, `validate_data`, `lookup_value` |
| 13 | [Temp Access Link](./epics/13-temp-access-link.md) | `trigger_automation` | `generate_temp_access`, `revoke_access` |
| 14 | [Copy to Clipboard](./epics/14-copy-to-clipboard.md) | `copy_to_clipboard` | None (frontend only) |

---

## Synthesized Schema Requirements

### Unified Button Element Schema (Zod)

```typescript
import { z } from 'zod';

/**
 * Button Action Types - determines the primary behavior
 */
export const ButtonActionTypeSchema = z.enum([
  'submit',           // Standard form submission
  'save_state',       // Save draft/partial submission
  'reset',            // Clear form fields
  'navigate',         // Multi-step wizard navigation
  'trigger_automation', // Fire custom event for automation
  'open_overlay',     // Open modal/drawer/popup
  'toggle_visibility', // Show/hide other elements
  'copy_to_clipboard', // Copy content to clipboard
]);

/**
 * Overlay Types for open_overlay action
 */
export const OverlayTypeSchema = z.enum([
  'modal',    // Centered modal dialog
  'dialog',   // Alert-style dialog
  'popup',    // Smaller popup box
  'drawer',   // Side drawer
  'tray',     // Bottom tray (mobile-friendly)
  'sheet',    // Full-height sheet
]);

/**
 * Navigation Directions for navigate action
 */
export const NavigateDirectionSchema = z.enum([
  'next',      // Go to next step
  'previous',  // Go to previous step
  'first',     // Go to first step
  'last',      // Go to last step (or submit)
  'specific',  // Jump to specific step
]);

/**
 * Button Variants (visual style)
 */
export const ButtonVariantSchema = z.enum([
  'primary',   // Main action (filled, primary color)
  'secondary', // Secondary action (filled, neutral)
  'outline',   // Bordered, transparent background
  'ghost',     // No border, minimal styling
  'link',      // Looks like a link
  'destructive', // Red/warning style for dangerous actions
]);

/**
 * Button Sizes
 */
export const ButtonSizeSchema = z.enum(['xs', 'sm', 'md', 'lg', 'xl']);

/**
 * Complete Button Element Schema
 */
export const ButtonElementSchema = z.object({
  // === Core Identity ===
  type: z.literal('button'),
  id: z.string(),

  // === Action Configuration ===
  action_type: ButtonActionTypeSchema,

  // For trigger_automation: custom event identifier
  event_id: z.string().regex(/^[a-z][a-z0-9_]*$/).optional(),

  // For save_state: what state to save as
  state: z.enum(['draft', 'pending_review', 'incomplete']).optional(),

  // For navigate: direction and target
  navigate_direction: NavigateDirectionSchema.optional(),
  target_step: z.union([z.number(), z.string()]).optional(),

  // For open_overlay: overlay configuration
  overlay_type: OverlayTypeSchema.optional(),
  overlay_title: z.string().optional(),
  overlay_content: z.string().optional(),
  overlay_size: z.enum(['sm', 'md', 'lg', 'xl', 'fullscreen']).optional(),
  drawer_position: z.enum(['left', 'right', 'top', 'bottom']).optional(),

  // For toggle_visibility: target elements
  target_elements: z.array(z.string()).optional(),
  visibility_action: z.enum(['show', 'hide', 'toggle']).optional(),

  // For copy_to_clipboard: content source
  copy_source: z.enum(['static', 'field', 'template', 'automation_result']).optional(),
  copy_content: z.string().optional(),
  source_field: z.string().optional(),

  // === Text & Labels ===
  button_text: z.string(),
  button_text_toggled: z.string().optional(), // For toggle buttons
  loading_text: z.string().optional(),
  success_text: z.string().optional(),

  // === Feedback Messages ===
  success_message: z.string().optional(),
  error_message: z.string().optional(),
  confirmation_message: z.string().optional(),

  // === Visual Styling ===
  variant: ButtonVariantSchema.default('primary'),
  size: ButtonSizeSchema.default('md'),
  icon: z.string().optional(),          // Lucide icon name
  icon_position: z.enum(['left', 'right']).default('left'),
  icon_toggled: z.string().optional(),  // Icon for toggled state
  full_width: z.boolean().default(false),

  // === Behavior Flags ===
  disabled_until_valid: z.boolean().default(false),
  confirm_before_action: z.boolean().default(false),
  validate_before_action: z.boolean().default(true),
  await_response: z.boolean().default(true),

  // === Conditional Logic ===
  disabled_when: z.array(PropertyConditionSchema).optional(),
  hidden_when: z.array(PropertyConditionSchema).optional(),
});

export type ButtonElement = z.infer<typeof ButtonElementSchema>;
```

### New Automation Node Types Required

```typescript
/**
 * New automation action nodes to implement
 */

// 1. Generate File (PDF, DOCX, CSV, etc.)
interface GenerateFileAction {
  type: 'generate_file';
  config: {
    template_id: string;
    output_format: 'pdf' | 'docx' | 'xlsx' | 'csv';
    filename_pattern: string; // Supports {tags}
    upload_to_media: boolean;
  };
  returns: {
    file_url: string;
    attachment_id?: number;
    file_size: number;
  };
}

// 2. Show Overlay (Modal, Drawer, Popup)
interface ShowOverlayAction {
  type: 'show_overlay';
  config: {
    overlay_type: 'modal' | 'drawer' | 'popup' | 'dialog';
    title: string;
    content_source: 'static' | 'form_section' | 'entry_data' | 'url';
    content: string;
    size: 'sm' | 'md' | 'lg' | 'xl';
  };
  returns: {
    displayed: true;
    overlay_id: string;
  };
}

// 3. Generate Temporary Access
interface GenerateTempAccessAction {
  type: 'generate_temp_access';
  config: {
    resource_type: 'entry' | 'file' | 'form';
    resource_id: number;
    access_type: 'view' | 'edit' | 'download';
    expiry_duration: string; // '1h', '24h', '7d', '30d'
    max_uses?: number;
  };
  returns: {
    access_url: string;
    token: string;
    expires_at: string; // ISO timestamp
  };
}

// 4. Create Checkout Session (Stripe/PayPal)
interface CreateCheckoutSessionAction {
  type: 'create_checkout_session';
  config: {
    provider: 'stripe' | 'paypal' | 'square';
    amount: number | string; // Can be tag like {calculated_total}
    currency: string;
    description: string;
    success_url: string;
    cancel_url: string;
    line_items?: Array<{ name: string; amount: number; quantity: number }>;
  };
  returns: {
    checkout_url: string;
    session_id: string;
    payment_intent_id?: string;
  };
}

// 5. Calculate Expression
interface CalculateAction {
  type: 'calculate';
  config: {
    expression: string; // '{quantity} * {unit_price} * (1 + {tax_rate})'
    precision: number;
    format: 'number' | 'currency' | 'percentage';
  };
  returns: {
    result: number;
    formatted_result: string;
  };
}

// 6. Validate Data (Custom Validation)
interface ValidateDataAction {
  type: 'validate_data';
  config: {
    rules: Array<{
      field: string;
      rule: string; // 'required', 'email', 'regex:pattern', 'min:N', etc.
      message: string;
    }>;
  };
  returns: {
    valid: boolean;
    errors: Array<{ field: string; message: string }>;
  };
}

// 7. Lookup Value (External Data)
interface LookupValueAction {
  type: 'lookup_value';
  config: {
    source: 'api' | 'database' | 'spreadsheet';
    query: string; // URL or SQL or cell reference
    key_field: string;
    value_field: string;
  };
  returns: {
    found: boolean;
    value: unknown;
    metadata?: Record<string, unknown>;
  };
}
```

### MCP Tool Definitions

```typescript
/**
 * MCP Tools for LLM Button Configuration
 */

// 1. Add a button element to form
export const addButtonTool = {
  name: 'addButton',
  description: `Add a button element to a form. Buttons can perform various actions:
  - submit: Submit the form
  - save_state: Save as draft
  - reset: Clear form fields
  - navigate: Move between wizard steps
  - trigger_automation: Fire custom event for automation
  - open_overlay: Open modal, drawer, or popup
  - toggle_visibility: Show/hide other elements
  - copy_to_clipboard: Copy content to clipboard`,
  inputSchema: z.object({
    action: z.literal('addButton'),
    formId: z.number(),
    buttonText: z.string(),
    actionType: ButtonActionTypeSchema,
    // Conditional properties based on actionType...
    eventId: z.string().optional(),
    targetElements: z.array(z.string()).optional(),
    variant: ButtonVariantSchema.optional(),
    size: ButtonSizeSchema.optional(),
    icon: z.string().optional(),
  }),
};

// 2. Create button with automation binding
export const createButtonAutomationTool = {
  name: 'createButtonAutomation',
  description: `Create a button and configure its automation in one call.
  Use for: PDF generation, payments, webhooks, calculations.`,
  inputSchema: z.object({
    action: z.literal('createButtonAutomation'),
    formId: z.number(),
    buttonText: z.string(),
    eventId: z.string(),
    icon: z.string().optional(),
    variant: ButtonVariantSchema.optional(),
    automationActions: z.array(z.object({
      type: z.string(),
      config: z.record(z.unknown()),
    })),
  }),
};

// 3. Configure button properties
export const configureButtonTool = {
  name: 'configureButton',
  description: `Update properties of an existing button element.`,
  inputSchema: z.object({
    action: z.literal('configureButton'),
    elementId: z.string(),
    updates: z.record(z.unknown()),
  }),
};

// 4. Add navigation buttons to wizard
export const addNavigationButtonsTool = {
  name: 'addNavigationButtons',
  description: `Add Next/Previous navigation buttons to a wizard step.`,
  inputSchema: z.object({
    action: z.literal('addNavigationButtons'),
    formId: z.number(),
    stepIndex: z.number(),
    showNext: z.boolean().default(true),
    showPrevious: z.boolean().default(true),
    nextText: z.string().default('Next'),
    previousText: z.string().default('Back'),
    validateBeforeNext: z.boolean().default(true),
  }),
};
```

### Frontend Event Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        BUTTON CLICK FLOW                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  [User Clicks Button]                                               │
│          │                                                          │
│          ▼                                                          │
│  ┌───────────────────┐                                              │
│  │ action_type check │                                              │
│  └───────────────────┘                                              │
│          │                                                          │
│   ┌──────┴──────┬──────────┬──────────┬──────────┐                 │
│   ▼             ▼          ▼          ▼          ▼                 │
│ [submit]    [reset]   [navigate]  [toggle]  [trigger]              │
│   │           │          │          │          │                   │
│   │           │          │          │          ▼                   │
│   │           │          │          │    ┌──────────────┐          │
│   │           │          │          │    │ POST to      │          │
│   │           │          │          │    │ /trigger     │          │
│   │           │          │          │    │ endpoint     │          │
│   │           │          │          │    └──────────────┘          │
│   │           │          │          │          │                   │
│   │           │          │          │          ▼                   │
│   │           │          │          │    ┌──────────────┐          │
│   │           │          │          │    │ Automation   │          │
│   │           │          │          │    │ Executor     │          │
│   │           │          │          │    └──────────────┘          │
│   │           │          │          │          │                   │
│   ▼           ▼          ▼          ▼          ▼                   │
│ [Form      [Clear    [Change    [Toggle   [Handle                  │
│  Submit]   Fields]   Step]      Visibility] Response]              │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Database Schema Addition

```sql
-- Temporary access tokens table
CREATE TABLE {$prefix}superforms_temp_access (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token_hash VARCHAR(64) NOT NULL,
  resource_type ENUM('entry', 'file', 'form') NOT NULL,
  resource_id BIGINT UNSIGNED NOT NULL,
  access_type ENUM('view', 'edit', 'download') NOT NULL,
  created_by BIGINT UNSIGNED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  max_uses INT UNSIGNED NULL,
  use_count INT UNSIGNED DEFAULT 0,
  last_used_at DATETIME NULL,
  revoked_at DATETIME NULL,
  INDEX idx_token (token_hash),
  INDEX idx_expires (expires_at),
  INDEX idx_resource (resource_type, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Work Log

- [2025-12-15] Subtask created
- [2025-12-15] Completed research using Brave Search:
  - Researched JotForm, Gravity Forms, Form.io, Typeform button patterns
  - Researched n8n, Zapier, Make automation trigger patterns
  - Found Form.io `saveState` action as excellent pattern reference
- [2025-12-15] Created 14 epic files in `epics/` directory covering:
  - Submit, Save Draft, Generate PDF, Open Modal, Open Drawer
  - Navigate Wizard, Payment/Checkout, Webhook/External
  - Show/Hide, Reset Form, Download File, Calculate/Validate
  - Temp Access Link, Copy to Clipboard
- [2025-12-15] Synthesized unified Button schema with Zod
- [2025-12-15] Documented 7 new automation node types needed
- [2025-12-15] Defined MCP tool schemas for LLM integration
- [2025-12-15] Marked subtask as completed
