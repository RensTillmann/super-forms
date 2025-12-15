# Epic: Webhook/External Trigger

## Use Case
Button click triggers an external automation via webhook - send data to Zapier, Make, n8n, or custom endpoints. Enables integration with any external system.

## User Story
As a form admin, I want to configure a button that triggers an external webhook so that form interactions can kick off workflows in third-party systems.

## Button Properties Required
- `action_type`: `'trigger_automation'`
- `event_id`: string - custom event identifier (e.g., "sync_to_crm")
- `button_text`: string - "Sync to CRM", "Send to Slack", "Process Data"
- `loading_text`: string - "Sending..."
- `success_message`: string - "Data sent successfully!"
- `await_response`: boolean - wait for webhook response
- `timeout_ms`: number - max wait time for response
- `show_response`: boolean - display response data to user

## Event Payload
```json
{
  "event": "button.sync_to_crm.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_webhook_123",
    "form_data": { "name": "John", "email": "john@example.com", "company": "Acme" },
    "user_id": 1,
    "session_key": "abc123",
    "source_url": "https://example.com/form/123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `webhook` - Send HTTP request to external URL
- `http_request` - More configurable HTTP client
- `set_variable` - Store webhook response data
- `conditional` - Branch based on response

## New Node Types Needed
- `await_webhook_response` - Wait for and process webhook response
  - Settings: timeout_ms, expected_status, response_mapping
  - Returns: `{ status, response_data, headers }`

## Frontend Behavior
- **On click**: Show loading state
- **If await_response = true**:
  - Wait for webhook to complete (with timeout)
  - Display success/error based on response
  - Optionally show response data to user
- **If await_response = false**:
  - Fire-and-forget, show immediate success
  - Background processing continues

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('trigger_automation'),
  event_id: z.string().regex(/^[a-z][a-z0-9_]*$/),
  button_text: z.string(),
  loading_text: z.string().optional(),
  success_message: z.string().optional(),
  error_message: z.string().optional(),
  await_response: z.boolean().default(true),
  timeout_ms: z.number().default(30000),
  show_response: z.boolean().default(false),
  response_display_template: z.string().optional(), // HTML template for response
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'createButtonAutomation',
  parameters: {
    action: 'createButtonAutomation',
    formId: number,
    buttonText: 'Sync to CRM',
    eventId: 'sync_to_crm',
    icon: 'refresh-cw',
    automationActions: [
      {
        type: 'webhook',
        config: {
          url: 'https://hooks.zapier.com/...',
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body_template: '{"name": "{name}", "email": "{email}"}'
        }
      }
    ]
  }
}
```

## Integration Patterns
| Platform | Trigger Type | URL Pattern |
|----------|--------------|-------------|
| Zapier | Webhook | `hooks.zapier.com/hooks/catch/...` |
| Make | Webhook | `hook.make.com/...` |
| n8n | Webhook node | `your-n8n.com/webhook/...` |
| Pipedream | HTTP trigger | `*.m.pipedream.net` |
| Custom | Any endpoint | User-defined URL |

## Research Sources
- n8n: Webhook node documentation, Form Trigger node
- Zapier: "Trigger Zaps from webhooks" documentation
- Make: Webhook module patterns
- WP Webhooks: WordPress webhook integration patterns
