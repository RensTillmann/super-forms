import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import SortableElement from './Elements/SortableElement';
import clsx from 'clsx';

function CanvasIntegrated() {
  const {
    elements,
    isDragging,
    selectElement,
    selectedElementId,
  } = useEmailBuilder();

  const renderElements = (elements, parentId = null) => {
    if (!elements || elements.length === 0) {
      return parentId ? null : (
        <DropZone parentId={null} position={0}>
          <div className="ev2-py-12 ev2-text-center ev2-text-gray-400">
            <svg className="ev2-w-10 ev2-h-10 ev2-mx-auto ev2-mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
              />
            </svg>
            <p className="ev2-text-sm">Drag elements here to start building</p>
          </div>
        </DropZone>
      );
    }

    return (
      <SortableContext items={elements.map(el => el.id)} strategy={verticalListSortingStrategy}>
        <DropZone parentId={parentId} position={0} />
        {elements.map((element, index) => (
          <React.Fragment key={element.id}>
            <SortableElement
              element={element}
              index={index}
              parentId={parentId}
              isSelected={selectedElementId === element.id}
              onSelect={() => selectElement(element.id)}
              renderElements={renderElements}
            />
            <DropZone parentId={parentId} position={index + 1} />
          </React.Fragment>
        ))}
      </SortableContext>
    );
  };

  // Droppable zone component
  function DropZone({ parentId, position, children }) {
    const { setNodeRef, isOver } = useDroppable({
      id: `dropzone-${parentId || 'root'}-${position}`,
      data: {
        parentId,
        position,
        type: 'dropzone'
      }
    });

    return (
      <div
        ref={setNodeRef}
        className={clsx(
          'ev2-min-h-[4px] ev2-transition-all ev2-relative',
          isDragging && 'ev2-min-h-[8px]',
          isOver && 'ev2-min-h-[30px]'
        )}
      >
        {isOver && (
          <div className="ev2-absolute ev2-inset-x-0 ev2-top-1/2 ev2--translate-y-1/2 ev2-h-1 ev2-bg-blue-500 ev2-rounded" />
        )}
        {children}
      </div>
    );
  }

  const handleCanvasClick = (e) => {
    // Only deselect if clicking directly on the canvas, not on an element
    if (e.target === e.currentTarget) {
      selectElement(null);
    }
  };

  return (
    <div 
      className={clsx(
        'ev2-min-h-[400px] ev2-transition-colors',
        isDragging && 'ev2-bg-gray-50'
      )}
      onClick={handleCanvasClick}
    >
      {renderElements(elements)}
    </div>
  );
}

export default CanvasIntegrated;