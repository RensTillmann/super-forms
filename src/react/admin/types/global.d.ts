/**
 * Global TypeScript declarations for SFUI Admin
 * Defines the window.sfuiData object passed from PHP
 */

export {};

declare global {
  interface Window {
    sfuiData: SFUIData;
  }
}

/**
 * Main data object passed from PHP to React
 */
export interface SFUIData {
  /** Current WordPress admin page (e.g., 'super_create_form', 'super_settings') */
  currentPage: string;

  /** Form ID when on form builder page */
  formId: number;

  /** Email configurations for the form */
  emails: EmailConfig[];

  /** Form translations */
  translations: Record<string, unknown>;

  /** Form settings */
  settings: Record<string, unknown>;

  /** WordPress AJAX URL */
  ajaxUrl: string;

  /** Nonce for form-specific AJAX actions */
  nonce: string;

  /** Nonce for WordPress REST API */
  restNonce: string;

  /** Current user's email address */
  currentUserEmail: string;

  /** Internationalization strings */
  i18n: I18nStrings;
}

/**
 * Email configuration object
 */
export interface EmailConfig {
  id: string;
  name: string;
  enabled: boolean;
  to: string[];
  cc?: string[];
  bcc?: string[];
  from?: string;
  fromName?: string;
  replyTo?: string;
  subject: string;
  body: string;
  bodyType: 'visual' | 'html' | 'legacy_html';
  template?: string;
  attachments?: string[];
  conditions?: EmailCondition[];
  schedule?: EmailSchedule;
}

/**
 * Email condition for conditional sending
 */
export interface EmailCondition {
  field: string;
  operator: string;
  value: string;
  logic?: 'AND' | 'OR';
}

/**
 * Email schedule configuration
 */
export interface EmailSchedule {
  enabled: boolean;
  delay?: number;
  delayUnit?: 'minutes' | 'hours' | 'days';
  sendAt?: string;
}

/**
 * Internationalization strings from PHP
 */
export interface I18nStrings {
  addEmail: string;
  deleteEmail: string;
  selectEmail: string;
  emailSettings: string;
  basicSettings: string;
  emailHeaders: string;
  emailContent: string;
  attachments: string;
  advancedOptions: string;
  conditionalLogic: string;
  scheduleSettings: string;
  preview: string;
  sendTestEmail: string;
  saving: string;
  saved: string;
  error: string;
}
