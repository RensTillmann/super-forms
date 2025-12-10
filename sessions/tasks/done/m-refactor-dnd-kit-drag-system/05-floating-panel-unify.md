---
name: 05-floating-panel-unify
status: pending
---

# Phase 5: Unify Floating Panel Drag System

## Goal
Convert the floating elements panel's separate drag implementation to use the same @dnd-kit system as the canvas. This ensures consistent behavior and eliminates the duplicated drag logic.

## Current State
The floating elements panel (lines 1169-1346 in FormBuilderV2.tsx) has its own drag implementation:
- Uses native `draggable`, `onDragStart`, `onDragOver`, `onDrop`
- Stores just index as string in dataTransfer
- Calls `handleReorder(dragIndex, hoverIndex)` on drop
- Completely separate from canvas drag system

## Success Criteria
- [ ] Floating panel uses @dnd-kit SortableContext
- [ ] Reordering in panel syncs with canvas (same store)
- [ ] Drag preview matches canvas style
- [ ] Panel reordering triggers undo/redo and auto-save
- [ ] Remove duplicated drag logic
- [ ] Consistent keyboard accessibility

## Implementation Steps

### 1. Create SortablePanelItem component

Create: `src/react/admin/apps/form-builder-v2/components/dnd/SortablePanelItem.tsx`

```typescript
import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Move, Trash2, Copy, Eye, EyeOff } from 'lucide-react';
import { FormElement } from '../../../../types';

interface SortablePanelItemProps {
  element: FormElement;
  isSelected: boolean;
  onClick: () => void;
  onDuplicate: (elementId: string) => void;
  onDelete: (elementId: string) => void;
  onToggleVisibility?: (elementId: string) => void;
}

export const SortablePanelItem: React.FC<SortablePanelItemProps> = ({
  element,
  isSelected,
  onClick,
  onDuplicate,
  onDelete,
  onToggleVisibility,
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
      className={`floating-element-item ${isSelected ? 'floating-element-selected' : ''} ${isDragging ? 'floating-element-dragging' : ''}`}
      onClick={onClick}
      data-testid={`panel-item-${element.id}`}
    >
      {/* Drag handle - ONLY this gets listeners */}
      <div
        {...attributes}
        {...listeners}
        className="floating-element-drag"
        data-testid={`panel-drag-handle-${element.id}`}
      >
        <Move size={12} />
      </div>

      <div className="floating-element-info">
        <div className="floating-element-label">
          {element.properties?.label || element.type}
        </div>
        <div className="floating-element-type">
          {element.type.replace('_', ' ')}
          {element.properties?.required && ' •'}
        </div>
      </div>

      <div className="floating-element-actions">
        {onToggleVisibility && (
          <button
            className="floating-element-btn"
            onClick={(e) => {
              e.stopPropagation();
              onToggleVisibility(element.id);
            }}
            title="Toggle visibility"
          >
            <Eye size={12} />
          </button>
        )}
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
          className="floating-element-btn floating-element-btn-danger"
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
```

### 2. Update FloatingElementsPanel component

The panel is defined inline in FormBuilderV2.tsx (lines 1169-1346). Refactor to use SortableContext:

```typescript
// Inside the FloatingElementsPanel render section
<SortableContext
  items={order}
  strategy={verticalListSortingStrategy}
>
  {orderedElements.map((element) => (
    <SortablePanelItem
      key={element.id}
      element={element}
      isSelected={selectedElement?.id === element.id}
      onClick={() => onElementClick(element.id)}
      onDuplicate={handleDuplicateElement}
      onDelete={handleDeleteElement}
    />
  ))}
</SortableContext>
```

### 3. Share DndContext between canvas and panel

The floating panel needs to be INSIDE the same DndContext as the canvas for unified drag behavior.

Two approaches:

**Option A: Lift DndContext higher**
Wrap both canvas and floating panel in a single DndContext at a higher level.

**Option B: Panel inside canvas DndContext**
Position the panel portal inside the DndContext wrapper.

Recommended: Option A - lift DndContext to wrap the entire builder area:

```tsx
<DndContext
  sensors={sensors}
  collisionDetection={closestCenter}
  onDragStart={handleDndDragStart}
  onDragOver={handleDndDragOver}
  onDragEnd={handleDndDragEnd}
>
  {/* Canvas area */}
  <div className="canvas-container">
    <SortableContext items={order} strategy={verticalListSortingStrategy}>
      {/* Canvas elements */}
    </SortableContext>
  </div>

  {/* Floating Elements Panel - shares same DndContext */}
  {showFloatingPanel && (
    <FloatingElementsPanel
      orderedElements={orderedElements}
      order={order}
      selectedElement={selectedElement}
      onElementClick={handleSelectElement}
      onDuplicate={handleDuplicateElement}
      onDelete={handleDeleteElement}
      // ... other props
    />
  )}

  {/* Palette area - also shares DndContext */}
  <div className="element-palette">
    {/* Palette items */}
  </div>

  {/* Single DragOverlay for all drag sources */}
  <DragOverlay>
    {activeId && renderDragOverlay(activeId)}
  </DragOverlay>
</DndContext>
```

### 4. Remove old panel drag handlers

Delete the following from the FloatingElementsPanel section:
- `handleReorder` function (lines 1212-1218)
- Native drag handlers on panel items:
  - `draggable`
  - `onDragStart={(e) => { e.dataTransfer.setData(...) }}`
  - `onDragOver={(e) => e.preventDefault()}`
  - `onDrop={(e) => { handleReorder(...) }}`

### 5. Update DragOverlay to handle panel items

The DragOverlay already handles canvas elements. Panel items use the same element IDs, so the same preview works:

```typescript
const renderDragOverlay = (activeId: string) => {
  // Check if it's a palette item
  if (activeId.startsWith('palette:')) {
    return <PaletteDragPreview {...} />;
  }

  // It's an element (from canvas OR panel - same ID)
  const element = items[activeId];
  if (element) {
    return <ElementDragPreview element={element} />;
  }

  return null;
};
```

### 6. Add CSS for panel drag states

```css
/* Panel item drag states */
.floating-element-item {
  transition: opacity 150ms ease, transform 150ms ease;
}

.floating-element-dragging {
  opacity: 0.5;
}

.floating-element-selected {
  background-color: #eff6ff;
  border-color: #3b82f6;
}

/* Drag handle cursor */
.floating-element-drag {
  cursor: grab;
}

.floating-element-drag:active {
  cursor: grabbing;
}
```

### 7. Handle cross-context drag (panel to canvas)

Since both panel and canvas share the same DndContext and SortableContext with the same `order` array, dragging from panel automatically reorders in canvas. The store update propagates to both views.

If you want to drag FROM panel TO a specific canvas position (not just reorder), the collision detection will determine the drop target based on pointer position over canvas elements.

## Testing Checklist
- [ ] Drag reorder in panel updates canvas order
- [ ] Drag reorder in canvas updates panel order
- [ ] Drag preview appears when dragging from panel
- [ ] Keyboard navigation works in panel (Tab, Space, arrows)
- [ ] Click to select still works
- [ ] Duplicate/Delete buttons still work
- [ ] Panel collapse/expand still works
- [ ] Undo/redo works for panel reorders
- [ ] Auto-save triggers for panel reorders

## Files Created
- `src/react/admin/apps/form-builder-v2/components/dnd/SortablePanelItem.tsx`

## Files Modified
- `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`
- `src/react/admin/apps/form-builder-v2/styles/form-builder.css`

## Notes
- Key insight: Panel and canvas share same `order` array from store
- Single DndContext ensures unified drag behavior
- Same element IDs mean same drag preview works for both contexts
- Removing duplicated drag logic reduces code complexity
- Consider extracting FloatingElementsPanel to separate file for clarity
