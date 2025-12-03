import { create } from 'zustand';
import { BuilderState } from '../types';

interface BuilderActions {
  setDraggedElement: (elementId: string | null) => void;
  setHoveredElement: (elementId: string | null) => void;
  setSelectedElements: (elementIds: string[]) => void;
  addSelectedElement: (elementId: string) => void;
  removeSelectedElement: (elementId: string) => void;
  clearSelection: () => void;
  setDropTarget: (target: { id: string; position: 'before' | 'after' | 'inside' } | null) => void;
}

type BuilderStore = BuilderState & BuilderActions;

export const useBuilderStore = create<BuilderStore>((set) => ({
  draggedElement: null,
  hoveredElement: null,
  selectedElements: [],
  dropZoneActive: false,
  dropTarget: null,

  setDraggedElement: (elementId) => {
    set({
      draggedElement: elementId,
      dropZoneActive: elementId !== null,
      dropTarget: elementId === null ? null : undefined,
    });
  },

  setHoveredElement: (elementId) => {
    set({ hoveredElement: elementId });
  },

  setSelectedElements: (elementIds) => {
    set({ selectedElements: elementIds });
  },

  addSelectedElement: (elementId) => {
    set((state) => ({
      selectedElements: state.selectedElements.includes(elementId)
        ? state.selectedElements
        : [...state.selectedElements, elementId],
    }));
  },

  removeSelectedElement: (elementId) => {
    set((state) => ({
      selectedElements: state.selectedElements.filter(id => id !== elementId),
    }));
  },

  clearSelection: () => {
    set({ selectedElements: [] });
  },

  setDropTarget: (target) => {
    set({ dropTarget: target });
  },
}));
