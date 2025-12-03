/**
 * OperationsManager - Client-side undo/redo with JSON Patch operations
 *
 * Manages operation history for form editing with undo/redo support.
 * Works with SUPER_Form_Operations backend via REST API.
 *
 * @since 6.6.0 (Phase 27)
 */

export interface Operation {
  op: 'add' | 'remove' | 'replace' | 'move' | 'copy' | 'test';
  path: string;
  value?: any;
  from?: string;
  oldValue?: any; // Captured for undo
}

export interface OperationsManagerOptions {
  maxHistorySize?: number;
  onHistoryChange?: (canUndo: boolean, canRedo: boolean) => void;
  autoSaveInterval?: number; // Auto-save interval in ms (0 = disabled)
}

export class OperationsManager {
  private undoStack: Operation[] = [];
  private redoStack: Operation[] = [];
  private maxHistorySize: number;
  private onHistoryChange?: (canUndo: boolean, canRedo: boolean) => void;
  private autoSaveInterval: number;
  private autoSaveTimer?: NodeJS.Timeout;
  private pendingOperations: Operation[] = [];

  constructor(options: OperationsManagerOptions = {}) {
    this.maxHistorySize = options.maxHistorySize ?? 100;
    this.onHistoryChange = options.onHistoryChange;
    this.autoSaveInterval = options.autoSaveInterval ?? 0;

    // Restore from localStorage if available
    this.restoreFromLocalStorage();

    // Start auto-save timer if enabled
    if (this.autoSaveInterval > 0) {
      this.startAutoSave();
    }
  }

  /**
   * Apply an operation and add to undo history
   *
   * @param operation Operation to apply
   * @param formData Current form data (used to capture oldValue)
   * @returns Modified form data
   */
  applyOperation(operation: Operation, formData: any): any {
    // Capture old value for undo (if needed)
    const enrichedOp = this.enrichOperationWithOldValue(operation, formData);

    // Apply operation to form data
    const modifiedData = this.executeOperation(enrichedOp, formData);

    // Add to undo stack
    this.undoStack.push(enrichedOp);

    // Limit stack size
    if (this.undoStack.length > this.maxHistorySize) {
      this.undoStack.shift();
    }

    // Clear redo stack (new operation invalidates redo history)
    this.redoStack = [];

    // Add to pending operations for server sync
    this.pendingOperations.push(enrichedOp);

    // Notify listeners
    this.notifyHistoryChange();

    // Persist to localStorage
    this.saveToLocalStorage();

    return modifiedData;
  }

  /**
   * Undo the last operation
   *
   * @param formData Current form data
   * @returns Modified form data or null if nothing to undo
   */
  undo(formData: any): any | null {
    if (this.undoStack.length === 0) {
      return null;
    }

    const operation = this.undoStack.pop()!;
    const inverseOp = this.getInverseOperation(operation);

    // Apply inverse operation
    const modifiedData = this.executeOperation(inverseOp, formData);

    // Add to redo stack
    this.redoStack.push(operation);

    // Notify listeners
    this.notifyHistoryChange();

    // Persist to localStorage
    this.saveToLocalStorage();

    return modifiedData;
  }

  /**
   * Redo the last undone operation
   *
   * @param formData Current form data
   * @returns Modified form data or null if nothing to redo
   */
  redo(formData: any): any | null {
    if (this.redoStack.length === 0) {
      return null;
    }

    const operation = this.redoStack.pop()!;

    // Apply operation
    const modifiedData = this.executeOperation(operation, formData);

    // Add back to undo stack
    this.undoStack.push(operation);

    // Notify listeners
    this.notifyHistoryChange();

    // Persist to localStorage
    this.saveToLocalStorage();

    return modifiedData;
  }

  /**
   * Execute a JSON Patch operation on form data
   *
   * @param operation Operation to execute
   * @param formData Form data to modify
   * @returns Modified form data
   */
  private executeOperation(operation: Operation, formData: any): any {
    const result = JSON.parse(JSON.stringify(formData)); // Deep clone

    switch (operation.op) {
      case 'add':
        this.opAdd(result, operation);
        break;
      case 'remove':
        this.opRemove(result, operation);
        break;
      case 'replace':
        this.opReplace(result, operation);
        break;
      case 'move':
        this.opMove(result, operation);
        break;
      case 'copy':
        this.opCopy(result, operation);
        break;
      case 'test':
        // Test operations don't modify data
        break;
    }

    return result;
  }

  /**
   * Add operation
   */
  private opAdd(data: any, op: Operation): void {
    const pathParts = this.parsePath(op.path);
    this.setValueAtPath(data, pathParts, op.value, true);
  }

  /**
   * Remove operation
   */
  private opRemove(data: any, op: Operation): void {
    const pathParts = this.parsePath(op.path);
    this.unsetValueAtPath(data, pathParts);
  }

  /**
   * Replace operation
   */
  private opReplace(data: any, op: Operation): void {
    const pathParts = this.parsePath(op.path);
    this.setValueAtPath(data, pathParts, op.value, false);
  }

  /**
   * Move operation
   */
  private opMove(data: any, op: Operation): void {
    if (!op.from) throw new Error('Move operation requires "from" field');

    const fromParts = this.parsePath(op.from);
    const value = this.getValueAtPath(data, fromParts);

    this.unsetValueAtPath(data, fromParts);

    const toParts = this.parsePath(op.path);
    this.setValueAtPath(data, toParts, value, true);
  }

  /**
   * Copy operation
   */
  private opCopy(data: any, op: Operation): void {
    if (!op.from) throw new Error('Copy operation requires "from" field');

    const fromParts = this.parsePath(op.from);
    const value = this.getValueAtPath(data, fromParts);

    const toParts = this.parsePath(op.path);
    this.setValueAtPath(data, toParts, value, true);
  }

  /**
   * Parse JSON Pointer path into array
   */
  private parsePath(path: string): string[] {
    if (path === '' || path === '/') return [];

    return path
      .slice(1) // Remove leading slash
      .split('/')
      .map((part) => {
        // Decode special characters
        return part.replace(/~1/g, '/').replace(/~0/g, '~');
      });
  }

  /**
   * Get value at path
   */
  private getValueAtPath(data: any, pathParts: string[]): any {
    let current = data;

    for (const part of pathParts) {
      if (current && typeof current === 'object' && part in current) {
        current = current[part];
      } else {
        throw new Error(`Path not found: /${pathParts.join('/')}`);
      }
    }

    return current;
  }

  /**
   * Set value at path
   */
  private setValueAtPath(
    data: any,
    pathParts: string[],
    value: any,
    allowAppend: boolean
  ): void {
    if (pathParts.length === 0) {
      throw new Error('Cannot set root value');
    }

    let current = data;
    const lastIndex = pathParts.length - 1;

    // Navigate to parent
    for (let i = 0; i < lastIndex; i++) {
      const part = pathParts[i];

      if (!(part in current)) {
        current[part] = {};
      }

      current = current[part];
    }

    const finalPart = pathParts[lastIndex];

    // Handle array append notation "-"
    if (finalPart === '-' && allowAppend) {
      if (!Array.isArray(current)) {
        throw new Error('Cannot append to non-array');
      }
      current.push(value);
    } else {
      current[finalPart] = value;
    }
  }

  /**
   * Unset value at path
   */
  private unsetValueAtPath(data: any, pathParts: string[]): void {
    if (pathParts.length === 0) {
      throw new Error('Cannot remove root element');
    }

    let current = data;
    const lastIndex = pathParts.length - 1;

    // Navigate to parent
    for (let i = 0; i < lastIndex; i++) {
      const part = pathParts[i];

      if (!(part in current)) {
        throw new Error(`Path not found: /${pathParts.join('/')}`);
      }

      current = current[part];
    }

    const finalPart = pathParts[lastIndex];

    if (Array.isArray(current)) {
      current.splice(parseInt(finalPart, 10), 1);
    } else {
      delete current[finalPart];
    }
  }

  /**
   * Enrich operation with oldValue for undo
   */
  private enrichOperationWithOldValue(op: Operation, formData: any): Operation {
    const enriched = { ...op };

    try {
      if (op.op === 'remove' || op.op === 'replace') {
        const pathParts = this.parsePath(op.path);
        enriched.oldValue = this.getValueAtPath(formData, pathParts);
      }
    } catch (e) {
      // Path doesn't exist yet (e.g., adding new field)
      enriched.oldValue = undefined;
    }

    return enriched;
  }

  /**
   * Get inverse operation for undo
   */
  private getInverseOperation(op: Operation): Operation {
    switch (op.op) {
      case 'add':
        return { op: 'remove', path: op.path };

      case 'remove':
        return { op: 'add', path: op.path, value: op.oldValue };

      case 'replace':
        return { op: 'replace', path: op.path, value: op.oldValue };

      case 'move':
        if (!op.from) throw new Error('Move operation missing "from"');
        return { op: 'move', from: op.path, path: op.from };

      case 'copy':
        return { op: 'remove', path: op.path };

      case 'test':
        return op; // Test doesn't modify

      default:
        throw new Error(`Unknown operation: ${(op as any).op}`);
    }
  }

  /**
   * Check if undo is available
   */
  canUndo(): boolean {
    return this.undoStack.length > 0;
  }

  /**
   * Check if redo is available
   */
  canRedo(): boolean {
    return this.redoStack.length > 0;
  }

  /**
   * Get pending operations (for server sync)
   */
  getPendingOperations(): Operation[] {
    return [...this.pendingOperations];
  }

  /**
   * Clear pending operations (after successful sync)
   */
  clearPendingOperations(): void {
    this.pendingOperations = [];
    this.saveToLocalStorage();
  }

  /**
   * Clear all history
   */
  clearHistory(): void {
    this.undoStack = [];
    this.redoStack = [];
    this.pendingOperations = [];
    this.notifyHistoryChange();
    this.saveToLocalStorage();
  }

  /**
   * Notify listeners of history change
   */
  private notifyHistoryChange(): void {
    if (this.onHistoryChange) {
      this.onHistoryChange(this.canUndo(), this.canRedo());
    }
  }

  /**
   * Save state to localStorage
   */
  private saveToLocalStorage(): void {
    try {
      const state = {
        undoStack: this.undoStack,
        redoStack: this.redoStack,
        pendingOperations: this.pendingOperations,
      };
      localStorage.setItem('superforms_operations', JSON.stringify(state));
    } catch (e) {
      console.warn('Failed to save operations to localStorage:', e);
    }
  }

  /**
   * Restore state from localStorage
   */
  private restoreFromLocalStorage(): void {
    try {
      const saved = localStorage.getItem('superforms_operations');
      if (saved) {
        const state = JSON.parse(saved);
        this.undoStack = state.undoStack || [];
        this.redoStack = state.redoStack || [];
        this.pendingOperations = state.pendingOperations || [];
        this.notifyHistoryChange();
      }
    } catch (e) {
      console.warn('Failed to restore operations from localStorage:', e);
    }
  }

  /**
   * Start auto-save timer
   */
  private startAutoSave(): void {
    if (this.autoSaveTimer) {
      clearInterval(this.autoSaveTimer);
    }

    this.autoSaveTimer = setInterval(() => {
      if (this.pendingOperations.length > 0) {
        // Trigger auto-save event (handled by parent component)
        window.dispatchEvent(
          new CustomEvent('superforms:autosave', {
            detail: { operations: this.getPendingOperations() },
          })
        );
      }
    }, this.autoSaveInterval);
  }

  /**
   * Stop auto-save timer
   */
  stopAutoSave(): void {
    if (this.autoSaveTimer) {
      clearInterval(this.autoSaveTimer);
      this.autoSaveTimer = undefined;
    }
  }

  /**
   * Cleanup (call when unmounting)
   */
  destroy(): void {
    this.stopAutoSave();
  }
}
