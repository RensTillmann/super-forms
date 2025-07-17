import React from 'react';
import { useDraggable } from '@dnd-kit/core';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import clsx from 'clsx';

function DraggableElement({ element }) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `palette-${element.id}`,
    data: {
      type: 'new-element',
      elementType: element.id,
    },
  });

  return (
    <div
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      className={clsx(
        'ev2-flex ev2-flex-col ev2-items-center ev2-justify-center ev2-p-3 ev2-cursor-move',
        'hover:ev2-bg-gray-50 ev2-transition-colors ev2-group',
        isDragging && 'ev2-opacity-50'
      )}
      title={element.name}
    >
      <span className="ev2-text-2xl ev2-mb-1">{element.icon}</span>
      <span className="ev2-text-xs ev2-text-gray-600 ev2-text-center ev2-leading-tight">
        {element.name}
      </span>
    </div>
  );
}

function ElementPalette() {
  const { elementTypes } = useEmailBuilder();

  const categories = [
    {
      name: 'Layout',
      elements: ['section', 'columns', 'spacer'],
    },
    {
      name: 'Content',
      elements: ['text', 'image', 'button', 'divider'],
    },
    {
      name: 'Dynamic',
      elements: ['formData', 'social'],
    },
  ];

  return (
    <div className="ev2-h-full">
      <div className="ev2-p-3 ev2-border-b">
        <h3 className="ev2-text-sm ev2-font-medium ev2-text-gray-700">Elements</h3>
      </div>
      
      {categories.map((category) => (
        <div key={category.name} className="ev2-border-b">
          <div className="ev2-px-3 ev2-py-2 ev2-bg-gray-50">
            <h4 className="ev2-text-xs ev2-font-medium ev2-text-gray-600 ev2-uppercase">
              {category.name}
            </h4>
          </div>
          <div className="ev2-grid ev2-grid-cols-1">
            {category.elements.map((elementId) => {
              const element = elementTypes[elementId];
              return element ? (
                <DraggableElement key={element.id} element={element} />
              ) : null;
            })}
          </div>
        </div>
      ))}
      
      <div className="ev2-p-3 ev2-text-xs ev2-text-gray-500 ev2-text-center">
        Drag elements to canvas
      </div>
    </div>
  );
}

export default ElementPalette;