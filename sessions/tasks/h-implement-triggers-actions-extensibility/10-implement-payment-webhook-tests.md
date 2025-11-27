# Subtask 10: Payment Webhook & OAuth Tests

## Overview

Write targeted unit tests for the payment webhook handlers and OAuth infrastructure implemented in Phase 9 Part B. Focus on pure functions and security-critical paths while skipping external API dependencies.

## Test Files to Create

### `tests/triggers/test-payment-events.php`

Main test file covering:

1. **Payment Event Registration**
2. **Stripe Signature Verification**
3. **Event Mapping**
4. **Context Building**

## Test Cases

### 1. Payment Event Registration (~5 min)

**Purpose:** Verify all 16 payment events are registered correctly in the trigger registry.

```php
public function test_stripe_payment_events_registered()
public function test_stripe_subscription_events_registered()
public function test_paypal_payment_events_registered()
public function test_paypal_subscription_events_registered()
public function test_payment_events_have_correct_category()
public function test_payment_events_have_context_fields()
```

**Assertions:**
- 8 Stripe events exist with category 'payment'
- 8 PayPal events exist with category 'payment'
- Events have appropriate context field definitions

### 2. Stripe Signature Verification (~15 min) - SECURITY CRITICAL

**Purpose:** Ensure webhook signature verification correctly validates/rejects requests.

```php
public function test_valid_stripe_signature_passes()
public function test_invalid_stripe_signature_rejected()
public function test_expired_timestamp_rejected()
public function test_malformed_signature_header_rejected()
public function test_missing_signature_components_rejected()
```

**Test Data:**
- Generate valid HMAC-SHA256 signatures with known secret
- Test 5-minute timestamp tolerance boundary
- Test signature format parsing (t=,v1=)

### 3. Event Mapping (~5 min)

**Purpose:** Verify correct mapping from gateway events to Super Forms trigger events.

```php
public function test_stripe_checkout_completed_maps_correctly()
public function test_stripe_payment_intent_succeeded_maps_correctly()
public function test_stripe_subscription_events_map_correctly()
public function test_paypal_capture_events_map_correctly()
public function test_paypal_subscription_events_map_correctly()
public function test_unknown_event_type_not_mapped()
```

**Assertions:**
- `checkout.session.completed` -> `payment.stripe.checkout_completed`
- `PAYMENT.CAPTURE.COMPLETED` -> `payment.paypal.capture_completed`
- Unknown types return null/not found

### 4. Context Building (~20 min)

**Purpose:** Verify webhook payload parsing extracts correct context for trigger execution.

```php
// Stripe context tests
public function test_stripe_checkout_session_context_extraction()
public function test_stripe_payment_intent_context_extraction()
public function test_stripe_subscription_context_extraction()
public function test_stripe_invoice_context_extraction()
public function test_stripe_metadata_form_id_extraction()
public function test_stripe_amount_conversion_cents_to_display()

// PayPal context tests
public function test_paypal_capture_context_extraction()
public function test_paypal_subscription_context_extraction()
public function test_paypal_custom_id_json_parsing()
public function test_paypal_amount_display_formatting()
```

**Test Data:** Mock webhook payloads matching Stripe/PayPal format.

## What to Skip (External Dependencies)

- PayPal API webhook verification (requires live API call)
- Full OAuth callback flow (requires super-forms.com server)
- WordPress REST endpoint integration tests (complex mocking)
- Token refresh flows (requires stored OAuth tokens)

## Success Criteria

- [ ] All 16 payment events verified as registered
- [ ] Stripe signature verification 100% covered
- [ ] Event mapping for all 16 events tested
- [ ] Context building handles all event types
- [ ] Edge cases: missing fields, malformed data, boundary conditions
- [ ] Tests pass in CI (no external dependencies)

## Estimated Time

| Component | Time |
|-----------|------|
| Payment event registration | 5 min |
| Stripe signature verification | 15 min |
| Event mapping | 5 min |
| Context building (Stripe) | 10 min |
| Context building (PayPal) | 10 min |
| **Total** | **~45 min** |

## Implementation Notes

### Accessing Private Methods for Testing

The signature verification and context building methods are private in `SUPER_Trigger_REST_Controller`. Options:

1. **Reflection** - Use PHP Reflection to test private methods
2. **Extract to separate class** - Create `SUPER_Payment_Webhook_Handler` with public methods
3. **Test via public endpoint** - Mock WordPress REST request (complex)

Recommendation: Use Reflection for signature verification (security-critical), test context building via mock webhook payloads through the public endpoint.

### Mock Webhook Payloads

Create fixture files or inline arrays matching actual Stripe/PayPal webhook formats:
- `tests/fixtures/stripe-checkout-completed.json`
- `tests/fixtures/stripe-payment-intent-succeeded.json`
- `tests/fixtures/paypal-capture-completed.json`
- etc.

## Dependencies

- Existing test infrastructure (bootstrap.php, test case base classes)
- `SUPER_Trigger_Registry` for event verification
- `SUPER_Trigger_REST_Controller` for webhook handlers

## Work Log

### Session [Date]
- Created subtask file
