import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './styles/index.css';

// Wait for DOM to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmailsV2);
} else {
  initEmailsV2();
}

function initEmailsV2() {
  const rootElement = document.getElementById('super-emails-v2-root');
  
  if (rootElement && window.superEmailsV2Data) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(
      <React.StrictMode>
        <App {...window.superEmailsV2Data} />
      </React.StrictMode>
    );
  }
}