import { z } from 'zod';

/**
 * Schema-First Form Builder - Tab Types
 *
 * Defines the schema for form builder tabs.
 * Each tab is registered via registerTab() and rendered by TabBar.
 */

// =============================================================================
// Tab Schema
// =============================================================================

/**
 * Schema for a form builder tab.
 */
export const TabSchemaSchema = z.object({
  /** Unique identifier (lowercase alphanumeric with dashes) */
  id: z.string().regex(/^[a-z][a-z0-9-]*$/, 'ID must be lowercase alphanumeric with dashes'),

  /** Display label */
  label: z.string().min(1),

  /** Lucide icon name */
  icon: z.string(),

  /** Sort order (lower = earlier) */
  position: z.number(),

  /** Whether to lazy load the tab content */
  lazyLoad: z.boolean().default(false),

  /** Component import path for lazy loading */
  componentPath: z.string().optional(),

  /** Required permission/capability to show this tab */
  requiredPermission: z.string().optional(),

  /** Required plan level (e.g., 'pro', 'enterprise') */
  requiredPlan: z.string().optional(),

  /** Whether this tab is hidden by default */
  hidden: z.boolean().default(false),

  /** Description for tooltips */
  description: z.string().optional(),
});

export type TabSchema = z.infer<typeof TabSchemaSchema>;

// =============================================================================
// Tab Registry Types
// =============================================================================

/**
 * Options for filtering tabs.
 */
export interface TabFilterOptions {
  /** Include hidden tabs */
  includeHidden?: boolean;
  /** Filter by required permission */
  permission?: string;
  /** Filter by required plan */
  plan?: string;
}
