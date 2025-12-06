import { useState, useRef, useEffect } from 'react';
import { Paintbrush, X } from 'lucide-react';
import { cn } from '../../../lib/utils';
import { Button } from '../button';
import {
  NodeType,
  StyleProperties,
  getNodeCapabilities,
  getElementNodes,
  styleRegistry,
} from '../../../schemas/styles';
import { useElementStyle } from '../../../apps/form-builder-v2/hooks/useElementStyle';
import { useElementsStore } from '../../../apps/form-builder-v2/store/useElementsStore';
import { FontControls } from './FontControls';
import { SpacingControl } from './SpacingControl';
import { ColorControl } from './ColorControl';

interface NodeStylePopoverProps {
  elementId: string;
  elementType: string;
  className?: string;
}

const NODE_LABELS: Record<NodeType, string> = {
  label: 'Label',
  description: 'Description',
  input: 'Input Field',
  placeholder: 'Placeholder',
  error: 'Error Message',
  required: 'Required *',
  fieldContainer: 'Container',
  heading: 'Heading',
  paragraph: 'Paragraph',
  button: 'Button',
  divider: 'Divider',
  optionLabel: 'Option Label',
  cardContainer: 'Card',
};

export function NodeStylePopover({
  elementId,
  elementType,
  className,
}: NodeStylePopoverProps) {
  const [open, setOpen] = useState(false);
  const [activeNode, setActiveNode] = useState<NodeType | null>(null);
  const [activeTab, setActiveTab] = useState<'individual' | 'global'>('individual');
  const popoverRef = useRef<HTMLDivElement>(null);

  const nodeStyles = useElementStyle(elementId);
  const nodes = getElementNodes(elementType);
  const { setStyleOverride, removeStyleOverride, clearNodeStyleOverrides } =
    useElementsStore();

  // Default to first node if none selected
  const currentNode = activeNode ?? nodes[0];
  const currentNodeStyle = nodeStyles.find((n) => n.nodeType === currentNode);
  const capabilities = getNodeCapabilities(currentNode);

  const overrideCount = nodeStyles.filter((n) => n.hasOverrides).length;

  // Close on outside click
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (popoverRef.current && !popoverRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    if (open) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [open]);

  const handleOverride = (property: keyof StyleProperties, value: StyleProperties[keyof StyleProperties]) => {
    setStyleOverride(elementId, currentNode, property, value);
  };

  const handleRemoveOverride = (property: keyof StyleProperties) => {
    removeStyleOverride(elementId, currentNode, property);
  };

  const handleResetNode = () => {
    clearNodeStyleOverrides(elementId, currentNode);
  };

  const handleSetAsGlobal = () => {
    if (!currentNodeStyle?.overrides) return;
    // Promote overrides to global
    Object.entries(currentNodeStyle.overrides).forEach(([prop, value]) => {
      styleRegistry.setGlobalProperty(
        currentNode,
        prop as keyof StyleProperties,
        value
      );
    });
    // Clear local overrides
    clearNodeStyleOverrides(elementId, currentNode);
  };

  return (
    <div className="relative" ref={popoverRef}>
      <Button
        variant="outline"
        size="sm"
        className={cn('gap-2', className)}
        onClick={() => setOpen(!open)}
      >
        <Paintbrush className="h-4 w-4" />
        Styles
        {overrideCount > 0 && (
          <span className="ml-1 px-1.5 py-0 text-xs bg-secondary rounded-full">
            {overrideCount}
          </span>
        )}
      </Button>

      {open && (
        <div className="absolute z-50 mt-2 w-96 bg-background border rounded-lg shadow-lg">
          {/* Header with tabs */}
          <div className="flex items-center border-b">
            <button
              onClick={() => setActiveTab('individual')}
              className={cn(
                'flex-1 px-4 py-2 text-sm font-medium transition-colors',
                activeTab === 'individual'
                  ? 'border-b-2 border-primary text-primary'
                  : 'text-muted-foreground hover:text-foreground'
              )}
            >
              Individual
            </button>
            <button
              onClick={() => setActiveTab('global')}
              className={cn(
                'flex-1 px-4 py-2 text-sm font-medium transition-colors',
                activeTab === 'global'
                  ? 'border-b-2 border-primary text-primary'
                  : 'text-muted-foreground hover:text-foreground'
              )}
            >
              Global Theme
            </button>
            <button
              onClick={() => setOpen(false)}
              className="p-2 text-muted-foreground hover:text-foreground"
            >
              <X className="h-4 w-4" />
            </button>
          </div>

          <div className="flex">
            {/* Node selector sidebar */}
            <div className="w-28 border-r bg-muted/30 p-2">
              <div className="h-[300px] overflow-y-auto">
                <div className="space-y-1">
                  {nodes.map((nodeType) => {
                    const style = nodeStyles.find((n) => n.nodeType === nodeType);
                    return (
                      <button
                        key={nodeType}
                        onClick={() => setActiveNode(nodeType)}
                        className={cn(
                          'w-full text-left px-2 py-1.5 rounded text-sm transition-colors',
                          currentNode === nodeType
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-muted'
                        )}
                      >
                        <span className="flex items-center justify-between">
                          {NODE_LABELS[nodeType] ?? nodeType}
                          {style?.hasOverrides && (
                            <span className="h-1.5 w-1.5 rounded-full bg-orange-500" />
                          )}
                        </span>
                      </button>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Style controls */}
            <div className="flex-1 p-4">
              <div className="h-[300px] overflow-y-auto">
                {activeTab === 'individual' && currentNodeStyle && (
                  <div className="space-y-6">
                    {/* Typography section */}
                    {(capabilities.fontSize ||
                      capabilities.fontFamily ||
                      capabilities.color) && (
                      <div>
                        <h4 className="text-sm font-medium mb-3">Typography</h4>
                        <FontControls
                          style={currentNodeStyle.resolvedStyle}
                          globalStyle={currentNodeStyle.globalStyle}
                          overrides={currentNodeStyle.overrides ?? {}}
                          capabilities={capabilities}
                          onOverride={handleOverride}
                          onRemoveOverride={handleRemoveOverride}
                        />
                      </div>
                    )}

                    {/* Spacing section */}
                    {(capabilities.margin || capabilities.padding) && (
                      <div>
                        <h4 className="text-sm font-medium mb-3">Spacing</h4>
                        {capabilities.margin && (
                          <SpacingControl
                            label="Margin"
                            value={
                              currentNodeStyle.resolvedStyle.margin ?? {
                                top: 0,
                                right: 0,
                                bottom: 0,
                                left: 0,
                              }
                            }
                            onChange={(v) => handleOverride('margin', v)}
                            color="orange"
                          />
                        )}
                        {capabilities.padding && (
                          <SpacingControl
                            label="Padding"
                            value={
                              currentNodeStyle.resolvedStyle.padding ?? {
                                top: 0,
                                right: 0,
                                bottom: 0,
                                left: 0,
                              }
                            }
                            onChange={(v) => handleOverride('padding', v)}
                            color="blue"
                            className="mt-4"
                          />
                        )}
                      </div>
                    )}

                    {/* Background section */}
                    {capabilities.backgroundColor && (
                      <div>
                        <h4 className="text-sm font-medium mb-3">Background</h4>
                        <ColorControl
                          value={
                            currentNodeStyle.resolvedStyle.backgroundColor ??
                            '#ffffff'
                          }
                          onChange={(v) => handleOverride('backgroundColor', v)}
                        />
                      </div>
                    )}

                    {/* Actions */}
                    {currentNodeStyle.hasOverrides && (
                      <div className="flex gap-2 pt-4 border-t">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={handleResetNode}
                        >
                          Reset to Global
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={handleSetAsGlobal}
                        >
                          Set as Global
                        </Button>
                      </div>
                    )}
                  </div>
                )}

                {activeTab === 'global' && currentNodeStyle && (
                  <div>
                    <p className="text-sm text-muted-foreground mb-4">
                      Changes here affect all elements using this node type.
                    </p>
                    <FontControls
                      style={currentNodeStyle.globalStyle}
                      globalStyle={currentNodeStyle.globalStyle}
                      overrides={{}}
                      capabilities={capabilities}
                      onOverride={(prop, value) => {
                        styleRegistry.setGlobalProperty(currentNode, prop, value);
                      }}
                      onRemoveOverride={() => {}}
                    />
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
