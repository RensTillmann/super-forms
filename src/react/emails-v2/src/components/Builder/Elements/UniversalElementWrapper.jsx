import React, { useState, useRef, useEffect } from 'react';
import clsx from 'clsx';
import { Palette, Pencil } from 'lucide-react';
import { getElementCapabilities, hasCapability } from '../../../capabilities/elementCapabilities';
import { useEmailBuilderStore } from '../../../hooks/useEmailBuilder';
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
  onEdit
}) {
  const wrapperRef = useRef(null);
  const [isResizing, setIsResizing] = useState(false);
  const [resizeDirection, setResizeDirection] = useState(null);
  const [showColorPicker, setShowColorPicker] = useState(false);
  
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
    setShowColorPicker(true);
  };

  // Handle color change
  const handleColorChange = (color) => {
    updateElement(element.id, {
      props: {
        ...element.props,
        backgroundColor: color
      }
    });
    setShowColorPicker(false);
  };

  // Close color picker when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (showColorPicker && wrapperRef.current && !wrapperRef.current.contains(event.target)) {
        setShowColorPicker(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showColorPicker]);

  // Determine if spacing should be applied based on capabilities
  // Email wrapper is background-only and shouldn't have spacing
  const shouldApplySpacing = element.type !== 'emailWrapper' && 
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
        'ev2-relative',
        // Only apply transitions when NOT resizing to avoid lag
        !isResizing && 'ev2-transition-all ev2-duration-200',
        isSelected && 'selected',
        isHovered && !isSelected && 'hovered ev2-ring-2 ev2-ring-gray-400 ev2-ring-opacity-40',
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
          minHeight: '100vh',
          display: 'block',
          backgroundColor: backgroundColor !== 'transparent' ? backgroundColor : '#f5f5f5'
        }),
        // Email container styling - centered with specified width and full height
        ...(element.type === 'emailContainer' && {
          width: width || '600px',
          margin: '0 auto',
          display: 'block',
          minHeight: '100vh', // Fill the full viewport height like the wrapper
          height: '100%', // Fill parent height
          ...(element.props.boxShadow && element.props.boxShadow !== 'none' && { boxShadow: element.props.boxShadow }),
          ...(element.props.borderRadius && { borderRadius: `${element.props.borderRadius}px` })
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
        <div className="ev2-absolute ev2-top-1 ev2-left-1 ev2-z-[60] ev2-pointer-events-auto">
          <button
            onClick={(e) => {
              e.stopPropagation();
              if (element.type === 'emailWrapper' && capabilities.layout?.backgroundOnly) {
                // For wrapper, show color picker directly
                handleBackgroundColorClick(e);
              } else {
                // For other system elements, use normal edit
                onEdit();
              }
            }}
            className="ev2-p-1.5 ev2-bg-blue-500 ev2-text-white ev2-rounded ev2-shadow-lg hover:ev2-shadow-xl ev2-transition-all hover:ev2-scale-110"
            title={element.type === 'emailWrapper' ? 'Change Background Color' : `Edit ${element.type === 'emailContainer' ? 'Email Container' : element.type}`}
          >
            {element.type === 'emailWrapper' && capabilities.layout?.backgroundOnly ? (
              // Color palette icon for wrapper
              <Palette className="ev2-w-3 ev2-h-3" />
            ) : (
              // Pencil icon for other elements
              <Pencil className="ev2-w-3 ev2-h-3" />
            )}
          </button>
        </div>
      )}

      {/* System Element Controls - Only show pencil icon for editing when selected */}
      {isSelected && capabilities.layout?.isSystemElement && (
        <div className="ev2-absolute ev2-top-1 ev2-left-1 ev2-z-10">
          <div className="ev2-bg-blue-500 ev2-text-white ev2-p-1 ev2-rounded ev2-shadow-sm ev2-flex ev2-items-center ev2-gap-1">
            <Pencil className="ev2-w-3 ev2-h-3" />
            <span className="ev2-text-xs ev2-font-medium">
              {element.type === 'emailWrapper' ? 'Wrapper' : 'Container'}
            </span>
          </div>
        </div>
      )}

      {/* Background Color Picker for Email Wrapper */}
      {showColorPicker && element.type === 'emailWrapper' && (
        <div className="ev2-absolute ev2-top-10 ev2-left-1 ev2-z-[70] ev2-pointer-events-auto">
          <div className="ev2-bg-white ev2-rounded-lg ev2-shadow-xl ev2-border ev2-p-3 ev2-min-w-[200px]">
            <div className="ev2-text-xs ev2-font-medium ev2-text-gray-700 ev2-mb-2">Background Color</div>
            <div className="ev2-grid ev2-grid-cols-6 ev2-gap-2 ev2-mb-3">
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
                  className="ev2-w-6 ev2-h-6 ev2-rounded ev2-border-2 ev2-border-gray-300 hover:ev2-border-blue-500 ev2-transition-colors"
                  style={{ backgroundColor: color }}
                  title={color}
                />
              ))}
            </div>
            <div className="ev2-text-xs ev2-text-gray-500 ev2-mb-2">Custom Color</div>
            <input
              type="color"
              value={element.props?.backgroundColor || '#ffffff'}
              onChange={(e) => handleColorChange(e.target.value)}
              className="ev2-w-full ev2-h-8 ev2-rounded ev2-border ev2-border-gray-300"
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
          'ev2-absolute ev2-top-0 ev2-right-0 ev2-w-2 ev2-h-full ev2-cursor-ew-resize',
          'ev2-bg-blue-500 ev2-opacity-0 hover:ev2-opacity-50 ev2-transition-opacity',
          isResizing && direction === 'right' && 'ev2-opacity-75'
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
          'ev2-absolute ev2-bottom-0 ev2-left-0 ev2-w-full ev2-h-2 ev2-cursor-ns-resize',
          'ev2-bg-blue-500 ev2-opacity-0 hover:ev2-opacity-50 ev2-transition-opacity',
          isResizing && direction === 'bottom' && 'ev2-opacity-75'
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
          'ev2-absolute ev2-bottom-0 ev2-right-0 ev2-w-3 ev2-h-3 ev2-cursor-nw-resize',
          'ev2-bg-blue-500 ev2-opacity-0 hover:ev2-opacity-75 ev2-transition-opacity',
          isResizing && direction === 'corner' && 'ev2-opacity-100'
        )}
        onMouseDown={(e) => onResizeStart('corner', e)}
      />
    );
  }
  
  return <>{handles}</>;
}



export default UniversalElementWrapper;