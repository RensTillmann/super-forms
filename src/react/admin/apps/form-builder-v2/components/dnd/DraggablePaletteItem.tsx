import React from 'react';
import { useDraggable } from '@dnd-kit/core';
import { cn } from '../../../../lib/utils';

interface ElementConfig {
  type: string;
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  keywords?: string[];
}

interface DraggablePaletteItemProps {
  element: ElementConfig;
  isSingleElement: boolean;
  onClick: () => void;
}

export const DraggablePaletteItem: React.FC<DraggablePaletteItemProps> = ({
  element,
  isSingleElement,
  onClick,
}) => {
  // Use element type as ID with prefix to distinguish from existing element UUIDs
  const draggableId = `palette:${element.type}`;

  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    isDragging,
  } = useDraggable({
    id: draggableId,
    data: {
      type: 'palette-item',
      elementType: element.type,
      label: element.label,
      isNew: true,
    },
  });

  const style = transform ? {
    transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
  } : undefined;

  return (
    <div
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      style={style}
      className={cn(
        "relative flex flex-col items-center justify-center gap-2 p-3 min-w-[120px] min-h-[80px]",
        "bg-white border border-border rounded-lg cursor-grab select-none",
        "transition-all hover:border-primary/30 hover:bg-primary/5 hover:-translate-y-0.5 hover:shadow-sm",
        "active:cursor-grabbing active:-translate-y-0.5",
        isDragging && "opacity-50",
        isSingleElement && "bg-primary/10 border-primary/40 shadow-sm"
      )}
      onClick={onClick}
      data-testid={`palette-item-${element.type}`}
    >
      <element.icon className="w-6 h-6 text-primary" />
      <span className="text-xs font-medium text-foreground text-center leading-tight">
        {element.label}
      </span>
      {isSingleElement && (
        <div className="absolute -top-9 left-1/2 -translate-x-1/2 px-2 py-1 bg-popover border border-border rounded shadow-md text-xs whitespace-nowrap opacity-100 transition-opacity">
          Press Enter to add
        </div>
      )}
    </div>
  );
};

// Drag preview shown in DragOverlay when dragging a palette item
interface PaletteDragPreviewProps {
  label: string;
  icon: React.ComponentType<{ className?: string }>;
}

export const PaletteDragPreview: React.FC<PaletteDragPreviewProps> = ({
  label,
  icon: Icon,
}) => {
  return (
    <div className="palette-drag-preview" data-testid="palette-drag-preview">
      <Icon className="w-6 h-6 text-primary" />
      <div className="preview-text">
        <span className="preview-label">{label}</span>
        <span className="preview-hint">Drop to add</span>
      </div>
    </div>
  );
};
