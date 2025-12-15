# Epic: Generate PDF

## Use Case
Generate a PDF document from form data - receipts, certificates, invoices, reports, contracts. User clicks button and receives downloadable PDF.

## User Story
As a form user, I want to click a "Download PDF" button so that I receive a formatted document with my submitted information.

## Button Properties Required
- `action_type`: `'trigger_automation'`
- `event_id`: string - custom event identifier (e.g., "generate_pdf")
- `button_text`: string - "Download PDF", "Generate Receipt", "Get Certificate"
- `loading_text`: string - "Generating PDF..."
- `icon`: `'file-down'` | `'file-text'`
- `download_filename`: string, supportsTags - "{name}_receipt_{date}.pdf"
- `open_in_new_tab`: boolean - open PDF in new tab vs download

## Event Payload
```json
{
  "event": "button.generate_pdf.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_pdf_123",
    "form_data": { "name": "John", "amount": "150.00" },
    "user_id": 1,
    "session_key": "abc123",
    "requested_action": "generate_pdf",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `generate_file` (new) - Generate PDF/DOCX from template
- `send_email` - Email generated file as attachment
- `upload_to_media` - Save to WordPress media library
- `webhook` - Send file URL to external service

## New Node Types Needed
- `generate_file` - Core node for file generation
  - Settings: template_id, output_format (pdf/docx/csv), filename_pattern
  - Uses: TCPDF, PhpSpreadsheet, or similar
  - Returns: `{ file_url, attachment_id, file_size }`

## Frontend Behavior
- **Loading state**: Button shows spinner, "Generating PDF..."
- **Success handling**:
  - Trigger browser download of file
  - OR open in new tab
  - Show success toast "Your PDF is ready!"
- **Error handling**: Show error toast, offer retry

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('trigger_automation'),
  event_id: z.string().regex(/^[a-z][a-z0-9_]*$/),
  button_text: z.string().default('Download PDF'),
  loading_text: z.string().optional(),
  icon: z.string().optional(),
  download_filename: z.string().optional(),
  open_in_new_tab: z.boolean().default(false),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'createButtonAutomation',
  parameters: {
    action: 'createButtonAutomation',
    formId: number,
    buttonText: 'Download Receipt',
    eventId: 'generate_receipt',
    icon: 'file-down',
    automationActions: [
      {
        type: 'generate_file',
        config: {
          template: 'receipt_template',
          format: 'pdf',
          filename: '{name}_receipt_{date}'
        }
      }
    ]
  }
}
```

## Research Sources
- JetFormBuilder: PDF Attachment Addon with "Generate PDF" post-submit action
- JotForm: Export form as fillable PDF
- Form.io: PDF generation tied to submission
- Portant: Google Forms to PDF automation workflow
