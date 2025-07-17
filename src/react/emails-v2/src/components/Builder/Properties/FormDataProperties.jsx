import React, { useState, useEffect } from 'react';
import SelectField from '../../fields/SelectField';
import CheckboxField from '../../fields/CheckboxField';
import TextField from '../../fields/TextField';

function FormDataProperties({ element, onChange }) {
  const { 
    fieldId, 
    showLabel, 
    labelPosition, 
    emptyText,
    dateFormat,
    numberFormat 
  } = element.props;

  const [formFields, setFormFields] = useState([]);

  useEffect(() => {
    // In a real implementation, this would fetch actual form fields
    // For now, we'll use mock data
    setFormFields([
      { value: '', label: 'Select a field...' },
      { value: 'field_name', label: 'Name', type: 'text' },
      { value: 'field_email', label: 'Email', type: 'email' },
      { value: 'field_phone', label: 'Phone', type: 'tel' },
      { value: 'field_message', label: 'Message', type: 'textarea' },
      { value: 'field_date', label: 'Date', type: 'date' },
      { value: 'field_amount', label: 'Amount', type: 'number' },
      { value: 'field_country', label: 'Country', type: 'select' },
      { value: 'field_subscribe', label: 'Subscribe to Newsletter', type: 'checkbox' },
    ]);
  }, []);

  const selectedField = formFields.find(f => f.value === fieldId);
  const fieldType = selectedField?.type || 'text';

  const labelPositionOptions = [
    { value: 'above', label: 'Above' },
    { value: 'before', label: 'Before' },
    { value: 'after', label: 'After' },
  ];

  const dateFormatOptions = [
    { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY' },
    { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY' },
    { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD' },
    { value: 'Month DD, YYYY', label: 'Month DD, YYYY' },
  ];

  const numberFormatOptions = [
    { value: 'default', label: 'Default' },
    { value: 'currency', label: 'Currency ($1,234.56)' },
    { value: 'percent', label: 'Percent (12.34%)' },
    { value: 'decimal-2', label: '2 Decimal Places' },
  ];

  return (
    <div className="ev2-space-y-4">
      <SelectField
        label="Form Field"
        value={fieldId}
        onChange={(value) => onChange({ fieldId: value })}
        options={formFields}
      />

      {fieldId && (
        <>
          <CheckboxField
            label="Show Field Label"
            value={showLabel}
            onChange={(value) => onChange({ showLabel: value })}
          />

          {showLabel && (
            <SelectField
              label="Label Position"
              value={labelPosition}
              onChange={(value) => onChange({ labelPosition: value })}
              options={labelPositionOptions}
            />
          )}

          <TextField
            label="Text When Empty"
            value={emptyText}
            onChange={(value) => onChange({ emptyText: value })}
            placeholder="No value provided"
          />

          {fieldType === 'date' && (
            <SelectField
              label="Date Format"
              value={dateFormat}
              onChange={(value) => onChange({ dateFormat: value })}
              options={dateFormatOptions}
            />
          )}

          {fieldType === 'number' && (
            <SelectField
              label="Number Format"
              value={numberFormat}
              onChange={(value) => onChange({ numberFormat: value })}
              options={numberFormatOptions}
            />
          )}
        </>
      )}

      <div className="ev2-mt-4 ev2-p-3 ev2-bg-blue-50 ev2-rounded-lg">
        <p className="ev2-text-sm ev2-text-blue-800">
          <strong>Note:</strong> This will insert the value submitted in the form field. 
          The actual value will be shown when the email is sent.
        </p>
      </div>
    </div>
  );
}

export default FormDataProperties;