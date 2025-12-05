---
parent: h-schema-first-form-builder
status: pending
priority: high
created: 2025-12-03
---

# Task 11: Schema CI/CD Enforcement

## Objective

Implement CI/CD checks that prevent schema drift by verifying generated files match source Zod schemas. Block PRs that modify schemas without regenerating outputs.

## Build Pipeline Scripts

### Main Generation Script

**File**: `/scripts/generate-schemas.ts`

```typescript
#!/usr/bin/env npx ts-node

import { writeFileSync, mkdirSync, existsSync, readFileSync } from 'fs';
import { join } from 'path';
import { zodToJsonSchema } from 'zod-to-json-schema';
import { createHash } from 'crypto';
import {
  ElementSchemaSchema,
  PropertySchemaSchema,
  PropertyTypeSchema,
} from '../src/schemas/core/types';
import { getAllElements, getElementSchema } from '../src/schemas/core/registry';

// Import all element schemas (side effects register them)
import '../src/schemas/elements';

const OUTPUT_DIR = join(__dirname, '../src/generated');
const PHP_OUTPUT_DIR = join(__dirname, '../src/includes/schema');

interface GenerationManifest {
  generatedAt: string;
  sourceHash: string;
  files: Record<string, string>;
}

// Generate source hash from all schema files
function generateSourceHash(): string {
  const schemaDir = join(__dirname, '../src/schemas');
  const hash = createHash('sha256');

  const files = [
    'core/types.ts',
    'core/registry.ts',
    'elements/index.ts',
    // Add all element files
  ];

  for (const file of files) {
    const path = join(schemaDir, file);
    if (existsSync(path)) {
      hash.update(readFileSync(path, 'utf-8'));
    }
  }

  return hash.digest('hex').substring(0, 12);
}

// Convert JS object to PHP array syntax
function toPhpArray(obj: unknown, indent = 0): string {
  const pad = '    '.repeat(indent);
  const pad1 = '    '.repeat(indent + 1);

  if (obj === null || obj === undefined) {
    return 'null';
  }
  if (typeof obj === 'boolean') {
    return obj ? 'true' : 'false';
  }
  if (typeof obj === 'number') {
    return String(obj);
  }
  if (typeof obj === 'string') {
    return `'${obj.replace(/'/g, "\\'")}'`;
  }
  if (Array.isArray(obj)) {
    if (obj.length === 0) return '[]';
    const items = obj.map(item => `${pad1}${toPhpArray(item, indent + 1)}`);
    return `[\n${items.join(",\n")},\n${pad}]`;
  }
  if (typeof obj === 'object') {
    const entries = Object.entries(obj);
    if (entries.length === 0) return '[]';
    const items = entries.map(
      ([key, val]) => `${pad1}'${key}' => ${toPhpArray(val, indent + 1)}`
    );
    return `[\n${items.join(",\n")},\n${pad}]`;
  }
  return 'null';
}

async function generate(): Promise<void> {
  console.log('🔧 Generating schema outputs...\n');

  // Ensure output directories exist
  if (!existsSync(OUTPUT_DIR)) mkdirSync(OUTPUT_DIR, { recursive: true });
  if (!existsSync(PHP_OUTPUT_DIR)) mkdirSync(PHP_OUTPUT_DIR, { recursive: true });

  const manifest: GenerationManifest = {
    generatedAt: new Date().toISOString(),
    sourceHash: generateSourceHash(),
    files: {},
  };

  // 1. Generate JSON Schema for element validation
  console.log('📄 Generating JSON Schema...');
  const elementJsonSchema = zodToJsonSchema(ElementSchemaSchema, {
    name: 'ElementSchema',
    definitions: {
      PropertySchema: PropertySchemaSchema,
      PropertyType: PropertyTypeSchema,
    },
  });

  const jsonSchemaPath = join(OUTPUT_DIR, 'element-schema.json');
  writeFileSync(jsonSchemaPath, JSON.stringify(elementJsonSchema, null, 2));
  manifest.files['element-schema.json'] = hashFile(jsonSchemaPath);

  // 2. Generate element registry (all registered elements)
  console.log('📦 Generating element registry...');
  const elements = getAllElements();
  const registry: Record<string, unknown> = {};

  for (const type of elements) {
    const schema = getElementSchema(type);
    if (schema) {
      registry[type] = schema;
    }
  }

  const registryPath = join(OUTPUT_DIR, 'element-registry.json');
  writeFileSync(registryPath, JSON.stringify(registry, null, 2));
  manifest.files['element-registry.json'] = hashFile(registryPath);

  // 3. Generate PHP arrays
  console.log('🐘 Generating PHP arrays...');
  const phpContent = `<?php
/**
 * Auto-generated from Zod schemas. DO NOT EDIT MANUALLY.
 * Generated: ${manifest.generatedAt}
 * Source hash: ${manifest.sourceHash}
 *
 * Regenerate with: npm run generate:schemas
 */

if (!defined('ABSPATH')) exit;

return ${toPhpArray(registry)};
`;

  const phpPath = join(PHP_OUTPUT_DIR, 'element-registry.php');
  writeFileSync(phpPath, phpContent);
  manifest.files['element-registry.php'] = hashFile(phpPath);

  // 4. Generate property types enum for PHP
  const propertyTypes = PropertyTypeSchema.options;
  const phpTypesContent = `<?php
/**
 * Auto-generated property types enum. DO NOT EDIT MANUALLY.
 * Generated: ${manifest.generatedAt}
 */

if (!defined('ABSPATH')) exit;

return ${toPhpArray(propertyTypes)};
`;

  const phpTypesPath = join(PHP_OUTPUT_DIR, 'property-types.php');
  writeFileSync(phpTypesPath, phpTypesContent);
  manifest.files['property-types.php'] = hashFile(phpTypesPath);

  // 5. Generate MCP tool definitions
  console.log('🤖 Generating MCP tool definitions...');
  const mcpTools = generateMcpTools(registry);
  const mcpPath = join(OUTPUT_DIR, 'mcp-tools.json');
  writeFileSync(mcpPath, JSON.stringify(mcpTools, null, 2));
  manifest.files['mcp-tools.json'] = hashFile(mcpPath);

  // 6. Write manifest
  const manifestPath = join(OUTPUT_DIR, 'manifest.json');
  writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));

  console.log('\n✅ Generation complete!');
  console.log(`   Source hash: ${manifest.sourceHash}`);
  console.log(`   Files generated: ${Object.keys(manifest.files).length}`);
}

function hashFile(path: string): string {
  return createHash('sha256')
    .update(readFileSync(path, 'utf-8'))
    .digest('hex')
    .substring(0, 12);
}

function generateMcpTools(registry: Record<string, unknown>): object {
  const tools = [];

  // Tool: list element types
  tools.push({
    name: 'list_element_types',
    description: 'List all available form element types',
    inputSchema: { type: 'object', properties: {} },
  });

  // Tool: get element schema
  tools.push({
    name: 'get_element_schema',
    description: 'Get the complete schema for an element type',
    inputSchema: {
      type: 'object',
      properties: {
        type: {
          type: 'string',
          enum: Object.keys(registry),
          description: 'The element type',
        },
      },
      required: ['type'],
    },
  });

  // Dynamic tools per element type
  for (const [type, schema] of Object.entries(registry)) {
    tools.push({
      name: `create_${type}_element`,
      description: `Create a new ${type} form element`,
      inputSchema: buildElementInputSchema(schema as any),
    });
  }

  return {
    elementTypes: Object.keys(registry),
    tools,
  };
}

function buildElementInputSchema(schema: any): object {
  // Build JSON Schema from element properties
  const properties: Record<string, any> = {};
  const required: string[] = [];

  if (schema.properties) {
    for (const [category, categoryProps] of Object.entries(schema.properties)) {
      for (const [propName, propSchema] of Object.entries(categoryProps as any)) {
        properties[propName] = convertPropertyToJsonSchema(propSchema as any);
        if ((propSchema as any).required) {
          required.push(propName);
        }
      }
    }
  }

  return {
    type: 'object',
    properties,
    required: required.length > 0 ? required : undefined,
  };
}

function convertPropertyToJsonSchema(prop: any): object {
  const typeMap: Record<string, any> = {
    string: { type: 'string' },
    number: { type: 'number' },
    boolean: { type: 'boolean' },
    select: { type: 'string', enum: prop.options?.map((o: any) => o.value) },
    multi_select: { type: 'array', items: { type: 'string' } },
    color: { type: 'string', pattern: '^#[0-9A-Fa-f]{6}$' },
    icon: { type: 'string' },
    array: { type: 'array' },
    object: { type: 'object' },
  };

  return typeMap[prop.type] || { type: 'string' };
}

generate().catch(console.error);
```

### Verification Script

**File**: `/scripts/verify-schemas.ts`

```typescript
#!/usr/bin/env npx ts-node

import { readFileSync, existsSync } from 'fs';
import { join } from 'path';
import { createHash } from 'crypto';

const OUTPUT_DIR = join(__dirname, '../src/generated');

interface VerificationResult {
  success: boolean;
  errors: string[];
  warnings: string[];
}

function verify(): VerificationResult {
  const result: VerificationResult = {
    success: true,
    errors: [],
    warnings: [],
  };

  // 1. Check manifest exists
  const manifestPath = join(OUTPUT_DIR, 'manifest.json');
  if (!existsSync(manifestPath)) {
    result.errors.push('manifest.json not found. Run: npm run generate:schemas');
    result.success = false;
    return result;
  }

  const manifest = JSON.parse(readFileSync(manifestPath, 'utf-8'));

  // 2. Check source hash (detect schema changes)
  const currentHash = generateSourceHash();
  if (manifest.sourceHash !== currentHash) {
    result.errors.push(
      `Schema source changed (${manifest.sourceHash} → ${currentHash}). ` +
      'Run: npm run generate:schemas'
    );
    result.success = false;
  }

  // 3. Verify all generated files exist and match hashes
  for (const [file, expectedHash] of Object.entries(manifest.files)) {
    const filePath = join(OUTPUT_DIR, file);

    if (!existsSync(filePath)) {
      result.errors.push(`Missing generated file: ${file}`);
      result.success = false;
      continue;
    }

    const actualHash = hashFile(filePath);
    if (actualHash !== expectedHash) {
      result.errors.push(
        `File hash mismatch: ${file} ` +
        `(expected ${expectedHash}, got ${actualHash})`
      );
      result.success = false;
    }
  }

  // 4. Check for stale files (generated long ago)
  const generatedAt = new Date(manifest.generatedAt);
  const daysSinceGeneration = (Date.now() - generatedAt.getTime()) / (1000 * 60 * 60 * 24);
  if (daysSinceGeneration > 7) {
    result.warnings.push(
      `Schemas generated ${Math.floor(daysSinceGeneration)} days ago. ` +
      'Consider regenerating.'
    );
  }

  return result;
}

function generateSourceHash(): string {
  const schemaDir = join(__dirname, '../src/schemas');
  const hash = createHash('sha256');

  // Same logic as generate script
  const files = [
    'core/types.ts',
    'core/registry.ts',
    'elements/index.ts',
  ];

  for (const file of files) {
    const path = join(schemaDir, file);
    if (existsSync(path)) {
      hash.update(readFileSync(path, 'utf-8'));
    }
  }

  return hash.digest('hex').substring(0, 12);
}

function hashFile(path: string): string {
  return createHash('sha256')
    .update(readFileSync(path, 'utf-8'))
    .digest('hex')
    .substring(0, 12);
}

// Run verification
const result = verify();

if (result.warnings.length > 0) {
  console.log('⚠️  Warnings:');
  result.warnings.forEach(w => console.log(`   ${w}`));
}

if (result.errors.length > 0) {
  console.log('❌ Errors:');
  result.errors.forEach(e => console.log(`   ${e}`));
  process.exit(1);
}

console.log('✅ Schema verification passed');
process.exit(0);
```

## Package.json Scripts

```json
{
  "scripts": {
    "generate:schemas": "ts-node scripts/generate-schemas.ts",
    "verify:schemas": "ts-node scripts/verify-schemas.ts",
    "precommit:schemas": "npm run verify:schemas"
  }
}
```

## GitHub Actions Workflow

**File**: `.github/workflows/schema-check.yml`

```yaml
name: Schema Verification

on:
  pull_request:
    paths:
      - 'src/schemas/**'
      - 'src/generated/**'
      - 'src/includes/schema/**'

jobs:
  verify-schemas:
    name: Verify Schema Consistency
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: src/react/admin/package-lock.json

      - name: Install dependencies
        working-directory: src/react/admin
        run: npm ci

      - name: Verify schemas are up to date
        working-directory: src/react/admin
        run: npm run verify:schemas

      - name: Regenerate and check for diff
        working-directory: src/react/admin
        run: |
          npm run generate:schemas
          if [[ -n $(git status --porcelain src/generated src/includes/schema) ]]; then
            echo "❌ Generated files are out of sync with schemas"
            echo "Run 'npm run generate:schemas' and commit the results"
            git diff src/generated src/includes/schema
            exit 1
          fi
          echo "✅ Generated files are in sync"
```

## Pre-commit Hook

**File**: `.husky/pre-commit`

```bash
#!/bin/sh
. "$(dirname "$0")/_/husky.sh"

# Check if schema files were modified
SCHEMA_FILES=$(git diff --cached --name-only | grep -E '^src/schemas/')

if [ -n "$SCHEMA_FILES" ]; then
  echo "📋 Schema files modified, verifying..."
  cd src/react/admin
  npm run verify:schemas

  if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Schema verification failed!"
    echo "Run: npm run generate:schemas"
    echo "Then stage the generated files."
    exit 1
  fi
fi
```

## VSCode Integration

**File**: `.vscode/tasks.json`

```json
{
  "version": "2.0.0",
  "tasks": [
    {
      "label": "Generate Schemas",
      "type": "shell",
      "command": "npm run generate:schemas",
      "options": {
        "cwd": "${workspaceFolder}/src/react/admin"
      },
      "group": "build",
      "presentation": {
        "echo": true,
        "reveal": "always",
        "panel": "shared"
      },
      "problemMatcher": []
    },
    {
      "label": "Verify Schemas",
      "type": "shell",
      "command": "npm run verify:schemas",
      "options": {
        "cwd": "${workspaceFolder}/src/react/admin"
      },
      "group": "test",
      "presentation": {
        "echo": true,
        "reveal": "always",
        "panel": "shared"
      },
      "problemMatcher": []
    }
  ]
}
```

## Implementation Checklist

- [ ] Create `/scripts/generate-schemas.ts`
- [ ] Create `/scripts/verify-schemas.ts`
- [ ] Add npm scripts to package.json
- [ ] Create GitHub Actions workflow
- [ ] Set up Husky pre-commit hook
- [ ] Add VSCode tasks for schema operations
- [ ] Document workflow in developer guide

## Testing Strategy

1. **Unit tests for generation**:
   - Verify JSON Schema output is valid
   - Verify PHP array syntax is correct
   - Verify MCP tools match registered elements

2. **Integration tests**:
   - Modify a schema, verify CI fails
   - Regenerate, verify CI passes
   - Add new element type, verify all outputs update

## Success Criteria

- [ ] `npm run generate:schemas` produces all output files
- [ ] `npm run verify:schemas` detects schema drift
- [ ] GitHub Actions blocks PRs with stale generated files
- [ ] Pre-commit hook warns developers before commit
- [ ] Generated files include source hash for traceability

## Work Log
- [2025-12-03] Task created from enforcement strategy discussion
