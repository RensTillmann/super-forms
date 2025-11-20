---
name: h-implement-triggers-actions-extensibility
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
---

# Implement Extensible Triggers/Actions System

## Problem/Goal

The current triggers/actions system is functionally solid but architecturally closed - it works well for the 5 built-in actions but provides zero extensibility for add-ons. Events and actions are hardcoded arrays with no registration API, making it impossible for add-ons to integrate properly.

**Core Issues:**
- Add-ons cannot register new events or actions
- No hooks/filters for extending the triggers UI
- Custom WP-Cron scheduling instead of Action Scheduler (unreliable)
- No action result tracking or error handling
- Limited conditional logic (single condition only)
- No support for complex integrations (API requests, response parsing, field mapping)

**Important Note:** The triggers/actions system has NOT been released yet - no customers are using it. This means we can refactor completely without migration scripts or backward compatibility concerns.

**Vision:**
Build a modular add-on ecosystem where external plugins can easily extend Super Forms with:
- CRM integrations (Salesforce, HubSpot, Pipedrive)
- AI services (OpenAI, Claude, Gemini, Groq)
- Google services (Sheets, Docs, Drive, Calendar)
- Email marketing (MailChimp, SendGrid, Mailster)
- Custom HTTP requests (Postman-like functionality)
- Real-time field interactions (on keypress, button click)

## Subtasks

This task is divided into 7 implementation phases, each documented as a separate subtask:

1. **[Foundation and Registry System](01-implement-foundation-registry.md)** - Database schema, DAL, base classes, registry pattern
2. **[Action Scheduler Integration](02-implement-action-scheduler.md)** - Replace WP-Cron with Action Scheduler
3. **[Execution and Logging](03-implement-execution-logging.md)** - Execution engine, logging, debugging
4. **[API and Security](04-implement-api-security.md)** - REST API, OAuth, credentials, security
5. **[HTTP Request Action](05-implement-http-request.md)** - Postman-like HTTP request builder
6. **[Payment and Subscription Events](06-implement-payment-subscription.md)** - Payment gateway integrations
7. **[Example Add-ons](07-implement-example-addons.md)** - Three complete example add-ons

## Success Criteria

### Phase 1: Foundation and Registry System
- [ ] Database tables created automatically on plugin update/install
- [ ] Data Access Layer (DAL) implemented
- [ ] Base action class with common functionality
- [ ] Registry system for events and actions
- [ ] Backward compatibility maintained
- [ ] See [Phase 1 subtask](01-implement-foundation-registry.md) for details

### Phase 2: Action Scheduler Integration
- [ ] All scheduled actions use Action Scheduler
- [ ] Existing scheduled actions migrated
- [ ] Queue management UI integrated
- [ ] Failed action retry mechanism
- [ ] See [Phase 2 subtask](02-implement-action-scheduler.md) for details

### Phase 3: Execution and Logging
- [ ] Robust execution engine with error handling
- [ ] Comprehensive logging system
- [ ] Debug mode for development
- [ ] Performance metrics tracking
- [ ] See [Phase 3 subtask](03-implement-execution-logging.md) for details

### Phase 4: API and Security
- [ ] Secure API credentials storage
- [ ] REST API endpoints
- [ ] OAuth 2.0 support
- [ ] Rate limiting implemented
- [ ] See [Phase 4 subtask](04-implement-api-security.md) for details

### Phase 5: HTTP Request Action
- [ ] Support all HTTP methods
- [ ] Multiple authentication methods
- [ ] Flexible body formats
- [ ] Response parsing and mapping
- [ ] See [Phase 5 subtask](05-implement-http-request.md) for details

### Phase 6: Payment Events
- [ ] Payment gateway events integrated
- [ ] Subscription lifecycle tracked
- [ ] Refund handling implemented
- [ ] See [Phase 6 subtask](06-implement-payment-subscription.md) for details

### Phase 7: Example Add-ons
- [ ] Slack integration example
- [ ] Google Sheets example
- [ ] CRM Connector example
- [ ] Developer documentation
- [ ] See [Phase 7 subtask](07-implement-example-addons.md) for details

## Implementation Order

The phases should be implemented sequentially:
1. Start with Phase 1 (Foundation) - this is required for everything else
2. Phase 2 (Action Scheduler) - needed for reliable execution
3. Phase 3 (Execution/Logging) - needed for debugging and monitoring
4. Phase 4 (API/Security) - needed for external integrations
5. Phase 5 (HTTP Request) - the most versatile action
6. Phase 6 (Payment Events) - integration with existing add-ons
7. Phase 7 (Examples) - demonstrate the system to developers

## Context Manifest
<!-- Added by context-gathering agent -->

## User Notes

### Architecture Considerations
- **No Migration Path Needed**: System hasn't been released, so we can rebuild from scratch
- **Action Scheduler Already Bundled**: v3.9.3 at `/src/includes/lib/action-scheduler/`
- **Existing Payment Add-ons**: PayPal, Stripe, WooCommerce integrations already exist
- **Form Builder Integration**: Triggers UI must integrate with existing form builder
- **Data Layer Architecture**: Use `SUPER_Data_Access` layer for all entry data operations, NOT direct `update_post_meta`
- **Background Processing**: Use Action Scheduler for ALL background tasks (no WP-Cron)

### Development Guidelines
- Follow WordPress coding standards
- Use WordPress APIs where possible (wpdb, options, etc.)
- **Entry Data Storage**: Always use `SUPER_Data_Access::update_entry_data()` for storing contact entry data
- **Scheduled Tasks**: All background/scheduled tasks must use Action Scheduler, not WP-Cron
- Ensure PHP 7.4+ compatibility
- Write unit tests for all new functionality
- Document all hooks and filters for developers

### Impact on Existing Features

The new triggers/actions system will affect several existing features:

1. **Form Submissions**
   - Current: Direct email sending and basic actions
   - Enhanced: Event-driven architecture with retry capability
   - Migration: Existing forms continue working, new features opt-in

2. **Email Notifications**
   - Current: Synchronous sending during form submission
   - Enhanced: Queued via Action Scheduler for reliability
   - Benefit: Better performance, automatic retries, failure tracking

3. **Payment Processing**
   - Current: Individual add-on handling
   - Enhanced: Unified event system for all payment gateways
   - Integration: Existing payment add-ons emit standardized events

4. **Data Storage**
   - Current: EAV tables via Data Access Layer
   - Enhanced: Trigger execution logs in separate tables
   - Compatibility: Full compatibility with existing EAV migration system

5. **Background Jobs**
   - Current: Mix of WP-Cron and Action Scheduler
   - Enhanced: Consolidate all background jobs to Action Scheduler
   - Benefit: Consistent queue management and monitoring

### Epic Use Cases

#### Epic 1: Enterprise Integration Suite
**User Stories:**
- As an enterprise user, I need to sync form submissions to Salesforce CRM
- As a marketing manager, I need to segment leads into MailChimp lists based on form responses
- As a support team, I need tickets created in Zendesk from support forms

**Edge Cases:**
- API rate limiting and retry strategies
- Credential rotation and OAuth token refresh
- Bulk operations for historical data sync
- Field mapping with data type conversions
- Handling API downtime gracefully

#### Epic 2: Advanced Conditional Logic
**User Stories:**
- As a form admin, I need complex conditions (AND/OR/NOT logic)
- As a business owner, I need different actions based on calculated values
- As a developer, I need custom PHP conditions for complex rules

**Edge Cases:**
- Circular condition dependencies
- Performance with 50+ conditions per form
- Condition evaluation order and precedence
- Dynamic conditions based on user roles/capabilities
- Time-based conditions (business hours, dates)

#### Epic 3: Real-time Form Interactions
**User Stories:**
- As a user, I want instant field validation via external APIs
- As a form designer, I need to show/hide fields based on external data
- As an admin, I need real-time duplicate detection during typing

**Edge Cases:**
- Debouncing rapid keystrokes
- Network latency and timeout handling
- Caching API responses for performance
- Fallback behavior when services unavailable
- Security for client-side API calls

#### Epic 4: Payment Lifecycle Management
**User Stories:**
- As a business owner, I need actions triggered on successful payments
- As an accountant, I need invoice generation on subscription renewals
- As a customer service rep, I need alerts for failed payments

**Edge Cases:**
- Partial refunds and proration
- Currency conversion handling
- Payment method changes mid-subscription
- Dunning process for failed payments
- Regulatory compliance (PCI, GDPR)

#### Epic 5: Developer Ecosystem
**User Stories:**
- As a developer, I need to create custom actions for clients
- As an agency, I need to package reusable trigger/action combinations
- As a plugin author, I need to integrate my plugin with Super Forms

**Edge Cases:**
- Version compatibility between add-ons
- Namespace conflicts between custom actions
- Performance impact of multiple add-ons
- Action execution priority and ordering
- Debugging tools for custom actions

#### Epic 6: Audit and Compliance
**User Stories:**
- As a compliance officer, I need audit logs of all trigger executions
- As an admin, I need to track data flow through integrations
- As a security auditor, I need to verify API credential usage

**Edge Cases:**
- Log retention policies and rotation
- GDPR data deletion requests
- Encrypted storage of sensitive logs
- Performance with millions of log entries
- Log export for external analysis

### Testing Requirements
- Test with high-volume forms (1000+ submissions/day)
- Test with multiple active triggers
- Test Action Scheduler under load
- Test payment event reliability
- Test third-party add-on integration
- **Data Layer Testing**: Verify all entry operations use `SUPER_Data_Access` methods
- **Background Job Testing**: Confirm all scheduled tasks use Action Scheduler
- **Migration Testing**: Test with both serialized and EAV storage formats

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Task created and broken into 7 subtasks
- [2025-11-20] All subtask files created with detailed implementation plans