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
      className="element-content flex flex-wrap"
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
        'min-h-[100px] transition-all',
        isOver && 'bg-primary-50'
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
          "h-full flex items-center justify-center text-sm border-2 border-dashed rounded p-4 transition-all",
          isHovered 
            ? "text-blue-500 border-blue-300" 
            : "text-gray-400 border-gray-300"
        )}>
          Drop here
        </div>
      )}
    </div>
  );
}

export default ColumnsElement;