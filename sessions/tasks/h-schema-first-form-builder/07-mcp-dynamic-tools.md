---
name: 07-mcp-dynamic-tools
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 7: MCP Dynamic Tools

## Goal

Update the MCP server to dynamically generate tools from the schema REST API, achieving full capability parity with the user interface.

## Dependencies

- Phase 6 (REST API Schema Endpoints) must be complete

## Current State

The MCP server (`.mcp/super-forms-server.json`) has **hardcoded** tool definitions:
- Only 15 element types (missing 15+ including all containers)
- Limited property support
- No layout operations
- No clipboard operations
- No automation node manipulation

## Target State

MCP server fetches schema from WordPress and dynamically generates tools:
- All 30+ element types supported
- Full property support per element type
- Layout operations (columns, wrap/unwrap)
- Clipboard operations (copy/paste settings)
- Automation node operations
- Conditional logic support

## Deliverables

### 1. Update MCP Server (`/.mcp/server.ts`)

```typescript
import { Server } from '@modelcontextprotocol/sdk/server/index.js';

interface FullSchema {
  elements: Record<string, ElementSchema>;
  settings: SettingsSchema;
  theme: ThemeSchema;
  automations: AutomationsSchema;
}

class SuperFormsMCPServer {
  private server: Server;
  private wpUrl: string;
  private schema: FullSchema | null = null;
  private schemaFetchedAt: Date | null = null;
  private schemaTTL = 5 * 60 * 1000; // 5 minutes

  constructor() {
    this.wpUrl = process.env.SUPER_FORMS_WP_URL || '';
    this.server = new Server({
      name: 'super-forms',
      version: '2.0.0',
    }, {
      capabilities: {
        tools: {},
      },
    });

    this.setupHandlers();
  }

  async initialize() {
    await this.fetchSchema();
    this.registerDynamicTools();
  }

  private async fetchSchema(): Promise<void> {
    // Check cache
    if (this.schema && this.schemaFetchedAt) {
      const age = Date.now() - this.schemaFetchedAt.getTime();
      if (age < this.schemaTTL) {
        return;
      }
    }

    try {
      const response = await fetch(`${this.wpUrl}/wp-json/super-forms/v1/schema`);
      this.schema = await response.json();
      this.schemaFetchedAt = new Date();
    } catch (error) {
      console.error('Failed to fetch schema:', error);
      // Use fallback schema
      this.schema = this.getFallbackSchema();
    }
  }

  private registerDynamicTools() {
    const tools = this.generateTools();

    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: tools.map(t => ({
        name: t.name,
        description: t.description,
        inputSchema: t.inputSchema,
      })),
    }));

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const tool = tools.find(t => t.name === request.params.name);
      if (!tool) {
        throw new Error(`Unknown tool: ${request.params.name}`);
      }
      return tool.handler(request.params.arguments);
    });
  }

  private generateTools(): Tool[] {
    if (!this.schema) return [];

    return [
      ...this.generateElementTools(),
      ...this.generateLayoutTools(),
      ...this.generateClipboardTools(),
      ...this.generateSettingsTools(),
      ...this.generateThemeTools(),
      ...this.generateAutomationTools(),
      ...this.generateTranslationTools(),
      ...this.generateVersionTools(),
    ];
  }

  // Tool generators...
}
```

### 2. Element Tools

```typescript
private generateElementTools(): Tool[] {
  const tools: Tool[] = [];
  const elementTypes = Object.keys(this.schema!.elements);

  // Generic add_element tool
  tools.push({
    name: 'add_element',
    description: `Add a form element. Supported types: ${elementTypes.join(', ')}`,
    inputSchema: {
      type: 'object',
      properties: {
        formId: { type: 'number', description: 'Form ID' },
        elementType: {
          type: 'string',
          enum: elementTypes,
          description: 'Element type',
        },
        position: {
          type: 'number',
          description: 'Position index (-1 for end)',
        },
        parentId: {
          type: 'string',
          description: 'Parent container ID (null for root)',
        },
        properties: {
          type: 'object',
          description: 'Element properties (varies by type)',
        },
      },
      required: ['formId', 'elementType'],
    },
    handler: async (args) => {
      return this.callWordPress('POST', `/forms/${args.formId}/operations`, [{
        op: 'add',
        path: args.parentId
          ? `/elements/${args.parentId}/children/${args.position ?? -1}`
          : `/elements/${args.position ?? -1}`,
        value: {
          type: args.elementType,
          ...this.getDefaults(args.elementType),
          ...args.properties,
        },
      }]);
    },
  });

  // Generic update_element tool
  tools.push({
    name: 'update_element',
    description: 'Update any element property',
    inputSchema: {
      type: 'object',
      properties: {
        formId: { type: 'number' },
        elementId: { type: 'string' },
        property: {
          type: 'string',
          description: 'Property path (e.g., "label", "validation.required")',
        },
        value: { description: 'New value' },
      },
      required: ['formId', 'elementId', 'property', 'value'],
    },
    handler: async (args) => {
      const path = args.property.includes('.')
        ? args.property.split('.').join('/')
        : args.property;

      return this.callWordPress('POST', `/forms/${args.formId}/operations`, [{
        op: 'replace',
        path: `/elements/${args.elementId}/${path}`,
        value: args.value,
      }]);
    },
  });

  // Remove element
  tools.push({
    name: 'remove_element',
    description: 'Remove an element from the form',
    inputSchema: {
      type: 'object',
      properties: {
        formId: { type: 'number' },
        elementId: { type: 'string' },
      },
      required: ['formId', 'elementId'],
    },
    handler: async (args) => {
      return this.callWordPress('POST', `/forms/${args.formId}/operations`, [{
        op: 'remove',
        path: `/elements/${args.elementId}`,
      }]);
    },
  });

  return tools;
}
```

### 3. Layout Tools

```typescript
private generateLayoutTools(): Tool[] {
  return [
    {
      name: 'create_column_layout',
      description: 'Create a column layout from elements',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementIds: {
            type: 'array',
            items: { type: 'string' },
            description: 'Elements to wrap in columns',
          },
          layout: {
            oneOf: [
              { type: 'number', description: 'Equal columns (2, 3, 4)' },
              {
                type: 'array',
                items: { type: 'string' },
                description: 'Ratios (e.g., ["1/3", "2/3"])',
              },
            ],
          },
        },
        required: ['formId', 'elementIds', 'layout'],
      },
      handler: async (args) => {
        const layout = typeof args.layout === 'number'
          ? Array(args.layout).fill(`1/${args.layout}`)
          : args.layout;

        // Generate operations to wrap elements in columns
        const operations = [];

        // Create columns container
        const columnsId = `columns_${Date.now()}`;
        operations.push({
          op: 'add',
          path: '/elements/-',
          value: {
            id: columnsId,
            type: 'columns',
            layout: layout,
            children: args.elementIds,
          },
        });

        // Move elements into columns
        for (const elementId of args.elementIds) {
          operations.push({
            op: 'remove',
            path: `/elements/${elementId}`,
          });
        }

        return this.callWordPress('POST', `/forms/${args.formId}/operations`, operations);
      },
    },

    {
      name: 'update_column_layout',
      description: 'Change column ratios',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          columnId: { type: 'string' },
          layout: {
            type: 'array',
            items: { type: 'string' },
            description: 'New ratios',
          },
        },
        required: ['formId', 'columnId', 'layout'],
      },
      handler: async (args) => {
        return this.callWordPress('POST', `/forms/${args.formId}/operations`, [{
          op: 'replace',
          path: `/elements/${args.columnId}/layout`,
          value: args.layout,
        }]);
      },
    },

    {
      name: 'move_element',
      description: 'Move/reorder an element',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementId: { type: 'string' },
          targetParentId: { type: 'string', nullable: true },
          targetIndex: { type: 'number' },
        },
        required: ['formId', 'elementId', 'targetIndex'],
      },
      handler: async (args) => {
        const targetPath = args.targetParentId
          ? `/elements/${args.targetParentId}/children/${args.targetIndex}`
          : `/elements/${args.targetIndex}`;

        return this.callWordPress('POST', `/forms/${args.formId}/operations`, [{
          op: 'move',
          from: `/elements/${args.elementId}`,
          path: targetPath,
        }]);
      },
    },

    {
      name: 'duplicate_element',
      description: 'Duplicate an element',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementId: { type: 'string' },
          deep: { type: 'boolean', default: true },
        },
        required: ['formId', 'elementId'],
      },
      handler: async (args) => {
        return this.callWordPress('POST', `/forms/${args.formId}/operations`, [{
          op: 'copy',
          from: `/elements/${args.elementId}`,
          path: `/elements/-`,  // Append
        }]);
      },
    },
  ];
}
```

### 4. Clipboard Tools

```typescript
private generateClipboardTools(): Tool[] {
  return [
    {
      name: 'copy_element_settings',
      description: 'Copy settings from one element for pasting to others',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementId: { type: 'string' },
          mode: {
            type: 'string',
            enum: ['all', 'category', 'properties'],
          },
          category: {
            type: 'string',
            enum: ['general', 'validation', 'styling', 'advanced', 'conditional'],
          },
          properties: {
            type: 'array',
            items: { type: 'string' },
          },
        },
        required: ['formId', 'elementId', 'mode'],
      },
      handler: async (args) => {
        // Get current element
        const form = await this.callWordPress('GET', `/forms/${args.formId}`);
        const element = form.elements.find((e: any) => e.id === args.elementId);

        if (!element) {
          throw new Error('Element not found');
        }

        // Store in session (MCP server state)
        this.clipboard = {
          sourceElementId: args.elementId,
          sourceElementType: element.type,
          copiedAt: new Date(),
          mode: args.mode,
          category: args.category,
          properties: args.properties,
          values: this.extractProperties(element, args),
        };

        return { success: true, message: 'Settings copied to clipboard' };
      },
    },

    {
      name: 'paste_element_settings',
      description: 'Paste previously copied settings to an element',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          targetElementId: { type: 'string' },
          mode: {
            type: 'string',
            enum: ['all', 'selected'],
          },
          properties: {
            type: 'array',
            items: { type: 'string' },
          },
        },
        required: ['formId', 'targetElementId', 'mode'],
      },
      handler: async (args) => {
        if (!this.clipboard) {
          throw new Error('Clipboard is empty');
        }

        // Get target element type
        const form = await this.callWordPress('GET', `/forms/${args.formId}`);
        const targetElement = form.elements.find((e: any) => e.id === args.targetElementId);

        if (!targetElement) {
          throw new Error('Target element not found');
        }

        // Filter compatible properties
        const operations = [];
        const propertiesToPaste = args.mode === 'selected' && args.properties
          ? args.properties
          : Object.keys(this.clipboard.values);

        for (const prop of propertiesToPaste) {
          if (this.isPropertyCompatible(prop, this.clipboard.sourceElementType, targetElement.type)) {
            operations.push({
              op: 'replace',
              path: `/elements/${args.targetElementId}/${prop}`,
              value: this.clipboard.values[prop],
            });
          }
        }

        if (operations.length === 0) {
          return { success: false, message: 'No compatible properties to paste' };
        }

        await this.callWordPress('POST', `/forms/${args.formId}/operations`, operations);

        return {
          success: true,
          appliedProperties: operations.map(o => o.path.split('/').pop()),
        };
      },
    },
  ];
}
```

### 5. WordPress API Helper

```typescript
private async callWordPress(method: string, endpoint: string, body?: unknown) {
  const url = `${this.wpUrl}/wp-json/super-forms/v1${endpoint}`;

  const response = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': this.wpNonce,
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'WordPress API error');
  }

  return response.json();
}
```

## File Structure

```
/.mcp/
├── server.ts           # Main MCP server (updated)
├── tools/
│   ├── elements.ts     # Element tool generators
│   ├── layout.ts       # Layout tool generators
│   ├── clipboard.ts    # Clipboard tool generators
│   ├── settings.ts     # Settings/theme tools
│   ├── automations.ts  # Automation tools
│   └── translations.ts # Translation tools
├── types.ts            # TypeScript types
└── package.json        # Dependencies
```

## Tool Summary

| Category | Tools |
|----------|-------|
| **Elements** | add_element, update_element, remove_element |
| **Layout** | create_column_layout, update_column_layout, move_element, duplicate_element, wrap_in_container, unwrap_container |
| **Clipboard** | copy_element_settings, paste_element_settings |
| **Settings** | update_form_settings |
| **Theme** | update_theme_settings |
| **Automations** | add_automation_node, update_automation_node, remove_automation_node, connect_nodes |
| **Translations** | add_translation, remove_translation |
| **Versions** | save_version, revert_version, list_versions |

## Acceptance Criteria

- [ ] MCP server fetches schema from WordPress
- [ ] Schema cached with 5-minute TTL
- [ ] All element types supported dynamically
- [ ] Layout tools create/modify columns
- [ ] Clipboard tools copy/paste settings
- [ ] Property compatibility checking works
- [ ] Automation tools manage nodes
- [ ] Translation tools work
- [ ] Version tools work
- [ ] Error handling is robust
- [ ] Fallback schema works when WordPress unreachable

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Section 13)
- **Current MCP**: `.mcp/super-forms-server.json`
- **Schema API**: `src/includes/class-schema-rest-controller.php` (from Phase 6)
- **Output**: `.mcp/server.ts` and related files

### Reference
- MCP SDK docs: https://modelcontextprotocol.io/
- Existing MCP README: `.mcp/README.md`

## Work Log
- [2025-12-03] Task created
