# JavaScript & React Development Guide

## React Admin UI (`/src/react/admin/`)

The React-based admin UI is built with TypeScript, Vite, Tailwind v4, and shadcn/ui.

### Directory Structure

```
/src/react/admin/
├── index.tsx                          # Main entry point (TypeScript, page routing)
├── package.json                       # super-forms-admin
├── tsconfig.json                      # TypeScript configuration
├── vite.config.ts                     # Build config (outputs admin.js/admin.css)
├── components.json                    # shadcn/ui configuration
├── types/
│   └── global.d.ts                    # TypeScript globals (window.sfuiData)
├── lib/
│   └── utils.ts                       # shadcn/ui utilities (cn helper)
├── styles/
│   └── index.css                      # Global styles (scoped to #sfui-admin-root)
└── components/
    ├── ui/                            # shadcn/ui components (when installed)
    ├── shared/                        # Shared components (future)
    └── form-builder/
        └── emails-tab/                # Email builder tab
            ├── App.tsx
            ├── hooks/                 # TypeScript hooks (.ts)
            ├── components/            # TypeScript components (.tsx)
            ├── capabilities/          # TypeScript modules (.ts)
            └── styles/
                └── index.css          # Feature-specific styles
```

### SFUI Admin Infrastructure (Phase 1 & 2)

**Mount Point and Namespace** (since Phase 2):

All React admin apps mount to a single DOM element and share a global data object:

**PHP Side** (`/src/includes/class-pages.php`):
```php
// Mount point: #sfui-admin-root (Phase 2 rename from #super-emails-root)
echo '<div id="sfui-admin-root"></div>';

// Data object: window.sfuiData (Phase 2 rename from window.superEmailsData)
<script>
  window.sfuiData = {
    currentPage: 'super_create_form',  // WP admin page identifier (Phase 2)
    formId: <?php echo $form_id; ?>,
    emails: <?php echo $emails_json; ?>,
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('super_save_form_emails'); ?>',
    restNonce: '<?php echo wp_create_nonce('wp_rest'); ?>',  // Phase 2
    currentUserEmail: '<?php echo wp_get_current_user()->user_email; ?>',
    i18n: { /* translations */ }
  };
</script>
```

**React Side** (`/src/react/admin/index.tsx`):
```tsx
// TypeScript definitions in types/global.d.ts
interface Window {
  sfuiData: SFUIData;
}

function initAdmin(): void {
  const rootElement = document.getElementById('sfui-admin-root');
  if (!rootElement || !window.sfuiData) return;

  // Page routing based on currentPage (Phase 2)
  switch (window.sfuiData.currentPage) {
    case 'super_create_form':
      initFormBuilderPage(rootElement);
      break;
    // Future pages: super_settings, super_entries, etc.
  }
}
```

**Why This Architecture:**
- Single mount point prevents multiple React roots competing for DOM
- Centralized data object follows WordPress patterns (like `wp.i18n`, `wp.ajax`)
- Page routing enables multiple admin pages using same bundle
- TypeScript definitions provide type safety for data object
- `restNonce` field enables REST API calls without additional nonce generation

### CSS Architecture

**Critical CSS Isolation Strategy** (since Phase 1):

The React admin CSS uses scoped resets to prevent Tailwind's preflight from breaking WordPress admin UI:

1. **Root mount point**: `#sfui-admin-root` - All React apps render here
2. **Scoped resets**: All CSS resets scoped to `#sfui-admin-root` selector
3. **No global preflight**: Import `tailwindcss/theme` and `tailwindcss/utilities` only (NOT `tailwindcss`)
4. **Z-index override**: Radix UI modals use `z-[100000]` to appear above WP admin bar (z-index 99999)

**CSS Structure** (`/src/react/admin/styles/index.css`):
```css
/* Tailwind v4 - theme and utilities only (no global preflight reset) */
@import "tailwindcss/theme";
@import "tailwindcss/utilities";

/* shadcn/ui theme variables on :root (safe - CSS only loaded on SF admin pages) */
:root {
  --z-overlay: 100000;  /* Above WP admin bar */
  --background: hsl(210 40% 98%);
  /* ... */
}

/* All resets scoped to #sfui-admin-root */
#sfui-admin-root {
  font-family: var(--font-sans);

  *, *::before, *::after {
    box-sizing: border-box;
    border-width: 0;
    /* ... */
  }

  /* Form element reset, button reset, etc. */
}
```

**Why This Matters:**
- WordPress admin uses its own styles for sidebar, top bar, notices
- Tailwind's default preflight (`@import "tailwindcss"`) resets ALL elements globally
- Scoping prevents breaking WP admin UI while allowing full Tailwind power inside React apps
- Z-index coordination ensures modals appear above WP admin bar

**Standard Tailwind Classes** (no prefix needed):
```tsx
<div className="h-full flex gap-4">
  <Button className="px-4 py-2">Click Me</Button>
</div>
```

### Element Identification with `data-testid` (AI/Testing)

**Convention**: Use `data-testid` attributes on key structural elements for:
- AI screenshot analysis (Playwright)
- E2E testing
- Visual debugging during development

**Key Rules**:
1. Write standard `data-testid="element-name"` in source code
2. Vite plugin automatically strips them in production builds
3. Debug overlay shows labels on hover (development only)

**Naming Convention**: Use kebab-case, be descriptive
- `data-testid="emails-tab"` - Main container
- `data-testid="email-list-sidebar"` - Left sidebar
- `data-testid="email-builder-main"` - Main content area
- `data-testid="add-email-btn"` - Interactive elements

**Example**:
```tsx
<div data-testid="email-builder-header" className="bg-white border-b">
  <button data-testid="add-email-btn" className="px-4 py-2">
    Add Email
  </button>
</div>
```

**Debug Overlay (Development)**:
- Hover any `[data-testid]` element to see a pink label with its name
- Dashed pink outline highlights the element boundaries
- Automatically hidden in production (no `data-testid` in build output)

**Vite Config** (`vite.config.ts`):
```typescript
import { defineConfig, Plugin } from 'vite';

// Custom plugin strips data-testid in production
function removeTestIdPlugin(): Plugin {
  return {
    name: 'remove-data-testid',
    enforce: 'pre',
    transform(code: string, id: string) {
      if (!id.match(/\.[jt]sx$/)) return null;
      const transformed = code
        .replace(/\s+data-testid=["'][^"']*["']/g, '')
        .replace(/\s+data-testid=\{[^}]*\}/g, '');
      if (transformed !== code) {
        return { code: transformed, map: null };
      }
      return null;
    },
  };
}

export default defineConfig(({ mode }) => ({
  plugins: [
    tailwindcss(),
    react(),
    mode === 'production' && process.env.STRIP_TESTID === '1' && removeTestIdPlugin(),
    moveCssPlugin(),
  ].filter(Boolean),
  resolve: {
    alias: {
      '@': resolve(__dirname, '.'),
      '@shared': resolve(__dirname, 'components/shared'),
    },
  },
  build: {
    rollupOptions: {
      input: resolve(__dirname, 'index.tsx'),
      output: {
        format: 'iife',
        name: 'SuperFormsAdmin',
        entryFileNames: 'admin.js',
      },
    },
  },
  // ...
}));
```

### Build Commands

```bash
cd /home/rens/super-forms/src/react/admin

# Development (with watch mode)
npm run watch              # Main admin bundle (form builder, emails, automations)
npm run watch:forms-list   # Forms list page bundle

# Production build (strips data-testid)
npm run build              # Builds all bundles

# Type checking (no build)
npm run typecheck

# Preview production build
npm run preview
```

**Build Outputs**:
- `/src/assets/js/backend/admin.js` (IIFE bundle - main admin UI)
- `/src/assets/js/backend/forms-list.js` (IIFE bundle - forms list page)
- `/src/assets/css/backend/admin.css` (Tailwind CSS - shared styles)

**Multi-Entry Build System:**

The Vite configuration supports building multiple entry points via the `ENTRY` environment variable:

```typescript
// vite.config.ts - Dynamic entry point configuration
rollupOptions: {
  input: resolve(__dirname, process.env.ENTRY || 'index.tsx'),
  output: {
    format: 'iife',
    name: process.env.ENTRY === 'pages/forms-list/index.tsx'
      ? 'SuperFormsFormsList'
      : 'SuperFormsAdmin',
    entryFileNames: process.env.ENTRY === 'pages/forms-list/index.tsx'
      ? 'forms-list.js'
      : 'admin.js',
  },
}
```

**Package.json Scripts:**
```json
{
  "scripts": {
    "build": "npm run build:admin && npm run build:forms-list",
    "build:admin": "vite build",
    "build:forms-list": "ENTRY=pages/forms-list/index.tsx vite build",
    "watch": "vite build --watch",
    "watch:forms-list": "ENTRY=pages/forms-list/index.tsx vite build --watch"
  }
}
```

### TypeScript Configuration

**See Also:** For comprehensive UI development guidelines, design tokens, component patterns, and accessibility standards, refer to **[docs/CLAUDE.ui.md](CLAUDE.ui.md)**.

**Tech Stack:**
- TypeScript 5.3+ for type safety
- Strict mode enabled (`strict: true`)
- Path aliases: `@/*` for project root, `@shared/*` for shared components
- Supports both `.ts`/`.tsx` and `.js`/`.jsx` files
- **Imports**: Always use ES6 `import` at top of file, never `require()` (browser bundles don't support CommonJS)

**tsconfig.json highlights:**
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "jsx": "react-jsx",
    "strict": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["./*"],
      "@shared/*": ["./components/shared/*"]
    },
    "allowJs": true  // Allows gradual migration from JS to TS
  }
}
```

**File Extensions:**
- `.tsx` - TypeScript React components
- `.ts` - TypeScript modules (hooks, utilities, types)
- `.jsx`/`.js` - Legacy JavaScript files (supported during migration)

**Type Checking:**
```bash
# Check types without building
npm run typecheck

# Watch mode for types
npm run typecheck -- --watch
```

### UI Stack: Tailwind v4 + shadcn/ui + Lucide Icons

**See Also:** For complete UI guidelines including design tokens, component patterns, accessibility standards, and common mistakes, refer to **[docs/CLAUDE.ui.md](CLAUDE.ui.md)**.

The React Admin UI uses a consistent component stack:

**Tailwind CSS v4** (utility-first styling):
```css
/* Configuration in styles/index.css */
@import "tailwindcss" prefix(sfui);

@theme {
  --color-primary-500: #3b82f6;
  --color-primary-600: #2563eb;
  /* Custom theme tokens */
}
```

**shadcn/ui** (Composable component library):
- Radix UI primitives with Tailwind styling
- Copy-paste component architecture (not NPM package)
- Full customization control
- Accessible by default
- Configured via `components.json`

**shadcn/ui Configuration** (`components.json`):
```json
{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "default",
  "tsx": true,
  "tailwind": {
    "css": "components/form-builder/emails-tab/styles/index.css",
    "baseColor": "slate",
    "cssVariables": true
  },
  "aliases": {
    "@/components": "@/components",
    "@/utils": "@/lib/utils"
  }
}
```

**Important: CSS Variable Scoping for shadcn/ui Components**

When using Tailwind v4's `@theme` with shadcn/ui, CSS variables must be defined on `:root` (not scoped to a class) for component classes like `bg-background`, `border-input` to resolve correctly. This is safe in this codebase because `admin.css` is only enqueued on Super Forms admin pages.

Pattern:
```css
/* Root styles - in styles/index.css */
:root {
  --color-background: #ffffff;
  --color-foreground: #000000;
  --color-input: #f0f0f0;
}

/* Component styles can reference these variables */
.sfui-card {
  background-color: var(--color-background);
}
```

**Button Customization:** For shadcn Button variants, prefer explicit Tailwind class references over complex CSS variable coordination. This provides more predictable styling across components.

**Installing shadcn/ui Components:**
```bash
cd /home/rens/super-forms/src/react/admin

# Install individual components (copies code to project)
npx shadcn@latest add button
npx shadcn@latest add dialog
npx shadcn@latest add dropdown-menu

# Components installed to: ./components/ui/
```

**Utility Helper** (`lib/utils.ts`):
```typescript
import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

// Combines clsx + tailwind-merge for className merging
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}
```

**Lucide React** (icon library):
- Consistent icon set across all UI
- Tree-shakeable imports
- Standard sizing: `sfui:w-4 sfui:h-4` (small), `sfui:w-5 sfui:h-5` (medium)

```tsx
import { Mail, Settings, ChevronDown } from 'lucide-react';

<Mail className="sfui:w-4 sfui:h-4 sfui:text-gray-500" />
```

**Component Patterns**:

```tsx
// Using shadcn/ui Button component
import { Button } from "@/components/ui/button"

<Button variant="default" size="sm">
  <Mail className="sfui:w-4 sfui:h-4 sfui:mr-2" />
  Send Email
</Button>

// Card container (Tailwind)
<div className="sfui:bg-white sfui:rounded-lg sfui:border sfui:border-gray-200 sfui:shadow-sm sfui:p-4">
  {/* Card content */}
</div>

// Using cn() utility for conditional classes
import { cn } from "@/lib/utils"

<button
  className={cn(
    "sfui:py-2 sfui:px-4 sfui:rounded-md",
    enabled ? "sfui:bg-blue-600" : "sfui:bg-gray-300"
  )}
>
  Toggle
</button>
```

**Dependencies** (package.json):
```json
{
  "dependencies": {
    "@radix-ui/react-slot": "^1.2.4",
    "class-variance-authority": "^0.7.0",
    "clsx": "^2.1.0",
    "lucide-react": "^0.525.0",
    "tailwind-merge": "^3.4.0"
  },
  "devDependencies": {
    "@tailwindcss/vite": "^4.0.0",
    "@types/react": "^18.2.46",
    "@types/react-dom": "^18.2.18",
    "tailwindcss": "^4.0.0",
    "typescript": "^5.3.3"
  }
}
```

**Why shadcn/ui vs Preline:**
- TypeScript-first (better type safety)
- Component composition pattern (more flexible)
- No runtime JS initialization required
- Full source code ownership (copy-paste, not dependency)
- Active community and frequent updates

---

## Email Builder Components

### Location & Architecture (Since Phase 11.3)

The email builder is now integrated into the admin bundle as reusable components:

**Location:** `/src/react/admin/components/email-builder/`

**Exports** (`email-builder/index.js`):
- `EmailBuilderIntegrated` - Full email builder with Gmail-style chrome
- `EmailClientBuilder` - Email client preview with mode toggle
- `useEmailBuilder` - Zustand store for email builder state
- `useEmailStore` - Store for email list management
- `generateHtmlFromElements` - Template generation utility
- Additional components: Canvas, ElementPalette, PropertyPanels, etc.

**Integration Pattern:**
```tsx
// Import from email-builder (within admin bundle)
import { EmailBuilderIntegrated } from '@/components/email-builder';
import { SendEmailModal } from '@/components/form-builder/automations/modals/SendEmailModal';

// Use in workflow/trigger modals
<SendEmailModal
  node={node}
  onUpdateNode={handleUpdate}
/>
```

**Build Output:**
- Single unified bundle: `/src/assets/js/backend/admin.js` (799KB)
- Includes email builder + form builder components
- Replaced dual-build architecture (emails-v2 + admin)

### Legacy Email Builder v2 [DEPRECATED]

> **DEPRECATED AS OF v6.5.0**: The standalone `/src/react/emails-v2/` directory has been deleted.
> All email builder components moved to `/src/react/admin/components/email-builder/`.
> Build outputs `emails-v2.js/css` no longer exist.
> PHP enqueues changed from `super-emails-v2` to `super-admin`.
> The documentation below is for historical reference only.

**Old Structure** (removed in Phase 11.3):
```
/src/react/emails-v2/  (DELETED)
├── package.json       (separate webpack build)
├── src/
│   ├── components/    (~70 React components)
│   ├── hooks/         (useEmailBuilder, useEmailStore)
│   └── styles/
```

**Migration Notes:**
- 70+ components moved to `/src/react/admin/components/email-builder/`
- Import paths changed: removed `sfui:` prefix, updated to use `@/` alias
- Webpack replaced with Vite build system
- Separate npm install no longer needed

**Current Development Workflow:**

```bash
# Navigate to admin bundle
cd /home/rens/super-forms/src/react/admin

# Development mode with watch (recommended)
npm run watch

# Production build (for releases only)
npm run build

# Type checking
npm run typecheck
```

**Build outputs:**
- `/src/assets/js/backend/admin.js` - Unified admin bundle
- `/src/assets/css/backend/admin.css` - Tailwind CSS

**Development Tools:**
- React DevTools (Components & Profiler tabs)
- TypeScript type checking (`npm run typecheck`)
- Source maps enabled in development mode
- Hot reload on file changes

### Visual/HTML Mode Toggle

**Mode System (since 6.5.0):**
The Email v2 builder supports two editing modes for maximum flexibility:

**Visual Mode** (`body_type: 'visual'`):
- Drag-drop email builder with reusable elements (Text, Button, Image, Divider, etc.)
- Live preview in Gmail/Outlook/Apple Mail chrome
- Element-based composition system
- Template stored as JSON with elements array
- Default mode for new emails

**HTML Mode** (`body_type: 'html'`):
- Raw HTML code editor with syntax highlighting
- Full control over email markup
- Live preview panel (optional toggle)
- Direct HTML editing without element abstraction
- Useful for importing existing HTML templates or advanced customization

**Mode Switching:**
```javascript
// Visual → HTML conversion
const html = generateHtml(); // Converts elements to HTML
updateEmailField(emailId, 'body', html);
updateEmailField(emailId, 'body_type', 'html');

// HTML → Visual conversion
const htmlElement = {
  id: uuidv4(),
  type: 'html',
  props: { content: currentBody },
  children: []
};
setElements([htmlElement]); // Wraps HTML in HtmlElement component
updateEmailField(emailId, 'body_type', 'visual');
```

**Mode Persistence:**
- Mode preference stored in localStorage: `emailBuilderMode_{emailId}`
- Prevents accidental data loss via confirmation dialogs
- Visual elements preserved when switching to HTML mode
- HTML content wrapped in editable HtmlElement when switching to Visual

**UI Components:**
- `EmailClientBuilder.jsx` - Main orchestrator, manages mode state and conversions
- `GmailChrome.jsx` - Chrome preview with Visual/HTML toggle buttons (Palette/Code icons)
- `HtmlElement.jsx` - Custom element type for raw HTML blocks within visual builder
- `InlineHtmlEditor` - Textarea-based HTML editor (embedded in GmailChrome body area)

**Component File Locations (Current as of Phase 11.3):**
- Main builder: `/src/react/admin/components/email-builder/Preview/EmailClientBuilder.jsx`
- Chrome UI: `/src/react/admin/components/email-builder/Preview/ClientChrome/GmailChrome.jsx`
- HTML element: `/src/react/admin/components/email-builder/Builder/Elements/HtmlElement.jsx`
- Element renderer: `/src/react/admin/components/email-builder/Builder/Elements/ElementRenderer.jsx`
- Element palette: `/src/react/admin/components/email-builder/Builder/ElementPaletteHorizontal.jsx`

### Email Builder Integration Patterns

**Standalone Usage (Email v2 Tab):**
```tsx
import { EmailList } from '@/components/email-builder';

// Full email management UI
<EmailList formId={formId} />
```

**Workflow Integration (Send Email Action):**
```tsx
import { SendEmailModal } from '@/components/form-builder/automations/modals/SendEmailModal';

// Modal with embedded email builder
<SendEmailModal
  isOpen={isOpen}
  onClose={handleClose}
  node={workflowNode}
  onUpdateNode={handleNodeUpdate}
/>
```

**Custom Integration:**
```tsx
import {
  EmailBuilderIntegrated,
  useEmailBuilder,
  generateHtmlFromElements
} from '@/components/email-builder';

// Direct builder access for custom UIs
const { elements, updateElement } = useEmailBuilder();
```

### Email v2 ↔ Automations Backend Integration

**Data Flow (since 6.5.0):**
The Email v2 React app stores email data in `_emails` postmeta, which automatically syncs to the automations system via `SUPER_Email_Automation_Migration`:

- **Save**: React app saves to `_emails` → `save_form_emails_settings()` → `sync_emails_to_automations()` → automations table
- **Load**: React app loads from `_emails` ← `get_form_emails_settings()` ← `get_emails_for_ui()` ← automations table (if `_emails` empty)

**Email Body Types Synced:**
- `visual` - Visual builder JSON (elements array + generated HTML)
- `html` - Raw HTML content from HTML mode editor
- `email_v2` - Legacy identifier (treated same as `visual`)
- `legacy_html` - Migrated from old Admin/Confirmation email settings

**Key Points:**
- Email v2 UI is unaware of automations system (facade pattern)
- Each email becomes a `send_email` action on `form.submitted` event
- Sync maintains `_super_email_automations` postmeta mapping (email_id → automation_id)
- Changes in Email v2 UI automatically update automation configurations
- Migrated legacy emails appear in Email v2 tab via reverse sync
- `body_type` field determines rendering method in `send_email` action

**Implementation Files:**
- Backend sync: `/src/includes/class-email-automation-migration.php`
- Integration hooks: `/src/includes/class-common.php` lines 121-156
- React app storage: Stores in `_emails` postmeta (sync transparent to React code)
- Action renderer: `/src/includes/automations/actions/class-action-send-email.php` (handles all body types)

## Vanilla JavaScript Components (Frontend)

### Session Manager (Progressive Form Saving)

**Location:** `/src/assets/js/frontend/session-manager.js`

**Architecture:**
- **Zero Dependencies**: Pure vanilla JavaScript (no jQuery, no libraries)
- **Modern APIs**: Uses `fetch()`, `AbortController`, `crypto.randomUUID()`, localStorage
- **Diff-Tracking**: Sends only changed fields to server (bandwidth efficient)
- **Event-Driven**: Custom events for integration (`super:session:restored`, `super:form:submitted`)

**Key Features:**
1. **Automatic Session Creation**: First field focus triggers session creation
2. **Debounced Auto-Save**: 500ms debounce on blur/change events
3. **Request Cancellation**: AbortController cancels in-flight requests when user types fast
4. **Session Recovery**: Shows recovery banner on form load if unsaved data exists
5. **Client Token**: UUID v4 stored in localStorage for anonymous session identification

**Performance Patterns:**
```javascript
// Diff-only updates (bandwidth efficient)
var changes = {};
for (key in currentData) {
    if (state.lastSavedData[key] !== currentData[key]) {
        changes[key] = currentData[key];
    }
}

// AbortController pattern (prevent race conditions)
if (state.abortController) {
    state.abortController.abort(); // Cancel previous
}
state.abortController = new AbortController();
fetch(url, { signal: state.abortController.signal });
```

**Integration Points:**
- Enqueued in: `/src/super-forms.php` (lines 2266-2276)
- AJAX handlers: `/src/includes/class-ajax.php` (lines 8176-8475)
- Dependencies: None (runs standalone)
- Global object: `window.SUPER_SessionManager`

**Browser Compatibility:**
- Modern browsers: Uses `crypto.randomUUID()`
- Legacy fallback: Custom UUID generator for older browsers
- IE11: Not supported (uses `fetch`, `AbortController`, arrow functions)

**Development Notes:**
- No build process required (direct source file)
- Browser console shows debug logs: `[Super Forms] Session save failed:`
- Custom events fire for lifecycle hooks
- Graceful degradation if AJAX fails (form still works)

## Form Version History Component

**Location:** `/src/react/admin/components/VersionHistory.tsx` (283 lines)

Git-like version control UI for viewing and reverting form versions.

**Features:**
- Version list with metadata (version number, timestamp, creator, commit message)
- Operation count display (number of changes in each version)
- Revert confirmation dialog
- Current version badge
- Relative timestamps ("2 hours ago")
- Empty state for new forms

**Props Interface:**
```typescript
interface VersionHistoryProps {
  formId: number;                       // Form to show versions for
  onRevert?: (version: Version) => void; // Callback after revert
  className?: string;                   // Optional container classes
}
```

**Version Data Structure:**
```typescript
interface Version {
  id: number;
  form_id: number;
  version_number: number;
  snapshot: any;                        // Full form state
  operations: any[] | null;             // Operations since last version
  created_by: number;
  created_at: string;                   // ISO timestamp
  message: string | null;               // Optional commit message
}
```

**REST API Integration:**
```typescript
// Load versions
GET /wp-json/super-forms/v1/forms/${formId}/versions?limit=20

// Revert to version
POST /wp-json/super-forms/v1/forms/${formId}/revert/${versionId}
```

**UI Components Used:**
- shadcn/ui: `Card`, `Button`, `Dialog`, `Badge`, `ScrollArea`
- Lucide icons: `Clock`, `GitBranch`, `RotateCcw`, `Save`, `User`

**Usage Example:**
```tsx
import { VersionHistory } from '@/components/VersionHistory';

<VersionHistory
  formId={123}
  onRevert={(version) => {
    console.log('Reverted to version', version.version_number);
    // Reload form data
  }}
  className="mt-4"
/>
```

**Revert Safety:**
- Confirmation dialog before reverting
- Explains that current state is saved as new version before revert
- No data loss (all versions preserved)
- Automatic version list reload after revert

**Implementation Notes:**
- Uses WordPress REST API nonce from `window.wpApiSettings.nonce`
- Loading and error states with user-friendly messages
- ScrollArea limits height to 400px with scrolling
- Current version (index 0) shows "Current" badge and no revert button

## Forms List Page (React + Tailwind CSS)

The forms management page provides a modern UI for viewing, searching, and managing forms. Built with TypeScript + React + Tailwind CSS.

**Location:** `/src/react/admin/pages/forms-list/`

### Architecture

**Standalone Page Pattern:**
- Separate entry point from main admin bundle
- Independent build: `forms-list.js` (lighter weight)
- Loads faster than bundling with form builder
- Reuses shared Tailwind CSS styles

**File Structure:**
```
pages/forms-list/
├── index.tsx           # Entry point, router
└── FormsList.tsx       # Main component (table, filters, search)
```

**Data Flow:**
1. PHP wrapper (`page-forms-list-react.php`) fetches initial data via `SUPER_Form_DAL`
2. Passes data to React via `window.sfuiData`
3. React renders table with Tailwind CSS + shadcn/ui components
4. All user actions handled via WordPress REST API using `wp.apiFetch()`

**Key Features:**
- Real-time search filtering (client-side)
- Status tabs with counts (All/Published/Draft/Archived)
- Bulk actions via REST API (delete, archive, restore)
- Single form actions via REST API (duplicate, archive/restore, delete)
- Entry count display per form
- Shortcode copy-to-clipboard
- Responsive design with Tailwind CSS

**Performance Optimizations:**
- Uses `SUPER_Form_DAL::count()` for status counts (60-70% faster)
- Single bulk query for entry counts (eliminates N+1 problem)
- Client-side search filtering (no page reloads)
- Separate bundle reduces initial load time vs main admin.js

**Integration with DAL:**
```php
// PHP side - Optimized data fetching (page-forms-list-react.php)
$status_counts = array(
    'all'      => SUPER_Form_DAL::count(),
    'publish'  => SUPER_Form_DAL::count(array('status' => 'publish')),
    'draft'    => SUPER_Form_DAL::count(array('status' => 'draft')),
    'archived' => SUPER_Form_DAL::count(array('status' => 'archived')),
);

// Get forms with entry counts (single query with GROUP BY)
$forms = SUPER_Form_DAL::query($query_args);
$entry_counts = $wpdb->get_results(
    "SELECT form_id, COUNT(*) as count
     FROM {$wpdb->prefix}superforms_entries
     WHERE form_id IN (...) GROUP BY form_id"
);
```

**React Component:**
```tsx
// FormsList.tsx - Client-side filtering
const filteredForms = forms.filter(form => {
  if (searchQuery) {
    return form.name.toLowerCase().includes(searchQuery.toLowerCase());
  }
  return true;
});
```

### WordPress REST API Integration

The forms list page uses `wp.apiFetch()` for all form operations instead of custom AJAX handlers.

**Setup Requirements:**
```php
// In page-forms-list-react.php - Enqueue with wp-api-fetch dependency
wp_enqueue_script(
    'super-forms-list',
    SUPER_PLUGIN_FILE . 'assets/js/backend/forms-list.js',
    array('wp-api-fetch'),  // Critical: Required for wp.apiFetch() global
    SUPER_VERSION,
    true
);

// Data passed to React (no ajaxUrl or nonce needed)
$react_data = array(
    'forms'         => $forms_data,
    'statusCounts'  => $status_counts,
    'currentStatus' => $current_status,
    'searchQuery'   => $search_query,
);
```

**REST API Endpoints Used:**
```typescript
// Bulk operations (delete, archive, restore)
await wp.apiFetch({
  path: '/super-forms/v1/forms/bulk',
  method: 'POST',
  data: {
    operation: 'delete',
    form_ids: [1, 2, 3]
  }
});

// Delete single form
await wp.apiFetch({
  path: `/super-forms/v1/forms/${formId}`,
  method: 'DELETE'
});

// Duplicate form
await wp.apiFetch({
  path: `/super-forms/v1/forms/${formId}/duplicate`,
  method: 'POST'
});

// Archive/restore (via bulk endpoint)
await wp.apiFetch({
  path: '/super-forms/v1/forms/bulk',
  method: 'POST',
  data: {
    operation: 'archive', // or 'restore'
    form_ids: [formId]
  }
});
```

**Authentication & Security:**
- WordPress REST API handles authentication automatically via cookies
- CSRF protection automatic via REST API nonce system
- No manual nonce verification required in JavaScript
- `wp-api-fetch` dependency handles all security headers

**Benefits vs Custom AJAX:**
- Removed 90+ lines of custom AJAX handler code
- WordPress standard authentication flow
- Automatic CSRF protection
- Better error handling via REST API response format
- Consistent with WordPress admin patterns

**Implementation Pattern (FormsList.tsx):**
```tsx
// Global type definition
declare const wp: {
  apiFetch: (options: {
    path: string;
    method?: string;
    data?: any;
  }) => Promise<any>;
};

// Handle bulk action
const handleBulkAction = async (action: string) => {
  setIsLoading(true);

  try {
    await wp.apiFetch({
      path: '/super-forms/v1/forms/bulk',
      method: 'POST',
      data: {
        operation: action,
        form_ids: Array.from(selectedForms),
      },
    });

    window.location.reload(); // Reload to refresh data
  } catch (error) {
    console.error('Bulk action error:', error);
    alert('An error occurred. Please try again.');
  } finally {
    setIsLoading(false);
  }
};
```

## Visual Workflow Builder (Automations Tab)

The automations tab features a custom-built visual workflow editor for creating node-based automation flows. Built with TypeScript + React, based on ai-automation architecture.

**Location:** `/src/react/admin/components/form-builder/automations/`

### Core Architecture

**State Management:** Custom `useNodeEditor` hook with pure React state (no Redux/Zustand)
- TypeScript-first with full type definitions in `types/workflow.types.ts`
- useState + useCallback pattern for performance
- useRef for counters and drag state to prevent feedback loops

**File Structure:**
```
automations/
├── canvas/
│   ├── Canvas.tsx                    # Main canvas with pan/zoom/drag
│   ├── Node.tsx                      # Individual node rendering
│   ├── ConnectionOverlay.tsx         # SVG connection rendering
│   └── GroupContainer.tsx            # Visual group containers
├── hooks/
│   └── useNodeEditor.ts              # Core state management (700+ lines)
├── types/
│   └── workflow.types.ts             # TypeScript definitions
└── data/
    └── superFormsNodeTypes.ts        # Node type registry
```

### GroupContainer Component

Visual container for organizing related nodes into logical groups.

**File:** `/src/react/admin/components/form-builder/automations/canvas/GroupContainer.tsx` (393 lines)

**Features:**
- **Drag to Move**: Drag header (⋮⋮ icon) to move group + all contained nodes simultaneously
- **Resize Handles**: Bottom-left and bottom-right corner handles for resizing bounds
- **Auto-Membership**: Resizing group automatically updates `nodeIds` array based on nodes within bounds
- **Editable Name**: Click name to edit inline, press Enter or blur to save
- **Delete Button**: × button removes group (preserves nodes on canvas)
- **Visual Feedback**: Dashed border (2px), background tint (rgba blue 5% opacity), hover effects
- **Z-Index Management**: Groups render at z-index -1 (behind nodes and connections)

**Props Interface:**
```typescript
interface GroupContainerProps {
  group: WorkflowGroup;              // { id, name, nodeIds, bounds, color, zIndex }
  viewport: Viewport;                // { x, y, zoom } for coordinate transforms
  nodes: WorkflowNode[];             // All nodes (for membership detection)
  isAnyNodeDragging?: boolean;       // Disables transitions during drag
  onUpdateGroup: (groupId: string, updates: Partial<WorkflowGroup>) => void;
  onRemoveGroup: (groupId: string) => void;
  onMoveGroup: (groupId: string, deltaX: number, deltaY: number, phase: 'start' | 'move' | 'end') => void;
}
```

**Key Implementation Details:**
- **Offset-Based Dragging**: Visual offset applied during drag, committed on mouseup (prevents state feedback loops)
- **Grid Snapping**: All movements snap to 20px grid
- **Resize Logic**: Handles both bottom-left (width + x change) and bottom-right (width + height) resize
- **Hover State**: Header controls (drag handle, name input, delete button) appear on hover
- **Event Propagation**: stopPropagation() on header elements to prevent canvas pan during interaction

### ConnectionOverlay Component

SVG overlay for rendering connections with electric animations and interactive features.

**File:** `/src/react/admin/components/form-builder/automations/canvas/ConnectionOverlay.tsx` (446 lines)

**Visual Enhancements:**

1. **Electric Light Animations**
   - Animated light source traveling along connection path (2s loop)
   - Radial gradient: white center → light blue → blue → transparent
   - Triple-layer glow filter (3px, 6px, 12px Gaussian blur)
   - Inner bright core (5px × 1.5px) + outer glow (10px × 3px)
   - Pulsing opacity animation (0.6-1.0, 0.4s duration)

2. **Connection Labels**
   - Output port name displayed at connection midpoint
   - Gray (#9ca3af) default, red (#ef4444) on hover
   - 10px font, centered above path

3. **Delete Hints**
   - "Click to delete" message appears below connection on hover
   - Red text, 9px font, 500 weight
   - Positioned 12px below connection midpoint

**Hover Interaction:**
- Invisible 20px stroke-width path for easy clicking
- Visual path changes to red with enhanced glow filter
- Arrow marker changes to red variant (url(#arrowhead-hovered))
- Label and delete hint become visible

**Connection Preview (during drag):**
- Dashed green line (#10b981) follows mouse cursor
- Snaps to nearby input ports within 30px radius
- Green arrow marker variant
- Pulse animation for visual feedback

**SVG Filters:**
```xml
<filter id="electric-glow">
  <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
  <feGaussianBlur stdDeviation="6" result="outerGlow"/>
  <feGaussianBlur stdDeviation="12" result="farGlow"/>
  <feMerge>
    <feMergeNode in="farGlow"/>
    <feMergeNode in="outerGlow"/>
    <feMergeNode in="coloredBlur"/>
    <feMergeNode in="SourceGraphic"/>
  </feMerge>
</filter>
```

**Performance:**
- `useMemo` for connection path calculations
- Memoized port position calculations
- Only re-renders when connections/nodes/viewport change

### Canvas Component

Main canvas area with viewport controls, rendering layers, and interaction handling.

**File:** `/src/react/admin/components/form-builder/automations/canvas/Canvas.tsx` (480 lines)

**Rendering Layers (z-order bottom to top):**
1. **Canvas Background** - Dot grid pattern (20px, scales with zoom)
2. **Nodes Layer** (transformed) - Contains groups, connections, nodes
   - Groups (z-index: -1) - Dashed containers behind everything
   - ConnectionOverlay (z-index: -1) - Paths above groups, below nodes
   - Nodes (z-index: 1+) - Individual workflow nodes on top
3. **UI Overlays** - Selection rectangle, connection mode indicator, zoom controls

**Dot Grid Background:**
```css
background-image: radial-gradient(
  circle at 1px 1px,
  rgba(156, 163, 175, 0.4) 1px,
  transparent 0
);
background-size: calc(20px * zoom) calc(20px * zoom);
background-position: calc(viewport.x) calc(viewport.y);
```

**Drag States:**
- `node`: Dragging one or more selected nodes
- `viewport`: Panning canvas (no modifier keys)
- `selection`: Ctrl+drag selection rectangle

**Coordinate Systems:**
- **Screen Coordinates**: Mouse position in browser viewport
- **Canvas Coordinates**: Position in workflow coordinate space
- **Conversion**: `(screenX - viewport.x) / viewport.zoom = canvasX`

**Pure Math Approach (prevents feedback loops):**
- Store initial state in refs at drag start (`dragStartRef`)
- Calculate total delta from initial position (not incremental)
- Apply absolute positions to avoid accumulating errors

**Zoom Behavior:**
- Mouse wheel: +/- zoom factor (0.95 / 1.05)
- Zoom towards mouse position (updates viewport.x/y to keep point under cursor)
- Clamp zoom: 0.1 - 3.0 range
- Zoom controls: +/− buttons and % display in bottom-right corner

**Group Integration:**
```typescript
{groups.map(group => (
  <GroupContainer
    key={group.id}
    group={group}
    viewport={viewport}
    nodes={nodes}
    isAnyNodeDragging={dragState?.type === 'node'}
    onUpdateGroup={onUpdateGroup || (() => {})}
    onRemoveGroup={onRemoveGroup || (() => {})}
    onMoveGroup={onMoveGroup || (() => {})}
  />
))}
```

### Node Component

Individual workflow node rendering with ports, status, and configuration preview.

**File:** `/src/react/admin/components/form-builder/automations/canvas/Node.tsx` (189 lines)

**Visual Improvements:**

1. **Conditional Transitions**
   ```typescript
   const transitionStyle = isDragging ? '' : 'transition-all duration-200';
   ```
   - Disabled during drag for immediate feedback (no lag)
   - Enabled otherwise for smooth hover/selection animations
   - Prevents jittery movement from transition conflicts

2. **Enhanced Port Visibility**
   - **Input Ports**: Scale 1.3x + blue glow when connection is being created
   - **Output Ports**: Hover animation (1.0 → 1.3) + colored shadow
   - **Invisible Hit Areas**: 6×6px transparent zones for easier clicking

3. **Status Indicator**
   - Green pulsing dot (2px) in top-right corner
   - Indicates node is active/ready
   - Uses CSS animate-pulse utility

**Node Structure:**
```tsx
<div data-node-id={node.id} className="...">
  {/* Header: Icon + Name */}
  <div className="flex items-center gap-2 p-3 border-b">
    <Icon className="w-5 h-5" style={{ color: nodeType.color }} />
    <span className="font-medium text-sm">{nodeType.name}</span>
  </div>

  {/* Body: Config Preview (first 2 properties) */}
  <div className="p-3 text-xs text-gray-600">
    {Object.entries(node.config).slice(0, 2).map(...)}
  </div>

  {/* Input Ports (left side) */}
  {/* Output Ports (right side) */}
  {/* Category Badge (top-right) */}
  {/* Status Indicator (top-right) */}
</div>
```

**Port Positioning:**
- Left edge: Input ports at `translate(-50%, -50%)`
- Right edge: Output ports at `translate(50%, -50%)`
- Vertical spacing: 20px per port (supports multiple ports)
- Z-index: 100 (above node body)

### useNodeEditor Hook

Core state management hook with group operations.

**File:** `/src/react/admin/components/form-builder/automations/hooks/useNodeEditor.ts` (700+ lines)

**Group Management Functions:**

```typescript
// Add new group
const addGroup = useCallback((
  name: string,
  bounds: { x: number; y: number; width: number; height: number },
  nodeIds: string[] = []
) => {
  const newGroup: WorkflowGroup = {
    id: `group-${groupCounter.current++}`,
    name,
    nodeIds,
    bounds,
    color: 'rgba(59, 130, 246, 0.3)',
    zIndex: -1
  };
  saveHistory();
  setGroups(prev => [...prev, newGroup]);
  return newGroup.id;
}, [saveHistory]);

// Update group properties
const updateGroup = useCallback((groupId: string, updates: Partial<WorkflowGroup>) => {
  saveHistory();
  setGroups(prev =>
    prev.map(g => g.id === groupId ? { ...g, ...updates } : g)
  );
}, [saveHistory]);

// Delete group (preserves nodes)
const removeGroup = useCallback((groupId: string) => {
  saveHistory();
  setGroups(prev => prev.filter(g => g.id !== groupId));
}, [saveHistory]);

// Move group and all contained nodes
const moveGroup = useCallback((
  groupId: string,
  deltaX: number,
  deltaY: number,
  phase: 'start' | 'move' | 'end'
) => {
  const group = groups.find(g => g.id === groupId);
  if (!group) return;

  if (phase === 'start') {
    // Mark nodes as being group-dragged
    setNodes(prev => prev.map(node =>
      group.nodeIds.includes(node.id)
        ? { ...node, isGroupDragging: true }
        : node
    ));
  } else if (phase === 'move') {
    // Move nodes during drag (visual feedback)
    setNodes(prev => prev.map(node =>
      group.nodeIds.includes(node.id)
        ? { ...node, position: { x: node.position.x + deltaX, y: node.position.y + deltaY } }
        : node
    ));
  } else if (phase === 'end') {
    // Commit final positions
    saveHistory();
    setNodes(prev => prev.map(node =>
      group.nodeIds.includes(node.id)
        ? { ...node, isGroupDragging: false }
        : node
    ));
  }
}, [groups, saveHistory]);

// Create group from selected nodes
const createGroupFromSelection = useCallback(() => {
  if (selectedNodes.length === 0) return null;

  // Calculate bounding box for selected nodes
  const selectedNodeObjects = nodes.filter(n => selectedNodes.includes(n.id));
  const xs = selectedNodeObjects.map(n => n.position.x);
  const ys = selectedNodeObjects.map(n => n.position.y);

  const bounds = {
    x: Math.min(...xs) - 20,
    y: Math.min(...ys) - 40,
    width: Math.max(...xs) - Math.min(...xs) + 240,
    height: Math.max(...ys) - Math.min(...ys) + 140
  };

  return addGroup(`Group ${groupCounter.current}`, bounds, selectedNodes);
}, [selectedNodes, nodes, addGroup]);
```

**State Structure:**
```typescript
interface WorkflowGroup {
  id: string;
  name: string;
  nodeIds: string[];        // Nodes contained in this group
  bounds: {
    x: number;
    y: number;
    width: number;
    height: number;
  };
  color?: string;           // Optional custom border/background color
  zIndex: number;           // Render order (typically -1 for behind nodes)
}
```

**Exported API:**
```typescript
return {
  // ... existing node/connection operations
  groups,
  addGroup,
  updateGroup,
  removeGroup,
  moveGroup,
  createGroupFromSelection,
};
```

## UI Component Guidelines

### Icons - CRITICAL RULES

- **ONLY use Lucide React icons** for ALL UI components - NO EXCEPTIONS
- **NEVER use emoji icons (❌ 📧 🔔 📅 etc.)** - ALWAYS replace with Lucide icons
- **NEVER use custom SVG icons** - always find appropriate Lucide icon
- Import from `lucide-react`: `import { IconName } from 'lucide-react'`
- Standard icon sizing: `className="ev2-w-4 ev2-h-4"` for buttons, `ev2-w-5 ev2-h-5` for larger elements
- Examples: `<Mail />`, `<Bell />`, `<Calendar />`, `<Settings />`, etc.
- Documentation, examples, and help text MUST use Lucide icons, NOT emojis
- If tempted to use an emoji, STOP and find the appropriate Lucide icon instead

### UI Consistency

- Use Tailwind CSS with `ev2-` prefix for all styling
- Follow existing component patterns and naming conventions
- Maintain consistent spacing, colors, and interaction patterns
- Always use Lucide icons for visual elements in the UI - no emoji icons anywhere

## Automated Code Quality Hooks

### React/JavaScript File Changes - MANDATORY VALIDATION

After ANY change to React/JavaScript files (`.js`, `.jsx`, `.ts`, `.tsx`):

#### HOOK 1: Build Validation (MANDATORY)

```bash
# React Admin UI (current)
cd /home/rens/super-forms/src/react/admin && npm run build

# Legacy emails-v2 (deprecated)
cd /projects/super-forms/src/react/emails-v2 && npm run build
```
- **FAIL** → Fix syntax errors immediately, re-run until pass
- **PASS** → Continue with changes

#### HOOK 2: Development Mode Validation (MANDATORY)

```bash
# React Admin UI (current)
cd /home/rens/super-forms/src/react/admin && npm run watch &

# Legacy emails-v2 (deprecated)
cd /projects/super-forms/src/react/emails-v2 && npm run watch &
```
- Always run in development mode for debugging
- Check browser console for errors
- Verify functionality works as expected

#### HOOK 3: Syntax Pre-Check (RECOMMENDED)

Before making complex changes, run:
```bash
# React Admin UI (current) - TypeScript type checking
cd /home/rens/super-forms/src/react/admin && npm run typecheck

# React Admin UI (current) - ESLint (if configured)
cd /home/rens/super-forms/src/react/admin && npx eslint . --ext .js,.jsx,.ts,.tsx

# Legacy emails-v2 (deprecated)
cd /projects/super-forms/src/react/emails-v2 && npx eslint src/ --ext .js,.jsx
```

## ESLint Configuration

### Incremental Linting Pattern (WooCommerce Pattern)

Following WooCommerce's approach, we implement **incremental linting** to prevent mass code changes:

**Benefits:**
- Only lint changed files (prevents 10,000+ line suggestions)
- Pre-commit hooks catch errors before deployment
- Gradual improvement without blocking development
- AI cannot break the entire codebase with lint "fixes"

**Implementation:**
1. `.eslintrc.json` - ESLint configuration
2. `.eslintignore` - Ignore legacy files (lint on edit only)
3. `package.json` scripts - Lint commands
4. Pre-commit hooks with `lint-staged` - Auto-lint changed files

### ESLint Configuration File

Location: `.eslintrc.json` (to be created)

```json
{
  "env": {
    "browser": true,
    "es6": true,
    "jquery": true
  },
  "extends": ["eslint:recommended"],
  "parserOptions": {
    "ecmaVersion": 2020,
    "sourceType": "module"
  },
  "rules": {
    "no-console": "off",
    "no-unused-vars": "warn",
    "no-undef": "error",
    "semi": ["error", "always"],
    "quotes": ["warn", "single"],
    "indent": ["warn", 4],
    "brace-style": ["warn", "1tbs"],
    "comma-dangle": ["warn", "never"],
    "no-trailing-spaces": "warn"
  },
  "globals": {
    "wp": "readonly",
    "jQuery": "readonly",
    "ajaxurl": "readonly",
    "SUPER": "readonly"
  }
}
```

### Package.json Scripts

```json
{
  "scripts": {
    "lint": "eslint src/assets/js/**/*.js --max-warnings=0",
    "lint:fix": "eslint src/assets/js/**/*.js --fix",
    "lint:staged": "lint-staged"
  }
}
```

### Pre-Commit Hooks with Husky & lint-staged

**Installation:**
```bash
npm install --save-dev husky lint-staged
npx husky install
```

**Configuration:**

`.husky/pre-commit`:
```bash
#!/bin/sh
. "$(dirname "$0")/_/husky.sh"

npm run lint:staged
```

`package.json`:
```json
{
  "lint-staged": {
    "src/assets/js/**/*.js": [
      "eslint --fix",
      "git add"
    ]
  }
}
```

## Auto-Fix Protocol for Common Errors

### JavaScript/React Syntax Errors

1. **Missing commas in object spreads** - Always check `...condition && { }` syntax
2. **Unclosed parentheses/braces** - Count opening/closing brackets
3. **Import statement errors** - Verify all imports exist and are spelled correctly
4. **JSX syntax errors** - Ensure proper JSX attribute syntax

### Zustand Store Errors

- Use `createWithEqualityFn` from `zustand/traditional` instead of deprecated `create`

### Common JavaScript Patterns to Avoid

**❌ BAD - Nested <p> tags:**
```html
<p>
    <button>Click</button>
    <p>Description</p>  <!-- Invalid! -->
</p>
```

**✅ GOOD - Use <div> for containers:**
```html
<div>
    <button>Click</button>
    <p>Description</p>
</div>
```

**❌ BAD - Duplicate closing braces:**
```javascript
function example() {
    if (condition) {
        doSomething();
    }
    }  // Extra brace!
}
```

**✅ GOOD - Proper brace matching:**
```javascript
function example() {
    if (condition) {
        doSomething();
    }
}
```

## Error Recovery Workflow

When build fails:
1. **READ THE ERROR** - Don't guess, read the exact line number and error
2. **FIX IMMEDIATELY** - Don't continue with other changes
3. **RE-RUN BUILD** - Verify fix works
4. **COMMIT WORKING CODE** - Only commit when build passes

## Pre-Edit Checklist

Before editing complex files:
- [ ] Know the exact line numbers to change
- [ ] Understand the surrounding syntax context
- [ ] Have a plan for testing the change
- [ ] Build is currently passing

## Extract Inline JavaScript Pattern

### Problem: Inline JavaScript in PHP Files

Large PHP files like `page-developer-tools.php` contain thousands of lines of inline JavaScript that cannot be linted or validated before deployment.

### Solution: Extract to Separate Files

**Benefits:**
1. ESLint can validate syntax before deployment
2. Pre-commit hooks catch errors automatically
3. Easier to maintain and debug
4. Can use modern JavaScript modules
5. Browser caching improves performance

**Process:**

1. **Extract JavaScript to separate file:**
   - Create `/src/assets/js/backend/developer-tools.js`
   - Move all `<script>` content from PHP file
   - Convert to proper JavaScript file with strict mode

2. **Update PHP to enqueue script:**
   ```php
   wp_enqueue_script(
       'super-forms-developer-tools',
       plugins_url('/assets/js/backend/developer-tools.js', __FILE__),
       array('jquery'),
       SUPER_VERSION,
       true
   );

   // Pass PHP variables to JavaScript
   wp_localize_script('super-forms-developer-tools', 'devtoolsData', array(
       'ajaxurl' => admin_url('admin-ajax.php'),
       'nonce' => wp_create_nonce('super-form-builder'),
       'migration' => $migration_state
   ));
   ```

3. **Update JavaScript to use localized data:**
   ```javascript
   jQuery(document).ready(function($) {
       const ajaxurl = devtoolsData.ajaxurl;
       const nonce = devtoolsData.nonce;
       const migration = devtoolsData.migration;

       // Rest of JavaScript code...
   });
   ```

## WooCommerce Best Practices

Based on analysis of WooCommerce's codebase:

### Use pnpm Instead of npm

WooCommerce uses pnpm for faster, more efficient package management:
```bash
# Install pnpm globally
npm install -g pnpm

# Use pnpm for all package operations
pnpm install
pnpm add --save-dev package-name
```

### Modular Documentation

- Keep documentation close to code
- Use `.cursor/rules/` for IDE-specific guidance
- Split large docs into domain-specific files

### Testing Philosophy

- Unit tests for business logic
- Integration tests for workflows
- E2E tests for critical paths
- Test React components with Jest

## Debugging Guidelines

### Browser Console

Always check browser console for errors:
```javascript
// Add debug logging
console.log('[SF Debug]', 'Variable:', variable);
console.error('[SF Error]', 'Failed:', error);
console.warn('[SF Warning]', 'Deprecated:', method);
```

### React DevTools

1. Install React DevTools browser extension
2. Open browser DevTools (F12)
3. Navigate to "Components" tab
4. Inspect component props, state, and hooks
5. Use "Profiler" tab for performance analysis

### Network Tab

Monitor AJAX requests:
1. Open DevTools → Network tab
2. Filter by "XHR" or "Fetch"
3. Check request payload and response
4. Verify status codes (200, 400, 500)
5. Check response data structure

## Performance Considerations

### Lazy Loading

- Only load assets on pages that require them
- Use conditional `wp_enqueue_script()` based on current page
- Load heavy libraries only when needed

### Debouncing

```javascript
// Debounce search inputs
let searchTimeout;
$('#search-input').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch($(this).val());
    }, 300);
});
```

### Optimize jQuery Selectors

```javascript
// ❌ BAD - Multiple DOM queries
$('.button').on('click', function() {
    $('.result').text('Loading...');
    $('.result').show();
});

// ✅ GOOD - Cache selector
const $result = $('.result');
$('.button').on('click', function() {
    $result.text('Loading...').show();
});
```

## Security Best Practices

### Nonce Verification

Always include nonces in AJAX requests:
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'super_action',
        security: devtoolsData.nonce,  // Always include nonce
        entry_id: entryId
    },
    success: function(response) {
        // Handle response
    }
});
```

### Escape Output

```javascript
// ❌ BAD - Direct HTML injection
$('#result').html(userInput);

// ✅ GOOD - Escape with text() or sanitize
$('#result').text(userInput);
// OR use DOMPurify for HTML content
$('#result').html(DOMPurify.sanitize(userInput));
```

### Validate Input

```javascript
// Always validate user input
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

if (!validateEmail(userEmail)) {
    alert('Invalid email address');
    return false;
}
```

## Extension JavaScript Patterns

### Backend Settings Management

When saving extension settings in WordPress admin, ensure JavaScript output matches PHP expectations.

**Critical Rules:**
1. **Respect field grouping** - If PHP defines `group_name`, save in that group
2. **Use arrays not objects** - For repeatable items, use `[]` not `{}`
3. **Match PHP structure** - JavaScript output must match what PHP expects to read

**Example: Listings Extension Backend Script**

```javascript
// ✅ GOOD - Save in groups matching PHP definition
data.formSettings._listings = {lists: []};

for (var key = 0; key < list.length; key++) {
    var listItem = {};

    // Generate/preserve unique ID
    var idInput = list[key].querySelector('input[name="id"]');
    listItem.id = idInput ? idInput.value : '';

    // Group fields as defined in PHP
    listItem.display = {
        retrieve: list[key].querySelector('[data-name="retrieve"]').querySelector('.super-active').dataset.value,
        form_ids: list[key].querySelector('input[name="form_ids"]').value
    };

    // Save repeatable items as arrays
    listItem.custom_columns = {
        columns: []  // Array, not object
    };

    var columns = list[key].querySelectorAll('.super-listings-list div[data-name="custom_columns"] li');
    for (var ckey = 0; ckey < columns.length; ckey++) {
        listItem.custom_columns.columns.push({
            name: columns[ckey].querySelector('input[name="name"]').value,
            field_name: columns[ckey].querySelector('input[name="field_name"]').value
        });
    }

    data.formSettings._listings.lists.push(listItem);
}
```

**❌ BAD - Common Mistakes:**

```javascript
// WRONG: Using object with numeric keys instead of array
data.formSettings._listings = {};
for (var key = 0; key < list.length; key++) {
    data.formSettings._listings[key] = {};  // Creates {"0": {}, "1": {}}
}

// WRONG: Saving at top level when PHP expects grouped
listItem.retrieve = value;  // Should be listItem.display.retrieve

// WRONG: Object for repeatable items
listItem.custom_columns = {
    columns: {}  // Should be []
};
for (var i = 0; i < items.length; i++) {
    listItem.custom_columns.columns[i] = item;  // Creates {"0": {}, "1": {}}
}
```

**Why This Matters:**

If JavaScript saves in different structure than PHP expects:
- Migration required for backward compatibility
- Fields appear empty in admin UI after save
- Frontend fails to read settings correctly
- Data loss when users re-save settings

**Reference:** Listings extension (v6.4.127) - see `/home/rens/super-forms/src/includes/extensions/listings/assets/js/backend/script.js` lines 95-172

### Data Structure Consistency Checklist

Before implementing extension settings JavaScript:

- [ ] Review PHP field definitions for `group_name` attributes
- [ ] Check if fields are in groups (look for `group_name` in PHP)
- [ ] Verify repeatable items use arrays `[]` not objects `{}`
- [ ] Test that saved data matches PHP structure exactly
- [ ] Add inline comments explaining backward compatibility context
- [ ] Verify admin UI loads saved settings correctly

**Testing Pattern:**

```javascript
// 1. Save settings in admin
console.log('Saving:', JSON.stringify(data.formSettings._listings, null, 2));

// 2. Reload page and check browser console
// 3. Verify structure matches what JavaScript saved
// 4. Check no fields appear empty that should have values
```
