import React, { useState, useEffect } from 'react';
import { getElementCapabilities } from '../../capabilities/elementCapabilities';
import { useEmailBuilderStore } from '../../hooks/useEmailBuilder';
import SpacingCompass from '../fields/SpacingCompass';
import EmailTextEditor from '../fields/EmailTextEditor';

/**
 * Simple Property Panel - Only re-renders when elementId changes
 */
function OptimizedPropertyPanelInner({ elementId, onClose }) {

  // Get the element once and store it locally
  const [element, setElement] = useState(null);
  const [localProps, setLocalProps] = useState({});

  // Simple effect that only runs when elementId changes
  useEffect(() => {
    // Add a small delay to ensure store is updated
    const timeoutId = setTimeout(() => {
      const store = useEmailBuilderStore.getState();
      const found = findElementById(store.elements, elementId);
      setElement(found);
      setLocalProps(found ? { ...found.props } : {});
    }, 10);
    
    return () => clearTimeout(timeoutId);
  }, [elementId]);

  // Get functions directly from store without subscribing
  const updateElement = useEmailBuilderStore.getState().updateElement;
  const selectElement = useEmailBuilderStore.getState().selectElement;
  
  if (!element || element.id !== elementId) {
    // Element not found - likely timing issue, show loading state
    return (
      <div className="ev2-w-full ev2-bg-white ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-shadow-lg ev2-p-4">
        <h3 className="ev2-text-lg ev2-font-semibold ev2-text-gray-900 ev2-mb-4">Loading...</h3>
        <p className="ev2-text-sm ev2-text-gray-600">Loading element properties...</p>
      </div>
    );
  }

  const capabilities = getElementCapabilities(element.type);

  // Update property - update local state for UI, store for persistence
  const updateProperty = (property, value) => {
    // Update local state immediately for responsive UI
    const newProps = {
      ...localProps,
      [property]: value
    };
    setLocalProps(newProps);
    
    // Update store with new props
    updateElement(elementId, {
      props: newProps
    });
  };

  // Close handler - stable reference
  const handleClose = () => {
    selectElement(null);
    if (onClose) onClose();
  };

  return (
    <div className="ev2-w-full ev2-bg-white ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-shadow-lg ev2-flex ev2-flex-col ev2-h-full">
      {/* Header */}
      <div className="ev2-p-4 ev2-border-b ev2-border-gray-200 ev2-flex ev2-justify-between ev2-items-center">
        <h3 className="ev2-text-lg ev2-font-semibold ev2-text-gray-900">
          {element.type.charAt(0).toUpperCase() + element.type.slice(1)} Properties
        </h3>
        <button
          onClick={handleClose}
          className="ev2-text-gray-400 hover:ev2-text-gray-600"
        >
          ✕
        </button>
      </div>

      {/* Content */}
      <div className="ev2-flex-1 ev2-overflow-y-auto ev2-p-4">
        <div className="ev2-space-y-4">
          
          {/* Email Wrapper Controls */}
          {element.type === 'emailWrapper' && (
            <div className="ev2-p-4 ev2-bg-purple-50 ev2-border ev2-border-purple-200 ev2-rounded ev2-space-y-3">
              <div>
                <h4 className="ev2-text-sm ev2-font-medium ev2-text-purple-800 ev2-mb-2">📧 Email Wrapper</h4>
                <p className="ev2-text-sm ev2-text-purple-700">
                  The outer email client container. Controls the overall background that surrounds your email.
                </p>
              </div>
            </div>
          )}

          {/* Email Container Controls */}
          {element.type === 'emailContainer' && (
            <div className="ev2-p-4 ev2-bg-green-50 ev2-border ev2-border-green-200 ev2-rounded ev2-space-y-3">
              <div>
                <h4 className="ev2-text-sm ev2-font-medium ev2-text-green-800 ev2-mb-2">📄 Email Container</h4>
                <p className="ev2-text-sm ev2-text-green-700">
                  The main email content area. This is your email canvas with width, styling, and spacing controls.
                </p>
              </div>
              
              {/* Width Options */}
              <div>
                <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                  Container Width
                </label>
                <div className="ev2-flex ev2-gap-2">
                  {['600px', '700px'].map(width => (
                    <button
                      key={width}
                      type="button"
                      onClick={() => updateProperty('width', width)}
                      className={`ev2-px-3 ev2-py-1 ev2-text-xs ev2-rounded ev2-border ev2-transition-colors ${
                        localProps.width === width 
                          ? 'ev2-bg-green-100 ev2-border-green-300 ev2-text-green-700' 
                          : 'ev2-bg-white ev2-border-gray-300 ev2-text-gray-700 hover:ev2-bg-gray-50'
                      }`}
                    >
                      {width}
                    </button>
                  ))}
                </div>
                <p className="ev2-text-xs ev2-text-gray-500 ev2-mt-1">
                  600px is industry standard. 700px for wider email designs.
                </p>
              </div>
            </div>
          )}

          {/* Section Info & Controls */}
          {element.type === 'section' && (
            <div className="ev2-p-4 ev2-bg-blue-50 ev2-border ev2-border-blue-200 ev2-rounded ev2-space-y-3">
              <div>
                <h4 className="ev2-text-sm ev2-font-medium ev2-text-blue-800 ev2-mb-2">📦 Section Container</h4>
                <p className="ev2-text-sm ev2-text-blue-700">
                  Container element with spacing, background, and border controls.
                </p>
              </div>
              
              {/* Full Width Toggle */}
              <div className="ev2-flex ev2-items-center ev2-gap-3">
                <button
                  type="button"
                  onClick={() => updateProperty('fullWidth', !localProps.fullWidth)}
                  className={`ev2-relative ev2-inline-flex ev2-h-6 ev2-w-11 ev2-flex-shrink-0 ev2-cursor-pointer ev2-rounded-full ev2-border-2 ev2-border-transparent ev2-transition-colors ev2-duration-200 ev2-ease-in-out ev2-focus:outline-none ev2-focus:ring-2 ev2-focus:ring-blue-500 ev2-focus:ring-offset-2 ${
                    localProps.fullWidth 
                      ? 'ev2-bg-blue-600' 
                      : 'ev2-bg-gray-200'
                  }`}
                >
                  <span
                    className={`ev2-pointer-events-none ev2-inline-block ev2-h-5 ev2-w-5 ev2-transform ev2-rounded-full ev2-bg-white ev2-shadow ev2-ring-0 ev2-transition ev2-duration-200 ev2-ease-in-out ${
                      localProps.fullWidth 
                        ? 'ev2-translate-x-5' 
                        : 'ev2-translate-x-0'
                    }`}
                  />
                </button>
                <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700">
                  Full Width
                </label>
              </div>
              <p className="ev2-text-xs ev2-text-gray-600">
                {localProps.fullWidth 
                  ? 'Section spans full email width' 
                  : 'Section respects container width (600px default)'
                }
              </p>
            </div>
          )}

          {/* Text Element Controls */}
          {element.type === 'text' && (
            <div className="ev2-space-y-4">
              <div className="ev2-p-4 ev2-bg-blue-50 ev2-border ev2-border-blue-200 ev2-rounded ev2-space-y-3">
                <h4 className="ev2-text-sm ev2-font-medium ev2-text-blue-800 ev2-mb-3">📝 Text Content</h4>
                
                {/* Rich Text Content */}
                <div>
                  <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                    Content
                  </label>
                  <EmailTextEditor
                    value={localProps.content || '<p>Enter your text here...</p>'}
                    onChange={(emailHTML) => updateProperty('content', emailHTML)}
                    placeholder="Enter your text here..."
                    className="ev2-border ev2-border-gray-300 ev2-rounded-md"
                    lineHeight={localProps.lineHeight || 1.6}
                  />
                  <p className="ev2-text-xs ev2-text-gray-500 ev2-mt-1">
                    Rich text editor - Email-optimized HTML with inline styles
                  </p>
                </div>

                {/* Font Family */}
                <div>
                  <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                    Font Family
                  </label>
                  <select
                    value={localProps.fontFamily || 'Arial, sans-serif'}
                    onChange={(e) => updateProperty('fontFamily', e.target.value)}
                    className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md ev2-text-sm"
                  >
                    <option value="Arial, sans-serif">Arial</option>
                    <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                    <option value="'Times New Roman', Times, serif">Times New Roman</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="'Courier New', Courier, monospace">Courier New</option>
                    <option value="Verdana, sans-serif">Verdana</option>
                    <option value="Tahoma, sans-serif">Tahoma</option>
                    <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
                  </select>
                </div>

                {/* Font Size and Color */}
                <div className="ev2-grid ev2-grid-cols-2 ev2-gap-3">
                  <div>
                    <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                      Font Size
                    </label>
                    <input
                      type="number"
                      value={localProps.fontSize || 16}
                      onChange={(e) => updateProperty('fontSize', parseInt(e.target.value) || 16)}
                      className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md ev2-text-sm"
                      min="8"
                      max="72"
                    />
                  </div>
                  <div>
                    <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                      Text Color
                    </label>
                    <input
                      type="color"
                      value={localProps.color || '#333333'}
                      onChange={(e) => updateProperty('color', e.target.value)}
                      className="ev2-w-full ev2-h-10 ev2-border ev2-border-gray-300 ev2-rounded-md"
                    />
                  </div>
                </div>

                {/* Text Alignment */}
                <div>
                  <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                    Text Alignment
                  </label>
                  <div className="ev2-flex ev2-gap-1">
                    {['left', 'center', 'right', 'justify'].map(align => (
                      <button
                        key={align}
                        type="button"
                        onClick={() => updateProperty('align', align)}
                        className={`ev2-px-3 ev2-py-2 ev2-text-xs ev2-rounded ev2-border ev2-transition-colors ev2-capitalize ${
                          localProps.align === align 
                            ? 'ev2-bg-blue-100 ev2-border-blue-300 ev2-text-blue-700' 
                            : 'ev2-bg-white ev2-border-gray-300 ev2-text-gray-700 hover:ev2-bg-gray-50'
                        }`}
                      >
                        {align}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Line Height */}
                <div>
                  <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                    Line Height
                  </label>
                  <input
                    type="number"
                    step="0.1"
                    value={localProps.lineHeight || 1.6}
                    onChange={(e) => updateProperty('lineHeight', parseFloat(e.target.value) || 1.6)}
                    className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md ev2-text-sm"
                    min="1"
                    max="3"
                  />
                  <p className="ev2-text-xs ev2-text-gray-500 ev2-mt-1">
                    1.6 is recommended for email readability
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Email Container Effects */}
          {element.type === 'emailContainer' && capabilities.effects && (
            <div className="ev2-space-y-3">
              {capabilities.effects.borderRadius && (
                <div>
                  <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                    Border Radius
                  </label>
                  <input
                    type="number"
                    value={localProps.borderRadius || 0}
                    onChange={(e) => updateProperty('borderRadius', parseInt(e.target.value) || 0)}
                    className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md ev2-text-sm"
                    min="0"
                    max="50"
                  />
                </div>
              )}
              
              {capabilities.effects.shadow && (
                <div>
                  <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2 ev2-block">
                    Box Shadow
                  </label>
                  <select
                    value={localProps.boxShadow || 'none'}
                    onChange={(e) => updateProperty('boxShadow', e.target.value)}
                    className="ev2-w-full ev2-px-3 ev2-py-2 ev2-border ev2-border-gray-300 ev2-rounded-md ev2-text-sm"
                  >
                    <option value="none">None</option>
                    <option value="0 1px 3px rgba(0,0,0,0.1)">Light</option>
                    <option value="0 4px 6px rgba(0,0,0,0.1)">Medium</option>
                    <option value="0 10px 15px rgba(0,0,0,0.1)">Heavy</option>
                    <option value="0 20px 25px rgba(0,0,0,0.15)">Extra Heavy</option>
                  </select>
                </div>
              )}
            </div>
          )}

          {/* Spacing Compass - Advanced N/E/S/W control system */}
          {(capabilities.spacing?.margin || capabilities.spacing?.padding || capabilities.spacing?.border || capabilities.background) && (
            <div>
              <SpacingCompass
                label={element.type === 'emailWrapper' ? 'Email Wrapper Background' : 'Element Spacing & Background'}
                margin={localProps.margin || { top: 0, right: 0, bottom: 0, left: 0 }}
                border={localProps.border || { top: 0, right: 0, bottom: 0, left: 0 }}
                borderStyle={localProps.borderStyle || 'solid'}
                borderColor={localProps.borderColor || '#000000'}
                padding={localProps.padding || { top: 20, right: 20, bottom: 20, left: 20 }}
                backgroundColor={localProps.backgroundColor || (element.type === 'emailWrapper' ? '#f5f5f5' : '#ffffff')}
                backgroundImage={localProps.backgroundImage || ''}
                backgroundImageId={localProps.backgroundImageId || null}
                backgroundSize={localProps.backgroundSize || 'cover'}
                backgroundPosition={localProps.backgroundPosition || 'center'}
                backgroundRepeat={localProps.backgroundRepeat || 'no-repeat'}
                onMarginChange={(margin) => updateProperty('margin', margin)}
                onBorderChange={(border) => updateProperty('border', border)}
                onBorderStyleChange={(style) => updateProperty('borderStyle', style)}
                onBorderColorChange={(color) => updateProperty('borderColor', color)}
                onPaddingChange={(padding) => updateProperty('padding', padding)}
                onBackgroundColorChange={(color) => updateProperty('backgroundColor', color)}
                onBackgroundImageChange={(image) => updateProperty('backgroundImage', image)}
                onBackgroundImageIdChange={(id) => updateProperty('backgroundImageId', id)}
                onBackgroundSizeChange={(size) => updateProperty('backgroundSize', size)}
                onBackgroundPositionChange={(position) => updateProperty('backgroundPosition', position)}
                onBackgroundRepeatChange={(repeat) => updateProperty('backgroundRepeat', repeat)}
                min={0}
                max={200}
                unit="px"
              />
            </div>
          )}

        </div>
      </div>
    </div>
  );
}

// Helper function to find element by ID
function findElementById(elements, id) {
  for (const element of elements) {
    if (element.id === id) {
      return element;
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        for (const column of element.children) {
          const found = findElementById(column.children || [], id);
          if (found) return found;
        }
      } else {
        const found = findElementById(element.children, id);
        if (found) return found;
      }
    }
  }
  return null;
}

// Memo wrapper to prevent unnecessary re-renders
const OptimizedPropertyPanel = React.memo(OptimizedPropertyPanelInner, (prevProps, nextProps) => {
  // Only re-render if elementId changes - return true to SKIP re-render, false to RE-RENDER
  const elementIdSame = prevProps.elementId === nextProps.elementId;
  return elementIdSame; // Skip re-render if elementId is the same
});

export default OptimizedPropertyPanel;