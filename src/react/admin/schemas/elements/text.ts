import { registerElement, withBaseProperties } from '../core/registry';

/**
 * Text Input Element Schema
 *
 * Single-line text input field for names, titles, short answers, etc.
 */
export const TextElementSchema = registerElement({
  type: 'text',
  name: 'Text Input',
  description: 'Single-line text input field for short text entries',
  category: 'basic',
  icon: 'type',
  container: null,

  properties: withBaseProperties({
    general: {
      placeholder: {
        type: 'string',
        label: 'Placeholder',
        description: 'Hint text shown when field is empty',
        translatable: true,
        supportsTags: true,
      },
      defaultValue: {
        type: 'string',
        label: 'Default Value',
        description: 'Pre-filled value for the field',
        supportsTags: true,
      },
      prefixText: {
        type: 'string',
        label: 'Prefix Text',
        description: 'Text shown before the input (e.g., $, €, https://)',
      },
      suffixText: {
        type: 'string',
        label: 'Suffix Text',
        description: 'Text shown after the input (e.g., USD, .com, kg)',
      },
      prefixIcon: {
        type: 'icon',
        label: 'Prefix Icon',
        description: 'Icon shown before the input',
      },
      suffixIcon: {
        type: 'icon',
        label: 'Suffix Icon',
        description: 'Icon shown after the input',
      },
    },
    validation: {
      required: {
        type: 'boolean',
        label: 'Required',
        description: 'User must fill this field to submit',
        default: false,
      },
      minLength: {
        type: 'number',
        label: 'Minimum Length',
        description: 'Minimum number of characters',
        min: 0,
      },
      maxLength: {
        type: 'number',
        label: 'Maximum Length',
        description: 'Maximum number of characters',
        min: 1,
      },
      pattern: {
        type: 'string',
        label: 'Pattern (Regex)',
        description: 'Regular expression for validation',
      },
      customError: {
        type: 'string',
        label: 'Custom Error Message',
        description: 'Error message shown when validation fails',
        translatable: true,
      },
    },
    appearance: {
      labelPosition: {
        type: 'position_picker',
        label: 'Label Position',
        description: 'Position of the label relative to the input',
        default: 'top-left',
        targets: ['label', 'fieldContainer'],
      },
      descriptionPosition: {
        type: 'position_picker',
        label: 'Description Position',
        description: 'Position of the description relative to the input',
        default: 'bottom-left',
        targets: ['description', 'fieldContainer'],
      },
      inputIcon: {
        type: 'icon',
        label: 'Input Icon',
        description: 'Icon shown inside the input',
        targets: ['input'],
      },
      iconPosition: {
        type: 'select',
        label: 'Icon Position',
        options: [
          { value: 'left', label: 'Left' },
          { value: 'right', label: 'Right' },
        ],
        default: 'left',
        targets: ['input'],
        conditions: [
          { property: 'inputIcon', operator: 'not_empty' },
        ],
      },
    },
    advanced: {
      autocomplete: {
        type: 'select',
        label: 'Autocomplete',
        description: 'Browser autocomplete hint',
        options: [
          { value: 'off', label: 'Off' },
          { value: 'on', label: 'On' },
          { value: 'name', label: 'Full Name' },
          { value: 'given-name', label: 'First Name' },
          { value: 'family-name', label: 'Last Name' },
          { value: 'organization', label: 'Organization' },
          { value: 'street-address', label: 'Street Address' },
          { value: 'postal-code', label: 'Postal Code' },
        ],
        default: 'off',
      },
      readOnly: {
        type: 'boolean',
        label: 'Read Only',
        description: 'Field cannot be edited by user',
        default: false,
      },
      showCharacterCount: {
        type: 'boolean',
        label: 'Show Character Counter',
        description: 'Display character count (requires Max Length)',
        default: false,
      },
      characterCountPosition: {
        type: 'select',
        label: 'Counter Position',
        description: 'Where to display the character counter',
        options: [
          { value: 'inline-end', label: 'Inside Input (Right)' },
          { value: 'block-end', label: 'Below Input' },
        ],
        default: 'block-end',
        conditions: [
          { property: 'showCharacterCount', operator: 'equals', value: true },
        ],
      },
      actionButton: {
        type: 'select',
        label: 'Action Button',
        description: 'Button shown at end of input',
        options: [
          { value: 'none', label: 'None' },
          { value: 'copy', label: 'Copy to Clipboard' },
          { value: 'clear', label: 'Clear Input' },
          { value: 'toggle-visibility', label: 'Toggle Visibility' },
        ],
        default: 'none',
      },
      helpTooltip: {
        type: 'string',
        label: 'Help Tooltip',
        description: 'Help text shown in tooltip next to label',
        translatable: true,
      },
    },
  }),

  defaults: {
    label: 'Text Field',
    name: '',
    width: 'full',
    required: false,
  },

  translatable: ['label', 'placeholder', 'customError'],
  supportsTags: ['placeholder', 'defaultValue'],
});
