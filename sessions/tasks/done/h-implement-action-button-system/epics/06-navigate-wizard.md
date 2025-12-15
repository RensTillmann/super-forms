# Epic: Navigate Wizard (Next/Previous/Jump)

## Use Case
Multi-step form navigation - moving between steps in a wizard-style form. Includes Next, Previous, and Jump-to-Step functionality with conditional skip logic.

## User Story
As a form user, I want navigation buttons that let me move through form steps, go back to previous steps, and skip optional sections so that I can complete multi-page forms efficiently.

## Button Properties Required
- `action_type`: `'navigate'`
- `navigate_direction`: `'next'` | `'previous'` | `'first'` | `'last'` | `'specific'`
- `target_step`: number | string - specific step to jump to
- `button_text`: string - "Next", "Previous", "Back", "Continue"
- `validate_before_navigate`: boolean - validate current step first
- `skip_validation`: boolean - allow skipping without validation
- `skip_conditions`: conditional_rules - conditions to skip this step
- `show_step_indicator`: boolean - show "Step 2 of 5" text

## Event Payload
```json
{
  "event": "form.step_changed",
  "context": {
    "form_id": 123,
    "button_id": "btn_next_123",
    "from_step": 1,
    "to_step": 2,
    "direction": "next",
    "form_data": { "step1_name": "John" },
    "step_valid": true,
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `validate_step` - Run validation on current step
- `save_progress` - Auto-save on step change
- `conditional_skip` - Determine if step should be skipped
- `set_variable` - Track wizard state

## New Node Types Needed
- `wizard_control` - Control multi-step form flow
  - Settings: action (next/prev/jump), target_step, validate_first
  - Returns: `{ new_step, skipped_steps, validation_errors }`

## Frontend Behavior
- **Next button**:
  - Validate current step (unless skip_validation)
  - If valid, transition to next step
  - If invalid, show validation errors, stay on step
- **Previous button**:
  - No validation needed
  - Transition to previous step, preserve entered data
- **Jump to step**:
  - Can optionally skip validation
  - Jump directly to target step
- **Conditional skip**:
  - Evaluate skip_conditions
  - If true, automatically advance past this step

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('navigate'),
  navigate_direction: z.enum(['next', 'previous', 'first', 'last', 'specific']),
  target_step: z.union([z.number(), z.string()]).optional(),
  button_text: z.string(),
  validate_before_navigate: z.boolean().default(true),
  skip_validation: z.boolean().default(false),
  skip_conditions: z.array(PropertyConditionSchema).optional(),
  show_step_indicator: z.boolean().default(true),
  save_on_navigate: z.boolean().default(true), // Auto-save draft on step change
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addNavigationButtons',
  parameters: {
    action: 'addNavigationButtons',
    formId: number,
    stepIndex: 1,
    showNext: true,
    showPrevious: true,
    nextText: 'Continue',
    previousText: 'Back',
    validateBeforeNext: true,
    saveOnNavigate: true
  }
}
```

## Multi-Step UX Patterns
1. **Linear progression**: Steps must be completed in order
2. **Free navigation**: Any step accessible via sidebar/tabs
3. **Conditional branching**: Different paths based on answers
4. **Optional steps**: Can be skipped with "Skip" button
5. **Review step**: Final step showing all entered data

## Research Sources
- JotForm: Card layout with Back/Next, conditional page skip
- Gravity Forms: Multi-Page Navigation perk with custom page links
- Typeform: One question per page, auto-advance
- Filament PHP: `skippable()` method for free navigation
- W3Schools: Multi-step form tutorial
