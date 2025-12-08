import React, { useState, useRef } from 'react';
import {
  ChevronLeft,
  Tag,
  FileText,
  TextCursor,
  Type,
  AlertCircle,
  Asterisk,
  Square,
  Heading,
  AlignLeft,
  MousePointer,
  Minus,
  CircleDot,
  CreditCard,
  LucideIcon,
} from 'lucide-react';
import {
  styleRegistry,
  NodeType,
  NODE_STYLE_CAPABILITIES,
  StyleProperties,
} from '../../schemas/styles';
import { useGlobalStyles } from '../../apps/form-builder-v2/hooks/useGlobalStyles';
import { SpacingControl } from '../ui/style-editor/SpacingControl';
import { ColorControl } from '../ui/style-editor/ColorControl';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '../ui/select';
import { ButtonGroup, ButtonGroupText } from '../ui/button-group';
import { Input } from '../ui/input';
import { Button } from '../ui/button';
import {
  Item,
  ItemGroup,
  ItemMedia,
  ItemContent,
  ItemTitle,
  ItemDescription,
  ItemActions,
  ItemSeparator,
} from '../ui/item';

interface NodeInfo {
  name: string;
  description: string;
  icon: LucideIcon;
}

const NODE_INFO: Record<NodeType, NodeInfo> = {
  label: {
    name: 'Field Labels',
    description: 'The main name of the field shown above inputs',
    icon: Tag,
  },
  description: {
    name: 'Descriptions',
    description: 'Helper text shown below field labels',
    icon: FileText,
  },
  input: {
    name: 'Input Fields',
    description: 'Text inputs, dropdowns, and other form controls',
    icon: TextCursor,
  },
  placeholder: {
    name: 'Placeholders',
    description: 'Hint text inside empty input fields',
    icon: Type,
  },
  error: {
    name: 'Error Messages',
    description: 'Validation error text shown below fields',
    icon: AlertCircle,
  },
  required: {
    name: 'Required Indicator',
    description: 'The asterisk or text marking required fields',
    icon: Asterisk,
  },
  fieldContainer: {
    name: 'Field Containers',
    description: 'The wrapper around each form field',
    icon: Square,
  },
  heading: {
    name: 'Headings',
    description: 'Section titles and form headings',
    icon: Heading,
  },
  paragraph: {
    name: 'Paragraphs',
    description: 'Body text and form descriptions',
    icon: AlignLeft,
  },
  button: {
    name: 'Buttons',
    description: 'Submit, reset, and action buttons',
    icon: MousePointer,
  },
  divider: {
    name: 'Dividers',
    description: 'Horizontal lines separating sections',
    icon: Minus,
  },
  optionLabel: {
    name: 'Option Labels',
    description: 'Text next to checkboxes and radio buttons',
    icon: CircleDot,
  },
  cardContainer: {
    name: 'Cards',
    description: 'Card-style containers for grouped content',
    icon: CreditCard,
  },
};

export function GlobalStylesPanel() {
  const [selectedNode, setSelectedNode] = useState<NodeType | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const globalStyles = useGlobalStyles();

  const nodeTypes = Object.keys(NODE_STYLE_CAPABILITIES) as NodeType[];
  const capabilities = selectedNode ? NODE_STYLE_CAPABILITIES[selectedNode] : null;
  const currentStyle = selectedNode ? (globalStyles[selectedNode] ?? {}) : {};

  const handlePropertyChange = (property: string, value: StyleProperties[keyof StyleProperties]) => {
    if (selectedNode) {
      styleRegistry.setGlobalProperty(selectedNode, property as keyof StyleProperties, value);
    }
  };

  const handleExport = () => {
    const json = styleRegistry.exportStyles();
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'form-styles.json';
    a.click();
    URL.revokeObjectURL(url);
  };

  const handleImport = () => {
    fileInputRef.current?.click();
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (event) => {
        const json = event.target?.result as string;
        styleRegistry.importStyles(json);
      };
      reader.readAsText(file);
    }
  };

  return (
    <div data-testid="global-styles-panel">
      {/* Hidden file input for import functionality */}
      <input
        ref={fileInputRef}
        type="file"
        accept=".json"
        onChange={handleFileChange}
        className="hidden"
        data-testid="global-styles-import-input"
      />

      {/* Content */}
      <div className="p-4">
        {selectedNode === null ? (
          /* List View - Show all style categories */
          <div className="max-h-[450px] overflow-y-auto">
            <ItemGroup>
              {nodeTypes.map((nodeType, index) => {
                const info = NODE_INFO[nodeType];
                const Icon = info.icon;
                return (
                  <React.Fragment key={nodeType}>
                    {index > 0 && <ItemSeparator />}
                    <Item size="sm">
                      <ItemMedia variant="icon">
                        <Icon className="h-4 w-4" />
                      </ItemMedia>
                      <ItemContent>
                        <ItemTitle>{info.name}</ItemTitle>
                        <ItemDescription>{info.description}</ItemDescription>
                      </ItemContent>
                      <ItemActions>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => setSelectedNode(nodeType)}
                        >
                          Edit
                        </Button>
                      </ItemActions>
                    </Item>
                  </React.Fragment>
                );
              })}
            </ItemGroup>
          </div>
        ) : (
          /* Detail View - Show style controls for selected category */
          <div>
            {/* Return button */}
            <button
              onClick={() => setSelectedNode(null)}
              className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground mb-4 -ml-1"
            >
              <ChevronLeft className="h-4 w-4" />
              Return
            </button>

            {/* Category header */}
            <div className="flex items-center gap-2 mb-4">
              {(() => {
                const Icon = NODE_INFO[selectedNode].icon;
                return <Icon className="h-5 w-5 text-muted-foreground" />;
              })()}
              <h4 className="font-medium">{NODE_INFO[selectedNode].name}</h4>
            </div>

            {/* Style controls */}
            <div className="max-h-[380px] overflow-y-auto pr-2">
              <div className="space-y-6">
                {/* Typography */}
                {capabilities && (capabilities.fontSize ||
                  capabilities.fontFamily ||
                  capabilities.color) && (
                  <div>
                    <h4 className="text-sm font-medium mb-3">Typography</h4>
                    <div className="space-y-3">
                      {capabilities.fontSize && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500 w-24">
                            Font Size
                          </span>
                          <ButtonGroup>
                            <Input
                              type="number"
                              min={8}
                              max={72}
                              value={currentStyle.fontSize ?? 14}
                              onChange={(e) =>
                                handlePropertyChange(
                                  'fontSize',
                                  parseInt(e.target.value) || 14
                                )
                              }
                              className="w-16 h-8 text-sm"
                            />
                            <ButtonGroupText className="h-8 px-2 text-xs text-muted-foreground">
                              px
                            </ButtonGroupText>
                          </ButtonGroup>
                        </div>
                      )}
                      {capabilities.fontWeight && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500 w-24">
                            Font Weight
                          </span>
                          <Select
                            value={String(currentStyle.fontWeight ?? '400')}
                            onValueChange={(value) =>
                              handlePropertyChange('fontWeight', value as StyleProperties['fontWeight'])
                            }
                          >
                            <SelectTrigger className="w-32 h-8 text-sm">
                              <SelectValue placeholder="Select weight" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="400">Normal</SelectItem>
                              <SelectItem value="500">Medium</SelectItem>
                              <SelectItem value="600">Semibold</SelectItem>
                              <SelectItem value="700">Bold</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                      )}
                      {capabilities.color && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500 w-24">
                            Color
                          </span>
                          <ColorControl
                            value={currentStyle.color ?? '#000000'}
                            onChange={(v) => handlePropertyChange('color', v)}
                          />
                        </div>
                      )}
                      {capabilities.lineHeight && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500 w-24">
                            Line Height
                          </span>
                          <Input
                            type="number"
                            min={1}
                            max={3}
                            step={0.1}
                            value={currentStyle.lineHeight ?? 1.4}
                            onChange={(e) =>
                              handlePropertyChange(
                                'lineHeight',
                                parseFloat(e.target.value) || 1.4
                              )
                            }
                            className="w-20 h-8 text-sm"
                          />
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* Spacing */}
                {capabilities && (capabilities.margin || capabilities.padding) && (
                  <div>
                    <h4 className="text-sm font-medium mb-3">Spacing</h4>
                    {capabilities.margin && (
                      <SpacingControl
                        label="Margin"
                        value={
                          currentStyle.margin ?? {
                            top: 0,
                            right: 0,
                            bottom: 0,
                            left: 0,
                          }
                        }
                        onChange={(v) => handlePropertyChange('margin', v)}
                        color="orange"
                      />
                    )}
                    {capabilities.padding && (
                      <SpacingControl
                        label="Padding"
                        value={
                          currentStyle.padding ?? {
                            top: 0,
                            right: 0,
                            bottom: 0,
                            left: 0,
                          }
                        }
                        onChange={(v) => handlePropertyChange('padding', v)}
                        color="blue"
                        className="mt-4"
                      />
                    )}
                  </div>
                )}

                {/* Background */}
                {capabilities && capabilities.backgroundColor && (
                  <div>
                    <h4 className="text-sm font-medium mb-3">Background</h4>
                    <div className="flex items-center gap-2">
                      <span className="text-sm text-gray-500 w-24">
                        Color
                      </span>
                      <ColorControl
                        value={currentStyle.backgroundColor ?? '#ffffff'}
                        onChange={(v) =>
                          handlePropertyChange('backgroundColor', v)
                        }
                      />
                    </div>
                  </div>
                )}

                {/* Border */}
                {capabilities && capabilities.border && (
                  <div>
                    <h4 className="text-sm font-medium mb-3">Border</h4>
                    <SpacingControl
                      label="Width"
                      value={
                        currentStyle.border ?? {
                          top: 0,
                          right: 0,
                          bottom: 0,
                          left: 0,
                        }
                      }
                      onChange={(v) => handlePropertyChange('border', v)}
                      color="purple"
                    />
                    {capabilities.borderRadius !== false && (
                      <div className="flex items-center gap-2 mt-3">
                        <span className="text-sm text-gray-500 w-24">
                          Radius
                        </span>
                        <ButtonGroup>
                          <Input
                            type="number"
                            min={0}
                            max={50}
                            value={currentStyle.borderRadius ?? 0}
                            onChange={(e) =>
                              handlePropertyChange(
                                'borderRadius',
                                parseInt(e.target.value) || 0
                              )
                            }
                            className="w-16 h-8 text-sm"
                          />
                          <ButtonGroupText className="h-8 px-2 text-xs text-muted-foreground">
                            px
                          </ButtonGroupText>
                        </ButtonGroup>
                      </div>
                    )}
                    <div className="flex items-center gap-2 mt-3">
                      <span className="text-sm text-gray-500 w-24">
                        Color
                      </span>
                      <ColorControl
                        value={currentStyle.borderColor ?? '#d1d5db'}
                        onChange={(v) =>
                          handlePropertyChange('borderColor', v)
                        }
                      />
                    </div>
                  </div>
                )}

                {/* Dimensions */}
                {capabilities && (capabilities.width || capabilities.minHeight) && (
                  <div>
                    <h4 className="text-sm font-medium mb-3">Dimensions</h4>
                    <div className="space-y-3">
                      {capabilities.minHeight && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500 w-24">
                            Min Height
                          </span>
                          <ButtonGroup>
                            <Input
                              type="number"
                              min={0}
                              max={500}
                              value={currentStyle.minHeight ?? 0}
                              onChange={(e) =>
                                handlePropertyChange(
                                  'minHeight',
                                  parseInt(e.target.value) || 0
                                )
                              }
                              className="w-16 h-8 text-sm"
                            />
                            <ButtonGroupText className="h-8 px-2 text-xs text-muted-foreground">
                              px
                            </ButtonGroupText>
                          </ButtonGroup>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
