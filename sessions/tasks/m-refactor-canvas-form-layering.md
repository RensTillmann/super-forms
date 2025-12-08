---
name: m-refactor-canvas-form-layering
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-08
---

# Refactor Canvas/Form Layering System

## Problem/Goal
The current form builder canvas has visual elements (shadow-sm, border) that blur the distinction between "builder UI chrome" and "actual form preview." Users may be confused about what styling represents their form vs the builder workspace.

Current structure:
```
Canvas Container (border border-border shadow-sm) ← Builder UI chrome
  └── desktop-form-container (flex centering)
        └── form-wrapper (formWrapperSettings styles)
              └── Form Elements
```

Goal: Create a clear visual hierarchy that shows users exactly how their form will look on the frontend.

Proposed layered model:
```
[page-background] > [form-wrapper] > [form-content] > [form-elements]
```

## Success Criteria
- [ ] Canvas workspace is visually neutral (no border/shadow that could be confused with form styling)
- [ ] Form wrapper is clearly distinct as "this is your actual form"
- [ ] Optional: Page background preview layer for context
- [ ] Users understand what is builder chrome vs form preview

## Context Manifest

### How the Canvas/Form Layering Currently Works

**The Visual Hierarchy Problem:**

When you open the Form Builder V2, you see what appears to be a "form preview" but it's actually a layered system where builder chrome (UI elements) and actual form preview are visually intermingled in a confusing way. Here's the current structure:

At line 3110-3133 in FormBuilderV2.tsx, the canvas area is rendered with this hierarchy:

```tsx
<div className="flex-1 flex flex-col overflow-auto bg-muted/30 p-4">  // Outer container
  <div className="flex-1 flex items-start justify-center origin-top" style={{transform: `scale(${zoom})`}}>  // Zoom wrapper
    <div className={cn(
      'w-full bg-background rounded-lg shadow-sm border border-border relative mx-auto',  // ← THE PROBLEM
      devicePreview === 'tablet' && 'max-w-[768px]',
      devicePreview === 'mobile' && 'max-w-[375px]',
      devicePreview === 'desktop' && 'max-w-[1200px]',
      showDeviceFrame && 'canvas-framed'
    )}>
      {/* This is the canvas container - it has shadow-sm and border border-border */}

      <GridOverlay isVisible={showGrid} />

      {/* Inside here is the form-wrapper at line 3255 (device mode) or 3465 (desktop mode) */}
    </div>
  </div>
</div>
```

**The Confusing Part - Two Visual Boundaries:**

1. **Canvas Container** (line 3123-3133): Has `shadow-sm border border-border rounded-lg bg-background` - This looks like it could be "the form" but it's actually just the builder workspace
2. **Form Wrapper** (line 3255 and 3465): The ACTUAL form container that users can style with background color/image, padding, margin, and border-radius

This creates confusion because users see two "boxes" - the canvas border/shadow and the form wrapper styling - and it's unclear which represents their actual form on the frontend.

**Current Form Wrapper Implementation:**

The form wrapper appears in TWO different places depending on device preview mode:

**Device Mode (Mobile/Tablet)** - Line 3224-3400:
- Wrapped inside a `.device-frame` div with visual chrome (fake phone/tablet frame)
- Inside a `.device-screen-overlay` div (line 3253)
- The `.form-wrapper` itself (line 3255) has inline styles for:
  - `background`: Based on `formWrapperSettings.backgroundType` (none/color/image)
  - `backgroundColor` with opacity converted to hex alpha
  - `backgroundImage` with cover/center positioning
  - `padding`: Device-specific from `formWrapperSettings[devicePreview].padding`
  - `margin`: Device-specific from `formWrapperSettings[devicePreview].margin`
  - `borderRadius: 'var(--radius-lg)'`
  - `width: '100%'` (fills device screen)
  - `minHeight: '200px'`
  - `position: 'relative'`
  - `z-index: 15` (from CSS line 2923-2927)

**Desktop Mode** - Line 3462-3597:
- Wrapped in a flex centering container: `<div className="flex justify-center w-full min-h-[200px]">`
- The `.form-wrapper` has same background/padding/margin styling
- Key difference: `width: formWrapperWidth` (resizable via DraggableResizeBar component)
- Has a draggable resize bar on the right edge (line 3486)
- Resize bar lets users adjust form width interactively

**Form Wrapper Settings State** (Line 1473-1490):
```typescript
const [formWrapperSettings, setFormWrapperSettings] = useState({
  backgroundType: 'color' as 'none' | 'color' | 'image',
  backgroundColor: '#ffffff',
  backgroundImage: '',
  backgroundOpacity: 1,
  desktop: { padding: {top: 40, right: 40, bottom: 40, left: 40}, margin: {top: 20, right: 20, bottom: 20, left: 20} },
  tablet: { padding: {top: 30, right: 30, bottom: 30, left: 30}, margin: {top: 15, right: 15, bottom: 15, left: 15} },
  mobile: { padding: {top: 20, right: 20, bottom: 20, left: 20}, margin: {top: 10, right: 10, bottom: 10, left: 10} }
});
```

These settings are managed through a FormWrapperSettingsPanel component (line 3818-3842) which appears as a floating panel when the form wrapper is selected.

**CSS Styling in form-builder.css:**

**Canvas Container Styles** (line 1053-1061):
```css
.canvas {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);  /* ← Creates visual confusion */
  min-height: 600px;
  margin: 0 auto;
  padding: var(--space-6);
  transition: all var(--duration-base);
}
```

**Form Wrapper Styles** (line 2923-2927):
```css
.form-wrapper {
  position: relative;
  z-index: 15;
  transition: all var(--duration-normal);
}
```

The form wrapper has minimal CSS because most styling is inline via the formWrapperSettings state.

**Form Wrapper Selection State** (line 3116-3126):
```css
.form-wrapper-selected {
  outline: 2px solid #f57c00;  /* Orange outline when selected */
  outline-offset: 4px;
  border-radius: var(--radius-lg);
  transition: outline var(--duration-fast);
}
```

**Drop Zone Styling** (line 1069-1091):
When the form is empty, a drop zone appears inside the form wrapper:
```css
.drop-zone {
  min-height: 80px;
  border: 2px dashed var(--color-border-primary);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  /* ... */
}
```

### The Styles & Themes System Integration

**How Styles Apply to Form Elements (NOT the Form Wrapper):**

The form builder has a sophisticated style system for individual form elements, but it's SEPARATE from the form wrapper styling:

**Style Registry Architecture** (schemas/styles/):
- `styleRegistry` (registry.ts): In-memory store with subscription system for reactive updates
- Global styles stored per NodeType (label, input, button, heading, etc.)
- Element-level overrides stored in `element.styleOverrides[nodeType]`
- Version tracking for memoization

**Node Types & Capabilities** (types.ts, capabilities.ts):
- 13 node types: fieldContainer, label, input, textarea, select, button, heading, paragraph, error, description, placeholder, required, optionLabel, divider, cardContainer
- Each node type has specific style capabilities defined in NODE_STYLE_CAPABILITIES
- StyleProperties interface includes: colors, typography, spacing (margin/padding/border), sizing, borders

**Style Resolution Flow in ElementRenderer** (components/elements/ElementRenderer.tsx line 23-59):
1. For each element being rendered, useResolvedStyle hook is called for each applicable node type
2. useResolvedStyle (hooks/useResolvedStyle.ts line 10-30):
   - Gets element from store: `useElementsStore((state) => state.items[elementId])`
   - Subscribes to global styles: `useGlobalNodeStyle(nodeType)`
   - Gets element-specific overrides: `element.styleOverrides?.[nodeType]`
   - Merges: `return { ...globalStyle, ...overrides }`
3. Styles converted to CSS: `stylesToCSS(labelStyle)` using styleUtils.ts
4. Applied as inline styles to rendered elements

**Key Point:** The themes system (wp_superforms_themes table, Theme REST API) stores global style presets, but these apply to FORM ELEMENTS (labels, inputs, buttons) NOT to the form wrapper itself. The form wrapper styling is managed separately via formWrapperSettings state.

### What Needs to Change for Clear Visual Hierarchy

**Current Problems:**
1. Canvas container has `border border-border shadow-sm` which looks like it could be part of the form styling
2. Users can't tell which visual elements represent their actual form vs builder chrome
3. Form wrapper border-radius is hardcoded to `var(--radius-lg)` instead of being styleable
4. No "page background" layer to provide context for how form appears in real environment

**Proposed Solution - New Layered Model:**

```
[Canvas Workspace - Neutral, no border/shadow]
  └─ [Optional: Page Background Preview Layer]
       └─ [Form Wrapper - User-styleable container]
            └─ [Form Content - Elements with style system]
                 └─ [Individual Form Elements]
```

**Required Changes:**

1. **Remove Canvas Visual Chrome** (line 3123):
   - Remove `shadow-sm border border-border` from canvas container
   - Keep `bg-background rounded-lg` for subtle definition
   - Canvas should feel like a neutral workspace, not a visual boundary

2. **Make Form Wrapper More Prominent**:
   - Add default shadow/border to form-wrapper to make it stand out
   - Make border-radius user-configurable (add to formWrapperSettings)
   - Consider adding a subtle label/badge when form-wrapper is hovered: "Your Form"

3. **Optional Page Background Layer**:
   - Add a new container between canvas and form-wrapper
   - User-configurable background (color/image/gradient)
   - Helps preview how form appears on actual website
   - Could be toggled on/off via toolbar button

4. **Visual Selection Improvements**:
   - Keep the orange outline for form-wrapper-selected (line 3116)
   - Add tooltip/label on hover: "Click to edit form container"
   - Update breadcrumb to make "Form" level more visually distinct

**File Locations for Implementation:**

- **Main canvas container**: `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` line 3123
- **Desktop form wrapper**: Same file, line 3465
- **Device mode form wrapper**: Same file, line 3255
- **Form wrapper settings state**: Same file, line 1473
- **FormWrapperSettingsPanel**: Same file, line 3818 (add border-radius control here)
- **CSS styles**: `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/styles/form-builder.css`
  - Canvas styles: line 1053
  - Form wrapper styles: line 2923
  - Selection styles: line 3116

**Architectural Considerations:**

1. **Form Wrapper vs Style System**: Keep these separate. Form wrapper is a layout/container concern, style system is for element-level styling. Don't try to merge them.

2. **Device-Specific Settings**: The formWrapperSettings already has device-specific padding/margin (desktop/tablet/mobile). Any new properties (like border-radius) should follow this pattern.

3. **Frontend Rendering**: While this task focuses on the builder preview, changes to formWrapperSettings structure will need corresponding changes in frontend form rendering (PHP shortcode system in class-shortcodes.php). Currently unsure if form wrapper styling is implemented on frontend.

4. **Persistence**: formWrapperSettings state needs to be saved with form data. Check if this is already happening or needs to be added to form save/load logic.

5. **Zoom Compatibility**: Canvas has zoom functionality (line 3118). Any new layers must respect the zoom transform.

## User Notes
- Related to mobile responsive work on same branch
- Options discussed:
  - Remove canvas border/shadow to make it purely a neutral workspace
  - Add a "page background" preview layer users can customize
  - Make form-wrapper more visually distinct as "this is your actual form"

## Work Log
<!-- Updated as work progresses -->
- [2025-12-08] Task created from mobile responsive discussion
