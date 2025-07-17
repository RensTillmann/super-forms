import React, { useEffect } from 'react';
import Canvas from './Canvas';
import ElementPalette from './ElementPalette';
import PropertyPanel from './PropertyPanel';
import useEmailBuilder from '../../hooks/useEmailBuilder';

function EmailBuilder({ email, onChange }) {
  const { selectedElementId, setElements, elements } = useEmailBuilder();

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
        <div className="ev2-w-80 ev2-bg-white ev2-border-l ev2-overflow-y-auto">
          <PropertyPanel />
        </div>
      )}
    </div>
  );
}

export default EmailBuilder;