---
name: 09-tailwind-integration
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 9: Tailwind Integration

## Goal

Implement the Tailwind CSS class generation system for form elements, replacing inline styles with utility classes.

## Dependencies

- Phase 3 (Theme Schema) must be complete
- Phase 8 (Property Panels) recommended for testing

## Design Principles

1. **WYSIWYG Guarantee**: Builder preview = frontend output
2. **sf- Prefix**: All Super Forms classes use `sf-` prefix to avoid conflicts
3. **CSS Variables**: Custom values use CSS variables, not inline styles
4. **No Inline Styles**: Except for user's custom CSS overrides

## Deliverables

### 1. CSS Variable System (`/src/react/admin/styles/sf-variables.css`)

```css
/* Super Forms CSS Variables */
:root {
  /* Primary Colors */
  --sf-primary: #3b82f6;
  --sf-primary-hover: #2563eb;
  --sf-primary-text: #ffffff;

  /* Neutral Colors */
  --sf-background: #ffffff;
  --sf-foreground: #0f172a;
  --sf-muted: #f1f5f9;
  --sf-muted-foreground: #64748b;
  --sf-border: #e2e8f0;

  /* Semantic Colors */
  --sf-error: #ef4444;
  --sf-success: #22c55e;
  --sf-warning: #f59e0b;

  /* Spacing (matches Tailwind scale) */
  --sf-spacing-xs: 0.25rem;   /* 4px */
  --sf-spacing-sm: 0.5rem;    /* 8px */
  --sf-spacing-md: 1rem;      /* 16px */
  --sf-spacing-lg: 1.5rem;    /* 24px */
  --sf-spacing-xl: 2rem;      /* 32px */

  /* Border Radius */
  --sf-radius-none: 0;
  --sf-radius-sm: 0.25rem;    /* 4px */
  --sf-radius-md: 0.375rem;   /* 6px */
  --sf-radius-lg: 0.5rem;     /* 8px */
  --sf-radius-xl: 0.75rem;    /* 12px */
  --sf-radius-full: 9999px;

  /* Shadows */
  --sf-shadow-none: none;
  --sf-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --sf-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --sf-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);

  /* Typography */
  --sf-font-family: inherit;
  --sf-font-size-xs: 0.75rem;
  --sf-font-size-sm: 0.875rem;
  --sf-font-size-base: 1rem;
  --sf-font-size-lg: 1.125rem;
  --sf-font-size-xl: 1.25rem;

  /* Input Specific */
  --sf-input-height: 2.5rem;
  --sf-input-padding-x: 0.75rem;
  --sf-input-padding-y: 0.5rem;
  --sf-input-border-width: 1px;

  /* Focus Ring */
  --sf-focus-ring-width: 2px;
  --sf-focus-ring-color: var(--sf-primary);
  --sf-focus-ring-offset: 2px;
}
```

### 2. Tailwind Config Extension (`/src/react/admin/tailwind.sf.config.js`)

```javascript
// Super Forms Tailwind Extension
module.exports = {
  theme: {
    extend: {
      colors: {
        'sf-primary': 'var(--sf-primary)',
        'sf-primary-hover': 'var(--sf-primary-hover)',
        'sf-primary-text': 'var(--sf-primary-text)',
        'sf-background': 'var(--sf-background)',
        'sf-foreground': 'var(--sf-foreground)',
        'sf-muted': 'var(--sf-muted)',
        'sf-muted-foreground': 'var(--sf-muted-foreground)',
        'sf-border': 'var(--sf-border)',
        'sf-error': 'var(--sf-error)',
        'sf-success': 'var(--sf-success)',
        'sf-warning': 'var(--sf-warning)',
      },
      spacing: {
        'sf-xs': 'var(--sf-spacing-xs)',
        'sf-sm': 'var(--sf-spacing-sm)',
        'sf-md': 'var(--sf-spacing-md)',
        'sf-lg': 'var(--sf-spacing-lg)',
        'sf-xl': 'var(--sf-spacing-xl)',
      },
      borderRadius: {
        'sf-none': 'var(--sf-radius-none)',
        'sf-sm': 'var(--sf-radius-sm)',
        'sf-md': 'var(--sf-radius-md)',
        'sf-lg': 'var(--sf-radius-lg)',
        'sf-xl': 'var(--sf-radius-xl)',
        'sf-full': 'var(--sf-radius-full)',
      },
      boxShadow: {
        'sf-none': 'var(--sf-shadow-none)',
        'sf-sm': 'var(--sf-shadow-sm)',
        'sf-md': 'var(--sf-shadow-md)',
        'sf-lg': 'var(--sf-shadow-lg)',
      },
      fontFamily: {
        'sf': 'var(--sf-font-family)',
      },
      fontSize: {
        'sf-xs': 'var(--sf-font-size-xs)',
        'sf-sm': 'var(--sf-font-size-sm)',
        'sf-base': 'var(--sf-font-size-base)',
        'sf-lg': 'var(--sf-font-size-lg)',
        'sf-xl': 'var(--sf-font-size-xl)',
      },
    },
  },
};
```

### 3. Class Generator Utilities (`/src/schemas/utils/class-generator.ts`)

```typescript
import { ThemeSchema, ElementStyleProperties } from '../types';

// Generate form wrapper classes
export function generateFormWrapperClasses(theme: ThemeSchema): string {
  const classes: string[] = ['sf-form'];

  // Background
  if (theme.form.backgroundColor) {
    classes.push(`bg-[${theme.form.backgroundColor}]`);
  } else {
    classes.push('bg-sf-background');
  }

  // Padding
  const paddingMap: Record<string, string> = {
    none: 'p-0',
    sm: 'p-sf-sm',
    md: 'p-sf-md',
    lg: 'p-sf-lg',
    xl: 'p-sf-xl',
  };
  classes.push(paddingMap[theme.form.padding] || 'p-sf-md');

  // Border radius
  const radiusMap: Record<string, string> = {
    none: 'rounded-sf-none',
    sm: 'rounded-sf-sm',
    md: 'rounded-sf-md',
    lg: 'rounded-sf-lg',
    xl: 'rounded-sf-xl',
  };
  classes.push(radiusMap[theme.form.borderRadius] || 'rounded-sf-md');

  // Shadow
  const shadowMap: Record<string, string> = {
    none: 'shadow-sf-none',
    sm: 'shadow-sf-sm',
    md: 'shadow-sf-md',
    lg: 'shadow-sf-lg',
  };
  classes.push(shadowMap[theme.form.shadow] || 'shadow-sf-none');

  // Max width
  const widthMap: Record<string, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    full: 'max-w-full',
    none: '',
  };
  if (theme.form.maxWidth && widthMap[theme.form.maxWidth]) {
    classes.push(widthMap[theme.form.maxWidth]);
  }

  return classes.filter(Boolean).join(' ');
}

// Generate input element classes
export function generateInputClasses(
  theme: ThemeSchema,
  elementStyle?: ElementStyleProperties
): string {
  const classes: string[] = ['sf-input'];

  // Base input styles
  classes.push(
    'w-full',
    'border',
    'border-sf-border',
    'bg-sf-background',
    'text-sf-foreground',
    'rounded-sf-md',
    'px-3',
    'py-2',
    'text-sf-base',
    'transition-colors',
    'focus:outline-none',
    'focus:ring-2',
    'focus:ring-sf-primary',
    'focus:border-sf-primary',
    'disabled:opacity-50',
    'disabled:cursor-not-allowed'
  );

  // Size variants from theme
  if (theme.inputs.size === 'sm') {
    classes.push('h-8', 'text-sf-sm');
  } else if (theme.inputs.size === 'lg') {
    classes.push('h-12', 'text-sf-lg');
  } else {
    classes.push('h-10');
  }

  // Full width or auto
  if (elementStyle?.width === 'auto') {
    classes.splice(classes.indexOf('w-full'), 1);
    classes.push('w-auto');
  }

  return classes.filter(Boolean).join(' ');
}

// Generate label classes
export function generateLabelClasses(
  theme: ThemeSchema,
  elementStyle?: ElementStyleProperties
): string {
  const classes: string[] = ['sf-label'];

  // Base label styles
  classes.push(
    'block',
    'text-sf-foreground',
    'font-medium',
    'mb-1'
  );

  // Size from theme
  const sizeMap: Record<string, string> = {
    sm: 'text-sf-sm',
    md: 'text-sf-base',
    lg: 'text-sf-lg',
  };
  classes.push(sizeMap[theme.labels.size] || 'text-sf-base');

  // Position (for inline labels)
  if (theme.labels.position === 'inline') {
    classes.push('inline-block', 'mr-2', 'mb-0');
  }

  return classes.filter(Boolean).join(' ');
}

// Generate button classes
export function generateButtonClasses(
  theme: ThemeSchema,
  variant: 'primary' | 'secondary' | 'outline' = 'primary'
): string {
  const classes: string[] = ['sf-button'];

  // Base button styles
  classes.push(
    'inline-flex',
    'items-center',
    'justify-center',
    'font-medium',
    'rounded-sf-md',
    'transition-colors',
    'focus:outline-none',
    'focus:ring-2',
    'focus:ring-offset-2',
    'disabled:opacity-50',
    'disabled:cursor-not-allowed'
  );

  // Variant styles
  if (variant === 'primary') {
    classes.push(
      'bg-sf-primary',
      'text-sf-primary-text',
      'hover:bg-sf-primary-hover',
      'focus:ring-sf-primary'
    );
  } else if (variant === 'secondary') {
    classes.push(
      'bg-sf-muted',
      'text-sf-foreground',
      'hover:bg-sf-border',
      'focus:ring-sf-muted'
    );
  } else if (variant === 'outline') {
    classes.push(
      'border',
      'border-sf-border',
      'bg-transparent',
      'text-sf-foreground',
      'hover:bg-sf-muted',
      'focus:ring-sf-border'
    );
  }

  // Size
  if (theme.buttons.size === 'sm') {
    classes.push('h-8', 'px-3', 'text-sf-sm');
  } else if (theme.buttons.size === 'lg') {
    classes.push('h-12', 'px-6', 'text-sf-lg');
  } else {
    classes.push('h-10', 'px-4', 'text-sf-base');
  }

  // Full width
  if (theme.buttons.fullWidth) {
    classes.push('w-full');
  }

  return classes.filter(Boolean).join(' ');
}

// Generate element wrapper classes (for spacing, visibility)
export function generateElementWrapperClasses(
  theme: ThemeSchema,
  elementStyle?: ElementStyleProperties
): string {
  const classes: string[] = ['sf-element'];

  // Spacing between elements
  const spacingMap: Record<string, string> = {
    none: 'mb-0',
    sm: 'mb-sf-sm',
    md: 'mb-sf-md',
    lg: 'mb-sf-lg',
    xl: 'mb-sf-xl',
  };
  classes.push(spacingMap[theme.form.elementSpacing] || 'mb-sf-md');

  // Custom margin if specified
  if (elementStyle?.marginTop) {
    classes.push(`mt-[${elementStyle.marginTop}]`);
  }
  if (elementStyle?.marginBottom) {
    // Override default spacing
    classes.splice(classes.findIndex(c => c.startsWith('mb-')), 1);
    classes.push(`mb-[${elementStyle.marginBottom}]`);
  }

  return classes.filter(Boolean).join(' ');
}

// Generate column layout classes
export function generateColumnClasses(layout: string[]): string[] {
  // Map column ratios to Tailwind grid classes
  const layoutMap: Record<string, string[]> = {
    '1/2,1/2': ['w-1/2', 'w-1/2'],
    '1/3,2/3': ['w-1/3', 'w-2/3'],
    '2/3,1/3': ['w-2/3', 'w-1/3'],
    '1/3,1/3,1/3': ['w-1/3', 'w-1/3', 'w-1/3'],
    '1/4,3/4': ['w-1/4', 'w-3/4'],
    '3/4,1/4': ['w-3/4', 'w-1/4'],
    '1/4,1/2,1/4': ['w-1/4', 'w-1/2', 'w-1/4'],
    '1/4,1/4,1/4,1/4': ['w-1/4', 'w-1/4', 'w-1/4', 'w-1/4'],
  };

  const key = layout.join(',');
  return layoutMap[key] || layout.map(() => 'w-full');
}

// Generate error state classes
export function generateErrorClasses(): string {
  return 'border-sf-error text-sf-error focus:ring-sf-error focus:border-sf-error';
}

// Generate success state classes
export function generateSuccessClasses(): string {
  return 'border-sf-success text-sf-success';
}
```

### 4. Theme Context Provider (`/src/react/admin/contexts/ThemeContext.tsx`)

```typescript
import React, { createContext, useContext, useMemo } from 'react';
import { ThemeSchema } from '@/schemas';
import {
  generateFormWrapperClasses,
  generateInputClasses,
  generateLabelClasses,
  generateButtonClasses,
  generateElementWrapperClasses,
} from '@/schemas/utils/class-generator';

interface ThemeContextValue {
  theme: ThemeSchema;
  classes: {
    formWrapper: string;
    input: string;
    label: string;
    primaryButton: string;
    secondaryButton: string;
    outlineButton: string;
    elementWrapper: string;
  };
  cssVariables: Record<string, string>;
}

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function ThemeProvider({
  theme,
  children,
}: {
  theme: ThemeSchema;
  children: React.ReactNode;
}) {
  const value = useMemo(() => {
    // Generate CSS variables from theme
    const cssVariables: Record<string, string> = {
      '--sf-primary': theme.colors.primary,
      '--sf-primary-hover': theme.colors.primaryHover || theme.colors.primary,
      '--sf-primary-text': theme.colors.primaryText || '#ffffff',
      '--sf-background': theme.colors.background,
      '--sf-foreground': theme.colors.foreground,
      '--sf-muted': theme.colors.muted,
      '--sf-border': theme.colors.border,
      '--sf-error': theme.colors.error,
      '--sf-success': theme.colors.success,
      // Add more as needed
    };

    return {
      theme,
      classes: {
        formWrapper: generateFormWrapperClasses(theme),
        input: generateInputClasses(theme),
        label: generateLabelClasses(theme),
        primaryButton: generateButtonClasses(theme, 'primary'),
        secondaryButton: generateButtonClasses(theme, 'secondary'),
        outlineButton: generateButtonClasses(theme, 'outline'),
        elementWrapper: generateElementWrapperClasses(theme),
      },
      cssVariables,
    };
  }, [theme]);

  return (
    <ThemeContext.Provider value={value}>
      <div style={value.cssVariables as React.CSSProperties}>
        {children}
      </div>
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within ThemeProvider');
  }
  return context;
}
```

### 5. Form Element Renderer (`/src/react/admin/components/form-renderer/FormElement.tsx`)

```typescript
import { useTheme } from '@/contexts/ThemeContext';
import { cn } from '@/lib/utils';

interface FormElementProps {
  element: FormElementData;
  className?: string;
}

export function FormElement({ element, className }: FormElementProps) {
  const { classes } = useTheme();

  // Get element-specific style overrides
  const elementClasses = useMemo(() => {
    if (!element.styling) return {};

    return {
      wrapper: generateElementWrapperClasses(theme, element.styling),
      input: generateInputClasses(theme, element.styling),
    };
  }, [element.styling]);

  return (
    <div className={cn(classes.elementWrapper, elementClasses.wrapper, className)}>
      {element.label && (
        <label className={classes.label}>
          {element.label}
          {element.validation?.required && (
            <span className="text-sf-error ml-1">*</span>
          )}
        </label>
      )}

      <ElementInput
        element={element}
        className={cn(classes.input, elementClasses.input)}
      />

      {element.description && (
        <p className="mt-1 text-sf-sm text-sf-muted-foreground">
          {element.description}
        </p>
      )}

      {element.error && (
        <p className="mt-1 text-sf-sm text-sf-error">
          {element.error}
        </p>
      )}
    </div>
  );
}
```

## Custom CSS Override System

For user-defined custom styles, use inline styles sparingly:

```typescript
interface ElementStyling {
  // Tailwind classes are used by default
  // Only these custom properties become inline styles:
  customCss?: string;  // Raw CSS (advanced users)
  customClasses?: string;  // Additional Tailwind classes
}

function applyCustomStyles(element: FormElementData) {
  const style: React.CSSProperties = {};

  // Only apply inline styles for truly custom values
  if (element.styling?.customCss) {
    // Parse and apply custom CSS
    // This is the ONLY place inline styles are used
  }

  return style;
}
```

## File Structure

```
/src/react/admin/
├── styles/
│   └── sf-variables.css
├── contexts/
│   └── ThemeContext.tsx
├── components/
│   └── form-renderer/
│       └── FormElement.tsx
└── tailwind.sf.config.js

/src/schemas/
└── utils/
    └── class-generator.ts
```

## Frontend Output

The frontend form output uses the same classes:

```html
<form class="sf-form bg-sf-background p-sf-md rounded-sf-md shadow-sf-sm max-w-lg">
  <div class="sf-element mb-sf-md">
    <label class="sf-label block text-sf-foreground font-medium mb-1 text-sf-base">
      Email Address
      <span class="text-sf-error ml-1">*</span>
    </label>
    <input
      type="email"
      class="sf-input w-full border border-sf-border bg-sf-background text-sf-foreground rounded-sf-md px-3 py-2 h-10 focus:ring-2 focus:ring-sf-primary"
    />
  </div>
  <button class="sf-button bg-sf-primary text-sf-primary-text h-10 px-4 rounded-sf-md hover:bg-sf-primary-hover">
    Submit
  </button>
</form>

<style>
  /* CSS Variables injected from theme */
  .sf-form {
    --sf-primary: #3b82f6;
    --sf-background: #ffffff;
    /* ... */
  }
</style>
```

## Acceptance Criteria

- [ ] CSS variables defined for all theme properties
- [ ] Tailwind config extends with sf- prefixed utilities
- [ ] Class generator functions work for all element types
- [ ] ThemeProvider injects CSS variables
- [ ] Form elements render with Tailwind classes (no inline styles)
- [ ] Custom CSS overrides work for advanced users
- [ ] Builder preview matches frontend output (WYSIWYG)
- [ ] Column layouts use Tailwind grid/flex classes
- [ ] Error/success states have appropriate classes

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Section 7)
- **Theme Schema**: `/src/schemas/settings-theme.schema.ts`
- **Output**: `/src/react/admin/styles/sf-variables.css`, `/src/schemas/utils/class-generator.ts`

### Reference
- Current Tailwind config: `src/react/admin/tailwind.config.js`
- shadcn/ui styles: `src/react/admin/src/index.css`

## Work Log
- [2025-12-03] Task created
