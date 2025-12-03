import { useEffect, useCallback } from 'react';

type KeyPressHandler = (event: KeyboardEvent) => void;

interface UseKeyPressOptions {
  preventDefault?: boolean;
  stopPropagation?: boolean;
  when?: boolean;
}

export const useKeyPress = (
  targetKey: string | string[],
  handler: KeyPressHandler,
  options: UseKeyPressOptions = {}
) => {
  const { preventDefault = false, stopPropagation = false, when = true } = options;

  const handleKeyPress = useCallback(
    (event: KeyboardEvent) => {
      if (!when) return;

      const keys = Array.isArray(targetKey) ? targetKey : [targetKey];
      const isTargetKey = keys.some(key => {
        if (key.includes('+')) {
          // Handle key combinations like 'Ctrl+S'
          const parts = key.split('+').map(p => p.trim().toLowerCase());
          const hasCtrl = parts.includes('ctrl') || parts.includes('control');
          const hasAlt = parts.includes('alt');
          const hasShift = parts.includes('shift');
          const hasMeta = parts.includes('meta') || parts.includes('cmd');
          const mainKey = parts[parts.length - 1];

          return (
            (!hasCtrl || event.ctrlKey) &&
            (!hasAlt || event.altKey) &&
            (!hasShift || event.shiftKey) &&
            (!hasMeta || event.metaKey) &&
            event.key.toLowerCase() === mainKey
          );
        }
        return event.key === key;
      });

      if (isTargetKey) {
        if (preventDefault) event.preventDefault();
        if (stopPropagation) event.stopPropagation();
        handler(event);
      }
    },
    [targetKey, handler, preventDefault, stopPropagation, when]
  );

  useEffect(() => {
    if (!when) return;

    document.addEventListener('keydown', handleKeyPress);
    return () => {
      document.removeEventListener('keydown', handleKeyPress);
    };
  }, [handleKeyPress, when]);
};