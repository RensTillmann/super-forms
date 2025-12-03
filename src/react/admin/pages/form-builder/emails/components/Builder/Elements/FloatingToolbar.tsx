import { useEffect, useRef } from 'react';
import { Bold, Italic, Underline, Link, AlignLeft, AlignCenter, AlignRight, Type, Palette, Trash2, ChevronUp, ChevronDown, GripVertical, Save } from 'lucide-react';
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
  onDelete,
  onMoveUp,
  onMoveDown,
  onStartDrag,
  onSave,
  currentAlign = 'left',
  currentFontSize = 16,
  currentColor = '#000000',
  isVisible = false,
  showElementControls = false
}) {
  const toolbarRef = useRef(null);

  // Position the toolbar
  useEffect(() => {
    if (isVisible && position && toolbarRef.current) {
      const toolbar = toolbarRef.current;
      toolbar.style.left = `${position.x}px`;
      toolbar.style.top = `${position.y}px`; // Use the exact position provided
    }
  }, [position, isVisible]);

  if (!isVisible) return null;

  const formatButtons = [
    { icon: Bold, command: 'bold', title: 'Bold (Ctrl+B)' },
    { icon: Italic, command: 'italic', title: 'Italic (Ctrl+I)' },
    { icon: Underline, command: 'underline', title: 'Underline (Ctrl+U)' },
    { icon: Link, command: 'createLink', title: 'Add Link (Ctrl+K)' },
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
      className="fixed z-50 bg-white shadow-lg border border-gray-200 rounded-lg p-2 flex items-center gap-1 pointer-events-auto"
      style={{ 
        position: 'fixed',
        transform: 'translateX(-50%)', // Center the toolbar
      }}
    >
      {/* Element controls (when text is being edited) - On the left side */}
      {showElementControls && (
        <>
          {/* Save Button */}
          <button
            type="button"
            onMouseDown={(e) => {
              e.preventDefault();
              e.stopPropagation();
            }}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onSave && onSave();
            }}
            className="p-2 rounded bg-green-500 hover:bg-green-600 text-white"
            title="Save and exit"
          >
            <Save className="w-4 h-4" />
          </button>
          
          {/* Drag Handle */}
          <button
            type="button"
            onMouseDown={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onStartDrag && onStartDrag(e);
            }}
            className="p-2 rounded hover:bg-gray-100 cursor-move"
            title="Drag to move"
          >
            <GripVertical className="w-4 h-4 text-gray-700" />
          </button>
          
          {/* Move Up Button */}
          <button
            type="button"
            onMouseDown={(e) => {
              e.preventDefault();
              e.stopPropagation();
            }}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onMoveUp && onMoveUp();
            }}
            className="p-2 rounded hover:bg-gray-100"
            title="Move Up"
          >
            <ChevronUp className="w-4 h-4 text-gray-700" />
          </button>
          
          {/* Move Down Button */}
          <button
            type="button"
            onMouseDown={(e) => {
              e.preventDefault();
              e.stopPropagation();
            }}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onMoveDown && onMoveDown();
            }}
            className="p-2 rounded hover:bg-gray-100"
            title="Move Down"
          >
            <ChevronDown className="w-4 h-4 text-gray-700" />
          </button>
          
          <div className="w-px h-6 bg-gray-300 mx-2" />
        </>
      )}

      {/* Format buttons */}
      <div className="flex border-r border-gray-200 pr-2 mr-2">
        {formatButtons.map(({ icon: Icon, command, title }) => (
          <button
            key={command}
            type="button"
            onMouseDown={(e) => {
              e.preventDefault(); // Prevent focus loss
              e.stopPropagation();
            }}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onFormatText(command);
            }}
            className="p-2 rounded hover:bg-gray-100"
            title={title}
          >
            <Icon className="w-4 h-4 text-gray-700" />
          </button>
        ))}
      </div>

      {/* Font size */}
      <div className="flex items-center border-r border-gray-200 pr-2 mr-2">
        <Type className="w-4 h-4 text-gray-500 mr-1" />
        <select
          value={currentFontSize}
          onChange={(e) => onFontSize(parseInt(e.target.value))}
          className="text-sm border border-gray-300 rounded px-2 py-1 min-w-[60px]"
        >
          {fontSizes.map(size => (
            <option key={size} value={size}>{size}px</option>
          ))}
        </select>
      </div>

      {/* Text color */}
      <div className="flex items-center border-r border-gray-200 pr-2 mr-2">
        <Palette className="w-4 h-4 text-gray-500 mr-1" />
        <input
          type="color"
          value={currentColor}
          onChange={(e) => onTextColor(e.target.value)}
          className="w-8 h-8 border border-gray-300 rounded cursor-pointer"
          title="Text Color"
        />
      </div>

      {/* Alignment buttons */}
      <div className="flex">
        {alignButtons.map(({ icon: Icon, value, title }) => (
          <button
            key={value}
            type="button"
            onClick={() => onAlignText(value)}
            className={clsx(
              'p-2 rounded transition-colors',
              currentAlign === value 
                ? 'bg-blue-100 text-blue-700' 
                : 'hover:bg-gray-100 text-gray-700'
            )}
            title={title}
          >
            <Icon className="w-4 h-4" />
          </button>
        ))}
      </div>

      {/* Delete Button - On the right side */}
      {showElementControls && (
        <>
          <div className="w-px h-6 bg-gray-300 mx-2" />
          <button
            type="button"
            onMouseDown={(e) => {
              e.preventDefault();
              e.stopPropagation();
            }}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onDelete && onDelete();
            }}
            className="p-2 rounded hover:bg-red-100"
            title="Delete Element"
          >
            <Trash2 className="w-4 h-4 text-red-600" />
          </button>
        </>
      )}
    </div>
  );
}

export default FloatingToolbar;