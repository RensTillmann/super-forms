# Phase 19b: Booking Calendar UI & Availability Engine

## Overview

This subtask covers the frontend booking calendar component and the availability calculation engine. The calendar integrates as a form field element in Super Forms' drag-and-drop builder.

## Prerequisites

- Phase 19a (Booking Tables & DAL)
- Super Forms form builder integration

## Availability Engine

### SUPER_Booking_Availability_Engine

The core class responsible for calculating available time slots.

```php
<?php
/**
 * Booking Availability Engine
 *
 * Calculates available time slots considering:
 * - Staff working hours
 * - Existing bookings
 * - Buffer times
 * - Special days (holidays, custom hours)
 * - Resource availability
 * - External calendar busy times (Google, Outlook)
 *
 * @package Super_Forms
 * @since   6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Booking_Availability_Engine {

    /**
     * Get available time slots for a service
     *
     * @param int $service_id Service ID
     * @param int|null $staff_id Specific staff (null = any available)
     * @param int|null $location_id Location filter
     * @param string $date Date (Y-m-d format)
     * @param array $options Additional options
     * @return array Array of available slots
     */
    public static function get_available_slots(
        int $service_id,
        ?int $staff_id = null,
        ?int $location_id = null,
        string $date,
        array $options = []
    ): array {
        $service = SUPER_Booking_Service_DAL::get($service_id);
        if (!$service) {
            return [];
        }

        // Get eligible staff
        $staff_members = self::get_eligible_staff($service_id, $staff_id, $location_id);
        if (empty($staff_members)) {
            return [];
        }

        // Check booking window
        if (!self::is_date_within_booking_window($date, $service)) {
            return [];
        }

        $all_slots = [];
        $slot_duration = $service->slot_duration ?? $service->duration;
        $total_duration = $service->duration + ($options['extras_duration'] ?? 0);

        foreach ($staff_members as $staff) {
            $staff_slots = self::get_staff_slots_for_date(
                $staff->id,
                $date,
                $slot_duration,
                $total_duration,
                $service->buffer_before,
                $service->buffer_after,
                $location_id
            );

            foreach ($staff_slots as $slot) {
                $slot['staff_id'] = $staff->id;
                $slot['staff_name'] = $staff->name;
                $slot['staff_avatar'] = $staff->avatar_url;

                // Check resource availability if service requires resources
                if ($options['check_resources'] ?? true) {
                    $resources_available = self::check_resource_availability(
                        $service_id,
                        $slot['datetime'],
                        $total_duration,
                        $location_id
                    );
                    if (!$resources_available) {
                        continue;
                    }
                }

                $all_slots[] = $slot;
            }
        }

        // Sort by time
        usort($all_slots, function($a, $b) {
            return strcmp($a['datetime'], $b['datetime']);
        });

        // Remove duplicates for "any staff" mode
        if ($staff_id === null) {
            $all_slots = self::dedupe_slots_any_staff($all_slots);
        }

        return $all_slots;
    }

    /**
     * Get available dates in a date range
     */
    public static function get_available_dates(
        int $service_id,
        ?int $staff_id = null,
        ?int $location_id = null,
        string $start_date,
        string $end_date
    ): array {
        $available_dates = [];
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);

        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $slots = self::get_available_slots($service_id, $staff_id, $location_id, $date, [
                'check_resources' => false, // Faster for date-level check
            ]);

            if (!empty($slots)) {
                $available_dates[] = [
                    'date' => $date,
                    'slots_count' => count($slots),
                    'first_slot' => $slots[0]['time'] ?? null,
                ];
            }

            $current->modify('+1 day');
        }

        return $available_dates;
    }

    /**
     * Check if a specific slot is bookable
     */
    public static function is_slot_bookable(
        int $service_id,
        int $staff_id,
        string $datetime,
        int $attendees = 1,
        ?int $location_id = null,
        ?int $exclude_booking_id = null
    ): bool|WP_Error {
        $service = SUPER_Booking_Service_DAL::get($service_id);
        if (!$service) {
            return new WP_Error('invalid_service', __('Service not found', 'super-forms'));
        }

        // Check capacity
        if ($attendees < $service->min_capacity || $attendees > $service->max_capacity) {
            return new WP_Error('invalid_capacity', sprintf(
                __('Number of attendees must be between %d and %d', 'super-forms'),
                $service->min_capacity,
                $service->max_capacity
            ));
        }

        $date = substr($datetime, 0, 10);

        // Check booking window
        if (!self::is_date_within_booking_window($date, $service)) {
            return new WP_Error('outside_booking_window', __('This date is outside the booking window', 'super-forms'));
        }

        // Check minimum booking notice
        $booking_time = new DateTime($datetime);
        $now = new DateTime();
        $minutes_until = ($booking_time->getTimestamp() - $now->getTimestamp()) / 60;

        if ($minutes_until < $service->min_booking_notice) {
            return new WP_Error('insufficient_notice', sprintf(
                __('Bookings require at least %d minutes notice', 'super-forms'),
                $service->min_booking_notice
            ));
        }

        // Check staff working hours
        $is_working = self::is_staff_working($staff_id, $datetime, $service->duration, $location_id);
        if (!$is_working) {
            return new WP_Error('staff_unavailable', __('Staff member is not available at this time', 'super-forms'));
        }

        // Check existing bookings (including buffers)
        $is_free = SUPER_Booking_DAL::is_slot_available(
            $staff_id,
            $datetime,
            $service->duration + $service->buffer_before + $service->buffer_after,
            $exclude_booking_id
        );

        if (!$is_free) {
            return new WP_Error('slot_taken', __('This time slot is no longer available', 'super-forms'));
        }

        // Check resources
        $resources_available = self::check_resource_availability(
            $service_id,
            $datetime,
            $service->duration,
            $location_id
        );

        if (!$resources_available) {
            return new WP_Error('resource_unavailable', __('Required resources are not available', 'super-forms'));
        }

        // Check external calendar
        $external_busy = self::get_external_busy_times(
            $staff_id,
            $datetime,
            (new DateTime($datetime))->modify("+{$service->duration} minutes")->format('Y-m-d H:i:s')
        );

        if (!empty($external_busy)) {
            return new WP_Error('external_conflict', __('Staff has an external calendar conflict', 'super-forms'));
        }

        return true;
    }

    /**
     * Get staff availability for a day (working hours minus bookings)
     */
    public static function get_staff_availability(
        int $staff_id,
        string $date,
        ?int $location_id = null
    ): array {
        // Get working hours for this day
        $working_hours = self::get_staff_working_hours($staff_id, $date, $location_id);
        if (empty($working_hours)) {
            return ['is_working' => false, 'blocks' => []];
        }

        // Get existing bookings
        $bookings = SUPER_Booking_DAL::get_by_date_range(
            $date . ' 00:00:00',
            $date . ' 23:59:59',
            ['staff_id' => $staff_id, 'status' => ['pending', 'confirmed']]
        );

        // Get external calendar events
        $external_busy = self::get_external_busy_times(
            $staff_id,
            $date . ' 00:00:00',
            $date . ' 23:59:59'
        );

        // Build availability blocks
        $blocks = [];
        foreach ($working_hours as $hours) {
            $blocks[] = [
                'type' => 'available',
                'start' => $hours['start'],
                'end' => $hours['end'],
            ];
        }

        // Subtract booked times
        foreach ($bookings as $booking) {
            $blocks = self::subtract_time_block($blocks, [
                'type' => 'booked',
                'start' => substr($booking->start_datetime, 11, 5),
                'end' => substr($booking->end_datetime, 11, 5),
                'booking_id' => $booking->id,
            ]);
        }

        // Subtract external calendar events
        foreach ($external_busy as $event) {
            $blocks = self::subtract_time_block($blocks, [
                'type' => 'external',
                'start' => $event['start'],
                'end' => $event['end'],
                'title' => $event['title'] ?? 'Busy',
            ]);
        }

        return [
            'is_working' => true,
            'working_hours' => $working_hours,
            'blocks' => $blocks,
            'bookings_count' => count($bookings),
        ];
    }

    /**
     * Get external calendar busy times
     */
    public static function get_external_busy_times(
        int $staff_id,
        string $start_datetime,
        string $end_datetime
    ): array {
        $staff = SUPER_Booking_Staff_DAL::get($staff_id);
        if (!$staff || $staff->sync_mode === 'none') {
            return [];
        }

        $busy_times = [];

        // Google Calendar
        if (!empty($staff->google_calendar_token)) {
            $google_events = self::fetch_google_calendar_events(
                $staff->google_calendar_id,
                $staff->google_calendar_token,
                $start_datetime,
                $end_datetime
            );
            $busy_times = array_merge($busy_times, $google_events);
        }

        // Outlook Calendar
        if (!empty($staff->outlook_calendar_token)) {
            $outlook_events = self::fetch_outlook_calendar_events(
                $staff->outlook_calendar_id,
                $staff->outlook_calendar_token,
                $start_datetime,
                $end_datetime
            );
            $busy_times = array_merge($busy_times, $outlook_events);
        }

        return $busy_times;
    }

    // =========================================================================
    // Private Helper Methods
    // =========================================================================

    /**
     * Get staff members eligible for a service
     */
    private static function get_eligible_staff(int $service_id, ?int $staff_id, ?int $location_id): array {
        if ($staff_id) {
            $staff = SUPER_Booking_Staff_DAL::get($staff_id);
            return $staff && $staff->status === 'active' ? [$staff] : [];
        }

        return SUPER_Booking_Staff_DAL::get_by_service($service_id, [
            'status' => 'active',
            'location_id' => $location_id,
        ]);
    }

    /**
     * Check if date is within booking window
     */
    private static function is_date_within_booking_window(string $date, object $service): bool {
        $target = new DateTime($date);
        $now = new DateTime();
        $now->setTime(0, 0, 0);

        // Check minimum notice (date level)
        $min_date = clone $now;
        if ($target < $min_date) {
            return false;
        }

        // Check maximum advance booking
        $max_date = clone $now;
        $max_date->modify("+{$service->max_booking_window} days");

        return $target <= $max_date;
    }

    /**
     * Get available slots for a staff member on a specific date
     */
    private static function get_staff_slots_for_date(
        int $staff_id,
        string $date,
        int $slot_duration,
        int $total_duration,
        int $buffer_before,
        int $buffer_after,
        ?int $location_id
    ): array {
        $working_hours = self::get_staff_working_hours($staff_id, $date, $location_id);
        if (empty($working_hours)) {
            return [];
        }

        // Get existing bookings
        $bookings = SUPER_Booking_DAL::get_by_date_range(
            $date . ' 00:00:00',
            $date . ' 23:59:59',
            ['staff_id' => $staff_id, 'status' => ['pending', 'confirmed']]
        );

        // Build blocked time ranges (including buffers)
        $blocked = [];
        foreach ($bookings as $booking) {
            $start = new DateTime($booking->start_datetime);
            $end = new DateTime($booking->end_datetime);

            // Include buffer times
            $start->modify("-{$booking->buffer_before} minutes");
            $end->modify("+{$booking->buffer_after} minutes");

            $blocked[] = [
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
            ];
        }

        // Generate slots
        $slots = [];
        foreach ($working_hours as $hours) {
            $current = new DateTime($date . ' ' . $hours['start']);
            $end_time = new DateTime($date . ' ' . $hours['end']);

            // Account for service duration
            $end_time->modify("-{$total_duration} minutes");

            while ($current <= $end_time) {
                $slot_start = $current->format('H:i');
                $slot_end_time = clone $current;
                $slot_end_time->modify("+{$total_duration} minutes");
                $slot_end = $slot_end_time->format('H:i');

                // Check against blocked times
                $is_blocked = false;
                foreach ($blocked as $block) {
                    if (self::times_overlap($slot_start, $slot_end, $block['start'], $block['end'])) {
                        $is_blocked = true;
                        break;
                    }
                }

                // Check break times
                if (!$is_blocked && isset($hours['break_start']) && isset($hours['break_end'])) {
                    if (self::times_overlap($slot_start, $slot_end, $hours['break_start'], $hours['break_end'])) {
                        $is_blocked = true;
                    }
                }

                if (!$is_blocked) {
                    $slots[] = [
                        'time' => $slot_start,
                        'datetime' => $current->format('Y-m-d H:i:s'),
                        'end_time' => $slot_end,
                    ];
                }

                $current->modify("+{$slot_duration} minutes");
            }
        }

        return $slots;
    }

    /**
     * Get working hours for a staff member on a specific date
     */
    private static function get_staff_working_hours(int $staff_id, string $date, ?int $location_id): array {
        $date_obj = new DateTime($date);
        $day_of_week = (int) $date_obj->format('w'); // 0=Sunday

        // Check special days first
        $special_day = SUPER_Booking_Staff_DAL::get_special_day($staff_id, $date);
        if ($special_day) {
            if ($special_day->type === 'day_off' || $special_day->type === 'holiday') {
                return [];
            }
            if ($special_day->type === 'custom_hours') {
                return [[
                    'start' => substr($special_day->start_time, 0, 5),
                    'end' => substr($special_day->end_time, 0, 5),
                ]];
            }
        }

        // Get regular schedule
        $schedules = SUPER_Booking_Staff_DAL::get_schedule($staff_id, $location_id);
        $hours = [];

        foreach ($schedules as $schedule) {
            if ($schedule->day_of_week === $day_of_week && $schedule->is_active) {
                $entry = [
                    'start' => substr($schedule->start_time, 0, 5),
                    'end' => substr($schedule->end_time, 0, 5),
                ];
                if ($schedule->break_start && $schedule->break_end) {
                    $entry['break_start'] = substr($schedule->break_start, 0, 5);
                    $entry['break_end'] = substr($schedule->break_end, 0, 5);
                }
                $hours[] = $entry;
            }
        }

        return $hours;
    }

    /**
     * Check if staff is working at a specific datetime
     */
    private static function is_staff_working(int $staff_id, string $datetime, int $duration, ?int $location_id): bool {
        $date = substr($datetime, 0, 10);
        $time = substr($datetime, 11, 5);
        $end_time = (new DateTime($datetime))->modify("+{$duration} minutes")->format('H:i');

        $working_hours = self::get_staff_working_hours($staff_id, $date, $location_id);

        foreach ($working_hours as $hours) {
            if ($time >= $hours['start'] && $end_time <= $hours['end']) {
                // Check break time
                if (isset($hours['break_start']) && isset($hours['break_end'])) {
                    if (self::times_overlap($time, $end_time, $hours['break_start'], $hours['break_end'])) {
                        return false;
                    }
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Check resource availability
     */
    private static function check_resource_availability(
        int $service_id,
        string $datetime,
        int $duration,
        ?int $location_id
    ): bool {
        global $wpdb;

        // Get required resources for service
        $required = $wpdb->get_results($wpdb->prepare(
            "SELECT sr.resource_id, sr.quantity_required, r.quantity as total_quantity
             FROM %i sr
             INNER JOIN %i r ON sr.resource_id = r.id
             WHERE sr.service_id = %d AND sr.is_required = 1 AND r.status = 'active'",
            $wpdb->prefix . 'superforms_booking_service_resources',
            $wpdb->prefix . 'superforms_booking_resources',
            $service_id
        ));

        if (empty($required)) {
            return true; // No resources required
        }

        $start = new DateTime($datetime);
        $end = clone $start;
        $end->modify("+{$duration} minutes");

        foreach ($required as $resource) {
            // Count bookings using this resource in the time range
            $in_use = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM %i
                 WHERE JSON_CONTAINS(resources, %s, '$')
                 AND status IN ('pending', 'confirmed')
                 AND start_datetime < %s AND end_datetime > %s",
                $wpdb->prefix . 'superforms_bookings',
                json_encode($resource->resource_id),
                $end->format('Y-m-d H:i:s'),
                $start->format('Y-m-d H:i:s')
            ));

            if ((int)$in_use + $resource->quantity_required > $resource->total_quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if two time ranges overlap
     */
    private static function times_overlap(string $start1, string $end1, string $start2, string $end2): bool {
        return $start1 < $end2 && $start2 < $end1;
    }

    /**
     * Deduplicate slots for "any staff" mode
     */
    private static function dedupe_slots_any_staff(array $slots): array {
        $seen = [];
        $deduped = [];

        foreach ($slots as $slot) {
            $key = $slot['datetime'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $slot['any_staff'] = true;
                $deduped[] = $slot;
            }
        }

        return $deduped;
    }

    /**
     * Subtract a time block from availability blocks
     */
    private static function subtract_time_block(array $blocks, array $subtract): array {
        $result = [];

        foreach ($blocks as $block) {
            if ($block['type'] !== 'available') {
                $result[] = $block;
                continue;
            }

            // No overlap
            if ($subtract['end'] <= $block['start'] || $subtract['start'] >= $block['end']) {
                $result[] = $block;
                continue;
            }

            // Subtract creates up to 2 remaining blocks
            if ($subtract['start'] > $block['start']) {
                $result[] = [
                    'type' => 'available',
                    'start' => $block['start'],
                    'end' => $subtract['start'],
                ];
            }

            // Add the subtracted block itself
            $result[] = $subtract;

            if ($subtract['end'] < $block['end']) {
                $result[] = [
                    'type' => 'available',
                    'start' => $subtract['end'],
                    'end' => $block['end'],
                ];
            }
        }

        return $result;
    }

    /**
     * Fetch Google Calendar events
     */
    private static function fetch_google_calendar_events(
        string $calendar_id,
        array $token,
        string $start,
        string $end
    ): array {
        // OAuth token refresh if needed
        $access_token = SUPER_Trigger_OAuth::get_instance()->refresh_if_needed('google', $token);
        if (is_wp_error($access_token)) {
            return [];
        }

        $url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events?timeMin=%s&timeMax=%s&singleEvents=true',
            urlencode($calendar_id),
            urlencode(gmdate('c', strtotime($start))),
            urlencode(gmdate('c', strtotime($end)))
        );

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $events = [];

        foreach ($body['items'] ?? [] as $event) {
            // Skip transparent events (free/busy)
            if (($event['transparency'] ?? 'opaque') === 'transparent') {
                continue;
            }

            $events[] = [
                'start' => date('H:i', strtotime($event['start']['dateTime'] ?? $event['start']['date'])),
                'end' => date('H:i', strtotime($event['end']['dateTime'] ?? $event['end']['date'])),
                'title' => $event['summary'] ?? 'Busy',
                'source' => 'google',
            ];
        }

        return $events;
    }

    /**
     * Fetch Outlook Calendar events
     */
    private static function fetch_outlook_calendar_events(
        string $calendar_id,
        array $token,
        string $start,
        string $end
    ): array {
        // OAuth token refresh if needed
        $access_token = SUPER_Trigger_OAuth::get_instance()->refresh_if_needed('outlook', $token);
        if (is_wp_error($access_token)) {
            return [];
        }

        $url = sprintf(
            'https://graph.microsoft.com/v1.0/me/calendars/%s/calendarView?startDateTime=%s&endDateTime=%s',
            urlencode($calendar_id),
            urlencode(gmdate('c', strtotime($start))),
            urlencode(gmdate('c', strtotime($end)))
        );

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $events = [];

        foreach ($body['value'] ?? [] as $event) {
            if (($event['showAs'] ?? 'busy') === 'free') {
                continue;
            }

            $events[] = [
                'start' => date('H:i', strtotime($event['start']['dateTime'])),
                'end' => date('H:i', strtotime($event['end']['dateTime'])),
                'title' => $event['subject'] ?? 'Busy',
                'source' => 'outlook',
            ];
        }

        return $events;
    }
}
```

## Form Builder Integration

### Booking Calendar Field Element

Register the booking calendar as a form builder element:

```php
// In class-shortcodes.php or dedicated booking class

add_filter('super_shortcodes', 'super_booking_add_form_elements');

function super_booking_add_form_elements($shortcodes) {
    $shortcodes['form_elements']['shortcodes']['booking_calendar'] = array(
        'name' => __('Booking Calendar', 'super-forms'),
        'icon' => 'calendar',
        'predefined' => array(
            array(
                'tag' => 'booking_calendar',
                'group' => 'form_elements',
                'inner' => '',
                'data' => array(
                    'label' => __('Select Appointment', 'super-forms'),
                    'service_selection' => 'dropdown',
                    'staff_selection' => 'dropdown',
                    'calendar_view' => 'month',
                    'show_prices' => 'true',
                    'show_timezone' => 'true',
                ),
            ),
        ),
        'atts' => array(
            'general' => array(
                'name' => __('General', 'super-forms'),
                'fields' => array(
                    'label' => array(
                        'name' => __('Label', 'super-forms'),
                        'default' => __('Select Appointment', 'super-forms'),
                    ),
                    'name' => array(
                        'name' => __('Field Name', 'super-forms'),
                        'default' => 'booking',
                    ),
                ),
            ),
            'service_settings' => array(
                'name' => __('Service Selection', 'super-forms'),
                'fields' => array(
                    'service_selection' => array(
                        'name' => __('Selection Type', 'super-forms'),
                        'type' => 'select',
                        'default' => 'dropdown',
                        'values' => array(
                            'dropdown' => __('Dropdown', 'super-forms'),
                            'radio' => __('Radio Buttons', 'super-forms'),
                            'cards' => __('Service Cards', 'super-forms'),
                            'hidden' => __('Hidden (pre-selected)', 'super-forms'),
                        ),
                    ),
                    'allowed_services' => array(
                        'name' => __('Allowed Services', 'super-forms'),
                        'desc' => __('Leave empty for all services', 'super-forms'),
                        'type' => 'multiselect',
                        'values' => 'booking_services', // Dynamic callback
                    ),
                    'default_service' => array(
                        'name' => __('Default Service', 'super-forms'),
                        'type' => 'select',
                        'values' => 'booking_services',
                    ),
                ),
            ),
            'staff_settings' => array(
                'name' => __('Staff Selection', 'super-forms'),
                'fields' => array(
                    'staff_selection' => array(
                        'name' => __('Selection Type', 'super-forms'),
                        'type' => 'select',
                        'default' => 'dropdown',
                        'values' => array(
                            'dropdown' => __('Dropdown', 'super-forms'),
                            'radio' => __('Radio Buttons', 'super-forms'),
                            'cards' => __('Staff Cards', 'super-forms'),
                            'any' => __('"Any Available" Only', 'super-forms'),
                            'hidden' => __('Hidden (auto-assign)', 'super-forms'),
                        ),
                    ),
                    'show_staff_photos' => array(
                        'name' => __('Show Photos', 'super-forms'),
                        'type' => 'checkbox',
                        'default' => 'true',
                    ),
                    'allowed_staff' => array(
                        'name' => __('Allowed Staff', 'super-forms'),
                        'type' => 'multiselect',
                        'values' => 'booking_staff',
                    ),
                ),
            ),
            'calendar_settings' => array(
                'name' => __('Calendar Settings', 'super-forms'),
                'fields' => array(
                    'calendar_view' => array(
                        'name' => __('Default View', 'super-forms'),
                        'type' => 'select',
                        'default' => 'month',
                        'values' => array(
                            'month' => __('Month View', 'super-forms'),
                            'week' => __('Week View', 'super-forms'),
                            'list' => __('List View', 'super-forms'),
                        ),
                    ),
                    'show_timezone' => array(
                        'name' => __('Show Timezone', 'super-forms'),
                        'type' => 'checkbox',
                        'default' => 'true',
                    ),
                    'customer_timezone' => array(
                        'name' => __('Use Customer Timezone', 'super-forms'),
                        'type' => 'checkbox',
                        'default' => 'true',
                    ),
                ),
            ),
            'booking_options' => array(
                'name' => __('Booking Options', 'super-forms'),
                'fields' => array(
                    'allow_group_booking' => array(
                        'name' => __('Allow Group Booking', 'super-forms'),
                        'type' => 'checkbox',
                        'default' => '',
                    ),
                    'max_attendees' => array(
                        'name' => __('Max Attendees', 'super-forms'),
                        'type' => 'slider',
                        'default' => 1,
                        'min' => 1,
                        'max' => 20,
                    ),
                    'show_extras' => array(
                        'name' => __('Show Service Extras', 'super-forms'),
                        'type' => 'checkbox',
                        'default' => 'true',
                    ),
                ),
            ),
            'display' => array(
                'name' => __('Display', 'super-forms'),
                'fields' => array(
                    'show_prices' => array(
                        'name' => __('Show Prices', 'super-forms'),
                        'type' => 'checkbox',
                        'default' => 'true',
                    ),
                    'primary_color' => array(
                        'name' => __('Primary Color', 'super-forms'),
                        'type' => 'color',
                        'default' => '#3498db',
                    ),
                ),
            ),
        ),
    );

    return $shortcodes;
}
```

## REST API Endpoints for Calendar

```php
// Availability endpoints

// GET /wp-json/super-forms/v1/booking/availability/dates
register_rest_route('super-forms/v1', '/booking/availability/dates', array(
    'methods' => 'GET',
    'callback' => 'super_booking_get_available_dates',
    'permission_callback' => '__return_true', // Public
    'args' => array(
        'service_id' => array('required' => true, 'type' => 'integer'),
        'staff_id' => array('type' => 'integer'),
        'location_id' => array('type' => 'integer'),
        'start_date' => array('required' => true, 'type' => 'string'),
        'end_date' => array('required' => true, 'type' => 'string'),
    ),
));

function super_booking_get_available_dates(WP_REST_Request $request) {
    $dates = SUPER_Booking_Availability_Engine::get_available_dates(
        $request->get_param('service_id'),
        $request->get_param('staff_id'),
        $request->get_param('location_id'),
        $request->get_param('start_date'),
        $request->get_param('end_date')
    );

    return rest_ensure_response($dates);
}

// GET /wp-json/super-forms/v1/booking/availability/slots
register_rest_route('super-forms/v1', '/booking/availability/slots', array(
    'methods' => 'GET',
    'callback' => 'super_booking_get_available_slots',
    'permission_callback' => '__return_true',
    'args' => array(
        'service_id' => array('required' => true, 'type' => 'integer'),
        'staff_id' => array('type' => 'integer'),
        'location_id' => array('type' => 'integer'),
        'date' => array('required' => true, 'type' => 'string'),
    ),
));

function super_booking_get_available_slots(WP_REST_Request $request) {
    $slots = SUPER_Booking_Availability_Engine::get_available_slots(
        $request->get_param('service_id'),
        $request->get_param('staff_id'),
        $request->get_param('location_id'),
        $request->get_param('date')
    );

    return rest_ensure_response($slots);
}
```

## Frontend JavaScript/React Component

The booking calendar uses a React component for the interactive UI:

```jsx
// /src/assets/js/frontend/booking-calendar.jsx

import React, { useState, useEffect } from 'react';
import { format, addMonths, startOfMonth, endOfMonth } from 'date-fns';

const BookingCalendar = ({
    formId,
    settings,
    onBookingSelected,
}) => {
    const [step, setStep] = useState('service'); // service, staff, datetime, extras
    const [selectedService, setSelectedService] = useState(null);
    const [selectedStaff, setSelectedStaff] = useState(null);
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedSlot, setSelectedSlot] = useState(null);
    const [selectedExtras, setSelectedExtras] = useState({});

    const [services, setServices] = useState([]);
    const [staff, setStaff] = useState([]);
    const [availableDates, setAvailableDates] = useState([]);
    const [availableSlots, setAvailableSlots] = useState([]);
    const [currentMonth, setCurrentMonth] = useState(new Date());
    const [loading, setLoading] = useState(false);

    // Fetch services on mount
    useEffect(() => {
        fetchServices();
    }, []);

    // Fetch staff when service selected
    useEffect(() => {
        if (selectedService) {
            fetchStaff(selectedService.id);
        }
    }, [selectedService]);

    // Fetch available dates when service/staff selected
    useEffect(() => {
        if (selectedService && (settings.staff_selection === 'hidden' || selectedStaff)) {
            fetchAvailableDates();
        }
    }, [selectedService, selectedStaff, currentMonth]);

    // Fetch slots when date selected
    useEffect(() => {
        if (selectedDate) {
            fetchAvailableSlots(selectedDate);
        }
    }, [selectedDate]);

    const fetchServices = async () => {
        const response = await wp.apiFetch({
            path: '/super-forms/v1/booking/services',
        });
        setServices(response);
    };

    const fetchStaff = async (serviceId) => {
        const response = await wp.apiFetch({
            path: `/super-forms/v1/booking/services/${serviceId}/staff`,
        });
        setStaff(response);
    };

    const fetchAvailableDates = async () => {
        setLoading(true);
        const start = format(startOfMonth(currentMonth), 'yyyy-MM-dd');
        const end = format(endOfMonth(addMonths(currentMonth, 1)), 'yyyy-MM-dd');

        const params = new URLSearchParams({
            service_id: selectedService.id,
            start_date: start,
            end_date: end,
        });

        if (selectedStaff && selectedStaff.id !== 'any') {
            params.append('staff_id', selectedStaff.id);
        }

        const response = await wp.apiFetch({
            path: `/super-forms/v1/booking/availability/dates?${params}`,
        });
        setAvailableDates(response);
        setLoading(false);
    };

    const fetchAvailableSlots = async (date) => {
        setLoading(true);
        const params = new URLSearchParams({
            service_id: selectedService.id,
            date: date,
        });

        if (selectedStaff && selectedStaff.id !== 'any') {
            params.append('staff_id', selectedStaff.id);
        }

        const response = await wp.apiFetch({
            path: `/super-forms/v1/booking/availability/slots?${params}`,
        });
        setAvailableSlots(response);
        setLoading(false);
    };

    const handleSlotSelect = (slot) => {
        setSelectedSlot(slot);

        // Build booking data
        const bookingData = {
            service_id: selectedService.id,
            service_name: selectedService.name,
            staff_id: slot.staff_id || selectedStaff?.id,
            staff_name: slot.staff_name || selectedStaff?.name,
            datetime: slot.datetime,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            duration: selectedService.duration,
            attendees: 1,
            extras: selectedExtras,
            total_price: calculateTotalPrice(),
            deposit_required: calculateDeposit(),
        };

        onBookingSelected(bookingData);
        setStep('extras');
    };

    // ... render methods for each step
};

export default BookingCalendar;
```

## Success Criteria

- [ ] `SUPER_Booking_Availability_Engine` fully implemented
- [ ] Slot calculation considers all constraints (working hours, bookings, buffers, resources)
- [ ] Google Calendar integration working (2-way sync)
- [ ] Outlook Calendar integration working
- [ ] Form builder element registered and configurable
- [ ] REST API endpoints for availability
- [ ] React calendar component functional
- [ ] Mobile-responsive design
- [ ] Timezone handling correct
- [ ] Performance: <500ms for availability queries
