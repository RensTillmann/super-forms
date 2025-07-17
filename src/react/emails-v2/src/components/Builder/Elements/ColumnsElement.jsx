import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import clsx from 'clsx';

function ColumnsElement({ element, renderElements }) {
  const { 
    columns = 2, 
    gap = 16, 
    stackOnMobile = true 
  } = element.props;

  // Ensure we have the right number of column containers
  const columnChildren = Array(columns).fill(null).map((_, index) => {
    return element.children?.[index] || { type: 'column', children: [] };
  });

  return (
    <div 
      className="element-content ev2-flex ev2-flex-wrap"
      style={{ gap: `${gap}px` }}
      data-stack-mobile={stackOnMobile}
    >
      {columnChildren.map((column, index) => (
        <Column
          key={index}
          columnId={`${element.id}-col-${index}`}
          parentId={element.id}
          index={index}
          width={`${100 / columns}%`}
          gap={gap}
          renderElements={renderElements}
          children={column.children || []}
        />
      ))}
    </div>
  );
}

function Column({ columnId, parentId, index, width, gap, renderElements, children }) {
  const [isHovered, setIsHovered] = React.useState(false);
  const { setNodeRef, isOver } = useDroppable({
    id: columnId,
    data: {
      parentId: parentId,
      position: index,
      isColumn: true,
    },
  });

  return (
    <div
      ref={setNodeRef}
      className={clsx(
        'ev2-min-h-[100px] ev2-transition-all',
        isOver && 'ev2-bg-primary-50'
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      style={{
        flex: `0 0 calc(${width} - ${gap}px)`,
      }}
    >
      {children.length > 0 ? (
        renderElements(children, columnId)
      ) : (
        <div className={clsx(
          "ev2-h-full ev2-flex ev2-items-center ev2-justify-center ev2-text-sm ev2-border-2 ev2-border-dashed ev2-rounded ev2-p-4 ev2-transition-all",
          isHovered 
            ? "ev2-text-blue-500 ev2-border-blue-300" 
            : "ev2-text-gray-400 ev2-border-gray-300"
        )}>
          Drop here
        </div>
      )}
    </div>
  );
}

export default ColumnsElement;