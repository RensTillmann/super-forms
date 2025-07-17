import React from 'react';

function FormDataElement({ element }) {
  const { 
    field = 'email', 
    fallback = '[Form Field]', 
    format = 'text' 
  } = element.props;

  // In the actual implementation, this would pull data from the form
  // For now, we'll just show the field placeholder
  const displayValue = field ? `{${field}}` : fallback;

  return (
    <div className="element-content ev2-inline-block ev2-px-2 ev2-py-1 ev2-bg-yellow-100 ev2-text-yellow-800 ev2-rounded ev2-text-sm">
      {displayValue}
    </div>
  );
}

export default FormDataElement;