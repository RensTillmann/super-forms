import React from 'react';

interface PropertyGroupProps {
  children: React.ReactNode;
  className?: string;
}

export const PropertyGroup: React.FC<PropertyGroupProps> = ({ 
  children, 
  className = '' 
}) => {
  return (
    <div className={`property-group ${className}`}>
      {children}
    </div>
  );
};