import React from 'react';

function ImageElement({ element }) {
  const { 
    src, 
    alt = 'Image', 
    width = 'auto', 
    height = 'auto', 
    align = 'left', 
    link,
    target = '_blank'
  } = element.props;

  const imageEl = (
    <img
      src={src || 'https://via.placeholder.com/600x300'}
      alt={alt}
      style={{
        width: width,
        height: height,
        maxWidth: '100%',
        display: 'block',
        // Email-friendly image styles
        border: '0',
        outline: 'none',
        textDecoration: 'none',
        msInterpolationMode: 'bicubic', // Better scaling in IE
      }}
    />
  );

  return (
    <div className="element-content" style={{ textAlign: align }}>
      {link ? (
        <a 
          href={link} 
          target={target} 
          rel={target === '_blank' ? 'noopener noreferrer' : undefined}
          style={{
            textDecoration: 'none',
            border: '0',
            outline: 'none'
          }}
        >
          {imageEl}
        </a>
      ) : (
        imageEl
      )}
    </div>
  );
}

export default ImageElement;