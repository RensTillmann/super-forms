---
name: 02a-frontend-event-trigger-system
status: pending
created: 2025-12-15
depends_on: 01-schema-zod-mcp-foundation
---

# Frontend Event Trigger System

## Problem/Goal

Create a **unified frontend event trigger system** that provides a single entry point for all frontend-initiated automation events. Instead of creating separate AJAX endpoints for each event type (button clicks, field interactions, timer events, etc.), we build one extensible system.

This architectural decision prevents technical debt and ensures:
- Centralized security (nonce, CSRF, rate limiting)
- Consistent response format
- Easy addition of new event types
- Single point for logging/debugging

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    Frontend Event Flow                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Button Click]──┐                                              │
│  [Field Blur]────┼──► triggerFrontendEvent() ──► AJAX POST     │
│  [Timer Done]────┤         (TypeScript)              │          │
│  [Step Change]───┘                                   ▼          │
│                                                                 │
│                              wp_ajax_super_trigger_frontend_event
│                                           │                     │
│                                           ▼                     │
│                              SUPER_Frontend_Event_Trigger       │
│                              ├─ validate_event_type()           │
│                              ├─ validate_params()               │
│                              ├─ build_context()                 │
│                              ├─ fire_automation_event()         │
│                              └─ format_response()               │
│                                           │                     │
│                                           ▼                     │
│                              SUPER_Automation_Executor          │
│                                   ::fire_event()                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Success Criteria

- [ ] `SUPER_Frontend_Event_Trigger` class created in `/src/includes/class-frontend-event-trigger.php`
- [ ] Single AJAX endpoint `trigger_frontend_event` registered (nopriv=true)
- [ ] Event type registry with `register_frontend_event_type()` method
- [ ] `button_click` registered as first event type (validates the pattern works)
- [ ] Frontend module `/src/react/admin/lib/frontendEvents.ts` created
- [ ] TypeScript types for event params and responses
- [ ] Generic `triggerFrontendEvent()` function
- [ ] Convenience wrapper `triggerButtonClick()` for button events
- [ ] Standardized response format matching automation system

## Event Type Registry Schema

Each frontend event type defines:

```php
$registry->register_frontend_event_type('button_click', [
    // Pattern for automation event ID (placeholders replaced from params)
    'event_pattern' => 'button.{event_id}.clicked',

    // Required POST parameters
    'required_params' => ['form_id', 'button_id', 'event_id'],

    // Optional POST parameters
    'optional_params' => ['form_data', 'entry_id', 'session_key'],

    // Validation callback (returns true or WP_Error)
    'validation_callback' => [$this, 'validate_button_click'],

    // Context builder (returns array for fire_event)
    'context_builder' => [$this, 'build_button_context'],

    // Whether this event type supports sync responses
    'supports_sync' => true,

    // Rate limit config (optional)
    'rate_limit' => [
        'max_requests' => 10,
        'window_seconds' => 60,
        'scope' => 'user_form', // user, form, user_form, ip
    ],
]);
```

## PHP Implementation

### Class: SUPER_Frontend_Event_Trigger

```php
<?php
class SUPER_Frontend_Event_Trigger {

    private static $event_types = [];

    /**
     * Initialize - register AJAX handler
     */
    public static function init() {
        add_action('wp_ajax_super_trigger_frontend_event', [__CLASS__, 'handle_request']);
        add_action('wp_ajax_nopriv_super_trigger_frontend_event', [__CLASS__, 'handle_request']);

        // Register built-in event types
        self::register_builtin_event_types();
    }

    /**
     * Register a frontend event type
     */
    public static function register_frontend_event_type($type_id, $config) {
        $defaults = [
            'event_pattern' => '',
            'required_params' => [],
            'optional_params' => [],
            'validation_callback' => null,
            'context_builder' => null,
            'supports_sync' => true,
            'rate_limit' => null,
        ];

        self::$event_types[$type_id] = wp_parse_args($config, $defaults);
    }

    /**
     * Main request handler
     */
    public static function handle_request() {
        // 1. Verify nonce
        if (!check_ajax_referer('super_frontend_event', 'nonce', false)) {
            wp_send_json_error([
                'code' => 'invalid_nonce',
                'message' => __('Security check failed. Please refresh and try again.', 'super-forms'),
            ], 403);
        }

        // 2. Get event type
        $event_type = sanitize_key($_POST['event_type'] ?? '');
        if (!isset(self::$event_types[$event_type])) {
            wp_send_json_error([
                'code' => 'invalid_event_type',
                'message' => __('Unknown event type.', 'super-forms'),
            ], 400);
        }

        $config = self::$event_types[$event_type];

        // 3. Extract and validate required params
        $params = self::extract_params($config);
        if (is_wp_error($params)) {
            wp_send_json_error([
                'code' => $params->get_error_code(),
                'message' => $params->get_error_message(),
            ], 400);
        }

        // 4. Run type-specific validation
        if ($config['validation_callback'] && is_callable($config['validation_callback'])) {
            $validation = call_user_func($config['validation_callback'], $params);
            if (is_wp_error($validation)) {
                wp_send_json_error([
                    'code' => $validation->get_error_code(),
                    'message' => $validation->get_error_message(),
                ], 400);
            }
        }

        // 5. Check rate limit
        if ($config['rate_limit']) {
            $rate_check = self::check_rate_limit($event_type, $params, $config['rate_limit']);
            if (is_wp_error($rate_check)) {
                wp_send_json_error([
                    'code' => 'rate_limited',
                    'message' => $rate_check->get_error_message(),
                ], 429);
            }
        }

        // 6. Build event ID from pattern
        $event_id = self::build_event_id($config['event_pattern'], $params);

        // 7. Build context
        $context = [
            'form_id' => $params['form_id'] ?? 0,
            'user_id' => get_current_user_id(),
            'user_ip' => SUPER_Common::real_ip(),
            'timestamp' => current_time('c'),
            'source_url' => wp_get_referer() ?: '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        // Add type-specific context
        if ($config['context_builder'] && is_callable($config['context_builder'])) {
            $type_context = call_user_func($config['context_builder'], $params);
            $context = array_merge($context, $type_context);
        }

        // 8. Fire automation event
        $start_time = microtime(true);
        $result = SUPER_Automation_Executor::fire_event($event_id, $context);
        $execution_time = (microtime(true) - $start_time) * 1000;

        // 9. Format and return response
        $response = self::format_response($result, $execution_time);

        if ($response['success']) {
            wp_send_json_success($response['data']);
        } else {
            wp_send_json_error($response['error'], 500);
        }
    }

    /**
     * Extract params from POST based on config
     */
    private static function extract_params($config) {
        $params = [];

        // Required params
        foreach ($config['required_params'] as $param) {
            if (!isset($_POST[$param]) || $_POST[$param] === '') {
                return new WP_Error(
                    'missing_param',
                    sprintf(__('Missing required parameter: %s', 'super-forms'), $param)
                );
            }
            $params[$param] = self::sanitize_param($param, $_POST[$param]);
        }

        // Optional params
        foreach ($config['optional_params'] as $param) {
            if (isset($_POST[$param])) {
                $params[$param] = self::sanitize_param($param, $_POST[$param]);
            }
        }

        return $params;
    }

    /**
     * Sanitize parameter based on name/type
     */
    private static function sanitize_param($name, $value) {
        // JSON params
        if (in_array($name, ['form_data', 'context', 'metadata'])) {
            return json_decode(stripslashes($value), true) ?: [];
        }

        // Integer params
        if (in_array($name, ['form_id', 'entry_id', 'step_index'])) {
            return absint($value);
        }

        // Key params (alphanumeric + underscore)
        if (in_array($name, ['event_id', 'event_type', 'interaction_type'])) {
            return sanitize_key($value);
        }

        // Default: text field
        return sanitize_text_field($value);
    }

    /**
     * Build event ID from pattern
     */
    private static function build_event_id($pattern, $params) {
        $event_id = $pattern;
        foreach ($params as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $event_id = str_replace('{' . $key . '}', $value, $event_id);
            }
        }
        return $event_id;
    }

    /**
     * Format response for frontend
     */
    private static function format_response($result, $execution_time_ms) {
        // If fire_event returns structured response
        if (is_array($result) && isset($result['success'])) {
            return [
                'success' => $result['success'],
                'data' => array_merge(
                    $result['data'] ?? [],
                    ['execution_time_ms' => round($execution_time_ms, 2)]
                ),
                'error' => $result['error'] ?? null,
            ];
        }

        // Default success (fire_event completed without error)
        return [
            'success' => true,
            'data' => [
                'message' => __('Action completed successfully', 'super-forms'),
                'execution_time_ms' => round($execution_time_ms, 2),
            ],
        ];
    }

    /**
     * Check rate limit
     */
    private static function check_rate_limit($event_type, $params, $config) {
        // Build rate limit key based on scope
        $key_parts = ['super_fe_rate', $event_type];

        switch ($config['scope']) {
            case 'user':
                $key_parts[] = get_current_user_id() ?: SUPER_Common::real_ip();
                break;
            case 'form':
                $key_parts[] = $params['form_id'] ?? 0;
                break;
            case 'user_form':
                $key_parts[] = (get_current_user_id() ?: SUPER_Common::real_ip()) . '_' . ($params['form_id'] ?? 0);
                break;
            case 'ip':
                $key_parts[] = SUPER_Common::real_ip();
                break;
        }

        $key = implode('_', $key_parts);
        $count = get_transient($key) ?: 0;

        if ($count >= $config['max_requests']) {
            return new WP_Error(
                'rate_limited',
                __('Too many requests. Please wait before trying again.', 'super-forms')
            );
        }

        set_transient($key, $count + 1, $config['window_seconds']);
        return true;
    }

    /**
     * Register built-in event types
     */
    private static function register_builtin_event_types() {
        // Button click events
        self::register_frontend_event_type('button_click', [
            'event_pattern' => 'button.{event_id}.clicked',
            'required_params' => ['form_id', 'button_id', 'event_id'],
            'optional_params' => ['form_data', 'entry_id', 'session_key', 'button_name'],
            'validation_callback' => [__CLASS__, 'validate_button_click'],
            'context_builder' => [__CLASS__, 'build_button_context'],
            'supports_sync' => true,
            'rate_limit' => [
                'max_requests' => 10,
                'window_seconds' => 60,
                'scope' => 'user_form',
            ],
        ]);
    }

    /**
     * Validate button click event
     */
    public static function validate_button_click($params) {
        // Verify form exists
        $form = get_post($params['form_id']);
        if (!$form || $form->post_type !== 'super_form') {
            return new WP_Error('invalid_form', __('Form not found.', 'super-forms'));
        }

        // TODO: Optionally verify button exists in form elements
        // This would require loading form settings and checking elements array

        return true;
    }

    /**
     * Build context for button click events
     */
    public static function build_button_context($params) {
        $context = [
            'button_id' => $params['button_id'],
            'button_name' => $params['button_name'] ?? '',
            'event_name' => $params['event_id'],
            'form_data' => $params['form_data'] ?? [],
            'entry_id' => $params['entry_id'] ?? null,
            'session_key' => $params['session_key'] ?? '',
        ];

        // Add user email if logged in
        $user = wp_get_current_user();
        if ($user->ID) {
            $context['user_email'] = $user->user_email;
        }

        return $context;
    }
}
```

### Hook into WordPress

In `super-forms.php` or appropriate init location:

```php
// Initialize frontend event trigger system
if (class_exists('SUPER_Frontend_Event_Trigger')) {
    SUPER_Frontend_Event_Trigger::init();
}
```

## TypeScript Implementation

### File: `/src/react/admin/lib/frontendEvents.ts`

```typescript
/**
 * Frontend Event Trigger System
 *
 * Unified system for triggering automation events from the frontend.
 * All event types go through a single AJAX endpoint with type-specific
 * validation and context building on the PHP side.
 */

// =============================================================================
// Types
// =============================================================================

export type FrontendEventType =
  | 'button_click'
  | 'field_interaction'
  | 'step_navigation'
  | 'timer_event';

export interface FrontendEventResponse {
  success: boolean;
  data?: {
    message?: string;
    file_url?: string;
    redirect_url?: string;
    access_url?: string;
    overlay_content?: string;
    result?: unknown;
    execution_time_ms?: number;
    automation_log_id?: number;
  };
  error?: {
    code: string;
    message: string;
  };
}

export interface BaseFrontendEventParams {
  formId: number;
  formData?: Record<string, unknown>;
  entryId?: number;
  sessionKey?: string;
}

export interface ButtonClickParams extends BaseFrontendEventParams {
  buttonId: string;
  eventId: string;
  buttonName?: string;
}

export interface FieldInteractionParams extends BaseFrontendEventParams {
  fieldName: string;
  interactionType: 'blur' | 'change' | 'focus';
  fieldValue?: unknown;
  previousValue?: unknown;
}

export interface StepNavigationParams extends BaseFrontendEventParams {
  stepIndex: number;
  navigationAction: 'entered' | 'completed' | 'left';
  validationPassed?: boolean;
}

// Map event types to their params
interface EventTypeParamsMap {
  button_click: ButtonClickParams;
  field_interaction: FieldInteractionParams;
  step_navigation: StepNavigationParams;
  timer_event: BaseFrontendEventParams & { timerName: string; timerAction: string };
}

// =============================================================================
// Core Function
// =============================================================================

/**
 * Trigger a frontend event and execute matching automations.
 *
 * @param eventType - The type of event (button_click, field_interaction, etc.)
 * @param params - Event-specific parameters
 * @returns Response from automation execution
 */
export async function triggerFrontendEvent<T extends FrontendEventType>(
  eventType: T,
  params: EventTypeParamsMap[T]
): Promise<FrontendEventResponse> {
  const formData = new FormData();

  // Always required
  formData.append('action', 'super_trigger_frontend_event');
  formData.append('nonce', (window as any).super_frontend_event_nonce || '');
  formData.append('event_type', eventType);
  formData.append('form_id', String(params.formId));

  // Common optional params
  if (params.formData) {
    formData.append('form_data', JSON.stringify(params.formData));
  }
  if (params.entryId) {
    formData.append('entry_id', String(params.entryId));
  }
  if (params.sessionKey) {
    formData.append('session_key', params.sessionKey);
  }

  // Event-specific params
  const specificParams = params as Record<string, unknown>;
  for (const [key, value] of Object.entries(specificParams)) {
    // Skip already-added common params
    if (['formId', 'formData', 'entryId', 'sessionKey'].includes(key)) continue;

    // Convert camelCase to snake_case for PHP
    const snakeKey = key.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);

    if (value !== undefined && value !== null) {
      if (typeof value === 'object') {
        formData.append(snakeKey, JSON.stringify(value));
      } else {
        formData.append(snakeKey, String(value));
      }
    }
  }

  try {
    const response = await fetch((window as any).ajaxurl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });

    const json = await response.json();

    // WordPress wp_send_json_success/error format
    if (json.success !== undefined) {
      return {
        success: json.success,
        data: json.success ? json.data : undefined,
        error: !json.success ? json.data : undefined,
      };
    }

    // Direct response format
    return json as FrontendEventResponse;
  } catch (error) {
    return {
      success: false,
      error: {
        code: 'network_error',
        message: error instanceof Error ? error.message : 'Network request failed',
      },
    };
  }
}

// =============================================================================
// Convenience Wrappers
// =============================================================================

/**
 * Trigger a button click event.
 *
 * @example
 * const result = await triggerButtonClick({
 *   formId: 123,
 *   buttonId: 'button-abc',
 *   eventId: 'generate_pdf',
 *   formData: { name: 'John', email: 'john@example.com' },
 * });
 *
 * if (result.success && result.data?.file_url) {
 *   window.open(result.data.file_url, '_blank');
 * }
 */
export function triggerButtonClick(params: ButtonClickParams): Promise<FrontendEventResponse> {
  return triggerFrontendEvent('button_click', params);
}

/**
 * Trigger a field interaction event.
 */
export function triggerFieldInteraction(params: FieldInteractionParams): Promise<FrontendEventResponse> {
  return triggerFrontendEvent('field_interaction', params);
}

/**
 * Trigger a step navigation event.
 */
export function triggerStepNavigation(params: StepNavigationParams): Promise<FrontendEventResponse> {
  return triggerFrontendEvent('step_navigation', params);
}

// =============================================================================
// Response Handlers
// =============================================================================

/**
 * Handle common response actions based on response data.
 * Call this after receiving a successful response to execute standard behaviors.
 */
export function handleFrontendEventResponse(
  response: FrontendEventResponse,
  options?: {
    onFileDownload?: (url: string) => void;
    onRedirect?: (url: string) => void;
    onAccessUrl?: (url: string) => void;
    onOverlay?: (content: string) => void;
    onMessage?: (message: string) => void;
  }
): void {
  if (!response.success || !response.data) return;

  const { data } = response;

  // File download
  if (data.file_url) {
    if (options?.onFileDownload) {
      options.onFileDownload(data.file_url);
    } else {
      // Default: open in new tab
      window.open(data.file_url, '_blank');
    }
  }

  // Redirect
  if (data.redirect_url) {
    if (options?.onRedirect) {
      options.onRedirect(data.redirect_url);
    } else {
      // Default: navigate
      window.location.href = data.redirect_url;
    }
  }

  // Access URL (copy to clipboard scenario)
  if (data.access_url && options?.onAccessUrl) {
    options.onAccessUrl(data.access_url);
  }

  // Overlay content
  if (data.overlay_content && options?.onOverlay) {
    options.onOverlay(data.overlay_content);
  }

  // Success message
  if (data.message && options?.onMessage) {
    options.onMessage(data.message);
  }
}
```

## Files to Create/Modify

**Create:**
- `/src/includes/class-frontend-event-trigger.php` - Core PHP handler class
- `/src/react/admin/lib/frontendEvents.ts` - TypeScript trigger module

**Modify:**
- `/src/includes/class-super-forms.php` or similar - Add init hook for trigger class
- Localize script to pass `super_frontend_event_nonce` to frontend

## Future Event Types (Reference)

These can be added later by calling `register_frontend_event_type()`:

| Event Type | Event Pattern | Use Case |
|------------|---------------|----------|
| `field_interaction` | `field.{field_name}.{interaction_type}` | On blur/change calculations |
| `step_navigation` | `step.{step_index}.{navigation_action}` | Wizard analytics, validation |
| `timer_event` | `timer.{timer_name}.{timer_action}` | Countdown completions |
| `visibility_event` | `element.{element_id}.visible` | Lazy loading, analytics |
| `file_upload` | `file.{field_name}.uploaded` | Post-upload processing |

## Security Considerations

1. **Nonce Verification**: All requests require valid nonce
2. **Rate Limiting**: Per event type, configurable scope (user, form, IP)
3. **Parameter Sanitization**: Type-specific sanitization rules
4. **Form Ownership**: Validation callbacks can check permissions
5. **Event Type Whitelist**: Only registered event types accepted

## Dependencies

- **Requires**: Subtask 01 (button schema defines `eventId` property)
- **Consumed by**: Subtask 02b (button-specific implementation)
- **Consumed by**: Subtask 03 (automation binding UI)
- **Consumed by**: Subtask 04 (built-in node types return response data)

## Work Log

[Will be populated during implementation]
