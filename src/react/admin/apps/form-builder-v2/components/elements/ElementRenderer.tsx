import React, { Suspense, lazy, useMemo } from 'react';
import { useResolvedStyle } from '../../hooks/useResolvedStyle';
import { stylesToCSS, mergeWithElementProps, ResolvedStyles } from '../../../../lib/styleUtils';

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
    properties?: Record<string, unknown>;
    styleOverrides?: Record<string, Record<string, unknown>>;
  };
  updateElementProperty?: (id: string, property: string, value: unknown) => void;
}

export const ElementRenderer: React.FC<ElementRendererProps> = ({ element, updateElementProperty }) => {
  // Resolve styles for common nodes
  const labelStyle = useResolvedStyle(element.id, 'label');
  const inputStyle = useResolvedStyle(element.id, 'input');
  const errorStyle = useResolvedStyle(element.id, 'error');
  const descriptionStyle = useResolvedStyle(element.id, 'description');
  const placeholderStyle = useResolvedStyle(element.id, 'placeholder');
  const requiredStyle = useResolvedStyle(element.id, 'required');
  const fieldContainerStyle = useResolvedStyle(element.id, 'fieldContainer');
  const headingStyle = useResolvedStyle(element.id, 'heading');
  const paragraphStyle = useResolvedStyle(element.id, 'paragraph');
  const buttonStyle = useResolvedStyle(element.id, 'button');
  const dividerStyle = useResolvedStyle(element.id, 'divider');
  const optionLabelStyle = useResolvedStyle(element.id, 'optionLabel');
  const cardContainerStyle = useResolvedStyle(element.id, 'cardContainer');

  // Convert to CSS and memoize
  const resolvedStyles = useMemo<ResolvedStyles>(() => ({
    label: stylesToCSS(labelStyle),
    input: mergeWithElementProps(stylesToCSS(inputStyle), element.properties),
    error: stylesToCSS(errorStyle),
    description: stylesToCSS(descriptionStyle),
    placeholder: stylesToCSS(placeholderStyle),
    required: stylesToCSS(requiredStyle),
    fieldContainer: stylesToCSS(fieldContainerStyle),
    heading: stylesToCSS(headingStyle),
    paragraph: stylesToCSS(paragraphStyle),
    button: stylesToCSS(buttonStyle),
    divider: stylesToCSS(dividerStyle),
    optionLabel: stylesToCSS(optionLabelStyle),
    cardContainer: stylesToCSS(cardContainerStyle),
  }), [
    labelStyle, inputStyle, errorStyle, descriptionStyle, placeholderStyle,
    requiredStyle, fieldContainerStyle, headingStyle, paragraphStyle,
    buttonStyle, dividerStyle, optionLabelStyle, cardContainerStyle,
    element.properties
  ]);

  const renderElement = () => {
    switch (element.type) {
      case 'text':
      case 'email':
      case 'phone':
      case 'url':
      case 'password':
      case 'number':
      case 'number-formatted':
        return <TextInput element={element} styles={resolvedStyles} />;

      case 'textarea':
        return <TextArea element={element} styles={resolvedStyles} />;

      case 'select':
        return <Select element={element} styles={resolvedStyles} />;

      case 'radio-cards':
        return <RadioCards element={element} updateElementProperty={updateElementProperty!} styles={resolvedStyles} />;

      case 'checkbox-cards':
        return <CheckboxCards element={element} updateElementProperty={updateElementProperty!} styles={resolvedStyles} />;

      case 'columns':
        return <ColumnsContainer element={element} />;

      default:
        // Fallback for elements not yet extracted
        return (
          <div className="border border-gray-300 rounded-md p-4 text-center text-gray-500">
            Element type: {element.type} (not yet styled)
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
      <div style={resolvedStyles.fieldContainer}>
        {renderElement()}
      </div>
    </Suspense>
  );
};

export default ElementRenderer;
