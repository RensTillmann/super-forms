/**
 * Input Addon Templates
 *
 * Predefined templates for input field addons following shadcn/ui InputGroup patterns.
 * These provide quick ways to add prefix/suffix text, icons, action buttons, etc.
 */

import { registerTemplate } from '../registry';
import type { Template } from '../types';

// Text input compatible types
const TEXT_INPUTS = ['text', 'email', 'url', 'tel', 'number', 'password'];

// ============================================================================
// PREFIX TEMPLATES
// ============================================================================

export const currencyPrefixTemplate: Template = registerTemplate({
  id: 'prefix-currency-usd',
  name: 'Currency (USD)',
  description: 'Add dollar sign prefix',
  category: 'prefix',
  icon: 'dollar-sign',
  previewLabel: '$',
  priority: 10,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['prefix-currency-eur', 'prefix-currency-gbp', 'prefix-https', 'prefix-at'],
  mutations: [
    { property: 'prefixText', value: '$', category: 'general' },
  ],
});

export const currencyEurTemplate: Template = registerTemplate({
  id: 'prefix-currency-eur',
  name: 'Currency (EUR)',
  description: 'Add euro sign prefix',
  category: 'prefix',
  icon: 'euro',
  previewLabel: '€',
  priority: 11,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['prefix-currency-usd', 'prefix-currency-gbp', 'prefix-https', 'prefix-at'],
  mutations: [
    { property: 'prefixText', value: '€', category: 'general' },
  ],
});

export const currencyGbpTemplate: Template = registerTemplate({
  id: 'prefix-currency-gbp',
  name: 'Currency (GBP)',
  description: 'Add pound sign prefix',
  category: 'prefix',
  icon: 'pound-sterling',
  previewLabel: '£',
  priority: 12,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['prefix-currency-usd', 'prefix-currency-eur', 'prefix-https', 'prefix-at'],
  mutations: [
    { property: 'prefixText', value: '£', category: 'general' },
  ],
});

export const httpsPrefix: Template = registerTemplate({
  id: 'prefix-https',
  name: 'HTTPS Prefix',
  description: 'Add https:// prefix for URLs',
  category: 'prefix',
  icon: 'link',
  previewLabel: 'https://',
  priority: 20,
  compatibleTypes: ['text', 'url'],
  conflicts: ['prefix-currency-usd', 'prefix-currency-eur', 'prefix-currency-gbp', 'prefix-at'],
  mutations: [
    { property: 'prefixText', value: 'https://', category: 'general' },
  ],
});

export const atPrefixTemplate: Template = registerTemplate({
  id: 'prefix-at',
  name: '@ Prefix',
  description: 'Add @ prefix for usernames/handles',
  category: 'prefix',
  icon: 'at-sign',
  previewLabel: '@',
  priority: 30,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['prefix-currency-usd', 'prefix-currency-eur', 'prefix-currency-gbp', 'prefix-https'],
  mutations: [
    { property: 'prefixText', value: '@', category: 'general' },
  ],
});

export const searchIconPrefix: Template = registerTemplate({
  id: 'prefix-icon-search',
  name: 'Search Icon',
  description: 'Add search icon prefix',
  category: 'prefix',
  icon: 'search',
  previewLabel: '🔍',
  priority: 40,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['prefix-icon-user', 'prefix-icon-mail', 'prefix-icon-phone'],
  mutations: [
    { property: 'prefixIcon', value: 'lucide:search', category: 'general' },
  ],
});

export const userIconPrefix: Template = registerTemplate({
  id: 'prefix-icon-user',
  name: 'User Icon',
  description: 'Add user icon prefix',
  category: 'prefix',
  icon: 'user',
  previewLabel: '👤',
  priority: 41,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['prefix-icon-search', 'prefix-icon-mail', 'prefix-icon-phone'],
  mutations: [
    { property: 'prefixIcon', value: 'lucide:user', category: 'general' },
  ],
});

export const mailIconPrefix: Template = registerTemplate({
  id: 'prefix-icon-mail',
  name: 'Email Icon',
  description: 'Add email icon prefix',
  category: 'prefix',
  icon: 'mail',
  previewLabel: '✉️',
  priority: 42,
  compatibleTypes: ['text', 'email'],
  conflicts: ['prefix-icon-search', 'prefix-icon-user', 'prefix-icon-phone'],
  mutations: [
    { property: 'prefixIcon', value: 'lucide:mail', category: 'general' },
  ],
});

export const phoneIconPrefix: Template = registerTemplate({
  id: 'prefix-icon-phone',
  name: 'Phone Icon',
  description: 'Add phone icon prefix',
  category: 'prefix',
  icon: 'phone',
  previewLabel: '📞',
  priority: 43,
  compatibleTypes: ['text', 'tel'],
  conflicts: ['prefix-icon-search', 'prefix-icon-user', 'prefix-icon-mail'],
  mutations: [
    { property: 'prefixIcon', value: 'lucide:phone', category: 'general' },
  ],
});

// ============================================================================
// SUFFIX TEMPLATES
// ============================================================================

export const dotComSuffix: Template = registerTemplate({
  id: 'suffix-dotcom',
  name: '.com Suffix',
  description: 'Add .com domain suffix',
  category: 'suffix',
  icon: 'globe',
  previewLabel: '.com',
  priority: 10,
  compatibleTypes: ['text', 'url'],
  conflicts: ['suffix-dotorg', 'suffix-dotnet', 'suffix-percent', 'suffix-kg'],
  mutations: [
    { property: 'suffixText', value: '.com', category: 'general' },
  ],
});

export const dotOrgSuffix: Template = registerTemplate({
  id: 'suffix-dotorg',
  name: '.org Suffix',
  description: 'Add .org domain suffix',
  category: 'suffix',
  icon: 'globe',
  previewLabel: '.org',
  priority: 11,
  compatibleTypes: ['text', 'url'],
  conflicts: ['suffix-dotcom', 'suffix-dotnet', 'suffix-percent', 'suffix-kg'],
  mutations: [
    { property: 'suffixText', value: '.org', category: 'general' },
  ],
});

export const percentSuffix: Template = registerTemplate({
  id: 'suffix-percent',
  name: 'Percent Suffix',
  description: 'Add % suffix for percentages',
  category: 'suffix',
  icon: 'percent',
  previewLabel: '%',
  priority: 20,
  compatibleTypes: ['text', 'number'],
  conflicts: ['suffix-dotcom', 'suffix-dotorg', 'suffix-kg', 'suffix-lbs'],
  mutations: [
    { property: 'suffixText', value: '%', category: 'general' },
  ],
});

export const kgSuffix: Template = registerTemplate({
  id: 'suffix-kg',
  name: 'Kilograms Suffix',
  description: 'Add kg suffix for weight',
  category: 'suffix',
  icon: 'scale',
  previewLabel: 'kg',
  priority: 30,
  compatibleTypes: ['text', 'number'],
  conflicts: ['suffix-dotcom', 'suffix-dotorg', 'suffix-percent', 'suffix-lbs'],
  mutations: [
    { property: 'suffixText', value: 'kg', category: 'general' },
  ],
});

export const lbsSuffix: Template = registerTemplate({
  id: 'suffix-lbs',
  name: 'Pounds Suffix',
  description: 'Add lbs suffix for weight',
  category: 'suffix',
  icon: 'scale',
  previewLabel: 'lbs',
  priority: 31,
  compatibleTypes: ['text', 'number'],
  conflicts: ['suffix-dotcom', 'suffix-dotorg', 'suffix-percent', 'suffix-kg'],
  mutations: [
    { property: 'suffixText', value: 'lbs', category: 'general' },
  ],
});

// ============================================================================
// ACTION BUTTON TEMPLATES
// ============================================================================

export const copyButtonTemplate: Template = registerTemplate({
  id: 'action-copy',
  name: 'Copy Button',
  description: 'Add copy to clipboard button',
  category: 'action',
  icon: 'copy',
  previewLabel: '📋 Copy',
  priority: 10,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['action-clear', 'action-visibility'],
  mutations: [
    { property: 'actionButton', value: 'copy', category: 'advanced' },
  ],
});

export const clearButtonTemplate: Template = registerTemplate({
  id: 'action-clear',
  name: 'Clear Button',
  description: 'Add clear input button',
  category: 'action',
  icon: 'x',
  previewLabel: '✕ Clear',
  priority: 20,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['action-copy', 'action-visibility'],
  mutations: [
    { property: 'actionButton', value: 'clear', category: 'advanced' },
  ],
});

export const visibilityToggleTemplate: Template = registerTemplate({
  id: 'action-visibility',
  name: 'Visibility Toggle',
  description: 'Add show/hide password toggle',
  category: 'action',
  icon: 'eye',
  previewLabel: '👁️ Show/Hide',
  priority: 30,
  compatibleTypes: ['text', 'password'],
  conflicts: ['action-copy', 'action-clear'],
  mutations: [
    { property: 'actionButton', value: 'toggle-visibility', category: 'advanced' },
  ],
});

// ============================================================================
// FEEDBACK TEMPLATES
// ============================================================================

export const characterCountInline: Template = registerTemplate({
  id: 'feedback-charcount-inline',
  name: 'Character Count (Inline)',
  description: 'Show character count inside input',
  category: 'feedback',
  icon: 'hash',
  previewLabel: '0/100',
  priority: 10,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['feedback-charcount-below'],
  mutations: [
    { property: 'showCharacterCount', value: true, category: 'advanced' },
    { property: 'characterCountPosition', value: 'inline-end', category: 'advanced' },
  ],
});

export const characterCountBelow: Template = registerTemplate({
  id: 'feedback-charcount-below',
  name: 'Character Count (Below)',
  description: 'Show character count below input',
  category: 'feedback',
  icon: 'hash',
  previewLabel: '0/100 ↓',
  priority: 11,
  compatibleTypes: TEXT_INPUTS,
  conflicts: ['feedback-charcount-inline'],
  mutations: [
    { property: 'showCharacterCount', value: true, category: 'advanced' },
    { property: 'characterCountPosition', value: 'block-end', category: 'advanced' },
  ],
});

