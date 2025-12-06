---
name: 06-migration-polish
status: not-started
---

# Subtask 6: Migration & Polish

## Goal

Migrate existing form styling to the new system, create theme presets, and add polish features like import/export and documentation.

## Success Criteria

- [ ] Existing inline styles migrated to new style system
- [ ] Theme presets created (Light, Dark, Minimal, Modern)
- [ ] Theme import/export functionality
- [ ] Global styles panel in Settings tab
- [ ] Documentation for end users
- [ ] Smooth upgrade path for existing forms

## Files to Create

```
/src/react/admin/schemas/styles/
├── presets/
│   ├── index.ts              # Preset registry
│   ├── light.ts              # Light theme preset
│   ├── dark.ts               # Dark theme preset
│   ├── minimal.ts            # Minimal theme preset
│   └── modern.ts             # Modern theme preset
```

```
/src/react/admin/components/
├── settings/
│   └── GlobalStylesPanel.tsx  # Global styles editor in Settings
```

## Files to Modify

```
/src/react/admin/apps/form-builder-v2/
├── components/tabs/SettingsTab.tsx  # Add global styles section
```

## Implementation Details

### 1. Theme Presets

#### presets/light.ts

```typescript
import { NodeType, StyleProperties } from '../types';

/**
 * Light Theme Preset
 * Clean, professional look with subtle grays
 */
export const LIGHT_PRESET: Record<NodeType, Partial<StyleProperties>> = {
  label: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontWeight: '500',
    lineHeight: 1.4,
    color: '#1f2937',
    margin: { top: 0, right: 0, bottom: 4, left: 0 },
  },
  description: {
    fontSize: 13,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    lineHeight: 1.4,
    color: '#6b7280',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  input: {
    fontSize: 14,
    fontFamily: 'inherit',
    textAlign: 'left',
    color: '#1f2937',
    padding: { top: 10, right: 14, bottom: 10, left: 14 },
    border: { top: 1, right: 1, bottom: 1, left: 1 },
    borderStyle: 'solid',
    borderColor: '#d1d5db',
    borderRadius: 6,
    backgroundColor: '#ffffff',
    width: '100%',
    minHeight: 42,
  },
  placeholder: {
    fontStyle: 'normal',
    color: '#9ca3af',
  },
  error: {
    fontSize: 13,
    fontWeight: '500',
    color: '#dc2626',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  required: {
    fontSize: 14,
    fontWeight: '400',
    color: '#dc2626',
  },
  fieldContainer: {
    margin: { top: 0, right: 0, bottom: 20, left: 0 },
    padding: { top: 0, right: 0, bottom: 0, left: 0 },
    border: { top: 0, right: 0, bottom: 0, left: 0 },
    borderRadius: 0,
    backgroundColor: 'transparent',
    width: '100%',
  },
  heading: {
    fontSize: 24,
    fontFamily: 'inherit',
    fontWeight: '600',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.3,
    letterSpacing: 0,
    color: '#111827',
    margin: { top: 0, right: 0, bottom: 12, left: 0 },
  },
  paragraph: {
    fontSize: 15,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.6,
    color: '#4b5563',
    margin: { top: 0, right: 0, bottom: 16, left: 0 },
  },
  button: {
    fontSize: 15,
    fontFamily: 'inherit',
    fontWeight: '500',
    textAlign: 'center',
    letterSpacing: 0,
    color: '#ffffff',
    margin: { top: 12, right: 0, bottom: 0, left: 0 },
    padding: { top: 12, right: 24, bottom: 12, left: 24 },
    border: { top: 0, right: 0, bottom: 0, left: 0 },
    borderRadius: 6,
    backgroundColor: '#2563eb',
    minHeight: 44,
  },
  divider: {
    color: '#e5e7eb',
    margin: { top: 20, right: 0, bottom: 20, left: 0 },
    border: { top: 1, right: 0, bottom: 0, left: 0 },
    width: '100%',
  },
  optionLabel: {
    fontSize: 14,
    fontFamily: 'inherit',
    lineHeight: 1.4,
    color: '#374151',
  },
  cardContainer: {
    margin: { top: 0, right: 8, bottom: 8, left: 0 },
    padding: { top: 16, right: 16, bottom: 16, left: 16 },
    border: { top: 2, right: 2, bottom: 2, left: 2 },
    borderRadius: 8,
    backgroundColor: '#ffffff',
    width: 'auto',
    minHeight: 80,
  },
};

export const LIGHT_PRESET_META = {
  id: 'light',
  name: 'Light',
  description: 'Clean, professional look with subtle grays',
};
```

#### presets/dark.ts

```typescript
import { NodeType, StyleProperties } from '../types';

/**
 * Dark Theme Preset
 * Modern dark mode with good contrast
 */
export const DARK_PRESET: Record<NodeType, Partial<StyleProperties>> = {
  label: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontWeight: '500',
    lineHeight: 1.4,
    color: '#f3f4f6',
    margin: { top: 0, right: 0, bottom: 4, left: 0 },
  },
  description: {
    fontSize: 13,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    lineHeight: 1.4,
    color: '#9ca3af',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  input: {
    fontSize: 14,
    fontFamily: 'inherit',
    textAlign: 'left',
    color: '#f9fafb',
    padding: { top: 10, right: 14, bottom: 10, left: 14 },
    border: { top: 1, right: 1, bottom: 1, left: 1 },
    borderStyle: 'solid',
    borderColor: '#4b5563',
    borderRadius: 6,
    backgroundColor: '#1f2937',
    width: '100%',
    minHeight: 42,
  },
  placeholder: {
    fontStyle: 'normal',
    color: '#6b7280',
  },
  error: {
    fontSize: 13,
    fontWeight: '500',
    color: '#f87171',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  required: {
    fontSize: 14,
    fontWeight: '400',
    color: '#f87171',
  },
  fieldContainer: {
    margin: { top: 0, right: 0, bottom: 20, left: 0 },
    padding: { top: 0, right: 0, bottom: 0, left: 0 },
    border: { top: 0, right: 0, bottom: 0, left: 0 },
    borderRadius: 0,
    backgroundColor: 'transparent',
    width: '100%',
  },
  heading: {
    fontSize: 24,
    fontFamily: 'inherit',
    fontWeight: '600',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.3,
    letterSpacing: 0,
    color: '#f9fafb',
    margin: { top: 0, right: 0, bottom: 12, left: 0 },
  },
  paragraph: {
    fontSize: 15,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.6,
    color: '#d1d5db',
    margin: { top: 0, right: 0, bottom: 16, left: 0 },
  },
  button: {
    fontSize: 15,
    fontFamily: 'inherit',
    fontWeight: '500',
    textAlign: 'center',
    letterSpacing: 0,
    color: '#ffffff',
    margin: { top: 12, right: 0, bottom: 0, left: 0 },
    padding: { top: 12, right: 24, bottom: 12, left: 24 },
    border: { top: 0, right: 0, bottom: 0, left: 0 },
    borderRadius: 6,
    backgroundColor: '#3b82f6',
    minHeight: 44,
  },
  divider: {
    color: '#374151',
    margin: { top: 20, right: 0, bottom: 20, left: 0 },
    border: { top: 1, right: 0, bottom: 0, left: 0 },
    width: '100%',
  },
  optionLabel: {
    fontSize: 14,
    fontFamily: 'inherit',
    lineHeight: 1.4,
    color: '#e5e7eb',
  },
  cardContainer: {
    margin: { top: 0, right: 8, bottom: 8, left: 0 },
    padding: { top: 16, right: 16, bottom: 16, left: 16 },
    border: { top: 2, right: 2, bottom: 2, left: 2 },
    borderRadius: 8,
    backgroundColor: '#1f2937',
    width: 'auto',
    minHeight: 80,
  },
};

export const DARK_PRESET_META = {
  id: 'dark',
  name: 'Dark',
  description: 'Modern dark mode with good contrast',
};
```

#### presets/index.ts

```typescript
import { NodeType, StyleProperties, styleRegistry } from '../';
import { LIGHT_PRESET, LIGHT_PRESET_META } from './light';
import { DARK_PRESET, DARK_PRESET_META } from './dark';

export interface ThemePreset {
  id: string;
  name: string;
  description: string;
  styles: Record<NodeType, Partial<StyleProperties>>;
}

export const THEME_PRESETS: ThemePreset[] = [
  { ...LIGHT_PRESET_META, styles: LIGHT_PRESET },
  { ...DARK_PRESET_META, styles: DARK_PRESET },
];

/**
 * Apply a preset to the global style registry.
 */
export function applyPreset(presetId: string): boolean {
  const preset = THEME_PRESETS.find((p) => p.id === presetId);
  if (!preset) return false;

  // Apply all styles from preset
  Object.entries(preset.styles).forEach(([nodeType, styles]) => {
    styleRegistry.setGlobalStyle(nodeType as NodeType, styles);
  });

  return true;
}

/**
 * Get a preset by ID.
 */
export function getPreset(presetId: string): ThemePreset | undefined {
  return THEME_PRESETS.find((p) => p.id === presetId);
}

/**
 * List all available presets.
 */
export function listPresets(): ThemePreset[] {
  return THEME_PRESETS;
}
```

### 2. GlobalStylesPanel.tsx

```tsx
import React, { useState } from 'react';
import { Palette, Download, Upload, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { toast } from 'sonner';
import {
  styleRegistry,
  NodeType,
  NODE_STYLE_CAPABILITIES,
} from '@/schemas/styles';
import { THEME_PRESETS, applyPreset } from '@/schemas/styles/presets';
import { useGlobalStyles } from '@/apps/form-builder-v2/hooks/useGlobalStyles';
import { FontControls } from '@/components/ui/style-editor/FontControls';
import { SpacingControl } from '@/components/ui/style-editor/SpacingControl';
import { ColorControl } from '@/components/ui/style-editor/ColorControl';

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
  const globalStyles = useGlobalStyles();

  const nodeTypes = Object.keys(NODE_STYLE_CAPABILITIES) as NodeType[];
  const capabilities = NODE_STYLE_CAPABILITIES[activeNode];
  const currentStyle = globalStyles[activeNode] ?? {};

  const handlePropertyChange = (property: string, value: any) => {
    styleRegistry.setGlobalProperty(activeNode, property as keyof typeof currentStyle, value);
  };

  const handleApplyPreset = () => {
    if (selectedPreset) {
      applyPreset(selectedPreset);
      toast.success('Theme preset applied');
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
    toast.success('Theme exported');
  };

  const handleImport = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = (e) => {
      const file = (e.target as HTMLInputElement).files?.[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          const json = e.target?.result as string;
          styleRegistry.importStyles(json);
          toast.success('Theme imported');
        };
        reader.readAsText(file);
      }
    };
    input.click();
  };

  const handleReset = () => {
    styleRegistry.resetAllToDefaults();
    toast.success('Styles reset to defaults');
  };

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2">
              <Palette className="h-5 w-5" />
              Global Styles
            </CardTitle>
            <CardDescription>
              Theme settings that apply to all form elements
            </CardDescription>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={handleExport}>
              <Download className="h-4 w-4 mr-1" />
              Export
            </Button>
            <Button variant="outline" size="sm" onClick={handleImport}>
              <Upload className="h-4 w-4 mr-1" />
              Import
            </Button>
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="outline" size="sm">
                  <RotateCcw className="h-4 w-4 mr-1" />
                  Reset
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Reset to defaults?</AlertDialogTitle>
                  <AlertDialogDescription>
                    This will reset all global styles to their default values.
                    This action cannot be undone.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Cancel</AlertDialogCancel>
                  <AlertDialogAction onClick={handleReset}>
                    Reset
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>
      </CardHeader>

      <CardContent>
        {/* Preset selector */}
        <div className="flex items-center gap-4 mb-6 p-4 bg-muted/50 rounded-lg">
          <span className="text-sm font-medium">Quick Start:</span>
          <Select value={selectedPreset} onValueChange={setSelectedPreset}>
            <SelectTrigger className="w-48">
              <SelectValue placeholder="Choose a preset..." />
            </SelectTrigger>
            <SelectContent>
              {THEME_PRESETS.map((preset) => (
                <SelectItem key={preset.id} value={preset.id}>
                  {preset.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Button
            size="sm"
            onClick={handleApplyPreset}
            disabled={!selectedPreset}
          >
            Apply Preset
          </Button>
        </div>

        {/* Node type tabs and controls */}
        <div className="flex gap-6">
          {/* Node selector */}
          <div className="w-48 border-r pr-4">
            <h4 className="text-sm font-medium mb-3">Element Type</h4>
            <ScrollArea className="h-[400px]">
              <div className="space-y-1">
                {nodeTypes.map((nodeType) => (
                  <button
                    key={nodeType}
                    onClick={() => setActiveNode(nodeType)}
                    className={`w-full text-left px-3 py-2 rounded text-sm transition-colors ${
                      activeNode === nodeType
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-muted'
                    }`}
                  >
                    {NODE_LABELS[nodeType]}
                  </button>
                ))}
              </div>
            </ScrollArea>
          </div>

          {/* Style controls */}
          <div className="flex-1">
            <ScrollArea className="h-[400px]">
              <div className="space-y-6 pr-4">
                {/* Typography */}
                {(capabilities.fontSize ||
                  capabilities.fontFamily ||
                  capabilities.color) && (
                  <div>
                    <h4 className="text-sm font-medium mb-3">Typography</h4>
                    <div className="space-y-3">
                      {capabilities.fontSize && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-muted-foreground w-24">
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
                            className="w-20 px-2 py-1 border rounded"
                          />
                          <span className="text-sm text-muted-foreground">px</span>
                        </div>
                      )}
                      {capabilities.color && (
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-muted-foreground w-24">
                            Color
                          </span>
                          <ColorControl
                            value={currentStyle.color ?? '#000000'}
                            onChange={(v) => handlePropertyChange('color', v)}
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
                      <span className="text-sm text-muted-foreground w-24">
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
                        <span className="text-sm text-muted-foreground w-24">
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
                          className="w-20 px-2 py-1 border rounded"
                        />
                        <span className="text-sm text-muted-foreground">px</span>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </ScrollArea>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
```

### 3. Migration Strategy

For existing forms that have inline styles:

```typescript
// Migration utility
import { FormElement } from '@/apps/form-builder-v2/types';
import { NodeType, styleRegistry } from '@/schemas/styles';

/**
 * Migrate old inline styles to new style system.
 * Call this when loading a form.
 */
export function migrateElementStyles(element: FormElement): FormElement {
  if (!element.properties) return element;

  const styleOverrides: FormElement['styleOverrides'] = {};

  // Map old property names to new node + property
  const migrationMap: Record<string, { node: NodeType; property: string }> = {
    labelColor: { node: 'label', property: 'color' },
    labelFontSize: { node: 'label', property: 'fontSize' },
    inputBackgroundColor: { node: 'input', property: 'backgroundColor' },
    inputBorderColor: { node: 'input', property: 'borderColor' },
    // Add more mappings as needed
  };

  Object.entries(element.properties).forEach(([key, value]) => {
    const mapping = migrationMap[key];
    if (mapping) {
      if (!styleOverrides[mapping.node]) {
        styleOverrides[mapping.node] = {};
      }
      styleOverrides[mapping.node]![mapping.property as keyof typeof styleOverrides[typeof mapping.node]] = value;

      // Remove from properties
      delete element.properties[key];
    }
  });

  if (Object.keys(styleOverrides).length > 0) {
    return {
      ...element,
      styleOverrides,
    };
  }

  return element;
}
```

## Testing

1. **Presets:**
   - Apply Light preset
   - Apply Dark preset
   - Verify all elements update

2. **Import/Export:**
   - Export current theme
   - Modify some styles
   - Import the exported theme
   - Verify styles restored

3. **GlobalStylesPanel:**
   - Changes apply immediately
   - Node switching works
   - All controls render correctly

4. **Migration:**
   - Load form with old inline styles
   - Verify migration runs
   - Verify styles appear correctly

## Dependencies

- Subtask 01-05 completed
- sonner for toast notifications

## Notes

- Presets are stored in code, not database
- User custom themes are stored in form settings
- Migration should be non-destructive (keep copy of old data)
- Consider adding "Undo preset" functionality
