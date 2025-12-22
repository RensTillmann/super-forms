import React, { useEffect, useRef, useState } from 'react';

interface DebugValues {
  innerHeight: number;
  vvHeight: number;
  vvOffsetTop: number;
  keyboardOffsetWithOffsetTop: number; // Correct formula
  keyboardOffsetWithoutOffsetTop: number; // Current buggy formula
  difference: number;
}

/**
 * Debug component to visualize viewport behavior when mobile keyboard opens.
 *
 * Shows numerical values for:
 * - innerHeight (layout viewport)
 * - visualViewport.height
 * - visualViewport.offsetTop
 * - Calculated keyboard offsets (with and without offsetTop)
 *
 * This helps identify the formula discrepancy between ViewportDebugIndicators
 * and useKeyboardState.
 */
export const ViewportDebugIndicators: React.FC = () => {
  const absoluteRef = useRef<HTMLDivElement>(null);
  const [viewportBottom, setViewportBottom] = useState(0);
  const [visualViewportHeight, setVisualViewportHeight] = useState(window.innerHeight);
  const [debugValues, setDebugValues] = useState<DebugValues>({
    innerHeight: 0,
    vvHeight: 0,
    vvOffsetTop: 0,
    keyboardOffsetWithOffsetTop: 0,
    keyboardOffsetWithoutOffsetTop: 0,
    difference: 0,
  });

  useEffect(() => {
    let rafId: number;
    let isRunning = true;

    // Get the correct visualViewport - use parent's if we're in an iframe (same-origin)
    // because the iframe extends behind the keyboard and its own visualViewport won't reflect keyboard
    const getVisualViewport = (): VisualViewport | null => {
      try {
        // If in iframe, use parent's visualViewport for accurate keyboard tracking
        if (window.parent !== window && window.parent.visualViewport) {
          return window.parent.visualViewport;
        }
      } catch {
        // Cross-origin, fall back to own viewport
      }
      return window.visualViewport;
    };

    // Get the correct innerHeight (parent's if in iframe)
    const getInnerHeight = (): number => {
      try {
        if (window.parent !== window) {
          return window.parent.innerHeight;
        }
      } catch {
        // Cross-origin
      }
      return window.innerHeight;
    };

    // Update position using requestAnimationFrame for smooth continuous tracking
    // Using transform instead of bottom for GPU-accelerated positioning
    const updatePosition = () => {
      if (!isRunning) return;

      const vv = getVisualViewport();
      const innerHeight = getInnerHeight();

      if (vv) {
        // Proper formula from https://bram.us - accounts for scroll position
        // This is the amount to translate UP (negative Y) to stay above keyboard
        const keyboardOffsetWithOffsetTop = innerHeight - vv.height - vv.offsetTop;
        const keyboardOffsetWithoutOffsetTop = innerHeight - vv.height; // Current buggy formula

        setViewportBottom(Math.max(0, keyboardOffsetWithOffsetTop));
        setVisualViewportHeight(vv.height);
        setDebugValues({
          innerHeight,
          vvHeight: vv.height,
          vvOffsetTop: vv.offsetTop,
          keyboardOffsetWithOffsetTop,
          keyboardOffsetWithoutOffsetTop,
          difference: keyboardOffsetWithoutOffsetTop - keyboardOffsetWithOffsetTop,
        });
      }

      // Continuous rAF loop for smooth keyboard animation tracking
      rafId = requestAnimationFrame(updatePosition);
    };

    // Start the animation loop
    updatePosition();

    // Also listen to visualViewport events for immediate response
    const vv = getVisualViewport();
    const handleViewportChange = () => {
      const viewport = getVisualViewport();
      const innerHeight = getInnerHeight();
      if (viewport) {
        const keyboardOffsetWithOffsetTop = innerHeight - viewport.height - viewport.offsetTop;
        const keyboardOffsetWithoutOffsetTop = innerHeight - viewport.height;

        setViewportBottom(Math.max(0, keyboardOffsetWithOffsetTop));
        setVisualViewportHeight(viewport.height);
        setDebugValues({
          innerHeight,
          vvHeight: viewport.height,
          vvOffsetTop: viewport.offsetTop,
          keyboardOffsetWithOffsetTop,
          keyboardOffsetWithoutOffsetTop,
          difference: keyboardOffsetWithoutOffsetTop - keyboardOffsetWithOffsetTop,
        });
      }
    };

    if (vv) {
      vv.addEventListener('resize', handleViewportChange);
      vv.addEventListener('scroll', handleViewportChange);
    }

    return () => {
      isRunning = false;
      cancelAnimationFrame(rafId);
      const viewport = getVisualViewport();
      if (viewport) {
        viewport.removeEventListener('resize', handleViewportChange);
        viewport.removeEventListener('scroll', handleViewportChange);
      }
    };
  }, []);

  return (
    <>
      {/* Debug values panel - shows numerical viewport values */}
      {/* Uses offsetTop to stay visible when keyboard opens and visual viewport shifts */}
      <div
        data-testid="viewport-debug-panel"
        style={{
          position: 'fixed',
          top: 10 + debugValues.vvOffsetTop, // Move down with visual viewport
          left: 10,
          padding: '8px 12px',
          backgroundColor: 'rgba(0, 0, 0, 0.85)',
          color: 'white',
          fontSize: '11px',
          fontFamily: 'monospace',
          borderRadius: '6px',
          zIndex: 2147483647,
          pointerEvents: 'none',
          lineHeight: 1.4,
          minWidth: '220px',
        }}
      >
        <div style={{ fontWeight: 'bold', marginBottom: '4px', borderBottom: '1px solid #555', paddingBottom: '4px' }}>
          Viewport Debug
        </div>
        <div>innerHeight: <span style={{ color: '#88f' }}>{Math.round(debugValues.innerHeight)}px</span></div>
        <div>vv.height: <span style={{ color: '#8f8' }}>{Math.round(debugValues.vvHeight)}px</span></div>
        <div>vv.offsetTop: <span style={{ color: '#ff8' }}>{Math.round(debugValues.vvOffsetTop)}px</span></div>
        <div style={{ marginTop: '4px', borderTop: '1px solid #555', paddingTop: '4px' }}>
          <div>offset (w/ offsetTop): <span style={{ color: '#8f8' }}>{Math.round(debugValues.keyboardOffsetWithOffsetTop)}px</span> ✓</div>
          <div>offset (w/o offsetTop): <span style={{ color: '#f88' }}>{Math.round(debugValues.keyboardOffsetWithoutOffsetTop)}px</span> ✗</div>
          <div style={{ marginTop: '2px', fontWeight: 'bold' }}>
            difference: <span style={{ color: debugValues.difference !== 0 ? '#f88' : '#8f8' }}>{Math.round(debugValues.difference)}px</span>
          </div>
        </div>
      </div>

      {/* Red indicator - absolute positioning with transform for GPU-accelerated positioning */}
      <div
        ref={absoluteRef}
        data-testid="viewport-debug-absolute"
        style={{
          position: 'absolute',
          bottom: 0,
          left: 0,
          width: '100px',
          height: '30px',
          backgroundColor: 'red',
          zIndex: 2147483647,
          pointerEvents: 'none',
          transform: `translateY(-${viewportBottom}px)`, // GPU-accelerated, more reliable during keyboard animation
        }}
      />

      {/* Green indicator - fixed positioning with transform for keyboard tracking */}
      <div
        data-testid="viewport-debug-fixed"
        style={{
          position: 'fixed',
          bottom: 0,
          right: 0,
          width: '100px',
          maxHeight: visualViewportHeight * 0.5, // 50% of visual viewport height (shrinks with keyboard)
          height: visualViewportHeight * 0.5, // Use same value to show full extent
          backgroundColor: 'green',
          zIndex: 2147483647,
          pointerEvents: 'none',
          opacity: 0.5, // Semi-transparent so we can see what's behind
          borderBottom: '2px solid red',
          borderTop: '2px solid blue',
          transform: `translateY(-${viewportBottom}px)`, // GPU-accelerated, more reliable during keyboard animation
        }}
      />
    </>
  );
};
