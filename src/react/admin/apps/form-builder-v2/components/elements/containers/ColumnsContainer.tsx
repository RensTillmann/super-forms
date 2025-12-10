import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { cn } from '../../../../../lib/utils';
import { useElementsStore } from '../../../store/useElementsStore';
import { SortableElement } from '../../dnd/SortableElement';
import type { FormElement } from '../../../types';

interface ColumnsContainerProps {
  element: FormElement & {
    type: 'columns';
    properties?: {
      columnCount?: number;
      gap?: string;
      columnWidths?: string[];
      alignment?: string;
      width?: string | number;
      margin?: string;
      backgroundColor?: string;
      borderStyle?: string;
    };
  };
  onSelect?: (elementId: string, event: React.MouseEvent) => void;
  onDelete?: (elementId: string) => void;
  onContextMenu?: (event: React.MouseEvent, elementId: string) => void;
  updateElementProperty?: (elementId: string, property: string, value: unknown) => void;
  selectedElements?: string[];
}

export const ColumnsContainer: React.FC<ColumnsContainerProps> = ({
  element,
  onSelect,
  onDelete,
  onContextMenu,
  updateElementProperty,
  selectedElements = [],
}) => {
  const columnCount = element.properties?.columnCount || 2;
  const gap = element.properties?.gap || '20px';
  const items = useElementsStore((s) => s.items);

  // Get children for each column
  // For now, children are stored flat - column assignment via index % columnCount
  const getColumnChildren = (columnIndex: number): string[] => {
    const children = element.children || [];
    return children.filter((_, idx) => idx % columnCount === columnIndex);
  };

  return (
    <div
      className="grid min-h-20 border-2 border-dashed border-gray-200 rounded-lg p-4 hover:border-primary-400 hover:bg-primary-50 transition-colors"
      style={{
        gridTemplateColumns: `repeat(${columnCount}, 1fr)`,
        gap: gap,
      }}
      data-testid={`columns-container-${element.id}`}
    >
      {Array.from({ length: columnCount }).map((_, columnIndex) => (
        <ColumnDropZone
          key={columnIndex}
          containerId={element.id}
          columnIndex={columnIndex}
          childIds={getColumnChildren(columnIndex)}
          items={items}
          onSelect={onSelect}
          onDelete={onDelete}
          onContextMenu={onContextMenu}
          updateElementProperty={updateElementProperty}
          selectedElements={selectedElements}
        />
      ))}
    </div>
  );
};

interface ColumnDropZoneProps {
  containerId: string;
  columnIndex: number;
  childIds: string[];
  items: Record<string, FormElement>;
  onSelect?: (elementId: string, event: React.MouseEvent) => void;
  onDelete?: (elementId: string) => void;
  onContextMenu?: (event: React.MouseEvent, elementId: string) => void;
  updateElementProperty?: (elementId: string, property: string, value: unknown) => void;
  selectedElements: string[];
}

const ColumnDropZone: React.FC<ColumnDropZoneProps> = ({
  containerId,
  columnIndex,
  childIds,
  items,
  onSelect,
  onDelete,
  onContextMenu,
  updateElementProperty,
  selectedElements,
}) => {
  // Unique droppable ID format: column:{containerId}:{columnIndex}
  const droppableId = `column:${containerId}:${columnIndex}`;

  const { setNodeRef, isOver } = useDroppable({
    id: droppableId,
    data: {
      type: 'column',
      containerId,
      columnIndex,
    },
  });

  return (
    <div
      ref={setNodeRef}
      className={cn(
        'flex flex-col gap-2 border border-dashed border-gray-300 rounded p-2 min-h-15 transition-colors',
        isOver
          ? 'bg-primary-100 border-primary-500 border-solid'
          : 'bg-gray-50 hover:border-primary-400 hover:bg-primary-50'
      )}
      data-testid={`column-${columnIndex}-dropzone`}
    >
      {childIds.length === 0 ? (
        <div className="flex items-center justify-center min-h-12 text-gray-400 text-sm">
          Drop here
        </div>
      ) : (
        <SortableContext items={childIds} strategy={verticalListSortingStrategy}>
          {childIds.map((childId) => {
            const childElement = items[childId];
            if (!childElement) return null;

            return (
              <SortableElement
                key={childId}
                element={childElement}
                isSelected={selectedElements.includes(childId)}
                isMultiSelected={selectedElements.length > 1 && selectedElements.includes(childId)}
                onSelect={onSelect || (() => {})}
                onDelete={onDelete || (() => {})}
                onContextMenu={onContextMenu || (() => {})}
                updateElementProperty={updateElementProperty || (() => {})}
              />
            );
          })}
        </SortableContext>
      )}
    </div>
  );
};

export default ColumnsContainer;
