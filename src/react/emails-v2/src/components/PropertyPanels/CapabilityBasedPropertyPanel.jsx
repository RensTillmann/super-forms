import React, { useState } from 'react';
import { getElementCapabilities, hasCapability } from '../../capabilities/elementCapabilities';

/**
 * CapabilityBasedPropertyPanel - Dynamic property panel based on element capabilities
 * 
 * This component automatically generates property controls based on the element's
 * capabilities definition, eliminating the need for element-specific property panels.
 */
function CapabilityBasedPropertyPanel({ 
  element, 
  onElementUpdate,
  onClose 
}) {
  const [activeTab, setActiveTab] = useState('content');
  const capabilities = getElementCapabilities(element.type);
  
  if (!element) {
    return null;
  }

  // Generate available tabs based on capabilities
  const availableTabs = [];
  
  // Content tab - always available
  availableTabs.push({ id: 'content', label: 'Content', icon: '📝' });
  
  // Typography tab
  if (capabilities.typography) {
    availableTabs.push({ id: 'typography', label: 'Typography', icon: 'Aa' });
  }
  
  // Spacing tab
  if (capabilities.spacing) {
    availableTabs.push({ id: 'spacing', label: 'Spacing', icon: '📐' });
  }
  
  // Background tab
  if (capabilities.background) {
    availableTabs.push({ id: 'background', label: 'Background', icon: '🎨' });
  }
  
  // Interactive tab
  if (capabilities.interactive) {
    availableTabs.push({ id: 'interactive', label: 'Links', icon: '🔗' });
  }
  
  // Layout tab
  if (capabilities.layout || capabilities.resizable) {
    availableTabs.push({ id: 'layout', label: 'Layout', icon: '📋' });
  }

  const updateProperty = (property, value) => {
    if (onElementUpdate) {
      onElementUpdate(element.id, property, value);
    }
  };

  return (
    <div className="ev2-fixed ev2-right-4 ev2-top-4 ev2-bottom-4 ev2-w-80 ev2-bg-white ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-shadow-lg ev2-z-50 ev2-flex ev2-flex-col">
      {/* Header */}
      <div className="ev2-p-4 ev2-border-b ev2-border-gray-200 ev2-flex ev2-items-center ev2-justify-between">
        <h3 className="ev2-text-lg ev2-font-semibold ev2-text-gray-900">
          {element.type} Properties
        </h3>
        <button
          onClick={onClose}
          className="ev2-text-gray-400 hover:ev2-text-gray-600 ev2-transition-colors"
        >
          <svg className="ev2-w-5 ev2-h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
          </svg>
        </button>
      </div>

      {/* Tabs */}
      <div className="ev2-border-b ev2-border-gray-200">
        <nav className="ev2-flex ev2-space-x-0" aria-label="Tabs">
          {availableTabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`ev2-flex-1 ev2-py-2 ev2-px-1 ev2-text-center ev2-text-xs ev2-font-medium ev2-transition-colors ${
                activeTab === tab.id
                  ? 'ev2-border-b-2 ev2-border-blue-500 ev2-text-blue-600'
                  : 'ev2-text-gray-500 hover:ev2-text-gray-700'
              }`}
            >
              <div className="ev2-text-lg">{tab.icon}</div>
              <div>{tab.label}</div>
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      <div className="ev2-flex-1 ev2-overflow-y-auto ev2-p-4">
        {activeTab === 'content' && (
          <ContentTab element={element} capabilities={capabilities} onUpdate={updateProperty} />
        )}
        {activeTab === 'typography' && capabilities.typography && (
          <TypographyTab element={element} capabilities={capabilities} onUpdate={updateProperty} />
        )}
        {activeTab === 'spacing' && capabilities.spacing && (
          <SpacingTab element={element} capabilities={capabilities} onUpdate={updateProperty} />
        )}
        {activeTab === 'background' && capabilities.background && (
          <BackgroundTab element={element} capabilities={capabilities} onUpdate={updateProperty} />
        )}
        {activeTab === 'interactive' && capabilities.interactive && (
          <InteractiveTab element={element} capabilities={capabilities} onUpdate={updateProperty} />
        )}
        {activeTab === 'layout' && (capabilities.layout || capabilities.resizable) && (
          <LayoutTab element={element} capabilities={capabilities} onUpdate={updateProperty} />
        )}
      </div>
    </div>
  );
}

// Content Tab Component
function ContentTab({ element, capabilities, onUpdate }) {
  const { props = {} } = element;

  return (
    <div className="ev2-space-y-4">
      {element.type === 'text' && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Content
          </label>
          <textarea
            value={props.content || ''}
            onChange={(e) => onUpdate('content', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
            rows={4}
            placeholder="Enter your text content..."
          />
        </div>
      )}

      {element.type === 'button' && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Button Text
          </label>
          <input
            type="text"
            value={props.text || ''}
            onChange={(e) => onUpdate('text', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
            placeholder="Button text..."
          />
        </div>
      )}

      {element.type === 'image' && (
        <>
          <div>
            <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
              Image URL
            </label>
            <input
              type="url"
              value={props.src || ''}
              onChange={(e) => onUpdate('src', e.target.value)}
              className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
              placeholder="https://example.com/image.jpg"
            />
          </div>
          <div>
            <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
              Alt Text
            </label>
            <input
              type="text"
              value={props.alt || ''}
              onChange={(e) => onUpdate('alt', e.target.value)}
              className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
              placeholder="Image description..."
            />
          </div>
        </>
      )}

      {element.type === 'spacer' && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Height (px)
          </label>
          <input
            type="number"
            value={props.height || 20}
            onChange={(e) => onUpdate('height', parseInt(e.target.value))}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
            min="1"
            max="200"
          />
        </div>
      )}
    </div>
  );
}

// Typography Tab Component
function TypographyTab({ element, capabilities, onUpdate }) {
  const { props = {} } = element;

  return (
    <div className="ev2-space-y-4">
      {capabilities.typography.font && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Font Family
          </label>
          <select
            value={props.fontFamily || 'Arial, sans-serif'}
            onChange={(e) => onUpdate('fontFamily', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
          >
            <option value="Arial, sans-serif">Arial</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="'Times New Roman', serif">Times New Roman</option>
            <option value="Helvetica, sans-serif">Helvetica</option>
            <option value="Verdana, sans-serif">Verdana</option>
          </select>
        </div>
      )}

      {capabilities.typography.size && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Font Size (px)
          </label>
          <input
            type="number"
            value={props.fontSize || 16}
            onChange={(e) => onUpdate('fontSize', parseInt(e.target.value))}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
            min="8"
            max="72"
          />
        </div>
      )}

      {capabilities.typography.color && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Text Color
          </label>
          <input
            type="color"
            value={props.color || '#000000'}
            onChange={(e) => onUpdate('color', e.target.value)}
            className="ev2-w-full ev2-h-10 ev2-border ev2-border-gray-300 ev2-rounded"
          />
        </div>
      )}

      {capabilities.typography.weight && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Font Weight
          </label>
          <select
            value={props.fontWeight || 'normal'}
            onChange={(e) => onUpdate('fontWeight', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
          >
            <option value="normal">Normal</option>
            <option value="bold">Bold</option>
            <option value="lighter">Lighter</option>
            <option value="bolder">Bolder</option>
          </select>
        </div>
      )}
    </div>
  );
}

// Spacing Tab Component
function SpacingTab({ element, capabilities, onUpdate }) {
  const { props = {} } = element;
  const margin = props.margin || { top: 0, right: 0, bottom: 0, left: 0 };
  const padding = props.padding || { top: 0, right: 0, bottom: 0, left: 0 };

  const updateMargin = (side, value) => {
    onUpdate('margin', { ...margin, [side]: parseInt(value) });
  };

  const updatePadding = (side, value) => {
    onUpdate('padding', { ...padding, [side]: parseInt(value) });
  };

  return (
    <div className="ev2-space-y-6">
      {capabilities.spacing.margin && (
        <div>
          <h4 className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2">Margin</h4>
          <div className="ev2-grid ev2-grid-cols-2 ev2-gap-2">
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Top</label>
              <input
                type="number"
                value={margin.top}
                onChange={(e) => updateMargin('top', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Right</label>
              <input
                type="number"
                value={margin.right}
                onChange={(e) => updateMargin('right', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Bottom</label>
              <input
                type="number"
                value={margin.bottom}
                onChange={(e) => updateMargin('bottom', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Left</label>
              <input
                type="number"
                value={margin.left}
                onChange={(e) => updateMargin('left', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
          </div>
        </div>
      )}

      {capabilities.spacing.padding && (
        <div>
          <h4 className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-2">Padding</h4>
          <div className="ev2-grid ev2-grid-cols-2 ev2-gap-2">
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Top</label>
              <input
                type="number"
                value={padding.top}
                onChange={(e) => updatePadding('top', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Right</label>
              <input
                type="number"
                value={padding.right}
                onChange={(e) => updatePadding('right', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Bottom</label>
              <input
                type="number"
                value={padding.bottom}
                onChange={(e) => updatePadding('bottom', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
            <div>
              <label className="ev2-block ev2-text-xs ev2-text-gray-600">Left</label>
              <input
                type="number"
                value={padding.left}
                onChange={(e) => updatePadding('left', e.target.value)}
                className="ev2-w-full ev2-p-1 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
                min="0"
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// Background Tab Component  
function BackgroundTab({ element, capabilities, onUpdate }) {
  const { props = {} } = element;

  return (
    <div className="ev2-space-y-4">
      {capabilities.background.color && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Background Color
          </label>
          <input
            type="color"
            value={props.backgroundColor || '#ffffff'}
            onChange={(e) => onUpdate('backgroundColor', e.target.value)}
            className="ev2-w-full ev2-h-10 ev2-border ev2-border-gray-300 ev2-rounded"
          />
        </div>
      )}

      {capabilities.background.image && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Background Image URL
          </label>
          <input
            type="url"
            value={props.backgroundImage || ''}
            onChange={(e) => onUpdate('backgroundImage', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
            placeholder="https://example.com/background.jpg"
          />
        </div>
      )}
    </div>
  );
}

// Interactive Tab Component
function InteractiveTab({ element, capabilities, onUpdate }) {
  const { props = {} } = element;

  return (
    <div className="ev2-space-y-4">
      {capabilities.interactive.href && (
        <>
          <div>
            <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
              Link URL
            </label>
            <input
              type="url"
              value={props.href || props.link || ''}
              onChange={(e) => onUpdate(element.type === 'button' ? 'href' : 'link', e.target.value)}
              className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
              placeholder="https://example.com"
            />
          </div>
          
          {capabilities.interactive.target && (
            <div>
              <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
                Target
              </label>
              <select
                value={props.target || '_blank'}
                onChange={(e) => onUpdate('target', e.target.value)}
                className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
              >
                <option value="_blank">New Window</option>
                <option value="_self">Same Window</option>
              </select>
            </div>
          )}
        </>
      )}
    </div>
  );
}

// Layout Tab Component
function LayoutTab({ element, capabilities, onUpdate }) {
  const { props = {} } = element;

  return (
    <div className="ev2-space-y-4">
      {capabilities.alignment && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Alignment
          </label>
          <select
            value={props.align || 'left'}
            onChange={(e) => onUpdate('align', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
          >
            <option value="left">Left</option>
            <option value="center">Center</option>
            <option value="right">Right</option>
          </select>
        </div>
      )}

      {capabilities.layout?.fullWidth && (
        <div className="ev2-flex ev2-items-center">
          <input
            type="checkbox"
            id="fullWidth"
            checked={props.fullWidth || false}
            onChange={(e) => onUpdate('fullWidth', e.target.checked)}
            className="ev2-mr-2"
          />
          <label htmlFor="fullWidth" className="ev2-text-sm ev2-text-gray-700">
            Full Width
          </label>
        </div>
      )}

      {capabilities.resizable?.horizontal && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Width
          </label>
          <input
            type="text"
            value={props.width || 'auto'}
            onChange={(e) => onUpdate('width', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
            placeholder="auto, 100px, 50%, etc."
          />
        </div>
      )}

      {capabilities.resizable?.vertical && (
        <div>
          <label className="ev2-block ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-mb-1">
            Height
          </label>
          <input
            type="text"
            value={props.height || 'auto'}
            onChange={(e) => onUpdate('height', e.target.value)}
            className="ev2-w-full ev2-p-2 ev2-border ev2-border-gray-300 ev2-rounded ev2-text-sm"
            placeholder="auto, 100px, 50%, etc."
          />
        </div>
      )}
    </div>
  );
}

export default CapabilityBasedPropertyPanel;