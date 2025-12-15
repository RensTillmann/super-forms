---
name: 04-built-in-node-types
status: completed
created: 2025-12-15
depends_on: 02-event-system-architecture
---

# Built-in Node Types for Button Actions

## Problem/Goal

Create new automation action nodes that are commonly triggered by button clicks. These nodes consume the context from subtask 02's event system and return data in the response format that the frontend expects.

Priority nodes based on research:
1. **generate_file** - PDF, DOCX, CSV generation
2. **generate_temp_access** - Secure temporary access tokens
3. **calculate** - Expression evaluation
4. **validate_data** - Custom validation rules

## Success Criteria

- [x] `SUPER_Action_Generate_File` implemented with PDF support via TCPDF
- [x] `SUPER_Action_Generate_Temp_Access` implemented with secure token generation
- [x] `SUPER_Action_Calculate` implemented for expression evaluation
- [x] `SUPER_Action_Validate_Data` implemented for custom validation
- [x] All actions registered in automation registry
- [x] Each action has settings schema for UI configuration
- [x] Actions return data in format defined by subtask 02
- [x] Database table `wp_superforms_temp_access` created
- [x] Security: proper sanitization, permissions, token hashing

## Shared Response Format (From Subtask 02)

All actions must return data compatible with:

```php
return [
    'success' => true,
    'data' => [
        // Action-specific fields that frontend handles:
        'file_url' => '...',        // Triggers download
        'access_url' => '...',      // Shows copy modal
        'result' => ...,            // For calculations
        'valid' => true/false,      // For validation
        'errors' => [...],          // Validation errors
        'message' => '...',         // Toast notification
    ],
];
```

---

## Action 1: Generate File

### Purpose
Generate PDF, DOCX, or CSV files from form data using templates.

### Settings Schema

```php
public function get_settings_schema() {
    return [
        [
            'name' => 'template_source',
            'label' => 'Template Source',
            'type' => 'select',
            'options' => [
                ['value' => 'html', 'label' => 'HTML Template'],
                ['value' => 'form_section', 'label' => 'Form Section'],
                ['value' => 'entry_summary', 'label' => 'Entry Summary'],
            ],
            'default' => 'html',
        ],
        [
            'name' => 'html_template',
            'label' => 'HTML Template',
            'type' => 'wysiwyg',
            'description' => 'HTML content with {tags} for form data',
            'conditions' => [['field' => 'template_source', 'value' => 'html']],
        ],
        [
            'name' => 'output_format',
            'label' => 'Output Format',
            'type' => 'select',
            'options' => [
                ['value' => 'pdf', 'label' => 'PDF'],
                ['value' => 'docx', 'label' => 'Word Document'],
                ['value' => 'csv', 'label' => 'CSV'],
                ['value' => 'xlsx', 'label' => 'Excel'],
            ],
            'default' => 'pdf',
        ],
        [
            'name' => 'filename_pattern',
            'label' => 'Filename',
            'type' => 'text',
            'description' => 'Supports {tags}. Example: {name}_receipt_{date}',
            'default' => 'document_{entry_id}',
        ],
        [
            'name' => 'page_size',
            'label' => 'Page Size',
            'type' => 'select',
            'options' => [
                ['value' => 'A4', 'label' => 'A4'],
                ['value' => 'Letter', 'label' => 'Letter'],
                ['value' => 'Legal', 'label' => 'Legal'],
            ],
            'default' => 'A4',
            'conditions' => [['field' => 'output_format', 'value' => 'pdf']],
        ],
        [
            'name' => 'upload_to_media',
            'label' => 'Save to Media Library',
            'type' => 'toggle',
            'default' => false,
        ],
    ];
}
```

### Execute Method

```php
public function execute($context, $config) {
    // 1. Get template content
    $html = $this->replace_variables($config['html_template'], $context);

    // 2. Generate file based on format
    switch ($config['output_format']) {
        case 'pdf':
            $file_path = $this->generate_pdf($html, $config);
            break;
        case 'csv':
            $file_path = $this->generate_csv($context['form_data'], $config);
            break;
        // ... other formats
    }

    // 3. Handle file storage
    $file_url = $this->get_file_url($file_path);
    $attachment_id = null;

    if ($config['upload_to_media']) {
        $attachment_id = $this->upload_to_media_library($file_path);
    }

    return [
        'success' => true,
        'data' => [
            'file_url' => $file_url,
            'attachment_id' => $attachment_id,
            'filename' => basename($file_path),
            'file_size' => filesize($file_path),
            'message' => 'File generated successfully',
        ],
    ];
}
```

### Libraries

- **PDF**: TCPDF (already in WordPress ecosystem) or DOMPDF
- **DOCX**: PHPWord
- **CSV/XLSX**: PhpSpreadsheet

---

## Action 2: Generate Temporary Access

### Purpose
Create secure, time-limited access tokens for viewing/editing entries or downloading files.

### Database Table

```sql
CREATE TABLE {$prefix}superforms_temp_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash VARCHAR(64) NOT NULL,           -- SHA256 of token
    resource_type ENUM('entry', 'file', 'form') NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    access_type ENUM('view', 'edit', 'download') NOT NULL,
    created_by BIGINT UNSIGNED,                -- User who created
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    max_uses INT UNSIGNED NULL,                -- NULL = unlimited
    use_count INT UNSIGNED DEFAULT 0,
    last_used_at DATETIME NULL,
    revoked_at DATETIME NULL,                  -- Soft delete
    metadata JSON,                             -- Additional config
    INDEX idx_token (token_hash),
    INDEX idx_expires (expires_at),
    INDEX idx_resource (resource_type, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Settings Schema

```php
public function get_settings_schema() {
    return [
        [
            'name' => 'resource_type',
            'label' => 'Resource Type',
            'type' => 'select',
            'options' => [
                ['value' => 'entry', 'label' => 'Form Entry'],
                ['value' => 'file', 'label' => 'Uploaded File'],
                ['value' => 'form', 'label' => 'Form (Pre-filled)'],
            ],
            'default' => 'entry',
        ],
        [
            'name' => 'access_type',
            'label' => 'Access Type',
            'type' => 'select',
            'options' => [
                ['value' => 'view', 'label' => 'View Only'],
                ['value' => 'edit', 'label' => 'Edit'],
                ['value' => 'download', 'label' => 'Download'],
            ],
            'default' => 'view',
        ],
        [
            'name' => 'expiry_duration',
            'label' => 'Expires After',
            'type' => 'select',
            'options' => [
                ['value' => '1h', 'label' => '1 Hour'],
                ['value' => '24h', 'label' => '24 Hours'],
                ['value' => '7d', 'label' => '7 Days'],
                ['value' => '30d', 'label' => '30 Days'],
            ],
            'default' => '24h',
        ],
        [
            'name' => 'max_uses',
            'label' => 'Maximum Uses',
            'type' => 'number',
            'description' => 'Leave empty for unlimited',
            'min' => 1,
        ],
    ];
}
```

### Execute Method

```php
public function execute($context, $config) {
    global $wpdb;

    // 1. Generate cryptographically secure token
    $token = wp_generate_password(32, false, false);
    $token_hash = hash('sha256', $token);

    // 2. Calculate expiry
    $expires_at = $this->calculate_expiry($config['expiry_duration']);

    // 3. Determine resource ID
    $resource_id = $this->get_resource_id($config['resource_type'], $context);

    // 4. Insert token record
    $wpdb->insert(
        $wpdb->prefix . 'superforms_temp_access',
        [
            'token_hash' => $token_hash,
            'resource_type' => $config['resource_type'],
            'resource_id' => $resource_id,
            'access_type' => $config['access_type'],
            'created_by' => $context['user_id'],
            'expires_at' => $expires_at,
            'max_uses' => $config['max_uses'] ?: null,
        ],
        ['%s', '%s', '%d', '%s', '%d', '%s', '%d']
    );

    // 5. Build access URL
    $access_url = add_query_arg([
        'sf_access' => $token,
        'type' => $config['resource_type'],
    ], home_url('/'));

    return [
        'success' => true,
        'data' => [
            'access_url' => $access_url,
            'token' => $token,  // Raw token for display
            'expires_at' => $expires_at,
            'message' => 'Access link generated',
        ],
    ];
}
```

### Security Requirements

1. **Token Generation**: Use `wp_generate_password(32, false, false)` - 32 chars, alphanumeric
2. **Storage**: Store SHA256 hash, never plain token
3. **Validation**: Check expiry, use count, revoked status on every access
4. **Rate Limiting**: Limit token generation per user/session
5. **Cleanup**: Scheduled job to delete expired tokens

---

## Action 3: Calculate Expression

### Purpose
Evaluate mathematical expressions with form field values.

### Settings Schema

```php
public function get_settings_schema() {
    return [
        [
            'name' => 'expression',
            'label' => 'Expression',
            'type' => 'text',
            'description' => 'Math expression with {tags}. Example: {quantity} * {unit_price} * (1 + {tax_rate})',
        ],
        [
            'name' => 'precision',
            'label' => 'Decimal Places',
            'type' => 'number',
            'default' => 2,
            'min' => 0,
            'max' => 10,
        ],
        [
            'name' => 'format',
            'label' => 'Output Format',
            'type' => 'select',
            'options' => [
                ['value' => 'number', 'label' => 'Number'],
                ['value' => 'currency', 'label' => 'Currency'],
                ['value' => 'percentage', 'label' => 'Percentage'],
            ],
            'default' => 'number',
        ],
        [
            'name' => 'currency_symbol',
            'label' => 'Currency Symbol',
            'type' => 'text',
            'default' => '$',
            'conditions' => [['field' => 'format', 'value' => 'currency']],
        ],
        [
            'name' => 'store_in_field',
            'label' => 'Store Result In Field',
            'type' => 'text',
            'description' => 'Optional: field name to store result',
        ],
    ];
}
```

### Execute Method

```php
public function execute($context, $config) {
    // 1. Replace tags with values
    $expression = $this->replace_variables($config['expression'], $context, [
        'sanitize' => 'float',
        'missing_behavior' => 'zero',
    ]);

    // 2. Evaluate expression safely (no eval!)
    $result = $this->safe_evaluate($expression);

    if ($result === false) {
        return new WP_Error('invalid_expression', 'Could not evaluate expression');
    }

    // 3. Format result
    $formatted = $this->format_result($result, $config);

    return [
        'success' => true,
        'data' => [
            'result' => round($result, $config['precision']),
            'formatted_result' => $formatted,
            'expression' => $expression,  // For debugging
        ],
    ];
}

private function safe_evaluate($expression) {
    // Use a safe math parser (e.g., symfony/expression-language or custom)
    // NEVER use eval()
    $parser = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();
    return $parser->evaluate($expression);
}
```

---

## Action 4: Validate Data

### Purpose
Run custom validation rules beyond standard field validation.

### Settings Schema

```php
public function get_settings_schema() {
    return [
        [
            'name' => 'rules',
            'label' => 'Validation Rules',
            'type' => 'repeater',
            'fields' => [
                ['name' => 'field', 'label' => 'Field', 'type' => 'text'],
                ['name' => 'rule', 'label' => 'Rule', 'type' => 'select', 'options' => [
                    ['value' => 'required', 'label' => 'Required'],
                    ['value' => 'email', 'label' => 'Valid Email'],
                    ['value' => 'url', 'label' => 'Valid URL'],
                    ['value' => 'min', 'label' => 'Minimum Value'],
                    ['value' => 'max', 'label' => 'Maximum Value'],
                    ['value' => 'regex', 'label' => 'Regex Pattern'],
                    ['value' => 'unique', 'label' => 'Unique in Database'],
                ]],
                ['name' => 'value', 'label' => 'Value/Pattern', 'type' => 'text'],
                ['name' => 'message', 'label' => 'Error Message', 'type' => 'text'],
            ],
        ],
        [
            'name' => 'stop_on_first_error',
            'label' => 'Stop on First Error',
            'type' => 'toggle',
            'default' => false,
        ],
    ];
}
```

### Execute Method

```php
public function execute($context, $config) {
    $errors = [];
    $form_data = $context['form_data'];

    foreach ($config['rules'] as $rule) {
        $field_value = $form_data[$rule['field']] ?? null;
        $is_valid = $this->validate_rule($field_value, $rule);

        if (!$is_valid) {
            $errors[] = [
                'field' => $rule['field'],
                'rule' => $rule['rule'],
                'message' => $rule['message'] ?: $this->get_default_message($rule),
            ];

            if ($config['stop_on_first_error']) {
                break;
            }
        }
    }

    $valid = empty($errors);

    return [
        'success' => true,  // Action succeeded (validation ran)
        'data' => [
            'valid' => $valid,
            'errors' => $errors,
            'message' => $valid ? 'Validation passed' : 'Validation failed',
        ],
    ];
}
```

---

## Registration

```php
// In class-automation-registry.php, load_builtin_actions()

$this->register_action('generate_file', 'SUPER_Action_Generate_File');
$this->register_action('generate_temp_access', 'SUPER_Action_Generate_Temp_Access');
$this->register_action('calculate', 'SUPER_Action_Calculate');
$this->register_action('validate_data', 'SUPER_Action_Validate_Data');
```

## Files to Create

- `/src/includes/automations/actions/class-action-generate-file.php`
- `/src/includes/automations/actions/class-action-generate-temp-access.php`
- `/src/includes/automations/actions/class-action-calculate.php`
- `/src/includes/automations/actions/class-action-validate-data.php`

## Files to Modify

- `/src/includes/automations/class-automation-registry.php` - Register actions
- `/src/includes/class-install.php` - Create temp_access table on install

## Dependencies

- **Requires**: Subtask 02 (event system) - Context format, response format
- **Libraries**:
  - TCPDF or DOMPDF for PDF generation
  - PHPWord for DOCX (optional)
  - PhpSpreadsheet for CSV/XLSX (optional)
  - symfony/expression-language for safe math eval

## Work Log

### 2025-12-15

#### Completed
- Implemented `SUPER_Action_Generate_File` (765 lines)
  - Supports PDF, CSV, HTML output formats
  - Hybrid rendering: server-side for CSV/HTML, client-side instructions for PDF
  - Template sources: custom HTML, entry summary, fields list
  - Field filtering (include/exclude), filename patterns with {tags}
  - Optional media library upload
- Implemented `SUPER_Action_Generate_Temp_Access` (545 lines)
  - Secure token generation with SHA256 hashing
  - Resource types: entry, file, form
  - Access types: view, edit, download
  - Configurable expiry (1h to 90d), max uses limit
  - Token validation and revocation utilities
  - Cleanup method for expired tokens
- Implemented `SUPER_Action_Calculate` (534 lines)
  - Safe expression parser (no eval!)
  - Supports +, -, *, /, %, parentheses
  - Currency/percentage/number formatting
  - Configurable decimal places, thousands/decimal separators
  - Store result as workflow variable
- Implemented `SUPER_Action_Validate_Data` (519 lines)
  - 18+ validation rule types
  - Rules: required, email, url, phone, numeric, integer, min/max, regex, equals, in_list, unique, date comparisons
  - Configurable failure behavior: continue, stop workflow, abort submission
  - Database uniqueness checking with form scope
- All 4 actions registered in `class-automation-registry.php`
- Database table `wp_superforms_temp_access` added to install migrations

#### Architecture Decisions
- PDF generation uses hybrid approach: returns HTML + config for client-side rendering (jsPDF/html2canvas) when triggered from frontend, falls back to TCPDF/DOMPDF for server-side contexts
- Calculate action uses custom recursive descent parser instead of eval() for security
- Temp access tokens stored as SHA256 hashes, never plain text
- Validate action supports cross-field validation via workflow context
