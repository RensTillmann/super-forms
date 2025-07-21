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
  document.addEventListener('DOMContentLoaded', initEmailsV2);
} else {
  initEmailsV2();
}

function initEmailsV2() {
  try {
    const rootElement = document.getElementById('super-emails-v2-root');
    
    if (!rootElement) {
      console.error('Super Forms Email v2: Root element not found');
      return;
    }
    
    if (!window.superEmailsV2Data) {
      console.error('Super Forms Email v2: Data not found on window.superEmailsV2Data');
      return;
    }
    
    console.log('Super Forms Email v2: Initializing with data:', window.superEmailsV2Data);
    
    const root = ReactDOM.createRoot(rootElement);
    root.render(
      <React.StrictMode>
        <App {...window.superEmailsV2Data} />
      </React.StrictMode>
    );
    
    console.log('Super Forms Email v2: App mounted successfully');
  } catch (error) {
    console.error('Super Forms Email v2: Error during initialization:', error);
  }
}