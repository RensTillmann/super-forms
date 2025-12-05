# Super Forms - Project Documentation Hub

@sessions/CLAUDE.sessions.md

## Quick Navigation

- **[JavaScript & React](docs/CLAUDE.javascript.md)** - React admin UI, email builder, build workflow, TypeScript
- **[UI Style Guidelines](docs/CLAUDE.ui.md)** - Tailwind CSS v4, shadcn/ui, design tokens, component patterns
- **[PHP & WordPress](docs/CLAUDE.php.md)** - WordPress standards, security, coding conventions
- **[Development & Deployment](docs/CLAUDE.development.md)** - Build commands, wp-env, server access
- **[Testing & Quality](docs/CLAUDE.testing.md)** - Testing requirements, validation protocols

## Project Overview

Super Forms is a WordPress drag & drop form builder plugin.

**Tech Stack:**
- WordPress plugin (PHP 7.4+, WordPress 6.4+)
- React + TypeScript + Vite for admin UI
- Tailwind CSS v4 + shadcn/ui
- Lucide icons
- Action Scheduler for background jobs

**Key Features:**
- Cache-compatible forms (works with Varnish, Cloudflare, CDN caching)
- Origin/Referer CSRF protection with configurable modes and trusted origins
- Operations-based form editing (99% smaller payloads)
- Visual workflow automation builder

**Key Directories:**
- `/src/react/admin/` - React admin UI (unified bundle)
- `/src/react/admin/schemas/` - Schema-first architecture (tabs, toolbar, elements)
- `/src/includes/` - PHP backend (automations, DAL, migrations)
- `/src/includes/automations/` - Automation system (actions, registry, REST controller)
- `/src/assets/` - Compiled JavaScript/CSS
- `/sessions/tasks/` - Active development tasks
- `/docs/` - Detailed documentation
- `/docs/architecture/` - Architecture specifications (schema-first, REST API)

## Automation System

**Database Tables:**
- `wp_superforms_automations` - Main automations storage (renamed from `wp_superforms_triggers`)
- `wp_superforms_automation_actions` - Individual automation actions
- `wp_superforms_automation_logs` - Execution history
- `wp_superforms_automation_states` - Workflow state management

**Core Classes:**
- `SUPER_Automation_DAL` - Data access layer
- `SUPER_Automation_Registry` - Node and action registry
- `SUPER_Automation_REST_Controller` - REST API endpoints
- `SUPER_Automation_Executor` - Workflow execution engine

**REST API Endpoints:**
- `GET /super-forms/v1/automations` - List automations
- `POST /super-forms/v1/automations` - Create automation
- `GET /super-forms/v1/automations/{id}` - Get automation
- `PUT /super-forms/v1/automations/{id}` - Update automation
- `DELETE /super-forms/v1/automations/{id}` - Delete automation
- `POST /super-forms/v1/automations/{id}/execute` - Execute automation

**Node Categories:**
- **Triggers** - Events that start automations (Form Submitted, Entry Updated, etc.)
- **Actions** - Tasks performed by the system (Send Email, Create Entry, HTTP Request, etc.)
- **Conditions** - Branching logic (Field Comparison, A/B Test, etc.)
- **Control** - Flow utilities (Delay, Schedule, Stop Execution, etc.)

## Operations & Versioning System

**Database Tables:**
- `wp_superforms_form_versions` - Form version snapshots and operation history

**Core Classes:**
- `SUPER_Form_Operations` - JSON Patch (RFC 6902) operations handler
- `SUPER_Form_REST_Controller` - Forms REST API with operations support
- `SUPER_Form_DAL` - Form data access layer

**REST API Endpoints:**
- `POST /super-forms/v1/forms/{id}/operations` - Apply JSON Patch operations
- `GET /super-forms/v1/forms/{id}/versions` - List form versions
- `POST /super-forms/v1/forms/{id}/versions` - Create version snapshot
- `POST /super-forms/v1/forms/{id}/revert/{versionId}` - Revert to version

**Key Features:**
- Operations-based editing (2KB vs 200KB payloads)
- Undo/redo via operation inversion
- Git-like version control with snapshots
- AI/LLM integration path via MCP server
- Sub-second saves on slow hosting

## React Development Guidelines

- Use TypeScript for new components (`.tsx`/`.ts`)
- Use shadcn/ui components when possible
- For icons: Lucide React only
- Build location: `/src/react/admin/`
- Build outputs:
  - `/src/assets/js/backend/admin.js` (main admin bundle)
  - `/src/assets/js/backend/forms-list.js` (forms list page)
  - `/src/assets/css/backend/admin.css` (shared styles)

**REST API Integration Pattern:**
- Use `wp.apiFetch()` for all admin page operations
- Enqueue scripts with `array('wp-api-fetch')` dependency
- Authentication automatic via WordPress cookies
- No custom AJAX handlers needed
- See [docs/CLAUDE.php.md - React Admin Pages with REST API](docs/CLAUDE.php.md#react-admin-pages-with-rest-api)

**Build Commands:**
```bash
cd /home/rens/super-forms/src/react/admin

# Development mode
npm run watch              # Main admin bundle
npm run watch:forms-list   # Forms list page

# Production build
npm run build              # Build all bundles
npm run typecheck          # Type checking
```

## Email Builder Architecture

**Location:** `/src/react/admin/components/email-builder/`

The email builder is now a reusable component library within the admin bundle:
- Exported components from `email-builder/index.js`
- Used in Email v2 tab and SendEmailModal (workflow integration)
- Single unified build replaced dual-app architecture
- See [docs/CLAUDE.javascript.md](docs/CLAUDE.javascript.md) for details

## Visual Inspection

You can use Playwright MCP to connect to WordPress admin:
```
https://f4d.nl/dev/wp-admin/?temp-login-token=e743c1697521d5cd707a09eb3d09df5e15e6daf6dc11d8e63cde5f9f6865f05b&tl-site=eead01cda8edfe6e
```
- After login, navigate to Form Builder V2: `https://f4d.nl/dev/wp-admin/admin.php?page=super_form_v2`
- this is local pc, sync src to dev server via sync script, it contains the ssh details. you can use wp cli on dev server for sql