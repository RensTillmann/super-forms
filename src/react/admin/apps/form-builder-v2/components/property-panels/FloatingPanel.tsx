import React, { useRef, useEffect, useMemo, useState } from 'react';
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
import { MobileDrawer } from '../../../../components/ui/mobile-drawer';

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
  isMobile?: boolean;
}

const PanelHeader = React.memo<PanelHeaderProps>(({
  headerRef,
  displayName,
  hasSchema,
  IconComponent,
  onDelete,
  onClose,
  isMobile = false,
}) => (
  <div
    ref={headerRef}
    className={cn("bg-muted/50 border-b border-border shrink-0", !isMobile && "pt-1.5")}
    data-testid="floating-panel-header"
    data-drawer-header
  >
    {/* Drag bar - only show on desktop (mobile drawer has its own handle) */}
    {!isMobile && (
      <div className="flex justify-center pb-1">
        <div className="w-10 h-1 bg-muted-foreground/30 rounded-full" />
      </div>
    )}
    {/* Title row with icons */}
    <div className={cn("flex items-center justify-between px-3", isMobile ? "py-2" : "pb-2")}>
      <div className="flex items-center gap-2 min-w-0">
        {IconComponent && <IconComponent size={16} />}
        <h3 className="text-sm font-medium text-foreground truncate">
          {displayName}
        </h3>
        {hasSchema && (
          <span className="px-1.5 py-0.5 text-[10px] font-medium text-primary bg-primary/10 rounded shrink-0">
            Schema
          </span>
        )}
      </div>
      <div className="flex items-center gap-0.5 shrink-0">
        <Button
          variant="ghost"
          size="icon"
          onClick={onDelete}
          className="h-7 w-7 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
          title="Delete element"
          aria-label="Delete element"
          data-testid="floating-panel-delete"
        >
          <Trash2 size={14} />
        </Button>
        <Button
          variant="ghost"
          size="icon"
          onClick={onClose}
          className="h-7 w-7 text-muted-foreground hover:text-foreground hover:bg-muted"
          title="Close panel"
          aria-label="Close panel"
          data-testid="floating-panel-close"
        >
          <X size={14} />
        </Button>
      </div>
    </div>
  </div>
));
PanelHeader.displayName = 'PanelHeader';

interface TabNavigationProps {
  navRef: React.RefObject<HTMLDivElement | null>;
}

const TabNavigation = React.memo<TabNavigationProps>(({ navRef }) => (
  <div ref={navRef} className="border-b border-border shrink-0" data-testid="floating-panel-nav">
    <TabsList className="w-full h-auto p-0 bg-transparent rounded-none justify-start overflow-x-auto scrollbar-hide">
      {TAB_CONFIG.map(({ id, label, icon: Icon }) => (
        <TabsTrigger
          key={id}
          value={id}
          className={cn(
            "flex-1 sm:flex-none px-3 py-2 text-xs font-medium rounded-none border-b-2 gap-1.5",
            "data-[state=active]:border-primary data-[state=active]:text-primary data-[state=active]:bg-transparent",
            "data-[state=inactive]:border-transparent data-[state=inactive]:text-muted-foreground",
            "hover:text-foreground hover:bg-muted/50 transition-colors",
            "min-h-[40px]"
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
    // Mobile: flex-1 to fill remaining drawer space, scroll internally
    // Disable focus transitions on inputs to prevent layout shifts
    <div
      ref={contentRef}
      className="flex-1 min-h-0 overflow-y-auto overscroll-contain [&_input]:transition-none [&_textarea]:transition-none [&_select]:transition-none [&_button]:transition-none"
      data-testid="floating-panel-content"
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
 * Floating property panel with 6-tab structure.
 * Content | Style | Behavior | Code | Templates | AI
 */
export const FloatingPanel: React.FC<FloatingPanelProps> = ({
  elementId,
  position,
  onClose,
  onPropertyChange,
  onDelete,
}) => {
  const panelRef = useRef<HTMLDivElement>(null);
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

  // Style override handlers
  const handleStyleOverrideChange = (nodeType: string, property: string, value: unknown) => {
    if (value === undefined) {
      removeStyleOverride(elementId, nodeType as NodeType, property as keyof StyleProperties);
    } else {
      setStyleOverride(elementId, nodeType as NodeType, property as keyof StyleProperties, value as StyleProperties[keyof StyleProperties]);
    }
  };

  const handleResetToGlobal = (nodeType?: string) => {
    if (nodeType) {
      clearNodeStyleOverrides(elementId, nodeType as NodeType);
    } else {
      clearAllStyleOverrides(elementId);
    }
  };

  // Close on click outside (desktop only)
  useEffect(() => {
    if (isMobile) return;

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
  }, [onClose, isMobile]);

  // Close on Escape (desktop only - MobileDrawer handles its own)
  useEffect(() => {
    if (isMobile) return;

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [onClose, isMobile]);

  // Calculate clamped position to keep panel in viewport (desktop)
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

  // Mobile: Custom MobileDrawer (replaces Vaul)
  if (isMobile) {
    return (
      <MobileDrawer
        open={true}
        onClose={onClose}
        title={`${displayName} Properties`}
        description={`Edit properties and styles for ${displayName}`}
        data-testid="floating-panel-drawer"
      >
        <PanelHeader
          headerRef={headerRef}
          displayName={displayName}
          hasSchema={hasSchema}
          IconComponent={IconComponent}
          onDelete={onDelete}
          onClose={onClose}
          isMobile={true}
        />
        <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as PanelTab)} className="flex flex-col flex-1 min-h-0">
          <TabNavigation navRef={navRef} />
          <TabContentPanels isMobile={isMobile} contentRef={contentRef}>
            {tabContent}
          </TabContentPanels>
        </Tabs>
      </MobileDrawer>
    );
  }

  // Desktop: positioned floating panel
  return (
    <div
      ref={panelRef}
      className="fixed z-50 w-[480px] max-w-[calc(100vw-32px)] bg-background border border-border rounded-lg shadow-xl flex flex-col max-h-[600px]"
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
