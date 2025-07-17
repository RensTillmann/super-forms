import React, { useState, useRef, useEffect } from 'react';
import clsx from 'clsx';
import { getElementCapabilities, hasCapability } from '../../../capabilities/elementCapabilities';
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
  onCapabilityAction
}) {
  const wrapperRef = useRef(null);
  const [isResizing, setIsResizing] = useState(false);
  const [resizeDirection, setResizeDirection] = useState(null);
  
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
    minWidth
  } = element.props || {};

  // Handle resize operations
  const handleResizeStart = (direction) => {
    setIsResizing(true);
    setResizeDirection(direction);
  };

  const handleResizeEnd = () => {
    setIsResizing(false);
    setResizeDirection(null);
  };

  // Handle capability-based actions
  const handleCapabilityAction = (action, data) => {
    if (onCapabilityAction) {
      onCapabilityAction(element.id, action, data);
    }
  };

  // Handle element property updates
  const updateElementProperty = (property, value) => {
    if (onElementUpdate) {
      onElementUpdate(element.id, property, value);
    }
  };

  // Determine if spacing should be applied based on capabilities
  const shouldApplySpacing = hasCapability(element.type, 'spacing.margin') || 
                           hasCapability(element.type, 'spacing.border') || 
                           hasCapability(element.type, 'spacing.padding');

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
        'ev2-relative ev2-transition-all ev2-duration-200',
        isSelected && 'selected',
        isHovered && !isSelected && 'hovered',
        isResizing && 'resizing',
        capabilities.layout?.fullWidth && 'full-width',
        capabilities.layout?.inline && 'inline-element'
      )}
      style={{
        ...getResponsiveDimensions(),
        // Apply container-level styles based on capabilities
        ...(capabilities.layout?.fullWidth && { width: '100%' }),
        ...(capabilities.layout?.inline && { display: 'inline-block' })
      }}
    >
      
      {/* Selection Ring with Pulsing Animation */}
      {isSelected && (
        <SelectionRing 
          capabilities={capabilities}
          isResizing={isResizing}
        />
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

      {/* Quick Action Controls - Capability based */}
      {isSelected && (
        <QuickActionControls
          element={element}
          capabilities={capabilities}
          onAction={handleCapabilityAction}
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

      {/* Capability Indicators - Development mode */}
      {process.env.NODE_ENV === 'development' && (
        <CapabilityIndicators
          elementType={element.type}
          capabilities={capabilities}
        />
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
        onMouseDown={() => onResizeStart('right')}
        onMouseUp={onResizeEnd}
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
        onMouseDown={() => onResizeStart('bottom')}
        onMouseUp={onResizeEnd}
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
        onMouseDown={() => onResizeStart('corner')}
        onMouseUp={onResizeEnd}
      />
    );
  }
  
  return <>{handles}</>;
}

/**
 * Quick Action Controls Component
 * Renders capability-based quick action buttons
 */
function QuickActionControls({ element, capabilities, onAction }) {
  const actions = [];
  
  // Background color picker for elements that support it
  if (capabilities.background?.color) {
    actions.push(
      <button
        key="bg-color"
        className="ev2-absolute ev2-top-2 ev2-right-2 ev2-w-6 ev2-h-6 ev2-rounded ev2-border-2 ev2-border-white ev2-shadow-md ev2-transition-all hover:ev2-scale-110"
        style={{ backgroundColor: element.props.backgroundColor || '#f0f0f0' }}
        onClick={() => onAction('background.color', { show: true })}
        title="Change background color"
      />
    );
  }
  
  // Link editor for interactive elements
  if (capabilities.interactive?.href) {
    actions.push(
      <button
        key="link"
        className="ev2-absolute ev2-top-2 ev2-right-10 ev2-w-6 ev2-h-6 ev2-bg-blue-500 ev2-text-white ev2-rounded ev2-border-2 ev2-border-white ev2-shadow-md ev2-transition-all hover:ev2-scale-110 ev2-flex ev2-items-center ev2-justify-center"
        onClick={() => onAction('interactive.href', { show: true })}
        title="Edit link"
      >
        <svg className="ev2-w-3 ev2-h-3" fill="currentColor" viewBox="0 0 20 20">
          <path d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5z" />
          <path d="m7.414 15.414a2 2 0 01-2.828-2.828l3-3a2 2 0 012.828 0 1 1 0 001.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5z" />
        </svg>
      </button>
    );
  }
  
  return <>{actions}</>;
}

/**
 * Capability Indicators Component
 * Shows development-time indicators of element capabilities
 */
function CapabilityIndicators({ elementType, capabilities }) {
  const indicators = [];
  
  if (capabilities.resizable?.horizontal) {
    indicators.push('↔️');
  }
  if (capabilities.resizable?.vertical) {
    indicators.push('↕️');
  }
  if (capabilities.background?.color) {
    indicators.push('🎨');
  }
  if (capabilities.interactive) {
    indicators.push('🔗');
  }
  if (capabilities.typography) {
    indicators.push('Aa');
  }
  
  if (indicators.length === 0) return null;
  
  return (
    <div className="ev2-absolute ev2-bottom-1 ev2-left-1 ev2-bg-black ev2-bg-opacity-75 ev2-text-white ev2-text-xs ev2-px-1 ev2-py-0.5 ev2-rounded">
      {elementType}: {indicators.join(' ')}
    </div>
  );
}

export default UniversalElementWrapper;