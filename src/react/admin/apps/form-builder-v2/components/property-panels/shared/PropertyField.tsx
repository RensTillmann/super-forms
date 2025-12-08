import React from 'react';
import { Label } from '../../../../../components/ui/label';

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
    <div className={`space-y-1.5 ${className}`}>
      <Label className="text-sm font-medium">{label}</Label>
      {children}
    </div>
  );
};
