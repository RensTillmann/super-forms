# TextInput Property Mapping & Component Matrix

## Architecture Overview

TextInput uses **conditional composition** to render either:
1. **shadcn Input** - Simple standalone input (no addons)
2. **shadcn InputGroup** - Input with prefix/suffix/icons/buttons

## ASCII Component Structure Matrix

### Simple Input (No Addons)
```
┌─────────────────────────────────────────┐
│ [Label] (top-left/center/right)        │  ← Our wrapper
│ [Description] (top-left/center/right)  │
│                                         │
│ ╔═══════════════════════════════════╗  │
│ ║ <Input />                         ║  │  ← shadcn Input component
│ ╚═══════════════════════════════════╝  │
│                                         │
│ [Description] (bottom-left/center/right│
│ [Label] (bottom-left/center/right)     │
│ [Character Count] (block-end)          │
└─────────────────────────────────────────┘
```

### Input Group (With Addons)
```
┌────────────────────────────────────────────────────────┐
│ [Label] (top-left/center/right)                       │  ← Our wrapper
│ [Description] (top-left/center/right)                 │
│                                                        │
│ ╔════════════════════════════════════════════════════╗│
│ ║ <InputGroup>                                       ║│  ← shadcn InputGroup
│ ║   ┌────────┬─────────────────┬────────┬─────────┐ ║│
│ ║   │ Prefix │ <InputGroupInput│ Suffix │ Action  │ ║│
│ ║   │ Addon  │      />         │ Addon  │ Button  │ ║│
│ ║   │(inline-│                 │(inline-│(inline- │ ║│
│ ║   │ start) │                 │  end)  │  end)   │ ║│
│ ║   └────────┴─────────────────┴────────┴─────────┘ ║│
│ ║   │ Char Count (inline-end) │                     ║│
│ ║   └─────────────────────────┘                     ║│
│ ║ </InputGroup>                                      ║│
│ ╚════════════════════════════════════════════════════╝│
│                                                        │
│ [Description] (bottom-left/center/right)              │
│ [Label] (bottom-left/center/right)                    │
│ [Character Count] (block-end)                         │
└────────────────────────────────────────────────────────┘
```

### Inline Label Layout (left/right positioning)
```
┌─────────────────────────────────────────────────────┐
│ ┌──────────┐  ╔═══════════════════════════════════╗│
│ │ [Label]  │  ║ <Input> or <InputGroup>          ║│
│ │ (w-1/3)  │  ║                                   ║│
│ └──────────┘  ╚═══════════════════════════════════╝│
│               [Description] (top/bottom)            │
│               [Character Count] (block-end)         │
└─────────────────────────────────────────────────────┘

OR (right-positioned):

┌─────────────────────────────────────────────────────┐
│ ╔═══════════════════════════════════╗ ┌──────────┐ │
│ ║ <Input> or <InputGroup>          ║ │ [Label]  │ │
│ ║                                   ║ │ (w-1/3)  │ │
│ ╚═══════════════════════════════════╝ └──────────┘ │
│ [Description] (top/bottom)                          │
│ [Character Count] (block-end)                       │
└─────────────────────────────────────────────────────┘
```

## Property Decision Tree

### Decision 1: Use Input vs InputGroup?

```
hasAddons = prefixText || suffixText || prefixIcon || suffixIcon ||
            actionButton !== 'none' ||
            (showCharacterCount && characterCountPosition === 'inline-end')

IF hasAddons:
  → Use InputGroup composition
ELSE:
  → Use simple Input component
```

### Decision 2: Label/Description Layout?

```
isInlineLabel = labelPosition === 'left' || labelPosition === 'right'

IF isInlineLabel:
  → Use flex layout (w-1/3 label + flex-1 input)
  → Apply flex-row-reverse if labelPosition === 'right'
ELSE:
  → Use vertical stack layout
  → Position label/description based on *-left/center/right alignment
```

## Complete Property Mapping

### Our Custom Properties → shadcn Components

| Our Property | shadcn Component | Notes |
|-------------|------------------|-------|
| `label` | Custom `<label>` wrapper | Not part of shadcn Input |
| `description` | Custom `<p>` wrapper | Not part of shadcn Input |
| `labelPosition` | Layout wrapper logic | Controls vertical/inline positioning |
| `descriptionPosition` | Layout wrapper logic | Controls vertical/inline positioning |
| `prefixText` | `InputGroupAddon` align="inline-start" + `InputGroupText` | Text before input |
| `suffixText` | `InputGroupAddon` align="inline-end" + `InputGroupText` | Text after input |
| `prefixIcon` | `InputGroupAddon` align="inline-start" + Lucide Icon | Icon before input |
| `suffixIcon` | `InputGroupAddon` align="inline-end" + Lucide Icon | Icon after input |
| `actionButton: 'copy'` | `InputGroupAddon` align="inline-end" + `InputGroupButton` + `<Copy/>` | Copy action |
| `actionButton: 'clear'` | `InputGroupAddon` align="inline-end" + `InputGroupButton` + `<X/>` | Clear action |
| `actionButton: 'toggle-visibility'` | `InputGroupAddon` align="inline-end" + `InputGroupButton` + `<Eye/><EyeOff/>` | Toggle password |
| `showCharacterCount` (inline-end) | `InputGroupAddon` align="inline-end" + custom div | Character counter |
| `showCharacterCount` (block-end) | Custom div after InputGroup | Block-level counter |
| `helpTooltip` | `Tooltip` + `TooltipTrigger` + `<Info/>` icon | In label |
| `required` | Custom `<span>*</span>` in label | Required indicator |
| `placeholder` | `InputGroupInput` placeholder prop | Standard HTML |
| `defaultValue` | `InputGroupInput` value prop | For canvas preview |
| `type` | `InputGroupInput` type prop | text, email, password, etc. |

## Addon Composition Rules

### Prefix Addon (inline-start)
```tsx
{(prefixIcon || prefixText) && (
  <InputGroupAddon align="inline-start">
    {prefixIcon && <LucideIcon name={prefixIcon} className="w-4 h-4" />}
    {prefixText && <InputGroupText>{prefixText}</InputGroupText>}
  </InputGroupAddon>
)}
```

### Suffix Addon (inline-end)
```tsx
{(suffixIcon || suffixText) && (
  <InputGroupAddon align="inline-end">
    {suffixText && <InputGroupText>{suffixText}</InputGroupText>}
    {suffixIcon && <LucideIcon name={suffixIcon} className="w-4 h-4" />}
  </InputGroupAddon>
)}
```

### Character Counter Addon (inline-end)
```tsx
{showCharacterCount && maxLength && characterCountPosition === 'inline-end' && (
  <InputGroupAddon align="inline-end">
    <InputGroupText className="text-xs text-muted-foreground">
      {charCount}/{maxLength}
    </InputGroupText>
  </InputGroupAddon>
)}
```

### Action Button Addon (inline-end)
```tsx
{actionButton !== 'none' && (
  <InputGroupAddon align="inline-end">
    <InputGroupButton
      variant="ghost"
      size="icon-xs"
      disabled
      aria-label={getActionLabel(actionButton)}
    >
      {actionButton === 'copy' && <Copy className="w-4 h-4" />}
      {actionButton === 'clear' && <X className="w-4 h-4" />}
      {actionButton === 'toggle-visibility' && <Eye className="w-4 h-4" />}
    </InputGroupButton>
  </InputGroupAddon>
)}
```

## Zod Schema (Schema-First Approach)

### Position Value Type
```typescript
const positionValueSchema = z.enum([
  'top-left', 'top-center', 'top-right',
  'left', 'center', 'right',
  'bottom-left', 'bottom-center', 'bottom-right'
]);
```

### Input Type
```typescript
const inputTypeSchema = z.enum([
  'text', 'email', 'phone', 'url',
  'password', 'number', 'number-formatted'
]);
```

### Action Button Type
```typescript
const actionButtonSchema = z.enum([
  'none', 'copy', 'clear', 'toggle-visibility'
]);
```

### Character Count Position
```typescript
const characterCountPositionSchema = z.enum([
  'inline-end',  // Inside InputGroup as addon
  'block-end'    // Below InputGroup as separate element
]);
```

### Complete TextInput Properties Schema
```typescript
const textInputPropertiesSchema = z.object({
  // Content
  label: z.string().optional(),
  placeholder: z.string().optional(),
  description: z.string().optional(),
  defaultValue: z.string().optional(),

  // Validation
  required: z.boolean().optional().default(false),
  maxLength: z.number().optional(),

  // Layout & Positioning
  labelPosition: positionValueSchema.optional().default('top-left'),
  descriptionPosition: positionValueSchema.optional().default('bottom-left'),
  width: z.union([z.string(), z.number()]).optional(),

  // Prefix/Suffix (triggers InputGroup)
  prefixText: z.string().optional(),
  suffixText: z.string().optional(),
  prefixIcon: z.string().optional(), // Lucide icon name
  suffixIcon: z.string().optional(), // Lucide icon name

  // Advanced Features (triggers InputGroup)
  actionButton: actionButtonSchema.optional().default('none'),
  showCharacterCount: z.boolean().optional().default(false),
  characterCountPosition: characterCountPositionSchema.optional().default('block-end'),

  // Help & Accessibility
  helpTooltip: z.string().optional(),
}).strict();
```

### Element Schema (with type discrimination)
```typescript
const textInputElementSchema = z.object({
  id: z.string(),
  type: inputTypeSchema,
  properties: textInputPropertiesSchema.optional(),
}).strict();
```

## Property Categories (for Property Panel Tabs)

### General Tab
- `label` - text_input
- `placeholder` - text_input
- `description` - textarea
- `defaultValue` - text_input
- `prefixText` - text_input
- `suffixText` - text_input
- `prefixIcon` - icon_picker (Lucide)
- `suffixIcon` - icon_picker (Lucide)

### Validation Tab
- `required` - toggle
- `maxLength` - number_input

### Appearance Tab
- `labelPosition` - position_picker (9-grid)
- `descriptionPosition` - position_picker (9-grid)
- `width` - dimension_input

### Advanced Tab
- `actionButton` - select (none, copy, clear, toggle-visibility)
- `showCharacterCount` - toggle
- `characterCountPosition` - select (inline-end, block-end)
- `helpTooltip` - text_input

## Style Application Strategy

### For Simple Input (no addons):
```typescript
<Input
  style={styles.input}  // Apply all theme styles directly
  className={cn(
    // Additional utility classes
  )}
/>
```

### For InputGroup (with addons):
```typescript
<InputGroup
  style={{
    borderWidth: styles.input?.borderWidth,
    borderStyle: styles.input?.borderStyle,
    borderColor: styles.input?.borderColor,
    borderRadius: styles.input?.borderRadius,
    backgroundColor: styles.input?.backgroundColor,
  }}
>
  <InputGroupInput
    style={{
      fontSize: styles.input?.fontSize,
      fontFamily: styles.input?.fontFamily,
      color: styles.input?.color,
      textAlign: styles.input?.textAlign,
      padding: styles.input?.padding,
    }}
  />
</InputGroup>
```

**Rationale:** InputGroup wrapper handles structural styles (border, background, radius) while InputGroupInput handles content styles (typography, alignment, padding).

## Component Selection Logic

```typescript
const renderInput = () => {
  const hasAddons =
    prefixText || suffixText ||
    prefixIcon || suffixIcon ||
    actionButton !== 'none' ||
    (showCharacterCount && maxLength && characterCountPosition === 'inline-end');

  if (!hasAddons) {
    // Simple Input
    return (
      <Input
        type={getInputType()}
        placeholder={getPlaceholder()}
        value={defaultValue || ''}
        style={styles.input}
        disabled
        readOnly
      />
    );
  }

  // InputGroup composition
  return (
    <InputGroup style={getWrapperStyles()}>
      {renderPrefixAddon()}
      <InputGroupInput
        type={getInputType()}
        placeholder={getPlaceholder()}
        value={defaultValue || ''}
        style={getInputStyles()}
        disabled
        readOnly
      />
      {renderInlineCharCounter()}
      {renderSuffixAddon()}
      {renderActionButton()}
    </InputGroup>
  );
};
```

## Migration Checklist

### Required shadcn Components
- [ ] `Input` - Already installed
- [ ] `InputGroup` - **NEED TO INSTALL**: `pnpm dlx shadcn@latest add input-group`
- [ ] `InputGroupInput` - Part of input-group
- [ ] `InputGroupAddon` - Part of input-group
- [ ] `InputGroupButton` - Part of input-group
- [ ] `InputGroupText` - Part of input-group
- [ ] `Tooltip` - Already installed (used for helpTooltip)
- [ ] Lucide icons - Already installed (Copy, X, Eye, EyeOff, Info)

### Code Changes Required
- [ ] Import InputGroup components from `@/components/ui/input-group`
- [ ] Refactor `renderInputGroup()` to use InputGroup composition
- [ ] Split style application: wrapper vs input styles
- [ ] Convert icon placeholders (•) to actual Lucide icon components
- [ ] Remove custom wrapper div, use InputGroup wrapper
- [ ] Update prefix/suffix rendering to use InputGroupAddon
- [ ] Update action buttons to use InputGroupButton
- [ ] Update character counter to use InputGroupAddon for inline-end
- [ ] Ensure proper addon ordering (prefix → input → counter → suffix → action)
- [ ] Test all property combinations
- [ ] Verify theme styles apply correctly to both modes
- [ ] TypeScript type checking passes
- [ ] No visual regressions in Form Builder V2

## Testing Matrix

### Test all combinations:
1. **Simple Input**
   - [ ] No addons (just input)
   - [ ] With label (9 positions)
   - [ ] With description (9 positions)
   - [ ] With inline label (left/right)
   - [ ] With required indicator
   - [ ] With help tooltip
   - [ ] With block-end character counter

2. **InputGroup Variants**
   - [ ] Prefix text only
   - [ ] Suffix text only
   - [ ] Prefix + suffix text
   - [ ] Prefix icon only
   - [ ] Suffix icon only
   - [ ] Prefix icon + text
   - [ ] Suffix icon + text
   - [ ] All addons combined
   - [ ] With copy button
   - [ ] With clear button
   - [ ] With toggle visibility button
   - [ ] With inline-end character counter
   - [ ] Multiple inline-end addons (counter + button)

3. **Layout Combinations**
   - [ ] All label positions × input variants
   - [ ] All description positions × input variants
   - [ ] Inline label + InputGroup

4. **Theme Compatibility**
   - [ ] Light theme applies correctly
   - [ ] Dark theme applies correctly
   - [ ] Custom theme styles override properly
   - [ ] No style conflicts or double borders
