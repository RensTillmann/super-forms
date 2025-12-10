---
name: h-implement-floating-panel-ai-templates-tabs
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-10
---

# Floating Panel: AI & Templates Tabs

## Problem/Goal
Add two new tabs to the element floating panel:
1. **AI Tab** - Chat interface focused on the active element (and children if container)
2. **Templates Tab** - Visual template picker with toggle-on/off UX for input field variations

The Templates system should leverage shadcn InputGroup addon patterns (prefix/suffix text, icons, tooltips, popovers) with a schema-first approach.

## Success Criteria
- [ ] Schema defined for input addons (prefix, suffix, icons, tooltips, action buttons)
- [ ] Templates registry with predefined element configurations
- [ ] Templates Tab UI with preview cards, toggleable options, and Apply button
- [ ] AI Tab with element-context-aware chat interface
- [ ] AI can modify element properties and apply templates
- [ ] Works for container elements (targets children)

## Subtasks
- `01-input-addon-schema.md` - Schema-first design for InputGroup addons
- `02-templates-registry.md` - Template definitions with toggle states
- `03-templates-tab-ui.md` - Templates tab UI with preview and apply logic
- `04-ai-tab-foundation.md` - AI tab with element-focused context
- `05-ai-element-operations.md` - AI actions for modifying elements

## Context Manifest
<!-- Added by context-gathering agent -->

## User Notes
- Use existing InputGroup components from shadcn (see ui.shadcn.com/docs/components/input-group)
- InputGroupAddon patterns: inline-end text, icons, tooltips, popovers, prefix text
- UX: Toggle templates on/off, then Apply to combine selections
- Schema-first approach like existing element schemas
- Stay on current branch (feature/h-implement-triggers-actions-extensibility)

## Work Log
- [2025-12-10] Task created
