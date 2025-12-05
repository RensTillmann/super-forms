# UI Style Guidelines

React admin UI development with Tailwind CSS v4, shadcn/ui, and WordPress integration.

## Table of Contents

- [Project Structure](#project-structure)
- [CSS Scoping Strategy](#css-scoping-strategy)
- [Design Tokens](#design-tokens)
  - [Colors](#colors)
  - [Typography](#typography)
  - [Spacing](#spacing)
  - [Border Radius](#border-radius)
  - [Shadows](#shadows)
  - [Animations](#animations)
- [shadcn/ui Components](#shadcnui-components)
- [Icons](#icons)
- [Component Patterns](#component-patterns)
- [UI State Patterns](#ui-state-patterns)
- [Accessibility](#accessibility)
- [Code Quality Guidelines](#code-quality-guidelines)
- [Naming Conventions](#naming-conventions)
- [WordPress Integration](#wordpress-integration)
- [Build Commands](#build-commands)
- [Common Mistakes](#common-mistakes)
- [PR Checklist](#pr-checklist)

---

## Project Structure

```
/src/react/admin/
├── index.tsx                    # Main entry point (page routing)
├── package.json                 # Dependencies
├── tsconfig.json                # TypeScript config
├── vite.config.ts               # Build config
├── components.json              # shadcn/ui config
├── types/
│   └── global.d.ts              # TypeScript globals (window.sfuiData)
├── lib/
│   └── utils.ts                 # Utilities (cn helper)
├── hooks/                       # Shared React hooks
├── styles/
│   └── index.css                # Global styles (scoped to #sfui-admin-root)
├── components/
│   ├── ui/                      # shadcn/ui primitives (Button, Dialog, Input)
│   ├── shared/                  # Reusable composed components
│   └── [feature]/               # Feature-specific components
└── pages/
    ├── forms-list/              # Forms list page
    ├── form-builder/            # Form builder page
    │   ├── automations/         # Automations tab
    │   └── emails-tab/          # Email builder tab
    └── [page-name]/             # Future pages
```

### Where New Files Go

| Type | Location | Example |
|------|----------|---------|
| shadcn/ui primitives | `components/ui/` | `button.tsx`, `dialog.tsx` |
| Reusable composed components | `components/shared/` | `SearchBar.tsx`, `DataTable.tsx` |
| Feature-specific components | `components/[feature]/` | `components/automations/NodePicker.tsx` |
| Page entry points | `pages/[page-name]/` | `pages/settings/index.tsx` |
| Custom hooks | `hooks/` | `useDebounce.ts`, `useLocalStorage.ts` |
| Type definitions | `types/` | `types/forms.d.ts` |

---

## CSS Scoping Strategy

**The Problem:** Tailwind's default preflight (`@import "tailwindcss"`) resets ALL elements globally, breaking WordPress admin UI.

**The Solution:** Scoped resets inside the React mount point only, using `@layer base` for proper CSS cascade.

### How It Works

1. PHP renders `<div id="sfui-admin-root"></div>`
2. CSS imports only `tailwindcss/theme` and `tailwindcss/utilities` (NO preflight)
3. All resets scoped to `#sfui-admin-root` selector **inside `@layer base`**
4. `@layer base` ensures Tailwind utilities always override base resets
5. Tailwind utilities work normally inside React, WordPress untouched outside

### Complete Scoped CSS Example

This is the correct "final form" for scoped Tailwind in WordPress:

```css
/* ==========================================================================
   CORRECT: Scoped Tailwind for WordPress Plugin
   ========================================================================== */

/* Theme and utilities only - NO global preflight */
@import "tailwindcss/theme";
@import "tailwindcss/utilities";

/* Custom hover variant for consistent behavior */
@custom-variant hover (&:hover);

/* shadcn/ui variables on :root (safe - CSS only loads on plugin pages) */
:root {
  --z-overlay: 100000;  /* Above WP admin bar (99999) */
  --background: hsl(210 40% 98%);
  --foreground: hsl(217 33% 17%);
  --primary: hsl(175 181% 26%);
  /* ... other variables ... */
  --radius: 0.5rem;
}

/* Dark mode overrides */
.dark {
  --background: hsl(222 47% 11%);
  --foreground: hsl(214 32% 91%);
  /* ... */
}

/* Map CSS variables to Tailwind utilities */
@theme inline {
  --color-background: var(--background);
  --color-foreground: var(--foreground);
  --color-primary: var(--primary);
  --radius-lg: var(--radius);
}

/* Custom theme additions */
@theme {
  --color-primary-500: #3b82f6;
  --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --spacing-18: 4.5rem;
}

/* ==========================================================================
   CRITICAL: All resets MUST be in @layer base and scoped to #sfui-admin-root
   Using @layer base ensures Tailwind utilities always override these resets
   ========================================================================== */

@layer base {
  #sfui-admin-root {
    font-family: var(--font-sans);
    -webkit-font-smoothing: antialiased;
    color: var(--foreground);
    background-color: var(--background);
    line-height: 1.5;
  }

  /* Scoped preflight resets */
  #sfui-admin-root *,
  #sfui-admin-root *::before,
  #sfui-admin-root *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    border: 0 solid var(--border);
  }

  /* Image and media reset */
  #sfui-admin-root img,
  #sfui-admin-root svg,
  #sfui-admin-root video {
    display: block;
    vertical-align: middle;
  }

  #sfui-admin-root img,
  #sfui-admin-root video {
    max-width: 100%;
    height: auto;
  }

  /* Form element reset */
  #sfui-admin-root button,
  #sfui-admin-root input,
  #sfui-admin-root select,
  #sfui-admin-root textarea {
    font-family: inherit;
    font-size: 100%;
    line-height: inherit;
    color: inherit;
  }

  /* Button reset */
  #sfui-admin-root button,
  #sfui-admin-root [role="button"] {
    cursor: pointer;
    background-color: transparent;
  }

  /* Link reset */
  #sfui-admin-root a {
    color: inherit;
    text-decoration: inherit;
  }

  /* List reset */
  #sfui-admin-root ol,
  #sfui-admin-root ul {
    list-style: none;
  }
}
```

**Why @layer base Matters:**
- Without `@layer base`, global resets like `#sfui-admin-root button { padding: 0 }` override Tailwind utilities (px-3, py-2)
- `@layer base` ensures utilities always win in specificity battles
- Prevents CSS specificity conflicts with component classes

---

## Design Tokens

All tokens defined in `/src/react/admin/styles/index.css`.

### Colors

#### Light Mode (Default)

| Token | Value | Usage |
|-------|-------|-------|
| `background` | `hsl(210 40% 98%)` | Page background |
| `foreground` | `hsl(217 33% 17%)` | Primary text |
| `card` | `hsl(0 0% 100%)` | Card backgrounds |
| `primary` | `hsl(175 181% 26%)` | Primary actions (teal) |
| `primary-foreground` | `hsl(0 0% 100%)` | Text on primary |
| `secondary` | `hsl(220 13% 91%)` | Secondary elements |
| `muted` | `hsl(220 14% 96%)` | Muted backgrounds |
| `muted-foreground` | `hsl(220 9% 46%)` | Secondary text |
| `accent` | `hsl(210 41% 96%)` | Hover backgrounds |
| `border` | `hsl(216 12% 84%)` | Borders |
| `destructive` | `hsl(0 84% 60%)` | Delete/error (red) |
| `ring` | `hsl(175 181% 26%)` | Focus rings |

#### Dark Mode

Applied via `.dark` class. Key changes:

| Token | Value |
|-------|-------|
| `background` | `hsl(222 47% 11%)` |
| `foreground` | `hsl(214 32% 91%)` |
| `primary` | `hsl(234 89% 74%)` |
| `border` | `hsl(215 14% 34%)` |

#### Color Scales

For granular control:

```tsx
// Primary (blue) scale
className="bg-primary-50"   // Lightest
className="bg-primary-500"  // Base
className="bg-primary-900"  // Darkest

// Gray scale
className="text-gray-500"
className="bg-gray-100"
```

#### Usage Examples

```tsx
<div className="bg-background text-foreground">       // Page
<div className="bg-card border border-border">        // Card
<Button className="bg-primary text-primary-foreground"> // Primary button
<p className="text-muted-foreground">                 // Secondary text
<Button variant="destructive">                        // Delete action
```

### Typography

#### Scale

| Class | Size | Line Height | Usage |
|-------|------|-------------|-------|
| `text-xs` | 12px | 16px | Badges, captions |
| `text-sm` | 14px | 20px | Body text, table cells |
| `text-base` | 16px | 24px | Default body |
| `text-lg` | 18px | 28px | Lead paragraphs |
| `text-xl` | 20px | 28px | Section headings |
| `text-2xl` | 24px | 32px | Page titles |
| `text-3xl` | 30px | 36px | Hero headings |

#### Heading Standards

```tsx
// Page title
<h1 className="text-2xl font-semibold text-foreground">Page Title</h1>

// Section heading (with spacing)
<h2 className="text-xl font-semibold text-foreground mt-8 mb-4">Section</h2>

// Subsection
<h3 className="text-lg font-medium text-foreground mt-6 mb-3">Subsection</h3>

// Small heading
<h4 className="text-base font-medium text-foreground mt-4 mb-2">Detail</h4>
```

#### Text Styles

```tsx
// Body text
<p className="text-sm text-foreground">Primary content</p>

// Secondary/muted text
<p className="text-sm text-muted-foreground">Less important info</p>

// Error text
<p className="text-sm text-destructive">Error message</p>

// Link
<a className="text-sm text-primary hover:underline">Link text</a>
```

#### Font Weight

| Class | Weight | Usage |
|-------|--------|-------|
| `font-normal` | 400 | Body text |
| `font-medium` | 500 | Labels, small headings |
| `font-semibold` | 600 | Headings, emphasis |
| `font-bold` | 700 | Strong emphasis (rare) |

### Spacing

Based on 4px grid. Common values:

| Class | Value | Usage |
|-------|-------|-------|
| `p-2` / `gap-2` | 8px | Tight spacing (badges, tags) |
| `p-3` / `gap-3` | 12px | Compact spacing |
| `p-4` / `gap-4` | 16px | Default spacing |
| `p-6` / `gap-6` | 24px | Section spacing |
| `p-8` / `gap-8` | 32px | Large sections |

#### Spacing Patterns

```tsx
// Page padding
<div className="p-6">

// Card padding
<div className="p-4">

// Stack items vertically
<div className="space-y-4">

// Inline items with gap
<div className="flex gap-2">

// Form fields
<div className="space-y-4">
  <div className="space-y-2">
    <Label>Field</Label>
    <Input />
  </div>
</div>
```

### Border Radius

| Token | Value | Class | Usage |
|-------|-------|-------|-------|
| `radius-sm` | 4px | `rounded-sm` | Small elements |
| `radius-md` | 6px | `rounded-md` | Buttons, inputs |
| `radius-lg` | 8px | `rounded-lg` | Cards, dialogs |
| `radius-xl` | 12px | `rounded-xl` | Large containers |

### Shadows

| Class | Usage |
|-------|-------|
| `shadow-sm` | Subtle elevation (cards) |
| `shadow` | Default elevation |
| `shadow-md` | Dropdowns, popovers |
| `shadow-lg` | Modals, dialogs |
| `shadow-xl` | Prominent elements |

```tsx
<div className="bg-card rounded-lg shadow-sm">    // Card
<div className="bg-popover rounded-md shadow-md"> // Dropdown
```

### Animations

Defined custom animations:

```css
--animate-slide-down: slideDown 0.2s ease-out;
--animate-slide-up: slideUp 0.2s ease-out;
--animate-fade-in: fadeIn 0.2s ease-out;
```

#### Transitions

Standard transition classes:

```tsx
// Color transitions (buttons, links)
className="transition-colors"

// All transitions
className="transition-all duration-200"

// Hover with transition
className="hover:bg-muted transition-colors"
```

---

## shadcn/ui Components

Copy-paste components built on Radix UI primitives. Full source ownership.

### Configuration

`/src/react/admin/components.json`:

```json
{
  "style": "default",
  "tsx": true,
  "tailwind": { "css": "styles/index.css", "cssVariables": true },
  "aliases": {
    "components": "@/components",
    "ui": "@/components/ui",
    "utils": "@/lib/utils"
  }
}
```

### Installing Components

```bash
cd /home/rens/super-forms/src/react/admin
npx shadcn@latest add button dialog input select
```

### Available Components

| Component | Usage |
|-----------|-------|
| `Button` | Actions, form submits |
| `Dialog` | Modals, confirmations |
| `Input` | Text inputs |
| `Label` | Form labels |
| `RadioGroup` | Radio selections |
| `Skeleton` | Loading placeholders |
| `Select` | Dropdowns |

**Custom Components:** `Tag.tsx`, `TagInput.tsx`, `CustomButton.tsx` - check `components/ui/` for the full list.

### Button Variants

```tsx
import { Button } from '@/components/ui/button';

// Variants
<Button variant="default">Primary</Button>      // Teal bg
<Button variant="outline">Secondary</Button>    // Border only
<Button variant="destructive">Delete</Button>   // Red bg
<Button variant="ghost">Subtle</Button>         // No bg
<Button variant="link">Link</Button>            // Underline

// Sizes
<Button size="default">Normal</Button>  // h-10
<Button size="sm">Small</Button>        // h-9
<Button size="lg">Large</Button>        // h-11
<Button size="icon"><Icon /></Button>   // h-10 w-10
```

### Dialog Pattern

```tsx
import {
  Dialog, DialogContent, DialogHeader,
  DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog';

<Dialog open={isOpen} onOpenChange={setIsOpen}>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Confirm Delete</DialogTitle>
      <DialogDescription>
        This action cannot be undone.
      </DialogDescription>
    </DialogHeader>

    <div className="py-4">
      {/* Content */}
    </div>

    <DialogFooter>
      <Button variant="outline" onClick={() => setIsOpen(false)}>
        Cancel
      </Button>
      <Button variant="destructive" onClick={handleDelete}>
        Delete
      </Button>
    </DialogFooter>
  </DialogContent>
</Dialog>
```

### Form Pattern

```tsx
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

<form className="space-y-4">
  <div className="space-y-2">
    <Label htmlFor="name">Name</Label>
    <Input
      id="name"
      value={name}
      onChange={(e) => setName(e.target.value)}
      placeholder="Enter name..."
      aria-describedby="name-error"
    />
    {errors.name && (
      <p id="name-error" className="text-sm text-destructive">
        {errors.name}
      </p>
    )}
  </div>
</form>
```

### The `cn()` Utility

Combines `clsx` and `tailwind-merge`:

```tsx
import { cn } from '@/lib/utils';

// Conditional classes
<div className={cn(
  "px-4 py-2 rounded-md",
  isActive && "bg-primary text-white",
  !isActive && "bg-gray-100"
)}>

// Override default classes
<Button className={cn("w-full")}>
```

---

## Icons

**CRITICAL: Only use Lucide React. Never use emoji icons.**

```tsx
// CORRECT
import { Mail, Settings, Trash2, Plus, ChevronDown } from 'lucide-react';

<Button>
  <Mail className="w-4 h-4 mr-2" />
  Send Email
</Button>

// WRONG
<span>📧</span>  // Never use emojis
```

### Standard Sizes

| Context | Size | Example |
|---------|------|---------|
| Buttons | `w-4 h-4` | `<Plus className="w-4 h-4 mr-2" />` |
| Icon-only buttons | `w-4 h-4` | `<Button size="icon"><Trash2 className="w-4 h-4" /></Button>` |
| Headers | `w-5 h-5` | `<Settings className="w-5 h-5" />` |
| Large/hero | `w-6 h-6` | Empty states, illustrations |

### Common Icons

| Purpose | Icon |
|---------|------|
| Add/Create | `Plus` |
| Delete | `Trash2` |
| Edit | `Pencil` |
| Settings | `Settings` |
| Search | `Search` |
| Close | `X` |
| Expand | `ChevronDown` |
| Back | `ArrowLeft` |
| Success | `Check` |
| Error | `AlertCircle` |
| Info | `Info` |
| Warning | `AlertTriangle` |

---

## Component Patterns

### Page Layout

```tsx
export default function PageName() {
  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-foreground">
          Page Title
        </h1>
        <Button>
          <Plus className="w-4 h-4 mr-2" />
          Add New
        </Button>
      </div>

      {/* Content */}
      <div className="bg-card rounded-lg border border-border shadow-sm p-6">
        {/* ... */}
      </div>
    </div>
  );
}
```

### Card Pattern

```tsx
<div className="bg-card rounded-lg border border-border shadow-sm">
  <div className="p-4 border-b border-border">
    <h3 className="font-medium">Card Title</h3>
  </div>
  <div className="p-4">
    {/* Content */}
  </div>
  <div className="p-4 border-t border-border bg-muted/30">
    {/* Footer actions */}
  </div>
</div>
```

### Table Pattern

```tsx
<div className="rounded-lg border border-border bg-card overflow-hidden">
  <table className="w-full">
    <thead className="border-b border-border bg-muted/50">
      <tr>
        <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
          Name
        </th>
        <th className="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
          Actions
        </th>
      </tr>
    </thead>
    <tbody>
      {items.map(item => (
        <tr
          key={item.id}
          className="border-b last:border-0 hover:bg-muted/30 transition-colors"
        >
          <td className="px-4 py-3 text-sm">{item.name}</td>
          <td className="px-4 py-3 text-right">
            <Button variant="ghost" size="sm">
              <Pencil className="w-4 h-4" />
            </Button>
          </td>
        </tr>
      ))}
    </tbody>
  </table>
</div>
```

---

## UI State Patterns

### Loading State

```tsx
import { Skeleton } from '@/components/ui/skeleton';
import { Loader2 } from 'lucide-react';

// Skeleton loading
{isLoading ? (
  <div className="space-y-4">
    <Skeleton className="h-10 w-full" />
    <Skeleton className="h-10 w-full" />
    <Skeleton className="h-10 w-3/4" />
  </div>
) : (
  <ActualContent />
)}

// Button loading
<Button disabled={isLoading}>
  {isLoading && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
  {isLoading ? 'Saving...' : 'Save'}
</Button>
```

### Empty State

```tsx
import { FileQuestion } from 'lucide-react';

<div className="flex flex-col items-center justify-center py-16 text-center">
  <FileQuestion className="w-12 h-12 text-muted-foreground mb-4" />
  <h3 className="text-lg font-medium text-foreground mb-2">
    No Forms Found
  </h3>
  <p className="text-sm text-muted-foreground mb-6 max-w-sm">
    Get started by creating your first form.
  </p>
  <Button>
    <Plus className="w-4 h-4 mr-2" />
    Create Form
  </Button>
</div>
```

### Error State

```tsx
import { AlertCircle, RefreshCw } from 'lucide-react';

<div className="flex flex-col items-center justify-center py-16 text-center">
  <AlertCircle className="w-12 h-12 text-destructive mb-4" />
  <h3 className="text-lg font-medium text-foreground mb-2">
    Failed to Load Data
  </h3>
  <p className="text-sm text-muted-foreground mb-6 max-w-sm">
    {error.message || 'An unexpected error occurred.'}
  </p>
  <Button variant="outline" onClick={handleRetry}>
    <RefreshCw className="w-4 h-4 mr-2" />
    Try Again
  </Button>
</div>
```

### Success State

```tsx
import { CheckCircle } from 'lucide-react';

<div className="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg">
  <CheckCircle className="w-5 h-5 text-green-600 shrink-0" />
  <div>
    <p className="text-sm font-medium text-green-800">
      Form saved successfully
    </p>
    <p className="text-sm text-green-600">
      Your changes have been applied.
    </p>
  </div>
</div>
```

### Inline Notifications

```tsx
// Success inline
<div className="flex items-center gap-2 text-sm text-green-600">
  <Check className="w-4 h-4" />
  Saved
</div>

// Error inline (form field)
<p className="text-sm text-destructive mt-1">
  This field is required
</p>

// Warning inline
<div className="flex items-center gap-2 text-sm text-amber-600">
  <AlertTriangle className="w-4 h-4" />
  Unsaved changes
</div>
```

### Toast Notifications

If using a toast system:

```tsx
import { useToast } from '@/hooks/useToast';

const { toast } = useToast();

// Success
toast({
  title: 'Form saved',
  description: 'Your changes have been applied.',
});

// Error
toast({
  variant: 'destructive',
  title: 'Error',
  description: 'Failed to save form.',
});
```

---

## Accessibility

### Label Associations

Always associate labels with inputs:

```tsx
// CORRECT - htmlFor matches id
<Label htmlFor="email">Email</Label>
<Input id="email" type="email" />

// WRONG - no association
<Label>Email</Label>
<Input type="email" />
```

### Error Messages

Link errors to inputs with `aria-describedby`:

```tsx
<Input
  id="email"
  aria-invalid={!!errors.email}
  aria-describedby={errors.email ? 'email-error' : undefined}
/>
{errors.email && (
  <p id="email-error" className="text-sm text-destructive" role="alert">
    {errors.email}
  </p>
)}
```

### Focus Management

Visible focus rings are required:

```tsx
// Default Input already includes focus ring
<Input /> // Has focus-visible:ring-2

// Custom focusable elements
<div
  tabIndex={0}
  className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
>
```

### Keyboard Navigation

- All interactive elements must be keyboard accessible
- Dialogs trap focus and close on Escape (handled by Radix)
- Dropdowns navigate with arrow keys

```tsx
// Button with keyboard handler
<Button
  onClick={handleAction}
  onKeyDown={(e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      handleAction();
    }
  }}
>
```

### ARIA for Dialogs

shadcn/ui Dialog handles this automatically:

```tsx
<Dialog>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Title</DialogTitle>        {/* aria-labelledby */}
      <DialogDescription>Desc</DialogDescription> {/* aria-describedby */}
    </DialogHeader>
  </DialogContent>
</Dialog>
```

### Color Contrast

Minimum contrast ratios:
- Normal text: 4.5:1
- Large text (18px+): 3:1
- UI components: 3:1

The design tokens are pre-configured for WCAG AA compliance.

### Screen Reader Text

For icon-only buttons, add accessible labels:

```tsx
// Icon button with label
<Button size="icon" aria-label="Delete item">
  <Trash2 className="w-4 h-4" />
</Button>

// Or use sr-only text
<Button size="icon">
  <Trash2 className="w-4 h-4" />
  <span className="sr-only">Delete item</span>
</Button>
```

---

## Code Quality Guidelines

### TypeScript Strictness

- Enable strict mode in `tsconfig.json`
- Never use `any` - use `unknown` and narrow types
- Define interfaces for all data structures

```tsx
// WRONG
const handleData = (data: any) => { ... }

// CORRECT
interface FormData {
  id: number;
  name: string;
  status: 'draft' | 'published';
}
const handleData = (data: FormData) => { ... }
```

### Component Structure

Prefer functional components with hooks:

```tsx
// CORRECT - Functional component
export function FormCard({ form, onDelete }: FormCardProps) {
  const [isDeleting, setIsDeleting] = useState(false);
  // ...
}

// WRONG - Class component
class FormCard extends React.Component { ... }
```

### Event Handlers

Extract complex handlers, keep inline handlers simple:

```tsx
// CORRECT - Extracted handler
const handleSubmit = async (e: FormEvent) => {
  e.preventDefault();
  setIsLoading(true);
  try {
    await saveForm(formData);
    onSuccess();
  } catch (error) {
    setError(error.message);
  } finally {
    setIsLoading(false);
  }
};

<form onSubmit={handleSubmit}>

// OK - Simple inline handler
<Button onClick={() => setIsOpen(false)}>Close</Button>

// WRONG - Complex inline handler
<Button onClick={async () => {
  setIsLoading(true);
  try { await saveForm(data); } catch (e) { ... }
}}>
```

### Memoization

Use `useMemo` and `useCallback` for expensive operations:

```tsx
// Memoize expensive computations
const filteredForms = useMemo(
  () => forms.filter(f => f.name.includes(search)),
  [forms, search]
);

// Memoize callbacks passed to children
const handleDelete = useCallback(
  (id: number) => deleteMutation.mutate(id),
  [deleteMutation]
);

// Memoize components that receive callbacks
const MemoizedRow = React.memo(FormRow);
```

### File Organization

One component per file, co-locate related code:

```
components/forms/
├── FormCard.tsx        # Main component
├── FormCard.test.tsx   # Tests (if applicable)
├── useFormCard.ts      # Component-specific hook
└── types.ts            # Component-specific types
```

---

## Naming Conventions

### Files and Folders

| Type | Convention | Example |
|------|------------|---------|
| Components | PascalCase | `FormCard.tsx`, `SearchBar.tsx` |
| Hooks | camelCase with `use` | `useDebounce.ts`, `useFormState.ts` |
| Utilities | camelCase | `formatDate.ts`, `utils.ts` |
| Types | camelCase or PascalCase | `types.ts`, `FormTypes.ts` |
| Pages | kebab-case folder | `pages/forms-list/` |
| shadcn/ui | lowercase | `button.tsx`, `dialog.tsx` |

### Components

```tsx
// PascalCase for components
export function FormCard() { ... }
export function SearchBar() { ... }

// Props interface = ComponentNameProps
interface FormCardProps {
  form: Form;
  onDelete: (id: number) => void;
}
```

### Variables and Functions

```tsx
// camelCase for variables and functions
const formCount = forms.length;
const handleSubmit = () => { ... };

// Boolean prefixes: is, has, should, can
const isLoading = true;
const hasErrors = errors.length > 0;
const canDelete = user.permissions.includes('delete');

// Event handlers: handle + Event
const handleClick = () => { ... };
const handleSubmit = () => { ... };
const handleChange = () => { ... };
```

### When to Use Folders vs Single Files

| Use Folder When | Use Single File When |
|-----------------|----------------------|
| Component has sub-components | Component is self-contained |
| Component has hooks/utils | No related code |
| Component has tests | Small, simple component |
| Exports multiple items | Single export |

```
// Folder structure for complex component
components/automations/
├── index.ts              # Re-exports
├── AutomationsTab.tsx    # Main component
├── NodePicker.tsx        # Sub-component
├── useAutomations.ts     # Hook
└── types.ts              # Types

// Single file for simple component
components/ui/skeleton.tsx
```

---

## WordPress Integration

### PHP Side

```php
// Enqueue with wp-api-fetch dependency
wp_enqueue_script(
    'super-forms-admin',
    SUPER_PLUGIN_FILE . 'assets/js/backend/admin.js',
    array('wp-api-fetch'),
    SUPER_VERSION,
    true
);

// Enqueue styles
wp_enqueue_style(
    'super-admin',
    SUPER_PLUGIN_FILE . 'assets/css/backend/admin.css',
    array(),
    SUPER_VERSION
);

// Mount point
echo '<div id="sfui-admin-root"></div>';

// Pass data to React
$data = array(
    'forms' => $forms,
    'nonce' => wp_create_nonce('wp_rest'),
);
echo '<script>window.sfuiData = ' . wp_json_encode($data) . ';</script>';
```

### React Side

```tsx
// Type definition in types/global.d.ts
interface Window {
  sfuiData: {
    forms: Form[];
    formId?: number;
  };
  wp: {
    apiFetch: <T>(options: ApiFetchOptions) => Promise<T>;
  };
}

// Access data
const { forms } = window.sfuiData;

// REST API calls (authenticated automatically)
const response = await wp.apiFetch<Form[]>({
  path: '/super-forms/v1/forms',
});

await wp.apiFetch({
  path: `/super-forms/v1/forms/${id}`,
  method: 'DELETE',
});
```

---

## Build Commands

```bash
cd /home/rens/super-forms/src/react/admin

# Development
npm run watch              # Main admin bundle
npm run watch:forms-list   # Forms list page

# Production
npm run build              # All bundles

# Type checking
npm run typecheck
```

### Build Outputs

| Entry | Output |
|-------|--------|
| `index.tsx` | `assets/js/backend/admin.js` |
| `pages/forms-list/index.tsx` | `assets/js/backend/forms-list.js` |
| Styles | `assets/css/backend/admin.css` |

---

## Common Mistakes

### CSS

| Wrong | Right |
|-------|-------|
| `@import "tailwindcss"` | `@import "tailwindcss/theme"` + `@import "tailwindcss/utilities"` |
| Global resets | Resets inside `#sfui-admin-root { }` |
| Low z-index modals | z-index 100000+ |

### Tailwind

| Wrong | Right |
|-------|-------|
| `className="sfui:text-sm"` | `className="text-sm"` |
| Prefix on utilities | No prefix needed |

### Icons

| Wrong | Right |
|-------|-------|
| `<span>📧</span>` | `<Mail className="w-4 h-4" />` |
| Font Awesome | Lucide React only |

### TypeScript

| Wrong | Right |
|-------|-------|
| `data: any` | `data: FormData` |
| No interface | Define interfaces |

### API Calls

| Wrong | Right |
|-------|-------|
| Custom AJAX | `wp.apiFetch()` |
| Manual nonces | Automatic with wp-api-fetch |

### Accessibility

| Wrong | Right |
|-------|-------|
| `<Label>Email</Label><Input />` | `<Label htmlFor="email">Email</Label><Input id="email" />` |
| Icon button without label | `<Button aria-label="Delete">` |

---

## PR Checklist

Before submitting UI changes:

### Required

- [ ] `npm run build` passes
- [ ] `npm run typecheck` passes
- [ ] WordPress admin sidebar/top bar unaffected
- [ ] All icons are Lucide React (no emojis)
- [ ] Labels associated with inputs
- [ ] Focus states visible

### Screenshots

- [ ] Light mode screenshot
- [ ] Dark mode screenshot (if applicable)
- [ ] Mobile/responsive screenshot
- [ ] Before/after comparison (for modifications)

### Testing

- [ ] Keyboard navigation works
- [ ] Screen reader announces correctly
- [ ] Works in Chrome, Firefox, Safari
- [ ] Loading states display correctly
- [ ] Error states display correctly

---

## See Also

- **[CLAUDE.javascript.md](CLAUDE.javascript.md)** - React admin architecture, build system, TypeScript patterns
  - Lines 89-132: CSS Architecture details
  - Lines 278-461: UI Stack (Tailwind v4 + shadcn/ui + Lucide)
- **[CLAUDE.php.md](CLAUDE.php.md)** - WordPress integration, REST API patterns
  - Lines 1877-1980: React Admin Pages with REST API
