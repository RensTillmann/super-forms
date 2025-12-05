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
