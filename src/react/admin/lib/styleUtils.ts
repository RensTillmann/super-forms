import type { StyleProperties, BoxSpacing } from '../schemas/styles/types';
import type { CSSProperties } from 'react';

/**
 * Convert BoxSpacing to CSS margin/padding string
 */
function boxSpacingToCSS(spacing: BoxSpacing | undefined): string | undefined {
  if (!spacing) return undefined;
  return `${spacing.top}px ${spacing.right}px ${spacing.bottom}px ${spacing.left}px`;
}

/**
 * Convert StyleProperties to React CSSProperties
 * Handles unit conversion and shorthand properties
 */
export function stylesToCSS(style: Partial<StyleProperties>): CSSProperties {
  const css: CSSProperties = {};

  // Typography
  if (style.fontSize !== undefined) css.fontSize = `${style.fontSize}px`;
  if (style.fontFamily !== undefined) css.fontFamily = style.fontFamily;
  if (style.fontWeight !== undefined) css.fontWeight = style.fontWeight;
  if (style.fontStyle !== undefined) css.fontStyle = style.fontStyle;
  if (style.textAlign !== undefined) css.textAlign = style.textAlign;
  if (style.textDecoration !== undefined) css.textDecoration = style.textDecoration;
  if (style.lineHeight !== undefined) css.lineHeight = style.lineHeight;
  if (style.letterSpacing !== undefined) css.letterSpacing = `${style.letterSpacing}px`;

  // Colors
  if (style.color !== undefined) css.color = style.color;
  if (style.backgroundColor !== undefined) css.backgroundColor = style.backgroundColor;

  // Spacing
  if (style.margin !== undefined) css.margin = boxSpacingToCSS(style.margin);
  if (style.padding !== undefined) css.padding = boxSpacingToCSS(style.padding);

  // Border
  if (style.border !== undefined) {
    const b = style.border;
    css.borderWidth = `${b.top}px ${b.right}px ${b.bottom}px ${b.left}px`;
  }
  if (style.borderStyle !== undefined) css.borderStyle = style.borderStyle;
  if (style.borderColor !== undefined) css.borderColor = style.borderColor;
  if (style.borderRadius !== undefined) css.borderRadius = `${style.borderRadius}px`;

  // Sizing
  if (style.width !== undefined) {
    css.width = typeof style.width === 'number' ? `${style.width}px` : style.width;
  }
  if (style.minHeight !== undefined) css.minHeight = `${style.minHeight}px`;

  return css;
}

/**
 * Merge resolved style with element property overrides
 * Element properties take precedence for layout (width, margin)
 */
export function mergeWithElementProps(
  resolvedStyle: CSSProperties,
  elementProps: Record<string, unknown> = {}
): CSSProperties {
  const merged = { ...resolvedStyle };

  // Element properties can override layout
  if (elementProps.width !== undefined) {
    merged.width = elementProps.width === 'full'
      ? '100%'
      : `${elementProps.width}%`;
  }

  return merged;
}

/**
 * Type for resolved styles passed to element components
 */
export interface ResolvedStyles {
  label: CSSProperties;
  input: CSSProperties;
  error: CSSProperties;
  description: CSSProperties;
  placeholder: CSSProperties;
  required: CSSProperties;
  fieldContainer: CSSProperties;
  heading: CSSProperties;
  paragraph: CSSProperties;
  button: CSSProperties;
  divider: CSSProperties;
  optionLabel: CSSProperties;
  cardContainer: CSSProperties;
}
