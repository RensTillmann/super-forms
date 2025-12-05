# Subtask 03: Emails Tab

## Goal

Enable the Emails tab in the Form Builder V2 and add a placeholder component that will eventually integrate the email builder.

## Implementation Plan

1. **Unhide emails tab in schema** - Set `hidden: false` in tabs/index.ts
2. **Add EmailsTabContent component** - Create placeholder component
3. **Update tab panel header** - Add "Emails" title for the emails tab
4. **Add tab content rendering** - Render EmailsTabContent when emails tab is active

## Changes

### 1. tabs/index.ts
- Change `hidden: true` to `hidden: false` on EmailsTab

### 2. FormBuilderV2.tsx
- Add "Emails" to the tab header conditional rendering
- Add `{activeTab === 'emails' && <EmailsTabContent />}` to content area

### 3. New: EmailsTabContent component
Simple placeholder component showing:
- Email notifications list (placeholder)
- Add email button
- Future: Will integrate the email builder component

## Testing
- [ ] Emails tab appears in tab bar
- [ ] Clicking emails tab shows panel
- [ ] Panel header shows "Emails"
- [ ] Build passes
