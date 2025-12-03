---
name: h-research-form-builder-v2-architecture
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-03
---

# Form Builder V2 Architecture Design

## Problem/Goal
Design the architecture for the Form Builder V2 page (`super_form_v2`) to handle data loading, state management, and AI integration efficiently. The current V2 UI exists but lacks proper data integration - it uses hardcoded state and no REST API calls.

Key architectural decisions needed:
1. **Lazy Data Loading** - Load only what's needed, when needed (settings, elements, translations, automations)
2. **State Management** - Zustand stores + REST API integration pattern
3. **Undo/Redo System** - Integration with existing operations-based versioning
4. **AI Integration** - MCP server / schema for AI-assisted form building
5. **Automations Migration** - Move visual workflow builder to V2 UI

## Success Criteria
- [ ] Architecture document with data flow diagrams
- [ ] Lazy loading strategy defined (what data loads when, and how)
- [ ] State management pattern documented (Zustand stores + wp.apiFetch API layer)
- [ ] Undo/Redo integration plan based on Phase 27 JSON Patch system
- [ ] AI integration plan based on Phase 28 schema system
- [ ] Automations UI migration path to V2
- [ ] REST API endpoints audit (existing vs needed for V2)
- [ ] Review and validate existing schemas still match current architecture

## Existing Documentation References
**IMPORTANT**: These tasks define existing schemas that must be incorporated:

1. **Phase 27: Operations-Based Form Editing & Versioning**
   - File: `sessions/tasks/h-implement-triggers-actions-extensibility/27-implement-operations-versioning-system.md`
   - Covers: JSON Patch (RFC 6902), undo/redo, version control, MCP server tools
   - Key: Two-layer architecture (session ops + saved versions)

2. **Phase 28: AI Assistant Token System**
   - File: `sessions/tasks/h-implement-triggers-actions-extensibility/28-ai-assistant-token-system.md`
   - Covers: Five schema domains (elements, nodes, settings, theme, translations)
   - Key: Schema system for AI-assisted form building

3. **MCP Server**
   - File: `.mcp/README.md`
   - Covers: Tool definitions, WordPress connection, usage examples

## Context Manifest
<!-- Added by context-gathering agent -->

## User Notes
- Stay on current branch: `feature/h-implement-triggers-actions-extensibility`
- Focus on `super_form_v2` page UI specifically
- Build on Phase 27 (JSON Patch operations) and Phase 28 (AI schemas)
- Use `wp.apiFetch()` pattern for REST API calls
- Automations (visual workflow builder) needs to be integrated into V2
- FormBuilderV2 component currently has no data integration (hardcoded state)

## Work Log
- [2025-12-03] Task created for V2 architecture planning
