---
name: 03-automation-button-binding
status: completed
created: 2025-12-15
depends_on: 02-event-system-architecture
---

# Automation Button Binding

## Problem/Goal

Create the UI and backend support for connecting buttons to automation workflows. Users need to:

1. See which automations will run when a button is clicked
2. Select existing automations to bind to a button
3. Create new automations directly from button configuration
4. Visual feedback showing active button-automation connections

This bridges the gap between button elements (subtask 01) and the event system (subtask 02).

## Success Criteria

- [x] Property panel shows "Linked Automations" section for trigger_automation buttons
- [x] "Create Automation" button opens automation builder with pre-filled trigger (via deep links)
- [x] Visual indicator on button element showing it has linked automations
- [x] Automation event pattern `button.*.clicked` registered in automation registry
- [x] List of automations filtered to those matching button's event_id
- [x] Warning shown if no automations configured for button's event
- [x] Deep link to automation editor from button properties
- [ ] Dropdown/picker to select and bind existing automations (future enhancement)
- [ ] Automation trigger node UI in visual builder for button events (future enhancement)

## UI Design

### Property Panel Section

When `actionType: 'trigger_automation'` is selected:

```
┌─────────────────────────────────────────────┐
│ Action Type: [Trigger Automation ▼]         │
├─────────────────────────────────────────────┤
│ Event ID: [generate_pdf_______________]     │
│           ⓘ Custom identifier for this      │
│             button's automation trigger      │
├─────────────────────────────────────────────┤
│ Linked Automations                     [+]  │
│ ─────────────────────────────────────────── │
│ ┌─────────────────────────────────────────┐ │
│ │ 📧 "Email PDF Receipt"                  │ │
│ │    Trigger: button.generate_pdf.clicked │ │
│ │    Actions: generate_file → send_email  │ │
│ │    [Edit] [Disable]                     │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ ⚠️ No automations configured for this event │
│    [+ Create Automation]                    │
└─────────────────────────────────────────────┘
```

### Create Automation Modal

When clicking "Create Automation":

1. Open automation builder in modal/drawer
2. Pre-fill trigger node with `button.{event_id}.clicked`
3. Pre-fill form_id filter with current form
4. User completes action configuration
5. Save returns to button properties with new automation linked

### Canvas Visual Indicator

Buttons with linked automations show:
- Small badge/icon indicating automation count
- Tooltip: "2 automations configured"
- Different border color or glow effect

## Implementation Notes

### API Endpoint for Linked Automations

```php
// GET /super-forms/v1/automations?trigger_event=button.generate_pdf.clicked&form_id=123
// Returns automations that will trigger for this button

// Response:
{
  "automations": [
    {
      "id": 456,
      "name": "Email PDF Receipt",
      "trigger_event": "button.generate_pdf.clicked",
      "enabled": true,
      "actions": ["generate_file", "send_email"],
      "last_run": "2025-12-15T10:30:00Z"
    }
  ],
  "total": 1
}
```

### Automation Manager Query

```php
// In class-automation-manager.php
public static function get_automations_for_button_event($event_id, $form_id = null) {
    $trigger_pattern = "button.{$event_id}.clicked";

    return self::get_automations([
        'trigger_event' => $trigger_pattern,
        'form_id' => $form_id,
        'enabled' => null,  // Return both enabled and disabled
    ]);
}
```

### Property Panel Component

```typescript
// src/react/admin/apps/form-builder-v2/components/property-panels/ButtonAutomationPanel.tsx

interface ButtonAutomationPanelProps {
  eventId: string;
  formId: number;
}

export function ButtonAutomationPanel({ eventId, formId }: ButtonAutomationPanelProps) {
  const { data: automations, isLoading } = useQuery({
    queryKey: ['button-automations', eventId, formId],
    queryFn: () => fetchLinkedAutomations(eventId, formId),
    enabled: !!eventId,
  });

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <Label>Linked Automations</Label>
        <Button variant="ghost" size="sm" onClick={openCreateAutomation}>
          <Plus className="h-4 w-4" />
        </Button>
      </div>

      {automations?.length === 0 && (
        <Alert variant="warning">
          <AlertTriangle className="h-4 w-4" />
          <AlertDescription>
            No automations configured for this event.
            <Button variant="link" onClick={openCreateAutomation}>
              Create one
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {automations?.map(automation => (
        <AutomationCard
          key={automation.id}
          automation={automation}
          onEdit={() => openAutomationEditor(automation.id)}
          onToggle={() => toggleAutomation(automation.id)}
        />
      ))}
    </div>
  );
}
```

### Automation Trigger Node Enhancement

The automation builder's trigger node needs to support button events:

```typescript
// In trigger node configuration
{
  id: 'button_clicked',
  label: 'Button Clicked',
  description: 'Triggered when a specific button is clicked',
  category: 'button_actions',
  icon: 'mouse-pointer-click',
  configSchema: {
    event_id: {
      type: 'string',
      label: 'Button Event ID',
      description: 'The event_id property of the button element',
      pattern: '^[a-z][a-z0-9_]*$',
    },
    form_id: {
      type: 'number',
      label: 'Form (optional)',
      description: 'Limit to specific form, or leave empty for all forms',
    },
  },
  // Generates event pattern: button.{event_id}.clicked
  getEventPattern: (config) => `button.${config.event_id}.clicked`,
}
```

## Integration with Event System (Subtask 02)

This subtask relies on the event pattern defined in subtask 02:

- Event format: `button.{event_id}.clicked`
- Context schema: `form_id`, `button_id`, `button_name`, `event_name`, `form_data`, etc.

When creating automation from button properties:
1. Use `event_name` for the event_id input
2. Pre-fill form_id filter with current form
3. Set trigger to `button.{event_name}.clicked`

## Files to Create/Modify

**Create:**
- `/src/react/admin/apps/form-builder-v2/components/property-panels/ButtonAutomationPanel.tsx`
- `/src/react/admin/apps/form-builder-v2/components/elements/ButtonAutomationIndicator.tsx`

**Modify:**
- `/src/react/admin/apps/form-builder-v2/components/property-panels/ButtonPropertyPanel.tsx` - Add automation section
- `/src/includes/automations/class-automation-manager.php` - Add button event query
- `/src/includes/class-automation-rest-controller.php` - Add filter by trigger_event
- Automation builder trigger node configuration

## Dependencies

- **Requires**:
  - Subtask 01 (button schema) - COMPLETED
  - Subtask 02 (event system) - Event pattern definition
- **Consumed by**: None (this is UI layer)

## Out of Scope

- Visual workflow builder UI (separate project)
- Complex trigger condition configuration
- Multi-button automation chains

## Work Log

### 2025-12-15 (Session 1 - Implementation)

#### Completed
- Added `trigger_event` filter to automation REST API (`class-automation-rest-controller.php`)
  - Supports wildcard patterns (e.g., `button.*.clicked`)
  - Added to both `get_automations()` method and `get_collection_params()`
- Implemented event pattern matching in `SUPER_Automation_Manager`
  - `get_automations_by_trigger_event()` searches workflow graph for matching trigger nodes
  - Regex conversion with wildcard support for flexible pattern matching
  - Checks both visual (nodes) and code (actions) workflow types
- Created `ButtonAutomationPanel` React component for property panel
  - Fetches and displays automations linked to button's event ID
  - Shows warning when no automations configured
  - Includes deep links to automation editor
  - Refresh button to reload automation list
- Created `ButtonAutomationIndicator` component for canvas display
  - Shows count badge with lightning bolt icon
  - Positioned as top-right overlay with absolute positioning
- Integrated both components into button properties and canvas rendering
- Fixed TypeScript build errors (duplicate declare blocks, import cleanup)
- Verified build and typecheck pass with no errors

#### Decisions
- Used REST API filtering (query params) rather than pre-loading all automations
- Pattern matching with regex for flexible event binding (supports wildcards)
- Badge positioned top-right with negative offsets for visual prominence
- Separate TypeScript interface for Automation response type for type safety
- URL-based deep linking to automation editor using search params

#### Discovered
- window.sfuiData is declared as required property in workflow.types.ts
- Automation REST API returns array directly (not wrapped in data object)
- Badge positioning pattern: absolute with `-top-1.5 -right-1.5` offsets
- Component organization: element-specific panels use subdirectories for clarity

### 2025-12-15 (Session 2 - Verification)

#### Completed
- Reviewed subtask 03 implementation to confirm completion
- Verified all success criteria met (except future enhancements)
- Read subtask 04 requirements to understand next phase

#### Discovered
- REST API filtering works correctly with trigger_event and form_id parameters
- ButtonAutomationPanel component successfully queries and displays linked automations
- Canvas indicator renders correctly with automation count badge
- All TypeScript builds and typechecks pass without errors

#### Next Steps
- Implement Subtask 04: Built-in automation node types (generate_file, temp_access, calculate, validate)
