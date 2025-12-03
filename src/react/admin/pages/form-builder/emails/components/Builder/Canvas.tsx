import React, { useEffect, useRef } from 'react';
import { DndContext, DragOverlay, closestCenter, useDroppable, DragEndEvent, DragOverEvent } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import SortableElement from './Elements/SortableElement';
import clsx from 'clsx';
import type { Email, EmailElement } from '../../types/email';

interface CanvasProps {
  email?: Email;
  onChange?: (html: string) => void;
}

function Canvas({ onChange }: CanvasProps) {
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

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    
    if (!over) {
      endDrag();
      return;
    }

    const activeData = active.data.current;
    const overData = over.data.current;

    if (!activeData) return;

    if (activeData.type === 'new-element') {
      // Adding new element from palette
      const position = overData?.position !== undefined ? overData.position : null;
      let parentId = overData?.parentId || null;

      // Handle column drops - extract parent and column info
      if (overData?.isColumn && String(over.id).includes('-col-')) {
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

  const handleDragOver = (_event: DragOverEvent) => {
    // Handle drag over for better drop zone detection
  };

  const renderElements = (elements: EmailElement[], parentId: string | null = null): React.ReactElement | null => {
    if (!elements || elements.length === 0) {
      return parentId ? null : (
        <DropZone parentId={null} position={0}>
          <div className="py-16 text-center text-gray-400">
            <svg className="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
              />
            </svg>
            <p className="text-sm">Drag elements here to start building</p>
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
              isMobile={false}
            />
            <DropZone parentId={parentId} position={index + 1} />
          </React.Fragment>
        ))}
      </SortableContext>
    );
  };

  // Droppable zone component
  interface DropZoneProps {
    parentId: string | null;
    position: number;
    children?: React.ReactNode;
  }

  function DropZone({ parentId, position, children }: DropZoneProps) {
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
          'min-h-[20px] transition-all',
          isOver && 'bg-blue-50 min-h-[60px] border-2 border-dashed border-blue-300'
        )}
      >
        {children}
      </div>
    );
  }

  return (
    <div className="h-full p-8">
      <div className="max-w-2xl mx-auto">
        <DndContext
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
          onDragOver={handleDragOver}
        >
          <div
            ref={canvasRef}
            className={clsx(
              'bg-white rounded-lg shadow-sm min-h-[600px]',
              'border-2 transition-colors',
              isDragging ? 'border-primary-300 border-dashed' : 'border-gray-200'
            )}
            style={{ width: '600px' }}
          >
            {renderElements(elements)}
          </div>

          <DragOverlay>
            {draggedElement && (
              <div className="bg-white rounded shadow-lg p-4 opacity-80">
                <div className="flex items-center gap-2">
                  <span className="text-2xl">{draggedElement.icon}</span>
                  <span className="text-sm font-medium">{draggedElement.name}</span>
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