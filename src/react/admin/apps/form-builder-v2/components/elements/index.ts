// Element categories
export { default as TextInput } from './basic/TextInput';
export { default as TextArea } from './basic/TextArea';

export { default as Select } from './choice/Select';
export { default as RadioCards } from './choice/RadioCards';
export { default as CheckboxCards } from './choice/CheckboxCards';

export { default as ColumnsContainer } from './containers/ColumnsContainer';

// Main renderer
export { default as ElementRenderer } from './ElementRenderer';

// Types
export interface ElementType {
  type: string;
  id: string;
  properties?: any;
}

export interface CommonElementProps {
  className: string;
  disabled: boolean;
  style: {
    width: string;
    margin?: string;
    backgroundColor?: string;
    borderStyle?: string;
  };
}

// Element categories mapping
export const ELEMENT_CATEGORIES = {
  basic: ['text', 'email', 'phone', 'url', 'password', 'number', 'number-formatted', 'textarea'],
  choice: ['select', 'radio', 'checkbox', 'radio-cards', 'checkbox-cards'],
  containers: ['columns', 'section', 'tabs', 'accordion', 'step-wizard', 'repeater'],
  advanced: ['date', 'datetime', 'time', 'location', 'rating', 'slider', 'signature'],
  upload: ['file', 'image'],
  layout: ['divider', 'spacer', 'page-break', 'heading', 'paragraph', 'html-block'],
  integration: ['webhook', 'payment', 'embed', 'calculation']
} as const;