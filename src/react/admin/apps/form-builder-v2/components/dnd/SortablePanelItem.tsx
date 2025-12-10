import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Move, Trash2, Copy } from 'lucide-react';
import { cn } from '../../../../lib/utils';
import type { FormElement } from '../../types';

interface SortablePanelItemProps {
  element: FormElement;
  isSelected: boolean;
  onClick: () => void;
  onDuplicate: (elementId: string) => void;
  onDelete: (elementId: string) => void;
  onContextMenu?: (e: React.MouseEvent, elementId: string) => void;
}

export const SortablePanelItem: React.FC<SortablePanelItemProps> = ({
  element,
  isSelected,
  onClick,
  onDuplicate,
  onDelete,
  onContextMenu,
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
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        'floating-element-item',
        isSelected && 'floating-element-selected',
        isDragging && 'opacity-50'
      )}
      onClick={onClick}
      onContextMenu={(e) => onContextMenu?.(e, element.id)}
      data-testid={`panel-item-${element.id}`}
    >
      {/* Drag handle - ONLY this gets listeners */}
      <div
        {...attributes}
        {...listeners}
        className="floating-element-drag cursor-grab active:cursor-grabbing"
        data-testid={`panel-drag-handle-${element.id}`}
      >
        <Move size={12} />
      </div>

      <div className="floating-element-info">
        <div className="floating-element-label">
          {(element.properties?.label as string) || element.type}
        </div>
        <div className="floating-element-type">
          {element.type.replace(/_/g, ' ')}
          {element.properties?.required && ' •'}
        </div>
      </div>

      <div className="floating-element-actions">
        <button
          className="floating-element-btn"
          onClick={(e) => {
            e.stopPropagation();
            onDuplicate(element.id);
          }}
          title="Duplicate"
        >
          <Copy size={12} />
        </button>
        <button
          className="floating-element-btn"
          onClick={(e) => {
            e.stopPropagation();
            onDelete(element.id);
          }}
          title="Delete"
        >
          <Trash2 size={12} />
        </button>
      </div>
    </div>
  );
};

// Panel drag preview (optional - for DragOverlay)
interface PanelDragPreviewProps {
  element: FormElement;
}

export const PanelDragPreview: React.FC<PanelDragPreviewProps> = ({ element }) => {
  return (
    <div className="floating-element-item bg-white shadow-lg border-primary-500 border-2">
      <div className="floating-element-drag">
        <Move size={12} />
      </div>
      <div className="floating-element-info">
        <div className="floating-element-label">
          {(element.properties?.label as string) || element.type}
        </div>
        <div className="floating-element-type">
          {element.type.replace(/_/g, ' ')}
        </div>
      </div>
    </div>
  );
};
