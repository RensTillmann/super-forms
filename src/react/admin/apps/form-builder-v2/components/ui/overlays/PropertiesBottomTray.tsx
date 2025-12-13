import React, { useRef, useState, useMemo } from 'react';
import { ChevronUp, ChevronDown, X, Trash2, FileText, Palette, Settings2, Code2, LayoutTemplate, Sparkles } from 'lucide-react';
import { PropertiesBottomTrayProps } from '../types/overlay.types';
import { cn } from '../../../../../lib/utils';
import { useWPAdminSidebar } from '../../../../../hooks/useWPAdminSidebar';
import { Button } from '../../../../../components/ui/button';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../../../../../components/ui/tabs';
import { useElementsStore } from '../../../store/useElementsStore';
import { isElementRegistered, getElementSchema } from '../../../../../schemas/core/registry';
import { NodeType, StyleProperties } from '../../../../../schemas/styles';
import { ContentTab, StyleTab, BehaviorTab, CodeTab, TemplatesTab, AITab } from '../../property-panels/tabs';

type PanelTab = 'content' | 'style' | 'behavior' | 'code' | 'templates' | 'ai';

const TAB_CONFIG: { id: PanelTab; label: string; icon: React.ComponentType<{ className?: string }> }[] = [
  { id: 'content', label: 'Content', icon: FileText },
  { id: 'style', label: 'Style', icon: Palette },
  { id: 'behavior', label: 'Behavior', icon: Settings2 },
  { id: 'code', label: 'Code', icon: Code2 },
  { id: 'templates', label: 'Templates', icon: LayoutTemplate },
  { id: 'ai', label: 'AI', icon: Sparkles },
];

/**
 * Properties Bottom Tray for mobile - displays element properties in a bottom tray
 * Uses the same UI pattern as ResizableBottomTray (elements palette) but with higher z-index
 * to overlay on top of the elements tray.
 */
export const PropertiesBottomTray: React.FC<PropertiesBottomTrayProps> = ({
  isCollapsed,
  onToggleCollapse,
  onClose,
  elementId,
  onPropertyChange,
  onDelete,
}) => {
  const trayRef = useRef<HTMLDivElement>(null);
  const [activeTab, setActiveTab] = useState<PanelTab>('content');

  // Get WordPress admin sidebar width for dynamic positioning
  const { width: sidebarWidth } = useWPAdminSidebar();

  // Subscribe to store for element data
  const element = useElementsStore((s) => s.items[elementId]);

  // Style override store methods
  const setStyleOverride = useElementsStore((s) => s.setStyleOverride);
  const removeStyleOverride = useElementsStore((s) => s.removeStyleOverride);
  const clearAllStyleOverrides = useElementsStore((s) => s.clearAllStyleOverrides);
  const clearNodeStyleOverrides = useElementsStore((s) => s.clearNodeStyleOverrides);

  // Check if element has a schema registered
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

  // Early return if no element
  if (!element) {
    return null;
  }

  // Get display name for the element
  const displayName = schema?.name || element.label || element.type;
  const IconComponent = element.icon;

  // Fixed height for mobile properties tray (auto with max)
  const effectiveHeight = isCollapsed ? 40 : 'auto';

  return (
    <div
      ref={trayRef}
      className={cn(
        "fixed bottom-0 right-0 z-[60]", // Higher z-index than elements tray (z-50)
        "bg-background border-t border-border",
        "shadow-[0_-4px_16px_-2px_rgb(0,0,0,0.1)]",
        "transition-[height,left] duration-300 ease-out",
        "min-h-4",
        "max-h-[70vh]", // Max 70% of viewport height
        "flex flex-col"
      )}
      style={{
        left: sidebarWidth,
        height: effectiveHeight,
      }}
      data-testid="properties-bottom-tray"
    >
      {/* Chevron Collapse Button */}
      <Button
        variant="ghost"
        size="icon"
        className={cn(
          "absolute -top-4 left-1/2 -translate-x-1/2",
          "w-12 h-4 rounded-t-lg rounded-b-none",
          "bg-muted border border-border border-b-0",
          "text-muted-foreground",
          "z-[1]",
          "hover:bg-accent hover:border-primary hover:text-primary"
        )}
        onClick={onToggleCollapse}
        aria-label={isCollapsed ? 'Show properties' : 'Hide properties'}
        aria-expanded={!isCollapsed}
        data-testid="properties-tray-collapse-button"
      >
        {isCollapsed ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
      </Button>

      {!isCollapsed && (
        <>
          {/* Header with element name, delete, and close buttons */}
          <div
            className="bg-muted/50 border-b border-border shrink-0 py-2"
            data-testid="properties-tray-header"
          >
            <div className="flex items-center justify-between px-3">
              <div className="flex items-center gap-2 min-w-0">
                {IconComponent && <IconComponent />}
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
                  data-testid="properties-tray-delete"
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
                  data-testid="properties-tray-close"
                >
                  <X size={14} />
                </Button>
              </div>
            </div>
          </div>

          {/* Tabs */}
          <Tabs
            value={activeTab}
            onValueChange={(v) => setActiveTab(v as PanelTab)}
            className="flex flex-col flex-1 min-h-0"
          >
            {/* Tab Navigation */}
            <div className="border-b border-border shrink-0" data-testid="properties-tray-nav">
              <TabsList className="w-full h-auto p-0 bg-transparent rounded-none justify-start overflow-x-auto scrollbar-hide">
                {TAB_CONFIG.map(({ id, icon: Icon }) => (
                  <TabsTrigger
                    key={id}
                    value={id}
                    className={cn(
                      "flex-1 px-3 py-2 text-xs font-medium rounded-none border-b-2 gap-1.5",
                      "data-[state=active]:border-primary data-[state=active]:text-primary data-[state=active]:bg-transparent",
                      "data-[state=inactive]:border-transparent data-[state=inactive]:text-muted-foreground",
                      "hover:text-foreground hover:bg-muted/50 transition-colors",
                      "min-h-[40px]"
                    )}
                    data-testid={`properties-tray-tab-${id}`}
                  >
                    <Icon className="h-4 w-4" />
                  </TabsTrigger>
                ))}
              </TabsList>
            </div>

            {/* Tab Content - scrollable */}
            <div
              className="flex-1 min-h-0 overflow-y-auto overscroll-contain"
              data-testid="properties-tray-content"
            >
              <div className="p-4">
                <TabsContent value="content" className="m-0" data-testid="properties-tray-tab-content-content">
                  <ContentTab
                    element={element}
                    onPropertyChange={onPropertyChange}
                  />
                </TabsContent>

                <TabsContent value="style" className="m-0" data-testid="properties-tray-tab-content-style">
                  <StyleTab
                    element={element}
                    onPropertyChange={onPropertyChange}
                    onOverrideChange={handleStyleOverrideChange}
                    onResetToGlobal={handleResetToGlobal}
                  />
                </TabsContent>

                <TabsContent value="behavior" className="m-0" data-testid="properties-tray-tab-content-behavior">
                  <BehaviorTab
                    element={element}
                    onPropertyChange={onPropertyChange}
                  />
                </TabsContent>

                <TabsContent value="code" className="m-0" data-testid="properties-tray-tab-content-code">
                  <CodeTab
                    element={element}
                    onPropertyChange={onPropertyChange}
                  />
                </TabsContent>

                <TabsContent value="templates" className="m-0" data-testid="properties-tray-tab-content-templates">
                  <TemplatesTab
                    element={element}
                    onPropertyChange={onPropertyChange}
                  />
                </TabsContent>

                <TabsContent value="ai" className="m-0" data-testid="properties-tray-tab-content-ai">
                  <AITab
                    element={element}
                    onPropertyChange={onPropertyChange}
                  />
                </TabsContent>
              </div>
            </div>
          </Tabs>
        </>
      )}
    </div>
  );
};
