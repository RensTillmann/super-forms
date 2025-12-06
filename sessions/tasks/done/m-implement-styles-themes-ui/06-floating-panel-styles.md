---
name: 06-floating-panel-styles
parent: m-implement-styles-themes-ui
status: pending
created: 2025-12-06
---

# Subtask 6: FloatingPanel Styles Section

## Goal

Add a collapsible "Element Styles" section to the FloatingPanel that allows users to override global styles for individual elements. Include link/unlink UI to show whether a property inherits from global or has a custom value.

## Success Criteria

- [ ] Styles section added after property editor in FloatingPanel
- [ ] NodeStyleSection component for each node type the element contains
- [ ] Link/unlink toggle per style property
- [ ] Visual distinction: linked (inherits global) vs unlinked (custom value)
- [ ] Override values stored in element.styleOverrides
- [ ] Changes update ElementRenderer preview immediately
- [ ] "Reset to Global" button to clear all overrides

## Technical Specification

### FloatingPanel Modification

Add styles section after the SchemaPropertyPanel in `/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`:

```tsx
import { Palette, Link2, Unlink2 } from 'lucide-react';
import { ElementStylesSection } from './ElementStylesSection';

// In the content area, after SchemaPropertyPanel:
<div className="flex-1 overflow-y-auto p-4">
  {hasSchema ? (
    <>
      <SchemaPropertyPanel
        elementType={element.type}
        properties={element.properties || {}}
        onPropertyChange={onPropertyChange}
      />

      {/* Element Styles Section */}
      <ElementStylesSection
        elementId={element.id}
        elementType={element.type}
        styleOverrides={element.styleOverrides}
        onOverrideChange={handleStyleOverrideChange}
        onResetToGlobal={handleResetToGlobal}
      />
    </>
  ) : (
    // ... legacy fallback
  )}
</div>
```

### ElementStylesSection Component

```tsx
// /src/react/admin/apps/form-builder-v2/components/property-panels/ElementStylesSection.tsx

import React, { useState } from 'react';
import { ChevronDown, ChevronRight, Palette, RotateCcw } from 'lucide-react';
import { getElementNodes } from '../../../../schemas/styles/elementNodes';
import { NodeStyleEditor } from './NodeStyleEditor';
import { cn } from '../../../../lib/utils';

interface ElementStylesSectionProps {
  elementId: string;
  elementType: string;
  styleOverrides?: Record<string, Record<string, unknown>>;
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
                  {nodeType}
                  {hasOverrides && <span className="ml-1 text-orange-400">•</span>}
                </button>
              );
            })}
          </div>

          {/* Node Style Editor */}
          {activeNode && (
            <NodeStyleEditor
              elementId={elementId}
              nodeType={activeNode}
              overrides={styleOverrides[activeNode] || {}}
              onOverrideChange={(property, value) =>
                onOverrideChange(activeNode, property, value)
              }
              onRemoveOverride={(property) =>
                onOverrideChange(activeNode, property, undefined)
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
```

### NodeStyleEditor Component

```tsx
// /src/react/admin/apps/form-builder-v2/components/property-panels/NodeStyleEditor.tsx

import React from 'react';
import { Link2, Unlink2 } from 'lucide-react';
import {
  NODE_STYLE_CAPABILITIES,
  NodeType,
  StyleProperties,
} from '../../../../schemas/styles';
import { usePropertyValues } from '../../hooks/useResolvedStyle';
import { SpacingControl } from '../../../../components/ui/style-editor/SpacingControl';
import { ColorControl } from '../../../../components/ui/style-editor/ColorControl';
import { cn } from '../../../../lib/utils';

interface NodeStyleEditorProps {
  elementId: string;
  nodeType: string;
  overrides: Record<string, unknown>;
  onOverrideChange: (property: string, value: unknown) => void;
  onRemoveOverride: (property: string) => void;
}

export const NodeStyleEditor: React.FC<NodeStyleEditorProps> = ({
  elementId,
  nodeType,
  overrides,
  onOverrideChange,
  onRemoveOverride,
}) => {
  const capabilities = NODE_STYLE_CAPABILITIES[nodeType as NodeType];

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
          overrides={overrides}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <input
              type="number"
              value={value ?? ''}
              onChange={(e) => onChange(Number(e.target.value))}
              className="w-20 px-2 py-1 text-sm border rounded"
              placeholder="14"
            />
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
          overrides={overrides}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <ColorControl
              value={value as string}
              onChange={onChange}
              size="sm"
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
          overrides={overrides}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <ColorControl
              value={value as string}
              onChange={onChange}
              size="sm"
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
          overrides={overrides}
          onOverride={onOverrideChange}
          onUnlink={onRemoveOverride}
        >
          {(value, onChange) => (
            <input
              type="number"
              value={value ?? ''}
              onChange={(e) => onChange(Number(e.target.value))}
              className="w-20 px-2 py-1 text-sm border rounded"
              placeholder="6"
            />
          )}
        </StylePropertyRow>
      )}

      {/* Add more properties as needed... */}
    </div>
  );
};

// Helper component for consistent property rows with link/unlink
interface StylePropertyRowProps {
  label: string;
  property: string;
  elementId: string;
  nodeType: string;
  overrides: Record<string, unknown>;
  onOverride: (property: string, value: unknown) => void;
  onUnlink: (property: string) => void;
  children: (value: unknown, onChange: (value: unknown) => void) => React.ReactNode;
}

const StylePropertyRow: React.FC<StylePropertyRowProps> = ({
  label,
  property,
  elementId,
  nodeType,
  overrides,
  onOverride,
  onUnlink,
  children,
}) => {
  const { globalValue, resolvedValue, isOverridden } = usePropertyValues(
    elementId,
    nodeType as NodeType,
    property as keyof StyleProperties
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
```

### Store Integration

Add style override methods to useElementsStore:

```typescript
// In useElementsStore.ts

setStyleOverride: (elementId, nodeType, property, value) =>
  set((state) => ({
    items: {
      ...state.items,
      [elementId]: {
        ...state.items[elementId],
        styleOverrides: {
          ...state.items[elementId]?.styleOverrides,
          [nodeType]: {
            ...state.items[elementId]?.styleOverrides?.[nodeType],
            [property]: value,
          },
        },
      },
    },
  })),

removeStyleOverride: (elementId, nodeType, property) =>
  set((state) => {
    const current = state.items[elementId]?.styleOverrides?.[nodeType] || {};
    const { [property]: removed, ...rest } = current;
    return {
      items: {
        ...state.items,
        [elementId]: {
          ...state.items[elementId],
          styleOverrides: {
            ...state.items[elementId]?.styleOverrides,
            [nodeType]: rest,
          },
        },
      },
    };
  }),

clearNodeStyleOverrides: (elementId, nodeType) =>
  set((state) => {
    const { [nodeType]: removed, ...rest } = state.items[elementId]?.styleOverrides || {};
    return {
      items: {
        ...state.items,
        [elementId]: {
          ...state.items[elementId],
          styleOverrides: rest,
        },
      },
    };
  }),

clearAllStyleOverrides: (elementId) =>
  set((state) => ({
    items: {
      ...state.items,
      [elementId]: {
        ...state.items[elementId],
        styleOverrides: {},
      },
    },
  })),
```

### Visual Design

**Link/Unlink States:**
- **Linked (gray link icon):** Property inherits from global theme
- **Unlinked (orange unlink icon):** Property has element-specific override

**Override Indicators:**
- Orange dot next to node type tabs when node has overrides
- Badge showing "X overrides" in section header
- "Reset all" button when any overrides exist

## Files to Create

1. `/src/react/admin/apps/form-builder-v2/components/property-panels/ElementStylesSection.tsx`
2. `/src/react/admin/apps/form-builder-v2/components/property-panels/NodeStyleEditor.tsx`

## Files to Modify

1. `/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`
   - Add ElementStylesSection import and render
   - Pass styleOverrides and handlers

2. `/src/react/admin/apps/form-builder-v2/store/useElementsStore.ts`
   - Add style override methods if not already present

## Implementation Notes

- Use existing useResolvedStyle hook for value resolution
- Collapsible section to not clutter the panel
- Only show properties that the node type supports (via capabilities)
- Override values stored in element.styleOverrides[nodeType][property]
- Changes trigger immediate re-render via store subscription

## Dependencies

- Existing style hooks (useResolvedStyle, usePropertyValues)
- Existing NODE_STYLE_CAPABILITIES
- Existing getElementNodes function
- SpacingControl and ColorControl components
