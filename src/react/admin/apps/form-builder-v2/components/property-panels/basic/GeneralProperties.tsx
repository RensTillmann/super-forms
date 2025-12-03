import React from 'react';
import { PropertyField } from '../shared';

interface GeneralPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const GeneralProperties: React.FC<GeneralPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <>
      <PropertyField label="Label">
        <input
          type="text"
          value={element.properties?.label || ''}
          onChange={(e) => onUpdate('label', e.target.value)}
          className="form-input"
          placeholder="Field label"
        />
      </PropertyField>

      <PropertyField label="Placeholder">
        <input
          type="text"
          value={element.properties?.placeholder || ''}
          onChange={(e) => onUpdate('placeholder', e.target.value)}
          className="form-input"
          placeholder="Enter placeholder text"
        />
      </PropertyField>

      <PropertyField label="Help Text">
        <input
          type="text"
          value={element.properties?.helpText || ''}
          onChange={(e) => onUpdate('helpText', e.target.value)}
          className="form-input"
          placeholder="Additional guidance for users"
        />
      </PropertyField>

      <PropertyField label="Required">
        <input
          type="checkbox"
          checked={element.properties?.required || false}
          onChange={(e) => onUpdate('required', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Disabled">
        <input
          type="checkbox"
          checked={element.properties?.disabled || false}
          onChange={(e) => onUpdate('disabled', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Read Only">
        <input
          type="checkbox"
          checked={element.properties?.readOnly || false}
          onChange={(e) => onUpdate('readOnly', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>
    </>
  );
};