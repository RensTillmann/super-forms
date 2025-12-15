# Epic: Calculate/Validate

## Use Case
Button triggers calculation or validation without submitting - price calculators, eligibility checkers, quote generators. Shows result to user inline or in modal.

## User Story
As a form user, I want to click a "Calculate" button so that I can see computed values (price, score, eligibility) based on my current inputs before deciding to submit.

## Button Properties Required
- `action_type`: `'trigger_automation'`
- `event_id`: string - custom event (e.g., "calculate_quote")
- `button_text`: string - "Calculate Price", "Check Eligibility", "Get Quote"
- `loading_text`: string - "Calculating..."
- `result_display`: `'inline'` | `'modal'` | `'toast'` | `'field'`
- `result_target_field`: string - field name to populate with result
- `result_template`: string - HTML template for displaying result
- `validate_fields`: string[] - fields to validate before calculating
- `icon`: `'calculator'` | `'check-circle'`

## Event Payload
```json
{
  "event": "button.calculate_quote.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_calc_123",
    "form_data": {
      "quantity": 5,
      "product_type": "premium",
      "shipping": "express"
    },
    "validate_fields": ["quantity", "product_type"],
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `calculate` (new) - Run calculation expression
- `validate_data` (new) - Run custom validation rules
- `fetch_data` - Look up external data (prices, rates)
- `set_variable` - Store calculated values
- `conditional` - Branch based on result

## New Node Types Needed
- `calculate` - Evaluate mathematical/logical expression
  - Settings: expression, variables_map, precision
  - Expression: `"{quantity} * {unit_price} * (1 + {tax_rate})"`
  - Returns: `{ result, formatted_result }`

- `validate_data` - Custom validation beyond field-level
  - Settings: rules[], error_messages
  - Returns: `{ valid, errors[] }`

- `lookup_value` - Query external data source
  - Settings: source (api/database/spreadsheet), query
  - Returns: `{ found, value, metadata }`

## Frontend Behavior
- **On click**:
  - Validate specified fields first
  - If validation fails, show errors, don't calculate
  - If valid, show loading, call automation
  - Display result based on result_display setting
- **Result display options**:
  - `inline`: Show result near button
  - `modal`: Show result in modal dialog
  - `toast`: Show as notification toast
  - `field`: Populate a hidden/readonly field
- **State**:
  - Result persists until form data changes
  - Optionally auto-recalculate on field change

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('trigger_automation'),
  event_id: z.string(),
  button_text: z.string(),
  loading_text: z.string().optional(),
  result_display: z.enum(['inline', 'modal', 'toast', 'field']).default('inline'),
  result_target_field: z.string().optional(),
  result_template: z.string().optional(), // HTML template with {result} placeholder
  validate_fields: z.array(z.string()).optional(),
  auto_recalculate: z.boolean().default(false),
  debounce_ms: z.number().default(500), // For auto-recalculate
  icon: z.string().optional(),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'createButtonAutomation',
  parameters: {
    action: 'createButtonAutomation',
    formId: number,
    buttonText: 'Calculate Total',
    eventId: 'calculate_total',
    icon: 'calculator',
    resultDisplay: 'inline',
    automationActions: [
      {
        type: 'calculate',
        config: {
          expression: '{quantity} * {unit_price} * (1 + 0.1)',
          precision: 2,
          format: 'currency'
        }
      }
    ]
  }
}
```

## Common Use Cases
1. **Price calculator**: Calculate total based on selections
2. **Eligibility checker**: Check if user qualifies for something
3. **Quote generator**: Generate custom quote
4. **BMI calculator**: Health/fitness calculations
5. **Loan calculator**: Interest/payment calculations
6. **Shipping estimator**: Estimate delivery cost

## Research Sources
- JotForm: Calculation widget, conditional calculations
- Gravity Forms: Calculated fields (but field-based, not button-triggered)
- Form.io: Calculated values in forms
