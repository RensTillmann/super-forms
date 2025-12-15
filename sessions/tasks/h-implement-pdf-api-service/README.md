---
name: h-implement-pdf-api-service
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-12-15
---

# PDF Generation API Service (Go + Chromedp)

## Problem/Goal

Build a high-quality PDF generation service on `api.super-forms.com` using Go + chromedp/rod for the Super Forms PDF add-on. This replaces/enhances the current client-side PDF generation with a server-side solution that produces:

- **Vector text PDFs** (selectable, searchable, small file sizes)
- **Pixel-perfect HTML/CSS rendering** via headless Chrome
- **Consistent output** across all users
- **Built-in header/footer** with page numbers
- **Full CSS support** (flexbox, grid, custom fonts)

The service will integrate with the existing Go API infrastructure at `api.super-forms.com` and be testable via `api.dev.super-forms.com`.

## Success Criteria

- [ ] PRD document complete and ready to send to Go developer
- [ ] API endpoint specification defined (request/response schemas)
- [ ] WordPress/PHP integration code written for Super Forms plugin
- [ ] Authentication/licensing validation integrated
- [ ] End-to-end testing validates PDF quality and text selectability
- [ ] Documentation for configuration options (page size, margins, headers/footers)
- [ ] Rate limiting strategy defined (per license/site)
- [ ] Error handling documented (Chrome crashes, timeouts, invalid HTML)
- [ ] Usage tracking/logging for billing purposes

## Subtasks

| # | File | Status | Description |
|---|------|--------|-------------|
| 00 | [00-prd-golang-pdf-service.md](./00-prd-golang-pdf-service.md) | pending | PRD document for Go developer |
| 01 | [01-wordpress-integration.md](./01-wordpress-integration.md) | pending | PHP integration code for Super Forms |
| 02 | [02-testing-validation.md](./02-testing-validation.md) | pending | Testing the integration end-to-end |

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    api.super-forms.com                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Existing Go Router                                       │  │
│  │  ├── /license/verify (existing)                          │  │
│  │  ├── /license/activate (existing)                        │  │
│  │  └── /v1/pdf/generate (NEW)  ←─────────────────────────┐ │  │
│  └──────────────────────────────────────────────────────────┘  │
│                              │                               │  │
│                              ▼                               │  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  PDF Worker (chromedp/rod)                               │  │
│  │  ├── Headless Chrome instance                            │  │
│  │  ├── HTML → PDF conversion                               │  │
│  │  └── Returns PDF bytes                                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                               ▲
                               │ POST /v1/pdf/generate
                               │ { html, options, license_key }
                               │
┌─────────────────────────────────────────────────────────────────┐
│  WordPress Plugin (Super Forms)                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ SUPER_Action_Generate_File                                │  │
│  │ └── Calls API when output_format = 'pdf'                 │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Technology Stack

| Component | Choice | Reason |
|-----------|--------|--------|
| **Language** | Go | Already running on api.super-forms.com |
| **Browser Control** | chromedp or rod | Chrome DevTools Protocol for PDF |
| **HTTP Router** | Existing | Integrate with current API router |
| **Chrome** | Headless Chrome | Must be installed on server |

## API Endpoint Design

```
POST /v1/pdf/generate

Request:
{
  "html": "<html>...</html>",
  "options": {
    "format": "A4",                    // A4, Letter, Legal, or [width, height] in inches
    "orientation": "portrait",         // portrait | landscape
    "margin": {
      "top": "10mm",
      "right": "10mm",
      "bottom": "10mm",
      "left": "10mm"
    },
    "displayHeaderFooter": true,
    "headerTemplate": "<div style='font-size:10px'>...</div>",
    "footerTemplate": "<div style='font-size:10px'>Page <span class='pageNumber'></span> of <span class='totalPages'></span></div>",
    "printBackground": true,
    "scale": 1.0,
    "preferCSSPageSize": false
  },
  "license_key": "xxx-xxx-xxx",
  "site_url": "https://customer-site.com",
  "form_id": 123
}

Response (success):
{
  "success": true,
  "pdf_base64": "JVBERi0xLjQK...",
  "pages": 3,
  "file_size": 45678
}

Response (error):
{
  "success": false,
  "error": "Invalid license key",
  "error_code": "LICENSE_INVALID"
}
```

## Context Manifest
<!-- Added by context-gathering agent -->

## User Notes

- Integrate with existing Go API router on api.super-forms.com
- Test via api.dev.super-forms.com
- Must validate PDF add-on license before generating
- Consider rate limiting per license/site
- Chrome must be installed on the server (or use Docker with Chrome)
- PRD should be standalone document that can be sent to Go developer

## Work Log
<!-- Updated as work progresses -->
