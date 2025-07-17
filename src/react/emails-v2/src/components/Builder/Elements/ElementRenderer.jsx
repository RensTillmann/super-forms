import React from 'react';
import SectionElement from './SectionElement';
import TextElement from './TextElement';
import ButtonElement from './ButtonElement';
import ImageElement from './ImageElement';
import SpacerElement from './SpacerElement';
import DividerElement from './DividerElement';
import ColumnsElement from './ColumnsElement';
import SocialElement from './SocialElement';
import FormDataElement from './FormDataElement';

const elementComponents = {
  section: SectionElement,
  text: TextElement,
  button: ButtonElement,
  image: ImageElement,
  spacer: SpacerElement,
  divider: DividerElement,
  columns: ColumnsElement,
  social: SocialElement,
  formData: FormDataElement,
};

function ElementRenderer({ element, renderElements, isSelected }) {
  const Component = elementComponents[element.type];
  
  if (!Component) {
    return (
      <div className="ev2-p-4 ev2-bg-red-50 ev2-text-red-600 ev2-text-sm">
        Unknown element type: {element.type}
      </div>
    );
  }

  return (
    <Component 
      element={element} 
      renderElements={renderElements}
      isSelected={isSelected}
    />
  );
}

export default ElementRenderer;