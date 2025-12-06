---
name: 07-element-renderer
parent: m-implement-styles-themes-ui
status: pending
created: 2025-12-06
---

# Subtask 7: ElementRenderer Style Integration

## Goal

Update the ElementRenderer and individual element components to use the resolved style system. This enables live preview of global theme changes and element-specific overrides.

## Success Criteria

- [ ] ElementRenderer uses useResolvedStyle for each node type
- [ ] applyStylesToReactStyle utility converts StyleProperties to CSSProperties
- [ ] All basic element components updated (TextInput, TextArea, Select, etc.)
- [ ] Live preview updates when global styles change
- [ ] Live preview updates when element overrides change
- [ ] Memoization for performance optimization
- [ ] TypeScript compiles cleanly

## Current State

The ElementRenderer at `/src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx` currently:
- Uses hardcoded inline styles from `element.properties`
- Passes `commonProps` object to child elements
- No integration with styleRegistry or hooks

```tsx
// Current - hardcoded styles
const commonProps: CommonProps = {
  className: "form-input",
  disabled: true,
  style: {
    width: element.properties?.width === 'full' ? '100%' : `${element.properties?.width || 100}%`,
    margin: element.properties?.margin,
    backgroundColor: element.properties?.backgroundColor,
    borderStyle: element.properties?.borderStyle,
  }
};
```

## Technical Specification

### Style Conversion Utility

Create `/src/react/admin/lib/styleUtils.ts`:

```typescript
import type { StyleProperties, BoxSpacing } from '../schemas/styles/types';
import type { CSSProperties } from 'react';

/**
 * Convert BoxSpacing to CSS margin/padding string
 */
function boxSpacingToCSS(spacing: BoxSpacing | undefined): string | undefined {
  if (!spacing) return undefined;
  return `${spacing.top}px ${spacing.right}px ${spacing.bottom}px ${spacing.left}px`;
}

/**
 * Convert StyleProperties to React CSSProperties
 * Handles unit conversion and shorthand properties
 */
export function stylesToCSS(style: Partial<StyleProperties>): CSSProperties {
  const css: CSSProperties = {};

  // Typography
  if (style.fontSize !== undefined) css.fontSize = `${style.fontSize}px`;
  if (style.fontFamily !== undefined) css.fontFamily = style.fontFamily;
  if (style.fontWeight !== undefined) css.fontWeight = style.fontWeight;
  if (style.fontStyle !== undefined) css.fontStyle = style.fontStyle;
  if (style.textAlign !== undefined) css.textAlign = style.textAlign;
  if (style.textDecoration !== undefined) css.textDecoration = style.textDecoration;
  if (style.lineHeight !== undefined) css.lineHeight = style.lineHeight;
  if (style.letterSpacing !== undefined) css.letterSpacing = `${style.letterSpacing}px`;

  // Colors
  if (style.color !== undefined) css.color = style.color;
  if (style.backgroundColor !== undefined) css.backgroundColor = style.backgroundColor;

  // Spacing
  if (style.margin !== undefined) css.margin = boxSpacingToCSS(style.margin);
  if (style.padding !== undefined) css.padding = boxSpacingToCSS(style.padding);

  // Border
  if (style.border !== undefined) {
    const b = style.border;
    css.borderWidth = `${b.top}px ${b.right}px ${b.bottom}px ${b.left}px`;
  }
  if (style.borderStyle !== undefined) css.borderStyle = style.borderStyle;
  if (style.borderColor !== undefined) css.borderColor = style.borderColor;
  if (style.borderRadius !== undefined) css.borderRadius = `${style.borderRadius}px`;

  // Sizing
  if (style.width !== undefined) {
    css.width = typeof style.width === 'number' ? `${style.width}px` : style.width;
  }
  if (style.minHeight !== undefined) css.minHeight = `${style.minHeight}px`;

  return css;
}

/**
 * Merge resolved style with element property overrides
 * Element properties take precedence for layout (width, margin)
 */
export function mergeWithElementProps(
  resolvedStyle: CSSProperties,
  elementProps: Record<string, unknown> = {}
): CSSProperties {
  const merged = { ...resolvedStyle };

  // Element properties can override layout
  if (elementProps.width !== undefined) {
    merged.width = elementProps.width === 'full'
      ? '100%'
      : `${elementProps.width}%`;
  }

  return merged;
}
```

### Updated ElementRenderer

```tsx
// /src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx

import React, { Suspense, lazy, useMemo } from 'react';
import { useResolvedStyle } from '../../hooks/useResolvedStyle';
import { stylesToCSS, mergeWithElementProps } from '../../../../lib/styleUtils';

// ... lazy imports ...

interface ElementRendererProps {
  element: {
    type: string;
    id: string;
    properties?: Record<string, unknown>;
    styleOverrides?: Record<string, Record<string, unknown>>;
  };
  updateElementProperty?: (id: string, property: string, value: unknown) => void;
}

export const ElementRenderer: React.FC<ElementRendererProps> = ({
  element,
  updateElementProperty,
}) => {
  // Resolve styles for common nodes
  const labelStyle = useResolvedStyle(element.id, 'label');
  const inputStyle = useResolvedStyle(element.id, 'input');
  const errorStyle = useResolvedStyle(element.id, 'error');
  const descriptionStyle = useResolvedStyle(element.id, 'description');
  const fieldContainerStyle = useResolvedStyle(element.id, 'fieldContainer');

  // Convert to CSS and memoize
  const resolvedStyles = useMemo(() => ({
    label: stylesToCSS(labelStyle),
    input: mergeWithElementProps(stylesToCSS(inputStyle), element.properties),
    error: stylesToCSS(errorStyle),
    description: stylesToCSS(descriptionStyle),
    fieldContainer: stylesToCSS(fieldContainerStyle),
  }), [labelStyle, inputStyle, errorStyle, descriptionStyle, fieldContainerStyle, element.properties]);

  const renderElement = () => {
    switch (element.type) {
      case 'text':
      case 'email':
      case 'phone':
      case 'url':
      case 'password':
      case 'number':
      case 'number-formatted':
        return (
          <TextInput
            element={element}
            styles={resolvedStyles}
          />
        );

      case 'textarea':
        return (
          <TextArea
            element={element}
            styles={resolvedStyles}
          />
        );

      case 'select':
        return (
          <Select
            element={element}
            styles={resolvedStyles}
          />
        );

      // ... other cases ...

      default:
        return (
          <div className="border border-gray-300 rounded-md p-4 text-center text-gray-500">
            Element type: {element.type} (not yet styled)
          </div>
        );
    }
  };

  return (
    <Suspense fallback={
      <div className="border border-gray-200 rounded-md p-4 text-center text-gray-400">
        Loading element...
      </div>
    }>
      <div style={resolvedStyles.fieldContainer}>
        {renderElement()}
      </div>
    </Suspense>
  );
};
```

### Updated Element Components

Example for TextInput:

```tsx
// /src/react/admin/apps/form-builder-v2/components/elements/basic/TextInput.tsx

import React from 'react';
import { CSSProperties } from 'react';

interface ResolvedStyles {
  label: CSSProperties;
  input: CSSProperties;
  error: CSSProperties;
  description: CSSProperties;
  fieldContainer: CSSProperties;
}

interface TextInputProps {
  element: {
    id: string;
    type: string;
    properties?: {
      label?: string;
      placeholder?: string;
      description?: string;
      required?: boolean;
    };
  };
  styles: ResolvedStyles;
}

export const TextInput: React.FC<TextInputProps> = ({ element, styles }) => {
  const { properties = {} } = element;

  return (
    <div>
      {/* Label */}
      {properties.label && (
        <label style={styles.label}>
          {properties.label}
          {properties.required && (
            <span className="text-red-500 ml-1">*</span>
          )}
        </label>
      )}

      {/* Description (before input) */}
      {properties.description && (
        <p style={styles.description}>
          {properties.description}
        </p>
      )}

      {/* Input */}
      <input
        type={element.type === 'password' ? 'password' : 'text'}
        placeholder={properties.placeholder}
        disabled
        style={styles.input}
      />
    </div>
  );
};

export default TextInput;
```

### Performance Optimization

**Memoization Strategy:**

1. `useResolvedStyle` already uses `useSyncExternalStore` for efficient updates
2. `useMemo` for CSS conversion to avoid recalculation on every render
3. Element components should be memoized with `React.memo`:

```tsx
export const TextInput = React.memo<TextInputProps>(({ element, styles }) => {
  // ...
});
```

4. Use `useStyleRegistryVersion` for cache invalidation:

```tsx
import { useStyleRegistryVersion } from '../../hooks/useGlobalStyles';

export const ElementRenderer = ({ element }) => {
  const version = useStyleRegistryVersion();

  // useMemo dependencies include version for cache invalidation
  const resolvedStyles = useMemo(() => ({
    // ...
  }), [/* other deps */, version]);
};
```

### Handling Different Node Types per Element

Different elements use different nodes. Map element type to relevant nodes:

```typescript
// Already exists in schemas/styles/elementNodes.ts
function getElementNodes(elementType: string): NodeType[] {
  switch (elementType) {
    case 'text':
    case 'email':
    case 'textarea':
      return ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'];

    case 'radio-cards':
    case 'checkbox-cards':
      return ['fieldContainer', 'label', 'description', 'cardContainer', 'optionLabel', 'error'];

    case 'button':
    case 'submit':
      return ['button'];

    case 'heading':
      return ['heading'];

    case 'paragraph':
      return ['paragraph'];

    case 'divider':
      return ['divider'];

    default:
      return ['fieldContainer', 'label', 'input'];
  }
}
```

## Files to Create

1. `/src/react/admin/lib/styleUtils.ts` - Style conversion utilities

## Files to Modify

1. `/src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx`
   - Import and use useResolvedStyle
   - Convert to CSS with stylesToCSS
   - Pass resolved styles to child components

2. `/src/react/admin/apps/form-builder-v2/components/elements/basic/TextInput.tsx`
   - Accept styles prop instead of commonProps
   - Apply resolved styles to label, input, etc.

3. `/src/react/admin/apps/form-builder-v2/components/elements/basic/TextArea.tsx`
   - Same pattern as TextInput

4. `/src/react/admin/apps/form-builder-v2/components/elements/choice/Select.tsx`
   - Same pattern

5. Other element components as needed

## Implementation Notes

- Start with basic elements (TextInput, TextArea, Select)
- Use consistent ResolvedStyles interface across components
- fieldContainer styles wrap the entire element
- Element-level width property can override style width
- Placeholder styling requires CSS ::placeholder (consider separate approach)
- Error styling only applies when validation fails (future integration)

## Test Cases

1. **Global style change:** Modify label fontSize in GlobalStylesPanel → all labels update
2. **Element override:** Unlink input borderColor for one element → only that element changes
3. **Theme switch:** Apply Dark theme → all elements update with new colors
4. **Reset to global:** Remove override → element returns to global style

## Dependencies

- Existing useResolvedStyle hook
- Existing styleRegistry
- Subtask 06 (FloatingPanel styles for setting overrides)
