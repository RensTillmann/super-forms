import React from 'react';
import { PropertyField } from '../shared';

interface RepeaterPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const RepeaterProperties: React.FC<RepeaterPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <>
      <PropertyField label="Item Label">
        <input
          type="text"
          value={element.properties?.itemLabel || 'Item'}
          onChange={(e) => onUpdate('itemLabel', e.target.value)}
          placeholder="Item"
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Minimum Items">
        <input
          type="number"
          value={element.properties?.minItems || 1}
          onChange={(e) => onUpdate('minItems', parseInt(e.target.value))}
          min="0"
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Maximum Items">
        <input
          type="number"
          value={element.properties?.maxItems || 10}
          onChange={(e) => onUpdate('maxItems', parseInt(e.target.value))}
          min="1"
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Allow Reordering">
        <input
          type="checkbox"
          checked={element.properties?.allowReordering !== false}
          onChange={(e) => onUpdate('allowReordering', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Collapsible Items">
        <input
          type="checkbox"
          checked={element.properties?.collapsibleItems || false}
          onChange={(e) => onUpdate('collapsibleItems', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>
    </>
  );
};