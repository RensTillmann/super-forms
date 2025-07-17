import React, { useEffect } from 'react';
import Canvas from './Canvas';
import ElementPalette from './ElementPalette';
import CapabilityBasedPropertyPanel from '../PropertyPanels/CapabilityBasedPropertyPanel';
import useEmailBuilder from '../../hooks/useEmailBuilder';

function EmailBuilder({ email, onChange }) {
  const { selectedElementId, setSelectedElementId, setElements, elements, updateElement } = useEmailBuilder();

  // Initialize elements from email template if it exists
  useEffect(() => {
    if (email?.template && typeof email.template === 'object' && email.template.elements) {
      setElements(email.template.elements);
    }
  }, [email?.template, setElements]);

  // Handle changes from the builder
  const handleBuilderChange = (html) => {
    if (onChange) {
      // Store both the elements and generated HTML
      onChange({
        elements: elements,
        html: html
      });
    }
  };

  return (
    <div className="ev2-flex ev2-h-full ev2-bg-gray-50">
      {/* Element Palette */}
      <div className="ev2-w-20 ev2-bg-white ev2-border-r ev2-overflow-y-auto">
        <ElementPalette />
      </div>

      {/* Canvas */}
      <div className="ev2-flex-1 ev2-overflow-auto">
        <Canvas email={email} onChange={handleBuilderChange} />
      </div>

      {/* Properties Panel */}
      {selectedElementId && (
        <CapabilityBasedPropertyPanel
          element={elements.find(el => el.id === selectedElementId)}
          onElementUpdate={updateElement}
          onClose={() => setSelectedElementId(null)}
        />
      )}
    </div>
  );
}

export default EmailBuilder;