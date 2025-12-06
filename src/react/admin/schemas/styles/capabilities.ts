import { NodeType, NodeStyleCapabilities } from './types';

/**
 * Node Style Capabilities
 *
 * Defines which style properties each node type supports.
 * Used to determine which controls to render in the UI and
 * validate style operations.
 */

// =============================================================================
// Capabilities Map
// =============================================================================

/**
 * Complete mapping of node types to their style capabilities.
 */
export const NODE_STYLE_CAPABILITIES: Record<NodeType, NodeStyleCapabilities> = {
  label: {
    fontSize: true,
    fontFamily: true,
    fontWeight: true,
    fontStyle: false,
    textAlign: false,
    textDecoration: false,
    lineHeight: true,
    letterSpacing: false,
    color: true,
    margin: true,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  description: {
    fontSize: true,
    fontFamily: true,
    fontWeight: false,
    fontStyle: true,
    textAlign: false,
    textDecoration: false,
    lineHeight: true,
    letterSpacing: false,
    color: true,
    margin: true,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  input: {
    fontSize: true,
    fontFamily: true,
    fontWeight: false,
    fontStyle: false,
    textAlign: true,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: false,
    color: true,
    margin: false,
    padding: true,
    border: true,
    borderRadius: true,
    backgroundColor: true,
    width: true,
    minHeight: true,
  },

  placeholder: {
    fontSize: false,  // inherits from input
    fontFamily: false,
    fontWeight: false,
    fontStyle: true,
    textAlign: false,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: false,
    color: true,
    margin: false,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  error: {
    fontSize: true,
    fontFamily: false,
    fontWeight: true,
    fontStyle: false,
    textAlign: false,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: false,
    color: true,
    margin: true,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  required: {
    fontSize: true,
    fontFamily: false,
    fontWeight: true,
    fontStyle: false,
    textAlign: false,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: false,
    color: true,
    margin: false,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  fieldContainer: {
    fontSize: false,
    fontFamily: false,
    fontWeight: false,
    fontStyle: false,
    textAlign: false,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: false,
    color: false,
    margin: true,
    padding: true,
    border: true,
    borderRadius: true,
    backgroundColor: true,
    width: true,
    minHeight: false,
  },

  heading: {
    fontSize: true,
    fontFamily: true,
    fontWeight: true,
    fontStyle: false,
    textAlign: true,
    textDecoration: true,
    lineHeight: true,
    letterSpacing: true,
    color: true,
    margin: true,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  paragraph: {
    fontSize: true,
    fontFamily: true,
    fontWeight: false,
    fontStyle: true,
    textAlign: true,
    textDecoration: true,
    lineHeight: true,
    letterSpacing: false,
    color: true,
    margin: true,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  button: {
    fontSize: true,
    fontFamily: true,
    fontWeight: true,
    fontStyle: false,
    textAlign: true,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: true,
    color: true,
    margin: true,
    padding: true,
    border: true,
    borderRadius: true,
    backgroundColor: true,
    width: true,
    minHeight: true,
  },

  divider: {
    fontSize: false,
    fontFamily: false,
    fontWeight: false,
    fontStyle: false,
    textAlign: false,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: false,
    color: true,  // line color
    margin: true,
    padding: false,
    border: true,  // for thickness
    borderRadius: false,
    backgroundColor: false,
    width: true,
    minHeight: false,
  },

  optionLabel: {
    fontSize: true,
    fontFamily: true,
    fontWeight: false,
    fontStyle: false,
    textAlign: false,
    textDecoration: false,
    lineHeight: true,
    letterSpacing: false,
    color: true,
    margin: false,
    padding: false,
    border: false,
    borderRadius: false,
    backgroundColor: false,
    width: false,
    minHeight: false,
  },

  cardContainer: {
    fontSize: false,
    fontFamily: false,
    fontWeight: false,
    fontStyle: false,
    textAlign: false,
    textDecoration: false,
    lineHeight: false,
    letterSpacing: false,
    color: false,
    margin: true,
    padding: true,
    border: true,
    borderRadius: true,
    backgroundColor: true,
    width: true,
    minHeight: true,
  },
};

// =============================================================================
// Query Functions
// =============================================================================

/**
 * Get capabilities for a node type.
 */
export function getNodeCapabilities(nodeType: NodeType): NodeStyleCapabilities {
  return NODE_STYLE_CAPABILITIES[nodeType];
}

/**
 * Check if a node type has a specific capability.
 */
export function hasCapability(
  nodeType: NodeType,
  property: keyof NodeStyleCapabilities
): boolean {
  return NODE_STYLE_CAPABILITIES[nodeType]?.[property] ?? false;
}

/**
 * Get all properties that a node type supports.
 */
export function getSupportedProperties(nodeType: NodeType): (keyof NodeStyleCapabilities)[] {
  const capabilities = NODE_STYLE_CAPABILITIES[nodeType];
  if (!capabilities) return [];

  return (Object.keys(capabilities) as (keyof NodeStyleCapabilities)[])
    .filter(key => capabilities[key]);
}

/**
 * Get all node types that support a specific property.
 */
export function getNodesWithCapability(property: keyof NodeStyleCapabilities): NodeType[] {
  return (Object.keys(NODE_STYLE_CAPABILITIES) as NodeType[])
    .filter(nodeType => NODE_STYLE_CAPABILITIES[nodeType][property]);
}
