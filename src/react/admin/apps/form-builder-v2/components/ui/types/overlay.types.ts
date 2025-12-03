import { ReactNode } from 'react';

export interface ContextMenuProps {
  x: number;
  y: number;
  onClose: () => void;
  onAction: (action: string) => void;
  items?: ContextMenuItem[];
}

export interface ContextMenuItem {
  id: string;
  label: string;
  icon?: ReactNode;
  shortcut?: string;
  divider?: boolean;
  danger?: boolean;
  disabled?: boolean;
  action?: string;
  children?: ContextMenuItem[];
}

export interface FloatingToolbarProps {
  position: { x: number; y: number };
  onClose: () => void;
  onFormat?: (format: string) => void;
  selectedText?: string;
  tools?: ToolbarItem[];
}

export interface ToolbarItem {
  id: string;
  label: string;
  icon: ReactNode;
  action: string;
  active?: boolean;
  disabled?: boolean;
}

export interface GridOverlayProps {
  isVisible: boolean;
  gridSize?: number;
  color?: string;
  opacity?: number;
}

export interface ZoomControlsProps {
  currentZoom: number;
  zoomLevels?: number[];
  onZoomChange: (zoom: number) => void;
  onFitToScreen?: () => void;
}

export interface ResizableBottomTrayProps {
  isCollapsed: boolean;
  onToggleCollapse: () => void;
  children: ReactNode;
  isMobile?: boolean;
  minHeight?: number;
  maxHeight?: number;
  defaultHeight?: number;
  onHeightChange?: (height: number) => void;
}