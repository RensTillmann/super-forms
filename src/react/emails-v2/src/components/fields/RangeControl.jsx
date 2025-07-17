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
    <div className={clsx('ev2-space-y-2', className)}>
      {label && (
        <div className="ev2-flex ev2-items-center ev2-justify-between">
          <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-text-sm ev2-font-medium ev2-text-gray-700">
            <span>{label}</span>
            {required && <span className="ev2-text-red-500">*</span>}
          </label>
          {showValue && !withInputField && (
            <span className="ev2-text-sm ev2-font-medium ev2-text-gray-900">
              {value}{unit}
            </span>
          )}
        </div>
      )}
      
      <div className="ev2-flex ev2-items-center ev2-gap-3">
        <div className="ev2-flex-1 ev2-relative">
          {/* Track background */}
          <div className="ev2-absolute ev2-inset-0 ev2-flex ev2-items-center">
            <div className="ev2-w-full ev2-h-2 ev2-bg-gray-200 ev2-rounded-full" />
          </div>
          
          {/* Filled track */}
          <div className="ev2-absolute ev2-inset-0 ev2-flex ev2-items-center">
            <div 
              className="ev2-h-2 ev2-bg-primary-500 ev2-rounded-full ev2-transition-all"
              style={{ width: `${percentage}%` }}
            />
          </div>
          
          {/* Marks */}
          {marks.length > 0 && (
            <div className="ev2-absolute ev2-inset-0 ev2-flex ev2-items-center">
              {marks.map((mark) => {
                const markPercentage = ((mark.value - min) / (max - min)) * 100;
                return (
                  <div
                    key={mark.value}
                    className="ev2-absolute ev2-w-0.5 ev2-h-3 ev2-bg-gray-400"
                    style={{ left: `${markPercentage}%` }}
                  >
                    {mark.label && (
                      <span className="ev2-absolute ev2-top-5 ev2-left-1/2 -ev2-translate-x-1/2 ev2-text-xs ev2-text-gray-500 ev2-whitespace-nowrap">
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
              'ev2-relative ev2-w-full ev2-h-2 ev2-appearance-none ev2-bg-transparent ev2-cursor-pointer',
              '[&::-webkit-slider-thumb]:ev2-appearance-none',
              '[&::-webkit-slider-thumb]:ev2-w-5',
              '[&::-webkit-slider-thumb]:ev2-h-5',
              '[&::-webkit-slider-thumb]:ev2-bg-white',
              '[&::-webkit-slider-thumb]:ev2-border-2',
              '[&::-webkit-slider-thumb]:ev2-border-primary-500',
              '[&::-webkit-slider-thumb]:ev2-rounded-full',
              '[&::-webkit-slider-thumb]:ev2-shadow-md',
              '[&::-webkit-slider-thumb]:ev2-transition-all',
              '[&::-webkit-slider-thumb]:hover:ev2-scale-110',
              '[&::-webkit-slider-thumb]:hover:ev2-shadow-lg',
              '[&::-moz-range-thumb]:ev2-appearance-none',
              '[&::-moz-range-thumb]:ev2-w-5',
              '[&::-moz-range-thumb]:ev2-h-5',
              '[&::-moz-range-thumb]:ev2-bg-white',
              '[&::-moz-range-thumb]:ev2-border-2',
              '[&::-moz-range-thumb]:ev2-border-primary-500',
              '[&::-moz-range-thumb]:ev2-rounded-full',
              '[&::-moz-range-thumb]:ev2-shadow-md',
              '[&::-moz-range-thumb]:ev2-transition-all',
              '[&::-moz-range-thumb]:hover:ev2-scale-110',
              '[&::-moz-range-thumb]:hover:ev2-shadow-lg',
              '[&:focus-visible::-webkit-slider-thumb]:ev2-ring-2',
              '[&:focus-visible::-webkit-slider-thumb]:ev2-ring-primary-500',
              '[&:focus-visible::-webkit-slider-thumb]:ev2-ring-offset-2',
              '[&:focus-visible::-moz-range-thumb]:ev2-ring-2',
              '[&:focus-visible::-moz-range-thumb]:ev2-ring-primary-500',
              '[&:focus-visible::-moz-range-thumb]:ev2-ring-offset-2',
              error && 'ev2-accent-red-500'
            )}
          />
        </div>
        
        {withInputField && (
          <div className="ev2-flex ev2-items-center ev2-gap-1">
            <input
              type="number"
              min={min}
              max={max}
              step={step}
              value={value}
              onChange={handleInputChange}
              className={clsx(
                'ev2-w-16 ev2-px-2 ev2-py-1 ev2-text-sm ev2-text-center ev2-border ev2-rounded-md ev2-transition-colors',
                'focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent',
                error 
                  ? 'ev2-border-red-300 ev2-text-red-900' 
                  : 'ev2-border-gray-300'
              )}
            />
            {unit && (
              <span className="ev2-text-sm ev2-text-gray-500">{unit}</span>
            )}
          </div>
        )}
      </div>
      
      {help && (
        <p className="ev2-text-xs ev2-text-gray-500">{help}</p>
      )}
      {error && (
        <p className="ev2-text-xs ev2-text-red-600">{error}</p>
      )}
    </div>
  );
}

export default RangeControl;