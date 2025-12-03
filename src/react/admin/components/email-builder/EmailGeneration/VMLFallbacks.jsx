/**
 * VML Fallbacks for Outlook Compatibility
 * 
 * Vector Markup Language (VML) is used to provide fallbacks for Outlook
 * clients that don't support modern CSS features like:
 * - Background images
 * - Border radius on buttons
 * - CSS transforms and positioning
 * - Modern layout techniques
 */

/**
 * Generate VML background image fallback for Outlook
 */
export function generateVMLBackground({
  imageUrl,
  backgroundColor = '#ffffff',
  width = 600,
  height = 'auto',
  content = ''
}) {
  if (!imageUrl) {
    return content;
  }

  return `<!--[if gte mso 9]>
<v:rect xmlns:v="urn:schemas-microsoft-com:vml" 
        fill="true" 
        stroke="false" 
        style="width: ${width}px; ${height !== 'auto' ? `height: ${height}px;` : ''}">
    <v:fill type="tile" src="${imageUrl}" color="${backgroundColor}" />
    <v:textbox inset="0,0,0,0">
<![endif]-->
<div style="background-image: url('${imageUrl}'); background-color: ${backgroundColor}; background-size: cover; background-position: center; background-repeat: no-repeat;">
    ${content}
</div>
<!--[if gte mso 9]>
    </v:textbox>
</v:rect>
<![endif]-->`;
}

/**
 * Generate VML button with border radius for Outlook
 */
export function generateVMLButton({
  href = '#',
  text = 'Button',
  backgroundColor = '#007cba',
  color = '#ffffff',
  fontSize = 16,
  fontWeight = 'normal',
  borderRadius = 4,
  padding = '12px 24px',
  width = 'auto',
  target = '_blank'
}) {
  // Calculate border radius percentage for VML
  const arcSize = Math.round((borderRadius / 40) * 100); // Approximate conversion

  return `<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" 
             href="${href}" 
             style="height: auto; v-text-anchor: middle; ${width !== 'auto' ? `width: ${width};` : ''}" 
             arcsize="${arcSize}%" 
             strokecolor="${backgroundColor}" 
             fillcolor="${backgroundColor}">
    <v:textbox inset="0,0,0,0">
        <center style="color: ${color}; font-family: Arial, sans-serif; font-size: ${fontSize}px; font-weight: ${fontWeight}; padding: ${padding};">
            ${text}
        </center>
    </v:textbox>
</v:roundrect>
<![endif]-->
<!--[if !mso]><!-->
<a href="${href}" 
   target="${target}" 
   ${target === '_blank' ? 'rel="noopener noreferrer"' : ''}
   style="display: inline-block; padding: ${padding}; background-color: ${backgroundColor}; color: ${color}; text-decoration: none; border-radius: ${borderRadius}px; font-family: Arial, sans-serif; font-size: ${fontSize}px; font-weight: ${fontWeight}; text-align: center; ${width !== 'auto' ? `width: ${width}; box-sizing: border-box;` : ''}">
    ${text}
</a>
<!--<![endif]-->`;
}

/**
 * Generate VML spacer for consistent spacing in Outlook
 */
export function generateVMLSpacer(height) {
  return `<!--[if mso]>
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td height="${height}" style="font-size: 0; line-height: 0;">&nbsp;</td>
    </tr>
</table>
<![endif]-->
<!--[if !mso]><!-->
<div style="height: ${height}px; line-height: ${height}px; font-size: 0;">&nbsp;</div>
<!--<![endif]-->`;
}

/**
 * Generate VML wrapper for complex layouts
 */
export function generateVMLContainer({
  width = 600,
  backgroundColor = 'transparent',
  content = ''
}) {
  if (backgroundColor === 'transparent') {
    return content;
  }

  return `<!--[if mso]>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="${width}">
    <tr>
        <td bgcolor="${backgroundColor}">
<![endif]-->
<div style="background-color: ${backgroundColor};">
    ${content}
</div>
<!--[if mso]>
        </td>
    </tr>
</table>
<![endif]-->`;
}

/**
 * Generate VML column layout for multi-column sections
 */
export function generateVMLColumns({
  columns = [],
  totalWidth = 600,
  spacing = 0
}) {
  if (!columns.length) {
    return '';
  }

  const columnWidth = Math.floor((totalWidth - (spacing * (columns.length - 1))) / columns.length);
  
  let vmlOutput = `<!--[if mso]>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="${totalWidth}">
    <tr>`;

  columns.forEach((column, index) => {
    vmlOutput += `
        <td width="${columnWidth}" valign="top" ${index > 0 && spacing > 0 ? `style="padding-left: ${spacing}px;"` : ''}>
            ${column.content || ''}
        </td>`;
  });

  vmlOutput += `
    </tr>
</table>
<![endif]-->
<!--[if !mso]><!-->`;

  // Modern email client version
  columns.forEach((column, index) => {
    vmlOutput += `
<div style="display: inline-block; width: 100%; max-width: ${columnWidth}px; vertical-align: top; ${index > 0 && spacing > 0 ? `margin-left: ${spacing}px;` : ''}">
    ${column.content || ''}
</div>`;
  });

  vmlOutput += `
<!--<![endif]-->`;

  return vmlOutput;
}

/**
 * Generate VML image with link support
 */
export function generateVMLImage({
  src,
  alt = 'Image',
  width = 'auto',
  height = 'auto',
  link = null,
  target = '_blank',
  align = 'left'
}) {
  const imgTag = `<img src="${src}" alt="${alt}" style="display: block; width: ${width}; height: ${height}; max-width: 100%; border: 0;" border="0">`;
  
  let content = imgTag;
  
  if (link) {
    content = `<a href="${link}" target="${target}" ${target === '_blank' ? 'rel="noopener noreferrer"' : ''} style="text-decoration: none; border: 0;">${imgTag}</a>`;
  }

  return `<!--[if mso]>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td align="${align}">
            ${content}
        </td>
    </tr>
</table>
<![endif]-->
<!--[if !mso]><!-->
<div style="text-align: ${align};">
    ${content}
</div>
<!--<![endif]-->`;
}

/**
 * Outlook-specific CSS that gets embedded in email head
 */
export function getOutlookCSS() {
  return `<!--[if mso]>
<style type="text/css">
    table { border-collapse: collapse; border-spacing: 0; border: none; margin: 0; }
    div, table, td { font-family: Arial, sans-serif !important; }
    td { border-collapse: collapse; }
    .outlook-hide { display: none !important; }
    .outlook-show { display: block !important; }
</style>
<![endif]-->`;
}

/**
 * Utility to detect if VML fallbacks are needed
 */
export function shouldUseVMLFallback(feature, clientSupport = 'maximum') {
  const vmlFeatures = {
    'background-image': true,
    'border-radius': true,
    'button-styling': true,
    'complex-layouts': true,
    'spacing': true
  };

  if (clientSupport === 'basic') {
    return false; // Use basic HTML only
  }

  return vmlFeatures[feature] || false;
}

/**
 * Generate complete VML document wrapper
 */
export function generateVMLDocument({
  content = '',
  width = 600,
  backgroundColor = '#f4f4f4',
  title = 'Email'
}) {
  return `<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>${title}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    ${getOutlookCSS()}
</head>
<body style="margin: 0; padding: 0; background-color: ${backgroundColor};">
    <!--[if mso]>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="${width}">
                    <tr>
                        <td>
    <![endif]-->
    ${content}
    <!--[if mso]>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <![endif]-->
</body>
</html>`;
}

export default {
  generateVMLBackground,
  generateVMLButton,
  generateVMLSpacer,
  generateVMLContainer,
  generateVMLColumns,
  generateVMLImage,
  getOutlookCSS,
  shouldUseVMLFallback,
  generateVMLDocument
};