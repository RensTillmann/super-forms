import React from 'react';
import clsx from 'clsx';

function CheckboxField({ 
  label, 
  value, 
  onChange, 
  help,
  error,
  className,
  ...props 
}) {
  return (
    <div className="space-y-1">
      <label className="flex items-center gap-2 cursor-pointer">
        <input
          type="checkbox"
          checked={value || false}
          onChange={(e) => onChange(e.target.checked)}
          className={clsx(
            'w-4 h-4 text-primary-600 border-gray-300 rounded',
            'focus:ring-2 focus:ring-primary-500',
            error && 'border-red-300',
            className
          )}
          {...props}
        />
        <span className="text-sm font-medium text-gray-700">
          {label}
        </span>
      </label>
      {help && (
        <p className="text-xs text-gray-500 ml-6">{help}</p>
      )}
      {error && (
        <p className="text-xs text-red-600 ml-6">{error}</p>
      )}
    </div>
  );
}

export default CheckboxField;