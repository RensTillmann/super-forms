import { useState, useEffect } from 'react';

/**
 * Hook to observe WordPress admin sidebar state and width.
 *
 * IFRAME CONTEXT: When running in iframe, sidebar DOM elements exist in parent window
 * and cannot be queried. In this case, we use window.sfuiData.sidebarWidth passed from PHP.
 *
 * NON-IFRAME CONTEXT: Dynamically measures actual sidebar width from DOM.
 *
 * @returns { width: number, isFolded: boolean }
 */
export function useWPAdminSidebar() {
  const [width, setWidth] = useState(() => {
    // Check if we have sidebar width from sfuiData (iframe context)
    // Note: sidebarWidth can be 0, so we check !== undefined, not truthiness
    if (window.sfuiData && 'sidebarWidth' in window.sfuiData) {
      console.log('[useWPAdminSidebar] Using sidebar width from sfuiData:', window.sfuiData.sidebarWidth);
      return window.sfuiData.sidebarWidth;
    }

    // Fallback: measure from DOM (non-iframe context)
    const sidebar = document.getElementById('adminmenuwrap');
    // If we're in an iframe (no sidebar in DOM) and no sfuiData, default to 0
    const measuredWidth = sidebar?.offsetWidth ?? (window.self !== window.top ? 0 : 36);
    console.log('[useWPAdminSidebar] Measured sidebar width from DOM:', measuredWidth, sidebar ? '(element found)' : '(element not found, using fallback)');
    return measuredWidth;
  });

  const [isFolded, setIsFolded] = useState(() =>
    document.body.classList.contains('folded')
  );

  useEffect(() => {
    // Skip DOM observation if we have static width from sfuiData (iframe context)
    if (window.sfuiData?.sidebarWidth !== undefined) {
      console.log('[useWPAdminSidebar] Skipping DOM observation (using static width from sfuiData)');
      return; // No cleanup needed
    }

    console.log('[useWPAdminSidebar] Setting up DOM observation for sidebar changes');

    // Non-iframe context: observe DOM changes
    const updateState = () => {
      setIsFolded(document.body.classList.contains('folded'));

      // Measure actual sidebar width from DOM
      const sidebar = document.getElementById('adminmenuwrap');
      if (sidebar) {
        const newWidth = sidebar.offsetWidth;
        console.log('[useWPAdminSidebar] Sidebar width updated:', newWidth);
        setWidth(newWidth);
      }
    };

    const observer = new MutationObserver(updateState);
    observer.observe(document.body, {
      attributes: true,
      attributeFilter: ['class']
    });

    // Initial measurement after mount
    updateState();

    return () => {
      console.log('[useWPAdminSidebar] Cleaning up DOM observer');
      observer.disconnect();
    };
  }, []);

  return { width, isFolded };
}
