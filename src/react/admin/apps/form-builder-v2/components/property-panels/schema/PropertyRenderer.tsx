import React from 'react';
import { PropertySchema } from '../../../../../schemas/core/types';
import { Input } from '../../../../../components/ui/input';
import { Textarea } from '../../../../../components/ui/textarea';
import { Checkbox } from '../../../../../components/ui/checkbox';
import { Label } from '../../../../../components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '../../../../../components/ui/select';
import { IconPicker } from '../../../../../components/ui/icon-picker';

interface PropertyRendererProps {
  name: string;
  schema: PropertySchema;
  value: unknown;
  onChange: (value: unknown) => void;
}

/**
 * Renders a single property input based on its schema type.
 * This is the core component that maps PropertyType to UI controls.
 */
export const PropertyRenderer: React.FC<PropertyRendererProps> = ({
  name,
  schema,
  value,
  onChange,
}) => {
  const renderInput = () => {
    switch (schema.type) {
      case 'string':
        return (
          <Input
            type="text"
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            placeholder={schema.description}
          />
        );

      case 'number':
      case 'range':
        return (
          <Input
            type="number"
            value={(value as number) ?? ''}
            onChange={(e) => onChange(e.target.value ? Number(e.target.value) : undefined)}
            min={schema.min}
            max={schema.max}
            step={schema.step}
            placeholder={schema.description}
          />
        );

      case 'boolean':
        return (
          <div className="flex items-center gap-2">
            <Checkbox
              id={`prop-${name}`}
              checked={Boolean(value)}
              onCheckedChange={(checked) => onChange(checked)}
            />
            {schema.description && (
              <Label htmlFor={`prop-${name}`} className="text-sm text-muted-foreground cursor-pointer">
                {schema.description}
              </Label>
            )}
          </div>
        );

      case 'select':
        return (
          <Select
            value={(value as string) || ''}
            onValueChange={(val) => onChange(val)}
          >
            <SelectTrigger>
              <SelectValue placeholder="Select..." />
            </SelectTrigger>
            <SelectContent>
              {schema.options?.map((opt) => (
                <SelectItem key={opt.value} value={opt.value}>
                  {opt.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        );

      case 'multi_select':
        const selectedValues = (value as string[]) || [];
        return (
          <div className="space-y-2">
            {schema.options?.map((opt) => (
              <div key={opt.value} className="flex items-center gap-2">
                <Checkbox
                  id={`multi-${name}-${opt.value}`}
                  checked={selectedValues.includes(opt.value)}
                  onCheckedChange={(checked) => {
                    if (checked) {
                      onChange([...selectedValues, opt.value]);
                    } else {
                      onChange(selectedValues.filter((v) => v !== opt.value));
                    }
                  }}
                />
                <Label htmlFor={`multi-${name}-${opt.value}`} className="text-sm cursor-pointer">
                  {opt.label}
                </Label>
              </div>
            ))}
          </div>
        );

      case 'color':
        return (
          <div className="flex items-center gap-2">
            <input
              type="color"
              value={(value as string) || '#000000'}
              onChange={(e) => onChange(e.target.value)}
              className="w-10 h-10 rounded border border-input cursor-pointer"
            />
            <Input
              type="text"
              value={(value as string) || ''}
              onChange={(e) => onChange(e.target.value)}
              placeholder="#000000"
              className="flex-1"
            />
          </div>
        );

      case 'icon':
        return (
          <IconPicker
            value={(value as string) || ''}
            onChange={(iconValue) => onChange(iconValue)}
          />
        );

      case 'rich_text':
        return (
          <Textarea
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            rows={4}
            placeholder={schema.description}
            className="resize-y"
          />
        );

      case 'code':
        return (
          <Textarea
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            rows={6}
            placeholder={schema.description}
            className="font-mono resize-y"
          />
        );

      // Complex types that need specialized components
      case 'conditional_rules':
      case 'columns_config':
      case 'items_config':
      case 'repeater_config':
      case 'step_config':
      case 'email_template':
      case 'calculation':
      case 'key_value':
        return (
          <div className="px-3 py-2 bg-muted border border-border rounded-md text-sm text-muted-foreground">
            Complex editor for "{schema.type}" (not yet implemented)
          </div>
        );

      default:
        return (
          <Input
            type="text"
            value={String(value ?? '')}
            onChange={(e) => onChange(e.target.value)}
          />
        );
    }
  };

  return (
    <div className="space-y-1.5">
      <Label className="text-sm font-medium">
        {schema.label}
        {schema.required && <span className="text-destructive ml-1">*</span>}
        {schema.translatable && (
          <span className="ml-2 text-xs text-blue-500" title="Translatable field">
            🌐
          </span>
        )}
        {schema.supportsTags && (
          <span className="ml-1 text-xs text-purple-500" title="Supports dynamic tags">
            {'{'}...{'}'}
          </span>
        )}
      </Label>
      {renderInput()}
    </div>
  );
};
