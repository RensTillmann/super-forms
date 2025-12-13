// Generic overlay components (not device-specific)
export { ErrorBoundary } from './ErrorBoundary';
export { ContextMenu } from './ContextMenu';
export { FloatingToolbar } from './FloatingToolbar';
export { GridOverlay } from './GridOverlay';

// Re-export from new locations for backwards compatibility
export { ResizableBottomTray } from '../responsive';
export { PropertiesBottomTray } from '../mobile';

// Types
export type {
  ErrorBoundaryProps,
  ErrorBoundaryState,
  ErrorFallbackProps
} from '../types/error-boundary.types';
export type {
  ContextMenuProps,
  ContextMenuItem,
  FloatingToolbarProps,
  ToolbarItem,
  GridOverlayProps,
  ResizableBottomTrayProps,
  PropertiesBottomTrayProps
} from '../types/overlay.types';
