import React from 'react';
import { PropertyField } from '../shared';
import { Input } from '../../../../../components/ui/input';
import { Checkbox } from '../../../../../components/ui/checkbox';
import { Label } from '../../../../../components/ui/label';

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
        <Input
          type="number"
          value={validationRules.minLength || ''}
          onChange={(e) => updateValidation('minLength', e.target.value ? parseInt(e.target.value) : undefined)}
          min={0}
          placeholder="0"
        />
      </PropertyField>

      <PropertyField label="Maximum Length">
        <Input
          type="number"
          value={validationRules.maxLength || ''}
          onChange={(e) => updateValidation('maxLength', e.target.value ? parseInt(e.target.value) : undefined)}
          min={1}
          placeholder="100"
        />
      </PropertyField>

      {(element.type === 'number' || element.type === 'slider') && (
        <>
          <PropertyField label="Minimum Value">
            <Input
              type="number"
              value={validationRules.min || ''}
              onChange={(e) => updateValidation('min', e.target.value ? parseFloat(e.target.value) : undefined)}
              placeholder="0"
            />
          </PropertyField>

          <PropertyField label="Maximum Value">
            <Input
              type="number"
              value={validationRules.max || ''}
              onChange={(e) => updateValidation('max', e.target.value ? parseFloat(e.target.value) : undefined)}
              placeholder="100"
            />
          </PropertyField>
        </>
      )}

      <PropertyField label="Pattern (RegEx)">
        <Input
          type="text"
          value={validationRules.pattern || ''}
          onChange={(e) => updateValidation('pattern', e.target.value)}
          placeholder="^[a-zA-Z0-9]+$"
        />
      </PropertyField>

      <PropertyField label="Custom Error Message">
        <Input
          type="text"
          value={validationRules.errorMessage || ''}
          onChange={(e) => updateValidation('errorMessage', e.target.value)}
          placeholder="Please enter a valid value"
        />
      </PropertyField>

      <PropertyField label="Email Validation">
        <div className="flex items-center gap-2">
          <Checkbox
            id="val-email"
            checked={validationRules.email || false}
            onCheckedChange={(checked) => updateValidation('email', checked)}
          />
          <Label htmlFor="val-email" className="text-sm text-muted-foreground cursor-pointer">
            Validate as email address
          </Label>
        </div>
      </PropertyField>

      <PropertyField label="URL Validation">
        <div className="flex items-center gap-2">
          <Checkbox
            id="val-url"
            checked={validationRules.url || false}
            onCheckedChange={(checked) => updateValidation('url', checked)}
          />
          <Label htmlFor="val-url" className="text-sm text-muted-foreground cursor-pointer">
            Validate as URL
          </Label>
        </div>
      </PropertyField>
    </>
  );
};
