import React, { useEffect, useRef, useState } from 'react';

/**
 * Debug component to visualize viewport behavior when mobile keyboard opens.
 *
 * - Red line: position absolute, bottom:0, left:0 - uses visualViewport to track keyboard
 * - Green line: position fixed, bottom:0, right:0 - standard fixed positioning
 *
 * Both update every 10ms to show real-time viewport changes.
 * Max z-index (2147483647) to ensure visibility above all content.
 */
export const ViewportDebugIndicators: React.FC = () => {
  const absoluteRef = useRef<HTMLDivElement>(null);
  const [viewportBottom, setViewportBottom] = useState(0);
  const [visualViewportHeight, setVisualViewportHeight] = useState(window.innerHeight);

  useEffect(() => {
    // Update position and height based on visualViewport
    const updatePosition = () => {
      if (window.visualViewport) {
        // Calculate where the bottom of the visual viewport actually is
        const vv = window.visualViewport;
        // The visual viewport offsetTop tells us how much the viewport has moved up
        // When keyboard opens, offsetTop increases and height decreases
        const bottomPosition = vv.offsetTop + vv.height;
        setViewportBottom(window.innerHeight - bottomPosition);
        // Track visual viewport height for dynamic max-height
        setVisualViewportHeight(vv.height);
      }
    };

    // Initial update
    updatePosition();

    // Update every 10ms
    const intervalId = setInterval(updatePosition, 10);

    // Also listen to visualViewport events
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', updatePosition);
      window.visualViewport.addEventListener('scroll', updatePosition);
    }

    return () => {
      clearInterval(intervalId);
      if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', updatePosition);
        window.visualViewport.removeEventListener('scroll', updatePosition);
      }
    };
  }, []);

  return (
    <>
      {/* Red indicator - absolute positioning with visualViewport tracking */}
      <div
        ref={absoluteRef}
        data-testid="viewport-debug-absolute"
        style={{
          position: 'absolute',
          bottom: `${viewportBottom}px`,
          left: 0,
          width: '100px',
          height: '30px',
          backgroundColor: 'red',
          zIndex: 2147483647,
          pointerEvents: 'none',
        }}
      />

      {/* Green indicator - fixed positioning with visualViewport-based maxHeight */}
      <div
        data-testid="viewport-debug-fixed"
        style={{
          position: 'fixed',
          bottom: `${viewportBottom}px`,
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
        }}
      />
    </>
  );
};
