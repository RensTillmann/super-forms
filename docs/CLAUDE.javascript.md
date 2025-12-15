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
├── schemas/                           # Schema-first architecture (Form Builder V2)
│   ├── core/                          # Core types and registry
│   ├── tabs/                          # Tab schema system
│   ├── toolbar/                       # Toolbar item schema system
│   ├── elements/                      # Element schemas
│   └── index.ts                       # Aggregated exports
├── apps/
│   └── form-builder-v2/               # Form Builder V2 application
│       ├── FormBuilderV2.tsx          # Main component
│       ├── components/                # UI components (TabBar, TopBar, etc.)
│       └── types/                     # Type definitions
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

### Schema-First Architecture (Form Builder V2)

**Location:** `/src/react/admin/schemas/`

Form Builder V2 uses a schema-driven architecture where UI elements, tabs, and toolbar items are defined declaratively rather than hardcoded in components. This enables plugin extensibility and AI integration.

**Core Principles:**
1. **Single Source of Truth** - Schema defines all capabilities (UI, validation, REST API)
2. **Declarative UI** - Tabs and toolbar items registered via schema, not JSX
3. **Plugin Extensibility** - Third-party plugins can register tabs/toolbar items
4. **Type Safety** - TypeScript definitions for all schemas

**Schema Types:**

**Tab Schema** (`/src/react/admin/schemas/tabs/`):
```typescript
registerTab({
  id: 'emails',
  label: 'Emails',
  icon: 'Mail',                // Lucide icon name
  position: 10,                // Sort order
  lazyLoad: false,             // Lazy load component
  description: 'Configure email notifications'
});
```

**Toolbar Item Schema** (`/src/react/admin/schemas/toolbar/`):
```typescript
registerToolbarItem({
  id: 'save',
  type: 'button',              // button, toggle, dropdown, custom
  group: 'primary',            // left, history, canvas, panels, primary
  icon: 'Save',                // Lucide icon name
  label: 'Save',
  tooltip: 'Save Form',
  variant: 'save',             // Button variant
  position: 10,                // Sort order within group
  showLabel: true,
  hiddenOnMobile: true
});
```

**Element Schema** (`/src/react/admin/schemas/elements/`):
```typescript
registerElement({
  type: 'text',
  name: 'Text Input',
  category: 'basic',
  icon: 'Type',
  properties: withBaseProperties({
    general: {
      placeholder: { type: 'string', label: 'Placeholder', ... },
      prefixText: { type: 'string', label: 'Prefix Text', ... },
      suffixIcon: { type: 'icon', label: 'Suffix Icon', ... },
    },
    validation: {
      required: { type: 'boolean', label: 'Required', ... },
      maxLength: { type: 'number', label: 'Maximum Length', ... },
    },
    appearance: {
      labelPosition: { type: 'position_picker', label: 'Label Position', default: 'top-left', ... },
      descriptionPosition: { type: 'position_picker', label: 'Description Position', default: 'bottom-left', ... },
    },
    advanced: {
      showCharacterCount: { type: 'boolean', label: 'Show Character Counter', ... },
      characterCountPosition: { type: 'select', label: 'Counter Position', ... },
      actionButton: { type: 'select', label: 'Action Button', options: ['none', 'copy', 'clear', 'toggle-visibility'], ... },
      helpTooltip: { type: 'string', label: 'Help Tooltip', ... },
    }
  }),
  defaults: { /* default values */ }
});
```

**Property Categories:**
Element properties are organized into 5 categories:
- `general` - Basic settings (name, label, placeholder, prefix/suffix)
- `validation` - Validation rules (required, minLength, pattern)
- `appearance` - Visual settings (labelPosition, descriptionPosition, icons)
- `advanced` - Advanced features (character counter, action buttons, help tooltips)
- `conditions` - Conditional logic

**Registered Element Types:**
- `text` - Text input with prefix/suffix addons (see `/src/react/admin/schemas/elements/text.ts`)
- `button` - Action button with 8 action types (submit, save_state, navigate, trigger_automation, etc.) (see `/src/react/admin/schemas/elements/button.ts` and MCP Button Tools section below for full API)

**Usage in Components:**
```typescript
import { TabBar } from '@/apps/form-builder-v2/components/TabBar';
import { TopBar } from '@/apps/form-builder-v2/components/TopBar';

// Renders all registered tabs from schema
<TabBar activeTab={activeTab} onTabChange={setActiveTab} />

// Renders all registered toolbar items from schema
<TopBar onAction={handleAction} />
```

**Property Panel Integration:**
The StyleTab now automatically renders schema-driven properties for the Appearance and Advanced categories:

```typescript
// StyleTab.tsx - Automatically displays appearance/advanced properties
{(hasAppearanceProps || hasAdvancedProps) && (
  <div className="p-4 space-y-2">
    <CollapsibleSection title="Appearance" icon={<Palette />}>
      <SchemaPropertyPanel
        elementType={element.type}
        properties={element.properties || {}}
        onPropertyChange={onPropertyChange}
        categories={['appearance']}
      />
    </CollapsibleSection>
    <CollapsibleSection title="Advanced" icon={<Settings2 />}>
      <SchemaPropertyPanel
        categories={['advanced']}
      />
    </CollapsibleSection>
  </div>
)}
```

**Benefits:**
- Reduced code size (replaced ~190 lines of hardcoded UI with schema definitions)
- Plugin developers can add tabs/toolbar items without modifying core files
- AI/LLM can query schema to understand available capabilities
- Type-safe with full TypeScript support
- Property panel automatically adapts to element schema changes

**See Also:** `/docs/architecture/form-builder-schema-spec.md` for complete specification

### Property Type System

**Supported Property Types (27 total):**

Form Builder V2 supports 27 property types that map to UI renderers in PropertyRenderer.tsx:

**Primitives:**
- `string` - Text input
- `number` - Numeric input
- `boolean` - Checkbox

**Selection:**
- `select` - Dropdown menu (single choice)
- `multi_select` - Multi-choice dropdown

**Visual:**
- `color` - Color picker
- `icon` - Icon picker (Font Awesome + Lucide)
- `position_picker` - 3x3 spatial grid (top-left, top-center, top-right, left, center, right, bottom-left, bottom-center, bottom-right)

**Complex Structures:**
- `array` - Array editor
- `object` - Object editor
- `conditional_rules` - Conditional logic builder
- `columns_config` - Column layout configuration
- `items_config` - List items configuration
- `key_value` - Key-value pairs
- `repeater_config` - Repeatable field groups

**Content:**
- `rich_text` - WYSIWYG editor
- `code` - Code editor with syntax highlighting
- `tag_input` - Tag/chip input

**Date/Time:**
- `date` - Date picker
- `time` - Time picker
- `datetime` - Combined date/time picker

**Media:**
- `file` - File uploader
- `image` - Image uploader

**Numeric:**
- `range` - Slider control

**Form-Specific:**
- `step_config` - Multi-step form configuration
- `email_template` - Email template editor
- `calculation` - Formula/calculation builder

**Position Picker Implementation:**

Location: `/src/react/admin/apps/form-builder-v2/components/property-panels/schema/renderers/PositionPickerRenderer.tsx`

```typescript
// Schema definition (text.ts)
labelPosition: {
  type: 'position_picker',
  label: 'Label Position',
  description: 'Position of the label relative to the input',
  default: 'top-left',
}

// UI Component - 3x3 toggle grid using shadcn/ui
<ToggleGroup type="single" value={currentValue}>
  {POSITIONS.map(pos => (
    <ToggleGroupItem value={pos.value}>
      <span className="w-2 h-2 rounded-full bg-current" />
    </ToggleGroupItem>
  ))}
</ToggleGroup>
```

**Usage in Elements:**
- Text fields: `labelPosition`, `descriptionPosition` control label/description placement
- Layout: Affects whether label is inline (left/right) or stacked (top/bottom)
- Alignment: Position determines text alignment (left, center, right)

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

**Lazy Loading Pattern (Form Builder V2):**
```tsx
// Import lazy-loaded tabs in FormBuilderV2.tsx
const EmailsTab = lazy(() => import('./tabs/EmailsTab'));
const AutomationsTab = lazy(() => import('../../components/form-builder/automations/AutomationsTab')
  .then(m => ({ default: m.AutomationsTab })));

// Wrap in Suspense boundary
<Suspense fallback={<div>Loading...</div>}>
  {activeTab === 'emails' && <EmailsTab />}
  {activeTab === 'automations' && <AutomationsTab />}
</Suspense>
```

**Why This Architecture:**
- Single mount point prevents multiple React roots competing for DOM
- Centralized data object follows WordPress patterns (like `wp.i18n`, `wp.ajax`)
- Page routing enables multiple admin pages using same bundle
- TypeScript definitions provide type safety for data object
- `restNonce` field enables REST API calls without additional nonce generation
- Lazy loading reduces initial bundle size by code-splitting heavy tabs

### CSS Architecture

**Critical CSS Isolation Strategy** (updated 2025-12-05):

The React admin CSS uses scoped resets to prevent Tailwind's preflight from breaking WordPress admin UI:

1. **Root mount point**: `#sfui-admin-root` - All React apps render here
2. **Scoped resets**: All CSS resets scoped to `#sfui-admin-root` selector **inside `@layer base`**
3. **No global preflight**: Import `tailwindcss/theme` and `tailwindcss/utilities` only (NOT `tailwindcss`)
4. **CSS Layers**: `@layer base` ensures Tailwind utilities always override base resets
5. **Z-index override**: Radix UI modals use `z-[100000]` to appear above WP admin bar (z-index 99999)

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

/* All resets scoped to #sfui-admin-root inside @layer base */
@layer base {
  #sfui-admin-root {
    font-family: var(--font-sans);
    -webkit-font-smoothing: antialiased;
    /* ... */
  }

  #sfui-admin-root *,
  #sfui-admin-root *::before,
  #sfui-admin-root *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    border: 0 solid var(--border);
  }

  /* Form element reset, button reset, etc. */
}
```

**Why @layer base Matters:**
- Without `@layer base`, global resets can override Tailwind utilities due to specificity
- Example: `#sfui-admin-root button { padding: 0 }` would override `px-3 py-2` utilities
- `@layer base` ensures utilities always win, preventing CSS specificity conflicts
- WordPress admin remains untouched outside `#sfui-admin-root`
- Z-index coordination ensures modals appear above WP admin bar

**Standard Tailwind Classes** (no prefix needed):
```tsx
<div className="h-full flex gap-4">
  <Button className="px-4 py-2">Click Me</Button>
</div>
```

**Reference:** See `/home/rens/super-forms/src/react/admin/styles/index.css` lines 223-313 for complete implementation

### Iframe Isolation Architecture

**Since v6.6.0** - Form Builder V2 runs in an isolated iframe to eliminate CSS conflicts with WordPress admin styles and plugins.

**Architecture Overview:**

Form Builder V2 follows WordPress Gutenberg's iframe isolation pattern:
- **Parent Document:** Minimal WordPress admin page that creates and manages the iframe
- **Iframe Document:** Isolated HTML document with its own `<head>` and `<body>` containing the React app
- **Same-Origin:** `about:blank` iframe allows full DOM access without CORS issues
- **CSS Isolation:** admin.css loaded ONLY in iframe head, not in parent document
- **Complete Independence:** WordPress admin styles, theme styles, and plugin styles cannot affect iframe content

**Why Iframe Isolation:**

Before v6.6.0, Form Builder V2 lived directly in the WordPress admin DOM, suffering from CSS conflicts:
- WordPress core admin styles (wp-admin.css, ~15,000 lines)
- Theme admin customizations
- Other plugin admin styles
- All using global selectors that cascaded into Form Builder elements

Example bug: TextInput had double borders (1px from WordPress + 1px from shadcn/ui).

**Implementation Files:**

**PHP View Template** (`/src/includes/admin/views/page-create-form-v2.php`):
```php
<div class="super-create-form-v2">
  <!-- Loading indicator -->
  <div id="sfui-loading-indicator">
    <div class="spinner"></div>
    <p>Loading Form Builder...</p>
  </div>

  <!-- iframe for isolated Form Builder V2 -->
  <iframe
    id="sfui-builder-iframe"
    title="Form Builder"
    data-testid="form-builder-iframe"
  ></iframe>
</div>

<script>
// Initialize iframe when DOM ready
function initIframe() {
  const iframe = document.getElementById('sfui-builder-iframe');

  // CRITICAL: Attach load listener BEFORE setting src (avoid race condition)
  iframe.addEventListener('load', function onIframeLoad() {
    const iframeDoc = iframe.contentDocument;

    // Build HTML structure
    iframeDoc.open();
    iframeDoc.write('<!DOCTYPE html><html lang="en"></html>');
    iframeDoc.close();

    const head = iframeDoc.createElement('head');
    const body = iframeDoc.createElement('body');

    // Add meta tags
    const metaCharset = iframeDoc.createElement('meta');
    metaCharset.setAttribute('charset', 'UTF-8');
    head.appendChild(metaCharset);

    // Load admin.css in iframe ONLY
    const cssLink = iframeDoc.createElement('link');
    cssLink.rel = 'stylesheet';
    cssLink.href = adminCssUrl;
    head.appendChild(cssLink);

    // Setup body
    body.id = 'sfui-admin-root';
    const mountDiv = iframeDoc.createElement('div');
    mountDiv.id = 'sfui-admin-mount';
    body.appendChild(mountDiv);

    // Load scripts in dependency order:
    // wp.hooks → wp.i18n → wp.apiFetch → admin.js
    loadScriptChain(iframeDoc, body);

    // Append to iframe
    iframeDoc.documentElement.appendChild(head);
    iframeDoc.documentElement.appendChild(body);
  });

  iframe.src = 'about:blank';
}
</script>
```

**React Context** (`/src/react/admin/contexts/IframeContext.tsx`):
```typescript
interface IframeContextValue {
  /** The document to use for portal rendering (iframe document or parent document) */
  portalDocument: Document;
  /** Whether we're running in an iframe */
  isInIframe: boolean;
}

export const IframeProvider: React.FC<IframeProviderProps> = ({ children }) => {
  const value = useMemo<IframeContextValue>(() => {
    const isInIframe = window !== window.parent;
    return {
      portalDocument: document, // Always use current document (iframe's document)
      isInIframe,
    };
  }, []);

  return <IframeContext.Provider value={value}>{children}</IframeContext.Provider>;
};

// Hook to get correct document for createPortal()
export const usePortalDocument = (): Document => {
  const { portalDocument } = useIframeContext();
  return portalDocument;
};
```

**React Entry Point** (`/src/react/admin/index.tsx`):
```typescript
function initAdmin(): void {
  // Detect iframe context
  const isInIframe = window !== window.parent;
  console.log(isInIframe ? 'Running in iframe' : 'Running in parent');

  const rootElement = document.getElementById('sfui-admin-mount');
  if (!rootElement || !window.sfuiData) return;

  // Route to page
  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <IframeProvider>
        <FormBuilderV2 />
      </IframeProvider>
    </React.StrictMode>
  );
}
```

**Portal Rendering Pattern:**

Before (pre-v6.6.0):
```typescript
// Portal to parent document.body - WRONG in iframe context
return createPortal(children, document.body);
```

After (v6.6.0+):
```typescript
import { usePortalDocument } from '@/contexts/IframeContext';

function MobileDrawer({ children }) {
  const portalDocument = usePortalDocument();

  // Portal to iframe's document.body (correct document context)
  return createPortal(children, portalDocument.body);
}
```

**Communication Bridge** (`/src/react/admin/lib/iframeMessaging.ts`):

For operations that need to affect the parent window (navigation, notifications):

```typescript
/**
 * Check if running in iframe context
 */
export function isInIframe(): boolean {
  return window !== window.parent;
}

/**
 * Request parent window to navigate to a URL
 * Uses postMessage with explicit same-origin targetOrigin
 */
export function navigateParent(url: string): void {
  if (isInIframe()) {
    window.parent.postMessage(
      { type: 'navigate', url },
      window.location.origin // Explicit origin for security
    );
  } else {
    window.location.href = url;
  }
}

/**
 * Send toast notification to parent window
 */
export function showParentToast(
  message: string,
  variant: 'success' | 'error' | 'info' | 'warning' = 'info'
): void {
  if (isInIframe()) {
    window.parent.postMessage(
      { type: 'toast', message, variant },
      window.location.origin
    );
  } else {
    console.log(`[Toast ${variant}]:`, message);
  }
}
```

**Parent Window Message Handler** (in `page-create-form-v2.php`):
```javascript
function setupCommunicationBridge(iframeWindow) {
  window.addEventListener('message', function(event) {
    // Verify message is from our iframe (security)
    if (event.source !== iframeWindow) return;

    const message = event.data;
    if (!message || !message.type) return;

    switch (message.type) {
      case 'navigate':
        if (message.url) {
          window.location.href = message.url;
        }
        break;

      case 'toast':
        console.log('SFUI Toast:', message.message, message.variant);
        // Future: show WordPress admin notice
        break;
    }
  });
}
```

**Usage Examples:**

**Navigate after form save:**
```typescript
import { navigateParent } from '@/lib/iframeMessaging';

async function handleSave() {
  await saveForm();
  // Redirect to forms list (parent window navigates)
  navigateParent(window.sfuiData.navigation.forms);
}
```

**Show success notification:**
```typescript
import { showParentToast } from '@/lib/iframeMessaging';

async function handlePublish() {
  await publishForm();
  showParentToast('Form published successfully!', 'success');
}
```

**Script Loading Order:**

CRITICAL - WordPress scripts must load in proper dependency order:

1. **wp.hooks** - WordPress hooks system (required by wp.i18n)
2. **wp.i18n** - Internationalization (required by wp.apiFetch)
3. **wp.apiFetch** - REST API wrapper (required by admin.js for API calls)
4. **admin.js** - React admin bundle

Each script waits for previous to load before continuing.

**Security Model:**

- **Same-origin only:** iframe src is `about:blank`, inherits parent origin
- **Explicit targetOrigin:** postMessage uses `window.location.origin` (not `'*'`)
- **Source verification:** Parent verifies `event.source === iframeWindow`
- **Trusted URLs:** Navigation URLs come from `window.sfuiData.navigation` (server-rendered)

**Browser Compatibility:**

- **Visual Viewport API:** iOS 13+, Chrome 62+, Firefox 91+, Safari 13+ (fallback: `window.innerHeight`)
- **iframe `about:blank`:** Universal support
- **postMessage:** Universal support
- **contentDocument/contentWindow:** Universal support (same-origin)

**Testing Considerations:**

- **Playwright:** Use `page.frameLocator('[data-testid="form-builder-iframe"]')` to access iframe content
- **React DevTools:** Works in iframe (inspect iframe content directly)
- **Console logs:** Appear in iframe's console context
- **Hot Module Replacement:** Vite HMR works in iframe context

**Components Updated for Iframe:**

- `MobileDrawer` - Uses `usePortalDocument()` for portal rendering
- `RightSidebar` - Uses `usePortalDocument()` for portal rendering (mobile mode)
- `useWPAdminSidebar` - Checks `window.sfuiData` before DOM queries (iframe-aware)

**Benefits:**

- **Zero CSS conflicts** - WordPress admin styles cannot reach iframe content
- **No plugin interference** - Other plugins cannot inject styles into iframe
- **Future-proof** - Any WordPress admin style changes won't affect Form Builder
- **WordPress standard** - Same pattern used by Gutenberg (proven at scale)
- **Performance** - Negligible overhead, one-time iframe document setup

**Migration Notes:**

- Portal components must use `usePortalDocument()` instead of `document.body`
- Navigation must use `navigateParent()` instead of `window.location.href`
- Toasts should use `showParentToast()` for parent window notifications
- All React components automatically wrapped in `<IframeProvider>` via `index.tsx`

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

## Themes Tab

### Overview (v6.6.0+)

The Themes Tab provides a gallery interface for browsing, applying, and creating form themes. Themes are first-class entities stored in the database, enabling reuse across forms and AI-powered generation.

**Location:** `/src/react/admin/components/themes/`

**Key Features:**
- Theme gallery with preview swatches
- Apply theme with one click
- Save current styles as custom theme
- "Coming Soon" badges for stub themes
- Delete custom themes (system themes protected)
- REST API integration via `wp.apiFetch()`

### Component Architecture

**Main Components:**

**ThemesTab.tsx** - Main tab component:
```tsx
import { ThemesTab } from '@/components/themes';

// Renders in Form Builder V2 when activeTab === 'themes'
<ThemesTab formId={formId} />
```

Features:
- Loads themes via `useThemes()` hook
- Applies theme to current form
- Shows CreateThemeDialog for saving current styles
- Handles system theme protection (no delete button)
- Displays "Coming Soon" badge for stub themes

**ThemeGallery.tsx** - Grid layout:
```tsx
<ThemeGallery
  themes={themes}
  onApply={handleApply}
  onDelete={handleDelete}
  isDeleting={isDeleting}
/>
```

Renders responsive grid (3 columns desktop, 2 tablet, 1 mobile) of theme cards.

**ThemeCard.tsx** - Individual theme preview:
```tsx
<ThemeCard
  theme={theme}
  onApply={() => onApply(theme.id)}
  onDelete={() => onDelete(theme.id)}
  isDeleting={isDeleting}
/>
```

Displays:
- Theme name and description
- Category badge (Light, Dark, etc.)
- Preview swatches from `preview_colors` array
- Apply button (primary action)
- Delete button (custom themes only, not system themes)
- "Coming Soon" badge if `is_stub === true`

**CreateThemeDialog.tsx** - Save current styles:
```tsx
<CreateThemeDialog
  open={open}
  onClose={() => setOpen(false)}
  onSave={handleSave}
/>
```

Form fields:
- Name (required)
- Description (optional)
- Category dropdown (light, dark, minimal, corporate, playful, highContrast)

Captures current `styleRegistry.exportStyles()` and posts to REST API.

### Custom Hook: useThemes

**Location:** `/src/react/admin/components/themes/hooks/useThemes.ts`

**Usage:**
```typescript
import { useThemes } from '@/components/themes/hooks/useThemes';

function MyComponent() {
  const {
    themes,
    isLoading,
    error,
    createTheme,
    deleteTheme,
    applyTheme,
    isDeleting,
    refetch
  } = useThemes();

  // Apply theme to form
  await applyTheme(themeId, formId);

  // Save current styles as new theme
  await createTheme({
    name: 'My Theme',
    description: 'Custom brand theme',
    category: 'light',
    styles: styleRegistry.exportStyles(),
    preview_colors: ['#fff', '#000', '#blue', '#gray']
  });

  // Delete custom theme
  await deleteTheme(themeId);
}
```

**API Methods:**
- `themes` - Array of theme objects with decoded JSON
- `isLoading` - Boolean for initial load state
- `error` - Error message if fetch failed
- `createTheme(data)` - POST to `/super-forms/v1/themes`
- `deleteTheme(id)` - DELETE to `/super-forms/v1/themes/{id}`
- `applyTheme(themeId, formId)` - POST to `/super-forms/v1/themes/{id}/apply`
- `isDeleting` - Boolean for delete in progress
- `refetch()` - Reload themes from server

**REST Integration:**
All operations use `wp.apiFetch()` with WordPress cookie authentication. No custom nonces required.

### Style System Integration

**styleRegistry** - In-memory style storage:
```typescript
import { styleRegistry } from '@/schemas/styles/registry';

// Export current styles for theme creation
const styles = styleRegistry.exportStyles();
// Returns: Record<NodeType, Partial<StyleProperties>>

// Import styles from theme
styleRegistry.importStyles(theme.styles);

// Subscribe to changes
const unsubscribe = styleRegistry.subscribe(() => {
  // Re-render when styles change
});
```

**Style Resolution Flow:**
1. Theme applied via REST API → Form settings updated
2. Form loads → `globalStyles` from settings loaded into `styleRegistry`
3. Elements render → `useResolvedStyle()` merges global + overrides
4. Live preview updates on style changes

### styleUtils.ts - CSS Conversion

**Location:** `/src/react/admin/lib/styleUtils.ts`

**Purpose:** Convert StyleProperties to React CSSProperties for element rendering.

**Core Function:**
```typescript
import { stylesToCSS } from '@/lib/styleUtils';
import type { StyleProperties } from '@/schemas/styles';

const style: Partial<StyleProperties> = {
  fontSize: 14,
  color: '#1f2937',
  margin: { top: 0, right: 0, bottom: 4, left: 0 },
  borderRadius: 6,
};

const css = stylesToCSS(style);
// Returns CSSProperties:
// {
//   fontSize: '14px',
//   color: '#1f2937',
//   margin: '0px 0px 4px 0px',
//   borderRadius: '6px'
// }
```

**Conversion Rules:**
- Numeric values → `${value}px` (fontSize, borderRadius, letterSpacing)
- BoxSpacing → `${top}px ${right}px ${bottom}px ${left}px` (margin, padding, border)
- Colors → pass through (already hex strings)
- Width → handle both numbers and strings ('100%' or 300)

**Helper Function:**
```typescript
import { mergeWithElementProps } from '@/lib/styleUtils';

const resolvedStyle = stylesToCSS(globalStyle);
const finalStyle = mergeWithElementProps(resolvedStyle, element.properties);
// Element properties override layout (width, margin)
```

### ElementRenderer Integration

**Location:** `/src/react/admin/apps/form-builder-v2/components/elements/ElementRenderer.tsx`

**Pattern:**
```tsx
import { useResolvedStyle } from '@/apps/form-builder-v2/hooks/useResolvedStyle';
import { stylesToCSS } from '@/lib/styleUtils';

export const ElementRenderer: React.FC<{ element }> = ({ element }) => {
  // Resolve styles for each node type the element contains
  const labelStyle = useResolvedStyle(element.id, 'label');
  const inputStyle = useResolvedStyle(element.id, 'input');
  const errorStyle = useResolvedStyle(element.id, 'error');

  // Convert to React CSS
  const labelCSS = stylesToCSS(labelStyle);
  const inputCSS = stylesToCSS(inputStyle);

  return (
    <div>
      <label style={labelCSS}>{element.properties?.label}</label>
      <input style={inputCSS} disabled />
      {error && <div style={stylesToCSS(errorStyle)}>{error}</div>}
    </div>
  );
};
```

**Live Preview:**
When theme is applied or global styles change:
1. `styleRegistry` notifies subscribers
2. `useResolvedStyle()` re-runs
3. ElementRenderer gets new CSS
4. Canvas updates instantly

### FloatingPanel - Element Style Overrides

**Location:** `/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`

**Note:** On mobile, element properties use PropertiesBottomTray instead of FloatingPanel (see PropertiesBottomTray section). The tab structure and property panels described below apply to both components.

**Pattern:**
```tsx
import { ElementStylesSection } from '@/components/settings/ElementStylesSection';

// In FloatingPanel content area (after properties):
{hasSchema && (
  <div className="mt-6 pt-6 border-t">
    <ElementStylesSection
      elementId={element.id}
      elementType={element.type}
      onUpdate={handleUpdate}
    />
  </div>
)}
```

**ElementStylesSection.tsx** - Per-element style editor:
- Shows all applicable node types for element (label, input, error, etc.)
- NodeStyleEditor for each node with link/unlink controls
- Unlinked properties show override value
- Linked properties use global value (grayed out)
- Visual indication: chain icon = linked, broken chain = overridden

**NodeStyleEditor.tsx** - Individual node controls:
```tsx
<NodeStyleEditor
  nodeType="input"
  elementId={elementId}
  onLink={(property) => removeOverride(property)}
  onUnlink={(property) => setOverride(property, currentValue)}
/>
```

Controls based on `NODE_STYLE_CAPABILITIES[nodeType]`:
- Typography: fontSize, fontWeight, fontFamily, lineHeight
- Colors: color, backgroundColor, borderColor
- Spacing: margin, padding, border (visual box model)
- Layout: borderRadius, width, minHeight

### MCP/AI Integration

**Location:** `/src/react/admin/mcp/`

**Style Action Schema:**
```typescript
import { z } from 'zod';

const ListThemesAction = z.object({
  action: z.literal('listThemes'),
  includeSystem: z.boolean().optional(),
  includeStubs: z.boolean().optional(),
  category: z.enum(['light', 'dark', 'minimal', 'corporate', 'playful', 'highContrast']).optional(),
});

const ApplyThemeAction = z.object({
  action: z.literal('applyTheme'),
  themeId: z.union([z.string(), z.number()]),
  formId: z.number().optional(),
});

const GenerateThemeAction = z.object({
  action: z.literal('generateTheme'),
  name: z.string().min(1).max(100),
  baseColor: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional(),
  density: z.enum(['compact', 'comfortable', 'spacious']).optional(),
  cornerStyle: z.enum(['sharp', 'rounded', 'pill']).optional(),
  contrastPreference: z.enum(['high', 'standard', 'soft']).optional(),
  save: z.boolean().optional().default(true),
});
```

**Style Actions Handler:**
```typescript
// src/react/admin/mcp/handlers/styleActions.ts
export async function handleStyleAction(action: StyleAction) {
  switch (action.action) {
    case 'listThemes': {
      const themes = await wp.apiFetch({
        path: '/super-forms/v1/themes',
        method: 'GET'
      });
      return { success: true, themes };
    }

    case 'applyTheme': {
      await wp.apiFetch({
        path: `/super-forms/v1/themes/${action.themeId}/apply`,
        method: 'POST',
        data: { form_id: action.formId }
      });
      return { success: true };
    }

    case 'generateTheme': {
      const generatedStyles = generateThemeFromOptions({
        baseColor: action.baseColor,
        density: action.density,
        cornerStyle: action.cornerStyle,
      });

      if (action.save) {
        const theme = await wp.apiFetch({
          path: '/super-forms/v1/themes',
          method: 'POST',
          data: {
            name: action.name,
            styles: generatedStyles,
            preview_colors: extractPreviewColors(generatedStyles),
          }
        });
        return { success: true, theme };
      }

      return { success: true, styles: generatedStyles };
    }
  }
}
```

**Theme Generator:**
```typescript
// src/react/admin/lib/themeGenerator.ts
export function generateThemeFromOptions(options: GenerateThemeOptions): ThemeStyles {
  // 1. Derive full color palette from baseColor using color theory
  const palette = deriveColorPalette(options.baseColor || '#2563eb');

  // 2. Apply density settings (compact/comfortable/spacious)
  const spacing = getDensitySpacing(options.density || 'comfortable');

  // 3. Apply corner style (sharp/rounded/pill)
  const borderRadius = getCornerRadius(options.cornerStyle || 'rounded');

  // 4. Generate node styles for all 13 node types
  return {
    label: { fontSize: 14, color: palette.text, margin: spacing.label },
    input: { fontSize: 14, borderColor: palette.border, borderRadius, ... },
    // ... all node types
  };
}
```

Color theory functions:
- `lighten(color, amount)` - Increase lightness
- `darken(color, amount)` - Decrease lightness
- `adjustHue(color, degrees)` - Shift hue on color wheel
- `getComplementary(color)` - Opposite hue (180deg)
- `getAnalogous(color)` - Adjacent hues (±30deg)
- `adjustSaturation(color, amount)` - Increase/decrease saturation

**AI Example Prompts (Style Tools):**
```
"use the dark theme"
→ listThemes() → find dark → applyTheme(id: 2)

"create a theme based on my brand color #FF5722"
→ generateTheme({ name: "Orange Brand", baseColor: "#FF5722" })

"make a compact corporate theme with sharp corners"
→ generateTheme({
    name: "Corporate",
    density: "compact",
    cornerStyle: "sharp",
    baseColor: "#1d4ed8"
  })
```

### Button Tools

**Location:** `/src/react/admin/mcp/handlers/buttonActions.ts`

**Purpose:** Enable LLM agents to add and configure button elements with diverse action types including automation triggers, navigation, overlays, and state management.

**Button Action Schema:**
```typescript
import { z } from 'zod';

// Button can trigger 8 different action types
const ButtonActionTypeSchema = z.enum([
  'submit',              // Standard form submission
  'save_state',          // Save as draft/pending/incomplete
  'reset',               // Clear all form fields
  'navigate',            // Wizard step navigation (next/previous/specific)
  'trigger_automation',  // Fire custom event for automation workflow
  'open_overlay',        // Open modal/drawer/popup/dialog
  'toggle_visibility',   // Show/hide other form elements
  'copy_to_clipboard',   // Copy content to clipboard
]);

// Add button to form
const AddButtonAction = z.object({
  action: z.literal('addButton'),
  formId: z.number(),
  buttonText: z.string(),
  actionType: ButtonActionTypeSchema.default('submit'),
  // Optional styling
  variant: z.enum(['primary', 'secondary', 'outline', 'ghost', 'link', 'destructive']).optional(),
  size: z.enum(['xs', 'sm', 'md', 'lg', 'xl']).optional(),
  icon: z.string().optional(),
  fullWidth: z.boolean().optional(),
  // Action-specific properties
  eventId: z.string().optional(),              // For trigger_automation
  saveState: z.enum(['draft', 'pending_review', 'incomplete']).optional(),
  navigateDirection: z.enum(['next', 'previous', 'first', 'last', 'specific']).optional(),
  overlayType: z.enum(['modal', 'dialog', 'drawer', 'tray', 'sheet', 'popup']).optional(),
  targetElements: z.array(z.string()).optional(),  // For toggle_visibility
  // Behavior
  validateBeforeAction: z.boolean().optional(),
  loadingText: z.string().optional(),
  successText: z.string().optional(),
  // Placement
  afterElementId: z.string().optional(),
  position: z.enum(['start', 'end']).optional(),
});
```

**Handler Functions:**

```typescript
export async function handleButtonAction(rawAction: unknown): Promise<ButtonActionResponse> {
  const action = ButtonActionSchema.parse(rawAction);

  switch (action.action) {
    case 'addButton': {
      const store = useElementsStore.getState();
      const elementId = `button-${generateId()}`;

      store.addElement({
        type: 'button',
        id: elementId,
        properties: {
          buttonText: action.buttonText,
          actionType: action.actionType,
          variant: action.variant || 'primary',
          // ... map all properties
        }
      }, insertIndex);

      return { success: true, data: { elementId, name } };
    }

    case 'createButtonAutomation': {
      // Add button + create automation in one call
      const buttonId = await addButtonElement(action);
      const automationId = await wp.apiFetch({
        path: '/super-forms/v1/automations',
        method: 'POST',
        data: {
          name: action.automationName,
          trigger_event: `button.${action.eventId}.clicked`,
          actions: action.automationActions
        }
      });
      return { success: true, data: { buttonId, automationId } };
    }

    case 'addNavigationButtons': {
      // Add prev/next buttons for wizard steps
      if (action.showPrevious) {
        store.addElement({
          type: 'button',
          properties: {
            buttonText: action.previousText || 'Back',
            actionType: 'navigate',
            navigateDirection: 'previous',
            variant: 'outline'
          }
        });
      }
      if (action.showNext) {
        store.addElement({
          type: 'button',
          properties: {
            buttonText: action.isFinalStep ? 'Submit' : action.nextText || 'Next',
            actionType: action.isFinalStep ? 'submit' : 'navigate',
            navigateDirection: 'next',
            variant: 'primary'
          }
        });
      }
      return { success: true };
    }
  }
}
```

**AI Example Prompts (Button Tools):**
```
"add a submit button at the end"
→ addButton({ formId: 123, buttonText: "Submit", actionType: "submit", position: "end" })

"add a save draft button with outline style"
→ addButton({
    formId: 123,
    buttonText: "Save Draft",
    actionType: "save_state",
    saveState: "draft",
    variant: "outline",
    icon: "save"
  })

"create a button that generates a PDF when clicked"
→ createButtonAutomation({
    formId: 123,
    buttonText: "Generate PDF",
    eventId: "generate_pdf",
    icon: "file-text",
    loadingText: "Generating...",
    successText: "PDF Ready!",
    automationActions: [
      { type: "generate_file", config: { template: "invoice", format: "pdf" } }
    ]
  })

"add navigation buttons to step 2"
→ addNavigationButtons({
    formId: 123,
    stepIndex: 2,
    showPrevious: true,
    showNext: true,
    nextText: "Continue",
    validateBeforeNext: true
  })

"add a button that opens a modal with terms and conditions"
→ addButton({
    formId: 123,
    buttonText: "View Terms",
    actionType: "open_overlay",
    overlayType: "modal",
    overlayTitle: "Terms & Conditions",
    overlayContent: "<p>Your terms content here...</p>",
    variant: "link"
  })

"list all buttons in form 123"
→ listButtons({ formId: 123 })

"update button btn-abc123 to be destructive style"
→ configureButton({
    elementId: "btn-abc123",
    updates: { variant: "destructive", buttonText: "Delete Entry" }
  })
```

**Button Element Properties:**

Button elements support 40+ properties organized into categories:

**General Properties:**
- `buttonText` (string, translatable, supportsTags) - Text displayed on button
- `actionType` (select) - What happens on click (submit, save_state, reset, navigate, trigger_automation, open_overlay, toggle_visibility, copy_to_clipboard)
- `eventId` (string) - Custom event identifier for automation binding (pattern: `^[a-z][a-z0-9_]*$`)
- `icon` (icon) - Lucide icon name
- `iconPosition` (select) - left/right

**Action-Specific Properties:**
- `saveState` - draft/pending_review/incomplete (shown when actionType=save_state)
- `navigateDirection` - next/previous/first/last/specific (shown when actionType=navigate)
- `targetStep` - Step number/ID for specific navigation
- `overlayType` - modal/dialog/drawer/tray/sheet/popup (shown when actionType=open_overlay)
- `overlayTitle`, `overlayContent`, `overlaySize`, `drawerPosition` - Overlay configuration
- `targetElements` - Element IDs to show/hide (shown when actionType=toggle_visibility)
- `visibilityAction` - toggle/show/hide
- `copySource` - static/field/template (shown when actionType=copy_to_clipboard)
- `copyContent`, `sourceField` - Content to copy

**Validation Properties:**
- `validateBeforeAction` (boolean, default: true) - Validate form before executing action
- `disabledUntilValid` (boolean) - Button disabled until all required fields valid

**Appearance Properties:**
- `variant` (select) - primary/secondary/outline/ghost/link/destructive
- `size` (select) - xs/sm/md/lg/xl
- `fullWidth` (boolean) - Button spans full container width
- `alignment` (select) - left/center/right

**Advanced Properties:**
- `loadingText` (string, translatable) - Text shown during action execution
- `successText` (string, translatable) - Button text after successful action
- `successDuration` (number, default: 2000) - How long to show success state
- `confirmBeforeAction` (boolean) - Show confirmation dialog
- `confirmationMessage` (string, translatable) - Confirmation dialog message
- `successMessage` (string, translatable, supportsTags) - Toast message on success
- `errorMessage` (string, translatable, supportsTags) - Toast message on error
- `awaitResponse` (boolean, default: true) - Wait for automation completion (trigger_automation only)
- `testId` (string) - data-testid attribute for testing

**Property Conditional Visibility:**
Each action type exposes relevant properties via `conditions` array. For example, `eventId` only shows when `actionType` equals `trigger_automation`.

**Button Element Schema Registration:**

```typescript
// src/react/admin/schemas/elements/button.ts
export const ButtonElementSchema = registerElement({
  type: 'button',
  name: 'Button',
  description: 'Action button that triggers automations, navigation, or other behaviors',
  category: 'basic',
  icon: 'mouse-pointer-click',
  container: null,

  properties: withBaseProperties({
    general: {
      buttonText: { type: 'string', label: 'Button Text', default: 'Submit', translatable: true, supportsTags: true },
      actionType: { type: 'select', label: 'Action Type', options: [...], default: 'submit' },
      eventId: {
        type: 'string',
        label: 'Event ID',
        pattern: '^[a-z][a-z0-9_]*$',
        conditions: [{ property: 'actionType', operator: 'equals', value: 'trigger_automation' }]
      },
      // ... 40+ more properties
    },
    validation: { validateBeforeAction, disabledUntilValid },
    appearance: { variant, size, fullWidth, alignment },
    advanced: { loadingText, successText, confirmBeforeAction, awaitResponse, testId }
  }),

  defaults: {
    buttonText: 'Submit',
    actionType: 'submit',
    variant: 'primary',
    size: 'md',
    validateBeforeAction: true,
    hideLabel: true
  },

  translatable: ['buttonText', 'loadingText', 'successText', 'confirmationMessage', 'successMessage', 'errorMessage', 'overlayTitle', 'overlayContent'],
  supportsTags: ['buttonText', 'copyContent', 'successMessage', 'errorMessage', 'overlayContent']
});
```

**MCP Tool Definition:**

```typescript
export const buttonToolDefinition = {
  name: 'form_buttons',
  description: `Manage form button elements with various action types...`,
  inputSchema: ButtonActionSchema
};
```

**ElementsStore Integration:**

Button MCP handlers use the ElementsStore API:
- `addElement(element, index?)` - Add button to form
- `updateElement(elementId, updates)` - Update button properties
- `removeElement(elementId)` - Remove button
- Store accessed via `useElementsStore.getState()`

**Automation Event Pattern:**

Buttons with `actionType: 'trigger_automation'` fire custom events:
- Event ID pattern: `button.{eventId}.clicked`
- Example: Button with `eventId: "generate_pdf"` fires `button.generate_pdf.clicked`
- Automation trigger nodes can bind to these events
- Context includes: `form_id`, `button_id`, `form_data`, `user_id`, `session_key`

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

## Drag-and-Drop System (@dnd-kit)

### Overview (v6.6.0+)

Form Builder V2 uses **@dnd-kit** for all drag-and-drop operations, replacing the legacy native HTML5 drag-and-drop API. This migration provides:
- Touch device support (mobile/tablet drag works properly)
- Keyboard accessibility (Space to grab, arrows to move, Escape to cancel)
- Proper scroll handling (drag handles don't trigger page scroll)
- Custom drag overlays with smooth animations
- Nested container support (drop elements into columns, tabs, etc.)

**Migration completed:** 2025-12-10 (all legacy native drag code removed)

### Architecture

**Core Libraries:**
- `@dnd-kit/core` ^6.1.0 - Core drag-and-drop primitives
- `@dnd-kit/sortable` ^8.0.0 - Sortable list utilities and hooks
- `@dnd-kit/utilities` ^3.2.2 - Helper functions for transforms

**Component Location:** `/src/react/admin/apps/form-builder-v2/components/dnd/`

**Key Components:**
```typescript
// Exported from components/dnd/index.ts
export { SortableElement, ElementDragPreview } from './SortableElement';
export { DraggablePaletteItem, PaletteDragPreview } from './DraggablePaletteItem';
export { SortablePanelItem, PanelDragPreview } from './SortablePanelItem';
```

### DndContext Setup

**Location:** `FormBuilderV2.tsx` (wraps both desktop and mobile canvas)

**Sensor Configuration:**
```typescript
const sensors = useSensors(
  useSensor(PointerSensor, {
    activationConstraint: {
      distance: 8, // 8px movement required before drag starts (prevents accidental drags)
    },
  }),
  useSensor(KeyboardSensor, {
    coordinateGetter: sortableKeyboardCoordinates, // Standard keyboard navigation
  })
);
```

**Collision Detection:**
- Uses `closestCenter` algorithm for determining drop targets
- Handles nested containers via collision detection bubbling

**Event Handlers:**
```typescript
<DndContext
  sensors={sensors}
  collisionDetection={closestCenter}
  onDragStart={handleDragStart}
  onDragOver={handleDragOver}
  onDragEnd={handleDragEnd}
>
  {/* Canvas content */}
</DndContext>
```

### SortableElement Component

**Purpose:** Canvas elements with drag handles for reordering

**Key Features:**
- Drag listeners attached **ONLY to Move icon handle** (not entire element)
- Prevents scroll conflicts when user clicks/drags element body
- Smooth transitions with opacity fade during drag
- Data-testid attributes for AI/testing integration

**Implementation:**
```typescript
// components/dnd/SortableElement.tsx
const {
  attributes,
  listeners,
  setNodeRef,
  transform,
  transition,
  isDragging,
} = useSortable({ id: element.id });

return (
  <div ref={setNodeRef} style={{ transform, transition, opacity: isDragging ? 0.5 : 1 }}>
    <div className="element-controls">
      {/* CRITICAL: listeners only on handle */}
      <button {...attributes} {...listeners} className="element-control-btn">
        <Move size={16} />
      </button>
      {/* Other controls don't have listeners */}
    </div>
    <ElementRenderer element={element} />
  </div>
);
```

### DraggablePaletteItem Component

**Purpose:** Element palette items for adding new elements to canvas

**Key Features:**
- Uses `useDraggable` hook (not `useSortable` - palette items aren't sortable)
- ID format: `palette:${elementType}` (distinguishes from existing element UUIDs)
- Carries metadata via `data` object (type, label, isNew flag)

**Implementation:**
```typescript
// components/dnd/DraggablePaletteItem.tsx
const draggableId = `palette:${element.type}`;

const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
  id: draggableId,
  data: {
    type: 'palette-item',
    elementType: element.type,
    label: element.label,
    isNew: true,
  },
});
```

### Drag Overlays

**Purpose:** Custom drag previews that follow the cursor

**ElementDragPreview:**
Shows existing element being reordered:
```typescript
<div className="drag-overlay-element">
  <Move size={14} className="text-blue-500" />
  <span>{element.properties?.label || element.type}</span>
</div>
```

**PaletteDragPreview:**
Shows new element being added from palette:
```typescript
<div className="palette-drag-preview">
  <Icon className="w-6 h-6 text-primary" />
  <span className="preview-label">{label}</span>
  <span className="preview-hint">Drop to add</span>
</div>
```

**DragOverlay Component:**
```typescript
<DragOverlay>
  {activeId ? (
    isPaletteItem(activeId) ? (
      <PaletteDragPreview {...} />
    ) : (
      <ElementDragPreview element={items[activeId]} />
    )
  ) : null}
</DragOverlay>
```

### Nested Container Support

**Container Drop Zones:**
Containers like ColumnsContainer support dropping elements into columns:

**ID Format:** `column:${elementId}:${columnIndex}` for column drop zones

**Example (ColumnsContainer.tsx):**
```typescript
{columns.map((column, index) => {
  const columnDropId = `column:${element.id}:${index}`;
  const columnElements = children.filter(child => child.parent === columnDropId);

  return (
    <SortableContext items={columnElements.map(e => e.id)} strategy={verticalListSortingStrategy}>
      {columnElements.map(child => (
        <SortableElement key={child.id} element={child} {...props} />
      ))}
    </SortableContext>
  );
})}
```

### Empty Canvas Drop Zone

**Purpose:** Visual feedback when canvas is empty

**Implementation:**
```typescript
const { setNodeRef, isOver } = useDroppable({ id: 'empty-canvas' });

<div
  ref={setNodeRef}
  className={`drop-zone ${isDragging ? 'drop-zone-active' : ''} ${isOver ? 'drop-zone-hover' : ''}`}
  role="region"
  aria-label="Drop zone for new form elements"
>
  <p>Drag elements here to start building your form</p>
</div>
```

### Floating Panel Integration

**Purpose:** Reorder elements in tree view using same @dnd-kit system

**Component:** `SortablePanelItem.tsx`

**Features:**
- Separate `DndContext` for panel (independent of canvas)
- Shorter activation distance (5px) for tighter UI
- Same keyboard navigation support

**Implementation:**
```typescript
// FloatingElementsPanel
const panelSensors = useSensors(
  useSensor(PointerSensor, {
    activationConstraint: { distance: 5 },
  }),
  useSensor(KeyboardSensor, {
    coordinateGetter: sortableKeyboardCoordinates,
  })
);

<DndContext sensors={panelSensors} onDragEnd={handlePanelDragEnd}>
  <SortableContext items={order} strategy={verticalListSortingStrategy}>
    {orderedElements.map(element => (
      <SortablePanelItem key={element.id} element={element} {...props} />
    ))}
  </SortableContext>
</DndContext>
```

### Keyboard Accessibility

**Navigation Pattern (provided by @dnd-kit):**
1. Tab to focus on element
2. Space to grab/pick up element
3. Arrow keys (↑↓) to move up/down in order
4. Space again to drop at new position
5. Escape to cancel drag operation

**Screen Reader Announcements:**
- "Element grabbed" when Space pressed
- "Element moved to position X of Y" during arrow navigation
- "Element dropped" when released

**Implementation:**
All accessibility features are automatic when using `useSortable` with `KeyboardSensor`.

### Event Flow

**Adding New Element from Palette:**
1. User drags `DraggablePaletteItem` from bottom tray
2. `onDragStart` → Store active ID, show `PaletteDragPreview` in `DragOverlay`
3. `onDragOver` → Detect drop target (canvas or container column)
4. `onDragEnd` → Check if `active.id` starts with `palette:`, extract element type, call `addElement()`

**Reordering Existing Element:**
1. User drags via Move handle on `SortableElement`
2. `onDragStart` → Store active ID, show `ElementDragPreview` in `DragOverlay`
3. `onDragOver` → Update visual drop indicators
4. `onDragEnd` → Get old/new index, call `arrayMove(order, oldIndex, newIndex)`, update store

**Container Drop:**
1. User drags element over column container
2. Collision detection identifies `column:id:index` as drop target
3. `onDragEnd` → Parse column ID, call `moveElement(elementId, { parent: columnId, position: 'inside' })`

### Migration from Native Drag API

**What Was Removed (2025-12-10):**
- All `draggable={true}` attributes
- `onDragStart`, `onDragEnd`, `onDragOver`, `onDrop` event handlers
- `dataTransfer` API usage
- State variables: `isDragging`, `draggedElement`, `dragOverIndex`
- ~70 lines of legacy drag code

**What Was Added:**
- `@dnd-kit` component wrappers (SortableElement, DraggablePaletteItem, etc.)
- DndContext with sensor configuration
- DragOverlay for custom previews
- Data-testid attributes for testing

**Benefits of Migration:**
- Touch drag now works on mobile/tablet devices
- Keyboard navigation for accessibility compliance
- No more scroll conflicts from drag handles
- Cleaner code with declarative hooks
- Nested container drops fully supported

### Testing Considerations

**Manual Testing Required:**
- Touch devices (iPad, Android tablets) - verify drag works
- Keyboard navigation - verify Space/arrows work
- Empty canvas - verify drop zone appears and accepts drops
- Nested containers - verify elements can be dropped into columns
- Multi-select - verify dragging multiple elements works
- Undo/redo - verify drag operations trigger history save

**Automated Testing:**
Data-testid attributes added for Playwright/E2E tests:
- `data-testid="sortable-element-{id}"`
- `data-testid="drag-handle-{id}"`
- `data-testid="palette-item-{type}"`
- `data-testid="element-drag-preview"`
- `data-testid="palette-drag-preview"`

### Performance Considerations

**Activation Constraint:**
The 8px activation distance prevents accidental drags when users click elements for selection. This is especially important for:
- Selecting multiple elements (Ctrl+click)
- Opening context menus (right-click)
- Clicking through to element content

**Transition Disabling:**
Transitions are disabled during drag for immediate visual feedback, then re-enabled on drop for smooth return animations.

**Memoization:**
Element rendering is memoized to prevent unnecessary re-renders during drag operations.

### Reference Implementation

**Complete working example:** `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/FormBuilderV2.tsx`

**Key sections:**
- Lines 21-39: @dnd-kit imports
- Lines 1323-1343: Desktop canvas DndContext (desktop view)
- Lines 3208-3611: Mobile canvas DndContext (mobile view)
- Lines 1191-1356: Floating panel DndContext (tree view)

**Component files:**
- `/src/react/admin/apps/form-builder-v2/components/dnd/SortableElement.tsx` (112 lines)
- `/src/react/admin/apps/form-builder-v2/components/dnd/DraggablePaletteItem.tsx` (96 lines)
- `/src/react/admin/apps/form-builder-v2/components/dnd/SortablePanelItem.tsx` (107 lines)
- `/src/react/admin/apps/form-builder-v2/components/dnd/index.ts` (exports)

## Mobile Drawer

### Overview (v6.6.0+)

Form Builder V2 uses a **custom MobileDrawer component** for all mobile bottom sheet UI patterns, replacing the Vaul library. This migration provides better control, eliminates external dependencies, and fixes critical scroll/keyboard issues.

**Migration completed:** 2025-12-12 (Vaul removed from package.json)

**Location:** `/src/react/admin/components/ui/mobile-drawer.tsx`

**Used by:**
- RightSidebar (settings panel on mobile)
- MobileMenu (canvas menu on mobile)

**Note:** FloatingPanel (element property editor) now uses PropertiesBottomTray on mobile instead of MobileDrawer (see PropertiesBottomTray section below).

### Why We Replaced Vaul

**Problems with Vaul snap points:**
1. Snap points use `translateY` transforms that push content below viewport
2. Scrollable content becomes unreachable (e.g., fields at 70% snap point are 199px below visible area)
3. Keyboard open/close doesn't adapt drawer height (Visual Viewport API not integrated)
4. Radix ScrollArea incompatibility with Vaul's touch event handling

**Benefits of custom solution:**
- Direct Visual Viewport API integration for keyboard-aware height
- No snap point transforms - drawer positioned at bottom with calculated height
- iOS-compatible body scroll lock (fixed positioning technique)
- Zero external dependencies - pure Tailwind CSS + React
- Full control over touch gestures and animations

### Architecture

**Component Interface:**
```typescript
interface MobileDrawerProps {
  /** Whether the drawer is open */
  open: boolean;
  /** Called when drawer should close */
  onClose: () => void;
  /** Fixed height in pixels, or use Visual Viewport API if not provided */
  height?: number;
  /** Accessible title for screen readers */
  title?: string;
  /** Accessible description for screen readers */
  description?: string;
  /** Content to render inside the drawer */
  children: React.ReactNode;
  /** Additional class names for the drawer content */
  className?: string;
  /** Test ID for the drawer */
  'data-testid'?: string;
}
```

**Iframe Portal Rendering (v6.6.0+):**

MobileDrawer uses `usePortalDocument()` hook to portal to the correct document:

```typescript
import { usePortalDocument } from '@/contexts/IframeContext';

export const MobileDrawer: React.FC<MobileDrawerProps> = ({ children, ...props }) => {
  const portalDocument = usePortalDocument();

  // Portal to iframe's document.body (not parent's document.body)
  return createPortal(
    <div className="drawer-content">{children}</div>,
    portalDocument.body
  );
};
```

This ensures the drawer renders in the isolated iframe context where CSS is properly scoped.

**Usage Example:**
```typescript
import { MobileDrawer } from '@/components/ui/mobile-drawer';

<MobileDrawer
  open={isOpen}
  onClose={() => setIsOpen(false)}
  title="Edit Element"              // Screen reader only
  description="Modify properties"    // Screen reader only (optional)
  height={500}                       // Optional: fixed height in px
  data-testid="floating-panel-drawer"
>
  <div className="flex-1 flex flex-col min-h-0 overflow-hidden">
    {/* Your content - use flex-1 and overflow for scrollable content */}
  </div>
</MobileDrawer>
```

### Visual Viewport API Integration

**Purpose:** Adapt drawer height when mobile keyboard opens/closes

**Supported browsers:** iOS 13+, Chrome 62+, Firefox 91+, Safari 13+ (fallback to `window.innerHeight` on older browsers)

**Implementation:**
```typescript
// Line 122-137 in mobile-drawer.tsx
useEffect(() => {
  if (!open) return;

  const updateHeight = () => {
    const vv = window.visualViewport;
    setViewportHeight(vv ? vv.height : window.innerHeight);
  };

  updateHeight();
  window.visualViewport?.addEventListener('resize', updateHeight);
  window.visualViewport?.addEventListener('scroll', updateHeight);

  return () => {
    window.visualViewport?.removeEventListener('resize', updateHeight);
    window.visualViewport?.removeEventListener('scroll', updateHeight);
  };
}, [open]);

// Calculate drawer height (70% of viewport or provided height)
const drawerHeight = height || Math.round(viewportHeight * 0.7);
```

**Flow:**
1. Drawer opens → Subscribe to `visualViewport.resize` and `visualViewport.scroll` events
2. Keyboard opens → Visual Viewport height decreases (e.g., from 844px to 400px on iPhone)
3. `updateHeight()` fires → `viewportHeight` state updates → Drawer height recalculates
4. Drawer shrinks to fit above keyboard → Content remains scrollable
5. Keyboard closes → Visual Viewport height increases → Drawer expands back to 70%

### Body Scroll Lock (iOS-Compatible)

**Problem:** When drawer is open, background page should not scroll

**Solution:** iOS-compatible fixed positioning technique

**Implementation:**
```typescript
// Line 88-116 in mobile-drawer.tsx
useEffect(() => {
  if (!open) return;

  const scrollY = window.scrollY;
  const body = document.body;
  const html = document.documentElement;

  // Store original styles
  const originalBodyStyle = body.style.cssText;
  const originalHtmlStyle = html.style.cssText;

  // Lock body scroll - iOS-compatible technique
  body.style.position = 'fixed';
  body.style.top = `-${scrollY}px`;  // Preserve scroll position
  body.style.left = '0';
  body.style.right = '0';
  body.style.overflow = 'hidden';
  body.style.transition = 'none';     // Disable transitions during lock
  html.style.overflow = 'hidden';
  html.style.scrollBehavior = 'auto'; // Disable smooth scroll
  html.style.transition = 'none';

  return () => {
    // Restore original styles and scroll position
    body.style.cssText = originalBodyStyle;
    html.style.cssText = originalHtmlStyle;
    window.scrollTo(0, scrollY);
  };
}, [open]);
```

**Why this works on iOS:**
- `position: fixed` with `top: -${scrollY}px` prevents scroll without layout shift
- Storing original `cssText` preserves any existing inline styles
- Restoring scroll position on close prevents jump to top
- Disabling transitions prevents any smooth scroll during lock/unlock

### Touch Swipe-to-Close

**Gesture:** Swipe down from drag handle to close drawer

**Threshold:** 100px vertical movement

**Implementation:**
```typescript
// Touch tracking refs
const touchStartY = useRef<number>(0);
const touchCurrentY = useRef<number>(0);
const isDragging = useRef(false);
const [dragOffset, setDragOffset] = useState(0);

// Handlers (lines 140-179)
const handleTouchStart = useCallback((e: React.TouchEvent) => {
  const touch = e.touches[0];
  const target = e.target as HTMLElement;

  // Only allow dragging from handle or header area
  if (target.closest('[data-drawer-handle]') || target.closest('[data-drawer-header]')) {
    touchStartY.current = touch.clientY;
    touchCurrentY.current = touch.clientY;
    isDragging.current = true;
  }
}, []);

const handleTouchMove = useCallback((e: React.TouchEvent) => {
  if (!isDragging.current) return;

  const touch = e.touches[0];
  touchCurrentY.current = touch.clientY;

  const diff = touchCurrentY.current - touchStartY.current;
  // Only allow dragging down (positive diff)
  if (diff > 0) {
    setDragOffset(diff);
  }
}, []);

const handleTouchEnd = useCallback(() => {
  if (!isDragging.current) return;

  isDragging.current = false;
  const diff = touchCurrentY.current - touchStartY.current;

  // If dragged more than 100px down, close the drawer
  if (diff > 100) {
    onClose();
  } else {
    // Snap back
    setDragOffset(0);
  }
}, [onClose]);
```

**Drag handle markup:**
```tsx
{/* Line 244-254 in mobile-drawer.tsx */}
<div
  className="flex justify-center pt-2 pb-1 shrink-0 cursor-grab active:cursor-grabbing"
  data-drawer-handle
  data-testid={`${testId}-handle`}
  role="separator"
  aria-orientation="horizontal"
  aria-label="Drag handle - swipe down to close"
>
  <div className="w-10 h-1 bg-muted-foreground/30 rounded-full" />
</div>
```

**Header area also draggable:**
```tsx
{/* Header div (line 60-65) includes data-drawer-header attribute */}
<div
  ref={headerRef}
  className={cn("bg-muted/50 border-b border-border shrink-0", !isMobile && "pt-1.5")}
  data-testid="floating-panel-header"
  data-drawer-header  // Enables dragging from header
>
```

### CSS Transitions & Animations

**Slide-up animation:**
```typescript
// Line 64-85 in mobile-drawer.tsx
useEffect(() => {
  if (open) {
    setIsVisible(true);
    // Double requestAnimationFrame ensures CSS transitions trigger correctly:
    // 1st RAF: Browser has painted the initial state (translateY 100%)
    // 2nd RAF: Now safe to change state, browser will animate the transition
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        setIsAnimating(true);
      });
    });
  } else {
    setIsAnimating(false);
    // Wait for animation to complete before hiding
    const timer = setTimeout(() => {
      setIsVisible(false);
      setDragOffset(0);
    }, 300);  // Matches CSS transition duration
    return () => clearTimeout(timer);
  }
}, [open]);
```

**Drawer element styling:**
```tsx
{/* Line 224-242 */}
<div
  ref={drawerRef}
  className={cn(
    'fixed bottom-0 left-0 right-0 bg-background rounded-t-xl flex flex-col overflow-hidden',
    'transition-transform duration-300 ease-out',
    // Disable transition when dragging for immediate feedback
    isDragging.current && 'transition-none',
    className
  )}
  style={{
    height: drawerHeight,
    transform: isAnimating
      ? `translateY(${dragOffset}px)`   // Open state (with drag offset)
      : `translateY(100%)`,              // Closed state (offscreen)
  }}
  onTouchStart={handleTouchStart}
  onTouchMove={handleTouchMove}
  onTouchEnd={handleTouchEnd}
>
```

**Why double requestAnimationFrame:**
- Without it, browser may batch both state changes into single paint (no animation)
- First RAF ensures initial state (translateY 100%) is painted
- Second RAF ensures next state change triggers CSS transition

### Accessibility

**ARIA attributes:**
```tsx
{/* Line 204-212 */}
<div
  className="fixed inset-0 z-50"
  role="dialog"
  aria-modal="true"
  aria-labelledby={title ? `${testId}-title` : undefined}
  aria-describedby={description ? `${testId}-description` : undefined}
  data-testid={testId}
>
```

**Screen reader only title/description:**
```tsx
{/* Line 256-266 */}
{title && (
  <span id={`${testId}-title`} className="sr-only">
    {title}
  </span>
)}
{description && (
  <span id={`${testId}-description`} className="sr-only">
    {description}
  </span>
)}
```

**Keyboard support:**
- Escape key closes drawer
- Focus trap (not implemented - content naturally traps focus due to modal nature)
- Drag handle has `role="separator"` and `aria-label`

**Close on Escape implementation:**
```typescript
// Line 189-200 in mobile-drawer.tsx
useEffect(() => {
  if (!open) return;

  const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
      onClose();
    }
  };

  document.addEventListener('keydown', handleKeyDown);
  return () => document.removeEventListener('keydown', handleKeyDown);
}, [open, onClose]);
```

### Portal Rendering

**Why portal to document.body:**
- Ensures drawer renders above all other content (z-index stacking)
- Prevents parent container overflow/clipping issues
- Allows backdrop to cover entire viewport

**Implementation:**
```tsx
// Line 204 in mobile-drawer.tsx
return createPortal(
  <div className="fixed inset-0 z-50">
    {/* Backdrop and drawer */}
  </div>,
  document.body
);
```

### RightSidebar and MobileMenu Integration

**Files:**
- `/src/react/admin/apps/form-builder-v2/components/ui/RightSidebar.tsx`
- `/src/react/admin/apps/form-builder-v2/components/MobileMenu.tsx`

**Usage pattern:**
```typescript
import { MobileDrawer } from '../../../../components/ui/mobile-drawer';

// Mobile render path
if (isMobile) {
  return (
    <MobileDrawer
      open={isOpen}
      onClose={onClose}
      title="Form Settings"
      description="Configure form behavior and appearance"
      data-testid="settings-drawer"
    >
      <div className="flex-1 flex flex-col min-h-0 overflow-hidden">
        {/* Settings content */}
      </div>
    </MobileDrawer>
  );
}

// Desktop render path
return (
  <div className="fixed right-0 top-0 h-full w-[360px] bg-background border-l">
    {/* Settings content */}
  </div>
);
```

### Testing Considerations

**Manual Testing Required:**
- iOS Safari - verify scroll lock works, keyboard adapts height
- Android Chrome - verify Visual Viewport API integration
- Swipe gesture - verify 100px threshold closes drawer
- Keyboard Escape - verify closes drawer
- Background scroll - verify locked when drawer open
- Drag from header - verify header area also initiates swipe

**Automated Testing:**
Data-testid attributes for Playwright/E2E tests:
- `data-testid="mobile-drawer"` (root element)
- `data-testid="mobile-drawer-overlay"` (backdrop)
- `data-testid="mobile-drawer-content"` (drawer container)
- `data-testid="mobile-drawer-handle"` (drag handle)

**Common Issues:**
- Drawer content not scrollable → Ensure child has `flex-1 flex flex-col min-h-0 overflow-hidden`
- Keyboard doesn't adapt height → Check Visual Viewport API support (iOS 13+)
- Background scrolls on iOS → Verify scroll lock cleanup on unmount
- Animation jank → Ensure double requestAnimationFrame for open transition

### Migration Guide (Vaul → MobileDrawer)

**Before (Vaul):**
```tsx
import { Drawer } from '@/components/ui/drawer';

<Drawer.Root open={open} onOpenChange={setOpen}>
  <Drawer.Portal>
    <Drawer.Overlay />
    <Drawer.Content>
      <Drawer.Handle />
      <Drawer.Title>Edit Element</Drawer.Title>
      <Drawer.Description>Modify properties</Drawer.Description>
      {children}
    </Drawer.Content>
  </Drawer.Portal>
</Drawer.Root>
```

**After (MobileDrawer):**
```tsx
import { MobileDrawer } from '@/components/ui/mobile-drawer';

<MobileDrawer
  open={open}
  onClose={() => setOpen(false)}
  title="Edit Element"              // Screen reader only
  description="Modify properties"    // Screen reader only
>
  {children}
</MobileDrawer>
```

**Key differences:**
- No nested components (Root/Portal/Content) - single component
- `onClose` callback instead of `onOpenChange(false)`
- Title/description are screen reader only (not visible)
- Drag handle included automatically
- No need for `data-vaul-no-drag` on scrollable content

**Removed from package.json:**
```diff
- "vaul": "^0.9.0"
```

**Deleted files:**
```
src/react/admin/components/ui/drawer.tsx  (Vaul wrapper)
```

### Performance Considerations

**Event Listener Cleanup:**
All event listeners (Visual Viewport, keyboard, touch) properly cleaned up in useEffect returns to prevent memory leaks.

**Conditional Rendering:**
Drawer only renders when `isVisible` state is true, preventing unnecessary DOM nodes when closed.

**Scroll Lock Efficiency:**
Original `cssText` stored and restored wholesale to avoid multiple style recalculations.

**Transition Optimization:**
Transitions disabled during drag (`transition-none` class) for immediate feedback, then re-enabled for smooth snap-back or close animation.

### Reference Implementation

**Complete source:** `/home/rens/super-forms/src/react/admin/components/ui/mobile-drawer.tsx` (279 lines)

**Key sections:**
- Lines 1-22: TypeScript interface and JSDoc
- Lines 24-32: Component documentation comment
- Lines 47-62: Visual Viewport height calculation
- Lines 64-85: Open/close transition logic (double RAF)
- Lines 88-116: iOS-compatible body scroll lock
- Lines 122-137: Visual Viewport API integration
- Lines 140-179: Touch swipe-to-close handlers
- Lines 189-200: Escape key handler
- Lines 204-275: JSX rendering (portal, backdrop, drawer, handle)

**Consumer files:**
- `/src/react/admin/apps/form-builder-v2/components/ui/RightSidebar.tsx`
- `/src/react/admin/apps/form-builder-v2/components/MobileMenu.tsx`

## PropertiesBottomTray

### Overview (v6.6.0+)

On mobile, element properties now use a **bottom tray pattern** instead of MobileDrawer. This provides better scroll containment, keyboard handling, and visual consistency with the elements palette tray.

**Location:** `/src/react/admin/apps/form-builder-v2/components/ui/overlays/PropertiesBottomTray.tsx`

**Migration date:** 2025-12-13

### Why Bottom Tray Instead of Drawer

**Problems with drawer pattern for element properties:**
1. Browser auto-scroll when focusing inputs conflicts with drawer scroll containers
2. Input fields at the bottom of drawer content become hard to reach
3. Visual inconsistency - elements palette uses bottom tray but properties used drawer
4. Keyboard opening/closing creates jarring height transitions

**Benefits of bottom tray:**
- **Scroll containment:** Content scrolls within tray, browser auto-scroll for inputs works correctly
- **Visual consistency:** Same UI pattern as ResizableBottomTray (elements palette)
- **Simpler height behavior:** Fixed max-height (70vh) without complex Visual Viewport calculations
- **Layered interface:** Properties tray overlays elements tray with higher z-index

### Architecture

**Component Interface:**
```typescript
interface PropertiesBottomTrayProps {
  /** ID of the element being edited */
  elementId: string;
  /** Whether the tray is collapsed (minimized) */
  isCollapsed: boolean;
  /** Called when collapse button clicked */
  onToggleCollapse: () => void;
  /** Called when tray should close (X button or element deselected) */
  onClose: () => void;
  /** Called when element property changes */
  onPropertyChange: (property: string, value: any) => void;
  /** Called when delete button clicked */
  onDelete: () => void;
}
```

**Usage Example:**
```typescript
import { PropertiesBottomTray } from '@/apps/form-builder-v2/components/ui/overlays';

// In FormBuilderV2.tsx - rendered when element selected on mobile
{isMobile && floatingPanel?.elementId && (
  <PropertiesBottomTray
    elementId={floatingPanel.elementId}
    isCollapsed={isPropertiesTrayCollapsed}
    onToggleCollapse={() => setIsPropertiesTrayCollapsed(!isPropertiesTrayCollapsed)}
    onClose={handleClosePanel}
    onPropertyChange={handlePropertyChange}
    onDelete={handleDeleteElement}
  />
)}
```

### Key Features

**Z-Index Layering:**
- Properties tray: `z-[60]`
- Elements tray: `z-[50]`
- Result: Properties tray overlays elements tray when open

**Show/Hide Logic:**
When properties tray is open (element selected):
- Properties tray visible
- Elements tray hidden (conditional rendering based on `floatingPanel?.elementId`)

When properties tray closed (no element selected):
- Properties tray hidden
- Elements tray visible

**Scroll Containment:**
```typescript
// Line 199 - Content area with overscroll-contain
<div className="flex-1 min-h-0 overflow-y-auto overscroll-contain">
  {/* Tab content panels */}
</div>
```
The `overscroll-contain` prevents scroll chaining - when user scrolls to bottom of properties, scroll doesn't escape to canvas.

**Header Structure:**
- Element icon + name + "Schema" badge (if applicable)
- Delete button (trash icon)
- Close button (X icon)

**Tab System:**
Full 6-tab interface matching FloatingPanel desktop:
1. **Content** - General properties (label, placeholder, etc.)
2. **Style** - Style overrides and appearance properties
3. **Behavior** - Validation, conditional logic
4. **Code** - Custom code, CSS classes
5. **Templates** - Element templates
6. **AI** - AI-powered element configuration

### FloatingPanel Integration

**File:** `/src/react/admin/apps/form-builder-v2/components/property-panels/FloatingPanel.tsx`

FloatingPanel now only renders on **desktop**. On mobile, FormBuilderV2 renders PropertiesBottomTray directly instead of wrapping FloatingPanel in a drawer.

**Desktop render path (FloatingPanel.tsx):**
```typescript
// Only renders when !isMobile
return (
  <div
    ref={panelRef}
    className="fixed bg-background border rounded-lg shadow-lg w-[480px] max-h-[600px] flex flex-col overflow-hidden z-50"
    style={{ left: clampedX, top: clampedY }}
    data-testid="floating-panel"
  >
    <PanelHeader />
    <Tabs>
      <TabNavigation />
      <TabContentPanels />
    </Tabs>
  </div>
);
```

**Mobile render path (FormBuilderV2.tsx):**
```typescript
// Lines 3899-3909 in FormBuilderV2.tsx
{isMobile && floatingPanel?.elementId && (
  <PropertiesBottomTray
    elementId={floatingPanel.elementId}
    isCollapsed={isPropertiesTrayCollapsed}
    onToggleCollapse={() => setIsPropertiesTrayCollapsed(!isPropertiesTrayCollapsed)}
    onClose={() => setFloatingPanel(null)}
    onPropertyChange={handlePropertyChange}
    onDelete={() => handleDeleteElement(floatingPanel.elementId)}
  />
)}

// FloatingPanel only renders when NOT mobile
{!isMobile && floatingPanel?.elementId && (
  <FloatingPanel
    elementId={floatingPanel.elementId}
    position={floatingPanel.position}
    onClose={() => setFloatingPanel(null)}
    onPropertyChange={handlePropertyChange}
    onDelete={handleDeleteElement}
  />
)}
```

### State Management

**FormBuilderV2 State:**
```typescript
// Shared state for both desktop FloatingPanel and mobile PropertiesBottomTray
const [floatingPanel, setFloatingPanel] = useState<{
  elementId: string | null;
  position: { x: number; y: number };
} | null>(null);

// Mobile-specific state for tray collapse
const [isPropertiesTrayCollapsed, setIsPropertiesTrayCollapsed] = useState(false);
```

**Element Selection Flow:**
1. User taps element on canvas
2. `handleSelectElement` sets `floatingPanel` state with element ID
3. On mobile: PropertiesBottomTray renders (elements tray hidden)
4. On desktop: FloatingPanel renders as floating panel

**Close Interactions:**
Properties tray closes when:
- User clicks X button (calls `onClose`)
- User clicks canvas background (`setFloatingPanel(null)`)
- User deletes element
- User presses Escape key

### Visual Structure

```
┌─────────────────────────────────────┐
│  ↓  (Chevron collapse button)       │ ← -top-4 positioned above tray
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Header (bg-muted/50)                │
│ 📝 Text Input [Schema]     🗑️  ✕   │ ← Icon, name, delete, close
├─────────────────────────────────────┤
│ Tabs (border-b)                     │
│ 📄 🎨 ⚙️ 💻 📋 ✨                    │ ← 6 icon-only tabs
├─────────────────────────────────────┤
│ Content (overflow-y-auto)           │
│ ┌─────────────────────────────────┐ │
│ │ Property controls...            │ │ ← Scrollable
│ │                                 │ │
│ │                                 │ │
│ └─────────────────────────────────┘ │
└─────────────────────────────────────┘
         max-h-[70vh]
```

### Testing Considerations

**Data-testid attributes:**
- `data-testid="properties-bottom-tray"` - Root element
- `data-testid="properties-tray-collapse-button"` - Collapse button
- `data-testid="properties-tray-header"` - Header section
- `data-testid="properties-tray-delete"` - Delete button
- `data-testid="properties-tray-close"` - Close button
- `data-testid="properties-tray-nav"` - Tab navigation
- `data-testid="properties-tray-content"` - Content area
- `data-testid="properties-tray-tab-{tabId}"` - Individual tabs
- `data-testid="properties-tray-tab-content-{tabId}"` - Tab content panels

**Manual Testing Required:**
- iOS Safari - verify scroll containment works, inputs accessible when keyboard open
- Android Chrome - verify tray doesn't hide behind keyboard
- Tap element - verify properties tray appears and elements tray hides
- Close properties - verify elements tray reappears
- Swipe between tabs - verify all 6 tabs render correctly
- Scroll long property list - verify scrolling contained to tray

### Reference Implementation

**Complete source:** `/home/rens/super-forms/src/react/admin/apps/form-builder-v2/components/ui/overlays/PropertiesBottomTray.tsx` (253 lines)

**Key sections:**
- Lines 1-12: Imports and tab configuration
- Lines 24-36: Component props and state
- Lines 44-72: Element data and style override handlers
- Lines 86-102: Tray container with z-index and height
- Lines 104-121: Collapse button
- Lines 126-167: Header with element info and action buttons
- Lines 170-195: Tab navigation
- Lines 198-248: Scrollable tab content panels

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
