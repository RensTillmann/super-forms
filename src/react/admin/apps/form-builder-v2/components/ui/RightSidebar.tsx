import React, { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '../../../../lib/utils';

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
  /** Width of the sidebar */
  width?: number;
  /** Additional class names */
  className?: string;
}

/**
 * Right sidebar overlay component.
 *
 * Used for Style and Themes tabs to show content alongside the canvas
 * instead of replacing it entirely.
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
  // Handle Escape key to close sidebar
  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div
      className={cn(
        'flex flex-col bg-background border-l border-border h-full',
        'shadow-[-4px_0_16px_rgba(0,0,0,0.08)]',
        'animate-in slide-in-from-right duration-200',
        className
      )}
      style={{ width: `${width}px`, minWidth: `${width}px` }}
    >
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/30 shrink-0">
        <div>
          <h3 className="font-semibold text-sm">{title}</h3>
          {subtitle && (
            <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>
          )}
        </div>
        <button
          onClick={onClose}
          className="p-1.5 rounded-md hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
          aria-label="Close sidebar"
        >
          <X className="w-4 h-4" />
        </button>
      </div>

      {/* Content - flex container for proper scrolling */}
      <div className="flex-1 min-h-0 flex flex-col">
        {children}
      </div>
    </div>
  );
}

export default RightSidebar;
