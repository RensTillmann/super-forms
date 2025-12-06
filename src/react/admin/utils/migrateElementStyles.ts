import type { FormElement } from '../apps/form-builder-v2/types';
import type { NodeType, StyleProperties } from '../schemas/styles';

/**
 * Map of old inline style property names to new node + property.
 * Add more mappings as you discover old property names in existing forms.
 */
const MIGRATION_MAP: Record<string, { node: NodeType; property: keyof StyleProperties }> = {
  // Label styles
  labelColor: { node: 'label', property: 'color' },
  labelFontSize: { node: 'label', property: 'fontSize' },
  labelFontWeight: { node: 'label', property: 'fontWeight' },
  labelFontFamily: { node: 'label', property: 'fontFamily' },

  // Input styles
  inputBackgroundColor: { node: 'input', property: 'backgroundColor' },
  inputBorderColor: { node: 'input', property: 'borderColor' },
  inputBorderRadius: { node: 'input', property: 'borderRadius' },
  inputColor: { node: 'input', property: 'color' },
  inputFontSize: { node: 'input', property: 'fontSize' },
  inputPadding: { node: 'input', property: 'padding' },

  // Placeholder styles
  placeholderColor: { node: 'placeholder', property: 'color' },

  // Error styles
  errorColor: { node: 'error', property: 'color' },
  errorFontSize: { node: 'error', property: 'fontSize' },

  // Description styles
  descriptionColor: { node: 'description', property: 'color' },
  descriptionFontSize: { node: 'description', property: 'fontSize' },

  // Button styles
  buttonBackgroundColor: { node: 'button', property: 'backgroundColor' },
  buttonColor: { node: 'button', property: 'color' },
  buttonBorderRadius: { node: 'button', property: 'borderRadius' },
  buttonFontSize: { node: 'button', property: 'fontSize' },
  buttonFontWeight: { node: 'button', property: 'fontWeight' },
  buttonPadding: { node: 'button', property: 'padding' },

  // Heading styles
  headingColor: { node: 'heading', property: 'color' },
  headingFontSize: { node: 'heading', property: 'fontSize' },
  headingFontWeight: { node: 'heading', property: 'fontWeight' },
  headingTextAlign: { node: 'heading', property: 'textAlign' },

  // Paragraph styles
  paragraphColor: { node: 'paragraph', property: 'color' },
  paragraphFontSize: { node: 'paragraph', property: 'fontSize' },
  paragraphLineHeight: { node: 'paragraph', property: 'lineHeight' },
};

/**
 * Migrate old inline styles to new style system.
 * Call this when loading a form to convert legacy style properties
 * to the new styleOverrides format.
 *
 * @param element The form element to migrate
 * @returns The migrated element (new object if changes were made)
 */
export function migrateElementStyles(element: FormElement): FormElement {
  if (!element.properties) return element;

  const styleOverrides: FormElement['styleOverrides'] = {};
  const propertiesToRemove: string[] = [];

  // Check each property against migration map
  Object.entries(element.properties).forEach(([key, value]) => {
    const mapping = MIGRATION_MAP[key];
    if (mapping && value !== undefined) {
      // Initialize node overrides if needed
      if (!styleOverrides[mapping.node]) {
        styleOverrides[mapping.node] = {};
      }

      // Add to overrides
      (styleOverrides[mapping.node] as Record<string, unknown>)[mapping.property] = value;

      // Mark for removal from properties
      propertiesToRemove.push(key);
    }
  });

  // If no migrations needed, return original element
  if (propertiesToRemove.length === 0) {
    return element;
  }

  // Create new properties object without migrated keys
  const newProperties = { ...element.properties };
  propertiesToRemove.forEach((key) => {
    delete newProperties[key];
  });

  // Merge with existing styleOverrides if present
  const mergedOverrides = element.styleOverrides
    ? mergeStyleOverrides(element.styleOverrides, styleOverrides)
    : styleOverrides;

  return {
    ...element,
    properties: newProperties,
    styleOverrides: mergedOverrides,
  };
}

/**
 * Merge two styleOverrides objects, with source taking precedence.
 */
function mergeStyleOverrides(
  existing: NonNullable<FormElement['styleOverrides']>,
  source: NonNullable<FormElement['styleOverrides']>
): NonNullable<FormElement['styleOverrides']> {
  const result = { ...existing };

  Object.entries(source).forEach(([nodeType, styles]) => {
    if (result[nodeType as NodeType]) {
      result[nodeType as NodeType] = {
        ...result[nodeType as NodeType],
        ...styles,
      };
    } else {
      result[nodeType as NodeType] = styles;
    }
  });

  return result;
}

/**
 * Migrate all elements in a form.
 *
 * @param elements Array of form elements
 * @returns Array of migrated elements
 */
export function migrateFormElements(elements: FormElement[]): FormElement[] {
  return elements.map(migrateElementStyles);
}

/**
 * Check if an element has any legacy style properties that need migration.
 *
 * @param element The form element to check
 * @returns true if migration is needed
 */
export function needsMigration(element: FormElement): boolean {
  if (!element.properties) return false;

  return Object.keys(element.properties).some((key) => key in MIGRATION_MAP);
}
