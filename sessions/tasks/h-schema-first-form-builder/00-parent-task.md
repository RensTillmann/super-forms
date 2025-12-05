---
name: h-schema-first-form-builder
branch: feature/schema-first-form-builder
status: pending
created: 2025-12-03
---

# Schema-First Form Builder Implementation

## Overview

Implement the Schema-First Architecture for Form Builder V2 as designed in `docs/architecture/form-builder-schema-spec.md`.

**Goal**: Single source of truth schema that drives React UI, PHP validation, and MCP tools with full capability parity.

## Key Requirements

1. **Single Source of Truth** - Zod schemas generate everything (TypeScript types, JSON Schema, PHP validation)
2. **MCP Parity** - AI can do everything a user can do
3. **Tailwind CSS** - Form rendering via Tailwind classes (not inline styles)
4. **Settings Clipboard** - Copy/paste settings between elements
5. **Layout Operations** - Create columns, wrap/unwrap containers
6. **Schema Enforcement** - Multi-layer validation at compile time, runtime boundaries, and CI/CD

## Architecture Diagram

```
/src/schemas/                         # SINGLE SOURCE OF TRUTH (Zod)
├── core/
│   ├── types.ts                      # PropertyTypeSchema, BasePropertiesSchema
│   └── registry.ts                   # registerElement(), validateElementData()
├── elements/                         # Element Zod schemas (registerElement())
├── settings/                         # Form/theme settings schemas
├── automations/                      # Node schemas
└── operations/                       # Clipboard, layout ops
        │
        ▼ npm run generate:schemas
┌───────────────────────────────────────────────────────────────────┐
│                    BUILD PIPELINE                                  │
│  Zod Schemas ──► JSON Schema ──► PHP Arrays ──► MCP Tools         │
│                 (validation)     (runtime)      (AI parity)       │
└───────────────────────────────────────────────────────────────────┘
        │                  │                  │
        ▼                  ▼                  ▼
   React Admin        PHP Backend        MCP Server
   (property panels)  (SUPER_Schema_*)   (dynamic tools)
```

## Enforcement Strategy

Multi-layer validation ensures schema compliance across all consumers:

| Layer | When | How | Failure Mode |
|-------|------|-----|--------------|
| **Definition** | `registerElement()` call | `Zod.parse()` validates schema structure | Crash at import time |
| **Import** | Application startup | Side-effect imports register all elements | Crash before render |
| **API Boundary** | REST calls | `validateElementData()` checks payload | 400 Bad Request |
| **Build** | `npm run generate:schemas` | Export JSON Schema, PHP, MCP tools | Build failure |
| **CI/CD** | Pull request | Compare generated files vs source | PR blocked |
| **PHP Runtime** | Form save/load | `SUPER_Schema_Validator::validate()` | WP_Error |

**Key Principle**: Invalid schemas crash at startup (fail fast), not silently at runtime.

## Subtasks

| # | Task | Status | Description |
|---|------|--------|-------------|
| 01 | Schema Foundation | **complete** | Core Zod types, property types, registry, base schemas |
| 02 | Element Schemas | in_progress | All 30+ element type schemas using registerElement() |
| 03 | Settings & Theme Schemas | pending | Form settings, theme with Tailwind |
| 04 | Automation Node Schemas | pending | Triggers, actions, conditions |
| 05 | Operations Schemas | pending | Clipboard, layout operations |
| 06 | REST API Schema Endpoints | pending | PHP controller, SUPER_Schema_Validator |
| 07 | MCP Dynamic Tools | pending | Update MCP server to use schema |
| 08 | React Property Panels | **complete** | Schema-driven UI generation |
| 09 | Tailwind Integration | pending | CSS variable system, class generation |
| 10 | Clipboard UI | pending | Copy/paste context menu and service |
| 11 | Schema CI Enforcement | pending | Build scripts, drift detection, CI/CD checks |

## Dependencies

- Spec document: `docs/architecture/form-builder-schema-spec.md`
- Research task: `sessions/tasks/h-research-form-builder-v2-architecture.md`
- Phase 27 (operations): `sessions/tasks/h-implement-triggers-actions-extensibility/27-implement-operations-versioning-system.md`
- Phase 28 (AI schemas): `sessions/tasks/h-implement-triggers-actions-extensibility/28-ai-assistant-token-system.md`

## Success Criteria

- [ ] All 30+ element types have complete Zod schemas with registerElement()
- [ ] Schema REST endpoints return valid data via SUPER_Schema_REST_Controller
- [ ] MCP server generates tools dynamically from schema
- [ ] Property panels render from schema definitions
- [ ] Clipboard copy/paste works across element types
- [ ] Layout operations create/modify column layouts
- [ ] Tailwind classes used for all form styling
- [ ] Build pipeline generates JSON Schema, PHP arrays, MCP tools
- [ ] CI/CD blocks PRs with schema drift

## Work Log
- [2025-12-03] Parent task created from research findings
- [2025-12-03] Added Zod enforcement strategy - multi-layer validation approach
- [2025-12-04] **Phase 1 Complete** - Schema Foundation implemented:
  - Created `/src/react/admin/schemas/core/` with Zod types and registry
  - Created `/src/react/admin/schemas/elements/text.ts` as proof-of-concept
  - Built SchemaPropertyPanel component with PropertyRenderer
  - Wired into PropertyPanelRegistry (schema panels for registered elements, fallback for others)
  - Fixed memory exhaustion: added `fields` param to SUPER_Form_DAL::query(), default limit 20
  - Fixed stale PHP includes causing warnings
