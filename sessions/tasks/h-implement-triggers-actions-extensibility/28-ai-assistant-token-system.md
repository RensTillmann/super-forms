# Phase 28: AI Assistant Token System

## Overview

Domain-based AI token system enabling AI-assisted form building. Tokens are tied to activated license domains, not user accounts. Users get free monthly tokens and can purchase more via Stripe Checkout.

**Dependencies:**
- api.super-forms.com (Go backend with MongoDB)
- WordPress plugin REST API
- MCP server for AI tool execution

## Architecture

### Token Model

```
Domain (e.g., example.com)
├── free_remaining: 850      # Resets monthly
├── purchased_remaining: 500 # Never expires
├── monthly_limit: 1000      # Free tier allocation
├── reset_at: 2025-02-01     # Next reset date
└── total_used: 2150         # Lifetime usage
```

**MongoDB: `ai_tokens` collection**
```go
type AITokens struct {
    Domain             string    `bson:"domain"`              // Primary key
    FreeRemaining      int       `bson:"free_remaining"`      // Monthly free tokens
    PurchasedRemaining int       `bson:"purchased_remaining"` // Paid tokens (never reset)
    MonthlyLimit       int       `bson:"monthly_limit"`       // Default: 1000
    ResetAt            time.Time `bson:"reset_at"`            // Monthly reset date
    TotalUsed          int64     `bson:"total_used"`          // Lifetime tracking
    UpdatedAt          time.Time `bson:"updated_at"`
}
```

### Token Logic

1. **Monthly Reset**: Free tokens reset to `monthly_limit` when `now > reset_at`
2. **Usage Order**: Free tokens consumed first, then purchased
3. **Purchased Tokens**: Never expire, added to pool on purchase
4. **Default Allocation**: 1000 tokens/month free per domain

```go
// Consume tokens (free first, then purchased)
func ConsumeTokens(domain string, amount int) error {
    tokens := GetTokens(domain)

    if tokens.FreeRemaining >= amount {
        tokens.FreeRemaining -= amount
    } else {
        remainder := amount - tokens.FreeRemaining
        if remainder > tokens.PurchasedRemaining {
            return errors.New("insufficient tokens")
        }
        tokens.FreeRemaining = 0
        tokens.PurchasedRemaining -= remainder
    }

    tokens.TotalUsed += int64(amount)
    return SaveTokens(tokens)
}
```

## Schema System Architecture

The AI needs comprehensive knowledge of what it can manipulate. We define schemas for five domains:

### 1. Form Elements Schema

Each form element type exports its full configuration schema.

**Source of Truth:** React components in `/src/react/admin/components/form-builder/elements/`

```typescript
// Example: TextInputElement schema
export const textInputSchema: ElementSchema = {
  tag: 'text',
  group: 'form_elements',
  name: 'Text Input',
  icon: 'type',
  description: 'Single-line text input field',
  settings: {
    general: {
      name: {
        type: 'text',
        required: true,
        description: 'Unique field identifier for data storage'
      },
      email: {
        type: 'text',
        label: 'Label',
        description: 'Label shown above the field'
      },
      placeholder: { type: 'text' },
      placeholderFilled: { type: 'text', label: 'Filled Placeholder' },
      defaultValue: { type: 'text' },
      description: { type: 'textarea', label: 'Help Text' },
    },
    validation: {
      validation: {
        type: 'select',
        values: ['none', 'empty', 'email', 'phone', 'numeric', 'alphanumeric', 'regex']
      },
      validationRegex: {
        type: 'text',
        conditional: { field: 'validation', value: 'regex' }
      },
      minLength: { type: 'number' },
      maxLength: { type: 'number' },
      required: { type: 'boolean' },
    },
    styling: {
      icon: { type: 'icon' },
      iconPosition: { type: 'select', values: ['left', 'right', 'none'] },
      width: { type: 'select', values: ['auto', '25%', '33%', '50%', '66%', '75%', '100%'] },
    },
    advanced: {
      excludeEntry: { type: 'boolean', label: 'Exclude from entry data' },
      excludeEmail: { type: 'boolean', label: 'Exclude from emails' },
    },
    conditional: {
      conditionalLogic: { type: 'conditional_rules' }, // Complex type
    }
  }
};
```

**Element Categories:**

| Category | Elements |
|----------|----------|
| **Input** | text, email, password, number, tel, url, hidden |
| **Selection** | dropdown, checkbox, radio, rating, quantity |
| **Text** | textarea, tinymce (rich text) |
| **Date/Time** | date, time, datepicker |
| **File** | file, image upload, signature |
| **Layout** | column, multipart (steps), tabs, accordion |
| **Display** | heading, html, divider, spacer, image |
| **Special** | google_map, address_autocomplete, countries, calculator |

### 2. Automation Nodes Schema

Automation workflows use a node-based system with four categories.

**Source of Truth:** Node registry in `/src/includes/automations/`

```typescript
// Example: Send Email action node
export const sendEmailNodeSchema: NodeSchema = {
  id: 'send_email',
  category: 'action',
  name: 'Send Email',
  icon: 'mail',
  description: 'Send an email using the visual email builder',
  config: {
    to: {
      type: 'text',
      required: true,
      supports_tags: true, // Can use {email}, {field_name}, etc.
      description: 'Recipient email address(es)'
    },
    subject: {
      type: 'text',
      required: true,
      supports_tags: true,
    },
    from_name: { type: 'text', supports_tags: true },
    from_email: { type: 'text', supports_tags: true },
    reply_to: { type: 'text', supports_tags: true },
    email_body: {
      type: 'email_builder', // Opens email builder modal
      description: 'Visual email content'
    },
    attachments: { type: 'file_tags' }, // {file_field_name}
  },
  outputs: ['success', 'failure'],
};
```

**Node Categories:**

| Category | Nodes |
|----------|-------|
| **Triggers** | form.submitted, entry.updated, entry.deleted, payment.completed, subscription.*, schedule, webhook |
| **Actions** | send_email, create_entry, update_entry, delete_entry, http_request, create_post, update_user, webhook, log_message |
| **Conditions** | field_comparison, entry_exists, user_role, a_b_test, custom_condition |
| **Control** | delay, schedule, stop_execution, set_variable, loop |

### 3. Form Settings Schema

Global form configuration options.

```typescript
export const formSettingsSchema: SettingsSchema = {
  general: {
    form_title: { type: 'text' },
    form_description: { type: 'textarea' },
  },
  submission: {
    submit_button_text: { type: 'text', default: 'Submit' },
    submit_button_loading: { type: 'text', default: 'Loading...' },
    success_message: { type: 'textarea' },
    error_message: { type: 'textarea' },
    redirect_url: { type: 'text' },
    redirect_method: { type: 'select', values: ['none', 'url', 'page'] },
  },
  behavior: {
    enable_ajax: { type: 'boolean', default: true },
    hide_after_submit: { type: 'boolean' },
    clear_after_submit: { type: 'boolean' },
    save_entry: { type: 'boolean', default: true },
  },
  spam: {
    honeypot_enabled: { type: 'boolean', default: true },
    time_threshold: { type: 'number', default: 3, description: 'Minimum seconds before submit' },
    duplicate_detection: { type: 'select', values: ['none', 'email', 'ip', 'hash'] },
  },
  access: {
    require_login: { type: 'boolean' },
    allowed_roles: { type: 'multi_select', values: 'wp_roles' },
    logged_in_message: { type: 'textarea' },
  },
};
```

### 4. Theme Settings Schema

Visual styling configuration.

```typescript
export const themeSettingsSchema: SettingsSchema = {
  colors: {
    primary_color: { type: 'color', default: '#3b82f6' },
    secondary_color: { type: 'color' },
    background_color: { type: 'color' },
    text_color: { type: 'color' },
    border_color: { type: 'color' },
    error_color: { type: 'color', default: '#ef4444' },
    success_color: { type: 'color', default: '#22c55e' },
  },
  typography: {
    font_family: { type: 'font_select' },
    font_size: { type: 'number', default: 14 },
    label_font_size: { type: 'number' },
    label_font_weight: { type: 'select', values: ['normal', 'medium', 'semibold', 'bold'] },
  },
  layout: {
    form_width: { type: 'text', default: '100%' },
    field_spacing: { type: 'number', default: 16 },
    border_radius: { type: 'number', default: 4 },
    input_height: { type: 'number', default: 40 },
  },
  buttons: {
    button_style: { type: 'select', values: ['filled', 'outline', 'minimal'] },
    button_border_radius: { type: 'number' },
    button_full_width: { type: 'boolean' },
  },
};
```

### 5. Translations Schema

Multi-language support structure.

```typescript
export const translationsSchema = {
  supported_languages: ['en', 'es', 'fr', 'de', 'nl', 'it', 'pt', 'ja', 'zh', 'ko', 'ar', 'ru'],
  translatable_properties: [
    'email',        // Field label
    'placeholder',
    'description',
    'validation_message',
    'dropdown_items[].label',
    'checkbox_items[].label',
    'radio_items[].label',
    'submit_button_text',
    'success_message',
    'error_message',
  ],
  structure: {
    // Translations stored per element per language
    'element_name': {
      'property': 'translated_value'
    }
  }
};
```

## API Endpoints (api.super-forms.com)

### Token Management

```go
// Get token status for domain
GET /v1/ai/tokens?domain=example.com
Headers: License-Key: XXXX-XXXX-XXXX
Response: {
  "free_remaining": 850,
  "purchased_remaining": 500,
  "total_available": 1350,
  "monthly_limit": 1000,
  "reset_at": "2025-02-01T00:00:00Z"
}

// Generate AI content (consumes tokens)
POST /v1/ai/generate
Headers: License-Key: XXXX-XXXX-XXXX
Body: {
  "domain": "example.com",
  "prompt": "Add a required email field with validation",
  "context": {
    "form_id": 123,
    "current_elements": [...],
    "available_schemas": {...}  // Sent from WP or fetched
  }
}
Response: {
  "success": true,
  "operations": [
    { "tool": "add_form_element", "args": {...} }
  ],
  "tokens_used": 45,
  "tokens_remaining": 1305
}

// Create Stripe Checkout for token purchase
POST /v1/ai/tokens/checkout
Headers: License-Key: XXXX-XXXX-XXXX
Body: {
  "domain": "example.com",
  "package": "5000",  // 1000, 5000, or 20000
  "success_url": "https://example.com/wp-admin/...",
  "cancel_url": "https://example.com/wp-admin/..."
}
Response: {
  "checkout_url": "https://checkout.stripe.com/..."
}

// Stripe webhook (internal)
POST /v1/webhooks/stripe
Event: checkout.session.completed
Metadata: { "type": "ai_tokens", "domain": "example.com", "amount": 5000 }
Action: Add 5000 to purchased_remaining for domain
```

### WordPress Schema Endpoints

```php
// Get all element schemas
GET /wp-json/super-forms/v1/schema/elements
Response: {
  "text": { ...textInputSchema },
  "email": { ...emailInputSchema },
  "dropdown": { ...dropdownSchema },
  // All element types
}

// Get all node schemas
GET /wp-json/super-forms/v1/schema/nodes
Response: {
  "triggers": { "form.submitted": {...}, ... },
  "actions": { "send_email": {...}, ... },
  "conditions": { "field_comparison": {...}, ... },
  "control": { "delay": {...}, ... }
}

// Get form settings schema
GET /wp-json/super-forms/v1/schema/settings
Response: { ...formSettingsSchema }

// Get theme schema
GET /wp-json/super-forms/v1/schema/theme
Response: { ...themeSettingsSchema }

// Get translations schema
GET /wp-json/super-forms/v1/schema/translations
Response: { ...translationsSchema }
```

## Token Packages (Stripe Products)

| Package | Tokens | Price | Per Token | Product ID |
|---------|--------|-------|-----------|------------|
| Starter | 1,000 | $5 | $0.005 | prod_starter_tokens |
| Pro | 5,000 | $20 | $0.004 | prod_pro_tokens |
| Agency | 20,000 | $60 | $0.003 | prod_agency_tokens |

## MCP Server Updates

The MCP server needs to be updated to:

1. **Fetch schemas on startup** from WordPress REST API
2. **Include full element schemas** in tool definitions
3. **Add new tools** for nodes, settings, theme, translations

```javascript
// server.js - Updated initialization
async function initialize() {
  // Fetch all schemas from WordPress
  const [elements, nodes, settings, theme, translations] = await Promise.all([
    fetch(`${WP_SITE_URL}/wp-json/super-forms/v1/schema/elements`),
    fetch(`${WP_SITE_URL}/wp-json/super-forms/v1/schema/nodes`),
    fetch(`${WP_SITE_URL}/wp-json/super-forms/v1/schema/settings`),
    fetch(`${WP_SITE_URL}/wp-json/super-forms/v1/schema/theme`),
    fetch(`${WP_SITE_URL}/wp-json/super-forms/v1/schema/translations`),
  ]);

  // Build dynamic tool definitions based on schemas
  schemas = { elements, nodes, settings, theme, translations };
}
```

**New MCP Tools:**

| Tool | Purpose |
|------|---------|
| `add_form_element` | Add element with full config |
| `update_form_element` | Update any element property |
| `remove_form_element` | Remove element by index/id |
| `move_form_element` | Reorder elements |
| `update_form_settings` | Modify form settings |
| `update_theme_settings` | Modify theme/styling |
| `add_automation_node` | Add node to workflow |
| `update_automation_node` | Configure node properties |
| `connect_automation_nodes` | Create connections |
| `add_translation` | Add/update translations |
| `add_conditional_logic` | Set element visibility rules |

## WordPress Admin UI

### AI Assistant Panel

Located in form builder sidebar:

```
┌─────────────────────────────────────────┐
│ AI Assistant                   1,350 │
│ ─────────────────────────────────────── │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ What would you like to do?          │ │
│ │                                     │ │
│ │ Examples:                           │ │
│ │ • Add a required email field        │ │
│ │ • Create a multi-step form          │ │
│ │ • Add spam protection               │ │
│ │ • Translate form to Spanish         │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ [────────────────────────────] [Send]   │
│                                         │
│ ⚠️ Running low on tokens?               │
│ [Purchase More Tokens]                  │
└─────────────────────────────────────────┘
```

### Token Purchase Flow

1. User clicks "Purchase More Tokens"
2. Modal shows package options (1K, 5K, 20K)
3. User selects package → redirects to Stripe Checkout
4. After payment → returns to form builder
5. Token count refreshes automatically

## Component Architecture

### Form Builder Elements

```
/src/react/admin/components/form-builder/
├── FormBuilder.tsx              # Main component
├── elements/
│   ├── index.ts                 # Exports all elements + schemas
│   ├── registry.ts              # Element type registry
│   │
│   ├── TextInputElement.tsx     # Each element exports:
│   ├── TextareaElement.tsx      #   - React component
│   ├── DropdownElement.tsx      #   - Schema definition
│   ├── CheckboxElement.tsx      #   - Property panel config
│   ├── RadioElement.tsx
│   ├── FileUploadElement.tsx
│   ├── DateElement.tsx
│   ├── ColumnElement.tsx
│   ├── MultipartElement.tsx
│   ├── HeadingElement.tsx
│   ├── HtmlElement.tsx
│   ├── DividerElement.tsx
│   ├── SpacerElement.tsx
│   ├── ImageElement.tsx         # Can import from email-builder
│   └── ...
│
├── Canvas/
│   ├── FormCanvas.tsx
│   └── ElementRenderer.tsx
│
├── PropertyPanels/
│   ├── ElementProperties.tsx
│   └── FormSettings.tsx
│
└── AIAssistant/
    ├── AIAssistantPanel.tsx
    ├── TokenDisplay.tsx
    ├── PurchaseModal.tsx
    └── PromptInput.tsx
```

### Email Builder (existing)

```
/src/react/admin/components/email-builder/
├── Builder/
│   └── Elements/
│       ├── TextElement.jsx      # Email-specific text
│       ├── ImageElement.jsx     # Shared pattern
│       ├── ButtonElement.jsx
│       ├── DividerElement.jsx
│       ├── SpacerElement.jsx
│       ├── ColumnsElement.jsx
│       └── ...
```

**Sharing Pattern:**
- FormBuilder imports specific email-builder elements when needed
- No shared directory - direct imports
- Example: `import { ImageElement } from '@/components/email-builder/Builder/Elements/ImageElement'`

## Implementation Steps

### Part A: API Infrastructure (api.super-forms.com)

1. Add `ai_tokens` MongoDB collection + indexes
2. Create `AITokens` struct in models.go
3. Add helper functions: `GetTokens()`, `ConsumeTokens()`, `AddTokens()`, `ResetMonthlyTokens()`
4. `GET /v1/ai/tokens` - Token status endpoint
5. `POST /v1/ai/generate` - Main AI generation endpoint (sync)
6. `POST /v1/ai/tokens/checkout` - Create Stripe Checkout session
7. Update Stripe webhook to handle `ai_tokens` purchase type

### Part B: WordPress Schema Endpoints

8. Create `SUPER_Schema_Controller` class
9. `GET /v1/schema/elements` - Export element schemas from PHP
10. `GET /v1/schema/nodes` - Export automation node schemas
11. `GET /v1/schema/settings` - Export form settings schema
12. `GET /v1/schema/theme` - Export theme settings schema
13. `GET /v1/schema/translations` - Export translations schema

### Part C: MCP Server Updates

14. Update server.js to fetch schemas on startup
15. Generate dynamic tool definitions from schemas
16. Add new tools: node management, settings, theme, translations
17. Cache schemas with TTL (5 min)

### Part D: React Form Builder Elements

18. Create element registry pattern
19. Port first element (TextInput) with full schema
20. Port remaining input elements
21. Port selection elements
22. Port layout elements
23. Port display elements

### Part E: WordPress Admin UI

24. Create AIAssistantPanel component
25. Create TokenDisplay component
26. Create PurchaseModal component
27. Integrate AI panel into form builder sidebar
28. Add token refresh after purchase return

### Part F: Integration Testing

29. Test token consumption flow
30. Test purchase flow (Stripe test mode)
31. Test AI generation → MCP → WordPress
32. Test monthly reset logic
33. Test schema endpoint caching

## Success Criteria

- [ ] Tokens tracked per domain (free + purchased separate)
- [ ] Monthly free allocation with automatic reset
- [ ] Purchase flow works from WordPress admin
- [ ] AI generation consumes tokens correctly
- [ ] Token exhaustion handled gracefully (error message, purchase prompt)
- [ ] All element schemas exposed via REST API
- [ ] All node schemas exposed via REST API
- [ ] Form settings schema exposed via REST API
- [ ] Theme settings schema exposed via REST API
- [ ] Translations schema exposed via REST API
- [ ] MCP server fetches schemas dynamically
- [ ] AI can add/update elements with full configuration
- [ ] AI can configure automation nodes
- [ ] AI can modify form/theme settings
- [ ] AI can add translations

## Cost Estimation

- Claude Haiku: ~$0.25/1M input tokens, ~$1.25/1M output
- Average request: ~2000 input + ~500 output tokens = ~$0.001
- Selling at $0.005/token (for 1000-token package)
- Margin: ~5x on small packages, higher on larger packages
- Covers API costs + infrastructure + profit

## Security Considerations

1. **License Validation**: Every API request validates license key owns the domain
2. **Rate Limiting**: Prevent abuse of generate endpoint (10 req/min per domain)
3. **Token Verification**: Double-check token balance before Claude API call
4. **Webhook Security**: Stripe signature verification for purchases
5. **Schema Access**: Public endpoints (no auth needed for schema, no sensitive data)

## Future Enhancements

- **Async Generation**: For complex operations (generate full form from description)
- **Generation History**: Track what AI generated for undo/learning
- **Custom Instructions**: Per-form AI behavior preferences
- **Template Library**: AI-curated form templates
- **Usage Analytics**: Which features are most requested via AI
