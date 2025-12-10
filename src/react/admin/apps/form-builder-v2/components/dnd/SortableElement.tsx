import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Move, Settings, Trash2 } from 'lucide-react';
import type { FormElement } from '../../types';
import { ElementRenderer } from '../elements/ElementRenderer';

interface SortableElementProps {
  element: FormElement;
  isSelected: boolean;
  isMultiSelected: boolean;
  onSelect: (elementId: string, event: React.MouseEvent) => void;
  onDelete: (elementId: string) => void;
  onContextMenu: (event: React.MouseEvent, elementId: string) => void;
  updateElementProperty: (elementId: string, property: string, value: unknown) => void;
  selectedElements?: string[];
}

export const SortableElement: React.FC<SortableElementProps> = ({
  element,
  isSelected,
  isMultiSelected,
  onSelect,
  onDelete,
  onContextMenu,
  updateElementProperty,
  selectedElements,
}) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: element.id });

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`form-element ${isSelected || isMultiSelected ? 'form-element-selected' : ''} ${isDragging ? 'form-element-dragging' : ''}`}
      data-element-id={element.id}
      data-testid={`sortable-element-${element.id}`}
      onClick={(e) => onSelect(element.id, e)}
      onContextMenu={(e) => onContextMenu(e, element.id)}
    >
      <div className="element-controls">
        {/* CRITICAL: listeners and attributes ONLY on the Move handle */}
        <button
          {...attributes}
          {...listeners}
          className="element-control-btn"
          title="Drag to reorder"
          data-testid={`drag-handle-${element.id}`}
        >
          <Move size={16} />
        </button>
        <button className="element-control-btn" title="Edit Properties">
          <Settings size={16} />
        </button>
        <button
          className="element-control-btn"
          title="Delete"
          onClick={(e) => {
            e.stopPropagation();
            onDelete(element.id);
          }}
        >
          <Trash2 size={16} />
        </button>
      </div>

      <ElementRenderer
        element={element}
        updateElementProperty={updateElementProperty}
        onSelect={onSelect}
        onDelete={onDelete}
        onContextMenu={onContextMenu}
        selectedElements={selectedElements}
      />
    </div>
  );
};

// Drag preview shown in DragOverlay
interface ElementDragPreviewProps {
  element: FormElement;
}

export const ElementDragPreview: React.FC<ElementDragPreviewProps> = ({ element }) => {
  return (
    <div className="drag-overlay-element" data-testid="element-drag-preview">
      <div className="drag-preview-header">
        <Move size={14} className="text-blue-500" />
        <span className="drag-preview-label">
          {element.properties?.label || element.type}
        </span>
      </div>
      <div className="drag-preview-type">
        {element.type.replace(/_/g, ' ')}
      </div>
    </div>
  );
};
