# Form Builder Schema Specification

Version: 1.0.0
Last Updated: 2025-12-03
Status: Draft

## Table of Contents

1. [Philosophy & Goals](#1-philosophy--goals)
2. [Schema Format](#2-schema-format)
3. [Property Types](#3-property-types)
4. [Element Schemas](#4-element-schemas)
5. [Container Elements](#5-container-elements)
6. [Form Settings Schema](#6-form-settings-schema)
7. [Theme Schema](#7-theme-schema)
8. [Automation Node Schemas](#8-automation-node-schemas)
9. [Conditional Logic Schema](#9-conditional-logic-schema)
10. [Translation Schema](#10-translation-schema)
11. [Operations](#11-operations)
12. [REST API Schema Endpoints](#12-rest-api-schema-endpoints)
13. [MCP Capability Model](#13-mcp-capability-model)
14. [Element Type Audit](#14-element-type-audit)

---

## 1. Philosophy & Goals

### Single Source of Truth

The schema is the **canonical definition** of all form builder capabilities. Everything derives from it:

```
                    ┌─────────────────────┐
                    │       SCHEMA        │
                    │   (TypeScript)      │
                    └──────────┬──────────┘
                               │
         ┌─────────────────────┼─────────────────────┐
         │                     │                     │
         ▼                     ▼                     ▼
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│  React Admin    │   │   PHP Backend   │   │   MCP Server    │
│                 │   │                 │   │                 │
│ • Property      │   │ • Validation    │   │ • Dynamic tool  │
│   panels        │   │ • Sanitization  │   │   definitions   │
│ • Tailwind      │   │ • REST schema   │   │ • Full parity   │
│   classes       │   │   endpoints     │   │   with user     │
└─────────────────┘   └─────────────────┘   └─────────────────┘
```

### Design Principles

1. **Schema-Driven UI**: Property panels are generated from schema, not hardcoded
2. **MCP Parity**: AI can do everything a user can do
3. **WYSIWYG**: Builder preview = frontend output
4. **Tailwind-First**: Styles via Tailwind classes, not inline styles
5. **Strongly Typed**: No `any` types, all properties validated
6. **Extensible**: Third-party elements follow same schema format

### File Location

```
/src/schemas/
├── types.ts                    # Core type definitions
├── property-types.ts           # Property type enum and helpers
├── elements/
│   ├── _base.schema.ts         # Shared properties
│   ├── text.schema.ts          # Text input
│   ├── email.schema.ts         # Email input
│   ├── dropdown.schema.ts      # Dropdown/select
│   ├── columns.schema.ts       # Column layout container
│   └── ...                     # All element types
├── settings/
│   ├── form.schema.ts          # Form settings
│   ├── theme.schema.ts         # Theme/styling
│   └── submission.schema.ts    # Submission behavior
├── automations/
│   ├── triggers.schema.ts      # Trigger nodes
│   ├── actions.schema.ts       # Action nodes
│   ├── conditions.schema.ts    # Condition nodes
│   └── control.schema.ts       # Control flow nodes
├── operations/
│   ├── clipboard.schema.ts     # Copy/paste operations
│   └── layout.schema.ts        # Layout operations
├── translations.schema.ts      # Translation structure
├── conditional-logic.schema.ts # Visibility rules
└── index.ts                    # Aggregated export
```

---

## 2. Schema Format

### Element Schema Structure

```typescript
// /src/schemas/types.ts

export interface ElementSchema {
  // Identity
  type: string;                          // Unique type identifier (e.g., 'text', 'dropdown')
  name: string;                          // Display name (e.g., 'Text Input')
  description: string;                   // Short description for palette

  // Classification
  category: ElementCategory;             // 'basic' | 'choice' | 'layout' | 'content' | 'advanced' | 'integration'
  icon: string;                          // Lucide icon name (e.g., 'type', 'mail', 'list')

  // Container behavior
  container: ContainerConfig | null;     // null = not a container

  // Properties organized by panel tab
  properties: {
    general: Record<string, PropertySchema>;
    validation?: Record<string, PropertySchema>;
    styling?: Record<string, PropertySchema>;
    advanced?: Record<string, PropertySchema>;
    conditional?: Record<string, PropertySchema>;
  };

  // Style properties (for theme/per-element styling)
  styleProperties: Record<string, StylePropertySchema>;

  // Default values
  defaults: Record<string, unknown>;

  // Which properties are translatable
  translatable: string[];

  // Which properties support dynamic tags like {field_name}
  supportsTags: string[];
}

export type ElementCategory =
  | 'basic'       // text, email, number, phone, url, password
  | 'choice'      // dropdown, radio, checkbox, rating
  | 'layout'      // columns, section, tabs, accordion, steps
  | 'content'     // heading, paragraph, html, divider, spacer, image
  | 'advanced'    // date, time, file, signature, location, calculator
  | 'integration' // payment, webhook, embed

export interface ContainerConfig {
  allowedChildren: string[] | '*';       // Element types allowed as children, '*' = all
  maxChildren?: number;                  // Maximum child count
  minChildren?: number;                  // Minimum child count
  childrenLabel?: string;                // Label for children (e.g., 'Columns', 'Tabs')
  defaultChildren?: number;              // Default child count on creation
}
```

### Property Schema Structure

```typescript
export interface PropertySchema {
  // Type system
  type: PropertyType;

  // Display
  label: string;
  description?: string;
  placeholder?: string;

  // Validation
  required?: boolean;
  default?: unknown;

  // Type-specific options
  options?: PropertyOption[];            // For select, radio, checkbox types
  min?: number;                          // For number type
  max?: number;                          // For number type
  step?: number;                         // For number type
  pattern?: string;                      // Regex for string validation

  // Complex type config
  items?: PropertySchema;                // For array type (schema of each item)
  properties?: Record<string, PropertySchema>;  // For object type

  // Behavior
  unique?: boolean;                      // Must be unique within form (e.g., field name)
  translatable?: boolean;                // Can be translated
  supportsTags?: boolean;                // Supports {field_name} tags

  // Conditional display
  showWhen?: ConditionExpression;        // Only show in UI when condition met

  // Tailwind mapping (for style properties)
  tailwind?: TailwindMapping;

  // Category for clipboard operations
  copyCategory?: 'general' | 'validation' | 'styling' | 'advanced' | 'conditional';

  // Universal vs type-specific (for clipboard compatibility)
  universal?: boolean;                   // Applies to all element types
}

export interface PropertyOption {
  value: string | number | boolean;
  label: string;
  description?: string;
  icon?: string;
}

export interface TailwindMapping {
  // Direct value → class mapping
  classes?: Record<string, string>;

  // CSS variable for custom values
  variable?: string;

  // Prefix for generated classes
  prefix?: string;
}
```

---

## 3. Property Types

### Property Type Enum

```typescript
export enum PropertyType {
  // Primitives
  STRING = 'string',
  NUMBER = 'number',
  BOOLEAN = 'boolean',

  // Selection
  SELECT = 'select',                     // Single select dropdown
  MULTI_SELECT = 'multi_select',         // Multiple select
  RADIO = 'radio',                       // Radio buttons
  CHECKBOX_GROUP = 'checkbox_group',     // Checkbox group

  // Specialized inputs
  COLOR = 'color',                       // Color picker
  ICON = 'icon',                         // Icon picker
  IMAGE = 'image',                       // Image upload/select
  DATE = 'date',                         // Date picker
  TIME = 'time',                         // Time picker
  DATETIME = 'datetime',                 // DateTime picker

  // Rich content
  TEXTAREA = 'textarea',                 // Multi-line text
  RICH_TEXT = 'rich_text',               // WYSIWYG editor
  CODE = 'code',                         // Code editor

  // Complex types
  ARRAY = 'array',                       // List of items (e.g., dropdown options)
  OBJECT = 'object',                     // Nested object
  KEY_VALUE = 'key_value',               // Key-value pairs

  // Super Forms specific
  CONDITIONAL_RULES = 'conditional_rules',  // Conditional logic builder
  FIELD_REFERENCE = 'field_reference',      // Reference to another field
  EMAIL_TEMPLATE = 'email_template',        // Email builder

  // Layout specific
  COLUMNS_CONFIG = 'columns_config',        // Column width ratios
  SPACING = 'spacing',                      // Margin/padding
  SIZE = 'size',                            // Width/height with units
}
```

### Property Type Examples

```typescript
// String with validation
{
  type: PropertyType.STRING,
  label: 'Field Name',
  required: true,
  unique: true,
  pattern: '^[a-z_][a-z0-9_]*$',
  description: 'Unique identifier (lowercase, underscores only)',
}

// Select with options
{
  type: PropertyType.SELECT,
  label: 'Width',
  options: [
    { value: 'auto', label: 'Auto' },
    { value: '25', label: '25%' },
    { value: '33', label: '33%' },
    { value: '50', label: '50%' },
    { value: '66', label: '66%' },
    { value: '75', label: '75%' },
    { value: '100', label: '100%' },
  ],
  default: '100',
  tailwind: {
    classes: {
      'auto': 'w-auto',
      '25': 'w-1/4',
      '33': 'w-1/3',
      '50': 'w-1/2',
      '66': 'w-2/3',
      '75': 'w-3/4',
      '100': 'w-full',
    }
  }
}

// Array of items (dropdown options)
{
  type: PropertyType.ARRAY,
  label: 'Options',
  items: {
    type: PropertyType.OBJECT,
    properties: {
      value: { type: PropertyType.STRING, label: 'Value', required: true },
      label: { type: PropertyType.STRING, label: 'Label', required: true, translatable: true },
      selected: { type: PropertyType.BOOLEAN, label: 'Pre-selected', default: false },
      disabled: { type: PropertyType.BOOLEAN, label: 'Disabled', default: false },
    }
  },
  default: [
    { value: 'option_1', label: 'Option 1', selected: false, disabled: false },
    { value: 'option_2', label: 'Option 2', selected: false, disabled: false },
  ]
}

// Conditional display
{
  type: PropertyType.STRING,
  label: 'Custom Pattern',
  showWhen: { field: 'validation', equals: 'regex' },
  description: 'Regular expression pattern',
}
```

---

## 4. Element Schemas

### Base Element Properties

All elements inherit these properties:

```typescript
// /src/schemas/elements/_base.schema.ts

export const baseElementProperties = {
  general: {
    name: {
      type: PropertyType.STRING,
      label: 'Field Name',
      required: true,
      unique: true,
      pattern: '^[a-z_][a-z0-9_]*$',
      description: 'Unique identifier for data storage',
      copyCategory: 'general',
      universal: true,
    },
    label: {
      type: PropertyType.STRING,
      label: 'Label',
      translatable: true,
      description: 'Label shown above the field',
      copyCategory: 'general',
      universal: true,
    },
    description: {
      type: PropertyType.STRING,
      label: 'Help Text',
      translatable: true,
      description: 'Additional help text below field',
      copyCategory: 'general',
      universal: true,
    },
  },

  styling: {
    width: {
      type: PropertyType.SELECT,
      label: 'Width',
      options: [
        { value: 'auto', label: 'Auto' },
        { value: '25', label: '25%' },
        { value: '33', label: '33%' },
        { value: '50', label: '50%' },
        { value: '66', label: '66%' },
        { value: '75', label: '75%' },
        { value: '100', label: '100%' },
      ],
      default: '100',
      tailwind: {
        classes: {
          'auto': 'sf-w-auto',
          '25': 'sf-w-1/4',
          '33': 'sf-w-1/3',
          '50': 'sf-w-1/2',
          '66': 'sf-w-2/3',
          '75': 'sf-w-3/4',
          '100': 'sf-w-full',
        }
      },
      copyCategory: 'styling',
      universal: true,
    },
    cssClass: {
      type: PropertyType.STRING,
      label: 'CSS Classes',
      description: 'Additional CSS classes',
      copyCategory: 'styling',
      universal: true,
    },
    hideLabel: {
      type: PropertyType.BOOLEAN,
      label: 'Hide Label',
      default: false,
      copyCategory: 'styling',
      universal: true,
    },
  },

  advanced: {
    excludeFromEntry: {
      type: PropertyType.BOOLEAN,
      label: 'Exclude from Entry',
      description: 'Do not save this field value',
      default: false,
      copyCategory: 'advanced',
      universal: true,
    },
    excludeFromEmail: {
      type: PropertyType.BOOLEAN,
      label: 'Exclude from Emails',
      description: 'Exclude from email notifications',
      default: false,
      copyCategory: 'advanced',
      universal: true,
    },
  },

  conditional: {
    conditionalLogic: {
      type: PropertyType.CONDITIONAL_RULES,
      label: 'Conditional Logic',
      description: 'Show/hide based on other field values',
      copyCategory: 'conditional',
      universal: true,
    },
  },
};
```

### Text Input Schema

```typescript
// /src/schemas/elements/text.schema.ts

import { defineElement, PropertyType } from '../types';
import { baseElementProperties } from './_base.schema';

export const textInputSchema = defineElement({
  type: 'text',
  name: 'Text Input',
  description: 'Single-line text input field',
  category: 'basic',
  icon: 'type',
  container: null,

  properties: {
    general: {
      ...baseElementProperties.general,
      placeholder: {
        type: PropertyType.STRING,
        label: 'Placeholder',
        translatable: true,
        copyCategory: 'general',
        universal: true,
      },
      defaultValue: {
        type: PropertyType.STRING,
        label: 'Default Value',
        supportsTags: true,
        copyCategory: 'general',
        universal: false,  // Type-specific
      },
    },

    validation: {
      required: {
        type: PropertyType.BOOLEAN,
        label: 'Required',
        default: false,
        copyCategory: 'validation',
        universal: true,
      },
      validation: {
        type: PropertyType.SELECT,
        label: 'Validation',
        options: [
          { value: 'none', label: 'None' },
          { value: 'email', label: 'Email format' },
          { value: 'phone', label: 'Phone number' },
          { value: 'numeric', label: 'Numbers only' },
          { value: 'alphanumeric', label: 'Letters and numbers' },
          { value: 'url', label: 'URL format' },
          { value: 'regex', label: 'Custom pattern' },
        ],
        default: 'none',
        copyCategory: 'validation',
        universal: false,
      },
      validationRegex: {
        type: PropertyType.STRING,
        label: 'Custom Pattern',
        showWhen: { field: 'validation', equals: 'regex' },
        copyCategory: 'validation',
        universal: false,
      },
      validationMessage: {
        type: PropertyType.STRING,
        label: 'Error Message',
        translatable: true,
        showWhen: { field: 'validation', notEquals: 'none' },
        copyCategory: 'validation',
        universal: true,
      },
      minLength: {
        type: PropertyType.NUMBER,
        label: 'Min Length',
        min: 0,
        copyCategory: 'validation',
        universal: false,
      },
      maxLength: {
        type: PropertyType.NUMBER,
        label: 'Max Length',
        min: 1,
        copyCategory: 'validation',
        universal: false,
      },
    },

    styling: {
      ...baseElementProperties.styling,
      icon: {
        type: PropertyType.ICON,
        label: 'Icon',
        copyCategory: 'styling',
        universal: true,
      },
      iconPosition: {
        type: PropertyType.SELECT,
        label: 'Icon Position',
        options: [
          { value: 'left', label: 'Left' },
          { value: 'right', label: 'Right' },
        ],
        default: 'left',
        showWhen: { field: 'icon', exists: true },
        copyCategory: 'styling',
        universal: true,
      },
    },

    advanced: baseElementProperties.advanced,
    conditional: baseElementProperties.conditional,
  },

  styleProperties: {
    backgroundColor: { type: 'color', variable: '--sf-input-bg' },
    borderColor: { type: 'color', variable: '--sf-input-border' },
    borderRadius: { type: 'size', variable: '--sf-input-radius' },
    fontSize: { type: 'size', variable: '--sf-input-font-size' },
    color: { type: 'color', variable: '--sf-input-color' },
    focusBorderColor: { type: 'color', variable: '--sf-input-focus-border' },
  },

  defaults: {
    name: 'text_field',
    label: 'Text Field',
    width: '100',
    required: false,
    validation: 'none',
  },

  translatable: ['label', 'placeholder', 'description', 'validationMessage'],
  supportsTags: ['defaultValue'],
});
```

### Dropdown Schema

```typescript
// /src/schemas/elements/dropdown.schema.ts

import { defineElement, PropertyType } from '../types';
import { baseElementProperties } from './_base.schema';

export const dropdownSchema = defineElement({
  type: 'dropdown',
  name: 'Dropdown',
  description: 'Select from a list of options',
  category: 'choice',
  icon: 'chevron-down',
  container: null,

  properties: {
    general: {
      ...baseElementProperties.general,
      placeholder: {
        type: PropertyType.STRING,
        label: 'Placeholder',
        default: 'Select an option...',
        translatable: true,
        copyCategory: 'general',
        universal: true,
      },
      items: {
        type: PropertyType.ARRAY,
        label: 'Options',
        items: {
          type: PropertyType.OBJECT,
          properties: {
            value: {
              type: PropertyType.STRING,
              label: 'Value',
              required: true,
            },
            label: {
              type: PropertyType.STRING,
              label: 'Label',
              required: true,
              translatable: true,
            },
            selected: {
              type: PropertyType.BOOLEAN,
              label: 'Pre-selected',
              default: false,
            },
            disabled: {
              type: PropertyType.BOOLEAN,
              label: 'Disabled',
              default: false,
            },
          }
        },
        copyCategory: 'general',
        universal: false,  // Type-specific
      },
      multiple: {
        type: PropertyType.BOOLEAN,
        label: 'Allow Multiple',
        default: false,
        copyCategory: 'general',
        universal: false,
      },
      searchable: {
        type: PropertyType.BOOLEAN,
        label: 'Searchable',
        default: false,
        copyCategory: 'general',
        universal: false,
      },
    },

    validation: {
      required: {
        type: PropertyType.BOOLEAN,
        label: 'Required',
        default: false,
        copyCategory: 'validation',
        universal: true,
      },
      minSelected: {
        type: PropertyType.NUMBER,
        label: 'Min Selections',
        min: 0,
        showWhen: { field: 'multiple', equals: true },
        copyCategory: 'validation',
        universal: false,
      },
      maxSelected: {
        type: PropertyType.NUMBER,
        label: 'Max Selections',
        min: 1,
        showWhen: { field: 'multiple', equals: true },
        copyCategory: 'validation',
        universal: false,
      },
    },

    styling: baseElementProperties.styling,
    advanced: baseElementProperties.advanced,
    conditional: baseElementProperties.conditional,
  },

  styleProperties: {
    backgroundColor: { type: 'color', variable: '--sf-select-bg' },
    borderColor: { type: 'color', variable: '--sf-select-border' },
    borderRadius: { type: 'size', variable: '--sf-select-radius' },
  },

  defaults: {
    name: 'dropdown_field',
    label: 'Dropdown',
    width: '100',
    required: false,
    multiple: false,
    searchable: false,
    items: [
      { value: 'option_1', label: 'Option 1', selected: false, disabled: false },
      { value: 'option_2', label: 'Option 2', selected: false, disabled: false },
      { value: 'option_3', label: 'Option 3', selected: false, disabled: false },
    ],
  },

  translatable: ['label', 'placeholder', 'description', 'items[].label'],
  supportsTags: [],
});
```

---

## 5. Container Elements

Container elements can hold other elements as children.

### Columns Schema

```typescript
// /src/schemas/elements/columns.schema.ts

import { defineElement, PropertyType } from '../types';

export const columnsSchema = defineElement({
  type: 'columns',
  name: 'Columns',
  description: 'Multi-column layout',
  category: 'layout',
  icon: 'columns',

  container: {
    allowedChildren: '*',                // All element types allowed
    minChildren: 2,
    maxChildren: 6,
    childrenLabel: 'Columns',
    defaultChildren: 2,
  },

  properties: {
    general: {
      layout: {
        type: PropertyType.COLUMNS_CONFIG,
        label: 'Column Layout',
        description: 'Define column widths',
        default: ['1/2', '1/2'],
        options: [
          // Preset layouts
          { value: ['1/2', '1/2'], label: '1/2 + 1/2', icon: 'layout-columns-2' },
          { value: ['1/3', '1/3', '1/3'], label: '1/3 + 1/3 + 1/3', icon: 'layout-columns-3' },
          { value: ['1/4', '1/4', '1/4', '1/4'], label: '1/4 + 1/4 + 1/4 + 1/4', icon: 'layout-columns-4' },
          { value: ['1/3', '2/3'], label: '1/3 + 2/3', icon: 'layout-sidebar-left' },
          { value: ['2/3', '1/3'], label: '2/3 + 1/3', icon: 'layout-sidebar-right' },
          { value: ['1/4', '1/2', '1/4'], label: '1/4 + 1/2 + 1/4', icon: 'layout-centered' },
          { value: ['1/4', '3/4'], label: '1/4 + 3/4', icon: 'layout-sidebar-narrow' },
          { value: 'custom', label: 'Custom', icon: 'sliders' },
        ],
        tailwind: {
          // Maps fractions to Tailwind grid classes
          classes: {
            '1/2': 'sf-col-span-6',
            '1/3': 'sf-col-span-4',
            '2/3': 'sf-col-span-8',
            '1/4': 'sf-col-span-3',
            '3/4': 'sf-col-span-9',
            '1/6': 'sf-col-span-2',
            '5/6': 'sf-col-span-10',
          }
        },
        copyCategory: 'general',
        universal: false,
      },
      gap: {
        type: PropertyType.SELECT,
        label: 'Gap',
        options: [
          { value: 'none', label: 'None' },
          { value: 'sm', label: 'Small' },
          { value: 'md', label: 'Medium' },
          { value: 'lg', label: 'Large' },
          { value: 'xl', label: 'Extra Large' },
        ],
        default: 'md',
        tailwind: {
          classes: {
            'none': 'sf-gap-0',
            'sm': 'sf-gap-2',
            'md': 'sf-gap-4',
            'lg': 'sf-gap-6',
            'xl': 'sf-gap-8',
          }
        },
        copyCategory: 'styling',
        universal: false,
      },
      stackOnMobile: {
        type: PropertyType.BOOLEAN,
        label: 'Stack on Mobile',
        description: 'Stack columns vertically on small screens',
        default: true,
        copyCategory: 'styling',
        universal: false,
      },
      verticalAlign: {
        type: PropertyType.SELECT,
        label: 'Vertical Alignment',
        options: [
          { value: 'top', label: 'Top' },
          { value: 'center', label: 'Center' },
          { value: 'bottom', label: 'Bottom' },
          { value: 'stretch', label: 'Stretch' },
        ],
        default: 'top',
        tailwind: {
          classes: {
            'top': 'sf-items-start',
            'center': 'sf-items-center',
            'bottom': 'sf-items-end',
            'stretch': 'sf-items-stretch',
          }
        },
        copyCategory: 'styling',
        universal: false,
      },
    },

    styling: {
      cssClass: {
        type: PropertyType.STRING,
        label: 'CSS Classes',
        copyCategory: 'styling',
        universal: true,
      },
    },

    conditional: {
      conditionalLogic: {
        type: PropertyType.CONDITIONAL_RULES,
        label: 'Conditional Logic',
        copyCategory: 'conditional',
        universal: true,
      },
    },
  },

  styleProperties: {
    backgroundColor: { type: 'color', variable: '--sf-columns-bg' },
    padding: { type: 'spacing', variable: '--sf-columns-padding' },
    borderRadius: { type: 'size', variable: '--sf-columns-radius' },
  },

  defaults: {
    layout: ['1/2', '1/2'],
    gap: 'md',
    stackOnMobile: true,
    verticalAlign: 'top',
  },

  translatable: [],
  supportsTags: [],
});
```

### Tabs Schema

```typescript
// /src/schemas/elements/tabs.schema.ts

import { defineElement, PropertyType } from '../types';

export const tabsSchema = defineElement({
  type: 'tabs',
  name: 'Tabs',
  description: 'Tabbed content sections',
  category: 'layout',
  icon: 'panel-top',

  container: {
    allowedChildren: '*',
    minChildren: 2,
    maxChildren: 10,
    childrenLabel: 'Tabs',
    defaultChildren: 3,
  },

  properties: {
    general: {
      tabs: {
        type: PropertyType.ARRAY,
        label: 'Tab Labels',
        items: {
          type: PropertyType.OBJECT,
          properties: {
            label: {
              type: PropertyType.STRING,
              label: 'Label',
              required: true,
              translatable: true,
            },
            icon: {
              type: PropertyType.ICON,
              label: 'Icon',
            },
          }
        },
        copyCategory: 'general',
        universal: false,
      },
      defaultTab: {
        type: PropertyType.NUMBER,
        label: 'Default Tab',
        description: 'Which tab to show initially (0-indexed)',
        default: 0,
        min: 0,
        copyCategory: 'general',
        universal: false,
      },
    },

    styling: {
      tabPosition: {
        type: PropertyType.SELECT,
        label: 'Tab Position',
        options: [
          { value: 'top', label: 'Top' },
          { value: 'bottom', label: 'Bottom' },
          { value: 'left', label: 'Left' },
          { value: 'right', label: 'Right' },
        ],
        default: 'top',
        copyCategory: 'styling',
        universal: false,
      },
      tabStyle: {
        type: PropertyType.SELECT,
        label: 'Tab Style',
        options: [
          { value: 'underline', label: 'Underline' },
          { value: 'pills', label: 'Pills' },
          { value: 'boxed', label: 'Boxed' },
        ],
        default: 'underline',
        copyCategory: 'styling',
        universal: false,
      },
      fullWidth: {
        type: PropertyType.BOOLEAN,
        label: 'Full Width Tabs',
        default: false,
        copyCategory: 'styling',
        universal: false,
      },
      cssClass: {
        type: PropertyType.STRING,
        label: 'CSS Classes',
        copyCategory: 'styling',
        universal: true,
      },
    },

    conditional: {
      conditionalLogic: {
        type: PropertyType.CONDITIONAL_RULES,
        label: 'Conditional Logic',
        copyCategory: 'conditional',
        universal: true,
      },
    },
  },

  styleProperties: {
    tabBackgroundColor: { type: 'color', variable: '--sf-tabs-bg' },
    tabActiveColor: { type: 'color', variable: '--sf-tabs-active' },
    tabBorderColor: { type: 'color', variable: '--sf-tabs-border' },
    contentBackgroundColor: { type: 'color', variable: '--sf-tabs-content-bg' },
  },

  defaults: {
    tabs: [
      { label: 'Tab 1', icon: null },
      { label: 'Tab 2', icon: null },
      { label: 'Tab 3', icon: null },
    ],
    defaultTab: 0,
    tabPosition: 'top',
    tabStyle: 'underline',
    fullWidth: false,
  },

  translatable: ['tabs[].label'],
  supportsTags: [],
});
```

### Steps (Multi-Step Form) Schema

```typescript
// /src/schemas/elements/steps.schema.ts

import { defineElement, PropertyType } from '../types';

export const stepsSchema = defineElement({
  type: 'steps',
  name: 'Multi-Step',
  description: 'Multi-step form wizard',
  category: 'layout',
  icon: 'git-branch',

  container: {
    allowedChildren: '*',
    minChildren: 2,
    maxChildren: 20,
    childrenLabel: 'Steps',
    defaultChildren: 3,
  },

  properties: {
    general: {
      steps: {
        type: PropertyType.ARRAY,
        label: 'Step Labels',
        items: {
          type: PropertyType.OBJECT,
          properties: {
            label: {
              type: PropertyType.STRING,
              label: 'Label',
              required: true,
              translatable: true,
            },
            description: {
              type: PropertyType.STRING,
              label: 'Description',
              translatable: true,
            },
            icon: {
              type: PropertyType.ICON,
              label: 'Icon',
            },
          }
        },
        copyCategory: 'general',
        universal: false,
      },
      validateOnStep: {
        type: PropertyType.BOOLEAN,
        label: 'Validate Each Step',
        description: 'Validate fields before proceeding to next step',
        default: true,
        copyCategory: 'general',
        universal: false,
      },
      allowBackNavigation: {
        type: PropertyType.BOOLEAN,
        label: 'Allow Back Navigation',
        default: true,
        copyCategory: 'general',
        universal: false,
      },
    },

    styling: {
      progressStyle: {
        type: PropertyType.SELECT,
        label: 'Progress Style',
        options: [
          { value: 'numbered', label: 'Numbered' },
          { value: 'bullets', label: 'Bullets' },
          { value: 'progress-bar', label: 'Progress Bar' },
          { value: 'none', label: 'None' },
        ],
        default: 'numbered',
        copyCategory: 'styling',
        universal: false,
      },
      progressPosition: {
        type: PropertyType.SELECT,
        label: 'Progress Position',
        options: [
          { value: 'top', label: 'Top' },
          { value: 'bottom', label: 'Bottom' },
          { value: 'left', label: 'Left' },
          { value: 'right', label: 'Right' },
        ],
        default: 'top',
        copyCategory: 'styling',
        universal: false,
      },
      nextButtonText: {
        type: PropertyType.STRING,
        label: 'Next Button Text',
        default: 'Next',
        translatable: true,
        copyCategory: 'styling',
        universal: false,
      },
      prevButtonText: {
        type: PropertyType.STRING,
        label: 'Previous Button Text',
        default: 'Previous',
        translatable: true,
        copyCategory: 'styling',
        universal: false,
      },
      cssClass: {
        type: PropertyType.STRING,
        label: 'CSS Classes',
        copyCategory: 'styling',
        universal: true,
      },
    },

    conditional: {
      conditionalLogic: {
        type: PropertyType.CONDITIONAL_RULES,
        label: 'Conditional Logic',
        copyCategory: 'conditional',
        universal: true,
      },
    },
  },

  styleProperties: {
    progressActiveColor: { type: 'color', variable: '--sf-steps-active' },
    progressInactiveColor: { type: 'color', variable: '--sf-steps-inactive' },
    progressCompletedColor: { type: 'color', variable: '--sf-steps-completed' },
  },

  defaults: {
    steps: [
      { label: 'Step 1', description: '', icon: null },
      { label: 'Step 2', description: '', icon: null },
      { label: 'Step 3', description: '', icon: null },
    ],
    validateOnStep: true,
    allowBackNavigation: true,
    progressStyle: 'numbered',
    progressPosition: 'top',
    nextButtonText: 'Next',
    prevButtonText: 'Previous',
  },

  translatable: ['steps[].label', 'steps[].description', 'nextButtonText', 'prevButtonText'],
  supportsTags: [],
});
```

---

## 6. Form Settings Schema

```typescript
// /src/schemas/settings/form.schema.ts

export const formSettingsSchema = {
  general: {
    formTitle: {
      type: PropertyType.STRING,
      label: 'Form Title',
      description: 'Internal name for this form',
    },
    formDescription: {
      type: PropertyType.TEXTAREA,
      label: 'Form Description',
      description: 'Internal description (not shown to users)',
    },
  },

  submission: {
    submitButtonText: {
      type: PropertyType.STRING,
      label: 'Submit Button Text',
      default: 'Submit',
      translatable: true,
    },
    submitButtonLoading: {
      type: PropertyType.STRING,
      label: 'Loading Text',
      default: 'Submitting...',
      translatable: true,
    },
    successMessage: {
      type: PropertyType.RICH_TEXT,
      label: 'Success Message',
      default: 'Thank you for your submission!',
      translatable: true,
    },
    errorMessage: {
      type: PropertyType.RICH_TEXT,
      label: 'Error Message',
      default: 'An error occurred. Please try again.',
      translatable: true,
    },
    redirectMethod: {
      type: PropertyType.SELECT,
      label: 'After Submission',
      options: [
        { value: 'message', label: 'Show message' },
        { value: 'url', label: 'Redirect to URL' },
        { value: 'page', label: 'Redirect to page' },
        { value: 'hide', label: 'Hide form' },
      ],
      default: 'message',
    },
    redirectUrl: {
      type: PropertyType.STRING,
      label: 'Redirect URL',
      showWhen: { field: 'redirectMethod', equals: 'url' },
      supportsTags: true,
    },
    redirectPage: {
      type: PropertyType.SELECT,
      label: 'Redirect Page',
      options: 'wp_pages',  // Dynamically populated
      showWhen: { field: 'redirectMethod', equals: 'page' },
    },
  },

  behavior: {
    enableAjax: {
      type: PropertyType.BOOLEAN,
      label: 'AJAX Submit',
      description: 'Submit without page reload',
      default: true,
    },
    saveEntry: {
      type: PropertyType.BOOLEAN,
      label: 'Save Entries',
      description: 'Save form submissions to database',
      default: true,
    },
    hideAfterSubmit: {
      type: PropertyType.BOOLEAN,
      label: 'Hide After Submit',
      default: false,
    },
    clearAfterSubmit: {
      type: PropertyType.BOOLEAN,
      label: 'Clear Form After Submit',
      default: true,
    },
    scrollToMessage: {
      type: PropertyType.BOOLEAN,
      label: 'Scroll to Message',
      description: 'Scroll to success/error message after submit',
      default: true,
    },
  },

  spam: {
    honeypotEnabled: {
      type: PropertyType.BOOLEAN,
      label: 'Honeypot Field',
      description: 'Add invisible field to catch bots',
      default: true,
    },
    timeThreshold: {
      type: PropertyType.NUMBER,
      label: 'Time Threshold',
      description: 'Minimum seconds before submit (0 = disabled)',
      default: 3,
      min: 0,
    },
    duplicateDetection: {
      type: PropertyType.SELECT,
      label: 'Duplicate Detection',
      options: [
        { value: 'none', label: 'None' },
        { value: 'email', label: 'By email field' },
        { value: 'ip', label: 'By IP address' },
        { value: 'hash', label: 'By content hash' },
      ],
      default: 'none',
    },
    duplicateMessage: {
      type: PropertyType.STRING,
      label: 'Duplicate Message',
      default: 'You have already submitted this form.',
      translatable: true,
      showWhen: { field: 'duplicateDetection', notEquals: 'none' },
    },
  },

  access: {
    requireLogin: {
      type: PropertyType.BOOLEAN,
      label: 'Require Login',
      default: false,
    },
    allowedRoles: {
      type: PropertyType.MULTI_SELECT,
      label: 'Allowed Roles',
      options: 'wp_roles',  // Dynamically populated
      showWhen: { field: 'requireLogin', equals: true },
    },
    loggedOutMessage: {
      type: PropertyType.RICH_TEXT,
      label: 'Login Required Message',
      default: 'Please log in to access this form.',
      translatable: true,
      showWhen: { field: 'requireLogin', equals: true },
    },
  },
};
```

---

## 7. Theme Schema

The theme schema defines form styling with Tailwind CSS mappings.

```typescript
// /src/schemas/settings/theme.schema.ts

export const themeSettingsSchema = {
  // CSS Variables that map to Tailwind
  cssVariables: {
    '--sf-primary': '#3b82f6',
    '--sf-primary-hover': '#2563eb',
    '--sf-secondary': '#64748b',
    '--sf-success': '#22c55e',
    '--sf-error': '#ef4444',
    '--sf-warning': '#f59e0b',
    '--sf-background': '#ffffff',
    '--sf-foreground': '#0f172a',
    '--sf-muted': '#f1f5f9',
    '--sf-muted-foreground': '#64748b',
    '--sf-border': '#e2e8f0',
    '--sf-input-bg': '#ffffff',
    '--sf-input-border': '#cbd5e1',
    '--sf-input-focus': '#3b82f6',
    '--sf-radius': '0.375rem',
    '--sf-radius-lg': '0.5rem',
  },

  colors: {
    primaryColor: {
      type: PropertyType.COLOR,
      label: 'Primary Color',
      default: '#3b82f6',
      variable: '--sf-primary',
      description: 'Buttons, links, focus states',
    },
    secondaryColor: {
      type: PropertyType.COLOR,
      label: 'Secondary Color',
      default: '#64748b',
      variable: '--sf-secondary',
    },
    backgroundColor: {
      type: PropertyType.COLOR,
      label: 'Form Background',
      default: '#ffffff',
      variable: '--sf-background',
    },
    textColor: {
      type: PropertyType.COLOR,
      label: 'Text Color',
      default: '#0f172a',
      variable: '--sf-foreground',
    },
    borderColor: {
      type: PropertyType.COLOR,
      label: 'Border Color',
      default: '#e2e8f0',
      variable: '--sf-border',
    },
    errorColor: {
      type: PropertyType.COLOR,
      label: 'Error Color',
      default: '#ef4444',
      variable: '--sf-error',
    },
    successColor: {
      type: PropertyType.COLOR,
      label: 'Success Color',
      default: '#22c55e',
      variable: '--sf-success',
    },
  },

  typography: {
    fontFamily: {
      type: PropertyType.SELECT,
      label: 'Font Family',
      options: [
        { value: 'system', label: 'System Default' },
        { value: 'inter', label: 'Inter' },
        { value: 'roboto', label: 'Roboto' },
        { value: 'open-sans', label: 'Open Sans' },
        { value: 'lato', label: 'Lato' },
        { value: 'custom', label: 'Custom' },
      ],
      default: 'system',
      tailwind: {
        classes: {
          'system': 'sf-font-sans',
          'inter': 'sf-font-inter',
          'roboto': 'sf-font-roboto',
          'open-sans': 'sf-font-opensans',
          'lato': 'sf-font-lato',
        }
      }
    },
    customFontFamily: {
      type: PropertyType.STRING,
      label: 'Custom Font',
      showWhen: { field: 'fontFamily', equals: 'custom' },
      variable: '--sf-font-family',
    },
    baseFontSize: {
      type: PropertyType.SELECT,
      label: 'Base Font Size',
      options: [
        { value: 'sm', label: 'Small (14px)' },
        { value: 'md', label: 'Medium (16px)' },
        { value: 'lg', label: 'Large (18px)' },
      ],
      default: 'md',
      tailwind: {
        classes: {
          'sm': 'sf-text-sm',
          'md': 'sf-text-base',
          'lg': 'sf-text-lg',
        }
      }
    },
    labelFontWeight: {
      type: PropertyType.SELECT,
      label: 'Label Weight',
      options: [
        { value: 'normal', label: 'Normal' },
        { value: 'medium', label: 'Medium' },
        { value: 'semibold', label: 'Semi-bold' },
        { value: 'bold', label: 'Bold' },
      ],
      default: 'medium',
      tailwind: {
        classes: {
          'normal': 'sf-font-normal',
          'medium': 'sf-font-medium',
          'semibold': 'sf-font-semibold',
          'bold': 'sf-font-bold',
        }
      }
    },
  },

  layout: {
    formMaxWidth: {
      type: PropertyType.SELECT,
      label: 'Max Width',
      options: [
        { value: 'sm', label: 'Small (480px)' },
        { value: 'md', label: 'Medium (640px)' },
        { value: 'lg', label: 'Large (768px)' },
        { value: 'xl', label: 'Extra Large (1024px)' },
        { value: 'full', label: 'Full Width' },
      ],
      default: 'lg',
      tailwind: {
        classes: {
          'sm': 'sf-max-w-sm',
          'md': 'sf-max-w-md',
          'lg': 'sf-max-w-lg',
          'xl': 'sf-max-w-xl',
          'full': 'sf-max-w-full',
        }
      }
    },
    fieldSpacing: {
      type: PropertyType.SELECT,
      label: 'Field Spacing',
      options: [
        { value: 'compact', label: 'Compact' },
        { value: 'normal', label: 'Normal' },
        { value: 'relaxed', label: 'Relaxed' },
      ],
      default: 'normal',
      tailwind: {
        classes: {
          'compact': 'sf-space-y-3',
          'normal': 'sf-space-y-5',
          'relaxed': 'sf-space-y-8',
        }
      }
    },
    formPadding: {
      type: PropertyType.SELECT,
      label: 'Form Padding',
      options: [
        { value: 'none', label: 'None' },
        { value: 'sm', label: 'Small' },
        { value: 'md', label: 'Medium' },
        { value: 'lg', label: 'Large' },
      ],
      default: 'md',
      tailwind: {
        classes: {
          'none': 'sf-p-0',
          'sm': 'sf-p-4',
          'md': 'sf-p-6',
          'lg': 'sf-p-8',
        }
      }
    },
  },

  inputs: {
    inputHeight: {
      type: PropertyType.SELECT,
      label: 'Input Height',
      options: [
        { value: 'sm', label: 'Small' },
        { value: 'md', label: 'Medium' },
        { value: 'lg', label: 'Large' },
      ],
      default: 'md',
      tailwind: {
        classes: {
          'sm': 'sf-h-8',
          'md': 'sf-h-10',
          'lg': 'sf-h-12',
        }
      }
    },
    inputBorderRadius: {
      type: PropertyType.SELECT,
      label: 'Border Radius',
      options: [
        { value: 'none', label: 'None' },
        { value: 'sm', label: 'Small' },
        { value: 'md', label: 'Medium' },
        { value: 'lg', label: 'Large' },
        { value: 'full', label: 'Full' },
      ],
      default: 'md',
      tailwind: {
        classes: {
          'none': 'sf-rounded-none',
          'sm': 'sf-rounded-sm',
          'md': 'sf-rounded-md',
          'lg': 'sf-rounded-lg',
          'full': 'sf-rounded-full',
        }
      }
    },
    inputBorderWidth: {
      type: PropertyType.SELECT,
      label: 'Border Width',
      options: [
        { value: '0', label: 'None' },
        { value: '1', label: '1px' },
        { value: '2', label: '2px' },
      ],
      default: '1',
      tailwind: {
        classes: {
          '0': 'sf-border-0',
          '1': 'sf-border',
          '2': 'sf-border-2',
        }
      }
    },
  },

  buttons: {
    buttonStyle: {
      type: PropertyType.SELECT,
      label: 'Button Style',
      options: [
        { value: 'filled', label: 'Filled' },
        { value: 'outline', label: 'Outline' },
        { value: 'ghost', label: 'Ghost' },
      ],
      default: 'filled',
    },
    buttonSize: {
      type: PropertyType.SELECT,
      label: 'Button Size',
      options: [
        { value: 'sm', label: 'Small' },
        { value: 'md', label: 'Medium' },
        { value: 'lg', label: 'Large' },
      ],
      default: 'md',
      tailwind: {
        classes: {
          'sm': 'sf-btn-sm',
          'md': 'sf-btn-md',
          'lg': 'sf-btn-lg',
        }
      }
    },
    buttonFullWidth: {
      type: PropertyType.BOOLEAN,
      label: 'Full Width',
      default: false,
    },
    buttonBorderRadius: {
      type: PropertyType.SELECT,
      label: 'Button Radius',
      options: [
        { value: 'none', label: 'None' },
        { value: 'sm', label: 'Small' },
        { value: 'md', label: 'Medium' },
        { value: 'lg', label: 'Large' },
        { value: 'full', label: 'Full' },
      ],
      default: 'md',
    },
  },

  formWrapper: {
    wrapperBackground: {
      type: PropertyType.COLOR,
      label: 'Wrapper Background',
      default: 'transparent',
      variable: '--sf-wrapper-bg',
    },
    wrapperBorder: {
      type: PropertyType.BOOLEAN,
      label: 'Show Border',
      default: false,
    },
    wrapperBorderColor: {
      type: PropertyType.COLOR,
      label: 'Border Color',
      default: '#e2e8f0',
      showWhen: { field: 'wrapperBorder', equals: true },
      variable: '--sf-wrapper-border',
    },
    wrapperBorderRadius: {
      type: PropertyType.SELECT,
      label: 'Border Radius',
      options: [
        { value: 'none', label: 'None' },
        { value: 'sm', label: 'Small' },
        { value: 'md', label: 'Medium' },
        { value: 'lg', label: 'Large' },
        { value: 'xl', label: 'Extra Large' },
      ],
      default: 'none',
      tailwind: {
        classes: {
          'none': 'sf-rounded-none',
          'sm': 'sf-rounded',
          'md': 'sf-rounded-lg',
          'lg': 'sf-rounded-xl',
          'xl': 'sf-rounded-2xl',
        }
      }
    },
    wrapperShadow: {
      type: PropertyType.SELECT,
      label: 'Shadow',
      options: [
        { value: 'none', label: 'None' },
        { value: 'sm', label: 'Small' },
        { value: 'md', label: 'Medium' },
        { value: 'lg', label: 'Large' },
        { value: 'xl', label: 'Extra Large' },
      ],
      default: 'none',
      tailwind: {
        classes: {
          'none': '',
          'sm': 'sf-shadow-sm',
          'md': 'sf-shadow-md',
          'lg': 'sf-shadow-lg',
          'xl': 'sf-shadow-xl',
        }
      }
    },
  },
};
```

---

## 8. Automation Node Schemas

### Trigger Nodes

```typescript
// /src/schemas/automations/triggers.schema.ts

export const triggerSchemas = {
  'form.submitted': {
    id: 'form.submitted',
    name: 'Form Submitted',
    description: 'Triggers when a form is successfully submitted',
    category: 'trigger',
    icon: 'send',

    config: {
      formId: {
        type: PropertyType.SELECT,
        label: 'Form',
        options: 'forms',  // Dynamically populated
        description: 'Which form triggers this automation',
      },
    },

    outputs: ['success'],

    availableContext: [
      'form_id',
      'entry_id',
      'form_data',      // All submitted field values
      'user_id',
      'user_email',
      'ip_address',
      'submission_time',
    ],
  },

  'entry.updated': {
    id: 'entry.updated',
    name: 'Entry Updated',
    description: 'Triggers when a form entry is updated',
    category: 'trigger',
    icon: 'edit',

    config: {
      formId: {
        type: PropertyType.SELECT,
        label: 'Form',
        options: 'forms',
      },
      fields: {
        type: PropertyType.MULTI_SELECT,
        label: 'Watch Fields',
        description: 'Only trigger when these fields change (empty = any field)',
        options: 'form_fields',  // Dynamic based on formId
      },
    },

    outputs: ['success'],

    availableContext: [
      'form_id',
      'entry_id',
      'old_data',
      'new_data',
      'changed_fields',
      'user_id',
    ],
  },

  'schedule': {
    id: 'schedule',
    name: 'Schedule',
    description: 'Triggers on a schedule (cron)',
    category: 'trigger',
    icon: 'clock',

    config: {
      scheduleType: {
        type: PropertyType.SELECT,
        label: 'Schedule Type',
        options: [
          { value: 'interval', label: 'Interval' },
          { value: 'daily', label: 'Daily' },
          { value: 'weekly', label: 'Weekly' },
          { value: 'monthly', label: 'Monthly' },
          { value: 'cron', label: 'Custom Cron' },
        ],
      },
      interval: {
        type: PropertyType.NUMBER,
        label: 'Interval (minutes)',
        min: 1,
        showWhen: { field: 'scheduleType', equals: 'interval' },
      },
      time: {
        type: PropertyType.TIME,
        label: 'Time',
        showWhen: { field: 'scheduleType', in: ['daily', 'weekly', 'monthly'] },
      },
      dayOfWeek: {
        type: PropertyType.SELECT,
        label: 'Day of Week',
        options: [
          { value: 0, label: 'Sunday' },
          { value: 1, label: 'Monday' },
          { value: 2, label: 'Tuesday' },
          { value: 3, label: 'Wednesday' },
          { value: 4, label: 'Thursday' },
          { value: 5, label: 'Friday' },
          { value: 6, label: 'Saturday' },
        ],
        showWhen: { field: 'scheduleType', equals: 'weekly' },
      },
      cronExpression: {
        type: PropertyType.STRING,
        label: 'Cron Expression',
        placeholder: '0 9 * * 1-5',
        showWhen: { field: 'scheduleType', equals: 'cron' },
      },
    },

    outputs: ['success'],
    availableContext: ['execution_time', 'schedule_id'],
  },

  'webhook': {
    id: 'webhook',
    name: 'Webhook Received',
    description: 'Triggers when webhook URL receives a request',
    category: 'trigger',
    icon: 'webhook',

    config: {
      // Webhook URL is auto-generated
      method: {
        type: PropertyType.MULTI_SELECT,
        label: 'Allowed Methods',
        options: [
          { value: 'POST', label: 'POST' },
          { value: 'GET', label: 'GET' },
          { value: 'PUT', label: 'PUT' },
        ],
        default: ['POST'],
      },
      authentication: {
        type: PropertyType.SELECT,
        label: 'Authentication',
        options: [
          { value: 'none', label: 'None' },
          { value: 'secret', label: 'Secret Header' },
          { value: 'basic', label: 'Basic Auth' },
        ],
      },
      secretHeader: {
        type: PropertyType.STRING,
        label: 'Secret Value',
        showWhen: { field: 'authentication', equals: 'secret' },
      },
    },

    outputs: ['success'],
    availableContext: ['request_body', 'request_headers', 'request_method', 'request_url'],
  },
};
```

### Action Nodes

```typescript
// /src/schemas/automations/actions.schema.ts

export const actionSchemas = {
  'send_email': {
    id: 'send_email',
    name: 'Send Email',
    description: 'Send an email notification',
    category: 'action',
    icon: 'mail',

    config: {
      to: {
        type: PropertyType.STRING,
        label: 'To',
        required: true,
        supportsTags: true,
        description: 'Recipient email(s). Use {email} for submitted email.',
      },
      subject: {
        type: PropertyType.STRING,
        label: 'Subject',
        required: true,
        supportsTags: true,
      },
      fromName: {
        type: PropertyType.STRING,
        label: 'From Name',
        supportsTags: true,
      },
      fromEmail: {
        type: PropertyType.STRING,
        label: 'From Email',
        supportsTags: true,
      },
      replyTo: {
        type: PropertyType.STRING,
        label: 'Reply To',
        supportsTags: true,
      },
      emailBody: {
        type: PropertyType.EMAIL_TEMPLATE,
        label: 'Email Body',
        description: 'Design email with visual builder',
      },
      attachments: {
        type: PropertyType.STRING,
        label: 'Attachments',
        description: 'Use {file_field_name} to attach uploaded files',
        supportsTags: true,
      },
    },

    inputs: ['trigger'],
    outputs: ['success', 'failure'],
  },

  'http_request': {
    id: 'http_request',
    name: 'HTTP Request',
    description: 'Make an HTTP request to external service',
    category: 'action',
    icon: 'globe',

    config: {
      method: {
        type: PropertyType.SELECT,
        label: 'Method',
        options: [
          { value: 'GET', label: 'GET' },
          { value: 'POST', label: 'POST' },
          { value: 'PUT', label: 'PUT' },
          { value: 'PATCH', label: 'PATCH' },
          { value: 'DELETE', label: 'DELETE' },
        ],
        default: 'POST',
      },
      url: {
        type: PropertyType.STRING,
        label: 'URL',
        required: true,
        supportsTags: true,
      },
      headers: {
        type: PropertyType.KEY_VALUE,
        label: 'Headers',
        supportsTags: true,
      },
      body: {
        type: PropertyType.CODE,
        label: 'Body',
        language: 'json',
        supportsTags: true,
        showWhen: { field: 'method', in: ['POST', 'PUT', 'PATCH'] },
      },
      timeout: {
        type: PropertyType.NUMBER,
        label: 'Timeout (seconds)',
        default: 30,
        min: 1,
        max: 120,
      },
    },

    inputs: ['trigger'],
    outputs: ['success', 'failure'],

    outputContext: ['response_body', 'response_status', 'response_headers'],
  },

  'create_post': {
    id: 'create_post',
    name: 'Create Post',
    description: 'Create a WordPress post/page/CPT',
    category: 'action',
    icon: 'file-plus',

    config: {
      postType: {
        type: PropertyType.SELECT,
        label: 'Post Type',
        options: 'wp_post_types',  // Dynamic
        default: 'post',
      },
      postTitle: {
        type: PropertyType.STRING,
        label: 'Title',
        required: true,
        supportsTags: true,
      },
      postContent: {
        type: PropertyType.RICH_TEXT,
        label: 'Content',
        supportsTags: true,
      },
      postStatus: {
        type: PropertyType.SELECT,
        label: 'Status',
        options: [
          { value: 'draft', label: 'Draft' },
          { value: 'publish', label: 'Published' },
          { value: 'pending', label: 'Pending Review' },
        ],
        default: 'draft',
      },
      postAuthor: {
        type: PropertyType.SELECT,
        label: 'Author',
        options: 'wp_users',  // Dynamic
      },
      customFields: {
        type: PropertyType.KEY_VALUE,
        label: 'Custom Fields',
        supportsTags: true,
      },
    },

    inputs: ['trigger'],
    outputs: ['success', 'failure'],

    outputContext: ['post_id', 'post_url'],
  },

  'set_variable': {
    id: 'set_variable',
    name: 'Set Variable',
    description: 'Store a value for use in later actions',
    category: 'control',
    icon: 'variable',

    config: {
      variableName: {
        type: PropertyType.STRING,
        label: 'Variable Name',
        required: true,
        pattern: '^[a-zA-Z_][a-zA-Z0-9_]*$',
      },
      value: {
        type: PropertyType.STRING,
        label: 'Value',
        supportsTags: true,
      },
    },

    inputs: ['trigger'],
    outputs: ['next'],
  },

  'delay': {
    id: 'delay',
    name: 'Delay',
    description: 'Wait before continuing',
    category: 'control',
    icon: 'timer',

    config: {
      delayType: {
        type: PropertyType.SELECT,
        label: 'Delay Type',
        options: [
          { value: 'duration', label: 'Duration' },
          { value: 'until', label: 'Until specific time' },
        ],
      },
      duration: {
        type: PropertyType.NUMBER,
        label: 'Duration',
        min: 1,
        showWhen: { field: 'delayType', equals: 'duration' },
      },
      durationUnit: {
        type: PropertyType.SELECT,
        label: 'Unit',
        options: [
          { value: 'seconds', label: 'Seconds' },
          { value: 'minutes', label: 'Minutes' },
          { value: 'hours', label: 'Hours' },
          { value: 'days', label: 'Days' },
        ],
        default: 'minutes',
        showWhen: { field: 'delayType', equals: 'duration' },
      },
      untilTime: {
        type: PropertyType.DATETIME,
        label: 'Until',
        showWhen: { field: 'delayType', equals: 'until' },
        supportsTags: true,
      },
    },

    inputs: ['trigger'],
    outputs: ['next'],
  },
};
```

### Condition Nodes

```typescript
// /src/schemas/automations/conditions.schema.ts

export const conditionSchemas = {
  'field_comparison': {
    id: 'field_comparison',
    name: 'Field Comparison',
    description: 'Branch based on field value',
    category: 'condition',
    icon: 'git-branch',

    config: {
      field: {
        type: PropertyType.FIELD_REFERENCE,
        label: 'Field',
        required: true,
      },
      operator: {
        type: PropertyType.SELECT,
        label: 'Operator',
        options: [
          { value: 'equals', label: 'Equals' },
          { value: 'not_equals', label: 'Not Equals' },
          { value: 'contains', label: 'Contains' },
          { value: 'not_contains', label: 'Does Not Contain' },
          { value: 'starts_with', label: 'Starts With' },
          { value: 'ends_with', label: 'Ends With' },
          { value: 'greater_than', label: 'Greater Than' },
          { value: 'less_than', label: 'Less Than' },
          { value: 'is_empty', label: 'Is Empty' },
          { value: 'is_not_empty', label: 'Is Not Empty' },
          { value: 'regex', label: 'Matches Regex' },
        ],
      },
      value: {
        type: PropertyType.STRING,
        label: 'Value',
        supportsTags: true,
        showWhen: { field: 'operator', notIn: ['is_empty', 'is_not_empty'] },
      },
    },

    inputs: ['trigger'],
    outputs: ['true', 'false'],
  },

  'user_role': {
    id: 'user_role',
    name: 'User Role',
    description: 'Branch based on user role',
    category: 'condition',
    icon: 'user-check',

    config: {
      roles: {
        type: PropertyType.MULTI_SELECT,
        label: 'Roles',
        options: 'wp_roles',
      },
      condition: {
        type: PropertyType.SELECT,
        label: 'Condition',
        options: [
          { value: 'has_any', label: 'Has any of these roles' },
          { value: 'has_all', label: 'Has all of these roles' },
          { value: 'has_none', label: 'Has none of these roles' },
        ],
        default: 'has_any',
      },
    },

    inputs: ['trigger'],
    outputs: ['true', 'false'],
  },

  'a_b_test': {
    id: 'a_b_test',
    name: 'A/B Test',
    description: 'Random split for testing',
    category: 'condition',
    icon: 'shuffle',

    config: {
      splits: {
        type: PropertyType.ARRAY,
        label: 'Splits',
        items: {
          type: PropertyType.OBJECT,
          properties: {
            name: { type: PropertyType.STRING, label: 'Name' },
            percentage: { type: PropertyType.NUMBER, label: 'Percentage', min: 0, max: 100 },
          }
        },
        default: [
          { name: 'A', percentage: 50 },
          { name: 'B', percentage: 50 },
        ],
      },
    },

    inputs: ['trigger'],
    outputs: 'dynamic',  // Based on splits
  },
};
```

---

## 9. Conditional Logic Schema

Defines how element visibility rules work.

```typescript
// /src/schemas/conditional-logic.schema.ts

export interface ConditionalRule {
  field: string;                    // Field name to check
  operator: ConditionalOperator;    // Comparison operator
  value: string | number | boolean; // Value to compare against
}

export interface ConditionalLogic {
  enabled: boolean;
  action: 'show' | 'hide';          // What to do when conditions match
  logic: 'and' | 'or';              // How to combine multiple rules
  rules: ConditionalRule[];
}

export type ConditionalOperator =
  | 'equals'
  | 'not_equals'
  | 'contains'
  | 'not_contains'
  | 'starts_with'
  | 'ends_with'
  | 'greater_than'
  | 'less_than'
  | 'greater_or_equal'
  | 'less_or_equal'
  | 'is_empty'
  | 'is_not_empty'
  | 'is_checked'        // For checkboxes
  | 'is_not_checked';

export const conditionalLogicSchema = {
  type: PropertyType.OBJECT,
  properties: {
    enabled: {
      type: PropertyType.BOOLEAN,
      label: 'Enable Conditional Logic',
      default: false,
    },
    action: {
      type: PropertyType.SELECT,
      label: 'Action',
      options: [
        { value: 'show', label: 'Show this element' },
        { value: 'hide', label: 'Hide this element' },
      ],
      default: 'show',
      showWhen: { field: 'enabled', equals: true },
    },
    logic: {
      type: PropertyType.SELECT,
      label: 'Logic',
      options: [
        { value: 'and', label: 'All conditions match (AND)' },
        { value: 'or', label: 'Any condition matches (OR)' },
      ],
      default: 'and',
      showWhen: { field: 'enabled', equals: true },
    },
    rules: {
      type: PropertyType.ARRAY,
      label: 'Rules',
      showWhen: { field: 'enabled', equals: true },
      items: {
        type: PropertyType.OBJECT,
        properties: {
          field: {
            type: PropertyType.FIELD_REFERENCE,
            label: 'Field',
            required: true,
          },
          operator: {
            type: PropertyType.SELECT,
            label: 'Operator',
            options: [
              { value: 'equals', label: 'Equals' },
              { value: 'not_equals', label: 'Not Equals' },
              { value: 'contains', label: 'Contains' },
              { value: 'not_contains', label: 'Does Not Contain' },
              { value: 'greater_than', label: 'Greater Than' },
              { value: 'less_than', label: 'Less Than' },
              { value: 'is_empty', label: 'Is Empty' },
              { value: 'is_not_empty', label: 'Is Not Empty' },
              { value: 'is_checked', label: 'Is Checked' },
              { value: 'is_not_checked', label: 'Is Not Checked' },
            ],
          },
          value: {
            type: PropertyType.STRING,
            label: 'Value',
            showWhen: {
              field: 'operator',
              notIn: ['is_empty', 'is_not_empty', 'is_checked', 'is_not_checked']
            },
          },
        }
      }
    },
  },
};
```

---

## 10. Translation Schema

```typescript
// /src/schemas/translations.schema.ts

export const translationsSchema = {
  // Supported languages (ISO 639-1 codes)
  supportedLanguages: [
    { code: 'en', name: 'English', native: 'English' },
    { code: 'es', name: 'Spanish', native: 'Español' },
    { code: 'fr', name: 'French', native: 'Français' },
    { code: 'de', name: 'German', native: 'Deutsch' },
    { code: 'nl', name: 'Dutch', native: 'Nederlands' },
    { code: 'it', name: 'Italian', native: 'Italiano' },
    { code: 'pt', name: 'Portuguese', native: 'Português' },
    { code: 'ja', name: 'Japanese', native: '日本語' },
    { code: 'zh', name: 'Chinese', native: '中文' },
    { code: 'ko', name: 'Korean', native: '한국어' },
    { code: 'ar', name: 'Arabic', native: 'العربية', rtl: true },
    { code: 'ru', name: 'Russian', native: 'Русский' },
    // ... more languages
  ],

  // Structure of translations data
  structure: {
    // Per-element translations
    elements: {
      // Element ID → property → translation
      '[element_id]': {
        label: 'Translated label',
        placeholder: 'Translated placeholder',
        description: 'Translated description',
        validationMessage: 'Translated error message',
        // For array items (dropdown, radio, checkbox)
        'items[0].label': 'Translated option 1',
        'items[1].label': 'Translated option 2',
      }
    },

    // Form-level translations
    form: {
      submitButtonText: 'Translated submit',
      successMessage: 'Translated success',
      errorMessage: 'Translated error',
      // Multi-step form
      nextButtonText: 'Translated next',
      prevButtonText: 'Translated previous',
    }
  },

  // Which properties are translatable (derived from element schemas)
  translatableProperties: [
    'label',
    'placeholder',
    'description',
    'validationMessage',
    'items[].label',
    'tabs[].label',
    'tabs[].description',
    'steps[].label',
    'steps[].description',
    'nextButtonText',
    'prevButtonText',
    'submitButtonText',
    'successMessage',
    'errorMessage',
    'loggedOutMessage',
    'duplicateMessage',
  ],
};

// Translation data structure
export interface FormTranslations {
  defaultLanguage: string;
  enabledLanguages: string[];
  translations: {
    [languageCode: string]: {
      elements: {
        [elementId: string]: {
          [property: string]: string;
        };
      };
      form: {
        [property: string]: string;
      };
    };
  };
}
```

---

## 11. Operations

### Settings Clipboard

```typescript
// /src/schemas/operations/clipboard.schema.ts

export interface ClipboardContents {
  sourceElementId: string;
  sourceElementType: string;
  copiedAt: Date;

  // What was copied
  mode: 'all' | 'category' | 'properties';
  category?: PropertyCategory;
  properties?: string[];

  // The actual values
  values: Record<string, unknown>;
}

export type PropertyCategory = 'general' | 'validation' | 'styling' | 'advanced' | 'conditional';

// Copy operation
export interface CopySettingsOperation {
  type: 'copy_settings';
  params: {
    sourceElementId: string;
    mode: 'all' | 'category' | 'properties';
    category?: PropertyCategory;
    properties?: string[];
  };
}

// Paste operation
export interface PasteSettingsOperation {
  type: 'paste_settings';
  params: {
    targetElementId: string;
    mode: 'all' | 'selected';
    properties?: string[];  // If mode is 'selected'
  };
}

// Clipboard service interface
export interface ClipboardService {
  // Copy settings from element
  copySettings(
    elementId: string,
    mode: 'all' | 'category' | 'properties',
    options?: { category?: PropertyCategory; properties?: string[] }
  ): ClipboardContents;

  // Get current clipboard
  getClipboard(): ClipboardContents | null;

  // Check paste compatibility
  canPaste(targetElementType: string): {
    compatible: boolean;
    compatibleProperties: string[];
    incompatibleProperties: string[];
  };

  // Paste settings to element
  pasteSettings(
    targetElementId: string,
    mode: 'all' | 'selected',
    selectedProperties?: string[]
  ): PasteResult;

  // Clear clipboard
  clear(): void;
}

export interface PasteResult {
  success: boolean;
  appliedProperties: string[];
  skippedProperties: { property: string; reason: string }[];
  operations: JsonPatchOperation[];  // For undo/redo
}
```

### Layout Operations

```typescript
// /src/schemas/operations/layout.schema.ts

// Create column layout from elements
export interface CreateColumnLayoutOperation {
  type: 'create_column_layout';
  params: {
    elementIds: string[];           // Elements to wrap in columns
    layout: string[] | number;      // ['1/2', '1/2'] or 2 for equal split
    position?: number;              // Where to insert (default: first element's position)
  };
}

// Update column ratios
export interface UpdateColumnLayoutOperation {
  type: 'update_column_layout';
  params: {
    columnElementId: string;
    layout: string[];               // New ratios e.g., ['1/3', '2/3']
  };
}

// Wrap elements in container
export interface WrapInContainerOperation {
  type: 'wrap_in_container';
  params: {
    elementIds: string[];
    containerType: 'columns' | 'section' | 'tabs' | 'accordion' | 'steps';
    config?: Record<string, unknown>;  // Container-specific config
  };
}

// Unwrap container (flatten children)
export interface UnwrapContainerOperation {
  type: 'unwrap_container';
  params: {
    containerId: string;
  };
}

// Move element (reorder or change parent)
export interface MoveElementOperation {
  type: 'move_element';
  params: {
    elementId: string;
    targetParentId?: string;        // null for root level
    targetIndex: number;            // Position within parent
  };
}

// Duplicate element
export interface DuplicateElementOperation {
  type: 'duplicate_element';
  params: {
    elementId: string;
    deep?: boolean;                 // Include children (default: true)
  };
}

// All layout operations
export type LayoutOperation =
  | CreateColumnLayoutOperation
  | UpdateColumnLayoutOperation
  | WrapInContainerOperation
  | UnwrapContainerOperation
  | MoveElementOperation
  | DuplicateElementOperation;

// Layout service interface
export interface LayoutService {
  createColumnLayout(
    elementIds: string[],
    layout: string[] | number
  ): { containerId: string; operations: JsonPatchOperation[] };

  updateColumnLayout(
    columnId: string,
    newLayout: string[]
  ): JsonPatchOperation[];

  wrapInContainer(
    elementIds: string[],
    containerType: string,
    config?: Record<string, unknown>
  ): { containerId: string; operations: JsonPatchOperation[] };

  unwrapContainer(
    containerId: string
  ): JsonPatchOperation[];

  moveElement(
    elementId: string,
    targetParentId: string | null,
    targetIndex: number
  ): JsonPatchOperation[];

  duplicateElement(
    elementId: string,
    deep?: boolean
  ): { newElementId: string; operations: JsonPatchOperation[] };
}
```

---

## 12. REST API Schema Endpoints

```typescript
// REST API endpoint specifications

/**
 * GET /wp-json/super-forms/v1/schema
 *
 * Returns the complete schema (all domains).
 *
 * Response:
 * {
 *   version: "1.0.0",
 *   elements: { [type]: ElementSchema },
 *   settings: FormSettingsSchema,
 *   theme: ThemeSettingsSchema,
 *   automations: {
 *     triggers: { [id]: TriggerSchema },
 *     actions: { [id]: ActionSchema },
 *     conditions: { [id]: ConditionSchema },
 *     control: { [id]: ControlSchema },
 *   },
 *   translations: TranslationsSchema,
 *   conditionalLogic: ConditionalLogicSchema,
 *   propertyTypes: PropertyTypeDefinitions,
 * }
 */

/**
 * GET /wp-json/super-forms/v1/schema/elements
 *
 * Returns all element schemas.
 *
 * Query params:
 * - category: Filter by category (basic, choice, layout, content, advanced, integration)
 * - type: Get single element type (e.g., ?type=dropdown)
 */

/**
 * GET /wp-json/super-forms/v1/schema/elements/{type}
 *
 * Returns schema for a single element type.
 */

/**
 * GET /wp-json/super-forms/v1/schema/settings
 *
 * Returns form settings schema.
 */

/**
 * GET /wp-json/super-forms/v1/schema/theme
 *
 * Returns theme/styling schema with Tailwind mappings.
 */

/**
 * GET /wp-json/super-forms/v1/schema/automations
 *
 * Returns all automation node schemas.
 *
 * Query params:
 * - category: Filter by category (trigger, action, condition, control)
 */

/**
 * GET /wp-json/super-forms/v1/schema/translations
 *
 * Returns translation structure schema.
 */

/**
 * GET /wp-json/super-forms/v1/capabilities
 *
 * Returns MCP capability manifest - what operations are available.
 *
 * Response:
 * {
 *   version: "1.0.0",
 *   operations: [
 *     {
 *       name: "add_element",
 *       description: "Add a form element",
 *       params: { ... },
 *       supportedElementTypes: ["text", "email", "dropdown", ...],
 *     },
 *     {
 *       name: "copy_settings",
 *       description: "Copy element settings to clipboard",
 *       params: { ... },
 *     },
 *     {
 *       name: "create_column_layout",
 *       description: "Create a column layout from elements",
 *       params: { ... },
 *     },
 *     // ... all operations
 *   ]
 * }
 */
```

### PHP Implementation Outline

```php
// /src/includes/class-schema-rest-controller.php

class SUPER_Schema_REST_Controller extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'super-forms/v1';
        $this->rest_base = 'schema';
    }

    public function register_routes() {
        // GET /schema - Full schema
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_full_schema'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // GET /schema/elements
        register_rest_route($this->namespace, '/' . $this->rest_base . '/elements', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_element_schemas'],
            'permission_callback' => '__return_true',
        ]);

        // GET /schema/elements/{type}
        register_rest_route($this->namespace, '/' . $this->rest_base . '/elements/(?P<type>[a-z-]+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_element_schema'],
            'permission_callback' => '__return_true',
        ]);

        // ... other endpoints

        // GET /capabilities
        register_rest_route($this->namespace, '/capabilities', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_capabilities'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_full_schema($request) {
        // Load schemas (cached with 5 min TTL)
        $schema = $this->load_schemas();
        return rest_ensure_response($schema);
    }

    public function get_capabilities($request) {
        return rest_ensure_response([
            'version' => '1.0.0',
            'operations' => $this->get_operation_definitions(),
        ]);
    }

    private function load_schemas() {
        // Try cache first
        $cached = wp_cache_get('super_forms_schema', 'super_forms');
        if ($cached !== false) {
            return $cached;
        }

        // Load from generated PHP file (built from TypeScript)
        $schema = require SUPER_PLUGIN_DIR . '/includes/schemas/generated-schema.php';

        // Cache for 5 minutes
        wp_cache_set('super_forms_schema', $schema, 'super_forms', 300);

        return $schema;
    }
}
```

---

## 13. MCP Capability Model

The MCP server dynamically generates tools from the schema.

```typescript
// /.mcp/server.ts

interface MCPTool {
  name: string;
  description: string;
  inputSchema: JsonSchema;
}

class SuperFormsMCPServer {
  private schema: FullSchema;
  private tools: MCPTool[];

  async initialize() {
    // Fetch schema from WordPress
    this.schema = await this.fetchSchema();

    // Generate tools from schema
    this.tools = this.generateTools();
  }

  private async fetchSchema(): Promise<FullSchema> {
    const response = await fetch(`${this.wpUrl}/wp-json/super-forms/v1/schema`);
    return response.json();
  }

  private generateTools(): MCPTool[] {
    return [
      // Element operations
      ...this.generateElementTools(),

      // Settings operations
      ...this.generateSettingsTools(),

      // Theme operations
      ...this.generateThemeTools(),

      // Automation operations
      ...this.generateAutomationTools(),

      // Layout operations
      ...this.generateLayoutTools(),

      // Clipboard operations
      ...this.generateClipboardTools(),

      // Translation operations
      ...this.generateTranslationTools(),

      // Version control operations
      ...this.generateVersionTools(),
    ];
  }

  private generateElementTools(): MCPTool[] {
    const tools: MCPTool[] = [];

    // Generic add_element tool with full schema
    tools.push({
      name: 'add_element',
      description: 'Add a form element. Supports all element types with full configuration.',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number', description: 'Form ID' },
          elementType: {
            type: 'string',
            enum: Object.keys(this.schema.elements),
            description: 'Element type to add',
          },
          position: { type: 'number', description: 'Position index (-1 for end)' },
          parentId: { type: 'string', description: 'Parent container ID (null for root)' },
          properties: {
            type: 'object',
            description: 'Element properties (varies by type, see schema)',
          },
        },
        required: ['formId', 'elementType'],
      },
    });

    // Generic update_element tool
    tools.push({
      name: 'update_element',
      description: 'Update any element property',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementId: { type: 'string' },
          property: { type: 'string', description: 'Property path (e.g., "label", "validation.required")' },
          value: { description: 'New value' },
        },
        required: ['formId', 'elementId', 'property', 'value'],
      },
    });

    // Remove element
    tools.push({
      name: 'remove_element',
      description: 'Remove an element from the form',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementId: { type: 'string' },
        },
        required: ['formId', 'elementId'],
      },
    });

    return tools;
  }

  private generateLayoutTools(): MCPTool[] {
    return [
      {
        name: 'create_column_layout',
        description: 'Create a column layout from existing elements',
        inputSchema: {
          type: 'object',
          properties: {
            formId: { type: 'number' },
            elementIds: {
              type: 'array',
              items: { type: 'string' },
              description: 'Elements to wrap in columns',
            },
            layout: {
              oneOf: [
                { type: 'number', description: 'Number of equal columns (2, 3, 4)' },
                {
                  type: 'array',
                  items: { type: 'string' },
                  description: 'Column widths (e.g., ["1/3", "2/3"])',
                },
              ],
            },
          },
          required: ['formId', 'elementIds', 'layout'],
        },
      },
      {
        name: 'update_column_layout',
        description: 'Change column ratios',
        inputSchema: {
          type: 'object',
          properties: {
            formId: { type: 'number' },
            columnId: { type: 'string' },
            layout: {
              type: 'array',
              items: { type: 'string' },
              description: 'New column widths',
            },
          },
          required: ['formId', 'columnId', 'layout'],
        },
      },
      {
        name: 'move_element',
        description: 'Move/reorder an element',
        inputSchema: {
          type: 'object',
          properties: {
            formId: { type: 'number' },
            elementId: { type: 'string' },
            targetParentId: { type: 'string', nullable: true },
            targetIndex: { type: 'number' },
          },
          required: ['formId', 'elementId', 'targetIndex'],
        },
      },
      {
        name: 'wrap_in_container',
        description: 'Wrap elements in a container (columns, tabs, steps, etc.)',
        inputSchema: {
          type: 'object',
          properties: {
            formId: { type: 'number' },
            elementIds: { type: 'array', items: { type: 'string' } },
            containerType: {
              type: 'string',
              enum: ['columns', 'section', 'tabs', 'accordion', 'steps'],
            },
            config: { type: 'object' },
          },
          required: ['formId', 'elementIds', 'containerType'],
        },
      },
      {
        name: 'duplicate_element',
        description: 'Duplicate an element (optionally with children)',
        inputSchema: {
          type: 'object',
          properties: {
            formId: { type: 'number' },
            elementId: { type: 'string' },
            deep: { type: 'boolean', default: true },
          },
          required: ['formId', 'elementId'],
        },
      },
    ];
  }

  private generateClipboardTools(): MCPTool[] {
    return [
      {
        name: 'copy_element_settings',
        description: 'Copy settings from one element (for pasting to others)',
        inputSchema: {
          type: 'object',
          properties: {
            formId: { type: 'number' },
            elementId: { type: 'string' },
            mode: {
              type: 'string',
              enum: ['all', 'category', 'properties'],
            },
            category: {
              type: 'string',
              enum: ['general', 'validation', 'styling', 'advanced', 'conditional'],
              description: 'Required if mode is "category"',
            },
            properties: {
              type: 'array',
              items: { type: 'string' },
              description: 'Required if mode is "properties"',
            },
          },
          required: ['formId', 'elementId', 'mode'],
        },
      },
      {
        name: 'paste_element_settings',
        description: 'Paste previously copied settings to an element',
        inputSchema: {
          type: 'object',
          properties: {
            formId: { type: 'number' },
            targetElementId: { type: 'string' },
            mode: {
              type: 'string',
              enum: ['all', 'selected'],
            },
            properties: {
              type: 'array',
              items: { type: 'string' },
              description: 'Required if mode is "selected"',
            },
          },
          required: ['formId', 'targetElementId', 'mode'],
        },
      },
    ];
  }

  // ... other tool generators
}
```

---

## 14. Element Type Audit

Complete list of element types to be supported:

### Basic Input Elements

| Type | Name | Category | Container | Notes |
|------|------|----------|-----------|-------|
| `text` | Text Input | basic | No | Single-line text |
| `email` | Email | basic | No | Email validation |
| `number` | Number | basic | No | Numeric input |
| `phone` | Phone | basic | No | Phone validation |
| `url` | URL | basic | No | URL validation |
| `password` | Password | basic | No | Hidden input |
| `hidden` | Hidden | basic | No | Hidden field |
| `textarea` | Textarea | basic | No | Multi-line text |

### Choice Elements

| Type | Name | Category | Container | Notes |
|------|------|----------|-----------|-------|
| `dropdown` | Dropdown | choice | No | Select single/multiple |
| `radio` | Radio Buttons | choice | No | Single choice |
| `checkbox` | Checkboxes | choice | No | Multiple choice |
| `rating` | Rating | choice | No | Star rating |
| `quantity` | Quantity | choice | No | +/- counter |

### Layout/Container Elements

| Type | Name | Category | Container | Notes |
|------|------|----------|-----------|-------|
| `columns` | Columns | layout | Yes | Multi-column layout |
| `section` | Section | layout | Yes | Grouping container |
| `tabs` | Tabs | layout | Yes | Tabbed content |
| `accordion` | Accordion | layout | Yes | Collapsible sections |
| `steps` | Multi-Step | layout | Yes | Form wizard |
| `repeater` | Repeater | layout | Yes | Dynamic rows |

### Content/Display Elements

| Type | Name | Category | Container | Notes |
|------|------|----------|-----------|-------|
| `heading` | Heading | content | No | H1-H6 text |
| `paragraph` | Paragraph | content | No | Rich text content |
| `html` | HTML Block | content | No | Custom HTML |
| `divider` | Divider | content | No | Horizontal line |
| `spacer` | Spacer | content | No | Vertical space |
| `image` | Image | content | No | Display image |

### Advanced Elements

| Type | Name | Category | Container | Notes |
|------|------|----------|-----------|-------|
| `date` | Date | advanced | No | Date picker |
| `time` | Time | advanced | No | Time picker |
| `datetime` | Date & Time | advanced | No | Combined picker |
| `file` | File Upload | advanced | No | File input |
| `signature` | Signature | advanced | No | Signature pad |
| `location` | Location | advanced | No | Google Maps |
| `address` | Address | advanced | No | Address autocomplete |
| `slider` | Slider | advanced | No | Range slider |
| `calculator` | Calculator | advanced | No | Computed field |

### Integration Elements

| Type | Name | Category | Container | Notes |
|------|------|----------|-----------|-------|
| `payment` | Payment | integration | No | Stripe/PayPal |
| `embed` | Embed | integration | No | iFrame embed |

### Property Categories for Clipboard

Each element property belongs to a category for the clipboard system:

| Category | Properties (examples) | Universal |
|----------|----------------------|-----------|
| `general` | name, label, placeholder, description, defaultValue, items | Mostly yes |
| `validation` | required, validation, minLength, maxLength, pattern | Mixed |
| `styling` | width, icon, iconPosition, cssClass, hideLabel | Mostly yes |
| `advanced` | excludeFromEntry, excludeFromEmail | Yes |
| `conditional` | conditionalLogic | Yes |

**Universal properties** can be copied between any element types.
**Type-specific properties** (like `items` for dropdown) only paste to compatible types.

---

## Appendix A: Tailwind CSS Class Prefixing

All Super Forms Tailwind classes use the `sf-` prefix to avoid conflicts:

```css
/* tailwind.config.js */
module.exports = {
  prefix: 'sf-',
  content: ['./src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: 'var(--sf-primary)',
        secondary: 'var(--sf-secondary)',
        // ... theme colors
      },
    },
  },
};
```

Generated classes:
- `sf-w-full` instead of `w-full`
- `sf-rounded-md` instead of `rounded-md`
- `sf-text-primary` instead of `text-primary`

---

## Appendix B: JSON Patch Integration

All operations generate JSON Patch (RFC 6902) operations for undo/redo:

```typescript
// Adding element generates:
{ op: 'add', path: '/elements/-', value: { ...elementData } }

// Updating property generates:
{ op: 'replace', path: '/elements/0/label', value: 'New Label' }

// Moving element generates:
{ op: 'move', from: '/elements/2', path: '/elements/0' }

// Removing element generates:
{ op: 'remove', path: '/elements/1' }

// Pasting settings generates multiple replace operations:
[
  { op: 'replace', path: '/elements/1/width', value: '50' },
  { op: 'replace', path: '/elements/1/required', value: true },
  { op: 'replace', path: '/elements/1/cssClass', value: 'custom-class' },
]
```

---

## Appendix C: Implementation Roadmap

1. **Phase 1: Schema Foundation**
   - Create TypeScript schema definitions
   - Build schema export system
   - Implement REST API endpoints

2. **Phase 2: React Integration**
   - Schema-driven property panels
   - Tailwind class generation
   - Clipboard UI

3. **Phase 3: MCP Integration**
   - Dynamic tool generation
   - Full capability parity
   - Schema caching

4. **Phase 4: PHP Integration**
   - Schema validation
   - Build step for PHP arrays
   - REST endpoint optimization
