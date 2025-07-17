import React from 'react';
import TextField from '../../fields/TextField';
import SelectField from '../../fields/SelectField';
import ColorField from '../../fields/ColorField';
import { DIVIDER_STYLE_OPTIONS } from '../../../constants/options';

function DividerProperties({ element, onChange }) {
  const { height, color, style, margin } = element.props;

  const updateMargin = (key, value) => {
    onChange({
      margin: {
        ...margin,
        [key]: parseInt(value) || 0,
      },
    });
  };

  return (
    <div className="ev2-space-y-4">
      <TextField
        label="Height (px)"
        type="number"
        value={height}
        onChange={(value) => onChange({ height: parseInt(value) || 1 })}
      />

      <ColorField
        label="Color"
        value={color}
        onChange={(value) => onChange({ color: value })}
      />

      <SelectField
        label="Style"
        value={style}
        onChange={(value) => onChange({ style: value })}
        options={DIVIDER_STYLE_OPTIONS}
      />

      <div>
        <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
          Margin
        </label>
        <div className="ev2-grid ev2-grid-cols-2 ev2-gap-2">
          <TextField
            label="Top"
            type="number"
            value={margin.top}
            onChange={(value) => updateMargin('top', value)}
          />
          <TextField
            label="Bottom"
            type="number"
            value={margin.bottom}
            onChange={(value) => updateMargin('bottom', value)}
          />
        </div>
      </div>
    </div>
  );
}

export default DividerProperties;