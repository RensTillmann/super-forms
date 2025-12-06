import { useMemo } from 'react';
import { useElementsStore } from '../store/useElementsStore';
import { useGlobalStyles } from './useGlobalStyles';
import {
  NodeType,
  StyleProperties,
  getElementNodes,
} from '../../../schemas/styles';

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

/**
 * Check if element has any style overrides.
 */
export function useHasStyleOverrides(elementId: string): boolean {
  const element = useElementsStore((state) => state.items[elementId]);
  return !!element?.styleOverrides && Object.keys(element.styleOverrides).length > 0;
}

/**
 * Get count of overridden properties across all nodes.
 */
export function useOverrideCount(elementId: string): number {
  const element = useElementsStore((state) => state.items[elementId]);

  return useMemo(() => {
    if (!element?.styleOverrides) return 0;

    let count = 0;
    Object.values(element.styleOverrides).forEach((nodeOverrides) => {
      if (nodeOverrides) {
        count += Object.keys(nodeOverrides).length;
      }
    });
    return count;
  }, [element?.styleOverrides]);
}
