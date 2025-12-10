/**
 * Template System
 *
 * Exports for the template registry and types.
 */

// Types
export type {
  Template,
  TemplateCategory,
  PropertyMutation,
  TemplateToggleState,
  TemplateApplicationResult,
  TemplateRegistry,
} from './types';

// Registry
export {
  templateRegistry,
  registerTemplate,
  getTemplateMutations,
  detectActiveTemplates,
} from './registry';

// Load template definitions (side effect: registers templates)
import './definitions/inputAddons';
import './definitions/fieldTemplates';
