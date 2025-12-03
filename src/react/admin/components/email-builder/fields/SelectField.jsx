import React from 'react';
import clsx from 'clsx';
import TranslatableField from '../shared/TranslatableField';

function SelectField({ 
  label, 
  value, 
  onChange, 
  options = [],
  help, 
  i18n = false,
  required = false,
  error,
  className,
  ...props 
}) {
  const selectElement = (
    <select
      value={value || ''}
      onChange={(e) => onChange(e.target.value)}
      className={clsx(
        'w-full px-3 py-2 border rounded-md transition-colors',
        'focus:ring-2 focus:ring-primary-500 focus:border-transparent',
        error 
          ? 'border-red-300 text-red-900' 
          : 'border-gray-300',
        className
      )}
      {...props}
    >
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  );

  if (i18n) {
    return (
      <TranslatableField
        label={label}
        value={value}
        onChange={onChange}
        required={required}
        error={error}
        help={help}
      >
        {selectElement}
      </TranslatableField>
    );
  }

  return (
    <div className="space-y-1">
      {label && (
        <label className="flex items-center gap-2 text-sm font-medium text-gray-700">
          <span>{label}</span>
          {required && <span className="text-red-500">*</span>}
        </label>
      )}
      {selectElement}
      {help && (
        <p className="text-xs text-gray-500">{help}</p>
      )}
      {error && (
        <p className="text-xs text-red-600">{error}</p>
      )}
    </div>
  );
}

export default SelectField;