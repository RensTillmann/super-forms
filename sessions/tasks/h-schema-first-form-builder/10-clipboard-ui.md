---
name: 10-clipboard-ui
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 10: Clipboard UI

## Goal

Implement the copy/paste settings UI with context menu integration for the Form Builder.

## Dependencies

- Phase 5 (Operations Schemas) must be complete
- Phase 8 (Property Panels) recommended
- Phase 9 (Tailwind Integration) recommended

## User Workflow

### Copy Settings Flow

1. User right-clicks on element A
2. Context menu shows "Copy Settings" submenu:
   - Copy All Settings
   - Copy General Settings
   - Copy Validation Settings
   - Copy Styling Settings
   - Copy Advanced Settings
   - Copy Specific Properties...
3. User selects option
4. Settings stored in clipboard state
5. Visual indicator shows "Settings copied"

### Paste Settings Flow

1. User right-clicks on element B
2. Context menu shows "Paste Settings" (if clipboard has content):
   - Paste All Compatible
   - Paste Selected Properties...
3. User selects option
4. If "Paste Selected", modal shows compatible properties with checkboxes
5. Settings applied via JSON Patch operations
6. Visual indicator shows "X properties applied"

## Deliverables

### 1. Clipboard Service (`/src/react/admin/services/clipboardService.ts`)

```typescript
import { create } from 'zustand';
import { PropertyCategory, JsonPatchOperation } from '@/schemas';

interface ClipboardContents {
  sourceElementId: string;
  sourceElementType: string;
  copiedAt: Date;
  mode: 'all' | 'category' | 'properties';
  category?: PropertyCategory;
  properties?: string[];
  values: Record<string, unknown>;
}

interface ClipboardStore {
  clipboard: ClipboardContents | null;

  copySettings: (
    element: FormElement,
    mode: 'all' | 'category' | 'properties',
    options?: { category?: PropertyCategory; properties?: string[] }
  ) => void;

  canPaste: (targetElementType: string) => {
    compatible: boolean;
    compatibleProperties: string[];
    incompatibleProperties: { property: string; reason: string }[];
  };

  pasteSettings: (
    targetElement: FormElement,
    mode: 'all' | 'selected',
    selectedProperties?: string[]
  ) => JsonPatchOperation[];

  clear: () => void;
}

export const useClipboardStore = create<ClipboardStore>((set, get) => ({
  clipboard: null,

  copySettings: (element, mode, options = {}) => {
    const values: Record<string, unknown> = {};

    if (mode === 'all') {
      // Copy all non-identity properties
      const { id, type, ...rest } = element;
      Object.assign(values, rest);
    } else if (mode === 'category' && options.category) {
      // Copy only properties from category
      const categoryProps = getCategoryProperties(element.type, options.category);
      for (const prop of categoryProps) {
        if (element[prop] !== undefined) {
          values[prop] = element[prop];
        }
      }
    } else if (mode === 'properties' && options.properties) {
      // Copy specific properties
      for (const prop of options.properties) {
        if (element[prop] !== undefined) {
          values[prop] = element[prop];
        }
      }
    }

    set({
      clipboard: {
        sourceElementId: element.id,
        sourceElementType: element.type,
        copiedAt: new Date(),
        mode,
        category: options.category,
        properties: options.properties,
        values,
      },
    });
  },

  canPaste: (targetElementType) => {
    const { clipboard } = get();
    if (!clipboard) {
      return { compatible: false, compatibleProperties: [], incompatibleProperties: [] };
    }

    const sourceSchema = getElementSchema(clipboard.sourceElementType);
    const targetSchema = getElementSchema(targetElementType);

    const compatibleProperties: string[] = [];
    const incompatibleProperties: { property: string; reason: string }[] = [];

    for (const prop of Object.keys(clipboard.values)) {
      const sourceProperty = findProperty(sourceSchema, prop);
      const targetProperty = findProperty(targetSchema, prop);

      if (!targetProperty) {
        incompatibleProperties.push({
          property: prop,
          reason: `Property does not exist on ${targetElementType}`,
        });
      } else if (sourceProperty?.universal || clipboard.sourceElementType === targetElementType) {
        // Universal properties always compatible, or same type
        compatibleProperties.push(prop);
      } else if (sourceProperty?.type !== targetProperty?.type) {
        incompatibleProperties.push({
          property: prop,
          reason: `Type mismatch: ${sourceProperty?.type} vs ${targetProperty?.type}`,
        });
      } else {
        compatibleProperties.push(prop);
      }
    }

    return {
      compatible: compatibleProperties.length > 0,
      compatibleProperties,
      incompatibleProperties,
    };
  },

  pasteSettings: (targetElement, mode, selectedProperties) => {
    const { clipboard } = get();
    if (!clipboard) return [];

    const operations: JsonPatchOperation[] = [];
    const { compatibleProperties } = get().canPaste(targetElement.type);

    const propertiesToPaste = mode === 'selected' && selectedProperties
      ? selectedProperties.filter(p => compatibleProperties.includes(p))
      : compatibleProperties;

    for (const prop of propertiesToPaste) {
      operations.push({
        op: 'replace',
        path: `/elements/${targetElement.id}/${prop}`,
        value: clipboard.values[prop],
        // Store old value for undo
        oldValue: targetElement[prop],
      });
    }

    return operations;
  },

  clear: () => set({ clipboard: null }),
}));

// Helper functions
function getCategoryProperties(elementType: string, category: PropertyCategory): string[] {
  const schema = getElementSchema(elementType);
  return Object.keys(schema.properties[category] || {});
}

function findProperty(schema: ElementSchema, path: string): PropertySchema | null {
  for (const category of Object.values(schema.properties)) {
    if (category[path]) return category[path];
  }
  return null;
}
```

### 2. Context Menu Component (`/src/react/admin/components/form-builder-v2/ElementContextMenu.tsx`)

```typescript
import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuSeparator,
  ContextMenuSub,
  ContextMenuSubContent,
  ContextMenuSubTrigger,
  ContextMenuTrigger,
} from '@/components/ui/context-menu';
import {
  Copy,
  Clipboard,
  ClipboardPaste,
  Trash2,
  CopyPlus,
  Settings,
  Shield,
  Palette,
  Wrench,
  GitBranch,
} from 'lucide-react';
import { useClipboardStore } from '@/services/clipboardService';
import { useOperations } from '@/hooks/useOperations';

interface ElementContextMenuProps {
  element: FormElement;
  children: React.ReactNode;
  onDuplicate: () => void;
  onDelete: () => void;
  onSelectProperties: () => void;
  onPasteSelectProperties: () => void;
}

export function ElementContextMenu({
  element,
  children,
  onDuplicate,
  onDelete,
  onSelectProperties,
  onPasteSelectProperties,
}: ElementContextMenuProps) {
  const { clipboard, copySettings, canPaste, pasteSettings, clear } = useClipboardStore();
  const { recordOperations } = useOperations();

  const pasteInfo = canPaste(element.type);
  const hasClipboard = clipboard !== null;

  const handleCopy = (mode: 'all' | 'category', category?: PropertyCategory) => {
    copySettings(element, mode, { category });
    // Show toast notification
    toast.success('Settings copied to clipboard');
  };

  const handlePaste = (mode: 'all' | 'selected') => {
    if (mode === 'all') {
      const operations = pasteSettings(element, 'all');
      if (operations.length > 0) {
        recordOperations(operations);
        toast.success(`${operations.length} properties applied`);
      } else {
        toast.info('No compatible properties to paste');
      }
    } else {
      onPasteSelectProperties();
    }
  };

  const getCategoryIcon = (category: PropertyCategory) => {
    const icons = {
      general: Settings,
      validation: Shield,
      styling: Palette,
      advanced: Wrench,
      conditional: GitBranch,
    };
    return icons[category];
  };

  return (
    <ContextMenu>
      <ContextMenuTrigger asChild>
        {children}
      </ContextMenuTrigger>

      <ContextMenuContent className="w-56">
        {/* Copy Settings */}
        <ContextMenuSub>
          <ContextMenuSubTrigger>
            <Copy className="mr-2 h-4 w-4" />
            Copy Settings
          </ContextMenuSubTrigger>
          <ContextMenuSubContent className="w-48">
            <ContextMenuItem onClick={() => handleCopy('all')}>
              <Clipboard className="mr-2 h-4 w-4" />
              Copy All Settings
            </ContextMenuItem>
            <ContextMenuSeparator />

            {(['general', 'validation', 'styling', 'advanced', 'conditional'] as const).map((category) => {
              const Icon = getCategoryIcon(category);
              return (
                <ContextMenuItem
                  key={category}
                  onClick={() => handleCopy('category', category)}
                >
                  <Icon className="mr-2 h-4 w-4" />
                  Copy {category.charAt(0).toUpperCase() + category.slice(1)}
                </ContextMenuItem>
              );
            })}

            <ContextMenuSeparator />
            <ContextMenuItem onClick={onSelectProperties}>
              <Settings className="mr-2 h-4 w-4" />
              Select Properties...
            </ContextMenuItem>
          </ContextMenuSubContent>
        </ContextMenuSub>

        {/* Paste Settings */}
        <ContextMenuSub disabled={!hasClipboard}>
          <ContextMenuSubTrigger disabled={!hasClipboard}>
            <ClipboardPaste className="mr-2 h-4 w-4" />
            Paste Settings
            {hasClipboard && pasteInfo.compatibleProperties.length > 0 && (
              <span className="ml-auto text-xs text-muted-foreground">
                {pasteInfo.compatibleProperties.length}
              </span>
            )}
          </ContextMenuSubTrigger>
          <ContextMenuSubContent className="w-48">
            <ContextMenuItem
              onClick={() => handlePaste('all')}
              disabled={pasteInfo.compatibleProperties.length === 0}
            >
              <Clipboard className="mr-2 h-4 w-4" />
              Paste All Compatible
              <span className="ml-auto text-xs text-muted-foreground">
                ({pasteInfo.compatibleProperties.length})
              </span>
            </ContextMenuItem>
            <ContextMenuItem onClick={() => handlePaste('selected')}>
              <Settings className="mr-2 h-4 w-4" />
              Select Properties...
            </ContextMenuItem>

            {pasteInfo.incompatibleProperties.length > 0 && (
              <>
                <ContextMenuSeparator />
                <div className="px-2 py-1.5 text-xs text-muted-foreground">
                  {pasteInfo.incompatibleProperties.length} incompatible
                </div>
              </>
            )}
          </ContextMenuSubContent>
        </ContextMenuSub>

        <ContextMenuSeparator />

        {/* Other actions */}
        <ContextMenuItem onClick={onDuplicate}>
          <CopyPlus className="mr-2 h-4 w-4" />
          Duplicate
        </ContextMenuItem>

        <ContextMenuSeparator />

        <ContextMenuItem
          onClick={onDelete}
          className="text-destructive focus:text-destructive"
        >
          <Trash2 className="mr-2 h-4 w-4" />
          Delete
        </ContextMenuItem>
      </ContextMenuContent>
    </ContextMenu>
  );
}
```

### 3. Property Selection Modal (`/src/react/admin/components/form-builder-v2/PropertySelectionModal.tsx`)

```typescript
import { useState, useMemo } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { getElementSchema, PropertyCategory } from '@/schemas';

interface PropertySelectionModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  mode: 'copy' | 'paste';
  element: FormElement;
  compatibleProperties?: string[];
  onConfirm: (selectedProperties: string[]) => void;
}

export function PropertySelectionModal({
  open,
  onOpenChange,
  mode,
  element,
  compatibleProperties,
  onConfirm,
}: PropertySelectionModalProps) {
  const [selected, setSelected] = useState<Set<string>>(new Set());

  const schema = useMemo(() => getElementSchema(element.type), [element.type]);

  // Group properties by category
  const groupedProperties = useMemo(() => {
    const groups: Record<PropertyCategory, { key: string; label: string; compatible: boolean }[]> = {
      general: [],
      validation: [],
      styling: [],
      advanced: [],
      conditional: [],
    };

    for (const [category, properties] of Object.entries(schema.properties)) {
      for (const [key, propSchema] of Object.entries(properties)) {
        // Skip identity properties
        if (['id', 'type'].includes(key)) continue;

        const compatible = mode === 'copy' || compatibleProperties?.includes(key) || false;

        groups[category as PropertyCategory].push({
          key,
          label: propSchema.label || key,
          compatible,
        });
      }
    }

    return groups;
  }, [schema, mode, compatibleProperties]);

  const toggleProperty = (key: string) => {
    const newSelected = new Set(selected);
    if (newSelected.has(key)) {
      newSelected.delete(key);
    } else {
      newSelected.add(key);
    }
    setSelected(newSelected);
  };

  const toggleCategory = (category: PropertyCategory) => {
    const newSelected = new Set(selected);
    const categoryProps = groupedProperties[category]
      .filter(p => mode === 'copy' || p.compatible)
      .map(p => p.key);

    const allSelected = categoryProps.every(p => newSelected.has(p));

    for (const prop of categoryProps) {
      if (allSelected) {
        newSelected.delete(prop);
      } else {
        newSelected.add(prop);
      }
    }

    setSelected(newSelected);
  };

  const handleConfirm = () => {
    onConfirm(Array.from(selected));
    onOpenChange(false);
    setSelected(new Set());
  };

  const categoryLabels: Record<PropertyCategory, string> = {
    general: 'General',
    validation: 'Validation',
    styling: 'Styling',
    advanced: 'Advanced',
    conditional: 'Conditional Logic',
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>
            {mode === 'copy' ? 'Select Properties to Copy' : 'Select Properties to Paste'}
          </DialogTitle>
        </DialogHeader>

        <ScrollArea className="h-[400px] pr-4">
          {(Object.entries(groupedProperties) as [PropertyCategory, typeof groupedProperties.general][]).map(
            ([category, properties]) => {
              if (properties.length === 0) return null;

              const availableProps = properties.filter(p => mode === 'copy' || p.compatible);
              if (availableProps.length === 0 && mode === 'paste') return null;

              const categorySelected = availableProps.filter(p => selected.has(p.key));

              return (
                <div key={category} className="mb-4">
                  <div
                    className="flex items-center gap-2 mb-2 cursor-pointer"
                    onClick={() => toggleCategory(category)}
                  >
                    <Checkbox
                      checked={
                        categorySelected.length === availableProps.length &&
                        availableProps.length > 0
                      }
                      indeterminate={
                        categorySelected.length > 0 &&
                        categorySelected.length < availableProps.length
                      }
                    />
                    <span className="font-medium">{categoryLabels[category]}</span>
                    <Badge variant="secondary" className="ml-auto">
                      {categorySelected.length}/{availableProps.length}
                    </Badge>
                  </div>

                  <div className="pl-6 space-y-1">
                    {properties.map(({ key, label, compatible }) => (
                      <div
                        key={key}
                        className={`flex items-center gap-2 py-1 ${
                          !compatible && mode === 'paste' ? 'opacity-50' : ''
                        }`}
                      >
                        <Checkbox
                          checked={selected.has(key)}
                          disabled={!compatible && mode === 'paste'}
                          onCheckedChange={() => toggleProperty(key)}
                        />
                        <span className="text-sm">{label}</span>
                        {!compatible && mode === 'paste' && (
                          <Badge variant="outline" className="ml-auto text-xs">
                            Incompatible
                          </Badge>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              );
            }
          )}
        </ScrollArea>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleConfirm} disabled={selected.size === 0}>
            {mode === 'copy' ? 'Copy' : 'Paste'} ({selected.size})
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
```

### 4. Clipboard Indicator (`/src/react/admin/components/form-builder-v2/ClipboardIndicator.tsx`)

```typescript
import { useClipboardStore } from '@/services/clipboardService';
import { Clipboard, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';

export function ClipboardIndicator() {
  const { clipboard, clear } = useClipboardStore();

  if (!clipboard) return null;

  const propertyCount = Object.keys(clipboard.values).length;

  return (
    <div className="fixed bottom-4 right-4 flex items-center gap-2 bg-background border rounded-lg shadow-lg px-3 py-2 animate-in slide-in-from-bottom-2">
      <Clipboard className="h-4 w-4 text-muted-foreground" />
      <span className="text-sm">
        <Badge variant="secondary" className="mr-2">
          {clipboard.sourceElementType}
        </Badge>
        {propertyCount} {propertyCount === 1 ? 'property' : 'properties'} copied
      </span>
      <Tooltip>
        <TooltipTrigger asChild>
          <Button
            variant="ghost"
            size="icon"
            className="h-6 w-6"
            onClick={clear}
          >
            <X className="h-3 w-3" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>Clear clipboard</TooltipContent>
      </Tooltip>
    </div>
  );
}
```

### 5. Integration with Form Builder (`/src/react/admin/apps/form-builder-v2/FormBuilder.tsx`)

```typescript
// Add to existing FormBuilder component
import { ElementContextMenu } from '@/components/form-builder-v2/ElementContextMenu';
import { PropertySelectionModal } from '@/components/form-builder-v2/PropertySelectionModal';
import { ClipboardIndicator } from '@/components/form-builder-v2/ClipboardIndicator';
import { useClipboardStore } from '@/services/clipboardService';

function FormBuilder() {
  const [propertyModalState, setPropertyModalState] = useState<{
    open: boolean;
    mode: 'copy' | 'paste';
    elementId: string | null;
  }>({
    open: false,
    mode: 'copy',
    elementId: null,
  });

  const { clipboard, copySettings, pasteSettings, canPaste } = useClipboardStore();
  const { recordOperations } = useOperations();

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const selectedElement = selectedElementId ? elements[selectedElementId] : null;

      if (!selectedElement) return;

      // Ctrl/Cmd + Shift + C = Copy settings
      if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'c') {
        e.preventDefault();
        copySettings(selectedElement, 'all');
        toast.success('Settings copied');
      }

      // Ctrl/Cmd + Shift + V = Paste settings
      if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'v') {
        e.preventDefault();
        if (clipboard) {
          const operations = pasteSettings(selectedElement, 'all');
          if (operations.length > 0) {
            recordOperations(operations);
            toast.success(`${operations.length} properties applied`);
          }
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [selectedElementId, clipboard]);

  const renderElement = (element: FormElement) => {
    return (
      <ElementContextMenu
        key={element.id}
        element={element}
        onDuplicate={() => duplicateElement(element.id)}
        onDelete={() => deleteElement(element.id)}
        onSelectProperties={() => {
          setPropertyModalState({
            open: true,
            mode: 'copy',
            elementId: element.id,
          });
        }}
        onPasteSelectProperties={() => {
          setPropertyModalState({
            open: true,
            mode: 'paste',
            elementId: element.id,
          });
        }}
      >
        <ElementRenderer element={element} />
      </ElementContextMenu>
    );
  };

  const currentElement = propertyModalState.elementId
    ? elements[propertyModalState.elementId]
    : null;

  const pasteInfo = currentElement
    ? canPaste(currentElement.type)
    : { compatibleProperties: [] };

  return (
    <div className="form-builder">
      {/* ... existing canvas ... */}

      {/* Property Selection Modal */}
      {currentElement && (
        <PropertySelectionModal
          open={propertyModalState.open}
          onOpenChange={(open) =>
            setPropertyModalState((s) => ({ ...s, open }))
          }
          mode={propertyModalState.mode}
          element={currentElement}
          compatibleProperties={
            propertyModalState.mode === 'paste'
              ? pasteInfo.compatibleProperties
              : undefined
          }
          onConfirm={(properties) => {
            if (propertyModalState.mode === 'copy') {
              copySettings(currentElement, 'properties', { properties });
              toast.success(`${properties.length} properties copied`);
            } else {
              const operations = pasteSettings(currentElement, 'selected', properties);
              recordOperations(operations);
              toast.success(`${operations.length} properties applied`);
            }
          }}
        />
      )}

      {/* Clipboard Indicator */}
      <ClipboardIndicator />
    </div>
  );
}
```

## File Structure

```
/src/react/admin/
├── services/
│   └── clipboardService.ts
├── components/
│   └── form-builder-v2/
│       ├── ElementContextMenu.tsx
│       ├── PropertySelectionModal.tsx
│       └── ClipboardIndicator.tsx
└── apps/
    └── form-builder-v2/
        └── FormBuilder.tsx (integration)
```

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + Shift + C` | Copy all settings from selected element |
| `Ctrl/Cmd + Shift + V` | Paste all compatible settings to selected element |

## MCP Integration

The clipboard service is accessible via MCP tools (Phase 7):

```typescript
// MCP can use clipboard operations
{
  name: 'copy_element_settings',
  handler: async (args) => {
    // Uses same logic as clipboardService
    return this.callWordPress('POST', `/forms/${args.formId}/clipboard/copy`, {
      elementId: args.elementId,
      mode: args.mode,
      category: args.category,
      properties: args.properties,
    });
  },
}

{
  name: 'paste_element_settings',
  handler: async (args) => {
    return this.callWordPress('POST', `/forms/${args.formId}/clipboard/paste`, {
      targetElementId: args.targetElementId,
      mode: args.mode,
      properties: args.properties,
    });
  },
}
```

## Acceptance Criteria

- [ ] Copy settings context menu works for all modes
- [ ] Paste settings shows compatible count
- [ ] Property selection modal shows grouped properties
- [ ] Incompatible properties are disabled in paste mode
- [ ] Keyboard shortcuts work (Ctrl+Shift+C/V)
- [ ] Clipboard indicator shows copied content
- [ ] Clear clipboard works
- [ ] Toast notifications for user feedback
- [ ] JSON Patch operations generated correctly
- [ ] Undo/redo works for paste operations
- [ ] MCP tools can access clipboard functionality

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Section 9)
- **Operations Schema**: `/src/schemas/operations/clipboard.schema.ts`
- **Output**: `/src/react/admin/services/clipboardService.ts`, Context menu components

### Reference
- shadcn/ui context menu: `src/react/admin/components/ui/context-menu.tsx`
- useOperations hook: `src/react/admin/hooks/useOperations.ts`

## Work Log
- [2025-12-03] Task created
