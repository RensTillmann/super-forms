---
name: n-implement-global-individual-styles
branch: feature/h-implement-triggers-actions-extensibility
status: not-started
created: 2025-12-05
---

# Global/Individual Style System

## Problem/Goal

The Form Builder lacks a unified styling system. Currently:
- Form styling is ad-hoc with no central registry or inheritance
- Users can't define global theme defaults that apply to all elements
- No way to override individual element styles while keeping others linked to global
- Styling is not exposed to MCP server for LLM-driven form building
- Form and Email builders have independent styling approaches

We need a **schema-first style system** where:
- Zod schemas are the source of truth (UI, API, MCP all derive from schemas)
- Global styles define theme-level defaults for "node types" (label, input, heading, etc.)
- Individual element overrides can "unlink" specific properties from globals
- MCP actions enable LLM agents to programmatically style forms

## Success Criteria

- [ ] Style schemas defined with Zod (StylePropertySchema, NodeTypeSchema, NodeStyleCapabilitiesSchema)
- [ ] Node type abstraction implemented (elements contain: label, description, input, error, etc.)
- [ ] Global style registry with sensible defaults for all node types
- [ ] Per-element style overrides with link/unlink capability per property
- [ ] Style resolution chain: Global Theme → Node Styles → Element Overrides → Rendered Style
- [ ] UI components for editing global and individual styles (NodeStylePopover)
- [ ] MCP action handlers for LLM style manipulation
- [ ] Form elements render using resolved styles from registry
- [ ] Theme presets (save/load/export)

## Context Manifest

### How Form Builder Styling Currently Works

The Form Builder V2 currently has **ad-hoc styling with no centralized system**. When you look at how elements are styled today, there's a clear absence of structure:

**Current Element Rendering Pattern:**
Form elements like `TextInput.tsx` receive a `commonProps` object that contains inline styles. The `ElementRenderer.tsx` component constructs these props by reading from `element.properties` (which is just a flat key-value record):

```typescript
// From ElementRenderer.tsx - lines 36-42
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
```

This means every element stores its own styling properties directly in `element.properties` without any inheritance, theming, or global defaults. If you want to change the background color of all inputs, you'd need to manually update every single element instance.

**Where Element Data Lives (Zustand Store):**
The Form Builder uses Zustand for state management with two main stores:

1. **`useElementsStore.ts`** - Manages element instances, their properties, and ordering:
   - `items: Record<string, FormElement>` - Map of element ID to element data
   - `order: string[]` - Array of element IDs defining render order
   - `deviceVisibility: Record<string, DeviceVisibility>` - Per-element visibility settings
   - Actions: `addElement`, `updateElement`, `removeElement`, `moveElement`

2. **`useBuilderStore.ts`** - Manages UI state (drag/drop, selection):
   - `selectedElements: string[]` - Currently selected element IDs
   - `hoveredElement: string | null` - Element being hovered
   - `draggedElement: string | null` - Element being dragged

The `FormElement` type (from `types/index.ts`) is simple:
```typescript
export interface FormElement {
  id: string;
  type: string;
  properties: Record<string, any>;  // ← All element data lives here (flat structure)
  children?: string[];
  parent?: string;
}
```

**There is NO style system yet.** The `properties` field is completely unstructured - it's just `Record<string, any>`. No global styles, no inheritance, no node type abstraction.

**Property Panel System (How Users Edit Properties):**
The Form Builder recently migrated to a **schema-first architecture**. The `FloatingPanel.tsx` component is the property editor that appears when you select an element. Here's how it works:

1. User clicks an element → `FloatingPanel` opens
2. Panel checks if element type has a registered schema via `isElementRegistered(element.type)`
3. If yes: Renders `SchemaPropertyPanel` which reads the element's schema from the registry
4. If no: Falls back to legacy property panels

The schema comes from `/src/react/admin/schemas/core/registry.ts`:
- Elements register themselves via `registerElement(schema)` at import time
- Schema defines all properties organized by category: `general`, `validation`, `appearance`, `advanced`, `conditions`
- Each property has a type (string, number, boolean, select, color, etc.) that determines which control to render
- The `PropertyRenderer.tsx` component maps property types to UI controls (inputs, selects, checkboxes, color pickers)

**Example - Text Element Schema (`schemas/elements/text.ts`):**
```typescript
export const TextElementSchema = registerElement({
  type: 'text',
  name: 'Text Input',
  category: 'basic',
  properties: withBaseProperties({
    general: {
      placeholder: { type: 'string', label: 'Placeholder', translatable: true },
      defaultValue: { type: 'string', label: 'Default Value' },
    },
    validation: {
      required: { type: 'boolean', label: 'Required', default: false },
      minLength: { type: 'number', label: 'Minimum Length' },
    },
    appearance: {
      inputIcon: { type: 'icon', label: 'Input Icon' },
    }
  }),
  // ...
});
```

When a property changes, the `FloatingPanel` calls `onPropertyChange(propertyName, value)` which triggers `useElementsStore.updateElement(id, updates)`. This updates the flat `properties` object in the store.

**Key Insight:** The schema system is **already built and working**. Our new style system needs to integrate with this existing pattern. We'll be adding:
- New schema types for style properties (e.g., `StylePropertySchema`)
- A global style registry (similar to element registry)
- Style-specific property renderers that understand linked/unlinked states

### How Email Builder Handles Styling (Lessons to Learn)

The Email Builder has a **capability-based styling system** that we should study and adapt. Located in `/src/react/admin/components/email-builder/`:

**Capability Registry Pattern (`capabilities/elementCapabilities.js`):**
Each element type declares what styling features it supports:

```javascript
export const elementCapabilities = {
  text: {
    resizable: { horizontal: true, vertical: false },
    background: { color: false, image: false },
    spacing: { margin: true, padding: false, border: false },
    typography: { font: true, size: true, color: true, lineHeight: true },
  },
  button: {
    resizable: { horizontal: true, vertical: false },
    background: { color: true, image: false },
    spacing: { margin: true, padding: true, border: true },
    typography: { font: true, size: true, color: true },
  },
  // ... more element types
};
```

The capability system uses helper functions:
- `getElementCapabilities(type)` - Get all capabilities for an element
- `hasCapability(type, 'spacing.margin')` - Check if element supports a specific capability
- `getElementsWithCapability('typography.font')` - Find all elements with a capability

**SpacingCompass Component - The Gold Standard:**
The `SpacingCompass.jsx` is a sophisticated UI control for editing margin/padding/border in a visual "box model" interface. Key features we need to port:

1. **Linked/Unlinked States** - Each spacing type (margin, border, padding) has a link button:
   - When linked: All four sides (top/right/bottom/left) have the same value
   - When unlinked: Each side can be edited independently
   - Link button shows `<Link>` icon when linked, `<Unlink>` when unlinked

2. **Visual Layered Interface:**
   - Outer orange layer = margin
   - Middle purple layer = border
   - Inner blue layer = padding
   - Center white box = content (background controls)

3. **Debounced Updates** - Color picker changes are debounced (150ms) to prevent performance issues:
   ```javascript
   const debouncedBackgroundColorChange = useCallback((color) => {
     setLocalBackgroundColor(color); // Update UI immediately
     colorChangeTimeoutRef.current = setTimeout(() => {
       onBackgroundColorChange(color); // Update store after delay
     }, 150);
   }, [onBackgroundColorChange]);
   ```

4. **DraggableNumberInput** - Number inputs support drag-to-adjust (not just typing)

**Property Panel Integration (`PropertyPanels/OptimizedPropertyPanel.jsx`):**
The email builder's property panel shows how to integrate the SpacingCompass:

```javascript
// Check capabilities first
const capabilities = getElementCapabilities(element.type);

// Only render if element supports spacing/background
{(capabilities.spacing?.margin || capabilities.background) && (
  <SpacingCompass
    margin={localProps.margin || { top: 0, right: 0, bottom: 0, left: 0 }}
    border={localProps.border || { top: 0, right: 0, bottom: 0, left: 0 }}
    padding={localProps.padding || { top: 20, right: 20, bottom: 20, left: 20 }}
    backgroundColor={localProps.backgroundColor || '#ffffff'}
    onMarginChange={(margin) => updateProperty('margin', margin)}
    onPaddingChange={(padding) => updateProperty('padding', padding)}
    // ... more props
  />
)}
```

The panel uses **local state** (`localProps`) for immediate UI updates, then calls `updateProperty()` to persist to the store. This prevents re-render storms.

**Email Builder Store Pattern (`hooks/useEmailStore.js`):**
Uses Zustand with devtools middleware:
```javascript
const useEmailStore = createWithEqualityFn(devtools((set, get) => ({
  // State
  elements: [],

  // Nested property updates
  updateElement: (elementId, updates) => {
    set((state) => ({
      elements: state.elements.map(el =>
        el.id === elementId ? { ...el, ...updates } : el
      ),
      isDirty: true
    }));
  },
})));
```

### What We Need to Build: The Architecture Bridge

Our new style system needs to **bridge between** the Form Builder's schema-first approach and the Email Builder's capability-based styling. Here's how the pieces connect:

**1. Node Type Abstraction (NEW CONCEPT)**

Form elements aren't atomic - they contain multiple "styleable nodes":

```
TextInput element (type='text')
├── labelNode       → Can style: font, color, size, weight
├── descriptionNode → Can style: font, color, size
├── inputNode       → Can style: background, border, padding, font
├── errorNode       → Can style: color, font, size
└── containerNode   → Can style: margin, padding, background
```

Each node type has its own style capabilities. This is MORE granular than email builder's element-level capabilities.

**2. Global Style Registry (NEW - Similar to Element Registry)**

We'll create a singleton registry at `/src/react/admin/schemas/styles/registry.ts`:

```typescript
class StyleRegistry {
  private globalStyles: Map<NodeType, StyleProperties>;
  private capabilities: Map<NodeType, NodeStyleCapabilities>;

  // Get/set global styles for a node type
  getGlobalStyle(nodeType: NodeType): StyleProperties;
  setGlobalStyle(nodeType: NodeType, property: string, value: any): void;

  // Get capabilities (which properties this node type supports)
  getCapabilities(nodeType: NodeType): NodeStyleCapabilities;
}

export const styleRegistry = new StyleRegistry(); // Singleton instance
```

This mirrors how `elementCapabilities` works in Email Builder, but adapted for Form Builder's needs.

**3. Element Schema Extension (MODIFY EXISTING)**

We need to extend the `FormElement` type to include style overrides:

```typescript
// Current (in types/index.ts):
export interface FormElement {
  id: string;
  type: string;
  properties: Record<string, any>;
  // ...
}

// After our changes:
export interface FormElement {
  id: string;
  type: string;
  properties: Record<string, any>;
  styleOverrides?: Record<NodeName, Partial<StyleProperties>>;  // ← NEW
  // styleOverrides example:
  // {
  //   'labelNode': { fontSize: 18, color: '#ff0000' },  // overridden
  //   'inputNode': { /* uses globals */ },              // not overridden
  // }
}
```

**4. Style Resolution Chain (NEW HOOK)**

When rendering an element, we need to resolve its final styles:

```typescript
// Usage in element renderer:
const labelStyle = useResolvedStyle(element.id, 'labelNode');
// Returns: Global styles + element overrides (merged)

// Implementation pseudocode:
function useResolvedStyle(elementId: string, nodeName: string) {
  const element = useElementsStore(state => state.items[elementId]);
  const globalStyle = styleRegistry.getGlobalStyle(nodeType);
  const override = element.styleOverrides?.[nodeName];

  return useMemo(() => ({
    ...globalStyle,    // Base from global theme
    ...override,       // Per-element overrides win
  }), [globalStyle, override]);
}
```

**5. Property Panel Integration (MODIFY EXISTING)**

The `FloatingPanel` needs a new section for style editing. We'll add a `NodeStylePopover` component that:
- Shows tabs: "Global" and "Individual"
- Global tab: Edit global styles for this node type (affects all elements)
- Individual tab: Override specific properties for just this element
- Each property has a link/unlink button (like SpacingCompass)

The popover will use the capability system to know which controls to render:
```typescript
const capabilities = styleRegistry.getCapabilities('labelNode');
// Returns: { fontSize: true, color: true, fontWeight: true, ... }

// Render controls based on capabilities:
{capabilities.fontSize && (
  <LinkedPropertyInput
    label="Font Size"
    value={style.fontSize}
    globalValue={globalStyle.fontSize}
    isLinked={!hasOverride('fontSize')}
    onLink={() => removeOverride('fontSize')}
    onUnlink={() => setOverride('fontSize', globalStyle.fontSize)}
    onChange={(val) => setOverride('fontSize', val)}
  />
)}
```

### MCP Integration (Future AI Access)

The task mentions MCP (Model Context Protocol) for LLM-driven form building. Here's what we need to know:

**Current State:**
The CLAUDE.md mentions MCP but there are no actual MCP handler files in the codebase yet (the grep found only node_modules files). The architecture is being prepared for future MCP integration.

**What We Need to Build:**
MCP actions will be defined as Zod schemas (matching the schema-first philosophy):

```typescript
export const StyleActionSchema = z.discriminatedUnion('action', [
  z.object({
    action: z.literal('setGlobalStyle'),
    nodeType: z.string(),
    property: z.string(),
    value: z.any()
  }),
  z.object({
    action: z.literal('setElementStyleOverride'),
    elementId: z.string(),
    nodeName: z.string(),
    property: z.string(),
    value: z.any()
  }),
  // ... more actions
]);
```

When MCP server is implemented, it will:
1. Read these schemas to generate tool definitions for LLM
2. Validate incoming requests against schemas
3. Call our style registry methods to execute actions

**For now:** We just need to ensure our style system has **programmatic APIs** (not just UI). The registry methods like `styleRegistry.setGlobalStyle()` will be called by future MCP handlers.

### Technical Reference Details

#### File Structure (What We'll Create)

```
/src/react/admin/schemas/styles/
├── types.ts              # Zod schemas (StylePropertySchema, NodeTypeSchema, etc.)
├── capabilities.ts       # NODE_STYLE_CAPABILITIES map
├── defaults.ts           # DEFAULT_GLOBAL_STYLES
├── registry.ts           # StyleRegistry class
└── index.ts              # Barrel export

/src/react/admin/components/ui/style-editor/
├── NodeStylePopover.tsx       # Main style editing popover
├── StylePropertyControls.tsx  # Renders controls based on capabilities
├── LinkedPropertyInput.tsx    # Single property with link/unlink button
└── SpacingCompass.tsx         # Ported from email builder
```

#### Key Patterns to Follow

**1. Schema-First Registration (Matches Element Registry Pattern):**
```typescript
// From schemas/core/registry.ts - lines 42-59
export function registerElement(schema: ElementSchema): ElementSchema {
  const validated = ElementSchemaSchema.parse(schema); // Zod validation
  if (elements.has(validated.type)) {
    throw new Error(`Element type '${validated.type}' is already registered.`);
  }
  elements.set(validated.type, validated);
  return validated;
}
```

Our style registry will follow the same pattern:
- Validate with Zod at registration time
- Throw errors immediately if invalid (fail fast)
- Store in Map for O(1) lookups
- Export query functions (`getGlobalStyle`, `getCapabilities`, etc.)

**2. Zustand Store Actions (Matches Elements Store Pattern):**
```typescript
// From store/useElementsStore.ts - lines 83-93
updateElement: (id, updates) => {
  set((state) => {
    if (!state.items[id]) return state;
    return {
      items: {
        ...state.items,
        [id]: { ...state.items[id], ...updates },
      },
    };
  });
}
```

We'll add similar actions for style overrides:
```typescript
setStyleOverride: (elementId, nodeName, property, value) => {
  set((state) => {
    const element = state.items[elementId];
    if (!element) return state;

    const styleOverrides = element.styleOverrides || {};
    const nodeOverrides = styleOverrides[nodeName] || {};

    return {
      items: {
        ...state.items,
        [elementId]: {
          ...element,
          styleOverrides: {
            ...styleOverrides,
            [nodeName]: {
              ...nodeOverrides,
              [property]: value,
            },
          },
        },
      },
    };
  });
}
```

**3. Property Renderer Integration (Extend Existing Pattern):**
```typescript
// From PropertyRenderer.tsx - lines 22-47
const renderInput = () => {
  switch (schema.type) {
    case 'string':
      return <input type="text" ... />;
    case 'number':
      return <input type="number" ... />;
    case 'color':
      return <div><input type="color" ... /></div>;
    // ...
  }
};
```

We'll add new style-specific property types or reuse existing ones with linking capability wrapper.

#### Data Structures

**StyleProperties (What Gets Stored):**
```typescript
interface StyleProperties {
  // Typography
  fontSize?: number;          // 8-72
  fontFamily?: string;        // e.g., 'Arial, sans-serif'
  fontWeight?: 400 | 500 | 600 | 700;
  color?: string;             // hex color

  // Spacing
  margin?: { top: number; right: number; bottom: number; left: number };
  padding?: { top: number; right: number; bottom: number; left: number };

  // Border
  border?: { top: number; right: number; bottom: number; left: number };
  borderStyle?: 'solid' | 'dashed' | 'dotted' | 'none';
  borderColor?: string;

  // Background
  backgroundColor?: string;
  backgroundImage?: string;

  // Layout
  width?: string;
  height?: string;
  display?: 'block' | 'inline' | 'flex';
}
```

**NodeStyleCapabilities (What Controls to Show):**
```typescript
interface NodeStyleCapabilities {
  fontSize?: boolean;
  fontFamily?: boolean;
  fontWeight?: boolean;
  color?: boolean;
  margin?: boolean;
  padding?: boolean;
  border?: boolean;
  backgroundColor?: boolean;
  // ...
}

// Example mapping:
const NODE_STYLE_CAPABILITIES: Record<NodeType, NodeStyleCapabilities> = {
  'label': {
    fontSize: true,
    fontFamily: true,
    fontWeight: true,
    color: true,
    margin: true,
    padding: false,  // Labels don't need padding
    border: false,
  },
  'input': {
    fontSize: true,
    color: true,
    backgroundColor: true,
    border: true,
    padding: true,
    margin: true,
  },
  // ...
};
```

#### Integration Points

**Where to Hook In:**

1. **Element Renderer (`components/elements/ElementRenderer.tsx`):**
   - Currently passes `commonProps` with hardcoded styles
   - Change to: `const labelStyle = useResolvedStyle(element.id, 'labelNode')`
   - Apply resolved styles to each node in the element

2. **Property Panel (`components/property-panels/FloatingPanel.tsx`):**
   - Add "Styles" tab/section after existing property categories
   - Render `NodeStylePopover` for each styleable node in the element
   - Use capability check to determine which nodes to show

3. **Elements Store (`store/useElementsStore.ts`):**
   - Add `styleOverrides` field to element schema
   - Add actions: `setStyleOverride`, `removeStyleOverride`, `clearStyleOverrides`

4. **Save/Load Flow:**
   - Element data already serializes `properties` to database
   - `styleOverrides` will serialize the same way (it's just another field)
   - No PHP changes needed initially (it's JSON data)

### Critical Architecture Decisions

**Why Node Types Instead of Element-Level Styles?**

The Email Builder uses element-level capabilities (e.g., "button element can have background color"). But Form elements are more complex - a single TextInput element contains a label, description, input field, error message, and container. Each of these needs independent styling.

By introducing node types, we achieve:
- **Granular control** - Style just the label without affecting the input
- **Semantic organization** - "All labels should be 14px bold" is a global theme rule
- **Reusability** - The `label` node type is used by 20+ element types

**Why Global + Overrides Instead of Just Element Styles?**

Users need both:
- **Global theming** - "Make all labels blue" (one change affects entire form)
- **Exceptions** - "But make THIS label red" (override for one element)

Without globals, every element is independent (nightmare to maintain). Without overrides, you can't make exceptions (too rigid).

**Why Link/Unlink Per Property?**

Consider: User wants most properties linked to global (auto-update), but wants to override just the color. If link/unlink is all-or-nothing, they'd have to manually sync all other properties when globals change.

Per-property linking means:
- `fontSize` → linked (uses global, auto-updates)
- `color` → unlinked (uses override, doesn't change when global changes)
- `fontWeight` → linked (uses global)

This is the most flexible approach and matches how design tools (Figma, Sketch) work.

## Subtasks

### 1. Core Schema & Registry
File: `01-core-schema-registry.md`
- Create `/src/react/admin/schemas/styles/types.ts` (Zod schemas)
- Create `/src/react/admin/schemas/styles/capabilities.ts` (which props each node supports)
- Create `/src/react/admin/schemas/styles/defaults.ts` (default theme values)
- Create `/src/react/admin/schemas/styles/registry.ts` (StyleRegistry singleton)
- Create barrel export `index.ts`
- Add element-to-node mapping (which nodes each element type contains)

### 2. Store Integration
File: `02-store-integration.md`
- Add `styleOverrides` to element schema in form store
- Create `useResolvedStyle` hook (merges global + overrides)
- Create `useElementStyle` hook (gets all node styles for an element)
- Implement style update actions in element store

### 3. UI Components
File: `03-ui-components.md`
- Create `NodeStylePopover` component (global/individual tabs)
- Create `StylePropertyControls` component (renders controls based on capabilities)
- Create `LinkedPropertyInput` component (link/unlink button per property)
- Port `SpacingCompass` from Email builder
- Integrate with FloatingPanel property editor

### 4. Context Menu & Shortcuts
File: `04-context-menu-shortcuts.md`
- Add "Copy Style" / "Paste Style" to element context menu
- Add "Set as Global" option (promote individual to global)
- Add "Reset to Global" option
- Keyboard shortcuts (Ctrl+Alt+C for copy style, etc.)

### 5. MCP Integration
File: `05-mcp-integration.md`
- Register style action handler in MCP server
- Define tool schema for LLM agents
- Test with sample prompts
- Document available actions

### 6. Migration & Polish
File: `06-migration-polish.md`
- Migrate existing form styling to new system
- Create theme presets (Light, Dark, Minimal, etc.)
- Add theme import/export
- Documentation

## Architecture

### Schema-First Source of Truth

All style definitions are expressed in TypeScript/Zod schemas that serve as:
- **Source of truth** for UI rendering
- **API contract** for REST endpoints
- **Machine-readable format** for MCP server / LLM agents

```typescript
// Zod schema IS the contract
export const StylePropertySchema = z.object({
  fontSize: z.number().min(8).max(72).optional(),
  color: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),
  // ...
});

// Type derived from schema
export type StyleProperties = z.infer<typeof StylePropertySchema>;
```

### Node Type Abstraction

Elements contain multiple "styleable nodes" (sub-components):

```
TextInput Element
├── labelNode       → GlobalStyleRegistry['label']
├── descriptionNode → GlobalStyleRegistry['description']
├── inputNode       → GlobalStyleRegistry['input']
├── errorNode       → GlobalStyleRegistry['error']
└── containerNode   → GlobalStyleRegistry['fieldContainer']
```

### Style Inheritance Chain

```
Global Theme Defaults
       ↓
Global Node Styles (per node type)
       ↓
Individual Element Overrides (per property)
       ↓
Final Rendered Style
```

### MCP Actions

```typescript
export const StyleActionSchema = z.discriminatedUnion('action', [
  z.object({ action: z.literal('getGlobalStyle'), nodeType: NodeTypeSchema }),
  z.object({ action: z.literal('setGlobalStyle'), nodeType: NodeTypeSchema, property: z.string(), value: z.any() }),
  z.object({ action: z.literal('getNodeCapabilities'), nodeType: NodeTypeSchema }),
  z.object({ action: z.literal('listNodeTypes') }),
  // Element-specific
  z.object({ action: z.literal('setElementStyleOverride'), elementId: z.string(), nodeName: z.string(), property: z.string(), value: z.any() }),
  z.object({ action: z.literal('promoteToGlobal'), elementId: z.string(), nodeName: z.string() }),
  z.object({ action: z.literal('resetToGlobal'), elementId: z.string(), nodeName: z.string().optional() }),
  // ...
]);
```

## Key Files

**Files to Create:**
- `/src/react/admin/schemas/styles/types.ts` - Zod schemas (StylePropertySchema, NodeTypeSchema, etc.)
- `/src/react/admin/schemas/styles/capabilities.ts` - NODE_STYLE_CAPABILITIES mapping
- `/src/react/admin/schemas/styles/defaults.ts` - DEFAULT_GLOBAL_STYLES
- `/src/react/admin/schemas/styles/registry.ts` - StyleRegistry class
- `/src/react/admin/schemas/styles/index.ts` - Barrel export
- `/src/react/admin/components/ui/style-editor/NodeStylePopover.tsx`
- `/src/react/admin/components/ui/style-editor/StylePropertyControls.tsx`
- `/src/mcp/handlers/styleActions.ts` - MCP handler

**Files to Modify:**
- Element store - add `styleOverrides` to element schema
- Form store - add global style registry
- FloatingPanel - integrate style editor
- Element renderers - use resolved styles

## Node Types

| Node Type | Description | Used By |
|-----------|-------------|---------|
| `label` | Field label text | All input elements |
| `description` | Help text below fields | All input elements |
| `input` | Text input, textarea, select | Input elements |
| `placeholder` | Placeholder text | Input elements |
| `error` | Validation error message | All input elements |
| `required` | Required indicator (*) | All input elements |
| `fieldContainer` | Wrapper around field | All elements |
| `heading` | h1-h6 text | Heading, Section |
| `paragraph` | Body text | Paragraph |
| `button` | Button styling | Button, Submit |
| `divider` | Separator line | Divider |
| `optionLabel` | Radio/checkbox label | Choice elements |
| `cardContainer` | Card wrapper | Card choice elements |
| `emailWrapper` | Email outer background | Email builder |
| `emailBody` | Email content area | Email builder |

## Testing Checklist

- [ ] Schema validation (Zod) works for all types
- [ ] Global style changes propagate to all elements
- [ ] Individual overrides work independently
- [ ] Unlinking a property copies global value correctly
- [ ] Re-linking removes override and uses global
- [ ] "Set as Global" updates registry
- [ ] Style resolution is performant (memoization)
- [ ] MCP actions work correctly
- [ ] UI reflects linked/unlinked state
- [ ] Keyboard navigation works in popover
- [ ] Undo/redo works for style changes

## Reference Specification

The detailed code examples for each file are preserved in the original specification document. See the subtask files for implementation details.

## Work Log

- [2025-12-05] Task created from architectural specification (moved from m-implement-unified-element-tray/03-unify-element-tray.md)
