import React from 'react';
import { PropertyField } from '../shared';

interface SliderPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const SliderProperties: React.FC<SliderPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <>
      <PropertyField label="Minimum Value">
        <input
          type="number"
          value={element.properties?.min || 0}
          onChange={(e) => onUpdate('min', parseInt(e.target.value))}
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Maximum Value">
        <input
          type="number"
          value={element.properties?.max || 100}
          onChange={(e) => onUpdate('max', parseInt(e.target.value))}
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Step">
        <input
          type="number"
          value={element.properties?.step || 1}
          onChange={(e) => onUpdate('step', parseInt(e.target.value))}
          className="form-input"
          min="1"
        />
      </PropertyField>

      <PropertyField label="Show Value">
        <input
          type="checkbox"
          checked={element.properties?.showValue !== false}
          onChange={(e) => onUpdate('showValue', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Show Ticks">
        <input
          type="checkbox"
          checked={element.properties?.showTicks || false}
          onChange={(e) => onUpdate('showTicks', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>
    </>
  );
};