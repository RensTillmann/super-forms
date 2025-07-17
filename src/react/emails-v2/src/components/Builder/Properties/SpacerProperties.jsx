import React from 'react';
import TextField from '../../fields/TextField';

function SpacerProperties({ element, onChange }) {
  const { height } = element.props;

  return (
    <div className="ev2-space-y-4">
      <TextField
        label="Height (px)"
        type="number"
        value={height}
        onChange={(value) => onChange({ height: parseInt(value) || 20 })}
      />
    </div>
  );
}

export default SpacerProperties;