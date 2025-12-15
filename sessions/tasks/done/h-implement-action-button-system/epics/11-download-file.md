# Epic: Download File

## Use Case
Download a static or dynamically generated file - certificates, receipts, documents, images. Similar to PDF generation but supports multiple formats and can include pre-existing files.

## User Story
As a form user, I want to click a "Download" button so that I receive a file (certificate, receipt, document) based on my form submission.

## Button Properties Required
- `action_type`: `'trigger_automation'`
- `event_id`: string - custom event (e.g., "download_certificate")
- `button_text`: string - "Download Certificate", "Get Receipt", "Download"
- `loading_text`: string - "Preparing download..."
- `file_source`: `'static'` | `'generated'` | `'attachment'`
- `static_file_url`: string - URL of static file (if source is static)
- `generated_template`: string - template ID for generation
- `output_format`: `'pdf'` | `'docx'` | `'xlsx'` | `'csv'` | `'zip'`
- `filename`: string, supportsTags - "{name}_certificate.pdf"
- `icon`: `'download'` | `'file-text'`

## Event Payload
```json
{
  "event": "button.download_certificate.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_download_123",
    "file_source": "generated",
    "output_format": "pdf",
    "form_data": { "name": "John Doe", "course": "Advanced Training" },
    "user_id": 1,
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `generate_file` - Generate document from template
- `fetch_attachment` - Retrieve stored attachment
- `send_email` - Email file as attachment
- `create_temp_access` - Generate temporary download link

## New Node Types Needed
Same `generate_file` node from PDF epic, plus:
- `create_download_response` - Return file for immediate download
  - Settings: file_url OR file_content, filename, content_type
  - Returns: `{ download_url, expires_at }`

## Frontend Behavior
- **On click**:
  - Show loading state
  - Call automation to generate/fetch file
  - Receive download URL in response
  - Trigger browser download
- **Success handling**:
  - File downloads automatically
  - Show success toast
- **Error handling**:
  - Show error message
  - Offer retry option

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('trigger_automation'),
  event_id: z.string(),
  button_text: z.string().default('Download'),
  loading_text: z.string().optional(),
  file_source: z.enum(['static', 'generated', 'attachment']),
  static_file_url: z.string().url().optional(),
  generated_template: z.string().optional(),
  output_format: z.enum(['pdf', 'docx', 'xlsx', 'csv', 'zip', 'png', 'jpg']).optional(),
  filename: z.string().optional(),
  icon: z.string().default('download'),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'createButtonAutomation',
  parameters: {
    action: 'createButtonAutomation',
    formId: number,
    buttonText: 'Download Certificate',
    eventId: 'download_certificate',
    icon: 'award',
    automationActions: [
      {
        type: 'generate_file',
        config: {
          template: 'certificate_template',
          format: 'pdf',
          filename: '{name}_certificate_{date}'
        }
      },
      {
        type: 'create_download_response',
        config: {
          source: '{generated_file_url}'
        }
      }
    ]
  }
}
```

## File Source Types
| Source | Description | Example |
|--------|-------------|---------|
| static | Pre-existing file URL | Terms PDF, brochure |
| generated | Created from template + form data | Certificate, invoice |
| attachment | File uploaded to entry | User's resume |

## Research Sources
- GeeksForGeeks: "Button that submits form and downloads PDF"
- JetFormBuilder: PDF attachment download on submit
- Form.io: File download after submission
