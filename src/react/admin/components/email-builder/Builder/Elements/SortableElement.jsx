import React, { useState } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import clsx from 'clsx';
import { Pencil, Trash2, GripVertical } from 'lucide-react';
import ElementRenderer from './ElementRenderer';
import useEmailBuilder from '../../hooks/useEmailBuilder';
import { getElementCapabilities } from '../../capabilities/elementCapabilities';

function SortableElement({ element, index, parentId, isSelected, onSelect, renderElements, isMobile }) {
  const [isHovered, setIsHovered] = useState(false);
  const { deleteElement, editingTextElementId } = useEmailBuilder();
  
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
    
    // For text elements, also trigger inline editing
    if (element.type === 'text') {
      // We need to find the text element and trigger its click handler
      setTimeout(() => {
        const textElement = e.target.closest('[data-component="SortableElement"]')
          ?.querySelector('[data-component="TextElement"]');
        if (textElement) {
          textElement.click();
        }
      }, 50); // Small delay to ensure property panel opens first
    }
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
        'relative group transition-all duration-200',
        isDragging && 'z-50',
        isSystemElement && 'cursor-default' // System elements aren't draggable
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

      {/* Action Buttons - Hide when text is being edited */}
      {isHovered && !isDragging && !isSystemElement && editingTextElementId !== element.id && (
        /* Regular Element - Centered action buttons */
        <div className="absolute inset-0 flex items-center justify-center z-20 pointer-events-none">
          <div className="flex gap-2 pointer-events-auto">
            {/* Edit Button */}
            <button
              onClick={handleEdit}
              className="p-2 bg-white rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-110"
              title="Edit element"
            >
              <Pencil className="w-5 h-5 text-gray-700" />
            </button>

            {/* Delete Button */}
            <button
              onClick={handleDelete}
              className="p-2 bg-white rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-110"
              title="Delete element"
            >
              <Trash2 className="w-5 h-5 text-red-600" />
            </button>

            {/* Drag Handle */}
            <button
              {...listeners}
              {...attributes}
              data-drag-handle="true"
              className="p-2 bg-white rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-110 cursor-move"
              title="Drag to reposition"
            >
              <GripVertical className="w-5 h-5 text-gray-700" />
            </button>
          </div>
        </div>
      )}

      {/* Element Content */}
      <div
        onClick={handleElementClick}
        className="cursor-pointer transition-all"
      >
        <ElementRenderer 
          element={element} 
          renderElements={renderElements}
          isSelected={isSelected}
          isHovered={isHovered && !isDragging}
          onElementUpdate={handleElementUpdate}
          isMobile={isMobile}
          onCapabilityAction={handleCapabilityAction}
          onEdit={handleEdit}
        />
      </div>
    </div>
  );
}

export default SortableElement;