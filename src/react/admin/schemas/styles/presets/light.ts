import type { NodeType, StyleProperties } from '../types';

/**
 * Light Theme Preset
 * Clean, professional look with subtle grays
 */
export const LIGHT_PRESET: Record<NodeType, Partial<StyleProperties>> = {
  label: {
    fontSize: 14,
    fontFamily: 'inherit',
    fontWeight: '500',
    lineHeight: 1.4,
    color: '#1f2937',
    margin: { top: 0, right: 0, bottom: 4, left: 0 },
  },
  description: {
    fontSize: 13,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    lineHeight: 1.4,
    color: '#6b7280',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  input: {
    fontSize: 14,
    fontFamily: 'inherit',
    textAlign: 'left',
    color: '#1f2937',
    padding: { top: 10, right: 14, bottom: 10, left: 14 },
    border: { top: 1, right: 1, bottom: 1, left: 1 },
    borderStyle: 'solid',
    borderColor: '#d1d5db',
    borderRadius: 6,
    backgroundColor: '#ffffff',
    width: '100%',
    minHeight: 42,
  },
  placeholder: {
    fontStyle: 'normal',
    color: '#9ca3af',
  },
  error: {
    fontSize: 13,
    fontWeight: '500',
    color: '#dc2626',
    margin: { top: 4, right: 0, bottom: 0, left: 0 },
  },
  required: {
    fontSize: 14,
    fontWeight: '400',
    color: '#dc2626',
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
    color: '#111827',
    margin: { top: 0, right: 0, bottom: 12, left: 0 },
  },
  paragraph: {
    fontSize: 15,
    fontFamily: 'inherit',
    fontStyle: 'normal',
    textAlign: 'left',
    textDecoration: 'none',
    lineHeight: 1.6,
    color: '#4b5563',
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
    backgroundColor: '#2563eb',
    minHeight: 44,
  },
  divider: {
    color: '#e5e7eb',
    margin: { top: 20, right: 0, bottom: 20, left: 0 },
    border: { top: 1, right: 0, bottom: 0, left: 0 },
    width: '100%',
  },
  optionLabel: {
    fontSize: 14,
    fontFamily: 'inherit',
    lineHeight: 1.4,
    color: '#374151',
  },
  cardContainer: {
    margin: { top: 0, right: 8, bottom: 8, left: 0 },
    padding: { top: 16, right: 16, bottom: 16, left: 16 },
    border: { top: 2, right: 2, bottom: 2, left: 2 },
    borderRadius: 8,
    backgroundColor: '#ffffff',
    width: 'auto',
    minHeight: 80,
  },
};

export const LIGHT_PRESET_META = {
  id: 'light',
  name: 'Light',
  description: 'Clean, professional look with subtle grays',
};
