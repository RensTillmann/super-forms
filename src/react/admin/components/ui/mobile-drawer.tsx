import React, { useEffect, useRef, useState, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '@/lib/utils';

interface MobileDrawerProps {
  /** Whether the drawer is open */
  open: boolean;
  /** Called when drawer should close */
  onClose: () => void;
  /** Fixed height in pixels, or use Visual Viewport API if not provided */
  height?: number;
  /** Accessible title for screen readers */
  title?: string;
  /** Accessible description for screen readers */
  description?: string;
  /** Content to render inside the drawer */
  children: React.ReactNode;
  /** Additional class names for the drawer content */
  className?: string;
  /** Test ID for the drawer */
  'data-testid'?: string;
}

/**
 * Custom mobile drawer component - replaces Vaul
 * Pure Tailwind CSS, no external dependencies
 * Features:
 * - Body scroll lock (iOS-compatible)
 * - Visual Viewport API for keyboard-aware height
 * - Touch swipe-to-close
 * - CSS transitions for smooth animations
 */
export const MobileDrawer: React.FC<MobileDrawerProps> = ({
  open,
  onClose,
  height,
  title,
  description,
  children,
  className,
  'data-testid': testId = 'mobile-drawer',
}) => {
  const drawerRef = useRef<HTMLDivElement>(null);
  const [isVisible, setIsVisible] = useState(false);
  const [isAnimating, setIsAnimating] = useState(false);

  // Touch swipe state
  const touchStartY = useRef<number>(0);
  const touchCurrentY = useRef<number>(0);
  const isDragging = useRef(false);
  const [dragOffset, setDragOffset] = useState(0);

  // Visual Viewport height for keyboard awareness
  const [viewportHeight, setViewportHeight] = useState<number>(
    typeof window !== 'undefined'
      ? (window.visualViewport?.height || window.innerHeight)
      : 0
  );

  // Calculate drawer height (70% of viewport or provided height)
  const drawerHeight = height || Math.round(viewportHeight * 0.7);

  // Handle open/close transitions
  useEffect(() => {
    if (open) {
      setIsVisible(true);
      // Double requestAnimationFrame ensures CSS transitions trigger correctly:
      // 1st RAF: Browser has painted the initial state (translateY 100%)
      // 2nd RAF: Now safe to change state, browser will animate the transition
      // Without this, the browser may batch both states into a single paint
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          setIsAnimating(true);
        });
      });
    } else {
      setIsAnimating(false);
      // Wait for animation to complete before hiding
      const timer = setTimeout(() => {
        setIsVisible(false);
        setDragOffset(0);
      }, 300);
      return () => clearTimeout(timer);
    }
  }, [open]);

  // Body scroll lock + disable scroll behavior
  useEffect(() => {
    if (!open) return;

    const scrollY = window.scrollY;
    const body = document.body;
    const html = document.documentElement;

    // Store original styles
    const originalBodyStyle = body.style.cssText;
    const originalHtmlStyle = html.style.cssText;

    // Lock body scroll - iOS-compatible technique
    // Also disable scroll-behavior and transitions to prevent any movement
    body.style.position = 'fixed';
    body.style.top = `-${scrollY}px`;
    body.style.left = '0';
    body.style.right = '0';
    body.style.overflow = 'hidden';
    body.style.transition = 'none';
    html.style.overflow = 'hidden';
    html.style.scrollBehavior = 'auto';
    html.style.transition = 'none';

    return () => {
      body.style.cssText = originalBodyStyle;
      html.style.cssText = originalHtmlStyle;
      window.scrollTo(0, scrollY);
    };
  }, [open]);

  // Visual Viewport API listener for keyboard-aware height
  // Supported: iOS 13+, Chrome 62+, Firefox 91+, Safari 13+
  // Falls back to window.innerHeight on unsupported browsers (pre-2018)
  useEffect(() => {
    if (!open) return;

    const updateHeight = () => {
      const vv = window.visualViewport;
      setViewportHeight(vv ? vv.height : window.innerHeight);
    };

    updateHeight();
    window.visualViewport?.addEventListener('resize', updateHeight);
    window.visualViewport?.addEventListener('scroll', updateHeight);

    return () => {
      window.visualViewport?.removeEventListener('resize', updateHeight);
      window.visualViewport?.removeEventListener('scroll', updateHeight);
    };
  }, [open]);

  // Touch handlers for swipe-to-close
  const handleTouchStart = useCallback((e: React.TouchEvent) => {
    // Only allow dragging from the handle area (top of drawer)
    const touch = e.touches[0];
    const target = e.target as HTMLElement;

    // Check if touch started on the handle or header area
    if (target.closest('[data-drawer-handle]') || target.closest('[data-drawer-header]')) {
      touchStartY.current = touch.clientY;
      touchCurrentY.current = touch.clientY;
      isDragging.current = true;
    }
  }, []);

  const handleTouchMove = useCallback((e: React.TouchEvent) => {
    if (!isDragging.current) return;

    const touch = e.touches[0];
    touchCurrentY.current = touch.clientY;

    const diff = touchCurrentY.current - touchStartY.current;
    // Only allow dragging down (positive diff)
    if (diff > 0) {
      setDragOffset(diff);
    }
  }, []);

  const handleTouchEnd = useCallback(() => {
    if (!isDragging.current) return;

    isDragging.current = false;
    const diff = touchCurrentY.current - touchStartY.current;

    // If dragged more than 100px down, close the drawer
    if (diff > 100) {
      onClose();
    } else {
      // Snap back
      setDragOffset(0);
    }
  }, [onClose]);

  // Close on backdrop click
  const handleBackdropClick = useCallback((e: React.MouseEvent) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  }, [onClose]);

  // Close on Escape
  useEffect(() => {
    if (!open) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [open, onClose]);

  if (!isVisible) return null;

  return createPortal(
    <div
      className="fixed inset-0 z-50"
      role="dialog"
      aria-modal="true"
      aria-labelledby={title ? `${testId}-title` : undefined}
      aria-describedby={description ? `${testId}-description` : undefined}
      data-testid={testId}
    >
      {/* Backdrop */}
      <div
        className={cn(
          'fixed inset-0 bg-black/40 transition-opacity duration-300',
          isAnimating ? 'opacity-100' : 'opacity-0'
        )}
        onClick={handleBackdropClick}
        data-testid={`${testId}-overlay`}
      />

      {/* Drawer */}
      <div
        ref={drawerRef}
        className={cn(
          'fixed bottom-0 left-0 right-0 bg-background rounded-t-xl flex flex-col overflow-hidden',
          'transition-transform duration-300 ease-out',
          // Disable transition when dragging for immediate feedback
          isDragging.current && 'transition-none',
          className
        )}
        style={{
          height: drawerHeight,
          transform: isAnimating
            ? `translateY(${dragOffset}px)`
            : `translateY(100%)`,
        }}
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
        data-testid={`${testId}-content`}
      >
        {/* Drag handle - touch target for swipe-to-close gesture */}
        <div
          className="flex justify-center pt-2 pb-1 shrink-0 cursor-grab active:cursor-grabbing"
          data-drawer-handle
          data-testid={`${testId}-handle`}
          role="separator"
          aria-orientation="horizontal"
          aria-label="Drag handle - swipe down to close"
        >
          <div className="w-10 h-1 bg-muted-foreground/30 rounded-full" />
        </div>

        {/* Screen reader only title/description */}
        {title && (
          <span id={`${testId}-title`} className="sr-only">
            {title}
          </span>
        )}
        {description && (
          <span id={`${testId}-description`} className="sr-only">
            {description}
          </span>
        )}

        {/* Content */}
        <div className="flex-1 flex flex-col min-h-0 overflow-hidden">
          {children}
        </div>
      </div>
    </div>,
    document.body
  );
};

export default MobileDrawer;
