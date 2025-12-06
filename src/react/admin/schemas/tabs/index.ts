/**
 * Schema-First Form Builder - Tab Schemas
 *
 * Default tab registrations for the form builder.
 * Import this module to register all standard tabs.
 */

import { registerTab } from './registry';

// =============================================================================
// Default Tab Registrations
// =============================================================================

/**
 * Canvas tab - Main form building canvas (always first, not lazy)
 * Note: Canvas is special - it shows the main canvas area, not a side panel
 */
export const CanvasTab = registerTab({
  id: 'canvas',
  label: 'Canvas',
  icon: 'Layout',
  position: 0,
  lazyLoad: false,
  hidden: true, // Hidden from tab bar, use Builder tab instead
  description: 'Form building canvas',
});

/**
 * Builder tab - Returns user to the canvas view
 * Note: This is the visible tab that sets activeTab to 'canvas'
 */
export const BuilderTab = registerTab({
  id: 'builder',
  label: 'Builder',
  icon: 'Layout',
  position: 5,
  lazyLoad: false,
  description: 'Form building canvas',
});

/**
 * Emails tab - Email template builder
 */
export const EmailsTab = registerTab({
  id: 'emails',
  label: 'Emails',
  icon: 'Mail',
  position: 10,
  lazyLoad: false,
  hidden: false,
  description: 'Configure email notifications',
});

/**
 * Settings tab - Form settings and configuration
 */
export const SettingsTab = registerTab({
  id: 'settings',
  label: 'Settings',
  icon: 'Settings',
  position: 20,
  lazyLoad: false,
  description: 'Form settings and configuration',
});

/**
 * Entries tab - Form submissions viewer
 */
export const EntriesTab = registerTab({
  id: 'entries',
  label: 'Entries',
  icon: 'Database',
  position: 30,
  lazyLoad: false,
  description: 'View form submissions',
});

/**
 * Automation tab - Visual workflow builder with triggers, actions, conditions
 */
export const AutomationTab = registerTab({
  id: 'automation',
  label: 'Automation',
  icon: 'Workflow',
  position: 40,
  lazyLoad: false,
  description: 'Build automated workflows',
});

/**
 * Style tab - Form theming and appearance
 */
export const StyleTab = registerTab({
  id: 'style',
  label: 'Style',
  icon: 'PaintBucket',
  position: 50,
  lazyLoad: false,
  description: 'Customize form appearance',
});

/**
 * Themes tab - Pick from presets or save custom themes
 */
export const ThemesTab = registerTab({
  id: 'themes',
  label: 'Themes',
  icon: 'Palette',
  position: 51,
  lazyLoad: false,
  description: 'Apply and create themes',
});

/**
 * Integrations tab - Third-party connections
 */
export const IntegrationsTab = registerTab({
  id: 'integrations',
  label: 'Integrations',
  icon: 'Webhook',
  position: 60,
  lazyLoad: false,
  description: 'Connect to external services',
});

// =============================================================================
// Re-exports
// =============================================================================

export * from './types';
export * from './registry';
