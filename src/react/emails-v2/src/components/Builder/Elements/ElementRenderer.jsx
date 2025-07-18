import React from 'react';
import UniversalElementWrapper from './UniversalElementWrapper';
import EmailWrapperElement from './EmailWrapperElement';
import EmailContainerElement from './EmailContainerElement';
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
  emailWrapper: EmailWrapperElement,
  emailContainer: EmailContainerElement,
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

function ElementRenderer({ 
  element, 
  renderElements, 
  isSelected, 
  isHovered = false,
  onElementUpdate,
  onCapabilityAction,
  onEdit
}) {
  const Component = elementComponents[element.type];
  
  if (!Component) {
    return (
      <div className="ev2-p-4 ev2-bg-red-50 ev2-text-red-600 ev2-text-sm">
        Unknown element type: {element.type}
      </div>
    );
  }

  return (
    <UniversalElementWrapper
      element={element}
      isSelected={isSelected}
      isHovered={isHovered}
      renderElements={renderElements}
      onElementUpdate={onElementUpdate}
      onCapabilityAction={onCapabilityAction}
      onEdit={onEdit}
    >
      <Component 
        element={element} 
        renderElements={renderElements}
        // Note: isSelected no longer needed here - wrapper handles it
      />
    </UniversalElementWrapper>
  );
}

export default ElementRenderer;