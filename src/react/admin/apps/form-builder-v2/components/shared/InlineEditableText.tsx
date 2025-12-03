import React, { useState, useEffect, useRef } from 'react';
import { Edit3 } from 'lucide-react';

// FloatingTextToolbar component - simplified version
const FloatingTextToolbar: React.FC<{
  position: { x: number; y: number };
  onClose: () => void;
  onFormat: (format: string) => void;
  selectedText: string;
}> = ({ position, onClose, onFormat, selectedText }) => {
  return (
    <div 
      className="floating-toolbar"
      style={{
        position: 'absolute',
        left: position.x,
        top: position.y - 40,
        background: 'white',
        border: '1px solid #ccc',
        borderRadius: '4px',
        padding: '4px',
        zIndex: 1000
      }}
    >
      <button onClick={() => onFormat('bold')}>B</button>
      <button onClick={() => onFormat('italic')}>I</button>
      <button onClick={onClose}>×</button>
    </div>
  );
};

interface InlineEditableTextProps {
  value: string;
  onChange: (value: string) => void;
  className?: string;
  placeholder?: string;
  multiline?: boolean;
  onFormat?: (format: string) => void;
}

export const InlineEditableText: React.FC<InlineEditableTextProps> = ({ 
  value, 
  onChange, 
  className = '', 
  placeholder = '', 
  multiline = false, 
  onFormat 
}) => {
  const [isEditing, setIsEditing] = useState(false);
  const [editValue, setEditValue] = useState(value);
  const [showToolbar, setShowToolbar] = useState(false);
  const [toolbarPosition, setToolbarPosition] = useState({ x: 0, y: 0 });
  const [selectedText, setSelectedText] = useState('');
  const inputRef = useRef<HTMLInputElement | HTMLTextAreaElement>(null);

  useEffect(() => {
    setEditValue(value);
  }, [value]);

  const handleClick = () => {
    setIsEditing(true);
    setTimeout(() => {
      inputRef.current?.focus();
    }, 0);
  };

  const handleBlur = () => {
    setIsEditing(false);
    onChange(editValue);
    setShowToolbar(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !multiline) {
      handleBlur();
    }
    if (e.key === 'Escape') {
      setEditValue(value);
      setIsEditing(false);
      setShowToolbar(false);
    }
  };

  const handleTextSelection = () => {
    const selection = window.getSelection();
    if (selection && selection.toString().length > 0) {
      const range = selection.getRangeAt(0);
      const rect = range.getBoundingClientRect();
      setToolbarPosition({ x: rect.left, y: rect.top });
      setSelectedText(selection.toString());
      setShowToolbar(true);
    } else {
      setShowToolbar(false);
    }
  };

  if (isEditing) {
    return (
      <>
        {multiline ? (
          <textarea
            ref={inputRef as React.RefObject<HTMLTextAreaElement>}
            value={editValue}
            onChange={(e) => setEditValue(e.target.value)}
            onBlur={handleBlur}
            onKeyDown={handleKeyDown}
            onMouseUp={handleTextSelection}
            className={`inline-editable-input ${className}`}
            placeholder={placeholder}
            rows={3}
          />
        ) : (
          <input
            ref={inputRef as React.RefObject<HTMLInputElement>}
            type="text"
            value={editValue}
            onChange={(e) => setEditValue(e.target.value)}
            onBlur={handleBlur}
            onKeyDown={handleKeyDown}
            onMouseUp={handleTextSelection}
            className={`inline-editable-input ${className}`}
            placeholder={placeholder}
          />
        )}
        {showToolbar && onFormat && (
          <FloatingTextToolbar
            position={toolbarPosition}
            onClose={() => setShowToolbar(false)}
            onFormat={onFormat}
            selectedText={selectedText}
          />
        )}
      </>
    );
  }

  return (
    <div
      onClick={handleClick}
      className={`inline-editable-text ${className}`}
      title="Click to edit"
    >
      {value || placeholder}
      <Edit3 size={12} className="inline-edit-icon" />
    </div>
  );
};

export default InlineEditableText;