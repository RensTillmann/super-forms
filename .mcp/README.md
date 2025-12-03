# Super Forms MCP Server

AI-powered form building via Model Context Protocol (MCP).

## Overview

This MCP server exposes form manipulation tools that AI assistants (like Claude) can use to build and modify Super Forms programmatically.

## Features

- **AI Form Building**: Let AI create complete forms from natural language descriptions
- **Element Management**: Add, update, remove, and reorder form fields
- **Settings Control**: Modify form settings and configuration
- **Translations**: Add multi-language support
- **Version Control**: Save and revert to previous versions

## Setup

### 1. Install Dependencies

None required - uses Node.js built-in modules.

### 2. Configure WordPress Connection

Set environment variables:

```bash
export WP_SITE_URL="https://f4d.nl/dev"
export WP_API_NONCE="your-nonce-here"
export WP_COOKIE="wordpress_logged_in_xyz=..."
```

**Getting the nonce and cookie:**

1. Log into WordPress admin
2. Open browser DevTools → Network tab
3. Make any REST API request
4. Copy the `X-WP-Nonce` header value
5. Copy the `Cookie` header value

### 3. Add to Claude Desktop

Edit `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "super-forms": {
      "command": "node",
      "args": ["/home/rens/super-forms/.mcp/server.js"],
      "env": {
        "WP_SITE_URL": "https://f4d.nl/dev",
        "WP_API_NONCE": "your-nonce-here",
        "WP_COOKIE": "wordpress_logged_in_xyz=..."
      }
    }
  }
}
```

### 4. Restart Claude Desktop

The MCP server will be available in the next session.

## Available Tools

### Form Element Management

**`add_form_element`** - Add a new field to the form
```
formId: number
elementType: "text" | "email" | "textarea" | "dropdown" | etc.
position: number (or -1 for end)
label: string
name: string
config: { placeholder?, required?, validation?, ... }
```

**`update_form_element`** - Update existing field properties
```
formId: number
elementIndex: number
updates: { label?, name?, placeholder?, ... }
```

**`remove_form_element`** - Delete a field
```
formId: number
elementIndex: number
```

**`move_form_element`** - Reorder fields
```
formId: number
fromIndex: number
toIndex: number
```

### Form Settings

**`update_form_settings`** - Modify form configuration
```
formId: number
settings: {
  form_title?: string
  form_description?: string
  submit_button_text?: string
  success_message?: string
  enable_ajax?: boolean
  ...
}
```

### Translations

**`add_form_translation`** - Add/update translations
```
formId: number
language: string (e.g., "es", "fr")
translations: { key: value, ... }
```

### Form Retrieval

**`get_form`** - Get complete form data
```
formId: number
```

**`list_forms`** - List all forms
```
status?: "publish" | "draft" | "trash"
limit?: number
```

### Version Control

**`save_form_version`** - Create a version snapshot
```
formId: number
message?: string
```

**`revert_form_version`** - Revert to previous version
```
formId: number
versionId: number
```

## Example Usage

**Natural language → AI builds the form:**

> "Create a contact form with name, email, phone, and message fields. Make email required with validation."

AI will use these tools:
1. `add_form_element` for name field
2. `add_form_element` for email field (with required + validation)
3. `add_form_element` for phone field
4. `add_form_element` for message textarea
5. `update_form_settings` for form title
6. `save_form_version` to commit changes

## Architecture

```
AI Assistant (Claude)
    ↓
MCP Protocol (STDIO)
    ↓
server.js (This MCP Server)
    ↓
WordPress REST API
    ↓
SUPER_Form_REST_Controller
    ↓
SUPER_Form_DAL + SUPER_Form_Operations
    ↓
MySQL Database
```

## Security

- **Authentication**: Uses WordPress nonce + cookie
- **Permissions**: Requires `manage_options` capability
- **Validation**: All operations validated server-side
- **Audit Trail**: All changes logged in version history

## Troubleshooting

**"Invalid nonce" errors:**
- Nonce expires after 24 hours
- Generate new nonce from WordPress admin
- Update `WP_API_NONCE` environment variable

**"Connection refused" errors:**
- Check `WP_SITE_URL` is correct
- Verify WordPress REST API is accessible
- Check firewall/SSL settings

**Tool not appearing in Claude:**
- Restart Claude Desktop
- Check MCP server logs: `tail -f ~/Library/Logs/Claude/mcp*.log`
- Verify JSON syntax in `claude_desktop_config.json`

## Development

**Test the server manually:**

```bash
echo '{"method":"tools/list","params":{}}' | node server.js
```

**Debug mode:**

```bash
NODE_DEBUG=* node server.js
```

## License

Same as Super Forms plugin.
