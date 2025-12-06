---
name: 05-styles-tab-integration
parent: m-implement-styles-themes-ui
status: pending
created: 2025-12-06
---

# Subtask 5: Styles Tab Integration

## Goal

Replace the placeholder StyleTabContent in FormBuilderV2 with the fully functional GlobalStylesPanel component. Ensure style changes persist and sync with form data.

## Success Criteria

- [ ] GlobalStylesPanel renders inside Styles tab
- [ ] Style changes update form preview in real-time
- [ ] Export/Import functionality works
- [ ] Preset selection applies correctly
- [ ] Changes persist via REST API when form is saved
- [ ] TypeScript compiles cleanly

## Current State

The Styles tab already exists (registered at position 50) but contains a placeholder implementation in `FormBuilderV2.tsx` (lines 933-1100). This placeholder has:
- Sub-tabs for Theme, Colors, Fonts, Layout, Custom CSS
- Hardcoded theme options (default, minimal, modern, classic)
- Non-functional color pickers

The actual GlobalStylesPanel component at `/src/react/admin/components/settings/GlobalStylesPanel.tsx` is fully functional and already:
- Connects to styleRegistry
- Supports node type selection (label, input, button, etc.)
- Has SpacingControl and ColorControl components
- Implements export/import JSON
- Has preset application (Light/Dark)

## Technical Specification

### Replace StyleTabContent

In `/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`:

**Before (placeholder):**
```tsx
const StyleTabContent: React.FC = () => {
  const [activeStyleTab, setActiveStyleTab] = useState('theme');
  // ... 170+ lines of placeholder UI
};
```

**After (using GlobalStylesPanel):**
```tsx
import { GlobalStylesPanel } from '../../components/settings/GlobalStylesPanel';

const StyleTabContent: React.FC = () => {
  return (
    <div className="flex-1 overflow-auto">
      <GlobalStylesPanel />
    </div>
  );
};
```

### Add Persistence Layer

Currently GlobalStylesPanel only updates the in-memory styleRegistry. To persist changes:

1. **On form load:** Initialize styleRegistry from form settings
2. **On style change:** Mark form as dirty
3. **On form save:** Include styles in save payload

Add to GlobalStylesPanel or create wrapper:

```tsx
// In FormBuilderV2 or a context provider

import { useEffect } from 'react';
import { styleRegistry } from '../schemas/styles';
import { useFormStore } from './store/useFormStore';

function useStylePersistence() {
  const formSettings = useFormStore(s => s.formSettings);
  const setFormDirty = useFormStore(s => s.setDirty);

  // Initialize styles from form on load
  useEffect(() => {
    if (formSettings?.globalStyles) {
      styleRegistry.importStyles(JSON.stringify(formSettings.globalStyles));
    }
  }, [formSettings?.globalStyles]);

  // Subscribe to style changes and mark form dirty
  useEffect(() => {
    return styleRegistry.subscribe(() => {
      setFormDirty(true);
    });
  }, [setFormDirty]);
}
```

Update form save logic to include styles:

```tsx
// In save handler
const saveForm = async () => {
  const currentStyles = JSON.parse(styleRegistry.exportStyles());

  const formData = {
    // ... existing form data
    settings: {
      ...formSettings,
      globalStyles: currentStyles,
    },
  };

  await wp.apiFetch({
    path: `/super-forms/v1/forms/${formId}`,
    method: 'PUT',
    data: formData,
  });
};
```

### Optional: Style Sub-tabs

If you want to keep the sub-tab organization from the placeholder but with real functionality:

```tsx
const StyleTabContent: React.FC = () => {
  const [activeSubTab, setActiveSubTab] = useState<'global' | 'presets' | 'custom'>('global');

  return (
    <div className="flex-1 flex flex-col overflow-auto">
      {/* Sub-tab navigation */}
      <div className="flex border-b border-border px-4">
        <button
          className={cn(
            "px-4 py-2 text-sm font-medium border-b-2 -mb-px",
            activeSubTab === 'global'
              ? "border-primary text-primary"
              : "border-transparent text-muted-foreground hover:text-foreground"
          )}
          onClick={() => setActiveSubTab('global')}
        >
          Global Styles
        </button>
        <button
          className={cn(
            "px-4 py-2 text-sm font-medium border-b-2 -mb-px",
            activeSubTab === 'presets'
              ? "border-primary text-primary"
              : "border-transparent text-muted-foreground hover:text-foreground"
          )}
          onClick={() => setActiveSubTab('presets')}
        >
          Quick Presets
        </button>
        <button
          className={cn(
            "px-4 py-2 text-sm font-medium border-b-2 -mb-px",
            activeSubTab === 'custom'
              ? "border-primary text-primary"
              : "border-transparent text-muted-foreground hover:text-foreground"
          )}
          onClick={() => setActiveSubTab('custom')}
        >
          Custom CSS
        </button>
      </div>

      {/* Sub-tab content */}
      <div className="flex-1 overflow-auto">
        {activeSubTab === 'global' && <GlobalStylesPanel />}
        {activeSubTab === 'presets' && <QuickPresetsPanel />}
        {activeSubTab === 'custom' && <CustomCSSPanel />}
      </div>
    </div>
  );
};
```

### Quick Presets Panel (Optional)

```tsx
const QuickPresetsPanel: React.FC = () => {
  const handleApplyPreset = (presetId: string) => {
    applyPreset(presetId);
  };

  return (
    <div className="p-4 space-y-4">
      <h4 className="font-medium">Quick Apply</h4>
      <div className="grid grid-cols-2 gap-4">
        {THEME_PRESETS.map(preset => (
          <button
            key={preset.id}
            className="p-4 border rounded-lg text-left hover:border-primary"
            onClick={() => handleApplyPreset(preset.id)}
          >
            <div className="font-medium">{preset.name}</div>
            <div className="text-sm text-muted-foreground">{preset.description}</div>
          </button>
        ))}
      </div>
    </div>
  );
};
```

## Files to Modify

1. `/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`
   - Replace StyleTabContent with GlobalStylesPanel
   - Add style persistence logic
   - Include styles in form save payload

2. `/src/react/admin/components/settings/GlobalStylesPanel.tsx`
   - Minor adjustments if needed for integration

## Files to Create (Optional)

1. `/src/react/admin/apps/form-builder-v2/hooks/useStylePersistence.ts` - Style sync hook

## Implementation Notes

- GlobalStylesPanel is already complete and functional
- Main work is replacing placeholder and adding persistence
- styleRegistry changes trigger form dirty state
- Form save includes globalStyles in settings
- On form load, initialize styleRegistry from saved styles

## Dependencies

- Existing GlobalStylesPanel component
- Existing styleRegistry
- Form save/load infrastructure
