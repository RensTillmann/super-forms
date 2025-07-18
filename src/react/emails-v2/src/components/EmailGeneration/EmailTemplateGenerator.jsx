import React from 'react';
import { generateEmailSpacingHTML, spacingToEmailCSS } from '../Builder/Elements/SpacingLayer';
import { getElementCapabilities, hasCapability } from '../../capabilities/elementCapabilities';

/**
 * EmailTemplateGenerator - Converts React builder state to email-compatible HTML
 * 
 * This component generates table-based HTML that works across all email clients:
 * - Gmail, Outlook, Apple Mail, Yahoo Mail, etc.
 * - Mobile and desktop versions
 * - Uses progressive enhancement for modern features
 * - Includes VML fallbacks for Outlook
 */

class EmailTemplateGenerator {
  constructor(options = {}) {
    this.options = {
      clientSupport: 'maximum', // 'maximum', 'modern', 'basic'
      includeVMLFallbacks: true,
      responsiveMode: 'fluid', // 'fluid', 'desktop-only'
      emailWidth: 600,
      backgroundColor: '#f4f4f4',
      ...options
    };
  }

  /**
   * Generate complete email HTML from builder elements
   */
  generateEmailHTML(elements, templateSettings = {}) {
    const settings = {
      title: 'Email from Super Forms',
      preheader: '',
      bodyBackgroundColor: '#ffffff',
      containerWidth: this.options.emailWidth,
      ...templateSettings
    };

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>${settings.title}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    ${this.generateEmailCSS()}
</head>
<body style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    ${settings.preheader ? this.generatePreheader(settings.preheader) : ''}
    
    <!-- Email Container -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: ${this.options.backgroundColor};">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Main Content Table -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="${settings.containerWidth}" style="background-color: ${settings.bodyBackgroundColor}; max-width: ${settings.containerWidth}px;">
                    <tr>
                        <td>
                            ${this.generateElementsHTML(elements)}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>`;
  }

  /**
   * Generate CSS styles for email compatibility
   */
  generateEmailCSS() {
    return `<style type="text/css">
        /* Email-safe CSS Reset */
        table, td { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        p { margin: 0; }
        
        /* Responsive Styles */
        @media screen and (max-width: 600px) {
            .mobile-stack { display: block !important; width: 100% !important; }
            .mobile-hide { display: none !important; }
            .mobile-center { text-align: center !important; }
            .mobile-full-width { width: 100% !important; min-width: 100% !important; }
            .mobile-padding { padding: 10px !important; }
        }
        
        /* Outlook-specific styles */
        /*[if mso]>
        table { border-collapse: collapse; border-spacing: 0; border: none; margin: 0; }
        div, table, td { font-family: Arial, sans-serif !important; }
        <![endif]*/
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .dark-mode-bg { background-color: #1f1f1f !important; }
            .dark-mode-text { color: #ffffff !important; }
        }
    </style>`;
  }

  /**
   * Generate preheader text (hidden preview text)
   */
  generatePreheader(text) {
    return `<!-- Preheader -->
    <div style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; font-family: Arial, sans-serif; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        ${text}
    </div>`;
  }

  /**
   * Generate HTML for all elements in the email
   */
  generateElementsHTML(elements) {
    if (!Array.isArray(elements)) {
      return '';
    }

    return elements.map(element => this.generateElementHTML(element)).join('');
  }

  /**
   * Generate HTML for a single element
   */
  generateElementHTML(element) {
    const capabilities = getElementCapabilities(element.type);
    const elementHTML = this.getElementContentHTML(element);
    
    // If element supports spacing, wrap with spacing layer
    if (this.elementSupportsSpacing(element)) {
      return this.generateElementWithSpacing(element, elementHTML);
    }
    
    // Otherwise, return direct element HTML
    return `<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td>
                ${elementHTML}
            </td>
        </tr>
    </table>`;
  }

  /**
   * Check if element supports spacing capabilities
   */
  elementSupportsSpacing(element) {
    return hasCapability(element.type, 'spacing.margin') || 
           hasCapability(element.type, 'spacing.border') || 
           hasCapability(element.type, 'spacing.padding');
  }

  /**
   * Generate element HTML with spacing layer
   */
  generateElementWithSpacing(element, contentHTML) {
    const { props = {} } = element;
    const {
      margin = { top: 0, right: 0, bottom: 0, left: 0 },
      border = { top: 0, right: 0, bottom: 0, left: 0 },
      borderStyle = 'solid',
      borderColor = '#000000',
      padding = { top: 0, right: 0, bottom: 0, left: 0 },
      backgroundColor = 'transparent',
      backgroundImage = '',
    } = props;

    return generateEmailSpacingHTML({
      margin: hasCapability(element.type, 'spacing.margin') ? margin : null,
      border: hasCapability(element.type, 'spacing.border') ? {
        width: border,
        style: borderStyle,
        color: borderColor
      } : null,
      padding: hasCapability(element.type, 'spacing.padding') ? padding : null,
      backgroundColor: hasCapability(element.type, 'background.color') ? backgroundColor : null,
      backgroundImage: hasCapability(element.type, 'background.image') ? {
        url: backgroundImage,
        size: props.backgroundSize || 'cover',
        position: props.backgroundPosition || 'center',
        repeat: props.backgroundRepeat || 'no-repeat'
      } : null,
      borderRadius: hasCapability(element.type, 'effects.borderRadius') ? props.borderRadius : null,
      content: contentHTML,
      width: '100%'
    });
  }

  /**
   * Generate content HTML for specific element types
   */
  getElementContentHTML(element) {
    switch (element.type) {
      case 'emailWrapper':
        return this.generateEmailWrapperHTML(element);
      case 'emailContainer':
        return this.generateEmailContainerHTML(element);
      case 'text':
        return this.generateTextHTML(element);
      case 'button':
        return this.generateButtonHTML(element);
      case 'image':
        return this.generateImageHTML(element);
      case 'spacer':
        return this.generateSpacerHTML(element);
      case 'divider':
        return this.generateDividerHTML(element);
      case 'section':
        return this.generateSectionHTML(element);
      default:
        return `<div>Unknown element type: ${element.type}</div>`;
    }
  }

  /**
   * Generate text element HTML
   */
  generateTextHTML(element) {
    const { 
      content = '', 
      fontSize = 16, 
      fontFamily = 'Arial, sans-serif', 
      color = '#000000', 
      lineHeight = 1.4, 
      align = 'left',
      fontWeight = 'normal'
    } = element.props;

    return `<div style="font-family: ${fontFamily}; font-size: ${fontSize}px; color: ${color}; line-height: ${lineHeight}; text-align: ${align}; font-weight: ${fontWeight};">
        ${content}
    </div>`;
  }

  /**
   * Generate button element HTML with Outlook compatibility
   */
  generateButtonHTML(element) {
    const { 
      text = 'Button', 
      href = '#', 
      backgroundColor = '#007cba', 
      color = '#ffffff', 
      fontSize = 16, 
      fontWeight = 'normal',
      borderRadius = 4, 
      align = 'left',
      fullWidth = false,
      target = '_blank'
    } = element.props;

    // Outlook-compatible button using VML
    const outlookButton = this.options.includeVMLFallbacks ? `
    <!--[if mso]>
    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" 
                 href="${href}" 
                 style="height:40px;v-text-anchor:middle;width:${fullWidth ? '100%' : 'auto'};" 
                 arcsize="${borderRadius}%" 
                 strokecolor="${backgroundColor}" 
                 fillcolor="${backgroundColor}">
        <v:textbox inset="0,0,0,0">
            <center style="color:${color};font-family:Arial,sans-serif;font-size:${fontSize}px;font-weight:${fontWeight};">
                ${text}
            </center>
        </v:textbox>
    </v:roundrect>
    <![endif]-->` : '';

    return `<div style="text-align: ${align};">
        ${outlookButton}
        <!--[if !mso]><!-->
        <a href="${href}" 
           target="${target}" 
           ${target === '_blank' ? 'rel="noopener noreferrer"' : ''}
           style="display: inline-block; padding: 12px 24px; background-color: ${backgroundColor}; color: ${color}; text-decoration: none; border-radius: ${borderRadius}px; font-family: Arial, sans-serif; font-size: ${fontSize}px; font-weight: ${fontWeight}; text-align: center; ${fullWidth ? 'width: 100%; box-sizing: border-box;' : ''}">
            ${text}
        </a>
        <!--<![endif]-->
    </div>`;
  }

  /**
   * Generate image element HTML
   */
  generateImageHTML(element) {
    const { 
      src, 
      alt = 'Image', 
      width = 'auto', 
      height = 'auto', 
      align = 'left', 
      link,
      target = '_blank'
    } = element.props;

    const imgTag = `<img src="${src || 'https://via.placeholder.com/600x300'}" 
                         alt="${alt}" 
                         style="display: block; width: ${width}; height: ${height}; max-width: 100%; border: 0; outline: none; text-decoration: none;" 
                         border="0">`;

    const content = link ? 
      `<a href="${link}" target="${target}" ${target === '_blank' ? 'rel="noopener noreferrer"' : ''} style="text-decoration: none; border: 0;">${imgTag}</a>` : 
      imgTag;

    return `<div style="text-align: ${align};">${content}</div>`;
  }

  /**
   * Generate spacer element HTML
   */
  generateSpacerHTML(element) {
    const { height = 20 } = element.props;
    
    return `<div style="height: ${height}px; line-height: ${height}px; font-size: 0;">&nbsp;</div>`;
  }

  /**
   * Generate divider element HTML
   */
  generateDividerHTML(element) {
    const { 
      height = 1, 
      color = '#cccccc', 
      style = 'solid',
      align = 'center',
      width = '100%'
    } = element.props;

    return `<div style="text-align: ${align};">
        <hr style="height: ${height}px; width: ${width}; background-color: ${style === 'solid' ? color : 'transparent'}; border: none; ${style !== 'solid' ? `border-top: ${height}px ${style} ${color};` : ''} margin: 0; display: block;">
    </div>`;
  }

  /**
   * Generate section element HTML (container)
   */
  /**
   * Generate email wrapper HTML (outermost container)
   */
  generateEmailWrapperHTML(element) {
    const { children = [], backgroundColor = '#f5f5f5' } = element.props;
    
    return `<div style="width: 100%; min-height: 100vh; background-color: ${backgroundColor}; margin: 0; padding: 0;">
        ${children.map(child => this.generateElementHTML(child)).join('')}
    </div>`;
  }

  /**
   * Generate email container HTML (main content area)
   */
  generateEmailContainerHTML(element) {
    const { 
      children = [], 
      width = '600px',
      backgroundColor = '#ffffff',
      padding = { top: 0, right: 0, bottom: 0, left: 0 },
      margin = { top: 0, right: 'auto', bottom: 0, left: 'auto' },
      border = { top: 0, right: 0, bottom: 0, left: 0 },
      borderStyle = 'solid',
      borderColor = '#e5e5e5',
      borderRadius = 0,
      boxShadow = 'none'
    } = element.props;
    
    const paddingStyle = `${padding.top}px ${padding.right}px ${padding.bottom}px ${padding.left}px`;
    const borderWidth = `${border.top}px ${border.right}px ${border.bottom}px ${border.left}px`;
    const marginStyle = `${margin.top}px ${margin.right === 'auto' ? 'auto' : margin.right + 'px'} ${margin.bottom}px ${margin.left === 'auto' ? 'auto' : margin.left + 'px'}`;
    
    return `<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width: ${width}; margin: ${marginStyle}; background-color: ${backgroundColor}; ${borderRadius ? `border-radius: ${borderRadius}px;` : ''} ${boxShadow !== 'none' ? `box-shadow: ${boxShadow};` : ''} ${border.top > 0 || border.right > 0 || border.bottom > 0 || border.left > 0 ? `border: ${borderWidth} ${borderStyle} ${borderColor};` : ''}">
        <tr>
            <td style="padding: ${paddingStyle};">
                ${children.map(child => this.generateElementHTML(child)).join('')}
            </td>
        </tr>
    </table>`;
  }

  generateSectionHTML(element) {
    const { children = [], backgroundColor = 'transparent' } = element.props;
    
    return `<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" ${backgroundColor !== 'transparent' ? `bgcolor="${backgroundColor}"` : ''}>
        <tr>
            <td ${backgroundColor !== 'transparent' ? `style="background-color: ${backgroundColor};"` : ''}>
                ${this.generateElementsHTML(children)}
            </td>
        </tr>
    </table>`;
  }

  /**
   * Generate preview text for testing
   */
  generatePreview(elements) {
    return this.generateEmailHTML(elements, {
      title: 'Email Preview - Super Forms',
      preheader: 'Preview of your email design'
    });
  }
}

/**
 * React component wrapper for email generation
 */
function EmailGenerator({ elements, settings, onGenerate }) {
  const generator = new EmailTemplateGenerator(settings);
  
  const handleGenerate = () => {
    const html = generator.generateEmailHTML(elements);
    if (onGenerate) {
      onGenerate(html);
    }
    return html;
  };

  const handlePreview = () => {
    const html = generator.generatePreview(elements);
    // Open preview in new window
    const previewWindow = window.open('', '_blank');
    previewWindow.document.write(html);
    previewWindow.document.close();
  };

  return {
    generateHTML: handleGenerate,
    preview: handlePreview,
    generator
  };
}

export default EmailTemplateGenerator;
export { EmailGenerator };