# Task 29: Port SaaS Form Builder UI to WordPress (Create Form V2)

## Overview

Port the form builder UI from `~/super-forms.com/api.super-forms.com/frontend/` to the WordPress plugin as a new "Create Form V2" admin page.

**Critical Requirement:** The UI must remain EXACTLY intact - same styles, CSS, layout, and visual appearance.

## UI Layout (Exact)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ TOP BAR (sticky, 64px)                                                        │
│ [FormSelector][Title] [Device▼][Frame] │ [Undo][Redo][Grid][Zoom] [...] [Preview][Save][Publish] │
├──────────────────────────────────────────────────────────────────────────────┤
│ TABS BAR (horizontal)                                                         │
│ [⚙Settings] [📊Entries] [⚡Logic] [🎨Style] [🔌Integrations]                  │
├────────────────────────┬─────────────────────────────────────────────────────┤
│ TAB CONTENT PANEL      │                    CANVAS AREA                       │
│ (collapsible, opens    │                                                      │
│  when tab clicked)     │   Form elements render here                          │
│                        │   (with device-responsive width + zoom)              │
│ • Form Settings        │   Grid overlay (toggleable)                          │
│ • Entries list         │                                                      │
│ • Logic builder        │                                                      │
│ • Style editor         │                                                      │
│ • Integrations         │                                                      │
│        [X close]       │                                                      │
├────────────────────────┴─────────────────────────────────────────────────────┤
│ BOTTOM ELEMENT TRAY (resizable, collapsible, horizontal scroll)               │
│ [Elements ▼] [search...] [Categories: All|Basic|Choice|Advanced|Upload|...]  │
│ 45+ element types in 8 categories                                             │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Source Files (from SaaS)

| Source | Destination | Notes |
|--------|-------------|-------|
| `frontend/src/styles/design-tokens.css` | `styles/design-tokens.css` | Exact copy |
| `frontend/src/styles/form-builder.css` | `styles/form-builder.css` | Exact copy |
| `frontend/src/types/index.ts` | `types/index.ts` | Exact copy |
| `frontend/src/store/slices/elementsSlice.ts` | `store/useElementsStore.ts` | Redux → Zustand |
| `frontend/src/store/slices/builderSlice.ts` | `store/useBuilderStore.ts` | Redux → Zustand |
| `frontend/src/components/FormBuilder/FormBuilderComplete.tsx` | `FormBuilderV2.tsx` | Main component |
| `frontend/src/components/FormBuilder/ui-components/*` | `components/ui/*` | UI sub-components |
| `frontend/src/components/FormBuilder/elements/*` | `components/elements/*` | Element renderers |

## Target Directory Structure

```
src/react/admin/apps/form-builder-v2/
├── FormBuilderV2.tsx           # Main component (from FormBuilderComplete)
├── index.ts                    # Exports
├── components/
│   ├── ui/                     # UI sub-components (FormSelector, Toast, etc.)
│   └── elements/               # Element renderers
├── store/
│   ├── useElementsStore.ts     # Zustand store (from elementsSlice)
│   ├── useBuilderStore.ts      # Zustand store (from builderSlice)
│   └── index.ts
├── types/
│   └── index.ts                # TypeScript types
└── styles/
    ├── design-tokens.css       # CSS custom properties
    └── form-builder.css        # Component styles
```

## Element Categories (45+ elements)

- **Basic (8):** text, email, textarea, number, phone, url, password, hidden
- **Choice (9):** select, multiselect, checkbox, radio, checkbox-cards, radio-cards, toggle, rating, likert
- **Advanced (8):** date, time, datetime, daterange, slider, color, location, signature
- **Upload (4):** file, image, multi-file, drag-drop
- **Containers (8):** columns, step-wizard, tabs, accordion, section, repeater, conditional-group, card
- **Layout (6):** heading, paragraph, divider, spacer, page-break, html-block
- **Integration (6):** payment, subscription, webhook, calculation, conditional, captcha

## PHP Integration

Add new admin submenu page:
- Parent: `super_forms`
- Menu title: `Create Form V2`
- Page slug: `super_form_v2`
- Renders React mount point

## Implementation Rules

1. **DO NOT modify any visual styles** - copy CSS exactly
2. **DO NOT change layout structure** - same TopBar, TabsBar, Canvas, Bottom Tray
3. **DO NOT remove any element types** - all 45+ elements must be present
4. **ONLY change what's necessary:**
   - Redux → Zustand conversion (same logic, different API)
   - Import paths (adjust for new location)
   - Remove unused SaaS-specific dependencies

## Acceptance Criteria

- [ ] New menu item "Create Form V2" appears under Super Forms
- [ ] Page loads the React form builder
- [ ] UI looks identical to SaaS version
- [ ] Top bar with FormSelector, title, device selector, zoom, undo/redo works
- [ ] Horizontal tabs bar (Settings, Entries, Logic, Style, Integrations) works
- [ ] Tab content panel appears on left when tab clicked, closes with X
- [ ] Canvas displays form elements with device preview and grid
- [ ] Bottom element tray with 8 categories and 45+ elements works
- [ ] Adding, selecting, deleting elements works
- [ ] Keyboard shortcuts work (Ctrl+Z, Ctrl+S, Delete, etc.)

## Work Log

- Created task file with correct UI layout analysis
