# Phase 18: Payment Architecture - Epics & User Flows

This document describes user stories, use-cases, and detailed flows for the payment architecture.

---

## Epic 1: One-Time Payment Processing

### User Story
> As a **form owner**, I want to sell products/services via my forms and track all payments in WordPress, so I don't have to check Stripe/PayPal dashboards constantly.

### Use Cases

#### UC1.1: Simple Product Payment
**Scenario:** Selling a digital download (e.g., e-book, template pack)

**Frontend Flow:**
```
1. User visits → form-with-payment-page.com
2. User fills form:
   - Name: John Doe
   - Email: john@example.com
   - Product: Premium Template Pack ($49)
3. User clicks "Purchase Now"
4. [Stripe Elements] Card form appears inline
5. User enters card: 4242 4242 4242 4242
6. User clicks "Pay $49.00"
7. [Loading] Processing payment...
8. [Success]
   - "Thank you! Your purchase is complete."
   - "Download link sent to john@example.com"
   - "Transaction ID: SF-PAY-00001"
```

**Backend Flow:**
```
1. Form submission received
2. Entry created: status = 'pending_payment'
3. Stripe PaymentIntent created
4. Payment confirmed via Stripe.js
5. Webhook: payment_intent.succeeded
6. Payment record created:
   - gateway: stripe
   - gateway_payment_id: pi_xxx
   - resource_type: entry
   - resource_id: 123
   - amount: 4900
   - status: completed
7. Entry updated: status = 'paid'
8. Trigger fired: payment.stripe.payment_succeeded
9. Email action sends download link
```

**Admin View:**
```
Super Forms > Payments
┌─────────────────────────────────────────────────────────────────────┐
│ Payments                                              [Export CSV]  │
├─────────────────────────────────────────────────────────────────────┤
│ Filter: [All Forms ▼] [All Statuses ▼] [Last 30 days ▼] [Search]   │
├─────────────────────────────────────────────────────────────────────┤
│ □  ID      Form            Customer        Amount   Status    Date  │
├─────────────────────────────────────────────────────────────────────┤
│ □  #001    Product Sale    john@exam...    $49.00   ● Paid    Today │
│ □  #002    Donation Form   jane@exam...    $25.00   ● Paid    Today │
│ □  #003    Event Ticket    bob@exampl...   $75.00   ○ Pending Today │
└─────────────────────────────────────────────────────────────────────┘
```

---

#### UC1.2: Variable Amount (Donations)
**Scenario:** Charity accepting custom donation amounts

**Frontend Flow:**
```
1. User visits → charity-org.com/donate
2. User fills form:
   - Donor Name: Sarah Smith
   - Email: sarah@example.com
   - Donation Amount: [Custom] $150
   - Message: "In memory of John"
   - □ Make this donation anonymous
3. User selects payment method: [Credit Card] [PayPal]
4. User completes payment
5. [Success]
   - "Thank you for your generous donation of $150!"
   - "Tax receipt sent to sarah@example.com"
```

**Payment Record:**
```json
{
  "id": 2,
  "gateway": "paypal",
  "gateway_payment_id": "CAPTURE-1234567890",
  "resource_type": "entry",
  "resource_id": 456,
  "amount": 15000,
  "currency": "USD",
  "status": "completed",
  "payment_type": "one_time",
  "meta": {
    "_billing_name": "Sarah Smith",
    "_billing_email": "sarah@example.com",
    "_receipt_url": "https://..."
  }
}
```

---

#### UC1.3: Multi-Item Order
**Scenario:** Registration form with multiple add-ons

**Frontend Flow:**
```
1. User visits → conference.com/register
2. User fills form:
   - Attendee: Mike Johnson
   - Email: mike@company.com
   - Ticket Type: [Early Bird] $299
   - Add-ons:
     ☑ Workshop: React Advanced ($99)
     ☑ Workshop: AI Integration ($99)
     ☐ Networking Dinner ($50)
   - T-Shirt Size: Large
3. Order Summary:
   - Early Bird Ticket: $299
   - React Workshop: $99
   - AI Workshop: $99
   - Subtotal: $497
   - Discount (EARLYBIRD20): -$99.40
   - Total: $397.60
4. User pays
5. [Success] Confirmation with QR code ticket
```

**Payment Meta:**
```json
{
  "_line_items": [
    {"name": "Early Bird Ticket", "amount": 29900, "quantity": 1},
    {"name": "React Workshop", "amount": 9900, "quantity": 1},
    {"name": "AI Workshop", "amount": 9900, "quantity": 1}
  ],
  "_coupon_code": "EARLYBIRD20",
  "_discount_amount": 9940,
  "_subtotal": 49700,
  "_tax_amount": 0
}
```

---

## Epic 2: Subscription Management

### User Story
> As a **membership site owner**, I want customers to subscribe to recurring plans and manage their subscriptions from my site, without needing to access Stripe directly.

### Use Cases

#### UC2.1: Monthly Membership Subscription
**Scenario:** Online course platform with monthly access

**Frontend Flow - Signup:**
```
1. User visits → courses.com/join
2. User selects plan:
   ○ Monthly ($29/month)
   ● Annual ($249/year - Save 28%)
3. User fills form:
   - Name: Emily Chen
   - Email: emily@startup.com
   - Password: ********
4. User enters payment
5. [Success]
   - "Welcome to Pro Membership!"
   - "Your subscription renews on Jan 24, 2026"
   - "Access your courses: [Go to Dashboard]"
```

**Subscription Record:**
```json
{
  "id": 1,
  "gateway": "stripe",
  "gateway_subscription_id": "sub_1234567890",
  "gateway_customer_id": "cus_abcdefg",
  "resource_type": "entry",
  "resource_id": 789,
  "plan_name": "Annual Pro Membership",
  "plan_id": "pro_annual",
  "amount": 24900,
  "currency": "USD",
  "billing_period": "year",
  "billing_interval": 1,
  "status": "active",
  "current_period_start": "2025-01-24 00:00:00",
  "current_period_end": "2026-01-24 00:00:00"
}
```

---

#### UC2.2: Customer Cancels Subscription
**Scenario:** Customer wants to cancel their membership

**Frontend Flow (Customer Portal):**
```
1. User logs into → courses.com/account
2. User clicks "Manage Subscription"
3. Subscription Details:
   ┌─────────────────────────────────────────────┐
   │ Pro Membership - Annual                     │
   │ Status: Active                              │
   │ Next billing: Jan 24, 2026                  │
   │ Amount: $249.00/year                        │
   │                                             │
   │ [Change Plan]  [Cancel Subscription]        │
   └─────────────────────────────────────────────┘
4. User clicks "Cancel Subscription"
5. Confirmation modal:
   ┌─────────────────────────────────────────────┐
   │ Cancel Subscription?                        │
   │                                             │
   │ You'll continue to have access until        │
   │ Jan 24, 2026.                               │
   │                                             │
   │ Reason for canceling:                       │
   │ [Too expensive              ▼]              │
   │                                             │
   │ [Keep Subscription]  [Cancel at Period End] │
   └─────────────────────────────────────────────┘
6. User confirms cancellation
7. [Success] "Your subscription will end on Jan 24, 2026"
```

**Subscription Update:**
```json
{
  "status": "active",
  "cancel_at_period_end": true,
  "canceled_at": "2025-11-24 14:30:00",
  "cancellation_reason": "too_expensive"
}
```

**Subscription Event:**
```json
{
  "subscription_id": 1,
  "event_type": "canceled",
  "event_source": "customer",
  "event_data": {
    "reason": "too_expensive",
    "cancel_at_period_end": true
  },
  "actor_id": 456,
  "actor_type": "user"
}
```

---

#### UC2.3: Admin Manages Subscription
**Scenario:** Admin needs to pause a subscription due to customer request

**Admin Flow:**
```
1. Admin navigates → Super Forms > Subscriptions
2. Admin searches for "emily@startup.com"
3. Admin clicks subscription row
4. Subscription Detail View:
   ┌─────────────────────────────────────────────────────────────────┐
   │ Subscription #1                                    [← Back]     │
   ├─────────────────────────────────────────────────────────────────┤
   │ Customer: Emily Chen (emily@startup.com)                        │
   │ Plan: Pro Membership - Annual ($249/year)                       │
   │ Status: ● Active                                                │
   │ Gateway: Stripe (sub_1234567890)                                │
   │                                                                 │
   │ Billing:                                                        │
   │ - Current period: Jan 24, 2025 → Jan 24, 2026                  │
   │ - Next payment: $249.00 on Jan 24, 2026                        │
   │ - Total payments: 1 ($249.00)                                  │
   │                                                                 │
   │ Actions:                                                        │
   │ [Pause Subscription] [Cancel] [View in Stripe]                 │
   ├─────────────────────────────────────────────────────────────────┤
   │ Timeline                                                        │
   │ ──────────────────────────────────────────────────────────────  │
   │ ● Jan 24, 2025 - Payment received ($249.00)                    │
   │ ● Jan 24, 2025 - Subscription activated                        │
   │ ● Jan 24, 2025 - Subscription created                          │
   └─────────────────────────────────────────────────────────────────┘
5. Admin clicks "Pause Subscription"
6. Modal: "Pause reason: [Customer requested ▼]"
7. Subscription paused in Stripe
8. Status updated: "paused"
9. Event logged: "paused" by admin
```

---

#### UC2.4: Failed Payment Recovery
**Scenario:** Renewal payment fails, system attempts recovery

**Automated Flow:**
```
Timeline:
─────────────────────────────────────────────────────────────────────

Day 0 (Renewal Date):
├─ Stripe attempts charge
├─ Card declined (insufficient funds)
├─ Webhook: invoice.payment_failed
├─ Subscription status → past_due
├─ Event logged: payment_failed
├─ Trigger: Send "Payment Failed" email
│   "Hi Emily, your payment of $249 failed.
│    Please update your card: [Update Payment Method]"

Day 3 (Auto-retry #1):
├─ Stripe retries charge
├─ Still fails
├─ Trigger: Send reminder email

Day 5 (Auto-retry #2):
├─ Stripe retries charge
├─ Still fails
├─ Trigger: Send urgent reminder
│   "Your membership will be suspended in 2 days..."

Day 7 (Final retry):
├─ Stripe retries charge
├─ SUCCESS! Card updated by customer
├─ Webhook: invoice.payment_succeeded
├─ Subscription status → active
├─ Event logged: renewed
├─ Payment record created
├─ Trigger: Send "Payment Successful" email

─────────────────────────────────────────────────────────────────────
```

---

## Epic 3: Refund Processing

### User Story
> As an **admin**, I want to process refunds directly from WordPress without logging into payment gateways, with full audit trail.

### Use Cases

#### UC3.1: Full Refund
**Scenario:** Customer requests refund within 30-day guarantee

**Admin Flow:**
```
1. Admin receives support ticket: "Please refund my purchase"
2. Admin navigates → Super Forms > Payments
3. Admin finds payment #001 ($49.00)
4. Admin clicks payment row
5. Payment Detail View:
   ┌─────────────────────────────────────────────────────────────────┐
   │ Payment #001                                       [← Back]     │
   ├─────────────────────────────────────────────────────────────────┤
   │ Amount: $49.00 USD                                              │
   │ Status: ● Completed                                             │
   │ Customer: John Doe (john@example.com)                           │
   │ Form: Product Sale Form                                         │
   │ Gateway: Stripe (pi_1234567890)                                 │
   │ Date: Nov 24, 2025 at 2:30 PM                                   │
   │                                                                 │
   │ [Process Refund] [View Receipt] [View in Stripe]               │
   └─────────────────────────────────────────────────────────────────┘
6. Admin clicks "Process Refund"
7. Refund Modal:
   ┌─────────────────────────────────────────────────────────────────┐
   │ Process Refund                                                  │
   │                                                                 │
   │ Original Amount: $49.00                                         │
   │ Already Refunded: $0.00                                         │
   │ Available to Refund: $49.00                                     │
   │                                                                 │
   │ Refund Amount: [$49.00        ] [Full Refund]                  │
   │                                                                 │
   │ Reason:                                                         │
   │ [Customer requested - within guarantee period    ▼]            │
   │                                                                 │
   │ Internal Note:                                                  │
   │ [Support ticket #1234                           ]              │
   │                                                                 │
   │ ⚠️ This will refund $49.00 to the customer's original          │
   │    payment method. This action cannot be undone.                │
   │                                                                 │
   │ [Cancel]                              [Process Refund]          │
   └─────────────────────────────────────────────────────────────────┘
8. Admin clicks "Process Refund"
9. [Processing...] Contacting Stripe...
10. [Success] "Refund processed. $49.00 returned to customer."
```

**Updated Payment Record:**
```json
{
  "id": 1,
  "status": "refunded",
  "amount": 4900,
  "amount_refunded": 4900,
  "refunded_at": "2025-11-24 15:45:00",
  "meta": {
    "_refund_id": "re_1234567890",
    "_refund_reason": "requested_by_customer",
    "_refund_note": "Support ticket #1234"
  }
}
```

---

#### UC3.2: Partial Refund
**Scenario:** Customer attended one of two workshops, refund for missed one

**Admin Flow:**
```
Refund Modal for $497.00 order:
┌─────────────────────────────────────────────────────────────────┐
│ Process Refund                                                  │
│                                                                 │
│ Original Amount: $397.60 (after discount)                       │
│ Already Refunded: $0.00                                         │
│ Available to Refund: $397.60                                    │
│                                                                 │
│ Refund Amount: [$99.00         ] [Partial]                     │
│                                                                 │
│ Items to refund:                                                │
│ ☐ Early Bird Ticket ($299.00)                                  │
│ ☐ React Workshop ($99.00)                                      │
│ ☑ AI Workshop ($99.00) ← refunding this                        │
│                                                                 │
│ Reason: [Customer did not attend workshop          ▼]          │
└─────────────────────────────────────────────────────────────────┘
```

**Updated Payment Record:**
```json
{
  "status": "partially_refunded",
  "amount": 39760,
  "amount_refunded": 9900
}
```

---

## Epic 4: License/SaaS API

### User Story
> As a **WordPress plugin developer**, I want to sell licenses for my plugin using Super Forms and have a simple API my plugin can call to validate licenses.

### Use Cases

#### UC4.1: Plugin Sells License via Super Forms
**Scenario:** Developer sells "SEO Pro" plugin licenses through their website

**Setup Flow:**
```
1. Developer creates form in Super Forms:
   - Product: SEO Pro Plugin
   - License Tiers:
     ○ Personal ($49) - 1 site
     ○ Business ($99) - 5 sites
     ○ Agency ($199) - unlimited sites
   - Payment: Stripe

2. Developer configures trigger:
   Event: payment.stripe.payment_succeeded
   Action: Create License
   - Product ID: seo-pro
   - License Type: {field:license_tier}
   - Max Activations: {conditional: personal=1, business=5, agency=999}
   - Valid Until: {conditional: subscription ? null : +1 year}
```

**Customer Purchase Flow:**
```
1. Customer visits → seopro.dev/buy
2. Customer selects "Business" license
3. Customer completes payment
4. [Success]
   - "Thank you for purchasing SEO Pro!"
   - "Your license key: SF-SEOP-A1B2-C3D4-E5F6"
   - "Activations: 0 / 5 sites"
   - "Valid until: Nov 24, 2026"
   - [Download Plugin] [View Documentation]
5. Customer receives email with license key
```

**License Record Created:**
```json
{
  "id": 1,
  "license_key": "SF-SEOP-A1B2-C3D4-E5F6",
  "product_id": "seo-pro",
  "product_name": "SEO Pro Plugin",
  "product_variant": "business",
  "customer_email": "buyer@company.com",
  "payment_id": 123,
  "license_type": "perpetual",
  "status": "active",
  "valid_from": "2025-11-24",
  "valid_until": "2026-11-24",
  "max_activations": 5,
  "current_activations": 0,
  "features": {
    "priority_support": true,
    "updates": "1_year"
  }
}
```

---

#### UC4.2: Plugin Validates License (API Call)
**Scenario:** SEO Pro plugin validates license on activation

**Plugin Code (in SEO Pro):**
```php
// When user activates plugin and enters license key
function seopro_activate_license( $license_key ) {
    $response = wp_remote_post( 'https://seopro.dev/wp-json/super-forms/v1/licenses/activate', [
        'body' => [
            'license_key' => $license_key,
            'site_url'    => home_url(),
            'site_name'   => get_bloginfo('name'),
            'product_version' => SEOPRO_VERSION,
        ]
    ]);

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $data['success'] ) {
        // Store activation token locally
        update_option( 'seopro_license_key', $license_key );
        update_option( 'seopro_activation_token', $data['activation_token'] );
        update_option( 'seopro_license_valid_until', $data['valid_until'] );
        return true;
    }

    return new WP_Error( $data['error_code'], $data['error_message'] );
}
```

**API Request:**
```http
POST /wp-json/super-forms/v1/licenses/activate
Content-Type: application/json

{
  "license_key": "SF-SEOP-A1B2-C3D4-E5F6",
  "site_url": "https://clientsite.com",
  "site_name": "Client's Blog",
  "product_version": "2.5.0"
}
```

**API Response (Success):**
```json
{
  "success": true,
  "activation_token": "act_xyz123abc456",
  "license": {
    "license_key": "SF-SEOP-A1B2-C3D4-E5F6",
    "product_id": "seo-pro",
    "product_variant": "business",
    "status": "active",
    "valid_until": "2026-11-24",
    "activations_used": 1,
    "activations_limit": 5,
    "features": {
      "priority_support": true,
      "updates": "1_year"
    }
  }
}
```

**API Response (Error - Limit Reached):**
```json
{
  "success": false,
  "error_code": "activation_limit_reached",
  "error_message": "This license has reached its activation limit (5/5). Please deactivate another site or upgrade your license.",
  "activations": [
    {"site_url": "https://site1.com", "activated_at": "2025-06-15"},
    {"site_url": "https://site2.com", "activated_at": "2025-07-20"},
    {"site_url": "https://site3.com", "activated_at": "2025-08-01"},
    {"site_url": "https://site4.com", "activated_at": "2025-09-10"},
    {"site_url": "https://site5.com", "activated_at": "2025-10-05"}
  ]
}
```

---

#### UC4.3: Subscription License (SaaS Model)
**Scenario:** Plugin uses monthly subscription licensing

**Customer Flow:**
```
1. Customer subscribes to SEO Pro Monthly ($9.99/month)
2. License created with:
   - license_type: subscription
   - subscription_id: 123 (linked subscription)
   - valid_until: NULL (managed by subscription)
3. When subscription renews → license stays active
4. When subscription canceled → license status = expired
5. Plugin checks license → sees expired → prompts renewal
```

**License Lifecycle with Subscription:**
```
Subscription Active:
┌─────────────────────────────────────────────────────┐
│ License: SF-SEOP-A1B2-C3D4-E5F6                     │
│ Status: ● Active (subscription)                     │
│ Renews: Dec 24, 2025                                │
│ Features: All Pro features                          │
└─────────────────────────────────────────────────────┘

Subscription Canceled:
┌─────────────────────────────────────────────────────┐
│ License: SF-SEOP-A1B2-C3D4-E5F6                     │
│ Status: ○ Expired                                   │
│ Expired: Nov 24, 2025                               │
│ Features: Read-only (no updates/support)            │
│                                                     │
│ [Reactivate Subscription]                           │
└─────────────────────────────────────────────────────┘
```

---

#### UC4.4: License Deactivation
**Scenario:** User moves site to new domain

**Plugin UI (in SEO Pro settings):**
```
┌─────────────────────────────────────────────────────┐
│ SEO Pro License                                     │
│                                                     │
│ License Key: SF-SEOP-A1B2-C3D4-E5F6                │
│ Status: ● Active                                    │
│ This Site: https://oldsite.com                      │
│ Activations: 3 / 5                                  │
│                                                     │
│ [Deactivate from This Site]                         │
└─────────────────────────────────────────────────────┘
```

**Deactivation API Call:**
```http
POST /wp-json/super-forms/v1/licenses/deactivate
Content-Type: application/json

{
  "license_key": "SF-SEOP-A1B2-C3D4-E5F6",
  "activation_token": "act_xyz123abc456",
  "site_url": "https://oldsite.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "License deactivated from https://oldsite.com",
  "activations_remaining": 4
}
```

---

## Epic 5: Invoice & Receipt Management

### User Story
> As a **customer**, I want to download invoices for my purchases for accounting/reimbursement purposes.

### Use Cases

#### UC5.1: Download Invoice from Email
**Scenario:** Customer needs invoice for expense report

**Email Content:**
```
Subject: Your receipt from Acme Corp (#SF-INV-00001)

Hi John,

Thank you for your purchase!

Order Details:
- Product: Premium Template Pack
- Amount: $49.00 USD
- Date: November 24, 2025
- Transaction ID: pi_1234567890

[Download Invoice (PDF)]
[View Receipt Online]

Questions? Reply to this email.

Thanks,
Acme Corp
```

---

#### UC5.2: Customer Portal Invoice History
**Scenario:** Customer accesses all past invoices

**Frontend (Customer Account Page):**
```
My Purchases
┌─────────────────────────────────────────────────────────────────┐
│ Invoice    Date           Amount    Status      Actions         │
├─────────────────────────────────────────────────────────────────┤
│ #00003     Nov 24, 2025   $99.00    ● Paid     [PDF] [Receipt] │
│ #00002     Oct 15, 2025   $49.00    ● Paid     [PDF] [Receipt] │
│ #00001     Sep 01, 2025   $249.00   ◐ Partial  [PDF] [Receipt] │
│                                       Refund                    │
└─────────────────────────────────────────────────────────────────┘
```

---

#### UC5.3: Subscription Invoice Auto-Generation
**Scenario:** Monthly subscription generates invoice each billing cycle

**Automatic Flow:**
```
1. Subscription renewal triggers
2. Payment processed successfully
3. Invoice generated:
   - Invoice #: SF-INV-00050
   - Period: Nov 24 - Dec 24, 2025
   - Plan: Pro Membership
   - Amount: $29.00
4. Invoice PDF stored
5. Email sent with invoice attached
6. Invoice linked to payment record
```

---

## Epic 6: Admin Analytics Dashboard

### User Story
> As a **business owner**, I want to see payment analytics and trends without exporting to spreadsheets.

### Use Cases

#### UC6.1: Revenue Overview
**Admin Dashboard:**
```
Super Forms > Payments > Overview
┌─────────────────────────────────────────────────────────────────────┐
│ Payment Analytics                                [Last 30 days ▼]  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────┐ │
│  │ Total Revenue│  │  Payments    │  │   Refunds    │  │   MRR   │ │
│  │   $12,450    │  │     156      │  │    $450      │  │ $2,100  │ │
│  │   ↑ 12%      │  │   ↑ 8%       │  │    ↓ 5%      │  │  ↑ 3%   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └─────────┘ │
│                                                                     │
│  Revenue Over Time                                                  │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │     $                                                       │   │
│  │  600│          ╭─╮                                          │   │
│  │     │    ╭─╮  │   │  ╭─╮                      ╭─╮          │   │
│  │  400│   │   │╭╯   ╰─╮│  ╰╮  ╭─╮  ╭─╮       ╭╯   ╰╮        │   │
│  │     │ ╭─╯   ╰╯       ╰╯   ╰─╯  ╰─╯  ╰─────╯      ╰────    │   │
│  │  200│╯                                                      │   │
│  │     └───────────────────────────────────────────────────────│   │
│  │       Oct 25   Nov 1    Nov 8   Nov 15   Nov 22             │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  Top Forms by Revenue          Payment Methods                      │
│  ┌────────────────────────┐   ┌─────────────────────────────────┐  │
│  │ 1. Product Sales $5,200│   │ ████████████████ Stripe 78%     │  │
│  │ 2. Membership   $4,100 │   │ ██████           PayPal 22%     │  │
│  │ 3. Donations    $2,050 │   └─────────────────────────────────┘  │
│  │ 4. Event Tix    $1,100 │                                        │
│  └────────────────────────┘                                        │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Epic 7: Future Resource Types (Bookings, Tickets)

### Use Cases

#### UC7.1: Booking Payment (Future)
```json
{
  "resource_type": "booking",
  "resource_id": 456,
  "meta": {
    "_booking_date": "2025-12-15",
    "_booking_time": "14:00",
    "_service": "1-Hour Consultation",
    "_provider": "Dr. Smith"
  }
}
```

#### UC7.2: Event Ticket Payment (Future)
```json
{
  "resource_type": "ticket",
  "resource_id": 789,
  "meta": {
    "_event_id": 123,
    "_event_name": "WordCamp 2025",
    "_ticket_type": "VIP",
    "_ticket_code": "WC25-VIP-00123",
    "_attendee_name": "John Doe"
  }
}
```

---

## Error Handling Flows

### Payment Declined
```
Frontend:
┌─────────────────────────────────────────────┐
│ ⚠️ Payment Failed                           │
│                                             │
│ Your card was declined.                     │
│                                             │
│ Please check your card details and try      │
│ again, or use a different payment method.   │
│                                             │
│ [Try Again] [Use Different Card]            │
└─────────────────────────────────────────────┘
```

### Subscription Payment Failed
```
Email:
Subject: Action Required: Payment failed for your subscription

Hi Emily,

We were unable to process your payment of $29.00 for your
Pro Membership subscription.

Reason: Card expired

Please update your payment method within 7 days to avoid
service interruption.

[Update Payment Method]

Current status: Past Due
Next retry: November 27, 2025
```

### License Validation Failed
```
Plugin Admin Notice:
┌─────────────────────────────────────────────────────────────────┐
│ ⚠️ SEO Pro License Invalid                                      │
│                                                                 │
│ Your license key is invalid or has expired. Please renew your  │
│ license to continue receiving updates and support.              │
│                                                                 │
│ [Enter New License Key] [Renew License]                        │
└─────────────────────────────────────────────────────────────────┘
```

---

## Security Considerations

### Webhook Verification
- All payment webhooks verified via signature (Stripe: HMAC-SHA256, PayPal: API verification)
- Webhook events logged for audit trail
- Duplicate webhook prevention via gateway_payment_id unique constraint

### License API Security
- Rate limiting: 60 requests/minute per IP
- License secret for sensitive operations (optional)
- Activation tokens are single-use per site
- Deactivation requires matching activation_token

### PCI Compliance
- No card data touches Super Forms servers
- Stripe Elements/PayPal SDK handle sensitive data
- Only store tokenized references (customer_id, payment_id)
