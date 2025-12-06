import React, { useState, useRef } from 'react';
import { Palette, Download, Upload, RotateCcw } from 'lucide-react';
import {
  styleRegistry,
  NodeType,
  NODE_STYLE_CAPABILITIES,
  StyleProperties,
} from '../../schemas/styles';
import { THEME_PRESETS, applyPreset } from '../../schemas/styles/presets';
import { useGlobalStyles } from '../../apps/form-builder-v2/hooks/useGlobalStyles';
import { SpacingControl } from '../ui/style-editor/SpacingControl';
import { ColorControl } from '../ui/style-editor/ColorControl';

const NODE_LABELS: Record<NodeType, string> = {
  label: 'Field Labels',
  description: 'Descriptions',
  input: 'Input Fields',
  placeholder: 'Placeholders',
  error: 'Error Messages',
  required: 'Required Indicator',
  fieldContainer: 'Field Containers',
  heading: 'Headings',
  paragraph: 'Paragraphs',
  button: 'Buttons',
  divider: 'Dividers',
  optionLabel: 'Option Labels',
  cardContainer: 'Cards',
};

export function GlobalStylesPanel() {
  const [activeNode, setActiveNode] = useState<NodeType>('label');
  const [selectedPreset, setSelectedPreset] = useState<string>('');
  const [showResetDialog, setShowResetDialog] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const globalStyles = useGlobalStyles();

  const nodeTypes = Object.keys(NODE_STYLE_CAPABILITIES) as NodeType[];
  const capabilities = NODE_STYLE_CAPABILITIES[activeNode];
  const currentStyle = globalStyles[activeNode] ?? {};

  const handlePropertyChange = (property: string, value: StyleProperties[keyof StyleProperties]) => {
    styleRegistry.setGlobalProperty(activeNode, property as keyof StyleProperties, value);
  };

  const handleApplyPreset = () => {
    if (selectedPreset) {
      applyPreset(selectedPreset);
    }
  };

  const handleExport = () => {
    const json = styleRegistry.exportStyles();
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'form-theme.json';
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

  const handleReset = () => {
    styleRegistry.resetAllToDefaults();
    setShowResetDialog(false);
  };

  return (
    <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
      {/* Header */}
      <div className="px-6 py-4 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold flex items-center gap-2">
              <Palette className="h-5 w-5" />
              Global Styles
            </h3>
            <p className="text-sm text-gray-500 mt-1">
              Theme settings that apply to all form elements
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={handleExport}
              className="inline-flex items-center gap-1 px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
            >
              <Download className="h-4 w-4" />
              Export
            </button>
            <button
              onClick={handleImport}
              className="inline-flex items-center gap-1 px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
            >
              <Upload className="h-4 w-4" />
              Import
            </button>
            <input
              ref={fileInputRef}
              type="file"
              accept=".json"
              onChange={handleFileChange}
              className="hidden"
            />
            <button
              onClick={() => setShowResetDialog(true)}
              className="inline-flex items-center gap-1 px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
            >
              <RotateCcw className="h-4 w-4" />
              Reset
            </button>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="p-6">
        {/* Preset selector */}
        <div className="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
          <span className="text-sm font-medium">Quick Start:</span>
          <select
            value={selectedPreset}
            onChange={(e) => setSelectedPreset(e.target.value)}
            className="w-48 px-3 py-1.5 border border-gray-300 rounded-md text-sm"
          >
            <option value="">Choose a preset...</option>
            {THEME_PRESETS.map((preset) => (
              <option key={preset.id} value={preset.id}>
                {preset.name} - {preset.description}
              </option>
            ))}
          </select>
          <button
            onClick={handleApplyPreset}
            disabled={!selectedPreset}
            className="px-4 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Apply Preset
          </button>
        </div>

        {/* Node type tabs and controls */}
        <div className="flex gap-6">
          {/* Node selector */}
          <div className="w-48 border-r border-gray-200 pr-4">
            <h4 className="text-sm font-medium mb-3">Element Type</h4>
            <div className="h-[400px] overflow-y-auto">
              <div className="space-y-1">
                {nodeTypes.map((nodeType) => (
                  <button
                    key={nodeType}
                    onClick={() => setActiveNode(nodeType)}
                    className={`w-full text-left px-3 py-2 rounded text-sm transition-colors ${
                      activeNode === nodeType
                        ? 'bg-blue-600 text-white'
                        : 'hover:bg-gray-100'
                    }`}
                  >
                    {NODE_LABELS[nodeType]}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Style controls */}
          <div className="flex-1">
            <div className="h-[400px] overflow-y-auto pr-4">
              <div className="space-y-6">
                {/* Typography */}
                {(capabilities.fontSize ||
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
                          <input
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
                            className="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                          />
                          <span className="text-sm text-gray-500">px</span>
                        </div>
                      )}
                      {capabilities.fontWeight && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500 w-24">
                            Font Weight
                          </span>
                          <select
                            value={currentStyle.fontWeight ?? '400'}
                            onChange={(e) =>
                              handlePropertyChange('fontWeight', e.target.value as StyleProperties['fontWeight'])
                            }
                            className="w-32 px-2 py-1 border border-gray-300 rounded text-sm"
                          >
                            <option value="400">Normal</option>
                            <option value="500">Medium</option>
                            <option value="600">Semibold</option>
                            <option value="700">Bold</option>
                          </select>
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
                          <input
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
                            className="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                          />
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* Spacing */}
                {(capabilities.margin || capabilities.padding) && (
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
                {capabilities.backgroundColor && (
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
                {capabilities.border && (
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
                        <input
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
                          className="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                        />
                        <span className="text-sm text-gray-500">px</span>
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
                {(capabilities.width || capabilities.minHeight) && (
                  <div>
                    <h4 className="text-sm font-medium mb-3">Dimensions</h4>
                    <div className="space-y-3">
                      {capabilities.minHeight && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500 w-24">
                            Min Height
                          </span>
                          <input
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
                            className="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                          />
                          <span className="text-sm text-gray-500">px</span>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Reset confirmation dialog */}
      {showResetDialog && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md mx-4">
            <h4 className="text-lg font-semibold mb-2">Reset to defaults?</h4>
            <p className="text-sm text-gray-500 mb-4">
              This will reset all global styles to their default values.
              This action cannot be undone.
            </p>
            <div className="flex justify-end gap-2">
              <button
                onClick={() => setShowResetDialog(false)}
                className="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={handleReset}
                className="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700"
              >
                Reset
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
