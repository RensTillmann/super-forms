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
 *
 * Always-mounted pattern: drawer is always in DOM but positioned off-screen
 * with transform. This eliminates mounting race conditions that cause flicker.
 *
 * Key: Transitions are disabled on initial mount to prevent "settling" animation.
 *
 * Features:
 * - Always-mounted (no flicker on open)
 * - Transitions disabled on mount (no settling flash)
 * - Tailwind CSS + inline styles for reliable transforms
 * - Body scroll lock (iOS-compatible)
 * - Visual Viewport API for keyboard-aware height
 * - Touch swipe-to-close
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

  // Track if component has mounted - transitions only enabled after mount
  // This prevents the "settling" flash when drawer first renders
  const [mounted, setMounted] = useState(false);

  // Touch swipe state
  const touchStartY = useRef<number>(0);
  const touchCurrentY = useRef<number>(0);
  const isDragging = useRef(false);
  const [dragOffset, setDragOffset] = useState(0);

  // Visual Viewport height for keyboard awareness
  const [viewportHeight, setViewportHeight] = useState<number>(
    typeof window !== 'undefined'
      ? (window.visualViewport?.height || window.innerHeight)
      : 768
  );

  // Keyboard offset - how much to move drawer up to stay above keyboard
  const [keyboardOffset, setKeyboardOffset] = useState<number>(0);

  // Calculate drawer height (70% of viewport or provided height)
  const drawerHeight = height || Math.round(viewportHeight * 0.7);

  // Enable transitions after mount (after first paint)
  useEffect(() => {
    // Use double RAF to ensure first paint has definitely happened
    const raf1 = requestAnimationFrame(() => {
      const raf2 = requestAnimationFrame(() => {
        setMounted(true);
      });
      return () => cancelAnimationFrame(raf2);
    });
    return () => cancelAnimationFrame(raf1);
  }, []);

  // iOS-compatible body scroll lock
  // Uses position:fixed technique to prevent visual viewport shift when keyboard opens
  useEffect(() => {
    if (!open) return;

    const body = document.body;
    const scrollY = window.scrollY;

    // Store original styles
    const originalPosition = body.style.position;
    const originalTop = body.style.top;
    const originalLeft = body.style.left;
    const originalRight = body.style.right;
    const originalWidth = body.style.width;
    const originalOverflow = body.style.overflow;

    // Fix body in place at current scroll position
    body.style.position = 'fixed';
    body.style.top = `-${scrollY}px`;
    body.style.left = '0';
    body.style.right = '0';
    body.style.width = '100%';
    body.style.overflow = 'hidden';

    return () => {
      // Restore original styles
      body.style.position = originalPosition;
      body.style.top = originalTop;
      body.style.left = originalLeft;
      body.style.right = originalRight;
      body.style.width = originalWidth;
      body.style.overflow = originalOverflow;

      // Restore scroll position
      window.scrollTo(0, scrollY);
    };
  }, [open]);

  // Visual Viewport API listener for keyboard-aware height and position
  // Debounced to prevent jitter from rapid resize events (e.g., keyboard animation,
  // mobile keyboard accessory bar rendering with slight delay)
  useEffect(() => {
    let timeoutId: ReturnType<typeof setTimeout> | null = null;

    const updateViewport = () => {
      // Clear any pending update (debounce reset)
      if (timeoutId) clearTimeout(timeoutId);

      // Wait 300ms for resize events to settle before updating
      // This handles keyboard animation + delayed accessory bar rendering
      timeoutId = setTimeout(() => {
        const vv = window.visualViewport;
        if (vv) {
          setViewportHeight(vv.height);
          // Calculate how much to move drawer up to stay above keyboard
          // Gap = window bottom - visual viewport bottom
          const offset = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
          setKeyboardOffset(offset);
        } else {
          setViewportHeight(window.innerHeight);
          setKeyboardOffset(0);
        }
      }, 300);
    };

    // Set initial values immediately (no debounce needed on mount)
    const vv = window.visualViewport;
    if (vv) {
      setViewportHeight(vv.height);
      const offset = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
      setKeyboardOffset(offset);
    } else {
      setViewportHeight(window.innerHeight);
    }

    window.visualViewport?.addEventListener('resize', updateViewport);
    window.visualViewport?.addEventListener('scroll', updateViewport);

    return () => {
      if (timeoutId) clearTimeout(timeoutId);
      window.visualViewport?.removeEventListener('resize', updateViewport);
      window.visualViewport?.removeEventListener('scroll', updateViewport);
    };
  }, []);

  // Reset drag offset when closing
  useEffect(() => {
    if (!open) {
      setDragOffset(0);
    }
  }, [open]);

  // Touch handlers for swipe-to-close
  const handleTouchStart = useCallback((e: React.TouchEvent) => {
    const touch = e.touches[0];
    const target = e.target as HTMLElement;

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
    if (diff > 0) {
      setDragOffset(diff);
    }
  }, []);

  const handleTouchEnd = useCallback(() => {
    if (!isDragging.current) return;

    isDragging.current = false;
    const diff = touchCurrentY.current - touchStartY.current;

    if (diff > 100) {
      onClose();
    } else {
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

  // Calculate transform based on open state, keyboard offset, and drag
  const getTransform = () => {
    // When closed, slide drawer completely off-screen
    if (!open) {
      return 'translateY(100%)';
    }

    // When open, move drawer up by keyboard offset to stay above keyboard
    // Negative value moves up, positive (drag) moves down
    let yOffset = -keyboardOffset;

    if (dragOffset > 0) {
      yOffset += dragOffset;
    }

    return yOffset !== 0 ? `translateY(${yOffset}px)` : 'translateY(0)';
  };

  // Always render - drawer is positioned off-screen when closed
  return createPortal(
    <div
      className={cn(
        'fixed inset-0 z-50',
        // Hide from pointer events and screen readers when closed
        open ? 'pointer-events-auto' : 'pointer-events-none'
      )}
      role="dialog"
      aria-modal="true"
      aria-hidden={!open}
      aria-labelledby={title ? `${testId}-title` : undefined}
      aria-describedby={description ? `${testId}-description` : undefined}
      data-testid={testId}
    >
      {/* Backdrop - use inline style for opacity to avoid transition on mount */}
      <div
        className={cn(
          'fixed inset-0 bg-black/40',
          // Only enable transition after mounted
          mounted && 'transition-opacity duration-300 ease-out'
        )}
        style={{ opacity: open ? 1 : 0 }}
        onClick={handleBackdropClick}
        data-testid={`${testId}-overlay`}
      />

      {/* Drawer - use inline style for transform to ensure reliable positioning */}
      {/* DEBUG: Red border on drawer container */}
      <div
        ref={drawerRef}
        className={cn(
          'fixed bottom-0 left-0 right-0 bg-background rounded-t-xl flex flex-col overflow-hidden',
          // Only enable transition after mounted, and not while dragging
          mounted && !isDragging.current && 'transition-transform duration-300 ease-out',
          'border-2 border-red-500', // DEBUG
          className
        )}
        style={{
          height: drawerHeight,
          transform: getTransform(),
        }}
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
        data-testid={`${testId}-content`}
      >
        {/* Drag handle */}
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
        {/* DEBUG: Green border on content wrapper */}
        <div className="flex-1 flex flex-col min-h-0 overflow-hidden border-2 border-green-500">
          {children}
        </div>
      </div>
    </div>,
    document.body
  );
};

export default MobileDrawer;
