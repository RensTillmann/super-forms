import React from 'react';
import { Type } from 'lucide-react';
import { CollapsibleSection } from './CollapsibleSection';
import { StylePropertyRow } from './StylePropertyRow';
import { ColorControl } from '../../../../../components/ui/style-editor/ColorControl';
import { NodeType, StyleProperties } from '../../../../../schemas/styles';
import { NODE_STYLE_CAPABILITIES } from '../../../../../schemas/styles';

interface TypographySectionProps {
  elementId: string;
  nodeType: NodeType;
  onOverrideChange: (property: keyof StyleProperties, value: unknown) => void;
  onRemoveOverride: (property: keyof StyleProperties) => void;
  defaultExpanded?: boolean;
}

const FONT_WEIGHTS = [
  { value: '400', label: 'Normal' },
  { value: '500', label: 'Medium' },
  { value: '600', label: 'Semibold' },
  { value: '700', label: 'Bold' },
];

const TEXT_ALIGNS = [
  { value: 'left', label: 'Left' },
  { value: 'center', label: 'Center' },
  { value: 'right', label: 'Right' },
];

export const TypographySection: React.FC<TypographySectionProps> = ({
  elementId,
  nodeType,
  onOverrideChange,
  onRemoveOverride,
  defaultExpanded = true,
}) => {
  const capabilities = NODE_STYLE_CAPABILITIES[nodeType];

  // Check if any typography properties are available
  const hasTypography = capabilities && (
    capabilities.fontSize ||
    capabilities.fontWeight ||
    capabilities.color ||
    capabilities.lineHeight ||
    capabilities.textAlign
  );

  if (!hasTypography) return null;

  return (
    <CollapsibleSection
      title="Typography"
      icon={<Type className="h-3.5 w-3.5" />}
      defaultExpanded={defaultExpanded}
    >
      {/* Font Size */}
      {capabilities.fontSize && (
        <StylePropertyRow
          label="Size"
          property="fontSize"
          elementId={elementId}
          nodeType={nodeType}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <div className="flex items-center gap-1">
              <input
                type="number"
                value={(value as number) ?? ''}
                onChange={(e) => onChange(Number(e.target.value) || undefined)}
                className="w-16 px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="14"
                min={8}
                max={72}
              />
              <span className="text-xs text-gray-500">px</span>
            </div>
          )}
        </StylePropertyRow>
      )}

      {/* Font Weight */}
      {capabilities.fontWeight && (
        <StylePropertyRow
          label="Weight"
          property="fontWeight"
          elementId={elementId}
          nodeType={nodeType}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <div className="flex gap-1">
              {FONT_WEIGHTS.map((fw) => (
                <button
                  key={fw.value}
                  onClick={() => onChange(value === fw.value ? undefined : fw.value)}
                  className={`px-2 py-1 text-[11px] rounded border transition-colors ${
                    value === fw.value
                      ? 'bg-gray-800 text-white border-gray-800'
                      : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'
                  }`}
                  title={fw.label}
                >
                  {fw.label.substring(0, 1)}
                </button>
              ))}
            </div>
          )}
        </StylePropertyRow>
      )}

      {/* Text Color */}
      {capabilities.color && (
        <StylePropertyRow
          label="Color"
          property="color"
          elementId={elementId}
          nodeType={nodeType}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <ColorControl
              value={(value as string) ?? '#000000'}
              onChange={(v) => onChange(v)}
            />
          )}
        </StylePropertyRow>
      )}

      {/* Text Alignment */}
      {capabilities.textAlign && (
        <StylePropertyRow
          label="Align"
          property="textAlign"
          elementId={elementId}
          nodeType={nodeType}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <div className="flex gap-1">
              {TEXT_ALIGNS.map((ta) => (
                <button
                  key={ta.value}
                  onClick={() => onChange(value === ta.value ? undefined : ta.value)}
                  className={`px-2 py-1 text-[11px] rounded border transition-colors ${
                    value === ta.value
                      ? 'bg-gray-800 text-white border-gray-800'
                      : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'
                  }`}
                >
                  {ta.label}
                </button>
              ))}
            </div>
          )}
        </StylePropertyRow>
      )}

      {/* Line Height */}
      {capabilities.lineHeight && (
        <StylePropertyRow
          label="Line H"
          property="lineHeight"
          elementId={elementId}
          nodeType={nodeType}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <input
              type="number"
              value={(value as number) ?? ''}
              onChange={(e) => onChange(parseFloat(e.target.value) || undefined)}
              className="w-16 px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="1.4"
              min={1}
              max={3}
              step={0.1}
            />
          )}
        </StylePropertyRow>
      )}
    </CollapsibleSection>
  );
};

export default TypographySection;
