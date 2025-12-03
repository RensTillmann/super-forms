import React, { useState, useEffect } from 'react';
import { getElementCapabilities } from '../capabilities/elementCapabilities';
import { useEmailBuilderStore } from '../hooks/useEmailBuilder';
import SpacingCompass from '../fields/SpacingCompass';
// Text editors removed - editing happens inline in the preview
// import EmailTextEditor from '../fields/EmailTextEditor';
// import EmailTinyMCEEditor from '../fields/EmailTinyMCEEditor';
import ErrorBoundary from '../ErrorBoundary';

/**
 * Simple Property Panel - Only re-renders when elementId changes
 */
function OptimizedPropertyPanelInner({ elementId, onClose }) {

  // Get the element once and store it locally
  const [element, setElement] = useState(null);
  const [localProps, setLocalProps] = useState({});
  
  // Editor selection state - simplified to only email-compatible editors
  const [selectedEditor, setSelectedEditor] = useState('quill'); // 'quill' or 'tinymce' only

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
      <div className="w-full bg-white border border-gray-200 rounded-lg shadow-lg p-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Loading...</h3>
        <p className="text-sm text-gray-600">Loading element properties...</p>
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
    <div className="w-full bg-white border border-gray-200 rounded-lg shadow-lg flex flex-col h-full">
      {/* Header */}
      <div className="p-4 border-b border-gray-200 flex justify-between items-center">
        <h3 className="text-lg font-semibold text-gray-900">
          {element.type.charAt(0).toUpperCase() + element.type.slice(1)} Properties
        </h3>
        <button
          onClick={handleClose}
          className="text-gray-400 hover:text-gray-600"
        >
          ✕
        </button>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-y-auto p-4">
        <div className="space-y-4">
          
          {/* Email Wrapper Controls */}
          {element.type === 'emailWrapper' && (
            <div className="p-4 bg-purple-50 border border-purple-200 rounded space-y-3">
              <div>
                <h4 className="text-sm font-medium text-purple-800 mb-2">📧 Email Wrapper</h4>
                <p className="text-sm text-purple-700">
                  The outer email client container. Controls the overall background that surrounds your email.
                </p>
              </div>
            </div>
          )}

          {/* Email Container Controls */}
          {element.type === 'emailContainer' && (
            <div className="p-4 bg-green-50 border border-green-200 rounded space-y-3">
              <div>
                <h4 className="text-sm font-medium text-green-800 mb-2">📄 Email Container</h4>
                <p className="text-sm text-green-700">
                  The main email content area. This is your email canvas with width, styling, and spacing controls.
                </p>
              </div>
              
              {/* Width Options */}
              <div>
                <label className="text-sm font-medium text-gray-700 mb-2 block">
                  Container Width
                </label>
                <div className="flex gap-2">
                  {['600px', '700px'].map(width => (
                    <button
                      key={width}
                      type="button"
                      onClick={() => updateProperty('width', width)}
                      className={`px-3 py-1 text-xs rounded border transition-colors ${
                        localProps.width === width 
                          ? 'bg-green-100 border-green-300 text-green-700' 
                          : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
                      }`}
                    >
                      {width}
                    </button>
                  ))}
                </div>
                <p className="text-xs text-gray-500 mt-1">
                  600px is industry standard. 700px for wider email designs.
                </p>
              </div>
            </div>
          )}

          {/* Section Info & Controls */}
          {element.type === 'section' && (
            <div className="p-4 bg-blue-50 border border-blue-200 rounded space-y-3">
              <div>
                <h4 className="text-sm font-medium text-blue-800 mb-2">📦 Section Container</h4>
                <p className="text-sm text-blue-700">
                  Container element with spacing, background, and border controls.
                </p>
              </div>
              
              {/* Full Width Toggle */}
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => updateProperty('fullWidth', !localProps.fullWidth)}
                  className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ${
                    localProps.fullWidth 
                      ? 'bg-blue-600' 
                      : 'bg-gray-200'
                  }`}
                >
                  <span
                    className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                      localProps.fullWidth 
                        ? 'translate-x-5' 
                        : 'translate-x-0'
                    }`}
                  />
                </button>
                <label className="text-sm font-medium text-gray-700">
                  Full Width
                </label>
              </div>
              <p className="text-xs text-gray-600">
                {localProps.fullWidth 
                  ? 'Section spans full email width' 
                  : 'Section respects container width (600px default)'
                }
              </p>
            </div>
          )}

          {/* Text Element Controls */}
          {element.type === 'text' && (
            <div className="space-y-4">
              <div className="p-4 bg-blue-50 border border-blue-200 rounded space-y-3">
                <h4 className="text-sm font-medium text-blue-800 mb-3">📝 Text Content</h4>
                
                {/* Content editing happens inline in the preview - rich text editors removed from properties panel */}
                <div className="p-3 bg-white border border-blue-200 rounded">
                  <p className="text-sm text-gray-700">
                    ✏️ Click on the text in the preview to edit it directly. 
                  </p>
                  <p className="text-xs text-gray-600 mt-1">
                    The floating toolbar will appear when you select text, allowing you to format with bold, italic, underline, and more.
                  </p>
                </div>

                {/* Font Family */}
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Font Family
                  </label>
                  <select
                    value={localProps.fontFamily || 'Arial, sans-serif'}
                    onChange={(e) => updateProperty('fontFamily', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
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
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="text-sm font-medium text-gray-700 mb-2 block">
                      Font Size
                    </label>
                    <input
                      type="number"
                      value={localProps.fontSize || 16}
                      onChange={(e) => updateProperty('fontSize', parseInt(e.target.value) || 16)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                      min="8"
                      max="72"
                    />
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-700 mb-2 block">
                      Text Color
                    </label>
                    <input
                      type="color"
                      value={localProps.color || '#333333'}
                      onChange={(e) => updateProperty('color', e.target.value)}
                      className="w-full h-10 border border-gray-300 rounded-md"
                    />
                  </div>
                </div>

                {/* Text Alignment */}
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Text Alignment
                  </label>
                  <div className="flex gap-1">
                    {['left', 'center', 'right', 'justify'].map(align => (
                      <button
                        key={align}
                        type="button"
                        onClick={() => updateProperty('align', align)}
                        className={`px-3 py-2 text-xs rounded border transition-colors capitalize ${
                          localProps.align === align 
                            ? 'bg-blue-100 border-blue-300 text-blue-700' 
                            : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
                        }`}
                      >
                        {align}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Line Height */}
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Line Height
                  </label>
                  <input
                    type="number"
                    step="0.1"
                    value={localProps.lineHeight || 1.6}
                    onChange={(e) => updateProperty('lineHeight', parseFloat(e.target.value) || 1.6)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                    min="1"
                    max="3"
                  />
                  <p className="text-xs text-gray-500 mt-1">
                    1.6 is recommended for email readability
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Email Container Effects */}
          {element.type === 'emailContainer' && capabilities.effects && (
            <div className="space-y-3">
              {capabilities.effects.borderRadius && (
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Border Radius
                  </label>
                  <input
                    type="number"
                    value={localProps.borderRadius || 0}
                    onChange={(e) => updateProperty('borderRadius', parseInt(e.target.value) || 0)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                    min="0"
                    max="50"
                  />
                </div>
              )}
              
              {capabilities.effects.shadow && (
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Box Shadow
                  </label>
                  <select
                    value={localProps.boxShadow || 'none'}
                    onChange={(e) => updateProperty('boxShadow', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
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