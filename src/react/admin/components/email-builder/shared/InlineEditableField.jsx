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
      <div className="inline-flex items-center gap-1">
        <InputComponent
          ref={inputRef}
          type={type}
          value={editValue}
          onChange={(e) => setEditValue(e.target.value)}
          onKeyDown={handleKeyDown}
          onBlur={handleSave}
          className={clsx(
            'bg-white border border-gray-300 rounded',
            !noPadding && 'px-2 py-0.5',
            'outline-none focus:border-blue-500',
            !noPadding && 'h-[28px]', // Fixed height for input only when padding
            multiline && 'resize-none min-h-[60px] h-auto',
            className
          )}
          placeholder={placeholder}
        />
        <div className="flex gap-1">
          <button
            type="button"
            onClick={handleSave}
            className="p-1 text-green-600 hover:bg-green-50 rounded"
            title="Save"
          >
            <Check className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={handleCancel}
            className="p-1 text-red-600 hover:bg-red-50 rounded"
            title="Cancel"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      </div>
    );
  }

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1 cursor-pointer group',
        'border border-transparent rounded',
        !noPadding && 'px-2 py-0.5',
        !noPadding && 'h-[28px]', // Same height as input only when padding
        className
      )}
      onClick={handleClick}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      title="Click to edit"
    >
      <span className={clsx(
        !value && 'text-gray-400'
      )}>
        {value || placeholder}
      </span>
      {showEditIcon && isHovered && (
        <Pencil className="w-3 h-3 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
      )}
    </span>
  );
}

export default InlineEditableField;