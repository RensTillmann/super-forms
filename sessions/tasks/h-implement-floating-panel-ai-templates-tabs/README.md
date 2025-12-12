---
name: h-implement-floating-panel-ai-templates-tabs
branch: feature/h-implement-triggers-actions-extensibility
status: in-progress
created: 2025-12-10
---

# Floating Panel: AI & Templates Tabs

## Problem/Goal
Add two new tabs to the element floating panel:
1. **AI Tab** - Chat interface focused on the active element (and children if container)
2. **Templates Tab** - Visual template picker with toggle-on/off UX for input field variations

The Templates system should leverage shadcn InputGroup addon patterns (prefix/suffix text, icons, tooltips, popovers) with a schema-first approach.

## Success Criteria
- [ ] Schema defined for input addons (prefix, suffix, icons, tooltips, action buttons)
- [ ] Templates registry with predefined element configurations
- [ ] Templates Tab UI with preview cards, toggleable options, and Apply button
- [ ] AI Tab with element-context-aware chat interface
- [ ] AI can modify element properties and apply templates
- [ ] Works for container elements (targets children)

## Subtasks
- `01-input-addon-schema.md` - Schema-first design for InputGroup addons
- `02-templates-registry.md` - Template definitions with toggle states
- `03-templates-tab-ui.md` - Templates tab UI with preview and apply logic
- `04-ai-tab-foundation.md` - AI tab with element-focused context
- `05-ai-element-operations.md` - AI actions for modifying elements

## Context Manifest

### How the Floating Panel Currently Works

The FloatingPanel is the primary interface for editing elements in Form Builder V2. When a user clicks on an element in the canvas, the panel appears with a 4-tab structure that provides different editing contexts. Understanding this flow is critical because you'll be adding two new tabs to this system.

**Activation Flow:**
When a user clicks an element in the canvas (ElementRenderer component), the click handler triggers `onPropertyChange` callbacks that bubble up to the parent FormBuilder component. This component maintains the currently selected element ID and passes it to FloatingPanel. The panel is rendered conditionally based on whether an element is selected, and it subscribes directly to the Zustand elements store to get fresh element data without causing parent re-renders.

**Tab System Architecture:**
The panel uses shadcn/ui's Tabs component with a configuration array (`TAB_CONFIG`) that defines 4 tabs: Content, Style, Behavior, and Code. Each tab has an ID, label, and Lucide icon. The tabs are defined at lines 27-34 in FloatingPanel.tsx. The tab content is rendered via `TabsContent` components (lines 312-340), each wrapping a dedicated tab component that receives the element and callbacks.

**Current Tab Implementations:**

1. **ContentTab** (`tabs/ContentTab.tsx`): Shows schema-driven properties from the 'general' category. It checks if the element has a registered schema using `isElementRegistered()` and renders SchemaPropertyPanel if yes, or falls back to legacy panels. This is where users set label, placeholder, prefix/suffix text/icons, and other basic properties.

2. **StyleTab** (`tabs/StyleTab.tsx`): Complex hierarchy with Target → State → Device selection. Users first select which part of the element to style (label, input, description, etc. - these are NodeTypes), then pick a state (normal/hover/focus/error), then optionally a breakpoint. The selected target determines which style properties appear. It renders TypographySection, BackgroundSection, and BorderSection components that show collapsible groups of style controls. At the bottom (lines 334-367), it includes schema-driven Appearance and Advanced properties using SchemaPropertyPanel filtered by category.

3. **BehaviorTab** (`tabs/BehaviorTab.tsx`): Organized with CollapsibleSection components. Shows validation rules (required field toggle, min/max length, pattern, custom error messages), conditional logic (placeholder for future condition builder), and calculations (placeholder for formula builder). Uses schema-driven properties for validation category when available.

4. **CodeTab** (`tabs/CodeTab.tsx`): Developer-focused settings split into CollapsibleSection groups: Identity (custom ID, CSS classes), Attributes (data-* and aria-* attributes), Custom CSS (with scoped .this selector), Events (placeholder), and Developer Info (JSON dump of element).

**Mobile vs Desktop Rendering:**
The panel has two render paths. On mobile (detected via `useIsMobile()` hook), it renders as a custom MobileDrawer component from the bottom of the screen with a drag handle. The drawer height is automatically calculated using Visual Viewport API to adapt when the mobile keyboard opens/closes. On desktop, it's a positioned floating panel that appears near the clicked element and is clamped to viewport bounds.

**State Management:**
The panel uses local React state for UI concerns (active tab, drawer height, mobile readiness) and subscribes to the elements store for element data. The `useElementsStore` subscription is scoped to just the element being edited (line 172: `s => s.items[elementId]`), preventing unnecessary re-renders when other elements change. Style overrides are managed via dedicated store actions: `setStyleOverride`, `removeStyleOverride`, `clearNodeStyleOverrides`, `clearAllStyleOverrides` (lines 175-205).

**Property Change Flow:**
When a user modifies a property in any tab, the change flows through `onPropertyChange` callback (passed as prop from parent). This callback updates the element in the store, which triggers a re-render of the panel to show the new value. For style overrides, there's a separate `handleStyleOverrideChange` callback (lines 186-194) that uses dedicated style override store actions instead of the general property change handler.

**Key Architectural Decisions:**
- Tabs are module-level components to maintain stable identity and prevent scroll position reset on re-renders
- The panel subscribes to store but doesn't mutate directly - all changes go through callbacks
- CollapsibleSection is used consistently for progressive disclosure in Behavior and Code tabs
- SchemaPropertyPanel is the preferred way to render properties, with fallbacks for non-schema elements
- Style overrides are stored separately from properties in `element.styleOverrides` by NodeType

### For New Tab Implementation: What Needs to Connect

**Adding Two New Tabs (AI & Templates):**

You'll need to extend the `TAB_CONFIG` array in FloatingPanel.tsx (currently lines 29-34) and the `PanelTab` type (line 27) to include 'ai' and 'templates'. Each needs a Lucide icon - suggest `Sparkles` for AI and `LayoutTemplate` for Templates. The tabs should be added after 'code' to appear at the end of the tab bar.

**Templates Tab Architecture:**

This tab will leverage the existing schema system. The text element already has `prefixText`, `suffixText`, `prefixIcon`, `suffixIcon` properties defined in its schema (lines 31-50 of `schemas/elements/text.ts`). There are also advanced properties like `showCharacterCount`, `characterCountPosition`, `actionButton`, and `helpTooltip` (lines 141-177).

The Templates tab should present these as visual templates (not raw property editors). Think of it like a gallery where each template is a preview card showing what the input will look like with that addon pattern active. Users toggle templates on/off (multiple can be active), then click Apply to merge the selected templates into the element's properties.

**Template Registry Pattern:**

You'll need to create a new registry similar to how `schemas/core/registry.ts` works for elements and `schemas/styles/registry.ts` works for styles. This registry will store template definitions. Each template should specify:
- ID and display name
- Preview configuration (what it looks like visually)
- Property mutations (what properties to set when applied)
- Conflicts (which templates are mutually exclusive)
- Element type compatibility (which elements support this template)

**AI Tab Architecture:**

The AI tab will be a chat interface focused on the currently selected element. When the element is a container (has `children` array in FormElement interface - see line 37 of `types/index.ts`), the AI context should include all child elements to enable operations like "make all children required" or "align all labels to the left".

The AI will need access to:
- Current element properties (via `element.properties`)
- Element schema (via `getElementSchema(element.type)` from core registry)
- Style overrides (via `element.styleOverrides`)
- For containers: child element data (via store subscription to `items[childId]`)

The AI should be able to:
- Modify element properties using `onPropertyChange` callback
- Apply style overrides using `onOverrideChange` callback
- Apply templates by triggering the template application logic from Templates tab
- Batch operations on container children

**Schema Integration Points:**

The PropertyRenderer component (at `property-panels/schema/PropertyRenderer.tsx`) shows how to render each property type. It handles 26 property types (defined in `schemas/core/types.ts` lines 20-65) including string, number, boolean, select, icon, position_picker, color, and complex types. The icon property type uses IconPicker component which supports both Font Awesome and Lucide icons with a searchable dialog.

The SchemaPropertyPanel component (at `property-panels/schema/SchemaPropertyPanel.tsx`) shows how to:
- Filter properties by category (line 55)
- Check property visibility based on conditions and target affinity (lines 74-115)
- Render properties dynamically using PropertyRenderer (lines 141-156)
- Support `targetFilter` prop to show only properties relevant to a specific NodeType

**Style System Integration:**

The Templates tab will need to understand NodeTypes and style capabilities. The `schemas/styles/elementNodes.ts` file (referenced in StyleTab line 3) defines `getElementNodes()` which returns the available NodeTypes for each element type. The StyleTab shows the pattern for target selection (lines 119-170) - a horizontal scrollable list of button pills with override indicators.

When a template affects styles, it should use the same `handleStyleOverrideChange` callback pattern that StyleTab uses (lines 281-286). The template should specify which NodeType the style applies to, and the system will handle merging with existing overrides.

**CollapsibleSection Pattern:**

Both new tabs should use CollapsibleSection components for organization. The pattern is: outer container with title/icon/state, collapsible content area, optional reset button, override indicators. See how BehaviorTab uses it (lines 48-227) - each major feature (Validation, Conditional Logic, Calculations) is a CollapsibleSection with `defaultExpanded` controlling initial state.

**Data Persistence:**

Element changes are automatically persisted via the parent FormBuilder component's save mechanism. The FloatingPanel doesn't handle persistence directly - it only triggers callbacks. However, you may need to add template state tracking in the UI layer (which templates are currently toggled but not yet applied) using local React state within the TemplatesTab component.

### Technical Reference Details

#### File Locations for Implementation

**New Tab Components (create these):**
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/tabs/AITab.tsx`
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/tabs/TemplatesTab.tsx`

**Template Registry (create new):**
- `/home/rens/super-forms/src/react/admin/schemas/templates/registry.ts`
- `/home/rens/super-forms/src/react/admin/schemas/templates/types.ts`
- `/home/rens/super-forms/src/react/admin/schemas/templates/definitions/inputAddons.ts`

**Files to Modify:**
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx` - Add tab config, imports, and TabsContent for new tabs
- `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/tabs/index.ts` - Export new tab components

#### Key Interfaces and Types

**FormElement Interface** (from `types/index.ts`):
```typescript
interface FormElement {
  id: string;
  type: string;
  properties: Record<string, any>;
  children?: string[];  // Present for container elements
  parent?: string;
  styleOverrides?: Partial<Record<NodeType, Partial<StyleProperties>>>;
}
```

**Tab Component Props Pattern:**
```typescript
interface TabProps {
  element: FormElement;
  onPropertyChange: (propertyName: string, value: unknown) => void;
  // StyleTab also receives these:
  onOverrideChange?: (nodeType: string, property: string, value: unknown) => void;
  onResetToGlobal?: (nodeType?: string) => void;
}
```

**PropertySchema Structure** (from `schemas/core/types.ts`):
```typescript
interface PropertySchema {
  type: PropertyType;  // 26 types including 'string', 'icon', 'select', etc.
  label: string;
  description?: string;
  default?: unknown;
  required?: boolean;
  translatable?: boolean;
  supportsTags?: boolean;
  readonly?: boolean;
  options?: SelectOption[];  // For select/multi_select types
  min?: number;  // For number/range
  max?: number;
  step?: number;
  minLength?: number;  // For string
  maxLength?: number;
  pattern?: string;
  conditions?: PropertyCondition[];  // Show/hide based on other properties
  targets?: string[];  // Style target affinity (NodeTypes this applies to)
  group?: string;
}
```

**NodeType Union** (from `schemas/styles/types.ts`):
```typescript
type NodeType =
  | 'label'
  | 'description'
  | 'input'
  | 'placeholder'
  | 'error'
  | 'required'
  | 'fieldContainer'
  | 'heading'
  | 'paragraph'
  | 'button'
  | 'divider'
  | 'optionLabel'
  | 'cardContainer';
```

**Text Element Schema Excerpt** (relevant InputGroup properties):
```typescript
{
  general: {
    prefixText: { type: 'string', label: 'Prefix Text', description: 'Text shown before the input (e.g., $, €, https://)' },
    suffixText: { type: 'string', label: 'Suffix Text', description: 'Text shown after the input (e.g., USD, .com, kg)' },
    prefixIcon: { type: 'icon', label: 'Prefix Icon', description: 'Icon shown before the input' },
    suffixIcon: { type: 'icon', label: 'Suffix Icon', description: 'Icon shown after the input' },
  },
  advanced: {
    showCharacterCount: { type: 'boolean', label: 'Show Character Counter', default: false },
    characterCountPosition: {
      type: 'select',
      label: 'Counter Position',
      options: [
        { value: 'inline-end', label: 'Inside Input (Right)' },
        { value: 'block-end', label: 'Below Input' }
      ],
      conditions: [{ property: 'showCharacterCount', operator: 'equals', value: true }]
    },
    actionButton: {
      type: 'select',
      label: 'Action Button',
      options: [
        { value: 'none', label: 'None' },
        { value: 'copy', label: 'Copy to Clipboard' },
        { value: 'clear', label: 'Clear Input' },
        { value: 'toggle-visibility', label: 'Toggle Visibility' }
      ],
      default: 'none'
    },
    helpTooltip: { type: 'string', label: 'Help Tooltip', translatable: true }
  }
}
```

#### Component Patterns to Follow

**CollapsibleSection Usage:**
```typescript
<CollapsibleSection
  title="Section Name"
  icon={<IconComponent className="h-3.5 w-3.5" />}
  defaultExpanded={true}
  hasOverrides={overrideCount > 0}
  overrideCount={overrideCount}
  onReset={() => handleReset()}
>
  {/* Content here */}
</CollapsibleSection>
```

**SchemaPropertyPanel Usage:**
```typescript
<SchemaPropertyPanel
  elementType={element.type}
  properties={element.properties || {}}
  onPropertyChange={onPropertyChange}
  categories={['general', 'validation']}  // Filter to specific categories
  targetFilter={selectedTarget}  // Optional: filter by NodeType
/>
```

**IconPicker Usage:**
```typescript
<IconPicker
  value={iconValue}  // Format: "fa:fas fa-user" or "lucide:user"
  onChange={(value) => onPropertyChange('iconProperty', value)}
/>
```

**Store Subscription Pattern:**
```typescript
// Subscribe to specific element only
const element = useElementsStore((s) => s.items[elementId]);

// Subscribe to multiple children for container elements
const childElements = useElementsStore((s) =>
  element?.children?.map(childId => s.items[childId]).filter(Boolean) || []
);
```

#### Prescribed Reading for Implementation

**Before starting, read these files completely:**

1. **Floating Panel Base**: `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx` (lines 1-421) - Understand tab structure, props flow, mobile/desktop rendering

2. **Existing Tab Examples**:
   - `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/tabs/ContentTab.tsx` (lines 1-51) - Simplest tab, schema integration
   - `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/tabs/BehaviorTab.tsx` (lines 1-276) - CollapsibleSection pattern
   - `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/tabs/StyleTab.tsx` (lines 1-373) - Complex hierarchy, target selection

3. **Schema System**:
   - `/home/rens/super-forms/src/react/admin/schemas/core/types.ts` (lines 1-296) - PropertySchema definition, all 26 property types
   - `/home/rens/super-forms/src/react/admin/schemas/core/registry.ts` (lines 1-370) - Registry pattern, withBaseProperties helper
   - `/home/rens/super-forms/src/react/admin/schemas/elements/text.ts` (lines 1-191) - Complete element schema with InputGroup properties

4. **Property Rendering**:
   - `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/schema/PropertyRenderer.tsx` (lines 1-223) - How each property type renders
   - `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/schema/SchemaPropertyPanel.tsx` (lines 1-191) - Category filtering, condition checking

5. **UI Components**:
   - `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/property-panels/style-sections/CollapsibleSection.tsx` (lines 1-101) - Reusable section component
   - `/home/rens/super-forms/src/react/admin/components/ui/icon-picker/IconPicker.tsx` (lines 1-540) - Icon selection UI

6. **Store Pattern**:
   - `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/store/useElementsStore.ts` (lines 1-314) - Element CRUD, style override actions

7. **Style System**:
   - `/home/rens/super-forms/src/react/admin/schemas/styles/registry.ts` (lines 1-322) - Style registry pattern (reference for template registry)

## User Notes
- Use existing InputGroup components from shadcn (see ui.shadcn.com/docs/components/input-group)
- InputGroupAddon patterns: inline-end text, icons, tooltips, popovers, prefix text
- UX: Toggle templates on/off, then Apply to combine selections
- Schema-first approach like existing element schemas
- Stay on current branch (feature/h-implement-triggers-actions-extensibility)

## Work Log
- [2025-12-10] Task created
