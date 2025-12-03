import React from 'react';

interface TextAreaProps {
  element: {
    type: 'textarea';
    id: string;
    properties?: {
      placeholder?: string;
      rows?: number;
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

export const TextArea: React.FC<TextAreaProps> = ({ element, commonProps }) => {
  return (
    <textarea
      placeholder={element.properties?.placeholder || 'Enter text'}
      rows={element.properties?.rows || 3}
      {...commonProps}
    />
  );
};

export default TextArea;