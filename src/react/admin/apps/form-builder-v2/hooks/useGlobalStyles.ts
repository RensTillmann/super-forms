import { useSyncExternalStore, useCallback } from 'react';
import {
  styleRegistry,
  NodeType,
  StyleProperties,
} from '../../../schemas/styles';

/**
 * Hook to subscribe to global style changes.
 * Re-renders component when global styles change.
 */
export function useGlobalStyles(): Record<NodeType, Partial<StyleProperties>> {
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

/**
 * Hook to get the style registry version (for memoization).
 */
export function useStyleRegistryVersion(): number {
  const subscribe = useCallback(
    (callback: () => void) => styleRegistry.subscribe(callback),
    []
  );

  const getSnapshot = useCallback(() => styleRegistry.getVersion(), []);

  return useSyncExternalStore(subscribe, getSnapshot, getSnapshot);
}
