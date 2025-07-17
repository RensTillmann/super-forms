import React from 'react';
import clsx from 'clsx';

function ToggleControl({ 
  label, 
  checked, 
  onChange, 
  help,
  disabled = false,
  className
}) {
  return (
    <div className={clsx('ev2-space-y-2', className)}>
      <label className="ev2-flex ev2-items-center ev2-gap-3 ev2-cursor-pointer">
        <button
          type="button"
          role="switch"
          aria-checked={checked}
          disabled={disabled}
          onClick={() => onChange(!checked)}
          className={clsx(
            'ev2-relative ev2-inline-flex ev2-h-6 ev2-w-11 ev2-items-center ev2-rounded-full',
            'ev2-transition-colors ev2-duration-200 ev2-ease-in-out',
            'focus:ev2-outline-none focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-ring-offset-2',
            checked ? 'ev2-bg-primary-600' : 'ev2-bg-gray-200',
            disabled && 'ev2-opacity-50 ev2-cursor-not-allowed'
          )}
        >
          <span
            className={clsx(
              'ev2-inline-block ev2-h-5 ev2-w-5 ev2-transform ev2-rounded-full ev2-bg-white ev2-shadow-lg',
              'ev2-transition-transform ev2-duration-200 ev2-ease-in-out',
              checked ? 'ev2-translate-x-5' : 'ev2-translate-x-0.5'
            )}
          />
        </button>
        {label && (
          <span className={clsx(
            'ev2-text-sm ev2-font-medium',
            disabled ? 'ev2-text-gray-400' : 'ev2-text-gray-700'
          )}>
            {label}
          </span>
        )}
      </label>
      
      {help && (
        <p className="ev2-text-xs ev2-text-gray-500 ev2-ml-14">{help}</p>
      )}
    </div>
  );
}

export default ToggleControl;