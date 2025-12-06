---
name: 04-context-menu-shortcuts
status: not-started
---

# Subtask 4: Context Menu & Shortcuts

## Goal

Add style-related actions to the element context menu and implement keyboard shortcuts for common style operations.

## Success Criteria

- [ ] "Copy Style" context menu option
- [ ] "Paste Style" context menu option
- [ ] "Set as Global" option (promote individual to global)
- [ ] "Reset to Global" option
- [ ] Keyboard shortcuts: Ctrl+Alt+C (copy style), Ctrl+Alt+V (paste style)
- [ ] Visual feedback when copying/pasting
- [ ] Style clipboard stored in session

## Files to Modify

```
/src/react/admin/apps/form-builder-v2/
├── components/canvas/ElementContextMenu.tsx  # Add style menu items
├── hooks/useKeyboardShortcuts.ts             # Add style shortcuts
├── store/useBuilderStore.ts                  # Add style clipboard
```

## Files to Create

```
/src/react/admin/apps/form-builder-v2/
├── hooks/useStyleClipboard.ts                # Style copy/paste logic
```

## Implementation Details

### 1. useStyleClipboard.ts

```typescript
import { create } from 'zustand';
import { NodeType, StyleProperties } from '@/schemas/styles';
import { useElementsStore } from '../store/useElementsStore';
import { FormElement } from '../types';

interface StyleClipboard {
  sourceElementId: string;
  sourceElementType: string;
  styleOverrides: FormElement['styleOverrides'];
  copiedAt: number;
}

interface StyleClipboardStore {
  clipboard: StyleClipboard | null;
  copy: (elementId: string) => void;
  paste: (targetElementId: string) => void;
  clear: () => void;
  canPaste: (targetElementType: string) => boolean;
}

export const useStyleClipboard = create<StyleClipboardStore>((set, get) => ({
  clipboard: null,

  copy: (elementId) => {
    const element = useElementsStore.getState().items[elementId];
    if (!element) return;

    set({
      clipboard: {
        sourceElementId: elementId,
        sourceElementType: element.type,
        styleOverrides: element.styleOverrides
          ? JSON.parse(JSON.stringify(element.styleOverrides))
          : undefined,
        copiedAt: Date.now(),
      },
    });
  },

  paste: (targetElementId) => {
    const { clipboard } = get();
    if (!clipboard) return;

    const { copyStyleOverrides } = useElementsStore.getState();
    copyStyleOverrides(clipboard.sourceElementId, targetElementId);
  },

  clear: () => {
    set({ clipboard: null });
  },

  canPaste: (targetElementType) => {
    const { clipboard } = get();
    if (!clipboard) return false;

    // For now, allow pasting between same element types
    // Could be extended to allow compatible types
    return clipboard.sourceElementType === targetElementType;
  },
}));

// Hooks for React components
export function useCanPasteStyle(elementType: string): boolean {
  return useStyleClipboard((state) => state.canPaste(elementType));
}

export function useHasClipboard(): boolean {
  return useStyleClipboard((state) => state.clipboard !== null);
}
```

### 2. ElementContextMenu.tsx Updates

Add style actions to the existing context menu:

```tsx
import {
  Copy,
  ClipboardPaste,
  Paintbrush,
  Link,
  RotateCcw,
} from 'lucide-react';
import {
  ContextMenuSeparator,
  ContextMenuItem,
  ContextMenuSub,
  ContextMenuSubContent,
  ContextMenuSubTrigger,
} from '@/components/ui/context-menu';
import { useStyleClipboard, useCanPasteStyle } from '../hooks/useStyleClipboard';
import { styleRegistry, getElementNodes } from '@/schemas/styles';
import { useElementsStore } from '../store/useElementsStore';
import { toast } from 'sonner';

// In the context menu component:

function StyleMenuItems({ elementId, elementType }: {
  elementId: string;
  elementType: string;
}) {
  const { copy, paste, clipboard } = useStyleClipboard();
  const canPaste = useCanPasteStyle(elementType);
  const element = useElementsStore((state) => state.items[elementId]);
  const { clearAllStyleOverrides } = useElementsStore();

  const hasOverrides =
    element?.styleOverrides &&
    Object.keys(element.styleOverrides).length > 0;

  const handleCopyStyle = () => {
    copy(elementId);
    toast.success('Style copied', {
      description: 'Use Ctrl+Alt+V or context menu to paste',
    });
  };

  const handlePasteStyle = () => {
    paste(elementId);
    toast.success('Style pasted');
  };

  const handleResetToGlobal = () => {
    clearAllStyleOverrides(elementId);
    toast.success('Reset to global styles');
  };

  const handleSetAsGlobal = () => {
    if (!element?.styleOverrides) return;

    // Promote all overrides to global
    const nodes = getElementNodes(elementType);
    nodes.forEach((nodeType) => {
      const overrides = element.styleOverrides?.[nodeType];
      if (overrides) {
        Object.entries(overrides).forEach(([prop, value]) => {
          styleRegistry.setGlobalProperty(
            nodeType,
            prop as keyof import('@/schemas/styles').StyleProperties,
            value
          );
        });
      }
    });

    // Clear local overrides
    clearAllStyleOverrides(elementId);
    toast.success('Styles promoted to global theme');
  };

  return (
    <>
      <ContextMenuSeparator />

      <ContextMenuSub>
        <ContextMenuSubTrigger>
          <Paintbrush className="mr-2 h-4 w-4" />
          Styles
        </ContextMenuSubTrigger>
        <ContextMenuSubContent className="w-48">
          <ContextMenuItem onClick={handleCopyStyle}>
            <Copy className="mr-2 h-4 w-4" />
            Copy Style
            <span className="ml-auto text-xs text-muted-foreground">
              Ctrl+Alt+C
            </span>
          </ContextMenuItem>

          <ContextMenuItem
            onClick={handlePasteStyle}
            disabled={!canPaste}
          >
            <ClipboardPaste className="mr-2 h-4 w-4" />
            Paste Style
            <span className="ml-auto text-xs text-muted-foreground">
              Ctrl+Alt+V
            </span>
          </ContextMenuItem>

          <ContextMenuSeparator />

          <ContextMenuItem
            onClick={handleSetAsGlobal}
            disabled={!hasOverrides}
          >
            <Link className="mr-2 h-4 w-4" />
            Set as Global
          </ContextMenuItem>

          <ContextMenuItem
            onClick={handleResetToGlobal}
            disabled={!hasOverrides}
          >
            <RotateCcw className="mr-2 h-4 w-4" />
            Reset to Global
          </ContextMenuItem>
        </ContextMenuSubContent>
      </ContextMenuSub>
    </>
  );
}

// Use in the main context menu:
<StyleMenuItems elementId={elementId} elementType={element.type} />
```

### 3. useKeyboardShortcuts.ts Updates

Add style-related keyboard shortcuts:

```typescript
import { useEffect, useCallback } from 'react';
import { useStyleClipboard } from './useStyleClipboard';
import { useBuilderStore } from '../store/useBuilderStore';
import { useElementsStore } from '../store/useElementsStore';
import { toast } from 'sonner';

export function useStyleKeyboardShortcuts() {
  const { copy, paste, canPaste } = useStyleClipboard();
  const selectedElements = useBuilderStore((state) => state.selectedElements);
  const items = useElementsStore((state) => state.items);

  const handleKeyDown = useCallback(
    (event: KeyboardEvent) => {
      // Only handle if an element is selected
      if (selectedElements.length !== 1) return;

      const elementId = selectedElements[0];
      const element = items[elementId];
      if (!element) return;

      // Ctrl+Alt+C - Copy Style
      if (event.ctrlKey && event.altKey && event.key === 'c') {
        event.preventDefault();
        copy(elementId);
        toast.success('Style copied');
        return;
      }

      // Ctrl+Alt+V - Paste Style
      if (event.ctrlKey && event.altKey && event.key === 'v') {
        event.preventDefault();
        if (canPaste(element.type)) {
          paste(elementId);
          toast.success('Style pasted');
        } else {
          toast.error('Cannot paste style', {
            description: 'No compatible style in clipboard',
          });
        }
        return;
      }
    },
    [selectedElements, items, copy, paste, canPaste]
  );

  useEffect(() => {
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handleKeyDown]);
}

// Use in FormBuilderV2.tsx:
// useStyleKeyboardShortcuts();
```

### 4. Visual Feedback Component

Add a small indicator when style is copied:

```tsx
// StyleClipboardIndicator.tsx
import { useStyleClipboard } from '../hooks/useStyleClipboard';
import { Paintbrush } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export function StyleClipboardIndicator() {
  const clipboard = useStyleClipboard((state) => state.clipboard);

  if (!clipboard) return null;

  return (
    <Badge
      variant="secondary"
      className="fixed bottom-4 right-4 gap-2 animate-in fade-in slide-in-from-bottom-2"
    >
      <Paintbrush className="h-3 w-3" />
      Style copied
    </Badge>
  );
}
```

## Testing

1. **Copy Style:**
   - Right-click element → Styles → Copy Style
   - Verify toast confirmation
   - Verify Ctrl+Alt+C works

2. **Paste Style:**
   - Copy style from one element
   - Paste to another element of same type
   - Verify styles transfer correctly
   - Verify Ctrl+Alt+V works

3. **Set as Global:**
   - Create overrides on an element
   - Right-click → Styles → Set as Global
   - Verify global styles updated
   - Verify other elements receive the style

4. **Reset to Global:**
   - Create overrides on an element
   - Right-click → Styles → Reset to Global
   - Verify overrides removed
   - Verify element uses global styles

## Dependencies

- Subtask 01 (Core Schema & Registry)
- Subtask 02 (Store Integration)
- sonner for toast notifications

## Notes

- Style clipboard persists only for the session
- Consider adding undo/redo integration
- Cross-element-type paste could be expanded (e.g., text → email share common nodes)
- Keyboard shortcuts should not conflict with existing shortcuts
