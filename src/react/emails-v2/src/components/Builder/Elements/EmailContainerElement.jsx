import React from 'react';
import { Upload } from 'lucide-react';
import { useDroppable } from '@dnd-kit/core';
import clsx from 'clsx';

/**
 * Email Container Element - System element representing the main email content area
 * This is the canvas where users add their email content
 */
function EmailContainerElement({ element, renderElements }) {
  const { children = [] } = element;
  
  // Add timestamp to track re-renders
  const renderTime = new Date().toISOString();
  
  // Removed debug logging to prevent render loops

  // Create a primary DropZone for the entire email container
  const { setNodeRef: setMainDropRef, isOver: isMainDropOver } = useDroppable({
    id: `email-container-main-drop-${element.id}`,
    data: {
      parentId: element.id,
      position: 0,
      type: 'email-container-drop'
    }
  });

  // Create a dedicated DropZone specifically for empty container
  const { setNodeRef: setEmptyDropRef, isOver: isEmptyDropOver } = useDroppable({
    id: `email-container-empty-drop-${element.id}`,
    data: {
      parentId: element.id,
      position: 0,
      type: 'email-container-empty-drop'
    }
  });

  return (
    <div 
      className="email-container-content ev2-w-full ev2-h-full ev2-min-h-[200px] ev2-relative"
      {...({
        // Always visible identification
        'data-component': 'EmailContainerElement',
        'data-element-type': 'emailContainer',
        'data-element-id': element.id,
        'data-is-system': 'true',
        'data-children-count': children.length,
        
        // Development debugging attributes
        ...(!process.env.NODE_ENV || process.env.NODE_ENV !== 'production') && {
          'data-debug-width': element.props?.width || '600px',
          'data-debug-min-height': '200px',
          'data-debug-role': 'email-content-canvas',
          'data-debug-main-drop-over': isMainDropOver ? 'true' : 'false',
          'data-debug-empty-drop-over': isEmptyDropOver ? 'true' : 'false'
        }
      })}
    >
      {children.length > 0 ? (
        // When container has children, render them with a main drop zone
        <div ref={setMainDropRef} className="ev2-min-h-full">
          {renderElements(children, element.id)}
        </div>
      ) : (
        // When container is empty, create a large drop zone
        <div 
          ref={setEmptyDropRef}
          className={clsx(
            "ev2-h-full ev2-min-h-[150px] ev2-flex ev2-flex-col ev2-items-center ev2-justify-center ev2-text-gray-400 ev2-transition-all",
            isEmptyDropOver && "ev2-bg-blue-50 ev2-border-2 ev2-border-blue-300 ev2-border-dashed"
          )}
        >
          <Upload className="ev2-w-10 ev2-h-10 ev2-mb-3" />
          <p className="ev2-text-sm">Drop elements here</p>
          {isEmptyDropOver && (
            <p className="ev2-text-xs ev2-text-blue-600 ev2-mt-2">Release to add element</p>
          )}
        </div>
      )}
    </div>
  );
}

export default EmailContainerElement;