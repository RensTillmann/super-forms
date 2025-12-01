# Super Forms - Project Documentation Hub

@sessions/CLAUDE.sessions.md

## Quick Navigation

- **[JavaScript & React](docs/CLAUDE.javascript.md)** - React admin UI, email builder, build workflow, TypeScript
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

**Key Directories:**
- `/src/react/admin/` - React admin UI (unified bundle)
- `/src/includes/` - PHP backend (automations, DAL, migrations)
- `/src/includes/automations/` - Automation system (actions, registry, REST controller)
- `/src/assets/` - Compiled JavaScript/CSS
- `/sessions/tasks/` - Active development tasks
- `/docs/` - Detailed documentation

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

## React Development Guidelines

- Use TypeScript for new components (`.tsx`/`.ts`)
- Use shadcn/ui components when possible
- For icons: Lucide React only
- Build location: `/src/react/admin/`
- Build output: `/src/assets/js/backend/admin.js`

**Build Commands:**
```bash
cd /home/rens/super-forms/src/react/admin
npm run watch    # Development mode
npm run build    # Production build
npm run typecheck # Type checking
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
https://f4d.nl/dev/wp-admin/?temp-login-token=15fa51a9ef0213164b1a88ab0f3e0e07d4dd8744ee7be20c462700e807cd6723&tl-site=638e72bbb24debc0
```

