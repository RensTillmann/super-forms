import React, { useState, useMemo } from 'react';
import { getElementSchema } from '../../../../../schemas/core/registry';
import { PropertyCategory, PropertySchema } from '../../../../../schemas/core/types';
import { PropertyRenderer } from './PropertyRenderer';

interface SchemaPropertyPanelProps {
  /** The element type (e.g., 'text', 'select') */
  elementType: string;
  /** Current property values */
  properties: Record<string, unknown>;
  /** Callback when a property changes */
  onPropertyChange: (propertyName: string, value: unknown) => void;
  /** Optional: only show specific categories */
  categories?: PropertyCategory[];
}

/**
 * Schema-driven property panel.
 * Automatically renders all properties for an element type based on its schema.
 *
 * This replaces hardcoded property panels with dynamic generation from the schema registry.
 */
export const SchemaPropertyPanel: React.FC<SchemaPropertyPanelProps> = ({
  elementType,
  properties,
  onPropertyChange,
  categories,
}) => {
  const schema = useMemo(() => getElementSchema(elementType), [elementType]);
  const [activeCategory, setActiveCategory] = useState<PropertyCategory>('general');

  if (!schema) {
    return (
      <div className="p-4 text-sm text-red-600 bg-red-50 rounded-md">
        Unknown element type: {elementType}
      </div>
    );
  }

  // Filter categories if specified
  const visibleCategories = categories || (['general', 'validation', 'appearance', 'advanced', 'conditions'] as PropertyCategory[]);

  // Get categories that have properties
  const categoriesWithProps = visibleCategories.filter(
    (cat) => schema.properties[cat] && Object.keys(schema.properties[cat]).length > 0
  );

  // Category labels
  const categoryLabels: Record<PropertyCategory, string> = {
    general: 'General',
    validation: 'Validation',
    appearance: 'Appearance',
    advanced: 'Advanced',
    conditions: 'Conditions',
  };

  const currentCategoryProps = schema.properties[activeCategory] || {};

  // Check if a property should be visible based on its conditions
  const isPropertyVisible = (propSchema: PropertySchema): boolean => {
    if (!propSchema.conditions || propSchema.conditions.length === 0) {
      return true;
    }

    return propSchema.conditions.every((condition) => {
      const conditionValue = properties[condition.property];

      switch (condition.operator) {
        case 'equals':
          return conditionValue === condition.value;
        case 'not_equals':
          return conditionValue !== condition.value;
        case 'contains':
          return Array.isArray(conditionValue) && conditionValue.includes(condition.value);
        case 'not_contains':
          return !Array.isArray(conditionValue) || !conditionValue.includes(condition.value);
        case 'empty':
          return conditionValue === undefined || conditionValue === null || conditionValue === '';
        case 'not_empty':
          return conditionValue !== undefined && conditionValue !== null && conditionValue !== '';
        case 'gt':
          return typeof conditionValue === 'number' && conditionValue > (condition.value as number);
        case 'lt':
          return typeof conditionValue === 'number' && conditionValue < (condition.value as number);
        case 'gte':
          return typeof conditionValue === 'number' && conditionValue >= (condition.value as number);
        case 'lte':
          return typeof conditionValue === 'number' && conditionValue <= (condition.value as number);
        default:
          return true;
      }
    });
  };

  return (
    <div className="schema-property-panel">
      {/* Category Tabs */}
      {categoriesWithProps.length > 1 && (
        <div className="flex border-b border-gray-200 mb-4">
          {categoriesWithProps.map((cat) => (
            <button
              key={cat}
              onClick={() => setActiveCategory(cat)}
              className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                activeCategory === cat
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              {categoryLabels[cat]}
            </button>
          ))}
        </div>
      )}

      {/* Properties */}
      <div className="space-y-4">
        {Object.entries(currentCategoryProps).map(([propName, propSchema]) => {
          // Skip hidden properties
          if (!isPropertyVisible(propSchema)) {
            return null;
          }

          return (
            <PropertyRenderer
              key={propName}
              name={propName}
              schema={propSchema}
              value={properties[propName]}
              onChange={(value) => onPropertyChange(propName, value)}
            />
          );
        })}

        {Object.keys(currentCategoryProps).length === 0 && (
          <div className="text-sm text-gray-500 italic">
            No properties in this category.
          </div>
        )}
      </div>

      {/* Debug info in development */}
      {process.env.NODE_ENV === 'development' && (
        <details className="mt-6 text-xs">
          <summary className="cursor-pointer text-gray-400 hover:text-gray-600">
            Debug: Schema Info
          </summary>
          <pre className="mt-2 p-2 bg-gray-100 rounded overflow-auto max-h-48">
            {JSON.stringify(
              {
                type: schema.type,
                name: schema.name,
                category: schema.category,
                propertyCount: Object.values(schema.properties).reduce(
                  (sum, cat) => sum + Object.keys(cat).length,
                  0
                ),
              },
              null,
              2
            )}
          </pre>
        </details>
      )}
    </div>
  );
};
