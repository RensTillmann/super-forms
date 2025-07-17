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
    <div className="ev2-space-y-1">
      <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-cursor-pointer">
        <input
          type="checkbox"
          checked={value || false}
          onChange={(e) => onChange(e.target.checked)}
          className={clsx(
            'ev2-w-4 ev2-h-4 ev2-text-primary-600 ev2-border-gray-300 ev2-rounded',
            'focus:ev2-ring-2 focus:ev2-ring-primary-500',
            error && 'ev2-border-red-300',
            className
          )}
          {...props}
        />
        <span className="ev2-text-sm ev2-font-medium ev2-text-gray-700">
          {label}
        </span>
      </label>
      {help && (
        <p className="ev2-text-xs ev2-text-gray-500 ev2-ml-6">{help}</p>
      )}
      {error && (
        <p className="ev2-text-xs ev2-text-red-600 ev2-ml-6">{error}</p>
      )}
    </div>
  );
}

export default CheckboxField;