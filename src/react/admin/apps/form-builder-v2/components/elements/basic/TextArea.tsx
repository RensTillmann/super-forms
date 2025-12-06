import React from 'react';
import type { ResolvedStyles } from '../../../../../lib/styleUtils';

interface TextAreaProps {
  element: {
    type: 'textarea';
    id: string;
    properties?: {
      label?: string;
      placeholder?: string;
      description?: string;
      required?: boolean;
      rows?: number;
      width?: string | number;
    };
  };
  styles: ResolvedStyles;
}

export const TextArea: React.FC<TextAreaProps> = ({ element, styles }) => {
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

      {/* Description (before input) */}
      {properties.description && (
        <p style={styles.description} className="text-sm mb-2">
          {properties.description}
        </p>
      )}

      {/* TextArea */}
      <textarea
        placeholder={properties.placeholder || 'Enter text'}
        rows={properties.rows || 3}
        disabled
        style={styles.input}
        className="form-input w-full border rounded-md px-3 py-2"
      />
    </div>
  );
};

export default TextArea;
