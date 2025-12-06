---
name: m-implement-styles-themes-ui
branch: feature/h-implement-triggers-actions-extensibility
status: completed
created: 2025-12-06
---

# Styles & Themes UI Integration

## Problem/Goal

The Global/Individual Style System infrastructure is built (task `n-implement-global-individual-styles`), but it's not yet wired into the user-facing UI. Users need:

1. **Styles Tab** - Edit global styles for all node types (labels, inputs, buttons, etc.)
2. **Themes Tab** - Pick from preset themes or save custom themes
3. **Per-Element Styles** - Override individual element styles in FloatingPanel
4. **Live Preview** - Elements render using resolved styles in real-time

Additionally, themes must be **first-class entities** stored in their own database table (not form settings), so they persist independently of forms and can be reused across forms. Custom themes are owned by users (`user_id` column).

The system must be **schema-first** so MCP/AI can:
- List available themes
- Apply a theme
- Create/save custom themes
- Generate themes from prompts or brand colors
- Modify global styles with natural language

## Success Criteria

### Infrastructure
- [x] Database table `wp_superforms_themes` created with migrations
- [x] Custom themes stored with `user_id` ownership (persist independently of forms)
- [x] PHP DAL class for theme CRUD operations
- [x] REST API endpoints for themes (list, get, create, update, delete, apply)
- [x] System themes (Light, Dark) fully implemented and seeded
- [x] Theme stubs (Minimal, Classic, Modern, etc.) stored with `is_stub` flag so UI shows "Coming Soon" badge

### MCP/AI Integration
- [x] MCP actions: listThemes, getTheme, applyTheme, createTheme, deleteTheme
- [x] MCP action: generateTheme (create theme from prompt/baseColor/density/cornerStyle)
- [x] generateTheme persists theme to database when `save=true`
- [x] AI can apply themes ("use dark theme")
- [x] AI can modify styles ("change border color to red", "make fields larger")
- [x] AI can generate new themes ("create theme based on #FF5722", "warm friendly theme")
- [x] AI example prompts documented as test cases in subtask docs

### UI
- [x] Themes Tab with gallery view, theme cards with preview swatches, apply button
- [x] "Coming Soon" badge on stub themes (via `is_stub` flag from API)
- [x] "Save Current as Theme" dialog
- [x] Styles Tab wired into Form Builder toolbar
- [x] Per-element style overrides in FloatingPanel with link/unlink UI

### Integration
- [x] ElementRenderer uses useResolvedStyle for live preview
- [x] TypeScript compiles cleanly
- [x] Theme switching works end-to-end
- [x] Export/Import themes as JSON

## Context Manifest

### How Database Migrations Work in Super Forms

The plugin uses a centralized migration pattern in `SUPER_Install::create_tables()` located at `/home/rens/super-forms/src/includes/class-install.php`. When a new table needs to be added:

**Pattern Flow:**
1. **Table Creation**: WordPress's `dbDelta()` function is used for schema creation/updates (lines 99-410 in class-install.php)
2. **Engine Detection**: The system checks if InnoDB is available using `self::get_storage_engine()` (lines 640-666). InnoDB is strongly preferred for transaction support
3. **Schema Structure**: Tables follow a consistent pattern with:
   - Auto-incrementing BIGINT primary key called `id`
   - Timestamps: `created_at`, `updated_at` (DATETIME)
   - JSON columns stored as LONGTEXT (e.g., `workflow_graph`, `settings`, `styles`)
   - Proper indexing on frequently queried columns
   - Consistent naming: `wp_superforms_*` (prefix handled by `$wpdb->prefix`)

**Example from Automations Table (lines 133-150):**
```php
$table_name = $wpdb->prefix . 'superforms_automations';

$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'visual',
    workflow_graph LONGTEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY enabled (enabled),
    KEY type (type)
) ENGINE={$engine} $charset_collate;";

dbDelta( $sql );
```

**Key Patterns:**
- `dbDelta()` is idempotent - safe to run multiple times (creates if missing, alters if schema changed)
- Charset/collation retrieved via `$wpdb->get_charset_collate()` with fallback to utf8mb4_unicode_ci
- User-owned data uses `user_id BIGINT(20) UNSIGNED` column with INDEX
- Boolean flags use `TINYINT(1)` (e.g., `is_system`, `is_stub`, `enabled`)
- Unique constraints for slug/key columns: `UNIQUE KEY slug (slug)`
- Foreign-key-like columns indexed: `KEY user_id (user_id)`

**For Themes Table:**
The wp_superforms_themes table will follow this exact pattern with JSON styles column, user_id ownership, is_system and is_stub flags.

---

### How DAL Classes Work (Data Access Layer)

DAL classes provide database abstraction following a static method pattern. Example: `SUPER_Automation_DAL` at `/home/rens/super-forms/src/includes/automations/class-automation-dal.php`.

**Core Pattern:**
- **Static methods** - No instantiation required, called directly: `SUPER_Automation_DAL::get_automation($id)`
- **Return types** - Returns data array/object on success, `WP_Error` on failure
- **JSON handling** - Automatically encodes arrays to JSON on insert/update, decodes on retrieval
- **Validation** - Required field checks before database operations
- **Sanitization** - Uses `sanitize_text_field()`, `absint()`, `sanitize_textarea_field()` appropriately

**CRUD Method Signatures (from SUPER_Automation_DAL):**

```php
// CREATE - Returns new ID or WP_Error
public static function create_automation( $data ) {
    // Validate required fields (lines 43-49)
    if ( empty( $data['name'] ) ) {
        return new WP_Error('missing_name', __('Automation name is required', 'super-forms'));
    }

    // Set defaults (lines 52-59)
    $data = wp_parse_args($data, array(
        'type' => 'visual',
        'workflow_graph' => '',
        'enabled' => 1,
    ));

    // JSON encode arrays (lines 74-77)
    if ( is_array( $workflow_graph ) ) {
        $workflow_graph = wp_json_encode( $workflow_graph );
    }

    // Insert with proper formats (lines 89-93)
    $result = $wpdb->insert(
        $wpdb->prefix . 'superforms_automations',
        $insert_data,
        array( '%s', '%s', '%s', '%d', '%s', '%s' ) // Format strings
    );

    return $wpdb->insert_id;
}

// READ - Returns object/array or WP_Error
public static function get_automation( $automation_id ) {
    // Validation (lines 115-120)
    // Query using prepared statement (lines 122-128)
    // JSON decode (lines 138-140)
    // Return data or WP_Error if not found
}

// UPDATE - Returns true or WP_Error
public static function update_automation( $automation_id, $data ) {
    // Verify exists first (lines 192-195)
    // Build update data conditionally (lines 198-224)
    // Always update timestamp (lines 226-227)
    // Execute update (lines 229-235)
}

// DELETE - Returns true or WP_Error
public static function delete_automation( $automation_id ) {
    // Verify exists (lines 264-268)
    // Manual cascade delete of related records (lines 271-275)
    // Delete main record (lines 278-282)
}
```

**For Themes DAL:**
Will implement: `create_theme()`, `get_theme()`, `get_all_themes()`, `update_theme()`, `delete_theme()` following this exact pattern. Additional helper: `seed_system_themes()` to populate Light/Dark on activation.

---

### How REST Controllers Work

REST controllers extend `WP_REST_Controller` and follow WordPress REST API conventions. Example: `SUPER_Automation_REST_Controller` at `/home/rens/super-forms/src/includes/automations/class-automation-rest-controller.php`.

**Registration Pattern (lines 39-224):**

```php
class SUPER_Automation_REST_Controller extends WP_REST_Controller {
    protected $namespace = 'super-forms/v1';

    public function register_routes() {
        // Collection endpoint (GET + POST)
        register_rest_route(
            $this->namespace,
            '/automations',
            array(
                array(
                    'methods' => WP_REST_Server::READABLE,  // GET
                    'callback' => array($this, 'get_automations'),
                    'permission_callback' => array($this, 'check_permission'),
                    'args' => $this->get_collection_params(),
                ),
                array(
                    'methods' => WP_REST_Server::CREATABLE, // POST
                    'callback' => array($this, 'create_automation'),
                    'permission_callback' => array($this, 'check_permission'),
                ),
            )
        );

        // Single item endpoint (GET, PUT, DELETE)
        register_rest_route(
            $this->namespace,
            '/automations/(?P<id>[\d]+)',
            array(
                array('methods' => WP_REST_Server::READABLE, ...), // GET
                array('methods' => WP_REST_Server::EDITABLE, ...), // PUT/PATCH
                array('methods' => WP_REST_Server::DELETABLE, ...), // DELETE
            )
        );
    }
}
```

**Permission Handling (lines 227-375):**
- `check_permission()` - Primary auth check, supports both cookie auth (logged-in users) and API key auth
- For admin-only operations: `current_user_can('manage_options')`
- The system supports API key authentication via X-API-Key header for future MCP/AI integration

**Endpoint Implementation Pattern:**

```php
public function get_automations( $request ) {
    // 1. Extract params
    $params = $request->get_params();
    $enabled_only = isset($params['enabled']) ? (bool)$params['enabled'] : false;

    // 2. Call DAL
    $automations = SUPER_Automation_DAL::get_all_automations($enabled_only);

    // 3. Return response
    return rest_ensure_response($automations);
}

public function create_automation( $request ) {
    // 1. Get JSON body
    $params = $request->get_json_params();

    // 2. Build data array
    $automation_data = array(
        'name' => $params['name'] ?? '',
        // ...
    );

    // 3. Call DAL
    $automation_id = SUPER_Automation_DAL::create_automation($automation_data);

    // 4. Return error or success
    if (is_wp_error($automation_id)) {
        return $automation_id; // WP_Error auto-converts to error response
    }

    // 5. Fetch and return created item
    $automation = SUPER_Automation_DAL::get_automation($automation_id);
    return rest_ensure_response($automation);
}
```

**For Themes REST Controller:**
Will need endpoints:
- `GET /super-forms/v1/themes` - List all themes (with filters)
- `GET /super-forms/v1/themes/{id}` - Get single theme
- `POST /super-forms/v1/themes` - Create custom theme
- `PUT /super-forms/v1/themes/{id}` - Update theme
- `DELETE /super-forms/v1/themes/{id}` - Delete custom theme
- `POST /super-forms/v1/themes/{id}/apply` - Apply theme to form (updates form settings)

---

### Current Form Builder Toolbar Structure

Located at `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/TabBar.tsx` with schema-driven tabs from `/home/rens/super-forms/src/react/admin/schemas/tabs/index.ts`.

**Current Tabs (in order by position):**
1. **Builder** (position: 5) - Shows canvas, icon: Layout
2. **Emails** (position: 10) - Email templates, icon: Mail
3. **Settings** (position: 20) - Form config, icon: Settings
4. **Entries** (position: 30) - Submissions, icon: Database
5. **Automation** (position: 40) - Workflows, icon: Workflow
6. **Style** (position: 50) - **Already registered!** icon: PaintBucket
7. **Integrations** (position: 60) - External services, icon: Webhook

**Tab Registration Pattern:**
```typescript
export const StyleTab = registerTab({
  id: 'style',
  label: 'Style',
  icon: 'PaintBucket',
  position: 50,
  lazyLoad: false,
  description: 'Customize form appearance',
});
```

**IMPORTANT DISCOVERY:** The "Style" tab is **already registered** in the schema! However, there's no corresponding panel component wired up in FormBuilderV2.tsx yet.

**How Tabs Render:**
1. TabBar component queries `getTabsSorted()` from registry (TabBar.tsx line 55)
2. Tabs sorted by position field
3. Icon mapping in TabBar.tsx (lines 20-29) maps string names to Lucide components
4. Active tab state managed in FormBuilderV2 parent component
5. Tab click triggers `onTabChange()` callback

**Integration Point:**
In FormBuilderV2.tsx, need to add conditional rendering like:
```tsx
{activeTab === 'style' && (
  <ResizableBottomTray>
    {/* Style tab content will go here */}
  </ResizableBottomTray>
)}
```

Similar pattern exists for emails tab (line 49 of FormBuilderV2.tsx - lazy loaded).

**For Themes Tab:**
Need to register a NEW tab:
```typescript
export const ThemesTab = registerTab({
  id: 'themes',
  label: 'Themes',
  icon: 'Palette', // Need to add to iconMap in TabBar.tsx
  position: 51, // Right after Style tab
  lazyLoad: false,
  description: 'Apply and create themes',
});
```

---

### FloatingPanel Component Structure

Located at `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`.

**Current Architecture:**
- **Purpose**: Shows property editor when element is selected on canvas
- **Positioning**: Fixed position calculated from click coordinates, clamped to viewport (lines 77-92)
- **Dimensions**: 480px width, max 600px height, scrollable content area
- **Close behavior**: Click outside (lines 46-62), Escape key (lines 65-74)

**Component Structure:**
```tsx
<div className="fixed z-50 w-[480px] max-h-[600px]">
  {/* Header - Element name, delete, close buttons (lines 108-136) */}
  <div className="border-b bg-gray-50 rounded-t-lg">
    <h3>Element Name</h3>
    <button onClick={onDelete}>Delete</button>
    <button onClick={onClose}>Close</button>
  </div>

  {/* Content - Property controls (lines 138-160) */}
  <div className="flex-1 overflow-y-auto p-4">
    {hasSchema ? (
      <SchemaPropertyPanel /> {/* Schema-driven properties */}
    ) : (
      <div>Legacy fallback panels</div>
    )}
  </div>
</div>
```

**Property Rendering:**
- Checks if element type has registered schema: `isElementRegistered(element.type)` (line 42)
- If yes: Renders `SchemaPropertyPanel` with type-safe property editors
- If no: Falls back to legacy `GeneralProperties` and `ValidationProperties`

**Where to Add Styles Section:**
After the existing SchemaPropertyPanel content (around line 146), add a new section:

```tsx
{/* After existing properties */}
{hasSchema && (
  <div className="mt-6 pt-6 border-t border-gray-200">
    <h4 className="text-sm font-semibold mb-3 flex items-center gap-2">
      <Palette className="h-4 w-4" />
      Element Styles
    </h4>
    <NodeStyleSection
      elementId={element.id}
      nodeType="input" // or whatever node this element has
      onUnlink={handleUnlinkProperty}
      onLink={handleLinkProperty}
    />
  </div>
)}
```

**Props Available:**
- `element` - Full element object with id, type, properties, and **styleOverrides**
- `onPropertyChange` - Callback to update properties (can be used for style overrides too)

**Style Override Pattern:**
Element objects can have a `styleOverrides` property:
```typescript
element: {
  id: string;
  type: string;
  properties: Record<string, unknown>;
  styleOverrides?: Record<NodeType, Partial<StyleProperties>>; // NEW
}
```

When user clicks "unlink" on a style property, store override in element.styleOverrides[nodeType][property].

---

### ElementRenderer Current Implementation

Located at `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx`.

**Current State - DOES NOT USE STYLES YET:**

```tsx
export const ElementRenderer: React.FC<ElementRendererProps> = ({ element, updateElementProperty }) => {
  // HARDCODED common props (lines 33-42)
  const commonProps: CommonProps = {
    className: "form-input",
    disabled: true,
    style: {
      width: element.properties?.width === 'full' ? '100%' : `${element.properties?.width || 100}%`,
      margin: element.properties?.margin,
      backgroundColor: element.properties?.backgroundColor,
      borderStyle: element.properties?.borderStyle,
    }
  };

  // Switch statement to render element (lines 44-78)
  switch (element.type) {
    case 'text':
      return <TextInput element={element} commonProps={commonProps} />;
    // ...
  }
};
```

**Problems:**
1. No integration with style system
2. Style properties pulled directly from `element.properties` (old pattern)
3. No global vs override resolution
4. Hardcoded style object, not using CSS classes or resolved values

**What Needs to Change:**

```tsx
import { useResolvedStyle } from '../../hooks/useResolvedStyle';
import { applyStylesToReactStyle } from '../../../lib/styleUtils'; // NEW utility

export const ElementRenderer: React.FC<ElementRendererProps> = ({ element }) => {
  // Resolve styles for each node type the element contains
  const labelStyle = useResolvedStyle(element.id, 'label');
  const inputStyle = useResolvedStyle(element.id, 'input');
  const errorStyle = useResolvedStyle(element.id, 'error');

  // Convert StyleProperties to React inline styles
  const inputReactStyle = applyStylesToReactStyle(inputStyle);
  const labelReactStyle = applyStylesToReactStyle(labelStyle);

  switch (element.type) {
    case 'text':
      return (
        <div>
          <label style={labelReactStyle}>{element.properties?.label}</label>
          <input style={inputReactStyle} disabled />
        </div>
      );
    // ...
  }
};
```

**Helper Utility Needed (applyStylesToReactStyle):**
Converts StyleProperties format to React CSSProperties:
- `fontSize: 14` → `fontSize: '14px'`
- `margin: {top: 10, right: 0, ...}` → `margin: '10px 0 0 0'`
- `borderColor: '#dc2626'` → `borderColor: '#dc2626'`

This ensures live preview updates when global styles change or element overrides are applied.

---

### Style System Hooks Available

Located at `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/hooks/`.

**1. useGlobalStyles.ts - Subscribe to Global Style Changes**

```typescript
// Get all global styles (re-renders on any change)
const globalStyles = useGlobalStyles();
// Returns: Record<NodeType, Partial<StyleProperties>>

// Get global style for one node type
const inputGlobalStyle = useGlobalNodeStyle('input');
// Returns: Partial<StyleProperties>

// Get + set a specific property
const [fontSize, setFontSize] = useGlobalStyleProperty('label', 'fontSize');
// Returns: [number | undefined, (value: number) => void]

// Track version for memoization
const version = useStyleRegistryVersion();
// Returns: number (increments on each change)
```

Uses `useSyncExternalStore` to subscribe to styleRegistry changes - efficient React 18 pattern.

**2. useResolvedStyle.ts - Merge Global + Overrides**

```typescript
// Get final computed style for an element's node
const resolvedStyle = useResolvedStyle(elementId, 'input');
// Returns: Partial<StyleProperties> (global merged with element overrides)

// Check if property is overridden
const isOverridden = useIsPropertyOverridden(elementId, 'input', 'fontSize');
// Returns: boolean

// Get override value (undefined if linked to global)
const overrideValue = useStyleOverride(elementId, 'input', 'fontSize');
// Returns: number | undefined

// Get both global and resolved (for showing both in UI)
const { globalValue, resolvedValue, isOverridden } = usePropertyValues(
  elementId,
  'input',
  'fontSize'
);
```

**How They Work Together:**
1. `useGlobalStyles()` subscribes to styleRegistry changes
2. `useResolvedStyle()` combines global + element.styleOverrides
3. When global changes → all elements re-render with new styles
4. When element override set → only that element updates

**For ElementRenderer:**
Use `useResolvedStyle(element.id, nodeType)` for each node the element contains (label, input, error, etc.).

**For FloatingPanel Styles Section:**
Use `usePropertyValues(elementId, nodeType, property)` to show:
- Global value (grayed out text)
- Current value (editable)
- Link/unlink button state

---

### Style Registry and Presets

Located at `/home/rens/super-forms/src/react/admin/schemas/styles/`.

**Core Registry (registry.ts):**
Singleton that manages global styles in memory:
- `styleRegistry.getGlobalStyle(nodeType)` - Get full style object
- `styleRegistry.setGlobalProperty(nodeType, property, value)` - Update one property
- `styleRegistry.resolveStyle(nodeType, overrides)` - Merge global + overrides
- `styleRegistry.exportStyles()` - Serialize to JSON string
- `styleRegistry.importStyles(json)` - Load from JSON
- `styleRegistry.subscribe(callback)` - React integration

**Preset Structure (presets/light.ts):**
```typescript
export const LIGHT_PRESET: Record<NodeType, Partial<StyleProperties>> = {
  label: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontWeight: '500',
    lineHeight: 1.4,
    color: '#1f2937',
    margin: { top: 0, right: 0, bottom: 4, left: 0 },
  },
  input: {
    fontSize: 14,
    padding: { top: 10, right: 14, bottom: 10, left: 14 },
    border: { top: 1, right: 1, bottom: 1, left: 1 },
    borderStyle: 'solid',
    borderColor: '#d1d5db',
    borderRadius: 6,
    backgroundColor: '#ffffff',
    width: '100%',
    minHeight: 42,
  },
  // ... all 13 node types defined
};
```

**Available Presets:**
- `LIGHT_PRESET` - Clean professional look
- `DARK_PRESET` - Dark mode theme
- Helper: `applyPreset(presetId)` - Applies preset to registry

**For Themes:**
- System themes (Light, Dark) will use these presets as base
- Custom themes stored in database, loaded into registry on form load
- `generateTheme()` MCP action will create new theme objects programmatically

---

### GlobalStylesPanel UI Component

Located at `/home/rens/super-forms/src/react/admin/components/settings/GlobalStylesPanel.tsx`.

**Current Implementation:**
A fully functional React component that edits global styles via styleRegistry. This is the reference implementation for the Styles tab.

**Structure:**
1. **Header** (lines 86-128): Title, Export/Import/Reset buttons
2. **Preset Selector** (lines 133-154): Dropdown to apply Light/Dark presets quickly
3. **Two-Column Layout** (lines 157-407):
   - Left: Node type selector (label, input, button, etc.)
   - Right: Style controls for selected node (typography, spacing, border, background)
4. **Reset Dialog** (lines 411-435): Confirmation modal

**How It Works:**
```typescript
const [activeNode, setActiveNode] = useState<NodeType>('label');
const globalStyles = useGlobalStyles(); // Subscribe to changes
const currentStyle = globalStyles[activeNode] ?? {};

const handlePropertyChange = (property, value) => {
  styleRegistry.setGlobalProperty(activeNode, property, value);
  // React re-renders automatically via subscription
};
```

**Style Controls Used:**
- `SpacingControl` - Visual box model editor for margin/padding/border
- `ColorControl` - Color picker
- Native inputs for fontSize, fontWeight, lineHeight, etc.

**Capabilities System:**
Uses `NODE_STYLE_CAPABILITIES[nodeType]` to determine which controls to show:
```typescript
const capabilities = NODE_STYLE_CAPABILITIES[activeNode];

{capabilities.fontSize && (
  <input type="number" ... />
)}
{capabilities.color && (
  <ColorControl ... />
)}
```

This ensures you don't show "fontSize" control for a divider node (which doesn't support text).

**For Styles Tab:**
This entire component can be reused! Just render it inside the tab panel:
```tsx
{activeTab === 'style' && (
  <div className="p-6">
    <GlobalStylesPanel />
  </div>
)}
```

---

### Integration Summary - What Connects to What

**Data Flow:**

```
DATABASE (wp_superforms_themes)
    ↓
PHP DAL (SUPER_Theme_DAL)
    ↓
REST API (SUPER_Theme_REST_Controller)
    ↓ (wp.apiFetch)
React UI (ThemesTab)
    ↓ (applyTheme action)
styleRegistry.fromJSON(theme.styles)
    ↓ (subscription)
useGlobalStyles() hook
    ↓
GlobalStylesPanel / ElementRenderer
    ↓
Live preview updates
```

**Form Settings Storage:**
```typescript
formSettings: {
  currentThemeId: 123, // Reference to wp_superforms_themes.id
  globalStyles: {...}, // Snapshot for this form (merged from theme + modifications)
}
```

**Theme Application Flow:**
1. User clicks "Apply" on a theme card
2. `POST /super-forms/v1/themes/{id}/apply` endpoint called
3. Backend fetches theme.styles JSON
4. Updates form settings with theme ID + styles snapshot
5. Frontend receives updated form data
6. Calls `styleRegistry.fromJSON(formSettings.globalStyles)`
7. All components re-render with new styles

**Save Custom Theme Flow:**
1. User clicks "Save Current as Theme"
2. Dialog opens to enter name/description
3. Frontend calls `styleRegistry.exportStyles()` to get current state
4. `POST /super-forms/v1/themes` with name + styles JSON
5. Backend creates row in wp_superforms_themes with user_id
6. Theme appears in gallery for this user

**MCP Integration Points:**
- `listThemes` → `GET /super-forms/v1/themes`
- `applyTheme` → `POST /super-forms/v1/themes/{id}/apply`
- `createTheme` → `POST /super-forms/v1/themes`
- `generateTheme` → Client-side color theory logic + `createTheme`
- `setGlobalProperty` → Direct styleRegistry calls (no API needed)

## Subtasks

### 1. Database Schema & DAL
File: `01-database-schema-dal.md`
- Create `wp_superforms_themes` table schema with `user_id`, `is_stub` columns
- Migration class following existing patterns
- `SUPER_Theme_DAL` class for CRUD
- Seed system themes (Light, Dark) on activation
- Seed stub themes with `is_stub=1`

### 2. REST API for Themes
File: `02-rest-api-themes.md`
- `SUPER_Theme_REST_Controller` class
- Endpoints: GET /themes, GET /themes/{id}, POST /themes, PUT /themes/{id}, DELETE /themes/{id}
- POST /themes/{id}/apply (applies theme to form)
- listThemes returns stubs with `is_stub` flag for UI badge
- Permission checks, validation

### 3. MCP Theme Actions
File: `03-mcp-theme-actions.md`
- Extend styleActionSchema.ts with theme actions
- listThemes, getTheme, applyTheme, createTheme, deleteTheme
- generateTheme with prompt/baseColor/density/cornerStyle options
- Handler implementation in styleActions.ts
- Integration with REST API via wp.apiFetch
- Document AI example prompts as test cases

### 4. Themes Tab UI
File: `04-themes-tab-ui.md`
- ThemesTab component
- ThemeGallery with ThemeCard components
- Theme preview using `previewColors` swatches
- "Coming Soon" badge for stubs
- Apply theme flow
- CreateThemeDialog (save current styles)
- Delete custom theme confirmation

### 5. Styles Tab Integration
File: `05-styles-tab-integration.md`
- Wire GlobalStylesPanel into Form Builder toolbar
- Add "Styles" tab to tab bar
- Connect to REST API for persistence
- Ensure changes persist across sessions

### 6. FloatingPanel Styles
File: `06-floating-panel-styles.md`
- Add Styles section to FloatingPanel
- NodeStyleSection component for each node type
- Link/unlink UI per property
- Visual indication of overridden vs global

### 7. ElementRenderer Integration
File: `07-element-renderer.md`
- Update ElementRenderer to use useResolvedStyle
- Apply resolved styles to all rendered elements
- Ensure live preview updates
- Performance optimization (memoization)

## Architecture

### Database Schema

```sql
CREATE TABLE wp_superforms_themes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT,
  category VARCHAR(50),          -- 'light', 'dark', 'minimal', 'corporate', etc.
  styles LONGTEXT NOT NULL,      -- JSON (full theme object)
  preview_colors TEXT,           -- JSON array of hex swatches for UI cards
  is_system TINYINT(1) DEFAULT 0,
  is_stub TINYINT(1) DEFAULT 0,  -- Coming soon themes
  user_id BIGINT UNSIGNED,       -- Owner (NULL for system themes)
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY user_id (user_id),
  KEY category (category)
);
```

### Theme JSON Structure (Single Source of Truth)

```typescript
interface ThemeDefinition {
  meta: {
    id: string;
    name: string;
    description: string;
    category: 'light' | 'dark' | 'minimal' | 'corporate' | 'playful' | 'highContrast';
    previewColors: string[];  // Swatches for UI cards
  };

  palette: {
    primary: string;
    primaryAccent: string;
    primaryContrast: string;
    secondary: string;
    secondaryAccent: string;
    background: string;
    surface: string;
    surfaceAlt: string;
    border: string;
    divider: string;
    text: string;
    textMuted: string;
    success: string;
    warning: string;
    error: string;
    info: string;
  };

  typography: {
    fontFamily: string;
    fontFamilyHeading: string;
    fontSizes: { xs: number; sm: number; base: number; lg: number; xl: number; '2xl': number };
    fontWeights: { normal: string; medium: string; semibold: string; bold: string };
    lineHeights: { tight: number; normal: number; relaxed: number };
  };

  spacing: {
    scale: { 0: number; 1: number; 2: number; 3: number; 4: number; 5: number };
    inputHeight: number;
    buttonHeight: number;
    fieldGap: number;
    sectionPadding: number;
  };

  borders: {
    radiusScale: { none: number; sm: number; md: number; lg: number; full: number };
    defaultRadius: 'none' | 'sm' | 'md' | 'lg' | 'full';
  };

  // Node-level styles (derived from palette/typography/spacing)
  nodes: Record<NodeType, Partial<StyleProperties>>;
}
```

### MCP Actions (Schema-First)

```typescript
// Theme actions extend existing StyleActionSchema
const ListThemesAction = z.object({
  action: z.literal('listThemes'),
  includeSystem: z.boolean().optional().default(true),
  includeStubs: z.boolean().optional().default(true),
  category: z.enum(['light', 'dark', 'minimal', 'corporate', 'playful', 'highContrast']).optional(),
});

const ApplyThemeAction = z.object({
  action: z.literal('applyTheme'),
  themeId: z.union([z.string(), z.number()]),
});

const CreateThemeAction = z.object({
  action: z.literal('createTheme'),
  name: z.string().min(1).max(100),
  description: z.string().max(500).optional(),
  category: z.enum(['light', 'dark', 'minimal', 'corporate', 'playful', 'highContrast']).optional(),
});

const DeleteThemeAction = z.object({
  action: z.literal('deleteTheme'),
  themeId: z.union([z.string(), z.number()]),
});

const GenerateThemeAction = z.object({
  action: z.literal('generateTheme'),
  name: z.string().min(1).max(100),
  prompt: z.string().max(500).optional(),           // "warm friendly", "corporate blue"
  baseColor: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),  // Brand color
  density: z.enum(['compact', 'comfortable', 'spacious']).optional().default('comfortable'),
  cornerStyle: z.enum(['sharp', 'rounded', 'pill']).optional().default('rounded'),
  contrastPreference: z.enum(['high', 'standard', 'soft']).optional().default('standard'),
  save: z.boolean().optional().default(true),       // Persist to database?
});
```

### AI Example Prompts (Test Cases)

| User Says | MCP Action | Expected Result |
|-----------|------------|-----------------|
| "use a dark theme" | `applyTheme("dark")` | Dark theme applied |
| "change field border color to red" | `setGlobalProperty(input, borderColor, #dc2626)` | All input borders red |
| "make the fields larger" | `setGlobalProperty(input, fontSize, 16)` + padding adjustments | Bigger inputs |
| "more spacing between elements" | `setGlobalProperty(fieldContainer, margin, {bottom: 28})` | Larger gaps |
| "make buttons more rounded" | `setGlobalProperty(button, borderRadius, 24)` | Pill buttons |
| "increase label font size" | `setGlobalProperty(label, fontSize, 16)` | Larger labels |
| "create a theme based on #FF5722" | `generateTheme({name: "Orange Brand", baseColor: "#FF5722"})` | New theme with orange palette |
| "design a warm friendly theme" | `generateTheme({name: "Warm", prompt: "warm friendly"})` | Warm color theme |
| "make a compact corporate theme" | `generateTheme({name: "Corporate", prompt: "corporate", density: "compact"})` | Dense professional theme |
| "create high contrast accessible theme" | `generateTheme({name: "Accessible", contrastPreference: "high"})` | WCAG-friendly theme |

### Theme Stubs (Expandable)

| Theme | Category | Status | Description |
|-------|----------|--------|-------------|
| Light | light | ✓ Implemented | Clean, professional with subtle grays |
| Dark | dark | ✓ Implemented | Modern dark mode with good contrast |
| Minimal | minimal | Stub | Borderless, maximum whitespace, understated |
| Classic | light | Stub | Traditional form styling, familiar |
| Modern | light | Stub | Rounded corners, subtle shadows, contemporary |
| Corporate | light | Stub | Professional, trust-inspiring, blue tones |
| Playful | light | Stub | Colorful, friendly, very rounded |
| High Contrast | highContrast | Stub | Accessibility-focused, WCAG AAA |

### UI Tab Structure

```
Form Builder Toolbar
├── [Builder]     - Canvas + Element Tray
├── [Styles]      - GlobalStylesPanel (edit global styles)
├── [Themes]      - ThemeGallery (pick/save/generate themes)
├── [Templates]   - Future: form starters
└── [Settings]    - Form configuration
```

## Dependencies

- Completed: `n-implement-global-individual-styles` (style system infrastructure)
- Existing: Database migration patterns from automations system
- Existing: REST controller patterns from forms/automations

## User Notes
<!-- Any specific notes or requirements from the developer -->
- Themes stored in database with user ownership, NOT form settings (persist independently)
- Custom themes owned by `user_id` - survive form deletion
- Stub themes have `is_stub=1` flag - UI shows "Coming Soon" badge
- Templates tab is future work (not in scope)
- Stay on current branch: feature/h-implement-triggers-actions-extensibility
- generateTheme uses color theory to derive palette from baseColor
- Export/Import format matches ThemeDefinition JSON structure

## Work Log
<!-- Updated as work progresses -->
- [2025-12-06] Task created with comprehensive theme architecture
- [2025-12-06] Subtask 01: Created wp_superforms_themes table, SUPER_Theme_DAL class, system theme seeding
- [2025-12-06] Subtask 02: Created SUPER_Theme_REST_Controller with full CRUD + apply endpoints
- [2025-12-06] Subtask 03: Extended MCP with theme actions, created themeGenerator.ts for color theory
- [2025-12-06] Subtask 04: Built ThemesTab UI with gallery, cards, CreateThemeDialog, useThemes hook
- [2025-12-06] Subtask 05: Wired GlobalStylesPanel into Style tab, fixed React #185 infinite re-render bug
- [2025-12-06] Fixed double scrollbar issues in ThemesTab, StyleTabContent, EntriesTabContent, IntegrationsTabContent
- [2025-12-06] Subtask 06: Created ElementStylesSection and NodeStyleEditor for FloatingPanel
- [2025-12-06] Subtask 07: Created styleUtils.ts, updated ElementRenderer and element components to use resolved styles
- [2025-12-06] Code review passed: 0 critical issues, 0 security vulnerabilities, production-ready
- [2025-12-06] Task completed - all 7 subtasks implemented
