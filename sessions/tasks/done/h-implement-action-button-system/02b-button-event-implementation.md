---
name: 02b-button-event-implementation
status: pending
created: 2025-12-15
depends_on: 02a-frontend-event-trigger-system
---

# Button Event Implementation

## Problem/Goal

Register `button_click` as the first frontend event type using the unified trigger system from 02a, and implement the Button component's click handler integration.

This subtask focuses on:
1. Registering `button_click` event type in the frontend event registry
2. Implementing button-specific validation and context building
3. Integrating with the Button React component for loading/success/error states
4. Testing the full flow from button click → automation execution → response handling

## Success Criteria

- [ ] `button_click` event type registered with validation callback
- [ ] Button context builder creates proper `button.{event_id}.clicked` events
- [ ] Button React component uses `triggerButtonClick()` wrapper from frontendEvents.ts
- [ ] Loading state shown on button during execution (spinner + loadingText)
- [ ] Success response triggers appropriate feedback (toast, download, redirect)
- [ ] Error response shows error message to user
- [ ] Response handlers implemented for: `file_url`, `redirect_url`, `access_url`, `message`

## Shared Interfaces (Used by Subtasks 03 & 04)

### Event Context Schema

All button events pass this context to automations:

```php
$context = [
    'event_id'     => 'button.{event_id}.clicked',  // Full event identifier
    'form_id'      => (int),                         // Form being submitted
    'button_id'    => (string),                      // Element ID of button
    'button_name'  => (string),                      // Name property of button
    'event_name'   => (string),                      // Just the event_id (e.g., 'generate_pdf')
    'form_data'    => (array),                       // Current form field values
    'entry_id'     => (int|null),                    // If editing existing entry
    'session_key'  => (string),                      // Progressive save session
    'user_id'      => (int),                         // Current user (0 if guest)
    'user_email'   => (string|null),                 // User email if available
    'timestamp'    => (string),                      // ISO 8601 timestamp
    'source_url'   => (string),                      // Page URL where form is displayed
    'user_agent'   => (string),                      // Browser user agent
];
```

### AJAX Response Format

All button action responses follow this structure:

```typescript
interface ButtonActionResponse {
  success: boolean;
  data?: {
    // Action-specific result data
    message?: string;           // Success message to display
    file_url?: string;          // For file downloads
    redirect_url?: string;      // For redirects
    access_url?: string;        // For temp access links
    result?: unknown;           // For calculations, lookups
    overlay_content?: string;   // For show_overlay action
  };
  error?: {
    code: string;               // Error code for programmatic handling
    message: string;            // Human-readable error message
  };
  execution_time_ms?: number;   // For performance monitoring
  automation_log_id?: number;   // Reference to execution log
}
```

### Frontend Action Handlers

Based on response data, frontend should:

| Response Field | Frontend Action |
|----------------|-----------------|
| `file_url` | Trigger download or open in new tab |
| `redirect_url` | Navigate to URL |
| `access_url` | Show copy-to-clipboard modal |
| `overlay_content` | Display overlay |
| `result` | Update form field or display |
| `message` | Show toast notification |

## Implementation Notes

### PHP AJAX Endpoint

```php
// In class-ajax.php $ajax_events array
'trigger_button_action' => true,  // nopriv = true (public users can click buttons)

// Handler method
public static function trigger_button_action() {
    // 1. Verify nonce
    check_ajax_referer('super_trigger_button_action', 'nonce');

    // 2. Get parameters
    $form_id = absint($_POST['form_id']);
    $button_id = sanitize_text_field($_POST['button_id']);
    $event_id = sanitize_key($_POST['event_id']);
    $form_data = json_decode(stripslashes($_POST['form_data']), true);
    $session_key = sanitize_text_field($_POST['session_key'] ?? '');
    $entry_id = absint($_POST['entry_id'] ?? 0);

    // 3. Validate button exists in form elements
    // 4. Build context array (see schema above)
    // 5. Fire event: SUPER_Automation_Executor::fire_event("button.{$event_id}.clicked", $context)
    // 6. Return JSON response
}
```

### Event Registration

```php
// In class-automation-registry.php, add to load_builtin_events()
$this->register_event('button.*.clicked', [
    'label' => 'Button Clicked',
    'description' => 'Custom button triggered an automation',
    'category' => 'button_actions',
    'available_context' => [
        'form_id', 'button_id', 'button_name', 'event_name',
        'form_data', 'entry_id', 'session_key', 'user_id',
        'user_email', 'timestamp', 'source_url'
    ],
    'required_context' => ['form_id', 'button_id', 'event_name'],
    'compatible_actions' => ['*'],  // All actions compatible
    'supports_sync' => true,        // Can return response to frontend
]);
```

### Frontend Module

```typescript
// src/react/admin/lib/buttonActions.ts (or in form frontend bundle)

interface TriggerButtonActionParams {
  formId: number;
  buttonId: string;
  eventId: string;
  formData: Record<string, unknown>;
  sessionKey?: string;
  entryId?: number;
}

export async function triggerButtonAction(
  params: TriggerButtonActionParams
): Promise<ButtonActionResponse> {
  const formData = new FormData();
  formData.append('action', 'super_trigger_button_action');
  formData.append('nonce', window.superFormsNonce);
  formData.append('form_id', String(params.formId));
  formData.append('button_id', params.buttonId);
  formData.append('event_id', params.eventId);
  formData.append('form_data', JSON.stringify(params.formData));
  if (params.sessionKey) formData.append('session_key', params.sessionKey);
  if (params.entryId) formData.append('entry_id', String(params.entryId));

  const response = await fetch(window.ajaxurl, {
    method: 'POST',
    body: formData,
  });

  return response.json();
}
```

### Button Click Handler Integration

```typescript
// In Button component or form submission handler

async function handleButtonClick(button: ButtonElement) {
  if (button.actionType !== 'trigger_automation') return;

  // Show loading state
  setButtonState(button.id, { loading: true, text: button.loadingText });

  try {
    const result = await triggerButtonAction({
      formId: currentFormId,
      buttonId: button.id,
      eventId: button.eventId,
      formData: collectFormData(),
      sessionKey: currentSessionKey,
    });

    if (result.success) {
      // Handle success based on response data
      handleSuccessResponse(button, result.data);
    } else {
      // Show error
      showToast(result.error?.message || button.errorMessage || 'Action failed');
    }
  } catch (error) {
    showToast('Network error. Please try again.');
  } finally {
    // Reset button state (after successDuration if applicable)
    resetButtonState(button.id);
  }
}
```

## Security Considerations

1. **Nonce Validation**: Every request must include valid nonce
2. **Form Ownership**: Verify button belongs to form being submitted
3. **Rate Limiting**: Consider rate limiting button actions per user/session
4. **Data Sanitization**: All form data must be sanitized before use
5. **CSRF Protection**: Use existing `SUPER_Common::verifyCSRF()` pattern

## Files to Create/Modify

**Create:**
- `/src/react/admin/lib/buttonActions.ts` - Frontend trigger module
- `/src/react/frontend/buttonActions.js` - Public-facing form button handler (if separate)

**Modify:**
- `/src/includes/class-ajax.php` - Add `trigger_button_action` endpoint
- `/src/includes/automations/class-automation-registry.php` - Register button.*.clicked event

## Dependencies

- **Requires**: Subtask 01 (button schema, MCP tools) - COMPLETED
- **Consumed by**:
  - Subtask 03 (automation binding - needs event pattern)
  - Subtask 04 (built-in nodes - consume context, return response format)

## Work Log

[Will be populated during implementation]
