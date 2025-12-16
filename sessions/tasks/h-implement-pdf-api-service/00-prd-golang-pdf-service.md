# PDF Generation API Service - Product Requirements Document

**Document Version:** 1.0
**Date:** 2025-12-16
**Author:** Super Forms Development Team
**Target Audience:** Go Developer

---

## 1. Executive Summary

### What
Build a PDF generation microservice using Go and headless Chrome (chromedp) that converts HTML to high-quality vector PDFs.

### Why
The current Super Forms WordPress plugin generates PDFs client-side using html2canvas/jsPDF, which produces **rasterized PDFs** (images of text). This results in:
- Large file sizes (300KB+ for simple forms vs 50KB for vector)
- Non-searchable/non-selectable text
- Poor print quality and accessibility issues
- Inconsistent rendering across browsers

The new service produces **vector PDFs** with:
- Selectable, searchable, copyable text
- Smaller file sizes
- Consistent output regardless of user's browser
- Full CSS support (flexbox, grid, custom fonts)

### Who
Super Forms is a WordPress drag-and-drop form builder plugin with 50,000+ active installations. The PDF generation feature is used for creating form submission receipts, contracts, invoices, and certificates.

### Integration
This service integrates with the existing Go API infrastructure at `api.super-forms.com`. The WordPress plugin will call this endpoint when generating PDFs.

---

## 2. Technical Requirements

### Language & Framework
- **Go version:** 1.21 or higher
- **HTTP framework:** Integrate with existing API router (net/http, Gin, or Echo depending on current setup)
- **Browser automation:** [chromedp](https://github.com/chromedp/chromedp) (recommended) or [rod](https://github.com/go-rod/rod)

### Dependencies
```go
import (
    "context"
    "encoding/base64"
    "encoding/json"
    "net/http"
    "time"

    "github.com/chromedp/cdproto/page"
    "github.com/chromedp/chromedp"
)
```

### Chrome Requirements
- Headless Chrome must be installed on the server
- Recommended: Use Docker image `chromedp/headless-shell` or `zenika/alpine-chrome`
- Chrome flags: `--no-sandbox`, `--disable-gpu`, `--disable-dev-shm-usage`

### Resource Requirements
- **Memory:** 512MB minimum per Chrome instance, recommend 1GB
- **CPU:** 1 core minimum, recommend 2 cores for concurrent rendering
- **Disk:** Minimal (no persistent storage needed, PDFs returned as base64)

---

## 3. API Specification

### Endpoint
```
POST /v1/pdf/generate
```

### Request Headers
```
Content-Type: application/json
```

### Request Body
```json
{
  "html": "<!DOCTYPE html><html><head><style>body { font-family: Arial; }</style></head><body><h1>Invoice #123</h1><p>Thank you for your order.</p></body></html>",
  "options": {
    "format": "A4",
    "orientation": "portrait",
    "margin": {
      "top": "10mm",
      "right": "10mm",
      "bottom": "10mm",
      "left": "10mm"
    },
    "displayHeaderFooter": true,
    "headerTemplate": "<div style='font-size:10px; text-align:center; width:100%;'>Company Name</div>",
    "footerTemplate": "<div style='font-size:10px; text-align:center; width:100%;'>Page <span class='pageNumber'></span> of <span class='totalPages'></span></div>",
    "printBackground": true,
    "scale": 1.0,
    "preferCSSPageSize": false
  },
  "site_url": "https://customer-website.com",
  "form_id": 123
}
```

### Request Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `html` | string | Yes | Complete HTML document including `<!DOCTYPE>`, `<html>`, `<head>`, `<body>` tags |
| `options.format` | string | No | Page size: `A4`, `Letter`, `Legal`, `A3`, `A5`, or `[width, height]` in inches. Default: `A4` |
| `options.orientation` | string | No | `portrait` or `landscape`. Default: `portrait` |
| `options.margin` | object | No | Page margins with `top`, `right`, `bottom`, `left` as strings (e.g., `10mm`, `1in`, `72pt`) |
| `options.displayHeaderFooter` | bool | No | Enable header/footer templates. Default: `false` |
| `options.headerTemplate` | string | No | HTML template for page header. Special classes: `date`, `title`, `url`, `pageNumber`, `totalPages` |
| `options.footerTemplate` | string | No | HTML template for page footer. Same special classes as header |
| `options.printBackground` | bool | No | Print background colors/images. Default: `true` |
| `options.scale` | float | No | Scale factor 0.1-2.0. Default: `1.0` |
| `options.preferCSSPageSize` | bool | No | Use CSS `@page` size if defined. Default: `false` |
| `site_url` | string | Yes | Customer's WordPress site URL. Server validates license by looking up this domain in MongoDB. |
| `form_id` | int | No | Form ID for rate limiting and logging purposes |

### Success Response (200 OK)
```json
{
  "success": true,
  "pdf_base64": "JVBERi0xLjQKJeLjz9MKMSAwIG9iago8PAovVHlwZSAvQ2F0YWxvZwov...",
  "pages": 3,
  "file_size": 45678,
  "render_time_ms": 1234
}
```

### Error Responses

**No License Found (403 Forbidden)**
```json
{
  "success": false,
  "error": "No active license found for this domain",
  "error_code": "LICENSE_NOT_FOUND",
  "domain": "example.com",
  "purchase_url": "https://super-forms.com/pricing"
}
```

**License Expired (403 Forbidden)**
```json
{
  "success": false,
  "error": "License for this domain has expired",
  "error_code": "LICENSE_EXPIRED",
  "domain": "example.com",
  "expired_at": "2025-01-01T00:00:00Z",
  "renewal_url": "https://super-forms.com/account/licenses"
}
```

**Rate Limit Exceeded (429 Too Many Requests)**
```json
{
  "success": false,
  "error": "Rate limit exceeded",
  "error_code": "RATE_LIMIT_EXCEEDED",
  "retry_after": 120,
  "limit": 100,
  "remaining": 0,
  "reset_at": "2025-12-16T11:00:00Z"
}
```

Also include HTTP header: `Retry-After: 120`

**HTML Too Large (413 Payload Too Large)**
```json
{
  "success": false,
  "error": "HTML content exceeds maximum size of 5MB",
  "error_code": "PAYLOAD_TOO_LARGE",
  "max_size_bytes": 5242880,
  "actual_size_bytes": 6000000
}
```

**Render Timeout (500 Internal Server Error)**
```json
{
  "success": false,
  "error": "Chrome rendering timed out after 30 seconds",
  "error_code": "RENDER_TIMEOUT"
}
```

**Chrome Crashed (500 Internal Server Error)**
```json
{
  "success": false,
  "error": "Chrome process crashed during rendering",
  "error_code": "CHROME_CRASHED"
}
```

**Service Unavailable (503 Service Unavailable)**
```json
{
  "success": false,
  "error": "All render workers are busy, please retry",
  "error_code": "SERVICE_BUSY",
  "retry_after": 5
}
```

---

## 4. License Verification (Server-Side MongoDB Lookup)

### Overview

License validation is performed **server-side** by looking up the domain extracted from `site_url` in the existing `licenseCodes` MongoDB collection. The WordPress plugin does NOT send or store license keys - it only sends the site URL.

**IMPORTANT:** The `licenseCodes` collection is the **single source of truth** for all license data in the Super Forms API. This is the same collection used for user dashboard license management, ensuring consistency.

### Verification Flow

```
┌─────────────────────────────────────────────────────────────┐
│  Request arrives with site_url                              │
│  e.g., "https://customer-website.com/some/path"             │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  1. Extract domain from site_url                            │
│     "https://www.example.com/path" → "example.com"          │
│     Strip: protocol, www prefix, port, path                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Check in-memory cache (TTL: 5 minutes)                  │
│     Cache key: "pdf_license:example.com"                    │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              │ Cache hit?                     │
              ▼                               ▼
        ┌──────────┐                    ┌──────────┐
        │   YES    │                    │    NO    │
        └──────────┘                    └──────────┘
              │                               │
              │                               ▼
              │         ┌─────────────────────────────────────┐
              │         │  3. Query licenseCodes collection   │
              │         │     db.licenseCodes.findOne({       │
              │         │       domain: "example.com",        │
              │         │       slug: "pdf",                  │
              │         │       used: true                    │
              │         │     })                              │
              │         └─────────────────────────────────────┘
              │                               │
              │                               ▼
              │         ┌─────────────────────────────────────┐
              │         │  4. Cache result (5 min TTL)        │
              │         │     Cache null results too          │
              │         └─────────────────────────────────────┘
              │                               │
              └───────────────┬───────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  5. Validate license:                                       │
│     - License exists for domain + slug "pdf"                │
│     - used = true (license is activated)                    │
│     - Has active subscription OR expires > NOW()            │
│     - status != "canceled" (if subscription)                │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              │ Valid?                         │
              ▼                               ▼
        ┌──────────┐                    ┌──────────┐
        │   YES    │                    │    NO    │
        │ Continue │                    │ Return   │
        │ to PDF   │                    │ 403      │
        └──────────┘                    └──────────┘
```

### Existing licenseCodes Collection Schema

The `licenseCodes` collection already exists in the Super Forms MongoDB database. Each document represents a single license code (one document per license, one domain per license).

```javascript
// Collection: licenseCodes (EXISTING - single source of truth)
{
  _id: ObjectId("..."),
  user_id: ObjectId("..."),              // Owner's user ID
  txn: "cs_live_xxx",                    // Checkout session ID or "codecanyon/envato"
  email: "customer@example.com",
  code: "SF-PDF-XXXX-XXXX",              // License code string (for admin reference)
  domain: "example.com",                 // Single domain (empty if unused)
  lifetime: 1,                           // Years (1, 2, 3, etc.) - 0 for subscriptions
  slug: "pdf",                           // Addon slug: "pdf", "super-forms", etc.
  used: true,                            // true = activated on a domain
  expires: 1735689600,                   // Unix timestamp (0 for active subscriptions)
  created: ISODate("2025-01-01"),
  subscription_id: "sub_xxx",            // Non-empty = subscription license
  payment_method: "card",                // "card", "sepa_debit", "ideal", "paypal"
  reminded: 0,                           // Last expiry reminder timestamp

  // Subscription-specific fields
  sub_item_id: "si_xxx",                 // Stripe subscription item ID
  status: "active",                      // "active", "past_due", "canceled"
  billing_cycle: "yearly",               // "yearly", "monthly"
  plan: "price_xxx"                      // Stripe price ID
}
```

### Go Implementation (Using Existing models.LicenseCodes)

```go
import "github.com/RensTillmann/api.super-forms.com/models"

// findPDFLicenseByDomain queries licenseCodes for an active PDF license
func findPDFLicenseByDomain(ctx context.Context, domain string) (*models.LicenseCodes, error) {
    // Check cache first
    cacheKey := "pdf_license:" + domain
    if cached, ok := pdfLicenseCache.Get(cacheKey); ok {
        if cached == nil {
            return nil, ErrLicenseNotFound // Negative cache hit
        }
        return cached.(*models.LicenseCodes), nil
    }

    // Query licenseCodes collection
    // A valid PDF license is:
    //   - domain matches
    //   - slug = "pdf"
    //   - used = true (activated)
    //   - Either has subscription_id (auto-renews) OR expires > now
    collection := mongoClient.Database("super_forms").Collection("licenseCodes")

    now := time.Now().Unix()
    filter := bson.M{
        "domain": domain,
        "slug":   "pdf",
        "used":   true,
        "$or": []bson.M{
            // Active subscription (subscription_id is not empty)
            {"subscription_id": bson.M{"$ne": ""}},
            // OR one-time purchase that hasn't expired
            {"expires": bson.M{"$gt": now}},
        },
    }

    var license models.LicenseCodes
    err := collection.FindOne(ctx, filter).Decode(&license)

    if err == mongo.ErrNoDocuments {
        // Check if license exists but is expired
        expiredFilter := bson.M{
            "domain": domain,
            "slug":   "pdf",
            "used":   true,
        }
        var expiredLicense models.LicenseCodes
        if collection.FindOne(ctx, expiredFilter).Decode(&expiredLicense) == nil {
            // License exists but expired
            pdfLicenseCache.Set(cacheKey, nil, 5*time.Minute)
            return nil, ErrLicenseExpired
        }
        // No license found at all
        pdfLicenseCache.Set(cacheKey, nil, 5*time.Minute)
        return nil, ErrLicenseNotFound
    }
    if err != nil {
        return nil, err
    }

    // Check subscription status (subscriptions can be past_due or canceled)
    if license.SubscriptionID != "" && license.Status == "canceled" {
        pdfLicenseCache.Set(cacheKey, nil, 5*time.Minute)
        return nil, ErrLicenseSuspended
    }

    // Cache positive result
    pdfLicenseCache.Set(cacheKey, &license, 5*time.Minute)
    return &license, nil
}

// extractDomain extracts the normalized domain from a URL
func extractDomain(siteURL string) string {
    u, err := url.Parse(siteURL)
    if err != nil {
        return siteURL
    }

    host := u.Hostname()

    // Strip www. prefix
    host = strings.TrimPrefix(host, "www.")

    return strings.ToLower(host)
}

// isLocalhost - skip license check for development (ENV=dev only)
func isLocalhost(domain string) bool {
    return domain == "localhost" ||
           strings.HasPrefix(domain, "127.0.0.1") ||
           strings.HasPrefix(domain, "192.168.") ||
           strings.HasPrefix(domain, "10.") ||
           domain == "::1"
}
```

### License Validation in Handler

```go
func PDFGenerateHandler(c *gin.Context) {
    var req PDFRequest
    if err := c.ShouldBindJSON(&req); err != nil {
        c.JSON(400, gin.H{"success": false, "error": err.Error(), "error_code": "INVALID_REQUEST"})
        return
    }

    // Extract domain from site_url
    domain := extractDomain(req.SiteURL)

    // Skip license check for localhost in dev mode
    var license *models.LicenseCodes
    if os.Getenv("ENV") == "dev" && isLocalhost(domain) {
        // Dev mode localhost bypass
        license = &models.LicenseCodes{Slug: "pdf", Domain: domain}
    } else {
        // Production: validate license
        var err error
        license, err = findPDFLicenseByDomain(c.Request.Context(), domain)
        if err != nil {
            switch err {
            case ErrLicenseNotFound:
                c.JSON(403, gin.H{
                    "success":      false,
                    "error":        "No active PDF license found for this domain",
                    "error_code":   "LICENSE_NOT_FOUND",
                    "domain":       domain,
                    "purchase_url": "https://super-forms.com/pricing",
                })
            case ErrLicenseExpired:
                c.JSON(403, gin.H{
                    "success":     false,
                    "error":       "PDF license for this domain has expired",
                    "error_code":  "LICENSE_EXPIRED",
                    "domain":      domain,
                    "renewal_url": "https://dashboard.super-forms.com/licenses",
                })
            case ErrLicenseSuspended:
                c.JSON(403, gin.H{
                    "success":    false,
                    "error":      "PDF license has been suspended",
                    "error_code": "LICENSE_SUSPENDED",
                    "domain":     domain,
                })
            default:
                c.JSON(500, gin.H{
                    "success":    false,
                    "error":      "License validation error",
                    "error_code": "INTERNAL_ERROR",
                })
            }
            return
        }
    }

    // Continue with PDF generation...
    // license.ID can be used for rate limiting and logging
}
```

### Existing Index (Already Present)

The `licenseCodes` collection should already have an index on `domain`. If not, create:

```javascript
// Compound index for PDF license lookup
db.licenseCodes.createIndex({ "domain": 1, "slug": 1, "used": 1 })

// Existing index (verify exists)
db.licenseCodes.createIndex({ "domain": 1 })
```

---

## 5. Rate Limiting

### Integration with Existing Rate Limiting System

The Super Forms API already has a **sliding window counter algorithm** using MongoDB's `rate_limits` collection. The PDF endpoint should integrate with this existing system.

### Add to RateLimitConfigs Map

Add a new entry to the existing `RateLimitConfigs` map in `main.go`:

```go
// In main.go - add to existing RateLimitConfigs map
var RateLimitConfigs = map[string]RateLimitConfig{
    // ... existing configs ...

    // PDF Generation - moderate limit (resource-intensive operation)
    "pdf_generate": {
        Limit:   30,           // 30 requests per minute
        Window:  time.Minute,
        KeyType: "ip",         // IP-based for unauthenticated endpoint
    },
}
```

### Rate Limit Tiers (Future Enhancement)

For tier-based limits, query the license and adjust limits:

| Scope | Standard | Pro | Agency |
|-------|----------|-----|--------|
| Per domain/minute | 10 | 30 | 100 |
| Per domain/hour | 100 | 500 | 2,000 |
| Per domain/day | 500 | 2,000 | 10,000 |

### Implementation Using Existing RateLimitMiddleware

```go
// Option 1: Use existing middleware (recommended)
v1.POST("/pdf/generate",
    RateLimitMiddleware("pdf_generate", mongoClient),
    PDFGenerateHandler,
)

// Option 2: Custom rate limiting by domain (for tier-based limits)
func PDFRateLimitMiddleware() gin.HandlerFunc {
    return func(c *gin.Context) {
        // Parse request to get domain
        var req PDFRequest
        if err := c.ShouldBindJSON(&req); err != nil {
            c.JSON(400, gin.H{"success": false, "error": "Invalid request"})
            c.Abort()
            return
        }

        domain := extractDomain(req.SiteURL)

        // Use existing CheckRateLimit function
        key := "pdf:" + domain
        config := RateLimitConfigs["pdf_generate"]

        allowed, remaining, resetAt := CheckRateLimit(c.Request.Context(), key, config.Limit, config.Window)

        // Set rate limit headers
        c.Header("X-RateLimit-Limit", strconv.Itoa(config.Limit))
        c.Header("X-RateLimit-Remaining", strconv.Itoa(remaining))
        c.Header("X-RateLimit-Reset", strconv.FormatInt(resetAt.Unix(), 10))

        if !allowed {
            retryAfter := int(time.Until(resetAt).Seconds())
            c.Header("Retry-After", strconv.Itoa(retryAfter))
            c.JSON(429, gin.H{
                "success":     false,
                "error":       "Rate limit exceeded",
                "error_code":  "RATE_LIMIT_EXCEEDED",
                "retry_after": retryAfter,
                "limit":       config.Limit,
                "remaining":   0,
                "reset_at":    resetAt.Format(time.RFC3339),
            })
            c.Abort()
            return
        }

        // Re-bind the request body for the handler
        c.Set("pdf_request", &req)
        c.Next()
    }
}
```

### Rate Limit Headers

Include in all responses (consistent with existing API patterns):
```
X-RateLimit-Limit: 30
X-RateLimit-Remaining: 25
X-RateLimit-Reset: 1702725600
```

---

## 6. Chrome/Chromedp Usage

### Basic PDF Generation

```go
func generatePDF(ctx context.Context, html string, opts *PDFOptions) ([]byte, int, error) {
    // Create context with timeout
    ctx, cancel := context.WithTimeout(ctx, 30*time.Second)
    defer cancel()

    // Create Chrome context
    allocCtx, allocCancel := chromedp.NewExecAllocator(ctx,
        append(chromedp.DefaultExecAllocatorOptions[:],
            chromedp.Flag("no-sandbox", true),
            chromedp.Flag("disable-gpu", true),
            chromedp.Flag("disable-dev-shm-usage", true),
            chromedp.Flag("disable-software-rasterizer", true),
        )...,
    )
    defer allocCancel()

    chromeCtx, chromeCancel := chromedp.NewContext(allocCtx)
    defer chromeCancel()

    // Convert HTML to data URL
    dataURL := "data:text/html;base64," + base64.StdEncoding.EncodeToString([]byte(html))

    var pdfBuf []byte

    // Navigate and generate PDF
    err := chromedp.Run(chromeCtx,
        chromedp.Navigate(dataURL),
        chromedp.WaitReady("body", chromedp.ByQuery),
        chromedp.ActionFunc(func(ctx context.Context) error {
            var err error
            pdfBuf, _, err = page.PrintToPDF().
                WithPaperWidth(opts.PaperWidth).
                WithPaperHeight(opts.PaperHeight).
                WithMarginTop(opts.MarginTop).
                WithMarginBottom(opts.MarginBottom).
                WithMarginLeft(opts.MarginLeft).
                WithMarginRight(opts.MarginRight).
                WithDisplayHeaderFooter(opts.DisplayHeaderFooter).
                WithHeaderTemplate(opts.HeaderTemplate).
                WithFooterTemplate(opts.FooterTemplate).
                WithPrintBackground(opts.PrintBackground).
                WithScale(opts.Scale).
                WithPreferCSSPageSize(opts.PreferCSSPageSize).
                WithLandscape(opts.Landscape).
                Do(ctx)
            return err
        }),
    )

    if err != nil {
        return nil, 0, fmt.Errorf("chrome error: %w", err)
    }

    // Count pages (parse PDF to find page count)
    pageCount := countPDFPages(pdfBuf)

    return pdfBuf, pageCount, nil
}
```

### PDF Options Mapping

```go
type PDFOptions struct {
    PaperWidth          float64 // inches (A4 = 8.27)
    PaperHeight         float64 // inches (A4 = 11.69)
    MarginTop           float64 // inches
    MarginBottom        float64 // inches
    MarginLeft          float64 // inches
    MarginRight         float64 // inches
    DisplayHeaderFooter bool
    HeaderTemplate      string
    FooterTemplate      string
    PrintBackground     bool
    Scale               float64
    PreferCSSPageSize   bool
    Landscape           bool
}

func parseOptions(req *PDFRequest) *PDFOptions {
    opts := &PDFOptions{
        PrintBackground: true,
        Scale:           1.0,
    }

    // Paper size
    switch strings.ToLower(req.Options.Format) {
    case "letter":
        opts.PaperWidth, opts.PaperHeight = 8.5, 11
    case "legal":
        opts.PaperWidth, opts.PaperHeight = 8.5, 14
    case "a3":
        opts.PaperWidth, opts.PaperHeight = 11.69, 16.54
    case "a5":
        opts.PaperWidth, opts.PaperHeight = 5.83, 8.27
    default: // A4
        opts.PaperWidth, opts.PaperHeight = 8.27, 11.69
    }

    // Orientation
    if strings.ToLower(req.Options.Orientation) == "landscape" {
        opts.Landscape = true
        opts.PaperWidth, opts.PaperHeight = opts.PaperHeight, opts.PaperWidth
    }

    // Margins (convert mm/cm/pt/in to inches)
    opts.MarginTop = parseMargin(req.Options.Margin.Top, 0.4)     // default 10mm
    opts.MarginRight = parseMargin(req.Options.Margin.Right, 0.4)
    opts.MarginBottom = parseMargin(req.Options.Margin.Bottom, 0.4)
    opts.MarginLeft = parseMargin(req.Options.Margin.Left, 0.4)

    // Header/Footer
    opts.DisplayHeaderFooter = req.Options.DisplayHeaderFooter
    opts.HeaderTemplate = req.Options.HeaderTemplate
    opts.FooterTemplate = req.Options.FooterTemplate

    // Other options
    if req.Options.PrintBackground != nil {
        opts.PrintBackground = *req.Options.PrintBackground
    }
    if req.Options.Scale > 0 {
        opts.Scale = req.Options.Scale
    }
    opts.PreferCSSPageSize = req.Options.PreferCSSPageSize

    return opts
}

func parseMargin(margin string, defaultInches float64) float64 {
    if margin == "" {
        return defaultInches
    }
    // Parse "10mm", "1in", "72pt", "1cm"
    margin = strings.TrimSpace(strings.ToLower(margin))

    var value float64
    var unit string
    fmt.Sscanf(margin, "%f%s", &value, &unit)

    switch unit {
    case "mm":
        return value / 25.4
    case "cm":
        return value / 2.54
    case "pt":
        return value / 72
    case "in", "":
        return value
    default:
        return defaultInches
    }
}
```

### Wait for Complete Render

For pages with web fonts or images, wait for network idle:

```go
// Wait for fonts and images to load
chromedp.ActionFunc(func(ctx context.Context) error {
    // Wait up to 5 seconds for network to be idle
    return chromedp.Poll(`
        new Promise((resolve) => {
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(() => resolve(true));
            } else {
                resolve(true);
            }
        })
    `, nil, chromedp.WithPollingTimeout(5*time.Second)).Do(ctx)
}),
```

---

## 7. Performance Optimization

### Chrome Instance Pool

Reuse Chrome browser instances instead of launching new ones per request:

```go
type ChromePool struct {
    pool    chan *chromedp.Context
    maxSize int
    mu      sync.Mutex
}

func NewChromePool(size int) (*ChromePool, error) {
    p := &ChromePool{
        pool:    make(chan *chromedp.Context, size),
        maxSize: size,
    }

    // Pre-warm pool with instances
    for i := 0; i < size; i++ {
        ctx, err := p.createInstance()
        if err != nil {
            return nil, err
        }
        p.pool <- ctx
    }

    return p, nil
}

func (p *ChromePool) Acquire(ctx context.Context) (*chromedp.Context, error) {
    select {
    case instance := <-p.pool:
        return instance, nil
    case <-ctx.Done():
        return nil, ctx.Err()
    case <-time.After(60 * time.Second):
        return nil, ErrPoolExhausted
    }
}

func (p *ChromePool) Release(instance *chromedp.Context) {
    // Check if instance is still healthy
    if p.isHealthy(instance) {
        p.pool <- instance
    } else {
        // Replace with new instance
        chromedp.Cancel(*instance)
        newInstance, _ := p.createInstance()
        p.pool <- newInstance
    }
}
```

### Recommended Pool Settings

| Server Size | Chrome Instances | Max Concurrent Renders |
|-------------|------------------|------------------------|
| Small (2GB RAM) | 2 | 2 |
| Medium (4GB RAM) | 5 | 5 |
| Large (8GB RAM) | 10 | 10 |
| XL (16GB RAM) | 20 | 20 |

### Request Queue

When all Chrome instances are busy:

```go
type RenderQueue struct {
    queue   chan *RenderJob
    workers int
}

type RenderJob struct {
    Request  *PDFRequest
    Response chan *PDFResponse
    Ctx      context.Context
}

func (q *RenderQueue) Submit(ctx context.Context, req *PDFRequest) (*PDFResponse, error) {
    job := &RenderJob{
        Request:  req,
        Response: make(chan *PDFResponse, 1),
        Ctx:      ctx,
    }

    select {
    case q.queue <- job:
        // Job accepted, wait for response
        select {
        case resp := <-job.Response:
            return resp, nil
        case <-ctx.Done():
            return nil, ctx.Err()
        }
    case <-time.After(10 * time.Second):
        return nil, ErrQueueFull
    case <-ctx.Done():
        return nil, ctx.Err()
    }
}
```

---

## 8. Logging and Monitoring

### Request Logging

Log every request using the existing `LogInfo()`/`LogError()` pattern. Logs are stored in MongoDB `logs` collection with automatic 30-day cleanup.

```go
type PDFRequestLog struct {
    Timestamp     time.Time `json:"timestamp"`
    RequestID     string    `json:"request_id"`
    Domain        string    `json:"domain"`           // Extracted from site_url
    LicenseID     string    `json:"license_id"`       // MongoDB ObjectID from licenseCodes
    UserID        string    `json:"user_id"`          // Owner of the license
    SiteURL       string    `json:"site_url"`
    FormID        int       `json:"form_id"`
    HTMLSize      int       `json:"html_size_bytes"`
    PDFSize       int       `json:"pdf_size_bytes"`
    PageCount     int       `json:"page_count"`
    RenderTimeMS  int64     `json:"render_time_ms"`
    Success       bool      `json:"success"`
    ErrorCode     string    `json:"error_code,omitempty"`
    ErrorMessage  string    `json:"error_message,omitempty"`
    HasSubscription bool    `json:"has_subscription"`  // subscription_id != ""
}

func logPDFRequest(log *PDFRequestLog) {
    // Use existing LogInfo/LogError for consistency
    metadata := map[string]interface{}{
        "domain":        log.Domain,
        "license_id":    log.LicenseID,
        "form_id":       log.FormID,
        "html_size":     log.HTMLSize,
        "pdf_size":      log.PDFSize,
        "page_count":    log.PageCount,
        "render_time":   log.RenderTimeMS,
        "subscription":  log.HasSubscription,
    }

    if log.Success {
        LogInfo("PDF generated successfully", log.Domain, log.UserID, metadata)
    } else {
        LogError("PDF generation failed: "+log.ErrorMessage, log.Domain, log.UserID, metadata)
    }
}
```

### Metrics to Track

```go
var (
    pdfRequestsTotal = prometheus.NewCounterVec(
        prometheus.CounterOpts{
            Name: "pdf_requests_total",
            Help: "Total PDF generation requests",
        },
        []string{"status", "plan_tier"},
    )

    pdfRenderDuration = prometheus.NewHistogramVec(
        prometheus.HistogramOpts{
            Name:    "pdf_render_duration_seconds",
            Help:    "PDF render duration in seconds",
            Buckets: []float64{0.5, 1, 2, 5, 10, 30},
        },
        []string{"plan_tier"},
    )

    pdfFileSizeBytes = prometheus.NewHistogramVec(
        prometheus.HistogramOpts{
            Name:    "pdf_file_size_bytes",
            Help:    "Generated PDF file size in bytes",
            Buckets: []float64{10000, 50000, 100000, 500000, 1000000, 5000000},
        },
        []string{"plan_tier"},
    )

    chromePoolSize = prometheus.NewGauge(
        prometheus.GaugeOpts{
            Name: "chrome_pool_available",
            Help: "Available Chrome instances in pool",
        },
    )
)
```

### Alerts

Configure alerts for:

| Condition | Threshold | Severity |
|-----------|-----------|----------|
| Error rate | >5% in 5 minutes | Warning |
| Error rate | >10% in 5 minutes | Critical |
| P95 latency | >10 seconds | Warning |
| P99 latency | >30 seconds | Critical |
| Chrome pool exhausted | 0 available for 30s | Critical |
| Memory usage | >90% | Warning |

### Billing Aggregation

For monthly billing, aggregate by license key:

```sql
-- Monthly usage per license
SELECT
    license_key,
    DATE_TRUNC('month', timestamp) as month,
    COUNT(*) as total_pdfs,
    SUM(pdf_size_bytes) as total_bytes,
    AVG(render_time_ms) as avg_render_time
FROM pdf_request_logs
WHERE success = true
GROUP BY license_key, DATE_TRUNC('month', timestamp);
```

---

## 9. Security Considerations

### Input Validation

```go
func validateRequest(req *PDFRequest) error {
    // HTML size limit (5MB)
    if len(req.HTML) > 5*1024*1024 {
        return &APIError{
            Code:    "PAYLOAD_TOO_LARGE",
            Message: "HTML content exceeds maximum size of 5MB",
            Status:  413,
        }
    }

    // HTML must be present
    if strings.TrimSpace(req.HTML) == "" {
        return &APIError{
            Code:    "INVALID_HTML",
            Message: "HTML content is required",
            Status:  400,
        }
    }

    // HTML must be valid (basic check)
    if !strings.Contains(strings.ToLower(req.HTML), "<html") {
        return &APIError{
            Code:    "INVALID_HTML",
            Message: "HTML must contain <html> tag",
            Status:  400,
        }
    }

    // Site URL must be present and valid
    if strings.TrimSpace(req.SiteURL) == "" {
        return &APIError{
            Code:    "INVALID_SITE_URL",
            Message: "Site URL is required",
            Status:  400,
        }
    }

    parsedURL, err := url.Parse(req.SiteURL)
    if err != nil || parsedURL.Host == "" {
        return &APIError{
            Code:    "INVALID_SITE_URL",
            Message: "Site URL is not a valid URL",
            Status:  400,
        }
    }

    // Scale must be in valid range (if provided)
    if req.Options.Scale != 0 && (req.Options.Scale < 0.1 || req.Options.Scale > 2.0) {
        return &APIError{
            Code:    "INVALID_SCALE",
            Message: "Scale must be between 0.1 and 2.0",
            Status:  400,
        }
    }

    return nil
}
```

### Chrome Sandboxing

Chrome's built-in sandbox prevents JavaScript in the HTML from accessing the host system. Additional measures:

1. **No network access** - Chrome is isolated; external resources won't load
2. **Timeout** - Hard 30-second timeout prevents infinite loops
3. **Memory limit** - Kill Chrome if memory exceeds 1GB

### HTTPS Only

- Reject requests over HTTP in production
- Use TLS 1.2+ with strong ciphers
- Validate SSL certificates

### Request Signing (Optional)

For additional security, implement HMAC signing:

```go
// WordPress plugin generates signature:
// $signature = hash_hmac('sha256', $html . $timestamp, $api_secret);

func verifySignature(req *PDFRequest, signature, timestamp string) bool {
    // Check timestamp is recent (within 5 minutes)
    ts, _ := strconv.ParseInt(timestamp, 10, 64)
    if time.Now().Unix()-ts > 300 {
        return false
    }

    // Verify HMAC
    secret := getLicenseSecret(req.LicenseKey)
    expected := hmac.New(sha256.New, []byte(secret))
    expected.Write([]byte(req.HTML + timestamp))

    return hmac.Equal([]byte(signature), expected.Sum(nil))
}
```

---

## 10. Deployment

### Docker Image

```dockerfile
FROM chromedp/headless-shell:latest

# Install Go binary
COPY pdf-service /usr/local/bin/pdf-service

# Configure Chrome
ENV CHROME_BIN=/headless-shell/headless-shell

# Run as non-root
RUN adduser -D -u 1000 pdfuser
USER pdfuser

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

ENTRYPOINT ["/usr/local/bin/pdf-service"]
```

### Kubernetes Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: pdf-service
spec:
  replicas: 3
  selector:
    matchLabels:
      app: pdf-service
  template:
    metadata:
      labels:
        app: pdf-service
    spec:
      containers:
      - name: pdf-service
        image: super-forms/pdf-service:latest
        ports:
        - containerPort: 8080
        resources:
          requests:
            memory: "1Gi"
            cpu: "500m"
          limits:
            memory: "2Gi"
            cpu: "2000m"
        env:
        - name: CHROME_POOL_SIZE
          value: "5"
        - name: DATABASE_URL
          valueFrom:
            secretKeyRef:
              name: pdf-service-secrets
              key: database-url
        livenessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 10
          periodSeconds: 30
        readinessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 5
          periodSeconds: 10
---
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: pdf-service-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: pdf-service
  minReplicas: 2
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
```

### Health Check Endpoint

```go
func healthHandler(w http.ResponseWriter, r *http.Request) {
    // Check Chrome is responsive
    ctx, cancel := context.WithTimeout(r.Context(), 5*time.Second)
    defer cancel()

    instance, err := chromePool.Acquire(ctx)
    if err != nil {
        w.WriteHeader(http.StatusServiceUnavailable)
        json.NewEncoder(w).Encode(map[string]string{
            "status": "unhealthy",
            "error":  "chrome pool unavailable",
        })
        return
    }
    chromePool.Release(instance)

    // Check database connection
    if err := db.Ping(); err != nil {
        w.WriteHeader(http.StatusServiceUnavailable)
        json.NewEncoder(w).Encode(map[string]string{
            "status": "unhealthy",
            "error":  "database unavailable",
        })
        return
    }

    w.WriteHeader(http.StatusOK)
    json.NewEncoder(w).Encode(map[string]interface{}{
        "status":          "healthy",
        "chrome_pool":     len(chromePool.pool),
        "uptime_seconds":  time.Since(startTime).Seconds(),
    })
}
```

### Graceful Shutdown

```go
func main() {
    // ... setup ...

    srv := &http.Server{Addr: ":8080", Handler: router}

    go func() {
        if err := srv.ListenAndServe(); err != http.ErrServerClosed {
            log.Fatal(err)
        }
    }()

    // Wait for interrupt signal
    quit := make(chan os.Signal, 1)
    signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
    <-quit

    log.Println("Shutting down gracefully...")

    // Give in-flight requests 30 seconds to complete
    ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
    defer cancel()

    if err := srv.Shutdown(ctx); err != nil {
        log.Printf("Forced shutdown: %v", err)
    }

    // Cleanup Chrome pool
    chromePool.Close()

    log.Println("Shutdown complete")
}
```

---

## 11. Testing Requirements

### Unit Tests

```go
// license_test.go
func TestPDFLicenseValidation(t *testing.T) {
    // Setup test MongoDB with licenseCodes collection
    ctx := context.Background()

    tests := []struct {
        name        string
        siteURL     string
        setupData   *models.LicenseCodes // nil = no license in DB
        wantValid   bool
        wantErrCode string
    }{
        {
            name:    "valid one-time license",
            siteURL: "https://licensed-domain.com",
            setupData: &models.LicenseCodes{
                Domain:  "licensed-domain.com",
                Slug:    "pdf",
                Used:    true,
                Expires: time.Now().Add(365 * 24 * time.Hour).Unix(),
            },
            wantValid: true,
        },
        {
            name:    "valid subscription license",
            siteURL: "https://subscriber-domain.com",
            setupData: &models.LicenseCodes{
                Domain:         "subscriber-domain.com",
                Slug:           "pdf",
                Used:           true,
                SubscriptionID: "sub_test123",
                Status:         "active",
            },
            wantValid: true,
        },
        {
            name:    "expired one-time license",
            siteURL: "https://expired-domain.com",
            setupData: &models.LicenseCodes{
                Domain:  "expired-domain.com",
                Slug:    "pdf",
                Used:    true,
                Expires: time.Now().Add(-30 * 24 * time.Hour).Unix(), // Expired 30 days ago
            },
            wantValid:   false,
            wantErrCode: "LICENSE_EXPIRED",
        },
        {
            name:    "canceled subscription",
            siteURL: "https://canceled-domain.com",
            setupData: &models.LicenseCodes{
                Domain:         "canceled-domain.com",
                Slug:           "pdf",
                Used:           true,
                SubscriptionID: "sub_canceled",
                Status:         "canceled",
            },
            wantValid:   false,
            wantErrCode: "LICENSE_SUSPENDED",
        },
        {
            name:        "no license found",
            siteURL:     "https://unlicensed-domain.com",
            setupData:   nil,
            wantValid:   false,
            wantErrCode: "LICENSE_NOT_FOUND",
        },
        {
            name:    "localhost in dev mode",
            siteURL: "http://localhost:8080",
            // No license needed for localhost in dev
            setupData: nil,
            wantValid: true, // Only in ENV=dev
        },
        {
            name:    "strips www prefix",
            siteURL: "https://www.example.com/some/path",
            setupData: &models.LicenseCodes{
                Domain:  "example.com", // Without www
                Slug:    "pdf",
                Used:    true,
                Expires: time.Now().Add(365 * 24 * time.Hour).Unix(),
            },
            wantValid: true,
        },
    }

    for _, tt := range tests {
        t.Run(tt.name, func(t *testing.T) {
            // Setup test data
            if tt.setupData != nil {
                insertTestLicense(ctx, tt.setupData)
                defer cleanupTestLicense(ctx, tt.setupData.Domain)
            }

            license, err := findPDFLicenseByDomain(ctx, extractDomain(tt.siteURL))

            if tt.wantValid && err != nil {
                t.Errorf("expected valid, got error: %v", err)
            }
            if !tt.wantValid && err == nil {
                t.Error("expected error, got nil")
            }
            if err != nil {
                var apiErr *APIError
                if errors.As(err, &apiErr) && apiErr.Code != tt.wantErrCode {
                    t.Errorf("expected error code %s, got %s", tt.wantErrCode, apiErr.Code)
                }
            }
            if tt.wantValid && license == nil {
                t.Error("expected license, got nil")
            }
        })
    }
}

// ratelimit_test.go
func TestRateLimiting(t *testing.T) {
    limiter := NewRateLimiter(NewMemoryStore())
    license := &License{LicenseKey: "test", PlanTier: "basic"}

    // Should allow first 5 requests per minute
    for i := 0; i < 5; i++ {
        result, _ := limiter.Check(context.Background(), license, "example.com", 1)
        if !result.Allowed {
            t.Errorf("request %d should be allowed", i+1)
        }
    }

    // 6th request should be rate limited
    result, _ := limiter.Check(context.Background(), license, "example.com", 1)
    if result.Allowed {
        t.Error("6th request should be rate limited")
    }
    if result.RetryAfter == 0 {
        t.Error("RetryAfter should be set")
    }
}
```

### Integration Tests

```go
func TestPDFGeneration(t *testing.T) {
    // Requires Chrome to be installed
    if os.Getenv("CI") == "" && !chromeInstalled() {
        t.Skip("Chrome not installed")
    }

    tests := []struct {
        name     string
        html     string
        wantErr  bool
        validate func(pdf []byte) error
    }{
        {
            name: "simple HTML",
            html: `<!DOCTYPE html><html><body><h1>Hello World</h1></body></html>`,
            validate: func(pdf []byte) error {
                if len(pdf) < 1000 {
                    return errors.New("PDF too small")
                }
                if !bytes.HasPrefix(pdf, []byte("%PDF")) {
                    return errors.New("not a valid PDF")
                }
                return nil
            },
        },
        {
            name: "with CSS",
            html: `<!DOCTYPE html><html><head><style>body { font-family: Arial; color: #333; }</style></head><body><p>Styled text</p></body></html>`,
            validate: func(pdf []byte) error {
                return nil // Just check it doesn't error
            },
        },
        {
            name: "with table",
            html: `<!DOCTYPE html><html><body><table><tr><td>A</td><td>B</td></tr></table></body></html>`,
            validate: func(pdf []byte) error {
                return nil
            },
        },
    }

    for _, tt := range tests {
        t.Run(tt.name, func(t *testing.T) {
            pdf, _, err := generatePDF(context.Background(), tt.html, defaultOptions())
            if (err != nil) != tt.wantErr {
                t.Errorf("generatePDF() error = %v, wantErr %v", err, tt.wantErr)
                return
            }
            if err == nil {
                if err := tt.validate(pdf); err != nil {
                    t.Errorf("validation failed: %v", err)
                }
            }
        })
    }
}
```

### Load Tests

```bash
# Using k6 or similar
k6 run --vus 50 --duration 60s load-test.js
```

```javascript
// load-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export default function() {
    const payload = JSON.stringify({
        html: '<html><body><h1>Load Test</h1><p>' + __VU + '</p></body></html>',
        options: { format: 'A4' },
        site_url: 'https://load-test.example.com',  // License validated server-side by domain
        form_id: 1
    });

    const res = http.post('http://localhost:8080/v1/pdf/generate', payload, {
        headers: { 'Content-Type': 'application/json' },
        timeout: '60s'
    });

    check(res, {
        'status is 200': (r) => r.status === 200,
        'response has pdf': (r) => JSON.parse(r.body).pdf_base64 !== undefined,
        'response time < 10s': (r) => r.timings.duration < 10000
    });

    sleep(1);
}
```

### Sample HTML Test Documents

Include test files for regression testing:

1. `test_simple.html` - Basic text document
2. `test_styled.html` - CSS with colors, fonts, borders
3. `test_table.html` - Complex table with headers, footers
4. `test_images.html` - Embedded base64 images
5. `test_flexbox.html` - Flexbox layout
6. `test_grid.html` - CSS Grid layout
7. `test_unicode.html` - Non-Latin scripts (Chinese, Arabic, Hebrew)
8. `test_multipage.html` - 10+ pages with page breaks

---

## Appendix A: Sample Request/Response

### Minimal Request

```bash
curl -X POST https://api.super-forms.com/v1/pdf/generate \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<!DOCTYPE html><html><body><h1>Invoice</h1></body></html>",
    "site_url": "https://example.com"
  }'
```

> **Note:** License validation happens server-side. The server extracts the domain from `site_url` and looks up the license in MongoDB.

### Full Request with All Options

```bash
curl -X POST https://api.super-forms.com/v1/pdf/generate \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<!DOCTYPE html><html><head><style>body{font-family:Arial;margin:0;padding:20px;}h1{color:#333;}.invoice-table{width:100%;border-collapse:collapse;}.invoice-table th,.invoice-table td{border:1px solid #ddd;padding:10px;}</style></head><body><h1>Invoice #12345</h1><table class=\"invoice-table\"><thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead><tbody><tr><td>Widget A</td><td>2</td><td>$50.00</td></tr><tr><td>Widget B</td><td>1</td><td>$75.00</td></tr></tbody><tfoot><tr><td colspan=\"2\"><strong>Total</strong></td><td><strong>$175.00</strong></td></tr></tfoot></table></body></html>",
    "options": {
      "format": "A4",
      "orientation": "portrait",
      "margin": {
        "top": "15mm",
        "right": "10mm",
        "bottom": "15mm",
        "left": "10mm"
      },
      "displayHeaderFooter": true,
      "headerTemplate": "<div style=\"font-size:10px;text-align:center;width:100%;\">ACME Corporation</div>",
      "footerTemplate": "<div style=\"font-size:10px;text-align:center;width:100%;\">Page <span class=\"pageNumber\"></span> of <span class=\"totalPages\"></span> | Generated on <span class=\"date\"></span></div>",
      "printBackground": true,
      "scale": 1.0
    },
    "site_url": "https://customer-site.com",
    "form_id": 456
  }'
```

### Success Response (Truncated)

```json
{
  "success": true,
  "pdf_base64": "JVBERi0xLjQKJeLjz9MKMSAwIG9iago8PAovVHlwZSAvQ2F0YWxvZwovUGFnZXMgMiAwIFIKPj4KZW5kb2JqCjIgMCBvYmoKPDwKL1R5cGUgL1BhZ2VzCi9LaWRzIFszIDAgUl0KL0NvdW50IDEKL01lZGlhQm94IFswIDAgNTk1LjI4IDg0MS44OV0KPj4KZW5kb2...",
  "pages": 1,
  "file_size": 45678,
  "render_time_ms": 1234
}
```

---

## Appendix B: Error Codes Reference

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `LICENSE_NOT_FOUND` | 403 | No active license found for domain |
| `LICENSE_EXPIRED` | 403 | License for domain has expired |
| `LICENSE_SUSPENDED` | 403 | License suspended for policy violation |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests, retry later |
| `PAYLOAD_TOO_LARGE` | 413 | HTML exceeds 5MB limit |
| `INVALID_HTML` | 400 | HTML is malformed or missing required tags |
| `INVALID_OPTIONS` | 400 | PDF options contain invalid values |
| `RENDER_TIMEOUT` | 500 | Chrome took too long (>30s) |
| `CHROME_CRASHED` | 500 | Chrome process crashed |
| `SERVICE_BUSY` | 503 | All render workers busy, retry |
| `INTERNAL_ERROR` | 500 | Unexpected server error |

---

## Appendix C: Migration Notes for Existing Endpoint

If migrating from an existing PDF endpoint, ensure:

1. Response format is backward compatible
2. Add deprecation headers to old endpoint
3. Support both old and new request formats during transition
4. Log requests to old endpoint for monitoring migration progress
