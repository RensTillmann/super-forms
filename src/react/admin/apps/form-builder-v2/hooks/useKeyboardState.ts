/**
 * Keyboard State Machine Hook
 *
 * Replaces scattered boolean flags and timeouts with a proper state machine.
 * States: IDLE → OPENING → OPEN → CLOSING → IDLE
 *
 * Benefits:
 * - Single source of truth for keyboard state
 * - Lazy rAF tracking (only runs when needed)
 * - Prevents impossible state transitions
 * - Cleaner cleanup
 */
import { useState, useEffect, useRef, useCallback } from 'react';

type KeyboardState = 'idle' | 'opening' | 'open' | 'closing';

interface KeyboardStateInfo {
  state: KeyboardState;
  /** How much the keyboard is covering (px) */
  offset: number;
  /** Visual viewport height (what user sees) */
  viewportHeight: number;
  /** Whether keyboard is considered "open" (opening or open) */
  isOpen: boolean;
  /** Whether we're in transition (opening or closing) */
  isTransitioning: boolean;
}

/** Info passed to keyboard callbacks for scroll calculations */
interface KeyboardCallbackInfo {
  /** Current keyboard offset in pixels (use for scroll calculations) */
  offset: number;
  /** Visual viewport height */
  viewportHeight: number;
}

interface UseKeyboardStateOptions {
  /** Callback when keyboard STARTS opening (immediate, for early scroll).
   *  Receives offset info so you can pass it to scrollElementIntoView for accurate positioning. */
  onKeyboardOpening?: (info: KeyboardCallbackInfo) => void;
  /** Callback when keyboard finishes opening (stable) */
  onKeyboardOpen?: (info: KeyboardCallbackInfo) => void;
  /** Callback when keyboard finishes closing */
  onKeyboardClose?: () => void;
  /** Whether tracking is enabled (disable when tray is closed) */
  enabled?: boolean;
  /** Enable debug logging */
  debug?: boolean;
}

/**
 * Get parent's inner height (layout viewport - doesn't change with keyboard)
 */
const getParentInnerHeight = (): number => {
  try {
    if (window.parent !== window) {
      return window.parent.innerHeight;
    }
  } catch {
    // Cross-origin
  }
  return window.innerHeight;
};

/**
 * Get parent's visual viewport height (shrinks when keyboard opens)
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

/**
 * Get parent's visual viewport offsetTop (shifts when keyboard opens on mobile)
 * This is how much the visual viewport has scrolled/shifted from the layout viewport top.
 */
const getParentViewportOffsetTop = (): number => {
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
 * Calculate the offset needed for tray positioning
 *
 * Formula: innerHeight - vv.height - vv.offsetTop
 *
 * Why offsetTop matters:
 * - When keyboard opens, the visual viewport shrinks AND shifts
 * - offsetTop = how much the visual viewport shifted down from layout origin
 * - The tray at bottom:0 is at layout bottom
 * - We need to move it up to reach the visual viewport bottom
 * - Visual viewport bottom = vv.offsetTop + vv.height
 * - Distance from layout bottom to visual bottom = innerHeight - (vv.offsetTop + vv.height)
 */
const getKeyboardOffset = (): number => {
  const innerH = getParentInnerHeight();
  const viewportH = getParentViewportHeight();
  const offsetTop = getParentViewportOffsetTop();
  // Correct formula: accounts for visual viewport shift
  return Math.max(0, innerH - viewportH - offsetTop);
};

export function useKeyboardState(options: UseKeyboardStateOptions = {}): KeyboardStateInfo {
  const {
    onKeyboardOpening,
    onKeyboardOpen,
    onKeyboardClose,
    enabled = true,
    debug = false,
  } = options;

  const [state, setState] = useState<KeyboardState>('idle');
  const [offset, setOffset] = useState(0);
  const [viewportHeight, setViewportHeight] = useState(window.innerHeight);

  // Ref to access state without causing effect re-runs
  const stateRef = useRef<KeyboardState>('idle');
  stateRef.current = state;

  // Refs for transition detection
  const lastOffset = useRef(0);
  const stableFrameCount = useRef(0);
  const rafId = useRef<number | null>(null);
  const isTracking = useRef(false);

  // Keyboard detection using viewport height ratio (more stable than offset)
  // When keyboard opens, visualViewport.height shrinks significantly
  const OPEN_RATIO = 0.85;  // Keyboard "open" when vv.height < 85% of innerHeight
  const CLOSE_RATIO = 0.95; // Keyboard "closed" when vv.height > 95% of innerHeight

  // Memoize callbacks to prevent stale closures
  const onOpeningRef = useRef(onKeyboardOpening);
  const onOpenRef = useRef(onKeyboardOpen);
  const onCloseRef = useRef(onKeyboardClose);
  onOpeningRef.current = onKeyboardOpening;
  onOpenRef.current = onKeyboardOpen;
  onCloseRef.current = onKeyboardClose;

  /**
   * Calculate current keyboard offset and viewport info
   * Returns the offset needed for tray positioning (accounts for offsetTop)
   * Also returns height ratio for stable keyboard detection
   */
  const calculateOffset = useCallback((): { offset: number; height: number; heightRatio: number } => {
    const keyboardOffset = getKeyboardOffset();
    const viewportH = getParentViewportHeight();
    const innerH = getParentInnerHeight();
    // Height ratio: how much of layout viewport is visible (1.0 = full, 0.5 = half)
    const heightRatio = innerH > 0 ? viewportH / innerH : 1;

    return {
      offset: keyboardOffset,
      height: viewportH,
      heightRatio,
    };
  }, []);

  /**
   * State machine transition logic
   * Uses height ratio for stable detection (not affected by offset fluctuations)
   */
  const processStateTransition = useCallback((currentOffset: number, heightRatio: number, currentViewportHeight: number) => {
    const offsetDelta = Math.abs(currentOffset - lastOffset.current);

    // If offset is stable (changed < 5px), count frames
    if (offsetDelta < 5) {
      stableFrameCount.current++;
    } else {
      stableFrameCount.current = 0;
      lastOffset.current = currentOffset;
    }

    // Need ~10 stable frames (~166ms at 60fps) to consider animation complete
    const isStable = stableFrameCount.current > 10;

    // Use height ratio for keyboard detection (more stable than offset)
    const keyboardLikelyOpen = heightRatio < OPEN_RATIO;   // vv.height < 85% of innerHeight
    const keyboardLikelyClosed = heightRatio > CLOSE_RATIO; // vv.height > 95% of innerHeight

    setState((prevState) => {
      let nextState = prevState;

      switch (prevState) {
        case 'idle':
          // Transition to opening when viewport shrinks significantly
          if (keyboardLikelyOpen) {
            stableFrameCount.current = 0;
            nextState = 'opening';
            // Fire early callback for immediate scroll - pass offset so caller can scroll accurately
            if (debug) console.log('[KeyboardState] → OPENING, firing onKeyboardOpening with offset:', currentOffset);
            const openingInfo = { offset: currentOffset, viewportHeight: currentViewportHeight };
            setTimeout(() => onOpeningRef.current?.(openingInfo), 0);
          }
          break;

        case 'opening':
          // Transition to open when animation stabilizes AND keyboard still looks open
          if (isStable && keyboardLikelyOpen) {
            // Fire callback on next tick to avoid setState-during-render
            if (debug) console.log('[KeyboardState] → OPEN, firing onKeyboardOpen');
            const openInfo = { offset: currentOffset, viewportHeight: currentViewportHeight };
            setTimeout(() => onOpenRef.current?.(openInfo), 0);
            nextState = 'open';
          }
          // User dismissed keyboard before it fully opened
          else if (keyboardLikelyClosed) {
            stableFrameCount.current = 0;
            nextState = 'closing';
          }
          break;

        case 'open':
          // Transition to closing when viewport expands
          if (!keyboardLikelyOpen) {
            stableFrameCount.current = 0;
            nextState = 'closing';
          }
          break;

        case 'closing':
          // Transition to idle when fully closed and stable
          if (isStable && keyboardLikelyClosed) {
            if (debug) console.log('[KeyboardState] → IDLE, firing onKeyboardClose');
            setTimeout(() => onCloseRef.current?.(), 0);
            nextState = 'idle';
          }
          // User re-opened keyboard
          else if (keyboardLikelyOpen) {
            stableFrameCount.current = 0;
            nextState = 'opening';
          }
          break;
      }

      if (debug && nextState !== prevState) {
        console.log('[KeyboardState] Transition:', prevState, '→', nextState, { offset: currentOffset, stableFrames: stableFrameCount.current });
      }

      return nextState;
    });
  }, [OPEN_RATIO, CLOSE_RATIO, debug]);

  // Ref for debug to avoid stale closure
  const debugRef = useRef(debug);
  debugRef.current = debug;

  /**
   * Start the rAF tracking loop (lazy - only when needed)
   */
  const startTracking = useCallback(() => {
    if (isTracking.current || !enabled) {
      if (debugRef.current) console.log('[KeyboardState] startTracking skipped:', { isTracking: isTracking.current, enabled });
      return;
    }

    if (debugRef.current) console.log('[KeyboardState] Starting rAF tracking');
    isTracking.current = true;

    let frameCount = 0;
    const track = () => {
      if (!isTracking.current) return;

      const { offset: currentOffset, height, heightRatio } = calculateOffset();
      setOffset(currentOffset);
      setViewportHeight(height);
      processStateTransition(currentOffset, heightRatio, height);

      frameCount++;
      // Log every 10 frames to avoid spam
      if (debugRef.current && frameCount % 10 === 0) {
        console.log('[KeyboardState] Frame', frameCount, { offset: currentOffset, stableFrames: stableFrameCount.current });
      }

      rafId.current = requestAnimationFrame(track);
    };

    track();
  }, [enabled, calculateOffset, processStateTransition]);

  /**
   * Stop the rAF tracking loop
   */
  const stopTracking = useCallback(() => {
    isTracking.current = false;
    if (rafId.current) {
      cancelAnimationFrame(rafId.current);
      rafId.current = null;
    }
  }, []);

  /**
   * Main effect: Start/stop tracking based on focus and enabled state
   */
  useEffect(() => {
    if (!enabled) {
      stopTracking();
      // Reset state when disabled
      setState('idle');
      setOffset(0);
      return;
    }

    // Start tracking when an input is focused
    const handleFocusIn = (e: FocusEvent) => {
      const target = e.target as HTMLElement;
      if (target.matches('input, textarea, select, [contenteditable="true"]')) {
        if (debug) console.log('[KeyboardState] FocusIn detected, starting tracking');
        startTracking();
      }
    };

    // Stop tracking when focus leaves inputs (with delay for keyboard close animation)
    const handleFocusOut = (e: FocusEvent) => {
      const relatedTarget = e.relatedTarget as HTMLElement | null;
      // Only stop if focus moved outside of inputs
      if (!relatedTarget?.matches('input, textarea, select, [contenteditable="true"]')) {
        // Wait for keyboard to close before stopping
        setTimeout(() => {
          // Use stateRef to avoid dependency on state
          if (stateRef.current === 'idle') {
            stopTracking();
          }
        }, 500);
      }
    };

    // Also listen to visualViewport events for immediate response when keyboard is open
    // Get parent's visualViewport (same-origin iframe)
    let parentVV: VisualViewport | null = null;
    try {
      if (window.parent !== window && window.parent.visualViewport) {
        parentVV = window.parent.visualViewport;
      }
    } catch {
      // Cross-origin
    }

    const handleViewportChange = () => {
      // Use stateRef to avoid dependency on state
      if (stateRef.current !== 'idle') {
        const { offset: currentOffset, height } = calculateOffset();
        setOffset(currentOffset);
        setViewportHeight(height);
      }
    };

    document.addEventListener('focusin', handleFocusIn);
    document.addEventListener('focusout', handleFocusOut);

    if (parentVV) {
      parentVV.addEventListener('resize', handleViewportChange);
      parentVV.addEventListener('scroll', handleViewportChange);
    }

    return () => {
      stopTracking();
      document.removeEventListener('focusin', handleFocusIn);
      document.removeEventListener('focusout', handleFocusOut);

      if (parentVV) {
        parentVV.removeEventListener('resize', handleViewportChange);
        parentVV.removeEventListener('scroll', handleViewportChange);
      }
    };
  // Note: state is accessed via stateRef to avoid re-running effect on state changes
  }, [enabled, startTracking, stopTracking, calculateOffset, debug]);

  return {
    state,
    offset,
    viewportHeight,
    isOpen: state === 'opening' || state === 'open',
    isTransitioning: state === 'opening' || state === 'closing',
  };
}
