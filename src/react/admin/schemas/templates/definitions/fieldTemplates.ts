/**
 * Field Templates
 *
 * Templates for label positioning, description positioning, placeholder text,
 * inset input icons, and input tooltips.
 */

import { registerTemplate } from '../registry';
import type { Template } from '../types';

// Text input compatible types
const TEXT_INPUTS = ['text', 'email', 'url', 'tel', 'number', 'password'];
const ALL_INPUTS = ['text', 'email', 'url', 'tel', 'number', 'password', 'textarea', 'dropdown', 'checkbox', 'radio'];

// ============================================================================
// LABEL POSITION TEMPLATES
// ============================================================================

export const labelTopLeft: Template = registerTemplate({
  id: 'label-top-left',
  name: 'Top Left',
  description: 'Label above input, aligned left',
  category: 'label',
  icon: 'align-left',
  previewLabel: '↖ Top Left',
  priority: 10,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['label-top-center', 'label-top-right', 'label-inline-left', 'label-inline-right', 'label-hidden'],
  mutations: [
    { property: 'labelPosition', value: 'top-left', category: 'appearance' },
  ],
});

export const labelTopCenter: Template = registerTemplate({
  id: 'label-top-center',
  name: 'Top Center',
  description: 'Label above input, centered',
  category: 'label',
  icon: 'align-center',
  previewLabel: '↑ Top Center',
  priority: 20,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['label-top-left', 'label-top-right', 'label-inline-left', 'label-inline-right', 'label-hidden'],
  mutations: [
    { property: 'labelPosition', value: 'top-center', category: 'appearance' },
  ],
});

export const labelTopRight: Template = registerTemplate({
  id: 'label-top-right',
  name: 'Top Right',
  description: 'Label above input, aligned right',
  category: 'label',
  icon: 'align-right',
  previewLabel: '↗ Top Right',
  priority: 30,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['label-top-left', 'label-top-center', 'label-inline-left', 'label-inline-right', 'label-hidden'],
  mutations: [
    { property: 'labelPosition', value: 'top-right', category: 'appearance' },
  ],
});

export const labelInlineLeft: Template = registerTemplate({
  id: 'label-inline-left',
  name: 'Inline Left',
  description: 'Label inline with input, on the left',
  category: 'label',
  icon: 'arrow-right',
  previewLabel: '← Inline',
  priority: 40,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['label-top-left', 'label-top-center', 'label-top-right', 'label-inline-right', 'label-hidden'],
  mutations: [
    { property: 'labelPosition', value: 'inline-left', category: 'appearance' },
  ],
});

export const labelHidden: Template = registerTemplate({
  id: 'label-hidden',
  name: 'Hidden',
  description: 'Hide label visually (still accessible)',
  category: 'label',
  icon: 'eye-off',
  previewLabel: '👁 Hidden',
  priority: 50,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['label-top-left', 'label-top-center', 'label-top-right', 'label-inline-left', 'label-inline-right'],
  mutations: [
    { property: 'labelPosition', value: 'hidden', category: 'appearance' },
  ],
});

// ============================================================================
// DESCRIPTION POSITION TEMPLATES
// ============================================================================

export const descBottomLeft: Template = registerTemplate({
  id: 'desc-bottom-left',
  name: 'Bottom Left',
  description: 'Description below input, aligned left',
  category: 'description',
  icon: 'align-left',
  previewLabel: '↙ Bottom Left',
  priority: 10,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['desc-bottom-center', 'desc-bottom-right', 'desc-top-left', 'desc-hidden'],
  mutations: [
    { property: 'descriptionPosition', value: 'bottom-left', category: 'appearance' },
  ],
});

export const descBottomCenter: Template = registerTemplate({
  id: 'desc-bottom-center',
  name: 'Bottom Center',
  description: 'Description below input, centered',
  category: 'description',
  icon: 'align-center',
  previewLabel: '↓ Bottom Center',
  priority: 20,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['desc-bottom-left', 'desc-bottom-right', 'desc-top-left', 'desc-hidden'],
  mutations: [
    { property: 'descriptionPosition', value: 'bottom-center', category: 'appearance' },
  ],
});

export const descBottomRight: Template = registerTemplate({
  id: 'desc-bottom-right',
  name: 'Bottom Right',
  description: 'Description below input, aligned right',
  category: 'description',
  icon: 'align-right',
  previewLabel: '↘ Bottom Right',
  priority: 30,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['desc-bottom-left', 'desc-bottom-center', 'desc-top-left', 'desc-hidden'],
  mutations: [
    { property: 'descriptionPosition', value: 'bottom-right', category: 'appearance' },
  ],
});

export const descTopLeft: Template = registerTemplate({
  id: 'desc-top-left',
  name: 'Above Input',
  description: 'Description above the input field',
  category: 'description',
  icon: 'arrow-up',
  previewLabel: '↑ Above',
  priority: 40,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['desc-bottom-left', 'desc-bottom-center', 'desc-bottom-right', 'desc-hidden'],
  mutations: [
    { property: 'descriptionPosition', value: 'top-left', category: 'appearance' },
  ],
});

export const descHidden: Template = registerTemplate({
  id: 'desc-hidden',
  name: 'Hidden',
  description: 'Hide description',
  category: 'description',
  icon: 'eye-off',
  previewLabel: '👁 Hidden',
  priority: 50,
  compatibleTypes: ALL_INPUTS,
  conflicts: ['desc-bottom-left', 'desc-bottom-center', 'desc-bottom-right', 'desc-top-left'],
  mutations: [
    { property: 'descriptionPosition', value: 'hidden', category: 'appearance' },
  ],
});

// ============================================================================
// PLACEHOLDER TEMPLATES
// ============================================================================

export const placeholderEnter: Template = registerTemplate({
  id: 'placeholder-enter',
  name: 'Enter...',
  description: 'Generic "Enter your..." placeholder',
  category: 'placeholder',
  icon: 'type',
  previewLabel: 'Enter your...',
  priority: 10,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['placeholder-type', 'placeholder-search', 'placeholder-email', 'placeholder-name'],
  mutations: [
    { property: 'placeholder', value: 'Enter your text here...', category: 'general' },
  ],
});

export const placeholderType: Template = registerTemplate({
  id: 'placeholder-type',
  name: 'Type here',
  description: '"Type here..." placeholder',
  category: 'placeholder',
  icon: 'keyboard',
  previewLabel: 'Type here...',
  priority: 20,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['placeholder-enter', 'placeholder-search', 'placeholder-email', 'placeholder-name'],
  mutations: [
    { property: 'placeholder', value: 'Type here...', category: 'general' },
  ],
});

export const placeholderSearch: Template = registerTemplate({
  id: 'placeholder-search',
  name: 'Search...',
  description: 'Search placeholder',
  category: 'placeholder',
  icon: 'search',
  previewLabel: 'Search...',
  priority: 30,
  compatibleTypes: ['text'],
  conflicts: ['placeholder-enter', 'placeholder-type', 'placeholder-email', 'placeholder-name'],
  mutations: [
    { property: 'placeholder', value: 'Search...', category: 'general' },
  ],
});

export const placeholderEmail: Template = registerTemplate({
  id: 'placeholder-email',
  name: 'Email',
  description: 'Email address placeholder',
  category: 'placeholder',
  icon: 'mail',
  previewLabel: 'name@example.com',
  priority: 40,
  compatibleTypes: ['text', 'email'],
  conflicts: ['placeholder-enter', 'placeholder-type', 'placeholder-search', 'placeholder-name'],
  mutations: [
    { property: 'placeholder', value: 'name@example.com', category: 'general' },
  ],
});

export const placeholderName: Template = registerTemplate({
  id: 'placeholder-name',
  name: 'Full Name',
  description: 'Full name placeholder',
  category: 'placeholder',
  icon: 'user',
  previewLabel: 'John Doe',
  priority: 50,
  compatibleTypes: ['text'],
  conflicts: ['placeholder-enter', 'placeholder-type', 'placeholder-search', 'placeholder-email'],
  mutations: [
    { property: 'placeholder', value: 'John Doe', category: 'general' },
  ],
});

// ============================================================================
// INSET INPUT ICON TEMPLATES
// ============================================================================

export const insetIconSearch: Template = registerTemplate({
  id: 'inset-icon-search',
  name: 'Search',
  description: 'Search icon inside input',
  category: 'inset',
  icon: 'search',
  previewLabel: '🔍 Search',
  priority: 10,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['inset-icon-user', 'inset-icon-mail', 'inset-icon-lock', 'inset-icon-calendar', 'inset-icon-none'],
  mutations: [
    { property: 'inputIcon', value: 'lucide:search', category: 'appearance' },
    { property: 'iconPosition', value: 'left', category: 'appearance' },
  ],
});

export const insetIconUser: Template = registerTemplate({
  id: 'inset-icon-user',
  name: 'User',
  description: 'User icon inside input',
  category: 'inset',
  icon: 'user',
  previewLabel: '👤 User',
  priority: 20,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['inset-icon-search', 'inset-icon-mail', 'inset-icon-lock', 'inset-icon-calendar', 'inset-icon-none'],
  mutations: [
    { property: 'inputIcon', value: 'lucide:user', category: 'appearance' },
    { property: 'iconPosition', value: 'left', category: 'appearance' },
  ],
});

export const insetIconMail: Template = registerTemplate({
  id: 'inset-icon-mail',
  name: 'Email',
  description: 'Email icon inside input',
  category: 'inset',
  icon: 'mail',
  previewLabel: '✉️ Email',
  priority: 30,
  compatibleTypes: ['text', 'email'],
  conflicts: ['inset-icon-search', 'inset-icon-user', 'inset-icon-lock', 'inset-icon-calendar', 'inset-icon-none'],
  mutations: [
    { property: 'inputIcon', value: 'lucide:mail', category: 'appearance' },
    { property: 'iconPosition', value: 'left', category: 'appearance' },
  ],
});

export const insetIconLock: Template = registerTemplate({
  id: 'inset-icon-lock',
  name: 'Lock',
  description: 'Lock icon inside input (for passwords)',
  category: 'inset',
  icon: 'lock',
  previewLabel: '🔒 Lock',
  priority: 40,
  compatibleTypes: ['text', 'password'],
  conflicts: ['inset-icon-search', 'inset-icon-user', 'inset-icon-mail', 'inset-icon-calendar', 'inset-icon-none'],
  mutations: [
    { property: 'inputIcon', value: 'lucide:lock', category: 'appearance' },
    { property: 'iconPosition', value: 'left', category: 'appearance' },
  ],
});

export const insetIconCalendar: Template = registerTemplate({
  id: 'inset-icon-calendar',
  name: 'Calendar',
  description: 'Calendar icon inside input',
  category: 'inset',
  icon: 'calendar',
  previewLabel: '📅 Calendar',
  priority: 50,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['inset-icon-search', 'inset-icon-user', 'inset-icon-mail', 'inset-icon-lock', 'inset-icon-none'],
  mutations: [
    { property: 'inputIcon', value: 'lucide:calendar', category: 'appearance' },
    { property: 'iconPosition', value: 'left', category: 'appearance' },
  ],
});

export const insetIconRight: Template = registerTemplate({
  id: 'inset-icon-right',
  name: 'Icon Right',
  description: 'Position icon on the right side',
  category: 'inset',
  icon: 'arrow-right',
  previewLabel: '→ Right',
  priority: 60,
  compatibleTypes: TEXT_INPUTS,
  conflicts: [],
  mutations: [
    { property: 'iconPosition', value: 'right', category: 'appearance' },
  ],
});

// ============================================================================
// INPUT TOOLTIP TEMPLATES
// ============================================================================

export const tooltipHelp: Template = registerTemplate({
  id: 'tooltip-help',
  name: 'Help Text',
  description: 'Add help tooltip with info icon',
  category: 'tooltip',
  icon: 'help-circle',
  previewLabel: '❓ Help',
  priority: 10,
  compatibleTypes: ['*'],
  conflicts: ['tooltip-info', 'tooltip-warning', 'tooltip-none'],
  mutations: [
    { property: 'helpTooltip', value: 'Click for more information about this field.', category: 'advanced' },
  ],
});

export const tooltipInfo: Template = registerTemplate({
  id: 'tooltip-info',
  name: 'Info Tip',
  description: 'Informational tooltip',
  category: 'tooltip',
  icon: 'info',
  previewLabel: 'ℹ️ Info',
  priority: 20,
  compatibleTypes: ['*'],
  conflicts: ['tooltip-help', 'tooltip-warning', 'tooltip-none'],
  mutations: [
    { property: 'helpTooltip', value: 'This field is used for...', category: 'advanced' },
  ],
});

export const tooltipWarning: Template = registerTemplate({
  id: 'tooltip-warning',
  name: 'Warning Tip',
  description: 'Warning/attention tooltip',
  category: 'tooltip',
  icon: 'alert-triangle',
  previewLabel: '⚠️ Warning',
  priority: 30,
  compatibleTypes: ['*'],
  conflicts: ['tooltip-help', 'tooltip-info', 'tooltip-none'],
  mutations: [
    { property: 'helpTooltip', value: 'Please note: This field requires special attention.', category: 'advanced' },
  ],
});
