# Testing & Validation Plan - PDF API Service

**Document Version:** 1.0
**Date:** 2025-12-16

---

## Overview

This document outlines the testing strategy for validating the PDF API service integration. Testing covers local development, staging environment (api.dev.super-forms.com), and production readiness.

---

## 1. Local Development Testing

### 1.1 Prerequisites

- WordPress development environment (wp-env or local setup)
- Super Forms plugin with PDF API integration code
- Test domain registered in MongoDB `licenseCodes` collection (or localhost for dev mode)
- cURL or Postman for API testing

### 1.2 Environment Setup

Add to `wp-config.php`:

```php
// Point to development API
define( 'SUPER_PDF_API_ENDPOINT', 'https://api.dev.super-forms.com/v1/pdf/generate' );

// Enable debug logging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

> **Note:** License validation is performed server-side by looking up the domain from `site_url` in the MongoDB `licenseCodes` collection. No license key needs to be stored in WordPress.

### 1.3 Basic Connectivity Test

**Test:** Verify WordPress can reach the PDF API.

```bash
# From server/container running WordPress
# License is validated server-side by looking up the domain in MongoDB licenseCodes collection
curl -X POST https://api.dev.super-forms.com/v1/pdf/generate \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<html><body><h1>Test</h1></body></html>",
    "site_url": "https://licensed-domain.com"
  }'
```

**Expected:** JSON response with `success: true` and `pdf_base64` containing valid PDF data.

> **Note:** The `site_url` domain must have an active PDF license in the `licenseCodes` collection (slug="pdf", used=true, not expired).

**Validation:**
```bash
# Decode and verify PDF
echo "<pdf_base64>" | base64 -d > test.pdf
file test.pdf  # Should output: test.pdf: PDF document, version 1.4
```

---

## 2. WordPress Integration Tests

### 2.1 Test Scenarios

| # | Scenario | Method | Expected Result |
|---|----------|--------|-----------------|
| 1 | Licensed domain, valid request | API | PDF generated via API |
| 2 | No license for domain | Error | Returns LICENSE_NOT_FOUND error |
| 3 | Expired license for domain | Error | Returns LICENSE_EXPIRED error |
| 4 | Canceled subscription | Error | Returns LICENSE_SUSPENDED error |
| 5 | Network timeout | Fallback | Falls back to client-side/TCPDF |
| 6 | Rate limit exceeded | Error/Retry | Returns error with retry_after |
| 7 | Large HTML (>5MB) | Error | Returns PAYLOAD_TOO_LARGE error |
| 8 | Frontend button click | API or Client | Depends on pdf_generation_method |
| 9 | Async automation trigger | API | PDF generated via API |
| 10 | Localhost (dev mode) | API | License check bypassed |

### 2.2 Manual Test Procedure

#### Test 1: API Generation Success

1. Create a new form in Form Builder V2
2. Add automation: Trigger="Form Submitted", Action="Generate File"
3. Configure action:
   - Output Format: PDF
   - PDF Generation Method: API Only
   - Template: Custom HTML with `<h1>Test {name}</h1>`
4. Submit form via frontend
5. Check wp-content/uploads/super-forms-files/ for generated PDF
6. Open PDF and verify:
   - Text is selectable (not image)
   - Field values replaced correctly
   - Page size matches configuration

#### Test 2: Fallback Behavior

1. Use a domain that has no PDF license in `licenseCodes` collection
2. Set PDF Generation Method to "Automatic"
3. Submit form via frontend button click
4. Verify:
   - Client-side PDF generated (browser download)
   - LICENSE_NOT_FOUND error logged in debug.log

#### Test 3: Error Handling (No Fallback)

1. Set PDF Generation Method to "API Only"
2. Use a domain without an active PDF license
3. Submit form
4. Verify:
   - LICENSE_NOT_FOUND error displayed to user
   - No fallback occurs
   - Error logged with domain info

### 2.3 Automated Test (PHPUnit)

```php
<?php
/**
 * Test PDF API integration.
 *
 * @package Super_Forms
 */

class Test_PDF_API_Integration extends WP_UnitTestCase {

    private $action;

    public function setUp(): void {
        parent::setUp();
        $this->action = new SUPER_Action_Generate_File();
    }

    /**
     * Test that API is used when enabled and domain is licensed.
     * License validation happens server-side via MongoDB licenseCodes collection.
     */
    public function test_api_used_when_enabled() {
        // Enable API - no license key stored, validation is server-side by domain.
        update_option( 'super_settings', [
            'pdf_api_enabled' => true,
        ] );

        $context = [
            'form_id'   => 1,
            'entry_id'  => 100,
            'site_url'  => 'https://licensed-domain.com', // Domain must exist in licenseCodes
            'form_data' => [
                'name'  => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        $config = [
            'output_format'         => 'pdf',
            'template_source'       => 'html',
            'html_template'         => '<h1>Hello {name}</h1>',
            'pdf_generation_method' => 'api',
        ];

        // This will fail without a real API, but we can verify the method is called.
        $result = $this->action->execute( $context, $config );

        // In real test, mock wp_remote_post and verify request body contains site_url.
        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertContains( $result->get_error_code(), [
            'license_not_found',  // Domain has no PDF license
            'license_expired',    // License exists but expired
            'api_connection_error',
        ] );
    }

    /**
     * Test fallback to client-side for frontend events when API disabled.
     */
    public function test_fallback_to_client_for_frontend() {
        // Disable API.
        update_option( 'super_settings', [
            'pdf_api_enabled' => false,
        ] );

        $context = [
            'form_id'      => 1,
            'entry_id'     => 100,
            'site_url'     => 'https://any-domain.com',
            'event_source' => 'frontend',
            'event_id'     => 'button.download_pdf',
            'form_data'    => [
                'name' => 'Jane Doe',
            ],
        ];

        $config = [
            'output_format'         => 'pdf',
            'template_source'       => 'html',
            'html_template'         => '<p>{name}</p>',
            'pdf_generation_method' => 'auto',
        ];

        $result = $this->action->execute( $context, $config );

        $this->assertTrue( $result['success'] );
        $this->assertTrue( $result['data']['client_render'] );
        $this->assertStringContainsString( 'Jane Doe', $result['data']['html'] );
    }

    /**
     * Test license errors do not fallback (API Only mode).
     * Server validates domain against licenseCodes collection.
     */
    public function test_license_errors_no_fallback() {
        // This test requires mocking wp_remote_post.
        // Verify that LICENSE_NOT_FOUND, LICENSE_EXPIRED, LICENSE_SUSPENDED
        // return errors without attempting fallback when pdf_generation_method='api'.
    }
}
```

---

## 3. API Endpoint Testing

### 3.1 cURL Test Suite

Create a shell script `test_pdf_api.sh`:

```bash
#!/bin/bash

API_URL="https://api.dev.super-forms.com/v1/pdf/generate"

# Test domains - these must exist in MongoDB licenseCodes collection with slug="pdf"
LICENSED_DOMAIN="https://licensed-domain.com"      # Has active PDF license
UNLICENSED_DOMAIN="https://unlicensed-domain.com"  # No PDF license exists
EXPIRED_DOMAIN="https://expired-domain.com"         # PDF license expired

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

pass() { echo -e "${GREEN}PASS${NC}: $1"; }
fail() { echo -e "${RED}FAIL${NC}: $1"; }

# Test 1: Basic PDF generation (licensed domain)
echo "Test 1: Basic PDF generation (licensed domain)"
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><body><h1>Invoice</h1><p>Amount: \$100</p></body></html>\",
    \"site_url\": \"$LICENSED_DOMAIN\"
  }")

if echo "$RESPONSE" | grep -q '"success":true'; then
  pass "Basic PDF generated"
  # Verify PDF content
  PDF_SIZE=$(echo "$RESPONSE" | jq -r '.file_size')
  if [ "$PDF_SIZE" -gt 1000 ]; then
    pass "PDF size reasonable ($PDF_SIZE bytes)"
  else
    fail "PDF size too small ($PDF_SIZE bytes)"
  fi
else
  fail "Basic PDF generation: $RESPONSE"
fi

# Test 2: No license for domain
echo -e "\nTest 2: No license for domain"
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><body>Test</body></html>\",
    \"site_url\": \"$UNLICENSED_DOMAIN\"
  }")

if echo "$RESPONSE" | grep -q '"error_code":"LICENSE_NOT_FOUND"'; then
  pass "Unlicensed domain rejected"
else
  fail "Unlicensed domain not rejected: $RESPONSE"
fi

# Test 3: Expired license
echo -e "\nTest 3: Expired license"
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><body>Test</body></html>\",
    \"site_url\": \"$EXPIRED_DOMAIN\"
  }")

if echo "$RESPONSE" | grep -q '"error_code":"LICENSE_EXPIRED"'; then
  pass "Expired license rejected"
else
  fail "Expired license not rejected: $RESPONSE"
fi

# Test 4: Custom page size
echo -e "\nTest 4: Custom page size (Letter)"
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><body><h1>Letter Size Document</h1></body></html>\",
    \"options\": {
      \"format\": \"Letter\",
      \"orientation\": \"landscape\"
    },
    \"site_url\": \"$LICENSED_DOMAIN\"
  }")

if echo "$RESPONSE" | grep -q '"success":true'; then
  pass "Letter landscape PDF generated"
else
  fail "Letter landscape PDF: $RESPONSE"
fi

# Test 5: Header/footer templates
echo -e "\nTest 5: Header/footer templates"
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><body><h1>Page 1</h1><div style='page-break-after:always'></div><h1>Page 2</h1></body></html>\",
    \"options\": {
      \"displayHeaderFooter\": true,
      \"headerTemplate\": \"<div style='font-size:10px;text-align:center;width:100%;'>ACME Corp</div>\",
      \"footerTemplate\": \"<div style='font-size:10px;text-align:center;width:100%;'>Page <span class='pageNumber'></span> of <span class='totalPages'></span></div>\"
    },
    \"site_url\": \"$LICENSED_DOMAIN\"
  }")

if echo "$RESPONSE" | grep -q '"pages":2'; then
  pass "Multi-page PDF with header/footer (2 pages)"
else
  fail "Multi-page PDF: $RESPONSE"
fi

# Test 6: Payload too large
echo -e "\nTest 6: Payload too large (5MB+)"
LARGE_CONTENT=$(python3 -c "print('<p>' + 'x' * 5500000 + '</p>')")
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><body>$LARGE_CONTENT</body></html>\",
    \"site_url\": \"$LICENSED_DOMAIN\"
  }")

if echo "$RESPONSE" | grep -q '"error_code":"PAYLOAD_TOO_LARGE"'; then
  pass "Large payload rejected"
else
  fail "Large payload not rejected: $RESPONSE"
fi

# Test 7: Complex rendering
echo -e "\nTest 7: Complex rendering"
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><head><style>body{font-family:Arial;}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}.item{background:#f0f0f0;padding:20px;border-radius:5px;}</style></head><body><div class='grid'><div class='item'>A</div><div class='item'>B</div><div class='item'>C</div><div class='item'>D</div></div></body></html>\",
    \"site_url\": \"$LICENSED_DOMAIN\"
  }")

if echo "$RESPONSE" | grep -q '"success":true'; then
  pass "Complex CSS (grid) rendered"
else
  fail "Complex CSS rendering: $RESPONSE"
fi

# Test 8: Localhost bypass (dev mode only)
echo -e "\nTest 8: Localhost bypass (requires ENV=dev on server)"
RESPONSE=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"html\": \"<html><body><h1>Dev Test</h1></body></html>\",
    \"site_url\": \"http://localhost:8080\"
  }")

# This test behavior depends on server ENV setting
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q '"success":true'; then
  pass "Localhost bypass works (server in dev mode)"
elif echo "$RESPONSE" | grep -q '"error_code":"LICENSE_NOT_FOUND"'; then
  pass "Localhost requires license (server in production mode)"
else
  fail "Unexpected response: $RESPONSE"
fi

echo -e "\n=== Test Suite Complete ==="
```

### 3.2 Postman Collection

Create `pdf-api-tests.postman_collection.json`:

```json
{
  "info": {
    "name": "Super Forms PDF API Tests",
    "description": "Tests for PDF generation API. License validation happens server-side by domain lookup in MongoDB licenseCodes collection.",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "api_url",
      "value": "https://api.dev.super-forms.com/v1"
    },
    {
      "key": "licensed_domain",
      "value": "https://licensed-domain.com",
      "description": "Domain with active PDF license in licenseCodes collection"
    },
    {
      "key": "unlicensed_domain",
      "value": "https://unlicensed-domain.com",
      "description": "Domain without PDF license"
    }
  ],
  "item": [
    {
      "name": "Generate Basic PDF (Licensed Domain)",
      "request": {
        "method": "POST",
        "url": "{{api_url}}/pdf/generate",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"html\": \"<html><body><h1>Test Invoice</h1><p>Amount: $100.00</p></body></html>\",\n  \"site_url\": \"{{licensed_domain}}\"\n}"
        }
      },
      "event": [
        {
          "listen": "test",
          "script": {
            "exec": [
              "pm.test('Status code is 200', function() {",
              "  pm.response.to.have.status(200);",
              "});",
              "",
              "pm.test('Response has success true', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.success).to.be.true;",
              "});",
              "",
              "pm.test('Response has pdf_base64', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.pdf_base64).to.be.a('string');",
              "  pm.expect(json.pdf_base64.length).to.be.above(100);",
              "});",
              "",
              "pm.test('PDF starts with valid header', function() {",
              "  var json = pm.response.json();",
              "  var decoded = atob(json.pdf_base64.substring(0, 20));",
              "  pm.expect(decoded).to.include('%PDF');",
              "});"
            ]
          }
        }
      ]
    },
    {
      "name": "No License for Domain",
      "request": {
        "method": "POST",
        "url": "{{api_url}}/pdf/generate",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"html\": \"<html><body>Test</body></html>\",\n  \"site_url\": \"{{unlicensed_domain}}\"\n}"
        }
      },
      "event": [
        {
          "listen": "test",
          "script": {
            "exec": [
              "pm.test('Status code is 403', function() {",
              "  pm.response.to.have.status(403);",
              "});",
              "",
              "pm.test('Error code is LICENSE_NOT_FOUND', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.error_code).to.equal('LICENSE_NOT_FOUND');",
              "});",
              "",
              "pm.test('Response includes domain', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.domain).to.be.a('string');",
              "});",
              "",
              "pm.test('Response includes purchase_url', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.purchase_url).to.include('super-forms.com');",
              "});"
            ]
          }
        }
      ]
    }
  ]
}
```

---

## 4. Quality Validation

### 4.1 Text Selectability Test

**Goal:** Verify generated PDFs contain vector text, not rasterized images.

**Procedure:**
1. Generate PDF via API with text content
2. Open in Adobe Acrobat Reader (or similar)
3. Attempt to select text with cursor
4. Copy selected text to clipboard
5. Paste into text editor

**Pass Criteria:**
- Text can be selected with cursor
- Selected text can be copied
- Pasted text matches original content exactly

**Automation:**
```python
# Using PyPDF2 to extract text
import PyPDF2
import base64

def test_text_extraction(pdf_base64):
    pdf_bytes = base64.b64decode(pdf_base64)

    with open('/tmp/test.pdf', 'wb') as f:
        f.write(pdf_bytes)

    with open('/tmp/test.pdf', 'rb') as f:
        reader = PyPDF2.PdfReader(f)
        text = ''
        for page in reader.pages:
            text += page.extract_text()

    # Verify expected content is extractable
    assert 'Invoice' in text, f"Expected 'Invoice' in text, got: {text[:100]}"
    assert '$100' in text, f"Expected '$100' in text, got: {text[:100]}"

    print("PASS: Text extraction successful")
```

### 4.2 File Size Comparison

**Goal:** Verify API PDFs are smaller than client-side PDFs.

| Content | Client-Side (html2canvas) | API (Chrome) | Target |
|---------|---------------------------|--------------|--------|
| Simple form (10 fields) | ~300KB | ~30KB | <50KB |
| Complex form (50 fields, table) | ~800KB | ~80KB | <150KB |
| Form with images (3 images) | ~1.5MB | ~200KB | <500KB |

**Test Procedure:**
1. Generate same content via both methods
2. Compare file sizes
3. Verify API PDF is at least 50% smaller

### 4.3 Visual Fidelity Test

**Goal:** Verify PDF output matches browser rendering.

**Procedure:**
1. Create HTML document with:
   - Various font sizes/weights
   - Colors (background, text)
   - Borders and border-radius
   - Flexbox/Grid layouts
   - Tables with complex styling
2. Take screenshot in Chrome browser
3. Generate PDF via API
4. Compare PDF first page to screenshot

**Automated Comparison:**
```python
# Using ImageMagick or similar
# compare -metric RMSE browser.png pdf_page1.png diff.png

from PIL import Image
import imagehash

def compare_visual_fidelity(browser_screenshot, pdf_screenshot):
    hash1 = imagehash.phash(Image.open(browser_screenshot))
    hash2 = imagehash.phash(Image.open(pdf_screenshot))

    difference = hash1 - hash2
    print(f"Visual difference score: {difference}")

    # Lower is better, <10 is acceptable
    assert difference < 10, f"Visual difference too high: {difference}"
```

### 4.4 Font Rendering Test

**Goal:** Verify custom fonts render correctly.

**Test Cases:**
1. System fonts (Arial, Times, Helvetica)
2. Google Fonts via embedded `@font-face`
3. Base64 embedded font files
4. Unicode characters (CJK, Arabic, Hebrew, Emoji)

**HTML Templates:**

```html
<!-- test_fonts_system.html -->
<html>
<body>
  <p style="font-family: Arial;">Arial: The quick brown fox</p>
  <p style="font-family: 'Times New Roman';">Times: The quick brown fox</p>
  <p style="font-family: Courier;">Courier: The quick brown fox</p>
</body>
</html>

<!-- test_fonts_unicode.html -->
<html>
<head>
  <style>
    body { font-family: 'Noto Sans', Arial, sans-serif; }
  </style>
</head>
<body>
  <p>English: Hello World</p>
  <p>Chinese: 你好世界</p>
  <p>Arabic: مرحبا بالعالم</p>
  <p>Hebrew: שלום עולם</p>
  <p>Emoji: 👋🌍✨</p>
</body>
</html>
```

---

## 5. Performance Benchmarks

### 5.1 Render Time Targets

| Metric | Target | Maximum |
|--------|--------|---------|
| P50 (median) | <2s | 3s |
| P95 | <5s | 8s |
| P99 | <10s | 15s |

### 5.2 Load Test

**Tool:** k6 or Apache JMeter

```javascript
// k6 load test script
// Note: License validation happens server-side via domain lookup
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '1m', target: 10 },  // Ramp up to 10 users
    { duration: '3m', target: 10 },  // Stay at 10 users
    { duration: '1m', target: 50 },  // Spike to 50 users
    { duration: '2m', target: 50 },  // Stay at 50
    { duration: '1m', target: 0 },   // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<5000'], // 95% under 5s
    http_req_failed: ['rate<0.05'],    // Error rate under 5%
  },
};

export default function() {
  // License is validated by domain lookup in MongoDB licenseCodes collection
  // The TEST_DOMAIN must have an active PDF license (slug="pdf", used=true)
  const payload = JSON.stringify({
    html: `<html><body>
      <h1>Load Test ${__VU}-${__ITER}</h1>
      <table>
        ${Array(20).fill('<tr><td>Row</td><td>Data</td></tr>').join('')}
      </table>
    </body></html>`,
    options: { format: 'A4' },
    site_url: __ENV.TEST_DOMAIN,  // Domain must have PDF license in licenseCodes
    form_id: 1
  });

  const res = http.post(__ENV.API_URL, payload, {
    headers: { 'Content-Type': 'application/json' },
    timeout: '30s'
  });

  check(res, {
    'status is 200': (r) => r.status === 200,
    'has pdf_base64': (r) => {
      const json = JSON.parse(r.body);
      return json.pdf_base64 && json.pdf_base64.length > 100;
    },
    'response time < 5s': (r) => r.timings.duration < 5000
  });

  sleep(1);
}
```

**Run command:**
```bash
# TEST_DOMAIN must have active PDF license in MongoDB licenseCodes collection
k6 run -e API_URL=https://api.dev.super-forms.com/v1/pdf/generate \
       -e TEST_DOMAIN=https://load-test.example.com \
       load_test.js
```

### 5.3 Memory Usage Test

Monitor server memory during load test:

```bash
# On API server
watch -n 1 'ps aux | grep -E "(chrome|pdf-service)" | awk "{sum+=\$6} END {print sum/1024 \" MB\"}"'
```

**Target:** <500MB per Chrome instance, <2GB total under 50 concurrent users.

---

## 6. End-to-End Scenarios

### 6.1 Scenario: Form Submission with PDF Receipt

**Steps:**
1. User fills out contact form
2. Clicks "Submit" button
3. Automation triggers:
   - Generate PDF receipt
   - Send email with PDF attachment
   - Save to media library
4. User receives email with PDF

**Validation:**
- PDF contains all form field values
- PDF file is attached to email
- PDF appears in WordPress Media Library
- Total processing time <10 seconds

### 6.2 Scenario: Scheduled Report Generation

**Steps:**
1. Schedule automation: "Generate Weekly Report" at midnight Sunday
2. Automation executes in background (via Action Scheduler)
3. PDF generated with aggregated data
4. PDF saved and admin notified

**Validation:**
- PDF generated without frontend context
- PDF uses API (not client-side)
- Async execution completes successfully
- Email notification sent

### 6.3 Scenario: Rate Limit Recovery

**Steps:**
1. Trigger rapid PDF generation (exceed rate limit)
2. Receive RATE_LIMIT_EXCEEDED error
3. Automation reschedules via Action Scheduler
4. PDF generates after retry_after period

**Validation:**
- Rate limit error includes retry_after
- Action Scheduler job created
- PDF eventually generates successfully
- User receives delayed notification

---

## 7. Regression Test Checklist

### Before Release

- [ ] All existing PDF forms still work (client-side)
- [ ] API toggle enables/disables API usage
- [ ] Fallback works when API disabled
- [ ] Fallback works for unlicensed domains (when method=auto)
- [ ] Error messages display correctly for LICENSE_NOT_FOUND
- [ ] Error messages display correctly for LICENSE_EXPIRED
- [ ] Debug logging captures API errors with domain info
- [ ] No PHP errors/warnings in error_log
- [ ] JavaScript console has no errors

### HTML Content Tests

- [ ] Simple paragraph text
- [ ] Headings (H1-H6)
- [ ] Lists (ordered, unordered)
- [ ] Tables (simple, complex, nested)
- [ ] Images (inline base64, URLs)
- [ ] CSS colors (background, text, border)
- [ ] Flexbox layouts
- [ ] CSS Grid layouts
- [ ] Custom fonts (@font-face)
- [ ] Page breaks (manual, automatic)
- [ ] Headers and footers

### Field Tag Replacement

- [ ] `{field_name}` replaced correctly
- [ ] `{entry_id}` replaced
- [ ] `{date}` and `{time}` replaced
- [ ] `{user_email}` replaced
- [ ] Missing tags handled (empty or placeholder)
- [ ] HTML in field values escaped properly

### Error Handling

- [ ] Network timeout → fallback (when method=auto)
- [ ] Invalid HTML → error message
- [ ] LICENSE_NOT_FOUND → error (no fallback when method=api)
- [ ] LICENSE_EXPIRED → error (no fallback when method=api)
- [ ] LICENSE_SUSPENDED → error (no fallback when method=api)
- [ ] Rate limited → retry scheduled via Action Scheduler
- [ ] Server error → fallback (when method=auto)

---

## 8. Monitoring in Production

### 8.1 Metrics to Track

| Metric | Source | Alert Threshold |
|--------|--------|-----------------|
| PDF API success rate | Plugin logs | <95% |
| Average render time | API logs | >5 seconds |
| Fallback usage rate | Plugin logs | >10% |
| LICENSE_NOT_FOUND errors | API logs | Spike detection |
| LICENSE_EXPIRED errors | API logs | Per domain monitoring |
| Rate limit hits | API logs | Per domain monitoring |

### 8.2 Log Analysis Queries

```javascript
// MongoDB query for daily PDF generation stats
// Note: Logs are stored in MongoDB 'logs' collection via LogInfo()/LogError()
db.logs.aggregate([
  {
    $match: {
      created: { $gte: new Date(Date.now() - 30*24*60*60*1000) },
      "metadata.domain": { $exists: true }
    }
  },
  {
    $group: {
      _id: {
        date: { $dateToString: { format: "%Y-%m-%d", date: "$created" } },
        domain: "$metadata.domain"
      },
      total_pdfs: { $sum: 1 },
      avg_render_time: { $avg: "$metadata.render_time" },
      avg_pdf_size: { $avg: "$metadata.pdf_size" }
    }
  },
  { $sort: { "_id.date": -1 } }
])
```

### 8.3 Health Check Endpoint

```bash
# Verify API is healthy before deployment
curl -f https://api.super-forms.com/health || echo "API UNHEALTHY"
```

---

## 9. Test Data Cleanup

After testing, clean up generated files:

```bash
# WordPress uploads
wp eval 'array_map("unlink", glob(wp_upload_dir()["basedir"] . "/super-forms-files/test_*.pdf"));'

# Media Library (test attachments)
wp post delete $(wp post list --post_type=attachment --field=ID --search="test_") --force
```

---

## 10. Sign-Off Checklist

Before marking task complete:

- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] Load test meets performance targets
- [ ] Visual quality validated
- [ ] Text selectability confirmed
- [ ] File size targets met
- [ ] Error handling verified
- [ ] Documentation complete
- [ ] Code reviewed
- [ ] Deployed to staging
- [ ] Smoke test on staging passed
