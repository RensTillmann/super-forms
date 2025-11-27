# Phase 9: Test Fixture System and Payment OAuth Integration

## Overview

This phase implements a comprehensive test fixture system for automated testing of the trigger/action system, plus OAuth integration for simplified payment gateway connections (Stripe, PayPal).

**Goals:**
1. Create programmatic test forms with all field types for PHPUnit testing
2. Enable real integration testing with triggers and actions attached
3. Implement OAuth flows for Stripe and PayPal (hybrid approach)
4. Test payment-specific events and webhook handling

## Part A: Test Fixture System

### Test Form Factory

Create `SUPER_Test_Form_Factory` class that generates comprehensive test forms:

**Comprehensive Form Features:**
- Multi-step form (3 steps: Personal Info → Project Details → Review)
- Dynamic columns (repeater/add more) for team members
- Conditional logic (enterprise requirements show when budget > $25K)
- All field types: text, email, phone, dropdown, checkbox, radio, textarea, file upload
- Hidden fields with dynamic values (`{server_timestamp}`)
- Signature field (if add-on structure)
- PDF generation settings enabled

**Form Types to Generate:**
| Method | Description |
|--------|-------------|
| `create_comprehensive_form()` | Full form with all features |
| `create_simple_form()` | Basic contact form |
| `create_multistep_form()` | Steps without signature/PDF |
| `create_payment_form($gateway)` | PayPal or Stripe checkout |
| `create_subscription_form($gateway)` | Recurring payment form |
| `create_woocommerce_form()` | WC product selection |
| `create_registration_form()` | User registration |

**Matching Test Data:**
```php
SUPER_Test_Form_Factory::get_test_submission_data(); // Comprehensive form
SUPER_Test_Form_Factory::get_simple_submission_data(); // Simple form
SUPER_Test_Form_Factory::get_payment_submission_data($amount); // Payment form
```

### Trigger Factory

Create `SUPER_Test_Trigger_Factory` class for attaching triggers:

```php
SUPER_Test_Trigger_Factory::create_trigger_set($form_id, [
    'on_submit_log' => true,        // log_message action
    'on_submit_webhook' => true,    // webhook to test endpoint
    'on_high_budget' => true,       // conditional: budget > $25K
    'on_spam_detected' => true,     // spam event handler
    'on_entry_created' => true,     // entry.created event
    'on_payment_success' => true,   // payment.completed event
    'on_subscription_created' => true, // subscription events
]);
```

### Webhook Simulator

Create `SUPER_Webhook_Simulator` for testing payment webhooks:

```php
// Simulate Stripe webhook with valid signature
SUPER_Webhook_Simulator::stripe('payment_intent.succeeded', [
    'payment_intent' => 'pi_test_123',
    'amount' => 2999,
    'metadata' => ['form_id' => $form_id, 'entry_id' => $entry_id],
]);

// Simulate PayPal webhook
SUPER_Webhook_Simulator::paypal('PAYMENT.CAPTURE.COMPLETED', [
    'id' => 'WH-TEST-123',
    'resource' => ['amount' => ['value' => '29.99']],
]);
```

### File Structure

```
tests/
├── fixtures/
│   ├── class-form-factory.php           # Base form factory (~600 lines)
│   ├── class-payment-form-factory.php   # PayPal/Stripe forms (~400 lines)
│   ├── class-addon-form-factory.php     # WC, Registration forms (~300 lines)
│   ├── class-trigger-factory.php        # Trigger creation (~250 lines)
│   └── class-webhook-simulator.php      # Payment webhook mocks (~400 lines)
│
├── integration/
│   ├── test-full-submission-flow.php    # Core form submission
│   ├── test-payment-flows.php           # Payment processing
│   ├── test-webhook-handling.php        # Webhook verification
│   ├── test-subscription-lifecycle.php  # Full subscription cycle
│   └── test-multi-gateway.php           # Gateway switching
│
└── mocks/
    ├── class-stripe-mock.php            # Mock Stripe API responses
    ├── class-paypal-mock.php            # Mock PayPal API responses
    └── class-webhook-signatures.php     # Generate valid webhook signatures
```

### Integration Test Cases

| Test | Description |
|------|-------------|
| `test_form_creation_with_all_elements()` | Verify comprehensive form structure |
| `test_trigger_fires_on_submission()` | Form submit → events fire |
| `test_conditional_trigger_execution()` | Conditions evaluated correctly |
| `test_repeater_fields_in_context()` | Dynamic columns data captured |
| `test_multi_step_events()` | Step navigation fires events |
| `test_action_execution_results()` | Actions return expected results |
| `test_payment_success_flow()` | Payment → entry update → confirmation |
| `test_subscription_lifecycle()` | Create → renew → cancel flow |
| `test_webhook_signature_verification()` | Invalid signatures rejected |
| `test_webhook_idempotency()` | Same event doesn't process twice |

---

## Part B: Payment OAuth Integration

### Architecture Decision: Hybrid Approach

**Default: Platform OAuth (Quick Connect)**
- Super Forms registers OAuth apps with Stripe/PayPal
- Users click "Connect" → authorize → done
- Requires small token-exchange server (super-forms.com/oauth/)

**Fallback: Manual Configuration**
- Users enter their own API keys
- Or create their own OAuth apps
- Full control for enterprise/agencies

### Stripe Connect OAuth

**Flow:**
```
1. User clicks "Connect with Stripe"
2. Redirect to Stripe OAuth:
   https://connect.stripe.com/oauth/authorize
   ?client_id={SUPER_FORMS_PLATFORM_CLIENT_ID}
   &redirect_uri=https://super-forms.com/oauth/stripe/callback
   &response_type=code
   &scope=read_write
   &state={csrf_token}_{site_url_hash}

3. User authorizes on Stripe

4. Stripe redirects to super-forms.com with code

5. super-forms.com exchanges code for tokens:
   POST https://connect.stripe.com/oauth/token
   - Returns: access_token, refresh_token, stripe_user_id

6. super-forms.com redirects back to user site with encrypted tokens

7. User site stores tokens via SUPER_Trigger_Credentials
```

**Token Storage:**
```php
// Stored encrypted in wp_superforms_api_credentials table
SUPER_Trigger_Credentials::store('stripe', 'oauth_tokens', [
    'stripe_user_id' => 'acct_...',
    'access_token' => 'sk_live_...',
    'refresh_token' => 'rt_...',
    'livemode' => true,
    'scope' => 'read_write',
    'connected_at' => time(),
], $user_id);
```

**Auto-Webhook Configuration:**
After OAuth connection, automatically register webhook endpoint:
```php
// Create webhook endpoint in connected Stripe account
\Stripe\WebhookEndpoint::create([
    'url' => site_url('/wp-json/super-forms/v1/webhooks/stripe'),
    'enabled_events' => SUPER_Stripe::$required_events,
]);
```

### PayPal OAuth

**Flow:**
```
1. User clicks "Connect with PayPal"
2. Redirect to PayPal:
   https://www.paypal.com/connect
   ?client_id={SUPER_FORMS_PAYPAL_CLIENT_ID}
   &redirect_uri=https://super-forms.com/oauth/paypal/callback
   &response_type=code
   &scope=openid profile email

3. User authorizes

4. super-forms.com exchanges code for tokens

5. Redirect back with encrypted tokens

6. Store via SUPER_Trigger_Credentials
```

### Admin UI: Connection Settings

```
┌─────────────────────────────────────────────────────────────────┐
│  Super Forms Settings → Payments → Stripe                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Connection Method:                                             │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ ● Quick Connect (Recommended)                          │    │
│  │   One-click setup via Super Forms                      │    │
│  │   [Connect with Stripe]                                │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ ○ Manual / Advanced                                    │    │
│  │   Use your own API keys                                │    │
│  │                                                         │    │
│  │   Secret Key: [sk_live_•••••••••••••••]               │    │
│  │   Webhook Secret: [whsec_•••••••••••]                 │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Mode: ○ Test   ● Live                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Form Builder Integration

```
┌─────────────────────────────────────────────────────────────────┐
│  Form Builder → Payment Settings                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Payment Gateway:                                               │
│  ○ None                                                        │
│  ● Stripe  [✓ Connected]                                       │
│  ○ PayPal  [Not Connected] [Connect →]                         │
│                                                                  │
│  Payment Type:                                                  │
│  ○ One-time Payment                                            │
│  ● Subscription                                                 │
│                                                                  │
│  Stripe Products: (auto-loaded from connected account)         │
│  ☑ Pro Plan - $29.99/month                                     │
│  □ Enterprise - $99.99/month                                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Payment Events to Register

**Stripe Events:**
| Event ID | Description | Context Fields |
|----------|-------------|----------------|
| `payment.stripe.checkout_completed` | Checkout session completed | session_id, amount, customer_id |
| `payment.stripe.payment_succeeded` | Payment intent succeeded | payment_intent_id, amount, metadata |
| `payment.stripe.payment_failed` | Payment failed | error_code, error_message, payment_intent_id |
| `subscription.stripe.created` | Subscription created | subscription_id, plan_id, customer_id |
| `subscription.stripe.updated` | Subscription changed | subscription_id, previous_plan, new_plan |
| `subscription.stripe.cancelled` | Subscription cancelled | subscription_id, cancel_reason |
| `subscription.stripe.invoice_paid` | Invoice payment succeeded | invoice_id, amount, period_start, period_end |
| `subscription.stripe.invoice_failed` | Invoice payment failed | invoice_id, attempt_count, next_retry |

**PayPal Events:**
| Event ID | Description | Context Fields |
|----------|-------------|----------------|
| `payment.paypal.capture_completed` | Payment captured | capture_id, amount, currency |
| `payment.paypal.capture_denied` | Payment denied | capture_id, reason |
| `payment.paypal.capture_refunded` | Payment refunded | capture_id, refund_id, amount |
| `subscription.paypal.created` | Subscription created | subscription_id, plan_id |
| `subscription.paypal.activated` | Subscription activated | subscription_id, billing_start |
| `subscription.paypal.cancelled` | Subscription cancelled | subscription_id, reason |
| `subscription.paypal.suspended` | Subscription suspended | subscription_id, reason |
| `subscription.paypal.payment_failed` | Subscription payment failed | subscription_id, retry_date |

---

## Part C: Token Exchange Server

### Why Needed

The OAuth client_secret cannot be bundled in the plugin (anyone could extract it). A small server-side component handles the secure token exchange.

### Endpoints

```
POST https://super-forms.com/oauth/stripe/token
Body: {
    code: "...",
    site_url: "https://usersite.com",
    nonce: "..."
}
Response: {
    encrypted_tokens: "...",
    stripe_user_id: "acct_...",
    livemode: true
}

POST https://super-forms.com/oauth/paypal/token
Body: { code: "...", site_url: "...", nonce: "..." }
Response: { encrypted_tokens: "...", merchant_id: "..." }

POST https://super-forms.com/oauth/stripe/refresh
Body: { refresh_token_encrypted: "...", site_url: "..." }
Response: { encrypted_tokens: "..." }
```

### Security

- Site URL validated against WordPress pingback
- Nonce prevents replay attacks
- Tokens encrypted with site-specific key before return
- Rate limiting on all endpoints
- Audit logging of all OAuth operations

---

## Success Criteria

### Part A: Test Fixtures
- [ ] `SUPER_Test_Form_Factory` creates forms with all field types
- [ ] Multi-step, repeater, conditional, signature, PDF all work
- [ ] `SUPER_Test_Trigger_Factory` attaches triggers correctly
- [ ] `SUPER_Webhook_Simulator` generates valid webhook payloads
- [ ] Integration tests pass for full submission flow
- [ ] Tests run automatically with `./sync-to-webserver.sh --test`

### Part B: Payment OAuth
- [x] Stripe Quick Connect flow implemented (SUPER_Payment_OAuth class)
- [x] PayPal Quick Connect flow implemented (SUPER_Payment_OAuth class)
- [x] Manual API key entry works (fallback in SUPER_Payment_OAuth)
- [ ] Test/Live mode toggle functions correctly (needs admin UI)
- [ ] Webhook endpoints auto-configured after OAuth (needs super-forms.com platform)
- [ ] Token refresh works when access token expires (needs super-forms.com platform)
- [ ] Form builder shows connection status (needs admin UI)

### Part C: Payment Events
- [x] All Stripe events registered in Registry (8 events)
- [x] All PayPal events registered in Registry (8 events)
- [x] Webhook handlers fire correct events (REST endpoints implemented)
- [x] Signature verification rejects invalid webhooks (Stripe HMAC-SHA256, PayPal API verification)
- [x] Events include all documented context fields (context building in webhook handlers)
- [ ] Idempotency prevents duplicate processing (future enhancement)

---

## Implementation Order

1. **A1: Form Factory** - Create basic form factory class
2. **A2: Trigger Factory** - Create trigger attachment helpers
3. **A3: Integration Tests** - Basic submission flow tests
4. **A4: Payment Forms** - Stripe/PayPal form generators
5. **A5: Webhook Simulator** - Mock webhook generation
6. **A6: Payment Integration Tests** - Full payment flow tests
7. **B1: Settings UI** - Connection method selection
8. **B2: Stripe OAuth** - Quick Connect implementation
9. **B3: PayPal OAuth** - Quick Connect implementation
10. **B4: Form Builder UI** - Gateway selection and status
11. **C1: Token Server** - Implement exchange endpoints
12. **C2: Payment Events** - Register all payment events
13. **C3: Webhook Handlers** - Update handlers to fire events

---

## Configuration Requirements

### Test Environment
```php
// wp-config.php or environment variables
define('SUPER_STRIPE_TEST_KEY', 'sk_test_...');
define('SUPER_STRIPE_TEST_WEBHOOK_SECRET', 'whsec_...');
define('SUPER_PAYPAL_SANDBOX_CLIENT_ID', '...');
define('SUPER_PAYPAL_SANDBOX_SECRET', '...');
```

### Production OAuth (super-forms.com)
```
STRIPE_PLATFORM_CLIENT_ID=ca_...
STRIPE_PLATFORM_SECRET=sk_live_...
PAYPAL_PLATFORM_CLIENT_ID=...
PAYPAL_PLATFORM_SECRET=...
TOKEN_ENCRYPTION_KEY=...
```

---

## Notes

- OAuth platform keys stored on super-forms.com, NOT in plugin
- All credentials encrypted using `SUPER_Trigger_Credentials` (AES-256-CBC)
- Test fixtures automatically clean up after test runs
- Webhook simulator generates valid signatures for testing
- Integration tests can run against real sandbox APIs when credentials provided

---

## Work Log

### 2025-11-23

#### Completed
- Created `SUPER_Payment_OAuth` class (`/src/includes/class-payment-oauth.php`, ~700 lines)
  - Stripe Connect OAuth flow (Standard/Express account types)
  - PayPal OAuth flow implementation
  - Manual API key configuration as fallback
  - Token storage integration via `SUPER_Trigger_Credentials`
  - AJAX handlers for connect/disconnect/status operations
  - Platform OAuth availability checking (super-forms.com server)
- Registered 16 payment events in `/src/includes/triggers/class-trigger-registry.php`
  - 8 Stripe events: checkout_completed, payment_succeeded, payment_failed, subscription.created/updated/cancelled, invoice_paid/failed
  - 8 PayPal events: capture_completed/denied/refunded, subscription.created/activated/cancelled/suspended/payment_failed
- Implemented webhook REST endpoints (`/src/includes/class-trigger-rest-controller.php`, ~600 lines added)
  - `POST /wp-json/super-forms/v1/webhooks/stripe` - Stripe webhook handler
  - `POST /wp-json/super-forms/v1/webhooks/paypal` - PayPal webhook handler
  - Stripe signature verification (HMAC-SHA256)
  - PayPal signature verification via API call
  - Event mapping from gateway events to Super Forms trigger events
  - Context building for trigger execution
  - Graceful handling when credentials not configured

#### Test Results
- 251 triggers tests, 870 assertions - all passing
- 17 integration tests, 100 assertions - all passing
- Total: 268 tests passing

#### Created
- Phase 10 subtask (`10-implement-payment-webhook-tests.md`) for payment webhook testing
  - Payment event registration tests
  - Stripe signature verification tests (security-critical)
  - Event mapping tests
  - Context building tests

#### Next Steps
- Complete Phase 10: Payment webhook tests (see `10-implement-payment-webhook-tests.md`)
- Implement admin UI for payment connection settings (Settings -> Payments)
- Add test/live mode toggle to admin UI
- Implement form builder gateway selection UI
