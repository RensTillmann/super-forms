import { useState, useEffect } from 'react';

/**
 * Hook to detect if a media query matches.
 * @param query - CSS media query string (e.g., '(max-width: 639px)')
 * @returns boolean indicating if the media query matches
 */
export function useMediaQuery(query: string): boolean {
  const [matches, setMatches] = useState(false);

  useEffect(() => {
    const media = window.matchMedia(query);
    setMatches(media.matches);

    const listener = (e: MediaQueryListEvent) => setMatches(e.matches);
    media.addEventListener('change', listener);
    return () => media.removeEventListener('change', listener);
  }, [query]);

  return matches;
}

/**
 * Hook to detect mobile viewport (< 640px).
 */
export function useIsMobile(): boolean {
  return useMediaQuery('(max-width: 639px)');
}

/**
 * Hook to detect tablet viewport (640px - 1023px).
 */
export function useIsTablet(): boolean {
  return useMediaQuery('(min-width: 640px) and (max-width: 1023px)');
}

/**
 * Hook to detect desktop viewport (>= 1024px).
 */
export function useIsDesktop(): boolean {
  return useMediaQuery('(min-width: 1024px)');
}
