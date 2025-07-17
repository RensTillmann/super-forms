import React from 'react';
import clsx from 'clsx';
import TranslatableField from '../shared/TranslatableField';

function TextareaField({ 
  label, 
  value, 
  onChange, 
  placeholder, 
  help, 
  i18n = false,
  required = false,
  error,
  rows = 4,
  className,
  ...props 
}) {
  const textareaElement = (
    <textarea
      value={value || ''}
      onChange={(e) => onChange(e.target.value)}
      placeholder={placeholder}
      rows={rows}
      className={clsx(
        'ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-rounded-md ev2-transition-colors',
        'focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent',
        error 
          ? 'ev2-border-red-300 ev2-text-red-900' 
          : 'ev2-border-gray-300',
        className
      )}
      {...props}
    />
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
        {textareaElement}
      </TranslatableField>
    );
  }

  return (
    <div className="ev2-space-y-1">
      {label && (
        <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-text-sm ev2-font-medium ev2-text-gray-700">
          <span>{label}</span>
          {required && <span className="ev2-text-red-500">*</span>}
        </label>
      )}
      {textareaElement}
      {help && (
        <p className="ev2-text-xs ev2-text-gray-500">{help}</p>
      )}
      {error && (
        <p className="ev2-text-xs ev2-text-red-600">{error}</p>
      )}
    </div>
  );
}

export default TextareaField;