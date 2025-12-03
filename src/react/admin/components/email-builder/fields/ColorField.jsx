import React, { useState, useRef, useEffect } from 'react';
import clsx from 'clsx';

function ColorField({ 
  label, 
  value, 
  onChange, 
  help,
  required = false,
  error,
  className,
  presetColors = [
    '#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF',
    '#FFFF00', '#FF00FF', '#00FFFF', '#FFA500', '#800080',
    '#FFC0CB', '#A52A2A', '#808080', '#C0C0C0', '#FFD700'
  ]
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [tempColor, setTempColor] = useState(value || '#000000');
  const colorInputRef = useRef(null);
  const popoverRef = useRef(null);
  const buttonRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (popoverRef.current && !popoverRef.current.contains(event.target) &&
          buttonRef.current && !buttonRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  const handleColorChange = (color) => {
    setTempColor(color);
    onChange(color);
  };

  const handleInputChange = (e) => {
    const newColor = e.target.value;
    if (/^#[0-9A-F]{6}$/i.test(newColor) || newColor === '') {
      handleColorChange(newColor);
    }
  };

  const getContrastColor = (hexColor) => {
    // Convert hex to RGB
    const r = parseInt(hexColor.slice(1, 3), 16);
    const g = parseInt(hexColor.slice(3, 5), 16);
    const b = parseInt(hexColor.slice(5, 7), 16);
    
    // Calculate luminance
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
  };

  return (
    <div className="space-y-1">
      {label && (
        <label className="flex items-center gap-2 text-sm font-medium text-gray-700">
          <span>{label}</span>
          {required && <span className="text-red-500">*</span>}
        </label>
      )}
      
      <div className="relative">
        <div className="flex items-center gap-2">
          <button
            ref={buttonRef}
            type="button"
            onClick={() => setIsOpen(!isOpen)}
            className={clsx(
              'w-10 h-10 rounded border-2 border-gray-300 shadow-sm',
              'focus:ring-2 focus:ring-primary-500 focus:border-transparent',
              error && 'border-red-300',
              className
            )}
            style={{ backgroundColor: value || '#FFFFFF' }}
            aria-label="Choose color"
          />
          
          <input
            type="text"
            value={value || ''}
            onChange={handleInputChange}
            placeholder="#000000"
            className={clsx(
              'flex-1 px-3 py-2 border rounded-md transition-colors',
              'focus:ring-2 focus:ring-primary-500 focus:border-transparent',
              error 
                ? 'border-red-300 text-red-900' 
                : 'border-gray-300'
            )}
          />
        </div>
        
        {isOpen && (
          <div
            ref={popoverRef}
            className="absolute z-50 mt-2 bg-white rounded-lg shadow-xl border border-gray-200 p-4"
          >
            <div className="space-y-3">
              {/* Native color picker */}
              <input
                ref={colorInputRef}
                type="color"
                value={tempColor}
                onChange={(e) => handleColorChange(e.target.value)}
                className="w-full h-10 cursor-pointer rounded"
              />
              
              {/* Preset colors */}
              <div className="grid grid-cols-5 gap-2">
                {presetColors.map((color) => (
                  <button
                    key={color}
                    type="button"
                    onClick={() => handleColorChange(color)}
                    className={clsx(
                      'w-8 h-8 rounded border-2 transition-all',
                      value === color 
                        ? 'border-primary-500 scale-110' 
                        : 'border-gray-300 hover:border-gray-400'
                    )}
                    style={{ backgroundColor: color }}
                    aria-label={`Select color ${color}`}
                  />
                ))}
              </div>
              
              {/* Current color display */}
              <div className="flex items-center justify-between p-2 bg-gray-50 rounded">
                <span className="text-sm text-gray-600">Current:</span>
                <div className="flex items-center gap-2">
                  <div 
                    className="w-6 h-6 rounded border border-gray-300"
                    style={{ backgroundColor: value || '#FFFFFF' }}
                  />
                  <span className="text-sm font-mono">{value || '#FFFFFF'}</span>
                </div>
              </div>
            </div>
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

export default ColorField;