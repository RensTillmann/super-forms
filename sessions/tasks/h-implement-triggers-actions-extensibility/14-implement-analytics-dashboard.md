---
name: 14-implement-analytics-dashboard
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-23
parent: h-implement-triggers-actions-extensibility
---

# Implement Form Analytics Dashboard

## Problem/Goal

Form owners need visibility into how their forms perform. Currently, Super Forms has no built-in analytics - users must rely on Google Analytics or third-party services. This creates several issues:

1. **No native insights** - Users can't see submission trends, completion rates, or abandonment patterns
2. **No live session monitoring** - No way to see forms being filled in real-time
3. **No field-level analytics** - Can't identify problematic fields causing abandonment
4. **Privacy concerns** - External analytics services raise GDPR/privacy issues
5. **Complexity** - Setting up GA4 + GTM for form tracking requires technical knowledge

**Vision:** Build a privacy-focused, zero-configuration analytics dashboard that rivals [Gravity Forms' Form Analytics Pro](https://www.gravityforms.com/add-ons/form-analytics-pro/). All data stored locally in WordPress database, no external services required.

## Research: Competitor Analytics Features

### Gravity Forms - Form Analytics Pro
- Zero-configuration (works immediately upon activation)
- Field-level analytics with interaction tracking and abandonment detection
- Multi-page conversion funnels with drop-off visualization
- Real-time monitoring of active form sessions
- Complete privacy (all data stored locally)
- Lightweight tracking with minimal performance impact

### WPForms + MonsterInsights
- Form impressions (views)
- Conversions (completions)
- Conversion rates
- Form Abandonment addon (partial entries)
- User Journey addon (pages visited before submission)

### Industry Best Practices ([CXL](https://cxl.com/blog/form-analytics/), [Howuku](https://howuku.com/blog/form-abandonment-tracking))
- **Form starter rate** - % of visitors who start filling the form
- **Completion rate** - % who finish the form
- **Abandonment rate** - Average is 67-81%!
- **Time spent** - Average completion time
- **Field timings** - Time per field
- **Most corrected fields** - Fields where users edit answers
- **Ignored/skipped fields** - Optional fields not being used
- **Last field before abandonment** - Critical for optimization
- **Drop-off visualization** - Funnel showing where users quit

## Success Criteria

### Core Dashboard (MVP)
- [ ] Dedicated "Super Forms > Analytics" admin page
- [ ] Overview cards: Total submissions (today/week/month/all-time)
- [ ] Submissions chart (line graph, selectable date range)
- [ ] Top forms by submissions (table with sparklines)
- [ ] Recent activity feed (latest 20 submissions)
- [ ] Zero-configuration (tracks automatically on activation)

### Live Sessions Monitor
- [ ] "Active Sessions" section showing forms being filled NOW
- [ ] Real-time count badge in admin menu
- [ ] Session list with: Form name, User/IP, Started time, Progress %, Fields completed
- [ ] Click to view session details (current form data, field-by-field progress)
- [ ] Auto-refresh via AJAX (configurable interval: 5s/15s/30s/off)
- [ ] Filter by form

### Form-Level Analytics
- [ ] Per-form analytics page (click form name to drill down)
- [ ] Form impressions (views) vs Submissions (conversions)
- [ ] Conversion rate with trend indicator
- [ ] Average completion time
- [ ] Abandonment rate with breakdown
- [ ] Submission heatmap (by day/hour)
- [ ] Traffic sources (referrer tracking)
- [ ] Device breakdown (desktop/mobile/tablet)

### Field-Level Analytics
- [ ] Field interaction funnel visualization
- [ ] Per-field metrics: Views, Interactions, Completions, Drop-offs
- [ ] Average time spent on each field
- [ ] Most corrected fields (edited after initial input)
- [ ] Last field before abandonment (critical insight)
- [ ] Field error rates (validation failures)
- [ ] Skipped optional fields %

### Abandonment Tracking
- [ ] Partial entry capture (requires sessions from Phase 1a)
- [ ] Abandonment rate by form
- [ ] Abandonment funnel (which step/field)
- [ ] Time-to-abandon patterns
- [ ] Recovery email capability (future: trigger action)
- [ ] Integrates with `session.abandoned` event

### Custom Tracking Configuration
- [ ] Per-form tracking settings (enable/disable)
- [ ] Custom events to track (configurable)
- [ ] Field-specific tracking (mark fields as "high-value")
- [ ] Goal tracking (e.g., "contact form = lead", "order form = sale")
- [ ] Value assignment per form (for ROI calculation)
- [ ] Conversion attribution (first/last touch)

### Data Management & Privacy
- [ ] All data stored locally in WordPress database (GDPR-friendly)
- [ ] Configurable data retention (30/60/90/365 days or forever)
- [ ] Export analytics data (CSV/JSON)
- [ ] Clear analytics data (per form or all)
- [ ] IP anonymization option
- [ ] Exclude logged-in users option
- [ ] Exclude user roles option (e.g., exclude admins)

### Performance & Efficiency
- [ ] Lightweight tracking script (< 5KB minified)
- [ ] Async data collection (no form slowdown)
- [ ] Aggregate data storage (not per-event for old data)
- [ ] Background aggregation via Action Scheduler
- [ ] Efficient queries with proper indexes

## Database Schema

### Analytics Events Table (Raw Events)
```sql
CREATE TABLE {$wpdb->prefix}superforms_analytics_events (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  session_id BIGINT(20) UNSIGNED,           -- FK to sessions table
  entry_id BIGINT(20) UNSIGNED,             -- NULL until submission
  event_type VARCHAR(50) NOT NULL,          -- 'impression', 'start', 'field_focus', 'field_blur', 'field_change', 'submit', 'abandon'
  field_name VARCHAR(255),                  -- For field-level events
  event_data JSON,                          -- Additional event-specific data
  user_id BIGINT(20) UNSIGNED,
  user_ip VARCHAR(45),
  user_agent VARCHAR(500),
  device_type VARCHAR(20),                  -- 'desktop', 'mobile', 'tablet'
  referrer VARCHAR(500),
  page_url VARCHAR(500),
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY form_id (form_id),
  KEY session_id (session_id),
  KEY event_type (event_type),
  KEY created_at (created_at),
  KEY form_event_date (form_id, event_type, created_at)
) ENGINE=InnoDB;
```

### Analytics Aggregates Table (Daily Rollups)
```sql
CREATE TABLE {$wpdb->prefix}superforms_analytics_daily (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  date DATE NOT NULL,
  impressions INT UNSIGNED DEFAULT 0,
  starts INT UNSIGNED DEFAULT 0,            -- Form interactions started
  submissions INT UNSIGNED DEFAULT 0,
  abandonments INT UNSIGNED DEFAULT 0,
  total_time_seconds INT UNSIGNED DEFAULT 0,-- Sum of completion times
  desktop_count INT UNSIGNED DEFAULT 0,
  mobile_count INT UNSIGNED DEFAULT 0,
  tablet_count INT UNSIGNED DEFAULT 0,
  unique_visitors INT UNSIGNED DEFAULT 0,
  field_stats JSON,                         -- Aggregated field-level data
  PRIMARY KEY (id),
  UNIQUE KEY form_date (form_id, date),
  KEY date (date)
) ENGINE=InnoDB;
```

### Field Analytics Table (Per-Field Daily Stats)
```sql
CREATE TABLE {$wpdb->prefix}superforms_analytics_fields (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  field_name VARCHAR(255) NOT NULL,
  date DATE NOT NULL,
  views INT UNSIGNED DEFAULT 0,
  interactions INT UNSIGNED DEFAULT 0,
  completions INT UNSIGNED DEFAULT 0,
  abandonments INT UNSIGNED DEFAULT 0,      -- Left form at this field
  corrections INT UNSIGNED DEFAULT 0,       -- Edited after initial input
  errors INT UNSIGNED DEFAULT 0,            -- Validation failures
  total_time_ms INT UNSIGNED DEFAULT 0,     -- Time spent on field
  skipped INT UNSIGNED DEFAULT 0,           -- Skipped (optional fields)
  PRIMARY KEY (id),
  UNIQUE KEY form_field_date (form_id, field_name(100), date),
  KEY form_id (form_id),
  KEY date (date)
) ENGINE=InnoDB;
```

## Architecture

### File Structure
```
/src/includes/
  class-analytics-tracker.php      -- Client-side tracking endpoint
  class-analytics-aggregator.php   -- Background aggregation jobs
  class-analytics-query.php        -- Query builder for reports

/src/includes/admin/
  class-analytics-page.php         -- Main dashboard page
  class-analytics-form-page.php    -- Per-form detail page
  class-analytics-export.php       -- CSV/JSON export

/src/includes/admin/views/
  page-analytics.php               -- Dashboard template
  page-analytics-form.php          -- Form detail template
  partials/
    analytics-overview-cards.php
    analytics-chart.php
    analytics-live-sessions.php
    analytics-form-table.php
    analytics-field-funnel.php

/src/assets/js/
  frontend/
    super-analytics.js             -- Lightweight tracking script
  backend/
    analytics-dashboard.js         -- Dashboard interactivity
    analytics-charts.js            -- Chart.js visualizations

/src/assets/css/
  backend/
    analytics.css                  -- Dashboard styles
```

### Event Types

| Event | Description | Tracked Data |
|-------|-------------|--------------|
| `impression` | Form rendered on page | form_id, page_url, referrer, device |
| `start` | First field interaction | form_id, session_key, first_field |
| `field_focus` | Field received focus | field_name, timestamp |
| `field_blur` | Field lost focus | field_name, time_spent, has_value |
| `field_change` | Field value changed | field_name, is_correction |
| `field_error` | Validation failed | field_name, error_type |
| `step_change` | Multi-step navigation | from_step, to_step, direction |
| `submit` | Form submitted | entry_id, total_time |
| `abandon` | Session abandoned | last_field, time_spent, fields_completed |

### Tracking Script (Frontend)

```javascript
// super-analytics.js (< 5KB minified)
(function() {
    'use strict';

    var SuperAnalytics = {
        sessionKey: null,
        formId: null,
        startTime: null,
        fieldTimes: {},
        lastField: null,

        init: function($form) {
            this.formId = $form.data('id');
            this.startTime = Date.now();

            // Track impression
            this.track('impression');

            // Bind events
            this.bindEvents($form);
        },

        bindEvents: function($form) {
            var self = this;

            // First interaction = start
            $form.one('focus', 'input, textarea, select', function() {
                self.track('start', { first_field: this.name });
            });

            // Field focus
            $form.on('focus', 'input, textarea, select', function() {
                self.fieldTimes[this.name] = Date.now();
                self.track('field_focus', { field: this.name });
            });

            // Field blur
            $form.on('blur', 'input, textarea, select', function() {
                var timeSpent = Date.now() - (self.fieldTimes[this.name] || Date.now());
                self.lastField = this.name;
                self.track('field_blur', {
                    field: this.name,
                    time_ms: timeSpent,
                    has_value: !!this.value
                });
            });

            // Page unload = potential abandon
            window.addEventListener('beforeunload', function() {
                if (!self.submitted) {
                    self.track('abandon', {
                        last_field: self.lastField,
                        time_spent: Date.now() - self.startTime
                    });
                }
            });
        },

        track: function(event, data) {
            data = data || {};
            data.form_id = this.formId;
            data.session_key = this.sessionKey;
            data.event = event;
            data.timestamp = Date.now();
            data.page_url = window.location.href;

            // Use sendBeacon for reliability (especially on page unload)
            if (navigator.sendBeacon) {
                navigator.sendBeacon(
                    super_analytics.endpoint,
                    JSON.stringify(data)
                );
            } else {
                // Fallback to async XHR
                var xhr = new XMLHttpRequest();
                xhr.open('POST', super_analytics.endpoint, true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.send(JSON.stringify(data));
            }
        }
    };

    // Initialize on form load
    jQuery(document).on('super:form:loaded', function(e, data) {
        SuperAnalytics.init(data.$form);
    });
})();
```

### Live Sessions Component

```php
/**
 * AJAX endpoint for live sessions
 */
public static function get_live_sessions() {
    check_ajax_referer('super_analytics_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }

    global $wpdb;
    $sessions_table = $wpdb->prefix . 'superforms_sessions';
    $forms_table = $wpdb->prefix . 'posts';

    // Get active sessions (draft status, updated in last 30 minutes)
    $sessions = $wpdb->get_results($wpdb->prepare("
        SELECT
            s.id,
            s.session_key,
            s.form_id,
            p.post_title as form_name,
            s.user_id,
            s.user_ip,
            s.form_data,
            s.metadata,
            s.started_at,
            s.last_saved_at,
            TIMESTAMPDIFF(SECOND, s.started_at, NOW()) as duration_seconds
        FROM {$sessions_table} s
        LEFT JOIN {$forms_table} p ON s.form_id = p.ID
        WHERE s.status = 'draft'
        AND s.last_saved_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ORDER BY s.last_saved_at DESC
        LIMIT 50
    "));

    // Calculate progress for each session
    foreach ($sessions as &$session) {
        $form_data = json_decode($session->form_data, true) ?: [];
        $metadata = json_decode($session->metadata, true) ?: [];

        $session->fields_completed = count(array_filter($form_data, function($v) {
            return !empty($v);
        }));
        $session->total_fields = $metadata['total_fields'] ?? 0;
        $session->progress_percent = $session->total_fields > 0
            ? round(($session->fields_completed / $session->total_fields) * 100)
            : 0;
        $session->current_step = $metadata['current_step'] ?? 1;
        $session->total_steps = $metadata['total_steps'] ?? 1;

        // Clean up sensitive data
        unset($session->form_data);
    }

    wp_send_json_success([
        'sessions' => $sessions,
        'count' => count($sessions),
        'timestamp' => current_time('timestamp')
    ]);
}
```

## Implementation Phases

### Phase A: Foundation (MVP Dashboard)
1. Create database tables (events, daily aggregates, field stats)
2. Implement lightweight tracking script
3. Create tracking endpoint (REST API)
4. Build main Analytics dashboard page
5. Overview cards (submissions today/week/month)
6. Basic submissions chart (Chart.js)
7. Top forms table

### Phase B: Live Sessions
1. Integrate with sessions table from Phase 1a
2. Build live sessions component
3. AJAX polling with configurable interval
4. Session detail modal
5. Admin menu badge for active session count

### Phase C: Form-Level Analytics
1. Per-form analytics page
2. Conversion rate tracking
3. Abandonment funnel
4. Time-based heatmap
5. Device/referrer breakdown

### Phase D: Field-Level Analytics
1. Field interaction tracking
2. Field funnel visualization
3. "Last field before abandon" analysis
4. Most corrected fields report
5. Field timing analysis

### Phase E: Custom Configuration
1. Per-form tracking settings
2. Goal/value configuration
3. Data retention settings
4. Export functionality
5. Privacy controls

## Dependencies

- **Phase 1a (Sessions)** - Required for live session monitoring and abandonment tracking
- **Action Scheduler** - For background aggregation jobs
- **Chart.js** - For dashboard visualizations (can bundle or use CDN)

## UI Mockups

### Main Dashboard
```
+------------------------------------------------------------------+
|  Super Forms > Analytics                         [Date Range: ▼] |
+------------------------------------------------------------------+
|                                                                  |
|  +-------------+  +-------------+  +-------------+  +-----------+|
|  | 1,234       |  | 456         |  | 89          |  | 67%       ||
|  | Total       |  | This Week   |  | Today       |  | Conv Rate ||
|  | Submissions |  | +12% ↑      |  | +5% ↑       |  | +2% ↑     ||
|  +-------------+  +-------------+  +-------------+  +-----------+|
|                                                                  |
|  [Submissions Over Time]                    [Active Sessions: 3] |
|  ┌──────────────────────────────────────┐  +-------------------+ |
|  │     ╭─╮                              │  | Contact Form      | |
|  │    ╱   ╲    ╭───╮                    │  | 192.168.1.x | 45% | |
|  │   ╱     ╲  ╱     ╲                   │  | 2 min ago         | |
|  │  ╱       ╲╱       ╲                  │  +-------------------+ |
|  │ ╱                  ╲                 │  | Quote Request     | |
|  │╱                    ╲────            │  | john@... | 80%    | |
|  └──────────────────────────────────────┘  | 30 sec ago        | |
|  Nov 1        Nov 15        Nov 23         +-------------------+ |
|                                                                  |
|  [Top Forms by Submissions]                                      |
|  +--------------------------------------------------------------+|
|  | Form Name          | Views | Submissions | Rate | Trend      ||
|  +--------------------------------------------------------------+|
|  | Contact Form       | 5,432 | 1,234       | 23%  | ████▌ +5%  ||
|  | Quote Request      | 2,100 | 567         | 27%  | ███▌  +12% ||
|  | Newsletter Signup  | 8,900 | 890         | 10%  | ██    -2%  ||
|  +--------------------------------------------------------------+|
|                                                                  |
+------------------------------------------------------------------+
```

### Form Detail Page
```
+------------------------------------------------------------------+
|  ← Back to Analytics    Contact Form (#123)                      |
+------------------------------------------------------------------+
|                                                                  |
|  +-------------+  +-------------+  +-------------+  +-----------+|
|  | 5,432       |  | 1,234       |  | 23%         |  | 2:34      ||
|  | Impressions |  | Submissions |  | Conv Rate   |  | Avg Time  ||
|  +-------------+  +-------------+  +-------------+  +-----------+|
|                                                                  |
|  [Field Drop-off Funnel]                                         |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │ Name        ████████████████████████████████████████ 100%    │|
|  │ Email       ██████████████████████████████████████   95%     │|
|  │ Phone       █████████████████████████████           72%      │|
|  │ Message     ████████████████████████                 60%     │|
|  │ [Submit]    ██████████████                           45%     │|
|  └──────────────────────────────────────────────────────────────┘|
|  ⚠ High drop-off at "Phone" field (-23%)                        |
|                                                                  |
|  [Submission Heatmap]               [Device Breakdown]           |
|  ┌─────────────────────┐           ┌─────────────────┐          |
|  │ Mon ░░▒▒▓▓██░░░░░░░│           │ Desktop   65%   │          |
|  │ Tue ░░▒▒▓▓▓▓░░░░░░░│           │ Mobile    30%   │          |
|  │ Wed ░▒▒▒▓▓██░░░░░░░│           │ Tablet     5%   │          |
|  │ ... 6am    12pm  6pm│           └─────────────────┘          |
|  └─────────────────────┘                                         |
|                                                                  |
+------------------------------------------------------------------+
```

## Testing Requirements

### Unit Tests
- Analytics event recording
- Daily aggregation calculations
- Field-level stat calculations
- Query builder for date ranges
- Data export formatting

### Integration Tests
- Tracking script fires correct events
- Impression/start/submit flow
- Abandonment detection accuracy
- Live session updates
- Multi-step form tracking

### Performance Tests
- Tracking script < 5KB
- Event recording < 50ms
- Dashboard load < 2s with 100K events
- Aggregation handles 10K events/day

## Privacy & Compliance

### GDPR Compliance
- All data stored locally (no external services)
- IP anonymization option (last octet zeroed)
- Configurable data retention with auto-delete
- Data export for GDPR requests
- Clear all data option
- No cookies required for basic tracking

### Opt-Out Support
- Respect Do Not Track header (optional)
- JavaScript opt-out variable: `window.super_analytics_optout = true`
- Server-side opt-out via filter: `add_filter('super_analytics_enabled', '__return_false')`
- Exclude specific user roles

## Future Enhancements

- **A/B Testing Integration** - Compare form variants
- **Goal Funnels** - Track multi-form conversion paths
- **Revenue Attribution** - Connect to payment forms
- **Email Reports** - Weekly analytics summary
- **Benchmarking** - Compare to industry averages
- **AI Insights** - Automated optimization suggestions
- **Heatmaps** - Visual click/scroll tracking
- **Session Recordings** - Replay form interactions
- **Webhook Integration** - Send analytics events to external services

## References

### Research Sources
- [Gravity Forms - Form Analytics Pro](https://www.gravityforms.com/add-ons/form-analytics-pro/)
- [MonsterInsights - Forms Tracking](https://www.monsterinsights.com/feature/forms/)
- [Form Abandonment Tracking Guide (Howuku)](https://howuku.com/blog/form-abandonment-tracking)
- [Form Analytics: What You Can Track (CXL)](https://cxl.com/blog/form-analytics/)
- [Form Abandonment Statistics (Insiteful)](https://insiteful.co/blog/form-abandonment-statistics/)

### Key Statistics
- 67-81% of users abandon forms before completion
- Asking for phone number reduces conversions by 5%
- 37% abandon if phone number is required
- Correct targeting can boost conversion rates up to 300%

## Work Log

### 2025-11-23
- Subtask created based on user request for analytics dashboard
- Researched competitor features (Gravity Forms, WPForms, MonsterInsights)
- Compiled industry best practices for form analytics
- Designed database schema for events, aggregates, and field stats
- Outlined implementation phases
- Created UI mockups for dashboard and form detail pages
