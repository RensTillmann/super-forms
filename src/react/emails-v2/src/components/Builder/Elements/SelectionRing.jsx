import React from 'react';
import clsx from 'clsx';

/**
 * SelectionRing Component
 * 
 * Provides the pulsing green ring animation for selected elements.
 * This component is used by UniversalElementWrapper to show selection state
 * with a subtle, professional animation that doesn't distract from editing.
 * 
 * Features:
 * - Pulsing opacity animation
 * - Capability-aware positioning
 * - Responsive to element state
 * - Email-builder optimized styling
 */
function SelectionRing({ 
  capabilities,
  isResizing = false,
  intensity = 'normal' // 'subtle', 'normal', 'strong'
}) {
  
  // Determine animation intensity based on context
  const getAnimationClasses = () => {
    const baseClasses = 'ev2-absolute ev2-inset-0 ev2-pointer-events-none ev2-rounded';
    
    switch (intensity) {
      case 'subtle':
        return clsx(
          baseClasses,
          'ev2-ring-1 ev2-ring-green-400 ev2-animate-pulse',
          'ev2-transition-all ev2-duration-1000'
        );
      case 'strong':
        return clsx(
          baseClasses,
          'ev2-ring-4 ev2-ring-green-500 ev2-animate-pulse',
          'ev2-transition-all ev2-duration-500'
        );
      default: // normal
        return clsx(
          baseClasses,
          'ev2-ring-2 ev2-ring-green-500 ev2-animate-pulse',
          'ev2-transition-all ev2-duration-700'
        );
    }
  };

  // Adjust ring style based on element capabilities
  const getRingStyle = () => {
    const style = {};
    
    // For elements with border radius capability, match the border radius
    if (capabilities.effects?.borderRadius) {
      style.borderRadius = 'inherit';
    }
    
    // Adjust opacity during resize to reduce visual noise
    if (isResizing) {
      style.opacity = 0.3;
    }
    
    return style;
  };

  // Add extra visual indicators for special capabilities
  const getCapabilityIndicators = () => {
    const indicators = [];
    
    // Resize indicators
    if (capabilities.resizable?.horizontal && !isResizing) {
      indicators.push(
        <div
          key="resize-horizontal-hint"
          className="ev2-absolute ev2-top-1/2 ev2-right-0 ev2-w-1 ev2-h-4 ev2-bg-green-500 ev2-opacity-40 ev2-transform -ev2-translate-y-1/2 ev2-rounded-l"
        />
      );
    }
    
    if (capabilities.resizable?.vertical && !isResizing) {
      indicators.push(
        <div
          key="resize-vertical-hint"
          className="ev2-absolute ev2-bottom-0 ev2-left-1/2 ev2-w-4 ev2-h-1 ev2-bg-green-500 ev2-opacity-40 ev2-transform -ev2-translate-x-1/2 ev2-rounded-t"
        />
      );
    }
    
    return indicators;
  };

  return (
    <>
      {/* Main selection ring */}
      <div
        className={getAnimationClasses()}
        style={getRingStyle()}
      />
      
      {/* Capability indicators */}
      {getCapabilityIndicators()}
      
      {/* Inner glow for better visibility on dark backgrounds */}
      <div
        className="ev2-absolute ev2-inset-0 ev2-pointer-events-none ev2-rounded"
        style={{
          boxShadow: 'inset 0 0 0 1px rgba(34, 197, 94, 0.3)',
          borderRadius: 'inherit',
          opacity: isResizing ? 0.2 : 0.6
        }}
      />
    </>
  );
}

/**
 * Variant for hover state (lighter, no animation)
 */
export function HoverRing({ capabilities }) {
  return (
    <div
      className="ev2-absolute ev2-inset-0 ev2-pointer-events-none ev2-ring-1 ev2-ring-gray-400 ev2-ring-opacity-40 ev2-rounded ev2-transition-all ev2-duration-200"
      style={{
        borderRadius: capabilities.effects?.borderRadius ? 'inherit' : undefined
      }}
    />
  );
}

/**
 * Variant for drag state (blue, more prominent)
 */
export function DragRing({ capabilities }) {
  return (
    <div
      className="ev2-absolute ev2-inset-0 ev2-pointer-events-none ev2-ring-2 ev2-ring-blue-500 ev2-ring-opacity-60 ev2-rounded ev2-transition-all ev2-duration-200"
      style={{
        borderRadius: capabilities.effects?.borderRadius ? 'inherit' : undefined,
        boxShadow: 'inset 0 0 0 1px rgba(59, 130, 246, 0.3)'
      }}
    />
  );
}

/**
 * Variant for error state (red, attention-grabbing)
 */
export function ErrorRing({ capabilities, message }) {
  return (
    <>
      <div
        className="ev2-absolute ev2-inset-0 ev2-pointer-events-none ev2-ring-2 ev2-ring-red-500 ev2-rounded ev2-animate-pulse"
        style={{
          borderRadius: capabilities.effects?.borderRadius ? 'inherit' : undefined
        }}
      />
      {message && (
        <div className="ev2-absolute ev2-top-0 ev2-left-0 ev2-transform -ev2-translate-y-full ev2-bg-red-500 ev2-text-white ev2-text-xs ev2-px-2 ev2-py-1 ev2-rounded ev2-mb-1">
          {message}
        </div>
      )}
    </>
  );
}

export default SelectionRing;