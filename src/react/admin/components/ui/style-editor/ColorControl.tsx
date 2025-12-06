import { useState, useCallback, useRef, useEffect } from 'react';
import { cn } from '../../../lib/utils';
import { Input } from '../input';

interface ColorControlProps {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  className?: string;
}

export function ColorControl({
  value,
  onChange,
  disabled = false,
  className,
}: ColorControlProps) {
  const [localColor, setLocalColor] = useState(value);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Sync local state when prop changes
  useEffect(() => {
    setLocalColor(value);
  }, [value]);

  const handleChange = useCallback(
    (newColor: string) => {
      setLocalColor(newColor); // Immediate UI update

      // Debounce the actual update
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
      timeoutRef.current = setTimeout(() => {
        onChange(newColor);
      }, 150);
    },
    [onChange]
  );

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <input
        type="color"
        value={localColor}
        onChange={(e) => handleChange(e.target.value)}
        disabled={disabled}
        className={cn(
          'w-10 h-10 rounded border-2 cursor-pointer transition-all',
          disabled
            ? 'opacity-50 cursor-not-allowed border-muted'
            : 'border-border hover:border-primary'
        )}
      />
      <Input
        type="text"
        value={localColor}
        onChange={(e) => handleChange(e.target.value)}
        disabled={disabled}
        className="w-24 font-mono text-sm"
        placeholder="#000000"
      />
    </div>
  );
}
