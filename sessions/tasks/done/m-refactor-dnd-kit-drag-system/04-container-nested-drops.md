---
name: 04-container-nested-drops
status: pending
---

# Phase 4: Implement Nested Container Drop Zones

## Goal
Enable dropping elements INTO container elements (columns, tabs, etc.). Each container column becomes its own SortableContext with the column's children as items. Implement collision detection to distinguish "drop into container" vs "drop between elements".

## Success Criteria
- [ ] ColumnsContainer columns accept dropped elements
- [ ] Dropped element becomes child of the container
- [ ] Container children can be reordered within container
- [ ] Container validates accepts/rejects rules from schema
- [ ] Visual feedback shows valid drop targets
- [ ] Elements can be dragged OUT of containers to root level
- [ ] Nested reordering triggers undo/redo and auto-save

## Implementation Steps

### 1. Understand container data structure

From `schemas/core/types.ts`, ContainerConfig:
```typescript
interface ContainerConfig {
  accepts?: string[];      // Empty = accepts all
  rejects?: string[];      // Element types to reject
  maxChildren?: number;
  minChildren?: number;
  allowReorder?: boolean;  // Default true
}
```

Container elements have:
- `children: string[]` - Array of child element IDs
- Child elements have `parent: string` - Parent container ID

### 2. Refactor ColumnsContainer to support drops

Rewrite `components/elements/containers/ColumnsContainer.tsx`:

```typescript
import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useElementsStore } from '../../../store/useElementsStore';
import { SortableElement } from '../dnd/SortableElement';

interface ColumnsContainerProps {
  element: {
    type: 'columns';
    id: string;
    children?: string[];
    properties?: {
      columnCount?: number;
      gap?: string;
      columnWidths?: string[];
    };
  };
  onSelect: (elementId: string, event: React.MouseEvent) => void;
  onDelete: (elementId: string) => void;
  onContextMenu: (event: React.MouseEvent, elementId: string) => void;
  updateElementProperty: (elementId: string, property: string, value: unknown) => void;
}

export const ColumnsContainer: React.FC<ColumnsContainerProps> = ({
  element,
  onSelect,
  onDelete,
  onContextMenu,
  updateElementProperty,
}) => {
  const columnCount = element.properties?.columnCount || 2;
  const gap = element.properties?.gap || '20px';
  const items = useElementsStore((s) => s.items);

  // Get children for each column
  // For now, distribute children evenly (can be enhanced with column assignment)
  const getColumnChildren = (columnIndex: number): string[] => {
    const children = element.children || [];
    // Simple distribution: each child goes to column based on its position
    return children.filter((_, idx) => idx % columnCount === columnIndex);
  };

  return (
    <div
      className="columns-container"
      style={{
        display: 'grid',
        gridTemplateColumns: `repeat(${columnCount}, 1fr)`,
        gap: gap,
        minHeight: '80px',
        border: '2px dashed #e5e7eb',
        borderRadius: '8px',
        padding: '16px',
      }}
      data-testid={`columns-container-${element.id}`}
    >
      {Array.from({ length: columnCount }).map((_, columnIndex) => (
        <ColumnDropZone
          key={columnIndex}
          containerId={element.id}
          columnIndex={columnIndex}
          children={getColumnChildren(columnIndex)}
          items={items}
          onSelect={onSelect}
          onDelete={onDelete}
          onContextMenu={onContextMenu}
          updateElementProperty={updateElementProperty}
        />
      ))}
    </div>
  );
};

interface ColumnDropZoneProps {
  containerId: string;
  columnIndex: number;
  children: string[];
  items: Record<string, FormElement>;
  onSelect: (elementId: string, event: React.MouseEvent) => void;
  onDelete: (elementId: string) => void;
  onContextMenu: (event: React.MouseEvent, elementId: string) => void;
  updateElementProperty: (elementId: string, property: string, value: unknown) => void;
}

const ColumnDropZone: React.FC<ColumnDropZoneProps> = ({
  containerId,
  columnIndex,
  children,
  items,
  onSelect,
  onDelete,
  onContextMenu,
  updateElementProperty,
}) => {
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
      className={`column-drop-zone ${isOver ? 'column-drop-zone-over' : ''}`}
      style={{
        border: '1px dashed #d1d5db',
        borderRadius: '4px',
        minHeight: '60px',
        padding: '8px',
        backgroundColor: isOver ? '#eff6ff' : '#f9fafb',
        transition: 'background-color 150ms ease',
      }}
      data-testid={`column-${columnIndex}-dropzone`}
    >
      {children.length === 0 ? (
        <div className="column-empty-state">
          <span className="text-gray-400 text-sm">Drop here</span>
        </div>
      ) : (
        <SortableContext items={children} strategy={verticalListSortingStrategy}>
          {children.map((childId) => {
            const childElement = items[childId];
            if (!childElement) return null;

            return (
              <SortableElement
                key={childId}
                element={childElement}
                isSelected={false} // TODO: pass from parent
                isMultiSelected={false}
                onSelect={onSelect}
                onDelete={onDelete}
                onContextMenu={onContextMenu}
                updateElementProperty={updateElementProperty}
              />
            );
          })}
        </SortableContext>
      )}
    </div>
  );
};
```

### 3. Update handleDndDragEnd for container drops

```typescript
const handleDndDragEnd = useCallback((event: DragEndEvent) => {
  const { active, over } = event;
  setActiveId(null);

  if (!over) return;

  const activeId = active.id as string;
  const overId = over.id as string;

  // Handle palette item drops
  if (activeId.startsWith('palette:')) {
    // ... existing palette logic ...

    // Check if dropping into a column
    if (overId.startsWith('column:')) {
      const [, containerId, columnIndexStr] = overId.split(':');
      const elementType = activeId.replace('palette:', '');
      handleAddElementToContainer(elementType, containerId, parseInt(columnIndexStr));
      return;
    }

    // ... rest of palette logic ...
  }

  // Handle dropping into a column
  if (overId.startsWith('column:')) {
    const [, containerId, columnIndexStr] = overId.split(':');

    // Validate: check container accepts this element type
    const container = items[containerId];
    const schema = getElementSchema(container?.type);

    if (schema?.container) {
      const { accepts, rejects } = schema.container;
      const draggedElement = items[activeId];

      if (draggedElement) {
        // Check accepts/rejects
        if (rejects?.includes(draggedElement.type)) {
          console.warn(`Container ${containerId} rejects ${draggedElement.type}`);
          return;
        }
        if (accepts?.length && !accepts.includes(draggedElement.type)) {
          console.warn(`Container ${containerId} doesn't accept ${draggedElement.type}`);
          return;
        }
      }
    }

    // Move element into container
    moveElement(activeId, containerId, 'inside');
    setAutoSaveStatus('saving');
    saveToHistory();
    return;
  }

  // Handle dragging OUT of container to root
  if (items[activeId]?.parent && !overId.startsWith('column:')) {
    // Element has parent but dropping on root-level element
    moveElement(activeId, overId, 'after');
    setAutoSaveStatus('saving');
    saveToHistory();
    return;
  }

  // Standard reordering at root level
  if (active.id !== over.id) {
    const oldIndex = order.indexOf(activeId);
    const newIndex = order.indexOf(overId);

    if (oldIndex !== -1 && newIndex !== -1) {
      const newOrder = arrayMove(order, oldIndex, newIndex);
      reorderElements(newOrder);
      setAutoSaveStatus('saving');
      saveToHistory();
    }
  }
}, [order, items, reorderElements, moveElement, handleAddElementToContainer, setAutoSaveStatus, saveToHistory]);
```

### 4. Add handleAddElementToContainer function

```typescript
const handleAddElementToContainer = useCallback((
  elementType: string,
  containerId: string,
  columnIndex: number
) => {
  const schema = getElementSchema(elementType);
  if (!schema) {
    console.warn(`No schema for element type: ${elementType}`);
    return;
  }

  const schemaDefaults = getSchemaDefaults(schema);
  const newElement = {
    id: uuidv4(),
    type: elementType,
    properties: {
      ...schemaDefaults,
      name: schemaDefaults.name || `${elementType}_${Date.now().toString(36)}`,
      label: schemaDefaults.label || elementType,
    },
    parent: containerId, // Set parent reference
    children: schema.container ? [] : undefined,
  };

  // Add element with parent reference
  addElement(newElement);

  // Update container's children array
  const container = items[containerId];
  if (container) {
    const newChildren = [...(container.children || []), newElement.id];
    updateElement(containerId, { children: newChildren });
  }

  setSelectedElements([newElement.id]);
  setAutoSaveStatus('saving');
  saveToHistory();
}, [addElement, updateElement, items, setSelectedElements, setAutoSaveStatus, saveToHistory]);
```

### 5. Add CSS for column drop states

```css
/* Column drop zone states */
.column-drop-zone {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.column-drop-zone-over {
  background-color: #dbeafe !important;
  border-color: #3b82f6 !important;
  border-style: solid !important;
}

.column-empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 50px;
}

/* Nested elements in containers */
.column-drop-zone .form-element {
  margin: 4px 0;
}
```

### 6. Custom collision detection for nested contexts

For complex nested scenarios, we may need custom collision detection:

```typescript
import { rectIntersection, getFirstCollision } from '@dnd-kit/core';

const customCollisionDetection: CollisionDetection = (args) => {
  // First check for column drop zones
  const columnCollisions = rectIntersection({
    ...args,
    droppableContainers: args.droppableContainers.filter(
      container => (container.id as string).startsWith('column:')
    ),
  });

  if (columnCollisions.length > 0) {
    return columnCollisions;
  }

  // Fall back to standard collision detection
  return closestCenter(args);
};
```

## Testing Checklist
- [ ] Drop palette item into empty column
- [ ] Drop palette item into column with existing elements
- [ ] Reorder elements within same column
- [ ] Drag element from one column to another
- [ ] Drag element from column back to root level
- [ ] Container accepts/rejects validation works
- [ ] Nested elements render correctly
- [ ] Selection works for nested elements
- [ ] Delete works for nested elements
- [ ] Undo/redo works for container operations

## Files Modified
- `src/react/admin/apps/form-builder-v2/components/elements/containers/ColumnsContainer.tsx`
- `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`
- `src/react/admin/apps/form-builder-v2/styles/form-builder.css`

## Notes
- Each column is its own SortableContext with its own items array
- Column ID format: `column:{containerId}:{columnIndex}`
- The moveElement store action handles parent-child relationship updates
- Custom collision detection may be needed for precise nested behavior
- Consider future: column-specific child assignment vs even distribution
