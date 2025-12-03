import React from 'react';
import { PropertyField } from '../shared';

interface ValidationPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const ValidationProperties: React.FC<ValidationPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  const validationRules = element.properties?.validation || {};

  const updateValidation = (rule: string, value: any) => {
    const newValidation = { ...validationRules, [rule]: value };
    onUpdate('validation', newValidation);
  };

  return (
    <>
      <PropertyField label="Minimum Length">
        <input
          type="number"
          value={validationRules.minLength || ''}
          onChange={(e) => updateValidation('minLength', e.target.value ? parseInt(e.target.value) : undefined)}
          className="form-input"
          min="0"
          placeholder="0"
        />
      </PropertyField>

      <PropertyField label="Maximum Length">
        <input
          type="number"
          value={validationRules.maxLength || ''}
          onChange={(e) => updateValidation('maxLength', e.target.value ? parseInt(e.target.value) : undefined)}
          className="form-input"
          min="1"
          placeholder="100"
        />
      </PropertyField>

      {(element.type === 'number' || element.type === 'slider') && (
        <>
          <PropertyField label="Minimum Value">
            <input
              type="number"
              value={validationRules.min || ''}
              onChange={(e) => updateValidation('min', e.target.value ? parseFloat(e.target.value) : undefined)}
              className="form-input"
              placeholder="0"
            />
          </PropertyField>

          <PropertyField label="Maximum Value">
            <input
              type="number"
              value={validationRules.max || ''}
              onChange={(e) => updateValidation('max', e.target.value ? parseFloat(e.target.value) : undefined)}
              className="form-input"
              placeholder="100"
            />
          </PropertyField>
        </>
      )}

      <PropertyField label="Pattern (RegEx)">
        <input
          type="text"
          value={validationRules.pattern || ''}
          onChange={(e) => updateValidation('pattern', e.target.value)}
          className="form-input"
          placeholder="^[a-zA-Z0-9]+$"
        />
      </PropertyField>

      <PropertyField label="Custom Error Message">
        <input
          type="text"
          value={validationRules.errorMessage || ''}
          onChange={(e) => updateValidation('errorMessage', e.target.value)}
          className="form-input"
          placeholder="Please enter a valid value"
        />
      </PropertyField>

      <PropertyField label="Email Validation">
        <input
          type="checkbox"
          checked={validationRules.email || false}
          onChange={(e) => updateValidation('email', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="URL Validation">
        <input
          type="checkbox"
          checked={validationRules.url || false}
          onChange={(e) => updateValidation('url', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>
    </>
  );
};