import { z } from 'zod';

/**
 * Schema-First Form Builder - Toolbar Types
 *
 * Defines the schema for form builder toolbar items.
 * Each item is registered via registerToolbarItem() and rendered by TopBar.
 */

// =============================================================================
// Toolbar Item Types
// =============================================================================

/**
 * Types of toolbar items.
 */
export const ToolbarItemTypeSchema = z.enum([
  'button',   // Simple click action
  'toggle',   // On/off state toggle
  'dropdown', // Dropdown menu with options
  'custom',   // Custom React component
]);

export type ToolbarItemType = z.infer<typeof ToolbarItemTypeSchema>;

/**
 * Toolbar groups for organizing items.
 */
export const ToolbarGroupSchema = z.enum([
  'left',     // Left section (form selector, title, device)
  'history',  // Undo/Redo group
  'canvas',   // Canvas controls (grid, zoom)
  'panels',   // Panel toggles (elements, history, share, export, analytics)
  'primary',  // Primary actions (preview, save, publish)
]);

export type ToolbarGroup = z.infer<typeof ToolbarGroupSchema>;

/**
 * Button variants for styling.
 */
export const ToolbarVariantSchema = z.enum([
  'ghost',      // Transparent background, subtle hover
  'secondary',  // Secondary action styling
  'save',       // Save button styling (primary color)
  'publish',    // Publish button styling (green)
]);

export type ToolbarVariant = z.infer<typeof ToolbarVariantSchema>;

// =============================================================================
// Dropdown Option Schema
// =============================================================================

/**
 * Option for dropdown toolbar items.
 */
export const ToolbarDropdownOptionSchema = z.object({
  value: z.string(),
  label: z.string(),
  icon: z.string().optional(),
});

export type ToolbarDropdownOption = z.infer<typeof ToolbarDropdownOptionSchema>;

// =============================================================================
// Toolbar Item Schema
// =============================================================================

/**
 * Schema for a toolbar item.
 */
export const ToolbarItemSchemaSchema = z.object({
  /** Unique identifier (lowercase alphanumeric with dashes) */
  id: z.string().regex(/^[a-z][a-z0-9-]*$/, 'ID must be lowercase alphanumeric with dashes'),

  /** Item type */
  type: ToolbarItemTypeSchema,

  /** Group this item belongs to */
  group: ToolbarGroupSchema,

  /** Lucide icon name */
  icon: z.string().optional(),

  /** Display label (shown next to icon) */
  label: z.string().optional(),

  /** Tooltip text */
  tooltip: z.string(),

  /** Button variant for styling */
  variant: ToolbarVariantSchema.default('ghost'),

  /** Sort order within group (lower = earlier) */
  position: z.number(),

  /** Icon when toggle is active (for toggle type) */
  activeIcon: z.string().optional(),

  /** Dropdown options (for dropdown type) */
  options: z.array(ToolbarDropdownOptionSchema).optional(),

  /** Component name for custom rendering (for custom type) */
  component: z.string().optional(),

  /** Whether to show the label */
  showLabel: z.boolean().default(true),

  /** Whether to hide on mobile devices */
  hiddenOnMobile: z.boolean().default(false),

  /** Keyboard shortcut hint */
  shortcut: z.string().optional(),
});

export type ToolbarItemSchema = z.infer<typeof ToolbarItemSchemaSchema>;

// =============================================================================
// Registry Types
// =============================================================================

/**
 * Options for filtering toolbar items.
 */
export interface ToolbarFilterOptions {
  /** Filter by group */
  group?: ToolbarGroup;
  /** Filter by type */
  type?: ToolbarItemType;
  /** Include items hidden on mobile */
  includeMobileHidden?: boolean;
}
