---
name: m-refactor-buttons-to-shadcn
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-08
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
- [ ] All custom `<button>` elements in FormBuilderV2 replaced with shadcn `<Button>`
- [ ] Proper variants used: ghost, outline, default, secondary, destructive
- [ ] Proper sizes used: icon, sm, default, lg
- [ ] All buttons have data-testid attributes
- [ ] Build passes without errors
- [ ] Visual verification confirms buttons render correctly

## Buttons to Convert

### Icon Buttons (12) → `variant="ghost" size="icon"`
- [ ] RightSidebar.tsx:78 - Drawer close button
- [ ] FormBuilderV2.tsx:910 - Eye icon button
- [ ] ContainerProperties.tsx:72,128 - Delete buttons
- [ ] StepWizardProperties.tsx:61 - Delete button
- [ ] BasePanel.tsx:71 - Close button
- [ ] Toast.tsx:37 - Close button
- [ ] ResizableBottomTray.tsx:170 - Collapse chevron
- [ ] ZoomControls.tsx - Zoom buttons

### Ghost Buttons (25) → `variant="ghost" size="sm"`
- [ ] TabBar.tsx:139 - Tab buttons (with active state handling)
- [ ] StyleMenuItems.tsx:94,112,118,130,139 - Style menu items
- [ ] InlineEditableText.tsx:25-27 - Text formatting toolbar
- [ ] FloatingToolbar.tsx:130 - Toolbar buttons
- [ ] ContainerProperties.tsx:83,139 - Add buttons
- [ ] StepWizardProperties.tsx:72 - Add step button
- [ ] PropertyPanelRegistry.tsx:144 - Panel tabs
- [ ] AnalyticsPanel.tsx:124,131 - Chart type selectors

### Outline Buttons (8) → `variant="outline" size="sm"`
- [ ] FormBuilderV2.tsx:855 - Export button
- [ ] FormBuilderV2.tsx:929,930 - Previous/Next pagination
- [ ] FormBuilderV2.tsx:1006 - Configure button
- [ ] FormBuilderV2.tsx:1044 - Test webhook button
- [ ] AnalyticsPanel.tsx:49,53 - Filter/Export buttons
- [ ] VersionHistoryPanel.tsx:145 - Compare button

### Default/Primary Buttons (6) → `variant="default" size="sm"`
- [ ] EmailsTab.tsx:14 - Add email button
- [ ] TopBar.tsx - Save button
- [ ] VersionHistoryPanel.tsx:139 - Restore button
- [ ] ErrorBoundary.tsx:34 - Try again button

### Special Cases
- [ ] TopBar.tsx - Preview (secondary), Publish (green custom)
- [ ] FormSelector.tsx:75 - Dropdown trigger
- [ ] FloatingToolbar.tsx:150 - Color picker swatches

## Context Manifest

### How Button Components Currently Work in FormBuilderV2

The FormBuilderV2 codebase currently uses a **mixed approach** for buttons, creating inconsistency and missing modern accessibility/dark mode features:

**Current Button Implementations:**

1. **Custom `<button>` elements with CSS classes** - The majority of buttons (~56 total) use native `<button>` elements styled via CSS classes defined in `/src/react/admin/apps/form-builder-v2/styles/form-builder.css`. These classes include:
   - `.btn` - Base button styles with padding, border-radius, font-size
   - `.btn-ghost` - Transparent background, hover to show muted background
   - `.btn-icon` - Icon-only buttons (40x40px fixed size)
   - `.btn-sm`, `.btn-xs` - Size variants
   - `.btn-primary`, `.btn-outline`, `.btn-secondary` - Variant styles
   - `.btn-save`, `.btn-preview`, `.btn-publish` - Special action buttons with color coding

2. **shadcn Button component** - Recently adopted in MobileMenu.tsx and MobileActionBar.tsx (converted 2025-12-08). Located at `/src/react/admin/components/ui/button.tsx`, this component provides:
   - Proper accessibility: `focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2`
   - Disabled state handling: `disabled:pointer-events-none disabled:opacity-50`
   - Automatic SVG sizing: `[&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0`
   - Dark mode support (via Tailwind design tokens)
   - Consistent variants via class-variance-authority (cva)

**The Problem:**

Custom `<button>` elements lack modern features that shadcn Button provides automatically:
- No focus ring accessibility (`focus-visible:ring-ring/50`)
- No disabled state styling (`disabled:pointer-events-none disabled:opacity-50`)
- Inconsistent sizing (some use fixed `w-8 h-8`, others use padding)
- No dark mode support
- No automatic SVG icon sizing
- Manual className composition instead of prop-based variants

**The Goal:**

Replace all custom `<button>` elements with shadcn `<Button>` component while preserving existing behavior and visual appearance. The Button component accepts these props:
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

### Existing Button Usage Patterns in FormBuilderV2

**Files Already Using shadcn Button (DO NOT TOUCH):**
1. `MobileMenu.tsx` - All menu items and trigger use Button with variant="ghost"
2. `MobileActionBar.tsx` - Preview/Save/Publish actions (ghost, default, custom green)
3. `SharePanel.tsx` - Tab buttons and action buttons
4. `FloatingPanel.tsx` - Close/minimize buttons
5. `ElementStylesSection.tsx` - Style node tabs
6. `NodeStyleEditor.tsx` - Reset button
7. `OptionsEditor.tsx` - Add/remove option buttons
8. `SchemaPropertyPanel.tsx` - Property group buttons

**Pattern Analysis from Converted Files:**

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

### Files Containing Custom Buttons to Convert

**Icon Buttons (variant="ghost" size="icon"):**
1. `/src/react/admin/apps/form-builder-v2/components/ui/RightSidebar.tsx:78` - Drawer close button (X icon)
2. `/src/react/admin/apps/form-builder-v2/components/ui/panels/BasePanel.tsx:71` - Panel close button
3. `/src/react/admin/apps/form-builder-v2/components/ui/toast/Toast.tsx:37` - Toast close button
4. `/src/react/admin/apps/form-builder-v2/components/ui/overlays/ResizableBottomTray.tsx:170` - Collapse chevron button
5. `/src/react/admin/apps/form-builder-v2/components/ui/controls/ZoomControls.tsx` - Zoom in/out/reset buttons (4 buttons)
6. `/src/react/admin/apps/form-builder-v2/components/property-panels/container/ContainerProperties.tsx:72,128` - Delete icon buttons (X)
7. `/src/react/admin/apps/form-builder-v2/components/property-panels/layout/StepWizardProperties.tsx:61` - Delete step button

**Ghost Buttons (variant="ghost" size="sm"):**
1. `/src/react/admin/apps/form-builder-v2/components/TabBar.tsx:139` - Tab button component (custom active state logic)
2. `/src/react/admin/apps/form-builder-v2/components/StyleMenuItems.tsx:94,112,118,130,139` - Style submenu items
3. `/src/react/admin/apps/form-builder-v2/components/shared/InlineEditableText.tsx:25-27` - Text formatting toolbar (bold, italic, close)
4. `/src/react/admin/apps/form-builder-v2/components/ui/overlays/FloatingToolbar.tsx:130` - Toolbar format buttons
5. `/src/react/admin/apps/form-builder-v2/components/property-panels/container/ContainerProperties.tsx:83,139` - Add tab/section buttons
6. `/src/react/admin/apps/form-builder-v2/components/property-panels/layout/StepWizardProperties.tsx:72` - Add step button
7. `/src/react/admin/apps/form-builder-v2/components/property-panels/PropertyPanelRegistry.tsx:144` - Panel tab buttons
8. `/src/react/admin/apps/form-builder-v2/components/ui/panels/AnalyticsPanel.tsx:124,131` - Chart type selector buttons

**Outline Buttons (variant="outline" size="sm"):**
1. `/src/react/admin/apps/form-builder-v2/components/ui/panels/AnalyticsPanel.tsx:49,53` - Filter/Export buttons
2. `/src/react/admin/apps/form-builder-v2/components/ui/panels/VersionHistoryPanel.tsx:145` - Compare button

**Default/Primary Buttons (variant="default" size="sm"):**
1. `/src/react/admin/apps/form-builder-v2/tabs/EmailsTab.tsx:14` - Add Email button
2. `/src/react/admin/apps/form-builder-v2/components/ui/panels/VersionHistoryPanel.tsx:139` - Restore button
3. `/src/react/admin/apps/form-builder-v2/components/ui/overlays/ErrorBoundary.tsx:34,41` - Try Again / Refresh buttons

**Special Cases Requiring Custom Logic:**

1. **TopBar.tsx** - Contains 8 custom buttons with complex styling:
   - Lines 279-286: Preview button (secondary variant, responsive text)
   - Lines 287-298: Save button (primary-like, loading state with spinner)
   - Lines 299-306: Publish button (custom green: `bg-green-600 hover:bg-green-700`)
   - Lines 328-341: ToolbarButton component (ghost icon buttons for undo/redo)
   - Lines 355-368: ToolbarToggle component (ghost icon with active state)
   - Lines 405-417: Device selector dropdown trigger
   - Lines 427-444: Device dropdown menu items
   - Lines 451-463: Device frame toggle button

2. **FormSelector.tsx:75** - Dropdown trigger button with custom chevron rotation

3. **FloatingToolbar.tsx:150** - Color picker swatches (8 button elements, special styling)

4. **TabBar.tsx:139** - Tab buttons with sidebar-specific active states:
   - Regular tabs: active = `bg-background text-foreground shadow-sm`
   - Sidebar tabs: active = `bg-primary/10 text-primary` with accent bar

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

### CSS Classes to Remove After Conversion

Once all buttons are converted, these CSS classes become obsolete (marked for removal in `form-builder.css`):

```css
.btn
.btn-sm, .btn-xs
.btn-ghost
.btn-icon
.btn-outline
.btn-primary, .btn-secondary
.btn-save, .btn-preview, .btn-publish
.btn-active
```

The note in `form-builder.css` line 2 already acknowledges this migration:
```
* - .btn, .btn-sm, .btn-xs, .btn-ghost, .btn-outline, .btn-primary, .btn-danger → Using Tailwind inline
```

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

### Build and Verification

**After conversion, verify:**

1. **TypeScript compilation:**
   ```bash
   cd /home/rens/super-forms/src/react/admin
   npm run typecheck
   ```

2. **Build passes:**
   ```bash
   npm run build
   ```

3. **Visual verification via Playwright MCP:**
   - Navigate to: `https://f4d.nl/dev/wp-admin/admin.php?page=super_form_v2`
   - Use temp-login-token from CLAUDE.md
   - Screenshot before/after for visual regression testing
   - Check focus rings (Tab key navigation)
   - Test disabled states
   - Verify hover states

4. **Console errors:**
   - Check browser console for React warnings
   - Verify no PropTypes errors
   - Check for missing aria-labels

### Implementation Order Recommendation

Convert in this order to minimize risk:

1. **Phase 1: Simple icon buttons** (BasePanel, Toast, RightSidebar) - Low complexity
2. **Phase 2: Ghost buttons in panels** (ContainerProperties, StepWizardProperties) - Medium complexity
3. **Phase 3: Action buttons** (EmailsTab, VersionHistoryPanel, ErrorBoundary) - Low complexity
4. **Phase 4: Complex components** (TabBar, TopBar, FormSelector) - High complexity, need careful testing
5. **Phase 5: Special cases** (FloatingToolbar color swatches, ZoomControls) - Custom styling

This phased approach allows early validation that the conversion pattern works before tackling complex components with custom state logic.

## User Notes
- Work on current branch (feature/h-implement-triggers-actions-extensibility)
- MobileMenu.tsx and MobileActionBar.tsx already converted (2025-12-08)

## Work Log
<!-- Updated as work progresses -->
- [2025-12-08] Task created from comprehensive button audit
