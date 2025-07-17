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
    <div className={clsx('ev2-space-y-2', className)}>
      {label && (
        <label className="ev2-flex ev2-items-center ev2-gap-2 ev2-text-sm ev2-font-medium ev2-text-gray-700">
          <span>{label}</span>
          {required && <span className="ev2-text-red-500">*</span>}
        </label>
      )}
      
      <div className="ev2-relative">
        <button
          ref={buttonRef}
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          className={clsx(
            'ev2-w-full ev2-px-3 ev2-py-2 ev2-flex ev2-items-center ev2-gap-3',
            'ev2-bg-white ev2-border ev2-rounded-md ev2-shadow-sm',
            'hover:ev2-border-gray-400 ev2-transition-colors',
            'focus:ev2-ring-2 focus:ev2-ring-primary-500 focus:ev2-border-transparent',
            error ? 'ev2-border-red-300' : 'ev2-border-gray-300'
          )}
        >
          <div className="ev2-flex ev2-items-center ev2-gap-2 ev2-flex-1">
            <div 
              className="ev2-w-8 ev2-h-8 ev2-rounded ev2-border ev2-border-gray-300 ev2-shadow-inner ev2-relative ev2-overflow-hidden"
              style={{ backgroundColor: getDisplayColor() }}
            >
              {/* Transparency checkerboard pattern */}
              <div className="ev2-absolute ev2-inset-0 ev2-opacity-10">
                <svg width="8" height="8" className="ev2-w-full ev2-h-full">
                  <rect width="4" height="4" fill="#ccc" />
                  <rect x="4" y="4" width="4" height="4" fill="#ccc" />
                </svg>
              </div>
            </div>
            <span 
              className="ev2-font-mono ev2-text-sm"
              style={{ color: getContrastColor(getDisplayColor()) }}
            >
              {value || 'Select color'}
            </span>
          </div>
          <Palette className="ev2-w-4 ev2-h-4 ev2-text-gray-400" />
        </button>
        
        {isOpen && (
          <div
            ref={popoverRef}
            className="ev2-absolute ev2-z-50 ev2-mt-2"
            style={{ 
              right: '0',
              left: 'auto'
            }}
          >
            <div className="ev2-bg-white ev2-rounded-lg ev2-shadow-2xl ev2-border ev2-border-gray-200 ev2-overflow-hidden">
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
              <div className="ev2-border-t ev2-border-gray-200 ev2-p-2 ev2-flex ev2-gap-2">
                <button
                  type="button"
                  onClick={() => {
                    onChange('');
                    setIsOpen(false);
                  }}
                  className="ev2-flex-1 ev2-px-3 ev2-py-1.5 ev2-text-sm ev2-text-gray-600 ev2-bg-gray-100 ev2-rounded hover:ev2-bg-gray-200 ev2-transition-colors"
                >
                  Clear
                </button>
                <button
                  type="button"
                  onClick={() => setIsOpen(false)}
                  className="ev2-flex-1 ev2-px-3 ev2-py-1.5 ev2-text-sm ev2-text-white ev2-bg-primary-600 ev2-rounded hover:ev2-bg-primary-700 ev2-transition-colors"
                >
                  Done
                </button>
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

export default ColorPicker;