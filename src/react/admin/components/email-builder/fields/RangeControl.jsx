import React from 'react';
import clsx from 'clsx';

function RangeControl({ 
  label, 
  value, 
  onChange, 
  min = 0, 
  max = 100, 
  step = 1,
  help,
  showValue = true,
  withInputField = true,
  unit = '',
  marks = [],
  required = false,
  error,
  className
}) {
  const handleSliderChange = (e) => {
    onChange(parseFloat(e.target.value));
  };

  const handleInputChange = (e) => {
    const newValue = parseFloat(e.target.value);
    if (!isNaN(newValue) && newValue >= min && newValue <= max) {
      onChange(newValue);
    }
  };

  const percentage = ((value - min) / (max - min)) * 100;

  return (
    <div className={clsx('space-y-2', className)}>
      {label && (
        <div className="flex items-center justify-between">
          <label className="flex items-center gap-2 text-sm font-medium text-gray-700">
            <span>{label}</span>
            {required && <span className="text-red-500">*</span>}
          </label>
          {showValue && !withInputField && (
            <span className="text-sm font-medium text-gray-900">
              {value}{unit}
            </span>
          )}
        </div>
      )}
      
      <div className="flex items-center gap-3">
        <div className="flex-1 relative">
          {/* Track background */}
          <div className="absolute inset-0 flex items-center">
            <div className="w-full h-2 bg-gray-200 rounded-full" />
          </div>
          
          {/* Filled track */}
          <div className="absolute inset-0 flex items-center">
            <div 
              className="h-2 bg-primary-500 rounded-full transition-all"
              style={{ width: `${percentage}%` }}
            />
          </div>
          
          {/* Marks */}
          {marks.length > 0 && (
            <div className="absolute inset-0 flex items-center">
              {marks.map((mark) => {
                const markPercentage = ((mark.value - min) / (max - min)) * 100;
                return (
                  <div
                    key={mark.value}
                    className="absolute w-0.5 h-3 bg-gray-400"
                    style={{ left: `${markPercentage}%` }}
                  >
                    {mark.label && (
                      <span className="absolute top-5 left-1/2 -translate-x-1/2 text-xs text-gray-500 whitespace-nowrap">
                        {mark.label}
                      </span>
                    )}
                  </div>
                );
              })}
            </div>
          )}
          
          {/* Slider input */}
          <input
            type="range"
            min={min}
            max={max}
            step={step}
            value={value}
            onChange={handleSliderChange}
            className={clsx(
              'relative w-full h-2 appearance-none bg-transparent cursor-pointer',
              '[&::-webkit-slider-thumb]:appearance-none',
              '[&::-webkit-slider-thumb]:w-5',
              '[&::-webkit-slider-thumb]:h-5',
              '[&::-webkit-slider-thumb]:bg-white',
              '[&::-webkit-slider-thumb]:border-2',
              '[&::-webkit-slider-thumb]:border-primary-500',
              '[&::-webkit-slider-thumb]:rounded-full',
              '[&::-webkit-slider-thumb]:shadow-md',
              '[&::-webkit-slider-thumb]:transition-all',
              '[&::-webkit-slider-thumb]:hover:scale-110',
              '[&::-webkit-slider-thumb]:hover:shadow-lg',
              '[&::-moz-range-thumb]:appearance-none',
              '[&::-moz-range-thumb]:w-5',
              '[&::-moz-range-thumb]:h-5',
              '[&::-moz-range-thumb]:bg-white',
              '[&::-moz-range-thumb]:border-2',
              '[&::-moz-range-thumb]:border-primary-500',
              '[&::-moz-range-thumb]:rounded-full',
              '[&::-moz-range-thumb]:shadow-md',
              '[&::-moz-range-thumb]:transition-all',
              '[&::-moz-range-thumb]:hover:scale-110',
              '[&::-moz-range-thumb]:hover:shadow-lg',
              '[&:focus-visible::-webkit-slider-thumb]:ring-2',
              '[&:focus-visible::-webkit-slider-thumb]:ring-primary-500',
              '[&:focus-visible::-webkit-slider-thumb]:ring-offset-2',
              '[&:focus-visible::-moz-range-thumb]:ring-2',
              '[&:focus-visible::-moz-range-thumb]:ring-primary-500',
              '[&:focus-visible::-moz-range-thumb]:ring-offset-2',
              error && 'accent-red-500'
            )}
          />
        </div>
        
        {withInputField && (
          <div className="flex items-center gap-1">
            <input
              type="number"
              min={min}
              max={max}
              step={step}
              value={value}
              onChange={handleInputChange}
              className={clsx(
                'w-16 px-2 py-1 text-sm text-center border rounded-md transition-colors',
                'focus:ring-2 focus:ring-primary-500 focus:border-transparent',
                error 
                  ? 'border-red-300 text-red-900' 
                  : 'border-gray-300'
              )}
            />
            {unit && (
              <span className="text-sm text-gray-500">{unit}</span>
            )}
          </div>
        )}
      </div>
      
      {help && (
        <p className="text-xs text-gray-500">{help}</p>
      )}
      {error && (
        <p className="text-xs text-red-600">{error}</p>
      )}
    </div>
  );
}

export default RangeControl;