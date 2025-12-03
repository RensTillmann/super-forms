import React from 'react';
import { PropertyField } from '../shared';

interface ConditionalGroupPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const ConditionalGroupProperties: React.FC<ConditionalGroupPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  const updateCondition = (field: string, value: any) => {
    const newCondition = { 
      ...element.properties?.condition, 
      [field]: value 
    };
    onUpdate('condition', newCondition);
  };

  return (
    <>
      <PropertyField label="Condition Field">
        <input
          type="text"
          value={element.properties?.condition?.field || ''}
          onChange={(e) => updateCondition('field', e.target.value)}
          placeholder="field_name"
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Operator">
        <select
          value={element.properties?.condition?.operator || 'equals'}
          onChange={(e) => updateCondition('operator', e.target.value)}
          className="form-input"
        >
          <option value="equals">Equals</option>
          <option value="not_equals">Not Equals</option>
          <option value="contains">Contains</option>
          <option value="not_contains">Does Not Contain</option>
          <option value="greater_than">Greater Than</option>
          <option value="less_than">Less Than</option>
          <option value="greater_than_or_equal">Greater Than or Equal</option>
          <option value="less_than_or_equal">Less Than or Equal</option>
          <option value="is_empty">Is Empty</option>
          <option value="is_not_empty">Is Not Empty</option>
          <option value="starts_with">Starts With</option>
          <option value="ends_with">Ends With</option>
        </select>
      </PropertyField>

      <PropertyField label="Value">
        <input
          type="text"
          value={element.properties?.condition?.value || ''}
          onChange={(e) => updateCondition('value', e.target.value)}
          placeholder="comparison value"
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Show When Condition is False">
        <input
          type="checkbox"
          checked={element.properties?.showWhenFalse || false}
          onChange={(e) => onUpdate('showWhenFalse', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Animation">
        <select
          value={element.properties?.animation || 'slide'}
          onChange={(e) => onUpdate('animation', e.target.value)}
          className="form-input"
        >
          <option value="none">None</option>
          <option value="slide">Slide</option>
          <option value="fade">Fade</option>
          <option value="scale">Scale</option>
        </select>
      </PropertyField>
    </>
  );
};