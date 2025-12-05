---
name: 01-schema-foundation
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 1: Schema Foundation (Zod-Based)

## Goal

Create the core Zod-based schema infrastructure that enforces type safety at compile time, runtime, and across PHP/MCP boundaries.

## Why Zod?

Plain TypeScript interfaces only provide compile-time checking. Zod provides:
1. **Runtime validation** - Catch invalid data at API boundaries
2. **Type inference** - TypeScript types derived from schemas (single source)
3. **JSON Schema export** - Generate schemas for PHP validation and MCP tools
4. **Parse vs validate** - Transform and validate in one step

## Deliverables

### 1. Package Setup

Add Zod dependency:

```bash
cd src/react/admin
npm install zod zod-to-json-schema
```

### 2. Property Type Schema (`/src/schemas/foundation/property-types.schema.ts`)

```typescript
import { z } from 'zod';

/**
 * All 26 property types supported by the schema system.
 * Adding a new type here requires:
 * 1. Add to this enum
 * 2. Add renderer in PropertyRenderer (TypeScript enforces this)
 * 3. Add PHP handler in validation
 */
export const PropertyTypeSchema = z.enum([
  // Primitives
  'string',
  'number',
  'boolean',

  // Selection
  'select',
  'multi_select',
  'radio',
  'checkbox_group',

  // Specialized inputs
  'color',
  'icon',
  'image',
  'date',
  'time',
  'datetime',

  // Rich content
  'textarea',
  'rich_text',
  'code',

  // Complex/nested
  'array',
  'object',
  'key_value',

  // Super Forms specific
  'conditional_rules',
  'field_reference',
  'email_template',
  'columns_config',
  'spacing',
  'size',
]);

export type PropertyType = z.infer<typeof PropertyTypeSchema>;

// Utility: Get all property types as array
export const ALL_PROPERTY_TYPES = PropertyTypeSchema.options;
```

### 3. Core Schema Types (`/src/schemas/foundation/types.schema.ts`)

```typescript
import { z } from 'zod';
import { PropertyTypeSchema } from './property-types.schema';

// ============================================================
// PROPERTY OPTION
// ============================================================

export const PropertyOptionSchema = z.object({
  value: z.union([z.string(), z.number(), z.boolean()]),
  label: z.string(),
  icon: z.string().optional(),
  description: z.string().optional(),
  disabled: z.boolean().optional(),
});

export type PropertyOption = z.infer<typeof PropertyOptionSchema>;

// ============================================================
// SHOW WHEN CONDITION (Conditional Visibility)
// ============================================================

export const ShowWhenConditionSchema = z.object({
  field: z.string(),
  equals: z.unknown().optional(),
  notEquals: z.unknown().optional(),
  in: z.array(z.unknown()).optional(),
  notIn: z.array(z.unknown()).optional(),
  isEmpty: z.boolean().optional(),
  isNotEmpty: z.boolean().optional(),
});

export type ShowWhenCondition = z.infer<typeof ShowWhenConditionSchema>;

// ============================================================
// PROPERTY SCHEMA
// ============================================================

export const PropertySchemaSchema = z.object({
  // Core
  type: PropertyTypeSchema,
  label: z.string().optional(),
  description: z.string().optional(),

  // Validation
  required: z.boolean().default(false),
  default: z.unknown().optional(),

  // UI hints
  placeholder: z.string().optional(),
  helpText: z.string().optional(),

  // Type-specific constraints
  options: z.union([
    z.array(PropertyOptionSchema),
    z.string(), // Dynamic options key: 'forms', 'wp_roles', etc.
  ]).optional(),
  min: z.number().optional(),
  max: z.number().optional(),
  step: z.number().optional(),
  pattern: z.string().optional(),

  // For array/object types
  items: z.lazy(() => PropertySchemaSchema).optional(),
  properties: z.record(z.string(), z.lazy(() => PropertySchemaSchema)).optional(),

  // Conditional visibility
  showWhen: ShowWhenConditionSchema.optional(),

  // Metadata flags
  translatable: z.boolean().default(false),
  universal: z.boolean().default(false),      // Can paste to any element type
  supportsTags: z.boolean().default(false),   // Supports {field_name} tags

  // UI customization
  fullWidth: z.boolean().optional(),
  rows: z.number().optional(),                // For textarea
  language: z.string().optional(),            // For code editor
});

export type PropertySchema = z.infer<typeof PropertySchemaSchema>;

// ============================================================
// CATEGORY ENUMS
// ============================================================

export const ElementCategorySchema = z.enum([
  'basic',
  'choice',
  'layout',
  'content',
  'advanced',
  'integration',
]);

export type ElementCategory = z.infer<typeof ElementCategorySchema>;

export const PropertyCategorySchema = z.enum([
  'general',
  'validation',
  'styling',
  'advanced',
  'conditional',
]);

export type PropertyCategory = z.infer<typeof PropertyCategorySchema>;

// ============================================================
// CONTAINER CONFIG
// ============================================================

export const ContainerConfigSchema = z.object({
  allowedChildren: z.union([
    z.array(z.string()),
    z.literal('*'),
  ]).default('*'),
  maxChildren: z.number().optional(),
  minChildren: z.number().optional(),
  childrenLabel: z.string().optional(),
  defaultChildren: z.number().optional(),
});

export type ContainerConfig = z.infer<typeof ContainerConfigSchema>;

// ============================================================
// ELEMENT SCHEMA
// ============================================================

export const ElementSchemaSchema = z.object({
  // Identity
  type: z.string().regex(/^[a-z][a-z0-9_-]*$/, 'Element type must be lowercase alphanumeric'),
  name: z.string().min(1),
  description: z.string(),
  category: ElementCategorySchema,
  icon: z.string(), // Lucide icon name

  // Container behavior
  container: ContainerConfigSchema.nullable(),

  // Properties grouped by category
  properties: z.object({
    general: z.record(z.string(), PropertySchemaSchema).default({}),
    validation: z.record(z.string(), PropertySchemaSchema).optional(),
    styling: z.record(z.string(), PropertySchemaSchema).optional(),
    advanced: z.record(z.string(), PropertySchemaSchema).optional(),
    conditional: z.record(z.string(), PropertySchemaSchema).optional(),
  }),

  // Default values for new elements
  defaults: z.record(z.string(), z.unknown()),

  // Metadata
  translatable: z.array(z.string()).default([]),
  supportsTags: z.array(z.string()).default([]),
});

export type ElementSchema = z.infer<typeof ElementSchemaSchema>;
```

### 4. Base Properties (`/src/schemas/foundation/base-properties.schema.ts`)

```typescript
import { z } from 'zod';
import { PropertySchemaSchema, PropertyCategorySchema } from './types.schema';

/**
 * Base properties inherited by ALL elements.
 * These are automatically merged into every element schema.
 */

// General properties shared by all elements
export const baseGeneralProperties = {
  name: PropertySchemaSchema.parse({
    type: 'string',
    label: 'Field Name',
    description: 'Unique identifier for this field (used in form data)',
    required: true,
    pattern: '^[a-z][a-z0-9_]*$',
    placeholder: 'field_name',
  }),

  label: PropertySchemaSchema.parse({
    type: 'string',
    label: 'Label',
    description: 'Display label shown above the field',
    translatable: true,
  }),

  description: PropertySchemaSchema.parse({
    type: 'string',
    label: 'Description',
    description: 'Help text shown below the field',
    translatable: true,
  }),

  hideLabel: PropertySchemaSchema.parse({
    type: 'boolean',
    label: 'Hide Label',
    default: false,
    universal: true,
  }),
};

// Styling properties shared by all elements
export const baseStylingProperties = {
  width: PropertySchemaSchema.parse({
    type: 'select',
    label: 'Width',
    options: [
      { value: 'full', label: 'Full Width' },
      { value: '3/4', label: '3/4 Width' },
      { value: '2/3', label: '2/3 Width' },
      { value: '1/2', label: '1/2 Width' },
      { value: '1/3', label: '1/3 Width' },
      { value: '1/4', label: '1/4 Width' },
      { value: 'auto', label: 'Auto' },
    ],
    default: 'full',
    universal: true,
  }),

  cssClass: PropertySchemaSchema.parse({
    type: 'string',
    label: 'CSS Classes',
    description: 'Additional CSS classes to apply',
    placeholder: 'my-custom-class',
    universal: true,
  }),

  cssId: PropertySchemaSchema.parse({
    type: 'string',
    label: 'CSS ID',
    description: 'Custom HTML ID attribute',
    pattern: '^[a-zA-Z][a-zA-Z0-9_-]*$',
    universal: true,
  }),
};

// Advanced properties shared by all elements
export const baseAdvancedProperties = {
  excludeFromEntry: PropertySchemaSchema.parse({
    type: 'boolean',
    label: 'Exclude from Entry',
    description: 'Do not save this field value to the database',
    default: false,
    universal: true,
  }),

  excludeFromEmail: PropertySchemaSchema.parse({
    type: 'boolean',
    label: 'Exclude from Emails',
    description: 'Do not include this field in email notifications',
    default: false,
    universal: true,
  }),

  adminOnly: PropertySchemaSchema.parse({
    type: 'boolean',
    label: 'Admin Only',
    description: 'Only show this field to administrators',
    default: false,
    universal: true,
  }),
};

// Conditional properties shared by all elements
export const baseConditionalProperties = {
  conditionalLogic: PropertySchemaSchema.parse({
    type: 'conditional_rules',
    label: 'Conditional Logic',
    description: 'Show or hide this field based on other field values',
    universal: true,
  }),
};

/**
 * Merge base properties with element-specific properties.
 * Call this when defining an element schema.
 */
export function withBaseProperties(elementProperties: {
  general?: Record<string, z.infer<typeof PropertySchemaSchema>>;
  validation?: Record<string, z.infer<typeof PropertySchemaSchema>>;
  styling?: Record<string, z.infer<typeof PropertySchemaSchema>>;
  advanced?: Record<string, z.infer<typeof PropertySchemaSchema>>;
  conditional?: Record<string, z.infer<typeof PropertySchemaSchema>>;
}) {
  return {
    general: {
      ...baseGeneralProperties,
      ...elementProperties.general,
    },
    validation: elementProperties.validation,
    styling: {
      ...baseStylingProperties,
      ...elementProperties.styling,
    },
    advanced: {
      ...baseAdvancedProperties,
      ...elementProperties.advanced,
    },
    conditional: {
      ...baseConditionalProperties,
      ...elementProperties.conditional,
    },
  };
}
```

### 5. Schema Registry (`/src/schemas/foundation/registry.ts`)

```typescript
import { z } from 'zod';
import { ElementSchemaSchema, ElementSchema } from './types.schema';

// Registry schema for validation
export const SchemaRegistrySchema = z.object({
  elements: z.record(z.string(), ElementSchemaSchema),
  version: z.string(),
  generatedAt: z.string().datetime(),
});

export type SchemaRegistry = z.infer<typeof SchemaRegistrySchema>;

// Mutable registry - populated by element schema files
const elements: Record<string, ElementSchema> = {};

/**
 * Register an element schema.
 * Called by each element schema file.
 * Validates schema at registration time (fail fast).
 */
export function registerElement(schema: ElementSchema): ElementSchema {
  // Validate schema structure
  const validated = ElementSchemaSchema.parse(schema);

  if (elements[validated.type]) {
    throw new Error(`Element type '${validated.type}' already registered`);
  }

  elements[validated.type] = validated;
  return validated;
}

/**
 * Get the full registry (for export/generation).
 */
export function getRegistry(): SchemaRegistry {
  return {
    elements,
    version: '1.0.0',
    generatedAt: new Date().toISOString(),
  };
}

/**
 * Get schema for a specific element type.
 */
export function getElementSchema(type: string): ElementSchema | null {
  return elements[type] ?? null;
}

/**
 * Get all registered element types.
 */
export function getAllElementTypes(): string[] {
  return Object.keys(elements);
}

/**
 * Get elements by category.
 */
export function getElementsByCategory(category: string): ElementSchema[] {
  return Object.values(elements).filter(s => s.category === category);
}

/**
 * Validate element data against its schema.
 */
export function validateElementData(
  type: string,
  data: Record<string, unknown>
): { success: true; data: Record<string, unknown> } | { success: false; errors: string[] } {
  const schema = getElementSchema(type);

  if (!schema) {
    return { success: false, errors: [`Unknown element type: ${type}`] };
  }

  const errors: string[] = [];

  // Validate each property
  for (const [category, properties] of Object.entries(schema.properties)) {
    if (!properties) continue;

    for (const [key, propSchema] of Object.entries(properties)) {
      const value = data[key];

      // Check required
      if (propSchema.required && (value === undefined || value === null || value === '')) {
        errors.push(`${key}: Required field is missing`);
        continue;
      }

      // Skip validation if empty and not required
      if (value === undefined || value === null) continue;

      // Type-specific validation
      const typeError = validatePropertyValue(value, propSchema);
      if (typeError) {
        errors.push(`${key}: ${typeError}`);
      }
    }
  }

  return errors.length > 0
    ? { success: false, errors }
    : { success: true, data };
}

function validatePropertyValue(value: unknown, schema: z.infer<typeof import('./types.schema').PropertySchemaSchema>): string | null {
  switch (schema.type) {
    case 'string':
      if (typeof value !== 'string') return 'Expected string';
      if (schema.pattern && !new RegExp(schema.pattern).test(value)) return 'Invalid format';
      if (schema.min !== undefined && value.length < schema.min) return `Minimum length is ${schema.min}`;
      if (schema.max !== undefined && value.length > schema.max) return `Maximum length is ${schema.max}`;
      break;

    case 'number':
      if (typeof value !== 'number') return 'Expected number';
      if (schema.min !== undefined && value < schema.min) return `Minimum value is ${schema.min}`;
      if (schema.max !== undefined && value > schema.max) return `Maximum value is ${schema.max}`;
      break;

    case 'boolean':
      if (typeof value !== 'boolean') return 'Expected boolean';
      break;

    case 'select':
      if (Array.isArray(schema.options)) {
        const validValues = schema.options.map(o => o.value);
        if (!validValues.includes(value as string | number | boolean)) {
          return 'Invalid option';
        }
      }
      break;

    case 'multi_select':
      if (!Array.isArray(value)) return 'Expected array';
      break;

    case 'array':
      if (!Array.isArray(value)) return 'Expected array';
      break;

    case 'object':
      if (typeof value !== 'object' || value === null) return 'Expected object';
      break;
  }

  return null;
}
```

### 6. Exports Index (`/src/schemas/index.ts`)

```typescript
// Foundation exports
export * from './foundation/property-types.schema';
export * from './foundation/types.schema';
export * from './foundation/base-properties.schema';
export * from './foundation/registry';

// Re-export Zod for convenience
export { z } from 'zod';
```

## File Structure

```
/src/schemas/
├── foundation/
│   ├── property-types.schema.ts    # PropertyType enum
│   ├── types.schema.ts             # Core Zod schemas
│   ├── base-properties.schema.ts   # Shared properties
│   └── registry.ts                 # Element registry
├── elements/                       # Phase 2
├── settings/                       # Phase 3
├── automations/                    # Phase 4
├── operations/                     # Phase 5
└── index.ts                        # Aggregated exports
```

## Validation Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    VALIDATION FLOW                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. DEFINITION TIME                                         │
│     └─ registerElement() → Zod.parse() → Fail if invalid   │
│                                                             │
│  2. IMPORT TIME                                             │
│     └─ Element files register on import                     │
│     └─ Invalid schemas crash at startup (fail fast)         │
│                                                             │
│  3. API BOUNDARY                                            │
│     └─ REST endpoint receives data                          │
│     └─ validateElementData() checks against schema          │
│     └─ Return 400 with errors if invalid                    │
│                                                             │
│  4. BUILD TIME                                              │
│     └─ generate-artifacts.ts exports:                       │
│        ├─ JSON Schema (for PHP/MCP)                         │
│        ├─ PHP arrays (for validation)                       │
│        └─ MCP tool definitions                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Acceptance Criteria

- [ ] Zod installed and configured
- [ ] PropertyTypeSchema covers all 26 types
- [ ] ElementSchemaSchema validates complete element definitions
- [ ] registerElement() validates and stores schemas
- [ ] getElementSchema() retrieves by type
- [ ] validateElementData() validates element instances
- [ ] withBaseProperties() merges correctly
- [ ] All exports work from index.ts
- [ ] TypeScript compiles with strict mode

## Dependencies

- `zod` ^3.23.0
- `zod-to-json-schema` ^3.23.0 (for Phase 6)

## Technical Notes

- Use `z.lazy()` for recursive types (array items, object properties)
- Use `.default()` for optional properties with defaults
- Use `.parse()` at definition time for immediate validation
- Use `.safeParse()` at runtime for graceful error handling
- Derive TypeScript types with `z.infer<>` (never duplicate)

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Sections 2-3)
- **Output**: `/src/schemas/foundation/`

### Reference
- Zod docs: https://zod.dev/
- zod-to-json-schema: https://github.com/StefanTerdell/zod-to-json-schema

## Work Log
- [2025-12-03] Task created
- [2025-12-03] Updated to use Zod as source of truth
