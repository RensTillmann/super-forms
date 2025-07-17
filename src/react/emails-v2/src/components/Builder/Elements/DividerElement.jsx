import React from 'react';

function DividerElement({ element }) {
  const { height, color, style, margin } = element.props;

  return (
    <div
      style={{
        marginTop: `${margin.top}px`,
        marginBottom: `${margin.bottom}px`,
      }}
    >
      <hr
        style={{
          height: `${height}px`,
          backgroundColor: color,
          border: 'none',
          borderTop: style === 'dashed' ? `${height}px dashed ${color}` : 
                     style === 'dotted' ? `${height}px dotted ${color}` : undefined,
          margin: 0,
        }}
      />
    </div>
  );
}

export default DividerElement;