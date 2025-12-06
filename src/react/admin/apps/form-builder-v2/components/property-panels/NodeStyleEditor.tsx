import React from 'react';
import { Link2, Unlink2 } from 'lucide-react';
import {
  NODE_STYLE_CAPABILITIES,
  NodeType,
  StyleProperties,
} from '../../../../schemas/styles';
import { usePropertyValues } from '../../hooks/useResolvedStyle';
import { ColorControl } from '../../../../components/ui/style-editor/ColorControl';
import { cn } from '../../../../lib/utils';

interface NodeStyleEditorProps {
  elementId: string;
  nodeType: NodeType;
  onOverrideChange: (property: keyof StyleProperties, value: unknown) => void;
  onRemoveOverride: (property: keyof StyleProperties) => void;
}

export const NodeStyleEditor: React.FC<NodeStyleEditorProps> = ({
  elementId,
  nodeType,
  onOverrideChange,
  onRemoveOverride,
}) => {
  const capabilities = NODE_STYLE_CAPABILITIES[nodeType];

  if (!capabilities) {
    return <p className="text-xs text-gray-500">Unknown node type</p>;
  }

  return (
    <div className="space-y-3 bg-gray-50 rounded-lg p-3">
      {/* Font Size */}
      {capabilities.fontSize && (
        <StylePropertyRow
          label="Font Size"
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
                className="w-16 px-2 py-1 text-sm border border-gray-300 rounded"
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
          label="Font Weight"
          property="fontWeight"
          elementId={elementId}
          nodeType={nodeType}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <select
              value={(value as string) ?? ''}
              onChange={(e) => onChange(e.target.value || undefined)}
              className="w-24 px-2 py-1 text-sm border border-gray-300 rounded"
            >
              <option value="">Default</option>
              <option value="400">Normal</option>
              <option value="500">Medium</option>
              <option value="600">Semibold</option>
              <option value="700">Bold</option>
            </select>
          )}
        </StylePropertyRow>
      )}

      {/* Color */}
      {capabilities.color && (
        <StylePropertyRow
          label="Text Color"
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

      {/* Background Color */}
      {capabilities.backgroundColor && (
        <StylePropertyRow
          label="Background"
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
      )}

      {/* Border Radius */}
      {capabilities.borderRadius && (
        <StylePropertyRow
          label="Border Radius"
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
                className="w-16 px-2 py-1 text-sm border border-gray-300 rounded"
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
          label="Border Color"
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

      {/* Line Height */}
      {capabilities.lineHeight && (
        <StylePropertyRow
          label="Line Height"
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
              className="w-16 px-2 py-1 text-sm border border-gray-300 rounded"
              placeholder="1.4"
              min={1}
              max={3}
              step={0.1}
            />
          )}
        </StylePropertyRow>
      )}
    </div>
  );
};

// Helper component for consistent property rows with link/unlink
interface StylePropertyRowProps {
  label: string;
  property: keyof StyleProperties;
  elementId: string;
  nodeType: NodeType;
  onOverride: (property: keyof StyleProperties, value: unknown) => void;
  onUnlink: (property: keyof StyleProperties) => void;
  children: (value: unknown, onChange: (value: unknown) => void) => React.ReactNode;
}

const StylePropertyRow: React.FC<StylePropertyRowProps> = ({
  label,
  property,
  elementId,
  nodeType,
  onOverride,
  onUnlink,
  children,
}) => {
  const { globalValue, resolvedValue, isOverridden } = usePropertyValues(
    elementId,
    nodeType,
    property
  );

  const handleChange = (value: unknown) => {
    onOverride(property, value);
  };

  const handleToggleLink = () => {
    if (isOverridden) {
      onUnlink(property);
    } else {
      // Unlink: copy global value to override
      onOverride(property, globalValue);
    }
  };

  return (
    <div className="flex items-center gap-2">
      <label className="text-xs text-gray-600 w-24 flex-shrink-0">
        {label}
      </label>

      <div className="flex-1">
        {children(resolvedValue, handleChange)}
      </div>

      <button
        onClick={handleToggleLink}
        className={cn(
          "p-1 rounded transition-colors",
          isOverridden
            ? "text-orange-500 hover:bg-orange-50"
            : "text-gray-400 hover:bg-gray-100"
        )}
        title={isOverridden ? "Link to global (remove override)" : "Unlink from global (create override)"}
      >
        {isOverridden ? (
          <Unlink2 className="h-3.5 w-3.5" />
        ) : (
          <Link2 className="h-3.5 w-3.5" />
        )}
      </button>
    </div>
  );
};
