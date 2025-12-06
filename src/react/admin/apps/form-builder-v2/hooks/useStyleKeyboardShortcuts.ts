import { useEffect, useCallback } from 'react';
import { useStyleClipboard } from './useStyleClipboard';
import { useBuilderStore } from '../store/useBuilderStore';
import { useElementsStore } from '../store/useElementsStore';

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
        return;
      }

      // Ctrl+Alt+V - Paste Style
      if (event.ctrlKey && event.altKey && event.key === 'v') {
        event.preventDefault();
        if (canPaste(element.type)) {
          paste(elementId);
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
