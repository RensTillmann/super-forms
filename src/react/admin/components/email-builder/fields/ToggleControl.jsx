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
    <div className={clsx('space-y-2', className)}>
      <label className="flex items-center gap-3 cursor-pointer">
        <button
          type="button"
          role="switch"
          aria-checked={checked}
          disabled={disabled}
          onClick={() => onChange(!checked)}
          className={clsx(
            'relative inline-flex h-6 w-11 items-center rounded-full',
            'transition-colors duration-200 ease-in-out',
            'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
            checked ? 'bg-primary-600' : 'bg-gray-200',
            disabled && 'opacity-50 cursor-not-allowed'
          )}
        >
          <span
            className={clsx(
              'inline-block h-5 w-5 transform rounded-full bg-white shadow-lg',
              'transition-transform duration-200 ease-in-out',
              checked ? 'translate-x-5' : 'translate-x-0.5'
            )}
          />
        </button>
        {label && (
          <span className={clsx(
            'text-sm font-medium',
            disabled ? 'text-gray-400' : 'text-gray-700'
          )}>
            {label}
          </span>
        )}
      </label>
      
      {help && (
        <p className="text-xs text-gray-500 ml-14">{help}</p>
      )}
    </div>
  );
}

export default ToggleControl;