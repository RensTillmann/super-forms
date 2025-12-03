---
name: m-docs-ui-style-guidelines
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-03
---

# UI Style Guidelines Documentation

## Problem/Goal

The project uses React + Tailwind CSS v4 + shadcn/ui for admin UI, but lacks documentation on:
- Color scheme and design tokens
- Component usage patterns
- Tailwind configuration
- How to build consistent WordPress admin UI

This makes it difficult for AI assistants and developers to maintain UI consistency.

**Goal:** Create comprehensive UI documentation that CLAUDE.md can reference when working on React/Tailwind/shadcn-ui tasks.

## Success Criteria
- [ ] Document color scheme from `styles/index.css` (light/dark modes)
- [ ] Document shadcn/ui component patterns and usage
- [ ] Document Tailwind v4 configuration and scoping approach
- [ ] Create CLAUDE.ui.md (or similar) consolidating all UI guidelines
- [ ] Update main CLAUDE.md to reference new UI documentation
- [ ] Optionally: Create/update `building-wordpress-ui` skill

## Context Manifest
<!-- Added by context-gathering agent -->

### How the React + Tailwind CSS v4 + shadcn/ui Style System Works

**Overview:** Super Forms uses a modern, WordPress-integrated UI system for all React admin pages. The architecture is designed specifically to prevent CSS conflicts with WordPress admin UI while providing full Tailwind utility access inside React components.

**Critical CSS Scoping Strategy:**

When you build a WordPress plugin with React and Tailwind CSS, you face a major challenge: Tailwind's default preflight reset (`@import "tailwindcss"`) resets ALL elements globally, which breaks WordPress admin UI (sidebar, top bar, notices, buttons). Super Forms solves this with a scoped reset approach.

The system loads CSS ONLY on Super Forms admin pages and scopes all resets to `#sfui-admin-root` (the React mount point). This means:
- WordPress admin UI remains untouched
- All Tailwind utilities work normally inside React components (no prefix needed!)
- Z-index coordination ensures modals appear above WP admin bar

**Architecture Flow:**

1. **PHP renders mount point** (`/src/includes/admin/views/page-forms-list-react.php`):
   - Creates `<div id="sfui-admin-root"></div>` container
   - Enqueues `admin.css` (scoped styles) and `admin.js` or `forms-list.js` (React bundle)
   - Passes initial data via `window.sfuiData` object

2. **CSS loads with scoping** (`/src/react/admin/styles/index.css`):
   - Imports Tailwind v4 utilities only: `@import "tailwindcss/theme"` and `@import "tailwindcss/utilities"`
   - Does NOT import `@import "tailwindcss"` (which includes preflight global reset)
   - Defines shadcn/ui CSS variables on `:root` (safe since CSS only loads on SF pages)
   - Scopes all resets to `#sfui-admin-root` selector (box-sizing, borders, images, forms, etc.)
   - Sets `--z-overlay: 100000` for Radix UI modals to appear above WP admin bar (z-index 99999)

3. **React renders inside mount point** (`/src/react/admin/pages/forms-list/index.tsx`):
   - All components render inside `#sfui-admin-root`
   - Tailwind utilities work normally: `className="flex gap-4 bg-card rounded-lg"`
   - No prefix needed (unlike old `sfui:` or `ev2:` patterns found in legacy code)

**Why This Architecture:**

The scoping strategy prevents three critical failures:
- **Without scoping:** Tailwind preflight breaks WP admin sidebar, top bar, notices
- **With global preflight + prefix:** Every utility needs `sfui:text-sm sfui:bg-white` (verbose, error-prone)
- **With scoped preflight (current):** Full Tailwind power inside React, WordPress untouched outside

### Color Scheme and Design Tokens

**Primary Color System (Light Mode):**
- Background: `hsl(210 40% 98%)` - Very light blue-gray
- Foreground: `hsl(217.24 32.58% 17.45%)` - Dark blue-gray text
- Primary: `hsl(175.02 181.45% 26.06%)` - Teal accent
- Primary foreground: White
- Secondary: `hsl(220 13.04% 90.98%)` - Light gray
- Muted: `hsl(220 14.29% 95.88%)` - Very light gray for backgrounds
- Border: `hsl(216 12.20% 83.92%)` - Medium gray borders
- Destructive: `hsl(0 84.24% 60.20%)` - Red for delete actions

**Dark Mode (`.dark` class):**
- Background: `hsl(222.22 47.37% 11.18%)` - Very dark blue
- Primary: `hsl(234.45 89.47% 73.92%)` - Lighter teal/purple for contrast
- All colors adjusted for proper dark mode contrast

**Custom Color Scales:**
- Primary scale: `primary-50` through `primary-900` (blue spectrum)
- Gray scale: `gray-50` through `gray-900` (neutral grays)

**Shadows:**
- Range from `shadow-2xs` to `shadow-2xl`
- Consistent `hsl(0 0% 0% / opacity)` format
- Example: `--shadow-sm: 0px 4px 8px -1px hsl(0 0% 0% / 0.10), 0px 1px 2px -2px hsl(0 0% 0% / 0.10)`

**Border Radius:**
- Base: `--radius: 0.5rem`
- Variants: `radius-sm` (4px less), `radius-md` (2px less), `radius-lg` (base), `radius-xl` (4px more)

**How Colors are Used in Components:**

Tailwind utilities reference CSS variables automatically:
```tsx
// Uses --color-background CSS variable
<div className="bg-background text-foreground">

// Uses --color-primary CSS variable
<Button className="bg-primary text-primary-foreground">

// Uses --color-border CSS variable
<div className="border border-border rounded-lg">
```

The `@theme inline` block in `styles/index.css` maps all CSS variables to Tailwind utilities, so `bg-background` becomes `background-color: var(--background)`.

### shadcn/ui Configuration and Component System

**Configuration File:** `/src/react/admin/components.json`

```json
{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "default",
  "tsx": true,
  "tailwind": {
    "config": "",
    "css": "styles/index.css",
    "baseColor": "slate",
    "cssVariables": true
  },
  "aliases": {
    "components": "@/components",
    "utils": "@/lib/utils",
    "ui": "@/components/ui"
  }
}
```

**What shadcn/ui Is:**
- NOT an npm package - it's a component library you copy-paste into your project
- Built on Radix UI primitives (accessible, unstyled React components)
- Styled with Tailwind CSS
- Full source code ownership - you can modify components freely

**Installing Components:**

```bash
cd /home/rens/super-forms/src/react/admin
npx shadcn@latest add button      # Copies button.tsx to components/ui/
npx shadcn@latest add dialog      # Copies dialog.tsx to components/ui/
npx shadcn@latest add input       # Copies input.tsx to components/ui/
```

**Available Components in Project:**
- `button.tsx` - Button with variants (default, destructive, outline, ghost, link)
- `dialog.tsx` - Modal dialog with overlay (z-index 100000 for WP admin compatibility)
- `input.tsx` - Text input with focus ring
- `label.tsx` - Form label
- `radio-group.tsx` - Radio button group
- `skeleton.tsx` - Loading skeleton
- Plus custom components: `CustomButton.tsx`, `Tag.tsx`, `TagInput.tsx`

**Component Usage Pattern:**

```tsx
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

// Button variants
<Button variant="default">Save</Button>
<Button variant="outline">Cancel</Button>
<Button variant="destructive">Delete</Button>
<Button variant="ghost" size="sm">Edit</Button>

// Dialog
<Dialog open={isOpen} onOpenChange={setIsOpen}>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Form Settings</DialogTitle>
    </DialogHeader>
    {/* Dialog body */}
  </DialogContent>
</Dialog>

// Input with label
<div>
  <Label htmlFor="name">Name</Label>
  <Input id="name" value={name} onChange={(e) => setName(e.target.value)} />
</div>
```

**The `cn()` Utility:**

Located at `/src/react/admin/lib/utils.ts`, this helper combines `clsx` (conditional classes) and `tailwind-merge` (merges conflicting Tailwind classes):

```tsx
import { cn } from '@/lib/utils';

// Conditional classes
<div className={cn(
  "px-4 py-2 rounded-md",
  isActive && "bg-primary text-white",
  !isActive && "bg-gray-100 text-gray-600"
)}>

// Merging classes (tailwind-merge resolves conflicts)
<Button className={cn(buttonVariants({ variant: "outline" }), "w-full")}>
  // w-full overrides default button width
</Button>
```

**Button Variants (class-variance-authority):**

The button component uses `cva` to define variants:

```tsx
const buttonVariants = cva(
  "base classes...",
  {
    variants: {
      variant: {
        default: "bg-primary text-primary-foreground hover:bg-primary/90",
        outline: "border border-gray-200 bg-white hover:bg-gray-100",
        // ...
      },
      size: {
        default: "h-10 px-4 py-2",
        sm: "h-9 rounded-md px-3",
        // ...
      }
    }
  }
);
```

### Tailwind CSS v4 Configuration

**Build System:** Vite with `@tailwindcss/vite` plugin

**Configuration Method:** CSS-based configuration (no `tailwind.config.js` file)

All Tailwind configuration lives in `/src/react/admin/styles/index.css`:

```css
/* Import only theme and utilities (NO preflight) */
@import "tailwindcss/theme";
@import "tailwindcss/utilities";

/* shadcn/ui theme variables on :root */
:root {
  --background: hsl(210 40% 98%);
  /* ... */
}

/* Map CSS variables to Tailwind (inline theme) */
@theme inline {
  --color-background: var(--background);
  --color-foreground: var(--foreground);
  /* ... */
}

/* Custom additions */
@theme {
  --color-primary-500: #3b82f6;
  --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, ...;
  --spacing-18: 4.5rem;
  --animate-slide-down: slideDown 0.2s ease-out;
  /* ... */
}

/* Scoped resets */
#sfui-admin-root {
  font-family: var(--font-sans);
  *, *::before, *::after {
    box-sizing: border-box;
    /* ... */
  }
}
```

**Why CSS-based Configuration:**
- Tailwind v4 prefers CSS-based config over JS config files
- Keeps all styling concerns in one file
- Easier to see theme values and customizations
- No separate `tailwind.config.js` to maintain

**Custom Hover Variant:**

```css
@custom-variant hover (&:hover);
```

This makes hover work consistently on all devices (prevents mobile hover state issues).

### Component Patterns from Existing Code

**Forms List Page Pattern:**

The forms list page demonstrates best practices for React admin pages:

1. **TypeScript interfaces** for data structures
2. **WordPress REST API integration** via `wp.apiFetch()`
3. **shadcn/ui components** for UI elements
4. **Lucide React icons** for all icons (NO emoji icons!)
5. **State management** with React hooks
6. **Loading states** for async operations

**Example from FormsList.tsx:**

```tsx
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, Plus, Trash2 } from 'lucide-react';

// TypeScript interface
interface Form {
  id: number;
  name: string;
  status: string;
  // ...
}

// Component with typed props
export function FormsList({ forms, statusCounts }: FormsListProps) {
  const [isLoading, setIsLoading] = useState(false);

  // REST API call
  const handleDelete = async (formId: number) => {
    setIsLoading(true);
    try {
      await wp.apiFetch({
        path: `/super-forms/v1/forms/${formId}`,
        method: 'DELETE'
      });
      window.location.reload();
    } catch (error) {
      console.error(error);
      alert('Error occurred');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="space-y-4">
      {/* Search bar */}
      <div className="flex gap-2">
        <Input
          placeholder="Search forms..."
          className="flex-1"
        />
        <Button>
          <Search className="w-4 h-4 mr-2" />
          Search
        </Button>
      </div>

      {/* Forms table */}
      <div className="rounded-lg border border-border bg-card">
        <table className="w-full">
          <thead className="border-b border-border bg-muted/50">
            {/* ... */}
          </thead>
          <tbody>
            {forms.map(form => (
              <tr key={form.id} className="border-b hover:bg-muted/30">
                {/* ... */}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
```

**Automations Page Pattern:**

Shows minimal page wrapper approach:

```tsx
export default function AutomationsPage({ formId }: Props) {
  if (!formId) {
    return (
      <div className="flex items-center justify-center h-full p-8">
        <div className="text-center">
          <h2 className="text-xl font-semibold text-gray-900 mb-2">
            No Form Selected
          </h2>
          <p className="text-gray-600">
            Please select a form to manage automations.
          </p>
        </div>
      </div>
    );
  }

  return <AutomationsTab formId={formId} />;
}
```

### Key Files and Their Purposes

**Styles:**
- `/src/react/admin/styles/index.css` - Main stylesheet (2300+ lines)
  - Tailwind v4 imports (theme + utilities only)
  - shadcn/ui CSS variables (light/dark mode)
  - Custom theme additions
  - Scoped resets for `#sfui-admin-root`
  - Custom animations (slideDown, slideUp, fadeIn)

**Configuration:**
- `/src/react/admin/components.json` - shadcn/ui config
- `/src/react/admin/vite.config.ts` - Build config
- `/src/react/admin/tsconfig.json` - TypeScript config
- `/src/react/admin/package.json` - Dependencies

**Utilities:**
- `/src/react/admin/lib/utils.ts` - `cn()` helper for className merging

**UI Components:**
- `/src/react/admin/components/ui/button.tsx` - Button with variants
- `/src/react/admin/components/ui/dialog.tsx` - Modal dialogs
- `/src/react/admin/components/ui/input.tsx` - Text inputs
- `/src/react/admin/components/ui/label.tsx` - Form labels
- `/src/react/admin/components/ui/radio-group.tsx` - Radio buttons
- `/src/react/admin/components/ui/skeleton.tsx` - Loading skeletons

**Example Pages:**
- `/src/react/admin/pages/forms-list/FormsList.tsx` - Forms management table
- `/src/react/admin/pages/form-builder/automations/AutomationsPage.tsx` - Workflow editor wrapper

**Build Output:**
- `/src/assets/js/backend/admin.js` - Main admin bundle (form builder, email builder, automations)
- `/src/assets/js/backend/forms-list.js` - Standalone forms list bundle
- `/src/assets/css/backend/admin.css` - Shared styles (moved from js/ to css/ by Vite plugin)

### Documentation to Reference

**Existing Docs:**
- `/docs/CLAUDE.javascript.md` - React development, build commands, TypeScript patterns
- `/docs/CLAUDE.php.md` - WordPress integration, React admin pages with REST API

**Relevant Sections in CLAUDE.javascript.md:**
- Lines 1-165: React Admin UI directory structure and architecture
- Lines 89-132: CSS Architecture (critical CSS isolation strategy)
- Lines 140-171: Element identification with data-testid
- Lines 278-461: UI Stack (Tailwind v4 + shadcn/ui + Lucide icons)
- Lines 805-982: Forms List Page (React + Tailwind CSS)
- Lines 877-982: WordPress REST API Integration

**Relevant Sections in CLAUDE.php.md:**
- Lines 1877-1980: React Admin Pages with REST API pattern

**Icon Guidelines (CRITICAL):**
- Lines 1348-1358 in CLAUDE.javascript.md: Icons - CRITICAL RULES
- ONLY use Lucide React icons - NO emoji icons ever
- Example imports: `import { Mail, Settings, ChevronDown } from 'lucide-react'`
- Standard sizing: `className="w-4 h-4"` for buttons, `w-5 h-5` for larger elements

### Dependencies and Versions

**Production Dependencies:**
- `react` / `react-dom`: ^18.2.0
- `tailwindcss`: ^4.0.0
- `@tailwindcss/vite`: ^4.0.0
- `lucide-react`: ^0.525.0 (icon library)
- `@radix-ui/react-*`: Various versions (primitives for shadcn/ui)
- `class-variance-authority`: ^0.7.1 (variant system)
- `clsx`: ^2.1.0 (conditional classes)
- `tailwind-merge`: ^3.4.0 (merge Tailwind classes)
- `zustand`: ^4.4.7 (state management)
- `@dnd-kit/*`: Various (drag & drop)
- `framer-motion`: ^10.16.16 (animations)

**Dev Dependencies:**
- `vite`: ^7.2.4 (build tool)
- `@vitejs/plugin-react`: ^5.1.1
- `typescript`: ^5.3.3
- `@types/react`: ^18.2.46
- `@types/react-dom`: ^18.2.18

### Build Commands

```bash
cd /home/rens/super-forms/src/react/admin

# Development mode with watch
npm run watch              # Main admin bundle (admin.js)
npm run watch:forms-list   # Forms list page (forms-list.js)

# Production build
npm run build              # Builds all bundles
npm run build:prod         # Explicit production mode

# Type checking (no build)
npm run typecheck

# Preview build
npm run preview
```

**Multi-Entry Build System:**

Vite config supports multiple entry points via `ENTRY` environment variable:

- `index.tsx` → `admin.js` (default)
- `pages/forms-list/index.tsx` → `forms-list.js`

**Build Output Locations:**
- JavaScript: `/src/assets/js/backend/`
- CSS: `/src/assets/css/backend/` (moved by Vite plugin)

### WordPress Integration Pattern

**PHP Side (example from page-forms-list-react.php):**

```php
// Enqueue React bundle with wp-api-fetch dependency
wp_enqueue_script(
    'super-forms-list',
    SUPER_PLUGIN_FILE . 'assets/js/backend/forms-list.js',
    array('wp-api-fetch'),  // Required for wp.apiFetch()
    SUPER_VERSION,
    true
);

// Enqueue shared styles
wp_enqueue_style(
    'super-admin',
    SUPER_PLUGIN_FILE . 'assets/css/backend/admin.css',
    array(),
    SUPER_VERSION
);

// Pass data to React
$react_data = array(
    'forms' => $forms_data,
    'statusCounts' => $status_counts
);

// Render mount point
echo '<div id="sfui-admin-root"></div>';
echo '<script>window.sfuiData = ' . wp_json_encode($react_data) . ';</script>';
```

**React Side:**

```tsx
// Type definition
interface Window {
  sfuiData: {
    forms: Form[];
    statusCounts: { all: number; publish: number; /* ... */ };
  }
}

// Access data
const { forms, statusCounts } = window.sfuiData;

// REST API calls
await wp.apiFetch({
  path: '/super-forms/v1/forms/123',
  method: 'GET'
});
```

### Common Mistakes to Avoid

1. **Don't import full Tailwind in styles:**
   - ❌ `@import "tailwindcss"` (includes preflight)
   - ✅ `@import "tailwindcss/theme"` + `@import "tailwindcss/utilities"`

2. **Don't use emoji icons:**
   - ❌ `<span>📧</span>` or `<span>🔔</span>`
   - ✅ `import { Mail, Bell } from 'lucide-react'`

3. **Don't add Tailwind prefix:**
   - ❌ `className="sfui:text-sm sfui:bg-white"`
   - ✅ `className="text-sm bg-white"`

4. **Don't forget scoped resets:**
   - All resets must be inside `#sfui-admin-root { }` block
   - Never add global resets that affect WordPress admin UI

5. **Don't use low z-index for modals:**
   - WordPress admin bar is z-index 99999
   - Modals need z-index 100000 or higher
   - Already configured in dialog.tsx component

6. **Don't create custom AJAX handlers:**
   - ❌ Custom AJAX with nonces
   - ✅ WordPress REST API with `wp.apiFetch()`

### Testing and Validation

**Visual Inspection:**
- Check that WordPress admin sidebar/top bar remain unstyled
- Verify modals appear above admin bar
- Test light/dark mode if implemented
- Verify all icons are Lucide React (no emojis)

**Build Validation:**
```bash
cd /home/rens/super-forms/src/react/admin
npm run build          # Must complete without errors
npm run typecheck      # Must pass all type checks
```

**Browser Testing:**
- Inspect React mount point: `document.getElementById('sfui-admin-root')`
- Verify CSS scoping: Styles only apply inside `#sfui-admin-root`
- Check computed styles: Background colors should match CSS variables
- Test responsive design: Mobile, tablet, desktop breakpoints

## User Notes
- Using current branch (feature/h-implement-triggers-actions-extensibility) - no new branch
- Existing color system in `/src/react/admin/styles/index.css`
- shadcn/ui config in `/src/react/admin/components.json`
- Components in `/src/react/admin/components/ui/`

## Work Log
<!-- Updated as work progresses -->
