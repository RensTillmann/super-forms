# Epic: Copy to Clipboard

## Use Case
Copy form data, calculated values, or generated content to clipboard. Useful for codes, links, formatted summaries, and quick data transfer.

## User Story
As a form user, I want to click a "Copy" button so that I can quickly copy generated content (codes, links, summaries) to my clipboard for use elsewhere.

## Button Properties Required
- `action_type`: `'copy_to_clipboard'`
- `copy_content`: string, supportsTags - content to copy
- `copy_source`: `'static'` | `'field'` | `'template'` | `'automation_result'`
- `source_field`: string - field name if source is 'field'
- `button_text`: string - "Copy", "Copy Code", "Copy Link"
- `success_text`: string - "Copied!"
- `success_duration`: number - ms to show success state
- `icon`: `'copy'` | `'clipboard'`
- `format`: `'text'` | `'html'` | `'json'`

## Event Payload
```json
{
  "event": "button.copy_to_clipboard.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_copy_123",
    "copied_content": "REF-2025-ABC123",
    "copy_source": "field",
    "source_field": "reference_code",
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `log_interaction` - Track copy events for analytics
- `set_variable` - Store what was copied

## New Node Types Needed
None - handled entirely in frontend JavaScript using Clipboard API.

## Frontend Behavior
- **On click**:
  - Resolve content from source (field value, template, static)
  - Copy to clipboard using `navigator.clipboard.writeText()`
  - Show success state (checkmark icon, "Copied!" text)
  - Return to normal state after success_duration
- **Fallback**:
  - For older browsers, use `document.execCommand('copy')`
  - Show tooltip if clipboard access denied
- **Accessibility**:
  - Announce "Copied to clipboard" to screen readers

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('copy_to_clipboard'),
  copy_source: z.enum(['static', 'field', 'template', 'automation_result']),
  copy_content: z.string().optional(), // For static source
  source_field: z.string().optional(), // For field source
  content_template: z.string().optional(), // For template source with {tags}
  button_text: z.string().default('Copy'),
  success_text: z.string().default('Copied!'),
  success_duration: z.number().default(2000),
  icon: z.string().default('copy'),
  icon_success: z.string().default('check'),
  format: z.enum(['text', 'html', 'json']).default('text'),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: {
    action: 'addButton',
    formId: number,
    buttonText: 'Copy Reference Code',
    actionType: 'copy_to_clipboard',
    copySource: 'field',
    sourceField: 'reference_code',
    icon: 'copy',
    variant: 'outline',
    size: 'sm'
  }
}
```

## Common Use Cases
1. **Copy reference code**: Generated order/ticket numbers
2. **Copy share link**: After generating temp access link
3. **Copy formatted summary**: Entry data as text
4. **Copy API response**: JSON data from calculation
5. **Copy coupon code**: Generated discount codes

## Research Sources
- TextInput element: Already has `actionButton: 'copy'` option
- Modern browsers: Clipboard API (navigator.clipboard)
- Accessibility: ARIA live regions for copy confirmation
