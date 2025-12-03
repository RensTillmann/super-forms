import React from 'react';

interface TextInputProps {
  element: {
    type: 'text' | 'email' | 'phone' | 'url' | 'password' | 'number' | 'number-formatted';
    id: string;
    properties?: {
      placeholder?: string;
      width?: string | number;
      margin?: string;
      backgroundColor?: string;
      borderStyle?: string;
    };
  };
  commonProps: {
    className: string;
    disabled: boolean;
    style: {
      width: string;
      margin?: string;
      backgroundColor?: string;
      borderStyle?: string;
    };
  };
}

export const TextInput: React.FC<TextInputProps> = ({ element, commonProps }) => {
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
    if (element.properties?.placeholder) {
      return element.properties.placeholder;
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
    <input
      type={getInputType()}
      placeholder={getPlaceholder()}
      {...commonProps}
    />
  );
};

export default TextInput;