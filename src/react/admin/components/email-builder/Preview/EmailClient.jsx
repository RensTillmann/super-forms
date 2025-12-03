import React, { useState } from 'react';
import clsx from 'clsx';
import GmailChrome from './ClientChrome/GmailChrome';
import useEmailStore from '../hooks/useEmailStore';

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
      <div className="h-full flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" 
            />
          </svg>
          <p className="mt-2 text-gray-500">Select an email to preview</p>
        </div>
      </div>
    );
  }

  return (
    <div className="h-full flex flex-col bg-gray-100">
      {/* Client Selector */}
      <div className="bg-white border-b px-4 py-2">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-medium text-gray-700">Preview Client</h3>
          <div className="flex gap-1">
            {EMAIL_CLIENTS.map((client) => (
              <button
                key={client.id}
                onClick={() => setSelectedClient(client.id)}
                className={clsx(
                  'px-3 py-1 rounded-md text-sm transition-colors',
                  selectedClient === client.id
                    ? 'bg-primary-500 text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                )}
                title={client.name}
              >
                <span className="mr-1">{client.icon}</span>
                <span className="hidden sm:inline">{client.name}</span>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Email Client Preview */}
      <div className="flex-1 overflow-hidden relative">
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