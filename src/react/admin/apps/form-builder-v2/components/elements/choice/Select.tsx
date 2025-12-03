import React from 'react';

interface SelectProps {
  element: {
    type: 'select';
    id: string;
    properties?: {
      options?: string[];
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

export const Select: React.FC<SelectProps> = ({ element, commonProps }) => {
  return (
    <select {...commonProps}>
      <option>{element.properties?.placeholder || 'Choose an option'}</option>
      {element.properties?.options?.map((option: string, idx: number) => (
        <option key={idx} value={option}>{option}</option>
      ))}
    </select>
  );
};

export default Select;