import { ReactNode } from 'react';

export interface FormSelectorProps {
  currentForm: string;
  onFormSelect: (formId: string) => void;
  forms?: FormOption[];
}

export interface FormOption {
  id: string;
  name: string;
  status: 'published' | 'draft' | 'archived';
  lastModified?: number;
}

export interface ZoomControlsProps {
  currentZoom: number;
  zoomLevels?: number[];
  onZoomChange: (zoom: number) => void;
  onFitToScreen?: () => void;
}

export interface InlineEditableTextProps {
  value: string;
  onChange: (value: string) => void;
  className?: string;
  placeholder?: string;
  multiline?: boolean;
  onFormat?: (format: string) => void;
  maxLength?: number;
  disabled?: boolean;
}

export interface ElementBreadcrumbProps {
  path: BreadcrumbItem[];
  onNavigate: (elementId: string) => void;
  maxItems?: number;
}

export interface BreadcrumbItem {
  id: string;
  name: string;
  type: string;
  icon?: ReactNode;
}