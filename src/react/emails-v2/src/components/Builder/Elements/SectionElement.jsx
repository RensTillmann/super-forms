import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import clsx from 'clsx';

function SectionElement({ element, renderElements, isSelected }) {
  const [isHovered, setIsHovered] = React.useState(false);
  const { setNodeRef, isOver } = useDroppable({
    id: `${element.id}-droppable`,
    data: {
      parentId: element.id,
      position: element.children?.length || 0,
    },
  });

  const { 
    margin,
    border,
    borderStyle = 'solid', // Default to solid if not specified
    borderColor = '#000000', // Default to black if not specified
    padding, 
    backgroundColor, 
    backgroundImage,
    backgroundSize,
    backgroundPosition,
    backgroundRepeat
  } = element.props;

  return (
    <div
      ref={setNodeRef}
      className={clsx(
        'ev2-min-h-[50px] ev2-transition-all',
        isOver && 'ev2-bg-primary-50'
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Inner element with margin, border, padding, and background */}
      <div
        className={clsx(
          'ev2-transition-all ev2-duration-200',
          isSelected && 'ev2-ring-2 ev2-ring-green-500 ev2-animate-pulse'
        )}
        style={{
          marginTop: margin ? `${margin.top}px` : undefined,
          marginRight: margin ? `${margin.right}px` : undefined,
          marginBottom: margin ? `${margin.bottom}px` : undefined,
          marginLeft: margin ? `${margin.left}px` : undefined,
          borderTop: border && border.top > 0 ? `${border.top}px ${borderStyle} ${borderColor}` : undefined,
          borderRight: border && border.right > 0 ? `${border.right}px ${borderStyle} ${borderColor}` : undefined,
          borderBottom: border && border.bottom > 0 ? `${border.bottom}px ${borderStyle} ${borderColor}` : undefined,
          borderLeft: border && border.left > 0 ? `${border.left}px ${borderStyle} ${borderColor}` : undefined,
          paddingTop: `${padding.top}px`,
          paddingRight: `${padding.right}px`,
          paddingBottom: `${padding.bottom}px`,
          paddingLeft: `${padding.left}px`,
          backgroundColor: backgroundColor,
          backgroundImage: backgroundImage ? `url(${backgroundImage})` : undefined,
          backgroundSize: backgroundSize || 'cover',
          backgroundPosition: backgroundPosition || 'center',
          backgroundRepeat: backgroundRepeat || 'no-repeat',
        }}
      >
        {element.children && element.children.length > 0 ? (
          renderElements(element.children, element.id)
        ) : (
          <div className={clsx(
            "ev2-py-8 ev2-text-center ev2-text-sm ev2-border-2 ev2-border-dashed ev2-rounded ev2-transition-all",
            isHovered 
              ? "ev2-text-blue-500 ev2-border-blue-300" 
              : "ev2-text-gray-400 ev2-border-gray-300"
          )}>
            Drop elements here
          </div>
        )}
      </div>
    </div>
  );
}

export default SectionElement;