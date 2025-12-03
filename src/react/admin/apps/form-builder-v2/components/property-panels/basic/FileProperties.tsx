import React from 'react';
import { PropertyField } from '../shared';

interface FilePropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const FileProperties: React.FC<FilePropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <>
      <PropertyField label="Allowed File Types">
        <input
          type="text"
          value={element.properties?.acceptedTypes || ''}
          onChange={(e) => onUpdate('acceptedTypes', e.target.value)}
          className="form-input"
          placeholder=".pdf,.doc,.docx,.jpg,.png"
        />
      </PropertyField>

      <PropertyField label="Max File Size (MB)">
        <input
          type="number"
          value={element.properties?.maxSize || 10}
          onChange={(e) => onUpdate('maxSize', parseInt(e.target.value))}
          className="form-input"
          min="1"
        />
      </PropertyField>

      <PropertyField label="Multiple Files">
        <input
          type="checkbox"
          checked={element.properties?.multiple || false}
          onChange={(e) => onUpdate('multiple', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Max Number of Files">
        <input
          type="number"
          value={element.properties?.maxFiles || 5}
          onChange={(e) => onUpdate('maxFiles', parseInt(e.target.value))}
          className="form-input"
          min="1"
          disabled={!element.properties?.multiple}
        />
      </PropertyField>

      <PropertyField label="Upload Button Text">
        <input
          type="text"
          value={element.properties?.uploadText || 'Choose Files'}
          onChange={(e) => onUpdate('uploadText', e.target.value)}
          className="form-input"
        />
      </PropertyField>

      <PropertyField label="Drag & Drop">
        <input
          type="checkbox"
          checked={element.properties?.allowDragDrop !== false}
          onChange={(e) => onUpdate('allowDragDrop', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>
    </>
  );
};