---
name: 03-unify-element-tray
parent: m-implement-unified-element-tray
branch: feature/h-implement-triggers-actions-extensibility
status: not-started
created: 2025-12-05
---

# Unified Element Tray: Global/Individual Style System

## Overview

This document outlines the schema-first implementation plan for a unified styling system that supports:
- **Global Styles**: Theme-level defaults for reusable "node types" (label, description, heading, etc.)
- **Individual Overrides**: Per-element style customizations that can "unlink" from global defaults
- **MCP/LLM Integration**: Machine-readable schemas enabling AI-driven form building

---

## Architecture Principles

### 1. Schema-First Design
All style definitions must be expressed in TypeScript schemas that serve as:
- Source of truth for UI rendering
- API contract for REST endpoints
- Machine-readable format for MCP server / LLM agents

### 2. Node Type Abstraction
Elements contain multiple "styleable nodes" (sub-components):
```
TextInput Element
├── labelNode      → Uses GlobalStyleRegistry['label']
├── descriptionNode → Uses GlobalStyleRegistry['description']
├── inputNode      → Uses GlobalStyleRegistry['input']
├── errorNode      → Uses GlobalStyleRegistry['error']
└── containerNode  → Uses GlobalStyleRegistry['fieldContainer']
```

### 3. Inheritance Chain
```
Global Theme Defaults
       ↓
Global Node Styles (per node type)
       ↓
Individual Element Overrides (per property)
       ↓
Final Rendered Style
```

---

## Schema Definitions

### File: `/src/react/admin/schemas/styles/types.ts`

```typescript
import { z } from 'zod';

// ============================================
// STYLE PROPERTY DEFINITIONS
// ============================================

/**
 * All possible style properties that can be applied to nodes.
 * This is the canonical list - MCP server uses this for validation.
 */
export const StylePropertySchema = z.object({
  // Typography
  fontSize: z.number().min(8).max(72).optional(),
  fontFamily: z.string().optional(),
  fontWeight: z.union([
    z.literal(100), z.literal(200), z.literal(300), z.literal(400),
    z.literal(500), z.literal(600), z.literal(700), z.literal(800), z.literal(900),
    z.literal('normal'), z.literal('bold')
  ]).optional(),
  fontStyle: z.enum(['normal', 'italic', 'oblique']).optional(),
  lineHeight: z.union([z.number(), z.string()]).optional(),
  letterSpacing: z.number().optional(),
  textAlign: z.enum(['left', 'center', 'right', 'justify']).optional(),
  textDecoration: z.enum(['none', 'underline', 'line-through', 'overline']).optional(),
  textTransform: z.enum(['none', 'uppercase', 'lowercase', 'capitalize']).optional(),

  // Colors
  color: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),
  backgroundColor: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),

  // Spacing (CSS shorthand or object)
  margin: z.union([
    z.number(),
    z.object({
      top: z.number().optional(),
      right: z.number().optional(),
      bottom: z.number().optional(),
      left: z.number().optional(),
    })
  ]).optional(),
  padding: z.union([
    z.number(),
    z.object({
      top: z.number().optional(),
      right: z.number().optional(),
      bottom: z.number().optional(),
      left: z.number().optional(),
    })
  ]).optional(),

  // Border
  borderWidth: z.number().min(0).max(20).optional(),
  borderStyle: z.enum(['none', 'solid', 'dashed', 'dotted', 'double']).optional(),
  borderColor: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),
  borderRadius: z.number().min(0).max(100).optional(),

  // Layout
  width: z.union([z.number(), z.string()]).optional(),
  minWidth: z.union([z.number(), z.string()]).optional(),
  maxWidth: z.union([z.number(), z.string()]).optional(),

  // Effects
  boxShadow: z.string().optional(),
  opacity: z.number().min(0).max(1).optional(),
});

export type StyleProperties = z.infer<typeof StylePropertySchema>;

// ============================================
// NODE TYPE DEFINITIONS
// ============================================

/**
 * All styleable node types in the system.
 * Each element is composed of one or more of these nodes.
 */
export const NodeTypeSchema = z.enum([
  // Form Field Nodes
  'label',           // Field label text
  'description',     // Field description/help text
  'input',           // Text input, textarea, select base
  'placeholder',     // Placeholder text styling
  'error',           // Validation error message
  'required',        // Required indicator (asterisk)

  // Container Nodes
  'fieldContainer',  // Wrapper around each form field
  'formContainer',   // Outer form wrapper
  'sectionContainer', // Section/group container
  'columnContainer', // Column wrapper

  // Content Nodes
  'heading',         // Heading text (h1-h6)
  'paragraph',       // Paragraph text
  'link',            // Link styling
  'button',          // Button styling
  'divider',         // Divider/separator

  // Choice Nodes
  'optionLabel',     // Radio/checkbox label
  'optionDescription', // Radio/checkbox description
  'cardContainer',   // Card-style choice container

  // Email-Specific Nodes (for unified builder)
  'emailWrapper',    // Outer email background
  'emailBody',       // Email content area
]);

export type NodeType = z.infer<typeof NodeTypeSchema>;

// ============================================
// STYLE CAPABILITY DEFINITIONS
// ============================================

/**
 * Defines which style properties are available for each node type.
 * This drives the UI - only show controls for available properties.
 */
export const NodeStyleCapabilitiesSchema = z.record(
  NodeTypeSchema,
  z.object({
    typography: z.object({
      fontSize: z.boolean().default(false),
      fontFamily: z.boolean().default(false),
      fontWeight: z.boolean().default(false),
      fontStyle: z.boolean().default(false),
      lineHeight: z.boolean().default(false),
      letterSpacing: z.boolean().default(false),
      textAlign: z.boolean().default(false),
      textDecoration: z.boolean().default(false),
      textTransform: z.boolean().default(false),
      color: z.boolean().default(false),
    }).optional(),
    spacing: z.object({
      margin: z.boolean().default(false),
      padding: z.boolean().default(false),
    }).optional(),
    border: z.object({
      borderWidth: z.boolean().default(false),
      borderStyle: z.boolean().default(false),
      borderColor: z.boolean().default(false),
      borderRadius: z.boolean().default(false),
    }).optional(),
    background: z.object({
      backgroundColor: z.boolean().default(false),
    }).optional(),
    layout: z.object({
      width: z.boolean().default(false),
      minWidth: z.boolean().default(false),
      maxWidth: z.boolean().default(false),
    }).optional(),
    effects: z.object({
      boxShadow: z.boolean().default(false),
      opacity: z.boolean().default(false),
    }).optional(),
  })
);

export type NodeStyleCapabilities = z.infer<typeof NodeStyleCapabilitiesSchema>;

// ============================================
// GLOBAL STYLE REGISTRY SCHEMA
// ============================================

/**
 * The global style registry - defines default styles for all node types.
 * Stored at form/theme level.
 */
export const GlobalStyleRegistrySchema = z.object({
  version: z.literal(1),
  name: z.string().optional(), // Theme name
  nodeStyles: z.record(NodeTypeSchema, StylePropertySchema),
});

export type GlobalStyleRegistry = z.infer<typeof GlobalStyleRegistrySchema>;

// ============================================
// ELEMENT STYLE OVERRIDE SCHEMA
// ============================================

/**
 * Per-element style overrides.
 * Each styleable node within an element can override specific properties.
 */
export const StyleOverrideSchema = z.object({
  useGlobal: z.boolean().default(true),
  // Only present when useGlobal = false
  value: StylePropertySchema.optional(),
});

export const ElementStyleOverridesSchema = z.record(
  z.string(), // Node name within element (e.g., 'label', 'description')
  z.record(
    z.string(), // Property name (e.g., 'fontSize', 'color')
    StyleOverrideSchema
  )
);

export type ElementStyleOverrides = z.infer<typeof ElementStyleOverridesSchema>;

// ============================================
// MCP ACTION SCHEMAS
// ============================================

/**
 * Actions that can be performed on the style system.
 * These are exposed to MCP server for LLM agents.
 */
export const StyleActionSchema = z.discriminatedUnion('action', [
  // Get global style for a node type
  z.object({
    action: z.literal('getGlobalStyle'),
    nodeType: NodeTypeSchema,
  }),

  // Set global style property
  z.object({
    action: z.literal('setGlobalStyle'),
    nodeType: NodeTypeSchema,
    property: z.string(),
    value: z.any(),
  }),

  // Get element's effective style (resolved)
  z.object({
    action: z.literal('getElementStyle'),
    elementId: z.string(),
    nodeName: z.string(),
  }),

  // Set individual override on element
  z.object({
    action: z.literal('setElementStyleOverride'),
    elementId: z.string(),
    nodeName: z.string(),
    property: z.string(),
    value: z.any(),
  }),

  // Remove individual override (revert to global)
  z.object({
    action: z.literal('removeElementStyleOverride'),
    elementId: z.string(),
    nodeName: z.string(),
    property: z.string(),
  }),

  // Copy style from one element to another
  z.object({
    action: z.literal('copyElementStyle'),
    sourceElementId: z.string(),
    targetElementId: z.string(),
    nodeName: z.string(),
  }),

  // Apply element's individual style as new global
  z.object({
    action: z.literal('promoteToGlobal'),
    elementId: z.string(),
    nodeName: z.string(),
  }),

  // Reset element to use all global styles
  z.object({
    action: z.literal('resetToGlobal'),
    elementId: z.string(),
    nodeName: z.string().optional(), // If omitted, reset all nodes
  }),

  // Get available style capabilities for a node type
  z.object({
    action: z.literal('getNodeCapabilities'),
    nodeType: NodeTypeSchema,
  }),

  // List all node types
  z.object({
    action: z.literal('listNodeTypes'),
  }),
]);

export type StyleAction = z.infer<typeof StyleActionSchema>;
```

---

### File: `/src/react/admin/schemas/styles/capabilities.ts`

```typescript
import { NodeStyleCapabilities, NodeType } from './types';

/**
 * Default capabilities for each node type.
 * Determines which style properties can be edited.
 */
export const NODE_STYLE_CAPABILITIES: Record<NodeType, NodeStyleCapabilities[NodeType]> = {
  // Label node - full typography, limited spacing
  label: {
    typography: {
      fontSize: true,
      fontFamily: true,
      fontWeight: true,
      fontStyle: false,
      lineHeight: true,
      letterSpacing: false,
      textAlign: false,
      textDecoration: false,
      textTransform: true,
      color: true,
    },
    spacing: {
      margin: true,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  // Description node - similar to label but lighter weight
  description: {
    typography: {
      fontSize: true,
      fontFamily: true,
      fontWeight: true,
      fontStyle: true,
      lineHeight: true,
      letterSpacing: false,
      textAlign: false,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {
      margin: true,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  // Input node - full styling
  input: {
    typography: {
      fontSize: true,
      fontFamily: true,
      fontWeight: false,
      fontStyle: false,
      lineHeight: false,
      letterSpacing: false,
      textAlign: true,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {
      margin: false,
      padding: true,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: true,
    },
    background: {
      backgroundColor: true,
    },
    layout: {
      width: true,
      minWidth: true,
      maxWidth: true,
    },
    effects: {
      boxShadow: true,
      opacity: false,
    },
  },

  // ... (other node types defined similarly)

  placeholder: {
    typography: {
      fontSize: false, // Inherits from input
      fontFamily: false,
      fontWeight: false,
      fontStyle: true,
      lineHeight: false,
      letterSpacing: false,
      textAlign: false,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {},
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  error: {
    typography: {
      fontSize: true,
      fontFamily: false,
      fontWeight: true,
      fontStyle: false,
      lineHeight: false,
      letterSpacing: false,
      textAlign: false,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {
      margin: true,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  required: {
    typography: {
      fontSize: true,
      fontFamily: false,
      fontWeight: false,
      fontStyle: false,
      lineHeight: false,
      letterSpacing: false,
      textAlign: false,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {
      margin: true,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  fieldContainer: {
    typography: {},
    spacing: {
      margin: true,
      padding: true,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: true,
    },
    background: {
      backgroundColor: true,
    },
    layout: {
      width: true,
      minWidth: false,
      maxWidth: true,
    },
    effects: {
      boxShadow: true,
      opacity: false,
    },
  },

  formContainer: {
    typography: {},
    spacing: {
      margin: true,
      padding: true,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: true,
    },
    background: {
      backgroundColor: true,
    },
    layout: {
      width: true,
      minWidth: false,
      maxWidth: true,
    },
    effects: {
      boxShadow: true,
      opacity: false,
    },
  },

  sectionContainer: {
    typography: {},
    spacing: {
      margin: true,
      padding: true,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: true,
    },
    background: {
      backgroundColor: true,
    },
    layout: {},
    effects: {
      boxShadow: true,
      opacity: false,
    },
  },

  columnContainer: {
    typography: {},
    spacing: {
      margin: false,
      padding: true,
    },
    border: {},
    background: {
      backgroundColor: true,
    },
    layout: {},
    effects: {},
  },

  heading: {
    typography: {
      fontSize: true,
      fontFamily: true,
      fontWeight: true,
      fontStyle: false,
      lineHeight: true,
      letterSpacing: true,
      textAlign: true,
      textDecoration: false,
      textTransform: true,
      color: true,
    },
    spacing: {
      margin: true,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  paragraph: {
    typography: {
      fontSize: true,
      fontFamily: true,
      fontWeight: false,
      fontStyle: false,
      lineHeight: true,
      letterSpacing: false,
      textAlign: true,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {
      margin: true,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  link: {
    typography: {
      fontSize: false,
      fontFamily: false,
      fontWeight: true,
      fontStyle: false,
      lineHeight: false,
      letterSpacing: false,
      textAlign: false,
      textDecoration: true,
      textTransform: false,
      color: true,
    },
    spacing: {},
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  button: {
    typography: {
      fontSize: true,
      fontFamily: true,
      fontWeight: true,
      fontStyle: false,
      lineHeight: false,
      letterSpacing: true,
      textAlign: true,
      textDecoration: false,
      textTransform: true,
      color: true,
    },
    spacing: {
      margin: true,
      padding: true,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: true,
    },
    background: {
      backgroundColor: true,
    },
    layout: {
      width: true,
      minWidth: true,
      maxWidth: false,
    },
    effects: {
      boxShadow: true,
      opacity: false,
    },
  },

  divider: {
    typography: {},
    spacing: {
      margin: true,
      padding: false,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: false,
    },
    background: {},
    layout: {
      width: true,
      minWidth: false,
      maxWidth: false,
    },
    effects: {},
  },

  optionLabel: {
    typography: {
      fontSize: true,
      fontFamily: false,
      fontWeight: true,
      fontStyle: false,
      lineHeight: false,
      letterSpacing: false,
      textAlign: false,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {
      margin: false,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  optionDescription: {
    typography: {
      fontSize: true,
      fontFamily: false,
      fontWeight: false,
      fontStyle: true,
      lineHeight: false,
      letterSpacing: false,
      textAlign: false,
      textDecoration: false,
      textTransform: false,
      color: true,
    },
    spacing: {
      margin: true,
      padding: false,
    },
    border: {},
    background: {},
    layout: {},
    effects: {},
  },

  cardContainer: {
    typography: {},
    spacing: {
      margin: true,
      padding: true,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: true,
    },
    background: {
      backgroundColor: true,
    },
    layout: {},
    effects: {
      boxShadow: true,
      opacity: false,
    },
  },

  emailWrapper: {
    typography: {},
    spacing: {
      margin: false,
      padding: true,
    },
    border: {},
    background: {
      backgroundColor: true,
    },
    layout: {},
    effects: {},
  },

  emailBody: {
    typography: {},
    spacing: {
      margin: true,
      padding: true,
    },
    border: {
      borderWidth: true,
      borderStyle: true,
      borderColor: true,
      borderRadius: true,
    },
    background: {
      backgroundColor: true,
    },
    layout: {
      width: true,
      minWidth: false,
      maxWidth: true,
    },
    effects: {
      boxShadow: true,
      opacity: false,
    },
  },
};

/**
 * Helper function to check if a node type has a specific capability.
 */
export function hasCapability(
  nodeType: NodeType,
  category: keyof NodeStyleCapabilities[NodeType],
  property: string
): boolean {
  const caps = NODE_STYLE_CAPABILITIES[nodeType];
  if (!caps) return false;
  const categoryObj = caps[category];
  if (!categoryObj) return false;
  return (categoryObj as Record<string, boolean>)[property] === true;
}

/**
 * Get all enabled properties for a node type.
 */
export function getEnabledProperties(nodeType: NodeType): string[] {
  const caps = NODE_STYLE_CAPABILITIES[nodeType];
  if (!caps) return [];

  const properties: string[] = [];
  for (const [category, props] of Object.entries(caps)) {
    if (props) {
      for (const [prop, enabled] of Object.entries(props)) {
        if (enabled) properties.push(prop);
      }
    }
  }
  return properties;
}
```

---

### File: `/src/react/admin/schemas/styles/defaults.ts`

```typescript
import { GlobalStyleRegistry, NodeType, StyleProperties } from './types';

/**
 * Default global styles - the base theme.
 * Can be overridden per-form or as a saved theme.
 */
export const DEFAULT_GLOBAL_STYLES: GlobalStyleRegistry = {
  version: 1,
  name: 'Default Theme',
  nodeStyles: {
    label: {
      fontSize: 14,
      fontFamily: 'Inter, system-ui, sans-serif',
      fontWeight: 500,
      color: '#374151',
      lineHeight: 1.4,
      margin: { top: 0, right: 0, bottom: 6, left: 0 },
    },

    description: {
      fontSize: 12,
      fontFamily: 'Inter, system-ui, sans-serif',
      fontWeight: 400,
      color: '#6B7280',
      lineHeight: 1.4,
      margin: { top: 4, right: 0, bottom: 0, left: 0 },
    },

    input: {
      fontSize: 14,
      fontFamily: 'Inter, system-ui, sans-serif',
      color: '#111827',
      backgroundColor: '#ffffff',
      padding: { top: 10, right: 12, bottom: 10, left: 12 },
      borderWidth: 1,
      borderStyle: 'solid',
      borderColor: '#D1D5DB',
      borderRadius: 6,
    },

    placeholder: {
      color: '#9CA3AF',
      fontStyle: 'normal',
    },

    error: {
      fontSize: 12,
      fontWeight: 500,
      color: '#DC2626',
      margin: { top: 4, right: 0, bottom: 0, left: 0 },
    },

    required: {
      fontSize: 14,
      color: '#DC2626',
      margin: { top: 0, right: 0, bottom: 0, left: 4 },
    },

    fieldContainer: {
      margin: { top: 0, right: 0, bottom: 16, left: 0 },
      padding: { top: 0, right: 0, bottom: 0, left: 0 },
    },

    formContainer: {
      backgroundColor: '#ffffff',
      padding: { top: 24, right: 24, bottom: 24, left: 24 },
      borderRadius: 8,
    },

    sectionContainer: {
      margin: { top: 0, right: 0, bottom: 24, left: 0 },
      padding: { top: 16, right: 16, bottom: 16, left: 16 },
      borderWidth: 1,
      borderStyle: 'solid',
      borderColor: '#E5E7EB',
      borderRadius: 8,
      backgroundColor: '#F9FAFB',
    },

    columnContainer: {
      padding: { top: 0, right: 8, bottom: 0, left: 8 },
    },

    heading: {
      fontSize: 24,
      fontFamily: 'Inter, system-ui, sans-serif',
      fontWeight: 600,
      color: '#111827',
      lineHeight: 1.3,
      margin: { top: 0, right: 0, bottom: 16, left: 0 },
    },

    paragraph: {
      fontSize: 14,
      fontFamily: 'Inter, system-ui, sans-serif',
      color: '#4B5563',
      lineHeight: 1.6,
      margin: { top: 0, right: 0, bottom: 12, left: 0 },
    },

    link: {
      color: '#2563EB',
      fontWeight: 500,
      textDecoration: 'underline',
    },

    button: {
      fontSize: 14,
      fontFamily: 'Inter, system-ui, sans-serif',
      fontWeight: 500,
      color: '#ffffff',
      backgroundColor: '#2563EB',
      padding: { top: 10, right: 20, bottom: 10, left: 20 },
      borderWidth: 0,
      borderRadius: 6,
      textAlign: 'center',
    },

    divider: {
      borderWidth: 1,
      borderStyle: 'solid',
      borderColor: '#E5E7EB',
      margin: { top: 16, right: 0, bottom: 16, left: 0 },
      width: '100%',
    },

    optionLabel: {
      fontSize: 14,
      fontWeight: 400,
      color: '#374151',
    },

    optionDescription: {
      fontSize: 12,
      color: '#6B7280',
      margin: { top: 2, right: 0, bottom: 0, left: 0 },
    },

    cardContainer: {
      backgroundColor: '#ffffff',
      padding: { top: 16, right: 16, bottom: 16, left: 16 },
      borderWidth: 1,
      borderStyle: 'solid',
      borderColor: '#E5E7EB',
      borderRadius: 8,
      margin: { top: 0, right: 8, bottom: 8, left: 0 },
    },

    emailWrapper: {
      backgroundColor: '#f5f5f5',
      padding: { top: 40, right: 0, bottom: 40, left: 0 },
    },

    emailBody: {
      backgroundColor: '#ffffff',
      width: 600,
      maxWidth: 600,
      padding: { top: 40, right: 30, bottom: 40, left: 30 },
      margin: { top: 0, right: 'auto' as any, bottom: 0, left: 'auto' as any },
      borderRadius: 0,
    },
  },
};

/**
 * Get default style for a specific node type.
 */
export function getDefaultNodeStyle(nodeType: NodeType): StyleProperties {
  return DEFAULT_GLOBAL_STYLES.nodeStyles[nodeType] || {};
}
```

---

### File: `/src/react/admin/schemas/styles/registry.ts`

```typescript
import { z } from 'zod';
import {
  GlobalStyleRegistry,
  GlobalStyleRegistrySchema,
  StyleProperties,
  NodeType,
  ElementStyleOverrides,
  StyleAction,
  StyleActionSchema,
} from './types';
import { DEFAULT_GLOBAL_STYLES } from './defaults';
import { NODE_STYLE_CAPABILITIES, hasCapability, getEnabledProperties } from './capabilities';

/**
 * Style Registry - manages global styles and resolves element styles.
 *
 * This is the main interface for the style system, used by:
 * - UI components for rendering
 * - Property panels for editing
 * - MCP server for LLM operations
 */
class StyleRegistry {
  private globalStyles: GlobalStyleRegistry;

  constructor() {
    this.globalStyles = { ...DEFAULT_GLOBAL_STYLES };
  }

  // ============================================
  // GLOBAL STYLE OPERATIONS
  // ============================================

  /**
   * Load a complete global style registry (e.g., from saved form data).
   */
  loadGlobalStyles(styles: GlobalStyleRegistry): void {
    const validated = GlobalStyleRegistrySchema.parse(styles);
    this.globalStyles = validated;
  }

  /**
   * Get the entire global style registry.
   */
  getGlobalStyles(): GlobalStyleRegistry {
    return this.globalStyles;
  }

  /**
   * Get global style for a specific node type.
   */
  getGlobalNodeStyle(nodeType: NodeType): StyleProperties {
    return this.globalStyles.nodeStyles[nodeType] || {};
  }

  /**
   * Update a single property in global styles.
   */
  setGlobalStyleProperty(
    nodeType: NodeType,
    property: string,
    value: any
  ): void {
    if (!this.globalStyles.nodeStyles[nodeType]) {
      this.globalStyles.nodeStyles[nodeType] = {};
    }
    (this.globalStyles.nodeStyles[nodeType] as any)[property] = value;
  }

  /**
   * Reset a node type to default styles.
   */
  resetNodeToDefault(nodeType: NodeType): void {
    this.globalStyles.nodeStyles[nodeType] =
      { ...DEFAULT_GLOBAL_STYLES.nodeStyles[nodeType] };
  }

  // ============================================
  // ELEMENT STYLE RESOLUTION
  // ============================================

  /**
   * Resolve the effective style for an element's node.
   * Merges global styles with individual overrides.
   */
  resolveElementStyle(
    nodeType: NodeType,
    overrides?: ElementStyleOverrides[string]
  ): StyleProperties {
    const globalStyle = this.getGlobalNodeStyle(nodeType);

    if (!overrides) {
      return { ...globalStyle };
    }

    const resolved = { ...globalStyle };

    for (const [property, override] of Object.entries(overrides)) {
      if (!override.useGlobal && override.value !== undefined) {
        (resolved as any)[property] = override.value;
      }
    }

    return resolved;
  }

  /**
   * Check if an element has any individual overrides for a node.
   */
  hasOverrides(overrides?: ElementStyleOverrides[string]): boolean {
    if (!overrides) return false;
    return Object.values(overrides).some(o => !o.useGlobal);
  }

  /**
   * Get list of overridden properties for an element's node.
   */
  getOverriddenProperties(overrides?: ElementStyleOverrides[string]): string[] {
    if (!overrides) return [];
    return Object.entries(overrides)
      .filter(([_, o]) => !o.useGlobal)
      .map(([prop, _]) => prop);
  }

  // ============================================
  // CAPABILITY QUERIES
  // ============================================

  /**
   * Check if a node type supports a specific style property.
   */
  nodeHasCapability(
    nodeType: NodeType,
    category: string,
    property: string
  ): boolean {
    return hasCapability(nodeType, category as any, property);
  }

  /**
   * Get all style properties enabled for a node type.
   */
  getNodeCapabilities(nodeType: NodeType): string[] {
    return getEnabledProperties(nodeType);
  }

  /**
   * Get the full capabilities object for a node type.
   */
  getNodeCapabilitiesObject(nodeType: NodeType) {
    return NODE_STYLE_CAPABILITIES[nodeType];
  }

  // ============================================
  // MCP ACTION HANDLER
  // ============================================

  /**
   * Execute a style action (for MCP server integration).
   * Returns the result of the action.
   */
  executeAction(action: StyleAction): any {
    // Validate action schema
    const validated = StyleActionSchema.parse(action);

    switch (validated.action) {
      case 'getGlobalStyle':
        return this.getGlobalNodeStyle(validated.nodeType);

      case 'setGlobalStyle':
        this.setGlobalStyleProperty(
          validated.nodeType,
          validated.property,
          validated.value
        );
        return { success: true };

      case 'getNodeCapabilities':
        return this.getNodeCapabilitiesObject(validated.nodeType);

      case 'listNodeTypes':
        return Object.keys(this.globalStyles.nodeStyles);

      // Element-specific actions would need element store access
      // These are handled by the element store, not the registry
      case 'getElementStyle':
      case 'setElementStyleOverride':
      case 'removeElementStyleOverride':
      case 'copyElementStyle':
      case 'promoteToGlobal':
      case 'resetToGlobal':
        throw new Error(`Action ${validated.action} must be handled by element store`);

      default:
        throw new Error(`Unknown action: ${(validated as any).action}`);
    }
  }
}

// Singleton instance
export const styleRegistry = new StyleRegistry();

// Export class for testing
export { StyleRegistry };
```

---

## Element Schema Integration

### Modified Element Schema Structure

Update `/src/react/admin/schemas/core/types.ts` to include styleable nodes:

```typescript
/**
 * Define which nodes an element type contains.
 * This maps element types to their styleable sub-components.
 */
export const ElementNodeMappingSchema = z.record(
  z.string(), // Element type (e.g., 'text_input')
  z.array(z.string()) // Node names (e.g., ['label', 'description', 'input', 'error'])
);

// Example mapping
export const ELEMENT_NODE_MAPPING: Record<string, string[]> = {
  // Input elements
  'text_input': ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  'textarea': ['fieldContainer', 'label', 'description', 'input', 'placeholder', 'error', 'required'],
  'select': ['fieldContainer', 'label', 'description', 'input', 'error', 'required'],
  'checkbox': ['fieldContainer', 'label', 'description', 'optionLabel', 'error'],
  'radio': ['fieldContainer', 'label', 'description', 'optionLabel', 'optionDescription', 'error'],

  // Choice cards
  'checkbox_cards': ['fieldContainer', 'label', 'description', 'cardContainer', 'optionLabel', 'optionDescription', 'error'],
  'radio_cards': ['fieldContainer', 'label', 'description', 'cardContainer', 'optionLabel', 'optionDescription', 'error'],

  // Content elements
  'heading': ['heading'],
  'paragraph': ['paragraph'],
  'divider': ['divider'],
  'html': [], // No styleable nodes (raw HTML)

  // Containers
  'section': ['sectionContainer', 'heading'],
  'columns': ['columnContainer'],

  // Buttons
  'button': ['button'],
  'submit_button': ['button'],

  // Email-specific
  'email_wrapper': ['emailWrapper'],
  'email_body': ['emailBody'],
};

/**
 * Get styleable nodes for an element type.
 */
export function getElementNodes(elementType: string): string[] {
  return ELEMENT_NODE_MAPPING[elementType] || [];
}
```

---

## UI Components

### File: `/src/react/admin/components/ui/style-editor/NodeStylePopover.tsx`

```tsx
import React, { useState } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '../popover';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../tabs';
import { Button } from '../button';
import { Settings2, Link, Unlink, RotateCcw } from 'lucide-react';
import { cn } from '../../../lib/utils';
import { styleRegistry } from '../../../schemas/styles/registry';
import { NodeType, StyleProperties, ElementStyleOverrides } from '../../../schemas/styles/types';

interface NodeStylePopoverProps {
  nodeType: NodeType;
  nodeName: string; // Display name (e.g., "Label", "Description")
  elementId: string;
  overrides?: ElementStyleOverrides[string];
  onOverrideChange: (property: string, value: any, useGlobal: boolean) => void;
  onResetToGlobal: () => void;
  onPromoteToGlobal: () => void;
}

export function NodeStylePopover({
  nodeType,
  nodeName,
  elementId,
  overrides,
  onOverrideChange,
  onResetToGlobal,
  onPromoteToGlobal,
}: NodeStylePopoverProps) {
  const [activeTab, setActiveTab] = useState<'global' | 'individual'>('global');

  const capabilities = styleRegistry.getNodeCapabilitiesObject(nodeType);
  const globalStyles = styleRegistry.getGlobalNodeStyle(nodeType);
  const resolvedStyles = styleRegistry.resolveElementStyle(nodeType, overrides);
  const overriddenProps = styleRegistry.getOverriddenProperties(overrides);
  const hasOverrides = overriddenProps.length > 0;

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          variant="ghost"
          size="icon"
          className={cn(
            "h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity",
            hasOverrides && "text-primary opacity-100"
          )}
        >
          <Settings2 size={14} />
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-80" align="start">
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h4 className="font-medium">{nodeName} Styles</h4>
            {hasOverrides && (
              <span className="text-xs text-muted-foreground">
                {overriddenProps.length} override{overriddenProps.length > 1 ? 's' : ''}
              </span>
            )}
          </div>

          <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as any)}>
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="global">Global</TabsTrigger>
              <TabsTrigger value="individual">
                Individual
                {hasOverrides && (
                  <span className="ml-1 w-2 h-2 rounded-full bg-primary" />
                )}
              </TabsTrigger>
            </TabsList>

            <TabsContent value="global" className="space-y-3 mt-4">
              <p className="text-xs text-muted-foreground">
                These styles apply to all {nodeName.toLowerCase()}s in the form.
              </p>

              {/* Render style controls based on capabilities */}
              <StylePropertyControls
                capabilities={capabilities}
                values={globalStyles}
                onChange={(prop, value) => {
                  styleRegistry.setGlobalStyleProperty(nodeType, prop, value);
                  // Trigger re-render
                }}
                showLinkedIndicator={false}
              />

              <Button
                variant="outline"
                size="sm"
                className="w-full"
                onClick={onResetToGlobal}
              >
                <RotateCcw size={14} className="mr-2" />
                Reset to Defaults
              </Button>
            </TabsContent>

            <TabsContent value="individual" className="space-y-3 mt-4">
              {hasOverrides ? (
                <>
                  <p className="text-xs text-muted-foreground">
                    Custom styles for this element only.
                  </p>

                  {/* Show only overridden properties with unlink buttons */}
                  <StylePropertyControls
                    capabilities={capabilities}
                    values={resolvedStyles}
                    overrides={overrides}
                    onChange={(prop, value) => onOverrideChange(prop, value, false)}
                    onToggleLink={(prop) => {
                      const isCurrentlyLinked = !overrides?.[prop] || overrides[prop].useGlobal;
                      if (isCurrentlyLinked) {
                        // Unlink - copy current global value as override
                        onOverrideChange(prop, globalStyles[prop as keyof StyleProperties], false);
                      } else {
                        // Link - remove override
                        onOverrideChange(prop, undefined, true);
                      }
                    }}
                    showLinkedIndicator={true}
                  />

                  <div className="flex gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      className="flex-1"
                      onClick={onResetToGlobal}
                    >
                      Reset All to Global
                    </Button>
                    <Button
                      variant="default"
                      size="sm"
                      className="flex-1"
                      onClick={onPromoteToGlobal}
                    >
                      Set as Global
                    </Button>
                  </div>
                </>
              ) : (
                <div className="text-center py-4">
                  <p className="text-sm text-muted-foreground mb-3">
                    No individual overrides.
                  </p>
                  <p className="text-xs text-muted-foreground">
                    Click the <Link size={12} className="inline mx-1" /> icon next to
                    a property in the Global tab to override it for this element.
                  </p>
                </div>
              )}
            </TabsContent>
          </Tabs>
        </div>
      </PopoverContent>
    </Popover>
  );
}
```

---

## MCP Server Integration

### File: `/src/mcp/handlers/styleActions.ts`

```typescript
import { styleRegistry } from '../../react/admin/schemas/styles/registry';
import { StyleAction, StyleActionSchema } from '../../react/admin/schemas/styles/types';

/**
 * MCP handler for style system actions.
 *
 * This enables LLM agents to:
 * - Query available node types and their capabilities
 * - Get/set global styles
 * - Get/set individual element style overrides
 * - Copy styles between elements
 * - Promote individual styles to global
 */
export async function handleStyleAction(action: unknown): Promise<unknown> {
  // Validate action against schema
  const validated = StyleActionSchema.parse(action);

  // Execute and return result
  return styleRegistry.executeAction(validated);
}

/**
 * Get schema information for MCP tool registration.
 */
export function getStyleActionSchema() {
  return {
    name: 'form_style',
    description: 'Manage form styling - global themes and individual element overrides',
    inputSchema: StyleActionSchema,
    examples: [
      {
        description: 'Get all available node types',
        input: { action: 'listNodeTypes' }
      },
      {
        description: 'Get global style for labels',
        input: { action: 'getGlobalStyle', nodeType: 'label' }
      },
      {
        description: 'Change global label font size',
        input: { action: 'setGlobalStyle', nodeType: 'label', property: 'fontSize', value: 16 }
      },
      {
        description: 'Get capabilities for input node',
        input: { action: 'getNodeCapabilities', nodeType: 'input' }
      },
    ]
  };
}
```

---

## Implementation Phases

### Phase 1: Core Schema & Registry (Foundation)
1. Create `/src/react/admin/schemas/styles/types.ts`
2. Create `/src/react/admin/schemas/styles/capabilities.ts`
3. Create `/src/react/admin/schemas/styles/defaults.ts`
4. Create `/src/react/admin/schemas/styles/registry.ts`
5. Create `/src/react/admin/schemas/styles/index.ts` (barrel export)
6. Add element-to-node mapping in core types

### Phase 2: Store Integration
1. Add `styleOverrides` to element schema in store
2. Create style resolution hooks (`useResolvedStyle`, `useElementStyle`)
3. Implement style update actions in element store

### Phase 3: UI Components
1. Create `NodeStylePopover` component
2. Create `StylePropertyControls` component
3. Create `LinkedPropertyInput` component (with link/unlink button)
4. Port `SpacingCompass` from Email builder
5. Integrate with FloatingPanel property editor

### Phase 4: Context Menu & Shortcuts
1. Add "Copy Style" / "Paste Style" to element context menu
2. Add "Set as Global" option
3. Add "Reset to Global" option
4. Keyboard shortcuts (Ctrl+Alt+C for copy style, etc.)

### Phase 5: MCP Integration
1. Register style action handler in MCP server
2. Add tool schema for LLM agents
3. Test with sample prompts

### Phase 6: Migration & Polish
1. Migrate existing form styling to new system
2. Create theme presets
3. Add theme import/export
4. Documentation

---

## Testing Checklist

- [ ] Schema validation (Zod) works for all types
- [ ] Global style changes propagate to all elements
- [ ] Individual overrides work independently
- [ ] Unlinking a property copies global value correctly
- [ ] Re-linking removes override and uses global
- [ ] "Set as Global" updates registry
- [ ] Style resolution is performant (memoization)
- [ ] MCP actions work correctly
- [ ] UI reflects linked/unlinked state
- [ ] Keyboard navigation works in popover
- [ ] Undo/redo works for style changes
