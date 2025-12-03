import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import SortableElement from './Elements/SortableElement';
import clsx from 'clsx';
import type { EmailElement } from '../../types/email';

interface CanvasIntegratedProps {
  isMobile?: boolean;
}

function CanvasIntegrated({ isMobile = false }: CanvasIntegratedProps) {
  const {
    elements,
    isDragging,
    selectElement,
    selectedElementId,
    setElements,
  } = useEmailBuilder();
  
  // Removed debug logging to prevent render loops
  
  // Ensure system elements are always present
  React.useEffect(() => {
    if (!elements || elements.length === 0) {
      // Initialize with system elements
      setElements([]);
    }
  }, [elements, setElements]);

  const renderElements = (elements: EmailElement[], parentId: string | null = null): React.ReactElement | null => {
    if (!elements || elements.length === 0) {
      return parentId ? null : (
        <DropZone parentId={null} position={0}>
          <div className="py-12 text-center text-gray-400">
            <svg className="w-10 h-10 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
              />
            </svg>
            <p className="text-sm">Drag elements here to start building</p>
          </div>
        </DropZone>
      );
    }

    // Filter out system elements from sortable items since they have fixed positions
    const sortableItems = elements
      .filter(el => el.type !== 'emailWrapper' && el.type !== 'emailContainer')
      .map(el => el.id);
      
    // Separate system elements from regular elements
    const systemElements = elements.filter(el => el.type === 'emailWrapper' || el.type === 'emailContainer');
    const regularElements = elements.filter(el => el.type !== 'emailWrapper' && el.type !== 'emailContainer');
    
    return (
      <>
        {/* Render system elements first without sortable wrapper or dropzones */}
        {systemElements.map((element) => (
          <div key={element.id} className="system-element-container">
            <SortableElement
              element={element}
              index={0} // System elements don't need real index
              parentId={parentId}
              isSelected={selectedElementId === element.id}
              onSelect={() => selectElement(element.id)}
              renderElements={renderElements}
              isMobile={isMobile}
            />
          </div>
        ))}
        
        {/* Render regular elements with sortable context and dropzones */}
        <SortableContext items={sortableItems} strategy={verticalListSortingStrategy}>
          <DropZone parentId={parentId} position={0} elementCount={regularElements.length} />
          {regularElements.map((element, index) => (
            <React.Fragment key={element.id}>
              <SortableElement
                element={element}
                index={index}
                parentId={parentId}
                isSelected={selectedElementId === element.id}
                onSelect={() => selectElement(element.id)}
                renderElements={renderElements}
                isMobile={isMobile}
              />
              <DropZone parentId={parentId} position={index + 1} elementCount={regularElements.length} />
            </React.Fragment>
          ))}
        </SortableContext>
      </>
    );
  };

  // Droppable zone component
  interface DropZoneProps {
    parentId: string | null;
    position: number;
    children?: React.ReactNode;
    elementCount?: number;
  }

  function DropZone({ parentId, position, children, elementCount = 0 }: DropZoneProps) {
    const { setNodeRef, isOver } = useDroppable({
      id: `dropzone-${parentId || 'root'}-${position}`,
      data: {
        parentId,
        position,
        type: 'dropzone'
      }
    });

    // Check if this DropZone should be hidden
    const shouldHide = (
      // Hide ALL DropZones at root level (parentId is null) - nothing should be dropped outside email structure
      !parentId ||
      // Hide DropZone at position 0 when parent is email-wrapper (above container inside wrapper)  
      (position === 0 && parentId && parentId.includes('email-wrapper'))
      // Allow all DropZones inside email-container for user content
    );

    return (
      <div
        ref={setNodeRef}
        className={clsx(
          shouldHide ? 'min-h-0' : 'min-h-[4px]',
          'transition-all relative',
          !shouldHide && isDragging && 'min-h-[8px]',
          !shouldHide && isOver && 'min-h-[30px]'
        )}
        {...({
          // Always visible identification
          'data-component': 'DropZone',
          'data-parent-id': parentId || 'root',
          'data-position': position,
          'data-is-over': isOver ? 'true' : 'false',
          'data-should-hide': shouldHide ? 'true' : 'false',
          
          // Development debugging attributes (always show when not production)
          ...(!process.env.NODE_ENV || process.env.NODE_ENV !== 'production') && {
            'data-debug-element-count': elementCount,
            'data-debug-droppable-id': `dropzone-${parentId || 'root'}-${position}`,
            'data-debug-is-dragging': isDragging ? 'true' : 'false'
          }
        })}
      >
        {!shouldHide && isOver && (
          <div className="absolute inset-x-0 top-1/2 -translate-y-1/2 h-1 bg-blue-500 rounded" />
        )}
        {children}
      </div>
    );
  }

  const handleCanvasClick = (e: React.MouseEvent<HTMLDivElement>) => {
    // Only deselect if clicking directly on the canvas, not on an element
    if (e.target === e.currentTarget) {
      selectElement(null);
    }
  };

  return (
    <div 
      className={clsx(
        'min-h-[100px] transition-colors',
        isDragging && 'bg-gray-50'
      )}
      onClick={handleCanvasClick}
    >
      {renderElements(elements)}
    </div>
  );
}

export default CanvasIntegrated;