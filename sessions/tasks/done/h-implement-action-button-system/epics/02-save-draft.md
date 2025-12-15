# Epic: Save Draft

## Use Case
Allow users to save their progress on a form without final submission. Critical for long forms or forms that require information gathering over time.

## User Story
As a form user, I want to click a "Save Draft" button so that I can continue filling out the form later without losing my progress.

## Button Properties Required
- `action_type`: `'save_state'` - designates as state-saving button
- `state`: `'draft'` - the state to save as
- `button_text`: string - "Save Draft", "Save Progress", "Save & Continue Later"
- `loading_text`: string - "Saving..."
- `success_message`: string - "Draft saved! You can continue later."
- `show_resume_link`: boolean - show link to resume later
- `email_resume_link`: boolean - offer to email the resume link
- `auto_save_interval`: number | null - if set, auto-save every N seconds

## Event Payload
```json
{
  "event": "form.draft_saved",
  "context": {
    "form_id": 123,
    "draft_id": "draft_abc123",
    "form_data": { "name": "John", "email": "" },
    "resume_token": "xyz789",
    "resume_url": "https://example.com/form/123?resume=xyz789",
    "user_id": 1,
    "session_key": "abc123",
    "saved_at": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `send_email` - Email resume link to user
- `webhook` - Notify external system of draft
- `set_variable` - Store draft metadata

## New Node Types Needed
- `generate_temp_access` - Create temporary access token/URL for resuming

## Frontend Behavior
- **Loading state**: Button shows spinner, "Saving..."
- **Success handling**:
  - Show success toast with resume link
  - Optionally show modal with options: "Copy Link" | "Email Link" | "Continue"
  - Keep form editable (don't clear)
- **Error handling**: Show error toast, data preserved

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('save_state'),
  state: z.enum(['draft', 'pending_review', 'incomplete']).default('draft'),
  button_text: z.string().default('Save Draft'),
  loading_text: z.string().optional(),
  success_message: z.string().optional(),
  show_resume_link: z.boolean().default(true),
  email_resume_link: z.boolean().default(false),
  auto_save_interval: z.number().nullable().optional(),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: {
    action: 'addButton',
    formId: number,
    buttonText: 'Save Draft',
    actionType: 'save_state',
    state: 'draft',
    variant: 'secondary',
    showResumeLink: true
  }
}
```

## Research Sources
- Form.io: `action: 'saveState', state: 'draft'` pattern with auto-save every 5 seconds
- JotForm: "Save and Continue Later" with draft link emailed
- Formidable Forms: Save button next to submit, logged-in users only
- Gravity Forms: "Advanced Save & Continue" with auto-saving and draft management
