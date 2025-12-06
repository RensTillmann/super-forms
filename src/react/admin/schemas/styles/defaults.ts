import { NodeType, StyleProperties, ZERO_SPACING } from './types';

/**
 * Default Global Styles
 *
 * These are the baseline values that elements inherit unless overridden.
 * Designed to provide a clean, professional look out of the box.
 */

// =============================================================================
// Default Global Styles
// =============================================================================

export const DEFAULT_GLOBAL_STYLES: Record<NodeType, Partial<StyleProperties>> = {
  label: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontWeight: '500',
    lineHeight: 1.4,
    color: '#1f2937',  // gray-800
    margin: { top: 0, right: 0, bottom: 4, left: 0 },
  },

  description: {
    fontSize: 13,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    lineHeight: 1.4,
    color: '#6b7280',  // gray-500
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },

  input: {
    fontSize: 14,
    fontFamily: 'inherit',
    textAlign: 'left',
    color: '#1f2937',  // gray-800
    padding: { top: 8, right: 12, bottom: 8, left: 12 },
    border: { top: 1, right: 1, bottom: 1, left: 1 },
    borderStyle: 'solid',
    borderColor: '#d1d5db',  // gray-300
    borderRadius: 6,
    backgroundColor: '#ffffff',
    width: '100%',
    minHeight: 40,
  },

  placeholder: {
    fontStyle: 'normal',
    color: '#9ca3af',  // gray-400
  },

  error: {
    fontSize: 13,
    fontWeight: '500',
    color: '#dc2626',  // red-600
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },

  required: {
    fontSize: 14,
    fontWeight: '400',
    color: '#dc2626',  // red-600
  },

  fieldContainer: {
    margin: { top: 0, right: 0, bottom: 16, left: 0 },
    padding: ZERO_SPACING,
    border: ZERO_SPACING,
    borderRadius: 0,
    backgroundColor: '#ffffff00',  // transparent
    width: '100%',
  },

  heading: {
    fontSize: 24,
    fontFamily: 'inherit',
    fontWeight: '600',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.3,
    letterSpacing: 0,
    color: '#111827',  // gray-900
    margin: { top: 0, right: 0, bottom: 8, left: 0 },
  },

  paragraph: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.6,
    color: '#4b5563',  // gray-600
    margin: { top: 0, right: 0, bottom: 12, left: 0 },
  },

  button: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontWeight: '500',
    textAlign: 'center',
    letterSpacing: 0,
    color: '#ffffff',
    margin: { top: 8, right: 0, bottom: 0, left: 0 },
    padding: { top: 10, right: 20, bottom: 10, left: 20 },
    border: ZERO_SPACING,
    borderRadius: 6,
    backgroundColor: '#2563eb',  // blue-600
    minHeight: 40,
  },

  divider: {
    color: '#e5e7eb',  // gray-200
    margin: { top: 16, right: 0, bottom: 16, left: 0 },
    border: { top: 1, right: 0, bottom: 0, left: 0 },
    width: '100%',
  },

  optionLabel: {
    fontSize: 14,
    fontFamily: 'inherit',
    lineHeight: 1.4,
    color: '#374151',  // gray-700
  },

  cardContainer: {
    margin: { top: 0, right: 8, bottom: 8, left: 0 },
    padding: { top: 16, right: 16, bottom: 16, left: 16 },
    border: { top: 2, right: 2, bottom: 2, left: 2 },
    borderRadius: 8,
    backgroundColor: '#ffffff',
    width: 'auto',
    minHeight: 80,
  },
};

// =============================================================================
// Query Functions
// =============================================================================

/**
 * Get default style for a node type.
 */
export function getDefaultStyle(nodeType: NodeType): Partial<StyleProperties> {
  return DEFAULT_GLOBAL_STYLES[nodeType] ?? {};
}

/**
 * Get a specific default property value.
 */
export function getDefaultProperty<K extends keyof StyleProperties>(
  nodeType: NodeType,
  property: K
): StyleProperties[K] | undefined {
  return DEFAULT_GLOBAL_STYLES[nodeType]?.[property];
}

/**
 * Get all node types that have a specific property set in defaults.
 */
export function getNodesWithDefaultProperty(property: keyof StyleProperties): NodeType[] {
  return (Object.keys(DEFAULT_GLOBAL_STYLES) as NodeType[])
    .filter(nodeType => DEFAULT_GLOBAL_STYLES[nodeType][property] !== undefined);
}
