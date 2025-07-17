import React from 'react';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import { X, Check } from 'lucide-react';

// Import all property components
import SectionProperties from './Properties/SectionProperties';
import TextProperties from './Properties/TextProperties';
import ButtonProperties from './Properties/ButtonProperties';
import ImageProperties from './Properties/ImageProperties';
import SpacerProperties from './Properties/SpacerProperties';
import DividerProperties from './Properties/DividerProperties';
import ColumnsProperties from './Properties/ColumnsProperties';
import SocialProperties from './Properties/SocialProperties';
import FormDataProperties from './Properties/FormDataProperties';

// Map element types to their property components
const propertyComponents = {
  section: SectionProperties,
  text: TextProperties,
  button: ButtonProperties,
  image: ImageProperties,
  spacer: SpacerProperties,
  divider: DividerProperties,
  columns: ColumnsProperties,
  social: SocialProperties,
  formData: FormDataProperties,
};

function PropertyPanel() {
  const { selectedElement, updateElement, deleteElement, selectElement } = useEmailBuilder();

  if (!selectedElement) {
    return (
      <div className="ev2-p-6 ev2-bg-white ev2-rounded-lg ev2-shadow-sm ev2-border ev2-border-gray-200">
        <div className="ev2-text-center ev2-text-gray-500">
          <svg className="ev2-mx-auto ev2-h-12 ev2-w-12 ev2-text-gray-300 ev2-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" 
            />
          </svg>
          <p className="ev2-text-sm">Select an element to edit its properties</p>
        </div>
      </div>
    );
  }

  const PropertyComponent = propertyComponents[selectedElement.type];

  if (!PropertyComponent) {
    return (
      <div className="ev2-p-6 ev2-bg-white ev2-rounded-lg ev2-shadow-sm ev2-border ev2-border-gray-200">
        <p className="ev2-text-sm ev2-text-red-600">
          No property editor available for {selectedElement.type}
        </p>
      </div>
    );
  }

  const handleChange = (updates) => {
    updateElement(selectedElement.id, {
      props: {
        ...selectedElement.props,
        ...updates,
      },
    });
  };

  return (
    <div className="ev2-bg-white ev2-h-full ev2-flex ev2-flex-col">
      <div className="ev2-px-4 ev2-py-3 ev2-border-b ev2-border-gray-200 ev2-bg-gray-50">
        <div className="ev2-flex ev2-items-center ev2-justify-between">
          <div className="ev2-flex ev2-items-center ev2-gap-2">
            <span className="ev2-text-lg">{selectedElement.type === 'text' ? '📝' : selectedElement.type === 'button' ? '🔘' : selectedElement.type === 'section' ? '📦' : '🧩'}</span>
            <h3 className="ev2-text-base ev2-font-medium ev2-text-gray-900">
              {selectedElement.type.charAt(0).toUpperCase() + selectedElement.type.slice(1)} Settings
            </h3>
          </div>
          <button
            onClick={() => selectElement(null)}
            className="ev2-p-1 ev2-text-gray-400 hover:ev2-text-gray-600 ev2-rounded-full hover:ev2-bg-gray-200 ev2-transition-all"
            title="Close settings"
          >
            <X className="ev2-w-4 ev2-h-4" />
          </button>
        </div>
      </div>

      <div className="ev2-flex-1 ev2-overflow-y-auto">
        <PropertyComponent
          element={selectedElement}
          onChange={handleChange}
        />
      </div>

      <div className="ev2-border-t ev2-border-gray-200 ev2-px-4 ev2-py-3 ev2-bg-gray-50">
        <div className="ev2-flex ev2-items-center ev2-justify-between ev2-gap-3">
          <button
            onClick={() => {
              if (confirm('Are you sure you want to delete this element?')) {
                deleteElement(selectedElement.id);
              }
            }}
            className="ev2-px-3 ev2-py-1.5 ev2-text-sm ev2-text-red-600 hover:ev2-text-red-700 hover:ev2-bg-red-50 ev2-rounded-md ev2-transition-colors"
          >
            Delete
          </button>
          <button
            onClick={() => selectElement(null)}
            className="ev2-px-4 ev2-py-1.5 ev2-bg-primary-600 ev2-text-white ev2-text-sm ev2-rounded-md hover:ev2-bg-primary-700 ev2-transition-colors ev2-font-medium"
          >
            Done
          </button>
        </div>
      </div>
    </div>
  );
}

export default PropertyPanel;