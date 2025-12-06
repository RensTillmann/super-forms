import React from 'react';
import { Radio } from 'lucide-react';
import { InlineEditableText } from '../../shared/InlineEditableText';
import type { ResolvedStyles } from '../../../../../lib/styleUtils';

interface RadioCardsProps {
  element: {
    type: 'radio-cards';
    id: string;
    properties?: {
      label?: string;
      description?: string;
      required?: boolean;
      options?: string[];
      columnsPerRow?: number;
      showDescriptions?: boolean;
      width?: string | number;
    };
  };
  updateElementProperty: (id: string, property: string, value: unknown) => void;
  styles: ResolvedStyles;
}

export const RadioCards: React.FC<RadioCardsProps> = ({ element, updateElementProperty, styles }) => {
  const { properties = {} } = element;

  return (
    <div>
      {/* Label */}
      {properties.label && (
        <label style={styles.label} className="block mb-2">
          {properties.label}
          {properties.required && (
            <span style={styles.required} className="ml-1">*</span>
          )}
        </label>
      )}

      {/* Description */}
      {properties.description && (
        <p style={styles.description} className="text-sm mb-3">
          {properties.description}
        </p>
      )}

      {/* Cards Grid */}
      <div
        className="grid gap-3"
        style={{
          gridTemplateColumns: `repeat(${properties.columnsPerRow || 2}, 1fr)`
        }}
      >
        {properties.options?.map((option: string, idx: number) => (
          <div
            key={idx}
            style={styles.cardContainer}
            className="radio-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer transition-all hover:border-blue-300 hover:shadow-md"
          >
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <div className="radio-card-icon mb-2">
                  <div className="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                    <Radio size={16} className="text-gray-400" />
                  </div>
                </div>
                <div className="radio-card-content">
                  <InlineEditableText
                    value={option}
                    onChange={(value) => {
                      const newOptions = [...(properties.options || [])];
                      newOptions[idx] = value;
                      updateElementProperty(element.id, 'options', newOptions);
                    }}
                    className="font-medium text-gray-900 text-sm leading-tight"
                    placeholder="Option title"
                  />
                  {properties.showDescriptions !== false && (
                    <p style={styles.optionLabel} className="text-xs text-gray-500 mt-1">
                      Description for this option
                    </p>
                  )}
                </div>
              </div>
              <input
                type="radio"
                name={element.id}
                className="radio-card-input mt-1"
                disabled
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default RadioCards;