import React from 'react';
import type { ResolvedStyles } from '../../../../../lib/styleUtils';

interface TextInputProps {
  element: {
    type: 'text' | 'email' | 'phone' | 'url' | 'password' | 'number' | 'number-formatted';
    id: string;
    properties?: {
      label?: string;
      placeholder?: string;
      description?: string;
      required?: boolean;
      width?: string | number;
    };
  };
  styles: ResolvedStyles;
}

export const TextInput: React.FC<TextInputProps> = ({ element, styles }) => {
  const { properties = {} } = element;

  const getInputType = () => {
    switch (element.type) {
      case 'password':
        return 'password';
      case 'number':
      case 'number-formatted':
        return 'number';
      case 'email':
        return 'email';
      case 'phone':
        return 'tel';
      case 'url':
        return 'url';
      default:
        return 'text';
    }
  };

  const getPlaceholder = () => {
    if (properties.placeholder) {
      return properties.placeholder;
    }

    switch (element.type) {
      case 'password':
        return 'Enter password';
      case 'email':
        return 'Enter email address';
      case 'phone':
        return 'Enter phone number';
      case 'url':
        return 'Enter URL';
      case 'number':
      case 'number-formatted':
        return 'Enter number';
      default:
        return 'Enter text';
    }
  };

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

      {/* Input */}
      <input
        type={getInputType()}
        placeholder={getPlaceholder()}
        disabled
        style={styles.input}
        className="form-input w-full border rounded-md px-3 py-2"
      />
    </div>
  );
};

export default TextInput;
