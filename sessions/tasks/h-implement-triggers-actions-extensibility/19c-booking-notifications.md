# Phase 19c: Booking Notifications & Reminders

## Overview

This subtask covers the notification and reminder system for bookings, including email/SMS delivery, reminder scheduling via Action Scheduler, and calendar synchronization.

## Prerequisites

- Phase 19a (Booking Tables & DAL)
- Phase 19b (Booking Calendar UI)
- Action Scheduler (bundled in Super Forms)
- Triggers/Actions system (Phase 1-5)

## Notification Types

### 1. Instant Notifications

Sent immediately when booking events occur:

| Event | Customer | Staff | Admin |
|-------|----------|-------|-------|
| Booking Created | ✅ Confirmation | ✅ New booking alert | Optional |
| Booking Confirmed | ✅ Confirmation | ✅ Confirmation | - |
| Booking Cancelled | ✅ Cancellation notice | ✅ Cancellation notice | - |
| Booking Rescheduled | ✅ New time details | ✅ Schedule change | - |
| Payment Received | ✅ Receipt | - | - |
| Waitlist Slot Opened | ✅ Opportunity notice | - | - |

### 2. Scheduled Reminders

Sent at configured intervals before/after appointments:

| Timing | Default | Purpose |
|--------|---------|---------|
| 24 hours before | ✅ Enabled | Primary reminder |
| 1 hour before | Optional | Last-minute reminder |
| Custom (X minutes) | Configurable | Business-specific |
| 24 hours after | Optional | Follow-up / Review request |

## Database: Reminder Management

Already defined in Phase 19a `wp_superforms_booking_reminders` table.

## SUPER_Booking_Reminder_DAL

```php
<?php
/**
 * Booking Reminder Data Access Layer
 *
 * @package Super_Forms
 * @since   6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Booking_Reminder_DAL {

    /**
     * Table name
     */
    private static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'superforms_booking_reminders';
    }

    /**
     * Action Scheduler hook names
     */
    const AS_REMINDER_HOOK = 'super_booking_send_reminder';
    const AS_GROUP = 'superforms_booking';

    /**
     * Schedule default reminders for a booking
     */
    public static function schedule_default_reminders(int $booking_id): array {
        $booking = SUPER_Booking_DAL::get($booking_id);
        if (!$booking || !in_array($booking->status, ['pending', 'confirmed'])) {
            return [];
        }

        // Get reminder settings (from service or global)
        $settings = self::get_reminder_settings($booking->service_id);
        $scheduled = [];

        foreach ($settings as $reminder) {
            $result = self::schedule_reminder($booking_id, $reminder);
            if (!is_wp_error($result)) {
                $scheduled[] = $result;
            }
        }

        return $scheduled;
    }

    /**
     * Schedule a single reminder
     */
    public static function schedule_reminder(int $booking_id, array $config): int|WP_Error {
        global $wpdb;

        $booking = SUPER_Booking_DAL::get($booking_id);
        if (!$booking) {
            return new WP_Error('booking_not_found', __('Booking not found', 'super-forms'));
        }

        // Calculate scheduled time
        $booking_time = new DateTime($booking->start_datetime, new DateTimeZone($booking->timezone));
        $offset_minutes = (int) $config['time_offset'];

        if ($config['reminder_type'] === 'before') {
            $scheduled_time = clone $booking_time;
            $scheduled_time->modify("-{$offset_minutes} minutes");
        } else {
            $scheduled_time = clone $booking_time;
            $scheduled_time->modify("+{$offset_minutes} minutes");
        }

        // Don't schedule if time has passed
        $now = new DateTime('now', new DateTimeZone($booking->timezone));
        if ($scheduled_time <= $now) {
            return new WP_Error('time_passed', __('Reminder time has already passed', 'super-forms'));
        }

        // Insert reminder record
        $data = [
            'booking_id' => $booking_id,
            'reminder_type' => $config['reminder_type'] ?? 'before',
            'time_offset' => $offset_minutes,
            'channel' => $config['channel'] ?? 'email',
            'recipient_type' => $config['recipient_type'] ?? 'customer',
            'status' => 'scheduled',
            'scheduled_for' => $scheduled_time->format('Y-m-d H:i:s'),
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert(self::table_name(), $data);
        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        $reminder_id = $wpdb->insert_id;

        // Schedule with Action Scheduler
        $action_id = as_schedule_single_action(
            $scheduled_time->getTimestamp(),
            self::AS_REMINDER_HOOK,
            ['reminder_id' => $reminder_id],
            self::AS_GROUP
        );

        // Update reminder with Action Scheduler ID
        $wpdb->update(
            self::table_name(),
            ['action_scheduler_id' => $action_id],
            ['id' => $reminder_id]
        );

        return $reminder_id;
    }

    /**
     * Cancel all reminders for a booking
     */
    public static function cancel_for_booking(int $booking_id): int {
        global $wpdb;

        // Get pending reminders
        $reminders = $wpdb->get_results($wpdb->prepare(
            "SELECT id, action_scheduler_id FROM %i WHERE booking_id = %d AND status = 'scheduled'",
            self::table_name(),
            $booking_id
        ));

        $cancelled = 0;
        foreach ($reminders as $reminder) {
            // Unschedule from Action Scheduler
            if ($reminder->action_scheduler_id) {
                as_unschedule_action(self::AS_REMINDER_HOOK, ['reminder_id' => $reminder->id], self::AS_GROUP);
            }

            // Update status
            $wpdb->update(
                self::table_name(),
                ['status' => 'cancelled'],
                ['id' => $reminder->id]
            );
            $cancelled++;
        }

        return $cancelled;
    }

    /**
     * Process and send a reminder
     */
    public static function send_reminder(int $reminder_id): bool|WP_Error {
        global $wpdb;

        $reminder = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            self::table_name(),
            $reminder_id
        ));

        if (!$reminder) {
            return new WP_Error('not_found', __('Reminder not found', 'super-forms'));
        }

        if ($reminder->status !== 'scheduled') {
            return new WP_Error('invalid_status', __('Reminder is not scheduled', 'super-forms'));
        }

        $booking = SUPER_Booking_DAL::get($reminder->booking_id);
        if (!$booking) {
            self::mark_failed($reminder_id, 'Booking not found');
            return new WP_Error('booking_not_found', __('Booking not found', 'super-forms'));
        }

        // Skip if booking is cancelled/completed
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            $wpdb->update(self::table_name(), ['status' => 'cancelled'], ['id' => $reminder_id]);
            return new WP_Error('booking_not_active', __('Booking is no longer active', 'super-forms'));
        }

        $success = true;
        $errors = [];

        // Send based on channel
        if (in_array($reminder->channel, ['email', 'both'])) {
            $email_result = self::send_email_reminder($booking, $reminder);
            if (is_wp_error($email_result)) {
                $errors[] = $email_result->get_error_message();
                $success = false;
            }
        }

        if (in_array($reminder->channel, ['sms', 'both'])) {
            $sms_result = self::send_sms_reminder($booking, $reminder);
            if (is_wp_error($sms_result)) {
                $errors[] = $sms_result->get_error_message();
                // SMS failure shouldn't fail the whole reminder if email succeeded
            }
        }

        // Update status
        if ($success) {
            $wpdb->update(
                self::table_name(),
                [
                    'status' => 'sent',
                    'sent_at' => current_time('mysql'),
                ],
                ['id' => $reminder_id]
            );

            // Fire trigger event
            $event_name = $reminder->time_offset == 1440 ? 'booking.reminder_24h' :
                         ($reminder->time_offset == 60 ? 'booking.reminder_1h' : 'booking.reminder_custom');

            SUPER_Trigger_Registry::get_instance()->fire_event($event_name, [
                'booking_id' => $booking->id,
                'booking_uid' => $booking->booking_uid,
                'customer_email' => $booking->customer_email,
                'reminder_type' => $reminder->reminder_type,
                'time_offset' => $reminder->time_offset,
                'channel' => $reminder->channel,
                'booking' => $booking,
            ]);

            return true;
        } else {
            self::mark_failed($reminder_id, implode('; ', $errors));
            return new WP_Error('send_failed', implode('; ', $errors));
        }
    }

    /**
     * Send email reminder
     */
    private static function send_email_reminder(object $booking, object $reminder): bool|WP_Error {
        // Determine recipient
        $recipients = [];
        if (in_array($reminder->recipient_type, ['customer', 'both'])) {
            $recipients[] = $booking->customer_email;
        }
        if (in_array($reminder->recipient_type, ['staff', 'both'])) {
            $staff = SUPER_Booking_Staff_DAL::get($booking->staff_id);
            if ($staff && $staff->email_notifications) {
                $recipients[] = $staff->email;
            }
        }

        if (empty($recipients)) {
            return new WP_Error('no_recipients', __('No email recipients', 'super-forms'));
        }

        // Get email template
        $template = self::get_email_template('reminder', $reminder->time_offset);

        // Build context for tag replacement
        $context = self::build_notification_context($booking);

        // Replace tags in template
        $subject = self::replace_tags($template['subject'], $context);
        $body = self::replace_tags($template['body'], $context);

        // Send email
        foreach ($recipients as $to) {
            $sent = wp_mail($to, $subject, $body, [
                'Content-Type: text/html; charset=UTF-8',
            ]);

            if (!$sent) {
                return new WP_Error('email_failed', sprintf(__('Failed to send email to %s', 'super-forms'), $to));
            }
        }

        return true;
    }

    /**
     * Send SMS reminder
     */
    private static function send_sms_reminder(object $booking, object $reminder): bool|WP_Error {
        // Get phone number based on recipient type
        $phone = null;
        if (in_array($reminder->recipient_type, ['customer', 'both'])) {
            $phone = $booking->customer_phone;
        }
        if (in_array($reminder->recipient_type, ['staff', 'both'])) {
            $staff = SUPER_Booking_Staff_DAL::get($booking->staff_id);
            if ($staff && $staff->sms_notifications && $staff->sms_phone) {
                $phone = $staff->sms_phone;
            }
        }

        if (empty($phone)) {
            return new WP_Error('no_phone', __('No phone number for SMS', 'super-forms'));
        }

        // Get SMS template
        $template = self::get_sms_template('reminder');

        // Build context
        $context = self::build_notification_context($booking);

        // Replace tags
        $message = self::replace_tags($template, $context);

        // Send via configured SMS provider
        $result = self::send_sms($phone, $message);

        return $result;
    }

    /**
     * Send SMS via provider
     */
    private static function send_sms(string $phone, string $message): bool|WP_Error {
        // Get SMS provider settings
        $provider = get_option('super_booking_sms_provider', 'twilio');
        $settings = get_option('super_booking_sms_settings', []);

        if (empty($settings['api_key'])) {
            return new WP_Error('sms_not_configured', __('SMS provider not configured', 'super-forms'));
        }

        switch ($provider) {
            case 'twilio':
                return self::send_twilio_sms($phone, $message, $settings);
            case 'nexmo':
                return self::send_nexmo_sms($phone, $message, $settings);
            default:
                return apply_filters('super_booking_send_sms', false, $phone, $message, $settings);
        }
    }

    /**
     * Send SMS via Twilio
     */
    private static function send_twilio_sms(string $phone, string $message, array $settings): bool|WP_Error {
        $url = sprintf(
            'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
            $settings['account_sid']
        );

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($settings['account_sid'] . ':' . $settings['auth_token']),
            ],
            'body' => [
                'From' => $settings['from_number'],
                'To' => $phone,
                'Body' => $message,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 400) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return new WP_Error('twilio_error', $body['message'] ?? 'SMS send failed');
        }

        return true;
    }

    /**
     * Mark reminder as failed
     */
    private static function mark_failed(int $reminder_id, string $error): void {
        global $wpdb;

        $reminder = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            self::table_name(),
            $reminder_id
        ));

        $retry_count = $reminder ? $reminder->retry_count + 1 : 1;

        $wpdb->update(
            self::table_name(),
            [
                'status' => $retry_count >= 3 ? 'failed' : 'scheduled',
                'error_message' => $error,
                'retry_count' => $retry_count,
            ],
            ['id' => $reminder_id]
        );

        // Reschedule if under retry limit
        if ($retry_count < 3 && $reminder) {
            as_schedule_single_action(
                time() + (300 * $retry_count), // 5, 10, 15 minutes
                self::AS_REMINDER_HOOK,
                ['reminder_id' => $reminder_id],
                self::AS_GROUP
            );
        }
    }

    /**
     * Get reminder settings for a service
     */
    private static function get_reminder_settings(int $service_id): array {
        // Check service-specific settings
        $service = SUPER_Booking_Service_DAL::get($service_id);
        $service_settings = $service->settings['reminders'] ?? null;

        if ($service_settings !== null) {
            return $service_settings;
        }

        // Fall back to global settings
        $global = get_option('super_booking_reminder_settings', []);

        if (empty($global)) {
            // Default: 24h and 1h reminders
            return [
                [
                    'reminder_type' => 'before',
                    'time_offset' => 1440, // 24 hours
                    'channel' => 'email',
                    'recipient_type' => 'customer',
                ],
                [
                    'reminder_type' => 'before',
                    'time_offset' => 60, // 1 hour
                    'channel' => 'email',
                    'recipient_type' => 'customer',
                ],
            ];
        }

        return $global;
    }

    /**
     * Build notification context from booking
     */
    private static function build_notification_context(object $booking): array {
        $service = SUPER_Booking_Service_DAL::get($booking->service_id);
        $staff = SUPER_Booking_Staff_DAL::get($booking->staff_id);
        $location = $booking->location_id ? SUPER_Booking_Location_DAL::get($booking->location_id) : null;

        $booking_time = new DateTime($booking->start_datetime, new DateTimeZone($booking->timezone));

        return [
            'booking_uid' => $booking->booking_uid,
            'customer_name' => $booking->customer_name,
            'customer_first_name' => explode(' ', $booking->customer_name)[0],
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone ?? '',

            'service_name' => $service->name ?? '',
            'service_duration' => $service->duration ?? 0,
            'service_price' => number_format($service->price ?? 0, 2),

            'staff_name' => $staff->name ?? '',
            'staff_email' => $staff->email ?? '',
            'staff_phone' => $staff->phone ?? '',

            'location_name' => $location->name ?? '',
            'location_address' => $location ? self::format_address($location) : '',

            'booking_date' => $booking_time->format('l, F j, Y'),
            'booking_time' => $booking_time->format('g:i A'),
            'booking_datetime' => $booking_time->format('l, F j, Y \a\t g:i A'),
            'booking_timezone' => $booking->timezone,

            'total_amount' => number_format($booking->total_amount, 2),
            'deposit_amount' => number_format($booking->deposit_amount, 2),
            'currency' => $booking->currency,

            'cancel_url' => self::get_cancel_url($booking),
            'reschedule_url' => self::get_reschedule_url($booking),
            'manage_url' => self::get_manage_url($booking),

            'meeting_type' => $booking->meeting_type,
            'meeting_url' => $booking->meeting_url ?? '',

            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ];
    }

    /**
     * Replace tags in template
     */
    private static function replace_tags(string $template, array $context): string {
        foreach ($context as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    /**
     * Format address
     */
    private static function format_address(object $location): string {
        $parts = array_filter([
            $location->address_line_1,
            $location->address_line_2,
            $location->city,
            $location->state,
            $location->postal_code,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Get cancel URL
     */
    private static function get_cancel_url(object $booking): string {
        return add_query_arg([
            'super_booking' => 'cancel',
            'uid' => $booking->booking_uid,
            'email' => base64_encode($booking->customer_email),
        ], home_url());
    }

    /**
     * Get reschedule URL
     */
    private static function get_reschedule_url(object $booking): string {
        return add_query_arg([
            'super_booking' => 'reschedule',
            'uid' => $booking->booking_uid,
            'email' => base64_encode($booking->customer_email),
        ], home_url());
    }

    /**
     * Get manage URL
     */
    private static function get_manage_url(object $booking): string {
        return add_query_arg([
            'super_booking' => 'manage',
            'uid' => $booking->booking_uid,
            'email' => base64_encode($booking->customer_email),
        ], home_url());
    }

    /**
     * Get email template
     */
    private static function get_email_template(string $type, int $time_offset = 0): array {
        $templates = get_option('super_booking_email_templates', []);

        $key = $type;
        if ($type === 'reminder') {
            $key = $time_offset >= 1440 ? 'reminder_24h' : ($time_offset >= 60 ? 'reminder_1h' : 'reminder_custom');
        }

        if (isset($templates[$key])) {
            return $templates[$key];
        }

        // Default templates
        return self::get_default_email_template($key);
    }

    /**
     * Get default email template
     */
    private static function get_default_email_template(string $type): array {
        $templates = [
            'confirmation' => [
                'subject' => 'Booking Confirmed: {service_name} on {booking_date}',
                'body' => '
                    <h2>Your appointment is confirmed!</h2>
                    <p>Hi {customer_first_name},</p>
                    <p>Your booking has been confirmed. Here are the details:</p>
                    <table>
                        <tr><td><strong>Service:</strong></td><td>{service_name}</td></tr>
                        <tr><td><strong>Date:</strong></td><td>{booking_datetime}</td></tr>
                        <tr><td><strong>Provider:</strong></td><td>{staff_name}</td></tr>
                        <tr><td><strong>Location:</strong></td><td>{location_name}<br>{location_address}</td></tr>
                        <tr><td><strong>Reference:</strong></td><td>{booking_uid}</td></tr>
                    </table>
                    <p>Need to make changes? <a href="{manage_url}">Manage your booking</a></p>
                    <p>Thank you for choosing {site_name}!</p>
                ',
            ],
            'reminder_24h' => [
                'subject' => 'Reminder: Your appointment tomorrow - {service_name}',
                'body' => '
                    <h2>Appointment Reminder</h2>
                    <p>Hi {customer_first_name},</p>
                    <p>This is a friendly reminder about your upcoming appointment:</p>
                    <table>
                        <tr><td><strong>Service:</strong></td><td>{service_name}</td></tr>
                        <tr><td><strong>Date:</strong></td><td>{booking_datetime}</td></tr>
                        <tr><td><strong>Provider:</strong></td><td>{staff_name}</td></tr>
                        <tr><td><strong>Location:</strong></td><td>{location_name}<br>{location_address}</td></tr>
                    </table>
                    <p>Need to reschedule? <a href="{reschedule_url}">Change your appointment</a></p>
                    <p>See you tomorrow!</p>
                ',
            ],
            'reminder_1h' => [
                'subject' => 'Starting Soon: {service_name} in 1 hour',
                'body' => '
                    <h2>Your appointment starts soon!</h2>
                    <p>Hi {customer_first_name},</p>
                    <p>Your {service_name} appointment with {staff_name} starts in 1 hour at {booking_time}.</p>
                    <p><strong>Location:</strong> {location_name}, {location_address}</p>
                    <p>See you soon!</p>
                ',
            ],
            'cancellation' => [
                'subject' => 'Booking Cancelled: {service_name}',
                'body' => '
                    <h2>Booking Cancelled</h2>
                    <p>Hi {customer_first_name},</p>
                    <p>Your booking has been cancelled:</p>
                    <table>
                        <tr><td><strong>Service:</strong></td><td>{service_name}</td></tr>
                        <tr><td><strong>Original Date:</strong></td><td>{booking_datetime}</td></tr>
                        <tr><td><strong>Reference:</strong></td><td>{booking_uid}</td></tr>
                    </table>
                    <p>Would you like to <a href="{site_url}">book a new appointment</a>?</p>
                ',
            ],
            'rescheduled' => [
                'subject' => 'Booking Rescheduled: {service_name}',
                'body' => '
                    <h2>Booking Rescheduled</h2>
                    <p>Hi {customer_first_name},</p>
                    <p>Your booking has been rescheduled. Here are the new details:</p>
                    <table>
                        <tr><td><strong>Service:</strong></td><td>{service_name}</td></tr>
                        <tr><td><strong>New Date:</strong></td><td>{booking_datetime}</td></tr>
                        <tr><td><strong>Provider:</strong></td><td>{staff_name}</td></tr>
                        <tr><td><strong>Location:</strong></td><td>{location_name}</td></tr>
                    </table>
                    <p><a href="{manage_url}">Manage your booking</a></p>
                ',
            ],
            'staff_notification' => [
                'subject' => 'New Booking: {customer_name} - {service_name}',
                'body' => '
                    <h2>New Booking</h2>
                    <p>You have a new booking:</p>
                    <table>
                        <tr><td><strong>Customer:</strong></td><td>{customer_name}</td></tr>
                        <tr><td><strong>Email:</strong></td><td>{customer_email}</td></tr>
                        <tr><td><strong>Phone:</strong></td><td>{customer_phone}</td></tr>
                        <tr><td><strong>Service:</strong></td><td>{service_name}</td></tr>
                        <tr><td><strong>Date:</strong></td><td>{booking_datetime}</td></tr>
                        <tr><td><strong>Location:</strong></td><td>{location_name}</td></tr>
                    </table>
                ',
            ],
        ];

        return $templates[$type] ?? $templates['confirmation'];
    }

    /**
     * Get SMS template
     */
    private static function get_sms_template(string $type): string {
        $templates = get_option('super_booking_sms_templates', []);

        if (isset($templates[$type])) {
            return $templates[$type];
        }

        // Default SMS templates (keep short for SMS limits)
        $defaults = [
            'reminder' => 'Reminder: Your {service_name} appointment is tomorrow at {booking_time} with {staff_name}. Reply CANCEL to cancel.',
            'confirmation' => 'Confirmed: {service_name} on {booking_date} at {booking_time}. Ref: {booking_uid}',
            'cancellation' => 'Your {service_name} appointment on {booking_date} has been cancelled.',
        ];

        return $defaults[$type] ?? $defaults['reminder'];
    }
}
```

## Notification Manager (Instant Notifications)

```php
<?php
/**
 * Booking Notification Manager
 *
 * Handles instant notifications for booking events
 *
 * @package Super_Forms
 * @since   6.5.0
 */

class SUPER_Booking_Notification_Manager {

    /**
     * Initialize hooks
     */
    public static function init(): void {
        // Hook into booking events for instant notifications
        add_action('super_booking_created', [self::class, 'on_booking_created'], 10, 2);
        add_action('super_booking_confirmed', [self::class, 'on_booking_confirmed'], 10, 1);
        add_action('super_booking_cancelled', [self::class, 'on_booking_cancelled'], 10, 3);
        add_action('super_booking_rescheduled', [self::class, 'on_booking_rescheduled'], 10, 3);
        add_action('super_booking_completed', [self::class, 'on_booking_completed'], 10, 1);

        // Action Scheduler hook for reminders
        add_action(SUPER_Booking_Reminder_DAL::AS_REMINDER_HOOK, [self::class, 'process_reminder'], 10, 1);
    }

    /**
     * Handle booking created
     */
    public static function on_booking_created(int $booking_id, array $data): void {
        $booking = SUPER_Booking_DAL::get($booking_id);
        if (!$booking) return;

        $settings = get_option('super_booking_notification_settings', []);

        // Send customer confirmation
        if ($settings['customer_confirmation'] ?? true) {
            self::send_email($booking, 'confirmation', 'customer');
        }

        // Send staff notification
        if ($settings['staff_new_booking'] ?? true) {
            self::send_email($booking, 'staff_notification', 'staff');
        }
    }

    /**
     * Handle booking confirmed
     */
    public static function on_booking_confirmed(int $booking_id): void {
        // Usually confirmation email already sent on creation
        // This handles cases where booking required approval/payment first
    }

    /**
     * Handle booking cancelled
     */
    public static function on_booking_cancelled(int $booking_id, string $cancelled_by, ?string $reason): void {
        $booking = SUPER_Booking_DAL::get($booking_id);
        if (!$booking) return;

        $settings = get_option('super_booking_notification_settings', []);

        // Notify customer
        if ($settings['customer_cancellation'] ?? true) {
            self::send_email($booking, 'cancellation', 'customer');
        }

        // Notify staff (unless they cancelled it)
        if (($settings['staff_cancellation'] ?? true) && $cancelled_by !== 'staff') {
            self::send_email($booking, 'cancellation', 'staff');
        }
    }

    /**
     * Handle booking rescheduled
     */
    public static function on_booking_rescheduled(int $new_booking_id, int $old_booking_id, string $old_datetime): void {
        $booking = SUPER_Booking_DAL::get($new_booking_id);
        if (!$booking) return;

        $settings = get_option('super_booking_notification_settings', []);

        // Notify customer
        if ($settings['customer_reschedule'] ?? true) {
            self::send_email($booking, 'rescheduled', 'customer');
        }

        // Notify staff
        if ($settings['staff_reschedule'] ?? true) {
            self::send_email($booking, 'rescheduled', 'staff');
        }
    }

    /**
     * Handle booking completed - send follow-up
     */
    public static function on_booking_completed(int $booking_id): void {
        $booking = SUPER_Booking_DAL::get($booking_id);
        if (!$booking) return;

        $settings = get_option('super_booking_notification_settings', []);

        // Schedule follow-up email (24h after)
        if ($settings['customer_followup'] ?? false) {
            SUPER_Booking_Reminder_DAL::schedule_reminder($booking_id, [
                'reminder_type' => 'after',
                'time_offset' => 1440, // 24 hours
                'channel' => 'email',
                'recipient_type' => 'customer',
            ]);
        }
    }

    /**
     * Process a scheduled reminder
     */
    public static function process_reminder(array $args): void {
        $reminder_id = $args['reminder_id'] ?? 0;
        if ($reminder_id) {
            SUPER_Booking_Reminder_DAL::send_reminder($reminder_id);
        }
    }

    /**
     * Send email helper
     */
    private static function send_email(object $booking, string $template_type, string $recipient_type): bool {
        $context = SUPER_Booking_Reminder_DAL::build_notification_context($booking);

        // Get recipient
        $to = '';
        if ($recipient_type === 'customer') {
            $to = $booking->customer_email;
        } elseif ($recipient_type === 'staff') {
            $staff = SUPER_Booking_Staff_DAL::get($booking->staff_id);
            if ($staff && $staff->email_notifications) {
                $to = $staff->email;
            }
        }

        if (empty($to)) {
            return false;
        }

        // Get template
        $template = SUPER_Booking_Reminder_DAL::get_email_template($template_type, 0);

        // Replace tags
        $subject = SUPER_Booking_Reminder_DAL::replace_tags($template['subject'], $context);
        $body = SUPER_Booking_Reminder_DAL::replace_tags($template['body'], $context);

        // Wrap in HTML email template
        $body = self::wrap_email_template($body);

        return wp_mail($to, $subject, $body, [
            'Content-Type: text/html; charset=UTF-8',
        ]);
    }

    /**
     * Wrap content in email template
     */
    private static function wrap_email_template(string $content): string {
        $template = get_option('super_booking_email_wrapper', '');

        if (empty($template)) {
            $template = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
                {content}
                <hr style="margin-top: 30px; border: none; border-top: 1px solid #eee;">
                <p style="font-size: 12px; color: #666;">{site_name} | <a href="{site_url}">{site_url}</a></p>
            </body>
            </html>
            ';
        }

        return str_replace('{content}', $content, $template);
    }
}
```

## Calendar Sync Service

```php
<?php
/**
 * Booking Calendar Sync Service
 *
 * 2-way sync with Google Calendar and Outlook
 *
 * @package Super_Forms
 * @since   6.5.0
 */

class SUPER_Booking_Calendar_Sync {

    /**
     * Sync booking to external calendars
     */
    public static function sync_booking_to_calendar(int $booking_id): array {
        $booking = SUPER_Booking_DAL::get($booking_id);
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        $staff = SUPER_Booking_Staff_DAL::get($booking->staff_id);
        if (!$staff || $staff->sync_mode === 'none') {
            return ['skipped' => 'Sync not enabled'];
        }

        $results = [];

        // Google Calendar
        if (!empty($staff->google_calendar_token)) {
            $result = self::create_google_calendar_event($booking, $staff);
            $results['google'] = $result;

            // Store event ID on booking for future updates/deletion
            if (!is_wp_error($result) && isset($result['id'])) {
                SUPER_Booking_DAL::update($booking_id, [
                    'meeting_data' => array_merge(
                        $booking->meeting_data ?? [],
                        ['google_event_id' => $result['id']]
                    ),
                ]);
            }
        }

        // Outlook Calendar
        if (!empty($staff->outlook_calendar_token)) {
            $result = self::create_outlook_calendar_event($booking, $staff);
            $results['outlook'] = $result;

            if (!is_wp_error($result) && isset($result['id'])) {
                SUPER_Booking_DAL::update($booking_id, [
                    'meeting_data' => array_merge(
                        $booking->meeting_data ?? [],
                        ['outlook_event_id' => $result['id']]
                    ),
                ]);
            }
        }

        // Fire trigger for calendar sync
        SUPER_Trigger_Registry::get_instance()->fire_event('booking.calendar_synced', [
            'booking_id' => $booking_id,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Delete booking from external calendars
     */
    public static function delete_from_calendars(int $booking_id): array {
        $booking = SUPER_Booking_DAL::get($booking_id);
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        $staff = SUPER_Booking_Staff_DAL::get($booking->staff_id);
        if (!$staff) {
            return ['error' => 'Staff not found'];
        }

        $results = [];

        // Google Calendar
        if (!empty($booking->meeting_data['google_event_id'])) {
            $results['google'] = self::delete_google_calendar_event(
                $booking->meeting_data['google_event_id'],
                $staff
            );
        }

        // Outlook Calendar
        if (!empty($booking->meeting_data['outlook_event_id'])) {
            $results['outlook'] = self::delete_outlook_calendar_event(
                $booking->meeting_data['outlook_event_id'],
                $staff
            );
        }

        return $results;
    }

    /**
     * Create Google Calendar event
     */
    private static function create_google_calendar_event(object $booking, object $staff): array|WP_Error {
        $access_token = SUPER_Trigger_OAuth::get_instance()->refresh_if_needed(
            'google',
            $staff->google_calendar_token
        );

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $service = SUPER_Booking_Service_DAL::get($booking->service_id);
        $location = $booking->location_id ? SUPER_Booking_Location_DAL::get($booking->location_id) : null;

        $event = [
            'summary' => sprintf('%s - %s', $service->name, $booking->customer_name),
            'description' => sprintf(
                "Booking: %s\nCustomer: %s\nEmail: %s\nPhone: %s",
                $booking->booking_uid,
                $booking->customer_name,
                $booking->customer_email,
                $booking->customer_phone ?? 'N/A'
            ),
            'start' => [
                'dateTime' => gmdate('c', strtotime($booking->start_datetime)),
                'timeZone' => $booking->timezone,
            ],
            'end' => [
                'dateTime' => gmdate('c', strtotime($booking->end_datetime)),
                'timeZone' => $booking->timezone,
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => 60],
                    ['method' => 'popup', 'minutes' => 15],
                ],
            ],
        ];

        if ($location) {
            $event['location'] = sprintf(
                '%s, %s, %s %s',
                $location->address_line_1,
                $location->city,
                $location->state,
                $location->postal_code
            );
        }

        // Add video conferencing if virtual meeting
        if ($booking->meeting_type === 'google_meet') {
            $event['conferenceData'] = [
                'createRequest' => [
                    'requestId' => $booking->booking_uid,
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ];
        }

        $url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events%s',
            urlencode($staff->google_calendar_id ?: 'primary'),
            $booking->meeting_type === 'google_meet' ? '?conferenceDataVersion=1' : ''
        );

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($event),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) >= 400) {
            return new WP_Error('google_calendar_error', $body['error']['message'] ?? 'Unknown error');
        }

        // If Google Meet was created, save the meeting URL
        if (!empty($body['conferenceData']['entryPoints'])) {
            foreach ($body['conferenceData']['entryPoints'] as $entry) {
                if ($entry['entryPointType'] === 'video') {
                    SUPER_Booking_DAL::update($booking->id, [
                        'meeting_url' => $entry['uri'],
                    ]);
                    break;
                }
            }
        }

        return $body;
    }

    /**
     * Delete Google Calendar event
     */
    private static function delete_google_calendar_event(string $event_id, object $staff): bool|WP_Error {
        $access_token = SUPER_Trigger_OAuth::get_instance()->refresh_if_needed(
            'google',
            $staff->google_calendar_token
        );

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events/%s',
            urlencode($staff->google_calendar_id ?: 'primary'),
            urlencode($event_id)
        );

        $response = wp_remote_request($url, [
            'method' => 'DELETE',
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return wp_remote_retrieve_response_code($response) === 204;
    }

    /**
     * Create Outlook Calendar event
     */
    private static function create_outlook_calendar_event(object $booking, object $staff): array|WP_Error {
        $access_token = SUPER_Trigger_OAuth::get_instance()->refresh_if_needed(
            'outlook',
            $staff->outlook_calendar_token
        );

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $service = SUPER_Booking_Service_DAL::get($booking->service_id);

        $event = [
            'subject' => sprintf('%s - %s', $service->name, $booking->customer_name),
            'body' => [
                'contentType' => 'text',
                'content' => sprintf(
                    "Booking: %s\nCustomer: %s\nEmail: %s",
                    $booking->booking_uid,
                    $booking->customer_name,
                    $booking->customer_email
                ),
            ],
            'start' => [
                'dateTime' => gmdate('Y-m-d\TH:i:s', strtotime($booking->start_datetime)),
                'timeZone' => $booking->timezone,
            ],
            'end' => [
                'dateTime' => gmdate('Y-m-d\TH:i:s', strtotime($booking->end_datetime)),
                'timeZone' => $booking->timezone,
            ],
        ];

        // Add Teams meeting if virtual
        if ($booking->meeting_type === 'teams') {
            $event['isOnlineMeeting'] = true;
            $event['onlineMeetingProvider'] = 'teamsForBusiness';
        }

        $url = sprintf(
            'https://graph.microsoft.com/v1.0/me/calendars/%s/events',
            urlencode($staff->outlook_calendar_id ?: 'primary')
        );

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($event),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) >= 400) {
            return new WP_Error('outlook_calendar_error', $body['error']['message'] ?? 'Unknown error');
        }

        // Save Teams meeting URL if created
        if (!empty($body['onlineMeeting']['joinUrl'])) {
            SUPER_Booking_DAL::update($booking->id, [
                'meeting_url' => $body['onlineMeeting']['joinUrl'],
            ]);
        }

        return $body;
    }

    /**
     * Delete Outlook Calendar event
     */
    private static function delete_outlook_calendar_event(string $event_id, object $staff): bool|WP_Error {
        $access_token = SUPER_Trigger_OAuth::get_instance()->refresh_if_needed(
            'outlook',
            $staff->outlook_calendar_token
        );

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $url = sprintf(
            'https://graph.microsoft.com/v1.0/me/events/%s',
            urlencode($event_id)
        );

        $response = wp_remote_request($url, [
            'method' => 'DELETE',
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return wp_remote_retrieve_response_code($response) === 204;
    }
}
```

## Success Criteria

- [ ] Reminder scheduling via Action Scheduler working
- [ ] Default reminders created on booking confirmation
- [ ] Email notifications sending correctly (confirmation, cancellation, reminder)
- [ ] SMS notifications via Twilio working
- [ ] Tag replacement in templates functioning
- [ ] Calendar sync to Google Calendar working
- [ ] Calendar sync to Outlook working
- [ ] Google Meet auto-creation working
- [ ] Microsoft Teams meeting creation working
- [ ] Reminder retry mechanism on failure
- [ ] Events firing for all notification actions
- [ ] Admin can customize email templates
- [ ] Notifications cancelled when booking cancelled
