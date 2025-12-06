import React, { useState } from 'react';
import { ChevronDown, ChevronRight, Palette, RotateCcw } from 'lucide-react';
import { getElementNodes } from '../../../../schemas/styles/elementNodes';
import { NodeStyleEditor } from './NodeStyleEditor';
import { cn } from '../../../../lib/utils';
import { NodeType, StyleProperties } from '../../../../schemas/styles';

// Human-readable labels for node types
const NODE_LABELS: Record<NodeType, string> = {
  label: 'Label',
  description: 'Description',
  input: 'Input',
  placeholder: 'Placeholder',
  error: 'Error',
  required: 'Required',
  fieldContainer: 'Container',
  heading: 'Heading',
  paragraph: 'Paragraph',
  button: 'Button',
  divider: 'Divider',
  optionLabel: 'Option',
  cardContainer: 'Card',
};

interface ElementStylesSectionProps {
  elementId: string;
  elementType: string;
  styleOverrides?: Record<string, Partial<StyleProperties>>;
  onOverrideChange: (nodeType: string, property: string, value: unknown) => void;
  onResetToGlobal: (nodeType?: string) => void;
}

export const ElementStylesSection: React.FC<ElementStylesSectionProps> = ({
  elementId,
  elementType,
  styleOverrides = {},
  onOverrideChange,
  onResetToGlobal,
}) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [activeNode, setActiveNode] = useState<string | null>(null);

  // Get which nodes this element type contains
  const nodes = getElementNodes(elementType);

  // Count total overrides
  const overrideCount = Object.values(styleOverrides).reduce(
    (sum, nodeOverrides) => sum + Object.keys(nodeOverrides || {}).length,
    0
  );

  // Don't render if element has no styleable nodes
  if (nodes.length === 0) {
    return null;
  }

  return (
    <div className="mt-6 pt-6 border-t border-gray-200">
      {/* Section Header */}
      <button
        className="w-full flex items-center justify-between text-left"
        onClick={() => setIsExpanded(!isExpanded)}
      >
        <div className="flex items-center gap-2">
          {isExpanded ? (
            <ChevronDown className="h-4 w-4 text-gray-400" />
          ) : (
            <ChevronRight className="h-4 w-4 text-gray-400" />
          )}
          <Palette className="h-4 w-4 text-gray-500" />
          <span className="text-sm font-medium text-gray-700">Element Styles</span>
          {overrideCount > 0 && (
            <span className="px-1.5 py-0.5 text-[10px] font-medium text-orange-600 bg-orange-50 rounded">
              {overrideCount} override{overrideCount !== 1 ? 's' : ''}
            </span>
          )}
        </div>
        {overrideCount > 0 && (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onResetToGlobal();
            }}
            className="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1"
          >
            <RotateCcw className="h-3 w-3" />
            Reset all
          </button>
        )}
      </button>

      {/* Section Content */}
      {isExpanded && (
        <div className="mt-4 space-y-3">
          {/* Node Type Tabs */}
          <div className="flex flex-wrap gap-1">
            {nodes.map((nodeType) => {
              const nodeOverrides = styleOverrides[nodeType];
              const hasOverrides = nodeOverrides && Object.keys(nodeOverrides).length > 0;

              return (
                <button
                  key={nodeType}
                  onClick={() => setActiveNode(activeNode === nodeType ? null : nodeType)}
                  className={cn(
                    "px-2.5 py-1 text-xs rounded-md transition-colors",
                    activeNode === nodeType
                      ? "bg-primary text-white"
                      : "bg-gray-100 text-gray-600 hover:bg-gray-200",
                    hasOverrides && activeNode !== nodeType && "ring-1 ring-orange-300"
                  )}
                >
                  {NODE_LABELS[nodeType] || nodeType}
                  {hasOverrides && <span className="ml-1 text-orange-400">•</span>}
                </button>
              );
            })}
          </div>

          {/* Node Style Editor */}
          {activeNode && (
            <NodeStyleEditor
              elementId={elementId}
              nodeType={activeNode as NodeType}
              onOverrideChange={(property, value) =>
                onOverrideChange(activeNode, property as string, value)
              }
              onRemoveOverride={(property) =>
                onOverrideChange(activeNode, property as string, undefined)
              }
            />
          )}

          {!activeNode && (
            <p className="text-xs text-gray-500 italic">
              Select a node type above to customize its styles for this element only.
            </p>
          )}
        </div>
      )}
    </div>
  );
};
