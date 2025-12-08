import React from 'react';
import { PropertyField } from '../shared';
import { Input } from '../../../../../components/ui/input';
import { Checkbox } from '../../../../../components/ui/checkbox';
import { Label } from '../../../../../components/ui/label';

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
        <Input
          type="text"
          value={element.properties?.label || ''}
          onChange={(e) => onUpdate('label', e.target.value)}
          placeholder="Field label"
        />
      </PropertyField>

      <PropertyField label="Placeholder">
        <Input
          type="text"
          value={element.properties?.placeholder || ''}
          onChange={(e) => onUpdate('placeholder', e.target.value)}
          placeholder="Enter placeholder text"
        />
      </PropertyField>

      <PropertyField label="Help Text">
        <Input
          type="text"
          value={element.properties?.helpText || ''}
          onChange={(e) => onUpdate('helpText', e.target.value)}
          placeholder="Additional guidance for users"
        />
      </PropertyField>

      <PropertyField label="Required">
        <div className="flex items-center gap-2">
          <Checkbox
            id="prop-required"
            checked={element.properties?.required || false}
            onCheckedChange={(checked) => onUpdate('required', checked)}
          />
          <Label htmlFor="prop-required" className="text-sm text-muted-foreground cursor-pointer">
            Mark field as required
          </Label>
        </div>
      </PropertyField>

      <PropertyField label="Disabled">
        <div className="flex items-center gap-2">
          <Checkbox
            id="prop-disabled"
            checked={element.properties?.disabled || false}
            onCheckedChange={(checked) => onUpdate('disabled', checked)}
          />
          <Label htmlFor="prop-disabled" className="text-sm text-muted-foreground cursor-pointer">
            Disable this field
          </Label>
        </div>
      </PropertyField>

      <PropertyField label="Read Only">
        <div className="flex items-center gap-2">
          <Checkbox
            id="prop-readonly"
            checked={element.properties?.readOnly || false}
            onCheckedChange={(checked) => onUpdate('readOnly', checked)}
          />
          <Label htmlFor="prop-readonly" className="text-sm text-muted-foreground cursor-pointer">
            Make field read-only
          </Label>
        </div>
      </PropertyField>
    </>
  );
};
