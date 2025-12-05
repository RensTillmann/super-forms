---
name: h-research-form-builder-v2-architecture
branch: feature/h-implement-triggers-actions-extensibility
status: in-progress
created: 2025-12-03
---

# Schema-First Form Builder Architecture

## Problem/Goal

Create a **Schema-First Architecture** for Form Builder V2 where:

1. **Single Source of Truth** - One schema definition drives React UI, PHP validation, and MCP tools
2. **MCP Parity** - AI can do EVERYTHING a user can do in the builder
3. **WYSIWYG Guarantee** - Builder preview = frontend output (Tailwind CSS, not inline styles)
4. **Unified Operations** - All changes go through JSON Patch operations for undo/redo/versioning

### Current Problems (from audit)

| Issue | Impact |
|-------|--------|
| Schemas scattered across React, PHP, MCP | MCP can only do ~40% of user capabilities |
| MCP has 15 hardcoded element types | Missing 11+ element types, all containers |
| No programmatic property definitions | Can't generate UI or validate dynamically |
| No theme/styling schema for forms | Only email builder has style types |
| Container elements not in MCP | Can't create layouts (columns, tabs, steps) |
| Conditional logic not exposed | Can't set visibility rules |
| Loose typing (`Record<string, any>`) | No validation, no autocomplete |

### Key Requirements

1. **Tailwind CSS Rendering** - Form elements rendered with Tailwind classes, not inline styles
   - Theme tokens → CSS variables → Tailwind classes
   - Only user custom overrides use inline styles
   - Schema defines allowed values that map to Tailwind classes

2. **Settings Clipboard System** - Copy/paste settings between elements
   - Right-click element → Copy settings (all, category, or specific properties)
   - Right-click another element → Paste (all or select which)
   - Stored in memory clipboard (not system clipboard)
   - MCP must also support this operation

3. **Layout Operations** - Create and modify layouts
   - Create column layouts (1/2+1/2, 1/3+2/3, 1/4+1/4+1/2, etc.)
   - Wrap elements in containers (columns, sections, tabs, accordion)
   - Reposition/reorder elements
   - Unwrap containers (flatten)

4. **Full MCP Capability** - Everything a user can do:
   - Add/remove/update/move any element type (26+)
   - Modify any element property (general, validation, styling, advanced)
   - Create/modify layouts and containers
   - Copy/paste settings between elements
   - Update form settings, theme, translations
   - Manage automations (create, configure nodes, connect)
   - Set conditional logic (show/hide rules)

## Success Criteria

### Architecture Deliverables
- [x] Schema format specification document (`docs/architecture/form-builder-schema-spec.md`)
- [x] Element type audit with property categories
- [ ] REST API schema endpoints design
- [ ] MCP capability model (dynamic tool generation)

### Schema Coverage
- [ ] All 26+ element types with full property schemas
- [ ] Container elements with nesting rules
- [ ] Form settings schema (submission, spam, access)
- [ ] Theme schema with Tailwind mappings
- [ ] Automation node schemas (triggers, actions, conditions, control)
- [ ] Conditional logic schema
- [ ] Translation structure schema

### Operations
- [ ] Settings clipboard operations (copy/paste by category/property)
- [ ] Layout operations (create columns, change ratios, wrap/unwrap)
- [ ] Element CRUD with full property support
- [ ] Bulk operations (select multiple, apply settings)

### Integration
- [ ] Schema → React property panels (UI generated from schema)
- [ ] Schema → PHP validation (build step generates PHP arrays)
- [ ] Schema → MCP tools (dynamic, always in sync)
- [ ] Tailwind class mappings for all style properties

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    SCHEMA (Single Source of Truth)              │
│                                                                 │
│  /src/schemas/                                                  │
│  ├── elements/          # All 26+ element type schemas          │
│  ├── settings/          # Form settings, theme, submission      │
│  ├── automations/       # Trigger, action, condition schemas    │
│  ├── operations/        # Clipboard, layout operations          │
│  └── index.ts           # Aggregated export                     │
└─────────────────────────────────────────────────────────────────┘
                              │
         ┌────────────────────┼────────────────────┐
         ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   React Admin   │  │   PHP Backend   │  │   MCP Server    │
│                 │  │                 │  │                 │
│ Property panels │  │ Validation      │  │ Dynamic tools   │
│ generated from  │  │ from schema     │  │ from schema     │
│ schema          │  │ (build step)    │  │ (runtime fetch) │
│                 │  │                 │  │                 │
│ Tailwind CSS    │  │ REST endpoints  │  │ Full capability │
│ classes from    │  │ /schema/*       │  │ parity with UI  │
│ theme tokens    │  │                 │  │                 │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

## Related Documentation

1. **Phase 27: Operations-Based Form Editing**
   - File: `sessions/tasks/h-implement-triggers-actions-extensibility/27-implement-operations-versioning-system.md`
   - JSON Patch (RFC 6902), undo/redo, version control

2. **Phase 28: AI Assistant Token System**
   - File: `sessions/tasks/h-implement-triggers-actions-extensibility/28-ai-assistant-token-system.md`
   - Schema domains (elements, nodes, settings, theme, translations)

3. **Schema Specification**
   - File: `docs/architecture/form-builder-schema-spec.md`
   - Comprehensive schema format, property types, operations

## Context Manifest

### Key Files
- **Schema Spec**: `docs/architecture/form-builder-schema-spec.md` (comprehensive)
- **Form Builder V2**: `src/react/admin/apps/form-builder-v2/`
- **Current Stores**: `src/react/admin/apps/form-builder-v2/store/`
- **Automations**: `src/react/admin/components/form-builder/automations/`
- **MCP Server**: `.mcp/super-forms-server.json`
- **Phase 27 Doc**: `sessions/tasks/h-implement-triggers-actions-extensibility/27-implement-operations-versioning-system.md`
- **Phase 28 Doc**: `sessions/tasks/h-implement-triggers-actions-extensibility/28-ai-assistant-token-system.md`

### Current State (from audit)
- V2 UI exists but uses hardcoded state, no REST integration
- MCP server has 15 element types (missing 11+ including all containers)
- No programmatic property schemas
- No theme/styling schema for forms
- Automations well-structured in PHP registry

## User Notes
- Stay on current branch: `feature/h-implement-triggers-actions-extensibility`
- Tailwind CSS for form rendering (not inline styles)
- Settings clipboard system for copy/paste between elements
- MCP must have full capability parity with user

## Context Manifest: FloatingPropertiesPanel → Schema-Driven Refactor

### How Element Interaction Currently Works

The Form Builder V2 has THREE distinct interaction modes for elements:

#### 1. Single Click → Opens Properties Panel Inline (lines 2466-2504)

**Flow**:
- User clicks element → `handleSelectElement(elementId, event)` called
- If NOT multi-select (Ctrl/Cmd key):
  - Sets `selectedElements` state
  - Updates breadcrumb
  - **Automatically opens FloatingPropertiesPanel** positioned below element

**Code** (lines 2487-2502):
```typescript
if (event && !isMultiSelect) {
  const element = items[elementId];
  if (element) {
    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();

    setFloatingPanel({
      element: element,
      position: {
        x: rect.left,           // Align left edge with element
        y: rect.bottom + 10     // Position 10px below element
      },
      isVisible: true
    });
  }
}
```

**Key insight**: Panel opens BELOW the element, aligned to its left edge, with 10px gap. This is NOT a centered floating dialog—it's more like a dropdown/popover anchored to the element.

#### 2. Right Click → Shows Context Menu (lines 2551-2555)

**Flow**:
- User right-clicks element → `handleContextMenu(e, elementId)` called
- Prevents default browser context menu
- Selects the element
- Shows custom context menu at cursor position

**Code**:
```typescript
const handleContextMenu = useCallback((e: React.MouseEvent, elementId: string) => {
  e.preventDefault();
  handleSelectElement(elementId);
  setContextMenu({ x: e.clientX, y: e.clientY });
}, [handleSelectElement]);
```

**Context menu actions** (line 2562):
- Edit → Opens FloatingPropertiesPanel at context menu position
- Duplicate, Copy, Paste, Delete

#### 3. Click + Hold + Drag → Drag & Drop (lines 2373-2388)

**Flow**:
- User clicks and holds → `handleDragStart(e, element)` called
- Element becomes draggable
- User can reorder or move element into columns/containers
- **Properties panel does NOT open during drag**

**Code**:
```typescript
const handleDragStart = useCallback((e: React.DragEvent, element: any, isNew = false) => {
  setIsDragging(true);
  setDraggedElement({
    type: element.type,
    label: element.label,
    icon: element.icon?.name || element.type,
    keywords: element.keywords,
    isNew
  });
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', JSON.stringify(serializableElement));
}, []);
```

**Key insight**: The browser's native drag events handle the distinction between click and drag. No custom timer/threshold logic needed.

### FloatingPropertiesPanel State Management

**State variable** (line 1918):
```typescript
const [floatingPanel, setFloatingPanel] = useState<{
  element: any;
  position: { x: number; y: number }
} | null>(null);
```

**Opening scenarios**:
1. **Single click element** → Position below element (line 2494)
2. **Context menu "Edit"** → Position at cursor (line 2563)
3. **Form wrapper click** → Position at viewport center (lines 3715, 3946)

**Closing scenarios**:
1. ESC key (line 2202)
2. Click outside (lines 263-272 in FloatingPropertiesPanel component)
3. Delete element (line 2522)
4. onClose button click (line 4290)

### Current FloatingPropertiesPanel Positioning

**Viewport-based clamping** (lines 282-283):
```typescript
style={{
  position: 'fixed',
  left: Math.min(position.x, window.innerWidth - 400),
  top: Math.min(position.y, window.innerHeight - 500),
  zIndex: 1000,
}}
```

**Problems with current approach**:
- Hard-coded width (400px) and height (500px) for clamping
- Doesn't account for actual panel content height
- Always fixed position (not anchored to element)
- Panel width is 320px (line 2374 CSS) but clamps at 400px—inconsistent

### Required New Behavior (Based on User Feedback)

#### Positioning Strategy

**Panel should**:
- Open **directly below the clicked element** (already does this on line 2498)
- Minimum width: **600px** (not 320px)
- Maximum width: **100% viewport width** (for mobile)
- Position: Anchored to element's left edge, 10px below bottom edge

**Responsive behavior**:
```typescript
// Desired positioning logic
const panelWidth = Math.min(600, window.innerWidth); // Min 600px, max viewport width
const position = {
  x: Math.max(0, Math.min(rect.left, window.innerWidth - panelWidth)), // Keep on screen
  y: rect.bottom + 10 // 10px below element
};

// If panel would overflow bottom of viewport, consider flipping above element
if (position.y + estimatedPanelHeight > window.innerHeight) {
  position.y = rect.top - estimatedPanelHeight - 10; // Position above instead
}
```

#### Interaction Flow Clarification

| User Action | Result |
|-------------|--------|
| **Single click** element | ✅ Opens properties panel below element |
| **Right click** element | ✅ Shows context menu at cursor |
| **Click + hold + drag** | ✅ Enables drag & drop, NO panel opens |
| **ESC key** | ✅ Closes panel |
| **Click outside panel** | ✅ Closes panel |
| **Delete button in panel** | ✅ Deletes element, closes panel |

### FloatingPropertiesPanel Component Structure (lines 253-650)

**Current implementation**:
```typescript
const FloatingPropertiesPanel: React.FC<{
  element: any;
  position: { x: number; y: number };
  onClose: () => void;
  onUpdate: (property: string, value: any) => void;
  onDelete: () => void;
}> = ({ element, position, onClose, onUpdate, onDelete }) => {
  const panelRef = useRef<HTMLDivElement>(null);
  const [activeTab, setActiveTab] = useState('general');

  // Click-outside-to-close
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        onClose();
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [onClose]);

  return (
    <div
      ref={panelRef}
      className="floating-properties-panel"
      style={{ position: 'fixed', left: position.x, top: position.y, zIndex: 1000 }}
    >
      {/* Header with icon, title, close button */}
      {/* Tabs: General, Validation, Styling, Advanced */}
      {/* Content with PropertyPanelRegistry */}
      {/* Footer with Delete button */}
    </div>
  );
};
```

**Chrome elements**:
- ✅ Header with element icon, label, close button (lines 287-295)
- ✅ Tab navigation (4 tabs) (lines 297-324)
- ✅ Scrollable content area (line 326)
- ✅ Delete button in footer (around line 530)
- ✅ Click-outside detection (lines 263-272)
- ✅ Fixed positioning with z-index 1000

### SchemaPropertyPanel Capabilities

Located at `/src/react/admin/apps/form-builder-v2/components/property-panels/schema/SchemaPropertyPanel.tsx`

**What it provides**:
```typescript
interface SchemaPropertyPanelProps {
  elementType: string;
  properties: Record<string, unknown>;
  onPropertyChange: (propertyName: string, value: unknown) => void;
  categories?: PropertyCategory[];  // Optional: filter visible categories
}
```

**Features**:
- ✅ Schema-driven property rendering (automatic based on elementType)
- ✅ Category tabs (General, Validation, Appearance, Advanced, Conditions)
- ✅ Conditional visibility (showWhen with 10+ operators)
- ✅ Tailwind CSS styling
- ✅ Debug panel in dev mode

**Missing** (needs wrapper):
- ❌ No header/title/icon
- ❌ No close button
- ❌ No delete button
- ❌ No positioning logic
- ❌ No click-outside-to-close
- ❌ No fixed positioning

**Current rendering** (line 96):
```tsx
<div className="schema-property-panel">
  {/* Category tabs if multiple categories */}
  <div className="flex border-b border-gray-200 mb-4">
    {/* Tailwind tab buttons */}
  </div>

  {/* Properties space */}
  <div className="space-y-4">
    {Object.entries(currentCategoryProps).map(([propName, propSchema]) => (
      <PropertyRenderer
        key={propName}
        name={propName}
        schema={propSchema}
        value={properties[propName]}
        onChange={(value) => onPropertyChange(propName, value)}
      />
    ))}
  </div>
</div>
```

### CSS Styling Analysis

**Current FloatingPropertiesPanel CSS** (`form-builder.css` lines 2373-2472):
```css
.floating-properties-panel {
  width: 320px;                    /* ❌ Need 600px */
  background: white;
  border: 1px solid var(--color-border-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  z-index: 1000;
  animation: fadeInUp 0.2s ease-out;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.panel-header { /* ... */ }
.panel-tabs { /* ... */ }
.panel-content {
  flex: 1;
  overflow-y: auto;
  padding: var(--space-4);
}
```

**Mobile responsive** (line 2658):
```css
@media (max-width: 768px) {
  .floating-properties-panel {
    width: calc(100vw - 32px);
    max-width: 320px;              /* ❌ Should be 600px or 100% */
    left: 16px !important;
    right: 16px;
  }
}
```

**SchemaPropertyPanel CSS**: Uses Tailwind classes, no custom CSS. Compatible with custom CSS wrapper.

### Refactor Strategy: Wrapper Component Approach

**Recommended**: Create new `FloatingPanel` wrapper with Tailwind, embed `SchemaPropertyPanel`.

#### New Component Structure

```typescript
// /src/react/admin/apps/form-builder-v2/components/FloatingPanel.tsx

interface FloatingPanelProps {
  element: any;
  position: { x: number; y: number };
  onClose: () => void;
  onDelete?: () => void;
  onUpdate: (property: string, value: any) => void;
}

export function FloatingPanel({ element, position, onClose, onDelete, onUpdate }: FloatingPanelProps) {
  const panelRef = useRef<HTMLDivElement>(null);

  // Calculate responsive width
  const panelWidth = Math.min(600, window.innerWidth * 0.95); // Min 600px, max 95% viewport

  // Clamp position to viewport
  const clampedPosition = {
    x: Math.max(16, Math.min(position.x, window.innerWidth - panelWidth - 16)),
    y: Math.max(16, position.y)
  };

  // Click-outside-to-close
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        onClose();
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [onClose]);

  return (
    <div
      ref={panelRef}
      className="fixed z-[1000] bg-white border border-gray-200 rounded-lg shadow-lg flex flex-col max-h-[80vh] animate-fadeInUp"
      style={{
        left: clampedPosition.x,
        top: clampedPosition.y,
        width: panelWidth,
        minWidth: Math.min(600, window.innerWidth * 0.95) // Enforce 600px min on desktop
      }}
    >
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
        <h3 className="text-sm font-semibold text-gray-900 flex items-center gap-2">
          <element.icon size={16} />
          {element.label} Properties
        </h3>
        <button
          onClick={onClose}
          className="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors"
        >
          <X size={16} />
        </button>
      </div>

      {/* Schema-driven content */}
      <div className="flex-1 overflow-y-auto p-4">
        <SchemaPropertyPanel
          elementType={element.type}
          properties={element.properties || {}}
          onPropertyChange={onUpdate}
        />
      </div>

      {/* Footer with delete button */}
      {onDelete && (
        <div className="p-4 border-t border-gray-200 bg-gray-50">
          <button
            onClick={onDelete}
            className="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors"
          >
            <Trash2 size={16} />
            Delete Element
          </button>
        </div>
      )}
    </div>
  );
}
```

#### Key Design Decisions

**Width**:
- Desktop: 600px minimum
- Mobile: 95% of viewport width
- Never exceed viewport width

**Positioning**:
- Anchored below element (x: rect.left, y: rect.bottom + 10)
- Clamped to viewport with 16px padding
- Could add "flip above" logic if panel overflows bottom

**Styling approach**:
- **Full Tailwind CSS** (no custom CSS classes)
- Aligns with project goal: "WYSIWYG Guarantee - Tailwind CSS, not inline styles"
- Uses design tokens via Tailwind (bg-gray-50, border-gray-200, etc.)

**Chrome separation**:
- FloatingPanel = wrapper (header, positioning, chrome, delete)
- SchemaPropertyPanel = content (tabs, properties, schema rendering)
- Clean separation of concerns

### Integration Points in FormBuilderV2.tsx

**Current rendering** (lines 4286-4293):
```typescript
{floatingPanel && floatingPanel.element && (
  <FloatingPropertiesPanel
    element={floatingPanel.element}
    position={floatingPanel.position}
    onClose={() => setFloatingPanel(null)}
    onUpdate={(property, value) => updateElementProperty(floatingPanel.element.id, property, value)}
    onDelete={() => handleDeleteElement(floatingPanel.element.id)}
  />
)}
```

**New rendering** (same location):
```typescript
{floatingPanel && floatingPanel.element && (
  <FloatingPanel
    element={floatingPanel.element}
    position={floatingPanel.position}
    onClose={() => setFloatingPanel(null)}
    onUpdate={(property, value) => updateElementProperty(floatingPanel.element.id, property, value)}
    onDelete={() => handleDeleteElement(floatingPanel.element.id)}
  />
)}
```

**State management** (line 1918): ✅ No changes needed

**Event handlers**: ✅ No changes needed
- handleSelectElement (line 2466) - already opens panel below element
- handleContextMenu (line 2551) - already opens panel at cursor
- ESC key handler (line 2202) - already closes panel
- handleDeleteElement (line 2522) - already closes panel

### PropertyPanelRegistry Integration

The PropertyPanelRegistry (line 379 in old FloatingPropertiesPanel) is **already integrated** into SchemaPropertyPanel:

**Current flow** (`PropertyPanelRegistry.tsx` lines 112-119):
```typescript
export const PropertyPanelRegistry: React.FC<PropertyPanelRegistryProps> = ({
  element,
  onUpdate
}) => {
  // Check if this element type has a schema registered
  if (isElementRegistered(element.type)) {
    return (
      <SchemaPropertyPanel
        elementType={element.type}
        properties={element.properties || {}}
        onPropertyChange={onUpdate}
      />
    );
  }

  // Fallback to legacy hardcoded panels
  // ...
};
```

**Key insight**: PropertyPanelRegistry is a **transition component** that:
1. Checks if element has schema → Use SchemaPropertyPanel
2. No schema → Use legacy hardcoded panels

Once all element types have schemas, PropertyPanelRegistry becomes unnecessary. SchemaPropertyPanel is called directly.

### FormWrapperSettingsPanel Consideration

The FormWrapperSettingsPanel (lines 650-1000+, rendered lines 4297-4326) is **separate** with unique features:
- Draggable header (lines 652-675)
- Device-specific settings (desktop/tablet/mobile)
- Different property structure (not element properties)

**Recommendation**: Keep FormWrapperSettingsPanel as-is for now. It serves a different purpose (form-level settings vs element properties). Address in separate refactor.

### Migration Path

#### Phase 1: Create FloatingPanel Component
- [ ] Create `/src/react/admin/apps/form-builder-v2/components/FloatingPanel.tsx`
- [ ] Implement 600px min width, responsive behavior
- [ ] Add header, close button, delete button chrome
- [ ] Embed SchemaPropertyPanel for content
- [ ] Implement click-outside-to-close
- [ ] Add fadeInUp animation (Tailwind: `animate-fadeInUp`)

#### Phase 2: Update FormBuilderV2.tsx
- [ ] Import FloatingPanel
- [ ] Replace FloatingPropertiesPanel usage (line 4287)
- [ ] Test single click → panel opens below element
- [ ] Test right-click → context menu → Edit → panel opens at cursor
- [ ] Test ESC key → panel closes
- [ ] Test click outside → panel closes
- [ ] Test delete button → element removed, panel closes

#### Phase 3: Cleanup
- [ ] Remove FloatingPropertiesPanel component (lines 253-650)
- [ ] Remove `.floating-properties-panel` CSS (lines 2373-2472) if unused
- [ ] Update imports

#### Phase 4: Verify All Element Types
- [ ] Test each element type opens panel correctly
- [ ] Verify all properties render (schema completeness check)
- [ ] Test validation, styling, advanced tabs
- [ ] Mobile responsive testing (width adapts to viewport)

### CSS Animation Note

The original FloatingPropertiesPanel uses `animation: fadeInUp 0.2s ease-out` (line 2380).

**Tailwind equivalent**:
```typescript
// In tailwind.config.js, ensure fadeInUp animation exists
theme: {
  extend: {
    keyframes: {
      fadeInUp: {
        '0%': { opacity: 0, transform: 'translateY(10px)' },
        '100%': { opacity: 1, transform: 'translateY(0)' }
      }
    },
    animation: {
      fadeInUp: 'fadeInUp 0.2s ease-out'
    }
  }
}

// In component
className="animate-fadeInUp"
```

### Files to Create/Modify

**Create**:
- `/src/react/admin/apps/form-builder-v2/components/FloatingPanel.tsx` (new wrapper)

**Modify**:
- `/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` (line 4287, import statement)

**Delete** (after verification):
- Lines 253-650 in FormBuilderV2.tsx (FloatingPropertiesPanel component)
- Lines 2373-2472 in form-builder.css (optional, may still be used by FormWrapperSettingsPanel)

**Test locations**:
- Canvas element rendering (lines 3840-3870)
- Context menu actions (lines 2558-2590)
- Keyboard handlers (lines 2199-2205)

### Edge Cases & Considerations

1. **Panel overflow bottom**: Current code doesn't flip panel above element if it overflows viewport bottom. Consider adding this logic:
```typescript
const estimatedHeight = 600; // Or calculate based on schema
if (position.y + estimatedHeight > window.innerHeight) {
  position.y = rect.top - estimatedHeight - 10; // Flip above
}
```

2. **Mobile horizontal overflow**: On mobile, 600px might exceed screen width. Solution: `minWidth: Math.min(600, window.innerWidth * 0.95)`.

3. **Quick toggles**: Old FloatingPropertiesPanel had quick toggle buttons (Required, Hidden, Read-only) at top of General tab (lines 330-353). SchemaPropertyPanel doesn't have this. **Question**: Should we add quick toggles to SchemaPropertyPanel or omit them?

4. **PropertyPanelRegistry transition**: Currently, PropertyPanelRegistry is called inside old FloatingPropertiesPanel (line 379). New design: SchemaPropertyPanel called directly in FloatingPanel. PropertyPanelRegistry logic moves into SchemaPropertyPanel (already done at lines 112-119 of PropertyPanelRegistry.tsx).

5. **Z-index layering**: Ensure FloatingPanel z-index (1000) doesn't conflict with context menu, modals, or other overlays. Check `z-index` values across codebase.

6. **Animation timing**: Original fadeInUp is 0.2s. Match this for consistency.

7. **Drag handle interference**: Element has drag handle. Ensure clicking drag handle doesn't trigger panel open—only click on element content should. (Currently handled by browser's drag event distinction.)

8. **Multi-select mode**: When Ctrl/Cmd-clicking elements, panel should NOT open (line 2469). Current code handles this correctly—only opens panel if `!isMultiSelect`.

9. **Form wrapper vs element**: When form wrapper selected (isFormWrapperSelected=true), different panel shown (FormWrapperSettingsPanel). Keep this distinction.

10. **Tab state persistence**: Old FloatingPropertiesPanel remembers active tab per element. SchemaPropertyPanel manages own tab state. Should tab state be global or per-panel-instance? Current: per-instance (useState in component).

### Testing Checklist

**Interaction tests**:
- [ ] Single click element → Panel opens below element, left-aligned
- [ ] Single click different element → Panel moves to new element
- [ ] Right-click element → Context menu appears
- [ ] Context menu "Edit" → Panel opens at cursor position
- [ ] Click + hold + drag → Element drags, panel does NOT open
- [ ] ESC key → Panel closes
- [ ] Click outside panel → Panel closes
- [ ] Click inside panel → Panel stays open
- [ ] Delete button → Element removed, panel closes

**Positioning tests**:
- [ ] Panel width 600px on desktop (>768px viewport)
- [ ] Panel width adapts to mobile viewport (<768px)
- [ ] Panel doesn't overflow right edge of viewport
- [ ] Panel doesn't overflow left edge of viewport
- [ ] Panel clamped with 16px padding on all sides
- [ ] Panel opens below element with 10px gap
- [ ] (Optional) Panel flips above element if bottom overflow

**Content tests**:
- [ ] Element icon shows in header
- [ ] Element label shows in header
- [ ] Close button works
- [ ] Delete button shows for regular elements
- [ ] Delete button hidden for form wrapper (if applicable)
- [ ] SchemaPropertyPanel renders all properties
- [ ] Category tabs work
- [ ] Property changes trigger onUpdate
- [ ] Conditional visibility (showWhen) works

**Responsive tests**:
- [ ] Desktop (1920x1080): 600px panel
- [ ] Tablet (768x1024): Adaptive width
- [ ] Mobile (375x667): Full width minus padding
- [ ] Mobile landscape: Adaptive width

**Performance tests**:
- [ ] Panel opens smoothly (animation)
- [ ] No flicker on position calculation
- [ ] Re-renders only when floatingPanel state changes
- [ ] Click-outside doesn't cause unnecessary re-renders

## Work Log
- [2025-12-03] Task created for V2 architecture planning
- [2025-12-03] Refocused to Schema-First Architecture after audit revealed fragmented schemas
- [2025-12-03] Added requirements: Tailwind rendering, settings clipboard, layout operations
- [2025-12-04] Context gathering: FloatingPropertiesPanel refactor analysis complete
