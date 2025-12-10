import React from 'react';
import { Square } from 'lucide-react';
import { CollapsibleSection } from './CollapsibleSection';
import { StylePropertyRow } from './StylePropertyRow';
import { ColorControl } from '../../../../../components/ui/style-editor/ColorControl';
import { NodeType, StyleProperties } from '../../../../../schemas/styles';
import { NODE_STYLE_CAPABILITIES } from '../../../../../schemas/styles';

interface BorderSectionProps {
  elementId: string;
  nodeType: NodeType;
  onOverrideChange: (property: keyof StyleProperties, value: unknown) => void;
  onRemoveOverride: (property: keyof StyleProperties) => void;
  defaultExpanded?: boolean;
}

export const BorderSection: React.FC<BorderSectionProps> = ({
  elementId,
  nodeType,
  onOverrideChange,
  onRemoveOverride,
  defaultExpanded = false,
}) => {
  const capabilities = NODE_STYLE_CAPABILITIES[nodeType];

  // Check if any border properties are available
  const hasBorder = capabilities && (
    capabilities.border ||
    capabilities.borderRadius
  );

  if (!hasBorder) return null;

  return (
    <CollapsibleSection
      title="Border"
      icon={<Square className="h-3.5 w-3.5" />}
      defaultExpanded={defaultExpanded}
    >
      {/* Border Radius */}
      {capabilities.borderRadius && (
        <StylePropertyRow
          label="Radius"
          property="borderRadius"
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
                placeholder="6"
                min={0}
                max={50}
              />
              <span className="text-xs text-gray-500">px</span>
            </div>
          )}
        </StylePropertyRow>
      )}

      {/* Border Color */}
      {capabilities.border && (
        <StylePropertyRow
          label="Color"
          property="borderColor"
          elementId={elementId}
          nodeType={nodeType}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <ColorControl
              value={(value as string) ?? '#d1d5db'}
              onChange={(v) => onChange(v)}
            />
          )}
        </StylePropertyRow>
      )}
    </CollapsibleSection>
  );
};

export default BorderSection;
