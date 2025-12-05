import React, { useState, useRef, useEffect } from 'react';
import { ChevronUp, ChevronDown, GripHorizontal } from 'lucide-react';
import { ResizableBottomTrayProps } from '../types/overlay.types';
import { cn } from '../../../../../lib/utils';
import { useWPAdminSidebar } from '../../../../../hooks/useWPAdminSidebar';

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

  // Debug height changes (can be removed in production)
  // useEffect(() => {
  //   console.log(`Height changed to: ${height}, isResizing: ${isResizing}`);
  // }, [height, isResizing]);
  const startY = useRef(0);
  const startHeight = useRef(0);

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (!isResizing) return;
      
      const deltaY = startY.current - e.clientY;
      
      // Calculate maximum allowed height based on viewport
      // For bottom-positioned tray, we need to ensure it doesn't go above a reasonable limit
      const viewportHeight = window.innerHeight;
      const trayBottom = trayRef.current?.getBoundingClientRect().bottom || viewportHeight;
      const availableSpaceFromBottom = viewportHeight - 50; // 50px buffer from viewport bottom
      const maxAllowedHeight = Math.min(maxHeight, availableSpaceFromBottom);
      
      const proposedHeight = startHeight.current + deltaY;
      const newHeight = Math.min(maxAllowedHeight, Math.max(minHeight, proposedHeight));
      
      // Debug logging (can be removed in production)
      // if (Math.abs(deltaY) > 10) {
      //   console.log(`Resize: deltaY=${deltaY}, proposedHeight=${proposedHeight}, newHeight=${newHeight}, minHeight=${minHeight}, maxAllowedHeight=${maxAllowedHeight}`);
      // }
      
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
      // console.log(`Notifying parent of height change: ${height}`);
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
  }, [maxHeight, minHeight, onHeightChange, isResizing]); // Added isResizing dependency

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
        >
          <GripHorizontal size={16} />
        </div>
      )}

      {/* Chevron Collapse Button */}
      <button
        className={cn(
          "absolute -top-4 left-1/2 -translate-x-1/2",
          "w-12 h-4",
          "bg-muted border border-border border-b-0",
          "rounded-t-lg",
          "cursor-pointer",
          "flex items-center justify-center",
          "text-muted-foreground",
          "transition-all duration-150 ease-out",
          "z-[1]",
          "hover:bg-accent hover:border-primary hover:text-primary"
        )}
        onClick={onToggleCollapse}
        aria-label={isCollapsed ? 'Show elements' : 'Hide elements'}
        aria-expanded={!isCollapsed}
      >
        {isCollapsed ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
      </button>
      
      {!isCollapsed && children}
    </div>
  );
};