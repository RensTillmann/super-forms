/**
 * Schema-First Style System
 *
 * A unified styling system for form elements with:
 * - Zod schemas as source of truth
 * - Global theme defaults per node type
 * - Per-element style overrides with link/unlink
 * - MCP-ready for LLM integration
 *
 * @example
 * ```typescript
 * import {
 *   styleRegistry,
 *   getElementNodes,
 *   getNodeCapabilities,
 * } from '@/schemas/styles';
 *
 * // Get global style for a node type
 * const labelStyle = styleRegistry.getGlobalStyle('label');
 *
 * // Set a global property
 * styleRegistry.setGlobalProperty('label', 'fontSize', 16);
 *
 * // Get nodes for an element type
 * const nodes = getElementNodes('text'); // ['fieldContainer', 'label', ...]
 *
 * // Check capabilities
 * const caps = getNodeCapabilities('input'); // { fontSize: true, ... }
 * ```
 */

// =============================================================================
// Types
// =============================================================================

export {
  // Schemas
  NodeTypeSchema,
  SpacingSchema,
  StylePropertySchema,
  NodeStyleCapabilitiesSchema,
  ElementStyleOverridesSchema,
  // Types
  type NodeType,
  type Spacing,
  type StyleProperties,
  type NodeStyleCapabilities,
  type ElementStyleOverrides,
  type StylePropertyKey,
  // Constants
  STYLE_PROPERTY_KEYS,
  ZERO_SPACING,
  // Utilities
  uniformSpacing,
} from './types';

// =============================================================================
// Capabilities
// =============================================================================

export {
  NODE_STYLE_CAPABILITIES,
  getNodeCapabilities,
  hasCapability,
  getSupportedProperties,
  getNodesWithCapability,
} from './capabilities';

// =============================================================================
// Defaults
// =============================================================================

export {
  DEFAULT_GLOBAL_STYLES,
  getDefaultStyle,
  getDefaultProperty,
  getNodesWithDefaultProperty,
} from './defaults';

// =============================================================================
// Element Nodes
// =============================================================================

export {
  ELEMENT_NODE_MAPPING,
  getElementNodes,
  elementHasNode,
  getElementsWithNode,
  getPrimaryNode,
  getNodeCount,
} from './elementNodes';

// =============================================================================
// Registry
// =============================================================================

export {
  styleRegistry,
  StyleRegistry,
} from './registry';
