import React from 'react';
import { useDraggable } from '@dnd-kit/core';
import useEmailBuilder from '../../hooks/useEmailBuilder';
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
        'ev2-flex ev2-flex-col ev2-items-center ev2-justify-center ev2-p-3 ev2-cursor-move',
        'hover:ev2-bg-gray-100 ev2-transition-colors ev2-rounded-lg ev2-group',
        isDragging && 'ev2-opacity-50'
      )}
      title={element.name}
    >
      <Icon className="ev2-w-5 ev2-h-5 ev2-mb-1 ev2-text-gray-600 group-hover:ev2-text-gray-800" />
      <span className="ev2-text-xs ev2-text-gray-600 ev2-text-center ev2-leading-tight group-hover:ev2-text-gray-800">
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
    <div className="ev2-px-4 ev2-py-3">
      <div className="ev2-flex ev2-items-center ev2-gap-6">
        <div className="ev2-text-sm ev2-font-medium ev2-text-gray-700 ev2-whitespace-nowrap">
          Elements:
        </div>
        
        <div className="ev2-flex ev2-items-center ev2-gap-6 ev2-overflow-x-auto">
          {categories.map((category, categoryIndex) => (
            <React.Fragment key={category.name}>
              {categoryIndex > 0 && (
                <div className="ev2-h-8 ev2-w-px ev2-bg-gray-300" />
              )}
              <div className="ev2-flex ev2-items-center ev2-gap-1">
                <span className="ev2-text-xs ev2-text-gray-500 ev2-uppercase ev2-mr-2">
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
        
        <div className="ev2-ml-auto ev2-text-xs ev2-text-gray-500 ev2-whitespace-nowrap">
          Drag to add
        </div>
      </div>
    </div>
  );
}

export default ElementPaletteHorizontal;