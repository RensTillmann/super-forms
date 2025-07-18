import React from 'react';

function TextElement({ element }) {
  const { 
    content, 
    fontSize = 16, 
    fontFamily = 'Arial, sans-serif', 
    color = '#000000', 
    lineHeight = 1.6, 
    align = 'left',
    width = '100%',
    margin = { top: 0, right: 0, bottom: 16, left: 0 },
    padding = { top: 0, right: 0, bottom: 0, left: 0 }
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
        width: width,
        marginTop: `${margin.top}px`,
        marginRight: `${margin.right}px`,
        marginBottom: `${margin.bottom}px`,
        marginLeft: `${margin.left}px`,
        paddingTop: `${padding.top}px`,
        paddingRight: `${padding.right}px`,
        paddingBottom: `${padding.bottom}px`,
        paddingLeft: `${padding.left}px`,
      }}
      dangerouslySetInnerHTML={{ __html: content }}
      {...({
        // Always visible identification
        'data-component': 'TextElement',
        'data-element-type': 'text',
        'data-element-id': element.id,
        
        // Development debugging attributes
        ...(!process.env.NODE_ENV || process.env.NODE_ENV !== 'production') && {
          'data-debug-content-length': content?.length || 0,
          'data-debug-font-size': fontSize,
          'data-debug-font-family': fontFamily,
          'data-debug-color': color,
          'data-debug-align': align
        }
      })}
    />
  );
}

export default TextElement;