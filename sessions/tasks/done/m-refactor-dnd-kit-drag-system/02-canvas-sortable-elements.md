---
name: 02-canvas-sortable-elements
status: pending
---

# Phase 2: Convert Canvas Elements to useSortable

## Goal
Replace native draggable props on canvas elements with the useSortable hook. The key change: attach drag listeners ONLY to the Move handle button, not the entire element. This fixes the scroll conflict issue.

## Success Criteria
- [ ] Canvas elements use useSortable hook
- [ ] Drag listeners attached ONLY to Move button (not entire element)
- [ ] Scrolling on element body doesn't trigger drag
- [ ] Reordering works via @dnd-kit
- [ ] DragOverlay shows proper element preview during drag
- [ ] Smooth animations during drag/drop
- [ ] undo/redo still works after reorder
- [ ] auto-save triggers after reorder

## Implementation Steps

### 1. Create SortableElement component

Create new file: `src/react/admin/apps/form-builder-v2/components/dnd/SortableElement.tsx`

```typescript
import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Move, Settings, Trash2 } from 'lucide-react';
import { FormElement } from '../../../../types';
import { ElementRenderer } from '../ElementRenderer';

interface SortableElementProps {
  element: FormElement;
  isSelected: boolean;
  isMultiSelected: boolean;
  onSelect: (elementId: string, event: React.MouseEvent) => void;
  onDelete: (elementId: string) => void;
  onContextMenu: (event: React.MouseEvent, elementId: string) => void;
  updateElementProperty: (elementId: string, property: string, value: unknown) => void;
}

export const SortableElement: React.FC<SortableElementProps> = ({
  element,
  isSelected,
  isMultiSelected,
  onSelect,
  onDelete,
  onContextMenu,
  updateElementProperty,
}) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: element.id });

  const style = {
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
      />
    </div>
  );
};
```

### 2. Create DragOverlay content component

Add to same file or create `ElementDragPreview.tsx`:

```typescript
interface ElementDragPreviewProps {
  element: FormElement;
}

export const ElementDragPreview: React.FC<ElementDragPreviewProps> = ({ element }) => {
  return (
    <div className="drag-overlay-element">
      <div className="drag-preview-header">
        <Move size={14} className="text-blue-500" />
        <span className="drag-preview-label">
          {element.properties?.label || element.type}
        </span>
      </div>
      <div className="drag-preview-type">
        {element.type.replace('_', ' ')}
      </div>
    </div>
  );
};
```

### 3. Update FormBuilderV2 to use SortableElement

In the canvas rendering section (lines 3300-3355), replace the existing element rendering with:

```tsx
import { SortableElement, ElementDragPreview } from './components/dnd/SortableElement';

// Inside DndContext...
<SortableContext items={order} strategy={verticalListSortingStrategy}>
  {orderedElements.map((element) => (
    <SortableElement
      key={element.id}
      element={element}
      isSelected={selectedElement?.id === element.id}
      isMultiSelected={multiSelectElements.includes(element.id)}
      onSelect={handleSelectElement}
      onDelete={handleDeleteElement}
      onContextMenu={handleContextMenu}
      updateElementProperty={updateElementProperty}
    />
  ))}
</SortableContext>

{/* Enhanced DragOverlay */}
<DragOverlay>
  {activeId && items[activeId] ? (
    <ElementDragPreview element={items[activeId]} />
  ) : null}
</DragOverlay>
```

### 4. Update handleDndDragEnd for reordering

```typescript
const handleDndDragEnd = useCallback((event: DragEndEvent) => {
  const { active, over } = event;
  setActiveId(null);

  if (!over) return;

  // Reordering existing elements
  if (active.id !== over.id) {
    const oldIndex = order.indexOf(active.id as string);
    const newIndex = order.indexOf(over.id as string);

    if (oldIndex !== -1 && newIndex !== -1) {
      const newOrder = arrayMove(order, oldIndex, newIndex);
      reorderElements(newOrder);
      setAutoSaveStatus('saving');
      saveToHistory();
    }
  }
}, [order, reorderElements, setAutoSaveStatus, saveToHistory]);
```

### 5. Add CSS for drag preview and states

```css
/* Element drag preview in overlay */
.drag-overlay-element {
  background: white;
  border: 2px solid #3b82f6;
  border-radius: 8px;
  padding: 12px 16px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
  min-width: 200px;
  max-width: 300px;
}

.drag-preview-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.drag-preview-label {
  font-weight: 500;
  font-size: 14px;
  color: #1f2937;
}

.drag-preview-type {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
  text-transform: capitalize;
}

/* Cursor on drag handle only */
.element-control-btn[data-testid^="drag-handle"] {
  cursor: grab;
}

.element-control-btn[data-testid^="drag-handle"]:active {
  cursor: grabbing;
}

/* Remove cursor: move from entire element (was causing scroll conflict) */
.form-element {
  cursor: default;
}
```

### 6. Remove native drag props from canvas elements

Remove from the element div:
- `draggable`
- `onDragStart`
- `onDragEnd`
- `onDragOver`
- `onDrop`

These are now handled by useSortable hook.

## Testing Checklist
- [ ] Elements reorder correctly via Move handle drag
- [ ] Clicking/scrolling on element body does NOT trigger drag
- [ ] DragOverlay shows element preview during drag
- [ ] Drop animation is smooth
- [ ] undo (Ctrl+Z) reverts reorder
- [ ] Auto-save triggers after reorder
- [ ] Selected element stays selected after drag
- [ ] Multi-select elements work (if dragging one of them)
- [ ] Works on touch devices (test mobile view)
- [ ] Keyboard accessibility: Tab to focus, Space to grab, arrows to move

## Files Created
- `src/react/admin/apps/form-builder-v2/components/dnd/SortableElement.tsx`

## Files Modified
- `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`
- `src/react/admin/apps/form-builder-v2/styles/form-builder.css`

## Notes
- The key fix is `{...listeners}` ONLY on the Move button, not the entire element
- Native drag handlers can be removed once this works
- DragOverlay provides touch-compatible preview the native API lacks
- arrayMove from @dnd-kit/sortable handles the index swap
