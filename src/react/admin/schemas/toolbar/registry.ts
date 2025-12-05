import {
  ToolbarItemSchema,
  ToolbarItemSchemaSchema,
  ToolbarGroup,
  ToolbarFilterOptions,
} from './types';

/**
 * Schema-First Form Builder - Toolbar Registry
 *
 * Central registry for form builder toolbar items.
 * Items register themselves via registerToolbarItem() at import time.
 */

// =============================================================================
// Registry State
// =============================================================================

const items: Map<string, ToolbarItemSchema> = new Map();

// =============================================================================
// Registration
// =============================================================================

/**
 * Register a toolbar item schema. Validates with Zod and crashes if invalid.
 *
 * @throws {ZodError} If schema is invalid
 * @throws {Error} If item ID is already registered
 */
export function registerToolbarItem(schema: ToolbarItemSchema): ToolbarItemSchema {
  // Validate schema structure with Zod
  const validated = ToolbarItemSchemaSchema.parse(schema);

  // Check for duplicate registration
  if (items.has(validated.id)) {
    throw new Error(
      `Toolbar item '${validated.id}' is already registered. ` +
      `Each item can only be registered once.`
    );
  }

  // Store in registry
  items.set(validated.id, validated);

  return validated;
}

// =============================================================================
// Queries
// =============================================================================

/**
 * Get a toolbar item by ID.
 */
export function getToolbarItem(id: string): ToolbarItemSchema | undefined {
  return items.get(id);
}

/**
 * Get all registered toolbar items.
 */
export function getAllToolbarItems(options: ToolbarFilterOptions = {}): ToolbarItemSchema[] {
  let result = Array.from(items.values());

  // Filter by group
  if (options.group) {
    result = result.filter(item => item.group === options.group);
  }

  // Filter by type
  if (options.type) {
    result = result.filter(item => item.type === options.type);
  }

  // Filter mobile hidden items
  if (!options.includeMobileHidden) {
    // Keep all items by default (mobile hiding is handled in component)
  }

  return result;
}

/**
 * Get toolbar items by group, sorted by position.
 */
export function getToolbarItemsByGroup(group: ToolbarGroup): ToolbarItemSchema[] {
  return getAllToolbarItems({ group }).sort((a, b) => a.position - b.position);
}

/**
 * Get all toolbar items sorted by group and position.
 */
export function getToolbarItemsSorted(): ToolbarItemSchema[] {
  const groupOrder: ToolbarGroup[] = ['left', 'history', 'canvas', 'panels', 'primary'];

  return Array.from(items.values()).sort((a, b) => {
    const groupDiff = groupOrder.indexOf(a.group) - groupOrder.indexOf(b.group);
    if (groupDiff !== 0) return groupDiff;
    return a.position - b.position;
  });
}

/**
 * Get all toolbar item IDs.
 */
export function getAllToolbarItemIds(): string[] {
  return Array.from(items.keys());
}

/**
 * Check if a toolbar item is registered.
 */
export function isToolbarItemRegistered(id: string): boolean {
  return items.has(id);
}

// =============================================================================
// Mutations (for testing/development)
// =============================================================================

/**
 * Unregister a toolbar item (use with caution).
 */
export function unregisterToolbarItem(id: string): boolean {
  return items.delete(id);
}

/**
 * Clear all registered toolbar items (for testing only).
 */
export function clearToolbarRegistry(): void {
  if (process.env.NODE_ENV !== 'test') {
    console.warn('clearToolbarRegistry() should only be used in tests');
  }
  items.clear();
}

// =============================================================================
// Debug / Development
// =============================================================================

/**
 * Get registry stats for debugging.
 */
export function getToolbarRegistryStats(): {
  totalItems: number;
  byGroup: Record<string, number>;
  byType: Record<string, number>;
} {
  const allItems = Array.from(items.values());
  const byGroup: Record<string, number> = {};
  const byType: Record<string, number> = {};

  allItems.forEach(item => {
    byGroup[item.group] = (byGroup[item.group] || 0) + 1;
    byType[item.type] = (byType[item.type] || 0) + 1;
  });

  return {
    totalItems: items.size,
    byGroup,
    byType,
  };
}
