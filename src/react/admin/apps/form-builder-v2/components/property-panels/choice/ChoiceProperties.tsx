import React from 'react';
import { PropertyField, OptionsEditor } from '../shared';

interface ChoicePropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const ChoiceProperties: React.FC<ChoicePropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  const isCardType = element.type === 'radio-cards' || element.type === 'checkbox-cards';
  
  return (
    <>
      <PropertyField label="Options">
        <OptionsEditor
          options={element.properties?.options || ['Option 1', 'Option 2']}
          onUpdate={(options) => onUpdate('options', options)}
        />
      </PropertyField>

      {isCardType && (
        <>
          <PropertyField label="Columns Per Row">
            <select
              value={element.properties?.columnsPerRow || 2}
              onChange={(e) => onUpdate('columnsPerRow', parseInt(e.target.value))}
              className="form-input"
            >
              <option value={1}>1 Column</option>
              <option value={2}>2 Columns</option>
              <option value={3}>3 Columns</option>
              <option value={4}>4 Columns</option>
            </select>
          </PropertyField>

          <PropertyField label="Show Descriptions">
            <input
              type="checkbox"
              checked={element.properties?.showDescriptions !== false}
              onChange={(e) => onUpdate('showDescriptions', e.target.checked)}
              className="form-checkbox"
            />
          </PropertyField>
        </>
      )}

      {element.type === 'select' && (
        <>
          <PropertyField label="Multiple Selection">
            <input
              type="checkbox"
              checked={element.properties?.multiple || false}
              onChange={(e) => onUpdate('multiple', e.target.checked)}
              className="form-checkbox"
            />
          </PropertyField>

          <PropertyField label="Searchable">
            <input
              type="checkbox"
              checked={element.properties?.searchable || false}
              onChange={(e) => onUpdate('searchable', e.target.checked)}
              className="form-checkbox"
            />
          </PropertyField>
        </>
      )}

      <PropertyField label="Default Value">
        <input
          type="text"
          value={element.properties?.defaultValue || ''}
          onChange={(e) => onUpdate('defaultValue', e.target.value)}
          className="form-input"
          placeholder="Select default option"
        />
      </PropertyField>
    </>
  );
};