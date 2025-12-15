/**
 * Frontend Event Trigger System
 *
 * Unified system for triggering automation events from the frontend.
 * All event types (button clicks, field interactions, step navigation, etc.)
 * go through a single AJAX endpoint with type-specific validation and
 * context building on the PHP side.
 *
 * @package Super_Forms
 * @since 6.7.0
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

export interface TimerEventParams extends BaseFrontendEventParams {
  timerName: string;
  timerAction: 'started' | 'completed' | 'cancelled';
}

// Map event types to their params
interface EventTypeParamsMap {
  button_click: ButtonClickParams;
  field_interaction: FieldInteractionParams;
  step_navigation: StepNavigationParams;
  timer_event: TimerEventParams;
}

// =============================================================================
// Global Window Extensions
// =============================================================================

declare global {
  interface Window {
    ajaxurl?: string;
    super_frontend_event_nonce?: string;
    // Frontend form scripts use super_common_i18n
    super_common_i18n?: {
      ajaxurl?: string;
      frontend_event_nonce?: string;
      [key: string]: unknown;
    };
  }
}

// =============================================================================
// Helper Functions
// =============================================================================

/**
 * Convert camelCase to snake_case for PHP compatibility
 */
function camelToSnake(str: string): string {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
}

/**
 * Get the AJAX URL
 * Checks multiple locations since admin and frontend use different globals
 */
function getAjaxUrl(): string {
  // Direct window property (WordPress admin sets this)
  if (window.ajaxurl) {
    return window.ajaxurl;
  }
  // Frontend form scripts use super_common_i18n
  if (window.super_common_i18n?.ajaxurl) {
    return window.super_common_i18n.ajaxurl;
  }
  return '/wp-admin/admin-ajax.php';
}

/**
 * Get the frontend event nonce
 * Checks multiple locations since admin and frontend use different globals
 */
function getNonce(): string {
  // Direct window property (admin pages may set this directly)
  if (window.super_frontend_event_nonce) {
    return window.super_frontend_event_nonce;
  }
  // Frontend form scripts use super_common_i18n
  if (window.super_common_i18n?.frontend_event_nonce) {
    return window.super_common_i18n.frontend_event_nonce;
  }
  return '';
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
 *
 * @example
 * const result = await triggerFrontendEvent('button_click', {
 *   formId: 123,
 *   buttonId: 'button-abc',
 *   eventId: 'generate_pdf',
 *   formData: { name: 'John' },
 * });
 */
export async function triggerFrontendEvent<T extends FrontendEventType>(
  eventType: T,
  params: EventTypeParamsMap[T]
): Promise<FrontendEventResponse> {
  const formData = new FormData();

  // Always required
  formData.append('action', 'super_trigger_frontend_event');
  formData.append('nonce', getNonce());
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

  // Event-specific params - convert from typed params to FormData
  const specificParams = params as unknown as Record<string, unknown>;
  const commonParams = ['formId', 'formData', 'entryId', 'sessionKey'];

  for (const [key, value] of Object.entries(specificParams)) {
    // Skip already-added common params
    if (commonParams.includes(key)) continue;

    // Convert camelCase to snake_case for PHP
    const snakeKey = camelToSnake(key);

    if (value !== undefined && value !== null) {
      if (typeof value === 'object') {
        formData.append(snakeKey, JSON.stringify(value));
      } else {
        formData.append(snakeKey, String(value));
      }
    }
  }

  try {
    const response = await fetch(getAjaxUrl(), {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });

    if (!response.ok) {
      return {
        success: false,
        error: {
          code: 'http_error',
          message: `HTTP ${response.status}: ${response.statusText}`,
        },
      };
    }

    const json = await response.json();

    // WordPress wp_send_json_success/error format
    if (typeof json.success === 'boolean') {
      return {
        success: json.success,
        data: json.success ? json.data : undefined,
        error: !json.success ? json.data : undefined,
      };
    }

    // Direct response format (fallback)
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
export function triggerButtonClick(
  params: ButtonClickParams
): Promise<FrontendEventResponse> {
  return triggerFrontendEvent('button_click', params);
}

/**
 * Trigger a field interaction event.
 *
 * @example
 * const result = await triggerFieldInteraction({
 *   formId: 123,
 *   fieldName: 'email',
 *   interactionType: 'blur',
 *   fieldValue: 'john@example.com',
 * });
 */
export function triggerFieldInteraction(
  params: FieldInteractionParams
): Promise<FrontendEventResponse> {
  return triggerFrontendEvent('field_interaction', params);
}

/**
 * Trigger a step navigation event.
 *
 * @example
 * const result = await triggerStepNavigation({
 *   formId: 123,
 *   stepIndex: 2,
 *   navigationAction: 'entered',
 *   validationPassed: true,
 * });
 */
export function triggerStepNavigation(
  params: StepNavigationParams
): Promise<FrontendEventResponse> {
  return triggerFrontendEvent('step_navigation', params);
}

/**
 * Trigger a timer event.
 *
 * @example
 * const result = await triggerTimerEvent({
 *   formId: 123,
 *   timerName: 'session_timeout',
 *   timerAction: 'completed',
 * });
 */
export function triggerTimerEvent(
  params: TimerEventParams
): Promise<FrontendEventResponse> {
  return triggerFrontendEvent('timer_event', params);
}

// =============================================================================
// Response Handlers
// =============================================================================

export interface ResponseHandlerOptions {
  onFileDownload?: (url: string) => void;
  onRedirect?: (url: string) => void;
  onAccessUrl?: (url: string) => void;
  onOverlay?: (content: string) => void;
  onMessage?: (message: string) => void;
  onResult?: (result: unknown) => void;
}

/**
 * Handle common response actions based on response data.
 * Call this after receiving a successful response to execute standard behaviors.
 *
 * @example
 * const result = await triggerButtonClick({ ... });
 * handleFrontendEventResponse(result, {
 *   onFileDownload: (url) => window.open(url, '_blank'),
 *   onMessage: (msg) => toast.success(msg),
 * });
 */
export function handleFrontendEventResponse(
  response: FrontendEventResponse,
  options?: ResponseHandlerOptions
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

  // Custom result data
  if (data.result !== undefined && options?.onResult) {
    options.onResult(data.result);
  }
}

// =============================================================================
// Utility Exports
// =============================================================================

/**
 * Check if the frontend event system is available (nonce is set)
 */
export function isFrontendEventSystemAvailable(): boolean {
  return Boolean(getNonce());
}

/**
 * Get execution time from response (useful for performance monitoring)
 */
export function getExecutionTime(
  response: FrontendEventResponse
): number | null {
  return response.data?.execution_time_ms ?? null;
}
