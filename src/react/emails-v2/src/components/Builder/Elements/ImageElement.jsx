import React from 'react';

function ImageElement({ element }) {
  const { src, alt, width, height, align, link } = element.props;

  const imageEl = (
    <img
      src={src || 'https://via.placeholder.com/600x300'}
      alt={alt}
      style={{
        width: width,
        height: height,
        maxWidth: '100%',
        display: 'block',
      }}
    />
  );

  return (
    <div style={{ textAlign: align }}>
      {link ? (
        <a href={link} target="_blank" rel="noopener noreferrer">
          {imageEl}
        </a>
      ) : (
        imageEl
      )}
    </div>
  );
}

export default ImageElement;