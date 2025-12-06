---
name: 01-core-schema-registry
status: not-started
---

# Subtask 1: Core Schema & Registry

## Goal

Create the foundational schema-first style system with Zod schemas, a style registry singleton, and node type definitions. This is the foundation that all other subtasks build upon.

## Success Criteria

- [ ] Zod schemas defined: `StylePropertySchema`, `NodeTypeSchema`, `NodeStyleCapabilitiesSchema`
- [ ] `StyleRegistry` singleton class with get/set methods
- [ ] `NODE_STYLE_CAPABILITIES` mapping defined
- [ ] `DEFAULT_GLOBAL_STYLES` with sensible defaults
- [ ] Element-to-node mapping (which nodes each element type contains)
- [ ] Barrel export from `index.ts`
- [ ] TypeScript types derived from Zod schemas

## Files to Create

```
/src/react/admin/schemas/styles/
├── types.ts              # Zod schemas and derived types
├── capabilities.ts       # NODE_STYLE_CAPABILITIES mapping
├── defaults.ts           # DEFAULT_GLOBAL_STYLES
├── registry.ts           # StyleRegistry class
├── elementNodes.ts       # Element-to-node mapping
└── index.ts              # Barrel export
```

## Implementation Details

### 1. types.ts - Zod Schemas

```typescript
import { z } from 'zod';

// =============================================================================
// Node Types
// =============================================================================

export const NodeTypeSchema = z.enum([
  'label',           // Field label text
  'description',     // Help text below fields
  'input',           // Text input, textarea, select
  'placeholder',     // Placeholder text styling
  'error',           // Validation error message
  'required',        // Required indicator (*)
  'fieldContainer',  // Wrapper around entire field
  'heading',         // h1-h6 text
  'paragraph',       // Body text
  'button',          // Button styling
  'divider',         // Separator line
  'optionLabel',     // Radio/checkbox option label
  'cardContainer',   // Card wrapper for card-style choices
]);

export type NodeType = z.infer<typeof NodeTypeSchema>;

// =============================================================================
// Spacing Schema (for margin/padding/border)
// =============================================================================

export const SpacingSchema = z.object({
  top: z.number().min(0).max(200).default(0),
  right: z.number().min(0).max(200).default(0),
  bottom: z.number().min(0).max(200).default(0),
  left: z.number().min(0).max(200).default(0),
});

export type Spacing = z.infer<typeof SpacingSchema>;

// =============================================================================
// Style Properties Schema
// =============================================================================

export const StylePropertySchema = z.object({
  // Typography
  fontSize: z.number().min(8).max(72).optional(),
  fontFamily: z.string().optional(),
  fontWeight: z.enum(['400', '500', '600', '700']).optional(),
  fontStyle: z.enum(['normal', 'italic']).optional(),
  textAlign: z.enum(['left', 'center', 'right']).optional(),
  textDecoration: z.enum(['none', 'underline', 'line-through']).optional(),
  lineHeight: z.number().min(0.5).max(3).optional(),
  letterSpacing: z.number().min(-2).max(10).optional(),
  color: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),

  // Spacing
  margin: SpacingSchema.optional(),
  padding: SpacingSchema.optional(),

  // Border
  border: SpacingSchema.optional(),  // border widths
  borderStyle: z.enum(['none', 'solid', 'dashed', 'dotted']).optional(),
  borderColor: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),
  borderRadius: z.number().min(0).max(50).optional(),

  // Background
  backgroundColor: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),

  // Layout
  width: z.string().optional(),  // e.g., '100%', '200px'
  minHeight: z.number().min(0).optional(),
});

export type StyleProperties = z.infer<typeof StylePropertySchema>;

// =============================================================================
// Node Style Capabilities Schema
// =============================================================================

export const NodeStyleCapabilitiesSchema = z.object({
  // Typography capabilities
  fontSize: z.boolean().default(false),
  fontFamily: z.boolean().default(false),
  fontWeight: z.boolean().default(false),
  fontStyle: z.boolean().default(false),
  textAlign: z.boolean().default(false),
  textDecoration: z.boolean().default(false),
  lineHeight: z.boolean().default(false),
  letterSpacing: z.boolean().default(false),
  color: z.boolean().default(false),

  // Spacing capabilities
  margin: z.boolean().default(false),
  padding: z.boolean().default(false),

  // Border capabilities
  border: z.boolean().default(false),
  borderRadius: z.boolean().default(false),

  // Background capabilities
  backgroundColor: z.boolean().default(false),

  // Layout capabilities
  width: z.boolean().default(false),
  minHeight: z.boolean().default(false),
});

export type NodeStyleCapabilities = z.infer<typeof NodeStyleCapabilitiesSchema>;

// =============================================================================
// Element Style Overrides Schema
// =============================================================================

export const ElementStyleOverridesSchema = z.record(
  NodeTypeSchema,
  StylePropertySchema.partial()
).optional();

export type ElementStyleOverrides = z.infer<typeof ElementStyleOverridesSchema>;
```

### 2. capabilities.ts - Node Capabilities

```typescript
import { NodeType, NodeStyleCapabilities } from './types';

/**
 * Defines which style properties each node type supports.
 * Used to determine which controls to render in the UI.
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
```

### 3. defaults.ts - Default Global Styles

```typescript
import { NodeType, StyleProperties, Spacing } from './types';

/**
 * Default spacing value (all sides 0)
 */
const ZERO_SPACING: Spacing = { top: 0, right: 0, bottom: 0, left: 0 };

/**
 * Default global styles for each node type.
 * These are the baseline values that elements inherit unless overridden.
 */
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
    backgroundColor: 'transparent',
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

/**
 * Get default style for a node type.
 */
export function getDefaultStyle(nodeType: NodeType): Partial<StyleProperties> {
  return DEFAULT_GLOBAL_STYLES[nodeType] ?? {};
}
```

### 4. elementNodes.ts - Element to Node Mapping

```typescript
import { NodeType } from './types';

/**
 * Maps element types to the node types they contain.
 * Used to determine which nodes to show in the style editor.
 */
export const ELEMENT_NODE_MAPPING: Record<string, NodeType[]> = {
  // Basic Input Elements
  text: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  email: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  phone: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  url: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  password: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  number: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  textarea: ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],

  // Choice Elements
  select: ['fieldContainer', 'label', 'description', 'input', 'optionLabel', 'error', 'required'],
  multiselect: ['fieldContainer', 'label', 'description', 'input', 'optionLabel', 'error', 'required'],
  checkbox: ['fieldContainer', 'label', 'description', 'optionLabel', 'error', 'required'],
  radio: ['fieldContainer', 'label', 'description', 'optionLabel', 'error', 'required'],
  'checkbox-cards': ['fieldContainer', 'label', 'description', 'cardContainer', 'optionLabel', 'error', 'required'],
  'radio-cards': ['fieldContainer', 'label', 'description', 'cardContainer', 'optionLabel', 'error', 'required'],
  toggle: ['fieldContainer', 'label', 'description', 'error'],

  // Advanced Elements
  date: ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],
  datetime: ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],
  time: ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],
  rating: ['fieldContainer', 'label', 'description', 'error', 'required'],
  slider: ['fieldContainer', 'label', 'description', 'error', 'required'],
  signature: ['fieldContainer', 'label', 'description', 'error', 'required'],

  // Upload Elements
  file: ['fieldContainer', 'label', 'description', 'error', 'required'],
  image: ['fieldContainer', 'label', 'description', 'error', 'required'],

  // Layout Elements
  divider: ['divider'],
  spacer: ['fieldContainer'],
  heading: ['heading'],
  paragraph: ['paragraph'],
  'html-block': ['fieldContainer'],

  // Container Elements
  columns: ['fieldContainer'],
  section: ['fieldContainer', 'heading'],
  tabs: ['fieldContainer', 'label'],
  accordion: ['fieldContainer', 'heading'],
  repeater: ['fieldContainer', 'label', 'button'],

  // Action Elements
  button: ['button'],
  submit: ['button'],

  // Hidden (minimal styling)
  hidden: [],
};

/**
 * Get all nodes for an element type.
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
```

### 5. registry.ts - Style Registry Singleton

```typescript
import { NodeType, StyleProperties, StylePropertySchema } from './types';
import { DEFAULT_GLOBAL_STYLES, getDefaultStyle } from './defaults';
import { getNodeCapabilities, hasCapability } from './capabilities';

/**
 * StyleRegistry - Singleton for managing global styles.
 *
 * Follows the same pattern as the element registry:
 * - Singleton instance
 * - Validation with Zod
 * - Query methods for lookups
 */
class StyleRegistry {
  private globalStyles: Map<NodeType, Partial<StyleProperties>>;
  private listeners: Set<() => void>;

  constructor() {
    // Initialize with defaults
    this.globalStyles = new Map();
    this.listeners = new Set();

    // Load default styles
    for (const [nodeType, styles] of Object.entries(DEFAULT_GLOBAL_STYLES)) {
      this.globalStyles.set(nodeType as NodeType, { ...styles });
    }
  }

  // ===========================================================================
  // Global Style Getters
  // ===========================================================================

  /**
   * Get the complete global style for a node type.
   */
  getGlobalStyle(nodeType: NodeType): Partial<StyleProperties> {
    return this.globalStyles.get(nodeType) ?? getDefaultStyle(nodeType);
  }

  /**
   * Get a specific property value from global style.
   */
  getGlobalProperty<K extends keyof StyleProperties>(
    nodeType: NodeType,
    property: K
  ): StyleProperties[K] | undefined {
    return this.globalStyles.get(nodeType)?.[property];
  }

  /**
   * Get all global styles as a plain object.
   */
  getAllGlobalStyles(): Record<NodeType, Partial<StyleProperties>> {
    const result: Record<string, Partial<StyleProperties>> = {};
    this.globalStyles.forEach((styles, nodeType) => {
      result[nodeType] = { ...styles };
    });
    return result as Record<NodeType, Partial<StyleProperties>>;
  }

  // ===========================================================================
  // Global Style Setters
  // ===========================================================================

  /**
   * Set a specific property in global style.
   */
  setGlobalProperty<K extends keyof StyleProperties>(
    nodeType: NodeType,
    property: K,
    value: StyleProperties[K]
  ): void {
    // Check if node type supports this property
    if (!hasCapability(nodeType, property as keyof ReturnType<typeof getNodeCapabilities>)) {
      console.warn(`Node type '${nodeType}' does not support property '${property}'`);
      return;
    }

    const current = this.globalStyles.get(nodeType) ?? {};
    this.globalStyles.set(nodeType, { ...current, [property]: value });
    this.notifyListeners();
  }

  /**
   * Set multiple properties in global style.
   */
  setGlobalStyle(nodeType: NodeType, updates: Partial<StyleProperties>): void {
    const current = this.globalStyles.get(nodeType) ?? {};
    this.globalStyles.set(nodeType, { ...current, ...updates });
    this.notifyListeners();
  }

  /**
   * Reset a node type to default global style.
   */
  resetToDefault(nodeType: NodeType): void {
    this.globalStyles.set(nodeType, { ...getDefaultStyle(nodeType) });
    this.notifyListeners();
  }

  /**
   * Reset all global styles to defaults.
   */
  resetAllToDefaults(): void {
    this.globalStyles.clear();
    for (const [nodeType, styles] of Object.entries(DEFAULT_GLOBAL_STYLES)) {
      this.globalStyles.set(nodeType as NodeType, { ...styles });
    }
    this.notifyListeners();
  }

  // ===========================================================================
  // Style Resolution
  // ===========================================================================

  /**
   * Resolve final style by merging global with overrides.
   * This is the main method for getting computed styles.
   */
  resolveStyle(
    nodeType: NodeType,
    overrides?: Partial<StyleProperties>
  ): Partial<StyleProperties> {
    const global = this.getGlobalStyle(nodeType);

    if (!overrides) {
      return { ...global };
    }

    return { ...global, ...overrides };
  }

  // ===========================================================================
  // Subscription (for React integration)
  // ===========================================================================

  /**
   * Subscribe to style changes.
   * Returns unsubscribe function.
   */
  subscribe(listener: () => void): () => void {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }

  private notifyListeners(): void {
    this.listeners.forEach(listener => listener());
  }

  // ===========================================================================
  // Serialization (for save/load)
  // ===========================================================================

  /**
   * Export global styles as JSON.
   */
  exportStyles(): string {
    return JSON.stringify(this.getAllGlobalStyles(), null, 2);
  }

  /**
   * Import global styles from JSON.
   */
  importStyles(json: string): void {
    try {
      const parsed = JSON.parse(json);
      for (const [nodeType, styles] of Object.entries(parsed)) {
        // Validate with Zod
        const validated = StylePropertySchema.partial().safeParse(styles);
        if (validated.success) {
          this.globalStyles.set(nodeType as NodeType, validated.data);
        }
      }
      this.notifyListeners();
    } catch (error) {
      console.error('Failed to import styles:', error);
    }
  }
}

// Singleton instance
export const styleRegistry = new StyleRegistry();

// Re-export for convenience
export { StyleRegistry };
```

### 6. index.ts - Barrel Export

```typescript
// Types
export * from './types';

// Capabilities
export * from './capabilities';

// Defaults
export * from './defaults';

// Element-to-node mapping
export * from './elementNodes';

// Registry
export { styleRegistry, StyleRegistry } from './registry';
```

## Testing

After implementation, verify:

1. **Schema validation works:**
   ```typescript
   import { StylePropertySchema } from './types';
   StylePropertySchema.parse({ fontSize: 14, color: '#ff0000' }); // should pass
   StylePropertySchema.parse({ fontSize: 'invalid' }); // should throw
   ```

2. **Registry operations work:**
   ```typescript
   import { styleRegistry } from './registry';
   styleRegistry.setGlobalProperty('label', 'fontSize', 16);
   console.log(styleRegistry.getGlobalProperty('label', 'fontSize')); // 16
   ```

3. **Style resolution works:**
   ```typescript
   const resolved = styleRegistry.resolveStyle('input', { backgroundColor: '#f0f0f0' });
   // Should merge global input styles with the override
   ```

## Dependencies

- `zod` (already in project)

## Notes

- Follow existing registry pattern from `/src/react/admin/schemas/core/registry.ts`
- Use singleton pattern for `styleRegistry`
- All styles are validated with Zod before storage
- Subscription system enables React components to re-render on global style changes
