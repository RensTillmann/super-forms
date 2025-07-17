import React, { useState } from 'react';
import clsx from 'clsx';
import { Link, Unlink } from 'lucide-react';

function BoxControl({ 
  label, 
  values = { top: 0, right: 0, bottom: 0, left: 0 }, 
  onChange,
  min = 0,
  max = 100,
  step = 1,
  unit = 'px',
  help,
  className
}) {
  const [isLinked, setIsLinked] = useState(
    values.top === values.right && 
    values.top === values.bottom && 
    values.top === values.left
  );

  const handleValueChange = (side, value) => {
    const numValue = parseInt(value) || 0;
    
    if (isLinked) {
      onChange({
        top: numValue,
        right: numValue,
        bottom: numValue,
        left: numValue
      });
    } else {
      onChange({
        ...values,
        [side]: numValue
      });
    }
  };

  const sides = [
    { key: 'top', label: 'Top', icon: '↑' },
    { key: 'right', label: 'Right', icon: '→' },
    { key: 'bottom', label: 'Bottom', icon: '↓' },
    { key: 'left', label: 'Left', icon: '←' }
  ];

  return (
    <div className={clsx('ev2-space-y-2', className)}>
      {label && (
        <div className="ev2-flex ev2-items-center ev2-justify-between">
          <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700">
            {label}
          </label>
          <button
            type="button"
            onClick={() => setIsLinked(!isLinked)}
            className={clsx(
              'ev2-p-1.5 ev2-rounded ev2-transition-colors',
              isLinked 
                ? 'ev2-bg-primary-100 ev2-text-primary-600 hover:ev2-bg-primary-200' 
                : 'ev2-bg-gray-100 ev2-text-gray-600 hover:ev2-bg-gray-200'
            )}
            title={isLinked ? 'Unlink values' : 'Link values'}
          >
            {isLinked ? (
              <Link className="ev2-w-3.5 ev2-h-3.5" />
            ) : (
              <Unlink className="ev2-w-3.5 ev2-h-3.5" />
            )}
          </button>
        </div>
      )}
      
      {isLinked ? (
        // Linked mode - single input
        <div className="ev2-flex ev2-items-center ev2-gap-2">
          <div className="ev2-flex ev2-items-center ev2-justify-center ev2-w-8 ev2-h-8 ev2-bg-gray-100 ev2-rounded ev2-text-xs ev2-text-gray-600">
            All
          </div>
          <input
            type="number"
            min={min}
            max={max}
            step={step}
            value={values.top}
            onChange={(e) => handleValueChange('top', e.target.value)}
            className="ev2-flex-1 ev2-px-3 ev2-py-1.5 ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent"
          />
          <span className="ev2-text-sm ev2-text-gray-500 ev2-w-6">{unit}</span>
        </div>
      ) : (
        // Unlinked mode - individual inputs
        <div className="ev2-space-y-2">
          {sides.map(({ key, label, icon }) => (
            <div key={key} className="ev2-flex ev2-items-center ev2-gap-2">
              <div className="ev2-flex ev2-items-center ev2-justify-center ev2-w-8 ev2-h-8 ev2-bg-gray-100 ev2-rounded ev2-text-xs ev2-text-gray-600" title={label}>
                {icon}
              </div>
              <input
                type="number"
                min={min}
                max={max}
                step={step}
                value={values[key]}
                onChange={(e) => handleValueChange(key, e.target.value)}
                className="ev2-flex-1 ev2-px-3 ev2-py-1.5 ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded-md focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent"
                placeholder={label}
              />
              <span className="ev2-text-sm ev2-text-gray-500 ev2-w-6">{unit}</span>
            </div>
          ))}
        </div>
      )}
      
      {help && (
        <p className="ev2-text-xs ev2-text-gray-500">{help}</p>
      )}
    </div>
  );
}

export default BoxControl;