# Epic: Temporary Access Link

## Use Case
Generate a time-limited access link - share entry data with others, allow temporary editing, provide secure file access. Critical for collaboration and secure sharing workflows.

## User Story
As a form user, I want to click a "Share" button so that I can generate a temporary link that allows someone else to view or edit my submission for a limited time.

## Button Properties Required
- `action_type`: `'trigger_automation'`
- `event_id`: string - custom event (e.g., "generate_share_link")
- `button_text`: string - "Share Entry", "Get Link", "Invite Collaborator"
- `loading_text`: string - "Generating link..."
- `access_type`: `'view'` | `'edit'` | `'download'`
- `expiry_duration`: string - "1h", "24h", "7d", "30d"
- `max_uses`: number | null - maximum times link can be used
- `require_email`: boolean - collect email before granting access
- `show_copy_button`: boolean - show button to copy link

## Event Payload
```json
{
  "event": "button.generate_share_link.clicked",
  "context": {
    "form_id": 123,
    "entry_id": 456,
    "button_id": "btn_share_123",
    "access_type": "view",
    "expiry_duration": "24h",
    "form_data": { "name": "John" },
    "user_id": 1,
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `generate_temp_access` (new) - Create temporary access token
- `send_email` - Email the access link
- `log_access` - Track who accessed via link
- `set_variable` - Store token for reference

## New Node Types Needed
- `generate_temp_access` - Create secure temporary access
  - Settings: access_type, resource (entry/file/form), expiry, max_uses, permissions
  - Security: cryptographically random token via `wp_generate_password(32, true, true)`
  - Storage: `wp_superforms_temp_access` table
  - Returns: `{ access_url, token, expires_at }`

- `revoke_access` - Invalidate a temp access token
  - Settings: token or access_id
  - Returns: `{ revoked: true }`

## Frontend Behavior
- **On click**:
  - Show loading state
  - Call automation to generate token
  - Receive access URL
  - Display modal/inline with:
    - The generated link
    - "Copy" button
    - "Email" button (optional)
    - Expiry information
- **Copy functionality**:
  - Copy link to clipboard
  - Show "Copied!" confirmation
- **Access tracking**:
  - Show who accessed and when (if tracked)

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('trigger_automation'),
  event_id: z.string(),
  button_text: z.string().default('Share'),
  loading_text: z.string().optional(),
  access_type: z.enum(['view', 'edit', 'download']).default('view'),
  expiry_duration: z.string().default('24h'), // 1h, 24h, 7d, 30d
  max_uses: z.number().nullable().optional(),
  require_email: z.boolean().default(false),
  show_copy_button: z.boolean().default(true),
  show_email_option: z.boolean().default(true),
  icon: z.string().default('share-2'),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'createButtonAutomation',
  parameters: {
    action: 'createButtonAutomation',
    formId: number,
    buttonText: 'Share Entry',
    eventId: 'generate_share_link',
    icon: 'share-2',
    automationActions: [
      {
        type: 'generate_temp_access',
        config: {
          access_type: 'view',
          resource_type: 'entry',
          expiry: '24h',
          max_uses: 5
        }
      }
    ]
  }
}
```

## Security Considerations
- Tokens must be cryptographically random (32+ chars)
- Store hashed tokens in database
- Validate expiry on every access
- Track access attempts for auditing
- Allow revocation at any time
- Rate limit token generation

## Database Schema
```sql
CREATE TABLE wp_superforms_temp_access (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token_hash VARCHAR(64) NOT NULL,
  resource_type ENUM('entry', 'file', 'form') NOT NULL,
  resource_id BIGINT UNSIGNED NOT NULL,
  access_type ENUM('view', 'edit', 'download') NOT NULL,
  created_by BIGINT UNSIGNED,
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  max_uses INT UNSIGNED NULL,
  use_count INT UNSIGNED DEFAULT 0,
  last_used_at DATETIME NULL,
  revoked_at DATETIME NULL,
  INDEX (token_hash),
  INDEX (expires_at)
);
```

## Research Sources
- Gravity Forms: Save and Continue resume links
- JotForm: Draft sharing via unique link
- Google Docs: Shareable links with expiry
- Dropbox: Shared link patterns
