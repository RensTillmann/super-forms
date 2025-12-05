import React from 'react';
import ReactDOM from 'react-dom/client';
import { FormBuilderRouter, SettingsRouter, EntriesRouter } from './router';
import { FormBuilderV2 } from './apps/form-builder-v2';
import './styles/index.css';

// Import types - window.sfuiData is defined in types/global.d.ts

// Global error handler for debugging
window.addEventListener('error', (e) => {
  console.error('SFUI Admin: Global error caught:', e.error);
});

// Wait for DOM to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAdmin);
} else {
  initAdmin();
}

function initAdmin(): void {
  const rootElement = document.getElementById('sfui-admin-root');

  if (!rootElement) {
    // Not on an SFUI admin page, skip silently
    return;
  }

  if (!window.sfuiData) {
    console.error('SFUI Admin: Data not found on window.sfuiData');
    return;
  }

  const { currentPage } = window.sfuiData;

  // Route to the appropriate page router based on WP admin page
  // Each WP page gets its own HashRouter for internal navigation
  switch (currentPage) {
    case 'super_create_form':
      initPage(rootElement, <FormBuilderRouter />);
      break;
    case 'super_form_v2':
      initPage(rootElement, <FormBuilderV2 />);
      break;
    case 'super_settings':
      initPage(rootElement, <SettingsRouter />);
      break;
    case 'super_entries':
      initPage(rootElement, <EntriesRouter />);
      break;
    default:
      // Default to form builder for backwards compatibility
      initPage(rootElement, <FormBuilderRouter />);
  }
}

function initPage(rootElement: HTMLElement, component: React.ReactNode): void {
  try {
    console.log('SFUI Admin: Initializing page:', window.sfuiData?.currentPage);

    const root = ReactDOM.createRoot(rootElement);
    root.render(
      <React.StrictMode>
        {component}
      </React.StrictMode>
    );

    console.log('SFUI Admin: Page mounted successfully');
  } catch (error) {
    console.error('SFUI Admin: Error during initialization:', error);
  }
}
