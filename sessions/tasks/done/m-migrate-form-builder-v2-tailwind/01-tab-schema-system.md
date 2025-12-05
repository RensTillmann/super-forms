# Subtask 01: Tab Schema System

## Goal

Create a schema-driven tab system that mirrors the existing element schema pattern. Tabs should be:
- Registered via `registerTab()` function
- Rendered dynamically from schema definitions
- Support lazy loading for heavy components
- Use Tailwind CSS (no custom CSS classes)

## Implementation Details

### Schema Location

```
src/react/admin/schemas/tabs/
├── types.ts     # Zod schemas and TypeScript types
├── registry.ts  # Tab registration and query functions
└── index.ts     # Default tab registrations + exports
```

### Tab Schema Definition

```typescript
// types.ts
import { z } from 'zod';

export const TabSchemaSchema = z.object({
  id: z.string().regex(/^[a-z][a-z0-9-]*$/),
  label: z.string(),
  icon: z.string(),  // Lucide icon name
  position: z.number(),  // Sort order
  lazyLoad: z.boolean().default(false),
  component: z.string().optional(),  // Component path for lazy loading
  requiredPermission: z.string().optional(),  // Feature gating
});

export type TabSchema = z.infer<typeof TabSchemaSchema>;
```

### Registry Functions

```typescript
// registry.ts
registerTab(schema: TabSchema): TabSchema
getTabSchema(id: string): TabSchema | undefined
getAllTabs(): TabSchema[]
getTabsSorted(): TabSchema[]  // Sorted by position
isTabRegistered(id: string): boolean
```

### Default Tabs

| ID | Label | Icon | Position | Lazy |
|----|-------|------|----------|------|
| canvas | Canvas | Layout | 0 | false |
| emails | Emails | Mail | 10 | true |
| settings | Settings | Settings | 20 | false |
| entries | Entries | Database | 30 | false |
| automation | Automation | Zap | 40 | true |
| style | Style | PaintBucket | 50 | false |
| integrations | Integrations | Webhook | 60 | false |

### TabBar Component

```typescript
// src/react/admin/apps/form-builder-v2/components/TabBar.tsx
interface TabBarProps {
  activeTab: string;
  onTabChange: (tabId: string) => void;
}
```

Uses Tailwind classes:
- Container: `flex items-center gap-1 px-4 py-2 bg-muted/50 border-b border-border`
- Tab button: `flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors`
- Active: `bg-background text-foreground shadow-sm`
- Inactive: `text-muted-foreground hover:text-foreground hover:bg-muted`

### Integration Points

1. **FormBuilderV2.tsx** - Replace hardcoded tabs (lines 3427-3469) with `<TabBar />`
2. **Tab Content** - Use `React.lazy()` for lazy-loaded tabs wrapped in `<Suspense>`
3. **State** - `activeTab` state already exists, just wire to TabBar

## Files to Create

- [ ] `src/react/admin/schemas/tabs/types.ts`
- [ ] `src/react/admin/schemas/tabs/registry.ts`
- [ ] `src/react/admin/schemas/tabs/index.ts`
- [ ] `src/react/admin/apps/form-builder-v2/components/TabBar.tsx`

## Files to Modify

- [ ] `src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx` - Import and use TabBar

## Testing Checklist

- [ ] All tabs render in correct order
- [ ] Active tab styling works
- [ ] Tab click changes activeTab state
- [ ] Lazy-loaded tabs show loading skeleton
- [ ] No custom CSS classes used (all Tailwind)
