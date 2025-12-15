---
name: m-refactor-textinput-shadcn-input-group
branch: feature/h-implement-triggers-actions-extensibility
status: completed
created: 2025-12-13
completed: 2025-12-14
---

# Refactor TextInput to Use shadcn Input/Input Group Patterns

## Problem/Goal
The current TextInput component has conflicting style systems causing visual bugs:
- Double borders (wrapper div + input element both have borders)
- Inline styles from theme system override Tailwind classes
- Not following shadcn/ui patterns for Input and Input Group components
- Custom wrapper implementation instead of using shadcn composition patterns

Need to refactor TextInput to:
1. Use shadcn `Input` component for simple text inputs (no addons)
2. Use shadcn `Input Group` pattern for inputs with prefix/suffix/icons/action buttons
3. Properly apply theme styles to correct elements (wrapper vs input)
4. Support all property combinations: prefix/suffix text, icons (inside/outside positioning), action buttons

## Success Criteria
- [x] TextInput uses shadcn Input component when no addons are present
- [x] TextInput uses shadcn Input Group pattern when prefix/suffix/icons/actions are configured
- [x] No double borders or style conflicts between theme system and Tailwind
- [x] Theme styles applied to appropriate element (wrapper for groups, input for standalone)
- [x] Icon rendering works with Lucide icons (both prefix and suffix)
- [x] Inside/outside positioning for addons works correctly
- [x] All existing properties still function (label positions, descriptions, character count, action buttons, help tooltips)
- [x] Visual appearance matches shadcn design system
- [x] Component passes TypeScript type checking
- [x] No regressions in Form Builder V2 canvas preview

## Context Manifest

### How the Current TextInput Component Works

The TextInput component (`src/react/admin/apps/form-builder-v2/components/elements/basic/TextInput.tsx`) is a canvas preview renderer used within Form Builder V2. It displays a disabled/read-only representation of how a text input will look to end users. The component is not a functioning form input - it's purely for visual preview in the builder interface.

**Entry Point and Invocation:**

When the form builder renders elements on the canvas, it uses `ElementRenderer.tsx` which lazy-loads the TextInput component. The renderer passes two critical pieces of data:

1. `element` object containing the element's type, id, and properties
2. `styles` object (type `ResolvedStyles`) with pre-calculated CSS for each node type (label, input, description, etc.)

**Style Resolution Flow:**

Before TextInput even renders, ElementRenderer performs a complex style resolution process:

1. For each node type (label, input, error, description, etc.), it calls `useResolvedStyle(element.id, nodeType)`
2. This hook fetches global styles from the `styleRegistry` and merges them with element-specific `styleOverrides`
3. Global styles come from the currently applied theme (Light, Dark, or custom themes stored in `wp_superforms_themes` table)
4. Element overrides are stored per-element in the form data structure as `element.styleOverrides.input`, `element.styleOverrides.label`, etc.
5. The merged styles are converted from `StyleProperties` (Zod schema types like `fontSize: 16`) to React `CSSProperties` (like `fontSize: '16px'`) via `stylesToCSS()` utility
6. The `input` styles additionally get merged with element layout properties (width) via `mergeWithElementProps()`

**Current Rendering Architecture:**

The component uses a custom wrapper-based approach that predates shadcn patterns. Here's the exact flow:

```
<div> (outer wrapper with fieldContainer styles)
  {labelPosition === 'top-*' && <label with styles.label>}
  {descriptionPosition === 'top-*' && <p with styles.description>}

  <div role="group" className="flex border rounded-md"> (INPUT GROUP WRAPPER)
    {hasPrefix && <div with bg-muted/50, border-r>prefix content</div>}

    <input
      style={styles.input}  ← ALL theme styles applied here
      className="flex-1 px-3 py-2 border-0..." ← Tailwind overrides
      disabled readOnly
    />

    {showCharacterCount && characterCountPosition === 'inline-end' && <div>count</div>}
    {hasSuffix && <div with bg-muted/50, border-l>suffix content</div>}
    {actionButton !== 'none' && <button>icon</button>}
  </div>

  {descriptionPosition === 'bottom-*' && <p>}
  {labelPosition === 'bottom-*' && <label>}
  {showCharacterCount && characterCountPosition === 'block-end' && <div>}
</div>
```

**The Double Border Problem:**

The issue happens because `styles.input` contains border properties from the theme (e.g., `borderWidth: '1px', borderColor: '#ccc', borderRadius: '4px'`) which are applied as inline styles to the `<input>` element. But the wrapper `<div role="group">` ALSO has `className="flex border rounded-md"` which adds ANOTHER border via Tailwind. This creates two overlapping borders.

Additionally, when prefix/suffix addons exist, the wrapper div has the outer border/radius, but the input still has its own border/radius from inline styles, causing visual artifacts at the boundaries.

**Property System Integration:**

The component receives 27+ properties from the schema system (`src/react/admin/schemas/elements/text.ts`):

- **General category**: label, placeholder, description, defaultValue, prefixText, suffixText, prefixIcon, suffixIcon
- **Validation category**: required, minLength, maxLength, pattern, customError
- **Appearance category**: labelPosition (9-grid position picker), descriptionPosition, inputIcon, iconPosition
- **Advanced category**: autocomplete, readOnly, showCharacterCount, characterCountPosition, actionButton, helpTooltip

These properties are registered via Zod schemas in the element registry and automatically render in the property panel. The component receives them via `element.properties` and must handle all combinations.

**Icon Rendering Gap:**

Currently, icons are shown as placeholder bullets (•) because the component uses static imports:
```tsx
import { Info, Copy, X, Eye, EyeOff } from 'lucide-react';
```

For action buttons, it directly renders these imported icons. But for `prefixIcon` and `suffixIcon` properties (which are strings like "mail", "search", "user"), the component doesn't have dynamic icon loading, so it renders bullets as placeholders.

The codebase HAS a dynamic icon loading system at `src/react/admin/components/ui/icon-picker/lucide-icons.ts` with a `loadLucideIcon(name: string)` async function that uses dynamic imports like:
```ts
const module = await import(`lucide-react/dist/esm/icons/${name}.js`);
```

However, this async loading pattern doesn't fit the synchronous render flow, so icons need a different approach (likely a separate Icon component with Suspense).

**Label Position Complexity:**

When `labelPosition` is 'left' or 'right', the entire layout switches to inline mode:
```tsx
<div className="flex items-center gap-3 flex-row-reverse?">
  <div className="w-1/3 shrink-0">{label}</div>
  <div className="flex-1">{input + descriptions}</div>
</div>
```

This inline layout must work with both simple Input AND InputGroup, maintaining consistent spacing and alignment.

**Character Counter Positions:**

The character counter has two modes:
- `inline-end`: Rendered inside the input group wrapper as an addon (like `{charCount}/{maxLength}`)
- `block-end`: Rendered below the entire input group as a separate div

When inline-end, it must coordinate with suffix addons and action buttons, all appearing at the end of the input.

**Action Buttons:**

The `actionButton` property supports four values:
- `none`: No button
- `copy`: Copy input value to clipboard
- `clear`: Clear the input
- `toggle-visibility`: Toggle password visibility (shows Eye/EyeOff icon)

In the canvas preview, these buttons are rendered disabled (pointer-events-none) with 50% opacity since they're non-functional previews. The actual functionality would be implemented in the frontend form rendering.

**Disabled State:**

ALL inputs in the canvas preview are `disabled` and `readOnly` because this is just a visual preview, not a functioning form. This is critical to remember - we're building a preview renderer, not a form input.

### What Needs to Change: shadcn Input/InputGroup Integration

shadcn/ui provides two components we need to adopt:

**1. Input Component** (`src/react/admin/components/ui/input.tsx`):
Already installed. It's a simple wrapper around `<input>` with pre-applied Tailwind classes:
```tsx
<input className={cn(
  "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2...",
  className
)} />
```

**2. InputGroup System** (NOT YET INSTALLED):
A composition pattern with these components:
- `InputGroup`: Wrapper container
- `InputGroupInput`: Replacement for Input with adjusted styles for group context
- `InputGroupAddon`: Container for prefix/suffix content (align prop: inline-start/inline-end/block-start/block-end)
- `InputGroupText`: Text addons (like "$", ".com", "USD")
- `InputGroupButton`: Button addons (with variant/size props)

**Installation Required:**
```bash
cd /home/rens/super-forms/src/react/admin
pnpm dlx shadcn@latest add input-group
```

This will install to `src/react/admin/components/ui/input-group.tsx` (or similar).

**Decision Tree for Component Selection:**

The refactored component must choose between two rendering paths:

```typescript
const hasAddons = !!(
  prefixText || suffixText ||
  prefixIcon || suffixIcon ||
  actionButton !== 'none' ||
  (showCharacterCount && maxLength && characterCountPosition === 'inline-end')
);

if (!hasAddons) {
  // SIMPLE PATH: Use standalone Input component
  return <Input style={styles.input} ... />
} else {
  // COMPLEX PATH: Use InputGroup composition
  return (
    <InputGroup style={wrapperStyles}>
      {renderPrefixAddon()}
      <InputGroupInput style={inputStyles} ... />
      {renderInlineCharCounter()}
      {renderSuffixAddon()}
      {renderActionButton()}
    </InputGroup>
  )
}
```

**Style Splitting Strategy:**

The current `styles.input` object contains ALL CSS properties for the input. When using InputGroup, these styles must be split between wrapper and input:

**Wrapper styles** (applied to InputGroup):
- `borderWidth`, `borderStyle`, `borderColor`, `borderRadius` - The group container has the border
- `backgroundColor` - Group container background

**Input styles** (applied to InputGroupInput):
- `fontSize`, `fontFamily`, `fontWeight`, `fontStyle`, `lineHeight`, `letterSpacing` - Typography
- `color`, `textAlign` - Text appearance
- `padding` - Inner spacing (though InputGroupInput may override this)

This splitting prevents the double-border issue because only ONE element (the wrapper) has border styles.

**Addon Composition Patterns:**

For prefix addon:
```tsx
{(prefixIcon || prefixText) && (
  <InputGroupAddon align="inline-start">
    {prefixIcon && <DynamicIcon name={prefixIcon} className="w-4 h-4" />}
    {prefixText && <InputGroupText>{prefixText}</InputGroupText>}
  </InputGroupAddon>
)}
```

For suffix addon:
```tsx
{(suffixText || suffixIcon) && (
  <InputGroupAddon align="inline-end">
    {suffixText && <InputGroupText>{suffixText}</InputGroupText>}
    {suffixIcon && <DynamicIcon name={suffixIcon} className="w-4 h-4" />}
  </InputGroupAddon>
)}
```

For action button:
```tsx
{actionButton !== 'none' && (
  <InputGroupAddon align="inline-end">
    <InputGroupButton variant="ghost" size="icon-xs" disabled>
      {actionButton === 'copy' && <Copy className="w-4 h-4" />}
      {actionButton === 'clear' && <X className="w-4 h-4" />}
      {actionButton === 'toggle-visibility' && <Eye className="w-4 h-4" />}
    </InputGroupButton>
  </InputGroupAddon>
)}
```

**Ordering Rules:**

Addons must appear in this order for proper tab navigation and visual flow:
1. Prefix addons (icon + text)
2. InputGroupInput
3. Inline character counter (if enabled)
4. Suffix addons (text + icon)
5. Action button

Multiple inline-end addons are allowed and will stack horizontally.

**Label and Description Wrapper:**

The outer wrapper structure (label positioning, description positioning) remains unchanged. Only the input/InputGroup rendering changes. The component still needs to handle:
- Vertical layout (label/description top/bottom with left/center/right alignment)
- Inline layout (label left/right with 1/3 + flex-1 split)
- Help tooltip in label (using existing Tooltip components)
- Required asterisk in label

### Technical Reference

**Import Statements (after installation):**

```tsx
import { Input } from '@/components/ui/input';
import {
  InputGroup,
  InputGroupInput,
  InputGroupAddon,
  InputGroupButton,
  InputGroupText,
} from '@/components/ui/input-group';
import { Copy, X, Eye, EyeOff, Info } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
```

**Type Definitions:**

```typescript
interface TextInputProps {
  element: {
    type: 'text' | 'email' | 'phone' | 'url' | 'password' | 'number' | 'number-formatted';
    id: string;
    properties?: {
      label?: string;
      placeholder?: string;
      description?: string;
      required?: boolean;
      defaultValue?: string;
      labelPosition?: PositionValue;
      descriptionPosition?: PositionValue;
      prefixText?: string;
      suffixText?: string;
      prefixIcon?: string; // Lucide icon name
      suffixIcon?: string; // Lucide icon name
      maxLength?: number;
      showCharacterCount?: boolean;
      characterCountPosition?: 'inline-end' | 'block-end';
      actionButton?: 'none' | 'copy' | 'clear' | 'toggle-visibility';
      helpTooltip?: string;
    };
  };
  styles: ResolvedStyles; // From lib/styleUtils
}

type PositionValue = 'top-left' | 'top-center' | 'top-right' |
                     'left' | 'center' | 'right' |
                     'bottom-left' | 'bottom-center' | 'bottom-right';
```

**ResolvedStyles Type (from lib/styleUtils.ts):**

```typescript
interface ResolvedStyles {
  label: CSSProperties;
  input: CSSProperties;
  error: CSSProperties;
  description: CSSProperties;
  placeholder: CSSProperties;
  required: CSSProperties;
  fieldContainer: CSSProperties;
  heading: CSSProperties;
  paragraph: CSSProperties;
  button: CSSProperties;
  divider: CSSProperties;
  optionLabel: CSSProperties;
  cardContainer: CSSProperties;
}
```

**Style Splitting Utility Functions:**

```typescript
// Extract wrapper styles (border, background, radius)
function getWrapperStyles(inputStyles: CSSProperties): CSSProperties {
  return {
    borderWidth: inputStyles.borderWidth,
    borderStyle: inputStyles.borderStyle,
    borderColor: inputStyles.borderColor,
    borderRadius: inputStyles.borderRadius,
    backgroundColor: inputStyles.backgroundColor,
  };
}

// Extract input-specific styles (typography, padding)
function getInputOnlyStyles(inputStyles: CSSProperties): CSSProperties {
  return {
    fontSize: inputStyles.fontSize,
    fontFamily: inputStyles.fontFamily,
    fontWeight: inputStyles.fontWeight,
    fontStyle: inputStyles.fontStyle,
    textAlign: inputStyles.textAlign,
    lineHeight: inputStyles.lineHeight,
    letterSpacing: inputStyles.letterSpacing,
    color: inputStyles.color,
    padding: inputStyles.padding,
  };
}
```

**Dynamic Icon Component (to be created or imported):**

The codebase has dynamic icon loading utilities but no synchronous Icon component. Options:

1. Create a simple Icon component with Suspense:
```tsx
const LucideIcon: React.FC<{ name: string; className?: string }> = ({ name, className }) => {
  const Icon = useMemo(() => {
    // Lazy load icon - needs async handling
    // For now, fallback to static rendering
    return null;
  }, [name]);
  return Icon ? <Icon className={className} /> : <span>•</span>;
};
```

2. Use static bullet placeholders for now (match current behavior)
3. Import commonly used icons statically (Mail, Search, User, etc.) with a mapping

Recommend approach #3 for MVP: Add static imports for 10-15 common icons with a fallback bullet.

**Testing Combinations:**

Must test these scenarios to ensure no regressions:

1. Simple input (no addons) - should use Input component
2. Input with prefix text only
3. Input with suffix text only
4. Input with both prefix and suffix text
5. Input with prefix icon (when icon rendering implemented)
6. Input with suffix icon
7. Input with prefix icon + text combo
8. Input with suffix icon + text combo
9. Input with action button (copy)
10. Input with action button (clear)
11. Input with action button (toggle-visibility for password type)
12. Input with inline-end character counter
13. Input with block-end character counter
14. Input with multiple inline-end addons (counter + suffix + action button)
15. All label positions (9 options) × simple input
16. All label positions × InputGroup
17. Inline label (left/right) with InputGroup
18. Required field with help tooltip
19. Light theme styling
20. Dark theme styling
21. Custom theme with unusual border/background values

**File Locations:**

- Component to refactor: `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/elements/basic/TextInput.tsx`
- shadcn Input (exists): `/home/rens/super-forms/src/react/admin/components/ui/input.tsx`
- shadcn InputGroup (needs install): `/home/rens/super-forms/src/react/admin/components/ui/input-group.tsx` (after install)
- Element schema: `/home/rens/super-forms/src/react/admin/schemas/elements/text.ts`
- Style utilities: `/home/rens/super-forms/src/react/admin/lib/styleUtils.ts`
- Element renderer: `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx`
- Icon utilities: `/home/rens/super-forms/src/react/admin/components/ui/icon-picker/lucide-icons.ts`
- Style hooks: `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/hooks/useResolvedStyle.ts`

**Build Commands:**

```bash
cd /home/rens/super-forms/src/react/admin
npm run watch              # Development mode with hot reload
npm run typecheck          # Verify TypeScript types
```

**Related Components for Consistency:**

After refactoring TextInput, these components may need similar updates:

- `TextArea.tsx` - Currently has same double-border issue
- `Select.tsx` - May benefit from InputGroup pattern for addons

**Key Constraints:**

1. Must maintain ALL existing functionality (27+ properties)
2. Must work with disabled/readonly state (canvas preview mode)
3. Must properly apply theme styles without conflicts
4. Must pass TypeScript type checking
5. Must not break ElementRenderer integration
6. Must handle all label/description position combinations
7. Must maintain accessibility (ARIA labels, data-testid attributes)
8. Must work on mobile (component used in responsive canvas)

**Success Metrics:**

- No double borders visible in any theme
- Theme border/background styles apply to correct element
- All 27+ properties still function
- TypeScript compiles without errors
- Visual appearance matches shadcn design system
- No regressions in Form Builder V2 canvas rendering

## User Notes
Reference documentation saved in task directory:
- `shadcn-input.md` - Standard Input component docs
- `shadcn-input-group.md` - Input Group pattern for addons

Key implementation notes:
- Stay on current branch: `feature/h-implement-triggers-actions-extensibility`
- Preserve all existing functionality (label/description positioning, character counter, etc.)
- Must work with disabled/readonly state for canvas preview
- Theme system provides `styles.input` object with CSS properties

## Work Log
- [2025-12-13] Task created, identified double border and style conflict issues
- [2025-12-13] Installed shadcn InputGroup component via `pnpm dlx shadcn@latest add input-group`
- [2025-12-13] Implemented style splitting utilities (`getWrapperStyles`, `getInputOnlyStyles`) to prevent double borders
- [2025-12-13] Refactored TextInput component to use shadcn Input for simple inputs and InputGroup for addons
- [2025-12-13] Added data-testid attributes to all elements for testing
- [2025-12-13] Verified all 27+ properties work correctly (prefix/suffix, icons, character counter, action buttons, label positions)
- [2025-12-13] Implemented iframe isolation for Form Builder V2 as additional CSS isolation layer
- [2025-12-14] Task marked as completed - all success criteria met (commit: 3dbbb5d8)
