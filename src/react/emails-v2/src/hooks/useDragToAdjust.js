import { useRef, useCallback } from 'react';

/**
 * Custom hook for drag-to-adjust functionality on input fields
 * Allows users to click and drag horizontally to adjust numeric values
 */
export function useDragToAdjust({
  value,
  onChange,
  min = 0,
  max = 1000,
  step = 1,
  sensitivity = 0.5 // How much mouse movement equals one unit
}) {
  const isDragging = useRef(false);
  const startX = useRef(0);
  const startValue = useRef(0);
  const inputRef = useRef(null);

  const handleMouseDown = useCallback((e) => {
    // Only start dragging on left mouse button
    if (e.button !== 0) return;
    
    e.preventDefault();
    isDragging.current = true;
    startX.current = e.clientX;
    startValue.current = parseFloat(value) || 0;
    
    // Change cursor to resize horizontal
    document.body.style.cursor = 'ew-resize';
    document.body.style.userSelect = 'none';
    
    // Attach global mouse events
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
  }, [value]);

  const handleMouseMove = useCallback((e) => {
    if (!isDragging.current) return;
    
    e.preventDefault();
    
    // Calculate delta and new value
    const deltaX = e.clientX - startX.current;
    const deltaValue = deltaX * sensitivity * step;
    const newValue = startValue.current + deltaValue;
    
    // Clamp to min/max bounds
    const clampedValue = Math.max(min, Math.min(max, newValue));
    
    // Round to step precision
    const roundedValue = Math.round(clampedValue / step) * step;
    
    // Call onChange with new value
    if (onChange) {
      onChange(roundedValue);
    }
  }, [onChange, min, max, step, sensitivity]);

  const handleMouseUp = useCallback(() => {
    if (!isDragging.current) return;
    
    isDragging.current = false;
    
    // Restore cursor and selection
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    
    // Remove global mouse events
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
  }, [handleMouseMove]);

  const handleMouseEnter = useCallback(() => {
    // Show drag cursor on hover
    if (inputRef.current) {
      inputRef.current.style.cursor = 'ew-resize';
    }
  }, []);

  const handleMouseLeave = useCallback(() => {
    // Reset cursor when not hovering (unless dragging)
    if (inputRef.current && !isDragging.current) {
      inputRef.current.style.cursor = '';
    }
  }, []);

  // Return props to spread on the input element
  return {
    ref: inputRef,
    onMouseDown: handleMouseDown,
    onMouseEnter: handleMouseEnter,
    onMouseLeave: handleMouseLeave,
    style: {
      cursor: 'ew-resize',
      userSelect: 'none'
    }
  };
}

export default useDragToAdjust;