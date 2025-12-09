---
name: m-refactor-buttons-to-shadcn
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-12-08
completed: 2025-12-08
---

# Refactor Custom Buttons to shadcn Button Components

## Problem/Goal

The FormBuilderV2 codebase has ~56 custom `<button>` elements that should use the shadcn `<Button>` component instead. These custom buttons lack:

- Proper focus ring accessibility (`focus-visible:ring-ring/50`)
- Disabled state styling (`disabled:pointer-events-none disabled:opacity-50`)
- Dark mode support (`dark:bg-input/30 dark:hover:bg-input/50`)
- Consistent sizing via `size` prop
- SVG auto-sizing (`[&_svg]:size-4`)
- `data-slot="button"` attribute for slot-based styling

Using shadcn Button provides better accessibility, dark mode support, and visual consistency.

## Success Criteria
- [x] All custom `<button>` elements in FormBuilderV2 replaced with shadcn `<Button>`
- [x] Proper variants used: ghost, outline, default, secondary, destructive
- [x] Proper sizes used: icon, sm, default, lg
- [x] All buttons have data-testid attributes
- [x] Build passes without errors
- [x] Obsolete CSS classes removed from form-builder.css
- [x] Portal CSS scoping issue resolved

## Buttons Converted

All ~60 custom `<button>` elements converted to shadcn `<Button>` components:
- [x] Icon buttons (variant="ghost" size="icon")
- [x] Ghost buttons (variant="ghost" size="sm")
- [x] Outline buttons (variant="outline" size="sm")
- [x] Default/Primary buttons (variant="default" size="sm")
- [x] Special cases (TopBar actions, FormSelector, FloatingToolbar)

## Context Manifest

### Button Component Architecture (Post-Refactor)

FormBuilderV2 now uses shadcn `<Button>` components exclusively. Located at `/src/react/admin/components/ui/button.tsx`, this component provides:
- Proper accessibility: `focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2`
- Disabled state handling: `disabled:pointer-events-none disabled:opacity-50`
- Automatic SVG sizing: `[&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0`
- Dark mode support via Tailwind design tokens
- Consistent variants via class-variance-authority (cva)

**Button Component Props:**
- `variant`: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link"
- `size`: "default" | "sm" | "lg" | "icon"
- `asChild`: boolean (use with Radix Slot for polymorphic rendering)

### Shadcn Button Component Deep Dive

**Location:** `/src/react/admin/components/ui/button.tsx`

**Implementation Details:**

```typescript
// Base classes applied to ALL buttons
"inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0"

// Variants
default:     "bg-primary text-primary-foreground hover:bg-primary/90"
destructive: "bg-destructive text-destructive-foreground hover:bg-destructive/90"
outline:     "border border-gray-200 bg-white hover:bg-gray-100 hover:text-gray-900"
secondary:   "bg-secondary text-secondary-foreground hover:bg-secondary/80"
ghost:       "hover:bg-accent hover:text-accent-foreground"
link:        "text-primary underline-offset-4 hover:underline"

// Sizes
default: "h-10 px-4 py-2"
sm:      "h-9 rounded-md px-3"
lg:      "h-11 rounded-md px-8"
icon:    "h-10 w-10"
```

**Key Features:**
- Uses `class-variance-authority` (cva) for type-safe variant composition
- Integrates with Radix UI Slot for `asChild` prop (polymorphic button)
- All SVG icons automatically sized to `size-4` (16px) via `[&_svg]:size-4`
- Consistent 2px focus ring with offset for accessibility
- Disabled state prevents clicks AND reduces opacity

**Import Path Pattern:**
```typescript
import { Button } from '@/components/ui/button';
// OR from FormBuilderV2 files:
import { Button } from '../../../components/ui/button';
import { Button } from '../../../../components/ui/button';
import { Button } from '../../../../../components/ui/button';
```

### Common Button Usage Patterns

**Pattern Examples from Converted Files:**

**Icon-only buttons (size="icon"):**
```tsx
<Button
  variant="ghost"
  size="icon"
  className="relative z-[101]"
  aria-label="Open menu"
  data-testid="mobile-menu-trigger"
>
  <Menu className="w-5 h-5" />
</Button>
```

**Menu/List item buttons (variant="ghost", custom height):**
```tsx
<Button
  variant="ghost"
  onClick={onClick}
  className="flex items-center justify-start gap-3 w-full h-auto px-4 py-3 text-sm min-h-[44px]"
  data-testid="menu-item"
>
  <Icon className="w-5 h-5" />
  <span>Label</span>
</Button>
```

**Action buttons with loading state:**
```tsx
<Button
  variant="default"
  onClick={onSave}
  disabled={isSaving}
  data-testid="action-save"
>
  {isSaving ? <RefreshCw className="w-5 h-5 animate-spin" /> : <Save className="w-5 h-5" />}
  <span>Save</span>
</Button>
```

**Custom color override (green publish button):**
```tsx
<Button
  onClick={onPublish}
  className="bg-green-600 hover:bg-green-700 text-white"
  data-testid="action-publish"
>
  <Send className="w-5 h-5" />
  <span>Publish</span>
</Button>
```

### Portal CSS Scoping Issue (Resolved)

**Problem:** shadcn Drawer (Vaul) uses React Portal rendering to `<body>`, placing content outside our scoped `#sfui-admin-root` CSS container. WordPress admin CSS `button, input, select, textarea` rule overrode Tailwind classes, causing browser default styles on portal buttons.

**Solution:**
1. Added `document.body.id = 'sfui-admin-root'` in page-create-form-v2.php
2. Changed React mount point from `id="sfui-admin-root"` to `id="sfui-admin-mount"` to avoid duplicate IDs
3. Enhanced button reset in styles/index.css with `border: 0; padding: 0;`

### Architecture Context: Import Paths and File Structure

FormBuilderV2 uses a **nested component structure** with varying import depths:

```
/src/react/admin/
├── components/ui/button.tsx          ← shadcn Button component
├── apps/form-builder-v2/
    ├── FormBuilderV2.tsx              ← Main app (3 levels up)
    ├── components/
    │   ├── TopBar.tsx                 ← (3 levels up)
    │   ├── TabBar.tsx                 ← (3 levels up)
    │   ├── ui/
    │   │   ├── RightSidebar.tsx       ← (4 levels up)
    │   │   ├── controls/
    │   │   │   └── ZoomControls.tsx   ← (5 levels up)
    │   │   ├── panels/
    │   │   │   └── BasePanel.tsx      ← (5 levels up)
    │   │   └── overlays/
    │   │       └── FloatingToolbar.tsx ← (5 levels up)
    │   └── property-panels/
    │       └── container/
    │           └── ContainerProperties.tsx ← (5 levels up)
```

**Import path formula:**
- From FormBuilderV2.tsx: `'../../../components/ui/button'`
- From TopBar.tsx: `'../../../components/ui/button'`
- From RightSidebar.tsx: `'../../../../components/ui/button'`
- From ZoomControls.tsx: `'../../../../../components/ui/button'`

### CSS Cleanup Completed

Removed obsolete button classes from `form-builder.css`:
- `.zoom-controls`, `.zoom-btn` - ZoomControls now uses Button component
- `.panel-close-btn` - Panels now use Button component
- `.toolbar-btn` - Toolbar now uses Button component

CSS bundle reduced by ~1.4 KB. All button styling now handled via shadcn Button variants.

### Conversion Mapping Reference

**Current CSS class → shadcn Button props:**

```typescript
// Icon buttons
className="btn btn-ghost btn-icon"
→ variant="ghost" size="icon"

// Small ghost buttons
className="btn btn-ghost text-sm"
→ variant="ghost" size="sm"

// Outline buttons
className="btn btn-sm btn-outline"
→ variant="outline" size="sm"

// Primary action buttons
className="btn btn-sm btn-primary"
→ variant="default" size="sm"

// Active state (tabs, toggles)
className="btn btn-active"
→ variant="secondary" (or custom className for specific active styles)

// Custom colors (preserve via className)
className="btn-publish" (green background)
→ className="bg-green-600 hover:bg-green-700 text-white"
```

### Data Attributes Pattern

All converted buttons should include `data-testid` for E2E testing:

```tsx
<Button
  variant="ghost"
  size="icon"
  data-testid="close-button"
  aria-label="Close"
>
  <X className="w-4 h-4" />
</Button>
```

**Naming convention for data-testid:**
- Action buttons: `"action-{name}"` (e.g., "action-save", "action-publish")
- Icon buttons: `"{name}-button"` (e.g., "close-button", "delete-button")
- Menu items: `"menu-{name}"` (e.g., "menu-undo", "menu-export")
- Tab buttons: `"tab-{name}"` (e.g., "tab-builder", "tab-settings")

### Active State Handling Patterns

**For toggle buttons (e.g., grid, elements panel):**
```tsx
// Before (TopBar.tsx ToolbarToggle)
<button
  className={cn(
    'flex items-center justify-center w-8 h-8 rounded-md transition-colors',
    isActive
      ? 'bg-muted text-foreground'
      : 'text-muted-foreground hover:text-foreground hover:bg-muted'
  )}
  aria-pressed={isActive}
>

// After
<Button
  variant="ghost"
  size="icon"
  className={cn(isActive && 'bg-muted text-foreground')}
  aria-pressed={isActive}
>
```

**For tab buttons with sidebar styling:**
```tsx
// TabBar.tsx TabButton - preserve special sidebar active state
<Button
  variant="ghost"
  className={cn(
    'relative flex items-center gap-2 whitespace-nowrap',
    isActive && !isSidebarTab && 'bg-background text-foreground shadow-sm',
    isActive && isSidebarTab && 'bg-primary/10 text-primary'
  )}
  aria-selected={isActive}
>
```

### Accessibility Requirements

All converted buttons must maintain or improve accessibility:

1. **Icon-only buttons MUST have `aria-label`:**
   ```tsx
   <Button variant="ghost" size="icon" aria-label="Close panel">
     <X className="w-4 h-4" />
   </Button>
   ```

2. **Toggle buttons MUST have `aria-pressed`:**
   ```tsx
   <Button aria-pressed={isActive}>Toggle Grid</Button>
   ```

3. **Disabled buttons inherit from Button component:**
   ```tsx
   <Button disabled={!canUndo}>Undo</Button>
   // Automatically gets: disabled:pointer-events-none disabled:opacity-50
   ```

4. **Tooltips via `title` attribute (when no visible label):**
   ```tsx
   <Button variant="ghost" size="icon" title="Undo (Ctrl+Z)">
     <RotateCcw className="w-4 h-4" />
   </Button>
   ```


## Work Log

### 2025-12-08

#### Completed

**Phase 1-5: Button Conversions**
- Converted ~60 buttons from custom `<button>` elements to shadcn `<Button>` components
- Icon buttons: BasePanel.tsx, Toast.tsx, RightSidebar.tsx, ResizableBottomTray.tsx, ContainerProperties.tsx, StepWizardProperties.tsx, ZoomControls.tsx
- Ghost buttons: TabBar.tsx, FloatingToolbar.tsx, FormSelector.tsx
- Outline buttons: FormBuilderV2.tsx (Export, Configure, Test webhook, Pagination)
- Default buttons: EmailsTab.tsx, TopBar.tsx, VersionHistoryPanel.tsx, ErrorBoundary.tsx
- All buttons include proper `variant`, `size`, and `data-testid` attributes

**Phase 6: CSS Cleanup**
- Removed `.zoom-controls`, `.zoom-btn`, `.panel-close-btn`, `.toolbar-btn` from form-builder.css
- CSS bundle reduced by ~1.4 KB
- All button styling now consolidated in shadcn Button component

#### Bug Fix: Portal CSS Scoping

**Issue:** Drawer close button showed browser default styles (2px outset border, gray background)

**Root Cause:** shadcn Drawer (Vaul) uses React Portal rendering to `<body>`, outside scoped `#sfui-admin-root` CSS container. WordPress admin CSS `button, input, select, textarea` rule overrode Tailwind classes.

**Solution:**
1. Added `document.body.id = 'sfui-admin-root'` in page-create-form-v2.php for portal scope
2. Changed React mount point from `id="sfui-admin-root"` to `id="sfui-admin-mount"` (avoid duplicate IDs)
3. Enhanced button reset in styles/index.css: `border: 0; padding: 0;`

#### Files Modified

**React Components (15 files):**
- BasePanel.tsx, Toast.tsx, RightSidebar.tsx, ResizableBottomTray.tsx
- ContainerProperties.tsx, StepWizardProperties.tsx
- EmailsTab.tsx, VersionHistoryPanel.tsx, ErrorBoundary.tsx
- TabBar.tsx, TopBar.tsx, FormSelector.tsx
- ZoomControls.tsx, FloatingToolbar.tsx, FormBuilderV2.tsx

**CSS & PHP:**
- form-builder.css (removed ~50 lines of obsolete CSS)
- styles/index.css (enhanced button reset)
- page-create-form-v2.php (added body ID for portal scoping)
- index.tsx (changed mount point ID)
