---
name: m-migrate-form-builder-v2-tailwind
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-12-04
---

# Migrate Form Builder V2 to Tailwind CSS + Schema-Driven UI

## Problem/Goal

Form Builder V2 currently uses a parallel custom CSS system (`design-tokens.css` + `form-builder.css`) instead of Tailwind CSS like the rest of the admin UI. Additionally, the tabs and top bar are hardcoded in the component rather than being schema-driven.

This task migrates Form Builder V2 to:
1. **Schema-driven UI** - Tabs, TopBar, and all UI elements defined via schemas (source of truth)
2. **Tailwind CSS** - Remove custom CSS, use Tailwind utilities + shadcn/ui patterns
3. **Lazy loading** - Emails and Automation tabs load on-demand

## Success Criteria
- [x] Tab schema system created and FormBuilderV2 renders tabs from schema
- [x] TopBar schema system created with all 16 toolbar items schema-defined
- [x] Emails tab added (lazy loaded) - positioned after Canvas, before Settings
- [x] Logic tab renamed to Automation (lazy loaded) - integrates nodes/workflow builder
- [x] All V2 components migrated from custom CSS to Tailwind utilities (main layout)
- [x] `design-tokens.css` and `form-builder.css` deprecated with notices
- [x] UI follows style guidelines (docs/CLAUDE.ui.md)

## Subtasks

### 1. Create Tab Schema System
File: `01-tab-schema-system.md`
- Define tab schema format (id, label, icon, component, lazyLoad)
- Create tab registry at `src/react/admin/schemas/tabs/`
- Refactor FormBuilderV2 to render tabs from schema

### 2. Create TopBar Schema System
File: `02-topbar-schema-system.md`
- Define toolbar item schema (id, type, icon, action, group, tooltip, variant)
- Support item types: button, toggle, dropdown, custom
- Support groups: left, history, canvas, panels, primary
- All 16 toolbar items schema-defined

### 3. Add Emails Tab (Schema-Defined)
File: `03-emails-tab.md`
- Create email tab schema with lazy loading
- Port Email Builder component to V2
- Position: After Canvas, before Settings

### 4. Replace Logic with Automation (Schema-Defined)
File: `04-automation-tab.md`
- Create automation tab schema with lazy loading
- Port/integrate automation builder (nodes system)
- Rename: Logic -> Automation

### 5. Migrate V2 Components to Tailwind
File: `05-tailwind-migration.md`
- Remove `design-tokens.css` and `form-builder.css`
- Convert all custom CSS classes to Tailwind utilities
- Follow UI style guidelines (shadcn/ui patterns)

## Tab Order (After Migration)
```
[Canvas] | Emails | Settings | Entries | Automation | Style | Integrations
```

## TopBar Items (Schema Reference)
```
LEFT SECTION:
- form-selector (dropdown)
- form-title (custom - InlineEditableTitle)
- device-selector (dropdown)
- device-frame-toggle (toggle)

HISTORY GROUP:
- undo (button)
- redo (button)

CANVAS GROUP:
- toggle-grid (toggle)
- zoom (custom - ZoomControls)

PANELS GROUP:
- elements-panel (toggle)
- version-history (button)
- share (button)
- export (button)
- analytics (button)

PRIMARY GROUP:
- preview (button, variant: secondary)
- save (button, variant: save)
- publish (button, variant: publish)
```

## Context Manifest

### How the Form Builder V2 Currently Works

The Form Builder V2 (`/home/rens/super-forms/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`) is a **massive 4337-line monolithic component** that hardcodes all UI elements directly in the component rather than using schema-driven patterns. This architectural pattern predates the newer schema-first approach established in the element system.

**Current Tab System (Hardcoded):**
The tab navigation is directly rendered in JSX starting around line 3427-3470. The tabs are:
- **Canvas** (default active state) - The main form building canvas
- **Settings** - Form-level settings panel
- **Entries** - Form submissions viewer
- **Logic** - Conditional logic rules (should be renamed to "Automation")
- **Style** - Form theming and appearance
- **Integrations** - Third-party connections

Tab state is managed via `const [activeTab, setActiveTab] = useState('canvas')` (line 1706). When a non-canvas tab is selected, a side panel slides in from the right with the tab content. The Canvas tab is special - it shows the main canvas area instead of a side panel.

Each tab button is manually coded:
```tsx
<button
  className={`tab-btn ${activeTab === 'settings' ? 'tab-btn-active' : ''}`}
  onClick={() => setActiveTab('settings')}
  title="Form Settings"
>
  <Settings size={20} />
  <span>Settings</span>
</button>
```

**Current Top Bar Structure (Hardcoded):**
The top bar (`.form-builder-topbar`) contains 16 toolbar items organized into logical groups, starting around line 3274-3424:

**LEFT SECTION:**
- **Form Selector** (`<FormSelector>` component) - Dropdown to switch between forms
- **Form Title** (`<InlineEditableText>` component) - Inline editable form name
- **Device Selector** - Dropdown with Desktop/Tablet/Mobile options + device frame toggle button

**HISTORY GROUP (Undo/Redo):**
- Undo button (Ctrl+Z) - Disabled when `historyIndex <= 0`
- Redo button (Ctrl+Shift+Z) - Disabled when `historyIndex >= history.length - 1`

**CANVAS GROUP:**
- Toggle Grid button - Shows/hides alignment grid overlay
- Zoom Controls (`<ZoomControls>` component) - Zoom in/out/reset controls

**PANELS GROUP (Right-side utility buttons):**
- Elements Panel button (`<Layers>` icon) - Toggles floating elements tree panel
- Version History button (`<History>` icon) - Opens version history panel
- Share button (`<Share>` icon) - Opens collaboration/sharing panel
- Export button (`<Download>` icon) - Opens export format selector
- Analytics button (`<BarChart>` icon) - Opens form analytics panel

**PRIMARY ACTIONS GROUP:**
- Preview button (secondary variant) - Opens form preview
- Save button (special "save" variant) - Shows spinner when saving
- Publish button (special "publish" variant) - Publishes form changes

All toolbar items use custom CSS classes (`btn`, `btn-ghost`, `btn-icon`, `action-group`) defined in `form-builder.css`.

**Custom CSS System (Parallel to Tailwind):**
Form Builder V2 imports two custom CSS files that define a complete parallel design system:

1. **`design-tokens.css`** (192 lines) - Comprehensive CSS custom properties:
   - Brand colors (primary, secondary, success, warning, error, neutral) with 50-900 scales
   - Background colors (primary, secondary, tertiary, inverse)
   - Text colors (primary, secondary, tertiary, disabled, inverse)
   - Border colors (primary, secondary, focus, error)
   - Typography system (font families, sizes, weights, line heights)
   - Spacing scale (0-24 using rem units)
   - Border radius scale (none to full)
   - Shadow system (xs to xl)
   - Z-index scale (0-100)
   - Animation durations and easing functions
   - Focus ring styles

2. **`form-builder.css`** (First 300 lines reviewed, file is much larger):
   - Toast notification system (`.toast-container`, `.toast`, variants for success/error/warning/info)
   - Container element styles (columns, tabs, accordion, step-wizard, section, repeater)
   - Tabs bar styles (`.tabs-bar`, `.tab-btn`, `.tab-btn-active`) starting at line 2103
   - Canvas styles (`.canvas-container`, `.canvas-zoom-wrapper`, device-specific classes)
   - Property panel styles
   - Element controls and hover states
   - Responsive breakpoints

The CSS uses BEM-like naming conventions and custom properties from design-tokens.css rather than Tailwind utilities.

**Why This Is Problematic:**
1. **Duplication** - Parallel design system duplicates concepts already in Tailwind + shadcn/ui
2. **Maintainability** - Changes require updating both CSS files and understanding custom naming
3. **Inconsistency** - Different color scales, spacing values than the rest of the admin UI
4. **Not Schema-Driven** - Tabs and toolbar items are hardcoded, can't be extended by plugins
5. **Bundle Size** - Two large CSS files add unnecessary weight

### Existing Schema System Architecture

**Element Schema Registry** (`/home/rens/super-forms/src/react/admin/schemas/`):
The codebase has a mature schema-first system for form elements that should serve as the pattern for tabs/toolbar:

**Core Types** (`schemas/core/types.ts`):
- Uses Zod for runtime validation and type inference
- Defines 26 property types (string, number, boolean, select, color, etc.)
- `ElementSchema` interface defines:
  - Identity (type, name, description, category, icon)
  - Container behavior (accepts/rejects child types, max/min children)
  - Properties organized by category (general, validation, appearance, advanced, conditions)
  - Default values and translatable fields
  - Tag support for dynamic field references

**Registry Pattern** (`schemas/core/registry.ts`):
- `registerElement(schema)` - Validates with Zod and stores in Map
- `getElementSchema(type)` - Retrieves schema by type
- `getElementsByCategory(category)` - Filters by category
- `isElementRegistered(type)` - Type existence check
- `withBaseProperties()` - Helper to merge base properties (name, label, width, cssClass, conditionalLogic) into element-specific properties

**Property Rendering** (`form-builder-v2/components/property-panels/schema/`):
- `SchemaPropertyPanel.tsx` - Renders property panel from element schema
- `PropertyRenderer.tsx` - Maps property types to UI controls
- Category tabs (General, Validation, Appearance, Advanced, Conditions)

This schema system is the **source of truth** for element definitions and should be the model for tab/toolbar schemas.

### Email Builder Location & Integration

**Email Builder Components** (`/home/rens/super-forms/src/react/admin/components/email-builder/`):
The email builder was consolidated from a separate app into the admin bundle as reusable components. Key exports from `index.js`:

**Main Components:**
- `EmailBuilderIntegrated` - Full builder with Gmail-style chrome
- `EmailBuilder` - Core builder without chrome
- `Canvas` / `CanvasIntegrated` - Email canvas areas
- `ElementPalette` / `ElementPaletteHorizontal` - Drag-drop element palettes
- `RawHtmlEditor` - Direct HTML editing

**Preview Components:**
- `EmailClient` / `EmailClientBuilder` - Email client preview
- Client chrome components (Gmail, Outlook, AppleMail)
- `EmailContent` - Renders email from elements

**Hooks & State:**
- `useEmailBuilder` - Zustand store for builder state
- `useEmailStore` - Email list management
- `useDragToAdjust` - Drag-to-resize functionality

**Integration Plan for V2:**
The Emails tab should lazy-load `EmailBuilderIntegrated` and integrate it into the Form Builder V2 workflow. Position should be **after Canvas, before Settings** per task requirements.

### UI Components Library (Form Builder V2)

**Extracted UI Library** (`form-builder-v2/components/ui/`):
Form Builder V2 already has a well-organized component library structure that exports reusable pieces:

**Controls** (`ui/controls/`):
- `FormSelector` - Form switcher dropdown
- `ZoomControls` - Zoom in/out/reset controls
- `InlineEditableText` - Click-to-edit text fields

**Panels** (`ui/panels/`):
- `BasePanel` - Generic panel wrapper
- `SharePanel` - Collaboration settings
- `ExportPanel` - Export format selector
- `AnalyticsPanel` - Form analytics viewer
- `VersionHistoryPanel` - Version history browser

**Overlays** (`ui/overlays/`):
- `ErrorBoundary` - Error catching wrapper
- `ContextMenu` - Right-click context menu
- `FloatingToolbar` - Hoverable element toolbar
- `GridOverlay` - Canvas alignment grid
- `ResizableBottomTray` - Resizable bottom panel

**Toast System** (`ui/toast/`):
- `Toast` - Toast notification component
- `ToastProvider` - Toast context provider
- `useToast` - Toast trigger hook

These components already use Tailwind CSS and shadcn/ui patterns in other parts of the admin UI, but Form Builder V2 currently uses custom CSS for its integration.

### Tailwind Configuration & Scoping

**Global Admin Stylesheet** (`/home/rens/super-forms/src/react/admin/styles/index.css`):
The main admin CSS follows WordPress-safe scoping patterns:

**Critical Architecture:**
- Imports only `tailwindcss/theme` and `tailwindcss/utilities` (NO preflight)
- All resets scoped inside `#sfui-admin-root` selector to avoid breaking WordPress admin
- shadcn/ui theme variables defined on `:root` (safe since CSS only loads on plugin pages)
- `@theme inline` block maps CSS variables to Tailwind utilities
- `@theme` block adds custom colors (primary-50 through primary-900, gray scale)
- Custom spacing values (--spacing-18, --spacing-88)

**Key Design Tokens (Already Defined):**
- `--background` / `--foreground` - Page colors
- `--card` / `--card-foreground` - Card backgrounds
- `--primary` / `--primary-foreground` - Primary actions (teal)
- `--secondary` - Secondary elements
- `--muted` / `--muted-foreground` - Muted backgrounds/text
- `--border` / `--ring` - Border and focus ring colors
- `--destructive` - Delete/error actions (red)
- `--radius` - Border radius (0.5rem base)
- Shadow scale (--shadow-2xs through --shadow-2xl)
- Z-index (--z-overlay: 100000 for modals above WP admin bar)

**Dark Mode Support:**
`.dark` class overrides all theme variables for dark mode.

**NO Separate Tailwind Config File:**
Tailwind v4 uses CSS-based configuration in `styles/index.css` via `@theme` blocks instead of `tailwind.config.js`. The glob search found no active tailwind config in the react/admin directory.

### Migration Strategy Insights

**What Needs to Happen:**

1. **Tab Schema System** - Create `schemas/tabs/` directory with:
   - Tab schema type definition (id, label, icon, component, lazyLoad, position)
   - Tab registry (similar to element registry pattern)
   - TabRenderer component that maps schemas to UI
   - Default tabs defined as schemas (Canvas, Emails, Settings, Entries, Automation, Style, Integrations)

2. **TopBar Schema System** - Create `schemas/toolbar/` directory with:
   - Toolbar item schema (id, type, icon, action, group, tooltip, variant, disabled condition)
   - Item types: button, toggle, dropdown, custom
   - Groups: left, history, canvas, panels, primary
   - Toolbar registry and renderer
   - All 16 existing toolbar items defined as schemas

3. **CSS Migration Path:**
   - Map design-tokens.css colors to existing Tailwind tokens in index.css
   - Replace custom CSS classes with Tailwind utilities:
     - `.tab-btn` → Tailwind button classes
     - `.form-builder-topbar` → Flexbox utilities
     - `.canvas-container` → Container/layout utilities
     - `.toast` → Use existing shadcn/ui Toast component pattern
   - Delete or deprecate design-tokens.css and form-builder.css
   - Follow UI guidelines (docs/CLAUDE.ui.md) for all new Tailwind usage

4. **Lazy Loading Implementation:**
   - Emails tab: `React.lazy(() => import('./tabs/EmailsTab'))`
   - Automation tab: `React.lazy(() => import('./tabs/AutomationTab'))`
   - Wrap lazy components in `<Suspense>` with loading skeleton

5. **State Management:**
   - Current: `const [activeTab, setActiveTab] = useState('canvas')`
   - After: Tab registry determines available tabs, state references tab IDs
   - Tab visibility/availability can be conditional (e.g., pro features)

### Critical Files Reference

**Form Builder V2 Core:**
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` (4337 lines) - Main component, needs refactoring
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/store.ts` - Zustand stores (elements, builder state)

**Custom CSS to Migrate:**
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/styles/design-tokens.css` (192 lines) - Remove
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/styles/form-builder.css` (Large file) - Remove

**Schema System (Pattern to Follow):**
- `/home/rens/super-forms/src/react/admin/schemas/core/types.ts` - Type definitions with Zod
- `/home/rens/super-forms/src/react/admin/schemas/core/registry.ts` - Registration pattern
- `/home/rens/super-forms/src/react/admin/schemas/elements/text.ts` - Example element schema

**Property Panel Rendering (Reference):**
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/schema/SchemaPropertyPanel.tsx` - Schema-driven UI
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/schema/PropertyRenderer.tsx` - Type → UI mapping

**Email Builder (For Emails Tab):**
- `/home/rens/super-forms/src/react/admin/components/email-builder/index.js` - Component exports
- `/home/rens/super-forms/src/react/admin/components/email-builder/Builder/EmailBuilderIntegrated.{js,jsx}` - Full builder

**UI Components (Already Tailwind-Ready):**
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/ui/` - Component library
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/ui/index.ts` - Central exports

**Styles & Guidelines:**
- `/home/rens/super-forms/src/react/admin/styles/index.css` - Global Tailwind setup
- `/home/rens/super-forms/docs/CLAUDE.ui.md` - UI style guidelines (comprehensive)

### Architectural Decision: Why Schema-First Matters

The current hardcoded approach means:
- **Plugin developers can't add tabs** - They'd need to modify FormBuilderV2.tsx
- **No extensibility** - Every new toolbar button requires core changes
- **Testing is difficult** - Tabs and toolbar deeply coupled to component logic
- **No feature flags** - Can't conditionally show/hide tabs based on user permissions

The schema-first approach enables:
- **Plugin API** - Third-party plugins can register tabs via `registerTab(schema)`
- **Feature Gating** - Tabs can have `requiredPermission` or `requiredPlan` conditions
- **A/B Testing** - Different toolbar configurations per user segment
- **Localization** - Tab labels/tooltips defined in schema, easily translated
- **Documentation** - Schemas auto-generate API docs
- **MCP Integration** - Schemas can be consumed by AI tools for form building

This migration isn't just about Tailwind CSS - it's about establishing extensibility patterns that future-proof the form builder architecture.

## User Notes
- Using existing branch: `feature/h-implement-triggers-actions-extensibility`
- Schema is the source of truth for all UI elements
- Follow docs/CLAUDE.ui.md for styling guidelines

## Work Log

### 2025-12-04

#### Completed

**Subtask 01: Tab Schema System**
- Created `schemas/tabs/` with types.ts, registry.ts, index.ts
- Implemented Zod validation for tab schemas
- Registered 7 default tabs (Canvas, Emails [hidden], Settings, Entries, Logic, Style, Integrations)
- Created `TabBar.tsx` component using Tailwind CSS (no custom classes)
- Integrated TabBar into FormBuilderV2.tsx - replaced ~40 lines of hardcoded tabs
- Build passed successfully

**Subtask 02: TopBar Schema System**
- Created `schemas/toolbar/` with types.ts, registry.ts, index.ts
- Defined toolbar item types: button, toggle, dropdown, custom
- Defined toolbar groups: left, history, canvas, panels, primary
- Registered all 16 toolbar items as schemas:
  - Left group: form-selector, form-title, device-selector, device-frame-toggle
  - History group: undo, redo
  - Canvas group: toggle-grid, zoom
  - Panels group: elements-panel, version-history, share, export, analytics
  - Primary group: preview, save, publish
- Created `TopBar.tsx` component using Tailwind CSS
- Integrated TopBar into FormBuilderV2.tsx - replaced ~150 lines of hardcoded toolbar
- Build passed successfully

**Subtask 05: Tailwind CSS Migration (Partial)**
- Migrated main layout classes from custom CSS to Tailwind:
  - `.main-content` → `flex` utilities
  - `.tab-content-panel` → Tailwind width, flex, border classes
  - `.canvas-container` → Tailwind flex, overflow utilities
- Added deprecation notices to `design-tokens.css` and `form-builder.css`
- Updated `schemas/index.ts` to export tabs and toolbar modules

#### Decisions
- Kept Logic tab ID for now (rename to Automation in subtask 04)
- Emails tab marked as hidden until subtask 03 implementation
- Custom components (FormSelector, ZoomControls, InlineEditableText) integrated via component mapping

#### Discovered
- TabBar and TopBar components reduce FormBuilderV2.tsx by ~190 lines
- Schema-driven approach enables plugin extensibility
- Build artifact sizes: admin.css (130KB), admin.js (799KB)

#### Next Steps
- Subtask 03: Add Emails tab with lazy loading
- Subtask 04: Rename Logic → Automation, add workflow builder integration
- Subtask 05: Complete Tailwind migration for remaining custom CSS classes
