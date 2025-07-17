import React, { useEffect, useRef } from 'react';
import { DndContext, DragOverlay, closestCenter, pointerWithin, useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import SortableElement from './Elements/SortableElement';
import clsx from 'clsx';

function Canvas({ email, onChange }) {
  const canvasRef = useRef(null);
  const {
    elements,
    isDragging,
    draggedElement,
    addElement,
    moveElement,
    selectElement,
    selectedElementId,
    endDrag,
    generateHtml,
  } = useEmailBuilder();

  // Generate HTML when requested by parent
  useEffect(() => {
    if (onChange && elements.length > 0) {
      // Only update when there are actual elements to prevent initial empty updates
      const html = generateHtml();
      // Use a timeout to avoid state updates during render
      const timeoutId = setTimeout(() => {
        onChange(html);
      }, 0);
      return () => clearTimeout(timeoutId);
    }
  }, [elements, generateHtml, onChange]);

  const handleDragEnd = (event) => {
    const { active, over } = event;
    
    if (!over) {
      endDrag();
      return;
    }

    const activeData = active.data.current;
    const overData = over.data.current;

    if (activeData.type === 'new-element') {
      // Adding new element from palette
      const position = overData?.position !== undefined ? overData.position : null;
      let parentId = overData?.parentId || null;
      
      // Handle column drops - extract parent and column info
      if (overData?.isColumn && over.id.includes('-col-')) {
        parentId = over.id; // Use the full column ID as parent
      }
      
      addElement(activeData.elementType, parentId, position);
    } else if (activeData.type === 'canvas-element') {
      // Moving existing element
      if (active.id !== over.id) {
        const position = overData?.position !== undefined ? overData.position : null;
        let parentId = overData?.parentId || null;
        
        // Handle column drops - extract parent and column info
        if (overData?.isColumn && over.id.includes('-col-')) {
          parentId = over.id; // Use the full column ID as parent
        }
        
        moveElement(active.id, parentId, position);
      }
    }

    endDrag();
  };

  const handleDragOver = (event) => {
    // Handle drag over for better drop zone detection
  };

  const renderElements = (elements, parentId = null) => {
    if (!elements || elements.length === 0) {
      return parentId ? null : (
        <DropZone parentId={null} position={0}>
          <div className="ev2-py-16 ev2-text-center ev2-text-gray-400">
            <svg className="ev2-w-12 ev2-h-12 ev2-mx-auto ev2-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          'ev2-min-h-[20px] ev2-transition-all',
          isOver && 'ev2-bg-blue-50 ev2-min-h-[60px] ev2-border-2 ev2-border-dashed ev2-border-blue-300'
        )}
      >
        {children}
      </div>
    );
  }

  return (
    <div className="ev2-h-full ev2-p-8">
      <div className="ev2-max-w-2xl ev2-mx-auto">
        <DndContext
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
          onDragOver={handleDragOver}
        >
          <div
            ref={canvasRef}
            className={clsx(
              'ev2-bg-white ev2-rounded-lg ev2-shadow-sm ev2-min-h-[600px]',
              'ev2-border-2 ev2-transition-colors',
              isDragging ? 'ev2-border-primary-300 ev2-border-dashed' : 'ev2-border-gray-200'
            )}
            style={{ width: '600px' }}
          >
            {renderElements(elements)}
          </div>

          <DragOverlay>
            {draggedElement && (
              <div className="ev2-bg-white ev2-rounded ev2-shadow-lg ev2-p-4 ev2-opacity-80">
                <div className="ev2-flex ev2-items-center ev2-gap-2">
                  <span className="ev2-text-2xl">{draggedElement.icon}</span>
                  <span className="ev2-text-sm ev2-font-medium">{draggedElement.name}</span>
                </div>
              </div>
            )}
          </DragOverlay>
        </DndContext>
      </div>
    </div>
  );
}

export default Canvas;