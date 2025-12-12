import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, MoreVertical, Copy, Trash2, ArrowUp, ArrowDown, Settings } from 'lucide-react';
import { cn } from '../../../../lib/utils';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '../../../../components/ui/dropdown-menu';
import type { FormElement } from '../../types';

interface SortablePanelItemProps {
  element: FormElement;
  isSelected: boolean;
  onClick: () => void;
  onDuplicate: (elementId: string) => void;
  onDelete: (elementId: string) => void;
  onMoveUp?: (elementId: string) => void;
  onMoveDown?: (elementId: string) => void;
  onContextMenu?: (e: React.MouseEvent, elementId: string) => void;
  isFirst?: boolean;
  isLast?: boolean;
}

export const SortablePanelItem: React.FC<SortablePanelItemProps> = ({
  element,
  isSelected,
  onClick,
  onDuplicate,
  onDelete,
  onMoveUp,
  onMoveDown,
  onContextMenu,
  isFirst = false,
  isLast = false,
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

  const label = (element.properties?.label as string) || element.type;
  const typeLabel = element.type.replace(/_/g, ' ');
  const isRequired = element.properties?.required;

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        'group flex items-center gap-2 px-2 py-2 rounded-md border border-transparent',
        'hover:bg-muted/50 hover:border-border',
        'transition-colors duration-150',
        isSelected && 'bg-primary/10 border-primary/30',
        isDragging && 'opacity-50 shadow-lg'
      )}
      onClick={onClick}
      onContextMenu={(e) => onContextMenu?.(e, element.id)}
      data-testid={`panel-item-${element.id}`}
    >
      {/* Drag handle */}
      <div
        {...attributes}
        {...listeners}
        className={cn(
          'flex items-center justify-center w-6 h-6 rounded',
          'text-muted-foreground/50 hover:text-muted-foreground',
          'cursor-grab active:cursor-grabbing touch-none',
          'transition-colors'
        )}
        data-testid={`panel-drag-handle-${element.id}`}
      >
        <GripVertical size={14} />
      </div>

      {/* Element info */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-1.5">
          <span className="text-sm font-medium text-foreground truncate">
            {label}
          </span>
          {isRequired && (
            <span className="text-xs text-destructive">*</span>
          )}
        </div>
        <div className="text-xs text-muted-foreground capitalize truncate">
          {typeLabel}
        </div>
      </div>

      {/* Actions dropdown */}
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <button
            className={cn(
              'flex items-center justify-center w-7 h-7 rounded',
              'text-muted-foreground/50 hover:text-muted-foreground hover:bg-muted',
              'opacity-0 group-hover:opacity-100 focus:opacity-100',
              'transition-all duration-150'
            )}
            onClick={(e) => e.stopPropagation()}
            data-testid={`panel-item-menu-${element.id}`}
          >
            <MoreVertical size={14} />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-40">
          <DropdownMenuItem
            onClick={(e) => {
              e.stopPropagation();
              onClick();
            }}
          >
            <Settings size={14} className="mr-2" />
            Edit
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={(e) => {
              e.stopPropagation();
              onDuplicate(element.id);
            }}
          >
            <Copy size={14} className="mr-2" />
            Duplicate
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            onClick={(e) => {
              e.stopPropagation();
              onMoveUp?.(element.id);
            }}
            disabled={isFirst}
          >
            <ArrowUp size={14} className="mr-2" />
            Move Up
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={(e) => {
              e.stopPropagation();
              onMoveDown?.(element.id);
            }}
            disabled={isLast}
          >
            <ArrowDown size={14} className="mr-2" />
            Move Down
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            onClick={(e) => {
              e.stopPropagation();
              onDelete(element.id);
            }}
            className="text-destructive focus:text-destructive"
          >
            <Trash2 size={14} className="mr-2" />
            Delete
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
};

// Panel drag preview (for DragOverlay)
interface PanelDragPreviewProps {
  element: FormElement;
}

export const PanelDragPreview: React.FC<PanelDragPreviewProps> = ({ element }) => {
  const label = (element.properties?.label as string) || element.type;
  const typeLabel = element.type.replace(/_/g, ' ');

  return (
    <div className="flex items-center gap-2 px-2 py-2 rounded-md bg-background border-2 border-primary shadow-lg">
      <div className="flex items-center justify-center w-6 h-6 text-muted-foreground">
        <GripVertical size={14} />
      </div>
      <div className="flex-1 min-w-0">
        <div className="text-sm font-medium text-foreground truncate">
          {label}
        </div>
        <div className="text-xs text-muted-foreground capitalize">
          {typeLabel}
        </div>
      </div>
    </div>
  );
};
