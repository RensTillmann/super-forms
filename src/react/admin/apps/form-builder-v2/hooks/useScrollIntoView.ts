/**
 * Unified Scroll Into View Hook
 *
 * Handles scrolling elements into view accounting for:
 * - Canvas container scroll
 * - Body/document scroll (scroll chaining)
 * - Parent viewport offset (iOS keyboard in iframe)
 * - Properties tray position
 *
 * Strategy:
 * 1. First reset body scroll to 0 (eliminate scroll chaining complexity)
 * 2. Then scroll canvas to center element
 * 3. Use transform fallback if canvas isn't scrollable
 */
import { useRef, useCallback, useEffect, useMemo } from 'react';

interface ScrollIntoViewOptions {
  /** CSS selector for the canvas container */
  canvasSelector?: string;
  /** CSS selector for the tray (to get its actual position) */
  traySelector?: string;
  /** Bottom offset to account for tray (px) - fallback if tray not found */
  bottomOffset?: number;
  /** Top offset to account for headers (px) */
  topOffset?: number;
  /** Animation duration (ms) - used for transition cleanup */
  animationDuration?: number;
  /** Enable debug logging */
  debug?: boolean;
}

interface ScrollIntoViewResult {
  /**
   * Scroll an element into the visible area
   * @param elementId - The element's data-element-id
   * @param predictedKeyboardOffset - Optional: Use this offset for visible area calculation
   *   instead of reading visualViewport.offsetTop. Useful when called during keyboard
   *   opening animation, before offsetTop has caught up to the actual keyboard height.
   */
  scrollElementIntoView: (elementId: string, predictedKeyboardOffset?: number) => void;
  /** Reset any applied transforms and body scroll */
  resetTransform: () => void;
  /** Reset body scroll to 0 */
  resetBodyScroll: () => void;
  /** Check if transform is currently applied */
  hasTransform: boolean;
}

interface ScrollState {
  bodyScrollTop: number;
  canvasScrollTop: number;
  parentViewportOffset: number;
  elementRect: DOMRect | null;
  trayRect: DOMRect | null;
  canvasRect: DOMRect | null;
  visibleTop: number;
  visibleBottom: number;
  visibleHeight: number;
}

/**
 * Get body scroll position (works across browsers)
 */
const getBodyScrollTop = (): number => {
  return document.documentElement.scrollTop || document.body.scrollTop || 0;
};

/**
 * Set body scroll position
 */
const setBodyScrollTop = (value: number, smooth = true): void => {
  if (smooth) {
    window.scrollTo({ top: value, behavior: 'smooth' });
  } else {
    document.documentElement.scrollTop = value;
    document.body.scrollTop = value;
  }
};

/**
 * Get parent's visual viewport offset (where visible area starts in iframe coords)
 */
const getParentViewportOffset = (): number => {
  try {
    if (window.parent !== window && window.parent.visualViewport) {
      return window.parent.visualViewport.offsetTop;
    }
  } catch {
    // Cross-origin
  }
  return 0;
};

/**
 * Get parent's visual viewport height
 */
const getParentViewportHeight = (): number => {
  try {
    if (window.parent !== window && window.parent.visualViewport) {
      return window.parent.visualViewport.height;
    }
  } catch {
    // Cross-origin
  }
  return window.innerHeight;
};

export function useScrollIntoView(options: ScrollIntoViewOptions = {}): ScrollIntoViewResult {
  const {
    canvasSelector = '[data-testid="canvas-container"]',
    traySelector = '[data-testid="properties-bottom-tray"]',
    bottomOffset = 300,
    topOffset = 0,
    animationDuration = 300,
    debug = true, // Enable by default for testing
  } = options;

  const hasTransformRef = useRef(false);
  const cleanupTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const transitionCleanupRef = useRef<(() => void) | null>(null);

  // Guard against multiple rapid calls that would compound transforms
  const lastScrollTimeRef = useRef<number>(0);
  const SCROLL_DEBOUNCE_MS = 200; // Minimum time between scroll calls

  /**
   * Get current scroll state for debugging and calculations
   * @param elementId - Element to check
   * @param predictedKeyboardOffset - Optional: predicted keyboard offset when called during
   *   keyboard opening animation (before visualViewport.offsetTop catches up)
   */
  const getScrollState = useCallback((elementId: string, predictedKeyboardOffset?: number): ScrollState => {
    const element = document.querySelector(`[data-element-id="${elementId}"]`) as HTMLElement;
    const canvas = document.querySelector(canvasSelector) as HTMLElement;
    const tray = document.querySelector(traySelector) as HTMLElement;

    const bodyScrollTop = getBodyScrollTop();
    const canvasScrollTop = canvas?.scrollTop || 0;
    const actualParentViewportOffset = getParentViewportOffset();
    const parentViewportHeight = getParentViewportHeight();

    // Use predicted offset if provided (during keyboard opening animation)
    // Fall back to actual offset for normal operations
    const effectiveViewportOffset = predictedKeyboardOffset !== undefined
      ? predictedKeyboardOffset
      : actualParentViewportOffset;

    const elementRect = element?.getBoundingClientRect() || null;
    const trayRect = tray?.getBoundingClientRect() || null;
    const canvasRect = canvas?.getBoundingClientRect() || null;

    // Calculate visible area IN IFRAME COORDS
    // When keyboard is open, the visual viewport shifts down (parentViewportOffset > 0)
    // Elements above parentViewportOffset are not visible to the user
    //
    // visibleTop: where the visual viewport starts (accounts for keyboard shift)
    // visibleBottom: where the tray starts (elements below are obscured by tray)
    //
    // When using predictedKeyboardOffset:
    // - We predict where the visible area WILL BE after keyboard animation
    // - This allows us to scroll immediately without waiting for offsetTop to catch up
    const visibleTop = effectiveViewportOffset + topOffset;
    const visibleBottom = trayRect
      ? trayRect.top  // Use actual tray position (includes transform)
      : effectiveViewportOffset + parentViewportHeight - bottomOffset;
    const visibleHeight = visibleBottom - visibleTop;

    return {
      bodyScrollTop,
      canvasScrollTop,
      parentViewportOffset: effectiveViewportOffset,
      elementRect,
      trayRect,
      canvasRect,
      visibleTop,
      visibleBottom,
      visibleHeight,
    };
  }, [canvasSelector, traySelector, topOffset, bottomOffset]);

  /**
   * Log scroll state for debugging
   */
  const logScrollState = useCallback((state: ScrollState, label: string) => {
    if (!debug) return;

    console.group(`[ScrollIntoView] ${label}`);
    console.log('Body scroll:', state.bodyScrollTop);
    console.log('Canvas scroll:', state.canvasScrollTop);
    console.log('Parent viewport offset:', state.parentViewportOffset);
    console.log('Element rect:', state.elementRect ? {
      top: state.elementRect.top,
      bottom: state.elementRect.bottom,
      height: state.elementRect.height,
    } : null);
    console.log('Tray rect:', state.trayRect ? {
      top: state.trayRect.top,
      bottom: state.trayRect.bottom,
    } : null);
    console.log('Canvas rect:', state.canvasRect ? {
      top: state.canvasRect.top,
      bottom: state.canvasRect.bottom,
      scrollHeight: (document.querySelector(canvasSelector) as HTMLElement)?.scrollHeight,
    } : null);
    console.log('Visible area:', {
      top: state.visibleTop,
      bottom: state.visibleBottom,
      height: state.visibleHeight,
    });
    if (state.elementRect) {
      const elementInVisible = state.elementRect.top - state.visibleTop;
      console.log('Element position in visible area:', elementInVisible);
      console.log('Is visible:', elementInVisible >= 0 && state.elementRect.bottom <= state.visibleBottom);
    }
    console.groupEnd();
  }, [debug, canvasSelector]);

  /**
   * Get the canvas element
   */
  const getCanvas = useCallback((): HTMLElement | null => {
    return document.querySelector(canvasSelector) as HTMLElement;
  }, [canvasSelector]);

  /**
   * Reset body scroll to 0
   */
  const resetBodyScroll = useCallback((smooth = true) => {
    const currentScroll = getBodyScrollTop();
    if (currentScroll > 0) {
      if (debug) console.log('[ScrollIntoView] Resetting body scroll from', currentScroll, 'to 0');
      setBodyScrollTop(0, smooth);
    }
  }, [debug]);

  /**
   * Set canvas scroll transform via CSS custom property on :root
   * Using CSS variables on :root persists across React re-renders
   * (React doesn't control :root styles, so they won't be overwritten)
   */
  const setCanvasTransform = useCallback((offset: number) => {
    document.documentElement.style.setProperty('--canvas-scroll-offset', `${offset}px`);
    if (debug) console.log('[ScrollIntoView] Set CSS var --canvas-scroll-offset:', offset);
  }, [debug]);

  /**
   * Reset canvas transform
   */
  const resetTransform = useCallback(() => {
    // Also reset body scroll
    resetBodyScroll(false);

    // Remove existing transition listener if any
    if (transitionCleanupRef.current) {
      transitionCleanupRef.current();
      transitionCleanupRef.current = null;
    }

    const currentOffset = document.documentElement.style.getPropertyValue('--canvas-scroll-offset');
    if (currentOffset && currentOffset !== '0px') {
      // Animate back to 0 via CSS transition (defined in CSS)
      setCanvasTransform(0);
      hasTransformRef.current = false;

      // Cleanup after animation
      cleanupTimeoutRef.current = setTimeout(() => {
        // Optionally remove the property entirely after animation
        // document.documentElement.style.removeProperty('--canvas-scroll-offset');
      }, animationDuration + 100);
    }
  }, [resetBodyScroll, animationDuration, setCanvasTransform]);

  /**
   * Scroll element into view - coordinated strategy
   * @param elementId - The element's data-element-id
   * @param predictedKeyboardOffset - Optional: Use this offset for visible area calculation.
   *   Pass keyboard.offset from useKeyboardState when calling during onKeyboardOpening
   *   to scroll immediately without waiting for visualViewport.offsetTop to catch up.
   */
  const scrollElementIntoView = useCallback((elementId: string, predictedKeyboardOffset?: number) => {
    // Debounce guard: prevent compound transforms from multiple rapid calls
    const now = Date.now();
    if (now - lastScrollTimeRef.current < SCROLL_DEBOUNCE_MS) {
      if (debug) console.log('[ScrollIntoView] Skipping - too soon after last scroll');
      return;
    }
    lastScrollTimeRef.current = now;

    const element = document.querySelector(`[data-element-id="${elementId}"]`) as HTMLElement;
    const canvas = getCanvas();

    if (!element || !canvas) {
      if (debug) console.warn('[ScrollIntoView] Element or canvas not found');
      return;
    }

    // Get initial state (use predicted offset if provided for early scroll during keyboard animation)
    const initialState = getScrollState(elementId, predictedKeyboardOffset);
    if (debug && predictedKeyboardOffset !== undefined) {
      console.log('[ScrollIntoView] Using predicted keyboard offset:', predictedKeyboardOffset);
    }
    logScrollState(initialState, 'Initial State');

    // Step 1: Reset body scroll if it's not 0
    // This simplifies our calculations - we only need to manage canvas scroll
    if (initialState.bodyScrollTop > 0) {
      if (debug) console.log('[ScrollIntoView] Step 1: Resetting body scroll from', initialState.bodyScrollTop);
      setBodyScrollTop(0, false); // Instant reset

      // After resetting body scroll, element positions change.
      // The element will appear LOWER in the viewport by bodyScrollTop amount.
      // We need to recalculate and retry after DOM updates.
      requestAnimationFrame(() => {
        // Recursive call after body scroll reset (preserve predicted offset)
        scrollElementIntoView(elementId, predictedKeyboardOffset);
      });
      return; // Exit and let the recursive call handle the rest
    }

    // Step 2: Calculate where element is now and where we want it
    // Re-get element rect after potential body scroll reset
    const elementRect = element.getBoundingClientRect();
    const elementTop = elementRect.top;
    const elementHeight = Math.min(elementRect.height, 120); // Cap for large elements
    const elementBottom = elementTop + elementHeight;

    const { visibleTop, visibleBottom, visibleHeight } = initialState;

    // Check if element is visible (with 20px buffer)
    const isAboveVisible = elementBottom < visibleTop + 20;
    const isBelowVisible = elementTop > visibleBottom - 20;
    const isVisible = !isAboveVisible && !isBelowVisible;

    if (debug) {
      console.log('[ScrollIntoView] Visibility check:', {
        elementTop,
        elementBottom,
        visibleTop,
        visibleBottom,
        isAboveVisible,
        isBelowVisible,
        isVisible,
      });
    }

    if (isVisible) {
      if (debug) console.log('[ScrollIntoView] Element is already visible');
      return;
    }

    // Step 3: Calculate scroll delta to center element
    const targetPosition = visibleTop + Math.max(40, (visibleHeight - elementHeight) / 2);
    const scrollDelta = elementTop - targetPosition;

    if (debug) {
      console.log('[ScrollIntoView] Scroll calculation:', {
        targetPosition,
        scrollDelta,
        direction: scrollDelta > 0 ? 'down (element below target)' : 'up (element above target)',
      });
    }

    // Step 4: Determine scroll strategy
    const canvasCanScroll = canvas.scrollHeight > canvas.clientHeight;
    const canvasScrollRoom = canvasCanScroll ? {
      up: canvas.scrollTop,
      down: canvas.scrollHeight - canvas.clientHeight - canvas.scrollTop,
    } : { up: 0, down: 0 };

    if (debug) {
      console.log('[ScrollIntoView] Canvas scroll capacity:', {
        canScroll: canvasCanScroll,
        currentScrollTop: canvas.scrollTop,
        maxScroll: canvas.scrollHeight - canvas.clientHeight,
        roomUp: canvasScrollRoom.up,
        roomDown: canvasScrollRoom.down,
      });
    }

    // Step 5: Execute scroll/transform
    // Strategy: Try canvas scroll first, use TRANSFORM for any remainder
    // Transform is needed when element is above visible area and canvas can't scroll up

    let remainder = scrollDelta;

    if (canvasCanScroll) {
      const actualScroll = scrollDelta > 0
        ? Math.min(scrollDelta, canvasScrollRoom.down)
        : Math.max(scrollDelta, -canvasScrollRoom.up);

      if (Math.abs(actualScroll) > 0) {
        if (debug) console.log('[ScrollIntoView] Scrolling canvas by:', actualScroll);
        canvas.scrollBy({ top: actualScroll, behavior: 'smooth' });
      }

      remainder = scrollDelta - actualScroll;
    }

    // Use TRANSFORM for remainder (handles element above visible area when at scrollTop=0)
    // Uses CSS custom property on :root to persist across React re-renders
    if (Math.abs(remainder) > 10) {
      if (debug) console.log('[ScrollIntoView] Using transform for remainder:', remainder);

      // Get existing offset from CSS variable (persists across re-renders)
      const existingOffsetStr = document.documentElement.style.getPropertyValue('--canvas-scroll-offset');
      const existingOffset = existingOffsetStr ? parseFloat(existingOffsetStr) : 0;
      const newOffset = existingOffset - remainder;

      if (debug) console.log('[ScrollIntoView] Transform:', { existing: existingOffset, new: newOffset });

      // Set transform via CSS custom property (survives React re-renders)
      setCanvasTransform(newOffset);
      hasTransformRef.current = true;
    } else if (debug && canvasCanScroll) {
      console.log('[ScrollIntoView] Scroll was sufficient, no transform needed');
    }

    // Log final state after scroll
    setTimeout(() => {
      const finalState = getScrollState(elementId);
      logScrollState(finalState, 'Final State (after scroll)');
    }, animationDuration + 50);
  }, [
    getCanvas,
    getScrollState,
    logScrollState,
    setCanvasTransform,
    animationDuration,
    debug,
  ]);

  /**
   * Cleanup on unmount
   */
  useEffect(() => {
    return () => {
      if (cleanupTimeoutRef.current) {
        clearTimeout(cleanupTimeoutRef.current);
      }

      if (transitionCleanupRef.current) {
        transitionCleanupRef.current();
      }

      // Clear the CSS custom property on unmount
      document.documentElement.style.removeProperty('--canvas-scroll-offset');
    };
  }, []);

  // Memoize return object to prevent cleanup effects in consumers from
  // triggering on every render (object reference must stay stable)
  return useMemo(() => ({
    scrollElementIntoView,
    resetTransform,
    resetBodyScroll,
    hasTransform: hasTransformRef.current,
  }), [scrollElementIntoView, resetTransform, resetBodyScroll]);
}
