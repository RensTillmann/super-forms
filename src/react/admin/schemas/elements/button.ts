import { registerElement, withBaseProperties } from '../core/registry';

/**
 * Button Element Schema
 *
 * Action buttons that can trigger automations, navigate wizards, show overlays,
 * submit forms, and more. Each button can have independent behavior.
 *
 * Action Types:
 * - submit: Standard form submission
 * - save_state: Save draft/partial submission
 * - reset: Clear form fields
 * - navigate: Multi-step wizard navigation
 * - trigger_automation: Fire custom event for automation
 * - open_overlay: Open modal/drawer/popup
 * - toggle_visibility: Show/hide other elements
 * - copy_to_clipboard: Copy content to clipboard
 */
export const ButtonElementSchema = registerElement({
  type: 'button',
  name: 'Button',
  description: 'Action button that triggers automations, navigation, or other behaviors',
  category: 'basic',
  icon: 'mouse-pointer-click',
  container: null,

  properties: withBaseProperties({
    general: {
      buttonText: {
        type: 'string',
        label: 'Button Text',
        description: 'Text displayed on the button',
        translatable: true,
        supportsTags: true,
        default: 'Submit',
      },
      actionType: {
        type: 'select',
        label: 'Action Type',
        description: 'What happens when the button is clicked',
        options: [
          { value: 'submit', label: 'Submit Form' },
          { value: 'save_state', label: 'Save Draft' },
          { value: 'reset', label: 'Reset Form' },
          { value: 'navigate', label: 'Navigate (Wizard)' },
          { value: 'trigger_automation', label: 'Trigger Automation' },
          { value: 'open_overlay', label: 'Open Modal/Drawer' },
          { value: 'toggle_visibility', label: 'Show/Hide Elements' },
          { value: 'copy_to_clipboard', label: 'Copy to Clipboard' },
        ],
        default: 'submit',
      },
      // === Automation Trigger Properties ===
      eventId: {
        type: 'string',
        label: 'Event ID',
        description: 'Custom event identifier for automation binding (e.g., "generate_pdf")',
        pattern: '^[a-z][a-z0-9_]*$',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'trigger_automation' },
        ],
      },
      // === Save State Properties ===
      saveState: {
        type: 'select',
        label: 'Save As',
        description: 'State to save the form entry as',
        options: [
          { value: 'draft', label: 'Draft' },
          { value: 'pending_review', label: 'Pending Review' },
          { value: 'incomplete', label: 'Incomplete' },
        ],
        default: 'draft',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'save_state' },
        ],
      },
      // === Navigation Properties ===
      navigateDirection: {
        type: 'select',
        label: 'Navigate To',
        description: 'Direction to navigate in multi-step form',
        options: [
          { value: 'next', label: 'Next Step' },
          { value: 'previous', label: 'Previous Step' },
          { value: 'first', label: 'First Step' },
          { value: 'last', label: 'Last Step' },
          { value: 'specific', label: 'Specific Step' },
        ],
        default: 'next',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'navigate' },
        ],
      },
      targetStep: {
        type: 'string',
        label: 'Target Step',
        description: 'Step number or ID to navigate to',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'navigate' },
          { property: 'navigateDirection', operator: 'equals', value: 'specific' },
        ],
      },
      // === Overlay Properties ===
      overlayType: {
        type: 'select',
        label: 'Overlay Type',
        description: 'Type of overlay to open',
        options: [
          { value: 'modal', label: 'Modal (Centered)' },
          { value: 'dialog', label: 'Dialog (Alert)' },
          { value: 'drawer', label: 'Drawer (Side)' },
          { value: 'tray', label: 'Tray (Bottom)' },
          { value: 'sheet', label: 'Sheet (Full Height)' },
          { value: 'popup', label: 'Popup (Small)' },
        ],
        default: 'modal',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'open_overlay' },
        ],
      },
      overlayTitle: {
        type: 'string',
        label: 'Overlay Title',
        description: 'Title shown in the overlay header',
        translatable: true,
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'open_overlay' },
        ],
      },
      overlayContent: {
        type: 'rich_text',
        label: 'Overlay Content',
        description: 'Content displayed inside the overlay',
        translatable: true,
        supportsTags: true,
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'open_overlay' },
        ],
      },
      overlaySize: {
        type: 'select',
        label: 'Overlay Size',
        options: [
          { value: 'sm', label: 'Small' },
          { value: 'md', label: 'Medium' },
          { value: 'lg', label: 'Large' },
          { value: 'xl', label: 'Extra Large' },
          { value: 'fullscreen', label: 'Fullscreen' },
        ],
        default: 'md',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'open_overlay' },
        ],
      },
      drawerPosition: {
        type: 'select',
        label: 'Drawer Position',
        options: [
          { value: 'left', label: 'Left' },
          { value: 'right', label: 'Right' },
          { value: 'top', label: 'Top' },
          { value: 'bottom', label: 'Bottom' },
        ],
        default: 'right',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'open_overlay' },
          { property: 'overlayType', operator: 'contains', value: 'drawer' },
        ],
      },
      // === Toggle Visibility Properties ===
      targetElements: {
        type: 'tag_input',
        label: 'Target Elements',
        description: 'Element IDs to show/hide (comma-separated)',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'toggle_visibility' },
        ],
      },
      visibilityAction: {
        type: 'select',
        label: 'Visibility Action',
        options: [
          { value: 'toggle', label: 'Toggle' },
          { value: 'show', label: 'Show' },
          { value: 'hide', label: 'Hide' },
        ],
        default: 'toggle',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'toggle_visibility' },
        ],
      },
      // === Copy to Clipboard Properties ===
      copySource: {
        type: 'select',
        label: 'Copy Source',
        description: 'Where to get content to copy',
        options: [
          { value: 'static', label: 'Static Text' },
          { value: 'field', label: 'Field Value' },
          { value: 'template', label: 'Template with Tags' },
        ],
        default: 'static',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'copy_to_clipboard' },
        ],
      },
      copyContent: {
        type: 'string',
        label: 'Content to Copy',
        description: 'Static text to copy to clipboard',
        supportsTags: true,
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'copy_to_clipboard' },
          { property: 'copySource', operator: 'not_equals', value: 'field' },
        ],
      },
      sourceField: {
        type: 'string',
        label: 'Source Field',
        description: 'Field name to copy value from',
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'copy_to_clipboard' },
          { property: 'copySource', operator: 'equals', value: 'field' },
        ],
      },
      // === Icon Configuration ===
      icon: {
        type: 'icon',
        label: 'Icon',
        description: 'Icon shown on the button',
      },
      iconPosition: {
        type: 'select',
        label: 'Icon Position',
        options: [
          { value: 'left', label: 'Left' },
          { value: 'right', label: 'Right' },
        ],
        default: 'left',
        conditions: [
          { property: 'icon', operator: 'not_empty' },
        ],
      },
    },
    validation: {
      validateBeforeAction: {
        type: 'boolean',
        label: 'Validate Form First',
        description: 'Validate required fields before executing action',
        default: true,
        conditions: [
          { property: 'actionType', operator: 'not_equals', value: 'reset' },
        ],
      },
      disabledUntilValid: {
        type: 'boolean',
        label: 'Disable Until Valid',
        description: 'Button is disabled until all required fields are valid',
        default: false,
      },
    },
    appearance: {
      variant: {
        type: 'select',
        label: 'Button Style',
        description: 'Visual style of the button',
        options: [
          { value: 'primary', label: 'Primary' },
          { value: 'secondary', label: 'Secondary' },
          { value: 'outline', label: 'Outline' },
          { value: 'ghost', label: 'Ghost' },
          { value: 'link', label: 'Link' },
          { value: 'destructive', label: 'Destructive' },
        ],
        default: 'primary',
        targets: ['button'],
      },
      size: {
        type: 'select',
        label: 'Size',
        options: [
          { value: 'xs', label: 'Extra Small' },
          { value: 'sm', label: 'Small' },
          { value: 'md', label: 'Medium' },
          { value: 'lg', label: 'Large' },
          { value: 'xl', label: 'Extra Large' },
        ],
        default: 'md',
        targets: ['button'],
      },
      fullWidth: {
        type: 'boolean',
        label: 'Full Width',
        description: 'Button spans full container width',
        default: false,
        targets: ['button'],
      },
      alignment: {
        type: 'select',
        label: 'Alignment',
        description: 'Horizontal alignment of the button',
        options: [
          { value: 'left', label: 'Left' },
          { value: 'center', label: 'Center' },
          { value: 'right', label: 'Right' },
        ],
        default: 'left',
        conditions: [
          { property: 'fullWidth', operator: 'equals', value: false },
        ],
      },
    },
    advanced: {
      loadingText: {
        type: 'string',
        label: 'Loading Text',
        description: 'Text shown while action is executing',
        translatable: true,
        conditions: [
          { property: 'actionType', operator: 'not_equals', value: 'reset' },
        ],
      },
      successText: {
        type: 'string',
        label: 'Success Text',
        description: 'Button text after successful action',
        translatable: true,
        conditions: [
          { property: 'actionType', operator: 'not_equals', value: 'submit' },
        ],
      },
      successDuration: {
        type: 'number',
        label: 'Success Duration (ms)',
        description: 'How long to show success state',
        default: 2000,
        min: 500,
        max: 10000,
        conditions: [
          { property: 'successText', operator: 'not_empty' },
        ],
      },
      confirmBeforeAction: {
        type: 'boolean',
        label: 'Confirm Before Action',
        description: 'Show confirmation dialog before executing',
        default: false,
      },
      confirmationMessage: {
        type: 'string',
        label: 'Confirmation Message',
        description: 'Message shown in confirmation dialog',
        translatable: true,
        default: 'Are you sure?',
        conditions: [
          { property: 'confirmBeforeAction', operator: 'equals', value: true },
        ],
      },
      successMessage: {
        type: 'string',
        label: 'Success Message',
        description: 'Toast message shown on success',
        translatable: true,
        supportsTags: true,
      },
      errorMessage: {
        type: 'string',
        label: 'Error Message',
        description: 'Toast message shown on error',
        translatable: true,
        supportsTags: true,
      },
      awaitResponse: {
        type: 'boolean',
        label: 'Wait for Response',
        description: 'Wait for automation to complete before showing success',
        default: true,
        conditions: [
          { property: 'actionType', operator: 'equals', value: 'trigger_automation' },
        ],
      },
      testId: {
        type: 'string',
        label: 'Test ID',
        description: 'data-testid attribute for testing',
      },
    },
  }),

  defaults: {
    label: '',
    name: '',
    width: 'auto',
    buttonText: 'Submit',
    actionType: 'submit',
    variant: 'primary',
    size: 'md',
    validateBeforeAction: true,
    hideLabel: true,
  },

  translatable: ['buttonText', 'loadingText', 'successText', 'confirmationMessage', 'successMessage', 'errorMessage', 'overlayTitle', 'overlayContent'],
  supportsTags: ['buttonText', 'copyContent', 'successMessage', 'errorMessage', 'overlayContent'],
});
