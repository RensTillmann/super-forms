import React, { useEffect, useRef, useState } from 'react';
import { 
  Bold, Italic, Underline, Link, Type, 
  AlignLeft, AlignCenter, AlignRight, List,
  Palette, Highlighter
} from 'lucide-react';
import { FloatingToolbarProps, ToolbarItem } from '../types/overlay.types';

const defaultTools: ToolbarItem[] = [
  { id: 'bold', label: 'Bold', icon: <Bold size={16} />, action: 'bold' },
  { id: 'italic', label: 'Italic', icon: <Italic size={16} />, action: 'italic' },
  { id: 'underline', label: 'Underline', icon: <Underline size={16} />, action: 'underline' },
  { id: 'divider1', label: '', icon: <span />, action: '' },
  { id: 'align-left', label: 'Align Left', icon: <AlignLeft size={16} />, action: 'align-left' },
  { id: 'align-center', label: 'Align Center', icon: <AlignCenter size={16} />, action: 'align-center' },
  { id: 'align-right', label: 'Align Right', icon: <AlignRight size={16} />, action: 'align-right' },
  { id: 'divider2', label: '', icon: <span />, action: '' },
  { id: 'link', label: 'Link', icon: <Link size={16} />, action: 'link' },
  { id: 'color', label: 'Text Color', icon: <Palette size={16} />, action: 'color' },
  { id: 'highlight', label: 'Highlight', icon: <Highlighter size={16} />, action: 'highlight' }
];

export const FloatingToolbar: React.FC<FloatingToolbarProps> = ({
  position,
  onClose,
  onFormat,
  selectedText = '',
  tools = defaultTools
}) => {
  const toolbarRef = useRef<HTMLDivElement>(null);
  const [adjustedPosition, setAdjustedPosition] = useState(position);
  const [activeTools, setActiveTools] = useState<Set<string>>(new Set());
  const [showColorPicker, setShowColorPicker] = useState(false);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (toolbarRef.current && !toolbarRef.current.contains(e.target as Node)) {
        onClose();
      }
    };

    const handleScroll = () => onClose();
    const handleResize = () => onClose();

    // Adjust position if toolbar would go off-screen
    if (toolbarRef.current) {
      const rect = toolbarRef.current.getBoundingClientRect();
      const padding = 10;
      const newPosition = { ...position };

      // Adjust horizontal position
      if (position.x + rect.width > window.innerWidth - padding) {
        newPosition.x = window.innerWidth - rect.width - padding;
      }
      if (position.x < padding) {
        newPosition.x = padding;
      }

      // Position above the selection
      newPosition.y = position.y - rect.height - 10;
      if (newPosition.y < padding) {
        // If not enough space above, position below
        newPosition.y = position.y + 30;
      }

      setAdjustedPosition(newPosition);
    }

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('scroll', handleScroll, true);
    window.addEventListener('resize', handleResize);

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('scroll', handleScroll, true);
      window.removeEventListener('resize', handleResize);
    };
  }, [position, onClose]);

  const handleToolClick = (tool: ToolbarItem) => {
    if (tool.id.startsWith('divider')) return;
    
    if (tool.action === 'color') {
      setShowColorPicker(!showColorPicker);
      return;
    }

    if (onFormat) {
      onFormat(tool.action);
    }

    // Toggle active state for formatting tools
    if (['bold', 'italic', 'underline'].includes(tool.action)) {
      const newActiveTools = new Set(activeTools);
      if (newActiveTools.has(tool.id)) {
        newActiveTools.delete(tool.id);
      } else {
        newActiveTools.add(tool.id);
      }
      setActiveTools(newActiveTools);
    }
  };

  const handleColorSelect = (color: string) => {
    if (onFormat) {
      onFormat(`color:${color}`);
    }
    setShowColorPicker(false);
  };

  return (
    <div
      ref={toolbarRef}
      className="floating-toolbar"
      style={{ 
        left: adjustedPosition.x, 
        top: adjustedPosition.y,
        opacity: toolbarRef.current ? 1 : 0
      }}
      role="toolbar"
      aria-label="Text formatting toolbar"
    >
      <div className="floating-toolbar-inner">
        {tools.map((tool) => {
          if (tool.id.startsWith('divider')) {
            return <div key={tool.id} className="toolbar-divider" />;
          }

          return (
            <button
              key={tool.id}
              className={`toolbar-btn ${activeTools.has(tool.id) ? 'toolbar-btn-active' : ''} 
                         ${tool.disabled ? 'toolbar-btn-disabled' : ''}`}
              onClick={() => handleToolClick(tool)}
              disabled={tool.disabled}
              title={tool.label}
              aria-label={tool.label}
              aria-pressed={activeTools.has(tool.id)}
            >
              {tool.icon}
            </button>
          );
        })}
      </div>

      {showColorPicker && (
        <div className="floating-color-picker">
          <div className="color-picker-grid">
            {['#000000', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF', '#FFFFFF'].map((color) => (
              <button
                key={color}
                className="color-picker-swatch"
                style={{ backgroundColor: color }}
                onClick={() => handleColorSelect(color)}
                aria-label={`Select color ${color}`}
              />
            ))}
          </div>
        </div>
      )}

      {selectedText && (
        <div className="toolbar-selection-preview">
          "{selectedText.substring(0, 20)}{selectedText.length > 20 ? '...' : ''}"
        </div>
      )}
    </div>
  );
};