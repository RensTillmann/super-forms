/**
 * Template System Types
 *
 * Templates are predefined element configurations that can be toggled on/off
 * and applied to elements. They provide visual presets for input addons like
 * prefix/suffix text, icons, action buttons, tooltips, etc.
 */

/**
 * Template categories for organization in the UI
 */
export type TemplateCategory =
  | 'label'       // Label positioning templates
  | 'description' // Description positioning templates
  | 'placeholder' // Placeholder text templates
  | 'inset'       // Inset input icons (inside the input field)
  | 'tooltip'     // Input tooltip/help icon templates
  | 'prefix'      // Prefix addons (text, icons before input)
  | 'suffix'      // Suffix addons (text, icons after input)
  | 'action'      // Action buttons (copy, clear, visibility toggle)
  | 'feedback';   // User feedback (character count, validation hints)

/**
 * Property mutation to apply when template is activated
 */
export interface PropertyMutation {
  /** Property path (e.g., 'prefixText', 'actionButton') */
  property: string;
  /** Value to set */
  value: unknown;
  /** Category the property belongs to (for schema lookup) */
  category?: 'general' | 'validation' | 'appearance' | 'advanced';
}

/**
 * Template definition
 */
export interface Template {
  /** Unique template identifier */
  id: string;
  /** Display name */
  name: string;
  /** Brief description */
  description: string;
  /** Category for grouping */
  category: TemplateCategory;
  /** Icon to display (lucide icon name) */
  icon?: string;
  /** Preview label shown on the template card */
  previewLabel?: string;
  /** Property mutations to apply when template is activated */
  mutations: PropertyMutation[];
  /** Template IDs that conflict with this one (mutually exclusive) */
  conflicts?: string[];
  /** Element types this template is compatible with */
  compatibleTypes: string[];
  /** Priority for ordering within category (lower = first) */
  priority?: number;
}

/**
 * Template toggle state for UI
 */
export interface TemplateToggleState {
  templateId: string;
  enabled: boolean;
}

/**
 * Result of applying templates to an element
 */
export interface TemplateApplicationResult {
  /** Properties that were changed */
  changedProperties: Array<{
    property: string;
    oldValue: unknown;
    newValue: unknown;
  }>;
  /** Templates that were applied */
  appliedTemplates: string[];
  /** Templates that were skipped due to conflicts */
  skippedDueToConflicts: string[];
}

/**
 * Template registry interface
 */
export interface TemplateRegistry {
  /** Get all templates */
  getAll(): Template[];
  /** Get template by ID */
  getById(id: string): Template | undefined;
  /** Get templates by category */
  getByCategory(category: TemplateCategory): Template[];
  /** Get templates compatible with element type */
  getCompatibleTemplates(elementType: string): Template[];
  /** Check if two templates conflict */
  hasConflict(templateId1: string, templateId2: string): boolean;
  /** Register a new template */
  register(template: Template): void;
}
