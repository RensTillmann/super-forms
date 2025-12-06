/**
 * Theme Generator
 *
 * Generates complete theme styles from configuration options.
 * Uses color theory to derive full palette from a base color.
 */

import type { NodeType, StyleProperties } from '../schemas/styles';

// =============================================================================
// Types
// =============================================================================

export interface GenerateThemeOptions {
  baseColor?: string;
  prompt?: string;
  density?: 'compact' | 'comfortable' | 'spacious';
  cornerStyle?: 'sharp' | 'rounded' | 'pill';
  contrastPreference?: 'high' | 'standard' | 'soft';
}

interface ColorPalette {
  primary: string;
  primaryHover: string;
  primaryContrast: string;
  background: string;
  surface: string;
  text: string;
  textMuted: string;
  border: string;
  error: string;
  success: string;
}

// =============================================================================
// Color Utilities (no external dependencies)
// =============================================================================

function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result
    ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
      }
    : null;
}

function rgbToHex(r: number, g: number, b: number): string {
  return (
    '#' +
    [r, g, b]
      .map((x) => {
        const hex = Math.max(0, Math.min(255, Math.round(x))).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
      })
      .join('')
  );
}

function getLuminance(hex: string): number {
  const rgb = hexToRgb(hex);
  if (!rgb) return 0;
  const { r, g, b } = rgb;
  return (0.299 * r + 0.587 * g + 0.114 * b) / 255;
}

function isDark(hex: string): boolean {
  return getLuminance(hex) < 0.5;
}

function darken(hex: string, amount: number): string {
  const rgb = hexToRgb(hex);
  if (!rgb) return hex;
  return rgbToHex(
    rgb.r * (1 - amount),
    rgb.g * (1 - amount),
    rgb.b * (1 - amount)
  );
}

// =============================================================================
// Palette Generation
// =============================================================================

function generatePalette(baseColor: string): ColorPalette {
  const dark = isDark(baseColor);

  return {
    primary: baseColor,
    primaryHover: darken(baseColor, 0.1),
    primaryContrast: dark ? '#ffffff' : '#000000',
    background: dark ? '#1f2937' : '#ffffff',
    surface: dark ? '#374151' : '#f9fafb',
    text: dark ? '#f9fafb' : '#1f2937',
    textMuted: dark ? '#9ca3af' : '#6b7280',
    border: dark ? '#4b5563' : '#d1d5db',
    error: '#dc2626',
    success: '#16a34a',
  };
}

// =============================================================================
// Theme Generation
// =============================================================================

export function generateThemeFromOptions(
  options: GenerateThemeOptions
): Record<NodeType, Partial<StyleProperties>> {
  const {
    baseColor = '#2563eb',
    density = 'comfortable',
    cornerStyle = 'rounded',
    contrastPreference = 'standard',
  } = options;

  const palette = generatePalette(baseColor);

  // Density modifiers
  const densityScale = {
    compact: { padding: 0.75, margin: 0.75, fontSize: 0.9 },
    comfortable: { padding: 1, margin: 1, fontSize: 1 },
    spacious: { padding: 1.5, margin: 1.25, fontSize: 1.1 },
  }[density];

  // Border radius based on corner style
  const borderRadius = {
    sharp: 0,
    rounded: 6,
    pill: 999,
  }[cornerStyle];

  // Contrast adjustments
  const contrastMod = {
    high: { borderWidth: 2, fontWeight: '600' as const },
    standard: { borderWidth: 1, fontWeight: '500' as const },
    soft: { borderWidth: 1, fontWeight: '400' as const },
  }[contrastPreference];

  // Base spacing values
  const basePadding = { vertical: 10, horizontal: 14 };
  const baseMargin = 4;
  const baseFontSize = 14;

  return {
    label: {
      fontSize: Math.round(baseFontSize * densityScale.fontSize),
      fontWeight: contrastMod.fontWeight,
      color: palette.text,
      margin: {
        top: 0,
        right: 0,
        bottom: Math.round(baseMargin * densityScale.margin),
        left: 0,
      },
    },
    description: {
      fontSize: Math.round(12 * densityScale.fontSize),
      fontWeight: '400',
      color: palette.textMuted,
      margin: {
        top: Math.round(baseMargin * densityScale.margin),
        right: 0,
        bottom: 0,
        left: 0,
      },
    },
    input: {
      fontSize: Math.round(baseFontSize * densityScale.fontSize),
      color: palette.text,
      backgroundColor: palette.background,
      borderColor: palette.border,
      borderRadius,
      borderStyle: 'solid',
      border: {
        top: contrastMod.borderWidth,
        right: contrastMod.borderWidth,
        bottom: contrastMod.borderWidth,
        left: contrastMod.borderWidth,
      },
      padding: {
        top: Math.round(basePadding.vertical * densityScale.padding),
        right: Math.round(basePadding.horizontal * densityScale.padding),
        bottom: Math.round(basePadding.vertical * densityScale.padding),
        left: Math.round(basePadding.horizontal * densityScale.padding),
      },
    },
    placeholder: {
      color: palette.textMuted,
      fontStyle: 'normal',
    },
    error: {
      fontSize: Math.round(12 * densityScale.fontSize),
      color: palette.error,
      margin: {
        top: Math.round(baseMargin * densityScale.margin),
        right: 0,
        bottom: 0,
        left: 0,
      },
    },
    required: {
      color: palette.error,
      fontSize: Math.round(baseFontSize * densityScale.fontSize),
    },
    fieldContainer: {
      margin: {
        top: 0,
        right: 0,
        bottom: Math.round(16 * densityScale.margin),
        left: 0,
      },
    },
    heading: {
      fontSize: Math.round(24 * densityScale.fontSize),
      fontWeight: '600',
      color: palette.text,
      margin: {
        top: 0,
        right: 0,
        bottom: Math.round(8 * densityScale.margin),
        left: 0,
      },
    },
    paragraph: {
      fontSize: Math.round(baseFontSize * densityScale.fontSize),
      color: palette.text,
      lineHeight: 1.6,
    },
    button: {
      fontSize: Math.round(15 * densityScale.fontSize),
      fontWeight: contrastMod.fontWeight,
      color: palette.primaryContrast,
      backgroundColor: palette.primary,
      borderRadius,
      borderStyle: 'none',
      padding: {
        top: Math.round(12 * densityScale.padding),
        right: Math.round(24 * densityScale.padding),
        bottom: Math.round(12 * densityScale.padding),
        left: Math.round(24 * densityScale.padding),
      },
    },
    divider: {
      backgroundColor: palette.border,
      margin: {
        top: Math.round(16 * densityScale.margin),
        right: 0,
        bottom: Math.round(16 * densityScale.margin),
        left: 0,
      },
    },
    optionLabel: {
      fontSize: Math.round(baseFontSize * densityScale.fontSize),
      color: palette.text,
    },
    cardContainer: {
      backgroundColor: palette.surface,
      borderColor: palette.border,
      borderRadius,
      padding: {
        top: Math.round(12 * densityScale.padding),
        right: Math.round(16 * densityScale.padding),
        bottom: Math.round(12 * densityScale.padding),
        left: Math.round(16 * densityScale.padding),
      },
    },
  };
}

// =============================================================================
// Utility Functions
// =============================================================================

export function detectCategory(
  styles: Record<string, Partial<StyleProperties>>
): string {
  const bgColor =
    (styles.input?.backgroundColor as string) || '#ffffff';
  return isDark(bgColor) ? 'dark' : 'light';
}

export function extractPreviewColors(
  styles: Record<string, Partial<StyleProperties>>
): string[] {
  return [
    (styles.input?.backgroundColor as string) || '#ffffff',
    (styles.label?.color as string) || '#1f2937',
    (styles.button?.backgroundColor as string) || '#2563eb',
    (styles.input?.borderColor as string) || '#d1d5db',
  ];
}
