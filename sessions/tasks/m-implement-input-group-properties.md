---
name: m-implement-input-group-properties
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-10
---

# Implement Input Group Properties for Text Fields

## Problem/Goal

Extend the FormBuilderV2 text field element with advanced input group features inspired by shadcn/ui input-group component. This includes prefix/suffix text and icons, flexible label/description positioning, character counters, action buttons, help tooltips, and loading states.

Additionally, move the `width` property from the General category to the Appearance category in the property panel, and fix the `defaultValue` not being applied to the input element preview.

## Success Criteria
- [ ] Width property moved from General to Appearance category
- [ ] DefaultValue applied to input element in canvas preview
- [ ] PositionPicker component created (3x3 grid for label/description placement)
- [ ] Label position property added with visual picker
- [ ] Description position property added with visual picker
- [ ] Prefix/suffix text properties added
- [ ] Prefix/suffix icon properties added
- [ ] Character counter option added
- [ ] Action button option added (copy, clear, toggle-visibility)
- [ ] Help tooltip property added
- [ ] TextInput component updated to render all new properties
- [ ] input-group shadcn component installed and integrated

## Implementation Plan

### Phase 1: Quick Fixes
1. Move `width` from `BASE_PROPERTIES.general` to `BASE_PROPERTIES.appearance`
2. Add `defaultValue` to TextInput component props and apply as input value

### Phase 2: PositionPicker Component
Create custom `PositionPicker` component using toggle-group:
```
┌───┬───┬───┐
│ ◉ │   │   │  ← top-left, top-center, top-right
├───┼───┼───┤
│   │   │   │  ← left, center, right (inline with input)
├───┼───┼───┤
│   │   │   │  ← bottom-left, bottom-center, bottom-right
└───┴───┴───┘
```

### Phase 3: Schema Updates
Add to text.ts element schema:

**Appearance Category:**
- `labelPosition`: position_picker (default: 'top-left')
- `descriptionPosition`: position_picker (default: 'bottom-left')
- `width`: select (moved from general)

**General Category:**
- `prefixText`: string
- `suffixText`: string
- `prefixIcon`: icon
- `suffixIcon`: icon

**Advanced Category:**
- `showCharacterCount`: boolean
- `characterCountPosition`: select (inline-end, block-end)
- `actionButton`: select (none, copy, clear, toggle-visibility)
- `helpTooltip`: string
- `showLoadingSpinner`: boolean

### Phase 4: Property Renderer
- Create `PositionPickerRenderer` for `position_picker` property type
- Register in PropertyRenderer switch

### Phase 5: TextInput Component
Update to render:
- Input with prefix/suffix addons
- Positioned label and description
- Character counter
- Action buttons
- Help tooltip icon
- Loading spinner state

---

## UX Specifications

### PositionPicker Component

**Visual Design:**
```
┌─────────────────────────────────┐
│  Position                       │
│  ┌────┬────┬────┐              │
│  │ TL │ TC │ TR │  ← 24x24px cells
│  ├────┼────┼────┤     with 2px gap
│  │ L  │ C  │ R  │              │
│  ├────┼────┼────┤              │
│  │ BL │ BC │ BR │              │
│  └────┴────┴────┘              │
└─────────────────────────────────┘
```

**Interaction:**
- Single-select (only one position active)
- Click to select, selected cell shows filled dot or highlight
- Keyboard: Arrow keys navigate, Enter/Space selects
- Hover: Subtle background change
- ARIA: `role="radiogroup"` with `role="radio"` items

**Values:**
- `top-left`, `top-center`, `top-right`
- `left`, `center`, `right` (inline with input)
- `bottom-left`, `bottom-center`, `bottom-right`

---

### Label Position UX

**Layout Behaviors:**

| Position | Label Placement | Input Layout |
|----------|-----------------|--------------|
| `top-left` | Above input, left-aligned | Vertical stack (default) |
| `top-center` | Above input, centered | Vertical stack |
| `top-right` | Above input, right-aligned | Vertical stack |
| `left` | Left of input, vertically centered | Horizontal inline |
| `center` | Hidden (use for label-less) | Input only |
| `right` | Right of input, vertically centered | Horizontal inline |
| `bottom-left` | Below input, left-aligned | Vertical stack reversed |
| `bottom-center` | Below input, centered | Vertical stack reversed |
| `bottom-right` | Below input, right-aligned | Vertical stack reversed |

**Note:** When label is `left` or `right`, field container becomes `flex-row` with label taking ~30% width.

---

### Description Position UX

**Layout Behaviors:**

| Position | Description Placement |
|----------|----------------------|
| `top-left` | Between label and input (if label is top), left-aligned |
| `top-center` | Between label and input, centered |
| `top-right` | Between label and input, right-aligned |
| `left` | Inline left of input (rare, for short hints) |
| `center` | Not recommended (hidden option) |
| `right` | Inline right of input |
| `bottom-left` | Below input, left-aligned (default) |
| `bottom-center` | Below input, centered |
| `bottom-right` | Below input, right-aligned |

---

### Prefix/Suffix Text UX

**Property Panel:**
```
┌─────────────────────────────────┐
│  Prefix Text                    │
│  ┌─────────────────────────────┐│
│  │ $                           ││
│  └─────────────────────────────┘│
│  Examples: $, €, https://, @    │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│  Suffix Text                    │
│  ┌─────────────────────────────┐│
│  │ USD                         ││
│  └─────────────────────────────┘│
│  Examples: USD, .com, kg, %     │
└─────────────────────────────────┘
```

**Canvas Preview:**
```
┌─────────────────────────────────────┐
│ $ │ Enter amount...           │ USD │
└─────────────────────────────────────┘
```

**Behavior:**
- Text renders inside input group addon
- Muted text color (`text-muted-foreground`)
- Clicking prefix/suffix focuses the input

---

### Prefix/Suffix Icon UX

**Property Panel:**
- Icon picker component (existing `icon` type)
- Shows icon preview in picker button
- Lucide icon search/selection modal

**Canvas Preview:**
```
┌─────────────────────────────────────┐
│ 🔍 │ Search...              │ ✓    │
└─────────────────────────────────────┘
  ↑ prefix icon               ↑ suffix icon
```

**Combination Rules:**
- Prefix: Icon renders before prefix text (if both set)
- Suffix: Icon renders after suffix text (if both set)
- Order: `[prefix-icon] [prefix-text] [input] [suffix-text] [suffix-icon] [action-button]`

---

### Character Counter UX

**Property Panel:**
```
┌─────────────────────────────────┐
│  ☑ Show Character Counter       │
│                                 │
│  Position  ┌────────────────┐   │
│            │ Below Input  ▼ │   │
│            └────────────────┘   │
│  Options: Inside Input, Below   │
│                                 │
│  Format    ┌────────────────┐   │
│            │ X / Max      ▼ │   │
│            └────────────────┘   │
│  Options: X/Max, X remaining    │
└─────────────────────────────────┘
```

**Canvas Preview (Inside Input):**
```
┌─────────────────────────────────────┐
│ Enter message...              45/100│
└─────────────────────────────────────┘
```

**Canvas Preview (Below Input):**
```
┌─────────────────────────────────────┐
│ Enter message...                    │
└─────────────────────────────────────┘
45 / 100 characters
```

**Behavior:**
- Requires `maxLength` to be set (show warning if not)
- Updates live as user types (in actual form, not builder preview)
- Color changes when approaching limit (yellow at 80%, red at 95%)

---

### Action Button UX

**Property Panel:**
```
┌─────────────────────────────────┐
│  Action Button                  │
│  ┌─────────────────────────────┐│
│  │ None                      ▼ ││
│  └─────────────────────────────┘│
│  Options:                       │
│  • None                         │
│  • Copy to Clipboard            │
│  • Clear Input                  │
│  • Toggle Visibility (password) │
└─────────────────────────────────┘
```

**Canvas Preview:**
```
Copy:     [...input...] [📋]
Clear:    [...input...] [✕]
Toggle:   [...input...] [👁]
```

**Behavior:**
- Renders as `InputGroupButton` with `size="icon-xs"`
- Copy: Shows checkmark briefly after copying
- Clear: Only visible when input has value
- Toggle: Switches between eye/eye-off, changes input type

---

### Help Tooltip UX

**Property Panel:**
```
┌─────────────────────────────────┐
│  Help Tooltip                   │
│  ┌─────────────────────────────┐│
│  │ Enter your legal full name  ││
│  │ as it appears on your ID.   ││
│  └─────────────────────────────┘│
│  (Leave empty to hide)          │
└─────────────────────────────────┘
```

**Canvas Preview:**
```
Full Name [ℹ]
┌─────────────────────────────────────┐
│ Enter your name...                  │
└─────────────────────────────────────┘
         ↑ Info icon next to label
```

**Hover/Click reveals tooltip:**
```
┌──────────────────────────┐
│ Enter your legal full    │
│ name as it appears on    │
│ your ID.                 │
└──────────────────────────┘
```

**Behavior:**
- Renders info icon (ℹ) next to label
- Uses shadcn Tooltip component
- Touch devices: tap to toggle
- Desktop: hover to show

---

### Width Property UX (Enhanced)

**Current:** Simple dropdown with text options

**Enhanced Option (future):** Visual width selector
```
┌─────────────────────────────────┐
│  Field Width                    │
│  ┌─┐ ┌──┐ ┌───┐ ┌────┐ ┌─────┐ │
│  │▓│ │▓ │ │▓  │ │▓   │ │▓    │ │
│  └─┘ └──┘ └───┘ └────┘ └─────┘ │
│  1/4  1/3  1/2   2/3   Full    │
└─────────────────────────────────┘
```

**For now:** Keep as dropdown, move to Appearance category

---

### Input Width vs Wrapper Width

**Two separate properties needed:**

1. **Field Width** (`width`) - Already exists
   - Controls the field's column span in form grid
   - Values: full, 1/2, 1/3, 2/3, 1/4, 3/4
   - Affects form layout

2. **Input Width** (`inputWidth`) - New property
   - Controls input element width within field container
   - Values: full (100%), auto (content-based), fixed (px value)
   - Useful when label is inline (left/right position)

**Example:**
```
Field Width: 1/2 (takes half of form width)
Input Width: 70% (input takes 70% of field, label takes 30%)

┌─────────────────────────────────┐  ← Field (50% of form)
│ Label      │ [Input 70%     ]   │
└─────────────────────────────────┘
  ↑ 30%        ↑ 70%
```

## Files to Modify

**Schema:**
- `src/react/admin/schemas/core/registry.ts` - Move width, add position_picker type
- `src/react/admin/schemas/core/types.ts` - Add position_picker to PropertyType
- `src/react/admin/schemas/elements/text.ts` - Add new properties

**Components:**
- `src/react/admin/apps/form-builder-v2/components/property-panels/schema/PropertyRenderer.tsx` - Add PositionPickerRenderer
- `src/react/admin/apps/form-builder-v2/components/property-panels/schema/renderers/PositionPickerRenderer.tsx` (new)
- `src/react/admin/apps/form-builder-v2/components/elements/basic/TextInput.tsx` - Render new properties

**shadcn:**
- Install input-group component: `npx shadcn@latest add input-group`
- Install toggle-group component: `npx shadcn@latest add toggle-group`

## Context Manifest

### How the Schema-Driven Property System Currently Works

The FormBuilderV2 uses a **schema-first architecture** where all element properties, validation rules, and UI rendering are driven by TypeScript schemas registered at import time. Here's the complete data flow:

#### 1. Schema Registration (`src/react/admin/schemas/elements/text.ts`)

When the application loads, element schemas are registered using `registerElement()`. For the text element:

```typescript
export const TextElementSchema = registerElement({
  type: 'text',
  name: 'Text Input',
  category: 'basic',
  icon: 'type',
  container: null,
  properties: withBaseProperties({
    general: {
      placeholder: { type: 'string', label: 'Placeholder', ... },
      defaultValue: { type: 'string', label: 'Default Value', ... },
    },
    validation: { required: { type: 'boolean', ... }, ... },
    appearance: { inputIcon: { type: 'icon', ... }, ... },
    advanced: { autocomplete: { type: 'select', ... }, ... }
  }),
  defaults: { label: 'Text Field', name: '', width: 'full', ... },
  translatable: ['label', 'placeholder', 'customError'],
  supportsTags: ['placeholder', 'defaultValue']
});
```

**Key Architectural Points:**
- `withBaseProperties()` merges element-specific props with base props (name, label, width, hideLabel, cssClass, conditionalLogic)
- Base properties are defined in `src/react/admin/schemas/core/registry.ts` at lines 147-199
- **Current location of `width` property:** `BASE_PROPERTIES.general` (line 162-175)
- Schema validation happens with Zod at registration time - invalid schemas crash immediately (fail-fast principle)
- The schema is stored in a Map and becomes the single source of truth for UI rendering

#### 2. Property Type System (`src/react/admin/schemas/core/types.ts`)

The system supports 26 property types (lines 20-64):
- Primitives: `string`, `number`, `boolean`
- Selection: `select`, `multi_select`
- Visual: `color`, `icon`
- Complex: `array`, `object`, `conditional_rules`, `columns_config`, etc.

**To add a new property type `position_picker`:**
1. Add to `PropertyTypeSchema` enum in types.ts (line 20-64)
2. Create a renderer in `PropertyRenderer.tsx` switch statement (line 33-192)
3. The type becomes immediately available to all element schemas

**Property Schema Structure** (lines 96-132):
```typescript
{
  type: PropertyType,
  label: string,
  description?: string,
  default?: unknown,
  required?: boolean,
  translatable?: boolean,
  supportsTags?: boolean,
  readonly?: boolean,
  options?: SelectOption[],    // For select/multi_select
  min?: number, max?: number,  // For number/range
  conditions?: PropertyCondition[]  // Conditional visibility
}
```

#### 3. Property Panel Rendering Flow

**A. User clicks element on canvas** → `FormBuilderV2.tsx` calls `setSelectedElement(elementId)`

**B. FloatingPanel opens** (`src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`):
- Lines 172-173: Subscribes to Zustand store for live element updates
- Lines 310-340: Renders 4 tabs (Content, Style, Behavior, Code)
- Line 312-317: Content tab uses `SchemaPropertyPanel`

**C. SchemaPropertyPanel renders** (`src/react/admin/apps/form-builder-v2/components/property-panels/schema/SchemaPropertyPanel.tsx`):
- Line 30: Fetches schema from registry: `getElementSchema(elementType)`
- Lines 52-57: Filters categories (defaults to showing all 5: general, validation, appearance, advanced, conditions)
- Lines 108-125: Renders category tabs
- Lines 129-146: Loops through properties in active category, calls `PropertyRenderer` for each

**D. PropertyRenderer maps type to UI control** (`src/react/admin/apps/form-builder-v2/components/property-panels/schema/PropertyRenderer.tsx`):
- Line 33-192: Giant switch statement mapping PropertyType → React component
- Examples:
  - `'string'` → `<Input type="text">` (lines 36-43)
  - `'boolean'` → `<Checkbox>` (lines 59-73)
  - `'select'` → `<Select>` with `<SelectItem>` loop (lines 75-92)
  - `'icon'` → `<IconPicker>` (lines 138-144)

**Icon Picker Implementation:**
- Location: `src/react/admin/components/ui/icon-picker/IconPicker.tsx`
- Uses Dialog + Tabs for Font Awesome and Lucide icons
- Infinite scroll grid (8x8 icons per page)
- Value format: `"fa:fas fa-user"` or `"lucide:user"`
- Dynamic icon loading with caching to avoid loading 1000+ icons upfront

#### 4. Data Flow: Property Panel → Element Store → Canvas

**Property Change Flow:**

```
User edits property in PropertyRenderer
  ↓ onChange callback
SchemaPropertyPanel.onPropertyChange (line 142)
  ↓ passes to
FloatingPanel.onPropertyChange (line 22 prop)
  ↓ passed from
FormBuilderV2.handlePropertyChange (line ~800)
  ↓ calls
useElementsStore.updateElement(elementId, { properties: { ...old, [key]: value } })
  ↓ updates Zustand store (src/react/admin/apps/form-builder-v2/store/useElementsStore.ts lines 91-101)
  ↓ triggers re-render of all subscribers
ElementRenderer component re-renders (subscribed to store indirectly via props)
  ↓
TextInput.tsx receives updated element.properties
  ↓
Renders new value on canvas
```

**Current Issue with `defaultValue`:**
- Schema defines it (text.ts line 25-30)
- PropertyRenderer shows input for it (works)
- Store saves it to `element.properties.defaultValue` (works)
- **BUT** TextInput.tsx doesn't read it (bug)
- TextInput only uses `properties.placeholder` (line 41-59), not `defaultValue`
- Fix: Add `value={properties.defaultValue || ''}` to `<input>` at line 82-88

#### 5. Element Rendering on Canvas (`src/react/admin/apps/form-builder-v2/components/elements/`)

**ElementRenderer.tsx** (central dispatcher):
- Lines 23-60: Resolves styles for all node types (label, input, description, etc.) using `useResolvedStyle()` hook
- Lines 39-58: Converts StyleProperties to CSS and merges with element props
- Lines 61-95: Switch statement routing element type to component
- Line 70: `case 'text'` → renders `<TextInput>`

**TextInput.tsx** (lines 1-94):
- Props interface (lines 4-17): Expects `element` with `type`, `id`, `properties`, and `styles: ResolvedStyles`
- Lines 22-38: `getInputType()` maps element.type to HTML input type
- Lines 40-60: `getPlaceholder()` provides fallback placeholders
- **Current rendering** (lines 62-91):
  ```tsx
  <div>
    {properties.label && <label>{properties.label} {required && <span>*</span>}</label>}
    {properties.description && <p>{properties.description}</p>}
    <input type={...} placeholder={...} disabled style={styles.input} />
  </div>
  ```
- **Missing:** Input value, prefix/suffix, icon support, character counter, etc.

**Style System Integration:**
- `useResolvedStyle(elementId, nodeType)` hook merges global styles + element overrides
- Node types: `label`, `input`, `description`, `error`, `required`, `placeholder`, `fieldContainer`, etc.
- Defined in `src/react/admin/schemas/styles/types.ts`
- Global styles managed by `styleRegistry` (in-memory subscription system)
- Element overrides stored in `element.styleOverrides` Zustand store field

#### 6. Property Conditional Visibility

PropertyRenderer checks conditions before rendering (SchemaPropertyPanel.tsx lines 71-104):

```typescript
isPropertyVisible(propSchema) {
  if (!propSchema.conditions) return true;
  return propSchema.conditions.every(condition => {
    const value = properties[condition.property];
    switch (condition.operator) {
      case 'equals': return value === condition.value;
      case 'not_empty': return value !== undefined && value !== null && value !== '';
      // ... etc
    }
  });
}
```

**Example from text.ts** (lines 77-79):
```typescript
iconPosition: {
  type: 'select',
  conditions: [{ property: 'inputIcon', operator: 'not_empty' }]
  // Only shows when inputIcon has a value
}
```

### For Implementing Input Group Properties

#### Adding New Property Type: `position_picker`

**1. Update PropertyTypeSchema** (`src/react/admin/schemas/core/types.ts` line 20-64):
```typescript
export const PropertyTypeSchema = z.enum([
  // ... existing types
  'tag_input',
  'position_picker',  // ADD THIS
]);
```

**2. Create PositionPickerRenderer** (`src/react/admin/apps/form-builder-v2/components/property-panels/schema/renderers/PositionPickerRenderer.tsx`):
- Use `@radix-ui/react-toggle-group` (need to install via shadcn: `npx shadcn@latest add toggle-group`)
- Or build custom with Button components in a 3x3 grid
- Value type: `'top-left' | 'top-center' | 'top-right' | 'left' | 'center' | 'right' | 'bottom-left' | 'bottom-center' | 'bottom-right'`
- Props: `{ value: string, onChange: (value: string) => void }`

**3. Add to PropertyRenderer switch** (`PropertyRenderer.tsx` line 33-192):
```typescript
case 'position_picker':
  return <PositionPickerRenderer value={value as string} onChange={onChange} />;
```

#### Modifying text.ts Element Schema

**Current structure:**
- `withBaseProperties()` provides: general.width (line 162-175 in registry.ts)
- Element adds: general.placeholder, general.defaultValue
- All under `general` category

**Changes needed:**

**A. Move width from general to appearance:**
```typescript
properties: withBaseProperties({
  general: {
    placeholder: { ... },
    defaultValue: { ... },
    prefixText: { type: 'string', label: 'Prefix Text', ... },
    suffixText: { type: 'string', label: 'Suffix Text', ... },
    prefixIcon: { type: 'icon', label: 'Prefix Icon', ... },
    suffixIcon: { type: 'icon', label: 'Suffix Icon', ... },
  },
  appearance: {
    // Don't include width here - need to modify BASE_PROPERTIES in registry.ts
    labelPosition: { type: 'position_picker', label: 'Label Position', default: 'top-left', ... },
    descriptionPosition: { type: 'position_picker', label: 'Description Position', default: 'bottom-left', ... },
  },
  advanced: {
    showCharacterCount: { type: 'boolean', label: 'Show Character Counter', ... },
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
      options: [
        { value: 'none', label: 'None' },
        { value: 'copy', label: 'Copy to Clipboard' },
        { value: 'clear', label: 'Clear Input' },
        { value: 'toggle-visibility', label: 'Toggle Visibility' }
      ]
    },
    helpTooltip: { type: 'string', label: 'Help Tooltip', ... }
  }
})
```

**B. Move width in BASE_PROPERTIES** (registry.ts line 147-199):
```typescript
const BASE_PROPERTIES: PropertiesByCategory = {
  general: {
    name: { ... },
    label: { ... },
    // Remove width from here
  },
  validation: {},
  appearance: {
    width: { ...move from general... },  // MOVE HERE
    hideLabel: { ... },
    cssClass: { ... },
  },
  // ...
}
```

#### Updating TextInput Component Rendering

**Current limitations:**
- No defaultValue rendering (bug fix needed)
- Fixed label/description position (always top-left, bottom-left)
- No prefix/suffix support
- No character counter
- No action buttons
- No help tooltip

**New rendering structure needed:**
```tsx
<div style={styles.fieldContainer} className={getLabelPositionClass()}>
  {shouldShowLabel() && (
    <div className="label-wrapper">
      <label>{properties.label} {required && <span>*</span>}</label>
      {helpTooltip && <Tooltip content={helpTooltip}><Info /></Tooltip>}
    </div>
  )}

  {descriptionPosition.startsWith('top') && <p>{description}</p>}

  <div className="input-group-wrapper">
    {prefixIcon && <Icon name={prefixIcon} />}
    {prefixText && <span>{prefixText}</span>}

    <input
      value={defaultValue || ''}
      placeholder={placeholder}
      {...}
    />

    {suffixText && <span>{suffixText}</span>}
    {suffixIcon && <Icon name={suffixIcon} />}
    {actionButton !== 'none' && <ActionButton type={actionButton} />}
  </div>

  {showCharacterCount && characterCountPosition === 'block-end' && (
    <div className="char-counter">{value.length} / {maxLength}</div>
  )}

  {descriptionPosition.startsWith('bottom') && <p>{description}</p>}
</div>
```

#### shadcn Components Available

From `src/react/admin/components/ui/`:
- ✅ `button.tsx` - Button with variants (default, outline, ghost, etc.)
- ✅ `input.tsx` - Base Input component
- ✅ `label.tsx` - Label component
- ✅ `select.tsx` - Select dropdown
- ✅ `checkbox.tsx` - Checkbox
- ✅ `dialog.tsx` - Modal dialog
- ✅ `tabs.tsx` - Tab navigation
- ✅ `tooltip.tsx` - Likely exists (check with Glob)
- ❌ `toggle-group.tsx` - NEEDS INSTALLATION
- ❌ `input-group` - May not exist in shadcn registry, might need custom build

**Installation command** (from `/src/react/admin/`):
```bash
npx shadcn@latest add toggle-group
npx shadcn@latest add tooltip  # If not present
```

**Config location:** `src/react/admin/components.json` specifies:
- Components go to `@/components/ui`
- Utils alias: `@/lib/utils`
- CSS file: `styles/index.css`
- Uses CSS variables for theming

#### Icon Picker Integration

Already implemented at `src/react/admin/components/ui/icon-picker/IconPicker.tsx`:
- Value format: `"fa:fas fa-user"` or `"lucide:user"`
- Props: `{ value?: string, onChange: (value: string) => void }`
- Features: Search, tabs for Font Awesome/Lucide, infinite scroll
- Used in PropertyRenderer case 'icon' (line 138-144)

**For prefix/suffix icons:**
- Reuse existing IconPicker component
- Parse value in TextInput: `parseIconValue(prefixIcon)` (function at line 39-61 in IconPicker.tsx)
- Render with DynamicLucideIcon or `<i className={faClass}>` (lines 162-237)

### Technical Reference Details

#### Element Store Structure (Zustand)

**Store location:** `src/react/admin/apps/form-builder-v2/store/useElementsStore.ts`

**FormElement interface** (types/index.ts lines 26-41):
```typescript
interface FormElement {
  id: string;              // UUID
  type: string;            // 'text', 'email', etc.
  properties: Record<string, any>;  // All properties from schema
  children?: string[];     // For containers
  parent?: string;         // Parent element ID
  styleOverrides?: Partial<Record<NodeType, Partial<StyleProperties>>>;
}
```

**Store actions:**
- `updateElement(id, updates)` - Merges partial updates (line 91-101)
- `setStyleOverride(elementId, nodeType, property, value)` - For style overrides (line 178-202)
- `addElement`, `removeElement`, `moveElement` - CRUD operations

**Usage pattern:**
```typescript
const element = useElementsStore(s => s.items[elementId]);  // Subscribe to specific element
const updateElement = useElementsStore(s => s.updateElement);  // Get action
updateElement(elementId, { properties: { ...element.properties, width: '1/2' } });
```

#### Style Resolution System

**Hook:** `useResolvedStyle(elementId, nodeType)` at `src/react/admin/apps/form-builder-v2/hooks/useResolvedStyle.ts`

**Returns:** `Partial<StyleProperties>` (merged global + overrides)

**Conversion to CSS:** `stylesToCSS(styleProps)` at `src/react/admin/lib/styleUtils.ts` (lines 16-53)
- Converts `fontSize: 16` → `{ fontSize: '16px' }`
- Converts `margin: { top: 10, right: 5, ... }` → `{ margin: '10px 5px ...' }`

**Node types for input elements:**
- `label` - Label text styles
- `input` - Input field styles
- `description` - Description text styles
- `required` - Asterisk styles
- `placeholder` - Placeholder text (pseudo-element)
- `fieldContainer` - Outer wrapper

#### Property Panel Categories

Defined in `PropertyCategorySchema` (types.ts line 141-147):
1. `'general'` - Basic settings (name, label, placeholder, etc.)
2. `'validation'` - Validation rules (required, minLength, pattern, etc.)
3. `'appearance'` - Visual settings (icons, CSS class, width)
4. `'advanced'` - Advanced options (autocomplete, readonly, etc.)
5. `'conditions'` - Conditional logic

**FloatingPanel tab mapping:**
- Content tab → Shows `['general']` category (ContentTab.tsx line 31)
- Behavior tab → Shows `['validation', 'advanced', 'conditions']` (likely, check BehaviorTab.tsx)
- Style tab → Uses separate NodeStyleEditor for style overrides
- Code tab → Shows element JSON

#### File Locations Summary

**Schema files (create/modify):**
- `/src/react/admin/schemas/core/types.ts` - Add `position_picker` to enum
- `/src/react/admin/schemas/core/registry.ts` - Move `width` from general to appearance in BASE_PROPERTIES
- `/src/react/admin/schemas/elements/text.ts` - Add new properties

**Renderer files (create/modify):**
- `/src/react/admin/apps/form-builder-v2/components/property-panels/schema/renderers/PositionPickerRenderer.tsx` (NEW)
- `/src/react/admin/apps/form-builder-v2/components/property-panels/schema/PropertyRenderer.tsx` - Add case for position_picker

**Component files (modify):**
- `/src/react/admin/apps/form-builder-v2/components/elements/basic/TextInput.tsx` - Complete rewrite to support all new features

**shadcn installation (from `/src/react/admin/`):**
```bash
npx shadcn@latest add toggle-group  # For PositionPicker
npx shadcn@latest add tooltip       # For help tooltip (if not present)
```

**Check existing components:**
```bash
ls /home/rens/super-forms/src/react/admin/components/ui/
```

### Architecture Patterns to Follow

**1. Locality of Behavior** - Keep related code together:
- PositionPickerRenderer should be self-contained (grid layout + state)
- Import only what's needed, avoid over-abstraction

**2. Schema is Source of Truth:**
- Never hardcode property lists in components
- Always use `getElementSchema()` to read capabilities
- PropertyRenderer drives all UI from schema type

**3. Zustand Store Pattern:**
- Subscribe to specific slices: `useElementsStore(s => s.items[id])`
- Actions at module level: `useElementsStore(s => s.updateElement)`
- Avoid subscribing to entire store (causes unnecessary re-renders)

**4. Style Override vs Property:**
- Properties control behavior/content (`placeholder`, `defaultValue`, `prefixText`)
- Style overrides control appearance (`fontSize`, `color`, `padding`)
- Never mix - keeps concerns separated

**5. Conditional Property Visibility:**
- Use `conditions` array in property schema
- PropertyRenderer automatically hides/shows based on other property values
- Example: Only show `characterCountPosition` when `showCharacterCount` is true

### Known Gotchas and Constraints

**1. Base Properties Modification:**
- `width` is in `BASE_PROPERTIES.general` - affects ALL elements
- Moving it requires updating registry.ts which is shared by all element types
- Test with other element types after moving (select, textarea, etc.)

**2. Element Re-rendering:**
- TextInput receives fresh props on every store update
- Use React.memo() if performance issues arise
- ElementRenderer already memoizes resolved styles (lines 39-59)

**3. Icon Picker Value Format:**
- Stored as string: `"fa:fas fa-user"` or `"lucide:user"`
- Must parse before rendering: Use `parseIconValue()` from IconPicker.tsx
- IconPicker component handles parsing internally

**4. Property Default Values:**
- Specified in schema `default` field
- Applied when element is created (FormBuilderV2 element creation logic)
- NOT automatically applied by PropertyRenderer (just shows initial state)

**5. Mobile vs Desktop UI:**
- FloatingPanel adapts: Desktop = positioned panel, Mobile = bottom drawer (Vaul)
- PropertyRenderer stays the same
- Test both viewports when adding new property types

**6. TypeScript Strict Mode:**
- All schemas validated with Zod at runtime
- Type errors caught at import time (fail-fast)
- Add proper TypeScript types for new property values in FormElement.properties

### Validation & Testing Checklist

After implementing changes:

1. **Schema registration:**
   - [ ] No Zod validation errors on page load
   - [ ] `getElementSchema('text')` returns updated schema
   - [ ] All new properties appear in correct categories

2. **Property rendering:**
   - [ ] PositionPicker shows 3x3 grid
   - [ ] Clicking position updates property value
   - [ ] Icon pickers open for prefix/suffix
   - [ ] Conditional fields hide/show correctly

3. **Canvas rendering:**
   - [ ] defaultValue shows in input field
   - [ ] Label/description render at correct positions
   - [ ] Prefix/suffix text appears
   - [ ] Icons render (both FA and Lucide)
   - [ ] Character counter displays when enabled
   - [ ] Action buttons work (copy, clear, toggle visibility)

4. **Data persistence:**
   - [ ] Property changes saved to Zustand store
   - [ ] Store state includes all new properties
   - [ ] Width property works in appearance category

5. **Cross-browser:**
   - [ ] Works in Chrome, Firefox, Safari
   - [ ] Mobile drawer works on touch devices
   - [ ] No console errors

6. **TypeScript:**
   - [ ] `npm run typecheck` passes
   - [ ] No type errors in editor
   - [ ] Proper types for new properties

## User Notes
- Stay on current branch: `feature/h-implement-triggers-actions-extensibility`
- Use toggle-group from shadcn as base for PositionPicker
- Both wrapper and input should have width settings
- Label and description need independent position control

## Work Log
<!-- Updated as work progresses -->
- [2025-12-10] Task created, analyzed shadcn input-group component features
