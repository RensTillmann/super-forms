import React, { useRef, useEffect, useMemo, useCallback } from 'react';
import { X, Trash2 } from 'lucide-react';
import { SchemaPropertyPanel } from './schema';
import { ElementStylesSection } from './ElementStylesSection';
import { isElementRegistered, getElementSchema } from '../../../../schemas/core/registry';
import { useElementsStore } from '../../store/useElementsStore';
import { NodeType, StyleProperties } from '../../../../schemas/styles';

// Import legacy panels for fallback
import { GeneralProperties, ValidationProperties } from './basic';

interface FloatingPanelProps {
  /** The element being edited */
  element: {
    id: string;
    type: string;
    label?: string;
    icon?: React.ComponentType<{ size?: number }>;
    properties?: Record<string, unknown>;
    styleOverrides?: Record<string, Partial<StyleProperties>>;
  };
  /** Position to render the panel */
  position: { x: number; y: number };
  /** Called when panel should close */
  onClose: () => void;
  /** Called when a property changes */
  onPropertyChange: (propertyName: string, value: unknown) => void;
  /** Called when delete is clicked */
  onDelete: () => void;
}

/**
 * Floating property panel that renders schema-driven property editors.
 * Replaces the old FloatingPropertiesPanel with a clean, schema-first approach.
 */
export const FloatingPanel: React.FC<FloatingPanelProps> = ({
  element,
  position,
  onClose,
  onPropertyChange,
  onDelete,
}) => {
  const panelRef = useRef<HTMLDivElement>(null);

  // Style override store methods
  const setStyleOverride = useElementsStore((s) => s.setStyleOverride);
  const removeStyleOverride = useElementsStore((s) => s.removeStyleOverride);
  const clearAllStyleOverrides = useElementsStore((s) => s.clearAllStyleOverrides);
  const clearNodeStyleOverrides = useElementsStore((s) => s.clearNodeStyleOverrides);

  // Check if element has a schema registered
  const hasSchema = useMemo(() => isElementRegistered(element.type), [element.type]);
  const schema = useMemo(() => hasSchema ? getElementSchema(element.type) : null, [element.type, hasSchema]);

  // Style override handlers
  const handleStyleOverrideChange = useCallback(
    (nodeType: string, property: string, value: unknown) => {
      if (value === undefined) {
        removeStyleOverride(element.id, nodeType as NodeType, property as keyof StyleProperties);
      } else {
        setStyleOverride(element.id, nodeType as NodeType, property as keyof StyleProperties, value as StyleProperties[keyof StyleProperties]);
      }
    },
    [element.id, setStyleOverride, removeStyleOverride]
  );

  const handleResetToGlobal = useCallback(
    (nodeType?: string) => {
      if (nodeType) {
        clearNodeStyleOverrides(element.id, nodeType as NodeType);
      } else {
        clearAllStyleOverrides(element.id);
      }
    },
    [element.id, clearNodeStyleOverrides, clearAllStyleOverrides]
  );

  // Close on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        onClose();
      }
    };

    // Delay adding listener to prevent immediate close
    const timeoutId = setTimeout(() => {
      document.addEventListener('mousedown', handleClickOutside);
    }, 100);

    return () => {
      clearTimeout(timeoutId);
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [onClose]);

  // Close on Escape
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [onClose]);

  // Calculate clamped position to keep panel in viewport
  const clampedPosition = useMemo(() => {
    const panelWidth = 480;
    const panelMaxHeight = 600;
    const padding = 16;

    return {
      left: Math.min(
        Math.max(padding, position.x),
        window.innerWidth - panelWidth - padding
      ),
      top: Math.min(
        Math.max(padding, position.y),
        window.innerHeight - panelMaxHeight - padding
      ),
    };
  }, [position]);

  // Get display name for the element
  const displayName = schema?.name || element.label || element.type;
  const IconComponent = element.icon;

  return (
    <div
      ref={panelRef}
      className="fixed z-50 w-[480px] max-w-[calc(100vw-32px)] bg-white border border-gray-200 rounded-lg shadow-xl flex flex-col max-h-[600px]"
      style={{
        left: clampedPosition.left,
        top: clampedPosition.top,
      }}
    >
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <div className="flex items-center gap-2">
          {IconComponent && <IconComponent size={18} />}
          <h3 className="text-sm font-semibold text-gray-900">
            {displayName}
          </h3>
          {hasSchema && (
            <span className="px-1.5 py-0.5 text-[10px] font-medium text-blue-600 bg-blue-50 rounded">
              Schema
            </span>
          )}
        </div>
        <div className="flex items-center gap-1">
          <button
            onClick={onDelete}
            className="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors"
            title="Delete element"
          >
            <Trash2 size={16} />
          </button>
          <button
            onClick={onClose}
            className="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors"
            title="Close panel"
          >
            <X size={16} />
          </button>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-y-auto p-4">
        {hasSchema ? (
          // Schema-driven panel
          <>
            <SchemaPropertyPanel
              elementType={element.type}
              properties={element.properties || {}}
              onPropertyChange={onPropertyChange}
            />
            <ElementStylesSection
              elementId={element.id}
              elementType={element.type}
              styleOverrides={element.styleOverrides}
              onOverrideChange={handleStyleOverrideChange}
              onResetToGlobal={handleResetToGlobal}
            />
          </>
        ) : (
          // Fallback for elements without schemas
          <div className="space-y-4">
            <div className="p-3 bg-amber-50 border border-amber-200 rounded-md">
              <p className="text-xs text-amber-700">
                This element type ({element.type}) doesn't have a schema yet.
                Using legacy property panels.
              </p>
            </div>
            <GeneralProperties element={element} onUpdate={onPropertyChange} />
            <ValidationProperties element={element} onUpdate={onPropertyChange} />
            <ElementStylesSection
              elementId={element.id}
              elementType={element.type}
              styleOverrides={element.styleOverrides}
              onOverrideChange={handleStyleOverrideChange}
              onResetToGlobal={handleResetToGlobal}
            />
          </div>
        )}
      </div>
    </div>
  );
};

export default FloatingPanel;
