import React from 'react';
import { useDraggable } from '@dnd-kit/core';
import useEmailBuilder from '../hooks/useEmailBuilder';
import clsx from 'clsx';
import {
  Type,
  Image,
  Square,
  Minus,
  MoveVertical,
  Share2,
  Database,
  Columns,
  Box,
  Code
} from 'lucide-react';

// Map element types to Lucide icons
const ELEMENT_ICONS = {
  section: Box,
  columns: Columns,
  text: Type,
  image: Image,
  button: Square,
  divider: Minus,
  spacer: MoveVertical,
  social: Share2,
  formData: Database,
  html: Code,
};

function DraggableElement({ element }) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `palette-${element.id}`,
    data: {
      type: 'new-element',
      elementType: element.id,
    },
  });

  const Icon = ELEMENT_ICONS[element.id] || Box;

  return (
    <div
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      className={clsx(
        'flex flex-col items-center justify-center p-3 cursor-move',
        'hover:bg-gray-100 transition-colors rounded-lg group',
        isDragging && 'opacity-50'
      )}
      title={element.name}
    >
      <Icon className="w-5 h-5 mb-1 text-gray-600 group-hover:text-gray-800" />
      <span className="text-xs text-gray-600 text-center leading-tight group-hover:text-gray-800">
        {element.name}
      </span>
    </div>
  );
}

function ElementPaletteHorizontal() {
  const { elementTypes } = useEmailBuilder();

  const categories = [
    {
      name: 'Layout',
      elements: ['section', 'columns', 'spacer'],
    },
    {
      name: 'Content',
      elements: ['text', 'image', 'button', 'divider', 'html'],
    },
    {
      name: 'Dynamic',
      elements: ['formData', 'social'],
    },
  ];

  return (
    <div className="px-4 py-3">
      <div className="flex items-center gap-6">
        <div className="text-sm font-medium text-gray-700 whitespace-nowrap">
          Elements:
        </div>
        
        <div className="flex items-center gap-6 overflow-x-auto">
          {categories.map((category, categoryIndex) => (
            <React.Fragment key={category.name}>
              {categoryIndex > 0 && (
                <div className="h-8 w-px bg-gray-300" />
              )}
              <div className="flex items-center gap-1">
                <span className="text-xs text-gray-500 uppercase mr-2">
                  {category.name}
                </span>
                {category.elements.map((elementId) => {
                  const element = elementTypes[elementId];
                  return element ? (
                    <DraggableElement key={element.id} element={element} />
                  ) : null;
                })}
              </div>
            </React.Fragment>
          ))}
        </div>
        
        <div className="ml-auto text-xs text-gray-500 whitespace-nowrap">
          Drag to add
        </div>
      </div>
    </div>
  );
}

export default ElementPaletteHorizontal;