import React, { useState } from 'react';
import clsx from 'clsx';
import TranslatableField from '../shared/TranslatableField';

function TextField({ 
  label, 
  value, 
  onChange, 
  placeholder, 
  help, 
  i18n = false,
  required = false,
  error,
  className,
  ...props 
}) {
  const [isFocused, setIsFocused] = useState(false);

  const inputElement = (
    <input
      type="text"
      value={value || ''}
      onChange={(e) => onChange(e.target.value)}
      onFocus={() => setIsFocused(true)}
      onBlur={() => setIsFocused(false)}
      placeholder={placeholder}
      className={clsx(
        'w-full px-3 py-2 border rounded-md transition-colors',
        'focus:ring-2 focus:ring-primary-500 focus:border-transparent',
        error 
          ? 'border-red-300 text-red-900' 
          : 'border-gray-300',
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
        {inputElement}
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
      {inputElement}
      {help && (
        <p className="text-xs text-gray-500">{help}</p>
      )}
      {error && (
        <p className="text-xs text-red-600">{error}</p>
      )}
    </div>
  );
}

export default TextField;