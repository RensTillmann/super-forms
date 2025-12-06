import type { NodeType, StyleProperties } from '../types';
import { styleRegistry } from '../registry';
import { LIGHT_PRESET, LIGHT_PRESET_META } from './light';
import { DARK_PRESET, DARK_PRESET_META } from './dark';

export interface ThemePreset {
  id: string;
  name: string;
  description: string;
  styles: Record<NodeType, Partial<StyleProperties>>;
}

export const THEME_PRESETS: ThemePreset[] = [
  { ...LIGHT_PRESET_META, styles: LIGHT_PRESET },
  { ...DARK_PRESET_META, styles: DARK_PRESET },
];

/**
 * Apply a preset to the global style registry.
 */
export function applyPreset(presetId: string): boolean {
  const preset = THEME_PRESETS.find((p) => p.id === presetId);
  if (!preset) return false;

  // Apply all styles from preset
  Object.entries(preset.styles).forEach(([nodeType, styles]) => {
    styleRegistry.setGlobalStyle(nodeType as NodeType, styles);
  });

  return true;
}

/**
 * Get a preset by ID.
 */
export function getPreset(presetId: string): ThemePreset | undefined {
  return THEME_PRESETS.find((p) => p.id === presetId);
}

/**
 * List all available presets.
 */
export function listPresets(): ThemePreset[] {
  return THEME_PRESETS;
}

// Re-export individual presets
export { LIGHT_PRESET, LIGHT_PRESET_META } from './light';
export { DARK_PRESET, DARK_PRESET_META } from './dark';
