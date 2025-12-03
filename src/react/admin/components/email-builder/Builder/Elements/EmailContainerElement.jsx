import React from 'react';
import { Upload } from 'lucide-react';
import { useDroppable } from '@dnd-kit/core';
import clsx from 'clsx';
import SpacingLayer from './SpacingLayer';
import { hasCapability } from '../../capabilities/elementCapabilities';

/**
 * Email Container Element - System element representing the main email content area
 * This is the canvas where users add their email content
 */
function EmailContainerElement({ element, renderElements }) {
  const { children = [] } = element;
  
  // Add timestamp to track re-renders
  const renderTime = new Date().toISOString();
  
  // Removed debug logging to prevent render loops

  // Extract spacing properties
  const {
    margin = { top: 0, right: 0, bottom: 0, left: 0 },
    padding = { top: 0, right: 0, bottom: 0, left: 0 },
    border = { top: 0, right: 0, bottom: 0, left: 0 },
    borderStyle = 'solid',
    borderColor = '#000000',
    backgroundColor,
    backgroundImage,
    backgroundSize = 'cover',
    backgroundPosition = 'center',
    backgroundRepeat = 'no-repeat',
    borderRadius = 0,
    boxShadow = 'none'
  } = element.props || {};

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
      style={{
        paddingTop: `${margin.top}px`,
        paddingRight: `${margin.right}px`,
        paddingBottom: `${margin.bottom}px`,
        paddingLeft: `${margin.left}px`
      }}
    >
      <SpacingLayer
        margin={null} // Margin is handled as padding in the outer div
        padding={padding}
        border={{
          width: border,
          style: borderStyle,
          color: borderColor
        }}
        backgroundColor={backgroundColor}
        backgroundImage={backgroundImage ? {
          url: backgroundImage,
          size: backgroundSize,
          position: backgroundPosition,
          repeat: backgroundRepeat
        } : null}
        borderRadius={borderRadius}
        boxShadow={boxShadow}
      >
        <div 
          className="email-container-content w-full h-full min-h-[200px] relative"
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
          <div ref={setMainDropRef} className="min-h-full">
            {renderElements(children, element.id)}
          </div>
        ) : (
          // When container is empty, create a large drop zone
          <div 
            ref={setEmptyDropRef}
            className={clsx(
              "h-full min-h-[150px] flex flex-col items-center justify-center text-gray-400 transition-all",
              isEmptyDropOver && "bg-blue-50 border-2 border-blue-300 border-dashed"
            )}
          >
            <Upload className="w-10 h-10 mb-3" />
            <p className="text-sm">Drop elements here</p>
            {isEmptyDropOver && (
              <p className="text-xs text-blue-600 mt-2">Release to add element</p>
            )}
          </div>
        )}
        </div>
      </SpacingLayer>
    </div>
  );
}

export default EmailContainerElement;