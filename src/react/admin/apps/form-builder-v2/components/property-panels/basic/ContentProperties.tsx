import React from 'react';
import { PropertyField } from '../shared';

interface ContentPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const ContentProperties: React.FC<ContentPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  if (element.type === 'paragraph') {
    return (
      <PropertyField label="Content">
        <textarea
          value={element.properties?.content || ''}
          onChange={(e) => onUpdate('content', e.target.value)}
          className="form-input"
          rows={3}
          placeholder="Enter paragraph content..."
        />
      </PropertyField>
    );
  }

  if (element.type === 'html-block') {
    return (
      <PropertyField label="HTML Content">
        <textarea
          value={element.properties?.htmlContent || ''}
          onChange={(e) => onUpdate('htmlContent', e.target.value)}
          className="form-input"
          rows={5}
          placeholder="<div>Custom HTML content</div>"
        />
      </PropertyField>
    );
  }

  return null;
};