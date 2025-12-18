import React, { useRef, useState, useMemo, useEffect, useCallback } from 'react';
import { X, Trash2, FileText, Palette, Settings2, Code2, LayoutTemplate, Sparkles } from 'lucide-react';
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
 * Mobile-only Properties Bottom Tray - displays element properties in a bottom tray.
 * Uses the same UI pattern as ResizableBottomTray (elements palette) but with higher z-index
 * to overlay on top of the elements tray.
 *
 * Note: Desktop uses FloatingPanel instead (see ui/desktop/).
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
  const [viewportBottom, setViewportBottom] = useState(0);
  const [visualViewportHeight, setVisualViewportHeight] = useState(window.innerHeight);

  // Swipe-to-close state (using refs for smooth 60fps drag)
  const touchStartY = useRef<number>(0);
  const touchCurrentY = useRef<number>(0);
  const isDragging = useRef(false);
  const dragOffset = useRef<number>(0);

  // Track keyboard state changes for auto-scroll
  const prevViewportBottom = useRef(0);
  const hasScrolledForElement = useRef<string | null>(null);

  // Get WordPress admin sidebar width for dynamic positioning
  const { width: sidebarWidth } = useWPAdminSidebar();

  /**
   * Scroll the selected element into the visible area above the tray.
   * Centers the element in the visible viewport area.
   *
   * IMPORTANT: On mobile when keyboard opens, the parent's visualViewport shifts.
   * getBoundingClientRect() gives iframe coordinates, but the user sees the PARENT's
   * visual viewport. We must account for parentOffset in all visibility calculations.
   *
   * Strategy: Scroll canvas-container if scrollable, otherwise use CSS transform fallback.
   */
  const scrollElementIntoView = useCallback(() => {
    if (!elementId || isCollapsed) return;

    const element = document.querySelector(`[data-element-id="${elementId}"]`) as HTMLElement;
    const canvas = document.querySelector('[data-testid="canvas-container"]') as HTMLElement;
    const tray = trayRef.current;

    if (!element || !canvas || !tray) return;

    // Get parent's visual viewport offset - where the REAL visible area starts
    // When keyboard opens, parentOffset increases (visible area shifts down in iframe coords)
    let parentOffset = 0;
    try {
      if (window.parent?.visualViewport) {
        parentOffset = window.parent.visualViewport.offsetTop;
      }
    } catch { /* cross-origin */ }

    const elementRect = element.getBoundingClientRect();
    const trayRect = tray.getBoundingClientRect();

    // Calculate REAL visible area (what user actually sees)
    // - Real visible TOP = parentOffset (keyboard pushed visible area down)
    // - Real visible BOTTOM = trayRect.top (tray positioned above keyboard via transform)
    const realVisibleTop = parentOffset;
    const realVisibleBottom = trayRect.top;
    const realVisibleHeight = realVisibleBottom - realVisibleTop;

    // Element position in iframe coords
    const elementTop = elementRect.top;
    const elementHeight = Math.min(elementRect.height, 120);
    const elementBottom = elementTop + elementHeight;

    // Check visibility using REAL coordinates (accounting for parent viewport shift)
    const isAboveVisible = elementBottom < realVisibleTop + 20;
    const isBelowVisible = elementTop > realVisibleBottom - 20;
    const isVisible = !isAboveVisible && !isBelowVisible;

    if (isVisible) return;

    // Calculate target: center element in the REAL visible area
    const targetRealPosition = Math.max(40, (realVisibleHeight - elementHeight) / 2);
    const targetIframePosition = realVisibleTop + targetRealPosition;
    const scrollDelta = elementTop - targetIframePosition;

    // Scroll canvas if scrollable, otherwise use CSS transform fallback
    if (canvas.scrollHeight > canvas.clientHeight) {
      canvas.scrollBy({ top: scrollDelta, behavior: 'smooth' });
    } else {
      // Canvas not scrollable - use transform to shift content into view
      // Invert scrollDelta: negative scroll (element above) = positive transform (shift down)
      const transformOffset = -scrollDelta;
      const existingOffset = parseFloat(canvas.dataset.keyboardTransformOffset || '0');
      const newOffset = existingOffset + transformOffset;

      canvas.style.transition = 'transform 300ms ease-out';
      canvas.style.transform = `translateY(${newOffset}px)`;
      canvas.dataset.keyboardTransformOffset = String(newOffset);
    }
  }, [elementId, isCollapsed]);

  // Auto-scroll when element is selected (after tray animation)
  useEffect(() => {
    if (!elementId || isCollapsed) return;

    // Only scroll once per element selection
    if (hasScrolledForElement.current === elementId) return;

    // Delay to let tray animate open (300ms transition + buffer)
    const timeoutId = setTimeout(() => {
      scrollElementIntoView('element-selected');
      hasScrolledForElement.current = elementId;
    }, 400);

    return () => clearTimeout(timeoutId);
  }, [elementId, isCollapsed, scrollElementIntoView]);

  // Auto-scroll when keyboard opens
  // iOS Safari scrolls the page when focusing inputs in fixed elements.
  // We wait for iOS to finish (500ms), then scroll element back into view.
  const keyboardScrollTimeout = useRef<NodeJS.Timeout | null>(null);
  const hasScheduledKeyboardScroll = useRef(false);

  useEffect(() => {
    const delta = viewportBottom - prevViewportBottom.current;

    // Keyboard OPENING (positive delta > 100) - only schedule once
    if (delta > 100 && !hasScheduledKeyboardScroll.current) {
      hasScheduledKeyboardScroll.current = true;

      // Clear any old timeout (safety)
      if (keyboardScrollTimeout.current) {
        clearTimeout(keyboardScrollTimeout.current);
      }

      // Wait for iOS to finish its scroll, then bring element back into view
      keyboardScrollTimeout.current = setTimeout(() => {
        hasScheduledKeyboardScroll.current = false;
        scrollElementIntoView();
      }, 500);
    }

    // Keyboard CLOSED (went back to ~0)
    if (viewportBottom < 50 && prevViewportBottom.current > 100) {
      hasScheduledKeyboardScroll.current = false;
      if (keyboardScrollTimeout.current) {
        clearTimeout(keyboardScrollTimeout.current);
        keyboardScrollTimeout.current = null;
      }

      // Reset any transform offset applied during keyboard-open scroll
      const canvas = document.querySelector('[data-testid="canvas-container"]') as HTMLElement;
      if (canvas && canvas.dataset.keyboardTransformOffset) {
        canvas.style.transition = 'transform 300ms ease-out';
        canvas.style.transform = '';
        delete canvas.dataset.keyboardTransformOffset;
      }
    }

    prevViewportBottom.current = viewportBottom;
  }, [viewportBottom, scrollElementIntoView]);

  // Reset scroll tracking and canvas transform when element changes or tray closes
  useEffect(() => {
    hasScrolledForElement.current = null;

    // Cleanup: reset canvas transform when tray closes or element changes
    return () => {
      const canvas = document.querySelector('[data-testid="canvas-container"]') as HTMLElement;
      if (canvas && canvas.dataset.keyboardTransformOffset) {
        canvas.style.transition = 'transform 300ms ease-out';
        canvas.style.transform = '';
        delete canvas.dataset.keyboardTransformOffset;
      }
    };
  }, [elementId]);

  // Re-center element when focusing different fields in the tray
  // This handles the case where user scrolls manually, then focuses another field
  const focusScrollTimeout = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    const tray = trayRef.current;
    if (!tray || !elementId || isCollapsed) return;

    const handleFocusIn = (e: FocusEvent) => {
      const target = e.target as HTMLElement;
      // Only trigger for focusable form elements
      if (!target.matches('input, textarea, select, [contenteditable="true"]')) return;

      // Clear any pending scroll
      if (focusScrollTimeout.current) {
        clearTimeout(focusScrollTimeout.current);
      }

      // Wait a bit for iOS to do its thing, then re-center if needed
      focusScrollTimeout.current = setTimeout(() => {
        scrollElementIntoView();
      }, 300);
    };

    tray.addEventListener('focusin', handleFocusIn);

    return () => {
      tray.removeEventListener('focusin', handleFocusIn);
      if (focusScrollTimeout.current) {
        clearTimeout(focusScrollTimeout.current);
      }
    };
  }, [elementId, isCollapsed, scrollElementIntoView]);

  // Track visual viewport for mobile keyboard handling using rAF for smooth tracking
  // Using transform instead of bottom for GPU-accelerated positioning
  useEffect(() => {
    let rafId: number;
    let isRunning = true;

    // Get the correct visualViewport - use parent's if we're in an iframe (same-origin)
    // because the iframe extends behind the keyboard and its own visualViewport won't reflect keyboard
    const getVisualViewport = (): VisualViewport | null => {
      try {
        // If in iframe, use parent's visualViewport for accurate keyboard tracking
        if (window.parent !== window && window.parent.visualViewport) {
          return window.parent.visualViewport;
        }
      } catch {
        // Cross-origin, fall back to own viewport
      }
      return window.visualViewport;
    };

    // Get the correct innerHeight (parent's if in iframe)
    const getInnerHeight = (): number => {
      try {
        if (window.parent !== window) {
          return window.parent.innerHeight;
        }
      } catch {
        // Cross-origin
      }
      return window.innerHeight;
    };

    const updatePosition = () => {
      if (!isRunning) return;

      const vv = getVisualViewport();
      const innerHeight = getInnerHeight();

      if (vv) {
        // Proper formula from https://bram.us - accounts for scroll position
        // This is the amount to translate UP (negative Y) to stay above keyboard
        const keyboardOffset = innerHeight - vv.height - vv.offsetTop;
        setViewportBottom(Math.max(0, keyboardOffset));
        setVisualViewportHeight(vv.height);
      }

      // Continuous rAF loop for smooth keyboard animation tracking
      rafId = requestAnimationFrame(updatePosition);
    };

    // Start the animation loop
    updatePosition();

    // Also listen to visualViewport events for immediate response
    const vv = getVisualViewport();
    const handleViewportChange = () => {
      const viewport = getVisualViewport();
      const innerHeight = getInnerHeight();
      if (viewport) {
        const keyboardOffset = innerHeight - viewport.height - viewport.offsetTop;
        setViewportBottom(Math.max(0, keyboardOffset));
        setVisualViewportHeight(viewport.height);
      }
    };

    if (vv) {
      vv.addEventListener('resize', handleViewportChange);
      vv.addEventListener('scroll', handleViewportChange);
    }

    return () => {
      isRunning = false;
      cancelAnimationFrame(rafId);
      const viewport = getVisualViewport();
      if (viewport) {
        viewport.removeEventListener('resize', handleViewportChange);
        viewport.removeEventListener('scroll', handleViewportChange);
      }
    };
  }, []);

  // Touch handlers for swipe-to-close - using direct DOM for smooth 60fps drag
  const handleTouchStart = useCallback((e: React.TouchEvent) => {
    const touch = e.touches[0];
    const target = e.target as HTMLElement;

    // Only allow dragging from the drag handle
    if (target.closest('[data-drag-handle]')) {
      touchStartY.current = touch.clientY;
      touchCurrentY.current = touch.clientY;
      isDragging.current = true;
      dragOffset.current = 0;

      // Disable transitions during drag for smooth movement
      if (trayRef.current) {
        trayRef.current.style.transition = 'none';
      }
    }
  }, []);

  const handleTouchMove = useCallback((e: React.TouchEvent) => {
    if (!isDragging.current) return;

    // Prevent pull-to-refresh
    e.preventDefault();

    const touch = e.touches[0];
    touchCurrentY.current = touch.clientY;

    const diff = touchCurrentY.current - touchStartY.current;
    // Only allow dragging down (positive diff)
    if (diff > 0 && trayRef.current) {
      dragOffset.current = diff;
      // Direct DOM manipulation for smooth 60fps - bypass React
      // Compose with keyboard offset: translate up for keyboard, down for drag
      trayRef.current.style.transform = `translateY(${-viewportBottom + diff}px)`;
    }
  }, [viewportBottom]);

  const handleTouchEnd = useCallback(() => {
    if (!isDragging.current) return;

    isDragging.current = false;
    const diff = touchCurrentY.current - touchStartY.current;

    // Re-enable transitions for snap animation
    if (trayRef.current) {
      trayRef.current.style.transition = 'transform 300ms ease-out, height 300ms ease-out, left 300ms ease-out';
    }

    // If dragged more than 80px down, close the tray
    if (diff > 80) {
      // Animate off screen then close
      if (trayRef.current) {
        trayRef.current.style.transform = 'translateY(100%)';
      }
      // Close after animation
      setTimeout(() => {
        onClose();
        // Reset transform after close - restore keyboard offset
        if (trayRef.current) {
          trayRef.current.style.transform = `translateY(-${viewportBottom}px)`;
        }
      }, 300);
    } else {
      // Snap back to original position with keyboard offset
      dragOffset.current = 0;
      if (trayRef.current) {
        trayRef.current.style.transform = `translateY(-${viewportBottom}px)`;
      }
    }
  }, [onClose, viewportBottom]);

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
          "fixed right-0 z-[60]", // Higher z-index than elements tray (z-50), bottom set via style for keyboard handling
          "bg-background border-t border-border rounded-t-xl",
          "shadow-[0_-4px_16px_-2px_rgb(0,0,0,0.1)]",
          "transition-[height,left,transform] duration-300 ease-out",
          "min-h-4",
          "flex flex-col"
        )}
        style={{
          left: sidebarWidth,
          height: effectiveHeight,
          bottom: 0, // Keep at bottom, use transform for keyboard positioning
          maxHeight: visualViewportHeight * 0.5, // 50% of visual viewport (shrinks with keyboard)
          overscrollBehavior: 'contain', // Prevent scroll chaining / pull-to-refresh
          transform: `translateY(-${viewportBottom}px)`, // GPU-accelerated, more reliable during keyboard animation
        }}
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
        data-testid="properties-bottom-tray"
      >
      {/* Drag handle - swipe down to close */}
      <div
        className="flex justify-center py-2 shrink-0 cursor-grab active:cursor-grabbing"
        style={{ touchAction: 'none' }}
        data-drag-handle
        data-testid="properties-tray-drag-handle"
        role="separator"
        aria-orientation="horizontal"
        aria-label="Drag handle - swipe down to close"
      >
        <div className="w-10 h-1 bg-muted-foreground/30 rounded-full" />
      </div>

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
