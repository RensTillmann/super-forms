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
 * - Subscription system for React integration
 */
class StyleRegistry {
  private globalStyles: Map<NodeType, Partial<StyleProperties>>;
  private listeners: Set<() => void>;
  private version: number;
  private cachedAllStyles: Record<NodeType, Partial<StyleProperties>> | null;
  private cachedVersion: number;

  constructor() {
    this.globalStyles = new Map();
    this.listeners = new Set();
    this.version = 0;
    this.cachedAllStyles = null;
    this.cachedVersion = -1;

    // Initialize with defaults
    this.resetAllToDefaults();
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
   * Returns a cached reference if version hasn't changed (for useSyncExternalStore).
   */
  getAllGlobalStyles(): Record<NodeType, Partial<StyleProperties>> {
    // Return cached version if still valid
    if (this.cachedAllStyles !== null && this.cachedVersion === this.version) {
      return this.cachedAllStyles;
    }

    // Rebuild cache
    const result: Record<string, Partial<StyleProperties>> = {};
    this.globalStyles.forEach((styles, nodeType) => {
      result[nodeType] = { ...styles };
    });
    this.cachedAllStyles = result as Record<NodeType, Partial<StyleProperties>>;
    this.cachedVersion = this.version;
    return this.cachedAllStyles;
  }

  /**
   * Get current version (increments on each change).
   * Useful for React memoization.
   */
  getVersion(): number {
    return this.version;
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
    const capabilityKey = property as keyof ReturnType<typeof getNodeCapabilities>;
    if (!hasCapability(nodeType, capabilityKey)) {
      console.warn(`Node type '${nodeType}' does not support property '${String(property)}'`);
      return;
    }

    const current = this.globalStyles.get(nodeType) ?? {};
    this.globalStyles.set(nodeType, { ...current, [property]: value });
    this.incrementVersion();
    this.notifyListeners();
  }

  /**
   * Set multiple properties in global style.
   */
  setGlobalStyle(nodeType: NodeType, updates: Partial<StyleProperties>): void {
    const current = this.globalStyles.get(nodeType) ?? {};
    this.globalStyles.set(nodeType, { ...current, ...updates });
    this.incrementVersion();
    this.notifyListeners();
  }

  /**
   * Remove a specific property from global style (reverts to default).
   */
  removeGlobalProperty(nodeType: NodeType, property: keyof StyleProperties): void {
    const current = this.globalStyles.get(nodeType);
    if (!current || !(property in current)) return;

    // Get default value and create updated style
    const defaultStyle = getDefaultStyle(nodeType);
    const updated: Partial<StyleProperties> = { ...current };

    // Remove the property and restore default if available
    delete updated[property];
    if (property in defaultStyle) {
      Object.assign(updated, { [property]: defaultStyle[property] });
    }

    this.globalStyles.set(nodeType, updated);
    this.incrementVersion();
    this.notifyListeners();
  }

  /**
   * Reset a node type to default global style.
   */
  resetToDefault(nodeType: NodeType): void {
    this.globalStyles.set(nodeType, { ...getDefaultStyle(nodeType) });
    this.incrementVersion();
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
    this.incrementVersion();
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

  /**
   * Check if a property is overridden (different from global).
   */
  isPropertyOverridden(
    nodeType: NodeType,
    property: keyof StyleProperties,
    overrideValue: unknown
  ): boolean {
    const globalValue = this.getGlobalProperty(nodeType, property);
    return overrideValue !== undefined && overrideValue !== globalValue;
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
    this.listeners.forEach(listener => {
      try {
        listener();
      } catch (error) {
        console.error('Style listener error:', error);
      }
    });
  }

  private incrementVersion(): void {
    this.version++;
  }

  // ===========================================================================
  // Serialization (for save/load)
  // ===========================================================================

  /**
   * Export global styles as JSON string.
   */
  exportStyles(): string {
    return JSON.stringify(this.getAllGlobalStyles(), null, 2);
  }

  /**
   * Import global styles from JSON string.
   */
  importStyles(json: string): boolean {
    try {
      const parsed = JSON.parse(json);

      for (const [nodeType, styles] of Object.entries(parsed)) {
        // Validate with Zod
        const validated = StylePropertySchema.partial().safeParse(styles);
        if (validated.success) {
          this.globalStyles.set(nodeType as NodeType, validated.data);
        } else {
          console.warn(`Invalid styles for node type '${nodeType}':`, validated.error);
        }
      }

      this.incrementVersion();
      this.notifyListeners();
      return true;
    } catch (error) {
      console.error('Failed to import styles:', error);
      return false;
    }
  }

  /**
   * Export styles as a plain object (not stringified).
   */
  toJSON(): Record<NodeType, Partial<StyleProperties>> {
    return this.getAllGlobalStyles();
  }

  /**
   * Import styles from a plain object.
   */
  fromJSON(data: Record<string, Partial<StyleProperties>>): boolean {
    try {
      for (const [nodeType, styles] of Object.entries(data)) {
        const validated = StylePropertySchema.partial().safeParse(styles);
        if (validated.success) {
          this.globalStyles.set(nodeType as NodeType, validated.data);
        }
      }
      this.incrementVersion();
      this.notifyListeners();
      return true;
    } catch (error) {
      console.error('Failed to load styles:', error);
      return false;
    }
  }

  // ===========================================================================
  // Debug / Development
  // ===========================================================================

  /**
   * Get registry stats for debugging.
   */
  getStats(): {
    nodeTypeCount: number;
    totalProperties: number;
    version: number;
    listenerCount: number;
  } {
    let totalProperties = 0;
    this.globalStyles.forEach((styles) => {
      totalProperties += Object.keys(styles).length;
    });

    return {
      nodeTypeCount: this.globalStyles.size,
      totalProperties,
      version: this.version,
      listenerCount: this.listeners.size,
    };
  }
}

// =============================================================================
// Singleton Instance
// =============================================================================

/**
 * Global style registry singleton.
 * Use this for all style operations.
 */
export const styleRegistry = new StyleRegistry();

// Re-export class for type usage
export { StyleRegistry };
