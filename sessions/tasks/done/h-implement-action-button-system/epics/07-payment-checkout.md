# Epic: Payment/Checkout

## Use Case
Initiate payment flow - integrate with Stripe, PayPal, or other payment processors. Button triggers checkout session, handles payment, and continues form flow based on result.

## User Story
As a form user, I want to click a "Pay Now" button so that I can complete my purchase and receive confirmation of my payment.

## Button Properties Required
- `action_type`: `'trigger_automation'`
- `event_id`: `'initiate_payment'`
- `button_text`: string - "Pay Now", "Checkout", "Complete Purchase"
- `loading_text`: string - "Processing payment..."
- `payment_amount`: number | string (supportsTags) - "{calculated_total}"
- `payment_currency`: string - "USD", "EUR"
- `payment_description`: string - "Order #{order_id}"
- `success_redirect`: string - URL to redirect on success
- `cancel_redirect`: string - URL if payment cancelled
- `collect_billing_address`: boolean
- `icon`: `'credit-card'` | `'shopping-cart'`

## Event Payload
```json
{
  "event": "button.initiate_payment.clicked",
  "context": {
    "form_id": 123,
    "button_id": "btn_pay_123",
    "payment_amount": 99.99,
    "payment_currency": "USD",
    "form_data": { "product": "Premium Plan", "email": "john@example.com" },
    "user_id": 1,
    "session_key": "abc123",
    "timestamp": "2025-12-15T10:30:00Z"
  }
}
```

## Compatible Automation Nodes
- `create_checkout_session` (new) - Create Stripe/PayPal checkout
- `send_email` - Send receipt/confirmation
- `create_post` - Create order record
- `webhook` - Notify external systems
- `set_variable` - Store transaction ID

## New Node Types Needed
- `create_checkout_session` - Payment gateway integration
  - Settings: provider (stripe/paypal), amount, currency, line_items, success_url, cancel_url
  - Returns: `{ checkout_url, session_id, payment_intent_id }`

- `verify_payment` - Confirm payment completed
  - Settings: session_id, expected_amount
  - Returns: `{ status, transaction_id, amount_paid }`

## Frontend Behavior
- **On click**:
  - Show loading state
  - Call automation to create checkout session
  - Redirect to payment provider (Stripe Checkout, PayPal)
  - OR open embedded payment form modal
- **Success handling**:
  - Return to success_redirect URL
  - Fire `payment.completed` event
  - Show success message
- **Cancel/Error handling**:
  - Return to cancel_redirect or stay on form
  - Show appropriate message
  - Allow retry

## Schema Fragment (Zod)
```typescript
z.object({
  action_type: z.literal('trigger_automation'),
  event_id: z.literal('initiate_payment'),
  button_text: z.string().default('Pay Now'),
  loading_text: z.string().optional(),
  payment_provider: z.enum(['stripe', 'paypal', 'square']).optional(),
  payment_amount: z.union([z.number(), z.string()]), // Can be tag like {total}
  payment_currency: z.string().default('USD'),
  payment_description: z.string().optional(),
  success_redirect: z.string().url().optional(),
  cancel_redirect: z.string().url().optional(),
  collect_billing_address: z.boolean().default(false),
  icon: z.string().default('credit-card'),
})
```

## MCP Tool Parameters
```typescript
{
  toolName: 'createButtonAutomation',
  parameters: {
    action: 'createButtonAutomation',
    formId: number,
    buttonText: 'Complete Purchase',
    eventId: 'initiate_payment',
    icon: 'credit-card',
    variant: 'primary',
    automationActions: [
      {
        type: 'create_checkout_session',
        config: {
          provider: 'stripe',
          amount: '{calculated_total}',
          currency: 'USD',
          description: 'Order from {form_title}'
        }
      }
    ]
  }
}
```

## Payment Flow Diagram
```
[Pay Now Click] → [Create Session] → [Redirect to Stripe] → [Payment] → [Webhook] → [Verify] → [Success Page]
                                                                ↓
                                                          [Cancel] → [Return to Form]
```

## Research Sources
- Stripe Checkout: Hosted checkout page pattern
- WPForms: Payment field with Stripe/PayPal integration
- Gravity Forms: PayPal/Stripe add-ons
- Form.io: Payment field component
