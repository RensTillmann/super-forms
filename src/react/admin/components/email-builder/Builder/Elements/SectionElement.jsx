import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import clsx from 'clsx';

function SectionElement({ element, renderElements }) {
  const [isHovered, setIsHovered] = React.useState(false);
  const { setNodeRef, isOver } = useDroppable({
    id: `${element.id}-droppable`,
    data: {
      parentId: element.id,
      position: element.children?.length || 0,
    },
  });

  const { 
    backgroundColor = 'transparent', 
    backgroundImage,
    backgroundSize = 'cover',
    backgroundPosition = 'center',
    backgroundRepeat = 'no-repeat'
  } = element.props;

  return (
    <div
      ref={setNodeRef}
      className={clsx(
        'element-content min-h-[50px] transition-all',
        isOver && 'bg-primary-50'
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      style={{
        backgroundColor: backgroundColor !== 'transparent' ? backgroundColor : undefined,
        backgroundImage: backgroundImage ? `url(${backgroundImage})` : undefined,
        backgroundSize: backgroundSize,
        backgroundPosition: backgroundPosition,
        backgroundRepeat: backgroundRepeat,
      }}
    >
      {element.children && element.children.length > 0 ? (
        renderElements(element.children, element.id)
      ) : (
        <div className={clsx(
          "py-8 text-center text-sm border-2 border-dashed rounded transition-all",
          isHovered 
            ? "text-blue-500 border-blue-300" 
            : "text-gray-400 border-gray-300"
        )}>
          Drop elements here
        </div>
      )}
    </div>
  );
}

export default SectionElement;