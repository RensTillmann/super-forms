import React from 'react';
import type { ResolvedStyles } from '../../../../../lib/styleUtils';

interface SelectProps {
  element: {
    type: 'select';
    id: string;
    properties?: {
      label?: string;
      description?: string;
      required?: boolean;
      options?: string[];
      placeholder?: string;
      width?: string | number;
    };
  };
  styles: ResolvedStyles;
}

export const Select: React.FC<SelectProps> = ({ element, styles }) => {
  const { properties = {} } = element;

  return (
    <div>
      {/* Label */}
      {properties.label && (
        <label style={styles.label} className="block mb-1">
          {properties.label}
          {properties.required && (
            <span style={styles.required} className="ml-1">*</span>
          )}
        </label>
      )}

      {/* Description */}
      {properties.description && (
        <p style={styles.description} className="text-sm mb-2">
          {properties.description}
        </p>
      )}

      {/* Select */}
      <select
        disabled
        style={styles.input}
        className="form-input w-full border rounded-md px-3 py-2"
      >
        <option>{properties.placeholder || 'Choose an option'}</option>
        {properties.options?.map((option: string, idx: number) => (
          <option key={idx} value={option}>{option}</option>
        ))}
      </select>
    </div>
  );
};

export default Select;
