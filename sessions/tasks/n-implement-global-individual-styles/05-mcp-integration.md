---
name: 05-mcp-integration
status: not-started
---

# Subtask 5: MCP Integration

## Goal

Create MCP (Model Context Protocol) action handlers that allow LLM agents to programmatically manipulate form styles. This enables AI-assisted form building and theming.

## Success Criteria

- [ ] `StyleActionSchema` defined with Zod for all style operations
- [ ] MCP handler registered for style actions
- [ ] Actions: getGlobalStyle, setGlobalStyle, getNodeCapabilities, listNodeTypes
- [ ] Actions: setElementStyleOverride, promoteToGlobal, resetToGlobal
- [ ] Proper error handling and validation
- [ ] Documentation for LLM agents

## Files to Create

```
/src/mcp/
├── handlers/
│   └── styleActions.ts           # Style action handler
├── schemas/
│   └── styleActionSchema.ts      # Zod schema for style actions
```

## Files to Modify

```
/src/mcp/
├── index.ts                      # Register style handler
```

## Implementation Details

### 1. styleActionSchema.ts

Define the schema for all style-related MCP actions:

```typescript
import { z } from 'zod';
import {
  NodeTypeSchema,
  StylePropertySchema,
  SpacingSchema,
} from '@/schemas/styles';

// =============================================================================
// Individual Action Schemas
// =============================================================================

const GetGlobalStyleAction = z.object({
  action: z.literal('getGlobalStyle'),
  nodeType: NodeTypeSchema,
});

const SetGlobalStyleAction = z.object({
  action: z.literal('setGlobalStyle'),
  nodeType: NodeTypeSchema,
  updates: StylePropertySchema.partial(),
});

const SetGlobalPropertyAction = z.object({
  action: z.literal('setGlobalProperty'),
  nodeType: NodeTypeSchema,
  property: z.string(),
  value: z.any(),
});

const GetNodeCapabilitiesAction = z.object({
  action: z.literal('getNodeCapabilities'),
  nodeType: NodeTypeSchema,
});

const ListNodeTypesAction = z.object({
  action: z.literal('listNodeTypes'),
});

const ListElementNodesAction = z.object({
  action: z.literal('listElementNodes'),
  elementType: z.string(),
});

const GetElementStyleAction = z.object({
  action: z.literal('getElementStyle'),
  elementId: z.string(),
  nodeType: NodeTypeSchema.optional(),
});

const SetElementStyleOverrideAction = z.object({
  action: z.literal('setElementStyleOverride'),
  elementId: z.string(),
  nodeType: NodeTypeSchema,
  property: z.string(),
  value: z.any(),
});

const SetElementStyleOverridesAction = z.object({
  action: z.literal('setElementStyleOverrides'),
  elementId: z.string(),
  nodeType: NodeTypeSchema,
  overrides: StylePropertySchema.partial(),
});

const RemoveElementStyleOverrideAction = z.object({
  action: z.literal('removeElementStyleOverride'),
  elementId: z.string(),
  nodeType: NodeTypeSchema,
  property: z.string(),
});

const PromoteToGlobalAction = z.object({
  action: z.literal('promoteToGlobal'),
  elementId: z.string(),
  nodeType: NodeTypeSchema.optional(), // If omitted, promotes all nodes
});

const ResetToGlobalAction = z.object({
  action: z.literal('resetToGlobal'),
  elementId: z.string(),
  nodeType: NodeTypeSchema.optional(), // If omitted, resets all nodes
});

const ResetGlobalToDefaultsAction = z.object({
  action: z.literal('resetGlobalToDefaults'),
  nodeType: NodeTypeSchema.optional(), // If omitted, resets all node types
});

const ExportStylesAction = z.object({
  action: z.literal('exportStyles'),
});

const ImportStylesAction = z.object({
  action: z.literal('importStyles'),
  styles: z.string(), // JSON string
});

// =============================================================================
// Combined Action Schema
// =============================================================================

export const StyleActionSchema = z.discriminatedUnion('action', [
  // Global style queries
  GetGlobalStyleAction,
  SetGlobalStyleAction,
  SetGlobalPropertyAction,
  GetNodeCapabilitiesAction,
  ListNodeTypesAction,
  ListElementNodesAction,
  ResetGlobalToDefaultsAction,

  // Element style operations
  GetElementStyleAction,
  SetElementStyleOverrideAction,
  SetElementStyleOverridesAction,
  RemoveElementStyleOverrideAction,
  PromoteToGlobalAction,
  ResetToGlobalAction,

  // Import/Export
  ExportStylesAction,
  ImportStylesAction,
]);

export type StyleAction = z.infer<typeof StyleActionSchema>;

// =============================================================================
// Response Types
// =============================================================================

export interface StyleActionResponse {
  success: boolean;
  data?: any;
  error?: string;
}
```

### 2. styleActions.ts - Handler Implementation

```typescript
import {
  styleRegistry,
  NodeType,
  StyleProperties,
  getNodeCapabilities,
  getSupportedProperties,
  getElementNodes,
  NODE_STYLE_CAPABILITIES,
  DEFAULT_GLOBAL_STYLES,
} from '@/schemas/styles';
import { useElementsStore } from '@/apps/form-builder-v2/store/useElementsStore';
import { StyleActionSchema, StyleAction, StyleActionResponse } from '../schemas/styleActionSchema';

/**
 * MCP Handler for style-related actions.
 *
 * This handler enables LLM agents to:
 * - Query and modify global theme styles
 * - Get style capabilities for nodes
 * - Override individual element styles
 * - Promote element styles to global theme
 */
export async function handleStyleAction(
  rawAction: unknown
): Promise<StyleActionResponse> {
  // Validate the action
  const parseResult = StyleActionSchema.safeParse(rawAction);
  if (!parseResult.success) {
    return {
      success: false,
      error: `Invalid action: ${parseResult.error.message}`,
    };
  }

  const action = parseResult.data;

  try {
    switch (action.action) {
      // =====================================================================
      // Global Style Queries
      // =====================================================================

      case 'getGlobalStyle': {
        const style = styleRegistry.getGlobalStyle(action.nodeType);
        return {
          success: true,
          data: {
            nodeType: action.nodeType,
            style,
          },
        };
      }

      case 'setGlobalStyle': {
        styleRegistry.setGlobalStyle(action.nodeType, action.updates);
        return {
          success: true,
          data: {
            nodeType: action.nodeType,
            updatedStyle: styleRegistry.getGlobalStyle(action.nodeType),
          },
        };
      }

      case 'setGlobalProperty': {
        styleRegistry.setGlobalProperty(
          action.nodeType,
          action.property as keyof StyleProperties,
          action.value
        );
        return {
          success: true,
          data: {
            nodeType: action.nodeType,
            property: action.property,
            value: action.value,
          },
        };
      }

      case 'getNodeCapabilities': {
        const capabilities = getNodeCapabilities(action.nodeType);
        const supportedProperties = getSupportedProperties(action.nodeType);
        return {
          success: true,
          data: {
            nodeType: action.nodeType,
            capabilities,
            supportedProperties,
          },
        };
      }

      case 'listNodeTypes': {
        const nodeTypes = Object.keys(NODE_STYLE_CAPABILITIES);
        return {
          success: true,
          data: {
            nodeTypes,
            descriptions: {
              label: 'Field label text',
              description: 'Help text below fields',
              input: 'Text input, textarea, select',
              placeholder: 'Placeholder text styling',
              error: 'Validation error message',
              required: 'Required indicator (*)',
              fieldContainer: 'Wrapper around entire field',
              heading: 'h1-h6 text',
              paragraph: 'Body text',
              button: 'Button styling',
              divider: 'Separator line',
              optionLabel: 'Radio/checkbox option label',
              cardContainer: 'Card wrapper for card-style choices',
            },
          },
        };
      }

      case 'listElementNodes': {
        const nodes = getElementNodes(action.elementType);
        return {
          success: true,
          data: {
            elementType: action.elementType,
            nodes,
          },
        };
      }

      case 'resetGlobalToDefaults': {
        if (action.nodeType) {
          styleRegistry.resetToDefault(action.nodeType);
        } else {
          styleRegistry.resetAllToDefaults();
        }
        return {
          success: true,
          data: {
            reset: action.nodeType ?? 'all',
          },
        };
      }

      // =====================================================================
      // Element Style Operations
      // =====================================================================

      case 'getElementStyle': {
        const store = useElementsStore.getState();
        const element = store.items[action.elementId];

        if (!element) {
          return {
            success: false,
            error: `Element not found: ${action.elementId}`,
          };
        }

        if (action.nodeType) {
          // Get specific node style
          const globalStyle = styleRegistry.getGlobalStyle(action.nodeType);
          const overrides = element.styleOverrides?.[action.nodeType];
          const resolvedStyle = { ...globalStyle, ...overrides };

          return {
            success: true,
            data: {
              elementId: action.elementId,
              nodeType: action.nodeType,
              globalStyle,
              overrides,
              resolvedStyle,
            },
          };
        } else {
          // Get all node styles
          const nodes = getElementNodes(element.type);
          const styles = nodes.map((nodeType) => {
            const globalStyle = styleRegistry.getGlobalStyle(nodeType);
            const overrides = element.styleOverrides?.[nodeType];
            return {
              nodeType,
              globalStyle,
              overrides,
              resolvedStyle: { ...globalStyle, ...overrides },
              hasOverrides: !!overrides,
            };
          });

          return {
            success: true,
            data: {
              elementId: action.elementId,
              elementType: element.type,
              styles,
            },
          };
        }
      }

      case 'setElementStyleOverride': {
        const store = useElementsStore.getState();
        store.setStyleOverride(
          action.elementId,
          action.nodeType,
          action.property as keyof StyleProperties,
          action.value
        );
        return {
          success: true,
          data: {
            elementId: action.elementId,
            nodeType: action.nodeType,
            property: action.property,
            value: action.value,
          },
        };
      }

      case 'setElementStyleOverrides': {
        const store = useElementsStore.getState();
        store.setStyleOverrides(action.elementId, action.nodeType, action.overrides);
        return {
          success: true,
          data: {
            elementId: action.elementId,
            nodeType: action.nodeType,
            overrides: action.overrides,
          },
        };
      }

      case 'removeElementStyleOverride': {
        const store = useElementsStore.getState();
        store.removeStyleOverride(
          action.elementId,
          action.nodeType,
          action.property as keyof StyleProperties
        );
        return {
          success: true,
          data: {
            elementId: action.elementId,
            nodeType: action.nodeType,
            property: action.property,
            removed: true,
          },
        };
      }

      case 'promoteToGlobal': {
        const store = useElementsStore.getState();
        const element = store.items[action.elementId];

        if (!element) {
          return {
            success: false,
            error: `Element not found: ${action.elementId}`,
          };
        }

        const nodesToPromote = action.nodeType
          ? [action.nodeType]
          : getElementNodes(element.type);

        const promoted: string[] = [];

        nodesToPromote.forEach((nodeType) => {
          const overrides = element.styleOverrides?.[nodeType];
          if (overrides) {
            Object.entries(overrides).forEach(([prop, value]) => {
              styleRegistry.setGlobalProperty(
                nodeType,
                prop as keyof StyleProperties,
                value
              );
              promoted.push(`${nodeType}.${prop}`);
            });
          }
        });

        // Clear the overrides after promoting
        if (action.nodeType) {
          store.clearNodeStyleOverrides(action.elementId, action.nodeType);
        } else {
          store.clearAllStyleOverrides(action.elementId);
        }

        return {
          success: true,
          data: {
            elementId: action.elementId,
            promoted,
          },
        };
      }

      case 'resetToGlobal': {
        const store = useElementsStore.getState();

        if (action.nodeType) {
          store.clearNodeStyleOverrides(action.elementId, action.nodeType);
        } else {
          store.clearAllStyleOverrides(action.elementId);
        }

        return {
          success: true,
          data: {
            elementId: action.elementId,
            reset: action.nodeType ?? 'all',
          },
        };
      }

      // =====================================================================
      // Import/Export
      // =====================================================================

      case 'exportStyles': {
        const json = styleRegistry.exportStyles();
        return {
          success: true,
          data: {
            styles: json,
          },
        };
      }

      case 'importStyles': {
        styleRegistry.importStyles(action.styles);
        return {
          success: true,
          data: {
            imported: true,
          },
        };
      }

      default:
        return {
          success: false,
          error: 'Unknown action',
        };
    }
  } catch (error) {
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error',
    };
  }
}

// =============================================================================
// MCP Tool Definition (for LLM agents)
// =============================================================================

export const styleToolDefinition = {
  name: 'form_styles',
  description: `
Manage form element styles with global themes and individual overrides.

## Concepts

- **Node Types**: Elements contain styleable "nodes" (label, input, error, etc.)
- **Global Styles**: Theme-level defaults that apply to all elements
- **Overrides**: Per-element style customizations that override globals

## Common Operations

### Change global theme:
{
  "action": "setGlobalProperty",
  "nodeType": "label",
  "property": "fontSize",
  "value": 16
}

### Get element's current style:
{
  "action": "getElementStyle",
  "elementId": "element-123"
}

### Override a specific element:
{
  "action": "setElementStyleOverride",
  "elementId": "element-123",
  "nodeType": "input",
  "property": "backgroundColor",
  "value": "#f0f0f0"
}

### Reset element to global:
{
  "action": "resetToGlobal",
  "elementId": "element-123"
}
`,
  inputSchema: StyleActionSchema,
};
```

### 3. MCP Registration

In `/src/mcp/index.ts`:

```typescript
import { handleStyleAction, styleToolDefinition } from './handlers/styleActions';

// Register the style tool
registerTool({
  definition: styleToolDefinition,
  handler: handleStyleAction,
});
```

## Example LLM Interactions

### "Make all labels blue and larger"
```json
{
  "action": "setGlobalStyle",
  "nodeType": "label",
  "updates": {
    "color": "#2563eb",
    "fontSize": 16
  }
}
```

### "Give this specific input a red border"
```json
{
  "action": "setElementStyleOverride",
  "elementId": "text-input-1",
  "nodeType": "input",
  "property": "borderColor",
  "value": "#dc2626"
}
```

### "What styles can I apply to buttons?"
```json
{
  "action": "getNodeCapabilities",
  "nodeType": "button"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "nodeType": "button",
    "capabilities": {
      "fontSize": true,
      "fontFamily": true,
      "fontWeight": true,
      "color": true,
      "backgroundColor": true,
      "padding": true,
      "borderRadius": true,
      ...
    },
    "supportedProperties": ["fontSize", "fontFamily", ...]
  }
}
```

## Testing

1. **Action validation:**
   - Invalid actions rejected with clear error
   - All action types parse correctly

2. **Global style operations:**
   - Get/set global styles work
   - Changes propagate to registry

3. **Element operations:**
   - Override single property
   - Override multiple properties
   - Remove override
   - Reset to global

4. **Promote to global:**
   - Element overrides become global
   - Element overrides cleared after promotion

## Dependencies

- Subtask 01 (Core Schema & Registry)
- Subtask 02 (Store Integration)
- MCP infrastructure (may need to be created if not exists)

## Notes

- All actions are validated with Zod before processing
- Handler is async to support future database operations
- Error responses include descriptive messages for LLM debugging
- Tool definition includes examples for LLM reference
