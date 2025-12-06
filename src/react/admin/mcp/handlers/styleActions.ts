import {
  styleRegistry,
  StyleProperties,
  getNodeCapabilities,
  getSupportedProperties,
  getElementNodes,
  NODE_STYLE_CAPABILITIES,
} from '../../schemas/styles';
import { useElementsStore } from '../../apps/form-builder-v2/store/useElementsStore';
import { StyleActionSchema, StyleActionResponse } from '../schemas/styleActionSchema';

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
          action.value as StyleProperties[keyof StyleProperties]
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
          action.value as StyleProperties[keyof StyleProperties]
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
