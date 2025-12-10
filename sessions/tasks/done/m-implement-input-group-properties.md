---
name: m-implement-input-group-properties
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-12-10
completed: 2025-12-10
---

# Implement Input Group Properties for Text Fields

## Problem/Goal

Extend the FormBuilderV2 text field element with advanced input group features inspired by shadcn/ui input-group component. This includes prefix/suffix text and icons, flexible label/description positioning, character counters, action buttons, help tooltips, and loading states.

Additionally, move the `width` property from the General category to the Appearance category in the property panel, and fix the `defaultValue` not being applied to the input element preview.

## Success Criteria
- [x] Width property moved from General to Appearance category
- [x] DefaultValue applied to input element in canvas preview
- [x] PositionPicker component created (3x3 grid for label/description placement)
- [x] Label position property added with visual picker
- [x] Description position property added with visual picker
- [x] Prefix/suffix text properties added
- [x] Prefix/suffix icon properties added
- [x] Character counter option added with inline/block positioning
- [x] Action button option added (copy, clear, toggle-visibility)
- [x] Help tooltip property added
- [x] TextInput component updated to render all new properties
- [x] input-group shadcn component installed and integrated
- [x] Appearance and advanced categories added to StyleTab
- [x] Code review issues fixed (emoji placeholders, data-testid attributes, TooltipProvider position)

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

### Schema-Driven Property System Overview

The FormBuilderV2 uses a schema-first architecture where element properties, validation rules, and UI rendering are driven by TypeScript schemas registered at import time.

### Key Implementation Details

**Schema Registration:**
- Element schemas registered using `registerElement()` in `src/react/admin/schemas/elements/text.ts`
- `withBaseProperties()` merges element-specific props with base props
- Base properties defined in `src/react/admin/schemas/core/registry.ts`
- Width property moved from `BASE_PROPERTIES.general` to `BASE_PROPERTIES.appearance`

**Property Type System:**
- Added `position_picker` type to `PropertyTypeSchema` enum in `types.ts`
- Created `PositionPickerRenderer` component using toggle-group
- Registered in `PropertyRenderer.tsx` switch statement

**Property Panel Flow:**
- User clicks element → FloatingPanel opens → SchemaPropertyPanel renders
- PropertyRenderer maps property types to UI controls
- Changes flow through: PropertyRenderer → SchemaPropertyPanel → FloatingPanel → FormBuilderV2 → useElementsStore

**Element Rendering:**
- ElementRenderer dispatches to element-specific components
- TextInput component updated to render all input group features
- Uses `useResolvedStyle()` hook for style resolution
- Integrates with shadcn input-group component

### Component Architecture

**PositionPicker Component:**
- 3x3 grid using toggle-group from shadcn
- Values: top-left, top-center, top-right, left, center, right, bottom-left, bottom-center, bottom-right
- Single-select with visual feedback

**TextInput Component Structure:**
- Label wrapper with help tooltip integration
- Input group with prefix/suffix text and icons
- Character counter with inline-end or block-end positioning
- Action buttons (copy, clear, toggle-visibility)
- Dynamic label/description positioning based on property values

**Icon Integration:**
- Uses existing IconPicker component
- Value format: `"fa:fas fa-user"` or `"lucide:user"`
- Renders with DynamicLucideIcon or Font Awesome classes

### Technical Reference

**Element Store (Zustand):**
- Location: `src/react/admin/apps/form-builder-v2/store/useElementsStore.ts`
- Store actions: `updateElement`, `setStyleOverride`, `addElement`, `removeElement`, `moveElement`

**Style Resolution:**
- Hook: `useResolvedStyle(elementId, nodeType)` merges global styles + element overrides
- Node types: `label`, `input`, `description`, `required`, `placeholder`, `fieldContainer`

**Property Categories:**
1. `general` - Basic settings (name, label, placeholder, prefix/suffix)
2. `validation` - Validation rules (required, minLength, pattern)
3. `appearance` - Visual settings (icons, CSS class, width, label/description position)
4. `advanced` - Advanced options (autocomplete, readonly, character counter, action buttons, help tooltip)
5. `conditions` - Conditional logic

### Modified Files

**Schema:**
- `src/react/admin/schemas/core/types.ts` - Added `position_picker` type
- `src/react/admin/schemas/core/registry.ts` - Moved `width` to appearance category
- `src/react/admin/schemas/elements/text.ts` - Added input group properties

**Components:**
- `src/react/admin/apps/form-builder-v2/components/property-panels/schema/renderers/PositionPickerRenderer.tsx` - New 3x3 grid picker
- `src/react/admin/apps/form-builder-v2/components/property-panels/schema/PropertyRenderer.tsx` - Added position_picker case
- `src/react/admin/apps/form-builder-v2/components/elements/basic/TextInput.tsx` - Updated to render input group features
- `src/react/admin/apps/form-builder-v2/components/property-panels/StyleTab.tsx` - Added appearance and advanced categories


## Notes
- Implemented on branch: `feature/h-implement-triggers-actions-extensibility`
- All success criteria completed
- Code review fixes applied (placeholders, data-testid attributes, TooltipProvider)

## Work Log

### 2025-12-10

#### Completed
- Created PositionPicker component using toggle-group for 3x3 grid label/description placement
- Moved width property from General to Appearance category in BASE_PROPERTIES
- Added defaultValue rendering to TextInput component (fixed bug where value wasn't shown)
- Implemented prefix/suffix text properties with visual placeholders
- Implemented prefix/suffix icon properties with icon picker integration
- Added character counter with inline-end and block-end positioning options
- Implemented action buttons (copy, clear, toggle-visibility) with visual icons
- Added help tooltip property with info icon next to label
- Updated TextInput component to render all input group features
- Installed and integrated shadcn input-group component
- Added appearance and advanced categories to StyleTab.tsx for style property support
- Fixed code review issues: changed emoji placeholders to bullet points, added data-testid attributes throughout components, moved TooltipProvider to component root level

#### Decisions
- Used toggle-group from shadcn as base for PositionPicker (3x3 grid with single-select)
- Position values follow CSS naming: top-left, top-center, top-right, left, center, right, bottom-left, bottom-center, bottom-right
- Character counter requires maxLength property to function (shows warning if not set)
- Action buttons render as icon-only buttons in input suffix area
- Help tooltip renders info icon inline with label text

#### Discovered
- Width property was in BASE_PROPERTIES.general, affecting all element types globally
- DefaultValue property was defined in schema but not applied to input element
- StyleTab was missing appearance and advanced categories for comprehensive style editing
- TooltipProvider needed to be at component root to work properly with other tooltip instances

#### Next Steps
- Test all new properties across different element types to ensure no regressions
- Verify mobile responsive behavior of position picker and input group features
- Consider adding validation for character counter (ensure maxLength is set when enabled)
