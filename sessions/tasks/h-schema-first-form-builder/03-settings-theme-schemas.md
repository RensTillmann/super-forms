---
name: 03-settings-theme-schemas
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 3: Settings & Theme Schemas

## Goal

Create schemas for form settings and theme/styling with Tailwind CSS mappings.

## Dependencies

- Phase 1 (Schema Foundation) must be complete

## Deliverables

### 1. Form Settings Schema (`/src/schemas/settings/form.schema.ts`)

Categories:
- **general** - formTitle, formDescription
- **submission** - submitButtonText, successMessage, errorMessage, redirectMethod, redirectUrl
- **behavior** - enableAjax, saveEntry, hideAfterSubmit, clearAfterSubmit, scrollToMessage
- **spam** - honeypotEnabled, timeThreshold, duplicateDetection
- **access** - requireLogin, allowedRoles, loggedOutMessage

### 2. Theme Settings Schema (`/src/schemas/settings/theme.schema.ts`)

With Tailwind mappings for all style options:

```typescript
// Example Tailwind mapping
inputBorderRadius: {
  type: PropertyType.SELECT,
  label: 'Border Radius',
  options: [
    { value: 'none', label: 'None' },
    { value: 'sm', label: 'Small' },
    { value: 'md', label: 'Medium' },
    { value: 'lg', label: 'Large' },
  ],
  default: 'md',
  tailwind: {
    classes: {
      'none': 'sf-rounded-none',
      'sm': 'sf-rounded-sm',
      'md': 'sf-rounded-md',
      'lg': 'sf-rounded-lg',
    }
  }
}
```

Categories:
- **colors** - primaryColor, secondaryColor, backgroundColor, textColor, borderColor, errorColor, successColor
- **typography** - fontFamily, baseFontSize, labelFontWeight
- **layout** - formMaxWidth, fieldSpacing, formPadding
- **inputs** - inputHeight, inputBorderRadius, inputBorderWidth
- **buttons** - buttonStyle, buttonSize, buttonFullWidth, buttonBorderRadius
- **formWrapper** - wrapperBackground, wrapperBorder, wrapperBorderRadius, wrapperShadow

### 3. CSS Variables Schema (`/src/schemas/settings/css-variables.ts`)

Default CSS variables for theme:
```typescript
export const defaultCssVariables = {
  '--sf-primary': '#3b82f6',
  '--sf-primary-hover': '#2563eb',
  '--sf-secondary': '#64748b',
  '--sf-success': '#22c55e',
  '--sf-error': '#ef4444',
  '--sf-warning': '#f59e0b',
  '--sf-background': '#ffffff',
  '--sf-foreground': '#0f172a',
  '--sf-muted': '#f1f5f9',
  '--sf-border': '#e2e8f0',
  '--sf-input-bg': '#ffffff',
  '--sf-input-border': '#cbd5e1',
  '--sf-input-focus': '#3b82f6',
  '--sf-radius': '0.375rem',
};
```

### 4. Settings Index (`/src/schemas/settings/index.ts`)

Aggregated exports for all settings schemas.

## File Structure

```
/src/schemas/settings/
├── form.schema.ts           # Form settings
├── theme.schema.ts          # Theme with Tailwind
├── css-variables.ts         # Default CSS vars
└── index.ts                 # Exports
```

## Tailwind Integration

All style properties must include `tailwind` mapping:

```typescript
interface TailwindMapping {
  // Value → class mapping
  classes?: Record<string, string>;

  // CSS variable for custom values
  variable?: string;

  // Class prefix (if needed)
  prefix?: string;
}
```

### Tailwind Class Prefix

All Super Forms classes use `sf-` prefix to avoid conflicts:
- `sf-rounded-md` not `rounded-md`
- `sf-text-primary` not `text-primary`
- `sf-shadow-lg` not `shadow-lg`

### CSS Variables for Custom Values

When users select "custom" or enter a color picker value:
```typescript
primaryColor: {
  type: PropertyType.COLOR,
  label: 'Primary Color',
  default: '#3b82f6',
  variable: '--sf-primary',  // Applied as CSS variable
}
```

## Schema Details

### Form Settings

```typescript
export const formSettingsSchema = {
  general: {
    formTitle: {
      type: PropertyType.STRING,
      label: 'Form Title',
    },
    formDescription: {
      type: PropertyType.TEXTAREA,
      label: 'Form Description',
    },
  },

  submission: {
    submitButtonText: {
      type: PropertyType.STRING,
      label: 'Submit Button Text',
      default: 'Submit',
      translatable: true,
    },
    // ... see spec for full list
  },

  behavior: {
    enableAjax: {
      type: PropertyType.BOOLEAN,
      label: 'AJAX Submit',
      default: true,
    },
    // ...
  },

  spam: {
    honeypotEnabled: {
      type: PropertyType.BOOLEAN,
      label: 'Honeypot Field',
      default: true,
    },
    // ...
  },

  access: {
    requireLogin: {
      type: PropertyType.BOOLEAN,
      label: 'Require Login',
      default: false,
    },
    // ...
  },
};
```

### Theme Settings (Example Section)

```typescript
export const themeSettingsSchema = {
  colors: {
    primaryColor: {
      type: PropertyType.COLOR,
      label: 'Primary Color',
      default: '#3b82f6',
      variable: '--sf-primary',
    },
    // ...
  },

  typography: {
    fontFamily: {
      type: PropertyType.SELECT,
      label: 'Font Family',
      options: [
        { value: 'system', label: 'System Default' },
        { value: 'inter', label: 'Inter' },
        { value: 'roboto', label: 'Roboto' },
        // ...
      ],
      default: 'system',
      tailwind: {
        classes: {
          'system': 'sf-font-sans',
          'inter': 'sf-font-inter',
          'roboto': 'sf-font-roboto',
        }
      }
    },
    // ...
  },

  // ... other categories
};
```

## Acceptance Criteria

- [ ] Form settings schema covers all categories
- [ ] Theme settings schema covers all style options
- [ ] All style properties have Tailwind mappings
- [ ] CSS variables match theme colors
- [ ] `sf-` prefix used for all Tailwind classes
- [ ] Translatable properties marked correctly
- [ ] Default values are sensible
- [ ] TypeScript compiles without errors

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Sections 6-7)
- **Foundation**: `/src/schemas/types.ts`
- **Output**: `/src/schemas/settings/`

### Reference
- Current settings: Look for existing settings handling in codebase
- Email builder theme: `src/react/admin/pages/form-builder/emails/types/email.ts`

## Work Log
- [2025-12-03] Task created
