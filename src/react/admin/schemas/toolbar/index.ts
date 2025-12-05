/**
 * Schema-First Form Builder - Toolbar Schemas
 *
 * Default toolbar item registrations for the form builder.
 * Import this module to register all standard toolbar items.
 */

import { registerToolbarItem } from './registry';

// =============================================================================
// LEFT GROUP - Form selector, title, device controls
// =============================================================================

/**
 * Form selector dropdown - Switch between forms
 */
export const FormSelectorItem = registerToolbarItem({
  id: 'form-selector',
  type: 'custom',
  group: 'left',
  tooltip: 'Select Form',
  position: 0,
  component: 'FormSelector',
  showLabel: false,
});

/**
 * Form title - Inline editable form name
 */
export const FormTitleItem = registerToolbarItem({
  id: 'form-title',
  type: 'custom',
  group: 'left',
  tooltip: 'Edit Form Title',
  position: 10,
  component: 'InlineEditableText',
  showLabel: false,
});

/**
 * Device selector dropdown - Desktop/Tablet/Mobile preview
 */
export const DeviceSelectorItem = registerToolbarItem({
  id: 'device-selector',
  type: 'dropdown',
  group: 'left',
  icon: 'Monitor',
  tooltip: 'Device Preview',
  position: 20,
  options: [
    { value: 'desktop', label: 'Desktop', icon: 'Monitor' },
    { value: 'tablet', label: 'Tablet', icon: 'Tablet' },
    { value: 'mobile', label: 'Mobile', icon: 'Smartphone' },
  ],
  showLabel: true,
});

/**
 * Device frame toggle - Show/hide device frame
 */
export const DeviceFrameToggleItem = registerToolbarItem({
  id: 'device-frame-toggle',
  type: 'toggle',
  group: 'left',
  icon: 'Maximize',
  tooltip: 'Toggle Device Frame',
  position: 30,
  showLabel: false,
});

// =============================================================================
// HISTORY GROUP - Undo/Redo
// =============================================================================

/**
 * Undo button
 */
export const UndoItem = registerToolbarItem({
  id: 'undo',
  type: 'button',
  group: 'history',
  icon: 'RotateCcw',
  tooltip: 'Undo',
  shortcut: 'Ctrl+Z',
  position: 0,
  showLabel: false,
});

/**
 * Redo button
 */
export const RedoItem = registerToolbarItem({
  id: 'redo',
  type: 'button',
  group: 'history',
  icon: 'RefreshCw',
  tooltip: 'Redo',
  shortcut: 'Ctrl+Shift+Z',
  position: 10,
  showLabel: false,
});

// =============================================================================
// CANVAS GROUP - Grid, Zoom
// =============================================================================

/**
 * Toggle grid overlay
 */
export const ToggleGridItem = registerToolbarItem({
  id: 'toggle-grid',
  type: 'toggle',
  group: 'canvas',
  icon: 'Grid',
  tooltip: 'Toggle Grid',
  position: 0,
  showLabel: false,
});

/**
 * Zoom controls - Custom component
 */
export const ZoomItem = registerToolbarItem({
  id: 'zoom',
  type: 'custom',
  group: 'canvas',
  tooltip: 'Zoom Controls',
  position: 10,
  component: 'ZoomControls',
  showLabel: false,
});

// =============================================================================
// PANELS GROUP - Panel toggles and utility buttons
// =============================================================================

/**
 * Elements panel toggle
 */
export const ElementsPanelItem = registerToolbarItem({
  id: 'elements-panel',
  type: 'toggle',
  group: 'panels',
  icon: 'Layers',
  tooltip: 'Elements Panel',
  position: 0,
  showLabel: false,
});

/**
 * Version history button
 */
export const VersionHistoryItem = registerToolbarItem({
  id: 'version-history',
  type: 'button',
  group: 'panels',
  icon: 'History',
  tooltip: 'Version History',
  position: 10,
  showLabel: false,
});

/**
 * Share button
 */
export const ShareItem = registerToolbarItem({
  id: 'share',
  type: 'button',
  group: 'panels',
  icon: 'Share',
  tooltip: 'Share & Collaborate',
  position: 20,
  showLabel: false,
});

/**
 * Export button
 */
export const ExportItem = registerToolbarItem({
  id: 'export',
  type: 'button',
  group: 'panels',
  icon: 'Download',
  tooltip: 'Export Form',
  position: 30,
  showLabel: false,
});

/**
 * Analytics button
 */
export const AnalyticsItem = registerToolbarItem({
  id: 'analytics',
  type: 'button',
  group: 'panels',
  icon: 'BarChart',
  tooltip: 'Analytics',
  position: 40,
  showLabel: false,
});

// =============================================================================
// PRIMARY GROUP - Main actions
// =============================================================================

/**
 * Preview button
 */
export const PreviewItem = registerToolbarItem({
  id: 'preview',
  type: 'button',
  group: 'primary',
  icon: 'Eye',
  label: 'Preview',
  tooltip: 'Preview Form',
  variant: 'secondary',
  position: 0,
  showLabel: true,
  hiddenOnMobile: true,
});

/**
 * Save button
 */
export const SaveItem = registerToolbarItem({
  id: 'save',
  type: 'button',
  group: 'primary',
  icon: 'Save',
  label: 'Save',
  tooltip: 'Save Form',
  variant: 'save',
  position: 10,
  showLabel: true,
  hiddenOnMobile: true,
});

/**
 * Publish button
 */
export const PublishItem = registerToolbarItem({
  id: 'publish',
  type: 'button',
  group: 'primary',
  icon: 'Send',
  label: 'Publish',
  tooltip: 'Publish Form',
  variant: 'publish',
  position: 20,
  showLabel: true,
  hiddenOnMobile: true,
});

// =============================================================================
// Re-exports
// =============================================================================

export * from './types';
export * from './registry';
