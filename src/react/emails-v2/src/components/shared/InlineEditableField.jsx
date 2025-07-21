import React, { useState, useRef, useEffect } from 'react';
import { Check, X, Pencil } from 'lucide-react';
import clsx from 'clsx';

/**
 * InlineEditableField - A component that allows inline editing of text fields
 * 
 * @param {string} value - The current value
 * @param {function} onChange - Callback when value is saved
 * @param {string} placeholder - Placeholder text when empty
 * @param {string} className - Additional CSS classes
 * @param {boolean} multiline - Whether to use textarea instead of input
 * @param {string} type - Input type (text, email, etc.)
 * @param {boolean} showEditIcon - Whether to show edit icon on hover
 */
function InlineEditableField({ 
  value, 
  onChange, 
  placeholder = 'Click to edit',
  className = '',
  multiline = false,
  type = 'text',
  showEditIcon = true,
  noPadding = false
}) {
  const [isEditing, setIsEditing] = useState(false);
  const [editValue, setEditValue] = useState(value || '');
  const [isHovered, setIsHovered] = useState(false);
  const inputRef = useRef(null);

  useEffect(() => {
    if (isEditing && inputRef.current) {
      inputRef.current.focus();
      // Select all text on focus
      if (inputRef.current.select) {
        inputRef.current.select();
      }
    }
  }, [isEditing]);

  // Update editValue when value prop changes
  useEffect(() => {
    setEditValue(value || '');
  }, [value]);

  const handleSave = () => {
    if (onChange && editValue !== value) {
      onChange(editValue);
    }
    setIsEditing(false);
  };

  const handleCancel = () => {
    setEditValue(value || '');
    setIsEditing(false);
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !multiline) {
      e.preventDefault();
      handleSave();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      handleCancel();
    }
  };

  const handleClick = () => {
    if (!isEditing) {
      setIsEditing(true);
    }
  };

  if (isEditing) {
    const InputComponent = multiline ? 'textarea' : 'input';
    
    return (
      <div className="ev2-inline-flex ev2-items-center ev2-gap-1">
        <InputComponent
          ref={inputRef}
          type={type}
          value={editValue}
          onChange={(e) => setEditValue(e.target.value)}
          onKeyDown={handleKeyDown}
          onBlur={handleSave}
          className={clsx(
            'ev2-bg-white ev2-border ev2-border-gray-300 ev2-rounded',
            !noPadding && 'ev2-px-2 ev2-py-0.5',
            'ev2-outline-none focus:ev2-border-blue-500',
            !noPadding && 'ev2-h-[28px]', // Fixed height for input only when padding
            multiline && 'ev2-resize-none ev2-min-h-[60px] ev2-h-auto',
            className
          )}
          placeholder={placeholder}
        />
        <div className="ev2-flex ev2-gap-1">
          <button
            type="button"
            onClick={handleSave}
            className="ev2-p-1 ev2-text-green-600 hover:ev2-bg-green-50 ev2-rounded"
            title="Save"
          >
            <Check className="ev2-w-4 ev2-h-4" />
          </button>
          <button
            type="button"
            onClick={handleCancel}
            className="ev2-p-1 ev2-text-red-600 hover:ev2-bg-red-50 ev2-rounded"
            title="Cancel"
          >
            <X className="ev2-w-4 ev2-h-4" />
          </button>
        </div>
      </div>
    );
  }

  return (
    <span
      className={clsx(
        'ev2-inline-flex ev2-items-center ev2-gap-1 ev2-cursor-pointer ev2-group',
        'ev2-border ev2-border-transparent ev2-rounded',
        !noPadding && 'ev2-px-2 ev2-py-0.5',
        !noPadding && 'ev2-h-[28px]', // Same height as input only when padding
        className
      )}
      onClick={handleClick}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      title="Click to edit"
    >
      <span className={clsx(
        !value && 'ev2-text-gray-400'
      )}>
        {value || placeholder}
      </span>
      {showEditIcon && isHovered && (
        <Pencil className="ev2-w-3 ev2-h-3 ev2-text-gray-400 ev2-opacity-0 group-hover:ev2-opacity-100 ev2-transition-opacity" />
      )}
    </span>
  );
}

export default InlineEditableField;