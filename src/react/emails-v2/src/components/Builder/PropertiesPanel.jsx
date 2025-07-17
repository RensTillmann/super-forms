import React from 'react';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import TextProperties from './Properties/TextProperties';
import ButtonProperties from './Properties/ButtonProperties';
import ImageProperties from './Properties/ImageProperties';
import SectionProperties from './Properties/SectionProperties';
import SpacerProperties from './Properties/SpacerProperties';
import DividerProperties from './Properties/DividerProperties';
import ColumnsProperties from './Properties/ColumnsProperties';
import SocialProperties from './Properties/SocialProperties';
import FormDataProperties from './Properties/FormDataProperties';

const propertyComponents = {
  text: TextProperties,
  button: ButtonProperties,
  image: ImageProperties,
  section: SectionProperties,
  spacer: SpacerProperties,
  divider: DividerProperties,
  columns: ColumnsProperties,
  social: SocialProperties,
  formData: FormDataProperties,
};

function PropertiesPanel() {
  const { selectedElementId, elements, updateElement, deleteElement, selectElement } = useEmailBuilder();
  
  // Find selected element
  const findElement = (elements, id) => {
    for (const element of elements) {
      if (element.id === id) return element;
      if (element.children) {
        const found = findElement(element.children, id);
        if (found) return found;
      }
    }
    return null;
  };
  
  const selectedElement = findElement(elements, selectedElementId);
  
  if (!selectedElement) return null;
  
  const PropertyComponent = propertyComponents[selectedElement.type];

  const handleUpdate = (updates) => {
    updateElement(selectedElementId, (element) => ({
      ...element,
      props: {
        ...element.props,
        ...updates,
      },
    }));
  };

  const handleDelete = () => {
    if (window.confirm('Are you sure you want to delete this element?')) {
      deleteElement(selectedElementId);
      selectElement(null);
    }
  };

  return (
    <div className="ev2-h-full ev2-flex ev2-flex-col">
      {/* Header */}
      <div className="ev2-p-4 ev2-border-b ev2-flex ev2-items-center ev2-justify-between">
        <h3 className="ev2-font-medium ev2-text-gray-900">
          {selectedElement.type.charAt(0).toUpperCase() + selectedElement.type.slice(1)} Properties
        </h3>
        <button
          onClick={() => selectElement(null)}
          className="ev2-p-1 hover:ev2-bg-gray-100 ev2-rounded"
        >
          <svg className="ev2-w-5 ev2-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      {/* Properties */}
      <div className="ev2-flex-1 ev2-overflow-y-auto ev2-p-4">
        {PropertyComponent ? (
          <PropertyComponent
            element={selectedElement}
            onChange={handleUpdate}
          />
        ) : (
          <p className="ev2-text-gray-500 ev2-text-sm">
            No properties available for this element type.
          </p>
        )}
      </div>

      {/* Actions */}
      <div className="ev2-p-4 ev2-border-t">
        <button
          onClick={handleDelete}
          className="ev2-w-full ev2-px-4 ev2-py-2 ev2-bg-red-50 ev2-text-red-600 ev2-rounded ev2-font-medium hover:ev2-bg-red-100 ev2-transition-colors"
        >
          Delete Element
        </button>
      </div>
    </div>
  );
}

export default PropertiesPanel;