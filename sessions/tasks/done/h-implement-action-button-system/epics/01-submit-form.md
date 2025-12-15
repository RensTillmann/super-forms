# Epic: Submit Form

## Use Case
Standard form submission - the most common button action. User clicks submit to send all form data for processing.

## User Story
As a form user, I want to click a Submit button so that my form data is saved and processed.

## Button Properties Required
- `action_type`: `'submit'` - designates this as a form submit button
- `button_text`: string, translatable - "Submit", "Send", "Apply", etc.
- `loading_text`: string - "Submitting...", "Sending..."
- `success_message`: string, supportsTags - "Thank you, {name}!"
- `error_message`: string - "Something went wrong. Please try again."
- `disabled_until_valid`: boolean - disable until form validates
- `confirm_before_submit`: boolean - show confirmation dialog
- `confirmation_message`: string - "Are you sure you want to submit?"

## Event Payload
```json
{
  "event": "form.submitted",
  "context": {
    "form_id": 123,
    "entry_id": 456,
    "form_data": { "name": "John", "email": "john@example.com" },
    "user_id": 1,
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `send_email` - Send confirmation/notification emails
- `webhook` - POST to external service
- `create_post` - Create WordPress post from submission
- `redirect_user` - Redirect to thank you page
- `set_variable` - Set response variables

## New Node Types Needed
None - uses existing `form.submitted` event.

## Frontend Behavior
- **Loading state**: Button shows spinner + loading_text, disabled
- **Success handling**: Show success toast, optionally redirect
- **Error handling**: Show error toast, re-enable button

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('submit'),
  button_text: z.string().default('Submit'),
  loading_text: z.string().optional(),
  success_message: z.string().optional(),
  error_message: z.string().optional(),
  disabled_until_valid: z.boolean().default(true),
  confirm_before_submit: z.boolean().default(false),
  confirmation_message: z.string().optional(),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: {
    action: 'addButton',
    formId: number,
    buttonText: 'Submit',
    actionType: 'submit',
    variant: 'primary',
    disabledUntilValid: true
  }
}
```

## Research Sources
- JotForm: Multiple submit buttons possible but trigger same action
- Gravity Forms: Submit button customizable via filters
- Form.io: Button with `action: 'submit'` type
