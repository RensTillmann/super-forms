import type { NodeType, StyleProperties } from '../types';

/**
 * Dark Theme Preset
 * Modern dark mode with good contrast
 */
export const DARK_PRESET: Record<NodeType, Partial<StyleProperties>> = {
  label: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontWeight: '500',
    lineHeight: 1.4,
    color: '#f3f4f6',
    margin: { top: 0, right: 0, bottom: 4, left: 0 },
  },
  description: {
    fontSize: 13,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    lineHeight: 1.4,
    color: '#9ca3af',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  input: {
    fontSize: 14,
    fontFamily: 'inherit',
    textAlign: 'left',
    color: '#f9fafb',
    padding: { top: 10, right: 14, bottom: 10, left: 14 },
    border: { top: 1, right: 1, bottom: 1, left: 1 },
    borderStyle: 'solid',
    borderColor: '#4b5563',
    borderRadius: 6,
    backgroundColor: '#1f2937',
    width: '100%',
    minHeight: 42,
  },
  placeholder: {
    fontStyle: 'normal',
    color: '#6b7280',
  },
  error: {
    fontSize: 13,
    fontWeight: '500',
    color: '#f87171',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  required: {
    fontSize: 14,
    fontWeight: '400',
    color: '#f87171',
  },
  fieldContainer: {
    margin: { top: 0, right: 0, bottom: 20, left: 0 },
    padding: { top: 0, right: 0, bottom: 0, left: 0 },
    border: { top: 0, right: 0, bottom: 0, left: 0 },
    borderRadius: 0,
    backgroundColor: 'transparent',
    width: '100%',
  },
  heading: {
    fontSize: 24,
    fontFamily: 'inherit',
    fontWeight: '600',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.3,
    letterSpacing: 0,
    color: '#f9fafb',
    margin: { top: 0, right: 0, bottom: 12, left: 0 },
  },
  paragraph: {
    fontSize: 15,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.6,
    color: '#d1d5db',
    margin: { top: 0, right: 0, bottom: 16, left: 0 },
  },
  button: {
    fontSize: 15,
    fontFamily: 'inherit',
    fontWeight: '500',
    textAlign: 'center',
    letterSpacing: 0,
    color: '#ffffff',
    margin: { top: 12, right: 0, bottom: 0, left: 0 },
    padding: { top: 12, right: 24, bottom: 12, left: 24 },
    border: { top: 0, right: 0, bottom: 0, left: 0 },
    borderRadius: 6,
    backgroundColor: '#3b82f6',
    minHeight: 44,
  },
  divider: {
    color: '#374151',
    margin: { top: 20, right: 0, bottom: 20, left: 0 },
    border: { top: 1, right: 0, bottom: 0, left: 0 },
    width: '100%',
  },
  optionLabel: {
    fontSize: 14,
    fontFamily: 'inherit',
    lineHeight: 1.4,
    color: '#e5e7eb',
  },
  cardContainer: {
    margin: { top: 0, right: 8, bottom: 8, left: 0 },
    padding: { top: 16, right: 16, bottom: 16, left: 16 },
    border: { top: 2, right: 2, bottom: 2, left: 2 },
    borderRadius: 8,
    backgroundColor: '#1f2937',
    width: 'auto',
    minHeight: 80,
  },
};

export const DARK_PRESET_META = {
  id: 'dark',
  name: 'Dark',
  description: 'Modern dark mode with good contrast',
};
