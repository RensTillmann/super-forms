import React from 'react';
import { PaintBucket } from 'lucide-react';
import { CollapsibleSection } from './CollapsibleSection';
import { StylePropertyRow } from './StylePropertyRow';
import { ColorControl } from '../../../../../components/ui/style-editor/ColorControl';
import { NodeType, StyleProperties } from '../../../../../schemas/styles';
import { NODE_STYLE_CAPABILITIES } from '../../../../../schemas/styles';

interface BackgroundSectionProps {
  elementId: string;
  nodeType: NodeType;
  onOverrideChange: (property: keyof StyleProperties, value: unknown) => void;
  onRemoveOverride: (property: keyof StyleProperties) => void;
  defaultExpanded?: boolean;
}

export const BackgroundSection: React.FC<BackgroundSectionProps> = ({
  elementId,
  nodeType,
  onOverrideChange,
  onRemoveOverride,
  defaultExpanded = false,
}) => {
  const capabilities = NODE_STYLE_CAPABILITIES[nodeType];

  if (!capabilities?.backgroundColor) return null;

  return (
    <CollapsibleSection
      title="Background"
      icon={<PaintBucket className="h-3.5 w-3.5" />}
      defaultExpanded={defaultExpanded}
    >
      <StylePropertyRow
        label="Color"
        property="backgroundColor"
        elementId={elementId}
        nodeType={nodeType}
        onOverride={onOverrideChange}
        onUnlink={onRemoveOverride}
      >
        {(value, onChange) => (
          <ColorControl
            value={(value as string) ?? '#ffffff'}
            onChange={(v) => onChange(v)}
          />
        )}
      </StylePropertyRow>
    </CollapsibleSection>
  );
};

export default BackgroundSection;
