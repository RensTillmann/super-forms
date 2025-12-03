import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './styles/index.css';

// Global error handler for debugging
window.addEventListener('error', (e) => {
  console.error('Super Forms Email v2: Global error caught:', e.error);
});

// Wait for DOM to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmails);
} else {
  initEmails();
}

function initEmails() {
  try {
    const rootElement = document.getElementById('sfui-admin-root');

    if (!rootElement) {
      console.error('Super Forms Emails: Root element #sfui-admin-root not found');
      return;
    }

    if (!window.sfuiData) {
      console.error('Super Forms Emails: Data not found on window.sfuiData');
      return;
    }

    console.log('Super Forms Emails: Initializing with data:', window.sfuiData);

    const root = ReactDOM.createRoot(rootElement);
    root.render(
      <React.StrictMode>
        <App {...window.sfuiData} />
      </React.StrictMode>
    );

    console.log('Super Forms Emails: App mounted successfully');
  } catch (error) {
    console.error('Super Forms Emails: Error during initialization:', error);
  }
}