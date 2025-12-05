# Subtask 05: Tailwind CSS Migration

## Goal

Remove the parallel custom CSS system (design-tokens.css + form-builder.css) and migrate all FormBuilderV2 styling to Tailwind utilities.

## Current CSS Analysis

### design-tokens.css (192 lines)
Defines CSS custom properties that duplicate Tailwind's design system:
- Color scales (primary, secondary, success, warning, error, neutral)
- Typography (font sizes, weights, line heights)
- Spacing scale
- Border radius
- Shadows
- Z-index scale
- Animation durations

**Migration**: Remove entirely. Use Tailwind's built-in utilities.

### form-builder.css (3900+ lines)
Complex stylesheet with many component styles. Key sections:

| Section | Lines | Migration Strategy |
|---------|-------|-------------------|
| Toast notifications | 1-90 | Use shadcn/ui Toast |
| Container elements | 106-280 | Keep (form element styling) |
| Topbar styles | 557-917 | **DONE** - TopBar component uses Tailwind |
| Element palette | 918-1035 | Keep (element picker UI) |
| Canvas styles | 1036-1090 | Migrate to Tailwind |
| Element controls | 1092-1258 | Keep (drag/hover states) |
| Tab bar styles | 2103-2158 | **DONE** - TabBar component uses Tailwind |
| Main content | 2159-2211 | Migrate to Tailwind |
| Canvas container | 2212-2280 | Migrate to Tailwind |
| Property fields | Various | Migrate to Tailwind |

## Classes to Migrate

### Layout Classes (High Priority)
```css
/* .main-content → Tailwind */
.main-content {
  display: flex;
  flex: 1;
  overflow: hidden;
  position: relative;
}
/* → className="flex flex-1 overflow-hidden relative" */

/* .tab-content-panel → Tailwind */
.tab-content-panel {
  width: 360px;
  background: white;
  border-right: 1px solid var(--color-border-primary);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
/* → className="w-[360px] bg-background border-r border-border flex flex-col overflow-hidden" */

/* .canvas-container → Tailwind */
.canvas-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: auto;
  background: var(--color-bg-secondary);
  padding: var(--space-4);
}
/* → className="flex-1 flex flex-col overflow-auto bg-muted p-4" */
```

### Form Field Classes
```css
/* .property-field → Tailwind */
.property-field { margin-bottom: 12px; }
/* → className="mb-3" or use space-y-3 on parent */

/* .property-label → Tailwind */
.property-label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-secondary);
  margin-bottom: 4px;
}
/* → className="block text-xs font-medium text-muted-foreground mb-1" */

/* .form-input → Tailwind (shadcn/ui Input style) */
.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--color-border-primary);
  border-radius: 6px;
  font-size: 14px;
}
/* → Use <Input /> from shadcn/ui or className="w-full px-3 py-2 border border-border rounded-md text-sm" */
```

### Button Classes (Partially Done)
```css
/* .btn variants → Use shadcn/ui Button */
.btn { ... }
.btn-sm { ... }
.btn-ghost { ... }
.btn-outline { ... }
.btn-primary { ... }
.btn-danger { ... }
/* → <Button variant="ghost" size="sm" /> */
```

## Migration Plan

### Phase 1: Layout Classes
Replace in FormBuilderV2.tsx:
- `.main-content` → Tailwind flex utilities
- `.tab-content-panel` → Tailwind width, flex, border
- `.tab-panel-header` → Tailwind flex, padding, border
- `.canvas-container` → Tailwind flex, overflow, background

### Phase 2: Form Field Classes
Replace inline:
- `.property-field` → `className="space-y-1"` or `mb-3`
- `.property-label` → `className="text-xs font-medium text-muted-foreground"`
- `.form-input` → Use shadcn/ui Input or Tailwind classes
- `.sidebar-content` → `className="flex flex-col overflow-auto"`

### Phase 3: Canvas Classes
Keep some, migrate others:
- `.canvas-responsive` → Tailwind max-width utilities
- `.canvas-desktop/tablet/mobile` → Tailwind max-width classes
- `.canvas-zoom-wrapper` → Keep (transform origin needed)
- `.canvas-framed` → Keep (device frame styling - complex)

### Phase 4: Remove/Deprecate Files
After migration:
1. Remove `import './styles/design-tokens.css'` from FormBuilderV2.tsx
2. Keep form-builder.css but remove migrated sections
3. Document remaining CSS (element styling, animations, device frames)

## Files to Modify

- [ ] `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` - Replace CSS classes
- [ ] `src/react/admin/apps/form-builder-v2/styles/form-builder.css` - Remove migrated styles
- [ ] `src/react/admin/apps/form-builder-v2/styles/design-tokens.css` - Mark as deprecated or remove import

## Testing Checklist

- [ ] Tab content panel renders correctly (width, border, background)
- [ ] Canvas area fills available space
- [ ] Device preview sizes work (desktop/tablet/mobile)
- [ ] Property fields have proper spacing and labels
- [ ] Buttons have correct styling
- [ ] No visual regressions from custom CSS removal
- [ ] Build passes without CSS errors

## Work Log

### 2025-12-05

#### Completed
- Fixed JSX syntax error in FormBuilderV2.tsx where canvas conditional wrapper had incorrect nesting causing React component errors
- Resolved button padding specificity issue: global `#sfui-admin-root button { padding: 0 }` was overriding Tailwind's px-3 py-2 utilities
- Refactored `/src/react/admin/styles/index.css` to use `@layer base` for all preflight resets, ensuring proper CSS cascade
- Implemented lazy loading for EmailsTab and AutomationsTab components to improve initial bundle size
- Created new EmailsTab.tsx component file
- Added Workflow icon mapping to TabBar.tsx iconMap for proper tab icon display
- Updated CLAUDE.md with refreshed temp-login-token and Form Builder V2 navigation instructions

#### Decisions
- Used `@layer base` pattern for global resets to respect Tailwind's layering system and avoid specificity conflicts
- Kept specificity fix minimal by using `@layer base` rather than increasing selector weight

#### Discovered
- Global button padding reset in `#sfui-admin-root button { padding: 0 }` was causing widespread button styling issues
- CSS specificity conflicts can be elegantly resolved using Tailwind's layer system
