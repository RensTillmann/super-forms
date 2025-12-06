// Form Builder Types

import type { NodeType, StyleProperties } from '../../../schemas/styles';

export type DeviceType = 'desktop' | 'tablet' | 'mobile';
export type TabType = 'builder' | 'settings' | 'translations' | 'triggers' | 'pdf' | 'listings' | 'stripe';
export type ElementCategory = 'basic' | 'advanced' | 'layout' | 'special';

export interface FormSettings {
  maxWidth: {
    desktop: string;
    tablet: string;
    mobile: string;
  };
  padding: string;
  background: string;
  [key: string]: any;
}

export interface TranslationData {
  languages: string[];
  defaultLanguage: string;
  translations: Record<string, Record<string, string>>;
}

export interface FormElement {
  id: string;
  type: string;
  /** @deprecated Use properties.* - schema defines element structure */
  category?: ElementCategory;
  /** @deprecated Use properties.label - kept for legacy compatibility */
  label?: string;
  /** @deprecated Schema defines icon - kept for legacy compatibility */
  icon?: string;
  /** All element data - schema is the source of truth */
  properties: Record<string, any>;
  children?: string[];
  parent?: string;
  /** Per-element style overrides by node type. Omitted properties use global defaults. */
  styleOverrides?: Partial<Record<NodeType, Partial<StyleProperties>>>;
}

export interface DeviceVisibility {
  desktop: boolean;
  tablet: boolean;
  mobile: boolean;
}

export interface ElementDefinition {
  type: string;
  category: ElementCategory;
  label: string;
  icon: string;
  defaultProperties: Record<string, any>;
  allowChildren?: boolean;
}

export interface DragItem {
  type: 'new' | 'existing';
  elementType?: string;
  elementId?: string;
  sourceIndex?: number;
}

export interface DropResult {
  targetId?: string;
  position: 'before' | 'after' | 'inside';
  index: number;
}

// State interfaces
export interface FormState {
  id: string;
  title: string;
  settings: FormSettings;
  translations: TranslationData;
  isDirty: boolean;
  lastSaved?: Date;
}

export interface ElementsState {
  items: Record<string, FormElement>;
  order: string[];
  deviceVisibility: Record<string, DeviceVisibility>;
}

export interface UIState {
  activeTab: TabType;
  currentDevice: DeviceType;
  paletteExpanded: boolean;
  paletteHeight: number;
  managerVisible: boolean;
  managerPosition: { x: number; y: number };
}

export interface BuilderState {
  draggedElement: string | null;
  hoveredElement: string | null;
  selectedElements: string[];
  dropZoneActive: boolean;
  dropTarget: { id: string; position: 'before' | 'after' | 'inside' } | null;
}

export interface RootState {
  form: FormState;
  elements: ElementsState;
  ui: UIState;
  builder: BuilderState;
}