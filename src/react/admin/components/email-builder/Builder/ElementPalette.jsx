import React from 'react';
import { useDraggable } from '@dnd-kit/core';
import useEmailBuilder from '../hooks/useEmailBuilder';
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
        'flex flex-col items-center justify-center p-3 cursor-move',
        'hover:bg-gray-50 transition-colors group',
        isDragging && 'opacity-50'
      )}
      title={element.name}
    >
      <span className="text-2xl mb-1">{element.icon}</span>
      <span className="text-xs text-gray-600 text-center leading-tight">
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
    <div className="h-full">
      <div className="p-3 border-b">
        <h3 className="text-sm font-medium text-gray-700">Elements</h3>
      </div>
      
      {categories.map((category) => (
        <div key={category.name} className="border-b">
          <div className="px-3 py-2 bg-gray-50">
            <h4 className="text-xs font-medium text-gray-600 uppercase">
              {category.name}
            </h4>
          </div>
          <div className="grid grid-cols-1">
            {category.elements.map((elementId) => {
              const element = elementTypes[elementId];
              return element ? (
                <DraggableElement key={element.id} element={element} />
              ) : null;
            })}
          </div>
        </div>
      ))}
      
      <div className="p-3 text-xs text-gray-500 text-center">
        Drag elements to canvas
      </div>
    </div>
  );
}

export default ElementPalette;