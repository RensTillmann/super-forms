import React, { useState, useRef, useEffect } from 'react';
import { ChevronUp, ChevronDown, GripHorizontal } from 'lucide-react';
import { ResizableBottomTrayProps } from '../types/overlay.types';
import { cn } from '../../../../../lib/utils';
import { useWPAdminSidebar } from '../../../../../hooks/useWPAdminSidebar';
import { Button } from '../../../../../components/ui/button';

/**
 * Responsive resizable bottom tray component.
 *
 * Used for the elements palette. Works on both desktop and mobile.
 * Desktop: Supports drag-to-resize
 * Mobile: Fixed height with auto-sizing
 */
export const ResizableBottomTray: React.FC<ResizableBottomTrayProps> = ({
  isCollapsed,
  onToggleCollapse,
  children,
  isMobile = false,
  minHeight = 190, // Minimum for header (50px) + elements (100px) + padding + resize handle + margins
  maxHeight = 500, // Max height for flexibility
  defaultHeight = 220, // Default height
  onHeightChange
}) => {
  const [height, setHeight] = useState(defaultHeight);
  const [isResizing, setIsResizing] = useState(false);
  const trayRef = useRef<HTMLDivElement>(null);

  // Get WordPress admin sidebar width for dynamic positioning
  const { width: sidebarWidth } = useWPAdminSidebar();

  const startY = useRef(0);
  const startHeight = useRef(0);

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (!isResizing) return;

      const deltaY = startY.current - e.clientY;

      // Calculate maximum allowed height based on viewport
      // For bottom-positioned tray, we need to ensure it doesn't go above a reasonable limit
      const viewportHeight = window.innerHeight;
      const availableSpaceFromBottom = viewportHeight - 50; // 50px buffer from viewport bottom
      const maxAllowedHeight = Math.min(maxHeight, availableSpaceFromBottom);

      const proposedHeight = startHeight.current + deltaY;
      const newHeight = Math.min(maxAllowedHeight, Math.max(minHeight, proposedHeight));

      setHeight(newHeight);

      // Notify parent of height change
      if (onHeightChange) {
        onHeightChange(newHeight);
      }
    };

    const handleMouseUp = () => {
      setIsResizing(false);
      document.body.style.cursor = '';
      document.body.style.userSelect = '';
    };

    if (isResizing) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
      document.body.style.cursor = 'ns-resize';
      document.body.style.userSelect = 'none';
    }

    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isResizing, maxHeight, minHeight]);

  const handleResizeStart = (e: React.MouseEvent) => {
    e.preventDefault();
    setIsResizing(true);
    startY.current = e.clientY;
    startHeight.current = height;
  };

  const effectiveHeight = isCollapsed ? 40 : height;

  // Notify parent of initial height and when height changes
  useEffect(() => {
    if (!isCollapsed && onHeightChange && !isResizing) {
      onHeightChange(height);
    }
  }, [height, isCollapsed, onHeightChange, isResizing]);

  // Handle window resize to keep tray within viewport bounds
  useEffect(() => {
    const handleWindowResize = () => {
      // Don't interfere if user is actively resizing
      if (isResizing) return;

      if (trayRef.current) {
        const viewportHeight = window.innerHeight;

        // Sanity check: ignore invalid viewport dimensions
        if (viewportHeight < 200) {
          return;
        }

        const availableSpaceFromBottom = viewportHeight - 50; // 50px buffer
        const maxAllowedHeight = Math.min(maxHeight, availableSpaceFromBottom);

        // Get current height from the component state
        setHeight(currentHeight => {
          if (currentHeight > maxAllowedHeight) {
            const newHeight = Math.max(minHeight, maxAllowedHeight);
            console.log(`Window resize: limiting height from ${currentHeight} to ${newHeight}`);
            if (onHeightChange) {
              onHeightChange(newHeight);
            }
            return newHeight;
          }
          return currentHeight;
        });
      }
    };

    window.addEventListener('resize', handleWindowResize);

    return () => {
      window.removeEventListener('resize', handleWindowResize);
    };
  }, [maxHeight, minHeight, onHeightChange, isResizing]);

  return (
    <div
      ref={trayRef}
      className={cn(
        "fixed bottom-0 right-0 z-[50]",
        "bg-white border-t border-border",
        "shadow-[0_-4px_16px_-2px_rgb(0,0,0,0.1)]",
        "transition-[height,left] duration-300 ease-out",
        "min-h-4",
        isMobile && "!h-auto max-h-[50vh]"
      )}
      style={{
        left: sidebarWidth,
        ...(isMobile ? {} : { height: effectiveHeight })
      }}
      data-testid="resizable-bottom-tray"
    >
      {/* Resize Handle */}
      {!isCollapsed && !isMobile && (
        <div
          className={cn(
            "h-4 bg-muted border-b border-border cursor-ns-resize",
            "flex items-center justify-center",
            "transition-colors duration-150 ease-out",
            "text-muted-foreground hover:bg-accent"
          )}
          onMouseDown={handleResizeStart}
          role="separator"
          aria-orientation="horizontal"
          aria-label="Resize tray"
          data-testid="tray-resize-handle"
        >
          <GripHorizontal size={16} />
        </div>
      )}

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
        aria-label={isCollapsed ? 'Show elements' : 'Hide elements'}
        aria-expanded={!isCollapsed}
        data-testid="tray-collapse-button"
      >
        {isCollapsed ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
      </Button>

      {!isCollapsed && children}
    </div>
  );
};
