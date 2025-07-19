import React, { useEffect, useRef } from 'react';
import { Bold, Italic, Underline, AlignLeft, AlignCenter, AlignRight, Type, Palette } from 'lucide-react';
import clsx from 'clsx';

/**
 * Floating toolbar for inline text editing
 * Appears above selected text with formatting options
 */
function FloatingToolbar({ 
  position, 
  onFormatText, 
  onAlignText, 
  onFontSize, 
  onTextColor,
  currentAlign = 'left',
  currentFontSize = 16,
  currentColor = '#000000',
  isVisible = false 
}) {
  const toolbarRef = useRef(null);

  // Position the toolbar
  useEffect(() => {
    if (isVisible && position && toolbarRef.current) {
      const toolbar = toolbarRef.current;
      toolbar.style.left = `${position.x}px`;
      toolbar.style.top = `${position.y - 50}px`; // Position above the selection
    }
  }, [position, isVisible]);

  if (!isVisible) return null;

  const formatButtons = [
    { icon: Bold, command: 'bold', title: 'Bold (Ctrl+B)' },
    { icon: Italic, command: 'italic', title: 'Italic (Ctrl+I)' },
    { icon: Underline, command: 'underline', title: 'Underline (Ctrl+U)' },
  ];

  const alignButtons = [
    { icon: AlignLeft, value: 'left', title: 'Align Left' },
    { icon: AlignCenter, value: 'center', title: 'Align Center' },
    { icon: AlignRight, value: 'right', title: 'Align Right' },
  ];

  const fontSizes = [12, 14, 16, 18, 20, 24, 28, 32, 36];

  return (
    <div
      ref={toolbarRef}
      className="ev2-fixed ev2-z-50 ev2-bg-white ev2-shadow-lg ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-p-2 ev2-flex ev2-items-center ev2-gap-1"
      style={{ 
        position: 'fixed',
        transform: 'translateX(-50%)', // Center the toolbar
      }}
    >
      {/* Format buttons */}
      <div className="ev2-flex ev2-border-r ev2-border-gray-200 ev2-pr-2 ev2-mr-2">
        {formatButtons.map(({ icon: Icon, command, title }) => (
          <button
            key={command}
            type="button"
            onClick={() => onFormatText(command)}
            className="ev2-p-2 ev2-rounded hover:ev2-bg-gray-100 ev2-transition-colors"
            title={title}
          >
            <Icon className="ev2-w-4 ev2-h-4 ev2-text-gray-700" />
          </button>
        ))}
      </div>

      {/* Font size */}
      <div className="ev2-flex ev2-items-center ev2-border-r ev2-border-gray-200 ev2-pr-2 ev2-mr-2">
        <Type className="ev2-w-4 ev2-h-4 ev2-text-gray-500 ev2-mr-1" />
        <select
          value={currentFontSize}
          onChange={(e) => onFontSize(parseInt(e.target.value))}
          className="ev2-text-sm ev2-border ev2-border-gray-300 ev2-rounded ev2-px-2 ev2-py-1 ev2-min-w-[60px]"
        >
          {fontSizes.map(size => (
            <option key={size} value={size}>{size}px</option>
          ))}
        </select>
      </div>

      {/* Text color */}
      <div className="ev2-flex ev2-items-center ev2-border-r ev2-border-gray-200 ev2-pr-2 ev2-mr-2">
        <Palette className="ev2-w-4 ev2-h-4 ev2-text-gray-500 ev2-mr-1" />
        <input
          type="color"
          value={currentColor}
          onChange={(e) => onTextColor(e.target.value)}
          className="ev2-w-8 ev2-h-8 ev2-border ev2-border-gray-300 ev2-rounded ev2-cursor-pointer"
          title="Text Color"
        />
      </div>

      {/* Alignment buttons */}
      <div className="ev2-flex">
        {alignButtons.map(({ icon: Icon, value, title }) => (
          <button
            key={value}
            type="button"
            onClick={() => onAlignText(value)}
            className={clsx(
              'ev2-p-2 ev2-rounded ev2-transition-colors',
              currentAlign === value 
                ? 'ev2-bg-blue-100 ev2-text-blue-700' 
                : 'hover:ev2-bg-gray-100 ev2-text-gray-700'
            )}
            title={title}
          >
            <Icon className="ev2-w-4 ev2-h-4" />
          </button>
        ))}
      </div>
    </div>
  );
}

export default FloatingToolbar;