import React, { useState, useRef, useEffect } from 'react';
import clsx from 'clsx';
import { Palette, Pencil } from 'lucide-react';
import { getElementCapabilities, hasCapability } from '../../capabilities/elementCapabilities';
import { useEmailBuilderStore } from '../../hooks/useEmailBuilder';
import SpacingLayer from './SpacingLayer';
import SelectionRing from './SelectionRing';

/**
 * UniversalElementWrapper - The universal wrapper for all email builder elements
 * 
 * Provides consistent behavior across all element types:
 * - Selection state management with pulsing animation
 * - Universal spacing handling (margin, border, padding)
 * - Capability-based control rendering
 * - Resize handles and interaction layers
 * - Background and styling management
 * 
 * This eliminates code duplication and ensures consistent UX across all elements.
 */
function UniversalElementWrapper({ 
  element, 
  isSelected, 
  isHovered,
  renderElements,
  children,
  onElementUpdate,
  onCapabilityAction,
  onEdit,
  isMobile
}) {
  const wrapperRef = useRef(null);
  const [isResizing, setIsResizing] = useState(false);
  const [resizeDirection, setResizeDirection] = useState(null);
  const [showColorPicker, setShowColorPicker] = useState(false);
  const [tempColor, setTempColor] = useState(null);
  
  // Get updateElement function from store
  const updateElement = useEmailBuilderStore(state => state.updateElement);
  
  // Get capabilities for this element type
  const capabilities = getElementCapabilities(element.type);
  
  // Extract element properties with defaults
  const {
    margin = { top: 0, right: 0, bottom: 0, left: 0 },
    border = { top: 0, right: 0, bottom: 0, left: 0 },
    borderStyle = 'solid',
    borderColor = '#000000',
    padding = { top: 0, right: 0, bottom: 0, left: 0 },
    backgroundColor = 'transparent',
    backgroundImage = '',
    backgroundSize = 'cover',
    backgroundPosition = 'center',
    backgroundRepeat = 'no-repeat',
    width,
    height,
    maxWidth,
    minWidth,
    fullWidth
  } = element.props || {};

  // Handle resize operations
  const handleResizeStart = (direction, e) => {
    e.preventDefault();
    e.stopPropagation();
    
    setIsResizing(true);
    setResizeDirection(direction);
    
    const initialX = e.clientX;
    const initialY = e.clientY;
    const initialWidth = wrapperRef.current?.offsetWidth || 0;
    const initialHeight = wrapperRef.current?.offsetHeight || 0;
    
    const handleMouseMove = (moveEvent) => {
      const deltaX = moveEvent.clientX - initialX;
      const deltaY = moveEvent.clientY - initialY;
      
      let newWidth = initialWidth;
      let newHeight = initialHeight;
      
      // Calculate new dimensions based on direction with capability-based constraints
      if (direction === 'right' || direction === 'corner') {
        const minWidth = capabilities.resizable?.minWidth || 100;
        const maxWidth = capabilities.resizable?.maxWidth || 700; // Email-friendly default
        newWidth = Math.max(minWidth, Math.min(maxWidth, initialWidth + deltaX));
      }
      if (direction === 'bottom' || direction === 'corner') {
        const minHeight = capabilities.resizable?.minHeight || 50;
        const maxHeight = capabilities.resizable?.maxHeight || 1000; // Reasonable max height
        newHeight = Math.max(minHeight, Math.min(maxHeight, initialHeight + deltaY));
      }
      
      // Debug logging for development
      if (process.env.NODE_ENV === 'development') {
        console.log('Resize:', { direction, deltaX, deltaY, newWidth, newHeight, initialWidth, initialHeight });
      }
      
      // Update element properties through the store
      updateElement(element.id, {
        props: {
          ...element.props,
          ...(direction === 'right' || direction === 'corner' ? { width: `${newWidth}px` } : {}),
          ...(direction === 'bottom' || direction === 'corner' ? { height: `${newHeight}px` } : {})
        }
      });
    };
    
    const handleMouseUp = () => {
      setIsResizing(false);
      setResizeDirection(null);
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
    
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
  };

  const handleResizeEnd = () => {
    setIsResizing(false);
    setResizeDirection(null);
  };

  // Handle element property updates
  const updateElementProperty = (property, value) => {
    if (onElementUpdate) {
      onElementUpdate(element.id, property, value);
    }
  };

  // Handle background color picker for wrapper
  const handleBackgroundColorClick = (e) => {
    e.stopPropagation();
    setTempColor(element.props?.backgroundColor || '#ffffff');
    setShowColorPicker(true);
  };

  // Handle color change (preview only)
  const handleColorChange = (color) => {
    setTempColor(color);
    // Preview the color immediately
    updateElement(element.id, {
      props: {
        ...element.props,
        backgroundColor: color
      }
    });
  };

  // Apply the selected color and close picker
  const handleApplyColor = () => {
    if (tempColor) {
      updateElement(element.id, {
        props: {
          ...element.props,
          backgroundColor: tempColor
        }
      });
    }
    setShowColorPicker(false);
    setTempColor(null);
  };

  // Cancel color selection and revert
  const handleCancelColor = () => {
    // Revert to original color
    const originalColor = element.props?.backgroundColor || '#ffffff';
    updateElement(element.id, {
      props: {
        ...element.props,
        backgroundColor: originalColor
      }
    });
    setShowColorPicker(false);
    setTempColor(null);
  };

  // Close color picker when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (showColorPicker && wrapperRef.current && !wrapperRef.current.contains(event.target)) {
        handleCancelColor();
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showColorPicker]);

  // Determine if spacing should be applied based on capabilities
  // Email wrapper is background-only and shouldn't have spacing
  // Email container should apply margin internally, not to wrapper
  const shouldApplySpacing = element.type !== 'emailWrapper' && 
                           element.type !== 'emailContainer' &&
                           (hasCapability(element.type, 'spacing.margin') || 
                            hasCapability(element.type, 'spacing.border') || 
                            hasCapability(element.type, 'spacing.padding'));

  // Calculate responsive dimensions
  const getResponsiveDimensions = () => {
    const dims = {};
    
    if (capabilities.resizable?.horizontal && width) {
      dims.width = width;
    }
    if (capabilities.resizable?.vertical && height) {
      dims.height = height;
    }
    if (maxWidth) {
      dims.maxWidth = maxWidth;
    }
    if (minWidth) {
      dims.minWidth = minWidth;
    }
    
    return dims;
  };

  return (
    <div
      ref={wrapperRef}
      className={clsx(
        'universal-element-wrapper',
        'relative',
        // Only apply transitions when NOT resizing to avoid lag
        !isResizing && 'transition-all duration-200',
        isSelected && 'selected',
        isHovered && !isSelected && 'hovered ring-2 ring-gray-400 ring-opacity-40',
        isResizing && 'resizing',
        capabilities.layout?.fullWidth && 'full-width',
        capabilities.layout?.inline && 'inline-element'
      )}
      style={{
        ...getResponsiveDimensions(),
        // Apply container-level styles based on capabilities
        // If fullWidth is enabled in properties, it overrides manual width
        ...(fullWidth ? { width: '100%' } : {}),
        ...(capabilities.layout?.fullWidth && !width && !fullWidth && { width: '100%' }),
        ...(capabilities.layout?.inline && { display: 'inline-block' }),
        // Center sections horizontally for email compatibility
        ...(capabilities.layout?.centered && !fullWidth && { margin: '0 auto' }),
        // Set default width for sections if no width is specified
        ...(element.type === 'section' && !width && !fullWidth && { 
          width: `${capabilities.resizable?.defaultWidth || 600}px` 
        }),
        // Email wrapper styling - full width with background
        ...(element.type === 'emailWrapper' && {
          width: '100%',
          minHeight: '200px',
          display: 'block',
          backgroundColor: backgroundColor !== 'transparent' ? backgroundColor : '#f5f5f5'
        }),
        // Email container styling - centered with specified width
        ...(element.type === 'emailContainer' && {
          width: isMobile ? '100%' : (width || '600px'),
          maxWidth: isMobile ? '100%' : (width || '600px'),
          margin: isMobile ? '0' : '0 auto',
          display: 'block',
          minHeight: '200px' // Reasonable minimum height
          // boxShadow and borderRadius are now handled by SpacingLayer
        })
      }}
      {...({
        // Always visible element identification
        'data-element-type': element.type,
        'data-element-id': element.id,
        'data-component': 'UniversalElementWrapper',
        'data-is-system': capabilities.layout?.isSystemElement ? 'true' : 'false',
        'data-is-selected': isSelected ? 'true' : 'false',
        'data-is-hovered': isHovered ? 'true' : 'false',
        
        // Development debugging attributes (always show when not production)
        ...(!process.env.NODE_ENV || process.env.NODE_ENV !== 'production') && {
          'data-debug-margin': JSON.stringify(margin),
          'data-debug-padding': JSON.stringify(padding),
          'data-debug-border': JSON.stringify(border),
          'data-debug-bg': backgroundColor,
          'data-debug-width': width || 'auto',
          'data-debug-height': height || 'auto',
          'data-debug-full-width': fullWidth ? 'true' : 'false',
          'data-debug-spacing': shouldApplySpacing ? 'true' : 'false',
          'data-debug-capabilities': JSON.stringify(Object.keys(capabilities)),
          'data-debug-resizing': isResizing ? 'true' : 'false',
          'data-debug-children-count': element.children?.length || 0,
          'data-debug-props': JSON.stringify(Object.keys(element.props || {}))
        },
        
        // Environment info
        'data-debug-env': process.env.NODE_ENV || 'undefined'
      })}
    >
      
      {/* Selection Ring with Pulsing Animation */}
      {isSelected && (
        <SelectionRing 
          capabilities={capabilities}
          isResizing={isResizing}
        />
      )}

      {/* System Element Blue Edit Button - Show on hover or when selected for background-only elements */}
      {((isHovered && !isSelected) || (isSelected && capabilities.layout?.backgroundOnly)) && capabilities.layout?.isSystemElement && onEdit && (
        <div className="absolute top-1 left-1 z-[60] pointer-events-auto">
          <button
            onClick={(e) => {
              e.stopPropagation();
              if (element.type === 'emailWrapper' && capabilities.layout?.backgroundOnly) {
                // For wrapper, show color picker directly
                handleBackgroundColorClick(e);
              } else {
                // For other system elements, use normal edit
                onEdit(e);
              }
            }}
            className="p-1.5 bg-blue-500 text-white rounded shadow-lg hover:shadow-xl transition-all hover:scale-110"
            title={element.type === 'emailWrapper' ? 'Change Background Color' : `Edit ${element.type === 'emailContainer' ? 'Email Container' : element.type}`}
          >
            {element.type === 'emailWrapper' && capabilities.layout?.backgroundOnly ? (
              // Color palette icon for wrapper
              <Palette className="w-3 h-3" />
            ) : (
              // Pencil icon for other elements
              <Pencil className="w-3 h-3" />
            )}
          </button>
        </div>
      )}

      {/* System Element Controls - Only show pencil icon for editing when selected */}
      {isSelected && capabilities.layout?.isSystemElement && (
        <div className="absolute top-1 left-1 z-10">
          <div className="bg-blue-500 text-white p-1 rounded shadow-sm flex items-center gap-1">
            <Pencil className="w-3 h-3" />
            <span className="text-xs font-medium">
              {element.type === 'emailWrapper' ? 'Wrapper' : 'Container'}
            </span>
          </div>
        </div>
      )}

      {/* Background Color Picker for Email Wrapper */}
      {showColorPicker && element.type === 'emailWrapper' && (
        <div className="absolute top-10 left-1 z-[70] pointer-events-auto">
          <div className="bg-white rounded-lg shadow-xl border p-3 min-w-[200px]">
            {/* Buttons at the top */}
            <div className="flex gap-2 mb-3">
              <button
                onClick={handleCancelColor}
                className="flex-1 px-3 py-1.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleApplyColor}
                className="flex-1 px-3 py-1.5 text-sm text-white bg-blue-500 hover:bg-blue-600 rounded transition-colors"
              >
                Apply
              </button>
            </div>
            <div className="text-xs font-medium text-gray-700 mb-2">Background Color</div>
            <div className="grid grid-cols-6 gap-2 mb-3">
              {/* Common email background colors */}
              {[
                '#ffffff', // White
                '#f8f9fa', // Light gray
                '#e9ecef', // Gray
                '#dee2e6', // Medium gray
                '#f8f5f0', // Cream
                '#fff9e6', // Light yellow
                '#e8f4fd', // Light blue
                '#e6f7ff', // Very light blue
                '#f0f8f0', // Light green
                '#fef7f0', // Light orange
                '#f5f5f5', // Very light gray
                '#000000', // Black
              ].map((color) => (
                <button
                  key={color}
                  onClick={() => handleColorChange(color)}
                  className={clsx(
                    "w-6 h-6 rounded border-2 transition-colors",
                    tempColor === color 
                      ? "border-blue-500 ring-2 ring-blue-200" 
                      : "border-gray-300 hover:border-blue-500"
                  )}
                  style={{ backgroundColor: color }}
                  title={color}
                />
              ))}
            </div>
            <div className="text-xs text-gray-500 mb-2">Custom Color</div>
            <input
              type="color"
              value={tempColor || element.props?.backgroundColor || '#ffffff'}
              onChange={(e) => handleColorChange(e.target.value)}
              className="w-full h-8 rounded border border-gray-300"
            />
          </div>
        </div>
      )}

      {/* Resize Handles - Only show for elements with resize capability */}
      {isSelected && capabilities.resizable && (
        <ResizeHandles
          capabilities={capabilities.resizable}
          onResizeStart={handleResizeStart}
          onResizeEnd={handleResizeEnd}
          isResizing={isResizing}
          direction={resizeDirection}
        />
      )}


      {/* Universal Spacing Layer */}
      {shouldApplySpacing ? (
        <SpacingLayer
          margin={hasCapability(element.type, 'spacing.margin') ? margin : null}
          border={hasCapability(element.type, 'spacing.border') ? {
            width: border,
            style: borderStyle,
            color: borderColor
          } : null}
          padding={hasCapability(element.type, 'spacing.padding') ? padding : null}
          backgroundColor={capabilities.background?.color ? backgroundColor : null}
          backgroundImage={capabilities.background?.image ? {
            url: backgroundImage,
            size: backgroundSize,
            position: backgroundPosition,
            repeat: backgroundRepeat
          } : null}
          borderRadius={capabilities.effects?.borderRadius ? element.props.borderRadius : null}
          isResizing={isResizing}
        >
          {/* Pure Element Content */}
          {children}
        </SpacingLayer>
      ) : (
        /* Direct rendering for elements that don't need spacing */
        <div className="element-content">
          {children}
        </div>
      )}

    </div>
  );
}

/**
 * Resize Handles Component
 * Renders resize handles based on element capabilities
 */
function ResizeHandles({ capabilities, onResizeStart, onResizeEnd, isResizing, direction }) {
  const handles = [];
  
  // Horizontal resize handles
  if (capabilities.horizontal) {
    handles.push(
      <div
        key="resize-right"
        className={clsx(
          'resize-handle resize-handle-right',
          'absolute top-0 right-0 w-2 h-full cursor-ew-resize',
          'bg-blue-500 opacity-0 hover:opacity-50 transition-opacity',
          isResizing && direction === 'right' && 'opacity-75'
        )}
        onMouseDown={(e) => onResizeStart('right', e)}
      />
    );
  }
  
  // Vertical resize handles
  if (capabilities.vertical) {
    handles.push(
      <div
        key="resize-bottom"
        className={clsx(
          'resize-handle resize-handle-bottom',
          'absolute bottom-0 left-0 w-full h-2 cursor-ns-resize',
          'bg-blue-500 opacity-0 hover:opacity-50 transition-opacity',
          isResizing && direction === 'bottom' && 'opacity-75'
        )}
        onMouseDown={(e) => onResizeStart('bottom', e)}
      />
    );
  }
  
  // Corner resize handle for elements that can resize both ways
  if (capabilities.horizontal && capabilities.vertical) {
    handles.push(
      <div
        key="resize-corner"
        className={clsx(
          'resize-handle resize-handle-corner',
          'absolute bottom-0 right-0 w-3 h-3 cursor-nw-resize',
          'bg-blue-500 opacity-0 hover:opacity-75 transition-opacity',
          isResizing && direction === 'corner' && 'opacity-100'
        )}
        onMouseDown={(e) => onResizeStart('corner', e)}
      />
    );
  }
  
  return <>{handles}</>;
}



export default UniversalElementWrapper;