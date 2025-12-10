import React from 'react';
import { ToggleGroup, ToggleGroupItem } from '../../../../../../components/ui/toggle-group';
import { cn } from '../../../../../../lib/utils';

export type PositionValue =
  | 'top-left'
  | 'top-center'
  | 'top-right'
  | 'left'
  | 'center'
  | 'right'
  | 'bottom-left'
  | 'bottom-center'
  | 'bottom-right';

interface PositionPickerRendererProps {
  value: PositionValue | string;
  onChange: (value: PositionValue) => void;
}

const POSITIONS: { value: PositionValue; row: number; col: number }[] = [
  { value: 'top-left', row: 0, col: 0 },
  { value: 'top-center', row: 0, col: 1 },
  { value: 'top-right', row: 0, col: 2 },
  { value: 'left', row: 1, col: 0 },
  { value: 'center', row: 1, col: 1 },
  { value: 'right', row: 1, col: 2 },
  { value: 'bottom-left', row: 2, col: 0 },
  { value: 'bottom-center', row: 2, col: 1 },
  { value: 'bottom-right', row: 2, col: 2 },
];

/**
 * 3x3 grid position picker for label/description placement.
 * Uses ToggleGroup for single-select behavior with ARIA radiogroup semantics.
 */
export const PositionPickerRenderer: React.FC<PositionPickerRendererProps> = ({
  value,
  onChange,
}) => {
  const currentValue = (value as PositionValue) || 'top-left';

  return (
    <ToggleGroup
      type="single"
      value={currentValue}
      onValueChange={(val) => {
        if (val) onChange(val as PositionValue);
      }}
      className="grid grid-cols-3 gap-0.5 w-fit p-1 bg-muted rounded-md"
      data-testid="position-picker"
    >
      {POSITIONS.map((pos) => (
        <ToggleGroupItem
          key={pos.value}
          value={pos.value}
          aria-label={pos.value.replace('-', ' ')}
          className={cn(
            'w-6 h-6 p-0 rounded-sm',
            'data-[state=on]:bg-primary data-[state=on]:text-primary-foreground',
            'hover:bg-accent hover:text-accent-foreground',
            'focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-1',
            'transition-colors'
          )}
          data-testid={`position-picker-${pos.value}`}
        >
          <span
            className={cn(
              'w-2 h-2 rounded-full',
              currentValue === pos.value ? 'bg-current' : 'bg-muted-foreground/30'
            )}
          />
        </ToggleGroupItem>
      ))}
    </ToggleGroup>
  );
};

export default PositionPickerRenderer;
