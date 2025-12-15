# Epic: Reset Form

## Use Case
Clear all form fields and return to initial state. User wants to start over without refreshing the page.

## User Story
As a form user, I want to click a "Clear Form" button so that I can quickly start over with a fresh form without reloading the page.

## Button Properties Required
- `action_type`: `'reset'`
- `button_text`: string - "Clear Form", "Start Over", "Reset"
- `confirm_reset`: boolean - show confirmation before resetting
- `confirmation_message`: string - "Are you sure? All entered data will be lost."
- `preserve_fields`: string[] - field names to NOT reset
- `reset_to_defaults`: boolean - reset to default values vs empty
- `scroll_to_top`: boolean - scroll to form top after reset

## Event Payload
```json
{
  "event": "form.reset",
  "context": {
    "form_id": 123,
    "button_id": "btn_reset_123",
    "preserved_fields": ["hidden_source"],
    "reset_to_defaults": true,
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `log_interaction` - Track reset events for analytics
- `clear_session` - Clear any stored session data

## New Node Types Needed
None - handled in frontend with optional logging.

## Frontend Behavior
- **On click**:
  - If confirm_reset, show confirmation dialog
  - If confirmed (or no confirmation needed):
    - Reset all form fields to default/empty
    - Preserve specified fields if preserve_fields set
    - Clear validation errors
    - Reset multi-step wizard to step 1
    - Optionally scroll to top
- **State management**:
  - Clear draft/session storage
  - Reset file upload states
  - Clear calculated field values

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('reset'),
  button_text: z.string().default('Clear Form'),
  confirm_reset: z.boolean().default(true),
  confirmation_message: z.string().optional(),
  preserve_fields: z.array(z.string()).optional(),
  reset_to_defaults: z.boolean().default(true),
  scroll_to_top: z.boolean().default(true),
  clear_drafts: z.boolean().default(true),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: {
    action: 'addButton',
    formId: number,
    buttonText: 'Clear Form',
    actionType: 'reset',
    confirmReset: true,
    confirmationMessage: 'This will clear all your entries. Continue?',
    variant: 'outline',
    icon: 'refresh-ccw'
  }
}
```

## Research Sources
- HTML native: `<button type="reset">` behavior
- Form.io: Button with action 'reset'
- General UX: Reset button placement (usually secondary, left of submit)
