import React from 'react';
import ReactDOM from 'react-dom/client';
import { FormsList } from './FormsList';
import '@/styles/index.css';

// Global error handler for debugging
window.addEventListener('error', (e) => {
  console.error('Super Forms List: Global error caught:', e.error);
});

// Wait for DOM to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initFormsList);
} else {
  initFormsList();
}

function initFormsList() {
  try {
    const rootElement = document.getElementById('sfui-admin-root');

    if (!rootElement) {
      console.error('Super Forms List: Root element #sfui-admin-root not found');
      return;
    }

    if (!window.sfuiData) {
      console.error('Super Forms List: Data not found on window.sfuiData');
      return;
    }

    console.log('Super Forms List: Initializing with data:', window.sfuiData);

    const root = ReactDOM.createRoot(rootElement);
    root.render(
      <React.StrictMode>
        <FormsList {...window.sfuiData} />
      </React.StrictMode>
    );

    console.log('Super Forms List: App mounted successfully');
  } catch (error) {
    console.error('Super Forms List: Error during initialization:', error);
  }
}
