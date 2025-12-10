import React from 'react';
import { SchemaPropertyPanel } from '../schema';
import { isElementRegistered } from '../../../../../schemas/core/registry';
import { GeneralProperties, ValidationProperties } from '../basic';

interface ContentTabProps {
  element: {
    id: string;
    type: string;
    properties?: Record<string, unknown>;
  };
  onPropertyChange: (propertyName: string, value: unknown) => void;
}

/**
 * Content tab - renders field-specific properties.
 * Uses schema-driven panel for elements with schemas, falls back to legacy panels.
 */
export const ContentTab: React.FC<ContentTabProps> = ({
  element,
  onPropertyChange,
}) => {
  const hasSchema = isElementRegistered(element.type);

  if (hasSchema) {
    return (
      <SchemaPropertyPanel
        elementType={element.type}
        properties={element.properties || {}}
        onPropertyChange={onPropertyChange}
        categories={['general']}
      />
    );
  }

  // Fallback for elements without schemas
  return (
    <div className="space-y-4">
      <div className="p-3 bg-amber-50 border border-amber-200 rounded-md">
        <p className="text-xs text-amber-700">
          This element type ({element.type}) doesn't have a schema yet.
          Using legacy property panels.
        </p>
      </div>
      <GeneralProperties element={element} onUpdate={onPropertyChange} />
    </div>
  );
};

export default ContentTab;
