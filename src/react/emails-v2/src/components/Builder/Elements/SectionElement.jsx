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
        'element-content ev2-min-h-[50px] ev2-transition-all',
        isOver && 'ev2-bg-primary-50'
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
          "ev2-py-8 ev2-text-center ev2-text-sm ev2-border-2 ev2-border-dashed ev2-rounded ev2-transition-all",
          isHovered 
            ? "ev2-text-blue-500 ev2-border-blue-300" 
            : "ev2-text-gray-400 ev2-border-gray-300"
        )}>
          Drop elements here
        </div>
      )}
    </div>
  );
}

export default SectionElement;