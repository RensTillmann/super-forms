---
name: 00-research-button-epics
status: pending
created: 2025-12-15
---

# Research: Button Use Case Epics

## Problem/Goal

Research and document comprehensive use cases for action buttons in form builders. This research will inform:

1. **Button element schema design** - What properties/configs are needed
2. **Automation node types** - What new nodes should be created
3. **Event system requirements** - How button events flow to automations
4. **MCP tool design** - How LLMs can configure buttons effectively

## Success Criteria

- [ ] Use Brave search to research button patterns in popular form builders
- [ ] Research automation/workflow trigger patterns from tools like Zapier, Make, n8n
- [ ] Document 10+ distinct epics with full schema implications
- [ ] Create epic files in `epics/` directory
- [ ] Map Zod schema requirements for each epic
- [ ] Define MCP tool parameters needed for LLM integration
- [ ] Identify new automation node types required

## Research Areas

### 1. Form Builder Button Patterns
- Typeform, JotForm, Gravity Forms, WPForms button features
- Multi-step form navigation patterns
- Conditional button visibility
- Button variants and styling options

### 2. Automation Trigger Patterns
- Zapier trigger types and configurations
- Make (Integromat) module patterns
- n8n node trigger bindings
- How workflows bind to specific UI events

### 3. Use Case Categories to Research

| Category | Examples |
|----------|----------|
| Form Submission | Submit, Save Draft, Submit & Continue |
| Navigation | Next Step, Previous Step, Jump to Section |
| File Operations | Generate PDF, Download File, Upload |
| Payment | Pay Now, Add to Cart, Checkout |
| Data Operations | Calculate, Validate, Lookup |
| External | Sync to CRM, Send to API, Webhook |
| UI Actions | Show/Hide, Enable/Disable, Reset |

### 4. Schema Implications per Epic

For each epic document:
- Required button properties
- Event payload structure
- Automation node compatibility
- Frontend state management needs
- Error handling patterns

## Output Format

Each epic file should follow this structure:

```markdown
# Epic: [Name]

## Use Case
[Description of what user wants to achieve]

## User Story
As a [user type], I want to [action] so that [benefit].

## Button Properties Required
- property: type - description

## Event Payload
{
  "event": "button.{id}.clicked",
  "context": { ... }
}

## Compatible Automation Nodes
- node_type: description

## New Node Types Needed
- node_type: what it does

## Frontend Behavior
- Loading state
- Success handling
- Error handling

## Schema Fragment (Zod)
```typescript
z.object({ ... })
```

## MCP Tool Parameters
```typescript
{
  toolName: 'addButton',
  parameters: { ... }
}
```
```

## Context Manifest
<!-- Added by context-gathering agent -->

## Work Log

- [2025-12-15] Subtask created
