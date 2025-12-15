# Epic: Show/Hide Element

## Use Case
Button click toggles visibility of other form elements - show additional fields, reveal help text, expand optional sections. No server round-trip needed.

## User Story
As a form user, I want to click a button that shows or hides additional form fields so that I can access optional content without cluttering the initial form view.

## Button Properties Required
- `action_type`: `'toggle_visibility'`
- `target_elements`: string[] - IDs or names of elements to toggle
- `visibility_action`: `'show'` | `'hide'` | `'toggle'`
- `button_text`: string - "Show More", "Add Details", "Expand"
- `button_text_toggled`: string - "Show Less", "Hide Details", "Collapse"
- `icon`: string - "chevron-down"
- `icon_toggled`: string - "chevron-up"
- `animate`: boolean - smooth animation
- `scroll_to_element`: boolean - scroll target into view

## Event Payload
```json
{
  "event": "button.toggle_visibility.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_toggle_123",
    "action": "show",
    "target_elements": ["additional_info_section", "extra_fields"],
    "new_visibility_state": true,
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
None typically needed - this is a frontend-only action. However:
- `log_interaction` - Track which sections users expand (analytics)
- `set_variable` - Store visibility state in session

## New Node Types Needed
None - handled entirely in frontend JavaScript.

## Frontend Behavior
- **On click**:
  - Toggle visibility of target elements
  - Swap button text (if button_text_toggled set)
  - Swap icon (if icon_toggled set)
  - Optionally animate with slide/fade
  - Optionally scroll element into view
- **State persistence**:
  - Remember toggle state in session
  - Restore state if user returns to form
- **Accessibility**:
  - Update aria-expanded attribute
  - Announce state change to screen readers

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('toggle_visibility'),
  target_elements: z.array(z.string()).min(1),
  visibility_action: z.enum(['show', 'hide', 'toggle']).default('toggle'),
  button_text: z.string(),
  button_text_toggled: z.string().optional(),
  icon: z.string().optional(),
  icon_toggled: z.string().optional(),
  animate: z.boolean().default(true),
  animation_duration: z.number().default(200), // ms
  scroll_to_element: z.boolean().default(false),
  persist_state: z.boolean().default(true),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: {
    action: 'addButton',
    formId: number,
    buttonText: 'Show Additional Options',
    buttonTextToggled: 'Hide Additional Options',
    actionType: 'toggle_visibility',
    targetElements: ['optional_section_1', 'optional_section_2'],
    visibilityAction: 'toggle',
    icon: 'chevron-down',
    iconToggled: 'chevron-up',
    variant: 'ghost'
  }
}
```

## Common Use Cases
1. **Expand optional fields**: "Add shipping address" reveals address fields
2. **Show help text**: "?" button reveals detailed instructions
3. **Progressive disclosure**: Show advanced options only when needed
4. **FAQ accordion**: Expand/collapse answer sections
5. **Conditional sections**: Show based on button instead of field value

## Research Sources
- JotForm: Show/Hide field conditional logic (but field-based, not button)
- Gravity Forms: Conditional visibility via field values
- General UX: Progressive disclosure patterns
