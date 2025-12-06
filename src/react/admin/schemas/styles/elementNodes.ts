import { NodeType } from './types';

/**
 * Element to Node Mapping
 *
 * Maps form element types to the node types they contain.
 * Used to determine which nodes to show in the style editor
 * and which styles to apply when rendering.
 */

// =============================================================================
// Element Node Mapping
// =============================================================================

/**
 * Complete mapping of element types to their contained nodes.
 * Order matters - nodes are listed in visual order (top to bottom).
 */
export const ELEMENT_NODE_MAPPING: Record<string, NodeType[]> = {
  // ===========================================================================
  // Basic Input Elements
  // ===========================================================================
  text: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  email: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  phone: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  url: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  password: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  number: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  textarea: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],

  // ===========================================================================
  // Choice Elements
  // ===========================================================================
  select: ['fieldContainer', 'label', 'description', 'input', 'optionLabel', 'error', 'required'],
  multiselect: ['fieldContainer', 'label', 'description', 'input', 'optionLabel', 'error', 'required'],
  checkbox: ['fieldContainer', 'label', 'description', 'optionLabel', 'error', 'required'],
  radio: ['fieldContainer', 'label', 'description', 'optionLabel', 'error', 'required'],
  'checkbox-cards': ['fieldContainer', 'label', 'description', 'cardContainer', 'optionLabel', 'error', 'required'],
  'radio-cards': ['fieldContainer', 'label', 'description', 'cardContainer', 'optionLabel', 'error', 'required'],
  toggle: ['fieldContainer', 'label', 'description', 'error'],

  // ===========================================================================
  // Advanced Elements
  // ===========================================================================
  date: ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],
  datetime: ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],
  time: ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],
  rating: ['fieldContainer', 'label', 'description', 'error', 'required'],
  slider: ['fieldContainer', 'label', 'description', 'error', 'required'],
  signature: ['fieldContainer', 'label', 'description', 'error', 'required'],
  location: ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],

  // ===========================================================================
  // Upload Elements
  // ===========================================================================
  file: ['fieldContainer', 'label', 'description', 'error', 'required'],
  image: ['fieldContainer', 'label', 'description', 'error', 'required'],

  // ===========================================================================
  // Layout Elements
  // ===========================================================================
  divider: ['divider'],
  spacer: ['fieldContainer'],
  heading: ['heading'],
  paragraph: ['paragraph'],
  'html-block': ['fieldContainer'],

  // ===========================================================================
  // Container Elements
  // ===========================================================================
  columns: ['fieldContainer'],
  section: ['fieldContainer', 'heading'],
  tabs: ['fieldContainer', 'label'],
  accordion: ['fieldContainer', 'heading'],
  repeater: ['fieldContainer', 'label', 'button'],
  'page-break': ['fieldContainer'],
  'step-wizard': ['fieldContainer', 'heading', 'button'],

  // ===========================================================================
  // Action Elements
  // ===========================================================================
  button: ['button'],
  submit: ['button'],

  // ===========================================================================
  // Special Elements
  // ===========================================================================
  hidden: [],  // No visual styling needed
  calculation: ['fieldContainer', 'label', 'description'],
  webhook: ['fieldContainer'],
  payment: ['fieldContainer', 'label', 'description', 'button'],
  embed: ['fieldContainer'],
};

// =============================================================================
// Query Functions
// =============================================================================

/**
 * Get all nodes for an element type.
 * Returns fieldContainer as default if element type is unknown.
 */
export function getElementNodes(elementType: string): NodeType[] {
  return ELEMENT_NODE_MAPPING[elementType] ?? ['fieldContainer'];
}

/**
 * Check if an element type has a specific node.
 */
export function elementHasNode(elementType: string, nodeType: NodeType): boolean {
  return ELEMENT_NODE_MAPPING[elementType]?.includes(nodeType) ?? false;
}

/**
 * Get all element types that have a specific node.
 */
export function getElementsWithNode(nodeType: NodeType): string[] {
  return Object.entries(ELEMENT_NODE_MAPPING)
    .filter(([_, nodes]) => nodes.includes(nodeType))
    .map(([elementType]) => elementType);
}

/**
 * Get the primary styleable node for an element (usually input or the main content node).
 */
export function getPrimaryNode(elementType: string): NodeType {
  const nodes = getElementNodes(elementType);

  // Priority order for primary node
  const priorityOrder: NodeType[] = ['input', 'button', 'heading', 'paragraph', 'divider', 'cardContainer', 'fieldContainer'];

  for (const priority of priorityOrder) {
    if (nodes.includes(priority)) {
      return priority;
    }
  }

  return nodes[0] ?? 'fieldContainer';
}

/**
 * Get count of styleable nodes for an element type.
 */
export function getNodeCount(elementType: string): number {
  return ELEMENT_NODE_MAPPING[elementType]?.length ?? 0;
}
