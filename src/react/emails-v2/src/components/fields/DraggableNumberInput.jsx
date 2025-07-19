import React, { useState, useRef, useEffect } from 'react';
import { useDragToAdjust } from '../../hooks/useDragToAdjust';

/**
 * Number input with drag-to-adjust functionality
 * Combines regular input behavior with horizontal drag-to-change
 */
function DraggableNumberInput({
  value,
  onChange,
  min = 0,
  max = 1000,
  step = 1,
  sensitivity = 0.5,
  className = '',
  title = '',
  placeholder = '',
  ...otherProps
}) {
  const [localValue, setLocalValue] = useState(value);
  const [isFocused, setIsFocused] = useState(false);
  const inputRef = useRef(null);

  // Update local value when prop changes (but not during focus)
  useEffect(() => {
    if (!isFocused) {
      setLocalValue(value);
    }
  }, [value, isFocused]);

  // Handle drag-to-adjust
  const dragProps = useDragToAdjust({
    value: localValue,
    onChange: (newValue) => {
      setLocalValue(newValue);
      if (onChange) {
        onChange({ target: { value: newValue.toString() } });
      }
    },
    min,
    max,
    step,
    sensitivity
  });

  // Handle regular input changes
  const handleInputChange = (e) => {
    const newValue = e.target.value;
    setLocalValue(newValue);
    if (onChange) {
      onChange(e);
    }
  };

  // Handle focus/blur to manage local state
  const handleFocus = (e) => {
    setIsFocused(true);
    if (otherProps.onFocus) {
      otherProps.onFocus(e);
    }
  };

  const handleBlur = (e) => {
    setIsFocused(false);
    // Ensure the final value is within bounds
    const numValue = parseFloat(localValue) || 0;
    const clampedValue = Math.max(min, Math.min(max, numValue));
    if (clampedValue !== numValue) {
      setLocalValue(clampedValue);
      if (onChange) {
        onChange({ target: { value: clampedValue.toString() } });
      }
    }
    if (otherProps.onBlur) {
      otherProps.onBlur(e);
    }
  };

  // Prevent drag when clicking directly in the input field (for text selection)
  const handleMouseDown = (e) => {
    // If clicking on the input border/padding area, enable drag
    // If clicking on the text content, allow normal text selection
    const rect = inputRef.current.getBoundingClientRect();
    const padding = 8; // Approximate padding
    const clickX = e.clientX - rect.left;
    const clickY = e.clientY - rect.top;
    
    // Only start drag if clicking in the padding area (not on text)
    const inPaddingArea = clickX < padding || clickX > rect.width - padding || 
                         clickY < padding || clickY > rect.height - padding;
    
    if (inPaddingArea && !isFocused) {
      dragProps.onMouseDown(e);
    }
  };

  // Merge class names for styling
  const combinedClassName = [
    className,
    'draggable-number-input',
    'hover:ev2-shadow-lg', // Extra hover effect for drag indication
    'ev2-transition-all ev2-duration-150'
  ].filter(Boolean).join(' ');

  return (
    <input
      {...otherProps}
      ref={(node) => {
        inputRef.current = node;
        if (dragProps.ref) {
          dragProps.ref.current = node;
        }
      }}
      type="number"
      min={min}
      max={max}
      step={step}
      value={localValue}
      onChange={handleInputChange}
      onFocus={handleFocus}
      onBlur={handleBlur}
      onMouseDown={handleMouseDown}
      onMouseEnter={dragProps.onMouseEnter}
      onMouseLeave={dragProps.onMouseLeave}
      className={combinedClassName}
      title={title ? `${title} (Click and drag to adjust)` : 'Click and drag to adjust'}
      placeholder={placeholder}
    />
  );
}

export default DraggableNumberInput;