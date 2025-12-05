---
name: 02-element-schemas
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 2: Element Schemas (Zod-Based)

## Goal

Create complete Zod-validated schema definitions for all 30+ form element types using the foundation from Phase 1.

## Dependencies

- Phase 1 (Schema Foundation) must be complete

## Element Definition Pattern

Every element uses `registerElement()` which validates the schema at import time:

```typescript
// /src/schemas/elements/basic/text.schema.ts

import { z } from 'zod';
import { registerElement, withBaseProperties } from '../../foundation/registry';
import { PropertySchemaSchema } from '../../foundation/types.schema';

/**
 * Text Input Element Schema
 *
 * Registers on import - invalid schema = immediate error (fail fast)
 */
export const TextElementSchema = registerElement({
  type: 'text',
  name: 'Text Input',
  description: 'Single-line text input field',
  category: 'basic',
  icon: 'type',
  container: null,

  properties: withBaseProperties({
    general: {
      placeholder: PropertySchemaSchema.parse({
        type: 'string',
        label: 'Placeholder',
        description: 'Text shown when field is empty',
        translatable: true,
        supportsTags: true,
      }),
      defaultValue: PropertySchemaSchema.parse({
        type: 'string',
        label: 'Default Value',
        supportsTags: true,
      }),
      inputMask: PropertySchemaSchema.parse({
        type: 'string',
        label: 'Input Mask',
        description: 'Format pattern (e.g., (999) 999-9999)',
        placeholder: '(999) 999-9999',
      }),
    },
    validation: {
      required: PropertySchemaSchema.parse({
        type: 'boolean',
        label: 'Required',
        default: false,
        universal: true,
      }),
      minLength: PropertySchemaSchema.parse({
        type: 'number',
        label: 'Minimum Length',
        min: 0,
      }),
      maxLength: PropertySchemaSchema.parse({
        type: 'number',
        label: 'Maximum Length',
        min: 1,
      }),
      pattern: PropertySchemaSchema.parse({
        type: 'string',
        label: 'Pattern (Regex)',
        description: 'Regular expression for validation',
      }),
      validationMessage: PropertySchemaSchema.parse({
        type: 'string',
        label: 'Error Message',
        description: 'Custom message when validation fails',
        translatable: true,
      }),
    },
    styling: {
      inputSize: PropertySchemaSchema.parse({
        type: 'select',
        label: 'Size',
        options: [
          { value: 'sm', label: 'Small' },
          { value: 'md', label: 'Medium' },
          { value: 'lg', label: 'Large' },
        ],
        default: 'md',
        universal: true,
      }),
      prefixIcon: PropertySchemaSchema.parse({
        type: 'icon',
        label: 'Prefix Icon',
      }),
      suffixIcon: PropertySchemaSchema.parse({
        type: 'icon',
        label: 'Suffix Icon',
      }),
    },
  }),

  defaults: {
    name: '',
    label: 'Text Field',
    placeholder: '',
    required: false,
    width: 'full',
    inputSize: 'md',
  },

  translatable: ['label', 'placeholder', 'description', 'validationMessage'],
  supportsTags: ['placeholder', 'defaultValue'],
});
```

## Element Categories

### Basic Input Elements (8 elements)

| Type | File | Priority | Key Properties |
|------|------|----------|----------------|
| `text` | text.schema.ts | High | placeholder, inputMask, minLength, maxLength, pattern |
| `email` | email.schema.ts | High | placeholder, allowMultiple (inherits text validation) |
| `number` | number.schema.ts | High | min, max, step, prefix, suffix, format |
| `phone` | phone.schema.ts | Medium | defaultCountry, format, allowedCountries |
| `url` | url.schema.ts | Medium | allowedProtocols, placeholder |
| `password` | password.schema.ts | Medium | minLength, requireUppercase, requireNumber, showStrength |
| `hidden` | hidden.schema.ts | Low | defaultValue (no label, no validation display) |
| `textarea` | textarea.schema.ts | High | rows, minLength, maxLength, autoResize |

### Choice Elements (5 elements)

| Type | File | Priority | Key Properties |
|------|------|----------|----------------|
| `dropdown` | dropdown.schema.ts | High | items[], multiple, searchable, allowCustom |
| `radio` | radio.schema.ts | High | items[], layout (vertical/horizontal), allowOther |
| `checkbox` | checkbox.schema.ts | High | items[], minSelect, maxSelect, layout |
| `rating` | rating.schema.ts | Medium | maxRating, icon, allowHalf, labels[] |
| `quantity` | quantity.schema.ts | Low | min, max, step, defaultValue |

### Layout/Container Elements (6 elements)

| Type | File | Priority | Key Properties |
|------|------|----------|----------------|
| `columns` | columns.schema.ts | High | layout[], gap, stackOnMobile |
| `section` | section.schema.ts | Medium | title, collapsible, defaultOpen |
| `tabs` | tabs.schema.ts | High | tabs[], defaultTab, orientation |
| `accordion` | accordion.schema.ts | Medium | panels[], allowMultiple, defaultOpen[] |
| `steps` | steps.schema.ts | High | steps[], showProgress, allowJump |
| `repeater` | repeater.schema.ts | Medium | minRows, maxRows, addButtonText |

### Content/Display Elements (6 elements)

| Type | File | Priority | Key Properties |
|------|------|----------|----------------|
| `heading` | heading.schema.ts | High | text, level (h1-h6), alignment |
| `paragraph` | paragraph.schema.ts | Medium | text, alignment |
| `html` | html.schema.ts | Medium | content (raw HTML) |
| `divider` | divider.schema.ts | Low | style (solid/dashed/dotted), thickness |
| `spacer` | spacer.schema.ts | Low | height |
| `image` | image.schema.ts | Medium | src, alt, width, alignment |

### Advanced Elements (9 elements)

| Type | File | Priority | Key Properties |
|------|------|----------|----------------|
| `date` | date.schema.ts | High | format, minDate, maxDate, disabledDates |
| `time` | time.schema.ts | Medium | format (12/24), minTime, maxTime, step |
| `datetime` | datetime.schema.ts | Medium | dateFormat, timeFormat, timezone |
| `file` | file.schema.ts | High | allowedTypes[], maxSize, maxFiles, multiple |
| `signature` | signature.schema.ts | Low | width, height, strokeColor |
| `location` | location.schema.ts | Low | defaultCenter, zoom, markerDraggable |
| `address` | address.schema.ts | Low | components (street, city, etc.), country |
| `slider` | slider.schema.ts | Low | min, max, step, showValue, range |
| `calculator` | calculator.schema.ts | Low | formula, variables[], precision |

### Integration Elements (2 elements)

| Type | File | Priority | Key Properties |
|------|------|----------|----------------|
| `payment` | payment.schema.ts | Low | provider, amount, currency |
| `embed` | embed.schema.ts | Low | url, type (video/map/custom), aspectRatio |

## Container Schema Pattern

Container elements have a `container` config:

```typescript
// /src/schemas/elements/layout/columns.schema.ts

import { registerElement, withBaseProperties } from '../../foundation/registry';
import { PropertySchemaSchema } from '../../foundation/types.schema';

export const ColumnsElementSchema = registerElement({
  type: 'columns',
  name: 'Columns',
  description: 'Arrange elements in columns',
  category: 'layout',
  icon: 'columns',

  // CONTAINER CONFIG - defines nesting behavior
  container: {
    allowedChildren: '*',  // Any element type
    minChildren: 1,
    maxChildren: 12,
    childrenLabel: 'Columns',
    defaultChildren: 2,
  },

  properties: withBaseProperties({
    general: {
      layout: PropertySchemaSchema.parse({
        type: 'columns_config',
        label: 'Column Layout',
        description: 'Define column widths',
        default: ['1/2', '1/2'],
      }),
      gap: PropertySchemaSchema.parse({
        type: 'select',
        label: 'Gap',
        options: [
          { value: 'none', label: 'None' },
          { value: 'sm', label: 'Small' },
          { value: 'md', label: 'Medium' },
          { value: 'lg', label: 'Large' },
        ],
        default: 'md',
        universal: true,
      }),
      stackOnMobile: PropertySchemaSchema.parse({
        type: 'boolean',
        label: 'Stack on Mobile',
        default: true,
      }),
      reverseOnMobile: PropertySchemaSchema.parse({
        type: 'boolean',
        label: 'Reverse on Mobile',
        default: false,
        showWhen: { field: 'stackOnMobile', equals: true },
      }),
    },
    styling: {
      verticalAlign: PropertySchemaSchema.parse({
        type: 'select',
        label: 'Vertical Alignment',
        options: [
          { value: 'start', label: 'Top' },
          { value: 'center', label: 'Center' },
          { value: 'end', label: 'Bottom' },
          { value: 'stretch', label: 'Stretch' },
        ],
        default: 'start',
      }),
    },
  }),

  defaults: {
    layout: ['1/2', '1/2'],
    gap: 'md',
    stackOnMobile: true,
    reverseOnMobile: false,
    verticalAlign: 'start',
  },

  translatable: [],
  supportsTags: [],
});
```

## Choice Element Pattern (with items array)

```typescript
// /src/schemas/elements/choice/dropdown.schema.ts

import { registerElement, withBaseProperties } from '../../foundation/registry';
import { PropertySchemaSchema } from '../../foundation/types.schema';

export const DropdownElementSchema = registerElement({
  type: 'dropdown',
  name: 'Dropdown',
  description: 'Select from a list of options',
  category: 'choice',
  icon: 'chevron-down',
  container: null,

  properties: withBaseProperties({
    general: {
      placeholder: PropertySchemaSchema.parse({
        type: 'string',
        label: 'Placeholder',
        default: 'Select an option...',
        translatable: true,
      }),
      // Array of items with nested object schema
      items: PropertySchemaSchema.parse({
        type: 'array',
        label: 'Options',
        items: {
          type: 'object',
          properties: {
            value: { type: 'string', label: 'Value', required: true },
            label: { type: 'string', label: 'Label', required: true, translatable: true },
            icon: { type: 'icon', label: 'Icon' },
            disabled: { type: 'boolean', label: 'Disabled', default: false },
          },
        },
      }),
      defaultValue: PropertySchemaSchema.parse({
        type: 'string',
        label: 'Default Value',
        description: 'Must match an option value',
      }),
    },
    validation: {
      required: PropertySchemaSchema.parse({
        type: 'boolean',
        label: 'Required',
        default: false,
        universal: true,
      }),
    },
    styling: {
      searchable: PropertySchemaSchema.parse({
        type: 'boolean',
        label: 'Searchable',
        default: false,
      }),
      multiple: PropertySchemaSchema.parse({
        type: 'boolean',
        label: 'Allow Multiple',
        default: false,
      }),
      maxSelections: PropertySchemaSchema.parse({
        type: 'number',
        label: 'Max Selections',
        min: 1,
        showWhen: { field: 'multiple', equals: true },
      }),
    },
    advanced: {
      allowCustom: PropertySchemaSchema.parse({
        type: 'boolean',
        label: 'Allow Custom Value',
        description: 'Let users enter values not in the list',
        default: false,
      }),
      optionsSource: PropertySchemaSchema.parse({
        type: 'select',
        label: 'Options Source',
        options: [
          { value: 'static', label: 'Static List' },
          { value: 'posts', label: 'WordPress Posts' },
          { value: 'taxonomy', label: 'Taxonomy Terms' },
          { value: 'users', label: 'WordPress Users' },
          { value: 'api', label: 'External API' },
        ],
        default: 'static',
      }),
    },
  }),

  defaults: {
    name: '',
    label: 'Dropdown',
    placeholder: 'Select an option...',
    items: [],
    required: false,
    searchable: false,
    multiple: false,
    allowCustom: false,
    optionsSource: 'static',
  },

  translatable: ['label', 'placeholder', 'description', 'items[].label'],
  supportsTags: ['defaultValue'],
});
```

## File Structure

```
/src/schemas/elements/
├── index.ts                 # Imports all & re-exports registry
│
├── basic/
│   ├── text.schema.ts       # registerElement() call
│   ├── email.schema.ts
│   ├── number.schema.ts
│   ├── phone.schema.ts
│   ├── url.schema.ts
│   ├── password.schema.ts
│   ├── hidden.schema.ts
│   └── textarea.schema.ts
│
├── choice/
│   ├── dropdown.schema.ts
│   ├── radio.schema.ts
│   ├── checkbox.schema.ts
│   ├── rating.schema.ts
│   └── quantity.schema.ts
│
├── layout/
│   ├── columns.schema.ts
│   ├── section.schema.ts
│   ├── tabs.schema.ts
│   ├── accordion.schema.ts
│   ├── steps.schema.ts
│   └── repeater.schema.ts
│
├── content/
│   ├── heading.schema.ts
│   ├── paragraph.schema.ts
│   ├── html.schema.ts
│   ├── divider.schema.ts
│   ├── spacer.schema.ts
│   └── image.schema.ts
│
├── advanced/
│   ├── date.schema.ts
│   ├── time.schema.ts
│   ├── datetime.schema.ts
│   ├── file.schema.ts
│   ├── signature.schema.ts
│   ├── location.schema.ts
│   ├── address.schema.ts
│   ├── slider.schema.ts
│   └── calculator.schema.ts
│
└── integration/
    ├── payment.schema.ts
    └── embed.schema.ts
```

## Index File (Side-Effect Imports)

```typescript
// /src/schemas/elements/index.ts

/**
 * Element Schema Registry
 *
 * IMPORTANT: Import order doesn't matter - each file registers itself.
 * All schemas are validated at import time (fail fast).
 */

// Basic elements
import './basic/text.schema';
import './basic/email.schema';
import './basic/number.schema';
import './basic/phone.schema';
import './basic/url.schema';
import './basic/password.schema';
import './basic/hidden.schema';
import './basic/textarea.schema';

// Choice elements
import './choice/dropdown.schema';
import './choice/radio.schema';
import './choice/checkbox.schema';
import './choice/rating.schema';
import './choice/quantity.schema';

// Layout elements
import './layout/columns.schema';
import './layout/section.schema';
import './layout/tabs.schema';
import './layout/accordion.schema';
import './layout/steps.schema';
import './layout/repeater.schema';

// Content elements
import './content/heading.schema';
import './content/paragraph.schema';
import './content/html.schema';
import './content/divider.schema';
import './content/spacer.schema';
import './content/image.schema';

// Advanced elements
import './advanced/date.schema';
import './advanced/time.schema';
import './advanced/datetime.schema';
import './advanced/file.schema';
import './advanced/signature.schema';
import './advanced/location.schema';
import './advanced/address.schema';
import './advanced/slider.schema';
import './advanced/calculator.schema';

// Integration elements
import './integration/payment.schema';
import './integration/embed.schema';

// Re-export registry functions for convenience
export { getElementSchema, getAllElementTypes, getElementsByCategory } from '../foundation/registry';
```

## Implementation Order

### Batch 1 (Core - High Priority)
1. text, email, textarea (basic inputs)
2. dropdown, radio, checkbox (choices)
3. columns, tabs, steps (containers)
4. heading, date, file (essentials)

### Batch 2 (Standard - Medium Priority)
5. number, phone, url, password (more inputs)
6. rating (choice)
7. section, accordion, repeater (containers)
8. paragraph, html, image (content)
9. time, datetime (date variants)

### Batch 3 (Extended - Low Priority)
10. hidden, quantity (remaining inputs)
11. divider, spacer (simple content)
12. signature, location, address, slider, calculator (advanced)
13. payment, embed (integrations)

## Enforcement: What Happens on Invalid Schema

```typescript
// This will FAIL at import time with clear error message:
export const BrokenSchema = registerElement({
  type: 'broken',
  name: 'Broken Element',
  // Missing required 'description' field
  category: 'basic',
  icon: 'x',
  container: null,
  properties: withBaseProperties({}),
  defaults: {},
  translatable: [],
  supportsTags: [],
});

// Error thrown:
// ZodError: [
//   {
//     "code": "invalid_type",
//     "expected": "string",
//     "received": "undefined",
//     "path": ["description"],
//     "message": "Required"
//   }
// ]
```

## Acceptance Criteria

- [ ] All 30+ elements have schema files
- [ ] Each schema uses `registerElement()` (validates on import)
- [ ] Each schema uses `withBaseProperties()` (inherits base props)
- [ ] Container elements have valid `container` config
- [ ] `translatable` arrays include all translatable properties
- [ ] `supportsTags` arrays include tag-supporting properties
- [ ] `defaults` provide sensible starting values
- [ ] All elements registered via index.ts imports
- [ ] TypeScript compiles without errors
- [ ] Invalid schema = build failure (enforced)

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Sections 4-5, 14)
- **Foundation**: `/src/schemas/foundation/`
- **Output**: `/src/schemas/elements/**/*.schema.ts`

### Reference
- Current V2 elements: `src/react/admin/apps/form-builder-v2/components/elements/`
- Email builder elements: `src/react/admin/pages/form-builder/emails/types/`

## Work Log
- [2025-12-03] Task created
- [2025-12-03] Updated to use Zod-based registerElement() pattern
