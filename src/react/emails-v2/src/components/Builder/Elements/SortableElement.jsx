import React, { useState } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import clsx from 'clsx';
import { Pencil, Trash2, GripVertical } from 'lucide-react';
import ElementRenderer from './ElementRenderer';
import useEmailBuilder from '../../../hooks/useEmailBuilder';
import { getElementCapabilities } from '../../../capabilities/elementCapabilities';

function SortableElement({ element, index, parentId, isSelected, onSelect, renderElements }) {
  const [isHovered, setIsHovered] = useState(false);
  const { deleteElement } = useEmailBuilder();
  
  // Get element capabilities to check if it's a system element
  const capabilities = getElementCapabilities(element.type);
  const isSystemElement = capabilities.layout?.isSystemElement;
  
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({
    id: element.id,
    disabled: isSystemElement, // Disable dragging for system elements
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
    // Email wrapper should not be selectable - it only has direct color picker
    if (element.type === 'emailWrapper') {
      // Don't select wrapper, it has no property panel
      e.stopPropagation();
      return;
    }
    
    // For email containers, only handle clicks within the visual bounds
    if (element.type === 'emailContainer') {
      const rect = e.currentTarget.getBoundingClientRect();
      const clickX = e.clientX;
      const containerWidth = parseInt(element.props?.width || '600px');
      const containerLeft = rect.left + (rect.width - containerWidth) / 2;
      const containerRight = containerLeft + containerWidth;
      
      // Only select container if click is within its visual bounds
      if (clickX >= containerLeft && clickX <= containerRight) {
        e.stopPropagation();
        onSelect();
      }
      // Otherwise, let the click bubble up but don't select anything
      return;
    }
    
    // For all other elements, handle normally
    e.stopPropagation();
    onSelect();
  };

  const handleElementUpdate = (elementId, property, value) => {
    // TODO: Implement element property updates through useEmailBuilder hook
    console.log('Element update:', elementId, property, value);
  };

  const handleCapabilityAction = (elementId, action, data) => {
    // TODO: Implement capability-based actions (color picker, link editor, etc.)
    console.log('Capability action:', elementId, action, data);
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={clsx(
        'ev2-relative ev2-group ev2-transition-all ev2-duration-200',
        isDragging && 'ev2-z-50',
        isSystemElement && 'ev2-cursor-default' // System elements aren't draggable
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      {...({
        // Always visible identification
        'data-component': 'SortableElement',
        'data-element-type': element.type,
        'data-element-id': element.id,
        'data-is-system': isSystemElement ? 'true' : 'false',
        'data-is-sortable': isSystemElement ? 'false' : 'true',
        'data-is-selected': isSelected ? 'true' : 'false',
        'data-is-hovered': isHovered ? 'true' : 'false',
        'data-is-dragging': isDragging ? 'true' : 'false',
        
        // Development debugging attributes (always show when not production)  
        ...(!process.env.NODE_ENV || process.env.NODE_ENV !== 'production') && {
          'data-debug-index': index,
          'data-debug-parent-id': parentId || 'root',
          'data-debug-transform': transform ? 'active' : 'none',
          'data-debug-transition': transition ? 'active' : 'none',
          'data-debug-disabled': isSystemElement ? 'true' : 'false'
        }
      })}
    >

      {/* Action Buttons */}
      {isHovered && !isDragging && !isSystemElement && (
        /* Regular Element - Centered action buttons */
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
          isHovered={isHovered && !isDragging}
          onElementUpdate={handleElementUpdate}
          onCapabilityAction={handleCapabilityAction}
          onEdit={handleEdit}
        />
      </div>
    </div>
  );
}

export default SortableElement;