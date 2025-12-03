/**
 * Email Builder Components
 * Consolidated from src/react/emails into the admin bundle
 *
 * Main exports:
 * - EmailBuilderIntegrated: Full email builder with Gmail-style chrome
 * - useEmailBuilder: Zustand store for email builder state
 * - useEmailStore: Store for email list management
 */

// Main builder components
export { default as EmailBuilderIntegrated } from './Builder/EmailBuilderIntegrated';
export { default as EmailBuilder } from './Builder/EmailBuilder';
export { default as CanvasIntegrated } from './Builder/CanvasIntegrated';
export { default as Canvas } from './Builder/Canvas';
export { default as ElementPalette } from './Builder/ElementPalette';
export { default as ElementPaletteHorizontal } from './Builder/ElementPaletteHorizontal';
export { default as RawHtmlEditor } from './Builder/RawHtmlEditor';

// Preview components
export { default as EmailClient } from './Preview/EmailClient';
export { default as EmailClientBuilder } from './Preview/EmailClientBuilder';
export { default as EmailContent } from './Preview/EmailContent';
export { default as GmailChrome } from './Preview/ClientChrome/GmailChrome';
export { default as OutlookChrome } from './Preview/ClientChrome/OutlookChrome';
export { default as AppleMailChrome } from './Preview/ClientChrome/AppleMailChrome';

// Property panels
export { default as OptimizedPropertyPanel } from './PropertyPanels/OptimizedPropertyPanel';

// Email generation
export { default as EmailTemplateGenerator, generateHtmlFromElements } from './EmailGeneration/EmailTemplateGenerator';

// Hooks
export { default as useEmailBuilder, useEmailBuilderStore, useSelectedElement } from './hooks/useEmailBuilder';
export { default as useEmailStore } from './hooks/useEmailStore';
export { useDragToAdjust } from './hooks/useDragToAdjust';

// Capabilities
export { getElementCapabilities, hasCapability } from './capabilities/elementCapabilities';

// Shared components
export { default as EmailList } from './EmailList';
export { default as EmailEditor } from './EmailEditor';
export { default as EmailSchedulingModal } from './EmailSchedulingModal';
export { default as ErrorBoundary } from './ErrorBoundary';
export { default as TestEmailBar } from './TestEmailBar';
