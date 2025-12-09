import React from 'react';
import { X } from 'lucide-react';
import { cn } from '../../../../lib/utils';
import { useIsMobile } from '../../../../hooks/useMediaQuery';
import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerDescription,
  DrawerHeader,
  DrawerTitle,
} from '../../../../components/ui/drawer';
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
 * Right sidebar overlay component using shadcn Drawer.
 *
 * Used for Style and Themes tabs to show content alongside the canvas
 * instead of replacing it entirely.
 *
 * On mobile: renders as bottom drawer
 * On desktop: renders as right drawer
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

  return (
    <Drawer
      open={isOpen}
      onOpenChange={(open) => !open && onClose()}
      direction={isMobile ? 'bottom' : 'right'}
      modal={false}
      shouldScaleBackground={false}
    >
      <DrawerContent
        className={cn(
          // Base styles
          'flex flex-col bg-background relative',
          // Mobile: bottom drawer
          isMobile && [
            'fixed inset-x-0 bottom-0 mt-0 max-h-[85vh] rounded-t-xl',
          ],
          // Desktop: right drawer
          !isMobile && [
            'fixed inset-y-0 right-0 h-full rounded-none rounded-l-none border-l',
            'w-[400px]',
          ],
          className
        )}
        hideHandle={!isMobile}
      >
        {/* Close button - positioned in top-right corner */}
        <DrawerClose asChild>
          <Button
            variant="ghost"
            size="icon"
            className="absolute top-2 right-2 h-7 w-7 z-10"
            aria-label="Close sidebar"
            data-testid="drawer-close-button"
          >
            <X className="w-4 h-4" />
          </Button>
        </DrawerClose>

        {/* Compact header */}
        <DrawerHeader className="px-3 pt-2 pb-2 border-b border-border bg-muted/30 shrink-0 text-left">
          <DrawerTitle className="text-sm font-semibold leading-none pr-8">
            {title}
          </DrawerTitle>
          {subtitle && (
            <DrawerDescription className="text-xs mt-0.5">
              {subtitle}
            </DrawerDescription>
          )}
        </DrawerHeader>

        {/* Content */}
        <div className="flex-1 min-h-0 flex flex-col overflow-hidden">
          {children}
        </div>
      </DrawerContent>
    </Drawer>
  );
}

export default RightSidebar;
