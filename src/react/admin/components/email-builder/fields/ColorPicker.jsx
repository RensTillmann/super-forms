import React, { useState, useRef, useEffect } from 'react';
import { ChromePicker } from 'react-color';
import clsx from 'clsx';
import { Palette } from 'lucide-react';

function ColorPicker({ 
  label, 
  value, 
  onChange, 
  help,
  required = false,
  error,
  className,
  enableAlpha = false,
  presetColors = [
    '#000000', '#FFFFFF', '#FF6900', '#FCB900', '#7BDCB5',
    '#00D084', '#8ED1FC', '#0693E3', '#ABB8C3', '#EB144C',
    '#F78DA7', '#9900EF', '#4A5568', '#718096', '#A0AEC0'
  ]
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [tempColor, setTempColor] = useState(value || '#000000');
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
    const colorValue = enableAlpha 
      ? `rgba(${color.rgb.r}, ${color.rgb.g}, ${color.rgb.b}, ${color.rgb.a})`
      : color.hex;
    setTempColor(colorValue);
    onChange(colorValue);
  };

  const getDisplayColor = () => {
    if (!value) return '#FFFFFF';
    return value;
  };

  const getContrastColor = (color) => {
    // Handle rgba colors
    if (color.startsWith('rgba')) {
      const matches = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
      if (matches) {
        const r = parseInt(matches[1]);
        const g = parseInt(matches[2]);
        const b = parseInt(matches[3]);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance > 0.5 ? '#000000' : '#FFFFFF';
      }
    }
    
    // Handle hex colors
    if (color.startsWith('#')) {
      const r = parseInt(color.slice(1, 3), 16);
      const g = parseInt(color.slice(3, 5), 16);
      const b = parseInt(color.slice(5, 7), 16);
      const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
      return luminance > 0.5 ? '#000000' : '#FFFFFF';
    }
    
    return '#000000';
  };

  return (
    <div className={clsx('space-y-2', className)}>
      {label && (
        <label className="flex items-center gap-2 text-sm font-medium text-gray-700">
          <span>{label}</span>
          {required && <span className="text-red-500">*</span>}
        </label>
      )}
      
      <div className="relative">
        <button
          ref={buttonRef}
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          className={clsx(
            'w-full px-3 py-2 flex items-center gap-3',
            'bg-white border rounded-md shadow-sm',
            'hover:border-gray-400 transition-colors',
            'focus:ring-2 focus:ring-primary-500 focus:border-transparent',
            error ? 'border-red-300' : 'border-gray-300'
          )}
        >
          <div className="flex items-center gap-2 flex-1">
            <div 
              className="w-8 h-8 rounded border border-gray-300 shadow-inner relative overflow-hidden"
              style={{ backgroundColor: getDisplayColor() }}
            >
              {/* Transparency checkerboard pattern */}
              <div className="absolute inset-0 opacity-10">
                <svg width="8" height="8" className="w-full h-full">
                  <rect width="4" height="4" fill="#ccc" />
                  <rect x="4" y="4" width="4" height="4" fill="#ccc" />
                </svg>
              </div>
            </div>
            <span 
              className="font-mono text-sm"
              style={{ color: getContrastColor(getDisplayColor()) }}
            >
              {value || 'Select color'}
            </span>
          </div>
          <Palette className="w-4 h-4 text-gray-400" />
        </button>
        
        {isOpen && (
          <div
            ref={popoverRef}
            className="absolute z-50 mt-2"
            style={{ 
              right: '0',
              left: 'auto'
            }}
          >
            <div className="bg-white rounded-lg shadow-2xl border border-gray-200 overflow-hidden">
              <ChromePicker
                color={tempColor}
                onChange={handleColorChange}
                disableAlpha={!enableAlpha}
                presetColors={presetColors}
                styles={{
                  default: {
                    picker: {
                      boxShadow: 'none',
                      border: 'none',
                      borderRadius: '0',
                      fontFamily: 'inherit'
                    }
                  }
                }}
              />
              
              {/* Quick actions */}
              <div className="border-t border-gray-200 p-2 flex gap-2">
                <button
                  type="button"
                  onClick={() => {
                    onChange('');
                    setIsOpen(false);
                  }}
                  className="flex-1 px-3 py-1.5 text-sm text-gray-600 bg-gray-100 rounded hover:bg-gray-200 transition-colors"
                >
                  Clear
                </button>
                <button
                  type="button"
                  onClick={() => setIsOpen(false)}
                  className="flex-1 px-3 py-1.5 text-sm text-white bg-primary-600 rounded hover:bg-primary-700 transition-colors"
                >
                  Done
                </button>
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

export default ColorPicker;