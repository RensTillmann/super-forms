import React, { useState } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import clsx from 'clsx';
import { Pencil, Trash2, GripVertical } from 'lucide-react';
import ElementRenderer from './ElementRenderer';
import useEmailBuilder from '../../../hooks/useEmailBuilder';

function SortableElement({ element, index, parentId, isSelected, onSelect, renderElements }) {
  const [isHovered, setIsHovered] = useState(false);
  const { deleteElement } = useEmailBuilder();
  
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({
    id: element.id,
    data: {
      type: 'canvas-element',
      elementId: element.id,
      parentId: parentId,
      position: index,
    },
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : undefined, // Let parent control opacity when not dragging
  };

  const handleDelete = (e) => {
    e.stopPropagation();
    if (confirm('Are you sure you want to delete this element?')) {
      deleteElement(element.id);
    }
  };

  const handleEdit = (e) => {
    e.stopPropagation();
    onSelect();
  };

  const handleElementClick = (e) => {
    e.stopPropagation();
    onSelect();
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={clsx(
        'ev2-relative ev2-group ev2-transition-all ev2-duration-200',
        isDragging && 'ev2-z-50',
        isHovered && !isDragging && !isSelected && 'ev2-ring-2 ev2-ring-gray-400 ev2-ring-opacity-40'
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >

      {/* Action Buttons - Centered */}
      {isHovered && !isDragging && (
        <div className="ev2-absolute ev2-inset-0 ev2-flex ev2-items-center ev2-justify-center ev2-z-20 ev2-pointer-events-none">
          <div className="ev2-flex ev2-gap-2 ev2-pointer-events-auto">
            {/* Edit Button */}
            <button
              onClick={handleEdit}
              className="ev2-p-2 ev2-bg-white ev2-rounded-full ev2-shadow-lg hover:ev2-shadow-xl ev2-transition-all hover:ev2-scale-110"
              title="Edit element"
            >
              <Pencil className="ev2-w-5 ev2-h-5 ev2-text-gray-700" />
            </button>

            {/* Delete Button */}
            <button
              onClick={handleDelete}
              className="ev2-p-2 ev2-bg-white ev2-rounded-full ev2-shadow-lg hover:ev2-shadow-xl ev2-transition-all hover:ev2-scale-110"
              title="Delete element"
            >
              <Trash2 className="ev2-w-5 ev2-h-5 ev2-text-red-600" />
            </button>

            {/* Drag Handle */}
            <button
              {...listeners}
              {...attributes}
              className="ev2-p-2 ev2-bg-white ev2-rounded-full ev2-shadow-lg hover:ev2-shadow-xl ev2-transition-all hover:ev2-scale-110 ev2-cursor-move"
              title="Drag to reposition"
            >
              <GripVertical className="ev2-w-5 ev2-h-5 ev2-text-gray-700" />
            </button>
          </div>
        </div>
      )}

      {/* Element Content */}
      <div
        onClick={handleElementClick}
        className="ev2-cursor-pointer ev2-transition-all"
      >
        <ElementRenderer 
          element={element} 
          renderElements={renderElements}
          isSelected={isSelected}
        />
      </div>
    </div>
  );
}

export default SortableElement;