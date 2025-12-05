import { TabSchema, TabSchemaSchema, TabFilterOptions } from './types';

/**
 * Schema-First Form Builder - Tab Registry
 *
 * Central registry for form builder tabs.
 * Tabs register themselves via registerTab() at import time.
 */

// =============================================================================
// Registry State
// =============================================================================

const tabs: Map<string, TabSchema> = new Map();

// =============================================================================
// Registration
// =============================================================================

/**
 * Register a tab schema. Validates with Zod and crashes if invalid.
 *
 * @throws {ZodError} If schema is invalid
 * @throws {Error} If tab ID is already registered
 */
export function registerTab(schema: TabSchema): TabSchema {
  // Validate schema structure with Zod
  const validated = TabSchemaSchema.parse(schema);

  // Check for duplicate registration
  if (tabs.has(validated.id)) {
    throw new Error(
      `Tab '${validated.id}' is already registered. ` +
      `Each tab can only be registered once.`
    );
  }

  // Store in registry
  tabs.set(validated.id, validated);

  return validated;
}

// =============================================================================
// Queries
// =============================================================================

/**
 * Get a tab schema by ID.
 */
export function getTabSchema(id: string): TabSchema | undefined {
  return tabs.get(id);
}

/**
 * Get all registered tabs.
 */
export function getAllTabs(options: TabFilterOptions = {}): TabSchema[] {
  let result = Array.from(tabs.values());

  // Filter hidden tabs unless explicitly included
  if (!options.includeHidden) {
    result = result.filter(tab => !tab.hidden);
  }

  // Filter by permission if specified
  if (options.permission) {
    result = result.filter(tab =>
      !tab.requiredPermission || tab.requiredPermission === options.permission
    );
  }

  // Filter by plan if specified
  if (options.plan) {
    result = result.filter(tab =>
      !tab.requiredPlan || tab.requiredPlan === options.plan
    );
  }

  return result;
}

/**
 * Get all tabs sorted by position.
 */
export function getTabsSorted(options: TabFilterOptions = {}): TabSchema[] {
  return getAllTabs(options).sort((a, b) => a.position - b.position);
}

/**
 * Get all tab IDs.
 */
export function getAllTabIds(): string[] {
  return Array.from(tabs.keys());
}

/**
 * Check if a tab is registered.
 */
export function isTabRegistered(id: string): boolean {
  return tabs.has(id);
}

// =============================================================================
// Mutations (for testing/development)
// =============================================================================

/**
 * Unregister a tab (use with caution).
 */
export function unregisterTab(id: string): boolean {
  return tabs.delete(id);
}

/**
 * Clear all registered tabs (for testing only).
 */
export function clearTabRegistry(): void {
  if (process.env.NODE_ENV !== 'test') {
    console.warn('clearTabRegistry() should only be used in tests');
  }
  tabs.clear();
}

// =============================================================================
// Debug / Development
// =============================================================================

/**
 * Get registry stats for debugging.
 */
export function getTabRegistryStats(): {
  totalTabs: number;
  lazyLoadedCount: number;
  hiddenCount: number;
} {
  const allTabs = Array.from(tabs.values());

  return {
    totalTabs: tabs.size,
    lazyLoadedCount: allTabs.filter(t => t.lazyLoad).length,
    hiddenCount: allTabs.filter(t => t.hidden).length,
  };
}
