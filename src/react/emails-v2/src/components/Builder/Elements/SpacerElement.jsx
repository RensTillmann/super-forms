import React from 'react';

function SpacerElement({ element }) {
  const { height = 20 } = element.props;

  return (
    <div 
      className="element-content ev2-bg-transparent"
      style={{ 
        height: `${height}px`,
        fontSize: '0',
        lineHeight: '0'
      }}
    >
      &nbsp;
    </div>
  );
}

export default SpacerElement;