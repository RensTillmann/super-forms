/**
 * Template Registry
 *
 * Central registry for element templates. Templates are predefined configurations
 * that can be toggled on/off and applied to elements via the Templates tab.
 */

import type { Template, TemplateCategory, TemplateRegistry } from './types';

// In-memory template storage
const templates: Map<string, Template> = new Map();

/**
 * Template registry singleton
 */
export const templateRegistry: TemplateRegistry = {
  getAll(): Template[] {
    return Array.from(templates.values()).sort((a, b) => {
      // Sort by category first, then by priority
      if (a.category !== b.category) {
        return a.category.localeCompare(b.category);
      }
      return (a.priority ?? 100) - (b.priority ?? 100);
    });
  },

  getById(id: string): Template | undefined {
    return templates.get(id);
  },

  getByCategory(category: TemplateCategory): Template[] {
    return this.getAll().filter((t) => t.category === category);
  },

  getCompatibleTemplates(elementType: string): Template[] {
    return this.getAll().filter((t) =>
      t.compatibleTypes.includes(elementType) || t.compatibleTypes.includes('*')
    );
  },

  hasConflict(templateId1: string, templateId2: string): boolean {
    const t1 = templates.get(templateId1);
    const t2 = templates.get(templateId2);

    if (!t1 || !t2) return false;

    // Check if either template lists the other as a conflict
    return (
      t1.conflicts?.includes(templateId2) ||
      t2.conflicts?.includes(templateId1) ||
      false
    );
  },

  register(template: Template): void {
    if (templates.has(template.id)) {
      console.warn(`Template "${template.id}" already registered, overwriting.`);
    }
    templates.set(template.id, template);
  },
};

/**
 * Register a template (convenience function)
 */
export function registerTemplate(template: Template): Template {
  templateRegistry.register(template);
  return template;
}

/**
 * Get mutations for a set of enabled templates
 * Handles conflicts by keeping the first enabled template in case of conflicts
 */
export function getTemplateMutations(
  enabledTemplateIds: string[],
  elementType: string
): { mutations: Map<string, unknown>; applied: string[]; skipped: string[] } {
  const mutations = new Map<string, unknown>();
  const applied: string[] = [];
  const skipped: string[] = [];
  const appliedSet = new Set<string>();

  for (const templateId of enabledTemplateIds) {
    const template = templateRegistry.getById(templateId);
    if (!template) continue;

    // Check compatibility
    if (
      !template.compatibleTypes.includes(elementType) &&
      !template.compatibleTypes.includes('*')
    ) {
      skipped.push(templateId);
      continue;
    }

    // Check conflicts with already applied templates
    let hasConflict = false;
    for (const appliedId of appliedSet) {
      if (templateRegistry.hasConflict(templateId, appliedId)) {
        hasConflict = true;
        skipped.push(templateId);
        break;
      }
    }

    if (hasConflict) continue;

    // Apply mutations
    for (const mutation of template.mutations) {
      mutations.set(mutation.property, mutation.value);
    }

    applied.push(templateId);
    appliedSet.add(templateId);
  }

  return { mutations, applied, skipped };
}

/**
 * Check which templates are currently active based on element properties
 */
export function detectActiveTemplates(
  elementType: string,
  properties: Record<string, unknown>
): string[] {
  const activeTemplates: string[] = [];
  const compatibleTemplates = templateRegistry.getCompatibleTemplates(elementType);

  for (const template of compatibleTemplates) {
    // A template is considered active if ALL its mutations match current properties
    const allMutationsMatch = template.mutations.every((mutation) => {
      const currentValue = properties[mutation.property];
      return currentValue === mutation.value;
    });

    if (allMutationsMatch && template.mutations.length > 0) {
      activeTemplates.push(template.id);
    }
  }

  return activeTemplates;
}
