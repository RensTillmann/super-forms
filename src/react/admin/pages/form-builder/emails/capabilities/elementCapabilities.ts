/**
 * Element Capabilities System
 * Defines what each element type can do in terms of:
 * - Resizing behavior
 * - Background options
 * - Spacing controls
 * - Interactive features
 * - Typography options
 * - Layout capabilities
 */

import type { ElementCapabilitiesMap, ElementType, ElementCapabilities } from '../types/email';

export const elementCapabilities: ElementCapabilitiesMap = {
  // System elements - email canvas and wrapper
  emailWrapper: {
    resizable: false,
    background: { 
      color: true, 
      image: false  // Background images not recommended for email clients
    },
    spacing: { 
      margin: false,  // Email client background doesn't need spacing
      padding: false, 
      border: false 
    },
    typography: false,
    alignment: false,
    interactive: false,
    layout: {
      fullWidth: true,
      canDelete: false,
      isSystemElement: true,
      backgroundOnly: true  // Special flag for background-only elements
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },
  
  emailContainer: {
    resizable: false,
    background: { 
      color: true, 
      image: true 
    },
    spacing: { 
      margin: true, 
      padding: true, 
      border: true 
    },
    typography: false,
    alignment: { 
      horizontal: true, 
      vertical: false 
    },
    interactive: false,
    layout: {
      canDelete: false,
      isSystemElement: true,
      droppable: true,
      widthOptions: ['600px', '700px']
    },
    effects: {
      borderRadius: true,
      shadow: true
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },
  
  // Text elements - content focused
  text: {
    resizable: { 
      horizontal: true, 
      vertical: false,
      minWidth: 50,
      maxWidth: 1000
    },
    background: { 
      color: false,  // Text elements typically don't have backgrounds
      image: false 
    },
    spacing: { 
      margin: true, 
      padding: false,  // Text elements use line-height instead
      border: false 
    },
    typography: { 
      font: true, 
      size: true, 
      color: true,
      weight: true,
      style: true,
      lineHeight: true,
      letterSpacing: true
    },
    alignment: {
      horizontal: true,
      vertical: false
    },
    interactive: false,
    layout: {
      fullWidth: false,
      inline: true
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },

  // Button elements - interactive focused
  button: {
    resizable: { 
      horizontal: true, 
      vertical: false,
      minWidth: 80,
      maxWidth: 400
    },
    background: { 
      color: true, 
      image: false  // Buttons typically use solid colors
    },
    spacing: { 
      margin: true, 
      padding: true, 
      border: true 
    },
    typography: { 
      font: true, 
      size: true, 
      color: true,
      weight: true,
      style: false,  // Buttons typically don't use italic
      lineHeight: false,
      letterSpacing: true
    },
    alignment: { 
      horizontal: true, 
      vertical: false 
    },
    interactive: {
      href: true,
      target: true,
      tracking: true
    },
    layout: {
      fullWidth: true,  // Buttons can be full-width
      inline: false
    },
    effects: {
      borderRadius: true,
      shadow: false  // Email client compatibility
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },

  // Image elements - media focused
  image: {
    resizable: { 
      horizontal: true, 
      vertical: true,
      minWidth: 50,
      minHeight: 50,
      maxWidth: 1000,
      maxHeight: 800,
      aspectRatio: true  // Maintain aspect ratio option
    },
    background: { 
      color: false,  // Images are the background
      image: false 
    },
    spacing: { 
      margin: true, 
      padding: false,  // Images don't need padding
      border: true 
    },
    typography: false,  // Images don't have typography
    alignment: { 
      horizontal: true, 
      vertical: false 
    },
    interactive: {
      href: true,
      target: true,
      alt: true,  // Alt text for accessibility
      title: true
    },
    layout: {
      fullWidth: true,
      inline: false
    },
    effects: {
      borderRadius: true,
      shadow: false  // Email client compatibility
    },
    media: {
      formats: ['jpg', 'png', 'gif'],
      maxFileSize: '5MB',
      optimization: true
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },

  // Section elements - container focused
  section: {
    resizable: { 
      horizontal: true, 
      vertical: true,
      minWidth: 200,
      minHeight: 50,
      maxWidth: 700,  // Email-friendly max width
      defaultWidth: 600  // Industry standard email width
    },
    background: { 
      color: true, 
      image: true,
      gradient: false  // Limited email client support
    },
    spacing: { 
      margin: true, 
      padding: true, 
      border: true 
    },
    typography: false,  // Sections contain other elements
    alignment: { 
      horizontal: true, 
      vertical: true 
    },
    interactive: false,
    layout: {
      fullWidth: true,
      contained: true,  // Can be contained within page width
      centered: true,   // Center horizontally for email compatibility
      columns: true,    // Can contain multiple columns
      droppable: true   // Can accept child elements
    },
    container: {
      maxColumns: 4,
      columnSpacing: true,
      verticalAlignment: true,
      equalHeight: true
    },
    effects: {
      borderRadius: true,
      shadow: false  // Email client compatibility
    }
  },

  // Spacer elements - layout focused
  spacer: {
    resizable: { 
      horizontal: false,  // Spacers are typically fixed width
      vertical: true,     // Height is the main property
      minHeight: 10,
      maxHeight: 200
    },
    background: { 
      color: false,  // Spacers are invisible
      image: false 
    },
    spacing: { 
      margin: false,  // Spacers ARE the spacing
      padding: false, 
      border: false 
    },
    typography: false,
    alignment: false,
    interactive: false,
    layout: {
      fullWidth: true,
      inline: false
    },
    display: {
      showInBuilder: true,   // Visible in builder for editing
      showInEmail: false     // Invisible in final email
    }
  },

  // Divider elements - visual separation
  divider: {
    resizable: { 
      horizontal: true,   // Width can be adjusted
      vertical: false,    // Height is determined by style
      minWidth: 50,
      maxWidth: 1000
    },
    background: { 
      color: true,   // Divider color
      image: false 
    },
    spacing: { 
      margin: true, 
      padding: false, 
      border: false 
    },
    typography: false,
    alignment: { 
      horizontal: true, 
      vertical: false 
    },
    interactive: false,
    layout: {
      fullWidth: true,
      inline: false
    },
    style: {
      thickness: true,   // Line thickness
      style: true,       // solid, dashed, dotted
      opacity: true
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },

  // Social elements - social media links
  social: {
    resizable: { 
      horizontal: false,  // Fixed icon sizes typically
      vertical: false
    },
    background: { 
      color: false,  // Icons have their own colors
      image: false 
    },
    spacing: { 
      margin: true, 
      padding: true,  // Space around icons
      border: false 
    },
    typography: false,
    alignment: { 
      horizontal: true, 
      vertical: false 
    },
    interactive: {
      href: true,
      target: true,
      tracking: true
    },
    layout: {
      fullWidth: false,
      inline: true
    },
    social: {
      platforms: ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'],
      iconStyle: ['default', 'round', 'square'],
      iconSize: ['small', 'medium', 'large'],
      showLabels: true
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },

  // Columns container - layout wrapper
  columns: {
    resizable: { 
      horizontal: true, 
      vertical: false  // Height determined by content
    },
    background: { 
      color: false,  // Columns container is typically transparent
      image: false 
    },
    spacing: { 
      margin: true, 
      padding: true, 
      border: false 
    },
    typography: false,
    alignment: { 
      horizontal: true, 
      vertical: true 
    },
    interactive: false,
    layout: {
      fullWidth: true,
      droppable: true
    },
    columns: {
      count: { min: 2, max: 4 },
      distribution: ['equal', 'custom'],
      spacing: true,
      verticalAlignment: ['top', 'middle', 'bottom'],
      responsive: {
        mobile: 'stack',
        tablet: 'maintain',
        desktop: 'maintain'
      }
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  },

  // Form data elements - dynamic content
  formData: {
    resizable: { 
      horizontal: true, 
      vertical: false
    },
    background: { 
      color: true, 
      image: false 
    },
    spacing: { 
      margin: true, 
      padding: true, 
      border: true 
    },
    typography: { 
      font: true, 
      size: true, 
      color: true,
      weight: true,
      style: true
    },
    alignment: { 
      horizontal: true, 
      vertical: false 
    },
    interactive: false,
    layout: {
      fullWidth: false,
      inline: true
    },
    data: {
      fields: ['name', 'email', 'phone', 'company', 'custom'],
      formatting: true,
      fallback: true  // What to show if data is missing
    },
    display: {
      showInBuilder: true,
      selectable: true
    }
  }
};

/**
 * Get capabilities for a specific element type
 */
export function getElementCapabilities(elementType: ElementType): ElementCapabilities {
  return elementCapabilities[elementType] || {} as ElementCapabilities;
}

/**
 * Check if an element has a specific capability
 * @param elementType - The type of element
 * @param capability - The capability to check (e.g., 'resizable.horizontal')
 * @returns Whether the element has that capability
 */
export function hasCapability(elementType: ElementType, capability: string): boolean {
  const capabilities = getElementCapabilities(elementType);
  const keys = capability.split('.');

  let current: any = capabilities;
  for (const key of keys) {
    if (current && typeof current === 'object' && key in current) {
      current = current[key];
    } else {
      return false;
    }
  }

  return Boolean(current);
}

/**
 * Get all element types that have a specific capability
 * @param capability - The capability to check
 * @returns Array of element types that have this capability
 */
export function getElementsWithCapability(capability: string): ElementType[] {
  return Object.keys(elementCapabilities).filter(elementType =>
    hasCapability(elementType as ElementType, capability)
  ) as ElementType[];
}

/**
 * Register a new element type with capabilities
 * @param elementType - The new element type
 * @param capabilities - The capabilities object
 */
export function registerElementCapability(elementType: ElementType, capabilities: ElementCapabilities): void {
  (elementCapabilities as any)[elementType] = capabilities;
}

export default elementCapabilities;