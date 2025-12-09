import React, { useRef, useEffect, useMemo, useCallback, useState } from 'react';
import { Drawer } from 'vaul';
import { X, Trash2 } from 'lucide-react';
import { SchemaPropertyPanel } from './schema';
import { ElementStylesSection } from './ElementStylesSection';
import { isElementRegistered, getElementSchema } from '../../../../schemas/core/registry';
import { useElementsStore } from '../../store/useElementsStore';
import { NodeType, StyleProperties } from '../../../../schemas/styles';
import { useIsMobile } from '../../../../hooks/useMediaQuery';
import { cn } from '../../../../lib/utils';
import { Button } from '../../../../components/ui/button';

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
  const isMobile = useIsMobile();

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

  // Mobile: state for dynamic snap point calculation
  const [mobileSnapPoint, setMobileSnapPoint] = useState<number>(0.6); // Default 60% of viewport
  const [isScrolled, setIsScrolled] = useState(false);

  // Mobile: scroll element into view and calculate snap point
  useEffect(() => {
    if (!isMobile) return;

    // Find the element in the DOM by data-element-id attribute
    const domElement = document.querySelector(`[data-element-id="${element.id}"]`) as HTMLElement;
    if (!domElement) {
      setIsScrolled(true);
      return;
    }

    // Scroll element into view at the top
    domElement.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Calculate snap point after scroll settles
    const calculateSnapPoint = () => {
      const rect = domElement.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const minDrawerHeight = 400;

      // Element bottom position from top of viewport
      // Show as much of element as possible, starting from top
      const elementVisibleBottom = Math.min(rect.bottom, viewportHeight - minDrawerHeight);

      // Calculate snap point: drawer height as fraction of viewport
      // snapPoint = (viewportHeight - elementBottom) / viewportHeight
      // But we want minimum 400px drawer, so:
      const drawerHeight = Math.max(minDrawerHeight, viewportHeight - elementVisibleBottom);
      const snapPoint = drawerHeight / viewportHeight;

      // Clamp between 0.4 (minimum useful drawer) and 0.95 (near full screen)
      setMobileSnapPoint(Math.min(0.95, Math.max(0.4, snapPoint)));
      setIsScrolled(true);
    };

    // Wait for scroll to complete before calculating
    const timeoutId = setTimeout(calculateSnapPoint, 300);

    return () => clearTimeout(timeoutId);
  }, [isMobile, element.id]);

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

  // Shared header component
  const PanelHeader = () => (
    <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50 shrink-0">
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
        <Button
          variant="ghost"
          size="icon"
          onClick={onDelete}
          className="text-gray-400 hover:text-red-500 hover:bg-red-50"
          title="Delete element"
        >
          <Trash2 size={16} />
        </Button>
        <Button
          variant="ghost"
          size="icon"
          onClick={onClose}
          className="text-gray-400 hover:text-gray-600 hover:bg-gray-100"
          title="Close panel"
        >
          <X size={16} />
        </Button>
      </div>
    </div>
  );

  // Shared content component
  const PanelContent = () => (
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
  );

  // Mobile: Vaul drawer from bottom with element-aware positioning
  if (isMobile) {
    // Don't render until scroll is complete (prevents flash)
    if (!isScrolled) {
      return null;
    }

    return (
      <Drawer.Root
        open={true}
        onOpenChange={(open) => !open && onClose()}
        modal={false}
        snapPoints={[mobileSnapPoint, 0.95]}
        activeSnapPoint={mobileSnapPoint}
        setActiveSnapPoint={() => {}}
      >
        <Drawer.Portal>
          <Drawer.Overlay className="fixed inset-0 bg-black/40 z-50" />
          <Drawer.Content
            className="fixed bottom-0 left-0 right-0 z-50 rounded-t-xl flex flex-col"
            style={{
              backgroundColor: '#ffffff',
              height: `${mobileSnapPoint * 100}vh`,
              maxHeight: '95vh',
            }}
          >
            <div className="mx-auto w-12 h-1.5 bg-gray-300 rounded-full mt-4 mb-2 shrink-0" />
            <Drawer.Title className="sr-only">{displayName} Properties</Drawer.Title>
            <Drawer.Description className="sr-only">Edit properties and styles for {displayName}</Drawer.Description>
            <PanelHeader />
            <PanelContent />
          </Drawer.Content>
        </Drawer.Portal>
      </Drawer.Root>
    );
  }

  // Desktop: positioned floating panel
  return (
    <div
      ref={panelRef}
      className="fixed z-50 w-[480px] max-w-[calc(100vw-32px)] bg-white border border-gray-200 rounded-lg shadow-xl flex flex-col max-h-[600px]"
      style={{
        left: clampedPosition.left,
        top: clampedPosition.top,
      }}
    >
      <PanelHeader />
      <PanelContent />
    </div>
  );
};

export default FloatingPanel;
