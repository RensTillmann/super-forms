import React from 'react';
import clsx from 'clsx';

/**
 * SpacingLayer Component
 * 
 * Handles universal spacing for all elements:
 * - Margin (outer spacing)
 * - Border (with style and color)
 * - Padding (inner spacing) 
 * - Background (color and image)
 * - Border radius and effects
 * 
 * This component ensures consistent spacing behavior across all element types
 * and generates email-compatible CSS that works across all email clients.
 */
function SpacingLayer({
  margin,
  border,
  padding,
  backgroundColor,
  backgroundImage,
  borderRadius,
  boxShadow,
  children,
  className,
  isResizing = false
}) {
  
  // Build inline styles for maximum email client compatibility
  const buildStyles = () => {
    const styles = {};
    
    // Margin styles
    if (margin) {
      if (margin.top !== undefined) styles.marginTop = `${margin.top}px`;
      if (margin.right !== undefined) styles.marginRight = `${margin.right}px`;
      if (margin.bottom !== undefined) styles.marginBottom = `${margin.bottom}px`;
      if (margin.left !== undefined) styles.marginLeft = `${margin.left}px`;
    }
    
    // Border styles
    if (border && border.width) {
      const { width, style = 'solid', color = '#000000' } = border;
      
      if (width.top > 0) styles.borderTop = `${width.top}px ${style} ${color}`;
      if (width.right > 0) styles.borderRight = `${width.right}px ${style} ${color}`;
      if (width.bottom > 0) styles.borderBottom = `${width.bottom}px ${style} ${color}`;
      if (width.left > 0) styles.borderLeft = `${width.left}px ${style} ${color}`;
    }
    
    // Padding styles
    if (padding) {
      if (padding.top !== undefined) styles.paddingTop = `${padding.top}px`;
      if (padding.right !== undefined) styles.paddingRight = `${padding.right}px`;
      if (padding.bottom !== undefined) styles.paddingBottom = `${padding.bottom}px`;
      if (padding.left !== undefined) styles.paddingLeft = `${padding.left}px`;
    }
    
    // Background color
    if (backgroundColor && backgroundColor !== 'transparent') {
      styles.backgroundColor = backgroundColor;
    }
    
    // Background image
    if (backgroundImage && backgroundImage.url) {
      styles.backgroundImage = `url(${backgroundImage.url})`;
      styles.backgroundSize = backgroundImage.size || 'cover';
      styles.backgroundPosition = backgroundImage.position || 'center';
      styles.backgroundRepeat = backgroundImage.repeat || 'no-repeat';
    }
    
    // Border radius (with email client compatibility considerations)
    if (borderRadius && borderRadius > 0) {
      styles.borderRadius = `${borderRadius}px`;
      // Add vendor prefixes for better email client support
      styles.WebkitBorderRadius = `${borderRadius}px`;
      styles.MozBorderRadius = `${borderRadius}px`;
    }
    
    // Box shadow (note: not all email clients support box-shadow)
    if (boxShadow && boxShadow !== 'none') {
      styles.boxShadow = boxShadow;
      // Add vendor prefixes for better email client support
      styles.WebkitBoxShadow = boxShadow;
      styles.MozBoxShadow = boxShadow;
    }
    
    return styles;
  };
  
  // Generate email-compatible HTML attributes for Outlook VML fallbacks
  const getEmailAttributes = () => {
    const attrs = {};
    
    // Background color fallback for Outlook
    if (backgroundColor && backgroundColor !== 'transparent') {
      attrs.bgcolor = backgroundColor;
    }
    
    // Background image fallback for Outlook (would need VML in real email)
    if (backgroundImage && backgroundImage.url) {
      attrs.background = backgroundImage.url;
    }
    
    return attrs;
  };
  
  return (
    <div
      className={clsx(
        'spacing-layer',
        // Only apply transitions when NOT resizing to avoid lag
        !isResizing && 'transition-all duration-200',
        className
      )}
      style={buildStyles()}
      {...getEmailAttributes()}
      {...(process.env.NODE_ENV === 'development' && {
        'data-debug-spacing': 'true',
        'data-debug-margin': margin ? JSON.stringify(margin) : 'none',
        'data-debug-border': border?.width ? JSON.stringify(border.width) : 'none',
        'data-debug-padding': padding ? JSON.stringify(padding) : 'none',
        'data-debug-bg-color': backgroundColor || 'transparent',
        'data-debug-bg-image': backgroundImage?.url || 'none'
      })}
    >
      {children}
    </div>
  );
}

/**
 * Helper function to generate table-based HTML for email output
 * This would be used by the email generation system
 */
export function generateEmailSpacingHTML({
  margin,
  border,
  padding,
  backgroundColor,
  backgroundImage,
  borderRadius,
  content,
  width = '100%'
}) {
  // For email generation, we use tables instead of divs
  let html = '';
  
  // Outer table for margin simulation
  if (margin && (margin.top > 0 || margin.bottom > 0 || margin.left > 0 || margin.right > 0)) {
    html += '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">';
    html += '<tr>';
    
    // Left margin
    if (margin.left > 0) {
      html += `<td width="${margin.left}" style="font-size: 0; line-height: 0;">&nbsp;</td>`;
    }
    
    html += '<td>';
  }
  
  // Main content table
  html += `<table role="presentation" cellspacing="0" cellpadding="0" border="0"`;
  
  // Add width
  if (width !== '100%') {
    html += ` width="${width}"`;
  } else {
    html += ' width="100%"';
  }
  
  // Add background color
  if (backgroundColor && backgroundColor !== 'transparent') {
    html += ` bgcolor="${backgroundColor}"`;
  }
  
  // Add background image for supported clients
  if (backgroundImage && backgroundImage.url) {
    html += ` background="${backgroundImage.url}"`;
  }
  
  html += '>';
  
  // Top margin/padding row
  if ((margin && margin.top > 0) || (padding && padding.top > 0)) {
    const spacingHeight = (margin?.top || 0) + (padding?.top || 0);
    html += `<tr><td height="${spacingHeight}" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>`;
  }
  
  // Main content row
  html += '<tr><td';
  
  // Add padding
  if (padding) {
    const paddingStyles = [];
    if (padding.top > 0) paddingStyles.push(`padding-top: ${padding.top}px`);
    if (padding.right > 0) paddingStyles.push(`padding-right: ${padding.right}px`);
    if (padding.bottom > 0) paddingStyles.push(`padding-bottom: ${padding.bottom}px`);
    if (padding.left > 0) paddingStyles.push(`padding-left: ${padding.left}px`);
    
    if (paddingStyles.length > 0) {
      html += ` style="${paddingStyles.join('; ')}"`;
    }
  }
  
  html += '>';
  
  // Insert the actual content
  html += content;
  
  html += '</td></tr>';
  
  // Bottom margin/padding row
  if ((margin && margin.bottom > 0) || (padding && padding.bottom > 0)) {
    const spacingHeight = (margin?.bottom || 0) + (padding?.bottom || 0);
    html += `<tr><td height="${spacingHeight}" style="font-size: 0; line-height: 0;">&nbsp;</td></tr>`;
  }
  
  html += '</table>';
  
  // Close outer margin table
  if (margin && (margin.top > 0 || margin.bottom > 0 || margin.left > 0 || margin.right > 0)) {
    html += '</td>';
    
    // Right margin
    if (margin.right > 0) {
      html += `<td width="${margin.right}" style="font-size: 0; line-height: 0;">&nbsp;</td>`;
    }
    
    html += '</tr></table>';
  }
  
  return html;
}

/**
 * Utility function to convert React spacing props to email-compatible CSS
 */
export function spacingToEmailCSS({ margin, border, padding, backgroundColor, backgroundImage }) {
  const styles = [];
  
  // Margin - converted to table cells in actual email
  if (margin) {
    if (margin.top) styles.push(`margin-top: ${margin.top}px`);
    if (margin.right) styles.push(`margin-right: ${margin.right}px`);
    if (margin.bottom) styles.push(`margin-bottom: ${margin.bottom}px`);
    if (margin.left) styles.push(`margin-left: ${margin.left}px`);
  }
  
  // Border
  if (border && border.width) {
    const { width, style = 'solid', color = '#000000' } = border;
    if (width.top > 0) styles.push(`border-top: ${width.top}px ${style} ${color}`);
    if (width.right > 0) styles.push(`border-right: ${width.right}px ${style} ${color}`);
    if (width.bottom > 0) styles.push(`border-bottom: ${width.bottom}px ${style} ${color}`);
    if (width.left > 0) styles.push(`border-left: ${width.left}px ${style} ${color}`);
  }
  
  // Padding
  if (padding) {
    if (padding.top) styles.push(`padding-top: ${padding.top}px`);
    if (padding.right) styles.push(`padding-right: ${padding.right}px`);
    if (padding.bottom) styles.push(`padding-bottom: ${padding.bottom}px`);
    if (padding.left) styles.push(`padding-left: ${padding.left}px`);
  }
  
  // Background
  if (backgroundColor && backgroundColor !== 'transparent') {
    styles.push(`background-color: ${backgroundColor}`);
  }
  
  if (backgroundImage && backgroundImage.url) {
    styles.push(`background-image: url(${backgroundImage.url})`);
    styles.push(`background-size: ${backgroundImage.size || 'cover'}`);
    styles.push(`background-position: ${backgroundImage.position || 'center'}`);
    styles.push(`background-repeat: ${backgroundImage.repeat || 'no-repeat'}`);
  }
  
  return styles.join('; ');
}

export default SpacingLayer;