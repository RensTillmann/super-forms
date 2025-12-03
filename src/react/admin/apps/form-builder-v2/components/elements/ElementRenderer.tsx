import React, { Suspense, lazy } from 'react';

// Lazy load element components
const TextInput = lazy(() => import('./basic/TextInput'));
const TextArea = lazy(() => import('./basic/TextArea'));
const Select = lazy(() => import('./choice/Select'));
const RadioCards = lazy(() => import('./choice/RadioCards'));
const CheckboxCards = lazy(() => import('./choice/CheckboxCards'));
const ColumnsContainer = lazy(() => import('./containers/ColumnsContainer'));

interface ElementRendererProps {
  element: {
    type: string;
    id: string;
    properties?: any;
  };
  updateElementProperty?: (id: string, property: string, value: any) => void;
}

interface CommonProps {
  className: string;
  disabled: boolean;
  style: {
    width: string;
    margin?: string;
    backgroundColor?: string;
    borderStyle?: string;
  };
}

export const ElementRenderer: React.FC<ElementRendererProps> = ({ element, updateElementProperty }) => {
  // Generate common props for form elements
  const commonProps: CommonProps = {
    className: "form-input",
    disabled: true,
    style: {
      width: element.properties?.width === 'full' ? '100%' : `${element.properties?.width || 100}%`,
      margin: element.properties?.margin,
      backgroundColor: element.properties?.backgroundColor,
      borderStyle: element.properties?.borderStyle,
    }
  };

  const renderElement = () => {
    switch (element.type) {
      case 'text':
      case 'email':
      case 'phone':
      case 'url':
      case 'password':
      case 'number':
      case 'number-formatted':
        return <TextInput element={element} commonProps={commonProps} />;
        
      case 'textarea':
        return <TextArea element={element} commonProps={commonProps} />;
        
      case 'select':
        return <Select element={element} commonProps={commonProps} />;
        
      case 'radio-cards':
        return <RadioCards element={element} updateElementProperty={updateElementProperty!} />;
        
      case 'checkbox-cards':
        return <CheckboxCards element={element} updateElementProperty={updateElementProperty!} />;
        
      case 'columns':
        return <ColumnsContainer element={element} />;
        
      default:
        // Fallback for elements not yet extracted
        return (
          <div className="border border-gray-300 rounded-md p-4 text-center text-gray-500">
            Element type: {element.type} (not yet extracted)
          </div>
        );
    }
  };

  return (
    <Suspense fallback={
      <div className="border border-gray-200 rounded-md p-4 text-center text-gray-400">
        Loading element...
      </div>
    }>
      {renderElement()}
    </Suspense>
  );
};

export default ElementRenderer;