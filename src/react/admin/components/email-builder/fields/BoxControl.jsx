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
    <div className={clsx('space-y-2', className)}>
      {label && (
        <div className="flex items-center justify-between">
          <label className="text-sm font-medium text-gray-700">
            {label}
          </label>
          <button
            type="button"
            onClick={() => setIsLinked(!isLinked)}
            className={clsx(
              'p-1.5 rounded transition-colors',
              isLinked 
                ? 'bg-primary-100 text-primary-600 hover:bg-primary-200' 
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            )}
            title={isLinked ? 'Unlink values' : 'Link values'}
          >
            {isLinked ? (
              <Link className="w-3.5 h-3.5" />
            ) : (
              <Unlink className="w-3.5 h-3.5" />
            )}
          </button>
        </div>
      )}
      
      {isLinked ? (
        // Linked mode - single input
        <div className="flex items-center gap-2">
          <div className="flex items-center justify-center w-8 h-8 bg-gray-100 rounded text-xs text-gray-600">
            All
          </div>
          <input
            type="number"
            min={min}
            max={max}
            step={step}
            value={values.top}
            onChange={(e) => handleValueChange('top', e.target.value)}
            className="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
          <span className="text-sm text-gray-500 w-6">{unit}</span>
        </div>
      ) : (
        // Unlinked mode - individual inputs
        <div className="space-y-2">
          {sides.map(({ key, label, icon }) => (
            <div key={key} className="flex items-center gap-2">
              <div className="flex items-center justify-center w-8 h-8 bg-gray-100 rounded text-xs text-gray-600" title={label}>
                {icon}
              </div>
              <input
                type="number"
                min={min}
                max={max}
                step={step}
                value={values[key]}
                onChange={(e) => handleValueChange(key, e.target.value)}
                className="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder={label}
              />
              <span className="text-sm text-gray-500 w-6">{unit}</span>
            </div>
          ))}
        </div>
      )}
      
      {help && (
        <p className="text-xs text-gray-500">{help}</p>
      )}
    </div>
  );
}

export default BoxControl;