# Epic: Open Drawer/Tray

## Use Case
Button click opens a slide-out drawer or bottom tray for secondary content, filters, or additional options. Less disruptive than modals while still providing focused interaction space.

## User Story
As a form user, I want to click a button that opens a side drawer or bottom tray so that I can access additional options without losing context of my form progress.

## Button Properties Required
- `action_type`: `'open_overlay'`
- `overlay_type`: `'drawer'` | `'tray'` | `'sheet'`
- `drawer_position`: `'left'` | `'right'` | `'bottom'` | `'top'`
- `button_text`: string - "More Options", "Filters", "Help"
- `drawer_title`: string - title for the drawer
- `drawer_size`: `'sm'` | `'md'` | `'lg'` | `'full'`
- `push_content`: boolean - push main content or overlay
- `show_backdrop`: boolean - dim background
- `swipe_to_close`: boolean - mobile swipe gesture support

## Event Payload
```json
{
  "event": "button.open_drawer.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_drawer_123",
    "overlay_type": "drawer",
    "drawer_position": "right",
    "form_data": { "name": "John" },
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `show_overlay` - Display drawer/tray with content
- `set_variable` - Prepare data for drawer display
- `fetch_data` - Load additional data for drawer content

## New Node Types Needed
Uses same `show_overlay` node as modal epic, with different overlay_type.

## Frontend Behavior
- **On click**: Slide drawer in from specified direction
- **Mobile considerations**:
  - Bottom tray often preferred on mobile (thumb-friendly)
  - Swipe-to-close gesture
  - Visual Viewport API for keyboard-aware height
- **Drawer content options**:
  - Help/documentation panels
  - Filter controls
  - Shopping cart summary
  - Form field explanations
  - Secondary forms

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('open_overlay'),
  overlay_type: z.enum(['drawer', 'tray', 'sheet', 'sidebar']),
  drawer_position: z.enum(['left', 'right', 'bottom', 'top']).default('right'),
  button_text: z.string(),
  drawer_title: z.string().optional(),
  drawer_content: z.string().optional(),
  drawer_content_source: z.enum(['static', 'form_section', 'help_panel', 'url']).optional(),
  drawer_size: z.enum(['sm', 'md', 'lg', 'full']).default('md'),
  push_content: z.boolean().default(false),
  show_backdrop: z.boolean().default(true),
  swipe_to_close: z.boolean().default(true),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: {
    action: 'addButton',
    formId: number,
    buttonText: 'Need Help?',
    actionType: 'open_overlay',
    overlayType: 'drawer',
    drawerPosition: 'right',
    drawerTitle: 'Help & FAQ',
    drawerSize: 'md',
    icon: 'help-circle'
  }
}
```

## Drawer vs Modal Decision Guide
| Use Drawer When | Use Modal When |
|----------------|----------------|
| Supplementary content | Critical decision required |
| User may need to reference main form | Focused single task |
| Content is contextual help | Confirmation needed |
| Filters/sorting options | Error/warning message |
| Cart/summary preview | Form-within-form |

## Research Sources
- Super Forms existing: MobileDrawer component, PropertiesBottomTray
- Material Design: Navigation drawer patterns
- iOS HIG: Bottom sheet patterns
- Radix UI: Sheet component documentation
