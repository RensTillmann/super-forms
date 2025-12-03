import React, { useState, useRef, useEffect } from 'react';
import { Edit3 } from 'lucide-react';
import { InlineEditableTextProps } from '../types/control.types';
import { FloatingToolbar } from '../overlays/FloatingToolbar';

export const InlineEditableText: React.FC<InlineEditableTextProps> = ({
  value,
  onChange,
  className = '',
  placeholder = 'Click to edit',
  multiline = false,
  onFormat,
  maxLength,
  disabled = false
}) => {
  const [isEditing, setIsEditing] = useState(false);
  const [editValue, setEditValue] = useState(value);
  const [showToolbar, setShowToolbar] = useState(false);
  const [toolbarPosition, setToolbarPosition] = useState({ x: 0, y: 0 });
  const [selectedText, setSelectedText] = useState('');
  const inputRef = useRef<HTMLInputElement | HTMLTextAreaElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setEditValue(value);
  }, [value]);

  const handleClick = () => {
    if (!disabled && !isEditing) {
      setIsEditing(true);
      setEditValue(value);
      setTimeout(() => {
        inputRef.current?.focus();
        inputRef.current?.select();
      }, 0);
    }
  };

  const handleBlur = () => {
    setIsEditing(false);
    if (editValue !== value) {
      onChange(editValue);
    }
    setShowToolbar(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !multiline) {
      e.preventDefault();
      handleBlur();
    }
    if (e.key === 'Escape') {
      setEditValue(value);
      setIsEditing(false);
      setShowToolbar(false);
    }
  };

  const handleTextSelection = () => {
    if (!onFormat) return;
    
    const selection = window.getSelection();
    if (selection && selection.toString().length > 0) {
      const range = selection.getRangeAt(0);
      const rect = range.getBoundingClientRect();
      setToolbarPosition({ 
        x: rect.left + (rect.width / 2), 
        y: rect.top 
      });
      setSelectedText(selection.toString());
      setShowToolbar(true);
    } else {
      setShowToolbar(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const newValue = e.target.value;
    if (!maxLength || newValue.length <= maxLength) {
      setEditValue(newValue);
    }
  };

  // Note: Removed the old conditional rendering - now using overlay approach

  return (
    <div 
      className={`inline-editable-text ${className} ${disabled ? 'inline-editable-disabled' : ''} ${isEditing ? 'inline-editable-editing' : ''}`}
      style={{ position: 'relative' }}
      onClick={handleClick}
      title={disabled ? '' : 'Click to edit'}
    >
      <span className={`inline-editable-value ${isEditing ? 'inline-editable-value-editing' : ''}`}>
        {value || <span className="inline-editable-placeholder">{placeholder}</span>}
      </span>
      {!disabled && !isEditing && <Edit3 size={12} className="inline-edit-icon" />}
      
      {isEditing && (
        multiline ? (
          <textarea
            ref={inputRef as React.RefObject<HTMLTextAreaElement>}
            value={editValue}
            onChange={handleChange}
            onBlur={handleBlur}
            onKeyDown={handleKeyDown}
            className="inline-editable-overlay-input"
            maxLength={maxLength}
            rows={3}
            style={{
              position: 'absolute',
              top: 0,
              left: 0,
              width: '100%',
              height: '100%',
              background: 'transparent',
              border: 'none',
              outline: 'none',
              boxShadow: 'none',
              fontSize: 'inherit',
              fontWeight: 'inherit',
              color: 'inherit',
              fontFamily: 'inherit',
              padding: 'inherit',
              margin: '0',
              textAlign: 'inherit',
              zIndex: 10,
              resize: 'none'
            }}
            autoFocus
          />
        ) : (
          <input
            ref={inputRef as React.RefObject<HTMLInputElement>}
            type="text"
            value={editValue}
            onChange={handleChange}
            onBlur={handleBlur}
            onKeyDown={handleKeyDown}
            className="inline-editable-overlay-input"
            maxLength={maxLength}
            style={{
              position: 'absolute',
              top: 0,
              left: 0,
              width: '100%',
              height: '100%',
              background: 'transparent',
              border: 'none',
              outline: 'none',
              boxShadow: 'none',
              fontSize: 'inherit',
              fontWeight: 'inherit',
              color: 'inherit',
              fontFamily: 'inherit',
              padding: 'inherit',
              margin: '0',
              textAlign: 'inherit',
              zIndex: 10
            }}
            autoFocus
          />
        )
      )}
    </div>
  );
};