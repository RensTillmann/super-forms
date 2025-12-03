import React from 'react';

interface PropertyFieldProps {
  label: string;
  children: React.ReactNode;
  className?: string;
}

export const PropertyField: React.FC<PropertyFieldProps> = ({ 
  label, 
  children, 
  className = '' 
}) => {
  return (
    <div className={`property-field ${className}`}>
      <label className="property-label">{label}</label>
      {children}
    </div>
  );
};