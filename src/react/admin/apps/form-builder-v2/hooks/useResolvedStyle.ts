import { useMemo } from 'react';
import { useElementsStore } from '../store/useElementsStore';
import { useGlobalNodeStyle } from './useGlobalStyles';
import { NodeType, StyleProperties } from '../../../schemas/styles';

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

/**
 * Get both global and resolved values for a property.
 * Useful for UI that shows both.
 */
export function usePropertyValues<K extends keyof StyleProperties>(
  elementId: string,
  nodeType: NodeType,
  property: K
): {
  globalValue: StyleProperties[K] | undefined;
  resolvedValue: StyleProperties[K] | undefined;
  isOverridden: boolean;
} {
  const globalStyle = useGlobalNodeStyle(nodeType);
  const element = useElementsStore((state) => state.items[elementId]);

  const globalValue = globalStyle[property];
  const overrideValue = element?.styleOverrides?.[nodeType]?.[property];
  const isOverridden = overrideValue !== undefined;

  return {
    globalValue,
    resolvedValue: isOverridden ? overrideValue : globalValue,
    isOverridden,
  };
}
