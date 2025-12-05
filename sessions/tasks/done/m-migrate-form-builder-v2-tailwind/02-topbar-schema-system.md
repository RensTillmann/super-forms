# Subtask 02: TopBar Schema System

## Goal

Create a schema-driven toolbar system for the form builder top bar. Items should be:
- Registered via `registerToolbarItem()` function
- Organized into groups (left, history, canvas, panels, primary)
- Support multiple types (button, toggle, dropdown, custom)
- Use Tailwind CSS (no custom CSS classes)

## Implementation Details

### Schema Location

```
src/react/admin/schemas/toolbar/
├── types.ts     # Zod schemas and TypeScript types
├── registry.ts  # Item registration and query functions
└── index.ts     # Default item registrations + exports
```

### Toolbar Item Schema Definition

```typescript
// types.ts
import { z } from 'zod';

export const ToolbarItemTypeSchema = z.enum(['button', 'toggle', 'dropdown', 'custom']);

export const ToolbarGroupSchema = z.enum(['left', 'history', 'canvas', 'panels', 'primary']);

export const ToolbarItemSchema = z.object({
  id: z.string().regex(/^[a-z][a-z0-9-]*$/),
  type: ToolbarItemTypeSchema,
  group: ToolbarGroupSchema,
  icon: z.string().optional(),  // Lucide icon name
  label: z.string().optional(),
  tooltip: z.string(),
  variant: z.enum(['default', 'ghost', 'secondary', 'save', 'publish']).default('ghost'),
  position: z.number(),  // Sort order within group
  // For toggle type
  activeIcon: z.string().optional(),
  // For dropdown type
  options: z.array(z.object({
    value: z.string(),
    label: z.string(),
    icon: z.string().optional(),
  })).optional(),
  // For custom type
  component: z.string().optional(),
  // Visibility
  showLabel: z.boolean().default(true),
  hiddenOnMobile: z.boolean().default(false),
});
```

### Registry Functions

```typescript
// registry.ts
registerToolbarItem(schema: ToolbarItemSchema): ToolbarItemSchema
getToolbarItem(id: string): ToolbarItemSchema | undefined
getToolbarItemsByGroup(group: ToolbarGroup): ToolbarItemSchema[]
getAllToolbarItems(): ToolbarItemSchema[]
```

### All 16 Toolbar Items

| Group | ID | Type | Icon | Label | Variant |
|-------|-----|------|------|-------|---------|
| left | form-selector | custom | - | - | - |
| left | form-title | custom | - | - | - |
| left | device-selector | dropdown | Monitor | Desktop | ghost |
| left | device-frame-toggle | toggle | Maximize | - | ghost |
| history | undo | button | RotateCcw | - | ghost |
| history | redo | button | RefreshCw | - | ghost |
| canvas | toggle-grid | toggle | Grid | - | ghost |
| canvas | zoom | custom | - | - | - |
| panels | elements-panel | toggle | Layers | - | ghost |
| panels | version-history | button | History | - | ghost |
| panels | share | button | Share | - | ghost |
| panels | export | button | Download | - | ghost |
| panels | analytics | button | BarChart | - | ghost |
| primary | preview | button | Eye | Preview | secondary |
| primary | save | button | Save | Save | save |
| primary | publish | button | Send | Publish | publish |

### TopBar Component

```typescript
// src/react/admin/apps/form-builder-v2/components/TopBar.tsx
interface TopBarProps {
  // State props for toggles
  showGrid: boolean;
  onToggleGrid: () => void;
  showElementsPanel: boolean;
  onToggleElementsPanel: () => void;
  showDeviceFrame: boolean;
  onToggleDeviceFrame: () => void;
  // Device preview
  devicePreview: 'desktop' | 'tablet' | 'mobile';
  onDeviceChange: (device: string) => void;
  // History
  canUndo: boolean;
  canRedo: boolean;
  onUndo: () => void;
  onRedo: () => void;
  // Actions
  onSave: () => void;
  onPreview: () => void;
  onPublish: () => void;
  // ... other handlers
}
```

Uses Tailwind classes:
- Container: `flex items-center justify-between px-4 py-2 bg-background border-b border-border`
- Button: `flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors`
- Ghost: `text-muted-foreground hover:text-foreground hover:bg-muted`
- Save: `bg-primary text-primary-foreground hover:bg-primary/90`
- Publish: `bg-green-600 text-white hover:bg-green-700`

### Integration Points

1. **FormBuilderV2.tsx** - Replace hardcoded topbar (lines 3274-3425) with `<TopBar />`
2. **State** - All toggle states already exist, just wire to TopBar props
3. **Handlers** - All handlers already exist (handleSave, handleUndo, etc.)

## Files to Create

- [ ] `src/react/admin/schemas/toolbar/types.ts`
- [ ] `src/react/admin/schemas/toolbar/registry.ts`
- [ ] `src/react/admin/schemas/toolbar/index.ts`
- [ ] `src/react/admin/apps/form-builder-v2/components/TopBar.tsx`

## Files to Modify

- [ ] `src/react/admin/schemas/index.ts` - Add toolbar export
- [ ] `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` - Import and use TopBar

## Testing Checklist

- [ ] All 16 toolbar items render correctly
- [ ] Button clicks trigger correct actions
- [ ] Toggle states work (grid, elements panel, device frame)
- [ ] Dropdown opens and selects device
- [ ] Custom components render (FormSelector, ZoomControls, InlineEditableText)
- [ ] Undo/Redo disabled states work
- [ ] Save button shows spinner when saving
- [ ] Mobile responsive (labels hidden on small screens)
- [ ] No custom CSS classes used (all Tailwind)
