import { cn } from '../../../lib/utils';
import { Input } from '../input';
import type { StyleProperties, NodeStyleCapabilities } from '../../../schemas/styles';
import { LinkedPropertyInput } from './LinkedPropertyInput';
import { ColorControl } from './ColorControl';

interface FontControlsProps {
  style: Partial<StyleProperties>;
  globalStyle: Partial<StyleProperties>;
  overrides: Partial<StyleProperties>;
  capabilities: NodeStyleCapabilities;
  onOverride: (property: keyof StyleProperties, value: StyleProperties[keyof StyleProperties]) => void;
  onRemoveOverride: (property: keyof StyleProperties) => void;
  className?: string;
}

const FONT_WEIGHTS = [
  { value: '400', label: 'Normal' },
  { value: '500', label: 'Medium' },
  { value: '600', label: 'Semibold' },
  { value: '700', label: 'Bold' },
];

const FONT_FAMILIES = [
  { value: 'inherit', label: 'Inherit' },
  { value: 'Arial, sans-serif', label: 'Arial' },
  { value: 'Georgia, serif', label: 'Georgia' },
  { value: 'system-ui, sans-serif', label: 'System UI' },
  { value: '"Courier New", monospace', label: 'Courier New' },
];

export function FontControls({
  style,
  globalStyle,
  overrides,
  capabilities,
  onOverride,
  onRemoveOverride,
  className,
}: FontControlsProps) {
  const isOverridden = (prop: keyof StyleProperties) =>
    overrides[prop] !== undefined;

  return (
    <div className={cn('space-y-3', className)}>
      {capabilities.fontSize && (
        <LinkedPropertyInput
          label="Size"
          value={style.fontSize ?? 14}
          globalValue={globalStyle.fontSize ?? 14}
          isLinked={!isOverridden('fontSize')}
          onLink={() => onRemoveOverride('fontSize')}
          onUnlink={() => onOverride('fontSize', globalStyle.fontSize ?? 14)}
          onChange={(v) => onOverride('fontSize', v)}
          renderInput={({ value, onChange, disabled }) => (
            <div className="flex items-center gap-1">
              <Input
                type="number"
                min={8}
                max={72}
                value={value}
                onChange={(e) => onChange(parseInt(e.target.value) || 14)}
                disabled={disabled}
                className="w-20"
              />
              <span className="text-sm text-muted-foreground">px</span>
            </div>
          )}
        />
      )}

      {capabilities.fontFamily && (
        <LinkedPropertyInput
          label="Font"
          value={style.fontFamily ?? 'inherit'}
          globalValue={globalStyle.fontFamily ?? 'inherit'}
          isLinked={!isOverridden('fontFamily')}
          onLink={() => onRemoveOverride('fontFamily')}
          onUnlink={() => onOverride('fontFamily', globalStyle.fontFamily ?? 'inherit')}
          onChange={(v) => onOverride('fontFamily', v)}
          renderInput={({ value, onChange, disabled }) => (
            <select
              value={value}
              onChange={(e) => onChange(e.target.value)}
              disabled={disabled}
              className={cn(
                'w-40 h-9 px-3 py-1 rounded-md border border-input bg-background text-sm',
                'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
                disabled && 'opacity-50 cursor-not-allowed'
              )}
            >
              {FONT_FAMILIES.map((f) => (
                <option key={f.value} value={f.value}>
                  {f.label}
                </option>
              ))}
            </select>
          )}
        />
      )}

      {capabilities.fontWeight && (
        <LinkedPropertyInput
          label="Weight"
          value={style.fontWeight ?? '400'}
          globalValue={globalStyle.fontWeight ?? '400'}
          isLinked={!isOverridden('fontWeight')}
          onLink={() => onRemoveOverride('fontWeight')}
          onUnlink={() => onOverride('fontWeight', globalStyle.fontWeight ?? '400')}
          onChange={(v) => onOverride('fontWeight', v as StyleProperties['fontWeight'])}
          renderInput={({ value, onChange, disabled }) => (
            <select
              value={value}
              onChange={(e) => onChange(e.target.value as StyleProperties['fontWeight'])}
              disabled={disabled}
              className={cn(
                'w-32 h-9 px-3 py-1 rounded-md border border-input bg-background text-sm',
                'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
                disabled && 'opacity-50 cursor-not-allowed'
              )}
            >
              {FONT_WEIGHTS.map((w) => (
                <option key={w.value} value={w.value}>
                  {w.label}
                </option>
              ))}
            </select>
          )}
        />
      )}

      {capabilities.color && (
        <LinkedPropertyInput
          label="Color"
          value={style.color ?? '#000000'}
          globalValue={globalStyle.color ?? '#000000'}
          isLinked={!isOverridden('color')}
          onLink={() => onRemoveOverride('color')}
          onUnlink={() => onOverride('color', globalStyle.color ?? '#000000')}
          onChange={(v) => onOverride('color', v)}
          renderInput={({ value, onChange, disabled }) => (
            <ColorControl
              value={value}
              onChange={onChange}
              disabled={disabled}
            />
          )}
        />
      )}

      {capabilities.lineHeight && (
        <LinkedPropertyInput
          label="Line Height"
          value={style.lineHeight ?? 1.4}
          globalValue={globalStyle.lineHeight ?? 1.4}
          isLinked={!isOverridden('lineHeight')}
          onLink={() => onRemoveOverride('lineHeight')}
          onUnlink={() => onOverride('lineHeight', globalStyle.lineHeight ?? 1.4)}
          onChange={(v) => onOverride('lineHeight', v)}
          renderInput={({ value, onChange, disabled }) => (
            <Input
              type="number"
              min={0.5}
              max={3}
              step={0.1}
              value={value}
              onChange={(e) => onChange(parseFloat(e.target.value) || 1.4)}
              disabled={disabled}
              className="w-20"
            />
          )}
        />
      )}
    </div>
  );
}
