# Epic: Open Modal/Dialog

## Use Case
Button click opens a modal dialog for additional input, confirmation, or displaying information. Essential for progressive disclosure and focused interactions.

## User Story
As a form user, I want to click a button that opens a modal so that I can view additional information or complete a focused sub-task without leaving the form.

## Button Properties Required
- `action_type`: `'open_overlay'`
- `overlay_type`: `'modal'` | `'dialog'`
- `overlay_content`: string - content type to display
- `button_text`: string - "View Details", "Add Item", "More Options"
- `modal_title`: string - title for the modal
- `modal_size`: `'sm'` | `'md'` | `'lg'` | `'xl'` | `'fullscreen'`
- `close_on_backdrop`: boolean - close when clicking outside
- `close_on_escape`: boolean - close on Escape key
- `show_close_button`: boolean - show X close button

## Event Payload
```json
{
  "event": "button.open_modal.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_modal_123",
    "overlay_type": "modal",
    "form_data": { "name": "John" },
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `show_overlay` (new) - Display modal/dialog/popup with content
- `set_variable` - Prepare data for modal display
- `fetch_data` (new) - Fetch additional data to show in modal

## New Node Types Needed
- `show_overlay` - Frontend action to display overlay
  - Settings: overlay_type, title, content_source, size
  - Content sources: static_html, form_fields, entry_data, external_url
  - Returns: `{ displayed: true, overlay_id }`

## Frontend Behavior
- **On click**: Immediately open modal (no loading usually)
- **Modal content options**:
  - Static HTML/text content
  - Another form section (sub-form)
  - Entry data summary
  - External iframe content
  - Dynamic content from automation result
- **Closing**: Returns to form, optionally with data from modal

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('open_overlay'),
  overlay_type: z.enum(['modal', 'dialog', 'popup', 'alert']),
  button_text: z.string(),
  modal_title: z.string().optional(),
  modal_content: z.string().optional(), // HTML or reference
  modal_content_source: z.enum(['static', 'form_section', 'entry_data', 'url']).optional(),
  modal_size: z.enum(['sm', 'md', 'lg', 'xl', 'fullscreen']).default('md'),
  close_on_backdrop: z.boolean().default(true),
  close_on_escape: z.boolean().default(true),
  show_close_button: z.boolean().default(true),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: {
    action: 'addButton',
    formId: number,
    buttonText: 'View Terms',
    actionType: 'open_overlay',
    overlayType: 'modal',
    modalTitle: 'Terms & Conditions',
    modalSize: 'lg',
    modalContentSource: 'static',
    modalContent: '<p>Terms content here...</p>'
  }
}
```

## UX Best Practices (from research)
- Modal should focus on ONE task
- User action must trigger modal (never auto-popup)
- Escape key should close modal
- Focus trap inside modal for accessibility
- Clear action buttons: "Save" / "Cancel" not "OK" / "Confirm"
- Don't stack modals on top of modals
- Consider using ellipsis (…) in button text to indicate additional action required

## Research Sources
- UX Planet: "Best Practices for Modals/Overlays/Dialog Windows"
- Carbon Design System: Modal dialog patterns
- MDN: `<dialog>` element documentation
- Medium: "Modern Enterprise UI design - Modal dialogs"
