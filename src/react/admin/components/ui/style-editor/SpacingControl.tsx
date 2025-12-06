import { useState } from 'react';
import { Link, Unlink } from 'lucide-react';
import { cn } from '../../../lib/utils';
import { Button } from '../button';
import { Input } from '../input';
import type { Spacing } from '../../../schemas/styles';

interface SpacingControlProps {
  label: string;
  value: Spacing;
  onChange: (value: Spacing) => void;
  disabled?: boolean;
  color?: 'orange' | 'purple' | 'blue';
  className?: string;
}

const colorClasses = {
  orange: {
    bg: 'bg-orange-50',
    border: 'border-orange-300',
    text: 'text-orange-600',
    input: 'border-orange-400 focus:ring-orange-500',
    linked: 'bg-orange-500 text-white',
  },
  purple: {
    bg: 'bg-purple-50',
    border: 'border-purple-300',
    text: 'text-purple-600',
    input: 'border-purple-400 focus:ring-purple-500',
    linked: 'bg-purple-500 text-white',
  },
  blue: {
    bg: 'bg-blue-50',
    border: 'border-blue-300',
    text: 'text-blue-600',
    input: 'border-blue-400 focus:ring-blue-500',
    linked: 'bg-blue-500 text-white',
  },
};

export function SpacingControl({
  label,
  value,
  onChange,
  disabled = false,
  color = 'blue',
  className,
}: SpacingControlProps) {
  const [isLinked, setIsLinked] = useState(
    value.top === value.right &&
      value.top === value.bottom &&
      value.top === value.left
  );

  const colors = colorClasses[color];

  const handleChange = (side: keyof Spacing, newValue: number) => {
    if (isLinked) {
      onChange({ top: newValue, right: newValue, bottom: newValue, left: newValue });
    } else {
      onChange({ ...value, [side]: newValue });
    }
  };

  return (
    <div className={cn('space-y-2', className)}>
      <div className="flex items-center justify-between">
        <span className={cn('text-sm font-medium', colors.text)}>{label}</span>
        <Button
          variant="ghost"
          size="icon"
          className={cn('h-6 w-6', isLinked ? colors.linked : '')}
          onClick={() => setIsLinked(!isLinked)}
          disabled={disabled}
        >
          {isLinked ? <Link className="h-3 w-3" /> : <Unlink className="h-3 w-3" />}
        </Button>
      </div>

      <div
        className={cn(
          'grid gap-2 p-3 rounded-lg border',
          colors.bg,
          colors.border,
          disabled && 'opacity-50'
        )}
      >
        {isLinked ? (
          // Single input when linked
          <div className="flex items-center justify-center">
            <Input
              type="number"
              min={0}
              max={200}
              value={value.top}
              onChange={(e) => handleChange('top', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-16 text-center', colors.input)}
            />
            <span className="ml-2 text-sm text-muted-foreground">all sides</span>
          </div>
        ) : (
          // Four inputs when unlinked
          <div className="grid grid-cols-3 gap-2">
            <div />
            <Input
              type="number"
              min={0}
              max={200}
              value={value.top}
              onChange={(e) => handleChange('top', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="T"
            />
            <div />

            <Input
              type="number"
              min={0}
              max={200}
              value={value.left}
              onChange={(e) => handleChange('left', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="L"
            />
            <div className="flex items-center justify-center">
              <span className="text-xs text-muted-foreground">px</span>
            </div>
            <Input
              type="number"
              min={0}
              max={200}
              value={value.right}
              onChange={(e) => handleChange('right', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="R"
            />

            <div />
            <Input
              type="number"
              min={0}
              max={200}
              value={value.bottom}
              onChange={(e) => handleChange('bottom', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="B"
            />
            <div />
          </div>
        )}
      </div>
    </div>
  );
}
