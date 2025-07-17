import React from 'react';
import clsx from 'clsx';

function ButtonElement({ element, isSelected }) {
  const { 
    text, 
    href, 
    backgroundColor, 
    color, 
    fontSize, 
    fontWeight,
    padding, 
    borderRadius, 
    align,
    fullWidth 
  } = element.props;

  return (
    <div 
      className={clsx(
        'ev2-transition-all ev2-duration-200',
        isSelected && 'ev2-ring-2 ev2-ring-green-500 ev2-animate-pulse'
      )}
      style={{ textAlign: align }}
    >
      <a
        href={href}
        className="ev2-inline-block ev2-no-underline ev2-transition-opacity hover:ev2-opacity-80"
        style={{
          backgroundColor,
          color,
          fontSize: `${fontSize}px`,
          fontWeight,
          paddingTop: `${padding.top}px`,
          paddingRight: `${padding.right}px`,
          paddingBottom: `${padding.bottom}px`,
          paddingLeft: `${padding.left}px`,
          borderRadius: `${borderRadius}px`,
          width: fullWidth ? '100%' : 'auto',
          textAlign: 'center',
        }}
      >
        {text}
      </a>
    </div>
  );
}

export default ButtonElement;