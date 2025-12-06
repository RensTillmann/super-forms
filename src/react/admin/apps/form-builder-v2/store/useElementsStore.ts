import { create } from 'zustand';
import { ElementsState, FormElement, DeviceVisibility } from '../types';
import type { NodeType, StyleProperties } from '../../../schemas/styles';

interface ElementsActions {
  addElement: (element: FormElement, index?: number) => void;
  removeElement: (elementId: string) => void;
  updateElement: (id: string, updates: Partial<FormElement>) => void;
  moveElement: (elementId: string, targetId?: string, position?: 'before' | 'after' | 'inside', targetIndex?: number) => void;
  updateDeviceVisibility: (elementId: string, device: keyof DeviceVisibility, visible: boolean) => void;
  reorderElements: (order: string[]) => void;
  loadElements: (state: ElementsState) => void;
  // Style override actions
  setStyleOverride: (elementId: string, nodeType: NodeType, property: keyof StyleProperties, value: StyleProperties[keyof StyleProperties]) => void;
  setStyleOverrides: (elementId: string, nodeType: NodeType, overrides: Partial<StyleProperties>) => void;
  removeStyleOverride: (elementId: string, nodeType: NodeType, property: keyof StyleProperties) => void;
  clearNodeStyleOverrides: (elementId: string, nodeType: NodeType) => void;
  clearAllStyleOverrides: (elementId: string) => void;
  copyStyleOverrides: (sourceElementId: string, targetElementId: string) => void;
}

type ElementsStore = ElementsState & ElementsActions;

export const useElementsStore = create<ElementsStore>((set, _get) => ({
  items: {},
  order: [],
  deviceVisibility: {},

  addElement: (element, index) => {
    set((state) => {
      const newItems = { ...state.items, [element.id]: element };
      const newOrder = [...state.order];

      if (index !== undefined) {
        newOrder.splice(index, 0, element.id);
      } else {
        newOrder.push(element.id);
      }

      const newDeviceVisibility = {
        ...state.deviceVisibility,
        [element.id]: { desktop: true, tablet: true, mobile: true },
      };

      return {
        items: newItems,
        order: newOrder,
        deviceVisibility: newDeviceVisibility,
      };
    });
  },

  removeElement: (elementId) => {
    set((state) => {
      const element = state.items[elementId];
      if (!element) return state;

      const newItems = { ...state.items };
      const newDeviceVisibility = { ...state.deviceVisibility };
      let newOrder = [...state.order];

      // Remove from parent's children if it has a parent
      if (element.parent && newItems[element.parent]?.children) {
        newItems[element.parent] = {
          ...newItems[element.parent],
          children: newItems[element.parent].children!.filter(id => id !== elementId),
        };
      }

      // Remove all children recursively
      const removeRecursive = (id: string) => {
        const el = newItems[id];
        if (el?.children) {
          el.children.forEach(childId => removeRecursive(childId));
        }
        delete newItems[id];
        delete newDeviceVisibility[id];
        newOrder = newOrder.filter(orderId => orderId !== id);
      };

      removeRecursive(elementId);

      return {
        items: newItems,
        order: newOrder,
        deviceVisibility: newDeviceVisibility,
      };
    });
  },

  updateElement: (id, updates) => {
    set((state) => {
      if (!state.items[id]) return state;
      return {
        items: {
          ...state.items,
          [id]: { ...state.items[id], ...updates },
        },
      };
    });
  },

  moveElement: (elementId, targetId, position, targetIndex) => {
    set((state) => {
      const element = state.items[elementId];
      if (!element) return state;

      const newItems = { ...state.items };
      let newOrder = state.order.filter(id => id !== elementId);

      // Remove from current parent
      if (element.parent && newItems[element.parent]?.children) {
        newItems[element.parent] = {
          ...newItems[element.parent],
          children: newItems[element.parent].children!.filter(id => id !== elementId),
        };
      }

      // Update element without parent
      newItems[elementId] = { ...element, parent: undefined };

      // Add to new position
      if (targetId && position === 'inside') {
        // Moving inside another element
        const target = newItems[targetId];
        if (target) {
          const children = target.children ? [...target.children] : [];
          children.push(elementId);
          newItems[targetId] = { ...target, children };
          newItems[elementId] = { ...newItems[elementId], parent: targetId };
        }
      } else if (targetId) {
        // Moving before or after
        const targetOrderIndex = newOrder.indexOf(targetId);
        if (targetOrderIndex !== -1) {
          const insertIndex = position === 'before' ? targetOrderIndex : targetOrderIndex + 1;
          newOrder.splice(insertIndex, 0, elementId);
        }
      } else if (targetIndex !== undefined) {
        // Moving to specific index
        newOrder.splice(targetIndex, 0, elementId);
      } else {
        // Default to end
        newOrder.push(elementId);
      }

      return {
        items: newItems,
        order: newOrder,
      };
    });
  },

  updateDeviceVisibility: (elementId, device, visible) => {
    set((state) => ({
      deviceVisibility: {
        ...state.deviceVisibility,
        [elementId]: {
          ...(state.deviceVisibility[elementId] || { desktop: true, tablet: true, mobile: true }),
          [device]: visible,
        },
      },
    }));
  },

  reorderElements: (order) => {
    set({ order });
  },

  loadElements: (newState) => {
    set(newState);
  },

  // ===========================================================================
  // Style Override Actions
  // ===========================================================================

  setStyleOverride: (elementId, nodeType, property, value) => {
    set((state) => {
      const element = state.items[elementId];
      if (!element) return state;

      const styleOverrides = element.styleOverrides ?? {};
      const nodeOverrides = styleOverrides[nodeType] ?? {};

      return {
        items: {
          ...state.items,
          [elementId]: {
            ...element,
            styleOverrides: {
              ...styleOverrides,
              [nodeType]: {
                ...nodeOverrides,
                [property]: value,
              },
            },
          },
        },
      };
    });
  },

  setStyleOverrides: (elementId, nodeType, overrides) => {
    set((state) => {
      const element = state.items[elementId];
      if (!element) return state;

      const styleOverrides = element.styleOverrides ?? {};
      const nodeOverrides = styleOverrides[nodeType] ?? {};

      return {
        items: {
          ...state.items,
          [elementId]: {
            ...element,
            styleOverrides: {
              ...styleOverrides,
              [nodeType]: {
                ...nodeOverrides,
                ...overrides,
              },
            },
          },
        },
      };
    });
  },

  removeStyleOverride: (elementId, nodeType, property) => {
    set((state) => {
      const element = state.items[elementId];
      if (!element?.styleOverrides?.[nodeType]) return state;

      const nodeOverrides = { ...element.styleOverrides[nodeType] };
      delete nodeOverrides[property];

      const styleOverrides = { ...element.styleOverrides };
      if (Object.keys(nodeOverrides).length === 0) {
        delete styleOverrides[nodeType];
      } else {
        styleOverrides[nodeType] = nodeOverrides;
      }

      return {
        items: {
          ...state.items,
          [elementId]: {
            ...element,
            styleOverrides: Object.keys(styleOverrides).length > 0 ? styleOverrides : undefined,
          },
        },
      };
    });
  },

  clearNodeStyleOverrides: (elementId, nodeType) => {
    set((state) => {
      const element = state.items[elementId];
      if (!element?.styleOverrides?.[nodeType]) return state;

      const styleOverrides = { ...element.styleOverrides };
      delete styleOverrides[nodeType];

      return {
        items: {
          ...state.items,
          [elementId]: {
            ...element,
            styleOverrides: Object.keys(styleOverrides).length > 0 ? styleOverrides : undefined,
          },
        },
      };
    });
  },

  clearAllStyleOverrides: (elementId) => {
    set((state) => {
      const element = state.items[elementId];
      if (!element?.styleOverrides) return state;

      return {
        items: {
          ...state.items,
          [elementId]: {
            ...element,
            styleOverrides: undefined,
          },
        },
      };
    });
  },

  copyStyleOverrides: (sourceElementId, targetElementId) => {
    set((state) => {
      const source = state.items[sourceElementId];
      const target = state.items[targetElementId];
      if (!source || !target) return state;

      return {
        items: {
          ...state.items,
          [targetElementId]: {
            ...target,
            styleOverrides: source.styleOverrides
              ? JSON.parse(JSON.stringify(source.styleOverrides))
              : undefined,
          },
        },
      };
    });
  },
}));
