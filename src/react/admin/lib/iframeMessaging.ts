/**
 * Iframe messaging utilities for parent-iframe communication
 *
 * When Form Builder V2 runs in an iframe, it needs to communicate with
 * the parent window for navigation and notifications.
 *
 * **Security Model:**
 * - Same-origin iframe only (no CORS issues)
 * - postMessage uses explicit targetOrigin (window.location.origin)
 * - Parent window verifies event.source === iframeWindow
 * - Only admin-context URLs (already authenticated)
 *
 * @since 6.6.0
 * @package SUPER_Forms
 */

interface NavigateMessage {
  type: 'navigate';
  url: string;
}

interface ToastMessage {
  type: 'toast';
  message: string;
  variant?: 'success' | 'error' | 'info' | 'warning';
}

type IframeMessage = NavigateMessage | ToastMessage;

/**
 * Check if we're running in an iframe context
 *
 * Returns true when the current window is not the top-level window,
 * indicating we're inside an iframe.
 *
 * @returns True if running in iframe, false if parent window
 *
 * @example
 * ```ts
 * if (isInIframe()) {
 *   console.log('Running in iframe context');
 * } else {
 *   console.log('Running in parent window');
 * }
 * ```
 */
export function isInIframe(): boolean {
  return window !== window.parent;
}

/**
 * Send a message to the parent window
 * Only works when running in iframe context
 *
 * SECURITY: Uses explicit same-origin targetOrigin for defense-in-depth.
 * Parent window also verifies event.source for additional security.
 */
function sendToParent(message: IframeMessage): void {
  if (!isInIframe()) {
    console.warn('sendToParent called but not in iframe context');
    return;
  }

  // Use explicit origin instead of '*' for better security
  window.parent.postMessage(message, window.location.origin);
}

/**
 * Request parent window to navigate to a URL
 *
 * This function sends a navigation request to the parent window via postMessage.
 * The parent window handles the actual navigation, allowing the iframe to
 * trigger top-level navigation without breaking out of the iframe.
 *
 * **Security:**
 * - Only works in same-origin iframe (no CORS issues)
 * - Parent window verifies message source before navigating
 * - URL is passed as-is - ensure it's already sanitized/trusted
 *
 * @param url - Absolute or relative URL to navigate to (must be trusted/sanitized)
 *
 * @example
 * ```ts
 * // Navigate to forms list after saving
 * navigateParent('/wp-admin/admin.php?page=super_forms_list');
 *
 * // Use from sfuiData
 * navigateParent(window.sfuiData.navigation.forms);
 * ```
 */
export function navigateParent(url: string): void {
  if (isInIframe()) {
    sendToParent({ type: 'navigate', url });
  } else {
    // Fallback for non-iframe context
    window.location.href = url;
  }
}

/**
 * Send a toast notification to parent window
 *
 * Sends a toast message to the parent window to be displayed as a WordPress
 * admin notice or notification. In non-iframe context, logs to console.
 *
 * **Future Enhancement:**
 * Parent window currently logs the toast - could be enhanced to show
 * WordPress admin notices using `wp.data.dispatch('core/notices')`.
 *
 * @param message - Toast message text to display
 * @param variant - Visual style of toast (default: 'info')
 *
 * @example
 * ```ts
 * // Show success message
 * showParentToast('Form saved successfully!', 'success');
 *
 * // Show error message
 * showParentToast('Failed to save form', 'error');
 *
 * // Show info message (default variant)
 * showParentToast('Processing...');
 * ```
 */
export function showParentToast(
  message: string,
  variant: 'success' | 'error' | 'info' | 'warning' = 'info'
): void {
  if (isInIframe()) {
    sendToParent({ type: 'toast', message, variant });
  } else {
    // Fallback for non-iframe context - log to console
    console.log(`[Toast ${variant}]:`, message);
  }
}
