---
name: h-implement-generate-pdf-node
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-16
---

# Generate PDF Automation Node

## Problem/Goal

Create a dedicated "Generate PDF" automation node that replaces the old form-level [PDF] tab with a flexible, node-based approach in the automation system. This node should:

1. **Default Mode (`template_source: 'form'`)** - Generate the form itself as PDF, respecting element-level include/exclude settings
2. **Custom Mode (`template_source: 'custom'`)** - Provide three PDF builder canvases (header/page/footer) that work like the form canvas builder

The PDF builders should:
- Temporarily hide the form canvas when opened
- Display dedicated canvas for PDF layout (header, page, or footer)
- Allow dragging elements from the elements tray (same as form builder)
- Support per-page header/footer disable options for flexibility (e.g., cover pages without headers)

All settings from the old PDF extension tab should migrate to this node, plus new API integration for server-side vector PDF generation.

## Success Criteria

**Action Class & Settings:**
- [ ] `SUPER_Action_Generate_PDF` action class created with full settings schema
- [ ] All old PDF extension settings migrated (general, conditions, page dimensions, margins, render settings)
- [ ] Action registered in automation registry as dedicated "Generate PDF" node
- [ ] `template_source` setting with 'form' (default) and 'custom' modes

**PDF Builder UI:**
- [ ] Three PDF builder buttons shown when `template_source: 'custom'` (Header, Page, Footer)
- [ ] PDF canvas replaces form canvas temporarily when builder opened
- [ ] Elements tray works in PDF builders (same drag-and-drop as form builder)
- [ ] PDF Page Builder has per-page header/footer disable checkboxes
- [ ] PDF layouts stored per-automation in database

**Element Schema Updates:**
- [ ] Element schema includes `_pdf.include` / `_pdf.exclude` properties
- [ ] Column schema includes `_pdf.useAsHeader` / `_pdf.useAsFooter` properties
- [ ] Zod types updated for all PDF-related element settings
- [ ] Properties visible in element settings panel

**MCP Integration:**
- [ ] `configurePdfGeneration` tool for setting PDF node options
- [ ] `openPdfBuilder` tool for AI to trigger PDF builder mode
- [ ] `setPdfElementSettings` tool for element-level PDF settings

**API Integration:**
- [ ] Generate PDF node calls PDF API service when `pdf_generation_method: 'api'`
- [ ] Domain-based license validation working
- [ ] Fallback to client-side when API unavailable

## Subtasks

| # | File | Status | Description |
|---|------|--------|-------------|
| 00 | [00-action-class.md](./00-action-class.md) | pending | Create `SUPER_Action_Generate_PDF` with full settings schema |
| 01 | [01-pdf-builder-ui.md](./01-pdf-builder-ui.md) | pending | PDF canvas builder UI (header/page/footer) |
| 02 | [02-element-schema.md](./02-element-schema.md) | pending | Update element schemas for PDF settings |
| 03 | [03-mcp-integration.md](./03-mcp-integration.md) | pending | MCP tools for AI-assisted PDF generation |

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│  Generate PDF Node (SUPER_Action_Generate_PDF)                       │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ template_source: 'form' (default) | 'custom'                   │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                              │                                       │
│              ┌───────────────┴───────────────┐                      │
│              ▼                               ▼                       │
│  ┌─────────────────────┐        ┌─────────────────────────────────┐ │
│  │ template_source:    │        │ template_source: 'custom'       │ │
│  │ 'form'              │        │                                 │ │
│  │                     │        │ ┌─────────────────────────────┐ │ │
│  │ Uses form canvas    │        │ │ [Open PDF Header Builder]   │ │ │
│  │ with element        │        │ ├─────────────────────────────┤ │ │
│  │ include/exclude     │        │ │ [Open PDF Page Builder]     │ │ │
│  │ settings            │        │ │ □ Disable header for page   │ │ │
│  │                     │        │ │ □ Disable footer for page   │ │ │
│  │                     │        │ ├─────────────────────────────┤ │ │
│  └─────────────────────┘        │ │ [Open PDF Footer Builder]   │ │ │
│                                 │ └─────────────────────────────┘ │ │
│                                 └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PDF API Service (api.super-forms.com/v1/pdf/generate)               │
│  - Domain-based license validation                                   │
│  - Headless Chrome rendering                                         │
│  - Vector PDF output with selectable text                           │
└─────────────────────────────────────────────────────────────────────┘
```

## Old PDF Extension Settings to Migrate

| Category | Settings |
|----------|----------|
| **General** | generate, debug, native, filename, emailLabel, adminEmail, confirmationEmail, excludeEntry, downloadBtn, downloadBtnText, generatingText |
| **Conditions** | enabled, f1, logic, f2 (conditional generation) |
| **Page Dimensions** | orientation, format (a0-c9, letter, legal, custom), customFormat, unit |
| **Margins** | header (top/right/bottom/left), body (top/right/bottom/left), footer (top/right/bottom/left) |
| **Render Settings** | smartBreak, normalizeFonts, textRendering, language (20+ scripts), imageQuality, renderScale |

## Element-Level Settings to Update

| Element Type | Setting | Description |
|--------------|---------|-------------|
| All Elements | `_pdf.include` | Include element in PDF |
| All Elements | `_pdf.exclude` | Exclude element from PDF |
| Columns | `_pdf.useAsHeader` | Use column as PDF header |
| Columns | `_pdf.useAsFooter` | Use column as PDF footer |

## Context Manifest
<!-- Added by context-gathering agent -->

## User Notes

- Stay on current branch: `feature/h-implement-triggers-actions-extensibility`
- PDF Page Builder should have per-page options to disable header/footer (for cover pages, full-bleed content, etc.)
- PDF builders work like form canvas - elements tray, drag-and-drop, same UX
- Update schema/zod/mcp accordingly for all PDF-related element settings
- Integration with PDF API service from `h-implement-pdf-api-service` task

## Work Log
<!-- Updated as work progresses -->
