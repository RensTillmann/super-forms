---
name: 03-mcp-theme-actions
parent: m-implement-styles-themes-ui
status: pending
created: 2025-12-06
---

# Subtask 3: MCP Theme Actions

## Goal

Extend the existing MCP style action system with theme-specific actions. Enable AI/LLM agents to list, apply, create, delete, and generate themes.

## Success Criteria

- [ ] Schema extended with theme actions in `styleActionSchema.ts`
- [ ] Handlers implemented in `styleActions.ts`
- [ ] `listThemes` - List available themes with filters
- [ ] `getTheme` - Get single theme by ID or slug
- [ ] `applyTheme` - Apply theme to current form
- [ ] `createTheme` - Save current styles as new theme
- [ ] `deleteTheme` - Delete custom theme
- [ ] `generateTheme` - Create theme from prompt/baseColor/density/cornerStyle
- [ ] AI example prompts documented as test cases
- [ ] Integration with REST API via `wp.apiFetch()`

## Technical Specification

### Action Schemas

Add to `styleActionSchema.ts`:

```typescript
// =============================================================================
// Theme Actions
// =============================================================================

const ListThemesAction = z.object({
  action: z.literal('listThemes'),
  includeSystem: z.boolean().optional().default(true),
  includeStubs: z.boolean().optional().default(true),
  category: z.enum(['light', 'dark', 'minimal', 'corporate', 'playful', 'highContrast']).optional(),
});

const GetThemeAction = z.object({
  action: z.literal('getTheme'),
  themeId: z.union([z.string(), z.number()]),
});

const ApplyThemeAction = z.object({
  action: z.literal('applyTheme'),
  themeId: z.union([z.string(), z.number()]), // ID or slug
});

const CreateThemeAction = z.object({
  action: z.literal('createTheme'),
  name: z.string().min(1).max(100),
  description: z.string().max(500).optional(),
  category: z.enum(['light', 'dark', 'minimal', 'corporate', 'playful', 'highContrast']).optional().default('light'),
});

const DeleteThemeAction = z.object({
  action: z.literal('deleteTheme'),
  themeId: z.union([z.string(), z.number()]),
});

const GenerateThemeAction = z.object({
  action: z.literal('generateTheme'),
  name: z.string().min(1).max(100),
  prompt: z.string().max(500).optional(),           // "warm friendly", "corporate blue"
  baseColor: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),  // Brand color
  density: z.enum(['compact', 'comfortable', 'spacious']).optional().default('comfortable'),
  cornerStyle: z.enum(['sharp', 'rounded', 'pill']).optional().default('rounded'),
  contrastPreference: z.enum(['high', 'standard', 'soft']).optional().default('standard'),
  save: z.boolean().optional().default(true),       // Persist to database?
});
```

Update combined schema:

```typescript
export const StyleActionSchema = z.discriminatedUnion('action', [
  // Existing style actions...
  GetGlobalStyleAction,
  SetGlobalStyleAction,
  // ... etc

  // Theme actions
  ListThemesAction,
  GetThemeAction,
  ApplyThemeAction,
  CreateThemeAction,
  DeleteThemeAction,
  GenerateThemeAction,
]);
```

### Handler Implementations

Add to `styleActions.ts`:

```typescript
// =====================================================================
// Theme Operations
// =====================================================================

case 'listThemes': {
  const response = await wp.apiFetch({
    path: '/super-forms/v1/themes',
    method: 'GET',
    data: {
      include_stubs: action.includeStubs,
      category: action.category,
    },
  });

  return {
    success: true,
    data: {
      themes: response,
      count: response.length,
    },
  };
}

case 'getTheme': {
  const path = typeof action.themeId === 'number'
    ? `/super-forms/v1/themes/${action.themeId}`
    : `/super-forms/v1/themes/slug/${action.themeId}`;

  const theme = await wp.apiFetch({ path, method: 'GET' });

  return {
    success: true,
    data: { theme },
  };
}

case 'applyTheme': {
  // Resolve theme ID (might be slug)
  let themeId = action.themeId;
  if (typeof themeId === 'string' && isNaN(Number(themeId))) {
    const theme = await wp.apiFetch({
      path: `/super-forms/v1/themes/slug/${themeId}`,
      method: 'GET',
    });
    themeId = theme.id;
  }

  // Get current form ID from store
  const formId = useFormStore.getState().formId;
  if (!formId) {
    return { success: false, error: 'No form loaded' };
  }

  // Apply via REST API
  await wp.apiFetch({
    path: `/super-forms/v1/themes/${themeId}/apply`,
    method: 'POST',
    data: { form_id: formId },
  });

  // Fetch theme and apply to local registry
  const theme = await wp.apiFetch({
    path: `/super-forms/v1/themes/${themeId}`,
    method: 'GET',
  });

  styleRegistry.importStyles(JSON.stringify(theme.styles));

  return {
    success: true,
    data: {
      themeId,
      themeName: theme.name,
      message: `Theme "${theme.name}" applied successfully`,
    },
  };
}

case 'createTheme': {
  // Export current styles
  const currentStyles = styleRegistry.exportStyles();

  // Generate preview colors from current theme
  const parsed = JSON.parse(currentStyles);
  const previewColors = [
    parsed.input?.backgroundColor || '#ffffff',
    parsed.label?.color || '#1f2937',
    parsed.button?.backgroundColor || '#2563eb',
    parsed.input?.borderColor || '#d1d5db',
  ];

  const response = await wp.apiFetch({
    path: '/super-forms/v1/themes',
    method: 'POST',
    data: {
      name: action.name,
      description: action.description,
      category: action.category,
      styles: parsed,
      preview_colors: previewColors,
    },
  });

  return {
    success: true,
    data: {
      themeId: response.id,
      themeName: response.name,
      message: `Theme "${response.name}" created successfully`,
    },
  };
}

case 'deleteTheme': {
  await wp.apiFetch({
    path: `/super-forms/v1/themes/${action.themeId}`,
    method: 'DELETE',
  });

  return {
    success: true,
    data: {
      deleted: action.themeId,
      message: 'Theme deleted successfully',
    },
  };
}

case 'generateTheme': {
  const generatedStyles = generateThemeFromOptions({
    baseColor: action.baseColor,
    prompt: action.prompt,
    density: action.density,
    cornerStyle: action.cornerStyle,
    contrastPreference: action.contrastPreference,
  });

  // Apply to registry
  styleRegistry.importStyles(JSON.stringify(generatedStyles));

  let savedTheme = null;
  if (action.save) {
    // Save to database
    const response = await wp.apiFetch({
      path: '/super-forms/v1/themes',
      method: 'POST',
      data: {
        name: action.name,
        description: `Generated theme: ${action.prompt || action.baseColor}`,
        category: detectCategory(generatedStyles),
        styles: generatedStyles,
        preview_colors: extractPreviewColors(generatedStyles),
      },
    });
    savedTheme = response;
  }

  return {
    success: true,
    data: {
      generated: true,
      saved: action.save,
      themeId: savedTheme?.id,
      styles: generatedStyles,
    },
  };
}
```

### Theme Generation Logic

Create `/src/react/admin/lib/themeGenerator.ts`:

```typescript
import Color from 'color'; // or custom color manipulation

interface GenerateThemeOptions {
  baseColor?: string;
  prompt?: string;
  density: 'compact' | 'comfortable' | 'spacious';
  cornerStyle: 'sharp' | 'rounded' | 'pill';
  contrastPreference: 'high' | 'standard' | 'soft';
}

export function generateThemeFromOptions(options: GenerateThemeOptions): Record<NodeType, Partial<StyleProperties>> {
  const {
    baseColor = '#2563eb',
    density = 'comfortable',
    cornerStyle = 'rounded',
    contrastPreference = 'standard',
  } = options;

  const primary = Color(baseColor);
  const isDark = primary.isDark();

  // Generate palette from base color
  const palette = {
    primary: baseColor,
    primaryHover: primary.darken(0.1).hex(),
    primaryContrast: isDark ? '#ffffff' : '#000000',
    background: isDark ? '#1f2937' : '#ffffff',
    surface: isDark ? '#374151' : '#f9fafb',
    text: isDark ? '#f9fafb' : '#1f2937',
    textMuted: isDark ? '#9ca3af' : '#6b7280',
    border: isDark ? '#4b5563' : '#d1d5db',
    error: '#dc2626',
    success: '#16a34a',
  };

  // Density modifiers
  const densityScale = {
    compact: { padding: 0.75, margin: 0.75, fontSize: 0.9 },
    comfortable: { padding: 1, margin: 1, fontSize: 1 },
    spacious: { padding: 1.5, margin: 1.25, fontSize: 1.1 },
  }[density];

  // Border radius based on corner style
  const borderRadius = {
    sharp: 0,
    rounded: 6,
    pill: 999,
  }[cornerStyle];

  // Contrast adjustments
  const contrastMod = {
    high: { borderWidth: 2, fontWeight: '600' },
    standard: { borderWidth: 1, fontWeight: '500' },
    soft: { borderWidth: 1, fontWeight: '400' },
  }[contrastPreference];

  return {
    label: {
      fontSize: Math.round(14 * densityScale.fontSize),
      fontWeight: contrastMod.fontWeight,
      color: palette.text,
      margin: { top: 0, right: 0, bottom: Math.round(4 * densityScale.margin), left: 0 },
    },
    input: {
      fontSize: Math.round(14 * densityScale.fontSize),
      color: palette.text,
      backgroundColor: palette.background,
      borderColor: palette.border,
      borderRadius,
      border: {
        top: contrastMod.borderWidth,
        right: contrastMod.borderWidth,
        bottom: contrastMod.borderWidth,
        left: contrastMod.borderWidth
      },
      padding: {
        top: Math.round(10 * densityScale.padding),
        right: Math.round(14 * densityScale.padding),
        bottom: Math.round(10 * densityScale.padding),
        left: Math.round(14 * densityScale.padding),
      },
    },
    button: {
      fontSize: Math.round(15 * densityScale.fontSize),
      fontWeight: contrastMod.fontWeight,
      color: palette.primaryContrast,
      backgroundColor: palette.primary,
      borderRadius,
      padding: {
        top: Math.round(12 * densityScale.padding),
        right: Math.round(24 * densityScale.padding),
        bottom: Math.round(12 * densityScale.padding),
        left: Math.round(24 * densityScale.padding),
      },
    },
    // ... other node types
  };
}

export function detectCategory(styles: Record<string, unknown>): string {
  const bgColor = styles.input?.backgroundColor || '#ffffff';
  const color = Color(bgColor);
  return color.isDark() ? 'dark' : 'light';
}

export function extractPreviewColors(styles: Record<string, unknown>): string[] {
  return [
    styles.input?.backgroundColor || '#ffffff',
    styles.label?.color || '#1f2937',
    styles.button?.backgroundColor || '#2563eb',
    styles.input?.borderColor || '#d1d5db',
  ];
}
```

### AI Example Prompts (Test Cases)

| User Says | MCP Action | Expected Result |
|-----------|------------|-----------------|
| "use a dark theme" | `{ action: 'applyTheme', themeId: 'dark' }` | Dark theme applied to form |
| "list available themes" | `{ action: 'listThemes' }` | Returns array of theme objects |
| "save this as a new theme called 'My Brand'" | `{ action: 'createTheme', name: 'My Brand' }` | Current styles saved as new theme |
| "delete my custom theme" | `{ action: 'deleteTheme', themeId: 123 }` | Theme removed from database |
| "create a theme based on #FF5722" | `{ action: 'generateTheme', name: 'Orange', baseColor: '#FF5722' }` | Orange palette theme generated |
| "make a compact corporate theme" | `{ action: 'generateTheme', name: 'Corporate', prompt: 'corporate', density: 'compact' }` | Dense professional theme |
| "create high contrast accessible theme" | `{ action: 'generateTheme', name: 'Accessible', contrastPreference: 'high' }` | WCAG-friendly theme |
| "use sharp corners and spacious layout" | `{ action: 'generateTheme', name: 'Modern', cornerStyle: 'sharp', density: 'spacious' }` | Sharp, airy theme |
| "apply the minimal theme" | `{ action: 'applyTheme', themeId: 'minimal' }` | Returns error (stub theme) |

### Updated Tool Definition

```typescript
export const styleToolDefinition = {
  name: 'form_styles',
  description: `
Manage form element styles with global themes and individual overrides.

## Theme Operations

### List themes:
{ "action": "listThemes" }

### Apply a theme (by ID or slug):
{ "action": "applyTheme", "themeId": "dark" }
{ "action": "applyTheme", "themeId": 123 }

### Save current styles as theme:
{ "action": "createTheme", "name": "My Custom Theme" }

### Generate new theme from parameters:
{
  "action": "generateTheme",
  "name": "Brand Theme",
  "baseColor": "#FF5722",
  "density": "comfortable",
  "cornerStyle": "rounded"
}

## Style Operations
...existing documentation...
`,
  inputSchema: StyleActionSchema,
};
```

## Files to Modify

1. `/src/react/admin/mcp/schemas/styleActionSchema.ts` - Add theme action schemas
2. `/src/react/admin/mcp/handlers/styleActions.ts` - Add theme handlers

## Files to Create

1. `/src/react/admin/lib/themeGenerator.ts` - Theme generation logic

## Implementation Notes

- Use `wp.apiFetch()` for REST API calls (WordPress pattern)
- Theme slugs can be used interchangeably with IDs for applyTheme
- generateTheme uses color theory to derive full palette from baseColor
- Stub themes return error when trying to apply
- generateTheme with `save: false` only applies locally (doesn't persist)

## Dependencies

- Subtask 01 (Database Schema & DAL) must be complete
- Subtask 02 (REST API) must be complete
- Existing style registry and hooks
