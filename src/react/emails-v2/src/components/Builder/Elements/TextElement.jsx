import React from 'react';
import clsx from 'clsx';

function TextElement({ element, isSelected }) {
  const { content, fontSize, fontFamily, color, lineHeight, align } = element.props;

  return (
    <div
      className={clsx(
        'ev2-min-h-[20px] ev2-transition-all ev2-duration-200',
        isSelected && 'ev2-ring-2 ev2-ring-green-500 ev2-animate-pulse'
      )}
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