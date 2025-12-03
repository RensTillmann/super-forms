/**
 * Email Builder Type Definitions
 * Core types for emails, elements, and builder state
 */

/**
 * Email configuration object
 */
export interface Email {
  id: string;
  enabled: boolean;
  description?: string;
  to: string[];
  cc?: string[];
  bcc?: string[];
  from?: string;
  fromName?: string;
  replyTo?: string;
  subject: string;
  body: EmailElement[];
  bodyType: 'visual' | 'html' | 'legacy_html';
  template?: string;
  attachments?: string[];
  conditions?: EmailCondition[];
  schedule?: EmailSchedule;
}

/**
 * Email condition for conditional sending
 */
export interface EmailCondition {
  field: string;
  operator: string;
  value: string;
  logic?: 'AND' | 'OR';
}

/**
 * Email schedule configuration
 */
export interface EmailSchedule {
  enabled: boolean;
  delay?: number;
  delayUnit?: 'minutes' | 'hours' | 'days';
  sendAt?: string;
}

/**
 * Email element (visual email builder components)
 */
export interface EmailElement {
  id: string;
  type: ElementType;
  parentId: string | null;
  position: number;
  props: ElementProps;
  children?: EmailElement[];
}

/**
 * All possible element types in the email builder
 */
export type ElementType =
  | 'emailWrapper'
  | 'emailContainer'
  | 'section'
  | 'columns'
  | 'text'
  | 'button'
  | 'image'
  | 'divider'
  | 'spacer'
  | 'social'
  | 'formData'
  | 'html';

/**
 * Element properties (common across all element types)
 */
export interface ElementProps {
  // Spacing
  margin?: SpacingValue;
  padding?: SpacingValue;
  border?: BorderValue;

  // Background
  backgroundColor?: string;
  backgroundImage?: BackgroundImageValue | null;

  // Typography (for text elements)
  fontFamily?: string;
  fontSize?: string;
  fontWeight?: string;
  lineHeight?: string;
  color?: string;
  textAlign?: 'left' | 'center' | 'right' | 'justify';

  // Layout
  width?: string;
  height?: string;
  maxWidth?: string;
  borderRadius?: string;
  boxShadow?: string;

  // Element-specific props (varies by type)
  [key: string]: unknown;
}

/**
 * Spacing value (margin/padding)
 */
export interface SpacingValue {
  top?: string;
  right?: string;
  bottom?: string;
  left?: string;
}

/**
 * Border value
 */
export interface BorderValue {
  width?: string;
  style?: 'solid' | 'dashed' | 'dotted' | 'none';
  color?: string;
}

/**
 * Background image configuration
 */
export interface BackgroundImageValue {
  url: string;
  size?: 'auto' | 'cover' | 'contain';
  position?: string;
  repeat?: 'no-repeat' | 'repeat' | 'repeat-x' | 'repeat-y';
}

/**
 * Translations for email fields across languages
 */
export interface Translations {
  [emailId: string]: {
    [field: string]: string;
  };
}

/**
 * Element capabilities configuration
 */
export interface ElementCapabilities {
  resizable: boolean | {
    horizontal?: boolean;
    vertical?: boolean;
    minWidth?: number;
    minHeight?: number;
    maxWidth?: number;
    maxHeight?: number;
    aspectRatio?: boolean;
  };
  background: {
    color: boolean;
    image: boolean;
    gradient?: boolean;
  };
  spacing: { margin: boolean; padding: boolean; border: boolean };
  typography: boolean | {
    font?: boolean;
    size?: boolean;
    color?: boolean;
    weight?: boolean;
    style?: boolean;
    lineHeight?: boolean;
    letterSpacing?: boolean;
  };
  alignment: boolean | {
    horizontal?: boolean;
    vertical?: boolean;
  };
  interactive: boolean | {
    href?: boolean;
    target?: boolean;
    tracking?: boolean;
    alt?: boolean;
    title?: boolean;
  };
  layout?: {
    fullWidth?: boolean;
    canDelete?: boolean;
    isSystemElement?: boolean;
    backgroundOnly?: boolean;
    inline?: boolean;
    droppable?: boolean;
    widthOptions?: string[];
    contained?: boolean;
  };
  display: {
    showInBuilder: boolean;
    selectable: boolean;
    showInEmail?: boolean;
  };
  effects?: {
    borderRadius?: boolean;
    shadow?: boolean;
  };
  media?: {
    formats?: string[];
    maxFileSize?: string;
    optimization?: boolean;
  };
  [key: string]: any;  // Allow additional properties
}

/**
 * Element capabilities map for all element types
 */
export type ElementCapabilitiesMap = {
  [K in ElementType]: ElementCapabilities;
};

/**
 * Drop zone position for drag and drop
 */
export interface DropZonePosition {
  parentId: string | null;
  position: number;
  children?: EmailElement[];
  elementCount?: number;
}
