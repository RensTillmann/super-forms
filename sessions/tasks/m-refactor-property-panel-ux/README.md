---
name: m-refactor-property-panel-ux
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-09
---

# Refactor Property Panel UX/UI

## Problem/Goal

Redesign the FloatingPanel (element properties editor) and GlobalStylesPanel to provide a professional, Elementor/Webflow-level editing experience with improved mobile UX, accessibility, and progressive disclosure for beginners while maintaining power user features.

### Current Issues
- FloatingPanel mixes all properties in a single scrollable view
- GlobalStylesPanel uses drill-down navigation (tedious for frequent switching)
- No clear separation between content, style, and behavior settings
- Limited responsive/state-based styling controls
- Missing progressive disclosure for beginners
- Accessibility gaps (focus states, ARIA labels, keyboard navigation)

### Proposed Solution
Implement a 4-tab structure with improved navigation, collapsible sections, and mobile-optimized UX:
- **Content Tab**: Field-specific data (name, label, placeholder, options)
- **Style Tab**: Visual appearance with Target → State → Device hierarchy
- **Behavior Tab**: Validation, conditional logic, calculations
- **Code Tab**: Custom attributes, CSS, JavaScript events

## Success Criteria

### Core Structure
- [ ] FloatingPanel implements 4-tab structure (Content | Style | Behavior | Code)
- [ ] Content tab renders field-specific sections based on element type
- [ ] Style tab implements Target → State → Device hierarchy

### Style System
- [ ] All style sections are collapsible with summary when collapsed
- [ ] State-based styling works (Normal, Hover, Focus, Active, Disabled, Error)
- [ ] Responsive styling works per breakpoint (Desktop, Tablet, Mobile)
- [ ] Override indicators show which sections/states/devices have customizations

### Mobile UX
- [ ] All panels use Vaul drawer on mobile with proper snap points
- [ ] Touch targets are 44px+ minimum
- [ ] Horizontal chip bars scroll smoothly with scroll indicators

### Accessibility
- [ ] Full keyboard navigation works
- [ ] All interactive elements have visible focus states
- [ ] ARIA labels on icon-only buttons and collapsibles

### Progressive Disclosure
- [ ] Advanced sections collapsed by default
- [ ] "Show more" toggles for advanced options
- [ ] Responsive toggles hidden until "Customize per device" enabled

## Planned Subtasks

### Phase 1: Tab Structure & Content Tab
- Implement 4-tab navigation (Content | Style | Behavior | Code)
- Create Content tab with field-specific sections
- Support different content layouts per element type (text, checkbox, select, etc.)

### Phase 2: Style Tab Redesign
- Implement Style Target selector (Label | Input | Description | Error | Wrapper)
- Add State selector (Normal | Hover | Focus | Active | Disabled | Error)
- Add Device selector (Desktop | Tablet | Mobile) with override indicators
- Create collapsible style sections:
  - Layout & Size (width, height, min/max)
  - Spacing (margin, padding with visual 4-sided editor)
  - Typography (font, size, weight, color, alignment, shadow)
  - Background (color, gradient, image)
  - Border (width, style, radius, color)
  - Box Shadow (presets + custom)
  - Effects (opacity, blend mode, cursor, transitions)
  - Responsive Visibility (show/hide per breakpoint)
  - Position & Display

### Phase 3: Behavior Tab
- Required field toggle with custom error message
- Validation rules builder (min/max length, pattern, email, etc.)
- Conditional logic builder (show/hide based on field values)
- Calculated value formula builder

### Phase 4: Code Tab
- Element ID and CSS classes
- Custom HTML attributes (data-*, aria-*)
- Scoped custom CSS editor
- JavaScript event handlers
- Developer info (element JSON, copy ID)

### Phase 5: Mobile UX Optimization
- Vaul drawer integration for all panels
- Single-column accordion with sticky tab bar
- Larger tap targets (44px+)
- Bottom sheet for pickers
- Sticky header showing current target/state/device
- Remember last open section

### Phase 6: Progressive Disclosure & Beginner UX
- Collapse advanced sections by default
- Show only essential props first (label, placeholder, required)
- "Show more" toggle for advanced options
- Preset styles and templates
- Inline tips and examples
- Guard rails (confirmation for custom CSS/JS)
- Hide responsive toggles behind "Customize per device"

### Phase 7: Accessibility
- Full keyboard navigation
- Visible focus states on all interactive elements
- ARIA labels for icon-only buttons
- Screen reader support for condition builder
- Announce override status
- Support 200% zoom without layout breakage

### Phase 8: Power User Features (Future)
- Design tokens/system picker
- Copy/paste styles between elements
- Apply style to similar fields
- Multi-select editing
- Preset styles (save/load)
- Import/export styles per field
- Undo/redo history scoped to panel
- Keyboard shortcuts

## ASCII Design Reference

### Tab Structure
```
┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐
│  Content  │ │   Style   │ │ Behavior  │ │   Code    │
└───────────┘ └───────────┘ └───────────┘ └───────────┘
```

### Style Tab Navigation Hierarchy
```
┌─────────────────────────────────────────────────────────┐
│ Target: [Label] [Input] [Desc] [Error] [Wrap]  →        │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ State: [Normal│Hover│Focus│Active]                  │ │
│ │ Device: [Desktop] [Tablet] [Mobile]   [Apply All ↻] │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Collapsible Sections Pattern
```
▼ TYPOGRAPHY                                    [Reset]
─────────────────────────────────────────────────────────
  Font Family    [Inter                              ▼]
  Font Size      [14] px     Weight [400│500│600│700]
  Color          [■ #374151]     Line Height [1.5]

▶ SPACING (collapsed)                           [Reset] •
─────────────────────────────────────────────────────────
  M: 0 0 16 0  P: 12 16 12 16         (• = has overrides)
```

## Context Manifest

### How Property Panels Currently Work

The Super Forms form builder uses a **dual-panel architecture** for editing elements. When a user clicks on a form element in the canvas, the `FloatingPanel` component appears (either as a desktop floating panel or mobile bottom drawer via Vaul), allowing the user to edit that specific element's properties and styles.

**FloatingPanel Flow (Desktop)**:
When an element is selected in the canvas (`FormBuilderV2.tsx`), the `handleElementSelect` function is called, which sets `selectedElement` state and calculates the panel position based on the element's bounding rect. The `FloatingPanel` renders at this calculated position as a fixed-positioned div (480px width, max 600px height) with white background, border, and shadow. The panel contains:
1. **Header** - Shows element icon, name, "Schema" badge if schema-driven, delete button, and close button
2. **Content Area** - Scrollable container that renders either:
   - `SchemaPropertyPanel` (for elements with registered schemas) - dynamically generates property inputs based on the element's schema definition
   - Legacy panels (`GeneralProperties`, `ValidationProperties`) as fallback for elements without schemas
3. **ElementStylesSection** - Always included at bottom, allows per-element style overrides

**FloatingPanel Flow (Mobile)**:
On mobile (viewport < 640px), the panel uses Vaul's `<Drawer.Root>` component to render as a bottom sheet. The panel first scrolls the selected element into view at the top of the viewport, then calculates a `mobileSnapPoint` (fraction of viewport height) to show the drawer below the element while keeping the element visible. The drawer has snap points at the calculated position and 0.95 (near full-screen). It renders with a drag handle, the same header/content structure as desktop, and includes ARIA labels for accessibility.

**Close-on-click-outside Pattern**:
The panel uses `useEffect` with `mousedown` event listener on `document` to detect clicks outside the panel ref. There's a 100ms timeout before adding the listener to prevent immediate close on panel open. Escape key also closes the panel via separate `keydown` listener.

**ElementStylesSection Architecture**:
This section is collapsible (using ChevronDown/ChevronRight icons) and shows a count badge when style overrides exist. When expanded, it displays:
1. **Node Type Tabs** - Horizontal scrollable chip bar showing all nodes the element contains (e.g., "Label", "Input", "Description", "Error"). Tabs with overrides show an orange dot indicator and orange ring.
2. **NodeStyleEditor** - When a node tab is active, renders style property controls (font size, weight, color, background, border radius, line height) with link/unlink buttons for each property.

The link/unlink pattern is central to the override system: each property row shows a Link2 or Unlink2 icon. When linked (default), the property uses the global style value from `styleRegistry`. When unlinked, it creates an element-specific override that's stored in `element.styleOverrides[nodeType][property]`. The `usePropertyValues` hook provides both `globalValue` and `resolvedValue` plus `isOverridden` boolean.

### Style System Deep Dive

The style system is **schema-first** with three architectural layers:

**Layer 1: Global Styles (styleRegistry)**
The `styleRegistry` singleton (at `schemas/styles/registry.ts`) manages global default styles for all node types. It's a subscription-based store similar to Zustand, maintaining a `Map<NodeType, Partial<StyleProperties>>`. When global styles change, it increments a version counter and notifies all subscribers. React components subscribe via `useGlobalStyles()` hook which uses `useSyncExternalStore` for optimal performance.

**Layer 2: Node Types and Capabilities**
`NodeType` is a Zod enum defining 13 styleable sub-components: label, description, input, placeholder, error, required, fieldContainer, heading, paragraph, button, divider, optionLabel, cardContainer. Each node has a `NodeStyleCapabilities` object (in `capabilities.ts`) that specifies which CSS properties it supports. For example, `input` supports fontSize, color, padding, border, borderRadius, backgroundColor, width, minHeight - but NOT margin (because it's inside a container). The `placeholder` node only supports color and fontStyle because it inherits other typography from the input.

**Layer 3: Element-to-Node Mapping**
The `ELEMENT_NODE_MAPPING` (in `elementNodes.ts`) defines which nodes each element type contains, in visual order. A text input contains: `['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required']`. A heading element only contains `['heading']`. This mapping drives which node tabs appear in the ElementStylesSection.

**Style Resolution Process**:
When rendering an element, the `useResolvedStyle(elementId, nodeType)` hook:
1. Gets the global style for that node type from `styleRegistry.getGlobalStyle(nodeType)`
2. Retrieves element-specific overrides from Zustand store: `element.styleOverrides?.[nodeType]`
3. Merges them: `{ ...globalStyle, ...overrides }`
4. Returns the final computed style

The `ElementRenderer` component uses this hook for each node it renders, applying the resolved styles via the `styleToCSS()` utility which converts `StyleProperties` (our schema format) to React `CSSProperties`.

**StyleProperties Schema**:
Defined in `types.ts` as a Zod schema with validation. Includes typography (fontSize, fontWeight, color, lineHeight, etc.), spacing (margin/padding as 4-sided `Spacing` objects), border (width per side, style, color, radius), background, and layout properties. All colors are hex strings validated with regex. All numeric values have min/max constraints. This schema serves as the API contract for both UI and REST endpoints.

### GlobalStylesPanel Pattern (For Reference)

The `GlobalStylesPanel` (currently used in the Styles tab) demonstrates a **drill-down navigation pattern** that this task will replace with an inline tab approach. The current flow:

1. **List View**: Shows all 13 node types as Item components (shadcn's semantic list pattern) with icon, name, description, and "Edit" button
2. **Detail View**: When a node is selected, it shows a "Return" button (ChevronLeft icon) and renders style controls grouped by category (Typography, Spacing, Background, Border, Dimensions)
3. **State Management**: Uses `useState` for `selectedNode` (null for list, NodeType for detail)

This works for global styles but creates friction for element editing because you can't quickly compare styles across different nodes. The task proposes keeping all nodes visible as tabs instead of requiring drill-down navigation.

### Property Schema System

Elements can optionally register schemas (in `schemas/core/registry.ts`) that define their configurable properties. The schema includes:

**PropertySchema Structure**:
- `type`: One of 26 property types (string, number, boolean, select, color, icon, conditional_rules, etc.)
- `label`: Human-readable label for the property
- `description`: Help text shown as placeholder or tooltip
- `default`: Default value for new elements
- `required`: Whether property must have a value
- `translatable`: Whether property supports WPML/Polylang translation
- `supportsTags`: Whether property supports dynamic tags like `{field_name}`
- `options`: Array of SelectOption for select/multi_select types
- `min/max/step`: Numeric constraints
- `conditions`: Array of PropertyCondition for conditional visibility (show property only if another property equals/contains/etc a value)

**Categories**:
Properties are organized into 5 categories: general, validation, appearance, advanced, conditions. The `SchemaPropertyPanel` component currently renders these as horizontal tabs when multiple categories have properties. Each category shows all its properties, with visibility controlled by the `conditions` array.

**PropertyRenderer**:
This component maps property types to UI controls. String → Input, number → Input[type=number], boolean → Checkbox, select → Select dropdown, color → color picker + hex input, icon → IconPicker modal, rich_text → Textarea, code → Textarea with monospace font. Complex types (conditional_rules, columns_config, etc.) show "not yet implemented" placeholders.

### Mobile UX Patterns Already in Place

The codebase has established mobile-first patterns using **Vaul** (drawer library):

**useMediaQuery Hook**:
Located at `hooks/useMediaQuery.ts`, provides `useIsMobile()` (< 640px), `useIsTablet()` (640-1023px), `useIsDesktop()` (>= 1024px). Uses native `window.matchMedia()` with event listeners for reactive updates.

**Vaul Drawer Usage**:
The `FloatingPanel` demonstrates the recommended pattern:
```tsx
<Drawer.Root open={true} onOpenChange={callback} modal={false} snapPoints={[0.6, 0.95]}>
  <Drawer.Portal>
    <Drawer.Overlay className="fixed inset-0 bg-black/40 z-50" />
    <Drawer.Content className="fixed bottom-0 left-0 right-0 z-50 rounded-t-xl">
      <div className="mx-auto w-12 h-1.5 bg-gray-300 rounded-full mt-4" />
      <Drawer.Title className="sr-only">...</Drawer.Title>
      <Drawer.Description className="sr-only">...</Drawer.Description>
      {/* Content */}
    </Drawer.Content>
  </Drawer.Portal>
</Drawer.Root>
```

Key features: `modal={false}` allows interaction with canvas, `snapPoints` array defines drawer height presets, sr-only title/description for accessibility, visible drag handle, overlay with transparency.

**RightSidebar Pattern**:
Used for Styles/Themes tabs, the `RightSidebar` component shows how to conditionally render drawer direction:
- Mobile: `direction="bottom"` with `max-h-[85vh]` and `rounded-t-xl`
- Desktop: `direction="right"` with fixed width (400px), `inset-y-0 right-0`, and left border

The sidebar hides the drag handle on desktop (`hideHandle={!isMobile}`) and positions the close button absolutely in top-right corner.

### Tab Patterns and Components

**TabBar Component (Main App Tabs)**:
Located at `components/TabBar.tsx`, this is the schema-driven main navigation. It reads from `getTabsSorted()` which queries the tab registry. Each tab has: id, label, icon (string mapped to Lucide component), description, order, and `sidebar` boolean flag.

Sidebar tabs (Style, Themes) work differently: clicking them toggles the `activeSidebar` state and ensures canvas is active, creating an overlay pattern rather than replacing content. The TabBar uses `Button` components with ghost variant, showing active state with `bg-background shadow-sm` for regular tabs and `bg-primary/10 text-primary` with a left accent bar for active sidebar tabs.

The component handles mobile by hiding completely when `isEditingElement={true}` (when FloatingPanel is open on mobile).

**Radix Tabs (Shadcn)**:
Available at `components/ui/tabs.tsx` for internal tab navigation. Provides:
- `Tabs` (root component wrapping TabsList and TabsContent)
- `TabsList` (horizontal container with muted background, rounded corners)
- `TabsTrigger` (individual tab buttons with data-[state=active] selector for styling)
- `TabsContent` (content panel associated with each tab)

Uses Radix UI primitives for accessibility (ARIA roles, keyboard navigation, focus management).

**SchemaPropertyPanel Tabs**:
Currently implements category tabs using the shadcn Button component (not Radix Tabs). Renders horizontal buttons with conditional styling:
```tsx
<Button
  variant="ghost"
  onClick={() => setActiveCategory(cat)}
  className={activeCategory === cat
    ? 'border-b-2 border-blue-500 text-blue-600'
    : 'border-transparent text-gray-500'}
>
  {categoryLabels[cat]}
</Button>
```

This pattern is simpler than Radix Tabs and works well for basic category switching.

### Collapsible Section Patterns

**Email Builder Accordion**:
Located at `pages/form-builder/emails/components/shared/Accordion.tsx`, uses Framer Motion for animations:
- Button with `flex justify-between` shows title and rotating chevron
- AnimatePresence + motion.div for height animation (0 to auto)
- Uses `initial={{ height: 0 }} animate={{ height: 'auto' }} exit={{ height: 0 }}`
- Gray background on hover, border-b separator

**ElementStylesSection Collapsible**:
Uses simpler approach without animation library:
- Button with ChevronDown/ChevronRight icon that rotates based on state
- Conditional rendering: `{isExpanded && <div>...</div>}`
- Badge showing override count when collapsed
- Reset button that stops propagation: `onClick={(e) => { e.stopPropagation(); ... }}`

The section header pattern includes summary information when collapsed (override count badge), making it useful for progressive disclosure. This is the pattern to extend for the new collapsible style sections.

### Existing Style Controls Components

**SpacingControl** (`components/ui/style-editor/SpacingControl.tsx`):
Sophisticated 4-sided spacing editor with link/unlink functionality. When linked, shows single input applying to all sides. When unlinked, shows 3x3 grid with inputs for top/right/bottom/left. Supports color themes (orange for margin, blue for padding, purple for border). Has visual feedback with colored backgrounds and borders. This component should be reused as-is in the new layout.

**ColorControl** (`components/ui/style-editor/ColorControl.tsx`):
Shows color swatch + hex input with validation. Should be reused.

**ButtonGroup** (`components/ui/button-group.tsx`):
Compound component for visually grouped controls. Supports horizontal/vertical orientation. Removes borders between adjacent buttons and adjusts border-radius. Includes ButtonGroupText for non-interactive labels with matching visual style. Used throughout GlobalStylesPanel for number input + "px" label combinations:
```tsx
<ButtonGroup>
  <Input type="number" value={14} className="w-16" />
  <ButtonGroupText>px</ButtonGroupText>
</ButtonGroup>
```

### Store and State Management

**useElementsStore** (Zustand):
The main store for all form elements. Key methods:
- `items`: Record<elementId, element> - all elements by ID
- `setStyleOverride(elementId, nodeType, property, value)` - creates element-specific style override
- `removeStyleOverride(elementId, nodeType, property)` - removes override, reverts to global
- `clearNodeStyleOverrides(elementId, nodeType)` - removes all overrides for a specific node
- `clearAllStyleOverrides(elementId)` - removes ALL style overrides for an element

The store maintains referential equality for performance - it only creates new objects when actual changes occur.

**Local State in FloatingPanel**:
- `mobileSnapPoint` - dynamically calculated drawer height (0.4 to 0.95)
- `isScrolled` - boolean flag preventing render until scroll animation completes
- No local state for property values - all property changes call `onPropertyChange` callback immediately, updating the Zustand store

### Override Indicators Pattern

The current ElementStylesSection shows overrides via:
1. **Section-level badge**: Orange badge showing total override count in header
2. **Node-level indicator**: Orange dot after node name + orange ring around button
3. **Reset buttons**: "Reset all" in section header, individual reset in NodeStyleEditor (via unlink icon)

The task proposes extending this to show which style SECTIONS have overrides (Typography section shows orange indicator if any typography property is overridden), and which STATES and DEVICES have customizations.

### State-Based and Responsive Styling (Future Extension)

Currently NOT implemented, but the architecture must support:

**State-based styling** would extend the styleOverrides structure:
```typescript
styleOverrides: {
  [nodeType]: {
    [state]: { // 'normal' | 'hover' | 'focus' | 'active' | 'disabled' | 'error'
      [property]: value
    }
  }
}
```

**Responsive styling** would add another nesting level:
```typescript
styleOverrides: {
  [nodeType]: {
    [state]: {
      [device]: { // 'desktop' | 'tablet' | 'mobile'
        [property]: value
      }
    }
  }
}
```

The proposed UI navigation hierarchy (Target → State → Device) reflects this nested structure. This task focuses on the UI structure; actual state/responsive styling implementation comes later.

### Accessibility Patterns

**Existing Accessibility Features**:
- Vaul Drawer includes Drawer.Title and Drawer.Description (can be sr-only)
- Button components support title attribute for tooltips
- Label components properly associate with inputs via htmlFor
- Radix primitives handle ARIA roles and keyboard nav automatically
- TabBar uses role="tablist" and aria-controls

**Gaps to Address**:
- Icon-only buttons missing aria-label (delete button in FloatingPanel header has title but no aria-label)
- Focus states not visually distinct (relies on default browser focus ring)
- No visible focus indicators on custom controls
- Collapsible sections don't announce expanded/collapsed state to screen readers
- Override indicators are visual-only (orange dots not announced)

### Progressive Disclosure Requirements

The task emphasizes **beginner-friendly defaults with power-user features hidden**. Current challenges:

1. **All properties shown equally**: Schema-driven panel shows every property, overwhelming beginners
2. **No grouping**: Properties listed in flat order within each category
3. **Style controls always visible**: ElementStylesSection starts collapsed but shows all nodes when expanded
4. **No presets**: Users must configure every style property manually

The proposed solution involves:
- Collapsing "Advanced" properties behind "Show more" toggle
- Collapsing style sections by default (Typography, Spacing, Border, etc.)
- Hiding responsive controls until "Customize per device" is enabled
- Providing preset styles and templates (future phase)

### File Locations and Naming Conventions

**Component Location Strategy**:
- Shared UI primitives: `src/react/admin/components/ui/` (shadcn components)
- Form builder specific: `src/react/admin/apps/form-builder-v2/components/`
- Property panels: `src/react/admin/apps/form-builder-v2/components/property-panels/`
- Style editors: `src/react/admin/components/ui/style-editor/`

**Naming Patterns**:
- React components: PascalCase with descriptive names (FloatingPanel, ElementStylesSection)
- Hooks: camelCase with "use" prefix (useResolvedStyle, useMediaQuery)
- Utility functions: camelCase (styleToCSS, getElementNodes)
- Types: PascalCase matching schema names (NodeType, StyleProperties)

**Import Path Convention**:
Components use relative imports (../../../) due to flat structure. The codebase doesn't use path aliases. Schema imports go through index files: `import { NodeType } from '../../../../schemas/styles'` (re-exported from schemas/styles/index.ts).

### Implementation Constraints

**Must Not Break**:
- Existing schema system - elements must continue using registered schemas
- Style resolution - global + override merging logic is core to performance
- Mobile drawer behavior - element scrolling and snap point calculation
- Zustand store structure - other components depend on current store shape

**Performance Considerations**:
- styleRegistry uses subscription system to avoid unnecessary re-renders
- useResolvedStyle memoizes merged styles with useMemo
- Store updates are batched by Zustand automatically
- Avoid reading from Zustand in render functions - use selectors

**TypeScript Requirements**:
- All new components must be .tsx with proper typing
- Avoid `any` types - use `unknown` and type guards if needed
- Style properties use Zod-inferred types (StyleProperties, NodeType, etc.)
- Props interfaces should extend React component props when appropriate

### Technical Reference

#### Key Component Interfaces

```typescript
// FloatingPanel
interface FloatingPanelProps {
  element: {
    id: string;
    type: string;
    label?: string;
    icon?: React.ComponentType<{ size?: number }>;
    properties?: Record<string, unknown>;
    styleOverrides?: Record<string, Partial<StyleProperties>>;
  };
  position: { x: number; y: number };
  onClose: () => void;
  onPropertyChange: (propertyName: string, value: unknown) => void;
  onDelete: () => void;
}

// ElementStylesSection
interface ElementStylesSectionProps {
  elementId: string;
  elementType: string;
  styleOverrides?: Record<string, Partial<StyleProperties>>;
  onOverrideChange: (nodeType: string, property: string, value: unknown) => void;
  onResetToGlobal: (nodeType?: string) => void;
}

// SchemaPropertyPanel
interface SchemaPropertyPanelProps {
  elementType: string;
  properties: Record<string, unknown>;
  onPropertyChange: (propertyName: string, value: unknown) => void;
  categories?: PropertyCategory[];
}
```

#### Style System Types

```typescript
// From schemas/styles/types.ts
type NodeType = 'label' | 'description' | 'input' | 'placeholder' | 'error'
  | 'required' | 'fieldContainer' | 'heading' | 'paragraph' | 'button'
  | 'divider' | 'optionLabel' | 'cardContainer';

interface StyleProperties {
  fontSize?: number;
  fontFamily?: string;
  fontWeight?: '400' | '500' | '600' | '700';
  color?: string;
  margin?: Spacing;
  padding?: Spacing;
  border?: Spacing;
  borderColor?: string;
  borderRadius?: number;
  backgroundColor?: string;
  width?: string;
  minHeight?: number;
  lineHeight?: number;
  // ... see types.ts for complete list
}

interface Spacing {
  top: number;
  right: number;
  bottom: number;
  left: number;
}
```

#### Configuration

**Breakpoints** (from useMediaQuery):
- Mobile: < 640px
- Tablet: 640px - 1023px
- Desktop: >= 1024px

**Touch Targets** (from task requirements):
- Minimum 44px for mobile (iOS/Android HIG standard)
- Current buttons: h-10 (40px) default, h-9 (36px) sm variant - NEED to increase on mobile

**Panel Dimensions**:
- Desktop FloatingPanel: 480px width, max 600px height
- Mobile Drawer: full width, 40-95vh height (snap points)
- RightSidebar: 400px width (desktop), 85vh height (mobile)

#### File Paths for Implementation

**Main components to modify**:
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/ElementStylesSection.tsx`
- `/home/rens/super-forms/src/react/admin/components/settings/GlobalStylesPanel.tsx`

**Components to create** (Phase 2):
- `property-panels/style/StyleTabContent.tsx` - main container for Target/State/Device selectors
- `property-panels/style/StyleSection.tsx` - reusable collapsible section component
- `property-panels/style/TargetSelector.tsx` - chip bar for Label/Input/etc selection
- `property-panels/style/StateSelector.tsx` - chip bar for Normal/Hover/Focus/etc (future)
- `property-panels/style/DeviceSelector.tsx` - chip bar for Desktop/Tablet/Mobile (future)

**Reuse existing components**:
- `components/ui/button.tsx` - shadcn Button (use sm size for tabs)
- `components/ui/tabs.tsx` - Radix Tabs for main 4-tab structure
- `components/ui/drawer.tsx` - Vaul wrapper for mobile
- `components/ui/style-editor/SpacingControl.tsx` - 4-sided spacing editor
- `components/ui/style-editor/ColorControl.tsx` - color picker
- `components/ui/button-group.tsx` - grouped controls with units

**Schema locations**:
- `/home/rens/super-forms/src/react/admin/schemas/styles/` - style system types and registry
- `/home/rens/super-forms/src/react/admin/schemas/core/` - property schema types
- `/home/rens/super-forms/src/react/admin/schemas/tabs/` - tab registry

## User Notes
- Stay on current branch (feature/h-implement-triggers-actions-extensibility)
- This is an incremental refactor, can be done in phases
- Mobile UX is high priority
- Accessibility must not be an afterthought

## Work Log
<!-- Updated as work progresses -->
- [2025-12-09] Task created based on UX/UI discussion and Codex review
