---
name: 05-operations-schemas
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 5: Operations Schemas

## Goal

Create schemas for clipboard (copy/paste settings) and layout operations.

## Dependencies

- Phase 1 (Schema Foundation) must be complete
- Phase 2 (Element Schemas) recommended for clipboard compatibility

## Deliverables

### 1. Clipboard Schema (`/src/schemas/operations/clipboard.schema.ts`)

```typescript
// Clipboard contents structure
export interface ClipboardContents {
  sourceElementId: string;
  sourceElementType: string;
  copiedAt: Date;

  // What was copied
  mode: 'all' | 'category' | 'properties';
  category?: PropertyCategory;
  properties?: string[];

  // The actual values
  values: Record<string, unknown>;
}

export type PropertyCategory =
  | 'general'
  | 'validation'
  | 'styling'
  | 'advanced'
  | 'conditional';

// Copy operation interface
export interface CopySettingsOperation {
  type: 'copy_settings';
  params: {
    sourceElementId: string;
    mode: 'all' | 'category' | 'properties';
    category?: PropertyCategory;
    properties?: string[];
  };
}

// Paste operation interface
export interface PasteSettingsOperation {
  type: 'paste_settings';
  params: {
    targetElementId: string;
    mode: 'all' | 'selected';
    properties?: string[];
  };
}

// Paste result
export interface PasteResult {
  success: boolean;
  appliedProperties: string[];
  skippedProperties: { property: string; reason: string }[];
  operations: JsonPatchOperation[];
}

// Service interface
export interface ClipboardService {
  copySettings(
    elementId: string,
    mode: 'all' | 'category' | 'properties',
    options?: { category?: PropertyCategory; properties?: string[] }
  ): ClipboardContents;

  getClipboard(): ClipboardContents | null;

  canPaste(targetElementType: string): {
    compatible: boolean;
    compatibleProperties: string[];
    incompatibleProperties: string[];
  };

  pasteSettings(
    targetElementId: string,
    mode: 'all' | 'selected',
    selectedProperties?: string[]
  ): PasteResult;

  clear(): void;
}
```

### 2. Layout Operations Schema (`/src/schemas/operations/layout.schema.ts`)

```typescript
// Create column layout
export interface CreateColumnLayoutOperation {
  type: 'create_column_layout';
  params: {
    elementIds: string[];
    layout: string[] | number;  // ['1/2', '1/2'] or 2
    position?: number;
  };
}

// Update column ratios
export interface UpdateColumnLayoutOperation {
  type: 'update_column_layout';
  params: {
    columnElementId: string;
    layout: string[];
  };
}

// Wrap elements in container
export interface WrapInContainerOperation {
  type: 'wrap_in_container';
  params: {
    elementIds: string[];
    containerType: 'columns' | 'section' | 'tabs' | 'accordion' | 'steps';
    config?: Record<string, unknown>;
  };
}

// Unwrap container
export interface UnwrapContainerOperation {
  type: 'unwrap_container';
  params: {
    containerId: string;
  };
}

// Move element
export interface MoveElementOperation {
  type: 'move_element';
  params: {
    elementId: string;
    targetParentId?: string | null;
    targetIndex: number;
  };
}

// Duplicate element
export interface DuplicateElementOperation {
  type: 'duplicate_element';
  params: {
    elementId: string;
    deep?: boolean;
  };
}

// All operations union
export type LayoutOperation =
  | CreateColumnLayoutOperation
  | UpdateColumnLayoutOperation
  | WrapInContainerOperation
  | UnwrapContainerOperation
  | MoveElementOperation
  | DuplicateElementOperation;

// Service interface
export interface LayoutService {
  createColumnLayout(
    elementIds: string[],
    layout: string[] | number
  ): { containerId: string; operations: JsonPatchOperation[] };

  updateColumnLayout(
    columnId: string,
    newLayout: string[]
  ): JsonPatchOperation[];

  wrapInContainer(
    elementIds: string[],
    containerType: string,
    config?: Record<string, unknown>
  ): { containerId: string; operations: JsonPatchOperation[] };

  unwrapContainer(containerId: string): JsonPatchOperation[];

  moveElement(
    elementId: string,
    targetParentId: string | null,
    targetIndex: number
  ): JsonPatchOperation[];

  duplicateElement(
    elementId: string,
    deep?: boolean
  ): { newElementId: string; operations: JsonPatchOperation[] };
}
```

### 3. Conditional Logic Schema (`/src/schemas/conditional-logic.schema.ts`)

```typescript
export interface ConditionalRule {
  field: string;
  operator: ConditionalOperator;
  value: string | number | boolean;
}

export interface ConditionalLogic {
  enabled: boolean;
  action: 'show' | 'hide';
  logic: 'and' | 'or';
  rules: ConditionalRule[];
}

export type ConditionalOperator =
  | 'equals'
  | 'not_equals'
  | 'contains'
  | 'not_contains'
  | 'starts_with'
  | 'ends_with'
  | 'greater_than'
  | 'less_than'
  | 'greater_or_equal'
  | 'less_or_equal'
  | 'is_empty'
  | 'is_not_empty'
  | 'is_checked'
  | 'is_not_checked';

export const conditionalLogicSchema: PropertySchema = {
  type: PropertyType.OBJECT,
  properties: {
    enabled: {
      type: PropertyType.BOOLEAN,
      label: 'Enable Conditional Logic',
      default: false,
    },
    action: {
      type: PropertyType.SELECT,
      label: 'Action',
      options: [
        { value: 'show', label: 'Show this element' },
        { value: 'hide', label: 'Hide this element' },
      ],
      default: 'show',
      showWhen: { field: 'enabled', equals: true },
    },
    logic: {
      type: PropertyType.SELECT,
      label: 'Logic',
      options: [
        { value: 'and', label: 'All conditions (AND)' },
        { value: 'or', label: 'Any condition (OR)' },
      ],
      default: 'and',
      showWhen: { field: 'enabled', equals: true },
    },
    rules: {
      type: PropertyType.ARRAY,
      label: 'Rules',
      showWhen: { field: 'enabled', equals: true },
      items: {
        type: PropertyType.OBJECT,
        properties: {
          field: {
            type: PropertyType.FIELD_REFERENCE,
            label: 'Field',
            required: true,
          },
          operator: {
            type: PropertyType.SELECT,
            label: 'Operator',
            options: [/* all operators */],
          },
          value: {
            type: PropertyType.STRING,
            label: 'Value',
            showWhen: { field: 'operator', notIn: ['is_empty', 'is_not_empty'] },
          },
        }
      }
    },
  },
};
```

### 4. Translations Schema (`/src/schemas/translations.schema.ts`)

```typescript
export const translationsSchema = {
  supportedLanguages: [
    { code: 'en', name: 'English', native: 'English' },
    { code: 'es', name: 'Spanish', native: 'Español' },
    { code: 'fr', name: 'French', native: 'Français' },
    { code: 'de', name: 'German', native: 'Deutsch' },
    { code: 'nl', name: 'Dutch', native: 'Nederlands' },
    // ... more languages
  ],

  translatableProperties: [
    'label',
    'placeholder',
    'description',
    'validationMessage',
    'items[].label',
    'tabs[].label',
    'steps[].label',
    // ... from spec
  ],
};

export interface FormTranslations {
  defaultLanguage: string;
  enabledLanguages: string[];
  translations: {
    [languageCode: string]: {
      elements: {
        [elementId: string]: {
          [property: string]: string;
        };
      };
      form: {
        [property: string]: string;
      };
    };
  };
}
```

## File Structure

```
/src/schemas/
├── operations/
│   ├── clipboard.schema.ts
│   ├── layout.schema.ts
│   └── index.ts
├── conditional-logic.schema.ts
└── translations.schema.ts
```

## Clipboard Compatibility Logic

When pasting settings between elements:

1. **Universal properties** (marked `universal: true`) always paste
2. **Type-specific properties** only paste to same element type
3. **Incompatible properties** are skipped with reason

```typescript
// Example compatibility check
function canPaste(source: string, target: string, property: string): boolean {
  const schema = getElementSchema(target);
  const propSchema = schema.properties[property];

  if (!propSchema) return false;  // Property doesn't exist on target
  if (propSchema.universal) return true;  // Always compatible
  return source === target;  // Type-specific: same type only
}
```

## Layout Operations JSON Patch Generation

All operations must generate RFC 6902 JSON Patch for undo/redo:

```typescript
// createColumnLayout(['el_1', 'el_2'], ['1/2', '1/2'])
// Generates:
[
  { op: 'remove', path: '/elements/1' },  // Remove el_2
  { op: 'remove', path: '/elements/0' },  // Remove el_1
  {
    op: 'add',
    path: '/elements/0',
    value: {
      id: 'columns_xyz',
      type: 'columns',
      layout: ['1/2', '1/2'],
      children: ['el_1', 'el_2'],
    }
  },
]
```

## Acceptance Criteria

- [ ] Clipboard interfaces complete
- [ ] Layout operation interfaces complete
- [ ] Conditional logic schema complete
- [ ] Translations schema complete
- [ ] Service interfaces defined
- [ ] JSON Patch generation documented
- [ ] Compatibility logic documented
- [ ] TypeScript compiles without errors

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Sections 9-11)
- **Foundation**: `/src/schemas/types.ts`
- **Output**: `/src/schemas/operations/`, `/src/schemas/conditional-logic.schema.ts`, `/src/schemas/translations.schema.ts`

### Reference
- Phase 27 (JSON Patch): `sessions/tasks/h-implement-triggers-actions-extensibility/27-implement-operations-versioning-system.md`

## Work Log
- [2025-12-03] Task created
