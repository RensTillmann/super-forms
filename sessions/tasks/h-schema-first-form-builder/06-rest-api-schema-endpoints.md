---
name: 06-rest-api-schema-endpoints
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
parent: h-schema-first-form-builder
---

# Phase 6: REST API Schema Endpoints

## Goal

Create PHP REST API endpoints that expose schemas and provide runtime validation using JSON Schema generated from Zod.

## Dependencies

- Phases 1-5 (All schemas) should be complete
- `zod-to-json-schema` package (installed in Phase 1)

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/schema` | Full schema (all domains) |
| GET | `/schema/elements` | All element schemas |
| GET | `/schema/elements/{type}` | Single element schema |
| GET | `/schema/settings` | Form settings schema |
| GET | `/schema/theme` | Theme settings schema |
| GET | `/schema/automations` | All node schemas |
| GET | `/schema/translations` | Translation structure |
| GET | `/capabilities` | MCP capability manifest |

All endpoints are **public** (no authentication required) - schemas contain no sensitive data.

## Build Pipeline: Zod → JSON Schema → PHP

```
┌─────────────────────────────────────────────────────────────┐
│                    BUILD PIPELINE                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  TypeScript (Zod)                                          │
│  /src/schemas/**/*.schema.ts                               │
│            │                                                │
│            ▼                                                │
│  ┌─────────────────────┐                                   │
│  │ npm run build:schemas│                                   │
│  └─────────────────────┘                                   │
│            │                                                │
│            ├──────────────────┬──────────────────┐         │
│            ▼                  ▼                  ▼         │
│     JSON Schema          PHP Arrays        MCP Tools       │
│     (validation)        (REST API)        (dynamic)        │
│                                                             │
│  Output files:                                              │
│  ├─ /src/includes/schemas/generated-schema.php             │
│  ├─ /src/includes/schemas/json-schema.json                 │
│  └─ /.mcp/generated-tools.json                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Deliverables

### 1. Build Script (`/scripts/generate-schemas.ts`)

```typescript
// /scripts/generate-schemas.ts

import * as fs from 'fs';
import * as path from 'path';
import { zodToJsonSchema } from 'zod-to-json-schema';

// Import schemas (triggers registration)
import '../src/schemas/elements';
import '../src/schemas/settings';
import '../src/schemas/automations';

import {
  getRegistry,
  ElementSchemaSchema,
  PropertySchemaSchema,
} from '../src/schemas';

const OUTPUT_DIR = path.join(__dirname, '../src/includes/schemas');

// Ensure output directory exists
if (!fs.existsSync(OUTPUT_DIR)) {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

// ============================================================
// 1. Generate JSON Schema (for validation)
// ============================================================

const elementJsonSchema = zodToJsonSchema(ElementSchemaSchema, {
  name: 'ElementSchema',
  definitions: {
    PropertySchema: PropertySchemaSchema,
  },
});

const propertyJsonSchema = zodToJsonSchema(PropertySchemaSchema, {
  name: 'PropertySchema',
});

const jsonSchemaOutput = {
  $schema: 'http://json-schema.org/draft-07/schema#',
  definitions: {
    ElementSchema: elementJsonSchema,
    PropertySchema: propertyJsonSchema,
  },
};

fs.writeFileSync(
  path.join(OUTPUT_DIR, 'json-schema.json'),
  JSON.stringify(jsonSchemaOutput, null, 2)
);

console.log('✅ Generated: json-schema.json');

// ============================================================
// 2. Generate PHP Array (for REST API)
// ============================================================

const registry = getRegistry();

function toPhpArray(value: unknown, indent = 0): string {
  const spaces = '    '.repeat(indent);
  const nextSpaces = '    '.repeat(indent + 1);

  if (value === null) {
    return 'null';
  }

  if (value === undefined) {
    return 'null';
  }

  if (typeof value === 'boolean') {
    return value ? 'true' : 'false';
  }

  if (typeof value === 'number') {
    return String(value);
  }

  if (typeof value === 'string') {
    // Escape single quotes and backslashes
    const escaped = value.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    return `'${escaped}'`;
  }

  if (Array.isArray(value)) {
    if (value.length === 0) {
      return '[]';
    }
    const items = value.map(item => `${nextSpaces}${toPhpArray(item, indent + 1)}`);
    return `[\n${items.join(',\n')},\n${spaces}]`;
  }

  if (typeof value === 'object') {
    const entries = Object.entries(value);
    if (entries.length === 0) {
      return '[]';
    }
    const items = entries.map(([key, val]) => {
      const phpKey = /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(key) ? `'${key}'` : `'${key}'`;
      return `${nextSpaces}${phpKey} => ${toPhpArray(val, indent + 1)}`;
    });
    return `[\n${items.join(',\n')},\n${spaces}]`;
  }

  return 'null';
}

const phpContent = `<?php
/**
 * Auto-generated schema file
 * DO NOT EDIT - Run 'npm run build:schemas' to regenerate
 *
 * @generated ${new Date().toISOString()}
 * @source /src/schemas/
 */

if (!defined('ABSPATH')) {
    exit;
}

return ${toPhpArray(registry)};
`;

fs.writeFileSync(
  path.join(OUTPUT_DIR, 'generated-schema.php'),
  phpContent
);

console.log('✅ Generated: generated-schema.php');

// ============================================================
// 3. Generate MCP Tool Definitions
// ============================================================

const elementTypes = Object.keys(registry.elements);

const mcpTools = {
  generated: new Date().toISOString(),
  elementTypes,
  tools: [
    {
      name: 'add_element',
      description: `Add a form element. Supported types: ${elementTypes.join(', ')}`,
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number', description: 'Form ID' },
          elementType: { type: 'string', enum: elementTypes },
          position: { type: 'number', description: 'Position index' },
          parentId: { type: 'string', description: 'Parent container ID' },
          properties: { type: 'object', description: 'Element properties' },
        },
        required: ['formId', 'elementType'],
      },
    },
    {
      name: 'update_element',
      description: 'Update any element property',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementId: { type: 'string' },
          property: { type: 'string', description: 'Property path' },
          value: { description: 'New value' },
        },
        required: ['formId', 'elementId', 'property', 'value'],
      },
    },
    {
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
    },
    // Layout tools
    {
      name: 'create_column_layout',
      description: 'Wrap elements in a column layout',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementIds: { type: 'array', items: { type: 'string' } },
          layout: {
            oneOf: [
              { type: 'number', description: 'Equal columns (2, 3, 4)' },
              { type: 'array', items: { type: 'string' }, description: 'Ratios' },
            ],
          },
        },
        required: ['formId', 'elementIds', 'layout'],
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
          targetParentId: { type: 'string' },
          targetIndex: { type: 'number' },
        },
        required: ['formId', 'elementId', 'targetIndex'],
      },
    },
    // Clipboard tools
    {
      name: 'copy_element_settings',
      description: 'Copy settings from an element',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          elementId: { type: 'string' },
          mode: { type: 'string', enum: ['all', 'category', 'properties'] },
          category: { type: 'string', enum: ['general', 'validation', 'styling', 'advanced', 'conditional'] },
          properties: { type: 'array', items: { type: 'string' } },
        },
        required: ['formId', 'elementId', 'mode'],
      },
    },
    {
      name: 'paste_element_settings',
      description: 'Paste copied settings to an element',
      inputSchema: {
        type: 'object',
        properties: {
          formId: { type: 'number' },
          targetElementId: { type: 'string' },
          mode: { type: 'string', enum: ['all', 'selected'] },
          properties: { type: 'array', items: { type: 'string' } },
        },
        required: ['formId', 'targetElementId', 'mode'],
      },
    },
  ],
};

fs.writeFileSync(
  path.join(__dirname, '../.mcp/generated-tools.json'),
  JSON.stringify(mcpTools, null, 2)
);

console.log('✅ Generated: .mcp/generated-tools.json');

// ============================================================
// Summary
// ============================================================

console.log('\n📦 Schema build complete!');
console.log(`   Elements: ${elementTypes.length}`);
console.log(`   Output: ${OUTPUT_DIR}`);
```

### 2. Schema Validator (`/src/includes/class-schema-validator.php`)

PHP class that validates element data using JSON Schema:

```php
<?php
/**
 * Schema Validator
 *
 * Validates element and form data against generated JSON Schema.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Schema_Validator {

    private static $instance = null;
    private $json_schema = null;
    private $element_schemas = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load JSON Schema for validation
     */
    private function get_json_schema() {
        if ($this->json_schema !== null) {
            return $this->json_schema;
        }

        $schema_file = SUPER_PLUGIN_DIR . '/includes/schemas/json-schema.json';

        if (!file_exists($schema_file)) {
            return null;
        }

        $content = file_get_contents($schema_file);
        $this->json_schema = json_decode($content, true);

        return $this->json_schema;
    }

    /**
     * Get element schemas from generated PHP
     */
    private function get_element_schemas() {
        if ($this->element_schemas !== null) {
            return $this->element_schemas;
        }

        $this->element_schemas = SUPER_Schema_Loader::instance()->get_element_schemas();
        return $this->element_schemas;
    }

    /**
     * Validate element data against its schema
     *
     * @param string $element_type Element type (e.g., 'text', 'dropdown')
     * @param array  $data         Element data to validate
     * @return true|WP_Error True if valid, WP_Error with details if invalid
     */
    public function validate_element($element_type, $data) {
        $schemas = $this->get_element_schemas();

        if (!isset($schemas[$element_type])) {
            return new WP_Error(
                'unknown_element_type',
                sprintf('Unknown element type: %s', $element_type),
                ['status' => 400]
            );
        }

        $schema = $schemas[$element_type];
        $errors = [];

        // Validate properties from all categories
        foreach ($schema['properties'] as $category => $properties) {
            if (!is_array($properties)) {
                continue;
            }

            foreach ($properties as $prop_name => $prop_schema) {
                $value = $data[$prop_name] ?? null;
                $error = $this->validate_property($prop_name, $value, $prop_schema);

                if ($error) {
                    $errors[] = $error;
                }
            }
        }

        if (!empty($errors)) {
            return new WP_Error(
                'validation_failed',
                'Element validation failed',
                [
                    'status' => 400,
                    'errors' => $errors,
                ]
            );
        }

        return true;
    }

    /**
     * Validate a single property value
     */
    private function validate_property($name, $value, $schema) {
        // Check required
        if (!empty($schema['required']) && ($value === null || $value === '')) {
            return [
                'property' => $name,
                'message' => 'Required field is missing',
                'code' => 'required',
            ];
        }

        // Skip validation if empty and not required
        if ($value === null || $value === '') {
            return null;
        }

        // Type validation
        $type = $schema['type'] ?? 'string';

        switch ($type) {
            case 'string':
                if (!is_string($value)) {
                    return [
                        'property' => $name,
                        'message' => 'Expected string',
                        'code' => 'type_error',
                    ];
                }

                // Pattern validation
                if (!empty($schema['pattern'])) {
                    if (!preg_match('/' . $schema['pattern'] . '/', $value)) {
                        return [
                            'property' => $name,
                            'message' => 'Value does not match required pattern',
                            'code' => 'pattern_error',
                        ];
                    }
                }

                // Length validation
                if (isset($schema['min']) && strlen($value) < $schema['min']) {
                    return [
                        'property' => $name,
                        'message' => sprintf('Minimum length is %d', $schema['min']),
                        'code' => 'min_length',
                    ];
                }

                if (isset($schema['max']) && strlen($value) > $schema['max']) {
                    return [
                        'property' => $name,
                        'message' => sprintf('Maximum length is %d', $schema['max']),
                        'code' => 'max_length',
                    ];
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    return [
                        'property' => $name,
                        'message' => 'Expected number',
                        'code' => 'type_error',
                    ];
                }

                $num = floatval($value);

                if (isset($schema['min']) && $num < $schema['min']) {
                    return [
                        'property' => $name,
                        'message' => sprintf('Minimum value is %s', $schema['min']),
                        'code' => 'min_value',
                    ];
                }

                if (isset($schema['max']) && $num > $schema['max']) {
                    return [
                        'property' => $name,
                        'message' => sprintf('Maximum value is %s', $schema['max']),
                        'code' => 'max_value',
                    ];
                }
                break;

            case 'boolean':
                if (!is_bool($value) && $value !== 0 && $value !== 1 && $value !== '0' && $value !== '1') {
                    return [
                        'property' => $name,
                        'message' => 'Expected boolean',
                        'code' => 'type_error',
                    ];
                }
                break;

            case 'select':
                if (!empty($schema['options']) && is_array($schema['options'])) {
                    $valid_values = array_column($schema['options'], 'value');
                    if (!in_array($value, $valid_values, true)) {
                        return [
                            'property' => $name,
                            'message' => 'Invalid option value',
                            'code' => 'invalid_option',
                        ];
                    }
                }
                break;

            case 'multi_select':
            case 'array':
                if (!is_array($value)) {
                    return [
                        'property' => $name,
                        'message' => 'Expected array',
                        'code' => 'type_error',
                    ];
                }
                break;

            case 'object':
                if (!is_array($value) && !is_object($value)) {
                    return [
                        'property' => $name,
                        'message' => 'Expected object',
                        'code' => 'type_error',
                    ];
                }
                break;
        }

        return null;
    }

    /**
     * Validate form operations (JSON Patch)
     */
    public function validate_operations($operations, $form_data) {
        $errors = [];

        foreach ($operations as $index => $op) {
            // Validate operation structure
            if (!isset($op['op']) || !isset($op['path'])) {
                $errors[] = [
                    'operation' => $index,
                    'message' => 'Invalid operation structure',
                ];
                continue;
            }

            // Validate operation type
            $valid_ops = ['add', 'remove', 'replace', 'move', 'copy'];
            if (!in_array($op['op'], $valid_ops, true)) {
                $errors[] = [
                    'operation' => $index,
                    'message' => sprintf('Invalid operation type: %s', $op['op']),
                ];
            }

            // For add/replace, validate the value against schema if it's an element
            if (in_array($op['op'], ['add', 'replace']) && isset($op['value'])) {
                if (preg_match('/^\/elements\//', $op['path']) && isset($op['value']['type'])) {
                    $validation = $this->validate_element($op['value']['type'], $op['value']);
                    if (is_wp_error($validation)) {
                        $errors[] = [
                            'operation' => $index,
                            'message' => $validation->get_error_message(),
                            'details' => $validation->get_error_data(),
                        ];
                    }
                }
            }
        }

        if (!empty($errors)) {
            return new WP_Error(
                'operations_validation_failed',
                'One or more operations failed validation',
                ['status' => 400, 'errors' => $errors]
            );
        }

        return true;
    }
}
```

### 3. Schema REST Controller (`/src/includes/class-schema-rest-controller.php`)

```php
<?php
/**
 * Schema REST Controller
 *
 * Exposes schemas via REST API for MCP and frontend consumption.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Schema_REST_Controller extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'super-forms/v1';
        $this->rest_base = 'schema';
    }

    public function register_routes() {
        // GET /schema - Full schema
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_full_schema'],
            'permission_callback' => '__return_true',
        ]);

        // GET /schema/elements
        register_rest_route($this->namespace, '/' . $this->rest_base . '/elements', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_element_schemas'],
            'permission_callback' => '__return_true',
            'args' => [
                'category' => [
                    'type' => 'string',
                    'enum' => ['basic', 'choice', 'layout', 'content', 'advanced', 'integration'],
                ],
            ],
        ]);

        // GET /schema/elements/{type}
        register_rest_route($this->namespace, '/' . $this->rest_base . '/elements/(?P<type>[a-z_-]+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_element_schema'],
            'permission_callback' => '__return_true',
        ]);

        // GET /schema/settings
        register_rest_route($this->namespace, '/' . $this->rest_base . '/settings', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_settings_schema'],
            'permission_callback' => '__return_true',
        ]);

        // GET /schema/theme
        register_rest_route($this->namespace, '/' . $this->rest_base . '/theme', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_theme_schema'],
            'permission_callback' => '__return_true',
        ]);

        // GET /schema/automations
        register_rest_route($this->namespace, '/' . $this->rest_base . '/automations', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_automation_schemas'],
            'permission_callback' => '__return_true',
            'args' => [
                'category' => [
                    'type' => 'string',
                    'enum' => ['trigger', 'action', 'condition', 'control'],
                ],
            ],
        ]);

        // GET /capabilities - MCP capability manifest
        register_rest_route($this->namespace, '/capabilities', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_capabilities'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * GET /schema - Full schema
     */
    public function get_full_schema($request) {
        $loader = SUPER_Schema_Loader::instance();

        return rest_ensure_response([
            'version' => $loader->get_version(),
            'generatedAt' => $loader->get_generated_at(),
            'elements' => $loader->get_element_schemas(),
            'settings' => $loader->get_settings_schema(),
            'theme' => $loader->get_theme_schema(),
            'automations' => $loader->get_automation_schemas(),
        ]);
    }

    /**
     * GET /schema/elements
     */
    public function get_element_schemas($request) {
        $elements = SUPER_Schema_Loader::instance()->get_element_schemas();

        // Filter by category if specified
        $category = $request->get_param('category');
        if ($category) {
            $elements = array_filter($elements, function($schema) use ($category) {
                return ($schema['category'] ?? '') === $category;
            });
        }

        return rest_ensure_response($elements);
    }

    /**
     * GET /schema/elements/{type}
     */
    public function get_element_schema($request) {
        $type = $request->get_param('type');
        $schema = SUPER_Schema_Loader::instance()->get_element_schema($type);

        if (!$schema) {
            return new WP_Error(
                'not_found',
                sprintf('Element type not found: %s', $type),
                ['status' => 404]
            );
        }

        return rest_ensure_response($schema);
    }

    /**
     * GET /schema/settings
     */
    public function get_settings_schema($request) {
        return rest_ensure_response(
            SUPER_Schema_Loader::instance()->get_settings_schema()
        );
    }

    /**
     * GET /schema/theme
     */
    public function get_theme_schema($request) {
        return rest_ensure_response(
            SUPER_Schema_Loader::instance()->get_theme_schema()
        );
    }

    /**
     * GET /schema/automations
     */
    public function get_automation_schemas($request) {
        $automations = SUPER_Schema_Loader::instance()->get_automation_schemas();

        // Filter by category if specified
        $category = $request->get_param('category');
        if ($category) {
            $automations = array_filter($automations, function($schema) use ($category) {
                return ($schema['category'] ?? '') === $category;
            });
        }

        return rest_ensure_response($automations);
    }

    /**
     * GET /capabilities - MCP capability manifest
     */
    public function get_capabilities($request) {
        $elements = SUPER_Schema_Loader::instance()->get_element_schemas();
        $element_types = array_keys($elements);

        return rest_ensure_response([
            'version' => '1.0.0',
            'elementTypes' => $element_types,
            'operations' => $this->get_operation_definitions(),
        ]);
    }

    /**
     * Get operation definitions for MCP
     */
    private function get_operation_definitions() {
        return [
            // Element operations
            [
                'name' => 'add_element',
                'description' => 'Add a form element',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'elementType' => ['type' => 'string', 'required' => true],
                    'position' => ['type' => 'number'],
                    'parentId' => ['type' => 'string'],
                    'properties' => ['type' => 'object'],
                ],
            ],
            [
                'name' => 'update_element',
                'description' => 'Update element property',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'elementId' => ['type' => 'string', 'required' => true],
                    'property' => ['type' => 'string', 'required' => true],
                    'value' => ['required' => true],
                ],
            ],
            [
                'name' => 'remove_element',
                'description' => 'Remove an element',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'elementId' => ['type' => 'string', 'required' => true],
                ],
            ],

            // Layout operations
            [
                'name' => 'create_column_layout',
                'description' => 'Create column layout from elements',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'elementIds' => ['type' => 'array', 'required' => true],
                    'layout' => ['required' => true],
                ],
            ],
            [
                'name' => 'move_element',
                'description' => 'Move/reorder element',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'elementId' => ['type' => 'string', 'required' => true],
                    'targetParentId' => ['type' => 'string'],
                    'targetIndex' => ['type' => 'number', 'required' => true],
                ],
            ],

            // Clipboard operations
            [
                'name' => 'copy_element_settings',
                'description' => 'Copy settings from element',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'elementId' => ['type' => 'string', 'required' => true],
                    'mode' => ['type' => 'string', 'required' => true, 'enum' => ['all', 'category', 'properties']],
                    'category' => ['type' => 'string'],
                    'properties' => ['type' => 'array'],
                ],
            ],
            [
                'name' => 'paste_element_settings',
                'description' => 'Paste settings to element',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'targetElementId' => ['type' => 'string', 'required' => true],
                    'mode' => ['type' => 'string', 'required' => true, 'enum' => ['all', 'selected']],
                    'properties' => ['type' => 'array'],
                ],
            ],

            // Settings operations
            [
                'name' => 'update_form_settings',
                'description' => 'Update form settings',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'settings' => ['type' => 'object', 'required' => true],
                ],
            ],
            [
                'name' => 'update_theme_settings',
                'description' => 'Update theme/styling',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'theme' => ['type' => 'object', 'required' => true],
                ],
            ],

            // Version operations
            [
                'name' => 'save_version',
                'description' => 'Save form version',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'message' => ['type' => 'string'],
                ],
            ],
            [
                'name' => 'revert_version',
                'description' => 'Revert to previous version',
                'params' => [
                    'formId' => ['type' => 'number', 'required' => true],
                    'versionId' => ['type' => 'number', 'required' => true],
                ],
            ],
        ];
    }
}
```

### 4. Schema Loader (`/src/includes/class-schema-loader.php`)

```php
<?php
/**
 * Schema Loader
 *
 * Loads and caches generated schema files.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Schema_Loader {

    private static $instance = null;
    private $schemas = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load full schema from generated file
     */
    public function get_schemas() {
        if ($this->schemas !== null) {
            return $this->schemas;
        }

        // Try object cache first
        $cached = wp_cache_get('super_forms_schema', 'super_forms');
        if ($cached !== false) {
            $this->schemas = $cached;
            return $this->schemas;
        }

        // Load from generated file
        $schema_file = SUPER_PLUGIN_DIR . '/includes/schemas/generated-schema.php';

        if (!file_exists($schema_file)) {
            // Return minimal fallback schema
            $this->schemas = $this->get_fallback_schema();
            return $this->schemas;
        }

        $this->schemas = require $schema_file;

        // Cache for 1 hour (schemas don't change at runtime)
        wp_cache_set('super_forms_schema', $this->schemas, 'super_forms', 3600);

        return $this->schemas;
    }

    public function get_version() {
        return $this->get_schemas()['version'] ?? '1.0.0';
    }

    public function get_generated_at() {
        return $this->get_schemas()['generatedAt'] ?? null;
    }

    public function get_element_schemas() {
        return $this->get_schemas()['elements'] ?? [];
    }

    public function get_element_schema($type) {
        $elements = $this->get_element_schemas();
        return $elements[$type] ?? null;
    }

    public function get_settings_schema() {
        return $this->get_schemas()['settings'] ?? [];
    }

    public function get_theme_schema() {
        return $this->get_schemas()['theme'] ?? [];
    }

    public function get_automation_schemas() {
        return $this->get_schemas()['automations'] ?? [];
    }

    /**
     * Fallback schema if generated file is missing
     */
    private function get_fallback_schema() {
        return [
            'version' => '0.0.0',
            'generatedAt' => null,
            'elements' => [],
            'settings' => [],
            'theme' => [],
            'automations' => [],
        ];
    }

    /**
     * Clear schema cache
     */
    public static function clear_cache() {
        wp_cache_delete('super_forms_schema', 'super_forms');
    }
}

// Clear cache on plugin update
add_action('upgrader_process_complete', function($upgrader, $options) {
    if (isset($options['plugins']) && in_array('super-forms/super-forms.php', $options['plugins'])) {
        SUPER_Schema_Loader::clear_cache();
    }
}, 10, 2);
```

## Package.json Scripts

```json
{
  "scripts": {
    "build:schemas": "ts-node scripts/generate-schemas.ts",
    "prebuild": "npm run build:schemas",
    "watch:schemas": "nodemon --watch src/schemas --ext ts --exec 'npm run build:schemas'"
  }
}
```

## File Structure

```
/src/
├── schemas/                          # TypeScript Zod schemas (source of truth)
│   ├── foundation/
│   ├── elements/
│   ├── settings/
│   ├── automations/
│   └── index.ts
│
├── includes/
│   ├── schemas/                      # Generated files (DO NOT EDIT)
│   │   ├── generated-schema.php      # PHP arrays
│   │   └── json-schema.json          # JSON Schema for validation
│   ├── class-schema-loader.php       # Loads generated schemas
│   ├── class-schema-validator.php    # Runtime validation
│   └── class-schema-rest-controller.php
│
└── scripts/
    └── generate-schemas.ts           # Build script

/.mcp/
└── generated-tools.json              # MCP tool definitions (generated)
```

## Acceptance Criteria

- [ ] Build script generates all artifacts without errors
- [ ] `generated-schema.php` loads without PHP errors
- [ ] `json-schema.json` is valid JSON Schema
- [ ] All REST endpoints return valid JSON
- [ ] Endpoints are public (no auth required)
- [ ] Category filtering works
- [ ] Individual element lookup works
- [ ] Capabilities endpoint lists all operations
- [ ] Schema validation rejects invalid element data
- [ ] Cache invalidation works on plugin update

## Context Manifest

### Key Files
- **Spec**: `docs/architecture/form-builder-schema-spec.md` (Section 12)
- **TypeScript Schemas**: `/src/schemas/`
- **Output**: `/src/includes/schemas/`, `/src/includes/class-schema-*.php`

### Reference
- Existing REST controllers: `src/includes/class-form-rest-controller.php`
- zod-to-json-schema: https://github.com/StefanTerdell/zod-to-json-schema

## Work Log
- [2025-12-03] Task created
- [2025-12-03] Updated with complete build pipeline and PHP validation
