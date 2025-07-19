import React, { useState } from 'react';
import clsx from 'clsx';
import GmailChrome from './ClientChrome/GmailChrome';
import useEmailStore from '../../hooks/useEmailStore';

const EMAIL_CLIENTS = [
  { id: 'desktop', name: 'Desktop', icon: '💻', component: GmailChrome },
  { id: 'mobile', name: 'Mobile', icon: '📱', component: GmailChrome },
];

function EmailClient() {
  const [selectedClient, setSelectedClient] = useState('desktop');
  const { activeEmailId, emails, updateEmailField } = useEmailStore();
  
  const activeEmail = emails.find(e => e.id === activeEmailId);
  const ClientComponent = EMAIL_CLIENTS.find(c => c.id === selectedClient)?.component || GmailChrome;

  if (!activeEmail) {
    return (
      <div className="ev2-h-full ev2-flex ev2-items-center ev2-justify-center ev2-bg-gray-50">
        <div className="ev2-text-center">
          <svg className="ev2-mx-auto ev2-h-12 ev2-w-12 ev2-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" 
            />
          </svg>
          <p className="ev2-mt-2 ev2-text-gray-500">Select an email to preview</p>
        </div>
      </div>
    );
  }

  return (
    <div className="ev2-h-full ev2-flex ev2-flex-col ev2-bg-gray-100">
      {/* Client Selector */}
      <div className="ev2-bg-white ev2-border-b ev2-px-4 ev2-py-2">
        <div className="ev2-flex ev2-items-center ev2-justify-between">
          <h3 className="ev2-text-sm ev2-font-medium ev2-text-gray-700">Preview Client</h3>
          <div className="ev2-flex ev2-gap-1">
            {EMAIL_CLIENTS.map((client) => (
              <button
                key={client.id}
                onClick={() => setSelectedClient(client.id)}
                className={clsx(
                  'ev2-px-3 ev2-py-1 ev2-rounded-md ev2-text-sm ev2-transition-colors',
                  selectedClient === client.id
                    ? 'ev2-bg-primary-500 ev2-text-white'
                    : 'ev2-bg-gray-100 ev2-text-gray-600 hover:ev2-bg-gray-200'
                )}
                title={client.name}
              >
                <span className="ev2-mr-1">{client.icon}</span>
                <span className="ev2-hidden sm:ev2-inline">{client.name}</span>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Email Client Preview */}
      <div className="ev2-flex-1 ev2-overflow-hidden">
        <ClientComponent 
          email={activeEmail} 
          isMobile={selectedClient === 'mobile'}
          updateEmailField={updateEmailField}
          activeEmailId={activeEmailId}
        />
      </div>
    </div>
  );
}

export default EmailClient;