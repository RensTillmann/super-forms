import { useState, useEffect } from 'react';

/**
 * Hook to observe WordPress admin sidebar state and width.
 * Dynamically measures the actual sidebar width from the DOM.
 *
 * @returns { width: number, isFolded: boolean }
 */
export function useWPAdminSidebar() {
  const [width, setWidth] = useState(() => {
    const sidebar = document.getElementById('adminmenuwrap');
    return sidebar?.offsetWidth ?? 36; // Fallback if not found
  });

  const [isFolded, setIsFolded] = useState(() =>
    document.body.classList.contains('folded')
  );

  useEffect(() => {
    const updateState = () => {
      setIsFolded(document.body.classList.contains('folded'));

      // Measure actual sidebar width from DOM
      const sidebar = document.getElementById('adminmenuwrap');
      if (sidebar) {
        setWidth(sidebar.offsetWidth);
      }
    };

    const observer = new MutationObserver(updateState);
    observer.observe(document.body, {
      attributes: true,
      attributeFilter: ['class']
    });

    // Initial measurement after mount
    updateState();

    return () => observer.disconnect();
  }, []);

  return { width, isFolded };
}
