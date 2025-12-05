---
name: m-implement-unified-element-tray
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-05
---

# Unified Element Tray for Form & Email Builders

## Problem/Goal

The bottom element tray has several issues:
1. **Positioning bug**: Uses `position: fixed; left: 0` which ignores WordPress admin sidebar, causing tray to extend under the WP menu
2. **Missing Builder tab**: No way to switch back from Settings/other tabs to the Builder/Canvas view
3. **Separate element systems**: Form builder and Email builder have independent element trays - should be unified
4. **No context awareness**: Elements should be filterable by context (form-only, email-only, shared)

## Success Criteria

- [ ] Bottom tray uses `fixed bottom-0 right-0 left-[36px]` for proper WP sidebar respect
- [ ] Tray floats on top of canvas content while scrolling
- [ ] Smooth height transition animation on expand/collapse
- [ ] Canvas has bottom padding to allow scrolling past tray
- [ ] "Builder" tab added to TabBar for returning to canvas view
- [ ] Element tray visible in both Form Builder and Email Builder contexts
- [ ] Elements tagged with context: `form`, `email`, or `both`
- [ ] Tray filters elements based on active builder context
- [ ] Form-only elements hidden when building emails (and vice versa)

## Subtasks

### 1. Fix Bottom Tray Positioning
File: `01-fix-tray-positioning.md`
- Remove `position: fixed; left: 0` from CSS
- Apply Tailwind: `fixed bottom-0 right-0 left-[36px] z-[100]`
- Solid white background, upward shadow for floating effect
- Add bottom padding to canvas for scroll clearance
- Smooth `transition-[height]` for expand/collapse

### 2. Add Builder Tab
File: `02-add-builder-tab.md`
- Add "Builder" or "Canvas" tab to tab schema
- Position as first tab (before Emails, Settings, etc.)
- Icon: Layout or PenTool
- Allows switching back from other tabs

### 3. Unify Element Tray System
File: `03-unify-element-tray.md`
- Create shared element registry with context tags
- Element schema: `{ id, label, icon, context: 'form' | 'email' | 'both' }`
- Render same tray component in both Form and Email builders
- Pass builder context to tray for filtering

### 4. Context-Aware Element Filtering
File: `04-context-filtering.md`
- Filter elements based on active builder context
- Form context: show `form` + `both` elements
- Email context: show `email` + `both` elements
- Visual indicator for context (optional)

## Technical Approach

### Bottom Tray CSS (Tailwind)
```jsx
<div
  className={cn(
    "fixed bottom-0 right-0 left-[36px]",
    "z-[100]",
    "bg-white border-t border-border",
    "shadow-[0_-4px_16px_-2px_rgb(0,0,0,0.1)]",
    "transition-[height] duration-300 ease-out",
    isCollapsed ? "h-10" : "h-[220px]"
  )}
>
```

### Element Context Schema
```typescript
interface TrayElement {
  id: string;
  type: string;
  label: string;
  icon: string;
  category: string;
  context: 'form' | 'email' | 'both';
}

// Examples:
{ id: 'text', context: 'both' }      // Available in both
{ id: 'hidden', context: 'form' }    // Form only
{ id: 'button', context: 'email' }   // Email only (CTA)
```

## Context Manifest

### How the Bottom Tray Currently Works

The Form Builder V2 uses a resizable bottom tray (`ResizableBottomTray`) that contains draggable form elements organized by category. When a user wants to add elements to their form, they interact with this tray which is positioned at the bottom of the screen.

**Current Positioning Problem:**

The tray is positioned using CSS `position: fixed; bottom: 0; left: 0; right: 0` (see line 1578-1581 in `form-builder.css`). The `left: 0` value causes the tray to start at the absolute left edge of the viewport, completely ignoring WordPress's admin sidebar (which is 36px wide when collapsed, 160px when expanded). This means the tray extends underneath the WordPress admin menu on the left side.

**Component Structure:**

The `ResizableBottomTray` component (`/src/react/admin/apps/form-builder-v2/components/ui/overlays/ResizableBottomTray.tsx`) is a controlled component that:
- Accepts `isCollapsed`, `onToggleCollapse`, `children`, `isMobile`, `minHeight`, `maxHeight`, `defaultHeight`, and `onHeightChange` props
- Uses inline styles to set height: `style={{ height: effectiveHeight }}` (line 134)
- Applies CSS classes: `bottom-tray`, `bottom-tray-collapsed`, `bottom-tray-mobile`
- Renders a resize handle (using `GripHorizontal` icon) when not collapsed
- Renders a chevron button for collapse/expand (using `ChevronUp`/`ChevronDown`)
- Implements mouse drag-to-resize functionality with `startY`, `startHeight` refs

**Tray Height Management:**

In `FormBuilderV2.tsx` (lines 1670-1734), the tray height is managed through several state variables:
- `trayHeight` (default: 250px) - tracks current tray height
- `isTrayCollapsed` (default: false) - whether tray is collapsed
- `canvasBottomPadding` (default: 250px) - padding added to canvas bottom for scrolling clearance
- `handleTrayHeightChange` callback - updates trayHeight and determines layout mode (vertical grid vs horizontal scroll based on available height)

The canvas gets bottom padding calculated as: `effectiveFooterHeight + paddingBuffer` where `effectiveFooterHeight = isTrayCollapsed ? 40 : trayHeight` and `paddingBuffer = 100`. This allows users to scroll the canvas content past the tray.

**Element Registry and Categories:**

The form builder defines elements in a large `ELEMENT_CATEGORIES` array (lines 57-210 in FormBuilderV2.tsx). Each category contains:
- `id`: Category identifier ('all', 'basic', 'choice', 'containers', 'advanced', 'upload', 'layout', 'integration')
- `name`: Display name for the category
- `elements`: Array of element definitions with `{ type, label, icon, keywords }`

Elements are filtered based on:
1. Active category (`activeTrayCategory` state)
2. Search query (`searchQuery` state with keyword matching)

The filtered elements are rendered in the tray content area with horizontal scrolling capability (managed via `trayScrollRef`, `canScrollLeft`, `canScrollRight`, `scrollTray` function).

**Current Rendering Location:**

The tray is rendered at the bottom of the FormBuilderV2 component (lines 3828-3965), AFTER the main content area but inside the root flex container. It contains:
- Tray header with category tabs and search input
- Scrollable element grid (either vertical or horizontal layout based on height)
- Scroll buttons for horizontal navigation

**CSS Transition and Animation:**

The tray uses `transition: height 0.2s ease` (line 1343 in form-builder.css). When collapsed, it shows only 40px height (just the chevron button). When expanded, it shows the full height set via state.

### How Tab System Works (For Adding Builder Tab)

The Form Builder V2 uses a **schema-first tab system** where tabs are registered via a central registry and rendered by the `TabBar` component.

**Tab Registration Flow:**

Tabs are defined in `/src/react/admin/schemas/tabs/index.ts` using the `registerTab()` function. Each tab schema includes:
- `id`: Unique identifier (lowercase alphanumeric with dashes)
- `label`: Display text
- `icon`: Lucide icon name (string)
- `position`: Sort order (lower = earlier)
- `lazyLoad`: Whether to lazy load content
- `description`: Tooltip text
- `hidden`: Whether tab is hidden by default

Current registered tabs (by position):
- Canvas (position: 0, id: 'canvas') - Main form building canvas
- Emails (position: 10, id: 'emails')
- Settings (position: 20, id: 'settings')
- Entries (position: 30, id: 'entries')
- Automation (position: 40, id: 'automation')
- Style (position: 50, id: 'style')
- Integrations (position: 60, id: 'integrations')

**Tab Rendering and Active State:**

The `TabBar` component (`/src/react/admin/apps/form-builder-v2/components/TabBar.tsx`) queries the registry via `getTabsSorted()` and filters out the 'canvas' tab (line 57) because canvas is the default/special case - it shows the main canvas area rather than a side panel.

In `FormBuilderV2.tsx` (line 1615), the active tab is managed via `activeTab` state (default: 'canvas'). The component conditionally renders content based on this state:
- When `activeTab === 'canvas'` (line 3317): Renders the main canvas with form elements
- When `activeTab !== 'canvas'` (line 3223): Renders a full-width tab content panel that REPLACES the canvas view
- Specific tabs like 'emails', 'entries', 'automation' render their respective components

**Important Architectural Note:**

The Canvas tab (id: 'canvas') is NOT shown in the TabBar because it's the default view. When users switch to other tabs (Emails, Settings, etc.), the canvas is hidden and replaced with the tab content panel. This means we need to add a "Builder" or "Canvas" tab to the TabBar to allow users to switch BACK to the canvas view from other tabs.

### Email Builder Element System

The email builder has its own independent element system managed through a Zustand store (`useEmailBuilderStore` in `/src/react/admin/components/email-builder/hooks/useEmailBuilder.js`).

**Email Element Types (lines 28-186):**

The store defines an `elementTypes` object with these elements:
- **System Elements** (not deletable): `emailWrapper`, `emailContainer`
- **Layout Elements**: `section`, `columns`, `spacer`
- **Content Elements**: `text`, `image`, `button`, `divider`, `html`
- **Dynamic Elements**: `social`, `formData`

Each element type has:
- `id`: Element identifier
- `name`: Display name
- `icon`: Emoji icon (e.g., '📝', '🖼️', '🔘')
- `defaultProps`: Default property values for new instances
- Special flags like `isSystemElement`, `canDelete`

**Email Builder UI Components:**

The email builder uses `ElementPaletteHorizontal` (`/src/react/admin/components/email-builder/Builder/ElementPaletteHorizontal.jsx`) which organizes elements into categories:
- Layout: section, columns, spacer
- Content: text, image, button, divider, html
- Dynamic: formData, social

The palette renders draggable elements using `@dnd-kit/core` with drag data containing `{ type: 'new-element', elementType }`. Elements are displayed in a horizontal row with category separators.

**Element Palette Icon Mapping (lines 19-30):**

The email builder uses Lucide icons mapped via `ELEMENT_ICONS` object:
- section: Box
- columns: Columns
- text: Type
- image: Image
- button: Square
- divider: Minus
- spacer: MoveVertical
- social: Share2
- formData: Database
- html: Code

**Integration Point:**

The email builder is already integrated into the admin bundle and exported via `/src/react/admin/components/email-builder/index.js`. It's used in:
- Emails Tab (standalone email template editor)
- SendEmailModal (automation workflow action)
- Email Client Builder (preview with client chrome)

### Context-Aware Element Filtering Requirements

To unify the form and email element trays, we need to implement context awareness. Currently:

**Form Elements** (should be tagged with `context: 'form'` or `context: 'both'`):
- Basic inputs: text, email, phone, url, password, number, textarea
- Choice elements: select, multiselect, checkbox, radio, checkbox-cards, radio-cards, toggle
- Advanced: date, datetime, time, location, rating, slider, signature
- Upload: file, image
- Layout: divider, spacer, page-break, heading, paragraph, html-block
- Containers: columns, section, tabs, accordion, step-wizard, repeater
- Integration: webhook, payment, embed, calculation
- Form-specific: hidden (hidden field - only makes sense in forms)

**Email Elements** (should be tagged with `context: 'email'` or `context: 'both'`):
- Layout: section, columns, spacer
- Content: text, image, button, divider, html
- Dynamic: social, formData
- Email-specific: emailWrapper, emailContainer (system elements)

**Shared Elements** (should be tagged with `context: 'both'`):
- Layout primitives: section, columns, spacer, divider
- Content: text, image, heading, paragraph, html/html-block
- These elements work in both form and email contexts with potentially different rendering

**Filtering Logic:**

When the active builder context is:
- `'form'`: Show elements where `context === 'form'` OR `context === 'both'`
- `'email'`: Show elements where `context === 'email'` OR `context === 'both'`

The context should be determined by:
- Current active tab (if user is in Emails tab -> email context)
- Default canvas view -> form context
- Potentially a mode switcher if we want email editing in the main canvas

### Technical Implementation Paths

**1. Fix Bottom Tray Positioning**

Files to modify:
- `/src/react/admin/apps/form-builder-v2/styles/form-builder.css` (lines 1577-1585)

Change from:
```css
.bottom-tray {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  ...
}
```

To Tailwind classes in ResizableBottomTray component:
```tsx
className={cn(
  "fixed bottom-0 right-0 left-[36px]",
  "z-[100]",
  "bg-white border-t border-border",
  "shadow-[0_-4px_16px_-2px_rgb(0,0,0,0.1)]",
  "transition-[height] duration-300 ease-out"
)}
```

The `left-[36px]` accounts for WordPress's collapsed sidebar. When the sidebar is expanded (160px), we may need JavaScript to detect this and adjust, OR accept that the expanded sidebar will overlap the tray slightly (which is acceptable as the sidebar is transparent in that area).

**2. Add Builder Tab**

Files to modify:
- `/src/react/admin/schemas/tabs/index.ts`

Add new tab registration BEFORE importing in FormBuilderV2:
```typescript
export const BuilderTab = registerTab({
  id: 'builder',
  label: 'Builder',
  icon: 'Layout', // or 'PenTool'
  position: 5, // Between Canvas (0) and Emails (10)
  lazyLoad: false,
  description: 'Form building canvas',
});
```

Modify TabBar.tsx to NOT filter out 'builder' (currently filters 'canvas'). When user clicks Builder tab, set `activeTab` to 'canvas' to show the main canvas view.

Alternatively, rename the internal 'canvas' tab to 'builder' for consistency.

**3. Unified Element Registry**

Create a new shared element registry:
- `/src/react/admin/schemas/elements/registry.ts` (new file)
- `/src/react/admin/schemas/elements/types.ts` (new file)

Element schema:
```typescript
interface UnifiedElement {
  id: string;
  type: string;
  label: string;
  icon: LucideIcon; // Unified to Lucide icons
  category: string;
  context: 'form' | 'email' | 'both';
  keywords?: string[];
  defaultProps?: Record<string, any>;
}
```

Migrate form elements from `ELEMENT_CATEGORIES` in FormBuilderV2.tsx and email elements from `elementTypes` in useEmailBuilder.js into this unified registry.

**4. Context-Aware Tray Component**

Files to create/modify:
- `/src/react/admin/components/ui/overlays/UnifiedElementTray.tsx` (new)

Props should include:
```typescript
interface UnifiedElementTrayProps extends ResizableBottomTrayProps {
  context: 'form' | 'email';
  onElementAdd: (elementType: string) => void;
}
```

The component would:
1. Query the unified element registry
2. Filter by context: `elements.filter(e => e.context === context || e.context === 'both')`
3. Render using the existing tray UI patterns (categories, search, horizontal/vertical layout)
4. Handle drag-and-drop for both form and email builder contexts

**5. Integration Points**

Form Builder integration (FormBuilderV2.tsx):
- Replace existing tray rendering (lines 3828-3965) with UnifiedElementTray
- Pass `context="form"` prop
- Keep existing `handleAddElement` function for drop handling

Email Builder integration (would need to modify EmailsTab.tsx or create new email canvas view):
- Render UnifiedElementTray when in email editing mode
- Pass `context="email"` prop
- Connect to email builder's `addElement` function from useEmailBuilder store

### Key Files Summary

**Files to Read/Understand:**
- `/src/react/admin/apps/form-builder-v2/components/ui/overlays/ResizableBottomTray.tsx` - Current tray component
- `/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` - Main builder, element categories (lines 57-210), tray rendering (lines 3828-3965)
- `/src/react/admin/apps/form-builder-v2/styles/form-builder.css` - Bottom tray CSS (lines 1339-1598)
- `/src/react/admin/schemas/tabs/index.ts` - Tab registration
- `/src/react/admin/schemas/tabs/registry.ts` - Tab registry functions
- `/src/react/admin/components/TabBar.tsx` - Tab bar rendering
- `/src/react/admin/components/email-builder/hooks/useEmailBuilder.js` - Email element types (lines 28-186)
- `/src/react/admin/components/email-builder/Builder/ElementPaletteHorizontal.jsx` - Email tray UI pattern

**Files to Modify:**
- `/src/react/admin/apps/form-builder-v2/styles/form-builder.css` - Fix `.bottom-tray` positioning (line 1580: change `left: 0` to `left: 36px` OR remove CSS in favor of Tailwind)
- `/src/react/admin/apps/form-builder-v2/components/ui/overlays/ResizableBottomTray.tsx` - Add Tailwind positioning classes
- `/src/react/admin/schemas/tabs/index.ts` - Add Builder tab registration
- `/src/react/admin/components/TabBar.tsx` - Allow Builder tab to show (modify filter logic on line 57)
- `/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` - Integrate unified tray when ready

**Files to Create:**
- `/src/react/admin/schemas/elements/types.ts` - Unified element type definitions
- `/src/react/admin/schemas/elements/registry.ts` - Unified element registry with context tags
- `/src/react/admin/schemas/elements/index.ts` - Barrel export
- `/src/react/admin/components/ui/overlays/UnifiedElementTray.tsx` - Context-aware tray component

### Architecture Decisions to Confirm

1. **Tab Naming**: Should we rename the internal 'canvas' concept to 'builder', or keep 'canvas' internal and add a separate 'builder' tab that just switches activeTab to 'canvas'?

2. **Email Builder Integration**: Should the unified tray appear in the existing EmailsTab, or do we need a new email canvas view in the main builder area?

3. **Icon Unification**: Email builder currently uses emoji icons (📝, 🖼️). Should we migrate all to Lucide icons for consistency?

4. **Element Type Conflicts**: Some elements exist in both systems with different structures (e.g., 'text', 'image', 'section', 'columns'). How do we handle the property schema differences?

5. **WordPress Sidebar Detection**: Should we detect when WordPress sidebar is expanded (160px) and adjust tray position dynamically, or just use a fixed `left-[36px]` offset?

6. **Drag-and-Drop Unification**: Form builder uses custom drag logic, email builder uses @dnd-kit/core. Should unified tray support both, or migrate form builder to dnd-kit?

## Work Log

- [2025-12-05] Task created
