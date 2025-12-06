---
name: 02-store-integration
status: not-started
---

# Subtask 2: Store Integration

## Goal

Integrate the style system with the Form Builder's Zustand store. Add `styleOverrides` field to elements and create hooks for style resolution.

## Success Criteria

- [ ] `styleOverrides` field added to `FormElement` type
- [ ] Store actions: `setStyleOverride`, `removeStyleOverride`, `clearStyleOverrides`
- [ ] `useResolvedStyle` hook created (merges global + overrides)
- [ ] `useElementStyle` hook created (gets all node styles for an element)
- [ ] `useGlobalStyles` hook for subscribing to registry changes
- [ ] Style changes trigger proper re-renders

## Files to Modify

```
/src/react/admin/apps/form-builder-v2/
├── types/index.ts           # Add styleOverrides to FormElement
├── store/useElementsStore.ts # Add style override actions
```

## Files to Create

```
/src/react/admin/apps/form-builder-v2/hooks/
├── useResolvedStyle.ts      # Resolve style for a single node
├── useElementStyle.ts       # Get all node styles for an element
├── useGlobalStyles.ts       # Subscribe to global style changes
```

## Implementation Details

### 1. types/index.ts - Add styleOverrides

Add to the existing `FormElement` interface:

```typescript
import { NodeType, StyleProperties } from '@/schemas/styles';

export interface FormElement {
  id: string;
  type: string;
  properties: Record<string, any>;
  children?: string[];
  parent?: string;
  // NEW: Style overrides per node
  styleOverrides?: Partial<Record<NodeType, Partial<StyleProperties>>>;
}
```

### 2. store/useElementsStore.ts - Add Style Actions

Add these actions to the existing store:

```typescript
import { NodeType, StyleProperties } from '@/schemas/styles';

interface ElementsActions {
  // ... existing actions ...

  // Style override actions
  setStyleOverride: (
    elementId: string,
    nodeType: NodeType,
    property: keyof StyleProperties,
    value: StyleProperties[keyof StyleProperties]
  ) => void;

  setStyleOverrides: (
    elementId: string,
    nodeType: NodeType,
    overrides: Partial<StyleProperties>
  ) => void;

  removeStyleOverride: (
    elementId: string,
    nodeType: NodeType,
    property: keyof StyleProperties
  ) => void;

  clearNodeStyleOverrides: (
    elementId: string,
    nodeType: NodeType
  ) => void;

  clearAllStyleOverrides: (elementId: string) => void;

  copyStyleOverrides: (
    sourceElementId: string,
    targetElementId: string
  ) => void;
}

// In the store implementation:

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

    // Clean up empty objects
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
```

### 3. hooks/useGlobalStyles.ts

```typescript
import { useSyncExternalStore, useCallback } from 'react';
import { styleRegistry, NodeType, StyleProperties } from '@/schemas/styles';

/**
 * Hook to subscribe to global style changes.
 * Re-renders component when global styles change.
 */
export function useGlobalStyles() {
  const subscribe = useCallback(
    (callback: () => void) => styleRegistry.subscribe(callback),
    []
  );

  const getSnapshot = useCallback(
    () => styleRegistry.getAllGlobalStyles(),
    []
  );

  return useSyncExternalStore(subscribe, getSnapshot, getSnapshot);
}

/**
 * Hook to get global style for a specific node type.
 */
export function useGlobalNodeStyle(nodeType: NodeType): Partial<StyleProperties> {
  const subscribe = useCallback(
    (callback: () => void) => styleRegistry.subscribe(callback),
    []
  );

  const getSnapshot = useCallback(
    () => styleRegistry.getGlobalStyle(nodeType),
    [nodeType]
  );

  return useSyncExternalStore(subscribe, getSnapshot, getSnapshot);
}

/**
 * Hook to get and set a specific global property.
 */
export function useGlobalStyleProperty<K extends keyof StyleProperties>(
  nodeType: NodeType,
  property: K
): [StyleProperties[K] | undefined, (value: StyleProperties[K]) => void] {
  const subscribe = useCallback(
    (callback: () => void) => styleRegistry.subscribe(callback),
    []
  );

  const getSnapshot = useCallback(
    () => styleRegistry.getGlobalProperty(nodeType, property),
    [nodeType, property]
  );

  const value = useSyncExternalStore(subscribe, getSnapshot, getSnapshot);

  const setValue = useCallback(
    (newValue: StyleProperties[K]) => {
      styleRegistry.setGlobalProperty(nodeType, property, newValue);
    },
    [nodeType, property]
  );

  return [value, setValue];
}
```

### 4. hooks/useResolvedStyle.ts

```typescript
import { useMemo } from 'react';
import { useElementsStore } from '../store/useElementsStore';
import { useGlobalNodeStyle } from './useGlobalStyles';
import { NodeType, StyleProperties, styleRegistry } from '@/schemas/styles';

/**
 * Resolve the final style for a node within an element.
 * Merges global styles with element-specific overrides.
 */
export function useResolvedStyle(
  elementId: string,
  nodeType: NodeType
): Partial<StyleProperties> {
  // Get element from store
  const element = useElementsStore((state) => state.items[elementId]);

  // Subscribe to global style changes
  const globalStyle = useGlobalNodeStyle(nodeType);

  // Get element-specific overrides
  const overrides = element?.styleOverrides?.[nodeType];

  // Merge global with overrides
  return useMemo(() => {
    if (!overrides) {
      return globalStyle;
    }
    return { ...globalStyle, ...overrides };
  }, [globalStyle, overrides]);
}

/**
 * Check if a specific property is overridden (unlinked from global).
 */
export function useIsPropertyOverridden(
  elementId: string,
  nodeType: NodeType,
  property: keyof StyleProperties
): boolean {
  const element = useElementsStore((state) => state.items[elementId]);
  return element?.styleOverrides?.[nodeType]?.[property] !== undefined;
}

/**
 * Get the override value for a property, or undefined if linked to global.
 */
export function useStyleOverride<K extends keyof StyleProperties>(
  elementId: string,
  nodeType: NodeType,
  property: K
): StyleProperties[K] | undefined {
  const element = useElementsStore((state) => state.items[elementId]);
  return element?.styleOverrides?.[nodeType]?.[property];
}
```

### 5. hooks/useElementStyle.ts

```typescript
import { useMemo } from 'react';
import { useElementsStore } from '../store/useElementsStore';
import { useGlobalStyles } from './useGlobalStyles';
import {
  NodeType,
  StyleProperties,
  getElementNodes,
  styleRegistry,
} from '@/schemas/styles';

export interface ElementNodeStyles {
  nodeType: NodeType;
  globalStyle: Partial<StyleProperties>;
  overrides: Partial<StyleProperties> | undefined;
  resolvedStyle: Partial<StyleProperties>;
  hasOverrides: boolean;
}

/**
 * Get all node styles for an element.
 * Returns an array of node styles with both global and override info.
 */
export function useElementStyle(elementId: string): ElementNodeStyles[] {
  const element = useElementsStore((state) => state.items[elementId]);
  const globalStyles = useGlobalStyles();

  return useMemo(() => {
    if (!element) return [];

    // Get nodes for this element type
    const nodes = getElementNodes(element.type);

    return nodes.map((nodeType) => {
      const globalStyle = globalStyles[nodeType] ?? {};
      const overrides = element.styleOverrides?.[nodeType];
      const resolvedStyle = overrides
        ? { ...globalStyle, ...overrides }
        : globalStyle;

      return {
        nodeType,
        globalStyle,
        overrides,
        resolvedStyle,
        hasOverrides: !!overrides && Object.keys(overrides).length > 0,
      };
    });
  }, [element, globalStyles]);
}

/**
 * Get summary of which nodes have overrides.
 */
export function useElementStyleSummary(elementId: string): {
  totalNodes: number;
  nodesWithOverrides: number;
  overriddenNodeTypes: NodeType[];
} {
  const nodeStyles = useElementStyle(elementId);

  return useMemo(() => {
    const overriddenNodes = nodeStyles.filter((n) => n.hasOverrides);
    return {
      totalNodes: nodeStyles.length,
      nodesWithOverrides: overriddenNodes.length,
      overriddenNodeTypes: overriddenNodes.map((n) => n.nodeType),
    };
  }, [nodeStyles]);
}
```

## Testing

1. **Type safety:**
   ```typescript
   const element: FormElement = {
     id: 'test',
     type: 'text',
     properties: {},
     styleOverrides: {
       label: { fontSize: 16, color: '#ff0000' },
       input: { backgroundColor: '#f0f0f0' },
     },
   };
   ```

2. **Store actions:**
   ```typescript
   const { setStyleOverride, removeStyleOverride } = useElementsStore();

   // Set an override
   setStyleOverride('element-1', 'label', 'fontSize', 18);

   // Remove an override
   removeStyleOverride('element-1', 'label', 'fontSize');
   ```

3. **Hooks:**
   ```typescript
   // In a component
   const labelStyle = useResolvedStyle('element-1', 'label');
   const isOverridden = useIsPropertyOverridden('element-1', 'label', 'fontSize');
   const allStyles = useElementStyle('element-1');
   ```

## Dependencies

- Subtask 01 (Core Schema & Registry) must be completed first

## Notes

- Element `styleOverrides` is optional - elements without overrides use globals
- Empty override objects are cleaned up automatically
- Use shallow comparison in selectors for performance
- Global style subscription uses `useSyncExternalStore` for React 18+ compatibility
