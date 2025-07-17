import React from 'react';
import TextField from '../../fields/TextField';
import SelectField from '../../fields/SelectField';
import CheckboxField from '../../fields/CheckboxField';
import { COLUMN_OPTIONS } from '../../../constants/options';

function ColumnsProperties({ element, onChange }) {
  const { columns, gap, stackOnMobile } = element.props;

  return (
    <div className="ev2-space-y-4">
      <SelectField
        label="Number of Columns"
        value={columns}
        onChange={(value) => onChange({ columns: parseInt(value) })}
        options={COLUMN_OPTIONS}
      />

      <TextField
        label="Gap (px)"
        type="number"
        value={gap}
        onChange={(value) => onChange({ gap: parseInt(value) || 20 })}
      />

      <CheckboxField
        label="Stack on Mobile"
        value={stackOnMobile}
        onChange={(value) => onChange({ stackOnMobile: value })}
      />
    </div>
  );
}

export default ColumnsProperties;