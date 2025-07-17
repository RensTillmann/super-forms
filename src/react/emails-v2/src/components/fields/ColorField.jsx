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
    <div className="ev2-space-y-1">
      {label && (
        <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-text-sm ev2-font-medium ev2-text-gray-700">
          <span>{label}</span>
          {required && <span className="ev2-text-red-500">*</span>}
        </label>
      )}
      
      <div className="ev2-relative">
        <div className="ev2-flex ev2-items-center ev2-gap-2">
          <button
            ref={buttonRef}
            type="button"
            onClick={() => setIsOpen(!isOpen)}
            className={clsx(
              'ev2-w-10 ev2-h-10 ev2-rounded ev2-border-2 ev2-border-gray-300 ev2-shadow-sm',
              'focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent',
              error && 'ev2-border-red-300',
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
              'ev2-flex-1 ev2-px-3 ev2-py-2 ev2-border ev2-rounded-md ev2-transition-colors',
              'focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent',
              error 
                ? 'ev2-border-red-300 ev2-text-red-900' 
                : 'ev2-border-gray-300'
            )}
          />
        </div>
        
        {isOpen && (
          <div
            ref={popoverRef}
            className="ev2-absolute ev2-z-50 ev2-mt-2 ev2-bg-white ev2-rounded-lg ev2-shadow-xl ev2-border ev2-border-gray-200 ev2-p-4"
          >
            <div className="ev2-space-y-3">
              {/* Native color picker */}
              <input
                ref={colorInputRef}
                type="color"
                value={tempColor}
                onChange={(e) => handleColorChange(e.target.value)}
                className="ev2-w-full ev2-h-10 ev2-cursor-pointer ev2-rounded"
              />
              
              {/* Preset colors */}
              <div className="ev2-grid ev2-grid-cols-5 ev2-gap-2">
                {presetColors.map((color) => (
                  <button
                    key={color}
                    type="button"
                    onClick={() => handleColorChange(color)}
                    className={clsx(
                      'ev2-w-8 ev2-h-8 ev2-rounded ev2-border-2 ev2-transition-all',
                      value === color 
                        ? 'ev2-border-primary-500 ev2-scale-110' 
                        : 'ev2-border-gray-300 hover:ev2-border-gray-400'
                    )}
                    style={{ backgroundColor: color }}
                    aria-label={`Select color ${color}`}
                  />
                ))}
              </div>
              
              {/* Current color display */}
              <div className="ev2-flex ev2-items-center ev2-justify-between ev2-p-2 ev2-bg-gray-50 ev2-rounded">
                <span className="ev2-text-sm ev2-text-gray-600">Current:</span>
                <div className="ev2-flex ev2-items-center ev2-gap-2">
                  <div 
                    className="ev2-w-6 ev2-h-6 ev2-rounded ev2-border ev2-border-gray-300"
                    style={{ backgroundColor: value || '#FFFFFF' }}
                  />
                  <span className="ev2-text-sm ev2-font-mono">{value || '#FFFFFF'}</span>
                </div>
              </div>
            </div>
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

export default ColorField;