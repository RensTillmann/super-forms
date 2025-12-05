# Subtask 04: Automation Tab

## Goal

Replace the placeholder "Logic" tab with the full Automation system, integrating the existing VisualBuilder component that supports nodes (triggers, actions, conditions, control).

## Changes

### 1. tabs/index.ts
- Rename `LogicTab` export to `AutomationTab`
- Change `id: 'logic'` to `id: 'automation'`
- Change `label: 'Logic'` to `label: 'Automation'`
- Change icon from 'Zap' to 'Workflow'
- Update description

### 2. FormBuilderV2.tsx
- Import `AutomationsTab` from `../../components/form-builder/automations/AutomationsTab`
- Remove `LogicTabContent` placeholder component
- Update `activeTab === 'logic'` references to `activeTab === 'automation'`
- Update tab header title from "Conditional Logic" to "Automation"
- Replace `<LogicTabContent />` with `<AutomationsTab formId={...} />`

## Existing Component
Located at: `components/form-builder/automations/AutomationsTab.tsx`
- Wraps `VisualBuilder` component
- Supports `formId` and optional `automationId` props
- Full visual workflow builder with node palette, canvas, properties panel

## Testing
- [ ] Automation tab appears in tab bar (replaces Logic)
- [ ] Tab shows "Automation" label with Workflow icon
- [ ] Panel renders VisualBuilder
- [ ] Build passes
