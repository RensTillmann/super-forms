---
name: 05-documentation-examples
status: completed
created: 2025-12-15
depends_on: 04-built-in-node-types
---

# Documentation and Examples

## Problem/Goal

Create comprehensive documentation and usage examples for the Action Button System. This helps users understand:
1. How to add buttons with different action types
2. How to connect buttons to automations
3. Common patterns for PDF generation, temp access links, calculations, and validation

## Success Criteria

- [x] Usage guide for button element properties
- [x] Example: "Generate PDF" button workflow
- [x] Example: "Create Shareable Link" button workflow
- [x] Example: "Calculate Total" button workflow
- [x] Example: Multi-button form (Submit + Save Draft + Generate PDF)
- [x] MCP tool usage examples for LLM integration
- [x] Troubleshooting section for common issues

## Documentation Sections

### 1. Button Element Properties

| Property | Type | Description |
|----------|------|-------------|
| `action_type` | select | `submit`, `button`, `reset` |
| `event_id` | string | Custom event ID for automation binding (e.g., `generate_pdf`) |
| `button_text` | string | Button label (supports {tags}) |
| `variant` | select | `primary`, `secondary`, `outline`, `ghost`, `destructive` |
| `size` | select | `sm`, `md`, `lg` |
| `loading_text` | string | Text shown during automation execution |
| `success_message` | string | Toast message on success |
| `error_message` | string | Toast message on error |
| `icon` | icon | Lucide icon name |
| `iconPosition` | select | `left`, `right` |

### 2. Event Flow

```
User clicks button
    ↓
Button fires event: `button.{event_id}.clicked`
    ↓
PHP AJAX handler receives request
    ↓
Automation Executor finds matching automations
    ↓
Actions execute (generate_file, send_email, etc.)
    ↓
Result returned to frontend
    ↓
Button shows success/error state
```

### 3. Example Workflows

#### Generate PDF Button

**Button Config:**
```json
{
  "type": "button",
  "properties": {
    "button_text": "Download PDF",
    "event_id": "download_pdf",
    "variant": "primary",
    "icon": "FileDown",
    "loading_text": "Generating...",
    "success_message": "PDF ready for download"
  }
}
```

**Automation:**
- Trigger: `button.download_pdf.clicked`
- Action: `generate_file`
  - output_format: `pdf`
  - template_source: `entry_summary`
  - filename_pattern: `invoice_{entry_id}`

**Frontend Result:**
- `data.client_render = true` triggers client-side PDF generation
- `data.html` contains rendered HTML
- `data.filename` used for download

---

#### Shareable Link Button

**Button Config:**
```json
{
  "type": "button",
  "properties": {
    "button_text": "Create Shareable Link",
    "event_id": "share_entry",
    "variant": "secondary",
    "icon": "Link",
    "loading_text": "Creating link..."
  }
}
```

**Automation:**
- Trigger: `button.share_entry.clicked`
- Action: `generate_temp_access`
  - resource_type: `entry`
  - access_type: `view`
  - expiry_duration: `7d`
  - max_uses: `10`

**Frontend Result:**
- `data.access_url` shown in modal for copying
- `data.expires_in` displayed as "7 days"

---

#### Calculate Total Button

**Button Config:**
```json
{
  "type": "button",
  "properties": {
    "button_text": "Calculate Total",
    "event_id": "calc_total",
    "variant": "outline",
    "icon": "Calculator"
  }
}
```

**Automation:**
- Trigger: `button.calc_total.clicked`
- Action: `calculate`
  - expression: `{quantity} * {unit_price} * (1 + {tax_rate} / 100)`
  - format: `currency`
  - currency_symbol: `$`
  - precision: `2`

**Frontend Result:**
- `data.result` = numeric value
- `data.formatted_result` = "$1,234.56"

---

#### Validate Before Submit Button

**Button Config:**
```json
{
  "type": "button",
  "properties": {
    "button_text": "Check Form",
    "event_id": "validate_form",
    "variant": "outline",
    "icon": "CheckCircle",
    "loading_text": "Validating..."
  }
}
```

**Automation:**
- Trigger: `button.validate_form.clicked`
- Action: `validate_data`
  - rules:
    - `{ field: "email", rule: "email", message: "Please enter a valid email" }`
    - `{ field: "phone", rule: "phone" }`
    - `{ field: "age", rule: "min", value: "18", message: "Must be 18 or older" }`
    - `{ field: "username", rule: "unique", message: "Username already taken" }`
  - stop_on_first_error: `false`
  - fail_action: `continue`

**Frontend Result:**
- `data.valid` = `true` or `false`
- `data.errors` = array of `{ field, rule, message }`
- `data.validated_fields` = list of checked fields

**Use Case:** Pre-validate form before submission, show all errors at once so user can fix them.

---

### 4. Multi-Button Form Example

Form with three buttons:

| Button | Event ID | Action |
|--------|----------|--------|
| Submit | (native submit) | Form submission flow |
| Save Draft | `save_draft` | Save to session, show success |
| Preview PDF | `preview_pdf` | Generate PDF, open in new tab |

Each button operates independently - users can preview PDF multiple times before submitting.

### 5. MCP Tool Examples

**Add a Generate PDF button:**
```typescript
{
  action: 'addButton',
  buttonText: 'Download Report',
  eventId: 'download_report',
  variant: 'primary',
  icon: 'FileDown'
}
```

**Create button with full automation:**
```typescript
{
  action: 'createButtonAutomation',
  buttonText: 'Get Shareable Link',
  eventId: 'share_link',
  variant: 'secondary',
  actions: [
    {
      type: 'generate_temp_access',
      config: {
        resource_type: 'entry',
        access_type: 'view',
        expiry_duration: '24h'
      }
    }
  ]
}
```

### 6. Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| Button shows "Error" immediately | No automation bound | Create automation with matching trigger event |
| PDF not downloading | Missing client-side library | Ensure jsPDF loaded on frontend |
| Temp link expired | Short expiry duration | Increase `expiry_duration` setting |
| Calculate returns 0 | Field name mismatch | Check {tags} match actual field names |
| Validation not stopping submit | `fail_action` set to `continue` | Change to `abort_submit` |

## Files Referenced

- Button schema: `/src/react/admin/schemas/elements/button.ts`
- Button component: `/src/react/admin/apps/form-builder-v2/components/elements/basic/Button.tsx`
- Frontend events: `/src/react/admin/lib/frontendEvents.ts`
- PHP event handler: `/src/includes/class-frontend-event-trigger.php`
- Actions: `/src/includes/automations/actions/class-action-*.php`

## Work Log

### 2025-12-15

#### Completed
- Created comprehensive documentation with all success criteria met
- Button element properties reference table
- Event flow diagram showing click → automation → result pipeline
- 4 complete workflow examples:
  - Generate PDF with client-side rendering
  - Shareable Link with temp access tokens
  - Calculate Total with currency formatting
  - Validate Before Submit with error collection
- Multi-button form pattern documentation
- MCP tool examples for LLM integration
- Troubleshooting table for common issues
