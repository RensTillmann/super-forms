/**
 * Element Visibility Hook
 *
 * Checks if an element is visible in the actual user-visible area,
 * accounting for:
 * - Body scroll (scroll chaining from canvas)
 * - Parent viewport offset (iOS keyboard in iframe)
 * - Properties tray position
 *
 * Uses manual calculations instead of IntersectionObserver because
 * our "visible area" is defined by parent viewport + tray, which
 * doesn't map cleanly to IntersectionObserver's model.
 */
import { useState, useEffect, useRef, useCallback } from 'react';

interface ElementVisibility {
  /** Whether the element is visible in the viewport */
  isVisible: boolean;
  /** Position relative to viewport: 'above' | 'visible' | 'below' */
  position: 'above' | 'visible' | 'below';
  /** Manually trigger a visibility check (useful after layout changes) */
  checkVisibility: () => void;
  /** Current scroll state for debugging */
  debugState: VisibilityState | null;
}

interface VisibilityState {
  bodyScrollTop: number;
  parentViewportOffset: number;
  elementTop: number;
  elementBottom: number;
  visibleTop: number;
  visibleBottom: number;
  trayTop: number | null;
}

interface UseElementVisibilityOptions {
  /** Element ID (uses data-element-id attribute) */
  elementId?: string;
  /** CSS selector for the tray */
  traySelector?: string;
  /** Bottom margin fallback if tray not found (px) */
  bottomMargin?: number;
  /** Top margin to account for header/toolbar (px) */
  topMargin?: number;
  /** Buffer zone for visibility check (px) */
  buffer?: number;
  /** Whether observation is enabled */
  enabled?: boolean;
  /** Enable debug logging */
  debug?: boolean;
}

/**
 * Get body scroll position
 */
const getBodyScrollTop = (): number => {
  return document.documentElement.scrollTop || document.body.scrollTop || 0;
};

/**
 * Get parent's visual viewport offset
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

export function useElementVisibility(options: UseElementVisibilityOptions = {}): ElementVisibility {
  const {
    elementId,
    traySelector = '[data-testid="properties-bottom-tray"]',
    bottomMargin = 300,
    topMargin = 0,
    buffer = 20,
    enabled = true,
    debug = false,
  } = options;

  const [isVisible, setIsVisible] = useState(true);
  const [position, setPosition] = useState<'above' | 'visible' | 'below'>('visible');
  const [debugState, setDebugState] = useState<VisibilityState | null>(null);

  const checkTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * Calculate visibility state
   */
  const calculateVisibility = useCallback((): { visible: boolean; pos: 'above' | 'visible' | 'below'; state: VisibilityState } => {
    if (!elementId) {
      return { visible: true, pos: 'visible', state: {} as VisibilityState };
    }

    const element = document.querySelector(`[data-element-id="${elementId}"]`) as HTMLElement;
    const tray = document.querySelector(traySelector) as HTMLElement;

    if (!element) {
      return { visible: true, pos: 'visible', state: {} as VisibilityState };
    }

    // Get all scroll/offset values
    const bodyScrollTop = getBodyScrollTop();
    const parentViewportOffset = getParentViewportOffset();
    const parentViewportHeight = getParentViewportHeight();

    // Element position (already accounts for scroll in getBoundingClientRect)
    const elementRect = element.getBoundingClientRect();
    const elementTop = elementRect.top;
    const elementHeight = Math.min(elementRect.height, 120); // Cap for large elements
    const elementBottom = elementTop + elementHeight;

    // Tray position (actual rendered position)
    const trayRect = tray?.getBoundingClientRect();
    const trayTop = trayRect?.top ?? null;

    // Calculate visible area
    // Top: parent viewport offset + top margin
    const visibleTop = parentViewportOffset + topMargin;
    // Bottom: either tray's top position OR calculated fallback
    const visibleBottom = trayTop !== null
      ? trayTop
      : parentViewportOffset + parentViewportHeight - bottomMargin;

    const state: VisibilityState = {
      bodyScrollTop,
      parentViewportOffset,
      elementTop,
      elementBottom,
      visibleTop,
      visibleBottom,
      trayTop,
    };

    // Check visibility with buffer
    const isAbove = elementBottom < visibleTop + buffer;
    const isBelow = elementTop > visibleBottom - buffer;
    const visible = !isAbove && !isBelow;

    const pos: 'above' | 'visible' | 'below' = visible ? 'visible' : (isAbove ? 'above' : 'below');

    return { visible, pos, state };
  }, [elementId, traySelector, bottomMargin, topMargin, buffer]);

  /**
   * Perform visibility check and update state
   */
  const checkVisibility = useCallback(() => {
    if (!enabled) {
      setIsVisible(true);
      setPosition('visible');
      return;
    }

    const { visible, pos, state } = calculateVisibility();

    if (debug) {
      console.log('[ElementVisibility] Check:', {
        elementId,
        ...state,
        visible,
        position: pos,
      });
    }

    setIsVisible(visible);
    setPosition(pos);
    setDebugState(state);
  }, [enabled, calculateVisibility, debug, elementId]);

  /**
   * Debounced visibility check
   */
  const debouncedCheck = useCallback(() => {
    if (checkTimeoutRef.current) {
      clearTimeout(checkTimeoutRef.current);
    }
    checkTimeoutRef.current = setTimeout(checkVisibility, 50);
  }, [checkVisibility]);

  /**
   * Setup scroll and resize listeners
   */
  useEffect(() => {
    if (!enabled || !elementId) {
      setIsVisible(true);
      setPosition('visible');
      return;
    }

    // Initial check
    checkVisibility();

    // Listen for scroll on document (catches body scroll)
    document.addEventListener('scroll', debouncedCheck, true); // Use capture to catch all scroll events

    // Listen for resize
    window.addEventListener('resize', debouncedCheck);

    // Listen for visualViewport changes (keyboard)
    const vv = window.parent?.visualViewport || window.visualViewport;
    if (vv) {
      vv.addEventListener('resize', debouncedCheck);
      vv.addEventListener('scroll', debouncedCheck);
    }

    return () => {
      document.removeEventListener('scroll', debouncedCheck, true);
      window.removeEventListener('resize', debouncedCheck);

      if (vv) {
        vv.removeEventListener('resize', debouncedCheck);
        vv.removeEventListener('scroll', debouncedCheck);
      }

      if (checkTimeoutRef.current) {
        clearTimeout(checkTimeoutRef.current);
      }
    };
  }, [enabled, elementId, checkVisibility, debouncedCheck]);

  /**
   * Re-check when element changes
   */
  useEffect(() => {
    if (enabled && elementId) {
      // Small delay to let DOM settle
      const timeout = setTimeout(checkVisibility, 100);
      return () => clearTimeout(timeout);
    }
  }, [elementId, enabled, checkVisibility]);

  return {
    isVisible,
    position,
    checkVisibility,
    debugState,
  };
}
