import { create } from 'zustand';
import type { NodeType, StyleProperties } from '../../../schemas/styles';
import { useElementsStore } from '../store/useElementsStore';
import type { FormElement } from '../types';

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
    if (!clipboard?.styleOverrides) return;

    const { setStyleOverrides } = useElementsStore.getState();

    // Copy each node's overrides to the target
    Object.entries(clipboard.styleOverrides).forEach(([nodeType, overrides]) => {
      if (overrides) {
        setStyleOverrides(
          targetElementId,
          nodeType as NodeType,
          overrides as Partial<StyleProperties>
        );
      }
    });
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
