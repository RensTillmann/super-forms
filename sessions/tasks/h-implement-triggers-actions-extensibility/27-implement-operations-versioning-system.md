# Phase 27: Operations-Based Form Editing & Versioning System

## Overview

Implement a sophisticated operations-based architecture for form editing that enables:
1. **JSON Patch Operations** (RFC 6902) for atomic, incremental updates
2. **AI/LLM Integration** via MCP server exposing form manipulation tools
3. **Undo/Redo System** with operation history tracking
4. **Version Control** (git-like) with snapshots and diffing
5. **Performance** via tiny payloads (2KB vs 200KB)

## Problem Statement

**Current Approach (Simple CRUD):**
- Send entire form JSON on every save (200KB+)
- No undo/redo capability
- No version history
- No AI integration path
- Large payloads hit server limits on cheap hosting

**Proposed Approach (Operations-Based):**
- Send tiny patch operations (2KB)
- Built-in undo/redo via operation inversion
- Full version control with snapshots
- Natural AI integration via MCP tools
- Sub-second saves even on slow hosting

## Core Concepts

### 1. JSON Patch Operations (RFC 6902)

**Standard format:**
```json
[
  {
    "op": "add",
    "path": "/elements/-",
    "value": {
      "type": "text",
      "name": "email",
      "label": "Email Address"
    }
  },
  {
    "op": "replace",
    "path": "/settings/form_title",
    "value": "Contact Us"
  },
  {
    "op": "remove",
    "path": "/elements/3"
  },
  {
    "op": "move",
    "from": "/elements/5",
    "path": "/elements/2"
  },
  {
    "op": "copy",
    "from": "/elements/1",
    "path": "/elements/-"
  },
  {
    "op": "test",
    "path": "/settings/form_title",
    "value": "Old Title"
  }
]
```

**Operations:**
- `add` - Add element, setting, translation, node
- `remove` - Delete element, node, setting
- `replace` - Update existing value
- `move` - Reorder elements (drag & drop)
- `copy` - Duplicate element
- `test` - Validate precondition before applying

### 2. Two-Layer Architecture

**Layer 1: Session Operations (Undo/Redo)**
```javascript
// In-memory operation stack (React state or localStorage)
const sessionOps = [
  {id: 1, op: "add", path: "/elements/-", value: {...}, timestamp: Date.now()},
  {id: 2, op: "replace", path: "/settings/title", value: "New", timestamp: Date.now()},
  // ... more operations
];

let currentIndex = 1; // Current position in history

// Undo: Apply inverse operation
function undo() {
  const op = sessionOps[currentIndex];
  const inverse = getInverseOperation(op);
  applyOperation(inverse);
  currentIndex--;
}

// Redo: Reapply operation
function redo() {
  currentIndex++;
  const op = sessionOps[currentIndex];
  applyOperation(op);
}
```

**Layer 2: Saved Versions (Snapshots)**
```javascript
// Database-backed version control
// When user clicks "Save" button:
saveVersion({
  form_id: 123,
  snapshot: currentFormState, // Full JSON
  operations: sessionOps,     // Operations since last save
  message: "Added payment fields" // Optional
});
```

## Database Schema

### Form Versions Table

```sql
CREATE TABLE wp_superforms_form_versions (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  version_number INT NOT NULL,
  snapshot LONGTEXT,              -- Full form state (JSON)
  operations JSON,                -- Operations since last version
  created_by BIGINT(20) UNSIGNED,
  created_at DATETIME NOT NULL,
  message VARCHAR(500),            -- Optional commit message
  PRIMARY KEY (id),
  KEY form_versions (form_id, version_number DESC),
  KEY created_at (created_at)
) ENGINE=InnoDB;
```

### Operation Log Table (Optional - for advanced features)

```sql
CREATE TABLE wp_superforms_form_operations (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  session_id VARCHAR(100),         -- Group operations by editing session
  operation JSON,                  -- Single patch operation
  user_id BIGINT(20) UNSIGNED,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY form_session (form_id, session_id),
  KEY created_at (created_at)
) ENGINE=InnoDB;
```

## AI/LLM Integration via MCP Server

### MCP Server Tools

**Tool 1: Add Form Element**
```typescript
{
  name: "add_form_element",
  description: "Add a new field to the form",
  inputSchema: {
    type: "object",
    properties: {
      formId: { type: "number", description: "Form ID" },
      elementType: {
        type: "string",
        enum: ["text", "email", "textarea", "dropdown", "checkbox", "radio", "file", ...],
        description: "Type of form element to add"
      },
      position: {
        type: "number",
        description: "Position to insert (0-based index, -1 for end)"
      },
      label: { type: "string", description: "Field label" },
      name: { type: "string", description: "Field name (unique)" },
      config: {
        type: "object",
        description: "Additional field configuration (required, placeholder, etc.)"
      }
    },
    required: ["formId", "elementType", "label", "name"]
  }
}
```

**Tool 2: Update Form Settings**
```typescript
{
  name: "update_form_settings",
  description: "Update form-level settings",
  inputSchema: {
    type: "object",
    properties: {
      formId: { type: "number" },
      path: {
        type: "string",
        description: "JSON path to setting (e.g., 'theme.primary_color', 'settings.ajax_enabled')"
      },
      value: { description: "New value for the setting" }
    },
    required: ["formId", "path", "value"]
  }
}
```

**Tool 3: Remove Form Element**
```typescript
{
  name: "remove_form_element",
  description: "Remove a field from the form",
  inputSchema: {
    type: "object",
    properties: {
      formId: { type: "number" },
      elementIndex: {
        type: "number",
        description: "Index of element to remove (0-based)"
      }
    },
    required: ["formId", "elementIndex"]
  }
}
```

**Tool 4: Reorder Elements**
```typescript
{
  name: "reorder_form_elements",
  description: "Change the order of form fields",
  inputSchema: {
    type: "object",
    properties: {
      formId: { type: "number" },
      fromIndex: { type: "number", description: "Current position" },
      toIndex: { type: "number", description: "New position" }
    },
    required: ["formId", "fromIndex", "toIndex"]
  }
}
```

**Tool 5: Add Automation Node**
```typescript
{
  name: "add_automation_node",
  description: "Add a node to the form's automation workflow",
  inputSchema: {
    type: "object",
    properties: {
      formId: { type: "number" },
      automationId: { type: "number" },
      nodeType: {
        type: "string",
        enum: ["trigger", "action", "condition", "control"],
        description: "Type of workflow node"
      },
      nodeConfig: {
        type: "object",
        description: "Node-specific configuration"
      },
      position: {
        type: "object",
        properties: {
          x: { type: "number" },
          y: { type: "number" }
        }
      }
    },
    required: ["formId", "automationId", "nodeType", "nodeConfig"]
  }
}
```

**Tool 6: Update Theme Settings**
```typescript
{
  name: "update_theme_settings",
  description: "Update form theme/styling",
  inputSchema: {
    type: "object",
    properties: {
      formId: { type: "number" },
      themeProperty: {
        type: "string",
        description: "Theme property path (e.g., 'colors.primary', 'typography.fontSize')"
      },
      value: { description: "New value for the theme property" }
    },
    required: ["formId", "themeProperty", "value"]
  }
}
```

### MCP Server Implementation Example

**User prompt to Claude:**
> "Add an email field after the name field, make it required, and update the submit button text to 'Send Message'"

**Claude's response (via MCP tools):**
```typescript
// Tool call 1: Add email field
await add_form_element({
  formId: 123,
  elementType: "email",
  position: 2, // After name field (index 1)
  label: "Email Address",
  name: "email",
  config: { required: true, placeholder: "you@example.com" }
});

// Tool call 2: Update submit button text
await update_form_settings({
  formId: 123,
  path: "settings.submit_button_text",
  value: "Send Message"
});
```

**What happens behind the scenes:**
1. MCP server converts tool calls to JSON Patch operations
2. Sends `PATCH /wp-json/super-forms/v1/forms/123` with operations
3. Backend validates and applies operations
4. Returns updated form state
5. Claude confirms: "Done! I've added an email field after the name field and updated the submit button text."

## REST API Endpoints

### Apply Operations (PATCH)

```
PATCH /wp-json/super-forms/v1/forms/{id}
Content-Type: application/json-patch+json

[
  {"op": "add", "path": "/elements/-", "value": {...}},
  {"op": "replace", "path": "/settings/title", "value": "New Title"}
]
```

**Response:**
```json
{
  "success": true,
  "applied_operations": 2,
  "form": { /* updated form state */ },
  "version": 15 // Operation count or version number
}
```

### Save Version (Snapshot)

```
POST /wp-json/super-forms/v1/forms/{id}/versions

{
  "message": "Added payment fields", // Optional
  "operations": [...] // Operations since last save
}
```

**Response:**
```json
{
  "success": true,
  "version_id": 42,
  "version_number": 5,
  "snapshot_size": 45320 // bytes
}
```

### Get Version History

```
GET /wp-json/super-forms/v1/forms/{id}/versions?limit=10
```

**Response:**
```json
{
  "versions": [
    {
      "id": 42,
      "version_number": 5,
      "created_by": "John Doe",
      "created_at": "2025-12-01 14:23:45",
      "message": "Added payment fields",
      "operations_count": 12
    },
    // ... more versions
  ],
  "total": 25
}
```

### Revert to Version

```
POST /wp-json/super-forms/v1/forms/{id}/revert

{
  "version_id": 38
}
```

### Get Version Diff

```
GET /wp-json/super-forms/v1/forms/{id}/versions/{version_id}/diff?compare_to={other_version_id}
```

**Response:**
```json
{
  "added": [
    {"path": "/elements/5", "value": {...}}
  ],
  "removed": [
    {"path": "/elements/3"}
  ],
  "changed": [
    {"path": "/settings/title", "old": "Old Title", "new": "New Title"}
  ]
}
```

## Frontend Implementation

### React State Management

```typescript
// useFormEditor hook
function useFormEditor(formId: number) {
  const [form, setForm] = useState<Form>(null);
  const [operationHistory, setOperationHistory] = useState<Operation[]>([]);
  const [currentIndex, setCurrentIndex] = useState(-1);

  // Apply operation and add to history
  const applyOperation = (op: Operation) => {
    // Apply to local state
    const newForm = applyPatch(form, [op]);
    setForm(newForm);

    // Add to history
    const newHistory = operationHistory.slice(0, currentIndex + 1);
    newHistory.push(op);
    setOperationHistory(newHistory);
    setCurrentIndex(newHistory.length - 1);

    // Send to server (debounced)
    debouncedSyncToServer([op]);
  };

  // Undo
  const undo = () => {
    if (currentIndex < 0) return;

    const op = operationHistory[currentIndex];
    const inverse = getInverseOperation(op);

    const newForm = applyPatch(form, [inverse]);
    setForm(newForm);
    setCurrentIndex(currentIndex - 1);

    debouncedSyncToServer([inverse]);
  };

  // Redo
  const redo = () => {
    if (currentIndex >= operationHistory.length - 1) return;

    const op = operationHistory[currentIndex + 1];
    const newForm = applyPatch(form, [op]);
    setForm(newForm);
    setCurrentIndex(currentIndex + 1);

    debouncedSyncToServer([op]);
  };

  // Save version
  const saveVersion = async (message?: string) => {
    const unsavedOps = operationHistory.slice(lastSavedIndex + 1);

    await fetch(`/wp-json/super-forms/v1/forms/${formId}/versions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message, operations: unsavedOps })
    });

    setLastSavedIndex(currentIndex);
  };

  return {
    form,
    applyOperation,
    undo,
    redo,
    canUndo: currentIndex >= 0,
    canRedo: currentIndex < operationHistory.length - 1,
    saveVersion,
    hasUnsavedChanges: currentIndex > lastSavedIndex
  };
}
```

### UI Components

**Undo/Redo Buttons:**
```tsx
<div className="toolbar">
  <button
    onClick={undo}
    disabled={!canUndo}
    title="Undo (Ctrl+Z)"
  >
    <UndoIcon />
  </button>
  <button
    onClick={redo}
    disabled={!canRedo}
    title="Redo (Ctrl+Y)"
  >
    <RedoIcon />
  </button>
  <button
    onClick={() => saveVersion()}
    disabled={!hasUnsavedChanges}
    className={hasUnsavedChanges ? 'has-changes' : ''}
  >
    Save {hasUnsavedChanges && '*'}
  </button>
</div>
```

**Version History Modal:**
```tsx
<VersionHistory formId={formId}>
  {versions.map(v => (
    <VersionItem key={v.id}>
      <div className="version-info">
        <span className="version-number">v{v.version_number}</span>
        <span className="created-by">{v.created_by}</span>
        <time>{v.created_at}</time>
        {v.message && <p className="message">{v.message}</p>}
      </div>
      <div className="version-actions">
        <button onClick={() => viewDiff(v.id)}>View Changes</button>
        <button onClick={() => revertTo(v.id)}>Restore</button>
      </div>
    </VersionItem>
  ))}
</VersionHistory>
```

## Operation Inversion (for Undo)

```typescript
function getInverseOperation(op: Operation): Operation {
  switch (op.op) {
    case 'add':
      // Inverse of add is remove
      return { op: 'remove', path: op.path };

    case 'remove':
      // Inverse of remove is add (need to cache old value)
      return { op: 'add', path: op.path, value: op.oldValue };

    case 'replace':
      // Inverse of replace is replace with old value
      return { op: 'replace', path: op.path, value: op.oldValue };

    case 'move':
      // Inverse of move is move back
      return { op: 'move', from: op.path, path: op.from };

    case 'copy':
      // Inverse of copy is remove the copy
      return { op: 'remove', path: op.path };

    default:
      throw new Error(`Unknown operation: ${op.op}`);
  }
}
```

**Key insight:** We must cache `oldValue` when applying operations to enable proper undo.

## Backend Implementation

### PHP JSON Patch Library

```php
class SUPER_JSON_Patch {
    /**
     * Apply JSON Patch operations to a document
     *
     * @param array $document  The JSON document to patch
     * @param array $operations Array of patch operations
     * @return array|WP_Error   Patched document or error
     */
    public static function apply($document, $operations) {
        foreach ($operations as $op) {
            $result = self::apply_operation($document, $op);
            if (is_wp_error($result)) {
                return $result;
            }
            $document = $result;
        }
        return $document;
    }

    private static function apply_operation($document, $op) {
        $operation = $op['op'];
        $path = $op['path'];

        switch ($operation) {
            case 'add':
                return self::op_add($document, $path, $op['value']);
            case 'remove':
                return self::op_remove($document, $path);
            case 'replace':
                return self::op_replace($document, $path, $op['value']);
            case 'move':
                return self::op_move($document, $op['from'], $path);
            case 'copy':
                return self::op_copy($document, $op['from'], $path);
            case 'test':
                return self::op_test($document, $path, $op['value']);
            default:
                return new WP_Error('invalid_operation', "Unknown operation: {$operation}");
        }
    }

    private static function op_add($document, $path, $value) {
        $pointer = self::parse_pointer($path);
        $parent = &$document;

        // Navigate to parent
        for ($i = 0; $i < count($pointer) - 1; $i++) {
            $token = $pointer[$i];
            if (!isset($parent[$token])) {
                $parent[$token] = array();
            }
            $parent = &$parent[$token];
        }

        $lastToken = $pointer[count($pointer) - 1];

        // Special handling for array append (-1 or '-')
        if ($lastToken === '-' || $lastToken === -1) {
            $parent[] = $value;
        } else {
            $parent[$lastToken] = $value;
        }

        return $document;
    }

    private static function op_remove($document, $path) {
        $pointer = self::parse_pointer($path);
        $parent = &$document;

        for ($i = 0; $i < count($pointer) - 1; $i++) {
            $token = $pointer[$i];
            if (!isset($parent[$token])) {
                return new WP_Error('path_not_found', "Path not found: {$path}");
            }
            $parent = &$parent[$token];
        }

        $lastToken = $pointer[count($pointer) - 1];
        if (!isset($parent[$lastToken])) {
            return new WP_Error('path_not_found', "Path not found: {$path}");
        }

        unset($parent[$lastToken]);
        return $document;
    }

    // ... other operations

    private static function parse_pointer($path) {
        // "/elements/0/name" → ["elements", "0", "name"]
        $path = ltrim($path, '/');
        return $path === '' ? [] : explode('/', $path);
    }
}
```

### REST Controller Enhancement

```php
class SUPER_Form_REST_Controller extends WP_REST_Controller {

    /**
     * Handle PATCH request with JSON Patch operations
     */
    public function update_form_patch($request) {
        $form_id = $request['id'];
        $operations = $request->get_json_params();

        // Validate operations format
        if (!is_array($operations)) {
            return new WP_Error('invalid_patch', 'Patch must be an array of operations');
        }

        // Get current form
        $form = SUPER_Form_DAL::get($form_id);
        if (is_wp_error($form)) {
            return $form;
        }

        // Apply patch operations
        $patched_form = SUPER_JSON_Patch::apply($form, $operations);
        if (is_wp_error($patched_form)) {
            return $patched_form;
        }

        // Save patched form
        $result = SUPER_Form_DAL::update($form_id, $patched_form);
        if (is_wp_error($result)) {
            return $result;
        }

        // Log operations (optional)
        $this->log_operations($form_id, $operations);

        return new WP_REST_Response(array(
            'success' => true,
            'applied_operations' => count($operations),
            'form' => $patched_form
        ), 200);
    }

    /**
     * Save a new version snapshot
     */
    public function create_version($request) {
        $form_id = $request['id'];
        $message = $request->get_param('message');
        $operations = $request->get_param('operations');

        $form = SUPER_Form_DAL::get($form_id);

        global $wpdb;
        $table = $wpdb->prefix . 'superforms_form_versions';

        // Get next version number
        $version_number = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(version_number), 0) + 1 FROM {$table} WHERE form_id = %d",
            $form_id
        ));

        $result = $wpdb->insert($table, array(
            'form_id' => $form_id,
            'version_number' => $version_number,
            'snapshot' => wp_json_encode($form),
            'operations' => $operations ? wp_json_encode($operations) : null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'message' => $message
        ));

        if ($result === false) {
            return new WP_Error('save_failed', 'Failed to save version');
        }

        // Cleanup old versions (keep last 20)
        $this->cleanup_old_versions($form_id, 20);

        return new WP_REST_Response(array(
            'success' => true,
            'version_id' => $wpdb->insert_id,
            'version_number' => $version_number
        ), 201);
    }

    private function cleanup_old_versions($form_id, $keep_count) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_form_versions';

        $wpdb->query($wpdb->prepare("
            DELETE FROM {$table}
            WHERE form_id = %d
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM {$table}
                    WHERE form_id = %d
                    ORDER BY version_number DESC
                    LIMIT %d
                ) AS keep_versions
            )
        ", $form_id, $form_id, $keep_count));
    }
}
```

## Configuration & Settings

### Admin Settings

**Version Control Settings:**
```php
// In form settings or global plugin settings
$settings = array(
    'version_control' => array(
        'enabled' => true,
        'max_versions' => 20,           // Keep last 20 versions
        'auto_save_interval' => 300,    // Auto-save every 5 minutes
        'require_commit_message' => false,
        'show_version_history' => true
    ),
    'undo_redo' => array(
        'enabled' => true,
        'max_operations' => 100,        // Keep last 100 operations
        'persist_session' => true,      // Save to localStorage
        'keyboard_shortcuts' => array(
            'undo' => 'Ctrl+Z',
            'redo' => 'Ctrl+Y',
            'save' => 'Ctrl+S'
        )
    )
);
```

## Success Criteria

### Phase 27.1: JSON Patch Infrastructure
- [ ] `SUPER_JSON_Patch` class with all 6 operations (add, remove, replace, move, copy, test)
- [ ] Unit tests for each operation type
- [ ] Error handling for invalid paths
- [ ] Support for array operations (append with `-`)

### Phase 27.2: REST API Endpoints
- [ ] `PATCH /forms/{id}` - Apply operations
- [ ] `POST /forms/{id}/versions` - Save version
- [ ] `GET /forms/{id}/versions` - List versions
- [ ] `GET /forms/{id}/versions/{vid}/diff` - Compare versions
- [ ] `POST /forms/{id}/revert` - Restore version

### Phase 27.3: Database Tables
- [ ] `wp_superforms_form_versions` table created
- [ ] Version cleanup scheduler (keep last 20)
- [ ] Migration for existing forms (create initial version)

### Phase 27.4: Frontend Undo/Redo
- [ ] Operation history state management
- [ ] Undo/Redo toolbar buttons
- [ ] Keyboard shortcuts (Ctrl+Z, Ctrl+Y)
- [ ] Operation inversion logic
- [ ] Visual feedback for unsaved changes

### Phase 27.5: Version History UI
- [ ] Version history modal
- [ ] Visual diff viewer
- [ ] Restore version functionality
- [ ] Optional commit messages
- [ ] User/timestamp display

### Phase 27.6: MCP Server Integration
- [ ] MCP server implementation
- [ ] 6+ form manipulation tools defined
- [ ] Tool calls → JSON Patch conversion
- [ ] Error handling and validation
- [ ] Documentation for AI prompts

### Phase 27.7: Performance & Optimization
- [ ] Debounced server sync
- [ ] Optimistic UI updates
- [ ] Operation batching
- [ ] Payload size monitoring
- [ ] Cache invalidation strategy

## Implementation Order

**Recommended sequence:**

1. **Week 1: JSON Patch Foundation**
   - Implement `SUPER_JSON_Patch` class
   - REST endpoint for `PATCH /forms/{id}`
   - Unit tests

2. **Week 2: Frontend Undo/Redo**
   - React state management
   - Operation history
   - Undo/Redo UI
   - Keyboard shortcuts

3. **Week 3: Version Control**
   - Database table
   - Save version endpoint
   - Version history UI
   - Diff viewer

4. **Week 4: MCP Server**
   - MCP server setup
   - Tool definitions
   - Integration testing
   - Documentation

## Benefits Summary

✅ **Performance**: 2KB vs 200KB payloads (99% reduction)
✅ **AI Integration**: Natural LLM interaction via MCP tools
✅ **User Experience**: Undo/Redo, version history like Google Docs
✅ **Developer Experience**: Clean, atomic operations
✅ **Audit Trail**: Know exactly what changed when
✅ **Future-Proof**: Enables real-time collaboration later
✅ **Server-Friendly**: Works on cheap hosting with low limits

## Dependencies

- **Requires**: Phase 27 is standalone but integrates with:
  - Forms migration (Phase 18) - operates on custom table
  - React admin UI - provides the editor interface
  - MCP server infrastructure - exposes tools to Claude

## Notes

- This is inspired by Google Docs' operational transformation
- Git-like experience familiar to developers
- Natural fit for AI/LLM form building
- Can be implemented incrementally (JSON Patch → Undo → Versions → MCP)
