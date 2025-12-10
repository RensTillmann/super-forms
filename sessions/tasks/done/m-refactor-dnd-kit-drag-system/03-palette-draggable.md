---
name: 03-palette-draggable
status: pending
---

# Phase 3: Convert Element Palette to useDraggable

## Goal
Convert the bottom element palette (tray) to use @dnd-kit's useDraggable hook. Palette items are NOT sortable (they stay in fixed positions), but they need to be draggable onto the canvas to create new elements.

## Success Criteria
- [ ] Palette items use useDraggable hook
- [ ] Dragging palette item shows preview in DragOverlay
- [ ] Dropping on canvas creates new element at correct position
- [ ] Dropping on empty canvas works
- [ ] Click on palette item still adds element (existing behavior)
- [ ] Touch drag works on mobile
- [ ] Distinguishes "new" elements from "existing" via ID check

## Implementation Steps

### 1. Create DraggablePaletteItem component

Create: `src/react/admin/apps/form-builder-v2/components/dnd/DraggablePaletteItem.tsx`

```typescript
import React from 'react';
import { useDraggable } from '@dnd-kit/core';
import { cn } from '../../../../../lib/utils';

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
        "flex flex-col items-center justify-center gap-1 p-3 rounded-xl",
        "bg-background border border-border",
        "cursor-grab transition-all duration-150",
        "hover:bg-accent hover:border-primary/30 hover:shadow-sm",
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
```

### 2. Create PaletteDragPreview component

```typescript
interface PaletteDragPreviewProps {
  elementType: string;
  label: string;
  icon: React.ComponentType<{ className?: string }>;
}

export const PaletteDragPreview: React.FC<PaletteDragPreviewProps> = ({
  elementType,
  label,
  icon: Icon,
}) => {
  return (
    <div className="palette-drag-preview">
      <Icon className="w-6 h-6 text-primary" />
      <div className="preview-text">
        <span className="preview-label">{label}</span>
        <span className="preview-hint">Drop to add</span>
      </div>
    </div>
  );
};
```

### 3. Update handleDndDragEnd to handle new elements

```typescript
const handleDndDragEnd = useCallback((event: DragEndEvent) => {
  const { active, over } = event;
  setActiveId(null);

  if (!over) return;

  const activeId = active.id as string;

  // Check if it's a palette item (new element)
  if (activeId.startsWith('palette:')) {
    const elementType = activeId.replace('palette:', '');
    const dropTargetId = over.id as string;

    // Determine drop position
    let dropIndex = order.length; // Default to end

    if (dropTargetId === 'canvas-drop-zone') {
      // Dropped on empty canvas or general canvas area
      dropIndex = order.length;
    } else if (items[dropTargetId]) {
      // Dropped on existing element - insert after
      dropIndex = order.indexOf(dropTargetId) + 1;
    }

    // Create new element using schema system
    handleAddElementAtPosition({ type: elementType, isNew: true }, dropIndex);
    return;
  }

  // Existing reordering logic...
  if (active.id !== over.id) {
    const oldIndex = order.indexOf(activeId);
    const newIndex = order.indexOf(over.id as string);

    if (oldIndex !== -1 && newIndex !== -1) {
      const newOrder = arrayMove(order, oldIndex, newIndex);
      reorderElements(newOrder);
      setAutoSaveStatus('saving');
      saveToHistory();
    }
  }
}, [order, items, reorderElements, handleAddElementAtPosition, setAutoSaveStatus, saveToHistory]);
```

### 4. Add canvas-level droppable for empty canvas

When canvas is empty, we need a droppable target. Use useDroppable:

```typescript
import { useDroppable } from '@dnd-kit/core';

// Inside canvas section
const { setNodeRef: setCanvasDropRef } = useDroppable({
  id: 'canvas-drop-zone',
});

// Empty canvas state
{orderedElements.length === 0 && (
  <div
    ref={setCanvasDropRef}
    className={cn(
      "drop-zone",
      isDragging && "drop-zone-active"
    )}
    data-testid="empty-canvas-drop-zone"
  >
    <p>Drag elements here to build your form</p>
  </div>
)}
```

### 5. Update DragOverlay to handle palette items

```tsx
<DragOverlay>
  {activeId && (
    <>
      {/* Existing element preview */}
      {items[activeId] && (
        <ElementDragPreview element={items[activeId]} />
      )}

      {/* Palette item preview */}
      {activeId.startsWith('palette:') && (
        <PaletteDragPreview
          elementType={activeId.replace('palette:', '')}
          label={active?.data?.current?.label || activeId}
          icon={getElementIcon(activeId.replace('palette:', ''))}
        />
      )}
    </>
  )}
</DragOverlay>
```

### 6. Replace palette items in FormBuilderV2

Replace the existing palette item rendering (lines 3670-3697):

```tsx
{getFilteredElements().map((element) => {
  const isSingleElement = getFilteredElements().length === 1;

  return (
    <DraggablePaletteItem
      key={element.type}
      element={element}
      isSingleElement={isSingleElement}
      onClick={() => handleAddElement(element.type, element.label, element.icon)}
    />
  );
})}
```

### 7. Add CSS for palette drag preview

```css
/* Palette item drag preview */
.palette-drag-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  background: white;
  border: 2px dashed #3b82f6;
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
}

.palette-drag-preview .preview-text {
  display: flex;
  flex-direction: column;
}

.palette-drag-preview .preview-label {
  font-weight: 500;
  font-size: 14px;
  color: #1f2937;
}

.palette-drag-preview .preview-hint {
  font-size: 11px;
  color: #6b7280;
}

/* Drop zone active state when dragging palette item */
.drop-zone-active {
  border-color: #3b82f6;
  background-color: #eff6ff;
}
```

## Testing Checklist
- [ ] Drag palette item shows preview overlay
- [ ] Drop on existing element inserts after that element
- [ ] Drop on empty canvas adds element
- [ ] Click on palette item still works (adds to end)
- [ ] Touch drag works on mobile device
- [ ] Multiple rapid drags don't cause issues
- [ ] Search filtering still works in palette
- [ ] Category switching still works
- [ ] "Press Enter to add" still works when single result

## Files Created
- `src/react/admin/apps/form-builder-v2/components/dnd/DraggablePaletteItem.tsx`

## Files Modified
- `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`
- `src/react/admin/apps/form-builder-v2/styles/form-builder.css`

## Notes
- Palette items use `palette:` prefix in ID to distinguish from element UUIDs
- useDraggable (not useSortable) because palette items don't reorder
- The `data` property on useDraggable carries metadata for the drop handler
- Empty canvas needs explicit droppable with useDroppable hook
- Maintain click handler for non-drag interaction
