import React from 'react';
import { PropertySchema, PropertyType } from '../../../../../schemas/core/types';

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
          <input
            type="text"
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            placeholder={schema.description}
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        );

      case 'number':
      case 'range':
        return (
          <input
            type="number"
            value={(value as number) ?? ''}
            onChange={(e) => onChange(e.target.value ? Number(e.target.value) : undefined)}
            min={schema.min}
            max={schema.max}
            step={schema.step}
            placeholder={schema.description}
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        );

      case 'boolean':
        return (
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={Boolean(value)}
              onChange={(e) => onChange(e.target.checked)}
              className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            {schema.description && (
              <span className="text-sm text-gray-500">{schema.description}</span>
            )}
          </label>
        );

      case 'select':
        return (
          <select
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Select...</option>
            {schema.options?.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        );

      case 'multi_select':
        const selectedValues = (value as string[]) || [];
        return (
          <div className="space-y-1">
            {schema.options?.map((opt) => (
              <label key={opt.value} className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={selectedValues.includes(opt.value)}
                  onChange={(e) => {
                    if (e.target.checked) {
                      onChange([...selectedValues, opt.value]);
                    } else {
                      onChange(selectedValues.filter((v) => v !== opt.value));
                    }
                  }}
                  className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span className="text-sm">{opt.label}</span>
              </label>
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
              className="w-10 h-10 rounded border border-gray-300 cursor-pointer"
            />
            <input
              type="text"
              value={(value as string) || ''}
              onChange={(e) => onChange(e.target.value)}
              placeholder="#000000"
              className="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        );

      case 'icon':
        // Simplified icon picker - in production would use a proper icon picker component
        return (
          <input
            type="text"
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            placeholder="Icon name (e.g., user, mail)"
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        );

      case 'rich_text':
        return (
          <textarea
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            rows={4}
            placeholder={schema.description}
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
          />
        );

      case 'code':
        return (
          <textarea
            value={(value as string) || ''}
            onChange={(e) => onChange(e.target.value)}
            rows={6}
            placeholder={schema.description}
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
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
          <div className="px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-500">
            Complex editor for "{schema.type}" (not yet implemented)
          </div>
        );

      default:
        return (
          <input
            type="text"
            value={String(value ?? '')}
            onChange={(e) => onChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        );
    }
  };

  return (
    <div className="space-y-1">
      <label className="block text-sm font-medium text-gray-700">
        {schema.label}
        {schema.required && <span className="text-red-500 ml-1">*</span>}
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
      </label>
      {renderInput()}
    </div>
  );
};
