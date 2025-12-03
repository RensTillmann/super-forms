import React from 'react';
import { PropertyField } from '../shared';

interface RatingPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const RatingProperties: React.FC<RatingPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <>
      <PropertyField label="Max Rating">
        <input
          type="number"
          value={element.properties?.maxRating || 5}
          onChange={(e) => onUpdate('maxRating', parseInt(e.target.value))}
          className="form-input"
          min="1"
          max="10"
        />
      </PropertyField>

      <PropertyField label="Rating Style">
        <select
          value={element.properties?.style || 'stars'}
          onChange={(e) => onUpdate('style', e.target.value)}
          className="form-input"
        >
          <option value="stars">Stars</option>
          <option value="hearts">Hearts</option>
          <option value="thumbs">Thumbs Up/Down</option>
          <option value="numbers">Numbers</option>
          <option value="faces">Faces</option>
        </select>
      </PropertyField>

      <PropertyField label="Show Labels">
        <input
          type="checkbox"
          checked={element.properties?.showLabels || false}
          onChange={(e) => onUpdate('showLabels', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      {element.properties?.showLabels && (
        <>
          <PropertyField label="Low Label">
            <input
              type="text"
              value={element.properties?.lowLabel || ''}
              onChange={(e) => onUpdate('lowLabel', e.target.value)}
              className="form-input"
              placeholder="Poor"
            />
          </PropertyField>

          <PropertyField label="High Label">
            <input
              type="text"
              value={element.properties?.highLabel || ''}
              onChange={(e) => onUpdate('highLabel', e.target.value)}
              className="form-input"
              placeholder="Excellent"
            />
          </PropertyField>
        </>
      )}

      <PropertyField label="Allow Half Ratings">
        <input
          type="checkbox"
          checked={element.properties?.allowHalf || false}
          onChange={(e) => onUpdate('allowHalf', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>
    </>
  );
};