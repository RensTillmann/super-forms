---
name: 08-react-property-panels
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 8: React Property Panels

## Goal

Create schema-driven property panels that automatically render UI based on element schemas.

## Dependencies

- Phase 1-5 (TypeScript schemas) must be complete
- Existing Form Builder V2 UI as integration point

## Current State

Property panels in Form Builder V2 are **hardcoded** per element type. Adding a new element requires:
1. Creating the element component
2. Creating a dedicated property panel
3. Manually mapping each property to form controls

## Target State

Property panels are **generated from schema**:
1. Read element schema
2. Render appropriate control for each property type
3. Handle validation, defaults, conditional visibility
4. Emit JSON Patch operations on change

## Deliverables

### 1. Property Renderers

One renderer component per PropertyType:

```
/src/react/admin/components/schema-ui/
├── PropertyRenderer.tsx          # Main dispatcher
├── renderers/
│   ├── StringRenderer.tsx        # PropertyType.STRING
│   ├── NumberRenderer.tsx        # PropertyType.NUMBER
│   ├── BooleanRenderer.tsx       # PropertyType.BOOLEAN
│   ├── SelectRenderer.tsx        # PropertyType.SELECT
│   ├── MultiSelectRenderer.tsx   # PropertyType.MULTI_SELECT
│   ├── ColorRenderer.tsx         # PropertyType.COLOR
│   ├── IconRenderer.tsx          # PropertyType.ICON
│   ├── ArrayRenderer.tsx         # PropertyType.ARRAY
│   ├── ObjectRenderer.tsx        # PropertyType.OBJECT
│   ├── ConditionalRulesRenderer.tsx  # PropertyType.CONDITIONAL_RULES
│   ├── ColumnsConfigRenderer.tsx     # PropertyType.COLUMNS_CONFIG
│   └── index.ts
├── PropertyGroup.tsx             # Renders a category of properties
├── PropertyPanel.tsx             # Full panel for element
└── index.ts
```

### 2. PropertyRenderer Component

```typescript
// /src/react/admin/components/schema-ui/PropertyRenderer.tsx

import { PropertySchema, PropertyType } from '@/schemas';
import * as Renderers from './renderers';

interface PropertyRendererProps {
  schema: PropertySchema;
  value: unknown;
  onChange: (value: unknown) => void;
  elementId: string;
  propertyPath: string;
  disabled?: boolean;
}

export function PropertyRenderer({
  schema,
  value,
  onChange,
  elementId,
  propertyPath,
  disabled,
}: PropertyRendererProps) {
  // Check conditional visibility
  const { isVisible, formValues } = usePropertyVisibility(schema, elementId);
  if (!isVisible) return null;

  // Get appropriate renderer
  const Renderer = getRenderer(schema.type);

  return (
    <div className="sf-property">
      {schema.label && (
        <label className="sf-property-label">
          {schema.label}
          {schema.required && <span className="sf-required">*</span>}
        </label>
      )}

      <Renderer
        schema={schema}
        value={value ?? schema.default}
        onChange={onChange}
        disabled={disabled}
      />

      {schema.description && (
        <p className="sf-property-description">{schema.description}</p>
      )}
    </div>
  );
}

function getRenderer(type: PropertyType) {
  const renderers: Record<PropertyType, React.ComponentType<any>> = {
    [PropertyType.STRING]: Renderers.StringRenderer,
    [PropertyType.NUMBER]: Renderers.NumberRenderer,
    [PropertyType.BOOLEAN]: Renderers.BooleanRenderer,
    [PropertyType.SELECT]: Renderers.SelectRenderer,
    [PropertyType.MULTI_SELECT]: Renderers.MultiSelectRenderer,
    [PropertyType.COLOR]: Renderers.ColorRenderer,
    [PropertyType.ICON]: Renderers.IconRenderer,
    [PropertyType.ARRAY]: Renderers.ArrayRenderer,
    [PropertyType.OBJECT]: Renderers.ObjectRenderer,
    [PropertyType.CONDITIONAL_RULES]: Renderers.ConditionalRulesRenderer,
    [PropertyType.COLUMNS_CONFIG]: Renderers.ColumnsConfigRenderer,
    // ... all 26 types
  };

  return renderers[type] || Renderers.StringRenderer;
}
```

### 3. Example Renderers

#### StringRenderer

```typescript
// /src/react/admin/components/schema-ui/renderers/StringRenderer.tsx

import { Input } from '@/components/ui/input';

interface StringRendererProps {
  schema: PropertySchema;
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
}

export function StringRenderer({ schema, value, onChange, disabled }: StringRendererProps) {
  return (
    <Input
      type="text"
      value={value || ''}
      onChange={(e) => onChange(e.target.value)}
      placeholder={schema.placeholder}
      disabled={disabled}
      pattern={schema.pattern}
      required={schema.required}
      className="sf-input"
    />
  );
}
```

#### SelectRenderer

```typescript
// /src/react/admin/components/schema-ui/renderers/SelectRenderer.tsx

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useDynamicOptions } from '@/hooks/useDynamicOptions';

interface SelectRendererProps {
  schema: PropertySchema;
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
}

export function SelectRenderer({ schema, value, onChange, disabled }: SelectRendererProps) {
  // Handle dynamic options (e.g., 'forms', 'wp_roles')
  const options = typeof schema.options === 'string'
    ? useDynamicOptions(schema.options)
    : schema.options;

  return (
    <Select value={value} onValueChange={onChange} disabled={disabled}>
      <SelectTrigger className="sf-select">
        <SelectValue placeholder={schema.placeholder || 'Select...'} />
      </SelectTrigger>
      <SelectContent>
        {options?.map((option) => (
          <SelectItem key={option.value} value={String(option.value)}>
            {option.icon && <span className="sf-option-icon">{option.icon}</span>}
            {option.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
```

#### ArrayRenderer (for dropdown items, etc.)

```typescript
// /src/react/admin/components/schema-ui/renderers/ArrayRenderer.tsx

import { Button } from '@/components/ui/button';
import { Plus, Trash2, GripVertical } from 'lucide-react';
import { DndContext, closestCenter } from '@dnd-kit/core';

interface ArrayRendererProps {
  schema: PropertySchema;
  value: unknown[];
  onChange: (value: unknown[]) => void;
  disabled?: boolean;
}

export function ArrayRenderer({ schema, value = [], onChange, disabled }: ArrayRendererProps) {
  const itemSchema = schema.items;

  const addItem = () => {
    const newItem = getDefaultValue(itemSchema);
    onChange([...value, newItem]);
  };

  const removeItem = (index: number) => {
    onChange(value.filter((_, i) => i !== index));
  };

  const updateItem = (index: number, newValue: unknown) => {
    const updated = [...value];
    updated[index] = newValue;
    onChange(updated);
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    if (over && active.id !== over.id) {
      const oldIndex = value.findIndex((_, i) => `item-${i}` === active.id);
      const newIndex = value.findIndex((_, i) => `item-${i}` === over.id);
      const reordered = arrayMove(value, oldIndex, newIndex);
      onChange(reordered);
    }
  };

  return (
    <div className="sf-array">
      <DndContext collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
        <SortableContext items={value.map((_, i) => `item-${i}`)}>
          {value.map((item, index) => (
            <SortableItem key={`item-${index}`} id={`item-${index}`}>
              <div className="sf-array-item">
                <GripVertical className="sf-drag-handle" />

                {itemSchema.type === PropertyType.OBJECT ? (
                  <ObjectRenderer
                    schema={itemSchema}
                    value={item as Record<string, unknown>}
                    onChange={(v) => updateItem(index, v)}
                    disabled={disabled}
                  />
                ) : (
                  <PropertyRenderer
                    schema={itemSchema}
                    value={item}
                    onChange={(v) => updateItem(index, v)}
                    disabled={disabled}
                  />
                )}

                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => removeItem(index)}
                  disabled={disabled}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </SortableItem>
          ))}
        </SortableContext>
      </DndContext>

      <Button
        variant="outline"
        size="sm"
        onClick={addItem}
        disabled={disabled}
        className="sf-array-add"
      >
        <Plus className="h-4 w-4 mr-2" />
        Add Item
      </Button>
    </div>
  );
}
```

### 4. PropertyPanel Component

Full panel that renders all properties for an element:

```typescript
// /src/react/admin/components/schema-ui/PropertyPanel.tsx

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PropertyGroup } from './PropertyGroup';
import { getElementSchema } from '@/schemas';
import { useOperations } from '@/hooks/useOperations';

interface PropertyPanelProps {
  elementId: string;
  elementType: string;
  elementData: Record<string, unknown>;
}

export function PropertyPanel({ elementId, elementType, elementData }: PropertyPanelProps) {
  const schema = getElementSchema(elementType);
  const { recordOperation } = useOperations();

  if (!schema) {
    return <div>Unknown element type: {elementType}</div>;
  }

  const handlePropertyChange = (path: string, value: unknown) => {
    // Record JSON Patch operation
    recordOperation({
      op: 'replace',
      path: `/elements/${elementId}/${path}`,
      value,
      // Cache old value for undo
      oldValue: getNestedValue(elementData, path),
    });
  };

  const categories = Object.keys(schema.properties) as PropertyCategory[];

  return (
    <div className="sf-property-panel">
      <div className="sf-panel-header">
        <span className="sf-element-icon">{schema.icon}</span>
        <span className="sf-element-name">{schema.name}</span>
      </div>

      <Tabs defaultValue={categories[0]} className="sf-panel-tabs">
        <TabsList>
          {categories.map((category) => (
            <TabsTrigger key={category} value={category}>
              {categoryLabels[category]}
            </TabsTrigger>
          ))}
        </TabsList>

        {categories.map((category) => (
          <TabsContent key={category} value={category}>
            <PropertyGroup
              properties={schema.properties[category]}
              values={elementData}
              onChange={handlePropertyChange}
              elementId={elementId}
            />
          </TabsContent>
        ))}
      </Tabs>
    </div>
  );
}

const categoryLabels: Record<PropertyCategory, string> = {
  general: 'General',
  validation: 'Validation',
  styling: 'Styling',
  advanced: 'Advanced',
  conditional: 'Conditional',
};
```

### 5. PropertyGroup Component

```typescript
// /src/react/admin/components/schema-ui/PropertyGroup.tsx

import { PropertyRenderer } from './PropertyRenderer';

interface PropertyGroupProps {
  properties: Record<string, PropertySchema>;
  values: Record<string, unknown>;
  onChange: (path: string, value: unknown) => void;
  elementId: string;
}

export function PropertyGroup({ properties, values, onChange, elementId }: PropertyGroupProps) {
  return (
    <div className="sf-property-group">
      {Object.entries(properties).map(([key, schema]) => (
        <PropertyRenderer
          key={key}
          schema={schema}
          value={values[key]}
          onChange={(value) => onChange(key, value)}
          elementId={elementId}
          propertyPath={key}
        />
      ))}
    </div>
  );
}
```

### 6. Conditional Visibility Hook

```typescript
// /src/react/admin/hooks/usePropertyVisibility.ts

import { useElementsStore } from '@/store/useElementsStore';

export function usePropertyVisibility(
  schema: PropertySchema,
  elementId: string
): { isVisible: boolean; formValues: Record<string, unknown> } {
  const element = useElementsStore((s) => s.items[elementId]);

  if (!schema.showWhen) {
    return { isVisible: true, formValues: element };
  }

  const { field, equals, notEquals, in: inValues, notIn } = schema.showWhen;
  const fieldValue = element[field];

  let isVisible = true;

  if (equals !== undefined) {
    isVisible = fieldValue === equals;
  } else if (notEquals !== undefined) {
    isVisible = fieldValue !== notEquals;
  } else if (inValues !== undefined) {
    isVisible = inValues.includes(fieldValue);
  } else if (notIn !== undefined) {
    isVisible = !notIn.includes(fieldValue);
  }

  return { isVisible, formValues: element };
}
```

### 7. Dynamic Options Hook

```typescript
// /src/react/admin/hooks/useDynamicOptions.ts

import { useQuery } from '@tanstack/react-query';

type OptionKey = 'forms' | 'form_fields' | 'wp_roles' | 'wp_users' | 'wp_pages' | 'wp_post_types';

export function useDynamicOptions(optionKey: OptionKey) {
  const { data } = useQuery({
    queryKey: ['dynamic-options', optionKey],
    queryFn: () => fetchOptions(optionKey),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

  return data ?? [];
}

async function fetchOptions(key: OptionKey): Promise<PropertyOption[]> {
  const endpoints: Record<OptionKey, string> = {
    forms: '/super-forms/v1/forms?fields=id,title',
    form_fields: '/super-forms/v1/forms/{formId}/fields',
    wp_roles: '/wp/v2/roles',
    wp_users: '/wp/v2/users?per_page=100',
    wp_pages: '/wp/v2/pages?per_page=100',
    wp_post_types: '/wp/v2/types',
  };

  const response = await wp.apiFetch({ path: endpoints[key] });
  return transformToOptions(key, response);
}
```

## Integration with Form Builder V2

Replace hardcoded property panels with schema-driven panel:

```typescript
// In FormBuilderV2.tsx or ElementPropertiesPanel.tsx

import { PropertyPanel } from '@/components/schema-ui/PropertyPanel';

function ElementPropertiesPanel() {
  const selectedId = useBuilderStore((s) => s.selectedElementId);
  const element = useElementsStore((s) => selectedId ? s.items[selectedId] : null);

  if (!element) {
    return <div>Select an element to edit</div>;
  }

  return (
    <PropertyPanel
      elementId={element.id}
      elementType={element.type}
      elementData={element}
    />
  );
}
```

## Acceptance Criteria

- [ ] PropertyRenderer dispatches to correct renderer by type
- [ ] All 26 PropertyTypes have renderers
- [ ] Conditional visibility (showWhen) works
- [ ] Dynamic options load correctly
- [ ] Array items are sortable
- [ ] Changes emit JSON Patch operations
- [ ] Integration with useOperations hook
- [ ] Tailwind/shadcn styling consistent
- [ ] TypeScript compiles without errors

## Context Manifest

### Key Files
- **Schemas**: `/src/schemas/`
- **Form Builder V2**: `src/react/admin/apps/form-builder-v2/`
- **Output**: `src/react/admin/components/schema-ui/`

### Reference
- shadcn/ui components: `src/react/admin/components/ui/`
- Existing property panels: `src/react/admin/apps/form-builder-v2/components/panels/`

## Work Log
- [2025-12-03] Task created
