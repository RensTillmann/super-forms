import React, { useEffect, useMemo } from 'react';
import Canvas from './Canvas';
import ElementPalette from './ElementPalette';
import OptimizedPropertyPanel from '../PropertyPanels/OptimizedPropertyPanel';
import { useEmailBuilderStore } from '../../hooks/useEmailBuilder';

// Simplified - no external functions needed

// Helper function to find element in tree
const findElement = (elements, id) => {
  for (const element of elements) {
    if (element.id === id) {
      return element;
    }
    if (element.children && element.children.length > 0) {
      if (element.type === 'columns') {
        // Handle column structure
        for (const column of element.children) {
          const found = findElement(column.children || [], id);
          if (found) return found;
        }
      } else {
        const found = findElement(element.children, id);
        if (found) return found;
      }
    }
  }
  return null;
};

function EmailBuilder({ email, onChange }) {
  const selectedElementId = useEmailBuilderStore(state => state.selectedElementId);
  
  console.log('EmailBuilder render - selectedElementId:', selectedElementId);

  // Initialize elements from email template if it exists
  useEffect(() => {
    if (email?.template && typeof email.template === 'object' && email.template.elements) {
      useEmailBuilderStore.getState().setElements(email.template.elements);
    }
  }, [email?.template]);

  // Handle changes from the builder
  const handleBuilderChange = (html) => {
    if (onChange) {
      // Store both the elements and generated HTML
      const currentElements = useEmailBuilderStore.getState().elements;
      onChange({
        elements: currentElements,
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

      {/* Properties Panel - Don't show for email wrapper (background-only element) */}
      {selectedElementId && (
        (() => {
          const elements = useEmailBuilderStore.getState().elements;
          const selectedElement = findElement(elements, selectedElementId);
          // Don't show property panel for email wrapper - it only uses the color picker
          if (selectedElement?.type === 'emailWrapper') {
            return null;
          }
          return (
            <div className="ev2-w-80 ev2-bg-gray-50 ev2-border-l ev2-overflow-y-auto">
              <OptimizedPropertyPanel
                elementId={selectedElementId}
              />
            </div>
          );
        })()
      )}
    </div>
  );
}

export default EmailBuilder;