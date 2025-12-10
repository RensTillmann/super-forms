import React, { useRef, useEffect, useMemo, useCallback, useState } from 'react';
import { Drawer } from 'vaul';
import { X, Trash2, FileText, Palette, Settings2, Code2, LayoutTemplate, Sparkles } from 'lucide-react';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../../../../components/ui/tabs';
import { ContentTab, StyleTab, BehaviorTab, CodeTab, TemplatesTab, AITab } from './tabs';
import { isElementRegistered, getElementSchema } from '../../../../schemas/core/registry';
import { useElementsStore } from '../../store/useElementsStore';
import { NodeType, StyleProperties } from '../../../../schemas/styles';
import { useIsMobile } from '../../../../hooks/useMediaQuery';
import { cn } from '../../../../lib/utils';
import { Button } from '../../../../components/ui/button';
import { ScrollArea } from '../../../../components/ui/scroll-area';

interface FloatingPanelProps {
  /** The element ID to edit - component subscribes to store for fresh data */
  elementId: string;
  /** Position to render the panel */
  position: { x: number; y: number };
  /** Called when panel should close */
  onClose: () => void;
  /** Called when a property changes */
  onPropertyChange: (propertyName: string, value: unknown) => void;
  /** Called when delete is clicked */
  onDelete: () => void;
}

type PanelTab = 'content' | 'style' | 'behavior' | 'code' | 'templates' | 'ai';

const TAB_CONFIG: { id: PanelTab; label: string; icon: React.ComponentType<{ className?: string }> }[] = [
  { id: 'content', label: 'Content', icon: FileText },
  { id: 'style', label: 'Style', icon: Palette },
  { id: 'behavior', label: 'Behavior', icon: Settings2 },
  { id: 'code', label: 'Code', icon: Code2 },
  { id: 'templates', label: 'Templates', icon: LayoutTemplate },
  { id: 'ai', label: 'AI', icon: Sparkles },
];

// ============================================================================
// Extracted sub-components (module-level for stable identity, prevents scroll reset)
// ============================================================================

interface PanelHeaderProps {
  headerRef: React.RefObject<HTMLDivElement | null>;
  displayName: string;
  hasSchema: boolean;
  IconComponent?: React.ComponentType<{ size?: number }>;
  onDelete: () => void;
  onClose: () => void;
}

const PanelHeader = React.memo<PanelHeaderProps>(({
  headerRef,
  displayName,
  hasSchema,
  IconComponent,
  onDelete,
  onClose,
}) => (
  <div ref={headerRef} className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50 shrink-0" data-testid="floating-panel-header">
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
        aria-label="Delete element"
        data-testid="floating-panel-delete"
      >
        <Trash2 size={16} />
      </Button>
      <Button
        variant="ghost"
        size="icon"
        onClick={onClose}
        className="text-gray-400 hover:text-gray-600 hover:bg-gray-100"
        title="Close panel"
        aria-label="Close panel"
        data-testid="floating-panel-close"
      >
        <X size={16} />
      </Button>
    </div>
  </div>
));
PanelHeader.displayName = 'PanelHeader';

interface TabNavigationProps {
  navRef: React.RefObject<HTMLDivElement | null>;
}

const TabNavigation = React.memo<TabNavigationProps>(({ navRef }) => (
  <div ref={navRef} className="border-b border-gray-200 shrink-0" data-testid="floating-panel-nav">
    <TabsList className="w-full h-auto p-0 bg-transparent rounded-none justify-start overflow-x-auto scrollbar-hide">
      {TAB_CONFIG.map(({ id, label, icon: Icon }) => (
        <TabsTrigger
          key={id}
          value={id}
          className={cn(
            "flex-1 sm:flex-none px-3 py-2.5 text-xs font-medium rounded-none border-b-2 gap-1.5",
            "data-[state=active]:border-primary data-[state=active]:text-primary data-[state=active]:bg-transparent",
            "data-[state=inactive]:border-transparent data-[state=inactive]:text-gray-500",
            "hover:text-gray-700 hover:bg-gray-50 transition-colors",
            "min-h-[44px]" // Touch-friendly
          )}
          data-testid={`floating-panel-tab-${id}`}
        >
          <Icon className="h-4 w-4" />
          <span className="hidden sm:inline">{label}</span>
        </TabsTrigger>
      ))}
    </TabsList>
  </div>
));
TabNavigation.displayName = 'TabNavigation';

interface TabContentPanelsProps {
  isMobile: boolean;
  contentRef: React.RefObject<HTMLDivElement | null>;
  children: React.ReactNode;
}

const TabContentPanels = React.memo<TabContentPanelsProps>(({ isMobile, contentRef, children }) => (
  isMobile ? (
    <div
      ref={contentRef}
      className="flex-1 min-h-0 overflow-y-auto overscroll-contain touch-pan-y"
      data-testid="floating-panel-content"
      data-vaul-no-drag
    >
      {children}
    </div>
  ) : (
    <ScrollArea ref={contentRef} className="flex-1 min-h-0" data-testid="floating-panel-content">
      {children}
    </ScrollArea>
  )
));
TabContentPanels.displayName = 'TabContentPanels';

// ============================================================================

/**
 * Floating property panel with 4-tab structure.
 * Content | Style | Behavior | Code
 */
export const FloatingPanel: React.FC<FloatingPanelProps> = ({
  elementId,
  position,
  onClose,
  onPropertyChange,
  onDelete,
}) => {
  const panelRef = useRef<HTMLDivElement>(null);
  const handleRef = useRef<HTMLDivElement>(null);
  const headerRef = useRef<HTMLDivElement>(null);
  const navRef = useRef<HTMLDivElement>(null);
  const contentRef = useRef<HTMLDivElement>(null);
  const isMobile = useIsMobile();
  const [activeTab, setActiveTab] = useState<PanelTab>('content');

  // Subscribe to store for element - only this component re-renders when element changes
  const element = useElementsStore((s) => s.items[elementId]);

  // Style override store methods
  const setStyleOverride = useElementsStore((s) => s.setStyleOverride);
  const removeStyleOverride = useElementsStore((s) => s.removeStyleOverride);
  const clearAllStyleOverrides = useElementsStore((s) => s.clearAllStyleOverrides);
  const clearNodeStyleOverrides = useElementsStore((s) => s.clearNodeStyleOverrides);

  // Check if element has a schema registered (safe even if element is null)
  const hasSchema = useMemo(() => element ? isElementRegistered(element.type) : false, [element?.type]);
  const schema = useMemo(() => (hasSchema && element) ? getElementSchema(element.type) : null, [element?.type, hasSchema]);

  // Style override handlers (use elementId directly since it's always defined)
  const handleStyleOverrideChange = useCallback(
    (nodeType: string, property: string, value: unknown) => {
      if (value === undefined) {
        removeStyleOverride(elementId, nodeType as NodeType, property as keyof StyleProperties);
      } else {
        setStyleOverride(elementId, nodeType as NodeType, property as keyof StyleProperties, value as StyleProperties[keyof StyleProperties]);
      }
    },
    [elementId, setStyleOverride, removeStyleOverride]
  );

  const handleResetToGlobal = useCallback(
    (nodeType?: string) => {
      if (nodeType) {
        clearNodeStyleOverrides(elementId, nodeType as NodeType);
      } else {
        clearAllStyleOverrides(elementId);
      }
    },
    [elementId, clearNodeStyleOverrides, clearAllStyleOverrides]
  );

  // Mobile: drawer height in pixels (not percentage)
  const [drawerHeight, setDrawerHeight] = useState<number>(400);
  const [isReady, setIsReady] = useState(false);

  // Mobile: scroll element into view and calculate drawer height to position below element
  useEffect(() => {
    if (!isMobile) return;

    const domElement = document.querySelector(`[data-element-id="${elementId}"]`) as HTMLElement;
    if (!domElement) {
      // Default height when element not found
      setDrawerHeight(Math.round(window.innerHeight * 0.6));
      setIsReady(true);
      return;
    }

    // Scroll element to top of viewport so it's visible above the drawer
    domElement.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const calculateHeight = () => {
      const rect = domElement.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const minDrawerHeight = 300; // Minimum drawer height for usability
      const maxDrawerHeight = Math.round(viewportHeight * 0.9); // Max 90% of viewport
      const padding = 16; // Small gap between element and drawer

      // Calculate space from element bottom to viewport bottom
      const availableSpace = viewportHeight - rect.bottom - padding;

      // Clamp between min and max
      const finalHeight = Math.max(minDrawerHeight, Math.min(maxDrawerHeight, availableSpace));

      setDrawerHeight(finalHeight);
      setIsReady(true);
    };

    // Wait for scroll to complete before calculating
    const timeoutId = setTimeout(calculateHeight, 350);
    return () => clearTimeout(timeoutId);
  }, [isMobile, elementId]);

  // Note: Removed tab-change recalculation - drawer height stays fixed based on element position
  // This prevents jarring resize when switching tabs

  // Close on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        onClose();
      }
    };

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

  // Early return if element doesn't exist (was deleted)
  // Must be after all hooks to satisfy React rules
  if (!element) {
    return null;
  }

  // Get display name for the element
  const displayName = schema?.name || element.label || element.type;
  const IconComponent = element.icon;

  // Tab content - shared between mobile and desktop
  const tabContent = (
    <div className="p-4">
      <TabsContent value="content" className="m-0" data-testid="floating-panel-tab-content-content">
        <ContentTab
          element={element}
          onPropertyChange={onPropertyChange}
        />
      </TabsContent>

      <TabsContent value="style" className="m-0" data-testid="floating-panel-tab-content-style">
        <StyleTab
          element={element}
          onPropertyChange={onPropertyChange}
          onOverrideChange={handleStyleOverrideChange}
          onResetToGlobal={handleResetToGlobal}
        />
      </TabsContent>

      <TabsContent value="behavior" className="m-0" data-testid="floating-panel-tab-content-behavior">
        <BehaviorTab
          element={element}
          onPropertyChange={onPropertyChange}
        />
      </TabsContent>

      <TabsContent value="code" className="m-0" data-testid="floating-panel-tab-content-code">
        <CodeTab
          element={element}
          onPropertyChange={onPropertyChange}
        />
      </TabsContent>

      <TabsContent value="templates" className="m-0" data-testid="floating-panel-tab-content-templates">
        <TemplatesTab
          element={element}
          onPropertyChange={onPropertyChange}
        />
      </TabsContent>

      <TabsContent value="ai" className="m-0" data-testid="floating-panel-tab-content-ai">
        <AITab
          element={element}
          onPropertyChange={onPropertyChange}
        />
      </TabsContent>
    </div>
  );

  // Mobile: Vaul drawer from bottom - simple height-based (no snap points)
  if (isMobile) {
    if (!isReady) {
      return null;
    }

    return (
      <Drawer.Root
        open={true}
        onOpenChange={(open) => !open && onClose()}
        modal={true}
        handleOnly={true}
      >
        <Drawer.Portal>
          <Drawer.Overlay className="fixed inset-0 bg-black/40 z-50" data-testid="floating-panel-overlay" />
          <Drawer.Content
            className="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-xl flex flex-col"
            style={{ height: `${drawerHeight}px`, maxHeight: '90vh' }}
            data-testid="floating-panel-drawer"
          >
            <Drawer.Handle
              ref={handleRef}
              className="mx-auto w-12 h-1.5 bg-gray-300 rounded-full mt-3 mb-2 shrink-0"
              data-testid="floating-panel-handle"
            />
            <Drawer.Title className="sr-only">{displayName} Properties</Drawer.Title>
            <Drawer.Description className="sr-only">Edit properties and styles for {displayName}</Drawer.Description>
            <PanelHeader
              headerRef={headerRef}
              displayName={displayName}
              hasSchema={hasSchema}
              IconComponent={IconComponent}
              onDelete={onDelete}
              onClose={onClose}
            />
            <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as PanelTab)} className="flex flex-col flex-1 min-h-0">
              <TabNavigation navRef={navRef} />
              <TabContentPanels isMobile={isMobile} contentRef={contentRef}>
                {tabContent}
              </TabContentPanels>
            </Tabs>
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
      data-testid="floating-panel"
    >
      <PanelHeader
        headerRef={headerRef}
        displayName={displayName}
        hasSchema={hasSchema}
        IconComponent={IconComponent}
        onDelete={onDelete}
        onClose={onClose}
      />
      <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as PanelTab)} className="flex flex-col flex-1 min-h-0">
        <TabNavigation navRef={navRef} />
        <TabContentPanels isMobile={isMobile} contentRef={contentRef}>
          {tabContent}
        </TabContentPanels>
      </Tabs>
    </div>
  );
};

export default FloatingPanel;
