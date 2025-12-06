---
name: 03-ui-components
status: not-started
---

# Subtask 3: UI Components

## Goal

Create the UI components for editing global and individual element styles. This includes the NodeStylePopover, property controls, and porting the SpacingCompass from the Email Builder.

## Success Criteria

- [ ] `LinkedPropertyInput` component (link/unlink button per property)
- [ ] `StylePropertyControls` component (renders controls based on capabilities)
- [ ] `NodeStylePopover` component (global/individual tabs)
- [ ] `SpacingCompass` ported and adapted from Email Builder
- [ ] Integration with `FloatingPanel` property editor
- [ ] Proper debouncing for color/spacing inputs
- [ ] Visual indicators for linked/unlinked state

## Files to Create

```
/src/react/admin/components/ui/style-editor/
├── LinkedPropertyInput.tsx       # Property input with link/unlink toggle
├── StylePropertyControls.tsx     # Renders controls based on capabilities
├── NodeStylePopover.tsx          # Main style editing popover
├── NodeStyleTabs.tsx             # Tabs for switching between nodes
├── SpacingControl.tsx            # Simplified spacing compass
├── ColorControl.tsx              # Color picker with debouncing
├── FontControls.tsx              # Typography controls group
├── index.ts                      # Barrel export
```

## Files to Modify

```
/src/react/admin/apps/form-builder-v2/components/property-panels/
├── FloatingPanel.tsx             # Add "Styles" section
```

## Implementation Details

### 1. LinkedPropertyInput.tsx

The core component for any property with link/unlink capability:

```tsx
import React from 'react';
import { Link, Unlink } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';

interface LinkedPropertyInputProps<T> {
  label: string;
  value: T;
  globalValue: T;
  isLinked: boolean;
  onLink: () => void;
  onUnlink: () => void;
  onChange: (value: T) => void;
  renderInput: (props: {
    value: T;
    onChange: (value: T) => void;
    disabled: boolean;
  }) => React.ReactNode;
  className?: string;
}

export function LinkedPropertyInput<T>({
  label,
  value,
  globalValue,
  isLinked,
  onLink,
  onUnlink,
  onChange,
  renderInput,
  className,
}: LinkedPropertyInputProps<T>) {
  const displayValue = isLinked ? globalValue : value;

  const handleToggleLink = () => {
    if (isLinked) {
      // Unlinking: copy current global value as the override
      onUnlink();
    } else {
      // Linking: remove override, revert to global
      onLink();
    }
  };

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <span className="text-sm text-muted-foreground w-24 flex-shrink-0">
        {label}
      </span>

      <div className="flex-1">
        {renderInput({
          value: displayValue,
          onChange,
          disabled: isLinked,
        })}
      </div>

      <Tooltip>
        <TooltipTrigger asChild>
          <Button
            variant="ghost"
            size="icon"
            className={cn(
              'h-8 w-8 flex-shrink-0',
              isLinked
                ? 'text-primary bg-primary/10'
                : 'text-muted-foreground hover:text-foreground'
            )}
            onClick={handleToggleLink}
          >
            {isLinked ? (
              <Link className="h-4 w-4" />
            ) : (
              <Unlink className="h-4 w-4" />
            )}
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          {isLinked ? 'Unlink from global (override)' : 'Link to global (use theme)'}
        </TooltipContent>
      </Tooltip>
    </div>
  );
}
```

### 2. ColorControl.tsx

Color picker with debouncing (adapted from SpacingCompass pattern):

```tsx
import React, { useState, useCallback, useRef, useEffect } from 'react';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';

interface ColorControlProps {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  className?: string;
}

export function ColorControl({
  value,
  onChange,
  disabled = false,
  className,
}: ColorControlProps) {
  const [localColor, setLocalColor] = useState(value);
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Sync local state when prop changes
  useEffect(() => {
    setLocalColor(value);
  }, [value]);

  const handleChange = useCallback(
    (newColor: string) => {
      setLocalColor(newColor); // Immediate UI update

      // Debounce the actual update
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
      timeoutRef.current = setTimeout(() => {
        onChange(newColor);
      }, 150);
    },
    [onChange]
  );

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <input
        type="color"
        value={localColor}
        onChange={(e) => handleChange(e.target.value)}
        disabled={disabled}
        className={cn(
          'w-10 h-10 rounded border-2 cursor-pointer transition-all',
          disabled
            ? 'opacity-50 cursor-not-allowed border-muted'
            : 'border-border hover:border-primary'
        )}
      />
      <Input
        type="text"
        value={localColor}
        onChange={(e) => handleChange(e.target.value)}
        disabled={disabled}
        className="w-24 font-mono text-sm"
        placeholder="#000000"
      />
    </div>
  );
}
```

### 3. SpacingControl.tsx

Simplified spacing control (adapted from Email Builder):

```tsx
import React, { useState } from 'react';
import { Link, Unlink } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spacing } from '@/schemas/styles';

interface SpacingControlProps {
  label: string;
  value: Spacing;
  onChange: (value: Spacing) => void;
  disabled?: boolean;
  color?: 'orange' | 'purple' | 'blue';
  className?: string;
}

const colorClasses = {
  orange: {
    bg: 'bg-orange-50',
    border: 'border-orange-300',
    text: 'text-orange-600',
    input: 'border-orange-400 focus:ring-orange-500',
    linked: 'bg-orange-500 text-white',
  },
  purple: {
    bg: 'bg-purple-50',
    border: 'border-purple-300',
    text: 'text-purple-600',
    input: 'border-purple-400 focus:ring-purple-500',
    linked: 'bg-purple-500 text-white',
  },
  blue: {
    bg: 'bg-blue-50',
    border: 'border-blue-300',
    text: 'text-blue-600',
    input: 'border-blue-400 focus:ring-blue-500',
    linked: 'bg-blue-500 text-white',
  },
};

export function SpacingControl({
  label,
  value,
  onChange,
  disabled = false,
  color = 'blue',
  className,
}: SpacingControlProps) {
  const [isLinked, setIsLinked] = useState(
    value.top === value.right &&
      value.top === value.bottom &&
      value.top === value.left
  );

  const colors = colorClasses[color];

  const handleChange = (side: keyof Spacing, newValue: number) => {
    if (isLinked) {
      onChange({ top: newValue, right: newValue, bottom: newValue, left: newValue });
    } else {
      onChange({ ...value, [side]: newValue });
    }
  };

  return (
    <div className={cn('space-y-2', className)}>
      <div className="flex items-center justify-between">
        <span className={cn('text-sm font-medium', colors.text)}>{label}</span>
        <Button
          variant="ghost"
          size="icon"
          className={cn('h-6 w-6', isLinked ? colors.linked : '')}
          onClick={() => setIsLinked(!isLinked)}
          disabled={disabled}
        >
          {isLinked ? <Link className="h-3 w-3" /> : <Unlink className="h-3 w-3" />}
        </Button>
      </div>

      <div
        className={cn(
          'grid gap-2 p-3 rounded-lg border',
          colors.bg,
          colors.border,
          disabled && 'opacity-50'
        )}
      >
        {isLinked ? (
          // Single input when linked
          <div className="flex items-center justify-center">
            <Input
              type="number"
              min={0}
              max={200}
              value={value.top}
              onChange={(e) => handleChange('top', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-16 text-center', colors.input)}
            />
            <span className="ml-2 text-sm text-muted-foreground">all sides</span>
          </div>
        ) : (
          // Four inputs when unlinked
          <div className="grid grid-cols-3 gap-2">
            <div />
            <Input
              type="number"
              min={0}
              max={200}
              value={value.top}
              onChange={(e) => handleChange('top', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="T"
            />
            <div />

            <Input
              type="number"
              min={0}
              max={200}
              value={value.left}
              onChange={(e) => handleChange('left', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="L"
            />
            <div className="flex items-center justify-center">
              <span className="text-xs text-muted-foreground">px</span>
            </div>
            <Input
              type="number"
              min={0}
              max={200}
              value={value.right}
              onChange={(e) => handleChange('right', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="R"
            />

            <div />
            <Input
              type="number"
              min={0}
              max={200}
              value={value.bottom}
              onChange={(e) => handleChange('bottom', parseInt(e.target.value) || 0)}
              disabled={disabled}
              className={cn('w-full text-center', colors.input)}
              placeholder="B"
            />
            <div />
          </div>
        )}
      </div>
    </div>
  );
}
```

### 4. FontControls.tsx

Typography controls group:

```tsx
import React from 'react';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { StyleProperties, NodeStyleCapabilities } from '@/schemas/styles';
import { LinkedPropertyInput } from './LinkedPropertyInput';
import { ColorControl } from './ColorControl';

interface FontControlsProps {
  style: Partial<StyleProperties>;
  globalStyle: Partial<StyleProperties>;
  overrides: Partial<StyleProperties>;
  capabilities: NodeStyleCapabilities;
  onOverride: (property: keyof StyleProperties, value: any) => void;
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
            <Select
              value={value}
              onValueChange={onChange}
              disabled={disabled}
            >
              <SelectTrigger className="w-40">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {FONT_FAMILIES.map((f) => (
                  <SelectItem key={f.value} value={f.value}>
                    {f.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
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
          onChange={(v) => onOverride('fontWeight', v)}
          renderInput={({ value, onChange, disabled }) => (
            <Select
              value={value}
              onValueChange={onChange}
              disabled={disabled}
            >
              <SelectTrigger className="w-32">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {FONT_WEIGHTS.map((w) => (
                  <SelectItem key={w.value} value={w.value}>
                    {w.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
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
```

### 5. NodeStylePopover.tsx

Main style editing popover:

```tsx
import React, { useState } from 'react';
import { Paintbrush, Link, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import {
  NodeType,
  StyleProperties,
  getNodeCapabilities,
  getElementNodes,
  styleRegistry,
} from '@/schemas/styles';
import { useElementStyle } from '@/apps/form-builder-v2/hooks/useElementStyle';
import { useElementsStore } from '@/apps/form-builder-v2/store/useElementsStore';
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

  const nodeStyles = useElementStyle(elementId);
  const nodes = getElementNodes(elementType);
  const { setStyleOverride, removeStyleOverride, clearNodeStyleOverrides } =
    useElementsStore();

  // Default to first node if none selected
  const currentNode = activeNode ?? nodes[0];
  const currentNodeStyle = nodeStyles.find((n) => n.nodeType === currentNode);
  const capabilities = getNodeCapabilities(currentNode);

  const overrideCount = nodeStyles.filter((n) => n.hasOverrides).length;

  const handleOverride = (property: keyof StyleProperties, value: any) => {
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
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className={cn('gap-2', className)}
        >
          <Paintbrush className="h-4 w-4" />
          Styles
          {overrideCount > 0 && (
            <Badge variant="secondary" className="ml-1 px-1.5 py-0">
              {overrideCount}
            </Badge>
          )}
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-96 p-0" align="start">
        <Tabs defaultValue="individual" className="w-full">
          <TabsList className="w-full justify-start rounded-none border-b">
            <TabsTrigger value="individual">Individual</TabsTrigger>
            <TabsTrigger value="global">Global Theme</TabsTrigger>
          </TabsList>

          <div className="flex">
            {/* Node selector sidebar */}
            <div className="w-28 border-r bg-muted/30 p-2">
              <ScrollArea className="h-[300px]">
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
              </ScrollArea>
            </div>

            {/* Style controls */}
            <div className="flex-1">
              <TabsContent value="individual" className="m-0 p-4">
                <ScrollArea className="h-[300px]">
                  {currentNodeStyle && (
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
                </ScrollArea>
              </TabsContent>

              <TabsContent value="global" className="m-0 p-4">
                <ScrollArea className="h-[300px]">
                  <p className="text-sm text-muted-foreground mb-4">
                    Changes here affect all elements using this node type.
                  </p>
                  {/* Similar controls but editing global styles directly */}
                  {currentNodeStyle && (
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
                  )}
                </ScrollArea>
              </TabsContent>
            </div>
          </div>
        </Tabs>
      </PopoverContent>
    </Popover>
  );
}
```

### 6. FloatingPanel Integration

Add styles section to FloatingPanel:

```tsx
// In FloatingPanel.tsx, add to the rendered content:

import { NodeStylePopover } from '@/components/ui/style-editor';

// In the panel content, after other sections:
<div className="border-t pt-4 mt-4">
  <div className="flex items-center justify-between mb-2">
    <h3 className="text-sm font-medium">Styles</h3>
  </div>
  <NodeStylePopover
    elementId={selectedElement.id}
    elementType={selectedElement.type}
  />
</div>
```

## Testing

1. **LinkedPropertyInput:**
   - Link/unlink toggle works
   - Visual state changes appropriately
   - Value syncs when linking

2. **SpacingControl:**
   - Linked mode sets all sides
   - Unlinked mode sets individual sides
   - Toggle works correctly

3. **NodeStylePopover:**
   - Opens/closes properly
   - Node selection works
   - Override badge shows correct count
   - Changes persist to store

4. **FloatingPanel integration:**
   - Styles button appears
   - Popover opens in correct position
   - Changes apply to element

## Dependencies

- Subtask 01 (Core Schema & Registry)
- Subtask 02 (Store Integration)
- shadcn/ui components: Button, Popover, Tabs, ScrollArea, Badge, Input, Select

## Notes

- Use debouncing for color and slider inputs (150ms)
- Maintain visual consistency with existing property panels
- Ensure proper focus management in popover
- Consider keyboard navigation for accessibility
