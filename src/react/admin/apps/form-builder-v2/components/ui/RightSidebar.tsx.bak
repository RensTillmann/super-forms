import React, { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';
import { cn } from '../../../../lib/utils';
import { useIsMobile } from '../../../../hooks/useMediaQuery';
import { MobileDrawer } from '../../../../components/ui/mobile-drawer';
import { Button } from '../../../../components/ui/button';

interface RightSidebarProps {
  /** Whether the sidebar is open */
  isOpen: boolean;
  /** Called when sidebar should close */
  onClose: () => void;
  /** Sidebar title */
  title: string;
  /** Optional subtitle/description */
  subtitle?: string;
  /** Content to render */
  children: React.ReactNode;
  /** Width of the sidebar (desktop only) */
  width?: number;
  /** Additional class names */
  className?: string;
}

/**
 * Right sidebar overlay component.
 *
 * Used for Style and Themes tabs to show content alongside the canvas
 * instead of replacing it entirely.
 *
 * On mobile: renders as bottom drawer using MobileDrawer
 * On desktop: renders as right slide-in panel
 */
export function RightSidebar({
  isOpen,
  onClose,
  title,
  subtitle,
  children,
  width = 400,
  className,
}: RightSidebarProps) {
  const isMobile = useIsMobile();
  const [isVisible, setIsVisible] = useState(false);
  const [isAnimating, setIsAnimating] = useState(false);

  // Handle open/close transitions for desktop
  useEffect(() => {
    if (!isMobile) {
      if (isOpen) {
        setIsVisible(true);
        requestAnimationFrame(() => {
          requestAnimationFrame(() => {
            setIsAnimating(true);
          });
        });
      } else {
        setIsAnimating(false);
        const timer = setTimeout(() => {
          setIsVisible(false);
        }, 300);
        return () => clearTimeout(timer);
      }
    }
  }, [isOpen, isMobile]);

  // Close on Escape (desktop)
  useEffect(() => {
    if (!isOpen || isMobile) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose, isMobile]);

  // Mobile: Use MobileDrawer
  if (isMobile) {
    return (
      <MobileDrawer
        open={isOpen}
        onClose={onClose}
        title={title}
        description={subtitle}
        data-testid="right-sidebar-drawer"
      >
        {/* Compact header */}
        <div className="px-3 pt-2 pb-2 border-b border-border bg-muted/30 shrink-0" data-drawer-header>
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-sm font-semibold leading-none">{title}</h2>
              {subtitle && (
                <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>
              )}
            </div>
            <Button
              variant="ghost"
              size="icon"
              className="h-7 w-7"
              onClick={onClose}
              aria-label="Close sidebar"
              data-testid="drawer-close-button"
            >
              <X className="w-4 h-4" />
            </Button>
          </div>
        </div>

        {/* Content */}
        <div className="flex-1 min-h-0 flex flex-col overflow-hidden">
          {children}
        </div>
      </MobileDrawer>
    );
  }

  // Desktop: Right slide-in panel
  if (!isVisible) return null;

  return createPortal(
    <div
      className="fixed inset-0 z-50"
      role="dialog"
      aria-modal="true"
      aria-labelledby="right-sidebar-title"
      data-testid="right-sidebar"
    >
      {/* Backdrop - click to close */}
      <div
        className={cn(
          'fixed inset-0 bg-black/20 transition-opacity duration-300',
          isAnimating ? 'opacity-100' : 'opacity-0'
        )}
        onClick={onClose}
        data-testid="right-sidebar-backdrop"
      />

      {/* Panel */}
      <div
        className={cn(
          'fixed inset-y-0 right-0 h-full flex flex-col bg-background border-l border-border shadow-xl',
          'transition-transform duration-300 ease-out',
          isAnimating ? 'translate-x-0' : 'translate-x-full',
          className
        )}
        style={{ width }}
        data-testid="right-sidebar-panel"
      >
        {/* Close button - positioned in top-right corner */}
        <Button
          variant="ghost"
          size="icon"
          className="absolute top-2 right-2 h-7 w-7 z-10"
          aria-label="Close sidebar"
          onClick={onClose}
          data-testid="drawer-close-button"
        >
          <X className="w-4 h-4" />
        </Button>

        {/* Compact header */}
        <div className="px-3 pt-2 pb-2 border-b border-border bg-muted/30 shrink-0">
          <h2 id="right-sidebar-title" className="text-sm font-semibold leading-none pr-8">
            {title}
          </h2>
          {subtitle && (
            <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>
          )}
        </div>

        {/* Content */}
        <div className="flex-1 min-h-0 flex flex-col overflow-hidden">
          {children}
        </div>
      </div>
    </div>,
    document.body
  );
}

export default RightSidebar;
