import React from 'react';

function SpacerElement({ element }) {
  const { height } = element.props;

  return (
    <div 
      style={{ height: `${height}px` }}
      className="ev2-bg-transparent"
    />
  );
}

export default SpacerElement;