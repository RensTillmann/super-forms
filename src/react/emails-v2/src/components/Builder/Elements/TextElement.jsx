import React from 'react';

function TextElement({ element }) {
  const { 
    content, 
    fontSize = 16, 
    fontFamily = 'Arial, sans-serif', 
    color = '#000000', 
    lineHeight = 1.4, 
    align = 'left' 
  } = element.props;

  return (
    <div
      className="ev2-min-h-[20px] element-content"
      style={{
        fontSize: `${fontSize}px`,
        fontFamily: fontFamily,
        color: color,
        lineHeight: lineHeight,
        textAlign: align,
      }}
      dangerouslySetInnerHTML={{ __html: content }}
    />
  );
}

export default TextElement;