# Epic: Visual Workflow Examples & Use Cases

This document provides real-world examples demonstrating the power and flexibility of the visual workflow system for Super Forms triggers and actions.

## Table of Contents

1. [E-commerce Order Processing](#epic-1-e-commerce-order-processing)
2. [Lead Nurturing Campaign](#epic-2-lead-nurturing-campaign)
3. [Support Ticket Routing](#epic-3-support-ticket-routing)
4. [Event Registration with Payment](#epic-4-event-registration-with-payment)
5. [Progressive Form with Conditional Logic](#epic-5-progressive-form-with-conditional-logic)
6. [Abandoned Cart Recovery](#epic-6-abandoned-cart-recovery)
7. [Multi-Step Application Process](#epic-7-multi-step-application-process)
8. [Subscription Management](#epic-8-subscription-management)

---

## Epic 1: E-commerce Order Processing

**Business Scenario**: Customer places an order through a product form. Different actions are triggered based on order value and customer status.

### Visual Workflow Graph

```
[Form Submit Trigger]
         │
         ▼
  [If Condition: Order Value > $500]
         │
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[VIP Path] [Regular Path]
    │         │
    ├─ Send Email (VIP Template)
    │         │
    ├─ Update User Meta (VIP Status)
    │         │
    ├─ Webhook (Notify Sales Team)
    │         │
    ▼         ▼
[Create Post: Order] [Send Email: Confirmation]
    │         │
    ▼         ▼
[End]     [End]
```

### Node Configuration Details

**Trigger Node**: `form.submitted`
- Form ID: 38036 (Product Order Form)
- Context available: `{form_data}`, `{user}`, `{entry_id}`

**Condition Node**: If Condition
```json
{
  "field": "{total_amount}",
  "operator": "greater_than",
  "value": "500"
}
```

**Action Nodes (VIP Path)**:
1. **Send Email** (VIP Template)
   ```json
   {
     "to": "{email}",
     "from": "vip@store.com",
     "subject": "VIP Order Confirmation - #{order_number}",
     "body_type": "visual",
     "template": "vip-order-confirmation",
     "attachments": ["{invoice_pdf}"]
   }
   ```

2. **Update User Meta**
   ```json
   {
     "user_id": "{user_id}",
     "meta_key": "customer_tier",
     "meta_value": "vip"
   }
   ```

3. **Webhook** (Notify Sales Team)
   ```json
   {
     "url": "https://crm.store.com/api/vip-order",
     "method": "POST",
     "body": {
       "customer_id": "{user_id}",
       "order_value": "{total_amount}",
       "products": "{products_json}"
     }
   }
   ```

4. **Create Post** (Order Record)
   ```json
   {
     "post_type": "shop_order",
     "post_title": "Order #{order_number}",
     "post_status": "processing",
     "meta_data": {
       "customer_id": "{user_id}",
       "total": "{total_amount}",
       "items": "{products_json}"
     }
   }
   ```

**Action Nodes (Regular Path)**:
1. **Send Email** (Standard Template)
   ```json
   {
     "to": "{email}",
     "from": "orders@store.com",
     "subject": "Order Confirmation - #{order_number}",
     "body_type": "visual",
     "template": "order-confirmation"
   }
   ```

### Execution Timeline

**T=0s**: Form submitted
- Trigger fires: `form.submitted`
- Context: `{total_amount: 750, email: "customer@example.com", ...}`

**T=0.1s**: Condition evaluated
- `750 > 500` = **true**
- Execution follows "Yes" (VIP) path

**T=0.2s**: VIP Email sent
- Template rendered with customer data
- Email queued via WP Mail

**T=0.3s**: User meta updated
- `update_user_meta($user_id, 'customer_tier', 'vip')`

**T=0.4s**: CRM webhook fired
- HTTP POST to external CRM system

**T=0.5s**: Order post created
- CPT entry in WordPress database

**T=0.6s**: Execution complete

### Database Storage

```sql
-- wp_superforms_triggers table
INSERT INTO wp_superforms_triggers (
  name,
  event,
  workflow_type,
  workflow_graph,
  enabled,
  scope
) VALUES (
  'E-commerce Order Processing',
  'form.submitted',
  'visual',
  '{"nodes":[...29 nodes...],"connections":[...8 connections...],"viewport":{...}}',
  1,
  'form:38036'
);
```

---

## Epic 2: Lead Nurturing Campaign

**Business Scenario**: New lead signs up via contact form. Drip campaign sends emails over 7 days with intelligent engagement tracking.

### Visual Workflow Graph

```
[Form Submit Trigger]
         │
         ▼
  [Send Email: Welcome]
         │
         ▼
  [Delay: 2 days]
         │
         ▼
  [If Condition: Email Opened?]
         │
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[Send Email:  [Send Email:
 Case Study]   Re-engage]
    │         │
    ▼         ▼
[Delay: 3 days]
    │
    ▼
[Send Email: Product Demo Offer]
    │
    ▼
[Webhook: Notify Sales Team]
    │
    ▼
[End]
```

### Node Configuration Details

**Action Node 1**: Send Email (Welcome)
```json
{
  "to": "{email}",
  "subject": "Welcome to Our Platform!",
  "body_type": "visual",
  "schedule": null,
  "track_opens": true
}
```

**Action Node 2**: Delay (2 days)
```json
{
  "duration": 2,
  "unit": "days"
}
```

**Condition Node**: If Condition (Email Opened?)
```json
{
  "field": "{email_opened}",
  "operator": "equals",
  "value": "true"
}
```

**Action Node 3a**: Send Email (Case Study) - Engaged Path
```json
{
  "to": "{email}",
  "subject": "See How Companies Like Yours Succeed",
  "body_type": "visual",
  "template": "case-study-email"
}
```

**Action Node 3b**: Send Email (Re-engage) - Not Opened Path
```json
{
  "to": "{email}",
  "subject": "Don't Miss Out - Quick Question About Your Goals",
  "body_type": "visual",
  "template": "re-engagement-email"
}
```

**Action Node 4**: Delay (3 days)
```json
{
  "duration": 3,
  "unit": "days"
}
```

**Action Node 5**: Send Email (Product Demo Offer)
```json
{
  "to": "{email}",
  "subject": "Ready for a Personalized Demo?",
  "body_type": "visual",
  "template": "demo-offer-email",
  "cc": "sales@company.com"
}
```

**Action Node 6**: Webhook (Notify Sales Team)
```json
{
  "url": "https://crm.company.com/api/hot-lead",
  "method": "POST",
  "body": {
    "email": "{email}",
    "name": "{name}",
    "engagement_score": "{calculated_score}",
    "last_interaction": "{timestamp}"
  }
}
```

### Execution Timeline

**Day 0, T=0s**: Form submitted
- Trigger fires: `form.submitted`
- Context: `{email: "lead@company.com", name: "John Doe", ...}`

**Day 0, T=1s**: Welcome email sent
- Email tracked for opens

**Day 0, T=2s**: Delay node schedules continuation
- `as_schedule_single_action(time() + (2 * DAY_IN_SECONDS), 'super_continue_workflow', ['trigger_id' => 123, 'node_id' => 'node-delay-1'])`

**Day 2, T=0s**: Action Scheduler executes scheduled action
- Checks email open status: `{email_opened: true}`
- Condition evaluates to **true**
- Follows engaged path

**Day 2, T=1s**: Case study email sent

**Day 2, T=2s**: Second delay node schedules continuation
- `as_schedule_single_action(time() + (3 * DAY_IN_SECONDS), ...)`

**Day 5, T=0s**: Second delay completes
- Demo offer email sent
- CRM webhook fired

**Day 5, T=1s**: Execution complete

### Action Scheduler Integration

```php
// In SUPER_Visual_Workflow_Executor::execute_node_chain()
if ($node['type'] === 'delay_execution') {
    $duration = $node['config']['duration'];
    $unit = $node['config']['unit'];

    $seconds = $this->convert_to_seconds($duration, $unit);

    // Schedule continuation via Action Scheduler
    as_schedule_single_action(
        time() + $seconds,
        'super_continue_visual_workflow',
        [
            'trigger_id' => $this->trigger_id,
            'node_id' => $node_id,
            'context' => $context,
            'depth' => $depth
        ]
    );

    // Stop execution here; Action Scheduler will resume later
    return;
}
```

---

## Epic 3: Support Ticket Routing

**Business Scenario**: Customer submits support ticket. System routes to appropriate team based on issue type and urgency, with SLA tracking.

### Visual Workflow Graph

```
[Form Submit Trigger]
         │
         ▼
  [Create Post: Ticket]
         │
         ▼
  [Switch: Issue Type]
         │
    ┌────┼────┬────┐
    │    │    │    │
 Technical Bug Feature Billing
    │    │    │    │
    ▼    ▼    ▼    ▼
[Different email templates + webhooks per department]
    │
    ▼
  [If Condition: Priority = High]
         │
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[Webhook:   [Log Message]
 Urgent
 Alert]
    │         │
    ▼         ▼
[End]     [End]
```

### Node Configuration Details

**Action Node 1**: Create Post (Ticket)
```json
{
  "post_type": "support_ticket",
  "post_title": "Ticket #{ticket_id}: {subject}",
  "post_content": "{message}",
  "post_status": "open",
  "meta_data": {
    "customer_email": "{email}",
    "customer_name": "{name}",
    "issue_type": "{issue_type}",
    "priority": "{priority}",
    "submitted_at": "{timestamp}"
  }
}
```

**Condition Node**: Switch (Issue Type)
```json
{
  "field": "{issue_type}",
  "cases": [
    {"condition": "equals", "value": "technical", "output": "technical"},
    {"condition": "equals", "value": "bug", "output": "bug"},
    {"condition": "equals", "value": "feature", "output": "feature"},
    {"condition": "equals", "value": "billing", "output": "billing"}
  ]
}
```

**Action Nodes (Technical Path)**:
```json
{
  "send_email": {
    "to": "tech-support@company.com",
    "subject": "[TECH] New Ticket #{ticket_id}",
    "body_type": "visual",
    "template": "technical-ticket-notification"
  },
  "webhook": {
    "url": "https://jira.company.com/api/create-ticket",
    "method": "POST",
    "body": {
      "project": "TECH",
      "summary": "{subject}",
      "description": "{message}",
      "priority": "{priority}"
    }
  }
}
```

**Condition Node 2**: If Condition (High Priority)
```json
{
  "field": "{priority}",
  "operator": "equals",
  "value": "high"
}
```

**Action Node (Urgent Alert)**:
```json
{
  "webhook": {
    "url": "https://slack.com/api/chat.postMessage",
    "method": "POST",
    "headers": {
      "Authorization": "Bearer {slack_token}"
    },
    "body": {
      "channel": "#urgent-support",
      "text": "🚨 HIGH PRIORITY TICKET #{ticket_id}\nCustomer: {name}\nIssue: {subject}"
    }
  }
}
```

### Execution Flow

1. **Form submitted** → Create ticket post in WordPress
2. **Switch evaluates issue_type** → Routes to correct department
3. **Each department path**:
   - Sends department-specific email notification
   - Fires webhook to department's ticketing system (JIRA, Zendesk, etc.)
4. **Priority check** → If high, fires Slack webhook to urgent channel
5. **Complete**

---

## Epic 4: Event Registration with Payment

**Business Scenario**: User registers for paid event. Workflow handles payment processing, sends confirmation, creates calendar invite, and manages waitlist.

### Visual Workflow Graph

```
[Form Submit Trigger]
         │
         ▼
  [If Condition: Spots Available?]
         │
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[Payment    [Add to
 Process]    Waitlist]
    │         │
    ▼         │
[If: Payment  │
 Successful?] │
    │         │
  ┌─┴─┐       │
 Yes  No      │
  │   │       │
  ▼   ▼       ▼
[Confirmed] [Failed] [Waitlist Email]
  │
  ├─ Send Email (Ticket)
  ├─ Create Post (Registration)
  ├─ Webhook (Calendar Invite)
  └─ Update User Meta (Attendee)
```

### Node Configuration Details

**Condition Node 1**: If Condition (Spots Available)
```json
{
  "field": "{spots_remaining}",
  "operator": "greater_than",
  "value": "0"
}
```

**Action Node**: Payment Process (Stripe)
```json
{
  "provider": "stripe",
  "amount": "{ticket_price}",
  "currency": "USD",
  "description": "Event Registration - {event_name}",
  "customer_email": "{email}"
}
```

**Condition Node 2**: If Condition (Payment Successful)
```json
{
  "field": "{payment_status}",
  "operator": "equals",
  "value": "succeeded"
}
```

**Action Nodes (Confirmed Path)**:

1. **Send Email** (Ticket)
```json
{
  "to": "{email}",
  "subject": "Your Event Ticket - {event_name}",
  "body_type": "visual",
  "template": "event-ticket-email",
  "attachments": ["{ticket_pdf}"]
}
```

2. **Create Post** (Registration)
```json
{
  "post_type": "event_registration",
  "post_title": "Registration - {name}",
  "post_status": "confirmed",
  "meta_data": {
    "event_id": "{event_id}",
    "attendee_name": "{name}",
    "attendee_email": "{email}",
    "ticket_number": "{ticket_number}",
    "payment_id": "{payment_id}"
  }
}
```

3. **Webhook** (Calendar Invite)
```json
{
  "url": "https://calendar.company.com/api/create-invite",
  "method": "POST",
  "body": {
    "attendee_email": "{email}",
    "event_title": "{event_name}",
    "start_time": "{event_start}",
    "end_time": "{event_end}",
    "location": "{event_location}"
  }
}
```

4. **Update User Meta**
```json
{
  "user_id": "{user_id}",
  "meta_key": "registered_events",
  "meta_value": "{event_id}",
  "append": true
}
```

**Action Nodes (Waitlist Path)**:
```json
{
  "create_post": {
    "post_type": "event_waitlist",
    "post_title": "Waitlist - {name}",
    "meta_data": {
      "event_id": "{event_id}",
      "position": "{waitlist_position}"
    }
  },
  "send_email": {
    "to": "{email}",
    "subject": "You're on the Waitlist - {event_name}",
    "body_type": "visual",
    "template": "waitlist-confirmation"
  }
}
```

---

## Epic 5: Progressive Form with Conditional Logic

**Business Scenario**: Multi-step loan application where subsequent steps depend on answers in previous steps.

### Visual Workflow Graph

```
[Session Started]
         │
         ▼
  [Auto-save Session Data]
         │
         ▼
  [If: Step Completed?]
    ┌────┴────┐
    │         │
  Step 1    Step 2
    │         │
    ▼         ▼
[Validate] [Validate]
    │         │
    ▼         ▼
[Save Progress]
    │
    ▼
[Form Submitted]
    │
    ▼
  [If: Loan Amount > $50k]
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[Manual    [Auto
 Review]    Approve]
    │         │
    ▼         ▼
[Notify    [Send
 Team]      Approval]
```

### Session Management Integration

**Trigger Node 1**: `session.auto_saved`
```json
{
  "form_id": 38036,
  "auto_save_interval": 30
}
```

**Action Node**: Update Entry Field
```json
{
  "entry_id": "{entry_id}",
  "field_name": "application_progress",
  "field_value": "{current_step}"
}
```

**Trigger Node 2**: `session.completed`
- Fires when user submits final step
- Context includes all session data

**Condition Node**: If Condition (Loan Amount)
```json
{
  "field": "{loan_amount}",
  "operator": "greater_than",
  "value": "50000"
}
```

### Execution Flow

**During Session**:
1. Every 30 seconds: `session.auto_saved` fires
2. Saves current progress to `wp_superforms_sessions` table
3. Updates entry with completion percentage

**On Submission**:
1. `session.completed` fires
2. Evaluates loan amount
3. Routes to manual review or auto-approval
4. Sends appropriate notifications

---

## Epic 6: Abandoned Cart Recovery

**Business Scenario**: User adds items to cart but doesn't complete checkout. System sends reminder emails over time.

### Visual Workflow Graph

```
[Session Abandoned]
         │
         ▼
  [Delay: 1 hour]
         │
         ▼
  [If: Still Abandoned?]
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         └─[End]
[Send Email: Reminder 1]
    │
    ▼
  [Delay: 24 hours]
    │
    ▼
  [If: Still Abandoned?]
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         └─[End]
[Send Email: Reminder 2 + 10% Discount]
    │
    ▼
  [Delay: 48 hours]
    │
    ▼
  [If: Still Abandoned?]
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         └─[End]
[Send Email: Final Reminder + 20% Discount]
    │
    ▼
[Webhook: Notify Sales Team]
    │
    ▼
[End]
```

### Node Configuration Details

**Trigger Node**: `session.abandoned`
```json
{
  "form_id": 38036,
  "abandonment_threshold": 1800
}
```

**Action Node 1**: Delay (1 hour)
```json
{
  "duration": 1,
  "unit": "hours"
}
```

**Condition Node 1**: If Condition (Still Abandoned)
```json
{
  "field": "{session_status}",
  "operator": "equals",
  "value": "abandoned"
}
```

**Action Node 2**: Send Email (Reminder 1)
```json
{
  "to": "{email}",
  "subject": "Don't Forget Your Cart - {cart_items_count} Items Waiting",
  "body_type": "visual",
  "template": "abandoned-cart-reminder-1",
  "dynamic_content": {
    "cart_items": "{cart_items_html}",
    "cart_total": "{cart_total}"
  }
}
```

**Action Node 5**: Send Email (Reminder 2 with Discount)
```json
{
  "to": "{email}",
  "subject": "10% OFF Your Cart - Limited Time!",
  "body_type": "visual",
  "template": "abandoned-cart-reminder-2",
  "dynamic_content": {
    "discount_code": "SAVE10",
    "cart_items": "{cart_items_html}",
    "discounted_total": "{cart_total_with_discount}"
  }
}
```

**Action Node 8**: Send Email (Final Reminder with Bigger Discount)
```json
{
  "to": "{email}",
  "subject": "Last Chance - 20% OFF Your Cart!",
  "body_type": "visual",
  "template": "abandoned-cart-reminder-3",
  "dynamic_content": {
    "discount_code": "SAVE20",
    "cart_items": "{cart_items_html}",
    "final_total": "{cart_total_20_percent_off}"
  }
}
```

### Execution Timeline

**Day 0, 12:00 PM**: User abandons cart
- Trigger: `session.abandoned`
- Context: `{email, cart_items, cart_total, session_id}`

**Day 0, 1:00 PM**: First reminder sent
- Check if still abandoned → Yes
- Email sent with cart summary

**Day 1, 1:00 PM**: Second reminder sent
- Check if still abandoned → Yes
- Email sent with 10% discount code

**Day 3, 1:00 PM**: Final reminder sent
- Check if still abandoned → Yes
- Email sent with 20% discount code
- Sales team notified via webhook

---

## Epic 7: Multi-Step Application Process

**Business Scenario**: Job application with document upload, reference checks, and interview scheduling.

### Visual Workflow Graph

```
[Form Submit: Application]
         │
         ▼
  [Create Post: Application]
         │
         ▼
  [Send Email: Confirmation to Applicant]
         │
         ▼
  [If: Resume Uploaded?]
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[Continue] [Send Email: Request Resume]
    │         │
    └────┬────┘
         ▼
  [Delay: 3 days]
         │
         ▼
  [HTTP Request: Reference Check API]
         │
         ▼
  [If: References Valid?]
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[Webhook:  [Update
 Schedule   Status:
 Interview] Rejected]
    │         │
    ▼         ▼
[Send Email] [Send Email]
```

### Node Configuration Details

**Action Node**: Create Post (Application)
```json
{
  "post_type": "job_application",
  "post_title": "Application - {name} - {position}",
  "post_content": "{cover_letter}",
  "post_status": "pending_review",
  "meta_data": {
    "applicant_email": "{email}",
    "applicant_phone": "{phone}",
    "position": "{position}",
    "resume_url": "{resume_url}",
    "references": "{references_json}"
  }
}
```

**Condition Node**: If Condition (Resume Uploaded)
```json
{
  "field": "{resume_url}",
  "operator": "not_empty",
  "value": ""
}
```

**Action Node**: HTTP Request (Reference Check API)
```json
{
  "url": "https://api.checkr.com/v1/reference_checks",
  "method": "POST",
  "auth": {
    "type": "bearer",
    "token": "{checkr_api_key}"
  },
  "body": {
    "candidate_id": "{entry_id}",
    "references": "{references_json}"
  },
  "response_mapping": {
    "status": "reference_check_status",
    "result": "reference_check_result"
  }
}
```

**Action Node**: Webhook (Schedule Interview)
```json
{
  "url": "https://calendly.com/api/scheduling_links",
  "method": "POST",
  "body": {
    "event_type": "job-interview",
    "invitee_email": "{email}",
    "invitee_name": "{name}"
  }
}
```

---

## Epic 8: Subscription Management

**Business Scenario**: User subscribes to monthly service. System handles payment, sends welcome package, manages renewal reminders, and handles cancellations.

### Visual Workflow Graph

```
[Payment: Subscription Created]
         │
         ▼
  [Create Post: Subscription]
         │
         ▼
  [Send Email: Welcome Package]
         │
         ▼
  [Update User Meta: Active Subscriber]
         │
         ▼
  [Webhook: CRM Update]
         │
         ▼
[Delay: 25 days]
         │
         ▼
  [Send Email: Renewal Reminder]
         │
         ▼
[Delay: 5 days]
         │
         ▼
  [If: Payment Succeeded?]
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
[Send Thank] [Send Failed
 You Email]   Payment Email]
    │         │
    ▼         ▼
[Loop Back] [If: Retry 3x?]
               ┌─┴─┐
              Yes  No
               │   │
               ▼   ▼
           [Cancel] [Retry]
```

### Trigger Nodes

**Trigger 1**: `subscription.stripe.created`
```json
{
  "provider": "stripe",
  "event_type": "customer.subscription.created"
}
```

**Trigger 2**: `subscription.stripe.invoice_paid`
```json
{
  "provider": "stripe",
  "event_type": "invoice.payment_succeeded"
}
```

**Trigger 3**: `subscription.stripe.invoice_failed`
```json
{
  "provider": "stripe",
  "event_type": "invoice.payment_failed"
}
```

**Trigger 4**: `subscription.stripe.cancelled`
```json
{
  "provider": "stripe",
  "event_type": "customer.subscription.deleted"
}
```

### Action Nodes

**Welcome Email**:
```json
{
  "to": "{customer_email}",
  "subject": "Welcome to {plan_name}!",
  "body_type": "visual",
  "template": "subscription-welcome",
  "attachments": ["{onboarding_guide_pdf}"]
}
```

**Renewal Reminder**:
```json
{
  "to": "{customer_email}",
  "subject": "Your Subscription Renews in 5 Days",
  "body_type": "html",
  "body": "<p>Your {plan_name} subscription will renew on {renewal_date} for {amount}.</p>"
}
```

**Failed Payment Email**:
```json
{
  "to": "{customer_email}",
  "subject": "Payment Failed - Action Required",
  "body_type": "visual",
  "template": "payment-failed",
  "dynamic_content": {
    "retry_count": "{retry_count}",
    "next_retry": "{next_retry_date}",
    "update_payment_url": "{update_payment_url}"
  }
}
```

### Multi-Trigger Workflow

This workflow demonstrates how MULTIPLE triggers can feed into the SAME visual workflow:

1. **Creation trigger** → Starts welcome sequence
2. **Invoice paid trigger** → Sends thank you, loops back to renewal reminder
3. **Invoice failed trigger** → Initiates retry sequence
4. **Cancellation trigger** → Sends exit survey, offboarding email

All these triggers share the same node graph but enter at different entry points based on the event type.

---

## Common Patterns Across Epics

### 1. Conditional Branching
Every epic uses `if` or `switch` nodes to route execution based on data values.

### 2. Delay Nodes + Action Scheduler
Time-based workflows use delay nodes that integrate seamlessly with WordPress Action Scheduler.

### 3. Multi-Channel Communication
Email + webhooks + SMS + push notifications used together for comprehensive engagement.

### 4. External API Integration
HTTP request nodes connect to third-party services (CRMs, payment processors, calendar systems).

### 5. Data Persistence
Create/update post and user meta nodes store workflow state in WordPress.

### 6. Multi-Trigger Workflows
Single workflow can be activated by multiple different events (subscriptions example).

---

## Technical Implementation Notes

### Database Storage

Each epic is stored as a single trigger record:

```sql
SELECT
  id,
  name,
  event,
  workflow_type,
  JSON_LENGTH(workflow_graph, '$.nodes') as node_count,
  JSON_LENGTH(workflow_graph, '$.connections') as connection_count
FROM wp_superforms_triggers
WHERE workflow_type = 'visual';
```

Example results:
```
| id  | name                         | event              | node_count | connection_count |
|-----|------------------------------|--------------------|------------|------------------|
| 123 | E-commerce Order Processing  | form.submitted     | 12         | 8                |
| 124 | Lead Nurturing Campaign      | form.submitted     | 9          | 7                |
| 125 | Support Ticket Routing       | form.submitted     | 15         | 11               |
| 126 | Event Registration           | form.submitted     | 14         | 10               |
| 127 | Abandoned Cart Recovery      | session.abandoned  | 11         | 9                |
| 128 | Subscription Management      | subscription.*     | 18         | 14               |
```

### Performance Considerations

- **Node count**: 9-18 nodes per workflow (manageable)
- **Execution depth**: Maximum 5-7 levels (prevents stack overflow)
- **Action Scheduler**: Handles delays and async operations efficiently
- **Memory**: Each workflow execution uses <5MB RAM
- **Database**: Single JSON column stores entire graph (no joins needed)

### Reusability

Common sub-workflows can be extracted as **templates**:
- "Send order confirmation" template
- "Payment processing" template
- "CRM sync" template
- "Multi-step delay sequence" template

Users can drag these templates onto canvas and customize.

---

## Conclusion

These epics demonstrate that the visual workflow system can handle:

- **Complex conditional logic** (multiple if/switch nodes)
- **Time-based automation** (delays + Action Scheduler)
- **External integrations** (webhooks, HTTP requests, APIs)
- **Multi-step processes** (progressive forms, nurture campaigns)
- **Payment workflows** (Stripe subscriptions, event registration)
- **Data management** (posts, user meta, entry fields)

The node-based approach makes these complex workflows **understandable** and **maintainable** compared to traditional form-based trigger configuration.
