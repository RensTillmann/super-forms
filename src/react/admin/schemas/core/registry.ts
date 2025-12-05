import {
  ElementSchema,
  ElementSchemaSchema,
  PropertySchema,
  PropertiesByCategory,
  PropertyCategory,
} from './types';

/**
 * Schema-First Form Builder - Element Registry
 *
 * Central registry for all element schemas.
 * Elements register themselves via registerElement() at import time.
 * Invalid schemas crash immediately (fail fast).
 */

// =============================================================================
// Registry State
// =============================================================================

const elements: Map<string, ElementSchema> = new Map();

// =============================================================================
// Registration
// =============================================================================

/**
 * Register an element schema. Validates with Zod and crashes if invalid.
 *
 * @example
 * ```ts
 * export const TextElement = registerElement({
 *   type: 'text',
 *   name: 'Text Input',
 *   // ...
 * });
 * ```
 *
 * @throws {ZodError} If schema is invalid (crashes at import time)
 * @throws {Error} If element type is already registered
 */
export function registerElement(schema: ElementSchema): ElementSchema {
  // Validate schema structure with Zod
  // This throws ZodError if invalid, crashing at import time
  const validated = ElementSchemaSchema.parse(schema);

  // Check for duplicate registration
  if (elements.has(validated.type)) {
    throw new Error(
      `Element type '${validated.type}' is already registered. ` +
      `Each element type can only be registered once.`
    );
  }

  // Store in registry
  elements.set(validated.type, validated);

  return validated;
}

// =============================================================================
// Queries
// =============================================================================

/**
 * Get an element schema by type.
 */
export function getElementSchema(type: string): ElementSchema | undefined {
  return elements.get(type);
}

/**
 * Get all registered element types.
 */
export function getAllElementTypes(): string[] {
  return Array.from(elements.keys());
}

/**
 * Get all element schemas.
 */
export function getAllElementSchemas(): ElementSchema[] {
  return Array.from(elements.values());
}

/**
 * Get elements by category.
 */
export function getElementsByCategory(category: ElementSchema['category']): ElementSchema[] {
  return Array.from(elements.values()).filter(el => el.category === category);
}

/**
 * Check if an element type is registered.
 */
export function isElementRegistered(type: string): boolean {
  return elements.has(type);
}

// =============================================================================
// Property Helpers
// =============================================================================

/**
 * Get a specific property schema from an element.
 */
export function getPropertySchema(
  elementType: string,
  category: PropertyCategory,
  propertyName: string
): PropertySchema | undefined {
  const element = elements.get(elementType);
  if (!element) return undefined;

  return element.properties[category]?.[propertyName];
}

/**
 * Get all properties for an element, optionally filtered by category.
 */
export function getElementProperties(
  elementType: string,
  category?: PropertyCategory
): Record<string, PropertySchema> {
  const element = elements.get(elementType);
  if (!element) return {};

  if (category) {
    return element.properties[category] || {};
  }

  // Merge all categories
  const allProps: Record<string, PropertySchema> = {};
  for (const cat of Object.values(element.properties)) {
    Object.assign(allProps, cat);
  }
  return allProps;
}

// =============================================================================
// Base Properties Helper
// =============================================================================

/**
 * Base properties that every element includes.
 */
const BASE_PROPERTIES: PropertiesByCategory = {
  general: {
    name: {
      type: 'string',
      label: 'Field Name',
      description: 'Unique identifier for this field',
      required: true,
      pattern: '^[a-zA-Z_][a-zA-Z0-9_]*$',
    },
    label: {
      type: 'string',
      label: 'Label',
      description: 'Label shown above the field',
      translatable: true,
    },
    width: {
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
    },
  },
  validation: {},
  appearance: {
    hideLabel: {
      type: 'boolean',
      label: 'Hide Label',
      description: 'Hide the field label',
      default: false,
    },
    cssClass: {
      type: 'string',
      label: 'CSS Class',
      description: 'Additional CSS classes for styling',
    },
  },
  advanced: {},
  conditions: {
    conditionalLogic: {
      type: 'conditional_rules',
      label: 'Conditional Logic',
      description: 'Show/hide this field based on conditions',
    },
  },
};

/**
 * Merge base properties with element-specific properties.
 * Use this when defining element schemas to include standard fields.
 *
 * @example
 * ```ts
 * registerElement({
 *   type: 'text',
 *   properties: withBaseProperties({
 *     general: {
 *       placeholder: { type: 'string', label: 'Placeholder' },
 *     },
 *     validation: {
 *       required: { type: 'boolean', label: 'Required' },
 *     },
 *   }),
 *   // ...
 * });
 * ```
 */
export function withBaseProperties(
  elementProperties: Partial<PropertiesByCategory>
): PropertiesByCategory {
  return {
    general: {
      ...BASE_PROPERTIES.general,
      ...(elementProperties.general || {}),
    },
    validation: {
      ...BASE_PROPERTIES.validation,
      ...(elementProperties.validation || {}),
    },
    appearance: {
      ...BASE_PROPERTIES.appearance,
      ...(elementProperties.appearance || {}),
    },
    advanced: {
      ...BASE_PROPERTIES.advanced,
      ...(elementProperties.advanced || {}),
    },
    conditions: {
      ...BASE_PROPERTIES.conditions,
      ...(elementProperties.conditions || {}),
    },
  };
}

// =============================================================================
// Validation
// =============================================================================

/**
 * Validate element instance data against its schema.
 * Used at API boundaries to ensure data integrity.
 *
 * @returns Object with success status and any validation errors
 */
export function validateElementData(
  type: string,
  data: Record<string, unknown>
): { valid: boolean; errors: string[] } {
  const schema = elements.get(type);
  const errors: string[] = [];

  if (!schema) {
    return { valid: false, errors: [`Unknown element type: ${type}`] };
  }

  // Check required properties
  for (const [category, props] of Object.entries(schema.properties)) {
    for (const [propName, propSchema] of Object.entries(props)) {
      const value = data[propName];

      // Check required
      if (propSchema.required && (value === undefined || value === null || value === '')) {
        errors.push(`Property '${propName}' is required`);
      }

      // Skip further validation if value is empty and not required
      if (value === undefined || value === null) continue;

      // Type validation
      switch (propSchema.type) {
        case 'string':
          if (typeof value !== 'string') {
            errors.push(`Property '${propName}' must be a string`);
          } else {
            if (propSchema.minLength && value.length < propSchema.minLength) {
              errors.push(`Property '${propName}' must be at least ${propSchema.minLength} characters`);
            }
            if (propSchema.maxLength && value.length > propSchema.maxLength) {
              errors.push(`Property '${propName}' must be at most ${propSchema.maxLength} characters`);
            }
            if (propSchema.pattern && !new RegExp(propSchema.pattern).test(value)) {
              errors.push(`Property '${propName}' does not match required pattern`);
            }
          }
          break;

        case 'number':
        case 'range':
          if (typeof value !== 'number') {
            errors.push(`Property '${propName}' must be a number`);
          } else {
            if (propSchema.min !== undefined && value < propSchema.min) {
              errors.push(`Property '${propName}' must be at least ${propSchema.min}`);
            }
            if (propSchema.max !== undefined && value > propSchema.max) {
              errors.push(`Property '${propName}' must be at most ${propSchema.max}`);
            }
          }
          break;

        case 'boolean':
          if (typeof value !== 'boolean') {
            errors.push(`Property '${propName}' must be a boolean`);
          }
          break;

        case 'select':
          if (propSchema.options && !propSchema.options.some(opt => opt.value === value)) {
            errors.push(`Property '${propName}' has invalid value`);
          }
          break;

        case 'multi_select':
          if (!Array.isArray(value)) {
            errors.push(`Property '${propName}' must be an array`);
          }
          break;
      }
    }
  }

  return { valid: errors.length === 0, errors };
}

// =============================================================================
// Debug / Development
// =============================================================================

/**
 * Get registry stats for debugging.
 */
export function getRegistryStats(): {
  totalElements: number;
  byCategory: Record<string, number>;
} {
  const byCategory: Record<string, number> = {};

  elements.forEach((element) => {
    byCategory[element.category] = (byCategory[element.category] || 0) + 1;
  });

  return {
    totalElements: elements.size,
    byCategory,
  };
}

/**
 * Clear registry (for testing only).
 */
export function clearRegistry(): void {
  if (process.env.NODE_ENV !== 'test') {
    console.warn('clearRegistry() should only be used in tests');
  }
  elements.clear();
}
