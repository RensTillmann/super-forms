import { z } from 'zod';

/**
 * Schema-First Style System - Type Definitions
 *
 * All style definitions are expressed as Zod schemas that serve as:
 * - Source of truth for UI rendering
 * - API contract for REST endpoints
 * - Machine-readable format for MCP server / LLM agents
 */

// =============================================================================
// Node Types
// =============================================================================

/**
 * Node types represent styleable sub-components within form elements.
 * Each element contains multiple nodes (label, input, error, etc.)
 */
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
// Spacing Schema
// =============================================================================

/**
 * Four-sided spacing values (margin, padding, border widths)
 */
export const SpacingSchema = z.object({
  top: z.number().min(0).max(200).default(0),
  right: z.number().min(0).max(200).default(0),
  bottom: z.number().min(0).max(200).default(0),
  left: z.number().min(0).max(200).default(0),
});

export type Spacing = z.infer<typeof SpacingSchema>;

/**
 * Create a spacing object with all sides set to the same value
 */
export function uniformSpacing(value: number): Spacing {
  return { top: value, right: value, bottom: value, left: value };
}

/**
 * Zero spacing constant
 */
export const ZERO_SPACING: Spacing = { top: 0, right: 0, bottom: 0, left: 0 };

// =============================================================================
// Style Properties Schema
// =============================================================================

/**
 * All possible style properties that can be applied to a node.
 * Not all properties are available for all node types - see capabilities.ts
 */
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
  border: SpacingSchema.optional(),  // border widths per side
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

/**
 * Defines which style properties a node type supports.
 * Used to determine which controls to render in the UI.
 */
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

/**
 * Per-element style overrides, organized by node type.
 * Only overridden properties are stored; missing properties use global defaults.
 */
export const ElementStyleOverridesSchema = z.record(
  NodeTypeSchema,
  StylePropertySchema.partial()
).optional();

export type ElementStyleOverrides = z.infer<typeof ElementStyleOverridesSchema>;

// =============================================================================
// Style Property Keys
// =============================================================================

/**
 * All available style property keys
 */
export const STYLE_PROPERTY_KEYS = [
  'fontSize',
  'fontFamily',
  'fontWeight',
  'fontStyle',
  'textAlign',
  'textDecoration',
  'lineHeight',
  'letterSpacing',
  'color',
  'margin',
  'padding',
  'border',
  'borderStyle',
  'borderColor',
  'borderRadius',
  'backgroundColor',
  'width',
  'minHeight',
] as const;

export type StylePropertyKey = (typeof STYLE_PROPERTY_KEYS)[number];
