import { z } from 'zod';

/**
 * Schema-First Form Builder - Core Types
 *
 * This is the SINGLE SOURCE OF TRUTH for all form element schemas.
 * All property types, element definitions, and validation rules are defined here.
 *
 * Build pipeline: Zod → JSON Schema → PHP arrays → MCP tools
 */

// =============================================================================
// Property Types
// =============================================================================

/**
 * All 26 property types supported by the form builder.
 * Each type maps to a specific property renderer in the UI.
 */
export const PropertyTypeSchema = z.enum([
  // Primitives
  'string',
  'number',
  'boolean',

  // Selection
  'select',
  'multi_select',

  // Visual
  'color',
  'icon',

  // Complex structures
  'array',
  'object',

  // Form-specific
  'conditional_rules',
  'columns_config',
  'items_config',
  'rich_text',
  'code',

  // Date/Time
  'date',
  'time',
  'datetime',

  // Media
  'file',
  'image',

  // Numeric
  'range',

  // Advanced
  'key_value',
  'repeater_config',
  'step_config',
  'email_template',
  'calculation',
  'tag_input',
]);

export type PropertyType = z.infer<typeof PropertyTypeSchema>;

// =============================================================================
// Property Schema
// =============================================================================

/**
 * Condition for showing/hiding a property based on another property's value.
 */
export const PropertyConditionSchema = z.object({
  property: z.string(),
  operator: z.enum(['equals', 'not_equals', 'contains', 'not_contains', 'gt', 'lt', 'gte', 'lte', 'empty', 'not_empty']),
  value: z.unknown().optional(),
});

export type PropertyCondition = z.infer<typeof PropertyConditionSchema>;

/**
 * Option for select/multi_select property types.
 */
export const SelectOptionSchema = z.object({
  value: z.string(),
  label: z.string(),
  icon: z.string().optional(),
  description: z.string().optional(),
});

export type SelectOption = z.infer<typeof SelectOptionSchema>;

/**
 * Schema for a single property definition.
 * This defines how a property behaves and renders in the property panel.
 */
export const PropertySchemaSchema = z.object({
  // Core
  type: PropertyTypeSchema,
  label: z.string(),
  description: z.string().optional(),
  default: z.unknown().optional(),

  // Behavior
  required: z.boolean().optional(),
  translatable: z.boolean().optional(),
  supportsTags: z.boolean().optional(),
  readonly: z.boolean().optional(),

  // Selection options (for select/multi_select)
  options: z.array(SelectOptionSchema).optional(),

  // Numeric constraints (for number/range)
  min: z.number().optional(),
  max: z.number().optional(),
  step: z.number().optional(),

  // String constraints
  minLength: z.number().optional(),
  maxLength: z.number().optional(),
  pattern: z.string().optional(),

  // Conditional visibility
  conditions: z.array(PropertyConditionSchema).optional(),

  // Grouping hint for UI
  group: z.string().optional(),
});

export type PropertySchema = z.infer<typeof PropertySchemaSchema>;

// =============================================================================
// Categories
// =============================================================================

/**
 * Property categories for organizing the property panel.
 */
export const PropertyCategorySchema = z.enum([
  'general',
  'validation',
  'appearance',
  'advanced',
  'conditions',
]);

export type PropertyCategory = z.infer<typeof PropertyCategorySchema>;

/**
 * Element categories for the element palette.
 */
export const ElementCategorySchema = z.enum([
  'basic',
  'choice',
  'advanced',
  'layout',
  'content',
]);

export type ElementCategory = z.infer<typeof ElementCategorySchema>;

// =============================================================================
// Container Configuration
// =============================================================================

/**
 * Configuration for container elements that can hold children.
 */
export const ContainerConfigSchema = z.object({
  /** Element types this container accepts. Empty = accepts all */
  accepts: z.array(z.string()).optional(),
  /** Element types this container rejects */
  rejects: z.array(z.string()).optional(),
  /** Maximum number of children allowed */
  maxChildren: z.number().optional(),
  /** Minimum number of children required */
  minChildren: z.number().optional(),
  /** Whether children can be reordered */
  allowReorder: z.boolean().optional().default(true),
});

export type ContainerConfig = z.infer<typeof ContainerConfigSchema>;

// =============================================================================
// Element Schema
// =============================================================================

/**
 * Complete schema for a form element type.
 * This is the master definition that drives:
 * - Property panel rendering
 * - Validation rules
 * - PHP schema generation
 * - MCP tool generation
 */
export const ElementSchemaSchema = z.object({
  // Identity
  type: z.string().regex(/^[a-z][a-z0-9-]*$/, 'Type must be lowercase alphanumeric with dashes'),
  name: z.string().min(1),
  description: z.string(),
  category: ElementCategorySchema,
  icon: z.string(),

  // Container behavior (null for non-containers)
  container: ContainerConfigSchema.nullable(),

  // Properties organized by category
  properties: z.record(PropertyCategorySchema, z.record(z.string(), PropertySchemaSchema)),

  // Default values for new instances
  defaults: z.record(z.string(), z.unknown()),

  // Fields that support translation
  translatable: z.array(z.string()),

  // Fields that support dynamic tags (e.g., {field_name})
  supportsTags: z.array(z.string()),
});

export type ElementSchema = z.infer<typeof ElementSchemaSchema>;

// =============================================================================
// Helper Types
// =============================================================================

/**
 * Properties organized by category - used for type inference in element definitions.
 */
export type PropertiesByCategory = z.infer<typeof ElementSchemaSchema>['properties'];

/**
 * Base properties that every element has.
 * These are merged into each element's properties automatically.
 */
export const BasePropertiesSchema = z.object({
  general: z.object({
    name: PropertySchemaSchema.parse({
      type: 'string',
      label: 'Field Name',
      description: 'Unique identifier for this field',
      required: true,
      pattern: '^[a-zA-Z_][a-zA-Z0-9_]*$',
    }),
    label: PropertySchemaSchema.parse({
      type: 'string',
      label: 'Label',
      description: 'Label shown above the field',
      translatable: true,
    }),
    width: PropertySchemaSchema.parse({
      type: 'select',
      label: 'Width',
      description: 'Field width in the form layout',
      options: [
        { value: 'full', label: 'Full Width' },
        { value: '1/2', label: 'Half (1/2)' },
        { value: '1/3', label: 'Third (1/3)' },
        { value: '2/3', label: 'Two Thirds (2/3)' },
        { value: '1/4', label: 'Quarter (1/4)' },
        { value: '3/4', label: 'Three Quarters (3/4)' },
      ],
      default: 'full',
    }),
  }),
  appearance: z.object({
    hideLabel: PropertySchemaSchema.parse({
      type: 'boolean',
      label: 'Hide Label',
      description: 'Hide the field label',
      default: false,
    }),
    cssClass: PropertySchemaSchema.parse({
      type: 'string',
      label: 'CSS Class',
      description: 'Additional CSS classes for styling',
    }),
  }),
  conditions: z.object({
    conditionalLogic: PropertySchemaSchema.parse({
      type: 'conditional_rules',
      label: 'Conditional Logic',
      description: 'Show/hide this field based on conditions',
    }),
  }),
});

export type BaseProperties = z.infer<typeof BasePropertiesSchema>;
