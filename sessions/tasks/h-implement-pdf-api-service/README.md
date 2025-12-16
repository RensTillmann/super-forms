---
name: h-implement-pdf-api-service
branch: feature/h-implement-triggers-actions-extensibility
status: in-progress
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

- [x] PRD document complete and ready to send to Go developer
- [x] API endpoint specification defined (request/response schemas)
- [x] WordPress/PHP integration code written for Super Forms plugin
- [x] Authentication/licensing validation integrated (domain-based)
- [ ] End-to-end testing validates PDF quality and text selectability
- [x] Documentation for configuration options (page size, margins, headers/footers)
- [x] Rate limiting strategy defined (per license/site)
- [x] Error handling documented (Chrome crashes, timeouts, invalid HTML)
- [x] Usage tracking/logging for billing purposes

## Subtasks

| # | File | Status | Description |
|---|------|--------|-------------|
| 00 | [00-prd-golang-pdf-service.md](./00-prd-golang-pdf-service.md) | complete | PRD document for Go developer |
| 01 | [01-wordpress-integration.md](./01-wordpress-integration.md) | complete | PHP integration code for Super Forms |
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
                               │ { html, options, site_url }
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
  "site_url": "https://customer-site.com",  // License validated by domain lookup
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
  "error": "No valid PDF license found for this domain",
  "error_code": "LICENSE_NOT_FOUND"
}
```

## Context Manifest

> **Note:** Detailed technical specifications have been moved to dedicated documents:
> - **[00-prd-golang-pdf-service.md](./00-prd-golang-pdf-service.md)** - Complete PRD for Go PDF API service
> - **[01-wordpress-integration.md](./01-wordpress-integration.md)** - WordPress plugin integration guide
> - **[02-testing-validation.md](./02-testing-validation.md)** - Testing and validation procedures

### Key Architectural Decision: Domain-Based License Validation

License validation is performed **server-side** by the Go API service. The API looks up the requesting domain in the MongoDB `licenseCodes` collection. WordPress only sends `site_url` - no license key is stored or transmitted from the plugin.

**MongoDB Query Pattern:**
```javascript
db.licenseCodes.findOne({
  domain: extractedDomain,  // e.g., "example.com" from "https://example.com/path"
  slug: "pdf",
  used: true,
  $or: [
    { subscription_id: { $ne: "" } },  // Active subscription
    { expires: { $gt: Date.now() / 1000 } }  // Not expired
  ]
})
```

**WordPress Request Body:**
```php
$request_body = [
    'html'     => $html,
    'options'  => [...],
    'site_url' => home_url('/'),  // License validated by domain lookup
    'form_id'  => $context['form_id'] ?? null,
    // No license_key - validation is server-side
];
```

### How PDF Generation Currently Works in Super Forms

Super Forms currently implements a **hybrid PDF generation system** with two distinct approaches:

**1. Client-Side PDF Generation (Current Primary Method)**

When a user triggers PDF generation via a button click or form submission, the system operates as follows:

The PDF generator add-on (`/src/includes/extensions/pdf-generator/pdf-generator.php`) integrates at the form level by:
- Enqueuing JavaScript libraries (`super-html-canvas.min.js` and `super-pdf-gen.min.js`) when `_pdf.generate = 'true'` in form settings
- Embedding PDF configuration into the page via `SUPER.form_js[form_id]["_pdf"]` containing page format (A4/Letter/Legal), orientation (portrait/landscape), margins (header/body/footer in mm/pt/cm/in/px), language scripts (latin/unicode/cyrillic/arabic), and render settings (smartBreak, normalizeFonts, imageQuality, renderScale)

When the Generate File action (`SUPER_Action_Generate_File`) executes with `output_format = 'pdf'`:
- It checks if the context is a frontend event (`event_source = 'frontend'`) or button click (`event_id` starts with 'button.')
- For frontend contexts, it returns `client_render: true` with HTML content and configuration, allowing JavaScript to generate the PDF in the browser using html2canvas/jsPDF
- The system builds HTML from templates (custom HTML, entry summary table, or fields list), replaces `{field_name}` tags with actual values, and wraps in default PDF styles

**2. Server-Side PDF Generation (Fallback)**

For server-side automation contexts (scheduled tasks, webhook triggers), the action attempts PHP-based generation:
- First checks for TCPDF library (`class_exists('TCPDF')`)
- Falls back to DOMPDF if available (`class_exists('Dompdf\\Dompdf')`)
- If neither library exists, returns `WP_Error('pdf_not_available')` with guidance to install a library or use HTML/CSV formats

The TCPDF implementation creates a PDF object, writes HTML via `writeHTML()`, and saves to `/wp-content/uploads/super-forms-files/` directory with `.htaccess` protection. File metadata (URL, path, size) is returned in the action result.

**3. File Storage and Attachment Flow**

Generated files are stored in WordPress uploads directory at `/wp-content/uploads/super-forms-files/` with:
- Automatic directory creation via `wp_mkdir_p()`
- `.htaccess` file for directory listing protection: `Options -Indexes`
- Filename generation supporting `{tags}` like `{entry_id}`, `{date}`, `{time}`, field values, sanitized via `sanitize_file_name()`

The `upload_to_media` option triggers WordPress Media Library integration via `media_handle_sideload()`, returning an `attachment_id` for referencing the file in emails or other contexts.

PDF settings from the form's PDF tab include:
- Conditional generation based on field values (e.g., only generate if `{field} == 'yes'`)
- Email attachment configuration (adminEmail, confirmationEmail toggles)
- Entry storage control (excludeEntry to prevent saving in contact entries)
- User-facing download button (downloadBtn, downloadBtnText)
- Header/footer templates with `{pdf_page}` and `{pdf_total_pages}` tags

**Why This Approach Has Limitations:**

The client-side approach produces **rasterized PDFs** (images of text) rather than vector PDFs with selectable text. This results in:
- Larger file sizes (300KB+ for simple forms vs 50KB for vector PDFs)
- Non-searchable content (users can't copy/paste text)
- Poor print quality and accessibility issues
- Browser inconsistencies (Firefox vs Chrome rendering differences)

The server-side TCPDF/DOMPDF fallback has:
- Limited CSS support (no flexbox, grid, modern layouts)
- Font rendering issues with non-Latin scripts
- Complex installation requirements (Composer dependencies)
- Inconsistent HTML rendering compared to browser output

### Implementation Details

> **See dedicated documentation for complete implementation details:**
> - Go API service implementation → [00-prd-golang-pdf-service.md](./00-prd-golang-pdf-service.md)
> - WordPress plugin integration → [01-wordpress-integration.md](./01-wordpress-integration.md)
> - Testing procedures → [02-testing-validation.md](./02-testing-validation.md)

## User Notes

- Integrate with existing Go API router on api.super-forms.com
- Test via api.dev.super-forms.com
- License validation is domain-based (server-side lookup in MongoDB `licenseCodes` collection)
- No license key stored in WordPress - only `site_url` is sent
- Rate limiting per domain/site
- Chrome must be installed on the server (or use Docker with Chrome)
- PRD should be standalone document that can be sent to Go developer

## Work Log
<!-- Updated as work progresses -->

<!-- NOTE: Outdated Context Manifest content removed 2025-12-16.
     Implementation details now in dedicated documents:
     - 00-prd-golang-pdf-service.md
     - 01-wordpress-integration.md
     - 02-testing-validation.md
     Key change: License validation is domain-based (server-side MongoDB lookup),
     not license_key based. WordPress only sends site_url. -->
