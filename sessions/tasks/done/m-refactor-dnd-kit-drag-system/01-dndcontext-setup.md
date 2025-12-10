---
name: 01-dndcontext-setup
status: complete
---

# Phase 1: DndContext Infrastructure Setup

## Goal
Wrap FormBuilder with DndContext provider and configure the foundational @dnd-kit infrastructure. Keep existing drag handlers working alongside during migration.

## Success Criteria
- [ ] DndContext wraps the canvas area in FormBuilderV2
- [ ] Sensors configured: PointerSensor (8px distance activation), KeyboardSensor
- [ ] Basic event handlers wired: onDragStart, onDragOver, onDragEnd
- [ ] New activeId state to track currently dragged element
- [ ] Existing native drag still works (parallel operation)
- [ ] No visual regressions

## Implementation Steps

### 1. Add @dnd-kit imports to FormBuilderV2.tsx

```typescript
import {
  DndContext,
  DragEndEvent,
  DragOverEvent,
  DragStartEvent,
  PointerSensor,
  KeyboardSensor,
  useSensor,
  useSensors,
  DragOverlay,
  closestCenter,
} from '@dnd-kit/core';

import {
  SortableContext,
  verticalListSortingStrategy,
  arrayMove,
  sortableKeyboardCoordinates,
} from '@dnd-kit/sortable';
```

### 2. Configure sensors (near other state declarations ~line 1544)

```typescript
// @dnd-kit sensors for pointer and keyboard
const sensors = useSensors(
  useSensor(PointerSensor, {
    activationConstraint: {
      distance: 8, // Prevents accidental drags on click
    },
  }),
  useSensor(KeyboardSensor, {
    coordinateGetter: sortableKeyboardCoordinates,
  })
);

// Track active drag for DragOverlay
const [activeId, setActiveId] = useState<string | null>(null);
```

### 3. Create @dnd-kit event handlers

```typescript
// @dnd-kit drag handlers (will gradually replace native handlers)
const handleDndDragStart = useCallback((event: DragStartEvent) => {
  const { active } = event;
  setActiveId(active.id as string);
}, []);

const handleDndDragOver = useCallback((event: DragOverEvent) => {
  // Will be used later for container drop detection
  // For now, just log to verify events fire
  console.log('[dnd-kit] dragOver:', event.over?.id);
}, []);

const handleDndDragEnd = useCallback((event: DragEndEvent) => {
  const { active, over } = event;
  setActiveId(null);

  if (!over) return;

  // For now, log to verify the system works
  console.log('[dnd-kit] dragEnd:', { active: active.id, over: over.id });
}, []);
```

### 4. Wrap canvas with DndContext

Find the canvas container (around line 3280) and wrap with DndContext:

```tsx
<DndContext
  sensors={sensors}
  collisionDetection={closestCenter}
  onDragStart={handleDndDragStart}
  onDragOver={handleDndDragOver}
  onDragEnd={handleDndDragEnd}
>
  {/* Existing canvas content */}
  <div className="canvas-container">
    {/* ... existing element rendering ... */}
  </div>

  {/* DragOverlay for custom drag preview */}
  <DragOverlay>
    {activeId ? (
      <div className="drag-overlay-preview">
        Dragging: {activeId}
      </div>
    ) : null}
  </DragOverlay>
</DndContext>
```

### 5. Add basic DragOverlay CSS

In form-builder.css, add:

```css
/* @dnd-kit drag overlay styles */
.drag-overlay-preview {
  padding: 12px 16px;
  background: white;
  border: 2px solid #3b82f6;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  font-size: 14px;
  opacity: 0.9;
}
```

## Testing Checklist
- [ ] Page loads without errors
- [ ] Console shows dnd-kit events when attempting drags
- [ ] Existing native drag still works for canvas elements
- [ ] Existing native drag still works for palette items
- [ ] No TypeScript errors
- [ ] Build succeeds

## Files Modified
- `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`
- `src/react/admin/apps/form-builder-v2/styles/form-builder.css`

## Notes
- This phase establishes the foundation without breaking existing functionality
- The native drag handlers remain active alongside @dnd-kit
- Console logging helps verify event flow before removing old system
- DragOverlay is minimal placeholder; will be enhanced in Phase 2
