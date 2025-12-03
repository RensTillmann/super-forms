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
    const baseClasses = 'absolute inset-0 pointer-events-none rounded';
    
    switch (intensity) {
      case 'subtle':
        return clsx(
          baseClasses,
          'ring-1 ring-green-400 animate-pulse',
          'transition-all duration-1000'
        );
      case 'strong':
        return clsx(
          baseClasses,
          'ring-4 ring-green-500 animate-pulse',
          'transition-all duration-500'
        );
      default: // normal
        return clsx(
          baseClasses,
          'ring-2 ring-green-500 animate-pulse',
          'transition-all duration-700'
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
          className="absolute top-1/2 right-0 w-1 h-4 bg-green-500 opacity-40 transform -translate-y-1/2 rounded-l"
        />
      );
    }
    
    if (capabilities.resizable?.vertical && !isResizing) {
      indicators.push(
        <div
          key="resize-vertical-hint"
          className="absolute bottom-0 left-1/2 w-4 h-1 bg-green-500 opacity-40 transform -translate-x-1/2 rounded-t"
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
        className="absolute inset-0 pointer-events-none rounded"
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
      className="absolute inset-0 pointer-events-none ring-1 ring-gray-400 ring-opacity-40 rounded transition-all duration-200"
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
      className="absolute inset-0 pointer-events-none ring-2 ring-blue-500 ring-opacity-60 rounded transition-all duration-200"
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
        className="absolute inset-0 pointer-events-none ring-2 ring-red-500 rounded animate-pulse"
        style={{
          borderRadius: capabilities.effects?.borderRadius ? 'inherit' : undefined
        }}
      />
      {message && (
        <div className="absolute top-0 left-0 transform -translate-y-full bg-red-500 text-white text-xs px-2 py-1 rounded mb-1">
          {message}
        </div>
      )}
    </>
  );
}

export default SelectionRing;